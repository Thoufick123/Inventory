<?php
// views/whatsapp_orders/damage/create.php — Manual Damage Logging for WhatsApp Channel
?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="mb-0 fw-bold text-dark"><i class="bi bi-plus-circle me-2 text-danger"></i>Log Damaged Garment Stock</h2>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill fw-semibold">
                WhatsApp Channel Quarantine
            </span>
        </div>
        <span class="text-muted small">Record handling damages, defective garments, packaging tears, or storage stains in WhatsApp division.</span>
    </div>
    <div>
        <a href="<?= url('warehouse/whatsapp-orders/damage') ?>" class="btn btn-outline-secondary rounded-pill px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Damage List
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4 shadow-sm" role="alert">
        <i class="bi bi-exclamation-octagon-fill me-2"></i><?= e($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<form method="POST" action="<?= url('warehouse/whatsapp-orders/damage/store') ?>" enctype="multipart/form-data" id="logDamageForm" onsubmit="return validateBeforeSubmit();">
    <?= CSRF::field() ?>
    
    <div class="row g-4">
        <!-- Left Column: Product Selection & Defect Metadata -->
        <div class="col-lg-8">
            <!-- 1. Scanner & One-by-One Scanned Items Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-upc-scan me-2 text-primary"></i>1. Garment Product &amp; QR Tag Lookup</h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill font-monospace" id="scannedCountBadge">0 Items Scanned</span>
                </div>
                
                <!-- Quick Scanner & SKU Input -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Scan Barcode / Piece QR / Enter SKU</label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-light border-secondary-subtle"><i class="bi bi-qr-code text-primary fs-5"></i></span>
                        <input type="text" id="scanLookupInput" class="form-control bg-light font-monospace fs-6" placeholder="Scan piece QR tag (e.g. KB102611) or type SKU..." autocomplete="off" autofocus onkeydown="if(event.key==='Enter'){event.preventDefault();lookupScannedProduct();}">
                        <button type="button" class="btn btn-primary px-4 fw-bold" id="btnLookup" onclick="lookupScannedProduct()">
                            <i class="bi bi-plus-circle me-1"></i> Scan &amp; Add
                        </button>
                    </div>
                    <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i>Scan multiple piece QR tags one by one to quarantine them in batch.</small>
                </div>

                <!-- Live Status / Notification Alert Bar -->
                <div id="scanAlertBox" class="alert py-2 px-3 rounded-3 small mb-3" style="display: none;"></div>

                <!-- One-By-One Scanned Garments List Table -->
                <div class="border rounded-4 overflow-hidden shadow-sm bg-light">
                    <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-dark small text-uppercase"><i class="bi bi-list-check me-1 text-danger"></i>Quarantined Products List</span>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill" onclick="clearAllScannedItems()" id="btnClearAll" style="display: none;">
                            <i class="bi bi-trash3 me-1"></i>Clear List
                        </button>
                    </div>

                    <div class="table-responsive mb-0" id="scannedTableContainer" style="display: none; max-height: 380px;">
                        <table class="table table-hover align-middle mb-0 bg-white" id="scannedItemsTable">
                            <thead class="table-light small text-uppercase text-muted sticky-top">
                                <tr>
                                    <th class="ps-3" style="width: 45px;">#</th>
                                    <th>Garment Product</th>
                                    <th>Piece Tag</th>
                                    <th class="text-end">Price (Incl. GST)</th>
                                    <th class="text-end">Excl. GST Loss</th>
                                    <th class="text-center pe-3" style="width: 60px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="scannedItemsTbody">
                                <!-- Appended dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div id="emptyScannedState" class="text-center py-4 text-muted">
                        <i class="bi bi-qr-code-scan fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <span class="small">No garments scanned yet. Scan piece QR serial tags or enter SKUs above to add items to quarantine.</span>
                    </div>
                </div>

                <!-- Hidden inputs container for form submission -->
                <div id="hiddenInputsContainer"></div>
            </div>

            <!-- 2. Defect Classification & Reason Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>2. Defect Details &amp; Reason</h5>
                
                <div>
                    <label class="form-label small fw-bold text-muted text-uppercase">Reason <span class="text-danger">*</span></label>
                    <textarea name="damage_reason" rows="3" class="form-control bg-light" placeholder="Enter the damage reason..." required></textarea>
                </div>
            </div>
        </div>

        <!-- Right Column: Summary Card & Submission -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4 sticky-top" style="top: 20px;">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-slash me-2 text-danger"></i>Quarantine Audit</h5>
                
                <div class="p-3 bg-danger-subtle rounded-4 border border-danger-subtle text-center mb-3">
                    <div class="text-danger text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">TOTAL ESTIMATED LOSS (EXCL. GST)</div>
                    <h2 class="fw-bold text-danger font-monospace mt-1 mb-0" id="totalLossDisplay">₹0.00</h2>
                    <small class="text-muted" style="font-size: 11px;">Base product cost loss without GST</small>
                </div>

                <!-- Financial Breakdown -->
                <div class="bg-light p-3 rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between small text-muted mb-1.5">
                        <span>Total Pieces:</span>
                        <strong class="text-dark font-monospace" id="summaryTotalPieces">0 pcs</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-1.5">
                        <span>Gross Value (Incl. GST):</span>
                        <strong class="text-dark font-monospace" id="summaryGrossValue">₹0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-1.5">
                        <span>Total GST Amount:</span>
                        <span class="text-danger font-monospace" id="summaryGstAmount">-₹0.00</span>
                    </div>
                    <div class="d-flex justify-content-between small border-top pt-1.5 fw-bold text-dark">
                        <span>Net Quarantine Loss:</span>
                        <span class="text-danger font-monospace" id="summaryNetLoss">₹0.00</span>
                    </div>
                </div>

                <div class="border-top pt-3 mb-4">
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span>Channel Destination:</span>
                        <strong class="text-dark">WhatsApp Channel</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span>Initial Quarantine Status:</span>
                        <span class="badge bg-danger text-white rounded-pill">LOGGED</span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Stock State Action:</span>
                        <strong class="text-danger">Transfer to DAMAGED</strong>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-danger btn-lg rounded-pill fw-bold shadow-sm" id="btnSubmitDamage" disabled>
                        <i class="bi bi-check2-circle me-1"></i> Confirm &amp; Log Damaged Stock
                    </button>
                    <a href="<?= url('warehouse/whatsapp-orders/damage') ?>" class="btn btn-light border rounded-pill">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let scannedItems = [];

function previewDamagePhoto(input) {
    const container = document.getElementById('photoPreviewContainer');
    const img = document.getElementById('photoPreviewImg');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            container.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        container.style.display = 'none';
        img.src = '';
    }
}

