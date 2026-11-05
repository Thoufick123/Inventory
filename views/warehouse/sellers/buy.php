<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-cart-plus me-2 text-success"></i>Record Third-Party Product Purchase &amp; Inward Bill</h2>
        <span class="text-muted">Enter vendor bill details, upload bill screenshots, and record multiple purchased products with full validation</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('warehouse/sellers/create') ?>" class="btn btn-outline-primary">
            <i class="bi bi-person-plus me-1"></i> Register New Supplier
        </a>
        <a href="<?= url('warehouse/sellers') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Suppliers Hub
        </a>
    </div>
</div>

<form method="POST" action="<?= url('warehouse/sellers/store-buy') ?>" id="sellerPurchaseForm" enctype="multipart/form-data" class="needs-validation" novalidate>
    <?= CSRF::input() ?>

    <div class="row g-4 mb-4">
        <!-- ========================================================================= -->
        <!-- LEFT COLUMN: SUPPLIER SELECTION & BILL INWARD DETAILS -->
        <!-- ========================================================================= -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4 bg-white rounded-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-person-vcard me-2 text-primary"></i>1. Supplier &amp; Warehouse Inward</h6>
                    <button type="button" class="btn btn-xs btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#quickAddSellerModal">
                        <i class="bi bi-plus-lg me-1"></i> Quick Add Supplier
                    </button>
                </div>
                <div class="card-body p-3">
                    <!-- Seller Selector -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Third-Party Supplier / Vendor <span class="text-danger">*</span></label>
                        <select name="seller_id" class="form-select" id="sellerSelect" required onchange="updateSellerPreview()">
                            <option value="">-- Select Third-Party Supplier --</option>
                            <?php foreach ($sellers as $s): ?>
                                <option value="<?= $s['id'] ?>" 
                                        data-name="<?= e($s['name']) ?>" 
                                        data-company="<?= e($s['company'] ?? '') ?>"
                                        data-phone="<?= e($s['phone'] ?? '') ?>" 
                                        data-city="<?= e($s['city'] ?? '') ?>" 
                                        data-gst="<?= e($s['gst_number'] ?? '') ?>"
                                        <?= (isset($preSelectedSellerId) && $preSelectedSellerId == $s['id']) ? 'selected' : '' ?>>
                                    <?= e($s['name']) ?> <?= !empty($s['company']) ? '— ' . e($s['company']) : '' ?> (<?= e($s['phone']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a supplier.</div>
                        <div id="sellerPreviewBox" class="mt-2 p-2 bg-light rounded small border d-none">
                            <div class="fw-bold text-dark" id="previewSellerName"></div>
                            <div class="text-primary small" id="previewSellerCompany"></div>
                            <div class="text-muted"><i class="bi bi-telephone me-1"></i><span id="previewSellerPhone"></span> | <span id="previewSellerCity"></span></div>
                            <div class="text-secondary font-monospace small d-none" id="previewSellerGstBox">GSTIN: <span id="previewSellerGst"></span></div>
                        </div>
                    </div>

                    <!-- Destination Warehouse -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Destination Warehouse (Stock Inward) <span class="text-danger">*</span></label>
                        <select name="warehouse_id" class="form-select" id="warehouseSelect" required <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                            <?php foreach ($warehouses as $wh): ?>
                                <option value="<?= $wh['id'] ?>"><?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1): ?>
                            <input type="hidden" name="warehouse_id" value="<?= e($warehouses[0]['id'] ?? (function_exists('currentWarehouseId') ? currentWarehouseId() : '')) ?>">
                        <?php endif; ?>
                        <div class="invalid-feedback">Destination warehouse is required.</div>
                        <small class="text-muted">Purchased products will be automatically credited to this warehouse inventory.</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Bill Date</label>
                            <input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Delivery Date</label>
                            <input type="date" name="expected_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <!-- Payment Mode & Payment Status -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Payment Mode</label>
                            <select name="payment_method" class="form-select">
                                <option value="CASH" selected>CASH</option>
                                <option value="BANK_TRANSFER">Bank Transfer / NEFT</option>
                                <option value="UPI">UPI / GPay / PhonePe</option>
                                <option value="CHEQUE">Cheque</option>
                                <option value="CREDIT">Supplier Credit</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="PAID" selected>PAID</option>
                                <option value="PARTIAL">PARTIAL</option>
                                <option value="PENDING">PENDING</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">GST Regime</label>
                        <select name="gst_type" class="form-select" id="gstTypeSelect" onchange="calculateTotals()">
                            <option value="CGST_SGST" selected>Intra-State (CGST + SGST)</option>
                            <option value="IGST">Inter-State (IGST)</option>
                        </select>
                    </div>

                    <!-- Bill / Invoice Screenshot Upload -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-camera me-1 text-primary"></i>Upload Bill / Invoice Screenshot</span>
                            <span class="badge bg-light text-muted border">Image / PDF (Max 10MB)</span>
                        </label>
                        <div class="border border-2 border-dashed rounded-3 p-3 text-center bg-light" id="billDropzone">
                            <input type="file" name="bill_image" id="billImageInput" class="d-none" accept="image/*,.pdf" onchange="handleBillImageSelect(event)">
                            <div id="billUploadPlaceholder" role="button" onclick="document.getElementById('billImageInput').click();" class="py-2 cursor-pointer">
                                <i class="bi bi-cloud-arrow-up fs-2 text-primary d-block mb-1"></i>
                                <div class="fw-semibold small text-dark">Click or Drag Bill Screenshot</div>
                                <div class="text-muted fs-8">Supports JPG, PNG, WEBP, or PDF invoices</div>
                            </div>
                            <div id="billImagePreviewBox" class="d-none mt-2 position-relative">
                                <img id="billPreviewImg" src="" alt="Bill Screenshot" class="img-fluid rounded border shadow-sm" style="max-height: 160px; object-fit: contain;">
                                <div class="mt-2 d-flex align-items-center justify-content-center gap-2">
                                    <span id="billFileName" class="badge bg-white text-dark border font-monospace small text-truncate" style="max-width: 220px;"></span>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeBillImage()" title="Remove Image">
                                        <i class="bi bi-x-lg me-1"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Vendor Invoice / DC Ref # / Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Vendor Invoice #, Transport DC #, batch reference..." maxlength="500"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- RIGHT COLUMN: MULTIPLE PRODUCTS BUY LIST (PURE FORM INPUTS) -->
        <!-- ========================================================================= -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 d-flex flex-column">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="card-title mb-0 fw-bold"><i class="bi bi-boxes me-2 text-primary"></i>2. Product Buy List &amp; Inward Items (Multiple Products)</h6>
                        <small class="text-muted">Enter product names, style codes, quantities, and buying rates to inward stock and maintain purchase logs</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary shadow-sm" onclick="addProductRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Another Product Line
                    </button>
                </div>

                <div class="card-body p-3 flex-grow-1">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="productsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30px;" class="text-center">#</th>
                                    <th style="min-width: 200px;">Product / Item Name <span class="text-danger">*</span></th>
                                    <th style="min-width: 120px;">Style / SKU</th>
                                    <th style="min-width: 100px;">Color</th>
                                    <th style="min-width: 80px;">Size</th>
                                    <th style="width: 80px;" class="text-center">Qty <span class="text-danger">*</span></th>
                                    <th style="width: 105px;" class="text-end">Cost (₹) <span class="text-danger">*</span></th>
                                    <th style="width: 110px;" class="text-end">Total (₹)</th>
                                    <th style="width: 35px;" class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <!-- First dynamic product row -->
                                <tr class="product-row">
                                    <td class="text-center fw-bold text-muted row-num">1</td>
                                    <td>
                                        <input type="text" name="product_name[]" class="form-control form-control-sm item-name-input" placeholder="e.g. Mens Polo T-Shirt" maxlength="200" required oninput="handleProductNameInput(this)">
                                        <div class="invalid-feedback">Product name is required (min 2 chars).</div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="product_sku[]" class="form-control form-control-sm font-monospace sku-input" placeholder="e.g. TP-POLO-NAV-L" maxlength="100">
                                            <button type="button" class="btn btn-outline-secondary px-2 py-0" onclick="autoGenerateSku(this)" title="Auto-generate SKU">
                                                <i class="bi bi-magic text-primary"></i> Auto
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">Invalid SKU format.</div>
                                    </td>
                                    <td>
                                        <input type="text" name="product_color[]" class="form-control form-control-sm color-input" placeholder="e.g. Navy" maxlength="50" oninput="handleVariantPropInput(this)">
                                        <div class="invalid-feedback">Color is invalid.</div>
                                    </td>
                                    <td>
                                        <input type="text" name="product_size[]" class="form-control form-control-sm size-input" placeholder="e.g. L / XL" maxlength="20" oninput="handleVariantPropInput(this)">
                                        <div class="invalid-feedback">Size is invalid.</div>
                                    </td>
                                    <td>
                                        <input type="number" name="quantity[]" class="form-control form-control-sm qty-input text-center fw-bold" value="1" min="1" max="100000" step="1" required oninput="calculateTotals()">
                                        <div class="invalid-feedback">Min 1.</div>
                                    </td>
                                    <td>
                                        <input type="number" name="unit_price[]" class="form-control form-control-sm price-input text-end fw-semibold" value="0.00" step="0.01" min="0.01" max="1000000" required oninput="calculateTotals()">
                                        <input type="hidden" name="mrp[]" class="mrp-input" value="0.00">
                                        <input type="hidden" name="selling_price[]" class="sell-price-input" value="0.00">
                                        <input type="hidden" name="gst_percentage[]" class="gst-select" value="0">
                                        <div class="invalid-feedback">Rate > 0.</div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm total-display text-end fw-bold text-success" value="₹0.00" readonly>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeProductRow(this)" title="Remove item">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 text-muted small">
                        <i class="bi bi-info-circle me-1 text-primary"></i> <strong>Workflow:</strong> Saving records the purchase bill and stores products in the <strong>Warehouse Inventory System</strong>. You can review QR codes and inward units in the supplier hub.
                    </div>
                </div>

                <!-- Financial Valuation Summary Footer -->
                <div class="card-footer bg-light p-3 border-top">
                    <div class="row align-items-center g-3">
                        <div class="col-md-7">
                            <div class="d-flex flex-wrap gap-3 small">
                                <div><span class="text-muted">Total Products:</span> <strong id="lblItemCount" class="text-dark">1</strong></div>
                                <div><span class="text-muted">Total Quantity:</span> <strong id="lblTotalQty" class="text-dark">1 pcs</strong></div>
                                <div><span class="text-muted">Taxable Value:</span> <strong id="lblSubtotal">₹0.00</strong></div>
                                <div><span class="text-muted">GST Tax:</span> <strong id="lblTax">₹0.00</strong></div>
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <div class="fs-5 fw-bold text-success" id="lblTotalBox">
                                <span class="small fs-6 text-muted fw-normal">Total Bill Value:</span> <span id="lblTotal">₹0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Action Bar -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-3 d-flex flex-row justify-content-between align-items-center">
        <a href="<?= url('warehouse/sellers') ?>" class="btn btn-light px-4">
            <i class="bi bi-arrow-left me-1"></i> Cancel
        </a>
        <button type="submit" class="btn btn-success px-5 py-2 fw-semibold shadow-sm" id="btnSubmit">
            <i class="bi bi-box-seam me-1"></i> Save Purchase Bill &amp; Inward Stock
        </button>
    </div>
</form>

<!-- ========================================================================= -->
<!-- MODAL: QUICK ADD SELLER -->
<!-- ========================================================================= -->
<div class="modal fade" id="quickAddSellerModal" tabindex="-1" aria-labelledby="quickAddSellerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="quickAddSellerModalLabel">
                    <i class="bi bi-person-plus me-2"></i>Quick Add Third-Party Supplier
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickSellerForm" onsubmit="submitQuickSeller(event)">
                <?= CSRF::input() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Contact Person / Supplier Name <span class="text-danger">*</span></label>
                        <input type="text" id="qs_name" class="form-control" placeholder="e.g. Ramesh Fabrics / RK Apparels" required minlength="2" maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Company / Brand Name</label>
                        <input type="text" id="qs_company" class="form-control" placeholder="e.g. RK Apparels Pvt Ltd" maxlength="200">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                            <input type="tel" id="qs_phone" class="form-control" placeholder="10-digit mobile" required pattern="[0-9]{10}" maxlength="10">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">GSTIN</label>
                            <input type="text" id="qs_gst" class="form-control font-monospace" placeholder="15-digit GSTIN" maxlength="15" style="text-transform: uppercase;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">City</label>
                        <input type="text" id="qs_city" class="form-control" placeholder="e.g. Tirupur / Surat / Ahmedabad" maxlength="100">
                    </div>
                    <div id="qsErrorBox" class="alert alert-danger small d-none"></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btnSaveSeller">
                        <i class="bi bi-check-circle me-1"></i> Save &amp; Select Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="productRowTemplate">
    <tr class="product-row">
        <td class="text-center fw-bold text-muted row-num">1</td>
        <td>
            <input type="text" name="product_name[]" class="form-control form-control-sm item-name-input" placeholder="e.g. Mens Polo T-Shirt" maxlength="200" oninput="handleProductNameInput(this)">
            <div class="invalid-feedback">Product name is required (min 2 chars).</div>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <input type="text" name="product_sku[]" class="form-control form-control-sm font-monospace sku-input" placeholder="e.g. TP-POLO-NAV-L" maxlength="100">
                <button type="button" class="btn btn-outline-secondary px-2 py-0" onclick="autoGenerateSku(this)" title="Auto-generate SKU">
                    <i class="bi bi-magic text-primary"></i> Auto
                </button>
            </div>
        </td>
        <td>
            <input type="text" name="product_color[]" class="form-control form-control-sm color-input" placeholder="e.g. Navy" maxlength="50" oninput="handleVariantPropInput(this)">
            <div class="invalid-feedback">Color is invalid.</div>
        </td>
        <td>
            <input type="text" name="product_size[]" class="form-control form-control-sm size-input" placeholder="e.g. L / XL" maxlength="20" oninput="handleVariantPropInput(this)">
            <div class="invalid-feedback">Size is invalid.</div>
        </td>
        <td>
            <input type="number" name="quantity[]" class="form-control form-control-sm qty-input text-center fw-bold" value="1" min="1" max="100000" step="1" required oninput="calculateTotals()">
            <div class="invalid-feedback">Min 1.</div>
        </td>
        <td>
            <input type="number" name="unit_price[]" class="form-control form-control-sm price-input text-end fw-semibold" value="0.00" step="0.01" min="0.01" max="1000000" required oninput="calculateTotals()">
            <input type="hidden" name="mrp[]" class="mrp-input" value="0.00">
            <input type="hidden" name="selling_price[]" class="sell-price-input" value="0.00">
            <input type="hidden" name="gst_percentage[]" class="gst-select" value="0">
            <div class="invalid-feedback">Rate > 0.</div>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm total-display text-end fw-bold text-success" value="₹0.00" readonly>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeProductRow(this)" title="Remove item">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<style>
/* Real-time inline validation feedback styling */
.invalid-feedback {
    display: none;
    font-size: 0.76rem;
    font-weight: 500;
    margin-top: 3px;
    color: #dc3545;
}
.valid-feedback {
    display: none;
    font-size: 0.76rem;
    font-weight: 500;
    margin-top: 3px;
    color: #198754;
}
.form-control.is-invalid, .form-select.is-invalid {
    border-color: #dc3545 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right calc(0.375em + 0.1875rem) center !important;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
    padding-right: calc(1.5em + 0.75rem);
}
.form-control.is-valid, .form-select.is-valid {
    border-color: #198754 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73.6 5l.7-.71 1 1 3.5-3.5.71.71-4.21 4.23z'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right calc(0.375em + 0.1875rem) center !important;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
    padding-right: calc(1.5em + 0.75rem);
}
.is-invalid ~ .invalid-feedback,
.is-invalid + .invalid-feedback {
    display: block !important;
}
.is-valid ~ .valid-feedback,
.is-valid + .valid-feedback {
    display: block !important;
}
#productsTable td {
    vertical-align: top;
    padding: 0.5rem 0.35rem;
}
#productsTable .invalid-feedback {
    font-size: 0.70rem;
    line-height: 1.1;
    white-space: normal;
    margin-top: 2px;
}
</style>

