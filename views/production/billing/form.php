<?php
$isEdit = !empty($bill);
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$existingItems = array_map(static function (array $item): array {
    return [
        'qc_item_id' => (int)$item['qc_item_id'],
        'product_name' => (string)$item['product_name'],
        'sku' => (string)($item['sku'] ?? ''),
        'size_name' => (string)($item['size_name'] ?? ''),
        'color_name' => (string)($item['color_name'] ?? ''),
        'mrp' => (float)$item['mrp'],
        'unit_price' => (float)$item['unit_price'],
        'gst_percentage' => (float)$item['gst_percentage'],
        'gst_type' => (string)($item['gst_type'] ?? ((float)$item['gst_percentage'] > 0 ? 'BOTH' : 'EXEMPTED')),
        'tax_input_type' => (string)($item['tax_input_type'] ?? 'PERCENT'),
        'sgst_rate' => (float)($item['sgst_rate'] ?? ((float)$item['gst_percentage'] / 2)),
        'cgst_rate' => (float)($item['cgst_rate'] ?? ((float)$item['gst_percentage'] / 2)),
        'igst_rate' => (float)($item['igst_rate'] ?? 0),
        'tags' => $item['tags'],
    ];
}, $billItems ?? []);
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><?= $isEdit ? 'Edit Production Bill' : 'Create Production Bill' ?></h4>
        <div class="text-muted small">Scan QR labels from available QC-passed Production Inventory.</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= url($isEdit ? 'production/billing/view/' . (int)$bill['id'] : 'production/billing') ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<form method="POST" action="<?= url($isEdit ? 'production/billing/update/' . (int)$bill['id'] : 'production/billing/store') ?>" id="productionBillForm">
    <?= CSRF::input() ?>
    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label fw-semibold" for="buyerType">Bill To</label>
                    <select class="form-select" name="buyer_type" id="buyerType">
                        <option value="COMPANY" <?= ($bill['buyer_type'] ?? 'COMPANY') === 'COMPANY' ? 'selected' : '' ?>>Company</option>
                        <option value="CUSTOMER" <?= ($bill['buyer_type'] ?? '') === 'CUSTOMER' ? 'selected' : '' ?>>Customer</option>
                    </select>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3" id="companyFields">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-building me-2 text-primary"></i>Company (Buyer)</div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Company name <span class="text-danger">*</span></label><input class="form-control" name="company_name" data-required-for="COMPANY" value="<?= $escape($bill['company_name'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="company_address" rows="2"><?= $escape($bill['company_address'] ?? '') ?></textarea></div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="company_phone" value="<?= $escape($bill['company_phone'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="company_email" value="<?= $escape($bill['company_email'] ?? '') ?>"></div>
                    </div>
                    <div class="mt-3"><label class="form-label">GSTIN</label><input class="form-control text-uppercase" name="company_gstin" maxlength="30" value="<?= $escape($bill['company_gstin'] ?? '') ?>"></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3" id="customerFields">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-person-vcard me-2 text-primary"></i>Customer / Contact</div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Customer name <span class="text-danger">*</span></label><input class="form-control" name="customer_name" data-required-for="CUSTOMER" value="<?= $escape($bill['customer_name'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Phone <span class="text-danger">*</span></label><input class="form-control" name="customer_phone" data-required-for="CUSTOMER" value="<?= $escape($bill['customer_phone'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea class="form-control" name="customer_address" rows="2"><?= $escape($bill['customer_address'] ?? '') ?></textarea></div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">City</label><input class="form-control" name="customer_city" value="<?= $escape($bill['customer_city'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">State</label><input class="form-control" name="customer_state" value="<?= $escape($bill['customer_state'] ?? '') ?>"></div>
                    </div>
                    <div class="mt-3"><label class="form-label">GSTIN</label><input class="form-control text-uppercase" name="customer_gstin" maxlength="30" value="<?= $escape($bill['customer_gstin'] ?? '') ?>"></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-credit-card me-2 text-primary"></i>Invoice &amp; Payment</div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Bill date</label><input class="form-control" type="date" name="bill_date" required value="<?= $escape($bill['bill_date'] ?? date('Y-m-d')) ?>"></div>
                    <div class="mb-3"><label class="form-label">Payment method</label>
                        <select class="form-select" name="payment_method" id="paymentMethod">
                            <?php foreach (['CASH' => 'Cash', 'UPI' => 'UPI', 'CASH_UPI' => 'Cash + UPI', 'CARD' => 'Card', 'NET_BANKING' => 'Net Banking', 'CREDIT' => 'Credit'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($bill['payment_method'] ?? 'CASH') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="splitPaymentFields" class="row g-2 <?= ($bill['payment_method'] ?? '') === 'CASH_UPI' ? '' : 'd-none' ?>">
                        <div class="col-6"><label class="form-label">Cash amount</label><input class="form-control" type="number" step="0.01" min="0" name="cash_amount" value="<?= $escape($bill['cash_amount'] ?? '0.00') ?>"></div>
                        <div class="col-6"><label class="form-label">UPI amount</label><input class="form-control" type="number" step="0.01" min="0" name="upi_amount" value="<?= $escape($bill['upi_amount'] ?? '0.00') ?>"></div>
                    </div>
                    <div class="mt-3"><label class="form-label">UPI reference</label><input class="form-control" name="upi_reference" value="<?= $escape($bill['upi_reference'] ?? '') ?>"></div>
                    <div class="mt-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"><?= $escape($bill['notes'] ?? '') ?></textarea></div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-upc-scan me-2 text-primary"></i>Scan Production Inventory QR</div>
                <div class="card-body">
                    <label class="form-label">Scan QR code or search product, SKU, size, or color</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input id="inventorySearch" class="form-control" autocomplete="off" placeholder="Scan QR label or type to search">
                    </div>
                    <div id="searchResults" class="list-group mt-2"></div>
                    <div id="cartMessage" class="small text-muted mt-2">Only unbilled, available QC-passed QR pieces can be added.</div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Product / QR codes</th><th class="text-end">MRP</th><th style="width:100px">Discount %</th><th style="width:120px">Price / piece<br><small>before tax</small></th><th style="min-width:330px">GST / Tax details</th><th class="text-end">Tax</th><th class="text-end">Total incl. tax</th><th></th></tr></thead>
                        <tbody id="billCart"><tr><td colspan="7" class="text-center text-muted py-5">Scan or search for items to add to this bill.</td></tr></tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row justify-content-end">
                        <div class="col-lg-6">
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Gross / MRP</span><strong id="grossTotal">₹0.00</strong></div>
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Discount</span><strong id="discountTotal">₹0.00</strong></div>
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Subtotal before GST</span><strong id="subtotal">₹0.00</strong></div>
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Total GST</span><strong id="taxTotal">₹0.00</strong></div>
                            <hr>
                            <div class="d-flex justify-content-between fs-5"><span class="fw-bold">Total</span><strong id="grandTotal">₹0.00</strong></div>
                            <div id="splitBalance" class="small text-muted text-end mt-2"></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a class="btn btn-outline-secondary" href="<?= url($isEdit ? 'production/billing/view/' . (int)$bill['id'] : 'production/billing') ?>">Cancel</a>
                        <button class="btn btn-primary" id="saveBillButton"><i class="bi bi-check2-circle me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Bill' ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
(() => {
    const form = document.getElementById('productionBillForm');
    const initialItems = <?= json_encode($existingItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const billId = <?= (int)($bill['id'] ?? 0) ?>;
    const cart = new Map();
    const search = document.getElementById('inventorySearch');
    const resultBox = document.getElementById('searchResults');
    const cartBody = document.getElementById('billCart');
    const buyerType = document.getElementById('buyerType');
    const money = value => '₹' + Number(value || 0).toFixed(2);
    let searchTimer;

    function updateBuyerFields() {
        const selectedType = buyerType.value;
        document.getElementById('companyFields').classList.toggle('d-none', selectedType !== 'COMPANY');
        document.getElementById('customerFields').classList.toggle('d-none', selectedType !== 'CUSTOMER');
        form.querySelectorAll('[data-required-for]').forEach(input => {
            input.required = input.dataset.requiredFor === selectedType;
        });
    }

    initialItems.forEach(item => cart.set(Number(item.qc_item_id), {
        ...item,
        tags: Array.isArray(item.tags) ? item.tags : [],
        unit_price: Number(item.unit_price),
        mrp: Number(item.mrp),
        gst_percentage: Number(item.gst_percentage),
        gst_type: item.gst_type || (Number(item.gst_percentage) > 0 ? 'BOTH' : 'EXEMPTED'),
        tax_input_type: item.tax_input_type || 'PERCENT',
        sgst_rate: Number(item.sgst_rate ?? Number(item.gst_percentage || 0) / 2),
        cgst_rate: Number(item.cgst_rate ?? Number(item.gst_percentage || 0) / 2),
        igst_rate: Number(item.igst_rate || 0)
    }));

    const assignedTags = () => new Set([...cart.values()].flatMap(item => item.tags.map(tag => tag.toUpperCase())));

    function renderCart() {
        cartBody.replaceChildren();
        if (!cart.size) {
            const row = cartBody.insertRow();
            const cell = row.insertCell();
            cell.colSpan = 8;
            cell.className = 'text-center text-muted py-5';
            cell.textContent = 'Scan or search for items to add to this bill.';
            updateTotals();
            return;
        }

        let index = 0;
        [...cart.values()].forEach(item => {
            const row = cartBody.insertRow();
            const productCell = row.insertCell();
            const name = document.createElement('div');
            name.className = 'fw-semibold';
            name.textContent = item.product_name;
            productCell.append(name);
            const meta = document.createElement('div');
            meta.className = 'small text-muted';
            meta.textContent = [item.sku, item.size_name, item.color_name, `${item.tags.length} piece(s)`].filter(Boolean).join(' · ');
            productCell.append(meta);

            const tagsWrap = document.createElement('div');
            tagsWrap.className = 'd-flex flex-wrap gap-1 mt-1';
            item.tags.forEach(tag => {
                const badge = document.createElement('button');
                badge.type = 'button';
                badge.className = 'badge border-0 bg-light text-dark font-monospace';
                badge.title = 'Remove QR from bill';
                badge.textContent = `${tag} ×`;
                badge.addEventListener('click', () => {
                    item.tags = item.tags.filter(existing => existing !== tag);
                    if (!item.tags.length) cart.delete(Number(item.qc_item_id));
                    renderCart();
                });
                tagsWrap.append(badge);
            });
            productCell.append(tagsWrap);

            const mrpCell = row.insertCell();
            mrpCell.className = 'text-end text-nowrap';
            mrpCell.textContent = money(item.mrp);

            const discountCell = row.insertCell();
            const discountInput = document.createElement('input');
            discountInput.type = 'number';
            discountInput.min = '0';
            discountInput.max = '100';
            discountInput.step = '0.01';
            discountInput.className = 'form-control form-control-sm';
            discountInput.value = item.mrp > 0 ? Math.max(0, (1 - item.unit_price / item.mrp) * 100).toFixed(2) : '0';
            discountInput.addEventListener('input', () => {
                item.unit_price = Math.round(item.mrp * (1 - Math.min(100, Math.max(0, Number(discountInput.value) || 0)) / 100) * 100) / 100;
                priceInput.value = item.unit_price.toFixed(2);
                updateTotals();
            });
            discountCell.append(discountInput);

            const priceCell = row.insertCell();
            const priceInput = document.createElement('input');
            priceInput.type = 'number';
            priceInput.min = '0';
            priceInput.step = '0.01';
            priceInput.className = 'form-control form-control-sm';
            priceInput.value = Number(item.unit_price).toFixed(2);
            priceInput.addEventListener('input', () => {
                item.unit_price = Math.max(0, Number(priceInput.value) || 0);
                discountInput.value = item.mrp > 0 ? Math.max(0, (1 - item.unit_price / item.mrp) * 100).toFixed(2) : '0';
                updateTotals();
            });
            priceCell.append(priceInput);

            const gstCell = row.insertCell();
            const gstControls = document.createElement('div');
            gstControls.className = 'd-flex flex-column gap-1';
            const gstTopRow = document.createElement('div');
            gstTopRow.className = 'd-flex gap-1';
            const gstTypeSelect = document.createElement('select');
            gstTypeSelect.className = 'form-select form-select-sm';
            gstTypeSelect.setAttribute('aria-label', 'GST type');
            [
                ['SGST', 'SGST'],
                ['IGST', 'IGST'],
                ['CGST', 'CGST'],
                ['BOTH', 'Both (CGST and SGST)'],
                ['EXEMPTED', 'Exempted']
            ].forEach(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                gstTypeSelect.append(option);
            });
            gstTypeSelect.value = item.gst_type || 'BOTH';

            const taxInputTypeSelect = document.createElement('select');
            taxInputTypeSelect.className = 'form-select form-select-sm';
            taxInputTypeSelect.setAttribute('aria-label', 'Tax input type');
            [['PERCENT', 'Percentage (%)'], ['FIXED', 'Fixed amount / piece']].forEach(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                taxInputTypeSelect.append(option);
            });
            taxInputTypeSelect.value = item.tax_input_type || 'PERCENT';
            gstTopRow.append(gstTypeSelect, taxInputTypeSelect);
            gstControls.append(gstTopRow);

            const ratesRow = document.createElement('div');
            ratesRow.className = 'd-flex gap-1';
            const rateInputs = {};
            [['sgst_rate', 'SGST', 'SGST'], ['igst_rate', 'IGST', 'IGST'], ['cgst_rate', 'CGST', 'CGST']].forEach(([key, label, type]) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'flex-fill';
                wrapper.dataset.gstRateType = type;
                const rateInput = document.createElement('input');
                rateInput.type = 'number';
                rateInput.min = '0';
                rateInput.step = '0.01';
                rateInput.className = 'form-control form-control-sm';
                rateInput.placeholder = label;
                rateInput.name = `items[${index}][${key}]`;
                rateInput.value = Number(item[key] || 0);
                rateInput.addEventListener('input', () => {
                    item[key] = Math.max(0, Number(rateInput.value) || 0);
                    updateTotals();
                });
                wrapper.append(rateInput);
                rateInputs[key] = {wrapper, input: rateInput};
                ratesRow.append(wrapper);
            });
            gstControls.append(ratesRow);
            const updateGstControls = () => {
                const type = gstTypeSelect.value;
                const visibleTypes = type === 'BOTH' ? ['SGST', 'CGST'] : (type === 'EXEMPTED' ? [] : [type]);
                Object.entries(rateInputs).forEach(([key, entry]) => {
                    entry.wrapper.classList.toggle('d-none', !visibleTypes.includes(entry.wrapper.dataset.gstRateType));
                    const unit = taxInputTypeSelect.value === 'PERCENT' ? '(%)' : '(₹ / piece)';
                    entry.input.placeholder = `${entry.wrapper.dataset.gstRateType} ${unit}`;
                    entry.input.setAttribute('aria-label', `${entry.wrapper.dataset.gstRateType} rate ${unit}`);
                    entry.input.max = taxInputTypeSelect.value === 'PERCENT' ? '100' : '';
                });
            };
            gstTypeSelect.addEventListener('change', () => {
                item.gst_type = gstTypeSelect.value;
                updateGstControls();
                updateTotals();
            });
            taxInputTypeSelect.addEventListener('change', () => {
                item.tax_input_type = taxInputTypeSelect.value;
                updateGstControls();
                updateTotals();
            });
            updateGstControls();
            gstCell.append(gstControls);

            const taxCell = row.insertCell();
            taxCell.className = 'text-end text-nowrap';
            taxCell.dataset.taxFor = item.qc_item_id;

            const lineTotalCell = row.insertCell();
            lineTotalCell.className = 'text-end text-nowrap fw-semibold';
            lineTotalCell.dataset.totalFor = item.qc_item_id;

            const removeCell = row.insertCell();
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn btn-sm btn-outline-danger';
            removeButton.innerHTML = '<i class="bi bi-trash"></i>';
            removeButton.title = 'Remove product';
            removeButton.addEventListener('click', () => {
                cart.delete(Number(item.qc_item_id));
                renderCart();
            });
            removeCell.append(removeButton);

            [['qc_item_id', item.qc_item_id], ['unit_price', item.unit_price], ['gst_type', item.gst_type || 'BOTH'], ['tax_input_type', item.tax_input_type || 'PERCENT']].forEach(([key, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${index}][${key}]`;
                input.value = value;
                row.append(input);
            });
            item.tags.forEach(tag => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${index}][tags][]`;
                input.value = tag;
                row.append(input);
            });
            index++;
        });
        updateTotals();
    }

    function updateTotals() {
        let gross = 0;
        let discount = 0;
        let subtotal = 0;
        let taxes = 0;
        let grandTotal = 0;
        [...cart.values()].forEach(item => {
            const quantity = item.tags.length;
            const lineGross = Number(item.mrp || 0) * quantity;
            const taxable = Number(item.unit_price || 0) * quantity;
            const inputType = item.tax_input_type || 'PERCENT';
            const gstType = item.gst_type || 'BOTH';
            const rates = {
                SGST: Number(item.sgst_rate || 0),
                CGST: Number(item.cgst_rate || 0),
                IGST: Number(item.igst_rate || 0)
            };
            const includedTypes = gstType === 'BOTH' ? ['SGST', 'CGST'] : (gstType === 'EXEMPTED' ? [] : [gstType]);
            const lineTax = includedTypes.reduce((sum, type) => {
                const rate = rates[type] || 0;
                const componentTax = inputType === 'PERCENT' ? taxable * rate / 100 : rate * quantity;
                return sum + Math.round((componentTax + Number.EPSILON) * 100) / 100;
            }, 0);
            const lineTotal = taxable + lineTax;
            gross += lineGross;
            discount += Math.max(0, lineGross - taxable);
            subtotal += taxable;
            taxes += lineTax;
            grandTotal += lineTotal;
            const lineTotalCell = cartBody.querySelector(`[data-total-for="${item.qc_item_id}"]`);
            const taxCell = cartBody.querySelector(`[data-tax-for="${item.qc_item_id}"]`);
            if (lineTotalCell) lineTotalCell.textContent = money(lineTotal);
            if (taxCell) taxCell.textContent = money(lineTax);
        });
        document.getElementById('grossTotal').textContent = money(gross);
        document.getElementById('discountTotal').textContent = money(discount);
        document.getElementById('subtotal').textContent = money(subtotal);
        document.getElementById('taxTotal').textContent = money(taxes);
        document.getElementById('grandTotal').textContent = money(grandTotal);
        const cash = Number(form.elements.cash_amount?.value || 0);
        const upi = Number(form.elements.upi_amount?.value || 0);
        document.getElementById('splitBalance').textContent = document.getElementById('paymentMethod').value === 'CASH_UPI'
            ? `Cash + UPI: ${money(cash + upi)} / ${money(grandTotal)}`
            : '';
    }

    function addResult(item, selectedTag) {
        const assigned = assignedTags();
        const tag = selectedTag || item.available_tags.find(code => !assigned.has(code.toUpperCase()));
        if (!tag || assigned.has(tag.toUpperCase())) {
            document.getElementById('cartMessage').textContent = 'No unbilled QR piece is available for this item.';
            return;
        }
        const key = Number(item.qc_item_id);
        if (!cart.has(key)) {
            cart.set(key, {
                qc_item_id: key,
                product_name: item.product_name,
                sku: item.sku || '',
                size_name: item.size_name || '',
                color_name: item.color_name || '',
                mrp: Number(item.mrp || 0),
                unit_price: Number(item.unit_price || item.mrp || 0),
                gst_percentage: Number(item.gst_percentage || 0),
                gst_type: Number(item.gst_percentage || 0) > 0 ? 'BOTH' : 'EXEMPTED',
                tax_input_type: 'PERCENT',
                sgst_rate: Number(item.gst_percentage || 0) / 2,
                cgst_rate: Number(item.gst_percentage || 0) / 2,
                igst_rate: 0,
                tags: []
            });
        }
        cart.get(key).tags.push(tag);
        document.getElementById('cartMessage').textContent = `Added QR ${tag}.`;
        search.value = '';
        resultBox.replaceChildren();
        renderCart();
        search.focus();
    }

    async function lookup() {
        const query = search.value.trim();
        resultBox.replaceChildren();
        if (!query) return;
        try {
            const params = new URLSearchParams({q: query});
            if (billId) params.set('bill_id', String(billId));
            const response = await fetch('<?= url('api/production/billing/search') ?>?' + params.toString(), {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'Inventory search failed.');
            const assigned = assignedTags();
            let resultCount = 0;
            payload.data.items.forEach(item => {
                const available = item.available_tags.filter(tag => !assigned.has(tag.toUpperCase()));
                if (!available.length) return;
                const exactTag = available.find(tag => tag.toUpperCase() === query.toUpperCase());
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                const details = document.createElement('span');
                const title = document.createElement('strong');
                title.textContent = item.product_name;
                const subtitle = document.createElement('small');
                subtitle.className = 'd-block text-muted';
                subtitle.textContent = [item.sku, item.size_name, item.color_name, `${available.length} available`].filter(Boolean).join(' · ');
                details.append(title, subtitle);
                const code = document.createElement('span');
                code.className = 'badge text-bg-light font-monospace';
                code.textContent = exactTag || available[0];
                button.append(details, code);
                button.addEventListener('click', () => addResult(item, exactTag || available[0]));
                resultBox.append(button);
                resultCount++;
            });
            if (!resultCount) {
                const empty = document.createElement('div');
                empty.className = 'list-group-item text-muted small';
                empty.textContent = 'No available matching Production Inventory items.';
                resultBox.append(empty);
            }
        } catch (error) {
            const message = document.createElement('div');
            message.className = 'list-group-item text-danger small';
            message.textContent = error.message || 'Unable to search Production Inventory.';
            resultBox.append(message);
        }
    }

    search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(lookup, 250);
    });
    search.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            clearTimeout(searchTimer);
            lookup().then(() => {
                const first = resultBox.querySelector('button');
                if (first) first.click();
            });
        }
    });
    buyerType.addEventListener('change', updateBuyerFields);
    document.getElementById('paymentMethod').addEventListener('change', event => {
        document.getElementById('splitPaymentFields').classList.toggle('d-none', event.target.value !== 'CASH_UPI');
        updateTotals();
    });
    ['cash_amount', 'upi_amount'].forEach(name => form.elements[name]?.addEventListener('input', updateTotals));
    form.addEventListener('submit', event => {
        if (!cart.size || [...cart.values()].some(item => !item.tags.length)) {
            event.preventDefault();
            document.getElementById('cartMessage').textContent = 'Scan at least one available QR item before saving.';
            return;
        }
        if ([...cart.values()].some(item => item.unit_price < 0 || item.gst_percentage < 0 || item.gst_percentage > 100)) {
            event.preventDefault();
            document.getElementById('cartMessage').textContent = 'Check the item price and GST values.';
        }
    });
    updateBuyerFields();
    renderCart();
})();
</script>
