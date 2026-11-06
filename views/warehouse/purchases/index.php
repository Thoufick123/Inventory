<?php $canReceivePurchase = hasPermission('purchases', 'receive'); ?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-cart-check me-2 text-primary"></i>Warehouse Direct Customer Purchases</h2>
        
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('purchases', 'create')): ?>
        <a href="<?= url('warehouse/purchases/create') ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> New Direct Customer Purchase
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- METRIC SUMMARY STATS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-primary border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Total Direct Orders</span>
                    <h4 class="mb-0 fw-bold mt-1 text-dark"><?= number_format($stats['total_orders'] ?? 0) ?></h4>
                </div>
                <div class="rounded-circle bg-primary-subtle text-primary p-3">
                    <i class="bi bi-receipt fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Total Sales Revenue</span>
                    <h4 class="mb-0 fw-bold mt-1 text-success">₹<?= number_format($stats['total_revenue'] ?? 0, 2) ?></h4>
                </div>
                <div class="rounded-circle bg-success-subtle text-success p-3">
                    <i class="bi bi-currency-rupee fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Total Garment Units</span>
                    <h4 class="mb-0 fw-bold mt-1 text-info"><?= number_format($stats['total_units'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h4>
                </div>
                <div class="rounded-circle bg-info-subtle text-info p-3">
                    <i class="bi bi-box-seam fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Paid vs Pending Revenue</span>
                    <h5 class="mb-0 fw-bold mt-1 text-dark">
                        <span class="text-success">₹<?= number_format($stats['paid_revenue'] ?? 0) ?></span>
                        <small class="text-muted fs-7">/ ₹<?= number_format($stats['pending_revenue'] ?? 0) ?> pend</small>
                    </h5>
                </div>
                <div class="rounded-circle bg-warning-subtle text-warning p-3">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- FILTERS CARD (AUTOMATIC INSTANT FILTERING) -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4 bg-white rounded-3">
    <div class="card-body">
        <form id="purchaseFilterForm" method="GET" action="<?= url('warehouse/purchases') ?>" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label small fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-search text-primary me-1"></i>Search Customer / Order # / Phone</span>
                    <span id="liveSearchIndicator" class="text-muted small fw-normal d-none"><i class="spinner-border spinner-border-sm text-primary me-1" style="width: 0.8rem; height: 0.8rem;"></i>Filtering...</span>
                </label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="searchFilterInput" name="search" class="form-control border-start-0" 
                           placeholder="Type customer name, phone, order # (auto-filters)..." 
                           value="<?= e($search ?? '') ?>" 
                           autocomplete="off">
                    <?php if (!empty($search)): ?>
                        <button type="button" class="btn btn-outline-secondary border-start-0" onclick="clearSearchFilter()" title="Clear Search">
                            <i class="bi bi-x-circle-fill text-muted"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold"><i class="bi bi-building text-secondary me-1"></i>Warehouse</label>
                <select id="warehouseFilterSelect" name="warehouse_id" class="form-select form-select-sm" onchange="autoTriggerFilter()" <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                    <?php if (!function_exists('currentWarehouseId') || currentWarehouseId() === null && count($warehouses) > 1): ?>
                        <option value="">All Warehouses</option>
                    <?php endif; ?>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?= $wh['id'] ?>" <?= (($warehouseId ?? '') == $wh['id'] || (function_exists('currentWarehouseId') && currentWarehouseId() == $wh['id'])) ? 'selected' : '' ?>>
                            <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1): ?>
                    <input type="hidden" name="warehouse_id" value="<?= e($warehouseId ?? ($warehouses[0]['id'] ?? '')) ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-2">
                <button type="button" onclick="resetPurchaseFilters()"
                   class="btn btn-sm btn-outline-secondary w-100"
                   title="Clear all active filters" 
                   style="height: 31px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reset
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- SEPARATE TABS: 1. ALL INVOICES | 2. ONE-TIME PURCHASES | 3. REGULAR CUSTOMERS -->
<!-- ========================================================================= -->
<?php 
$regularCount = count($regularCustomers ?? []);
$oneTimeCount = count($oneTimeCustomers ?? []) + count($unregisteredPurchases ?? []);
?>

<div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
    <!-- View Mode Tabs Bar -->
    <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <ul class="nav nav-pills gap-2" id="customerCategoryTabs" role="tablist">
            <!-- TAB 1: ALL INVOICES -->
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-2 px-3 fw-semibold small shadow-sm d-flex align-items-center gap-1.5" 
                        id="tab-all-btn" data-bs-toggle="pill" data-bs-target="#tab-all-pane" type="button" role="tab">
                    <i class="bi bi-receipt me-1 text-white"></i>
                    <span>All Invoices (Order-by-Order)</span>
                    <span class="badge bg-white text-primary rounded-pill ms-1 fw-bold"><?= count($purchases ?? []) ?></span>
                </button>
            </li>
            
            <!-- TAB 2: ONE-TIME PURCHASES (SECOND) -->
            <li class="nav-item" role="presentation">
                <button class="nav-link py-2 px-3 fw-semibold small text-secondary border bg-light d-flex align-items-center gap-1.5" 
                        id="tab-onetime-btn" data-bs-toggle="pill" data-bs-target="#tab-onetime-pane" type="button" role="tab">
                    <i class="bi bi-bag-check me-1 text-primary"></i>
                    <span>One-Time Purchases</span>
                    <span class="badge bg-secondary text-white rounded-pill ms-1"><?= $oneTimeCount ?></span>
                </button>
            </li>

            <!-- TAB 3: REGULAR CUSTOMERS -->
            <li class="nav-item" role="presentation">
                <button class="nav-link py-2 px-3 fw-semibold small text-secondary border bg-light d-flex align-items-center gap-1.5" 
                        id="tab-regular-btn" data-bs-toggle="pill" data-bs-target="#tab-regular-pane" type="button" role="tab">
                    <i class="bi bi-arrow-repeat me-1 text-primary fs-6"></i>
                    <span>Regular Customers (Repeat Buyers)</span>
                    <span class="badge bg-secondary text-white rounded-pill ms-1"><?= $regularCount ?></span>
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content" id="customerCategoryTabContent">
            
            <!-- ============================================================= -->
            <!-- TAB 3: REGULAR BUY CUSTOMERS LIST -->
            <!-- ============================================================= -->
            <div class="tab-pane fade" id="tab-regular-pane" role="tabpanel">
                <?php if (empty($regularCustomers)): ?>
                    <div class="text-center py-5">
                        <div class="rounded-circle bg-light d-inline-flex p-3 mb-2 text-muted">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                        <h5 class="text-muted">No Regular / Repeat Customers Found</h5>
                        <p class="text-muted small">Customers who make repeated purchases from warehouse stock will automatically appear here with lifetime statistics.</p>
                        <?php if (hasPermission('purchases', 'create')): ?>
                        <a href="<?= url('warehouse/purchases/create') ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Create Direct Purchase
                        </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light" style="font-size: 0.84rem;">
                                <tr>
                                    <th class="ps-3" style="width: 280px;">Customer Profile</th>
                                    <th>Warehouse</th>
                                    <th>Last Purchase</th>
                                    <th>Total Units</th>
                                    <th>Lifetime Spend</th>
                                    <th class="text-end pe-3" style="width: 210px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($regularCustomers as $cust): ?>
                                    <tr class="align-middle">
                                        <!-- Customer Profile & Details -->
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2.5">
                                                <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" 
                                                     style="width: 38px; height: 38px; font-size: 0.95rem; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); flex-shrink: 0;">
                                                    <?= strtoupper(substr($cust['customer_name'] ?? 'C', 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                        <a href="javascript:void(0)" 
                                                           onclick="openCustomerProfileModal(<?= (int)$cust['customer_id'] ?>, '<?= e(addslashes($cust['customer_name'])) ?>')" 
                                                           class="fw-bold text-primary text-decoration-none customer-hover-link" 
                                                           style="font-size: 0.92rem;" 
                                                           title="Click to view full customer profile & purchased items">
                                                            <span class="text-decoration-underline-hover"><?= e($cust['customer_name']) ?></span>
                                                        </a>
                                                    </div>
                                                    <div class="text-muted small mt-0.5" style="font-size: 0.78rem;">
                                                        <?php if (!empty($cust['customer_phone'])): ?>
                                                            <i class="bi bi-telephone me-1"></i><?= e($cust['customer_phone']) ?>
                                                        <?php endif; ?>
                                                        <?php if (!empty($cust['customer_city'])): ?>
                                                            <span class="text-secondary ms-1">| <?= e($cust['customer_city']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Warehouse -->
                                        <td><span class="badge bg-secondary"><?= e($cust['warehouse_name']) ?></span></td>

                                        <!-- Last Purchase Date -->
                                        <td>
                                            <div class="fw-semibold text-dark" style="font-size: 0.84rem;"><?= date('d M Y', strtotime($cust['latest_order_date'])) ?></div>
                                        </td>

                                        <!-- Total Units & SKUs -->
                                        <td>
                                            <span class="badge bg-light text-dark border fw-bold fs-7">
                                                <?= number_format($cust['total_units']) ?> pcs
                                            </span>
                                            <small class="text-muted d-block mt-0.5"><?= number_format($cust['total_items']) ?> SKU line(s)</small>
                                        </td>

                                        <!-- Lifetime Spend -->
                                        <td>
                                            <span class="fw-bold text-success fs-6">₹<?= number_format($cust['grand_total'], 2) ?></span>
                                            <?php if ($cust['pending_total'] > 0): ?>
                                                <br><small class="text-danger">Pend: ₹<?= number_format($cust['pending_total'], 2) ?></small>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Actions -->
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <!-- Profile Button -->
                                                <button type="button" 
                                                        class="btn btn-primary fw-semibold" 
                                                        onclick="openCustomerProfileModal(<?= (int)$cust['customer_id'] ?>, '<?= e(addslashes($cust['customer_name'])) ?>')" 
                                                        title="View Customer Profile &amp; Bought Products Breakdown">
                                                    <i class="bi bi-person-lines-fill me-1"></i> Profile
                                                </button>
                                                
                                                <button type="button"
                                                        class="btn btn-outline-primary"
                                                        data-customer-name="<?= e($cust['customer_name']) ?>"
                                                        data-invoices="<?= e(json_encode($cust['invoices'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>"
                                                        onclick="openCustomerInvoicesModal(this)"
                                                        title="View this customer's invoices">
                                                    <i class="bi bi-receipt me-1"></i> Invoices
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ============================================================= -->
            <!-- TAB 2: ONE-TIME PURCHASES (SECOND) -->
            <!-- ============================================================= -->
            <div class="tab-pane fade" id="tab-onetime-pane" role="tabpanel">
                <?php if (empty($oneTimeCustomers) && empty($unregisteredPurchases)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bag fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No One-Time Purchases Found</h5>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light" style="font-size: 0.84rem;">
                                <tr>
                                    <th class="ps-3">Invoice #</th>
                                    <th>Customer Name / Type</th>
                                    <th>Warehouse</th>
                                    <th>Purchase Date</th>
                                    <th>Purchased Items</th>
                                    <th>Grand Total</th>
                                    <th>Payment</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Registered 1-time buyers (if any) -->
                                <?php if (!empty($oneTimeCustomers)): ?>
                                    <?php foreach ($oneTimeCustomers as $cust): ?>
                                        <?php $firstInv = $cust['invoices'][0] ?? []; ?>
                                        <tr>
                                            <td class="ps-3">
                                                <span class="fw-bold font-monospace text-primary"><?= e($firstInv['po_number'] ?? '') ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1.5">
                                                    <span class="badge bg-light text-primary border rounded-circle" style="width: 24px; height: 24px; line-height: 18px; text-align: center;">
                                                        <?= strtoupper(substr($cust['customer_name'] ?? 'C', 0, 1)) ?>
                                                    </span>
                                                    <a href="javascript:void(0)" 
                                                       onclick="openCustomerProfileModal(<?= (int)$cust['customer_id'] ?>, '<?= e(addslashes($cust['customer_name'])) ?>')" 
                                                       class="fw-bold text-dark text-decoration-none">
                                                        <?= e($cust['customer_name']) ?>
                                                    </a>
                                                    <span class="badge bg-light text-muted border small">1-Time Buyer</span>
                                                </div>
                                                <?php if (!empty($cust['customer_phone'])): ?>
                                                    <small class="text-muted d-block ms-4"><i class="bi bi-telephone me-1"></i><?= e($cust['customer_phone']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-secondary"><?= e($cust['warehouse_name']) ?></span></td>
                                            <td><small class="text-muted"><?= date('d M Y', strtotime($cust['latest_order_date'])) ?></small></td>
                                            <td>
                                                <span class="badge bg-light text-dark border fw-bold"><?= number_format($cust['total_units']) ?> pcs</span>
                                                <small class="text-muted d-block"><?= number_format($cust['total_items']) ?> SKU</small>
                                            </td>
                                            <td><span class="fw-bold text-success fs-6">₹<?= number_format($cust['grand_total'], 2) ?></span></td>
                                            <td>
                                                <span class="badge bg-<?= ($firstInv['payment_status'] ?? 'PAID') === 'PAID' ? 'success' : 'warning text-dark' ?>">
                                                    <?= e($firstInv['payment_status'] ?? 'PAID') ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" 
                                                            class="btn btn-outline-info" 
                                                            onclick="openCustomerProfileModal(<?= (int)$cust['customer_id'] ?>, '<?= e(addslashes($cust['customer_name'])) ?>')" 
                                                            title="View Customer Profile">
                                                        <i class="bi bi-person-lines-fill"></i>
                                                    </button>
                                                    <?php if ($canReceivePurchase): ?>
                                                        <a href="<?= url('warehouse/purchases/receive/' . ($firstInv['id'] ?? 0)) ?>" target="_blank" class="btn btn-outline-primary" title="Print Tax Invoice">
                                                            <i class="bi bi-printer me-1"></i> Invoice
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (hasPermission('purchases', 'edit')): ?>
                                                    <a href="<?= url('warehouse/purchases/edit/' . ($firstInv['id'] ?? 0)) ?>" class="btn btn-outline-secondary" title="Edit Purchase">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- Unregistered walk-in counter sales -->
                                <?php if (!empty($unregisteredPurchases)): ?>
                                    <?php foreach ($unregisteredPurchases as $po): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <span class="fw-bold font-monospace text-primary"><?= e($po['po_number']) ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark"><i class="bi bi-person text-secondary me-1"></i><?= e($po['display_customer_name'] ?? 'Direct Customer') ?></span>
                                                <span class="badge bg-light text-secondary border small ms-1">Walk-in Counter</span>
                                            </td>
                                            <td><span class="badge bg-secondary"><?= e($po['warehouse_name']) ?></span></td>
                                            <td><small class="text-muted"><?= date('d M Y', strtotime($po['order_date'])) ?></small></td>
                                            <td>
                                                <span class="badge bg-light text-dark border fw-bold"><?= number_format($po['total_ordered_qty']) ?> pcs</span>
                                                <small class="text-muted d-block"><?= number_format($po['total_items']) ?> SKU</small>
                                            </td>
                                            <td><span class="fw-bold text-success fs-6">₹<?= number_format($po['total_amount'], 2) ?></span></td>
                                            <td>
                                                <span class="badge bg-<?= ($po['payment_status'] ?? 'PAID') === 'PAID' ? 'success' : 'warning text-dark' ?>">
                                                    <?= e($po['payment_status'] ?? 'PAID') ?>
                                                </span>
                                                <small class="text-muted d-block font-monospace"><?= e($po['payment_method'] ?? 'CASH') ?></small>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($canReceivePurchase): ?>
                                                        <a href="<?= url('warehouse/purchases/receive/' . $po['id']) ?>" target="_blank" class="btn btn-outline-primary" title="Print Tax Invoice">
                                                            <i class="bi bi-printer me-1"></i> Invoice
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (hasPermission('purchases', 'edit')): ?>
                                                    <a href="<?= url('warehouse/purchases/edit/' . $po['id']) ?>" class="btn btn-outline-secondary" title="Edit Purchase">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ============================================================= -->
            <!-- TAB 1: ALL INDIVIDUAL INVOICES (CONSOLIDATED LOG) -->
            <!-- ============================================================= -->
            <div class="tab-pane fade show active" id="tab-all-pane" role="tabpanel">
                <?php if (empty($purchases)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bag-x fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No Direct Customer Purchases Found</h5>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Order / Invoice #</th>
                                    <th>Customer Name &amp; Profile</th>
                                    <th>Warehouse</th>
                                    <th>Order Date</th>
                                    <th>Purchased Items</th>
                                    <th>Grand Total</th>
                                    <th>Payment</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($purchases as $po): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-bold font-monospace text-primary"><?= e($po['po_number']) ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <div>
                                                    <?php if (!empty($po['customer_id'])): ?>
                                                        <a href="javascript:void(0)" 
                                                           onclick="openCustomerProfileModal(<?= (int)$po['customer_id'] ?>, '<?= e(addslashes($po['display_customer_name'])) ?>')" 
                                                           class="fw-bold text-primary text-decoration-none d-inline-flex align-items-center gap-1.5 customer-hover-link" 
                                                           title="Click to view full customer profile & purchased items">
                                                            <span class="avatar-badge-sm rounded-circle bg-primary-subtle text-primary fw-bold d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.72rem;">
                                                                <?= strtoupper(substr($po['display_customer_name'] ?? 'C', 0, 1)) ?>
                                                            </span>
                                                            <span class="text-decoration-underline-hover"><?= e($po['display_customer_name']) ?></span>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="fw-bold text-dark d-inline-flex align-items-center gap-1">
                                                            <i class="bi bi-person text-secondary"></i>
                                                            <?= e($po['display_customer_name']) ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if (!empty($po['display_customer_phone'])): ?>
                                                        <div class="text-muted small mt-0.5" style="font-size: 0.78rem;">
                                                            <i class="bi bi-telephone me-1"></i><?= e($po['display_customer_phone']) ?>
                                                            <?php if (!empty($po['customer_city'])): ?>
                                                                <span class="text-secondary ms-1">| <?= e($po['customer_city']) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                            </div>
                                        </td>
                                        <td><span class="badge bg-secondary"><?= e($po['warehouse_name']) ?></span></td>
                                        <td><small class="text-muted"><?= date('d M Y', strtotime($po['order_date'])) ?></small></td>
                                        <td>
                                            <span class="badge bg-light text-dark border fw-bold">
                                                <?= number_format($po['total_ordered_qty']) ?> pcs
                                            </span>
                                            <small class="text-muted d-block"><?= number_format($po['total_items']) ?> SKU line(s)</small>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success fs-6">₹<?= number_format($po['total_amount'], 2) ?></span>
                                            <?php if (($po['discount_amount'] ?? 0) > 0): ?>
                                                <br><small class="text-danger">Disc: -₹<?= number_format($po['discount_amount'], 2) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= ($po['payment_status'] ?? 'PAID') === 'PAID' ? 'success' : (($po['payment_status'] ?? '') === 'PARTIAL' ? 'warning text-dark' : 'danger') ?>">
                                                <?= e($po['payment_status'] ?? 'PAID') ?>
                                            </span>
                                            <small class="text-muted d-block font-monospace"><?= e($po['payment_method'] ?? 'CASH') ?></small>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <?php if (!empty($po['customer_id'])): ?>
                                                    <button type="button" 
                                                            class="btn btn-outline-info" 
                                                            onclick="openCustomerProfileModal(<?= (int)$po['customer_id'] ?>, '<?= e(addslashes($po['display_customer_name'])) ?>')" 
                                                            title="View Customer Profile &amp; Bought Products">
                                                        <i class="bi bi-person-lines-fill"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canReceivePurchase): ?>
                                                    <a href="<?= url('warehouse/purchases/receive/' . $po['id']) ?>" target="_blank" class="btn btn-outline-primary" title="View Tax Invoice &amp; Receipt">
                                                        <i class="bi bi-printer me-1"></i> Invoice
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (hasPermission('purchases', 'edit')): ?>
                                                <a href="<?= url('warehouse/purchases/edit/' . $po['id']) ?>" class="btn btn-outline-secondary" title="Edit Purchase">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- CUSTOMER INVOICES QUICK VIEW MODAL -->
<!-- ========================================================================= -->
<div class="modal fade" id="customerInvoicesModal" tabindex="-1" aria-labelledby="customerInvoicesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold" id="customerInvoicesModalLabel">Customer Invoices</h5>
                    <div class="small text-muted" id="customerInvoicesModalCustomer"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2">
                <div id="customerInvoicesModalList" class="list-group list-group-flush"></div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- CUSTOMER PROFILE & PURCHASED PRODUCTS HISTORY MODAL -->
<!-- ========================================================================= -->
<div class="modal fade" id="customerProfileModal" tabindex="-1" aria-labelledby="customerProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header with Profile Card -->
            <div class="modal-header border-bottom-0 pb-0 text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="w-100 py-2">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div id="modalCustAvatar" class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center shadow" 
                                 style="width: 54px; height: 54px; font-size: 1.4rem; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: 2px solid rgba(255,255,255,0.2);">
                                C
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h4 class="mb-0 fw-bold text-white" id="modalCustName">Customer Name</h4>
                                    <span id="modalCustRepeatBadge" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small">
                                        <i class="bi bi-repeat me-1"></i>Regular Customer
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-3 text-white-50 small mt-1 flex-wrap" id="modalCustMetaChips">
                                    <span><i class="bi bi-telephone text-info me-1"></i><strong class="text-white" id="modalCustPhone">--</strong></span>
                                    <span><i class="bi bi-geo-alt text-warning me-1"></i><span id="modalCustLocation">--</span></span>
                                    <span id="modalCustGstSpan" class="d-none"><i class="bi bi-shield-check text-success me-1"></i>GSTIN: <span class="font-monospace text-white" id="modalCustGst">--</span></span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (hasPermission('purchases', 'create')): ?>
                            <a href="#" id="btnModalNewPurchase" class="btn btn-sm btn-primary fw-semibold shadow-sm">
                                <i class="bi bi-cart-plus me-1"></i> New Purchase for Customer
                            </a>
                            <?php endif; ?>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>

                    <!-- 3 Summary Stats Cards inside Header -->
                    <div class="row g-2 pt-2 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
                        <div class="col-12 col-md-4">
                            <div class="p-2.5 rounded-3" style="background: rgba(255, 255, 255, 0.06); backdrop-filter: blur(4px);">
                                <div class="text-white-50 small" style="font-size: 0.75rem;"><i class="bi bi-receipt me-1 text-primary"></i>Total Direct Orders</div>
                                <div class="h5 fw-bold text-white mb-0 mt-0.5" id="statCustTotalOrders">0</div>
                                <div class="text-white-50" style="font-size: 0.7rem;" id="statCustOrdersSub">Across all warehouses</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-2.5 rounded-3" style="background: rgba(255, 255, 255, 0.06); backdrop-filter: blur(4px);">
                                <div class="text-white-50 small" style="font-size: 0.75rem;"><i class="bi bi-box-seam me-1 text-info"></i>Total Garments Bought</div>
                                <div class="h5 fw-bold text-info mb-0 mt-0.5" id="statCustTotalUnits">0 pcs</div>
                                <div class="text-white-50" style="font-size: 0.7rem;" id="statCustProductsCount">0 distinct styles</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-2.5 rounded-3" style="background: rgba(255, 255, 255, 0.06); backdrop-filter: blur(4px);">
                                <div class="text-white-50 small" style="font-size: 0.75rem;"><i class="bi bi-currency-rupee me-1 text-success"></i>Total Lifetime Spend</div>
                                <div class="h5 fw-bold text-success mb-0 mt-0.5" id="statCustTotalSpend">₹0.00</div>
                                <div class="text-white-50" style="font-size: 0.7rem;" id="statCustAvgOrder">Avg ₹0.00 / order</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Nav Tabs -->
            <div class="bg-light px-4 pt-2 border-bottom">
                <ul class="nav nav-tabs border-0 customer-profile-tabs" id="customerProfileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold py-2.5 px-3 border-0 bg-transparent"
                                id="tab-products-btn" data-bs-target="#tab-products-pane" type="button" role="tab" aria-controls="tab-products-pane" aria-selected="true">
                            <i class="bi bi-bag-check me-1.5 text-primary"></i>Purchased Products Summary 
                            <span class="badge bg-primary-subtle text-primary rounded-pill ms-1" id="badgeTotalProductsCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-2.5 px-3 border-0 bg-transparent"
                                id="tab-orders-btn" data-bs-target="#tab-orders-pane" type="button" role="tab" aria-controls="tab-orders-pane" aria-selected="false">
                            <i class="bi bi-receipt-cutoff me-1.5 text-success"></i>Separate Purchase Invoices 
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1" id="badgeTotalOrdersCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-2.5 px-3 border-0 bg-transparent"
                                id="tab-details-btn" data-bs-target="#tab-details-pane" type="button" role="tab" aria-controls="tab-details-pane" aria-selected="false">
                            <i class="bi bi-person-lines-fill me-1.5 text-info"></i>Customer Information
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Modal Body Content -->
            <div class="modal-body p-4 bg-white" style="min-height: 380px;">
                <!-- Loading State -->
                <div id="modalCustLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <h6 class="text-muted fw-semibold">Loading Customer Purchase History &amp; Products...</h6>
                </div>

                <!-- Content Area -->
                <div id="modalCustContent" class="d-none">
                    <div class="tab-content" id="customerProfileTabContent">
                        
                        <!-- TAB 1: SEPARATELY PURCHASED PRODUCTS SUMMARY -->
                        <div class="tab-pane fade show active" id="tab-products-pane" role="tabpanel" aria-labelledby="tab-products-btn">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">
                                        <i class="bi bi-list-stars text-primary me-1"></i>All Garment Products Purchased by this Customer
                                    </h6>
                                    <small class="text-muted">Itemized breakdown of distinct styles, sizes, colors and total quantities bought across all direct purchase orders.</small>
                                </div>
                                <div class="w-auto" style="min-width: 250px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                        <input type="text" id="filterModalProductsInput" class="form-control border-start-0" placeholder="Filter style, SKU, size, color..." oninput="filterModalPurchasedProducts(this.value)">
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive border rounded-3">
                                <table class="table table-hover align-middle mb-0" id="tblModalPurchasedProducts">
                                    <thead class="table-light" style="font-size: 0.82rem;">
                                        <tr>
                                            <th class="ps-3">Product Name &amp; Style</th>
                                            <th>SKU Code</th>
                                            <th>Size &amp; Color</th>
                                            <th class="text-center">Total Bought</th>
                                            <th>Unit Rate / MRP</th>
                                            <th>Total Spend</th>
                                            <th class="pe-3">Invoices &amp; Scanned Piece QRs</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tblModalPurchasedProductsBody" style="font-size: 0.86rem;">
                                        <!-- Dynamically Populated -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="emptyModalProductsNotice" class="text-center py-4 d-none text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                                No matching products found for this customer.
                            </div>
                        </div>

                        <!-- TAB 2: SEPARATE PURCHASE ORDERS & TAX INVOICES -->
                        <div class="tab-pane fade" id="tab-orders-pane" role="tabpanel" aria-labelledby="tab-orders-btn">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">
                                        <i class="bi bi-receipt text-success me-1"></i>Direct Customer Separate Purchase Invoices
                                    </h6>
                                    <small class="text-muted">Each invoice is listed separately with individual line item breakdown and one-click print receipt.</small>
                                </div>
                            </div>

                            <div class="table-responsive border rounded-3">
                                <table class="table table-hover align-middle mb-0" id="tblModalOrdersHistory">
                                    <thead class="table-light" style="font-size: 0.82rem;">
                                        <tr>
                                            <th class="ps-3">Separate Invoice #</th>
                                            <th>Order Date</th>
                                            <th>Warehouse</th>
                                            <th>Garment Items</th>
                                            <th>Total Units</th>
                                            <th>Grand Total</th>
                                            <th>Payment Method &amp; Status</th>
                                            <th class="text-end pe-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tblModalOrdersHistoryBody" style="font-size: 0.86rem;">
                                        <!-- Dynamically Populated -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 3: CUSTOMER CONTACT & PROFILE INFORMATION -->
                        <div class="tab-pane fade" id="tab-details-pane" role="tabpanel" aria-labelledby="tab-details-btn">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border rounded-3 shadow-none h-100">
                                        <div class="card-header bg-light py-2 px-3 fw-bold small text-dark">
                                            <i class="bi bi-person-badge me-1.5 text-primary"></i>Customer Profile Info
                                        </div>
                                        <div class="card-body p-3">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td class="text-muted" style="width: 140px;">Customer Name:</td>
                                                    <td class="fw-bold text-dark" id="infoCustName">--</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Primary Phone:</td>
                                                    <td class="fw-semibold text-dark font-monospace" id="infoCustPhone">--</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Email Address:</td>
                                                    <td class="text-dark" id="infoCustEmail">--</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">GST Identification:</td>
                                                    <td class="fw-bold font-monospace text-primary" id="infoCustGst">--</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Customer Status:</td>
                                                    <td><span class="badge bg-success-subtle text-success border border-success-subtle" id="infoCustStatus">Regular Customer</span></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border rounded-3 shadow-none h-100">
                                        <div class="card-header bg-light py-2 px-3 fw-bold small text-dark">
                                            <i class="bi bi-geo-alt me-1.5 text-danger"></i>Billing &amp; Delivery Address
                                        </div>
                                        <div class="card-body p-3">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td class="text-muted" style="width: 140px;">Street Address:</td>
                                                    <td class="text-dark" id="infoCustAddress">--</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">City / Town:</td>
                                                    <td class="fw-semibold text-dark" id="infoCustCity">--</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">State / Region:</td>
                                                    <td class="text-dark" id="infoCustState">--</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Primary Warehouse:</td>
                                                    <td class="text-dark"><span class="badge bg-secondary" id="infoCustPrimaryWh">Central Warehouse</span></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light py-2 px-4 border-top d-flex justify-content-between">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>Viewing direct customer purchases &amp; garment items history.
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
                    <?php if (hasPermission('purchases', 'create')): ?>
                    <a href="#" id="btnFooterNewPurchase" class="btn btn-primary btn-sm px-3 fw-semibold">
                        <i class="bi bi-cart-plus me-1"></i> Create Direct Purchase
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.customer-hover-link:hover .text-decoration-underline-hover {
    text-decoration: underline !important;
}
.hover-shadow:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
}
#customerCategoryTabs .nav-link {
    transition: all 0.2s ease-in-out;
}
#customerCategoryTabs .nav-link.active {
    background: #4f46e5 !important;
    color: #ffffff !important;
    border-color: #4f46e5 !important;
}
.customer-profile-tabs .nav-link {
    border-bottom: 3px solid transparent !important;
    color: var(--bs-secondary-color);
    transition: color 0.2s ease, border-color 0.2s ease;
}
.customer-profile-tabs .nav-link.active {
    border-bottom-color: var(--bs-primary) !important;
    color: var(--bs-dark) !important;
}
</style>

<script>
let currentLoadedCustomer = null;
let currentLoadedProducts = [];
let currentLoadedOrders = [];

document.addEventListener('click', function(event) {
    const button = event.target.closest('#customerProfileTabs [data-bs-target]');
    if (!button) return;

    if (typeof bootstrap === 'undefined' || !bootstrap.Tab) {
        console.error('Bootstrap tabs are unavailable for the customer profile.');
        return;
    }

    bootstrap.Tab.getOrCreateInstance(button).show();
});

function openCustomerInvoicesModal(button) {
    const modalEl = document.getElementById('customerInvoicesModal');
    const listEl = document.getElementById('customerInvoicesModalList');
    const customerEl = document.getElementById('customerInvoicesModalCustomer');
    let invoices = [];

    try {
        invoices = JSON.parse(button.dataset.invoices || '[]');
        if (!Array.isArray(invoices)) throw new Error('Invoice list is not an array.');
    } catch (error) {
        console.error('Unable to read customer invoices:', error);
        listEl.innerHTML = '<div class="text-danger text-center small py-3">Unable to load this invoice list.</div>';
        customerEl.textContent = button.dataset.customerName || '';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        return;
    }

    customerEl.textContent = button.dataset.customerName || '';
    if (invoices.length === 0) {
        listEl.innerHTML = '<div class="text-muted text-center small py-3">No invoices found for this customer.</div>';
    } else {
        listEl.innerHTML = invoices.map(invoice => `
            <?php if ($canReceivePurchase): ?>
            <a href="<?= url('warehouse/purchases/receive/') ?>${encodeURIComponent(invoice.id)}"
               target="_blank" rel="noopener"
               class="list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-2 py-2">
                <span class="min-w-0">
                    <span class="d-block fw-semibold font-monospace text-primary small">${escapeHtml(invoice.po_number || 'Invoice')}</span>
                    <span class="d-block text-muted" style="font-size: 0.72rem;">${escapeHtml(formatDate(invoice.order_date))}</span>
                </span>
                <span class="badge rounded-pill bg-success-subtle text-success flex-shrink-0">₹${Number(invoice.total_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
            </a>
            <?php else: ?>
            <div class="list-group-item d-flex align-items-center justify-content-between gap-2 py-2">
                <span class="fw-semibold font-monospace text-primary small">${escapeHtml(invoice.po_number || 'Invoice')}</span>
                <span class="badge rounded-pill bg-success-subtle text-success flex-shrink-0">₹${Number(invoice.total_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
            </div>
            <?php endif; ?>
        `).join('');
    }

    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function openCustomerProfileModal(customerId, customerName = '') {
    if (!customerId) return;

    const modalEl = document.getElementById('customerProfileModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();

    // Reset view states
    document.getElementById('modalCustLoading').classList.remove('d-none');
    document.getElementById('modalCustContent').classList.add('d-none');

    // Reset tab to default Products
    const productsTabBtn = document.getElementById('tab-products-btn');
    if (productsTabBtn) {
        const tab = bootstrap.Tab.getInstance(productsTabBtn) || new bootstrap.Tab(productsTabBtn);
        tab.show();
    }

    // Set initial quick placeholders
    if (customerName) {
        document.getElementById('modalCustName').innerText = customerName;
        document.getElementById('modalCustAvatar').innerText = customerName.charAt(0).toUpperCase();
    }

    const apiUrl = '<?= url("api/warehouse/customers") ?>/' + customerId + '/purchase-history';

    fetch(apiUrl)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentLoadedCustomer = data.customer;
                currentLoadedProducts = data.products || [];
                currentLoadedOrders = data.orders || [];

                renderCustomerProfileData(data);

                document.getElementById('modalCustLoading').classList.add('d-none');
                document.getElementById('modalCustContent').classList.remove('d-none');
            } else {
                document.getElementById('modalCustLoading').innerHTML = `
                    <div class="text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                        <h6>${data.message || 'Unable to load customer purchase history.'}</h6>
                    </div>
                `;
            }
        })
        .catch(err => {
            console.error('Error fetching customer history:', err);
            document.getElementById('modalCustLoading').innerHTML = `
                <div class="text-danger py-4">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                    <h6>Failed to connect to server. Please try again.</h6>
                </div>
            `;
        });
}

function renderCustomerProfileData(data) {
    const cust = data.customer || {};
    const sum = data.summary || {};
    const products = data.products || [];
    const orders = data.orders || [];

    // Header Profile
    const cName = cust.name || 'Direct Customer';
    document.getElementById('modalCustName').innerText = cName;
    document.getElementById('modalCustAvatar').innerText = cName.charAt(0).toUpperCase();
    document.getElementById('modalCustPhone').innerText = cust.phone || 'No Mobile';
    
    let locationStr = cust.city || 'Local';
    if (cust.state) locationStr += ', ' + cust.state;
    document.getElementById('modalCustLocation').innerText = locationStr;

    if (cust.gst_number) {
        document.getElementById('modalCustGst').innerText = cust.gst_number;
        document.getElementById('modalCustGstSpan').classList.remove('d-none');
    } else {
        document.getElementById('modalCustGstSpan').classList.add('d-none');
    }

    // Repeat badge
    const isRepeat = sum.is_repeat_customer || orders.length > 1;
    const repeatBadge = document.getElementById('modalCustRepeatBadge');
    if (isRepeat) {
        repeatBadge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small fw-semibold';
        repeatBadge.innerHTML = `<i class="bi bi-repeat me-1"></i>Regular Customer (${sum.total_orders} Orders)`;
    } else {
        repeatBadge.className = 'badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 py-1 small';
        repeatBadge.innerHTML = `<i class="bi bi-person-check me-1"></i>One-Time Customer`;
    }

    // New Purchase buttons
    const newPurchaseUrl = '<?= url("warehouse/purchases/create") ?>?customer_id=' + (cust.id || '');
    const btnM = document.getElementById('btnModalNewPurchase');
    if (btnM) btnM.href = newPurchaseUrl;
    const btnF = document.getElementById('btnFooterNewPurchase');
    if (btnF) btnF.href = newPurchaseUrl;

    // Stat cards
    document.getElementById('statCustTotalOrders').innerText = (sum.total_orders || 0) + ' Orders';
    document.getElementById('statCustOrdersSub').innerText = sum.last_order_date ? 'Latest: ' + formatDate(sum.last_order_date) : 'Across all warehouses';

    document.getElementById('statCustTotalUnits').innerText = (sum.total_pieces || 0) + ' pcs';
    document.getElementById('statCustProductsCount').innerText = (sum.distinct_products_count || products.length) + ' unique garment styles';

    document.getElementById('statCustTotalSpend').innerText = '₹' + Number(sum.total_spend || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('statCustAvgOrder').innerText = 'Avg ₹' + Number(sum.avg_order_value || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' / order';

    const paidAmt = Number(sum.paid_spend || 0).toLocaleString('en-IN', {minimumFractionDigits: 0});
    const pendAmt = Number(sum.pending_spend || 0).toLocaleString('en-IN', {minimumFractionDigits: 0});
    const elPayStat = document.getElementById('statCustPaymentStatus');
    if (elPayStat) {
        elPayStat.innerHTML = `<span class="text-success">₹${paidAmt}</span> <small class="text-white-50 fs-7">/ ₹${pendAmt} pend</small>`;
    }
    const elPayMeth = document.getElementById('statCustPayMethods');
    if (elPayMeth) {
        elPayMeth.innerText = (sum.payment_methods && sum.payment_methods.length > 0) ? sum.payment_methods.join(', ') : 'CASH';
    }

    // Badges on tabs
    document.getElementById('badgeTotalProductsCount').innerText = products.length;
    document.getElementById('badgeTotalOrdersCount').innerText = orders.length;

    // Populate Tab 1: Products
    renderPurchasedProductsTable(products);

    // Populate Tab 2: Orders
    renderOrdersHistoryTable(orders);

    // Populate Tab 3: Customer Details
    document.getElementById('infoCustName').innerText = cust.name || '--';
    document.getElementById('infoCustPhone').innerText = cust.phone || '--';
    document.getElementById('infoCustEmail').innerText = cust.email || 'None';
    document.getElementById('infoCustGst').innerText = cust.gst_number || 'Unregistered / Consumer';
    document.getElementById('infoCustAddress').innerText = cust.address || 'Direct counter billing';
    document.getElementById('infoCustCity').innerText = cust.city || 'Local';
    document.getElementById('infoCustState').innerText = cust.state || 'Tamil Nadu';
    document.getElementById('infoCustPrimaryWh').innerText = sum.primary_warehouse || 'Central Distribution Warehouse';
}

function renderPurchasedProductsTable(products) {
    const tbody = document.getElementById('tblModalPurchasedProductsBody');
    const emptyNotice = document.getElementById('emptyModalProductsNotice');
    tbody.innerHTML = '';

    if (!products || products.length === 0) {
        emptyNotice.classList.remove('d-none');
        return;
    }
    emptyNotice.classList.add('d-none');

    products.forEach((p) => {
        const tr = document.createElement('tr');
        
        // Combined Invoices & Scanned Piece QRs (Placed near each order)
        let ordersAndQrsHtml = '';
        if (p.orders && p.orders.length > 0) {
            ordersAndQrsHtml = p.orders.map(ord => {
                let cleanQr = (ord.qr_code || '').replace(/^QR:\s*/i, '').trim();
                let qrPills = '';
                if (cleanQr && cleanQr !== 'Standard SKU') {
                    let qrList = cleanQr.split(',').map(s => s.trim()).filter(Boolean);
                    qrPills = qrList.map(q => `
                        <span class="badge bg-dark-subtle text-dark border font-monospace" style="font-size:0.71rem;" title="Scanned Piece QR: ${escapeHtml(q)}">
                            <i class="bi bi-qr-code text-primary me-0.5"></i>${escapeHtml(q)}
                        </span>
                    `).join(' ');
                } else {
                    qrPills = '<span class="badge bg-light text-muted border font-monospace" style="font-size:0.7rem;">Std SKU</span>';
                }

                return `
                    <div class="d-flex align-items-center gap-1.5 flex-wrap py-0.5">
                        <?php if ($canReceivePurchase): ?>
                        <a href="<?= url('warehouse/purchases/receive/') ?>${ord.po_id}" target="_blank" 
                           class="badge bg-light text-primary border text-decoration-none font-monospace py-1 px-1.5" 
                           title="View Separate Invoice for Order Date: ${formatDate(ord.order_date)}">
                            <i class="bi bi-file-earmark-text me-0.5"></i>${escapeHtml(ord.po_number)} <span class="text-secondary">(${ord.quantity} pcs)</span>
                        </a>
                        <?php else: ?>
                        <span class="badge bg-light text-primary border font-monospace py-1 px-1.5">${escapeHtml(ord.po_number)} <span class="text-secondary">(${ord.quantity} pcs)</span></span>
                        <?php endif; ?>
                        ${qrPills}
                    </div>
                `;
            }).join('');
        } else {
            ordersAndQrsHtml = '<span class="text-muted small">--</span>';
        }

        tr.innerHTML = `
            <td class="ps-3">
                <div class="fw-bold text-dark">${escapeHtml(p.product_name)}</div>
                <div class="d-flex align-items-center gap-1.5 mt-0.5">
                    <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.7rem;">${escapeHtml(p.category_name || 'Garments')}</span>
                    <small class="text-muted font-monospace">${escapeHtml(p.product_code || '')}</small>
                </div>
            </td>
            <td>
                <span class="font-monospace fw-semibold text-primary" style="font-size: 0.8rem;">${escapeHtml(p.sku)}</span>
            </td>
            <td>
                <div class="d-flex align-items-center gap-1.5">
                    <span class="badge bg-primary text-white font-monospace px-1.5 py-0.5" style="font-size: 0.75rem;">${escapeHtml(p.size_name || 'Std')}</span>
                    <span class="badge bg-light text-dark border px-1.5 py-0.5 d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                        <span class="d-inline-block rounded-circle border" style="width: 8px; height: 8px; background-color: ${escapeHtml(p.color_hex || '#ccc')};"></span>
                        ${escapeHtml(p.color_name || 'Std')}
                    </span>
                </div>
            </td>
            <td class="text-center">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-2.5 py-1 fw-bold">
                    ${p.total_quantity} pcs
                </span>
                <small class="text-muted d-block mt-0.5">${p.times_ordered} order(s)</small>
            </td>
            <td>
                <div class="fw-bold text-dark">₹${Number(p.unit_price).toFixed(2)}</div>
                ${p.mrp > p.unit_price ? `<small class="text-muted text-decoration-line-through">₹${Number(p.mrp).toFixed(2)}</small>` : ''}
            </td>
            <td>
                <span class="fw-bold text-success fs-6">₹${Number(p.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
            </td>
            <td class="pe-3">
                <div class="d-flex flex-column gap-1" style="min-width: 200px;">
                    ${ordersAndQrsHtml}
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function renderOrdersHistoryTable(orders) {
    const tbody = document.getElementById('tblModalOrdersHistoryBody');
    tbody.innerHTML = '';

    if (!orders || orders.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    No purchase orders recorded for this customer.
                </td>
            </tr>
        `;
        return;
    }

    orders.forEach((ord) => {
        const tr = document.createElement('tr');
        
        let payBadgeClass = ord.payment_status === 'PAID' ? 'bg-success' : (ord.payment_status === 'PARTIAL' ? 'bg-warning text-dark' : 'bg-danger');

        // Items preview
        let itemsListHtml = '';
        if (ord.items && ord.items.length > 0) {
            itemsListHtml = ord.items.map(it => `
                <div class="small text-muted d-flex justify-content-between py-0.5 border-bottom border-light">
                    <span>• <strong>${escapeHtml(it.product_name)}</strong> (${escapeHtml(it.size_name || 'Std')} / ${escapeHtml(it.color_name || 'Std')})</span>
                    <span class="fw-bold text-dark font-monospace">${it.quantity} pcs @ ₹${Number(it.unit_price).toFixed(2)}</span>
                </div>
            `).join('');
        }

        tr.innerHTML = `
            <td class="ps-3">
                <span class="fw-bold font-monospace text-primary">${escapeHtml(ord.po_number)}</span>
            </td>
            <td>
                <small class="text-dark fw-semibold">${formatDate(ord.order_date)}</small>
            </td>
            <td>
                <span class="badge bg-secondary-subtle text-secondary border">${escapeHtml(ord.warehouse_name || 'Central WH')}</span>
            </td>
            <td>
                <div style="max-width: 260px;">
                    ${itemsListHtml}
                </div>
            </td>
            <td>
                <span class="badge bg-light text-dark border fw-bold">${ord.total_ordered_qty || 0} pcs</span>
            </td>
            <td>
                <span class="fw-bold text-success fs-6">₹${Number(ord.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
            </td>
            <td>
                <span class="badge ${payBadgeClass}">${escapeHtml(ord.payment_status || 'PAID')}</span>
                <small class="text-muted d-block font-monospace mt-0.5">${escapeHtml(ord.payment_method || 'CASH')}</small>
            </td>
            <td class="text-end pe-3">
                <div class="btn-group btn-group-sm">
                    <?php if ($canReceivePurchase): ?>
                        <a href="<?= url('warehouse/purchases/receive/') ?>${ord.id}" target="_blank" class="btn btn-outline-primary" title="Print Separate Tax Invoice">
                            <i class="bi bi-printer me-1"></i> Invoice
                        </a>
                    <?php endif; ?>
                    <?php if (hasPermission('purchases', 'edit')): ?>
                    <a href="<?= url('warehouse/purchases/edit/') ?>${ord.id}" class="btn btn-outline-secondary" title="Edit Purchase">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function filterModalPurchasedProducts(term) {
    term = term.trim().toLowerCase();
    if (!currentLoadedProducts) return;

    if (term === '') {
        renderPurchasedProductsTable(currentLoadedProducts);
        return;
    }

    const filtered = currentLoadedProducts.filter(p => {
        const pName = (p.product_name || '').toLowerCase();
        const sku = (p.sku || '').toLowerCase();
        const pCode = (p.product_code || '').toLowerCase();
        const cat = (p.category_name || '').toLowerCase();
        const size = (p.size_name || '').toLowerCase();
        const color = (p.color_name || '').toLowerCase();
        const qrs = (p.qr_codes || []).join(' ').toLowerCase();

        return pName.includes(term) || sku.includes(term) || pCode.includes(term) ||
               cat.includes(term) || size.includes(term) || color.includes(term) || qrs.includes(term);
    });

    renderPurchasedProductsTable(filtered);
}

function formatDate(dateStr) {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${d.getDate().toString().padStart(2, '0')} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// =========================================================================
// AUTOMATIC INSTANT FILTERING (WITHOUT MANUAL BUTTON TRIGGER)
// =========================================================================
let searchDebounceTimer = null;

function autoTriggerFilter() {
    const form = document.getElementById('purchaseFilterForm');
    if (form) {
        form.submit();
    }
}

function resetPurchaseFilters() {
    clearTimeout(searchDebounceTimer);
    sessionStorage.removeItem('purchases_search_focused');
    window.location.assign('<?= url("warehouse/purchases") ?>');
}

function clearSearchFilter() {
    const input = document.getElementById('searchFilterInput');
    if (input) {
        input.value = '';
        autoTriggerFilter();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchFilterInput');
    const form = document.getElementById('purchaseFilterForm');
    const indicator = document.getElementById('liveSearchIndicator');

    if (searchInput) {
        // Restore focus if user was actively searching before reload
        if (sessionStorage.getItem('purchases_search_focused') === '1') {
            const len = searchInput.value.length;
            searchInput.focus();
            searchInput.setSelectionRange(len, len);
            sessionStorage.removeItem('purchases_search_focused');
        }

        searchInput.addEventListener('input', function() {
            const val = this.value.trim().toLowerCase();

            // 1. Instant real-time row filtering across all tabs (0ms instant response)
            filterTableRowsLive(val);

            // 2. Debounce auto-submit (600ms) for database search sync
            if (indicator) indicator.classList.remove('d-none');
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(function() {
                sessionStorage.setItem('purchases_search_focused', '1');
                if (form) form.submit();
            }, 600);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchDebounceTimer);
                sessionStorage.setItem('purchases_search_focused', '1');
                if (form) form.submit();
            }
        });
    }
});

function filterTableRowsLive(query) {
    const tableBodies = document.querySelectorAll('#tab-regular-pane tbody, #tab-onetime-pane tbody, #tab-all-pane tbody');
    tableBodies.forEach(tbody => {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            if (!query) {
                row.style.display = '';
                return;
            }
            const text = (row.textContent || row.innerText || '').toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
}
</script>
