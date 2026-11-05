<style>
.table-responsive {
    position: relative;
}
#returnInventoryTable, #selectedProductsTable {
    border-collapse: separate;
    border-spacing: 0;
}
.sticky-col-select {
    position: sticky;
    left: 0;
    z-index: 5;
    background-color: #ffffff;
    box-shadow: 2px 0 6px rgba(0,0,0,0.06);
}
thead th.sticky-col-select {
    z-index: 6;
    background-color: #f8fafc !important;
}

/* Selected Product Row Highlighting (Vibrant Green & Border) */
.inv-row.inv-row-selected {
    background-color: #f0fdf4 !important;
    border-left: 5px solid #16a34a !important;
}
.inv-row.inv-row-selected td {
    background-color: #f0fdf4 !important;
}
.inv-row.inv-row-selected .sticky-col-select {
    background-color: #dcfce7 !important;
}
.inv-row.inv-row-selected:hover td,
.inv-row.inv-row-selected:hover .sticky-col-select {
    background-color: #bbf7d0 !important;
}
.inv-row:hover .sticky-col-select {
    background-color: #f8fafc;
}
.inv-row.inv-row-selected .item-name {
    color: #15803d !important;
    font-weight: 700 !important;
}
.inv-row.inv-row-selected .return-qty {
    border: 2px solid #16a34a !important;
    background-color: #ffffff !important;
    color: #15803d !important;
    font-weight: 800 !important;
}
.inv-row.inv-row-selected .row-qty-btn {
    border-color: #16a34a !important;
    color: #15803d !important;
}

/* Disabled Return Qty */
.return-qty:disabled {
    background-color: #f8fafc !important;
    color: #94a3b8 !important;
    cursor: not-allowed;
    border-color: #e2e8f0 !important;
}
.return-qty:not(:disabled) {
    background-color: #ffffff !important;
    color: #15803d !important;
    border-color: #16a34a !important;
}

/* Modal Selected Configuration Card */
.modal-config-panel.is-selected {
    background-color: #f0fdf4 !important;
    border: 2px solid #16a34a !important;
    box-shadow: 0 4px 14px rgba(22, 163, 74, 0.15) !important;
}
.piece-card-selected {
    border: 2px solid #16a34a !important;
    background-color: #f0fdf4 !important;
    box-shadow: 0 4px 10px rgba(22, 163, 74, 0.12) !important;
}

#inventoryLedgerCard {
    display: none !important;
}

