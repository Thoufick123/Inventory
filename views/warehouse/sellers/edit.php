<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Third-Party Supplier &amp; Products: <?= e($seller['name']) ?></h2>
    </div>
</div>

<form method="POST" action="<?= url('warehouse/sellers/update/' . $seller['id']) ?>" id="supplierEditForm" class="needs-validation" novalidate enctype="multipart/form-data">
    <?= CSRF::input() ?>

    <div class="row g-4 mb-4">
        <!-- ========================================================================= -->
        <!-- LEFT COLUMN: SUPPLIER PROFILE & COMMERCIAL DETAILS -->
        <!-- ========================================================================= -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-building-gear me-2 text-primary"></i>1. Supplier Commercial Information</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Contact Person / Supplier Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="supplierNameInput" class="form-control" value="<?= e($seller['name']) ?>" required minlength="2" maxlength="150" autocomplete="off">
                            <div class="invalid-feedback">Supplier name is required (min 2 characters).</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Company / Brand Name</label>
                            <input type="text" name="company" id="supplierCompanyInput" class="form-control" value="<?= e($seller['company'] ?? '') ?>" placeholder="e.g. RK Apparels Pvt Ltd" maxlength="200">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" value="<?= e($seller['phone']) ?>" required pattern="[0-9]{10}" maxlength="10">
                            <div class="invalid-feedback">Must be a valid 10-digit mobile number.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= e($seller['email'] ?? '') ?>" placeholder="vendor@company.com" maxlength="255">
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">GSTIN (15 Digits)</label>
                            <input type="text" name="gst_number" class="form-control font-monospace" value="<?= e($seller['gst_number'] ?? '') ?>" placeholder="33AAAAA0000A1Z5" maxlength="15" style="text-transform: uppercase;">
                            <div class="invalid-feedback">Invalid GSTIN format (15 characters).</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Supplier Classification</label>
                            <select name="seller_type" class="form-select">
                                <option value="PRODUCT_SELLER" <?= ($seller['seller_type'] ?? '') === 'PRODUCT_SELLER' ? 'selected' : '' ?>>Finished Garments / Goods</option>
                                <option value="MATERIAL_SUPPLIER" <?= ($seller['seller_type'] ?? '') === 'MATERIAL_SUPPLIER' ? 'selected' : '' ?>>Fabric Material Supplier</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Street Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Office / Warehouse street address..." maxlength="500"><?= e($seller['address'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">City</label>
                            <input type="text" name="city" class="form-control" value="<?= e($seller['city'] ?? '') ?>" placeholder="e.g. Tirupur / Surat" maxlength="100">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">State</label>
                            <input type="text" name="state" class="form-control" value="<?= e($seller['state'] ?? 'Tamil Nadu') ?>" maxlength="100">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Payment / Credit Terms</label>
                            <input type="text" name="payment_terms" class="form-control" value="<?= e($seller['payment_terms'] ?? '') ?>" placeholder="e.g. Net 30 Days, 50% Advance" maxlength="100">
                        </div>

                        <!-- Document Attachment -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-file-earmark-arrow-up me-1 text-primary"></i>Supplier Document / Bill Screenshot</span>
                                <span class="badge bg-light text-muted border">Image / PDF</span>
                            </label>
                            <?php if (!empty($seller['document_url'])): ?>
                                <div class="mb-2 p-2 bg-light rounded border d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-file-earmark-check text-success fs-5"></i>
                                        <span class="small text-muted font-monospace text-truncate" style="max-width: 200px;"><?= basename(parse_url($seller['document_url'], PHP_URL_PATH)) ?></span>
                                    </div>
                                    <a href="<?= e($seller['document_url']) ?>" target="_blank" class="btn btn-xs btn-outline-primary">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> View Document
                                    </a>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="bill_image" class="form-control form-control-sm" accept="image/*,.pdf">
                            <small class="text-muted">Upload replacement document/bill screenshot (JPG, PNG, PDF)</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Supplier Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Internal remarks, contact notes..."><?= e($seller['notes'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Account Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveSwitch" <?= $seller['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label small fw-semibold text-dark" for="isActiveSwitch">Active Supplier</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- RIGHT COLUMN: SUPPLIER PRODUCTS & CATALOG STYLES EDIT SECTION -->
        <!-- ========================================================================= -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 d-flex flex-column">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="card-title mb-0 fw-bold"><i class="bi bi-boxes me-2 text-primary"></i>2. Supplier Products &amp; Catalog Styles (Edit Details)</h6>
                       
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                            <?= count($products ?? []) ?> Existing Variant(s)
                        </span>
                        <button type="button" class="btn btn-sm btn-primary shadow-sm px-3 fw-semibold" onclick="addNewProductRow()">
                            <i class="bi bi-plus-circle me-1"></i> Add Another Product Line
                        </button>
                    </div>
                </div>

                <div class="card-body p-3 flex-grow-1">
                    <?php if (!empty($products)): ?>
                        <!-- Existing Supplier Products Table -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <strong class="small text-dark"><i class="bi bi-check2-square text-success me-1"></i>Existing Catalog Product Styles &amp; Variants:</strong>
                                <small class="text-muted">Directly edit names, SKU, color, size, packaging &amp; cost rate below</small>
                            </div>
                            <div class="table-responsive border rounded-3 bg-white shadow-xs">
                                <table class="table table-sm table-bordered align-middle mb-0" id="existingProductsTable">
                                    <thead class="table-light">
                                        <tr class="small text-muted">
                                            <th style="width: 30px;" class="text-center">#</th>
                                            <th style="min-width: 170px;">Product / Item Name</th>
                                            <th style="min-width: 120px;">Style / SKU</th>
                                            <th style="min-width: 90px;">Color</th>
                                            <th style="min-width: 70px;">Size</th>
                                            <th style="min-width: 180px;">QR Packaging Mode</th>
                                            <th style="width: 100px;" class="text-end">Cost (₹)</th>
                                            <th style="width: 110px;" class="text-center text-primary"><i class="bi bi-boxes me-1"></i>Stock (Pcs)</th>
                                            <!-- <th style="width: 80px;" class="text-center">Actions</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $idx => $p): ?>
                                            <tr class="existing-product-row">
                                                <td class="text-center fw-bold text-muted"><?= $idx + 1 ?></td>
                                                <td>
                                                    <input type="hidden" name="existing_variant_id[]" value="<?= (int)$p['variant_id'] ?>">
                                                    <input type="text" name="existing_product_name[]" class="form-control form-control-sm" value="<?= e($p['product_name']) ?>" placeholder="Product Name" maxlength="200">
                                                </td>
                                                <td>
                                                    <input type="text" name="existing_sku[]" class="form-control form-control-sm font-monospace" value="<?= e($p['sku']) ?>" placeholder="SKU" maxlength="100">
                                                </td>
                                                <td>
                                                    <input type="text" name="existing_color[]" class="form-control form-control-sm" value="<?= e($p['color_name'] ?? '') ?>" placeholder="Color" maxlength="50">
                                                </td>
                                                <td>
                                                    <input type="text" name="existing_size[]" class="form-control form-control-sm" value="<?= e($p['size_name'] ?? '') ?>" placeholder="Size" maxlength="20">
                                                </td>
                                                <td>
                                                    <select name="existing_qr_type[]" class="form-select form-select-sm fw-semibold" onchange="handleExistingRowQRModeChange(this)">
                                                        <option value="INDIVIDUAL" <?= ($p['qr_type'] ?? '') !== 'SERIES_BATCH' ? 'selected' : '' ?>>🏷️ Individual (1 Pc = 1 QR)</option>
                                                        <option value="SERIES_BATCH" <?= ($p['qr_type'] ?? '') === 'SERIES_BATCH' ? 'selected' : '' ?>>📦 Master Series Bundle Pack</option>
                                                    </select>
                                                    <div class="existing-series-box <?= ($p['qr_type'] ?? '') === 'SERIES_BATCH' ? '' : 'd-none' ?> mt-1 p-1 bg-light rounded border">
                                                        <div class="input-group input-group-sm mb-1">
                                                            <span class="input-group-text py-0 px-1 font-monospace fs-8 text-muted">From</span>
                                                            <input type="text" name="existing_series_start_code[]" class="form-control form-control-sm font-monospace" value="<?= e($p['series_start_code'] ?? '') ?>" placeholder="<?= e($p['sku']) ?>-001">
                                                        </div>
                                                        <div class="input-group input-group-sm mb-1">
                                                            <span class="input-group-text py-0 px-1 font-monospace fs-8 text-muted">To</span>
                                                            <input type="text" name="existing_series_end_code[]" class="form-control form-control-sm font-monospace" value="<?= e($p['series_end_code'] ?? '') ?>" placeholder="<?= e($p['sku']) ?>-050">
                                                        </div>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text py-0 px-1 fs-8 text-muted">Pack Qty</span>
                                                            <input type="number" name="existing_pack_quantity[]" class="form-control form-control-sm" value="<?= (int)($p['pack_quantity'] ?? 1) ?>" min="1" max="10000">
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="number" name="existing_cost[]" class="form-control form-control-sm text-end fw-semibold" value="<?= number_format((float)($p['purchase_price'] ?? 0), 2, '.', '') ?>" step="0.01" min="0">
                                                    <input type="hidden" name="existing_mrp[]" value="<?= number_format((float)($p['mrp'] ?? 0), 2, '.', '') ?>">
                                                    <input type="hidden" name="existing_selling_price[]" value="<?= number_format((float)($p['selling_price'] ?? 0), 2, '.', '') ?>">
                                                </td>
                                                <td class="text-center" style="width: 110px;">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="existing_stock[]" class="form-control form-control-sm text-center fw-bold text-primary" value="<?= (int)($p['current_stock'] ?? $p['total_bought_qty'] ?? 0) ?>" min="0" step="1" title="Update live stock for this variant">
                                                        <span class="input-group-text px-1 text-muted fs-8">pcs</span>
                                                    </div>
                                                    <?php if ((int)($p['current_stock'] ?? 0) > 0): ?>
                                                        <small class="text-success fs-8 d-block mt-0.5"><i class="bi bi-check2 me-0.5"></i>Live: <?= (int)$p['current_stock'] ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted fs-8 d-block mt-0.5">Bought: <?= (int)($p['total_bought_qty'] ?? 0) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border text-center py-3 mb-3">
                            <i class="bi bi-box-seam fs-3 text-muted d-block mb-1"></i>
                            <span class="text-muted small">No product styles registered for this supplier yet. You can add new products below.</span>
                        </div>
                    <?php endif; ?>

                    <!-- New Product Lines Entry Box -->
                    <div class="mt-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong class="small text-dark"><i class="bi bi-plus-circle text-primary me-1"></i>Add New Product Lines to this Supplier:</strong>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" id="newProductsTable">
                                <thead class="table-light">
                                    <tr class="small text-muted">
                                        <th style="width: 30px;" class="text-center">#</th>
                                        <th style="min-width: 170px;">Product / Item Name</th>
                                        <th style="min-width: 110px;">Style / SKU</th>
                                        <th style="min-width: 80px;">Color</th>
                                        <th style="min-width: 65px;">Size</th>
                                        <th style="min-width: 180px;">QR Packaging Mode</th>
                                        <th style="width: 100px;" class="text-end">Cost Rate (₹)</th>
                                        <th style="width: 125px;" class="text-center"><i class="bi bi-gift-fill text-primary me-1"></i>Complementary</th>
                                        <th style="width: 35px;" class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody id="newProductsTableBody">
                                    <tr id="noNewRowsPlaceholder">
                                        <td colspan="9" class="text-center py-2 text-muted small">
                                            <i class="bi bi-info-circle me-1"></i> Click <strong>"+ Add Another Product Line"</strong> above to register additional products for this supplier.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer Summary / Helper -->
                <div class="card-footer bg-light p-3 border-top">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted">
                            <i class="bi bi-shield-check text-success me-1"></i> Changes will immediately sync product names, SKU barcodes, and packaging modes in the <strong>Warehouse Catalog</strong>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Action Bar -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-3 d-flex flex-row justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('warehouse/sellers') ?>" class="btn btn-light px-4">
                <i class="bi bi-arrow-left me-1"></i> Cancel
            </a>
            <button type="button" class="btn btn-outline-danger" onclick="if(confirm('Are you sure you want to delete supplier &quot;<?= e(addslashes($seller['name'])) ?>&quot;?')) document.getElementById('deleteSupplierForm').submit();">
                <i class="bi bi-trash me-1"></i> Delete Supplier
            </button>
        </div>
        <button type="submit" class="btn btn-primary px-5 py-2 fw-semibold shadow-sm" id="btnSubmit">
            <i class="bi bi-check-circle me-1"></i> Save Changes &amp; Update Products
        </button>
    </div>
