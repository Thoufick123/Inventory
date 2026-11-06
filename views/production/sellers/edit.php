<div class="page-header mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h2><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Material Supplier</h2>
            <span class="text-muted">Update supplier details, fabric roll specifications, bill attachments, and payment terms</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="<?= url('production/sellers') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Suppliers
            </a>
        </div>
    </div>
</div>

<form method="POST" action="<?= url('production/sellers/update/' . $seller['id']) ?>" enctype="multipart/form-data" id="supplierEditForm" class="needs-validation" novalidate>
    <?= CSRF::field() ?>

    <div class="row g-4">
        <!-- Supplier, Material, and Document Details -->
        <div class="col-12">
            <!-- 1. Supplier Basic Information -->
            <div class="card shadow-sm border mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bi bi-person-vcard me-2 text-primary"></i>Supplier Profile — <?= e($seller['name']) ?>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <!-- Contact Person -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Contact Person / Supplier Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="name" 
                                   id="supplierNameInput"
                                   class="form-control text-uppercase" 
                                   style="text-transform: uppercase;"
                                   required 
                                   minlength="2" 
                                   maxlength="200" 
                                   placeholder="E.G. RAMESH KUMAR"
                                   value="<?= e($seller['name']) ?>">
                            <div class="invalid-feedback" id="nameError">Contact person name is required and cannot contain numbers.</div>
                            <div class="valid-feedback">Looks good!</div>
                        </div>

                        <!-- Company Name -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Company / Mill / Business Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="company" 
                                   id="companyNameInput"
                                   class="form-control text-uppercase" 
                                   style="text-transform: uppercase;"
                                   required
                                   minlength="2"
                                   maxlength="300" 
                                   placeholder="E.G. TIRUPUR COTTON MILLS PVT LTD"
                                   value="<?= e($seller['company']) ?>">
                            <div class="invalid-feedback" id="companyError">Company name is required and cannot contain numbers.</div>
                            <div class="valid-feedback">Looks good!</div>
                        </div>

                        <!-- Phone Number -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Phone Number <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                <input type="tel" 
                                       name="phone" 
                                       id="phoneInput"
                                       class="form-control" 
                                       maxlength="10" 
                                       minlength="10" 
                                       inputmode="numeric"
                                       required
                                       placeholder="e.g. 9876543210"
                                       value="<?= e($seller['phone']) ?>">
                                <div class="invalid-feedback" id="phoneError">Phone number must be exactly 10 digits.</div>
                                <div class="valid-feedback">Valid 10-digit number!</div>
                            </div>
                            <div class="form-text small">Enter exact 10-digit contact number.</div>
                        </div>

                        <!-- Email Address -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address <span class="text-muted">(Optional)</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" 
                                       name="email" 
                                       id="emailInput"
                                       class="form-control" 
                                       maxlength="255" 
                                       placeholder="e.g. sales@grm.in or supplier@gmail.com"
                                       value="<?= e($seller['email']) ?>">
                                <div class="invalid-feedback" id="emailError">Please enter a valid email address (e.g. name@domain.in or name@gmail.com).</div>
                                <div class="valid-feedback">Looks good!</div>
                            </div>
                            <div class="form-text small">Business or personal email address (e.g. supplier@domain.in, name@gmail.com).</div>
                        </div>

                        <!-- GST Number -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">GSTIN / Tax ID</label>
                            <input type="text" 
                                   name="gst_number" 
                                   id="gstInput"
                                   class="form-control text-uppercase" 
                                   maxlength="15" 
                                   placeholder="E.G. 33AAAAA0000A1Z5"
                                   value="<?= e($seller['gst_number']) ?>"
                                   style="letter-spacing: 1px; text-transform: uppercase;">
                            <div class="invalid-feedback" id="gstError">GSTIN must be a valid 15-character alphanumeric code.</div>
                            <div class="form-text small">Optional: 15-character alphanumeric GST registration code.</div>
                        </div>

                        <!-- City -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">City / District <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="city" 
                                   id="cityInput"
                                   list="tnDistrictsList"
                                   class="form-control text-uppercase" 
                                   style="text-transform: uppercase;"
                                   maxlength="100" 
                                   required
                                   placeholder="E.G. TIRUPPUR / COIMBATORE / SURAT"
                                   value="<?= e($seller['city']) ?>">
                            <datalist id="tnDistrictsList">
                                <option value="ARIYALUR">
                                <option value="CHENGALPATTU">
                                <option value="CHENNAI">
                                <option value="COIMBATORE">
                                <option value="CUDDALORE">
                                <option value="DHARMAPURI">
                                <option value="DINDIGUL">
                                <option value="ERODE">
                                <option value="KALLAKURICHI">
                                <option value="KANCHIPURAM">
                                <option value="KANYAKUMARI">
                                <option value="KARUR">
                                <option value="KRISHNAGIRI">
                                <option value="MADURAI">
                                <option value="MAYILADUTHURAI">
                                <option value="NAGAPATTINAM">
                                <option value="NAMAKKAL">
                                <option value="NILGIRIS">
                                <option value="PERAMBALUR">
                                <option value="PUDUKKOTTAI">
                                <option value="RAMANATHAPURAM">
                                <option value="RANIPET">
                                <option value="SALEM">
                                <option value="SIVAGANGA">
                                <option value="TENKASI">
                                <option value="THANJAVUR">
                                <option value="THENI">
                                <option value="THOOTHUKUDI">
                                <option value="TIRUCHIRAPPALLI">
                                <option value="TIRUNELVELI">
                                <option value="TIRUPATTUR">
                                <option value="TIRUPPUR">
                                <option value="TIRUVALLUR">
                                <option value="TIRUVANNAMALAI">
                                <option value="TIRUVARUR">
                                <option value="VELLORE">
                                <option value="VILUPPURAM">
                                <option value="VIRUDHUNAGAR">
                            </datalist>
                            <div class="invalid-feedback" id="cityError">City / District is required and cannot contain numbers.</div>
                            <div class="form-text small" id="cityHint">Select Tamil Nadu district or enter other state city.</div>
                        </div>

                        <!-- State -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">State <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="state" 
                                   id="stateInput"
                                   class="form-control text-uppercase" 
                                   style="text-transform: uppercase;"
                                   maxlength="100" 
                                   required
                                   placeholder="E.G. TAMIL NADU / GUJARAT / MAHARASHTRA"
                                   value="<?= e($seller['state']) ?>">
                            <div class="invalid-feedback" id="stateError">State is required and cannot contain numbers.</div>
                        </div>

                        <!-- Full Factory / Mill Address -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Full Factory / Mill Address <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="address" 
                                   id="addressInput"
                                   class="form-control text-uppercase" 
                                   style="text-transform: uppercase;"
                                   maxlength="500" 
                                   required
                                   placeholder="PLOT NO., INDUSTRIAL ESTATE, LANDMARK..."
                                   value="<?= e($seller['address']) ?>">
                            <div class="invalid-feedback" id="addressError">Please enter full factory / mill address.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Fabric Roll & Material Supply Specifications (Dynamic Multi-Roll) -->
            <div class="card shadow-sm border mb-4">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="bi bi-layers-half me-2 text-primary"></i>Fabric Roll & Material Supply Details
                        </h5>
                        <small class="text-muted">Manage individual rolls with photos, colors, design names, and design counts (All roll fields are required)</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary shadow-sm" onclick="addRollCard()">
                        <i class="bi bi-plus-circle me-1"></i> Add Fabric Roll
                    </button>
                </div>
                <div class="card-body p-4">
                    <!-- General Fabric Description -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold required">Fabric / Material Description <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="material_type" 
                                   id="materialTypeInput"
                                   class="form-control text-uppercase" 
                                   style="text-transform: uppercase;"
                                   maxlength="150" 
                                   required
                                   placeholder="E.G. 100% COTTON 30S SINGLE JERSEY, 240 GSM FRENCH TERRY" 
                                   value="<?= e($seller['material_type'] ?? '') ?>"
                                   list="materialTypeSuggestions">
                            <datalist id="materialTypeSuggestions">
                                <option value="100% COMBED COTTON 30S SINGLE JERSEY (160 GSM)">
                                <option value="100% COTTON 24S SINGLE JERSEY (180 GSM)">
                                <option value="95% COTTON 5% SPANDEX/LYCRA (190 GSM)">
                                <option value="100% COTTON FRENCH TERRY (240 GSM)">
                                <option value="100% COTTON 3-THREAD FLEECE (320 GSM)">
                                <option value="COTTON-POLYESTER BLEND 60/40 (180 GSM)">
                                <option value="100% COTTON PIQUE POLO KNIT (220 GSM)">
                                <option value="100% COTTON 2X2 RIB KNIT (220 GSM)">
                                <option value="DENIM TWILL 12OZ INDIGO">
                                <option value="RAYON / VISCOSE PRINTED FABRIC">
                            </datalist>
                            <div class="invalid-feedback" id="materialTypeError">Fabric / Material description is required.</div>
                            <div class="form-text small">Select from standard fabric grades or custom name & GSM.</div>
                        </div>
                    </div>

                    <!-- Dynamic Multiple Rolls Container -->
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-stack me-1 text-primary"></i>Individual Fabric Rolls (<span id="rollCountBadge">0</span>)
                        </h6>
                        <span class="text-muted small">Click <strong>+ Add Fabric Roll</strong> to add or delete individual roll records</span>
                    </div>

                    <div id="rollsContainer" class="d-flex flex-column gap-3 mb-4">
                        <!-- Dynamic Roll Cards will be rendered here via JS / PHP data -->
                    </div>

                    <!-- Live Roll Details & Stock Allocation Table -->
                    <div class="card border shadow-sm mb-4" id="rollDetailsTableContainer">
                        <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-dark small">
                                <i class="bi bi-table me-1 text-primary"></i>Fabric Rolls Inventory & Allocation Details Table
                            </span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary-subtle text-secondary border small" id="tableRollCountBadge">0 Rolls</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle mb-0" id="rollDetailsSummaryTable" style="font-size: 13px;">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th style="width: 45px;" class="text-center">#</th>
                                        <th style="width: 60px;">Photo</th>
                                        <th>Roll Code</th>
                                        <th>Color / Shade</th>
                                        <th>Design / Pattern</th>
                                        <th class="text-center">Designs</th>
                                        <th class="text-end">Total RQ</th>
                                        <th class="text-end">Total Wgt</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end text-danger bg-danger-subtle bg-opacity-25">Damages</th>
                                        <th class="text-end bg-light-subtle text-muted">Used Stock</th>
                                        <th class="text-end bg-success-subtle fw-bold text-success">Available</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="rollDetailsTableBody">
                                    <!-- Dynamic rows rendered via JS -->
                                </tbody>
                                <tfoot id="rollDetailsTableFoot" class="table-light fw-bold"></tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Empty state if all rolls deleted -->
                    <div id="emptyRollsState" class="text-center py-4 bg-light rounded border d-none mb-4">
                        <i class="bi bi-stack fs-3 text-secondary d-block mb-1"></i>
                        <p class="text-muted small mb-2">No rolls in this lot. Click below to add rolls.</p>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addRollCard()">
                            <i class="bi bi-plus-circle me-1"></i> Add First Roll
                        </button>
                    </div>

                    <!-- Calculated Batch Metrics Summary Banner -->
                    <div class="p-3 bg-primary-subtle rounded border border-primary-subtle d-flex flex-wrap align-items-center justify-content-between gap-3" id="metricsBanner">
                        <div>
                            <div class="fw-bold text-primary"><i class="bi bi-calculator me-1"></i>Lot Metrics & Available Stock Summary</div>
                            <small class="text-muted">Live calculations based on current roll list minus consumed allocation</small>
                        </div>
                        <div class="d-flex flex-wrap gap-4">
                            <div>
                                <span class="text-muted small d-block">Total Rolls:</span>
                                <span class="fw-bold fs-6 text-dark" id="summaryTotalRolls">0 Rolls</span>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Total RQ:</span>
                                <span class="fw-bold fs-6 text-primary" id="summaryTotalRQ">0 RQ</span>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Colors / Designs:</span>
                                <span class="fw-bold fs-6 text-dark" id="summaryColorsDesigns">0 Colors • 0 Designs</span>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Total Weight:</span>
                                <span class="fw-bold fs-6 text-dark" id="summaryTotalWeight">0.00 kg</span>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Total Lot Value:</span>
                                <span class="fw-bold fs-6 text-success" id="summaryTotalPrice">₹ 0.00</span>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Available Stock:</span>
                                <span class="fw-bold fs-6 text-primary" id="summaryAvailableStock">—</span>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Rate / KG:</span>
                                <span class="fw-bold fs-6 text-success" id="summaryRatePerKg">— ₹/kg</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Bill / Invoice / Document Upload -->
            <div class="card shadow-sm border mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bi bi-file-earmark-arrow-up me-2 text-primary"></i>Bill / Invoice & Verification Documents <span class="text-danger">*</span>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="p-3 bg-light rounded border">
                        <label class="form-label fw-semibold d-flex align-items-center justify-content-between mb-1">
                            <span><i class="bi bi-paperclip me-1 text-danger"></i>Bill / Invoice / Document Attachment <span class="text-danger">*</span></span>
                            <?php if (!empty($seller['document_url'])): ?>
                                <span class="badge bg-success-subtle text-success border"><i class="bi bi-check2-circle me-1"></i>Current File Attached</span>
                            <?php else: ?>
                                <span class="badge bg-danger text-white border">Required (Max 5MB)</span>
                            <?php endif; ?>
                        </label>

                        <?php if (!empty($seller['document_url'])): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 mb-2 bg-white rounded border">
                                <div class="d-flex align-items-center gap-3">
                                    <a href="<?= uploadUrl($seller['document_url']) ?>" target="_blank" class="flex-shrink-0">
                                        <img src="<?= uploadUrl($seller['document_url']) ?>" alt="Bill" class="rounded border shadow-sm" style="width: 44px; height: 44px; object-fit: cover;" onerror="this.parentElement.style.display='none'">
                                    </a>
                                    <i class="bi bi-file-earmark-check fs-4 text-success"></i>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <a href="<?= url('production/sellers/download-bill/' . $seller['id']) ?>" class="small text-decoration-none text-primary fw-semibold" download>
                                                <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Bill
                                            </a>
                                            <span class="text-muted small">•</span>
                                            <a href="<?= uploadUrl($seller['document_url']) ?>" target="_blank" class="small text-muted text-decoration-none">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> View
                                            </a>
                                        </div>
                                </div>
                                <span class="badge bg-success-subtle text-success border">Attached</span>
                            </div>
                        <?php endif; ?>

                        <input type="file" 
                               name="document" 
                               id="documentInput" 
                               class="form-control" 
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               <?= empty($seller['document_url']) ? 'required' : '' ?>
                               onchange="previewDocument(this)">
                        <div class="form-text small mt-1">
                            <?= !empty($seller['document_url']) ? 'Upload new file to replace current attachment (JPG, JPEG, PNG, WEBP, PDF up to 5MB).' : 'Supported formats: <strong>JPG, JPEG, PNG, WEBP, PDF</strong>. Upload supplier invoice, mill test report, or GST bill.' ?>
                        </div>
                        <div id="docPreviewContainer" class="mt-2 d-none">
                            <div class="d-flex align-items-center gap-3 p-2 bg-white rounded border">
                                <div id="previewThumbnail"></div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold small" id="previewFileName"></div>
                                    <small class="text-muted" id="previewFileSize"></small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearDocumentUpload()">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm border h-100">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="isActiveSwitch" name="is_active" value="1" <?= $seller['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-dark ms-2" for="isActiveSwitch">
                                    Vendor is Active for Purchase Orders
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm border h-100">
                        <div class="card-body p-4 d-flex align-items-center justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm" id="submitBtn">
                                <i class="bi bi-check2-circle me-1"></i> Update Supplier
                            </button>
                            <a href="<?= url('production/sellers') ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
const existingRollsData = <?= json_encode($rolls ?? []) ?>;
let rollCounter = 0;

// Tamil Nadu Districts List for Smart Validation & Auto-Fill
const TN_DISTRICTS = [
    'ARIYALUR', 'CHENGALPATTU', 'CHENNAI', 'COIMBATORE', 'CUDDALORE',
    'DHARMAPURI', 'DINDIGUL', 'ERODE', 'KALLAKURICHI', 'KANCHIPURAM',
    'KANYAKUMARI', 'KARUR', 'KRISHNAGIRI', 'MADURAI', 'MAYILADUTHURAI',
    'NAGAPATTINAM', 'NAMAKKAL', 'NILGIRIS', 'PERAMBALUR', 'PUDUKKOTTAI',
    'RAMANATHAPURAM', 'RANIPET', 'SALEM', 'SIVAGANGA', 'TENKASI',
    'THANJAVUR', 'THENI', 'THOOTHUKUDI', 'TIRUCHIRAPPALLI', 'TRICHY',
    'TIRUNELVELI', 'TIRUPATTUR', 'TIRUPPUR', 'TIRUVALLUR', 'TIRUVANNAMALAI',
    'TIRUVARUR', 'VELLORE', 'VILUPPURAM', 'VILLUPURAM', 'VIRUDHUNAGAR'
];

function getUploadUrl(path) {
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    const clean = path.replace(/^\/+/, '');
    const finalPath = clean.startsWith('public/') ? clean : 'public/' + clean;
    const baseUrl = '<?= rtrim(env('APP_URL', ''), '/') ?>';
    return baseUrl + '/' + finalPath;
}

// Validation Helper Functions
function setFieldValid(input, feedbackEl, msg = '') {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    if (feedbackEl) {
        feedbackEl.innerText = msg;
    }
}

function setFieldInvalid(input, feedbackEl, msg) {
    input.classList.remove('is-valid');
    input.classList.add('is-invalid');
    if (feedbackEl) {
        feedbackEl.innerText = msg;
    }
}

function validateName(input) {
    const val = input.value.trim();
    const errEl = document.getElementById('nameError');
    if (val === '') {
        setFieldInvalid(input, errEl, 'Contact Person / Supplier Name is required.');
        return false;
    }
    if (/\d/.test(val)) {
        setFieldInvalid(input, errEl, 'Contact Person / Supplier Name cannot contain numbers.');
        return false;
    }
    if (val.length < 2) {
        setFieldInvalid(input, errEl, 'Name must be at least 2 characters long.');
        return false;
    }
    setFieldValid(input, errEl);
    return true;
}

function validateCompany(input) {
    const val = input.value.trim();
    const errEl = document.getElementById('companyError');
    if (val === '') {
        setFieldInvalid(input, errEl, 'Company / Mill Name is required.');
        return false;
    }
    if (/\d/.test(val)) {
        setFieldInvalid(input, errEl, 'Company / Mill Name cannot contain numbers.');
        return false;
    }
    if (val.length < 2) {
        setFieldInvalid(input, errEl, 'Company name must be at least 2 characters long.');
        return false;
    }
    setFieldValid(input, errEl);
    return true;
}

function validatePhone(input) {
    input.value = input.value.replace(/\D/g, '').slice(0, 10);
    const val = input.value.trim();
    const errEl = document.getElementById('phoneError');
    if (val === '') {
        setFieldInvalid(input, errEl, 'Phone number is required.');
        return false;
    }
    if (!/^\d{10}$/.test(val)) {
        setFieldInvalid(input, errEl, 'Phone number must be exactly 10 digits (e.g. 9876543210).');
        return false;
    }
    setFieldValid(input, errEl);
    return true;
}

function validateEmail(input) {
    const val = input.value.trim();
    const errEl = document.getElementById('emailError');
    if (val === '') {
        input.classList.remove('is-invalid', 'is-valid');
        return true;
    }
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailRegex.test(val)) {
        setFieldInvalid(input, errEl, 'Please enter a valid email address (e.g. name@domain.in or name@gmail.com).');
        return false;
    }
    setFieldValid(input, errEl);
    return true;
}

