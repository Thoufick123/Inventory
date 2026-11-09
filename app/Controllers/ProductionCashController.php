<?php

require_once BASE_PATH . '/app/Services/ProductionCashService.php';

class ProductionCashController
{
    public function index(): void
    {
        $units = function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : ProductionUnit::getAll(['is_active' => 1]);
        $unitId = $this->resolveUnitId($_GET['unit_id'] ?? null, $units, false);
        $selectedUnit = null;
        foreach ($units as $unit) {
            if ((int)$unit['id'] === $unitId) {
                $selectedUnit = $unit;
                break;
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

        $activeRegister = $unitId > 0 ? ProductionCashService::getActiveRegister($unitId) : null;
        $lastClosedRegister = $unitId > 0 ? ProductionCashService::getLastClosedRegister($unitId) : null;
        $summary = $unitId > 0 ? ProductionCashService::getSummary($unitId, $startDate, $endDate) : [];
        $filters = [
            'type' => trim((string)($_GET['type'] ?? '')),
            'flow' => trim((string)($_GET['flow'] ?? '')),
            'payment_mode' => trim((string)($_GET['payment_mode'] ?? '')),
            'start_date' => !empty($_GET['all_time']) ? null : $startDate,
            'end_date' => !empty($_GET['all_time']) ? null : $endDate,
            'limit' => 100,
        ];
        $ledger = $unitId > 0 ? ProductionCashService::getLedger($unitId, $filters) : [];
        $shifts = $unitId > 0 ? Database::fetchAll(
            "SELECT cr.*, COALESCE(cr.cashier_name, opener.full_name) AS cashier_name, closer.full_name AS closed_by_name
             FROM production_cash_registers cr
             LEFT JOIN users opener ON opener.id = cr.cashier_id
             LEFT JOIN users closer ON closer.id = cr.closed_by
             WHERE cr.production_unit_id = ? ORDER BY cr.id DESC LIMIT 50",
            [$unitId]
        ) : [];
        $expenses = $unitId > 0 ? Database::fetchAll(
            "SELECT e.*, u.full_name AS staff_name, cr.register_number
             FROM production_cash_expenses e
             LEFT JOIN users u ON u.id = e.created_by
             LEFT JOIN production_cash_registers cr ON cr.id = e.register_id
             WHERE e.production_unit_id = ? ORDER BY e.id DESC LIMIT 50",
            [$unitId]
        ) : [];
        $deposits = $unitId > 0 ? Database::fetchAll(
            "SELECT d.*, u.full_name AS staff_name, cr.register_number
             FROM production_cash_deposits d
             LEFT JOIN users u ON u.id = d.deposited_by
             LEFT JOIN production_cash_registers cr ON cr.id = d.register_id
             WHERE d.production_unit_id = ? ORDER BY d.id DESC LIMIT 50",
            [$unitId]
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
            'unitId' => $unitId,
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
        try {
            $unitId = $this->resolveUnitId($_POST['production_unit_id'] ?? null);
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
        }
        Response::redirect(url('production/cash?unit_id=' . (int)($_POST['production_unit_id'] ?? 0)));
    }

    public function closeRegister(): void
    {
        if (!$this->validateCsrf()) {
            return;
        }
        $unitId = 0;
        try {
            $unitId = $this->resolveUnitId($_POST['production_unit_id'] ?? null);
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
            $unitId = (int)($_POST['production_unit_id'] ?? 0);
        }
        Response::redirect(url('production/cash?unit_id=' . $unitId . '&tab=shifts'));
    }

    public function storeExpense(): void
    {
        $unitId = (int)($_POST['production_unit_id'] ?? 0);
        if (!$this->validateCsrf()) {
            return;
        }
        try {
            $unitId = $this->resolveUnitId($_POST['production_unit_id'] ?? null);
            $result = ProductionCashService::recordExpense($_POST + ['production_unit_id' => $unitId], (int)(Session::userId() ?? 0));
            Session::flash('success', "Expense voucher {$result['voucher_number']} for ₹" . number_format((float)$result['amount'], 2) . ' recorded.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('production/cash?unit_id=' . $unitId . '&tab=expenses'));
    }

    public function storeCashIn(): void
    {
        $unitId = (int)($_POST['production_unit_id'] ?? 0);
        if (!$this->validateCsrf()) {
            return;
        }
        try {
            $unitId = $this->resolveUnitId($_POST['production_unit_id'] ?? null);
            $result = ProductionCashService::recordCashIn($_POST + ['production_unit_id' => $unitId], (int)(Session::userId() ?? 0));
            Session::flash('success', 'Cash top-up of ₹' . number_format((float)$result['amount'], 2) . ' recorded.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('production/cash?unit_id=' . $unitId));
    }

    public function storeBankDeposit(): void
    {
        $unitId = (int)($_POST['production_unit_id'] ?? 0);
        if (!$this->validateCsrf()) {
            return;
        }
        try {
            $unitId = $this->resolveUnitId($_POST['production_unit_id'] ?? null);
            $result = ProductionCashService::recordBankDeposit($_POST + ['production_unit_id' => $unitId], (int)(Session::userId() ?? 0));
            Session::flash('success', "Deposit {$result['deposit_number']} for ₹" . number_format((float)$result['amount'], 2) . ' recorded.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('production/cash?unit_id=' . $unitId . '&tab=deposits'));
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

    private function resolveUnitId(mixed $candidate, ?array $units = null, bool $required = true): int
    {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($scopedUnitId !== null) {
            return $scopedUnitId;
        }
        $units ??= function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : ProductionUnit::getAll(['is_active' => 1]);
        $unitId = (int)$candidate;
        if ($unitId <= 0 && $units) {
            $unitId = (int)$units[0]['id'];
        }
        foreach ($units as $unit) {
            if ((int)$unit['id'] === $unitId) {
                return $unitId;
            }
        }
        if (!$required && !$units) {
            return 0;
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