<script>
// =========================================================================
// SELLER PREVIEW BOX
// =========================================================================
function updateSellerPreview() {
    const sel = document.getElementById('sellerSelect');
    const box = document.getElementById('sellerPreviewBox');
    if (!sel || !box) return;

    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('previewSellerName').textContent = opt.getAttribute('data-name') || '';
        document.getElementById('previewSellerCompany').textContent = opt.getAttribute('data-company') || '';
        document.getElementById('previewSellerPhone').textContent = opt.getAttribute('data-phone') || '';
        document.getElementById('previewSellerCity').textContent = opt.getAttribute('data-city') || '';
        const gst = opt.getAttribute('data-gst') || '';
        const gstBox = document.getElementById('previewSellerGstBox');
        if (gst) {
            document.getElementById('previewSellerGst').textContent = gst;
            gstBox.classList.remove('d-none');
        } else {
            gstBox.classList.add('d-none');
        }
        box.classList.remove('d-none');
    } else {
        box.classList.add('d-none');
    }
}

// =========================================================================
// BILL IMAGE PREVIEW & REMOVAL
// =========================================================================
function handleBillImageSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    document.getElementById('billFileName').textContent = file.name;
    
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('billPreviewImg').src = e.target.result;
            document.getElementById('billPreviewImg').classList.remove('d-none');
            document.getElementById('billUploadPlaceholder').classList.add('d-none');
            document.getElementById('billImagePreviewBox').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('billPreviewImg').classList.add('d-none');
        document.getElementById('billUploadPlaceholder').classList.add('d-none');
        document.getElementById('billImagePreviewBox').classList.remove('d-none');
    }
}

