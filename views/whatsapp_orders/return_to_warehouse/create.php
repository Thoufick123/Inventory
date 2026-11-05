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
            <a href="<?= url('warehouse/whatsapp-orders/return-to-warehouse') ?>" class="btn btn-sm btn-light border rounded-circle me-3" title="Back to Consignments">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <div class="badge bg-success-subtle text-success mb-1"><i class="bi bi-whatsapp me-1"></i><?= e($channel['name'] ?? 'WhatsApp Channel') ?></div>
                <h3 class="fw-bold mb-1 text-dark">Initiate Return to Warehouse</h3>
                <p class="text-muted small mb-0">Select WhatsApp inventory products, inspect full product specifications &amp; piece QR codes, and dispatch return consignments to central warehouse.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= url('warehouse/whatsapp-orders/return-to-warehouse/store') ?>" id="returnDispatchForm">
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
                                <option value="Other WhatsApp Return">Other WhatsApp Return</option>
                            </select>
                        </div>

                        <!-- Outward Logistics Information -->
                        <div class="p-2 bg-light rounded-3 border mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-dark" style="font-size: 11px;"><i class="bi bi-box-seam me-1 text-primary"></i>Outward Details</span>
                                <span class="badge bg-primary-subtle text-primary" style="font-size: 9px;">Transport</span>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label text-muted small mb-1" style="font-size: 10px;">Carrier / Transporter</label>
                                <input type="text" name="carrier_name" id="fieldCarrierName" class="form-control form-control-sm bg-white" placeholder="e.g. GRM Logistics / DTDC / Self" value="GRM Dedicated Logistics">
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small mb-1" style="font-size: 10px;">Vehicle No</label>
                                    <input type="text" name="vehicle_no" id="fieldVehicleNo" class="form-control form-control-sm bg-white" placeholder="TN-01-AB-1234">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small mb-1" style="font-size: 10px;">Cartons / Boxes</label>
                                    <input type="number" name="carton_count" id="fieldCartonCount" class="form-control form-control-sm bg-white" min="1" value="1">
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small mb-1" style="font-size: 10px;">Driver / Bearer</label>
                                    <input type="text" name="driver_name" id="fieldDriverName" class="form-control form-control-sm bg-white" placeholder="Driver Name">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small mb-1" style="font-size: 10px;">Driver Phone</label>
                                    <input type="text" name="driver_phone" id="fieldDriverPhone" class="form-control form-control-sm bg-white" placeholder="Phone No">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label text-muted small mb-1" style="font-size: 10px;">LR / Docket / E-Way No</label>
                                <input type="text" name="tracking_no" id="fieldTrackingNo" class="form-control form-control-sm bg-white" placeholder="Tracking / Waybill No">
                            </div>

                            <div>
                                <label class="form-label text-muted small mb-1" style="font-size: 10px;">Outward Remarks / Notes</label>
                                <textarea name="remarks" id="fieldRemarks" class="form-control form-control-sm bg-white" rows="2" placeholder="Driver instructions, parcel tags, handover notes..."></textarea>
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
                        <span><strong>Scan &amp; Return Products:</strong> Choose <strong>Good / Available</strong> or <strong>Bad / Damaged</strong>, then scan or enter the product QR/SKU above.</span>
                    </div>
                </div>

                <div id="returnInventoryFilters" class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-body p-3">
                        <label for="returnScanInput" class="form-label fw-bold small text-dark mb-2"><i class="bi bi-qr-code-scan text-success me-1"></i>Scan Product for Warehouse Return</label>
                        <div class="input-group">
                            <span class="input-group-text bg-success text-white border-0"><i class="bi bi-upc-scan"></i></span>
                            <input type="text" id="returnScanInput" class="form-control font-monospace" placeholder="Scan a piece QR or product code and press Enter" autocomplete="off">
                            <button type="button" class="btn btn-success" id="btnReturnScan">Add to Return</button>
                        </div>
                        <div class="d-flex align-items-center gap-3 mt-2 small flex-wrap">
                            <span class="text-muted fw-semibold">Scan as:</span>
                            <label class="form-check mb-0"><input class="form-check-input" type="radio" name="returnScanPool" value="AVAILABLE" checked><span class="form-check-label text-success fw-semibold">Good / Available Stock</span></label>
                            <label class="form-check mb-0"><input class="form-check-input" type="radio" name="returnScanPool" value="DAMAGED"><span class="form-check-label text-danger fw-semibold">Bad / Damaged Stock</span></label>
                        </div>
                        <small id="returnScanStatus" class="text-muted d-block mt-2">Good scans select only available WhatsApp stock. Bad scans select only the damaged stock pool.</small>
                    </div>
                </div>

                <!-- 💥 SECTION 2: LIVE SELECTED RETURN PRODUCTS CONSIGNMENT SECTION -->
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
                            <p class="text-muted small mb-0">Scan a Good or Bad product QR/SKU above to add it to this return consignment.</p>
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
                                        <th class="text-center pe-4" style="width: 110px;">Scanned QR</th>
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

                <!-- 3. WHATSAPP STOCK LEDGER & AVAILABLE INVENTORY SELECTOR -->
                <!-- Search & Filter Card -->
                <div id="legacyInventoryFilters" class="card border-0 shadow-sm rounded-4 mb-4 bg-white d-none">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="filterSearchInput" class="form-control border-start-0 ps-0" placeholder="Search by Product Name, SKU, Style Code, Category...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select id="filterCategorySelect" class="form-select form-select-sm bg-light">
                                    <option value="ALL">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="filterStockPoolSelect" class="form-select form-select-sm bg-light">
                                    <option value="ALL">All Stock Pools</option>
                                    <option value="AVAILABLE">Available Stock</option>
                                    <option value="DAMAGED">Damaged Stock</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex gap-1 justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnSelectAllVisible">
                                    <i class="bi bi-check-all me-1"></i> Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-light border rounded-circle" id="btnClearSelection" title="Clear Selection">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ledger Table Card -->
                <div id="returnInventoryLedgerSection" class="card border-0 shadow-sm rounded-4 bg-white d-none">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-boxes me-2 text-primary"></i>3. WhatsApp Stock Ledger &amp; Product Selector</h5>
                        <span class="badge bg-light text-dark border rounded-pill px-3 py-2"><?= count($inventory) ?> Items in WhatsApp</span>
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
                                    <th class="text-center">WhatsApp Stock</th>
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
                                            No inventory records with positive stock found for WhatsApp channel.
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
                                            data-qr-code="<?= e(strtolower($item['qr_code'] ?? '')) ?>"
                                            data-piece-tags="<?= e(strtolower(implode('|', array_map('strval', $item['piece_tags'] ?? [])))) ?>"
                                            data-all-piece-tags="<?= e(strtolower(implode('|', array_map('strval', $item['piece_tags_all'] ?? [])))) ?>"
                                            data-category-type="<?= e(strtoupper($item['category_type'] ?? 'NORMAL')) ?>"
                                            data-complementary="<?= !empty($item['is_complementary']) ? '1' : '0' ?>"
                                            data-qr-type="<?= e(strtoupper($item['qr_type'] ?? '')) ?>"
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
                                                <input type="hidden" name="items[<?= $idx ?>][tag_code]" value="" disabled data-idx="<?= $idx ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][scanned_qr_codes]" value="[]" disabled class="return-scanned-qrs" data-idx="<?= $idx ?>">
                                            </td>

                                            <!-- 2. Product Name -->
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

                                            <!-- 3. SKU / QR Code -->
                                            <td>
                                                <div class="small fw-semibold text-dark font-monospace"><?= e($item['sku']) ?></div>
                                                <?php if (!empty($item['qr_code'])): ?>
                                                    <span class="badge bg-light text-dark border font-monospace" style="font-size:10px;"><i class="bi bi-qr-code me-1 text-warning"></i><?= e($item['qr_code']) ?></span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- 4. Category -->
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= e($item['category_name'] ?? 'General') ?></span>
                                                <?php if (!empty($item['subcategory_name'])): ?>
                                                    <small class="text-muted d-block" style="font-size: 11px;">› <?= e($item['subcategory_name']) ?></small>
                                                <?php endif; ?>
                                            </td>

                                            <!-- 5. Size & Color -->
                                            <td>
                                                <span class="badge bg-secondary-subtle text-secondary"><?= e($item['size_name'] ?? 'Std') ?></span>
                                                <span class="badge bg-secondary-subtle text-secondary ms-1"><?= e($item['color_name'] ?? 'Std') ?></span>
                                            </td>

                                            <!-- 6. Selling Price -->
                                            <td class="fw-bold text-dark">
                                                ₹<?= number_format((float)$item['selling_price'], 2) ?>
                                                <?php if (!empty($item['mrp']) && (float)$item['mrp'] > (float)$item['selling_price']): ?>
                                                    <small class="text-muted text-decoration-line-through d-block" style="font-size: 10px;">MRP ₹<?= number_format((float)$item['mrp'], 2) ?></small>
                                                <?php endif; ?>
                                            </td>

                                            <!-- 7. Stock & Pool -->
                                            <td class="text-center">
                                                <span class="fs-6 fw-bold <?= $isDamaged ? 'text-danger' : 'text-success' ?>"><?= (int)$qty ?></span>
                                                <small class="text-muted d-block" style="font-size:10px;">
                                                    <?= $isDamaged ? '<span class="text-danger fw-semibold">Damaged</span>' : '<span class="text-success">Available</span>' ?>
                                                </small>
                                            </td>

                                            <!-- 8. Return Quantity Stepper Input -->
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

                                            <!-- 10. Action: View Button -->
                                            <td class="text-end pe-4">
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 shadow-sm fw-semibold" 
                                                        onclick="openProductDetailModal(<?= (int)$item['variant_id'] ?>, '<?= e($item['stock_state']) ?>', <?= $idx ?>)" 
                                                        title="View Complete Product Details, Images &amp; Piece QR Codes">
                                                    <i class="bi bi-eye me-1"></i> View
                                                </button>
                                            </td>
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

