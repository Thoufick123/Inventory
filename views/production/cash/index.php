<?php
$activeReg = $activeRegister ?? null;
$isOpen = !empty($activeReg);
$currentTab = $tab ?? 'ledger';
$lastClosed = $lastClosedRegister ?? null;
$defaultFloat = $lastClosed ? (float)($lastClosed['closing_cash_counted'] ?? $lastClosed['expected_cash'] ?? 0) : 0.0;
$lastDenominations = $lastClosed['resolved_denominations'] ?? [];
$unitId = (int)($unitId ?? 0);
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary border"><i class="bi bi-gear-wide-connected me-1"></i><?= e($selectedUnit['name'] ?? 'Production unit not selected') ?></span>
                <?php if ($isOpen): ?><span class="badge bg-success">Shift Open · <?= e($activeReg['register_number']) ?></span><?php else: ?><span class="badge bg-warning-subtle text-warning-emphasis">No Active Shift</span><?php endif; ?>
            </div>
            <h2 class="h3 fw-bold mb-1">Production Cash &amp; Cash Flow</h2>
            <p class="text-muted small mb-0">Production-unit cash shifts, operational expenses, top-ups, bank deposits, and reconciliation.</p>
        </div>
        <?php if ($unitId > 0): ?>
        <div class="d-flex flex-wrap gap-2">
            <?php if (!$isOpen && hasPermission('production_cash', 'create')): ?><button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#openProductionShift"><i class="bi bi-door-open me-1"></i>Open Shift</button><?php endif; ?>
            <?php if ($isOpen && hasPermission('production_cash', 'close_shift')): ?><button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#closeProductionShift"><i class="bi bi-lock me-1"></i>Close Shift</button><?php endif; ?>
            <?php if (hasPermission('production_cash', 'create')): ?>
                <div class="dropdown"><button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" type="button"><i class="bi bi-plus-circle me-1"></i>Cash Movement</button><ul class="dropdown-menu dropdown-menu-end"><li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#productionExpenseModal" type="button"><i class="bi bi-wallet2 me-2"></i>Record Expense</button></li><li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#productionCashInModal" type="button"><i class="bi bi-arrow-down-left-circle me-2"></i>Cash Top-Up</button></li><li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#productionDepositModal" type="button"><i class="bi bi-bank me-2"></i>Bank Deposit</button></li></ul></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (Session::has('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= e(Session::flash('success')) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if (Session::has('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= e(Session::flash('error')) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body"><form method="GET" action="<?= url('production/cash') ?>" class="row g-3 align-items-end">
            <?php if (currentProductionUnitId() === null): ?><div class="col-lg-4"><label class="form-label small fw-semibold">Production Unit</label><select name="unit_id" class="form-select" required><?php foreach ($units as $unit): ?><option value="<?= (int)$unit['id'] ?>" <?= (int)$unit['id'] === $unitId ? 'selected' : '' ?>><?= e($unit['name']) ?><?= !empty($unit['code']) ? ' (' . e($unit['code']) . ')' : '' ?></option><?php endforeach; ?></select></div><?php else: ?><input type="hidden" name="unit_id" value="<?= $unitId ?>"><?php endif; ?>
            <div class="col-sm-6 col-lg-3"><label class="form-label small fw-semibold">From</label><input class="form-control" type="date" name="start_date" value="<?= e($startDate) ?>"></div>
            <div class="col-sm-6 col-lg-3"><label class="form-label small fw-semibold">To</label><input class="form-control" type="date" name="end_date" value="<?= e($endDate) ?>"></div>
            <div class="col-lg-2"><button class="btn btn-secondary w-100" type="submit"><i class="bi bi-funnel me-1"></i>Apply</button></div>
        </form></div>
    </div>

    <?php if ($unitId <= 0): ?>
        <div class="alert alert-warning">Create a Production Unit before opening a production cash register.</div>
    <?php else: ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl"><div class="card border-0 shadow-sm border-start border-primary border-4 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">Live Till Cash</small><div class="fs-3 fw-bold text-primary">₹<?= number_format((float)($summary['current_till_cash'] ?? 0), 2) ?></div><small class="text-muted"><?= $isOpen ? 'Shift ' . e($activeReg['register_number']) : 'Till closed' ?></small></div></div></div>
        <div class="col-sm-6 col-xl"><div class="card border-0 shadow-sm border-start border-success border-4 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">Cash Inflow</small><div class="fs-3 fw-bold text-success">+₹<?= number_format((float)($summary['cash_inflow'] ?? 0), 2) ?></div><small class="text-muted">Top-ups and adjustments</small></div></div></div>
        <div class="col-sm-6 col-xl"><div class="card border-0 shadow-sm border-start border-danger border-4 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">Cash Outflow</small><div class="fs-3 fw-bold text-danger">-₹<?= number_format((float)($summary['cash_outflow'] ?? 0), 2) ?></div><small class="text-muted">Expenses and deposits</small></div></div></div>
        <div class="col-sm-6 col-xl"><div class="card border-0 shadow-sm border-start border-info border-4 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">Net Cash Flow</small><div class="fs-3 fw-bold text-info"><?= ((float)($summary['net_cash_flow'] ?? 0) >= 0 ? '+' : '-') ?>₹<?= number_format(abs((float)($summary['net_cash_flow'] ?? 0)), 2) ?></div><small class="text-muted">Selected date range</small></div></div></div>
        <div class="col-sm-6 col-xl"><div class="card border-0 shadow-sm border-start border-warning border-4 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">Bank Deposits</small><div class="fs-3 fw-bold text-warning-emphasis">₹<?= number_format((float)($summary['bank_deposits'] ?? 0), 2) ?></div><small class="text-muted"><?= (int)($summary['total_shifts'] ?? 0) ?> recorded shifts</small></div></div></div>
    </div>

    <?php if ($isOpen): ?>
    <div class="card border-0 shadow-sm border-top border-4 border-success mb-4"><div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3"><div><h5 class="mb-1 fw-bold">Active Shift <span class="font-monospace text-success"><?= e($activeReg['register_number']) ?></span></h5><small class="text-muted">Cashier: <?= e($activeReg['cashier_name'] ?? 'Staff') ?> · Opened <?= e(formatDateTime($activeReg['opening_time'])) ?></small></div><span class="badge bg-success">LIVE RECONCILIATION</span></div>
        <div class="row g-3 text-center"><div class="col-6 col-md"><small class="text-muted d-block">Opening Float</small><strong>₹<?= number_format((float)$activeReg['opening_balance'], 2) ?></strong></div><div class="col-6 col-md"><small class="text-muted d-block">Cash Top-Ups</small><strong class="text-success">+₹<?= number_format((float)$activeReg['cash_in_amount'], 2) ?></strong></div><div class="col-6 col-md"><small class="text-muted d-block">Cash Expenses</small><strong class="text-danger">-₹<?= number_format((float)$activeReg['cash_out_amount'], 2) ?></strong></div><div class="col-6 col-md"><small class="text-muted d-block">Bank Deposits</small><strong class="text-warning-emphasis">-₹<?= number_format((float)$activeReg['bank_deposit_amount'], 2) ?></strong></div><div class="col-12 col-md"><small class="text-muted d-block">Expected Till</small><strong class="fs-5 text-primary">₹<?= number_format((float)$activeReg['expected_cash'], 2) ?></strong></div></div>
    </div></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <ul class="nav nav-pills gap-1"><li class="nav-item"><a class="nav-link <?= $currentTab === 'ledger' ? 'active' : '' ?>" href="<?= url('production/cash?unit_id=' . $unitId . '&tab=ledger') ?>"><i class="bi bi-list-columns me-1"></i>Cash Flow Ledger (<?= count($ledger) ?>)</a></li><li class="nav-item"><a class="nav-link <?= $currentTab === 'expenses' ? 'active' : '' ?>" href="<?= url('production/cash?unit_id=' . $unitId . '&tab=expenses') ?>"><i class="bi bi-wallet2 me-1"></i>Expenses (<?= count($expenses) ?>)</a></li><li class="nav-item"><a class="nav-link <?= $currentTab === 'deposits' ? 'active' : '' ?>" href="<?= url('production/cash?unit_id=' . $unitId . '&tab=deposits') ?>"><i class="bi bi-bank me-1"></i>Bank Deposits (<?= count($deposits) ?>)</a></li><li class="nav-item"><a class="nav-link <?= $currentTab === 'shifts' ? 'active' : '' ?>" href="<?= url('production/cash?unit_id=' . $unitId . '&tab=shifts') ?>"><i class="bi bi-clock-history me-1"></i>Shift Registers (<?= count($shifts) ?>)</a></li></ul>
            <input id="productionCashSearch" type="search" class="form-control" style="max-width:280px" placeholder="Search visible records..." oninput="filterProductionCash(this.value)">
        </div>
        <div class="table-responsive">
        <?php if ($currentTab === 'ledger'): ?>
            <table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Transaction #</th><th>Date &amp; Time</th><th>Type / Category</th><th>Flow</th><th>Mode</th><th>Amount</th><th>Shift #</th><th>Description / Reference</th><th>Handled By</th></tr></thead><tbody class="production-cash-rows">
                <?php if (!$ledger): ?><tr><td colspan="9" class="text-center text-muted py-5">No production cash transactions in this date range.</td></tr><?php else: foreach ($ledger as $row): $isIn = $row['flow_direction'] === 'IN'; ?><tr data-search="<?= e(strtolower(implode(' ', [$row['transaction_number'], $row['transaction_type'], $row['category'], $row['reference_id'] ?? '', $row['description'] ?? '', $row['staff_name'] ?? '']))) ?>"><td class="ps-3 fw-semibold font-monospace"><?= e($row['transaction_number']) ?></td><td><?= e(formatDateTime($row['created_at'])) ?></td><td><span class="badge bg-light text-dark border"><?= e($row['category']) ?></span><small class="d-block text-muted"><?= e($row['transaction_type']) ?></small></td><td><span class="badge <?= $isIn ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>"><?= $isIn ? 'INFLOW' : 'OUTFLOW' ?></span></td><td><?= e($row['payment_mode']) ?></td><td class="fw-bold <?= $isIn ? 'text-success' : 'text-danger' ?>"><?= $isIn ? '+' : '-' ?>₹<?= number_format((float)$row['amount'], 2) ?></td><td class="font-monospace"><?= e($row['register_number'] ?? 'Direct') ?></td><td><?= e($row['description'] ?? '-') ?><?php if (!empty($row['reference_id'])): ?><small class="d-block text-muted">Ref: <?= e($row['reference_id']) ?></small><?php endif; ?></td><td><?= e($row['staff_name'] ?? 'Staff') ?></td></tr><?php endforeach; endif; ?>
            </tbody></table>
        <?php elseif ($currentTab === 'expenses'): ?>
            <table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Voucher #</th><th>Date</th><th>Category</th><th>Paid To</th><th>Mode</th><th>Bill / Ref</th><th>Amount</th><th>By</th><th></th></tr></thead><tbody class="production-cash-rows">
                <?php if (!$expenses): ?><tr><td colspan="9" class="text-center text-muted py-5">No production cash expenses recorded.</td></tr><?php else: foreach ($expenses as $expense): ?><tr data-search="<?= e(strtolower(implode(' ', [$expense['voucher_number'], $expense['expense_category'], $expense['paid_to'], $expense['bill_number'] ?? '', $expense['notes'] ?? '', $expense['staff_name'] ?? '']))) ?>"><td class="ps-3 fw-semibold font-monospace text-danger"><?= e($expense['voucher_number']) ?></td><td><?= e(formatDateTime($expense['created_at'])) ?></td><td><?= e($expense['expense_category']) ?></td><td><?= e($expense['paid_to']) ?></td><td><?= e($expense['payment_mode']) ?></td><td><?= e($expense['bill_number'] ?: '-') ?></td><td class="fw-bold text-danger">₹<?= number_format((float)$expense['amount'], 2) ?></td><td><?= e($expense['staff_name'] ?? 'Staff') ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= url('production/cash/expense-voucher/' . $expense['id'] . '?unit_id=' . $unitId) ?>" target="_blank" title="Print voucher"><i class="bi bi-printer"></i></a></td></tr><?php endforeach; endif; ?>
            </tbody></table>
        <?php elseif ($currentTab === 'deposits'): ?>
            <table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Deposit #</th><th>Date</th><th>Bank</th><th>Account</th><th>Slip #</th><th>Amount</th><th>By</th><th>Status</th><th>Notes</th></tr></thead><tbody class="production-cash-rows">
                <?php if (!$deposits): ?><tr><td colspan="9" class="text-center text-muted py-5">No production bank deposits recorded.</td></tr><?php else: foreach ($deposits as $deposit): ?><tr data-search="<?= e(strtolower(implode(' ', [$deposit['deposit_number'], $deposit['bank_name'], $deposit['account_number'] ?? '', $deposit['slip_number'] ?? '', $deposit['notes'] ?? '', $deposit['staff_name'] ?? '']))) ?>"><td class="ps-3 fw-semibold font-monospace"><?= e($deposit['deposit_number']) ?></td><td><?= e(formatDate($deposit['deposit_date'])) ?></td><td><?= e($deposit['bank_name']) ?></td><td><?= e($deposit['account_number'] ?: '-') ?></td><td><?= e($deposit['slip_number'] ?: '-') ?></td><td class="fw-bold text-info">₹<?= number_format((float)$deposit['amount'], 2) ?></td><td><?= e($deposit['staff_name'] ?? 'Staff') ?></td><td><span class="badge bg-success-subtle text-success"><?= e($deposit['status']) ?></span></td><td><?= e($deposit['notes'] ?: '-') ?></td></tr><?php endforeach; endif; ?>
            </tbody></table>
        <?php else: ?>
            <table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Shift #</th><th>Cashier</th><th>Opened</th><th>Opening Float</th><th>Cash In</th><th>Cash Out</th><th>Expected</th><th>Counted</th><th>Variance</th><th>Status</th><th></th></tr></thead><tbody class="production-cash-rows">
                <?php if (!$shifts): ?><tr><td colspan="11" class="text-center text-muted py-5">No production cash shifts found.</td></tr><?php else: foreach ($shifts as $shift): $difference = (float)$shift['cash_difference']; ?><tr data-search="<?= e(strtolower(implode(' ', [$shift['register_number'], $shift['cashier_name'] ?? '', $shift['status']]))) ?>"><td class="ps-3 fw-semibold font-monospace"><?= e($shift['register_number']) ?></td><td><?= e($shift['cashier_name'] ?? 'Staff') ?></td><td><?= e(formatDateTime($shift['opening_time'])) ?></td><td>₹<?= number_format((float)$shift['opening_balance'], 2) ?></td><td class="text-success">+₹<?= number_format((float)$shift['cash_in_amount'], 2) ?></td><td class="text-danger">-₹<?= number_format((float)$shift['cash_out_amount'] + (float)$shift['bank_deposit_amount'], 2) ?></td><td>₹<?= number_format((float)$shift['expected_cash'], 2) ?></td><td><?= $shift['status'] === 'OPEN' ? 'In progress' : '₹' . number_format((float)$shift['closing_cash_counted'], 2) ?></td><td><?= $shift['status'] === 'OPEN' ? '-' : (($difference >= 0 ? '+' : '-') . '₹' . number_format(abs($difference), 2)) ?></td><td><span class="badge <?= $shift['status'] === 'OPEN' ? 'bg-success' : 'bg-secondary' ?>"><?= e($shift['status']) ?></span></td><td><a class="btn btn-sm btn-outline-primary" href="<?= url('production/cash/shift-receipt/' . $shift['id'] . '?unit_id=' . $unitId) ?>" target="_blank" title="Print shift slip"><i class="bi bi-printer"></i></a></td></tr><?php endforeach; endif; ?>
            </tbody></table>
        <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($unitId > 0 && hasPermission('production_cash', 'create')): ?>
