<?php
$canDeleteCutting = hasPermission('cutting', 'delete');
?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-scissors me-2 text-info"></i>Edit Cutting Batch — <?= e($batch['name'] ?: $batch['batch_number']) ?></h2>
        <span class="text-muted">Update cut pieces, fabric rolls with per-roll 3-tier catalog, wastage & fabric cost analytics</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('production/cutting') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Cutting Batches
        </a>
    </div>
</div>

<form method="POST" action="<?= url('production/cutting/update/' . $batch['id']) ?>" id="editCuttingForm">
    <?= CSRF::field() ?>

    <input type="hidden" name="production_unit_id" value="<?= e($batch['production_unit_id'] ?? '') ?>">
    <input type="hidden" name="wastage_cost" id="wastageCost" value="<?= e($batch['wastage_cost'] ?? '0.00') ?>">

    <div class="row g-4">
        <!-- Left Column: Batch & Fabric Specs -->
        <div class="col-lg-5">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-card-checklist me-2 text-primary"></i>1. Batch Details
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label required fw-semibold">Cutting Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="cuttingName" class="form-control text-uppercase fw-bold" value="<?= e($batch['name']) ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Batch Number</label>
                            <input type="text" class="form-control bg-light fw-bold" value="<?= e($batch['batch_number']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="IN_PROGRESS" <?= $batch['status'] === 'IN_PROGRESS' ? 'selected' : '' ?>>In Progress</option>
                                <option value="PENDING" <?= $batch['status'] === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                                <option value="COMPLETED" <?= $batch['status'] === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                    </div>

                    <!-- Program Summary Box -->
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-primary"><?= e($batch['program_number'] ?? 'PRG') ?></span>
                            <span class="text-muted small"><i class="bi bi-building me-1"></i><?= e($batch['unit_name'] ?? 'Production Unit') ?></span>
                        </div>
                        <div class="fw-bold text-dark fs-6 mb-1"><?= e($batch['product_name'] ?? 'Apparel Program') ?></div>
                    </div>

                    <!-- Roll Details Box (Multi-Roll Support with Per-Roll Catalog) -->
                    <input type="hidden" name="seller_roll_id" id="inputSellerRollId" value="<?= e($batch['seller_roll_id'] ?? '') ?>">
                    <div id="rollDetailsBox" class="mb-3 p-3 bg-white border rounded shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">
                                <i class="bi bi-layers-fill text-info me-1"></i>Fabric Roll(s) &amp; Catalog Allocation
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-info-subtle text-info border" id="rollStatusBadge">Roll Selected</span>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddRollRow" onclick="addCuttingRollRow()">
                                    <i class="bi bi-plus-lg me-1"></i> Add Roll
                                </button>
                            </div>
                        </div>

                        <!-- Dynamic Multi-Roll Rows Container -->
                        <div id="cuttingRollsContainer" class="d-flex flex-column gap-3 mb-2">
                            <!-- Populated dynamically via JavaScript with independent Catalog dropdowns -->
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cutting Master / Operator</label>
                        <select name="operator_id" class="form-select">
                            <option value="">Select Operator</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($batch['operator_id'] == $u['id']) ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes & Remarks</label>
                        <textarea name="notes" class="form-control" rows="2"><?= e($batch['notes']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Fabric Wastage & Cost Analytics -->
            <div class="card shadow-sm border-0 border-top border-4 border-warning mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-pie-chart me-2 text-warning"></i>2. Fabric Wastage & Cost Analytics
                        </h5>
                        <small class="text-muted">Calculate multi-roll fabric wastage, rate per kg & exact wastage costs</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold" id="btnAddWastageRollBtn" onclick="addCuttingRollRow()">
                            <i class="bi bi-plus-lg me-1"></i> Add Roll
                        </button>
                        <?php 
                            $rW = (float)$batch['roll_weight'];
                            $rP = (float)$batch['roll_price'];
                            $rRate = $rW > 0 ? ($rP / $rW) : 0;
                            $wW = (float)$batch['wastage_weight'];
                            $wCost = (float)($batch['wastage_cost'] ?? ($wW * $rRate));
                        ?>
                        <span class="badge bg-warning-subtle text-dark border fw-bold" id="badgeRatePerKg">Rate: ₹<?= number_format($rRate, 2) ?> / kg</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Total Roll Weight (kg)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" name="roll_weight" id="rollWeight" class="form-control fw-bold bg-light" value="<?= number_format((float)$batch['roll_weight'], 2, '.', '') ?>" readonly style="cursor: not-allowed;">
                                <span class="input-group-text bg-light">kg</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Total Roll Price (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">₹</span>
                                <input type="number" step="0.01" min="0" name="roll_price" id="rollPrice" class="form-control fw-bold text-primary bg-light" value="<?= number_format((float)$batch['roll_price'], 2, '.', '') ?>" readonly style="cursor: not-allowed;">
                            </div>
                        </div>
                    </div>

                    <!-- Multi-Roll Individual Wastage Section -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold text-danger mb-0">
                                <i class="bi bi-trash3 me-1"></i>Fabric Roll Wastage Breakdown
                            </label>
                            <span class="badge bg-danger-subtle text-danger border" id="dispWastageCostBadge">Wastage Cost: ₹<?= number_format($wCost, 2) ?></span>
                        </div>

                        <!-- Dynamic Per-Roll Wastage Inputs Container -->
                        <div id="rollsWastageContainer" class="d-flex flex-column gap-2 mb-3">
                            <!-- Populated dynamically for each active fabric roll -->
                        </div>
                    </div>

                    <!-- Total Wastage Summary Box -->
                    <div class="mb-4 p-3 bg-danger-subtle bg-opacity-10 border border-danger-subtle rounded">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label required fw-bold text-danger mb-0">
                                Total Wastage Fabric Weight (kg)
                            </label>
                            <span class="fw-bold text-danger fs-6" id="dispTotalWastageCostSum">Total Cost: ₹<?= number_format($wCost, 2) ?></span>
                        </div>
                        <div class="input-group input-group-lg">
                            <input type="number" step="0.01" min="0" name="wastage_weight" id="wastageWeight" class="form-control border-danger text-danger fw-bold" value="<?= number_format((float)$batch['wastage_weight'], 2, '.', '') ?>" oninput="handleManualTotalWastageInput(this.value)" required>
                            <span class="input-group-text bg-danger text-white fw-bold">kg</span>
                        </div>
                        <small class="text-muted">Total end cuts, scraps & defective cloth remaining across all selected rolls.</small>
                    </div>

                    <!-- Computed Real-time Efficiency Metrics (6 Metric Grid) -->
                    <div class="p-3 bg-light rounded border">
                        <div class="row g-3 text-center">
                            <div class="col-6 col-sm-4 border-end pb-2">
                                <div class="text-muted small">Fabric Rate</div>
                                <div class="fw-bold fs-6 text-dark mt-1" id="dispRatePerKg">₹<?= number_format($rRate, 2) ?> / kg</div>
                            </div>
                            <div class="col-6 col-sm-4 border-end pb-2">
                                <div class="text-muted small">Wastage Cost</div>
                                <div class="fw-bold fs-6 text-danger mt-1" id="dispWastageCost">₹<?= number_format($wCost, 2) ?></div>
                            </div>
                            <div class="col-6 col-sm-4 pb-2">
                                <div class="text-muted small">Wastage %</div>
                                <?php $wPct = $batch['roll_weight'] > 0 ? (($batch['wastage_weight'] / $batch['roll_weight']) * 100) : 0; ?>
                                <div class="fw-bold fs-6 text-warning mt-1" id="dispWastagePercent"><?= number_format($wPct, 1) ?>%</div>
                            </div>
                            <div class="col-6 col-sm-4 border-end pt-2 border-top">
                                <div class="text-muted small">Used Weight</div>
                                <div class="fw-bold fs-6 text-success mt-1" id="dispUsedWeight"><?= number_format((float)$batch['used_weight'], 2) ?> kg</div>
                            </div>
                            <div class="col-6 col-sm-4 border-end pt-2 border-top">
                                <div class="text-muted small">Total Cut Qty</div>
                                <div class="fw-bold fs-6 text-primary mt-1" id="dispTotalCut"><?= number_format((int)$batch['cut_qty']) ?> pcs</div>
                            </div>
                            <div class="col-6 col-sm-4 pt-2 border-top">
                                <div class="text-muted small">Fabric Cost / Pc</div>
                                <div class="fw-bold fs-6 text-success mt-1" id="dispCostPerPiece">₹<?= number_format((float)$batch['cost_per_piece'], 2) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Variant Quantities Breakdown -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap gap-2 py-3 border-bottom">
                    <div>
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-grid-3x3-gap me-2 text-info"></i>3. Variant Cutting Quantities
                        </h5>
                        <small class="text-muted">Enter actual cut pieces per variant</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCustomVariantRow()">
                            <i class="bi bi-plus-lg me-1"></i> Add Row
                        </button>
                        <span class="badge bg-success fs-6 px-3 py-2" id="totalCutBadge"><?= number_format((int)$batch['cut_qty']) ?> Cut pcs</span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <!-- Roll Navigation Tabs / Slide Bar for Clean Multi-Roll View -->
                    <div id="rollVariantTabsContainer" class="p-2 px-3 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2" style="display: none;">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="small fw-bold text-secondary text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                                <i class="bi bi-layers-half text-primary me-1"></i>Select Roll:
                            </span>
                            <div class="btn-group btn-group-sm" role="group" id="rollVariantNavTabs">
                                <!-- Populated dynamically: [Roll #1: LIGHT RED] [Roll #2: SKY BLUE] [All Rolls] -->
                            </div>
                        </div>
                        <div class="small text-muted" id="activeRollInfoLabel"></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="variantsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 180px;">Size / Color Variant</th>
                                    <th style="width: 160px;">Quantity (pcs)</th>
                                    <th style="width: <?= $canDeleteCutting ? '50px' : '10px' ?>;"></th>
                                </tr>
                            </thead>
                            <tbody id="variantsTableBody">
                                <?php $counter = 0; ?>
                                <?php foreach ($items as $it): ?>
                                    <?php $counter++; ?>
                                    <tr id="variant_row_<?= $counter ?>">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="text" name="items[<?= $counter ?>][size_name]" class="form-control form-control-sm fw-bold text-uppercase bg-light" style="max-width: 90px; cursor: not-allowed;" value="<?= e($it['size_name']) ?>" readonly>
                                                <span class="text-muted">/</span>
                                                <input type="text" name="items[<?= $counter ?>][color_name]" class="form-control form-control-sm text-uppercase bg-light" style="max-width: 140px; cursor: not-allowed;" value="<?= e($it['color_name']) ?>" readonly>
                                                <input type="hidden" name="items[<?= $counter ?>][variant_id]" value="<?= e($it['variant_id']) ?>">
                                            </div>
                                            <?php if (!empty($it['sku'])): ?>
                                                <small class="text-muted"><code><?= e($it['sku']) ?></code></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="number" name="items[<?= $counter ?>][planned_qty]" class="form-control form-control-sm planned-qty-input fw-bold border-primary text-primary" min="0" value="<?= (int)($it['planned_qty'] ?: $it['cut_qty']) ?>" oninput="calculateMetrics()">
                                            <input type="hidden" name="items[<?= $counter ?>][cut_qty]" class="cut-qty-input" value="<?= (int)($it['cut_qty'] ?: $it['planned_qty']) ?>">
                                            <input type="hidden" name="items[<?= $counter ?>][rejected_qty]" class="rej-qty-input" value="<?= (int)$it['rejected_qty'] ?>">
                                        </td>
                                        <td class="text-end">
                                            <?php if ($canDeleteCutting): ?>
                                            <button type="button" class="btn btn-sm text-danger p-0" onclick="removeVariantRow(<?= $counter ?>)" title="Remove row">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center border-top">
                    <a href="<?= url('production/cutting') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Update Cutting Batch
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
const canDeleteCutting = <?= $canDeleteCutting ? 'true' : 'false' ?>;
let variantCounter = <?= $counter ?? 0 ?>;
let cuttingRollCounter = 0;
let availableRolls = <?= json_encode($rolls ?? []) ?>;
const allCategories = <?= json_encode($categories ?? []) ?>;
let initialBatchRolls = <?= json_encode($batchRolls ?? []) ?>;
let initialItems = <?= json_encode($items ?? []) ?>;

function buildRollOptionsHtml(selectedRollId = '') {
    let html = '<option value="">-- Select Fabric Roll --</option>';
    if (availableRolls && availableRolls.length > 0) {
        availableRolls.forEach(r => {
            const w = r.weight !== undefined ? r.weight : (r.weight_kg || 0);
            const p = r.price !== undefined ? r.price : (r.price_total || 0);
            const isSel = (selectedRollId && String(r.id) === String(selectedRollId)) ? 'selected' : '';
            html += `<option value="${r.id}" 
                data-weight="${w}" 
                data-price="${p}" 
                data-color="${escapeHtml(r.color || '')}" 
                data-design="${escapeHtml(r.design_name || '')}" 
                data-material="${escapeHtml(r.material_type || '')}" 
                data-seller="${escapeHtml(r.seller_name || '')}" 
                ${isSel}>
                Roll #${escapeHtml(r.roll_number || r.id)} (${escapeHtml(r.color || 'Standard')} - ${escapeHtml(r.design_name || 'Plain')}) — ${parseFloat(w).toFixed(2)} kg / ₹${parseFloat(p).toFixed(2)}
            </option>`;
        });
    }
    return html;
}

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

function addCuttingRollRow(rollData = null) {
    cuttingRollCounter++;
    const idx = cuttingRollCounter;
    const container = document.getElementById('cuttingRollsContainer');
    if (!container) return;

    const rowDiv = document.createElement('div');
    rowDiv.className = 'p-3 border rounded bg-light cutting-roll-row mb-2 shadow-sm';
    rowDiv.id = 'cutting_roll_row_' + idx;

    const batchCatId = '<?= $batch['category_id'] ?? '' ?>';
    const batchSubcatId = '<?= $batch['subcategory_id'] ?? '' ?>';
    const batchSubSubcatId = '<?= $batch['sub_subcategory_id'] ?? '' ?>';

    const rollId = rollData ? (rollData.seller_roll_id || rollData.id || '') : '';
    const weight = rollData ? parseFloat(rollData.roll_weight || rollData.weight || 0).toFixed(2) : '0.00';
    const price = rollData ? parseFloat(rollData.roll_price || rollData.price || 0).toFixed(2) : '0.00';
    const color = rollData ? (rollData.roll_color || rollData.color || '') : '';
    const design = rollData ? (rollData.roll_design || rollData.design_name || '') : '';
    const catId = rollData ? (rollData.category_id || batchCatId) : batchCatId;
    const subcatId = rollData ? (rollData.subcategory_id || batchSubcatId) : batchSubcatId;
    const subSubcatId = rollData ? (rollData.sub_subcategory_id || batchSubSubcatId) : batchSubSubcatId;
    const initWastage = rollData ? parseFloat(rollData.wastage_weight || 0).toFixed(2) : '0.00';

    rowDiv.setAttribute('data-init-wastage', initWastage);

    const optionsHtml = buildRollOptionsHtml(rollId);
    const categoryOptionsHtml = buildCategoryOptionsHtml(catId);

    rowDiv.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
            <span class="fw-bold text-dark small"><i class="bi bi-layers text-info me-1"></i>Fabric Roll #${idx}</span>
            ${canDeleteCutting ? `
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" title="Remove Roll" onclick="removeCuttingRollRow(this)">
                <i class="bi bi-trash"></i> Remove
            </button>
            ` : ''}
        </div>
        <div class="row g-2 align-items-center mb-2">
            <div class="col-12 col-md-6">
                <label class="form-label small text-muted mb-1">Fabric Roll</label>
                <select name="rolls[${idx}][seller_roll_id]" class="form-select form-select-sm border-info cutting-roll-select" onchange="handleCuttingRollRowChange(this)">
                    ${optionsHtml}
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted mb-1">Weight (kg)</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" min="0" name="rolls[${idx}][roll_weight]" class="form-control text-end cutting-roll-weight bg-light fw-bold" value="${weight}" readonly style="cursor: not-allowed;" oninput="calculateRollTotals()">
                    <span class="input-group-text bg-light">kg</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted mb-1">Price (₹)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">₹</span>
                    <input type="number" step="0.01" min="0" name="rolls[${idx}][roll_price]" class="form-control text-end cutting-roll-price bg-light fw-bold text-primary" value="${price}" readonly style="cursor: not-allowed;" oninput="calculateRollTotals()">
                </div>
            </div>
        </div>
        <div class="row g-2 small text-muted mb-2 px-1">
            <div class="col-6"><strong>Color:</strong> <span class="roll-color-label">${color ? escapeHtml(color) : '-'}</span></div>
            <div class="col-6"><strong>Design:</strong> <span class="roll-design-label">${design ? escapeHtml(design) : '-'}</span></div>
        </div>

        <!-- 3-Tier Catalog Selection for THIS specific Roll (Locked & Disabled with full visibility) -->
        <div class="p-2 bg-light rounded border mt-2">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-semibold text-primary"><i class="bi bi-diagram-3 me-1"></i>Catalog Hierarchy for this Roll</span>
                <span class="badge bg-secondary-subtle text-secondary border small roll-catalog-badge">Catalog</span>
            </div>
            <div class="row g-2">
                <div class="col-12 mb-1">
                    <label class="form-label small text-muted mb-0" style="font-size: 11px;">Main Category</label>
                    <select name="rolls[${idx}][category_id]" class="form-select form-select-sm fw-semibold roll-cat-select bg-light text-dark" style="cursor: not-allowed; pointer-events: none;" tabindex="-1" onchange="handleRollCategoryChange(${idx}, this.value)">
                        ${categoryOptionsHtml}
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small text-muted mb-0" style="font-size: 11px;">Sub Category</label>
                    <select name="rolls[${idx}][subcategory_id]" class="form-select form-select-sm roll-subcat-select bg-light text-dark" style="cursor: not-allowed; pointer-events: none;" tabindex="-1" onchange="handleRollSubcategoryChange(${idx}, this.value)">
                        <option value="">-- Sub Category --</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small text-muted mb-0" style="font-size: 11px;">Sub Sub Category</label>
                    <select name="rolls[${idx}][sub_subcategory_id]" class="form-select form-select-sm roll-subsubcat-select bg-light text-dark" style="cursor: not-allowed; pointer-events: none;" tabindex="-1" onchange="handleRollSubSubcategoryChange(${idx}, this.value)">
                        <option value="">-- Sub Sub Category --</option>
                    </select>
                </div>
            </div>
        </div>
    `;

    container.appendChild(rowDiv);

    if (catId) {
        loadRollSubcategories(idx, catId, subcatId, subSubcatId);
    } else {
        updateRollCatalogBadge(idx);
    }

    calculateRollTotals();
}

function handleCuttingRollRowChange(select) {
    const row = select.closest('.cutting-roll-row');
    if (!row) return;

    const opt = select.options[select.selectedIndex];
    if (opt && opt.value) {
        const w = parseFloat(opt.getAttribute('data-weight') || 0);
        const p = parseFloat(opt.getAttribute('data-price') || 0);
        const color = opt.getAttribute('data-color') || '';
        const design = opt.getAttribute('data-design') || '';

        const weightInput = row.querySelector('.cutting-roll-weight');
        const priceInput = row.querySelector('.cutting-roll-price');
        const colorLabel = row.querySelector('.roll-color-label');
        const designLabel = row.querySelector('.roll-design-label');

        if (weightInput) weightInput.value = w.toFixed(2);
        if (priceInput) priceInput.value = p.toFixed(2);
        if (colorLabel) colorLabel.innerText = color || '-';
        if (designLabel) designLabel.innerText = design || '-';
    } else {
        const colorLabel = row.querySelector('.roll-color-label');
        const designLabel = row.querySelector('.roll-design-label');
        if (colorLabel) colorLabel.innerText = '-';
        if (designLabel) designLabel.innerText = '-';
    }

    calculateRollTotals();
    regenerateAllRollVariants();
}

function removeCuttingRollRow(btn) {
    if (!canDeleteCutting) return;
    const row = btn.closest('.cutting-roll-row');
    if (row) {
        row.remove();
        const container = document.getElementById('cuttingRollsContainer');
        if (container && container.querySelectorAll('.cutting-roll-row').length === 0) {
            addCuttingRollRow();
        }
        calculateRollTotals();
        regenerateAllRollVariants();
    }
}

function calculateRollTotals() {
    let totalWeight = 0;
    let totalPrice = 0;
    let rollCount = 0;
    let firstRollId = '';

    document.querySelectorAll('.cutting-roll-row').forEach(row => {
        const select = row.querySelector('.cutting-roll-select');
        const weightInput = row.querySelector('.cutting-roll-weight');
        const priceInput = row.querySelector('.cutting-roll-price');

        if (select && select.value) {
            rollCount++;
            if (!firstRollId) firstRollId = select.value;
            const w = parseFloat(weightInput ? weightInput.value : 0) || 0;
            const p = parseFloat(priceInput ? priceInput.value : 0) || 0;
            totalWeight += w;
            totalPrice += p;
        }
    });

    if (document.getElementById('inputSellerRollId')) {
        document.getElementById('inputSellerRollId').value = firstRollId;
    }
    if (document.getElementById('rollWeight')) {
        document.getElementById('rollWeight').value = totalWeight.toFixed(2);
    }
    if (document.getElementById('rollPrice')) {
        document.getElementById('rollPrice').value = totalPrice.toFixed(2);
    }

    const badge = document.getElementById('rollStatusBadge');
    if (badge) {
        badge.innerText = rollCount > 0 ? `${rollCount} Roll(s) (${totalWeight.toFixed(2)} kg)` : 'Choose Roll';
    }

    renderRollWastageRows();
    calculateMetrics();
}

function renderRollWastageRows() {
    const rollsContainer = document.getElementById('cuttingRollsContainer');
    const wastageContainer = document.getElementById('rollsWastageContainer');
    if (!rollsContainer || !wastageContainer) return;

    const rollRows = rollsContainer.querySelectorAll('.cutting-roll-row');
    if (rollRows.length === 0) {
        wastageContainer.innerHTML = '<div class="text-muted small py-1 fst-italic">No fabric rolls added yet.</div>';
        return;
    }

    // Capture existing entered wastage values before rerendering
    const existingWastageValues = {};
    wastageContainer.querySelectorAll('.roll-wastage-input').forEach(inp => {
        const rowId = inp.getAttribute('data-row-id');
        if (rowId) {
            existingWastageValues[rowId] = inp.value;
        }
    });

    wastageContainer.innerHTML = '';

    rollRows.forEach((row, rIndex) => {
        const idx = row.id.replace('cutting_roll_row_', '');
        const select = row.querySelector('.cutting-roll-select');
        const weightInp = row.querySelector('.cutting-roll-weight');
        const priceInp = row.querySelector('.cutting-roll-price');
        const colorLabel = row.querySelector('.roll-color-label');
        const designLabel = row.querySelector('.roll-design-label');

        const rollOpt = select && select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
        let rawRollNum = rollOpt ? (rollOpt.text.split('(')[0].trim()) : String(rIndex + 1);
        let rollNum = rawRollNum.replace(/^(roll\s*#?|#)\s*/i, '').trim() || (rIndex + 1);
        const color = (colorLabel && colorLabel.innerText !== '-') ? colorLabel.innerText : (rollOpt ? (rollOpt.getAttribute('data-color') || 'Standard') : 'Standard');
        const design = (designLabel && designLabel.innerText !== '-') ? designLabel.innerText : (rollOpt ? (rollOpt.getAttribute('data-design') || 'Plain') : 'Plain');

        const weight = parseFloat(weightInp ? weightInp.value : 0) || 0;
        const price = parseFloat(priceInp ? priceInp.value : 0) || 0;
        const rate = weight > 0 ? (price / weight) : 0;

        const initW = row.getAttribute('data-init-wastage') || '0.00';
        const savedVal = existingWastageValues[row.id] !== undefined ? existingWastageValues[row.id] : initW;

        const card = document.createElement('div');
        card.className = 'p-2 border rounded bg-white shadow-sm roll-wastage-card';
        card.id = 'roll_wastage_row_' + idx;
        card.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-bold small text-dark text-truncate me-2">
                    <i class="bi bi-layers text-info me-1"></i>Roll #${escapeHtml(rollNum)} (${escapeHtml(color)} - ${escapeHtml(design)})
                </span>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger-subtle text-danger border small roll-wastage-cost-badge" id="roll_wastage_cost_badge_${idx}">Cost: ₹0.00</span>
                    ${canDeleteCutting ? `
                    <button type="button" class="btn btn-sm btn-outline-danger p-0 px-1 border-0" title="Delete / Remove this Roll" onclick="deleteRollFromWastage('${row.id}')">
                        <i class="bi bi-trash text-danger"></i>
                    </button>
                    ` : ''}
                </div>
            </div>
            <div class="row g-2 align-items-center">
                <div class="col-6 col-md-5">
                    <small class="text-muted d-block" style="font-size: 11px;">Weight &amp; Rate</small>
                    <span class="small fw-semibold text-dark">${weight.toFixed(2)} kg <span class="text-muted">(@ ₹${rate.toFixed(2)}/kg)</span></span>
                </div>
                <div class="col-6 col-md-7">
                    <small class="text-danger fw-semibold d-block" style="font-size: 11px;">Wastage Weight (kg)</small>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.01" min="0" max="${weight}" name="rolls[${idx}][wastage_weight]" id="roll_wastage_input_${idx}" data-row-id="${row.id}" data-rate="${rate}" data-weight="${weight}" class="form-control text-end border-danger roll-wastage-input fw-bold text-danger" value="${savedVal}" oninput="calculateMetrics()" placeholder="0.00">
                        <span class="input-group-text bg-danger-subtle text-danger">kg</span>
                    </div>
                </div>
            </div>
        `;
        wastageContainer.appendChild(card);
    });
}

function deleteRollFromWastage(rowId) {
    if (!canDeleteCutting) return;
    const row = document.getElementById(rowId);
    if (row) {
        row.remove();
        const container = document.getElementById('cuttingRollsContainer');
        if (container && container.querySelectorAll('.cutting-roll-row').length === 0) {
            addCuttingRollRow();
        }
        calculateRollTotals();
        regenerateAllRollVariants();
    }
}

function handleManualTotalWastageInput(totalVal) {
    const totalW = parseFloat(totalVal) || 0;
    const wastageInputs = document.querySelectorAll('.roll-wastage-input');
    if (wastageInputs.length === 1) {
        wastageInputs[0].value = totalW.toFixed(2);
    }
    calculateMetrics();
}

function handleRollCategoryChange(rowIdx, categoryId) {
    const row = document.getElementById('cutting_roll_row_' + rowIdx);
    if (!row) return;

    const subcatSelect = row.querySelector('.roll-subcat-select');
    const subSubcatSelect = row.querySelector('.roll-subsubcat-select');
    
    subcatSelect.innerHTML = '<option value="">-- Sub Category --</option>';
    subSubcatSelect.innerHTML = '<option value="">-- Sub Sub Category --</option>';

    updateRollCatalogBadge(rowIdx);

    if (!categoryId) {
        regenerateAllRollVariants();
        return;
    }

    loadRollSubcategories(rowIdx, categoryId, null, null);
}

function loadRollSubcategories(rowIdx, categoryId, selectedSubcatId, selectedSubSubcatId) {
    const row = document.getElementById('cutting_roll_row_' + rowIdx);
    if (!row) return;

    fetch('<?= url('api/subcategories/') ?>' + categoryId)
        .then(res => res.json())
        .then(data => {
            const subcatSelect = row.querySelector('.roll-subcat-select');
            subcatSelect.innerHTML = '<option value="">-- Sub Category --</option>';
            if (data && Array.isArray(data) && data.length > 0) {
                data.forEach(sc => {
                    const opt = document.createElement('option');
                    opt.value = sc.id;
                    opt.textContent = sc.name;
                    if (selectedSubcatId && String(sc.id) === String(selectedSubcatId)) {
                        opt.selected = true;
                    }
                    subcatSelect.appendChild(opt);
                });

                if (selectedSubcatId) {
                    loadRollSubSubcategories(rowIdx, selectedSubcatId, selectedSubSubcatId);
                } else {
                    regenerateAllRollVariants();
                }
            } else {
                regenerateAllRollVariants();
            }
            updateRollCatalogBadge(rowIdx);
        })
        .catch(err => console.error('Error loading subcategories for roll ' + rowIdx + ':', err));
}

function handleRollSubcategoryChange(rowIdx, subcategoryId) {
    const row = document.getElementById('cutting_roll_row_' + rowIdx);
    if (!row) return;

    const subSubcatSelect = row.querySelector('.roll-subsubcat-select');
    subSubcatSelect.innerHTML = '<option value="">-- Sub Sub Category --</option>';

    updateRollCatalogBadge(rowIdx);

    if (!subcategoryId) {
        regenerateAllRollVariants();
        return;
    }

    loadRollSubSubcategories(rowIdx, subcategoryId, null);
}

function loadRollSubSubcategories(rowIdx, subcategoryId, selectedSubSubcatId) {
    const row = document.getElementById('cutting_roll_row_' + rowIdx);
    if (!row) return;

    fetch('<?= url('api/sub-subcategories/') ?>' + subcategoryId)
        .then(res => res.json())
        .then(data => {
            const subSubcatSelect = row.querySelector('.roll-subsubcat-select');
            subSubcatSelect.innerHTML = '<option value="">-- Sub Sub Category --</option>';
            if (data && Array.isArray(data) && data.length > 0) {
                data.forEach(ssc => {
                    const opt = document.createElement('option');
                    opt.value = ssc.id;
                    opt.textContent = ssc.name;
                    if (selectedSubSubcatId && String(ssc.id) === String(selectedSubSubcatId)) {
                        opt.selected = true;
                    }
                    subSubcatSelect.appendChild(opt);
                });
            }
            updateRollCatalogBadge(rowIdx);
            regenerateAllRollVariants();
        })
        .catch(err => console.error('Error loading sub subcategories for roll ' + rowIdx + ':', err));
}

function handleRollSubSubcategoryChange(rowIdx, subSubcategoryId) {
    updateRollCatalogBadge(rowIdx);
    regenerateAllRollVariants();
}

// Regenerate variants across all active rolls with catalog selection
function regenerateAllRollVariants() {
    const rows = document.querySelectorAll('.cutting-roll-row');
    const activeRollRequests = [];

    rows.forEach((row, rIndex) => {
        const rollSelect = row.querySelector('.cutting-roll-select');
        const catSelect = row.querySelector('.roll-cat-select');
        const subcatSelect = row.querySelector('.roll-subcat-select');
        const subSubcatSelect = row.querySelector('.roll-subsubcat-select');
        const colorLabel = row.querySelector('.roll-color-label');
        const designLabel = row.querySelector('.roll-design-label');

        const rollId = rollSelect ? rollSelect.value : '';
        const rollOpt = rollSelect && rollSelect.selectedIndex >= 0 ? rollSelect.options[rollSelect.selectedIndex] : null;
        const rollNumber = rollOpt ? (rollOpt.text.split('(')[0].replace('Roll #', '').trim()) : (rIndex + 1);
        const rollColor = (colorLabel && colorLabel.innerText !== '-') ? colorLabel.innerText : (rollOpt ? (rollOpt.getAttribute('data-color') || '') : '');
        const rollDesign = (designLabel && designLabel.innerText !== '-') ? designLabel.innerText : (rollOpt ? (rollOpt.getAttribute('data-design') || '') : '');

        const catId = catSelect ? catSelect.value : '';
        const subcatId = subcatSelect ? subcatSelect.value : '';
        const subSubcatId = subSubcatSelect ? subSubcatSelect.value : '';

        const catParts = [];
        if (catSelect && catSelect.selectedIndex > 0) catParts.push(catSelect.options[catSelect.selectedIndex].text);
        if (subcatSelect && subcatSelect.selectedIndex > 0) catParts.push(subcatSelect.options[subcatSelect.selectedIndex].text);
        if (subSubcatSelect && subSubcatSelect.selectedIndex > 0) catParts.push(subSubcatSelect.options[subSubcatSelect.selectedIndex].text);
        const catalogPath = catParts.length > 0 ? catParts.join(' › ') : 'General Catalog';

        activeRollRequests.push({
            rollId,
            rollNumber,
            rollColor,
            rollDesign,
            catId,
            subcatId,
            subSubcatId,
            catalogPath
        });
    });

    if (activeRollRequests.length === 0) {
        return;
    }

    // Capture existing entered quantities
    const existingQuantities = {};
    if (initialItems && Array.isArray(initialItems)) {
        initialItems.forEach(it => {
            const key = (String(it.size_name || '').trim() + '___' + String(it.color_name || '').trim()).toUpperCase();
            existingQuantities[key] = {
                cut: parseInt(it.cut_qty) || 0,
                rej: parseInt(it.rejected_qty) || 0,
                planned: parseInt(it.planned_qty) || 0
            };
        });
    }

    document.querySelectorAll('#variantsTableBody tr.roll-variant-item').forEach(tr => {
        const sizeInp = tr.querySelector('input[name*="[size_name]"]');
        const colorInp = tr.querySelector('input[name*="[color_name]"]');
        const cutInp = tr.querySelector('.cut-qty-input');
        const rejInp = tr.querySelector('.rej-qty-input');
        const plannedInp = tr.querySelector('.planned-qty-input');
        if (sizeInp && colorInp && (sizeInp.value || colorInp.value)) {
            const key = (sizeInp.value.trim() + '___' + colorInp.value.trim()).toUpperCase();
            existingQuantities[key] = {
                cut: cutInp ? (parseInt(cutInp.value) || 0) : 0,
                rej: rejInp ? (parseInt(rejInp.value) || 0) : 0,
                planned: plannedInp ? (parseInt(plannedInp.value) || 0) : 0
            };
        }
    });

    const promises = activeRollRequests.map(req => {
        const url = '<?= url('api/catalog/variants') ?>?category_id=' + (req.catId || '') + 
                    '&subcategory_id=' + (req.subcatId || '') + 
                    '&sub_subcategory_id=' + (req.subSubcatId || '') + 
                    '&roll_id=' + (req.rollId || '') + 
                    '&roll_color=' + encodeURIComponent(req.rollColor || '');
        return fetch(url)
            .then(res => res.json())
            .then(data => ({
                req,
                variants: data.variants || [],
                sizes: data.sizes || ['M', 'L', 'XL', 'XXL', '3XL'],
                colors: data.colors || (req.rollColor ? [req.rollColor] : ['STANDARD'])
            }))
            .catch(err => ({
                req,
                variants: [],
                sizes: ['M', 'L', 'XL', 'XXL', '3XL'],
                colors: req.rollColor ? [req.rollColor] : ['STANDARD']
            }));
    });

    Promise.all(promises).then(results => {
        const tbody = document.getElementById('variantsTableBody');
        tbody.innerHTML = '';
        variantCounter = 0;

        const navTabsContainer = document.getElementById('rollVariantTabsContainer');
        const navTabs = document.getElementById('rollVariantNavTabs');
        if (navTabs) navTabs.innerHTML = '';

        let totalVariantsCount = 0;
        const rollTabConfigs = [];

        results.forEach((resItem, resIdx) => {
            const req = resItem.req;
            const groupKey = 'roll_' + (req.rollId || resIdx);
            let rawRollNum = req.rollNumber ? String(req.rollNumber).trim() : String(resIdx + 1);
            let cleanRollNum = rawRollNum.replace(/^(roll\s*#?|#)\s*/i, '').trim() || (resIdx + 1);
            const rollTitle = `Roll #${cleanRollNum} (${req.rollColor || 'Standard'}${req.rollDesign ? ' - ' + req.rollDesign : ''}) — ${req.catalogPath}`;

            const groupTr = document.createElement('tr');
            groupTr.className = 'table-light border-top border-2 roll-variant-item roll-variant-header roll-group-' + groupKey;
            groupTr.setAttribute('data-roll-group', groupKey);
            groupTr.innerHTML = `
                <td colspan="5" class="py-2 px-3 fw-bold text-dark">
                    <i class="bi bi-layers text-info me-1"></i>${escapeHtml(rollTitle)}
                </td>
            `;
            tbody.appendChild(groupTr);

            let rollVariantCount = 0;
            if (resItem.variants && resItem.variants.length > 0) {
                resItem.variants.forEach(v => {
                    rollVariantCount++;
                    const sName = v.size_name || 'FREE SIZE';
                    const cName = req.rollColor || v.color_name || 'STANDARD';
                    const lookupKey = (sName.trim() + '___' + cName.trim()).toUpperCase();
                    let pQty = v.planned_quantity || 0;
                    let cQty = pQty;
                    let rQty = 0;
                    if (existingQuantities[lookupKey]) {
                        cQty = existingQuantities[lookupKey].cut;
                        rQty = existingQuantities[lookupKey].rej;
                        pQty = existingQuantities[lookupKey].planned;
                    }
                    appendVariantRow(sName, cName, v.id || '', v.sku || '', pQty, req.rollId, groupKey, cQty, rQty, req.catId, req.subcatId, req.subSubcatId, req.catalogPath);
                });
            } else {
                const sizes = resItem.sizes && resItem.sizes.length > 0 ? resItem.sizes : ['M', 'L', 'XL', 'XXL', '3XL'];
                const colors = req.rollColor ? [req.rollColor] : (resItem.colors && resItem.colors.length > 0 ? resItem.colors : ['STANDARD']);
                sizes.forEach(sz => {
                    colors.forEach(col => {
                        rollVariantCount++;
                        const lookupKey = (sz.trim() + '___' + col.trim()).toUpperCase();
                        let pQty = 0;
                        let cQty = 0;
                        let rQty = 0;
                        if (existingQuantities[lookupKey]) {
                            cQty = existingQuantities[lookupKey].cut;
                            rQty = existingQuantities[lookupKey].rej;
                            pQty = existingQuantities[lookupKey].planned;
                        }
                        appendVariantRow(sz, col, '', '', pQty, req.rollId, groupKey, cQty, rQty, req.catId, req.subcatId, req.subSubcatId, req.catalogPath);
                    });
                });
            }

            totalVariantsCount += rollVariantCount;
            rollTabConfigs.push({
                groupKey,
                label: `Roll #${cleanRollNum}` + (req.rollColor ? ` (${req.rollColor})` : ''),
                fullTitle: rollTitle,
                count: rollVariantCount
            });
        });

        // Build Roll View Tabs if multiple rolls exist
        if (results.length > 1 && navTabsContainer && navTabs) {
            navTabsContainer.style.display = 'flex';
            rollTabConfigs.forEach((cfg, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `btn btn-sm ${idx === 0 ? 'btn-primary active' : 'btn-outline-primary'} fw-semibold`;
                btn.setAttribute('data-target-group', cfg.groupKey);
                btn.innerHTML = `<i class="bi bi-layers me-1"></i>${escapeHtml(cfg.label)} <span class="badge ${idx === 0 ? 'bg-white text-primary' : 'bg-primary-subtle text-primary'} ms-1">${cfg.count}</span>`;
                btn.onclick = () => switchRollVariantTab(cfg.groupKey, cfg.fullTitle);
                navTabs.appendChild(btn);
            });

            // Add "All Rolls" tab
            const allBtn = document.createElement('button');
            allBtn.type = 'button';
            allBtn.className = 'btn btn-sm btn-outline-secondary fw-semibold';
            allBtn.setAttribute('data-target-group', 'all');
            allBtn.innerHTML = `<i class="bi bi-grid-fill me-1"></i>All Rolls <span class="badge bg-secondary text-white ms-1">${totalVariantsCount}</span>`;
            allBtn.onclick = () => switchRollVariantTab('all', 'Showing all rolls combined');
            navTabs.appendChild(allBtn);

            // Activate First Roll by default
            switchRollVariantTab(rollTabConfigs[0].groupKey, rollTabConfigs[0].fullTitle);
        } else {
            if (navTabsContainer) navTabsContainer.style.display = 'none';
            switchRollVariantTab('all', '');
        }

        calculateMetrics();
    });
}

function switchRollVariantTab(targetGroupKey, labelText = '') {
    // Update Tab Buttons UI
    document.querySelectorAll('#rollVariantNavTabs .btn').forEach(btn => {
        const group = btn.getAttribute('data-target-group');
        const badge = btn.querySelector('.badge');
        if (group === targetGroupKey) {
            btn.classList.remove('btn-outline-primary', 'btn-outline-secondary');
            btn.classList.add(group === 'all' ? 'btn-secondary' : 'btn-primary', 'active');
            if (badge) {
                badge.className = 'badge bg-white ' + (group === 'all' ? 'text-secondary' : 'text-primary') + ' ms-1';
            }
        } else {
            btn.classList.remove('btn-primary', 'btn-secondary', 'active');
            btn.classList.add(group === 'all' ? 'btn-outline-secondary' : 'btn-outline-primary');
            if (badge) {
                badge.className = group === 'all' ? 'badge bg-secondary text-white ms-1' : 'badge bg-primary-subtle text-primary ms-1';
            }
        }
    });

    // Filter Table Rows
    document.querySelectorAll('.roll-variant-item').forEach(row => {
        const rowGroup = row.getAttribute('data-roll-group');
        if (targetGroupKey === 'all' || rowGroup === targetGroupKey) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    const infoLabel = document.getElementById('activeRollInfoLabel');
    if (infoLabel) {
        infoLabel.innerHTML = labelText ? `<i class="bi bi-info-circle me-1 text-info"></i>Showing: <strong>${escapeHtml(labelText)}</strong>` : '';
    }
}

function appendVariantRow(sizeName, colorName, variantId, sku, plannedQty, rollId = '', groupKey = '', cutQty = null, rejQty = null, catId = '', subcatId = '', subSubcatId = '', catalogHierarchy = '') {
    variantCounter++;
    const tbody = document.getElementById('variantsTableBody');
    const tr = document.createElement('tr');
    tr.id = 'variant_row_' + variantCounter;
    tr.className = 'roll-variant-item' + (groupKey ? ' roll-group-' + groupKey : '');
    if (groupKey) {
        tr.setAttribute('data-roll-group', groupKey);
    }

    const finalCutQty = (cutQty !== null && cutQty !== undefined) ? cutQty : (plannedQty > 0 ? plannedQty : 0);
    const finalRejQty = (rejQty !== null && rejQty !== undefined) ? rejQty : 0;

    tr.innerHTML = `
        <td>
            <div class="d-flex align-items-center gap-2">
                <input type="text" name="items[${variantCounter}][size_name]" class="form-control form-control-sm fw-bold text-uppercase bg-light" style="max-width: 90px; cursor: not-allowed;" value="${escapeHtml(sizeName)}" placeholder="Size" readonly>
                <span class="text-muted">/</span>
                <input type="text" name="items[${variantCounter}][color_name]" class="form-control form-control-sm text-uppercase bg-light" style="max-width: 140px; cursor: not-allowed;" value="${escapeHtml(colorName)}" placeholder="Color" readonly>
                <input type="hidden" name="items[${variantCounter}][variant_id]" value="${escapeHtml(variantId)}">
                <input type="hidden" name="items[${variantCounter}][seller_roll_id]" value="${escapeHtml(rollId)}">
                <input type="hidden" name="items[${variantCounter}][category_id]" value="${escapeHtml(catId)}">
                <input type="hidden" name="items[${variantCounter}][subcategory_id]" value="${escapeHtml(subcatId)}">
                <input type="hidden" name="items[${variantCounter}][sub_subcategory_id]" value="${escapeHtml(subSubcatId)}">
                <input type="hidden" name="items[${variantCounter}][catalog_hierarchy]" value="${escapeHtml(catalogHierarchy)}">
            </div>
            ${sku ? `<small class="text-muted"><code>${escapeHtml(sku)}</code></small>` : ''}
        </td>
        <td>
            <input type="number" name="items[${variantCounter}][planned_qty]" class="form-control form-control-sm planned-qty-input fw-bold border-primary text-primary" min="0" value="${plannedQty > 0 ? plannedQty : (finalCutQty > 0 ? finalCutQty : 0)}" oninput="calculateMetrics()">
            <input type="hidden" name="items[${variantCounter}][cut_qty]" class="cut-qty-input" value="${finalCutQty > 0 ? finalCutQty : (plannedQty > 0 ? plannedQty : 0)}">
            <input type="hidden" name="items[${variantCounter}][rejected_qty]" class="rej-qty-input" value="${finalRejQty}">
        </td>
        <td class="text-end">
            ${canDeleteCutting ? `
            <button type="button" class="btn btn-sm text-danger p-0" onclick="removeVariantRow(${variantCounter})" title="Remove row">
                <i class="bi bi-trash"></i>
            </button>
            ` : ''}
        </td>
    `;
    tbody.appendChild(tr);
}

function updateRollCatalogBadge(rowIdx) {
    const row = document.getElementById('cutting_roll_row_' + rowIdx);
    if (!row) return;

    const cat = row.querySelector('.roll-cat-select');
    const sub = row.querySelector('.roll-subcat-select');
    const subsub = row.querySelector('.roll-subsubcat-select');

    const parts = [];
    if (cat && cat.selectedIndex > 0) parts.push(cat.options[cat.selectedIndex].text);
    if (sub && sub.selectedIndex > 0) parts.push(sub.options[sub.selectedIndex].text);
    if (subsub && subsub.selectedIndex > 0) parts.push(subsub.options[subsub.selectedIndex].text);

    const badge = row.querySelector('.roll-catalog-badge');
    if (badge) {
        badge.innerText = parts.length > 0 ? parts.join(' › ') : 'Select Catalog';
    }
}

function addCustomVariantRow() {
    const activeBtn = document.querySelector('#rollVariantNavTabs .btn.active');
    const activeGroup = activeBtn ? activeBtn.getAttribute('data-target-group') : 'all';
    const rollId = (activeGroup && activeGroup !== 'all') ? activeGroup.replace('roll_', '') : '';
    appendVariantRow('XL', 'STANDARD', '', '', 0, rollId, activeGroup !== 'all' ? activeGroup : '');
    calculateMetrics();
}

function removeVariantRow(id) {
    if (!canDeleteCutting) return;
    const row = document.getElementById('variant_row_' + id);
    if (row) {
        row.remove();
        calculateMetrics();
    }
}

function calculateMetrics() {
    let totalCut = 0;
    let totalRej = 0;

    document.querySelectorAll('.planned-qty-input').forEach(inp => {
        const val = parseInt(inp.value) || 0;
        totalCut += val;
        const row = inp.closest('tr');
        if (row) {
            const cutInp = row.querySelector('.cut-qty-input');
            if (cutInp) cutInp.value = val;
        }
    });

    const rollWeight = parseFloat(document.getElementById('rollWeight').value) || 0;
    const rollPrice = parseFloat(document.getElementById('rollPrice').value) || 0;

    // Calculate Per-Roll Wastage Weights and Costs
    const rollWastageInputs = document.querySelectorAll('.roll-wastage-input');
    let sumRollWastageWeight = 0;
    let sumRollWastageCost = 0;

    if (rollWastageInputs.length > 0) {
        rollWastageInputs.forEach(inp => {
            const rowId = inp.id.replace('roll_wastage_input_', '');
            const rRate = parseFloat(inp.getAttribute('data-rate')) || 0;
            const rWastageW = parseFloat(inp.value) || 0;
            const rCost = rWastageW * rRate;

            sumRollWastageCost += rCost;
            sumRollWastageWeight += rWastageW;

            const rollBadge = document.getElementById('roll_wastage_cost_badge_' + rowId);
            if (rollBadge) {
                rollBadge.innerText = 'Cost: ₹' + rCost.toFixed(2);
            }
        });

        document.getElementById('wastageWeight').value = sumRollWastageWeight.toFixed(2);
        const totalCostSumEl = document.getElementById('dispTotalWastageCostSum');
        if (totalCostSumEl) {
            totalCostSumEl.innerText = 'Total Cost: ₹' + sumRollWastageCost.toFixed(2);
        }
    }

    const wastageWeight = parseFloat(document.getElementById('wastageWeight').value) || 0;

    // 1. Single / Per-kg Fabric Price: Roll Price / Roll Weight
    const ratePerKg = rollWeight > 0 ? (rollPrice / rollWeight) : 0;

    // 2. Wastage Fabric Price / Cost
    const wastageCost = (rollWastageInputs.length > 0) ? sumRollWastageCost : (wastageWeight * ratePerKg);

    // 3. Used Weight & Wastage Percentage
    const usedWeight = Math.max(0, rollWeight - wastageWeight);
    const wastagePct = rollWeight > 0 ? ((wastageWeight / rollWeight) * 100) : 0;

    // 4. Fabric Cost per Cut Piece
    const costPerPiece = (rollPrice > 0 && totalCut > 0) ? (rollPrice / totalCut) : 0;

    // Update Hidden Input for DB save
    const wastageCostInp = document.getElementById('wastageCost');
    if (wastageCostInp) wastageCostInp.value = wastageCost.toFixed(2);

    // Update Display Elements
    const badgeRate = document.getElementById('badgeRatePerKg');
    if (badgeRate) badgeRate.innerText = 'Rate: ₹' + ratePerKg.toFixed(2) + ' / kg';

    const badgeWCost = document.getElementById('dispWastageCostBadge');
    if (badgeWCost) badgeWCost.innerText = 'Wastage Cost: ₹' + wastageCost.toFixed(2);

    const dRate = document.getElementById('dispRatePerKg');
    if (dRate) dRate.innerText = '₹' + ratePerKg.toFixed(2) + ' / kg';

    const dWCost = document.getElementById('dispWastageCost');
    if (dWCost) dWCost.innerText = '₹' + wastageCost.toFixed(2);

    const dUsed = document.getElementById('dispUsedWeight');
    if (dUsed) dUsed.innerText = usedWeight.toFixed(2) + ' kg';

    const dWPct = document.getElementById('dispWastagePercent');
    if (dWPct) dWPct.innerText = wastagePct.toFixed(1) + '%';

    const dCut = document.getElementById('dispTotalCut');
    if (dCut) dCut.innerText = totalCut.toLocaleString() + ' pcs';

    const dCpp = document.getElementById('dispCostPerPiece');
    if (dCpp) dCpp.innerText = '₹' + costPerPiece.toFixed(2);

    // Update Badge
    const badge = document.getElementById('totalCutBadge');
    if (badge) {
        badge.innerText = totalCut.toLocaleString() + ' Cut pcs';
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
    const container = document.getElementById('cuttingRollsContainer');
    if (container) {
        container.innerHTML = '';
        cuttingRollCounter = 0;
        if (initialBatchRolls && initialBatchRolls.length > 0) {
            initialBatchRolls.forEach(r => addCuttingRollRow(r));
        } else {
            addCuttingRollRow();
        }
    }
    calculateMetrics();

    // Form submission validation
    const form = document.getElementById('editCuttingForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const nameInput = document.getElementById('cuttingName');
            if (!nameInput || !nameInput.value.trim()) {
                e.preventDefault();
                alert('Validation error: Cutting Name is required. Please enter a valid name.');
                nameInput?.focus();
                return false;
            }

            const masterSelect = document.getElementById('cuttingMasterSelect');
            if (masterSelect && !masterSelect.value) {
                e.preventDefault();
                alert('Validation error: Please select a Cutting Master.');
                masterSelect.focus();
                return false;
            }

            let totalCut = 0;
            document.querySelectorAll('.cut-qty-input').forEach(inp => {
                totalCut += (parseInt(inp.value) || 0);
            });

            if (totalCut <= 0) {
                e.preventDefault();
                alert('Validation error: Total Cut Quantity must be greater than 0 pcs before saving.');
                const firstCut = document.querySelector('.cut-qty-input');
                if (firstCut) firstCut.focus();
                return false;
            }
        });
    }
});
</script>
