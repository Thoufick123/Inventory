<style>
.custom-modal-scroll {
    max-height: 220px;
    overflow-y: auto !important;
    overflow-x: hidden;
    padding-right: 4px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}
.custom-modal-scroll::-webkit-scrollbar {
    width: 6px;
}
.custom-modal-scroll::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}
.custom-modal-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.custom-modal-scroll::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Allocation QR Scanner Styles */
.scanner-active-glow {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
    border-color: #0d6efd !important;
}
.card-duplicate-shake {
    animation: duplicateShake 0.5s cubic-bezier(.36,.07,.19,.97) both;
    border-color: #dc3545 !important;
    background-color: #f8d7da !important;
}
@keyframes duplicateShake {
    10%, 90% { transform: translate3d(-2px, 0, 0); }
    20%, 80% { transform: translate3d(3px, 0, 0); }
    30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
    40%, 60% { transform: translate3d(4px, 0, 0); }
}
.row-scan-highlight {
    animation: rowGlow 1.5s ease-out;
}
@keyframes rowGlow {
    0% { background-color: rgba(25, 135, 84, 0.25); }
    100% { background-color: transparent; }
}
#itemsTable th,
#itemsTable td {
    vertical-align: middle !important;
}
.fw-mono {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-0 text-gray-800"><i class="bi bi-plus-circle me-2 text-primary"></i>New Shop Allocation Requisition</h2>
        <p class="text-muted small mb-0">Reserve available warehouse inventory for dispatching to retail stores or online sales channels.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('warehouse/allocation') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Allocations
        </a>
    </div>
</div>

<form method="POST" action="<?= url('warehouse/allocation/store') ?>" id="allocationForm" class="card border-0 shadow-sm rounded-3 p-4">
    <?= CSRF::field() ?>

    <div class="row g-2 mb-4">
        <div class="col-md-3">
            <label class="form-label fw-bold mb-1">Source Warehouse <span class="text-danger">*</span></label>
            <select name="warehouse_id" class="form-select" id="warehouse_select" required <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                <?php if (!function_exists('currentWarehouseId') || currentWarehouseId() === null && count($warehouses) > 1): ?>
                    <option value="">-- Select Source Warehouse --</option>
                <?php endif; ?>
                <?php foreach ($warehouses as $wh): ?>
                    <option value="<?= $wh['id'] ?>" <?= (count($warehouses) === 1 || (function_exists('currentWarehouseId') && currentWarehouseId() == $wh['id'])) ? 'selected' : '' ?>><?= htmlspecialchars($wh['name']) ?><?= !empty($wh['code']) ? ' (' . htmlspecialchars($wh['code']) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1): ?>
                <input type="hidden" name="warehouse_id" value="<?= htmlspecialchars((string)($warehouses[0]['id'] ?? (function_exists('currentWarehouseId') ? currentWarehouseId() : ''))) ?>">
            <?php endif; ?>
        </div>

        <div class="col-md-2">
            <label class="form-label fw-bold mb-1">Dest. Shop <span class="text-muted small fw-normal ms-1">(Pick 1)</span></label>
            <select name="source_shop_id" class="form-select dest-select" id="source_shop_select">
                <option value="">-- Select Shop --</option>
                <?php foreach ($shops as $sh): ?>
                    <option value="<?= $sh['id'] ?>"><?= htmlspecialchars($sh['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label fw-bold mb-1">Dest. WhatsApp <span class="text-muted small fw-normal ms-1">(Pick 1)</span></label>
            <select name="destination_type" class="form-select dest-select" id="destination_whatsapp_select">
                <option value="">-- Select WhatsApp --</option>
                <?php foreach ($salesChannels as $sc): ?>
                    <?php if ($sc['channel_type'] === 'WHATSAPP'): ?>
                        <option value="CHANNEL_<?= $sc['id'] ?>"><?= htmlspecialchars($sc['name']) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label fw-bold mb-1">Dest. Website <span class="text-muted small fw-normal ms-1">(Pick 1)</span></label>
            <select name="destination_type" class="form-select dest-select" id="destination_website_select">
                <option value="">-- Select Website --</option>
                <?php foreach ($salesChannels as $sc): ?>
                    <?php if ($sc['channel_type'] === 'WEBSITE'): ?>
                        <option value="CHANNEL_<?= $sc['id'] ?>"><?= htmlspecialchars($sc['name']) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-bold mb-1">Dest. Warehouse <span class="text-muted small fw-normal ms-1">(Pick 1)</span></label>
            <select name="destination_warehouse_id" class="form-select dest-select" id="destination_warehouse_select">
                <option value="">-- Select Warehouse --</option>
                <?php foreach ($warehouses as $wh): ?>
                    <option value="<?= $wh['id'] ?>"><?= htmlspecialchars($wh['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- LIVE QR / BARCODE SCANNER BAR -->
    <!-- ========================================================================= -->
    <div class="card border-0 shadow-sm mb-4 rounded-3" style="background: linear-gradient(135deg, rgba(13,110,253,0.05) 0%, rgba(13,110,253,0.12) 100%); border-left: 4px solid #0d6efd !important;">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white p-2 rounded-circle shadow-sm">
                        <i class="bi bi-qr-code-scan fs-5"></i>
                    </span>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Live QR Code & Barcode Gun Scanner</h6>
                        <small class="text-muted">Scan piece tag sticker or enter tag / SKU (e.g. <code>TTS092601</code>, <code>RMT092606</code>) to dynamically add product & allocate stock</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="autoFocusSwitch" checked role="switch">
                        <label class="form-check-label small fw-bold text-muted" for="autoFocusSwitch">Continuous Auto-Focus</label>
                    </div>
                    <span class="badge bg-light text-dark border px-2.5 py-1.5 small shadow-sm" id="scanCounterBadge">
                        <i class="bi bi-check2-circle text-success me-1"></i><span id="totalScannedCount" class="fw-bold">0</span> pieces scanned
                    </span>
                </div>
            </div>

            <div class="input-group input-group-lg shadow-sm">
                <span class="input-group-text bg-white border-primary border-opacity-25 text-primary">
                    <i class="bi bi-upc-scan fs-4"></i>
                </span>
                <input type="text" 
                       id="allocation_qr_input" 
                       class="form-control form-control-lg border-primary border-opacity-25 font-monospace fw-bold" 
                       placeholder="Scan QR Code or Type Piece Tag / SKU (e.g. TTS092601, RMT092606)..." 
                       autocomplete="off" 
                       autofocus>
                <button type="button" class="btn btn-primary px-4 fw-bold d-flex align-items-center gap-2" id="triggerScanBtn">
                    <i class="bi bi-lightning-charge-fill"></i> Scan / Add
                </button>
                <button type="button" class="btn btn-outline-secondary px-3" id="clearScanInputBtn" title="Clear input">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Dynamic Scan Feedback / Duplicate Warning Alerts -->
            <div id="scanStatusBanner" class="mt-2.5 d-none">
                <!-- Injected via JavaScript -->
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- REQUISITION ITEMS TABLE -->
    <!-- ========================================================================= -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Requisition Items</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 14%;">QR NO / SKU</th>
                            <th style="width: 14%;">CATEGORY</th>
                            <th style="width: 32%;">PRODUCT NAME / SELECT VARIANT <span class="text-danger">*</span></th>
                            <th style="width: 8%;" class="text-center">SIZE</th>
                            <th style="width: 10%;" class="text-center">TOTAL STOCK</th>
                            <th style="width: 12%;" class="text-center">ALLOCATE QTY <span class="text-danger">*</span></th>
                            <th style="width: 8%;" class="text-center">AVAILABLE</th>
                            <th style="width: 4%;" class="text-center">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="orderItemsContainer">
                        <!-- Dynamic items loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Requisition Summary Bar -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-light">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-4">
                <div>
                    <small class="text-muted d-block fw-semibold">Total Items</small>
                    <span class="fw-bold text-dark fs-6" id="totalItemsDisplay">0 items</span>
                </div>
                <div>
                    <small class="text-muted d-block fw-semibold">Total Reserved Pieces</small>
                    <span class="badge bg-primary fs-6 px-3 py-2" id="totalQtyDisplay">0 pcs</span>
                </div>
            </div>
            <div class="text-muted small">
                <i class="bi bi-info-circle me-1 text-primary"></i> 
                <strong>Stock Breakdown:</strong> Total Stock = Warehouse Inventory &bull; Reserved = Scanned / Selected &bull; Available = Total Stock &minus; Reserved
            </div>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label fw-bold">Requisition Notes / Instructions</label>
        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Festive season stock allocation request..."></textarea>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="<?= url('warehouse/allocation') ?>" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary px-4 fw-bold" id="submitBtn"><i class="bi bi-check-circle me-1"></i> Submit & Reserve Stock</button>
    </div>
</form>


<!-- ========================================================================= -->
<!-- MODAL 1: ADD & MANAGE WAREHOUSE -->
<!-- ========================================================================= -->
<div class="modal fade" id="warehouseModal" tabindex="-1" aria-labelledby="warehouseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #157347;">
                <h5 class="modal-title fw-bold" id="warehouseModalLabel"><i class="bi bi-building me-2"></i><span id="whModalTitleText">Add Warehouse</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="whModalBody">
                <form id="addWarehouseForm" class="mb-4">
                    <input type="hidden" id="wh_id_input" value="">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Warehouse Name <span class="text-danger">*</span></label>
                        <input type="text" id="wh_name_input" class="form-control" placeholder="e.g. Central Finished Goods Warehouse" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Warehouse Code</label>
                            <input type="text" id="wh_code_input" class="form-control" placeholder="Auto-generated e.g. WH-01">
                            <div class="form-text text-muted">Leave blank for auto-generation</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Warehouse Manager</label>
                            <input type="text" id="wh_manager_input" class="form-control" placeholder="e.g. Suresh Patel">
                            <div class="form-text text-muted">Alphabets only</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Contact Phone</label>
                        <input type="text" id="wh_phone_input" class="form-control" placeholder="10-digit number (e.g. 9876543210)">
                        <div class="form-text text-muted">10-digit mobile number</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Warehouse Address</label>
                        <textarea id="wh_address_input" class="form-control" rows="3" placeholder="Full address of the warehouse facility..."></textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="wh_is_active_input" checked role="switch">
                        <label class="form-check-label fw-bold" for="wh_is_active_input">Warehouse is Active</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <button type="button" class="btn btn-outline-secondary px-3 d-none" id="cancelWhEditBtn">Cancel Edit</button>
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn text-white px-4 fw-bold" id="saveWhBtn" style="background-color: #157347;">
                            <span id="saveWhBtnText">Save Warehouse</span>
                        </button>
                    </div>
                </form>

                <h6 class="fw-bold border-bottom pb-2 mb-3">Existing Warehouses in Database</h6>
                <div class="table-responsive custom-modal-scroll" style="max-height: 220px;">
                    <table class="table table-sm align-middle" id="whManageTable">
                        <tbody id="whTableBody">
                            <?php foreach ($warehouses as $wh): ?>
                                <tr id="wh_row_<?= $wh['id'] ?>">
                                    <td><strong><?= htmlspecialchars($wh['name']) ?></strong><?php if (!empty($wh['code'])): ?> <span class="badge bg-light text-dark border"><?= htmlspecialchars($wh['code']) ?></span><?php endif; ?></td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-wh-btn me-1" data-id="<?= $wh['id'] ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-wh-btn" data-id="<?= $wh['id'] ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: ADD & MANAGE SHOP -->
<!-- ========================================================================= -->
<div class="modal fade" id="destinationModal" tabindex="-1" aria-labelledby="destinationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #00b4d8;">
                <h5 class="modal-title fw-bold" id="destinationModalLabel"><i class="bi bi-shop me-2"></i><span id="destModalTitleText">Add Shop</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="destModalBody">
                <form id="addDestinationForm" class="mb-4">
                    <input type="hidden" id="shop_id_input" value="">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Shop / Store Name <span class="text-danger">*</span></label>
                        <input type="text" id="dest_name_input" class="form-control" placeholder="e.g. Flagship City Store" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Shop Code</label>
                            <input type="text" id="shop_code_input" class="form-control" placeholder="Auto-generated e.g. SH-01">
                            <div class="form-text text-muted">Leave blank for auto-generation</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Store Manager</label>
                            <input type="text" id="shop_manager_input" class="form-control" placeholder="e.g. Anitha Devi">
                            <div class="form-text text-muted">Alphabets only</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">GST Number</label>
                            <input type="text" id="shop_gst_input" class="form-control" placeholder="33AAAAA0000A1Z5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contact Phone</label>
                            <input type="text" id="shop_phone_input" class="form-control" placeholder="10-digit number (e.g. 9876543210)">
                            <div class="form-text text-muted">10-digit mobile number</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Store Address</label>
                        <textarea id="shop_address_input" class="form-control" rows="3" placeholder="Full physical address of the retail outlet..."></textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="shop_is_active_input" checked role="switch">
                        <label class="form-check-label fw-bold" for="shop_is_active_input">Store is Active</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <button type="button" class="btn btn-outline-secondary px-3 d-none" id="cancelDestEditBtn">Cancel Edit</button>
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn text-white px-4 fw-bold" id="saveDestBtn" style="background-color: #00b4d8;">
                            <span id="saveDestBtnText">Save Shop</span>
                        </button>
                    </div>
                </form>

                <h6 class="fw-bold border-bottom pb-2 mb-3">All Shops</h6>
                <div class="table-responsive custom-modal-scroll" style="max-height: 220px;">
                    <table class="table table-sm align-middle" id="destManageTable">
                        <tbody id="destTableBody">
                            <?php foreach ($shops as $sh):
                                $nameLower  = strtolower($sh['name']);
                                $isWebsite  = (strpos($nameLower, 'website')  !== false);
                                $isAmazon   = (strpos($nameLower, 'amazon')   !== false);
                                $isFlipkart = (strpos($nameLower, 'flipkart') !== false);
                                $isMyntra   = (strpos($nameLower, 'myntra')   !== false);
                                $isOnline   = ($isWebsite || $isAmazon || $isFlipkart || $isMyntra);

                                if ($isWebsite):
                                    $badgeClass = 'text-white border-0';
                                    $badgeLabel = 'WEB';
                                elseif ($isAmazon || $isFlipkart || $isMyntra):
                                    $badgeClass = 'bg-info text-dark border-0';
                                    $badgeLabel = 'Online';
                                else:
                                    $badgeClass = 'bg-secondary text-white border-0';
                                    $badgeLabel = 'SH';
                                endif;
                            ?>
                                <tr id="dest_row_<?= $sh['id'] ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($sh['name']) ?></strong>
                                        <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-dest-btn me-1" data-id="<?= $sh['id'] ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-dest-btn" data-id="<?= $sh['id'] ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 3: ADD & MANAGE SALES CHANNEL -->
<!-- ========================================================================= -->
<div class="modal fade" id="channelModal" tabindex="-1" aria-labelledby="channelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #6f42c1;">
                <h5 class="modal-title fw-bold" id="channelModalLabel"><i class="bi bi-diagram-3 me-2"></i><span id="channelModalTitleText">Add Sales Channel</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="channelModalBody">
                <form id="addChannelForm" class="mb-4">
                    <input type="hidden" id="channel_id_input" value="">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Channel Name <span class="text-danger">*</span></label>
                        <input type="text" id="channel_name_input" class="form-control" placeholder="e.g. Website E-Commerce / Amazon / Flipkart" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Channel Code</label>
                            <input type="text" id="channel_code_input" class="form-control" placeholder="Auto-generated e.g. SC-01">
                            <div class="form-text text-muted">Leave blank for auto-generation</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Channel Manager / Person</label>
                            <input type="text" id="channel_manager_input" class="form-control" placeholder="e.g. Rahul Sharma">
                            <div class="form-text text-muted">Alphabets only</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Contact Phone</label>
                        <input type="text" id="channel_phone_input" class="form-control" placeholder="10-digit number (e.g. 9876543210)">
                        <div class="form-text text-muted">10-digit mobile number</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Channel Description / Notes</label>
                        <textarea id="channel_desc_input" class="form-control" rows="3" placeholder="Full details or channel terms..."></textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="channel_is_active_input" checked role="switch">
                        <label class="form-check-label fw-bold" for="channel_is_active_input">Channel is Active</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <button type="button" class="btn btn-outline-secondary px-3 d-none" id="cancelChannelEditBtn">Cancel Edit</button>
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn text-white px-4 fw-bold" id="saveChannelBtn" style="background-color: #6f42c1;">
                            <span id="saveChannelBtnText">Save Sales Channel</span>
                        </button>
                    </div>
                </form>

                <h6 class="fw-bold border-bottom pb-2 mb-3">All Sales Channels</h6>
                <div class="table-responsive custom-modal-scroll" style="max-height: 220px;">
                    <table class="table table-sm align-middle" id="channelManageTable">
                        <tbody id="channelTableBody">
                            <?php foreach ($salesChannels as $sc): ?>
                                <tr id="channel_row_<?= $sc['id'] ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($sc['name']) ?></strong>
                                        <span class="badge text-white border-0" style="background-color: #6f42c1;"><?= htmlspecialchars($sc['code']) ?></span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-channel-btn me-1" data-id="<?= $sc['id'] ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-channel-btn" data-id="<?= $sc['id'] ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const variantsList = <?= json_encode($variants ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

// Global Registry of Scanned Piece Tags for Strict Duplicate Prevention
const SCANNED_ALLOCATION_TAGS = new Set();
const PENDING_ALLOCATION_TAGS = new Set();

// Web Audio API Sound Synthesizer
let audioCtx = null;
function getAudioContext() {
    if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
    return audioCtx;
}

function playSuccessSound() {
    try {
        const ctx = getAudioContext();
        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, now); // D5
        osc.frequency.setValueAtTime(880.00, now + 0.08); // A5
        gain.gain.setValueAtTime(0.15, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(now);
        osc.stop(now + 0.25);
    } catch(e) {}
}

function playDuplicateSound() {
    try {
        const ctx = getAudioContext();
        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(220, now); // A3
        osc.frequency.setValueAtTime(146.83, now + 0.1); // D3
        gain.gain.setValueAtTime(0.2, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(now);
        osc.stop(now + 0.35);
    } catch(e) {}
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getRowScannedQrs(row) {
    if (!row || !row.dataset || !row.dataset.scannedQrs) return [];
    try {
        const val = row.dataset.scannedQrs;
        if (typeof val === 'string' && val.startsWith('[')) {
            return JSON.parse(val);
        }
        return val ? [val] : [];
    } catch(e) {
        return [];
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const warehouseSelect = document.getElementById('warehouse_select');
    const scanInput = document.getElementById('allocation_qr_input');
    const triggerScanBtn = document.getElementById('triggerScanBtn');
    const clearScanBtn = document.getElementById('clearScanInputBtn');
    const autoFocusSwitch = document.getElementById('autoFocusSwitch');
    const totalScannedBadge = document.getElementById('totalScannedCount');
    const destinationWarehouseSelect = document.getElementById('destination_warehouse_select');

    function updateDestinationWarehouseOptions() {
        if (!warehouseSelect || !destinationWarehouseSelect) return;

        const sourceWarehouseId = warehouseSelect.value;
        let removedSelectedSource = false;
        Array.from(destinationWarehouseSelect.options).forEach(option => {
            if (!option.value) return;
            const isSourceWarehouse = option.value === sourceWarehouseId;
            option.hidden = isSourceWarehouse;
            option.disabled = isSourceWarehouse;
            if (isSourceWarehouse && destinationWarehouseSelect.value === option.value) {
                destinationWarehouseSelect.value = '';
                removedSelectedSource = true;
            }
        });

        if (removedSelectedSource) {
            document.querySelectorAll('.dest-select').forEach(select => {
                select.disabled = false;
            });
            showScanAlert('warning', 'The destination warehouse must be different from the source warehouse. Please choose another destination.');
        }
    }

    function updateGlobalScannedCounter() {
        if (totalScannedBadge) {
            totalScannedBadge.textContent = SCANNED_ALLOCATION_TAGS.size;
        }
    }

    function showScanAlert(type, message) {
        const banner = document.getElementById('scanStatusBanner');
        if (!banner) return;
        
        let bgClass = 'alert-success border-success text-success-emphasis';
        let icon = 'bi-check-circle-fill text-success';
        if (type === 'duplicate' || type === 'warning') {
            bgClass = 'alert-warning border-warning text-warning-emphasis';
            icon = 'bi-exclamation-triangle-fill text-warning';
        } else if (type === 'danger' || type === 'error') {
            bgClass = 'alert-danger border-danger text-danger-emphasis';
            icon = 'bi-x-circle-fill text-danger';
        }

        banner.className = `alert ${bgClass} py-2 px-3 mb-0 rounded-3 shadow-sm d-flex align-items-center justify-content-between`;
        banner.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <i class="bi ${icon} fs-5"></i>
                <div class="small">${message}</div>
            </div>
            <button type="button" class="btn-close btn-sm ms-2" onclick="this.closest('.alert').classList.add('d-none')"></button>
        `;
        banner.classList.remove('d-none');
    }

    function clearAndRefocusScanInput() {
        if (scanInput) {
            scanInput.value = '';
            if (autoFocusSwitch && autoFocusSwitch.checked) {
                scanInput.focus();
            }
        }
    }

    function supportsAdjustableAllocationQty(product) {
        const categoryType = getAllocationProductType(product);
        const qrType = String(product.qr_type || product.qrType || product.dataset?.qrType || '').trim().toUpperCase();
        return categoryType === 'SERIES'
            || categoryType === 'COMPLEMENTARY'
            || qrType === 'SERIES_BATCH';
    }

    function getAllocationProductType(product) {
        const categoryType = String(product.category_type || product.categoryType || product.dataset?.categoryType || '').trim().toUpperCase();
        const qrType = String(product.qr_type || product.qrType || product.dataset?.qrType || '').trim().toUpperCase();
        const isComplementary = product.is_complementary === true
            || product.is_complementary === 1
            || product.is_complementary === '1'
            || product.isComplementary === '1'
            || product.dataset?.isComplementary === '1';
        if (isComplementary || categoryType === 'COMPLEMENTARY') return 'COMPLEMENTARY';
        if (qrType === 'SERIES_BATCH' || categoryType === 'SERIES') return 'SERIES';
        return categoryType || 'NORMAL';
    }

    // =========================================================================
    // TABLE ROW MANAGEMENT & DYNAMIC VARIANT SELECTION
    // =========================================================================
    window.addOrderItemRow = function(selectedVariantId = null, initialTag = null, initialQty = 1) {
        const container = document.getElementById('orderItemsContainer');
        if (!container) return null;

        let opts = '<option value="">-- Select Product Variant --</option>';
        variantsList.forEach(v => {
            const isSel = (selectedVariantId && String(selectedVariantId) === String(v.id)) ? 'selected' : '';
            if (!isSel) return;

            const stock = parseFloat(v.total_warehouse_stock || 0);
            const categoryType = getAllocationProductType(v);
            const isComplementary = categoryType === 'COMPLEMENTARY';
            const isSeries = categoryType === 'SERIES' || v.qr_type === 'SERIES_BATCH';

            let catDisplay = v.sub_subcategory_name || v.subcategory_name || v.category_name || 'General';
            let label = `${v.product_name} | SKU: ${v.sku} (${v.color_name ? v.color_name + ' | ' : ''}Size: ${v.size_name || 'Std'}, Stock: ${stock} pcs)`;

            opts += `<option value="${v.id}" 
                             data-sku="${v.sku || ''}" 
                             data-qr="${v.barcode || v.sku || ''}"
                             data-category="${escapeHtml(catDisplay)}"
                             data-size="${escapeHtml(v.size_name || 'Free')}"
                             data-category-type="${escapeHtml(categoryType)}"
                             data-qr-type="${escapeHtml(v.qr_type || 'INDIVIDUAL')}"
                             data-pack-quantity="${parseInt(v.pack_quantity || 1)}"
                             data-is-series="${isSeries ? '1' : '0'}"
                             data-is-complementary="${isComplementary ? '1' : '0'}"
                             data-stock="${stock}" ${isSel}>
                        ${escapeHtml(label)}
                     </option>`;
        });

        const isPieceScan = initialTag ? true : false;
        const rowQty = parseInt(initialQty) > 0 ? parseInt(initialQty) : 1;
        const tr = document.createElement('tr');
        tr.className = 'item-row align-middle';
        tr.innerHTML = `
            <td class="qr-sku-cell">
                <span class="badge bg-light text-dark border font-monospace qr-badge">-</span>
                <input type="hidden" name="selected_qr_codes[]" class="scanned-qrs-input" value="${initialTag ? escapeHtml(initialTag) : ''}">
            </td>
            <td>
                <span class="badge bg-secondary-subtle text-secondary category-badge">-</span>
            </td>
            <td>
                <input type="hidden" name="variant_ids[]" class="variant-id-input" value="${selectedVariantId || ''}">
                <select class="form-select variant-select" aria-label="Selected product variant" disabled>
                    ${opts}
                </select>
            </td>
            <td class="text-center">
                <span class="fw-bold text-dark size-badge">-</span>
            </td>
            <td class="text-center">
                <span class="badge bg-secondary-subtle text-dark border fw-bold fs-6 total-stock-badge">0 pcs</span>
            </td>
            <td class="text-center">
                <div class="input-group input-group-sm mx-auto shadow-xs" style="max-width: 110px;">
                    <button type="button" class="btn btn-outline-secondary px-2 py-0 fw-bold qty-stepper d-none" onclick="changeRowQty(this, -1)" title="Decrease quantity">-</button>
                    <input type="number" 
                           name="requested_qty[]" 
                           class="form-control text-center fw-bold qty-input fs-6 px-1" 
                           value="${rowQty}" 
                           min="1" 
                           readonly
                           aria-readonly="true"
                           required 
                           oninput="updateRowStockCalculations(this.closest('tr'))" 
                           onchange="updateRowStockCalculations(this.closest('tr'))">
                    <button type="button" class="btn btn-outline-secondary px-2 py-0 fw-bold qty-stepper d-none" onclick="changeRowQty(this, 1)" title="Increase quantity">+</button>
                </div>
            </td>
            <td class="text-center">
                <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold fs-6 available-stock-badge">0 pcs</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row" onclick="removeOrderItemRow(this)" title="Remove item">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        container.appendChild(tr);

        if (initialTag) {
            tr.dataset.scannedQrs = JSON.stringify([initialTag]);
        }

        if (selectedVariantId) {
            const selElem = tr.querySelector('.variant-select');
            onVariantChange(selElem);
        } else {
            updateRowStockCalculations(tr);
        }

        return tr;
    };

    window.changeRowQty = function(btn, delta) {
        const row = btn.closest('tr');
        if (!row) return;
        const input = row.querySelector('.qty-input');
        if (!input) return;
        const sel = row.querySelector('.variant-select');
        const opt = sel ? sel.options[sel.selectedIndex] : null;
        if (!opt || !supportsAdjustableAllocationQty(opt.dataset)) return;
        let currentVal = parseInt(input.value) || 0;
        let newVal = Math.max(1, currentVal + delta);

        if (opt.value) {
            const maxAllowed = parseFloat(input.max || opt.dataset.stock || '0');
            if (maxAllowed > 0 && newVal > maxAllowed) {
                newVal = maxAllowed;
            }
        }
        input.value = newVal;
        updateRowStockCalculations(row);
    };

    window.removeOrderItemRow = function(btn) {
        const container = document.getElementById('orderItemsContainer');
        const rows = container.querySelectorAll('tr.item-row');
        const row = btn.closest('tr');

        if (row) {
            // Remove scanned tags from global tracking registry
            let scannedQrs = getRowScannedQrs(row);
            scannedQrs.forEach(q => SCANNED_ALLOCATION_TAGS.delete(q));

            if (rows.length <= 1) {
                // If only 1 row, just clear and reset it
                const sel = row.querySelector('.variant-select');
                if (sel) sel.value = '';
                const qtyInput = row.querySelector('.qty-input');
                if (qtyInput) {
                    qtyInput.value = 1;
                    qtyInput.style.backgroundColor = '';
                }
                row.dataset.scannedQrs = JSON.stringify([]);
                onVariantChange(sel);
                updateRowQrDisplay(row);
            } else {
                row.remove();
            }

            calculateGrandTotal();
            updateGlobalScannedCounter();
            updateAllRowsStockCalculations();
        }
    };

    window.onVariantChange = function(select) {
        const row = select.closest('tr');
        if (!row) return;

        const opt = select.options[select.selectedIndex];
        const catBadge = row.querySelector('.category-badge');
        const sizeBadge = row.querySelector('.size-badge');
        const qtyInput = row.querySelector('.qty-input');
        const variantIdInput = row.querySelector('.variant-id-input');
        const qtySteppers = row.querySelectorAll('.qty-stepper');

        if (opt && opt.value) {
            const cat = opt.getAttribute('data-category') || 'General';
            const size = opt.getAttribute('data-size') || 'Free';
            const categoryType = (opt.dataset.categoryType || '').trim().toUpperCase();
            const canAdjustQty = supportsAdjustableAllocationQty(opt.dataset);
            const availableStock = parseFloat(opt.dataset.stock || '0');

            if (catBadge) catBadge.textContent = cat;
            if (sizeBadge) sizeBadge.textContent = size;
            if (!canAdjustQty) {
                qtyInput.value = 1;
            } else if (!qtyInput.value || parseInt(qtyInput.value) <= 0) {
                qtyInput.value = 1;
            }
            if (variantIdInput) variantIdInput.value = opt.value;
            if (qtyInput) {
                qtyInput.readOnly = !canAdjustQty;
                qtyInput.disabled = false;
                qtyInput.max = String(availableStock);
                qtyInput.setAttribute('aria-readonly', canAdjustQty ? 'false' : 'true');
            }
            qtySteppers.forEach(button => button.classList.toggle('d-none', !canAdjustQty));
        } else {
            if (catBadge) catBadge.textContent = '-';
            if (sizeBadge) sizeBadge.textContent = '-';
            if (qtyInput) {
                qtyInput.value = 1;
                qtyInput.readOnly = true;
                qtyInput.setAttribute('aria-readonly', 'true');
            }
            if (variantIdInput) variantIdInput.value = '';
            qtySteppers.forEach(button => button.classList.add('d-none'));
        }

        updateRowQrDisplay(row);
        updateAllRowsStockCalculations();
    };

    window.updateAllRowsStockCalculations = function() {
        const rows = document.querySelectorAll('#orderItemsContainer tr.item-row');
        
        // 1. Calculate cumulative reserved quantity per variant_id
        const reservedMap = {};
        rows.forEach(r => {
            const sel = r.querySelector('.variant-select');
            const vId = sel ? sel.value : null;
            const qtyInput = r.querySelector('.qty-input');
            const qty = parseInt(qtyInput ? qtyInput.value : 0) || 0;
            if (vId && qty > 0) {
                reservedMap[vId] = (reservedMap[vId] || 0) + qty;
            }
        });

        // 2. Calculate each row's available stock in requisition order.
        const allocatedBeforeRow = {};
        rows.forEach(r => {
            const sel = r.querySelector('.variant-select');
            const opt = sel ? sel.options[sel.selectedIndex] : null;
            const vId = sel ? sel.value : null;
            const qtyInput = r.querySelector('.qty-input');
            const totalStockBadge = r.querySelector('.total-stock-badge');
            const availBadge = r.querySelector('.available-stock-badge');

            let totalStock = 0;
            if (opt && opt.value) {
                totalStock = Math.max(0, parseFloat(opt.dataset.stock || '0'));
            }

            const qty = Math.max(0, parseInt(qtyInput ? qtyInput.value : '0') || 0);
            const allocatedBefore = allocatedBeforeRow[vId] || 0;
            const totalReservedForVariant = reservedMap[vId] || 0;
            const maxAfterOtherRows = Math.max(0, totalStock - (totalReservedForVariant - qty));

            if (qtyInput && opt && opt.value) {
                qtyInput.max = String(maxAfterOtherRows);
                qtyInput.setCustomValidity(maxAfterOtherRows < 1
                    ? 'No available stock remains for this variant.'
                    : '');
            } else if (qtyInput) {
                qtyInput.setCustomValidity('');
            }

            const currentQty = Math.max(0, parseInt(qtyInput ? qtyInput.value : '0') || 0);
            const remainingAvailable = Math.max(0, totalStock - allocatedBefore - currentQty);
            if (vId) allocatedBeforeRow[vId] = allocatedBefore + currentQty;

            if (totalStockBadge) {
                totalStockBadge.textContent = `${totalStock} pcs`;
            }

            if (availBadge) {
                if (!opt || !opt.value) {
                    availBadge.className = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-bold fs-6 available-stock-badge';
                    availBadge.textContent = '0 pcs';
                } else if (remainingAvailable > 0) {
                    availBadge.className = 'badge bg-success-subtle text-success border border-success-subtle fw-bold fs-6 available-stock-badge';
                    availBadge.innerHTML = `<i class="bi bi-check2 me-1"></i>${remainingAvailable} pcs`;
                } else if (remainingAvailable === 0) {
                    availBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle fw-bold fs-6 available-stock-badge';
                    availBadge.innerHTML = `0 pcs`;
                } else {
                    availBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle fw-bold fs-6 available-stock-badge';
                    availBadge.textContent = '0 pcs';
                }
            }
        });

        calculateGrandTotal();
    };

    window.updateRowStockCalculations = function(row) {
        const qtyInput = row ? row.querySelector('.qty-input') : null;
        const select = row ? row.querySelector('.variant-select') : null;
        const variantId = select ? select.value : '';
        const option = select && select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
        if (qtyInput && variantId && option) {
            const otherReserved = Array.from(document.querySelectorAll('#orderItemsContainer tr.item-row'))
                .filter(otherRow => otherRow !== row)
                .reduce((sum, otherRow) => {
                    const otherSelect = otherRow.querySelector('.variant-select');
                    const otherQty = otherRow.querySelector('.qty-input');
                    return otherSelect && otherSelect.value === variantId
                        ? sum + (parseInt(otherQty ? otherQty.value : '0') || 0)
                        : sum;
                }, 0);
            const maxAllowed = Math.max(0, parseFloat(option.dataset.stock || '0') - otherReserved);
            qtyInput.max = String(maxAllowed);
            if (maxAllowed >= 1 && (parseInt(qtyInput.value) || 0) > maxAllowed) {
                qtyInput.value = String(maxAllowed);
            }
        }
        updateAllRowsStockCalculations();
    };

    window.updateRowQrDisplay = function(row) {
        const sel = row.querySelector('.variant-select');
        const opt = sel ? sel.options[sel.selectedIndex] : null;
        const sku = opt ? (opt.getAttribute('data-sku') || '') : '';
        const defaultQr = opt ? (opt.getAttribute('data-qr') || sku || '-') : '-';
        const categoryType = opt ? getAllocationProductType(opt.dataset) : '';
        const productTypeLabel = categoryType === 'SERIES'
            ? 'SERIES PRODUCT'
            : (categoryType === 'COMPLEMENTARY' ? 'COMPLEMENTARY PRODUCT' : 'NORMAL PRODUCT');
        const productTypeClass = categoryType === 'SERIES'
            ? 'bg-primary-subtle text-primary'
            : (categoryType === 'COMPLEMENTARY' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-secondary-subtle text-secondary');
        const cell = row.querySelector('.qr-sku-cell');
        if (!cell) return;

        let scannedQrs = getRowScannedQrs(row);
        const scannedVal = scannedQrs.join(',');

        let html = '';
        if (scannedQrs && scannedQrs.length > 0) {
            html += '<div class="d-flex flex-wrap gap-1 align-items-center mb-1">';
            scannedQrs.forEach(q => {
                html += `<span class="badge bg-success text-white font-monospace shadow-xs" style="font-size:0.75rem; padding: 4px 6px;"><i class="bi bi-qr-code-scan me-1"></i>${escapeHtml(q)}</span>`;
            });
            html += '</div>';
            if (sku) {
                html += `<small class="text-muted d-block font-monospace" style="font-size:0.72rem;">SKU: ${escapeHtml(sku)}</small>`;
            }
            html += `<span class="badge ${productTypeClass} mt-1" style="font-size:0.62rem;">${productTypeLabel}</span>`;
        } else {
            html += `<span class="badge bg-light text-dark border font-monospace qr-badge" style="font-size:0.80rem;">${escapeHtml(defaultQr)}</span>`;
            if (opt && opt.value) {
                html += `<span class="badge ${productTypeClass} ms-1" style="font-size:0.62rem;">${productTypeLabel}</span>`;
            }
        }

        html += `<input type="hidden" name="selected_qr_codes[]" class="scanned-qrs-input" value="${escapeHtml(scannedVal)}">`;
        cell.innerHTML = html;
    };

    window.calculateGrandTotal = function() {
        const rows = document.querySelectorAll('#orderItemsContainer tr.item-row');
        let totalItems = 0;
        let totalPieces = 0;
        let hasValidItems = false;

        rows.forEach(r => {
            const sel = r.querySelector('.variant-select');
            const qtyInput = r.querySelector('.qty-input');

            const vId = sel ? sel.value : null;
            const qty = parseInt(qtyInput ? qtyInput.value : 0) || 0;

            if (vId && qty > 0) {
                totalItems++;
                totalPieces += qty;
                hasValidItems = true;
            }
        });

        const itemsDisplay = document.getElementById('totalItemsDisplay');
        const qtyDisplay = document.getElementById('totalQtyDisplay');
        const submitBtn = document.getElementById('submitBtn');

        if (itemsDisplay) itemsDisplay.textContent = `${totalItems} items`;
        if (qtyDisplay) qtyDisplay.textContent = `${totalPieces} pcs`;
        if (submitBtn) submitBtn.disabled = !hasValidItems;
    };

    // =========================================================================
    // LIVE QR & BARCODE GUN SCANNER HANDLER
    // =========================================================================
    function handleQrScanInput(rawCode) {
        rawCode = (rawCode || '').trim();
        if (!rawCode) return;

        const whId = (warehouseSelect && warehouseSelect.value ? warehouseSelect.value : '') 
                  || (document.querySelector('input[name="warehouse_id"]') ? document.querySelector('input[name="warehouse_id"]').value : '') 
                  || '1';

        if (!whId) {
            playDuplicateSound();
            showScanAlert('warning', '⚠️ <strong>Select Source Warehouse First:</strong> Please choose a Source Warehouse at top before scanning QR codes.');
            if (warehouseSelect) {
                warehouseSelect.focus();
                warehouseSelect.classList.add('scanner-active-glow');
                setTimeout(() => warehouseSelect.classList.remove('scanner-active-glow'), 2500);
            }
            return;
        }

        // Extract potential tag code for duplicate check
        let tagCode = rawCode;
        if (rawCode.startsWith('{') && rawCode.endsWith('}')) {
            try {
                const j = JSON.parse(rawCode);
                tagCode = j.tag || j.tag_code || j.code || j.sku || rawCode;
            } catch(e) {}
        } else if (rawCode.includes('|')) {
            const parts = rawCode.split('|').map(p => p.trim());
            if (parts[0].toUpperCase() === 'SERIES') {
                tagCode = parts[3] || parts[2] || rawCode;
            } else {
                tagCode = parts[0];
            }
        }
        tagCode = tagCode.toUpperCase();

        if (PENDING_ALLOCATION_TAGS.has(tagCode)) {
            playDuplicateSound();
            showScanAlert('duplicate', `⚠️ <strong>Scan in Progress:</strong> Piece Tag <code>${tagCode}</code> is already being processed.`);
            clearAndRefocusScanInput();
            return;
        }

        // 1. DUPLICATE CHECK
        if (SCANNED_ALLOCATION_TAGS.has(tagCode)) {
            // Check if this tag belongs to an existing row that is a series product
            let existingPackRow = null;
            const rows = document.querySelectorAll('#orderItemsContainer tr.item-row');
            rows.forEach(r => {
                let scannedQrs = getRowScannedQrs(r);
                if (scannedQrs.map(s => s.toUpperCase()).includes(tagCode)) {
                    const sel = r.querySelector('.variant-select');
                    const vId = sel ? sel.value : null;
                    const vObj = variantsList.find(v => String(v.id) === String(vId));
                    if (vObj && supportsAdjustableAllocationQty(vObj)) {
                        existingPackRow = r;
                    }
                }
            });

            if (existingPackRow) {
                const qtyInput = existingPackRow.querySelector('.qty-input');
                const sel = existingPackRow.querySelector('.variant-select');
                const opt = sel ? sel.options[sel.selectedIndex] : null;
                const totalStock = opt ? parseFloat(opt.getAttribute('data-stock') || 0) : 0;
                let currentVal = parseInt(qtyInput ? qtyInput.value : 1) || 1;

                if (currentVal + 1 <= totalStock) {
                    qtyInput.value = currentVal + 1;
                    updateRowStockCalculations(existingPackRow);
                    playSuccessSound();
                    showScanAlert('success', `📦 Pack QR <code>${tagCode}</code> re-scanned: Allocated quantity increased to <strong>${currentVal + 1} pcs</strong> (Remaining in warehouse: ${totalStock - (currentVal + 1)} pcs).`);
                    existingPackRow.classList.add('row-scan-highlight');
                    setTimeout(() => existingPackRow.classList.remove('row-scan-highlight'), 1500);
                    existingPackRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else {
                    playDuplicateSound();
                    showScanAlert('warning', `⚠️ <strong>Warehouse Stock Limit:</strong> Series Tag <code>${tagCode}</code> has only <strong>${totalStock} pcs</strong> available in this warehouse. All available pieces are already allocated!`);
                }
                clearAndRefocusScanInput();
                return;
            }

            playDuplicateSound();
            showScanAlert('duplicate', `⚠️ <strong>Duplicate Scan Blocked:</strong> Piece Tag <code>${tagCode}</code> has already been added in this requisition!`);
            clearAndRefocusScanInput();
            return;
        }

        // Helper to apply matched variant to table — Each scanned piece QR creates its OWN distinct row
        function applyScannedVariant(data, resolvedTag) {
            resolvedTag = (resolvedTag || tagCode).toUpperCase();

            // Determine if this is a series or complementary product pack
            const categoryType = String(data.category_type || '').trim().toUpperCase();
            const isSeriesOrComp = supportsAdjustableAllocationQty(data);

            if (SCANNED_ALLOCATION_TAGS.has(resolvedTag) && !isSeriesOrComp) {
                playDuplicateSound();
                showScanAlert('duplicate', `⚠️ <strong>Duplicate Scan Blocked:</strong> Piece Tag <code>${resolvedTag}</code> has already been added in this requisition!`);
                clearAndRefocusScanInput();
                return;
            }

            const vId = data.variant_id || data.id;
            const rows = document.querySelectorAll('#orderItemsContainer tr.item-row');
            let targetRow = null;

            const packQty = parseInt(data.scan_qty || data.pack_quantity || 1);
            // For series products, allocate 1 piece initially so remainder is retained in warehouse
            const targetQty = isSeriesOrComp ? (data.is_series ? 1 : packQty) : 1;

            // Refresh the cached stock and category metadata from the scan response.
            let cachedVariant = variantsList.find(v => String(v.id) === String(vId));
            const scannedVariantData = {
                    id: vId,
                    sku: data.sku || '',
                    barcode: data.barcode || data.sku || '',
                    product_name: data.product_name || data.sku || '',
                    size_name: data.size_name || 'Free',
                    color_name: data.color_name || '',
                    category_name: data.category_name || '',
                    subcategory_name: data.subcategory_name || '',
                    sub_subcategory_name: data.sub_subcategory_name || '',
                    category_type: getAllocationProductType(data),
                    is_complementary: getAllocationProductType(data) === 'COMPLEMENTARY' ? 1 : 0,
                    total_warehouse_stock: parseFloat(data.available_stock ?? data.total_stock ?? 0),
                    is_series: categoryType === 'SERIES' || data.qr_type === 'SERIES_BATCH' ? 1 : 0,
                    qr_type: data.qr_type || 'INDIVIDUAL',
                    pack_quantity: packQty
            };
            if (cachedVariant) {
                Object.assign(cachedVariant, scannedVariantData);
            } else {
                variantsList.push(scannedVariantData);
                cachedVariant = scannedVariantData;
            }

            document.querySelectorAll('#orderItemsContainer .variant-select').forEach(select => {
                Array.from(select.options).forEach(option => {
                    if (String(option.value) !== String(vId)) return;
                    option.dataset.categoryType = cachedVariant.category_type;
                    option.dataset.qrType = cachedVariant.qr_type;
                    option.dataset.packQuantity = String(cachedVariant.pack_quantity);
                    option.dataset.isSeries = cachedVariant.is_series ? '1' : '0';
                    option.dataset.isComplementary = cachedVariant.is_complementary ? '1' : '0';
                    option.dataset.stock = String(cachedVariant.total_warehouse_stock);
                    option.textContent = `${cachedVariant.product_name} | SKU: ${cachedVariant.sku} (${cachedVariant.color_name ? cachedVariant.color_name + ' | ' : ''}Size: ${cachedVariant.size_name || 'Std'}, Stock: ${cachedVariant.total_warehouse_stock} pcs)`;
                });
            });

            // If first row is completely empty, use it; otherwise create a brand new distinct row
            let emptyRow = null;
            rows.forEach(r => {
                const sel = r.querySelector('.variant-select');
                if (!emptyRow && sel && (!sel.value || sel.value === '')) {
                    emptyRow = r;
                }
            });

            if (emptyRow) {
                targetRow = emptyRow;
                targetRow.dataset.scannedQrs = JSON.stringify([resolvedTag]);
                const qtyInput = targetRow.querySelector('.qty-input');
                if (qtyInput) {
                    qtyInput.value = targetQty;
                }
                const sel = targetRow.querySelector('.variant-select');
                if (sel) {
                    if (!sel.querySelector(`option[value="${vId}"]`)) {
                        const opt = document.createElement('option');
                        opt.value = vId;
                        opt.textContent = `${data.product_name} | SKU: ${data.sku} (${data.color_name ? data.color_name + ' | ' : ''}Size: ${data.size_name || 'Std'}, Stock: ${data.available_stock || data.total_stock || 0} pcs)`;
                        opt.setAttribute('data-sku', data.sku || '');
                        opt.setAttribute('data-qr', data.barcode || data.sku || '');
                        opt.setAttribute('data-category', data.hierarchy || data.category_name || 'General');
                        opt.setAttribute('data-size', data.size_name || 'Free');
                        opt.setAttribute('data-category-type', cachedVariant.category_type);
                        opt.setAttribute('data-qr-type', cachedVariant.qr_type);
                        opt.setAttribute('data-pack-quantity', parseInt(data.pack_quantity || 1));
                        opt.setAttribute('data-is-series', cachedVariant.is_series ? '1' : '0');
                        opt.setAttribute('data-is-complementary', cachedVariant.is_complementary ? '1' : '0');
                        opt.setAttribute('data-stock', cachedVariant.total_warehouse_stock);
                        sel.appendChild(opt);
                    }
                    sel.value = vId;
                    const variantIdInput = targetRow.querySelector('.variant-id-input');
                    if (variantIdInput) variantIdInput.value = vId;
                    onVariantChange(sel);
                }
            } else {
                targetRow = addOrderItemRow(vId, resolvedTag, targetQty);
            }

            SCANNED_ALLOCATION_TAGS.add(resolvedTag);

            if (targetRow) {
                targetRow.dataset.scannedQrs = JSON.stringify([resolvedTag]);
                const qtyInput = targetRow.querySelector('.qty-input');
                if (qtyInput) {
                    if (parseInt(qtyInput.value) !== targetQty) {
                        qtyInput.value = targetQty;
                    }
                }
                updateRowQrDisplay(targetRow);
                updateAllRowsStockCalculations();

                targetRow.classList.add('row-scan-highlight');
                setTimeout(() => targetRow.classList.remove('row-scan-highlight'), 1500);
                targetRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            playSuccessSound();
            const packBadge = isSeriesOrComp ? ` <span class="badge bg-primary ms-1">📦 Pack (${data.available_stock || data.total_stock || packQty} Pcs in Warehouse)</span>` : '';
            const quantityMessage = isSeriesOrComp
                ? 'You can adjust/split the allocation quantity as needed.'
                : 'Individual QR quantity is fixed at 1.';
            showScanAlert('success', `✅ Scanned <strong class="font-monospace">${resolvedTag}</strong> &rarr; ${data.product_name || data.sku} (${data.size_name || 'Std'})${packBadge} added! ${quantityMessage}`);
            updateGlobalScannedCounter();
            clearAndRefocusScanInput();
        }

        // 2. Try backend API matcher with fallback to client-side
        PENDING_ALLOCATION_TAGS.add(tagCode);
        fetch(`<?= url("api/warehouse/scan-allocation-qr") ?>?warehouse_id=${whId}&code=${encodeURIComponent(rawCode)}`)
            .then(res => {
                if (!res.ok) throw new Error('API lookup failed');
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    applyScannedVariant(data, (data.tag_code || tagCode).toUpperCase());
                } else if (data.is_already_allocated) {
                    playDuplicateSound();
                    showScanAlert('duplicate', `🚫 <strong>Piece Already Allocated:</strong> Tag <code class="fw-bold">${data.tag_code || tagCode}</code> has ALREADY been allocated in Pass <strong>#${data.allocation_number}</strong> (${data.allocation_status}) at <em>${data.allocated_warehouse || 'Warehouse'}</em> for ${data.allocated_product || 'item'}. Cannot allocate the same piece QR twice!`);
                    clearAndRefocusScanInput();
                } else if (data.is_no_stock) {
                    playDuplicateSound();
                    showScanAlert('danger', `❌ <strong>Scan Failed:</strong> ${data.message}`);
                    clearAndRefocusScanInput();
                } else if (data.is_ambiguous) {
                    playDuplicateSound();
                    showScanAlert('danger', `❌ <strong>Scan Failed:</strong> ${data.message}`);
                    clearAndRefocusScanInput();
                } else {
                    throw new Error(data.message || 'Variant not found');
                }
            })
            .catch(err => {
                // Client-side fallback matching only for generic errors (never for already allocated items)
                const clean = tagCode.replace(/[-_]\d+$/, '');
                let local = variantsList.find(v => (v.sku || '').toUpperCase() === tagCode || (v.barcode || '').toUpperCase() === tagCode);
                if (!local) local = variantsList.find(v => (v.sku || '').toUpperCase() === clean || (v.barcode || '').toUpperCase() === clean);
                if (!local) local = variantsList.find(v => tagCode.startsWith((v.sku || '').toUpperCase()) || (v.sku && clean.startsWith((v.sku || '').toUpperCase())));

                if (local) {
                    if (parseFloat(local.total_warehouse_stock || 0) <= 0) {
                        playDuplicateSound();
                        showScanAlert('danger', `❌ <strong>Scan Failed:</strong> Product <strong>${local.product_name || local.sku}</strong> is out of stock in this warehouse.`);
                        clearAndRefocusScanInput();
                        return;
                    }
                    applyScannedVariant(local, tagCode);
                } else {
                    playDuplicateSound();
                    showScanAlert('danger', `❌ <strong>Scan Failed:</strong> No product variant found matching <code>${tagCode}</code>.`);
                    clearAndRefocusScanInput();
                }
            })
            .finally(() => {
                PENDING_ALLOCATION_TAGS.delete(tagCode);
            });
    }

    // Expose globally for inline event handlers and scanner calls
    window.handleQrScanInput = handleQrScanInput;

    if (scanInput) {
        scanInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleQrScanInput(this.value);
            }
        });
    }

    if (triggerScanBtn) {
        triggerScanBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (scanInput) handleQrScanInput(scanInput.value);
        });
    }

    if (clearScanBtn) {
        clearScanBtn.addEventListener('click', function() {
            if (scanInput) {
                scanInput.value = '';
                scanInput.focus();
            }
            const banner = document.getElementById('scanStatusBanner');
            if (banner) banner.classList.add('d-none');
        });
    }

    function refreshVariantStockForWarehouse(whId) {
        if (!whId) return;
        fetch(`<?= url("api/warehouse/variant-stock-hierarchy") ?>?warehouse_id=${whId}`)
            .then(res => {
                if (!res.ok) throw new Error('Warehouse stock lookup failed.');
                return res.json();
            })
            .then(data => {
                if (!data.success || !Array.isArray(data.variants)) {
                    throw new Error(data.message || 'Warehouse stock lookup returned invalid data.');
                }

                const stockMap = new Map(data.variants.map(v => [String(v.id), v]));
                variantsList.forEach(v => {
                    const stockVariant = stockMap.get(String(v.id));
                    if (!stockVariant) return;
                    v.total_warehouse_stock = parseFloat(stockVariant.available_stock ?? 0);
                    v.category_type = getAllocationProductType(stockVariant);
                    v.qr_type = stockVariant.qr_type || v.qr_type || 'INDIVIDUAL';
                    v.pack_quantity = parseInt(stockVariant.pack_quantity || v.pack_quantity || 1);
                    v.is_complementary = parseInt(stockVariant.is_complementary ?? v.is_complementary ?? 0);
                    v.is_series = v.category_type === 'SERIES' || v.qr_type === 'SERIES_BATCH' ? 1 : 0;
                });

                document.querySelectorAll('#orderItemsContainer tr.item-row').forEach(row => {
                    const select = row.querySelector('.variant-select');
                    if (!select) return;

                    Array.from(select.options).forEach(option => {
                        const variant = variantsList.find(v => String(v.id) === String(option.value));
                        if (!variant) return;

                        const categoryType = getAllocationProductType(variant);
                        const stock = parseFloat(variant.total_warehouse_stock || 0);
                        option.dataset.categoryType = categoryType;
                        option.dataset.qrType = variant.qr_type || 'INDIVIDUAL';
                        option.dataset.packQuantity = String(variant.pack_quantity || 1);
                        option.dataset.isSeries = categoryType === 'SERIES' || variant.qr_type === 'SERIES_BATCH' ? '1' : '0';
                        option.dataset.isComplementary = categoryType === 'COMPLEMENTARY' ? '1' : '0';
                        option.dataset.stock = String(stock);
                        option.textContent = `${variant.product_name} | SKU: ${variant.sku} (${variant.color_name ? variant.color_name + ' | ' : ''}Size: ${variant.size_name || 'Std'}, Stock: ${stock} pcs)`;
                    });

                    onVariantChange(select);
                });
            })
            .catch(err => {
                console.error('Unable to refresh allocation stock for selected warehouse.', err);
                showScanAlert('danger', 'Unable to refresh product stock for the selected warehouse. Please reload the page and try again.');
            });
    }

    if (warehouseSelect) {
        warehouseSelect.addEventListener('change', function() {
            updateDestinationWarehouseOptions();
            refreshVariantStockForWarehouse(this.value);
        });
        if (warehouseSelect.value) {
            refreshVariantStockForWarehouse(warehouseSelect.value);
        }
    }

    // =========================================================================
    // WAREHOUSE QUICK-ADD, EDIT & DELETE AJAX
    // =========================================================================
    function resetWhForm() {
        document.getElementById('wh_id_input').value = '';
        document.getElementById('wh_name_input').value = '';
        document.getElementById('wh_code_input').value = '';
        document.getElementById('wh_manager_input').value = '';
        document.getElementById('wh_phone_input').value = '';
        document.getElementById('wh_address_input').value = '';
        document.getElementById('wh_is_active_input').checked = true;

        document.getElementById('whModalTitleText').textContent = 'Add Warehouse';
        document.getElementById('saveWhBtnText').textContent = 'Save Warehouse';
        const cancelBtn = document.getElementById('cancelWhEditBtn');
        if (cancelBtn) cancelBtn.classList.add('d-none');
    }

    const cancelWhBtn = document.getElementById('cancelWhEditBtn');
    if (cancelWhBtn) {
        cancelWhBtn.addEventListener('click', resetWhForm);
    }

    const addWhForm = document.getElementById('addWarehouseForm');
    if (addWhForm) {
        addWhForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const whId = document.getElementById('wh_id_input').value;
            const whName = document.getElementById('wh_name_input').value.trim();
            const whCode = document.getElementById('wh_code_input').value.trim();
            const whManager = document.getElementById('wh_manager_input').value.trim();
            const whPhone = document.getElementById('wh_phone_input').value.trim();
            const whAddress = document.getElementById('wh_address_input').value.trim();
            const whIsActive = document.getElementById('wh_is_active_input').checked ? 1 : 0;

            const saveBtn = document.getElementById('saveWhBtn');
            saveBtn.disabled = true;

            const formData = new FormData();
            formData.append('_csrf_token', '<?= CSRF::token() ?>');
            formData.append('id', whId);
            formData.append('name', whName);
            formData.append('code', whCode);
            formData.append('manager_name', whManager);
            formData.append('phone', whPhone);
            formData.append('address', whAddress);
            formData.append('is_active', whIsActive);

            const endpoint = whId ? '<?= url("api/warehouse/update") ?>' : '<?= url("api/warehouse/store") ?>';

            fetch(endpoint, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                saveBtn.disabled = false;
                if (data.success) {
                    alert(data.message || 'Warehouse saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save warehouse.'));
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                alert('Network error: ' + err.message);
            });
        });
    }

    // =========================================================================
    // SHOP QUICK-ADD, EDIT & DELETE AJAX
    // =========================================================================
    function resetDestForm() {
        document.getElementById('shop_id_input').value = '';
        document.getElementById('dest_name_input').value = '';
        document.getElementById('shop_code_input').value = '';
        document.getElementById('shop_manager_input').value = '';
        document.getElementById('shop_gst_input').value = '';
        document.getElementById('shop_phone_input').value = '';
        document.getElementById('shop_address_input').value = '';
        document.getElementById('shop_is_active_input').checked = true;

        document.getElementById('destModalTitleText').textContent = 'Add Shop';
        document.getElementById('saveDestBtnText').textContent = 'Save Shop';
        const cancelBtn = document.getElementById('cancelDestEditBtn');
        if (cancelBtn) cancelBtn.classList.add('d-none');
    }

    const cancelDestBtn = document.getElementById('cancelDestEditBtn');
    if (cancelDestBtn) {
        cancelDestBtn.addEventListener('click', resetDestForm);
    }

    const addDestForm = document.getElementById('addDestinationForm');
    if (addDestForm) {
        addDestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const shopId = document.getElementById('shop_id_input').value;
            const shopName = document.getElementById('dest_name_input').value.trim();
            const shopCode = document.getElementById('shop_code_input').value.trim();
            const shopManager = document.getElementById('shop_manager_input').value.trim();
            const shopGst = document.getElementById('shop_gst_input').value.trim();
            const shopPhone = document.getElementById('shop_phone_input').value.trim();
            const shopAddress = document.getElementById('shop_address_input').value.trim();
            const shopIsActive = document.getElementById('shop_is_active_input').checked ? 1 : 0;

            const saveBtn = document.getElementById('saveDestBtn');
            saveBtn.disabled = true;

            const formData = new FormData();
            formData.append('_csrf_token', '<?= CSRF::token() ?>');
            formData.append('id', shopId);
            formData.append('name', shopName);
            formData.append('code', shopCode);
            formData.append('manager_name', shopManager);
            formData.append('gst_number', shopGst);
            formData.append('phone', shopPhone);
            formData.append('address', shopAddress);
            formData.append('is_active', shopIsActive);

            const endpoint = shopId ? '<?= url("api/shop/update") ?>' : '<?= url("api/shop/store") ?>';

            fetch(endpoint, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                saveBtn.disabled = false;
                if (data.success) {
                    alert(data.message || 'Shop saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save shop.'));
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                alert('Network error: ' + err.message);
            });
        });
    }

    // =========================================================================
    // SALES CHANNEL QUICK-ADD, EDIT & DELETE AJAX
    // =========================================================================
    function resetChannelForm() {
        document.getElementById('channel_id_input').value = '';
        document.getElementById('channel_name_input').value = '';
        document.getElementById('channel_code_input').value = '';
        document.getElementById('channel_manager_input').value = '';
        document.getElementById('channel_phone_input').value = '';
        document.getElementById('channel_desc_input').value = '';
        document.getElementById('channel_is_active_input').checked = true;

        document.getElementById('channelModalTitleText').textContent = 'Add Sales Channel';
        document.getElementById('saveChannelBtnText').textContent = 'Save Sales Channel';
        const cancelBtn = document.getElementById('cancelChannelEditBtn');
        if (cancelBtn) cancelBtn.classList.add('d-none');
    }

    const cancelChannelBtn = document.getElementById('cancelChannelEditBtn');
    if (cancelChannelBtn) {
        cancelChannelBtn.addEventListener('click', resetChannelForm);
    }

    const addChannelForm = document.getElementById('addChannelForm');
    if (addChannelForm) {
        addChannelForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const channelId = document.getElementById('channel_id_input').value;
            const channelName = document.getElementById('channel_name_input').value.trim();
            const channelCode = document.getElementById('channel_code_input').value.trim();
            const channelManager = document.getElementById('channel_manager_input').value.trim();
            const channelPhone = document.getElementById('channel_phone_input').value.trim();
            const channelDesc = document.getElementById('channel_desc_input').value.trim();
            const channelIsActive = document.getElementById('channel_is_active_input').checked ? 1 : 0;

            const saveBtn = document.getElementById('saveChannelBtn');
            saveBtn.disabled = true;

            const formData = new FormData();
            formData.append('_csrf_token', '<?= CSRF::token() ?>');
            formData.append('id', channelId);
            formData.append('name', channelName);
            formData.append('code', channelCode);
            formData.append('manager_name', channelManager);
            formData.append('phone', channelPhone);
            formData.append('description', channelDesc);
            formData.append('is_active', channelIsActive);

            const endpoint = channelId ? '<?= url("api/sales-channel/update") ?>' : '<?= url("api/sales-channel/store") ?>';

            fetch(endpoint, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                saveBtn.disabled = false;
                if (data.success) {
                    alert(data.message || 'Sales Channel saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save sales channel.'));
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                alert('Network error: ' + err.message);
            });
        });
    }

    // =========================================================================
    // MODAL EDIT & DELETE BUTTONS DELEGATION
    // =========================================================================
    document.addEventListener('click', function(e) {
        // Warehouse Edit
        const editWhBtn = e.target.closest('.edit-wh-btn');
        if (editWhBtn) {
            const id = editWhBtn.getAttribute('data-id');
            fetch(`<?= url("api/warehouse/get") ?>/${id}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data) {
                        const d = res.data;
                        document.getElementById('wh_id_input').value = d.id;
                        document.getElementById('wh_name_input').value = d.name || '';
                        document.getElementById('wh_code_input').value = d.code || '';
                        document.getElementById('wh_manager_input').value = d.manager_name || '';
                        document.getElementById('wh_phone_input').value = d.phone || '';
                        document.getElementById('wh_address_input').value = d.address || '';
                        document.getElementById('wh_is_active_input').checked = (d.is_active == 1);

                        document.getElementById('whModalTitleText').textContent = 'Edit Warehouse';
                        document.getElementById('saveWhBtnText').textContent = 'Update Warehouse';
                        const cancelBtn = document.getElementById('cancelWhEditBtn');
                        if (cancelBtn) cancelBtn.classList.remove('d-none');
                        document.getElementById('wh_name_input').focus();
                    }
                });
        }

        // Warehouse Delete
        const delWhBtn = e.target.closest('.delete-wh-btn');
        if (delWhBtn) {
            const id = delWhBtn.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this warehouse?')) {
                const fd = new FormData();
                fd.append('_csrf_token', '<?= CSRF::token() ?>');
                fetch(`<?= url("api/warehouse/delete") ?>/${id}`, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        alert(res.message || (res.success ? 'Warehouse deleted successfully.' : 'Failed to delete warehouse.'));
                        if (res.success) location.reload();
                    });
            }
        }

        // Shop Edit
        const editShopBtn = e.target.closest('.edit-dest-btn');
        if (editShopBtn) {
            const id = editShopBtn.getAttribute('data-id');
            fetch(`<?= url("api/shop/get") ?>/${id}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data) {
                        const d = res.data;
                        document.getElementById('shop_id_input').value = d.id;
                        document.getElementById('dest_name_input').value = d.name || '';
                        document.getElementById('shop_code_input').value = d.code || '';
                        document.getElementById('shop_manager_input').value = d.manager_name || '';
                        document.getElementById('shop_gst_input').value = d.gst_number || '';
                        document.getElementById('shop_phone_input').value = d.phone || '';
                        document.getElementById('shop_address_input').value = d.address || '';
                        document.getElementById('shop_is_active_input').checked = (d.is_active == 1);

                        document.getElementById('destModalTitleText').textContent = 'Edit Shop';
                        document.getElementById('saveDestBtnText').textContent = 'Update Shop';
                        const cancelBtn = document.getElementById('cancelDestEditBtn');
                        if (cancelBtn) cancelBtn.classList.remove('d-none');
                        document.getElementById('dest_name_input').focus();
                    }
                });
        }

        // Shop Delete
        const delShopBtn = e.target.closest('.delete-dest-btn');
        if (delShopBtn) {
            const id = delShopBtn.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this shop?')) {
                const fd = new FormData();
                fd.append('_csrf_token', '<?= CSRF::token() ?>');
                fetch(`<?= url("api/shop/delete") ?>/${id}`, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        alert(res.message || (res.success ? 'Shop deleted successfully.' : 'Failed to delete shop.'));
                        if (res.success) location.reload();
                    });
            }
        }

        // Sales Channel Edit
        const editChanBtn = e.target.closest('.edit-channel-btn');
        if (editChanBtn) {
            const id = editChanBtn.getAttribute('data-id');
            fetch(`<?= url("api/sales-channel/get") ?>/${id}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data) {
                        const d = res.data;
                        document.getElementById('channel_id_input').value = d.id;
                        document.getElementById('channel_name_input').value = d.name || '';
                        document.getElementById('channel_code_input').value = d.code || '';
                        document.getElementById('channel_manager_input').value = d.manager_name || '';
                        document.getElementById('channel_phone_input').value = d.phone || '';
                        document.getElementById('channel_desc_input').value = d.description || '';
                        document.getElementById('channel_is_active_input').checked = (d.is_active == 1);

                        document.getElementById('channelModalTitleText').textContent = 'Edit Sales Channel';
                        document.getElementById('saveChannelBtnText').textContent = 'Update Sales Channel';
                        const cancelBtn = document.getElementById('cancelChannelEditBtn');
                        if (cancelBtn) cancelBtn.classList.remove('d-none');
                        document.getElementById('channel_name_input').focus();
                    }
                });
        }

        // Sales Channel Delete
        const delChanBtn = e.target.closest('.delete-channel-btn');
        if (delChanBtn) {
            const id = delChanBtn.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this sales channel?')) {
                const fd = new FormData();
                fd.append('_csrf_token', '<?= CSRF::token() ?>');
                fetch(`<?= url("api/sales-channel/delete") ?>/${id}`, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        alert(res.message || (res.success ? 'Sales Channel deleted successfully.' : 'Failed to delete sales channel.'));
                        if (res.success) location.reload();
                    });
            }
        }
    });

    // Mutually exclusive destination selects
    const destSelects = document.querySelectorAll('.dest-select');
    updateDestinationWarehouseOptions();
    destSelects.forEach(select => {
        select.addEventListener('change', function() {
            if (this.value !== '') {
                // If a value is selected, disable others
                destSelects.forEach(otherSelect => {
                    if (otherSelect !== this) {
                        otherSelect.value = '';
                        otherSelect.disabled = true;
                    }
                });
            } else {
                // If the value is cleared, re-enable all
                destSelects.forEach(otherSelect => {
                    otherSelect.disabled = false;
                });
            }
        });
    });

    const allocationForm = document.getElementById('allocationForm');
    if (allocationForm) {
        allocationForm.addEventListener('submit', function(event) {
            if (destinationWarehouseSelect && destinationWarehouseSelect.value !== ''
                && destinationWarehouseSelect.value === warehouseSelect?.value) {
                event.preventDefault();
                showScanAlert('warning', 'The destination warehouse must be different from the source warehouse.');
                destinationWarehouseSelect.focus();
                return;
            }

            const hasDestination = Array.from(destSelects).some(select => select.value !== '');
            if (hasDestination) return;

            event.preventDefault();
            showScanAlert('warning', 'Please select a destination before submitting. Your scanned products are still on this page.');
            document.getElementById('source_shop_select')?.focus();
        });
    }
});
</script>
