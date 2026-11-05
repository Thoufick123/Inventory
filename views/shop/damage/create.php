<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-3 py-1 rounded-pill fw-semibold">
                <i class="bi bi-shop me-1"></i><?= e($shop['name']) ?>
            </span>
            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 font-monospace">
                <?= e($shop['code'] ?? 'SHOP-01') ?>
            </span>
        </div>
        <h2 class="mb-1 text-dark fw-bold"><i class="bi bi-qr-code-scan me-2 text-danger"></i>Log Shop Damaged Stock Entry</h2>
        <span class="text-muted">Scan piece QR tags/barcodes from shop bins or shelves, inspect defect reason, and move to quarantine list</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('shop/damage') ?>" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Damage List
        </a>
    </div>
</div>

<form method="POST" action="<?= url('shop/damage/store') ?>" id="damageEntryForm">
    <?= CSRF::input() ?>
    <input type="hidden" name="items" id="hiddenItemsJson" value="[]">
    <input type="hidden" name="shop_id" value="<?= (int)$shop['id'] ?>">

    <!-- TOP SCANNER & SHOP LOCATION BAR -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-gradient overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff;">
        <div class="card-body p-4">
            <div class="row g-3 align-items-center">
                <div class="col-12">
                    <label class="form-label text-white-50 small fw-bold text-uppercase mb-1">
                        <i class="bi bi-upc-scan text-warning me-1"></i> Scan Piece QR Tag / Barcode or Type SKU / Product Name
                    </label>
                    <div class="position-relative">
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-dark border-secondary text-warning">
                                <i class="bi bi-qr-code-scan fs-4"></i>
                            </span>
                            <input type="text" id="scanInput" class="form-control form-control-lg bg-dark text-white border-secondary font-monospace" 
                                   placeholder="Type SKU, Product name, or scan barcode gun..." 
                                   autocomplete="off" autofocus>
                            <button type="button" class="btn btn-warning px-4 fw-bold d-flex align-items-center gap-1" id="btnLookupScan" title="Lookup / Add to Queue">
                                <i class="bi bi-plus-circle"></i> <span>Add to Queue</span>
                            </button>
                        </div>
                        <!-- Live Instant Auto-Suggest Dropdown on Typing -->
                        <div id="liveSearchResults" class="position-absolute start-0 end-0 bg-white text-dark rounded-3 shadow-lg border mt-1 overflow-auto d-none" style="max-height: 380px; z-index: 1050;">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                        <small class="text-white-50">
                            <i class="bi bi-keyboard me-1 text-warning"></i> Type any SKU or piece tag &amp; hit <strong>Enter</strong> or click <strong>Add</strong>.
                        </small>
                        <a href="javascript:void(0)" class="small text-warning text-decoration-none fw-semibold" id="toggleManualDropdown">
                            <i class="bi bi-list-ul me-1"></i> Manual Catalog Picker Fallback
                        </a>
                    </div>
                </div>
            </div>

            <!-- Optional Manual Variant Select Dropdown -->
            <div class="mt-3 pt-3 border-top border-secondary d-none" id="manualSelectContainer">
                <label class="form-label text-white-50 small fw-bold text-uppercase">Pick Product Variant from Catalog</label>
                <div class="input-group">
                    <select id="variantSelectManual" class="form-select bg-dark text-white border-secondary">
                        <option value="">-- Choose Product Style &amp; Variant SKU --</option>
                        <?php foreach ($variants as $v): 
                            $hParts = array_filter([$v['category_name'] ?? '', $v['subcategory_name'] ?? '', $v['sub_subcategory_name'] ?? '']);
                            $hBadge = !empty($hParts) ? ('[' . implode(' › ', $hParts) . '] ') : '';
                        ?>
                            <option value="<?= $v['sku'] ?>" data-id="<?= $v['id'] ?>" <?= ($prefillVariantId === (int)$v['id']) ? 'selected' : '' ?>>
                                <?= e($hBadge) ?>[<?= e($v['product_code'] ?? 'CODE') ?>] <?= e($v['product_name']) ?> | SKU: <?= e($v['sku']) ?> (<?= e($v['size_name'] ?? 'Std') ?> / <?= e($v['color_name'] ?? 'Std') ?>) — Stock: <?= (int)$v['available_qty'] ?> pcs
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

    <!-- 2. SCANNED DAMAGE QUEUE TABLE -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger text-white rounded-pill px-2.5 py-1 fs-6" id="badgeQueueCount">0 Items</span>
                <h6 class="card-title mb-0 fw-bold text-dark">Damaged Stock Queue to be Quarantined</h6>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearQueue" onclick="clearQueue()">
                <i class="bi bi-trash me-1"></i> Clear All
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0" id="queueTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3 text-nowrap" style="width: 48px;">#</th>
                            <th class="text-nowrap" style="min-width: 220px;">Product &amp; SKU</th>
                            <th class="text-nowrap" style="min-width: 140px;">Scanned Tag</th>
                            <th class="text-nowrap" style="min-width: 180px;">Damage Reason / Notes</th>
                            <th class="text-center text-nowrap" style="width: 110px;">Damaged Qty</th>
                            <th class="text-end text-nowrap" style="width: 110px;">Unit Cost</th>
                            <th class="text-end text-nowrap" style="width: 120px;">Total Loss</th>
                            <th class="text-center text-nowrap pe-3" style="width: 60px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="queueTableBody">
                        <tr id="emptyQueueRow">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="bi bi-qr-code text-danger opacity-25" style="font-size: 4rem;"></i>
                                    <h6 class="fw-semibold text-dark mt-2">No items added to damage queue yet</h6>
                                    <p class="small text-muted mb-0">Scan barcode, piece QR code, or type SKU above to queue products for damage reporting.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. ACTIONS & SUBMISSION BAR -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="p-3 bg-light rounded-3 border d-flex flex-wrap justify-content-between align-items-center gap-3 w-100 ms-lg-auto mb-3" style="max-width: 700px;">
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">Total Damaged Units:</small>
                    <strong class="text-dark fs-6" id="summaryTotalUnits">0 pcs</strong>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">Total Estimated Loss:</small>
                    <strong class="text-danger fs-5" id="summaryTotalLoss">₹0.00</strong>
                </div>
            </div>

            <div class="border-top pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <a href="<?= url('shop/damage') ?>" class="btn btn-light rounded-pill px-4">Cancel</a>
                <button type="submit" class="btn btn-danger rounded-pill px-4 px-md-5 fw-bold shadow-sm" id="btnSubmitDamage" disabled>
                    <i class="bi bi-check2-circle me-1"></i> Confirm &amp; Move to Damage Section
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    const LOOKUP_API_URL = "<?= url('api/shop/damage/lookup-scan') ?>";
    const SHOP_ID = <?= (int)$shop['id'] ?>;
    let queueItems = [];

    document.addEventListener('DOMContentLoaded', () => {
        const scanInput = document.getElementById('scanInput');
        const btnLookup = document.getElementById('btnLookupScan');
        const toggleManual = document.getElementById('toggleManualDropdown');
        const manualContainer = document.getElementById('manualSelectContainer');
        const btnAddManual = document.getElementById('btnAddManualVariant');
        const variantSelectManual = document.getElementById('variantSelectManual');
        const liveResults = document.getElementById('liveSearchResults');

        // Enter key in scan input triggers lookup
        scanInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                performLookup(scanInput.value.trim());
            }
        });

        // Click Add/Lookup button
        btnLookup.addEventListener('click', () => {
            performLookup(scanInput.value.trim());
        });

        // Toggle Manual Dropdown
        if (toggleManual) {
            toggleManual.addEventListener('click', () => {
                manualContainer.classList.toggle('d-none');
            });
        }

        // Add from manual dropdown
        if (btnAddManual) {
            btnAddManual.addEventListener('click', () => {
                const sku = variantSelectManual.value;
                if (sku) {
                    performLookup(sku);
                }
            });
        }

        // Live Auto-Suggest on input
        let searchDebounce = null;
        scanInput.addEventListener('input', () => {
            const val = scanInput.value.trim();
            clearTimeout(searchDebounce);
            if (val.length < 2) {
                liveResults.classList.add('d-none');
                liveResults.innerHTML = '';
                return;
            }

            searchDebounce = setTimeout(() => {
                fetch(LOOKUP_API_URL + '?code=' + encodeURIComponent(val) + '&shop_id=' + SHOP_ID)
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            liveResults.classList.remove('d-none');
                            liveResults.innerHTML = `
                                <div class="p-2.5 border-bottom d-flex align-items-center justify-content-between hover-bg-light cursor-pointer" onclick="selectLiveResult('${escapeHtml(res.sku)}')">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-box-seam fs-5 text-primary"></i>
                                        <div>
                                            <div class="fw-bold text-dark">${escapeHtml(res.product_name)}</div>
                                            <small class="text-muted font-monospace">${escapeHtml(res.sku)} | Available: ${res.available_qty} pcs</small>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary py-0.5 px-2">Add</button>
                                </div>
                            `;
                        } else {
                            liveResults.classList.add('d-none');
                        }
                    })
                    .catch(() => liveResults.classList.add('d-none'));
            }, 300);
        });

        // Pre-fill if requested from query string
        <?php if (!empty($prefillVariantId)): ?>
            const preSku = variantSelectManual ? variantSelectManual.value : '';
            if (preSku) performLookup(preSku);
        <?php endif; ?>
    });

    function selectLiveResult(sku) {
        document.getElementById('liveSearchResults').classList.add('d-none');
        document.getElementById('scanInput').value = sku;
        performLookup(sku);
    }

    function performLookup(code) {
        if (!code) return;
        const feedback = document.getElementById('scanFeedback');
        const feedbackMsg = document.getElementById('scanFeedbackMsg');
        feedback.classList.remove('d-none', 'alert-danger', 'alert-success');
        feedback.classList.add('alert-info');
        feedbackMsg.textContent = `Looking up code '${code}' in shop catalog...`;

        fetch(LOOKUP_API_URL + '?code=' + encodeURIComponent(code) + '&shop_id=' + SHOP_ID)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    const added = addItemToQueue(res);
                    feedback.classList.remove('alert-info', 'alert-success', 'alert-warning', 'alert-danger');
                    feedback.classList.add(added ? 'alert-success' : 'alert-warning');
                    feedbackMsg.textContent = added
                        ? `Found: ${res.product_name} (${res.sku})`
                        : 'This product is already in the damage queue.';
                    document.getElementById('scanInput').value = '';
                    document.getElementById('liveSearchResults').classList.add('d-none');
                    setTimeout(() => feedback.classList.add('d-none'), 2000);
                } else {
                    feedback.classList.remove('alert-info');
                    feedback.classList.add('alert-danger');
                    feedbackMsg.textContent = res.message || 'Product not found.';
                }
            })
            .catch(err => {
                feedback.classList.remove('alert-info');
                feedback.classList.add('alert-danger');
                feedbackMsg.textContent = 'Network error during lookup: ' + err.message;
            });
    }

    function addItemToQueue(prod) {
        const categoryType = String(prod.category_type || 'NORMAL').toUpperCase();
        const isComplementary = Boolean(parseInt(prod.is_complementary, 10)) || categoryType === 'COMPLEMENTARY';
        const isSeries = !isComplementary && (
            categoryType === 'SERIES'
            || String(prod.qr_type || '').toUpperCase() === 'SERIES_BATCH'
            || (parseInt(prod.pack_quantity, 10) || 1) > 1
        );
        const productType = isComplementary ? 'COMPLEMENTARY' : (isSeries ? 'SERIES' : 'NORMAL');
        const quantityEditable = productType !== 'NORMAL';
        const existingIdx = queueItems.findIndex(x => x.variant_id === prod.variant_id && x.tag_code === prod.tag_code);
        if (existingIdx !== -1) {
            if (!quantityEditable) return false;
            queueItems[existingIdx].quantity = Math.min(
                (parseInt(queueItems[existingIdx].available_qty, 10) || 1),
                queueItems[existingIdx].quantity + 1
            );
            queueItems[existingIdx].total_loss = queueItems[existingIdx].quantity * queueItems[existingIdx].cost_per_unit;
        } else {
            queueItems.push({
                variant_id: prod.variant_id,
                product_name: prod.product_name,
                sku: prod.sku,
                size_name: prod.size_name,
                color_name: prod.color_name,
                tag_code: prod.tag_code || '',
                product_type: productType,
                quantity_editable: quantityEditable,
                defect_type: 'OTHER',
                damage_reason: 'Shop Floor Defect / Damage',
                defect_notes: '',
                quantity: 1,
                cost_per_unit: prod.cost_price || 0.0,
                total_loss: prod.cost_price || 0.0,
                available_qty: prod.available_qty || 0
            });
        }
        renderQueue();
        return true;
    }

    function renderQueue() {
        const tbody = document.getElementById('queueTableBody');
        const badgeCount = document.getElementById('badgeQueueCount');
        const submitBtn = document.getElementById('btnSubmitDamage');
        const hiddenInput = document.getElementById('hiddenItemsJson');
        const totalUnitsEl = document.getElementById('summaryTotalUnits');
        const totalLossEl = document.getElementById('summaryTotalLoss');

        if (queueItems.length === 0) {
            tbody.innerHTML = `
                <tr id="emptyQueueRow">
                    <td colspan="8" class="text-center py-5 text-muted">
                        <div class="py-4">
                            <i class="bi bi-qr-code text-danger opacity-25" style="font-size: 4rem;"></i>
                            <h6 class="fw-semibold text-dark mt-2">No items added to damage queue yet</h6>
                            <p class="small text-muted mb-0">Scan barcode, piece QR code, or type SKU above to queue products for damage reporting.</p>
                        </div>
                    </td>
                </tr>
            `;
            badgeCount.textContent = '0 Items';
            submitBtn.disabled = true;
            hiddenInput.value = '[]';
            totalUnitsEl.textContent = '0 pcs';
            totalLossEl.textContent = '₹0.00';
            return;
        }

        let html = '';
        let sumUnits = 0;
        let sumLoss = 0;

        queueItems.forEach((item, idx) => {
            sumUnits += item.quantity;
            sumLoss += item.total_loss;

            html += `
                <tr>
                    <td class="ps-3 fw-bold text-muted">${idx + 1}</td>
                    <td>
                        <div>
                            <div class="fw-bold text-dark">${escapeHtml(item.product_name)}</div>
                            <div class="small font-monospace text-primary">${escapeHtml(item.sku)}</div>
                            <small class="text-muted">${escapeHtml(item.size_name)} / ${escapeHtml(item.color_name)} · Shop Stock: ${item.available_qty} pcs</small>
                        </div>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm font-monospace" value="${escapeHtml(item.tag_code)}" placeholder="Optional tag..." onchange="updateItemField(${idx}, 'tag_code', this.value)">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" value="${escapeHtml(item.damage_reason)}" placeholder="Reason..." onchange="updateItemField(${idx}, 'damage_reason', this.value)">
                    </td>
                    <td class="text-center">
                        <input type="number" min="1" max="${parseInt(item.available_qty, 10) || 1}" class="form-control form-control-sm text-center fw-bold text-danger ${item.quantity_editable ? '' : 'bg-light'}" value="${item.quantity}" ${item.quantity_editable ? '' : 'readonly inputmode="none" title="Normal products are reported one piece per QR tag"'} onchange="updateItemQty(${idx}, this.value)">
                    </td>
                    <td class="text-end font-monospace">
                        ₹${parseFloat(item.cost_per_unit).toFixed(2)}
                    </td>
                    <td class="text-end font-monospace fw-bold text-danger">
                        ₹${parseFloat(item.total_loss).toFixed(2)}
                    </td>
                    <td class="text-center pe-3">
                        <button type="button" class="btn btn-sm btn-outline-danger p-1 rounded-circle" onclick="removeItem(${idx})" title="Remove item">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        badgeCount.textContent = `${queueItems.length} Product${queueItems.length > 1 ? 's' : ''} (${sumUnits} pcs)`;
        submitBtn.disabled = false;
        hiddenInput.value = JSON.stringify(queueItems);
        totalUnitsEl.textContent = `${sumUnits} pcs`;
        totalLossEl.textContent = `₹${sumLoss.toFixed(2)}`;
    }

    function updateItemField(idx, field, value) {
        if (queueItems[idx]) {
            queueItems[idx][field] = value;
            document.getElementById('hiddenItemsJson').value = JSON.stringify(queueItems);
        }
    }

    function updateItemQty(idx, val) {
        const item = queueItems[idx];
        if (!item || !item.quantity_editable) return;
        const maxQty = Math.max(1, parseInt(item.available_qty, 10) || 1);
        const q = Math.min(maxQty, Math.max(1, parseInt(val, 10) || 1));
        if (queueItems[idx]) {
            queueItems[idx].quantity = q;
            queueItems[idx].total_loss = q * queueItems[idx].cost_per_unit;
            renderQueue();
        }
    }

    function removeItem(idx) {
        queueItems.splice(idx, 1);
        renderQueue();
    }

    function clearQueue() {
        if (queueItems.length > 0 && confirm("Are you sure you want to clear the entire damage queue?")) {
            queueItems = [];
            renderQueue();
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

</script>
