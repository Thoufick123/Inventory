<?php $returnedInventoryItems = array_values(array_filter($returnItems, static fn(array $item): bool => !empty($item['returned_inventory_tag']))); ?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div><h2 class="mb-1 fw-bold">Production <?= e(ucfirst(strtolower($return['action_type']))) ?> Details</h2><span class="font-monospace text-muted"><?= e($return['return_number']) ?></span></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?= url('production/billing/returns') ?>"><i class="bi bi-arrow-left me-1"></i>Return Ledger</a><?php if ($returnedInventoryItems): ?><button class="btn btn-success" type="button" onclick="window.print()"><i class="bi bi-qr-code me-1"></i>Print New Stickers</button><?php endif; ?><a class="btn btn-primary" href="<?= url('production/billing/returns/print/' . $return['id']) ?>" target="_blank" rel="noopener"><i class="bi bi-printer me-1"></i>Print Slip</a></div>
</div>
<div class="row g-3 mb-4">
    <div class="col-lg-7"><div class="card border shadow-sm h-100"><div class="card-header bg-white fw-bold">Original Bill &amp; Customer</div><div class="card-body"><div class="row g-3"><div class="col-sm-6"><small class="text-muted d-block">Production Bill</small><span class="font-monospace fw-semibold"><?= e($return['bill_number']) ?></span></div><div class="col-sm-6"><small class="text-muted d-block">Bill Date</small><?= e(formatDate($return['bill_date'])) ?></div><div class="col-sm-6"><small class="text-muted d-block">Customer</small><?= e($return['customer_name'] ?: $return['company_name'] ?: 'Production Customer') ?></div><div class="col-sm-6"><small class="text-muted d-block">Phone</small><?= e($return['customer_phone'] ?? '') ?></div><div class="col-sm-6"><small class="text-muted d-block">Processed By</small><?= e($return['processed_by_name'] ?? 'Staff') ?></div><div class="col-sm-6"><small class="text-muted d-block">Processed At</small><?= e(formatDate($return['created_at'])) ?></div><div class="col-12"><small class="text-muted d-block">Reason</small><?= e($return['reason']) ?></div></div></div></div></div>
    <div class="col-lg-5"><div class="card border shadow-sm h-100"><div class="card-header bg-white fw-bold">Settlement</div><div class="card-body"><div class="d-flex justify-content-between mb-2"><span>Original value</span><strong>₹<?= number_format((float)$return['refund_amount'], 2) ?></strong></div><div class="d-flex justify-content-between mb-2"><span>Replacement value</span><strong>₹<?= number_format((float)$return['new_amount'], 2) ?></strong></div><hr><div class="d-flex justify-content-between"><span><?= e($return['payment_action']) ?></span><strong><?= e($return['payment_action'] === 'NONE' ? 'Even settlement' : $return['payment_action']) ?> · <?= e($return['settlement_mode']) ?> ₹<?= number_format((float)$return['difference_amount'], 2) ?></strong></div><?php if (!empty($return['notes'])): ?><div class="small text-muted mt-3"><?= nl2br(e($return['notes'])) ?></div><?php endif; ?></div></div></div>
</div>
<div class="card border shadow-sm"><div class="card-header bg-white fw-bold">Returned and Replacement QR Items</div><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Original Product</th><th>Retired QR</th><th>Condition</th><th>Replacement Product</th><th>Replacement QR</th><th>New Inventory QR</th><th class="text-end">Value</th></tr></thead><tbody><?php foreach ($returnItems as $item): ?><tr><td><?= e($item['original_product_name']) ?><small class="d-block text-muted"><?= e($item['original_sku'] ?? '') ?> · <?= e(trim(($item['original_size_name'] ?? '') . ' ' . ($item['original_color_name'] ?? ''))) ?></small></td><td class="font-monospace text-muted text-decoration-line-through"><?= e($item['original_tag_code']) ?></td><td><span class="badge <?= $item['item_condition'] === 'GOOD' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' ?>"><?= e($item['item_condition']) ?></span></td><td><?= e($item['replacement_product_name'] ?? '—') ?><?php if (!empty($item['replacement_sku'])): ?><small class="d-block text-muted"><?= e($item['replacement_sku']) ?> · <?= e(trim(($item['replacement_size_name'] ?? '') . ' ' . ($item['replacement_color_name'] ?? ''))) ?></small><?php endif; ?></td><td class="font-monospace"><?= e($item['replacement_tag_code'] ?? '—') ?></td><td class="font-monospace fw-bold text-success"><?= e($item['returned_inventory_tag'] ?? ($item['item_condition'] === 'GOOD' ? 'Sticker pending' : 'Not restocked')) ?></td><td class="text-end">₹<?= number_format((float)$item['original_unit_price'], 2) ?><?php if ((float)$item['replacement_unit_price'] > 0): ?><small class="d-block text-success">New ₹<?= number_format((float)$item['replacement_unit_price'], 2) ?></small><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div>

<?php if ($returnedInventoryItems): ?>
<section id="returnedInventoryLabels" class="mt-4 d-flex flex-wrap gap-3">
    <?php foreach ($returnedInventoryItems as $item): ?>
        <article class="returned-inventory-label border rounded p-3 text-center bg-white" style="width: 190px;">
            <div class="returned-qr mb-2" data-qr="<?= e($item['returned_inventory_tag']) ?>"></div>
            <div class="font-monospace fw-bold"><?= e($item['returned_inventory_tag']) ?></div>
            <div class="small fw-semibold"><?= e($item['original_product_name']) ?></div>
            <div class="small text-muted"><?= e($item['original_sku'] ?? '') ?> · <?= e(trim(($item['original_size_name'] ?? '') . ' ' . ($item['original_color_name'] ?? ''))) ?></div>
            <small class="text-success">Returned stock</small>
        </article>
    <?php endforeach; ?>
</section>
<script src="<?= asset('js/qrcode.min.js') ?>"></script>
<script>
document.querySelectorAll('.returned-qr').forEach(element => new QRCode(element, {text: element.dataset.qr, width: 112, height: 112, correctLevel: QRCode.CorrectLevel.M}));
</script>
<style>
@media print {
    body * { visibility: hidden !important; }
    #returnedInventoryLabels, #returnedInventoryLabels * { visibility: visible !important; }
    #returnedInventoryLabels { position: absolute; inset: 0 auto auto 0; display: flex !important; flex-wrap: wrap; gap: 4mm; margin: 0 !important; }
    .returned-inventory-label { width: 45mm !important; min-height: 38mm; break-inside: avoid; border: 1px solid #222 !important; }
}
</style>
<?php endif; ?>