function validateGst(input) {
    const val = input.value.trim();
    const errEl = document.getElementById('gstError');
    if (val === '') {
        input.classList.remove('is-invalid', 'is-valid');
        return true;
    }
    const gstRegex = /^[0-9A-Za-z]{15}$/;
    if (!gstRegex.test(val)) {
        setFieldInvalid(input, errEl, 'GSTIN must be a 15-character alphanumeric code.');
        return false;
    }
    setFieldValid(input, errEl);
    return true;
}

function validateCity(input) {
    const val = input.value.trim().toUpperCase();
    const errEl = document.getElementById('cityError');
    const stateInput = document.getElementById('stateInput');
    const cityHint = document.getElementById('cityHint');

    if (val === '') {
        setFieldInvalid(input, errEl, 'City / District is required.');
        return false;
    }
    if (/\d/.test(val)) {
        setFieldInvalid(input, errEl, 'City / District cannot contain numbers.');
        return false;
    }

    if (TN_DISTRICTS.includes(val)) {
        if (!stateInput.value || stateInput.value.trim().toUpperCase() !== 'TAMIL NADU') {
            stateInput.value = 'TAMIL NADU';
            validateState(stateInput);
        }
        if (cityHint) cityHint.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Tamil Nadu district recognized (State auto-set).</span>';
    } else {
        if (cityHint) cityHint.innerHTML = '<span class="text-warning"><i class="bi bi-info-circle me-1"></i>District is outside Tamil Nadu — please specify State below.</span>';
        if (stateInput.value.trim().toUpperCase() === 'TAMIL NADU') {
            stateInput.value = '';
            validateState(stateInput);
        }
    }

    setFieldValid(input, errEl);
    return true;
}

