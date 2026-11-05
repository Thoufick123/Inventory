<!-- WhatsApp Orders Channel Stock Request Creation Page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill fw-semibold fs-7">
                <i class="bi bi-whatsapp me-1"></i> WhatsApp Orders
            </span>
            <span class="text-muted small">|</span>
            <span class="text-muted small">Channel Stock Requisition</span>
        </div>
        <h2 class="h3 mb-0 text-gray-800 fw-bold"><i class="bi bi-box-arrow-in-down me-2 text-success"></i>Create WhatsApp Stock Request</h2>
        <p class="text-muted small mb-0">Record a stock requisition submitted for WhatsApp channel inventory to be fulfilled by central warehouse stock.</p>
    </div>
    <div>
        <a href="<?= url('warehouse/whatsapp-orders/allocations') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Inventory
        </a>
    </div>
</div>

<form method="POST" action="<?= url('warehouse/whatsapp-orders/requests/store') ?>" class="card border-0 shadow-sm rounded-3 p-4">
    <?= CSRF::field() ?>

    <div class="row g-3 mb-4">
        <!-- Requesting Channel -->
        <div class="col-md-4">
            <label class="form-label fw-bold">Requesting Channel Store <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text bg-success bg-opacity-10 text-success border-end-0"><i class="bi bi-whatsapp"></i></span>
                <input type="text" class="form-control bg-light text-dark fw-bold border-start-0" value="WhatsApp Channel Store" readonly>
                <input type="hidden" name="channel_id" value="<?= htmlspecialchars($channel['id'] ?? 2) ?>">
            </div>
            <small class="text-muted">Channel receiving stock replenishment</small>
        </div>

        <!-- Source Warehouse -->
        <div class="col-md-4">
            <label class="form-label fw-bold">Source Warehouse <span class="text-danger">*</span></label>
            <select name="warehouse_id" class="form-select" required <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || (!empty($warehouses) && count($warehouses) <= 1)) ? 'disabled' : '' ?>>
                <?php if (!function_exists('currentWarehouseId') || currentWarehouseId() === null && (!empty($warehouses) && count($warehouses) > 1)): ?>
                    <option value="">-- Select Source Warehouse --</option>
                <?php endif; ?>
                <?php if (!empty($warehouses)): foreach ($warehouses as $wh): ?>
                    <option value="<?= $wh['id'] ?>" <?= (count($warehouses) === 1 || (function_exists('currentWarehouseId') && currentWarehouseId() == $wh['id'])) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($wh['name']) ?><?= !empty($wh['code']) ? ' (' . htmlspecialchars($wh['code']) . ')' : '' ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
            <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || (!empty($warehouses) && count($warehouses) <= 1)): ?>
                <input type="hidden" name="warehouse_id" value="<?= htmlspecialchars((string)($warehouses[0]['id'] ?? (function_exists('currentWarehouseId') ? currentWarehouseId() : ''))) ?>">
            <?php endif; ?>
            <small class="text-muted">Central warehouse fulfilling the request</small>
        </div>

        <!-- Requisition Priority / Notes -->
        <div class="col-md-4">
            <label class="form-label fw-bold">Request Notes / Reason</label>
            <input type="text" name="notes" class="form-control" placeholder="e.g. WhatsApp campaign restock, Urgent requisition">
            <small class="text-muted">Optional notes or remarks</small>
        </div>
    </div>

    <!-- Products & Items Table -->
    <div class="card border mb-4">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-boxes me-2 text-success"></i>Requested Stock Items</h6>
            <button type="button" class="btn btn-sm btn-success" onclick="addRequestRow()">
                <i class="bi bi-plus-lg me-1"></i> Add Product Item
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="requestItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40%;">Select Product / Variant SKU <span class="text-danger">*</span></th>
                            <th style="width: 20%;">Warehouse Stock</th>
                            <th style="width: 25%;">Requested Qty (pcs) <span class="text-danger">*</span></th>
                            <th style="width: 15%;" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemsContainer">
                        <!-- Dynamic Rows appended via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="<?= url('warehouse/whatsapp-orders/allocations') ?>" class="btn btn-light border">Cancel</a>
        <button type="submit" class="btn btn-success px-4 fw-bold">
            <i class="bi bi-check-circle me-1"></i> Save &amp; Submit WhatsApp Request
        </button>
    </div>
