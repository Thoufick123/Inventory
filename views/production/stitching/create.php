<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-threads me-2 text-warning"></i>New Stitching Batch</h2>
        <span class="text-muted">Select cutting batch by name, dynamically identify multi-catalog hierarchies, and set variant-wise quantities, weights &amp; distinct prices</span>
    </div>
    <a href="<?= url('production/stitching') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Stitching Batches
    </a>
</div>

<form method="POST" action="<?= url('production/stitching/store') ?>" id="stitchingForm">
    <?= CSRF::field() ?>

    <input type="hidden" name="program_id" id="inputProgramId" value="<?= e($selectedProgram['id'] ?? ($selectedCutting['program_id'] ?? '')) ?>">
    <input type="hidden" name="category_id" id="inputCategoryId" value="<?= e($selectedProgram['category_id'] ?? ($selectedCutting['category_id'] ?? '')) ?>">
    <input type="hidden" name="subcategory_id" id="inputSubcategoryId" value="<?= e($selectedProgram['subcategory_id'] ?? ($selectedCutting['subcategory_id'] ?? '')) ?>">
    <input type="hidden" name="sub_subcategory_id" id="inputSubSubcategoryId" value="<?= e($selectedProgram['sub_subcategory_id'] ?? ($selectedCutting['sub_subcategory_id'] ?? '')) ?>">

    <div class="row g-4">
        <!-- Left Column: Stitching Details & Cutting Setup -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle me-2 text-primary"></i>1. Stitching Batch Details
                    </h5>
                </div>
                <div class="card-body p-4">
                    <!-- 1. Stitching Name (Required input) -->
                    <div class="mb-3">
                        <label class="form-label required fw-semibold">Stitching Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="stitchingName" class="form-control text-uppercase fw-bold" placeholder="e.g. STITCHING - LOT A" required>
                        <small class="text-muted">Identifies this stitching production batch.</small>
                    </div>

                    <!-- Batch Number & Status -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Batch Number</label>
                            <input type="text" name="batch_number" id="batchNumber" class="form-control bg-light fw-bold" value="<?= e($suggestedBatchNumber) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="IN_PROGRESS" selected>In Progress</option>
                                <option value="PENDING">Pending</option>
                                <option value="COMPLETED">Completed</option>
                            </select>
                        </div>
                    </div>

                    <!-- 2. Cutting Name Dropdown (Primary Selection) -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label required fw-semibold mb-0">
                                <i class="bi bi-scissors text-primary me-1"></i>Select Cutting Name <span class="text-danger">*</span>
                            </label>
                            <span class="badge bg-primary-subtle text-primary border">Source Batch</span>
                        </div>
                        <select name="cutting_batch_id" id="cuttingBatchSelect" class="form-select form-select-lg border-primary fw-semibold" required onchange="handleCuttingBatchChange(this.value)">
                            <option value="">-- Choose Cutting Name / Batch --</option>
                            <?php foreach ($cuttingBatches as $cb): ?>
                                <?php 
                                    $cuttingDisplayName = !empty($cb['name']) ? $cb['name'] : $cb['batch_number'];
                                    $progStr = !empty($cb['program_number']) ? $cb['program_number'] : ('Program #' . $cb['program_id']);
                                    $qtyStr = !empty($cb['cut_qty']) ? (number_format($cb['cut_qty']) . ' pcs') : '0 pcs';
                                    $usableWeight = isset($cb['used_weight']) && (float)$cb['used_weight'] > 0 
                                        ? (float)$cb['used_weight'] 
                                        : max(0.0, (float)($cb['roll_weight'] ?? 0) - (float)($cb['wastage_weight'] ?? 0));
                                ?>
                                <option value="<?= $cb['id'] ?>" 
                                    <?= ($selectedCutting && $selectedCutting['id'] == $cb['id']) ? 'selected' : '' ?>
                                    data-name="<?= e($cuttingDisplayName) ?>"
                                    data-batch="<?= e($cb['batch_number']) ?>"
                                    data-program-id="<?= e($cb['program_id']) ?>"
                                    data-program-number="<?= e($cb['program_number'] ?? '') ?>"
                                    data-program-name="<?= e($cb['program_name'] ?? ($cb['product_name'] ?? '')) ?>"
                                    data-qty="<?= e($cb['cut_qty'] ?? 0) ?>"
                                    data-usable-weight="<?= number_format($usableWeight, 2, '.', '') ?>">
                                    <?= e($cuttingDisplayName) ?> (<?= e($cb['batch_number']) ?> &bull; <?= e($progStr) ?> &bull; <?= e($qtyStr) ?> &bull; <?= number_format($usableWeight, 2) ?> kg)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Select a cutting batch to automatically load multi-catalog cut components &amp; quantities.</small>
                        <?php if (empty($cuttingBatches) && !$selectedCutting): ?>
                            <div class="alert alert-warning py-2 px-3 small mt-2 mb-0">
                                <i class="bi bi-info-circle me-1"></i> All cutting batches have already been assigned to stitching. <a href="<?= url('production/cutting/create') ?>" class="alert-link">Create a cutting batch</a>.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Cutting & Catalog Summary Box -->
                    <div id="cuttingDetailsBox" class="p-3 bg-light rounded border mb-3" style="<?= $selectedCutting ? '' : 'display: none;' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                            <span class="badge bg-primary px-2 py-1" id="boxBatchNumber"><?= e($selectedCutting['batch_number'] ?? '') ?></span>
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge bg-success-subtle text-success border" id="boxCutQty">
                                    <i class="bi bi-scissors me-1"></i><?= number_format($selectedCutting['cut_qty'] ?? 0) ?> pcs Cut
                                </span>
                                <?php 
                                    $initUsableWt = isset($selectedCutting['used_weight']) && (float)$selectedCutting['used_weight'] > 0 
                                        ? (float)$selectedCutting['used_weight'] 
                                        : max(0.0, (float)($selectedCutting['roll_weight'] ?? 0) - (float)($selectedCutting['wastage_weight'] ?? 0));
                                ?>
                                <span class="badge bg-info-subtle text-info border" id="boxCutWeight">
                                    <i class="bi bi-speedometer2 me-1"></i><?= number_format($initUsableWt, 2) ?> kg Usable
                                </span>
                            </div>
                        </div>
                        <div class="fw-bold text-dark fs-6 mb-1" id="boxCuttingName"><?= e($selectedCutting['name'] ?? 'Cutting Batch') ?></div>
                        <div class="text-muted small mb-2" id="boxProgramInfo">
                            <i class="bi bi-file-earmark-text me-1"></i><?= e($selectedCutting['program_number'] ?? '') ?> — <?= e($selectedCutting['product_name'] ?? '') ?>
                        </div>
                        
                        <!-- Catalog Hierarchy Summary -->
                        <div class="p-2 bg-white rounded border">
                            <div class="small text-muted mb-1"><i class="bi bi-diagram-3 me-1 text-info"></i>Assigned Catalog(s):</div>
                            <div id="boxCatalogList" class="d-flex flex-column gap-1">
                                <span class="badge bg-light text-dark border fs-7 text-start" id="boxCatalogHierarchy">
                                    <?= e($selectedCutting['category_name'] ?? 'Apparel Catalog') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Operator / Tailor & Notes -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tailor / Stitching Master</label>
                        <select name="worker_id" class="form-select">
                            <option value="">Select Worker / Operator</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= e($u['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes &amp; Remarks</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Stitching instructions, lot details, etc..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Quantities, Multi-Catalog Grouping & Weight Breakdown -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100 d-flex flex-column">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-grid-3x3 me-2 text-primary"></i>2. Stitching Quantities, Weights &amp; Pricing
                        </h5>
                        <small class="text-muted" id="catalogSubHeader">Variant components grouped with distinct catalog identities</small>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="copyCutQtyToStitchingQty()" title="Copy all Cut Qty values into Stitching Qty inputs">
                            <i class="bi bi-arrow-down-circle me-1"></i>Copy Cut Qty
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success fw-semibold" id="btnToggleQuickPrice" onclick="toggleQuickPriceSetter()" title="Set Base & Discount Price per Catalog in bulk">
                            <i class="bi bi-cash-stack me-1"></i>Set Prices by Catalog
                        </button>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-7" id="totalCutBadge">
                            <i class="bi bi-scissors me-1"></i>0 Cut pcs
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-7" id="totalStitchedBadge">
                            <i class="bi bi-check2-circle me-1"></i>0 Stitched pcs
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 fs-7" id="cuttingWeightBadge">
                            <i class="bi bi-scissors me-1"></i>Cut Wt: 0.00 kg
                        </span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 fs-7" id="totalWeightBadge">
                            <i class="bi bi-speedometer2 me-1"></i>Stitch Wt: 0.00 kg
                        </span>
                        <span class="badge bg-success text-white border border-success px-2 py-1 fs-7" id="weightMatchBadge" style="display: none;">
                            <i class="bi bi-check2-circle me-1"></i>Weight Matched
                        </span>
                    </div>
                </div>

                <!-- Catalog Dynamic Price Loader Bar -->
                <div id="catalogPriceLoaderBar" class="px-3 py-2 bg-light-subtle border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2" style="<?= $selectedCutting ? '' : 'display: none !important;' ?>">
                    <div class="d-flex align-items-center gap-2 flex-wrap flex-grow-1">
                        <span class="small fw-bold text-dark d-flex align-items-center">
                            <i class="bi bi-tag-fill text-success me-1"></i> Catalog Pricing:
                        </span>
                        <div style="min-width: 220px; max-width: 320px;" class="flex-grow-1">
                            <select id="quickPriceCatalogSelect" class="form-select form-select-sm fw-semibold shadow-sm border-success-subtle" onchange="onCatalogPriceDropdownChange(this.value)">
                                <option value="ALL">All Catalogs in Batch</option>
                            </select>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <div class="input-group input-group-sm" style="width: 145px;">
                                <span class="input-group-text bg-white fw-bold text-muted">Base ₹</span>
                                <input type="number" id="quickBulkBasePrice" class="form-control form-control-sm text-center fw-bold" placeholder="0.00" min="0.01" step="0.01" title="Base Price (₹)">
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <div class="input-group input-group-sm" style="width: 145px;">
                                <span class="input-group-text bg-white fw-bold text-muted">Disc ₹</span>
                                <input type="number" id="quickBulkDiscPrice" class="form-control form-control-sm text-center fw-bold" placeholder="0.00" min="0.01" step="0.01" title="Discount Price (₹)">
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-success fw-semibold shadow-sm px-3" onclick="applyQuickPricesFromBar()" title="Load and set prices for the selected catalog into the table">
                            <i class="bi bi-arrow-down-circle-fill me-1"></i> Set Prices to Table
                        </button>
                    </div>
                    <div id="quickPriceStatusMsg" class="small fw-semibold text-success" style="display: none;"></div>
                </div>

                <!-- Catalog Filter Navigation Tabs (shown when batch is loaded) -->
                <div id="catalogTabsContainer" class="px-3 py-2 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2" style="display: none !important;">
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <span class="small fw-semibold text-muted me-2"><i class="bi bi-funnel me-1"></i>Catalog Filter:</span>
                        <div class="btn-group btn-group-sm flex-wrap" role="group" id="catalogNavTabs">
                            <!-- Dynamic Catalog Filter Buttons -->
                        </div>
                    </div>
                    <div class="small text-muted" id="activeCatalogCountInfo"></div>
                </div>

                <!-- Quick Price Setter Panel (Collapsible) -->
                <div id="quickPricePanel" class="p-3 bg-light border-bottom" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0 fw-bold text-success"><i class="bi bi-lightning-charge-fill me-1"></i>Quick Price Setup by Catalog</h6>
                        <button type="button" class="btn btn-sm btn-close" onclick="toggleQuickPriceSetter()" aria-label="Close"></button>
                    </div>
                    <p class="small text-muted mb-2">Set Base Price (₹) and Discount Price (₹) for all variants of a specific catalog at once:</p>
                    <div id="quickPriceRows" class="d-flex flex-column gap-2">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>

                <div class="card-body p-0 flex-grow-1" id="stitchingItemsContainer">
                    <!-- Loading State -->
                    <div id="stitchingTableLoadingState" class="p-5 text-center text-muted" style="display: none;">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
                        <h6 class="fw-bold text-dark">Loading Cut Components &amp; Multi-Catalog Pricing...</h6>
                        <p class="text-muted small mb-0">Fetching variant attributes, quantities, and pricing configuration.</p>
                    </div>

                    <!-- Empty State -->
                    <div id="noCuttingSelectedState" class="p-5 text-center text-muted" style="<?= $selectedCutting ? 'display: none;' : '' ?>">
                        <div class="mb-3">
                            <div class="d-inline-flex p-3 rounded-circle bg-light">
                                <i class="bi bi-scissors fs-1 text-primary"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold text-dark">Select a Cutting Name</h5>
                        <p class="text-muted mb-0">Choose a cutting name from the dropdown on the left to dynamically load all cut components, multiple catalog hierarchies, quantities, weights, and pricing.</p>
                    </div>

                    <!-- Table Container -->
                    <div id="variantsTableContainer" class="table-responsive" style="<?= $selectedCutting ? '' : 'display: none;' ?>">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 200px;">Catalog &amp; Variant Attribute</th>
                                    <th style="width: 100px;" class="text-center text-primary"><i class="bi bi-scissors me-1"></i>Cut Qty</th>
                                    <th style="width: 120px;" class="text-center">Stitching Qty (pcs) <span class="text-danger">*</span></th>
                                    <th style="width: 120px;" class="text-center">Per Pc Wt (kg) <span class="text-danger">*</span></th>
                                    <th style="width: 120px;" class="text-center">Total Wt (kg) <span class="text-danger">*</span></th>
                                    <th style="width: 120px;" class="text-center">Base Price (₹) <span class="text-danger">*</span></th>
                                    <th style="width: 120px;" class="text-center">Discount Price (₹) <span class="text-danger">*</span></th>
                                </tr>
                            </thead>
                            <tbody id="variantsTableBody">
                                <!-- Populated dynamically by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center border-top">
                    <a href="<?= url('production/stitching') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" id="btnSaveStitching">
                        <i class="bi bi-check2-circle me-1"></i> Save Stitching Batch
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let currentLoadedCatalogs = [];
let currentCuttingUsableWeight = 0;

function handleCuttingBatchChange(cuttingBatchId) {
    const cuttingBox = document.getElementById('cuttingDetailsBox');
    const emptyState = document.getElementById('noCuttingSelectedState');
    const loadingState = document.getElementById('stitchingTableLoadingState');
    const tableContainer = document.getElementById('variantsTableContainer');
    const priceLoaderBar = document.getElementById('catalogPriceLoaderBar');
    const tabsContainer = document.getElementById('catalogTabsContainer');
    const quickPricePanel = document.getElementById('quickPricePanel');
    const tableBody = document.getElementById('variantsTableBody');

    if (!cuttingBatchId) {
        currentCuttingUsableWeight = 0;
        if (cuttingBox) cuttingBox.style.display = 'none';
        if (emptyState) emptyState.style.display = 'block';
        if (loadingState) loadingState.style.display = 'none';
        if (tableContainer) tableContainer.style.display = 'none';
        if (priceLoaderBar) priceLoaderBar.style.setProperty('display', 'none', 'important');
        if (tabsContainer) tabsContainer.style.setProperty('display', 'none', 'important');
        if (quickPricePanel) quickPricePanel.style.display = 'none';
        if (tableBody) tableBody.innerHTML = '';
        calcStitchingTotals();
        return;
    }

    const select = document.getElementById('cuttingBatchSelect');
    if (!select || select.selectedIndex < 0) return;
    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption) return;

    const cuttingName = selectedOption.getAttribute('data-name') || '';
    const batchNumber = selectedOption.getAttribute('data-batch') || '';
    const programId = selectedOption.getAttribute('data-program-id') || '';
    const programNumber = selectedOption.getAttribute('data-program-number') || '';
    const programName = selectedOption.getAttribute('data-program-name') || '';
    const cutQty = selectedOption.getAttribute('data-qty') || '0';
    const optUsableWeight = parseFloat(selectedOption.getAttribute('data-usable-weight')) || 0;
    currentCuttingUsableWeight = optUsableWeight;

    // Auto-fill stitching name if empty or default
    const nameInput = document.getElementById('stitchingName');
    if (nameInput && (!nameInput.value || nameInput.value.startsWith('STITCHING -'))) {
        nameInput.value = 'STITCHING - ' + (cuttingName || batchNumber).toUpperCase();
    }

    // Set hidden program ID
    const inpProgId = document.getElementById('inputProgramId');
    if (inpProgId && programId) {
        inpProgId.value = programId;
    }

    // Show details box and initial batch information
    if (cuttingBox) cuttingBox.style.display = 'block';
    const boxBatchNumber = document.getElementById('boxBatchNumber');
    if (boxBatchNumber) boxBatchNumber.textContent = batchNumber;
    const boxCuttingName = document.getElementById('boxCuttingName');
    if (boxCuttingName) boxCuttingName.textContent = cuttingName;
    const boxProgramInfo = document.getElementById('boxProgramInfo');
    if (boxProgramInfo) boxProgramInfo.innerHTML = `<i class="bi bi-file-earmark-text me-1"></i>${escapeHtml(programNumber)} — ${escapeHtml(programName)}`;
    const boxCutQty = document.getElementById('boxCutQty');
    if (boxCutQty) boxCutQty.innerHTML = `<i class="bi bi-scissors me-1"></i>${parseInt(cutQty).toLocaleString()} pcs Cut`;
    const boxCutWeight = document.getElementById('boxCutWeight');
    if (boxCutWeight) {
        boxCutWeight.innerHTML = `<i class="bi bi-speedometer2 me-1"></i>${currentCuttingUsableWeight.toFixed(2)} kg Usable`;
    }

    // Safely reset catalog summary placeholder without breaking DOM references
    const boxCatList = document.getElementById('boxCatalogList');
    if (boxCatList) {
        boxCatList.innerHTML = `<span class="badge bg-light text-dark border fs-7 text-start" id="boxCatalogHierarchy"><span class="spinner-border spinner-border-sm me-1 text-primary"></span>Fetching catalogs &amp; attributes...</span>`;
    }

    // Show loading state on variant table container
    if (emptyState) emptyState.style.display = 'none';
    if (tableContainer) tableContainer.style.display = 'none';
    if (loadingState) loadingState.style.display = 'block';

    // Fetch Cutting Batch & Catalog details via API
    fetch('<?= url("api/cutting/") ?>' + cuttingBatchId + '/details')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const cb = data.cuttingBatch || {};
                const prg = data.program || {};
                const catalogs = data.catalogs || [];
                currentLoadedCatalogs = catalogs;

                if (cb && cb.used_weight !== undefined && parseFloat(cb.used_weight) > 0) {
                    currentCuttingUsableWeight = parseFloat(cb.used_weight);
                } else if (cb) {
                    const rW = parseFloat(cb.roll_weight) || 0;
                    const wW = parseFloat(cb.wastage_weight) || 0;
                    currentCuttingUsableWeight = Math.max(0, rW - wW);
                }
                const bCutWeight = document.getElementById('boxCutWeight');
                if (bCutWeight) {
                    bCutWeight.innerHTML = `<i class="bi bi-speedometer2 me-1"></i>${currentCuttingUsableWeight.toFixed(2)} kg Usable`;
                }

                // Update 3-tier catalog summary display on the left box
                renderCatalogSummaryBox(catalogs, data.catalogHierarchy);
                
                const inpProg = document.getElementById('inputProgramId');
                if (inpProg && cb.program_id) inpProg.value = cb.program_id;
                const inpCat = document.getElementById('inputCategoryId');
                if (inpCat && (cb.category_id || prg.category_id)) inpCat.value = cb.category_id || prg.category_id;
                const inpSubcat = document.getElementById('inputSubcategoryId');
                if (inpSubcat && (cb.subcategory_id || prg.subcategory_id)) inpSubcat.value = cb.subcategory_id || prg.subcategory_id;
                const inpSubSubcat = document.getElementById('inputSubSubcategoryId');
                if (inpSubSubcat && (cb.sub_subcategory_id || prg.sub_subcategory_id)) inpSubSubcat.value = cb.sub_subcategory_id || prg.sub_subcategory_id;

                // Render dynamic variant rows with full catalog badges & price defaults
                renderVariants(data.items || [], catalogs);
                renderCatalogNavTabs(catalogs, data.items || []);
                renderQuickPriceCatalogDropdown(catalogs, data.items || []);
                renderQuickPricePanel(catalogs);
            } else {
                if (loadingState) loadingState.style.display = 'none';
                if (emptyState) emptyState.style.display = 'block';
                alert(data.message || 'Error fetching cutting details.');
            }
        })
        .catch(err => {
            console.error('Error fetching cutting details:', err);
            generateFallbackVariants();
        });
}

