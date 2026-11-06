<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-bag-plus me-2 text-primary"></i>Record Production Purchase</h2>
        <span class="text-muted">Record supplier invoices, fabric rolls, and other garment supplies.</span>
    </div>
    <a href="<?= url('production/purchases') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Purchases</a>
</div>

<form method="POST" action="<?= url('production/purchases/store') ?>" enctype="multipart/form-data" id="productionPurchaseForm">
    <?= CSRF::field() ?>
    <div class="card shadow-sm border mb-4">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Purchase &amp; Supplier Details</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label required" for="purchaseSeller">Supplier *</label>
                    <select class="form-select" name="seller_id" id="purchaseSeller" required>
                        <option value="">-- Select registered supplier --</option>
                        <?php foreach ($sellers as $seller): ?>
                            <option value="<?= (int)$seller['id'] ?>"
                                data-supplier-name="<?= e($seller['name'] ?? '') ?>"
                                data-supplier-company="<?= e($seller['company'] ?? '') ?>"
                                data-supplier-phone="<?= e($seller['phone'] ?? '') ?>"
                                data-supplier-email="<?= e($seller['email'] ?? '') ?>"
                                data-supplier-gst="<?= e($seller['gst_number'] ?? '') ?>"
                                data-supplier-city="<?= e($seller['city'] ?? '') ?>"
                                data-supplier-state="<?= e($seller['state'] ?? '') ?>"
                                data-supplier-address="<?= e($seller['address'] ?? '') ?>"
                                data-supplier-payment-terms="<?= e($seller['payment_terms'] ?? '') ?>"><?= e($seller['company'] ?: $seller['name']) ?><?= $seller['company'] ? ' — ' . e($seller['name']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($sellers)): ?><small class="text-danger">Add a supplier profile before recording a purchase.</small><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label required" for="purchaseDate">Purchase Date *</label>
                    <input type="date" class="form-control" name="purchase_date" id="purchaseDate" required value="<?= e(date('Y-m-d')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="invoiceNumber">Invoice / Bill Number</label>
                    <input type="text" class="form-control" name="invoice_number" id="invoiceNumber" maxlength="100">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="purchaseDocument">Tax Invoice / Bill Attachment (Optional)</label>
                    <input type="file" class="form-control" name="document" id="purchaseDocument" accept=".jpg,.jpeg,.png,.webp,.pdf">
                    <small class="text-muted">JPG, PNG, WEBP or PDF up to 5 MB.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="purchaseNotes">Purchase Notes</label>
                    <input type="text" class="form-control" name="notes" id="purchaseNotes" maxlength="500">
                </div>
                <div class="col-12 d-none" id="selectedSupplierDetails" aria-live="polite">
                    <div class="border rounded bg-light p-3">
                        <div class="fw-semibold mb-3"><i class="bi bi-person-vcard me-1 text-primary"></i>Selected Supplier Information</div>
                        <div class="row g-3">
                            <div class="col-md-4"><small class="text-muted d-block">Contact Person</small><span data-supplier-detail="name">—</span></div>
                            <div class="col-md-4"><small class="text-muted d-block">Company</small><span data-supplier-detail="company">—</span></div>
                            <div class="col-md-4"><small class="text-muted d-block">Phone</small><span data-supplier-detail="phone">—</span></div>
                            <div class="col-md-4"><small class="text-muted d-block">Email</small><span data-supplier-detail="email">—</span></div>
                            <div class="col-md-4"><small class="text-muted d-block">GSTIN / Tax ID</small><span data-supplier-detail="gst">—</span></div>
                            <div class="col-md-4"><small class="text-muted d-block">City / State</small><span data-supplier-detail="location">—</span></div>
                            <div class="col-md-6"><small class="text-muted d-block">Business Address</small><span data-supplier-detail="address">—</span></div>
                            <div class="col-md-6"><small class="text-muted d-block">Payment Terms</small><span data-supplier-detail="payment-terms">—</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border mb-4">
        <div class="card-header bg-white py-3">
            <div class="row g-3 align-items-center">
                <div class="col">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-layers-half me-2 text-primary"></i>Fabric Roll &amp; Material Supply Details</h5>
                    <small class="text-muted">Specify the fabric grade and add multiple rolls for this purchase.</small>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-primary" id="addFabricRoll"><i class="bi bi-plus-circle me-1"></i>Add Fabric Roll</button>
                </div>
                <div class="col-12">
                    <label class="form-label required" for="fabricDescription">Fabric / Material Description *</label>
                    <input type="text" name="fabric_description" id="fabricDescription" class="form-control text-uppercase" maxlength="150" placeholder="E.G. 100% COMBED COTTON 30S SINGLE JERSEY (160 GSM)" list="materialTypeSuggestions">
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
                    <small class="text-muted">Select a standard grade or enter a custom fabric name and GSM.</small>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-stack me-1 text-primary"></i>Individual Fabric Rolls (<span id="rollCountBadge">0</span>)</h6>
                <span class="text-muted small">Add each roll received on this supplier invoice.</span>
            </div>
            <div id="rollsContainer" class="d-flex flex-column gap-3 mb-4"></div>
            <div class="card border shadow-sm mb-4" id="rollDetailsTableContainer">
                <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold small"><i class="bi bi-table me-1 text-primary"></i>Fabric Rolls Inventory &amp; Details Table</span>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-dark" id="previewLabels"><i class="bi bi-qr-code text-warning me-1"></i>Preview Labels</button>
                        <span class="badge bg-secondary-subtle text-secondary border" id="tableRollCountBadge">0 Rolls</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0" style="font-size:13px">
                        <thead class="table-light small text-uppercase">
                            <tr><th class="text-center">#</th><th>Photo</th><th>Roll Code</th><th>Color / Shade</th><th>Design / Pattern</th><th class="text-center">Designs</th><th class="text-end">Total RQ</th><th class="text-end">Weight</th><th class="text-end">Price</th><th class="text-end">SGST</th><th class="text-end">CGST</th><th class="text-end bg-success-subtle text-success">Total Value</th><th class="text-center">Status</th></tr>
                        </thead>
                        <tbody id="rollDetailsTableBody"><tr><td colspan="13" class="text-center text-muted py-4">Add fabric rolls to see their inventory details.</td></tr></tbody>
                        <tfoot id="rollDetailsTableFoot" class="table-light fw-bold"></tfoot>
                    </table>
                </div>
            </div>
            <div class="p-3 bg-primary-subtle rounded border d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div><strong class="text-primary"><i class="bi bi-calculator me-1"></i>Batch Metrics Summary</strong><small class="d-block text-muted">Totals are calculated from the rolls in this purchase.</small></div>
                <div class="d-flex flex-wrap gap-4">
                    <div><small class="text-muted d-block">Total Rolls</small><strong id="summaryTotalRolls">0 Rolls</strong></div>
                    <div><small class="text-muted d-block">Total RQ</small><strong id="summaryTotalRQ">0 RQ</strong></div>
                    <div><small class="text-muted d-block">Colors / Designs</small><strong id="summaryColorsDesigns">0 Colors • 0 Designs</strong></div>
                    <div><small class="text-muted d-block">Total Weight</small><strong id="summaryTotalWeight">0.00 kg</strong></div>
                    <div><small class="text-muted d-block">Total incl. Tax</small><strong id="summaryTotalPrice" class="text-success">₹0.00</strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-success"></i>Other Garment Supplies</h5><small class="text-muted">Optional non-fabric products received on this invoice.</small></div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addSupply"><i class="bi bi-plus-circle me-1"></i>Add Supply</button>
        </div>
        <div class="card-body" id="suppliesContainer">
            <div id="emptySupplyMessage" class="small text-muted">No general supplies added.</div>
        </div>
    </div>

    <div class="card border shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <strong>Purchase Total</strong><strong class="fs-5 text-success" id="purchaseTotal">₹0.00</strong>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= url('production/purchases') ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Purchase</button>
    </div>