<div class="modal fade" id="productionExpenseModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="<?= url('production/cash/expense') ?>"><?= CSRF::field() ?><input type="hidden" name="production_unit_id" value="<?= $unitId ?>"><div class="modal-header"><h5 class="modal-title">Production Cash Expense Voucher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Category</label><input class="form-control" name="expense_category" list="productionCashExpenseCategories" required><datalist id="productionCashExpenseCategories"><option value="Maintenance & Repairs"><option value="Production Supplies"><option value="Transport"><option value="Utilities"><option value="Staff Welfare"><option value="Miscellaneous"></datalist></div><div class="row g-3 mb-3"><div class="col-6"><label class="form-label">Amount</label><input class="form-control" type="number" name="amount" step="0.01" min="0.01" required></div><div class="col-6"><label class="form-label">Payment Mode</label><select class="form-select" name="payment_mode"><option>CASH</option><option>UPI</option><option>BANK_TRANSFER</option><option>CARD</option><option>OTHER</option></select></div></div><div class="mb-3"><label class="form-label">Paid To</label><input class="form-control" name="paid_to" required></div><div class="mb-3"><label class="form-label">Bill / Reference</label><input class="form-control" name="bill_number"></div><div><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div></div><div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-warning" type="submit">Save Expense</button></div></form></div></div></div>