<!-- MODAL: PRODUCT DETAILS & ALL PIECE QR CODES VIEW -->
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
                                                <small class="text-success fw-semibold d-block text-uppercase" style="font-size: 10px;">WhatsApp Stock</small>
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

                                    <!-- RETURN CONSIGNMENT SELECTION PANEL INSIDE MODAL -->
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
                                            <!-- Return Quantity Stepper -->
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
                                                <select id="modalClassificationSelect" class="form-select form-select-sm shadow-sm" disabled>
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

                                        <!-- DYNAMIC SELECTED RETURN PIECE TAGS LIST IN CONFIG BOX -->
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
                                                    <!-- Populated dynamically with piece tags chips and remove buttons -->
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

                    <!-- 3. ALL INDIVIDUAL PIECE QR CODES GRID -->
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

                    <!-- 4. Recent Stock Movements Table -->
                    <div class="card border-0 shadow-sm rounded-4 bg-white">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Recent Stock Movements in WhatsApp</h6>
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
</div>

<!-- ========================================================================= -->
<!-- 🚀 MODAL: OUTWARD PROCESS & TRANSPORTER CONFIRMATION MODAL -->
<!-- ========================================================================= -->
<div class="modal fade" id="outwardProcessConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-danger text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-white text-danger d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-box-arrow-right fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Confirm Return Outward Process</h5>
                        <small class="text-white-50">Review outward transporter, vehicle &amp; delivery gate pass details before dispatch</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- 1. Consignment Volume & Destination Summary -->
                <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white">
                    <div class="card-body p-3">
                        <div class="row g-3 text-center text-md-start align-items-center">
                            <div class="col-md-3 border-end">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Total Products</small>
                                <span class="fs-5 fw-bold text-dark" id="modalConfirmItemsCount">0 items</span>
                            </div>
                            <div class="col-md-3 border-end">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Total Units</small>
                                <span class="fs-4 fw-bold text-danger" id="modalConfirmTotalUnits">0 pcs</span>
                            </div>
                            <div class="col-md-3 border-end">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Estimated Value</small>
                                <span class="fs-5 fw-bold text-success font-monospace" id="modalConfirmTotalValue">₹0.00</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Destination</small>
                                <span class="fw-bold text-primary small d-block text-truncate" id="modalConfirmWarehouseName">Central Warehouse</span>
                                <span class="badge bg-light text-dark border" style="font-size: 9px;" id="modalConfirmReason">Damaged Return</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Outward Process & Transporter Form -->
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-truck text-danger me-2"></i>Outward Handover &amp; Logistics Details</h6>
                        <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded-pill small">Required for Gate Pass</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">Carrier / Transporter Name <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-truck text-muted"></i></span>
                                    <input type="text" id="modalFieldCarrierName" class="form-control" placeholder="e.g. GRM Dedicated Logistics / DTDC / Self Handover">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">Vehicle / Transport No</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-car-front text-muted"></i></span>
                                    <input type="text" id="modalFieldVehicleNo" class="form-control" placeholder="TN-01-AB-1234">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">Cartons / Packages <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-box-seam text-muted"></i></span>
                                    <input type="number" id="modalFieldCartonCount" class="form-control" min="1" value="1">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">Driver / Bearer Handover Name</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                                    <input type="text" id="modalFieldDriverName" class="form-control" placeholder="Driver / Bearer Name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">Driver Contact Phone</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                                    <input type="text" id="modalFieldDriverPhone" class="form-control" placeholder="Phone Number">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">LR / Docket / E-Way Bill Number</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-upc-scan text-muted"></i></span>
                                    <input type="text" id="modalFieldTrackingNo" class="form-control" placeholder="Tracking / Waybill No">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">Dispatch Remarks / Gate Pass Notes</label>
                                <input type="text" id="modalFieldRemarks" class="form-control form-control-sm" placeholder="Any special notes for warehouse goods receiving...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Alert -->
                <div class="alert alert-warning border-0 rounded-4 shadow-sm mt-3 mb-0 py-2 px-3 d-flex align-items-center small">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5 me-2 flex-shrink-0"></i>
                    <div>
                        <strong>Notice:</strong> Confirming this outward dispatch will <strong>deduct WhatsApp channel inventory stock immediately</strong> and generate the <strong>Delivery Gate Pass</strong> and <strong>Carton QR Shipping Label</strong>.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3 px-4 border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="bi bi-arrow-left me-1"></i> Back to Review Items
                </button>
                <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" id="btnFinalExecuteOutwardDispatch">
                    <i class="bi bi-send-check me-2"></i> Confirm &amp; Complete Outward Dispatch
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Load QRCode Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
let currentModalVariantId = null;
let currentModalStockState = 'AVAILABLE';
let currentModalRowIdx = null;
let currentModalMaxStock = 1;
let currentModalMaxReturnQty = 1;
let currentModalPieces = [];
let calculateConsignment;
const selectedPiecesByVariant = {}; // Keyed by `${variantId}_${stockState}`

