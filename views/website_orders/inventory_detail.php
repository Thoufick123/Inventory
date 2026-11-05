<?php
// views/website_orders/inventory_detail.php — Full Page Product & Stock Ledger Detail for Website Channel
$imgUrl = !empty($item['variant_image']) ? $item['variant_image'] : (!empty($item['product_image']) ? $item['product_image'] : '');
$availQty = (float)($item['available_quantity'] ?? 0);
$damagedQty = (float)($item['damaged_quantity'] ?? 0);
$minStock = (int)($item['min_stock'] ?? 5);
$isLow = ($availQty > 0 && $availQty <= $minStock);
$mrp = (float)($item['mrp'] ?? 0);
$sellingPrice = (float)($item['selling_price'] ?? 0);
$cost = (float)($item['production_cost'] > 0 ? $item['production_cost'] : ($item['purchase_price'] ?? 0));
$discount = ($mrp > $sellingPrice && $mrp > 0) ? round((($mrp - $sellingPrice) / $mrp) * 100) : 0;
$canAdjustPrice = hasPermission('website_inventory', 'adjust');
$canReportDamage = hasPermission('website_damage', 'create');
$canViewDamage = hasPermission('website_damage', 'view');
$canViewInward = hasPermission('website_inward', 'view');
$canViewOrders = hasPermission('website_orders', 'view');
$canViewReturns = hasPermission('website_returns', 'view');
$canPrintReturn = hasPermission('website_returns', 'print');
$canViewReturnToWarehouse = hasPermission('website_return_to_wh', 'view');

$returnsHistory = $returnsHistory ?? [];
$exchangesHistory = $exchangesHistory ?? [];
$warehouseReturnsHistory = $warehouseReturnsHistory ?? [];

$pieceTags = $pieceTags ?? [];
$isPackType = !empty($item['product_is_complementary']) || !empty($item['is_complementary'])
    || in_array(strtoupper((string)($item['warehouse_category_type'] ?? '')), ['SERIES', 'COMPLEMENTARY'], true)
    || strtoupper((string)($item['qr_type'] ?? '')) === 'SERIES_BATCH'
    || (int)($item['pack_quantity'] ?? 1) > 1;
$tagStats = [
    'total'      => count($pieceTags),
    'available'  => count(array_filter($pieceTags, fn($p) => ($p['status'] ?? '') === 'AVAILABLE')),
    'damaged'    => count(array_filter($pieceTags, fn($p) => ($p['status'] ?? '') === 'DAMAGED')),
    'sold'       => count(array_filter($pieceTags, fn($p) => ($p['status'] ?? '') === 'SOLD')),
    'in_transit' => count(array_filter($pieceTags, fn($p) => ($p['status'] ?? '') === 'IN_TRANSIT')),
    'returned'   => count(array_filter($pieceTags, fn($p) => ($p['status'] ?? '') === 'RETURNED')),
];
if ($isPackType) {
    // A shared Series/Complementary QR represents the live pack balance. As
    // long as any units remain available, do not label that shared QR SOLD.
    $packSold = $availQty > 0 ? 0 : max(0, (int)($metrics['net_sold_qty'] ?? 0));
    $tagStats['available'] = (int)$availQty;
    $tagStats['damaged'] = (int)$damagedQty;
    $tagStats['sold'] = $packSold;
    $tagStats['total'] = (int)$availQty + (int)$damagedQty + $packSold;
}

$firstTag = !empty($pieceTags) ? ($pieceTags[0]['tag_code'] ?? '') : '';
$lastTag  = !empty($pieceTags) ? ($pieceTags[count($pieceTags) - 1]['tag_code'] ?? '') : '';
$tagRangeStr = ($firstTag && $lastTag) ? ($firstTag === $lastTag ? $firstTag : "{$firstTag} → {$lastTag}") : 'N/A';
usort($pieceTags, static function ($a, $b) {
    $priority = ['AVAILABLE' => 0, 'DAMAGED' => 1, 'IN_TRANSIT' => 2, 'SOLD' => 3, 'RETURNED' => 4];
    return ($priority[strtoupper($a['status'] ?? '')] ?? 5) <=> ($priority[strtoupper($b['status'] ?? '')] ?? 5);
});
?>

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
/* Sold Piece Disabled UI Styling */
.piece-item-card.is-sold .card {
    background: #f8fafc !important;
    border: 1.5px dashed #cbd5e1 !important;
    box-shadow: none !important;
    opacity: 0.72;
    transition: all 0.2s ease-in-out;
}
.piece-item-card.is-sold .card:hover {
    opacity: 0.95 !important;
    border-color: #94a3b8 !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06) !important;
}
.piece-item-card.is-sold .card-header {
    background: #f1f5f9 !important;
    border-bottom: 1px dashed #cbd5e1 !important;
}
.piece-item-card.is-sold .piece-qr-canvas {
    filter: grayscale(100%) opacity(40%);
}
.piece-item-card.is-sold .sold-tag-box {
    background: #e2e8f0 !important;
    color: #64748b !important;
    border-color: #cbd5e1 !important;
    text-decoration: line-through;
}
.piece-item-card.is-sold .sold-watermark-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-15deg);
    background: rgba(15, 23, 42, 0.82);
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1px;
    padding: 2px 8px;
    border-radius: 4px;
    pointer-events: none;
    z-index: 5;
    text-transform: uppercase;
    box-shadow: 0 2px 4px rgba(0,0,0,0.12);
}

/* Sold Table Row Disabled Styling */
.piece-table-row.is-sold {
    background-color: #f8fafc !important;
    color: #94a3b8 !important;
    opacity: 0.72;
}
.piece-table-row.is-sold .piece-table-qr-canvas {
    filter: grayscale(100%) opacity(40%);
}
.piece-table-row.is-sold .sold-tag-code {
    text-decoration: line-through;
    color: #64748b !important;
    background: #f1f5f9 !important;
    border-color: #cbd5e1 !important;
}
.piece-item-card.is-sold,
.piece-table-row.is-sold { display: none !important; }
</style>