<div class="modal fade" id="productionCashInModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="<?= url('production/cash/cash-in') ?>"><?= CSRF::field() ?><input type="hidden" name="production_unit_id" value="<?= $unitId ?>"><div class="modal-header"><h5 class="modal-title">Production Cash Top-Up</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Amount</label><input class="form-control" type="number" name="amount" step="0.01" min="0.01" required></div><div class="mb-3"><label class="form-label">Source / Provided By</label><input class="form-control" name="source" value="Production Treasury" required></div><div><label class="form-label">Reason</label><input class="form-control" name="notes"></div></div><div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" type="submit">Record Top-Up</button></div></form></div></div></div>

<div class="modal fade" id="productionDepositModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="<?= url('production/cash/bank-deposit') ?>"><?= CSRF::field() ?><input type="hidden" name="production_unit_id" value="<?= $unitId ?>"><div class="modal-header"><h5 class="modal-title">Production Bank Deposit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Amount</label><input class="form-control" type="number" name="amount" step="0.01" min="0.01" required></div><div class="row g-3 mb-3"><div class="col-6"><label class="form-label">Bank Name</label><input class="form-control" name="bank_name" required></div><div class="col-6"><label class="form-label">Deposit Date</label><input class="form-control" type="date" name="deposit_date" value="<?= date('Y-m-d') ?>" required></div></div><div class="row g-3 mb-3"><div class="col-6"><label class="form-label">Account #</label><input class="form-control" name="account_number"></div><div class="col-6"><label class="form-label">Slip #</label><input class="form-control" name="slip_number"></div></div><div><label class="form-label">Notes</label><input class="form-control" name="notes"></div></div><div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-info text-white" type="submit">Record Deposit</button></div></form></div></div></div>
<?php endif; ?>