function syncSelectedReturnTag() {
    if (currentModalRowIdx === null || !currentModalPieces.length) return;
    const key = `${currentModalVariantId}_${currentModalStockState}`;
    const selected = Array.from(selectedPiecesByVariant[key] || []).sort((a, b) => a - b);
    const piece = selected.length ? currentModalPieces[selected[0] - 1] : null;
    const input = document.querySelector(`input[name="items[${currentModalRowIdx}][tag_code]"]`);
    const exactTag = piece?.tag_code ? String(piece.tag_code).trim() : '';
    if (input && exactTag) {
        input.value = exactTag;
        input.disabled = false;
    }
}

function returnFlowAllowsMultiple(row) {
    const categoryType = (row?.getAttribute('data-category-type') || 'NORMAL').toUpperCase();
    return categoryType === 'SERIES'
        || categoryType === 'COMPLEMENTARY'
        || row?.getAttribute('data-complementary') === '1'
        || (row?.getAttribute('data-qr-type') || '').toUpperCase() === 'SERIES_BATCH'
        || parseInt(row?.getAttribute('data-pack-quantity') || '1', 10) > 1;
}

// Open and populate Product Detail Modal dynamically
function openProductDetailModal(variantId, stockState = 'AVAILABLE', rowIdx = null) {
    currentModalVariantId = variantId;
    currentModalStockState = stockState || 'AVAILABLE';
    currentModalRowIdx = rowIdx !== null ? rowIdx : document.querySelector(`.inv-row[data-variant-id="${variantId}"][data-stock-state="${currentModalStockState}"]`)?.getAttribute('data-idx');

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
    fetch(`<?= url('api/whatsapp/inventory/ajax-detail') ?>/${variantId}?stock_state=${encodeURIComponent(currentModalStockState)}`)
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
            const modalSourceRow = currentModalRowIdx !== null
                ? document.querySelector(`.inv-row[data-idx="${currentModalRowIdx}"]`)
                : document.querySelector(`.inv-row[data-variant-id="${variantId}"][data-stock-state="${currentModalStockState}"]`);
            currentModalMaxReturnQty = returnFlowAllowsMultiple(modalSourceRow) ? currentModalMaxStock : 1;
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
            document.getElementById('modalInwardRef').textContent = item.inward_reference || 'WhatsApp Allocation';

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
            if (currentModalStockState === 'DAMAGED') {
                stockStatusEl.textContent = 'Damaged Pool';
                stockStatusEl.className = 'badge bg-danger rounded-pill';
            } else if (qty <= 0) {
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
            const row = currentModalRowIdx !== null ? document.querySelector(`.inv-row[data-idx="${currentModalRowIdx}"]`) : document.querySelector(`.inv-row[data-variant-id="${variantId}"][data-stock-state="${currentModalStockState}"]`);
            let isChecked = false;
            let curQty = 0;
            let curClass = currentModalStockState === 'DAMAGED' ? 'DAMAGED' : 'GOOD';

            if (row) {
                const idx = row.getAttribute('data-idx');
                const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
                const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
                const classSelect = document.querySelector(`.return-classification[data-idx="${idx}"]`);

                isChecked = chk ? chk.checked : false;
                curQty = (isChecked && qtyInput) ? (parseInt(qtyInput.value) || 0) : 0;
                curClass = classSelect ? classSelect.value : (currentModalStockState === 'DAMAGED' ? 'DAMAGED' : 'GOOD');
            }

            const variantKey = `${variantId}_${currentModalStockState}`;
            // Initialize or sync selected pieces set for this variant
            if (!selectedPiecesByVariant[variantKey]) {
                selectedPiecesByVariant[variantKey] = new Set();
                if (isChecked && curQty > 0) {
                    for (let i = 1; i <= curQty; i++) {
                        selectedPiecesByVariant[variantKey].add(i);
                    }
                }
            } else {
                if (!isChecked || curQty === 0) {
                    selectedPiecesByVariant[variantKey].clear();
                } else if (selectedPiecesByVariant[variantKey].size !== curQty) {
                    const pSet = selectedPiecesByVariant[variantKey];
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

            // Sync Modal Config Box inputs with main table selection
            const modalQtyInput = document.getElementById('modalReturnQtyInput');
            const modalClassSelect = document.getElementById('modalClassificationSelect');
            const modalMinusBtn = document.getElementById('modalQtyMinusBtn');
            const modalStatusBadge = document.getElementById('modalSelectionStatusBadge');
            const modalConfigBox = document.getElementById('modalConfigBox');
            const btnDeselect = document.getElementById('btnModalDeselectFromReturn');
            const selectBtnText = document.getElementById('modalSelectBtnText');

            modalQtyInput.value = curQty;
            modalQtyInput.max = currentModalMaxReturnQty;
            if (curQty > currentModalMaxReturnQty) curQty = currentModalMaxReturnQty;
            modalQtyInput.value = curQty;
            modalClassSelect.value = currentModalStockState === 'DAMAGED' ? 'DAMAGED' : 'GOOD';
            modalClassSelect.disabled = true;
            modalMinusBtn.disabled = (curQty <= 0);

            if (isChecked && curQty > 0) {
                modalStatusBadge.classList.remove('d-none');
                document.getElementById('modalSelectedQtyBadge').textContent = curQty;
                modalConfigBox.classList.add('is-selected');
                btnDeselect.classList.remove('d-none');
                selectBtnText.textContent = 'Update Selection';
            } else {
                modalStatusBadge.classList.add('d-none');
                modalConfigBox.classList.remove('is-selected');
                btnDeselect.classList.add('d-none');
                selectBtnText.textContent = 'Select for Return';
            }

            // Render Selected Pieces in modal config box
            renderModalSelectedPiecesTray();

            // Link to full detail view
            const fullLink = document.getElementById('modalFullDetailLink');
            if (fullLink) {
                fullLink.href = `<?= url('warehouse/whatsapp-orders/inventory/detail') ?>/${variantId}`;
            }

            // Render All Individual Pieces Grid
            renderIndividualPiecesGrid(item, sellingPrice);

            // Render Recent Transactions
            renderModalTransactions(item.transactions || []);

            // Show Content
            if (spinner) spinner.classList.add('d-none');
            if (content) content.classList.remove('d-none');
        })
        .catch(err => {
            console.error(err);
            alert('Failed to load product information.');
            modal.hide();
        });
}

// Render dynamic piece tags in the Modal Config Box Tray
function renderModalSelectedPiecesTray() {
    if (!currentModalVariantId) return;
    const variantKey = `${currentModalVariantId}_${currentModalStockState}`;
    const pSet = selectedPiecesByVariant[variantKey] || new Set();
    const emptyBox = document.getElementById('modalSelectedPiecesEmpty');
    const wrapper = document.getElementById('modalSelectedPiecesWrapper');
    const tray = document.getElementById('modalSelectedPiecesTray');
    const countSpan = document.getElementById('modalSelectedPiecesCount');
    const valueSpan = document.getElementById('modalSelectedPiecesValue');

    if (!tray || !emptyBox || !wrapper) return;

    if (pSet.size === 0) {
        emptyBox.classList.remove('d-none');
        wrapper.classList.add('d-none');
        tray.innerHTML = '';
        return;
    }

    emptyBox.classList.add('d-none');
    wrapper.classList.remove('d-none');
    countSpan.textContent = pSet.size;

    const row = currentModalRowIdx !== null ? document.querySelector(`.inv-row[data-idx="${currentModalRowIdx}"]`) : document.querySelector(`.inv-row[data-variant-id="${currentModalVariantId}"][data-stock-state="${currentModalStockState}"]`);
    const unitPrice = parseFloat(row?.getAttribute('data-price')) || 0;
    valueSpan.textContent = `₹${(unitPrice * pSet.size).toFixed(2)}`;

    let html = '';
    const sorted = Array.from(pSet).sort((a,b) => a - b);
    sorted.forEach(pieceNum => {
        const pieceData = currentModalPieces[pieceNum - 1] || {};
        const tag = pieceData.tag_code || `PIECE #${pieceNum}`;

        html += `
            <span class="modal-piece-chip" id="modalChip_${pieceNum}">
                <i class="bi bi-qr-code text-success"></i>
                <span class="fw-bold font-monospace small text-dark">${escapeHtml(tag)}</span>
                <span class="chip-remove-btn" onclick="removePieceFromModalSet(${pieceNum})" title="Remove Piece #${pieceNum}">✕</span>
            </span>
        `;
    });

    tray.innerHTML = html;
}

// Remove individual piece tag directly from the tray
function removePieceFromModalSet(pieceNum) {
    if (!currentModalVariantId) return;
    const variantKey = `${currentModalVariantId}_${currentModalStockState}`;
    const pSet = selectedPiecesByVariant[variantKey];
    if (pSet && pSet.has(pieceNum)) {
        pSet.delete(pieceNum);
        const modalQtyInput = document.getElementById('modalReturnQtyInput');
        if (modalQtyInput) {
            modalQtyInput.value = pSet.size;
        }
        if (pSet.size > 0) {
            applyModalSelectionToForm();
        } else {
            removeModalSelectionFromForm();
        }
        highlightSelectedPieceCards();
        renderModalSelectedPiecesTray();
    }
}

// Sync the piece set count with a numeric quantity
function syncPieceSetWithQty(targetQty) {
    if (!currentModalVariantId) return;
    const variantKey = `${currentModalVariantId}_${currentModalStockState}`;
    if (!selectedPiecesByVariant[variantKey]) {
        selectedPiecesByVariant[variantKey] = new Set();
    }
    const pSet = selectedPiecesByVariant[variantKey];
    if (targetQty <= 0) {
        pSet.clear();
    } else if (pSet.size < targetQty) {
        for (let i = 1; i <= currentModalMaxStock && pSet.size < targetQty; i++) {
            pSet.add(i);
        }
    } else if (pSet.size > targetQty) {
        const arr = Array.from(pSet);
        while (arr.length > targetQty) {
            pSet.delete(arr.pop());
        }
    }
    highlightSelectedPieceCards();
    renderModalSelectedPiecesTray();
    syncSelectedReturnTag();
}

// Toggle individual piece card selection directly from grid
function togglePieceSelectionFromCard(pieceIndex) {
    if (!currentModalVariantId) return;
    const variantKey = `${currentModalVariantId}_${currentModalStockState}`;
    if (!selectedPiecesByVariant[variantKey]) {
        selectedPiecesByVariant[variantKey] = new Set();
    }
    const pSet = selectedPiecesByVariant[variantKey];
    const pieceNum = pieceIndex + 1;

    if (pSet.has(pieceNum)) {
        pSet.delete(pieceNum);
    } else {
        if (pSet.size >= currentModalMaxReturnQty) pSet.clear();
        pSet.add(pieceNum);
    }

    const modalQtyInput = document.getElementById('modalReturnQtyInput');
    if (modalQtyInput) {
        modalQtyInput.value = pSet.size;
    }

    if (pSet.size > 0) {
        applyModalSelectionToForm();
    } else {
        removeModalSelectionFromForm();
    }

    highlightSelectedPieceCards();
    renderModalSelectedPiecesTray();
    syncSelectedReturnTag();
}

// Update piece card visual highlight styles
function highlightSelectedPieceCards() {
    if (!currentModalVariantId) return;
    const variantKey = `${currentModalVariantId}_${currentModalStockState}`;
    const pSet = selectedPiecesByVariant[variantKey] || new Set();

    document.querySelectorAll('.piece-inspect-card').forEach(card => {
        const pNum = parseInt(card.getAttribute('data-piece-num')) || 0;
        const btn = card.querySelector('.piece-add-btn');
        const badge = card.querySelector('.piece-select-status-badge');

        if (pSet.has(pNum)) {
            card.classList.add('piece-card-selected');
            if (btn) {
                btn.className = 'btn btn-xs btn-success w-100 rounded-pill fw-bold piece-add-btn';
                btn.innerHTML = `<i class="bi bi-check2-circle me-1"></i> Selected for Return`;
            }
            if (badge) badge.classList.remove('d-none');
        } else {
            card.classList.remove('piece-card-selected');
            if (btn) {
                btn.className = 'btn btn-xs btn-outline-danger w-100 rounded-pill fw-semibold piece-add-btn';
                btn.innerHTML = `<i class="bi bi-plus-circle me-1"></i> Add Piece #${pNum} to Return`;
            }
            if (badge) badge.classList.add('d-none');
        }
    });
}

// Render All Individual Pieces Grid with QR Codes
function renderIndividualPiecesGrid(item, sellingPrice) {
    const grid = document.getElementById('modalIndividualPiecesGrid');
    if (!grid) return;

    const pieces = currentModalPieces;
    document.getElementById('modalPiecesTotalCount').textContent = pieces.length;

    if (pieces.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-4 text-muted">
                <i class="bi bi-qr-code fs-1 opacity-50 d-block mb-1"></i>
                No individual tracked pieces found for this variant in this stock pool.
            </div>
        `;
        return;
    }

    let html = '';
    const variantKey = `${currentModalVariantId}_${currentModalStockState}`;
    const pSet = selectedPiecesByVariant[variantKey] || new Set();

    pieces.forEach((piece, pIdx) => {
        const pNum = pIdx + 1;
        const tag = piece.tag_code || `${item.sku}-${String(pNum).padStart(3, '0')}`;
        const isSold = (piece.status === 'SOLD');
        const isDamaged = (piece.status === 'DAMAGED');
        const isSelected = pSet.has(pNum);

        let statusBadge = '<span class="badge bg-success rounded-pill" style="font-size: 10px;">AVAILABLE IN STOCK</span>';
        if (isSold) {
            statusBadge = '<span class="badge bg-secondary rounded-pill" style="font-size: 10px;">SOLD</span>';
        } else if (isDamaged) {
            statusBadge = '<span class="badge bg-danger rounded-pill" style="font-size: 10px;">DAMAGED POOL</span>';
        }

        html += `
            <div class="col-sm-6 col-md-4 col-xl-3">
                <div class="card h-100 border rounded-4 bg-white shadow-sm p-3 piece-inspect-card transition-all ${isSelected ? 'piece-card-selected' : ''}" 
                     data-piece-num="${pNum}" id="pieceCard_${pNum}">
                    
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                        ${statusBadge}
                        <span class="badge bg-success font-monospace piece-select-status-badge ${isSelected ? '' : 'd-none'}" style="font-size: 10px;">
                            <i class="bi bi-check2 me-1"></i>Return Selected
                        </span>
                        <span class="fw-bold font-monospace text-muted small">#${pNum}</span>
                    </div>

                    <!-- 2D QR Code Visual Rendering -->
                    <div class="text-center py-2">
                        <div id="pieceQrContainer_${pNum}" class="d-inline-block p-2 bg-light rounded-3 border"></div>
                    </div>

                    <!-- Piece Attributes -->
                    <div class="text-center mt-2">
                        <div class="fw-bold font-monospace text-dark text-truncate" style="font-size: 12px;" title="${escapeHtml(tag)}">
                            ${escapeHtml(tag)}
                        </div>
                        <small class="text-muted d-block font-monospace" style="font-size: 10px;">${escapeHtml(item.sku)}</small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small">
                        <span class="text-muted">Size: <strong>${escapeHtml(item.size_name || 'Std')}</strong></span>
                        <span class="text-success fw-bold font-monospace">₹${sellingPrice.toFixed(2)}</span>
                    </div>

                    <div class="mt-2 pt-1">
                        <button type="button" class="btn btn-xs ${isSelected ? 'btn-success' : 'btn-outline-danger'} w-100 rounded-pill fw-semibold piece-add-btn" 
                                onclick="togglePieceSelectionFromCard(${pIdx})">
                            <i class="bi ${isSelected ? 'bi-check2-circle' : 'bi-plus-circle'} me-1"></i> ${isSelected ? 'Selected for Return' : `Add Piece #${pNum} to Return`}
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    grid.innerHTML = html;

    // Generate real QR codes via QRCode.js
    setTimeout(() => {
        pieces.forEach((piece, pIdx) => {
            const pNum = pIdx + 1;
            const container = document.getElementById(`pieceQrContainer_${pNum}`);
            if (container) {
                container.innerHTML = '';
                const tag = piece.tag_code || `${item.sku}-${String(pNum).padStart(3, '0')}`;
                new QRCode(container, {
                    text: tag,
                    width: 76,
                    height: 76,
                    colorDark : "#0f172a",
                    colorLight : "#f8fafc",
                    correctLevel : QRCode.CorrectLevel.M
                });
            }
        });
    }, 50);
}

// Render Recent Movements at WhatsApp Channel
function renderModalTransactions(txs) {
    const tbody = document.getElementById('modalTransactionsTableBody');
    if (!tbody) return;

    document.getElementById('modalTxCount').textContent = `${txs.length} Transaction(s)`;

    if (txs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">No stock movement history found.</td></tr>';
        return;
    }

    let html = '';
    txs.forEach(tx => {
        const isPlus = ['INWARD', 'TRANSFER_IN', 'RETURN_REINWARD', 'RETURN'].includes(tx.transaction_type);
        html += `
            <tr>
                <td class="ps-3 text-muted font-monospace" style="font-size: 11px;">${tx.created_at || '-'}</td>
                <td><span class="badge ${isPlus ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'} font-monospace" style="font-size: 10px;">${tx.transaction_type}</span></td>
                <td class="fw-bold font-monospace ${isPlus ? 'text-success' : 'text-danger'}">${isPlus ? '+' : '-'}${parseFloat(tx.quantity)}</td>
                <td class="font-monospace text-muted">${parseFloat(tx.balance_after || 0)}</td>
                <td class="pe-3 text-truncate small text-secondary" style="max-width: 140px;" title="${escapeHtml(tx.notes || '')}">${escapeHtml(tx.notes || '-')}</td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// Apply selection inside modal to main table
function applyModalSelectionToForm() {
    if (!currentModalVariantId) return;
    const modalQtyInput = document.getElementById('modalReturnQtyInput');
    const modalClassSelect = document.getElementById('modalClassificationSelect');
    const modalStatusBadge = document.getElementById('modalSelectionStatusBadge');
    const modalConfigBox = document.getElementById('modalConfigBox');
    const btnDeselect = document.getElementById('btnModalDeselectFromReturn');
    const selectBtnText = document.getElementById('modalSelectBtnText');
    const successAlert = document.getElementById('modalReturnSuccessAlert');

    const qty = parseInt(modalQtyInput?.value) || 1;
    const classification = modalClassSelect?.value || (currentModalStockState === 'DAMAGED' ? 'DAMAGED' : 'GOOD');

    const row = currentModalRowIdx !== null ? document.querySelector(`.inv-row[data-idx="${currentModalRowIdx}"]`) : document.querySelector(`.inv-row[data-variant-id="${currentModalVariantId}"][data-stock-state="${currentModalStockState}"]`);
    if (row) {
        const idx = row.getAttribute('data-idx');
        const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
        const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
        const classSelect = document.querySelector(`.return-classification[data-idx="${idx}"]`);

        if (chk) chk.checked = true;
        if (qtyInput) qtyInput.value = qty;
        if (classSelect) classSelect.value = classification;

        if (modalStatusBadge) {
            modalStatusBadge.classList.remove('d-none');
            document.getElementById('modalSelectedQtyBadge').textContent = qty;
        }
        if (modalConfigBox) modalConfigBox.classList.add('is-selected');
        if (btnDeselect) btnDeselect.classList.remove('d-none');
        if (selectBtnText) selectBtnText.textContent = 'Update Selection';
        if (successAlert) successAlert.classList.remove('d-none');

        // Recalculate main page consignment
        if (calculateConsignment) calculateConsignment();
    }
}

// Remove selection from main table
function removeModalSelectionFromForm() {
    if (!currentModalVariantId) return;
    const modalQtyInput = document.getElementById('modalReturnQtyInput');
    const modalStatusBadge = document.getElementById('modalSelectionStatusBadge');
    const modalConfigBox = document.getElementById('modalConfigBox');
    const btnDeselect = document.getElementById('btnModalDeselectFromReturn');
    const selectBtnText = document.getElementById('modalSelectBtnText');
    const successAlert = document.getElementById('modalReturnSuccessAlert');

    if (modalQtyInput) modalQtyInput.value = 0;

    const row = currentModalRowIdx !== null ? document.querySelector(`.inv-row[data-idx="${currentModalRowIdx}"]`) : document.querySelector(`.inv-row[data-variant-id="${currentModalVariantId}"][data-stock-state="${currentModalStockState}"]`);
    if (row) {
        const idx = row.getAttribute('data-idx');
        const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
        const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);

        if (chk) chk.checked = false;
        if (qtyInput) qtyInput.value = 0;

        const variantKey = `${currentModalVariantId}_${currentModalStockState}`;
        if (selectedPiecesByVariant[variantKey]) {
            selectedPiecesByVariant[variantKey].clear();
        }

        if (modalStatusBadge) modalStatusBadge.classList.add('d-none');
        if (modalConfigBox) modalConfigBox.classList.remove('is-selected');
        if (btnDeselect) btnDeselect.classList.add('d-none');
        if (selectBtnText) selectBtnText.textContent = 'Select for Return';
        if (successAlert) successAlert.classList.add('d-none');

        highlightSelectedPieceCards();
        renderModalSelectedPiecesTray();

        if (calculateConsignment) calculateConsignment();
    }
}

// Escape HTML utility
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.item-select');
    const returnQtyInputs = document.querySelectorAll('.return-qty');
    const btnSubmit = document.getElementById('btnSubmitDispatch');

    const summarySelected = document.getElementById('summarySelectedCount');
    const summaryTotalUnits = document.getElementById('summaryTotalUnits');
    const summaryTotalValue = document.getElementById('summaryTotalValue');
    const selectedChipsTray = document.getElementById('selectedChipsTray');

    // Section 2 elements
    const selectedSectionBadge = document.getElementById('selectedSectionBadge');
    const btnClearAllSelectedSection = document.getElementById('btnClearAllSelectedSection');
    const selectedProductsEmptyState = document.getElementById('selectedProductsEmptyState');
    const selectedProductsTableWrapper = document.getElementById('selectedProductsTableWrapper');
    const selectedProductsTableBody = document.getElementById('selectedProductsTableBody');
    const selectedTableTotalQty = document.getElementById('selectedTableTotalQty');
    const selectedTableTotalValue = document.getElementById('selectedTableTotalValue');
    const returnScanInput = document.getElementById('returnScanInput');
    const returnScanStatus = document.getElementById('returnScanStatus');

    function returnRowAllowsMultiple(row) {
        return returnFlowAllowsMultiple(row);
    }

    function scanReturnProduct() {
        const rawCode = (returnScanInput?.value || '').trim();
        if (!rawCode) return;
        const code = (rawCode.includes('|') ? rawCode.split('|')[0] : rawCode).trim().toLowerCase();
        const stockPool = document.querySelector('input[name="returnScanPool"]:checked')?.value || 'AVAILABLE';
        const candidates = Array.from(document.querySelectorAll('.inv-row')).filter(row =>
            (row.getAttribute('data-stock-state') || 'AVAILABLE').toUpperCase() === stockPool
        );
        const row = candidates.find(candidate => {
            const exactValues = [candidate.getAttribute('data-qr-code'), candidate.getAttribute('data-sku'), candidate.getAttribute('data-code')]
                .filter(Boolean).map(value => value.trim().toLowerCase());
            const tagAttribute = returnRowAllowsMultiple(candidate) ? 'data-all-piece-tags' : 'data-piece-tags';
            const pieceTags = (candidate.getAttribute(tagAttribute) || '').split('|').map(value => value.trim().toLowerCase()).filter(Boolean);
            return exactValues.includes(code) || pieceTags.includes(code);
        });
        if (!row) {
            if (returnScanStatus) returnScanStatus.innerHTML = `<span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>No ${stockPool === 'DAMAGED' ? 'damaged' : 'available'} WhatsApp stock matches this QR.</span>`;
            returnScanInput.select();
            return;
        }

        const idx = row.getAttribute('data-idx');
        const tagInput = document.querySelector(`input[name="items[${idx}][tag_code]"]`);
        const checkbox = document.querySelector(`.item-select[data-idx="${idx}"]`);
        const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
        const classSelect = document.querySelector(`.return-classification[data-idx="${idx}"]`);
        const maxQty = parseInt(row.getAttribute('data-max-stock') || '0', 10);
        if (!checkbox || !qtyInput) return;
        const allowsMultiple = returnRowAllowsMultiple(row);
        if (!checkbox.checked) {
            checkbox.checked = true;
            qtyInput.value = '1';
        } else if (allowsMultiple && (parseInt(qtyInput.value, 10) || 0) < maxQty) {
            qtyInput.value = String((parseInt(qtyInput.value, 10) || 0) + 1);
        } else if (!allowsMultiple) {
            if (returnScanStatus) returnScanStatus.innerHTML = '<span class="text-warning fw-semibold">Normal products are returned one QR piece at a time. Use View to choose the exact piece.</span>';
            returnScanInput.value = '';
            returnScanInput.focus();
            return;
        }
        let scannedQrs = [];
        const qrInput = document.querySelector(`.return-scanned-qrs[data-idx="${idx}"]`);
        try {
            const existingQrs = JSON.parse(qrInput?.value || '[]');
            if (Array.isArray(existingQrs)) scannedQrs = existingQrs.filter(qr => typeof qr === 'string');
        } catch (error) {
            console.error('Unable to read scanned WhatsApp return QR tags.', error);
        }
        if (!scannedQrs.some(qr => qr.toLowerCase() === rawCode.toLowerCase())) scannedQrs.push(rawCode);
        if (qrInput) {
            qrInput.value = JSON.stringify(scannedQrs);
            qrInput.disabled = false;
        }
        if (tagInput) { tagInput.value = scannedQrs[0] || rawCode; tagInput.disabled = false; }
        if (classSelect) classSelect.value = stockPool === 'DAMAGED' ? 'DAMAGED' : 'GOOD';
        calculateConsignment();
        if (returnScanStatus) returnScanStatus.innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Added ${escapeHtml(row.getAttribute('data-name-raw') || code)} from ${stockPool === 'DAMAGED' ? 'damaged' : 'available'} WhatsApp stock.</span>`;
        returnScanInput.value = '';
        returnScanInput.focus();
    }

    document.getElementById('btnReturnScan')?.addEventListener('click', scanReturnProduct);
    returnScanInput?.addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); scanReturnProduct(); }
    });
    document.querySelectorAll('input[name="returnScanPool"]').forEach(radio => radio.addEventListener('change', function() {
        if (returnScanStatus) returnScanStatus.textContent = `Scanner is ready for ${this.value === 'DAMAGED' ? 'Bad / Damaged' : 'Good / Available'} WhatsApp stock.`;
        returnScanInput?.focus();
    }));

    // Main calculation & row activation function
    calculateConsignment = function() {
        let selectedCount = 0;
        let totalUnits = 0;
        let totalValue = 0.0;
        let chipsHtml = '';
        let selectedTableRowsHtml = '';

        checkboxes.forEach(chk => {
            const idx = chk.getAttribute('data-idx');
            const row = document.querySelector(`.inv-row[data-idx="${idx}"]`);
            const qtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
            const classSelect = document.querySelector(`.return-classification[data-idx="${idx}"]`);
            const hiddenVarId = document.querySelector(`input[name="items[${idx}][variant_id]"]`);
            const hiddenStockState = document.querySelector(`input[name="items[${idx}][stock_state]"]`);
            const hiddenTagCode = document.querySelector(`input[name="items[${idx}][tag_code]"]`);
            const hiddenScannedQrs = document.querySelector(`.return-scanned-qrs[data-idx="${idx}"]`);
            const selectedBadge = row?.querySelector('.selected-row-badge');
            const selectLabel = row?.querySelector('.select-label');
            const minusBtn = row?.querySelector('.row-qty-minus');
            const plusBtn = row?.querySelector('.row-qty-plus');
            if (chk.checked) {
                selectedCount++;
                row?.classList.add('inv-row-selected');
                if (selectedBadge) selectedBadge.classList.remove('d-none');
                if (selectLabel) {
                    selectLabel.textContent = 'Selected';
                    selectLabel.className = 'text-success fw-bold select-label';
                }

                // Enable inputs
                if (hiddenVarId) hiddenVarId.disabled = false;
                if (hiddenStockState) hiddenStockState.disabled = false;
                if (hiddenTagCode) {
                    // Preserve the exact piece tag shown in the selected QR
                    // column even when the row was selected via the product
                    // picker instead of the top scanner.
                    if (!hiddenTagCode.value.trim()) {
                        const firstPieceTag = (row?.getAttribute('data-piece-tags') || '').split('|').map(v => v.trim()).find(Boolean);
                        if (firstPieceTag) hiddenTagCode.value = firstPieceTag;
                    }
                    hiddenTagCode.disabled = false;
                }
                let scannedQrs = [];
                try {
                    const parsedQrs = JSON.parse(hiddenScannedQrs?.value || '[]');
                    if (Array.isArray(parsedQrs)) scannedQrs = parsedQrs.filter(qr => typeof qr === 'string' && qr.trim() !== '');
                } catch (error) {
                    console.error('Unable to read selected WhatsApp return QR tags.', error);
                }
                if (!scannedQrs.length && hiddenTagCode?.value.trim()) scannedQrs = [hiddenTagCode.value.trim()];
                if (!scannedQrs.length) {
                    const firstPieceTag = (row?.getAttribute('data-piece-tags') || '').split('|').map(v => v.trim()).find(Boolean);
                    if (firstPieceTag) {
                        scannedQrs = [firstPieceTag];
                        if (hiddenTagCode) hiddenTagCode.value = firstPieceTag;
                    }
                }
                if (hiddenScannedQrs) {
                    hiddenScannedQrs.value = JSON.stringify(scannedQrs);
                    hiddenScannedQrs.disabled = false;
                }
                // The quantity input starts disabled so unselected rows are not
                // submitted. Re-enable it when a row is selected; otherwise the
                // browser omits items[*][quantity] and the server receives 0.
                if (qtyInput) qtyInput.disabled = false;
                const allowsMultiple = returnRowAllowsMultiple(row);
                qtyInput.readOnly = !allowsMultiple;
                classSelect.value = (row?.getAttribute('data-stock-state') || 'AVAILABLE').toUpperCase() === 'DAMAGED' ? 'DAMAGED' : 'GOOD';
                classSelect.disabled = true;

                let q = parseInt(qtyInput.value) || 0;
                if (q <= 0) {
                    q = 1;
                    qtyInput.value = 1;
                }
                if (!allowsMultiple && q > 1) { q = 1; qtyInput.value = 1; }
                if (minusBtn) minusBtn.disabled = (!allowsMultiple || q <= 1);
                if (plusBtn) plusBtn.disabled = !allowsMultiple;

                const price = parseFloat(row?.getAttribute('data-price')) || 0;
                const maxStock = parseInt(row?.getAttribute('data-max-stock')) || 1;
                const name = row?.getAttribute('data-name-raw') || 'Item';
                const sku = row?.getAttribute('data-sku-raw') || '';
                const code = row?.getAttribute('data-code-raw') || '';
                const img = row?.getAttribute('data-image') || '';
                const size = row?.getAttribute('data-size') || '-';
                const color = row?.getAttribute('data-color') || '-';
                const lineTotal = price * q;

                totalUnits += q;
                totalValue += lineTotal;

                // Build Summary Tray Chip
                chipsHtml += `
                    <span class="badge bg-white text-dark border border-danger border-opacity-50 py-1 px-2 d-flex align-items-center gap-1 shadow-sm" style="font-size: 11px;">
                        <span class="text-danger fw-bold font-monospace">${q}x</span>
                        <span class="text-truncate" style="max-width: 100px;">${escapeHtml(name)}</span>
                        <span class="text-danger fw-bold cursor-pointer ms-1 remove-chip-btn" data-idx="${idx}" title="Remove">✕</span>
                    </span>
                `;

                // Build Row for Section 2 Table
                selectedTableRowsHtml += `
                    <tr class="selected-table-row align-middle" data-idx="${idx}">
                        <td class="ps-3 text-muted small fw-bold">${selectedCount}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                ${img ? `<img src="${escapeHtml(img)}" class="rounded border shadow-sm flex-shrink-0" style="width: 36px; height: 36px; object-fit: cover;">` : `<div class="rounded bg-light text-primary d-flex align-items-center justify-content-center flex-shrink-0 border" style="width: 36px; height: 36px;"><i class="bi bi-box-seam"></i></div>`}
                                <div>
                                    <strong class="text-dark d-block text-truncate" style="max-width: 180px;">${escapeHtml(name)}</strong>
                                    <small class="text-muted font-monospace">${escapeHtml(code)}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border font-monospace">${escapeHtml(sku)}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">${escapeHtml(size)}</span>
                            <span class="badge bg-secondary-subtle text-secondary ms-1">${escapeHtml(color)}</span>
                        </td>
                        <td>
                            <div class="input-group input-group-sm" style="width: 110px; margin: 0 auto;">
                                <button type="button" class="btn btn-outline-secondary px-2 sec-qty-minus" data-idx="${idx}" title="Decrease" ${allowsMultiple ? '' : 'disabled'}><i class="bi bi-dash"></i></button>
                                <input type="number" class="form-control form-control-sm text-center font-monospace fw-bold sec-qty-input" value="${q}" min="1" max="${maxStock}" data-idx="${idx}" ${allowsMultiple ? '' : 'readonly'}>
                                <button type="button" class="btn btn-outline-secondary px-2 sec-qty-plus" data-idx="${idx}" title="Increase" ${allowsMultiple ? '' : 'disabled'}><i class="bi bi-plus"></i></button>
                            </div>
                        </td>
                        <td><span class="badge ${classSelect.value === 'DAMAGED' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'}">${classSelect.value === 'DAMAGED' ? 'Bad / Damaged' : 'Good / Available'}</span></td>
                        <td class="text-end font-monospace text-dark">₹${price.toFixed(2)}</td>
                        <td class="text-end font-monospace fw-bold text-success">₹${lineTotal.toFixed(2)}</td>
                        <td class="text-center pe-4">
                            ${scannedQrs.length ? scannedQrs.map(tag => `
                                <div class="d-inline-flex flex-column align-items-center mx-1">
                                    <div class="selected-product-qr d-flex align-items-center justify-content-center bg-white border rounded p-1" style="width:52px;height:52px;" data-qr-payload="${escapeHtml(tag)}"></div>
                                    <small class="d-block text-primary font-monospace text-break" style="font-size:9px;max-width:90px;">${escapeHtml(tag)}</small>
                                </div>
                            `).join('') : '<span class="text-muted small">QR not captured</span>'}
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 sec-remove-btn" data-idx="${idx}" title="Remove from return consignment"><i class="bi bi-trash-fill"></i></button>
                        </td>
                    </tr>
                `;
            } else {
                row?.classList.remove('inv-row-selected');
                if (selectedBadge) selectedBadge.classList.add('d-none');
                if (selectLabel) {
                    selectLabel.textContent = 'Select';
                    selectLabel.className = 'text-muted select-label';
                }

                // Disable inputs
                if (hiddenVarId) hiddenVarId.disabled = true;
                if (hiddenStockState) hiddenStockState.disabled = true;
                if (hiddenTagCode) {
                    hiddenTagCode.disabled = true;
                    hiddenTagCode.value = '';
                }
                if (hiddenScannedQrs) {
                    hiddenScannedQrs.disabled = true;
                    hiddenScannedQrs.value = '[]';
                }
                qtyInput.disabled = true;
                qtyInput.value = 0;
                if (minusBtn) minusBtn.disabled = true;
                if (plusBtn) plusBtn.disabled = false;
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
            if (selectedProductsTableBody) {
                selectedProductsTableBody.innerHTML = selectedTableRowsHtml;
                selectedProductsTableBody.querySelectorAll('.selected-product-qr').forEach(container => {
                    const payload = (container.dataset.qrPayload || '').trim();
                    if (payload && typeof QRCode !== 'undefined') {
                        try { new QRCode(container, { text: payload, width: 44, height: 44, correctLevel: QRCode.CorrectLevel.M }); } catch (e) { container.textContent = 'QR'; }
                    }
                });
            }
            if (selectedTableTotalQty) selectedTableTotalQty.innerText = `${totalUnits} pcs`;
            if (selectedTableTotalValue) selectedTableTotalValue.innerText = `₹${totalValue.toFixed(2)}`;

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

    function attachSelectedSectionListeners() {
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
                if (!returnRowAllowsMultiple(row)) return;
                const maxStock = parseInt(row?.getAttribute('data-max-stock')) || 1;
                let val = parseInt(mainQtyInput?.value) || 0;
                if (val < maxStock) {
                    if (mainQtyInput) mainQtyInput.value = val + 1;
                    calculateConsignment();
                }
            });
        });

        document.querySelectorAll('.sec-qty-input').forEach(input => {
            input.addEventListener('input', function() {
                const idx = this.getAttribute('data-idx');
                const mainQtyInput = document.querySelector(`.return-qty[data-idx="${idx}"]`);
                const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
                const row = document.querySelector(`.inv-row[data-idx="${idx}"]`);
                const maxStock = parseInt(row?.getAttribute('data-max-stock')) || 1;
                if (!returnRowAllowsMultiple(row)) {
                    this.value = mainQtyInput?.value || '1';
                    return;
                }

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

        document.querySelectorAll('.sec-condition-select').forEach(sel => {
            sel.addEventListener('change', function() {
                const idx = this.getAttribute('data-idx');
                const mainClassSelect = document.querySelector(`.return-classification[data-idx="${idx}"]`);
                if (mainClassSelect) mainClassSelect.value = this.value;
            });
        });

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
            const allowsMultiple = returnRowAllowsMultiple(row);
            const maxStock = parseInt(row?.getAttribute('data-max-stock')) || 1;

            let val = parseInt(qtyInput.value) || 0;
            if (!chk.checked || val === 0) {
                chk.checked = true;
                qtyInput.value = 1;
            } else if (allowsMultiple && val < maxStock) {
                qtyInput.value = val + 1;
            }
            calculateConsignment();
        });
    });

    returnQtyInputs.forEach(qInput => {
        qInput.addEventListener('input', function() {
            const idx = this.getAttribute('data-idx');
            const chk = document.querySelector(`.item-select[data-idx="${idx}"]`);
            const row = document.querySelector(`.inv-row[data-idx="${idx}"]`);
            const maxStock = returnRowAllowsMultiple(row) ? (parseInt(row?.getAttribute('data-max-stock')) || 1) : 1;

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
            if (val < currentModalMaxReturnQty) {
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
            if (val > currentModalMaxReturnQty) {
                val = currentModalMaxReturnQty;
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
    // 🚚 OUTWARD PROCESS CONFIRMATION MODAL INTERCEPT
    // =========================================================================
    const btnSubmitDispatch = document.getElementById('btnSubmitDispatch');
    const outwardModalEl = document.getElementById('outwardProcessConfirmModal');
    const btnFinalExecuteOutwardDispatch = document.getElementById('btnFinalExecuteOutwardDispatch');
    const returnDispatchForm = document.getElementById('returnDispatchForm');

    if (btnSubmitDispatch && outwardModalEl) {
        btnSubmitDispatch.addEventListener('click', function(e) {
            e.preventDefault();

            // Validate that we have items selected
            let totalUnits = 0;
            let totalCount = 0;
            let totalValue = 0;
            checkboxes.forEach(chk => {
                if (chk.checked) {
                    const idx = chk.getAttribute('data-idx');
                    const qty = parseInt(document.querySelector(`.return-qty[data-idx="${idx}"]`)?.value) || 0;
                    const row = document.querySelector(`.inv-row[data-idx="${idx}"]`);
                    const price = parseFloat(row?.getAttribute('data-price')) || 0;
                    if (qty > 0) {
                        totalCount++;
                        totalUnits += qty;
                        totalValue += (price * qty);
                    }
                }
            });

            if (totalCount === 0 || totalUnits <= 0) {
                alert('Please select at least 1 product item with quantity > 0 before starting outward dispatch.');
                return;
            }

            // Sync values from sidebar form into Outward Modal
            const whSelect = document.getElementById('fieldWarehouseId');
            const whName = whSelect?.options[whSelect.selectedIndex]?.text || 'Warehouse';
            const reasonVal = document.getElementById('fieldReason')?.value || 'Return to Warehouse';

            document.getElementById('modalConfirmItemsCount').innerText = `${totalCount} item(s)`;
            document.getElementById('modalConfirmTotalUnits').innerText = `${totalUnits} pcs`;
            document.getElementById('modalConfirmTotalValue').innerText = `₹${totalValue.toFixed(2)}`;
            document.getElementById('modalConfirmWarehouseName').innerText = whName;
            document.getElementById('modalConfirmReason').innerText = reasonVal;

            document.getElementById('modalFieldCarrierName').value = document.getElementById('fieldCarrierName')?.value || 'GRM Dedicated Logistics';
            document.getElementById('modalFieldVehicleNo').value = document.getElementById('fieldVehicleNo')?.value || '';
            document.getElementById('modalFieldCartonCount').value = document.getElementById('fieldCartonCount')?.value || '1';
            document.getElementById('modalFieldDriverName').value = document.getElementById('fieldDriverName')?.value || '';
            document.getElementById('modalFieldDriverPhone').value = document.getElementById('fieldDriverPhone')?.value || '';
            document.getElementById('modalFieldTrackingNo').value = document.getElementById('fieldTrackingNo')?.value || '';
            document.getElementById('modalFieldRemarks').value = document.getElementById('fieldRemarks')?.value || '';

            // Show Outward Modal
            const outwardModal = bootstrap.Modal.getOrCreateInstance(outwardModalEl);
            outwardModal.show();
        });
    }

    if (btnFinalExecuteOutwardDispatch) {
        btnFinalExecuteOutwardDispatch.addEventListener('click', function() {
            // Sync values from Outward Modal back to Sidebar Form
            if (document.getElementById('fieldCarrierName')) document.getElementById('fieldCarrierName').value = document.getElementById('modalFieldCarrierName')?.value || '';
            if (document.getElementById('fieldVehicleNo')) document.getElementById('fieldVehicleNo').value = document.getElementById('modalFieldVehicleNo')?.value || '';
            if (document.getElementById('fieldCartonCount')) document.getElementById('fieldCartonCount').value = document.getElementById('modalFieldCartonCount')?.value || '1';
            if (document.getElementById('fieldDriverName')) document.getElementById('fieldDriverName').value = document.getElementById('modalFieldDriverName')?.value || '';
            if (document.getElementById('fieldDriverPhone')) document.getElementById('fieldDriverPhone').value = document.getElementById('modalFieldDriverPhone')?.value || '';
            if (document.getElementById('fieldTrackingNo')) document.getElementById('fieldTrackingNo').value = document.getElementById('modalFieldTrackingNo')?.value || '';
            if (document.getElementById('fieldRemarks')) document.getElementById('fieldRemarks').value = document.getElementById('modalFieldRemarks')?.value || '';

            // Disable button to prevent double-post & show loading state
            this.disabled = true;
            this.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing Outward Dispatch...`;

            // Submit Form
            if (returnDispatchForm) {
                returnDispatchForm.submit();
            }
        });
    }

    calculateConsignment();
});
</script>
