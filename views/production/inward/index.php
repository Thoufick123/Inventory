<?php
$locationJson = json_encode($locations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>
<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Production Inward / GRN</h2>
        <span class="text-muted">Receive products from warehouses, shops, and other production units.</span>
    </div>
    <a href="<?= url('production/inventory') ?>" class="btn btn-outline-secondary"><i class="bi bi-boxes me-1"></i>Production Inventory</a>
</div>

<?php if (empty($units)): ?>
    <div class="alert alert-warning">No active production unit is available for receiving stock.</div>
<?php elseif (!$unitId): ?>
    <div class="alert alert-info">Select a destination production unit to view stock and receive products.</div>
<?php endif; ?>

<form method="GET" action="<?= url('production/inward') ?>" class="row g-2 align-items-end mb-3">
    <?php if ($scopedUnitId): ?>
        <input type="hidden" name="unit_id" value="<?= (int)$scopedUnitId ?>">
    <?php else: ?>
        <div class="col-md-4">
            <label class="form-label small fw-semibold" for="destinationUnitFilter">Destination Production Unit</label>
            <select class="form-select" name="unit_id" id="destinationUnitFilter" onchange="this.form.submit()">
                <option value="">Select unit</option>
                <?php foreach ($units as $unit): ?>
                    <option value="<?= (int)$unit['id'] ?>" <?= (int)$unitId === (int)$unit['id'] ? 'selected' : '' ?>><?= e($unit['name']) ?> (<?= e($unit['code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="col-md-5">
        <label class="form-label small fw-semibold" for="inwardSearch">Search GRN, batch, or concern</label>
        <input class="form-control" type="search" name="search" id="inwardSearch" value="<?= e($search) ?>" placeholder="GRN number, batch number, source...">
    </div>
    <div class="col-auto"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button></div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-uppercase text-muted">GRN Receipts</div><div class="fs-3 fw-bold"><?= number_format($stats['receipt_count']) ?></div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-uppercase text-muted">Total Received</div><div class="fs-3 fw-bold text-primary"><?= number_format($stats['received_quantity']) ?> <span class="fs-6 fw-normal">pcs</span></div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-uppercase text-muted">Available In This Unit</div><div class="fs-3 fw-bold text-success"><?= number_format($stats['available_quantity']) ?> <span class="fs-6 fw-normal">pcs</span></div></div></div></div>
</div>

<?php if ($canReceive && $unitId): ?>
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div><h5 class="mb-0 fw-bold"><i class="bi bi-upc-scan me-2 text-primary"></i>Receive Products</h5><small class="text-muted">Warehouse batches can be received by batch number; scan individual product labels from any source.</small></div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearScans" title="Clear scanned items"><i class="bi bi-trash3 me-1"></i>Clear</button>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('production/inward/receive') ?>" id="productionInwardForm">
            <?= CSRF::field() ?>
            <input type="hidden" name="destination_unit_id" value="<?= (int)$unitId ?>">
            <input type="hidden" name="items_json" id="itemsJson" value="[]">
            <input type="hidden" name="batch_number" id="batchNumber" value="">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="sourceType">Source Concern</label>
                    <select class="form-select" id="sourceType" name="source_type" required>
                        <option value="WAREHOUSE">Warehouse</option>
                        <option value="SHOP">Shop</option>
                        <option value="PRODUCTION_UNIT">Production Unit</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="sourceId">Source Location</label>
                    <select class="form-select" id="sourceId" name="source_id" required></select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold" for="scanCode">Scan batch number or product QR / SKU</label>
                    <div class="input-group">
                        <input class="form-control font-monospace" id="scanCode" type="text" autocomplete="off" placeholder="Scan code, then press Enter" <?= $unitId ? '' : 'disabled' ?>>
                        <button class="btn btn-primary" type="button" id="lookupCode"><i class="bi bi-upc-scan me-1"></i>Scan</button>
                    </div>
                </div>
                <div class="col-12"><label class="form-label" for="inwardNotes">Notes <span class="text-muted">(optional)</span></label><input class="form-control" id="inwardNotes" name="notes" maxlength="1000" placeholder="Delivery note, condition, or reference"></div>
            </div>
            <div class="alert alert-info py-2 small mb-3" id="scanMessage" role="status">Choose the source location, then scan products or a warehouse batch number.</div>
            <div class="table-responsive border rounded mb-3">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Product</th><th>SKU</th><th>Size / Color</th><th class="text-end">Qty</th><th class="text-end">Available Source Stock</th></tr></thead>
                    <tbody id="scannedItems"><tr><td colspan="5" class="text-center text-muted py-4">No products scanned.</td></tr></tbody>
                    <tfoot class="table-light"><tr><th colspan="3" class="text-end">Total</th><th class="text-end" id="totalScannedQty">0</th><th></th></tr></tfoot>
                </table>
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn btn-success" type="submit" id="postReceipt" disabled><i class="bi bi-check2-circle me-1"></i>Post Inward &amp; Credit Production Stock</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white"><h5 class="mb-0 fw-bold"><i class="bi bi-journal-check me-2 text-primary"></i>Production Inward Ledger</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>GRN / Received</th><th>Source</th><th>Destination</th><th>Batch</th><th class="text-center">Lines</th><th class="text-end">Received</th><th>Received By</th></tr></thead>
            <tbody>
            <?php if (empty($receipts)): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">No inward receipts found for this production unit.</td></tr>
            <?php else: foreach ($receipts as $receipt): ?>
                <tr>
                    <td><strong class="font-monospace"><?= e($receipt['grn_number']) ?></strong><small class="d-block text-muted"><?= e(formatDate($receipt['received_at'])) ?></small></td>
                    <td><span class="badge bg-light text-dark border"><?= e(ucwords(strtolower(str_replace('_', ' ', $receipt['source_type'])))) ?></span><div><?= e($receipt['source_name'] ?? 'Unknown source') ?></div></td>
                    <td><?= e($receipt['destination_name']) ?></td>
                    <td class="font-monospace"><?= e($receipt['source_batch_number'] ?: '—') ?></td>
                    <td class="text-center"><?= number_format((int)$receipt['line_count']) ?></td>
                    <td class="text-end fw-semibold"><?= number_format((int)$receipt['total_quantity']) ?> pcs</td>
                    <td><?= e($receipt['received_by_name'] ?? 'System') ?></td>
                </tr>
                <tr class="table-light"><td colspan="7" class="py-2">
                    <div class="d-flex flex-wrap gap-3 small">
                        <?php foreach ($receipt['items'] as $item): ?>
                            <span><?= e($item['product_name']) ?> · <?= e($item['sku']) ?><?= !empty($item['size_name']) || !empty($item['color_name']) ? ' · ' . e(trim(($item['size_name'] ?? '') . ' ' . ($item['color_name'] ?? ''))) : '' ?> · <?= number_format((int)$item['quantity']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </td></tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border shadow-sm">
    <div class="card-header bg-white"><h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-success"></i>Current Available Stock at Production Unit</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Product</th><th>SKU</th><th>Size / Color</th><th class="text-end">Available</th><th class="text-end">Selling Price</th></tr></thead>
            <tbody>
            <?php if (empty($unitStock)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No available stock in this unit.</td></tr>
            <?php else: foreach ($unitStock as $stock): ?>
                <tr><td><?= e($stock['product_name']) ?></td><td class="font-monospace"><?= e($stock['sku']) ?></td><td><?= e(trim(($stock['size_name'] ?? '') . ' ' . ($stock['color_name'] ?? '')) ?: '—') ?></td><td class="text-end fw-semibold"><?= number_format((float)$stock['quantity']) ?></td><td class="text-end">₹<?= number_format((float)$stock['selling_price'], 2) ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canReceive && $unitId): ?>
<script>
(() => {
    const locations = <?= $locationJson ?: '{}' ?>;
    const currentUnitId = <?= (int)$unitId ?>;
    const sourceType = document.getElementById('sourceType');
    const sourceId = document.getElementById('sourceId');
    const scanCode = document.getElementById('scanCode');
    const scanMessage = document.getElementById('scanMessage');
    const scannedItems = document.getElementById('scannedItems');
    const itemsJson = document.getElementById('itemsJson');
    const batchNumber = document.getElementById('batchNumber');
    const postReceipt = document.getElementById('postReceipt');
    const cart = new Map();
    let batchMode = false;

    function updateSources() {
        const type = sourceType.value;
        const options = locations[type] || [];
        sourceId.replaceChildren(new Option('Select source location', ''));
        options.filter(item => !(type === 'PRODUCTION_UNIT' && Number(item.id) === currentUnitId))
            .forEach(item => sourceId.add(new Option(`${item.name} (${item.code})`, item.id)));
    }

    function render() {
        if (cart.size === 0) {
            scannedItems.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No products scanned.</td></tr>';
            document.getElementById('totalScannedQty').textContent = '0';
            itemsJson.value = '[]';
            postReceipt.disabled = true;
            return;
        }
        let total = 0;
        const items = [...cart.values()];
        scannedItems.innerHTML = items.map(item => {
            total += Number(item.quantity);
            const displayName = item.product_name || 'Product';
            const available = item.available === undefined ? 'Batch' : Number(item.available).toLocaleString();
            return `<tr><td>${escapeHtml(displayName)}</td><td class="font-monospace">${escapeHtml(item.sku || '')}</td><td>${escapeHtml([item.size_name, item.color_name].filter(Boolean).join(' / ') || '—')}</td><td class="text-end fw-semibold">${Number(item.quantity).toLocaleString()}</td><td class="text-end">${available}</td></tr>`;
        }).join('');
        document.getElementById('totalScannedQty').textContent = total.toLocaleString();
        itemsJson.value = JSON.stringify(items.map(({variant_id, quantity, codes}) => ({variant_id, quantity, codes})));
        postReceipt.disabled = false;
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
    }

    async function lookup() {
        const code = scanCode.value.trim();
        if (!code) return;
        if (batchMode && cart.size > 0) {
            scanMessage.className = 'alert alert-warning py-2 small mb-3';
            scanMessage.textContent = 'Clear the current batch before scanning another code.';
            return;
        }
        if (!batchMode && cart.size > 0 && batchNumber.value) {
            scanMessage.className = 'alert alert-warning py-2 small mb-3';
            scanMessage.textContent = 'Clear the current batch before scanning individual products.';
            return;
        }
        if (cart.size > 0 && batchNumber.value) {
            scanMessage.className = 'alert alert-warning py-2 small mb-3';
            scanMessage.textContent = 'Clear the current batch before scanning individual products.';
            return;
        }
        const query = new URLSearchParams({source_type: sourceType.value, source_id: sourceId.value, code});
        try {
            const response = await fetch(`<?= url('api/production/inward/lookup') ?>?${query}`, {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'Code could not be matched.');
            const result = payload.result;
            if (result.type === 'batch') {
                if (cart.size > 0) throw new Error('Clear scanned products before loading a batch.');
                batchMode = true;
                batchNumber.value = result.batch_number;
                result.items.forEach(item => cart.set(Number(item.variant_id), {...item, codes: []}));
                scanMessage.className = 'alert alert-success py-2 small mb-3';
                scanMessage.textContent = `Batch ${result.batch_number} loaded. Verify the lines, then post the receipt.`;
            } else {
                if (batchMode) throw new Error('Clear the loaded batch before scanning individual products.');
                const item = result.variant;
                const key = Number(item.id);
                const current = cart.get(key) || {...item, variant_id: key, quantity: 0, codes: [], available: result.available};
                if (current.quantity >= Number(result.available)) throw new Error(`Only ${Number(result.available)} unit(s) are available at this source.`);
                current.quantity += 1;
                current.codes.push(code);
                cart.set(key, current);
                scanMessage.className = 'alert alert-success py-2 small mb-3';
                scanMessage.textContent = `${item.product_name} scanned. ${current.quantity} unit(s) added.`;
            }
            render();
        } catch (error) {
            scanMessage.className = 'alert alert-danger py-2 small mb-3';
            scanMessage.textContent = error.message;
        } finally {
            scanCode.value = '';
            scanCode.focus();
        }
    }

    sourceType.addEventListener('change', () => {
        updateSources();
        cart.clear();
        batchMode = false;
        batchNumber.value = '';
        render();
    });
    sourceId.addEventListener('change', () => {
        cart.clear();
        batchMode = false;
        batchNumber.value = '';
        render();
    });
    document.getElementById('lookupCode').addEventListener('click', lookup);
    scanCode.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            lookup();
        }
    });
    document.getElementById('clearScans').addEventListener('click', () => {
        cart.clear();
        batchMode = false;
        batchNumber.value = '';
        scanMessage.className = 'alert alert-info py-2 small mb-3';
        scanMessage.textContent = 'Scans cleared. Choose the source location, then scan products or a warehouse batch number.';
        render();
        scanCode.focus();
    });
    document.getElementById('productionInwardForm').addEventListener('submit', event => {
        if (cart.size === 0 || !sourceId.value) {
            event.preventDefault();
            scanMessage.className = 'alert alert-warning py-2 small mb-3';
            scanMessage.textContent = 'Select the source location and scan at least one product or batch.';
            return;
        }
        if (!confirm('Post this inward receipt and transfer the scanned stock into Production?')) event.preventDefault();
    });

    updateSources();
    render();
})();
</script>
<?php endif; ?>