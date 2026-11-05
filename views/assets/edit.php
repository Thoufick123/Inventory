<?php
/**
 * Edit Existing Enterprise Asset View
 */
?>

<div class="w-100" style="max-width: 100%; overflow-x: hidden;">
    <style>
        .asset-form-details > summary {
            cursor: pointer;
            list-style: none;
        }
        .asset-form-details > summary::-webkit-details-marker {
            display: none;
        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <a href="<?= url('asset/view/' . $asset['id']) ?>" class="btn btn-outline-secondary btn-sm rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-arrow-left"></i>
                </a>
                Edit Asset <span class="text-primary font-monospace"><?= e($asset['asset_code']) ?></span>
            </h3>
            <p class="text-muted small mb-0">Update the asset details. Optional information is grouped below.</p>
        </div>
        <div>
            <a href="<?= url('asset/view/' . $asset['id']) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-circle me-1"></i> Cancel
            </a>
        </div>
    </div>

    <!-- Live Toast Container for Quick Add Feedback -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div id="quickActionToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                    <span id="toastMessage">Action completed successfully!</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= url('asset/update/' . $asset['id']) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="row g-4">
            <!-- Left Column: Core Details & Technical Specs -->
            <div class="col-lg-8">
                <!-- Asset details -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-tag text-primary"></i> Asset details
                        </h5>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Asset Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-lg" value="<?= e($asset['name']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-semibold small mb-0">Asset Category <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 small d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#quickAddCategoryModal">
                                        <i class="bi bi-plus-circle"></i> <span>Add Category</span>
                                    </button>
                                </div>
                                <select name="category_id" class="form-select form-select-lg" required id="categorySelector">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" data-dep="<?= $cat['depreciation_rate_annual'] ?>" <?= (int)$asset['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                                            <?= e($cat['name']) ?> (Depreciation: <?= $cat['depreciation_rate_annual'] ?>%/yr)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Operational Unit Pillar <span class="text-danger">*</span></label>
                                <select name="location_type" id="locationTypeSelect" class="form-select" onchange="updateLocationIdOptions()" required>
                                    <option value="PRODUCTION" <?= $asset['location_type'] === 'PRODUCTION' ? 'selected' : '' ?>>🏭 Production Unit</option>
                                    <option value="WAREHOUSE" <?= $asset['location_type'] === 'WAREHOUSE' ? 'selected' : '' ?>>🏢 Central Warehouse</option>
                                    <option value="SHOP" <?= $asset['location_type'] === 'SHOP' ? 'selected' : '' ?>>🛍️ Retail Shop</option>
                                    <option value="OFFICE" <?= $asset['location_type'] === 'OFFICE' ? 'selected' : '' ?>>🏛️ Head Office &amp; Fleet</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-semibold small mb-0">Specific Facility / Store <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 small d-inline-flex align-items-center gap-1" id="btnAddUnitTrigger" onclick="openQuickAddUnitModal()">
                                        <i class="bi bi-plus-circle"></i> <span id="addUnitBtnLabel">Add Unit</span>
                                    </button>
                                </div>
                                <select name="location_id" id="locationIdSelect" class="form-select" required>
                                    <!-- Populated via Javascript -->
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Floor Area / Line / Station</label>
                                <input type="text" name="floor_area" class="form-control" value="<?= e($asset['floor_area']) ?>" placeholder="e.g. Stitching Line 1, Billing Counter 2">
                            </div>
                        </div>
                    </div>
                </div>

                <details class="card border-0 shadow-sm rounded-4 mb-4 bg-white asset-form-details">
                    <summary class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-cpu text-primary"></i> Technical details
                        </h5>
                        <span class="small text-muted">Optional <i class="bi bi-chevron-down ms-1"></i></span>
                    </summary>
                    <div class="card-body p-4 pt-0">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Brand / Manufacturer</label>
                                <input type="text" name="brand" class="form-control" value="<?= e($asset['brand']) ?>" placeholder="e.g. Juki, Epson, Posiflex">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Model Number</label>
                                <input type="text" name="model_number" class="form-control" value="<?= e($asset['model_number']) ?>" placeholder="e.g. DDL-8700, TM-T82III">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Serial Number (SN)</label>
                                <input type="text" name="serial_number" class="form-control font-monospace" value="<?= e($asset['serial_number']) ?>" placeholder="e.g. SN-88392109">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small">Technical Specifications &amp; Capacity</label>
                                <textarea name="specifications" class="form-control" rows="3"><?= e($asset['specifications']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </details>

                <!-- Purchase details -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-cash-stack text-primary"></i> Purchase details
                        </h5>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Purchase Cost (₹) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">₹</span>
                                    <input type="number" step="0.01" name="purchase_cost" class="form-control form-control-lg fw-bold" value="<?= (float)$asset['purchase_cost'] ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Purchase Date</label>
                                <input type="date" name="purchase_date" class="form-control form-control-lg" value="<?= e($asset['purchase_date']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Warranty Expiry Date</label>
                                <input type="date" name="warranty_expiry" class="form-control form-control-lg" value="<?= e($asset['warranty_expiry']) ?>">
                            </div>

                            <details class="col-12 asset-form-details">
                                <summary class="small text-primary">Vendor and depreciation details <i class="bi bi-chevron-down ms-1"></i></summary>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small">Vendor / Supplier Name</label>
                                        <input type="text" name="vendor_name" class="form-control" value="<?= e($asset['vendor_name']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small">Vendor Phone / Contact</label>
                                        <input type="text" name="vendor_contact" class="form-control" value="<?= e($asset['vendor_contact']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small">Purchase Invoice Number</label>
                                        <input type="text" name="invoice_number" class="form-control" value="<?= e($asset['invoice_number']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Estimated Useful Life (Years)</label>
                                        <input type="number" name="useful_life_years" class="form-control" value="<?= (int)$asset['useful_life_years'] ?>" min="1" max="50">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Estimated Salvage / Scrap Value (₹)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">₹</span>
                                            <input type="number" step="0.01" name="salvage_value" class="form-control" value="<?= (float)$asset['salvage_value'] ?>">
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-paperclip text-primary"></i> Asset Attachments
                        </h5>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Asset / Product Image</label>
                                <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">Optional JPG, PNG, or WebP image, up to 10 MB. Uploading a new image replaces the current one.</div>
                                <?php if (!empty($asset['photo_url'])): ?>
                                    <a class="small d-inline-block mt-2" href="<?= e($asset['photo_url']) ?>" target="_blank" rel="noopener">View current asset image</a>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Purchase Invoice / Bill</label>
                                <input type="file" name="invoice_file" class="form-control" accept="application/pdf,image/jpeg,image/png,image/webp">
                                <div class="form-text">Optional PDF or image, up to 10 MB. Uploading a new file replaces the current attachment.</div>
                                <?php if (!empty($asset['invoice_file_url'])): ?>
                                    <a class="small d-inline-block mt-2" href="<?= e($asset['invoice_file_url']) ?>" target="_blank" rel="noopener">View current invoice / bill</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Status, Custodian & Photo -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-activity text-primary"></i> Status
                        </h5>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Operational Status</label>
                            <select name="status" class="form-select">
                                <option value="IN_USE" <?= $asset['status'] === 'IN_USE' ? 'selected' : '' ?>>In Use (Active)</option>
                                <option value="IDLE" <?= $asset['status'] === 'IDLE' ? 'selected' : '' ?>>Idle / In Reserve</option>
                                <option value="IN_MAINTENANCE" <?= $asset['status'] === 'IN_MAINTENANCE' ? 'selected' : '' ?>>In Maintenance</option>
                                <option value="DAMAGED" <?= $asset['status'] === 'DAMAGED' ? 'selected' : '' ?>>Damaged / Breakdown</option>
                                <option value="DISPOSED" <?= $asset['status'] === 'DISPOSED' ? 'selected' : '' ?>>Disposed / Sold</option>
                                <option value="WRITTEN_OFF" <?= $asset['status'] === 'WRITTEN_OFF' ? 'selected' : '' ?>>Written Off</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Physical Condition</label>
                            <select name="condition_state" class="form-select">
                                <option value="EXCELLENT" <?= $asset['condition_state'] === 'EXCELLENT' ? 'selected' : '' ?>>Excellent</option>
                                <option value="GOOD" <?= $asset['condition_state'] === 'GOOD' ? 'selected' : '' ?>>Good</option>
                                <option value="FAIR" <?= $asset['condition_state'] === 'FAIR' ? 'selected' : '' ?>>Fair</option>
                                <option value="POOR" <?= $asset['condition_state'] === 'POOR' ? 'selected' : '' ?>>Poor</option>
                                <option value="CRITICAL" <?= $asset['condition_state'] === 'CRITICAL' ? 'selected' : '' ?>>Critical</option>
                            </select>
                        </div>

                        <details class="asset-form-details">
                            <summary class="small text-primary mb-3">Custodian and notes <i class="bi bi-chevron-down ms-1"></i></summary>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Assigned Custodian / Operator</label>
                                <input type="text" name="custodian_name" class="form-control" value="<?= e($asset['custodian_name']) ?>">
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-semibold small">Internal Remarks / Notes</label>
                                <textarea name="notes" class="form-control" rows="3"><?= e($asset['notes']) ?></textarea>
                            </div>
                        </details>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 bg-primary text-white">
                    <div class="card-body p-4 text-center">
                        <button type="submit" class="btn btn-light btn-lg w-100 fw-bold shadow-sm text-primary">
                            <i class="bi bi-check-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DYNAMIC ADD NEW ASSET CATEGORY                                     -->
<!-- ========================================================================= -->
<div class="modal fade" id="quickAddCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-tags me-2"></i> Add New Asset Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Category Name <span class="text-danger">*</span></label>
                    <input type="text" id="new_cat_name" class="form-control" placeholder="e.g. Boiler & Thermal Systems, Embroidery Units" required oninput="autoSuggestCategoryCode(this.value)">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Category Code</label>
                        <input type="text" id="new_cat_code" class="form-control font-monospace" placeholder="e.g. BOILER">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Depreciation Rate (%/Yr)</label>
                        <div class="input-group">
                            <input type="number" step="0.5" id="new_cat_dep_rate" class="form-control" value="15.00">
                            <span class="input-group-text bg-light">%</span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Category Icon</label>
                    <select id="new_cat_icon" class="form-select">
                        <option value="bi-cpu">💻 bi-cpu (IT, Electronics, POS)</option>
                        <option value="bi-gear-wide-connected">⚙️ bi-gear-wide-connected (Machinery &amp; Plant)</option>
                        <option value="bi-scissors">✂️ bi-scissors (Garment, Cutting, Sewing)</option>
                        <option value="bi-truck">🚚 bi-truck (Vehicles &amp; Fleet Logistics)</option>
                        <option value="bi-box-seam">📦 bi-box-seam (Material Handling &amp; Racks)</option>
                        <option value="bi-lightning-charge">⚡ bi-lightning-charge (Electrical &amp; Power/HVAC)</option>
                        <option value="bi-fan">🌀 bi-fan (Boilers, Compressors, Cooling)</option>
                        <option value="bi-display">🖥️ bi-display (Office Workstations &amp; Displays)</option>
                        <option value="bi-shield-check">🛡️ bi-shield-check (Safety &amp; Security Systems)</option>
                        <option value="bi-tools">🔧 bi-tools (Tools &amp; General Equipment)</option>
                    </select>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold small">Description / Notes (Optional)</label>
                    <textarea id="new_cat_desc" class="form-control" rows="2" placeholder="Brief note on asset types in this category..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4 fw-semibold" id="btnSubmitCategory" onclick="submitQuickCategory()">
                    <span id="btnSubmitCatText"><i class="bi bi-check-circle me-1"></i> Save &amp; Select Category</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DYNAMIC ADD NEW OPERATIONAL UNIT (FACILITY / STORE)                -->
<!-- ========================================================================= -->
<div class="modal fade" id="quickAddUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="quickUnitModalTitle"><i class="bi bi-building me-2"></i> Add New Operational Unit</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Facility / Unit Type <span class="text-danger">*</span></label>
                    <select id="new_unit_type" class="form-select" onchange="onUnitTypeChanged()">
                        <option value="PRODUCTION">🏭 Production Unit</option>
                        <option value="WAREHOUSE">🏢 Central Warehouse</option>
                        <option value="SHOP">🛍️ Retail Shop</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Facility / Store Name <span class="text-danger">*</span></label>
                    <input type="text" id="new_unit_name" class="form-control" placeholder="e.g. PU 2 - Avinashi Dyeing & Spinning Unit" required oninput="autoSuggestUnitCode(this.value)">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Unit Code <span class="text-danger">*</span></label>
                        <input type="text" id="new_unit_code" class="form-control font-monospace" placeholder="e.g. PU2, WH2, SH2">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Contact Phone</label>
                        <input type="text" id="new_unit_phone" class="form-control" placeholder="e.g. 9842100000">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Unit In-Charge / Manager Name</label>
                    <input type="text" id="new_unit_manager" class="form-control" placeholder="e.g. K. Selvaraj (Plant Manager)">
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold small">Address / Physical Location</label>
                    <textarea id="new_unit_address" class="form-control" rows="2" placeholder="Street, Industrial Estate, City..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4 fw-semibold text-white" id="btnSubmitUnit" onclick="submitQuickUnit()">
                    <span id="btnSubmitUnitText"><i class="bi bi-check-circle me-1"></i> Save &amp; Select Facility</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const unitsMap = <?= json_encode($unitsMap) ?>;
const currentLocationId = <?= (int)$asset['location_id'] ?>;

function updateLocationIdOptions() {
    const locType = document.getElementById('locationTypeSelect').value;
    const select = document.getElementById('locationIdSelect');
    const addBtn = document.getElementById('btnAddUnitTrigger');
    const addLabel = document.getElementById('addUnitBtnLabel');
    select.innerHTML = '';

    if (locType === 'OFFICE') {
        addBtn.style.display = 'none';
    } else {
        addBtn.style.display = 'inline-flex';
        if (locType === 'PRODUCTION') addLabel.textContent = 'Add PU';
        else if (locType === 'WAREHOUSE') addLabel.textContent = 'Add WH';
        else if (locType === 'SHOP') addLabel.textContent = 'Add Shop';
    }

    let items = [];
    if (locType === 'PRODUCTION') items = unitsMap.production || [];
    else if (locType === 'WAREHOUSE') items = unitsMap.warehouses || [];
    else if (locType === 'SHOP') items = unitsMap.shops || [];
    else if (locType === 'OFFICE') items = unitsMap.office || [];

    items.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = (item.code ? `[${item.code}] ` : '') + item.name;
        if (parseInt(item.id) === currentLocationId && locType === '<?= $asset['location_type'] ?>') {
            opt.selected = true;
        }
        select.appendChild(opt);
    });
}

/**
 * Auto-suggest Category Code
 */
function autoSuggestCategoryCode(name) {
    const codeInput = document.getElementById('new_cat_code');
    if (!codeInput.dataset.touched) {
        const clean = name.toUpperCase().replace(/[^A-Z0-9]/g, '_').substring(0, 10).replace(/_+$/, '');
        codeInput.value = clean;
    }
}
document.getElementById('new_cat_code')?.addEventListener('input', function() {
    this.dataset.touched = "true";
});

/**
 * Submit Dynamic Category via AJAX
 */
function submitQuickCategory() {
    const name = document.getElementById('new_cat_name').value.trim();
    if (!name) {
        alert('Please enter a Category Name.');
        document.getElementById('new_cat_name').focus();
        return;
    }

    const code = document.getElementById('new_cat_code').value.trim();
    const depRate = parseFloat(document.getElementById('new_cat_dep_rate').value) || 15.0;
    const icon = document.getElementById('new_cat_icon').value;
    const desc = document.getElementById('new_cat_desc').value.trim();

    const btn = document.getElementById('btnSubmitCategory');
    const btnText = document.getElementById('btnSubmitCatText');
    btn.disabled = true;
    btnText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    fetch('<?= url('api/asset/category/create') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            name: name,
            code: code,
            depreciation_rate_annual: depRate,
            icon: icon,
            description: desc
        })
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btnText.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save &amp; Select Category';

        if (data.success && data.category) {
            const cat = data.category;
            const select = document.getElementById('categorySelector');
            
            // Add new option and select it
            const opt = document.createElement('option');
            opt.value = cat.id;
            opt.dataset.dep = cat.depreciation_rate_annual;
            opt.textContent = `${cat.name} (Depreciation: ${cat.depreciation_rate_annual}%/yr)`;
            opt.selected = true;
            select.appendChild(opt);

            // Close modal
            const modalEl = document.getElementById('quickAddCategoryModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            // Reset modal form
            document.getElementById('new_cat_name').value = '';
            document.getElementById('new_cat_code').value = '';
            document.getElementById('new_cat_desc').value = '';

            // Flash toast notification
            showQuickToast(`Category "${cat.name}" added and selected!`);
        } else {
            alert(data.message || 'Failed to create category.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btnText.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save &amp; Select Category';
        alert('Server communication error.');
    });
}

/**
 * Open Quick Add Unit Modal pre-customized to selected Pillar
 */
function openQuickAddUnitModal() {
    const locType = document.getElementById('locationTypeSelect').value;
    const unitTypeSelect = document.getElementById('new_unit_type');
    unitTypeSelect.value = (locType === 'OFFICE') ? 'PRODUCTION' : locType;
    onUnitTypeChanged();

    const modal = new bootstrap.Modal(document.getElementById('quickAddUnitModal'));
    modal.show();
}

function onUnitTypeChanged() {
    const type = document.getElementById('new_unit_type').value;
    const title = document.getElementById('quickUnitModalTitle');
    const nameInput = document.getElementById('new_unit_name');
    const codeInput = document.getElementById('new_unit_code');

    if (type === 'PRODUCTION') {
        title.innerHTML = '<i class="bi bi-gear-fill text-primary me-2"></i> Add New Production Unit (PU)';
        nameInput.placeholder = 'e.g. PU 2 - Tirupur Weaving & Processing Plant';
        if (!codeInput.dataset.touched) codeInput.placeholder = 'e.g. PU2';
    } else if (type === 'WAREHOUSE') {
        title.innerHTML = '<i class="bi bi-building-fill text-info me-2"></i> Add New Central Warehouse (WH)';
        nameInput.placeholder = 'e.g. WH 2 - Coimbatore Logistics Terminal';
        if (!codeInput.dataset.touched) codeInput.placeholder = 'e.g. WH2';
    } else if (type === 'SHOP') {
        title.innerHTML = '<i class="bi bi-shop-window text-success me-2"></i> Add New Retail Shop (SH)';
        nameInput.placeholder = 'e.g. SH 2 - Madurai Retail Showroom';
        if (!codeInput.dataset.touched) codeInput.placeholder = 'e.g. SH2';
    }
}

function autoSuggestUnitCode(name) {
    const type = document.getElementById('new_unit_type').value;
    const codeInput = document.getElementById('new_unit_code');
    if (!codeInput.dataset.touched) {
        let prefix = type === 'PRODUCTION' ? 'PU' : (type === 'WAREHOUSE' ? 'WH' : 'SH');
        const match = name.match(/\d+/);
        codeInput.value = match ? `${prefix}${match[0]}` : prefix;
    }
}
document.getElementById('new_unit_code')?.addEventListener('input', function() {
    this.dataset.touched = "true";
});

/**
 * Submit Dynamic Operational Unit via AJAX
 */
function submitQuickUnit() {
    const type = document.getElementById('new_unit_type').value;
    const name = document.getElementById('new_unit_name').value.trim();
    if (!name) {
        alert('Please enter a Facility / Store Name.');
        document.getElementById('new_unit_name').focus();
        return;
    }

    const code = document.getElementById('new_unit_code').value.trim();
    const phone = document.getElementById('new_unit_phone').value.trim();
    const manager = document.getElementById('new_unit_manager').value.trim();
    const address = document.getElementById('new_unit_address').value.trim();

    const btn = document.getElementById('btnSubmitUnit');
    const btnText = document.getElementById('btnSubmitUnitText');
    btn.disabled = true;
    btnText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    fetch('<?= url('api/asset/unit/create') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            type: type,
            name: name,
            code: code,
            phone: phone,
            manager_name: manager,
            address: address
        })
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btnText.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save &amp; Select Facility';

        if (data.success && data.unit) {
            const u = data.unit;

            if (type === 'PRODUCTION') {
                if (!unitsMap.production) unitsMap.production = [];
                unitsMap.production.push(u);
            } else if (type === 'WAREHOUSE') {
                if (!unitsMap.warehouses) unitsMap.warehouses = [];
                unitsMap.warehouses.push(u);
            } else if (type === 'SHOP') {
                if (!unitsMap.shops) unitsMap.shops = [];
                unitsMap.shops.push(u);
            }

            document.getElementById('locationTypeSelect').value = type;
            updateLocationIdOptions();
            document.getElementById('locationIdSelect').value = u.id;

            const modalEl = document.getElementById('quickAddUnitModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            document.getElementById('new_unit_name').value = '';
            document.getElementById('new_unit_code').value = '';
            document.getElementById('new_unit_phone').value = '';
            document.getElementById('new_unit_manager').value = '';
            document.getElementById('new_unit_address').value = '';

            showQuickToast(`Facility "${u.name}" created and selected!`);
        } else {
            alert(data.message || 'Failed to create facility.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btnText.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save &amp; Select Facility';
        alert('Server communication error.');
    });
}

function showQuickToast(msg) {
    document.getElementById('toastMessage').textContent = msg;
    const toastEl = document.getElementById('quickActionToast');
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
}

document.addEventListener('DOMContentLoaded', () => {
    updateLocationIdOptions();
});
</script>