function validateState(input) {
    const val = input.value.trim();
    const errEl = document.getElementById('stateError');
    if (val === '') {
        setFieldInvalid(input, errEl, 'State is required.');
        return false;
    }
    if (/\d/.test(val)) {
        setFieldInvalid(input, errEl, 'State cannot contain numbers.');
        return false;
    }
    setFieldValid(input, errEl);
    return true;
}

function validateAddress(input) {
    const val = input.value.trim();
    const errEl = document.getElementById('addressError');
    if (val === '') {
        setFieldInvalid(input, errEl, 'Full Factory / Mill Address is required.');
        return false;
    }
    setFieldValid(input, errEl);
    return true;
}

function validateMaterialType(input) {
    const val = input.value.trim();
    const errEl = document.getElementById('materialTypeError');
    if (val === '') {
        setFieldInvalid(input, errEl, 'Fabric / Material description is required.');
        return false;
    }
    setFieldValid(input, errEl);
    return true;
}

function validateRollInput(input) {
    const val = input.value.trim();
    const type = input.type;

    if (type === 'number') {
        const num = parseFloat(val);
        const min = parseFloat(input.min) || 0;
        if (isNaN(num) || num < min || (min === 0.01 && num <= 0) || (input.required && val === '')) {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            return false;
        }
    } else {
        if (input.required && val === '') {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            return false;
        }
    }
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    return true;
}

