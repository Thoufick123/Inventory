<?php
$currentTab = $currentTab ?? ($_GET['tab'] ?? 'all');
$summary = $summary ?? [];
$recentReturns = $recentReturns ?? [];
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex flex-wrap gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border">Production Billing</span>
            <span class="badge bg-light text-secondary border">Returns &amp; Exchanges</span>
        </div>
        <h2 class="mb-1 fw-bold">Customer Returns &amp; Exchanges</h2>
        <p class="text-muted mb-0">Scan the original billed QR to refund or exchange a production-billed item.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= url('production/billing') ?>"><i class="bi bi-receipt me-1"></i>Billing</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl"><div class="card border-0 shadow-sm border-start border-primary border-4 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">All Cases</small><div class="fs-3 fw-bold text-primary"><?= number_format((int)$summary['total_transactions']) ?> <small class="fs-6 fw-normal text-muted">txns</small></div><small class="text-muted"><?= number_format((int)$summary['today_transactions']) ?> today · <?= number_format((int)$summary['total_items_returned']) ?> pcs</small></div></div></div>
    <div class="col-sm-6 col-xl"><div class="card border-0 shadow-sm border-start border-danger border-4 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">Refund Value</small><div class="fs-3 fw-bold text-danger">₹<?= number_format((float)$summary['total_refund_amount'], 2) ?></div><small class="text-danger"><?= number_format((int)$summary['total_returns_count']) ?> direct returns</small></div></div></div>
    <div class="col-sm-6 col-xl"><div class="card border-0 shadow-sm border-start border-success border-4 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">Exchanges</small><div class="fs-3 fw-bold text-success"><?= number_format((int)$summary['total_exchanges_count']) ?> <small class="fs-6 fw-normal text-muted">cases</small></div><small class="text-success">QR-for-QR replacements</small></div></div></div>
    <div class="col-sm-6 col-xl"><div class="card border-0 shadow-sm border-start border-info border-4 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">Returned Pieces</small><div class="fs-3 fw-bold text-info"><?= number_format((int)$summary['total_items_returned']) ?> <small class="fs-6 fw-normal text-muted">pcs</small></div><small class="text-info">Good tags available to bill again</small></div></div></div>
    <div class="col-sm-6 col-xl"><div class="card border-0 shadow-sm border-start border-warning border-4 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">Damaged Pieces</small><div class="fs-3 fw-bold text-warning"><?= number_format((int)$summary['total_damaged_pcs']) ?> <small class="fs-6 fw-normal text-muted">pcs</small></div><small class="text-warning">Excluded from billing inventory</small></div></div></div>
</div>