</form>

<script>
const availableVariants = <?= json_encode($variants ?? []) ?>;

function addRequestRow() {
    const container = document.getElementById('itemsContainer');
    const rowIndex = container.children.length;
    
    let optionsHtml = '<option value="">-- Select Variant SKU / Product --</option>';
    availableVariants.forEach(v => {
        optionsHtml += `<option value="${v.id}" data-stock="${v.stock_qty || 0}">${v.product_name} | ${v.category_name || 'General'} | SKU: ${v.sku} (Stock: ${v.stock_qty || 0} pcs)</option>`;
    });

    const tr = document.createElement('tr');
    tr.id = `row_${rowIndex}`;
    tr.innerHTML = `
        <td>
            <select name="items[${rowIndex}][variant_id]" class="form-select variant-select" required onchange="updateStockDisplay(this)">
                ${optionsHtml}
            </select>
        </td>
        <td>
            <span class="badge bg-light text-dark border fs-6 stock-display">0 pcs</span>
        </td>
        <td>
            <input type="number" name="items[${rowIndex}][requested_qty]" class="form-control fw-bold" min="1" value="1" required>
        </td>
        <td class="text-end">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    container.appendChild(tr);
}

function refreshRequestProductRows() {
    document.querySelectorAll('#itemsContainer .variant-select').forEach(select => {
        const current = select.value;
        const matches = availableVariants;
        select.innerHTML = '<option value="">-- Select Variant SKU / Product --</option>' + matches.map(v => `<option value="${v.id}" data-stock="${v.stock_qty || 0}">${v.product_name} | ${v.category_name || 'General'} | SKU: ${v.sku} (Stock: ${v.stock_qty || 0} pcs)</option>`).join('');
        if (matches.some(v => String(v.id) === String(current))) select.value = current;
        updateStockDisplay(select);
    });
}

function variantSearchText(v) {
    return [v.product_name, v.sku, v.category_name, v.qr_code].filter(Boolean).join(' ').toLowerCase();
}

function renderProductMatches(query) {
    const box = document.getElementById('warehouseProductMatches');
    const q = String(query || '').trim().toLowerCase();
    if (!q) { box.classList.add('d-none'); box.innerHTML = ''; return; }
    const matches = availableVariants.filter(v => variantSearchText(v).includes(q)).slice(0, 8);
    box.innerHTML = matches.length ? matches.map(v => `
        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="selectRequestProduct(${Number(v.id)})">
            <span><strong>${escapeRequestHtml(v.product_name || '')}</strong><small class="d-block text-muted">${escapeRequestHtml(v.sku || '')} · ${escapeRequestHtml(v.category_name || 'General')}</small></span>
            <span class="badge bg-success-subtle text-success">${Number(v.stock_qty || 0)} pcs</span>
        </button>`).join('') : '<div class="list-group-item text-muted small">No warehouse products found.</div>';
    box.classList.remove('d-none');
}

function selectRequestProduct(variantId) {
    const select = document.querySelector('#itemsContainer .variant-select');
    if (!select) { addRequestRow(); return selectRequestProduct(variantId); }
    select.value = String(variantId);
    updateStockDisplay(select);
    document.getElementById('requestProductSearch').value = '';
    document.getElementById('warehouseProductMatches').classList.add('d-none');
}

function addMatchingRequestProduct() {
    const query = document.getElementById('requestProductSearch').value;
    const match = availableVariants.find(v => variantSearchText(v).includes(String(query).trim().toLowerCase()));
    if (match) selectRequestProduct(match.id);
}

function escapeRequestHtml(value) {
    return String(value).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}

function updateStockDisplay(selectElem) {
    const selected = selectElem.options[selectElem.selectedIndex];
    const stock = selected ? selected.getAttribute('data-stock') : 0;
    const row = selectElem.closest('tr');
    row.querySelector('.stock-display').textContent = (stock || 0) + ' pcs';
}

document.addEventListener('DOMContentLoaded', () => {
    addRequestRow();
});
</script>