function renderCatalogSummaryBox(catalogs, defaultHierarchy) {
    const container = document.getElementById('boxCatalogList');
    if (!container) return;
    container.innerHTML = '';

    if (!catalogs || catalogs.length === 0) {
        container.innerHTML = `<span class="badge bg-light text-dark border fs-7 text-start">${escapeHtml(defaultHierarchy || 'General Apparel')}</span>`;
        return;
    }

    catalogs.forEach((cat, idx) => {
        const span = document.createElement('div');
        span.className = 'badge bg-primary-subtle text-primary border text-start p-2 d-flex justify-content-between align-items-center flex-wrap gap-1';
        span.innerHTML = `
            <span><i class="bi bi-diagram-3-fill me-1 text-primary"></i><strong>${escapeHtml(cat.name)}</strong></span>
            <span class="badge bg-white text-primary border">${cat.item_count} items &bull; ${cat.total_qty} pcs</span>
        `;
        container.appendChild(span);
    });
}

function renderCatalogNavTabs(catalogs, items) {
    const tabsContainer = document.getElementById('catalogTabsContainer');
    const navTabs = document.getElementById('catalogNavTabs');
    if (!tabsContainer || !navTabs) return;

    if (!catalogs || catalogs.length <= 1) {
        tabsContainer.style.setProperty('display', 'none', 'important');
        return;
    }

    tabsContainer.style.setProperty('display', 'flex', 'important');
    navTabs.innerHTML = '';

    const totalVariants = items.length;

    // "All Catalogs" button
    const allBtn = document.createElement('button');
    allBtn.type = 'button';
    allBtn.className = 'btn btn-primary active fw-semibold btn-catalog-filter';
    allBtn.setAttribute('data-target-catalog', 'ALL');
    allBtn.innerHTML = `<i class="bi bi-grid-fill me-1"></i>All Catalogs <span class="badge bg-white text-primary ms-1">${totalVariants}</span>`;
    allBtn.onclick = () => filterTableByCatalog('ALL');
    navTabs.appendChild(allBtn);

    // Buttons for each catalog
    catalogs.forEach(cat => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-primary fw-semibold btn-catalog-filter text-truncate';
        btn.style.maxWidth = '250px';
        btn.setAttribute('data-target-catalog', cat.name);
        btn.innerHTML = `<i class="bi bi-tag-fill me-1"></i>${escapeHtml(cat.name)} <span class="badge bg-primary-subtle text-primary ms-1">${cat.item_count}</span>`;
        btn.onclick = () => filterTableByCatalog(cat.name);
        navTabs.appendChild(btn);
    });
}

