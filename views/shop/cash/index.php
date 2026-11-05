<?php
// views/shop/cash/index.php — Shop Cash & Full Cash Flow Management Portal
$activeReg = $activeRegister ?? null;
$isOpen = !empty($activeReg);
$currTab = $tab ?? 'overview';
?>
<div class="container-fluid py-4">
    <!-- 1. Header & Quick Actions -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-1 rounded-pill fw-semibold">
                    <i class="bi bi-shop me-1"></i><?= e($shop['name']) ?>
                </span>
                <?php if ($isOpen): ?>
                    <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold">
                        <i class="bi bi-circle-fill me-1 small" style="font-size: 8px;"></i> Shift Active: <?= e($activeReg['register_number']) ?>
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold">
                        <i class="bi bi-exclamation-circle me-1"></i> No Active Shift (Till Closed)
                    </span>
                <?php endif; ?>
            </div>
            <h2 class="fw-bold mb-1 text-dark">Shop Cash &amp; Cash Flow Register</h2>
            <p class="text-muted small mb-0">Daily cash register till management, cashier shifts, petty cash expenses, bank deposits, and live cash flow audit.</p>
        </div>

        <div class="d-flex gap-2 flex-wrap align-items-center">
            <?php if (!$isOpen): ?>
                <?php if (hasPermission('shop_cash', 'create')): ?>
                    <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#openShiftModal">
                        <i class="bi bi-door-open me-1"></i> Open Cashier Shift
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <?php if (hasPermission('shop_cash', 'close_shift')): ?>
                    <button type="button" class="btn btn-outline-danger rounded-pill px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#closeShiftModal">
                        <i class="bi bi-lock me-1"></i> Close Shift / End Day
                    </button>
                <?php endif; ?>
                <?php if (hasPermission('shop_cash', 'create')): ?>
                    <button type="button" class="btn btn-warning rounded-pill px-3 shadow-sm fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                        <i class="bi bi-dash-circle me-1"></i> Record Expense
                    </button>
                    <button type="button" class="btn btn-success rounded-pill px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#cashInModal">
                        <i class="bi bi-plus-circle me-1"></i> Cash Top-Up
                    </button>
                    <button type="button" class="btn btn-info rounded-pill px-3 shadow-sm fw-bold text-white" data-bs-toggle="modal" data-bs-target="#bankDepositModal">
                        <i class="bi bi-bank me-1"></i> Bank Deposit
                    </button>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (hasPermission('pos', 'view') || hasPermission('pos', 'create')): ?>
                <a href="<?= url('shop/pos') ?>" class="btn btn-outline-primary rounded-pill px-3 shadow-sm fw-semibold">
                    <i class="bi bi-receipt me-1"></i> POS Billing
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (Session::has('success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= Session::flash('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (Session::has('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= Session::flash('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- 2. QUICK ANALYSIS CARDS (5 Interactive Cash Flow KPI Cards) -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Current Drawer Till Cash -->
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 rounded-4 bg-white transition-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Live Drawer Cash</span>
                            <h3 class="mb-0 mt-1 fw-bold text-primary">
                                <?= formatCurrency($summary['current_till_cash'] ?? 0) ?>
                            </h3>
                            <small class="text-secondary d-flex align-items-center mt-1" style="font-size: 11px;">
                                <?php if ($isOpen): ?>
                                    <span class="text-success fw-bold"><i class="bi bi-dot"></i> Till Open (Shift: <?= e($activeReg['register_number']) ?>)</span>
                                <?php else: ?>
                                    <span class="text-muted"><i class="bi bi-lock-fill me-1"></i> Till Closed</span>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-circle fs-3 flex-shrink-0">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Today's Cash Inflow -->
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100 rounded-4 bg-white transition-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Today's Cash Inflow</span>
                            <h3 class="mb-0 mt-1 fw-bold text-success">
                                +<?= formatCurrency($summary['today_cash_inflow'] ?? 0) ?>
                            </h3>
                            <small class="text-success d-flex align-items-center mt-1" style="font-size: 11px;">
                                <i class="bi bi-arrow-up-right me-1"></i> Sales: <?= formatCurrency($summary['today_cash_sales'] ?? 0) ?>
                            </small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success rounded-circle fs-3 flex-shrink-0">
                            <i class="bi bi-arrow-down-left-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Today's Cash Outflow -->
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100 rounded-4 bg-white transition-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Today's Cash Outflow</span>
                            <h3 class="mb-0 mt-1 fw-bold text-danger">
                                -<?= formatCurrency($summary['today_cash_outflow'] ?? 0) ?>
                            </h3>
                            <small class="text-danger d-flex align-items-center mt-1" style="font-size: 11px;">
                                <i class="bi bi-arrow-down-right me-1"></i> Exp: <?= formatCurrency($summary['today_expenses'] ?? 0) ?> • Ref: <?= formatCurrency($summary['today_cash_refunds'] ?? 0) ?>
                            </small>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger rounded-circle fs-3 flex-shrink-0">
                            <i class="bi bi-arrow-up-right-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Net Cash Flow -->
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100 rounded-4 bg-white transition-card" style="border-color: #0284c7 !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Net Cash Position</span>
                            <h3 class="mb-0 mt-1 fw-bold" style="color: <?= ($summary['net_cash_flow'] ?? 0) >= 0 ? '#0284c7' : '#dc2626' ?>;">
                                <?= ($summary['net_cash_flow'] ?? 0) >= 0 ? '+' : '' ?><?= formatCurrency($summary['net_cash_flow'] ?? 0) ?>
                            </h3>
                            <small class="text-muted d-flex align-items-center mt-1" style="font-size: 11px;">
                                Bank Deposits: <?= formatCurrency($summary['today_bank_deposits'] ?? 0) ?>
                            </small>
                        </div>
                        <div class="stat-icon rounded-circle fs-3 flex-shrink-0" style="background-color: rgba(2, 132, 199, 0.1); color: #0284c7;">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 5: Digital Collections (UPI & Cards) -->
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100 rounded-4 bg-white transition-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-warning-emphasis small fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">
                                <i class="bi bi-qr-code-scan me-1"></i>Digital Channels
                            </span>
                            <h3 class="mb-0 mt-1 fw-bold text-warning-emphasis">
                                <?= formatCurrency($summary['today_digital_total'] ?? 0) ?>
                            </h3>
                            <small class="text-muted d-flex align-items-center mt-1" style="font-size: 11px;">
                                UPI: <strong class="text-dark ms-1"><?= formatCurrency($summary['today_upi_sales'] ?? 0) ?></strong> • Card: <strong class="text-dark ms-1"><?= formatCurrency($summary['today_card_sales'] ?? 0) ?></strong>
                            </small>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning-emphasis rounded-circle fs-3 flex-shrink-0">
                            <i class="bi bi-credit-card-2-front"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Active Shift Live Summary Banner -->
    <?php if ($isOpen): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-top border-4 border-success">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-4 fs-2">
                            <i class="bi bi-calculator"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="fw-bold text-dark mb-0">Active Shift: <span class="font-monospace text-success"><?= e($activeReg['register_number']) ?></span></h5>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-bold">LIVE RECONCILIATION</span>
                            </div>
                            <small class="text-muted">
                                Cashier: <strong class="text-dark"><?= e($activeReg['cashier_name']) ?></strong> • 
                                Opened at: <strong><?= formatDateTime($activeReg['opening_time']) ?></strong>
                            </small>
                        </div>
                    </div>
                    <?php if (hasPermission('shop_cash', 'close_shift')): ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#closeShiftModal">
                            <i class="bi bi-lock me-1"></i> Close &amp; Reconcile Shift
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Live Mathematical Equation Bar -->
                <div class="bg-light bg-opacity-75 rounded-4 p-3 border">
                    <div class="row g-2 text-center align-items-center">
                        <div class="col-6 col-md">
                            <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 10px;">Opening Float</small>
                            <span class="fs-6 fw-bold text-dark">₹<?= number_format((float)$activeReg['opening_balance'], 2) ?></span>
                        </div>
                        <div class="col-1 col-md-auto text-muted fw-bold fs-5">+</div>
                        <div class="col-6 col-md">
                            <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 10px;">Cash Sales</small>
                            <span class="fs-6 fw-bold text-success">+₹<?= number_format((float)$activeReg['cash_sales_amount'], 2) ?></span>
                        </div>
                        <div class="col-1 col-md-auto text-muted fw-bold fs-5">+</div>
                        <div class="col-6 col-md">
                            <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 10px;">Cash Top-Up</small>
                            <span class="fs-6 fw-bold text-primary">+₹<?= number_format((float)$activeReg['cash_in_amount'], 2) ?></span>
                        </div>
                        <div class="col-1 col-md-auto text-muted fw-bold fs-5">-</div>
                        <div class="col-6 col-md">
                            <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 10px;">Petty Cash Exp</small>
                            <span class="fs-6 fw-bold text-danger">-₹<?= number_format((float)$activeReg['cash_out_amount'], 2) ?></span>
                        </div>
                        <div class="col-1 col-md-auto text-muted fw-bold fs-5">-</div>
                        <div class="col-6 col-md">
                            <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 10px;">Cash Refunds</small>
                            <span class="fs-6 fw-bold text-danger">-₹<?= number_format((float)$activeReg['cash_refund_amount'], 2) ?></span>
                        </div>
                        <div class="col-1 col-md-auto text-muted fw-bold fs-5">-</div>
                        <div class="col-6 col-md">
                            <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 10px;">Bank Drops</small>
                            <span class="fs-6 fw-bold text-info">-₹<?= number_format((float)$activeReg['bank_deposit_amount'], 2) ?></span>
                        </div>
                        <div class="col-1 col-md-auto text-muted fw-bold fs-5">=</div>
                        <div class="col-12 col-md-auto bg-white px-3 py-2 rounded-3 border border-success shadow-sm">
                            <small class="text-success text-uppercase fw-bold d-block" style="font-size: 10px;">Expected Drawer Till</small>
                            <span class="fs-5 fw-bold text-success">₹<?= number_format((float)$activeReg['expected_cash'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4. Unified Navigation Tabs & Tables -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
            <!-- Navigation Tabs -->
            <ul class="nav nav-pills card-header-pills" id="cashModuleTabs">
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-3 <?= $currTab === 'overview' || $currTab === 'ledger' ? 'active' : '' ?>" href="<?= url('shop/cash?tab=ledger') ?>">
                        <i class="bi bi-list-columns me-1"></i> Cash Flow Ledger (<?= count($ledger) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-3 <?= $currTab === 'expenses' ? 'active' : '' ?>" href="<?= url('shop/cash?tab=expenses') ?>">
                        <i class="bi bi-wallet2 me-1"></i> Petty Cash Expenses (<?= count($expenses) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-3 <?= $currTab === 'deposits' ? 'active' : '' ?>" href="<?= url('shop/cash?tab=deposits') ?>">
                        <i class="bi bi-bank me-1"></i> Bank Deposits (<?= count($deposits) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-3 <?= $currTab === 'shifts' ? 'active' : '' ?>" href="<?= url('shop/cash?tab=shifts') ?>">
                        <i class="bi bi-clock-history me-1"></i> Shift Registers (<?= count($shifts) ?>)
                    </a>
                </li>
            </ul>

            <!-- Live Search Bar -->
            <div class="input-group input-group-sm" style="min-width: 240px; max-width: 320px;">
                <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="cashTabSearchInput" class="form-control bg-light border-start-0 rounded-end-pill font-monospace" placeholder="Search records..." oninput="onCashSearchChange(this.value)">
            </div>
        </div>

        <div class="card-body p-0">
            <!-- TAB 1: CASH FLOW LEDGER -->
            <?php if ($currTab === 'overview' || $currTab === 'ledger'): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="cashLedgerTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Tx #</th>
                                <th>Date &amp; Time</th>
                                <th>Type / Category</th>
                                <th>Flow</th>
                                <th>Mode</th>
                                <th>Amount</th>
                                <th>Shift #</th>
                                <th>Description / Ref</th>
                                <th class="pe-4">Handled By</th>
                            </tr>
                        </thead>
                        <tbody class="cash-table-body">
                            <?php if (empty($ledger)): ?>
                                <tr id="emptyLedgerRow">
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        No cash transactions recorded for the selected period.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ledger as $tx): ?>
                                    <?php 
                                    $isIncome = ($tx['flow_direction'] === 'IN');
                                    $searchData = strtolower($tx['transaction_number'] . ' ' . $tx['transaction_type'] . ' ' . $tx['category'] . ' ' . ($tx['reference_id'] ?? '') . ' ' . ($tx['description'] ?? '') . ' ' . ($tx['staff_name'] ?? '') . ' ' . ($tx['register_number'] ?? ''));
                                    ?>
                                    <tr class="cash-row" data-search="<?= e($searchData) ?>">
                                        <td class="ps-4 fw-bold font-monospace text-dark small"><?= e($tx['transaction_number']) ?></td>
                                        <td><small class="text-muted"><?= formatDateTime($tx['created_at']) ?></small></td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= e($tx['category']) ?></span>
                                            <small class="text-muted d-block" style="font-size: 11px;"><?= e($tx['transaction_type']) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($isIncome): ?>
                                                <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill"><i class="bi bi-arrow-down-left me-1"></i>INFLOW</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded-pill"><i class="bi bi-arrow-up-right me-1"></i>OUTFLOW</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= e($tx['payment_mode']) ?></span></td>
                                        <td class="fw-bold <?= $isIncome ? 'text-success' : 'text-danger' ?>">
                                            <?= $isIncome ? '+' : '-' ?>₹<?= number_format((float)$tx['amount'], 2) ?>
                                        </td>
                                        <td><small class="font-monospace text-primary"><?= e($tx['register_number'] ?? 'Direct') ?></small></td>
                                        <td>
                                            <div class="text-dark small"><?= e($tx['description'] ?? '-') ?></div>
                                            <?php if (!empty($tx['reference_id'])): ?>
                                                <small class="text-muted font-monospace">Ref: <?= e($tx['reference_id']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4"><small class="text-dark fw-semibold"><?= e($tx['staff_name'] ?? 'Admin') ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <!-- TAB 2: PETTY CASH EXPENSES -->
            <?php elseif ($currTab === 'expenses'): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="expensesTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Voucher #</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Paid To</th>
                                <th>Payment Mode</th>
                                <th>Bill / Ref #</th>
                                <th>Amount</th>
                                <th class="pe-4">Recorded By</th>
                            </tr>
                        </thead>
                        <tbody class="cash-table-body">
                            <?php if (empty($expenses)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-wallet2 fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        No petty cash expense vouchers recorded yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($expenses as $exp): ?>
                                    <?php $searchData = strtolower($exp['voucher_number'] . ' ' . $exp['expense_category'] . ' ' . $exp['paid_to'] . ' ' . ($exp['bill_number'] ?? '') . ' ' . ($exp['notes'] ?? '') . ' ' . ($exp['staff_name'] ?? '')); ?>
                                    <tr class="cash-row" data-search="<?= e($searchData) ?>">
                                        <td class="ps-4 fw-bold font-monospace text-danger"><?= e($exp['voucher_number']) ?></td>
                                        <td><small class="text-muted"><?= formatDateTime($exp['created_at']) ?></small></td>
                                        <td><span class="badge bg-warning-subtle text-warning-emphasis px-2 py-1 rounded-pill"><?= e($exp['expense_category']) ?></span></td>
                                        <td class="fw-semibold text-dark"><?= e($exp['paid_to']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= e($exp['payment_mode']) ?></span></td>
                                        <td><small class="font-monospace text-muted"><?= e($exp['bill_number'] ?: '-') ?></small></td>
                                        <td class="fw-bold text-danger">₹<?= number_format((float)$exp['amount'], 2) ?></td>
                                        <td class="pe-4"><small class="text-dark"><?= e($exp['staff_name'] ?? 'Admin') ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <!-- TAB 3: BANK DEPOSITS -->
            <?php elseif ($currTab === 'deposits'): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="depositsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Deposit #</th>
                                <th>Deposit Date</th>
                                <th>Bank Name</th>
                                <th>Account #</th>
                                <th>Slip #</th>
                                <th>Amount</th>
                                <th>Deposited By</th>
                                <th>Status</th>
                                <th class="pe-4">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="cash-table-body">
                            <?php if (empty($deposits)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-bank fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        No bank cash drops recorded yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deposits as $dep): ?>
                                    <?php $searchData = strtolower($dep['deposit_number'] . ' ' . $dep['bank_name'] . ' ' . ($dep['account_number'] ?? '') . ' ' . ($dep['slip_number'] ?? '') . ' ' . ($dep['notes'] ?? '') . ' ' . ($dep['staff_name'] ?? '')); ?>
                                    <tr class="cash-row" data-search="<?= e($searchData) ?>">
                                        <td class="ps-4 fw-bold font-monospace text-info"><?= e($dep['deposit_number']) ?></td>
                                        <td><small class="text-muted"><?= formatDate($dep['deposit_date']) ?></small></td>
                                        <td class="fw-bold text-dark"><?= e($dep['bank_name']) ?></td>
                                        <td><small class="font-monospace text-muted"><?= e($dep['account_number'] ?: '-') ?></small></td>
                                        <td><span class="badge bg-light text-dark border font-monospace"><?= e($dep['slip_number'] ?: '-') ?></span></td>
                                        <td class="fw-bold text-primary">₹<?= number_format((float)$dep['amount'], 2) ?></td>
                                        <td><small class="text-dark"><?= e($dep['staff_name'] ?? 'Admin') ?></small></td>
                                        <td><span class="badge bg-success-subtle text-success rounded-pill px-2 py-1"><i class="bi bi-check-circle me-1"></i>VERIFIED</span></td>
                                        <td class="pe-4 small text-muted"><?= e($dep['notes'] ?: '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <!-- TAB 4: SHIFT REGISTERS AUDIT -->
            <?php elseif ($currTab === 'shifts'): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="shiftsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Shift #</th>
                                <th>Opened At</th>
                                <th>Opening Cash</th>
                                <th>Cash Sales</th>
                                <th>Expenses</th>
                                <th>Expected Till</th>
                                <th>Physical Counted</th>
                                <th>Variance</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="cash-table-body">
                            <?php if (empty($shifts)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="bi bi-clock-history fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        No shift records found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($shifts as $sft): ?>
                                    <?php 
                                    $isShiftOpen = ($sft['status'] === 'OPEN');
                                    $diff = (float)$sft['cash_difference'];
                                    $searchData = strtolower($sft['register_number'] . ' ' . ($sft['cashier_name'] ?? '') . ' ' . ($sft['closed_by_name'] ?? '') . ' ' . $sft['status']);
                                    ?>
                                    <tr class="cash-row" data-search="<?= e($searchData) ?>">
                                        <td class="ps-4 fw-bold font-monospace text-dark"><?= e($sft['register_number']) ?></td>
                                        <td>
                                            <div class="small fw-semibold"><?= formatDateTime($sft['opening_time']) ?></div>
                                            <small class="text-muted">By: <?= e($sft['cashier_name'] ?? 'Admin') ?></small>
                                        </td>
                                        <td class="fw-semibold">
                                            <div>₹<?= number_format((float)$sft['opening_balance'], 2) ?></div>
                                            <?php if (!empty($sft['opening_difference']) && abs((float)$sft['opening_difference']) > 0.01): ?>
                                                <small class="<?= (float)$sft['opening_difference'] > 0 ? 'text-primary' : 'text-danger' ?> d-block fw-normal" style="font-size: 11px;" title="Mismatch Reason: <?= e($sft['opening_discrepancy_reason'] ?? 'N/A') ?>">
                                                    <?= (float)$sft['opening_difference'] > 0 ? '+' : '' ?>₹<?= number_format((float)$sft['opening_difference'], 2) ?> (Mismatch)
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-success fw-bold">+₹<?= number_format((float)$sft['cash_sales_amount'], 2) ?></td>
                                        <td class="text-danger fw-bold">-₹<?= number_format((float)$sft['cash_out_amount'], 2) ?></td>
                                        <td class="fw-bold text-primary">₹<?= number_format((float)$sft['expected_cash'], 2) ?></td>
                                        <td>
                                            <?php if ($isShiftOpen): ?>
                                                <span class="text-muted small">In Progress...</span>
                                            <?php else: ?>
                                                <span class="fw-bold text-dark">₹<?= number_format((float)$sft['closing_cash_counted'], 2) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isShiftOpen): ?>
                                                <span class="badge bg-light text-muted">-</span>
                                            <?php elseif (abs($diff) < 0.01): ?>
                                                <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1"><i class="bi bi-check2-circle me-1"></i>Exact Match</span>
                                            <?php elseif ($diff > 0): ?>
                                                <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1">+₹<?= number_format($diff, 2) ?> Overage</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1">-₹<?= number_format(abs($diff), 2) ?> Shortage</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isShiftOpen): ?>
                                                <span class="badge bg-success rounded-pill px-3 py-1">OPEN</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary rounded-pill px-3 py-1">CLOSED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="<?= url('shop/cash/shift-receipt/' . $sft['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="bi bi-printer me-1"></i> Thermal Slip
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODALS SECTION                                                 -->
<!-- ============================================================== -->

<!-- 1. OPEN SHIFT MODAL -->
<?php if (hasPermission('shop_cash', 'create')): ?>
<?php 
$lastClosed = $lastClosedRegister ?? null;
$defaultFloat = 0.00;
$lastDenoms = [];
if ($lastClosed) {
    $defaultFloat = (float)($lastClosed['closing_cash_counted'] ?? $lastClosed['expected_cash'] ?? 0);
    $lastDenoms = $lastClosed['resolved_denominations'] ?? [];
    if (empty($lastDenoms) && !empty($lastClosed['closing_denominations'])) {
        $decoded = is_string($lastClosed['closing_denominations']) ? json_decode($lastClosed['closing_denominations'], true) : $lastClosed['closing_denominations'];
        if (is_array($decoded)) $lastDenoms = $decoded;
    }
    if (empty($lastDenoms) && !empty($lastClosed['opening_denominations'])) {
        $decoded = is_string($lastClosed['opening_denominations']) ? json_decode($lastClosed['opening_denominations'], true) : $lastClosed['opening_denominations'];
        if (is_array($decoded)) $lastDenoms = $decoded;
    }
    if (empty($lastDenoms) && $defaultFloat > 0) {
        $lastDenoms = CashService::breakIntoDenominations($defaultFloat);
    }
}
$curUser = $currentUserId ?? 1;
?>
<div class="modal fade" id="openShiftModal" tabindex="-1" aria-labelledby="openShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form method="POST" action="<?= url('shop/cash/open') ?>">
                <input type="hidden" name="opening_expected_float" id="openingExpectedFloatInput" value="<?= number_format($defaultFloat, 2, '.', '') ?>">

                <div class="modal-header bg-light border-bottom py-3 px-4 rounded-top-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-circle p-2 fs-5">
                            <i class="bi bi-door-open"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="openShiftModalLabel">Open Cashier Shift Register</h5>
                            <small class="text-muted">Count and verify starting cash float before opening till drawer.</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Non-editable System Expected Opening Float Reference -->
                    <?php if ($lastClosed): ?>
                        <div class="card border border-primary-subtle bg-primary bg-opacity-10 rounded-4 p-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-white p-2.5 text-primary shadow-xs">
                                        <i class="bi bi-clock-history fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="text-uppercase fw-bold text-muted small" style="font-size: 11px; letter-spacing: 0.5px;">
                                            Expected Opening Float (From Last Closed Shift)
                                        </div>
                                        <div class="d-flex align-items-baseline gap-2">
                                            <h3 class="fw-bold text-primary mb-0">₹<span id="expectedOpeningFloatDisplay"><?= number_format($defaultFloat, 2) ?></span></h3>
                                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 small font-monospace"><?= e($lastClosed['register_number']) ?></span>
                                        </div>
                                        <small class="text-muted">
                                            Closed: <?= !empty($lastClosed['closing_time']) ? date('d M Y, h:i A', strtotime($lastClosed['closing_time'])) : 'N/A' ?> 
                                            • Last Cashier: <?= e($lastClosed['closed_by_name'] ?? $lastClosed['cashier_name'] ?? 'Staff') ?>
                                        </small>
                                    </div>
                                </div>
                                <div>
                                    <span class="badge bg-white text-dark border px-3 py-2 rounded-pill fw-semibold shadow-xs">
                                        <i class="bi bi-lock-fill text-muted me-1"></i> System Reference (Not Editable)
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border rounded-4 p-3 mb-4 d-flex align-items-center gap-3">
                            <i class="bi bi-info-circle fs-3 text-primary"></i>
                            <div>
                                <div class="fw-bold text-dark small">Initial Register Shift Setup</div>
                                <small class="text-muted">No previous closed shift found. Specify initial starting cash float.</small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Row 1: Cashier Name & Opening Remarks -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><i class="bi bi-person-badge me-1 text-primary"></i>Cashier Name <span class="text-danger">*</span></label>
                            <input type="text" name="cashier_name" class="form-control form-control-lg rounded-3 fw-semibold" placeholder="e.g. Saran / Rajesh" value="<?= e($currentUserName ?? 'Cashier') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-journal-text me-1 text-secondary"></i>Opening Notes / Shift Remarks</label>
                            <input type="text" name="notes" class="form-control form-control-lg rounded-3" placeholder="e.g. Morning Shift - Counter 1" value="<?= $lastClosed ? 'Opening float carried from shift ' . e($lastClosed['register_number']) : '' ?>">
                        </div>
                    </div>

                    <!-- Row 2: Actual Counted Cash Float & Live Match / Mismatch Status -->
                    <div class="card border rounded-4 p-3 mb-4 bg-white shadow-xs">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1"><i class="bi bi-cash-stack me-1 text-success"></i>Total Starting Cash Float (₹) <span class="text-danger">*</span></label>
                                <small class="text-muted d-block mb-2">Unmodified closing balance from previous shift.</small>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light fw-bold">₹</span>
                                    <input type="number" step="0.01" min="0" name="opening_balance" id="openFloatInput" class="form-control fw-bold fs-4 text-primary bg-light bg-opacity-75" placeholder="0.00" value="<?= number_format($defaultFloat, 2, '.', '') ?>" readonly tabindex="-1" style="pointer-events: none; background-color: #f8f9fa !important;" required>
                                    <span class="input-group-text bg-light text-muted small" title="Fixed unmodified starting float amount"><i class="bi bi-lock-fill me-1"></i>Unmodified</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold mb-1">Float Match Status &amp; Variance</label>
                                <small class="text-muted d-block mb-2">Denomination count vs starting float</small>
                                <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between" id="openVarianceBox">
                                    <span class="fw-bold" id="openVarianceDisplayText">
                                        <i class="bi bi-check-circle-fill text-success me-1"></i>₹0.00 (Exact Match)
                                    </span>
                                    <span class="badge bg-success-subtle text-success fw-bold px-2.5 py-1.5" id="openVarianceBadge">Matched</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Mismatch Warning & Reason (Conditional) -->
                    <div id="openDiscrepancyReasonDiv" class="mb-4" style="display: none;">
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 rounded-4 p-3 mb-0">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                                <span class="fw-bold text-danger">Starting Float Mismatch Detected!</span>
                            </div>
                            <p class="small text-muted mb-2">The counted physical denominations differ from the starting float amount (<strong id="openDiscrepancyAmountText" class="text-danger">₹0.00</strong>). Please provide the official reason for audit trail:</p>
                            <div>
                                <label class="form-label fw-bold small text-dark">Mismatch Explanation / Reason <span class="text-danger">*</span></label>
                                <input type="text" name="opening_discrepancy_reason" id="openDiscrepancyReasonInput" class="form-control form-control-lg rounded-3" placeholder="e.g. Manager withdrew ₹380 for morning change replenishment / recount adjustment">
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Starting Denominations Count (Optional Grid) -->
                    <div class="card border rounded-4 p-3 bg-light bg-opacity-50">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark small"><i class="bi bi-calculator me-1 text-success"></i>Starting Denominations Count (Optional)</span>
                            <span class="badge bg-secondary-subtle text-secondary small" id="openDenomTotalBadge">Denom Total: ₹0.00</span>
                        </div>
                        <div class="row g-2">
                            <?php 
                            $denomList = [500, 200, 100, 50, 20, 10, 5];
                            foreach ($denomList as $denom): 
                                $cnt = isset($lastDenoms[$denom]) ? (int)$lastDenoms[$denom] : 0;
                            ?>
                                <div class="col-6 col-sm-4 col-md-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white fw-bold" style="min-width: 60px;">₹<?= $denom ?></span>
                                        <input type="number" min="0" name="denominations[<?= $denom ?>]" class="form-control open-denom-count text-center fw-bold" placeholder="0" value="<?= $cnt > 0 ? $cnt : '0' ?>" data-value="<?= $denom ?>" oninput="calculateOpenDenom()">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php 
                            $coinCnt = isset($lastDenoms['coins']) ? (int)$lastDenoms['coins'] : (isset($lastDenoms['coin']) ? (int)$lastDenoms['coin'] : 0);
                            ?>
                            <div class="col-6 col-sm-4 col-md-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white fw-bold" style="min-width: 60px;">Coins</span>
                                    <input type="number" min="0" name="denominations[coins]" class="form-control open-denom-count text-center fw-bold" placeholder="0" value="<?= $coinCnt > 0 ? $coinCnt : '0' ?>" data-value="1" oninput="calculateOpenDenom()">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Start Shift Register
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 2. CLOSE SHIFT MODAL -->
<?php if ($isOpen && hasPermission('shop_cash', 'close_shift')): ?>
<div class="modal fade" id="closeShiftModal" tabindex="-1" aria-labelledby="closeShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form method="POST" action="<?= url('shop/cash/close') ?>">
                <input type="hidden" name="register_id" value="<?= $activeReg['id'] ?>">

                <div class="modal-header bg-light border-bottom py-3 px-4 rounded-top-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger rounded-circle p-2 fs-5">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="closeShiftModalLabel">End Shift &amp; Reconcile Drawer Till</h5>
                            <small class="text-muted">Shift: <strong class="text-primary font-monospace"><?= e($activeReg['register_number']) ?></strong></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Expected Cash Display -->
                    <div class="alert alert-primary bg-primary bg-opacity-10 border-0 rounded-4 p-3 mb-4 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-uppercase fw-bold text-muted d-block" style="font-size: 11px;">Expected Cash in Drawer Till</small>
                            <h3 class="fw-bold text-primary mb-0">₹<span id="expectedCashVal"><?= number_format((float)$activeReg['expected_cash'], 2) ?></span></h3>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block" style="font-size: 11px;">Sales: +₹<?= number_format((float)$activeReg['cash_sales_amount'], 2) ?> • Exp: -₹<?= number_format((float)$activeReg['cash_out_amount'], 2) ?></small>
                            <small class="text-muted">Refunds: -₹<?= number_format((float)$activeReg['cash_refund_amount'], 2) ?> • Drops: -₹<?= number_format((float)$activeReg['bank_deposit_amount'], 2) ?></small>
                        </div>
                    </div>

                    <!-- Denomination Physical Counter -->
                    <div class="card border rounded-4 p-3 mb-3 bg-light bg-opacity-50">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark small"><i class="bi bi-calculator me-1 text-primary"></i>Physical Cash Denomination Count</span>
                            <span class="badge bg-primary text-white" id="closeDenomCountBadge">Counted Total: ₹0.00</span>
                        </div>
                        <div class="row g-2">
                            <?php foreach ([500, 200, 100, 50, 20, 10, 5] as $denom): ?>
                                <div class="col-6 col-sm-4 col-md-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white fw-bold" style="min-width: 60px;">₹<?= $denom ?></span>
                                        <input type="number" min="0" name="denominations[<?= $denom ?>]" class="form-control close-denom-count text-center fw-bold" placeholder="0" data-value="<?= $denom ?>" oninput="calculateCloseDenom()">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="col-6 col-sm-4 col-md-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white fw-bold" style="min-width: 60px;">Coins</span>
                                    <input type="number" min="0" name="denominations[coins]" class="form-control close-denom-count text-center fw-bold" placeholder="0" data-value="1" oninput="calculateCloseDenom()">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Physical Count Total & Variance -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Actual Total Physical Cash Counted (₹)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light fw-bold">₹</span>
                                <input type="number" step="0.01" min="0" name="closing_cash_counted" id="closeCountedInput" class="form-control fw-bold fs-4" placeholder="0.00" value="<?= number_format((float)$activeReg['expected_cash'], 2, '.', '') ?>" required oninput="calculateVariance()">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cash Discrepancy / Variance</label>
                            <div class="p-3 bg-light rounded-3 border text-center" id="varianceDisplayBox">
                                <span class="fs-5 fw-bold text-success" id="varianceDisplayText"><i class="bi bi-check-circle me-1"></i>₹0.00 (Exact Match)</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="discrepancyReasonDiv" style="display: none;">
                        <label class="form-label fw-bold text-danger">Reason for Overage / Shortage Discrepancy *</label>
                        <input type="text" name="discrepancy_reason" id="discrepancyReasonInput" class="form-control" placeholder="e.g. Customer change rounding error / shortage verified">
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">End of Shift Closing Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Register handed over to evening cashier">
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <i class="bi bi-lock-fill me-1"></i> Confirm &amp; Close Shift
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 3. ADD PETTY CASH EXPENSE MODAL -->
<?php if (hasPermission('shop_cash', 'create')): ?>
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form method="POST" action="<?= url('shop/cash/expense') ?>">
                <div class="modal-header bg-light border-bottom py-3 px-4 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark mb-0" id="addExpenseModalLabel">
                        <i class="bi bi-wallet2 text-warning me-2"></i>Record Petty Cash Expense Voucher
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Expense Category</label>
                        <input type="text" name="expense_category" class="form-control" placeholder="e.g. Tea & Refreshments / Cleaning / Store Maintenance / Miscellaneous" list="expenseCategorySuggestions" required>
                        <datalist id="expenseCategorySuggestions">
                            <option value="Tea & Refreshments">
                            <option value="Cleaning & Housekeeping">
                            <option value="Store Maintenance & Repairs">
                            <option value="Courier & Customer Shipping">
                            <option value="Carry Bags & Packaging">
                            <option value="Staff Welfare / Lunch">
                            <option value="Electricity / Internet / Water">
                            <option value="Travel / Local Conveyance">
                            <option value="Printing & POS Rolls">
                            <option value="Miscellaneous General Expense">
                        </datalist>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Expense Amount (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">₹</span>
                                <input type="number" step="0.01" min="1" name="amount" class="form-control fw-bold" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Mode</label>
                            <select name="payment_mode" class="form-select">
                                <option value="CASH" selected>Cash (From Till)</option>
                                <option value="UPI">UPI Transfer</option>
                                <option value="CARD">Card / Online</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Paid To (Vendor / Person Name)</label>
                        <input type="text" name="paid_to" class="form-control" placeholder="e.g. Local Tea Stall / Cleaner / Courier Partner" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bill / Receipt / Slip # (Optional)</label>
                        <input type="text" name="bill_number" class="form-control" placeholder="e.g. BILL-4521">
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Notes / Purpose Description</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Tea & snacks for customer & staff">
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">
                        <i class="bi bi-check2-circle me-1"></i> Save Expense Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. CASH TOP-UP / CASH IN MODAL -->
<div class="modal fade" id="cashInModal" tabindex="-1" aria-labelledby="cashInModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form method="POST" action="<?= url('shop/cash/cash-in') ?>">
                <div class="modal-header bg-light border-bottom py-3 px-4 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark mb-0" id="cashInModalLabel">
                        <i class="bi bi-plus-circle text-success me-2"></i>Cash Injection / Float Top-Up
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Top-Up Amount (₹)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light fw-bold">₹</span>
                            <input type="number" step="0.01" min="1" name="amount" class="form-control fw-bold text-success fs-4" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Source / Provided By</label>
                        <input type="text" name="source" class="form-control" placeholder="e.g. Store Owner / Main Safe Vault" value="Store Owner" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Reason / Description</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Added change float for busy weekend">
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Add Cash to Drawer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 5. BANK DEPOSIT MODAL -->
<div class="modal fade" id="bankDepositModal" tabindex="-1" aria-labelledby="bankDepositModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form method="POST" action="<?= url('shop/cash/bank-deposit') ?>">
                <div class="modal-header bg-light border-bottom py-3 px-4 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark mb-0" id="bankDepositModalLabel">
                        <i class="bi bi-bank text-info me-2"></i>Bank Cash Drop / Safe Transfer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deposit Amount (₹)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light fw-bold">₹</span>
                            <input type="number" step="0.01" min="1" name="amount" class="form-control fw-bold text-info fs-4" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" placeholder="e.g. HDFC Bank / SBI" value="Primary Bank" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Deposit Date</label>
                            <input type="date" name="deposit_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Account / CDM Slip # (Optional)</label>
                        <input type="text" name="slip_number" class="form-control" placeholder="e.g. CDM-895123">
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Remarks</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Day cash deposit dropped at CDM machine">
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info rounded-pill px-4 fw-bold text-white">
                        <i class="bi bi-check2-circle me-1"></i> Record Bank Drop
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Denomination calculator for Opening Shift
function calculateOpenDenom() {
    let total = 0;
    let hasCount = false;
    document.querySelectorAll('.open-denom-count').forEach(inp => {
        const val = parseFloat(inp.getAttribute('data-value')) || 0;
        const count = parseInt(inp.value) || 0;
        if (count > 0) hasCount = true;
        total += val * count;
    });

    const badge = document.getElementById('openDenomTotalBadge');
    if (badge) {
        badge.innerText = `Denom Total: ₹${total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        if (total > 0) {
            badge.className = 'badge bg-primary text-white small';
        } else {
            badge.className = 'badge bg-secondary-subtle text-secondary small';
        }
    }

    // Crucial: The starting cash float (#openFloatInput) remains strictly UNMODIFIED and is never changed by count.
    calculateOpenVariance(total, hasCount);
}

// Opening Float Variance / Mismatch Calculator
function calculateOpenVariance(denomTotal = null, hasCount = null) {
    const expected = parseFloat(document.getElementById('openingExpectedFloatInput')?.value) 
                  || parseFloat(document.getElementById('openFloatInput')?.value) 
                  || 0;

    if (denomTotal === null) {
        denomTotal = 0;
        hasCount = false;
        document.querySelectorAll('.open-denom-count').forEach(inp => {
            const val = parseFloat(inp.getAttribute('data-value')) || 0;
            const count = parseInt(inp.value) || 0;
            if (count > 0) hasCount = true;
            denomTotal += val * count;
        });
    }

    const diff = denomTotal - expected;

    const textEl = document.getElementById('openVarianceDisplayText');
    const badgeEl = document.getElementById('openVarianceBadge');
    const reasonDiv = document.getElementById('openDiscrepancyReasonDiv');
    const reasonInput = document.getElementById('openDiscrepancyReasonInput');
    const amountText = document.getElementById('openDiscrepancyAmountText');

    if (Math.abs(diff) < 0.01) {
        if (textEl) {
            textEl.className = 'fw-bold text-success';
            textEl.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i>₹0.00 (Exact Match)';
        }
        if (badgeEl) {
            badgeEl.className = 'badge bg-success-subtle text-success fw-bold px-2.5 py-1.5';
            badgeEl.innerText = 'Matched';
        }
        if (reasonDiv) reasonDiv.style.display = 'none';
        if (reasonInput) reasonInput.required = false;
    } else if (diff > 0) {
        if (textEl) {
            textEl.className = 'fw-bold text-primary';
            textEl.innerHTML = `<i class="bi bi-plus-circle-fill text-primary me-1"></i>+₹${diff.toFixed(2)} (Overage)`;
        }
        if (badgeEl) {
            badgeEl.className = 'badge bg-primary-subtle text-primary fw-bold px-2.5 py-1.5';
            badgeEl.innerText = `+₹${diff.toFixed(2)} Overage`;
        }
        if (amountText) amountText.innerText = `+₹${diff.toFixed(2)} overage`;
        if (reasonDiv) reasonDiv.style.display = 'block';
        if (reasonInput) reasonInput.required = true;
    } else {
        if (textEl) {
            textEl.className = 'fw-bold text-danger';
            textEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>-₹${Math.abs(diff).toFixed(2)} (Shortage)`;
        }
        if (badgeEl) {
            badgeEl.className = 'badge bg-danger-subtle text-danger fw-bold px-2.5 py-1.5';
            badgeEl.innerText = `-₹${Math.abs(diff).toFixed(2)} Shortage`;
        }
        if (amountText) amountText.innerText = `-₹${Math.abs(diff).toFixed(2)} shortage`;
        if (reasonDiv) reasonDiv.style.display = 'block';
        if (reasonInput) reasonInput.required = true;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    calculateOpenDenom();
    const openModal = document.getElementById('openShiftModal');
    if (openModal) {
        openModal.addEventListener('show.bs.modal', function () {
            calculateOpenDenom();
        });
    }
});

// Denomination calculator for Closing Shift
function calculateCloseDenom() {
    let total = 0;
    document.querySelectorAll('.close-denom-count').forEach(inp => {
        const val = parseFloat(inp.getAttribute('data-value')) || 0;
        const count = parseInt(inp.value) || 0;
        total += val * count;
    });

    const badge = document.getElementById('closeDenomCountBadge');
    if (badge) badge.innerText = `Counted Total: ₹${total.toFixed(2)}`;

    const countedInput = document.getElementById('closeCountedInput');
    if (countedInput) {
        countedInput.value = total.toFixed(2);
        calculateVariance();
    }
}

// Variance calculation
function calculateVariance() {
    const expected = parseFloat(document.getElementById('expectedCashVal')?.innerText.replace(/,/g, '')) || 0;
    const counted = parseFloat(document.getElementById('closeCountedInput')?.value) || 0;
    const diff = counted - expected;

    const textEl = document.getElementById('varianceDisplayText');
    const reasonDiv = document.getElementById('discrepancyReasonDiv');
    const reasonInput = document.getElementById('discrepancyReasonInput');

    if (Math.abs(diff) < 0.01) {
        textEl.className = 'fs-5 fw-bold text-success';
        textEl.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>₹0.00 (Exact Match)';
        if (reasonDiv) reasonDiv.style.display = 'none';
        if (reasonInput) reasonInput.required = false;
    } else if (diff > 0) {
        textEl.className = 'fs-5 fw-bold text-primary';
        textEl.innerHTML = `<i class="bi bi-plus-circle-fill me-1"></i>+₹${diff.toFixed(2)} (Overage)`;
        if (reasonDiv) reasonDiv.style.display = 'block';
        if (reasonInput) reasonInput.required = true;
    } else {
        textEl.className = 'fs-5 fw-bold text-danger';
        textEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>-₹${Math.abs(diff).toFixed(2)} (Shortage)`;
        if (reasonDiv) reasonDiv.style.display = 'block';
        if (reasonInput) reasonInput.required = true;
    }
}

// Live search for current table tab
function onCashSearchChange(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.cash-table-body .cash-row');
    const tableBody = document.querySelector('.cash-table-body');
    let visibleCount = 0;

    rows.forEach(row => {
        const search = (row.getAttribute('data-search') || '').toLowerCase();
        if (!q || search.includes(q)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    let emptyRow = document.getElementById('noCashMatchRow');
    if (visibleCount === 0 && rows.length > 0) {
        if (!emptyRow && tableBody) {
            emptyRow = document.createElement('tr');
            emptyRow.id = 'noCashMatchRow';
            emptyRow.innerHTML = `
                <td colspan="10" class="text-center py-5 text-muted">
                    <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    <h6 class="fw-bold text-secondary">No Matching Cash Records</h6>
                    <p class="small text-muted mb-0">No records match "${query}".</p>
                </td>
            `;
            tableBody.appendChild(emptyRow);
        }
    } else if (emptyRow) {
        emptyRow.remove();
    }
}
</script>

<style>
.transition-card {
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.transition-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.08) !important;
}
.stat-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
