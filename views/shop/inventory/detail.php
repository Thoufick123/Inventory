<?php
// views/shop/inventory/detail.php — Full Page Product & Stock Ledger Detail for Shop Channel (Exact WhatsApp & Website Style)
$availQty = (float)($item['available_quantity'] ?? $item['quantity'] ?? 0);
$damagedQty = (float)($item['damaged_quantity'] ?? 0);
$minStock = (int)($item['min_stock'] ?? 5);
$isLow = ($availQty > 0 && $availQty <= $minStock);
$mrp = (float)($item['mrp'] ?? 0);
$sellingPrice = (float)($item['selling_price'] ?? 0);
$cost = (float)($item['production_cost'] > 0 ? $item['production_cost'] : ($item['purchase_price'] ?? 0));
$discount = ($mrp > $sellingPrice && $mrp > 0) ? round((($mrp - $sellingPrice) / $mrp) * 100) : 0;

$pieceTags = $pieceTags ?? ($item['individual_pieces'] ?? []);
$tagStats = [
    'total'              => count($pieceTags),
    'available'          => count(array_filter($pieceTags, fn($p) => strtoupper($p['status'] ?? '') === 'AVAILABLE' || strtolower($p['status'] ?? '') === 'in stock')),
    'damaged'            => count(array_filter($pieceTags, fn($p) => strtoupper($p['status'] ?? '') === 'DAMAGED' || !empty($p['is_damaged']))),
    'sold'               => count(array_filter($pieceTags, fn($p) => strtoupper($p['status'] ?? '') === 'SOLD' || !empty($p['is_sold']))),
    'restocked'          => count(array_filter($pieceTags, fn($p) => !empty($p['meta']['is_restocked']))),
    'exchanged'          => count(array_filter($pieceTags, fn($p) => strtoupper($p['status'] ?? '') === 'EXCHANGED' || !empty($p['is_exchanged']))),
    'returned_warehouse' => count(array_filter($pieceTags, fn($p) => strtoupper($p['status'] ?? '') === 'RETURNED' || strtoupper($p['status'] ?? '') === 'RETURNED_TO_WAREHOUSE' || !empty($p['is_returned']))),
    'in_transit'         => count(array_filter($pieceTags, fn($p) => strtoupper($p['status'] ?? '') === 'IN_TRANSIT')),
];

$firstTag = !empty($pieceTags) ? ($pieceTags[0]['tag_code'] ?? '') : '';
$lastTag  = !empty($pieceTags) ? ($pieceTags[count($pieceTags) - 1]['tag_code'] ?? '') : '';
$tagRangeStr = ($firstTag && $lastTag) ? ($firstTag === $lastTag ? $firstTag : "{$firstTag} → {$lastTag}") : 'N/A';

$transactions = $transactions ?? ($item['transactions'] ?? []);
$inwardHistory = $inwardHistory ?? ($item['inward_history'] ?? []);
$salesHistory = $salesHistory ?? [];
$damageHistory = $damageHistory ?? [];
$returnsHistory = $returnsHistory ?? [];
$exchangesHistory = $exchangesHistory ?? [];
$warehouseReturnsHistory = $warehouseReturnsHistory ?? [];
$metrics = $metrics ?? [
    'total_sold_qty' => count(array_filter($pieceTags, fn($p) => !empty($p['is_sold']) || strtoupper($p['status'] ?? '') === 'SOLD')),
    'total_sold_amt' => array_sum(array_column($salesHistory, 'total_price')),
    'total_returned_qty' => count(array_filter($pieceTags, fn($p) => !empty($p['is_returned']))),
    'total_exchanged_qty' => count(array_filter($pieceTags, fn($p) => !empty($p['is_exchanged']))),
];
?>

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
/* Returned to Warehouse Piece UI Styling */
.piece-item-card.is-returned .card {
    background: #f8fafc !important;
    border: 1.5px dashed #94a3b8 !important;
    opacity: 0.82;
}
.piece-item-card.is-returned .card-header {
    background: #f1f5f9 !important;
    border-bottom: 1px dashed #94a3b8 !important;
}
.piece-item-card.is-returned .returned-tag-box {
    background: #f1f5f9 !important;
    color: #475569 !important;
    border-color: #cbd5e1 !important;
}
.piece-item-card.is-returned .returned-watermark-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-15deg);
    background: rgba(71, 85, 105, 0.9);
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

/* Sold Piece Disabled UI Styling */
.piece-item-card.is-sold .card {
    background: #f8fafc !important;
    border: 1.5px dashed #cbd5e1 !important;
    box-shadow: none !important;
    opacity: 0.75;
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

/* Exchanged Piece UI Styling */
.piece-item-card.is-exchanged .card {
    background: #faf5ff !important;
    border: 1.5px dashed #c084fc !important;
    opacity: 0.88;
}
.piece-item-card.is-exchanged .card-header {
    background: #f3e8ff !important;
    border-bottom: 1px dashed #c084fc !important;
}
.piece-item-card.is-exchanged .exchanged-tag-box {
    background: #f3e8ff !important;
    color: #6b21a8 !important;
    border-color: #d8b4fe !important;
}
.piece-item-card.is-exchanged .exchanged-watermark-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-15deg);
    background: rgba(109, 40, 217, 0.9);
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1px;
    padding: 2px 8px;
    border-radius: 4px;
    pointer-events: none;
    z-index: 5;
    text-transform: uppercase;
}

/* Restocked Piece UI Styling */
.piece-item-card.is-restocked .card {
    border-color: #10b981 !important;
    background: #f0fdf4 !important;
}
.piece-item-card.is-restocked .card-header {
    background: #dcfce7 !important;
    border-bottom: 1px solid #86efac !important;
}

