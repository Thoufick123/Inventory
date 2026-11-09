<?php
$companyName = $sellerCompany['company_name'] ?? $sellerCompany['name'] ?? 'GRM Inventory';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Production Return <?= e($return['return_number']) ?></title>
    <link rel="stylesheet" href="<?= asset('css/bootstrap.min.css') ?>">
    <style>
        body { color:#17212b; font-size:14px; }
        .slip { max-width:900px; margin:24px auto; padding:28px; border:1px solid #d5dce2; }
        .mono { font-family:ui-monospace, SFMono-Regular, Consolas, monospace; }
        @media print { .slip { margin:0; max-width:none; border:0; padding:0; } .no-print { display:none !important; } }
    </style>
</head>
<body>
<div class="slip">
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
        <div><h2 class="h4 mb-1 fw-bold"><?= e($companyName) ?></h2><div class="text-muted">Production Billing Returns &amp; Exchanges</div></div>
        <div class="text-end"><div class="small text-muted">Return / Exchange No.</div><strong class="mono fs-5"><?= e($return['return_number']) ?></strong><div class="small"><?= e(formatDate($return['created_at'])) ?></div></div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-6"><div class="small text-muted">Original Production Bill</div><strong class="mono"><?= e($return['bill_number']) ?></strong><div class="small text-muted">Bill date: <?= e(formatDate($return['bill_date'])) ?></div></div>
        <div class="col-6 text-end"><div class="small text-muted">Customer</div><strong><?= e($return['customer_name'] ?: $return['company_name'] ?: 'Production Customer') ?></strong><div class="small"><?= e($return['customer_phone'] ?? '') ?></div></div>
    </div>
    <table class="table table-sm table-bordered align-middle">
        <thead class="table-light"><tr><th>Product / Variant</th><th>Retired QR</th><th>Condition</th><th>Replacement</th><th>New Inventory QR</th><th class="text-end">Original Value</th></tr></thead>
        <tbody><?php foreach ($returnItems as $item): ?><tr><td><?= e($item['original_product_name']) ?><small class="d-block text-muted"><?= e($item['original_sku'] ?? '') ?> · <?= e(trim(($item['original_size_name'] ?? '') . ' ' . ($item['original_color_name'] ?? ''))) ?></small></td><td class="mono text-muted"><?= e($item['original_tag_code']) ?></td><td><?= e($item['item_condition']) ?></td><td><?php if (!empty($item['replacement_tag_code'])): ?><?= e($item['replacement_product_name']) ?><small class="d-block mono"><?= e($item['replacement_tag_code']) ?></small><?php else: ?>—<?php endif; ?></td><td class="mono fw-bold"><?= e($item['returned_inventory_tag'] ?? ($item['item_condition'] === 'GOOD' ? 'Sticker pending' : 'Not restocked')) ?></td><td class="text-end">₹<?= number_format((float)$item['original_unit_price'], 2) ?></td></tr><?php endforeach; ?></tbody>
    </table>
    <div class="row justify-content-end"><div class="col-md-5">
        <div class="d-flex justify-content-between py-1"><span>Original value</span><strong>₹<?= number_format((float)$return['refund_amount'], 2) ?></strong></div>
        <div class="d-flex justify-content-between py-1"><span>Replacement value</span><strong>₹<?= number_format((float)$return['new_amount'], 2) ?></strong></div>
        <div class="d-flex justify-content-between border-top mt-2 pt-2"><span class="fw-bold"><?= e($return['payment_action'] === 'NONE' ? 'Settlement' : $return['payment_action']) ?> · <?= e($return['settlement_mode']) ?></span><strong>₹<?= number_format((float)$return['difference_amount'], 2) ?></strong></div>
    </div></div>
    <div class="mt-4"><div class="small text-muted">Reason</div><?= e($return['reason']) ?></div>
    <?php if (!empty($return['notes'])): ?><div class="mt-2"><div class="small text-muted">Notes</div><?= e($return['notes']) ?></div><?php endif; ?>
    <div class="d-flex justify-content-between mt-5 pt-3 border-top small text-muted"><span>Processed by <?= e($return['processed_by_name'] ?? 'Staff') ?></span><span>Computer generated return slip</span></div>
    <div class="no-print text-end mt-4"><button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>
</body>
</html>
