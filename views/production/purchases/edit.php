<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div><h2 class="mb-1"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit <?= e($purchase['purchase_number']) ?></h2><span class="text-muted">Edit purchased items and tax details. Fabric RQ and QR ranges remain unchanged.</span></div>
    <a href="<?= url('production/purchases/view/' . $purchase['id']) ?>" class="btn btn-outline-secondary"><i class="bi bi-eye me-1"></i>View Purchase</a>
</div>

<form method="POST" action="<?= url('production/purchases/update/' . $purchase['id']) ?>" id="editProductionPurchaseForm">
    <?= CSRF::field() ?>
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Purchase Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Supplier</label><input class="form-control" value="<?= e($purchase['seller_company'] ?: $purchase['seller_name']) ?><?= $purchase['seller_company'] ? ' — ' . e($purchase['seller_name']) : '' ?>" disabled></div>
                <div class="col-md-3"><label class="form-label required">Purchase Date *</label><input type="date" name="purchase_date" class="form-control" value="<?= e($purchase['purchase_date']) ?>" required></div>
                <div class="col-md-5"><label class="form-label">Invoice / Bill Number</label><input name="invoice_number" class="form-control" maxlength="100" value="<?= e($purchase['invoice_number']) ?>"></div>
                <div class="col-12"><label class="form-label">Notes</label><input name="notes" class="form-control" maxlength="500" value="<?= e($purchase['notes']) ?>"></div>
            </div>
        </div>
    </div>

    <?php foreach ($items as $item): ?>
        <?php
            $itemKey = (int)$item['id'];
            $gstType = in_array($item['gst_type'] ?? '', ['SGST', 'CGST', 'IGST', 'BOTH', 'EXEMPTED'], true) ? $item['gst_type'] : 'EXEMPTED';
            if (($item['gst_type'] ?? '') === 'LEGACY') {
                if ((float)$item['sgst_amount'] > 0 && (float)$item['cgst_amount'] > 0) $gstType = 'BOTH';
                elseif ((float)$item['igst_amount'] > 0) $gstType = 'IGST';
                elseif ((float)$item['sgst_amount'] > 0) $gstType = 'SGST';
                elseif ((float)$item['cgst_amount'] > 0) $gstType = 'CGST';
            }
            $taxValue = $gstType === 'SGST' ? $item['sgst_value']
                : ($gstType === 'IGST' ? $item['igst_value']
                    : ($gstType === 'CGST' ? $item['cgst_value'] : $item['sgst_value']));
            $cgstValue = $gstType === 'BOTH' ? $item['cgst_value'] : 0;
        ?>
        <div class="card border shadow-sm mb-3 purchase-item-row" data-item-type="<?= e($item['item_type']) ?>">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong><?= $item['item_type'] === 'FABRIC_ROLL' ? 'Fabric Roll' : 'Other Garment Supply' ?> · <?= e($item['item_name']) ?></strong>
                <?php if ($item['item_type'] === 'GENERAL_SUPPLY'): ?>
                    <label class="form-check text-danger mb-0"><input type="checkbox" class="form-check-input remove-item" name="items[<?= $itemKey ?>][remove_item]" value="1"> Remove item</label>
                <?php else: ?>
                    <span class="badge bg-info-subtle text-info">RQ / QR range fixed</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <input type="hidden" name="items[<?= $itemKey ?>][id]" value="<?= $itemKey ?>">
                <input type="hidden" name="items[<?= $itemKey ?>][item_type]" value="<?= e($item['item_type']) ?>">
                <div class="row g-3 align-items-end">
                    <?php if ($item['item_type'] === 'FABRIC_ROLL'): ?>
                        <div class="col-md-3"><label class="form-label">Fabric Description</label><input name="items[<?= $itemKey ?>][item_name]" class="form-control" maxlength="255" value="<?= e($item['item_name']) ?>" required></div>
                        <div class="col-md-2"><label class="form-label">Roll Number</label><input name="items[<?= $itemKey ?>][roll_number]" class="form-control" value="<?= e($item['roll_number']) ?>" required></div>
                        <div class="col-md-2"><label class="form-label">Color</label><input name="items[<?= $itemKey ?>][color]" class="form-control" value="<?= e($item['color']) ?>" required></div>
                        <div class="col-md-2"><label class="form-label">Design</label><input name="items[<?= $itemKey ?>][design_name]" class="form-control" value="<?= e($item['design_name']) ?>" required></div>
                        <div class="col-md-1"><label class="form-label">Designs</label><input type="number" min="1" name="items[<?= $itemKey ?>][design_count]" class="form-control" value="<?= (int)$item['design_count'] ?>" required></div>
                        <div class="col-md-1"><label class="form-label">RQ</label><input class="form-control" value="<?= number_format((float)$item['quantity'], 0) ?>" disabled></div>
                        <input type="hidden" name="items[<?= $itemKey ?>][quantity]" value="<?= e($item['quantity']) ?>">
                        <div class="col-md-1"><label class="form-label">Weight kg</label><input type="number" min="0.01" step="0.01" name="items[<?= $itemKey ?>][weight_kg]" class="form-control" value="<?= e($item['weight_kg']) ?>" required></div>
                    <?php else: ?>
                        <div class="col-md-5"><label class="form-label">Item Name / Description</label><input name="items[<?= $itemKey ?>][item_name]" class="form-control" maxlength="255" value="<?= e($item['item_name']) ?>" required></div>
                        <div class="col-md-2"><label class="form-label">Quantity</label><input type="number" min="0.001" step="0.001" name="items[<?= $itemKey ?>][quantity]" class="form-control" value="<?= e($item['quantity']) ?>" required></div>
                        <div class="col-md-2"><label class="form-label">Unit</label><select name="items[<?= $itemKey ?>][unit]" class="form-select edit-supply-unit" required>
                            <?php foreach (['PCS'=>'Pieces','KG'=>'Kilograms','METRE'=>'Metres','LITRE'=>'Litres','BOX'=>'Boxes','PACK'=>'Packs','PAIR'=>'Pairs','SET'=>'Sets','BUNDLE'=>'Bundles'] as $unit => $label): ?>
                                <option value="<?= e($unit) ?>" <?= $item['unit'] === $unit ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                            <?php if (!in_array($item['unit'], ['PCS','KG','METRE','LITRE','BOX','PACK','PAIR','SET','BUNDLE'], true)): ?><option value="OTHER" selected>Other</option><?php endif; ?>
                        </select></div>
                        <?php if (!in_array($item['unit'], ['PCS','KG','METRE','LITRE','BOX','PACK','PAIR','SET','BUNDLE'], true)): ?><div class="col-md-2"><label class="form-label">Unit Name</label><input name="items[<?= $itemKey ?>][custom_unit]" class="form-control" value="<?= e($item['unit']) ?>" required></div><?php endif; ?>
                    <?php endif; ?>
                    <div class="col-md-2"><label class="form-label">Price (₹)</label><input type="number" min="0" step="0.01" name="items[<?= $itemKey ?>][unit_price]" class="form-control" value="<?= e($item['unit_price']) ?>" required></div>
                    <?php if ($item['item_type'] === 'GENERAL_SUPPLY'): ?><input type="hidden" name="items[<?= $itemKey ?>][weight_kg]" value=""><input type="hidden" name="items[<?= $itemKey ?>][roll_number]" value=""><input type="hidden" name="items[<?= $itemKey ?>][color]" value=""><input type="hidden" name="items[<?= $itemKey ?>][design_name]" value=""><input type="hidden" name="items[<?= $itemKey ?>][design_count]" value=""><?php endif; ?>
                    <div class="col-md-3"><label class="form-label">GST</label><select name="items[<?= $itemKey ?>][gst_type]" class="form-select edit-gst-type">
                        <?php foreach (['SGST'=>'SGST','IGST'=>'IGST','CGST'=>'CGST','BOTH'=>'Both (CGST and SGST)','EXEMPTED'=>'Exempted'] as $type => $label): ?><option value="<?= e($type) ?>" <?= $gstType === $type ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="col-md-3 edit-tax-mode"><label class="form-label">Tax Input Type</label><select name="items[<?= $itemKey ?>][tax_mode]" class="form-select edit-tax-mode-input"><option value="PERCENT" <?= ($item['tax_input_type'] ?? '') === 'PERCENT' ? 'selected' : '' ?>>Percentage (%)</option><option value="AMOUNT" <?= ($item['tax_input_type'] ?? '') === 'AMOUNT' ? 'selected' : '' ?>>Amount (₹)</option></select></div>
                    <div class="col-md-3 edit-tax-primary"><label class="form-label edit-tax-primary-label">Tax</label><input type="number" min="0" step="0.01" name="items[<?= $itemKey ?>][tax_value]" class="form-control edit-tax-primary-input" value="<?= e($taxValue) ?>"></div>
                    <div class="col-md-3 edit-tax-cgst"><label class="form-label edit-tax-cgst-label">CGST</label><input type="number" min="0" step="0.01" name="items[<?= $itemKey ?>][cgst_tax_value]" class="form-control" value="<?= e($cgstValue) ?>"></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="card border shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between flex-wrap gap-2">
            <a href="<?= url('production/purchases/view/' . $purchase['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Purchase Changes</button>
        </div>
    </div>
