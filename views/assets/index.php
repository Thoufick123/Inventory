<?php
/**
 * Asset register
 */
$summary = $metrics['summary'] ?? [];
$totalAssets = (int)($summary['total_assets'] ?? 0);
$inUseCount = (int)($summary['in_use_count'] ?? 0);
$inMntCount = (int)($summary['in_maintenance_count'] ?? 0);
$damagedCount = (int)($summary['damaged_count'] ?? 0);
$critCount = (int)($summary['critical_condition_count'] ?? 0);
$expiringCount = (int)($summary['warranty_expiring_soon_count'] ?? 0);
$totalBookValue = (float)($summary['total_book_value'] ?? 0.00);
$totalPurchaseCost = (float)($summary['total_purchase_cost'] ?? 0.00);
$utilizationRate = $totalAssets > 0 ? round(($inUseCount / $totalAssets) * 100, 1) : 0;
?>

<style>
.asset-data-table {
    width: 100%;
    font-size: 0.85rem;
}
.asset-data-table th {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.hover-primary:hover {
    color: var(--bs-primary) !important;
}
.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.06) !important;
}

/* Custom Dropdown Filter Styling - ALWAYS OPENS DOWNWARDS */
.custom-filter-dropdown {
    position: relative;
}
.custom-filter-dropdown .dropdown-menu {
    position: absolute !important;
    top: 100% !important;
    bottom: auto !important;
    left: 0 !important;
    transform: none !important;
    margin-top: 6px !important;
    max-height: 320px;
    overflow-y: auto;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    border: 1px solid rgba(0,0,0,0.08);
    z-index: 1055 !important;
}
.custom-filter-btn {
    border: 1px solid #dee2e6;
    background-color: #fff;
    color: #212529;
    font-size: 0.85rem;
    padding: 0.45rem 0.75rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    text-align: left;
}
.custom-filter-btn:hover, .custom-filter-btn:focus, .custom-filter-btn.show {
    border-color: #0d6efd;
    background-color: #fff;
    color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.12);
}
.filter-item-btn {
    font-size: 0.83rem;
    border-radius: 6px;
    padding: 6px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.15s ease;
    color: #333;
    border: none;
    background: transparent;
    width: 100%;
}
.filter-item-btn:hover {
    background-color: #f1f5f9;
    color: #0d6efd;
}
.filter-item-btn.active {
    background-color: rgba(13, 110, 253, 0.08);
    color: #0d6efd;
    font-weight: 600;
}
.asset-action-floating-menu {
    position: fixed !important;
    inset: auto !important;
    transform: none !important;
    z-index: 1085 !important;
    margin: 0 !important;
}
.dropdown-header-custom {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    padding: 6px 12px 2px;
}
</style>

<!-- Page Header with Clean Top-Right Register Asset Button -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="mb-1 d-flex align-items-center gap-2 fw-bold text-dark">
            <i class="bi bi-table text-primary"></i> Asset Register
        </h2>
        <span class="text-secondary small">Search and manage assets across all locations.</span>
    </div>
    <div>
        <a href="<?= url('asset/create') ?>" class="btn btn-primary btn-lg shadow-sm d-inline-flex align-items-center gap-2 px-4 fw-semibold">
            <i class="bi bi-plus-circle-fill fs-5"></i>
            <span>New Asset</span>
        </a>
    </div>
</div>