<?php if ($unitId > 0 && !$isOpen && hasPermission('production_cash', 'create')): ?>
<div class="modal fade" id="openProductionShift" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="POST" action="<?= url('production/cash/open') ?>"><?= CSRF::field() ?><input type="hidden" name="production_unit_id" value="<?= $unitId ?>"><input type="hidden" name="opening_expected_float" value="<?= number_format($defaultFloat, 2, '.', '') ?>"><div class="modal-header"><h5 class="modal-title">Open Production Cash Shift</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label">Cashier / Custodian</label><input class="form-control" name="cashier_name" value="<?= e($currentUserName) ?>" required></div><div class="col-md-6"><label class="form-label">Opening Float (₹)</label><input class="form-control" id="productionOpeningFloat" type="number" name="opening_balance" step="0.01" min="0" value="<?= number_format($defaultFloat, 2, '.', '') ?>" required></div></div><div class="small text-muted mb-2">Expected float from last closed shift: ₹<?= number_format($defaultFloat, 2) ?></div><div class="border rounded p-3 mb-3"><div class="fw-semibold mb-2">Optional denomination count</div><div class="row g-2"><?php foreach ([500, 200, 100, 50, 20, 10, 5, 1] as $note): ?><div class="col-6 col-md-3"><label class="form-label small">₹<?= $note ?></label><input class="form-control form-control-sm production-opening-denom" type="number" min="0" step="1" name="denominations[<?= $note ?>]" data-value="<?= $note ?>" value="<?= (int)($lastDenominations[$note] ?? 0) ?>"></div><?php endforeach; ?><div class="col-6 col-md-3"><label class="form-label small">Coins (₹1 units)</label><input class="form-control form-control-sm production-opening-denom" type="number" min="0" step="1" name="denominations[coins]" data-value="1" value="<?= (int)($lastDenominations['coins'] ?? 0) ?>"></div></div><div class="small mt-2" id="productionOpeningCount">Denomination total: ₹0.00</div></div><div class="mb-3"><label class="form-label">Opening discrepancy reason, if any</label><input class="form-control" name="opening_discrepancy_reason"></div><div><label class="form-label">Opening notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div></div><div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Open Shift</button></div></form></div></div></div>
<?php endif; ?>

