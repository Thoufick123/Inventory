<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1 text-dark fw-bold"><i class="bi bi-qr-code-scan me-2 text-danger"></i>Log Damaged Stock &amp; Returns Entry</h2>
        <span class="text-muted">Scan piece QR/barcode tags, build multi-item return/damage batches, inspect style codes, and deduct inventory</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('warehouse/damage') ?>" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Damage List
        </a>
    </div>
</div>

<form method="POST" action="<?= url('warehouse/damage/store') ?>" enctype="multipart/form-data" id="damageEntryForm">
    <?= CSRF::input() ?>
    <input type="hidden" name="items" id="hiddenItemsJson" value="[]">

    <!-- TOP SCANNER & WAREHOUSE SELECTION BAR -->
    <div class="card border shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="row g-3 align-items-center">
                <div class="col-lg-4">
                    <label class="form-label text-dark small fw-bold text-uppercase mb-1 d-flex align-items-center gap-1">
                        <i class="bi bi-geo-alt-fill text-danger"></i> <span>Warehouse Location</span> <span class="text-danger">*</span>
                    </label>
                    <select name="warehouse_id" id="warehouseSelect" class="form-select form-select-lg bg-white text-dark border fw-bold shadow-sm" style="color: #000000 !important; font-weight: 700 !important;" required <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                        <?php foreach ($warehouses as $idx => $wh): ?>
                            <option value="<?= $wh['id'] ?>" <?= $idx === 0 ? 'selected' : '' ?> style="background-color: #ffffff; color: #000000; font-weight: 600;">
                                <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1): ?>
                        <input type="hidden" name="warehouse_id" value="<?= e($warehouses[0]['id'] ?? (function_exists('currentWarehouseId') ? currentWarehouseId() : '')) ?>">
                    <?php endif; ?>
                </div>

                <div class="col-lg-8">
                    <label class="form-label text-dark small fw-bold text-uppercase mb-1">
                        <i class="bi bi-upc-scan text-danger me-1"></i> Scan Piece QR Tag / Barcode or Type Code / SKU / Batch #
                    </label>
                    <div class="position-relative">
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-light border text-danger">
                                <i class="bi bi-qr-code-scan fs-4"></i>
                            </span>
                            <input type="text" id="scanInput" class="form-control form-control-lg bg-white text-dark border fw-mono" 
                                   placeholder="Type SKU, Product, Batch # or scan piece QR/barcode..." 
                                   autocomplete="off" autofocus>
                            <button type="button" class="btn btn-warning px-4 fw-bold d-flex align-items-center gap-1 text-dark shadow-sm" id="btnLookupScan" title="Lookup / Add to Queue">
                                <i class="bi bi-plus-circle"></i> <span>Add / Lookup</span>
                            </button>
                        </div>
                        <!-- Live Instant Auto-Suggest Dropdown on Typing -->
                        <div id="liveSearchResults" class="position-absolute start-0 end-0 bg-white text-dark rounded-3 shadow-lg border mt-1 overflow-auto d-none" style="max-height: 380px; z-index: 1050;">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                        <small class="text-muted">
                            <i class="bi bi-keyboard me-1 text-secondary"></i> Type any SKU, product name, or batch # &amp; hit <strong>Enter</strong> or click <strong>Add</strong>.
                        </small>
                        <a href="javascript:void(0)" class="small text-primary text-decoration-none fw-semibold" id="toggleManualDropdown">
                            <i class="bi bi-list-ul me-1"></i> Manual SKU / Catalog Picker Fallback
                        </a>
                    </div>
                </div>
            </div>

            <!-- Optional Manual Variant Select Dropdown -->
            <div class="mt-3 pt-3 border-top d-none" id="manualSelectContainer">
                <label class="form-label text-dark small fw-bold text-uppercase">Pick Product Variant from Catalog</label>
                <div class="input-group">
                    <select id="variantSelectManual" class="form-select bg-white text-dark border">
                        <option value="">-- Choose Product Style &amp; Variant SKU --</option>
                        <?php foreach ($variants as $v): 
                            $hParts = array_filter([$v['category_name'] ?? '', $v['subcategory_name'] ?? '', $v['sub_subcategory_name'] ?? '']);
                            $hBadge = !empty($hParts) ? ('[' . implode(' › ', $hParts) . '] ') : '';
                        ?>
                            <option value="<?= $v['sku'] ?>" data-id="<?= $v['id'] ?>">
                                <?= e($hBadge) ?>[<?= e($v['product_code'] ?? 'CODE') ?>] <?= e($v['product_name']) ?> | SKU: <?= e($v['sku']) ?> (<?= e($v['size_name'] ?? 'Std') ?> / <?= e($v['color_name'] ?? 'Std') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-primary" id="btnAddManualVariant">
                        <i class="bi bi-plus-lg me-1"></i> Add to Queue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCAN STATUS FEEDBACK BANNER -->
    <div id="scanFeedback" class="alert alert-info d-none align-items-center justify-content-between shadow-sm rounded-3 py-2 px-3 mb-4">
        <div class="d-flex align-items-center gap-2">
            <div class="spinner-border spinner-border-sm text-primary" role="status" id="scanSpinner"></div>
            <span id="scanFeedbackMsg" class="fw-semibold">Looking up scanned piece tag...</span>
        </div>
        <button type="button" class="btn-close" onclick="document.getElementById('scanFeedback').classList.add('d-none')"></button>
    </div>

    <!-- 2. SCANNED DAMAGE & RETURNS BATCH LIST TABLE -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-list-check me-2 text-danger"></i>Scanned Items Queue
                </h5>
                <span class="badge bg-danger rounded-pill px-2.5 py-1" id="queueBadgeCount">0 items</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearQueue" onclick="clearQueue()" style="display: none;">
                    <i class="bi bi-trash3 me-1"></i> Clear All
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="queueTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-center py-3 text-uppercase fs-7 fw-bold text-muted" style="width: 48px;">#</th>
                        <th class="text-start py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 140px;">Piece Tag</th>
                        <th class="text-start py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 250px;">Product &amp; Style Codes</th>
                        <th class="text-center py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 120px;">Available Stock</th>
                        <th class="text-start py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 190px;">Damage Reason</th>
                        <th class="text-center py-3 text-uppercase fs-7 fw-bold text-muted" style="width: 100px;">Qty (pcs)</th>
                        <th class="text-center py-3 text-uppercase fs-7 fw-bold text-muted" style="width: 130px;">Unit Price (₹)</th>
                        <th class="text-end py-3 text-uppercase fs-7 fw-bold text-muted" style="width: 120px;">Total Loss</th>
                        <th class="text-center pe-3 py-3 text-uppercase fs-7 fw-bold text-muted" style="width: 70px;">Cancel</th>
                    </tr>
                </thead>
                <tbody id="queueTableBody">
                    <tr id="emptyQueueRow">
                        <td colspan="9" class="text-center py-5 text-muted">
                            <div class="bg-light d-inline-flex p-4 rounded-circle mb-3 shadow-xs">
                                <i class="bi bi-upc-scan fs-1 text-danger"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">No items scanned in queue</h6>
                            <p class="small text-muted mb-0">Point barcode scanner or scan piece QR tags above to log items into this damage batch.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- BATCH SUMMARY FOOTER BAR -->
        <div class="card-footer bg-light p-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="queueSummaryFooter" style="display: none !important;">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div>
                    <span class="text-muted small text-uppercase fw-bold d-block">Total Unique Styles</span>
                    <h5 class="fw-bold text-dark mb-0" id="summaryTotalStyles">0</h5>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-bold d-block">Total Units</span>
                    <h5 class="fw-bold text-danger mb-0" id="summaryTotalQty">0 pcs</h5>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-bold d-block">Total Valuation Loss</span>
                    <h5 class="fw-bold text-danger mb-0" id="summaryTotalLoss">₹0.00</h5>
                </div>
            </div>
            <div class="text-muted small">
                <i class="bi bi-shield-check text-success me-1"></i> Stock balances will be automatically adjusted for all items upon confirmation.
            </div>
        </div>
    </div>

    <!-- 3. NOTES & DUAL EXECUTION ACTION BAR -->
    <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
        <div class="row g-3 mb-4">
            <div class="col-md-8">
                <label class="form-label small fw-bold text-uppercase text-muted">Batch Defect Notes / Inspector Observations</label>
                <textarea name="defect_notes" class="form-control" rows="2" placeholder="e.g. Packing box damaged during inward transit, zippers missing on batch..."></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-uppercase text-muted d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-camera me-1 text-primary"></i>Batch Photo Evidence (Optional)</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;"><i class="bi bi-cloud-arrow-up me-1"></i>Cloudinary CDN</span>
                </label>
                <input type="file" name="photo" id="damagePhotoInput" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div id="photoPreviewContainer" class="mt-2 d-none text-center">
                    <img id="photoPreviewImg" src="" alt="Damage Preview" class="img-thumbnail shadow-xs rounded" style="max-height: 120px;">
                    <div class="small text-muted mt-1"><i class="bi bi-check-circle-fill text-success me-1"></i>Ready for Cloudinary upload</div>
                </div>
                <small class="text-muted d-block mt-1">Uploaded securely to Cloudinary cloud storage (Max 5MB).</small>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 border-top">
            <div>
                <span class="fw-bold text-dark d-block fs-6">Select Batch Disposition Action:</span>
                <span class="text-muted small">Choose whether to quarantine as warehouse damage or dispatch directly to factory rework.</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('warehouse/damage') ?>" class="btn btn-light px-3 py-2">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </a>

                <!-- 🟡 FACTORY REWORK BUTTON -->
                <button type="submit" name="action_type" value="rework" class="btn btn-warning px-4 py-2.5 fw-bold text-dark shadow-sm d-inline-flex align-items-center gap-2" id="btnSubmitRework">
                    <i class="bi bi-arrow-repeat fs-5"></i>
                    <span>Send All to Factory Rework &amp; Deduct Stock</span>
                </button>

                <!-- 🔴 CONFIRM SCRAP / DAMAGE BUTTON -->
                <button type="submit" name="action_type" value="damage" class="btn btn-danger px-4 py-2.5 fw-bold shadow-sm d-inline-flex align-items-center gap-2" id="btnSubmitDamage">
                    <i class="bi bi-slash-circle fs-5"></i>
                    <span>Log All Damage (Quarantine) &amp; Deduct Stock</span>
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scanInput = document.getElementById('scanInput');
    const warehouseSelect = document.getElementById('warehouseSelect');
    const btnLookupScan = document.getElementById('btnLookupScan');
    const liveSearchResults = document.getElementById('liveSearchResults');
    const toggleManualDropdown = document.getElementById('toggleManualDropdown');
    const manualSelectContainer = document.getElementById('manualSelectContainer');
    const variantSelectManual = document.getElementById('variantSelectManual');
    const btnAddManualVariant = document.getElementById('btnAddManualVariant');
    
    const scanFeedback = document.getElementById('scanFeedback');
    const scanFeedbackMsg = document.getElementById('scanFeedbackMsg');
    const scanSpinner = document.getElementById('scanSpinner');
    
    const queueTableBody = document.getElementById('queueTableBody');
    const emptyQueueRow = document.getElementById('emptyQueueRow');
    const queueBadgeCount = document.getElementById('queueBadgeCount');
    const btnClearQueue = document.getElementById('btnClearQueue');
    const queueSummaryFooter = document.getElementById('queueSummaryFooter');
    const summaryTotalStyles = document.getElementById('summaryTotalStyles');
    const summaryTotalQty = document.getElementById('summaryTotalQty');
    const summaryTotalLoss = document.getElementById('summaryTotalLoss');
    const hiddenItemsJson = document.getElementById('hiddenItemsJson');
    const damageEntryForm = document.getElementById('damageEntryForm');

    // Scanned items in-memory queue
    let queue = [];
    let searchDebounceTimer = null;
    let selectedSuggestionIndex = -1;

    // Synthesize subtle audio beep on scan
    function playBeep() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 tone
            gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.12);
        } catch (e) {}
    }

    // Toggle manual dropdown fallback
    toggleManualDropdown.addEventListener('click', function() {
        manualSelectContainer.classList.toggle('d-none');
    });

    btnAddManualVariant.addEventListener('click', function() {
        const val = variantSelectManual.value;
        if (val) {
            lookupProduct(val);
            variantSelectManual.value = '';
        }
    });

    // Hide live search results
    function hideLiveSearch() {
        if (liveSearchResults) {
            liveSearchResults.classList.add('d-none');
            liveSearchResults.innerHTML = '';
            selectedSuggestionIndex = -1;
        }
    }

    // Render Live Search Dropdown Suggestions
    function renderLiveSearch(matches, query) {
        if (!liveSearchResults) return;
        if (!matches || matches.length === 0) {
            liveSearchResults.innerHTML = `
                <div class="p-3 text-center text-muted">
                    <i class="bi bi-search me-1"></i> No matching products found for "<strong>${escapeHtml(query)}</strong>".
                </div>
            `;
            liveSearchResults.classList.remove('d-none');
            return;
        }

        let html = `
            <div class="p-2 border-bottom bg-light d-flex justify-content-between align-items-center">
                <small class="fw-bold text-muted text-uppercase" style="font-size: 0.75rem;">
                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Matching Products &amp; Variants (${matches.length})
                </small>
                <small class="text-muted" style="font-size: 0.72rem;">Click or press Enter to add</small>
            </div>
            <div class="list-group list-group-flush">
        `;

        matches.forEach((m, idx) => {
            html += `
                <a href="javascript:void(0)" class="list-group-item list-group-item-action p-2.5 d-flex align-items-center justify-content-between suggestion-item" data-index="${idx}">
                    <div class="me-2 text-truncate">
                        <div class="d-flex align-items-center gap-1 mb-0.5">
                            <span class="fw-bold text-dark text-truncate" style="max-width: 320px;">${escapeHtml(m.product_name)}</span>
                            ${m.category_hierarchy ? `<span class="badge bg-light text-primary border small font-monospace px-1.5 py-0.5" style="font-size: 0.68rem;">${escapeHtml(m.category_hierarchy)}</span>` : ''}
                        </div>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span class="badge bg-dark text-white font-monospace small px-1.5 py-0.5">${escapeHtml(m.sku)}</span>
                            <span class="badge bg-light text-secondary border small px-1.5 py-0.5">${escapeHtml(m.size_name || 'Free')} / ${escapeHtml(m.color_name || 'Std')}</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle small px-1.5 py-0.5 fw-bold"><i class="bi bi-boxes me-1"></i>${m.available_stock} pcs in WH</span>
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <div class="fw-bold text-danger small">₹${parseFloat(m.cost_per_unit || 0).toFixed(2)}</div>
                        <button type="button" class="btn btn-xs btn-primary fw-semibold px-2 py-0.5 mt-1 btn-add-suggest" data-sku="${escapeHtml(m.sku)}">
                            <i class="bi bi-plus"></i> Add
                        </button>
                    </div>
                </a>
            `;
        });

        html += '</div>';
        liveSearchResults.innerHTML = html;
        liveSearchResults.classList.remove('d-none');

        // Add click events to suggestion items
        liveSearchResults.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const idx = parseInt(this.getAttribute('data-index'));
                if (matches[idx]) {
                    lookupProduct(matches[idx].sku);
                }
            });
        });
    }

    // Lookup function via AJAX (supports both single exact scan and manual typed search)
    function lookupProduct(code) {
        let query = (code || scanInput.value || '').trim();
        if (!query) return;

        hideLiveSearch();
        scanInput.value = '';
        scanInput.focus();

        const warehouseId = warehouseSelect.value;
        
        // Show loading status
        scanFeedback.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-secondary');
        scanFeedback.classList.add('alert-info');
        scanSpinner.classList.remove('d-none');
        scanFeedbackMsg.innerText = 'Looking up code: ' + query + '...';

        fetch('<?= url("api/warehouse/damage/lookup-scan") ?>?code=' + encodeURIComponent(query) + '&warehouse_id=' + warehouseId)
            .then(res => res.json())
            .then(data => {
                scanSpinner.classList.add('d-none');
                if (data.success && data.variant) {
                    playBeep();
                    const v = data.variant;
                    
                    // Add or increment in queue
                    addItemToQueue(v);

                    scanFeedback.classList.remove('alert-info', 'alert-danger');
                    scanFeedback.classList.add('alert-success');
                    scanFeedbackMsg.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> Added to list: <strong>' + v.product_name + '</strong> (SKU: <code>' + v.sku + '</code> | Available: <strong>' + v.available_stock + ' pcs</strong>)' + (v.tag_code ? ' [Tag: <strong>' + v.tag_code + '</strong>]' : '');
                } else {
                    scanFeedback.classList.remove('alert-info', 'alert-success');
                    scanFeedback.classList.add('alert-danger');
                    scanFeedbackMsg.innerHTML = '<i class="bi bi-exclamation-octagon-fill text-danger me-1"></i> ' + (data.message || 'Variant not found');
                }
            })
            .catch(err => {
                scanSpinner.classList.add('d-none');
                scanFeedback.classList.remove('alert-info', 'alert-success');
                scanFeedback.classList.add('alert-danger');
                scanFeedbackMsg.innerText = 'Network error looking up product. Please try again.';
            });
    }

    // Add Item to queue
    function addItemToQueue(variant) {
        // If unique piece tag exists, check if already present in queue
        if (variant.tag_code) {
            const existingTagIdx = queue.findIndex(item => item.tag_code && item.tag_code.toUpperCase() === variant.tag_code.toUpperCase());
            if (existingTagIdx !== -1) {
                scanFeedback.classList.remove('alert-info', 'alert-danger');
                scanFeedback.classList.add('alert-warning');
                scanFeedbackMsg.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Piece tag <strong>' + variant.tag_code + '</strong> is already added to the queue (Row #' + (existingTagIdx + 1) + ').';
                return;
            }
        } else {
            // If no unique piece tag, check if same variant_id exists -> increment quantity
            const existingVariantIdx = queue.findIndex(item => !item.tag_code && item.variant_id === variant.id);
            if (existingVariantIdx !== -1) {
                queue[existingVariantIdx].quantity += 1;
                queue[existingVariantIdx].total_loss = queue[existingVariantIdx].quantity * queue[existingVariantIdx].cost_per_unit;
                renderQueue();
                return;
            }
        }

        const unitPrice = parseFloat(
            variant.selling_price > 0 ? variant.selling_price : 
            (variant.cost_per_unit > 0 ? variant.cost_per_unit : 
            (variant.mrp > 0 ? variant.mrp : 
            (variant.purchase_price || 0)))
        ) || 0;
        const qty = 1;

        queue.push({
            variant_id: variant.id,
            product_name: variant.product_name,
            product_code: variant.product_code || 'CODE',
            sku: variant.sku,
            size_name: variant.size_name || 'Free',
            color_name: variant.color_name || 'Std',
            tag_code: variant.tag_code || '',
            category_name: variant.category_name || '',
            subcategory_name: variant.subcategory_name || '',
            sub_subcategory_name: variant.sub_subcategory_name || '',
            category_hierarchy: variant.category_hierarchy || '',
            available_stock: variant.available_stock || 0,
            quantity: qty,
            mrp: parseFloat(variant.mrp || 0),
            selling_price: parseFloat(variant.selling_price || 0),
            cost_per_unit: unitPrice,
            total_loss: qty * unitPrice,
            source_type: 'STORAGE_HANDLING',
            damage_reason: 'Fabric Tear / Cut',
            defect_notes: ''
        });

        renderQueue();
    }

    // Render Queue Table
    function renderQueue() {
        if (queue.length === 0) {
            emptyQueueRow.style.display = '';
            queueBadgeCount.innerText = '0 items';
            btnClearQueue.style.display = 'none';
            queueSummaryFooter.style.setProperty('display', 'none', 'important');
            hiddenItemsJson.value = '[]';
            return;
        }

        emptyQueueRow.style.display = 'none';
        queueBadgeCount.innerText = queue.length + (queue.length === 1 ? ' item' : ' items');
        btnClearQueue.style.display = '';
        queueSummaryFooter.style.setProperty('display', 'flex', 'important');

        // Clear existing rows except emptyQueueRow
        const rows = queueTableBody.querySelectorAll('.queue-item-row');
        rows.forEach(r => r.remove());

        let totalUnits = 0;
        let totalValuation = 0;

        queue.forEach((item, index) => {
            totalUnits += parseInt(item.quantity) || 0;
            totalValuation += parseFloat(item.total_loss) || 0;

            const tr = document.createElement('tr');
            tr.className = 'queue-item-row';
            tr.innerHTML = `
                <td class="text-center text-muted fw-bold">${index + 1}</td>
                <td class="text-start text-nowrap">
                    ${item.tag_code ? `<span class="badge bg-dark font-monospace text-warning px-2.5 py-1.5 shadow-xs d-inline-flex align-items-center gap-1"><i class="bi bi-qr-code"></i> ${item.tag_code}</span>` : `<span class="badge bg-light text-muted border text-nowrap"><i class="bi bi-dash me-1"></i>No Tag</span>`}
                </td>
                <td class="text-start">
                    <div class="fw-bold text-dark mb-1" style="font-size: 0.92rem; line-height: 1.35; max-width: 270px;">
                        ${item.product_name}
                    </div>
                    ${(item.category_hierarchy || item.category_name) ? `<div class="mb-1"><span class="badge bg-light text-primary border font-monospace small px-2 py-0.5"><i class="bi bi-diagram-3-fill me-1 text-warning"></i>${item.category_hierarchy || item.category_name}</span></div>` : ''}
                    <div class="d-flex flex-wrap align-items-center gap-1 mb-1">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace small px-2 py-0.5">Code: ${item.product_code}</span>
                        <span class="badge bg-dark-subtle text-dark-emphasis font-monospace border small px-2 py-0.5">${item.sku}</span>
                        <span class="badge bg-light text-secondary border small px-2 py-0.5">${item.size_name} / ${item.color_name}</span>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-1">
                        <span class="badge bg-light text-muted border small px-2 py-0.5"><i class="bi bi-tag-fill me-1 text-muted"></i>MRP: ₹${parseFloat(item.mrp || 0).toFixed(2)}</span>
                        ${item.selling_price > 0 ? `<span class="badge bg-light text-secondary border small px-2 py-0.5">Sale: ₹${parseFloat(item.selling_price || 0).toFixed(2)}</span>` : ''}
                    </div>
                </td>
                <td class="text-center text-nowrap">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold">
                        <i class="bi bi-boxes me-1"></i>${item.available_stock} pcs
                    </span>
                </td>
                <td class="text-start">
                    <select class="form-select form-select-sm" style="min-width: 185px;" onchange="updateItemField(${index}, 'damage_reason', this.value)">
                        <option value="Fabric Tear / Cut" ${item.damage_reason === 'Fabric Tear / Cut' ? 'selected' : ''}>Fabric Tear / Cut</option>
                        <option value="Stitching / Seam Opening" ${item.damage_reason === 'Stitching / Seam Opening' ? 'selected' : ''}>Stitching / Seam Opening</option>
                        <option value="Water / Moisture Contamination" ${item.damage_reason === 'Water / Moisture Contamination' ? 'selected' : ''}>Water / Moisture</option>
                        <option value="Stain / Oil Contamination" ${item.damage_reason === 'Stain / Oil Contamination' ? 'selected' : ''}>Stain / Oil</option>
                        <option value="Color Bleed / Discoloration" ${item.damage_reason === 'Color Bleed / Discoloration' ? 'selected' : ''}>Color Bleed</option>
                        <option value="Broken Button / Zipper / Trims" ${item.damage_reason === 'Broken Button / Zipper / Trims' ? 'selected' : ''}>Broken Button/Zipper</option>
                        <option value="Crushed / Compromised Packaging" ${item.damage_reason === 'Crushed / Compromised Packaging' ? 'selected' : ''}>Crushed Packaging</option>
                        <option value="Transit Physical Impact" ${item.damage_reason === 'Transit Physical Impact' ? 'selected' : ''}>Transit Impact</option>
                        <option value="Other Defect" ${item.damage_reason === 'Other Defect' ? 'selected' : ''}>Other Defect</option>
                    </select>
                </td>
                <td class="text-center">
                    <input type="number" min="1" class="form-control form-control-sm fw-bold text-center mx-auto" style="max-width: 75px;" value="${item.quantity}" onchange="updateItemQty(${index}, this.value)">
                </td>
                <td class="text-center">
                    <div class="input-group input-group-sm mx-auto shadow-xs" style="min-width: 125px; max-width: 135px;">
                        <span class="input-group-text bg-light fw-bold text-muted px-2">₹</span>
                        <input type="text" class="form-control form-control-sm fw-bold text-end pe-2 bg-light text-dark" value="${parseFloat(item.cost_per_unit).toFixed(2)}" readonly tabindex="-1" style="background-color: #f1f5f9 !important; cursor: not-allowed; font-weight: 700;">
                    </div>
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.68rem;">(Selling Price)</small>
                </td>
                <td class="text-end text-nowrap pe-2">
                    <span class="fw-bold text-danger fs-6">₹${parseFloat(item.total_loss).toFixed(2)}</span>
                </td>
                <td class="text-center pe-3 text-nowrap">
                    <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 shadow-xs" onclick="removeQueueItem(${index})" title="Cancel / Remove this item">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                </td>
            `;
            queueTableBody.appendChild(tr);
        });

        summaryTotalStyles.innerText = queue.length;
        summaryTotalQty.innerText = totalUnits + ' pcs';
        summaryTotalLoss.innerText = '₹' + totalValuation.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        hiddenItemsJson.value = JSON.stringify(queue);
    }

    // Update item field
    window.updateItemField = function(index, field, value) {
        if (queue[index]) {
            queue[index][field] = value;
            hiddenItemsJson.value = JSON.stringify(queue);
        }
    };

    // Update quantity
    window.updateItemQty = function(index, value) {
        const qty = Math.max(1, parseInt(value) || 1);
        if (queue[index]) {
            queue[index].quantity = qty;
            queue[index].total_loss = qty * (parseFloat(queue[index].cost_per_unit) || 0);
            renderQueue();
        }
    };

    // Update cost
    window.updateItemCost = function(index, value) {
        const cost = Math.max(0, parseFloat(value) || 0);
        if (queue[index]) {
            queue[index].cost_per_unit = cost;
            queue[index].total_loss = (parseInt(queue[index].quantity) || 1) * cost;
            renderQueue();
        }
    };

    // Remove single item from queue
    window.removeQueueItem = function(index) {
        if (queue[index]) {
            const removed = queue.splice(index, 1)[0];
            renderQueue();
            scanFeedback.classList.remove('alert-info', 'alert-success', 'alert-danger');
            scanFeedback.classList.add('alert-secondary');
            scanFeedbackMsg.innerHTML = '<i class="bi bi-trash me-1"></i> Removed: <strong>' + removed.product_name + '</strong> from queue.';
        }
    };

    // Clear all items from queue
    window.clearQueue = function() {
        if (queue.length > 0 && confirm('Are you sure you want to clear all ' + queue.length + ' scanned items from the list?')) {
            queue = [];
            renderQueue();
            scanFeedback.classList.add('d-none');
            scanInput.focus();
        }
    };

    // Utility: escape HTML for safe rendering in suggestions
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    // Auto-lookup on paste
    scanInput.addEventListener('paste', function(e) {
        setTimeout(() => {
            lookupProduct(this.value);
        }, 50);
    });

    // Debounced live search suggestions while typing manually
    scanInput.addEventListener('input', function(e) {
        const val = this.value.trim();
        clearTimeout(searchDebounceTimer);
        
        if (val.length < 2) {
            hideLiveSearch();
            return;
        }

        searchDebounceTimer = setTimeout(() => {
            const warehouseId = warehouseSelect.value;
            fetch('<?= url("api/warehouse/damage/lookup-scan") ?>?code=' + encodeURIComponent(val) + '&warehouse_id=' + warehouseId)
                .then(res => res.json())
                .then(data => {
                    if (data.matches && data.matches.length > 0) {
                        renderLiveSearch(data.matches, val);
                    } else if (data.success && data.variant) {
                        renderLiveSearch([data.variant], val);
                    } else {
                        renderLiveSearch([], val);
                    }
                })
                .catch(() => {
                    hideLiveSearch();
                });
        }, 220);
    });

    // Keyboard navigation (Arrow Up, Arrow Down, Escape, Enter)
    scanInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideLiveSearch();
            return;
        }

        const items = liveSearchResults ? liveSearchResults.querySelectorAll('.suggestion-item') : [];
        if (e.key === 'ArrowDown') {
            if (items.length > 0) {
                e.preventDefault();
                selectedSuggestionIndex = (selectedSuggestionIndex + 1) % items.length;
                updateSuggestionHighlight(items);
            }
            return;
        }

        if (e.key === 'ArrowUp') {
            if (items.length > 0) {
                e.preventDefault();
                selectedSuggestionIndex = (selectedSuggestionIndex - 1 + items.length) % items.length;
                updateSuggestionHighlight(items);
            }
            return;
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedSuggestionIndex >= 0 && items[selectedSuggestionIndex]) {
                items[selectedSuggestionIndex].click();
            } else {
                lookupProduct(this.value);
            }
        }
    });

    function updateSuggestionHighlight(items) {
        items.forEach((it, idx) => {
            if (idx === selectedSuggestionIndex) {
                it.classList.add('active', 'bg-primary-subtle', 'text-primary');
                it.scrollIntoView({ block: 'nearest' });
            } else {
                it.classList.remove('active', 'bg-primary-subtle', 'text-primary');
            }
        });
    }

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (liveSearchResults && !liveSearchResults.contains(e.target) && e.target !== scanInput && e.target !== btnLookupScan) {
            hideLiveSearch();
        }
    });

    btnLookupScan.addEventListener('click', function() {
        lookupProduct(scanInput.value);
    });

    // Form Submission Validation
    damageEntryForm.addEventListener('submit', function(e) {
        if (queue.length === 0) {
            e.preventDefault();
            alert('Please scan or add at least one product to the list before submitting.');
            scanInput.focus();
            return false;
        }
        hiddenItemsJson.value = JSON.stringify(queue);
    });

    // Photo preview listener
    const photoInput = document.getElementById('damagePhotoInput');
    const photoContainer = document.getElementById('photoPreviewContainer');
    const photoImg = document.getElementById('photoPreviewImg');
    if (photoInput && photoContainer && photoImg) {
        photoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    alert('Photo file size exceeds 5MB. Please choose a smaller image.');
                    this.value = '';
                    photoContainer.classList.add('d-none');
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoImg.src = e.target.result;
                    photoContainer.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                photoContainer.classList.add('d-none');
            }
        });
    }

});
</script>
