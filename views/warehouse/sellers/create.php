<?php
// views/warehouse/sellers/create.php
// Supplier Registration Wizard — Phase 1: Supplier Commercial Profile & Bill Setup
$isEditMode = (!empty($_GET['mode']) && $_GET['mode'] === 'edit');
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-person-plus-fill me-2 text-primary"></i><?= $isEditMode ? 'Edit Third-Party Supplier' : 'Register Third-Party Supplier' ?></h2>
        <p class="text-muted small mb-0"><?= $isEditMode ? 'Phase 1 of 2: Update supplier commercial credentials and settlement bill setup.' : 'Phase 1 of 2: Collect supplier commercial credentials and settlement bill setup.' ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('warehouse/sellers') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Suppliers Hub
        </a>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2-PHASE WIZARD STEPPER -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <!-- Phase 1 Step Indicator (Active) -->
            <div class="col-md-6">
                <div class="d-flex align-items-center p-2.5 rounded-3 bg-primary-subtle border border-primary">
                    <div class="badge rounded-circle bg-primary text-white me-3 fs-6 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        1
                    </div>
                    <div>
                        <span class="badge bg-primary text-white mb-0.5">CURRENT STEP</span>
                        <div class="fw-bold text-dark">Supplier Details &amp; Settlement Setup</div>
                        <small class="text-muted">Profile, GSTIN, payment terms &amp; invoice information</small>
                    </div>
                </div>
            </div>

            <!-- Phase 2 Step Indicator (Upcoming) -->
            <div class="col-md-6">
                <div class="d-flex align-items-center p-2.5 rounded-3 bg-light border opacity-75">
                    <div class="badge rounded-circle bg-secondary text-white me-3 fs-6 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        2
                    </div>
                    <div>
                        <span class="badge bg-light text-secondary border mb-0.5">NEXT STEP</span>
                        <div class="fw-bold text-dark">Product Selection &amp; Auto-Fill</div>
                        <small class="text-muted">Click pre-set category tiles &amp; enter HSN, weights, MRP, cost &amp; qty</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Validation Alert -->
<div id="validationAlert" class="alert alert-danger alert-dismissible fade show shadow-sm d-none mb-4" role="alert">
    <div class="d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
        <div id="validationAlertText">Please fill in all required fields.</div>
    </div>
    <button type="button" class="btn-close" onclick="document.getElementById('validationAlert').classList.add('d-none')"></button>
</div>

