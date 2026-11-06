<?php
$sellerCompany = $sellerCompany ?? [];
$seller = static fn(string $key): string => htmlspecialchars((string)($sellerCompany[$key] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Production Bill <?= htmlspecialchars($bill['bill_number']) ?></title>
    <style>
        body{font:14px Arial,sans-serif;color:#222;margin:24px}.invoice{max-width:900px;margin:auto}.header{display:flex;justify-content:space-between;border-bottom:2px solid #222;padding-bottom:16px}.section{margin:18px 0}.parties{display:grid;grid-template-columns:1fr 1fr;gap:24px}.label{font-size:11px;text-transform:uppercase;color:#666;font-weight:bold;margin-bottom:6px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f3f4f6}.right{text-align:right}.summary{width:360px;margin-left:auto}.summary td{border:0;padding:5px 0}.total{font-weight:bold;font-size:17px;border-top:1px solid #222!important}.tags{font-size:10px;color:#666;word-break:break-all}.actions{max-width:900px;margin:0 auto 15px;text-align:right}.actions button{padding:8px 14px}@media print{body{margin:0}.actions{display:none}.invoice{max-width:none}}
    </style>
</head>
<body>
<div class="actions"><button onclick="window.print()">Print bill</button></div>
<main class="invoice">
    <div class="header">
        <div><h2 style="margin:0 0 6px"><?= $seller('company_name') ?></h2><div><?= nl2br($seller('company_address')) ?></div><div><?= $seller('company_phone') ?> · <?= $seller('company_email') ?></div><div>GSTIN: <?= $seller('company_gstin') ?></div></div>
        <div class="right"><h2 style="margin:0 0 6px">TAX INVOICE</h2><div><strong>Bill:</strong> <?= htmlspecialchars($bill['bill_number']) ?></div><div><strong>Date:</strong> <?= htmlspecialchars(date('d M Y', strtotime($bill['bill_date']))) ?></div></div>
    </div>
    <div class="section">
        <?php if (($bill['buyer_type'] ?? 'COMPANY') === 'CUSTOMER'): ?>
            <div class="label">Customer</div><strong><?= htmlspecialchars($bill['customer_name'] ?? '') ?></strong><div><?= htmlspecialchars($bill['customer_phone'] ?? '') ?></div><div><?= nl2br(htmlspecialchars($bill['customer_address'] ?? '')) ?></div><div><?= htmlspecialchars(trim(($bill['customer_city'] ?? '') . ' ' . ($bill['customer_state'] ?? ''))) ?></div><div>GSTIN: <?= htmlspecialchars($bill['customer_gstin'] ?? '') ?></div>
        <?php else: ?>
        <div><div class="label">Buyer company</div><strong><?= htmlspecialchars($bill['company_name']) ?></strong><div><?= nl2br(htmlspecialchars($bill['company_address'] ?? '')) ?></div><div><?= htmlspecialchars($bill['company_phone'] ?? '') ?> · <?= htmlspecialchars($bill['company_email'] ?? '') ?></div><div>GSTIN: <?= htmlspecialchars($bill['company_gstin'] ?? '') ?></div></div>
        <?php endif; ?>
    </div>
    <div class="section">
        <table>
            <thead><tr><th>Item / QR</th><th>Qty</th><th class="right">MRP</th><th class="right">Discount</th><th class="right">Taxable value</th><th>GST details</th><th class="right">Tax</th><th class="right">Total</th></tr></thead>
            <tbody>
                <?php foreach ($billItems as $item): ?>
                    <tr><td><strong><?= htmlspecialchars($item['product_name']) ?></strong><div class="tags"><?= htmlspecialchars(trim(($item['sku'] ?? '') . ' ' . ($item['size_name'] ?? '') . ' ' . ($item['color_name'] ?? ''))) ?><br><?= htmlspecialchars(implode(', ', $item['tags'])) ?></div></td><td><?= (int)$item['quantity'] ?></td><td class="right">₹<?= number_format((float)$item['mrp'] * (int)$item['quantity'], 2) ?></td><td class="right">₹<?= number_format((float)$item['discount_amount'], 2) ?></td><td class="right">₹<?= number_format((float)$item['taxable_amount'], 2) ?></td><td><?= htmlspecialchars($item['gst_type'] ?? 'BOTH') ?> · <?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? 'Percentage' : 'Fixed' ?><br>
                        <?php $gstUnit = ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ₹/piece'; ?>
                        <?php if (($item['gst_type'] ?? '') === 'BOTH'): ?>SGST <?= number_format((float)$item['sgst_rate'], 2) ?><?= $gstUnit ?> · CGST <?= number_format((float)$item['cgst_rate'], 2) ?><?= $gstUnit ?>
                        <?php elseif (($item['gst_type'] ?? '') === 'IGST'): ?>IGST <?= number_format((float)$item['igst_rate'], 2) ?><?= $gstUnit ?>
                        <?php elseif (($item['gst_type'] ?? '') === 'SGST'): ?>SGST <?= number_format((float)$item['sgst_rate'], 2) ?><?= $gstUnit ?>
                        <?php elseif (($item['gst_type'] ?? '') === 'CGST'): ?>CGST <?= number_format((float)$item['cgst_rate'], 2) ?><?= $gstUnit ?><?php endif; ?>
                    </td><td class="right">₹<?= number_format((float)$item['tax_amount'], 2) ?></td><td class="right">₹<?= number_format((float)$item['total_amount'], 2) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="section"><table class="summary">
        <tr><td>Gross / MRP</td><td class="right">₹<?= number_format((float)$bill['gross_amount'], 2) ?></td></tr>
        <tr><td>Discount</td><td class="right">₹<?= number_format((float)$bill['discount_amount'], 2) ?></td></tr>
        <tr><td>Taxable subtotal</td><td class="right">₹<?= number_format((float)$bill['subtotal'], 2) ?></td></tr>
        <?php if ((float)$bill['cgst_amount'] > 0): ?><tr><td>CGST</td><td class="right">₹<?= number_format((float)$bill['cgst_amount'], 2) ?></td></tr><?php endif; ?>
        <?php if ((float)$bill['sgst_amount'] > 0): ?><tr><td>SGST</td><td class="right">₹<?= number_format((float)$bill['sgst_amount'], 2) ?></td></tr><?php endif; ?>
        <?php if ((float)$bill['igst_amount'] > 0): ?><tr><td>IGST</td><td class="right">₹<?= number_format((float)$bill['igst_amount'], 2) ?></td></tr><?php endif; ?>
        <tr><td>Total taxes</td><td class="right">₹<?= number_format((float)$bill['tax_amount'], 2) ?></td></tr>
        <tr class="total"><td>Total</td><td class="right">₹<?= number_format((float)$bill['total_amount'], 2) ?></td></tr>
    </table></div>
    <div class="section"><strong>Payment:</strong> <?= htmlspecialchars($bill['payment_method']) ?> (<?= htmlspecialchars($bill['payment_status']) ?>)</div>
    <?php if (!empty($bill['notes'])): ?><div class="section"><strong>Notes:</strong> <?= nl2br(htmlspecialchars($bill['notes'])) ?></div><?php endif; ?>
    <div class="section" style="text-align:center;border-top:1px solid #ddd;padding-top:12px"><?= htmlspecialchars((string)($sellerCompany['invoice_footer'] ?? 'Thank you for your business.')) ?></div>
</main>
</body>
</html>
