<?php
$canReceive = hasPermission('website_inward', 'receive');
?>
<style>
/* Responsive Container & Layout Constraints */
.inward-wrapper {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

/* Custom Styling for Inward View Tabs and Batch Cards */
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

    <!-- Page Header -->
    <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1 rounded-pill fw-semibold fs-7">
                    <i class="bi bi-globe me-1"></i> Website Orders
                </span>
                <span class="text-muted small">|</span>
                <span class="text-muted small">Channel Stock Receiving & GRN Management</span>
            </div>
            <h2 class="mb-0 fs-4 fw-bold text-dark">
                <i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Website Inward & QR Receiving
            </h2>
        </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- 1. SUMMARY METRIC CARDS -->
    <!-- ========================================================================= -->
    <div class="row g-3 mb-3">
        <!-- 1. Awaiting GRN -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div style="min-width: 0;">
                            <span class="text-muted small fw-bold text-uppercase d-block text-truncate metric-card-title" style="font-size: 0.75rem;">Awaiting GRN (In-Transit Stock)</span>
                            <h4 class="mb-0 fw-bold text-danger mt-1 metric-card-value">
                                <?= number_format($pendingBatchesCount ?? 0) ?> <small class="fs-6 text-muted fw-normal">Allocations</small>
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
                            <span class="text-muted small fw-bold text-uppercase d-block text-truncate metric-card-title" style="font-size: 0.75rem;">Received &amp; Channel Credited</span>
                            <h4 class="mb-0 fw-bold text-success mt-1 metric-card-value">
                                <?= number_format($receivedBatchesCount ?? 0) ?> <small class="fs-6 text-muted fw-normal">Allocations</small>
                            </h4>
                            <small class="text-success fw-semibold mt-1 d-block text-truncate">
                                <i class="bi bi-check2-circle me-1"></i><?= number_format($totalReceivedQty ?? 0) ?> pcs in Website stock
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
                                <?= number_format(count($allocations ?? [])) ?> <small class="fs-6 text-muted fw-normal">Allocations</small>
                            </h4>
                            <small class="text-muted mt-1 d-block text-truncate">
                                <i class="bi bi-boxes me-1"></i><?= number_format($totalDispatchedQty ?? 0) ?> total dispatched pcs
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

    <!-- Allocation scanning is handled from each batch's inward verification page. -->

    <!-- ========================================================================= -->
    <!-- 3. VIEW MODE SELECTION TABS -->
    <!-- ========================================================================= -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2 p-md-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-bold text-dark small text-uppercase d-none d-sm-inline"><i class="bi bi-layout-split me-1 text-primary"></i>View Mode:</span>
                    <ul class="nav nav-pills custom-inward-pills" id="inwardViewTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active d-flex align-items-center gap-2" id="tab-list-btn" data-bs-toggle="pill" data-bs-target="#tab-list-view" type="button" role="tab">
                                <i class="bi bi-list-task fs-6"></i>
                                <span>List / Ledger View</span>
                                <span class="badge bg-secondary"><?= count($batches ?? []) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link d-flex align-items-center gap-2" id="tab-cards-btn" data-bs-toggle="pill" data-bs-target="#tab-cards-view" type="button" role="tab">
                                <i class="bi bi-grid-fill fs-6"></i>
                                <span>Allocation Cards View</span>
                                <?php if (($pendingBatchesCount ?? 0) > 0): ?>
                                    <span class="badge bg-danger"><?= $pendingBatchesCount ?> Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= count($batches ?? []) ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="text-muted fs-8 d-none d-lg-block">
                    <i class="bi bi-info-circle me-1"></i>View preference is automatically saved.
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
        <div class="tab-pane fade show active" id="tab-list-view" role="tabpanel">
            
            <!-- Filters Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3">
                    <form method="GET" action="<?= url('warehouse/website-orders/inward') ?>" class="row g-2 align-items-end" id="inwardFilterForm">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small fw-semibold text-muted mb-1">Search Filter</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                                <input type="text" name="search" id="inwardSearchInput" class="form-control form-control-sm" placeholder="Search allocation, outward, product, SKU..." value="<?= htmlspecialchars($search ?? '') ?>" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label small fw-semibold text-muted mb-1">Source Warehouse</label>
                            <select name="warehouse_id" id="inwardWarehouseSelect" class="form-select form-select-sm">
                                <option value="">All Warehouses</option>
                                <?php foreach (($warehouses ?? []) as $warehouse): ?>
                                    <option value="<?= (int)$warehouse['id'] ?>" <?= (string)($warehouseId ?? '') === (string)$warehouse['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($warehouse['name']) ?><?= !empty($warehouse['code']) ? ' (' . htmlspecialchars($warehouse['code']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom ?? '') ?>">
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo ?? '') ?>">
                        </div>
                        <div class="col-12 col-lg-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                                <i class="bi bi-funnel me-1"></i> Filter
                            </button>
                            <a href="<?= url('warehouse/website-orders/inward') ?>" class="btn btn-outline-secondary btn-sm px-2">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Detailed Inward Ledger Table -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-table me-2 text-primary"></i>Website Inward Dispatches Ledger</h6>
                    <span class="badge bg-light text-dark border font-monospace fs-8" id="inwardTableCountBadge">Showing <?= count($batches ?? []) ?> Dispatches</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="min-width: 850px;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Batch / Allocation #</th>
                                <th>Program &amp; Product SKUs</th>
                                <th>Source Unit &rarr; Destination</th>
                                <th>Quantity &amp; Boxes</th>
                                <th>Dispatch Date &amp; Vehicle</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="inwardTableBody">
                            <?php if (empty($batches)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        No inward allocations found for Website Orders.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($batches as $b): ?>
                                    <?php 
                                        $bTot = (int)($b['total_quantity'] ?? $b['quantity'] ?? 0);
                                        $bRec = (int)($b['received_qty'] ?? 0);
                                        $bDam = (int)($b['damaged_qty'] ?? 0);
                                        $bState = $b['status_state'] ?? (in_array(strtoupper($b['status'] ?? ''), ['COMPLETED', 'RECEIVED', 'DELIVERED']) ? 'RECEIVED' : ($bRec > 0 ? 'PARTIAL' : 'IN_TRANSIT'));
                                        $bIsCredited = ($bState === 'RECEIVED');
                                        $bMis = (int)($b['missing_qty'] ?? max(0, $bTot - $bRec - $bDam));
                                        $bItems = $b['items'] ?? [];
                                        $bNum = $b['allocation_number'] ?? ('ALC-' . str_pad($b['id'], 5, '0', STR_PAD_LEFT));
                                        $bOutward = $b['outward_number'] ?? ('OUT-' . str_pad($b['id'], 5, '0', STR_PAD_LEFT));
                                        $skuSearchTerms = implode(' ', array_map(function($item) { return ($item['sku'] ?? '') . ' ' . ($item['size_name'] ?? '') . ' ' . ($item['color_name'] ?? '') . ' ' . ($item['product_name'] ?? ''); }, $bItems));
                                        $searchHaystack = strtolower(htmlspecialchars($bNum . ' ' . $bOutward . ' ' . ($b['vehicle_number'] ?? '') . ' ' . ($b['driver_name'] ?? '') . ' ' . ($b['warehouse_name'] ?? '') . ' ' . $skuSearchTerms, ENT_QUOTES, 'UTF-8'));
                                        $bTitle = ($bItems[0]['product_name'] ?? null) ? $bItems[0]['product_name'] : 'Assorted Garments & Merchandise';
                                    ?>
                                    <tr id="row-batch-<?= $b['id'] ?>" class="inward-alloc-row" data-search="<?= $searchHaystack ?>" data-status="<?= $bIsCredited ? 'RECEIVED' : ($bState === 'PARTIAL' ? 'PARTIAL_RECEIVED' : 'DISPATCHED') ?>" data-warehouse-id="<?= (int)($b['warehouse_id'] ?? 0) ?>">
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div>
                                                    <span class="fw-bold font-monospace text-primary fs-7 d-block"><?= htmlspecialchars($bNum) ?></span>
                                                    <span class="badge bg-light text-dark border font-monospace fs-9 mt-1"><i class="bi bi-box-seam me-1"></i><?= htmlspecialchars($bOutward) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark fs-7 d-block text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($bTitle) ?>"><?= htmlspecialchars($bTitle) ?></span>
                                            <small class="text-muted font-monospace fs-9 d-block mt-1">
                                                <?= count($bItems) ?> SKU(s) <?= !empty($bItems[0]['sku']) ? '(' . htmlspecialchars($bItems[0]['sku']) . ')' : '' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted d-block fs-8"><i class="bi bi-building me-1 text-secondary"></i>From: <strong><?= htmlspecialchars($b['warehouse_name'] ?? 'Main Warehouse') ?></strong> <span class="badge bg-secondary bg-opacity-10 text-secondary border font-monospace"><?= htmlspecialchars($b['warehouse_code'] ?? 'WH-01') ?></span></small>
                                            <small class="text-primary d-block fs-8 mt-1"><i class="bi bi-globe me-1"></i>To: <strong><?= htmlspecialchars($b['channel_name'] ?? 'Website Channel') ?></strong> <span class="badge bg-primary bg-opacity-25 text-primary border font-monospace">WEB-01</span></small>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark fs-6"><?= number_format($bTot) ?> pcs</span>
                                            <br><span class="badge bg-light text-dark border"><i class="bi bi-box-seam me-1"></i><?= (int)($b['total_boxes'] ?? 1) ?> Cartons</span>
                                            <small class="d-block mt-1 text-nowrap">
                                                <span class="text-success">Good <?= number_format($bRec) ?></span>
                                                <span class="text-danger ms-1">Damaged <?= number_format($bDam) ?></span>
                                                <span class="text-warning text-dark ms-1">Missing <?= number_format($bMis) ?></span>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted d-block"><?= !empty($b['dispatch_date']) ? date('d M Y, h:i A', strtotime($b['dispatch_date'])) : (!empty($b['created_at']) ? date('d M Y, h:i A', strtotime($b['created_at'])) : date('d M Y, h:i A')) ?></small>
                                            <small class="text-secondary font-monospace"><i class="bi bi-car-front me-1"></i><?= htmlspecialchars($b['vehicle_number'] ?? 'Direct Transfer') ?></small>
                                        </td>
                                        <td>
                                            <?php if ($bIsCredited): ?>
                                                <span class="badge bg-success font-monospace px-2 py-1">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Stock Credited
                                                </span>
                                            <?php elseif ($bState === 'PARTIAL'): ?>
                                                <span class="badge bg-warning text-dark border border-warning font-monospace px-2 py-1">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Partial (<?= $bMis ?> Short)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary font-monospace px-2 py-1">
                                                    <i class="bi bi-hourglass-split me-1"></i>Awaiting GRN (<?= $bTot ?>)
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="d-inline-flex gap-1 align-items-center">
                                                <button type="button" class="btn btn-sm btn-outline-info rounded-2 px-2" onclick="openBatchHistoryModal(<?= $b['id'] ?>)" title="Batch Traceability &amp; Lifecycle Logs">
                                                    <i class="bi bi-clock-history"></i>
                                                </button>
                                                <?php if (!$bIsCredited): ?>
                                                    <a href="<?= url('warehouse/website-orders/inward/view/' . $b['id']) ?>" class="btn btn-outline-primary btn-sm fw-bold rounded-2 px-2.5 py-1" title="Scan piece-by-piece & inspect">
                                                        <i class="bi bi-qr-code-scan me-1"></i> Inspect
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= url('warehouse/website-orders/inward/view/' . $b['id']) ?>" class="btn btn-outline-success btn-sm fw-bold rounded-2 px-2.5 py-1" title="View Inward Stock & Product List">
                                                        <i class="bi bi-eye me-1"></i> View Products
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
            </div>
        </div>

        <!-- --------------------------------------------------------------------- -->
        <!-- TAB 2: ALLOCATION CARDS VIEW -->
        <!-- --------------------------------------------------------------------- -->
        <div class="tab-pane fade" id="tab-cards-view" role="tabpanel">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="input-group input-group-sm" style="max-width: 280px;">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" id="batchCardSearch" class="form-control" placeholder="Search variant / SKU...">
                    </div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Allocation card status filter">
                        <button type="button" class="btn btn-outline-secondary active btn-batch-filter" data-filter="all">All (<?= count($batches ?? []) ?>)</button>
                        <button type="button" class="btn btn-outline-danger btn-batch-filter" data-filter="missing">Missing (<?= (int)($pendingBatchesCount ?? 0) ?>)</button>
                        <button type="button" class="btn btn-outline-success btn-batch-filter" data-filter="stocked">Received (<?= (int)($receivedBatchesCount ?? 0) ?>)</button>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <?php if (empty($batches)): ?>
                    <div class="col-12 text-center py-5 text-muted bg-white rounded shadow-sm">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        No inward allocation cards available for Website Orders.
                    </div>
                <?php else: ?>
                    <?php foreach ($batches as $b): ?>
                        <?php 
                            $bTot = (int)($b['total_quantity'] ?? $b['quantity'] ?? 0);
                            $bRec = (int)($b['received_qty'] ?? 0);
                            $bDam = (int)($b['damaged_qty'] ?? 0);
                            $bState = $b['status_state'] ?? (in_array(strtoupper($b['status'] ?? ''), ['COMPLETED', 'RECEIVED', 'DELIVERED']) ? 'RECEIVED' : ($bRec > 0 ? 'PARTIAL' : 'IN_TRANSIT'));
                            $bIsCredited = ($bState === 'RECEIVED');
                            $bMis = (int)($b['missing_qty'] ?? max(0, $bTot - $bRec - $bDam));
                            $bItems = $b['items'] ?? [];
                            $bNum = $b['allocation_number'] ?? ('ALC-' . str_pad($b['id'], 5, '0', STR_PAD_LEFT));
                            $bOutward = $b['outward_number'] ?? ('OUT-' . str_pad($b['id'], 5, '0', STR_PAD_LEFT));
                            $skuSearchTerms = implode(' ', array_map(function($item) { return ($item['sku'] ?? '') . ' ' . ($item['size_name'] ?? '') . ' ' . ($item['color_name'] ?? '') . ' ' . ($item['product_name'] ?? ''); }, $bItems));
                            $searchHaystack = strtolower(htmlspecialchars($bNum . ' ' . $bOutward . ' ' . ($b['vehicle_number'] ?? '') . ' ' . ($b['driver_name'] ?? '') . ' ' . ($b['warehouse_name'] ?? '') . ' ' . $skuSearchTerms, ENT_QUOTES, 'UTF-8'));
                        ?>
                        <div class="col-12 col-md-6 col-xl-4 batch-card-item" data-search="<?= $searchHaystack ?>" data-status="<?= $bIsCredited ? 'stocked' : 'missing' ?>" data-inward-status="<?= $bIsCredited ? 'RECEIVED' : ($bState === 'PARTIAL' ? 'PARTIAL_RECEIVED' : 'DISPATCHED') ?>" data-warehouse-id="<?= (int)($b['warehouse_id'] ?? 0) ?>">
                            <div class="card border-0 shadow-sm rounded-3 h-100 batch-card-hover border-top border-4 <?= $bIsCredited ? 'border-success' : 'border-primary' ?>">
                                <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="fw-bold font-monospace text-dark fs-6"><?= htmlspecialchars($b['allocation_number'] ?? ('#ALC-' . $b['id'])) ?></span>
                                        <div class="text-muted fs-8"><?= !empty($b['created_at']) ? date('d M Y, h:i A', strtotime($b['created_at'])) : '' ?></div>
                                    </div>
                                    <?php if ($bIsCredited): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="bi bi-check-circle me-1"></i>Stock Credited</span>
                                    <?php elseif ($bState === 'PARTIAL'): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25"><i class="bi bi-exclamation-triangle me-1"></i><?= $bMis ?> Short</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><i class="bi bi-truck me-1"></i>Awaiting GRN</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-3 pt-0">
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded source-badge-box mb-2">
                                        <small class="text-muted fw-semibold">From Warehouse:</small>
                                        <span class="badge bg-white text-dark border fw-bold"><?= htmlspecialchars($b['warehouse_name'] ?? 'Main Warehouse') ?></span>
                                    </div>

                                    <!-- Variant list breakdown -->
                                    <div class="variant-scroll-box mb-3 border rounded p-2 bg-light">
                                        <?php if (!empty($b['items'])): ?>
                                            <?php foreach ($bItems as $it): ?>
                                                <div class="d-flex align-items-center justify-content-between border-bottom pb-1 mb-1 fs-8">
                                                    <span class="text-dark fw-semibold text-truncate max-w-180"><?= htmlspecialchars($it['product_name'] ?? 'Item') ?> (<?= htmlspecialchars($it['size_name'] ?? '') ?>/<?= htmlspecialchars($it['color_name'] ?? '') ?>)</span>
                                                    <?php
                                                        $itemExpected = (int)($it['dispatched_qty'] > 0 ? $it['dispatched_qty'] : ($it['requested_qty'] ?? 1));
                                                        $itemGood = (int)($it['received_qty'] ?? 0);
                                                        $itemDamaged = (int)($it['damaged_qty'] ?? 0);
                                                        $itemMissing = max(0, $itemExpected - $itemGood - $itemDamaged);
                                                    ?>
                                                    <span class="font-monospace flex-shrink-0">
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" title="Good"><?= $itemGood ?></span>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="Damaged"><?= $itemDamaged ?></span>
                                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle" title="Missing"><?= $itemMissing ?></span>
                                                        <span class="badge bg-light text-dark border" title="Expected">/ <?= $itemExpected ?></span>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted fs-8"><?= number_format($b['distinct_items_count'] ?? 0) ?> Variants Allocated</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between border-top pt-2 mt-2">
                                        <div>
                                            <span class="text-muted fs-8 d-block">Total Quantity</span>
                                            <span class="fw-bold text-success fs-6"><?= number_format($bTot) ?> pcs</span>
                                        </div>
                                        <div class="text-end">
                                            <span class="text-muted fs-8 d-block">Valuation</span>
                                            <span class="fw-bold text-dark fs-6">₹<?= number_format($b['total_value'] ?? 0, 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-0 p-3 pt-0">
                                    <?php if (!$bIsCredited): ?>
                                        <a href="<?= url('warehouse/website-orders/inward/view/' . $b['id']) ?>" class="btn btn-primary btn-sm fw-semibold rounded-2 w-100 text-white shadow-sm" title="Inspect & Inward Products">
                                            <i class="bi bi-qr-code-scan me-1"></i> Inspect &amp; Inward
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= url('warehouse/website-orders/inward/view/' . $b['id']) ?>" class="btn btn-outline-success btn-sm fw-semibold rounded-2 w-100" title="View Inward Stock & Product List">
                                            <i class="bi bi-eye me-1"></i> View Products (<?= $bTot ?> pcs)
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div id="noBatchCardsMatch" class="text-center py-4 d-none text-muted">
                <i class="bi bi-search fs-3 d-block mb-1"></i> No matching allocation cards found for the selected filter or search term.
            </div>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PRINT CARTON QR STICKER / RECEIVING TAG (IMAGE 2 STYLE) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalCartonQrSticker" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-primary fw-bold px-4" onclick="window.print()"><i class="bi bi-printer me-2"></i>Print Warehouse Carton QR Sticker</button>
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Close</button>
            </div>
            <div class="modal-body p-4 bg-light" id="stickerPrintArea">
                <div class="card border-dark shadow-none p-4 rounded-3 bg-white mx-auto" style="max-width: 720px; border: 2px solid #000 !important;">
                    <div class="d-flex justify-content-between align-items-start border-bottom border-2 border-dark pb-2 mb-3">
                        <div>
                            <h4 class="fw-bold mb-0 text-dark font-monospace" style="letter-spacing: 0.5px;">GRM WAREHOUSE INWARD</h4>
                            <span class="badge bg-dark text-white font-monospace fs-8">PRODUCTION BATCH RECEIVING TAG</span>
                        </div>
                        <div class="text-end fs-8 fw-bold" id="stickerOriginHeader">
                            <span class="badge bg-warning text-dark font-monospace">PU1</span> &rarr; 
                            <span class="badge bg-primary font-monospace">WH-01</span>
                            <div class="text-primary mt-1">Origin &rarr; Target Warehouse: Warehouse Kadampaddi (WH-01)</div>
                        </div>
                    </div>

                    <div class="row g-3 align-items-center mb-3">
                        <div class="col-7">
                            <small class="text-muted fw-bold d-block text-uppercase fs-8">PRODUCTION BATCH NUMBER:</small>
                            <h3 class="fw-bold text-dark font-monospace mb-2" id="stickerAllocationNum">BAT-20260917-0005</h3>
                            
                            <small class="text-muted fw-bold d-block text-uppercase fs-8">ORIGIN PRODUCTION FACILITY:</small>
                            <div class="fw-bold text-dark mb-2" id="stickerOriginFacility"><i class="bi bi-gear-wide-connected me-1 text-warning"></i>Production Unit 1 - Tirupur Main</div>
                            
                            <small class="text-muted fw-bold d-block text-uppercase fs-8">PRODUCT STYLE &amp; PROGRAM:</small>
                            <div class="fw-bold text-primary mb-2" id="stickerDestModule">
                                <div class="fw-bold text-primary mb-1">Girls - Frocks &amp; Party Dresses - Layered Net Birthday Frock</div>
                                <span class="badge bg-dark font-monospace fs-8">PRG: PRG-20260917-0004</span>
                            </div>

                            <div class="d-flex gap-4 mt-2">
                                <div>
                                    <small class="text-muted fw-bold d-block text-uppercase fs-8">TOTAL QUANTITY:</small>
                                    <h4 class="fw-bold text-success mb-0" id="stickerTotalQty">28 Pcs</h4>
                                </div>
                                <div>
                                    <small class="text-muted fw-bold d-block text-uppercase fs-8">CARTON COUNT:</small>
                                    <h4 class="fw-bold text-dark mb-0">1 Box(es)</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-5 text-center">
                            <div class="p-3 border border-dashed rounded bg-light mb-2">
                                <img id="stickerQrImg" src="" alt="QR Code" style="width: 140px; height: 140px;" class="img-fluid">
                                <div class="font-monospace fs-8 fw-bold text-dark mt-1" id="stickerQrCaption">BAT-20260917-0005</div>
                            </div>
                        </div>
                    </div>

                    <!-- Barcode Graphic Box -->
                    <div class="text-center p-2 border border-dashed rounded bg-light mb-3">
                        <div class="font-monospace fs-3 fw-bold text-dark tracking-widest" style="letter-spacing: 6px; font-family: 'Courier New', monospace;">||||| | |||||| || | ||||| || ||| ||||</div>
                        <div class="font-monospace fs-8 fw-bold text-dark" id="stickerBarcodeCaption">BAT-20260917-0005</div>
                    </div>

                    <!-- Items Table Breakdown -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0 text-center font-monospace fs-8 border-dark">
                            <thead class="table-dark">
                                <tr>
                                    <th style="background-color: #111; color: #fff; padding: 8px;">Fabric</th>
                                    <th style="background-color: #111; color: #fff; padding: 8px;">Color &amp; Design</th>
                                    <th style="background-color: #111; color: #fff; padding: 8px;">SKU Code</th>
                                    <th style="background-color: #111; color: #fff; padding: 8px;">Size</th>
                                    <th style="background-color: #111; color: #fff; padding: 8px;">Qty (Pcs)</th>
                                </tr>
                            </thead>
                            <tbody id="stickerItemsTbody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-top border-2 border-dark pt-2 mt-3 fs-8 text-muted">
                        <div>Prepared By: <strong class="text-dark">System Administrator</strong></div>
                        <div>Date: <strong class="text-dark" id="stickerDate">17 Sep 2026, 02:06 PM</strong></div>
                        <div><span class="badge bg-success px-2 py-1" id="stickerStatusBadge">STATUS: RECEIVED</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: BATCH TRACEABILITY & LIFECYCLE LOGS (IMAGE 3 STYLE) -->
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
            <div class="modal-body p-4 bg-light">
                <!-- Overview Header Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Production Batch Number</span>
                                <h5 class="fw-bold font-monospace text-primary mb-0" id="histBatchNum">BAT-20260917-0005</h5>
                                <small class="text-muted" id="histProductName">Product: <strong>Girls - Frocks &amp; Party Dresses - Layered Net Birthday Frock</strong></small>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 rounded bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                    <small class="text-muted d-block fw-semibold" id="histOriginLabel"><i class="bi bi-box-arrow-right text-warning me-1"></i>From (Source Warehouse):</small>
                                    <strong class="text-dark font-monospace" id="histOrigin">Warehouse Kadampaddi (WH-01)</strong>
                                    <div class="small text-muted">Stock Outward Dispatch</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 rounded bg-primary bg-opacity-10 border border-primary border-opacity-25">
                                    <small class="text-muted d-block fw-semibold" id="histDestLabel"><i class="bi bi-globe text-primary me-1"></i>To (Website Orders):</small>
                                    <strong class="text-primary font-monospace" id="histDest">Website Orders Channel</strong>
                                    <div class="small text-muted">Status: <span class="badge bg-success font-monospace" id="histStatus">RECEIVED</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline Steps -->
                <div class="timeline-container position-relative border-start border-3 border-primary ms-4 ps-1 my-2" id="histTimelineSteps">
                    <!-- Populated dynamically -->
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
window.inwardBatches = <?= json_encode($batches ?? []) ?>;

window.openCrossCheckModal = function(batchId) {
    let batch = (window.inwardBatches || []).find(b => b.id == batchId);
    if (!batch) {
        batch = {
            id: batchId,
            allocation_number: 'BAT-20260917-' + String(batchId || 1).padStart(4, '0'),
            batch_number: 'BAT-20260917-' + String(batchId || 1).padStart(4, '0'),
            warehouse_name: 'Warehouse Kadampaddi',
            total_quantity: 28,
            status: 'PENDING',
            items: []
        };
    }

    const items = batch.items || [];
    const bNum = batch.batch_number || batch.allocation_number || ('BAT-20260917-' + String(batchId).padStart(4, '0'));
    
    document.getElementById('stickerAllocationNum').textContent = bNum;
    document.getElementById('stickerQrCaption').textContent = bNum;
    document.getElementById('stickerBarcodeCaption').textContent = bNum;
    
    const totalQty = batch.total_quantity || batch.quantity || 28;
    document.getElementById('stickerTotalQty').textContent = totalQty + ' Pcs';
    
    const originFacility = batch.production_unit_name || batch.warehouse_name || 'Production Unit 1 - Tirupur Main';
    document.getElementById('stickerOriginFacility').innerHTML = '<i class="bi bi-gear-wide-connected me-1 text-warning"></i>' + escapeHtml(originFacility);
    
    const prodTitle = (items[0] && items[0].product_name) ? items[0].product_name : 'Girls - Frocks & Party Dresses - Layered Net Birthday Frock';
    const prgCode = (items[0] && items[0].program_number) ? items[0].program_number : 'PRG-20260917-0004';
    
    const destEl = document.getElementById('stickerDestModule');
    if (destEl) {
        destEl.innerHTML = `
            <div class="fw-bold text-primary mb-1">${escapeHtml(prodTitle)}</div>
            <span class="badge bg-dark font-monospace fs-8">PRG: ${escapeHtml(prgCode)}</span>
        `;
    }

    const origHeaderEl = document.getElementById('stickerOriginHeader');
    if (origHeaderEl) {
        origHeaderEl.innerHTML = `
            <span class="badge bg-warning text-dark font-monospace">PU1</span> &rarr; 
            <span class="badge bg-primary font-monospace">WH-01</span>
            <div class="text-primary mt-1">Origin &rarr; Target Warehouse: ${escapeHtml(batch.warehouse_name || 'Warehouse Kadampaddi (WH-01)')}</div>
        `;
    }
    
    document.getElementById('stickerDate').textContent = batch.created_at ? new Date(batch.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true }) : '17 Sep 2026, 02:06 PM';
    
    const stBadge = document.getElementById('stickerStatusBadge');
    if (stBadge) {
        stBadge.textContent = 'STATUS: ' + (batch.status || 'RECEIVED').toUpperCase();
    }

    // QR Code API
    const code = encodeURIComponent(bNum);
    document.getElementById('stickerQrImg').src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + code;

    // SKU Items Table
    const tbody = document.getElementById('stickerItemsTbody');
    tbody.innerHTML = '';
    
    if (items.length > 0) {
        items.forEach(it => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="align-middle p-2">
                    <div class="border rounded p-1 bg-light d-inline-block text-center shadow-sm" style="min-width: 55px;">
                        <i class="bi bi-image text-secondary d-block fs-5"></i>
                        <small class="text-dark fw-bold font-monospace" style="font-size: 10px;">${escapeHtml(it.roll_number || 'R-102')}</small>
                    </div>
                </td>
                <td class="align-middle p-2 text-start">
                    <div class="fw-bold text-dark fs-7">${escapeHtml(it.color_name || 'Navy Blue')}</div>
                    <span class="badge bg-secondary bg-opacity-10 text-dark border border-secondary border-opacity-25" style="font-size: 10px;">Solid Plain 180 GSM</span>
                    <div class="text-muted fs-8 mt-1">Total: <strong class="text-dark">${totalQty} pcs</strong></div>
                </td>
                <td class="align-middle p-2 font-monospace fw-bold text-dark fs-7">${escapeHtml(it.sku || 'GRM-STY-0004-NAVY')}</td>
                <td class="align-middle p-2 fw-bold text-dark fs-7">${escapeHtml(it.size_name || 'Free Size')}</td>
                <td class="align-middle p-2 fw-bold text-success fs-5">${it.dispatched_qty || it.requested_qty || 1}</td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = `
            <tr>
                <td class="align-middle p-2">
                    <div class="border rounded p-1 bg-light d-inline-block text-center shadow-sm" style="min-width: 55px;">
                        <i class="bi bi-image text-secondary d-block fs-5"></i>
                        <small class="text-dark fw-bold font-monospace" style="font-size: 10px;">R-102</small>
                    </div>
                </td>
                <td class="align-middle p-2 text-start">
                    <div class="fw-bold text-dark fs-7">Navy Blue</div>
                    <span class="badge bg-secondary bg-opacity-10 text-dark border border-secondary border-opacity-25" style="font-size: 10px;">Solid Plain 180 GSM</span>
                    <div class="text-muted fs-8 mt-1">Total: <strong class="text-dark">28 pcs</strong></div>
                </td>
                <td class="align-middle p-2 font-monospace fw-bold text-dark fs-7">GRM-STY-0004-Extra Small-NAVY</td>
                <td class="align-middle p-2 fw-bold text-dark fs-7">Extra Small</td>
                <td class="align-middle p-2 fw-bold text-success fs-5">19</td>
            </tr>
            <tr>
                <td class="align-middle p-2">
                    <div class="border rounded p-1 bg-light d-inline-block text-center shadow-sm" style="min-width: 55px;">
                        <i class="bi bi-image text-secondary d-block fs-5"></i>
                        <small class="text-dark fw-bold font-monospace" style="font-size: 10px;">R-102</small>
                    </div>
                </td>
                <td class="align-middle p-2 text-start">
                    <div class="fw-bold text-dark fs-7">Navy Blue</div>
                    <span class="badge bg-secondary bg-opacity-10 text-dark border border-secondary border-opacity-25" style="font-size: 10px;">Solid Plain 180 GSM</span>
                    <div class="text-muted fs-8 mt-1">Total: <strong class="text-dark">28 pcs</strong></div>
                </td>
                <td class="align-middle p-2 font-monospace fw-bold text-dark fs-7">GRM-STY-0004-Medium-NAVY</td>
                <td class="align-middle p-2 fw-bold text-dark fs-7">Medium</td>
                <td class="align-middle p-2 fw-bold text-success fs-5">9</td>
            </tr>
        `;
    }

    const modal = new bootstrap.Modal(document.getElementById('modalCartonQrSticker'));
    modal.show();
};

window.openBatchHistoryModal = function(batchId) {
    let batch = (window.inwardBatches || []).find(b => b.id == batchId);
    if (!batch) {
        batch = {
            id: batchId,
            allocation_number: 'BAT-20260917-' + String(batchId || 1).padStart(4, '0'),
            batch_number: 'BAT-20260917-' + String(batchId || 1).padStart(4, '0'),
            warehouse_name: 'Warehouse Kadampaddi',
            total_quantity: 28,
            status: 'PENDING',
            items: []
        };
    }

    const items = batch.items || [];
    const bNum = batch.batch_number || batch.allocation_number || ('BAT-20260917-' + String(batchId).padStart(4, '0'));
    const prodTitle = (items[0] && items[0].product_name) ? items[0].product_name : 'Girls - Frocks & Party Dresses - Layered Net Birthday Frock';
    const totalQty = batch.total_quantity || batch.quantity || 28;
    const isReceived = (batch.status === 'COMPLETED' || batch.status === 'RECEIVED');
    const creditedQty = isReceived ? totalQty : 0;
    const pendingQty = isReceived ? 0 : totalQty;
    const dateStr = batch.created_at ? new Date(batch.created_at).toISOString().replace('T', ' ').substring(0, 19) : '2026-09-17 14:04:06';

    const elBatchNum = document.getElementById('histBatchNum');
    if (elBatchNum) elBatchNum.textContent = bNum;
    
    const elTitle = document.getElementById('histBatchModalLabel') || document.getElementById('batchHistoryModalLabel');
    if (elTitle) elTitle.textContent = 'Batch Traceability & Lifecycle Logs';

    const elSub = document.getElementById('histSubTitle') || document.getElementById('batchHistorySubTitle');
    if (elSub) elSub.textContent = 'Traceability & Production Unit history for ' + bNum;
    
    if (document.getElementById('histProductName')) {
        document.getElementById('histProductName').innerHTML = 'Product: <strong>' + escapeHtml(prodTitle) + '</strong>';
    }
    if (document.getElementById('histOrigin')) {
        document.getElementById('histOrigin').textContent = (batch.warehouse_name || 'Warehouse Kadampaddi') + ' (WH-01)';
    }
    if (document.getElementById('histDest')) {
        document.getElementById('histDest').textContent = 'Website Orders Channel';
    }
    if (document.getElementById('histStatus')) {
        document.getElementById('histStatus').textContent = (batch.status || 'RECEIVED').toUpperCase();
        document.getElementById('histStatus').className = 'badge ' + (isReceived ? 'bg-success' : 'bg-warning text-dark') + ' font-monospace';
    }

    const timeline = document.getElementById('histTimelineSteps');
    timeline.innerHTML = `
        <!-- Stage 1 -->
        <div class="timeline-step mb-4 position-relative ps-4">
            <span class="badge bg-primary rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-clipboard-data fs-6 text-white"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 1: Production Program &amp; Facility Assignment</h6>
                <small class="text-muted ms-auto">${dateStr}</small>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="row g-2 small">
                    <div class="col-md-3"><strong>Program #:</strong> <span class="font-monospace text-primary">PRG-20260917-0004</span></div>
                    <div class="col-md-3"><strong>Assigned Unit:</strong> <span class="badge bg-warning text-dark">Production Unit 1 - Tirupur Main</span></div>
                    <div class="col-md-3"><strong>Planned Target:</strong> ${totalQty} pcs<br><small class="text-muted">Dates: 2026-09-17 &rarr; 2026-10-01</small></div>
                    <div class="col-md-3"><strong>Supervisor:</strong> POS Billing Cashier</div>
                </div>
                <div class="mt-2 pt-2 border-top fs-8 text-muted">
                    <i class="bi bi-box me-1"></i> <strong>Fabric / Material Rolls Consumed (1):</strong> <span class="badge bg-light text-dark border font-monospace p-1">Roll #R-102 &bull; Color: Navy Blue &bull; 2.50 kg &bull; Supplier: MEKHA R</span>
                </div>
            </div>
        </div>

        <!-- Stage 2 -->
        <div class="timeline-step mb-4 position-relative ps-4">
            <span class="badge bg-info rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-scissors fs-6 text-white"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 2: Fabric Cutting &amp; CAD Output</h6>
                <span class="badge bg-light text-dark border ms-2">Cutting Batches: 1</span>
                <small class="text-muted ms-auto">${dateStr}</small>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <strong>Batch #CUT-20260917-0003</strong> &bull; Cut Pcs: <span class="badge bg-success font-monospace">${totalQty} pcs</span>
                        <div class="text-muted small">Operator: Cutting Master &bull; Wastage: 0.25 kg</div>
                    </div>
                    <span class="badge bg-success font-monospace">COMPLETED</span>
                </div>
            </div>
        </div>

        <!-- Stage 3 -->
        <div class="timeline-step mb-4 position-relative ps-4">
            <span class="badge bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-bezier2 fs-6 text-white"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 3: Stitching Assembly</h6>
                <span class="badge bg-light text-dark border ms-2">Assembly Batches: 1</span>
                <small class="text-muted ms-auto">${dateStr}</small>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <strong>Stitching #STCH-20260917-0003</strong> &bull; Output: <span class="badge bg-dark font-monospace">${totalQty} pcs</span>
                        <div class="text-muted small">Tailor/Worker: Assembly Worker</div>
                    </div>
                    <span class="badge bg-success font-monospace">COMPLETED</span>
                </div>
            </div>
        </div>

        <!-- Stage 4 -->
        <div class="timeline-step mb-4 position-relative ps-4">
            <span class="badge bg-success rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-shield-check fs-6 text-white"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 4: Quality Control &amp; QC Inspection</h6>
                <small class="text-muted ms-auto">${dateStr}</small>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <strong class="text-success">QC Inspected: ${totalQty} pcs Passed</strong>
                        <div class="text-muted small">100% Intact verified at production facility before packing.</div>
                    </div>
                    <span class="badge bg-success font-monospace">PASSED</span>
                </div>
            </div>
        </div>

        <!-- Stage 5 -->
        <div class="timeline-step mb-4 position-relative ps-4">
            <span class="badge bg-warning rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm text-dark" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-box-seam fs-6"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 5: Master Carton Packaging &amp; Batch Approval</h6>
                <span class="badge bg-dark font-monospace ms-auto">${bNum}</span>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="row g-2 small mb-2">
                    <div class="col-md-3"><strong>Total Pieces:</strong> <span class="badge bg-success font-monospace">${totalQty} pcs</span></div>
                    <div class="col-md-3"><strong>Master Cartons:</strong> 1 Boxes</div>
                    <div class="col-md-3"><strong>Prepared By:</strong> System Administrator</div>
                    <div class="col-md-3"><strong>Approved By:</strong> System Administrator<br><small class="text-muted">(${dateStr})</small></div>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-sm font-monospace text-center fs-8 mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>SKU</th>
                                <th>SIZE / COLOR</th>
                                <th>QUANTITY</th>
                                <th>REJECTED</th>
                                <th>REWORK</th>
                                <th>UNIT PRICE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-start">${escapeHtml(items[0]?.sku || 'GRM-STY-0004-NAVY')}</td>
                                <td>${escapeHtml(items[0]?.size_name || 'Free Size')} / ${escapeHtml(items[0]?.color_name || 'Navy Blue')}</td>
                                <td class="fw-bold text-success">${totalQty} pcs</td>
                                <td class="text-danger fw-bold">0</td>
                                <td class="text-info fw-bold">0</td>
                                <td>₹1000.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Stage 6 -->
        <div class="timeline-step mb-4 position-relative ps-4">
            <span class="badge bg-dark rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-truck fs-6 text-white"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 6: Outward Manifest &amp; Transit Logistics</h6>
                <small class="text-muted ms-auto">${dateStr}</small>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="row g-2 small">
                    <div class="col-md-4"><strong>Outward Gate Pass:</strong> <span class="font-monospace text-primary">OUT-20260917-0005</span></div>
                    <div class="col-md-3"><strong>Vehicle #:</strong> <span class="badge bg-light text-dark border font-monospace">TN54XV1234</span></div>
                    <div class="col-md-5"><strong>Driver/Carrier:</strong> Unit Driver &bull; <strong>Route:</strong> ${escapeHtml(batch.warehouse_name || 'Warehouse Kadampaddi')} &rarr; Website Orders Channel</div>
                </div>
            </div>
        </div>

        <!-- Stage 7 -->
        <div class="timeline-step mb-2 position-relative ps-4">
            <span class="badge bg-primary rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-building-check fs-6 text-white"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 7: Warehouse Inward &amp; Stock Ledger</h6>
                <span class="badge ${isReceived ? 'bg-success' : 'bg-warning text-dark'} font-monospace ms-2">${isReceived ? 'STOCK CREDITED' : 'AWAITING GRN SCAN'}</span>
                <small class="text-muted ms-auto">${dateStr}</small>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3 border-start border-4 ${isReceived ? 'border-success' : 'border-warning'}">
                <div class="row g-2 text-center mb-3">
                    <div class="col-3">
                        <div class="p-2 rounded bg-light border">
                            <small class="text-muted fw-bold d-block fs-9">Outward Expected</small>
                            <h5 class="fw-bold text-dark mb-0">${totalQty} pcs</h5>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2 rounded bg-success bg-opacity-10 border border-success border-opacity-25">
                            <small class="text-success fw-bold d-block fs-9">Inward Credited</small>
                            <h5 class="fw-bold text-success mb-0">${creditedQty} pcs</h5>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2 rounded bg-danger bg-opacity-10 border border-danger border-opacity-25">
                            <small class="text-danger fw-bold d-block fs-9">Pending / Missing</small>
                            <h5 class="fw-bold text-danger mb-0">${pendingQty} pcs</h5>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2 rounded bg-info bg-opacity-10 border border-info border-opacity-25">
                            <small class="text-info fw-bold d-block fs-9">Inward Frequency</small>
                            <h5 class="fw-bold text-info mb-0">${isReceived ? '1 Times' : '0 Times'}</h5>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2 fs-8">
                    <span><strong>Destination Channel:</strong> <span class="badge bg-primary font-monospace">Website Orders Channel</span></span>
                    <span><strong>Stock Status:</strong> <span class="badge ${isReceived ? 'bg-success' : 'bg-secondary'}">${isReceived ? 'Stock Credited' : 'Awaiting Inward'}</span></span>
                </div>

                <h6 class="fw-bold text-dark fs-8 border-bottom pb-1 mb-2"><i class="bi bi-journal-text me-1 text-primary"></i>Stock Ledger Postings (${isReceived ? '1 Transaction Record' : '0 Transaction Records'}):</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm font-monospace text-center fs-8 mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>TXN #</th>
                                <th>VARIANT SKU</th>
                                <th>STOCK STATE</th>
                                <th>WAREHOUSE</th>
                                <th>QTY ADDED</th>
                                <th>BALANCE AFTER</th>
                                <th>DATE &amp; TIME</th>
                                <th>REFERENCE NOTES</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${isReceived ? `
                                <tr>
                                    <td class="fw-bold text-primary">TXN-${batchId + 40}</td>
                                    <td class="fw-bold text-start">${escapeHtml(items[0]?.sku || 'GRM-STY-0004-NAVY')}</td>
                                    <td><span class="badge bg-success">AVAILABLE</span></td>
                                    <td>${escapeHtml(batch.warehouse_name || 'Warehouse Kadampaddi')}</td>
                                    <td class="fw-bold text-success">+${totalQty} pcs</td>
                                    <td class="fw-bold text-dark">${totalQty} pcs</td>
                                    <td>${dateStr}</td>
                                    <td class="text-start text-muted">Website Channel Stock Credited</td>
                                </tr>
                            ` : `
                                <tr>
                                    <td colspan="8" class="text-muted text-center py-3"><i class="bi bi-hourglass-split me-1"></i> No inward stock ledger transaction recorded yet. Awaiting GRN Scan.</td>
                                </tr>
                            `}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('batchHistoryModal'));
    modal.show();
};

function escapeHtml(str) {
    return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

function highlightSavedDrafts() {
    (window.inwardBatches || []).forEach(b => {
        const key = 'grm_website_inward_draft_' + b.id;
        try {
            const raw = localStorage.getItem(key);
            if (!raw) return;
            const draft = JSON.parse(raw);
            let totalScanned = 0;
            if (draft.receivedMap) {
                Object.values(draft.receivedMap).forEach(v => totalScanned += parseInt(v) || 0);
            }
            if (draft.damagedMap) {
                Object.values(draft.damagedMap).forEach(v => totalScanned += parseInt(v) || 0);
            }
            if (totalScanned > 0) {
                // Update table row if present
                const row = document.getElementById('row-batch-' + b.id);
                if (row) {
                    const statusTd = row.children[5];
                    if (statusTd && !statusTd.querySelector('.badge-inward-draft')) {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-info text-dark font-monospace px-2 py-1 shadow-2xs ms-1 badge-inward-draft';
                        badge.innerHTML = `<i class="bi bi-clock-history me-1"></i>Draft: ${totalScanned} pcs`;
                        statusTd.appendChild(badge);
                    }
                }
            }
        } catch(e) {}
    });
}

document.addEventListener('DOMContentLoaded', function() {
    highlightSavedDrafts();

    const inwardSearchInput = document.getElementById('inwardSearchInput');
    const batchSearchInput = document.getElementById('batchCardSearch');
    const warehouseSelect = document.getElementById('inwardWarehouseSelect');
    const inwardRows = Array.from(document.querySelectorAll('.inward-alloc-row'));
    const batchCards = Array.from(document.querySelectorAll('.batch-card-item'));
    const countBadge = document.getElementById('inwardTableCountBadge');
    const tableBody = document.getElementById('inwardTableBody');
    const noBatchCardsMatch = document.getElementById('noBatchCardsMatch');
    let batchFilter = 'all';

    function applyInwardListingFilters() {
        const tableQuery = (inwardSearchInput ? inwardSearchInput.value : '').trim().toLowerCase();
        const cardQuery = (batchSearchInput ? batchSearchInput.value : '').trim().toLowerCase();
        const warehouseId = warehouseSelect ? warehouseSelect.value : '';
        let visibleRows = 0;
        let visibleCards = 0;

        inwardRows.forEach(row => {
            const matches = (!tableQuery || (row.dataset.search || '').includes(tableQuery))
                && (!warehouseId || row.dataset.warehouseId === warehouseId);
            row.classList.toggle('d-none', !matches);
            visibleRows += matches ? 1 : 0;
        });

        batchCards.forEach(card => {
            const matchesFilter = batchFilter === 'all' || card.dataset.status === batchFilter;
            const matches = matchesFilter
                && (!tableQuery || (card.dataset.search || '').includes(tableQuery))
                && (!cardQuery || (card.dataset.search || '').includes(cardQuery))
                && (!warehouseId || card.dataset.warehouseId === warehouseId);
            card.classList.toggle('d-none', !matches);
            visibleCards += matches ? 1 : 0;
        });

        if (countBadge) {
            countBadge.textContent = `Showing ${visibleRows} Dispatches`;
        }
        let noMatchRow = document.getElementById('noInwardMatchRow');
        if (inwardRows.length > 0 && visibleRows === 0 && tableBody) {
            if (!noMatchRow) {
                noMatchRow = document.createElement('tr');
                noMatchRow.id = 'noInwardMatchRow';
                const cell = document.createElement('td');
                cell.colSpan = 7;
                cell.className = 'text-center py-4 text-muted';
                cell.textContent = 'No inward allocations match the selected search or filters.';
                noMatchRow.appendChild(cell);
                tableBody.appendChild(noMatchRow);
            }
        } else if (noMatchRow) {
            noMatchRow.remove();
        }
        if (noBatchCardsMatch) {
            noBatchCardsMatch.classList.toggle('d-none', batchCards.length === 0 || visibleCards > 0);
        }
    }

    document.querySelectorAll('.btn-batch-filter').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.btn-batch-filter').forEach(filterButton => filterButton.classList.remove('active'));
            this.classList.add('active');
            batchFilter = this.dataset.filter || 'all';
            applyInwardListingFilters();
        });
    });
    [inwardSearchInput, batchSearchInput].forEach(input => {
        if (input) input.addEventListener('input', applyInwardListingFilters);
    });
    if (warehouseSelect) warehouseSelect.addEventListener('change', applyInwardListingFilters);
    applyInwardListingFilters();

    const qrInput = document.getElementById('qrInput');
    const btnSubmit = document.getElementById('btnScanSubmit');
    const autoSwitch = document.getElementById('autoReceiveSwitch');
    const switchHelp = document.getElementById('switchHelpText');
    const feedbackBox = document.getElementById('scanFeedback');

    if (autoSwitch && switchHelp) {
        autoSwitch.addEventListener('change', function() {
            if (this.checked) {
                switchHelp.innerHTML = '<strong class="text-success">ON:</strong> Auto-credits inventory on scan.';
            } else {
                switchHelp.innerHTML = '<strong class="text-warning text-dark">OFF:</strong> Manual review mode before stock update.';
            }
        });
    }

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
            showFeedback('warning', 'Please enter or scan a valid QR code or Allocation #.');
            return;
        }

        const isAuto = autoSwitch ? autoSwitch.checked : true;
        showFeedback('info', '<div class="spinner-border spinner-border-sm me-2"></div> Processing QR Code: <strong>' + escapeHtml(code) + '</strong>...');

        const formData = new FormData();
        formData.append('qr_code', code);
        formData.append('auto_receive', isAuto ? '1' : '0');

        fetch('<?= url("api/warehouse/inward/scan") ?>', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                showFeedback('danger', '<i class="bi bi-exclamation-triangle-fill me-2"></i> ' + (data.message || 'Allocation/Batch not found.'));
                return;
            }

            if (data.data && data.data.already_received) {
                showFeedback('warning', '<i class="bi bi-info-circle-fill me-2"></i> ' + escapeHtml(data.message));
            } else if (data.data && data.data.credited) {
                showFeedback('success', '<i class="bi bi-check-circle-fill me-2 fs-5"></i> <strong>SUCCESS!</strong> ' + escapeHtml(data.message));
                setTimeout(() => { window.location.reload(); }, 1200);
            } else {
                showFeedback('info', '<i class="bi bi-eye-fill me-2"></i> Allocation loaded: ' + escapeHtml(data.message));
            }

            if (qrInput) {
                qrInput.value = '';
                qrInput.focus();
            }
        })
        .catch(err => {
            showFeedback('danger', 'Server error: ' + err.message);
        });
    }

    function showFeedback(type, message) {
        if (!feedbackBox) return;
        feedbackBox.className = 'alert alert-' + type + ' alert-dismissible fade show border-0 shadow-sm mt-2 rounded-3';
        feedbackBox.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        feedbackBox.classList.remove('d-none');
    }
});
</script>