<!-- Phase 1 Form -->
<form method="POST" action="<?= url('warehouse/sellers/store-phase1') ?>" enctype="multipart/form-data" id="supplierPhase1Form" onsubmit="handlePhase1Submit(event)">
    <?= CSRF::field() ?>
    <input type="hidden" name="existing_supplier_id" id="existingSupplierId" value="<?= (int)($selectedSellerId ?? 0) ?>">
    <input type="hidden" name="edit_supplier_mode" value="<?= $isEditMode ? '1' : '0' ?>">

    <div class="row g-4 mb-4">
        <!-- ===================================================================== -->
        <!-- CARD 1: SUPPLIER COMMERCIAL PROFILE -->
        <!-- ===================================================================== -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-building-check text-primary me-2"></i>1. Supplier Commercial Profile
                    </h6>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-success text-white d-none" id="badgeExistingActive"><i class="bi bi-check-circle-fill me-1"></i>Existing Supplier</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="badgeNewActive"><i class="bi bi-person-plus-fill me-1"></i>New Supplier</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <!-- SUPPLIER NAME / DROPDOWN SELECTION FIELD -->
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between mb-1.5">
                                <label class="form-label small fw-bold text-dark mb-0">
                                    <i class="bi bi-person-badge text-primary me-1"></i>Contact Person / Supplier Name <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-sm btn-link text-decoration-none py-0 px-0 fw-semibold" id="btnToggleSupplierMode" onclick="toggleSupplierModeDirect()" style="font-size:0.78rem;">
                                    <i class="bi bi-person-plus-fill text-success me-1"></i>+ Add New Supplier
                                </button>
                            </div>

                            <!-- Mode A: Existing supplier text input with suggestions -->
                            <div id="supplierDropdownWrapper">
                                <div class="position-relative">
                                    <input type="text" id="supplierSearchInput" class="form-control form-control-sm border-primary-subtle fw-semibold" placeholder="Type supplier name or phone number..." autocomplete="off" aria-label="Search existing supplier">
                                    <div class="position-absolute end-0 top-50 translate-middle-y me-2 text-primary">
                                        <i class="bi bi-search"></i>
                                    </div>
                                </div>
                                <div class="mt-2 border rounded-3 bg-white shadow-sm d-none" id="supplierSuggestionsBox" style="max-height: 190px; overflow-y: auto;">
                                    <?php foreach (($existingSuppliers ?? []) as $es):
                                        $cName = !empty($es['company']) ? $es['company'] : $es['name'];
                                        $contact = !empty($es['company']) && !empty($es['name']) && $es['name'] !== $es['company'] ? ' (' . $es['name'] . ')' : '';
                                        $gst = !empty($es['gst_number']) ? ' • GST: ' . $es['gst_number'] : '';
                                        $phone = !empty($es['phone']) ? ' • Ph: ' . $es['phone'] : '';
                                        $city = !empty($es['city']) ? ' • ' . $es['city'] : '';
                                        $displayText = $cName . $contact . $phone . $gst . $city;
                                    ?>
                                        <button type="button" class="list-group-item list-group-item-action border-0 text-start supplier-suggestion-item px-3 py-2" data-supplier-id="<?= (int)$es['id'] ?>" data-supplier-label="<?= e($displayText) ?>">
                                            <div class="fw-semibold text-dark"><?= e($cName . $contact) ?></div>
                                            <div class="small text-muted"><?= e($phone ?: $gst ?: ($city ?: '')) ?></div>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="form-text text-muted fs-8 mt-1">
                                    <i class="bi bi-info-circle text-primary me-1"></i>Type a supplier name or phone number to suggest matching details. Selecting a match auto-fills the profile.
                                </div>
                            </div>

                            <!-- Mode B: Text input for New Supplier Name -->
                            <div id="supplierTextInputWrapper" class="d-none">
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-success border-success-subtle"><i class="bi bi-person-plus-fill text-success"></i></span>
                                    <input type="text" id="newSupplierNameInput" class="form-control text-uppercase fw-semibold border-success-subtle" placeholder="Enter new contact person / supplier name..." minlength="2" maxlength="150" oninput="onNewSupplierNameInput(this.value)">
                                    <button type="button" class="btn btn-outline-secondary btn-sm px-2.5" onclick="setSupplierMode('existing')" title="Switch to existing supplier dropdown">
                                        <i class="bi bi-arrow-return-left me-1"></i> Pick Existing
                                    </button>
                                </div>
                                <div class="form-text text-success fs-8 mt-1">
                                    <i class="bi bi-plus-circle me-1"></i>Registering a new supplier. Enter their commercial credentials below.
                                </div>
                            </div>

                            <!-- Hidden field carrying final name parameter for controller -->
                            <input type="hidden" name="name" id="supplierName" value="">
                        </div>

                        <!-- Existing Supplier Auto-Filled Alert Banner -->
                        <div class="col-12" id="existingSupplierBannerWrapper">
                            <div id="existingSupplierBanner" class="alert alert-success border-success-subtle shadow-xs py-2 px-3 mb-0 d-none align-items-center justify-content-between">
                                <div class="small">
                                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                                    Pre-filled for: <strong id="bannerSupplierName"></strong>
                                    <div class="text-muted fs-8">Profile loaded from registered database. Only invoice bill details needed in Section 2.</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="clearExistingSupplierSelection()" title="Clear or switch">
                                    <i class="bi bi-arrow-repeat me-1"></i> Clear
                                </button>
                            </div>
                        </div>

                        <!-- 1. Company Name Field -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">
                                <i class="bi bi-building me-1 text-primary"></i>Company Name
                            </label>
                            <input type="text" name="company" id="supplierCompany" class="form-control text-uppercase" placeholder="e.g. RK Apparels Pvt Ltd" maxlength="200">
                        </div>

                        <!-- 2. Brand Name Field (Dropdown + New Brand Creation) -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold mb-0">
                                    <i class="bi bi-tag-fill me-1 text-primary"></i>Brand Name <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-sm btn-link text-decoration-none py-0 px-0 fw-semibold" id="btnToggleBrandMode" onclick="toggleBrandModeDirect()" style="font-size:0.78rem;">
                                    <i class="bi bi-plus-circle-fill text-success me-1"></i>+ Add New Brand
                                </button>
                            </div>

                            <!-- Mode A: Dropdown showing Existing Brands -->
                            <div id="brandDropdownWrapper">
                                <select name="brand_id" id="brandSelect" class="form-select text-uppercase" onchange="onBrandSelectChange(this.value)">
                                    <option value="">-- Choose Existing Brand --</option>
                                    <?php foreach (($brands ?? []) as $b): ?>
                                        <option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="__NEW__" class="fw-bold text-success">+ Create New Brand...</option>
                                </select>
                            </div>

                            <!-- Mode B: Direct Input for Newly Created Brand -->
                            <div id="brandTextInputWrapper" class="d-none">
                                <div class="input-group">
                                    <input type="text" name="new_brand_name" id="newBrandNameInput" class="form-control text-uppercase" placeholder="Enter new brand name..." minlength="2" maxlength="150">
                                    <button type="button" class="btn btn-outline-secondary" onclick="setBrandMode('existing')" title="Select from existing brands">
                                        <i class="bi bi-arrow-return-left"></i>
                                    </button>
                                </div>
                                <div class="form-text text-muted" style="font-size: 0.72rem;">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>Will be newly created in catalog brands.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Mobile Number</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light">+91</span>
                                <input type="tel" name="phone" id="supplierPhone" class="form-control" placeholder="9876543210" inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10" title="Enter a valid 10-digit Indian mobile number">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="email" name="email" id="supplierEmail" class="form-control form-control-sm" placeholder="vendor@company.com" maxlength="255">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">GSTIN (15 Characters)</label>
                            <input type="text" name="gst_number" id="supplierGst" class="form-control form-control-sm font-monospace" placeholder="33AAAAA0000A1Z5" maxlength="15" pattern="[0-9]{2}[A-Za-z]{5}[0-9]{4}[A-Za-z][1-9A-Za-z][Zz][0-9A-Za-z]" title="Enter a valid 15-character GSTIN" style="text-transform: uppercase;">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Street Address</label>
                            <textarea name="address" class="form-control form-control-sm" rows="2" placeholder="Office / Warehouse street address..." maxlength="500"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">City</label>
                            <input type="text" name="city" class="form-control form-control-sm" placeholder="e.g. Tirupur / Surat / Chennai" maxlength="100">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">State</label>
                            <input type="text" name="state" class="form-control form-control-sm" value="Tamil Nadu" maxlength="100">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- CARD 2: BILL & SETTLEMENT SETUP -->
        <!-- ===================================================================== -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-receipt-cutoff text-success me-2"></i>2. Bill &amp; Inward Settlement Setup
                    </h6>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-file-earmark-plus me-1"></i>New Inward Bill Details</span>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-light border py-2 px-3 mb-3 d-flex align-items-center gap-2 small text-muted">
                        <i class="bi bi-info-circle-fill text-primary fs-6"></i>
                        <span>Select the destination warehouse and bill date, then upload the supplier's bill or invoice to continue.</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Destination Warehouse (Live Stock Inward)</label>
                            <select name="warehouse_id" class="form-select form-select-sm" id="warehouseSelect">
                                <?php if (!empty($warehouses)): ?>
                                    <?php foreach ($warehouses as $wh): ?>
                                        <option value="<?= $wh['id'] ?>"><?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="1">Main Central Warehouse (WH-MAIN)</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Bill Date</label>
                            <input type="date" name="order_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Payment Method</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="CASH" selected>CASH</option>
                                <option value="BANK_TRANSFER">Bank Transfer / NEFT / RTGS</option>
                                <option value="UPI">UPI / GPay / PhonePe</option>
                                <option value="CHEQUE">Cheque</option>
                                <option value="CREDIT">Supplier Credit</option>
                            </select>
                        </div>

                        <!-- Bill Document / Invoice Photo Upload -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-camera me-1 text-primary"></i>Upload Bill / Invoice <span class="text-danger" id="billRequiredMarker">*</span></span>
                                <span class="badge bg-light text-muted border">Image or PDF (Max 10MB)</span>
                            </label>
                            <div class="border border-2 border-dashed rounded-3 p-3 text-center bg-light" id="billDropzone">
                                <input type="file" name="bill_image" id="billImageInput" class="d-none" accept="image/*,.pdf" onchange="handleBillImageSelect(event)">
                                <div id="existingBillDocument" class="d-none mb-2 text-start">
                                    <div class="d-flex align-items-center gap-3 p-2 bg-white rounded border">
                                        <div id="existingBillPreview"></div>
                                        <div class="min-w-0">
                                            <div class="fw-semibold small text-dark">Existing uploaded bill / invoice</div>
                                            <a id="existingBillLink" href="#" target="_blank" rel="noopener noreferrer" class="small text-truncate d-inline-block" style="max-width: 260px;">View existing bill</a>
                                            <div class="text-muted fs-8">Upload a new file below only if you want to replace it.</div>
                                        </div>
                                    </div>
                                </div>
                                <div id="billUploadPlaceholder" role="button" onclick="document.getElementById('billImageInput').click();" class="py-2 cursor-pointer">
                                    <i class="bi bi-cloud-arrow-up fs-2 text-primary d-block mb-1"></i>
                                    <div class="fw-semibold small text-dark">Click to Upload Bill / Invoice</div>
                                    <div class="text-muted fs-8" id="billUploadHelp">Required to continue. Supports JPG, PNG, WEBP, or PDF invoices (max 10MB).</div>
                                </div>
                                <div id="billImagePreviewBox" class="d-none mt-2 position-relative">
                                    <img id="billPreviewImg" src="" alt="Bill Screenshot" class="img-fluid rounded border shadow-sm" style="max-height: 120px; object-fit: contain;">
                                    <div class="mt-2 d-flex align-items-center justify-content-center gap-2">
                                        <span id="billFileName" class="badge bg-white text-dark border font-monospace small text-truncate" style="max-width: 200px;"></span>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeBillImage()">
                                            <i class="bi bi-x-lg me-1"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Sticky Submission Bar -->
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted small">
                <i class="bi bi-arrow-right-circle text-primary me-1"></i>
                <?= $isEditMode ? 'Saving Phase 1 will update this supplier and immediately unlock the product selection wizard.' : 'Saving Phase 1 will register this supplier and immediately unlock the product selection wizard.' ?>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('warehouse/sellers') ?>" class="btn btn-outline-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" id="btnSubmitPhase1">
                    <?= $isEditMode ? 'Update Supplier &amp; Continue to Products (Phase 2)' : 'Save Supplier &amp; Continue to Products (Phase 2)' ?> <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</form>

<script>
window.existingSuppliersData = <?= json_encode($existingSuppliers ?? []) ?>;
window.preselectedSellerId = <?= (int)($selectedSellerId ?? 0) ?>;
</script>
<script src="<?= asset('js/warehouse_supplier_create.js') ?>"></script>

<style>
.fs-8 { font-size: 0.74rem !important; }
.shadow-xs { box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
</style>