</form>

<script>
document.querySelectorAll('.purchase-item-row').forEach(row => {
    const type = row.querySelector('.edit-gst-type');
    const mode = row.querySelector('.edit-tax-mode-input');
    const primary = row.querySelector('.edit-tax-primary');
    const secondary = row.querySelector('.edit-tax-cgst');
    const value = row.querySelector('.edit-tax-primary-input');
    function updateTaxFields() {
        const active = type.value !== 'EXEMPTED';
        row.querySelector('.edit-tax-mode').classList.toggle('d-none', !active);
        primary.classList.toggle('d-none', !active);
        secondary.classList.toggle('d-none', type.value !== 'BOTH');
        value.required = active;
        row.querySelector('.edit-tax-primary-label').textContent = `${type.value === 'BOTH' ? 'SGST' : type.value} (${mode.value === 'PERCENT' ? '%' : '₹'})`;
        row.querySelector('.edit-tax-cgst-label').textContent = `CGST (${mode.value === 'PERCENT' ? '%' : '₹'})`;
        [value, secondary.querySelector('input')].forEach(input => {
            if (mode.value === 'PERCENT') input.max = '100';
            else input.removeAttribute('max');
        });
        secondary.querySelector('input').required = type.value === 'BOTH';
    }
    type.addEventListener('change', updateTaxFields);
    mode.addEventListener('change', updateTaxFields);
    updateTaxFields();
    const remove = row.querySelector('.remove-item');
    if (remove) remove.addEventListener('change', () => row.querySelectorAll('input, select').forEach(input => {
        if (input !== remove && !['id', 'item_type'].some(field => input.name.endsWith(`[${field}]`))) {
            input.disabled = remove.checked;
        }
    }));
});
</script>
