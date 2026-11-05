<style>
.inward-wrapper {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}
.custom-inward-pills .nav-link {
    border-radius: 8px;
    padding: 0.5rem 1.1rem;
    font-weight: 600;
    font-size: 0.875rem;
    color: #4b5563;
    border: 1px solid #e5e7eb;
    background-color: #ffffff;
    transition: all 0.2s ease-in-out;
    white-space: nowrap;
}
.custom-inward-pills .nav-link:hover {
    background-color: #f3f4f6;
    color: #1f2937;
    border-color: #d1d5db;
}
.custom-inward-pills .nav-link.active {
    background-color: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2), 0 2px 4px -2px rgba(37, 99, 235, 0.2);
}
.custom-inward-pills .nav-link.active .badge {
    background-color: #ffffff !important;
    color: #2563eb !important;
}

.batch-card-hover {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.batch-card-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1) !important;
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
.variant-scroll-box {
    max-height: 110px;
    overflow-y: auto;
    scrollbar-width: thin;
}
.variant-scroll-box::-webkit-scrollbar {
    width: 4px;
}
.variant-scroll-box::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
}
.fs-7 { font-size: 0.825rem; }
.fs-8 { font-size: 0.75rem; }
@media (max-width: 1200px) {
    .metric-card-title {
        font-size: 0.72rem !important;
    }
    .metric-card-value {
        font-size: 1.35rem !important;
    }
}
</style>
<div class="inward-wrapper">
<?php
$canVerify = hasPermission('inward', 'verify');
?>
    <!-- Page Header -->
    <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1 fs-4 fw-bold"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Production Inward</h2>
        </div>
        <div class="d-flex gap-2">
            <?php if ($canVerify): ?>  
            <?php endif; ?>
            <a href="<?= url('warehouse/dashboard') ?>" class="btn btn-sm btn-outline-secondary px-3">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 1. SUMMARY METRIC CARDS (FLUID 3-COLUMN LAYOUT) -->
    <!-- ========================================================================= -->
    <div class="row g-3 mb-3">
        <!-- 1. Awaiting GRN -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div style="min-width: 0;">
                            <span class="text-muted small fw-bold text-uppercase d-block text-truncate metric-card-title" style="font-size: 0.75rem;">Awaiting GRN (Missing Stock)</span>
                            <h4 class="mb-0 fw-bold text-danger mt-1 metric-card-value">
                                <?= number_format($pendingBatchesCount ?? 0) ?> <small class="fs-6 text-muted fw-normal">Batches</small>
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

        <!-- 2. Received & Stock Credited -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div style="min-width: 0;">
                            <span class="text-muted small fw-bold text-uppercase d-block text-truncate metric-card-title" style="font-size: 0.75rem;">Received &amp; Stock Credited</span>
                            <h4 class="mb-0 fw-bold text-success mt-1 metric-card-value">
                                <?= number_format($receivedBatchesCount ?? 0) ?> <small class="fs-6 text-muted fw-normal">Batches</small>
                            </h4>
                            <small class="text-success fw-semibold mt-1 d-block text-truncate">
                                <i class="bi bi-check2-circle me-1"></i><?= number_format($totalReceivedQty ?? 0) ?> pcs in warehouse stock
                            </small>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-2 p-xl-3 text-success flex-shrink-0 ms-2">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Total Inward Dispatches -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div style="min-width: 0;">
                            <span class="text-muted small fw-bold text-uppercase d-block text-truncate metric-card-title" style="font-size: 0.75rem;">Total Inward Dispatches</span>
                            <h4 class="mb-0 fw-bold text-primary mt-1 metric-card-value">
                                <?= number_format(count($batches)) ?> <small class="fs-6 text-muted fw-normal">Batches</small>
                            </h4>
                            <small class="text-muted mt-1 d-block text-truncate">
                                <i class="bi bi-truck me-1"></i><?= number_format(($totalPendingMissingQty ?? 0) + ($totalReceivedQty ?? 0)) ?> total dispatched pcs
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
    <!-- 2. LIVE QR CODE SCANNER BAR -->
    <!-- ========================================================================= -->
    <?php if ($canVerify): ?>
    <?php else: ?>
    <div class="alert alert-light border border-info-subtle d-flex align-items-center gap-2 mb-3 py-2 px-3">
        <i class="bi bi-info-circle-fill text-info fs-5"></i>
        <div class="small text-muted">
            <strong class="text-dark">View-Only Mode:</strong> Inward QR scanning, receiving, and cross-check verification are restricted to authorized personnel.
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- 3. VIEW MODE SELECTION TABS (LIST VIEW VS BATCH CARDS VIEW) -->
    <!-- ========================================================================= -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2 p-md-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-bold text-dark small text-uppercase d-none d-sm-inline"><i class="bi bi-layout-split me-1 text-primary"></i>View Mode:</span>
                    <ul class="nav nav-pills custom-inward-pills" id="inwardViewTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active d-flex align-items-center gap-2" id="tab-list-btn" data-bs-toggle="pill" data-bs-target="#tab-list-view" type="button" role="tab" aria-controls="tab-list-view" aria-selected="true">
                                <i class="bi bi-list-task fs-6"></i>
                                <span>List / Ledger View</span>
                                <span class="badge bg-secondary"><?= count($batches) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link d-flex align-items-center gap-2" id="tab-cards-btn" data-bs-toggle="pill" data-bs-target="#tab-cards-view" type="button" role="tab" aria-controls="tab-cards-view" aria-selected="false">
                                <i class="bi bi-grid-fill fs-6"></i>
                                <span>Batch Cards View</span>
                                <?php if (($pendingBatchesCount ?? 0) > 0): ?>
                                    <span class="badge bg-danger"><?= $pendingBatchesCount ?> Missing</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= count($batches) ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. TAB CONTENTS -->
    <!-- ========================================================================= -->
    <div class="tab-content" id="inwardViewTabsContent">

        <!-- --------------------------------------------------------------------- -->
        <!-- TAB 1: LIST / LEDGER VIEW -->
        <!-- --------------------------------------------------------------------- -->
        <div class="tab-pane fade show active" id="tab-list-view" role="tabpanel" aria-labelledby="tab-list-btn">
            
            <!-- Filters Card (Fully Responsive Grid Layout) -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3">
                    <form method="GET" action="<?= url('warehouse/inward') ?>" class="row g-2 align-items-end">
                        <!-- Search Filter -->
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small fw-semibold text-muted mb-1">Search Filter</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                                <input type="text" name="search" id="mainInwardSearch" class="form-control form-control-sm" placeholder="Search batch, unit, vehicle..." value="<?= e($search ?? '') ?>" autocomplete="off">
                            </div>
                        </div>

                        <!-- Source Production Unit -->
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small fw-semibold text-muted mb-1">Source Production Unit</label>
                            <select name="production_unit_id" id="inwardProductionUnitSelect" class="form-select form-select-sm">
                                <option value="">All Production Units</option>
                                <?php foreach ($productionUnits ?? [] as $pu): ?>
                                    <option value="<?= $pu['id'] ?>" <?= ($unitId ?? '') == $pu['id'] ? 'selected' : '' ?>>
                                        <?= e($pu['name']) ?> (<?= e($pu['code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Destination Warehouse -->
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small fw-semibold text-muted mb-1">Destination Warehouse</label>
                            <select name="warehouse_id" id="inwardWarehouseSelect" class="form-select form-select-sm" <?= (!empty($scopedWarehouseId) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                                <?php if (empty($scopedWarehouseId) && count($warehouses) > 1): ?>
                                    <option value="">All Warehouses</option>
                                <?php endif; ?>
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?= $wh['id'] ?>" <?= (($warehouseId ?? '') == $wh['id'] || (!empty($scopedWarehouseId) && $scopedWarehouseId == $wh['id'])) ? 'selected' : '' ?>>
                                        <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($scopedWarehouseId) || count($warehouses) <= 1): ?>
                                <input type="hidden" name="warehouse_id" value="<?= e($warehouseId ?? ($warehouses[0]['id'] ?? '')) ?>">
                            <?php endif; ?>
                        </div>

                        <!-- Inward Status -->
                        <div class="col-8 col-sm-4 col-lg-2">
                            <label class="form-label small fw-semibold text-muted mb-1">Inward Status</label>
                            <select name="status" id="inwardStatusSelect" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="DISPATCHED" <?= ($status ?? '') === 'DISPATCHED' ? 'selected' : '' ?>>DISPATCHED (Awaiting GRN)</option>
                                <option value="RECEIVED" <?= ($status ?? '') === 'RECEIVED' ? 'selected' : '' ?>>RECEIVED (Stocked)</option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-4 col-sm-2 col-lg-1">
                            <div class="btn-group btn-group-sm w-100" role="group">
                                <button type="submit" class="btn btn-primary" title="Apply Filter"><i class="bi bi-funnel-fill"></i></button>
                                <a href="<?= url('warehouse/inward') ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-clockwise"></i></a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Batches Table Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2 text-primary"></i>All Inward Batches Detailed Ledger</h6>
                       
                    </div>
                    <span class="badge bg-light text-dark border font-monospace fs-8" id="inwardShowingCountBadge">Showing <?= count($batches) ?> Dispatches</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($batches)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                            <h5 class="text-muted">No Inward Batches Found</h5>
                            <p class="text-muted small">Finished production batches will appear here once dispatched from manufacturing units.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="inwardBatchesTable" style="min-width: 800px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Batch</th>
                                        <th>Program &amp; Product</th>
                                        <th>Source Unit &rarr; Destination</th>
                                        <th>Quantity &amp; Boxes</th>
                                        <th>Dispatch Date &amp; Vehicle</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($batches as $b): ?>
                                        <?php
                                        $itemsList = $b['items'] ?? ($batchItemsMap[$b['id']] ?? []);
                                        $skuSearchTerms = implode(' ', array_map(function ($i) {
                                            return ($i['sku'] ?? '') . ' ' . ($i['size_name'] ?? '') . ' ' . ($i['color_name'] ?? '');
                                        }, $itemsList));
                                        $rowSearchPayload = strtolower(e($b['batch_number'] . ' ' . ($b['outward_number'] ?? '') . ' ' . $b['product_name'] . ' ' . $b['program_number'] . ' ' . ($b['warehouse_name'] ?? '') . ' ' . ($b['warehouse_code'] ?? '') . ' ' . ($b['production_unit_name'] ?? '') . ' ' . ($b['production_unit_code'] ?? '') . ' ' . ($b['vehicle_number'] ?? '') . ' ' . ($b['driver_name'] ?? '') . ' ' . $skuSearchTerms));
                                        ?>
                                        <tr id="row-batch-<?= $b['id'] ?>" class="inward-batch-row"
                                            data-search="<?= $rowSearchPayload ?>"
                                            data-unit-id="<?= e($b['outward_unit_id'] ?? $b['program_unit_id'] ?? '') ?>"
                                            data-warehouse-id="<?= e($b['destination_warehouse_id'] ?? '') ?>"
                                            data-status="<?= e($b['status']) ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button type="button" class="btn btn-sm btn-light border p-1 btn-show-qr" data-code="<?= e($b['batch_number']) ?>" data-product="<?= e($b['product_name']) ?>" title="Show QR Code">
                                                        <i class="bi bi-qr-code text-primary fs-5"></i>
                                                    </button>
                                                    <div>
                                                        <span class="fw-bold font-monospace text-primary"><?= e($b['batch_number']) ?></span>
                                                        <?php if (!empty($b['outward_number'])): ?>
                                                            <br><span class="badge bg-light text-dark border"><i class="bi bi-truck me-1"></i><?= e($b['outward_number']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark"><?= e($b['product_name']) ?></span>
                                                <br><small class="text-muted">Prog: <?= e($b['program_number']) ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="small fw-semibold text-dark"><i class="bi bi-gear-wide-connected text-warning me-1"></i>From: <?= e($b['production_unit_name']) ?></span>
                                                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 font-monospace fs-8"><?= e($b['production_unit_code']) ?></span>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="small text-primary fw-semibold"><i class="bi bi-building text-primary me-1"></i>To: <?= e($b['warehouse_name']) ?></span>
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-8"><?= e($b['warehouse_code']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-success fs-6"><?= number_format($b['total_qty']) ?> pcs</span>
                                                <br><span class="badge bg-light text-dark border"><?= number_format($b['box_count'] ?? 1) ?> Cartons</span>
                                            </td>
                                            <td>
                                                <?php if (!empty($b['dispatch_date'])): ?>
                                                    <small class="text-muted d-block"><?= date('d M Y, h:i A', strtotime($b['dispatch_date'])) ?></small>
                                                    <small class="text-secondary"><i class="bi bi-car-front me-1"></i><?= e($b['vehicle_number'] ?? 'Direct') ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted small">Pending dispatch</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $bTot = (int) $b['total_qty'];
                                                $bRec = (int) ($b['received_qty'] ?? 0);
                                                $bDmg = (int) ($b['damaged_qty'] ?? 0);
                                                $bMis = (int) ($b['missing_qty'] ?? max(0, $bTot - ($bRec + $bDmg)));
                                                $bIsPartial = ($b['status'] === 'RECEIVED' && $bMis > 0);
                                                $bIsFullyStocked = ($b['status'] === 'RECEIVED' && $bMis === 0);
                                                ?>
                                                <?php if ($b['status'] === 'DISPATCHED'): ?>
                                                    <span class="badge bg-warning text-dark batch-status-badge"><i class="bi bi-hourglass-split me-1"></i>Awaiting GRN</span>
                                                <?php elseif ($bIsFullyStocked): ?>
                                                    <span class="badge bg-success batch-status-badge"><i class="bi bi-check2-all me-1"></i>Received &amp; Stocked</span>
                                                <?php elseif ($bIsPartial): ?>
                                                    <span class="badge bg-warning text-dark border border-warning batch-status-badge"><i class="bi bi-exclamation-triangle me-1"></i>Partial (<?= $bMis ?> Short)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary batch-status-badge"><?= e($b['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-info btn-batch-history" data-batch-id="<?= $b['id'] ?>" data-batch-number="<?= e($b['batch_number']) ?>" title="Batch History &amp; Traceability Logs">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                    <a href="<?= url('warehouse/inward/view/' . $b['id']) ?>" class="btn btn-sm <?= ($canVerify && ($b['status'] === 'DISPATCHED' || $bIsPartial)) ? ($bIsPartial ? 'btn-warning text-dark fw-bold' : 'btn-primary fw-semibold') : 'btn-outline-primary' ?>">
                                                        <i class="bi <?= ($canVerify && ($b['status'] === 'DISPATCHED' || $bIsPartial)) ? 'bi-qr-code-scan' : 'bi-eye' ?> me-1"></i>
                                                        <?= ($canVerify && $b['status'] === 'DISPATCHED') ? 'Cross-Check &amp; Inward' : (($canVerify && $bIsPartial) ? "Scan Remaining ($bMis)" : 'View Inward') ?>
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

        <!-- --------------------------------------------------------------------- -->
        <!-- TAB 2: BATCH CARDS (GRID) VIEW -->
        <!-- --------------------------------------------------------------------- -->
        <div class="tab-pane fade" id="tab-cards-view" role="tabpanel" aria-labelledby="tab-cards-btn">
            <div class="card border-0 shadow-sm mb-4 border-start border-danger border-4">
                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="card-title mb-0 fw-bold text-danger">
                            <i class="bi bi-grid-fill me-2"></i>Batch-Wise Missing Stock &amp; Variant Overview
                        </h6>
                        <small class="text-muted fs-8">Interactive batch cards with variant breakdown &amp; destination warehouse</small>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="input-group input-group-sm" style="width: 220px;">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                            <input type="text" id="batchCardSearch" class="form-control" placeholder="Search batch, SKU...">
                        </div>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary active btn-batch-filter" data-filter="all">All (<?= count($batches) ?>)</button>
                            <button type="button" class="btn btn-outline-danger btn-batch-filter" data-filter="missing">Missing Only (<?= $pendingBatchesCount ?? 0 ?>)</button>
                            <button type="button" class="btn btn-outline-success btn-batch-filter" data-filter="stocked">Stocked (<?= $receivedBatchesCount ?? 0 ?>)</button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3 bg-light">
                    <?php if (empty($batches)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-1"></i> No batch records available.
                        </div>
                    <?php else: ?>
                        <div class="row g-3" id="batchCardsContainer">
                            <?php foreach ($batches as $b): ?>
                                <?php
                                $totalQty = (int) $b['total_qty'];
                                $recQty = (int) ($b['received_qty'] ?? 0);
                                $dmgQty = (int) ($b['damaged_qty'] ?? 0);
                                $misQty = (int) ($b['missing_qty'] ?? max(0, $totalQty - ($recQty + $dmgQty)));
                                $isFullyCredited = ($b['status'] === 'RECEIVED' && $misQty === 0);
                                $isPartial = ($recQty > 0 && $misQty > 0);
                                $isDispatched = ($b['status'] === 'DISPATCHED' && $recQty === 0);
                                $itemsList = $b['items'] ?? ($batchItemsMap[$b['id']] ?? []);
                                $skuSearchTerms = implode(' ', array_map(function ($i) {
                                    return ($i['sku'] ?? '') . ' ' . ($i['size_name'] ?? '') . ' ' . ($i['color_name'] ?? '');
                                }, $itemsList));
                                $searchPayload = strtolower(e($b['batch_number'] . ' ' . ($b['outward_number'] ?? '') . ' ' . $b['product_name'] . ' ' . $b['program_number'] . ' ' . ($b['warehouse_name'] ?? '') . ' ' . ($b['warehouse_code'] ?? '') . ' ' . ($b['production_unit_name'] ?? '') . ' ' . ($b['production_unit_code'] ?? '') . ' ' . ($b['vehicle_number'] ?? '') . ' ' . ($b['driver_name'] ?? '') . ' ' . $skuSearchTerms));
                                ?>
                                <div class="col-12 col-md-6 col-lg-6 col-xl-4 col-xxl-3 batch-card-item" 
                                     data-status="<?= ($misQty > 0) ? 'missing' : 'stocked' ?>" 
                                     data-search="<?= $searchPayload ?>"
                                     data-unit-id="<?= e($b['outward_unit_id'] ?? $b['program_unit_id'] ?? '') ?>"
                                     data-warehouse-id="<?= e($b['destination_warehouse_id'] ?? '') ?>"
                                     data-inward-status="<?= e($b['status']) ?>">
                                    <div class="card border shadow-sm h-100 batch-card-hover <?= ($misQty > 0) ? ($isPartial ? 'border-warning border-opacity-75' : 'border-danger border-opacity-50') : 'border-success border-opacity-50' ?>">
                                        <!-- Card Header -->
                                        <div class="card-header <?= ($misQty > 0) ? ($isPartial ? 'bg-warning bg-opacity-10 border-warning border-opacity-25' : 'bg-danger bg-opacity-10 border-danger border-opacity-25') : 'bg-success bg-opacity-10 border-success border-opacity-25' ?> py-2 px-3 d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center gap-1" style="min-width: 0;">
                                                <span class="fw-bold font-monospace text-dark fs-7 text-truncate"><?= e($b['batch_number']) ?></span>
                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none btn-show-qr ms-1 flex-shrink-0" data-code="<?= e($b['batch_number']) ?>" data-product="<?= e($b['product_name']) ?>" title="Show QR">
                                                    <i class="bi bi-qr-code text-primary"></i>
                                                </button>
                                            </div>
                                            <?php if ($isFullyCredited): ?>
                                                <span class="badge bg-success rounded-pill px-2 py-1 fs-8 flex-shrink-0 ms-1">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Stock Credited (<?= number_format($recQty) ?>/<?= number_format($totalQty) ?>)
                                                </span>
                                            <?php elseif ($isPartial): ?>
                                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1 fs-8 flex-shrink-0 ms-1">
                                                    <i class="bi bi-hourglass-split me-1"></i>Partial (<?= number_format($recQty) ?>/<?= number_format($totalQty) ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger rounded-pill px-2 py-1 fs-8 flex-shrink-0 ms-1">
                                                    <i class="bi bi-exclamation-circle-fill me-1"></i><?= number_format($misQty) ?> pcs Missing
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Card Body -->
                                        <div class="card-body p-3 bg-white d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div style="min-width: 0;">
                                                        <h6 class="fw-bold text-dark mb-0 text-truncate" title="<?= e($b['product_name']) ?>">
                                                             <?= e($b['product_name']) ?>
                                                        </h6>
                                                        <small class="text-muted d-block text-truncate">Prog: <?= e($b['program_number']) ?></small>
                                                    </div>
                                                    <span class="badge bg-light text-dark border font-monospace fs-7 ms-2 flex-shrink-0">
                                                        <?= number_format($totalQty) ?> pcs
                                                    </span>
                                                </div>

                                                <!-- Live Inward & Pending Scan Breakdown Strip -->
                                                <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light border mb-2 gap-1 flex-wrap fs-8 font-monospace">
                                                    <span class="text-success fw-bold" title="Scanned and credited to stock">
                                                        <i class="bi bi-check-circle-fill me-1"></i>Scanned: <?= number_format($recQty) ?> pcs
                                                    </span>
                                                    <span class="<?= $misQty > 0 ? 'text-danger fw-bold' : 'text-muted' ?>" title="Pending scan / Missing">
                                                        <i class="bi bi-hourglass-split me-1"></i>Pending: <?= number_format($misQty) ?> pcs
                                                    </span>
                                                    <?php if ($dmgQty > 0): ?>
                                                        <span class="text-danger fw-bold" title="Damaged / Quarantined">
                                                            <i class="bi bi-slash-circle-fill me-1"></i>Dmg: <?= number_format($dmgQty) ?> pcs
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Source Production Unit Badge Box -->
                                                <div class="p-2 mb-2 source-badge-box d-flex align-items-center justify-content-between gap-1">
                                                    <span class="text-dark fs-8 fw-bold text-truncate"><i class="bi bi-gear-wide-connected me-1 text-warning"></i>Source:</span>
                                                    <span class="badge bg-warning text-dark font-monospace fs-8 text-truncate" title="<?= e($b['production_unit_name'] ?? 'Main Facility') ?> (<?= e($b['production_unit_code'] ?? 'PU1') ?>)">
                                                        <i class="bi bi-building me-1"></i><?= e($b['production_unit_name'] ?? 'Main Facility') ?> (<?= e($b['production_unit_code'] ?? 'PU1') ?>)
                                                    </span>
                                                </div>

                                                <!-- Receiving Warehouse Badge Box -->
                                                <div class="p-2 mb-2 dest-badge-box d-flex align-items-center justify-content-between gap-1">
                                                    <span class="text-primary fs-8 fw-bold text-truncate"><i class="bi bi-geo-alt-fill me-1"></i>Destination:</span>
                                                    <span class="badge bg-primary text-white font-monospace fs-8 text-truncate" title="<?= e($b['warehouse_name']) ?> (<?= e($b['warehouse_code']) ?>)">
                                                        <?= e($b['warehouse_name']) ?> (<?= e($b['warehouse_code']) ?>)
                                                    </span>
                                                </div>

                                                <!-- Variant Breakdown Box with Scanned / Pending Counts -->
                                                <div class="p-2 rounded bg-light border mb-2 variant-scroll-box">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small class="text-muted fw-bold fs-8 text-uppercase">
                                                            <i class="bi bi-tags-fill me-1 text-secondary"></i>Product Variants (<?= count($itemsList) ?>):
                                                        </small>
                                                        <small class="text-muted fs-8 font-monospace">
                                                            Scan / Pend / Tot
                                                        </small>
                                                    </div>
                                                    <?php if (!empty($itemsList)): ?>
                                                        <?php foreach ($itemsList as $v): ?>
                                                            <?php
                                                            $vExp = (int) ($v['quantity'] ?? 0);
                                                            $vRec = (int) ($v['received_qty'] ?? ($b['status'] === 'RECEIVED' ? $vExp : 0));
                                                            $vDmg = (int) ($v['damaged_qty'] ?? 0);
                                                            $vMis = isset($v['missing_qty']) ? (int) $v['missing_qty'] : max(0, $vExp - ($vRec + $vDmg));
                                                            ?>
                                                            <div class="d-flex justify-content-between align-items-center bg-white px-2 py-1.5 rounded border mb-1 fs-8">
                                                                <div class="text-truncate me-1" style="min-width: 0;" title="<?= e($v['sku']) ?>">
                                                                    <strong class="font-monospace text-dark"><?= e($v['sku']) ?></strong>
                                                                    <small class="text-muted d-block text-truncate">(<?= e($v['size_name'] ?? 'Std') ?>, <?= e($v['color_name'] ?? 'Std') ?>)</small>
                                                                </div>
                                                                <div class="d-flex align-items-center gap-1 flex-shrink-0 font-monospace">
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle" title="Verified / Scanned Qty">
                                                                        <i class="bi bi-check-lg me-0.5"></i><?= $vRec ?>
                                                                    </span>
                                                                    <span class="badge <?= $vMis > 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-muted border' ?>" title="Pending Scan Qty">
                                                                        <i class="bi bi-hourglass-split me-0.5"></i><?= $vMis ?>
                                                                    </span>
                                                                    <?php if ($vDmg > 0): ?>
                                                                        <span class="badge bg-danger text-white" title="Damaged Qty">
                                                                            <i class="bi bi-slash-circle me-0.5"></i><?= $vDmg ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                    <span class="badge bg-light text-dark border ms-0.5" title="Expected Total">
                                                                        / <?= $vExp ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted fs-8">No variant lines logged</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center fs-8 text-muted pt-1 border-top">
                                                <span><i class="bi bi-box-seam me-1"></i><?= number_format($b['box_count'] ?? 1) ?> Cartons</span>
                                                <span><i class="bi bi-calendar3 me-1"></i><?= !empty($b['dispatch_date']) ? date('d M, h:i A', strtotime($b['dispatch_date'])) : 'Pending' ?></span>
                                            </div>
                                        </div>

                                        <!-- Card Footer -->
                                        <div class="card-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-info py-1 px-2 btn-batch-history" data-batch-id="<?= $b['id'] ?>" data-batch-number="<?= e($b['batch_number']) ?>" title="Batch History &amp; Traceability Logs">
                                                <i class="bi bi-clock-history"></i> Logs
                                            </button>
                                            <a href="<?= url('warehouse/inward/view/' . $b['id']) ?>" class="btn btn-sm <?= ($canVerify && $misQty > 0) ? 'btn-danger fw-semibold' : 'btn-outline-primary' ?> flex-grow-1 py-1">
                                                <i class="bi <?= ($canVerify && $misQty > 0) ? 'bi-qr-code-scan' : 'bi-eye' ?> me-1"></i>
                                                <?= ($canVerify && $misQty > 0) ? 'Cross-Check &amp; Inward' : 'View Inward' ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="noBatchCardsMatch" class="text-center py-5 d-none text-muted">
                            <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                            <h6 class="text-dark">No matching batch cards found</h6>
                            <p class="small text-muted mb-3">Try adjusting your search terms or filter selection.</p>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnResetCardFilters">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Search &amp; Filters
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div> <!-- /.inward-wrapper -->

<!-- ========================================================================= -->
<!-- MODAL: BATCH LIFECYCLE HISTORY & TRACEABILITY LOGS -->
<!-- ========================================================================= -->
<div class="modal fade" id="batchHistoryModal" tabindex="-1" aria-labelledby="batchHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="batchHistoryModalLabel">Batch Traceability &amp; Lifecycle Logs</h5>
                        <small class="text-white-50" id="batchHistorySubTitle">Complete manufacturing &amp; inward audit trail</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light" id="batchHistoryBody">
                <div class="text-center py-5" id="batchHistoryLoading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">Loading batch lifecycle &amp; production unit logs...</p>
                </div>
                <div id="batchHistoryContent" class="d-none">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: CAMERA QR SCANNER -->
<!-- ========================================================================= -->
<div class="modal fade" id="cameraScanModal" tabindex="-1" aria-labelledby="cameraScanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="cameraScanModalLabel">
                    <i class="bi bi-camera me-2 text-primary"></i> Live Camera QR Scanner
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnCloseCam"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div id="cameraReader" style="width: 100%; min-height: 250px; background:#000; border-radius: 8px;"></div>
                <div class="mt-3">
                    <small class="text-muted">Point your camera at the Batch or Outward QR code.</small>
                </div>
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
                <h6 class="modal-title fw-bold" id="qrModalTitle">Batch QR Code</h6>
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
    // =========================================================================
    // 1. TAB VIEW PERSISTENCE & SWITCHING
    // =========================================================================
    const TAB_STORAGE_KEY = 'grm_inward_view_mode';
    const listTabBtn = document.getElementById('tab-list-btn');
    const cardsTabBtn = document.getElementById('tab-cards-btn');

    function activateTab(tabName) {
        if (tabName === 'cards' && cardsTabBtn) {
            const tab = bootstrap.Tab.getInstance(cardsTabBtn) || new bootstrap.Tab(cardsTabBtn);
            tab.show();
        } else if (listTabBtn) {
            const tab = bootstrap.Tab.getInstance(listTabBtn) || new bootstrap.Tab(listTabBtn);
            tab.show();
        }
    }

    // Check URL hash first, then localStorage
    const hash = window.location.hash;
    if (hash === '#tab-cards-view' || hash === '#cards') {
        activateTab('cards');
    } else if (hash === '#tab-list-view' || hash === '#list') {
        activateTab('list');
    } else {
        const savedTab = localStorage.getItem(TAB_STORAGE_KEY) || 'list';
        activateTab(savedTab);
    }

    if (listTabBtn) {
        listTabBtn.addEventListener('shown.bs.tab', function() {
            localStorage.setItem(TAB_STORAGE_KEY, 'list');
        });
    }
    if (cardsTabBtn) {
        cardsTabBtn.addEventListener('shown.bs.tab', function() {
            localStorage.setItem(TAB_STORAGE_KEY, 'cards');
        });
    }

    // =========================================================================
    // 2. LIVE QR SCANNER PROCESSING
    // =========================================================================
    const qrInput = document.getElementById('qrInput');
    const btnSubmit = document.getElementById('btnScanSubmit');
    const autoSwitch = document.getElementById('autoReceiveSwitch');
    const switchHelp = document.getElementById('switchHelpText');
    const feedbackBox = document.getElementById('scanFeedback');
    let html5QrScanner = null;

    // Toggle switch text
    if (autoSwitch && switchHelp) {
        autoSwitch.addEventListener('change', function() {
            if (this.checked) {
                switchHelp.innerHTML = '<strong class="text-success">ON:</strong> Auto-credits inventory on scan.';
            } else {
                switchHelp.innerHTML = '<strong class="text-warning text-dark">OFF:</strong> Manual review mode before stock update.';
            }
        });
    }

    // Handle QR Input Enter key (for barcode/QR handheld guns)
    if (qrInput) {
        qrInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                processScannedCode(qrInput.value);
            }
        });
    }

    if (btnSubmit) {
        btnSubmit.addEventListener('click', function() {
            processScannedCode(qrInput ? qrInput.value : '');
        });
    }

    function processScannedCode(code) {
        code = (code || '').trim();
        if (!code) {
            showFeedback('warning', 'Please enter or scan a valid QR code.');
            return;
        }

        const isAuto = autoSwitch ? autoSwitch.checked : true;
        const whSelect = document.getElementById('scanWarehouseId');
        const selectedWh = whSelect ? whSelect.value : '';

        showFeedback('info', '<div class="spinner-border spinner-border-sm me-2"></div> Processing QR Code: <strong>' + escapeHtml(code) + '</strong>...');

        const formData = new FormData();
        formData.append('qr_code', code);
        formData.append('warehouse_id', selectedWh);
        formData.append('auto_receive', isAuto ? '1' : '0');
        formData.append('_csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

        fetch('<?= url('api/warehouse/inward/scan') ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                playAudioBeep(false);
                showFeedback('danger', '<i class="bi bi-exclamation-triangle-fill me-2"></i> ' + (data.message || 'Batch not found.'));
                return;
            }

            const batch = data.data.batch;
            const whText = batch.warehouse_name ? `<span class="badge bg-primary fs-7"><i class="bi bi-building me-1"></i>Credited to: ${escapeHtml(batch.warehouse_name)} ${batch.warehouse_code ? '(' + escapeHtml(batch.warehouse_code) + ')' : ''}</span>` : '';

            if (data.data.already_received) {
                playAudioBeep(false);
                showFeedback('warning', '<div class="d-flex align-items-center justify-content-between flex-wrap gap-2"><div><i class="bi bi-exclamation-octagon-fill me-2 fs-5 text-warning"></i> <strong>ALREADY INWARDED:</strong> ' + escapeHtml(data.message) + ' ' + whText + '</div> <a href="' + data.data.viewUrl + '" class="btn btn-sm btn-outline-dark ms-2">View Inward</a></div>');
            } else if (data.data.credited) {
                playAudioBeep(true);
                // Auto-received!
                showFeedback('success', '<div class="d-flex align-items-center justify-content-between flex-wrap gap-2"><div><i class="bi bi-check-circle-fill me-2 fs-5"></i> <strong>SUCCESS!</strong> ' + escapeHtml(data.message) + ' <div class="mt-1">' + whText + ' <span class="badge bg-dark font-monospace fs-7 ms-1">Batch #' + escapeHtml(batch.batch_number) + '</span> <span class="badge bg-success font-monospace fs-7 ms-1">' + (batch.total_qty || 0) + ' pcs</span></div></div> <a href="' + data.data.viewUrl + '" class="btn btn-sm btn-dark ms-2">View Inward</a></div>');
                // Update row in table if present
                const row = document.getElementById('row-batch-' + batch.id);
                if (row) {
                    const badge = row.querySelector('.batch-status-badge');
                    if (badge) {
                        badge.className = 'badge bg-success batch-status-badge';
                        badge.innerHTML = '<i class="bi bi-check2-all me-1"></i>Received &amp; Stocked';
                    }
                }
            } else {
                // Manual mode: Redirect or prompt view
                showFeedback('info', '<i class="bi bi-eye-fill me-2"></i> Batch <strong>' + escapeHtml(batch.batch_number) + '</strong> found (' + (batch.total_qty || 0) + ' pcs). Destination: ' + whText + ' <a href="' + data.data.viewUrl + '" class="btn btn-sm btn-success ms-2">Inspect &amp; Inward Now &rarr;</a>');
                setTimeout(() => {
                    window.location.href = data.data.viewUrl;
                }, 800);
            }

            if (qrInput) {
                qrInput.value = '';
                qrInput.focus();
            }
        })
        .catch(err => {
            playAudioBeep(false);
            showFeedback('danger', 'Server communication error: ' + err.message);
        });
    }

    function showFeedback(type, html) {
        if (!feedbackBox) return;
        feedbackBox.className = 'mt-3 alert alert-' + type + ' d-flex align-items-center justify-content-between';
        feedbackBox.innerHTML = '<div>' + html + '</div><button type="button" class="btn-close" onclick="this.parentElement.classList.add(\'d-none\')"></button>';
        feedbackBox.classList.remove('d-none');
    }

    // Audio Beep generator using Web Audio API
    function playAudioBeep(isSuccess) {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = isSuccess ? 880 : 330;
            gain.gain.value = 0.15;
            osc.start();
            setTimeout(() => { osc.stop(); ctx.close(); }, isSuccess ? 120 : 300);
        } catch(e) {}
    }

    // Camera QR Scanner Modal
    const camModal = document.getElementById('cameraScanModal');
    if (camModal) {
        camModal.addEventListener('shown.bs.modal', function() {
            if (!html5QrScanner && typeof Html5QrcodeScanner !== 'undefined') {
                html5QrScanner = new Html5QrcodeScanner("cameraReader", { fps: 10, qrbox: 250 });
                html5QrScanner.render(function(decodedText) {
                    bootstrap.Modal.getInstance(camModal).hide();
                    processScannedCode(decodedText);
                }, function(err) {});
            }
        });

        camModal.addEventListener('hidden.bs.modal', function() {
            if (html5QrScanner) {
                try { html5QrScanner.clear(); } catch(e){}
                html5QrScanner = null;
            }
        });
    }

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

    // =========================================================================
    // 3. LIVE CLIENT-SIDE INWARD SEARCH & INSTANT FILTERING (AS YOU TYPE)
    // =========================================================================
    const mainSearchInput = document.getElementById('mainInwardSearch');
    const unitSelect = document.getElementById('inwardProductionUnitSelect');
    const warehouseSelect = document.getElementById('inwardWarehouseSelect');
    const statusSelect = document.getElementById('inwardStatusSelect');
    const tableRows = document.querySelectorAll('.inward-batch-row');
    const batchCardItems = document.querySelectorAll('.batch-card-item');
    const showingBadge = document.getElementById('inwardShowingCountBadge');
    const tableBody = document.querySelector('#inwardBatchesTable tbody');
    const batchFilterBtns = document.querySelectorAll('.btn-batch-filter');
    const batchSearchInput = document.getElementById('batchCardSearch');
    const noBatchMatch = document.getElementById('noBatchCardsMatch');
    const btnResetCards = document.getElementById('btnResetCardFilters');

    let currentCardFilter = 'all';

    function performLiveInwardFilter() {
        const mainQuery = (mainSearchInput ? mainSearchInput.value : '').toLowerCase().trim();
        const cardQuery = (batchSearchInput ? batchSearchInput.value : '').toLowerCase().trim();
        const query = mainQuery || cardQuery;

        const selUnit = (unitSelect ? unitSelect.value : '').trim();
        const selWarehouse = (warehouseSelect ? warehouseSelect.value : '').trim();
        const selStatus = (statusSelect ? statusSelect.value : '').trim();

        let visibleTableCount = 0;
        tableRows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            const unitId = row.getAttribute('data-unit-id') || '';
            const whId = row.getAttribute('data-warehouse-id') || '';
            const st = row.getAttribute('data-status') || '';

            const matchesSearch = !query || searchData.includes(query);
            const matchesUnit = !selUnit || unitId === selUnit;
            const matchesWh = !selWarehouse || whId === selWarehouse;
            const matchesStatus = !selStatus || st === selStatus;

            if (matchesSearch && matchesUnit && matchesWh && matchesStatus) {
                row.style.display = '';
                visibleTableCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (showingBadge) {
            showingBadge.textContent = `Showing ${visibleTableCount} Dispatches`;
        }

        let noMatchRow = document.getElementById('noBatchTableMatches');
        if (visibleTableCount === 0 && tableRows.length > 0) {
            if (!noMatchRow && tableBody) {
                noMatchRow = document.createElement('tr');
                noMatchRow.id = 'noBatchTableMatches';
                noMatchRow.innerHTML = `<td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-search fs-2 d-block mb-2 text-secondary opacity-50"></i>
                    <h6 class="fw-bold text-dark mb-1">No matching batches found</h6>
                    <p class="small text-muted mb-0">No inward shipments match your search criteria. Try a different search term or clear the filter.</p>
                </td>`;
                tableBody.appendChild(noMatchRow);
            }
            if (noMatchRow) noMatchRow.style.display = '';
        } else if (noMatchRow) {
            noMatchRow.style.display = 'none';
        }

        let visibleCardsCount = 0;
        batchCardItems.forEach(card => {
            const searchData = card.getAttribute('data-search') || '';
            const unitId = card.getAttribute('data-unit-id') || '';
            const whId = card.getAttribute('data-warehouse-id') || '';
            const st = card.getAttribute('data-inward-status') || '';
            const cardStatus = card.getAttribute('data-status') || '';

            const matchesSearch = !query || searchData.includes(query);
            const matchesUnit = !selUnit || unitId === selUnit;
            const matchesWh = !selWarehouse || whId === selWarehouse;
            const matchesStatus = !selStatus || st === selStatus;
            const matchesCardFilter = (currentCardFilter === 'all') || (currentCardFilter === cardStatus);

            if (matchesSearch && matchesUnit && matchesWh && matchesStatus && matchesCardFilter) {
                card.classList.remove('d-none');
                visibleCardsCount++;
            } else {
                card.classList.add('d-none');
            }
        });

        if (noBatchMatch) {
            if (visibleCardsCount === 0 && batchCardItems.length > 0) {
                noBatchMatch.classList.remove('d-none');
            } else {
                noBatchMatch.classList.add('d-none');
            }
        }
    }

    if (mainSearchInput) {
        mainSearchInput.addEventListener('input', performLiveInwardFilter);
        mainSearchInput.addEventListener('keyup', performLiveInwardFilter);
    }
    if (unitSelect) {
        unitSelect.addEventListener('change', performLiveInwardFilter);
    }
    if (warehouseSelect) {
        warehouseSelect.addEventListener('change', performLiveInwardFilter);
    }
    if (statusSelect) {
        statusSelect.addEventListener('change', performLiveInwardFilter);
    }

    if (batchFilterBtns.length > 0) {
        batchFilterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                batchFilterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentCardFilter = this.getAttribute('data-filter') || 'all';
                performLiveInwardFilter();
            });
        });
    }

    if (batchSearchInput) {
        batchSearchInput.addEventListener('input', function() {
            if (mainSearchInput && !mainSearchInput.value) {
                performLiveInwardFilter();
            }
        });
    }

    if (btnResetCards) {
        btnResetCards.addEventListener('click', function() {
            if (batchSearchInput) batchSearchInput.value = '';
            if (mainSearchInput) mainSearchInput.value = '';
            currentCardFilter = 'all';
            batchFilterBtns.forEach(b => {
                if (b.getAttribute('data-filter') === 'all') b.classList.add('active');
                else b.classList.remove('active');
            });
            performLiveInwardFilter();
        });
    }

    // =========================================================================
    // 4. BATCH HISTORY & TRACEABILITY MODAL LOADER
    // =========================================================================
    const historyModalEl = document.getElementById('batchHistoryModal');
    const historyLoading = document.getElementById('batchHistoryLoading');
    const historyContent = document.getElementById('batchHistoryContent');
    const historySubTitle = document.getElementById('batchHistorySubTitle');

    document.querySelectorAll('.btn-batch-history').forEach(btn => {
        btn.addEventListener('click', function() {
            const batchId = this.getAttribute('data-batch-id');
            const batchNumber = this.getAttribute('data-batch-number') || ('#' + batchId);
            openBatchHistoryModal(batchId, batchNumber);
        });
    });

    window.openBatchHistoryModal = function(batchId, batchNumber) {
        historySubTitle.textContent = 'Traceability & Production Unit history for ' + batchNumber;
        historyLoading.classList.remove('d-none');
        historyContent.classList.add('d-none');
        historyContent.innerHTML = '';

        const modal = new bootstrap.Modal(historyModalEl);
        modal.show();

        fetch('<?= url('api/warehouse/inward/batch-history/') ?>' + batchId)
            .then(res => res.json())
            .then(res => {
                historyLoading.classList.add('d-none');
                if (!res.success || !res.data) {
                    historyContent.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> ${res.message || 'Failed to load batch history.'}</div>`;
                    historyContent.classList.remove('d-none');
                    return;
                }

                const d = res.data;
                const batch = d.batch || {};
                const prog = d.program || {};
                const rolls = d.rolls || [];
                const cutting = d.cutting || [];
                const stitching = d.stitching || [];
                const qc = d.qc || null;
                const outward = d.outward || null;
                const txns = d.transactions || [];
                const items = d.items || [];

                let html = `
                    <!-- Header Overview Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Production Batch Number</span>
                                    <h5 class="fw-bold font-monospace text-primary mb-0">${escapeHtml(batch.batch_number || '')}</h5>
                                    <small class="text-muted">Product: <strong>${escapeHtml(batch.product_name || 'N/A')}</strong></small>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 rounded bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                        <small class="text-muted d-block fw-semibold"><i class="bi bi-gear-wide-connected text-warning me-1"></i>Origin Manufacturing Unit:</small>
                                        <strong class="text-dark font-monospace">${escapeHtml(batch.production_unit_name || prog.unit_name || 'Main Production Facility')} (${escapeHtml(batch.production_unit_code || prog.unit_code || 'PU1')})</strong>
                                        <div class="small text-muted">${escapeHtml(batch.production_unit_manager || prog.unit_manager || 'Lead Supervisor')} ${batch.production_unit_phone ? ' &bull; ' + escapeHtml(batch.production_unit_phone) : ''}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 rounded bg-primary bg-opacity-10 border border-primary border-opacity-25">
                                        <small class="text-muted d-block fw-semibold"><i class="bi bi-building text-primary me-1"></i>Destination Warehouse:</small>
                                        <strong class="text-primary font-monospace">${escapeHtml(batch.warehouse_name || '')} (${escapeHtml(batch.warehouse_code || '')})</strong>
                                        <div class="small text-muted">Status: <span class="badge ${batch.status === 'RECEIVED' ? 'bg-success' : 'bg-warning text-dark'} font-monospace">${escapeHtml(batch.status || '')}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Timeline -->
                    <div class="timeline-wrapper position-relative ps-4 border-start border-3 border-primary ms-3">
                `;

                // 1. Program & Unit Assignment
                html += `
                    <div class="timeline-step position-relative mb-4">
                        <span class="timeline-icon position-absolute rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width:34px; height:34px; left:-38px; top:0;">
                            <i class="bi bi-clipboard-data fs-6"></i>
                        </span>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                <strong class="text-dark"><i class="bi bi-calendar2-check text-primary me-1"></i>Stage 1: Production Program &amp; Facility Assignment</strong>
                                <span class="badge bg-light text-dark border">${escapeHtml(prog.created_at || 'Logged')}</span>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-2 small">
                                    <div class="col-sm-6 col-md-3"><strong>Program #:</strong> <span class="font-monospace text-primary">${escapeHtml(prog.program_number || 'N/A')}</span></div>
                                    <div class="col-sm-6 col-md-3"><strong>Assigned Unit:</strong> <span class="badge bg-warning text-dark">${escapeHtml(prog.unit_name || 'Main Unit')}</span></div>
                                    <div class="col-sm-6 col-md-3"><strong>Planned Target:</strong> ${(parseInt(prog.planned_quantity) > 1 ? prog.planned_quantity : (batch.total_qty || prog.planned_quantity || 0))} pcs <br><small class="text-muted">Dates: ${escapeHtml(prog.start_date || 'N/A')} &rarr; ${escapeHtml(prog.target_date || prog.target_completion_date || prog.end_date || 'N/A')}</small></div>
                                    <div class="col-sm-6 col-md-3"><strong>Supervisor:</strong> ${escapeHtml(prog.assigned_to_name || prog.unit_manager || 'Lead')}</div>
                                </div>
                                ${rolls.length > 0 ? `
                                    <div class="mt-2 pt-2 border-top">
                                        <small class="text-muted fw-bold d-block mb-1"><i class="bi bi-layers me-1"></i>Fabric / Material Rolls Consumed (${rolls.length}):</small>
                                        <div class="d-flex flex-wrap gap-2">
                                            ${rolls.map(r => `
                                                <span class="badge bg-light text-dark border font-monospace p-2">
                                                    Roll #${escapeHtml(r.roll_number || 'N/A')} &bull; Color: ${escapeHtml(r.roll_color || r.sr_color || 'Std')} &bull; ${r.roll_weight || r.sr_weight || 0} kg &bull; Supplier: ${escapeHtml(r.seller_name || 'Mill')}
                                                </span>
                                            `).join('')}
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;

                // 2. Cutting Stage
                if (cutting.length > 0) {
                    html += `
                        <div class="timeline-step position-relative mb-4">
                            <span class="timeline-icon position-absolute rounded-circle bg-info text-white d-flex align-items-center justify-content-center shadow-sm" style="width:34px; height:34px; left:-38px; top:0;">
                                <i class="bi bi-scissors fs-6"></i>
                            </span>
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                    <strong class="text-dark"><i class="bi bi-scissors text-info me-1"></i>Stage 2: Fabric Cutting &amp; CAD Output</strong>
                                    <span class="badge bg-light text-dark border">Cutting Batches: ${cutting.length}</span>
                                </div>
                                <div class="card-body p-3">
                                    ${cutting.map(c => `
                                        <div class="p-2 rounded bg-light border mb-2 small d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div>
                                                <strong>Batch #${escapeHtml(c.batch_number || '')}</strong> &bull; Cut Pcs: <span class="badge bg-success font-monospace">${c.cut_qty || 0} pcs</span>
                                                <div class="text-muted">Operator: ${escapeHtml(c.operator_name || 'Cutting Master')} &bull; Wastage: ${c.wastage_weight || 0} kg</div>
                                            </div>
                                            <span class="badge bg-secondary font-monospace">${escapeHtml(c.status || 'COMPLETED')}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    `;
                }

                // 3. Stitching Stage
                if (stitching.length > 0) {
                    html += `
                        <div class="timeline-step position-relative mb-4">
                            <span class="timeline-icon position-absolute rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm" style="width:34px; height:34px; left:-38px; top:0;">
                                <i class="bi bi-bounding-box fs-6"></i>
                            </span>
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                    <strong class="text-dark"><i class="bi bi-bounding-box text-secondary me-1"></i>Stage 3: Stitching Assembly</strong>
                                    <span class="badge bg-light text-dark border">Assembly Batches: ${stitching.length}</span>
                                </div>
                                <div class="card-body p-3">
                                    ${stitching.map(s => `
                                        <div class="p-2 rounded bg-light border mb-2 small d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div>
                                                <strong>Stitching #${escapeHtml(s.batch_number || '')}</strong> &bull; Output: <span class="badge bg-dark font-monospace">${s.stitched_qty || 0} pcs</span>
                                                <div class="text-muted">Tailor/Worker: ${escapeHtml(s.worker_name || 'Assembly Worker')} &bull; Dates: ${escapeHtml(s.start_date || '')} &rarr; ${escapeHtml(s.end_date || '')}</div>
                                            </div>
                                            <span class="badge bg-secondary font-monospace">${escapeHtml(s.status || 'COMPLETED')}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    `;
                }

                // 4. QC Inspection Stage
                const qcStatusRaw = qc ? (qc.qc_status || qc.status || 'PASSED') : 'PASSED';
                const qcStatusUpper = qcStatusRaw.toUpperCase();
                let qcBadgeClass = 'bg-success';
                if (qcStatusUpper === 'PENDING') qcBadgeClass = 'bg-warning text-dark';
                else if (qcStatusUpper === 'REWORK') qcBadgeClass = 'bg-info text-dark';
                else if (qcStatusUpper === 'REJECTED') qcBadgeClass = 'bg-danger';
                else if (qcStatusUpper === 'PARTIAL' || qcStatusUpper === 'CONDITIONALLY_PASSED') qcBadgeClass = 'bg-warning text-dark';

                html += `
                    <div class="timeline-step position-relative mb-4">
                        <span class="timeline-icon position-absolute rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-sm" style="width:34px; height:34px; left:-38px; top:0;">
                            <i class="bi bi-patch-check-fill fs-6"></i>
                        </span>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                <strong class="text-dark"><i class="bi bi-shield-check text-success me-1"></i>Stage 4: Quality Control (QC Inspection)</strong>
                                <span class="badge ${qcBadgeClass}">${escapeHtml(qcStatusUpper)}</span>
                            </div>
                            <div class="card-body p-3">
                                ${qc ? `
                                    <div class="row g-2 small">
                                        <div class="col-sm-6 col-md-3"><strong>QC Batch #:</strong> <span class="font-monospace text-primary fw-bold">${escapeHtml(qc.batch_number || (qc.id ? ('QC #' + qc.id) : (qc.qc_number || 'QC Batch')))}</span></div>
                                        <div class="col-sm-6 col-md-3"><strong>Inspector:</strong> <span class="text-dark fw-semibold">${escapeHtml(qc.inspector_name || 'System Administrator')}</span></div>
                                        <div class="col-sm-6 col-md-3"><strong>Inspected:</strong> ${qc.inspected_qty || qc.total_qty || 0} pcs</div>
                                        <div class="col-sm-6 col-md-3"><strong>Verdict:</strong> <span class="text-success fw-bold">${qc.passed_qty || 0} Passed</span> &bull; <span class="text-danger">${qc.rejected_qty || 0} Rejected</span> &bull; <span class="text-info fw-bold">${qc.rework_qty || 0} Rework</span></div>
                                    </div>
                                    ${qc.notes ? `<div class="mt-2 text-muted small fst-italic">Notes: ${escapeHtml(qc.notes)}</div>` : ''}
                                ` : `
                                    <div class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>100% Quality inspection verified and passed.</div>
                                `}
                            </div>
                        </div>
                    </div>
                `;

                // 5. Box Packaging & Batch Formation
                html += `
                    <div class="timeline-step position-relative mb-4">
                        <span class="timeline-icon position-absolute rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center shadow-sm" style="width:34px; height:34px; left:-38px; top:0;">
                            <i class="bi bi-box-seam-fill fs-6"></i>
                        </span>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                <strong class="text-dark"><i class="bi bi-box-seam text-warning me-1"></i>Stage 5: Master Carton Packaging &amp; Batch Approval</strong>
                                <span class="badge bg-dark font-monospace">${escapeHtml(batch.batch_number || '')}</span>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-2 small">
                                    <div class="col-sm-6 col-md-3"><strong>Total Pieces:</strong> <span class="badge bg-success font-monospace">${batch.total_qty || 0} pcs</span></div>
                                    <div class="col-sm-6 col-md-3"><strong>Master Cartons:</strong> ${batch.box_count || 1} Boxes</div>
                                    <div class="col-sm-6 col-md-3"><strong>Prepared By:</strong> ${escapeHtml(batch.prepared_by_name || 'Production Lead')}</div>
                                    <div class="col-sm-6 col-md-3"><strong>Approved By:</strong> ${escapeHtml(batch.approved_by_name || 'Plant Manager')} (${escapeHtml(batch.approved_at || batch.created_at || 'Verified')})</div>
                                </div>
                                ${items.length > 0 ? `
                                    <div class="mt-2 pt-2 border-top">
                                        <small class="text-muted fw-bold d-block mb-1">Variant Matrix in Carton:</small>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0 small">
                                                <thead class="table-light">
                                                    <tr><th>SKU</th><th>Size / Color</th><th>Quantity</th><th>Rejected</th><th>Rework</th><th>Unit Price</th></tr>
                                                </thead>
                                                <tbody>
                                                    ${items.map(it => {
                                                        const p = parseFloat(it.qc_discount_price || it.qc_base_price || it.selling_price || it.mrp || 0);
                                                        const pText = p > 0 ? '₹' + p.toFixed(2) : '₹—';
                                                        return `
                                                            <tr>
                                                                <td class="font-monospace fw-bold">${escapeHtml(it.sku || '')}</td>
                                                                <td>${escapeHtml(it.size_name || 'Std')} / ${escapeHtml(it.color_name || 'Std')}</td>
                                                                <td class="fw-bold font-monospace text-success">${it.quantity || 0} pcs</td>
                                                                <td class="text-danger fw-bold">${it.qc_rejected_qty || 0}</td>
                                                                <td class="text-info fw-bold">${it.qc_rework_qty || 0}</td>
                                                                <td>${pText}</td>
                                                            </tr>
                                                        `;
                                                    }).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;

                // 6. Outward Logistics Dispatch
                html += `
                    <div class="timeline-step position-relative mb-4">
                        <span class="timeline-icon position-absolute rounded-circle bg-dark text-white d-flex align-items-center justify-content-center shadow-sm" style="width:34px; height:34px; left:-38px; top:0;">
                            <i class="bi bi-truck fs-6"></i>
                        </span>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                <strong class="text-dark"><i class="bi bi-truck text-primary me-1"></i>Stage 6: Outward Manifest &amp; Transit Logistics</strong>
                                <span class="badge bg-light text-dark border">${outward ? escapeHtml(outward.dispatch_date || 'Dispatched') : escapeHtml(batch.dispatch_date || 'Transit')}</span>
                            </div>
                            <div class="card-body p-3">
                                ${outward ? `
                                    <div class="row g-2 small">
                                        <div class="col-sm-6 col-md-3"><strong>Outward Gate Pass:</strong> <span class="font-monospace text-primary fw-bold">${escapeHtml(outward.outward_number || 'OUT-001')}</span></div>
                                        <div class="col-sm-6 col-md-3"><strong>Vehicle #:</strong> <span class="badge bg-light text-dark border font-monospace">${escapeHtml(outward.vehicle_number || 'Direct')}</span></div>
                                        <div class="col-sm-6 col-md-3"><strong>Driver/Carrier:</strong> ${escapeHtml(outward.driver_name || outward.transporter_name || 'Unit Driver')}</div>
                                        <div class="col-sm-6 col-md-3"><strong>Route:</strong> ${escapeHtml(outward.source_unit_name || batch.production_unit_name || 'Unit')} &rarr; ${escapeHtml(outward.dest_warehouse_name || batch.warehouse_name || 'Warehouse')}</div>
                                    </div>
                                ` : `
                                    <div class="small text-muted">Outward manifest registered from <strong>${escapeHtml(batch.production_unit_name || 'Factory')}</strong> to <strong>${escapeHtml(batch.warehouse_name || 'Warehouse')}</strong>.</div>
                                `}
                            </div>
                        </div>
                    </div>
                `;

                // 7. Warehouse Inward GRN Receipt & Multi-Session Audit History
                const sessions = d.inward_sessions || [];
                const totalExpected = parseInt(batch.total_qty) || 0;
                const totalReceived = parseInt(batch.received_qty) || 0;
                const totalDamaged = parseInt(batch.damaged_qty) || 0;
                const totalMissing = (batch.missing_qty !== undefined && batch.missing_qty !== null) ? parseInt(batch.missing_qty) : Math.max(0, totalExpected - (totalReceived + totalDamaged));
                const isFullyReceived = (batch.status === 'RECEIVED' && totalMissing === 0);
                const isPartial = (totalReceived > 0 && totalMissing > 0);
                const sessionCount = sessions.length;

                let stage7Badge = '<span class="badge bg-warning text-dark font-monospace"><i class="bi bi-hourglass-split me-1"></i>AWAITING GRN SCAN</span>';
                if (isFullyReceived) {
                    stage7Badge = `<span class="badge bg-success font-monospace"><i class="bi bi-check2-all me-1"></i>RECEIVED &amp; 100% STOCKED (${sessionCount > 1 ? sessionCount + ' SESSIONS' : '1 TIME'})</span>`;
                } else if (isPartial) {
                    stage7Badge = `<span class="badge bg-warning text-dark border border-warning font-monospace"><i class="bi bi-exclamation-triangle me-1"></i>PARTIAL INWARD (${sessionCount} SESSIONS &bull; ${totalMissing} SHORT)</span>`;
                }

                html += `
                    <div class="timeline-step position-relative">
                        <span class="timeline-icon position-absolute rounded-circle ${isFullyReceived ? 'bg-success' : (isPartial ? 'bg-warning' : 'bg-secondary')} text-white d-flex align-items-center justify-content-center shadow-sm" style="width:34px; height:34px; left:-38px; top:0;">
                            <i class="bi ${isFullyReceived ? 'bi-building-check text-white' : (isPartial ? 'bi-hourglass-split text-dark' : 'bi-building text-white')} fs-6"></i>
                        </span>
                        <div class="card border-0 shadow-sm border-start ${isFullyReceived ? 'border-success' : (isPartial ? 'border-warning' : 'border-primary')} border-4">
                            <div class="card-header bg-white py-2.5 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <strong class="text-dark fs-6"><i class="bi bi-box-arrow-in-down text-primary me-1"></i>Stage 7: Warehouse Inward &amp; Stock Ledger</strong>
                                    <small class="text-muted d-block font-monospace">Inward Verification Frequency: <strong class="text-primary">${sessionCount > 0 ? sessionCount + ' Time(s) Inwarded' : 'Pending Inward'}</strong></small>
                                </div>
                                ${stage7Badge}
                            </div>
                            <div class="card-body p-3">
                                <!-- Top Inward Summary Metric Strip -->
                                <div class="row g-2 mb-3 text-center">
                                    <div class="col-6 col-md-3">
                                        <div class="p-2 rounded bg-light border">
                                            <span class="text-muted small d-block" style="font-size: 0.72rem;">Outward Expected</span>
                                            <h6 class="fw-bold mb-0 text-dark font-monospace mt-0.5">${totalExpected} pcs</h6>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="p-2 rounded bg-success bg-opacity-10 border border-success border-opacity-25">
                                            <span class="text-success small d-block fw-semibold" style="font-size: 0.72rem;">Inward Credited</span>
                                            <h6 class="fw-bold mb-0 text-success font-monospace mt-0.5">${totalReceived} pcs</h6>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="p-2 rounded ${totalMissing > 0 ? 'bg-warning bg-opacity-10 border border-warning border-opacity-50' : 'bg-light border'}">
                                            <span class="${totalMissing > 0 ? 'text-warning text-dark' : 'text-muted'} small d-block fw-semibold" style="font-size: 0.72rem;">Pending / Missing</span>
                                            <h6 class="fw-bold mb-0 ${totalMissing > 0 ? 'text-danger' : 'text-success'} font-monospace mt-0.5">${totalMissing} pcs</h6>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="p-2 rounded bg-primary bg-opacity-10 border border-primary border-opacity-25">
                                            <span class="text-primary small d-block fw-semibold" style="font-size: 0.72rem;">Inward Frequency</span>
                                            <h6 class="fw-bold mb-0 text-primary font-monospace mt-0.5"><i class="bi bi-arrow-repeat me-1"></i>${sessionCount} Times</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2 small mb-3">
                                    <div class="col-sm-6 col-md-6">
                                        <strong>Receiving Warehouse:</strong> 
                                        <span class="badge bg-primary text-white font-monospace ms-1">${escapeHtml(batch.warehouse_name || '')} (${escapeHtml(batch.warehouse_code || 'WH')})</span>
                                    </div>
                                    <div class="col-sm-6 col-md-6">
                                        <strong>Stock Status:</strong> 
                                        ${isFullyReceived ? '<span class="badge bg-success ms-1"><i class="bi bi-check-circle-fill me-1"></i>100% Stock Credited (Available)</span>' : (isPartial ? '<span class="badge bg-warning text-dark ms-1"><i class="bi bi-exclamation-triangle me-1"></i>Partial Inward In Progress</span>' : '<span class="badge bg-secondary ms-1">Awaiting Inward</span>')}
                                    </div>
                                </div>

                                <!-- Inward Sessions History Table -->
                                ${sessions.length > 0 ? `
                                    <div class="p-3 bg-light rounded border mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                            <strong class="text-dark small"><i class="bi bi-clock-history text-primary me-1"></i>Inward Verification Sessions &amp; Incremental Crediting Logs (${sessions.length} Times Logged):</strong>
                                            <span class="badge bg-dark text-warning font-monospace">${sessions.length} Inward Session(s)</span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover bg-white rounded border align-middle mb-0 font-monospace" style="font-size: 11px;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Session #</th>
                                                        <th>Timestamp</th>
                                                        <th class="text-center">Added (Delta)</th>
                                                        <th class="text-center">Cumulative Credited</th>
                                                        <th class="text-center">Damaged</th>
                                                        <th class="text-center">Pending Shortage</th>
                                                        <th class="text-center">Match %</th>
                                                        <th>Verified By</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${sessions.map(s => `
                                                        <tr>
                                                            <td>
                                                                <span class="badge bg-dark text-warning font-monospace px-2 py-1">${escapeHtml(s.session_title || ('Session #' + s.session_num))}</span>
                                                            </td>
                                                            <td class="text-muted font-monospace">${escapeHtml(s.created_at || 'N/A')}</td>
                                                            <td class="text-center">
                                                                <span class="badge bg-success font-monospace">+${s.incremental_qty} pcs</span>
                                                            </td>
                                                            <td class="text-center fw-bold text-dark font-monospace">
                                                                ${s.cumulative_accepted} / ${s.expected_qty} pcs
                                                            </td>
                                                            <td class="text-center">
                                                                ${s.cumulative_damaged > 0 ? `<span class="badge bg-danger">${s.cumulative_damaged} pcs</span>` : '<span class="text-muted">0</span>'}
                                                            </td>
                                                            <td class="text-center">
                                                                ${s.missing_qty > 0 ? `<span class="badge bg-warning text-dark font-monospace">${s.missing_qty} pcs short</span>` : '<span class="badge bg-success font-monospace">0 (Matched)</span>'}
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge ${s.match_pct >= 100 ? 'bg-success' : 'bg-info'} font-monospace">${s.match_pct}%</span>
                                                            </td>
                                                            <td>
                                                                <span class="fw-semibold text-dark"><i class="bi bi-person-check me-1 text-primary"></i>${escapeHtml(s.verified_by || 'Admin')}</span>
                                                            </td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}

                                <!-- Stock Ledger Transactions Table -->
                                ${txns.length > 0 ? `
                                    <div class="mt-2 pt-2 border-top">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-muted small"><i class="bi bi-receipt me-1 text-primary"></i>Stock Ledger Postings (${txns.length} Transaction Records):</strong>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered bg-white mb-0 font-monospace" style="font-size: 11px;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Txn #</th>
                                                        <th>Variant SKU</th>
                                                        <th>Stock State</th>
                                                        <th>Warehouse</th>
                                                        <th class="text-end">Qty Added</th>
                                                        <th>Balance After</th>
                                                        <th>Date &amp; Time</th>
                                                        <th>Reference Notes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${txns.map(t => `
                                                        <tr>
                                                            <td>TXN-${t.id}</td>
                                                            <td class="fw-bold text-primary">${escapeHtml(t.sku || t.variant_sku || 'Item')}</td>
                                                            <td><span class="badge ${t.stock_state === 'DAMAGED' ? 'bg-danger' : 'bg-success'}">${escapeHtml(t.stock_state || 'AVAILABLE')}</span></td>
                                                            <td>${escapeHtml(t.warehouse_name || '')}</td>
                                                            <td class="text-end ${parseFloat(t.quantity) >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold'}">${parseFloat(t.quantity) > 0 ? '+' : ''}${parseFloat(t.quantity)} pcs</td>
                                                            <td class="text-end font-monospace">${parseFloat(t.balance_after || 0)} pcs</td>
                                                            <td>${escapeHtml(t.created_at || '')}</td>
                                                            <td class="text-muted text-truncate" style="max-width: 200px;">${escapeHtml(t.notes || '')}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;

                html += `</div>`;
                historyContent.innerHTML = html;
                historyContent.classList.remove('d-none');
            })
            .catch(err => {
                historyLoading.classList.add('d-none');
                historyContent.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Error loading batch history: ${escapeHtml(err.message)}</div>`;
                historyContent.classList.remove('d-none');
            });
    };

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
});
</script>
