<?php
$canInward = hasPermission('shop_inward', 'verify') || hasPermission('shop_inward', 'receive') || hasPermission('shop_inward', 'create');
$hasMultipleShops = count($shops ?? []) > 1;
?>
<style>
.custom-inward-pills .nav-link {
    border-radius: 8px;
    padding: 0.5rem 1.1rem;
    font-weight: 600;
    font-size: 0.875rem;
    color: #4b5563;
    border: 1px solid #e5e7eb;
    background-color: #fff;
    white-space: nowrap;
}
.custom-inward-pills .nav-link.active {
    background-color: #2563eb;
    color: #fff;
    border-color: #2563eb;
}
.custom-inward-pills .nav-link.active .badge {
    background-color: #fff !important;
    color: #2563eb !important;
}
.batch-card-hover {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.batch-card-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1) !important;
}
.variant-scroll-box {
    max-height: 110px;
    overflow-y: auto;
    scrollbar-width: thin;
}
.source-badge-box {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    border-radius: 6px;
}
.dest-badge-box {
    background: #eff6ff;
    border: 1px solid #dbeafe;
    border-radius: 6px;
}
.fs-7 { font-size: 0.825rem; }
.fs-8 { font-size: 0.75rem; }
@media (max-width: 1200px) {
    .metric-card-title { font-size: 0.72rem !important; }
    .metric-card-value { font-size: 1.35rem !important; }
}
</style>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1 fs-4 fw-bold"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Shop Inward / GRN</h2>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('shop/dashboard') ?>" class="btn btn-sm btn-outline-secondary px-3">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. SUMMARY METRIC CARDS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block text-truncate metric-card-title">Awaiting Shop Inward (Missing Stock)</span>
                        <h4 class="mb-0 fw-bold text-danger mt-1 metric-card-value">
                            <?= number_format($pendingInwardsCount ?? 0) ?> <small class="fs-6 text-muted">Batches</small>
                        </h4>
                        <small class="text-danger fw-semibold mt-1 d-block text-truncate">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i><?= number_format($totalPendingMissingQty ?? 0) ?> pcs pending verification
                        </small>
                    </div>
                    <div class="rounded-circle bg-danger bg-opacity-10 p-2 p-xl-3 text-danger flex-shrink-0 ms-2">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block text-truncate metric-card-title">Received / Processed</span>
                        <h4 class="mb-0 fw-bold text-success mt-1 metric-card-value">
                            <?= number_format($receivedInwardsCount ?? 0) ?> <small class="fs-6 text-muted">Batches</small>
                        </h4>
                        <small class="text-success fw-semibold mt-1 d-block text-truncate">
                            <i class="bi bi-check2-circle me-1"></i><?= number_format($totalReceivedQty ?? 0) ?> pcs in shop inventory
                        </small>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 p-2 p-xl-3 text-success flex-shrink-0 ms-2">
                        <i class="bi bi-shield-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block text-truncate metric-card-title">Damaged / Quarantined</span>
                        <h4 class="mb-0 fw-bold text-warning mt-1 metric-card-value">
                            <?= number_format($totalDamagedQty ?? 0) ?> <small class="fs-6 text-muted">pcs</small>
                        </h4>
                        <small class="text-muted fw-semibold mt-1 d-block text-truncate">
                            <i class="bi bi-shield-exclamation me-1"></i>Not included in sellable shop stock
                        </small>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 p-2 p-xl-3 text-warning flex-shrink-0 ms-2">
                        <i class="bi bi-shield-exclamation fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block text-truncate metric-card-title">Total Inward Dispatches</span>
                        <h4 class="mb-0 fw-bold text-primary mt-1 metric-card-value">
                            <?= number_format(count($allocations ?? [])) ?> <small class="fs-6 text-muted">Batches</small>
                        </h4>
                        <small class="text-muted mt-1 d-block text-truncate">
                            <i class="bi bi-truck me-1"></i><?= number_format($totalDispatchedQty ?? 0) ?> total dispatched pcs
                        </small>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 p-xl-3 text-primary flex-shrink-0 ms-2">
                        <i class="bi bi-box-seam fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- VIEW MODE SELECTION (LIST VIEW / ALLOCATION CARDS) -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-2 p-md-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-bold text-dark small text-uppercase d-none d-sm-inline"><i class="bi bi-layout-split me-1 text-primary"></i>View Mode:</span>
                <ul class="nav nav-pills custom-inward-pills" id="inwardViewTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active d-flex align-items-center gap-2" id="tab-list-btn" data-bs-toggle="pill" data-bs-target="#tab-list-view" type="button" role="tab" aria-controls="tab-list-view" aria-selected="true">
                            <i class="bi bi-list-task fs-6"></i><span>List / Ledger View</span>
                            <span class="badge bg-secondary"><?= count($allocations ?? []) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center gap-2" id="tab-cards-btn" data-bs-toggle="pill" data-bs-target="#tab-cards-view" type="button" role="tab" aria-controls="tab-cards-view" aria-selected="false">
                            <i class="bi bi-grid-fill fs-6"></i><span>Batch Cards View</span>
                            <?php if (($pendingInwardsCount ?? 0) > 0): ?>
                                <span class="badge bg-danger"><?= (int)$pendingInwardsCount ?> Missing</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= count($allocations ?? []) ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="tab-content" id="inwardViewTabsContent">
    <div class="tab-pane fade" id="tab-cards-view" role="tabpanel" aria-labelledby="tab-cards-btn">
