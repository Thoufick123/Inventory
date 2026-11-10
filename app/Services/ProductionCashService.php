<?php

require_once BASE_PATH . '/app/Services/AuditService.php';
require_once BASE_PATH . '/app/Services/ConcernTransactionService.php';

class ProductionCashService
{
    private static function activeConcernIdForUnit(int $unitId): int
    {
        ConcernTransactionService::assertFacility('PRODUCTION', $unitId);
        return ConcernTransactionService::activeConcernId();
    }

    public static function breakIntoDenominations(float $amount): array
    {
        $denominations = [500 => 0, 200 => 0, 100 => 0, 50 => 0, 20 => 0, 10 => 0, 5 => 0, 'coins' => 0];
        $remaining = max(0, (int)round($amount));
        foreach ([500, 200, 100, 50, 20, 10, 5] as $note) {
            if ($remaining >= $note) {
                $denominations[$note] = intdiv($remaining, $note);
                $remaining %= $note;
            }
        }
        $denominations['coins'] = $remaining;
        return $denominations;
    }

    public static function getActiveRegister(int $unitId): ?array
    {
        $concernId = self::activeConcernIdForUnit($unitId);
        $register = Database::fetchOne(
            "SELECT cr.*, pu.name AS production_unit_name, pu.code AS production_unit_code,
                    COALESCE(cr.cashier_name, u.full_name) AS cashier_name
             FROM production_cash_registers cr
             JOIN production_units pu ON pu.id = cr.production_unit_id
             LEFT JOIN users u ON u.id = cr.cashier_id
             WHERE cr.production_unit_id = ? AND cr.concern_id = ? AND cr.status = 'OPEN'
             ORDER BY cr.id DESC LIMIT 1",
            [$unitId, $concernId]
        );
        if (!$register) {
            return null;
        }

        $flow = Database::fetchOne(
            "SELECT COALESCE(SUM(CASE WHEN transaction_type = 'CASH_IN' THEN amount ELSE 0 END), 0) AS cash_in,
                    COALESCE(SUM(CASE WHEN transaction_type = 'EXPENSE' AND payment_mode = 'CASH' THEN amount ELSE 0 END), 0) AS cash_out,
                    COALESCE(SUM(CASE WHEN transaction_type = 'BANK_DEPOSIT' THEN amount ELSE 0 END), 0) AS bank_deposit
             FROM production_cash_transactions WHERE register_id = ? AND concern_id = ?",
            [(int)$register['id'], $concernId]
        ) ?: [];

        $register['cash_in_amount'] = (float)($flow['cash_in'] ?? 0);
        $register['cash_out_amount'] = (float)($flow['cash_out'] ?? 0);
        $register['bank_deposit_amount'] = (float)($flow['bank_deposit'] ?? 0);
        $register['expected_cash'] = (float)$register['opening_balance']
            + $register['cash_in_amount'] - $register['cash_out_amount'] - $register['bank_deposit_amount'];

        Database::execute(
            "UPDATE production_cash_registers
             SET cash_in_amount = ?, cash_out_amount = ?, bank_deposit_amount = ?, expected_cash = ?, updated_at = NOW()
             WHERE id = ? AND concern_id = ?",
            [
                $register['cash_in_amount'], $register['cash_out_amount'],
                $register['bank_deposit_amount'], $register['expected_cash'], (int)$register['id'], $concernId,
            ]
        );
        return $register;
    }

    public static function getLastClosedRegister(int $unitId): ?array
    {
        $concernId = self::activeConcernIdForUnit($unitId);
        $register = Database::fetchOne(
            "SELECT cr.*, COALESCE(cr.cashier_name, u.full_name) AS cashier_name, closer.full_name AS closed_by_name,
                    pu.name AS production_unit_name, pu.code AS production_unit_code
             FROM production_cash_registers cr
             JOIN production_units pu ON pu.id = cr.production_unit_id
             LEFT JOIN users u ON u.id = cr.cashier_id
             LEFT JOIN users closer ON closer.id = cr.closed_by
             WHERE cr.production_unit_id = ? AND cr.concern_id = ? AND cr.status = 'CLOSED'
             ORDER BY cr.closing_time DESC, cr.id DESC LIMIT 1",
            [$unitId, $concernId]
        );
        if (!$register) {
            return null;
        }

        $denominations = json_decode((string)($register['closing_denominations'] ?? ''), true);
        if (!is_array($denominations) || !$denominations) {
            $denominations = json_decode((string)($register['opening_denominations'] ?? ''), true);
        }
        $cash = (float)($register['closing_cash_counted'] ?? $register['expected_cash'] ?? 0);
        if (!is_array($denominations) || !$denominations || abs(self::denominationTotal($denominations) - $cash) > 0.01) {
            $denominations = self::breakIntoDenominations($cash);
        }
        $register['resolved_denominations'] = $denominations;
        return $register;
    }

