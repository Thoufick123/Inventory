<?php

require_once BASE_PATH . '/app/Services/ProductionCashService.php';
require_once BASE_PATH . '/app/Services/ConcernTransactionService.php';

class ProductionCashController
{
    public function index(): void
    {
        $concernId = ConcernTransactionService::activeConcernId();
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $units = function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : ProductionUnit::getAll(['is_active' => 1]);
        $hasConcernCashRecords = (bool)Database::fetchOne(
            "SELECT 1 FROM production_cash_transactions WHERE concern_id = ? AND production_unit_id IS NULL LIMIT 1",
            [$concernId]
        ) || (bool)Database::fetchOne(
            "SELECT 1 FROM production_cash_registers WHERE concern_id = ? AND production_unit_id IS NULL LIMIT 1",
            [$concernId]
        );

        $scopeAvailable = $scopedUnitId !== 0;
        $unitId = null;
        $isConcernScope = false;
        $selectedUnit = null;
        if ($scopeAvailable) {
            try {
                $resolved = $this->resolveUnitId($_GET['unit_id'] ?? null, $units, false);
                if ($resolved === null) {
                    $isConcernScope = true;
                    $unitId = null;
                    $concern = Database::fetchOne('SELECT id, name FROM concerns WHERE id = ?', [$concernId]);
                    $selectedUnit = [
                        'id' => 0,
                        'name' => ($concern['name'] ?? 'Concern') . ' (Concern Register)',
                        'code' => 'CONC-' . $concernId,
                    ];
                } else {
                    $unitId = $resolved;
                    foreach ($units as $unit) {
                        if ((int)$unit['id'] === $unitId) {
                            $selectedUnit = $unit;
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $scopeAvailable = false;
            }
        }

        $tab = trim((string)($_GET['tab'] ?? 'ledger'));
        if (!in_array($tab, ['ledger', 'expenses', 'deposits', 'shifts'], true)) {
            $tab = 'ledger';
        }
        $today = date('Y-m-d');
        $startDate = $this->validDate((string)($_GET['start_date'] ?? $today), $today);
        $endDate = $this->validDate((string)($_GET['end_date'] ?? $today), $today);
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $activeRegister = $scopeAvailable ? ProductionCashService::getActiveRegister($unitId) : null;
        $lastClosedRegister = $scopeAvailable ? ProductionCashService::getLastClosedRegister($unitId) : null;
        $summary = $scopeAvailable ? ProductionCashService::getSummary($unitId, $startDate, $endDate) : [];
        $filters = [
            'type' => trim((string)($_GET['type'] ?? '')),
            'flow' => trim((string)($_GET['flow'] ?? '')),
            'payment_mode' => trim((string)($_GET['payment_mode'] ?? '')),
            'start_date' => !empty($_GET['all_time']) ? null : $startDate,
            'end_date' => !empty($_GET['all_time']) ? null : $endDate,
            'limit' => 100,
        ];
        $ledger = $scopeAvailable ? ProductionCashService::getLedger($unitId, $filters) : [];

        $unitCondCr = $unitId !== null ? 'cr.production_unit_id = ?' : 'cr.production_unit_id IS NULL';
        $unitCondE = $unitId !== null ? 'e.production_unit_id = ?' : 'e.production_unit_id IS NULL';
        $unitCondD = $unitId !== null ? 'd.production_unit_id = ?' : 'd.production_unit_id IS NULL';
        $scopeQueryParams = $unitId !== null ? [$unitId, $concernId] : [$concernId];

        $shifts = $scopeAvailable ? Database::fetchAll(
            "SELECT cr.*, COALESCE(cr.cashier_name, opener.full_name) AS cashier_name, closer.full_name AS closed_by_name
             FROM production_cash_registers cr
             LEFT JOIN users opener ON opener.id = cr.cashier_id
             LEFT JOIN users closer ON closer.id = cr.closed_by
             WHERE {$unitCondCr} AND cr.concern_id = ? ORDER BY cr.id DESC LIMIT 50",
            $scopeQueryParams
        ) : [];
        $expenses = $scopeAvailable ? Database::fetchAll(
            "SELECT e.*, u.full_name AS staff_name, cr.register_number
             FROM production_cash_expenses e
             LEFT JOIN users u ON u.id = e.created_by
             LEFT JOIN production_cash_registers cr ON cr.id = e.register_id
             WHERE {$unitCondE} AND e.concern_id = ? ORDER BY e.id DESC LIMIT 50",
            $scopeQueryParams
        ) : [];
        $deposits = $scopeAvailable ? Database::fetchAll(
            "SELECT d.*, u.full_name AS staff_name, cr.register_number
             FROM production_cash_deposits d
             LEFT JOIN users u ON u.id = d.deposited_by
             LEFT JOIN production_cash_registers cr ON cr.id = d.register_id
             WHERE {$unitCondD} AND d.concern_id = ? ORDER BY d.id DESC LIMIT 50",
            $scopeQueryParams
        ) : [];
        $user = Session::user() ?? [];

        Response::view('production.cash.index', [
            'pageTitle' => 'Production Cash & Cash Flow',
            'activeModule' => 'production',
            'activeMenu' => 'production-cash',
            'breadcrumb' => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Cash & Cash Flow'],
            ],
            'units' => $units,
            'selectedUnit' => $selectedUnit,
            'unitId' => $unitId ?? 0,
            'unitParam' => $unitId !== null ? (string)$unitId : 'concern',
            'isConcernScope' => $isConcernScope,
            'scopeAvailable' => $scopeAvailable,
            'hasConcernCashRecords' => $hasConcernCashRecords,
            'tab' => $tab,
            'summary' => $summary,
            'activeRegister' => $activeRegister,
            'lastClosedRegister' => $lastClosedRegister,
            'currentUserName' => $user['full_name'] ?? $user['username'] ?? 'Staff',
            'ledger' => $ledger,
            'shifts' => $shifts,
            'expenses' => $expenses,
            'deposits' => $deposits,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => $filters,
        ]);
    }

    public function openRegister(): void
    {
        if (!$this->validateCsrf()) {
            return;
        }
        $unitParam = 'concern';
        try {
            $unitId = $this->resolveUnitId($_POST['production_unit_id'] ?? null);
            $unitParam = $unitId !== null ? (string)$unitId : 'concern';
            $userId = (int)(Session::userId() ?? 0);
            $denominations = $this->cleanDenominations($_POST['denominations'] ?? []);
            $result = ProductionCashService::openRegister(
                $unitId,
                $userId,
                (float)($_POST['opening_balance'] ?? 0),
                $denominations,
                (float)($_POST['opening_expected_float'] ?? 0),
                trim((string)($_POST['opening_discrepancy_reason'] ?? '')),
                trim((string)($_POST['cashier_name'] ?? '')),
                trim((string)($_POST['notes'] ?? ''))
            );
            Session::flash('success', "Shift {$result['register_number']} opened with ₹" . number_format((float)$result['opening_balance'], 2) . '.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $rawUnit = trim((string)($_POST['production_unit_id'] ?? ''));
            if ($rawUnit !== '' && $rawUnit !== '0') {
                $unitParam = $rawUnit;
            }
        }
        Response::redirect(url('production/cash?unit_id=' . urlencode($unitParam)));
    }

    public function closeRegister(): void
    {
        if (!$this->validateCsrf()) {
            return;
        }
        $unitParam = 'concern';
        try {
            $unitId = $this->resolveUnitId($_POST['production_unit_id'] ?? null);
            $unitParam = $unitId !== null ? (string)$unitId : 'concern';
            $result = ProductionCashService::closeRegister(
                $unitId,
                (int)($_POST['register_id'] ?? 0),
                (int)(Session::userId() ?? 0),
                (float)($_POST['closing_cash_counted'] ?? 0),
                $this->cleanDenominations($_POST['denominations'] ?? []),
                trim((string)($_POST['discrepancy_reason'] ?? '')),
                trim((string)($_POST['notes'] ?? ''))
            );
            $difference = (float)$result['cash_difference'];
            $variance = abs($difference) < 0.01 ? 'Exact cash match.' : ($difference > 0 ? 'Overage ₹' . number_format($difference, 2) : 'Shortage ₹' . number_format(abs($difference), 2));
            Session::flash('success', "Shift {$result['register_number']} closed. {$variance}");
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $rawUnit = trim((string)($_POST['production_unit_id'] ?? ''));
            if ($rawUnit !== '' && $rawUnit !== '0') {
                $unitParam = $rawUnit;
            }
        }
        Response::redirect(url('production/cash?unit_id=' . urlencode($unitParam) . '&tab=shifts'));
    }

    public function storeExpense(): void
    {
        if (!$this->validateCsrf()) {
            return;
        }
        $unitParam = 'concern';
        try {
            $unitId = $this->resolveUnitId($_POST['production_unit_id'] ?? null);
            $unitParam = $unitId !== null ? (string)$unitId : 'concern';
            $result = ProductionCashService::recordExpense(['production_unit_id' => $unitId] + $_POST, (int)(Session::userId() ?? 0));
            Session::flash('success', "Expense voucher {$result['voucher_number']} for ₹" . number_format((float)$result['amount'], 2) . ' recorded.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $rawUnit = trim((string)($_POST['production_unit_id'] ?? ''));
            if ($rawUnit !== '' && $rawUnit !== '0') {
                $unitParam = $rawUnit;
            }
        }
        Response::redirect(url('production/cash?unit_id=' . urlencode($unitParam) . '&tab=expenses'));
    }

    public function storeCashIn(): void
    {
        if (!$this->validateCsrf()) {
            return;
        }
        $unitParam = 'concern';
        try {
            $unitId = $this->resolveUnitId($_POST['production_unit_id'] ?? null);
            $unitParam = $unitId !== null ? (string)$unitId : 'concern';
            $result = ProductionCashService::recordCashIn(['production_unit_id' => $unitId] + $_POST, (int)(Session::userId() ?? 0));
            Session::flash('success', 'Cash top-up of ₹' . number_format((float)$result['amount'], 2) . ' recorded.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $rawUnit = trim((string)($_POST['production_unit_id'] ?? ''));
            if ($rawUnit !== '' && $rawUnit !== '0') {
                $unitParam = $rawUnit;
            }
        }
        Response::redirect(url('production/cash?unit_id=' . urlencode($unitParam)));
    }

    public function storeBankDeposit(): void
    {
        if (!$this->validateCsrf()) {
            return;
        }
        $unitParam = 'concern';
        try {
            $unitId = $this->resolveUnitId($_POST['production_unit_id'] ?? null);
            $unitParam = $unitId !== null ? (string)$unitId : 'concern';
            $result = ProductionCashService::recordBankDeposit(['production_unit_id' => $unitId] + $_POST, (int)(Session::userId() ?? 0));
            Session::flash('success', "Deposit {$result['deposit_number']} for ₹" . number_format((float)$result['amount'], 2) . ' recorded.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $rawUnit = trim((string)($_POST['production_unit_id'] ?? ''));
            if ($rawUnit !== '' && $rawUnit !== '0') {
                $unitParam = $rawUnit;
            }
        }
        Response::redirect(url('production/cash?unit_id=' . urlencode($unitParam) . '&tab=deposits'));
    }

    public function shiftReceipt(int $id): void
    {
        try {
            $unitId = $this->resolveUnitId($_GET['unit_id'] ?? null);
            $details = ProductionCashService::getShiftDetails($unitId, $id);
            if (!$details) {
                Response::error(404, 'Production cash shift not found.');
                return;
            }
            Response::viewOnly('production.cash.shift_receipt', [
                'shift' => $details['register'],
                'transactions' => $details['transactions'],
                'expenses' => $details['expenses'],
                'deposits' => $details['deposits'],
            ]);
        } catch (\Throwable $e) {
            Response::error(403, 'You cannot view this production cash shift.');
        }
    }

    public function expenseVoucher(int $id): void
    {
        try {
            $unitId = $this->resolveUnitId($_GET['unit_id'] ?? null);
            $expense = ProductionCashService::getExpenseDetails($unitId, $id);
            if (!$expense) {
                Response::error(404, 'Production cash expense not found.');
                return;
            }
            Response::viewOnly('production.cash.expense_voucher', ['expense' => $expense]);
        } catch (\Throwable $e) {
            Response::error(403, 'You cannot view this production cash expense.');
        }
    }

    private function resolveUnitId(mixed $candidate, ?array $units = null, bool $required = true): ?int
    {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($scopedUnitId === 0) {
            throw new InvalidArgumentException('No production unit is assigned in the active concern.');
        }
        if ($scopedUnitId !== null) {
            return $scopedUnitId;
        }

        $units ??= function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : ProductionUnit::getAll(['is_active' => 1]);
        $raw = is_string($candidate) ? trim($candidate) : $candidate;
        if ($raw === 'concern' || $raw === '0' || $raw === 0) {
            return null;
        }

        $unitId = (int)$raw;
        if ($unitId <= 0 && $units) {
            $unitId = (int)$units[0]['id'];
        }
        foreach ($units as $unit) {
            if ((int)$unit['id'] === $unitId) {
                return $unitId;
            }
        }
        if (!$units) {
            return null;
        }
        if (!$required) {
            return null;
        }
        throw new InvalidArgumentException('Select a production unit you are authorized to access.');
    }

    private function cleanDenominations(mixed $denominations): array
    {
        if (!is_array($denominations)) {
            return [];
        }
        $clean = [];
        foreach ($denominations as $value => $count) {
            $count = filter_var($count, FILTER_VALIDATE_INT);
            if ($count === false || $count < 0) {
                throw new InvalidArgumentException('Denomination counts must be non-negative whole numbers.');
            }
            if ($count > 0) {
                $clean[$value] = $count;
            }
        }
        return $clean;
    }

    private function validDate(string $date, string $fallback): string
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date ? $date : $fallback;
    }

    private function validateCsrf(): bool
    {
        if (CSRF::validateRequest()) {
            return true;
        }
        Session::flash('error', 'Invalid security token. Please try again.');
        Response::back();
        return false;
    }
}