function removeBillImage() {
    const input = document.getElementById('billImageInput');
    input.value = '';
    document.getElementById('billPreviewImg').src = '';
    document.getElementById('billFileName').textContent = '';
    document.getElementById('billImagePreviewBox').classList.add('d-none');
    document.getElementById('billUploadPlaceholder').classList.remove('d-none');
}

// Drag & drop for bill upload
const dropzone = document.getElementById('billDropzone');
if (dropzone) {
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('border-primary', 'bg-primary-subtle');
        }, false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('border-primary', 'bg-primary-subtle');
        }, false);
    });
    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            const fileInput = document.getElementById('billImageInput');
            fileInput.files = files;
            handleBillImageSelect({ target: { files: files } });
        }
    });
}

// =========================================================================
// MULTI-PRODUCT DYNAMIC ROW MANAGEMENT & CALCULATIONS
// =========================================================================
function addProductRow() {
    const template = document.getElementById('productRowTemplate');
    const tbody = document.getElementById('productsTableBody');
    const clone = template.content.cloneNode(true);
    tbody.appendChild(clone);
    const newRow = tbody.lastElementChild;
    if (newRow) {
        attachLiveValidation(newRow);
    }
    updateRowNumbers();
    calculateTotals();
}

function removeProductRow(btn) {
    const tbody = document.getElementById('productsTableBody');
    if (tbody.querySelectorAll('tr.product-row').length > 1) {
        btn.closest('tr').remove();
        updateRowNumbers();
    } else {
        const row = btn.closest('tr');
        row.querySelector('.item-name-input').value = '';
        row.querySelectorAll('input[type="text"], input[type="number"]').forEach(inp => {
            if (inp.classList.contains('qty-input')) inp.value = '1';
            else if (inp.classList.contains('price-input') || inp.classList.contains('mrp-input') || inp.classList.contains('sell-price-input')) inp.value = '0.00';
            else inp.value = '';
            inp.classList.remove('is-invalid', 'is-valid');
        });
        row.querySelectorAll('.invalid-feedback').forEach(fb => fb.style.display = 'none');
        row.querySelector('.total-display').value = '₹0.00';
    }
    calculateTotals();
}

