<?php
/**
 * Cash Service — Complete Retail Shop Cash Management & Flow Register
 * Handles Daily Shifts, Opening/Closing Floats, Denominations, Petty Cash Expenses,
 * Bank Drops, Live Till Reconciliation, POS Cash Sync, and Thermal Shift Slips.
 */

require_once BASE_PATH . '/app/Services/AuditService.php';

class CashService {

    /**
     * Get the active (open) register shift for a shop.
     */
    public static function getActiveRegister(int $shopId): ?array {
        $register = Database::fetchOne(
            "SELECT cr.*, COALESCE(cr.cashier_name, u.full_name) as cashier_name, u.email as cashier_email
             FROM cash_registers cr
             JOIN users u ON cr.cashier_id = u.id
             WHERE cr.shop_id = ? AND cr.status = 'OPEN'
             ORDER BY cr.id DESC LIMIT 1",
            [$shopId]
        );

        if (!$register) return null;

        // Recompute real-time live aggregates for this shift
        $regId = (int)$register['id'];
        $flow = Database::fetchOne(
            "SELECT 
                COALESCE(SUM(CASE WHEN transaction_type = 'SALE_CASH' THEN amount ELSE 0 END), 0) as live_cash_sales,
                COALESCE(SUM(CASE WHEN transaction_type = 'SALE_UPI' THEN amount ELSE 0 END), 0) as live_upi_sales,
                COALESCE(SUM(CASE WHEN transaction_type = 'SALE_CARD' THEN amount ELSE 0 END), 0) as live_card_sales,
                COALESCE(SUM(CASE WHEN transaction_type = 'CASH_IN' THEN amount ELSE 0 END), 0) as live_cash_in,
                COALESCE(SUM(CASE WHEN transaction_type = 'EXPENSE' AND payment_mode = 'CASH' THEN amount ELSE 0 END), 0) as live_cash_out,
                COALESCE(SUM(CASE WHEN transaction_type = 'REFUND_CASH' THEN amount ELSE 0 END), 0) as live_cash_refund,
                COALESCE(SUM(CASE WHEN transaction_type = 'BANK_DEPOSIT' THEN amount ELSE 0 END), 0) as live_bank_deposit
             FROM cash_transactions
             WHERE register_id = ?",
            [$regId]
        );

        $opening = (float)$register['opening_balance'];
        $cashSales = (float)($flow['live_cash_sales'] ?? 0);
        $cashIn = (float)($flow['live_cash_in'] ?? 0);
        $cashOut = (float)($flow['live_cash_out'] ?? 0);
        $cashRefund = (float)($flow['live_cash_refund'] ?? 0);
        $bankDeposit = (float)($flow['live_bank_deposit'] ?? 0);

        $expectedCash = $opening + $cashSales + $cashIn - $cashOut - $cashRefund - $bankDeposit;

        // Sync with db record if changed
        Database::execute(
            "UPDATE cash_registers SET 
                cash_sales_amount = ?,
                upi_sales_amount = ?,
                card_sales_amount = ?,
                cash_in_amount = ?,
                cash_out_amount = ?,
                cash_refund_amount = ?,
                bank_deposit_amount = ?,
                expected_cash = ?,
                updated_at = NOW()
             WHERE id = ?",
            [
                $cashSales,
                (float)($flow['live_upi_sales'] ?? 0),
                (float)($flow['live_card_sales'] ?? 0),
                $cashIn,
                $cashOut,
                $cashRefund,
                $bankDeposit,
                $expectedCash,
                $regId
            ]
        );

        $register['cash_sales_amount'] = $cashSales;
        $register['upi_sales_amount'] = (float)($flow['live_upi_sales'] ?? 0);
        $register['card_sales_amount'] = (float)($flow['live_card_sales'] ?? 0);
        $register['cash_in_amount'] = $cashIn;
        $register['cash_out_amount'] = $cashOut;
        $register['cash_refund_amount'] = $cashRefund;
        $register['bank_deposit_amount'] = $bankDeposit;
        $register['expected_cash'] = $expectedCash;

        return $register;
    }

