<?php
// views/warehouse/expenses/index.php
// Warehouse Operational Expenses & Petty Cash Management Portal
$expenses = $expenses ?? [];
$summary = $summary ?? [
    'total_count' => 0, 'total_amount' => 0, 'total_paid' => 0,
    'total_pending' => 0, 'cash_outflow' => 0, 'digital_outflow' => 0,
    'categories' => [], 'payment_modes' => []
];
$categories = $categories ?? [];
$warehouses = $warehouses ?? [];
$selectedWhId = $selectedWhId ?? 0;

$curSearch = $filters['search'] ?? '';
$curCategory = $filters['category'] ?? 'all';
$curPaymentMethod = $filters['payment_method'] ?? 'all';
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-d');
?>

<!-- ========================================================================= -->
<!-- 1. HEADER & ACTIONS -->
<!-- ========================================================================= -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 text-uppercase fw-bold" style="letter-spacing: 0.5px;">
                <i class="bi bi-buildings me-1"></i>Warehouse Operations
            </span>
            <?php if ($selectedWhId > 0): 
                $activeWhName = '';
                foreach ($warehouses as $w) { if ($w['id'] == $selectedWhId) $activeWhName = $w['name']; }
            ?>
                <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-geo-alt me-1"></i><?= e($activeWhName) ?></span>
            <?php endif; ?>
        </div>
        <h4 class="mb-0 fw-bold text-dark mt-1">
            <i class="bi bi-cash-stack text-primary me-2"></i>Warehouse Operational Expenses &amp; Petty Cash
        </h4>
        <div class="text-muted small mt-0.5">Manage carton boxes, freight transport, forklift maintenance, utility bills, and petty cash vouchers</div>
    </div>
    
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php if (hasPermission('warehouse_expenses', 'create')): ?>
        <button type="button" class="btn btn-primary fw-semibold shadow-xs" data-bs-toggle="modal" data-bs-target="#recordExpenseModal">
            <i class="bi bi-plus-circle me-1.5"></i>Record Warehouse Expense
        </button>
        <?php endif; ?>
        <?php if (hasPermission('reports', 'view')): ?>
            <a href="<?= url('reports/warehouse') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-bar-chart me-1"></i>Warehouse Reports
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Flash Alerts -->
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-xs mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= e($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-xs mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- 2. TOP KPI SUMMARY CARDS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Total Warehouse Spend -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Warehouse Outflow</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($summary['total_amount'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-receipt me-1"></i><?= number_format($summary['total_count']) ?> Expense Vouchers
                    </div>
                </div>
                <div class="bg-success text-white p-2.5 rounded-3 shadow-xs"><i class="bi bi-currency-rupee fs-4"></i></div>
            </div>
        </div>
    </div>

    <!-- Direct Cash Outflow -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Cash &amp; Petty Disbursed</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($summary['cash_outflow'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-wallet2 me-1"></i>Physical Cash Drawer
                    </div>
                </div>
                <div class="bg-primary text-white p-2.5 rounded-3 shadow-xs"><i class="bi bi-cash-stack fs-4"></i></div>
            </div>
        </div>
    </div>

    <!-- Bank & Digital Transfers -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border-left: 4px solid #0284c7 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-info" style="font-size: 0.72rem; letter-spacing: 0.5px; color: #0284c7 !important;">UPI &amp; Bank Transfers</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($summary['digital_outflow'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-bank me-1"></i>Direct Vendor NEFT/UPI
                    </div>
                </div>
                <div class="bg-info text-white p-2.5 rounded-3 shadow-xs" style="background-color: #0284c7 !important;"><i class="bi bi-credit-card fs-4"></i></div>
            </div>
        </div>
    </div>

    <!-- Pending Payables -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #d97706 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-warning" style="font-size: 0.72rem; letter-spacing: 0.5px;">Pending Payables</div>
                    <div class="fs-4 fw-bold text-warning-emphasis mt-1 font-monospace">₹<?= number_format($summary['total_pending'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-clock-history me-1"></i>Unsettled Invoices
                    </div>
                </div>
                <div class="bg-warning text-dark p-2.5 rounded-3 shadow-xs"><i class="bi bi-hourglass-split fs-4"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. FILTER BAR -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-end">
            <!-- Warehouse Selector -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <label class="form-label text-muted small fw-semibold mb-1"><i class="bi bi-buildings me-1"></i>Warehouse</label>
                <select name="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()" <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                    <?php if (!function_exists('currentWarehouseId') || currentWarehouseId() === null && count($warehouses) > 1): ?>
                        <option value="0">All Warehouses</option>
                    <?php endif; ?>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?= $wh['id'] ?>" <?= (($selectedWhId == $wh['id']) || (function_exists('currentWarehouseId') && currentWarehouseId() == $wh['id'])) ? 'selected' : '' ?>>
                            <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1): ?>
                    <input type="hidden" name="warehouse_id" value="<?= e($selectedWhId ?: ($warehouses[0]['id'] ?? (function_exists('currentWarehouseId') ? currentWarehouseId() : ''))) ?>">
                <?php endif; ?>
            </div>

            <!-- Start Date -->
            <div class="col-xl-2 col-md-2 col-sm-6">
                <label class="form-label text-muted small fw-semibold mb-1">From Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>" onchange="this.form.submit()">
            </div>

            <!-- End Date -->
            <div class="col-xl-2 col-md-2 col-sm-6">
                <label class="form-label text-muted small fw-semibold mb-1">To Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>" onchange="this.form.submit()">
            </div>

            <!-- Category -->
            <div class="col-xl-2 col-md-2 col-sm-6">
                <label class="form-label text-muted small fw-semibold mb-1">Category</label>
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['name']) ?>" <?= $curCategory === $cat['name'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Payment Method -->
            <div class="col-xl-2 col-md-2 col-sm-6">
                <label class="form-label text-muted small fw-semibold mb-1">Payment Mode</label>
                <select name="payment_method" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Modes</option>
                    <option value="CASH" <?= $curPaymentMethod === 'CASH' ? 'selected' : '' ?>>Cash</option>
                    <option value="UPI" <?= $curPaymentMethod === 'UPI' ? 'selected' : '' ?>>UPI</option>
                    <option value="BANK_TRANSFER" <?= $curPaymentMethod === 'BANK_TRANSFER' ? 'selected' : '' ?>>Bank Transfer</option>
                    <option value="CARD" <?= $curPaymentMethod === 'CARD' ? 'selected' : '' ?>>Card</option>
                    <option value="CHEQUE" <?= $curPaymentMethod === 'CHEQUE' ? 'selected' : '' ?>>Cheque</option>
                </select>
            </div>

            <!-- Search -->
            <div class="col-xl-2 col-md-1 col-sm-12 d-flex gap-2">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search payee/bill..." value="<?= e($curSearch) ?>">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 4. EXPENSE REGISTER TABLE -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 px-3.5 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-journal-text text-primary me-2"></i>Warehouse Expenses Register
            </h5>
            <div class="small text-muted">Showing <?= count($expenses) ?> records from <?= date('d M Y', strtotime($startDate)) ?> to <?= date('d M Y', strtotime($endDate)) ?></div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border font-monospace">Total: ₹<?= number_format($summary['total_amount'], 2) ?></span>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Voucher #</th>
                        <th>Warehouse</th>
                        <th>Category</th>
                        <th>Particulars / Item</th>
                        <th>Paid To / Vendor</th>
                        <th>Payment Mode</th>
                        <th>Status</th>
                        <th class="text-end">Amount (INR)</th>
                        <th class="pe-3 text-end">Date</th>
                        <th class="pe-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                No warehouse expenses found for the selected period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $exp): 
                            $pm = strtoupper($exp['payment_method'] ?? 'CASH');
                            $ps = strtoupper($exp['payment_status'] ?? 'PAID');
                            $psBadge = ($ps === 'PAID') ? 'bg-success' : (($ps === 'PARTIAL') ? 'bg-warning text-dark' : 'bg-danger');
                        ?>
                            <tr>
                                <td class="ps-3 fw-bold font-monospace text-primary">
                                    <?= e($exp['expense_number']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-buildings me-1"></i><?= e($exp['location_name'] ?? 'Main WH') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">
                                        <?= e($exp['category']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($exp['item_name']) ?></div>
                                    <?php if (!empty($exp['description'])): ?>
                                        <div class="small text-muted" style="font-size: 0.72rem;"><?= e(substr($exp['description'], 0, 45)) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($exp['paid_to']) ?></div>
                                    <?php if (!empty($exp['vendor_phone'])): ?>
                                        <div class="small text-muted font-monospace"><i class="bi bi-telephone me-0.5"></i><?= e($exp['vendor_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace"><?= e($pm) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $psBadge ?>"><?= e($ps) ?></span>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark">
                                    ₹<?= number_format($exp['total_cost'], 2) ?>
                                </td>
                                <td class="pe-3 text-end small text-muted font-monospace">
                                    <?= date('d M Y', strtotime($exp['expense_date'])) ?>
                                </td>
                                <td class="pe-3 text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (!empty($exp['receipt_url'])): ?>
                                            <a href="<?= uploadUrl($exp['receipt_url']) ?>" target="_blank" class="btn btn-xs btn-outline-success" title="View Cloudinary Bill / Receipt">
                                                <i class="bi bi-paperclip"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('warehouse_expenses', 'voucher')): ?>
                                            <a href="<?= url("warehouse/expenses/voucher/{$exp['id']}") ?>" target="_blank" class="btn btn-xs btn-outline-primary" title="Print Payment Voucher">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('warehouse_expenses', 'delete')): ?>
                                        <form method="POST" action="<?= url("warehouse/expenses/delete/{$exp['id']}") ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this expense voucher?');">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($expenses)): ?>
                    <tfoot class="table-light fw-bold border-top">
                        <tr>
                            <td colspan="7" class="ps-3 text-uppercase">Total Spend (<?= count($expenses) ?> Records)</td>
                            <td class="text-end font-monospace text-dark">₹<?= number_format($summary['total_amount'], 2) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 5. RECORD WAREHOUSE EXPENSE MODAL -->
<!-- ========================================================================= -->
<div class="modal fade" id="recordExpenseModal" tabindex="-1" aria-labelledby="recordExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="recordExpenseModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Record Warehouse Operational Expense
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('warehouse/expenses/store') ?>" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Warehouse -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Warehouse Location <span class="text-danger">*</span></label>
                            <select name="location_id" class="form-select" required <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?= $wh['id'] ?>" <?= (($selectedWhId == $wh['id']) || (function_exists('currentWarehouseId') && currentWarehouseId() == $wh['id'])) ? 'selected' : '' ?>>
                                        <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1): ?>
                                <input type="hidden" name="location_id" value="<?= e($selectedWhId ?: ($warehouses[0]['id'] ?? (function_exists('currentWarehouseId') ? currentWarehouseId() : ''))) ?>">
                            <?php endif; ?>
                        </div>

                        <!-- Expense Category -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Expense Category <span class="text-danger">*</span></label>
                            <input type="text" name="category" class="form-control" list="whCategoryList" placeholder="e.g. Logistics & Freight Shipping, Carton Packaging..." required>
                            <datalist id="whCategoryList">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= e($cat['name'] ?? $cat['category_name'] ?? '') ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <!-- Particulars / Line Item -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Particulars / Item Description <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" class="form-control" placeholder="e.g. Staff Tea & Snacks / Carton Boxes / Printing Paper" required>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Amount (INR ₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control fw-bold font-monospace" placeholder="0.00" required>
                            </div>
                        </div>

                        <!-- Paid To / Vendor -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Paid To / Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="paid_to" class="form-control" placeholder="e.g. Sri Balaji Packaging / Tea Stall / Vendor" required>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="CASH">Cash (Petty Cash)</option>
                                <option value="UPI">UPI (GPay / PhonePe)</option>
                                <option value="BANK_TRANSFER">Bank Transfer (NEFT/RTGS)</option>
                                <option value="CARD">Debit / Credit Card</option>
                                <option value="CHEQUE">Cheque</option>
                            </select>
                        </div>

                        <!-- Payment Status -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="PAID">PAID (Disbursed)</option>
                                <option value="PENDING">PENDING (Payable)</option>
                                <option value="PARTIAL">PARTIAL</option>
                            </select>
                        </div>

                        <!-- Expense Date -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Expense Date</label>
                            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-12">
                            <details>
                                <summary class="small fw-semibold text-primary cursor-pointer">Additional details (optional)</summary>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-dark">Vendor Phone</label>
                                        <input type="text" name="vendor_phone" class="form-control" placeholder="e.g. 9840123456">
                                    </div>
                                    <div class="col-12">
                                        <div class="p-3 bg-light rounded-3 border">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h6 class="fw-bold text-primary mb-0 small text-uppercase">
                                                    <i class="bi bi-truck me-1"></i>Logistics &amp; Transport Details
                                                </h6>
                                                <span class="text-muted" style="font-size: 11px;">Skip for general expenses</span>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label small text-muted mb-1">Transporter Name</label>
                                                    <input type="text" name="transporter_name" class="form-control form-control-sm" placeholder="e.g. VRL Logistics">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small text-muted mb-1">Vehicle #</label>
                                                    <input type="text" name="vehicle_number" class="form-control form-control-sm" placeholder="e.g. TN-01-AB-1234">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small text-muted mb-1">Bill / LR #</label>
                                                    <input type="text" name="invoice_number" class="form-control form-control-sm" placeholder="e.g. INV-9876">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-dark d-flex align-items-center justify-content-between">
                                            <span><i class="bi bi-paperclip me-1"></i>Attach Bill / Receipt / Invoice Copy</span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;"><i class="bi bi-cloud-arrow-up me-1"></i>Cloudinary CDN</span>
                                        </label>
                                        <input type="file" name="receipt_file" class="form-control" accept="image/*,.pdf">
                                        <div class="form-text small text-muted" style="font-size: 11px;"><i class="bi bi-cloud-check text-success me-1"></i>JPG, PNG, WEBP or PDF.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small text-muted">Internal Remarks / Notes</label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Additional details, approval remarks, or bill descriptions..."></textarea>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2.5">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-check-circle me-1"></i>Save &amp; Generate Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>