function updateRowNumbers() {
    const rows = document.querySelectorAll('#productsTableBody tr.product-row');
    rows.forEach((row, idx) => {
        const numEl = row.querySelector('.row-num');
        if (numEl) numEl.textContent = idx + 1;
    });
}

function calculateTotals() {
    let grossSubtotal = 0;
    let totalTax = 0;
    let grandTotal = 0;
    let totalUnits = 0;
    let activeItemCount = 0;

    const rows = document.querySelectorAll('#productsTableBody tr.product-row');

    rows.forEach(row => {
        const name = row.querySelector('.item-name-input').value.trim();
        const qty = parseInt(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const gstPct = parseFloat(row.querySelector('.gst-select').value) || 0;

        if (name !== '' || price > 0) {
            activeItemCount++;
            totalUnits += qty;
        }

        const lineGross = qty * price;
        grossSubtotal += lineGross;

        const lineTax = (lineGross * gstPct) / 100;
        totalTax += lineTax;

        const lineTotal = lineGross + lineTax;
        grandTotal += lineTotal;

        row.querySelector('.total-display').value = '₹' + lineTotal.toFixed(2);
    });

    document.getElementById('lblItemCount').textContent = activeItemCount;
    document.getElementById('lblTotalQty').textContent = totalUnits + ' pcs';
    document.getElementById('lblSubtotal').textContent = '₹' + grossSubtotal.toFixed(2);
    document.getElementById('lblTax').textContent = '₹' + totalTax.toFixed(2);
    document.getElementById('lblTotal').textContent = '₹' + grandTotal.toFixed(2);
}

// =========================================================================
// AJAX QUICK ADD SELLER
// =========================================================================
function submitQuickSeller(event) {
    event.preventDefault();
    const btn = document.getElementById('btnSaveSeller');
    const errBox = document.getElementById('qsErrorBox');
    errBox.classList.add('d-none');

    const nameInp = document.getElementById('qs_name');
    const phoneInp = document.getElementById('qs_phone');
    if (!nameInp.value.trim() || nameInp.value.trim().length < 2) {
        nameInp.classList.add('is-invalid');
        return;
    }
    if (!phoneInp.value.trim() || !/^[0-9]{10}$/.test(phoneInp.value.trim())) {
        phoneInp.classList.add('is-invalid');
        return;
    }

    btn.disabled = true;

    const fd = new FormData();
    fd.append('name', nameInp.value.trim());
    fd.append('company', document.getElementById('qs_company').value.trim());
    fd.append('phone', phoneInp.value.trim());
    fd.append('gst_number', document.getElementById('qs_gst').value.trim());
    fd.append('city', document.getElementById('qs_city').value.trim());
    fd.append('csrf_token', document.querySelector('#quickSellerForm input[name="csrf_token"]').value);

    fetch('<?= url("api/warehouse/sellers/quick-add") ?>', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            const sel = document.getElementById('sellerSelect');
            const opt = new Option(data.seller.name + (data.seller.company ? ' — ' + data.seller.company : ''), data.seller.id, true, true);
            opt.setAttribute('data-name', data.seller.name);
            opt.setAttribute('data-company', data.seller.company || '');
            opt.setAttribute('data-phone', data.seller.phone || '');
            opt.setAttribute('data-city', data.seller.city || '');
            opt.setAttribute('data-gst', data.seller.gst_number || '');
            sel.add(opt);
            validateField(sel);
            updateSellerPreview();

            bootstrap.Modal.getInstance(document.getElementById('quickAddSellerModal')).hide();
            document.getElementById('quickSellerForm').reset();
        } else {
            errBox.textContent = data.message || 'Failed to save supplier.';
            errBox.classList.remove('d-none');
        }
    })
    .catch(err => {
        btn.disabled = false;
        errBox.textContent = 'Server connection error.';
        errBox.classList.remove('d-none');
    });
}