function addRollCard(data = {}) {
    const container = document.getElementById('rollsContainer');
    const emptyState = document.getElementById('emptyRollsState');
    emptyState.classList.add('d-none');

    const index = rollCounter++;
    const rollId = data.id || '';
    const rollNumber = data.roll_number || ('ROLL #' + (container.children.length + 1));
    const color = data.color || '';
    const designName = data.design_name || '';
    const designCount = data.design_count !== undefined ? data.design_count : '1';
    const quantity = data.quantity !== undefined ? data.quantity : (data.qty !== undefined ? data.qty : '1');
    const weight = data.weight !== undefined ? data.weight : '';
    const price = data.price !== undefined ? data.price : '';
    const existingPhoto = data.photo_url || '';
    const usedQuantity = data.used_quantity !== undefined ? parseInt(data.used_quantity) : 0;
    const usedWeight = data.used_weight !== undefined ? parseFloat(data.used_weight) : 0;
    const usedPrice = data.used_price !== undefined ? parseFloat(data.used_price) : 0;
    const damagedQuantity = data.damaged_quantity !== undefined ? parseInt(data.damaged_quantity) : 0;
    const damagedWeight = data.damaged_weight !== undefined ? parseFloat(data.damaged_weight) : 0;
    const damagedPrice = data.damaged_price !== undefined ? parseFloat(data.damaged_price) : 0;

    const card = document.createElement('div');
    card.className = 'card border shadow-sm roll-card';
    card.id = 'roll_card_' + index;
    card.dataset.index = index;
    card.dataset.rollId = rollId;
    card.dataset.photoUrl = existingPhoto;
    card.dataset.usedQty = usedQuantity;
    card.dataset.usedWeight = usedWeight;
    card.dataset.usedPrice = usedPrice;
    card.dataset.damagedQty = damagedQuantity;
    card.dataset.damagedWeight = damagedWeight;
    card.dataset.damagedPrice = damagedPrice;

    card.dataset.startCode = data.start_code || '';
    card.dataset.endCode = data.end_code || '';
    card.dataset.startSeq = data.start_seq || '';
    card.dataset.endSeq = data.end_seq || '';

    const existingCodeText = data.start_code ? (data.start_code + (data.end_code && data.end_code !== data.start_code ? '–' + data.end_code : '')) : '';

    let photoHtml = '';
    if (existingPhoto) {
        photoHtml = `
            <div class="d-flex align-items-center gap-2 mb-2 p-1 bg-white rounded border">
                <img src="${getUploadUrl(existingPhoto)}" alt="Roll Photo" class="rounded border" style="width: 44px; height: 44px; object-fit: cover;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'44\\' height=\\'44\\' viewBox=\\'0 0 44 44\\'%3E%3Crect width=\\'44\\' height=\\'44\\' fill=\\'%23e9ecef\\'/%3E%3Ctext x=\\'50%25\\' y=\\'50%25\\' dominant-baseline=\\'middle\\' text-anchor=\\'middle\\' font-family=\\'sans-serif\\' font-size=\\'8\\' fill=\\'%236c757d\\'%3ERoll%3C/text%3E%3C/svg%3E'">
                <div class="flex-grow-1">
                    <span class="small text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Photo saved</span>
                    <a href="${getUploadUrl(existingPhoto)}" target="_blank" class="small text-decoration-none d-block">View Full</a>
                </div>
            </div>
        `;
    }

    card.innerHTML = `
        <input type="hidden" name="rolls[${index}][id]" value="${escapeHtml(rollId)}">
        <input type="hidden" name="rolls[${index}][existing_photo]" value="${escapeHtml(existingPhoto)}">
        <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark roll-title"><i class="bi bi-layers text-primary me-1"></i> ${escapeHtml(rollNumber)}</span>
                <span class="badge bg-dark text-white font-monospace" style="font-size: 11px;">
                    <i class="bi bi-qr-code text-warning me-1"></i><span class="roll-code-text">${escapeHtml(existingCodeText || 'FB...')}</span>
                </span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeRollCard('${index}')" title="Delete this roll">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <!-- Roll Number / Code -->
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold required">Roll No. / Code <span class="text-danger">*</span></label>
                    <input type="text" 
                           name="rolls[${index}][roll_number]" 
                           class="form-control form-control-sm roll-num-input text-uppercase" 
                           style="text-transform: uppercase;"
                           required
                           placeholder="E.G. R-101" 
                           value="${escapeHtml(rollNumber)}"
                           oninput="updateRollTitle(this, '${index}'); validateRollInput(this);">
                    <div class="invalid-feedback">Roll code is required.</div>
                </div>

                <!-- Color / Shade -->
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold required">Color / Shade <span class="text-danger">*</span></label>
                    <input type="text" 
                           name="rolls[${index}][color]" 
                           class="form-control form-control-sm roll-color-input text-uppercase" 
                           style="text-transform: uppercase;"
                           required
                           placeholder="E.G. NAVY BLUE" 
                           value="${escapeHtml(color)}"
                           oninput="recalculateMultiRollMetrics(); validateRollInput(this);">
                    <div class="invalid-feedback">Color is required.</div>
                </div>

                <!-- Design Name / Pattern -->
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold required">Design Name / Pattern <span class="text-danger">*</span></label>
                    <input type="text" 
                           name="rolls[${index}][design_name]" 
                           class="form-control form-control-sm roll-design-input text-uppercase" 
                           style="text-transform: uppercase;"
                           required
                           placeholder="E.G. FLORAL 01, SOLID, STRIPED" 
                           value="${escapeHtml(designName)}"
                           oninput="recalculateMultiRollMetrics(); validateRollInput(this);">
                    <div class="invalid-feedback">Design name is required.</div>
                </div>

                <!-- Design Count -->
                <div class="col-md-3 col-lg-1">
                    <label class="form-label small fw-semibold required">Designs <span class="text-danger">*</span></label>
                    <input type="number" 
                           name="rolls[${index}][design_count]" 
                           class="form-control form-control-sm text-end roll-design-count-input" 
                           min="1" 
                           step="1" 
                           required
                           placeholder="1" 
                           value="${escapeHtml(designCount)}"
                           oninput="recalculateMultiRollMetrics(); validateRollInput(this);">
                    <div class="invalid-feedback">Min 1 design required.</div>
                </div>

                <!-- Roll Quantity -->
                <div class="col-md-3 col-lg-1">
                    <label class="form-label small fw-semibold required">RQ (Roll Quantity) <span class="text-danger">*</span></label>
                    <input type="number" 
                           name="rolls[${index}][quantity]" 
                           class="form-control form-control-sm text-end roll-qty-input" 
                           min="1" 
                           step="1" 
                           required
                           placeholder="1" 
                           value="${escapeHtml(quantity)}"
                           oninput="recalculateMultiRollMetrics(); validateRollInput(this);">
                    <div class="invalid-feedback">Minimum 1 RQ (Roll Quantity) required.</div>
                </div>

                <!-- Roll Weight (KG) -->
                <div class="col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold required">Weight (KG) <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm has-validation">
                        <input type="number" 
                               name="rolls[${index}][weight]" 
                               class="form-control text-end roll-weight-input" 
                               min="0.01" 
                               step="0.01" 
                               required
                               placeholder="0.00" 
                               value="${escapeHtml(weight)}"
                               oninput="recalculateMultiRollMetrics(); validateRollInput(this);">
                        <span class="input-group-text">kg</span>
                        <div class="invalid-feedback">Weight must be > 0.</div>
                    </div>
                </div>

                <!-- Roll Price (INR) -->
                <div class="col-md-5 col-lg-2">
                    <label class="form-label small fw-semibold required">Price (₹) <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm has-validation">
                        <span class="input-group-text">₹</span>
                        <input type="number" 
                               name="rolls[${index}][price]" 
                               class="form-control text-end roll-price-input" 
                               min="0.01" 
                               step="0.01" 
                               required
                               placeholder="0.00" 
                               value="${escapeHtml(price)}"
                               oninput="recalculateMultiRollMetrics(); validateRollInput(this);">
                        <div class="invalid-feedback">Price must be > 0.</div>
                    </div>
                </div>

                <!-- Individual Roll Photo Upload -->
                <div class="col-12 pt-1 border-top mt-2">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="flex-grow-1" style="max-width: 480px;">
                            <label class="form-label small fw-semibold mb-1 d-flex align-items-center gap-1">
                                <i class="bi bi-camera text-primary"></i> Roll Sample Photo
                                <span class="text-muted fw-normal small">(${existingPhoto ? 'Upload new to replace' : 'Optional - JPG, PNG, WEBP'})</span>
                            </label>
                            ${photoHtml}
                            <input type="file" 
                                   name="roll_photo_${index}" 
                                   class="form-control form-control-sm roll-photo-input" 
                                   accept=".jpg,.jpeg,.png,.webp"
                                   onchange="previewIndividualRollPhoto(this, '${index}')">
                        </div>
                        <div id="roll_thumb_preview_${index}" class="d-none roll-thumb-preview">
                            <div class="d-flex align-items-center gap-2 p-1 bg-light rounded border">
                                <img src="" alt="Roll Preview" class="rounded border" style="width: 44px; height: 44px; object-fit: cover;">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="clearIndividualRollPhoto('${index}')" title="Remove new photo">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.appendChild(card);
    recalculateMultiRollMetrics();
}

function removeRollCard(index) {
    const card = document.getElementById('roll_card_' + index);
    if (card) {
        card.remove();
    }
    const container = document.getElementById('rollsContainer');
    if (container.children.length === 0) {
        document.getElementById('emptyRollsState').classList.remove('d-none');
    }
    recalculateMultiRollMetrics();
}

function updateRollTitle(input, index) {
    const card = document.getElementById('roll_card_' + index);
    if (card) {
        const titleEl = card.querySelector('.roll-title');
        titleEl.innerHTML = '<i class="bi bi-layers text-primary me-1"></i> ' + escapeHtml(input.value || ('ROLL #' + (Array.from(card.parentNode.children).indexOf(card) + 1)));
    }
    recalculateMultiRollMetrics();
}

function previewIndividualRollPhoto(input, index) {
    const previewContainer = document.getElementById('roll_thumb_preview_' + index);
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('Photo size exceeds 5MB limit. Please choose a smaller file.');
            input.value = '';
            previewContainer.classList.add('d-none');
            return;
        }
        const img = previewContainer.querySelector('img');
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            previewContainer.classList.remove('d-none');
            renderRollsSummaryTable();
        };
        reader.readAsDataURL(file);
    } else {
        previewContainer.classList.add('d-none');
        renderRollsSummaryTable();
    }
}

function clearIndividualRollPhoto(index) {
    const card = document.getElementById('roll_card_' + index);
    if (card) {
        const input = card.querySelector('.roll-photo-input');
        if (input) input.value = '';
    }
    const previewContainer = document.getElementById('roll_thumb_preview_' + index);
    if (previewContainer) {
        previewContainer.classList.add('d-none');
    }
    renderRollsSummaryTable();
}

function recalculateMultiRollMetrics() {
    const cards = document.querySelectorAll('.roll-card');
    const totalRolls = cards.length;
    document.getElementById('rollCountBadge').innerText = totalRolls;

    let totalWeight = 0;
    let totalPrice = 0;
    let totalRollQuantity = 0;
    let totalDesignCount = 0;
    let totalAvailQty = 0;
    let totalAvailWeight = 0;
    let totalAvailPrice = 0;
    const colorsSet = new Set();
    const designsSet = new Set();

    cards.forEach(card => {
        const weight = parseFloat(card.querySelector('.roll-weight-input')?.value) || 0;
        const price = parseFloat(card.querySelector('.roll-price-input')?.value) || 0;
        const initialQty = parseInt(card.querySelector('.roll-qty-input')?.value) || 0;
        const dCount = parseInt(card.querySelector('.roll-design-count-input')?.value) || 1;
        const color = card.querySelector('.roll-color-input')?.value?.trim().toUpperCase();
        const design = card.querySelector('.roll-design-input')?.value?.trim().toUpperCase();

        const usedQty = parseInt(card.dataset.usedQty) || 0;
        const usedWeight = parseFloat(card.dataset.usedWeight) || 0;
        const usedPrice = parseFloat(card.dataset.usedPrice) || 0;
        const damagedQty = parseInt(card.dataset.damagedQty) || 0;
        const damagedWeight = parseFloat(card.dataset.damagedWeight) || 0;
        const damagedPrice = parseFloat(card.dataset.damagedPrice) || 0;

        const availQty = Math.max(0, initialQty - usedQty - damagedQty);
        const availWeight = Math.max(0, weight - usedWeight - damagedWeight);
        const availPrice = Math.max(0, price - usedPrice - damagedPrice);

        totalWeight += weight;
        totalPrice += price;
        totalRollQuantity += initialQty;
        totalDesignCount += dCount;
        totalAvailQty += availQty;
        totalAvailWeight += availWeight;
        totalAvailPrice += availPrice;

        if (color) colorsSet.add(color);
        if (design) designsSet.add(design);
    });

    document.getElementById('summaryTotalRolls').innerText = totalRolls + (totalRolls === 1 ? ' Roll' : ' Rolls');
    document.getElementById('summaryTotalRQ').innerText = totalRollQuantity.toLocaleString('en-IN') + ' RQ';
    document.getElementById('summaryColorsDesigns').innerText = colorsSet.size + ' Colors • ' + Math.max(designsSet.size, totalDesignCount) + ' Designs';
    document.getElementById('summaryTotalWeight').innerText = totalWeight.toFixed(2) + ' kg';
    document.getElementById('summaryTotalPrice').innerText = '₹ ' + totalPrice.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('summaryAvailableStock').innerText = totalAvailQty + ' RQ (' + totalAvailWeight.toFixed(2) + ' kg)';

    if (totalWeight > 0 && totalPrice > 0) {
        document.getElementById('summaryRatePerKg').innerText = '₹ ' + (totalPrice / totalWeight).toFixed(2) + ' / kg';
    } else {
        document.getElementById('summaryRatePerKg').innerText = '— ₹/kg';
    }

    updateEditRollCodeSequencePreviews();
    renderRollsSummaryTable();
}

function renderRollsSummaryTable() {
    const tableBody = document.getElementById('rollDetailsTableBody');
    const tableFoot = document.getElementById('rollDetailsTableFoot');
    const tableBadge = document.getElementById('tableRollCountBadge');
    const tableContainer = document.getElementById('rollDetailsTableContainer');
    if (!tableBody) return;

    const cards = document.querySelectorAll('.roll-card');
    if (tableBadge) tableBadge.innerText = cards.length + (cards.length === 1 ? ' Roll' : ' Rolls');

    if (cards.length === 0) {
        if (tableContainer) tableContainer.classList.add('d-none');
        tableBody.innerHTML = '';
        if (tableFoot) tableFoot.innerHTML = '';
        return;
    }
    if (tableContainer) tableContainer.classList.remove('d-none');

    let html = '';
    let totalRollQuantity = 0;
    let totalWeight = 0;
    let totalPrice = 0;
    let totalDamagedQty = 0;
    let totalDamagedWeight = 0;
    let totalUsedQty = 0;
    let totalUsedWeight = 0;
    let totalAvailableQty = 0;
    let totalAvailableWeight = 0;
    let totalAvailablePrice = 0;
    cards.forEach((card, i) => {
        const rollNum = card.querySelector('.roll-num-input')?.value?.trim().toUpperCase() || ('ROLL #' + (i + 1));
        const color = card.querySelector('.roll-color-input')?.value?.trim().toUpperCase() || '—';
        const design = card.querySelector('.roll-design-input')?.value?.trim().toUpperCase() || '—';
        const designCount = parseInt(card.querySelector('.roll-design-count-input')?.value) || 1;
        const initialQty = parseInt(card.querySelector('.roll-qty-input')?.value) || 0;
        const initialWeight = parseFloat(card.querySelector('.roll-weight-input')?.value) || 0;
        const initialPrice = parseFloat(card.querySelector('.roll-price-input')?.value) || 0;

        const usedQty = parseInt(card.dataset.usedQty) || 0;
        const usedWeight = parseFloat(card.dataset.usedWeight) || 0;
        const usedPrice = parseFloat(card.dataset.usedPrice) || 0;
        const damagedQty = parseInt(card.dataset.damagedQty) || 0;
        const damagedWeight = parseFloat(card.dataset.damagedWeight) || 0;
        const damagedPrice = parseFloat(card.dataset.damagedPrice) || 0;

        const availQty = Math.max(0, initialQty - usedQty - damagedQty);
        const availWeight = Math.max(0, initialWeight - usedWeight - damagedWeight);
        const availPrice = Math.max(0, initialPrice - usedPrice - damagedPrice);

        totalRollQuantity += initialQty;
        totalWeight += initialWeight;
        totalPrice += initialPrice;
        totalDamagedQty += damagedQty;
        totalDamagedWeight += damagedWeight;
        totalUsedQty += usedQty;
        totalUsedWeight += usedWeight;
        totalAvailableQty += availQty;
        totalAvailableWeight += availWeight;
        totalAvailablePrice += availPrice;

        // Photo thumbnail
        const previewContainer = card.querySelector('.roll-thumb-preview');
        const previewImgEl = previewContainer?.querySelector('img');
        const hasPreview = previewContainer && !previewContainer.classList.contains('d-none') && previewImgEl && previewImgEl.getAttribute('src');
        const previewSrc = hasPreview ? previewImgEl.src : null;
        
        const savedPhotoInput = card.querySelector('input[name$="[existing_photo]"]');
        const savedPhoto = (savedPhotoInput ? savedPhotoInput.value : '') || card.dataset.photoUrl || '';
        
        let photoCell = '';
        const fallbackSvg = `data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'40\\' height=\\'40\\' viewBox=\\'0 0 40 40\\'%3E%3Crect width=\\'40\\' height=\\'40\\' fill=\\'%23f1f5f9\\'/%3E%3Ctext x=\\'50%25\\' y=\\'50%25\\' dominant-baseline=\\'middle\\' text-anchor=\\'middle\\' font-family=\\'sans-serif\\' font-size=\\'8\\' fill=\\'%2394a3b8\\'%3ENo Photo%3C/text%3E%3C/svg%3E`;

        if (previewSrc && !previewSrc.includes('data:image/svg+xml') && previewSrc.length > 10) {
            photoCell = `<a href="${previewSrc}" target="_blank" class="d-inline-block" title="Click to view full photo"><img src="${previewSrc}" alt="Roll" class="rounded border shadow-sm" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.onerror=null; this.src='${fallbackSvg}';"></a>`;
        } else if (savedPhoto && savedPhoto.trim()) {
            const photoUrl = getUploadUrl(savedPhoto.trim());
            photoCell = `<a href="${photoUrl}" target="_blank" class="d-inline-block" title="Click to view full photo"><img src="${photoUrl}" alt="Roll" class="rounded border shadow-sm" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.onerror=null; this.src='${fallbackSvg}';"></a>`;
        } else {
            photoCell = `<div class="rounded bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 40px; height: 40px;" title="No photo uploaded"><i class="bi bi-camera text-secondary"></i></div>`;
        }

        let statusBadge = '';
        if (damagedWeight >= initialWeight && initialWeight > 0) {
            statusBadge = `<span class="badge bg-danger text-white px-2 py-1"><i class="bi bi-exclamation-diamond me-1"></i>Damaged</span>`;
        } else if (usedQty === 0 && damagedQty === 0 && damagedWeight === 0) {
            statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle me-1"></i>Available (${availQty} RQ)</span>`;
        } else if (availQty > 0) {
            statusBadge = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>${availQty} RQ Left</span>`;
        } else {
            statusBadge = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle me-1"></i>Fully Consumed</span>`;
        }

        const displayCode = card.dataset.displayCode || (card.dataset.startCode ? (card.dataset.startCode + (card.dataset.endCode && card.dataset.endCode !== card.dataset.startCode ? '–' + card.dataset.endCode : '')) : '');

        html += `
            <tr>
                <td class="text-center text-muted fw-semibold">${i + 1}</td>
                <td>${photoCell}</td>
                <td>
                    <span class="fw-bold text-dark">${escapeHtml(rollNum)}</span>
                    ${displayCode ? `<div class="mt-1"><span class="badge bg-dark text-white font-monospace" style="font-size: 10px;"><i class="bi bi-qr-code text-warning me-1"></i>${escapeHtml(displayCode)}</span></div>` : ''}
                </td>
                <td>
                    <span class="badge bg-light text-dark border"><i class="bi bi-palette text-primary me-1"></i>${escapeHtml(color)}</span>
                </td>
                <td>
                    <span class="fw-semibold text-secondary"><i class="bi bi-brush text-primary me-1"></i>${escapeHtml(design)}</span>
                </td>
                <td class="text-center fw-semibold text-dark">${designCount}</td>
                <td class="text-end fw-bold text-dark">${initialQty} RQ</td>
                <td class="text-end fw-semibold text-dark">${initialWeight.toFixed(2)} kg</td>
                <td class="text-end fw-semibold text-dark">₹ ${initialPrice.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                <td class="text-end text-danger bg-danger-subtle bg-opacity-25">
                    ${damagedQty > 0 || damagedWeight > 0 ? `<span class="text-danger fw-bold">-${damagedQty} RQ</span> <small class="text-danger d-block">(${damagedWeight.toFixed(2)} kg)</small>` : '<span class="text-muted small">0 RQ</span>'}
                </td>
                <td class="text-end bg-light-subtle text-muted">
                    ${usedQty > 0 ? `<span class="text-secondary fw-bold">-${usedQty} RQ</span> <small class="text-muted d-block">(${usedWeight.toFixed(2)} kg)</small>` : '<span class="text-muted small">0 RQ</span>'}
                </td>
                <td class="text-end bg-success-subtle fw-bold text-success">
                    ${availQty} RQ
                    <small class="text-muted d-block fw-normal">${availWeight.toFixed(2)} kg • ₹${availPrice.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</small>
                </td>
                <td class="text-center">${statusBadge}</td>
            </tr>
        `;
    });

    tableBody.innerHTML = html;
    if (tableFoot) {
        const formatNumber = value => value.toLocaleString('en-IN', { maximumFractionDigits: 2 });
        tableFoot.innerHTML = `
            <tr>
                <td colspan="6" class="text-end">Total Summary:</td>
                <td class="text-end text-dark">${formatNumber(totalRollQuantity)} RQ</td>
                <td class="text-end text-dark">${formatNumber(totalWeight)} kg</td>
                <td class="text-end text-dark">₹ ${formatNumber(totalPrice)}</td>
                <td class="text-end text-danger bg-danger-subtle bg-opacity-25">
                    ${totalDamagedQty > 0 ? '-' + formatNumber(totalDamagedQty) : '0'} RQ
                    <small class="text-danger d-block fw-normal">${formatNumber(totalDamagedWeight)} kg</small>
                </td>
                <td class="text-end text-muted bg-light-subtle">
                    ${totalUsedQty > 0 ? '-' + formatNumber(totalUsedQty) : '0'} RQ
                    <small class="text-muted d-block fw-normal">${formatNumber(totalUsedWeight)} kg</small>
                </td>
                <td class="text-end text-success bg-success-subtle">
                    ${formatNumber(totalAvailableQty)} RQ
                    <small class="text-muted d-block fw-normal">${formatNumber(totalAvailableWeight)} kg • ₹${formatNumber(totalAvailablePrice)}</small>
                </td>
                <td class="text-center">${cards.length} Rolls Total</td>
            </tr>
        `;
    }
}

