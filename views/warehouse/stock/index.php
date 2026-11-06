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
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <!-- Main View Mode Switcher -->
        <div class="btn-group shadow-sm" role="group" id="mainStockViewSwitcher">
            <button type="button" class="btn <?= ($viewMode ?? 'ledger') !== 'pieces' ? 'btn-primary active' : 'btn-outline-primary bg-white text-dark' ?> fw-semibold" id="btnShowLedgerView" onclick="switchMainStockView('ledger')">
                <i class="bi bi-table me-1"></i> Ledger View
            </button>
            <button type="button" class="btn <?= ($viewMode ?? 'ledger') === 'pieces' ? 'btn-primary active' : 'btn-outline-primary bg-white text-dark' ?> fw-semibold" id="btnShowPieceView" onclick="switchMainStockView('pieces')">
                <i class="bi bi-card-list me-1"></i> Piece-by-Piece View <span class="badge bg-warning text-dark ms-1"><?= count($pieceItems ?? []) ?> pcs</span>
            </button>
        </div>

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

<!-- LEDGER / SUMMARY VIEW SECTION -->
<div id="ledgerStockViewSection" style="display: <?= ($viewMode ?? 'ledger') !== 'pieces' ? 'block' : 'none' ?>;">
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
</div><!-- /#ledgerStockViewSection -->