// =========================================================================
// SKU AUTO GENERATION HELPER
// =========================================================================
function generateCleanSku(name, color, size) {
    name = (name || '').trim();
    color = (color || '').trim();
    size = (size || '').trim();

    const cleanName = name.replace(/[^A-Za-z0-9]/g, '').substring(0, 5).toUpperCase() || 'ITEM';
    const cleanColor = color ? '-' + color.replace(/[^A-Za-z0-9]/g, '').substring(0, 3).toUpperCase() : '';
    const cleanSize = size ? '-' + size.replace(/[^A-Za-z0-9]/g, '').substring(0, 4).toUpperCase() : '';
    const rand = Math.floor(100 + Math.random() * 900);

    return 'TP-' + cleanName + cleanColor + cleanSize + '-' + rand;
}

function autoGenerateSku(btn) {
    const row = btn.closest('tr');
    const name = row.querySelector('.item-name-input')?.value || '';
    const color = row.querySelector('.color-input')?.value || '';
    const size = row.querySelector('.size-input')?.value || '';
    const skuInput = row.querySelector('.sku-input');

    if (skuInput) {
        skuInput.value = generateCleanSku(name, color, size);
        validateField(skuInput);
    }
}

function handleProductNameInput(input) {
    calculateTotals();
    validateField(input);
    const row = input.closest('tr');
    const skuInput = row.querySelector('.sku-input');
    if (skuInput && !skuInput.value.trim()) {
        const color = row.querySelector('.color-input')?.value || '';
        const size = row.querySelector('.size-input')?.value || '';
        skuInput.value = generateCleanSku(input.value, color, size);
    }
}

