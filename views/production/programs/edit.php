<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1 fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Production Program</h2>
        <span class="text-muted">Update program details, raw material rolls, per-roll 3-tier category hierarchies, and schedule dates for <strong><?= e($program['program_number']) ?></strong></span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('production/programs/view/' . $program['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i> View Program
        </a>
        <a href="<?= url('production/programs') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Programs
        </a>
    </div>
</div>

<form method="POST" action="<?= url('production/programs/update/' . $program['id']) ?>" id="programEditForm">
    <?= CSRF::field() ?>

    <div class="row g-4">
        <!-- LEFT COLUMN: Program Info & Raw Material Rolls with Per-Roll Catalog -->
        <div class="col-lg-6">
            <!-- 1. Program Identity -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-tag-fill me-2 text-primary"></i> 1. Program Overview
                    </h5>
                    <small class="text-muted">Define the unique program title and reference code</small>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold required">Program Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="name" 
                               id="program_name" 
                               class="form-control form-control-lg text-uppercase fw-bold" 
                               value="<?= e($program['name'] ?: $program['product_name'] ?? '') ?>" 
                               placeholder="e.g. SUMMER MATERNITY COLLECTION 2026" 
                               required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold required">Program Code / Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-hash"></i></span>
                            <input type="text" 
                                   name="program_number" 
                                   id="program_number" 
                                   class="form-control text-uppercase fw-bold" 
                                   value="<?= e($program['program_number']) ?>" 
                                   required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Raw Material Rolls (From Sellers) - MULTI-ROLL WITH PER-ROLL 3-TIER CATALOG -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-layers-fill me-2 text-primary"></i> 2. Raw Material Rolls &amp; Per-Roll Catalog Hierarchy
                        </h5>
                        <small class="text-muted">Assign fabric rolls and configure 3-tier catalog categories for each roll</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnMultiSelectRolls" onclick="openMultiRollModal()">
                            <i class="bi bi-check2-square me-1"></i> Multi-Select Rolls
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="btnAddRollRow" onclick="addProgramRollRow()">
                            <i class="bi bi-plus-lg me-1"></i> Add Roll
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Container for dynamic roll items -->
                    <div id="programRollsContainer" class="d-flex flex-column gap-3 mb-3">
                        <!-- Dynamically populated by JS with independent Catalog Dropdowns -->
                    </div>

                    <!-- Hidden inputs for backward compatibility and total sums -->
                    <input type="hidden" name="seller_roll_id" id="primary_seller_roll_id" value="<?= e($program['seller_roll_id'] ?? '') ?>">
                    <input type="hidden" name="roll_weight" id="total_roll_weight" value="<?= e($program['roll_weight'] ?? '0.00') ?>">
                    <input type="hidden" name="roll_price" id="total_roll_price" value="<?= e($program['roll_price'] ?? '0.00') ?>">

                    <!-- Live Aggregated Roll Metrics Summary Bar -->
                    <div class="p-3 bg-light rounded border">
                        <div class="row g-2 text-center align-items-center">
                            <div class="col-6 col-sm-3 border-end">
                                <div class="text-muted small">Total Rolls</div>
                                <div class="fw-bold fs-6 text-primary mt-1" id="dispTotalRollCount">0 rolls</div>
                            </div>
                            <div class="col-6 col-sm-3 border-end">
                                <div class="text-muted small">Total Roll Weight</div>
                                <div class="fw-bold fs-6 text-dark mt-1" id="dispTotalRollWeight">0.00 kg</div>
                            </div>
                            <div class="col-6 col-sm-3 border-end">
                                <div class="text-muted small">Total Roll Price</div>
                                <div class="fw-bold fs-6 text-success mt-1" id="dispTotalRollPrice">₹0.00</div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-muted small">Avg Fabric Rate</div>
                                <div class="fw-bold fs-6 text-secondary mt-1" id="dispAvgRatePerKg">₹0.00 / kg</div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>

        <!-- RIGHT COLUMN: Timeline & Additional Settings -->
        <div class="col-lg-6">
            <!-- 3. Timeline & Schedule -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-calendar-week me-2 text-primary"></i> 3. Programming Timeline &amp; Scheduling
                    </h5>
                    <small class="text-muted">Specify the manufacturing start and completion target dates</small>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Programming Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= e($program['start_date'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Target Completion / End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?= e($program['end_date'] ?? $program['target_date'] ?? date('Y-m-d', strtotime('+14 days'))) ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Production Unit & Assignment -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-buildings-fill me-2 text-primary"></i> 4. Production Unit &amp; Assignment
                    </h5>
                    <small class="text-muted">Optional manufacturing unit assignment, priority, and supervisor</small>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <?php if (!empty($scopedUnitId)): 
                                $effectiveUnitId = !empty($program['production_unit_id']) ? $program['production_unit_id'] : $scopedUnitId;
                                $assignedUnitName = $program['production_unit_name'] ?? '';
                                if (!$assignedUnitName && !empty($units)) {
                                    foreach ($units as $u) {
                                        if ($u['id'] == $effectiveUnitId) { $assignedUnitName = $u['name']; break; }
                                    }
                                }
                                if (!$assignedUnitName) {
                                    $puRow = Database::fetchOne("SELECT name FROM production_units WHERE id = ?", [$effectiveUnitId]);
                                    $assignedUnitName = $puRow['name'] ?? 'Assigned Unit';
                                }
                            ?>
                                <label class="form-label fw-semibold">Production Unit</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-primary border-end-0">
                                        <i class="bi bi-buildings-fill"></i>
                                    </span>
                                    <input type="text" class="form-control bg-light border-start-0 fw-semibold text-dark" value="<?= e($assignedUnitName) ?>" readonly>
                                    <span class="input-group-text bg-light text-muted small border-start-0" title="Locked to your assigned unit">
                                        <i class="bi bi-lock-fill text-secondary me-1"></i> Locked
                                    </span>
                                </div>
                                <input type="hidden" name="production_unit_id" value="<?= e($effectiveUnitId) ?>">
                                <small class="text-muted d-block mt-1">
                                    <i class="bi bi-shield-check text-success me-1"></i>Designated manufacturing unit (locked for staff)
                                </small>
                            <?php else: ?>
                                <label class="form-label fw-semibold">Production Unit</label>
                                <select name="production_unit_id" class="form-select">
                                    <option value="">Select Unit</option>
                                    <?php foreach ($units as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= ($program['production_unit_id'] == $u['id']) ? 'selected' : '' ?>><?= e($u['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-1">Optional manufacturing facility for this program</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="LOW" <?= ($program['priority'] === 'LOW') ? 'selected' : '' ?>>Low</option>
                                <option value="MEDIUM" <?= ($program['priority'] === 'MEDIUM' || empty($program['priority'])) ? 'selected' : '' ?>>Medium</option>
                                <option value="HIGH" <?= ($program['priority'] === 'HIGH') ? 'selected' : '' ?>>High</option>
                                <option value="URGENT" <?= ($program['priority'] === 'URGENT') ? 'selected' : '' ?>>Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Supervisor / Manager</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">Select Staff</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= ($program['assigned_to'] == $u['id']) ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label fw-semibold">Notes / Special Instructions</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Fabric specifications, special trims, packaging, or delivery instructions..."><?= e($program['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Sticky Actions -->
    <div class="card shadow-sm border-0 mb-5 sticky-bottom bg-white py-3 px-4 border-top">
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?= url('production/programs/view/' . $program['id']) ?>" class="btn btn-outline-secondary px-4">
                <i class="bi bi-x-circle me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-5 py-2 fw-semibold shadow-sm" id="btnUpdateProgram">
                <i class="bi bi-check2-circle me-1"></i> Update Production Program
            </button>
        </div>
    </div>
</form>

<!-- Multi-Select Rolls Modal -->
<div class="modal fade" id="multiRollSelectModal" tabindex="-1" aria-labelledby="multiRollSelectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check2-square fs-5"></i>
                    <h5 class="modal-title fw-bold mb-0" id="multiRollSelectModalLabel">Select Multiple Raw Material Rolls</h5>
                    <span class="badge bg-white text-primary fw-bold ms-2 px-2 py-1" id="multiSelectedCountBadge">0 Selected</span>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Search & Filter Controls -->
                <div class="row g-2 mb-3 align-items-center">
                    <div class="col-md-7">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="multiRollSearchInput" class="form-control form-control-sm border-start-0" placeholder="Search by roll #, color, design, supplier, fabric..." oninput="filterMultiRollList()">
                            <button type="button" class="btn btn-outline-secondary border-start-0" onclick="clearMultiRollSearch()" title="Clear search">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllFilteredRolls()">
                            <i class="bi bi-check-all me-1"></i> Select All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllMultiRolls()">
                            <i class="bi bi-dash-square me-1"></i> Deselect All
                        </button>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="table-responsive border rounded" style="max-height: 380px;">
                    <table class="table table-hover align-middle mb-0" id="multiRollSelectTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 40px;" class="text-center">
                                    <input type="checkbox" class="form-check-input" id="masterMultiRollCheckbox" onchange="toggleMasterMultiRoll(this.checked)">
                                </th>
                                <th>Roll Details</th>
                                <th>Color &amp; Design</th>
                                <th>Supplier</th>
                                <th>Available Stock</th>
                                <th class="text-end">Weight / Price</th>
                            </tr>
                        </thead>
                        <tbody id="multiRollTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
                <div id="noMultiRollsAlert" class="text-center py-4 text-muted d-none">
                    <i class="bi bi-search fs-3 d-block mb-2 text-secondary"></i>
                    No matching fabric rolls found.
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted fw-semibold" id="multiRollFooterSummary">0 roll(s) ready to add</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm fw-semibold px-4" onclick="confirmMultiRollSelection()">
                        <i class="bi bi-plus-circle me-1"></i> Add Selected Rolls
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const baseUrl = '<?= rtrim(url(''), '/') ?>';
const availableSellerRolls = <?= json_encode($rolls ?? []) ?>;
const allCategories = <?= json_encode($categories ?? []) ?>;
const existingProgramRolls = <?= json_encode(!empty($programRolls) ? $programRolls : [['seller_roll_id' => $program['seller_roll_id'] ?? null, 'roll_weight' => $program['roll_weight'] ?? 0, 'roll_price' => $program['roll_price'] ?? 0, 'category_id' => $program['category_id'] ?? null, 'subcategory_id' => $program['subcategory_id'] ?? null, 'sub_subcategory_id' => $program['sub_subcategory_id'] ?? null]]) ?>;
let rollCounter = 0;

function buildCategoryOptionsHtml(selectedCatId = '') {
    let html = '<option value="">-- Select Main Category --</option>';
    if (allCategories && allCategories.length > 0) {
        allCategories.forEach(cat => {
            const isSel = (selectedCatId && String(cat.id) === String(selectedCatId)) ? 'selected' : '';
            html += `<option value="${cat.id}" ${isSel}>${escapeHtml(cat.name)}</option>`;
        });
    }
    return html;
}

function getRollBaseLabel(r) {
    let label = (r.roll_number || ('Roll #' + r.id)) + ' — ' + (r.material_type || 'Fabric');
    if (r.color) label += ' (' + r.color + ')';
    if (r.design_name) label += ' [' + r.design_name + ']';
    label += ' — Supplier: ' + (r.seller_name || 'Seller');
    return label;
}

function buildRollOptionsHtml(selectedRollId = '', currentRowIndex = null) {
    let optionsHtml = '<option value="">-- Select Fabric Roll --</option>';
    if (availableSellerRolls && Array.isArray(availableSellerRolls)) {
        availableSellerRolls.forEach(r => {
            const origQty = parseInt(r.quantity || 0);
            const totalAvail = (r.available_quantity !== undefined && r.available_quantity !== null) ? parseInt(r.available_quantity) : origQty;
            const origWeight = parseFloat(r.weight || 0);
            const origPrice = parseFloat(r.price || 0);

            let otherUsed = 0;
            if (currentRowIndex !== null) {
                const cards = document.querySelectorAll('.program-roll-card');
                cards.forEach(card => {
                    const cardIdMatch = card.id.match(/\d+$/);
                    const cIdx = cardIdMatch ? parseInt(cardIdMatch[0]) : null;
                    if (cIdx !== currentRowIndex) {
                        const sel = card.querySelector('.roll-select');
                        const qInp = card.querySelector('.roll-qty-input');
                        if (sel && String(sel.value) === String(r.id)) {
                            otherUsed += parseInt(qInp?.value) || 0;
                        }
                    }
                });
            }

            const remAvail = Math.max(0, totalAvail - otherUsed);
            const baseLabel = getRollBaseLabel(r);
            let label = baseLabel;
            const dmgQty = parseInt(r.damaged_quantity || 0);
            if (dmgQty > 0) {
                label += ' • [' + dmgQty + ' pcs Damaged]';
            }
            if (remAvail <= 0) {
                label += ' • [OUT OF STOCK - 0 pcs]';
            } else {
                label += ' • Available Stock: ' + remAvail + ' pcs';
            }

            const isSel = selectedRollId && String(selectedRollId) === String(r.id);
            optionsHtml += `<option value="${r.id}" 
                data-weight="${origWeight}" 
                data-price="${origPrice}" 
                data-qty="${remAvail}"
                data-avail-qty="${remAvail}"
                data-total-avail="${totalAvail}"
                data-orig-qty="${origQty}"
                data-orig-weight="${origWeight}"
                data-orig-price="${origPrice}"
                data-seller="${escapeHtml(r.seller_name || '')}" 
                data-company="${escapeHtml(r.seller_company || '')}" 
                data-material="${escapeHtml(r.material_type || 'Fabric')}" 
                data-color="${escapeHtml(r.color || '')}" 
                data-design="${escapeHtml(r.design_name || '')}" 
                data-rollno="${escapeHtml(r.roll_number || ('Roll #' + r.id))}"
                ${isSel ? 'selected' : ''}>
                ${escapeHtml(label)}
            </option>`;
        });
    }
    return optionsHtml;
}

// Dynamic Multi-Roll Manager with 3-Tier Catalog Selection
function addProgramRollRow(rollData = null) {
    rollCounter++;
    const idx = rollCounter;
    const container = document.getElementById('programRollsContainer');
    const rowDiv = document.createElement('div');
    rowDiv.className = 'p-3 bg-white rounded border shadow-sm program-roll-card mb-2';
    rowDiv.id = 'program_roll_card_' + idx;

    const optionsHtml = buildRollOptionsHtml(rollData ? (rollData.seller_roll_id || rollData.id) : '', idx);

    const initQty = rollData ? (rollData.roll_qty || rollData.quantity || '1') : '';
    const initWeight = rollData ? parseFloat(rollData.roll_weight || rollData.weight || 0).toFixed(2) : '';
    const initPrice = rollData ? parseFloat(rollData.roll_price || rollData.price || 0).toFixed(2) : '';
    const catId = rollData ? (rollData.category_id || '') : '';
    const subcatId = rollData ? (rollData.subcategory_id || '') : '';
    const subSubcatId = rollData ? (rollData.sub_subcategory_id || '') : '';

    const categoryOptionsHtml = buildCategoryOptionsHtml(catId);

    rowDiv.innerHTML = `
        <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
            <span class="badge bg-primary-subtle text-primary border fw-semibold roll-badge-num">Roll #${idx}</span>
            <button type="button" class="btn btn-sm btn-outline-danger p-1 px-2 btn-remove-roll" onclick="removeProgramRollRow(${idx})" title="Remove Roll">
                <i class="bi bi-trash"></i> Remove
            </button>
        </div>

        <div class="mb-2">
            <label class="form-label small fw-semibold text-dark mb-1">Select Raw Material Roll <span class="text-danger">*</span></label>
            <select name="rolls[${idx}][seller_roll_id]" class="form-select roll-select" required onchange="handleRollRowChange(this, ${idx})">
                ${optionsHtml}
            </select>
        </div>

        <div class="row g-2 mb-2">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">
                    Roll Qty (pcs) <span class="badge bg-light text-secondary border ms-1" id="disp_avail_qty_${idx}" style="font-size: 10px;">Stock: 0</span>
                </label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-box-seam"></i></span>
                    <input type="number" step="1" min="1" name="rolls[${idx}][roll_qty]" id="roll_qty_${idx}" class="form-control fw-bold roll-qty-input" placeholder="1" value="${initQty}" oninput="handleRollQtyInput(${idx})" required>
                    <span class="input-group-text bg-light">pcs</span>
                </div>
                <div id="roll_qty_warning_${idx}" class="text-danger small mt-1 fw-semibold d-none" style="font-size: 11px;"></div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Roll Weight (kg)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-speedometer2"></i></span>
                    <input type="number" step="0.01" min="0" name="rolls[${idx}][roll_weight]" id="roll_weight_${idx}" class="form-control fw-bold roll-weight-input bg-light" placeholder="0.00" value="${initWeight}" readonly style="cursor: not-allowed;" required>
                    <span class="input-group-text bg-light">kg</span>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Roll Price (₹)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">₹</span>
                    <input type="number" step="0.01" min="0" name="rolls[${idx}][roll_price]" id="roll_price_${idx}" class="form-control fw-bold text-primary roll-price-input bg-light" placeholder="0.00" value="${initPrice}" readonly style="cursor: not-allowed;" required>
                </div>
            </div>
        </div>

        <div id="roll_specs_${idx}" class="p-2 bg-light rounded small text-muted d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 ${rollData ? '' : 'd-none'}">
            <div><i class="bi bi-building text-primary me-1"></i> <span id="roll_supplier_${idx}">-</span></div>
            <div><i class="bi bi-palette text-primary me-1"></i> <span id="roll_color_${idx}">-</span></div>
            <div><i class="bi bi-brush text-primary me-1"></i> <span id="roll_design_${idx}">-</span></div>
        </div>

        <!-- 3-Tier Catalog Selection for THIS specific Roll -->
        <div class="p-2 bg-light-subtle rounded border mt-2">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-semibold text-primary"><i class="bi bi-diagram-3 me-1"></i>Catalog Hierarchy for this Roll</span>
                <span class="badge bg-secondary-subtle text-secondary border small roll-catalog-badge" id="roll_cat_badge_${idx}">Catalog</span>
            </div>
            <div class="row g-2">
                <div class="col-12 mb-1">
                    <label class="form-label small text-muted mb-0" style="font-size: 11px;">Main Category</label>
                    <select name="rolls[${idx}][category_id]" class="form-select form-select-sm fw-semibold roll-cat-select" id="roll_cat_${idx}" onchange="handleRollCategoryChange(${idx}, this.value)">
                        ${categoryOptionsHtml}
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small text-muted mb-0" style="font-size: 11px;">Sub Category</label>
                    <select name="rolls[${idx}][subcategory_id]" class="form-select form-select-sm roll-subcat-select" id="roll_subcat_${idx}" onchange="handleRollSubcategoryChange(${idx}, this.value)" ${catId ? '' : 'disabled'}>
                        <option value="">-- Sub Category --</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small text-muted mb-0" style="font-size: 11px;">Sub Sub Category</label>
                    <select name="rolls[${idx}][sub_subcategory_id]" class="form-select form-select-sm roll-subsubcat-select" id="roll_subsubcat_${idx}" onchange="handleRollSubSubcategoryChange(${idx}, this.value)" ${subcatId ? '' : 'disabled'}>
                        <option value="">-- Sub Sub Category --</option>
                    </select>
                </div>
            </div>
        </div>
    `;

    container.appendChild(rowDiv);
    updateRollIndices();

    if (rollData) {
        const sel = rowDiv.querySelector('.roll-select');
        handleRollRowChange(sel, idx, false);
    }

    if (catId) {
        loadRollSubcategories(idx, catId, subcatId, subSubcatId);
    } else {
        updateRollCatalogBadge(idx);
    }

    syncRollStockAllocations();
}

function removeProgramRollRow(counterId) {
    const cards = document.querySelectorAll('.program-roll-card');
    if (cards.length <= 1) {
        alert('A production program must have at least one assigned fabric roll.');
        return;
    }
    const row = document.getElementById('program_roll_card_' + counterId);
    if (row) {
        row.remove();
        updateRollIndices();
        syncRollStockAllocations();
    }
}

function updateRollIndices() {
    const cards = document.querySelectorAll('.program-roll-card');
    cards.forEach((card, idx) => {
        const badge = card.querySelector('.roll-badge-num');
        if (badge) {
            badge.innerText = 'Roll #' + (idx + 1);
        }
    });
}

function handleRollQtyInput(counterId) {
    const card = document.getElementById('program_roll_card_' + counterId);
    if (!card) return;
    const select = card.querySelector('.roll-select');
    if (!select || !select.value) {
        calculateTotalRollMetrics();
        return;
    }
    syncRollStockAllocations();
}

function handleRollRowChange(selectElement, counterId, autoPopulate = true) {
    const qtyInput = document.getElementById('roll_qty_' + counterId);
    const weightInput = document.getElementById('roll_weight_' + counterId);
    const priceInput = document.getElementById('roll_price_' + counterId);
    const specsDiv = document.getElementById('roll_specs_' + counterId);
    const warningDiv = document.getElementById('roll_qty_warning_' + counterId);

    if (!selectElement.value) {
        if (specsDiv) specsDiv.classList.add('d-none');
        if (warningDiv) {
            warningDiv.innerHTML = '';
            warningDiv.classList.add('d-none');
        }
        if (qtyInput) {
            qtyInput.value = '';
            qtyInput.removeAttribute('max');
            qtyInput.classList.remove('is-invalid', 'border-danger');
        }
        if (weightInput) weightInput.value = '';
        if (priceInput) priceInput.value = '';
        syncRollStockAllocations();
        return;
    }

    const selectedRollId = selectElement.value;
    const rollObj = availableSellerRolls.find(r => String(r.id) === String(selectedRollId));
    if (!rollObj) {
        syncRollStockAllocations();
        return;
    }

    const origQty = parseInt(rollObj.quantity || 0);
    const totalAvail = (rollObj.available_quantity !== undefined && rollObj.available_quantity !== null) ? parseInt(rollObj.available_quantity) : origQty;
    const origWeight = parseFloat(rollObj.weight || 0);
    const origPrice = parseFloat(rollObj.price || 0);

    let otherUsed = 0;
    const allCards = document.querySelectorAll('.program-roll-card');
    allCards.forEach(c => {
        const cIdMatch = c.id.match(/\d+$/);
        const cIdx = cIdMatch ? parseInt(cIdMatch[0]) : null;
        if (cIdx !== counterId) {
            const sel = c.querySelector('.roll-select');
            const qInp = c.querySelector('.roll-qty-input');
            if (sel && String(sel.value) === String(selectedRollId)) {
                otherUsed += parseInt(qInp?.value) || 0;
            }
        }
    });

    const currentAvail = Math.max(0, totalAvail - otherUsed);

    const supSpan = document.getElementById('roll_supplier_' + counterId);
    const colSpan = document.getElementById('roll_color_' + counterId);
    const desSpan = document.getElementById('roll_design_' + counterId);
    if (supSpan) supSpan.innerText = rollObj.seller_name ? (rollObj.seller_name + (rollObj.seller_company ? ` (${rollObj.seller_company})` : '')) : 'Seller';
    if (colSpan) colSpan.innerText = rollObj.color || 'Plain';
    if (desSpan) desSpan.innerText = rollObj.design_name || 'Standard Design';
    if (specsDiv) specsDiv.classList.remove('d-none');

    const stockBadge = document.getElementById('disp_avail_qty_' + counterId);
    if (stockBadge) {
        stockBadge.innerText = `Stock: ${currentAvail} pcs`;
        stockBadge.className = `badge ${currentAvail > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'} ms-1`;
    }

    if (qtyInput) {
        qtyInput.setAttribute('max', totalAvail);
        if (autoPopulate && (!qtyInput.value || parseInt(qtyInput.value) <= 0)) {
            qtyInput.value = currentAvail > 0 ? 1 : 1;
        }
    }

    syncRollStockAllocations();
}

function syncRollStockAllocations() {
    const cards = document.querySelectorAll('.program-roll-card');
    const rollUsageMap = {};

    cards.forEach(card => {
        const sel = card.querySelector('.roll-select');
        const qInp = card.querySelector('.roll-qty-input');
        if (sel && sel.value) {
            const rollId = String(sel.value);
            const qty = parseInt(qInp?.value) || 0;
            rollUsageMap[rollId] = (rollUsageMap[rollId] || 0) + qty;
        }
    });

    cards.forEach(card => {
        const cardIdMatch = card.id.match(/\d+$/);
        const counterId = cardIdMatch ? parseInt(cardIdMatch[0]) : null;
        if (!counterId) return;

        const sel = card.querySelector('.roll-select');
        const qtyInp = document.getElementById('roll_qty_' + counterId);
        const weightInput = document.getElementById('roll_weight_' + counterId);
        const priceInput = document.getElementById('roll_price_' + counterId);
        const warningDiv = document.getElementById('roll_qty_warning_' + counterId);
        const stockBadge = document.getElementById('disp_avail_qty_' + counterId);

        if (!sel || !sel.value) {
            if (stockBadge) {
                stockBadge.innerText = 'Stock: 0 pcs';
                stockBadge.className = 'badge bg-light text-secondary border ms-1';
            }
            return;
        }

        const rollId = String(sel.value);
        const rollObj = availableSellerRolls.find(r => String(r.id) === rollId);
        if (!rollObj) return;

        const origQty = parseInt(rollObj.quantity || 0);
        const totalAvail = (rollObj.available_quantity !== undefined && rollObj.available_quantity !== null) ? parseInt(rollObj.available_quantity) : origQty;
        const origWeight = parseFloat(rollObj.weight || 0);
        const origPrice = parseFloat(rollObj.price || 0);

        const thisRowQty = parseInt(qtyInp?.value) || 0;
        const totalUsed = rollUsageMap[rollId] || 0;
        const otherRowsUsed = totalUsed - thisRowQty;
        const currentAvail = Math.max(0, totalAvail - otherRowsUsed);

        if (stockBadge) {
            stockBadge.innerText = `Stock: ${currentAvail} pcs`;
            stockBadge.className = `badge ${currentAvail >= thisRowQty && currentAvail > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'} ms-1`;
        }

        if (origQty > 0 && thisRowQty > 0) {
            const calculatedWeight = (origWeight / origQty) * thisRowQty;
            const calculatedPrice = (origPrice / origQty) * thisRowQty;
            if (weightInput) weightInput.value = calculatedWeight.toFixed(2);
            if (priceInput) priceInput.value = calculatedPrice.toFixed(2);
        } else {
            if (weightInput) weightInput.value = '0.00';
            if (priceInput) priceInput.value = '0.00';
        }

        if (warningDiv && qtyInp) {
            if (thisRowQty > currentAvail) {
                warningDiv.innerHTML = `<i class="bi bi-exclamation-triangle me-1"></i>Exceeds available stock (${currentAvail} pcs available)`;
                warningDiv.classList.remove('d-none');
                qtyInp.classList.add('is-invalid', 'border-danger');
            } else if (thisRowQty <= 0) {
                warningDiv.innerHTML = `<i class="bi bi-exclamation-triangle me-1"></i>Qty must be at least 1`;
                warningDiv.classList.remove('d-none');
                qtyInp.classList.add('is-invalid', 'border-danger');
            } else {
                warningDiv.innerHTML = '';
                warningDiv.classList.add('d-none');
                qtyInp.classList.remove('is-invalid', 'border-danger');
            }
        }

        Array.from(sel.options).forEach(opt => {
            if (!opt.value) return;
            const rId = String(opt.value);
            const rObj = availableSellerRolls.find(r => String(r.id) === rId);
            if (!rObj) return;

            const rOrigQty = parseInt(rObj.quantity || 0);
            const rTotalAvail = (rObj.available_quantity !== undefined && rObj.available_quantity !== null) ? parseInt(rObj.available_quantity) : rOrigQty;
            const rOtherUsed = (rId === rollId) ? otherRowsUsed : (rollUsageMap[rId] || 0);
            const rRemAvail = Math.max(0, rTotalAvail - rOtherUsed);

            let label = getRollBaseLabel(rObj);
            if (rRemAvail <= 0) {
                label += ' • [OUT OF STOCK - 0 pcs]';
            } else {
                label += ' • Available Stock: ' + rRemAvail + ' pcs';
            }
            opt.text = label;
            opt.setAttribute('data-avail-qty', rRemAvail);
        });
    });

    calculateTotalRollMetrics();
}

function calculateTotalRollMetrics() {
    let totalQty = 0;
    let totalWeight = 0;
    let totalPrice = 0;
    let count = 0;
    let firstRollId = '';

    document.querySelectorAll('.program-roll-card').forEach(card => {
        const select = card.querySelector('.roll-select');
        const qtyInp = card.querySelector('.roll-qty-input');
        const weightInp = card.querySelector('.roll-weight-input');
        const priceInp = card.querySelector('.roll-price-input');

        if (select && select.value) {
            count++;
            if (!firstRollId) firstRollId = select.value;
            totalQty += parseInt(qtyInp?.value) || 0;
            totalWeight += parseFloat(weightInp?.value) || 0;
            totalPrice += parseFloat(priceInp?.value) || 0;
        }
    });

    const avgRate = totalWeight > 0 ? (totalPrice / totalWeight) : 0;

    const dispCount = document.getElementById('dispTotalRollCount');
    if (dispCount) dispCount.innerText = count + ' roll(s) (' + totalQty + ' pcs)';
    const dispWeight = document.getElementById('dispTotalRollWeight');
    if (dispWeight) dispWeight.innerText = totalWeight.toFixed(2) + ' kg';
    const dispPrice = document.getElementById('dispTotalRollPrice');
    if (dispPrice) dispPrice.innerText = '₹' + totalPrice.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const dispAvg = document.getElementById('dispAvgRatePerKg');
    if (dispAvg) dispAvg.innerText = '₹' + avgRate.toFixed(2) + ' / kg';

    const hiddenRollId = document.getElementById('primary_seller_roll_id');
    const hiddenWeight = document.getElementById('total_roll_weight');
    const hiddenPrice = document.getElementById('total_roll_price');

    if (hiddenRollId) hiddenRollId.value = firstRollId;
    if (hiddenWeight) hiddenWeight.value = totalWeight.toFixed(2);
    if (hiddenPrice) hiddenPrice.value = totalPrice.toFixed(2);
}

// 3-Tier Dynamic Catalog Handlers Per Roll Row
function handleRollCategoryChange(counterId, catId) {
    const subcatSelect = document.getElementById('roll_subcat_' + counterId);
    const subSubcatSelect = document.getElementById('roll_subsubcat_' + counterId);

    if (!catId) {
        if (subcatSelect) {
            subcatSelect.innerHTML = '<option value="">-- Sub Category --</option>';
            subcatSelect.disabled = true;
        }
        if (subSubcatSelect) {
            subSubcatSelect.innerHTML = '<option value="">-- Sub Sub Category --</option>';
            subSubcatSelect.disabled = true;
        }
        updateRollCatalogBadge(counterId);
        return;
    }

    if (subcatSelect) {
        subcatSelect.disabled = false;
        subcatSelect.innerHTML = '<option value="">-- Loading Subcategories... --</option>';
    }
    if (subSubcatSelect) {
        subSubcatSelect.innerHTML = '<option value="">-- Sub Sub Category --</option>';
        subSubcatSelect.disabled = true;
    }

    fetch(`${baseUrl}/api/subcategories/${catId}`)
        .then(res => res.json())
        .then(res => {
            let options = '<option value="">-- Select Sub Category --</option>';
            if (res.success && res.data && res.data.length > 0) {
                res.data.forEach(item => {
                    options += `<option value="${item.id}">${escapeHtml(item.name)}</option>`;
                });
            } else if (Array.isArray(res) && res.length > 0) {
                res.forEach(item => {
                    options += `<option value="${item.id}">${escapeHtml(item.name)}</option>`;
                });
            } else {
                options = '<option value="">-- No Subcategories Found --</option>';
            }
            if (subcatSelect) subcatSelect.innerHTML = options;
            updateRollCatalogBadge(counterId);
        })
        .catch(err => {
            console.error('Error fetching subcategories for roll ' + counterId, err);
            if (subcatSelect) subcatSelect.innerHTML = '<option value="">-- No Subcategories --</option>';
            updateRollCatalogBadge(counterId);
        });
}

function handleRollSubcategoryChange(counterId, subcatId) {
    const subSubcatSelect = document.getElementById('roll_subsubcat_' + counterId);

    if (!subcatId) {
        if (subSubcatSelect) {
            subSubcatSelect.innerHTML = '<option value="">-- Sub Sub Category --</option>';
            subSubcatSelect.disabled = true;
        }
        updateRollCatalogBadge(counterId);
        return;
    }

    if (subSubcatSelect) {
        subSubcatSelect.disabled = false;
        subSubcatSelect.innerHTML = '<option value="">-- Loading Sub-Subcategories... --</option>';
    }

    fetch(`${baseUrl}/api/sub-subcategories/${subcatId}`)
        .then(res => res.json())
        .then(res => {
            let options = '<option value="">-- Select Sub Sub Category --</option>';
            if (res.success && res.data && res.data.length > 0) {
                res.data.forEach(item => {
                    options += `<option value="${item.id}">${escapeHtml(item.name)}</option>`;
                });
            } else if (Array.isArray(res) && res.length > 0) {
                res.forEach(item => {
                    options += `<option value="${item.id}">${escapeHtml(item.name)}</option>`;
                });
            } else {
                options = '<option value="">-- No Sub-Subcategories --</option>';
            }
            if (subSubcatSelect) subSubcatSelect.innerHTML = options;
            updateRollCatalogBadge(counterId);
        })
        .catch(err => {
            console.error('Error fetching sub-subcategories for roll ' + counterId, err);
            if (subSubcatSelect) subSubcatSelect.innerHTML = '<option value="">-- No Sub-Subcategories --</option>';
            updateRollCatalogBadge(counterId);
        });
}

function handleRollSubSubcategoryChange(counterId, subSubcatId) {
    updateRollCatalogBadge(counterId);
}

function loadRollSubcategories(counterId, catId, selectedSubcatId = '', selectedSubSubcatId = '') {
    const subcatSelect = document.getElementById('roll_subcat_' + counterId);
    if (!subcatSelect || !catId) return;

    subcatSelect.disabled = false;
    subcatSelect.innerHTML = '<option value="">-- Loading Subcategories... --</option>';

    fetch(`${baseUrl}/api/subcategories/${catId}`)
        .then(res => res.json())
        .then(res => {
            let options = '<option value="">-- Select Sub Category --</option>';
            const list = (res.success && res.data) ? res.data : (Array.isArray(res) ? res : []);
            if (list.length > 0) {
                list.forEach(item => {
                    const isSel = (selectedSubcatId && String(item.id) === String(selectedSubcatId)) ? 'selected' : '';
                    options += `<option value="${item.id}" ${isSel}>${escapeHtml(item.name)}</option>`;
                });
            } else {
                options = '<option value="">-- No Subcategories Found --</option>';
            }
            subcatSelect.innerHTML = options;

            if (selectedSubcatId) {
                loadRollSubSubcategories(counterId, selectedSubcatId, selectedSubSubcatId);
            } else {
                updateRollCatalogBadge(counterId);
            }
        })
        .catch(err => {
            console.error('Error loading subcategories:', err);
            subcatSelect.innerHTML = '<option value="">-- No Subcategories --</option>';
            updateRollCatalogBadge(counterId);
        });
}

function loadRollSubSubcategories(counterId, subcatId, selectedSubSubcatId = '') {
    const subSubcatSelect = document.getElementById('roll_subsubcat_' + counterId);
    if (!subSubcatSelect || !subcatId) return;

    subSubcatSelect.disabled = false;
    subSubcatSelect.innerHTML = '<option value="">-- Loading Sub-Subcategories... --</option>';

    fetch(`${baseUrl}/api/sub-subcategories/${subcatId}`)
        .then(res => res.json())
        .then(res => {
            let options = '<option value="">-- Select Sub Sub Category --</option>';
            const list = (res.success && res.data) ? res.data : (Array.isArray(res) ? res : []);
            if (list.length > 0) {
                list.forEach(item => {
                    const isSel = (selectedSubSubcatId && String(item.id) === String(selectedSubSubcatId)) ? 'selected' : '';
                    options += `<option value="${item.id}" ${isSel}>${escapeHtml(item.name)}</option>`;
                });
            } else {
                options = '<option value="">-- No Sub-Subcategories --</option>';
            }
            subSubcatSelect.innerHTML = options;
            updateRollCatalogBadge(counterId);
        })
        .catch(err => {
            console.error('Error loading sub-subcategories:', err);
            subSubcatSelect.innerHTML = '<option value="">-- No Sub-Subcategories --</option>';
            updateRollCatalogBadge(counterId);
        });
}

function updateRollCatalogBadge(counterId) {
    const catSelect = document.getElementById('roll_cat_' + counterId);
    const subcatSelect = document.getElementById('roll_subcat_' + counterId);
    const subSubcatSelect = document.getElementById('roll_subsubcat_' + counterId);
    const badge = document.getElementById('roll_cat_badge_' + counterId);

    if (!badge || !catSelect) return;

    const parts = [];
    if (catSelect.selectedIndex > 0) parts.push(catSelect.options[catSelect.selectedIndex].text);
    if (subcatSelect && subcatSelect.selectedIndex > 0 && subcatSelect.value) parts.push(subcatSelect.options[subcatSelect.selectedIndex].text);
    if (subSubcatSelect && subSubcatSelect.selectedIndex > 0 && subSubcatSelect.value) parts.push(subSubcatSelect.options[subSubcatSelect.selectedIndex].text);

    if (parts.length > 0) {
        badge.innerText = parts.join(' › ');
        badge.className = 'badge bg-primary-subtle text-primary border small roll-catalog-badge';
    } else {
        badge.innerText = 'No Catalog Selected';
        badge.className = 'badge bg-secondary-subtle text-secondary border small roll-catalog-badge';
    }
}

// MULTI-SELECT ROLLS MODAL HANDLERS
function openMultiRollModal() {
    const tableBody = document.getElementById('multiRollTableBody');
    if (!tableBody) return;

    // Determine which rolls are already assigned on page
    const currentlyAssignedRollIds = new Set();
    document.querySelectorAll('.program-roll-card .roll-select').forEach(sel => {
        if (sel.value) currentlyAssignedRollIds.add(String(sel.value));
    });

    let rowsHtml = '';
    if (availableSellerRolls && Array.isArray(availableSellerRolls)) {
        availableSellerRolls.forEach(r => {
            const origQty = parseInt(r.quantity || 0);
            const totalAvail = (r.available_quantity !== undefined && r.available_quantity !== null) ? parseInt(r.available_quantity) : origQty;
            const origWeight = parseFloat(r.weight || 0).toFixed(2);
            const origPrice = parseFloat(r.price || 0).toFixed(2);
            const isAssigned = currentlyAssignedRollIds.has(String(r.id));

            const searchString = [
                r.roll_number || ('Roll #' + r.id),
                r.material_type || 'Fabric',
                r.color || '',
                r.design_name || '',
                r.seller_name || '',
                r.seller_company || ''
            ].join(' ').toLowerCase();

            rowsHtml += `
                <tr class="multi-roll-item-row ${isAssigned ? 'table-light' : ''}" 
                    data-roll-id="${r.id}" 
                    data-search-text="${escapeHtml(searchString)}"
                    data-stock="${totalAvail}">
                    <td class="text-center">
                        <input type="checkbox" 
                               class="form-check-input multi-roll-checkbox" 
                               value="${r.id}" 
                               data-roll-id="${r.id}"
                               ${isAssigned ? 'checked disabled title="Already added to program"' : ''}
                               onchange="updateMultiSelectedCount()">
                    </td>
                    <td>
                        <div class="fw-bold text-dark">${escapeHtml(r.roll_number || ('Roll #' + r.id))}</div>
                        <small class="text-muted">${escapeHtml(r.material_type || 'Fabric')}</small>
                        ${isAssigned ? '<span class="badge bg-primary-subtle text-primary border ms-1" style="font-size: 10px;">Already in Program</span>' : ''}
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">${escapeHtml(r.color || '—')}</div>
                        ${r.design_name ? `<span class="badge bg-light text-secondary border small">[${escapeHtml(r.design_name)}]</span>` : ''}
                    </td>
                    <td>
                        <div class="small fw-medium text-dark">${escapeHtml(r.seller_name || '—')}</div>
                        ${r.seller_company ? `<div class="small text-muted" style="font-size: 11px;">${escapeHtml(r.seller_company)}</div>` : ''}
                    </td>
                    <td>
                        <span class="badge ${totalAvail > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'}">
                            ${totalAvail} pcs Avail
                        </span>
                        ${(parseInt(r.damaged_quantity) > 0 || parseFloat(r.damaged_weight) > 0) ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle d-block mt-1" style="font-size: 10px;"><i class="bi bi-exclamation-triangle me-1"></i>${r.damaged_quantity || 0} pcs Damaged</span>` : ''}
                    </td>
                    <td class="text-end">
                        <div class="fw-semibold text-dark small">${origWeight} kg</div>
                        <div class="text-success small fw-bold">₹${origPrice}</div>
                    </td>
                </tr>
            `;
        });
    }

    tableBody.innerHTML = rowsHtml || '<tr><td colspan="6" class="text-center py-4 text-muted">No seller fabric rolls found in database.</td></tr>';

    const searchInput = document.getElementById('multiRollSearchInput');
    if (searchInput) searchInput.value = '';
    filterMultiRollList();
    updateMultiSelectedCount();

    const modalEl = document.getElementById('multiRollSelectModal');
    if (modalEl && window.bootstrap && bootstrap.Modal) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function filterMultiRollList() {
    const searchInput = document.getElementById('multiRollSearchInput');
    const term = (searchInput ? searchInput.value : '').trim().toLowerCase();
    const rows = document.querySelectorAll('.multi-roll-item-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.getAttribute('data-search-text') || '';
        if (!term || text.includes(term)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const alertEl = document.getElementById('noMultiRollsAlert');
    if (alertEl) {
        alertEl.classList.toggle('d-none', visibleCount > 0 || rows.length === 0);
    }
    updateMultiSelectedCount();
}

function clearMultiRollSearch() {
    const searchInput = document.getElementById('multiRollSearchInput');
    if (searchInput) {
        searchInput.value = '';
        filterMultiRollList();
    }
}

function updateMultiSelectedCount() {
    const checkboxes = document.querySelectorAll('.multi-roll-checkbox:checked:not(:disabled)');
    const count = checkboxes.length;
    const badge = document.getElementById('multiSelectedCountBadge');
    const footer = document.getElementById('multiRollFooterSummary');
    if (badge) badge.textContent = `${count} Selected`;
    if (footer) footer.textContent = `${count} new roll(s) ready to add`;

    const visibleCheckboxes = Array.from(document.querySelectorAll('.multi-roll-item-row'))
        .filter(r => r.style.display !== 'none')
        .map(r => r.querySelector('.multi-roll-checkbox:not(:disabled)'))
        .filter(cb => cb !== null);

    const masterCheckbox = document.getElementById('masterMultiRollCheckbox');
    if (masterCheckbox && visibleCheckboxes.length > 0) {
        masterCheckbox.checked = visibleCheckboxes.every(cb => cb.checked);
        masterCheckbox.indeterminate = visibleCheckboxes.some(cb => cb.checked) && !masterCheckbox.checked;
    } else if (masterCheckbox) {
        masterCheckbox.checked = false;
        masterCheckbox.indeterminate = false;
    }
}

function toggleMasterMultiRoll(isChecked) {
    const visibleRows = Array.from(document.querySelectorAll('.multi-roll-item-row')).filter(r => r.style.display !== 'none');
    visibleRows.forEach(row => {
        const cb = row.querySelector('.multi-roll-checkbox:not(:disabled)');
        if (cb) cb.checked = isChecked;
    });
    updateMultiSelectedCount();
}

function selectAllFilteredRolls() {
    const visibleRows = Array.from(document.querySelectorAll('.multi-roll-item-row')).filter(r => r.style.display !== 'none');
    visibleRows.forEach(row => {
        const cb = row.querySelector('.multi-roll-checkbox:not(:disabled)');
        if (cb) cb.checked = true;
    });
    updateMultiSelectedCount();
}

function deselectAllMultiRolls() {
    document.querySelectorAll('.multi-roll-checkbox:not(:disabled)').forEach(cb => cb.checked = false);
    updateMultiSelectedCount();
}

function confirmMultiRollSelection() {
    const selectedCheckboxes = document.querySelectorAll('.multi-roll-checkbox:checked:not(:disabled)');
    if (selectedCheckboxes.length === 0) {
        alert('Please select at least one fabric roll using the checkboxes.');
        return;
    }

    const selectedRollIds = Array.from(selectedCheckboxes).map(cb => cb.value);

    // Check for existing cards
    const existingCards = Array.from(document.querySelectorAll('.program-roll-card'));
    const existingAssignedIds = new Set();
    let emptyCard = null;

    existingCards.forEach(card => {
        const sel = card.querySelector('.roll-select');
        if (sel && sel.value) {
            existingAssignedIds.add(String(sel.value));
        } else if (sel && !sel.value && !emptyCard) {
            emptyCard = card;
        }
    });

    selectedRollIds.forEach(rollId => {
        if (existingAssignedIds.has(String(rollId))) {
            return; // already assigned
        }

        const rollObj = availableSellerRolls.find(r => String(r.id) === String(rollId));
        if (!rollObj) return;

        if (emptyCard) {
            // Populate the empty initial card
            const cardIdMatch = emptyCard.id.match(/\d+$/);
            const idx = cardIdMatch ? parseInt(cardIdMatch[0]) : null;
            const sel = emptyCard.querySelector('.roll-select');
            if (sel && idx) {
                sel.value = rollId;
                handleRollRowChange(sel, idx, true);
                emptyCard = null;
                existingAssignedIds.add(String(rollId));
                return;
            }
        }

        // Add a new roll row
        addProgramRollRow({
            seller_roll_id: rollId,
            roll_qty: 1,
            roll_weight: rollObj.weight,
            roll_price: rollObj.price
        });
        existingAssignedIds.add(String(rollId));
    });

    updateRollIndices();
    calculateTotalRollMetrics();
    syncRollStockAllocations();

    const modalEl = document.getElementById('multiRollSelectModal');
    if (modalEl && window.bootstrap && bootstrap.Modal) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
    }
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

document.addEventListener('DOMContentLoaded', function() {
    if (existingProgramRolls && existingProgramRolls.length > 0) {
        existingProgramRolls.forEach(r => addProgramRollRow(r));
    } else {
        addProgramRollRow();
    }

    // Form submission validation
    const form = document.getElementById('programEditForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const nameInput = document.getElementById('program_name');
            if (!nameInput || !nameInput.value.trim()) {
                e.preventDefault();
                alert('Program Name is required. Please enter a descriptive title.');
                nameInput?.focus();
                return false;
            }

            const cards = document.querySelectorAll('.program-roll-card');
            let hasValidRoll = false;

            for (let i = 0; i < cards.length; i++) {
                const card = cards[i];
                const sel = card.querySelector('.roll-select');
                const qtyInp = card.querySelector('.roll-qty-input');

                if (sel && sel.value) {
                    hasValidRoll = true;
                    const opt = sel.options[sel.selectedIndex];
                    const availQty = parseFloat(opt.getAttribute('data-avail-qty')) || parseFloat(opt.getAttribute('data-qty')) || 0;
                    const enteredQty = parseFloat(qtyInp?.value) || 0;

                    if (enteredQty <= 0) {
                        e.preventDefault();
                        alert(`Roll #${i + 1}: Quantity must be at least 1 pcs.`);
                        qtyInp?.focus();
                        return false;
                    }

                    if (availQty > 0 && enteredQty > availQty) {
                        e.preventDefault();
                        alert(`Roll #${i + 1}: Quantity cannot exceed available roll stock (${availQty} pcs).`);
                        qtyInp?.focus();
                        return false;
                    }
                }
            }

            if (!hasValidRoll) {
                e.preventDefault();
                alert('Please select at least one Raw Material Roll for this program.');
                return false;
            }
        });
    }
});
</script>
