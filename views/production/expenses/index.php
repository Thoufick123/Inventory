<?php
// Production Expenses & Assets Management View (Unit 1 & Unit 2)
?>
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-wallet2 text-primary me-2"></i>Production Expenses &amp; Assets Management
            </h4>
            <span class="text-muted small">Maintain machinery assets, sewing consumables (threads, zips, needles), machine repairs, and costs for Production Unit 1 &amp; Unit 2</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('production/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-speedometer2 me-1"></i>Production Dashboard
            </a>
            <?php if (hasPermission('production_expenses', 'create')): ?>
            <button type="button" class="btn btn-primary btn-sm fw-semibold shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#recordExpenseModal">
                <i class="bi bi-plus-circle me-1"></i> Record Production Expense / Asset
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Summary Analytics Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Production Expense Spend -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Total Production Spend</span>
                        <div class="p-2 rounded-circle bg-primary-subtle text-primary">
                            <i class="bi bi-currency-rupee fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-primary mb-1">₹ <?= number_format($stats['total_cost'], 2) ?></h3>
                    <div class="small text-muted d-flex justify-content-between align-items-center">
                        <span><?= number_format($stats['total_records']) ?> Transaction(s)</span>
                        <span class="badge bg-light text-dark border">All Units Combined</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Unit 1 vs Unit 2 Spend -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Unit 1 &amp; Unit 2 Costs</span>
                        <div class="p-2 rounded-circle bg-info-subtle text-info">
                            <i class="bi bi-diagram-2 fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <div>
                            <span class="badge bg-primary text-white me-1">Unit 1</span>
                            <strong class="text-dark">₹ <?= number_format($stats['unit1_cost'], 2) ?></strong>
                        </div>
                        <div>
                            <span class="badge bg-info text-white me-1">Unit 2</span>
                            <strong class="text-dark">₹ <?= number_format($stats['unit2_cost'], 2) ?></strong>
                        </div>
                    </div>
                    <div class="small text-muted d-flex justify-content-between align-items-center mt-2 pt-1 border-top">
                        <span>Common/Facility: ₹<?= number_format($stats['both_units_cost'], 2) ?></span>
                        <span class="badge bg-info-subtle text-info border">By Unit</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Machinery vs Consumables (Threads/Zips/Needles) -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Machinery &amp; Materials</span>
                        <div class="p-2 rounded-circle bg-success-subtle text-success">
                            <i class="bi bi-scissors fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <div>
                            <small class="text-muted d-block">Machinery Assets</small>
                            <strong class="text-warning text-dark">₹<?= number_format($stats['machinery_cost'], 2) ?></strong>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Threads / Zips / Needles</small>
                            <strong class="text-success">₹<?= number_format($stats['sewing_materials_cost'], 2) ?></strong>
                        </div>
                    </div>
                    <div class="small text-muted d-flex justify-content-between align-items-center mt-2 pt-1 border-top">
                        <span>Repairs: ₹<?= number_format($stats['maintenance_cost'], 2) ?></span>
                        <span class="badge bg-success-subtle text-success border">Assets &amp; Trims</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Paid vs Pending Payables -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Settlement &amp; Payables</span>
                        <div class="p-2 rounded-circle bg-danger-subtle text-danger">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <div>
                            <small class="text-muted d-block">Paid Settled</small>
                            <strong class="text-success">₹<?= number_format($stats['total_paid'], 2) ?></strong>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Vendor Due</small>
                            <strong class="text-danger">₹<?= number_format($stats['total_pending'], 2) ?></strong>
                        </div>
                    </div>
                    <div class="small text-muted d-flex justify-content-between align-items-center mt-2 pt-1 border-top">
                        <span><?= number_format(($stats['count_pending'] ?? 0) + ($stats['count_partial'] ?? 0)) ?> Unsettled Bills</span>
                        <span class="badge bg-danger-subtle text-danger border">Payables</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('production/expenses') ?>" class="row g-2 align-items-center">
                <!-- Search -->
                <div class="col-lg-3 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="expensesSearchInput" class="form-control form-control-sm border-start-0" placeholder="Search item, vendor, invoice, serial..." value="<?= e($search) ?>" autocomplete="off">
                    </div>
                </div>

                <!-- Unit Filter -->
                <div class="col-lg-2 col-md-3">
                    <select name="production_unit" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Production Units --</option>
                        <?php foreach ($productionUnitOptions as $unitValue => $unitLabel): ?>
                            <option value="<?= e($unitValue) ?>" <?= $productionUnit === $unitValue ? 'selected' : '' ?>><?= e($unitLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="col-lg-2 col-md-3">
                    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Categories --</option>
                        <?php foreach ($expenseCategoryOptions as $categoryValue => $categoryLabel): ?>
                            <option value="<?= e($categoryValue) ?>" <?= $category === $categoryValue ? 'selected' : '' ?>><?= e($categoryLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Payment Status -->
                <div class="col-lg-2 col-md-3">
                    <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Payment Statuses --</option>
                        <option value="PAID" <?= $paymentStatus === 'PAID' ? 'selected' : '' ?>>Paid</option>
                        <option value="PARTIAL" <?= $paymentStatus === 'PARTIAL' ? 'selected' : '' ?>>Partial</option>
                        <option value="PENDING" <?= $paymentStatus === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="col-lg-1 col-md-3">
                    <input type="date" name="date_from" class="form-control form-control-sm" title="From Date" value="<?= e($dateFrom) ?>" onchange="this.form.submit()">
                </div>
                <div class="col-lg-1 col-md-3">
                    <input type="date" name="date_to" class="form-control form-control-sm" title="To Date" value="<?= e($dateTo) ?>" onchange="this.form.submit()">
                </div>

                <!-- Reset -->
                <div class="col-lg-1 col-md-2 text-end">
                    <a href="<?= url('production/expenses') ?>" class="btn btn-sm btn-outline-secondary w-100" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Expenses & Assets Table Log -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-card-list text-primary me-2"></i>Production Expenses &amp; Assets Log (<?= count($expenses) ?>)
            </h6>
            <span class="text-muted small">Unit 1 &amp; Unit 2 asset tracking, material purchases, and cost management</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($expenses)): ?>
                <div class="text-center py-5 text-muted">
                    <div class="d-inline-flex p-3 rounded-circle bg-light mb-2">
                        <i class="bi bi-inbox fs-1 text-secondary"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No Production Expenses or Assets Found</h5>
                    <p class="text-muted small mb-0">No records match your filters. Click "Record Production Expense / Asset" to add a new machinery, material, or maintenance record.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="expensesTable" style="font-size: 13px;">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Expense # &amp; Date</th>
                                <th>Unit &amp; Category</th>
                                <th>Item Name &amp; Serial / Tag</th>
                                <th>Vendor / Supplier</th>
                                <th class="text-end">Quantity &amp; Rate</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-center">Payment Status</th>
                                <th class="text-center">Bill / Photo</th>
                                <th class="text-center" style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $e): ?>
                                <tr class="expense-row">
                                    <!-- Expense # & Date -->
                                    <td>
                                        <span class="badge bg-dark font-monospace fs-7 px-2 py-1"><?= e($e['expense_number']) ?></span>
                                        <div class="text-muted small mt-1">
                                            <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($e['expense_date'])) ?>
                                        </div>
                                        <?php if (!empty($e['invoice_number'])): ?>
                                            <small class="text-muted d-block">Bill #: <?= e($e['invoice_number']) ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Unit & Category -->
                                    <td>
                                        <!-- Unit Badge -->
                                        <?php
                                        $unitBadge = match($e['production_unit']) {
                                            'PRODUCTION_1' => 'bg-primary text-white',
                                            'PRODUCTION_2' => 'bg-info text-white',
                                            'BOTH_UNITS'   => 'bg-dark text-white',
                                            default        => 'bg-secondary text-white'
                                        };
                                        $unitLabel = match($e['production_unit']) {
                                            'PRODUCTION_1' => 'Production Unit 1',
                                            'PRODUCTION_2' => 'Production Unit 2',
                                            'BOTH_UNITS'   => 'Both Units / Common',
                                            default        => 'General Facility'
                                        };
                                        ?>
                                        <span class="badge <?= $unitBadge ?> px-2 py-1 mb-1 d-inline-block">
                                            <i class="bi bi-building me-1"></i><?= $unitLabel ?>
                                        </span>

                                        <!-- Category Badge -->
                                        <div>
                                            <?php
                                            $catBadge = match($e['category']) {
                                                'MACHINERY_ASSET'    => 'bg-warning-subtle text-dark border-warning-subtle',
                                                'SEWING_MATERIALS'   => 'bg-success-subtle text-success border-success-subtle',
                                                'MAINTENANCE_REPAIR' => 'bg-danger-subtle text-danger border-danger-subtle',
                                                'OPERATIONAL_UTILITY'=> 'bg-primary-subtle text-primary border-primary-subtle',
                                                default              => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                            };
                                            $catIcon = match($e['category']) {
                                                'MACHINERY_ASSET'    => 'bi-gear-wide-connected',
                                                'SEWING_MATERIALS'   => 'bi-thread',
                                                'MAINTENANCE_REPAIR' => 'bi-tools',
                                                'OPERATIONAL_UTILITY'=> 'bi-lightning-charge',
                                                default              => 'bi-tag'
                                            };
                                            $catLabel = str_replace('_', ' ', $e['category']);
                                            ?>
                                            <span class="badge <?= $catBadge ?> border small fw-semibold">
                                                <i class="bi <?= $catIcon ?> me-1"></i><?= e($catLabel) ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Item Name & Serial / Tag -->
                                    <td>
                                        <strong class="text-dark d-block"><?= e($e['item_name']) ?></strong>
                                        <?php if (!empty($e['asset_tag_or_serial'])): ?>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-upc-scan me-1"></i>Serial: <span class="font-monospace text-dark"><?= e($e['asset_tag_or_serial']) ?></span>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($e['program_number'])): ?>
                                            <small class="text-primary d-block">
                                                <i class="bi bi-diagram-3 me-1"></i>Program: <?= e($e['program_number']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Vendor Details -->
                                    <td>
                                        <strong class="text-dark d-block"><?= e($e['vendor_name']) ?></strong>
                                        <?php if (!empty($e['vendor_company'])): ?>
                                            <span class="text-muted small d-block"><i class="bi bi-building me-1"></i><?= e($e['vendor_company']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($e['vendor_phone'])): ?>
                                            <span class="text-muted small d-block"><i class="bi bi-telephone me-1"></i><?= e($e['vendor_phone']) ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Quantity & Rate -->
                                    <td class="text-end">
                                        <div class="fw-bold text-dark fs-7"><?= number_format($e['quantity'], 2) ?> <?= e($e['unit_type']) ?></div>
                                        <small class="text-muted">@ ₹<?= number_format($e['unit_cost'], 2) ?> / <?= e($e['unit_type']) ?></small>
                                    </td>

                                    <!-- Total Cost -->
                                    <td class="text-end">
                                        <div class="fw-bold text-primary fs-6">₹ <?= number_format($e['total_cost'], 2) ?></div>
                                        <?php if ($e['paid_amount'] > 0 && $e['paid_amount'] < $e['total_cost']): ?>
                                            <small class="text-muted d-block">Paid: ₹<?= number_format($e['paid_amount'], 2) ?></small>
                                            <small class="text-danger fw-semibold d-block">Due: ₹<?= number_format($e['total_cost'] - $e['paid_amount'], 2) ?></small>
                                        <?php elseif ($e['paid_amount'] >= $e['total_cost']): ?>
                                            <small class="text-success fw-semibold"><i class="bi bi-check-all me-1"></i>Settled</small>
                                        <?php else: ?>
                                            <small class="text-warning fw-semibold">Unpaid</small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Payment Status -->
                                    <td class="text-center">
                                        <?php
                                        $payBadge = match($e['payment_status']) {
                                            'PAID'    => 'bg-success text-white',
                                            'PARTIAL' => 'bg-warning text-dark',
                                            default   => 'bg-danger text-white'
                                        };
                                        ?>
                                        <span class="badge <?= $payBadge ?> px-2 py-1"><?= e($e['payment_status']) ?></span>
                                        <div class="small text-muted mt-1"><?= e($e['payment_method']) ?></div>
                                    </td>

                                    <!-- Proof Photo / Bill -->
                                    <td class="text-center">
                                        <?php if (!empty($e['photo_url'])): ?>
                                            <a href="<?= uploadUrl($e['photo_url']) ?>" target="_blank" title="View bill slip or asset photo">
                                                <img src="<?= uploadUrl($e['photo_url']) ?>" alt="Proof" class="rounded border shadow-sm" style="width: 36px; height: 36px; object-fit: cover;">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <?php if (hasPermission('production_expenses', 'view')): ?>
                                            <button type="button" class="btn btn-outline-info" title="View Expense Details" onclick='openExpenseReceipt(<?= (int)$e['id'] ?>)'>
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php endif; ?>

                                            <!-- Print Voucher Button -->
                                            <?php if (hasPermission('production_expenses', 'print')): ?>
                                            <button type="button" class="btn btn-outline-secondary" title="Print Expense Voucher" onclick='openExpenseReceipt(<?= (int)$e['id'] ?>)'>
                                                <i class="bi bi-printer"></i>
                                            </button>
                                            <?php endif; ?>

                                            <?php if (hasPermission('production_expenses', 'edit')): ?>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-outline-primary" title="Edit Expense / Asset Details" onclick='openEditExpenseModal(<?= json_encode($e) ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php endif; ?>

                                            <?php if (hasPermission('production_expenses', 'delete')): ?>
                                            <!-- Delete Button -->
                                            <form method="POST" action="<?= url('production/expenses/delete/' . $e['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Expense #<?= e(addslashes($e['expense_number'])) ?> (<?= e(addslashes($e['item_name'])) ?>)?');">
                                                <?= CSRF::field() ?>
                                                <button type="submit" class="btn btn-outline-danger" title="Delete Expense Record">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination Controls Footer -->
                <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="expensesPaginationFooter">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="small text-muted fw-medium" id="expensesPageInfo">
                            Showing 1 to 10 of <?= count($expenses) ?> expenses
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-muted mb-0 fw-medium">Show:</label>
                            <select id="expensesPageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                                <option value="10" selected>10 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                    </div>
                    <nav aria-label="Expenses Pagination">
                        <ul class="pagination pagination-modern mb-0" id="expensesPaginationList">
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- ========================================================================= -->
<!-- 1. RECORD EXPENSE MODAL (Streamlined: Expense Type + Qty & Price)         -->
<!-- ========================================================================= -->
<div class="modal fade" id="recordExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="bi bi-plus-circle me-2"></i>Record Production Expense
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('production/expenses/store') ?>" id="recordExpenseForm" enctype="multipart/form-data">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <!-- 1. Expense Type Input with Side (+) Button -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark required mb-1">Expense Type</label>
                        <div class="input-group input-group-lg">
                            <input type="text" 
                                   name="item_name" 
                                   id="newItemName" 
                                   class="form-control fw-bold fs-6" 
                                   list="expenseTypesList" 
                                   placeholder="Select or enter expense type..." 
                                   autocomplete="off" 
                                   required 
                                   oninput="onExpenseTypeSelect(this.value)">
                            <button type="button" 
                                    class="btn btn-primary px-3 fw-bold" 
                                    id="btnAddExpenseType" 
                                    onclick="toggleQuickAddTypeBox()" 
                                    title="Create New Expense Type">
                                <i class="bi bi-plus-lg fs-5"></i>
                            </button>
                        </div>
                        <datalist id="expenseTypesList">
                            <?php foreach ($expenseItems ?? [] as $item): ?>
                                <option value="<?= htmlspecialchars($item['name']) ?>" data-rate="<?= (float)($item['default_rate'] ?? 0) ?>">
                            <?php endforeach; ?>
                        </datalist>

                        <!-- Quick Add Type Inline Form (Toggled by + Button) -->
                        <div id="quickAddTypeDrawer" class="mt-2 p-3 bg-light rounded-3 border border-primary-subtle d-none">
                            <label class="form-label small fw-bold text-dark mb-1">
                                <i class="bi bi-plus-circle-fill text-primary me-1"></i>Create New Expense Type
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       id="quickTypeNameInput" 
                                       class="form-control form-control-sm fw-bold" 
                                       placeholder="e.g. Thread Cones, Needles, Machine Servicing"
                                       onkeydown="if(event.key==='Enter'){event.preventDefault();saveQuickType();}">
                                <button type="button" class="btn btn-sm btn-primary px-3 fw-bold" id="btnSaveQuickType" onclick="saveQuickType()">
                                    <i class="bi bi-check-lg me-1"></i> Add
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleQuickAddTypeBox(false)">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Row with Two Input Fields: Quantity and Price -->
                    <div class="row g-3 mb-3">
                        <!-- Quantity Input -->
                        <div class="col-6">
                            <label class="form-label fw-bold text-dark required mb-1">Quantity</label>
                            <input type="number" 
                                   step="any" 
                                   min="0.01" 
                                   name="quantity" 
                                   id="newQuantity" 
                                   class="form-control form-control-lg fw-bold text-center" 
                                   value="1" 
                                   required 
                                   oninput="calculateNewExpenseTotal()">
                        </div>

                        <!-- Price Input -->
                        <div class="col-6">
                            <label class="form-label fw-bold text-dark required mb-1">Price (₹)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light fw-bold text-muted">₹</span>
                                <input type="number" 
                                       step="any" 
                                       min="0" 
                                       name="unit_cost" 
                                       id="newUnitCost" 
                                       class="form-control fw-bold text-primary" 
                                       placeholder="0.00" 
                                       required 
                                       oninput="calculateNewExpenseTotal()">
                            </div>
                        </div>
                    </div>

                    <!-- Total Amount Live Display -->
                    <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-muted small text-uppercase">Total Cost:</span>
                        <div class="d-flex align-items-center">
                            <span class="fs-5 fw-bold text-primary me-1">₹</span>
                            <span class="fs-3 fw-bolder text-primary font-monospace" id="newDisplayTotal">0.00</span>
                            <input type="hidden" name="total_cost" id="newTotalCost" value="0.00">
                        </div>
                    </div>

                    <!-- 4. Image Upload Input with Live Preview -->
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark mb-1">
                            <i class="bi bi-image text-primary me-1"></i>Image Upload (Receipt / Bill / Item Photo)
                        </label>
                        <input type="file" 
                               name="photo" 
                               id="newPhoto" 
                               class="form-control" 
                               accept="image/*,.pdf" 
                               onchange="previewExpenseImage(this, 'newPhotoPreview')">
                        <div id="newPhotoPreview" class="mt-2 text-center d-none p-2 bg-light rounded border">
                            <img src="" class="img-thumbnail rounded shadow-sm border" style="max-height: 120px;" alt="Selected receipt / photo">
                            <div class="small text-muted mt-1" id="newPhotoFilename"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Save Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. EDIT EXPENSE MODAL (Streamlined: Expense Type + Qty & Price)           -->
<!-- ========================================================================= -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="bi bi-pencil-square me-2"></i>Edit Production Expense
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editExpenseForm" enctype="multipart/form-data">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <!-- Expense Type -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark required mb-1">Expense Type</label>
                        <input type="text" 
                               name="item_name" 
                               id="editItemName" 
                               class="form-control form-control-lg fw-bold" 
                               list="expenseTypesList" 
                               placeholder="Select or enter expense type..." 
                               required>
                    </div>

                    <!-- Row with Two Input Fields: Quantity and Price -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold text-dark required mb-1">Quantity</label>
                            <input type="number" 
                                   step="any" 
                                   min="0.01" 
                                   name="quantity" 
                                   id="editQuantity" 
                                   class="form-control form-control-lg fw-bold text-center" 
                                   required 
                                   oninput="calculateEditExpenseTotal()">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-dark required mb-1">Price (₹)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light fw-bold text-muted">₹</span>
                                <input type="number" 
                                       step="any" 
                                       min="0" 
                                       name="unit_cost" 
                                       id="editUnitCost" 
                                       class="form-control fw-bold text-primary" 
                                       placeholder="0.00" 
                                       required 
                                       oninput="calculateEditExpenseTotal()">
                            </div>
                        </div>
                    </div>

                    <!-- Total Amount Live Display -->
                    <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-muted small text-uppercase">Total Cost:</span>
                        <div class="d-flex align-items-center">
                            <span class="fs-5 fw-bold text-primary me-1">₹</span>
                            <span class="fs-3 fw-bolder text-primary font-monospace" id="editDisplayTotal">0.00</span>
                            <input type="hidden" name="total_cost" id="editTotalCost" value="0.00">
                        </div>
                    </div>

                    <!-- 4. Image Upload Input with Live Preview -->
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark mb-1">
                            <i class="bi bi-image text-primary me-1"></i>Image Upload (Receipt / Bill / Item Photo)
                        </label>
                        <input type="file" 
                               name="photo" 
                               id="editPhoto" 
                               class="form-control" 
                               accept="image/*,.pdf" 
                               onchange="previewExpenseImage(this, 'editPhotoPreview')">
                        <div id="editPhotoPreview" class="mt-2 text-center d-none p-2 bg-light rounded border">
                            <img src="" class="img-thumbnail rounded shadow-sm border" style="max-height: 120px;" alt="Selected receipt / photo">
                            <div class="small text-muted mt-1" id="editPhotoStatus">Current Image</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                        <i class="bi bi-save me-1"></i> Update Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. PRINTABLE EXPENSE VOUCHER MODAL                                        -->
<!-- ========================================================================= -->
<div class="modal fade" id="expenseReceiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-receipt me-1"></i>Production Expense / Asset Voucher
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="printableExpenseVoucherArea">
                <div class="text-center mb-3">
                    <h5 class="fw-bold mb-0 text-dark">GRM APPARELS</h5>
                    <small class="text-muted d-block">Production Expense &amp; Asset Voucher</small>
                    <span class="badge bg-light text-dark border mt-1" id="recExpNo">EXP-2026-0001</span>
                </div>
                <hr class="my-2">
                <div class="row g-1 small mb-3">
                    <div class="col-6"><strong>Date:</strong> <span id="recExpDate">-</span></div>
                    <div class="col-6 text-end"><strong>Status:</strong> <span id="recExpStatus">-</span></div>
                    <div class="col-12"><strong>Production Unit:</strong> <span id="recExpUnit">-</span></div>
                    <div class="col-12"><strong>Category:</strong> <span id="recExpCategory">-</span></div>
                    <div class="col-12"><strong>Vendor / Supplier:</strong> <span id="recExpVendor">-</span></div>
                    <div class="col-12" id="recExpCompanyRow"><strong>Company:</strong> <span id="recExpCompany">-</span></div>
                    <div class="col-12" id="recExpSerialRow"><strong>Serial / Asset Tag:</strong> <span id="recExpSerial">-</span></div>
                    <div class="col-12" id="recExpProgramRow"><strong>Linked Program:</strong> <span id="recExpProgram">-</span></div>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item Description</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit Rate</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id="recExpItem">-</td>
                                <td class="text-end" id="recExpQty">0.00</td>
                                <td class="text-end" id="recExpRate">₹0.00</td>
                                <td class="text-end fw-bold" id="recExpTotal">₹0.00</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total Expense Cost:</th>
                                <th class="text-end text-primary" id="recExpTotalFooter">₹0.00</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Amount Paid:</th>
                                <th class="text-end text-success" id="recExpPaid">₹0.00</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Balance Due:</th>
                                <th class="text-end text-danger" id="recExpDue">₹0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="small text-muted mb-3" id="recExpNotes"></div>
                <div class="d-flex justify-content-between text-muted small pt-3 border-top">
                    <div>Receiver's Signature</div>
                    <div>Authorized Signatory</div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <?php if (hasPermission('production_expenses', 'print')): ?>
                <button type="button" class="btn btn-dark btn-sm px-3" onclick="printExpenseVoucherArea()">
                    <i class="bi bi-printer me-1"></i> Print Voucher
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
let expenseTypesMaster = <?= json_encode($expenseItems ?? []) ?>;

function toggleQuickAddTypeBox(forceState) {
    const box = document.getElementById('quickAddTypeDrawer');
    if (!box) return;
    const isHidden = box.classList.contains('d-none');
    const show = forceState !== undefined ? forceState : isHidden;
    if (show) {
        box.classList.remove('d-none');
        const typed = document.getElementById('newItemName').value.trim();
        const input = document.getElementById('quickTypeNameInput');
        if (typed && !input.value) {
            input.value = typed;
        }
        input.focus();
        input.select();
    } else {
        box.classList.add('d-none');
    }
}

function saveQuickType() {
    const input = document.getElementById('quickTypeNameInput');
    const name = (input.value || '').trim();
    const btn = document.getElementById('btnSaveQuickType');
    if (!name) {
        alert('Please enter an Expense Type name.');
        input.focus();
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Adding...';

    const formData = new FormData();
    formData.append('name', name);
    formData.append('category', 'GENERAL');
    formData.append('unit_type', 'PCS');
    formData.append('default_rate', '0.00');

    fetch('<?= url("api/production/expenses/quick-add-item") ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Add';

        if (data.success && data.item) {
            const item = data.item;
            const exists = expenseTypesMaster.find(it => it.name.trim().toLowerCase() === item.name.trim().toLowerCase());
            if (!exists) {
                expenseTypesMaster.push(item);
                const list = document.getElementById('expenseTypesList');
                if (list) {
                    const opt = document.createElement('option');
                    opt.value = item.name;
                    list.appendChild(opt);
                }
            }

            document.getElementById('newItemName').value = item.name;
            toggleQuickAddTypeBox(false);
            input.value = '';

            // Focus Quantity input
            const qtyInput = document.getElementById('newQuantity');
            if (qtyInput) {
                qtyInput.focus();
                qtyInput.select();
            }
        } else {
            alert(data.message || 'Failed to add expense type.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Add';
        alert('Error saving expense type.');
    });
}

function onExpenseTypeSelect(val) {
    const trimmed = (val || '').trim().toLowerCase();
    if (!trimmed) return;
    const found = expenseTypesMaster.find(it => it.name.trim().toLowerCase() === trimmed);
    if (found) {
        if (found.default_rate && parseFloat(found.default_rate) > 0) {
            const currentPrice = parseFloat(document.getElementById('newUnitCost').value) || 0;
            if (currentPrice === 0) {
                document.getElementById('newUnitCost').value = parseFloat(found.default_rate).toFixed(2);
                calculateNewExpenseTotal();
            }
        }
        const qtyInput = document.getElementById('newQuantity');
        if (qtyInput) {
            qtyInput.focus();
            qtyInput.select();
        }
    }
}

function calculateNewExpenseTotal() {
    const q = parseFloat(document.getElementById('newQuantity').value) || 0;
    const p = parseFloat(document.getElementById('newUnitCost').value) || 0;
    const total = q * p;
    document.getElementById('newTotalCost').value = total.toFixed(2);
    document.getElementById('newDisplayTotal').textContent = total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function calculateEditExpenseTotal() {
    const q = parseFloat(document.getElementById('editQuantity').value) || 0;
    const p = parseFloat(document.getElementById('editUnitCost').value) || 0;
    const total = q * p;
    document.getElementById('editTotalCost').value = total.toFixed(2);
    document.getElementById('editDisplayTotal').textContent = total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function previewExpenseImage(input, previewContainerId) {
    const container = document.getElementById(previewContainerId);
    if (!container) return;
    const img = container.querySelector('img');
    const status = container.querySelector('.small');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (img) img.src = e.target.result;
                if (status) status.textContent = file.name;
                container.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            if (img) img.src = '';
            if (status) status.textContent = 'Document selected: ' + file.name;
            container.classList.remove('d-none');
        }
    }
}

function openEditExpenseModal(data) {
    document.getElementById('editExpenseForm').action = '<?= url('production/expenses/update') ?>/' + data.id;
    document.getElementById('editItemName').value = data.item_name || '';
    document.getElementById('editQuantity').value = parseFloat(data.quantity || 1).toString();
    document.getElementById('editUnitCost').value = parseFloat(data.unit_cost || 0).toFixed(2);
    calculateEditExpenseTotal();

    const preview = document.getElementById('editPhotoPreview');
    const img = preview ? preview.querySelector('img') : null;
    const status = preview ? preview.querySelector('.small') : null;
    const fileInput = document.getElementById('editPhoto');
    if (fileInput) fileInput.value = '';

    if (preview && data.photo_url) {
        let photoUrl = data.photo_url;
        if (!photoUrl.startsWith('http://') && !photoUrl.startsWith('https://')) {
            photoUrl = '<?= url('') ?>/' + photoUrl.replace(/^\/+/, '');
        }
        if (img) img.src = photoUrl;
        if (status) status.textContent = 'Current Image (choose new file to replace)';
        preview.classList.remove('d-none');
    } else if (preview) {
        preview.classList.add('d-none');
        if (img) img.src = '';
    }

    const modal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
    modal.show();
}

function openExpenseReceipt(id) {
    fetch('<?= url('production/expenses/receipt') ?>/' + id)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.expense) {
                const e = res.expense;
                document.getElementById('recExpNo').textContent = e.expense_number;
                document.getElementById('recExpDate').textContent = e.expense_date;
                document.getElementById('recExpStatus').textContent = e.payment_status;
                
                const unitLabels = {
                    'PRODUCTION_1': 'Production Unit 1',
                    'PRODUCTION_2': 'Production Unit 2',
                    'BOTH_UNITS': 'Both Units / Common',
                    'GENERAL': 'General Facility'
                };
                document.getElementById('recExpUnit').textContent = unitLabels[e.production_unit] || e.production_unit;
                document.getElementById('recExpCategory').textContent = (e.category || '').replace(/_/g, ' ');
                document.getElementById('recExpVendor').textContent = e.vendor_name;

                if (e.vendor_company) {
                    document.getElementById('recExpCompanyRow').classList.remove('d-none');
                    document.getElementById('recExpCompany').textContent = e.vendor_company;
                } else {
                    document.getElementById('recExpCompanyRow').classList.add('d-none');
                }

                if (e.asset_tag_or_serial) {
                    document.getElementById('recExpSerialRow').classList.remove('d-none');
                    document.getElementById('recExpSerial').textContent = e.asset_tag_or_serial;
                } else {
                    document.getElementById('recExpSerialRow').classList.add('d-none');
                }

                if (e.program_number) {
                    document.getElementById('recExpProgramRow').classList.remove('d-none');
                    document.getElementById('recExpProgram').textContent = e.program_number + (e.program_name ? ' (' + e.program_name + ')' : '');
                } else {
                    document.getElementById('recExpProgramRow').classList.add('d-none');
                }

                document.getElementById('recExpItem').textContent = e.item_name;
                document.getElementById('recExpQty').textContent = parseFloat(e.quantity).toFixed(2) + ' ' + (e.unit_type || 'PCS');
                document.getElementById('recExpRate').textContent = '₹' + parseFloat(e.unit_cost).toFixed(2);
                document.getElementById('recExpTotal').textContent = '₹' + parseFloat(e.total_cost).toFixed(2);
                document.getElementById('recExpTotalFooter').textContent = '₹' + parseFloat(e.total_cost).toFixed(2);
                document.getElementById('recExpPaid').textContent = '₹' + parseFloat(e.paid_amount).toFixed(2);
                const due = Math.max(0, parseFloat(e.total_cost) - parseFloat(e.paid_amount));
                document.getElementById('recExpDue').textContent = '₹' + due.toFixed(2);
                document.getElementById('recExpNotes').textContent = e.notes ? ('Remarks: ' + e.notes) : '';

                const modal = new bootstrap.Modal(document.getElementById('expenseReceiptModal'));
                modal.show();
            }
        })
        .catch(err => {
            console.error('Error fetching expense voucher data:', err);
            alert('Failed to load expense voucher.');
        });
}

function printExpenseVoucherArea() {
    const printContents = document.getElementById('printableExpenseVoucherArea').innerHTML;
    const origContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = origContents;
    window.location.reload();
}

// Initialize Expenses & Assets Table Pagination
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initClientPagination === 'function' && document.getElementById('expensesTable')) {
        const expensesPagination = window.initClientPagination({
            container: '#expensesTable tbody',
            itemSelector: 'tr.expense-row',
            footer: '#expensesPaginationFooter',
            infoEl: '#expensesPageInfo',
            paginationList: '#expensesPaginationList',
            pageSizeSelect: '#expensesPageSizeSelect',
            pageSize: 10,
            itemName: 'expenses'
        });

        const searchInput = document.getElementById('expensesSearchInput');
        if (searchInput && expensesPagination) {
            let debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const q = searchInput.value.trim().toLowerCase();
                    expensesPagination.filter(row => {
                        if (!q) return true;
                        const text = (row.textContent || '').toLowerCase();
                        return text.includes(q);
                    });
                }, 150);
            });
        }
    }
});
</script>