function handleVariantPropInput(input) {
    validateField(input);
    const row = input.closest('tr');
    const skuInput = row.querySelector('.sku-input');
    // If SKU is empty or previously auto-generated with TP-
    if (skuInput && (!skuInput.value.trim() || skuInput.value.startsWith('TP-'))) {
        const name = row.querySelector('.item-name-input')?.value || '';
        const color = row.querySelector('.color-input')?.value || '';
        const size = row.querySelector('.size-input')?.value || '';
        if (name) {
            skuInput.value = generateCleanSku(name, color, size);
        }
    }
}

// =========================================================================
// REAL-TIME INLINE FIELD VALIDATION (LIVE AS USER TYPES / FOCUSES / BLURS)
// =========================================================================
function findFeedbackElement(input) {
    if (!input) return null;
    let parent = input.parentElement;
    let fb = parent.querySelector('.invalid-feedback');
    if (fb) return fb;

    if (parent.classList.contains('input-group') && parent.parentElement) {
        fb = parent.parentElement.querySelector('.invalid-feedback');
        if (fb) return fb;
    }

    const cell = input.closest('td') || input.closest('.col-12, .col-md-6, .col-6, .mb-3');
    if (cell) {
        fb = cell.querySelector('.invalid-feedback');
        if (fb) return fb;
    }

    return null;
}