<?php if (hasPermission('production_billing_returns', 'view')): ?>
<div class="card border-0 shadow-sm mb-4 border-top border-4 border-primary">
    <div class="card-body p-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-7">
                <label for="productionBillSearch" class="form-label fw-bold">Search Production Bill</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light"><i class="bi bi-qr-code-scan text-primary"></i></span>
                    <input type="search" id="productionBillSearch" class="form-control" placeholder="Bill number or customer phone">
                    <button class="btn btn-primary px-4" type="button" id="lookupProductionBill"><i class="bi bi-search me-1"></i>Lookup</button>
                </div>
                <div id="lookupMessage" class="small mt-2" role="status" aria-live="polite"></div>
            </div>
            <div class="col-lg-5">
                <div class="alert alert-light border mb-0 small">
                    <div class="fw-semibold mb-1"><i class="bi bi-info-circle text-primary me-1"></i>Returns workflow</div>
                    Good returns release their QR for future Production Billing. Damaged returns are kept unavailable. Exchanges require scanning an available replacement QR and calculate the price difference.
                </div>
            </div>
        </div>
        <div id="billResult" class="mt-4 d-none"></div>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2"><i class="bi bi-clock-history text-primary fs-5"></i><h5 class="mb-0 fw-bold">Return &amp; Exchange History</h5><span class="badge bg-secondary-subtle text-secondary"><?= count($recentReturns) ?> records</span></div>
        <div class="btn-group btn-group-sm" role="group" aria-label="Return history filter">
            <a class="btn <?= $currentTab === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= url('production/billing/returns?tab=all') ?>">All</a>
            <a class="btn <?= $currentTab === 'returns' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= url('production/billing/returns?tab=returns') ?>">Direct Returns</a>
            <a class="btn <?= $currentTab === 'exchanges' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= url('production/billing/returns?tab=exchanges') ?>">Exchanges</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase small"><tr><th class="ps-3">Return Number</th><th>Original Bill / Date</th><th>Customer</th><th>Type &amp; Items</th><th class="text-end">Refund / Difference</th><th>Settlement</th><th>Processed By</th><th class="text-end pe-3">Actions</th></tr></thead>
            <tbody>
            <?php if (!$recentReturns): ?>
                <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No Production Billing returns or exchanges yet.</td></tr>
            <?php else: foreach ($recentReturns as $row): ?>
                <tr>
                    <td class="ps-3"><strong class="font-monospace"><?= e($row['return_number']) ?></strong><small class="d-block text-muted"><?= e(formatDate($row['created_at'])) ?></small></td>
                    <td><span class="font-monospace fw-semibold"><?= e($row['bill_number']) ?></span><small class="d-block text-muted"><?= e(formatDate($row['bill_date'])) ?></small></td>
                    <td><?= e($row['customer_name']) ?><small class="d-block text-muted"><?= e($row['customer_phone']) ?></small></td>
                    <td><span class="badge <?= $row['action_type'] === 'EXCHANGE' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>"><?= e($row['action_type']) ?></span><small class="d-block text-muted"><?= number_format((int)$row['item_count']) ?> pc(s)</small></td>
                    <td class="text-end"><?php if ($row['action_type'] === 'EXCHANGE'): ?><span class="fw-semibold">New ₹<?= number_format((float)$row['new_amount'], 2) ?></span><small class="d-block text-muted">Old ₹<?= number_format((float)$row['refund_amount'], 2) ?></small><?php else: ?><span class="fw-semibold text-danger">₹<?= number_format((float)$row['refund_amount'], 2) ?></span><?php endif; ?></td>
                    <td><?= e($row['payment_action']) ?> <small class="d-block text-muted"><?= e($row['settlement_mode']) ?></small></td>
                    <td><?= e($row['processed_by_name'] ?? 'Staff') ?></td>
                    <td class="text-end pe-3"><div class="btn-group btn-group-sm"><a class="btn btn-outline-secondary" href="<?= url('production/billing/returns/view/' . $row['id']) ?>" title="View"><i class="bi bi-eye"></i></a><a class="btn btn-outline-primary" href="<?= url('production/billing/returns/print/' . $row['id']) ?>" target="_blank" rel="noopener" title="Print"><i class="bi bi-printer"></i></a></div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(() => {
    const search = document.getElementById('productionBillSearch');
    const lookupButton = document.getElementById('lookupProductionBill');
    const message = document.getElementById('lookupMessage');
    const result = document.getElementById('billResult');
    const csrfToken = <?= json_encode(CSRF::token()) ?>;
    let billData = null;
    let actionType = 'RETURN';

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
    }
    function money(value) { return `₹${Number(value || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`; }
    function setMessage(text, kind = 'muted') {
        message.className = `small mt-2 text-${kind}`;
        message.textContent = text;
    }
    function updateMode() {
        const isExchange = actionType === 'EXCHANGE';
        document.querySelectorAll('[data-return-action]').forEach(button => {
            button.classList.toggle('active', button.dataset.returnAction === actionType);
        });
        document.querySelectorAll('.replacement-input').forEach(input => {
            input.closest('td').classList.toggle('d-none', !isExchange);
        });
        document.querySelectorAll('.replacement-header').forEach(header => header.classList.toggle('d-none', !isExchange));
        document.querySelectorAll('.condition-input').forEach(select => {
            if (isExchange) select.value = 'GOOD';
            select.disabled = isExchange;
        });
    }
    function renderBill(data) {
        billData = data;
        const bill = data.bill;
        const activeItems = data.items.flatMap(item => item.tags.map(tag => ({...item, tag})));
        result.classList.remove('d-none');
        if (!activeItems.length) {
            result.innerHTML = `<div class="alert alert-warning mb-0">Bill <strong>${escapeHtml(bill.bill_number)}</strong> has no active billed QR codes available for return.</div>`;
            return;
        }
        result.innerHTML = `
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3 p-3 bg-light border rounded">
                <div><div class="fw-bold">Bill <span class="font-monospace">${escapeHtml(bill.bill_number)}</span></div><div class="small text-muted">${escapeHtml(bill.display_customer_name)} · ${escapeHtml(bill.display_customer_phone || 'No phone')} · ${escapeHtml(bill.bill_date)}</div></div>
                <div class="text-end"><div class="small text-muted">Bill total</div><strong>${money(bill.total_amount)}</strong></div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <div class="btn-group btn-group-sm" role="group" aria-label="Return action">
                    <button type="button" class="btn btn-primary active" data-return-action="RETURN">Direct Refund</button>
                    <button type="button" class="btn btn-outline-success" data-return-action="EXCHANGE">Exchange</button>
                </div>
                <select id="settlementMode" class="form-select form-select-sm" style="width:auto"><option value="CASH">Cash</option><option value="UPI">UPI</option><option value="CREDIT_NOTE">Credit note</option><option value="NONE">No settlement</option></select>
                <input id="returnReason" class="form-control form-control-sm" style="max-width:320px" maxlength="500" placeholder="Reason for return / exchange" value="Customer Return">
            </div>
            <div class="table-responsive border rounded">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Return</th><th>Product / Variant</th><th>Original QR</th><th>Condition</th><th class="replacement-header d-none">Replacement QR / SKU</th><th class="text-end">Original Paid</th></tr></thead>
                    <tbody>${activeItems.map((item, index) => `<tr data-return-row data-index="${index}" data-tag="${escapeHtml(item.tag)}" data-amount="${Number(item.unit_total || 0)}">
                        <td><input class="form-check-input return-select" type="checkbox" checked aria-label="Select ${escapeHtml(item.tag)}"></td>
                        <td><div class="fw-semibold">${escapeHtml(item.product_name)}</div><small class="text-muted">${escapeHtml(item.sku)} · ${escapeHtml([item.size_name, item.color_name].filter(Boolean).join(' / '))}</small></td>
                        <td class="font-monospace">${escapeHtml(item.tag)}</td>
                        <td><select class="form-select form-select-sm condition-input"><option value="GOOD">Good</option><option value="DAMAGED">Damaged</option></select></td>
                        <td class="replacement-cell d-none"><input class="form-control form-control-sm font-monospace replacement-input" placeholder="Scan available piece QR" autocomplete="off"></td>
                        <td class="text-end">${money(item.unit_total)}</td>
                    </tr>`).join('')}</tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                <div id="settlementEstimate" class="small text-muted">Select items to calculate the refund. Exchange difference is verified when replacement tags are checked.</div>
                <button type="button" id="processProductionReturn" class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Process Return</button>
            </div>`;
        document.querySelectorAll('[data-return-action]').forEach(button => button.addEventListener('click', () => {
            actionType = button.dataset.returnAction;
            const processButton = document.getElementById('processProductionReturn');
            processButton.innerHTML = actionType === 'EXCHANGE' ? '<i class="bi bi-arrow-left-right me-1"></i>Process Exchange' : '<i class="bi bi-check2-circle me-1"></i>Process Return';
            updateMode();
        }));
        result.querySelectorAll('.return-select').forEach(input => input.addEventListener('change', updateEstimate));
        document.getElementById('processProductionReturn').addEventListener('click', processReturn);
        updateMode();
        updateEstimate();
    }
    function updateEstimate() {
        if (!billData) return;
        const selected = [...result.querySelectorAll('[data-return-row]')].filter(row => row.querySelector('.return-select').checked);
        const total = selected.reduce((sum, row) => sum + Number(row.dataset.amount || 0), 0);
        const estimate = document.getElementById('settlementEstimate');
        if (estimate) estimate.textContent = `${selected.length} piece(s) selected · Original paid value ${money(total)}${actionType === 'EXCHANGE' ? ' · replacement value and difference validated when posted' : ''}`;
    }
    async function runLookup() {
        const query = search.value.trim();
        if (!query) { setMessage('Enter a Production Bill number or customer phone.', 'danger'); return; }
        lookupButton.disabled = true;
        setMessage('Searching bill and active QR tags…');
        try {
            const response = await fetch(`<?= url('production/billing/returns/lookup') ?>?query=${encodeURIComponent(query)}`, {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'Bill lookup failed.');
            setMessage('Bill loaded. Select the returned piece QR codes below.', 'success');
            renderBill(payload.data);
        } catch (error) {
            result.classList.add('d-none');
            setMessage(error.message, 'danger');
        } finally { lookupButton.disabled = false; }
    }
    async function processReturn() {
        const selected = [...result.querySelectorAll('[data-return-row]')].filter(row => row.querySelector('.return-select').checked);
        if (!selected.length) { setMessage('Select at least one billed QR code.', 'danger'); return; }
        const items = selected.map(row => ({
            tag_code: row.dataset.tag,
            condition: row.querySelector('.condition-input').value,
            replacement_tag_code: row.querySelector('.replacement-input')?.value.trim() || '',
        }));
        if (actionType === 'EXCHANGE' && items.some(item => !item.replacement_tag_code)) {
            setMessage('Scan one available replacement QR for each selected return.', 'danger'); return;
        }
        const button = document.getElementById('processProductionReturn');
        button.disabled = true;
        try {
            const response = await fetch('<?= url('production/billing/returns/process') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({
                    _csrf_token: csrfToken,
                    bill_id: Number(billData.bill.id),
                    action_type: actionType,
                    settlement_mode: document.getElementById('settlementMode').value,
                    reason: document.getElementById('returnReason').value.trim(),
                    items,
                }),
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'Return processing failed.');
            window.location.href = payload.details_url;
        } catch (error) {
            setMessage(error.message, 'danger');
            button.disabled = false;
        }
    }

    lookupButton.addEventListener('click', runLookup);
    search.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); runLookup(); } });
})();
</script>