function filterTableByCatalog(targetCatalog) {
    // Update active state on buttons
    document.querySelectorAll('#catalogNavTabs .btn-catalog-filter').forEach(btn => {
        const target = btn.getAttribute('data-target-catalog');
        const badge = btn.querySelector('.badge');
        if (target === targetCatalog) {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary', 'active');
            if (badge) badge.className = 'badge bg-white text-primary ms-1';
        } else {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-outline-primary');
            if (badge) badge.className = 'badge bg-primary-subtle text-primary ms-1';
        }
    });

    let visibleCount = 0;
    document.querySelectorAll('#variantsTableBody tr.variant-item-row').forEach(row => {
        const rowCat = row.getAttribute('data-catalog') || '';
        if (targetCatalog === 'ALL' || rowCat === targetCatalog) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const info = document.getElementById('activeCatalogCountInfo');
    if (info) {
        info.innerHTML = targetCatalog === 'ALL' 
            ? `Showing all <strong>${visibleCount}</strong> items`
            : `Showing <strong>${visibleCount}</strong> items in <em>${escapeHtml(targetCatalog)}</em>`;
    }
}

function toggleQuickPriceSetter() {
    const panel = document.getElementById('quickPricePanel');
    if (!panel) return;
    panel.style.display = (panel.style.display === 'none' || panel.style.display === '') ? 'block' : 'none';
}

function renderQuickPricePanel(catalogs) {
    const rowsContainer = document.getElementById('quickPriceRows');
    if (!rowsContainer) return;
    rowsContainer.innerHTML = '';

    if (!catalogs || catalogs.length === 0) {
        rowsContainer.innerHTML = '<div class="text-muted small">No catalogs available to configure prices.</div>';
        return;
    }

    catalogs.forEach((cat, idx) => {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'p-2 bg-white rounded border d-flex align-items-center justify-content-between flex-wrap gap-2';
        rowDiv.innerHTML = `
            <div class="d-flex align-items-center gap-2" style="min-width: 220px;">
                <span class="badge bg-primary-subtle text-primary border"><i class="bi bi-tag-fill me-1"></i>${escapeHtml(cat.name)}</span>
                <span class="text-muted small">(${cat.item_count} items)</span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="d-flex align-items-center gap-1">
                    <label class="small text-muted fw-semibold mb-0">Base ₹:</label>
                    <input type="number" id="quickBasePrice_${idx}" class="form-control form-control-sm text-center fw-bold" style="width: 100px;" placeholder="Base ₹" min="0.01" step="0.01">
                </div>
                <div class="d-flex align-items-center gap-1">
                    <label class="small text-muted fw-semibold mb-0">Discount ₹:</label>
                    <input type="number" id="quickDiscPrice_${idx}" class="form-control form-control-sm text-center fw-bold" style="width: 100px;" placeholder="Disc ₹" min="0.01" step="0.01">
                </div>
                <button type="button" class="btn btn-sm btn-success fw-semibold" onclick="applyQuickPriceToCatalog('${escapeHtml(cat.name).replace(/'/g, "\\'")}', 'quickBasePrice_${idx}', 'quickDiscPrice_${idx}')">
                    <i class="bi bi-check-lg me-1"></i>Apply to Catalog
                </button>
            </div>
        `;
        rowsContainer.appendChild(rowDiv);
    });
}

function renderQuickPriceCatalogDropdown(catalogs, items) {
    const bar = document.getElementById('catalogPriceLoaderBar');
    const select = document.getElementById('quickPriceCatalogSelect');
    const baseInp = document.getElementById('quickBulkBasePrice');
    const discInp = document.getElementById('quickBulkDiscPrice');
    if (!bar || !select) return;

    bar.style.removeProperty('display');
    bar.style.setProperty('display', 'flex', 'important');

    select.innerHTML = '<option value="ALL">All Catalogs in Batch</option>';
    if (catalogs && catalogs.length > 0) {
        catalogs.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat.name;
            opt.textContent = `${cat.name} (${cat.item_count} items)`;
            select.appendChild(opt);
        });
    }

    // Pre-fill prices from first item with prices if available
    if (baseInp && discInp) {
        let foundBase = 0;
        let foundDisc = 0;
        if (items && items.length > 0) {
            for (let it of items) {
                const b = parseFloat(it.base_price || it.variant_mrp || 0);
                const d = parseFloat(it.discount_price || it.variant_selling_price || 0);
                if (b > 0 || d > 0) {
                    foundBase = b;
                    foundDisc = d;
                    break;
                }
            }
        }
        if (foundBase > 0) baseInp.value = foundBase.toFixed(2);
        if (foundDisc > 0) discInp.value = foundDisc.toFixed(2);
    }
}