    /**
     * Compute standard denomination counts for a given total cash amount.
     */
    public static function breakIntoDenominations(float $amount): array {
        $denoms = [500 => 0, 200 => 0, 100 => 0, 50 => 0, 20 => 0, 10 => 0, 5 => 0, 'coins' => 0];
        $remaining = (int)round($amount);
        $notes = [500, 200, 100, 50, 20, 10, 5];
        foreach ($notes as $note) {
            if ($remaining >= $note) {
                $count = intdiv($remaining, $note);
                $denoms[$note] = $count;
                $remaining %= $note;
            }
        }
        if ($remaining > 0) {
            $denoms['coins'] = $remaining;
        }
        return $denoms;
    }

    /**
     * Get the most recently closed register shift for a shop.
     */
    public static function getLastClosedRegister(int $shopId): ?array {
        $reg = Database::fetchOne(
            "SELECT cr.*, COALESCE(cr.cashier_name, u.full_name) as cashier_name, cu.full_name as closed_by_name
             FROM cash_registers cr
             LEFT JOIN users u ON cr.cashier_id = u.id
             LEFT JOIN users cu ON cr.closed_by = cu.id
             WHERE cr.shop_id = ? AND cr.status = 'CLOSED'
             ORDER BY cr.closing_time DESC, cr.id DESC LIMIT 1",
            [$shopId]
        );

        if (!$reg) return null;

        $float = (float)($reg['closing_cash_counted'] ?? $reg['expected_cash'] ?? 0);
        $denoms = [];

        // 1. Try closing denominations
        if (!empty($reg['closing_denominations'])) {
            $d = is_string($reg['closing_denominations']) ? json_decode($reg['closing_denominations'], true) : $reg['closing_denominations'];
            if (is_array($d) && !empty($d)) {
                $denoms = $d;
            }
        }

        // 2. Fallback to opening denominations
        if (empty($denoms) && !empty($reg['opening_denominations'])) {
            $d = is_string($reg['opening_denominations']) ? json_decode($reg['opening_denominations'], true) : $reg['opening_denominations'];
            if (is_array($d) && !empty($d)) {
                $denoms = $d;
            }
        }

        // 3. Verify sum matches; if not or if empty, auto-generate standard denominations
        $sum = 0;
        foreach ($denoms as $k => $v) {
            $m = ($k === 'coins' || $k === 'coin') ? 1 : (float)$k;
            $sum += $m * (int)$v;
        }

        if (abs($sum - $float) > 0.01 && $float > 0) {
            $denoms = self::breakIntoDenominations($float);
        }

        $reg['resolved_denominations'] = $denoms;
        return $reg;
    }