    public static function openRegister(
        int $unitId,
        int $userId,
        float $openingBalance,
        array $denominations,
        float $expectedFloat,
        string $discrepancyReason,
        string $cashierName,
        string $notes
    ): array {
        if ($openingBalance < 0 || $expectedFloat < 0) {
            throw new InvalidArgumentException('Opening cash values cannot be negative.');
        }
        if (trim($cashierName) === '') {
            throw new InvalidArgumentException('Enter the cashier or custodian name.');
        }
        Database::beginTransaction();
        try {
            $concernId = self::activeConcernIdForUnit($unitId);
            if (!Database::fetchOne(
                'SELECT id FROM production_units WHERE id = ? AND concern_id = ? FOR UPDATE',
                [$unitId, $concernId]
            )) {
                throw new InvalidArgumentException('Production unit not found in the active concern.');
            }
            $active = self::getActiveRegister($unitId);
            if ($active) {
                throw new RuntimeException("A production cash shift is already open ({$active['register_number']}). Close it first.");
            }

            if ($expectedFloat <= 0) {
                $lastClosed = self::getLastClosedRegister($unitId);
                $expectedFloat = (float)($lastClosed['closing_cash_counted'] ?? $lastClosed['expected_cash'] ?? 0);
            }
            $denominationTotal = self::denominationTotal($denominations);
            $hasDenominations = $denominationTotal > 0;
            $actualOpening = $hasDenominations ? $denominationTotal : $openingBalance;
            $difference = round($actualOpening - $expectedFloat, 2);
            if ($expectedFloat > 0 && abs($difference) > 0.01 && trim($discrepancyReason) === '') {
                throw new InvalidArgumentException('Explain the opening float difference before opening this shift.');
            }

            $unit = ProductionUnit::find($unitId);
            if (!$unit) {
                throw new InvalidArgumentException('Production unit not found.');
            }
            $registerNumber = self::nextNumber('production_cash_registers', 'register_number', 'PREG-' . ($unit['code'] ?: $unitId) . '-' . date('Ymd'));
            Database::execute(
                "INSERT INTO production_cash_registers
                    (production_unit_id, concern_id, register_number, cashier_id, cashier_name, opening_time,
                     opening_balance, opening_expected_float, opening_difference, opening_denominations,
                     opening_notes, opening_discrepancy_reason, expected_cash, status)
                 VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, 'OPEN')",
                [
                    $unitId, $concernId, $registerNumber, $userId, $cashierName, $actualOpening, $expectedFloat,
                    $difference, $denominations ? json_encode($denominations) : null, $notes,
                    $discrepancyReason ?: null, $actualOpening,
                ]
            );
            $registerId = (int)Database::lastInsertId();
            if ($actualOpening > 0) {
                self::logTransaction([
                    'unit_id' => $unitId, 'register_id' => $registerId,
                    'transaction_type' => 'OPENING_FLOAT', 'flow_direction' => 'IN',
                    'amount' => $actualOpening, 'payment_mode' => 'CASH', 'category' => 'FLOAT',
                    'reference_id' => $registerNumber, 'source_table' => 'production_cash_registers',
                    'source_id' => $registerId, 'handled_by' => $userId,
                    'description' => 'Opening production cash float',
                ]);
            }
            AuditService::log('production_cash', 'OPEN_SHIFT', 'production_cash_registers', $registerId, null, [
                'production_unit_id' => $unitId, 'register_number' => $registerNumber,
                'opening_balance' => $actualOpening, 'opening_difference' => $difference,
            ], $userId);
            Database::commit();
            return ['register_id' => $registerId, 'register_number' => $registerNumber, 'opening_balance' => $actualOpening, 'opening_difference' => $difference];
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function closeRegister(int $unitId, int $registerId, int $userId, float $countedCash, array $denominations, string $reason, string $notes): array
    {
        $concernId = self::activeConcernIdForUnit($unitId);
        Database::beginTransaction();
        try {
            $register = Database::fetchOne(
                "SELECT * FROM production_cash_registers WHERE id = ? AND production_unit_id = ? AND concern_id = ? FOR UPDATE",
                [$registerId, $unitId, $concernId]
            );
            if (!$register || $register['status'] !== 'OPEN') {
                throw new RuntimeException('This shift is missing, belongs to another unit, or is already closed.');
            }
            $active = self::getActiveRegister($unitId);
            $expected = (float)($active['expected_cash'] ?? $register['expected_cash']);
            $difference = round($countedCash - $expected, 2);
            if ($countedCash < 0 || (abs($difference) > 0.01 && trim($reason) === '')) {
                throw new InvalidArgumentException('Enter a non-negative cash count and explain any shortage or overage.');
            }
            Database::execute(
                "UPDATE production_cash_registers
                 SET closing_time = NOW(), closed_by = ?, closing_cash_counted = ?, closing_denominations = ?,
                     cash_difference = ?, discrepancy_reason = ?, status = 'CLOSED', notes = ?, updated_at = NOW()
                 WHERE id = ? AND concern_id = ?",
                [$userId, $countedCash, $denominations ? json_encode($denominations) : null, $difference, $reason ?: null, $notes, $registerId, $concernId]
            );
            if (abs($difference) > 0.01) {
                self::logTransaction([
                    'unit_id' => $unitId, 'register_id' => $registerId,
                    'transaction_type' => 'CLOSING_ADJUSTMENT',
                    'flow_direction' => $difference > 0 ? 'IN' : 'OUT', 'amount' => abs($difference),
                    'payment_mode' => 'CASH', 'category' => $difference > 0 ? 'OVERAGE' : 'SHORTAGE',
                    'reference_id' => $register['register_number'], 'source_table' => 'production_cash_registers',
                    'source_id' => $registerId, 'handled_by' => $userId,
                    'description' => 'Production cash shift closing variance: ' . $reason,
                ]);
            }
            AuditService::log('production_cash', 'CLOSE_SHIFT', 'production_cash_registers', $registerId, ['status' => 'OPEN'], [
                'status' => 'CLOSED', 'expected_cash' => $expected, 'closing_cash_counted' => $countedCash,
                'cash_difference' => $difference, 'reason' => $reason,
            ], $userId);
            Database::commit();
            return ['register_number' => $register['register_number'], 'expected_cash' => $expected, 'cash_difference' => $difference];
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function recordExpense(array $payload, int $userId): array
    {
        $unitId = (int)$payload['production_unit_id'];
        $concernId = self::activeConcernIdForUnit($unitId);
        $amount = (float)($payload['amount'] ?? 0);
        $category = trim((string)($payload['expense_category'] ?? 'General'));
        $paidTo = trim((string)($payload['paid_to'] ?? ''));
        $paymentMode = strtoupper(trim((string)($payload['payment_mode'] ?? 'CASH')));
        self::validateAmountMode($amount, $paymentMode);
        if ($category === '' || $paidTo === '') {
            throw new InvalidArgumentException('Expense category and recipient are required.');
        }
        $active = self::getActiveRegister($unitId);
        Database::beginTransaction();
        try {
            $voucher = self::nextNumber('production_cash_expenses', 'voucher_number', 'PEXP-' . date('Ymd'));
            Database::execute(
                "INSERT INTO production_cash_expenses
                    (production_unit_id, concern_id, register_id, voucher_number, expense_category, amount, paid_to,
                     payment_mode, bill_number, approved_by, created_by, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$unitId, $concernId, $active['id'] ?? null, $voucher, $category, $amount, $paidTo, $paymentMode,
                 trim((string)($payload['bill_number'] ?? '')) ?: null, $userId, $userId,
                 trim((string)($payload['notes'] ?? '')) ?: null]
            );
            $expenseId = (int)Database::lastInsertId();
            self::logTransaction([
                'unit_id' => $unitId, 'register_id' => $active['id'] ?? null,
                'transaction_type' => 'EXPENSE', 'flow_direction' => 'OUT', 'amount' => $amount,
                'payment_mode' => $paymentMode, 'category' => $category, 'reference_id' => $voucher,
                'source_table' => 'production_cash_expenses', 'source_id' => $expenseId,
                'handled_by' => $userId, 'description' => "Production expense: {$category} to {$paidTo}",
            ]);
            AuditService::log('production_cash', 'CREATE_EXPENSE', 'production_cash_expenses', $expenseId, null, [
                'production_unit_id' => $unitId, 'voucher_number' => $voucher, 'amount' => $amount,
            ], $userId);
            if ($active) {
                self::getActiveRegister($unitId);
            }
            Database::commit();
            return ['expense_id' => $expenseId, 'voucher_number' => $voucher, 'amount' => $amount];
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function recordCashIn(array $payload, int $userId): array
    {
        $unitId = (int)$payload['production_unit_id'];
        self::activeConcernIdForUnit($unitId);
        $amount = (float)($payload['amount'] ?? 0);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Cash top-up must be greater than zero.');
        }
        $active = self::getActiveRegister($unitId);
        Database::beginTransaction();
        try {
            $reference = 'PCIN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            self::logTransaction([
                'unit_id' => $unitId, 'register_id' => $active['id'] ?? null,
                'transaction_type' => 'CASH_IN', 'flow_direction' => 'IN', 'amount' => $amount,
                'payment_mode' => 'CASH', 'category' => trim((string)($payload['category'] ?? 'TOP_UP')),
                'reference_id' => $reference, 'source_table' => 'production_cash_registers',
                'source_id' => $active['id'] ?? null, 'handled_by' => $userId,
                'description' => 'Cash top-up from ' . trim((string)($payload['source'] ?? 'Production treasury')) . ': ' . trim((string)($payload['notes'] ?? '')),
            ]);
            if ($active) {
                self::getActiveRegister($unitId);
            }
            AuditService::log('production_cash', 'CASH_IN', 'production_cash_registers', (int)($active['id'] ?? 0), null, ['amount' => $amount, 'source' => $payload['source'] ?? ''], $userId);
            Database::commit();
            return ['amount' => $amount, 'reference' => $reference];
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function recordBankDeposit(array $payload, int $userId): array
    {
        $unitId = (int)$payload['production_unit_id'];
        $concernId = self::activeConcernIdForUnit($unitId);
        $amount = (float)($payload['amount'] ?? 0);
        $bankName = trim((string)($payload['bank_name'] ?? ''));
        if ($amount <= 0 || $bankName === '') {
            throw new InvalidArgumentException('A positive deposit amount and bank name are required.');
        }
        $active = self::getActiveRegister($unitId);
        Database::beginTransaction();
        try {
            $depositNumber = self::nextNumber('production_cash_deposits', 'deposit_number', 'PDEP-' . date('Ymd'));
            Database::execute(
                "INSERT INTO production_cash_deposits
                    (production_unit_id, concern_id, register_id, deposit_number, bank_name, account_number, amount,
                     slip_number, deposit_date, deposited_by, verified_by, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'VERIFIED', ?)",
                [$unitId, $concernId, $active['id'] ?? null, $depositNumber, $bankName,
                 trim((string)($payload['account_number'] ?? '')) ?: null, $amount,
                 trim((string)($payload['slip_number'] ?? '')) ?: null,
                 $payload['deposit_date'] ?? date('Y-m-d'), $userId, $userId,
                 trim((string)($payload['notes'] ?? '')) ?: null]
            );
            $depositId = (int)Database::lastInsertId();
            self::logTransaction([
                'unit_id' => $unitId, 'register_id' => $active['id'] ?? null,
                'transaction_type' => 'BANK_DEPOSIT', 'flow_direction' => 'OUT', 'amount' => $amount,
                'payment_mode' => 'CASH', 'category' => 'BANK_DEPOSIT', 'reference_id' => $depositNumber,
                'source_table' => 'production_cash_deposits', 'source_id' => $depositId,
                'handled_by' => $userId, 'description' => "Production cash deposit to {$bankName}",
            ]);
            if ($active) {
                self::getActiveRegister($unitId);
            }
            AuditService::log('production_cash', 'BANK_DEPOSIT', 'production_cash_deposits', $depositId, null, [
                'production_unit_id' => $unitId, 'deposit_number' => $depositNumber, 'amount' => $amount,
            ], $userId);
            Database::commit();
            return ['deposit_id' => $depositId, 'deposit_number' => $depositNumber, 'amount' => $amount];
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function getSummary(int $unitId, string $startDate, string $endDate): array
    {
        $concernId = self::activeConcernIdForUnit($unitId);
        $active = self::getActiveRegister($unitId);
        $flows = Database::fetchOne(
            "SELECT COALESCE(SUM(CASE WHEN flow_direction = 'IN' AND payment_mode = 'CASH' AND transaction_type != 'OPENING_FLOAT' THEN amount ELSE 0 END), 0) AS cash_inflow,
                COALESCE(SUM(CASE WHEN flow_direction = 'OUT' AND payment_mode = 'CASH' THEN amount ELSE 0 END), 0) AS cash_outflow,
                    COALESCE(SUM(CASE WHEN transaction_type = 'EXPENSE' THEN amount ELSE 0 END), 0) AS expenses,
                    COALESCE(SUM(CASE WHEN transaction_type = 'CASH_IN' THEN amount ELSE 0 END), 0) AS cash_topups,
                    COALESCE(SUM(CASE WHEN transaction_type = 'BANK_DEPOSIT' THEN amount ELSE 0 END), 0) AS deposits
             FROM production_cash_transactions
             WHERE production_unit_id = ? AND concern_id = ? AND DATE(created_at) BETWEEN ? AND ?",
            [$unitId, $concernId, $startDate, $endDate]
        ) ?: [];
        $shiftCounts = Database::fetchOne(
            "SELECT COUNT(*) AS total_shifts,
                    COALESCE(SUM(status = 'OPEN'), 0) AS open_shifts,
                    COALESCE(SUM(status = 'CLOSED'), 0) AS closed_shifts
             FROM production_cash_registers WHERE production_unit_id = ? AND concern_id = ?",
            [$unitId, $concernId]
        ) ?: [];
        return [
            'current_till_cash' => (float)($active['expected_cash'] ?? 0),
            'cash_inflow' => (float)($flows['cash_inflow'] ?? 0),
            'cash_outflow' => (float)($flows['cash_outflow'] ?? 0),
            'expenses' => (float)($flows['expenses'] ?? 0),
            'cash_topups' => (float)($flows['cash_topups'] ?? 0),
            'bank_deposits' => (float)($flows['deposits'] ?? 0),
            'net_cash_flow' => (float)($flows['cash_inflow'] ?? 0) - (float)($flows['cash_outflow'] ?? 0),
            'total_shifts' => (int)($shiftCounts['total_shifts'] ?? 0),
            'open_shifts' => (int)($shiftCounts['open_shifts'] ?? 0),
            'closed_shifts' => (int)($shiftCounts['closed_shifts'] ?? 0),
        ];
    }

    public static function getLedger(int $unitId, array $filters = []): array
    {
        $where = ['ct.production_unit_id = ?', 'ct.concern_id = ?'];
        $params = [$unitId, self::activeConcernIdForUnit($unitId)];
        foreach (['type' => 'transaction_type', 'flow' => 'flow_direction', 'payment_mode' => 'payment_mode'] as $filter => $column) {
            if (!empty($filters[$filter])) {
                $where[] = "ct.{$column} = ?";
                $params[] = $filters[$filter];
            }
        }
        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(ct.created_at) >= ?';
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(ct.created_at) <= ?';
            $params[] = $filters['end_date'];
        }
        $limit = max(1, min(500, (int)($filters['limit'] ?? 100)));
        return Database::fetchAll(
            "SELECT ct.*, u.full_name AS staff_name, cr.register_number
             FROM production_cash_transactions ct
             LEFT JOIN users u ON u.id = ct.handled_by
             LEFT JOIN production_cash_registers cr ON cr.id = ct.register_id
             WHERE " . implode(' AND ', $where) . " ORDER BY ct.id DESC LIMIT {$limit}",
            $params
        );
    }

    public static function getShiftDetails(int $unitId, int $registerId): ?array
    {
        $concernId = self::activeConcernIdForUnit($unitId);
        $register = Database::fetchOne(
            "SELECT cr.*, pu.name AS production_unit_name, pu.code AS production_unit_code,
                    opener.full_name AS open_cashier_name, closer.full_name AS close_cashier_name
             FROM production_cash_registers cr
             JOIN production_units pu ON pu.id = cr.production_unit_id
             LEFT JOIN users opener ON opener.id = cr.cashier_id
             LEFT JOIN users closer ON closer.id = cr.closed_by
             WHERE cr.id = ? AND cr.production_unit_id = ? AND cr.concern_id = ?",
            [$registerId, $unitId, $concernId]
        );
        if (!$register) {
            return null;
        }
        return [
            'register' => $register,
            'transactions' => Database::fetchAll(
                "SELECT ct.*, u.full_name AS staff_name FROM production_cash_transactions ct
                 LEFT JOIN users u ON u.id = ct.handled_by WHERE ct.register_id = ? AND ct.concern_id = ? ORDER BY ct.id ASC",
                [$registerId, $concernId]
            ),
            'expenses' => Database::fetchAll('SELECT * FROM production_cash_expenses WHERE register_id = ? AND concern_id = ? ORDER BY id ASC', [$registerId, $concernId]),
            'deposits' => Database::fetchAll('SELECT * FROM production_cash_deposits WHERE register_id = ? AND concern_id = ? ORDER BY id ASC', [$registerId, $concernId]),
        ];
    }

    public static function getExpenseDetails(int $unitId, int $expenseId): ?array
    {
        $concernId = self::activeConcernIdForUnit($unitId);
        return Database::fetchOne(
            "SELECT e.*, u.full_name AS staff_name, approver.full_name AS approver_name,
                    pu.name AS production_unit_name, pu.code AS production_unit_code, cr.register_number
             FROM production_cash_expenses e
             JOIN production_units pu ON pu.id = e.production_unit_id
             LEFT JOIN users u ON u.id = e.created_by
             LEFT JOIN users approver ON approver.id = e.approved_by
             LEFT JOIN production_cash_registers cr ON cr.id = e.register_id
             WHERE e.id = ? AND e.production_unit_id = ? AND e.concern_id = ?",
            [$expenseId, $unitId, $concernId]
        );
    }

    private static function logTransaction(array $data): int
    {
        $unitId = (int)$data['unit_id'];
        $concernId = self::activeConcernIdForUnit($unitId);
        $registerId = isset($data['register_id']) ? (int)$data['register_id'] : null;
        if ($registerId !== null && !Database::fetchOne(
            'SELECT id FROM production_cash_registers WHERE id = ? AND production_unit_id = ? AND concern_id = ?',
            [$registerId, $unitId, $concernId]
        )) {
            throw new InvalidArgumentException('Production cash register does not belong to the active concern and unit.');
        }
        $number = self::nextNumber('production_cash_transactions', 'transaction_number', 'PCTX-' . date('Ymd'));
        Database::execute(
            "INSERT INTO production_cash_transactions
                (production_unit_id, concern_id, register_id, transaction_number, transaction_type, flow_direction,
                 amount, payment_mode, category, reference_id, source_table, source_id, handled_by, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$unitId, $concernId, $registerId, $number, $data['transaction_type'],
             $data['flow_direction'], $data['amount'], $data['payment_mode'] ?? 'CASH',
             $data['category'] ?? 'GENERAL', $data['reference_id'] ?? null, $data['source_table'] ?? null,
             $data['source_id'] ?? null, $data['handled_by'], $data['description'] ?? null]
        );
        return (int)Database::lastInsertId();
    }

    private static function nextNumber(string $table, string $column, string $prefix): string
    {
        $last = Database::fetchOne("SELECT `{$column}` AS item_number FROM `{$table}` WHERE `{$column}` LIKE ? AND concern_id = ? ORDER BY id DESC LIMIT 1", [$prefix . '-%', ConcernTransactionService::activeConcernId()]);
        $sequence = $last ? ((int)substr((string)$last['item_number'], -4) + 1) : 1;
        return $prefix . '-' . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
    }

    private static function denominationTotal(array $denominations): float
    {
        $total = 0.0;
        foreach ($denominations as $value => $count) {
            $multiplier = in_array((string)$value, ['coins', 'coin'], true) ? 1 : (float)$value;
            $total += $multiplier * max(0, (int)$count);
        }
        return $total;
    }

    private static function validateAmountMode(float $amount, string $paymentMode): void
    {
        if ($amount <= 0 || !is_finite($amount)) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }
        if (!in_array($paymentMode, ['CASH', 'UPI', 'BANK_TRANSFER', 'CARD', 'OTHER'], true)) {
            throw new InvalidArgumentException('Select a valid payment mode.');
        }
    }
}