function onCatalogPriceDropdownChange(targetCatalog) {
    const baseInp = document.getElementById('quickBulkBasePrice');
    const discInp = document.getElementById('quickBulkDiscPrice');
    if (!baseInp || !discInp) return;

    // Find the first matching row to read existing prices
    const rows = document.querySelectorAll('#variantsTableBody tr.variant-item-row');
    for (let tr of rows) {
        const rowCat = tr.getAttribute('data-catalog') || '';
        if (targetCatalog === 'ALL' || rowCat === targetCatalog) {
            const bVal = parseFloat(tr.querySelector('.stitching-base-price-input')?.value) || 0;
            const dVal = parseFloat(tr.querySelector('.stitching-discount-price-input')?.value) || 0;
            if (bVal > 0) baseInp.value = bVal.toFixed(2);
            if (dVal > 0) discInp.value = dVal.toFixed(2);
            break;
        }
    }
}

function validateStitchingPricePair(row) {
    if (!row) return true;
    const baseInput = row.querySelector('.stitching-base-price-input');
    const discountInput = row.querySelector('.stitching-discount-price-input');
    if (!baseInput || !discountInput) return true;

    const basePrice = parseFloat(baseInput.value);
    const discountPrice = parseFloat(discountInput.value);
    const pricesEntered = baseInput.value.trim() !== '' && discountInput.value.trim() !== ''
        && Number.isFinite(basePrice) && Number.isFinite(discountPrice);
    const isInvalid = pricesEntered && discountPrice >= basePrice;

    baseInput.classList.toggle('is-invalid', isInvalid);
    baseInput.classList.toggle('border-danger', isInvalid);
    discountInput.classList.toggle('is-invalid', isInvalid);
    discountInput.classList.toggle('border-danger', isInvalid);
    if (isInvalid) {
        discountInput.setCustomValidity('Discount Price must be less than Base Price.');
    } else {
        discountInput.setCustomValidity('');
    }

    return !isInvalid;
}