<?php if ($unitId > 0 && $isOpen && hasPermission('production_cash', 'close_shift')): ?>
<div class="modal fade" id="closeProductionShift" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="POST" action="<?= url('production/cash/close') ?>"><?= CSRF::field() ?><input type="hidden" name="production_unit_id" value="<?= $unitId ?>"><input type="hidden" name="register_id" value="<?= (int)$activeReg['id'] ?>"><div class="modal-header"><h5 class="modal-title">Close &amp; Reconcile <?= e($activeReg['register_number']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-primary">Expected till cash: <strong>₹<?= number_format((float)$activeReg['expected_cash'], 2) ?></strong></div><div class="border rounded p-3 mb-3"><div class="fw-semibold mb-2">Physical denomination count</div><div class="row g-2"><?php foreach ([500, 200, 100, 50, 20, 10, 5, 1] as $note): ?><div class="col-6 col-md-3"><label class="form-label small">₹<?= $note ?></label><input class="form-control form-control-sm production-closing-denom" type="number" min="0" step="1" name="denominations[<?= $note ?>]" data-value="<?= $note ?>"></div><?php endforeach; ?><div class="col-6 col-md-3"><label class="form-label small">Coins (₹1 units)</label><input class="form-control form-control-sm production-closing-denom" type="number" min="0" step="1" name="denominations[coins]" data-value="1"></div></div><div class="small mt-2" id="productionClosingCount">Counted total: ₹0.00</div></div><div class="mb-3"><label class="form-label">Counted cash total (₹)</label><input class="form-control" id="productionClosingCash" type="number" name="closing_cash_counted" step="0.01" min="0" value="<?= number_format((float)$activeReg['expected_cash'], 2, '.', '') ?>" required></div><div class="mb-3"><label class="form-label">Variance explanation, if any</label><input class="form-control" name="discrepancy_reason"></div><div><label class="form-label">Closing notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div></div><div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" type="submit">Close Shift</button></div></form></div></div></div>
<?php endif; ?>

<script>
function filterProductionCash(query) {
    const term = String(query || '').toLowerCase().trim();
    document.querySelectorAll('.production-cash-rows tr[data-search]').forEach(row => {
        row.hidden = term !== '' && !row.dataset.search.includes(term);
    });
}
function calculateProductionDenominations(selector, outputId, totalInputId = null) {
    let total = 0;
    document.querySelectorAll(selector).forEach(input => {
        total += (parseInt(input.dataset.value, 10) || 0) * (parseInt(input.value, 10) || 0);
    });
    document.getElementById(outputId).textContent = `Counted total: ₹${total.toFixed(2)}`;
    if (totalInputId && total > 0) document.getElementById(totalInputId).value = total.toFixed(2);
    if (selector === '.production-opening-denom') updateProductionOpeningVariance();
    if (totalInputId) updateProductionClosingVariance();
}
function addProductionVarianceIndicator(reasonInput, id) {
    if (!reasonInput || document.getElementById(id)) return;
    const indicator = document.createElement('div');
    indicator.id = id;
    indicator.className = 'alert alert-success py-2 mb-2';
    indicator.textContent = 'Cash count matches expected amount.';
    reasonInput.closest('.mb-3')?.before(indicator);
}
function updateProductionOpeningVariance() {
    const openingInput = document.getElementById('productionOpeningFloat');
    const expectedInput = document.querySelector('[name="opening_expected_float"]');
    const reasonInput = document.querySelector('[name="opening_discrepancy_reason"]');
    if (!openingInput || !expectedInput || !reasonInput) return;
    addProductionVarianceIndicator(reasonInput, 'productionOpeningVariance');
    let denominationTotal = 0;
    document.querySelectorAll('.production-opening-denom').forEach(input => {
        denominationTotal += (parseInt(input.dataset.value, 10) || 0) * (parseInt(input.value, 10) || 0);
    });
    const expected = Number(expectedInput.value) || 0;
    const actual = denominationTotal > 0 ? denominationTotal : (Number(openingInput.value) || 0);
    const difference = actual - expected;
    const indicator = document.getElementById('productionOpeningVariance');
    if (expected <= 0) {
        indicator.className = 'alert alert-info py-2 mb-2';
        indicator.textContent = 'Initial shift: no previous closing float is available for comparison.';
        reasonInput.required = false;
    } else if (Math.abs(difference) < 0.01) {
        indicator.className = 'alert alert-success py-2 mb-2';
        indicator.textContent = 'Opening cash matches the previous closing float.';
        reasonInput.required = false;
    } else {
        indicator.className = 'alert alert-warning py-2 mb-2';
        indicator.textContent = `${difference > 0 ? 'Overage' : 'Shortage'}: ${difference > 0 ? '+' : '-'}₹${Math.abs(difference).toFixed(2)} against previous float.`;
        reasonInput.required = true;
    }
}
function updateProductionClosingVariance() {
    const countedInput = document.getElementById('productionClosingCash');
    const reasonInput = document.querySelector('[name="discrepancy_reason"]');
    if (!countedInput || !reasonInput) return;
    addProductionVarianceIndicator(reasonInput, 'productionClosingVariance');
    const expected = <?= json_encode((float)($activeReg['expected_cash'] ?? 0)) ?>;
    const difference = (Number(countedInput.value) || 0) - expected;
    const indicator = document.getElementById('productionClosingVariance');
    if (Math.abs(difference) < 0.01) {
        indicator.className = 'alert alert-success py-2 mb-2';
        indicator.textContent = 'Cash count matches the expected till balance.';
        reasonInput.required = false;
    } else {
        indicator.className = 'alert alert-warning py-2 mb-2';
        indicator.textContent = `${difference > 0 ? 'Overage' : 'Shortage'}: ${difference > 0 ? '+' : '-'}₹${Math.abs(difference).toFixed(2)}.`;
        reasonInput.required = true;
    }
}
document.querySelectorAll('.production-opening-denom').forEach(input => input.addEventListener('input', () => calculateProductionDenominations('.production-opening-denom', 'productionOpeningCount')));
document.querySelectorAll('.production-closing-denom').forEach(input => input.addEventListener('input', () => calculateProductionDenominations('.production-closing-denom', 'productionClosingCount', 'productionClosingCash')));
document.getElementById('productionOpeningFloat')?.addEventListener('input', updateProductionOpeningVariance);
document.getElementById('productionClosingCash')?.addEventListener('input', updateProductionClosingVariance);
updateProductionOpeningVariance();
updateProductionClosingVariance();
</script>