function previewDocument(input) {
    const container = document.getElementById('docPreviewContainer');
    const thumb = document.getElementById('previewThumbnail');
    const nameEl = document.getElementById('previewFileName');
    const sizeEl = document.getElementById('previewFileSize');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit. Please choose a smaller file.');
            input.value = '';
            container.classList.add('d-none');
            return;
        }

        nameEl.innerText = file.name;
        sizeEl.innerText = (file.size / 1024).toFixed(1) + ' KB';

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                thumb.innerHTML = '<img src="' + e.target.result + '" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">';
            };
            reader.readAsDataURL(file);
        } else if (file.type === 'application/pdf') {
            thumb.innerHTML = '<div class="rounded bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><i class="bi bi-file-earmark-pdf fs-4"></i></div>';
        } else {
            thumb.innerHTML = '<div class="rounded bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><i class="bi bi-file-earmark-text fs-4"></i></div>';
        }

        container.classList.remove('d-none');
    } else {
        container.classList.add('d-none');
    }
}

function clearDocumentUpload() {
    const input = document.getElementById('documentInput');
    input.value = '';
    document.getElementById('docPreviewContainer').classList.add('d-none');
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Attach Live Validation Listeners
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('supplierNameInput');
    const companyInput = document.getElementById('companyNameInput');
    const phoneInput = document.getElementById('phoneInput');
    const emailInput = document.getElementById('emailInput');
    const gstInput = document.getElementById('gstInput');
    const cityInput = document.getElementById('cityInput');
    const stateInput = document.getElementById('stateInput');
    const addressInput = document.getElementById('addressInput');
    const materialTypeInput = document.getElementById('materialTypeInput');

    if (nameInput) {
        nameInput.addEventListener('input', () => validateName(nameInput));
        nameInput.addEventListener('blur', () => validateName(nameInput));
    }
    if (companyInput) {
        companyInput.addEventListener('input', () => validateCompany(companyInput));
        companyInput.addEventListener('blur', () => validateCompany(companyInput));
    }
    if (phoneInput) {
        phoneInput.addEventListener('input', () => validatePhone(phoneInput));
        phoneInput.addEventListener('blur', () => validatePhone(phoneInput));
    }
    if (emailInput) {
        emailInput.addEventListener('input', () => validateEmail(emailInput));
        emailInput.addEventListener('blur', () => validateEmail(emailInput));
    }
    if (gstInput) {
        gstInput.addEventListener('input', () => validateGst(gstInput));
        gstInput.addEventListener('blur', () => validateGst(gstInput));
    }
    if (cityInput) {
        cityInput.addEventListener('input', () => validateCity(cityInput));
        cityInput.addEventListener('change', () => validateCity(cityInput));
        cityInput.addEventListener('blur', () => validateCity(cityInput));
    }
    if (stateInput) {
        stateInput.addEventListener('input', () => validateState(stateInput));
        stateInput.addEventListener('blur', () => validateState(stateInput));
    }
    if (addressInput) {
        addressInput.addEventListener('input', () => validateAddress(addressInput));
        addressInput.addEventListener('blur', () => validateAddress(addressInput));
    }
    if (materialTypeInput) {
        materialTypeInput.addEventListener('input', () => validateMaterialType(materialTypeInput));
        materialTypeInput.addEventListener('change', () => validateMaterialType(materialTypeInput));
        materialTypeInput.addEventListener('blur', () => validateMaterialType(materialTypeInput));
    }

    // Populate existing rolls on load
    if (existingRollsData && existingRollsData.length > 0) {
        existingRollsData.forEach(r => addRollCard(r));
    } else {
        addRollCard({
            roll_number: 'ROLL #1',
            color: <?= json_encode($seller['colors'] ?? '') ?>,
            design_name: '',
            design_count: <?= json_encode((int)($seller['design_count'] ?? 1)) ?>,
            quantity: 1,
            weight: <?= json_encode((string)($seller['roll_weight'] ?? '')) ?>,
            price: <?= json_encode((string)($seller['roll_price'] ?? '')) ?>,
            photo_url: <?= json_encode($seller['roll_photo'] ?? '') ?>
        });
    }

    // Handle Form Submit with Full Validation
    const form = document.getElementById('supplierEditForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            let firstInvalid = null;

            if (!validateName(nameInput)) { isValid = false; if (!firstInvalid) firstInvalid = nameInput; }
            if (!validateCompany(companyInput)) { isValid = false; if (!firstInvalid) firstInvalid = companyInput; }
            if (!validatePhone(phoneInput)) { isValid = false; if (!firstInvalid) firstInvalid = phoneInput; }
            if (!validateEmail(emailInput)) { isValid = false; if (!firstInvalid) firstInvalid = emailInput; }
            if (!validateGst(gstInput)) { isValid = false; if (!firstInvalid) firstInvalid = gstInput; }
            if (!validateCity(cityInput)) { isValid = false; if (!firstInvalid) firstInvalid = cityInput; }
            if (!validateState(stateInput)) { isValid = false; if (!firstInvalid) firstInvalid = stateInput; }
            if (!validateAddress(addressInput)) { isValid = false; if (!firstInvalid) firstInvalid = addressInput; }
            if (!validateMaterialType(materialTypeInput)) { isValid = false; if (!firstInvalid) firstInvalid = materialTypeInput; }

            const cards = document.querySelectorAll('.roll-card');
            if (cards.length === 0) {
                isValid = false;
                alert('Please add at least 1 fabric roll for this supplier lot.');
                addRollCard({ roll_number: 'ROLL #1', design_count: 1, quantity: 1 });
                e.preventDefault();
                return;
            }

            cards.forEach(card => {
                const reqInputs = card.querySelectorAll('input[required]');
                reqInputs.forEach(input => {
                    if (!validateRollInput(input)) {
                        isValid = false;
                        if (!firstInvalid) firstInvalid = input;
                    }
                });
            });

            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                if (firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }
});

