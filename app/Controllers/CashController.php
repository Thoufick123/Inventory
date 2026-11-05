<?php
/**
 * Cash Controller — Retail Shop Cash & Cash Flow Management Portal
 * Manages daily cash registers, cashier shifts, float denominations, petty cash expenses,
 * bank cash drops, audit ledgers, and thermal shift reconciliation receipts.
 */

require_once BASE_PATH . '/app/Services/ShopService.php';
require_once BASE_PATH . '/app/Services/CashService.php';
require_once BASE_PATH . '/app/Services/AuditService.php';

class CashController {

    /**
     * Main Cash & Cash Flow Dashboard Portal.
     */
    public function index(): void {
        if (!hasPermission('shop_cash', 'view') && !hasPermission('shop', 'view')) {
            Response::redirectWithError(url('shop/dashboard'), 'Access Denied: You do not have permission to view Cash Flow.');
            return;
        }

        $shop = ShopService::getActiveShop();
        $shopId = (int)$shop['id'];

        $tab = $_GET['tab'] ?? 'overview'; // 'overview', 'ledger', 'expenses', 'deposits', 'shifts'
        $startDate = $_GET['start_date'] ?? date('Y-m-d');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $searchQuery = trim($_GET['q'] ?? '');

        // 1. Grand KPI Summary
        $summary = CashService::getCashFlowSummary($shopId, $startDate, $endDate);
        $activeRegister = CashService::getActiveRegister($shopId);
        $lastClosedRegister = CashService::getLastClosedRegister($shopId);

        // 2. Transaction Ledger with filters
        $ledgerFilters = [
            'type'         => $_GET['type'] ?? '',
            'flow'         => $_GET['flow'] ?? '',
            'payment_mode' => $_GET['payment_mode'] ?? '',
            'start_date'   => !empty($_GET['filter_date']) ? $_GET['filter_date'] : (empty($_GET['all_time']) ? $startDate : null),
            'end_date'     => !empty($_GET['filter_date']) ? $_GET['filter_date'] : (empty($_GET['all_time']) ? $endDate : null),
            'limit'        => 100
        ];
        $ledger = CashService::getTransactionsLedger($shopId, $ledgerFilters);

        // 3. Shift History List
        $shifts = Database::fetchAll(
            "SELECT cr.*, COALESCE(cr.cashier_name, u.full_name) as cashier_name, cu.full_name as closed_by_name
             FROM cash_registers cr
             LEFT JOIN users u ON cr.cashier_id = u.id
             LEFT JOIN users cu ON cr.closed_by = cu.id
             WHERE cr.shop_id = ?
             ORDER BY cr.id DESC LIMIT 50",
            [$shopId]
        );

        // 4. Expenses List
        $expenses = Database::fetchAll(
            "SELECT ce.*, u.full_name as staff_name, cr.register_number
             FROM cash_expenses ce
             LEFT JOIN users u ON ce.created_by = u.id
             LEFT JOIN cash_registers cr ON ce.register_id = cr.id
             WHERE ce.shop_id = ?
             ORDER BY ce.id DESC LIMIT 50",
            [$shopId]
        );

        // 5. Bank Deposits List
        $deposits = Database::fetchAll(
            "SELECT bd.*, u.full_name as staff_name, cr.register_number
             FROM bank_deposits bd
             LEFT JOIN users u ON bd.deposited_by = u.id
             LEFT JOIN cash_registers cr ON bd.register_id = cr.id
             WHERE bd.shop_id = ?
             ORDER BY bd.id DESC LIMIT 50",
            [$shopId]
        );

        // Current cashier name default
        $user = Session::user() ?? Session::get('user');
        $currentUserName = !empty($user['full_name']) ? $user['full_name'] : (!empty($user['username']) ? $user['username'] : 'Cashier');

        Response::view('shop.cash.index', [
            'pageTitle'          => 'Shop Cash & Cash Flow Register',
            'activeModule'       => 'shop',
            'activeMenu'         => 'shop-cash',
            'breadcrumb'         => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Cash & Cash Flow'],
            ],
            'shop'               => $shop,
            'tab'                => $tab,
            'summary'            => $summary,
            'activeRegister'     => $activeRegister,
            'lastClosedRegister' => $lastClosedRegister,
            'currentUserName'    => $currentUserName,
            'ledger'             => $ledger,
            'shifts'             => $shifts,
            'expenses'           => $expenses,
            'deposits'           => $deposits,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'searchQuery'        => $searchQuery,
            'filters'            => $ledgerFilters,
        ]);
    }

    /**
     * Start / Open a new Register Shift.
     */
    public function openRegister(): void {
        if (!hasPermission('shop_cash', 'create')) {
            Session::flash('error', 'Access Denied: You do not have permission to open register shift.');
            Response::redirect(url('shop/cash'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $sessionUserId = Session::userId() ?? 1;

        $cashierName = trim($_POST['cashier_name'] ?? '');
        $user = Session::user() ?? Session::get('user');
        if (empty($cashierName)) {
            $cashierName = !empty($user['full_name']) ? $user['full_name'] : 'Cashier';
        }

        $openingFloat = (float)($_POST['opening_balance'] ?? 0);
        $expectedFloat = (float)($_POST['opening_expected_float'] ?? 0);
        $discrepancyReason = trim($_POST['opening_discrepancy_reason'] ?? '');
        $denominations = $_POST['denominations'] ?? [];
        $notes = trim($_POST['notes'] ?? '');

        // Filter valid denomination counts
        $cleanDenoms = [];
        foreach ($denominations as $k => $v) {
            $count = (int)$v;
            if ($count > 0) {
                $cleanDenoms[$k] = $count;
            }
        }

        try {
            $res = CashService::openRegister(
                (int)$shop['id'], 
                $sessionUserId, 
                $openingFloat, 
                $cleanDenoms, 
                $notes, 
                $expectedFloat, 
                $discrepancyReason,
                $cashierName
            );
            $msg = "Cash Register Shift #{$res['register_number']} opened successfully by " . e($cashierName) . " with ₹" . number_format($openingFloat, 2) . " opening float.";
            if (abs($res['opening_difference'] ?? 0) > 0.01) {
                $msg .= " (Opening mismatch: " . ($res['opening_difference'] > 0 ? "+" : "") . "₹" . number_format($res['opening_difference'], 2) . " recorded)";
            }
            Session::flash('success', $msg);
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        Response::redirect(url('shop/cash'));
    }

    /**
     * Close Shift & End Cashier Register with Physical Tally.
     */
    public function closeRegister(): void {
        if (!hasPermission('shop_cash', 'close_shift')) {
            Session::flash('error', 'Access Denied: You do not have permission to close register shift.');
            Response::redirect(url('shop/cash'));
            return;
        }

        $userId = Session::userId() ?? 1;
        $registerId = (int)($_POST['register_id'] ?? 0);
        $countedCash = (float)($_POST['closing_cash_counted'] ?? 0);
        $denominations = $_POST['denominations'] ?? [];
        $reason = trim($_POST['discrepancy_reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $cleanDenoms = [];
        foreach ($denominations as $k => $v) {
            $count = (int)$v;
            if ($count > 0) {
                $cleanDenoms[$k] = $count;
            }
        }

        try {
            $res = CashService::closeRegister($registerId, $userId, $countedCash, $cleanDenoms, $reason, $notes);
            $diff = (float)$res['cash_difference'];
            $varMsg = abs($diff) < 0.01 ? "Perfect cash match!" : ($diff > 0 ? "Overage of +₹" . number_format($diff, 2) : "Shortage of -₹" . number_format(abs($diff), 2));
            Session::flash('success', "Shift #{$res['register_number']} closed. {$varMsg}");
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        Response::redirect(url('shop/cash'));
    }

    /**
     * Record Petty Cash Expense Voucher.
     */
    public function storeExpense(): void {
        if (!hasPermission('shop_cash', 'create')) {
            Session::flash('error', 'Access Denied: You do not have permission to record expenses.');
            Response::redirect(url('shop/cash?tab=expenses'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $userId = Session::userId() ?? 1;

        $payload = $_POST;
        $payload['shop_id'] = $shop['id'];

        try {
            $res = CashService::recordExpense($payload, $userId);
            Session::flash('success', "Petty Cash Voucher #{$res['voucher_number']} for ₹" . number_format($res['amount'], 2) . " recorded successfully.");
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        Response::redirect(url('shop/cash?tab=expenses'));
    }

    /**
     * Record Manual Cash Injection / Top-up.
     */
    public function storeCashIn(): void {
        if (!hasPermission('shop_cash', 'create')) {
            Session::flash('error', 'Access Denied: You do not have permission to record cash additions.');
            Response::redirect(url('shop/cash'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $userId = Session::userId() ?? 1;

        $payload = $_POST;
        $payload['shop_id'] = $shop['id'];

        try {
            $res = CashService::recordCashIn($payload, $userId);
            Session::flash('success', "Cash addition of ₹" . number_format($res['amount'], 2) . " recorded in drawer till.");
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        Response::redirect(url('shop/cash'));
    }

    /**
     * Record Bank Cash Drop / Deposit.
     */
    public function storeBankDeposit(): void {
        if (!hasPermission('shop_cash', 'create')) {
            Session::flash('error', 'Access Denied: You do not have permission to record bank deposits.');
            Response::redirect(url('shop/cash?tab=deposits'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $userId = Session::userId() ?? 1;

        $payload = $_POST;
        $payload['shop_id'] = $shop['id'];

        try {
            $res = CashService::recordBankDeposit($payload, $userId);
            Session::flash('success', "Bank Deposit #{$res['deposit_number']} of ₹" . number_format($res['amount'], 2) . " logged successfully.");
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        Response::redirect(url('shop/cash?tab=deposits'));
    }

    /**
     * Thermal Printable Shift Summary Receipt (80mm / 58mm).
     */
    public function shiftReceipt(int $id): void {
        if (!hasPermission('shop_cash', 'voucher') && !hasPermission('shop_cash', 'view') && !hasPermission('shop', 'view')) {
            die("Access Denied");
        }

        $details = CashService::getRegisterShiftDetails($id);
        if (!$details) {
            die("Shift register record not found.");
        }

        Response::viewOnly('shop.cash.shift_receipt', [
            'shift'        => $details['register'],
            'transactions' => $details['transactions'],
            'expenses'     => $details['expenses'],
            'deposits'     => $details['deposits'],
        ]);
    }

    /**
     * Printable Digital Cash Payment Voucher (CPV).
     */
    public function expenseVoucher(int $id): void {
        if (!hasPermission('shop_cash', 'voucher') && !hasPermission('shop_cash', 'view') && !hasPermission('shop', 'view')) {
            die("Access Denied");
        }

        $expense = CashService::getExpenseDetails($id);
        if (!$expense) {
            die("Expense record not found.");
        }

        Response::viewOnly('shop.cash.expense_voucher', [
            'expense' => $expense
        ]);
    }

    /**
     * Ajax API: Check live register status for POS & Cash terminal.
     */
    public function ajaxRegisterStatus(): void {
        $shop = ShopService::getActiveShop();
        $active = CashService::getActiveRegister((int)$shop['id']);

        Response::json([
            'success' => true,
            'is_open' => !empty($active),
            'data'    => $active
        ]);
    }
}