/* Modal Piece Tag Chips in Configuration Box */
.modal-piece-chip {
    background-color: #ffffff;
    border: 1px solid #16a34a;
    border-radius: 8px;
    padding: 3px 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease-in-out;
}
.modal-piece-chip:hover {
    background-color: #f0fdf4;
    border-color: #15803d;
    box-shadow: 0 2px 6px rgba(22, 163, 74, 0.15);
}
.modal-piece-chip .chip-remove-btn {
    cursor: pointer;
    color: #ef4444;
    font-weight: bold;
    font-size: 13px;
    line-height: 1;
    transition: color 0.15s, transform 0.15s;
}
.modal-piece-chip .chip-remove-btn:hover {
    color: #b91c1c;
    transform: scale(1.2);
}
</style>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div class="d-flex align-items-center">
            <a href="<?= url('shop/return-to-warehouse') ?>" class="btn btn-sm btn-light border rounded-circle me-3" title="Back to Consignments">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <div class="badge bg-danger-subtle text-danger mb-1"><i class="bi bi-arrow-return-left me-1"></i><?= e($shop['name'] ?? 'Shop Store') ?></div>
                <h3 class="fw-bold mb-1 text-dark">Initiate Return to Warehouse</h3>
                <p class="text-muted small mb-0">Select shop inventory products, inspect full product specifications &amp; piece QR codes, and dispatch return consignments.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= url('shop/return-to-warehouse/store') ?>" id="returnDispatchForm">
        <div class="row g-4">
            <!-- Left: Consignment Manifest Settings & Live Summary -->
            <div class="col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 bg-white sticky-top" style="top: 20px;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-truck me-2 text-primary"></i>1. Destination &amp; Outward Logistics</h6>
                    </div>
                    <div class="card-body p-3">
                        <!-- Target Warehouse -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small">Destination Warehouse <span class="text-danger">*</span></label>
                            <select name="warehouse_id" id="fieldWarehouseId" class="form-select form-select-sm bg-light" required>
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?= $wh['id'] ?>">
                                        <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Return Reason / Category -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small">Return Reason <span class="text-danger">*</span></label>
                            <select name="reason" id="fieldReason" class="form-select form-select-sm bg-light" required>
                                <option value="Damaged & Defective Stock Return">Damaged & Defective Stock Return</option>
                                <option value="Excess / Unsold Inventory Return">Excess / Unsold Inventory Return</option>
                                <option value="Seasonal & Style Rotation Return">Seasonal & Style Rotation Return</option>
                                <option value="Customer Return Restock to Warehouse">Customer Return Restock to Warehouse</option>
                                <option value="Quality Inspection Rejection">Quality Inspection Rejection</option>
                                <option value="Other Shop Return">Other Shop Return</option>
                            </select>
                        </div>

                        <!-- Outward Logistics Information -->
                        <div class="p-2 bg-light rounded-3 border mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-dark" style="font-size: 11px;"><i class="bi bi-box-seam me-1 text-primary"></i>Outward Details</span>
                                <span class="badge bg-primary-subtle text-primary" style="font-size: 9px;">Transport</span>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label text-muted small mb-1" style="font-size: 10px;">Carrier / Transporter <span class="text-danger">*</span></label>
                                <input type="text" name="carrier_name" id="fieldCarrierName" class="form-control form-control-sm bg-white" placeholder="e.g. GRM Logistics / DTDC / Self" value="GRM Dedicated Logistics" required>
                                <div class="invalid-feedback" style="font-size: 9px;">Carrier name is required.</div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small mb-1" style="font-size: 10px;">Vehicle No</label>
                                    <input type="text" name="vehicle_no" id="fieldVehicleNo" class="form-control form-control-sm bg-white font-monospace" placeholder="TN-01-AB-1234" maxlength="20" style="text-transform: uppercase;">
                                    <div class="invalid-feedback" style="font-size: 9px;">Invalid vehicle format.</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small mb-1" style="font-size: 10px;">Cartons / Boxes <span class="text-danger">*</span></label>
                                    <input type="number" name="carton_count" id="fieldCartonCount" class="form-control form-control-sm bg-white" min="1" max="999" value="1" required>
                                    <div class="invalid-feedback" style="font-size: 9px;">Must be at least 1 box.</div>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small mb-1" style="font-size: 10px;">Driver / Bearer</label>
                                    <input type="text" name="driver_name" id="fieldDriverName" class="form-control form-control-sm bg-white" placeholder="Driver Name" maxlength="50">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small mb-1" style="font-size: 10px;">Driver Phone</label>
                                    <input type="tel" name="driver_phone" id="fieldDriverPhone" class="form-control form-control-sm bg-white font-monospace" placeholder="10-digit Mobile" maxlength="10" pattern="[6-9][0-9]{9}">
                                    <div class="invalid-feedback" id="fieldDriverPhoneError" style="font-size: 9px;">Enter valid 10-digit mobile.</div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label text-muted small mb-1" style="font-size: 10px;">LR / Docket / E-Way No</label>
                                <input type="text" name="tracking_no" id="fieldTrackingNo" class="form-control form-control-sm bg-white font-monospace" placeholder="Tracking / Waybill No" maxlength="50">
                            </div>

                            <div>
                                <label class="form-label text-muted small mb-1" style="font-size: 10px;">Outward Remarks / Notes</label>
                                <textarea name="remarks" id="fieldRemarks" class="form-control form-control-sm bg-white" rows="2" placeholder="Driver instructions, parcel tags, handover notes..." maxlength="255"></textarea>
                            </div>
                        </div>

                        <!-- Live Summary Box -->
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <h6 class="fw-bold text-dark mb-2 small text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Consignment Summary</h6>
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="text-muted">Selected Items:</span>
                                <strong class="text-dark" id="summarySelectedCount">0 items</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="text-muted">Total Units to Return:</span>
                                <strong class="text-danger fs-5 fw-bold" id="summaryTotalUnits">0 pcs</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <span class="text-muted">Estimated Value:</span>
                                <strong class="text-success font-monospace" id="summaryTotalValue">₹0.00</strong>
                            </div>

                            <!-- Selected Item Chips -->
                            <div id="selectedChipsTray" class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top" style="max-height: 100px; overflow-y: auto;">
                                <small class="text-muted fst-italic" style="font-size: 10px;">Select products from the table to dispatch.</small>
                            </div>
                        </div>

                        <div>
                            <button type="button" class="btn btn-danger btn-md w-100 rounded-pill fw-bold shadow-sm" id="btnSubmitDispatch" disabled>
                                <i class="bi bi-box-arrow-right me-1"></i> Confirm &amp; Outward Process
                            </button>
                            <small class="text-muted d-block text-center mt-1" style="font-size: 10px;">
                                <i class="bi bi-shield-check me-1 text-success"></i>Stock deducted upon outward dispatch
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-9">
                <!-- Guidance Banner -->
                <div class="alert alert-info border-0 rounded-4 shadow-sm mb-3 py-2 px-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center small">
                        <i class="bi bi-info-circle-fill fs-5 text-primary me-2"></i>
                        <span><strong>Scan &amp; Send Flow:</strong> Scan an available or damaged product QR/SKU. Each scanned product will be added to the floating warehouse dispatch list below.</span>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-body p-3">
                        <label for="damageScanInput" class="form-label fw-bold small text-dark mb-2">
                            <i class="bi bi-qr-code-scan text-danger me-1"></i>Scan Product for Warehouse Dispatch
                        </label>
                        <div class="d-flex align-items-center gap-3 mb-2 small">
                            <span class="text-muted fw-semibold">Scan from:</span>
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="scanStockPool" value="AVAILABLE" checked>
                                <span class="form-check-label text-success fw-semibold">Available Stock</span>
                            </label>
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="scanStockPool" value="DAMAGED">
                                <span class="form-check-label text-danger fw-semibold">Damaged Stock</span>
                            </label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-danger text-white border-0"><i class="bi bi-upc-scan"></i></span>
                            <input type="text" id="damageScanInput" class="form-control font-monospace" placeholder="Scan available or damaged QR/SKU and press Enter" autocomplete="off" autofocus>
                            <button type="button" class="btn btn-danger" id="btnDamageScan">Add to Dispatch</button>
                        </div>
                        <small id="damageScanStatus" class="text-muted d-block mt-2">Available and damaged shop stock can be scanned for warehouse dispatch.</small>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- 💥 EXTRA SECTION: LIVE SELECTED RETURN PRODUCTS CONSIGNMENT SECTION -->
                <!-- ========================================================================= -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white" id="selectedProductsCard">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger rounded-pill px-3 py-2 fw-bold">
                                <i class="bi bi-arrow-return-left me-1"></i> 2. Selected Return Consignment Items
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-semibold" id="selectedSectionBadge">
                                <i class="bi bi-check-circle-fill me-1"></i> 0 Products Selected
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-none" id="btnClearAllSelectedSection">
                                <i class="bi bi-trash me-1"></i> Clear Selection
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Empty State when 0 products selected -->
                        <div id="selectedProductsEmptyState" class="text-center py-5 px-3">
                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2 shadow-sm" style="width: 56px; height: 56px;">
                                <i class="bi bi-boxes text-muted opacity-50 fs-3"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">No Products Added to Consignment Yet</h6>
                            <p class="text-muted small mb-0">Select products from the Shop Stock Ledger below or open the "View" modal to add pieces to this return consignment.</p>
                        </div>

                        <!-- Table of Selected Products -->
                        <div id="selectedProductsTableWrapper" class="table-responsive d-none">
                            <table class="table table-hover align-middle mb-0" id="selectedProductsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" style="width: 40px;">#</th>
                                        <th>Selected Product</th>
                                        <th>SKU / Style Code</th>
                                        <th>Size &amp; Color</th>
                                        <th class="text-center" style="width: 140px;">Return Qty</th>
                                        <th style="width: 160px;">Return Condition</th>
                                        <th class="text-end" style="width: 110px;">Unit Price</th>
                                        <th class="text-end" style="width: 120px;">Line Total</th>
                                        <th class="text-end pe-4" style="width: 120px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="selectedProductsTableBody">
                                    <!-- Populated dynamically by JS -->
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold text-dark">
                                        <td colspan="4" class="ps-3 text-uppercase small text-muted">Consignment Total:</td>
                                        <td class="text-center text-danger fs-6" id="selectedTableTotalQty">0 pcs</td>
                                        <td></td>
                                        <td class="text-end small text-muted">Total Value:</td>
                                        <td class="text-end text-success fs-6 font-monospace" id="selectedTableTotalValue">₹0.00</td>
                                        <td class="text-end pe-4"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- 3. SHOP STOCK LEDGER & AVAILABLE INVENTORY SELECTOR -->
                <!-- ========================================================================= -->
               

                <!-- Ledger Table Card -->
                <div class="card border-0 shadow-sm rounded-4 bg-white" id="inventoryLedgerCard">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-boxes me-2 text-primary"></i>3. Shop Stock Ledger &amp; Product Selector</h5>
                        <span class="badge bg-light text-dark border rounded-pill px-3 py-2"><?= count($inventory) ?> Items in Shop</span>
                    </div>

                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover align-middle mb-0" id="returnInventoryTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 text-center sticky-col-select" style="width: 70px;">Select</th>
                                    <th>Product Name</th>
                                    <th>SKU / QR Code</th>
                                    <th>Category</th>
                                    <th>Size &amp; Color</th>
                                    <th>Selling Price</th>
                                    <th class="text-center">Shop Stock</th>
                                    <th class="text-center" style="width: 120px;">Return Qty</th>
                                    <th style="width: 140px;">Condition</th>
                                    <th class="text-end pe-4" style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inventory)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">
                                            <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                            No inventory records with positive stock found for this shop location.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inventory as $idx => $item): ?>
                                        <?php
                                            $qty = (float)$item['quantity'];
                                            $imgUrl = !empty($item['variant_image']) ? $item['variant_image'] : (!empty($item['product_image']) ? $item['product_image'] : '');
                                            $isDamaged = ($item['stock_state'] === 'DAMAGED');
                                        ?>
                                        <tr class="inv-row" id="inv-row-<?= (int)$item['variant_id'] ?>"
                                            data-idx="<?= $idx ?>"
                                            data-variant-id="<?= (int)$item['variant_id'] ?>"
                                            data-sku="<?= e(strtolower($item['sku'])) ?>"
                                            data-sku-raw="<?= e($item['sku']) ?>"
                                            data-qr-code="<?= e(strtolower($item['qr_code'] ?? '')) ?>"
                                            data-qr-code-raw="<?= e($item['qr_code'] ?? '') ?>"
                                            data-name="<?= e(strtolower($item['product_name'])) ?>"
                                            data-name-raw="<?= e($item['product_name']) ?>"
                                            data-code="<?= e(strtolower($item['product_code'] ?? '')) ?>"
                                            data-code-raw="<?= e($item['product_code'] ?? '') ?>"
                                            data-category="<?= e($item['category_name'] ?? 'General') ?>"
                                            data-subcategory="<?= e($item['subcategory_name'] ?? '') ?>"
                                            data-size="<?= e($item['size_name'] ?? 'Std') ?>"
                                            data-color="<?= e($item['color_name'] ?? 'Std') ?>"
                                            data-image="<?= e($imgUrl) ?>"
                                            data-stock-state="<?= e($item['stock_state']) ?>"
                                            data-piece-tags="<?= e(implode('|', $item['piece_tags'] ?? [])) ?>"
                                            data-damaged-piece-tags="<?= e(json_encode($item['damaged_piece_tags'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                                            data-category-type="<?= e($item['category_type'] ?? 'NORMAL') ?>"
                                            data-complementary="<?= !empty($item['is_complementary']) ? '1' : '0' ?>"
                                            data-qr-type="<?= e($item['qr_type'] ?? '') ?>"
                                            data-pack-quantity="<?= (int)($item['pack_quantity'] ?? 1) ?>"
                                            data-price="<?= (float)($item['selling_price'] ?? $item['mrp'] ?? 0) ?>"
                                            data-max-stock="<?= (int)$qty ?>">
                                            
                                            <!-- 1. Selection Checkbox (Sticky Column) -->
                                            <td class="ps-3 text-center sticky-col-select">
                                                <div class="d-flex flex-column align-items-center">
                                                    <input type="checkbox" class="form-check-input item-select fs-4 cursor-pointer" data-idx="<?= $idx ?>" id="chk_item_<?= $idx ?>">
                                                    <small class="text-muted select-label" style="font-size: 9px;">Select</small>
                                                </div>
                                                <input type="hidden" name="items[<?= $idx ?>][variant_id]" value="<?= $item['variant_id'] ?>" disabled data-idx="<?= $idx ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][stock_state]" value="<?= $item['stock_state'] ?>" disabled data-idx="<?= $idx ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][piece_tags][]" value="" disabled data-idx="<?= $idx ?>" class="scanned-piece-tag">
                                            </td>

                                            <!-- 2. Product Name (Exact Replica) -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if ($imgUrl): ?>
                                                        <img src="<?= e($imgUrl) ?>" alt="" class="rounded-3 border shadow-sm flex-shrink-0" style="width: 44px; height: 44px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 44px; height: 44px;">
                                                            <i class="bi bi-box-seam fs-5"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="fw-bold text-dark item-name">
                                                            <?= e($item['product_name']) ?>
                                                            <span class="badge bg-success text-white ms-1 d-none selected-row-badge" style="font-size: 9px;"><i class="bi bi-check2"></i> Selected</span>
                                                        </div>
                                                        <small class="text-muted font-monospace"><?= e($item['product_code']) ?></small>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- 3. SKU / QR Code (Exact Replica) -->
                                            <td>
                                                <div class="small fw-semibold text-dark font-monospace"><?= e($item['sku']) ?></div>
                                                <?php if (!empty($item['qr_code'])): ?>
                                                    <span class="badge bg-light text-dark border font-monospace" style="font-size:10px;"><i class="bi bi-qr-code me-1 text-warning"></i><?= e($item['qr_code']) ?></span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- 4. Category (Exact Replica) -->
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= e($item['category_name'] ?? 'General') ?></span>
                                                <?php if (!empty($item['subcategory_name'])): ?>
                                                    <small class="text-muted d-block" style="font-size: 11px;">› <?= e($item['subcategory_name']) ?></small>
                                                <?php endif; ?>
                                            </td>

                                            <!-- 5. Size & Color (Exact Replica) -->
                                            <td>
                                                <span class="badge bg-secondary-subtle text-secondary"><?= e($item['size_name'] ?? 'Std') ?></span>
                                                <span class="badge bg-secondary-subtle text-secondary ms-1"><?= e($item['color_name'] ?? 'Std') ?></span>
                                            </td>

                                            <!-- 6. Selling Price (Exact Replica) -->
                                            <td class="fw-bold text-dark">
                                                ₹<?= number_format((float)$item['selling_price'], 2) ?>
                                                <?php if (!empty($item['mrp']) && (float)$item['mrp'] > (float)$item['selling_price']): ?>
                                                    <small class="text-muted text-decoration-line-through d-block" style="font-size: 10px;">MRP ₹<?= number_format((float)$item['mrp'], 2) ?></small>
                                                <?php endif; ?>
                                            </td>

                                            <!-- 7. Shop Stock & Status Pool -->
                                            <td class="text-center">
                                                <span class="fs-6 fw-bold <?= $isDamaged ? 'text-danger' : 'text-success' ?>"><?= (int)$qty ?></span>
                                                <small class="text-muted d-block" style="font-size:10px;">
                                                    <?= $isDamaged ? '<span class="text-danger fw-semibold">Damaged</span>' : '<span class="text-success">Available</span>' ?>
                                                </small>
                                            </td>

                                            <!-- 8. Return Quantity Stepper Input (Initial 0, changes on selection) -->
                                            <td>
                                                <div class="input-group input-group-sm" style="width: 105px; margin: 0 auto;">
                                                    <button type="button" class="btn btn-outline-secondary px-2 row-qty-btn row-qty-minus" data-idx="<?= $idx ?>" disabled title="Decrease Qty">
                                                        <i class="bi bi-dash"></i>
                                                    </button>
                                                    <input type="number" name="items[<?= $idx ?>][quantity]" class="form-control form-control-sm text-center return-qty font-monospace fw-bold px-1" 
                                                           value="0" min="0" max="<?= (int)$qty ?>" disabled data-idx="<?= $idx ?>">
                                                    <button type="button" class="btn btn-outline-secondary px-2 row-qty-btn row-qty-plus" data-idx="<?= $idx ?>" title="Increase Qty / Select">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
                                                </div>
                                            </td>

                                            <!-- 9. Condition / Classification -->
                                            <td>
                                                <select name="items[<?= $idx ?>][classification]" class="form-select form-select-sm return-classification" disabled data-idx="<?= $idx ?>" style="font-size: 11px;">
                                                    <option value="DAMAGED" <?= $isDamaged ? 'selected' : '' ?>>Damaged (Defect)</option>
                                                    <option value="GOOD" <?= !$isDamaged ? 'selected' : '' ?>>Good (Restock)</option>
                                                    <option value="DEFECTIVE">Defective Quality</option>
                                                    <option value="WRONG_SIZE">Wrong Size / Style</option>
                                                </select>
                                            </td>

                                            <td class="text-end pe-4"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>
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
                                    <div class="row g-2 text-center mb-3">
                                        <div class="col-3">
                                            <div class="p-2 rounded-3 bg-light border">
                                                <small class="text-muted d-block text-uppercase" style="font-size: 10px;">Selling Price</small>
                                                <div class="fw-bold text-success fs-5" id="modalSellingPrice">₹0.00</div>
                                                <small class="text-muted text-decoration-line-through d-none" id="modalMrpPrice" style="font-size: 10px;"></small>
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
                                        <div class="col-3">
                                            <div class="p-2 rounded-3 bg-light border">
                                                <small class="text-muted d-block text-uppercase" style="font-size: 10px;">Inward Ref</small>
                                                <div class="fw-bold text-primary small mt-1 font-monospace text-truncate" id="modalInwardRef">-</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 💥 RETURN CONSIGNMENT SELECTION PANEL INSIDE MODAL -->
                                    <div class="p-3 rounded-4 modal-config-panel bg-danger bg-opacity-10 border border-danger border-opacity-25 transition-all" id="modalConfigBox">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                            <span class="fw-bold text-danger small text-uppercase" style="letter-spacing: 0.5px;">
                                                <i class="bi bi-arrow-return-left me-1"></i> Return Consignment Configuration
                                            </span>
                                            <span id="modalSelectionStatusBadge" class="badge bg-success border border-success px-3 py-1 rounded-pill d-none">
                                                <i class="bi bi-check-circle-fill me-1"></i> Selected (<span id="modalSelectedQtyBadge">0</span> pcs)
                                            </span>
                                        </div>

                                        <div class="row g-2 align-items-end">
                                            <!-- Return Quantity Stepper (Initial 0, changes on selection) -->
                                            <div class="col-sm-4">
                                                <label class="form-label fw-bold text-dark mb-1 small">Return Qty (Max: <span id="modalMaxQtyText">0</span>)</label>
                                                <div class="input-group input-group-sm shadow-sm">
                                                    <button type="button" class="btn btn-outline-danger px-2" id="modalQtyMinusBtn" disabled><i class="bi bi-dash"></i></button>
                                                    <input type="number" id="modalReturnQtyInput" class="form-control text-center fw-bold font-monospace" value="0" min="0" max="100">
                                                    <button type="button" class="btn btn-outline-danger px-2" id="modalQtyPlusBtn"><i class="bi bi-plus"></i></button>
                                                </div>
                                            </div>

                                            <!-- Condition Dropdown -->
                                            <div class="col-sm-4">
                                                <label class="form-label fw-bold text-dark mb-1 small">Return Condition</label>
                                                <select id="modalClassificationSelect" class="form-select form-select-sm shadow-sm">
                                                    <option value="DAMAGED">Damaged (Defect)</option>
                                                    <option value="GOOD">Good (Restock)</option>
                                                    <option value="DEFECTIVE">Defective Quality</option>
                                                    <option value="WRONG_SIZE">Wrong Size / Style</option>
                                                </select>
                                            </div>

                                            <!-- Direct Action Buttons -->
                                            <div class="col-sm-4 d-flex gap-1">
                                                <button type="button" class="btn btn-danger btn-sm rounded-pill w-100 fw-bold shadow-sm" id="btnModalSelectForReturn">
                                                    <i class="bi bi-check2-circle me-1"></i> <span id="modalSelectBtnText">Select for Return</span>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill d-none" id="btnModalDeselectFromReturn" title="Deselect from Return">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div id="modalReturnSuccessAlert" class="alert alert-success border-0 rounded-3 py-1 px-2 mt-2 mb-0 small d-none">
                                            <i class="bi bi-check-circle-fill me-1"></i> Return selection updated! Consignment summary updated.
                                        </div>

                                        <!-- 💥 DYNAMIC SELECTED RETURN PIECE TAGS LIST IN CONFIG BOX -->
                                        <div id="modalSelectedPiecesContainer" class="mt-3 pt-2 border-top">
                                            <div id="modalSelectedPiecesEmpty" class="text-muted small">
                                                <i class="bi bi-info-circle me-1 text-primary"></i>
                                                <span class="fst-italic" style="font-size: 11px;">No piece tags selected yet. Click <strong>"+ Add Piece #X to Return"</strong> on any piece card below to add individual piece tags.</span>
                                            </div>
                                            <div id="modalSelectedPiecesWrapper" class="d-none">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-bold text-success small" style="font-size: 11px;">
                                                        <i class="bi bi-check2-square me-1"></i> Selected Return Piece Tags (<span id="modalSelectedPiecesCount">0</span> pcs):
                                                    </span>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace" id="modalSelectedPiecesValue">₹0.00</span>
                                                </div>
                                                <div id="modalSelectedPiecesTray" class="d-flex flex-wrap gap-1" style="max-height: 120px; overflow-y: auto;">
                                                    <!-- Populated dynamically with piece tags chips and remove (✕) buttons -->
                                                </div>
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
                                            <th class="bg-light text-muted">Damaged Units</th>
                                            <td id="modalDamagedUnits" class="text-danger fw-bold">0 pcs</td>
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

                    <!-- 3. ALL INDIVIDUAL PIECE QR CODES GRID (Full units breakdown) -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-grid-3x3-gap-fill text-danger me-2"></i>All Individual Inward Pieces &amp; QR Codes (<span id="modalPiecesTotalCount">0</span> pcs)
                                </h6>
                                <small class="text-muted">Itemized piece-by-piece QR tag cards with complete product, category, size and pricing information</small>
                            </div>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2">
                                <i class="bi bi-arrow-return-left me-1"></i>Selectable for Return to Warehouse
                            </span>
                        </div>
                        <div class="card-body p-3 bg-light bg-opacity-50">
                            <div class="row g-3" id="modalIndividualPiecesGrid">
                                <!-- Populated dynamically by JS with all individual pieces -->
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
                <button type="button" class="btn btn-sm btn-danger rounded-pill px-4" id="btnModalSelectForReturnFooter">
                    <i class="bi bi-check2-circle me-1"></i> Apply &amp; Close Modal
                </button>
                <a href="#" id="modalFullDetailLink" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Full Page View
                </a>
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>

