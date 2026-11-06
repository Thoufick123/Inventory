<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2><i class="bi bi-bag-plus me-2 text-primary"></i>Record Production Purchase</h2>
        <span class="text-muted">Select a registered supplier, then add fabric rolls and/or general garment supplies.</span>
    </div>
    <a href="<?= url('production/purchases') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Purchases</a>
</div>

<form method="POST" action="<?= url('production/purchases/store') ?>" enctype="multipart/form-data" id="productionPurchaseForm">
    <?= CSRF::field() ?>
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white"><h5 class="mb-0 fw-bold">Purchase Details</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label required" for="purchaseSeller">Supplier *</label>
                    <select class="form-select" name="seller_id" id="purchaseSeller" required>
                        <option value="">-- Select supplier --</option>
                        <?php foreach ($sellers as $seller): ?>
                            <option value="<?= (int)$seller['id'] ?>"><?= e($seller['company'] ?: $seller['name']) ?><?= $seller['company'] ? ' — ' . e($seller['name']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
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
                    <label class="form-label" for="purchaseDocument">Invoice / Bill attachment (optional)</label>
                    <input type="file" class="form-control" name="document" id="purchaseDocument" accept=".jpg,.jpeg,.png,.webp,.pdf">
                    <small class="text-muted">JPG, PNG, WEBP or PDF up to 5 MB.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="purchaseNotes">Notes</label>
                    <input type="text" class="form-control" name="notes" id="purchaseNotes" maxlength="500">
                </div>
            </div>
        </div>
    </div>

    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><h5 class="mb-0 fw-bold">Purchased Items</h5><small class="text-muted">Add each roll or non-fabric supply as a separate line.</small></div>
            <button type="button" class="btn btn-sm btn-primary" id="addPurchaseItem"><i class="bi bi-plus-circle me-1"></i>Add Item</button>
        </div>
        <div class="card-body" id="purchaseItems"></div>
        <div class="card-footer bg-light d-flex justify-content-between"><strong>Purchase Total</strong><strong id="purchaseTotal">₹0.00</strong></div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="<?= url('production/purchases') ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Purchase</button>
    </div>
</form>

<template id="purchaseItemTemplate">
    <div class="purchase-item border rounded p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong class="purchase-item-title">Item</strong>
            <button type="button" class="btn btn-sm btn-outline-danger remove-purchase-item"><i class="bi bi-trash me-1"></i>Remove</button>
        </div>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Item Type *</label>
                <select class="form-select item-type" required>
                    <option value="GENERAL_SUPPLY">General Supply</option>
                    <option value="FABRIC_ROLL">Fabric Roll</option>
                </select>
            </div>
            <div class="col-md-5 general-field">
                <label class="form-label">Item Name / Description *</label>
                <input type="text" class="form-control item-name" maxlength="255" placeholder="e.g. Sewing needles, thread, buttons" required>
            </div>
            <div class="col-md-4 fabric-field d-none">
                <label class="form-label">Fabric Description *</label>
                <input type="text" class="form-control fabric-description" maxlength="150" placeholder="e.g. Cotton jersey 180 GSM">
            </div>
            <div class="col-md-4 fabric-field d-none">
                <label class="form-label">Roll Number / Code *</label>
                <input type="text" class="form-control roll-number text-uppercase" maxlength="50">
            </div>
            <div class="col-md-4 fabric-field d-none">
                <label class="form-label">Color / Shade *</label>
                <input type="text" class="form-control roll-color text-uppercase" maxlength="100">
            </div>
            <div class="col-md-4 fabric-field d-none">
                <label class="form-label">Design / Pattern *</label>
                <input type="text" class="form-control roll-design text-uppercase" maxlength="150">
            </div>
            <div class="col-md-2 fabric-field d-none">
                <label class="form-label">Designs *</label>
                <input type="number" class="form-control roll-design-count" min="1" step="1" value="1">
            </div>
            <div class="col-md-2">
                <label class="form-label quantity-label">Quantity *</label>
                <input type="number" class="form-control item-quantity" min="0.001" step="0.001" value="1" required>
            </div>
            <div class="col-md-3 general-field">
                <label class="form-label">Unit *</label>
                <select class="form-select item-unit" required>
                    <option value="PCS">Pieces</option><option value="KG">Kilograms</option><option value="METRE">Metres</option>
                    <option value="LITRE">Litres</option><option value="BOX">Boxes</option><option value="PACK">Packs</option>
                    <option value="PAIR">Pairs</option><option value="SET">Sets</option><option value="BUNDLE">Bundles</option>
                    <option value="OTHER">Other (type unit)</option>
                </select>
            </div>
            <div class="col-md-3 other-unit-field d-none">
                <label class="form-label">Unit Name *</label><input type="text" class="form-control other-unit" maxlength="30">
            </div>
            <div class="col-md-3 fabric-field d-none">
                <label class="form-label">Weight (kg) *</label><input type="number" class="form-control roll-weight" min="0.01" step="0.01">
            </div>
            <div class="col-md-3">
                <label class="form-label unit-price-label">Unit Price (₹) *</label>
                <input type="number" class="form-control item-unit-price" min="0" step="0.01" value="0" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Line Total (₹)</label>
                <input type="text" class="form-control item-line-total" readonly value="₹0.00">
            </div>
        </div>
    </div>
</template>

<script>
(() => {
    const form = document.getElementById('productionPurchaseForm');
    const container = document.getElementById('purchaseItems');
    const template = document.getElementById('purchaseItemTemplate');
    let nextItemIndex = 0;

    function updateRow(row) {
        const fabric = row.querySelector('.item-type').value === 'FABRIC_ROLL';
        row.querySelectorAll('.fabric-field').forEach(field => field.classList.toggle('d-none', !fabric));
        row.querySelectorAll('.general-field').forEach(field => field.classList.toggle('d-none', fabric));
        row.querySelector('.item-name').required = !fabric;
        row.querySelector('.fabric-description').required = fabric;
        row.querySelector('.roll-number').required = fabric;
        row.querySelector('.roll-color').required = fabric;
        row.querySelector('.roll-design').required = fabric;
        row.querySelector('.roll-design-count').required = fabric;
        row.querySelector('.roll-weight').required = fabric;
        row.querySelector('.item-unit').required = !fabric;
        row.querySelector('.quantity-label').textContent = fabric ? 'RQ (Roll Quantity) *' : 'Quantity *';
        row.querySelector('.item-quantity').min = fabric ? '1' : '0.001';
        row.querySelector('.item-quantity').step = fabric ? '1' : '0.001';
        row.querySelector('.unit-price-label').textContent = fabric ? 'Roll Price (₹) *' : 'Unit Price (₹) *';
        const custom = !fabric && row.querySelector('.item-unit').value === 'OTHER';
        row.querySelector('.other-unit-field').classList.toggle('d-none', !custom);
        row.querySelector('.other-unit').required = custom;
        const qty = Number(row.querySelector('.item-quantity').value) || 0;
        const unitPrice = Number(row.querySelector('.item-unit-price').value) || 0;
        row.querySelector('.item-line-total').value = '₹' + (fabric ? unitPrice : qty * unitPrice).toFixed(2);
        row.querySelector('.purchase-item-title').textContent = 'Item ' + (Array.from(container.children).indexOf(row) + 1);
        updateTotal();
    }

    function updateTotal() {
        const total = Array.from(container.querySelectorAll('.item-line-total')).reduce((sum, input) => sum + (Number(input.value.replace(/[^\d.]/g, '')) || 0), 0);
        document.getElementById('purchaseTotal').textContent = '₹' + total.toFixed(2);
    }

    function addRow() {
        const row = template.content.firstElementChild.cloneNode(true);
        const index = nextItemIndex++;
        const names = {
            '.item-type': 'item_type', '.item-name': 'item_name', '.fabric-description': 'fabric_description',
            '.roll-number': 'roll_number', '.roll-color': 'color', '.roll-design': 'design_name',
            '.roll-design-count': 'design_count', '.item-quantity': 'quantity', '.item-unit': 'unit',
            '.other-unit': 'custom_unit', '.roll-weight': 'weight_kg', '.item-unit-price': 'unit_price'
        };
        Object.entries(names).forEach(([selector, field]) => row.querySelector(selector).name = `items[${index}][${field}]`);
        row.querySelector('.item-type').addEventListener('change', () => updateRow(row));
        row.querySelector('.item-unit').addEventListener('change', () => updateRow(row));
        row.querySelectorAll('.item-quantity, .item-unit-price').forEach(input => input.addEventListener('input', () => updateRow(row)));
        row.querySelector('.remove-purchase-item').addEventListener('click', () => {
            if (container.children.length <= 1) return alert('A purchase must contain at least one item.');
            row.remove();
            Array.from(container.children).forEach(updateRow);
        });
        container.appendChild(row);
        updateRow(row);
    }

    document.getElementById('addPurchaseItem').addEventListener('click', addRow);
    form.addEventListener('submit', event => {
        if (!container.children.length) {
            event.preventDefault();
            alert('Add at least one purchase item.');
        }
    });
    addRow();
})();
</script>