</form>

<div class="modal fade" id="purchaseLabelsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-qr-code me-2 text-warning"></i>Fabric Roll Labels Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light"><div id="purchaseLabelCards" class="d-flex flex-wrap justify-content-center gap-3"></div></div>
            <div class="modal-footer"><span class="small text-muted me-auto" id="purchaseLabelCount"></span><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>
<style>
.purchase-label-card{width:250px;min-height:130px;border:1.5px solid #0f172a;border-radius:6px;background:#fff;padding:8px;display:flex;gap:8px;align-items:center}
.purchase-label-qr{width:76px;height:76px;flex-shrink:0}
.purchase-label-qr img,.purchase-label-qr canvas{width:100%!important;height:100%!important}
.purchase-label-code{font-family:monospace;font-size:10px;font-weight:800;word-break:break-all}
.purchase-label-info{font-size:9px;overflow-wrap:anywhere}
</style>

<template id="fabricRollTemplate">
    <div class="roll-card card border shadow-sm" data-index="">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
            <strong><i class="bi bi-layers text-primary me-1"></i><span class="roll-title">ROLL #1</span> <span class="badge bg-dark roll-code-text"></span></strong>
            <button type="button" class="btn btn-sm btn-outline-danger remove-roll"><i class="bi bi-trash me-1"></i>Delete</button>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-lg-2 col-md-4"><label class="form-label required">Roll No. / Code *</label><input class="form-control roll-number text-uppercase" maxlength="50" required></div>
                <div class="col-lg-2 col-md-4"><label class="form-label required">Color / Shade *</label><input class="form-control roll-color text-uppercase" maxlength="100" required placeholder="E.G. NAVY BLUE"></div>
                <div class="col-lg-2 col-md-4"><label class="form-label required">Design Name / Pattern *</label><input class="form-control roll-design text-uppercase" maxlength="150" required placeholder="E.G. FLORAL 01"></div>
                <div class="col-lg-1 col-md-3"><label class="form-label required">Designs *</label><input type="number" class="form-control roll-design-count" min="1" step="1" value="1" required></div>
                <div class="col-lg-1 col-md-3"><label class="form-label required">RQ *</label><input type="number" class="form-control roll-quantity" min="1" step="1" value="1" required></div>
                <div class="col-lg-2 col-md-3"><label class="form-label required">Weight (kg) *</label><div class="input-group"><input type="number" class="form-control roll-weight" min="0.01" step="0.01" required><span class="input-group-text">kg</span></div></div>
                <div class="col-lg-2 col-md-3"><label class="form-label required">Price (₹) *</label><div class="input-group"><span class="input-group-text">₹</span><input type="number" class="form-control roll-price" min="0.01" step="0.01" required></div></div>
                <div class="col-lg-3 col-md-4"><label class="form-label">Tax Input Type</label><select class="form-select item-tax-mode"><option value="PERCENT">Percentage (%)</option><option value="AMOUNT">Amount (₹)</option></select></div>
                <div class="col-lg-3 col-md-4"><label class="form-label">SGST <span class="tax-unit">(%)</span></label><input type="number" class="form-control item-sgst" min="0" max="100" step="0.01" value="0"></div>
                <div class="col-lg-3 col-md-4"><label class="form-label">CGST <span class="tax-unit">(%)</span></label><input type="number" class="form-control item-cgst" min="0" max="100" step="0.01" value="0"></div>
                <div class="col-md-6"><label class="form-label">Roll Sample Photo <small class="text-muted">(Optional — JPG, PNG, WEBP)</small></label><input type="file" class="form-control roll-photo" accept=".jpg,.jpeg,.png,.webp"></div>
                <div class="col-md-6 roll-preview d-none"><img class="img-thumbnail" style="height:54px" alt="Roll photo preview"></div>
            </div>
        </div>
    </div>
</template>

<template id="supplyTemplate">
    <div class="row g-2 align-items-end supply-row mb-3">
        <div class="col-md-5"><label class="form-label">Item Name / Description *</label><input class="form-control supply-name" maxlength="255" placeholder="Sewing needles, thread, buttons" required></div>
        <div class="col-md-2"><label class="form-label">Quantity *</label><input type="number" class="form-control supply-quantity" min="0.001" step="0.001" value="1" required></div>
        <div class="col-md-2"><label class="form-label">Unit *</label><select class="form-select supply-unit"><option value="PCS">Pieces</option><option value="KG">Kilograms</option><option value="METRE">Metres</option><option value="LITRE">Litres</option><option value="BOX">Boxes</option><option value="PACK">Packs</option><option value="PAIR">Pairs</option><option value="SET">Sets</option><option value="BUNDLE">Bundles</option><option value="OTHER">Other</option></select></div>
        <div class="col-md-2"><label class="form-label">Unit Price (₹) *</label><input type="number" class="form-control supply-price" min="0" step="0.01" value="0" required></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-supply" title="Remove supply"><i class="bi bi-trash"></i></button></div>
        <div class="col-md-3 custom-unit d-none"><label class="form-label">Unit Name *</label><input class="form-control custom-unit-name" maxlength="30"></div>
        <div class="col-md-3"><label class="form-label">Tax Input Type</label><select class="form-select item-tax-mode"><option value="PERCENT">Percentage (%)</option><option value="AMOUNT">Amount (₹)</option></select></div>
        <div class="col-md-3"><label class="form-label">SGST <span class="tax-unit">(%)</span></label><input type="number" class="form-control item-sgst" min="0" max="100" step="0.01" value="0"></div>
        <div class="col-md-3"><label class="form-label">CGST <span class="tax-unit">(%)</span></label><input type="number" class="form-control item-cgst" min="0" max="100" step="0.01" value="0"></div>
    </div>
</template>

<script src="<?= asset('js/qrcode.min.js') ?>"></script>
<script>
(() => {
    const form = document.getElementById('productionPurchaseForm');
    const rollsContainer = document.getElementById('rollsContainer');
    const suppliesContainer = document.getElementById('suppliesContainer');
    const rollTemplate = document.getElementById('fabricRollTemplate');
    const supplyTemplate = document.getElementById('supplyTemplate');
    const supplierSelect = document.getElementById('purchaseSeller');
    const supplierDetails = document.getElementById('selectedSupplierDetails');
    let rollIndex = 0;
    let supplyIndex = 0;
    let nextSequence = 1;
    let sequencePrefix = 'FB<?= date('my') ?>';
    const money = value => '₹' + (Number(value) || 0).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    const safe = value => String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const calculateTaxes = (base, mode, sgstValue, cgstValue) => {
        const sgst = Number(sgstValue) || 0;
        const cgst = Number(cgstValue) || 0;
        if (mode === 'AMOUNT') return {sgst, cgst};
        return {sgst: Math.round(base * sgst) / 100, cgst: Math.round(base * cgst) / 100};
    };

    function updateTaxInputLabels(item) {
        const isPercentage = item.querySelector('.item-tax-mode').value === 'PERCENT';
        item.querySelectorAll('.tax-unit').forEach(label => { label.textContent = isPercentage ? '(%)' : '(₹)'; });
        item.querySelectorAll('.item-sgst, .item-cgst').forEach(input => {
            if (isPercentage) input.max = '100';
            else input.removeAttribute('max');
        });
    }

    function readItemTaxes(item, base) {
        return calculateTaxes(
            base,
            item.querySelector('.item-tax-mode').value,
            item.querySelector('.item-sgst').value,
            item.querySelector('.item-cgst').value
        );
    }

    function updateSupplierDetails() {
        const selected = supplierSelect.options[supplierSelect.selectedIndex];
        if (!selected || !selected.value) {
            supplierDetails.classList.add('d-none');
            return;
        }

        const fields = {
            name: selected.dataset.supplierName,
            company: selected.dataset.supplierCompany,
            phone: selected.dataset.supplierPhone,
            email: selected.dataset.supplierEmail,
            gst: selected.dataset.supplierGst,
            location: [selected.dataset.supplierCity, selected.dataset.supplierState].filter(Boolean).join(', '),
            address: selected.dataset.supplierAddress,
            'payment-terms': selected.dataset.supplierPaymentTerms
        };
        Object.entries(fields).forEach(([field, value]) => {
            supplierDetails.querySelector(`[data-supplier-detail="${field}"]`).textContent = value || '—';
        });
        supplierDetails.classList.remove('d-none');
    }

    function updateSummary() {
        const cards = Array.from(rollsContainer.querySelectorAll('.roll-card'));
        document.getElementById('fabricDescription').required = cards.length > 0;
        const totalWeight = cards.reduce((sum, card) => sum + (Number(card.querySelector('.roll-weight').value) || 0), 0);
        const totalPrice = cards.reduce((sum, card) => {
            const price = Number(card.querySelector('.roll-price').value) || 0;
            const taxes = readItemTaxes(card, price);
            return sum + price + taxes.sgst + taxes.cgst;
        }, 0);
        const totalRq = cards.reduce((sum, card) => sum + (Number(card.querySelector('.roll-quantity').value) || 0), 0);
        const colors = new Set(cards.map(card => card.querySelector('.roll-color').value.trim().toUpperCase()).filter(Boolean));
        const designs = new Set(cards.map(card => card.querySelector('.roll-design').value.trim().toUpperCase()).filter(Boolean));
        document.getElementById('rollCountBadge').textContent = cards.length;
        document.getElementById('tableRollCountBadge').textContent = cards.length + (cards.length === 1 ? ' Roll' : ' Rolls');
        document.getElementById('summaryTotalRolls').textContent = cards.length + (cards.length === 1 ? ' Roll' : ' Rolls');
        document.getElementById('summaryTotalRQ').textContent = totalRq.toLocaleString('en-IN') + ' RQ';
        document.getElementById('summaryColorsDesigns').textContent = colors.size + ' Colors • ' + designs.size + ' Designs';
        document.getElementById('summaryTotalWeight').textContent = totalWeight.toFixed(2) + ' kg';
        document.getElementById('summaryTotalPrice').textContent = money(totalPrice);

        const body = document.getElementById('rollDetailsTableBody');
        if (!cards.length) {
            body.innerHTML = '<tr><td colspan="13" class="text-center text-muted py-4">Add fabric rolls to see their inventory details.</td></tr>';
        } else {
            body.innerHTML = cards.map((card, index) => {
                const qty = Number(card.querySelector('.roll-quantity').value) || 0;
                const weight = Number(card.querySelector('.roll-weight').value) || 0;
                const price = Number(card.querySelector('.roll-price').value) || 0;
                const taxes = readItemTaxes(card, price);
                const totalValue = price + taxes.sgst + taxes.cgst;
                const start = Number(card.dataset.startSeq) || 0;
                const end = start + Math.max(qty, 1) - 1;
                const codeStart = start ? sequencePrefix + String(start).padStart(2, '0') : '—';
                const codeEnd = start ? sequencePrefix + String(end).padStart(2, '0') : '—';
                const photo = card.querySelector('.roll-photo').files[0];
                const previewImg = card.querySelector('.roll-preview img');
                const photoHtml = photo && previewImg.src ? `<img src="${previewImg.src}" alt="Roll" style="width:38px;height:38px;object-fit:cover">` : '<span class="text-muted"><i class="bi bi-camera"></i></span>';
                return `<tr><td class="text-center">${index + 1}</td><td>${photoHtml}</td><td><strong>${safe(card.querySelector('.roll-number').value)}</strong><small class="d-block font-monospace">${safe(codeStart)}${codeStart !== codeEnd ? '–' + safe(codeEnd) : ''}</small></td><td>${safe(card.querySelector('.roll-color').value || '—')}</td><td>${safe(card.querySelector('.roll-design').value || '—')}</td><td class="text-center">${Number(card.querySelector('.roll-design-count').value) || 0}</td><td class="text-end">${qty.toLocaleString('en-IN')} RQ</td><td class="text-end">${weight.toFixed(2)} kg</td><td class="text-end">${money(price)}</td><td class="text-end">${money(taxes.sgst)}</td><td class="text-end">${money(taxes.cgst)}</td><td class="text-end fw-bold text-success">${money(totalValue)}</td><td class="text-center"><span class="badge bg-success-subtle text-success">New Roll (${qty} RQ)</span></td></tr>`;
            }).join('');
        }
        updateTotal();
    }

    function updateTotal() {
        const rolls = Array.from(rollsContainer.querySelectorAll('.roll-card')).reduce((sum, card) => {
            const base = Number(card.querySelector('.roll-price').value) || 0;
            const taxes = readItemTaxes(card, base);
            return sum + base + taxes.sgst + taxes.cgst;
        }, 0);
        const supplies = Array.from(suppliesContainer.querySelectorAll('.supply-row')).reduce((sum, row) => {
            const base = (Number(row.querySelector('.supply-quantity').value) || 0) * (Number(row.querySelector('.supply-price').value) || 0);
            const taxes = readItemTaxes(row, base);
            return sum + base + taxes.sgst + taxes.cgst;
        }, 0);
        document.getElementById('purchaseTotal').textContent = money(rolls + supplies);
    }

    function updateCodes() {
        let sequence = nextSequence;
        rollsContainer.querySelectorAll('.roll-card').forEach(card => {
            card.dataset.startSeq = sequence;
            const quantity = Math.max(1, Number(card.querySelector('.roll-quantity').value) || 1);
            const first = sequencePrefix + String(sequence).padStart(2, '0');
            const last = sequencePrefix + String(sequence + quantity - 1).padStart(2, '0');
            card.querySelector('.roll-code-text').textContent = first === last ? first : first + '–' + last;
            sequence += quantity;
        });
        updateSummary();
    }

    function addRoll() {
        const index = rollIndex++;
        const card = rollTemplate.content.firstElementChild.cloneNode(true);
        card.dataset.index = index;
        const rollCode = 'ROLL #' + (rollsContainer.children.length + 1);
        card.querySelector('.roll-title').textContent = rollCode;
        card.querySelector('.roll-number').value = rollCode;
        const names = {
            '.roll-number': 'roll_number', '.roll-color': 'color', '.roll-design': 'design_name',
            '.roll-design-count': 'design_count', '.roll-quantity': 'quantity', '.roll-weight': 'weight_kg',
            '.roll-price': 'unit_price', '.item-tax-mode': 'tax_mode', '.item-sgst': 'sgst_value',
            '.item-cgst': 'cgst_value', '.roll-photo': null
        };
        Object.entries(names).forEach(([selector, field]) => {
            const input = card.querySelector(selector);
            if (field) input.name = `items[${index}][${field}]`;
            else input.name = `roll_photo_${index}`;
        });
        const type = document.createElement('input');
        type.type = 'hidden';
        type.name = `items[${index}][item_type]`;
        type.value = 'FABRIC_ROLL';
        card.appendChild(type);
        card.querySelector('.remove-roll').addEventListener('click', () => {
            card.remove();
            Array.from(rollsContainer.children).forEach((item, i) => item.querySelector('.roll-title').textContent = 'ROLL #' + (i + 1));
            updateCodes();
        });
        card.querySelectorAll('input').forEach(input => input.addEventListener('input', updateCodes));
        card.querySelector('.item-tax-mode').addEventListener('change', () => {
            updateTaxInputLabels(card);
            updateCodes();
        });
        card.querySelector('.roll-photo').addEventListener('change', event => {
            const file = event.target.files[0];
            const preview = card.querySelector('.roll-preview');
            if (!file) { preview.classList.add('d-none'); updateSummary(); return; }
            if (file.size > 5 * 1024 * 1024) {
                alert('Roll photo must not exceed 5 MB.');
                event.target.value = '';
                preview.classList.add('d-none');
                updateSummary();
                return;
            }
            const reader = new FileReader();
            reader.onload = () => { card.querySelector('.roll-preview img').src = reader.result; preview.classList.remove('d-none'); updateSummary(); };
            reader.readAsDataURL(file);
        });
        rollsContainer.appendChild(card);
        updateCodes();
    }

    function addSupply() {
        const index = supplyIndex++;
        const row = supplyTemplate.content.firstElementChild.cloneNode(true);
        row.querySelector('.supply-name').name = `items[${index + 1000}][item_name]`;
        row.querySelector('.supply-quantity').name = `items[${index + 1000}][quantity]`;
        row.querySelector('.supply-unit').name = `items[${index + 1000}][unit]`;
        row.querySelector('.supply-price').name = `items[${index + 1000}][unit_price]`;
        row.querySelector('.item-tax-mode').name = `items[${index + 1000}][tax_mode]`;
        row.querySelector('.item-sgst').name = `items[${index + 1000}][sgst_value]`;
        row.querySelector('.item-cgst').name = `items[${index + 1000}][cgst_value]`;
        const type = document.createElement('input');
        type.type = 'hidden'; type.name = `items[${index + 1000}][item_type]`; type.value = 'GENERAL_SUPPLY'; row.appendChild(type);
        row.querySelector('.supply-unit').addEventListener('change', event => {
            const custom = event.target.value === 'OTHER';
            const unitContainer = row.querySelector('.custom-unit');
            unitContainer.classList.toggle('d-none', !custom);
            const input = row.querySelector('.custom-unit-name');
            input.required = custom;
            if (custom) input.name = `items[${index + 1000}][custom_unit]`;
            else input.removeAttribute('name');
            updateTotal();
        });
        row.querySelectorAll('input').forEach(input => input.addEventListener('input', updateTotal));
        row.querySelector('.item-tax-mode').addEventListener('change', () => {
            updateTaxInputLabels(row);
            updateTotal();
        });
        updateTaxInputLabels(row);
        row.querySelector('.remove-supply').addEventListener('click', () => { row.remove(); if (!suppliesContainer.querySelector('.supply-row')) document.getElementById('emptySupplyMessage').classList.remove('d-none'); updateTotal(); });
        document.getElementById('emptySupplyMessage').classList.add('d-none');
        suppliesContainer.appendChild(row);
        updateTotal();
    }

    function previewLabels() {
        const supplier = document.getElementById('purchaseSeller');
        if (!supplier.value) { alert('Select a supplier before previewing labels.'); supplier.focus(); return; }
        const material = document.getElementById('fabricDescription').value.trim().toUpperCase();
        if (!material) { document.getElementById('fabricDescription').focus(); return; }
        const rolls = Array.from(rollsContainer.querySelectorAll('.roll-card'));
        if (!rolls.length) { alert('Add a fabric roll before previewing labels.'); return; }
        const labelCards = document.getElementById('purchaseLabelCards');
        labelCards.innerHTML = '';
        let labelCount = 0;
        rolls.forEach((card, index) => {
            const qty = Number(card.querySelector('.roll-quantity').value) || 1;
            const start = Number(card.dataset.startSeq) || nextSequence;
            const roll = card.querySelector('.roll-number').value || 'ROLL #' + (index + 1);
            const supplierName = supplier.options[supplier.selectedIndex].text;
            const weight = Number(card.querySelector('.roll-weight').value) || 0;
            for (let piece = 0; piece < qty; piece++) {
                const code = sequencePrefix + String(start + piece).padStart(2, '0');
                const sticker = document.createElement('div');
                sticker.className = 'purchase-label-card';
                sticker.innerHTML = `<div class="purchase-label-qr"></div><div class="purchase-label-info"><div class="fw-bold">${safe(supplierName)}</div><div class="purchase-label-code">${safe(code)}</div><div>${safe(roll)}</div><div>${safe(material)}</div><div>${safe(card.querySelector('.roll-color').value)} · ${safe(card.querySelector('.roll-design').value)}</div><div>${weight.toFixed(2)} kg · Piece ${piece + 1} of ${qty}</div></div>`;
                labelCards.appendChild(sticker);
                const qrTarget = sticker.querySelector('.purchase-label-qr');
                if (window.QRCode) {
                    new QRCode(qrTarget, {text: `${code}|${supplierName}|${roll}|${material}`, width:76, height:76, correctLevel:QRCode.CorrectLevel.M});
                } else {
                    qrTarget.textContent = code;
                    qrTarget.classList.add('purchase-label-code');
                }
                labelCount++;
            }
        });
        document.getElementById('purchaseLabelCount').textContent = labelCount + (labelCount === 1 ? ' label' : ' labels');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('purchaseLabelsModal')).show();
    }

    document.getElementById('addFabricRoll').addEventListener('click', addRoll);
    document.getElementById('addSupply').addEventListener('click', addSupply);
    document.getElementById('previewLabels').addEventListener('click', previewLabels);
    supplierSelect.addEventListener('change', updateSupplierDetails);
    updateSupplierDetails();
    fetch('<?= url("api/sellers/next-roll-codes") ?>')
        .then(response => { if (!response.ok) throw new Error('Unable to fetch roll code sequence.'); return response.json(); })
        .then(response => {
            if (!response.success || !response.data || !response.data.prefix || !Number.isInteger(Number(response.data.start_seq))) throw new Error('Invalid roll code sequence response.');
            sequencePrefix = response.data.prefix;
            nextSequence = Number(response.data.start_seq);
            updateCodes();
        })
        .catch(error => { console.error(error); alert('Could not load the next fabric roll code. Refresh the page before recording the purchase.'); });

    form.addEventListener('submit', event => {
        const hasRoll = rollsContainer.querySelector('.roll-card');
        const hasSupply = suppliesContainer.querySelector('.supply-row');
        if (!hasRoll && !hasSupply) {
            event.preventDefault();
            alert('Add at least one fabric roll or general supply.');
            return;
        }
        if (hasRoll && !document.getElementById('fabricDescription').value.trim()) {
            event.preventDefault();
            document.getElementById('fabricDescription').focus();
        }
    });
    addRoll();
})();
</script>
