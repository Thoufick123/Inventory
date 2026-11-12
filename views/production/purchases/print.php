<?php
$currency = $company['currency_symbol'] ?? '₹';
$companyName = $company['company_name'] ?? '';
$taxLabels = ['SGST' => 'SGST', 'CGST' => 'CGST', 'IGST' => 'IGST'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Report <?= e($purchase['purchase_number']) ?></title>
    <style>
        *{box-sizing:border-box}body{font:14px Arial,sans-serif;color:#1f2937;margin:28px}.toolbar{display:flex;justify-content:flex-end;margin-bottom:16px}.toolbar button{padding:9px 16px;border:0;border-radius:4px;background:#1d4ed8;color:#fff;font-size:14px;cursor:pointer}.report{max-width:1000px;margin:auto}.header{display:flex;justify-content:space-between;gap:24px;border-bottom:2px solid #1f2937;padding-bottom:18px;margin-bottom:22px}.company h1{font-size:23px;margin:0 0 8px}.company p,.meta p,.party p{margin:4px 0;white-space:pre-line}.title{text-align:right}.title h2{margin:0 0 8px;font-size:21px}.parties{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0}.party{border:1px solid #d1d5db;border-radius:5px;padding:14px}.party h3{margin:0 0 10px;font-size:14px;text-transform:uppercase;color:#4b5563}.meta{margin:10px 0 20px}.meta strong{display:inline-block;min-width:120px}.items{width:100%;border-collapse:collapse;margin:16px 0}.items th,.items td{border:1px solid #d1d5db;padding:9px;text-align:left;vertical-align:top}.items th{background:#f3f4f6}.num{text-align:right!important;white-space:nowrap}.totals{width:360px;max-width:100%;margin:16px 0 0 auto;border-collapse:collapse}.totals td{padding:7px 10px;border-bottom:1px solid #e5e7eb}.grand-total{font-weight:bold;font-size:17px;border-top:2px solid #1f2937}.notes{margin-top:24px;border-top:1px solid #d1d5db;padding-top:12px}.footer{margin-top:36px;text-align:center;color:#6b7280;font-size:12px}@media print{body{margin:12mm}.toolbar{display:none}.report{max-width:none}thead{display:table-header-group}tr{break-inside:avoid}}
    </style>
</head>
<body>
<div class="toolbar"><button type="button" onclick="window.print()"><i>Print / Save PDF</i></button></div>
<main class="report">
    <header class="header">
        <div class="company">
            <h1><?= e($companyName ?: 'Company') ?></h1>
            <?php if (!empty($company['company_tagline'])): ?><p><?= e($company['company_tagline']) ?></p><?php endif; ?>
            <?php if (!empty($company['company_address'])): ?><p><?= e($company['company_address']) ?></p><?php endif; ?>
            <?php if (!empty($company['company_phone'])): ?><p>Phone: <?= e($company['company_phone']) ?></p><?php endif; ?>
            <?php if (!empty($company['company_email'])): ?><p>Email: <?= e($company['company_email']) ?></p><?php endif; ?>
            <?php if (!empty($company['company_gstin'])): ?><p>GSTIN: <?= e($company['company_gstin']) ?></p><?php endif; ?>
        </div>
        <div class="title">
            <h2>Purchase Report</h2>
            <p><strong><?= e($purchase['purchase_number']) ?></strong></p>
        </div>
    </header>

    <div class="parties">
        <section class="party">
            <h3>Our Company</h3>
            <p><strong><?= e($companyName ?: '—') ?></strong></p>
            <?php if (!empty($company['company_address'])): ?><p><?= e($company['company_address']) ?></p><?php endif; ?>
            <?php if (!empty($company['company_phone'])): ?><p>Phone: <?= e($company['company_phone']) ?></p><?php endif; ?>
            <?php if (!empty($company['company_email'])): ?><p>Email: <?= e($company['company_email']) ?></p><?php endif; ?>
            <?php if (!empty($company['company_gstin'])): ?><p>GSTIN: <?= e($company['company_gstin']) ?></p><?php endif; ?>
        </section>
        <section class="party">
            <h3>Purchased From</h3>
            <p><strong><?= e($purchase['seller_company'] ?: $purchase['seller_name']) ?></strong></p>
            <?php if (!empty($purchase['seller_company'])): ?><p>Contact: <?= e($purchase['seller_name']) ?></p><?php endif; ?>
            <?php if (!empty($purchase['seller_address'])): ?><p><?= e($purchase['seller_address']) ?></p><?php endif; ?>
            <?php if (!empty($purchase['seller_city']) || !empty($purchase['seller_state'])): ?><p><?= e(trim(($purchase['seller_city'] ?? '') . ', ' . ($purchase['seller_state'] ?? ''), ', ')) ?></p><?php endif; ?>
            <?php if (!empty($purchase['seller_phone'])): ?><p>Phone: <?= e($purchase['seller_phone']) ?></p><?php endif; ?>
            <?php if (!empty($purchase['seller_email'])): ?><p>Email: <?= e($purchase['seller_email']) ?></p><?php endif; ?>
            <?php if (!empty($purchase['seller_gst_number'])): ?><p>GSTIN: <?= e($purchase['seller_gst_number']) ?></p><?php endif; ?>
        </section>
    </div>

    <div class="meta">
        <p><strong>Purchase Date:</strong> <?= e(formatDate($purchase['purchase_date'])) ?></p>
        <p><strong>Invoice / Bill No.:</strong> <?= e($purchase['invoice_number'] ?: '—') ?></p>
        <?php if (!empty($purchase['source_batch_number'])): ?>
            <p><strong>Inter-Concern Batch:</strong> <?= e($purchase['source_batch_number']) ?><?= !empty($purchase['source_sale_bill_number']) ? ' (Sale Bill ' . e($purchase['source_sale_bill_number']) . ')' : '' ?></p>
        <?php endif; ?>
    </div>

    <table class="items">
        <thead><tr><th>#</th><th>Purchased Item</th><th>Quantity</th><th class="num">Unit Price</th><th class="num">Subtotal</th><th>GST Type</th><th class="num">Tax</th><th class="num">Total</th></tr></thead>
        <tbody>
        <?php foreach ($items as $index => $item): ?>
            <?php
                $taxType = $item['gst_type'] ?? 'LEGACY';
                $itemTax = (float)($item['sgst_amount'] ?? 0) + (float)($item['cgst_amount'] ?? 0) + (float)($item['igst_amount'] ?? 0);
                if ($item['item_type'] === 'FABRIC_ROLL') {
                    $description = trim(($item['item_name'] ?? '') . ' — ' . ($item['roll_number'] ?? '') . ' — ' . ($item['color'] ?? '') . ' / ' . ($item['design_name'] ?? ''), ' —/');
                    $typeLabel = 'Fabric Roll';
                } elseif ($item['item_type'] === 'FINISHED_GOODS') {
                    $variantPart = trim(($item['size_name'] ?? '') . ' / ' . ($item['color_name'] ?? ''), ' /');
                    $description = trim(($item['item_name'] ?? '') . (!empty($item['sku']) ? ' (' . $item['sku'] . ')' : '') . ($variantPart !== '' ? ' — ' . $variantPart : ''));
                    $typeLabel = 'Finished Goods';
                } else {
                    $description = $item['item_name'] ?? '';
                    $typeLabel = 'Other Garment Supply';
                }
                if ($taxType === 'LEGACY') $taxType = $itemTax > 0 ? 'SGST / CGST' : 'Exempted';
                if ($taxType === 'BOTH') $taxType = 'SGST + CGST';
            ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= e($description) ?><br><small><?= $typeLabel ?></small></td>
                <td><?= number_format((float)$item['quantity'], 3) ?> <?= e($item['unit']) ?><?= !empty($item['weight_kg']) ? ' · ' . number_format((float)$item['weight_kg'], 2) . ' kg' : '' ?></td>
                <td class="num"><?= e($currency) ?><?= number_format((float)$item['unit_price'], 2) ?></td>
                <td class="num"><?= e($currency) ?><?= number_format((float)$item['base_amount'], 2) ?></td>
                <td><?= e($taxLabels[$taxType] ?? $taxType) ?></td>
                <td class="num"><?= e($currency) ?><?= number_format($itemTax, 2) ?></td>
                <td class="num"><?= e($currency) ?><?= number_format((float)$item['total_price'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num"><?= e($currency) ?><?= number_format((float)$subtotal, 2) ?></td></tr>
        <?php foreach ($taxes as $type => $amount): if ($amount > 0): ?>
            <tr><td><?= e($type) ?></td><td class="num"><?= e($currency) ?><?= number_format((float)$amount, 2) ?></td></tr>
        <?php endif; endforeach; ?>
        <tr><td>Total Taxes</td><td class="num"><?= e($currency) ?><?= number_format((float)$totalTax, 2) ?></td></tr>
        <tr class="grand-total"><td>Overall Total</td><td class="num"><?= e($currency) ?><?= number_format((float)$overallTotal, 2) ?></td></tr>
    </table>
    <?php if (!empty($purchase['notes'])): ?><div class="notes"><strong>Notes:</strong> <?= e($purchase['notes']) ?></div><?php endif; ?>
    <div class="footer"><?= e($company['invoice_footer'] ?? '') ?></div>
</main>
</body>
</html>