let baseEditFabricNextSeq = 1;
let monthEditFabricPrefix = 'FB<?= date('my') ?>';

fetch('<?= url("api/sellers/next-roll-codes") ?>')
    .then(res => res.json())
    .then(res => {
        if (res && res.success && res.data && Number.isInteger(Number(res.data.start_seq)) && res.data.prefix) {
            baseEditFabricNextSeq = Number(res.data.start_seq);
            monthEditFabricPrefix = res.data.prefix;
            updateEditRollCodeSequencePreviews();
        }
    })
    .catch(e => console.error('Could not load the next fabric roll code sequence:', e));

function updateEditRollCodeSequencePreviews() {
    const cards = document.querySelectorAll('.roll-card');
    let dynamicSeq = baseEditFabricNextSeq;
    cards.forEach((card) => {
        const qtyInput = card.querySelector('.roll-qty-input');
        const qty = Math.max(1, parseInt(qtyInput ? qtyInput.value : 1) || 1);

        let startCode = card.dataset.startCode;
        let endCode = card.dataset.endCode;

        if (!startCode) {
            const startSeq = dynamicSeq;
            const endSeq = dynamicSeq + qty - 1;
            dynamicSeq = endSeq + 1;
            startCode = `${monthEditFabricPrefix}${String(startSeq).padStart(2, '0')}`;
            endCode = `${monthEditFabricPrefix}${String(endSeq).padStart(2, '0')}`;
            card.dataset.startSeq = startSeq;
            card.dataset.endSeq = endSeq;
        }

        const displayCode = startCode === endCode ? startCode : `${startCode}–${endCode}`;
        card.dataset.displayCode = displayCode;

        const codeEl = card.querySelector('.roll-code-text');
        if (codeEl) {
            codeEl.textContent = displayCode;
        }
    });
}