/* Sold Table Row Disabled Styling */
.piece-table-row.is-sold {
    background-color: #f8fafc !important;
    color: #94a3b8 !important;
    opacity: 0.75;
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
.piece-table-row.is-exchanged {
    background-color: #faf5ff !important;
    color: #6b21a8 !important;
}
.piece-table-row.is-restocked {
    background-color: #f0fdf4 !important;
}
</style>

<div class="container-fluid py-4">
    <!-- Header & Quick Actions -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-1 rounded-pill fw-semibold">
                    <i class="bi bi-shop me-1"></i><?= htmlspecialchars($shop['name'] ?? 'Shop Channel') ?>
                </span>
                <span class="badge bg-dark text-white font-monospace rounded-pill px-3 py-1">
                    <?= htmlspecialchars($item['sku']) ?>
                </span>
                <?php if (!empty($pieceTags)): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                        <i class="bi bi-qr-code me-1"></i><?= $tagStats['available'] ?> Available In Stock<?php if ($tagStats['sold'] > 0 || $tagStats['restocked'] > 0 || $tagStats['exchanged'] > 0 || $tagStats['returned_warehouse'] > 0 || $tagStats['damaged'] > 0): ?> <span class="text-muted fw-normal">(<?= $tagStats['sold'] ?> Sold<?php if ($tagStats['damaged'] > 0): ?> &bull; <?= $tagStats['damaged'] ?> Damaged<?php endif; ?><?php if ($tagStats['restocked'] > 0): ?> &bull; <?= $tagStats['restocked'] ?> Restocked<?php endif; ?><?php if ($tagStats['exchanged'] > 0): ?> &bull; <?= $tagStats['exchanged'] ?> Exchanged<?php endif; ?><?php if ($tagStats['returned_warehouse'] > 0): ?> &bull; <?= $tagStats['returned_warehouse'] ?> Returned to WH<?php endif; ?> / <?= count($pieceTags) ?> Total)</span><?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
            <h2 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($item['product_name']) ?></h2>
            <p class="text-muted small mb-0 font-monospace">Product Code: <?= htmlspecialchars($item['product_code'] ?? 'N/A') ?> | Tag Range: <span class="fw-semibold text-primary"><?= htmlspecialchars($tagRangeStr) ?></span></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= url('shop/inventory') ?>?shop_id=<?= (int)$shop['id'] ?>" class="btn btn-outline-secondary rounded-pill px-3 fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Back to Inventory
            </a>
        </div>
    </div>

    <!-- Product Stock Summary -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="row g-3">
                <!-- 1. Selling Price -->
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-success border-4 position-relative">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted fw-semibold d-block text-uppercase" style="font-size: 11px;">Selling Price</small>
                        </div>
                        <h3 class="fw-bold text-success mb-0 mt-1" id="detailPageSellingPrice">₹<?= number_format($sellingPrice, 2) ?></h3>
                        <small class="text-muted text-decoration-line-through <?= $mrp > $sellingPrice ? '' : 'd-none' ?>" id="detailPageMrp">MRP: ₹<?= number_format($mrp, 2) ?></small>
                        <small class="text-muted <?= $mrp <= $sellingPrice ? '' : 'd-none' ?>" id="detailPageStandardMrp">Standard Retail</small>
                    </div>
                </div>

                <!-- 2. Available Shop Stock -->
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

                <!-- 4. Total Shop POS Sales Revenue & Net Sold -->
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-info border-4">
                        <small class="text-muted fw-semibold d-block text-uppercase" style="font-size: 11px;">Units Sold (Billed)</small>
                        <h3 class="fw-bold text-info-emphasis mb-0 mt-1">
                            <?= number_format($metrics['net_sold_qty'] ?? $metrics['total_sold_qty'] ?? $tagStats['sold'] ?? 0) ?> <small class="fs-6 fw-normal text-muted">pcs</small>
                        </h3>
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">
                            Rev: <span class="fw-bold text-dark">₹<?= number_format($metrics['total_sold_amt'] ?? 0, 2) ?></span>
                        </small>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Lifecycle History Tabs (7 Detailed Sections) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom pt-3 pb-0 px-4">
            <ul class="nav nav-tabs card-header-tabs" id="productHistoryTabs" role="tablist">
                <!-- 1. Tracked Piece QR & Tag Codes -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold text-primary border-primary border-bottom-0" id="tab-piecetags-tab" data-bs-toggle="tab" data-bs-target="#tab-piecetags" type="button" role="tab">
                        <i class="bi bi-qr-code-scan me-1 text-primary"></i> Piece QR &amp; Tag Codes (<?= $tagStats['available'] ?> In Stock / <?= count($pieceTags) ?> Total)
                    </button>
                </li>
                <!-- 2. Stock Ledger Transactions -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-muted" id="tab-ledger-tab" data-bs-toggle="tab" data-bs-target="#tab-ledger" type="button" role="tab">
                        <i class="bi bi-clock-history me-1"></i> Stock Ledger (<?= count($transactions) ?>)
                    </button>
                </li>
                <!-- 3. Warehouse Inward Allocations -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-muted" id="tab-inward-tab" data-bs-toggle="tab" data-bs-target="#tab-inward" type="button" role="tab">
                        <i class="bi bi-truck me-1"></i> Warehouse Inwards (<?= count($inwardHistory) ?>)
                    </button>
                </li>
                <!-- 4. Shop POS Sales Orders -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-muted" id="tab-sales-tab" data-bs-toggle="tab" data-bs-target="#tab-sales" type="button" role="tab">
                        <i class="bi bi-receipt me-1"></i> Shop POS Sales (<?= count($salesHistory) ?>)
                    </button>
                </li>
                <!-- 5. Returns & Exchanges -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-muted" id="tab-returns-tab" data-bs-toggle="tab" data-bs-target="#tab-returns" type="button" role="tab">
                        <i class="bi bi-arrow-left-right me-1 text-info"></i> Returns &amp; Exchanges (<?= count($returnsHistory ?? []) + count($exchangesHistory ?? []) ?>)
                    </button>
                </li>
                <!-- 6. Damage & Quarantine Log -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-muted" id="tab-damage-tab" data-bs-toggle="tab" data-bs-target="#tab-damage" type="button" role="tab">
                        <i class="bi bi-shield-x me-1 text-danger"></i> Damage &amp; Quarantine (<?= count($damageHistory) ?>)
                    </button>
                </li>
                <!-- 7. Return to Central Warehouse -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-muted" id="tab-wh-returns-tab" data-bs-toggle="tab" data-bs-target="#tab-wh-returns" type="button" role="tab">
                        <i class="bi bi-box-arrow-right me-1 text-secondary"></i> Warehouse Returns (<?= count($warehouseReturnsHistory ?? []) ?>)
                    </button>
                </li>
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
                    <div class="row g-2 mb-4">
                        <div class="col-6 col-md-auto">
                            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill w-100 fw-semibold active piece-filter-btn" data-filter="all" onclick="filterPieceTags('all', this)">
                                All Pieces <span class="badge bg-dark text-white rounded-pill ms-1"><?= $tagStats['total'] ?></span>
                            </button>
                        </div>
                        <div class="col-6 col-md-auto">
                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold piece-filter-btn" data-filter="AVAILABLE" onclick="filterPieceTags('AVAILABLE', this)">
                                Available in Stock <span class="badge bg-success rounded-pill ms-1"><?= $tagStats['available'] ?></span>
                            </button>
                        </div>
                        <?php if ($tagStats['restocked'] > 0): ?>
                            <div class="col-6 col-md-auto">
                                <button type="button" class="btn btn-sm btn-outline-success border-success rounded-pill w-100 fw-semibold piece-filter-btn" data-filter="RESTOCKED" onclick="filterPieceTags('RESTOCKED', this)">
                                    <i class="bi bi-arrow-repeat me-1"></i>Restocked (Returns) <span class="badge bg-success rounded-pill ms-1"><?= $tagStats['restocked'] ?></span>
                                </button>
                            </div>
                        <?php endif; ?>
                        <?php if ($tagStats['exchanged'] > 0): ?>
                            <div class="col-6 col-md-auto">
                                <button type="button" class="btn btn-sm btn-outline-primary border-primary rounded-pill w-100 fw-semibold piece-filter-btn" data-filter="EXCHANGED" onclick="filterPieceTags('EXCHANGED', this)">
                                    <i class="bi bi-arrow-left-right me-1"></i>Exchanged (Out) <span class="badge text-white rounded-pill ms-1" style="background-color: #7c3aed;"><?= $tagStats['exchanged'] ?></span>
                                </button>
                            </div>
                        <?php endif; ?>
                        <?php if ($tagStats['returned_warehouse'] > 0): ?>
                            <div class="col-6 col-md-auto">
                                <button type="button" class="btn btn-sm btn-outline-secondary border-secondary rounded-pill w-100 fw-semibold piece-filter-btn" data-filter="RETURNED" onclick="filterPieceTags('RETURNED', this)">
                                    <i class="bi bi-box-arrow-right me-1"></i>Returned to Warehouse <span class="badge bg-secondary text-white rounded-pill ms-1"><?= $tagStats['returned_warehouse'] ?></span>
                                </button>
                            </div>
                        <?php endif; ?>
                        <div class="col-6 col-md-auto">
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill w-100 fw-semibold piece-filter-btn" data-filter="DAMAGED" onclick="filterPieceTags('DAMAGED', this)">
                                Damaged / Quarantined <span class="badge bg-danger rounded-pill ms-1"><?= $tagStats['damaged'] ?></span>
                            </button>
                        </div>
                        <div class="col-6 col-md-auto">
                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill w-100 fw-semibold piece-filter-btn" data-filter="SOLD" onclick="filterPieceTags('SOLD', this)">
                                Sold <span class="badge bg-info text-dark rounded-pill ms-1"><?= $tagStats['sold'] ?></span>
                            </button>
                        </div>
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
                            <input type="text" id="pieceTagSearchInput" class="form-control border-start-0" placeholder="Filter by Tag Code (e.g. GRM102610), QR payload, or invoice/GRN batch..." onkeyup="searchPieceTags()">
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
                            <?php foreach ($pieceTags as $idx => $pt): 
                                $st = strtoupper($pt['status'] ?? 'AVAILABLE');
                                if ($st === 'IN STOCK') $st = 'AVAILABLE';
                                $isRestocked = !empty($pt['meta']['is_restocked']);
                                $isExchanged = !empty($pt['meta']['is_exchange_dispatched']) || !empty($pt['is_exchanged']) || $st === 'EXCHANGED';
                                $isWarehouseReturned = !empty($pt['meta']['is_warehouse_returned']) || !empty($pt['is_returned']) || ($st === 'RETURNED') || ($st === 'RETURNED_TO_WAREHOUSE');
                                $isDamaged = !empty($pt['is_damaged']) || ($st === 'DAMAGED');
                                $isPartialDamage = !empty($pt['is_partial_damage']) || $st === 'PARTIALLY DAMAGED';
                                $isSold = !empty($pt['is_sold']) || ($st === 'SOLD');
                                $isUnavailable = $st === 'OUT OF STOCK';
                                
                                $cardBorder = match(true) {
                                    $isRestocked => 'border-success shadow',
                                    $isExchanged => 'border-primary',
                                    $isWarehouseReturned => 'border-secondary-subtle',
                                    $isPartialDamage     => 'border-warning',
                                    $isDamaged           => 'border-danger',
                                    $isSold              => 'border-secondary-subtle',
                                    $isUnavailable       => 'border-secondary',
                                    $st === 'AVAILABLE'  => 'border-success',
                                    $st === 'IN_TRANSIT' => 'border-warning',
                                    default              => 'border-secondary'
                                };
                                
                                $stBadge = match(true) {
                                    $isRestocked => '<span class="badge bg-success text-white fw-bold shadow-sm"><i class="bi bi-arrow-repeat me-1"></i>RESTOCKED (RETURN)</span>',
                                    $isExchanged => '<span class="badge text-white fw-bold shadow-sm" style="background-color: #7c3aed;"><i class="bi bi-arrow-left-right me-1"></i>EXCHANGED (OUT)</span>',
                                    $isWarehouseReturned => '<span class="badge bg-secondary text-white fw-bold shadow-sm"><i class="bi bi-box-arrow-right me-1"></i>RETURNED (WAREHOUSE)</span>',
                                    $isPartialDamage     => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning fw-bold"><i class="bi bi-exclamation-circle me-1"></i>PARTIALLY DAMAGED</span>',
                                    $isDamaged   => '<span class="badge bg-danger text-white fw-bold shadow-sm"><i class="bi bi-shield-x me-1"></i>DAMAGED (QUARANTINED)</span>',
                                    $isSold      => '<span class="badge bg-secondary text-white fw-bold shadow-sm"><i class="bi bi-cart-check-fill me-1"></i>SOLD IN INVOICE</span>',
                                    $isUnavailable => '<span class="badge bg-secondary text-white fw-bold shadow-sm"><i class="bi bi-box me-1"></i>OUT OF STOCK</span>',
                                    $st === 'AVAILABLE'  => '<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><i class="bi bi-check-circle me-1"></i>AVAILABLE IN STOCK</span>',
                                    $st === 'IN_TRANSIT' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold"><i class="bi bi-truck me-1"></i>IN-TRANSIT</span>',
                                    default              => '<span class="badge bg-light text-dark border">' . htmlspecialchars($st) . '</span>'
                                };
                                
                                $cardClasses = ['col-md-6', 'col-xl-4', 'piece-item-card'];
                                if ($isSold && !$isExchanged) $cardClasses[] = 'is-sold';
                                if ($isExchanged) $cardClasses[] = 'is-exchanged';
                                if ($isRestocked) $cardClasses[] = 'is-restocked';
                                if ($isWarehouseReturned) $cardClasses[] = 'is-returned';
                                
                                $filterType = $st;
                                if ($isDamaged) $filterType = 'DAMAGED';
                                if ($isSold) $filterType = 'SOLD';
                                if ($isRestocked) $filterType .= ' RESTOCKED';
                                if ($isExchanged) $filterType .= ' EXCHANGED';
                                if ($isWarehouseReturned) $filterType .= ' RETURNED';

                                $pieceNo = $pt['piece_number'] ?? ($pt['piece_no'] ?? ($idx + 1));
                                $qrPayloadStr = $pt['tag_code'];
                            ?>
                                <div class="<?= implode(' ', $cardClasses) ?>" data-status="<?= htmlspecialchars($filterType) ?>" data-tag="<?= htmlspecialchars(strtolower($pt['tag_code'])) ?>" data-search="<?= htmlspecialchars(strtolower($pt['tag_code'] . ' ' . $qrPayloadStr . ' ' . ($pt['inward_reference'] ?? '') . ' ' . ($pt['invoice_number'] ?? '') . ' ' . ($pt['damage_number'] ?? ''))) ?>">
                                    <div class="card h-100 rounded-4 shadow-sm border <?= $cardBorder ?> bg-white overflow-hidden position-relative">
                                        <div class="card-header bg-light bg-opacity-50 d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                                            <span class="badge <?= $isExchanged ? 'text-white' : ($isWarehouseReturned ? 'bg-secondary' : ($isSold ? 'bg-secondary' : 'bg-dark')) ?> font-monospace" style="<?= $isExchanged ? 'background-color: #6b21a8;' : '' ?>">Piece #<?= $pieceNo ?></span>
                                            <?= $stBadge ?>
                                        </div>
                                        <div class="card-body p-2">
                                            <div class="p-2 border rounded-3 bg-white">
                                                <div class="row g-2 align-items-start">
                                                    <div class="col-4 text-center position-relative">
                                                        <div class="p-1 bg-white border rounded shadow-sm d-inline-block position-relative">
                                                            <div id="pieceQr_<?= $pieceNo ?>" class="piece-qr-canvas" data-payload="<?= htmlspecialchars($qrPayloadStr) ?>"></div>
                                                            <?php if ($isExchanged): ?>
                                                                <span class="exchanged-watermark-overlay">SWAPPED</span>
                                                            <?php elseif ($isWarehouseReturned): ?>
                                                                <span class="returned-watermark-overlay">RETURNED</span>
                                                            <?php elseif ($isSold): ?>
                                                                <span class="sold-watermark-overlay">SOLD</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="badge bg-dark text-white font-monospace mt-1 px-1 py-1 w-100 text-break" style="font-size: 10px;">
                                                            <?= htmlspecialchars($pt['tag_code']) ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-8 overflow-hidden">
                                                        <div class="fw-bold text-uppercase text-dark text-truncate small">
                                                            <?= htmlspecialchars($pt['brand_name'] ?? $item['brand_name'] ?? $item['product_name']) ?>
                                                        </div>
                                                        <?php
                                                            $categoryHierarchy = array_filter([
                                                                $pt['category_name'] ?? $item['category_name'] ?? '',
                                                                $pt['subcategory_name'] ?? $item['subcategory_name'] ?? '',
                                                                $pt['sub_subcategory_name'] ?? $item['sub_subcategory_name'] ?? '',
                                                            ]);
                                                        ?>
                                                        <?php if ($categoryHierarchy): ?>
                                                            <div class="small text-primary text-truncate mt-1" title="<?= htmlspecialchars(implode(' › ', $categoryHierarchy)) ?>">
                                                                <?= htmlspecialchars(implode(' › ', $categoryHierarchy)) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="d-flex gap-1 mt-1">
                                                            <span class="badge bg-dark text-white"><?= htmlspecialchars($pt['size_name'] ?? 'Free') ?></span>
                                                            <span class="badge bg-secondary text-white"><?= htmlspecialchars($pt['color_name'] ?? 'Std') ?></span>
                                                        </div>
                                                        <?php if ($mrp > $sellingPrice): ?>
                                                            <div class="small text-muted text-decoration-line-through mt-1">MRP: ₹<?= number_format($mrp, 2) ?></div>
                                                            <div class="small fw-bold text-success">Discount Price: ₹<?= number_format($sellingPrice, 2) ?></div>
                                                        <?php else: ?>
                                                            <div class="small fw-bold text-success mt-1">Discount Price: ₹<?= number_format($sellingPrice > 0 ? $sellingPrice : $mrp, 2) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <small class="text-muted text-truncate" style="font-size: 10px;" title="<?= htmlspecialchars($pt['inward_reference'] ?? $item['latest_allocation_number'] ?? 'Inward GRN') ?>">
                                                    <i class="bi bi-box-seam me-1"></i><?= htmlspecialchars($pt['inward_reference'] ?? $item['latest_allocation_number'] ?? 'Inward GRN') ?>
                                                </small>
                                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1" title="Copy Tag Code" onclick="copyTagCode('<?= htmlspecialchars($pt['tag_code']) ?>')">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>

                                            <!-- Status Specific Metadata & Actions -->
                                            <div class="mt-3 pt-2 border-top">
                                                <?php if ($isRestocked): ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-success fw-bold text-truncate" style="max-width: 65%;">
                                                            <i class="bi bi-arrow-repeat me-1"></i>Restocked: <?= htmlspecialchars($pt['meta']['return_number'] ?? 'Return') ?>
                                                        </small>
                                                        <div class="d-flex gap-1">
                                                            <button type="button" class="btn btn-xs btn-outline-primary px-2 py-1" title="Print Tag Sticker" onclick="printSinglePieceTag(<?= htmlspecialchars(json_encode($pt)) ?>)">
                                                                <i class="bi bi-printer"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php elseif ($isExchanged): ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="fw-semibold text-truncate" style="color: #6b21a8; max-width: 65%;">
                                                            <i class="bi bi-arrow-left-right me-1"></i>Exchange: <?= htmlspecialchars($pt['meta']['exchange_number'] ?? 'Customer Exchange') ?>
                                                        </small>
                                                        <a href="<?= url('shop/returns?tab=exchanges') ?>" class="btn btn-xs btn-outline-primary px-2 py-1 shadow-sm" style="border-color: #a855f7; color: #7c3aed;">
                                                            <i class="bi bi-box-arrow-up-right me-1"></i> View Slip
                                                        </a>
                                                    </div>
                                                <?php elseif ($isWarehouseReturned): ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-secondary fw-semibold text-truncate" style="max-width: 65%;">
                                                            <i class="bi bi-box-arrow-right me-1"></i>Return: <?= htmlspecialchars($pt['meta']['return_number'] ?? 'Warehouse Return') ?>
                                                        </small>
                                                        <a href="<?= url('shop/return-to-warehouse') ?>" class="btn btn-xs btn-outline-secondary px-2 py-1 shadow-sm">
                                                            <i class="bi bi-truck me-1"></i> Dispatched
                                                        </a>
                                                    </div>
                                                <?php elseif ($isDamaged): ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="small text-danger text-truncate" style="max-width: 65%;">
                                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                            <?= htmlspecialchars($pt['damage_number'] ?? $pt['damage_reason'] ?? 'Damaged & Quarantined') ?>
                                                        </div>
                                                        <a href="<?= url('shop/damage') ?>" class="btn btn-xs btn-outline-danger px-2 py-1">
                                                            <i class="bi bi-eye me-1"></i> View Damage
                                                        </a>
                                                    </div>
                                                <?php elseif ($isSold): ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-primary fw-semibold text-truncate" style="max-width: 65%;">
                                                            <i class="bi bi-receipt text-primary me-1"></i>Billed: <?= htmlspecialchars($pt['invoice_number'] ?? 'POS Invoice') ?>
                                                        </small>
                                                        <?php if (!empty($pt['sale_id'])): ?>
                                                            <a href="<?= url('shop/pos/invoice/' . $pt['sale_id']) ?>" class="btn btn-xs btn-outline-primary px-2 py-1 shadow-sm" target="_blank">
                                                                <i class="bi bi-box-arrow-up-right me-1"></i> View Invoice
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="<?= url('shop/pos/history') ?>" class="btn btn-xs btn-outline-secondary px-2 py-1 shadow-sm" target="_blank">
                                                                <i class="bi bi-receipt me-1"></i> POS History
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($st === 'AVAILABLE'): ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-success fw-semibold"><i class="bi bi-check2-all me-1"></i>Ready for Shop POS Billing</small>
                                                        <div class="d-flex gap-1">
                                                            <a href="<?= url('shop/damage/create?variant_id=' . $item['variant_id'] . '&piece_tag=' . urlencode($pt['tag_code'])) ?>" class="btn btn-xs btn-outline-danger px-2 py-1" title="Quarantine as Damaged">
                                                                <i class="bi bi-shield-x"></i> Report Damage
                                                            </a>
                                                            <button type="button" class="btn btn-xs btn-outline-primary px-2 py-1" title="Print Tag Sticker" onclick="printSinglePieceTag(<?= htmlspecialchars(json_encode($pt)) ?>)">
                                                                <i class="bi bi-printer"></i>
                                                            </button>
                                                        </div>
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
                                    <?php foreach ($pieceTags as $idx => $pt): 
                                        $st = strtoupper($pt['status'] ?? 'AVAILABLE');
                                        if ($st === 'IN STOCK') $st = 'AVAILABLE';
                                        $isRestocked = !empty($pt['meta']['is_restocked']);
                                        $isExchanged = !empty($pt['meta']['is_exchange_dispatched']) || !empty($pt['is_exchanged']) || $st === 'EXCHANGED';
                                        $isWarehouseReturned = !empty($pt['meta']['is_warehouse_returned']) || !empty($pt['is_returned']) || ($st === 'RETURNED') || ($st === 'RETURNED_TO_WAREHOUSE');
                                        $isDamaged = !empty($pt['is_damaged']) || ($st === 'DAMAGED');
                                        $isSold = !empty($pt['is_sold']) || ($st === 'SOLD');
                                        
                                        $stBadge = match(true) {
                                            $isRestocked => '<span class="badge bg-success text-white">RESTOCKED (RETURN)</span>',
                                            $isExchanged => '<span class="badge text-white" style="background-color: #7c3aed;">EXCHANGED (OUT)</span>',
                                            $isWarehouseReturned => '<span class="badge bg-secondary text-white">RETURNED (WAREHOUSE)</span>',
                                            $isDamaged   => '<span class="badge bg-danger text-white">DAMAGED</span>',
                                            $isSold      => '<span class="badge bg-secondary text-white">SOLD OUT</span>',
                                            $st === 'AVAILABLE'  => '<span class="badge bg-success text-white">AVAILABLE</span>',
                                            $st === 'IN_TRANSIT' => '<span class="badge bg-warning text-dark">IN-TRANSIT</span>',
                                            default              => '<span class="badge bg-secondary">' . htmlspecialchars($st) . '</span>'
                                        };
                                        
                                        $rowClasses = ['piece-table-row'];
                                        if ($isSold && !$isExchanged) $rowClasses[] = 'is-sold';
                                        if ($isExchanged) $rowClasses[] = 'is-exchanged';
                                        if ($isRestocked) $rowClasses[] = 'is-restocked';
                                        
                                        $filterType = $st;
                                        if ($isDamaged) $filterType = 'DAMAGED';
                                        if ($isSold) $filterType = 'SOLD';
                                        if ($isRestocked) $filterType .= ' RESTOCKED';
                                        if ($isExchanged) $filterType .= ' EXCHANGED';

                                        $pieceNo = $pt['piece_number'] ?? ($pt['piece_no'] ?? ($idx + 1));
                                                $qrPayloadStr = $pt['tag_code'];
                                    ?>
                                        <tr class="<?= implode(' ', $rowClasses) ?>" data-status="<?= htmlspecialchars($filterType) ?>" data-tag="<?= htmlspecialchars(strtolower($pt['tag_code'])) ?>" data-search="<?= htmlspecialchars(strtolower($pt['tag_code'] . ' ' . $qrPayloadStr . ' ' . ($pt['inward_reference'] ?? '') . ' ' . ($pt['invoice_number'] ?? '') . ' ' . ($pt['damage_number'] ?? ''))) ?>">
                                            <td class="text-muted small fw-semibold"><?= $pieceNo ?></td>
                                            <td>
                                                <div id="pieceTableQr_<?= $pieceNo ?>" class="piece-table-qr-canvas" data-payload="<?= htmlspecialchars($qrPayloadStr) ?>"></div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge <?= $isExchanged ? 'bg-light text-primary' : ($isSold ? 'sold-tag-code' : 'bg-light text-primary') ?> font-monospace fs-6 border" style="<?= $isExchanged ? 'color: #7c3aed !important; border-color: #d8b4fe !important;' : '' ?>"><?= htmlspecialchars($pt['tag_code']) ?></span>
                                                    <button type="button" class="btn btn-xs btn-link text-muted p-0" title="Copy Tag" onclick="copyTagCode('<?= htmlspecialchars($pt['tag_code']) ?>')">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="small font-monospace text-muted text-truncate" style="max-width: 200px;">
                                                <?= htmlspecialchars($qrPayloadStr) ?>
                                            </td>
                                            <td class="small">
                                                <div class="fw-semibold <?= ($isSold && !$isExchanged) ? 'text-muted' : 'text-dark' ?>"><?= htmlspecialchars($pt['inward_reference'] ?? $item['latest_allocation_number'] ?? 'Inward GRN') ?></div>
                                                <small class="text-muted"><?= date('d M Y') ?></small>
                                            </td>
                                            <td class="text-center">
                                                <?= $stBadge ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?php if ($isRestocked): ?>
                                                    <span class="text-success fw-bold"><i class="bi bi-arrow-repeat me-1"></i>Restocked via <?= htmlspecialchars($pt['meta']['return_number'] ?? 'Return') ?></span>
                                                <?php elseif ($isExchanged): ?>
                                                    <span class="fw-semibold" style="color: #7c3aed;"><i class="bi bi-arrow-left-right me-1"></i>Dispatched in Exchange</span>
                                                <?php elseif ($isDamaged): ?>
                                                    <span class="text-danger"><i class="bi bi-shield-x me-1"></i><?= htmlspecialchars($pt['damage_number'] ?? 'Defect Quarantined') ?></span>
                                                <?php elseif ($isSold): ?>
                                                    <span class="text-primary"><i class="bi bi-receipt me-1"></i><?= htmlspecialchars($pt['invoice_number'] ?? 'POS Invoice') ?> (Billed)</span>
                                                <?php elseif ($st === 'AVAILABLE'): ?>
                                                    <span class="text-success"><i class="bi bi-check2 me-1"></i>In Shop Stock</span>
                                                <?php else: ?>
                                                    <span><?= htmlspecialchars($pt['inward_reference'] ?? 'Stock') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex gap-1 justify-content-end">
                                                    <button type="button" class="btn btn-xs btn-outline-primary px-2 py-1" title="Print Tag Label" onclick="printSinglePieceTag(<?= htmlspecialchars(json_encode($pt)) ?>)">
                                                        <i class="bi bi-printer"></i>
                                                    </button>
                                                    <?php if ($isDamaged): ?>
                                                        <a href="<?= url('shop/damage') ?>" class="btn btn-xs btn-outline-danger px-2 py-1" title="View Damage">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    <?php elseif ($isSold && !empty($pt['sale_id'])): ?>
                                                        <a href="<?= url('shop/pos/invoice/' . $pt['sale_id']) ?>" class="btn btn-xs btn-outline-primary px-2 py-1" title="View POS Invoice" target="_blank">
                                                            <i class="bi bi-receipt"></i>
                                                        </a>
                                                    <?php elseif ($st === 'AVAILABLE'): ?>
                                                        <a href="<?= url('shop/damage/create?variant_id=' . $item['variant_id'] . '&piece_tag=' . urlencode($pt['tag_code'])) ?>" class="btn btn-xs btn-outline-danger px-2 py-1" title="Report Damage">
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
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-text me-2 text-primary"></i>Shop Stock Ledger Audit Trail</h6>
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
                                    <th>Balance After</th>
                                    <th>Logged By</th>
                                    <th>Notes / Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No stock movement transactions recorded yet for this variant.</td>
                                    </tr>
                                <?php else: foreach ($transactions as $txn): 
                                    $qtyVal = (float)$txn['quantity'];
                                    $isPositive = in_array(strtoupper($txn['transaction_type'] ?? ''), ['TRANSFER_IN', 'INWARD_RECEIVE', 'ADJUSTMENT_ADD', 'RETURN_CUSTOMER', 'RETURN_REINWARD', 'INWARD_ALLOCATION']) || $qtyVal > 0;
                                ?>
                                    <tr>
                                        <td class="small">
                                            <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($txn['created_at'])) ?></div>
                                            <small class="text-muted"><?= date('h:i A', strtotime($txn['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= in_array(strtoupper($txn['transaction_type'] ?? ''), ['RETURN_REINWARD', 'RETURN_CUSTOMER']) ? 'success' : (in_array(strtoupper($txn['transaction_type'] ?? ''), ['EXCHANGE_DISPATCH']) ? 'primary' : 'light text-dark') ?> border font-monospace">
                                                <?= htmlspecialchars($txn['transaction_type'] ?? 'MOVEMENT') ?>
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
                                        <td class="small fw-bold text-dark">
                                            <?= number_format((float)($txn['balance_after'] ?? 0)) ?> pcs
                                        </td>
                                        <td class="small text-muted">
                                            <?= htmlspecialchars($txn['user_name'] ?? 'System') ?>
                                        </td>
                                        <td class="small text-muted" style="max-width: 320px;">
                                            <?= htmlspecialchars($txn['notes'] ?? $txn['reference_type'] ?? '-') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- TAB 3: WAREHOUSE INWARDS -->
                <!-- ========================================================================= -->
                <div class="tab-pane fade" id="tab-inward" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-truck me-2 text-primary"></i>Warehouse Inward Stock Allocations (GRN)</h6>
                        <span class="badge bg-primary-subtle text-primary"><?= count($inwardHistory) ?> Inward Passes</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Allocation GRN #</th>
                                    <th>Origin Warehouse</th>
                                    <th>Dispatch Info</th>
                                    <th class="text-center">Requested</th>
                                    <th class="text-center">Received Qty</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inwardHistory)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No inward allocation batches recorded yet for this item.</td>
                                    </tr>
                                <?php else: foreach ($inwardHistory as $inw): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold font-monospace text-primary"><?= htmlspecialchars($inw['allocation_number']) ?></div>
                                            <small class="text-muted"><?= date('d M Y, h:i A', strtotime($inw['alloc_date'] ?? 'now')) ?></small>
                                        </td>
                                        <td class="small">
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($inw['warehouse_name'] ?? 'Central Warehouse') ?></div>
                                            <span class="badge bg-light text-secondary border font-monospace"><?= htmlspecialchars($inw['warehouse_code'] ?? 'CWH-01') ?></span>
                                        </td>
                                        <td class="small text-muted">
                                            <div>Outward: <span class="font-monospace text-dark fw-semibold"><?= htmlspecialchars($inw['outward_number'] ?? 'DIRECT') ?></span></div>
                                            <div>Transporter: <?= htmlspecialchars($inw['transporter_name'] ?? 'Company Fleet') ?> (<?= htmlspecialchars($inw['vehicle_number'] ?? '-') ?>)</div>
                                        </td>
                                        <td class="text-center fw-semibold text-muted">
                                            <?= number_format((float)($inw['requested_qty'] ?? $inw['quantity'] ?? 0)) ?> pcs
                                        </td>
                                        <td class="text-center fw-bold text-success fs-6">
                                            <?= number_format((float)($inw['received_qty'] ?? $inw['quantity'] ?? 0)) ?> pcs
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1"><?= htmlspecialchars($inw['alloc_status'] ?? 'COMPLETED') ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('shop/inward/view/' . ($inw['allocation_id'] ?? $inw['id'])) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" target="_blank">
                                                <i class="bi bi-eye me-1"></i> View GRN
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- TAB 4: SHOP POS SALES ORDERS -->
                <!-- ========================================================================= -->
                <div class="tab-pane fade" id="tab-sales" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-receipt me-2 text-primary"></i>Shop POS Billed Retail Invoices</h6>
                        <span class="badge bg-success-subtle text-success"><?= count($salesHistory) ?> Invoices</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice # &amp; Date</th>
                                    <th>Customer Details</th>
                                    <th class="text-center">Billed Qty</th>
                                    <th class="text-end">Rate &amp; Discount</th>
                                    <th class="text-end">Net Total</th>
                                    <th class="text-center">Payment Mode</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($salesHistory)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No POS retail invoices billed yet for this product variant.</td>
                                    </tr>
                                <?php else: foreach ($salesHistory as $sale): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold font-monospace text-primary"><?= htmlspecialchars($sale['invoice_number']) ?></div>
                                            <small class="text-muted"><?= date('d M Y, h:i A', strtotime($sale['sale_date'] ?? 'now')) ?></small>
                                        </td>
                                        <td class="small">
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($sale['customer_name'] ?? 'Retail Customer') ?></div>
                                            <small class="text-muted font-monospace"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($sale['customer_phone'] ?? '-') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill px-2.5 py-1 font-monospace fs-6"><?= number_format((float)$sale['quantity']) ?></span>
                                        </td>
                                        <td class="text-end small">
                                            <div class="fw-bold text-dark">₹<?= number_format((float)$sale['unit_price'], 2) ?></div>
                                            <?php if ((float)($sale['discount_amount'] ?? 0) > 0): ?>
                                                <small class="text-danger">-₹<?= number_format((float)$sale['discount_amount'], 2) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold text-success fs-6">
                                            ₹<?= number_format((float)($sale['total_price'] ?? ($sale['unit_price'] * $sale['quantity'])), 2) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border text-uppercase font-monospace"><?= htmlspecialchars($sale['payment_mode'] ?? 'CASH') ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('shop/pos/invoice/' . ($sale['sale_id'] ?? $sale['id'])) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" target="_blank">
                                                <i class="bi bi-receipt me-1"></i> Invoice
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- TAB 5: RETURNS & EXCHANGES -->
                <!-- ========================================================================= -->
                <div class="tab-pane fade" id="tab-returns" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-arrow-left-right me-2 text-info"></i>Customer Returns &amp; Swaps History</h6>
                        <span class="badge bg-info-subtle text-info-emphasis"><?= count($returnsHistory) + count($exchangesHistory) ?> Records</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Return / Exchange #</th>
                                    <th>Customer</th>
                                    <th>Original Sale Invoice</th>
                                    <th class="text-center">Quantity</th>
                                    <th>Reason / Settlement</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $allRetExc = array_merge($returnsHistory, $exchangesHistory);
                                if (empty($allRetExc)): 
                                ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No returns or exchanges registered for this product variant.</td>
                                    </tr>
                                <?php else: foreach ($allRetExc as $re): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold font-monospace text-dark"><?= htmlspecialchars($re['return_number'] ?? $re['exchange_number'] ?? 'RET-001') ?></div>
                                            <small class="text-muted"><?= date('d M Y', strtotime($re['return_date'] ?? $re['exchange_date'] ?? 'now')) ?></small>
                                        </td>
                                        <td class="small">
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($re['customer_name'] ?? 'Customer') ?></div>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($re['customer_phone'] ?? '-') ?></small>
                                        </td>
                                        <td class="small font-monospace text-primary">
                                            <?= htmlspecialchars($re['sale_invoice'] ?? $re['invoice_number'] ?? '-') ?>
                                        </td>
                                        <td class="text-center fw-bold">
                                            <?= number_format((float)($re['quantity'] ?? 1)) ?> pcs
                                        </td>
                                        <td class="small text-muted">
                                            <?= htmlspecialchars($re['return_reason'] ?? $re['settlement_mode'] ?? 'Customer preference return') ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-3 py-1"><?= htmlspecialchars($re['return_status'] ?? $re['exchange_status'] ?? 'COMPLETED') ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- TAB 6: DAMAGE & QUARANTINE LOG -->
                <!-- ========================================================================= -->
                <div class="tab-pane fade" id="tab-damage" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-x me-2 text-danger"></i>Quarantined Defect &amp; Damage Register</h6>
                        <a href="<?= url('shop/damage/create?variant_id=' . $item['variant_id']) ?>" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm">
                            <i class="bi bi-plus-circle me-1"></i> Report New Damage
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Damage # &amp; Date</th>
                                    <th>Quarantined Piece Tag</th>
                                    <th>Defect Classification</th>
                                    <th class="text-center">Quarantined Qty</th>
                                    <th class="text-end">Estimated Loss</th>
                                    <th>Reported By</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($damageHistory)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No damage or defect quarantine records for this product variant.</td>
                                    </tr>
                                <?php else: foreach ($damageHistory as $dmg): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold font-monospace text-danger"><?= htmlspecialchars($dmg['damage_number']) ?></div>
                                            <small class="text-muted"><?= date('d M Y, h:i A', strtotime($dmg['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark font-monospace text-warning fs-6"><?= htmlspecialchars($dmg['piece_tag'] ?? $dmg['scanned_qr_codes'] ?? 'N/A') ?></span>
                                        </td>
                                        <td class="small">
                                            <span class="badge bg-danger-subtle text-danger border font-monospace"><?= htmlspecialchars($dmg['defect_type'] ?? 'STORE_DAMAGE') ?></span>
                                            <small class="text-muted d-block mt-1"><?= htmlspecialchars($dmg['damage_reason'] ?? '-') ?></small>
                                        </td>
                                        <td class="text-center fw-bold text-danger fs-6">
                                            <?= number_format((float)$dmg['quantity']) ?> pcs
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            ₹<?= number_format((float)($dmg['total_loss'] ?? ($dmg['quantity'] * $sellingPrice)), 2) ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= htmlspecialchars($dmg['reported_by_name'] ?? 'Shop Executive') ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger text-white rounded-pill px-3 py-1"><?= htmlspecialchars($dmg['status'] ?? 'QUARANTINED') ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- TAB 7: WAREHOUSE OUTWARD RETURNS -->
                <!-- ========================================================================= -->
                <div class="tab-pane fade" id="tab-wh-returns" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-arrow-right me-2 text-secondary"></i>Outward Consignments Returned to Central Warehouse</h6>
                        <a href="<?= url('shop/return-to-warehouse/create') ?>" class="btn btn-sm btn-warning rounded-pill px-3 shadow-sm text-dark fw-semibold">
                            <i class="bi bi-truck me-1"></i> Dispatch RTV Consignment
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Return Consignment #</th>
                                    <th>Destination Warehouse</th>
                                    <th>Classification</th>
                                    <th class="text-center">Dispatched Qty</th>
                                    <th>Reason / Notes</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($warehouseReturnsHistory)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No outward return consignments dispatched to warehouse yet.</td>
                                    </tr>
                                <?php else: foreach ($warehouseReturnsHistory as $wrItem): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold font-monospace text-dark"><?= htmlspecialchars($wrItem['return_number']) ?></div>
                                            <small class="text-muted"><?= date('d M Y', strtotime($wrItem['created_at'])) ?></small>
                                        </td>
                                        <td class="small">
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($wrItem['destination_warehouse_name'] ?? 'Central Warehouse') ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($wrItem['classification'] ?? 'GOOD') ?></span>
                                        </td>
                                        <td class="text-center fw-bold text-dark fs-6">
                                            <?= number_format((float)($wrItem['returned_qty'] ?? 1)) ?> pcs
                                        </td>
                                        <td class="small text-muted">
                                            <?= htmlspecialchars($wrItem['reason'] ?? $wrItem['notes'] ?? '-') ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1"><?= htmlspecialchars($wrItem['status'] ?? 'DISPATCHED') ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT PRODUCT SELLING PRICE & MRP -->
<!-- ========================================================================= -->
<div class="modal fade" id="editPriceModal" tabindex="-1" aria-labelledby="editPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form id="editPriceForm" onsubmit="handlePriceFormSubmit(event)">
                <input type="hidden" name="variant_id" id="editPriceVariantId" value="<?= (int)$item['variant_id'] ?>">
                <input type="hidden" id="editPriceCostVal" value="<?= (float)$cost ?>">

                <div class="modal-header bg-success text-white py-3 px-4 border-0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-tag-fill fs-5"></i>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="editPriceModalLabel">Edit Product Price</h5>
                            <small class="text-white-50 font-monospace" id="editPriceModalSku"><?= htmlspecialchars($item['sku']) ?></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <!-- Product Title Banner -->
                    <div class="p-3 bg-white rounded-3 border shadow-2xs mb-3">
                        <h6 class="fw-bold text-dark mb-1" id="editPriceModalProductName"><?= htmlspecialchars($item['product_name']) ?></h6>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-dark font-monospace text-white px-2 py-1" id="editPriceBadgeSku"><?= htmlspecialchars($item['sku']) ?></span>
                            <span class="badge bg-light text-muted border" id="editPriceCostBadge">Cost: ₹<?= number_format($cost, 2) ?></span>
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
                                       value="<?= number_format($sellingPrice, 2, '.', '') ?>" 
                                       required
                                       oninput="calculatePriceAnalytics()">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Active retail selling rate at POS</small>
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
                                       value="<?= number_format($mrp > 0 ? $mrp : $sellingPrice, 2, '.', '') ?>" 
                                       oninput="calculatePriceAnalytics()">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Printed sticker / catalog MRP</small>
                        </div>
                    </div>

                    <!-- Live Price Analytics Card -->
                    <div class="p-3 bg-white rounded-3 border shadow-2xs mb-3" id="priceAnalyticsCard">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted fw-semibold text-uppercase" style="font-size: 10px;">Price Dynamics &amp; Margin</small>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace" id="badgeDiscountPreview"><?= $discount > 0 ? $discount . '% Discount' : 'Standard Price' ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small">
                                <span class="text-muted">Markup over Cost: </span>
                                <strong class="text-dark" id="txtMarkupPreview">—</strong>
                            </div>
                            <div class="small">
                                <span class="text-muted">Discount on MRP: </span>
                                <strong class="text-primary" id="txtDiscountPreview"><?= $discount ?>%</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Reason / Revision Notes -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-muted text-uppercase mb-1">Revision Notes / Reason (Optional)</label>
                        <input type="text" name="notes" id="inputPriceNotes" class="form-control" placeholder="e.g. Retail festive promotion, Season discount">
                    </div>
                </div>

                <div class="modal-footer bg-white border-0 py-3 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm" id="btnSavePrice">
                        <i class="bi bi-check-circle me-1"></i> Save Price Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PRINT PIECE QR LABELS -->
<!-- ========================================================================= -->
<div class="modal fade" id="printPieceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-printer-fill fs-5"></i>
                    <h5 class="modal-title fw-bold mb-0">Print Itemized Piece QR Stickers</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light" id="printModalBody">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">Select labels to include in print sheet:</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3" onclick="selectAllPrintPieces(true)">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="selectAllPrintPieces(false)">Deselect All</button>
                    </div>
                </div>
                <div class="row g-3" id="printPieceStickersContainer">
                    <!-- Populated dynamically by JS -->
                </div>
            </div>
            <div class="modal-footer bg-white py-3 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="triggerStickerPrint()">
                    <i class="bi bi-printer me-1"></i> Print Selected Stickers
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Indent Stock Modal -->
<div class="modal fade" id="requestStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" action="<?= url('shop/inventory/request') ?>">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-box-arrow-in-down text-primary me-2"></i>Indent Stock from Central Warehouse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Submit a stock indent for <strong><?= htmlspecialchars($item['product_name']) ?> (<?= htmlspecialchars($item['sku']) ?>)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity to Request (pcs)</label>
                        <input type="number" name="items[<?= (int)$item['variant_id'] ?>]" class="form-control" value="10" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Indent Request Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Weekend retail replenishment">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT: INSTANT CLIENT-SIDE LOGIC, QR RENDERING & PRICE ENGINE -->
<!-- ========================================================================= -->
<script>
const allPieceTagsData = <?= json_encode($pieceTags) ?>;
let currentEditPriceModal = null;
let currentPrintModal = null;

document.addEventListener('DOMContentLoaded', function() {
    // Render individual piece QR codes for card and table views.
    renderAllPieceQrCanvases();
});

// Render dynamic QR canvases
function renderAllPieceQrCanvases() {
    if (typeof QRCode === 'undefined') return;

    // Render Cards QR Canvases
    document.querySelectorAll('.piece-qr-canvas').forEach(el => {
        const payload = el.getAttribute('data-payload');
        if (payload && !el.hasChildNodes()) {
            try {
                new QRCode(el, {
                    text: payload,
                    width: 52,
                    height: 52,
                    colorDark: "#0f172a",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
            } catch(e) {
                console.error(e);
            }
        }
    });

    // Render Table QR Canvases
    document.querySelectorAll('.piece-table-qr-canvas').forEach(el => {
        const payload = el.getAttribute('data-payload');
        if (payload && !el.hasChildNodes()) {
            try {
                new QRCode(el, {
                    text: payload,
                    width: 36,
                    height: 36,
                    colorDark: "#0f172a",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
            } catch(e) {
                console.error(e);
            }
        }
    });
}

// Switch between Cards and Table View
window.switchPieceView = function(mode) {
    const cards = document.getElementById('pieceCardsContainer');
    const table = document.getElementById('pieceTableContainer');
    const btnCards = document.getElementById('btnViewCards');
    const btnTable = document.getElementById('btnViewTable');

    if (mode === 'table') {
        if (cards) cards.classList.add('d-none');
        if (table) table.classList.remove('d-none');
        if (btnCards) btnCards.classList.remove('active');
        if (btnTable) btnTable.classList.add('active');
        renderAllPieceQrCanvases();
    } else {
        if (table) table.classList.add('d-none');
        if (cards) cards.classList.remove('d-none');
        if (btnTable) btnTable.classList.remove('active');
        if (btnCards) btnCards.classList.add('active');
        renderAllPieceQrCanvases();
    }
};

// Filter piece tags by status
window.filterPieceTags = function(filter, btn) {
    document.querySelectorAll('.piece-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const searchVal = (document.getElementById('pieceTagSearchInput')?.value || '').trim().toLowerCase();
    applyPieceFilterLogic(filter, searchVal);
};

// Search piece tags by text
window.searchPieceTags = function() {
    const searchVal = (document.getElementById('pieceTagSearchInput')?.value || '').trim().toLowerCase();
    const activeBtn = document.querySelector('.piece-filter-btn.active');
    const activeFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
    applyPieceFilterLogic(activeFilter, searchVal);
};

window.clearPieceSearch = function() {
    const input = document.getElementById('pieceTagSearchInput');
    if (input) input.value = '';
    const activeBtn = document.querySelector('.piece-filter-btn.active');
    const activeFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
    applyPieceFilterLogic(activeFilter, '');
};

function applyPieceFilterLogic(filter, search) {
    const filterUpper = filter.toUpperCase();

    // Filter Cards
    document.querySelectorAll('.piece-item-card').forEach(card => {
        const cardStatus = (card.getAttribute('data-status') || '').toUpperCase();
        const cardSearch = (card.getAttribute('data-search') || '').toLowerCase();

        const matchFilter = (filterUpper === 'ALL') || cardStatus.includes(filterUpper);
        const matchSearch = (!search) || cardSearch.includes(search);

        card.style.display = (matchFilter && matchSearch) ? '' : 'none';
    });

    // Filter Table Rows
    document.querySelectorAll('.piece-table-row').forEach(row => {
        const rowStatus = (row.getAttribute('data-status') || '').toUpperCase();
        const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();

        const matchFilter = (filterUpper === 'ALL') || rowStatus.includes(filterUpper);
        const matchSearch = (!search) || rowSearch.includes(search);

        row.style.display = (matchFilter && matchSearch) ? '' : 'none';
    });
}

// Copy Tag Code
window.copyTagCode = function(code) {
    if (!code) return;
    navigator.clipboard.writeText(code).then(() => {
        alert('Copied Tag Code: ' + code);
    }).catch(() => {
        alert('Copied Tag Code: ' + code);
    });
};

// =========================================================================
// PRICE EDIT ENGINE & AJAX SUBMIT
// =========================================================================
window.openEditPriceModal = function(variantId, sku, productName, sellingPrice, mrp, costPrice = 0) {
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
            document.getElementById('inputSellingPrice').focus();
            document.getElementById('inputSellingPrice').select();
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
    const form = document.getElementById('editPriceForm');
    const btn = document.getElementById('btnSavePrice');
    const alertBox = document.getElementById('editPriceAlertContainer');
    const variantId = document.getElementById('editPriceVariantId').value;
    const sp = parseFloat(document.getElementById('inputSellingPrice').value) || 0;
    const mrp = parseFloat(document.getElementById('inputMrpPrice').value) || sp;

    if (sp < 0) {
        alertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3">Selling price cannot be negative.</div>`;
        return;
    }

    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Saving...`;

    const formData = new FormData(form);

    fetch(`<?= url('shop/inventory/update-price') ?>`, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-check-circle me-1"></i> Save Price Changes`;

        if (data.success) {
            alertBox.innerHTML = `<div class="alert alert-success py-2 small mb-3">${data.message || 'Price updated successfully!'}</div>`;
            document.getElementById('detailPageSellingPrice').textContent = `₹${sp.toFixed(2)}`;
            const mrpEl = document.getElementById('detailPageMrp');
            const stdMrpEl = document.getElementById('detailPageStandardMrp');
            const discBadge = document.getElementById('detailPageDiscountBadge');

            if (mrp > sp) {
                if (mrpEl) {
                    mrpEl.textContent = `MRP: ₹${mrp.toFixed(2)}`;
                    mrpEl.classList.remove('d-none');
                }
                if (stdMrpEl) stdMrpEl.classList.add('d-none');
                if (discBadge) {
                    const disc = Math.round(((mrp - sp) / mrp) * 100);
                    discBadge.textContent = `${disc}% OFF`;
                    discBadge.classList.remove('d-none');
                }
            } else {
                if (mrpEl) mrpEl.classList.add('d-none');
                if (stdMrpEl) stdMrpEl.classList.remove('d-none');
                if (discBadge) discBadge.classList.add('d-none');
            }

            setTimeout(() => {
                if (currentEditPriceModal) currentEditPriceModal.hide();
            }, 1000);
        } else {
            alertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3">${data.message || 'Failed to update price.'}</div>`;
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-check-circle me-1"></i> Save Price Changes`;
        alertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3">Connection error occurred. Please try again.</div>`;
    });
};

// =========================================================================
// PRINT PIECE QR LABELS MODAL & ENGINE
// =========================================================================
window.printAllPieceTags = function() {
    const container = document.getElementById('printPieceStickersContainer');
    if (!container) return;

    container.innerHTML = '';

    allPieceTagsData.forEach((pt, idx) => {
        const pieceNo = pt.piece_number ?? (pt.piece_no ?? (idx + 1));
        const payload = pt.tag_code;
        
        const col = document.createElement('div');
        col.className = 'col-sm-6 col-md-4 col-lg-3';
        col.innerHTML = `
            <div class="card p-3 rounded-3 shadow-xs border bg-white text-center position-relative">
                <div class="form-check position-absolute top-0 start-0 m-2">
                    <input class="form-check-input print-piece-checkbox" type="checkbox" value="${pieceNo}" id="chk_print_${pieceNo}" checked>
                </div>
                <div id="printModalQr_${pieceNo}" class="d-flex justify-content-center my-2"></div>
                <div class="font-monospace fw-bold text-dark small">${escapeHtml(pt.tag_code)}</div>
                <small class="text-muted font-monospace" style="font-size: 10px;"><?= htmlspecialchars($item['sku']) ?></small>
                <div class="fw-bold text-success small">₹<?= number_format($sellingPrice, 2) ?></div>
            </div>
        `;
        container.appendChild(col);

        // Generate QR
        setTimeout(() => {
            const qrEl = document.getElementById(`printModalQr_${pieceNo}`);
            if (qrEl && typeof QRCode !== 'undefined') {
                new QRCode(qrEl, {
                    text: payload,
                    width: 70,
                    height: 70,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
            }
        }, 50);
    });

    const modalEl = document.getElementById('printPieceModal');
    if (modalEl) {
        currentPrintModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        currentPrintModal.show();
    }
};

window.selectAllPrintPieces = function(checked) {
    document.querySelectorAll('.print-piece-checkbox').forEach(cb => cb.checked = checked);
};

window.triggerStickerPrint = function() {
    const printWindow = window.open('', '_blank');
    const checkedNos = Array.from(document.querySelectorAll('.print-piece-checkbox:checked')).map(cb => cb.value);

    let stickersHtml = '';
    allPieceTagsData.forEach((pt, idx) => {
        const pieceNo = String(pt.piece_number ?? (pt.piece_no ?? (idx + 1)));
        if (!checkedNos.includes(pieceNo)) return;

        const qrCanvas = document.querySelector(`#pieceQr_${pieceNo} canvas`) || document.querySelector(`#printModalQr_${pieceNo} canvas`);
        const qrDataUrl = qrCanvas ? qrCanvas.toDataURL() : '';

        stickersHtml += `
            <div class="sticker-card">
                <div class="header"><?= htmlspecialchars($shop['name'] ?? 'GRM STORE') ?></div>
                <div class="sku-name"><?= htmlspecialchars($item['product_name']) ?></div>
                <div class="sku-code"><?= htmlspecialchars($item['sku']) ?></div>
                <div class="qr-box">
                    <img src="${qrDataUrl}" width="80" height="80">
                </div>
                <div class="tag-code">${escapeHtml(pt.tag_code)}</div>
                <div class="pricing">
                    <span class="mrp">MRP: ₹<?= number_format($mrp, 2) ?></span>
                    <span class="sp">SP: ₹<?= number_format($sellingPrice, 2) ?></span>
                </div>
            </div>
        `;
    });

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Piece QR Labels - <?= htmlspecialchars($item['sku']) ?></title>
            <style>
                @page { size: auto; margin: 8mm; }
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 0; background: #fff; }
                .stickers-grid { display: flex; flex-wrap: wrap; gap: 8mm; justify-content: flex-start; }
                .sticker-card { width: 50mm; height: 60mm; border: 1px dashed #cbd5e1; box-sizing: border-box; padding: 3mm; text-align: center; page-break-inside: avoid; display: flex; flex-column; justify-content: space-between; align-items: center; }
                .header { font-size: 8pt; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
                .sku-name { font-size: 7.5pt; font-weight: 600; color: #334155; max-width: 44mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                .sku-code { font-size: 7pt; font-family: monospace; color: #64748b; font-weight: bold; }
                .qr-box { margin: 1mm 0; }
                .tag-code { font-size: 9pt; font-family: monospace; font-weight: 900; color: #0f172a; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; width: 100%; padding: 1px 0; }
                .pricing { display: flex; justify-content: space-between; width: 100%; font-size: 7.5pt; font-weight: bold; }
                .mrp { text-decoration: line-through; color: #94a3b8; }
                .sp { color: #15803d; }
            </style>
        </head>
        <body>
            <div class="stickers-grid">
                ${stickersHtml}
            </div>
            <script>
                window.onload = function() { window.print(); window.close(); };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
};

window.printSinglePieceTag = function(pt) {
    const pieceNo = pt.piece_number ?? (pt.piece_no ?? 1);
    const qrCanvas = document.querySelector(`#pieceQr_${pieceNo} canvas`);
    const qrDataUrl = qrCanvas ? qrCanvas.toDataURL() : '';

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Label - ${escapeHtml(pt.tag_code)}</title>
            <style>
                @page { size: 50mm 60mm; margin: 0; }
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 3mm; box-sizing: border-box; text-align: center; }
                .header { font-size: 8pt; font-weight: 800; text-transform: uppercase; }
                .sku-name { font-size: 7.5pt; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                .sku-code { font-size: 7pt; font-family: monospace; color: #64748b; font-weight: bold; }
                .tag-code { font-size: 9pt; font-family: monospace; font-weight: 900; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 1px 0; margin: 1mm 0; }
                .pricing { display: flex; justify-content: space-between; font-size: 7.5pt; font-weight: bold; }
                .mrp { text-decoration: line-through; color: #94a3b8; }
                .sp { color: #15803d; }
            </style>
        </head>
        <body>
            <div class="header"><?= htmlspecialchars($shop['name'] ?? 'GRM STORE') ?></div>
            <div class="sku-name"><?= htmlspecialchars($item['product_name']) ?></div>
            <div class="sku-code"><?= htmlspecialchars($item['sku']) ?></div>
            <div><img src="${qrDataUrl}" width="85" height="85"></div>
            <div class="tag-code">${escapeHtml(pt.tag_code)}</div>
            <div class="pricing">
                <span class="mrp">MRP: ₹<?= number_format($mrp, 2) ?></span>
                <span class="sp">SP: ₹<?= number_format($sellingPrice, 2) ?></span>
            </div>
            <script>
                window.onload = function() { window.print(); window.close(); };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
};

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}
</script>