    /**
     * Open a new Cash Register Shift with Opening Float, Denominations & Mismatch Tracking.
     */
    public static function openRegister(
        int $shopId, 
        int $userId, 
        float $openingBalance, 
        array $denominations = [], 
        string $notes = '',
        float $expectedFloat = 0.00,
        string $discrepancyReason = '',
        string $cashierName = ''
    ): array {
        $active = self::getActiveRegister($shopId);
        if ($active) {
            throw new RuntimeException("A cash register shift is already open ({$active['register_number']}). Please close the current shift first.");
        }

        // If expected float was not passed, resolve from last closed register
        if ($expectedFloat <= 0) {
            $lastClosed = self::getLastClosedRegister($shopId);
            if ($lastClosed) {
                $expectedFloat = (float)($lastClosed['closing_cash_counted'] ?? $lastClosed['expected_cash'] ?? 0);
            }
        }

        // Compute denomination sum if provided
        $denomSum = 0;
        $hasDenoms = false;
        if (!empty($denominations) && is_array($denominations)) {
            foreach ($denominations as $k => $v) {
                $multiplier = ($k === 'coins' || $k === 'coin') ? 1 : (float)$k;
                $count = (int)$v;
                if ($count > 0) {
                    $denomSum += $multiplier * $count;
                    $hasDenoms = true;
                }
            }
        }

        $actualStartingCash = $hasDenoms ? $denomSum : $openingBalance;
        $openingDifference = round($actualStartingCash - $expectedFloat, 2);

        // If there's an expected float and a difference, ensure discrepancy reason is set
        if ($expectedFloat > 0 && abs($openingDifference) > 0.01 && empty($discrepancyReason)) {
            $discrepancyReason = !empty($notes) ? $notes : "Starting float difference: " . ($openingDifference > 0 ? "+₹" : "-₹") . abs($openingDifference);
        }

        Database::beginTransaction();
        try {
            $today = date('Ymd');
            $prefix = 'REG-' . $today . '-';
            $lastReg = Database::fetchOne(
                "SELECT register_number FROM cash_registers WHERE register_number LIKE ? ORDER BY id DESC LIMIT 1",
                [$prefix . '%']
            );
            $seq = 1;
            if ($lastReg && !empty($lastReg['register_number'])) {
                $parts = explode('-', $lastReg['register_number']);
                $seq = (int)end($parts) + 1;
            }
            $registerNumber = sprintf('%s%04d', $prefix, $seq);

            $denomJson = !empty($denominations) ? json_encode($denominations) : null;

            Database::execute(
                "INSERT INTO cash_registers (
                    shop_id, register_number, cashier_id, cashier_name, opening_time, opening_balance,
                    opening_expected_float, opening_difference,
                    opening_denominations, opening_notes, opening_discrepancy_reason,
                    expected_cash, status, created_at
                ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, 'OPEN', NOW())",
                [
                    $shopId,
                    $registerNumber,
                    $userId,
                    $cashierName ?: null,
                    $actualStartingCash,
                    $expectedFloat,
                    $openingDifference,
                    $denomJson,
                    $notes,
                    $discrepancyReason,
                    $actualStartingCash
                ]
            );
            $registerId = (int)Database::lastInsertId();

            // Record initial float transaction
            if ($actualStartingCash > 0) {
                self::logTransaction([
                    'shop_id'          => $shopId,
                    'register_id'      => $registerId,
                    'transaction_type' => 'OPENING_FLOAT',
                    'flow_direction'   => 'IN',
                    'amount'           => $actualStartingCash,
                    'payment_mode'     => 'CASH',
                    'category'         => 'FLOAT',
                    'reference_id'     => $registerNumber,
                    'source_table'     => 'cash_registers',
                    'source_id'        => $registerId,
                    'handled_by'       => $userId,
                    'description'      => "Opening Cash Float for Shift: {$registerNumber}" . (abs($openingDifference) > 0.01 ? " (Mismatch: " . ($openingDifference > 0 ? "+" : "") . number_format($openingDifference, 2) . ")" : "")
                ]);
            }

            AuditService::log('shop_cash', 'OPEN_SHIFT', 'cash_registers', $registerId, null, [
                'register_number'    => $registerNumber,
                'cashier_name'       => $cashierName,
                'opening_balance'    => $openingBalance,
                'expected_float'     => $expectedFloat,
                'opening_difference' => $openingDifference,
                'discrepancy_reason' => $discrepancyReason
            ]);

            Database::commit();

            return [
                'register_id'        => $registerId,
                'register_number'    => $registerNumber,
                'opening_balance'    => $openingBalance,
                'opening_difference' => $openingDifference
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Close an Active Cash Register Shift with Physical Cash Count & Denominations.
     */
    public static function closeRegister(int $registerId, int $userId, float $countedCash, array $denominations = [], string $reason = '', string $notes = ''): array {
        $register = Database::fetchOne("SELECT * FROM cash_registers WHERE id = ?", [$registerId]);
        if (!$register) {
            throw new InvalidArgumentException("Cash register record #{$registerId} not found.");
        }
        if ($register['status'] === 'CLOSED') {
            throw new RuntimeException("This cash register shift is already closed.");
        }

        // Recompute active register aggregates
        self::getActiveRegister((int)$register['shop_id']);
        $register = Database::fetchOne("SELECT * FROM cash_registers WHERE id = ?", [$registerId]);

        $expectedCash = (float)$register['expected_cash'];
        $cashDiff = $countedCash - $expectedCash;
        $denomJson = !empty($denominations) ? json_encode($denominations) : null;

        Database::beginTransaction();
        try {
            Database::execute(
                "UPDATE cash_registers SET 
                    closing_time = NOW(),
                    closed_by = ?,
                    closing_cash_counted = ?,
                    closing_denominations = ?,
                    cash_difference = ?,
                    discrepancy_reason = ?,
                    status = 'CLOSED',
                    notes = ?,
                    updated_at = NOW()
                 WHERE id = ?",
                [
                    $userId,
                    $countedCash,
                    $denomJson,
                    $cashDiff,
                    $reason,
                    $notes,
                    $registerId
                ]
            );

            // If discrepancy exists, log a closing adjustment transaction
            if (abs($cashDiff) > 0.01) {
                self::logTransaction([
                    'shop_id'          => (int)$register['shop_id'],
                    'register_id'      => $registerId,
                    'transaction_type' => 'CLOSING_ADJUSTMENT',
                    'flow_direction'   => ($cashDiff > 0 ? 'IN' : 'OUT'),
                    'amount'           => abs($cashDiff),
                    'payment_mode'     => 'CASH',
                    'category'         => ($cashDiff > 0 ? 'OVERAGE' : 'SHORTAGE'),
                    'reference_id'     => $register['register_number'],
                    'source_table'     => 'cash_registers',
                    'source_id'        => $registerId,
                    'handled_by'       => $userId,
                    'description'      => "Shift Closing Variance: " . ($cashDiff > 0 ? "+₹".number_format($cashDiff, 2)." Overage" : "-₹".number_format(abs($cashDiff), 2)." Shortage") . (!empty($reason) ? " ($reason)" : "")
                ]);
            }

            AuditService::log('shop_cash', 'CLOSE_SHIFT', 'cash_registers', $registerId, null, [
                'register_number'      => $register['register_number'],
                'expected_cash'        => $expectedCash,
                'closing_cash_counted' => $countedCash,
                'cash_difference'      => $cashDiff
            ]);

            Database::commit();

            return [
                'register_id'          => $registerId,
                'register_number'      => $register['register_number'],
                'expected_cash'        => $expectedCash,
                'closing_cash_counted' => $countedCash,
                'cash_difference'      => $cashDiff
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Record a Petty Cash / Shop Expense Voucher.
     */
    public static function recordExpense(array $payload, int $userId): array {
        $shopId = (int)$payload['shop_id'];
        $amount = (float)$payload['amount'];
        $category = trim($payload['expense_category'] ?? '');
        if (empty($category)) {
            $category = 'Miscellaneous';
        }
        $paidTo = trim($payload['paid_to'] ?? 'Vendor / Service');
        $paymentMode = $payload['payment_mode'] ?? 'CASH';
        $billNumber = trim($payload['bill_number'] ?? '');
        $notes = trim($payload['notes'] ?? '');

        if ($amount <= 0) {
            throw new InvalidArgumentException("Expense amount must be greater than zero.");
        }
        if (empty($paidTo)) {
            throw new InvalidArgumentException("Please specify the recipient or vendor name.");
        }

        $activeReg = self::getActiveRegister($shopId);
        $registerId = $activeReg ? (int)$activeReg['id'] : null;

        Database::beginTransaction();
        try {
            $today = date('Ymd');
            $prefix = 'EXP-' . $today . '-';
            $lastExp = Database::fetchOne(
                "SELECT voucher_number FROM cash_expenses WHERE voucher_number LIKE ? ORDER BY id DESC LIMIT 1",
                [$prefix . '%']
            );
            $seq = 1;
            if ($lastExp && !empty($lastExp['voucher_number'])) {
                $parts = explode('-', $lastExp['voucher_number']);
                $seq = (int)end($parts) + 1;
            }
            $voucherNumber = sprintf('%s%04d', $prefix, $seq);

            Database::execute(
                "INSERT INTO cash_expenses (
                    shop_id, register_id, voucher_number, expense_category, amount,
                    paid_to, payment_mode, bill_number, approved_by, created_by, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $shopId,
                    $registerId,
                    $voucherNumber,
                    $category,
                    $amount,
                    $paidTo,
                    $paymentMode,
                    $billNumber,
                    $userId,
                    $userId,
                    $notes
                ]
            );
            $expenseId = (int)Database::lastInsertId();

            // Log Transaction in Ledger
            self::logTransaction([
                'shop_id'          => $shopId,
                'register_id'      => $registerId,
                'transaction_type' => 'EXPENSE',
                'flow_direction'   => 'OUT',
                'amount'           => $amount,
                'payment_mode'     => $paymentMode,
                'category'         => $category,
                'reference_id'     => $voucherNumber,
                'source_table'     => 'cash_expenses',
                'source_id'        => $expenseId,
                'handled_by'       => $userId,
                'description'      => "Petty Cash Expense: {$category} to {$paidTo}" . (!empty($notes) ? " ({$notes})" : "")
            ]);

            // If active register, refresh aggregates
            if ($activeReg) {
                self::getActiveRegister($shopId);
            }

            AuditService::log('shop_cash', 'CREATE_EXPENSE', 'cash_expenses', $expenseId, null, [
                'voucher_number' => $voucherNumber,
                'amount'         => $amount,
                'category'       => $category
            ]);

            Database::commit();

            return [
                'expense_id'     => $expenseId,
                'voucher_number' => $voucherNumber,
                'amount'         => $amount
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Record Cash Addition / Float Top-Up.
     */
    public static function recordCashIn(array $payload, int $userId): array {
        $shopId = (int)$payload['shop_id'];
        $amount = (float)$payload['amount'];
        $category = $payload['category'] ?? 'TOP_UP';
        $source = trim($payload['source'] ?? 'Owner / Treasury');
        $notes = trim($payload['notes'] ?? '');

        if ($amount <= 0) {
            throw new InvalidArgumentException("Cash In amount must be greater than zero.");
        }

        $activeReg = self::getActiveRegister($shopId);
        $registerId = $activeReg ? (int)$activeReg['id'] : null;

        Database::beginTransaction();
        try {
            $today = date('Ymd');
            $ref = 'CIN-' . $today . '-' . sprintf('%04d', rand(100, 9999));

            self::logTransaction([
                'shop_id'          => $shopId,
                'register_id'      => $registerId,
                'transaction_type' => 'CASH_IN',
                'flow_direction'   => 'IN',
                'amount'           => $amount,
                'payment_mode'     => 'CASH',
                'category'         => $category,
                'reference_id'     => $ref,
                'source_table'     => 'cash_registers',
                'source_id'        => $registerId,
                'handled_by'       => $userId,
                'description'      => "Cash Top-Up from {$source}" . (!empty($notes) ? " - {$notes}" : "")
            ]);

            if ($activeReg) {
                self::getActiveRegister($shopId);
            }

            AuditService::log('shop_cash', 'CASH_IN', 'cash_registers', $registerId ?? 0, null, [
                'amount' => $amount,
                'source' => $source
            ]);

            Database::commit();

            return ['amount' => $amount, 'reference' => $ref];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Record Bank Cash Deposit / Safe Drop.
     */
    public static function recordBankDeposit(array $payload, int $userId): array {
        $shopId = (int)$payload['shop_id'];
        $amount = (float)$payload['amount'];
        $bankName = trim($payload['bank_name'] ?? 'Bank Account');
        $accountNum = trim($payload['account_number'] ?? '');
        $slipNumber = trim($payload['slip_number'] ?? '');
        $depositDate = !empty($payload['deposit_date']) ? $payload['deposit_date'] : date('Y-m-d');
        $notes = trim($payload['notes'] ?? '');

        if ($amount <= 0) {
            throw new InvalidArgumentException("Deposit amount must be greater than zero.");
        }

        $activeReg = self::getActiveRegister($shopId);
        $registerId = $activeReg ? (int)$activeReg['id'] : null;

        Database::beginTransaction();
        try {
            $today = date('Ymd');
            $prefix = 'DEP-' . $today . '-';
            $lastDep = Database::fetchOne(
                "SELECT deposit_number FROM bank_deposits WHERE deposit_number LIKE ? ORDER BY id DESC LIMIT 1",
                [$prefix . '%']
            );
            $seq = 1;
            if ($lastDep && !empty($lastDep['deposit_number'])) {
                $parts = explode('-', $lastDep['deposit_number']);
                $seq = (int)end($parts) + 1;
            }
            $depositNumber = sprintf('%s%04d', $prefix, $seq);

            Database::execute(
                "INSERT INTO bank_deposits (
                    shop_id, register_id, deposit_number, bank_name, account_number,
                    amount, slip_number, deposit_date, deposited_by, verified_by, status, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'VERIFIED', ?, NOW())",
                [
                    $shopId,
                    $registerId,
                    $depositNumber,
                    $bankName,
                    $accountNum,
                    $amount,
                    $slipNumber,
                    $depositDate,
                    $userId,
                    $userId,
                    $notes
                ]
            );
            $depositId = (int)Database::lastInsertId();

            self::logTransaction([
                'shop_id'          => $shopId,
                'register_id'      => $registerId,
                'transaction_type' => 'BANK_DEPOSIT',
                'flow_direction'   => 'OUT',
                'amount'           => $amount,
                'payment_mode'     => 'CASH',
                'category'         => 'BANK_DEPOSIT',
                'reference_id'     => $depositNumber,
                'source_table'     => 'bank_deposits',
                'source_id'        => $depositId,
                'handled_by'       => $userId,
                'description'      => "Bank Cash Drop to {$bankName}" . (!empty($slipNumber) ? " (Slip: {$slipNumber})" : "")
            ]);

            if ($activeReg) {
                self::getActiveRegister($shopId);
            }

            AuditService::log('shop_cash', 'BANK_DEPOSIT', 'bank_deposits', $depositId, null, [
                'deposit_number' => $depositNumber,
                'bank_name'      => $bankName,
                'amount'         => $amount
            ]);

            Database::commit();

            return [
                'deposit_id'     => $depositId,
                'deposit_number' => $depositNumber,
                'amount'         => $amount
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Helper to insert a record in `cash_transactions`.
     */
    public static function logTransaction(array $data): int {
        $today = date('Ymd');
        $prefix = 'CTX-' . $today . '-';
        $lastCtx = Database::fetchOne(
            "SELECT transaction_number FROM cash_transactions WHERE transaction_number LIKE ? ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );
        $seq = 1;
        if ($lastCtx && !empty($lastCtx['transaction_number'])) {
            $parts = explode('-', $lastCtx['transaction_number']);
            $seq = (int)end($parts) + 1;
        }
        $txNumber = sprintf('%s%04d', $prefix, $seq);

        Database::execute(
            "INSERT INTO cash_transactions (
                shop_id, register_id, transaction_number, transaction_type, flow_direction,
                amount, payment_mode, category, reference_id, source_table, source_id,
                handled_by, description, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['shop_id'],
                $data['register_id'] ?? null,
                $txNumber,
                $data['transaction_type'],
                $data['flow_direction'],
                $data['amount'],
                $data['payment_mode'] ?? 'CASH',
                $data['category'] ?? 'GENERAL',
                $data['reference_id'] ?? null,
                $data['source_table'] ?? null,
                $data['source_id'] ?? null,
                $data['handled_by'],
                $data['description'] ?? null
            ]
        );

        return (int)Database::lastInsertId();
    }

    /**
     * Compute Grand KPI Analysis Summary for the Cash Portal.
     */
    public static function getCashFlowSummary(int $shopId, ?string $startDate = null, ?string $endDate = null): array {
        $today = date('Y-m-d');
        $start = $startDate ?: $today;
        $end = $endDate ?: $today;

        $activeReg = self::getActiveRegister($shopId);

        // Today's movements
        $todayStats = Database::fetchOne(
            "SELECT 
                -- Inflows
                COALESCE(SUM(CASE WHEN flow_direction = 'IN' AND payment_mode = 'CASH' AND transaction_type != 'OPENING_FLOAT' THEN amount ELSE 0 END), 0) as today_cash_inflow,
                COALESCE(SUM(CASE WHEN transaction_type = 'SALE_CASH' THEN amount ELSE 0 END), 0) as today_cash_sales,
                COALESCE(SUM(CASE WHEN transaction_type = 'SALE_UPI' THEN amount ELSE 0 END), 0) as today_upi_sales,
                COALESCE(SUM(CASE WHEN transaction_type = 'SALE_CARD' THEN amount ELSE 0 END), 0) as today_card_sales,
                
                -- Outflows
                COALESCE(SUM(CASE WHEN flow_direction = 'OUT' AND payment_mode = 'CASH' THEN amount ELSE 0 END), 0) as today_cash_outflow,
                COALESCE(SUM(CASE WHEN transaction_type = 'EXPENSE' AND payment_mode = 'CASH' THEN amount ELSE 0 END), 0) as today_expenses,
                COALESCE(SUM(CASE WHEN transaction_type = 'REFUND_CASH' THEN amount ELSE 0 END), 0) as today_cash_refunds,
                COALESCE(SUM(CASE WHEN transaction_type = 'BANK_DEPOSIT' THEN amount ELSE 0 END), 0) as today_bank_deposits
             FROM cash_transactions
             WHERE shop_id = ? AND DATE(created_at) BETWEEN ? AND ?",
            [$shopId, $start, $end]
        );

        $cashInflow = (float)($todayStats['today_cash_inflow'] ?? 0);
        $cashOutflow = (float)($todayStats['today_cash_outflow'] ?? 0);
        $netCashFlow = $cashInflow - $cashOutflow;

        // Current till cash
        $currentTillCash = $activeReg ? (float)$activeReg['expected_cash'] : 0.00;

        // All shifts count
        $shiftsCount = Database::fetchOne(
            "SELECT 
                COUNT(id) as total_shifts,
                SUM(CASE WHEN status = 'OPEN' THEN 1 ELSE 0 END) as open_shifts,
                SUM(CASE WHEN status = 'CLOSED' THEN 1 ELSE 0 END) as closed_shifts
             FROM cash_registers
             WHERE shop_id = ?",
            [$shopId]
        );

        return [
            'current_till_cash'    => $currentTillCash,
            'today_cash_inflow'    => $cashInflow,
            'today_cash_sales'     => (float)($todayStats['today_cash_sales'] ?? 0),
            'today_upi_sales'      => (float)($todayStats['today_upi_sales'] ?? 0),
            'today_card_sales'     => (float)($todayStats['today_card_sales'] ?? 0),
            'today_digital_total'  => (float)($todayStats['today_upi_sales'] ?? 0) + (float)($todayStats['today_card_sales'] ?? 0),
            'today_cash_outflow'   => $cashOutflow,
            'today_expenses'       => (float)($todayStats['today_expenses'] ?? 0),
            'today_cash_refunds'   => (float)($todayStats['today_cash_refunds'] ?? 0),
            'today_bank_deposits'  => (float)($todayStats['today_bank_deposits'] ?? 0),
            'net_cash_flow'        => $netCashFlow,
            'total_shifts_count'   => (int)($shiftsCount['total_shifts'] ?? 0),
            'open_shifts_count'    => (int)($shiftsCount['open_shifts'] ?? 0),
            'active_register'      => $activeReg,
        ];
    }

    /**
     * Get Filtered Transactions Ledger.
     */
    public static function getTransactionsLedger(int $shopId, array $filters = []): array {
        $where = ["ct.shop_id = ?"];
        $params = [$shopId];

        if (!empty($filters['type'])) {
            $where[] = "ct.transaction_type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['flow'])) {
            $where[] = "ct.flow_direction = ?";
            $params[] = $filters['flow'];
        }

        if (!empty($filters['payment_mode'])) {
            $where[] = "ct.payment_mode = ?";
            $params[] = $filters['payment_mode'];
        }

        if (!empty($filters['register_id'])) {
            $where[] = "ct.register_id = ?";
            $params[] = (int)$filters['register_id'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = "DATE(ct.created_at) >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = "DATE(ct.created_at) <= ?";
            $params[] = $filters['end_date'];
        }

        $whereClause = implode(" AND ", $where);
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 100;

        return Database::fetchAll(
            "SELECT ct.*, u.full_name as staff_name, cr.register_number
             FROM cash_transactions ct
             LEFT JOIN users u ON ct.handled_by = u.id
             LEFT JOIN cash_registers cr ON ct.register_id = cr.id
             WHERE {$whereClause}
             ORDER BY ct.id DESC LIMIT {$limit}",
            $params
        );
    }

    /**
     * Get Complete Shift Details for Thermal Shift Receipt.
     */
    public static function getRegisterShiftDetails(int $registerId): ?array {
        $register = Database::fetchOne(
            "SELECT cr.*, 
                    COALESCE(cr.cashier_name, u_open.full_name) as open_cashier_name, u_open.phone as open_cashier_phone,
                    u_close.full_name as close_cashier_name,
                    sh.name as shop_name, sh.code as shop_code, sh.address as shop_address, sh.phone as shop_phone, sh.gst_number as shop_gst
             FROM cash_registers cr
             LEFT JOIN users u_open ON cr.cashier_id = u_open.id
             LEFT JOIN users u_close ON cr.closed_by = u_close.id
             LEFT JOIN shops sh ON cr.shop_id = sh.id
             WHERE cr.id = ?",
            [$registerId]
        );

        if (!$register) return null;

        $transactions = Database::fetchAll(
            "SELECT ct.*, u.full_name as staff_name
             FROM cash_transactions ct
             LEFT JOIN users u ON ct.handled_by = u.id
             WHERE ct.register_id = ?
             ORDER BY ct.id ASC",
            [$registerId]
        );

        $expenses = Database::fetchAll(
            "SELECT ce.*, u.full_name as staff_name
             FROM cash_expenses ce
             LEFT JOIN users u ON ce.created_by = u.id
             WHERE ce.register_id = ?
             ORDER BY ce.id ASC",
            [$registerId]
        );

        $deposits = Database::fetchAll(
            "SELECT bd.*, u.full_name as staff_name
             FROM bank_deposits bd
             LEFT JOIN users u ON bd.deposited_by = u.id
             WHERE bd.register_id = ?
             ORDER BY bd.id ASC",
            [$registerId]
        );

        return [
            'register'      => $register,
            'transactions'  => $transactions,
            'expenses'      => $expenses,
            'deposits'      => $deposits,
        ];
    }

    /**
     * Get Single Expense Voucher Details for Printing.
     */
    public static function getExpenseDetails(int $expenseId): ?array {
        return Database::fetchOne(
            "SELECT ce.*, u.full_name as staff_name, app_u.full_name as approver_name,
                    sh.name as shop_name, sh.address as shop_address, sh.phone as shop_phone,
                    cr.register_number
             FROM cash_expenses ce
             LEFT JOIN users u ON ce.created_by = u.id
             LEFT JOIN users app_u ON ce.approved_by = app_u.id
             LEFT JOIN shops sh ON ce.shop_id = sh.id
             LEFT JOIN cash_registers cr ON ce.register_id = cr.id
             WHERE ce.id = ?",
            [$expenseId]
        );
    }
}