function openEditLabelsPreviewModal() {
    const modalEl = document.getElementById('editLabelsPreviewModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    const container = document.getElementById('editModalStickersContainer');
    const countInfo = document.getElementById('editModalCountInfo');
    container.innerHTML = '';

    const supplierName = (document.getElementById('companyNameInput')?.value || document.getElementById('supplierNameInput')?.value || 'GRM FABRIC').trim().toUpperCase();
    const materialType = (document.getElementById('materialTypeInput')?.value || 'STANDARD FABRIC').trim().toUpperCase();

    const cards = document.querySelectorAll('.roll-card');
    const stickers = [];

    cards.forEach((card, cIdx) => {
        const rollNum = card.querySelector('.roll-num-input')?.value?.trim().toUpperCase() || ('ROLL #' + (cIdx + 1));
        const color = card.querySelector('.roll-color-input')?.value?.trim().toUpperCase() || '';
        const design = card.querySelector('.roll-design-input')?.value?.trim().toUpperCase() || '';
        const qty = Math.max(1, parseInt(card.querySelector('.roll-qty-input')?.value) || 1);
        const weight = parseFloat(card.querySelector('.roll-weight-input')?.value) || 0;
        
        let startSeq = parseInt(card.dataset.startSeq) || 1;
        let prefix = monthEditFabricPrefix;
        if (card.dataset.startCode) {
            prefix = card.dataset.startCode.substring(0, 6);
            startSeq = parseInt(card.dataset.startCode.substring(6)) || 1;
        }

        for (let i = 0; i < qty; i++) {
            const seq = startSeq + i;
            const code = `${prefix}${String(seq).padStart(2, '0')}`;
            const payload = `${code}|${supplierName}|${rollNum}|${color}|${design}|${weight}KG`;
            stickers.push({
                code,
                seq,
                piece_index: i + 1,
                total_qty: qty,
                roll_number: rollNum,
                color,
                design_name: design,
                weight,
                supplier_name: supplierName,
                material_type: materialType,
                qr_payload: payload
            });
        }
    });

    if (stickers.length === 0) {
        container.innerHTML = '<div class="alert alert-warning text-center w-100">No fabric rolls in this lot.</div>';
        countInfo.textContent = '0 Stickers';
        return;
    }

    countInfo.textContent = stickers.length + ' Label Sticker' + (stickers.length === 1 ? '' : 's') + ' Ready';

    stickers.forEach((stk, idx) => {
        const card = document.createElement('div');
        card.className = 'edit-sticker-card';
        card.id = 'edit_stk_card_' + idx;

        const safeColor = stk.color ? `Color: ${stk.color}` : '';
        const safeDesign = stk.design_name ? `Design: ${stk.design_name}` : '';
        const safeWeight = stk.weight > 0 ? `Total Roll Weight: ${Number(stk.weight).toFixed(2)} kg` : '';

        card.innerHTML = `
            <div class="edit-sticker-header">
                <div class="edit-sticker-title text-truncate">GRM RAW MATERIAL</div>
                <div class="edit-sticker-supplier text-truncate" style="max-width: 120px;" title="${stk.supplier_name}">${stk.supplier_name}</div>
            </div>
            <div class="edit-sticker-body">
                <div class="edit-qr-wrapper">
                    <div class="edit-qr-box" id="edit_qr_${idx}"></div>
                    <div class="edit-code-text">${stk.code}</div>
                </div>
                <div class="edit-details-col">
                    <div class="edit-roll-badge" title="${stk.roll_number}">${stk.roll_number}</div>
                    <div class="edit-material-text" title="${stk.material_type}">${stk.material_type}</div>
                    <div class="edit-spec-row">
                        ${safeColor ? `<div class="edit-spec-item"><strong>Color:</strong> ${stk.color}</div>` : ''}
                        ${safeDesign ? `<div class="edit-spec-item"><strong>Design:</strong> ${stk.design_name}</div>` : ''}
                        ${safeWeight ? `<div class="edit-spec-item"><strong>Total Roll Weight:</strong> ${Number(stk.weight).toFixed(2)} kg</div>` : ''}
                    </div>
                    <div class="edit-piece-seq-badge">
                        PIECE ${stk.piece_index} OF ${stk.total_qty}
                    </div>
                </div>
            </div>
        `;

        container.appendChild(card);

        const qrBox = document.getElementById('edit_qr_' + idx);
        if (qrBox && window.QRCode) {
            new QRCode(qrBox, {
                text: stk.qr_payload,
                width: 64,
                height: 64,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    });
}
</script>

<!-- Live Thermal Labels Preview Modal in Edit -->
<div class="modal fade" id="editLabelsPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-qr-code fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0">Fabric Roll Labels Live Sequence Preview</h5>
                        <small class="text-white-50">Thermal 250px × 130px stickers for this supplier</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= url('production/sellers/' . $seller['id'] . '/labels') ?>" target="_blank" class="btn btn-sm btn-warning fw-bold">
                        <i class="bi bi-printer me-1"></i> Open Print Page
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="edit-stickers-grid d-flex flex-wrap gap-3 justify-content-center" id="editModalStickersContainer">
                    <!-- Dynamic stickers -->
                </div>
            </div>
            <div class="modal-footer bg-white py-2 justify-content-between">
                <div class="small text-muted" id="editModalCountInfo">0 Stickers</div>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.edit-sticker-card {
    width: 250px;
    min-height: 130px;
    background: #fff;
    border: 1.5px solid #0f172a;
    border-radius: 6px;
    padding: 8px 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-align: left;
}
.edit-sticker-header {
    border-bottom: 1px dashed #cbd5e1;
    padding-bottom: 3px;
    margin-bottom: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.edit-sticker-title {
    font-size: 8.5px;
    font-weight: 800;
    letter-spacing: 0.5px;
    color: #0f172a;
    text-transform: uppercase;
    line-height: 1.1;
}
.edit-sticker-supplier {
    font-size: 7.5px;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 600;
    line-height: 1;
}
.edit-sticker-body {
    display: flex;
    align-items: center;
    gap: 8px;
}
.edit-qr-wrapper {
    width: 72px;
    flex-shrink: 0;
    text-align: center;
}
.edit-qr-box {
    width: 68px;
    height: 68px;
    background: #fff;
    padding: 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
}
.edit-qr-box img,
.edit-qr-box canvas {
    max-width: 100% !important;
    max-height: 100% !important;
    display: block;
}
.edit-code-text {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-weight: 800;
    font-size: 10px;
    color: #0f172a;
    letter-spacing: 0.8px;
    margin-top: 2px;
    line-height: 1;
    text-align: center;
}
.edit-details-col {
    flex-grow: 1;
    overflow: hidden;
    line-height: 1.15;
}
.edit-roll-badge {
    font-size: 10.5px;
    font-weight: 800;
    color: #0f172a;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.edit-material-text {
    font-size: 7.5px;
    color: #475569;
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 2px;
}
.edit-spec-row {
    margin-top: 3px;
    font-size: 8px;
    color: #334155;
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.edit-spec-item {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.edit-piece-seq-badge {
    display: inline-block;
    background: #0f172a;
    color: #fff;
    padding: 1px 4px;
    border-radius: 3px;
    font-size: 7.5px;
    font-weight: 700;
    letter-spacing: 0.3px;
    margin-top: 3px;
}
</style>

<script src="<?= asset('js/qrcode.min.js') ?>"></script>
<script>if (typeof QRCode === 'undefined') document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>');</script>