function validateField(input) {
    if (!input || input.disabled || input.type === 'hidden' || input.type === 'submit' || input.type === 'button') return true;

    let isValid = true;
    let customError = '';

    const name = input.name || '';
    const id = input.id || '';
    const val = (input.value || '').trim();

    // Required checks
    if (input.hasAttribute('required') && !val) {
        isValid = false;
        if (name === 'seller_id') customError = 'Please select a third-party supplier.';
        else if (name === 'warehouse_id') customError = 'Destination warehouse is required.';
        else if (name === 'order_date') customError = 'Bill date is required.';
        else if (name === 'product_name[]' || input.classList.contains('item-name-input')) customError = 'Product name is required (min 2 chars).';
        else if (name === 'quantity[]' || input.classList.contains('qty-input')) customError = 'Quantity is required (min 1).';
        else if (name === 'unit_price[]' || input.classList.contains('price-input')) customError = 'Cost price is required (> 0).';
        else if (id === 'qs_name') customError = 'Supplier name is required (min 2 chars).';
        else if (id === 'qs_phone') customError = 'Mobile number is required (10 digits).';
        else customError = 'This field is required.';
    } 
    // Field-specific validation
    else if ((id === 'qs_name') && val.length < 2) {
        isValid = false;
        customError = 'Supplier name must be at least 2 characters.';
    } else if (input.type === 'tel' || name === 'phone' || id === 'qs_phone') {
        if (val && !/^[0-9]{10}$/.test(val)) {
            isValid = false;
            customError = 'Enter a valid 10-digit mobile number (digits only).';
        }
    } else if (name === 'gst_number' || id === 'qs_gst') {
        if (val && !/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i.test(val)) {
            isValid = false;
            customError = 'Enter valid 15-character GSTIN (e.g. 33AAAAA0000A1Z5).';
        }
    } else if (input.classList.contains('qty-input') || name === 'quantity[]') {
        const num = parseInt(val);
        if (isNaN(num) || num < 1) {
            isValid = false;
            customError = 'Quantity must be at least 1.';
        }
    } else if (input.classList.contains('price-input') || name === 'unit_price[]') {
        const num = parseFloat(val);
        if (isNaN(num) || num <= 0) {
            isValid = false;
            customError = 'Cost price must be greater than 0.';
        }
    } else if (input.classList.contains('mrp-input') || name === 'mrp[]') {
        const num = parseFloat(val);
        if (!isNaN(num) && num < 0) {
            isValid = false;
            customError = 'MRP cannot be negative.';
        }
    } else if (input.classList.contains('sell-price-input') || name === 'selling_price[]') {
        const num = parseFloat(val);
        if (!isNaN(num) && num < 0) {
            isValid = false;
            customError = 'Price cannot be negative.';
        }
    } else if (input.classList.contains('item-name-input') || name === 'product_name[]') {
        if (!val || val.length < 2) {
            isValid = false;
            customError = 'Product name is required (min 2 chars).';
        }
    }

    const feedback = findFeedbackElement(input);

    // Apply inline visual feedback classes immediately
    if (!isValid) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');

        if (feedback) {
            if (customError) feedback.textContent = customError;
            feedback.style.display = 'block';
        }
    } else {
        input.classList.remove('is-invalid');
        if (val !== '') {
            input.classList.add('is-valid');
        } else {
            input.classList.remove('is-valid');
        }

        if (feedback) {
            feedback.style.display = 'none';
        }
    }

    return isValid;
}