function applyQuickPricesFromBar() {
    const catSelect = document.getElementById('quickPriceCatalogSelect');
    const targetCatalog = catSelect ? catSelect.value : 'ALL';
    const baseInp = document.getElementById('quickBulkBasePrice');
    const discInp = document.getElementById('quickBulkDiscPrice');

    let baseVal = parseFloat(baseInp?.value) || 0;
    let discVal = parseFloat(discInp?.value) || 0;

    if (baseVal <= 0 || discVal <= 0) {
        alert('Enter both prices. Discount Price must be less than Base Price.');
        if (baseInp) baseInp.focus();
        return;
    }

    if (discVal >= baseVal) {
        alert('Discount Price must be less than Base Price.');
        if (discInp) discInp.focus();
        return;
    }

    let appliedCount = 0;
    document.querySelectorAll('#variantsTableBody tr.variant-item-row').forEach(tr => {
        const rowCat = tr.getAttribute('data-catalog') || '';
        if (targetCatalog === 'ALL' || rowCat === targetCatalog) {
            const bInp = tr.querySelector('.stitching-base-price-input');
            const dInp = tr.querySelector('.stitching-discount-price-input');
            if (bInp) {
                bInp.value = baseVal.toFixed(2);
                bInp.classList.remove('is-invalid', 'border-danger');
                bInp.classList.add('bg-success-subtle');
                setTimeout(() => bInp.classList.remove('bg-success-subtle'), 1200);
            }
            if (dInp) {
                dInp.value = discVal.toFixed(2);
                dInp.classList.remove('is-invalid', 'border-danger');
                dInp.classList.add('bg-success-subtle');
                setTimeout(() => dInp.classList.remove('bg-success-subtle'), 1200);
            }
            validateStitchingPricePair(tr);
            appliedCount++;
        }
    });

    const statusMsg = document.getElementById('quickPriceStatusMsg');
    if (statusMsg) {
        const catLabel = targetCatalog === 'ALL' ? 'all variants' : `"${targetCatalog}"`;
        statusMsg.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Loaded ₹${baseVal.toFixed(2)} / ₹${discVal.toFixed(2)} to ${appliedCount} items (${catLabel})!`;
        statusMsg.style.display = 'inline-block';
        setTimeout(() => { statusMsg.style.display = 'none'; }, 4000);
    }
}

function applyQuickPriceToCatalog(catalogName, baseInputId, discInputId) {
    const baseVal = parseFloat(document.getElementById(baseInputId)?.value) || 0;
    const discVal = parseFloat(document.getElementById(discInputId)?.value) || 0;

    if (baseVal <= 0 || discVal <= 0) {
        alert('Enter both prices. Discount Price must be less than Base Price.');
        return;
    }
    if (discVal >= baseVal) {
        alert('Discount Price must be less than Base Price.');
        return;
    }

    let appliedCount = 0;
    document.querySelectorAll('#variantsTableBody tr.variant-item-row').forEach(tr => {
        const rowCat = tr.getAttribute('data-catalog') || '';
        if (rowCat === catalogName || catalogName === 'ALL') {
            const baseInp = tr.querySelector('.stitching-base-price-input');
            const discInp = tr.querySelector('.stitching-discount-price-input');
            if (baseInp && baseVal > 0) {
                baseInp.value = baseVal.toFixed(2);
                baseInp.classList.remove('is-invalid', 'border-danger');
            }
            if (discInp && discVal > 0) {
                discInp.value = discVal.toFixed(2);
                discInp.classList.remove('is-invalid', 'border-danger');
            }
            validateStitchingPricePair(tr);
            appliedCount++;
        }
    });

    alert(`Successfully applied pricing to ${appliedCount} items in "${catalogName}"!`);
}

function renderVariants(items, catalogs = []) {
    const loadingState = document.getElementById('stitchingTableLoadingState');
    if (loadingState) loadingState.style.display = 'none';

    const tbody = document.getElementById('variantsTableBody');
    if (tbody) tbody.innerHTML = '';

    if (!items || items.length === 0) {
        generateFallbackVariants();
        return;
    }

    const emptyState = document.getElementById('noCuttingSelectedState');
    if (emptyState) emptyState.style.display = 'none';
    const tableContainer = document.getElementById('variantsTableContainer');
    if (tableContainer) tableContainer.style.display = 'block';

    if (!tbody) return;

    items.forEach((it, idx) => {
        const sizeName = it.size_name || it.size_code || 'Standard';
        const colorName = it.color_name || 'Assorted';
        const variantId = it.variant_id || '';
        const catId = it.category_id || '';
        const subcatId = it.subcategory_id || '';
        const subSubcatId = it.sub_subcategory_id || '';
        const catalogHierarchy = it.catalog_hierarchy || 'General Apparel';
        const cutQty = parseInt(it.cut_qty) || 0;

        const unitWeightVal = it.weight_per_piece && parseFloat(it.weight_per_piece) > 0 ? parseFloat(it.weight_per_piece).toFixed(3) : '';
        const totalWeightVal = it.weight && parseFloat(it.weight) > 0 ? parseFloat(it.weight).toFixed(2) : '';
        const basePriceVal = it.base_price && parseFloat(it.base_price) > 0 ? parseFloat(it.base_price).toFixed(2) : '';
        const discountPriceVal = it.discount_price && parseFloat(it.discount_price) > 0 ? parseFloat(it.discount_price).toFixed(2) : '';
        const stitchedQtyVal = cutQty > 0 ? cutQty : (it.stitched_qty && parseInt(it.stitched_qty) > 0 ? parseInt(it.stitched_qty) : '');

        const tr = document.createElement('tr');
        tr.className = 'variant-item-row';
        tr.setAttribute('data-catalog', catalogHierarchy);

        tr.innerHTML = `
            <td>
                <input type="hidden" name="items[${idx}][variant_id]" value="${escapeHtml(variantId)}">
                <input type="hidden" name="items[${idx}][category_id]" value="${escapeHtml(catId)}">
                <input type="hidden" name="items[${idx}][subcategory_id]" value="${escapeHtml(subcatId)}">
                <input type="hidden" name="items[${idx}][sub_subcategory_id]" value="${escapeHtml(subSubcatId)}">
                <input type="hidden" name="items[${idx}][catalog_hierarchy]" value="${escapeHtml(catalogHierarchy)}">
                <input type="hidden" name="items[${idx}][size_name]" value="${escapeHtml(sizeName)}">
                <input type="hidden" name="items[${idx}][color_name]" value="${escapeHtml(colorName)}">
                <input type="hidden" name="items[${idx}][received_qty]" value="${cutQty}">

                <!-- Prominent Catalog Badge -->
                <div class="mb-1">
                    <span class="badge bg-primary-subtle text-primary border text-wrap text-start" style="font-size: 0.72rem; line-height: 1.2;">
                        <i class="bi bi-diagram-3-fill me-1 text-primary"></i>${escapeHtml(catalogHierarchy)}
                    </span>
                </div>

                <!-- Variant Attributes -->
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 fw-bold">${escapeHtml(sizeName)}</span>
                    <span class="badge bg-dark-subtle text-dark border px-2 py-1">${escapeHtml(colorName)}</span>
                    <small class="text-muted ms-1"><code>${escapeHtml(it.sku || (sizeName + '-' + colorName))}</code></small>
                </div>
            </td>
            <td class="text-center">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6 fw-bold">
                    ${cutQty} pcs
                </span>
            </td>
            <td>
                <input type="number" name="items[${idx}][stitched_qty]" 
                    class="form-control form-control-sm text-center fw-bold stitching-qty-input border-primary" 
                    min="1" value="${stitchedQtyVal}" placeholder="Qty" required oninput="onRowQtyOrUnitWeightChange(this.closest('tr'))">
            </td>
            <td>
                <input type="number" name="items[${idx}][weight_per_piece]" 
                    class="form-control form-control-sm text-center stitching-unit-weight-input" 
                    min="0.001" step="0.001" value="${unitWeightVal}" placeholder="0.000" required oninput="onRowQtyOrUnitWeightChange(this.closest('tr'))">
            </td>
            <td>
                <input type="number" name="items[${idx}][weight]" 
                    class="form-control form-control-sm text-center fw-bold stitching-weight-input border-info" 
                    min="0.01" step="0.01" value="${totalWeightVal}" placeholder="0.00" required oninput="onRowTotalWeightChange(this.closest('tr'))">
                <input type="hidden" name="items[${idx}][rejected_qty]" value="0">
                <input type="hidden" name="items[${idx}][rework_qty]" value="0">
            </td>
            <td>
                <input type="number" name="items[${idx}][base_price]" 
                    class="form-control form-control-sm text-center stitching-base-price-input" 
                    min="0.01" step="0.01" value="${basePriceVal}" placeholder="0.00" required oninput="validateStitchingPricePair(this.closest('tr'))">
            </td>
            <td>
                <input type="number" name="items[${idx}][discount_price]" 
                    class="form-control form-control-sm text-center stitching-discount-price-input" 
                    min="0.01" step="0.01" value="${discountPriceVal}" placeholder="0.00" required oninput="validateStitchingPricePair(this.closest('tr'))">
            </td>
        `;
        tbody.appendChild(tr);
    });

    calcStitchingTotals();
}

function generateFallbackVariants() {
    const emptyState = document.getElementById('noCuttingSelectedState');
    if (emptyState) emptyState.style.display = 'none';
    const loadingState = document.getElementById('stitchingTableLoadingState');
    if (loadingState) loadingState.style.display = 'none';
    const tableContainer = document.getElementById('variantsTableContainer');
    if (tableContainer) tableContainer.style.display = 'block';

    const fallbackSizes = ['M', 'L', 'XL', 'XXL'];
    const fallbackColors = ['NAVY BLUE', 'MAROON', 'BLACK'];
    const vars = [];
    fallbackSizes.forEach(s => {
        fallbackColors.forEach(c => {
            vars.push({
                variant_id: null,
                size_name: s,
                color_name: c,
                cut_qty: 0,
                sku: 'STCH-' + s + '-' + c.substring(0, 3),
                catalog_hierarchy: 'General Apparel'
            });
        });
    });
    renderVariants(vars);
    renderQuickPriceCatalogDropdown([{name: 'General Apparel', item_count: vars.length}], vars);
}

function onRowQtyOrUnitWeightChange(row) {
    if (!row) return;
    const qtyInp = row.querySelector('.stitching-qty-input');
    const unitWeightInp = row.querySelector('.stitching-unit-weight-input');
    const totalWeightInp = row.querySelector('.stitching-weight-input');

    if (qtyInp && parseFloat(qtyInp.value) > 0) qtyInp.classList.remove('is-invalid', 'border-danger');
    if (unitWeightInp && parseFloat(unitWeightInp.value) > 0) unitWeightInp.classList.remove('is-invalid', 'border-danger');

    const qty = parseInt(qtyInp?.value) || 0;
    const unitWeight = parseFloat(unitWeightInp?.value) || 0;

    if (qty > 0 && unitWeight > 0) {
        const total = qty * unitWeight;
        if (totalWeightInp) {
            totalWeightInp.value = total.toFixed(2);
            totalWeightInp.classList.remove('is-invalid', 'border-danger');
        }
    } else if (unitWeight === 0 && totalWeightInp && !totalWeightInp.dataset.manual) {
        totalWeightInp.value = '';
    }
    calcStitchingTotals();
}

function onRowTotalWeightChange(row) {
    if (!row) return;
    const qtyInp = row.querySelector('.stitching-qty-input');
    const unitWeightInp = row.querySelector('.stitching-unit-weight-input');
    const totalWeightInp = row.querySelector('.stitching-weight-input');

    if (totalWeightInp) {
        totalWeightInp.dataset.manual = '1';
        if (parseFloat(totalWeightInp.value) > 0) totalWeightInp.classList.remove('is-invalid', 'border-danger');
    }

    const qty = parseInt(qtyInp?.value) || 0;
    const totalWeight = parseFloat(totalWeightInp?.value) || 0;

    if (qty > 0 && totalWeight > 0 && unitWeightInp) {
        unitWeightInp.value = (totalWeight / qty).toFixed(3);
        unitWeightInp.classList.remove('is-invalid', 'border-danger');
    }
    calcStitchingTotals();
}

function calcStitchingTotals() {
    let totalCutQty = 0;
    let totalQty = 0;
    let totalWeight = 0.0;

    document.querySelectorAll('input[name*="[received_qty]"]').forEach(inp => {
        totalCutQty += parseInt(inp.value) || 0;
    });

    document.querySelectorAll('.stitching-qty-input').forEach(inp => {
        totalQty += parseInt(inp.value) || 0;
    });

    document.querySelectorAll('.stitching-weight-input').forEach(inp => {
        totalWeight += parseFloat(inp.value) || 0;
    });

    totalWeight = Math.round(totalWeight * 100) / 100;

    const cutBadge = document.getElementById('totalCutBadge');
    if (cutBadge) {
        cutBadge.innerHTML = '<i class="bi bi-scissors me-1"></i>' + totalCutQty.toLocaleString() + ' Cut pcs';
    }

    const qtyBadge = document.getElementById('totalStitchedBadge');
    if (qtyBadge) {
        qtyBadge.innerHTML = '<i class="bi bi-check2-circle me-1"></i>' + totalQty.toLocaleString() + ' Stitched pcs';
    }

    const cutWeightBadge = document.getElementById('cuttingWeightBadge');
    if (cutWeightBadge) {
        cutWeightBadge.innerHTML = '<i class="bi bi-scissors me-1"></i>Cut Wt: ' + currentCuttingUsableWeight.toFixed(2) + ' kg';
    }

    const weightBadge = document.getElementById('totalWeightBadge');
    if (weightBadge) {
        weightBadge.innerHTML = '<i class="bi bi-speedometer2 me-1"></i>Stitch Wt: ' + totalWeight.toFixed(2) + ' kg';
    }

    const matchBadge = document.getElementById('weightMatchBadge');
    if (matchBadge) {
        if (currentCuttingUsableWeight > 0) {
            matchBadge.style.display = 'inline-block';
            const diff = Math.round((totalWeight - currentCuttingUsableWeight) * 100) / 100;
            if (Math.abs(diff) <= 0.01) {
                matchBadge.className = 'badge bg-success text-white border border-success px-2 py-1 fs-7';
                matchBadge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Weight Matched (${totalWeight.toFixed(2)} kg)`;
            } else if (diff > 0.01) {
                matchBadge.className = 'badge bg-danger text-white border border-danger px-2 py-1 fs-7';
                matchBadge.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>Weight Exceeded (+${diff.toFixed(2)} kg vs Cut ${currentCuttingUsableWeight.toFixed(2)} kg)`;
            } else {
                matchBadge.className = 'badge bg-warning text-dark border border-warning px-2 py-1 fs-7';
                matchBadge.innerHTML = `<i class="bi bi-info-circle-fill me-1"></i>Weight Short (${diff.toFixed(2)} kg vs Cut ${currentCuttingUsableWeight.toFixed(2)} kg)`;
            }
        } else {
            matchBadge.style.display = 'none';
        }
    }
}

function copyCutQtyToStitchingQty() {
    document.querySelectorAll('#stitchingForm tbody tr').forEach(tr => {
        const cutBadge = tr.querySelector('.badge.bg-primary-subtle');
        const stitchedInput = tr.querySelector('.stitching-qty-input');
        if (cutBadge && stitchedInput) {
            const cutVal = parseInt(cutBadge.textContent) || 0;
            stitchedInput.value = cutVal;
            onRowQtyOrUnitWeightChange(tr);
        }
    });
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

document.addEventListener('DOMContentLoaded', () => {
    const cuttingSelect = document.getElementById('cuttingBatchSelect');
    if (cuttingSelect) {
        cuttingSelect.addEventListener('change', function() {
            handleCuttingBatchChange(this.value);
        });
        cuttingSelect.addEventListener('input', function() {
            handleCuttingBatchChange(this.value);
        });
        if (cuttingSelect.value) {
            handleCuttingBatchChange(cuttingSelect.value);
        }
    }

    // Form submission validation
    const form = document.getElementById('stitchingForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // 1. Stitching Name validation
            const nameInput = document.getElementById('stitchingName');
            if (!nameInput || !nameInput.value.trim()) {
                e.preventDefault();
                alert('Stitching Name is required. Please enter a valid batch name.');
                nameInput?.focus();
                return false;
            }

            // 2. Select Cutting Name validation
            const cutSelect = document.getElementById('cuttingBatchSelect');
            if (!cutSelect || !cutSelect.value) {
                e.preventDefault();
                alert('Select Cutting Name is required. Please choose a source Cutting Batch.');
                cutSelect?.focus();
                return false;
            }

            // 3. Variant rows validation
            const rows = document.querySelectorAll('#variantsTableBody tr.variant-item-row');
            if (rows.length === 0) {
                e.preventDefault();
                alert('Please select a Cutting Name to generate stitching variant items.');
                cutSelect?.focus();
                return false;
            }

            for (let i = 0; i < rows.length; i++) {
                const tr = rows[i];
                const sizeBadge = tr.querySelector('.badge.bg-secondary-subtle')?.textContent || 'Variant';
                const colorBadge = tr.querySelector('.badge.bg-dark-subtle')?.textContent || '';
                const catBadge = tr.querySelector('.badge.bg-primary-subtle')?.textContent || '';
                const variantLabel = `[${catBadge}] ${sizeBadge} / ${colorBadge}`.trim();

                const qtyInp = tr.querySelector('.stitching-qty-input');
                const unitWeightInp = tr.querySelector('.stitching-unit-weight-input');
                const totalWeightInp = tr.querySelector('.stitching-weight-input');
                const basePriceInp = tr.querySelector('.stitching-base-price-input');
                const discountPriceInp = tr.querySelector('.stitching-discount-price-input');

                const missingFields = [];

                const qVal = parseFloat(qtyInp?.value);
                if (!qtyInp || qtyInp.value.trim() === '' || isNaN(qVal) || qVal <= 0) {
                    missingFields.push('Stitching Qty (pcs)');
                    qtyInp?.classList.add('is-invalid', 'border-danger');
                } else {
                    qtyInp?.classList.remove('is-invalid', 'border-danger');
                }

                const uwVal = parseFloat(unitWeightInp?.value);
                if (!unitWeightInp || unitWeightInp.value.trim() === '' || isNaN(uwVal) || uwVal <= 0) {
                    missingFields.push('Per Pc Weight (kg)');
                    unitWeightInp?.classList.add('is-invalid', 'border-danger');
                } else {
                    unitWeightInp?.classList.remove('is-invalid', 'border-danger');
                }

                const twVal = parseFloat(totalWeightInp?.value);
                if (!totalWeightInp || totalWeightInp.value.trim() === '' || isNaN(twVal) || twVal <= 0) {
                    missingFields.push('Total Weight (kg)');
                    totalWeightInp?.classList.add('is-invalid', 'border-danger');
                } else {
                    totalWeightInp?.classList.remove('is-invalid', 'border-danger');
                }

                const bpVal = parseFloat(basePriceInp?.value);
                if (!basePriceInp || basePriceInp.value.trim() === '' || isNaN(bpVal) || bpVal <= 0) {
                    missingFields.push('Base Price (₹)');
                    basePriceInp?.classList.add('is-invalid', 'border-danger');
                } else {
                    basePriceInp?.classList.remove('is-invalid', 'border-danger');
                }

                const dpVal = parseFloat(discountPriceInp?.value);
                if (!discountPriceInp || discountPriceInp.value.trim() === '' || isNaN(dpVal) || dpVal <= 0) {
                    missingFields.push('Discount Price (₹)');
                    discountPriceInp?.classList.add('is-invalid', 'border-danger');
                } else {
                    discountPriceInp?.classList.remove('is-invalid', 'border-danger');
                }

                if (bpVal > 0 && dpVal > 0 && dpVal >= bpVal) {
                    e.preventDefault();
                    basePriceInp?.classList.add('is-invalid', 'border-danger');
                    discountPriceInp?.classList.add('is-invalid', 'border-danger');
                    alert(`Row #${i + 1} (${variantLabel}): Discount Price must be less than Base Price.`);
                    discountPriceInp?.focus();
                    return false;
                }

                if (missingFields.length > 0) {
                    e.preventDefault();
                    // Make sure the row is visible if hidden by filter
                    tr.style.display = '';
                    alert(`Row #${i + 1} (${variantLabel}) has missing or zero required fields:\n• ${missingFields.join('\n• ')}\n\nPlease fill all required fields with valid values before saving.`);
                    if (qtyInp && qtyInp.classList.contains('is-invalid')) qtyInp.focus();
                    else if (unitWeightInp && unitWeightInp.classList.contains('is-invalid')) unitWeightInp.focus();
                    else if (totalWeightInp && totalWeightInp.classList.contains('is-invalid')) totalWeightInp.focus();
                    else if (basePriceInp && basePriceInp.classList.contains('is-invalid')) basePriceInp.focus();
                    else if (discountPriceInp && discountPriceInp.classList.contains('is-invalid')) discountPriceInp.focus();
                    return false;
                }
            }

            // 4. Weight matching validation against Cutting Usable Weight
            if (currentCuttingUsableWeight > 0) {
                let sumStitchWeight = 0;
                document.querySelectorAll('.stitching-weight-input').forEach(inp => {
                    sumStitchWeight += parseFloat(inp.value) || 0;
                });
                sumStitchWeight = Math.round(sumStitchWeight * 100) / 100;
                const maxAllowedWeight = Math.round((currentCuttingUsableWeight + 0.01) * 100) / 100;

                if (sumStitchWeight > maxAllowedWeight) {
                    e.preventDefault();
                    alert(`Validation Error: Total Stitching Weight (${sumStitchWeight.toFixed(2)} kg) exceeds the Source Cutting Batch Usable Weight (${currentCuttingUsableWeight.toFixed(2)} kg).\n\nThe stitching weight must match the cutting weight (${currentCuttingUsableWeight.toFixed(2)} kg). Please adjust the Per Piece Weight or Total Weight before saving.`);
                    const firstWeightInp = document.querySelector('.stitching-weight-input');
                    firstWeightInp?.focus();
                    return false;
                }
            }
        });
    }
});

window.addEventListener('pageshow', () => {
    const cuttingSelect = document.getElementById('cuttingBatchSelect');
    const tbody = document.getElementById('variantsTableBody');
    if (cuttingSelect && cuttingSelect.value && (!tbody || tbody.children.length === 0)) {
        handleCuttingBatchChange(cuttingSelect.value);
    }
});
</script>
