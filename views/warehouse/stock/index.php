<?php
// views/warehouse/stock/index.php
// Warehouse Stock Inventory — Dual Sections: Production Inward & Third-Party Vendor Inventory
$currTab = $activeTab ?? ($_GET['tab'] ?? 'normal');

if (!function_exists('renderCategoryTypeBadge')) {
    function renderCategoryTypeBadge(array $item): string {
        $catType = strtoupper($item['category_type'] ?? '');
        $packQty = (int)($item['pack_quantity'] ?? 1);
        $isComp = ($catType === 'COMPLEMENTARY' || !empty($item['is_complementary']) || !empty($item['variant_is_complementary']));
        $isSeries = ($catType === 'SERIES' || ($item['qr_type'] ?? '') === 'SERIES_BATCH' || $packQty > 1);

        if ($isComp) {
            $html = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning fw-semibold px-2 py-1 shadow-xs font-monospace" style="font-size: 0.72rem;">';
            $html .= '<i class="bi bi-gift-fill me-1 text-danger"></i>COMPLEMENTARY';
            $html .= '</span>';
            if ($packQty > 1) {
                $html .= '<small class="text-muted d-block mt-0.5 font-monospace" style="font-size: 0.68rem;">' . $packQty . ' pcs / pack</small>';
            }
            return $html;
        }

        if ($isSeries) {
            $html = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold px-2 py-1 shadow-xs font-monospace" style="font-size: 0.72rem;">';
            $html .= '<i class="bi bi-boxes me-1 text-primary"></i>SERIES PACK';
            $html .= '</span>';
            $html .= '<small class="text-muted d-block mt-0.5 font-monospace" style="font-size: 0.68rem;">' . ($packQty > 1 ? $packQty : 10) . ' pcs bundle</small>';
            return $html;
        }

        $html = '<span class="badge bg-light text-secondary border fw-semibold px-2 py-1 shadow-xs font-monospace" style="font-size: 0.72rem;">';
        $html .= '<i class="bi bi-tag me-1 text-muted"></i>NORMAL';
        $html .= '</span>';
        $html .= '<small class="text-muted d-block mt-0.5" style="font-size: 0.68rem;">Single Piece</small>';
        return $html;
    }
}
?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-boxes me-2 text-primary"></i>Warehouse Stock &amp; Inventory</h2>
        
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <!-- <a href="<?= url('warehouse/catalog') ?>" class="btn btn-outline-primary shadow-sm">
            <i class="bi bi-journal-bookmark me-1"></i> Product Catalog
        </a>
        <?php if (hasPermission('inward', 'create') || hasPermission('inward', 'verify')): ?>
        <a href="<?= url('warehouse/inward') ?>" class="btn btn-primary shadow-sm fw-semibold">
            <i class="bi bi-box-arrow-in-down me-1"></i> + Inward Production GRN
        </a>
        <?php endif; ?> -->
        <?php if (hasPermission('stock', 'view')): ?>
        <a href="<?= url('warehouse/adjustments') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-sliders me-1"></i> Stock Adjustments
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Warehouse Summary Cards -->
<div class="row g-3 mb-4">
    <!-- 1. Available Stock -->
    <div class="col-sm-6 col-xl">
        <a href="<?= url('warehouse/stock?warehouse_id=' . ($selectedWarehouseId ?? '') . '&stock_state=AVAILABLE&tab=' . $currTab) ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100 <?= ($stockState ?? '') === 'AVAILABLE' ? 'bg-success bg-opacity-10' : '' ?>">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block">Total Available Stock</span>
                            <h4 class="mb-0 mt-1 fw-bold text-success"><?= number_format($summary['available_qty'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h4>
                            <small class="text-success"><i class="bi bi-check2-circle me-1"></i>Ready for Allocation</small>
                        </div>
                        <div class="fs-2 text-success opacity-75"><i class="bi bi-boxes"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- 2. Production Inward Stock -->
    <div class="col-sm-6 col-xl">
        <a href="<?= url('warehouse/stock?warehouse_id=' . ($selectedWarehouseId ?? '') . '&tab=production') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 <?= $currTab === 'production' ? 'bg-primary bg-opacity-10' : '' ?>">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-primary small fw-bold d-block text-uppercase"><i class="bi bi-gear-fill me-1"></i>In-House Production</span>
                            <h4 class="mb-0 mt-1 fw-bold text-primary"><?= number_format($prodAvailableQty ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h4>
                            <small class="text-muted">Est. Val: ₹<?= number_format($prodValuation ?? 0, 2) ?></small>
                        </div>
                        <div class="fs-2 text-primary opacity-75"><i class="bi bi-buildings"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- 3. Third-Party Vendor Stock -->
    <div class="col-sm-6 col-xl">
        <a href="<?= url('warehouse/stock?warehouse_id=' . ($selectedWarehouseId ?? '') . '&tab=third_party') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm border-start border-warning-emphasis border-4 h-100 <?= $currTab === 'third_party' ? 'bg-warning bg-opacity-10' : '' ?>">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-warning-emphasis small fw-bold d-block text-uppercase"><i class="bi bi-shop me-1"></i>Third-Party Products</span>
                            <h4 class="mb-0 mt-1 fw-bold text-warning-emphasis"><?= number_format($tpAvailableQty ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h4>
                            <small class="text-muted">Est. Val: ₹<?= number_format($tpValuation ?? 0, 2) ?></small>
                        </div>
                        <div class="fs-2 text-warning opacity-75"><i class="bi bi-bag-check"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- 4. Reserved Stock -->
    <div class="col-sm-6 col-xl">
        <a href="<?= url('warehouse/stock?warehouse_id=' . ($selectedWarehouseId ?? '') . '&stock_state=RESERVED&tab=' . $currTab) ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100 <?= ($stockState ?? '') === 'RESERVED' ? 'bg-info bg-opacity-10' : '' ?>">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block">Reserved Stock</span>
                            <h4 class="mb-0 mt-1 fw-bold text-info-emphasis"><?= number_format($summary['reserved_qty'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h4>
                            <small class="text-muted">In Active Orders</small>
                        </div>
                        <div class="fs-2 text-info opacity-75"><i class="bi bi-bookmark-check"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- 5. Damaged Stock -->
    <div class="col-sm-6 col-xl">
        <?php if (hasPermission('damage', 'view')): ?>
        <a href="<?= url('warehouse/damage?warehouse_id=' . ($selectedWarehouseId ?? '') . '&status=LOGGED') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100 bg-danger bg-opacity-10">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-danger small fw-bold d-block text-uppercase"><i class="bi bi-qr-code me-1"></i>Damaged / Quarantined</span>
                            <h4 class="mb-0 mt-1 fw-bold text-danger"><?= number_format($summary['damaged_qty'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h4>
                            <small class="text-danger fw-semibold">View Damaged &rarr;</small>
                        </div>
                        <div class="fs-2 text-danger"><i class="bi bi-slash-circle-fill"></i></div>
                    </div>
                </div>
            </div>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Complementary Products & Revenue Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <a href="<?= url('warehouse/stock?warehouse_id=' . ($selectedWarehouseId ?? '') . '&tab=complementary') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100 <?= $currTab === 'complementary' ? 'bg-warning bg-opacity-10' : 'bg-white' ?> rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="text-dark small fw-bold text-uppercase d-flex align-items-center gap-1">
                                    <i class="bi bi-gift-fill text-warning"></i> Complementary Products (Stock)
                                </span>
                                <span class="badge bg-warning-subtle text-dark border border-warning font-monospace" style="font-size: 0.72rem;">
                                    <?= count($complementaryItems ?? []) ?> Products
                                </span>
                            </div>
                            <h4 class="mb-0 fw-bold text-dark"><?= number_format($compAvailableQty ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs available</span></h4>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted">Est. Potential Revenue: <strong class="text-success">₹<?= number_format($compValuation ?? 0, 2) ?></strong></small>
                                <small class="text-warning-emphasis fw-semibold">View Complementary &rarr;</small>
                            </div>
                        </div>
                        <div class="fs-1 text-warning opacity-75 ms-3"><i class="bi bi-gift"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-6">
        <a href="<?= url('warehouse/stock?warehouse_id=' . ($selectedWarehouseId ?? '') . '&tab=complementary') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100 bg-white rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="text-dark small fw-bold text-uppercase d-flex align-items-center gap-1">
                                    <i class="bi bi-graph-up-arrow text-success"></i> Complementary Sales Revenue
                                </span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace" style="font-size: 0.72rem;">
                                    <?= number_format($compSales['total_orders'] ?? 0) ?> Orders
                                </span>
                            </div>
                            <h4 class="mb-0 fw-bold text-success">₹<?= number_format($compSoldRevenue ?? 0, 2) ?></h4>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted">Total Sold: <strong class="text-dark"><?= number_format($compSoldQty ?? 0) ?> pcs</strong> across all channels</small>
                                <small class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Realized Revenue</small>
                            </div>
                        </div>
                        <div class="fs-1 text-success opacity-75 ms-3"><i class="bi bi-cash-coin"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Filters Form -->
<div class="card border-0 shadow-sm mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('warehouse/stock') ?>" class="row g-3 align-items-center" id="stockFilterForm">
            <input type="hidden" name="tab" value="<?= e($currTab) ?>">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Warehouse Facility</label>
                <?php if (!empty($scopedWarehouseId) || count($warehouses) <= 1): ?>
                    <select class="form-select bg-light text-dark fw-semibold" disabled>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?= $wh['id'] ?>" selected>
                                <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="warehouse_id" value="<?= e($selectedWarehouseId ?? '') ?>">
                <?php else: ?>
                    <select name="warehouse_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?= $wh['id'] ?>" <?= ($selectedWarehouseId ?? '') == $wh['id'] ? 'selected' : '' ?>>
                                <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Stock State</label>
                <select name="stock_state" class="form-select" onchange="this.form.submit()">
                    <option value="">All States</option>
                    <option value="AVAILABLE" <?= ($stockState ?? '') === 'AVAILABLE' ? 'selected' : '' ?>>AVAILABLE (Saleable)</option>
                    <option value="RESERVED" <?= ($stockState ?? '') === 'RESERVED' ? 'selected' : '' ?>>RESERVED (Allocations)</option>
                    <option value="DAMAGED" <?= ($stockState ?? '') === 'DAMAGED' ? 'selected' : '' ?>>DAMAGED (Quarantine)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Search Keyword</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="stockLiveSearchInput" class="form-control" placeholder="SKU, style code, vendor, material..." value="<?= e($search ?? '') ?>" oninput="liveFilterStockRows(this.value)">
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2 pt-3">
                <button type="submit" class="btn btn-primary w-100 fw-semibold"><i class="bi bi-filter me-1"></i> Apply Filter</button>
                <a href="<?= url('warehouse/stock?' . (!empty($selectedWarehouseId) ? 'warehouse_id=' . $selectedWarehouseId . '&' : '') . 'tab=' . $currTab) ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Validation: Item Exists in Another Warehouse Alert -->
<?php if (!empty($search) && !empty($otherWarehouseMatches)): ?>
    <div class="alert alert-warning border-warning border-2 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 rounded-3 p-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning text-dark p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            </div>
            <div>
                <h6 class="alert-heading fw-bold mb-1 text-dark">
                    ⚠️ Item Not Located in Current Warehouse: "<?= e($currentWarehouse['name'] ?? 'Selected Warehouse') ?> (<?= e($currentWarehouse['code'] ?? '') ?>)"
                </h6>
                <div class="text-dark small">
                    The search code / keyword <strong>"<?= e($search) ?>"</strong> was found in another warehouse:
                    <?php 
                        $whGrouped = [];
                        foreach ($otherWarehouseMatches as $owm) {
                            $whGrouped[$owm['warehouse_id']][] = $owm;
                        }
                    ?>
                    <div class="mt-1 d-flex flex-wrap gap-2">
                        <?php foreach ($whGrouped as $owhId => $items): ?>
                            <span class="badge bg-dark text-white font-monospace px-2 py-1">
                                <i class="bi bi-geo-alt-fill text-warning me-1"></i><?= e($items[0]['warehouse_name']) ?> (<?= e($items[0]['warehouse_code']) ?>): <?= number_format(array_sum(array_column($items, 'total_qty'))) ?> pcs
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach ($whGrouped as $owhId => $items): ?>
                <?php 
                    $destTab = ($items[0]['source_type'] === 'OWN_PRODUCTION') ? 'production' : 'third_party';
                ?>
                <a href="<?= url('warehouse/stock?warehouse_id=' . $owhId . '&tab=' . $destTab . '&search=' . urlencode($search)) ?>" class="btn btn-dark btn-sm fw-bold shadow-sm">
                    <i class="bi bi-arrow-right-circle-fill me-1 text-warning"></i> Switch to <?= e($items[0]['warehouse_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- PRODUCT TYPE TABS -->
<ul class="nav nav-pills mb-3 gap-2" id="inventorySectionTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link fw-semibold px-4 py-2 <?= $currTab === 'production' ? 'active shadow-sm bg-success text-white' : 'bg-white text-dark border' ?>" href="<?= url('warehouse/stock?warehouse_id=' . ($selectedWarehouseId ?? '') . '&stock_state=' . ($stockState ?? '') . '&search=' . urlencode($search ?? '') . '&tab=production') ?>">
            <i class="bi bi-buildings me-1"></i> Production Unit Products <span class="badge <?= $currTab === 'production' ? 'bg-white text-success' : 'bg-success' ?> ms-1"><?= count($productionItems ?? []) ?></span>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link fw-semibold px-4 py-2 <?= $currTab === 'third_party' ? 'active shadow-sm bg-warning text-dark' : 'bg-white text-dark border' ?>" href="<?= url('warehouse/stock?warehouse_id=' . ($selectedWarehouseId ?? '') . '&stock_state=' . ($stockState ?? '') . '&search=' . urlencode($search ?? '') . '&tab=third_party') ?>">
            <i class="bi bi-shop me-1"></i> Third-Party Products <span class="badge <?= $currTab === 'third_party' ? 'bg-dark text-white' : 'bg-warning text-dark' ?> ms-1"><?= count($thirdPartyItems ?? []) ?></span>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link fw-semibold px-4 py-2 <?= $currTab === 'returned' ? 'active shadow-sm bg-danger text-white' : 'bg-white text-dark border' ?>" href="<?= url('warehouse/stock?warehouse_id=' . ($selectedWarehouseId ?? '') . '&stock_state=' . ($stockState ?? '') . '&search=' . urlencode($search ?? '') . '&tab=returned') ?>">
            <i class="bi bi-arrow-return-left me-1"></i> Returned Products <span class="badge <?= $currTab === 'returned' ? 'bg-white text-danger' : 'bg-danger' ?> ms-1"><?= count($returnedItems ?? []) ?></span>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link fw-semibold px-4 py-2 <?= $currTab === 'series' ? 'active shadow-sm bg-info text-white' : 'bg-white text-dark border' ?>" href="<?= url('warehouse/stock?warehouse_id=' . ($selectedWarehouseId ?? '') . '&stock_state=' . ($stockState ?? '') . '&search=' . urlencode($search ?? '') . '&tab=series') ?>">
            <i class="bi bi-boxes me-1"></i> Series Products <span class="badge <?= $currTab === 'series' ? 'bg-white text-info' : 'bg-info' ?> ms-1"><?= count($seriesItems ?? []) ?></span>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link fw-semibold px-4 py-2 <?= $currTab === 'complementary' ? 'active shadow-sm bg-warning text-dark' : 'bg-white text-dark border' ?>" href="<?= url('warehouse/stock?warehouse_id=' . ($selectedWarehouseId ?? '') . '&stock_state=' . ($stockState ?? '') . '&search=' . urlencode($search ?? '') . '&tab=complementary') ?>">
            <i class="bi bi-gift-fill me-1 text-danger"></i> Complementary Products <span class="badge bg-warning text-dark ms-1"><?= count($complementaryItems ?? []) ?></span>
        </a>
    </li>
</ul>

<!-- TAB CONTENT SECTIONS -->
<?php if ($currTab === 'normal' || $currTab === 'series' || $currTab === 'returned' || $currTab === 'production'): ?>
    <!-- 1. PRODUCTION INWARD INVENTORY SECTION -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-boxes text-primary me-2"></i> <?= $currTab === 'production' ? 'Production Unit Products' : ($currTab === 'series' ? 'Series Products' : ($currTab === 'returned' ? 'Returned Products' : 'Normal Products')) ?>
                </h5>
                
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?php if (hasPermission('stock', 'edit')): ?>
                <button type="button" class="btn btn-outline-success shadow-sm fw-semibold btnTogglePriceEdit" onclick="togglePriceEditMode()">
                    <i class="bi bi-pencil-square me-1"></i> <span class="btnPriceEditLabel">Edit Price</span>
                </button>
                <button type="button" class="btn btn-success shadow-sm fw-semibold btnHeaderApplyPrice d-none" onclick="openBulkPriceModal()">
                    <i class="bi bi-check-circle-fill me-1"></i> Change Price (<span class="headerSelectedPriceCount">0</span>)
                </button>
                <?php endif; ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                    <i class="bi bi-boxes me-1"></i> <?= number_format($prodAvailableQty ?? 0) ?> pcs Available
                </span>
                <span class="badge bg-light text-dark border px-3 py-2 fs-6">
                    Est. Valuation: ₹<?= number_format($prodValuation ?? 0, 2) ?>
                </span>
                <!-- <a href="<?= url('warehouse/inward') ?>" class="btn btn-sm btn-outline-success fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i> Inward Batch GRN
                </a> -->
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($productionItems)): ?>
                <div class="text-center py-5">
                    <?php if ($currTab === 'returned'): ?>
                        <i class="bi bi-arrow-return-left fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No Returned Products Found</h5>
                        <p class="text-muted small">Products will appear here after their return QR codes are inwarded into this warehouse.</p>
                    <?php else: ?>
                        <i class="bi bi-gear fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No In-House Production Stock Records Found</h5>
                        <p class="text-muted small">Perform a Production Inward GRN verification to credit manufactured garments into this warehouse.</p>
                        <?php if (hasPermission('inward', 'view')): ?>
                            <a href="<?= url('warehouse/inward') ?>" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-box-arrow-in-down me-1"></i> Verify Production Inward Batch
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="price-checkbox-col" style="display: none; width: 44px; text-align: center;">
                                    <input type="checkbox" class="form-check-input select-all-price-chk" onchange="toggleSelectAllPriceCheckboxes(this.checked)" title="Select all on this page">
                                </th>
                                <th>SKU / QR</th>
                                <th>Product Style &amp; Category</th>
                                <th>Category Type</th>
                                <th>Variant (Size / Color)</th>
                                <th>Stock State</th>
                                <th>Current Balance</th>
                                <th class="text-end">MRP / Base Price (₹)</th>
                                <th class="text-end">Discount Price (₹)</th>
                                <th class="text-end">Total Valuation</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productionItems as $item): ?>
                                <tr id="stock-row-<?= $item['variant_id'] ?>">
                                    <td class="price-checkbox-col text-center" style="display: none;">
                                        <input type="checkbox" class="form-check-input price-item-checkbox" 
                                               value="<?= (int)$item['variant_id'] ?>" 
                                               data-sku="<?= e($item['sku'] ?? '') ?>" 
                                               data-product-name="<?= e($item['product_name'] ?? '') ?>" 
                                               data-selling-price="<?= (float)($item['display_selling_price'] ?? $item['selling_price'] ?? 0) ?>" 
                                               data-mrp="<?= (float)($item['display_mrp'] ?? $item['mrp'] ?? 0) ?>" 
                                               data-cost-price="<?= (float)($item['cost_rate'] ?? ($item['production_cost'] ?? ($item['purchase_price'] ?? 0))) ?>"
                                               data-valuation-basis="cost"
                                               data-quantity="<?= (float)($item['quantity'] ?? 0) ?>" 
                                               onchange="updatePriceSelectionState()">
                                    </td>
                                    <td>
                                        <span class="fw-bold font-monospace text-primary"><?= e($item['sku']) ?></span>
                                        <?php if (!empty($item['qr_code'])): ?>
                                            <br><small class="text-muted"><i class="bi bi-qr-code me-1"></i><?= e($item['qr_code']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark d-flex align-items-center gap-1.5 flex-wrap">
                                            <span><?= e($item['product_name']) ?></span>
                                            <?php if (!empty($item['is_complementary']) || !empty($item['variant_is_complementary'])): ?>
                                                <span class="badge bg-warning text-dark border border-warning fw-bold px-2 py-0.5 shadow-xs" style="font-size: 0.70rem;" title="Complementary Product (Direct Warehouse Live Stock)">
                                                    <i class="bi bi-gift-fill me-1 text-danger"></i>COMPLEMENTARY
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle small font-monospace"><?= e($item['product_code']) ?></span>
                                            <?php 
                                                $catParts = array_filter([$item['category_name'] ?? '', $item['subcategory_name'] ?? '', $item['sub_subcategory_name'] ?? '']);
                                                if (!empty($catParts)):
                                            ?>
                                                <span class="badge bg-light text-primary border small font-monospace"><i class="bi bi-diagram-3-fill me-1 text-warning"></i><?= e(implode(' › ', $catParts)) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= renderCategoryTypeBadge($item) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark px-2 py-1"><?= e($item['size_name']) ?></span>
                                        <span class="d-inline-flex align-items-center gap-1 ms-1">
                                            <?php if (!empty($item['color_hex'])): ?>
                                                <span class="rounded-circle d-inline-block border" style="width:10px; height:10px; background-color:<?= e($item['color_hex']) ?>;"></span>
                                            <?php endif; ?>
                                            <span class="small"><?= e($item['color_name']) ?></span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= match ($item['display_stock_state']) { 'AVAILABLE' => 'success', 'RESERVED' => 'warning', 'OUT OF STOCK' => 'secondary', default => 'danger' } ?><?= $item['display_stock_state'] === 'RESERVED' ? ' text-dark' : '' ?>">
                                            <?= e($item['display_stock_state']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold fs-6 <?= $item['quantity'] > 0 ? 'text-dark' : 'text-danger' ?>">
                                            <?= number_format($item['quantity']) ?> pcs
                                        </span>
                                        <?php if (!empty($item['min_stock']) && $item['quantity'] <= $item['min_stock']): ?>
                                            <br><span class="badge bg-danger-subtle text-danger border border-danger-subtle small"><i class="bi bi-exclamation-circle me-1"></i>Low Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-muted text-decoration-line-through font-monospace" id="mrp-cell-<?= $item['variant_id'] ?>">₹<?= number_format((float)($item['display_mrp'] ?? $item['mrp'] ?? 0), 2) ?></td>
                                    <td class="text-end fw-bold text-success font-monospace" id="selling-cell-<?= $item['variant_id'] ?>">₹<?= number_format((float)($item['display_selling_price'] ?? $item['selling_price'] ?? 0), 2) ?></td>
                                    <td class="text-end fw-bold text-dark font-monospace" id="val-cell-<?= $item['variant_id'] ?>">₹<?= number_format((float)($item['valuation'] ?? 0), 2) ?></td>
                                    <td class="text-end text-nowrap">
                                        <div class="d-inline-flex gap-1">
                                            <a href="<?= url('warehouse/stock/detail/' . $item['variant_id'] . (!empty($selectedWarehouseId) ? ('?warehouse_id=' . (int)$selectedWarehouseId) : '')) ?>" class="btn btn-sm btn-outline-primary px-2 py-1" title="View Stock Details & Ledger">
                                                <i class="bi bi-clock-history me-1"></i> History
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

<?php elseif ($currTab === 'third_party'): ?>
    <!-- 2. THIRD-PARTY PRODUCT INVENTORY SECTION -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-shop text-warning-emphasis me-2"></i> Third-Party Product Inventory (Vendors &amp; Suppliers)
                </h5>
                
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?php if (hasPermission('stock', 'edit')): ?>
                <button type="button" class="btn btn-outline-success shadow-sm fw-semibold btnTogglePriceEdit" onclick="togglePriceEditMode()">
                    <i class="bi bi-pencil-square me-1"></i> <span class="btnPriceEditLabel">Edit Price</span>
                </button>
                <button type="button" class="btn btn-success shadow-sm fw-semibold btnHeaderApplyPrice d-none" onclick="openBulkPriceModal()">
                    <i class="bi bi-check-circle-fill me-1"></i> Change Price (<span class="headerSelectedPriceCount">0</span>)
                </button>
                <?php endif; ?>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 fs-6">
                    <i class="bi bi-boxes me-1"></i> <?= number_format($tpAvailableQty ?? 0) ?> pcs Available
                </span>
                <span class="badge bg-light text-dark border px-3 py-2 fs-6">
                    Est. Valuation: <span id="thirdPartyValuationTotal" data-total="<?= (float)($tpValuation ?? 0) ?>">₹<?= number_format($tpValuation ?? 0, 2) ?></span>
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($thirdPartyItems)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-shop fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-muted">No Third-Party Product Stock Found</h5>
                    <p class="text-muted small">Go to the Warehouse Catalog and click <strong>"Update Inventory"</strong> on any product style to inward stock.</p>
                    <?php if (hasPermission('wh_catalog', 'view')): ?>
                        <a href="<?= url('warehouse/catalog') ?>" class="btn btn-warning px-4 py-2 text-dark fw-semibold">
                            <i class="bi bi-journal-bookmark me-1"></i> Open Warehouse Catalog
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="price-checkbox-col" style="display: none; width: 44px; text-align: center;">
                                    <input type="checkbox" class="form-check-input select-all-price-chk" onchange="toggleSelectAllPriceCheckboxes(this.checked)" title="Select all on this page">
                                </th>
                                <th>SKU / QR</th>
                                <th>Product Style &amp; Supplier</th>
                                <th>Category Type</th>
                                <th>Variant (Size / Color)</th>
                                <th>Stock State</th>
                                <th>Current Balance</th>
                                <th class="text-end">MRP / Base Price (₹)</th>
                                <th class="text-end">Discount Price (₹)</th>
                                <th class="text-end">Purchase Cost (₹)</th>
                                <th class="text-end">Total Valuation</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($thirdPartyItems as $item): ?>
                                <tr id="stock-row-<?= $item['variant_id'] ?>">
                                    <td class="price-checkbox-col text-center" style="display: none;">
                                        <input type="checkbox" class="form-check-input price-item-checkbox" 
                                               value="<?= (int)$item['variant_id'] ?>" 
                                               data-sku="<?= e($item['sku'] ?? '') ?>" 
                                               data-product-name="<?= e($item['product_name'] ?? '') ?>" 
                                               data-selling-price="<?= (float)($item['display_selling_price'] ?? $item['selling_price'] ?? 0) ?>" 
                                               data-mrp="<?= (float)($item['display_mrp'] ?? $item['mrp'] ?? 0) ?>" 
                                               data-cost-price="<?= (float)($item['purchase_price'] > 0 ? $item['purchase_price'] : ($item['production_cost'] ?? 0)) ?>" 
                                               data-valuation-basis="discount"
                                               data-stock-state="<?= e($item['stock_state']) ?>"
                                               data-quantity="<?= (float)($item['quantity'] ?? 0) ?>" 
                                               onchange="updatePriceSelectionState()">
                                    </td>
                                    <td>
                                        <span class="fw-bold font-monospace text-primary"><?= e($item['sku']) ?></span>
                                        <?php if (!empty($item['qr_code'])): ?>
                                            <br><small class="text-muted"><i class="bi bi-qr-code me-1"></i><?= e($item['qr_code']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark d-flex align-items-center gap-1.5 flex-wrap">
                                            <span><?= e($item['product_name']) ?></span>
                                            <?php if (!empty($item['is_complementary']) || !empty($item['variant_is_complementary'])): ?>
                                                <span class="badge bg-warning text-dark border border-warning fw-bold px-2 py-0.5 shadow-xs" style="font-size: 0.70rem;" title="Complementary Product (Direct Warehouse Live Stock)">
                                                    <i class="bi bi-gift-fill me-1 text-danger"></i>COMPLEMENTARY
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                            <span class="badge bg-warning-subtle text-dark border border-warning small font-monospace"><?= e($item['product_code']) ?></span>
                                            <?php 
                                                $catParts = array_filter([$item['category_name'] ?? '', $item['subcategory_name'] ?? '', $item['sub_subcategory_name'] ?? '']);
                                                if (!empty($catParts)):
                                            ?>
                                                <span class="badge bg-light text-primary border small font-monospace"><i class="bi bi-diagram-3-fill me-1 text-warning"></i><?= e(implode(' › ', $catParts)) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['seller_name'])): ?>
                                                <span class="badge bg-info-subtle text-info-emphasis border small"><i class="bi bi-shop me-1"></i><?= e($item['seller_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['brand_name'])): ?>
                                                <span class="badge bg-secondary-subtle text-secondary border small"><?= e($item['brand_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= renderCategoryTypeBadge($item) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark px-2 py-1"><?= e($item['size_name']) ?></span>
                                        <span class="d-inline-flex align-items-center gap-1 ms-1">
                                            <?php if (!empty($item['color_hex'])): ?>
                                                <span class="rounded-circle d-inline-block border" style="width:10px; height:10px; background-color:<?= e($item['color_hex']) ?>;"></span>
                                            <?php endif; ?>
                                            <span class="small"><?= e($item['color_name']) ?></span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= match ($item['display_stock_state']) { 'AVAILABLE' => 'success', 'RESERVED' => 'warning', 'OUT OF STOCK' => 'secondary', default => 'danger' } ?><?= $item['display_stock_state'] === 'RESERVED' ? ' text-dark' : '' ?>">
                                            <?= e($item['display_stock_state']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold fs-6 <?= $item['quantity'] > 0 ? 'text-dark' : 'text-danger' ?>">
                                            <?= number_format($item['quantity']) ?> pcs
                                        </span>
                                        <?php if (!empty($item['min_stock']) && $item['quantity'] <= $item['min_stock']): ?>
                                            <br><span class="badge bg-danger-subtle text-danger border border-danger-subtle small"><i class="bi bi-exclamation-circle me-1"></i>Low Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-muted text-decoration-line-through font-monospace" id="mrp-cell-<?= $item['variant_id'] ?>">₹<?= number_format((float)($item['display_mrp'] ?? $item['mrp'] ?? 0), 2) ?></td>
                                    <td class="text-end fw-bold text-success font-monospace" id="selling-cell-<?= $item['variant_id'] ?>">₹<?= number_format((float)($item['display_selling_price'] ?? $item['selling_price'] ?? 0), 2) ?></td>
                                    <td class="text-end text-primary font-monospace" id="cost-cell-<?= $item['variant_id'] ?>">₹<?= number_format((float)($item['purchase_price'] > 0 ? $item['purchase_price'] : ($item['production_cost'] ?? 0)), 2) ?></td>
                                    <td class="text-end fw-bold text-dark font-monospace" id="val-cell-<?= $item['variant_id'] ?>">₹<?= number_format((float)($item['valuation'] ?? 0), 2) ?></td>
                                    <td class="text-end text-nowrap">
                                        <div class="d-inline-flex gap-1">
                                            <a href="<?= url('warehouse/stock/detail/' . $item['variant_id'] . (!empty($selectedWarehouseId) ? ('?warehouse_id=' . (int)$selectedWarehouseId) : '')) ?>" class="btn btn-sm btn-outline-primary px-2 py-1" title="View Stock Details & Ledger">
                                                <i class="bi bi-clock-history me-1"></i> History
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

<?php elseif ($currTab === 'complementary'): ?>
    <!-- 3. COMPLEMENTARY PRODUCT INVENTORY SECTION -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-gift-fill text-warning me-2"></i> Complementary Product Inventory (Special Items &amp; Offers)
                </h5>
                <small class="text-muted">Separate inventory for complementary items, gifts, and promotional products</small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?php if (hasPermission('stock', 'edit')): ?>
                <button type="button" class="btn btn-outline-success shadow-sm fw-semibold btnTogglePriceEdit" onclick="togglePriceEditMode()">
                    <i class="bi bi-pencil-square me-1"></i> <span class="btnPriceEditLabel">Edit Price</span>
                </button>
                <button type="button" class="btn btn-success shadow-sm fw-semibold btnHeaderApplyPrice d-none" onclick="openBulkPriceModal()">
                    <i class="bi bi-check-circle-fill me-1"></i> Change Price (<span class="headerSelectedPriceCount">0</span>)
                </button>
                <?php endif; ?>
                <span class="badge bg-warning-subtle text-dark border border-warning px-3 py-2 fs-6">
                    <i class="bi bi-boxes me-1"></i> <?= number_format($compAvailableQty ?? 0) ?> pcs Available
                </span>
                <span class="badge bg-light text-dark border px-3 py-2 fs-6">
                    Est. Valuation: ₹<?= number_format($compValuation ?? 0, 2) ?>
                </span>
                <?php if (($compSoldQty ?? 0) > 0 || ($compSoldRevenue ?? 0) > 0): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                        <i class="bi bi-cash-coin me-1"></i> Realized Sales: ₹<?= number_format($compSoldRevenue ?? 0, 2) ?> (<?= number_format($compSoldQty ?? 0) ?> sold)
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($complementaryItems)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-gift fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-muted">No Complementary Product Stock Found</h5>
                    <p class="text-muted small">Add a new complementary product from the warehouse catalog or mark existing catalog styles as complementary.</p>
                    <?php if (hasPermission('wh_catalog', 'create')): ?>
                        <a href="<?= url('warehouse/catalog/create') ?>" class="btn btn-warning px-4 py-2 text-dark fw-semibold">
                            <i class="bi bi-plus-circle me-1"></i> Add Complementary Product
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="price-checkbox-col" style="display: none; width: 44px; text-align: center;">
                                    <input type="checkbox" class="form-check-input select-all-price-chk" onchange="toggleSelectAllPriceCheckboxes(this.checked)" title="Select all on this page">
                                </th>
                                <th>SKU / QR</th>
                                <th>Product Style &amp; Supplier</th>
                                <th>Category Type</th>
                                <th>Variant (Size / Color)</th>
                                <th>Stock State</th>
                                <th>Current Balance</th>
                                <th class="text-end">MRP / Base Price (₹)</th>
                                <th class="text-end">Selling Price (₹)</th>
                                <th class="text-end">Purchase Cost (₹)</th>
                                <th class="text-end">Total Valuation</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complementaryItems as $item): ?>
                                <tr id="stock-row-<?= $item['variant_id'] ?>">
                                    <td class="price-checkbox-col text-center" style="display: none;">
                                        <input type="checkbox" class="form-check-input price-item-checkbox" 
                                               value="<?= (int)$item['variant_id'] ?>" 
                                               data-sku="<?= e($item['sku'] ?? '') ?>" 
                                               data-product-name="<?= e($item['product_name'] ?? '') ?>" 
                                               data-selling-price="<?= (float)($item['display_selling_price'] ?? $item['selling_price'] ?? 0) ?>" 
                                               data-mrp="<?= (float)($item['display_mrp'] ?? $item['mrp'] ?? 0) ?>" 
                                               data-cost-price="<?= (float)($item['purchase_price'] > 0 ? $item['purchase_price'] : ($item['production_cost'] ?? 0)) ?>" 
                                               data-valuation-basis="discount"
                                               data-quantity="<?= (float)($item['quantity'] ?? 0) ?>" 
                                               onchange="updatePriceSelectionState()">
                                    </td>
                                    <td>
                                        <span class="fw-bold font-monospace text-primary"><?= e($item['sku']) ?></span>
                                        <?php if (!empty($item['qr_code'])): ?>
                                            <br><small class="text-muted"><i class="bi bi-qr-code me-1"></i><?= e($item['qr_code']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark d-flex align-items-center gap-1.5 flex-wrap">
                                            <span><?= e($item['product_name']) ?></span>
                                            <span class="badge bg-warning text-dark border border-warning fw-bold px-2 py-0.5 shadow-xs" style="font-size: 0.70rem;" title="Complementary Product (Separate from Third-Party)">
                                                <i class="bi bi-gift-fill me-1 text-danger"></i>COMPLEMENTARY
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                            <span class="badge bg-warning-subtle text-dark border border-warning small font-monospace"><?= e($item['product_code']) ?></span>
                                            <?php 
                                                $catParts = array_filter([$item['category_name'] ?? '', $item['subcategory_name'] ?? '', $item['sub_subcategory_name'] ?? '']);
                                                if (!empty($catParts)):
                                            ?>
                                                <span class="badge bg-light text-primary border small font-monospace"><i class="bi bi-diagram-3-fill me-1 text-warning"></i><?= e(implode(' › ', $catParts)) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['seller_name'])): ?>
                                                <span class="badge bg-info-subtle text-info-emphasis border small"><i class="bi bi-shop me-1"></i><?= e($item['seller_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['brand_name'])): ?>
                                                <span class="badge bg-secondary-subtle text-secondary border small"><?= e($item['brand_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= renderCategoryTypeBadge($item) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark px-2 py-1"><?= e($item['size_name']) ?></span>
                                        <span class="d-inline-flex align-items-center gap-1 ms-1">
                                            <?php if (!empty($item['color_hex'])): ?>
                                                <span class="rounded-circle d-inline-block border" style="width:10px; height:10px; background-color:<?= e($item['color_hex']) ?>;"></span>
                                            <?php endif; ?>
                                            <span class="small"><?= e($item['color_name']) ?></span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= match ($item['display_stock_state']) { 'AVAILABLE' => 'success', 'RESERVED' => 'warning', 'OUT OF STOCK' => 'secondary', default => 'danger' } ?><?= $item['display_stock_state'] === 'RESERVED' ? ' text-dark' : '' ?>">
                                            <?= e($item['display_stock_state']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold fs-6 <?= $item['quantity'] > 0 ? 'text-dark' : 'text-danger' ?>">
                                            <?= number_format($item['quantity']) ?> pcs
                                        </span>
                                        <?php if (!empty($item['min_stock']) && $item['quantity'] <= $item['min_stock']): ?>
                                            <br><span class="badge bg-danger-subtle text-danger border border-danger-subtle small"><i class="bi bi-exclamation-circle me-1"></i>Low Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-muted text-decoration-line-through font-monospace" id="mrp-cell-<?= $item['variant_id'] ?>">₹<?= number_format((float)($item['display_mrp'] ?? $item['mrp'] ?? 0), 2) ?></td>
                                    <td class="text-end fw-bold text-success font-monospace" id="selling-cell-<?= $item['variant_id'] ?>">₹<?= number_format((float)($item['display_selling_price'] ?? $item['selling_price'] ?? 0), 2) ?></td>
                                    <td class="text-end text-primary font-monospace" id="cost-cell-<?= $item['variant_id'] ?>">₹<?= number_format((float)($item['purchase_price'] > 0 ? $item['purchase_price'] : ($item['production_cost'] ?? 0)), 2) ?></td>
                                    <td class="text-end fw-bold text-dark font-monospace" id="val-cell-<?= $item['variant_id'] ?>">₹<?= number_format((float)($item['valuation'] ?? 0), 2) ?></td>
                                    <td class="text-end text-nowrap">
                                        <div class="d-inline-flex gap-1">
                                            <a href="<?= url('warehouse/stock/detail/' . $item['variant_id'] . (!empty($selectedWarehouseId) ? ('?warehouse_id=' . (int)$selectedWarehouseId) : '')) ?>" class="btn btn-sm btn-outline-primary px-2 py-1" title="View Stock Details & Ledger">
                                                <i class="bi bi-clock-history me-1"></i> History
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

<?php endif; ?>

<!-- Floating Bulk Price Action Toolbar (Active when items are selected) -->
<div id="priceEditFloatingBar" class="card shadow-lg border-0 position-fixed bottom-0 start-50 translate-middle-x mb-4 px-4 py-2 z-3 d-none align-items-center flex-row gap-3 rounded-pill bg-dark text-white border border-light border-opacity-25" style="backdrop-filter: blur(12px); z-index: 1060; max-width: 92vw; box-shadow: 0 10px 25px rgba(0,0,0,0.3) !important;">
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-success rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
            <i class="bi bi-check2 text-white fw-bold"></i>
        </span>
        <span class="fw-semibold text-nowrap"><span id="floatingSelectedCount">0</span> selected</span>
    </div>
    <div class="vr bg-secondary my-1"></div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="selectAllPriceItems(true)">
            <i class="bi bi-check-all me-1"></i> Select All
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary text-white-50 rounded-pill px-2.5" onclick="clearPriceSelection()">
            Clear
        </button>
        <button type="button" class="btn btn-sm btn-success rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" onclick="openBulkPriceModal()">
            <i class="bi bi-pencil-square"></i>
            <span>Change Price</span>
        </button>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT PRODUCT PRICE & MARGIN DYNAMICS (Bulk & Single Supported)     -->
<!-- ========================================================================= -->
<div class="modal fade" id="editPriceModal" tabindex="-1" aria-labelledby="editPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <!-- Emerald Green Header -->
            <div class="modal-header py-3 px-4 border-0" style="background-color: #127547; color: #ffffff;">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-circle p-1.5 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.15);">
                        <i class="bi bi-tag-fill fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="editPriceModalLabel" style="font-size: 1.15rem; letter-spacing: -0.2px;">Edit Product Price</h5>
                        <div class="text-white-50 font-monospace small" id="modalPriceHeaderSku" style="font-size: 0.76rem; letter-spacing: 0.5px;">SKU: ---</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="editPriceModalForm" novalidate onsubmit="handlePriceSubmit(event)">
                <input type="hidden" name="variant_ids" id="modalVariantIdsInput" value="">
                <input type="hidden" id="modalCostPriceRaw" value="0">
                <input type="hidden" id="modalQuantityRaw" value="0">

                <div class="modal-body p-4 bg-white">
                    <!-- Product Info / Selected Items Box -->
                    <div class="border rounded-3 p-3 mb-3.5 bg-light-subtle shadow-2xs" id="modalProductInfoBox">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold text-dark" id="modalPriceProductName" style="font-size: 0.95rem;">
                                Product Name Loading...
                            </span>
                            <span class="badge bg-success" id="modalSelectedCountBadge" style="display: none;">0 Products</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap" id="modalSingleInfoTags">
                            <span class="badge bg-dark text-white font-monospace px-2.5 py-1" id="modalPriceSkuBadge" style="font-size: 0.74rem;">
                                SKU-CODE
                            </span>
                            <span class="badge bg-light text-secondary border px-2.5 py-1 font-monospace" id="modalPriceCostBadge" style="font-size: 0.74rem;">
                                Cost: ₹0.00
                            </span>
                        </div>
                        <div id="modalSelectedBadgesList" class="d-flex flex-wrap gap-1 mt-1" style="max-height: 100px; overflow-y: auto; display: none;">
                            <!-- Populated dynamically with SKU chips in bulk mode -->
                        </div>
                    </div>

                    <!-- MRP & Discount Price Row -->
                    <div class="row g-3 mb-3.5">
                        <!-- Maximum Retail Price (MRP) Input (First) -->
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark text-uppercase mb-1" style="font-size: 0.74rem; letter-spacing: 0.3px;">
                                Maximum Retail Price (MRP ₹) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-secondary fw-bold border-end-0 fs-5 ps-3 pe-2" style="border-color: #cbd5e1;">₹</span>
                                <input type="number" step="0.01" min="0.01" name="mrp" id="modalMrpInput" 
                                       class="form-control border-start-0 fs-5 fw-bold text-dark ps-1" 
                                       style="border-color: #cbd5e1;" placeholder="0.00" required oninput="recalcPriceDynamics()">
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Printed sticker / catalog MRP</small>
                        </div>

                        <!-- Discount Price Input (Second) -->
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark text-uppercase mb-1" style="font-size: 0.74rem; letter-spacing: 0.3px;">
                                Discount Price (₹) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-success fw-bold border-end-0 fs-5 ps-3 pe-2" style="border-color: #cbd5e1;">₹</span>
                                <input type="number" step="0.01" min="0.01" name="selling_price" id="modalSellingPriceInput" 
                                       class="form-control border-start-0 fs-5 fw-bold text-success ps-1" 
                                       style="border-color: #cbd5e1;" placeholder="0.00" required oninput="recalcPriceDynamics()">
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Active retail selling rate at POS</small>
                        </div>
                    </div>
                    <div class="alert alert-danger py-2 px-3 mb-3" id="modalPriceValidationMessage" role="alert" style="display: none;">
                        MRP must be greater than the discount price.
                    </div>

                    <!-- Price Dynamics & Margin Box -->
                    <div class="border rounded-3 p-3 mb-3.5" style="background-color: #fcfdfd; border-color: #e2e8f0 !important;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 0.70rem; letter-spacing: 0.5px;">
                                Price Dynamics &amp; Margin
                            </span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace px-2 py-0.5" id="modalDiscountBadge" style="font-size: 0.72rem; border-radius: 6px; display: none;">
                                0% Discount
                            </span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-1" style="font-size: 0.84rem;">
                            <div>
                                <span class="text-muted">Markup over Cost:</span>
                                <strong class="text-success ms-1 font-monospace" id="modalMarkupVal">—</strong>
                            </div>
                            <div>
                                <span class="text-muted">Discount on MRP:</span>
                                <strong class="text-primary ms-1 font-monospace" id="modalDiscountVal">0% Off</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Revision Notes / Reason -->
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.3px;">
                            Revision Notes / Reason (Optional)
                        </label>
                        <input type="text" name="revision_notes" id="modalRevisionNotesInput" class="form-control py-2" style="border-color: #cbd5e1; font-size: 0.85rem;" placeholder="e.g. Bulk price revision, seasonal promotion">
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="modal-footer px-4 py-3 bg-white border-0 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-light border px-4 py-2 fw-semibold" style="border-radius: 24px; color: #475569; font-size: 0.88rem;" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" id="btnSavePrice" class="btn btn-success px-4 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm" style="border-radius: 24px; background-color: #127547; border-color: #127547; font-size: 0.88rem;">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Apply Price Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let isPriceEditMode = false;
let currentPriceModalInstance = null;

function togglePriceEditMode(forceState) {
    if (typeof forceState === 'boolean') {
        isPriceEditMode = forceState;
    } else {
        isPriceEditMode = !isPriceEditMode;
    }
    
    const checkboxCols = document.querySelectorAll('.price-checkbox-col');
    checkboxCols.forEach(col => {
        col.style.display = isPriceEditMode ? '' : 'none';
    });
    
    const btns = document.querySelectorAll('.btnTogglePriceEdit, #btnTogglePriceEdit');
    const labels = document.querySelectorAll('.btnPriceEditLabel, #btnPriceEditLabel');
    btns.forEach(btn => {
        if (isPriceEditMode) {
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-success');
        } else {
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-success');
        }
    });
    labels.forEach(lbl => {
        lbl.innerText = isPriceEditMode ? 'Close Price Edit' : 'Edit Price';
    });
    if (!isPriceEditMode) {
        clearPriceSelection();
    }
    updatePriceSelectionState();
}

function getSelectedPriceCheckboxes() {
    return Array.from(document.querySelectorAll('.price-item-checkbox:checked'));
}

function updatePriceSelectionState() {
    const selected = getSelectedPriceCheckboxes();
    const count = selected.length;
    
    const floatingBar = document.getElementById('priceEditFloatingBar');
    const headerBtns = document.querySelectorAll('.btnHeaderApplyPrice, #btnHeaderApplyPrice');
    const floatCount = document.getElementById('floatingSelectedCount');
    const headerCounts = document.querySelectorAll('.headerSelectedPriceCount, #headerSelectedPriceCount');
    
    if (floatCount) floatCount.innerText = count;
    headerCounts.forEach(el => el.innerText = count);
    
    if (isPriceEditMode && count > 0) {
        if (floatingBar) floatingBar.classList.remove('d-none');
        headerBtns.forEach(btn => btn.classList.remove('d-none'));
    } else {
        if (floatingBar) floatingBar.classList.add('d-none');
        headerBtns.forEach(btn => btn.classList.add('d-none'));
    }
    
    // Highlight selected rows
    document.querySelectorAll('.price-item-checkbox').forEach(chk => {
        const tr = chk.closest('tr');
        if (tr) {
            if (chk.checked) {
                tr.classList.add('table-success');
            } else {
                tr.classList.remove('table-success');
            }
        }
    });
}

function toggleSelectAllPriceCheckboxes(checked) {
    const checkboxes = document.querySelectorAll('.price-item-checkbox');
    checkboxes.forEach(chk => {
        const table = chk.closest('table');
        if (table && table.offsetParent !== null) {
            chk.checked = checked;
        }
    });
    const selectAllHeaders = document.querySelectorAll('.select-all-price-chk');
    selectAllHeaders.forEach(chk => chk.checked = checked);
    updatePriceSelectionState();
}

function selectAllPriceItems(checked) {
    const checkboxes = document.querySelectorAll('.price-item-checkbox');
    checkboxes.forEach(chk => {
        const table = chk.closest('table');
        if (table && table.offsetParent !== null) {
            chk.checked = checked;
        }
    });
    const selectAllHeaders = document.querySelectorAll('.select-all-price-chk');
    selectAllHeaders.forEach(chk => chk.checked = checked);
    updatePriceSelectionState();
}

function clearPriceSelection() {
    const checkboxes = document.querySelectorAll('.price-item-checkbox');
    checkboxes.forEach(chk => chk.checked = false);
    const selectAllHeaders = document.querySelectorAll('.select-all-price-chk');
    selectAllHeaders.forEach(chk => chk.checked = false);
    updatePriceSelectionState();
}

function openBulkPriceModal() {
    const selected = getSelectedPriceCheckboxes();
    if (selected.length === 0) {
        alert('Please select at least one product using the checkboxes.');
        return;
    }
    
    const variantIds = selected.map(chk => chk.value);
    document.getElementById('modalVariantIdsInput').value = variantIds.join(',');
    
    const countBadge = document.getElementById('modalSelectedCountBadge');
    const singleTags = document.getElementById('modalSingleInfoTags');
    const badgesContainer = document.getElementById('modalSelectedBadgesList');
    badgesContainer.innerHTML = '';
    
    let totalCost = 0;
    let costCount = 0;
    let firstSelling = '';
    let firstMrp = '';
    
    selected.forEach((chk, idx) => {
        const sku = chk.dataset.sku || ('Variant #' + chk.value);
        const name = chk.dataset.productName || '';
        const cost = parseFloat(chk.dataset.costPrice) || 0;
        const sp = parseFloat(chk.dataset.sellingPrice) || 0;
        const mrp = parseFloat(chk.dataset.mrp) || 0;
        
        if (cost > 0) {
            totalCost += cost;
            costCount++;
        }
        if (idx === 0) {
            firstSelling = sp > 0 ? sp.toFixed(2) : '';
            firstMrp = mrp > 0 ? mrp.toFixed(2) : '';
        }
        
        const badge = document.createElement('span');
        badge.className = 'badge bg-secondary-subtle text-dark border font-monospace py-1 px-2';
        badge.style.fontSize = '0.74rem';
        badge.innerText = sku + (name ? ` (${name})` : '');
        badgesContainer.appendChild(badge);
    });
    
    if (variantIds.length === 1) {
        const firstChk = selected[0];
        document.getElementById('editPriceModalLabel').innerText = 'Edit Product Price';
        document.getElementById('modalPriceHeaderSku').innerText = 'SKU: ' + (firstChk.dataset.sku || '---');
        document.getElementById('modalPriceProductName').innerText = firstChk.dataset.productName || '1 Product Selected';
        document.getElementById('modalPriceSkuBadge').innerText = firstChk.dataset.sku || 'SKU';
        const cost = parseFloat(firstChk.dataset.costPrice) || 0;
        document.getElementById('modalCostPriceRaw').value = cost;
        document.getElementById('modalPriceCostBadge').innerText = 'Cost: ₹' + cost.toFixed(2);
        
        document.getElementById('modalSellingPriceInput').value = firstSelling;
        document.getElementById('modalMrpInput').value = firstMrp;
        
        countBadge.style.display = 'none';
        singleTags.style.display = 'flex';
        badgesContainer.style.display = 'none';
    } else {
        document.getElementById('editPriceModalLabel').innerText = 'Bulk Edit Product Prices';
        document.getElementById('modalPriceHeaderSku').innerText = `${variantIds.length} Products Selected`;
        document.getElementById('modalPriceProductName').innerText = `Batch Price Update (${variantIds.length} Products)`;
        
        const avgCost = costCount > 0 ? (totalCost / costCount) : 0;
        document.getElementById('modalCostPriceRaw').value = avgCost;
        
        document.getElementById('modalSellingPriceInput').value = firstSelling;
        document.getElementById('modalMrpInput').value = firstMrp;
        
        countBadge.innerText = `${variantIds.length} Products`;
        countBadge.style.display = 'inline-block';
        singleTags.style.display = 'none';
        badgesContainer.style.display = 'flex';
    }
    
    document.getElementById('modalRevisionNotesInput').value = '';
    recalcPriceDynamics();
    
    const modalEl = document.getElementById('editPriceModal');
    if (!currentPriceModalInstance) {
        currentPriceModalInstance = new bootstrap.Modal(modalEl);
    }
    currentPriceModalInstance.show();
}

// Backward compatibility for single direct invocation
function openEditPriceModal(data) {
    if (!data || !data.variantId) return;
    
    // Find checkbox if exists
    const chk = document.querySelector(`.price-item-checkbox[value="${data.variantId}"]`);
    if (chk) {
        chk.checked = true;
        updatePriceSelectionState();
        openBulkPriceModal();
        return;
    }
    
    // Fallback if triggered from a page without checkboxes
    document.getElementById('modalVariantIdsInput').value = data.variantId;
    document.getElementById('modalPriceHeaderSku').innerText = 'SKU: ' + (data.sku || '---');
    document.getElementById('modalPriceProductName').innerText = data.productName || 'Product';
    document.getElementById('modalPriceSkuBadge').innerText = data.sku || 'SKU';
    
    const cost = parseFloat(data.costPrice) || 0;
    document.getElementById('modalCostPriceRaw').value = cost;
    document.getElementById('modalPriceCostBadge').innerText = 'Cost: ₹' + cost.toFixed(2);
    
    const selling = parseFloat(data.sellingPrice) || 0;
    const mrp = parseFloat(data.mrp) || selling;
    
    document.getElementById('modalSellingPriceInput').value = selling > 0 ? selling.toFixed(2) : '';
    document.getElementById('modalMrpInput').value = mrp > 0 ? mrp.toFixed(2) : '';
    document.getElementById('modalRevisionNotesInput').value = '';
    
    document.getElementById('modalSelectedCountBadge').style.display = 'none';
    document.getElementById('modalSingleInfoTags').style.display = 'flex';
    document.getElementById('modalSelectedBadgesList').style.display = 'none';
    
    recalcPriceDynamics();
    
    const modalEl = document.getElementById('editPriceModal');
    if (!currentPriceModalInstance) {
        currentPriceModalInstance = new bootstrap.Modal(modalEl);
    }
    currentPriceModalInstance.show();
}

function recalcPriceDynamics() {
    const cost = parseFloat(document.getElementById('modalCostPriceRaw').value) || 0;
    const selling = parseFloat(document.getElementById('modalSellingPriceInput').value) || 0;
    const mrp = parseFloat(document.getElementById('modalMrpInput').value) || 0;
    const sellingInput = document.getElementById('modalSellingPriceInput');
    const priceValidationMessage = document.getElementById('modalPriceValidationMessage');
    const invalidPrice = selling > 0 && mrp <= selling;

    sellingInput.setCustomValidity(
        invalidPrice ? 'MRP must be greater than the discount price.' : ''
    );
    sellingInput.classList.toggle('is-invalid', invalidPrice);
    priceValidationMessage.style.display = invalidPrice ? 'block' : 'none';
    
    // Markup over cost
    const markupEl = document.getElementById('modalMarkupVal');
    if (cost > 0 && selling > 0) {
        const markup = ((selling - cost) / cost) * 100;
        const sign = markup >= 0 ? '+' : '';
        markupEl.innerText = sign + Math.round(markup) + '%';
        markupEl.className = (markup >= 0 ? 'text-success' : 'text-danger') + ' ms-1 font-monospace';
    } else {
        markupEl.innerText = '—';
        markupEl.className = 'text-muted ms-1 font-monospace';
    }
    
    // Discount on MRP
    const discountValEl = document.getElementById('modalDiscountVal');
    const discountBadgeEl = document.getElementById('modalDiscountBadge');
    
    if (mrp > 0 && mrp > selling && selling > 0) {
        const disc = ((mrp - selling) / mrp) * 100;
        const discRound = Math.round(disc);
        discountValEl.innerText = discRound + '% Off';
        discountValEl.className = 'text-primary ms-1 font-monospace';
        
        discountBadgeEl.style.display = 'inline-block';
        discountBadgeEl.innerText = discRound + '% Discount';
    } else if (mrp > 0 && selling >= mrp) {
        discountValEl.innerText = '0% Off';
        discountValEl.className = 'text-muted ms-1 font-monospace';
        discountBadgeEl.style.display = 'none';
    } else {
        discountValEl.innerText = '0% Off';
        discountValEl.className = 'text-muted ms-1 font-monospace';
        discountBadgeEl.style.display = 'none';
    }
}

function handlePriceSubmit(e) {
    e.preventDefault();
    recalcPriceDynamics();
    if (!e.currentTarget.reportValidity()) return;

    const btn = document.getElementById('btnSavePrice');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...';
    
    const variantIds = document.getElementById('modalVariantIdsInput').value;
    const sellingPrice = document.getElementById('modalSellingPriceInput').value;
    const mrp = document.getElementById('modalMrpInput').value;
    const notes = document.getElementById('modalRevisionNotesInput').value;
    
    const formData = new FormData();
    formData.append('variant_ids', variantIds);
    formData.append('selling_price', sellingPrice);
    formData.append('mrp', mrp);
    formData.append('revision_notes', notes);
    
    fetch('<?= url('warehouse/stock/update-price') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        
        if (res.success) {
            // Update table cells dynamically
            const updated = res.updated_variants || [];
            if (updated.length === 0 && res.variant_id) {
                updated.push({
                    variant_id: res.variant_id,
                    formatted_mrp: res.formatted_mrp,
                    formatted_selling_price: res.formatted_selling_price,
                    new_selling_price: res.new_selling_price,
                    new_mrp: res.new_mrp
                });
            }
            
            updated.forEach(item => {
                const vId = item.variant_id;
                const mrpCell = document.getElementById('mrp-cell-' + vId);
                const sellingCell = document.getElementById('selling-cell-' + vId);
                const valCell = document.getElementById('val-cell-' + vId);
                const row = document.getElementById('stock-row-' + vId);
                
                if (mrpCell) mrpCell.innerText = item.formatted_mrp;
                if (sellingCell) sellingCell.innerText = item.formatted_selling_price;
                
                // Update checkbox data attributes so subsequent edits have fresh data
                const chk = document.querySelector(`.price-item-checkbox[value="${vId}"]`);
                if (chk) {
                    const oldDisplayDiscountPrice = parseFloat(chk.dataset.sellingPrice) || 0;
                    const newSellingPrice = parseFloat(item.new_selling_price) || 0;
                    const newMrp = parseFloat(item.new_mrp) || 0;
                    const displayDiscountPrice = newMrp > 0 && newSellingPrice > 0
                        ? Math.min(newMrp, newSellingPrice)
                        : (newSellingPrice > 0 ? newSellingPrice : newMrp);
                    chk.dataset.sellingPrice = displayDiscountPrice;
                    chk.dataset.mrp = item.new_mrp;
                    const qty = parseFloat(chk.dataset.quantity) || 0;
                    if (valCell && qty > 0) {
                        const valuationPrice = chk.dataset.valuationBasis === 'cost'
                            ? (parseFloat(chk.dataset.costPrice) || 0)
                            : displayDiscountPrice;
                        const newVal = qty * valuationPrice;
                        valCell.innerText = '₹' + newVal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                    const valuationSummary = document.getElementById('thirdPartyValuationTotal');
                    if (valuationSummary && qty > 0 && chk.dataset.valuationBasis === 'discount' && chk.dataset.stockState === 'AVAILABLE') {
                        const updatedTotal = (parseFloat(valuationSummary.dataset.total) || 0)
                            + (qty * (displayDiscountPrice - oldDisplayDiscountPrice));
                        valuationSummary.dataset.total = updatedTotal;
                        valuationSummary.textContent = '₹' + updatedTotal.toLocaleString('en-IN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                }
                
                // Highlight changed row with green pulse
                if (row) {
                    row.classList.remove('table-success');
                    row.style.transition = 'background-color 0.4s ease';
                    row.style.backgroundColor = '#d1e7dd';
                    setTimeout(() => {
                        row.style.backgroundColor = '';
                    }, 2500);
                }
            });
            
            // Clear selections
            clearPriceSelection();
            
            if (currentPriceModalInstance) {
                currentPriceModalInstance.hide();
            }
            
            // Show toast or alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed bottom-0 end-0 m-3 shadow-lg z-3';
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                    <div>
                        <strong>Price Updated!</strong> ${res.message || 'Product pricing revised successfully.'}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            document.body.appendChild(alertDiv);
            setTimeout(() => {
                try { alertDiv.remove(); } catch(e) {}
            }, 4500);
        } else {
            alert(res.message || 'Failed to update price. Please try again.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        console.error(err);
        alert('Server connection error. Please try again.');
    });
}

// Enable row click to toggle checkbox when in price edit mode
document.addEventListener('click', function(e) {
    if (!isPriceEditMode) return;
    const tr = e.target.closest('tr[id^="stock-row-"]');
    if (!tr) return;
    if (e.target.closest('a, button, input[type="checkbox"], label')) return;
    const chk = tr.querySelector('.price-item-checkbox');
    if (chk) {
        chk.checked = !chk.checked;
        updatePriceSelectionState();
    }
});

// Live filter stock inventory rows by SKU, style code, product name
function liveFilterStockRows(query) {
    const term = (query || '').toLowerCase().trim();
    document.querySelectorAll('tr[id^="stock-row-"]').forEach(tr => {
        const text = tr.textContent.toLowerCase();
        tr.style.display = (!term || text.includes(term)) ? '' : 'none';
    });
}
</script>

<style>
.price-checkbox-col input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
tr.table-success td {
    background-color: rgba(25, 135, 84, 0.08) !important;
}
</style>