function attachLiveValidation(container) {
    const root = container || document;
    const inputs = root.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        ['input', 'blur', 'change', 'keyup', 'paste'].forEach(evt => {
            input.addEventListener(evt, function() {
                validateField(this);
            });
        });
    });
}

// Form submission validation
(function() {
    'use strict';
    const form = document.getElementById('sellerPurchaseForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            let formValid = true;
            let firstInvalid = null;

            const sellerSelect = form.querySelector('select[name="seller_id"]');
            if (sellerSelect && (!sellerSelect.value || sellerSelect.value === '')) {
                sellerSelect.classList.add('is-invalid');
                formValid = false;
                if (!firstInvalid) firstInvalid = sellerSelect;
            } else if (sellerSelect) {
                sellerSelect.classList.remove('is-invalid');
            }

            // Check product rows: at least one row with a product name is required
            const rows = form.querySelectorAll('#productsTableBody .product-row');
            let validProductCount = 0;

            rows.forEach(row => {
                const nameInp = row.querySelector('.item-name-input');
                const pName = (nameInp ? nameInp.value : '').trim();
                const priceInp = row.querySelector('.price-input');
                const priceVal = priceInp ? parseFloat(priceInp.value || 0) : 0;

                if (pName.length > 0) {
                    validProductCount++;
                    if (nameInp) nameInp.classList.remove('is-invalid');
                    if (priceVal <= 0 && priceInp) {
                        priceInp.classList.add('is-invalid');
                        formValid = false;
                        if (!firstInvalid) firstInvalid = priceInp;
                    } else if (priceInp) {
                        priceInp.classList.remove('is-invalid');
                    }
                }
            });

            if (validProductCount === 0) {
                const firstRowName = form.querySelector('#productsTableBody .product-row .item-name-input');
                if (firstRowName) {
                    firstRowName.classList.add('is-invalid');
                    if (!firstInvalid) firstInvalid = firstRowName;
                }
                formValid = false;
                alert('Please enter at least one product with a Name and Cost Price.');
            }

            if (!formValid) {
                event.preventDefault();
                event.stopPropagation();
                if (firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                form.classList.add('was-validated');
                return false;
            }
        }, false);
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    updateSellerPreview();
    attachLiveValidation();
    calculateTotals();
});
</script>
