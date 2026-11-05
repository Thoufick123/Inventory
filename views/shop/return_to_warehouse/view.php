<?php
// views/shop/return_to_warehouse/view.php — Return to Warehouse Consignment Manifest & Inspection View
?>
<div class="container-fluid py-4">
    <!-- Top Action Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div class="d-flex align-items-center">
            <a href="<?= url('shop/return-to-warehouse') ?>" class="btn btn-sm btn-light border rounded-circle me-3" title="Back to Consignments">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-warning-subtle text-warning-emphasis fw-semibold">
                        <i class="bi bi-arrow-return-left me-1"></i>Return to Warehouse
                    </span>
                    <?php
                        $statusClass = match($return['status']) {
                            'RECEIVED'   => 'bg-warning-subtle text-warning-emphasis',
                            'INSPECTING' => 'bg-info-subtle text-info-emphasis',
                            'CLASSIFIED' => 'bg-primary-subtle text-primary',
                            'COMPLETED'  => 'bg-success-subtle text-success',
                            default      => 'bg-secondary-subtle text-secondary'
                        };
                    ?>
                    <span class="badge <?= $statusClass ?> fw-semibold px-3 py-1 rounded-pill">
                        <?= e($return['status']) ?>
                    </span>
                </div>
                <h3 class="fw-bold mb-0 text-dark font-monospace"><?= e($return['return_number']) ?></h3>
            </div>
        </div>
        <div class="d-flex gap-2">
            <?php if (hasPermission('return_to_warehouse', 'print')): ?>
            <a href="<?= url('shop/return-to-warehouse/label/' . $return['id']) ?>" target="_blank" class="btn btn-outline-secondary rounded-pill px-3 btn-sm fw-semibold">
                <i class="bi bi-qr-code me-1"></i> Print Carton QR Sticker
            </a>
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 btn-sm shadow-sm fw-semibold">
                <i class="bi bi-printer-fill me-1"></i> Print Manifest
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 4 Overview Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Originating Shop Info -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <h6 class="fw-bold text-muted text-uppercase mb-2" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="bi bi-shop me-1 text-primary"></i> Originating Shop
                    </h6>
                    <div class="mb-2">
                        <small class="text-muted d-block" style="font-size: 10px;">Retail Store</small>
                        <strong class="text-dark fs-6"><?= e($return['shop_name'] ?? 'Flagship Retail Store') ?></strong>
                        <span class="badge bg-light text-dark border font-monospace ms-1"><?= e($return['shop_code'] ?? 'SH-01') ?></span>
                    </div>
                    <?php if (!empty($return['shop_address'])): ?>
                        <div class="small text-muted mb-2 text-truncate" style="font-size: 11px;" title="<?= e($return['shop_address']) ?>"><i class="bi bi-geo-alt me-1"></i><?= e($return['shop_address']) ?></div>
                    <?php endif; ?>
                    <div>
                        <small class="text-muted d-block" style="font-size: 10px;">Dispatched Date &amp; Time</small>
                        <span class="text-dark small fw-semibold"><?= formatDateTime($return['created_at']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Target Warehouse Destination -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <h6 class="fw-bold text-muted text-uppercase mb-2" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="bi bi-building me-1 text-info"></i> Destination Warehouse
                    </h6>
                    <div class="mb-2">
                        <small class="text-muted d-block" style="font-size: 10px;">Central Warehouse</small>
                        <strong class="text-dark fs-6"><?= e($return['warehouse_name']) ?></strong>
                        <span class="badge bg-light text-dark border font-monospace ms-1"><?= e($return['warehouse_code']) ?></span>
                    </div>
                    <?php if (!empty($return['warehouse_address'])): ?>
                        <div class="small text-muted mb-2 text-truncate" style="font-size: 11px;" title="<?= e($return['warehouse_address']) ?>"><i class="bi bi-geo-alt me-1"></i><?= e($return['warehouse_address']) ?></div>
                    <?php endif; ?>
                    <div>
                        <small class="text-muted d-block" style="font-size: 10px;">Inspector / Receiver</small>
                        <span class="text-dark small fw-semibold"><?= e($return['inspected_by_name'] ?? 'Warehouse QC Staff') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Consignment Volume & Value -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <h6 class="fw-bold text-muted text-uppercase mb-2" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="bi bi-boxes me-1 text-danger"></i> Consignment Volume
                    </h6>
                    <div class="mb-2">
                        <small class="text-muted d-block" style="font-size: 10px;">Total Dispatched Units</small>
                        <div class="fs-5 fw-bold text-danger"><?= $totalReturnedQty ?> <span class="fs-6 text-muted fw-normal">pcs (<?= count($items) ?> items)</span></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block" style="font-size: 10px;">Return Reason</small>
                        <span class="badge bg-warning-subtle text-warning-emphasis fw-semibold text-truncate d-inline-block" style="max-width: 100%;"><?= e($return['reason']) ?></span>
                    </div>
                    <div>
                        <small class="text-muted d-block" style="font-size: 10px;">Estimated Stock Value</small>
                        <strong class="text-success font-monospace">₹<?= number_format((float)$totalEstimatedValue, 2) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Outward Logistics & Transporter Details -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-danger border-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-dark text-uppercase mb-0" style="font-size: 11px; letter-spacing: 0.5px;">
                            <i class="bi bi-truck me-1 text-danger"></i> Outward Logistics
                        </h6>
                        <span class="badge bg-danger text-white rounded-pill" style="font-size: 9px;">
                            <?= e($return['outward_logistics']['carton_count'] ?? 1) ?> Carton(s)
                        </span>
                    </div>
                    <div class="mb-1">
                        <small class="text-muted d-block" style="font-size: 10px;">Carrier / Transporter</small>
                        <strong class="text-dark small"><?= e($return['outward_logistics']['carrier_name'] ?? 'GRM Dedicated Logistics') ?></strong>
                    </div>
                    <div class="row g-1 mb-1 small">
                        <div class="col-6">
                            <span class="text-muted" style="font-size: 10px;">Vehicle:</span>
                            <span class="fw-semibold text-dark font-monospace d-block" style="font-size: 11px;"><?= !empty($return['outward_logistics']['vehicle_no']) ? e($return['outward_logistics']['vehicle_no']) : '<span class="text-muted fw-normal">N/A</span>' ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted" style="font-size: 10px;">Tracking/LR:</span>
                            <span class="fw-semibold text-primary font-monospace d-block text-truncate" style="font-size: 11px;"><?= !empty($return['outward_logistics']['tracking_no']) ? e($return['outward_logistics']['tracking_no']) : '<span class="text-muted fw-normal">N/A</span>' ?></span>
                        </div>
                    </div>
                    <?php if (!empty($return['outward_logistics']['driver_name']) || !empty($return['outward_logistics']['driver_phone'])): ?>
                    <div class="small text-muted" style="font-size: 10.5px;">
                        <i class="bi bi-person text-secondary me-1"></i><?= e($return['outward_logistics']['driver_name'] ?? 'Driver') ?> <?= !empty($return['outward_logistics']['driver_phone']) ? '(' . e($return['outward_logistics']['driver_phone']) . ')' : '' ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($return['outward_logistics']['remarks'])): ?>
                    <div class="small text-muted mt-2 pt-1 border-top" style="font-size: 10px;">
                        <i class="bi bi-chat-left-text text-secondary me-1"></i><strong>Notes:</strong> <?= e($return['outward_logistics']['remarks']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Itemized Consignment Table & QR Scanner Canvas -->
    <div class="row g-4 mb-4">
        <!-- Left: Itemized Return Table -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Manifest Item Breakdown</h5>
                    <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill">
                        <?= count($items) ?> Product Item(s)
                    </span>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="ps-4" style="width: 5%;">#</th>
                                <th style="width: 35%;">Product & Variant</th>
                                <th style="width: 15%;">Classification</th>
                                <th class="text-center" style="width: 12%;">Dispatched Qty</th>
                                <th style="width: 15%;">QC Breakdown</th>
                                <th class="text-end" style="width: 15%;">Est. Line Value</th>
                                <th class="text-end pe-4" style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $idx => $it): ?>
                                <tr>
                                    <td class="ps-4 text-muted small fw-bold"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($it['image'])): ?>
                                                <img src="<?= e($it['image']) ?>" alt="" class="rounded-3 border me-2 flex-shrink-0" style="width: 42px; height: 42px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="rounded-3 bg-light border me-2 flex-shrink-0 d-flex align-items-center justify-content-center text-secondary" style="width: 42px; height: 42px;">
                                                    <i class="bi bi-box"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold text-dark small"><?= e($it['product_name']) ?></div>
                                                <div class="text-muted font-monospace d-flex flex-wrap align-items-center gap-1 mt-1" style="font-size: 11px;">
                                                    <span class="badge bg-light text-dark border"><i class="bi bi-qr-code text-primary me-1"></i><?= e($it['sku']) ?></span>
                                                    <?= !empty($it['size_name']) ? '<span class="badge bg-light text-dark border">' . e($it['size_name']) . '</span>' : '' ?>
                                                    <?= !empty($it['color_name']) ? '<span class="badge bg-light text-dark border">' . e($it['color_name']) . '</span>' : '' ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                            $classBadge = match($it['classification']) {
                                                'GOOD'         => 'bg-success-subtle text-success',
                                                'DAMAGED'      => 'bg-danger-subtle text-danger',
                                                'DEFECTIVE'    => 'bg-warning-subtle text-warning-emphasis',
                                                'WRONG_SIZE'   => 'bg-info-subtle text-info-emphasis',
                                                default        => 'bg-secondary-subtle text-secondary'
                                            };
                                        ?>
                                        <span class="badge <?= $classBadge ?> px-3 py-1 rounded-pill fw-semibold">
                                            <?= e($it['classification']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger rounded-pill px-3 fw-bold fs-6">
                                            <?= $it['returned_qty'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($return['status'] === 'COMPLETED' || $return['status'] === 'CLASSIFIED'): ?>
                                            <div class="small">
                                                <span class="text-success fw-bold" title="Good Qty">✓ <?= (int)$it['good_qty'] ?></span> • 
                                                <span class="text-danger fw-bold" title="Damaged Qty">✗ <?= (int)$it['damaged_qty'] ?></span> • 
                                                <span class="text-warning-emphasis fw-bold" title="Defective Qty">⚠ <?= (int)$it['defective_qty'] ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Pending QC</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold text-dark font-monospace">₹<?= number_format((float)$it['line_total'], 2) ?></div>
                                        <small class="text-muted">@ ₹<?= number_format((float)$it['unit_price'], 2) ?>/pc</small>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 shadow-sm fw-semibold" 
                                                onclick="openProductDetailModal(<?= (int)$it['variant_id'] ?>, <?= (int)$it['returned_qty'] ?>, '<?= e($it['classification']) ?>')" 
                                                title="View Returned Piece QR Codes & Details">
                                            <i class="bi bi-eye me-1"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="ps-4 fw-bold text-dark">Consignment Totals</th>
                                <th class="text-center fw-bold text-danger"><?= $totalReturnedQty ?> pcs</th>
                                <th></th>
                                <th class="text-end fw-bold text-success font-monospace">₹<?= number_format((float)$totalEstimatedValue, 2) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: 2D Consignment QR Code Badge -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 bg-white text-center p-4 h-100">
                <small class="text-muted text-uppercase fw-bold mb-3" style="font-size: 10px; letter-spacing: 0.5px;">Master Consignment QR</small>
                <div class="p-3 bg-light rounded-4 border d-inline-block mx-auto mb-3">
                    <div id="consignmentQrCanvas" class="d-flex justify-content-center"></div>
                </div>
                <div class="fw-bold font-monospace text-dark mb-1"><?= e($return['return_number']) ?></div>
                <p class="text-muted small mb-3" style="font-size: 11px;">Scan this QR sticker at the Central Warehouse inward terminal for automatic receipt.</p>
                
                <div class="d-grid gap-2">
                    <a href="<?= url('shop/return-to-warehouse/label/' . $return['id']) ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill">
                        <i class="bi bi-printer me-1"></i> Print QR Label
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- EXACT REPLICA MODAL: PRODUCT DETAILS & ALL PIECE QR CODES VIEW -->
<!-- ========================================================================= -->
<div class="modal fade" id="productDetailModal" tabindex="-1" aria-labelledby="productDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam-fill fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="productDetailModalLabel">Product Details &amp; Piece QR Codes</h5>
                        <small class="text-white-50 font-monospace" id="modalProductSkuCode">-</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light">
                <!-- Loading Spinner -->
                <div id="modalLoadingSpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted small mt-2">Loading detailed product specifications &amp; individual piece QR tags...</p>
                </div>

                <!-- Modal Content Body -->
                <div id="modalContentBody" class="d-none">
                    <!-- 1. Hero Product Banner -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                        <div class="card-body p-3 p-md-4 bg-white">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4 text-center">
                                    <div id="modalImageContainer" class="position-relative d-inline-block w-100">
                                        <!-- Image loaded dynamically -->
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                        <span class="badge bg-primary-subtle text-primary border" id="modalCategoryBadge">-</span>
                                        <span class="badge bg-info-subtle text-info-emphasis border" id="modalSourceBadge">-</span>
                                        <span class="badge bg-light text-dark border" id="modalBrandBadge">-</span>
                                    </div>
                                    <h4 class="fw-bold text-dark mb-1" id="modalProductName">-</h4>
                                    <div class="text-muted font-monospace small mb-2">
                                        Style Code: <strong class="text-primary" id="modalProductCode">-</strong>
                                    </div>

                                    <!-- Category Path -->
                                    <div class="small text-muted mb-3" id="modalCategoryPath">
                                        Category: <span class="fw-semibold text-dark">-</span>
                                    </div>

                                    <!-- Key Metrics in Hero -->
                                    <!-- Key Metrics in Hero -->
                                    <div class="row g-2 text-center">
                                        <div class="col-3">
                                            <div class="p-2 rounded-3 bg-light border">
                                                <small class="text-muted d-block text-uppercase" style="font-size: 10px;">Selling Price</small>
                                                <div class="fw-bold text-success fs-5" id="modalSellingPrice">₹0.00</div>
                                                <small class="text-muted text-decoration-line-through d-none" id="modalMrpPrice" style="font-size: 10px;"></small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="p-2 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25">
                                                <small class="text-danger fw-semibold d-block text-uppercase" style="font-size: 10px;">Returned In Return</small>
                                                <div class="fw-bold text-danger fs-5" id="modalHeroReturnedQty">0 pcs</div>
                                                <small class="text-danger" style="font-size: 10px;" id="modalHeroClassification">Dispatched</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="p-2 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25">
                                                <small class="text-success fw-semibold d-block text-uppercase" style="font-size: 10px;">Shop Stock</small>
                                                <div class="fw-bold text-success fs-5" id="modalAvailableStock">0</div>
                                                <small class="text-success" style="font-size: 10px;" id="modalStockStatus">In Stock</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="p-2 rounded-3 bg-light border">
                                                <small class="text-muted d-block text-uppercase" style="font-size: 10px;">Size &amp; Color</small>
                                                <div class="fw-bold text-dark fs-6 mt-1" id="modalVariantAttributes">-</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Technical Specifications Grid -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-0">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-card-checklist text-primary me-2"></i>Technical Specifications &amp; Compliance</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0 small">
                                    <tbody>
                                        <tr>
                                            <th class="bg-light text-muted w-25">Material / Fabric</th>
                                            <td class="fw-semibold text-dark" id="modalMaterial">-</td>
                                            <th class="bg-light text-muted w-25">HSN Code &amp; GST</th>
                                            <td id="modalHsnGst">-</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light text-muted">Category Hierarchy</th>
                                            <td id="modalCategoryHierarchy" class="fw-semibold text-dark">-</td>
                                            <th class="bg-light text-muted">Stock Thresholds</th>
                                            <td id="modalStockThresholds">-</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light text-muted">Production / Cost</th>
                                            <td id="modalCostPrice">-</td>
                                            <th class="bg-light text-muted">Inward Reference</th>
                                            <td id="modalInwardRef" class="font-monospace text-primary">-</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light text-muted">Description</th>
                                            <td colspan="3" class="text-secondary" id="modalDescription">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 3. INDIVIDUAL PIECE QR CODES GRID -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-grid-3x3-gap-fill text-danger me-2"></i><span id="modalPiecesSectionTitle">Dispatched Pieces for this Consignment</span> (<span id="modalPiecesDisplayCount">0</span> pcs)
                                </h6>
                                <small class="text-muted" id="modalPiecesSectionSubtitle">Itemized piece-by-piece QR tag cards for this return consignment</small>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="btn-group btn-group-sm" role="group" id="modalPieceFilterGroup">
                                    <button type="button" class="btn btn-outline-danger active fw-semibold" id="btnFilterReturned" onclick="toggleModalPieceView('returned')">
                                        <i class="bi bi-box-arrow-right me-1"></i>Returned in Consignment (<span id="btnReturnedCount">0</span>)
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary fw-semibold" id="btnFilterAll" onclick="toggleModalPieceView('all')">
                                        <i class="bi bi-collection me-1"></i>All Lifetime Pieces (<span id="btnAllCount">0</span>)
                                    </button>
                                </div>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-2" id="modalClassificationBadge">
                                    <i class="bi bi-arrow-return-left me-1"></i>Returned Consignment Item
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-3 bg-light bg-opacity-50">
                            <div class="row g-3" id="modalIndividualPiecesGrid">
                                <!-- Populated dynamically by JS -->
                            </div>
                        </div>
                    </div>

                    <!-- 4. Recent Stock Movements Table at Shop -->
                    <div class="card border-0 shadow-sm rounded-4 bg-white">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Recent Stock Movements at Shop</h6>
                            <span class="badge bg-light text-dark border" id="modalTxCount">0 Transactions</span>
                        </div>
                        <div class="card-body p-0 table-responsive" style="max-height: 220px;">
                            <table class="table table-sm table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Date</th>
                                        <th>Type</th>
                                        <th>Qty</th>
                                        <th>Balance</th>
                                        <th class="pe-3">Notes / Ref</th>
                                    </tr>
                                </thead>
                                <tbody id="modalTransactionsTableBody">
                                    <!-- Dynamic rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white border-0 py-3">
                <a href="#" id="modalFullDetailLink" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Full Page View
                </a>
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- QRCode Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrContainer = document.getElementById('consignmentQrCanvas');
    if (qrContainer && typeof QRCode !== 'undefined') {
        new QRCode(qrContainer, {
            text: '<?= e($return["return_number"]) ?>',
            width: 140,
            height: 140,
            colorDark: "#111827",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    }
});

let currentModalPieces = [];
let currentModalReturnedQty = 0;
let currentModalClassification = '';
let currentPieceViewMode = 'returned';

// Open and populate Product Detail Modal dynamically (Exact Replica of Shop Inventory with Return Context)
function openProductDetailModal(variantId, returnedQty = 0, classification = '') {
    const modalEl = document.getElementById('productDetailModal');
    if (!modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    const spinner = document.getElementById('modalLoadingSpinner');
    const content = document.getElementById('modalContentBody');
    if (spinner) spinner.classList.remove('d-none');
    if (content) content.classList.add('d-none');

    currentModalReturnedQty = parseInt(returnedQty) || 0;
    currentModalClassification = classification || 'Returned Consignment Item';
    currentPieceViewMode = currentModalReturnedQty > 0 ? 'returned' : 'all';

    // Fetch dynamic product detail data
    fetch(`<?= url('shop/inventory/ajax-detail') ?>/${variantId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.item) {
                alert('Product details could not be loaded.');
                modal.hide();
                return;
            }

            const item = data.item;
            const imgUrl = item.variant_image || item.product_image || '';
            const qty = parseFloat(item.quantity) || 0;
            const minStock = parseFloat(item.min_stock) || 5;
            const isLow = qty <= minStock;
            const mrp = parseFloat(item.mrp) || 0;
            const sellingPrice = parseFloat(item.selling_price) || 0;
            const cost = parseFloat(item.production_cost > 0 ? item.production_cost : (item.purchase_price || 0));

            // Populate Metadata
            document.getElementById('modalProductSkuCode').textContent = `SKU: ${item.sku}`;
            document.getElementById('modalProductName').textContent = item.product_name || 'Retail Product';
            document.getElementById('modalProductCode').textContent = item.product_code || item.sku;
            document.getElementById('modalCategoryBadge').textContent = item.category_name || 'General';
            document.getElementById('modalSourceBadge').textContent = item.source_type || 'OWN_PRODUCTION';
            document.getElementById('modalBrandBadge').textContent = item.brand_name ? `Brand: ${item.brand_name}` : 'GRM Collection';

            // Category Path
            let catPath = escapeHtml(item.category_name || 'General');
            if (item.subcategory_name) catPath += ` › ${escapeHtml(item.subcategory_name)}`;
            if (item.sub_subcategory_name) catPath += ` › ${escapeHtml(item.sub_subcategory_name)}`;
            document.getElementById('modalCategoryPath').innerHTML = `Category: <strong class="text-dark">${catPath}</strong>`;
            document.getElementById('modalCategoryHierarchy').innerHTML = catPath;

            // Inward Ref
            const inwardRefEl = document.getElementById('modalInwardRef');
            if (inwardRefEl) inwardRefEl.textContent = item.inward_reference || 'Inward Stock';

            // Prices & Stock
            document.getElementById('modalSellingPrice').textContent = `₹${sellingPrice.toFixed(2)}`;
            const mrpEl = document.getElementById('modalMrpPrice');
            if (mrp > sellingPrice) {
                mrpEl.textContent = `MRP: ₹${mrp.toFixed(2)}`;
                mrpEl.classList.remove('d-none');
            } else {
                mrpEl.classList.add('d-none');
            }

            // Hero Return Qty and Classification
            const heroRetEl = document.getElementById('modalHeroReturnedQty');
            if (heroRetEl) heroRetEl.textContent = `${currentModalReturnedQty} pcs`;
            const heroClassEl = document.getElementById('modalHeroClassification');
            if (heroClassEl) heroClassEl.textContent = currentModalClassification || 'Dispatched';
            const badgeClassEl = document.getElementById('modalClassificationBadge');
            if (badgeClassEl) badgeClassEl.innerHTML = `<i class="bi bi-arrow-return-left me-1"></i>${escapeHtml(currentModalClassification || 'Returned Item')}`;

            document.getElementById('modalAvailableStock').textContent = `${qty} pcs`;
            const stockStatusEl = document.getElementById('modalStockStatus');
            if (qty <= 0) {
                stockStatusEl.textContent = 'Out of Stock';
                stockStatusEl.className = 'badge bg-danger rounded-pill';
            } else if (isLow) {
                stockStatusEl.textContent = 'Low Stock';
                stockStatusEl.className = 'badge bg-warning text-dark rounded-pill';
            } else {
                stockStatusEl.textContent = 'In Stock';
                stockStatusEl.className = 'badge bg-success rounded-pill';
            }

            document.getElementById('modalVariantAttributes').innerHTML = `
                <span class="badge bg-dark px-2 py-1 me-1">${escapeHtml(item.size_name || 'Free')}</span>
                <span class="badge bg-secondary px-2 py-1">${escapeHtml(item.color_name || 'Std')}</span>
            `;

            // Image Container
            const imgBox = document.getElementById('modalImageContainer');
            if (imgBox) {
                if (imgUrl) {
                    imgBox.innerHTML = `
                        <img src="${escapeHtml(imgUrl)}" alt="${escapeHtml(item.product_name)}" 
                             class="img-fluid rounded-4 shadow-sm border bg-white" 
                             style="max-height: 200px; width: 100%; object-fit: cover;">
                    `;
                } else {
                    imgBox.innerHTML = `
                        <div class="rounded-4 bg-light border d-flex flex-column align-items-center justify-content-center shadow-sm p-4" style="min-height: 180px;">
                            <i class="bi bi-box-seam text-primary opacity-50" style="font-size: 3.5rem;"></i>
                            <span class="text-muted small mt-1">No Image Available</span>
                        </div>
                    `;
                }
            }

            // Specs Table
            document.getElementById('modalMaterial').textContent = item.material || 'N/A';
            document.getElementById('modalHsnGst').innerHTML = `${escapeHtml(item.hsn_code || 'N/A')} ${item.gst_percentage ? `<span class="badge bg-secondary-subtle text-secondary ms-1">${parseFloat(item.gst_percentage)}% GST</span>` : ''}`;
            document.getElementById('modalStockThresholds').textContent = `Min: ${item.min_stock || 0} pcs | Max: ${item.max_stock || 1000} pcs`;
            document.getElementById('modalCostPrice').textContent = cost > 0 ? `₹${cost.toFixed(2)}` : 'N/A';
            document.getElementById('modalDescription').textContent = item.description || 'No description provided for this catalog product.';

            // Store piece arrays and render
            currentModalPieces = item.individual_pieces || [];
            
            // Set counts on toggle buttons
            const btnRetCount = document.getElementById('btnReturnedCount');
            if (btnRetCount) btnRetCount.textContent = currentModalReturnedQty > 0 ? currentModalReturnedQty : currentModalPieces.length;
            const btnAllCount = document.getElementById('btnAllCount');
            if (btnAllCount) btnAllCount.textContent = currentModalPieces.length;

            const btnRet = document.getElementById('btnFilterReturned');
            const btnAll = document.getElementById('btnFilterAll');
            if (btnRet && btnAll) {
                if (currentPieceViewMode === 'returned') {
                    btnRet.className = 'btn btn-danger active fw-semibold';
                    btnAll.className = 'btn btn-outline-secondary fw-semibold';
                } else {
                    btnRet.className = 'btn btn-outline-danger fw-semibold';
                    btnAll.className = 'btn btn-secondary active fw-semibold';
                }
            }

            renderModalPieceCards();

            // Transactions Table
            const txBody = document.getElementById('modalTransactionsTableBody');
            const txCountEl = document.getElementById('modalTxCount');
            if (txBody) {
                const txs = item.transactions || [];
                if (txCountEl) txCountEl.textContent = `${txs.length} Transactions`;
                if (txs.length === 0) {
                    txBody.innerHTML = `<tr><td colspan="5" class="text-center py-3 text-muted">No stock transactions logged yet.</td></tr>`;
                } else {
                    let txHtml = '';
                    txs.forEach(t => {
                        const typeStr = (t.transaction_type || '').toUpperCase();
                        const isCredit = typeStr.includes('INWARD') || typeStr.includes('IN') || typeStr.includes('RETURN') || typeStr.includes('CREDIT') || parseFloat(t.quantity) > 0;
                        const q = Math.abs(parseFloat(t.quantity) || 0);
                        txHtml += `
                            <tr>
                                <td class="ps-3 text-muted font-monospace">${escapeHtml(t.created_at || '')}</td>
                                <td><span class="badge bg-${isCredit ? 'success-subtle text-success border border-success-subtle' : 'danger-subtle text-danger border border-danger-subtle'} font-monospace">${escapeHtml(t.transaction_type)}</span></td>
                                <td class="fw-bold ${isCredit ? 'text-success' : 'text-danger'}">${isCredit ? '+' : '-'}${q}</td>
                                <td class="fw-bold text-dark">${parseFloat(t.balance_after) || 0}</td>
                                <td class="pe-3 text-muted">${escapeHtml(t.notes || t.reference_type || '-')}</td>
                            </tr>
                        `;
                    });
                    txBody.innerHTML = txHtml;
                }
            }

            // Update full page link
            const fullLink = document.getElementById('modalFullDetailLink');
            if (fullLink) {
                fullLink.href = `<?= url('shop/inventory/detail') ?>/${item.variant_id || variantId}`;
            }

            if (spinner) spinner.classList.add('d-none');
            if (content) content.classList.remove('d-none');
        })
        .catch(err => {
            console.error(err);
            alert('Failed to connect to server for product details.');
            modal.hide();
        });
}

function toggleModalPieceView(mode) {
    currentPieceViewMode = mode;
    const btnRet = document.getElementById('btnFilterReturned');
    const btnAll = document.getElementById('btnFilterAll');
    if (btnRet && btnAll) {
        if (mode === 'returned') {
            btnRet.className = 'btn btn-danger active fw-semibold';
            btnAll.className = 'btn btn-outline-secondary fw-semibold';
        } else {
            btnRet.className = 'btn btn-outline-danger fw-semibold';
            btnAll.className = 'btn btn-secondary active fw-semibold';
        }
    }
    renderModalPieceCards();
}

function renderModalPieceCards() {
    const piecesGrid = document.getElementById('modalIndividualPiecesGrid');
    const displayCountEl = document.getElementById('modalPiecesDisplayCount');
    const titleEl = document.getElementById('modalPiecesSectionTitle');
    const subtitleEl = document.getElementById('modalPiecesSectionSubtitle');
    if (!piecesGrid) return;

    let displayPieces = [];
    const isReturnedMode = currentPieceViewMode === 'returned' && currentModalReturnedQty > 0;

    if (isReturnedMode) {
        if (titleEl) titleEl.textContent = 'Dispatched Consignment Pieces';
        if (subtitleEl) subtitleEl.textContent = `Showing the ${currentModalReturnedQty} piece(s) designated in this return dispatch`;

        // 1. Primary: Find explicitly marked returned pieces in this consignment
        let explicitReturned = currentModalPieces.filter(p => p.is_returned || (p.status && p.status.toLowerCase() === 'returned'));
        
        const isDamagedType = (currentModalClassification || '').toUpperCase().includes('DAMAGED') || (currentModalClassification || '').toUpperCase().includes('DEFECT');

        if (explicitReturned.length >= currentModalReturnedQty && currentModalReturnedQty > 0) {
            displayPieces = explicitReturned.slice(0, currentModalReturnedQty);
        } else if (explicitReturned.length > 0) {
            displayPieces = explicitReturned;
        } else if (isDamagedType) {
            // Pick damaged pieces
            let damagedPieces = currentModalPieces.filter(p => p.is_damaged || (p.status && p.status.toLowerCase() === 'damaged'));
            if (damagedPieces.length > 0) {
                displayPieces = damagedPieces.slice(0, currentModalReturnedQty);
            }
        }
        
        // If still empty, pick available pieces (excluding sold and damaged pieces) from the batch
        if (displayPieces.length === 0) {
            let availablePieces = currentModalPieces.filter(p => !p.is_sold && !p.is_damaged && !p.is_exchanged);
            if (availablePieces.length > 0) {
                displayPieces = availablePieces.slice(-currentModalReturnedQty);
            } else {
                displayPieces = currentModalPieces.slice(0, currentModalReturnedQty);
            }
        }
    } else {
        if (titleEl) titleEl.textContent = 'All Individual Inward Pieces & QR Codes';
        if (subtitleEl) subtitleEl.textContent = 'Itemized piece-by-piece QR tag cards across lifetime inward history';
        displayPieces = currentModalPieces;
    }

    if (displayCountEl) displayCountEl.textContent = displayPieces.length;

    if (displayPieces.length === 0) {
        piecesGrid.innerHTML = `
            <div class="col-12 text-center py-4 text-muted">
                <i class="bi bi-qr-code fs-2 d-block mb-1 opacity-50"></i>
                No individual pieces found to display.
            </div>
        `;
        return;
    }

    let phtml = '';
    displayPieces.forEach((p, idx) => {
        const pieceImg = p.product_image 
            ? `<img src="${escapeHtml(p.product_image)}" class="rounded border shadow-sm flex-shrink-0" style="width: 48px; height: 48px; object-fit: cover;">`
            : `<div class="rounded bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 48px; height: 48px;"><i class="bi bi-box-seam fs-5"></i></div>`;

        const isRestocked = p.is_restocked || (p.status && p.status.toLowerCase() === 'in stock' && p.return_number);
        const isExchanged = p.is_exchanged || (p.status && p.status.toLowerCase() === 'exchanged');
        const isSold = (p.is_sold || (p.status && p.status.toLowerCase() === 'sold')) && !isRestocked;
        const isDamaged = p.is_damaged || (p.status && p.status.toLowerCase() === 'damaged');
        const isWarehouseReturned = p.is_returned || (p.status && p.status.toLowerCase() === 'returned');

        let statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle small font-monospace"><i class="bi bi-check-circle me-1"></i>In Stock</span>`;
        let cardBg = 'bg-white shadow-sm';

        if (isReturnedMode) {
            statusBadge = `<span class="badge bg-danger text-white border border-danger small font-monospace"><i class="bi bi-arrow-return-left me-1"></i>Returned Consignment</span>`;
            cardBg = 'border-danger border-opacity-50 bg-danger-subtle bg-opacity-10 shadow-sm';
        } else if (isRestocked) {
            statusBadge = `<span class="badge bg-success text-white border border-success small font-monospace"><i class="bi bi-arrow-repeat me-1"></i>Restocked (Return)</span>`;
            cardBg = 'border-success border-opacity-50 bg-success-subtle bg-opacity-10';
        } else if (isExchanged) {
            statusBadge = `<span class="badge text-white small font-monospace" style="background:#7c3aed;"><i class="bi bi-arrow-left-right me-1"></i>Exchanged (Out)</span>`;
            cardBg = 'border-secondary-subtle bg-secondary-subtle bg-opacity-10 opacity-75';
        } else if (isSold) {
            statusBadge = `<span class="badge bg-primary text-white border border-primary small font-monospace"><i class="bi bi-cart-check me-1"></i>Sold (Billed)</span>`;
            cardBg = 'border-primary-subtle bg-primary-subtle bg-opacity-10 opacity-75';
        } else if (isDamaged) {
            statusBadge = `<span class="badge bg-danger text-white border border-danger small font-monospace"><i class="bi bi-slash-circle me-1"></i>Damaged</span>`;
            cardBg = 'border-danger border-opacity-50 bg-danger-subtle bg-opacity-15';
        } else if (isWarehouseReturned) {
            statusBadge = `<span class="badge bg-secondary text-white border border-secondary small font-monospace"><i class="bi bi-truck me-1"></i>Returned (Warehouse)</span>`;
            cardBg = 'border-secondary-subtle bg-secondary-subtle bg-opacity-15 opacity-75';
        }

        const pieceNumberLabel = isReturnedMode
            ? `Piece #${p.piece_number || (idx + 1)} • Dispatched (${idx + 1} of ${displayPieces.length})`
            : `Piece #${p.piece_number || (idx + 1)} of ${currentModalPieces.length}`;

        phtml += `
            <div class="col-md-6 col-lg-4">
                <div class="card border rounded-3 h-100 p-3 transition-all ${cardBg}">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <span class="badge ${isReturnedMode ? 'bg-danger text-white' : 'bg-danger-subtle text-danger border border-danger-subtle'} font-monospace" style="font-size: 0.75rem;">
                            ${pieceNumberLabel}
                        </span>
                        ${statusBadge}
                    </div>

                    <div class="d-flex align-items-center gap-2 mb-2">
                        ${pieceImg}
                        <div class="overflow-hidden">
                            <div class="fw-bold text-dark text-truncate small">${escapeHtml(p.product_name)}</div>
                            <div class="font-monospace text-primary fw-semibold" style="font-size: 11px;">${escapeHtml(p.sku)}</div>
                            <div class="text-muted text-truncate" style="font-size: 10px;">${escapeHtml(p.category_name)} › ${escapeHtml(p.subcategory_name || '')}</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border mb-2 small">
                        <div>
                            <span class="badge bg-dark font-monospace text-white me-1" style="font-size: 10px;">${escapeHtml(p.size_name || 'Free')}</span>
                            <span class="badge bg-secondary font-monospace" style="font-size: 10px;">${escapeHtml(p.color_name || 'Std')}</span>
                        </div>
                        <div class="fw-bold text-success">₹${(p.selling_price || 0).toFixed(2)}</div>
                    </div>

                    <!-- QR Code Block -->
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                        <div class="d-flex align-items-center gap-2">
                            <div id="piece_qr_canvas_${idx}" class="border p-1 bg-white rounded shadow-sm"></div>
                            <div>
                                <small class="text-muted d-block" style="font-size: 9px;">Piece QR Tag</small>
                                <span class="badge bg-dark font-monospace text-white" style="font-size: 11px;">
                                    <i class="bi bi-qr-code me-1 text-warning"></i>${escapeHtml(p.tag_code)}
                                </span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="navigator.clipboard.writeText('${escapeHtml(p.tag_code)}'); alert('Copied QR Tag: ${escapeHtml(p.tag_code)}');" title="Copy QR Tag">
                            <i class="bi bi-copy"></i>
                        </button>
                    </div>

                    ${isReturnedMode ? `
                        <div class="mt-2 text-danger fw-semibold text-truncate" style="font-size: 10px;">
                            <i class="bi bi-box-arrow-right me-1"></i>Consignment: <?= e($return['return_number']) ?>
                        </div>
                    ` : (isSold || isExchanged ? `
                        <div class="mt-2 text-primary fw-semibold text-truncate" style="font-size: 10px;">
                            <i class="bi bi-receipt me-1"></i>${escapeHtml(p.invoice_number || 'POS Billed')}
                        </div>
                    ` : (isDamaged ? `
                        <div class="mt-2 text-danger fw-semibold text-truncate" style="font-size: 10px;">
                            <i class="bi bi-exclamation-triangle me-1"></i>${escapeHtml(p.damage_number || 'Damaged Stock')}
                        </div>
                    ` : (isRestocked ? `
                        <div class="mt-2 text-success fw-semibold text-truncate" style="font-size: 10px;">
                            <i class="bi bi-arrow-repeat me-1"></i>Restocked: ${escapeHtml(p.return_number || 'Customer Return')}
                        </div>
                    ` : `
                        <div class="mt-2 text-muted text-truncate" style="font-size: 9px;">
                            <i class="bi bi-truck me-1 text-primary"></i>${escapeHtml(p.inward_reference || 'Inward Consignment')}
                        </div>
                    `)))}
                </div>
            </div>
        `;
    });
    piecesGrid.innerHTML = phtml;

    // Render QR codes for each piece
    setTimeout(() => {
        displayPieces.forEach((p, idx) => {
            const cEl = document.getElementById(`piece_qr_canvas_${idx}`);
            if (cEl && typeof QRCode !== 'undefined') {
                cEl.innerHTML = '';
                try {
                    new QRCode(cEl, {
                        text: p.qr_payload || p.tag_code,
                        width: 44,
                        height: 44,
                        colorDark: "#0f172a",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } catch(e) {}
            }
        });
    }, 50);
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

<?php if (isset($_GET['outward_success'])): ?>
// Automatically show Outward Process Completed Modal upon successful dispatch
document.addEventListener('DOMContentLoaded', function() {
    const successModalEl = document.getElementById('outwardSuccessModal');
    if (successModalEl) {
        const successModal = new bootstrap.Modal(successModalEl);
        successModal.show();
    }
});
<?php endif; ?>
</script>

<!-- ========================================================================= -->
<!-- 🚀 MODAL: OUTWARD PROCESS COMPLETED PROMPT MODAL -->
<!-- ========================================================================= -->
<div class="modal fade" id="outwardSuccessModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-success text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-white text-success d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-check2-all fs-4 fw-bold"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Outward Process Completed!</h5>
                        <small class="text-white-50">Consignment #<?= e($return['return_number']) ?> has been dispatched &amp; inventory deducted</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Highlight Summary -->
                <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white text-center p-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold mx-auto mb-2" style="font-size: 11px;">
                        <i class="bi bi-check-circle-fill me-1"></i> Stock Deducted &amp; Return Handover Recorded
                    </span>
                    <h4 class="fw-bold text-dark font-monospace mb-1"><?= e($return['return_number']) ?></h4>
                    <p class="text-muted small mb-0">
                        <strong><?= $totalReturnedQty ?> pcs</strong> dispatched to <strong><?= e($return['warehouse_name']) ?></strong> via <strong><?= e($return['outward_logistics']['carrier_name'] ?? 'GRM Dedicated Logistics') ?></strong>
                    </p>
                </div>

                <!-- Outward Logistics Details Card -->
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-3">
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-dark mb-2 small text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                            <i class="bi bi-truck text-primary me-1"></i> Transporter &amp; Gate Pass Details
                        </h6>
                        <div class="row g-2 small">
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block" style="font-size: 10px;">Carrier</span>
                                <strong class="text-dark"><?= e($return['outward_logistics']['carrier_name'] ?? 'GRM Dedicated Logistics') ?></strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block" style="font-size: 10px;">Vehicle No</span>
                                <strong class="text-dark font-monospace"><?= !empty($return['outward_logistics']['vehicle_no']) ? e($return['outward_logistics']['vehicle_no']) : 'N/A' ?></strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block" style="font-size: 10px;">Cartons</span>
                                <span class="badge bg-light text-dark border"><?= e($return['outward_logistics']['carton_count'] ?? 1) ?> Carton(s)</span>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block" style="font-size: 10px;">Tracking/LR #</span>
                                <strong class="text-primary font-monospace"><?= !empty($return['outward_logistics']['tracking_no']) ? e($return['outward_logistics']['tracking_no']) : 'N/A' ?></strong>
                            </div>
                            <?php if (!empty($return['outward_logistics']['driver_name'])): ?>
                            <div class="col-12 mt-1 pt-1 border-top">
                                <span class="text-muted">Driver / Handover:</span>
                                <strong class="text-dark ms-1"><?= e($return['outward_logistics']['driver_name']) ?></strong>
                                <?php if (!empty($return['outward_logistics']['driver_phone'])): ?>
                                    <span class="text-muted ms-1">(<?= e($return['outward_logistics']['driver_phone']) ?>)</span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Action Prompts -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="<?= url('shop/return-to-warehouse/label/' . $return['id']) ?>" target="_blank" class="btn btn-outline-secondary w-100 py-2 rounded-4 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-qr-code fs-5"></i>
                            <span>Print Carton QR Sticker</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3 px-4 border-top d-flex justify-content-between">
                <a href="<?= url('shop/return-to-warehouse') ?>" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i> Return Consignments List
                </a>
                <button type="button" class="btn btn-success rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">
                    <i class="bi bi-check2 me-1"></i> Continue to Manifest View
                </button>
            </div>
        </div>
    </div>
</div>
