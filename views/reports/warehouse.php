<?php
// views/reports/warehouse.php
// Warehouse Logistics, Dispatches, Damaged Stock & Adjustments Report
$reportType = 'warehouse';
$totals = $warehouseData['totals'] ?? [];
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold text-dark">
            <i class="bi bi-buildings-fill text-primary me-2"></i>Warehouse Logistics &amp; Damage Reports
        </h4>
        <div class="text-muted small mt-1">Retail store allocations, transit shipments, damaged items write-offs, and stock adjustments</div>
    </div>
</div>

<?php require __DIR__ . '/partials/filter_bar.php'; ?>

<!-- ========================================================================= -->
<!-- 1. WAREHOUSE KPI SCOREBOARD -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Total Dispatches to Retail -->
    <div class="col-xl-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Store Allocations / Dispatched</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= number_format($totals['dispatched_qty'] ?? 0) ?> pcs</div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-truck me-1"></i><?= count($warehouseData['allocations'] ?? []) ?> Dispatch Shipments
                    </div>
                </div>
                <div class="bg-primary text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-box-arrow-up-right fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Damaged Pieces -->
    <div class="col-xl-4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-left: 4px solid #dc2626 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-danger" style="font-size: 0.72rem; letter-spacing: 0.5px;">Damaged Goods Count</div>
                    <div class="fs-4 fw-bold text-danger mt-1 font-monospace"><?= number_format($totals['damaged_qty'] ?? 0) ?> pcs</div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i><?= count($warehouseData['damages'] ?? []) ?> Damage Incidents
                    </div>
                </div>
                <div class="bg-danger text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-trash3 fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Damage Loss in Rupees -->
    <div class="col-xl-4 col-sm-12">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border-left: 4px solid #e11d48 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-danger" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Damage Loss (INR)</div>
                    <div class="fs-4 fw-bold text-danger mt-1 font-monospace">₹<?= number_format($totals['damage_loss'] ?? 0, 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-file-earmark-x me-1"></i>Direct Inventory Write-Off
                    </div>
                </div>
                <div class="bg-danger text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-currency-rupee fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. WAREHOUSE FACILITY FILTER BAR -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="bi bi-building me-1 text-primary"></i>Filter by Warehouse:
                </span>
                <a href="<?= url('reports/warehouse') ?>?filter_type=<?= urlencode($filter['filter_type']) ?>&warehouse_id=0&year=<?= $filter['year'] ?>&month=<?= $filter['month'] ?>" 
                   class="btn btn-sm <?= empty($warehouseId) ? 'btn-primary' : 'btn-light border' ?>">
                   All Warehouses
                </a>
                <?php foreach ($warehouses as $wh): ?>
                    <a href="<?= url('reports/warehouse') ?>?filter_type=<?= urlencode($filter['filter_type']) ?>&warehouse_id=<?= $wh['id'] ?>&year=<?= $filter['year'] ?>&month=<?= $filter['month'] ?>" 
                       class="btn btn-sm <?= ($warehouseId == $wh['id']) ? 'btn-primary' : 'btn-light border' ?>">
                       <?= e($wh['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. DETAILED WAREHOUSE LOGISTICS AUDIT TABS -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-0 border-bottom">
        <ul class="nav nav-pills card-header-pills" id="whReportTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-semibold" id="alloc-tab" data-bs-toggle="tab" data-bs-target="#alloc-content" type="button" role="tab">
                    <i class="bi bi-truck me-1 text-primary"></i>Store Dispatches &amp; Allocations (<?= count($warehouseData['allocations']) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="damage-tab" data-bs-toggle="tab" data-bs-target="#damage-content" type="button" role="tab">
                    <i class="bi bi-trash3 me-1 text-danger"></i>Damage Incidents &amp; Losses (<?= count($warehouseData['damages']) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="adj-tab" data-bs-toggle="tab" data-bs-target="#adj-content" type="button" role="tab">
                    <i class="bi bi-sliders me-1 text-warning"></i>Stock Adjustments &amp; Audits (<?= count($warehouseData['adjustments']) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases-content" type="button" role="tab">
                    <i class="bi bi-cart-check me-1 text-success"></i>Third-Party Purchases (<?= count($warehouseData['third_party_buys'] ?? []) ?>)
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content" id="whReportTabsContent">
            <!-- 1. DISPATCHES TAB -->
            <div class="tab-pane fade show active" id="alloc-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Allocation / Dispatch #</th>
                                <th>Source Warehouse</th>
                                <th>Destination Shop</th>
                                <th class="text-end">Items Count</th>
                                <th class="text-end">Total Dispatched Qty</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Dispatch Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($warehouseData['allocations'])): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No dispatch allocations recorded in this date range.</td></tr>
                            <?php else: ?>
                                <?php foreach ($warehouseData['allocations'] as $al): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-primary"><?= e($al['allocation_number']) ?></td>
                                        <td><?= e($al['warehouse_name'] ?? 'Main Warehouse') ?></td>
                                        <td class="fw-semibold text-dark"><i class="bi bi-shop me-1 text-muted"></i><?= e($al['shop_name'] ?? 'Retail Outlet') ?></td>
                                        <td class="text-end font-monospace"><?= number_format($al['item_count']) ?> lines</td>
                                        <td class="text-end font-monospace fw-bold text-primary"><?= number_format($al['total_qty']) ?> pcs</td>
                                        <td>
                                            <span class="badge <?= $al['status'] === 'RECEIVED' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                <?= e($al['status']) ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-end small text-muted font-monospace"><?= date('d M Y, H:i', strtotime($al['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. DAMAGES TAB -->
            <div class="tab-pane fade" id="damage-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Damage Ref #</th>
                                <th>Warehouse</th>
                                <th>Product Name &amp; SKU</th>
                                <th class="text-end">Damaged Qty</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Total Loss (INR)</th>
                                <th>Damage Reason</th>
                                <th class="pe-4 text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($warehouseData['damages'])): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No damaged inventory recorded in this date range.</td></tr>
                            <?php else: ?>
                                <?php foreach ($warehouseData['damages'] as $dm): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-danger"><?= e($dm['damage_number']) ?></td>
                                        <td><?= e($dm['warehouse_name'] ?? 'Main Warehouse') ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($dm['product_name']) ?></div>
                                            <div class="small text-muted font-monospace"><?= e($dm['sku']) ?></div>
                                        </td>
                                        <td class="text-end font-monospace text-danger fw-bold"><?= number_format($dm['quantity']) ?> pcs</td>
                                        <td class="text-end font-monospace text-muted">₹<?= number_format($dm['cost_per_unit'], 2) ?></td>
                                        <td class="text-end font-monospace fw-bold text-danger">₹<?= number_format($dm['total_loss'], 2) ?></td>
                                        <td class="small text-muted"><?= e($dm['damage_reason'] ?: 'Damaged in transit / warehouse handling') ?></td>
                                        <td class="pe-4 text-end small text-muted"><?= date('d M Y', strtotime($dm['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. ADJUSTMENTS TAB -->
            <div class="tab-pane fade" id="adj-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Adjustment #</th>
                                <th>Warehouse</th>
                                <th>Product / SKU</th>
                                <th>Adjustment Type</th>
                                <th class="text-end">Quantity</th>
                                <th>Reason / Note</th>
                                <th class="pe-4 text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($warehouseData['adjustments'])): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No stock adjustments recorded.</td></tr>
                            <?php else: ?>
                                <?php foreach ($warehouseData['adjustments'] as $aj): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-warning-emphasis"><?= e($aj['adjustment_number'] ?? ('ADJ-'.$aj['id'])) ?></td>
                                        <td><?= e($aj['warehouse_name'] ?? 'Warehouse') ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($aj['product_name'] ?? 'Item') ?></div>
                                            <div class="small text-muted font-monospace"><?= e($aj['sku'] ?? '-') ?></div>
                                        </td>
                                        <td>
                                            <span class="badge <?= ($aj['type'] ?? '') === 'ADD' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                                <?= e($aj['type'] ?? 'ADJUST') ?>
                                            </span>
                                        </td>
                                        <td class="text-end font-monospace fw-bold"><?= number_format($aj['quantity'] ?? 0) ?></td>
                                        <td class="small text-muted"><?= e($aj['reason'] ?? 'Physical stock count adjustment') ?></td>
                                        <td class="pe-4 text-end small text-muted"><?= date('d M Y', strtotime($aj['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. THIRD-PARTY PURCHASES TAB -->
            <div class="tab-pane fade" id="purchases-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">PO #</th>
                                <th>Warehouse</th>
                                <th>Supplier / Vendor</th>
                                <th class="text-end">Items Count</th>
                                <th class="text-end">Total Qty</th>
                                <th class="text-end">Total Amount (INR)</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($warehouseData['third_party_buys'])): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No third-party purchases recorded in this date range.</td></tr>
                            <?php else: ?>
                                <?php foreach ($warehouseData['third_party_buys'] as $buy): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-success"><?= e($buy['po_number'] ?? $buy['order_number'] ?? '') ?></td>
                                        <td><?= e($buy['warehouse_name'] ?? 'Main Warehouse') ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($buy['supplier_name']) ?></div>
                                            <div class="small text-muted font-monospace"><?= e($buy['supplier_company']) ?></div>
                                        </td>
                                        <td class="text-end font-monospace"><?= number_format($buy['item_count']) ?> lines</td>
                                        <td class="text-end font-monospace fw-bold text-success"><?= number_format($buy['total_qty']) ?> pcs</td>
                                        <td class="text-end font-monospace fw-bold text-dark">₹<?= number_format($buy['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge <?= $buy['status'] === 'RECEIVED' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                <?= e($buy['status']) ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-end small text-muted font-monospace"><?= date('d M Y, H:i', strtotime($buy['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