<div class="container-fluid py-4">
    <!-- Header & Quick Actions -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-1 rounded-pill fw-semibold">
                    <i class="bi bi-globe me-1"></i><?= htmlspecialchars($channel['name'] ?? 'Website Channel') ?>
                </span>
                <span class="badge bg-dark text-white font-monospace rounded-pill px-3 py-1">
                    <?= htmlspecialchars($item['sku']) ?>
                </span>
                <?php if (!empty($pieceTags)): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                        <i class="bi bi-qr-code me-1"></i><?= $tagStats['available'] ?> Available In Stock <span class="text-muted fw-normal">/ <?= $tagStats['total'] ?> Total</span>
                    </span>
                <?php endif; ?>
            </div>
            <h2 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($item['product_name']) ?></h2>
            <p class="text-muted small mb-0 font-monospace">Product Code: <?= htmlspecialchars($item['product_code'] ?? 'N/A') ?> | Tag Range: <span class="fw-semibold text-primary"><?= htmlspecialchars($tagRangeStr) ?></span></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= url('warehouse/website-orders/allocations') ?>" class="btn btn-outline-secondary rounded-pill px-3 fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Back to Inventory
            </a>
        </div>
    </div>

    <!-- Product Hero & Metrics Row -->
    <div class="row g-4 mb-4">
        <!-- Commercials & Stock Balances Summary -->
        <div class="col-12">
            <div class="row g-3 mb-4">
                <!-- 1. Selling Price -->
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-success border-4 position-relative">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted fw-semibold d-block text-uppercase" style="font-size: 11px;">Selling Price</small>
                            <?php if ($canAdjustPrice): ?>
                            <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" 
                                    onclick="openEditPriceModal(<?= (int)$item['variant_id'] ?>, <?= htmlspecialchars(json_encode($item['sku']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($item['product_name']), ENT_QUOTES, 'UTF-8') ?>, <?= (float)$sellingPrice ?>, <?= (float)$mrp ?>, <?= (float)$cost ?>)" 
                                    title="Quick Edit Selling Price & MRP">
                                <i class="bi bi-pencil-square fs-6 text-success"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <h3 class="fw-bold text-success mb-0 mt-1" id="detailPageSellingPrice">₹<?= number_format($sellingPrice, 2) ?></h3>
                        <small class="text-muted text-decoration-line-through <?= $mrp > $sellingPrice ? '' : 'd-none' ?>" id="detailPageMrp">MRP: ₹<?= number_format($mrp, 2) ?></small>
                        <small class="text-muted <?= $mrp <= $sellingPrice ? '' : 'd-none' ?>" id="detailPageStandardMrp">Standard Retail</small>
                    </div>
                </div>

                <!-- 2. Available Website Stock -->
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-primary border-4">
                        <small class="text-muted fw-semibold d-block text-uppercase" style="font-size: 11px;">Available Stock</small>
                        <h3 class="fw-bold <?= $availQty <= 0 ? 'text-dark' : ($isLow ? 'text-warning' : 'text-primary') ?> mb-0 mt-1">
                            <?= number_format($availQty) ?> <small class="fs-6 fw-normal text-muted">pcs</small>
                        </h3>
                        <?php if ($availQty <= 0): ?>
                            <span class="badge bg-dark rounded-pill mt-1">Out of Stock</span>
                        <?php elseif ($isLow): ?>
                            <span class="badge bg-warning text-dark rounded-pill mt-1">Low Stock (≤<?= $minStock ?>)</span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill mt-1">Healthy Stock</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Damaged Quarantined -->
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-danger border-4">
                        <small class="text-muted fw-semibold d-block text-uppercase" style="font-size: 11px;">Damaged Stock</small>
                        <h3 class="fw-bold text-danger mb-0 mt-1">
                            <?= number_format($damagedQty) ?> <small class="fs-6 fw-normal text-muted">pcs</small>
                        </h3>
                        <small class="text-danger d-block mt-1" style="font-size: 11px;">
                            <i class="bi bi-shield-x me-1"></i>Quarantined from sales
                        </small>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Lifecycle History Tabs (5 Detailed Sections) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom pt-3 pb-0 px-4">
            <ul class="nav nav-tabs card-header-tabs" id="productHistoryTabs" role="tablist">
                <!-- 1. Tracked Piece QR & Tag Codes -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold text-primary border-primary border-bottom-0" id="tab-piecetags-tab" data-bs-toggle="tab" data-bs-target="#tab-piecetags" type="button" role="tab">
                        <i class="bi bi-qr-code-scan me-1 text-primary"></i> Piece QR &amp; Tag Codes (<?= $tagStats['available'] ?> In Stock / <?= $tagStats['total'] ?> Total)
                    </button>
                </li>
                <!-- 2. Stock Ledger Transactions -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-muted" id="tab-ledger-tab" data-bs-toggle="tab" data-bs-target="#tab-ledger" type="button" role="tab">
                        <i class="bi bi-clock-history me-1"></i> Stock Ledger (<?= count($transactions) ?>)
                    </button>
                </li>
                <!-- 3. Warehouse Inward Allocations -->
                <?php if ($canViewInward): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-muted" id="tab-inward-tab" data-bs-toggle="tab" data-bs-target="#tab-inward" type="button" role="tab">
                        <i class="bi bi-truck me-1"></i> Warehouse Inwards (<?= count($inwardHistory) ?>)
                    </button>
                </li>
                <?php endif; ?>
                <!-- 4. Website Sales Orders -->
                <?php if ($canViewOrders): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-muted" id="tab-sales-tab" data-bs-toggle="tab" data-bs-target="#tab-sales" type="button" role="tab">
                        <i class="bi bi-receipt me-1"></i> Website Sales (<?= count($salesHistory) ?>)
                    </button>
                </li>
                <?php endif; ?>
                <!-- 5. Returns & Exchanges -->
                <?php if ($canViewReturns): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-muted" id="tab-returns-tab" data-bs-toggle="tab" data-bs-target="#tab-returns" type="button" role="tab">
                        <i class="bi bi-arrow-left-right me-1 text-info"></i> Returns &amp; Exchanges (<?= count($returnsHistory ?? []) + count($exchangesHistory ?? []) ?>)
                    </button>
                </li>
                <?php endif; ?>
                <!-- 6. Damage & Quarantine Log -->
                <?php if ($canViewDamage): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-muted" id="tab-damage-tab" data-bs-toggle="tab" data-bs-target="#tab-damage" type="button" role="tab">
                        <i class="bi bi-shield-x me-1"></i> Damage &amp; Quarantine (<?= count($damageHistory) ?>)
                    </button>
                </li>
                <?php endif; ?>
                <?php if ($canViewReturnToWarehouse && !empty($warehouseReturnsHistory)): ?>
                <!-- 7. Warehouse Outward Returns -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-muted" id="tab-wh-returns-tab" data-bs-toggle="tab" data-bs-target="#tab-wh-returns" type="button" role="tab">
                        <i class="bi bi-box-arrow-up-right me-1 text-secondary"></i> Outward to Warehouse (<?= count($warehouseReturnsHistory) ?>)
                    </button>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="productHistoryTabsContent">
                
                <!-- ========================================================================= -->
                <!-- TAB 1: TRACKED PIECE QR & SERIAL TAG CODES -->
                <!-- ========================================================================= -->
                <div class="tab-pane fade show active" id="tab-piecetags" role="tabpanel">
                    <!-- Top Summary & Filter Controls -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 pb-3 border-bottom">
                        <div>
                            <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-upc-scan me-2 text-primary"></i>Tracked Piece QR Codes &amp; Serial Tag Register</h5>
                            <p class="text-muted small mb-0">Every individual garment unit tracked with unique QR payload, serial tag code, and live channel status.</p>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary active" id="btnViewCards" onclick="switchPieceView('cards')">
                                <i class="bi bi-grid-fill me-1"></i> Cards
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnViewTable" onclick="switchPieceView('table')">
                                <i class="bi bi-table me-1"></i> Table
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="printAllPieceTags()">
                                <i class="bi bi-printer me-1"></i> Print All Labels
                            </button>
                        </div>
                    </div>

                    <!-- Piece KPI Filter Pills -->
                    <div id="pieceKpiFilters" class="row g-2 mb-4 d-none">
                        <div class="col-6 col-md-auto">
                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold piece-filter-btn" data-filter="AVAILABLE" onclick="filterPieceTags('AVAILABLE', this)">
                                Available in Stock <span class="badge bg-success rounded-pill ms-1"><?= $tagStats['available'] ?></span>
                            </button>
                        </div>
                        <div class="col-6 col-md-auto">
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill w-100 fw-semibold piece-filter-btn" data-filter="DAMAGED" onclick="filterPieceTags('DAMAGED', this)">
                                Damaged / Quarantined <span class="badge bg-danger rounded-pill ms-1"><?= $tagStats['damaged'] ?></span>
                            </button>
                        </div>
                        <?php if ($tagStats['returned'] > 0): ?>
                            <div class="col-6 col-md-auto">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill w-100 fw-semibold piece-filter-btn" data-filter="RETURNED" onclick="filterPieceTags('RETURNED', this)">
                                    Returned to WH <span class="badge bg-secondary text-white rounded-pill ms-1"><?= $tagStats['returned'] ?></span>
                                </button>
                            </div>
                        <?php endif; ?>
                        <?php if ($tagStats['in_transit'] > 0): ?>
                            <div class="col-6 col-md-auto">
                                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill w-100 fw-semibold piece-filter-btn" data-filter="IN_TRANSIT" onclick="filterPieceTags('IN_TRANSIT', this)">
                                    In-Transit <span class="badge bg-warning text-dark rounded-pill ms-1"><?= $tagStats['in_transit'] ?></span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Piece Quick Search -->
                    <div class="mb-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="pieceTagSearchInput" class="form-control border-start-0" placeholder="Filter by Tag Code (e.g. BSD092611), QR payload, or allocation number..." onkeyup="searchPieceTags()">
                            <button class="btn btn-outline-secondary" type="button" onclick="clearPieceSearch()">Clear</button>
                        </div>
                    </div>

                    <?php if (empty($pieceTags)): ?>
                        <div class="text-center py-5 bg-light rounded-4 border">
                            <i class="bi bi-qr-code text-muted opacity-50" style="font-size: 3.5rem;"></i>
                            <h6 class="fw-bold text-dark mt-3">No Piece QR Tags Tracked</h6>
                            <p class="text-muted small">No inward allocations or serial piece tags recorded yet for this product variant.</p>
                        </div>
                    <?php else: ?>

                        <!-- VIEW 1: PIECE CARDS GRID -->
                        <div id="pieceCardsContainer" class="row g-3">
                            <?php foreach ($pieceTags as $pt): 
                                $st = strtoupper($pt['status'] ?? 'AVAILABLE');
                                $isSold = ($st === 'SOLD');
                                $isExchanged = !empty($pt['meta']['is_exchange_dispatched']);
                                $isRestocked = !empty($pt['meta']['is_restocked']);
                                $isWhReturned = ($st === 'RETURNED' || !empty($pt['meta']['is_warehouse_returned']));

                                $cardBorder = match($st) {
                                    'AVAILABLE'  => 'border-success',
                                    'DAMAGED'    => 'border-danger',
                                    'SOLD'       => $isExchanged ? 'border-primary' : 'border-secondary-subtle',
                                    'RETURNED'   => 'border-info-subtle',
                                    'IN_TRANSIT' => 'border-warning',
                                    default      => 'border-secondary'
                                };
                                $stBadge = match($st) {
                                    'AVAILABLE'  => $isRestocked 
                                                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><i class="bi bi-check-circle me-1"></i>AVAILABLE (RESTOCKED)</span>' 
                                                    : '<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><i class="bi bi-check-circle me-1"></i>AVAILABLE IN STOCK</span>',
                                    'DAMAGED'    => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold"><i class="bi bi-shield-x me-1"></i>DAMAGED (QUARANTINED)</span>',
                                    'SOLD'       => $isExchanged 
                                                    ? '<span class="badge bg-primary text-white fw-bold shadow-sm"><i class="bi bi-arrow-left-right me-1"></i>EXCHANGED / DISPATCHED</span>' 
                                                    : '<span class="badge bg-secondary text-white fw-bold shadow-sm"><i class="bi bi-cart-x-fill me-1"></i>SOLD / FULFILLED</span>',
                                    'RETURNED'   => '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fw-bold"><i class="bi bi-box-arrow-up-right me-1"></i>RETURNED TO WH</span>',
                                    'IN_TRANSIT' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold"><i class="bi bi-truck me-1"></i>IN-TRANSIT</span>',
                                    default      => '<span class="badge bg-light text-dark border">' . htmlspecialchars($st) . '</span>'
                                };
                            ?>
                                <div class="col-md-6 col-xl-4 piece-item-card <?= ($isSold && !$isExchanged) ? 'is-sold' : '' ?>" data-status="<?= htmlspecialchars($st) ?>" data-tag="<?= htmlspecialchars(strtolower($pt['tag_code'])) ?>" data-search="<?= htmlspecialchars(strtolower($pt['tag_code'] . ' ' . $pt['qr_payload'] . ' ' . $pt['allocation_number'])) ?>">
                                    <div class="card h-100 rounded-4 shadow-sm border <?= $cardBorder ?> bg-white overflow-hidden position-relative">
                                        <div class="card-header bg-light bg-opacity-50 d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                                            <span class="badge <?= ($isSold && !$isExchanged) ? 'bg-secondary' : ($isExchanged ? 'bg-primary' : 'bg-dark') ?> text-white font-monospace">Piece #<?= $pt['piece_no'] ?></span>
                                            <?= $stBadge ?>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="row g-2 align-items-center">
                                                <!-- Dynamic QR Canvas for this Piece -->
                                                <div class="col-4 text-center position-relative">
                                                    <div class="p-2 bg-white border rounded-3 shadow-sm d-inline-block position-relative">
                                                        <div id="pieceQr_<?= $pt['piece_no'] ?>" class="piece-qr-canvas" data-payload="<?= htmlspecialchars($pt['qr_payload']) ?>"></div>
                                                        <?php if ($isSold && !$isExchanged): ?>
                                                             <span class="sold-watermark-overlay">SOLD</span>
                                                        <?php elseif ($isExchanged): ?>
                                                             <span class="sold-watermark-overlay" style="background: rgba(124, 58, 237, 0.85);">EXCHANGED</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="d-block text-muted mt-1" style="font-size: 10px;">Piece QR</small>
                                                </div>
                                                <!-- Piece Info -->
                                                <div class="col-8">
                                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                                        <span class="text-muted small fw-semibold">Serial Tag:</span>
                                                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1" title="Copy Tag Code" onclick="copyTagCode('<?= htmlspecialchars($pt['tag_code']) ?>')">
                                                            <i class="bi bi-clipboard"></i> Copy
                                                        </button>
                                                    </div>
                                                    <div class="fw-bold font-monospace <?= ($isSold && !$isExchanged) ? 'sold-tag-box' : 'text-primary bg-light' ?> fs-6 mb-1 p-1 rounded border text-center">
                                                        🏷️ <?= htmlspecialchars($pt['tag_code']) ?>
                                                    </div>
                                                    <div class="small mb-1">
                                                        <span class="text-muted">Base price:</span>
                                                        <strong class="text-dark">₹<?= number_format((float)($pt['mrp'] ?? 0), 2) ?></strong>
                                                    </div>
                                                    <div class="small mb-1">
                                                        <span class="text-muted">Discount price:</span>
                                                        <strong class="text-success">₹<?= number_format((float)($pt['disc'] ?? 0), 2) ?></strong>
                                                    </div>
                                                    <div class="small text-muted" style="font-size: 11px;">
                                                        <i class="bi bi-box-seam me-1"></i>Batch: <span class="fw-semibold text-dark"><?= htmlspecialchars($pt['allocation_number']) ?></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status Specific Metadata & Actions -->
                                            <div class="mt-3 pt-2 border-top">
                                                <?php if ($st === 'AVAILABLE'): ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <?php if ($isRestocked): ?>
                                                            <small class="text-success fw-semibold"><i class="bi bi-arrow-repeat me-1"></i>Restocked (<?= htmlspecialchars($pt['meta']['exchange_number'] ?? $pt['meta']['return_number'] ?? 'Return') ?>)</small>
                                                        <?php else: ?>
                                                            <small class="text-success fw-semibold"><i class="bi bi-check2-all me-1"></i>Ready for Website Orders</small>
                                                        <?php endif; ?>
                                                        <div class="d-flex gap-1">
                                                            <?php if ($canReportDamage): ?>
                                                            <a href="<?= url('warehouse/website-orders/damage/create?variant_id=' . $item['variant_id'] . '&tag_code=' . urlencode($pt['tag_code']) . '&allocation_id=' . ($pt['allocation_id'] ?? '')) ?>" class="btn btn-xs btn-outline-danger px-2 py-1" title="Quarantine as Damaged">
                                                                <i class="bi bi-shield-x"></i> Report Damage
                                                            </a>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-xs btn-outline-primary px-2 py-1" title="Print Tag Sticker" onclick="printSinglePieceTag(<?= htmlspecialchars(json_encode($pt)) ?>)">
                                                                <i class="bi bi-printer"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php elseif ($st === 'DAMAGED'): ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="small text-danger text-truncate" style="max-width: 65%;">
                                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                            <?= htmlspecialchars($pt['meta']['damage_reason'] ?? $pt['meta']['defect_type'] ?? 'Defect Quarantined') ?>
                                                        </div>
                                                        <?php if ($canViewDamage && !empty($pt['meta']['damage_id'])): ?>
                                                            <a href="<?= url('warehouse/website-orders/damage/view/' . $pt['meta']['damage_id']) ?>" class="btn btn-xs btn-outline-danger px-2 py-1">
                                                                <i class="bi bi-eye"></i> View Damage
                                                            </a>
                                                        <?php elseif ($canViewDamage && !empty($pt['meta']['outward_id'])): ?>
                                                            <a href="<?= url('warehouse/website-orders/damage/outward/view/' . $pt['meta']['outward_id']) ?>" class="btn btn-xs btn-outline-danger px-2 py-1">
                                                                <i class="bi bi-truck"></i> Return Batch
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger-subtle text-danger border">Quarantined</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($st === 'SOLD'): ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <?php if ($isExchanged): ?>
                                                            <small class="text-primary fw-semibold text-truncate" style="max-width: 70%;">
                                                                <i class="bi bi-arrow-left-right me-1"></i><?= htmlspecialchars($pt['meta']['invoice_number'] ?? 'Exchange Order') ?>
                                                            </small>
                                                            <span class="badge bg-primary text-white">Dispatched</span>
                                                        <?php else: ?>
                                                            <small class="text-muted fw-semibold text-truncate" style="max-width: 70%;">
                                                                <i class="bi bi-receipt text-secondary me-1"></i><?= htmlspecialchars($pt['meta']['invoice_number'] ?? 'Website Order') ?>
                                                            </small>
                                                            <span class="badge bg-secondary text-white">Fulfilled</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($st === 'RETURNED'): ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-info fw-semibold text-truncate" style="max-width: 70%;">
                                                            <i class="bi bi-box-arrow-up-right me-1"></i><?= htmlspecialchars($pt['meta']['return_number'] ?? 'Warehouse Return') ?>
                                                        </small>
                                                        <span class="badge bg-info text-dark">Returned</span>
                                                    </div>
                                                <?php elseif ($st === 'IN_TRANSIT'): ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-warning-emphasis"><i class="bi bi-hourglass-split me-1"></i>Awaiting Inward Confirmation</small>
                                                        <?php if ($canViewInward && !empty($pt['allocation_id'])): ?>
                                                            <a href="<?= url('warehouse/website-orders/inward/view/' . $pt['allocation_id']) ?>" class="btn btn-xs btn-outline-warning px-2 py-1">
                                                                Receive
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- VIEW 2: PIECE TABLE VIEW (HIDDEN BY DEFAULT) -->
                        <div id="pieceTableContainer" class="table-responsive d-none">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Piece QR Code</th>
                                        <th>Piece Serial Tag Code</th>
                                        <th>QR Payload String</th>
                                        <th>Inward Allocation</th>
                                        <th class="text-center">Live Status</th>
                                        <th>Tracking Meta</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pieceTags as $pt): 
                                        $st = strtoupper($pt['status'] ?? 'AVAILABLE');
                                        $isSold = ($st === 'SOLD');
                                        $isExchanged = !empty($pt['meta']['is_exchange_dispatched']);
                                        $isRestocked = !empty($pt['meta']['is_restocked']);
                                        $isWhReturned = ($st === 'RETURNED' || !empty($pt['meta']['is_warehouse_returned']));

                                        $stBadge = match($st) {
                                            'AVAILABLE'  => $isRestocked 
                                                            ? '<span class="badge bg-success text-white">RESTOCKED</span>' 
                                                            : '<span class="badge bg-success text-white">AVAILABLE</span>',
                                            'DAMAGED'    => '<span class="badge bg-danger text-white">DAMAGED</span>',
                                            'SOLD'       => $isExchanged 
                                                            ? '<span class="badge bg-primary text-white">EXCHANGED</span>' 
                                                            : '<span class="badge bg-secondary text-white">SOLD OUT</span>',
                                            'RETURNED'   => '<span class="badge bg-info text-dark">RETURNED TO WH</span>',
                                            'IN_TRANSIT' => '<span class="badge bg-warning text-dark">IN-TRANSIT</span>',
                                            default      => '<span class="badge bg-secondary">' . htmlspecialchars($st) . '</span>'
                                        };
                                    ?>
                                        <tr class="piece-table-row <?= ($isSold && !$isExchanged) ? 'is-sold' : '' ?>" data-status="<?= htmlspecialchars($st) ?>" data-tag="<?= htmlspecialchars(strtolower($pt['tag_code'])) ?>" data-search="<?= htmlspecialchars(strtolower($pt['tag_code'] . ' ' . $pt['qr_payload'] . ' ' . $pt['allocation_number'])) ?>">
                                            <td class="text-muted small fw-semibold"><?= $pt['piece_no'] ?></td>
                                            <td>
                                                <div id="pieceTableQr_<?= $pt['piece_no'] ?>" class="piece-table-qr-canvas" data-payload="<?= htmlspecialchars($pt['qr_payload']) ?>"></div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge <?= ($isSold && !$isExchanged) ? 'sold-tag-code' : 'bg-light text-primary' ?> font-monospace fs-6 border"><?= htmlspecialchars($pt['tag_code']) ?></span>
                                                    <button type="button" class="btn btn-xs btn-link text-muted p-0" title="Copy Tag" onclick="copyTagCode('<?= htmlspecialchars($pt['tag_code']) ?>')">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="small font-monospace text-muted text-truncate" style="max-width: 200px;">
                                                <?= htmlspecialchars($pt['qr_payload']) ?>
                                            </td>
                                            <td class="small">
                                                <div class="fw-semibold <?= ($isSold && !$isExchanged) ? 'text-muted' : 'text-dark' ?>"><?= htmlspecialchars($pt['allocation_number']) ?></div>
                                                <small class="text-muted"><?= date('d M Y', strtotime($pt['inward_date'])) ?></small>
                                            </td>
                                            <td class="text-center">
                                                <?= $stBadge ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?php if ($isRestocked): ?>
                                                    <span class="text-success fw-bold"><i class="bi bi-arrow-repeat me-1"></i>Restocked via <?= htmlspecialchars($pt['meta']['exchange_number'] ?? $pt['meta']['return_number'] ?? 'Return') ?></span>
                                                <?php elseif ($isExchanged): ?>
                                                    <span class="text-primary fw-semibold"><i class="bi bi-arrow-left-right me-1"></i>Dispatched in Exchange <?= htmlspecialchars($pt['meta']['invoice_number'] ?? '') ?></span>
                                                <?php elseif ($st === 'DAMAGED'): ?>
                                                    <span class="text-danger"><i class="bi bi-shield-x me-1"></i><?= htmlspecialchars($pt['meta']['damage_reason'] ?? 'Defect Quarantined') ?></span>
                                                <?php elseif ($st === 'SOLD'): ?>
                                                    <span class="text-muted"><i class="bi bi-receipt me-1"></i><?= htmlspecialchars($pt['meta']['invoice_number'] ?? 'Website Order') ?> (Billed)</span>
                                                <?php elseif ($st === 'RETURNED'): ?>
                                                    <span class="text-info"><i class="bi bi-box-arrow-up-right me-1"></i><?= htmlspecialchars($pt['meta']['return_number'] ?? 'Warehouse Return') ?></span>
                                                <?php elseif ($st === 'AVAILABLE'): ?>
                                                    <span class="text-success"><i class="bi bi-check2 me-1"></i>In Channel Stock</span>
                                                <?php else: ?>
                                                    <span><?= htmlspecialchars($pt['allocation_number']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex gap-1 justify-content-end">
                                                    <button type="button" class="btn btn-xs btn-outline-primary px-2 py-1" title="Print Tag Label" onclick="printSinglePieceTag(<?= htmlspecialchars(json_encode($pt)) ?>)">
                                                        <i class="bi bi-printer"></i>
                                                    </button>
                                                    <?php if ($canPrintReturn && $isRestocked && !empty($pt['meta']['return_id'])): ?>
                                                        <a href="<?= url('warehouse/website-orders/returns/invoice/' . $pt['meta']['return_id']) ?>" class="btn btn-xs btn-outline-success px-2 py-1" title="View Return Slip">
                                                            <i class="bi bi-receipt"></i>
                                                        </a>
                                                    <?php elseif ($canPrintReturn && $isExchanged && !empty($pt['meta']['exchange_id'])): ?>
                                                        <a href="<?= url('warehouse/website-orders/returns/invoice/' . ($pt['meta']['return_id'] ?? $pt['meta']['exchange_id'])) ?>" class="btn btn-xs btn-outline-primary px-2 py-1" title="View Exchange Slip">
                                                            <i class="bi bi-receipt"></i>
                                                        </a>
                                                    <?php elseif ($canReportDamage && $st === 'AVAILABLE'): ?>
                                                        <a href="<?= url('warehouse/website-orders/damage/create?variant_id=' . $item['variant_id'] . '&tag_code=' . urlencode($pt['tag_code']) . '&allocation_id=' . ($pt['allocation_id'] ?? '')) ?>" class="btn btn-xs btn-outline-danger px-2 py-1" title="Report Damage">
                                                            <i class="bi bi-shield-x"></i>
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

                <!-- ========================================================================= -->
                <!-- TAB 2: STOCK LEDGER TRANSACTIONS -->
                <!-- ========================================================================= -->
                <div class="tab-pane fade" id="tab-ledger" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-text me-2 text-primary"></i>Website Channel Stock Ledger Audit Trail</h6>
                        <span class="badge bg-secondary-subtle text-secondary"><?= count($transactions) ?> Transactions Recorded</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date &amp; Time</th>
                                    <th>Movement Type</th>
                                    <th class="text-center">Partition</th>
                                    <th class="text-center">Quantity Change</th>
                                    <th>Reference Document</th>
                                    <th>Logged By</th>
                                    <th>Notes / Audit Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No stock movement transactions recorded yet for this variant.</td>
                                    </tr>
                                <?php else: foreach ($transactions as $txn): 
                                    $qtyVal = (float)$txn['quantity'];
                                    $isPositive = in_array(strtoupper($txn['transaction_type'] ?? ''), ['TRANSFER_IN', 'INWARD_RECEIVE', 'ADJUSTMENT_ADD', 'RETURN_CUSTOMER']);
                                ?>
                                    <tr>
                                        <td class="small">
                                            <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($txn['created_at'])) ?></div>
                                            <small class="text-muted"><?= date('h:i A', strtotime($txn['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace">
                                                <?= htmlspecialchars($txn['transaction_type'] ?? 'TRANSFER') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= ($txn['stock_state'] ?? '') === 'DAMAGED' ? 'danger' : 'success' ?>-subtle text-<?= ($txn['stock_state'] ?? '') === 'DAMAGED' ? 'danger' : 'success' ?> border">
                                                <?= htmlspecialchars($txn['stock_state'] ?? 'AVAILABLE') ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold fs-6 <?= $isPositive ? 'text-success' : 'text-danger' ?>">
                                            <?= $isPositive ? '+' : '-' ?><?= number_format(abs($qtyVal)) ?> pcs
                                        </td>
                                        <td class="small font-monospace">
                                            <?= htmlspecialchars(($txn['reference_type'] ?? '') . ' #' . ($txn['reference_id'] ?? '')) ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= htmlspecialchars($txn['user_name'] ?? 'System') ?>
                                        </td>
                                        <td class="small text-muted" style="max-width: 320px;">
                                            <?php 
                                            $rawNotes = $txn['notes'] ?? '-';
                                            if (preg_match('/\[Piece Tags:\s*([^\]]+)\]/', $rawNotes, $tagMatch)) {
                                                $prefixText = trim(str_replace($tagMatch[0], '', $rawNotes));
                                                $tagsList = array_filter(array_map('trim', explode(',', $tagMatch[1])));
                                                echo htmlspecialchars($prefixText) . ' ';
                                                foreach ($tagsList as $tg) {
                                                    echo '<span class="badge bg-primary text-white font-monospace ms-1 shadow-sm"><i class="bi bi-qr-code-scan me-1"></i>' . htmlspecialchars($tg) . '</span>';
                                                }
                                            } else {
                                                echo htmlspecialchars($rawNotes);
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- TAB 3: WAREHOUSE INWARD ALLOCATIONS -->
                <!-- ========================================================================= -->
                <?php if ($canViewInward): ?>
                <div class="tab-pane fade" id="tab-inward" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Inward Dispatches from Central Warehouse</h6>
                        <span class="badge bg-primary-subtle text-primary">Total Received: <?= number_format($metrics['total_inward_qty']) ?> pcs</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Allocation Ref #</th>
                                    <th>Origin Warehouse</th>
                                    <th>Piece Tag Range</th>
                                    <th class="text-center">Dispatched Qty</th>
                                    <th class="text-center">Received Qty</th>
                                    <th class="text-center">Transit Damaged</th>
                                    <th class="text-center">Status</th>
                                    <th>Dispatch Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inwardHistory)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">No warehouse inward allocations recorded for this product.</td>
                                    </tr>
                                <?php else: foreach ($inwardHistory as $inw): 
                                    $inwSavedQrs = !empty($inw['selected_qr_codes']) ? array_filter(array_map('trim', explode(',', $inw['selected_qr_codes']))) : [];
                                    $inwTagRange = !empty($inwSavedQrs) ? ($inwSavedQrs[0] . (count($inwSavedQrs) > 1 ? (' → ' . end($inwSavedQrs)) : '')) : 'Auto Sequence';
                                ?>
                                    <tr>
                                        <td class="fw-bold font-monospace text-dark">
                                            <?= htmlspecialchars($inw['allocation_number']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($inw['warehouse_name'] ?? 'Main Central Warehouse') ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-primary border font-monospace"><?= htmlspecialchars($inwTagRange) ?></span>
                                        </td>
                                        <td class="text-center fw-semibold text-dark">
                                            <?= number_format($inw['dispatched_qty'] > 0 ? $inw['dispatched_qty'] : $inw['requested_qty']) ?> pcs
                                        </td>
                                        <td class="text-center fw-bold text-success">
                                            <?= number_format($inw['received_qty'] ?? 0) ?> pcs
                                        </td>
                                        <td class="text-center fw-bold text-danger">
                                            <?= (int)($inw['damaged_qty'] ?? 0) > 0 ? (number_format($inw['damaged_qty']) . ' pcs') : '-' ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= in_array($inw['alloc_status'], ['COMPLETED', 'DISPATCHED', 'RECEIVED']) ? 'success' : 'warning text-dark' ?>">
                                                <?= htmlspecialchars($inw['alloc_status']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('d M Y, h:i A', strtotime($inw['alloc_date'])) ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('warehouse/website-orders/inward/view/' . $inw['allocation_id']) ?>" class="btn btn-xs btn-outline-primary px-2 py-1">
                                                <i class="bi bi-eye"></i> View Batch
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ========================================================================= -->
                <!-- TAB 4: WEBSITE SALES ORDERS -->
                <!-- ========================================================================= -->
                <?php if ($canViewOrders): ?>
                <div class="tab-pane fade" id="tab-sales" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-cart-check me-2 text-primary"></i>Direct Website Sales &amp; Dispatch Orders</h6>
                        <span class="badge bg-primary-subtle text-primary">Total Dispatched: <?= number_format($metrics['total_sold_qty']) ?> pcs</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order / Invoice #</th>
                                    <th>Customer Details</th>
                                    <th>Scanned Tag Codes</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Selling Price</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-center">Status</th>
                                    <th>Order Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($salesHistory)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">No website sales orders generated for this variant yet.</td>
                                    </tr>
                                <?php else: foreach ($salesHistory as $sale): 
                                    $scannedTags = !empty($sale['scanned_qr_codes']) ? (is_array(json_decode($sale['scanned_qr_codes'], true)) ? implode(', ', json_decode($sale['scanned_qr_codes'], true)) : $sale['scanned_qr_codes']) : '-';
                                ?>
                                    <tr>
                                        <td class="fw-bold font-monospace text-dark">
                                            <?= htmlspecialchars($sale['invoice_number'] ?? ('ORD #' . ($sale['sale_id'] ?? ''))) ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($sale['customer_name'] ?? 'Online Customer') ?></div>
                                            <small class="text-muted"><i class="bi bi-globe text-primary me-1"></i><?= htmlspecialchars($sale['customer_phone'] ?? 'Web Store') ?></small>
                                        </td>
                                        <td class="small font-monospace text-muted">
                                            <?= htmlspecialchars($scannedTags) ?>
                                        </td>
                                        <td class="text-center fw-bold text-dark">
                                            <?= number_format($sale['quantity']) ?> pcs
                                        </td>
                                        <td class="text-end">
                                            ₹<?= number_format((float)($sale['unit_price'] ?? $sale['price'] ?? $item['selling_price']), 2) ?>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            ₹<?= number_format((float)($sale['total_price'] ?? ($sale['quantity'] * $item['selling_price'])), 2) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= ($sale['payment_status'] ?? '') === 'PAID' ? 'success' : 'primary' ?>-subtle text-<?= ($sale['payment_status'] ?? '') === 'PAID' ? 'success' : 'primary' ?>">
                                                <?= htmlspecialchars($sale['payment_status'] ?? 'COMPLETED') ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('d M Y, h:i A', strtotime($sale['sale_date'])) ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('warehouse/website-orders/history') ?>" class="btn btn-xs btn-outline-primary px-2 py-1">
                                                <i class="bi bi-eye"></i> View History
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ========================================================================= -->
                <!-- TAB 5: DAMAGE & QUARANTINE LOG -->
                <!-- ========================================================================= -->
                <?php if ($canViewDamage): ?>
                <div class="tab-pane fade" id="tab-damage" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-x me-2 text-danger"></i>Quarantined Damage Logs &amp; Return Batches</h6>
                        <span class="badge bg-danger-subtle text-danger">Total Damaged: <?= number_format($metrics['total_dmg_qty']) ?> pcs</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Damage Ref #</th>
                                    <th>Quarantined Tag Code(s)</th>
                                    <th>Defect Category / Reason</th>
                                    <th class="text-center">Damaged Qty</th>
                                    <th class="text-end">Financial Loss</th>
                                    <th class="text-center">Status</th>
                                    <th>Outward Return Batch</th>
                                    <th>Logged On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($damageHistory)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No damage or defect records reported for this product in Website channel.</td>
                                    </tr>
                                <?php else: foreach ($damageHistory as $dmg): ?>
                                    <tr>
                                        <td class="fw-bold font-monospace text-danger">
                                            <?= htmlspecialchars($dmg['damage_number']) ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($dmg['tag_code'])): ?>
                                                <span class="badge bg-light text-danger border font-monospace"><?= htmlspecialchars($dmg['tag_code']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">Inward Batch</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-1">
                                                <?= htmlspecialchars($dmg['defect_type'] ?? 'DEFECT') ?>
                                            </span>
                                            <small class="text-muted d-block mt-0.5"><?= htmlspecialchars($dmg['damage_reason'] ?? '-') ?></small>
                                        </td>
                                        <td class="text-center fw-bold text-danger fs-6">
                                            <?= number_format($dmg['quantity']) ?> pcs
                                        </td>
                                        <td class="text-end fw-bold text-danger">
                                            ₹<?= number_format((float)($dmg['total_loss'] ?? 0), 2) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary text-white">
                                                <?= htmlspecialchars($dmg['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($canViewDamage && !empty($dmg['outward_batch_id'])): ?>
                                                <a href="<?= url('warehouse/website-orders/damage/outward/view/' . $dmg['outward_batch_id']) ?>" class="badge bg-danger text-decoration-none">
                                                    <?= htmlspecialchars($dmg['outward_batch_number'] ?? ('Batch #' . $dmg['outward_batch_id'])) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">In Quarantine</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('d M Y, h:i A', strtotime($dmg['created_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ========================================================================= -->
                <!-- TAB 5: RETURNS & EXCHANGES -->
                <!-- ========================================================================= -->
                <?php if ($canViewReturns): ?>
                <div class="tab-pane fade" id="tab-returns" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-arrow-left-right me-2 text-info"></i>Website Customer Returns &amp; Product Exchanges</h6>
                        <div class="d-flex gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Restocked / Returned: <?= (int)($metrics['total_returned_qty'] ?? 0) ?> pcs</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Exchanged Out: <?= (int)($metrics['total_exchanged_qty'] ?? 0) ?> pcs</span>
                        </div>
                    </div>

                    <?php 
                    $combinedEvents = [];
                    foreach (($returnsHistory ?? []) as $ret) {
                        $combinedEvents[] = [
                            'type'        => 'RETURN',
                            'doc_no'      => $ret['return_number'],
                            'return_id'   => $ret['return_id'] ?? $ret['id'],
                            'sale_inv'    => $ret['sale_invoice'] ?? '-',
                            'customer'    => $ret['customer_name'] ?? 'Website Customer',
                            'phone'       => $ret['customer_phone'] ?? '-',
                            'piece_tag'   => $ret['piece_tag'] ?? '-',
                            'qty'         => (int)$ret['quantity'],
                            'condition'   => $ret['item_condition'] ?? $ret['condition_type'] ?? 'GOOD',
                            'amount'      => (float)($ret['refund_amount'] ?? 0),
                            'status'      => $ret['return_status'] ?? $ret['status'] ?? 'APPROVED',
                            'date'        => $ret['return_date'] ?? $ret['created_at'],
                            'notes'       => $ret['return_reason'] ?? '-'
                        ];
                    }
                    foreach (($exchangesHistory ?? []) as $ex) {
                        $combinedEvents[] = [
                            'type'        => 'EXCHANGE_' . strtoupper($ex['item_type'] ?? 'NEW'),
                            'doc_no'      => $ex['exchange_number'],
                            'return_id'   => $ex['return_id'] ?? null,
                            'sale_inv'    => $ex['sale_invoice'] ?? '-',
                            'customer'    => $ex['customer_name'] ?? 'Website Customer',
                            'phone'       => $ex['customer_phone'] ?? '-',
                            'piece_tag'   => $ex['piece_tag'] ?? '-',
                            'qty'         => (int)$ex['quantity'],
                            'condition'   => strtoupper($ex['item_type'] ?? '') === 'OLD' ? 'GOOD (Returned)' : 'NEW (Dispatched)',
                            'amount'      => (float)($ex['total_amount'] ?? $ex['difference_amount'] ?? 0),
                            'status'      => $ex['exchange_status'] ?? 'COMPLETED',
                            'date'        => $ex['exchange_date'] ?? $ex['created_at'],
                            'notes'       => strtoupper($ex['item_type'] ?? '') === 'OLD' ? 'Old Product Handed in by Customer' : 'New Replacement Garment Dispatched'
                        ];
                    }
                    // Sort by date desc
                    usort($combinedEvents, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));
                    ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Event Date</th>
                                    <th>Type</th>
                                    <th>Doc Number</th>
                                    <th>Original Sale</th>
                                    <th>Customer Details</th>
                                    <th>Piece Serial Tag</th>
                                    <th class="text-center">Quantity</th>
                                    <th>Condition / Action</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($combinedEvents)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center py-4 text-muted">
                                            <i class="bi bi-arrow-left-right fs-2 d-block mb-2 text-info opacity-50"></i>
                                            No return or exchange events recorded for this product variant.
                                        </td>
                                    </tr>
                                <?php else: foreach ($combinedEvents as $ev): 
                                    $isOld = str_contains($ev['type'], 'OLD') || $ev['type'] === 'RETURN';
                                    $isNew = str_contains($ev['type'], 'NEW');
                                ?>
                                    <tr>
                                        <td class="small">
                                            <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($ev['date'])) ?></div>
                                            <small class="text-muted"><?= date('h:i A', strtotime($ev['date'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($ev['type'] === 'RETURN'): ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle fw-bold"><i class="bi bi-arrow-return-left me-1"></i>RETURN</span>
                                            <?php elseif ($isOld): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><i class="bi bi-box-arrow-in-down me-1"></i>EXCHANGE (OLD)</span>
                                            <?php else: ?>
                                                <span class="badge text-white fw-bold shadow-sm" style="background-color: #7c3aed;"><i class="bi bi-box-arrow-up me-1"></i>EXCHANGE (NEW)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold font-monospace text-dark">
                                            <?= htmlspecialchars($ev['doc_no']) ?>
                                        </td>
                                        <td class="small font-monospace">
                                            <?= htmlspecialchars($ev['sale_inv']) ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark small"><?= htmlspecialchars($ev['customer']) ?></div>
                                            <small class="text-muted"><i class="bi bi-globe text-primary me-1"></i><?= htmlspecialchars($ev['phone']) ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($ev['piece_tag']) && $ev['piece_tag'] !== '-'): ?>
                                                <span class="badge bg-light text-primary border font-monospace"><?= htmlspecialchars($ev['piece_tag']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold fs-6 <?= $isOld ? 'text-success' : 'text-primary' ?>">
                                            <?= $isOld ? '+' : '-' ?><?= number_format($ev['qty']) ?> pcs
                                        </td>
                                        <td class="small">
                                            <span class="badge bg-<?= in_array($ev['condition'], ['GOOD', 'GOOD (Returned)']) ? 'success' : 'warning text-dark' ?>-subtle text-<?= in_array($ev['condition'], ['GOOD', 'GOOD (Returned)']) ? 'success' : 'warning' ?> border">
                                                <?= htmlspecialchars($ev['condition']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            ₹<?= number_format($ev['amount'], 2) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success">
                                                <?= htmlspecialchars($ev['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($canPrintReturn && !empty($ev['return_id'])): ?>
                                                <a href="<?= url('warehouse/website-orders/returns/invoice/' . $ev['return_id']) ?>" class="btn btn-xs btn-outline-primary px-2 py-1" title="Print Return/Exchange Slip">
                                                    <i class="bi bi-printer me-1"></i> Slip
                                                </a>
                                            <?php elseif ($canViewReturns): ?>
                                                <a href="<?= url('warehouse/website-orders/returns') ?>" class="btn btn-xs btn-outline-secondary px-2 py-1">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($canViewReturnToWarehouse && !empty($warehouseReturnsHistory)): ?>
                <!-- ========================================================================= -->
                <!-- TAB 7: WAREHOUSE OUTWARD RETURNS -->
                <!-- ========================================================================= -->
                <div class="tab-pane fade" id="tab-wh-returns" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-arrow-up-right me-2 text-secondary"></i>Outward Returns to Central Warehouse</h6>
                        <span class="badge bg-secondary-subtle text-secondary">Total Returned to CW: <?= number_format($metrics['total_wh_returned_qty'] ?? 0) ?> pcs</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Return Ref #</th>
                                    <th>Destination Warehouse</th>
                                    <th>Condition / Reason</th>
                                    <th class="text-center">Returned Qty</th>
                                    <th class="text-center">Good / Defect Qty</th>
                                    <th class="text-center">Status</th>
                                    <th>Dispatch Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($warehouseReturnsHistory as $wrItem): ?>
                                    <tr>
                                        <td class="fw-bold font-monospace text-dark">
                                            <?= htmlspecialchars($wrItem['return_number']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($wrItem['destination_warehouse_name'] ?? 'Central Warehouse') ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= ($wrItem['classification'] ?? '') === 'DAMAGED' ? 'danger' : 'success' ?>-subtle text-<?= ($wrItem['classification'] ?? '') === 'DAMAGED' ? 'danger' : 'success' ?> border me-1">
                                                <?= htmlspecialchars($wrItem['classification'] ?? 'GOOD') ?>
                                            </span>
                                            <small class="text-muted d-block"><?= htmlspecialchars($wrItem['reason'] ?? '-') ?></small>
                                        </td>
                                        <td class="text-center fw-bold fs-6 text-danger">
                                            -<?= number_format($wrItem['returned_qty'] ?? 1) ?> pcs
                                        </td>
                                        <td class="text-center small">
                                            <span class="text-success"><?= (int)($wrItem['good_qty'] ?? 0) ?> Good</span> / 
                                            <span class="text-danger"><?= (int)($wrItem['damaged_qty'] ?? 0) ?> Dmg</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= ($wrItem['status'] ?? '') === 'COMPLETED' ? 'success' : 'warning text-dark' ?>">
                                                <?= htmlspecialchars($wrItem['status'] ?? 'PENDING') ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('d M Y, h:i A', strtotime($wrItem['created_at'])) ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('warehouse/website-orders/return-to-warehouse') ?>" class="btn btn-xs btn-outline-secondary px-2 py-1">
                                                <i class="bi bi-eye"></i> View List
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- PRINT PIECE QR LABELS MODAL / PRINT CONTAINER -->
<!-- ========================================================================= -->
<div class="modal fade" id="printLabelsModal" tabindex="-1" aria-labelledby="printLabelsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark" id="printLabelsModalLabel">
                    <i class="bi bi-printer me-2 text-primary"></i>Print Piece QR &amp; Serial Tag Labels
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light" id="printableLabelsContainer">
                <div class="row g-3" id="printableLabelsRow">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary fw-bold" onclick="triggerBrowserPrint()">
                    <i class="bi bi-printer me-1"></i> Print Labels Now
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printableLabelsContainer, #printableLabelsContainer * {
        visibility: visible;
    }
    #printableLabelsContainer {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: #fff !important;
        padding: 0 !important;
    }
    .modal-backdrop, .modal-footer, .modal-header {
        display: none !important;
    }
}
</style>

<script>
const allPieceTagsData = <?= json_encode($pieceTags) ?>;
const masterSku = <?= json_encode($item['sku']) ?>;
const productName = <?= json_encode($item['product_name']) ?>;
const mrpVal = <?= json_encode($mrp) ?>;
const sellVal = <?= json_encode($sellingPrice) ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize individual piece QR canvases for the card grid.
    document.querySelectorAll('.piece-qr-canvas').forEach(function(el) {
        const payload = el.getAttribute('data-payload');
        if (payload) {
            new QRCode(el, {
                text: payload,
                width: 80,
                height: 80,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.M
            });
        }
    });

    // 3. Initialize Individual Piece QR Canvases for Table View
    document.querySelectorAll('.piece-table-qr-canvas').forEach(function(el) {
        const payload = el.getAttribute('data-payload');
        if (payload) {
            new QRCode(el, {
                text: payload,
                width: 44,
                height: 44,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.M
            });
        }
    });
});

// Switch between Card Grid and Table View
function switchPieceView(mode) {
    const cards = document.getElementById('pieceCardsContainer');
    const table = document.getElementById('pieceTableContainer');
    const btnCards = document.getElementById('btnViewCards');
    const btnTable = document.getElementById('btnViewTable');

    if (mode === 'table') {
        cards.classList.add('d-none');
        table.classList.remove('d-none');
        btnCards.classList.remove('active');
        btnTable.classList.add('active');
    } else {
        cards.classList.remove('d-none');
        table.classList.add('d-none');
        btnCards.classList.add('active');
        btnTable.classList.remove('active');
    }
}

// Filter Piece Tags by Status
function filterPieceTags(status, btn) {
    document.querySelectorAll('.piece-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const cards = document.querySelectorAll('.piece-item-card');
    const rows = document.querySelectorAll('.piece-table-row');

    cards.forEach(c => {
        if (status === 'all' || c.getAttribute('data-status') === status) {
            c.style.display = '';
        } else {
            c.style.display = 'none';
        }
    });

    rows.forEach(r => {
        if (status === 'all' || r.getAttribute('data-status') === status) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

// Live Search Piece Tags
function searchPieceTags() {
    const q = document.getElementById('pieceTagSearchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.piece-item-card');
    const rows = document.querySelectorAll('.piece-table-row');

    cards.forEach(c => {
        const searchTxt = c.getAttribute('data-search') || '';
        if (!q || searchTxt.includes(q)) {
            c.style.display = '';
        } else {
            c.style.display = 'none';
        }
    });

    rows.forEach(r => {
        const searchTxt = r.getAttribute('data-search') || '';
        if (!q || searchTxt.includes(q)) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

function clearPieceSearch() {
    document.getElementById('pieceTagSearchInput').value = '';
    searchPieceTags();
}

// Copy Tag Code to Clipboard
function copyTagCode(tag) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(tag).then(() => {
            alert('Tag Code "' + tag + '" copied to clipboard!');
        });
    } else {
        const temp = document.createElement('input');
        temp.value = tag;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        alert('Tag Code "' + tag + '" copied to clipboard!');
    }
}

// Print Modal Handlers
function printAllPieceTags() {
    populatePrintModal(allPieceTagsData);
    const modal = new bootstrap.Modal(document.getElementById('printLabelsModal'));
    modal.show();
}

function printSinglePieceTag(piece) {
    populatePrintModal([piece]);
    const modal = new bootstrap.Modal(document.getElementById('printLabelsModal'));
    modal.show();
}

function populatePrintModal(pieces) {
    const container = document.getElementById('printableLabelsRow');
    container.innerHTML = '';

    pieces.forEach((p, idx) => {
        const col = document.createElement('div');
        col.className = 'col-sm-6 col-md-4 col-lg-3';
        col.innerHTML = `
            <div class="bg-white p-2 border rounded text-center shadow-sm" style="page-break-inside: avoid; border: 1.5px dashed #333 !important;">
                <div class="fw-bold text-dark font-monospace" style="font-size: 11px;">GRM INVENTORY</div>
                <div class="small fw-semibold text-truncate text-dark mb-1" style="font-size: 10px;">${productName}</div>
                <div id="modalQr_${idx}" class="d-flex justify-content-center my-1"></div>
                <div class="fw-bold font-monospace text-dark" style="font-size: 13px;">${p.tag_code}</div>
                <div class="small font-monospace text-muted" style="font-size: 9px;">${masterSku}</div>
                <div class="d-flex justify-content-between px-2 text-dark font-monospace" style="font-size: 10px;">
                    <span>MRP: ₹${p.mrp || mrpVal}</span>
                    <span class="fw-bold">SP: ₹${p.disc || sellVal}</span>
                </div>
            </div>
        `;
        container.appendChild(col);

        setTimeout(() => {
            const qrEl = document.getElementById(`modalQr_${idx}`);
            if (qrEl) {
                new QRCode(qrEl, {
                    text: p.qr_payload,
                    width: 70,
                    height: 70,
                    colorDark : "#000000",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.M
                });
            }
        }, 50);
    });
}

function triggerBrowserPrint() {
    window.print();
}
</script>

<!-- ========================================================================= -->
<!-- MODAL: EDIT PRODUCT RETAIL PRICE & MRP (WEBSITE CHANNEL) -->
<!-- ========================================================================= -->
<?php if ($canAdjustPrice): ?>
<div class="modal fade" id="editPriceModal" tabindex="-1" aria-labelledby="editPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form id="editPriceForm" onsubmit="handlePriceFormSubmit(event)">
                <?= csrf_field() ?>
                <input type="hidden" name="variant_id" id="editPriceVariantId" value="">
                <input type="hidden" id="editPriceCostVal" value="0">
                
                <div class="modal-header bg-success text-white py-3 px-4 border-0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-tag-fill fs-5"></i>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="editPriceModalLabel">Edit Product Price</h5>
                            <small class="text-white-50 font-monospace" id="editPriceModalSku">-</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <!-- Product Title Banner -->
                    <div class="p-3 bg-white rounded-3 border shadow-2xs mb-3">
                        <h6 class="fw-bold text-dark mb-1" id="editPriceModalProductName">-</h6>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-dark font-monospace text-white px-2 py-1" id="editPriceBadgeSku">-</span>
                            <span class="badge bg-light text-muted border" id="editPriceCostBadge">Cost: ₹0.00</span>
                        </div>
                    </div>

                    <!-- Alert message container -->
                    <div id="editPriceAlertContainer"></div>

                    <!-- Price Inputs -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-bold small text-uppercase text-dark mb-1">
                                Selling Price (₹) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold text-success">₹</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       name="selling_price" 
                                       id="inputSellingPrice" 
                                       class="form-control form-control-lg fw-bold text-success" 
                                       placeholder="0.00" 
                                       required
                                       oninput="calculatePriceAnalytics()">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Active retail selling rate</small>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-bold small text-uppercase text-dark mb-1">
                                Maximum Retail Price (MRP ₹)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted">₹</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       name="mrp" 
                                       id="inputMrpPrice" 
                                       class="form-control form-control-lg text-dark fw-semibold" 
                                       placeholder="0.00" 
                                       oninput="calculatePriceAnalytics()">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Printed sticker / catalog MRP</small>
                        </div>
                    </div>

                    <!-- Live Price Analytics Card -->
                    <div class="p-3 bg-white rounded-3 border shadow-2xs mb-3" id="priceAnalyticsCard">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted fw-semibold text-uppercase" style="font-size: 10px;">Price Dynamics &amp; Margin</small>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace" id="badgeDiscountPreview">Standard Price</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small">
                                <span class="text-muted">Markup over Cost: </span>
                                <strong class="text-dark" id="txtMarkupPreview">—</strong>
                            </div>
                            <div class="small">
                                <span class="text-muted">Discount on MRP: </span>
                                <strong class="text-primary" id="txtDiscountPreview">0%</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Reason / Revision Notes -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-muted text-uppercase mb-1">Revision Notes / Reason (Optional)</label>
                        <input type="text" name="notes" id="inputPriceNotes" class="form-control" placeholder="e.g. Website promotion, Season discount">
                    </div>
                </div>

                <div class="modal-footer bg-white px-4 py-3 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm" id="btnSavePrice">
                        <i class="bi bi-check-circle me-1"></i> Save Price Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
let currentEditPriceModal = null;
const canAdjustPrice = <?= $canAdjustPrice ? 'true' : 'false' ?>;

window.openEditPriceModal = function(variantId, sku, productName, sellingPrice, mrp, costPrice = 0) {
    if (!canAdjustPrice) return;
    document.getElementById('editPriceVariantId').value = variantId;
    document.getElementById('editPriceModalSku').textContent = `SKU: ${sku}`;
    document.getElementById('editPriceBadgeSku').textContent = sku;
    document.getElementById('editPriceModalProductName').textContent = productName || 'Product';
    document.getElementById('inputSellingPrice').value = parseFloat(sellingPrice || 0).toFixed(2);
    document.getElementById('inputMrpPrice').value = (parseFloat(mrp) > 0) ? parseFloat(mrp).toFixed(2) : parseFloat(sellingPrice || 0).toFixed(2);
    document.getElementById('editPriceCostVal').value = parseFloat(costPrice || 0);
    document.getElementById('editPriceCostBadge').textContent = (parseFloat(costPrice) > 0) ? `Cost: ₹${parseFloat(costPrice).toFixed(2)}` : 'Cost: Standard';
    document.getElementById('editPriceAlertContainer').innerHTML = '';
    document.getElementById('inputPriceNotes').value = '';

    calculatePriceAnalytics();

    const modalEl = document.getElementById('editPriceModal');
    if (modalEl) {
        currentEditPriceModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        currentEditPriceModal.show();
        setTimeout(() => {
            const spInput = document.getElementById('inputSellingPrice');
            if (spInput) {
                spInput.focus();
                spInput.select();
            }
        }, 300);
    }
};

window.calculatePriceAnalytics = function() {
    const sp = parseFloat(document.getElementById('inputSellingPrice')?.value) || 0;
    const mrp = parseFloat(document.getElementById('inputMrpPrice')?.value) || 0;
    const cost = parseFloat(document.getElementById('editPriceCostVal')?.value) || 0;

    const discountBadge = document.getElementById('badgeDiscountPreview');
    const txtDiscount = document.getElementById('txtDiscountPreview');
    const txtMarkup = document.getElementById('txtMarkupPreview');

    // Calculate Discount on MRP
    if (mrp > sp && sp > 0 && mrp > 0) {
        const discPct = Math.round(((mrp - sp) / mrp) * 100);
        if (txtDiscount) txtDiscount.textContent = `${discPct}% Off`;
        if (discountBadge) {
            discountBadge.textContent = `${discPct}% Discount`;
            discountBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill font-monospace';
        }
    } else {
        if (txtDiscount) txtDiscount.textContent = '0%';
        if (discountBadge) {
            discountBadge.textContent = 'Standard Price';
            discountBadge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace';
        }
    }

    // Calculate Markup over Cost
    if (cost > 0 && sp > 0) {
        const markupPct = Math.round(((sp - cost) / cost) * 100);
        if (txtMarkup) {
            txtMarkup.textContent = markupPct >= 0 ? `+${markupPct}%` : `${markupPct}%`;
            txtMarkup.className = markupPct >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
        }
    } else {
        if (txtMarkup) txtMarkup.textContent = '—';
    }
};

window.handlePriceFormSubmit = function(event) {
    event.preventDefault();
    if (!canAdjustPrice) return;
    const form = document.getElementById('editPriceForm');
    const btn = document.getElementById('btnSavePrice');
    const alertBox = document.getElementById('editPriceAlertContainer');
    const sp = parseFloat(document.getElementById('inputSellingPrice').value) || 0;

    if (sp < 0) {
        alertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3">Selling price cannot be negative.</div>`;
        return;
    }

    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...`;

    const formData = new FormData(form);
    formData.append('is_ajax', '1');

    fetch('<?= url("warehouse/website-orders/inventory/update-price") ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-check-circle me-1"></i> Save Price Changes`;

        if (!res.success) {
            alertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3"><i class="bi bi-exclamation-triangle me-1"></i>${escapeHtml(res.message || 'Error updating price')}</div>`;
            return;
        }

        // Instant Page Hero Update
        const spEl = document.getElementById('detailPageSellingPrice');
        const mrpEl = document.getElementById('detailPageMrp');
        const stdMrpEl = document.getElementById('detailPageStandardMrp');
        const discBadge = document.getElementById('detailPageDiscountBadge');

        if (spEl) spEl.textContent = res.data.formatted_price;
        if (mrpEl && stdMrpEl) {
            if (res.data.new_mrp > res.data.new_selling_price) {
                mrpEl.textContent = `MRP: ${res.data.formatted_mrp}`;
                mrpEl.classList.remove('d-none');
                stdMrpEl.classList.add('d-none');

                if (discBadge) {
                    const disc = Math.round(((res.data.new_mrp - res.data.new_selling_price) / res.data.new_mrp) * 100);
                    discBadge.textContent = `${disc}% OFF`;
                    discBadge.classList.remove('d-none');
                }
            } else {
                mrpEl.classList.add('d-none');
                stdMrpEl.classList.remove('d-none');
                if (discBadge) discBadge.classList.add('d-none');
            }
        }

        // Hide Edit Price Modal
        if (currentEditPriceModal) {
            currentEditPriceModal.hide();
        }

        // Show alert / toast
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Price Updated!',
                text: res.message,
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            showPriceUpdateToast(res.message || 'Price updated successfully!');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-check-circle me-1"></i> Save Price Changes`;
        alertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3">Server connection error. Please try again.</div>`;
    });
};

function showPriceUpdateToast(message) {
    let toast = document.getElementById('priceUpdateLiveToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'priceUpdateLiveToast';
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="toast align-items-center text-white bg-success border-0 show shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <span id="priceUpdateLiveToastMsg"></span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        document.body.appendChild(toast);
    }
    const msgEl = document.getElementById('priceUpdateLiveToastMsg');
    if (msgEl) msgEl.textContent = message;
    setTimeout(() => {
        if (toast && toast.parentNode) toast.remove();
    }, 4000);
}
</script>