function showScanAlert(msg, type = 'danger') {
    const box = document.getElementById('scanAlertBox');
    box.className = `alert alert-${type} py-2 px-3 rounded-3 small mb-3`;
    box.innerHTML = msg;
    box.style.display = 'block';
}

function hideScanAlert() {
    const box = document.getElementById('scanAlertBox');
    box.style.display = 'none';
    box.innerHTML = '';
}

function lookupScannedProduct() {
    hideScanAlert();
    const input = document.getElementById('scanLookupInput');
    const query = input.value.trim();
    if (!query) {
        showScanAlert('<i class="bi bi-exclamation-triangle me-1"></i> Please scan or type a piece QR tag / SKU.', 'warning');
        input.focus();
        return;
    }

    const btn = document.getElementById('btnLookup');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Searching...';

    fetch(`<?= url('api/whatsapp/damage/lookup-scan') ?>?query=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = origHtml;

            if (res.success && (res.data || res.variant)) {
                const item = res.data || res.variant;
                const tag = (item.tag_code || res.tag_code || query).trim();

                // Check if this piece tag is already added in the current list
                if (tag) {
                    const alreadyInList = scannedItems.some(it => it.tag_code && it.tag_code.toUpperCase() === tag.toUpperCase());
                    if (alreadyInList) {
                        showScanAlert(`<i class="bi bi-info-circle me-1"></i> Piece tag <strong>${tag}</strong> is already added in this quarantine list.`, 'warning');
                        input.value = '';
                        input.focus();
                        return;
                    }
                }

                // Calculate base price without GST
                const gstRate = parseFloat(item.gst_rate ?? 5.0) || 5.0;
                const priceInclGst = parseFloat(item.price || item.selling_price || 0);
                const priceExGst = item.price_ex_gst ? parseFloat(item.price_ex_gst) : Math.round((priceInclGst / (1 + (gstRate / 100))) * 100) / 100;

                const newItem = {
                    variant_id: parseInt(item.variant_id || item.id),
                    tag_code: tag,
                    allocation_id: item.allocation_id || null,
                    sku: item.sku || '',
                    product_name: item.product_name || '',
                    size_name: item.size_name || 'Std',
                    color_name: item.color_name || 'Std',
                    price: priceInclGst,
                    mrp: parseFloat(item.mrp || priceInclGst),
                    gst_rate: gstRate,
                    price_ex_gst: priceExGst,
                    available_stock: parseInt(item.available_stock || 0),
                    image: item.image || ''
                };

                scannedItems.push(newItem);
                renderScannedTable();
                showScanAlert(`<i class="bi bi-check2-circle me-1"></i> Added <strong>${newItem.product_name}</strong> (${tag || newItem.sku}) to quarantine list.`, 'success');

                input.value = '';
                input.focus();
            } else {
                showScanAlert(`<i class="bi bi-exclamation-octagon me-1"></i> ${res.message || 'Product variant not found in WhatsApp inventory.'}`, 'danger');
                input.select();
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            console.error(err);
            showScanAlert('<i class="bi bi-exclamation-octagon me-1"></i> Error looking up product details.', 'danger');
        });
}

function removeScannedItem(index) {
    if (index >= 0 && index < scannedItems.length) {
        const removed = scannedItems.splice(index, 1);
        renderScannedTable();
        showScanAlert(`<i class="bi bi-info-circle me-1"></i> Removed <strong>${removed[0]?.product_name || 'item'}</strong> from list.`, 'info');
    }
}

function clearAllScannedItems() {
    if (scannedItems.length === 0) return;
    if (confirm('Are you sure you want to clear all scanned products?')) {
        scannedItems = [];
        renderScannedTable();
        hideScanAlert();
    }
}

function renderScannedTable() {
    const tbody = document.getElementById('scannedItemsTbody');
    const container = document.getElementById('scannedTableContainer');
    const emptyState = document.getElementById('emptyScannedState');
    const badge = document.getElementById('scannedCountBadge');
    const btnClear = document.getElementById('btnClearAll');
    const btnSubmit = document.getElementById('btnSubmitDamage');
    const hiddenContainer = document.getElementById('hiddenInputsContainer');

    badge.innerText = `${scannedItems.length} Item${scannedItems.length === 1 ? '' : 's'} Scanned`;

    if (scannedItems.length === 0) {
        tbody.innerHTML = '';
        container.style.display = 'none';
        emptyState.style.display = 'block';
        btnClear.style.display = 'none';
        btnSubmit.disabled = true;
        hiddenContainer.innerHTML = '';
        updateAuditTotals(0, 0, 0, 0);
        return;
    }

    container.style.display = 'block';
    emptyState.style.display = 'none';
    btnClear.style.display = 'inline-block';
    btnSubmit.disabled = false;

    let grossTotal = 0;
    let netLossTotal = 0;
    let totalGstAmount = 0;

    let rowsHtml = '';
    let hiddenHtml = '';

    scannedItems.forEach((it, idx) => {
        const lineGross = it.price;
        const lineNet = it.price_ex_gst;
        const lineGst = lineGross - lineNet;

        grossTotal += lineGross;
        netLossTotal += lineNet;
        totalGstAmount += lineGst;

        const imgHtml = it.image 
            ? `<img src="${it.image}" class="rounded-2 border flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover;">`
            : `<div class="rounded-2 bg-light border d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;"><i class="bi bi-box-seam text-secondary"></i></div>`;

        rowsHtml += `
            <tr>
                <td class="ps-3 font-monospace text-muted small">${idx + 1}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        ${imgHtml}
                        <div>
                            <div class="fw-bold text-dark text-truncate" style="max-width: 260px;">${it.product_name}</div>
                            <div class="small text-muted font-monospace">
                                <span class="badge bg-light text-dark border me-1">${it.sku}</span>
                                <span>${it.size_name} / ${it.color_name}</span>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    ${it.tag_code ? `<span class="badge bg-dark-subtle text-dark border font-monospace"><i class="bi bi-qr-code me-1 text-primary"></i>${it.tag_code}</span>` : '<span class="text-muted small">—</span>'}
                </td>
                <td class="text-end font-monospace">
                    ₹${it.price.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td class="text-end font-monospace fw-bold text-danger">
                    ₹${it.price_ex_gst.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    <div class="text-muted font-monospace" style="font-size: 10px;">(GST ${it.gst_rate}%)</div>
                </td>
                <td class="text-center pe-3">
                    <button type="button" class="btn btn-sm btn-outline-danger p-1 rounded-circle lh-1" title="Remove Item" onclick="removeScannedItem(${idx})">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            </tr>
        `;

        hiddenHtml += `
            <input type="hidden" name="items[${idx}][variant_id]" value="${it.variant_id}">
            <input type="hidden" name="items[${idx}][tag_code]" value="${it.tag_code}">
            <input type="hidden" name="items[${idx}][allocation_id]" value="${it.allocation_id || ''}">
            <input type="hidden" name="items[${idx}][cost_per_unit]" value="${it.price_ex_gst.toFixed(2)}">
            <input type="hidden" name="items[${idx}][price]" value="${it.price.toFixed(2)}">
            <input type="hidden" name="items[${idx}][quantity]" value="1">
        `;
    });

    tbody.innerHTML = rowsHtml;
    hiddenContainer.innerHTML = hiddenHtml;
    updateAuditTotals(scannedItems.length, grossTotal, totalGstAmount, netLossTotal);
}

function updateAuditTotals(totalPcs, gross, gst, netLoss) {
    document.getElementById('totalLossDisplay').innerText = '₹' + netLoss.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('summaryTotalPieces').innerText = `${totalPcs} pcs`;
    document.getElementById('summaryGrossValue').innerText = '₹' + gross.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('summaryGstAmount').innerText = '-₹' + gst.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('summaryNetLoss').innerText = '₹' + netLoss.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function validateBeforeSubmit() {
    if (scannedItems.length === 0) {
        showScanAlert('<i class="bi bi-exclamation-octagon me-1"></i> Please scan at least one product before submitting.', 'danger');
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    // If prefill data was passed in URL
    <?php if (!empty($prefillVariantId)): ?>
        fetch(`<?= url('api/whatsapp/damage/lookup-scan') ?>?query=<?= urlencode($prefillTagCode ?: $prefillVariantId) ?>`)
            .then(r => r.json())
            .then(res => {
                if (res.success && (res.data || res.variant)) {
                    const item = res.data || res.variant;
                    const gstRate = parseFloat(item.gst_rate ?? 5.0) || 5.0;
                    const priceInclGst = parseFloat(item.price || item.selling_price || 0);
                    const priceExGst = item.price_ex_gst ? parseFloat(item.price_ex_gst) : Math.round((priceInclGst / (1 + (gstRate / 100))) * 100) / 100;

                    scannedItems.push({
                        variant_id: parseInt(item.variant_id || item.id),
                        tag_code: '<?= e($prefillTagCode ?? '') ?>' || item.tag_code || '',
                        allocation_id: <?= (int)($prefillAllocId ?? 0) ?> || item.allocation_id || null,
                        sku: item.sku || '',
                        product_name: item.product_name || '',
                        size_name: item.size_name || 'Std',
                        color_name: item.color_name || 'Std',
                        price: priceInclGst,
                        mrp: parseFloat(item.mrp || priceInclGst),
                        gst_rate: gstRate,
                        price_ex_gst: priceExGst,
                        available_stock: parseInt(item.available_stock || 0),
                        image: item.image || ''
                    });
                    renderScannedTable();
                }
            });
    <?php endif; ?>
});
</script>