<div class="card border-0 shadow-sm mb-4 border-start border-danger border-4">
    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="card-title mb-0 fw-bold text-danger">
                <i class="bi bi-grid-fill me-2"></i>Allocation-Wise Missing Stock &amp; Variant Overview
            </h6>
            <small class="text-muted fs-8">Interactive allocation cards with product variants and source warehouse details</small>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="batchCardSearch" class="form-control" placeholder="Search variant / SKU...">
            </div>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active btn-batch-filter" data-filter="all">All (<?= count($allocations ?? []) ?>)</button>
                <button type="button" class="btn btn-outline-danger btn-batch-filter" data-filter="missing">Missing Only (<?= $pendingInwardsCount ?? 0 ?>)</button>
                <button type="button" class="btn btn-outline-success btn-batch-filter" data-filter="stocked">Stocked (<?= $stockedInwardsCount ?? 0 ?>)</button>
            </div>
        </div>
    </div>
    <div class="card-body p-3 bg-light">
        <?php if (empty($allocations)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-1"></i> No allocation records available for this shop.
            </div>
        <?php else: ?>
            <div class="row g-3" id="batchCardsContainer">
                <?php foreach ($allocations as $a): ?>
                    <?php 
                        $totalCredited = (int)($a['total_received_items_qty'] ?? 0);
                        $missingQty = (int)($a['missing_qty'] ?? max(0, (int)$a['effective_qty'] - $totalCredited));
                        $isFullyReceived = in_array($a['status'], ['RECEIVED', 'COMPLETED', 'DELIVERED'], true) && $missingQty === 0;
                        $isPartiallyReceived = $a['status'] === 'PARTIAL_RECEIVED' || $missingQty > 0;
                        $itemsList = $a['items'] ?? [];
                        $skuSearchTerms = implode(' ', array_map(function($i) { return ($i['sku'] ?? '') . ' ' . ($i['size_name'] ?? '') . ' ' . ($i['color_name'] ?? '') . ' ' . ($i['product_name'] ?? ''); }, $itemsList));
                        $primaryProduct = $itemsList[0]['product_name'] ?? 'Assorted Apparels';
                    ?>
                    <div class="col-12 col-md-6 col-lg-6 col-xl-4 col-xxl-3 batch-card-item"
                         data-alloc-id="<?= (int)$a['id'] ?>"
                         data-status="<?= $missingQty > 0 ? 'missing' : 'stocked' ?>"
                         data-has-missing="<?= $missingQty > 0 ? '1' : '0' ?>"
                         data-has-stock="<?= (int)($a['total_good_items_qty'] ?? 0) > 0 ? '1' : '0' ?>"
                         data-inward-status="<?= e($a['status']) ?>"
                         data-warehouse-id="<?= (int)($a['warehouse_id'] ?? 0) ?>"
                         data-search="<?= strtolower(e($a['allocation_number'] . ' ' . ($a['outward_number'] ?? '') . ' ' . ($a['vehicle_number'] ?? '') . ' ' . ($a['driver_name'] ?? '') . ' ' . $a['warehouse_name'] . ' ' . $skuSearchTerms)) ?>">
                        <div class="card border shadow-sm h-100 batch-card-hover <?= $missingQty > 0 ? ($isPartiallyReceived ? 'border-warning border-opacity-75' : 'border-danger border-opacity-50') : 'border-success border-opacity-50' ?>">
                            <!-- Card Header -->
                            <div class="card-header <?= $isFullyReceived ? 'bg-success bg-opacity-10 border-success border-opacity-25' : ($isPartiallyReceived ? 'bg-warning bg-opacity-15 border-warning border-opacity-25' : 'bg-danger bg-opacity-10 border-danger border-opacity-25') ?> py-2 px-3 d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="fw-bold font-monospace text-dark fs-7"><?= e($a['allocation_number']) ?></span>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none btn-show-qr ms-1" data-code="<?= e($a['allocation_number']) ?>" data-product="<?= e($primaryProduct) ?>" title="Show QR">
                                        <i class="bi bi-qr-code text-primary"></i>
                                    </button>
                                </div>
                                <?php if ($isFullyReceived): ?>
                                    <span class="badge bg-success rounded-pill px-2 py-1 fs-8">
                                        <i class="bi bi-check-circle-fill me-1"></i>Fully Received
                                    </span>
                                <?php elseif ($isPartiallyReceived): ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-2 py-1 fs-8">
                                        <i class="bi bi-pie-chart me-1"></i>Partial (<?= $totalCredited ?> / <?= (int)$a['effective_qty'] ?> pcs)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger rounded-pill px-2 py-1 fs-8">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i><?= number_format($missingQty) ?> pcs Missing
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Card Body -->
                            <div class="card-body p-3 bg-white">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 200px;" title="<?= e($primaryProduct) ?>">
                                            <?= e($primaryProduct) ?><?= count($itemsList) > 1 ? ' +' . (count($itemsList) - 1) . ' more' : '' ?>
                                        </h6>
                                        <small class="text-muted d-block">From: <span class="badge bg-light text-dark border"><?= e($a['warehouse_name']) ?></span></small>
                                    </div>
                                    <span class="badge bg-light text-dark border font-monospace fs-7">
                                        <?= number_format($a['effective_qty']) ?> pcs
                                    </span>
                                </div>

                                <!-- Variant Breakdown Box -->
                                <div class="p-2 rounded bg-light border mb-2" style="max-height: 120px; overflow-y: auto;">
                                    <small class="text-muted fw-semibold d-block mb-1 fs-8 text-uppercase">
                                        <i class="bi bi-tags-fill me-1 text-secondary"></i>Items Breakdown (<?= count($itemsList) ?>):
                                    </small>
                                    <?php if (!empty($itemsList)): ?>
                                        <?php foreach ($itemsList as $v): ?>
                                            <?php
                                                $vDispatched = (int)($v['dispatched_qty'] > 0 ? $v['dispatched_qty'] : $v['requested_qty']);
                                                $vRecv = (int)($v['received_qty'] ?? 0);
                                                $vDam = (int)($v['damaged_qty'] ?? 0);
                                                $vGood = max(0, $vRecv - $vDam);
                                                $vMissing = max(0, $vDispatched - $vRecv);
                                            ?>
                                            <div class="d-flex justify-content-between align-items-center bg-white px-2 py-1 rounded border mb-1 fs-8">
                                                <span class="font-monospace text-truncate" style="max-width: 170px;" title="<?= e($v['sku']) ?>">
                                                    <strong><?= e($v['sku']) ?></strong> (<?= e($v['size_name'] ?? 'Std') ?>, <?= e($v['color_name'] ?? 'Std') ?>)
                                                </span>
                                                <span class="d-flex align-items-center gap-1 font-monospace flex-shrink-0">
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle" title="Good stock"><?= $vGood ?></span>
                                                    <span class="badge <?= $vMissing > 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-muted border' ?>" title="Missing"><?= $vMissing ?></span>
                                                    <?php if ($vDam > 0): ?><span class="badge bg-danger text-white" title="Damaged"><?= $vDam ?></span><?php endif; ?>
                                                    <span class="badge bg-light text-dark border" title="Expected">/ <?= $vDispatched ?></span>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">No variant lines logged</span>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex justify-content-between align-items-center small text-muted">
                                    <span><i class="bi bi-box-seam me-1"></i><?= number_format($a['total_boxes'] ?? 1) ?> Cartons</span>
                                    <span><i class="bi bi-calendar3 me-1"></i><?= !empty($a['dispatch_date']) ? date('d M, h:i A', strtotime($a['dispatch_date'])) : 'Dispatched' ?></span>
                                </div>
                            </div>

                            <!-- Card Footer -->
                            <div class="card-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center gap-1">
                                <?php if ($isFullyReceived || !$canInward): ?>
                                    <a href="<?= url('shop/inward/view/' . $a['id']) . $shopQuery ?>" class="btn btn-sm btn-outline-primary flex-grow-1 py-1">
                                        <i class="bi bi-eye me-1"></i> View Inward
                                    </a>
                                <?php elseif ($isPartiallyReceived): ?>
                                    <a href="<?= url('shop/inward/view/' . $a['id']) . $shopQuery ?>" class="btn btn-sm btn-warning text-dark fw-semibold flex-grow-1 py-1">
                                        <i class="bi bi-qr-code-scan me-1"></i> Continue Inward (<?= $missingQty ?> missing)
                                    </a>
                                <?php else: ?>
                                    <a href="<?= url('shop/inward/view/' . $a['id']) . $shopQuery ?>" class="btn btn-sm btn-danger fw-semibold flex-grow-1 py-1">
                                        <i class="bi bi-qr-code-scan me-1"></i> Cross-Check &amp; Inward
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="noBatchCardsMatch" class="text-center py-4 d-none text-muted">
                <i class="bi bi-search fs-3 d-block mb-1"></i> No matching allocation cards found for the selected filter or search term.
            </div>
        <?php endif; ?>
    </div>
</div>
    </div>
    <div class="tab-pane fade show active" id="tab-list-view" role="tabpanel" aria-labelledby="tab-list-btn">
<!-- Filters Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('shop/inward') ?>" class="row g-2 align-items-end" id="inwardFilterForm">
            <div class="col-12 col-sm-6 <?= $hasMultipleShops ? 'col-lg-3' : 'col-lg-4' ?>">
                <label class="form-label small fw-semibold text-muted mb-1">Search Filter</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                    <input type="text" name="search" id="inwardSearchInput" class="form-control form-control-sm"
                           placeholder="Search allocation, outward, product, SKU..."
                           value="<?= e($search ?? '') ?>" autocomplete="off">
                </div>
            </div>
            <?php if ($hasMultipleShops): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Destination Shop</label>
                    <select name="shop_id" id="inwardShopSelect" class="form-select form-select-sm">
                        <option value="0" <?= (int)$selectedShopId === 0 ? 'selected' : '' ?>>All Shops</option>
                        <?php foreach ($shops as $availableShop): ?>
                            <option value="<?= (int)$availableShop['id'] ?>" <?= (int)$selectedShopId === (int)$availableShop['id'] ? 'selected' : '' ?>>
                                <?= e($availableShop['name']) ?><?= !empty($availableShop['code']) ? ' (' . e($availableShop['code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-12 col-sm-6 <?= $hasMultipleShops ? 'col-lg-3' : 'col-lg-4' ?>">
                <label class="form-label small fw-semibold text-muted mb-1">Source Warehouse</label>
                <select name="warehouse_id" id="inwardWarehouseSelect" class="form-select form-select-sm">
                    <option value="">All Warehouses</option>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?= $wh['id'] ?>" <?= ($warehouseId ?? '') == $wh['id'] ? 'selected' : '' ?>>
                            <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-8 col-sm-4 col-lg-2">
                <label class="form-label small fw-semibold text-muted mb-1">Inward Status</label>
                <select name="status" id="inwardStatusSelect" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="DISPATCHED" <?= ($status ?? '') === 'DISPATCHED' ? 'selected' : '' ?>>DISPATCHED (Awaiting Inward)</option>
                    <option value="PARTIAL_RECEIVED" <?= ($status ?? '') === 'PARTIAL_RECEIVED' ? 'selected' : '' ?>>PARTIAL (Continue Inward)</option>
                    <option value="RECEIVED" <?= in_array(($status ?? ''), ['RECEIVED', 'COMPLETED', 'DELIVERED']) ? 'selected' : '' ?>>RECEIVED (Stock Credited)</option>
                </select>
            </div>
            <div class="col-4 col-sm-2 <?= $hasMultipleShops ? 'col-lg-1' : 'col-lg-2' ?>">
                <div class="btn-group btn-group-sm w-100" role="group">
                    <button type="submit" class="btn btn-primary" title="Apply Filter"><i class="bi bi-funnel-fill"></i></button>
                    <a href="<?= url('shop/inward') . $shopQuery ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Allocation Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2 text-primary"></i>All Inward Allocations Detailed Ledger</h6>
        <span class="badge bg-light text-dark border font-monospace fs-8" id="inwardTableCountBadge">Showing <?= count($allocations ?? []) ?> Dispatches</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($allocations)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                <h5 class="text-muted">No Inward Allocations Found</h5>
                <p class="text-muted small">Allocations dispatched from warehouses will appear here ready for shop inward verification.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="inwardTable" style="min-width: 800px;">
                    <thead class="table-light">
                        <tr>
                            <th>Allocation</th>
                            <th>Product &amp; Items</th>
                            <th>Source Warehouse &rarr; Destination</th>
                            <th>Quantity &amp; Boxes</th>
                            <th>Dispatch Date &amp; Vehicle</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inwardTableBody">
                        <?php foreach ($allocations as $a): ?>
                            <?php 
                                $isPartial = $a['status'] === 'PARTIAL_RECEIVED' || (int)($a['missing_qty'] ?? 0) > 0;
                                $isRecv = in_array($a['status'], ['RECEIVED', 'COMPLETED', 'DELIVERED'], true) && !$isPartial;
                                $itemsList = $a['items'] ?? [];
                                $skuSearchTerms = implode(' ', array_map(function($i) { return ($i['sku'] ?? '') . ' ' . ($i['size_name'] ?? '') . ' ' . ($i['color_name'] ?? '') . ' ' . ($i['product_name'] ?? ''); }, $itemsList));
                                $searchHaystack = strtolower(e($a['allocation_number'] . ' ' . ($a['outward_number'] ?? '') . ' ' . ($a['vehicle_number'] ?? '') . ' ' . ($a['driver_name'] ?? '') . ' ' . $a['warehouse_name'] . ' ' . $skuSearchTerms));
                            ?>
                            <tr id="row-alloc-<?= $a['id'] ?>" 
                                class="inward-alloc-row" 
                                data-search="<?= $searchHaystack ?>"
                                data-warehouse-id="<?= (int)($a['warehouse_id'] ?? 0) ?>"
                                data-status="<?= $isPartial ? 'PARTIAL_RECEIVED' : ($isRecv ? 'RECEIVED' : 'DISPATCHED') ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-light border p-1 btn-show-qr" data-code="<?= e($a['allocation_number']) ?>" data-product="<?= e($a['items'][0]['product_name'] ?? 'Stock Consignment') ?>" title="Show QR Code">
                                            <i class="bi bi-qr-code text-primary fs-5"></i>
                                        </button>
                                        <div>
                                            <span class="fw-bold font-monospace text-primary"><?= e($a['allocation_number']) ?></span>
                                            <?php if (!empty($a['outward_number'])): ?>
                                                <br><span class="badge bg-light text-dark border"><i class="bi bi-truck me-1"></i><?= e($a['outward_number']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark"><?= e($a['items'][0]['product_name'] ?? 'Assorted Apparels') ?></span>
                                    <?php if (count($a['items']) > 1): ?>
                                        <br><small class="text-muted">+<?= count($a['items']) - 1 ?> other variant(s)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <div class="p-1 source-badge-box d-flex align-items-center justify-content-between gap-1">
                                            <span class="small fw-semibold text-dark text-truncate"><i class="bi bi-building text-warning me-1"></i>From: <?= e($a['warehouse_name']) ?></span>
                                            <span class="badge bg-warning text-dark border border-warning font-monospace fs-8"><?= e($a['warehouse_code'] ?? 'WH') ?></span>
                                        </div>
                                        <div class="p-1 dest-badge-box d-flex align-items-center justify-content-between gap-1">
                                            <span class="small fw-semibold text-primary text-truncate"><i class="bi bi-shop me-1"></i>To: <?= e($a['destination_shop_name'] ?? $shop['name'] ?? 'Shop') ?></span>
                                            <span class="badge bg-primary text-white font-monospace fs-8"><?= e($a['destination_shop_code'] ?? $shop['code'] ?? 'SHOP') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-success fs-6"><?= number_format($a['effective_qty']) ?> pcs</span>
                                    <br><span class="badge bg-light text-dark border"><?= number_format($a['total_boxes'] ?? 1) ?> Cartons</span>
                                </td>
                                <td>
                                    <?php if (!empty($a['dispatch_date'])): ?>
                                        <small class="text-muted d-block"><?= date('d M Y, h:i A', strtotime($a['dispatch_date'])) ?></small>
                                        <small class="text-secondary"><i class="bi bi-car-front me-1"></i><?= e($a['vehicle_number'] ?? 'Direct') ?></small>
                                    <?php else: ?>
                                        <small class="text-muted d-block"><?= date('d M Y', strtotime($a['created_at'])) ?></small>
                                        <span class="text-muted small">Standard Transport</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isPartial): ?>
                                        <span class="badge bg-warning text-dark border border-warning batch-status-badge"><i class="bi bi-exclamation-triangle me-1"></i>Partial (<?= (int)($a['missing_qty'] ?? 0) ?> Short)</span>
                                    <?php elseif (!$isRecv): ?>
                                        <span class="badge bg-warning text-dark batch-status-badge"><i class="bi bi-hourglass-split me-1"></i>Awaiting Shop Inward</span>
                                    <?php else: ?>
                                        <span class="badge bg-success batch-status-badge"><i class="bi bi-check2-all me-1"></i>Received / Processed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= url('shop/inward/view/' . $a['id']) . $shopQuery ?>" class="btn btn-sm <?= ((!$isRecv || $isPartial) && $canInward) ? ($isPartial ? 'btn-warning text-dark fw-semibold' : 'btn-primary fw-semibold') : 'btn-outline-primary' ?>">
                                            <i class="bi <?= ((!$isRecv || $isPartial) && $canInward) ? 'bi-qr-code-scan' : 'bi-eye' ?> me-1"></i>
                                            <?= ((!$isRecv || $isPartial) && $canInward) ? ($isPartial ? 'Continue Inward' : 'Cross-Check &amp; Inward') : 'View Inward' ?>
                                        </a>
                                        <a href="<?= url('shop/inventory') ?>?shop_id=<?= (int)($a['destination_shop_id'] ?? 0) ?>"
                                           class="btn btn-sm btn-outline-success"
                                           title="View inventory for <?= e($a['destination_shop_name'] ?? 'destination shop') ?>">
                                            <i class="bi bi-box-seam"></i>
                                        </a>
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
<!-- MODAL: SHOW QR CODE -->
<!-- ========================================================================= -->
<div class="modal fade" id="qrPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="qrModalTitle">Consignment QR Code</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modalQrCode" class="d-flex justify-content-center mb-3"></div>
                <div id="qrModalCodeText" class="font-monospace fw-bold fs-6 text-primary"></div>
                <small id="qrModalProductText" class="text-muted d-block mt-1"></small>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View QR Modal
    document.querySelectorAll('.btn-show-qr').forEach(btn => {
        btn.addEventListener('click', function() {
            const code = this.dataset.code;
            const product = this.dataset.product;
            const modalEl = document.getElementById('qrPreviewModal');
            document.getElementById('modalQrCode').innerHTML = '';
            document.getElementById('qrModalCodeText').textContent = code;
            document.getElementById('qrModalProductText').textContent = product;
            if (typeof QRCode !== 'undefined') {
                new QRCode(document.getElementById("modalQrCode"), {
                    text: code,
                    width: 160,
                    height: 160
                });
            }
            new bootstrap.Modal(modalEl).show();
        });
    });

    // Keep the selected view while navigating between inward records.
    const listTabBtn = document.getElementById('tab-list-btn');
    const cardsTabBtn = document.getElementById('tab-cards-btn');
    const tabStorageKey = 'grm_shop_inward_view_mode';
    const activateTab = function(tabName) {
        const tabButton = tabName === 'cards' ? cardsTabBtn : listTabBtn;
        if (tabButton) {
            (bootstrap.Tab.getInstance(tabButton) || new bootstrap.Tab(tabButton)).show();
        }
    };
    const tabHash = window.location.hash;
    if (tabHash === '#tab-cards-view' || tabHash === '#cards') {
        activateTab('cards');
    } else if (tabHash === '#tab-list-view' || tabHash === '#list') {
        activateTab('list');
    } else {
        try {
            activateTab(localStorage.getItem(tabStorageKey) || 'list');
        } catch (error) {
            activateTab('list');
        }
    }
    if (listTabBtn) {
        listTabBtn.addEventListener('shown.bs.tab', function() {
            try { localStorage.setItem(tabStorageKey, 'list'); } catch (error) {}
        });
    }
    if (cardsTabBtn) {
        cardsTabBtn.addEventListener('shown.bs.tab', function() {
            try { localStorage.setItem(tabStorageKey, 'cards'); } catch (error) {}
        });
    }

    // Interactive Allocation Cards Filter & Search
    const batchFilterBtns = document.querySelectorAll('.btn-batch-filter');
    const batchSearchInput = document.getElementById('batchCardSearch');
    const batchCardItems = document.querySelectorAll('.batch-card-item');
    const noBatchMatch = document.getElementById('noBatchCardsMatch');

    let currentFilter = 'all';

    function filterBatchCards() {
        const mainQuery = (inwardSearchInput ? inwardSearchInput.value : '').toLowerCase().trim();
        const cardQuery = (batchSearchInput ? batchSearchInput.value : '').toLowerCase().trim();
        const query = mainQuery || cardQuery;
        const whId = inwardWhSelect ? inwardWhSelect.value : '';
        const statusVal = inwardStatusSelect ? inwardStatusSelect.value : '';
        let visibleCount = 0;

        batchCardItems.forEach(card => {
            const inwardStatus = card.getAttribute('data-inward-status') || '';
            const searchData = card.getAttribute('data-search') || '';

            const matchesStatus = currentFilter === 'all'
                || (currentFilter === 'missing' && card.getAttribute('data-has-missing') === '1')
                || (currentFilter === 'stocked' && card.getAttribute('data-has-stock') === '1');
            const matchesSearch = !query || searchData.includes(query);
            const matchesWarehouse = !whId || card.getAttribute('data-warehouse-id') === whId;
            const matchesInwardStatus = !statusVal || inwardStatus === statusVal || (statusVal === 'RECEIVED' && ['COMPLETED', 'DELIVERED'].includes(inwardStatus));

            if (matchesStatus && matchesSearch && matchesWarehouse && matchesInwardStatus) {
                card.classList.remove('d-none');
                visibleCount++;
            } else {
                card.classList.add('d-none');
            }
        });

        if (noBatchMatch) {
            if (visibleCount === 0 && batchCardItems.length > 0) {
                noBatchMatch.classList.remove('d-none');
            } else {
                noBatchMatch.classList.add('d-none');
            }
        }
    }

    if (batchFilterBtns.length > 0) {
        batchFilterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                batchFilterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.getAttribute('data-filter');
                filterBatchCards();
            });
        });
    }

    // Instant Real-Time Letter-by-Letter Table Filter Engine for Allocation Ledger
    const inwardSearchInput = document.getElementById('inwardSearchInput');
    const inwardWhSelect = document.getElementById('inwardWarehouseSelect');
    const inwardStatusSelect = document.getElementById('inwardStatusSelect');
    const inwardTableBody = document.getElementById('inwardTableBody');
    const inwardCountBadge = document.getElementById('inwardTableCountBadge');
    const btnClearInward = document.getElementById('btnClearInwardSearch');
    const allocRows = Array.from(document.querySelectorAll('.inward-alloc-row'));

    window.clearInwardSearch = function() {
        if (inwardSearchInput) {
            inwardSearchInput.value = '';
            if (btnClearInward) btnClearInward.classList.add('d-none');
            if (window.applyInwardFilters) window.applyInwardFilters();
        }
    };

    window.applyInwardFilters = function() {
        if (!allocRows.length) return;

        const query = (inwardSearchInput ? inwardSearchInput.value : '').trim().toLowerCase();
        const whId = inwardWhSelect ? inwardWhSelect.value : '';
        const statusVal = inwardStatusSelect ? inwardStatusSelect.value : '';

        if (btnClearInward) {
            if (query) btnClearInward.classList.remove('d-none');
            else btnClearInward.classList.add('d-none');
        }

        let visibleCount = 0;

        allocRows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            const rowWh = row.getAttribute('data-warehouse-id') || '';
            const rowStatus = row.getAttribute('data-status') || '';

            const matchQuery = !query || searchData.includes(query);
            const matchWh = !whId || rowWh === whId;
            const matchStatus = !statusVal || rowStatus === statusVal || (statusVal === 'RECEIVED' && ['COMPLETED', 'DELIVERED'].includes(rowStatus));

            if (matchQuery && matchWh && matchStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (inwardCountBadge) {
            inwardCountBadge.textContent = `${visibleCount} Consignments`;
        }

        let emptyRow = document.getElementById('noInwardMatchRow');
        if (visibleCount === 0) {
            if (!emptyRow && inwardTableBody) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'noInwardMatchRow';
                emptyRow.innerHTML = `
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <h6 class="fw-bold text-secondary">No Matching Inward Allocations Found</h6>
                        <p class="small text-muted mb-2">No consignments match your live search criteria.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="clearInwardSearch()">
                            Clear Search
                        </button>
                    </td>
                `;
                inwardTableBody.appendChild(emptyRow);
            }
        } else if (emptyRow) {
            emptyRow.remove();
        }
        filterBatchCards();
    };

    if (inwardSearchInput) {
        inwardSearchInput.addEventListener('input', applyInwardFilters);
        inwardSearchInput.addEventListener('keyup', applyInwardFilters);
    }
    if (inwardWhSelect) {
        inwardWhSelect.addEventListener('change', applyInwardFilters);
    }
    if (inwardStatusSelect) {
        inwardStatusSelect.addEventListener('change', applyInwardFilters);
    }
    if (batchSearchInput) {
        batchSearchInput.addEventListener('input', applyInwardFilters);
        batchSearchInput.addEventListener('keyup', applyInwardFilters);
    }
    applyInwardFilters();

    // Check localStorage for any in-progress scan drafts and badge the cards
    try {
        document.querySelectorAll('.batch-card-item[data-alloc-id]').forEach(card => {
            const allocId = card.getAttribute('data-alloc-id');
            const status = card.getAttribute('data-status');
            if (status === 'missing' && allocId) {
                const raw = localStorage.getItem('grm_shop_inward_draft_' + allocId);
                if (raw) {
                    const draft = JSON.parse(raw);
                    let scannedCount = 0;
                    if (draft.receivedMap) {
                        Object.values(draft.receivedMap).forEach(v => scannedCount += (parseInt(v) || 0));
                    }
                    if (scannedCount > 0) {
                        const btn = card.querySelector('a[href*="shop/inward/view/"]');
                        if (btn) {
                            btn.className = 'btn btn-sm btn-warning text-dark fw-bold flex-grow-1 py-1 shadow-xs';
                            btn.innerHTML = `<i class="bi bi-clock-history me-1"></i>Resume Cross-Check (${scannedCount} scanned)`;
                        }
                        const header = card.querySelector('.card-header');
                        if (header) {
                            const badge = document.createElement('span');
                            badge.className = 'badge bg-warning text-dark font-monospace px-2 py-1 fs-8 ms-1';
                            badge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Draft: ${scannedCount} pcs`;
                            header.appendChild(badge);
                        }
                    }
                }
            }
        });
    } catch(e) {
        console.warn("Draft indicator check:", e);
    }
});
</script>