</form>

<form id="deleteSupplierForm" method="POST" action="<?= url('warehouse/sellers/delete/' . $seller['id']) ?>" style="display:none;">
    <?= csrf_field() ?>
</form>

<!-- Template for adding new product rows during supplier edit -->
<template id="newProductRowTemplate">
    <tr class="new-product-row">
        <td class="text-center fw-bold text-muted new-row-num">1</td>
        <td>
            <input type="text" name="new_product_name[]" class="form-control form-control-sm item-name-input" placeholder="e.g. Mens Shirt / Socks Pack" maxlength="200" oninput="handleNewProductNameInput(this)">
        </td>
        <td>
            <div class="input-group input-group-sm">
                <input type="text" name="new_product_sku[]" class="form-control form-control-sm font-monospace sku-input" placeholder="e.g. TP-ITEM-01" maxlength="100">
                <button type="button" class="btn btn-outline-secondary px-2 py-0" onclick="autoGenerateNewSku(this)" title="Auto-generate SKU">
                    <i class="bi bi-magic text-primary"></i>
                </button>
            </div>
        </td>
        <td>
            <input type="text" name="new_product_color[]" class="form-control form-control-sm color-input" placeholder="e.g. Navy" maxlength="50" oninput="handleNewVariantPropInput(this)">
        </td>
        <td>
            <input type="text" name="new_product_size[]" class="form-control form-control-sm size-input" placeholder="e.g. L / XL" maxlength="20" oninput="handleNewVariantPropInput(this)">
        </td>
        <td>
            <select name="new_product_qr_type[]" class="form-select form-select-sm qr-type-select fw-semibold" onchange="handleNewRowQRModeChange(this)">
                <option value="INDIVIDUAL" selected>🏷️ Individual (1 Pc = 1 QR)</option>
                <option value="SERIES_BATCH">📦 Master Series Bundle Pack</option>
            </select>
            <div class="row-series-box d-none mt-1 p-1 bg-light rounded border">
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text py-0 px-1 font-monospace fs-8 text-muted">From</span>
                    <input type="text" name="new_series_start_code[]" class="form-control form-control-sm font-monospace series-start-input" placeholder="SKU-001">
                </div>
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text py-0 px-1 font-monospace fs-8 text-muted">To</span>
                    <input type="text" name="new_series_end_code[]" class="form-control form-control-sm font-monospace series-end-input" placeholder="SKU-050">
                </div>
                <div class="input-group input-group-sm">
                    <span class="input-group-text py-0 px-1 fs-8 text-muted">Pack Qty</span>
                    <input type="number" name="new_pack_quantity[]" class="form-control form-control-sm" value="10" min="1" max="10000">
                </div>
            </div>
        </td>
        <td>
            <input type="number" name="new_unit_price[]" class="form-control form-control-sm price-input text-end fw-semibold" value="0.00" step="0.01" min="0" max="1000000">
        </td>
        <td class="text-center">
            <input type="hidden" name="new_is_complementary[]" value="0" class="comp-hidden-val">
            <div class="form-check form-switch d-inline-flex align-items-center justify-content-center m-0">
                <input class="form-check-input comp-switch" type="checkbox" role="switch" onchange="this.closest('td').querySelector('.comp-hidden-val').value = this.checked ? '1' : '0'; this.closest('td').querySelector('.comp-label-badge').textContent = this.checked ? 'Complementary' : 'Normal'; this.closest('td').querySelector('.comp-label-badge').className = this.checked ? 'badge bg-primary text-white d-block mt-0.5' : 'badge bg-light text-muted border d-block mt-0.5';" title="Toggle Normal / Complementary product">
            </div>
            <span class="badge bg-light text-muted border d-block mt-0.5 comp-label-badge" style="font-size: 0.65rem;">Normal</span>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeNewProductRow(this)" title="Remove item">
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
}
.form-control.is-valid, .form-select.is-valid {
    border-color: #198754 !important;
}
.is-invalid ~ .invalid-feedback,
.is-invalid + .invalid-feedback {
    display: block !important;
}
#existingProductsTable td, #newProductsTable td {
    vertical-align: middle;
    padding: 0.45rem 0.35rem;
}
</style>

<script src="<?= asset('js/warehouse_supplier_edit.js') ?>"></script>