<!-- Load QRCode Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
let currentModalVariantId = null;
let currentModalMaxStock = 1;
let currentModalPieces = [];
let calculateConsignment;
const selectedPiecesByVariant = {}; // Tracks selected piece numbers per variant

// Open and populate Product Detail Modal dynamically (Exact Replica of Shop Inventory)
function openProductDetailModal(variantId) {
    currentModalVariantId = variantId;
    const modalEl = document.getElementById('productDetailModal');
    if (!modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    const spinner = document.getElementById('modalLoadingSpinner');
    const content = document.getElementById('modalContentBody');
    if (spinner) spinner.classList.remove('d-none');
    if (content) content.classList.add('d-none');

    // Hide previous success alerts
    const successAlert = document.getElementById('modalReturnSuccessAlert');
    if (successAlert) successAlert.classList.add('d-none');

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
            currentModalPieces = item.individual_pieces || [];
            const imgUrl = item.variant_image || item.product_image || '';
            const qty = parseFloat(item.quantity) || 0;
            const damagedQty = parseFloat(item.damaged_quantity) || 0;
            const minStock = parseFloat(item.min_stock) || 5;
            const isLow = qty <= minStock;
            const mrp = parseFloat(item.mrp) || 0;
            const sellingPrice = parseFloat(item.selling_price) || 0;
            const cost = parseFloat(item.production_cost > 0 ? item.production_cost : (item.purchase_price || 0));

            currentModalMaxStock = Math.max(1, parseInt(qty) || 1);
            document.getElementById('modalMaxQtyText').textContent = currentModalMaxStock;

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
            document.getElementById('modalInwardRef').textContent = item.inward_reference || 'Inward Stock';

            // Prices & Stock
            document.getElementById('modalSellingPrice').textContent = `₹${sellingPrice.toFixed(2)}`;
            const mrpEl = document.getElementById('modalMrpPrice');
            if (mrp > sellingPrice) {
                mrpEl.textContent = `MRP: ₹${mrp.toFixed(2)}`;
                mrpEl.classList.remove('d-none');
            } else {
                mrpEl.classList.add('d-none');
            }

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
            document.getElementById('modalDamagedUnits').textContent = `${damagedQty} pcs`;
            document.getElementById('modalDescription').textContent = item.description || 'No description provided for this catalog product.';

            // Check if this row is already selected on the main table
            const row = document.querySelector(`.inv-row[data-variant-id="${variantId}"]`);
            let isChecked = false;
            let curQty = 0;
            let curClass = 'DAMAGED';

            if (row) {
                const idx = row.getAttribute('data-idx');
                const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
                const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
                const classSelect = document.querySelector(`.return-classification[data-idx="${idx}"]`);

                isChecked = chk ? chk.checked : false;
                curQty = (isChecked && qtyInput) ? (parseInt(qtyInput.value) || 0) : 0;
                curClass = classSelect ? classSelect.value : 'DAMAGED';
            }

            // Initialize or sync selected pieces set for this variant
            if (!selectedPiecesByVariant[variantId]) {
                selectedPiecesByVariant[variantId] = new Set();
                if (isChecked && curQty > 0) {
                    for (let i = 1; i <= curQty; i++) {
                        selectedPiecesByVariant[variantId].add(i);
                    }
                }
            } else {
                if (!isChecked || curQty === 0) {
                    selectedPiecesByVariant[variantId].clear();
                } else if (selectedPiecesByVariant[variantId].size !== curQty) {
                    const pSet = selectedPiecesByVariant[variantId];
                    if (pSet.size < curQty) {
                        for (let i = 1; i <= currentModalMaxStock && pSet.size < curQty; i++) {
                            pSet.add(i);
                        }
                    } else if (pSet.size > curQty) {
                        const arr = Array.from(pSet);
                        while (arr.length > curQty) {
                            pSet.delete(arr.pop());
                        }
                    }
                }
            }

            const modalQtyInput = document.getElementById('modalReturnQtyInput');
            const modalClassSelect = document.getElementById('modalClassificationSelect');
            const modalBtnText = document.getElementById('modalSelectBtnText');
            const modalStatusBadge = document.getElementById('modalSelectionStatusBadge');
            const modalSelectedQtyBadge = document.getElementById('modalSelectedQtyBadge');
            const modalDeselectBtn = document.getElementById('btnModalDeselectFromReturn');
            const modalMinusBtn = document.getElementById('modalQtyMinusBtn');
            const modalConfigBox = document.getElementById('modalConfigBox');

            // Set INITIAL QUANTITY TO 0 IF UNSELECTED
            if (modalQtyInput) {
                modalQtyInput.value = curQty;
                modalQtyInput.min = 0;
                modalQtyInput.max = currentModalMaxStock;
            }
            if (modalClassSelect) modalClassSelect.value = curClass;

            if (isChecked && curQty > 0) {
                if (modalBtnText) modalBtnText.textContent = 'Update Return Selection';
                if (modalStatusBadge) modalStatusBadge.classList.remove('d-none');
                if (modalSelectedQtyBadge) modalSelectedQtyBadge.textContent = curQty;
                if (modalDeselectBtn) modalDeselectBtn.classList.remove('d-none');
                if (modalMinusBtn) modalMinusBtn.disabled = false;
                if (modalConfigBox) modalConfigBox.classList.add('is-selected');
            } else {
                if (modalBtnText) modalBtnText.textContent = 'Select for Return';
                if (modalStatusBadge) modalStatusBadge.classList.add('d-none');
                if (modalDeselectBtn) modalDeselectBtn.classList.add('d-none');
                if (modalMinusBtn) modalMinusBtn.disabled = true;
                if (modalConfigBox) modalConfigBox.classList.remove('is-selected');
            }

            // RENDER ALL INDIVIDUAL PIECE CARDS WITH INDIVIDUAL PIECE TOGGLE
            renderModalPieceCards(currentModalPieces);

            // RENDER DYNAMIC SELECTED RETURN PIECE LIST IN CONFIG BOX
            renderModalSelectedPiecesList();

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

// Render Dynamic Selected Return Piece Tags List in Modal Config Box
function renderModalSelectedPiecesList() {
    const emptyMsg = document.getElementById('modalSelectedPiecesEmpty');
    const listWrapper = document.getElementById('modalSelectedPiecesWrapper');
    const countBadge = document.getElementById('modalSelectedPiecesCount');
    const valueBadge = document.getElementById('modalSelectedPiecesValue');
    const chipsTray = document.getElementById('modalSelectedPiecesTray');

    if (!currentModalVariantId) return;

    const selectedSet = selectedPiecesByVariant[currentModalVariantId] || new Set();
    const count = selectedSet.size;

    if (count === 0) {
        if (emptyMsg) emptyMsg.classList.remove('d-none');
        if (listWrapper) listWrapper.classList.add('d-none');
        if (chipsTray) chipsTray.innerHTML = '';
        return;
    }

    if (emptyMsg) emptyMsg.classList.add('d-none');
    if (listWrapper) listWrapper.classList.remove('d-none');
    if (countBadge) countBadge.textContent = count;

    // Build pieces lookup
    const pieceMap = {};
    (currentModalPieces || []).forEach((p, idx) => {
        const pNum = p.piece_number || (idx + 1);
        pieceMap[pNum] = p;
    });

    let html = '';
    let totalVal = 0;

    Array.from(selectedSet).sort((a, b) => a - b).forEach(pNum => {
        const p = pieceMap[pNum] || {
            piece_number: pNum,
            tag_code: `TAG-${pNum}`,
            size_name: 'Std',
            color_name: 'Std',
            selling_price: 0
        };
        const pPrice = parseFloat(p.selling_price) || 0;
        totalVal += pPrice;

        html += `
            <div class="modal-piece-chip">
                <span class="badge bg-success text-white font-monospace" style="font-size: 10px;">Piece #${pNum}</span>
                <span class="font-monospace fw-bold text-dark" style="font-size: 11px;"><i class="bi bi-qr-code text-warning me-1"></i>${escapeHtml(p.tag_code)}</span>
                <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 9px;">${escapeHtml(p.size_name || 'Std')} / ${escapeHtml(p.color_name || 'Std')}</span>
                <span class="fw-bold text-success font-monospace" style="font-size: 10px;">₹${pPrice.toFixed(2)}</span>
                <i class="bi bi-x-circle-fill chip-remove-btn" onclick="togglePieceSelection(${pNum})" title="Remove Piece #${pNum} from return"></i>
            </div>
        `;
    });

    if (chipsTray) chipsTray.innerHTML = html;
    if (valueBadge) valueBadge.textContent = `₹${totalVal.toFixed(2)}`;
}

// Render Piece QR Cards with Individual Piece Toggle
function renderModalPieceCards(pieces) {
    const piecesGrid = document.getElementById('modalIndividualPiecesGrid');
    const piecesCountEl = document.getElementById('modalPiecesTotalCount');
    if (piecesCountEl) piecesCountEl.textContent = pieces.length;

    if (!piecesGrid) return;
    if (pieces.length === 0) {
        piecesGrid.innerHTML = `
            <div class="col-12 text-center py-4 text-muted">
                <i class="bi bi-qr-code fs-2 d-block mb-1 opacity-50"></i>
                No piece tags recorded for this item.
            </div>
        `;
        return;
    }

    const selectedSet = selectedPiecesByVariant[currentModalVariantId] || new Set();

    let phtml = '';
    pieces.forEach((p, idx) => {
        const pieceNum = p.piece_number || (idx + 1);
        const isPieceSelected = selectedSet.has(pieceNum);
        const pieceImg = p.product_image 
            ? `<img src="${escapeHtml(p.product_image)}" class="rounded border shadow-sm flex-shrink-0" style="width: 48px; height: 48px; object-fit: cover;">`
            : `<div class="rounded bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 48px; height: 48px;"><i class="bi bi-box-seam fs-5"></i></div>`;

        const isRestocked = p.is_restocked || (p.status && p.status.toLowerCase() === 'in stock' && p.return_number);
        const isExchanged = p.is_exchanged || (p.status && p.status.toLowerCase() === 'exchanged');
        const isSold = (p.is_sold || (p.status && p.status.toLowerCase() === 'sold')) && !isRestocked;
        const isDamaged = p.is_damaged || (p.status && p.status.toLowerCase() === 'damaged');
        const isWarehouseReturned = p.is_returned || (p.status && p.status.toLowerCase() === 'returned');

        let statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle small font-monospace"><i class="bi bi-check-circle me-1"></i>In Stock</span>`;
        let cardBg = isPieceSelected ? 'piece-card-selected' : 'bg-white shadow-sm';
        let isSelectable = true;
        let disabledLabel = '';

        if (isRestocked) {
            statusBadge = `<span class="badge bg-success text-white border border-success small font-monospace"><i class="bi bi-arrow-repeat me-1"></i>Restocked (Return)</span>`;
            cardBg = isPieceSelected ? 'piece-card-selected' : 'border-success border-opacity-50 bg-success-subtle bg-opacity-10';
        } else if (isExchanged) {
            statusBadge = `<span class="badge text-white small font-monospace" style="background:#7c3aed;"><i class="bi bi-arrow-left-right me-1"></i>Exchanged (Out)</span>`;
            cardBg = 'border-secondary-subtle bg-secondary-subtle bg-opacity-10 opacity-75';
            isSelectable = false;
            disabledLabel = p.invoice_number ? `Exchanged in ${p.invoice_number}` : 'Exchanged Item';
        } else if (isSold) {
            statusBadge = `<span class="badge bg-primary text-white border border-primary small font-monospace"><i class="bi bi-cart-check me-1"></i>Sold (Billed)</span>`;
            cardBg = 'border-primary-subtle bg-primary-subtle bg-opacity-10 opacity-75';
            isSelectable = false;
            disabledLabel = p.invoice_number ? `Sold in ${p.invoice_number}` : 'Sold (Billed)';
        } else if (isDamaged) {
            statusBadge = `<span class="badge bg-danger text-white border border-danger small font-monospace"><i class="bi bi-slash-circle me-1"></i>Damaged</span>`;
            cardBg = isPieceSelected ? 'piece-card-selected' : 'border-danger border-opacity-50 bg-danger-subtle bg-opacity-15';
        } else if (isWarehouseReturned) {
            statusBadge = `<span class="badge bg-secondary text-white border border-secondary small font-monospace"><i class="bi bi-truck me-1"></i>Returned (Warehouse)</span>`;
            cardBg = 'border-secondary-subtle bg-secondary-subtle bg-opacity-15 opacity-75';
            isSelectable = false;
            disabledLabel = 'Already Returned to Warehouse';
        }

        phtml += `
            <div class="col-md-6 col-lg-4">
                <div class="card border rounded-3 h-100 p-3 transition-all ${cardBg}" id="pieceCard_${idx}">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <span class="badge ${isPieceSelected ? 'bg-success text-white' : 'bg-secondary-subtle text-secondary border'} font-monospace" id="pieceBadge_${idx}">
                            ${isPieceSelected ? `<i class="bi bi-check-circle-fill me-1"></i>Piece #${pieceNum} (Selected)` : `Piece #${pieceNum} of ${pieces.length}`}
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
                            <span class="badge bg-dark font-monospace text-white me-1" style="font-size: 10px;">${escapeHtml(p.size_name)}</span>
                            <span class="badge bg-secondary font-monospace" style="font-size: 10px;">${escapeHtml(p.color_name)}</span>
                        </div>
                        <div class="fw-bold text-success">₹${p.selling_price.toFixed(2)}</div>
                    </div>

                    <!-- QR Code Block -->
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top mb-2">
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

                    <!-- Piece Return Action Button -->
                    ${isSelectable ? `
                        <button type="button" id="pieceBtn_${idx}" 
                                class="btn btn-sm w-100 rounded-pill py-1 fw-bold ${isPieceSelected ? 'btn-success' : 'btn-outline-danger'}" 
                                onclick="togglePieceSelection(${pieceNum})" 
                                title="${isPieceSelected ? 'Click to remove this piece from return consignment' : 'Click to add this piece to return consignment'}">
                            <i class="bi ${isPieceSelected ? 'bi-check-circle-fill' : 'bi-plus-circle'} me-1"></i>
                            ${isPieceSelected ? `Piece #${pieceNum} Added (Click to Remove)` : `Add Piece #${pieceNum} to Return`}
                        </button>
                    ` : `
                        <button type="button" class="btn btn-sm w-100 rounded-pill py-1 fw-semibold btn-light border text-muted opacity-75" disabled title="${escapeHtml(disabledLabel)}">
                            <i class="bi bi-lock-fill me-1"></i> ${escapeHtml(disabledLabel)}
                        </button>
                    `}

                    <!-- Extended Piece Reference Line -->
                    ${isSold || isExchanged ? `
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
                    `))}
                </div>
            </div>
        `;
    });
    piecesGrid.innerHTML = phtml;

    // Render QR codes
    setTimeout(() => {
        pieces.forEach((p, idx) => {
            const cEl = document.getElementById(`piece_qr_canvas_${idx}`);
            if (cEl && typeof QRCode !== 'undefined') {
                cEl.innerHTML = '';
                try {
                    new QRCode(cEl, {
                        text: p.qr_payload,
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

// Function to toggle individual piece selection on click
function togglePieceSelection(pieceNumber) {
    if (!currentModalVariantId) return;
    if (!selectedPiecesByVariant[currentModalVariantId]) {
        selectedPiecesByVariant[currentModalVariantId] = new Set();
    }
    const pieceSet = selectedPiecesByVariant[currentModalVariantId];
    const pNum = parseInt(pieceNumber) || 1;

    if (pieceSet.has(pNum)) {
        pieceSet.delete(pNum);
    } else {
        if (pieceSet.size < currentModalMaxStock) {
            pieceSet.add(pNum);
        }
    }

    const newQty = pieceSet.size;
    const modalQtyInput = document.getElementById('modalReturnQtyInput');
    if (modalQtyInput) {
        modalQtyInput.value = newQty;
    }

    if (newQty > 0) {
        applyModalSelectionToForm();
    } else {
        removeModalSelectionFromForm();
    }
}

// Helper to sync piece set when quantity is typed or stepper clicked
function syncPieceSetWithQty(targetQty) {
    if (!currentModalVariantId) return;
    if (!selectedPiecesByVariant[currentModalVariantId]) {
        selectedPiecesByVariant[currentModalVariantId] = new Set();
    }
    const pieceSet = selectedPiecesByVariant[currentModalVariantId];
    if (targetQty > pieceSet.size) {
        for (let i = 1; i <= currentModalMaxStock && pieceSet.size < targetQty; i++) {
            pieceSet.add(i);
        }
    } else if (targetQty < pieceSet.size) {
        const arr = Array.from(pieceSet);
        while (arr.length > targetQty) {
            const popped = arr.pop();
            pieceSet.delete(popped);
        }
    }
    updatePieceCardSelectionStyles();
}

// Helper to apply modal selection to form
function applyModalSelectionToForm() {
    if (!currentModalVariantId) return;
    const row = document.querySelector(`.inv-row[data-variant-id="${currentModalVariantId}"]`);
    if (!row) return;

    const idx = row.getAttribute('data-idx');
    const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
    const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
    const classSelect = document.querySelector(`.return-classification[data-idx="${idx}"]`);

    const modalQtyInput = document.getElementById('modalReturnQtyInput');
    const modalClassSelect = document.getElementById('modalClassificationSelect');

    let returnQty = parseInt(modalQtyInput?.value) || 0;
    if (returnQty <= 0) returnQty = 1;
    if (returnQty > currentModalMaxStock) returnQty = currentModalMaxStock;

    if (modalQtyInput) modalQtyInput.value = returnQty;
    if (qtyInput) qtyInput.value = returnQty;
    if (classSelect && modalClassSelect) classSelect.value = modalClassSelect.value;
    if (chk) chk.checked = true;

    // Trigger calculation & UI sync
    if (typeof calculateConsignment === 'function') {
        calculateConsignment();
    }

    // Update modal UI indicators & Color Change
    const modalBtnText = document.getElementById('modalSelectBtnText');
    const modalStatusBadge = document.getElementById('modalSelectionStatusBadge');
    const modalSelectedQtyBadge = document.getElementById('modalSelectedQtyBadge');
    const modalDeselectBtn = document.getElementById('btnModalDeselectFromReturn');
    const modalMinusBtn = document.getElementById('modalQtyMinusBtn');
    const modalConfigBox = document.getElementById('modalConfigBox');
    const successAlert = document.getElementById('modalReturnSuccessAlert');

    if (modalBtnText) modalBtnText.textContent = 'Update Return Selection';
    if (modalStatusBadge) modalStatusBadge.classList.remove('d-none');
    if (modalSelectedQtyBadge) modalSelectedQtyBadge.textContent = returnQty;
    if (modalDeselectBtn) modalDeselectBtn.classList.remove('d-none');
    if (modalMinusBtn) modalMinusBtn.disabled = false;
    if (modalConfigBox) modalConfigBox.classList.add('is-selected');

    if (successAlert) {
        successAlert.classList.remove('d-none');
        setTimeout(() => successAlert.classList.add('d-none'), 3000);
    }

    // Re-render piece card highlights in modal & dynamic piece list
    updatePieceCardSelectionStyles();
}

// Helper to remove selection from form
function removeModalSelectionFromForm() {
    if (!currentModalVariantId) return;
    const row = document.querySelector(`.inv-row[data-variant-id="${currentModalVariantId}"]`);
    if (!row) return;

    const idx = row.getAttribute('data-idx');
    const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
    const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);

    if (chk) chk.checked = false;
    if (qtyInput) qtyInput.value = 0;

    const modalQtyInput = document.getElementById('modalReturnQtyInput');
    if (modalQtyInput) modalQtyInput.value = 0;

    if (selectedPiecesByVariant[currentModalVariantId]) {
        selectedPiecesByVariant[currentModalVariantId].clear();
    }

    if (typeof calculateConsignment === 'function') {
        calculateConsignment();
    }

    const modalBtnText = document.getElementById('modalSelectBtnText');
    const modalStatusBadge = document.getElementById('modalSelectionStatusBadge');
    const modalDeselectBtn = document.getElementById('btnModalDeselectFromReturn');
    const modalMinusBtn = document.getElementById('modalQtyMinusBtn');
    const modalConfigBox = document.getElementById('modalConfigBox');
    const successAlert = document.getElementById('modalReturnSuccessAlert');

    if (modalBtnText) modalBtnText.textContent = 'Select for Return';
    if (modalStatusBadge) modalStatusBadge.classList.add('d-none');
    if (modalDeselectBtn) modalDeselectBtn.classList.add('d-none');
    if (modalMinusBtn) modalMinusBtn.disabled = true;
    if (modalConfigBox) modalConfigBox.classList.remove('is-selected');
    if (successAlert) successAlert.classList.add('d-none');

    // Reset piece card highlights & dynamic piece list
    updatePieceCardSelectionStyles();
}

// Helper to update piece card styles dynamically and sync dynamic selected piece tags tray
function updatePieceCardSelectionStyles() {
    if (!currentModalVariantId) return;
    const selectedSet = selectedPiecesByVariant[currentModalVariantId] || new Set();

    document.querySelectorAll('[id^="pieceCard_"]').forEach((card, idx) => {
        const pieceNum = idx + 1;
        const isPieceSelected = selectedSet.has(pieceNum);
        const btn = document.getElementById(`pieceBtn_${idx}`);
        const badge = document.getElementById(`pieceBadge_${idx}`);

        if (isPieceSelected) {
            card.className = 'card border rounded-3 h-100 p-3 transition-all piece-card-selected';
            if (btn) {
                btn.className = 'btn btn-sm w-100 rounded-pill py-1 fw-bold btn-success';
                btn.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> Piece #${pieceNum} Added (Click to Remove)`;
                btn.title = 'Click to remove this piece from return consignment';
            }
            if (badge) {
                badge.className = 'badge bg-success text-white font-monospace';
                badge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Piece #${pieceNum} (Selected)`;
            }
        } else {
            card.className = 'card border rounded-3 h-100 p-3 transition-all bg-white shadow-sm';
            if (btn) {
                btn.className = 'btn btn-sm w-100 rounded-pill py-1 fw-semibold btn-outline-danger';
                btn.innerHTML = `<i class="bi bi-plus-circle me-1"></i> Add Piece #${pieceNum} to Return`;
                btn.title = 'Click to add this piece to return consignment';
            }
            if (badge) {
                badge.className = 'badge bg-secondary-subtle text-secondary border font-monospace';
                badge.style.fontSize = '0.75rem';
                badge.innerHTML = `Piece #${pieceNum}`;
            }
        }
    });

    // Update dynamic selected piece tags tray in config box
    renderModalSelectedPiecesList();
}


document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.item-select');
    const returnQtyInputs = document.querySelectorAll('.return-qty');
    const summarySelected = document.getElementById('summarySelectedCount');
    const summaryTotalUnits = document.getElementById('summaryTotalUnits');
    const summaryTotalValue = document.getElementById('summaryTotalValue');
    const btnSubmit = document.getElementById('btnSubmitDispatch');
    const selectedChipsTray = document.getElementById('selectedChipsTray');

    // Elements for Section 2: Selected Products Section
    const selectedSectionBadge = document.getElementById('selectedSectionBadge');
    const btnClearAllSelectedSection = document.getElementById('btnClearAllSelectedSection');
    const selectedProductsEmptyState = document.getElementById('selectedProductsEmptyState');
    const selectedProductsTableWrapper = document.getElementById('selectedProductsTableWrapper');
    const selectedProductsTableBody = document.getElementById('selectedProductsTableBody');
    const selectedTableTotalQty = document.getElementById('selectedTableTotalQty');
    const selectedTableTotalValue = document.getElementById('selectedTableTotalValue');
    const damageScanInput = document.getElementById('damageScanInput');
    const damageScanStatus = document.getElementById('damageScanStatus');
    function selectScannedRow(row, code) {
        const idx = row.getAttribute('data-idx');
        const checkbox = document.querySelector(`.item-select[data-idx="${idx}"]`);
        const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
        const maxQty = parseInt(row.getAttribute('data-max-stock')) || 1;
        const categoryType = (row.getAttribute('data-category-type') || 'NORMAL').toUpperCase();
        const allowMultiple = categoryType === 'SERIES'
            || row.getAttribute('data-complementary') === '1'
            || (row.getAttribute('data-qr-type') || '').toUpperCase() === 'SERIES_BATCH'
            || parseInt(row.getAttribute('data-pack-quantity')) > 1;
        const tagInput = row.querySelector('.scanned-piece-tag');
        const pieceTags = (row.getAttribute('data-piece-tags') || '').split('|').map(tag => tag.trim().toLowerCase()).filter(Boolean);
        const damagedTags = JSON.parse(row.getAttribute('data-damaged-piece-tags') || '[]')
            .map(tag => String(tag).trim().toLowerCase());
        const scannedTag = code.trim().toLowerCase();
        if (tagInput) {
            tagInput.value = !allowMultiple && (pieceTags.includes(scannedTag) || damagedTags.includes(scannedTag)) ? code.trim() : '';
        }
        if (checkbox && !checkbox.checked) checkbox.checked = true;
        else if (qtyInput && allowMultiple) qtyInput.value = Math.min(maxQty, (parseInt(qtyInput.value) || 0) + 1);
        calculateConsignment();
        const stockState = row.getAttribute('data-stock-state') || 'AVAILABLE';
        damageScanStatus.innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Added: ${escapeHtml(row.getAttribute('data-name-raw') || code)} (${stockState})</span>`;
        damageScanInput.value = '';
        damageScanInput.focus();
    }

    function scanDamagedProduct() {
        const rawCode = (damageScanInput?.value || '').trim();
        if (!rawCode) return;
        const selectedStockPool = document.querySelector('input[name="scanStockPool"]:checked')?.value || 'AVAILABLE';
        const rows = Array.from(document.querySelectorAll('.inv-row'));
        const scanCandidates = [rawCode];
        if (rawCode.startsWith('{') && rawCode.endsWith('}')) {
            try {
                const payload = JSON.parse(rawCode);
                ['tag', 'tag_code', 'code', 'qr_code', 'sku'].forEach(key => {
                    if (payload[key]) scanCandidates.push(String(payload[key]).trim());
                });
            } catch (error) {
                damageScanStatus.innerHTML = '<span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>Invalid QR payload.</span>';
                return;
            }
        } else if (rawCode.includes('|')) {
            rawCode.split('|').forEach(part => {
                const candidate = part.trim();
                if (candidate && !/^(MRP|Disc|Cost|Price):/i.test(candidate)) scanCandidates.push(candidate);
            });
        }
        const normalizedCandidates = [...new Set(scanCandidates.map(candidate => candidate.toLowerCase()))];
        const damagedTagRows = rows.filter(candidate => {
            const tags = JSON.parse(candidate.getAttribute('data-damaged-piece-tags') || '[]')
                .map(tag => String(tag).trim().toLowerCase());
            return tags.some(tag => normalizedCandidates.includes(tag));
        });
        if (damagedTagRows.length > 0) {
            if (selectedStockPool !== 'DAMAGED') {
                damageScanStatus.innerHTML = '<span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>This QR is assigned to Damage Management. Select Damaged Stock to scan it.</span>';
                return;
            }
            const damagedTagRow = damagedTagRows.find(candidate =>
                (candidate.getAttribute('data-stock-state') || '').toUpperCase() === 'DAMAGED'
            );
            if (!damagedTagRow) {
                damageScanStatus.innerHTML = '<span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>This damage-tagged piece has no damaged stock balance available for return.</span>';
                return;
            }
            selectScannedRow(damagedTagRow, normalizedCandidates.find(candidate =>
                JSON.parse(damagedTagRow.getAttribute('data-damaged-piece-tags') || '[]')
                    .some(tag => String(tag).trim().toLowerCase() === candidate)
            ) || rawCode);
            return;
        }

        if (selectedStockPool === 'DAMAGED') {
            const availableTagRow = rows.find(candidate => {
                if (candidate.getAttribute('data-stock-state') !== 'AVAILABLE') return false;
                const tags = (candidate.getAttribute('data-piece-tags') || '').split('|')
                    .map(tag => tag.trim().toLowerCase());
                return tags.some(tag => normalizedCandidates.includes(tag));
            });
            if (availableTagRow) {
                damageScanStatus.innerHTML = '<span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>This QR belongs to Available Stock, not Damage Management. Select Available Stock to scan it.</span>';
                return;
            }
        }

        const row = rows.find(candidate => {
            if ((candidate.getAttribute('data-stock-state') || 'AVAILABLE').toUpperCase() !== selectedStockPool) return false;
            if (selectedStockPool === 'DAMAGED' && (candidate.getAttribute('data-category-type') || 'NORMAL').toUpperCase() === 'NORMAL'
                && JSON.parse(candidate.getAttribute('data-damaged-piece-tags') || '[]').length > 0) return false;
            const values = [
                candidate.getAttribute('data-sku'), candidate.getAttribute('data-code'),
                candidate.getAttribute('data-qr-code')
            ].filter(Boolean).map(value => value.toLowerCase());
            const pieceTags = (candidate.getAttribute('data-piece-tags') || '').split('|').map(value => value.toLowerCase());
            return values.some(value => normalizedCandidates.includes(value))
                || pieceTags.some(value => normalizedCandidates.includes(value));
        });
        if (row) {
            const scannedPieceTag = normalizedCandidates.find(candidate => {
                const pieceTags = (row.getAttribute('data-piece-tags') || '').split('|')
                    .map(tag => tag.trim().toLowerCase());
                const damageTags = JSON.parse(row.getAttribute('data-damaged-piece-tags') || '[]')
                    .map(tag => String(tag).trim().toLowerCase());
                return pieceTags.includes(candidate) || damageTags.includes(candidate);
            });
            selectScannedRow(row, scannedPieceTag || rawCode);
            return;
        }
        if (selectedStockPool === 'DAMAGED') {
            const damagedVariantRow = rows.find(candidate =>
                candidate.getAttribute('data-stock-state') === 'DAMAGED'
                && JSON.parse(candidate.getAttribute('data-damaged-piece-tags') || '[]').length > 0
                && [candidate.getAttribute('data-sku'), candidate.getAttribute('data-code'), candidate.getAttribute('data-qr-code')]
                    .filter(Boolean)
                    .some(value => normalizedCandidates.includes(value.toLowerCase()))
            );
            if (damagedVariantRow) {
                damageScanStatus.innerHTML = '<span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>This product has damage-tagged pieces. Scan the exact QR assigned in Damage Management.</span>';
                return;
            }
        }

        damageScanStatus.innerHTML = '<span class="text-primary fw-semibold"><span class="spinner-border spinner-border-sm me-1"></span>Looking up QR code...</span>';
        fetch(`<?= url('api/shop/damage/lookup-scan') ?>?purpose=return&code=${encodeURIComponent(rawCode)}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Product not found');
                const variantId = String(data.variant_id || data.id || '');
                const matchedRow = rows.find(candidate =>
                    String(candidate.getAttribute('data-variant-id')) === variantId
                    && (candidate.getAttribute('data-stock-state') || 'AVAILABLE').toUpperCase() === selectedStockPool
                    && !(selectedStockPool === 'DAMAGED'
                        && (candidate.getAttribute('data-category-type') || 'NORMAL').toUpperCase() === 'NORMAL'
                        && JSON.parse(candidate.getAttribute('data-damaged-piece-tags') || '[]').length > 0)
                );
                if (!matchedRow) throw new Error('This product has no available or damaged stock in the selected shop.');
                const scannedTag = String(data.tag_code || normalizedCandidates[0] || rawCode);
                const rowDamageTags = JSON.parse(matchedRow.getAttribute('data-damaged-piece-tags') || '[]')
                    .map(tag => String(tag).trim().toLowerCase());
                if (selectedStockPool === 'DAMAGED' && rowDamageTags.length > 0 && !rowDamageTags.includes(scannedTag.toLowerCase())) {
                    throw new Error('Scan the exact piece QR assigned to this product in Damage Management.');
                }
                if (selectedStockPool === 'AVAILABLE' && rowDamageTags.includes(scannedTag.toLowerCase())) {
                    throw new Error('This QR is assigned to Damage Management. Select Damaged Stock to scan it.');
                }
                selectScannedRow(matchedRow, scannedTag);
            })
            .catch(error => {
                damageScanStatus.innerHTML = `<span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>${escapeHtml(error.message)}</span>`;
                damageScanInput.select();
            });
    }

    calculateConsignment = function() {
        let selectedCount = 0;
        let totalUnits = 0;
        let totalValue = 0;
        let chipsHtml = '';
        let selectedTableRowsHtml = '';

        checkboxes.forEach(chk => {
            const idx = chk.getAttribute('data-idx');
            const row = document.querySelector(`.inv-row[data-idx="${idx}"]`);
            const hiddenInputs = document.querySelectorAll(`input[type="hidden"][data-idx="${idx}"]`);
            const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
            const minusBtn = document.querySelector(`.row-qty-minus[data-idx="${idx}"]`);
            const plusBtn = document.querySelector(`.row-qty-plus[data-idx="${idx}"]`);
            const classSelect = document.querySelector(`.return-classification[data-idx="${idx}"]`);
            const selectLabel = row.querySelector('.select-label');
            const selectedRowBadge = row.querySelector('.selected-row-badge');

            const variantId = row.getAttribute('data-variant-id') || '';
            const maxStock = parseInt(row.getAttribute('data-max-stock')) || 0;
            const categoryType = (row.getAttribute('data-category-type') || 'NORMAL').toUpperCase();
            const allowMultiple = categoryType === 'SERIES'
                || row.getAttribute('data-complementary') === '1'
                || (row.getAttribute('data-qr-type') || '').toUpperCase() === 'SERIES_BATCH'
                || parseInt(row.getAttribute('data-pack-quantity')) > 1;
            const unitPrice = parseFloat(row.getAttribute('data-price')) || 0;
            const skuRaw = row.getAttribute('data-sku-raw') || '';
            const nameRaw = row.getAttribute('data-name-raw') || '';
            const codeRaw = row.getAttribute('data-code-raw') || '';
            const sizeRaw = row.getAttribute('data-size') || 'Std';
            const colorRaw = row.getAttribute('data-color') || 'Std';
            const imgRaw = row.getAttribute('data-image') || '';

            if (chk.checked) {
                selectedCount++;
                
                // Color Highlighting for Row
                row.classList.add('inv-row-selected');
                if (selectLabel) {
                    selectLabel.textContent = 'Selected';
                    selectLabel.className = 'text-success fw-bold select-label';
                }
                if (selectedRowBadge) selectedRowBadge.classList.remove('d-none');

                hiddenInputs.forEach(h => h.disabled = false);
                qtyInput.disabled = !allowMultiple;
                if (minusBtn) minusBtn.disabled = !allowMultiple;
                if (plusBtn) plusBtn.disabled = !allowMultiple;
                classSelect.disabled = false;

                let qty = parseInt(qtyInput.value) || 0;
                if (!allowMultiple) qty = 1;
                if (qty <= 0) qty = 1; // When selected, ensure at least 1
                if (qty > maxStock) qty = maxStock;
                qtyInput.value = qty;

                const lineTotal = (qty * unitPrice);
                totalUnits += qty;
                totalValue += lineTotal;

                // Chip in left summary drawer
                chipsHtml += `
                    <span class="badge bg-success text-white border d-inline-flex align-items-center gap-1 px-2 py-1" style="font-size: 10px;">
                        ${escapeHtml(skuRaw.toUpperCase())} (${qty} pcs)
                        <i class="bi bi-x-circle cursor-pointer ms-1 remove-chip-btn" data-idx="${idx}" title="Remove"></i>
                    </span>
                `;

                // Row in Extra Section: Selected Products Table
                const curCondition = classSelect ? classSelect.value : 'DAMAGED';
                const thumbnailHtml = imgRaw 
                    ? `<img src="${escapeHtml(imgRaw)}" class="rounded border shadow-sm flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover;">`
                    : `<div class="rounded bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 38px; height: 38px;"><i class="bi bi-box-seam fs-6"></i></div>`;
                const productTypeLabel = row.getAttribute('data-complementary') === '1'
                    ? 'Complementary Product'
                    : (categoryType === 'SERIES' || (row.getAttribute('data-qr-type') || '').toUpperCase() === 'SERIES_BATCH' || parseInt(row.getAttribute('data-pack-quantity')) > 1)
                        ? 'Series Product'
                        : 'Normal Product';
                const productTypeClass = productTypeLabel === 'Complementary Product'
                    ? 'bg-warning-subtle text-warning-emphasis'
                    : productTypeLabel === 'Series Product'
                        ? 'bg-info-subtle text-info-emphasis'
                        : 'bg-secondary-subtle text-secondary';

                selectedTableRowsHtml += `
                    <tr class="selected-product-entry" data-idx="${idx}" data-variant-id="${variantId}">
                        <td class="ps-3 text-muted small fw-semibold">${selectedCount}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                ${thumbnailHtml}
                                <div>
                                    <div class="fw-bold text-dark small">${escapeHtml(nameRaw)} <span class="badge ${productTypeClass} ms-1" style="font-size: 9px;">${productTypeLabel}</span></div>
                                    <small class="text-muted font-monospace">${escapeHtml(codeRaw)}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="font-monospace text-primary fw-bold small">${escapeHtml(skuRaw)}</span>
                        </td>
                        <td>
                            <span class="badge bg-dark text-white font-monospace" style="font-size: 10px;">${escapeHtml(sizeRaw)}</span>
                            <span class="badge bg-secondary text-white font-monospace ms-1" style="font-size: 10px;">${escapeHtml(colorRaw)}</span>
                        </td>
                        <td class="text-center">
                            <div class="input-group input-group-sm" style="width: 105px; margin: 0 auto;">
                                <button type="button" class="btn btn-outline-secondary px-2 sec-qty-minus" data-idx="${idx}" ${allowMultiple ? '' : 'disabled'} title="Decrease Qty">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" class="form-control form-control-sm text-center font-monospace fw-bold sec-qty-input px-1" 
                                       value="${qty}" min="1" max="${maxStock}" data-idx="${idx}" ${allowMultiple ? '' : 'disabled'}>
                                <button type="button" class="btn btn-outline-secondary px-2 sec-qty-plus" data-idx="${idx}" ${allowMultiple ? '' : 'disabled'} title="Increase Qty">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block" style="font-size: 9px;">Max: ${maxStock} pcs</small>
                        </td>
                        <td>
                            <select class="form-select form-select-sm sec-condition-select shadow-sm" data-idx="${idx}" style="font-size: 11px;">
                                <option value="DAMAGED" ${curCondition === 'DAMAGED' ? 'selected' : ''}>Damaged (Defect)</option>
                                <option value="GOOD" ${curCondition === 'GOOD' ? 'selected' : ''}>Good (Restock)</option>
                                <option value="DEFECTIVE" ${curCondition === 'DEFECTIVE' ? 'selected' : ''}>Defective Quality</option>
                                <option value="WRONG_SIZE" ${curCondition === 'WRONG_SIZE' ? 'selected' : ''}>Wrong Size / Style</option>
                            </select>
                        </td>
                        <td class="text-end fw-semibold text-dark small">₹${unitPrice.toFixed(2)}</td>
                        <td class="text-end fw-bold text-success font-monospace">₹${lineTotal.toFixed(2)}</td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-1">
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-1 sec-remove-btn" style="width: 28px; height: 28px;" data-idx="${idx}" title="Remove Product">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                // Unselected Row Reset
                row.classList.remove('inv-row-selected');
                if (selectLabel) {
                    selectLabel.textContent = 'Select';
                    selectLabel.className = 'text-muted select-label';
                }
                if (selectedRowBadge) selectedRowBadge.classList.add('d-none');

                hiddenInputs.forEach(h => h.disabled = true);
                qtyInput.disabled = true;
                qtyInput.value = 0;
                if (minusBtn) minusBtn.disabled = true;
                classSelect.disabled = true;
            }
        });

        // Update Left Summary Box
        summarySelected.innerText = `${selectedCount} item(s)`;
        summaryTotalUnits.innerText = `${totalUnits} pcs`;
        summaryTotalValue.innerText = `₹${totalValue.toFixed(2)}`;
        btnSubmit.disabled = (selectedCount === 0 || totalUnits <= 0);

        // Update Section 2: Selected Products Section
        if (selectedCount > 0) {
            selectedSectionBadge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> ${selectedCount} Products Selected (${totalUnits} pcs)`;
            selectedSectionBadge.className = 'badge bg-success text-white rounded-pill px-3 py-2 fw-semibold';
            if (btnClearAllSelectedSection) btnClearAllSelectedSection.classList.remove('d-none');
            if (selectedProductsEmptyState) selectedProductsEmptyState.classList.add('d-none');
            if (selectedProductsTableWrapper) selectedProductsTableWrapper.classList.remove('d-none');
            if (selectedProductsTableBody) selectedProductsTableBody.innerHTML = selectedTableRowsHtml;
            if (selectedTableTotalQty) selectedTableTotalQty.innerText = `${totalUnits} pcs`;
            if (selectedTableTotalValue) selectedTableTotalValue.innerText = `₹${totalValue.toFixed(2)}`;

            // Wire up event listeners inside the Selected Products Section table
            attachSelectedSectionListeners();
        } else {
            selectedSectionBadge.innerHTML = `<i class="bi bi-info-circle me-1"></i> 0 Products Selected`;
            selectedSectionBadge.className = 'badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-2 fw-semibold';
            if (btnClearAllSelectedSection) btnClearAllSelectedSection.classList.add('d-none');
            if (selectedProductsEmptyState) selectedProductsEmptyState.classList.remove('d-none');
            if (selectedProductsTableWrapper) selectedProductsTableWrapper.classList.add('d-none');
            if (selectedProductsTableBody) selectedProductsTableBody.innerHTML = '';
            if (selectedTableTotalQty) selectedTableTotalQty.innerText = `0 pcs`;
            if (selectedTableTotalValue) selectedTableTotalValue.innerText = `₹0.00`;
        }

        // Summary Tray Chips
        if (chipsHtml) {
            selectedChipsTray.innerHTML = chipsHtml;
            document.querySelectorAll('.remove-chip-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idx = this.getAttribute('data-idx');
                    const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
                    if (chk) {
                        chk.checked = false;
                        calculateConsignment();
                    }
                });
            });
        } else {
            selectedChipsTray.innerHTML = '<small class="text-muted fst-italic" style="font-size: 10px;">Select products from the table to dispatch.</small>';
        }
    };

    document.getElementById('btnDamageScan')?.addEventListener('click', scanDamagedProduct);
    damageScanInput?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            scanDamagedProduct();
        }
    });
    // Also accept a manually typed/pasted QR when the field is submitted by a scanner
    // configured without a trailing Enter key.
    damageScanInput?.addEventListener('change', scanDamagedProduct);
    document.querySelectorAll('input[name="scanStockPool"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const label = this.value === 'DAMAGED' ? 'damaged' : 'available';
            if (damageScanStatus) damageScanStatus.textContent = `Scanner is ready for ${label} stock.`;
            damageScanInput?.focus();
        });
    });

    // Attach listeners for interactive controls in Section 2 table
    function attachSelectedSectionListeners() {
        // Section +/- steppers
        document.querySelectorAll('.sec-qty-minus').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = this.getAttribute('data-idx');
                const mainQtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
                const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
                let val = parseInt(mainQtyInput?.value) || 0;
                if (val > 1) {
                    if (mainQtyInput) mainQtyInput.value = val - 1;
                } else {
                    if (chk) chk.checked = false;
                    if (mainQtyInput) mainQtyInput.value = 0;
                }
                calculateConsignment();
            });
        });

        document.querySelectorAll('.sec-qty-plus').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = this.getAttribute('data-idx');
                const mainQtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
                const row = document.querySelector(`.inv-row[data-idx="${idx}"]`);
                const maxStock = parseInt(row?.getAttribute('data-max-stock')) || 1;
                let val = parseInt(mainQtyInput?.value) || 0;
                if (val < maxStock) {
                    if (mainQtyInput) mainQtyInput.value = val + 1;
                    calculateConsignment();
                }
            });
        });

        // Section direct quantity typing
        document.querySelectorAll('.sec-qty-input').forEach(input => {
            input.addEventListener('input', function() {
                const idx = this.getAttribute('data-idx');
                const mainQtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
                const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
                const row = document.querySelector(`.inv-row[data-idx="${idx}"]`);
                const maxStock = parseInt(row?.getAttribute('data-max-stock')) || 1;

                let val = parseInt(this.value) || 0;
                if (val > maxStock) val = maxStock;

                if (val > 0) {
                    if (mainQtyInput) mainQtyInput.value = val;
                } else {
                    if (chk) chk.checked = false;
                    if (mainQtyInput) mainQtyInput.value = 0;
                }
                calculateConsignment();
            });
        });

        // Section condition change sync
        document.querySelectorAll('.sec-condition-select').forEach(sel => {
            sel.addEventListener('change', function() {
                const idx = this.getAttribute('data-idx');
                const mainClassSelect = document.querySelector(`.return-classification[data-idx="${idx}"]`);
                if (mainClassSelect) mainClassSelect.value = this.value;
            });
        });

        // Section remove button
        document.querySelectorAll('.sec-remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = this.getAttribute('data-idx');
                const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
                const mainQtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
                if (chk) chk.checked = false;
                if (mainQtyInput) mainQtyInput.value = 0;
                calculateConsignment();
            });
        });
    }

    // When main table checkbox is clicked
    checkboxes.forEach(chk => {
        chk.addEventListener('change', function() {
            const idx = this.getAttribute('data-idx');
            const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
            if (this.checked) {
                if (qtyInput && (parseInt(qtyInput.value) || 0) <= 0) {
                    qtyInput.value = 1;
                }
            } else {
                if (qtyInput) {
                    qtyInput.value = 0;
                }
            }
            calculateConsignment();
        });
    });

    // Row inline steppers in ledger table
    document.querySelectorAll('.row-qty-minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const idx = this.getAttribute('data-idx');
            const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
            const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
            let val = parseInt(qtyInput.value) || 0;
            if (val > 1) {
                qtyInput.value = val - 1;
                calculateConsignment();
            } else {
                if (chk) chk.checked = false;
                qtyInput.value = 0;
                calculateConsignment();
            }
        });
    });

    document.querySelectorAll('.row-qty-plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const idx = this.getAttribute('data-idx');
            const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
            const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
            const row = document.querySelector(`.inv-row[data-idx="${idx}"]`);
            const maxStock = parseInt(row?.getAttribute('data-max-stock')) || 1;

            let val = parseInt(qtyInput.value) || 0;
            if (!chk.checked || val === 0) {
                chk.checked = true;
                qtyInput.value = 1;
            } else if (val < maxStock) {
                qtyInput.value = val + 1;
            }
            calculateConsignment();
        });
    });

    // When typing directly into return qty input in ledger table
    returnQtyInputs.forEach(qInput => {
        qInput.addEventListener('input', function() {
            const idx = this.getAttribute('data-idx');
            const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
            const row = document.querySelector(`.inv-row[data-idx="${idx}"]`);
            const maxStock = parseInt(row?.getAttribute('data-max-stock')) || 1;

            let val = parseInt(this.value) || 0;
            if (val > maxStock) {
                val = maxStock;
                this.value = val;
            }

            if (val > 0) {
                if (chk) chk.checked = true;
            } else {
                if (chk) chk.checked = false;
                this.value = 0;
            }
            calculateConsignment();
        });
    });

    // Modal Stepper Buttons
    const btnQtyMinus = document.getElementById('modalQtyMinusBtn');
    const btnQtyPlus = document.getElementById('modalQtyPlusBtn');
    const modalQtyInput = document.getElementById('modalReturnQtyInput');

    if (btnQtyMinus && modalQtyInput) {
        btnQtyMinus.addEventListener('click', function() {
            let val = parseInt(modalQtyInput.value) || 0;
            if (val > 1) {
                val = val - 1;
                modalQtyInput.value = val;
                syncPieceSetWithQty(val);
                applyModalSelectionToForm();
            } else if (val === 1) {
                modalQtyInput.value = 0;
                removeModalSelectionFromForm();
            }
        });
    }

    if (btnQtyPlus && modalQtyInput) {
        btnQtyPlus.addEventListener('click', function() {
            let val = parseInt(modalQtyInput.value) || 0;
            if (val < currentModalMaxStock) {
                val = val + 1;
                modalQtyInput.value = val;
                syncPieceSetWithQty(val);
                applyModalSelectionToForm();
            }
        });
    }

    if (modalQtyInput) {
        modalQtyInput.addEventListener('input', function() {
            let val = parseInt(this.value) || 0;
            if (val > currentModalMaxStock) {
                val = currentModalMaxStock;
                this.value = val;
            }
            syncPieceSetWithQty(val);
            if (val > 0) {
                applyModalSelectionToForm();
            } else {
                this.value = 0;
                removeModalSelectionFromForm();
            }
        });
    }

    // Modal Action Buttons
    const btnModalSelect = document.getElementById('btnModalSelectForReturn');
    const btnModalSelectFooter = document.getElementById('btnModalSelectForReturnFooter');
    const btnModalDeselect = document.getElementById('btnModalDeselectFromReturn');

    if (btnModalSelect) {
        btnModalSelect.addEventListener('click', function() {
            let val = parseInt(modalQtyInput?.value) || 0;
            if (val <= 0) {
                val = 1;
                if (modalQtyInput) modalQtyInput.value = 1;
                syncPieceSetWithQty(1);
            }
            applyModalSelectionToForm();
        });
    }
    if (btnModalSelectFooter) {
        btnModalSelectFooter.addEventListener('click', function() {
            let val = parseInt(modalQtyInput?.value) || 0;
            if (val <= 0) {
                val = 1;
                if (modalQtyInput) modalQtyInput.value = 1;
                syncPieceSetWithQty(1);
            }
            applyModalSelectionToForm();
            const modalEl = document.getElementById('productDetailModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        });
    }
    if (btnModalDeselect) {
        btnModalDeselect.addEventListener('click', removeModalSelectionFromForm);
    }

    // Filter Functionality
    const searchInput = document.getElementById('filterSearchInput');
    const catSelect = document.getElementById('filterCategorySelect');
    const poolSelect = document.getElementById('filterStockPoolSelect');

    function filterTable() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const selCat = catSelect?.value || 'ALL';
        const selPool = poolSelect?.value || 'ALL';

        document.querySelectorAll('.inv-row').forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const sku = row.getAttribute('data-sku') || '';
            const code = row.getAttribute('data-code') || '';
            const cat = row.getAttribute('data-category') || '';
            const pool = row.getAttribute('data-stock-state') || '';

            const matchQuery = !query || name.includes(query) || sku.includes(query) || code.includes(query) || cat.toLowerCase().includes(query);
            const matchCat = (selCat === 'ALL' || cat === selCat);
            const matchPool = (selPool === 'ALL' || pool === selPool);

            if (matchQuery && matchCat && matchPool) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (catSelect) catSelect.addEventListener('change', filterTable);
    if (poolSelect) poolSelect.addEventListener('change', filterTable);

    // Select All / Clear All
    const btnSelectAll = document.getElementById('btnSelectAllVisible');
    const btnClear = document.getElementById('btnClearSelection');

    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', function() {
            document.querySelectorAll('.inv-row').forEach(row => {
                if (row.style.display !== 'none') {
                    const idx = row.getAttribute('data-idx');
                    const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
                    const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
                    if (chk) chk.checked = true;
                    if (qtyInput && (parseInt(qtyInput.value) || 0) <= 0) qtyInput.value = 1;
                }
            });
            calculateConsignment();
        });
    }

    function clearAllSelection() {
        checkboxes.forEach(chk => {
            chk.checked = false;
            const idx = chk.getAttribute('data-idx');
            const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
            if (qtyInput) qtyInput.value = 0;
        });
        calculateConsignment();
    }

    if (btnClear) btnClear.addEventListener('click', clearAllSelection);
    if (btnClearAllSelectedSection) btnClearAllSelectedSection.addEventListener('click', clearAllSelection);

    // =========================================================================
    // 🚚 FORM VALIDATION & DIRECT OUTWARD DISPATCH SUBMISSION
    // =========================================================================
    const btnSubmitDispatch = document.getElementById('btnSubmitDispatch');
    const returnDispatchForm = document.getElementById('returnDispatchForm');

    // Sidebar Logistics Inputs
    const fieldCarrierName = document.getElementById('fieldCarrierName');
    const fieldVehicleNo = document.getElementById('fieldVehicleNo');
    const fieldCartonCount = document.getElementById('fieldCartonCount');
    const fieldDriverName = document.getElementById('fieldDriverName');
    const fieldDriverPhone = document.getElementById('fieldDriverPhone');
    const fieldDriverPhoneError = document.getElementById('fieldDriverPhoneError');
    const fieldTrackingNo = document.getElementById('fieldTrackingNo');
    const fieldRemarks = document.getElementById('fieldRemarks');

    // Indian Mobile Number Regex: 10 digits starting with 6, 7, 8, or 9
    const mobileRegex = /^[6-9]\d{9}$/;

    // Live Formatter & Validator for Driver Phone
    function setupPhoneValidation(inputEl, errorEl) {
        if (!inputEl) return;
        inputEl.addEventListener('input', function() {
            // Strip any non-digit character and limit to 10 digits
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
            validatePhone(inputEl, errorEl);
        });
        inputEl.addEventListener('blur', function() {
            validatePhone(inputEl, errorEl);
        });
    }

    function validatePhone(inputEl, errorEl) {
        if (!inputEl) return true;
        const val = inputEl.value.trim();
        if (val === '') {
            inputEl.classList.remove('is-invalid', 'is-valid');
            return true;
        }
        if (!mobileRegex.test(val)) {
            inputEl.classList.add('is-invalid');
            inputEl.classList.remove('is-valid');
            if (errorEl) {
                if (val.length < 10) {
                    errorEl.textContent = `Phone number must be 10 digits (${val.length}/10 entered).`;
                } else if (!/^[6-9]/.test(val)) {
                    errorEl.textContent = 'Indian mobile number must start with 6, 7, 8, or 9.';
                } else {
                    errorEl.textContent = 'Please enter a valid 10-digit mobile number.';
                }
            }
            return false;
        } else {
            inputEl.classList.remove('is-invalid');
            inputEl.classList.add('is-valid');
            return true;
        }
    }

    // Live Formatter for Vehicle Number (Uppercase & Alphanumeric + Hyphen/Space)
    function setupVehicleValidation(inputEl) {
        if (!inputEl) return;
        inputEl.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s-]/g, '').slice(0, 20);
            inputEl.classList.remove('is-invalid');
        });
    }

    // Live Formatter for Cartons Count
    function setupCartonValidation(inputEl) {
        if (!inputEl) return;
        inputEl.addEventListener('input', function() {
            let val = parseInt(this.value);
            if (isNaN(val) || val < 1) {
                inputEl.classList.add('is-invalid');
            } else {
                if (val > 999) this.value = 999;
                inputEl.classList.remove('is-invalid');
            }
        });
    }

    // Live Formatter for Carrier Name
    function setupCarrierValidation(inputEl) {
        if (!inputEl) return;
        inputEl.addEventListener('input', function() {
            if (this.value.trim().length === 0) {
                inputEl.classList.add('is-invalid');
            } else {
                inputEl.classList.remove('is-invalid');
            }
        });
    }

    setupPhoneValidation(fieldDriverPhone, fieldDriverPhoneError);
    setupVehicleValidation(fieldVehicleNo);
    setupCartonValidation(fieldCartonCount);
    setupCarrierValidation(fieldCarrierName);

    // Validate Section Logistics Details
    function validateLogisticsSection() {
        let isValid = true;
        let firstInvalid = null;

        // Carrier Validation
        if (!fieldCarrierName || fieldCarrierName.value.trim().length === 0) {
            if (fieldCarrierName) {
                fieldCarrierName.classList.add('is-invalid');
                if (!firstInvalid) firstInvalid = fieldCarrierName;
            }
            isValid = false;
        } else {
            fieldCarrierName.classList.remove('is-invalid');
        }

        // Carton Count Validation
        const cartonVal = parseInt(fieldCartonCount?.value) || 0;
        if (!fieldCartonCount || cartonVal < 1 || cartonVal > 999) {
            if (fieldCartonCount) {
                fieldCartonCount.classList.add('is-invalid');
                if (!firstInvalid) firstInvalid = fieldCartonCount;
            }
            isValid = false;
        } else {
            fieldCartonCount.classList.remove('is-invalid');
        }

        // Driver Phone Validation (optional, but must be valid if provided)
        if (fieldDriverPhone && fieldDriverPhone.value.trim() !== '') {
            if (!validatePhone(fieldDriverPhone, fieldDriverPhoneError)) {
                isValid = false;
                if (!firstInvalid) firstInvalid = fieldDriverPhone;
            }
        } else if (fieldDriverPhone) {
            fieldDriverPhone.classList.remove('is-invalid');
        }

        if (!isValid && firstInvalid) {
            firstInvalid.focus();
        }

        return isValid;
    }

    if (btnSubmitDispatch && returnDispatchForm) {
        btnSubmitDispatch.addEventListener('click', function(e) {
            e.preventDefault();

            // 1. Validate Sidebar Logistics Details
            if (!validateLogisticsSection()) {
                return;
            }

            // 2. Validate that we have items selected
            let totalUnits = 0;
            let totalCount = 0;
            let hasExceededStock = false;
            let invalidItemName = '';

            checkboxes.forEach(chk => {
                if (chk.checked) {
                    const idx = chk.getAttribute('data-idx');
                    const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
                    const qty = parseInt(qtyInput?.value) || 0;
                    const row = document.querySelector(`.inv-row[data-idx="${idx}"]`);
                    const maxStock = parseInt(row?.getAttribute('data-max-stock')) || 0;
                    const nameRaw = row?.getAttribute('data-name-raw') || 'Item';

                    if (qty > maxStock) {
                        hasExceededStock = true;
                        invalidItemName = nameRaw;
                        if (qtyInput) qtyInput.classList.add('is-invalid');
                    } else if (qty > 0) {
                        totalCount++;
                        totalUnits += qty;
                        if (qtyInput) qtyInput.classList.remove('is-invalid');
                    }
                }
            });

            if (hasExceededStock) {
                alert(`The return quantity for "${invalidItemName}" exceeds available shop inventory stock.`);
                return;
            }

            if (totalCount === 0 || totalUnits <= 0) {
                alert('Please select at least 1 product item with quantity > 0 before proceeding to outward dispatch.');
                return;
            }

            // 3. Ensure all selected inputs are enabled for POST submission
            checkboxes.forEach(chk => {
                const idx = chk.getAttribute('data-idx');
                const hiddenInputs = document.querySelectorAll(`input[type="hidden"][data-idx="${idx}"]`);
                const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
                const classSelect = document.querySelector(`.return-classification[data-idx="${idx}"]`);

                if (chk.checked) {
                    hiddenInputs.forEach(h => h.disabled = false);
                    if (qtyInput) qtyInput.disabled = false;
                    if (classSelect) classSelect.disabled = false;
                } else {
                    hiddenInputs.forEach(h => h.disabled = true);
                    if (qtyInput) qtyInput.disabled = true;
                    if (classSelect) classSelect.disabled = true;
                }
            });

            // 4. Change button state to loading & submit
            btnSubmitDispatch.disabled = true;
            btnSubmitDispatch.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing Outward Dispatch...`;

            // Submit Form directly
            returnDispatchForm.submit();
        });
    }

    // Initial calculation on load
    calculateConsignment();
});
</script>
