<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-box-seam me-2 text-primary"></i>Stock Detail: <?= e($variant['sku']) ?></h2>
        
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('warehouse/stock' . (!empty($selectedWarehouseId) ? ('?warehouse_id=' . (int)$selectedWarehouseId) : '')) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Stock Ledger
        </a>
    </div>
</div>
<!-- Warehouse Facility Scope Switcher -->
<div class="card border-0 shadow-sm mb-4 bg-white">
    <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 fs-6">
                <i class="bi bi-geo-alt-fill me-1"></i>Warehouse Facility
            </span>
            <span class="text-muted small">Viewing stock and piece labels for:</span>
            <?php if (!empty($selectedWarehouse)): ?>
                <span class="fw-bold text-dark fs-6"><?= e($selectedWarehouse['name']) ?> (<?= e($selectedWarehouse['code']) ?>)</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-check2-circle me-1"></i><?= number_format($totalAvailable ?? 0) ?> pcs available in this warehouse
                </span>
            <?php else: ?>
                <span class="fw-bold text-dark fs-6">All Warehouses (Consolidated View)</span>
                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                    <i class="bi bi-globe me-1"></i><?= number_format($totalAvailable ?? 0) ?> pcs combined across all facilities
                </span>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="btn-group btn-group-sm shadow-xs" role="group" aria-label="Warehouse Filter">
                <a href="<?= url('warehouse/stock/detail/' . $variant['id']) ?>" 
                   class="btn <?= empty($selectedWarehouseId) ? 'btn-primary active fw-bold' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-globe me-1"></i> All Facilities (<?= number_format($allWarehouseTotalAvailable ?? 0) ?>)
                </a>
                <?php foreach (($warehouses ?? []) as $wh): 
                    $whAvail = $warehouseAvailMap[$wh['id']] ?? 0;
                    $isActiveWh = (!empty($selectedWarehouseId) && (int)$selectedWarehouseId === (int)$wh['id']);
                ?>
                    <a href="<?= url('warehouse/stock/detail/' . $variant['id'] . '?warehouse_id=' . $wh['id']) ?>" 
                       class="btn <?= $isActiveWh ? 'btn-primary active fw-bold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-buildings me-1"></i> <?= e($wh['name']) ?> (<?= number_format($whAvail) ?>)
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($selectedWarehouseId)): ?>
                <a href="<?= url('warehouse/stock/detail/' . $variant['id']) ?>" class="btn btn-sm btn-outline-danger" title="Clear filter and view all warehouses">
                    <i class="bi bi-x-circle me-1"></i> Clear Filter
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Variant Specification Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-tag me-2 text-primary"></i>Product &amp; SKU Metadata</h6>
                <?php if (($variant['source_type'] ?? '') === 'PURCHASED' || ($variant['source_type'] ?? '') === 'EXTERNAL_PURCHASE' || !empty($variant['seller_id'])): ?>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                        <i class="bi bi-bag-check me-1"></i>Third-Party Vendor
                    </span>
                <?php else: ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="bi bi-gear-fill me-1"></i>In-House Production
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">SKU:</span>
                        <span class="fw-bold font-monospace text-primary"><?= e($variant['sku']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Product Name:</span>
                        <span class="fw-semibold text-dark"><?= e($variant['product_name']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Brand / Label:</span>
                        <span class="fw-semibold badge bg-secondary-subtle text-secondary border"><?= e($variant['brand_name'] ?? 'In-House') ?></span>
                    </li>
                    <?php if (!empty($variant['seller_name'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                        <span class="text-muted">Vendor / Supplier:</span>
                        <span class="fw-semibold text-dark text-end">
                            <i class="bi bi-shop text-primary me-1"></i><?= e($variant['seller_name']) ?>
                            <?php if (!empty($variant['seller_company'])): ?>
                                <small class="text-muted d-block"><?= e($variant['seller_company']) ?><?= !empty($variant['seller_city']) ? ' (' . e($variant['seller_city']) . ')' : '' ?></small>
                            <?php endif; ?>
                            <?php if (!empty($variant['seller_phone'])): ?>
                                <small class="text-muted d-block"><i class="bi bi-telephone me-1"></i><?= e($variant['seller_phone']) ?></small>
                            <?php endif; ?>
                        </span>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Category Hierarchy:</span>
                        <span class="fw-semibold text-dark text-end">
                            <?= e($variant['category_name'] ?? 'General') ?>
                            <?php if (!empty($variant['subcategory_name'])): ?>
                                <small class="text-muted d-block">› <?= e($variant['subcategory_name']) ?><?= !empty($variant['sub_subcategory_name']) ? ' › ' . e($variant['sub_subcategory_name']) : '' ?></small>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Category Type:</span>
                        <span>
                            <?php 
                                $isCompVar = (!empty($variant['is_complementary']) || !empty($variant['prod_is_comp']) || ($variant['warehouse_category_type'] ?? '') === 'COMPLEMENTARY');
                                $isSeriesVar = (($variant['qr_type'] ?? '') === 'SERIES_BATCH' || ($variant['warehouse_category_type'] ?? '') === 'SERIES' || (int)($variant['pack_quantity'] ?? 1) > 1);
                                if ($isCompVar):
                            ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning fw-semibold px-2 py-1"><i class="bi bi-gift-fill me-1 text-danger"></i>Complementary</span>
                            <?php elseif ($isSeriesVar): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold px-2 py-1"><i class="bi bi-boxes me-1 text-primary"></i>Series Pack (<?= (int)($variant['pack_quantity'] ?? 10) ?> Pcs)</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border fw-semibold px-2 py-1"><i class="bi bi-tag me-1 text-secondary"></i>Normal (Individual)</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <?php if (!empty($variant['material'])): ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Material / Fabric:</span>
                        <span class="fw-semibold"><?= e($variant['material']) ?></span>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Size &amp; Color:</span>
                        <span>
                            <span class="badge bg-light text-dark border me-1"><?= e($variant['size_name'] ?? 'Free') ?></span>
                            <?= e($variant['color_name'] ?? 'Std') ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">MRP / Base Price:</span>
                        <span class="fw-bold text-muted text-decoration-line-through font-monospace" id="detail-mrp-val">₹<?= number_format((float)($variant['display_mrp'] ?? $variant['mrp'] ?? 0), 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Discount Price:</span>
                        <span class="fw-bold text-success font-monospace" id="detail-selling-val">₹<?= number_format((float)($variant['display_selling_price'] ?? $variant['selling_price'] ?? 0), 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">HSN Code:</span>
                        <span><?= e($variant['hsn_code'] ?? 'N/A') ?></span>
                    </li>
                    <?php if (!empty($variant['mfg_date'])): ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Mfg Date:</span>
                        <span class="fw-semibold text-dark"><i class="bi bi-calendar-check text-success me-1"></i><?= date('d M Y', strtotime($variant['mfg_date'])) ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($variant['expiry_date'])): ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Expiry Date:</span>
                        <span class="fw-semibold text-danger"><i class="bi bi-calendar-x text-danger me-1"></i><?= date('d M Y', strtotime($variant['expiry_date'])) ?></span>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Low Stock Threshold:</span>
                        <span><?= number_format($variant['min_stock'] ?? 0) ?> pcs</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Balances by Warehouse -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-buildings me-2 text-primary"></i>Stock by Warehouse Location</h6>
                <?php if (!empty($selectedWarehouseId)): ?>
                    <a href="<?= url('warehouse/stock/detail/' . $variant['id']) ?>" class="badge bg-secondary-subtle text-secondary border text-decoration-none" title="Show all warehouses combined">
                        View All
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($balances)): ?>
                    <div class="p-3 text-muted text-center">No active inventory balance records.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($balances as $b): 
                            $isThisSelected = (!empty($selectedWarehouseId) && (int)$b['location_id'] === (int)$selectedWarehouseId);
                        ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center <?= $isThisSelected ? 'bg-primary bg-opacity-10 border-start border-primary border-4' : '' ?>">
                                <div>
                                    <span class="fw-semibold <?= $isThisSelected ? 'text-primary' : '' ?>"><?= e($b['warehouse_name']) ?></span>
                                    <?php if ($isThisSelected): ?>
                                        <span class="badge bg-primary text-white ms-1" style="font-size: 0.65rem;"><i class="bi bi-check-circle-fill me-1"></i>Active View</span>
                                    <?php endif; ?>
                                    <br>
                                    <span class="badge bg-<?= match ($b['display_stock_state']) { 'AVAILABLE' => 'success', 'RESERVED' => 'warning text-dark', 'OUT OF STOCK' => 'secondary', default => 'danger' } ?> small">
                                        <?= e($b['display_stock_state']) ?>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold fs-6"><?= number_format($b['quantity']) ?> pcs</span>
                                    <?php if (!$isThisSelected): ?>
                                        <div>
                                            <a href="<?= url('warehouse/stock/detail/' . $variant['id'] . '?warehouse_id=' . $b['location_id']) ?>" class="btn btn-xs btn-outline-primary py-0 px-2 mt-1" style="font-size: 0.70rem;">
                                                View Only
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php if (!empty($selectedWarehouseId)): ?>
                <div class="card-footer bg-light p-2 text-center border-top">
                    <a href="<?= url('warehouse/stock/detail/' . $variant['id']) ?>" class="text-decoration-none small fw-semibold text-primary">
                        <i class="bi bi-globe me-1"></i>View All Warehouses Combined (<?= number_format($allWarehouseTotalAvailable ?? 0) ?> pcs)
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Full Immutable Transaction History -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Immutable Audit Ledger (Movement Trail)</h6>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="max-width:240px;">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="ledgerSearchInput" class="form-control form-control-sm border-start-0" placeholder="Filter ledger events..." oninput="filterLedgerRows(this.value)">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($transactions)): ?>
                    <div class="text-center py-5 text-muted">No stock movement transactions recorded for this SKU.</div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Date &amp; Time</th>
                                    <th>Transaction Event</th>
                                    <th>Quantity</th>
                                    <th>Balance After</th>
                                    <th>User &amp; Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $tx): ?>
                                    <tr class="ledger-tx-row">
                                        <td>
                                            <small class="text-muted d-block"><?= date('d M Y', strtotime($tx['created_at'])) ?></small>
                                            <small class="text-secondary"><?= date('h:i:s A', strtotime($tx['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= str_contains($tx['transaction_type'], 'GRN') || str_contains($tx['transaction_type'], 'IN') ? 'success' : (str_contains($tx['transaction_type'], 'SALE') || str_contains($tx['transaction_type'], 'OUT') || str_contains($tx['transaction_type'], 'DAMAGE') ? 'danger' : 'info') ?>">
                                                <?= e($tx['transaction_type']) ?>
                                            </span>
                                            <br><small class="text-muted"><?= e($tx['stock_state']) ?></small>
                                        </td>
                                        <td class="fw-bold <?= $tx['quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= ($tx['quantity'] > 0 ? '+' : '') . number_format($tx['quantity']) ?>
                                        </td>
                                        <td class="fw-bold fs-6 text-dark"><?= number_format($tx['balance_after']) ?></td>
                                        <td>
                                            <small class="fw-semibold text-primary d-block"><?= e($tx['user_name'] ?? 'System') ?></small>
                                            <small class="text-muted"><?= e($tx['notes'] ?? '—') ?></small>
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
<!-- BATCH-WISE ADDED STOCK HISTORY & MANUFACTURING LINEAGE -->
<!-- ========================================================================= -->
<!-- ========================================================================= -->
<!-- STOCK MANAGEMENT TABS: AVAILABLE PIECES | DAMAGED PRODUCTS | BATCH HISTORY -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-2 px-3 border-bottom">
        <ul class="nav nav-pills card-header-pills gap-2 flex-wrap" id="stockDetailTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link tab-green active fw-bold d-flex align-items-center gap-2 py-2 px-3" id="available-pieces-tab" data-bs-toggle="pill" data-bs-target="#available-pieces-pane" type="button" role="tab" aria-controls="available-pieces-pane" aria-selected="true">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>Warehouse Pieces &amp; QR Labels</span>
                    <span class="badge rounded-pill font-monospace">
                        <?= number_format($totalAvailable ?? 0) ?>
                    </span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link tab-red fw-bold d-flex align-items-center gap-2 py-2 px-3" id="damaged-stock-tab" data-bs-toggle="pill" data-bs-target="#damaged-stock-pane" type="button" role="tab" aria-controls="damaged-stock-pane" aria-selected="false">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <span>Damaged &amp; Quarantined Products</span>
                    <span class="badge rounded-pill font-monospace">
                        <?= count($damagedPieceList ?? []) ?>
                    </span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link tab-pink fw-bold d-flex align-items-center gap-2 py-2 px-3" id="sold-pieces-tab" data-bs-toggle="pill" data-bs-target="#sold-pieces-pane" type="button" role="tab" aria-controls="sold-pieces-pane" aria-selected="false">
                    <i class="bi bi-bag-check-fill"></i>
                    <span>Direct Sales / Sold Pieces</span>
                    <span class="badge rounded-pill font-monospace">
                        <?= count($soldPieceList ?? []) ?>
                    </span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link tab-yellow fw-bold d-flex align-items-center gap-2 py-2 px-3" id="customer-returns-tab" data-bs-toggle="pill" data-bs-target="#customer-returns-pane" type="button" role="tab" aria-controls="customer-returns-pane" aria-selected="false">
                    <i class="bi bi-arrow-return-left"></i>
                    <span>Customer Returns &amp; Exchanges</span>
                    <span class="badge rounded-pill font-monospace">
                        <?= count($returnedPieceList ?? []) ?>
                    </span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link tab-purple fw-bold d-flex align-items-center gap-2 py-2 px-3" id="price-history-tab" data-bs-toggle="pill" data-bs-target="#price-history-pane" type="button" role="tab" aria-controls="price-history-pane" aria-selected="false">
                    <i class="bi bi-tag-fill"></i>
                    <span>Price Revision History &amp; Logs</span>
                    <span class="badge rounded-pill font-monospace" id="price-log-count-badge">
                        <?= count($priceLogs ?? []) ?>
                    </span>
                </button>
            </li>
        </ul>
    </div>
    
    <div class="tab-content" id="stockDetailTabsContent">
        <!-- ================================================================= -->
        <!-- TAB 1: AVAILABLE PIECE QR CODES & LABELS (Sellable Inventory)     -->
        <!-- ================================================================= -->
        <div class="tab-pane fade show active p-4" id="available-pieces-pane" role="tabpanel" aria-labelledby="available-pieces-tab">
            <?php
                $availablePieceCount = count(array_filter($pieceQRs ?? [], static fn($piece) => empty($piece['is_reserved'])));
                $reservedPieceCount = count($pieceQRs ?? []) - $availablePieceCount;
            ?>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-check2-circle me-1 text-success"></i> <?= (!empty($pieceQRs[0]['is_series']) || !empty($pieceQRs[0]['is_complementary'])) ? 'Master Series / Complementary Pack QR Label' : 'Warehouse Pieces (Available &amp; Reserved)' ?>
                        <?php if (!empty($selectedWarehouse)): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2 fw-normal fs-6">
                                <i class="bi bi-geo-alt-fill me-1"></i><?= e($selectedWarehouse['name']) ?> (<?= number_format($totalAvailable) ?> pcs)
                            </span>
                        <?php endif; ?>
                    </h5>
                    <small class="text-muted"><?= (!empty($pieceQRs[0]['is_series']) || !empty($pieceQRs[0]['is_complementary'])) ? 'Single 1-Master QR code represents all ' . ($pieceQRs[0]['pack_quantity'] ?? $totalAvailable) . ' pieces in this bundle/pack. Scanning auto-loads the full pack quantity.' : 'Reserved allocation pieces remain visible until dispatch; dispatched pieces are removed from warehouse stock.' ?></small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-dark fw-semibold shadow-sm" onclick="openPrinterSettingsModal()" title="Calibrate TSC TE244 printer alignment and dimensions">
                        <i class="bi bi-sliders me-1 text-warning"></i> Printer Settings
                    </button>
                    <?php if ($availablePieceCount > 0): ?>
                        <button type="button" class="btn btn-sm btn-success fw-semibold shadow-sm" onclick="printAllPiecesDirectly()">
                            <i class="bi bi-printer me-1"></i> <?= (!empty($pieceQRs[0]['is_series']) || !empty($pieceQRs[0]['is_complementary'])) ? 'Print Master Pack Sticker (1)' : 'Print Available Stickers (' . $availablePieceCount . ')' ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($pieceQRs)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-qr-code fs-1 d-block mb-2 text-secondary"></i>
                    <h6 class="fw-semibold">No active available pieces to display</h6>
                    <p class="small text-muted mb-0">All inward pieces may have been dispatched or moved to damaged quarantine.</p>
                </div>
            <?php else: ?>
                <!-- Search Bar to easily find products by piece code / barcode / size / color -->
                <div class="card border-0 bg-light p-3 rounded-3 mb-3 border">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-6 col-lg-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-primary">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" id="pieceCodeSearchInput" class="form-control border-start-0 border-end-0" 
                                       placeholder="Find product by piece tag code, SKU, size, color... (e.g. WOMMAT...)" 
                                       oninput="filterAvailablePieceCards(this.value)" autocomplete="off">
                                <button class="btn btn-outline-secondary border-start-0 bg-white" type="button" 
                                        id="btnClearPieceCodeSearch" onclick="clearAvailablePieceSearch()" style="display:none;" title="Clear Search">
                                    <i class="bi bi-x-circle-fill text-muted"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-white text-dark border font-monospace py-2 px-3 shadow-xs" id="pieceCardsFilterCountBadge">
                                <i class="bi bi-qr-code me-1 text-primary"></i><?= count($pieceQRs) ?> pieces · <?= $availablePieceCount ?> available · <?= $reservedPieceCount ?> reserved
                            </span>
                        </div>
                        <div class="col-auto ms-auto d-flex align-items-center gap-2">
                            <small class="text-muted"><i class="bi bi-upc-scan me-1 text-warning"></i>Barcode scanner ready</small>
                        </div>
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3" id="availablePieceCardsGrid">
                    <?php foreach ($pieceQRs as $index => $pq): ?>
                        <?php 
                            $fmtMrp = ($pq['mrp'] == (int)$pq['mrp']) ? (int)$pq['mrp'] : number_format($pq['mrp'], 2, '.', '');
                            $fmtSelling = ($pq['selling_price'] == (int)$pq['selling_price']) ? (int)$pq['selling_price'] : number_format($pq['selling_price'], 2, '.', '');
                            $qrPayload = $pq['tag_code'];
                            $isItemSeries = !empty($pq['is_series']) || !empty($pq['is_complementary']);
                            $packQty = (int)($pq['pack_quantity'] ?? ($pieceListQty ?? $totalAvailable));
                            $pieceSearchString = strtolower(htmlspecialchars(($pq['tag_code'] ?? '') . ' ' . ($pq['sku'] ?? '') . ' ' . ($variant['sku'] ?? '') . ' ' . ($variant['product_code'] ?? '') . ' ' . ($pq['size_name'] ?? '') . ' ' . ($pq['color_name'] ?? '') . ' ' . ($pq['category_hierarchy'] ?? '') . ' piece #' . ($pq['piece_no'] ?? ''), ENT_QUOTES, 'UTF-8'));
                        ?>
                        <div class="col piece-card-col" data-search="<?= $pieceSearchString ?>" data-tag-code="<?= htmlspecialchars($pq['tag_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-reserved="<?= !empty($pq['is_reserved']) ? '1' : '0' ?>">
                            <div class="card border rounded-3 p-2.5 h-100 shadow-sm bg-white piece-card position-relative">
                                <div class="d-flex align-items-center justify-content-between mb-1.5">
                                    <?php if ($isItemSeries): ?>
                                        <span class="badge bg-primary text-white font-monospace" style="font-size: 0.68rem;">
                                            <i class="bi bi-box-seam me-1"></i><?= !empty($pq['is_complementary']) ? 'Complementary Pack' : 'Master Series Pack' ?> (<?= $packQty ?> Pcs)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.68rem;">Piece #<?= $pq['piece_no'] ?> of <?= $pieceListQty ?? $totalAvailable ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($pq['is_reserved'])): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.68rem;" title="Allocation <?= e($pq['allocation_number'] ?? '') ?> · <?= e($pq['allocation_status'] ?? 'Reserved') ?>">
                                            <i class="bi bi-lock-fill me-1"></i>Reserved<?= !empty($pq['allocation_number']) ? ' · ' . e($pq['allocation_number']) : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;"><i class="bi bi-check-circle me-1"></i>Available</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Garment Tag Sticker Layout (Inward QR Format) -->
                                <div class="p-2 border rounded-3 bg-white shadow-xs d-flex align-items-start gap-2 mb-2" style="background: #fafafa;">
                                    <!-- Left: QR Code + Tag Code Badge -->
                                    <div class="text-center flex-shrink-0" style="width: 80px;">
                                        <div class="piece-qr-box bg-white p-1 rounded border shadow-xs d-flex align-items-center justify-content-center mx-auto" id="qr-box-<?= $index ?>" data-json="<?= htmlspecialchars($qrPayload, ENT_QUOTES, 'UTF-8') ?>" style="width: 72px; height: 72px;"></div>
                                        <div class="badge bg-dark text-white font-monospace mt-1 px-1 py-0.5 w-100 text-truncate shadow-xs" style="font-size: 0.62rem; letter-spacing: 0.2px;" title="<?= e($pq['tag_code']) ?>">
                                            <?= e($pq['tag_code']) ?>
                                        </div>
                                    </div>
                                    <!-- Right: Brand, Category Hierarchy, Size/Color, MRP, Discount Price -->
                                    <div class="overflow-hidden flex-grow-1" style="font-size: 0.76rem; margin-top: 1px;">
                                        <div class="fw-bold text-uppercase text-dark text-truncate" style="font-size: 0.80rem; letter-spacing: 0.2px;">
                                            <?= e($pq['brand_name'] ?? 'GRM WAREHOUSE') ?>
                                        </div>
                                        <?php if (!empty($pq['category_hierarchy'])): ?>
                                            <div class="mt-1 mb-1">
                                                <span class="badge bg-light text-primary border font-monospace small px-1.5 py-0.5 text-truncate d-inline-block mw-100" style="font-size: 0.70rem;" title="<?= e($pq['category_hierarchy']) ?>">
                                                    <i class="bi bi-diagram-3-fill me-1 text-warning"></i><?= e($pq['category_hierarchy']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="fw-semibold text-secondary text-truncate mt-0.5" style="font-size: 0.78rem;">
                                            <?= e($pq['size_name']) ?>-<?= e($pq['color_name']) ?>
                                        </div>
                                        <?php if ($pq['mrp'] > $pq['selling_price']): ?>
                                            <div class="text-muted small mt-0.5 text-decoration-line-through" style="font-size: 0.72rem;">
                                                MRP: ₹<?= number_format($pq['mrp'], 2) ?>
                                            </div>
                                            <div class="fw-bold text-success mt-0.5" style="font-size: 0.82rem;">
                                                Discount Price: ₹<?= number_format($pq['selling_price'], 2) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="fw-bold text-success mt-0.5" style="font-size: 0.82rem;">
                                                Discount Price: ₹<?= number_format($pq['selling_price'] > 0 ? $pq['selling_price'] : $pq['mrp'], 2) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons for This Piece -->
                                <div class="d-flex gap-1 mt-auto pt-1.5 border-top">
                                    <button type="button" 
                                            class="btn btn-xs btn-outline-primary flex-fill fw-semibold py-1" 
                                            onclick="previewSingleTag(<?= $index ?>)" title="Preview 2D QR Code Tag">
                                        <i class="bi bi-eye"></i> Preview
                                    </button>
                                    <button type="button" 
                                            class="btn btn-xs btn-outline-dark flex-fill fw-semibold py-1" 
                                            onclick="printSinglePiece(<?= $index ?>)" title="<?= !empty($pq['is_reserved']) ? 'Reserved pieces cannot be reprinted as available stock' : 'Print Thermal Sticker' ?>"
                                            <?= !empty($pq['is_reserved']) ? 'disabled' : '' ?>>
                                        <i class="bi bi-printer"></i> <?= !empty($pq['is_reserved']) ? 'Reserved' : 'Print' ?>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-xs btn-outline-secondary px-2 py-1" 
                                            onclick="copyTagCode('<?= e($pq['tag_code']) ?>', this)" title="Copy Code">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Alert when no pieces match the search code -->
                <div id="noPieceCardsFoundAlert" class="text-center py-5 text-muted card bg-light border-0 my-3 rounded-3" style="display:none;">
                    <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    <h6 class="fw-semibold text-dark">No piece found matching your code search</h6>
                    <p class="small text-muted mb-3">Check the tag code, barcode, or style code and try again.</p>
                    <button type="button" class="btn btn-sm btn-outline-primary mx-auto" onclick="clearAvailablePieceSearch()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset Search
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 2: DAMAGED / QUARANTINED PRODUCTS (Dedicated Tab for Users)   -->
        <!-- ================================================================= -->
        <div class="tab-pane fade p-4" id="damaged-stock-pane" role="tabpanel" aria-labelledby="damaged-stock-tab">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="card-title mb-0 fw-bold text-danger">
                        <i class="bi bi-exclamation-octagon-fill me-1"></i> Damaged &amp; Quarantined Products
                        <span class="badge bg-danger text-white rounded-pill ms-2 font-monospace">
                            <?= count($damagedPieceList ?? []) ?> Damaged Pieces
                        </span>
                    </h5>
                    <small class="text-muted">Isolated garments rejected during batch inward verification or flagged as warehouse damage</small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= url('warehouse/damage/create') ?>" class="btn btn-sm btn-outline-danger fw-semibold shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> Report Damage Case
                    </a>
                    <?php if (!empty($damagedPieceList)): ?>
                        <button type="button" class="btn btn-sm btn-danger fw-semibold shadow-sm" onclick="printAllDamagedPiecesDirectly()">
                            <i class="bi bi-printer me-1"></i> Print Damaged Stickers (<?= count($damagedPieceList) ?>)
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($damagedPieceList) && empty($variantDamages)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-shield-check fs-1 d-block mb-2 text-success"></i>
                    <h6 class="fw-semibold text-dark">No Damaged or Quarantined Stock</h6>
                    <p class="small text-muted mb-0">All received pieces for this variant are in good sellable condition.</p>
                </div>
            <?php else: ?>
                <!-- Summary Alert Banner -->
                <div class="alert alert-danger border-danger-subtle bg-danger-subtle d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 rounded-3 p-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                        <div>
                            <div class="fw-bold text-danger-emphasis">Quarantine Isolation Active</div>
                            <small class="text-danger-emphasis">These items are excluded from active sellable stock balances to prevent dispatch errors.</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-danger text-white px-3 py-2 font-monospace fs-7">
                            Total Quarantined: <?= count($damagedPieceList ?? []) ?> pcs
                        </span>
                    </div>
                </div>

                <!-- Section: Damaged Piece QR Codes -->
                <?php if (!empty($damagedPieceList)): ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-qr-code me-1 text-danger"></i> Damaged Pieces Individual QR Codes &amp; Labels
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="max-width:280px;">
                                <span class="input-group-text bg-white border-end-0 text-danger"><i class="bi bi-search"></i></span>
                                <input type="text" id="damagedPieceSearchInput" class="form-control border-start-0" placeholder="Find damaged piece by code..." oninput="filterDamagedPieceCards(this.value)">
                            </div>
                            <span class="badge bg-light text-dark border font-monospace py-1.5" id="damagedPieceCountBadge">
                                <?= count($damagedPieceList) ?> pieces
                            </span>
                        </div>
                    </div>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3 mb-4" id="damagedPieceCardsGrid">
                        <?php foreach ($damagedPieceList as $dIndex => $dp): ?>
                            <?php 
                                $dpMrp = (float)($dp['mrp'] ?? ($variant['mrp'] ?? 0));
                                $dpSelling = (float)($dp['selling_price'] ?? ($variant['selling_price'] ?? $dpMrp));
                                $fmtMrp = ($dpMrp == (int)$dpMrp) ? (int)$dpMrp : number_format($dpMrp, 2, '.', '');
                                $fmtSelling = ($dpSelling == (int)$dpSelling) ? (int)$dpSelling : number_format($dpSelling, 2, '.', '');
                                $dpSku = $dp['sku'] ?? ($variant['sku'] ?? '');
                                $dpReason = $dp['damage_reason'] ?? 'Defective';
                                $dmgQrPayload = $dp['tag_code'];
                                $dmgSearchString = strtolower(htmlspecialchars(($dp['tag_code'] ?? '') . ' ' . ($dpSku ?? '') . ' ' . ($dp['size_name'] ?? '') . ' ' . ($dp['color_name'] ?? '') . ' ' . ($dp['damage_reason'] ?? '') . ' ' . ($dp['damage_number'] ?? ''), ENT_QUOTES, 'UTF-8'));
                            ?>
                            <div class="col damaged-piece-col" data-search="<?= $dmgSearchString ?>">
                                <div class="card border border-danger-subtle rounded-3 p-2.5 h-100 shadow-sm bg-white piece-card position-relative" style="background: #fff8f8 !important;">
                                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace" style="font-size: 0.68rem;">
                                            Piece #<?= $dp['piece_no'] ?? ($dIndex + 1) ?>
                                        </span>
                                        <span class="badge bg-danger text-white border" style="font-size: 0.68rem;">
                                            <i class="bi bi-exclamation-octagon me-1"></i>DAMAGED
                                        </span>
                                    </div>
                                    
                                    <!-- Garment Tag Sticker Layout with Damaged Overlay (Inward QR Format) -->
                                    <div class="p-2 border border-danger-subtle rounded-3 bg-white shadow-xs d-flex align-items-start gap-2 mb-2">
                                        <!-- Left: QR Code + Tag Code Badge -->
                                        <div class="text-center flex-shrink-0" style="width: 80px;">
                                            <div class="piece-qr-box bg-white p-1 rounded border border-danger-subtle shadow-xs d-flex align-items-center justify-content-center mx-auto" id="dmg-qr-box-<?= $dIndex ?>" data-json="<?= htmlspecialchars($dmgQrPayload, ENT_QUOTES, 'UTF-8') ?>" style="width: 72px; height: 72px;"></div>
                                            <div class="badge bg-danger text-white font-monospace mt-1 px-1 py-0.5 w-100 text-truncate shadow-xs" style="font-size: 0.62rem; letter-spacing: 0.2px;" title="<?= e($dp['tag_code']) ?>">
                                                <?= e($dp['tag_code']) ?>
                                            </div>
                                        </div>
                                        <!-- Right: Brand, Size/Color, Damage Reason -->
                                        <div class="overflow-hidden flex-grow-1" style="font-size: 0.76rem; margin-top: 1px;">
                                            <div class="fw-bold text-uppercase text-dark text-truncate" style="font-size: 0.80rem; letter-spacing: 0.2px;">
                                                <?= e($dp['brand_name'] ?? 'GRM WAREHOUSE') ?>
                                            </div>
                                            <div class="fw-semibold text-secondary text-truncate mt-0.5" style="font-size: 0.78rem;">
                                                <?= e($dp['size_name']) ?>-<?= e($dp['color_name']) ?>
                                            </div>
                                            <div class="badge bg-danger-subtle text-danger border border-danger-subtle text-truncate mt-1 d-block text-start" style="font-size: 0.70rem;" title="<?= e($dp['damage_reason']) ?>">
                                                <i class="bi bi-tools me-1"></i><?= e($dp['damage_reason']) ?>
                                            </div>
                                            <?php if (!empty($dp['damage_number'])): ?>
                                                <a href="<?= url('warehouse/damage/view/' . $dp['damage_id']) ?>" class="badge bg-light text-primary border text-decoration-none font-monospace mt-1 d-inline-block" style="font-size: 0.68rem;">
                                                    <i class="bi bi-file-earmark-text me-1"></i><?= e($dp['damage_number']) ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons for Damaged Piece -->
                                    <div class="d-flex gap-1 mt-auto pt-1.5 border-top border-danger-subtle">
                                        <button type="button" 
                                                class="btn btn-xs btn-outline-danger flex-fill fw-semibold py-1" 
                                                onclick="previewSingleDamagedTag(<?= $dIndex ?>)" title="Preview Damaged 2D QR Code Tag">
                                            <i class="bi bi-eye"></i> Preview
                                        </button>
                                        <button type="button" 
                                                class="btn btn-xs btn-outline-dark flex-fill fw-semibold py-1" 
                                                onclick="printSingleDamagedPiece(<?= $dIndex ?>)" title="Print Thermal Sticker">
                                            <i class="bi bi-printer"></i> Print
                                        </button>
                                        <?php if (!empty($dp['damage_id'])): ?>
                                            <a href="<?= url('warehouse/damage/view/' . $dp['damage_id']) ?>" class="btn btn-xs btn-outline-secondary px-2 py-1" title="View Damage Record">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Section: Damage Cases & Defect Ledger Table -->
                <h6 class="fw-bold text-dark mb-2">
                    <i class="bi bi-journal-text me-1 text-danger"></i> Damage Cases &amp; Defect Ledger
                </h6>
                <div class="table-responsive border rounded-3 bg-white shadow-xs">
                    <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 0.84rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Damage Case #</th>
                                <th>Damaged Piece Tags</th>
                                <th>Source / Origin</th>
                                <th>Damage Reason / Defect</th>
                                <th class="text-center">Qty</th>
                                <th>Reported By / Date</th>
                                <th>Status</th>
                                <th class="pe-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($variantDamages)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-3 text-muted">No damage cases registered for this variant.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($variantDamages as $dmg): ?>
                                    <tr>
                                        <td class="ps-3 text-nowrap">
                                            <a href="<?= url('warehouse/damage/view/' . $dmg['id']) ?>" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none fw-bold p-1.5">
                                                <i class="bi bi-exclamation-octagon me-1"></i><?= e($dmg['damage_number']) ?>
                                            </a>
                                        </td>
                                        <td style="max-width: 200px;">
                                            <?php if (!empty($dmg['piece_tags'])): ?>
                                                <?php 
                                                    $tags = array_filter(array_map('trim', explode(',', $dmg['piece_tags'])));
                                                    foreach ($tags as $tag): 
                                                ?>
                                                    <span class="badge bg-dark text-white font-monospace me-1 mb-1" style="font-size: 0.70rem;"><?= e($tag) ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">Bulk Qty (No Tags)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border small">
                                                <?= e($dmg['source_type'] ?? 'WAREHOUSE_FOUND') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-danger"><?= e($dmg['reason_type'] ?? 'Defective') ?></div>
                                            <?php if (!empty($dmg['notes'])): ?>
                                                <small class="text-muted d-block text-truncate" style="max-width: 220px;" title="<?= e($dmg['notes']) ?>"><?= e($dmg['notes']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center text-nowrap fw-bold text-danger">
                                            <?= number_format($dmg['quantity']) ?> pcs
                                        </td>
                                        <td class="text-nowrap">
                                            <div class="small fw-semibold text-dark"><?= e($dmg['reported_by_name'] ?? 'Warehouse Staff') ?></div>
                                            <small class="text-muted"><?= !empty($dmg['reported_at']) ? date('d M Y, h:i A', strtotime($dmg['reported_at'])) : 'N/A' ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1 small">
                                                <?= e($dmg['status'] ?? 'REPORTED') ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 text-end text-nowrap">
                                            <a href="<?= url('warehouse/damage/view/' . $dmg['id']) ?>" class="btn btn-xs btn-outline-danger py-1 px-2">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>View Case
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 3: INWARD BATCH HISTORY & MANUFACTURING LINEAGE               -->
        <!-- ================================================================= -->
        <div class="tab-pane fade p-4" id="batch-history-pane" role="tabpanel" aria-labelledby="batch-history-tab" hidden>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-boxes me-1"></i> Batch-Wise Added Stock History
                        <?php if (!empty($selectedWarehouse)): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2 fw-normal fs-6">
                                <i class="bi bi-geo-alt-fill me-1"></i><?= e($selectedWarehouse['name']) ?>
                            </span>
                        <?php endif; ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-2 font-monospace" id="batchHistoryCountBadge">
                            <?= count($variantBatchHistory ?? []) ?> Inward Batches
                        </span>
                    </h5>
                    <small class="text-muted">Track which manufacturing batches, production units &amp; gatepasses credited stock to this inventory</small>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="input-group input-group-sm" style="min-width:240px;">
                        <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-search"></i></span>
                        <input type="text" id="batchHistorySearchInput" class="form-control border-start-0" placeholder="Find batch by code or #..." oninput="filterBatchHistoryTable(this.value)">
                    </div>
                    <span class="badge bg-light text-dark border font-monospace px-2.5 py-1.5">
                        <i class="bi bi-upc-scan me-1 text-primary"></i>SKU: <?= e($variant['sku']) ?>
                    </span>
                </div>
            </div>

            <?php if (empty($variantBatchHistory)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary"></i>
                    <h6 class="fw-semibold mb-1">No Direct Production Inward Batches Recorded for this Specific Variant</h6>
                    <p class="small text-muted mb-0">Initial opening stock was established or stock was recorded via opening balance ledger.</p>
                    <?php if (!empty($productBatchHistory)): ?>
                        <div class="mt-3 pt-3 border-top text-start px-4">
                            <span class="small fw-bold text-dark d-block mb-2"><i class="bi bi-info-circle me-1 text-primary"></i>Related Production Batches for Style "<?= e($variant['product_name']) ?>":</span>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0 font-monospace" style="font-size: 0.82rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Batch #</th>
                                            <th>Program #</th>
                                            <th>Origin Facility</th>
                                            <th>Receiving Warehouse</th>
                                            <th class="text-end">Batch Total Qty</th>
                                            <th>Outward Gatepass</th>
                                            <th>Date</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productBatchHistory as $pbh): ?>
                                            <tr>
                                                <td>
                                                    <a href="<?= url('warehouse/inward/view/' . $pbh['batch_id']) ?>" class="badge bg-info-subtle text-info-emphasis border border-info-subtle text-decoration-none">
                                                        <i class="bi bi-box-seam me-1"></i><?= e($pbh['batch_number']) ?>
                                                    </a>
                                                </td>
                                                <td><?= e($pbh['program_number'] ?? 'N/A') ?></td>
                                                <td><span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50"><?= e($pbh['unit_code'] ?? 'PU1') ?> · <?= e($pbh['unit_name'] ?? 'Main Facility') ?></span></td>
                                                <td><?= e($pbh['warehouse_name'] ?? 'Main WH') ?></td>
                                                <td class="text-end fw-bold text-primary"><?= number_format($pbh['style_batch_qty'] ?? $pbh['batch_total_qty']) ?> pcs</td>
                                                <td><?= e($pbh['outward_number'] ?? 'N/A') ?></td>
                                                <td><?= !empty($pbh['inward_received_at']) ? date('d M Y', strtotime($pbh['inward_received_at'])) : 'N/A' ?></td>
                                                <td class="text-end">
                                                    <a href="<?= url('warehouse/inward/view/' . $pbh['batch_id']) ?>" class="btn btn-xs btn-outline-primary py-1 px-2">
                                                        <i class="bi bi-box-arrow-in-down me-1"></i>View GRN
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
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 140px;">Batch #</th>
                                <th class="text-uppercase fs-7 fw-bold text-muted" style="min-width: 160px;">Program &amp; Style</th>
                                <th class="text-uppercase fs-7 fw-bold text-muted" style="min-width: 180px;">Origin Production Unit</th>
                                <th class="text-uppercase fs-7 fw-bold text-muted" style="min-width: 140px;">Warehouse</th>
                                <th class="text-center text-uppercase fs-7 fw-bold text-muted" style="min-width: 110px;">Good Stock</th>
                                <th class="text-center text-uppercase fs-7 fw-bold text-muted" style="min-width: 140px;">Damaged / Quarantine</th>
                                <th class="text-uppercase fs-7 fw-bold text-muted" style="min-width: 140px;">Outward Logistics</th>
                                <th class="text-uppercase fs-7 fw-bold text-muted" style="min-width: 130px;">Inward Date</th>
                                <th class="text-uppercase fs-7 fw-bold text-muted" style="min-width: 100px;">Status</th>
                                <th class="pe-3 text-end text-uppercase fs-7 fw-bold text-muted" style="min-width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($variantBatchHistory as $b): ?>
                                <?php 
                                    $dmgQty = (int)($b['damage_qty'] ?? ($b['item_damaged_qty'] ?? 0));
                                    $batchSearchString = strtolower(htmlspecialchars(($b['batch_number'] ?? '') . ' ' . ($b['program_number'] ?? '') . ' ' . ($b['unit_name'] ?? '') . ' ' . ($b['unit_code'] ?? '') . ' ' . ($b['outward_number'] ?? '') . ' ' . ($b['warehouse_name'] ?? ''), ENT_QUOTES, 'UTF-8'));
                                ?>
                                <tr class="batch-history-row" data-search="<?= $batchSearchString ?>">
                                    <td class="ps-3 text-nowrap">
                                        <a href="<?= url('warehouse/inward/view/' . $b['batch_id']) ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2.5 py-1.5 fs-7 fw-bold text-decoration-none shadow-2xs d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-box-seam text-primary"></i>
                                            <?= e($b['batch_number']) ?>
                                        </a>
                                        <?php if (!empty($b['box_count'])): ?>
                                            <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;"><?= number_format($b['box_count']) ?> carton box(es)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark-subtle text-dark-emphasis font-monospace border small"><?= e($b['program_number'] ?? 'PRG') ?></span>
                                        <div class="small fw-semibold text-dark text-truncate mt-1" style="max-width: 180px;" title="<?= e($b['program_name'] ?? '') ?>">
                                            <?= e($b['program_name'] ?? 'Production Order') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                            <span class="badge bg-warning text-dark font-monospace border border-warning small px-2 py-0.5"><?= e($b['unit_code'] ?? 'PU1') ?></span>
                                            <span class="small fw-semibold text-dark text-truncate" style="max-width: 140px;" title="<?= e($b['unit_name'] ?? '') ?>"><?= e($b['unit_name'] ?? 'Main Apparel Unit') ?></span>
                                        </div>
                                        <?php if (!empty($b['unit_manager'])): ?>
                                            <small class="text-muted d-block" style="font-size: 0.72rem;"><i class="bi bi-person me-1"></i><?= e($b['unit_manager']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1 small">
                                            <i class="bi bi-building me-1 text-primary"></i><?= e($b['warehouse_name'] ?? 'Central WH') ?>
                                        </span>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 px-2.5 py-1.5 fw-bold font-monospace">
                                            +<?= number_format($b['batch_item_qty']) ?> pcs
                                        </span>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <?php if ($dmgQty > 0): ?>
                                            <a href="<?= url('warehouse/damage/view/' . ($b['damage_id'] ?? '')) ?>" class="badge bg-danger-subtle text-danger border border-danger-subtle fs-7 px-2.5 py-1.5 text-decoration-none shadow-2xs d-inline-flex align-items-center gap-1" title="View Damage Case <?= e($b['damage_number'] ?? '') ?>">
                                                <i class="bi bi-exclamation-octagon-fill text-danger"></i>
                                                <span><?= number_format($dmgQty) ?> pcs Damaged</span>
                                            </a>
                                            <?php if (!empty($b['damage_tags'])): ?>
                                                <small class="text-danger d-block font-monospace mt-0.5" style="font-size: 0.70rem;" title="<?= e($b['damage_tags']) ?>">
                                                    <i class="bi bi-qr-code me-1"></i><?= e(strlen($b['damage_tags']) > 24 ? substr($b['damage_tags'], 0, 22) . '...' : $b['damage_tags']) ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border small"><i class="bi bi-check2 text-success me-1"></i>0 Damaged</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold font-monospace text-primary"><?= e($b['outward_number'] ?? 'N/A') ?></div>
                                        <?php if (!empty($b['vehicle_number'])): ?>
                                            <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;"><i class="bi bi-truck me-1"></i><?= e($b['vehicle_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="small fw-semibold text-dark"><?= !empty($b['inward_received_at']) ? date('d M Y', strtotime($b['inward_received_at'])) : 'N/A' ?></div>
                                        <small class="text-muted d-block" style="font-size: 0.72rem;"><?= !empty($b['inward_received_at']) ? date('h:i A', strtotime($b['inward_received_at'])) : '' ?></small>
                                    </td>
                                    <td class="text-nowrap">
                                        <?php if ($b['batch_status'] === 'COMPLETED' || $b['batch_status'] === 'RECEIVED'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill small fw-semibold">
                                                <i class="bi bi-check2-circle me-1"></i>Stocked
                                            </span>
                                        <?php elseif ($b['batch_status'] === 'DISPATCHED'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 rounded-pill small fw-semibold">
                                                <i class="bi bi-truck me-1"></i>In Transit
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border px-2.5 py-1 rounded-pill small">
                                                <?= e($b['batch_status']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <div class="d-inline-flex gap-1">
                                            <a href="<?= url('warehouse/inward/view/' . $b['batch_id']) ?>" class="btn btn-sm btn-outline-primary px-2 py-1 shadow-sm d-inline-flex align-items-center gap-1" title="View Inward GRN &amp; Verification">
                                                <i class="bi bi-box-arrow-in-down"></i>
                                                <span>GRN</span>
                                            </a>
                                            <?php if (!empty($b['damage_id'])): ?>
                                                <a href="<?= url('warehouse/damage/view/' . $b['damage_id']) ?>" class="btn btn-sm btn-outline-danger px-2 py-1 shadow-sm d-inline-flex align-items-center gap-1" title="View Damage Case <?= e($b['damage_number'] ?? '') ?>">
                                                    <i class="bi bi-exclamation-octagon"></i>
                                                    <span>Damage</span>
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

        <!-- ================================================================= -->
        <!-- TAB 4: DIRECT SALES & SOLD PIECES LEDGER                          -->
        <!-- ================================================================= -->
        <div class="tab-pane fade p-4" id="sold-pieces-pane" role="tabpanel" aria-labelledby="sold-pieces-tab">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-bag-check-fill me-1"></i> Sold &amp; Purchased Garment Pieces
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-2 font-monospace">
                            <?= count($soldPieceList ?? []) ?> Sold Pieces
                        </span>
                    </h5>
                    <small class="text-muted">Track pieces sold via Direct Customer Purchases — excluded from active sellable inventory</small>
                </div>
                <span class="badge bg-light text-dark border font-monospace px-2.5 py-1.5">
                    <i class="bi bi-upc-scan me-1 text-primary"></i>SKU: <?= e($variant['sku']) ?>
                </span>
            </div>

            <?php if (empty($soldPieceList)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bag-x fs-1 d-block mb-2 text-secondary"></i>
                    <h6 class="fw-semibold mb-1">No Direct Sales Recorded for this Variant</h6>
                    <p class="small text-muted mb-0">No pieces have been sold through Warehouse Direct Purchases yet.</p>
                </div>
            <?php else: ?>
                <!-- Piece Tag Badges Grid -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-qr-code me-1 text-primary"></i> Sold Piece QR Tags
                    </h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($soldPieceList as $sp): ?>
                            <div class="card border border-primary-subtle shadow-xs p-2 rounded-3 bg-light d-flex flex-row align-items-center gap-2" style="min-width: 220px;">
                                <div class="badge bg-dark text-white font-monospace px-2 py-1.5 fs-7">
                                    <i class="bi bi-qr-code me-1"></i><?= e($sp['tag_code']) ?>
                                </div>
                                <div class="overflow-hidden flex-grow-1" style="font-size: 0.76rem;">
                                    <div class="fw-bold text-truncate text-dark"><?= e($sp['customer_name']) ?></div>
                                    <div class="text-muted font-monospace" style="font-size: 0.70rem;">
                                        <a href="<?= url('warehouse/purchases/receive/' . $sp['purchase_id']) ?>" class="text-decoration-none text-primary">
                                            <?= e($sp['po_number']) ?>
                                        </a>
                                        · <?= !empty($sp['sold_at']) ? date('d M y', strtotime($sp['sold_at'])) : '' ?>
                                    </div>
                                </div>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace" style="font-size: 0.68rem;">
                                    SOLD OUT
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Direct Purchase Transactions Table -->
                <h6 class="fw-bold text-dark mb-2">
                    <i class="bi bi-receipt me-1 text-primary"></i> Direct Purchase Invoices
                </h6>
                <div class="table-responsive border rounded-3 bg-white shadow-xs">
                    <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 0.84rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Invoice / PO #</th>
                                <th>Sold Piece Tags</th>
                                <th>Customer Details</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total</th>
                                <th>Order Date</th>
                                <th>Status</th>
                                <th class="pe-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($variantPurchases as $vp): ?>
                                <tr>
                                    <td class="ps-3 text-nowrap">
                                        <a href="<?= url('warehouse/purchases/receive/' . $vp['purchase_order_id']) ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none fw-bold p-1.5">
                                            <i class="bi bi-receipt me-1"></i><?= e($vp['po_number']) ?>
                                        </a>
                                    </td>
                                    <td style="max-width: 220px;">
                                        <?php if (!empty($vp['batch_lot_no'])): ?>
                                            <?php 
                                                $pTags = array_filter(array_map('trim', explode(',', $vp['batch_lot_no'])));
                                                foreach ($pTags as $pt): 
                                            ?>
                                                <span class="badge bg-dark text-white font-monospace me-1 mb-1" style="font-size: 0.70rem;"><?= e($pt) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Bulk Qty (No Tags)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($vp['customer_name'] ?? 'Direct Customer') ?></div>
                                        <?php if (!empty($vp['customer_phone'])): ?>
                                            <small class="text-muted d-block"><?= e($vp['customer_phone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-dark">
                                        <?= number_format($vp['quantity']) ?> pc
                                    </td>
                                    <td class="text-end font-monospace">
                                        ₹<?= number_format((float)$vp['unit_price'], 2) ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-success">
                                        ₹<?= number_format((float)$vp['total_amount'], 2) ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <?= !empty($vp['order_date']) ? date('d M Y', strtotime($vp['order_date'])) : 'N/A' ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small fw-semibold">
                                            <?= e($vp['po_status']) ?>
                                        </span>
                                    </td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <a href="<?= url('warehouse/purchases/receive/' . $vp['purchase_order_id']) ?>" class="btn btn-xs btn-outline-primary py-1 px-2">
                                            <i class="bi bi-file-earmark-text me-1"></i>Invoice
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 5: CUSTOMER RETURNS & EXCHANGES LEDGER                        -->
        <!-- ================================================================= -->
        <div class="tab-pane fade p-4" id="customer-returns-pane" role="tabpanel" aria-labelledby="customer-returns-tab">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-arrow-return-left me-1 text-warning"></i> Customer Returns &amp; Exchanged Pieces
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill ms-2 font-monospace">
                            <?= count($returnedPieceList ?? []) ?> Returned Pieces
                        </span>
                    </h5>
                    <small class="text-muted">Track garment pieces returned or exchanged by direct customers — dynamically linked with QR code tags &amp; return invoices</small>
                </div>
                <span class="badge bg-light text-dark border font-monospace px-2.5 py-1.5">
                    <i class="bi bi-upc-scan me-1 text-primary"></i>SKU: <?= e($variant['sku']) ?>
                </span>
            </div>

            <?php if (empty($returnedPieceList)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-arrow-counterclockwise fs-1 d-block mb-2 text-secondary"></i>
                    <h6 class="fw-semibold mb-1">No Customer Returns Recorded for this Variant</h6>
                    <p class="small text-muted mb-0">No pieces have been returned or exchanged for this SKU yet.</p>
                </div>
            <?php else: ?>
                <!-- Piece Tag Badges Grid -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-qr-code me-1 text-warning"></i> Returned Garment Piece QR Tags
                    </h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($returnedPieceList as $idx => $rp): ?>
                            <div class="card border border-warning-subtle shadow-xs p-2 rounded-3 bg-light d-flex flex-row align-items-center gap-2" style="min-width: 240px;">
                                <div class="badge bg-dark text-white font-monospace px-2 py-1.5 fs-7 cursor-pointer" onclick="previewSingleReturnedTag(<?= $idx ?>)" title="Click to view QR Code">
                                    <i class="bi bi-qr-code me-1 text-warning"></i><?= e($rp['tag_code']) ?>
                                </div>
                                <div class="overflow-hidden flex-grow-1" style="font-size: 0.76rem;">
                                    <div class="fw-bold text-truncate text-dark"><?= e($rp['customer_name']) ?></div>
                                    <div class="text-muted font-monospace" style="font-size: 0.70rem;">
                                        <a href="<?= url('warehouse/purchases/return-invoice/' . $rp['return_id']) ?>" class="text-decoration-none text-primary">
                                            <?= e($rp['return_number']) ?>
                                        </a>
                                        · <?= !empty($rp['returned_at']) ? date('d M y', strtotime($rp['returned_at'])) : '' ?>
                                    </div>
                                </div>
                                <span class="badge <?= $rp['item_condition'] === 'GOOD' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> font-monospace" style="font-size: 0.68rem;">
                                    <?= $rp['item_condition'] === 'GOOD' ? 'RESTOCKED' : 'QUARANTINE' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Customer Returns Transactions Table -->
                <h6 class="fw-bold text-dark mb-2">
                    <i class="bi bi-receipt me-1 text-warning"></i> Return &amp; Exchange Invoices
                </h6>
                <div class="table-responsive border rounded-3 bg-white shadow-xs">
                    <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 0.84rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Return / Exchange #</th>
                                <th>Returned Piece Tags</th>
                                <th>Type</th>
                                <th>Customer Details</th>
                                <th class="text-center">Qty</th>
                                <th>Condition &amp; Dest</th>
                                <th class="text-end">Refund Amount</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th class="pe-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($variantReturns as $vr): ?>
                                <tr>
                                    <td class="ps-3 text-nowrap">
                                        <a href="<?= url('warehouse/purchases/return-invoice/' . $vr['return_id']) ?>" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle text-decoration-none fw-bold p-1.5">
                                            <i class="bi bi-arrow-return-left me-1"></i><?= e($vr['return_number']) ?>
                                        </a>
                                        <?php if (!empty($vr['po_number'])): ?>
                                            <small class="text-muted d-block font-monospace">PO: <?= e($vr['po_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width: 220px;">
                                        <?php 
                                            $rTags = [];
                                            if (!empty($vr['reason']) && preg_match_all('/([A-Z0-9]{8,14})/i', $vr['reason'], $mTags)) {
                                                $rTags = $mTags[1];
                                            }
                                            if (empty($rTags)) {
                                                $rTags = ['RET-' . str_pad($vr['id'], 4, '0', STR_PAD_LEFT)];
                                            }
                                            foreach ($rTags as $rt):
                                        ?>
                                            <span class="badge bg-dark text-white font-monospace me-1 mb-1" style="font-size: 0.70rem;"><?= e($rt) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 rounded-pill small fw-semibold">
                                            <?= e($vr['return_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($vr['customer_name'] ?? 'Direct Customer') ?></div>
                                        <?php if (!empty($vr['customer_phone'])): ?>
                                            <small class="text-muted d-block"><?= e($vr['customer_phone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-dark">
                                        <?= number_format($vr['quantity']) ?> pc
                                    </td>
                                    <td class="text-nowrap">
                                        <?php if (($vr['item_condition'] ?? 'GOOD') === 'GOOD'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small fw-semibold">
                                                <i class="bi bi-check-circle me-1"></i>Good (Restocked)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill small fw-semibold">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Damaged (Quarantine)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-dark">
                                        ₹<?= number_format((float)($vr['refund_amount'] ?? 0), 2) ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <?= !empty($vr['return_date']) ? date('d M Y', strtotime($vr['return_date'])) : 'N/A' ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small fw-semibold">
                                            <?= e($vr['return_status'] ?? 'COMPLETED') ?>
                                        </span>
                                    </td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <a href="<?= url('warehouse/purchases/return-invoice/' . $vr['return_id']) ?>" class="btn btn-xs btn-outline-warning py-1 px-2 text-dark">
                                            <i class="bi bi-file-earmark-text me-1"></i>Invoice
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 6: PRICE REVISION LOGS & DYNAMICS AUDIT TRAIL                 -->
        <!-- ================================================================= -->
        <div class="tab-pane fade p-4" id="price-history-pane" role="tabpanel" aria-labelledby="price-history-tab">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="card-title mb-0 fw-bold" style="color: #7c3aed;">
                        <i class="bi bi-tag-fill me-1"></i> Price Revision History &amp; Margin Ledger
                        <span class="badge rounded-pill ms-2 font-monospace" id="price-log-inner-count" style="background-color: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe;">
                            <?= count($priceLogs ?? []) ?> Revision Logs
                        </span>
                    </h5>
                    <small class="text-muted">Complete audit log of all MRP, Selling Price, markup &amp; discount changes applied to this SKU</small>
                </div>
            </div>

            <?php if (empty($priceLogs)): ?>
                <div class="text-center py-5 text-muted" id="no-price-logs-msg">
                    <i class="bi bi-tags fs-1 d-block mb-2 text-secondary"></i>
                    <h6 class="fw-semibold mb-1">No Price Revisions Recorded for this SKU</h6>
                    <p class="small text-muted mb-0">Current pricing is active from initial catalog entry.</p>
                </div>
                <div class="table-responsive border rounded-3 bg-white shadow-xs d-none" id="price-logs-table-wrap">
                    <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 0.84rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Date &amp; Time</th>
                                <th>MRP Transition</th>
                                <th>Selling Price Transition</th>
                                <th>Markup over Cost</th>
                                <th>Discount on MRP</th>
                                <th>Revision Notes / Reason</th>
                                <th class="pe-3 text-end">Updated By</th>
                            </tr>
                        </thead>
                        <tbody id="price-logs-tbody">
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="table-responsive border rounded-3 bg-white shadow-xs" id="price-logs-table-wrap">
                    <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 0.84rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Date &amp; Time</th>
                                <th>MRP Transition</th>
                                <th>Selling Price Transition</th>
                                <th>Markup over Cost</th>
                                <th>Discount on MRP</th>
                                <th>Revision Notes / Reason</th>
                                <th class="pe-3 text-end">Updated By</th>
                            </tr>
                        </thead>
                        <tbody id="price-logs-tbody">
                            <?php foreach ($priceLogs as $pl): ?>
                                <tr>
                                    <td class="ps-3 text-nowrap">
                                        <div class="small fw-semibold text-dark"><?= !empty($pl['created_at']) ? date('d M Y', strtotime($pl['created_at'])) : 'N/A' ?></div>
                                        <small class="text-muted d-block" style="font-size: 0.72rem;"><?= !empty($pl['created_at']) ? date('h:i A', strtotime($pl['created_at'])) : '' ?></small>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="text-muted text-decoration-line-through">₹<?= number_format((float)$pl['old_mrp'], 2) ?></span>
                                        <i class="bi bi-arrow-right text-primary mx-1"></i>
                                        <span class="fw-bold text-dark">₹<?= number_format((float)$pl['new_mrp'], 2) ?></span>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="text-muted text-decoration-line-through">₹<?= number_format((float)$pl['old_selling_price'], 2) ?></span>
                                        <i class="bi bi-arrow-right text-success mx-1"></i>
                                        <span class="fw-bold text-success">₹<?= number_format((float)$pl['new_selling_price'], 2) ?></span>
                                    </td>
                                    <td class="text-nowrap">
                                        <?php if ($pl['markup_percentage'] !== null): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 font-monospace">
                                                <?= ($pl['markup_percentage'] >= 0 ? '+' : '') . round($pl['markup_percentage'], 1) ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <?php if ($pl['discount_percentage'] !== null && $pl['discount_percentage'] > 0): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 font-monospace">
                                                <?= round($pl['discount_percentage'], 1) ?>% Off
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-2 py-1">0%</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width: 250px;">
                                        <span class="text-dark font-sans"><?= e($pl['revision_notes'] ?: 'Price adjustment') ?></span>
                                    </td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            <i class="bi bi-person-circle me-1 text-primary"></i><?= e($pl['user_name'] ?? 'Admin') ?>
                                        </span>
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

<!-- ========================================================================= -->
<!-- MODAL: 2D QR CODE TAG PREVIEW (Exact Image 3 Layout) -->
<!-- ========================================================================= -->
<div class="modal fade" id="singleQRModal" tabindex="-1" aria-labelledby="singleQRModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-white py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="singleQRModalLabel">
                    <i class="bi bi-qr-code me-2 text-primary"></i>2D QR Code Tag Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center bg-light">
                <div class="p-3 border rounded-3 bg-white d-inline-block shadow-sm mb-3 text-start position-relative" style="width: 270px; min-height: 120px;">
                    <div class="d-flex align-items-start gap-2.5">
                        <div class="text-center flex-shrink-0">
                            <div id="singleQRCanvas" class="bg-white p-1 rounded border shadow-xs d-flex align-items-center justify-content-center" style="width: 78px; height: 78px;"></div>
                            <div class="badge bg-dark text-white font-monospace mt-1 px-2 py-1 w-100" id="previewDamageCode" style="font-size: 0.75rem; letter-spacing: 0.5px;">TTS092601</div>
                        </div>
                        <div class="overflow-hidden ms-1 flex-grow-1" style="margin-top: 2px;">
                            <div class="fw-bold text-uppercase text-dark text-truncate" id="previewBrandName" style="font-size: 0.82rem; letter-spacing: 0.3px;">GRM WAREHOUSE</div>
                            <div id="previewCategoryHierarchyContainer" class="mt-1 mb-1" style="display: none;">
                                <span class="badge bg-light text-primary border font-monospace small px-1.5 py-0.5 text-truncate d-inline-block mw-100" style="font-size: 0.68rem;">
                                    <i class="bi bi-diagram-3-fill me-1 text-warning"></i><span id="previewCategoryHierarchyText"></span>
                                </span>
                            </div>
                            <div class="fw-bold text-secondary text-truncate" id="previewAttrText" style="font-size: 0.80rem;">4XL-BLUE</div>
                            <div class="text-muted small text-decoration-line-through" id="previewBasePrice" style="font-size: 0.76rem;">MRP: ₹1000</div>
                            <div class="fw-bold text-success" id="previewDiscPrice" style="font-size: 0.84rem;">Discount Price: ₹800</div>
                        </div>
                    </div>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1 text-primary"></i>Scannable 2D QR Code containing SKU, Style, Size, Color &amp; Price payload.
                </div>
            </div>
            <div class="modal-footer bg-white border-top py-2">
                <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success px-4 fw-semibold shadow-sm" id="btnSinglePrintModal" onclick="printSinglePieceFromModal()">
                    <i class="bi bi-printer-fill me-1"></i> Print This Tag
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PRINT STOCK STICKER SHEET / SINGLE STICKER PREVIEW -->
<!-- ========================================================================= -->
<div class="modal fade" id="stockStickerPrintModal" tabindex="-1" aria-labelledby="stockStickerPrintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3 no-print">
                <h5 class="modal-title fw-bold" id="stockStickerPrintModalLabel">
                    <i class="bi bi-printer me-2 text-warning"></i>Print Stock QR Stickers Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                    <div class="small text-muted">
                        Sticker format: Standard 50mm × 30mm Barcode/QR Garment Label
                    </div>
                    <div>
                        <button type="button" class="btn btn-success fw-bold shadow-sm" onclick="window.print()">
                            <i class="bi bi-printer-fill me-1"></i> Print Stickers (Ctrl+P)
                        </button>
                    </div>
                </div>

                <!-- Printable Stickers Container -->
                <div id="printStickerArea" class="printable-stickers-container bg-white p-3 rounded border">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
            <div class="modal-footer bg-white no-print">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success px-4 fw-bold" onclick="window.print()">
                    <i class="bi bi-printer-fill me-1"></i> Print Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- TSC TE244 PRINTER SETTINGS & LIVE DUAL-STICKER PREVIEW MODAL -->
<!-- ========================================================================= -->
<div class="modal fade" id="printerSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1280px; width: 95%;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-sliders me-2 text-warning"></i>TSC TE244 Printer &amp; Dynamic Label Layout Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row g-4">
                    <!-- Left Side: Interactive Controls -->
                    <div class="col-lg-5 col-md-6">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white fw-bold py-2"><i class="bi bi-bounding-box me-1 text-primary"></i>1. Sticker Dimensions</div>
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Sticker Width (mm)</label>
                                        <input type="number" id="cfgWidth" class="form-control form-control-sm" value="40" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Sticker Height (mm)</label>
                                        <input type="number" id="cfgHeight" class="form-control form-control-sm" value="25" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Roll Columns</label>
                                        <select id="cfgColumns" class="form-select form-select-sm" onchange="updateLivePreview()">
                                            <option value="2">2 Columns (Dual Roll)</option>
                                            <option value="1">1 Column (Single Roll)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Row Gap (mm)</label>
                                        <input type="number" id="cfgGap" class="form-control form-control-sm" value="2" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <label class="form-label small fw-semibold mb-1 text-primary"><i class="bi bi-arrow-left-right me-1"></i>2nd Sticker (Right Column) X Shift (dots)</label>
                                        <input type="number" id="cfgCol2ShiftDots" class="form-control form-control-sm border-primary" value="0" placeholder="e.g. -10 to move left, +10 to move right" oninput="updateLivePreview()">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white fw-bold py-2"><i class="bi bi-qr-code me-1 text-success"></i>2. QR Code Alignment (Left Column)</div>
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold mb-1">QR X (dots)</label>
                                        <input type="number" id="cfgQrX" class="form-control form-control-sm" value="12" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold mb-1">QR Y (dots)</label>
                                        <input type="number" id="cfgQrY" class="form-control form-control-sm" value="35" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold mb-1">QR Size</label>
                                        <select id="cfgQrCellSize" class="form-select form-select-sm" onchange="updateLivePreview()">
                                            <option value="3">Size 3 (Medium)</option>
                                            <option value="2">Size 2 (Small)</option>
                                            <option value="4">Size 4 (Large)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Code Subtitle X</label>
                                        <input type="number" id="cfgCodeX" class="form-control form-control-sm" value="12" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Code Subtitle Y</label>
                                        <input type="number" id="cfgCodeY" class="form-control form-control-sm" value="125" oninput="updateLivePreview()">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white fw-bold py-2"><i class="bi bi-fonts me-1 text-info"></i>3. Text Positions (Right Column)</div>
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Right Text X Offset</label>
                                        <input type="number" id="cfgTextX" class="form-control form-control-sm" value="115" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">TSPL Font Size</label>
                                        <select id="cfgTextFont" class="form-select form-select-sm" onchange="updateLivePreview()">
                                            <option value="2">Font 2 (Standard)</option>
                                            <option value="1">Font 1 (Small)</option>
                                            <option value="3">Font 3 (Large)</option>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-semibold mb-1">Title Y</label>
                                        <input type="number" id="cfgTitleY" class="form-control form-control-sm" value="35" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-semibold mb-1">SKU Y</label>
                                        <input type="number" id="cfgSkuY" class="form-control form-control-sm" value="62" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-semibold mb-1">MRP Y</label>
                                        <input type="number" id="cfgMrpY" class="form-control form-control-sm" value="90" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-semibold mb-1">Disc Y</label>
                                        <input type="number" id="cfgDiscY" class="form-control form-control-sm" value="118" oninput="updateLivePreview()">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Live Visual Dual-Sticker Preview Box -->
                    <div class="col-lg-7 col-md-6 d-flex flex-column">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white fw-bold py-2 d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-eye me-1 text-primary"></i>Live Dual-Sticker Preview</span>
                                <span class="badge bg-primary-subtle text-primary border" id="previewDimensionBadge">Dual Roll 40mm × 25mm</span>
                            </div>
                            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center bg-secondary-subtle rounded m-2 overflow-auto">
                                <div id="liveStickerContainer" class="d-flex align-items-center gap-3 p-3 bg-white rounded border shadow-sm flex-wrap justify-content-center">
                                    <!-- Left Sticker #1 -->
                                    <div id="liveStickerCard1" class="bg-white border border-secondary-subtle rounded shadow-sm position-relative flex-shrink-0" style="width: 240px; height: 140px; font-family: 'Segoe UI', monospace; overflow: hidden; background: #fafafa;">
                                        <div id="badgeSticker1" class="badge bg-light text-dark position-absolute top-0 start-0 m-1 border shadow-xs" style="font-size: 9px; opacity: 0.85;">Left Sticker #1</div>
                                        <div id="prevQRBox1" class="position-absolute border border-dark rounded d-flex align-items-center justify-content-center bg-light text-muted fw-bold" style="left: 15px; top: 20px; width: 42px; height: 42px; font-size: 10px;">QR</div>
                                        <div id="prevCodeTxt1" class="position-absolute fw-bold text-dark" style="left: 15px; top: 90px; font-size: 9px;">WMF92601</div>
                                        <div id="prevTitleTxt1" class="position-absolute fw-bold text-primary" style="left: 80px; top: 20px; font-size: 9px;">GRM WAREHOUSE</div>
                                        <div id="prevSkuTxt1" class="position-absolute fw-bold text-dark" style="left: 80px; top: 38px; font-size: 9px;"><?= e($variant['sku']) ?></div>
                                        <div id="prevMrpTxt1" class="position-absolute text-secondary" style="left: 80px; top: 56px; font-size: 8.5px; text-decoration: line-through;">MRP: Rs.<?= number_format((float)$variant['mrp'], 2) ?></div>
                                        <div id="prevDiscTxt1" class="position-absolute fw-bold text-success" style="left: 80px; top: 74px; font-size: 8.5px;">Disc: Rs.<?= number_format((float)$variant['selling_price'], 2) ?></div>
                                    </div>

                                    <!-- Right Sticker #2 -->
                                    <div id="liveStickerCard2" class="bg-white border border-secondary-subtle rounded shadow-sm position-relative flex-shrink-0" style="width: 240px; height: 140px; font-family: 'Segoe UI', monospace; overflow: hidden; background: #fafafa;">
                                        <div id="badgeSticker2" class="badge bg-light text-dark position-absolute top-0 start-0 m-1 border shadow-xs" style="font-size: 9px; opacity: 0.85;">Right Sticker #2</div>
                                        <div id="prevQRBox2" class="position-absolute border border-dark rounded d-flex align-items-center justify-content-center bg-light text-muted fw-bold" style="left: 15px; top: 20px; width: 42px; height: 42px; font-size: 10px;">QR</div>
                                        <div id="prevCodeTxt2" class="position-absolute fw-bold text-dark" style="left: 15px; top: 90px; font-size: 9px;">WMF92602</div>
                                        <div id="prevTitleTxt2" class="position-absolute fw-bold text-primary" style="left: 80px; top: 20px; font-size: 9px;">GRM WAREHOUSE</div>
                                        <div id="prevSkuTxt2" class="position-absolute fw-bold text-dark" style="left: 80px; top: 38px; font-size: 9px;"><?= e($variant['sku']) ?></div>
                                        <div id="prevMrpTxt2" class="position-absolute text-secondary" style="left: 80px; top: 56px; font-size: 8.5px; text-decoration: line-through;">MRP: Rs.<?= number_format((float)$variant['mrp'], 2) ?></div>
                                        <div id="prevDiscTxt2" class="position-absolute fw-bold text-success" style="left: 80px; top: 74px; font-size: 8.5px;">Disc: Rs.<?= number_format((float)$variant['selling_price'], 2) ?></div>
                                    </div>
                                </div>
                                <div class="text-muted small mt-2 text-center" style="font-size: 0.8rem;">
                                    <i class="bi bi-info-circle me-1"></i>Simulating physical 2-column TSC thermal sticker roll layout.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-primary fw-semibold" onclick="testPrintCurrentSettings()">
                    <i class="bi bi-printer me-1"></i>Test Print 1 Sticker
                </button>
                <button type="button" class="btn btn-primary fw-semibold shadow-sm px-4" onclick="savePrinterSettings()">
                    <i class="bi bi-check-lg me-1"></i>Save Printer Configuration
                </button>
            </div>
        </div>
    </div>
</div>

<!-- STYLES FOR PRINTING & STICKER LAYOUT -->
<style>
/* Custom Multi-Color Tab Navigation (GREEN, RED, BLUE, PINK, YELLOW) */
#stockDetailTabs {
    border-bottom: none !important;
}
#stockDetailTabs .nav-link {
    color: #334155 !important;
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 0.5rem !important;
    font-weight: 600;
}

/* 1. GREEN TAB: Available Pieces */
#stockDetailTabs .nav-link.tab-green i { color: #16a34a !important; font-size: 1.05rem; }
#stockDetailTabs .nav-link.tab-green .badge { background-color: #dcfce7 !important; color: #15803d !important; border: 1px solid #bbf7d0 !important; }
#stockDetailTabs .nav-link.tab-green:hover { background-color: #f0fdf4 !important; border-color: #86efac !important; color: #15803d !important; }
#stockDetailTabs .nav-link.tab-green.active {
    background-color: #16a34a !important;
    border-color: #16a34a !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35) !important;
}
#stockDetailTabs .nav-link.tab-green.active i,
#stockDetailTabs .nav-link.tab-green.active span:not(.badge) { color: #ffffff !important; }
#stockDetailTabs .nav-link.tab-green.active .badge { background-color: #ffffff !important; color: #16a34a !important; border-color: #ffffff !important; font-weight: 700 !important; }

/* 2. RED TAB: Damaged & Quarantined */
#stockDetailTabs .nav-link.tab-red i { color: #dc2626 !important; font-size: 1.05rem; }
#stockDetailTabs .nav-link.tab-red .badge { background-color: #fee2e2 !important; color: #b91c1c !important; border: 1px solid #fecaca !important; }
#stockDetailTabs .nav-link.tab-red:hover { background-color: #fef2f2 !important; border-color: #fca5a5 !important; color: #b91c1c !important; }
#stockDetailTabs .nav-link.tab-red.active {
    background-color: #dc2626 !important;
    border-color: #dc2626 !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35) !important;
}
#stockDetailTabs .nav-link.tab-red.active i,
#stockDetailTabs .nav-link.tab-red.active span:not(.badge) { color: #ffffff !important; }
#stockDetailTabs .nav-link.tab-red.active .badge { background-color: #ffffff !important; color: #dc2626 !important; border-color: #ffffff !important; font-weight: 700 !important; }

/* 4. PINK TAB: Direct Sales / Sold Pieces */
#stockDetailTabs .nav-link.tab-pink i { color: #db2777 !important; font-size: 1.05rem; }
#stockDetailTabs .nav-link.tab-pink .badge { background-color: #fce7f3 !important; color: #be185d !important; border: 1px solid #fbcfe8 !important; }
#stockDetailTabs .nav-link.tab-pink:hover { background-color: #fdf2f8 !important; border-color: #f472b6 !important; color: #be185d !important; }
#stockDetailTabs .nav-link.tab-pink.active {
    background-color: #db2777 !important;
    border-color: #db2777 !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(219, 39, 119, 0.35) !important;
}
#stockDetailTabs .nav-link.tab-pink.active i,
#stockDetailTabs .nav-link.tab-pink.active span:not(.badge) { color: #ffffff !important; }
#stockDetailTabs .nav-link.tab-pink.active .badge { background-color: #ffffff !important; color: #db2777 !important; border-color: #ffffff !important; font-weight: 700 !important; }

/* 5. YELLOW / AMBER TAB: Customer Returns & Exchanges */
#stockDetailTabs .nav-link.tab-yellow i { color: #d97706 !important; font-size: 1.05rem; }
#stockDetailTabs .nav-link.tab-yellow .badge { background-color: #fef3c7 !important; color: #b45309 !important; border: 1px solid #fde68a !important; }
#stockDetailTabs .nav-link.tab-yellow:hover { background-color: #fffbeb !important; border-color: #fcd34d !important; color: #b45309 !important; }
#stockDetailTabs .nav-link.tab-yellow.active {
    background-color: #f59e0b !important;
    border-color: #f59e0b !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35) !important;
}
#stockDetailTabs .nav-link.tab-yellow.active i,
#stockDetailTabs .nav-link.tab-yellow.active span:not(.badge) { color: #ffffff !important; }
#stockDetailTabs .nav-link.tab-yellow.active .badge { background-color: #ffffff !important; color: #b45309 !important; border-color: #ffffff !important; font-weight: 700 !important; }

/* 6. PURPLE TAB: Price Revision History & Logs */
#stockDetailTabs .nav-link.tab-purple i { color: #7c3aed !important; font-size: 1.05rem; }
#stockDetailTabs .nav-link.tab-purple .badge { background-color: #ede9fe !important; color: #6d28d9 !important; border: 1px solid #ddd6fe !important; }
#stockDetailTabs .nav-link.tab-purple:hover { background-color: #f5f3ff !important; border-color: #c4b5fd !important; color: #6d28d9 !important; }
#stockDetailTabs .nav-link.tab-purple.active {
    background-color: #7c3aed !important;
    border-color: #7c3aed !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.35) !important;
}
#stockDetailTabs .nav-link.tab-purple.active i,
#stockDetailTabs .nav-link.tab-purple.active span:not(.badge) { color: #ffffff !important; }
#stockDetailTabs .nav-link.tab-purple.active .badge { background-color: #ffffff !important; color: #7c3aed !important; border-color: #ffffff !important; font-weight: 700 !important; }

.piece-card {
    transition: all 0.2s ease-in-out;
}
.piece-qr-box {
    position: relative;
    overflow: hidden;
}
.piece-qr-box canvas, #singleQRCanvas canvas {
    display: none !important;
}
.piece-qr-box img, #singleQRCanvas img {
    max-width: 100% !important;
    max-height: 100% !important;
    display: block !important;
    object-fit: contain;
    margin: 0 auto !important;
}
.btn-xs {
    padding: 0.2rem 0.4rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}

/* Printable Sticker Template - Normal 2-Column Garment Tag Layout */
.printable-stickers-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 14px;
}
.stock-sticker-item {
    border: 1.5px dashed #0f172a;
    border-radius: 8px;
    padding: 10px 12px;
    background: #fff;
    page-break-inside: avoid;
    break-inside: avoid;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 12px;
    min-height: 110px;
    text-align: left;
}
.stock-sticker-left {
    flex-shrink: 0;
    text-align: center;
}
.stock-sticker-qr-box {
    width: 72px;
    height: 72px;
    background: #fff;
    padding: 2px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.stock-sticker-qr-box canvas {
    display: none !important;
}
.stock-sticker-qr-box img {
    max-width: 100% !important;
    max-height: 100% !important;
    display: block !important;
    object-fit: contain;
    margin: 0 auto !important;
}
.stock-sticker-tag-badge {
    background: #0f172a;
    color: #fff;
    font-family: monospace;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 4px;
    border-radius: 4px;
    margin-top: 4px;
    display: block;
    text-align: center;
    letter-spacing: 0.5px;
}
.stock-sticker-right {
    flex-grow: 1;
    overflow: hidden;
}
.stock-sticker-title {
    font-size: 12px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.stock-sticker-meta {
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.stock-sticker-sku-meta {
    font-size: 10px;
    color: #64748b;
    font-family: monospace;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.stock-sticker-prices {
    margin-top: 4px;
    padding-top: 4px;
    border-top: 1px solid #f1f5f9;
}
.stock-sticker-mrp {
    font-size: 10.5px;
    color: #94a3b8;
    text-decoration: line-through;
    line-height: 1.2;
}
.stock-sticker-price {
    font-size: 12.5px;
    font-weight: 800;
    color: #166534;
    line-height: 1.2;
}

@media print {
    /* Hide everything except printable sticker area */
    body * {
        visibility: hidden !important;
    }
    #stockStickerPrintModal,
    #stockStickerPrintModal .modal-dialog,
    #stockStickerPrintModal .modal-content,
    #stockStickerPrintModal .modal-body,
    #printStickerArea,
    #printStickerArea * {
        visibility: visible !important;
    }
    #stockStickerPrintModal {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
    }
    #stockStickerPrintModal .modal-content {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
    }
    .no-print, .modal-header, .modal-footer, .btn-close {
        display: none !important;
    }
    .printable-stickers-container {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 6mm !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
    }
    .stock-sticker-item {
        border: 1px solid #000 !important;
        border-radius: 4px !important;
        padding: 3mm !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
    }
}
</style>

<!-- QRCode Library -->
<script src="<?= asset('js/qrcode.min.js') ?>"></script>
<script>
if (typeof QRCode === 'undefined') {
    document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>');
}
</script>
<script>
const PIECE_RECORDS = <?= json_encode($pieceQRs ?? []) ?>;
const DAMAGED_PIECE_RECORDS = <?= json_encode($damagedPieceList ?? []) ?>;
const RETURNED_PIECE_RECORDS = <?= json_encode($returnedPieceList ?? []) ?>;
const PRODUCT_INFO = <?= json_encode([
    'name' => $variant['product_name'] ?? 'Garment Item',
    'sku'  => $variant['sku'] ?? '',
    'size' => $variant['size_name'] ?? 'Free',
    'color'=> $variant['color_name'] ?? 'Std',
    'mrp'  => number_format((float)($variant['mrp'] > 0 ? $variant['mrp'] : $variant['selling_price']), 2),
    'selling_price' => number_format((float)($variant['selling_price'] > 0 ? $variant['selling_price'] : $variant['mrp']), 2),
    'brand'=> 'GRM APPARELS'
]) ?>;

function renderPieceQRCodes() {
    if (typeof QRCode === 'undefined') {
        setTimeout(renderPieceQRCodes, 150);
        return;
    }
    document.querySelectorAll('.piece-qr-box').forEach(function(box) {
        if (box.dataset.rendered === '1') return;
        const text = box.getAttribute('data-json');
        if (text) {
            box.innerHTML = '';
            try {
                new QRCode(box, {
                    text: text,
                    width: 66,
                    height: 66,
                    colorDark : "#0f172a",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.M
                });
                box.dataset.rendered = '1';
            } catch (e) {
                console.warn('QR render error:', e);
            }
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderPieceQRCodes);
} else {
    renderPieceQRCodes();
}
window.addEventListener('load', renderPieceQRCodes);

// Re-render QR codes when switching tabs
document.querySelectorAll('#stockDetailTabs button[data-bs-toggle="pill"]').forEach(function(tabEl) {
    tabEl.addEventListener('shown.bs.tab', function() {
        setTimeout(renderPieceQRCodes, 50);
    });
});

let currentPreviewIndex = 0;
let currentPreviewType = 'available';

/**
 * Preview Single 2D QR Code Tag (Image 3 Style)
 */
function previewSingleTag(index) {
    if (!PIECE_RECORDS || !PIECE_RECORDS[index]) return;
    currentPreviewIndex = index;
    currentPreviewType = 'available';
    const item = PIECE_RECORDS[index];

    const fmtMrp = (item.mrp == parseInt(item.mrp)) ? parseInt(item.mrp) : parseFloat(item.mrp).toFixed(2);
    const fmtSelling = (item.selling_price == parseInt(item.selling_price)) ? parseInt(item.selling_price) : parseFloat(item.selling_price).toFixed(2);

    const codeBadgeEl = document.getElementById('previewDamageCode');
    codeBadgeEl.textContent = item.tag_code;
    codeBadgeEl.className = 'badge bg-dark text-white font-monospace mt-1 px-2 py-1 w-100';

    document.getElementById('previewBrandName').textContent = item.brand_name || 'GRM WAREHOUSE';

    const catContainer = document.getElementById('previewCategoryHierarchyContainer');
    const catText = document.getElementById('previewCategoryHierarchyText');
    if (catContainer && catText) {
        if (item.category_hierarchy) {
            catText.textContent = item.category_hierarchy;
            catContainer.style.display = 'block';
        } else {
            catContainer.style.display = 'none';
        }
    }
    document.getElementById('previewAttrText').textContent = `${item.size_name}-${item.color_name}`;
    
    const mrpEl = document.getElementById('previewBasePrice');
    if (parseFloat(item.mrp) > parseFloat(item.selling_price)) {
        mrpEl.style.display = 'block';
        mrpEl.textContent = `MRP: ₹${fmtMrp}`;
    } else {
        mrpEl.style.display = 'none';
    }
    document.getElementById('previewDiscPrice').textContent = `Discount Price: ₹${fmtSelling}`;

    const qrContainer = document.getElementById('singleQRCanvas');
    qrContainer.innerHTML = '';
    const qrPayload = item.tag_code;

    try {
        new QRCode(qrContainer, {
            text: qrPayload,
            width: 78,
            height: 78,
            colorDark: "#0f172a",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    } catch (e) {
        console.warn('Single QR error:', e);
    }

    const modal = new bootstrap.Modal(document.getElementById('singleQRModal'));
    modal.show();
}

/**
 * Preview Single Damaged 2D QR Code Tag
 */
function previewSingleDamagedTag(index) {
    if (!DAMAGED_PIECE_RECORDS || !DAMAGED_PIECE_RECORDS[index]) return;
    currentPreviewIndex = index;
    currentPreviewType = 'damaged';
    const item = DAMAGED_PIECE_RECORDS[index];

    const fmtMrp = (item.mrp == parseInt(item.mrp)) ? parseInt(item.mrp) : parseFloat(item.mrp).toFixed(2);
    const fmtSelling = (item.selling_price == parseInt(item.selling_price)) ? parseInt(item.selling_price) : parseFloat(item.selling_price).toFixed(2);

    document.getElementById('previewDamageCode').textContent = item.tag_code;
    document.getElementById('previewBrandName').textContent = (item.brand_name || 'GRM WAREHOUSE') + ' [DAMAGED]';
    document.getElementById('previewAttrText').textContent = `${item.size_name}-${item.color_name} (${item.damage_reason || 'Defect'})`;
    
    const mrpEl = document.getElementById('previewBasePrice');
    mrpEl.style.display = 'block';
    mrpEl.textContent = `MRP: ₹${fmtMrp}`;
    document.getElementById('previewDiscPrice').textContent = `Status: QUARANTINED / DAMAGED`;

    const qrContainer = document.getElementById('singleQRCanvas');
    qrContainer.innerHTML = '';
    const qrPayload = item.tag_code;

    try {
        new QRCode(qrContainer, {
            text: qrPayload,
            width: 78,
            height: 78,
            colorDark: "#b91c1c",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    } catch (e) {
        console.warn('Single Damaged QR error:', e);
    }

    const modal = new bootstrap.Modal(document.getElementById('singleQRModal'));
    modal.show();
}

/**
 * Preview Single Returned 2D QR Code Tag
 */
function previewSingleReturnedTag(index) {
    if (!RETURNED_PIECE_RECORDS || !RETURNED_PIECE_RECORDS[index]) return;
    currentPreviewIndex = index;
    currentPreviewType = 'returned';
    const item = RETURNED_PIECE_RECORDS[index];

    const fmtMrp = (item.mrp == parseInt(item.mrp)) ? parseInt(item.mrp) : parseFloat(item.mrp).toFixed(2);
    const fmtSelling = (item.selling_price == parseInt(item.selling_price)) ? parseInt(item.selling_price) : parseFloat(item.selling_price).toFixed(2);

    document.getElementById('previewDamageCode').textContent = item.tag_code;
    document.getElementById('previewBrandName').textContent = (item.brand_name || 'GRM WAREHOUSE') + ' [RETURNED]';
    document.getElementById('previewAttrText').textContent = `${item.size_name}-${item.color_name} (${item.item_condition === 'GOOD' ? 'Restocked' : 'Damaged'})`;
    
    const mrpEl = document.getElementById('previewBasePrice');
    if (parseFloat(item.mrp) > parseFloat(item.selling_price)) {
        mrpEl.style.display = 'block';
        mrpEl.textContent = `MRP: ₹${fmtMrp}`;
    } else {
        mrpEl.style.display = 'none';
    }
    document.getElementById('previewDiscPrice').textContent = `Return: ${item.return_number} · ₹${fmtSelling}`;

    const qrContainer = document.getElementById('singleQRCanvas');
    qrContainer.innerHTML = '';
    const qrPayload = item.tag_code;

    try {
        new QRCode(qrContainer, {
            text: qrPayload,
            width: 78,
            height: 78,
            colorDark: "#d97706",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    } catch (e) {
        console.warn('Single Returned QR error:', e);
    }

    const modal = new bootstrap.Modal(document.getElementById('singleQRModal'));
    modal.show();
}

/**
 * Print Single Piece directly from the 2D QR Code Tag Preview Modal
 */
function printSinglePieceFromModal() {
    if (currentPreviewType === 'damaged') {
        printSingleDamagedPiece(currentPreviewIndex);
    } else if (currentPreviewType === 'returned') {
        printSinglePiece(currentPreviewIndex);
    } else {
        printSinglePiece(currentPreviewIndex);
    }
}

/**
 * Direct TSPL Thermal Print Function (Port 9191) with mode & pieceIndex support
 */
async function printLabel(labels, mode = 'all', pieceIndex = 0) {
    try {
        const response = await fetch('http://127.0.0.1:9191/print', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ labels: labels })
        });

        if (response.ok) {
            const result = await response.json();
            showPrintToast(result.message || 'Printed successfully to TSC TE244!', 'success');
        } else {
            const err = await response.json().catch(() => ({}));
            showPrintToast(`Printer helper: ${err.error || response.statusText}. Opening print preview...`, 'warning');
            openPrintModal(mode, pieceIndex);
        }
    } catch (e) {
        console.warn('Local print service not responding on port 9191. Falling back to browser print preview.', e);
        showPrintToast('Opening print preview for QR sticker...', 'info');
        openPrintModal(mode, pieceIndex);
    }
}

/**
 * Print all available stock pieces directly
 */
function printAllPiecesDirectly() {
    const availablePieces = (PIECE_RECORDS || []).filter(item => !item.is_reserved);
    if (availablePieces.length === 0) {
        alert('No available pieces to print. Reserved allocation pieces cannot be printed as available stock.');
        return;
    }

    const batchLabels = availablePieces.map(item => ({
        sku: item.sku,
        basePrice: parseFloat(item.mrp || 0).toFixed(2),
        discountPrice: parseFloat(item.selling_price || item.mrp || 0).toFixed(2),
        damageCode: item.tag_code,
        title: item.brand_name || "GRM WAREHOUSE",
        quantity: 1
    }));

    printLabel(batchLabels, 'all', 0);
}

/**
 * Print all damaged stock pieces directly
 */
function printAllDamagedPiecesDirectly() {
    if (!DAMAGED_PIECE_RECORDS || DAMAGED_PIECE_RECORDS.length === 0) {
        alert('No damaged pieces available to print.');
        return;
    }

    const batchLabels = DAMAGED_PIECE_RECORDS.map(item => ({
        sku: item.sku,
        basePrice: parseFloat(item.mrp || 0).toFixed(2),
        discountPrice: parseFloat(item.selling_price || item.mrp || 0).toFixed(2),
        damageCode: item.tag_code,
        title: (item.brand_name || "GRM WAREHOUSE") + " [DAMAGED]",
        quantity: 1
    }));

    printLabel(batchLabels, 'damaged_all', 0);
}

/**
 * Print a single piece QR sticker directly
 */
function printSinglePiece(index) {
    if (!PIECE_RECORDS[index]) return;
    const item = PIECE_RECORDS[index];
    if (item.is_reserved) return;

    const labelPayload = [{
        sku: item.sku,
        basePrice: parseFloat(item.mrp || 0).toFixed(2),
        discountPrice: parseFloat(item.selling_price || item.mrp || 0).toFixed(2),
        damageCode: item.tag_code,
        title: item.brand_name || "GRM WAREHOUSE",
        quantity: 1
    }];

    printLabel(labelPayload, 'single', index);
}

/**
 * Print a single damaged piece QR sticker directly
 */
function printSingleDamagedPiece(index) {
    if (!DAMAGED_PIECE_RECORDS[index]) return;
    const item = DAMAGED_PIECE_RECORDS[index];

    const labelPayload = [{
        sku: item.sku,
        basePrice: parseFloat(item.mrp || 0).toFixed(2),
        discountPrice: parseFloat(item.selling_price || item.mrp || 0).toFixed(2),
        damageCode: item.tag_code,
        title: (item.brand_name || "GRM WAREHOUSE") + " [DAMAGED]",
        quantity: 1
    }];

    printLabel(labelPayload, 'damaged_single', index);
}

/**
 * Open browser print modal for all pieces or a single piece (Normal 2-Column QR Garment Tag Layout)
 */
function openPrintModal(mode = 'all', pieceIndex = 0) {
    const printArea = document.getElementById('printStickerArea');
    printArea.innerHTML = '';

    let itemsToRender = [];
    const modalTitle = document.getElementById('stockStickerPrintModalLabel');

    if (mode === 'single') {
        const item = PIECE_RECORDS[pieceIndex];
        if (item) {
            itemsToRender = [item];
            if (modalTitle) {
                modalTitle.innerHTML = `<i class="bi bi-qr-code me-2 text-warning"></i>Print Single Piece QR Sticker: <span class="badge bg-warning text-dark font-monospace">${item.tag_code}</span> (Piece #${item.piece_no})`;
            }
        }
    } else if (mode === 'damaged_single') {
        const item = DAMAGED_PIECE_RECORDS[pieceIndex];
        if (item) {
            itemsToRender = [item];
            if (modalTitle) {
                modalTitle.innerHTML = `<i class="bi bi-exclamation-octagon me-2 text-danger"></i>Print Damaged Piece QR Sticker: <span class="badge bg-danger text-white font-monospace">${item.tag_code}</span>`;
            }
        }
    } else if (mode === 'damaged_all') {
        itemsToRender = DAMAGED_PIECE_RECORDS;
        if (modalTitle) {
            modalTitle.innerHTML = `<i class="bi bi-printer me-2 text-danger"></i>Print All Damaged Stickers (${DAMAGED_PIECE_RECORDS.length} Pieces)`;
        }
    } else {
        itemsToRender = PIECE_RECORDS;
        if (modalTitle) {
            modalTitle.innerHTML = `<i class="bi bi-printer me-2 text-warning"></i>Print All Stock QR Stickers (${PIECE_RECORDS.length} Pieces)`;
        }
    }

    if (itemsToRender.length === 0) {
        printArea.innerHTML = '<div class="text-center py-4 text-muted">No sticker records available to print.</div>';
    } else {
        itemsToRender.forEach((item, idx) => {
            const fmtMrp = (item.mrp == parseInt(item.mrp)) ? parseInt(item.mrp) : parseFloat(item.mrp).toFixed(2);
            const fmtSelling = (item.selling_price == parseInt(item.selling_price)) ? parseInt(item.selling_price) : parseFloat(item.selling_price).toFixed(2);
            const qrPayload = item.tag_code;
            const isDiscounted = parseFloat(item.mrp) > parseFloat(item.selling_price);

            const stickerDiv = document.createElement('div');
            stickerDiv.className = 'stock-sticker-item';
            stickerDiv.innerHTML = `
                <div class="stock-sticker-left">
                    <div class="stock-sticker-qr-box">
                        <div id="print-qr-${idx}"></div>
                    </div>
                    <span class="stock-sticker-tag-badge">${item.tag_code}</span>
                </div>
                <div class="stock-sticker-right">
                    <div class="stock-sticker-title">${item.brand_name || (typeof PRODUCT_INFO !== 'undefined' ? PRODUCT_INFO.brand : 'GRM WAREHOUSE')}</div>
                    ${item.category_hierarchy ? `<div class="stock-sticker-cat" style="font-size: 8px; color: #475569; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><i class="bi bi-diagram-3-fill me-1 text-warning"></i>${item.category_hierarchy}</div>` : ''}
                    <div class="stock-sticker-meta">${item.size_name} - ${item.color_name}</div>
                    <div class="stock-sticker-sku-meta">SKU: ${item.sku}</div>
                    <div class="stock-sticker-prices">
                        ${isDiscounted ? `<div class="stock-sticker-mrp">MRP: ₹${parseFloat(item.mrp).toFixed(2)}</div>` : ''}
                        <div class="stock-sticker-price">Discount Price: ₹${fmtSelling}</div>
                    </div>
                </div>
            `;
            printArea.appendChild(stickerDiv);

            setTimeout(() => {
                const qrContainer = document.getElementById(`print-qr-${idx}`);
                if (qrContainer && typeof QRCode !== 'undefined') {
                    try {
                        new QRCode(qrContainer, {
                            text: qrPayload,
                            width: 66,
                            height: 66,
                            colorDark : "#000000",
                            colorLight : "#ffffff",
                            correctLevel : QRCode.CorrectLevel.M
                        });
                    } catch (e) {
                        console.warn('Print QR render error:', e);
                    }
                }
            }, 50);
        });
    }

    const modalEl = document.getElementById('stockStickerPrintModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

/**
 * Copy piece tag code to clipboard
 */
function copyTagCode(code, btn) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(() => {
            const origText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2 text-success me-1"></i> Copied!';
            btn.classList.replace('btn-outline-secondary', 'btn-success');
            btn.classList.add('text-white');
            setTimeout(() => {
                btn.innerHTML = origText;
                btn.classList.replace('btn-success', 'btn-outline-secondary');
                btn.classList.remove('text-white');
            }, 1500);
        });
    }
}

/**
 * Toast notifications
 */
function showPrintToast(message, type = 'success') {
    let container = document.getElementById('printToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'printToastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    const toastId = 'toast_' + Date.now();
    const bgClass = type === 'success' ? 'bg-success text-white' : (type === 'warning' ? 'bg-warning text-dark' : 'bg-info text-white');
    const icon = type === 'success' ? 'bi-check-circle-fill' : (type === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2 fs-6">
                    <i class="bi ${icon} fs-5"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close ${type === 'warning' ? '' : 'btn-close-white'} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = document.getElementById(toastId);
    if (window.bootstrap && bootstrap.Toast) {
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
    } else {
        setTimeout(() => toastEl.remove(), 4000);
    }
}

/**
 * Open Printer Settings Modal
 */
async function openPrinterSettingsModal() {
    try {
        const response = await fetch('http://127.0.0.1:9191/config');
        if (response.ok) {
            const data = await response.json();
            if (data && data.config) {
                const cfg = data.config;
                if (document.getElementById('cfgWidth')) document.getElementById('cfgWidth').value = cfg.labelWidthMm || 40;
                if (document.getElementById('cfgHeight')) document.getElementById('cfgHeight').value = cfg.labelHeightMm || 25;
                if (document.getElementById('cfgColumns')) document.getElementById('cfgColumns').value = cfg.columns || 2;
                if (document.getElementById('cfgGap')) document.getElementById('cfgGap').value = cfg.gapMm || 2;
                if (document.getElementById('cfgCol2ShiftDots')) document.getElementById('cfgCol2ShiftDots').value = cfg.col2ShiftDots !== undefined ? cfg.col2ShiftDots : 0;
                if (document.getElementById('cfgQrX')) document.getElementById('cfgQrX').value = cfg.qrX !== undefined ? cfg.qrX : 12;
                if (document.getElementById('cfgQrY')) document.getElementById('cfgQrY').value = cfg.qrY !== undefined ? cfg.qrY : 35;
                if (document.getElementById('cfgQrCellSize')) document.getElementById('cfgQrCellSize').value = cfg.qrCellSize || 3;
                if (document.getElementById('cfgCodeX')) document.getElementById('cfgCodeX').value = cfg.codeX !== undefined ? cfg.codeX : 12;
                if (document.getElementById('cfgCodeY')) document.getElementById('cfgCodeY').value = cfg.codeY !== undefined ? cfg.codeY : 125;
                if (document.getElementById('cfgTextX')) document.getElementById('cfgTextX').value = cfg.textX !== undefined ? cfg.textX : 115;
                if (document.getElementById('cfgTextFont')) document.getElementById('cfgTextFont').value = cfg.textFont || "2";
                if (document.getElementById('cfgTitleY')) document.getElementById('cfgTitleY').value = cfg.titleY !== undefined ? cfg.titleY : 35;
                if (document.getElementById('cfgSkuY')) document.getElementById('cfgSkuY').value = cfg.skuY !== undefined ? cfg.skuY : 62;
                if (document.getElementById('cfgMrpY')) document.getElementById('cfgMrpY').value = cfg.mrpY !== undefined ? cfg.mrpY : 90;
                if (document.getElementById('cfgDiscY')) document.getElementById('cfgDiscY').value = cfg.discY !== undefined ? cfg.discY : 118;
            }
        }
    } catch (e) {
        console.warn('Could not fetch printer config:', e);
    }

    updateLivePreview();
    const modal = new bootstrap.Modal(document.getElementById('printerSettingsModal'));
    modal.show();
}

/**
 * Update Live Visual Sticker Layout Preview Box (Dual Sticker Roll)
 */
function updateLivePreview() {
    const widthMm = parseFloat(document.getElementById('cfgWidth')?.value || 40);
    const heightMm = parseFloat(document.getElementById('cfgHeight')?.value || 25);
    const columns = parseInt(document.getElementById('cfgColumns')?.value || 2);
    const col2ShiftDots = parseFloat(document.getElementById('cfgCol2ShiftDots')?.value || 0);

    const badge = document.getElementById('previewDimensionBadge');
    if (badge) badge.textContent = `${columns === 2 ? 'Dual Roll ' : ''}${widthMm}mm × ${heightMm}mm`;

    const card1 = document.getElementById('liveStickerCard1');
    const card2 = document.getElementById('liveStickerCard2');
    if (!card1) return;

    const scale = 240 / widthMm;
    const cardPxWidth = 240;
    const cardPxHeight = Math.max(100, Math.round(heightMm * scale));

    card1.style.width = `${cardPxWidth}px`;
    card1.style.height = `${cardPxHeight}px`;

    if (card2) {
        if (columns === 1) {
            card2.style.display = 'none';
        } else {
            card2.style.display = 'block';
            card2.style.width = `${cardPxWidth}px`;
            card2.style.height = `${cardPxHeight}px`;
        }
    }

    const badge2 = document.getElementById('badgeSticker2');
    if (badge2) {
        if (col2ShiftDots !== 0) {
            badge2.textContent = `Right Sticker #2 (${col2ShiftDots > 0 ? '+' : ''}${col2ShiftDots} dots)`;
            badge2.className = 'badge bg-primary text-white position-absolute top-0 start-0 m-1 border';
        } else {
            badge2.textContent = 'Right Sticker #2';
            badge2.className = 'badge bg-light text-dark position-absolute top-0 start-0 m-1 border';
        }
    }

    const dotScale = scale / 8.12;

    const qrX = parseFloat(document.getElementById('cfgQrX')?.value || 12) * dotScale;
    const qrY = parseFloat(document.getElementById('cfgQrY')?.value || 35) * dotScale;
    const qrSize = parseInt(document.getElementById('cfgQrCellSize')?.value || 3);
    const qrPxDim = Math.max(20, Math.round(qrSize * 8.5 * (scale / 4)));

    const codeX = parseFloat(document.getElementById('cfgCodeX')?.value || 12) * dotScale;
    const codeY = parseFloat(document.getElementById('cfgCodeY')?.value || 125) * dotScale;

    const textX = parseFloat(document.getElementById('cfgTextX')?.value || 115) * dotScale;
    const titleY = parseFloat(document.getElementById('cfgTitleY')?.value || 35) * dotScale;
    const skuY = parseFloat(document.getElementById('cfgSkuY')?.value || 62) * dotScale;
    const mrpY = parseFloat(document.getElementById('cfgMrpY')?.value || 90) * dotScale;
    const discY = parseFloat(document.getElementById('cfgDiscY')?.value || 118) * dotScale;

    // Apply to Left Card
    const qb1 = document.getElementById('prevQRBox1');
    if (qb1) { qb1.style.left = `${qrX}px`; qb1.style.top = `${qrY}px`; qb1.style.width = `${qrPxDim}px`; qb1.style.height = `${qrPxDim}px`; }

    const ct1 = document.getElementById('prevCodeTxt1');
    if (ct1) { ct1.style.left = `${codeX}px`; ct1.style.top = `${codeY}px`; }

    const tt1 = document.getElementById('prevTitleTxt1');
    if (tt1) { tt1.style.left = `${textX}px`; tt1.style.top = `${titleY}px`; }

    const st1 = document.getElementById('prevSkuTxt1');
    if (st1) { st1.style.left = `${textX}px`; st1.style.top = `${skuY}px`; }

    const mt1 = document.getElementById('prevMrpTxt1');
    if (mt1) { mt1.style.left = `${textX}px`; mt1.style.top = `${mrpY}px`; }

    const dt1 = document.getElementById('prevDiscTxt1');
    if (dt1) { dt1.style.left = `${textX}px`; dt1.style.top = `${discY}px`; }

    // Apply to Right Card
    const col2ShiftPx = col2ShiftDots * dotScale;
    const qb2 = document.getElementById('prevQRBox2');
    if (qb2) { qb2.style.left = `${qrX + col2ShiftPx}px`; qb2.style.top = `${qrY}px`; qb2.style.width = `${qrPxDim}px`; qb2.style.height = `${qrPxDim}px`; }

    const ct2 = document.getElementById('prevCodeTxt2');
    if (ct2) { ct2.style.left = `${codeX + col2ShiftPx}px`; ct2.style.top = `${codeY}px`; }

    const tt2 = document.getElementById('prevTitleTxt2');
    if (tt2) { tt2.style.left = `${textX + col2ShiftPx}px`; tt2.style.top = `${titleY}px`; }

    const st2 = document.getElementById('prevSkuTxt2');
    if (st2) { st2.style.left = `${textX + col2ShiftPx}px`; st2.style.top = `${skuY}px`; }

    const mt2 = document.getElementById('prevMrpTxt2');
    if (mt2) { mt2.style.left = `${textX + col2ShiftPx}px`; mt2.style.top = `${mrpY}px`; }

    const dt2 = document.getElementById('prevDiscTxt2');
    if (dt2) { dt2.style.left = `${textX + col2ShiftPx}px`; dt2.style.top = `${discY}px`; }
}

/**
 * Save Printer Settings
 */
async function savePrinterSettings() {
    const configData = {
        labelWidthMm: parseFloat(document.getElementById('cfgWidth')?.value || 40),
        labelHeightMm: parseFloat(document.getElementById('cfgHeight')?.value || 25),
        columns: parseInt(document.getElementById('cfgColumns')?.value || 2),
        gapMm: parseFloat(document.getElementById('cfgGap')?.value || 2),
        col2ShiftDots: parseFloat(document.getElementById('cfgCol2ShiftDots')?.value || 0),
        qrX: parseInt(document.getElementById('cfgQrX')?.value || 12),
        qrY: parseInt(document.getElementById('cfgQrY')?.value || 35),
        qrCellSize: parseInt(document.getElementById('cfgQrCellSize')?.value || 3),
        codeX: parseInt(document.getElementById('cfgCodeX')?.value || 12),
        codeY: parseInt(document.getElementById('cfgCodeY')?.value || 125),
        textX: parseInt(document.getElementById('cfgTextX')?.value || 115),
        textFont: document.getElementById('cfgTextFont')?.value || "2",
        titleY: parseInt(document.getElementById('cfgTitleY')?.value || 35),
        skuY: parseInt(document.getElementById('cfgSkuY')?.value || 62),
        mrpY: parseInt(document.getElementById('cfgMrpY')?.value || 90),
        discY: parseInt(document.getElementById('cfgDiscY')?.value || 118),
    };

    try {
        const response = await fetch('http://127.0.0.1:9191/config', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(configData)
        });

        if (response.ok) {
            showPrintToast('Printer settings saved successfully!', 'success');
            const modalEl = document.getElementById('printerSettingsModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        } else {
            showPrintToast('Could not save printer configuration.', 'warning');
        }
    } catch (e) {
        showPrintToast('Printer service not responding on port 9191.', 'warning');
    }
}

/**
 * Test Print 1 Sticker
 */
function testPrintCurrentSettings() {
    printLabel([
        {
            sku: PRODUCT_INFO.sku || "TEST-SKU",
            basePrice: String(PRODUCT_INFO.mrp || "999.00"),
            discountPrice: String(PRODUCT_INFO.mrp || "799.00"),
            damageCode: "TST01",
            title: "GRM WAREHOUSE",
            quantity: 1
        }
    ]);
}

// =========================================================================
// REAL-TIME SEARCH FOR PIECES, DAMAGED STOCK, BATCHES & LEDGER BY CODE
// =========================================================================
function filterAvailablePieceCards(query) {
    const term = (query || '').toLowerCase().trim();
    const clearBtn = document.getElementById('btnClearPieceCodeSearch');
    if (clearBtn) clearBtn.style.display = term ? '' : 'none';

    const cards = document.querySelectorAll('#availablePieceCardsGrid .piece-card-col');
    let matchCount = 0;
    let availableMatchCount = 0;
    let reservedMatchCount = 0;
    let firstMatch = null;

    cards.forEach(card => {
        const searchText = card.getAttribute('data-search') || '';
        const tagCode = card.getAttribute('data-tag-code') || '';
        const isMatch = !term || searchText.includes(term) || tagCode.toLowerCase().includes(term);

        if (isMatch) {
            card.style.display = '';
            matchCount++;
            if (card.dataset.reserved === '1') {
                reservedMatchCount++;
            } else {
                availableMatchCount++;
            }
            if (!firstMatch && term) firstMatch = card;
        } else {
            card.style.display = 'none';
        }
    });

    const badge = document.getElementById('pieceCardsFilterCountBadge');
    if (badge) {
        if (!term) {
            badge.innerHTML = `<i class="bi bi-qr-code me-1 text-primary"></i>${cards.length} pieces · ${availableMatchCount} available · ${reservedMatchCount} reserved`;
            badge.className = 'badge bg-white text-dark border font-monospace py-2 px-3 shadow-xs';
        } else {
            badge.innerHTML = `<i class="bi bi-search me-1 text-success"></i>Found ${matchCount} · ${availableMatchCount} available · ${reservedMatchCount} reserved`;
            badge.className = 'badge bg-success-subtle text-success border border-success-subtle font-monospace py-2 px-3 shadow-xs';
        }
    }

    const noAlert = document.getElementById('noPieceCardsFoundAlert');
    if (noAlert) {
        noAlert.style.display = (matchCount === 0 && cards.length > 0) ? '' : 'none';
    }

    // If single match or exact code match, auto-scroll and subtle highlight
    if (term && matchCount === 1 && firstMatch) {
        firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstMatch.classList.add('border-primary', 'shadow-lg');
        setTimeout(() => firstMatch.classList.remove('border-primary', 'shadow-lg'), 1800);
    }
}

function clearAvailablePieceSearch() {
    const inp = document.getElementById('pieceCodeSearchInput');
    if (inp) { inp.value = ''; inp.focus(); }
    filterAvailablePieceCards('');
}

function filterDamagedPieceCards(query) {
    const term = (query || '').toLowerCase().trim();
    const cards = document.querySelectorAll('.damaged-piece-col');
    let matchCount = 0;
    cards.forEach(card => {
        const searchText = card.getAttribute('data-search') || '';
        const isMatch = !term || searchText.includes(term);
        card.style.display = isMatch ? '' : 'none';
        if (isMatch) matchCount++;
    });
    const badge = document.getElementById('damagedPieceCountBadge');
    if (badge) badge.textContent = `${matchCount} of ${cards.length} pieces`;
}

function filterLedgerRows(query) {
    const term = (query || '').toLowerCase().trim();
    document.querySelectorAll('.ledger-tx-row').forEach(r => {
        const text = r.textContent.toLowerCase();
        r.style.display = (!term || text.includes(term)) ? '' : 'none';
    });
}
</script>