<!-- 2. PIECE-BY-PIECE WAREHOUSE INVENTORY SECTION (Image 2 Reference Built for Central Warehouse) -->
<div id="pieceStockViewSection" style="display: <?= ($viewMode ?? 'ledger') === 'pieces' ? 'block' : 'none' ?>;">

    <!-- Fast 2D QR Code Search & Quick Label Print Bar -->
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold fs-7"><i class="bi bi-qr-code-scan me-2"></i>Garment &amp; Product QR Code Search &amp; Quick Label Print</span>
            <span class="badge bg-white text-primary fw-bold fs-8" id="whPieceSearchMatchBadge">Showing all <?= count($pieceItems ?? []) ?> pieces</span>
        </div>
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-lg-7 col-md-6">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light border-primary"><i class="bi bi-upc-scan text-primary fs-5"></i></span>
                        <input type="text" id="whPieceSearchInput" class="form-control border-primary fw-bold" 
                               placeholder="Search or Scan Piece QR sticker tag (e.g. <?= (!empty($pieceItems[0]['tag_code']) ? $pieceItems[0]['tag_code'] : 'SSS112601') ?>)..." 
                               oninput="filterWhPieces()" onkeyup="if(event.key==='Enter') filterWhPieces()">
                        <button type="button" class="btn btn-outline-secondary" onclick="clearWhPieceSearch()" title="Clear Search">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="col-lg-5 col-md-6 d-flex gap-2 justify-content-md-end flex-wrap">
                    <button type="button" class="btn btn-primary btn-lg fw-semibold shadow-sm flex-grow-1 flex-md-grow-0" id="btnWhPrintFiltered" onclick="openWhBatchPrintModal()">
                        <i class="bi bi-printer-fill me-1"></i> Print Filtered Labels (<span id="whFilteredCountLabel"><?= count($pieceItems ?? []) ?></span>)
                    </button>
                </div>
            </div>

            <!-- Quick Sub-Filters Row -->
            <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                <span class="small text-muted fw-bold text-uppercase me-1"><i class="bi bi-funnel me-1"></i>Filter:</span>
                <button type="button" class="btn btn-sm btn-dark fw-semibold px-3 py-1 wh-source-filter-btn active" data-source="all" onclick="filterWhPieceSource('all', this)">
                    All Pieces (<?= count($pieceItems ?? []) ?>)
                </button>
                <button type="button" class="btn btn-sm btn-outline-success fw-semibold px-3 py-1 wh-source-filter-btn" data-source="OWN_PRODUCTION" onclick="filterWhPieceSource('OWN_PRODUCTION', this)">
                    <i class="bi bi-buildings me-1"></i> Production Unit (<?= count(array_filter($pieceItems ?? [], fn($p) => ($p['source_type'] ?? '') === 'OWN_PRODUCTION')) ?>)
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-semibold px-3 py-1 wh-source-filter-btn" data-source="EXTERNAL_PURCHASE" onclick="filterWhPieceSource('EXTERNAL_PURCHASE', this)">
                    <i class="bi bi-shop me-1"></i> Third-Party (<?= count(array_filter($pieceItems ?? [], fn($p) => ($p['source_type'] ?? '') === 'EXTERNAL_PURCHASE')) ?>)
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger fw-semibold px-3 py-1 wh-source-filter-btn" data-source="COMPLEMENTARY" onclick="filterWhPieceSource('COMPLEMENTARY', this)">
                    <i class="bi bi-gift-fill me-1"></i> Complementary (<?= count(array_filter($pieceItems ?? [], fn($p) => ($p['category_type'] ?? '') === 'COMPLEMENTARY')) ?>)
                </button>
                <button type="button" class="btn btn-sm btn-outline-info text-dark fw-semibold px-3 py-1 wh-source-filter-btn" data-source="SERIES" onclick="filterWhPieceSource('SERIES', this)">
                    <i class="bi bi-boxes me-1"></i> Series Pack (<?= count(array_filter($pieceItems ?? [], fn($p) => ($p['category_type'] ?? '') === 'SERIES')) ?>)
                </button>
            </div>
        </div>
    </div>

    <!-- Central Warehouse Pieces Main Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-boxes text-primary"></i>
                    Central Warehouse Individual Pieces
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fs-7 px-2" id="whPieceCountBadge">
                        <?= count($pieceItems ?? []) ?> In Stock / <?= count($pieceItems ?? []) ?> Total Pieces
                    </span>
                </h5>
                <span class="text-muted small">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                    Piece-by-piece inventory for <?= e($currentWarehouse['name'] ?? 'Central Distribution Warehouse') ?> (<?= count($pieceItems ?? []) ?> pieces available in warehouse)
                </span>
            </div>

            <!-- View Switcher within Piece Mode: List vs Cards -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary active fw-semibold" id="btnWhDisplayList" onclick="switchWhPieceDisplay('list')">
                        <i class="bi bi-list-ul me-1"></i> List (<?= count($pieceItems ?? []) ?>)
                    </button>
                    <button type="button" class="btn btn-outline-secondary fw-semibold" id="btnWhDisplayCard" onclick="switchWhPieceDisplay('card')">
                        <i class="bi bi-grid-3x3-gap me-1"></i> Cards
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-4">

            <!-- Empty Search State -->
            <div class="text-center text-muted py-5" id="whEmptyPieceSearchState" style="display: none;">
                <i class="bi bi-search fs-1 d-block mb-2 text-secondary"></i>
                <h6 class="fw-semibold mb-1">No Matching Warehouse Garment Tag Found</h6>
                <p class="small mb-0">No piece in warehouse stock matches the scanned or searched QR code.</p>
            </div>

            <?php if (empty($pieceItems)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-box-seam fs-1 d-block mb-3 text-secondary"></i>
                    <h6 class="fw-semibold mb-1">No Warehouse Stock Pieces Found</h6>
                    <p class="small text-muted mb-0">Inward production batches or vendor products into this warehouse to view individual pieces.</p>
                </div>
            <?php else: ?>
                <!-- 1. Piece List Container (Exact Image 2 Structure Adapted for Central Warehouse) -->
                <div class="list-group list-group-flush gap-2" id="whPieceListContainer" style="display: flex;">
                    <?php foreach ($pieceItems as $it): ?>
                        <article class="list-group-item inventory-piece-list-item d-flex flex-wrap align-items-center gap-3 p-3 wh-piece-row"
                                 id="whPieceRow_<?= $it['piece_no'] ?>"
                                 data-piece-no="<?= $it['piece_no'] ?>"
                                 data-tag="<?= strtoupper($it['tag_code']) ?>"
                                 data-sku="<?= strtoupper($it['sku']) ?>"
                                 data-size="<?= strtoupper($it['size_name']) ?>"
                                 data-color="<?= strtoupper($it['color_name']) ?>"
                                 data-product="<?= strtoupper($it['product_name']) ?>"
                                 data-source="<?= $it['source_type'] ?>"
                                 data-cat-type="<?= $it['category_type'] ?? 'NORMAL' ?>">
                            
                            <!-- 1. Piece # & QR Badge (Image 2 Left Column) -->
                            <div class="inventory-list-tag" style="min-width: 175px;">
                                <div class="small text-muted fw-semibold mb-1">Piece #<?= $it['piece_no'] ?></div>
                                <span class="badge bg-dark text-white font-monospace fs-7 px-2 py-1">
                                    <i class="bi bi-qr-code-scan me-1 text-warning"></i><?= e($it['tag_code']) ?>
                                </span>
                                <div class="small text-muted mt-1">Garment #<?= $it['piece_seq'] ?> of <?= $it['total_in_variant'] ?></div>
                            </div>

                            <!-- 2. Product Icon & Style Name with Breadcrumb (Image 2 Middle-Left Column) -->
                            <div class="inventory-list-product" style="min-width: 200px; flex: 1 1 220px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2 text-center d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi <?= $it['source_type'] === 'OWN_PRODUCTION' ? 'bi-bag-check' : 'bi-box-seam' ?> fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= e($it['product_name']) ?></div>
                                        <?php if (!empty($it['category_name'])): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-7"><?= e($it['category_name']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Variant, Status, Batch Info, Price (Image 2 Middle-Right Column) -->
                            <div class="inventory-list-meta d-flex flex-wrap gap-3 align-items-center" style="flex: 1 1 340px;">
                                <!-- Variant & Status Badges -->
                                <div>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-secondary-subtle text-secondary border font-monospace px-2 py-1">Size: <?= e($it['size_name']) ?></span>
                                        <span class="badge bg-info-subtle text-info border font-monospace px-2 py-1">Color: <?= e($it['color_name']) ?></span>
                                    </div>
                                    <div class="mt-1">
                                        <?php if ($it['source_type'] === 'OWN_PRODUCTION'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 d-inline-block" style="font-size: 11px;">
                                                <i class="bi bi-patch-check me-1"></i>QC PASSED
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5 d-inline-block" style="font-size: 11px;">
                                                <i class="bi bi-check2-circle me-1"></i>VERIFIED INWARD
                                            </span>
                                        <?php endif; ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 d-inline-block ms-1" style="font-size: 11px;">
                                            <i class="bi bi-building-check me-1"></i>AVAILABLE
                                        </span>
                                    </div>
                                </div>

                                <!-- QC Batch & Lineage Info (Warehouse Context) -->
                                <div>
                                    <?php if ($it['source_type'] === 'OWN_PRODUCTION'): ?>
                                        <div>
                                            <a href="<?= url('production/qc/inspect/' . $it['qc_batch_id']) ?>" class="fw-semibold text-decoration-none">
                                                QC Batch #<?= $it['qc_batch_id'] ?>
                                            </a>
                                        </div>
                                        <small class="text-muted d-block">
                                            Program: <span class="text-primary"><?= e($it['program_number']) ?></span>
                                        </small>
                                        <small class="text-secondary" style="font-size: 11px;">Batch: <?= e($it['batch_number']) ?></small>
                                    <?php else: ?>
                                        <div>
                                            <span class="fw-semibold text-dark"><i class="bi bi-shop me-1 text-primary"></i><?= e($it['seller_name'] ?? 'Vendor Partner') ?></span>
                                        </div>
                                        <small class="text-muted d-block"><?= e($it['batch_number']) ?></small>
                                        <small class="text-secondary" style="font-size: 11px;"><i class="bi bi-geo-alt me-1"></i><?= e($it['warehouse_name']) ?></small>
                                    <?php endif; ?>
                                </div>

                                <!-- Price & MRP -->
                                <div>
                                    <div class="fw-semibold text-dark fs-6">₹<?= number_format($it['selling_price'], 2) ?></div>
                                    <?php if ($it['mrp'] > $it['selling_price']): ?>
                                        <small class="text-muted text-decoration-line-through">₹<?= number_format($it['mrp'], 2) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 4. Actions Dropdown (Image 2 Right Column) -->
                            <div class="inventory-list-actions d-flex justify-content-end ms-auto">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <button type="button" class="dropdown-item" onclick="printSinglePieceLabel(<?= htmlspecialchars(json_encode($it), ENT_QUOTES, 'UTF-8') ?>)">
                                                <i class="bi bi-printer me-2 text-primary"></i>Print QR Label
                                            </button>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= url('warehouse/stock/detail/' . $it['variant_id'] . '?warehouse_id=' . ($selectedWarehouseId ?? 1)) ?>">
                                                <i class="bi bi-clock-history me-2 text-info"></i>Stock Ledger &amp; History
                                            </a>
                                        </li>
                                        <?php if (hasPermission('allocation', 'create')): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?= url('warehouse/allocation') ?>">
                                                <i class="bi bi-arrow-right-circle me-2 text-success"></i>Shop Allocation
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        <?php if (hasPermission('damage', 'create')): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="<?= url('warehouse/damage/create?variant_id=' . $it['variant_id'] . '&qr_code=' . urlencode($it['tag_code'])) ?>">
                                                <i class="bi bi-exclamation-triangle me-2"></i>Report Damage / Quarantine
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>

                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- 2. Piece Card Grid Container -->
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" id="whPieceCardsContainer" style="display: none;">
                    <?php foreach ($pieceItems as $it): ?>
                        <div class="col wh-piece-card-col" 
                             id="whPieceCard_<?= $it['piece_no'] ?>"
                             data-piece-no="<?= $it['piece_no'] ?>"
                             data-tag="<?= strtoupper($it['tag_code']) ?>"
                             data-sku="<?= strtoupper($it['sku']) ?>"
                             data-size="<?= strtoupper($it['size_name']) ?>"
                             data-color="<?= strtoupper($it['color_name']) ?>"
                             data-product="<?= strtoupper($it['product_name']) ?>"
                             data-source="<?= $it['source_type'] ?>"
                             data-cat-type="<?= $it['category_type'] ?? 'NORMAL' ?>">
                            <article class="card inventory-piece-card h-100 shadow-sm border">
                                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-start gap-2 pt-3">
                                    <span class="badge bg-secondary-subtle text-secondary font-monospace">Piece #<?= $it['piece_no'] ?></span>
                                    <div class="text-end">
                                        <span class="badge bg-dark text-white font-monospace fs-7 px-2 py-1">
                                            <i class="bi bi-qr-code-scan me-1 text-warning"></i><?= e($it['tag_code']) ?>
                                        </span>
                                        <div class="small text-muted mt-1" style="font-size: 11px;">Garment #<?= $it['piece_seq'] ?> of <?= $it['total_in_variant'] ?></div>
                                    </div>
                                </div>
                                <div class="card-body pt-1">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded p-2 text-center d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                            <i class="bi <?= $it['source_type'] === 'OWN_PRODUCTION' ? 'bi-bag-check' : 'bi-box-seam' ?> fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 220px;" title="<?= e($it['product_name']) ?>"><?= e($it['product_name']) ?></div>
                                            <?php if (!empty($it['category_name'])): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-7 text-truncate" style="max-width: 220px;"><?= e($it['category_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        <?php if ($it['source_type'] === 'OWN_PRODUCTION'): ?>
                                            <div>QC Batch #<?= $it['qc_batch_id'] ?> · <?= e($it['program_number']) ?></div>
                                        <?php else: ?>
                                            <div><i class="bi bi-shop me-1 text-warning"></i><?= e($it['seller_name'] ?? 'Vendor') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                        <div class="d-flex gap-1">
                                            <span class="badge bg-secondary-subtle text-secondary border font-monospace">Size: <?= e($it['size_name']) ?></span>
                                            <span class="badge bg-info-subtle text-info border font-monospace">Color: <?= e($it['color_name']) ?></span>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold text-dark">₹<?= number_format($it['selling_price'], 2) ?></div>
                                            <?php if ($it['mrp'] > $it['selling_price']): ?>
                                                <small class="text-muted text-decoration-line-through">₹<?= number_format($it['mrp'], 2) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5">
                                            <i class="bi bi-patch-check me-1"></i><?= e($it['qc_status'] ?? 'QC PASSED') ?>
                                        </span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 ms-1">
                                            <i class="bi bi-building-check me-1"></i>AVAILABLE
                                        </span>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top-0 d-flex justify-content-end pb-3 pt-0">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">Actions</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button type="button" class="dropdown-item" onclick="printSinglePieceLabel(<?= htmlspecialchars(json_encode($it), ENT_QUOTES, 'UTF-8') ?>)"><i class="bi bi-printer me-2 text-primary"></i>Print QR Label</button></li>
                                            <li><a class="dropdown-item" href="<?= url('warehouse/stock/detail/' . $it['variant_id'] . '?warehouse_id=' . ($selectedWarehouseId ?? 1)) ?>"><i class="bi bi-clock-history me-2 text-info"></i>History</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- Pagination Controls Footer for Warehouse Pieces -->
        <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="whPiecePaginationFooter">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="small text-muted fw-medium" id="whPageEntriesInfo">
                    Showing 1 to 10 of <?= count($pieceItems ?? []) ?> pieces
                </span>
                <div class="d-flex align-items-center gap-2">
                    <label for="whPageSizeSelect" class="small text-muted mb-0 fw-medium">Show:</label>
                    <select id="whPageSizeSelect" class="form-select form-select-sm" style="width: auto;" onchange="changeWhPageSize(this.value)">
                        <option value="10" selected>10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                        <option value="all">All pieces (<?= count($pieceItems ?? []) ?>)</option>
                    </select>
                </div>
            </div>
            <nav aria-label="Warehouse Pieces Pagination">
                <ul class="pagination pagination-sm mb-0 shadow-xs" id="whPiecePaginationList">
                    <!-- Dynamically rendered page numbers -->
                </ul>
            </nav>
        </div>
    </div>

</div><!-- /#pieceStockViewSection -->

<!-- Single Piece QR Sticker Modal -->
<div class="modal fade" id="singlePieceQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-qr-code me-2 text-warning"></i>Piece Sticker Label</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="border rounded-3 p-3 bg-white shadow-xs d-inline-block text-start mb-3" style="max-width: 320px;" id="singlePiecePrintableArea">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="fw-bold font-monospace text-primary" id="lblPieceSku">SKU</span>
                        <span class="badge bg-dark text-white font-monospace" id="lblPieceTag">TAG</span>
                    </div>
                    <div class="d-flex gap-3 align-items-center">
                        <div id="lblPieceQrCodeBox" style="width: 80px; height: 80px;" class="d-flex align-items-center justify-content-center bg-light border"></div>
                        <div>
                            <div class="fw-bold small text-dark mb-1" id="lblPieceProduct">Product Name</div>
                            <div class="d-flex gap-1 mb-1">
                                <span class="badge bg-secondary-subtle text-secondary border font-monospace" id="lblPieceSize">Size</span>
                                <span class="badge bg-info-subtle text-info border font-monospace" id="lblPieceColor">Color</span>
                            </div>
                            <div class="small">MRP: <span class="text-decoration-line-through text-muted" id="lblPieceMrp">₹0</span></div>
                            <div class="fw-bold text-success fs-6" id="lblPiecePrice">₹0</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary px-4 fw-semibold" onclick="printSinglePieceSticker()"><i class="bi bi-printer me-1"></i> Print Label</button>
            </div>
        </div>
    </div>
</div>

<!-- Batch QR Sticker Print Modal -->
<div class="modal fade" id="whBatchQrPrintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3 no-print">
                <h5 class="modal-title fw-bold"><i class="bi bi-printer me-2 text-primary"></i>Batch QR Sticker Tags (Warehouse Pieces)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="bg-white p-3 rounded shadow-xs mb-3 border d-flex justify-content-between align-items-center flex-wrap gap-2 no-print">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-tag me-1 text-primary"></i>Ready to Print QR Stickers</h6>
                        <small class="text-muted" id="whPrintableCountBadge">0 Stickers Ready</small>
                    </div>
                    <button type="button" class="btn btn-success fw-bold px-4" onclick="window.print()">
                        <i class="bi bi-printer-fill me-1"></i> Print Stickers Now
                    </button>
                </div>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 bg-white p-3 rounded border" id="whBatchPrintSheet">
                    <!-- Dynamic QR cards -->
                </div>
            </div>
        </div>
    </div>
</div>

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

/* =========================================================================
   WAREHOUSE PIECE-BY-PIECE VIEW CONTROLLER & PAGINATION ENGINE
   ========================================================================= */
let currentWhView = '<?= $viewMode ?? 'ledger' ?>';
let currentWhPieceDisplay = 'list';
let currentWhPiecePage = 1;
let whPiecePageSize = 10;
let currentWhSourceFilter = 'all';
let matchingWhPieceElements = [];
const allWhPieceData = <?= json_encode($pieceItems ?? []) ?>;

function switchMainStockView(mode) {
    currentWhView = mode;
    const ledgerSec = document.getElementById('ledgerStockViewSection');
    const pieceSec = document.getElementById('pieceStockViewSection');
    const btnLedger = document.getElementById('btnShowLedgerView');
    const btnPiece = document.getElementById('btnShowPieceView');

    if (mode === 'pieces') {
        if (ledgerSec) ledgerSec.style.display = 'none';
        if (pieceSec) pieceSec.style.display = 'block';
        if (btnPiece) { btnPiece.classList.add('btn-primary', 'active'); btnPiece.classList.remove('btn-outline-primary', 'bg-white', 'text-dark'); }
        if (btnLedger) { btnLedger.classList.remove('btn-primary', 'active'); btnLedger.classList.add('btn-outline-primary', 'bg-white', 'text-dark'); }
        renderWhPagination();
    } else {
        if (pieceSec) pieceSec.style.display = 'none';
        if (ledgerSec) ledgerSec.style.display = 'block';
        if (btnLedger) { btnLedger.classList.add('btn-primary', 'active'); btnLedger.classList.remove('btn-outline-primary', 'bg-white', 'text-dark'); }
        if (btnPiece) { btnPiece.classList.remove('btn-primary', 'active'); btnPiece.classList.add('btn-outline-primary', 'bg-white', 'text-dark'); }
    }

    try {
        const url = new URL(window.location);
        url.searchParams.set('view', mode);
        window.history.replaceState({}, '', url);
    } catch(e) {}
}

function switchWhPieceDisplay(displayMode) {
    currentWhPieceDisplay = displayMode;
    const listContainer = document.getElementById('whPieceListContainer');
    const cardsContainer = document.getElementById('whPieceCardsContainer');
    const btnList = document.getElementById('btnWhDisplayList');
    const btnCard = document.getElementById('btnWhDisplayCard');

    if (displayMode === 'card') {
        if (listContainer) listContainer.style.display = 'none';
        if (cardsContainer) cardsContainer.style.display = 'flex';
        if (btnCard) { btnCard.classList.add('btn-primary', 'active'); btnCard.classList.remove('btn-outline-secondary'); }
        if (btnList) { btnList.classList.remove('btn-primary', 'active'); btnList.classList.add('btn-outline-secondary'); }
    } else {
        if (cardsContainer) cardsContainer.style.display = 'none';
        if (listContainer) listContainer.style.display = 'flex';
        if (btnList) { btnList.classList.add('btn-primary', 'active'); btnList.classList.remove('btn-outline-secondary'); }
        if (btnCard) { btnCard.classList.remove('btn-primary', 'active'); btnCard.classList.add('btn-outline-secondary'); }
    }
    renderWhPagination();
}

function filterWhPieceSource(sourceType, btn) {
    currentWhSourceFilter = sourceType;
    document.querySelectorAll('.wh-source-filter-btn').forEach(b => {
        b.classList.remove('active', 'btn-dark', 'btn-success', 'btn-warning', 'btn-danger', 'btn-info');
        const s = b.dataset.source;
        if (s === 'all') b.classList.add('btn-outline-dark');
        else if (s === 'OWN_PRODUCTION') b.classList.add('btn-outline-success');
        else if (s === 'EXTERNAL_PURCHASE') b.classList.add('btn-outline-warning', 'text-dark');
        else if (s === 'COMPLEMENTARY') b.classList.add('btn-outline-danger');
        else if (s === 'SERIES') b.classList.add('btn-outline-info', 'text-dark');
    });

    if (btn) {
        btn.classList.add('active');
        if (sourceType === 'all') btn.classList.replace('btn-outline-dark', 'btn-dark');
        else if (sourceType === 'OWN_PRODUCTION') btn.classList.replace('btn-outline-success', 'btn-success');
        else if (sourceType === 'EXTERNAL_PURCHASE') { btn.classList.remove('btn-outline-warning'); btn.classList.add('btn-warning', 'text-dark'); }
        else if (sourceType === 'COMPLEMENTARY') btn.classList.replace('btn-outline-danger', 'btn-danger');
        else if (sourceType === 'SERIES') { btn.classList.remove('btn-outline-info'); btn.classList.add('btn-info', 'text-white'); }
    }

    filterWhPieces();
}

function filterWhPieces() {
    const input = document.getElementById('whPieceSearchInput');
    const rawVal = input ? input.value.trim().toUpperCase() : '';
    const allRows = Array.from(document.querySelectorAll('.wh-piece-row'));
    const totalCount = allRows.length;

    matchingWhPieceElements = [];

    allRows.forEach(row => {
        row.classList.remove('inventory-search-highlight');
        const pieceNo = row.dataset.pieceNo;
        const cardCol = document.getElementById('whPieceCard_' + pieceNo);
        if (cardCol) cardCol.classList.remove('inventory-search-highlight');

        const tag = (row.dataset.tag || '').toUpperCase();
        const sku = (row.dataset.sku || '').toUpperCase();
        const prod = (row.dataset.product || '').toUpperCase();
        const size = (row.dataset.size || '').toUpperCase();
        const color = (row.dataset.color || '').toUpperCase();
        const src = row.dataset.source || '';
        const catType = row.dataset.catType || '';

        // Source filter match
        let sourceMatch = true;
        if (currentWhSourceFilter === 'OWN_PRODUCTION') sourceMatch = (src === 'OWN_PRODUCTION');
        else if (currentWhSourceFilter === 'EXTERNAL_PURCHASE') sourceMatch = (src === 'EXTERNAL_PURCHASE');
        else if (currentWhSourceFilter === 'COMPLEMENTARY') sourceMatch = (catType === 'COMPLEMENTARY');
        else if (currentWhSourceFilter === 'SERIES') sourceMatch = (catType === 'SERIES');

        // Text query match
        const textMatch = !rawVal || tag.includes(rawVal) || sku.includes(rawVal) || prod.includes(rawVal) || size === rawVal || color.includes(rawVal);

        if (sourceMatch && textMatch) {
            matchingWhPieceElements.push(row);
        } else {
            row.style.display = 'none';
            if (cardCol) cardCol.style.display = 'none';
        }
    });

    const badge = document.getElementById('whPieceSearchMatchBadge');
    if (badge) {
        if (rawVal || currentWhSourceFilter !== 'all') {
            badge.textContent = `Filtered: ${matchingWhPieceElements.length} of ${totalCount} pieces`;
            badge.className = 'badge bg-warning text-dark fw-bold fs-8';
        } else {
            badge.textContent = `Showing all ${totalCount} pieces`;
            badge.className = 'badge bg-white text-primary fw-bold fs-8';
        }
    }

    const filteredCountLbl = document.getElementById('whFilteredCountLabel');
    if (filteredCountLbl) {
        filteredCountLbl.textContent = matchingWhPieceElements.length;
    }

    currentWhPiecePage = 1;
    renderWhPagination();

    // Exact tag code match highlight
    if (rawVal) {
        const exactMatch = matchingWhPieceElements.find(row => (row.dataset.tag || '').toUpperCase() === rawVal);
        if (exactMatch) {
            exactMatch.classList.add('inventory-search-highlight');
            const exactCard = document.getElementById('whPieceCard_' + exactMatch.dataset.pieceNo);
            if (exactCard) exactCard.classList.add('inventory-search-highlight');
            exactMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}

function clearWhPieceSearch() {
    const input = document.getElementById('whPieceSearchInput');
    if (input) input.value = '';
    filterWhPieces();
}

function changeWhPageSize(newSize) {
    whPiecePageSize = (newSize === 'all') ? 'all' : parseInt(newSize, 10);
    currentWhPiecePage = 1;
    renderWhPagination();
}

function goToWhPage(pageNum) {
    currentWhPiecePage = pageNum;
    renderWhPagination();
    const container = currentWhPieceDisplay === 'card'
        ? document.getElementById('whPieceCardsContainer')
        : document.getElementById('whPieceListContainer');
    if (container) container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function renderWhPagination() {
    if (!matchingWhPieceElements || matchingWhPieceElements.length === 0) {
        matchingWhPieceElements = Array.from(document.querySelectorAll('.wh-piece-row'));
    }

    const totalMatching = matchingWhPieceElements.length;
    const isAll = (whPiecePageSize === 'all');
    const effectivePageSize = isAll ? totalMatching : parseInt(whPiecePageSize, 10);
    const totalPages = isAll ? 1 : Math.ceil(totalMatching / effectivePageSize) || 1;

    if (currentWhPiecePage > totalPages) currentWhPiecePage = totalPages;
    if (currentWhPiecePage < 1) currentWhPiecePage = 1;

    const startIndex = isAll ? 0 : (currentWhPiecePage - 1) * effectivePageSize;
    const endIndex = isAll ? totalMatching : Math.min(startIndex + effectivePageSize, totalMatching);

    // Toggle row/card visibility based on slice
    matchingWhPieceElements.forEach((row, idx) => {
        const visible = idx >= startIndex && idx < endIndex;
        const pieceNo = row.dataset.pieceNo;
        const cardCol = document.getElementById('whPieceCard_' + pieceNo);

        if (currentWhPieceDisplay === 'card') {
            row.style.display = 'none';
            if (cardCol) cardCol.style.display = visible ? '' : 'none';
        } else {
            row.style.display = visible ? 'flex' : 'none';
            if (cardCol) cardCol.style.display = 'none';
        }
    });

    // Empty search state
    const emptyState = document.getElementById('whEmptyPieceSearchState');
    if (emptyState) {
        emptyState.style.display = (totalMatching === 0) ? 'block' : 'none';
    }

    // Update Entries Counter
    const infoEl = document.getElementById('whPageEntriesInfo');
    if (infoEl) {
        if (totalMatching === 0) {
            infoEl.textContent = 'Showing 0 pieces';
        } else {
            infoEl.textContent = `Showing ${startIndex + 1} to ${endIndex} of ${totalMatching} pieces`;
        }
    }

    // Build pagination links
    const paginationList = document.getElementById('whPiecePaginationList');
    if (!paginationList) return;
    paginationList.innerHTML = '';

    if (totalPages <= 1) return;

    // Prev Button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentWhPiecePage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<button class="page-link" onclick="goToWhPage(${currentWhPiecePage - 1})"><i class="bi bi-chevron-left"></i></button>`;
    paginationList.appendChild(prevLi);

    // Determine pagination window (max 7 buttons)
    let startPage = Math.max(1, currentWhPiecePage - 2);
    let endPage = Math.min(totalPages, currentWhPiecePage + 2);
    if (startPage > 1) {
        const li1 = document.createElement('li');
        li1.className = 'page-item';
        li1.innerHTML = `<button class="page-link" onclick="goToWhPage(1)">1</button>`;
        paginationList.appendChild(li1);
        if (startPage > 2) {
            const ell = document.createElement('li');
            ell.className = 'page-item disabled';
            ell.innerHTML = `<span class="page-link">…</span>`;
            paginationList.appendChild(ell);
        }
    }

    for (let p = startPage; p <= endPage; p++) {
        const li = document.createElement('li');
        li.className = `page-item ${p === currentWhPiecePage ? 'active' : ''}`;
        li.innerHTML = `<button class="page-link" onclick="goToWhPage(${p})">${p}</button>`;
        paginationList.appendChild(li);
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ell = document.createElement('li');
            ell.className = 'page-item disabled';
            ell.innerHTML = `<span class="page-link">…</span>`;
            paginationList.appendChild(ell);
        }
        const liLast = document.createElement('li');
        liLast.className = 'page-item';
        liLast.innerHTML = `<button class="page-link" onclick="goToWhPage(${totalPages})">${totalPages}</button>`;
        paginationList.appendChild(liLast);
    }

    // Next Button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentWhPiecePage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<button class="page-link" onclick="goToWhPage(${currentWhPiecePage + 1})"><i class="bi bi-chevron-right"></i></button>`;
    paginationList.appendChild(nextLi);
}

// Single Piece QR Sticker Modal
let singlePieceQrInstance = null;
function printSinglePieceLabel(item) {
    if (!item) return;
    document.getElementById('lblPieceSku').innerText = item.sku || 'SKU';
    document.getElementById('lblPieceTag').innerText = item.tag_code || 'TAG';
    document.getElementById('lblPieceProduct').innerText = item.product_name || 'Product';
    document.getElementById('lblPieceSize').innerText = 'Size: ' + (item.size_name || 'Std');
    document.getElementById('lblPieceColor').innerText = 'Color: ' + (item.color_name || 'Std');
    document.getElementById('lblPieceMrp').innerText = '₹' + parseFloat(item.mrp || 0).toFixed(2);
    document.getElementById('lblPiecePrice').innerText = '₹' + parseFloat(item.selling_price || 0).toFixed(2);

    const box = document.getElementById('lblPieceQrCodeBox');
    box.innerHTML = '';
    if (typeof QRCode !== 'undefined') {
        new QRCode(box, {
            text: item.tag_code || item.sku || 'GRM-ITEM',
            width: 76,
            height: 76,
            colorDark: '#0f172a',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    }

    const modalEl = document.getElementById('singlePieceQrModal');
    if (!singlePieceQrInstance) {
        singlePieceQrInstance = new bootstrap.Modal(modalEl);
    }
    singlePieceQrInstance.show();
}

function printSinglePieceSticker() {
    const printContents = document.getElementById('singlePiecePrintableArea').innerHTML;
    const printWindow = window.open('', '_blank', 'width=500,height=500');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Piece QR Sticker Label</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; font-family: system-ui, sans-serif; }
                @media print { body { padding: 0; } }
            </style>
        </head>
        <body onload="window.print(); window.close();">
            <div style="border: 2px dashed #000; padding: 15px; width: 280px; margin: 0 auto; text-align: left;">
                ${printContents}
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// Batch QR Stickers Print Modal
let whBatchPrintModalInstance = null;
function openWhBatchPrintModal() {
    const sheet = document.getElementById('whBatchPrintSheet');
    if (!sheet) return;
    sheet.innerHTML = '';

    const matchingRows = matchingWhPieceElements && matchingWhPieceElements.length > 0 
        ? matchingWhPieceElements 
        : Array.from(document.querySelectorAll('.wh-piece-row'));

    const countBadge = document.getElementById('whPrintableCountBadge');
    if (countBadge) countBadge.innerText = `${matchingRows.length} QR Stickers Ready`;

    const modalEl = document.getElementById('whBatchQrPrintModal');
    if (!whBatchPrintModalInstance) {
        whBatchPrintModalInstance = new bootstrap.Modal(modalEl);
    }
    whBatchPrintModalInstance.show();

    // Render QR cards
    matchingRows.forEach((row, idx) => {
        const pieceNo = row.dataset.pieceNo;
        const tag = row.dataset.tag;
        const sku = row.dataset.sku;
        const prod = row.dataset.product;
        const size = row.dataset.size;
        const color = row.dataset.color;

        const card = document.createElement('div');
        card.className = 'col';
        card.innerHTML = `
            <div class="border rounded p-2 text-center bg-white shadow-xs" style="font-size: 11px;">
                <div class="fw-bold text-truncate text-dark mb-1">${prod}</div>
                <div id="batchQrCode_${pieceNo}" class="d-flex justify-content-center my-1" style="min-height: 80px;"></div>
                <div class="font-monospace fw-bold fs-7 text-dark mb-0.5">${tag}</div>
                <div class="text-muted font-monospace">${sku}</div>
                <div class="badge bg-light text-dark border font-monospace mt-1">${size} / ${color}</div>
            </div>
        `;
        sheet.appendChild(card);

        setTimeout(() => {
            const qrBox = document.getElementById('batchQrCode_' + pieceNo);
            if (qrBox && typeof QRCode !== 'undefined') {
                new QRCode(qrBox, {
                    text: tag,
                    width: 76,
                    height: 76,
                    colorDark: '#0f172a',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            }
        }, 10 * idx);
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    if (currentWhView === 'pieces') {
        switchMainStockView('pieces');
    } else {
        renderWhPagination();
    }
});
</script>

<!-- QRCode JS CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
.inventory-piece-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.inventory-piece-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0,0,0,0.08) !important;
}
.inventory-piece-list-item {
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem !important;
    background: #fff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
}
.inventory-piece-list-item:hover {
    border-color: #93c5fd;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.06);
}
.inventory-piece-list-item.inventory-search-highlight,
.inventory-piece-card.inventory-search-highlight {
    outline: 3px solid #f59e0b;
    outline-offset: -2px;
    background-color: #fffbeb !important;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.16) !important;
}
.price-checkbox-col input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
tr.table-success td {
    background-color: rgba(25, 135, 84, 0.08) !important;
}
@media print {
    .no-print, .modal-header, .page-header, .sidebar, nav, header {
        display: none !important;
    }
    #whBatchPrintSheet {
        display: flex !important;
        flex-wrap: wrap !important;
        border: none !important;
        padding: 0 !important;
    }
    #whBatchPrintSheet .col {
        width: 33.33% !important;
        page-break-inside: avoid;
        margin-bottom: 12px;
    }
}
</style>