<!-- 4 High-Contrast KPI Cards (Dynamically Updated on Filter Selection) -->
<div class="row g-3 mb-4">
    <!-- Card 1: Valuation -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card border-start border-primary border-4 p-3 shadow-sm h-100 bg-white rounded-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label text-uppercase fw-600 text-muted small">Total Book Value</div>
                    <div class="stat-value fw-700 text-primary mt-1 font-monospace fs-4" id="kpi_total_book_value"><?= formatCurrency($totalBookValue) ?></div>
                    <div class="small text-muted mt-2">
                        <span class="text-secondary">Assets:</span> <strong id="kpi_total_assets_count" class="badge bg-primary-subtle text-primary"><?= $totalAssets ?> Units</strong>
                    </div>
                </div>
                <div class="stat-icon icon-primary bg-primary-subtle text-primary p-3 rounded-circle">
                    <i class="bi bi-cash-coin fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: In-Use Active -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card border-start border-success border-4 p-3 shadow-sm h-100 bg-white rounded-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label text-uppercase fw-600 text-muted small">Active &amp; In Use</div>
                    <div class="stat-value fw-700 text-success mt-1 fs-4">
                        <span id="kpi_in_use_count"><?= $inUseCount ?></span> <span class="fs-6 fw-normal text-secondary">/ <span id="kpi_total_fraction"><?= $totalAssets ?></span></span>
                    </div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-check2-circle text-success me-1"></i>Uptime: <strong class="text-success" id="kpi_utilization_rate"><?= $utilizationRate ?>% Active</strong>
                    </div>
                </div>
                <div class="stat-icon icon-success bg-success-subtle text-success p-3 rounded-circle">
                    <i class="bi bi-check2-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Maintenance -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card border-start border-warning border-4 p-3 shadow-sm h-100 bg-white rounded-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label text-uppercase fw-600 text-muted small">Under Maintenance</div>
                    <div class="stat-value fw-700 text-warning mt-1 fs-4"><span id="kpi_in_mnt_count"><?= $inMntCount ?></span> <span class="fs-6 fw-normal text-secondary">Units</span></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-exclamation-triangle text-danger me-1"></i>Damaged / Idle: <strong class="text-danger" id="kpi_damaged_count"><?= $damagedCount ?></strong>
                    </div>
                </div>
                <div class="stat-icon icon-warning bg-warning-subtle text-warning p-3 rounded-circle">
                    <i class="bi bi-tools fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Health & Alerts -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card border-start border-danger border-4 p-3 shadow-sm h-100 bg-white rounded-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label text-uppercase fw-600 text-muted small">Service &amp; Warranty</div>
                    <div class="stat-value fw-700 <?= $critCount > 0 ? 'text-danger' : 'text-primary' ?> mt-1 fs-4">
                        <span id="kpi_expiring_count"><?= $expiringCount ?></span> <span class="fs-6 fw-normal text-secondary">Expiring</span>
                    </div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-shield-exclamation text-danger me-1"></i>Critical: <strong class="<?= $critCount > 0 ? 'text-danger' : 'text-secondary' ?>" id="kpi_crit_count"><?= $critCount ?> Units</strong>
                    </div>
                </div>
                <div class="stat-icon icon-danger bg-danger-subtle text-danger p-3 rounded-circle">
                    <i class="bi bi-shield-exclamation fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- DYNAMIC LIVE FILTER CONTROLS TOOLBAR (INSTANT ON-CLICK FILTERING)         -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
    <div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <!-- Search Keyword -->
            <div class="col-lg-3 col-md-6 col-12">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="live_search_input" class="form-control border-start-0" placeholder="Search code, name, serial..." oninput="applyAssetLiveFilters()">
                </div>
            </div>

            <!-- Unit / Location Dropdown (Downward Opening, Instant Filter) -->
            <div class="col-lg-3 col-md-6 col-12">
                <input type="hidden" id="live_unit_select" value="ALL">
                <div class="dropdown custom-filter-dropdown">
                    <button class="btn custom-filter-btn w-100 d-flex align-items-center justify-content-between" type="button" id="btn_unit_filter" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                        <span class="text-truncate me-1" id="label_unit_filter">
                            <i class="bi bi-geo-alt-fill text-primary me-1"></i> All Locations / Units
                        </span>
                        <i class="bi bi-chevron-down text-muted small ms-auto"></i>
                    </button>
                    <ul class="dropdown-menu shadow-lg border-0 p-2 dropdown-menu-start" style="min-width: 290px;">
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn active" data-type="unit" data-val="ALL" onclick="selectLiveFilter('unit', 'ALL', this)">
                                <span><i class="bi bi-globe text-primary me-2"></i>All Locations / Units</span>
                                <i class="bi bi-check2 check-icon"></i>
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="dropdown-header-custom">🏭 Production Units</li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="unit" data-val="PRODUCTION_ALL" onclick="selectLiveFilter('unit', 'PRODUCTION_ALL', this)">
                                <span><i class="bi bi-building-gear text-primary me-2"></i>All Production Units</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                        <?php foreach (($unitsMap['production'] ?? []) as $pu): ?>
                            <li>
                                <button type="button" class="dropdown-item filter-item-btn" data-type="unit" data-val="PU_<?= $pu['id'] ?>" onclick="selectLiveFilter('unit', 'PU_<?= $pu['id'] ?>', this)">
                                    <span>🏭 <?= e($pu['code'] ? '[' . $pu['code'] . '] ' : '') ?><?= e($pu['name']) ?></span>
                                    <i class="bi bi-check2 check-icon d-none"></i>
                                </button>
                            </li>
                        <?php endforeach; ?>
                        
                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="dropdown-header-custom">🏢 Central Warehouses</li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="unit" data-val="WAREHOUSE_ALL" onclick="selectLiveFilter('unit', 'WAREHOUSE_ALL', this)">
                                <span><i class="bi bi-boxes text-info me-2"></i>All Warehouses</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                        <?php foreach (($unitsMap['warehouses'] ?? []) as $wh): ?>
                            <li>
                                <button type="button" class="dropdown-item filter-item-btn" data-type="unit" data-val="WH_<?= $wh['id'] ?>" onclick="selectLiveFilter('unit', 'WH_<?= $wh['id'] ?>', this)">
                                    <span>🏢 <?= e($wh['code'] ? '[' . $wh['code'] . '] ' : '') ?><?= e($wh['name']) ?></span>
                                    <i class="bi bi-check2 check-icon d-none"></i>
                                </button>
                            </li>
                        <?php endforeach; ?>

                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="dropdown-header-custom">🛍️ Retail Shops</li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="unit" data-val="SHOP_ALL" onclick="selectLiveFilter('unit', 'SHOP_ALL', this)">
                                <span><i class="bi bi-shop-window text-success me-2"></i>All Retail Shops</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                        <?php foreach (($unitsMap['shops'] ?? []) as $sh): ?>
                            <li>
                                <button type="button" class="dropdown-item filter-item-btn" data-type="unit" data-val="SH_<?= $sh['id'] ?>" onclick="selectLiveFilter('unit', 'SH_<?= $sh['id'] ?>', this)">
                                    <span>🛍️ <?= e($sh['code'] ? '[' . $sh['code'] . '] ' : '') ?><?= e($sh['name']) ?></span>
                                    <i class="bi bi-check2 check-icon d-none"></i>
                                </button>
                            </li>
                        <?php endforeach; ?>

                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="dropdown-header-custom">🏛️ Head Office</li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="unit" data-val="OFFICE" onclick="selectLiveFilter('unit', 'OFFICE', this)">
                                <span>🏛️ Head Office &amp; Logistics</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Category Dropdown (Downward Opening, Instant Filter) -->
            <div class="col-lg-2 col-md-4 col-6">
                <input type="hidden" id="live_cat_select" value="">
                <div class="dropdown custom-filter-dropdown">
                    <button class="btn custom-filter-btn w-100 d-flex align-items-center justify-content-between" type="button" id="btn_cat_filter" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                        <span class="text-truncate me-1" id="label_cat_filter">
                            <i class="bi bi-folder2-open text-warning me-1"></i> All Categories (<?= $totalAssets ?>)
                        </span>
                        <i class="bi bi-chevron-down text-muted small ms-auto"></i>
                    </button>
                    <ul class="dropdown-menu shadow-lg border-0 p-2 dropdown-menu-start" style="min-width: 250px;">
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn active" data-type="cat" data-val="" onclick="selectLiveFilter('cat', '', this)">
                                <span><i class="bi bi-folder2-open text-warning me-2"></i>All Categories (<?= $totalAssets ?>)</span>
                                <i class="bi bi-check2 check-icon"></i>
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php foreach ($categories as $cat): ?>
                            <?php $catIcon = !empty($cat['icon']) ? $cat['icon'] : (!empty($cat['icon_class']) ? $cat['icon_class'] : 'bi-box-seam'); ?>
                            <li>
                                <button type="button" class="dropdown-item filter-item-btn" data-type="cat" data-val="<?= $cat['id'] ?>" onclick="selectLiveFilter('cat', '<?= $cat['id'] ?>', this)">
                                    <span><i class="bi <?= e($catIcon) ?> text-primary me-2"></i><?= e($cat['name']) ?></span>
                                    <i class="bi bi-check2 check-icon d-none"></i>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Status Dropdown (Downward Opening, Instant Filter) -->
            <div class="col-lg-2 col-md-4 col-6">
                <input type="hidden" id="live_status_select" value="">
                <div class="dropdown custom-filter-dropdown">
                    <button class="btn custom-filter-btn w-100 d-flex align-items-center justify-content-between" type="button" id="btn_status_filter" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                        <span class="text-truncate me-1" id="label_status_filter">
                            <i class="bi bi-lightning-charge text-info me-1"></i> All Statuses
                        </span>
                        <i class="bi bi-chevron-down text-muted small ms-auto"></i>
                    </button>
                    <ul class="dropdown-menu shadow-lg border-0 p-2 dropdown-menu-start" style="min-width: 210px;">
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn active" data-type="status" data-val="" onclick="selectLiveFilter('status', '', this)">
                                <span>⚡ All Statuses</span>
                                <i class="bi bi-check2 check-icon"></i>
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="status" data-val="IN_USE" onclick="selectLiveFilter('status', 'IN_USE', this)">
                                <span>🟢 In Use (Active)</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="status" data-val="IDLE" onclick="selectLiveFilter('status', 'IDLE', this)">
                                <span>⚪ Idle / Reserve</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="status" data-val="IN_MAINTENANCE" onclick="selectLiveFilter('status', 'IN_MAINTENANCE', this)">
                                <span>🟡 In Maintenance</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="status" data-val="DAMAGED" onclick="selectLiveFilter('status', 'DAMAGED', this)">
                                <span>🔴 Damaged / Breakdown</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="status" data-val="DISPOSED" onclick="selectLiveFilter('status', 'DISPOSED', this)">
                                <span>⚫ Disposed / Sold</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Condition Dropdown & Reset (Instant Filter, No Submit Button Needed) -->
            <div class="col-lg-2 col-md-4 col-6 d-flex gap-2">
                <input type="hidden" id="live_cond_select" value="">
                <div class="dropdown custom-filter-dropdown flex-grow-1">
                    <button class="btn custom-filter-btn w-100 d-flex align-items-center justify-content-between" type="button" id="btn_cond_filter" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                        <span class="text-truncate me-1" id="label_cond_filter">
                            <i class="bi bi-heart-pulse text-danger me-1"></i> All Conditions
                        </span>
                        <i class="bi bi-chevron-down text-muted small ms-auto"></i>
                    </button>
                    <ul class="dropdown-menu shadow-lg border-0 p-2 dropdown-menu-start" style="min-width: 200px;">
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn active" data-type="cond" data-val="" onclick="selectLiveFilter('cond', '', this)">
                                <span>🩺 All Conditions</span>
                                <i class="bi bi-check2 check-icon"></i>
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="cond" data-val="EXCELLENT" onclick="selectLiveFilter('cond', 'EXCELLENT', this)">
                                <span>🟢 Excellent</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="cond" data-val="GOOD" onclick="selectLiveFilter('cond', 'GOOD', this)">
                                <span>🔵 Good</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="cond" data-val="FAIR" onclick="selectLiveFilter('cond', 'FAIR', this)">
                                <span>🟡 Fair</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="cond" data-val="POOR" onclick="selectLiveFilter('cond', 'POOR', this)">
                                <span>🟠 Poor</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item filter-item-btn" data-type="cond" data-val="CRITICAL" onclick="selectLiveFilter('cond', 'CRITICAL', this)">
                                <span>🔴 Critical</span>
                                <i class="bi bi-check2 check-icon d-none"></i>
                            </button>
                        </li>
                    </ul>
                </div>
                <button type="button" class="btn btn-outline-secondary px-3 d-flex align-items-center justify-content-center rounded-3 bg-white" title="Reset All Filters" onclick="resetAssetLiveFilters()">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- REGISTERED ASSET INVENTORY DATA TABLE (ALL ASSETS LIST)                   -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4" id="asset-inventory-table">
    <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-list-columns-reverse text-primary"></i> All Registered Assets
            </h5>
            <span class="text-muted small">Real-time valuation, condition tracking, and equipment operations</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 small" id="showing_counter_badge">
                Showing <?= count($assets) ?> of <?= $totalAssets ?> Total
            </span>
        </div>
    </div>

    <div class="table-responsive" style="width: 100%; overflow-x: auto;">
        <table class="table table-hover align-middle mb-0 asset-data-table" id="main_asset_table">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th style="min-width: 130px;" class="ps-4">Asset Code / QR</th>
                    <th style="min-width: 220px;">Equipment &amp; Details</th>
                    <th style="min-width: 150px;">Category</th>
                    <th style="min-width: 170px;">Assigned Location</th>
                    <th class="text-end" style="min-width: 120px;">Book Value (₹)</th>
                    <th class="text-center" style="min-width: 110px;">Status</th>
                    <th class="text-center" style="min-width: 100px;">Condition</th>
                    <th class="text-end pe-4" style="min-width: 100px;">Action</th>
                </tr>
            </thead>
            <tbody id="asset_tbody">
                <?php foreach ($assets as $a): ?>
                    <?php
                        $statusBadge = match($a['status']) {
                            'IN_USE' => 'bg-success text-white',
                            'IN_MAINTENANCE' => 'bg-warning text-dark',
                            'IDLE' => 'bg-secondary text-white',
                            'DAMAGED' => 'bg-danger text-white',
                            'DISPOSED', 'WRITTEN_OFF' => 'bg-dark text-white',
                            default => 'bg-light text-dark'
                        };
                        $condBadge = match($a['condition_state']) {
                            'EXCELLENT' => 'text-success bg-success bg-opacity-10 border border-success border-opacity-25',
                            'GOOD' => 'text-primary bg-primary bg-opacity-10 border border-primary border-opacity-25',
                            'FAIR' => 'text-warning bg-warning bg-opacity-10 border border-warning border-opacity-25',
                            'POOR', 'CRITICAL' => 'text-danger bg-danger bg-opacity-10 border border-danger border-opacity-25',
                            default => 'text-secondary bg-light'
                        };
                        $unitKey = match($a['location_type']) {
                            'PRODUCTION' => 'PU_' . $a['location_id'],
                            'WAREHOUSE'  => 'WH_' . $a['location_id'],
                            'SHOP'       => 'SH_' . $a['location_id'],
                            'OFFICE'     => 'OFFICE',
                            default      => 'ALL'
                        };
                        $searchBlob = strtolower(implode(' ', [
                            $a['asset_code'] ?? '',
                            $a['barcode_qr'] ?? '',
                            $a['name'] ?? '',
                            $a['brand'] ?? '',
                            $a['model_number'] ?? '',
                            $a['serial_number'] ?? '',
                            $a['category_name'] ?? '',
                            $a['location_display'] ?? '',
                            $a['floor_area'] ?? '',
                            $a['custodian_name'] ?? ''
                        ]));
                        $isExpiring = !empty($a['warranty_expiry']) && (strtotime($a['warranty_expiry']) - time() <= 30*86400) && (strtotime($a['warranty_expiry']) >= time());
                    ?>
                    <tr class="asset-row" 
                        data-unitkey="<?= e($unitKey) ?>"
                        data-unittype="<?= e($a['location_type']) ?>"
                        data-unitid="<?= (int)$a['location_id'] ?>"
                        data-category="<?= (int)$a['category_id'] ?>"
                        data-status="<?= e($a['status']) ?>"
                        data-condition="<?= e($a['condition_state']) ?>"
                        data-bookvalue="<?= (float)$a['current_book_value'] ?>"
                        data-expiring="<?= $isExpiring ? '1' : '0' ?>"
                        data-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8') ?>">
                        <td class="ps-4">
                            <a href="<?= url('asset/view/' . $a['id']) ?>" class="fw-bold text-primary font-monospace text-decoration-none d-block">
                                <?= e($a['asset_code']) ?>
                            </a>
                            <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.72rem;">
                                <i class="bi bi-qr-code"></i> <?= e($a['barcode_qr']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">
                                <a href="<?= url('asset/view/' . $a['id']) ?>" class="text-dark text-decoration-none hover-primary">
                                    <?= e($a['name']) ?>
                                </a>
                            </div>
                            <div class="text-muted small">
                                <?php if (!empty($a['brand']) || !empty($a['model_number'])): ?>
                                    <span><strong><?= e($a['brand']) ?></strong> <?= e($a['model_number']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($a['serial_number'])): ?>
                                    <span class="ms-1 text-secondary font-monospace">#SN: <?= e($a['serial_number']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="bi <?= e($a['category_icon']) ?> text-primary me-1"></i> <?= e($a['category_name']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-medium small"><?= e($a['location_display']) ?></div>
                            <?php if (!empty($a['floor_area'])): ?>
                                <span class="text-muted small d-block"><i class="bi bi-geo-alt"></i> <?= e($a['floor_area']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="fw-bold text-dark font-monospace"><?= formatCurrency((float)$a['current_book_value']) ?></div>
                            <span class="text-muted small d-block" style="font-size: 0.72rem;">Orig: <?= formatCurrency((float)$a['purchase_cost']) ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill <?= $statusBadge ?> px-2 py-1 small">
                                <?= str_replace('_', ' ', $a['status']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill <?= $condBadge ?> px-2 py-1 small">
                                <?= e($a['condition_state']) ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="dropdown asset-action-dropdown">
                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <a class="dropdown-item" href="<?= url('asset/view/' . $a['id']) ?>">
                                            <i class="bi bi-eye text-primary me-2"></i> View Asset 360
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= url('asset/label/' . $a['id']) ?>" target="_blank">
                                            <i class="bi bi-qr-code text-success me-2"></i> Print QR Sticker
                                        </a>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" type="button" onclick="openTransferModal(<?= htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="bi bi-arrow-left-right text-info me-2"></i> Transfer Location
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" type="button" onclick="openMaintenanceModal(<?= htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="bi bi-tools text-warning me-2"></i> Log Service / Repair
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?= url('asset/edit/' . $a['id']) ?>">
                                            <i class="bi bi-pencil me-2"></i> Edit Details
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <!-- Empty state row dynamically shown when 0 results match -->
                <tr id="empty_filter_row" class="<?= empty($assets) ? '' : 'd-none' ?>">
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                        No assets found matching the selected filter criteria.
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="resetAssetLiveFilters()">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                            </button>
                            <a href="<?= url('asset/create') ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Register New Asset
                            </a>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Inter-Unit Asset Transfer -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('asset/transfers/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="asset_id" id="trf_asset_id">
                <input type="hidden" name="redirect_back" value="<?= e($_SERVER['REQUEST_URI'] ?? url('asset/list')) ?>">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-arrow-left-right me-2"></i> Inter-Unit Asset Transfer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="text-muted small">Asset to Transfer:</div>
                        <div class="fw-bold text-dark" id="trf_asset_name">-</div>
                        <div class="text-muted small">Current Location: <span class="fw-semibold text-primary" id="trf_current_loc">-</span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Destination Target Unit <span class="text-danger">*</span></label>
                        <select name="target_unit" class="form-select" required>
                            <optgroup label="🏭 Production Units">
                                <?php foreach (($unitsMap['production'] ?? []) as $pu): ?>
                                    <option value="PU_<?= $pu['id'] ?>">PU: <?= e($pu['name']) ?> (<?= e($pu['code']) ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="🏢 Central Warehouses">
                                <?php foreach (($unitsMap['warehouses'] ?? []) as $wh): ?>
                                    <option value="WH_<?= $wh['id'] ?>">Warehouse: <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="🛍️ Retail Shops">
                                <?php foreach (($unitsMap['shops'] ?? []) as $sh): ?>
                                    <option value="SH_<?= $sh['id'] ?>">Shop: <?= e($sh['name']) ?> (<?= e($sh['code']) ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="🏛️ Head Office">
                                <option value="OFFICE_1">Head Office &amp; Central Logistics</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Specific Floor / Bay / Counter</label>
                        <input type="text" name="to_floor_area" class="form-control" placeholder="e.g. Line 2 Station 4, Billing Desk 1">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Transfer Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Re-allocation, promotion deployment">
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Challan / Vehicle Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Vehicle number, driver, instructions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Confirm Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Service & Maintenance Logging -->
<div class="modal fade" id="maintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('asset/maintenance/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="asset_id" id="mnt_asset_id">
                <input type="hidden" name="redirect_back" value="<?= e($_SERVER['REQUEST_URI'] ?? url('asset/list')) ?>">

                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-tools me-2"></i> Log Service / Repair Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="text-muted small">Asset:</div>
                        <div class="fw-bold text-dark" id="mnt_asset_name">-</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Service Type <span class="text-danger">*</span></label>
                            <select name="service_type" class="form-select form-select-sm" required>
                                <option value="ROUTINE_PREVENTIVE">Routine Preventive Maintenance</option>
                                <option value="BREAKDOWN_REPAIR">Breakdown Repair</option>
                                <option value="CALIBRATION">Calibration &amp; Safety Testing</option>
                                <option value="PART_REPLACEMENT">Spare Part Replacement</option>
                                <option value="ANNUAL_AMC">Annual AMC Servicing</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Service Date <span class="text-danger">*</span></label>
                            <input type="date" name="service_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Issue / Maintenance Scope</label>
                        <textarea name="issue_description" class="form-control" rows="2" placeholder="Describe issue or service required..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Action Taken</label>
                        <textarea name="action_taken" class="form-control" rows="2" placeholder="Work done, parts replaced..."></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Vendor / Agency</label>
                            <input type="text" name="serviced_by_vendor" class="form-control form-control-sm" placeholder="e.g. Machinery Corp">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Technician</label>
                            <input type="text" name="technician_name" class="form-control form-control-sm" placeholder="e.g. R. Kumar">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Cost (₹)</label>
                            <input type="number" step="0.01" name="service_cost" class="form-control form-control-sm" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Downtime (Hrs)</label>
                            <input type="number" step="0.5" name="downtime_hours" class="form-control form-control-sm" value="0.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Next Due Date</label>
                            <input type="date" name="next_due_date" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Condition Post-Service</label>
                            <select name="condition_state" class="form-select form-select-sm">
                                <option value="EXCELLENT">Excellent</option>
                                <option value="GOOD" selected>Good</option>
                                <option value="FAIR">Fair</option>
                                <option value="POOR">Poor</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Operational Status</label>
                            <select name="asset_status" class="form-select form-select-sm">
                                <option value="IN_USE" selected>In Use (Ready)</option>
                                <option value="IN_MAINTENANCE">In Maintenance</option>
                                <option value="IDLE">Idle</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 fw-semibold"><i class="bi bi-check-circle me-1"></i> Save Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Total global assets count for fractions
 */
const totalGlobalAssetsCount = <?= (int)$totalAssets ?>;

/**
 * Format Currency in INR (₹)
 */
function formatRupees(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2
    }).format(amount);
}

/**
 * Select a live filter option from custom dropdown and trigger instant filtering
 */
function selectLiveFilter(type, val, el) {
    const labelSpan = el ? el.querySelector('span') : null;
    const labelHtml = labelSpan ? labelSpan.innerHTML : (el ? el.innerText : '');

    // 1. Update hidden input and button label
    if (type === 'unit') {
        const input = document.getElementById('live_unit_select');
        if (input) input.value = val;
        const lbl = document.getElementById('label_unit_filter');
        if (lbl && labelHtml) lbl.innerHTML = labelHtml;
    } else if (type === 'cat') {
        const input = document.getElementById('live_cat_select');
        if (input) input.value = val;
        const lbl = document.getElementById('label_cat_filter');
        if (lbl && labelHtml) lbl.innerHTML = labelHtml;
    } else if (type === 'status') {
        const input = document.getElementById('live_status_select');
        if (input) input.value = val;
        const lbl = document.getElementById('label_status_filter');
        if (lbl && labelHtml) lbl.innerHTML = labelHtml;
    } else if (type === 'cond') {
        const input = document.getElementById('live_cond_select');
        if (input) input.value = val;
        const lbl = document.getElementById('label_cond_filter');
        if (lbl && labelHtml) lbl.innerHTML = labelHtml;
    }

    // 2. Update active visual states and checkmarks in the dropdown menu
    if (el) {
        const menu = el.closest('.dropdown-menu');
        if (menu) {
            menu.querySelectorAll('.filter-item-btn').forEach(btn => {
                btn.classList.remove('active');
                const check = btn.querySelector('.check-icon');
                if (check) check.classList.add('d-none');
            });
            el.classList.add('active');
            const myCheck = el.querySelector('.check-icon');
            if (myCheck) myCheck.classList.remove('d-none');
        }
    }

    // 3. Immediately apply dynamic live filtering across table and KPI metrics (no filter button needed)
    applyAssetLiveFilters();
}

/**
 * Dynamic Instant Live Filter across all rows and KPI metrics
 */
function applyAssetLiveFilters() {
    const searchVal = (document.getElementById('live_search_input')?.value || '').trim().toLowerCase();
    const unitVal = document.getElementById('live_unit_select')?.value || 'ALL';
    const catVal = document.getElementById('live_cat_select')?.value || '';
    const statusVal = document.getElementById('live_status_select')?.value || '';
    const condVal = document.getElementById('live_cond_select')?.value || '';

    const rows = document.querySelectorAll('.asset-row');
    let visibleCount = 0;
    let totalBookValue = 0;
    let inUseCount = 0;
    let inMntCount = 0;
    let damagedCount = 0;
    let critCount = 0;
    let expiringCount = 0;

    rows.forEach(row => {
        const rowUnitKey = row.dataset.unitkey;
        const rowUnitType = row.dataset.unittype;
        const rowCat = row.dataset.category;
        const rowStatus = row.dataset.status;
        const rowCond = row.dataset.condition;
        const rowSearch = row.dataset.search;
        const rowBookVal = parseFloat(row.dataset.bookvalue) || 0;
        const rowExpiring = row.dataset.expiring === '1';

        // 1. Search Query Match
        let matchSearch = true;
        if (searchVal) {
            matchSearch = rowSearch.includes(searchVal);
        }

        // 2. Unit Match
        let matchUnit = true;
        if (unitVal !== 'ALL') {
            if (unitVal === 'PRODUCTION_ALL') {
                matchUnit = (rowUnitType === 'PRODUCTION');
            } else if (unitVal === 'WAREHOUSE_ALL') {
                matchUnit = (rowUnitType === 'WAREHOUSE');
            } else if (unitVal === 'SHOP_ALL') {
                matchUnit = (rowUnitType === 'SHOP');
            } else {
                matchUnit = (rowUnitKey === unitVal);
            }
        }

        // 3. Category Match
        let matchCat = true;
        if (catVal) {
            matchCat = (rowCat === catVal);
        }

        // 4. Status Match
        let matchStatus = true;
        if (statusVal) {
            matchStatus = (rowStatus === statusVal);
        }

        // 5. Condition Match
        let matchCond = true;
        if (condVal) {
            matchCond = (rowCond === condVal);
        }

        // Final Visibility Decision
        if (matchSearch && matchUnit && matchCat && matchStatus && matchCond) {
            row.style.display = '';
            visibleCount++;
            totalBookValue += rowBookVal;

            if (rowStatus === 'IN_USE') inUseCount++;
            if (rowStatus === 'IN_MAINTENANCE') inMntCount++;
            if (rowStatus === 'DAMAGED' || rowStatus === 'IDLE') damagedCount++;
            if (rowCond === 'CRITICAL' || rowCond === 'POOR') critCount++;
            if (rowExpiring) expiringCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Update Counter Badge
    const counterBadge = document.getElementById('showing_counter_badge');
    if (counterBadge) {
        counterBadge.textContent = `Showing ${visibleCount} of ${totalGlobalAssetsCount} Total`;
    }

    // Toggle Empty State Row
    const emptyRow = document.getElementById('empty_filter_row');
    if (emptyRow) {
        if (visibleCount === 0) {
            emptyRow.classList.remove('d-none');
        } else {
            emptyRow.classList.add('d-none');
        }
    }

    // Dynamically Update the 4 Top KPI Cards
    const kpiVal = document.getElementById('kpi_total_book_value');
    if (kpiVal) kpiVal.textContent = formatRupees(totalBookValue);

    const kpiAssets = document.getElementById('kpi_total_assets_count');
    if (kpiAssets) kpiAssets.textContent = `${visibleCount} Units`;

    const kpiInUse = document.getElementById('kpi_in_use_count');
    if (kpiInUse) kpiInUse.textContent = inUseCount;

    const kpiFraction = document.getElementById('kpi_total_fraction');
    if (kpiFraction) kpiFraction.textContent = visibleCount;

    const kpiUptime = document.getElementById('kpi_utilization_rate');
    if (kpiUptime) {
        const rate = visibleCount > 0 ? ((inUseCount / visibleCount) * 100).toFixed(1) : 0;
        kpiUptime.textContent = `${rate}% Active`;
    }

    const kpiMnt = document.getElementById('kpi_in_mnt_count');
    if (kpiMnt) kpiMnt.textContent = inMntCount;

    const kpiDamaged = document.getElementById('kpi_damaged_count');
    if (kpiDamaged) kpiDamaged.textContent = damagedCount;

    const kpiExp = document.getElementById('kpi_expiring_count');
    if (kpiExp) kpiExp.textContent = expiringCount;

    const kpiCrit = document.getElementById('kpi_crit_count');
    if (kpiCrit) kpiCrit.textContent = `${critCount} Units`;
}

/**
 * Reset all live filters back to default
 */
function resetAssetLiveFilters() {
    const searchInput = document.getElementById('live_search_input');
    if (searchInput) searchInput.value = '';

    // Reset Unit
    const unitInput = document.getElementById('live_unit_select');
    if (unitInput) unitInput.value = 'ALL';
    const unitLbl = document.getElementById('label_unit_filter');
    if (unitLbl) unitLbl.innerHTML = '<i class="bi bi-geo-alt-fill text-primary me-1"></i> All Locations / Units';

    // Reset Category
    const catInput = document.getElementById('live_cat_select');
    if (catInput) catInput.value = '';
    const catLbl = document.getElementById('label_cat_filter');
    if (catLbl) catLbl.innerHTML = '<i class="bi bi-folder2-open text-warning me-1"></i> All Categories (<?= $totalAssets ?>)';

    // Reset Status
    const statusInput = document.getElementById('live_status_select');
    if (statusInput) statusInput.value = '';
    const statusLbl = document.getElementById('label_status_filter');
    if (statusLbl) statusLbl.innerHTML = '<i class="bi bi-lightning-charge text-info me-1"></i> All Statuses';

    // Reset Condition
    const condInput = document.getElementById('live_cond_select');
    if (condInput) condInput.value = '';
    const condLbl = document.getElementById('label_cond_filter');
    if (condLbl) condLbl.innerHTML = '<i class="bi bi-heart-pulse text-danger me-1"></i> All Conditions';

    // Reset active checks in all custom dropdowns
    document.querySelectorAll('.filter-item-btn').forEach(btn => {
        const val = btn.getAttribute('data-val');
        const isDefault = (val === 'ALL' || val === '');
        btn.classList.toggle('active', isDefault);
        const check = btn.querySelector('.check-icon');
        if (check) {
            check.classList.toggle('d-none', !isDefault);
        }
    });

    applyAssetLiveFilters();
}

/**
 * Open Modal Triggers
 */
function openTransferModal(asset) {
    document.getElementById('trf_asset_id').value = asset.id;
    document.getElementById('trf_asset_name').textContent = (asset.asset_code || '') + ' - ' + (asset.name || '');
    document.getElementById('trf_current_loc').textContent = (asset.location_display || '') + (asset.floor_area ? ' (' + asset.floor_area + ')' : '');
    new bootstrap.Modal(document.getElementById('transferModal')).show();
}

function openMaintenanceModal(asset) {
    document.getElementById('mnt_asset_id').value = asset.id;
    document.getElementById('mnt_asset_name').textContent = (asset.asset_code || '') + ' - ' + (asset.name || '');
    new bootstrap.Modal(document.getElementById('maintenanceModal')).show();
}

// Initial check on DOM load
document.addEventListener('DOMContentLoaded', () => {
    applyAssetLiveFilters();

    let activeActionDropdown = null;

    const closeActionMenu = () => {
        if (!activeActionDropdown) return;

        const { toggle, menu, placeholder } = activeActionDropdown;
        menu.classList.remove('show', 'asset-action-floating-menu');
        menu.style.removeProperty('top');
        menu.style.removeProperty('left');
        placeholder.parentNode.insertBefore(menu, placeholder);
        placeholder.remove();
        toggle.setAttribute('aria-expanded', 'false');
        activeActionDropdown = null;
    };

    const positionActionMenu = () => {
        if (!activeActionDropdown) return;

        const { toggle, menu } = activeActionDropdown;
        const toggleRect = toggle.getBoundingClientRect();
        const viewportPadding = 8;
        const menuHeight = menu.offsetHeight;
        const menuWidth = menu.offsetWidth;
        const spaceBelow = window.innerHeight - toggleRect.bottom;
        const placeAbove = spaceBelow < menuHeight && toggleRect.top > spaceBelow;
        const top = Math.max(
            viewportPadding,
            Math.min(
                placeAbove ? toggleRect.top - menuHeight : toggleRect.bottom,
                window.innerHeight - menuHeight - viewportPadding
            )
        );
        const left = Math.max(
            viewportPadding,
            Math.min(toggleRect.right - menuWidth, window.innerWidth - menuWidth - viewportPadding)
        );

        menu.style.setProperty('top', `${top}px`, 'important');
        menu.style.setProperty('left', `${left}px`, 'important');
    };

    document.querySelectorAll('.asset-action-dropdown').forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        if (!toggle || !menu) return;

        menu.addEventListener('click', event => {
            if (event.target.closest('button.dropdown-item')) closeActionMenu();
        });

        toggle.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            if (activeActionDropdown && activeActionDropdown.toggle === toggle) {
                closeActionMenu();
                return;
            }

            closeActionMenu();
            const placeholder = document.createComment('asset-action-menu');
            menu.parentNode.insertBefore(placeholder, menu);
            document.body.appendChild(menu);
            menu.classList.add('asset-action-floating-menu', 'show');
            toggle.setAttribute('aria-expanded', 'true');
            activeActionDropdown = { toggle, menu, placeholder };
            positionActionMenu();
        });
    });

    document.addEventListener('click', event => {
        if (!activeActionDropdown) return;
        if (activeActionDropdown.menu.contains(event.target) || activeActionDropdown.toggle.contains(event.target)) return;
        closeActionMenu();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeActionMenu();
    });

    window.addEventListener('resize', positionActionMenu);
    document.addEventListener('scroll', positionActionMenu, true);
});
</script>
