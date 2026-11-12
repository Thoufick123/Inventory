<?php
$sellerCompany = $sellerCompany ?? [];
$seller = static fn(string $key, string $default = ''): string => htmlspecialchars((string)($sellerCompany[$key] ?? $default), ENT_QUOTES, 'UTF-8');
$printFormat = strtolower((string)($printFormat ?? (strtoupper((string)($bill['buyer_type'] ?? 'COMPANY')) === 'CUSTOMER' ? 'pos' : 'a4')));
if (!in_array($printFormat, ['a4', 'pos'], true)) {
    $printFormat = 'a4';
}
$billId = (int)($bill['id'] ?? 0);
$printBaseUrl = (string)($printBaseUrl ?? url('production/billing/print/' . $billId));
$backUrl = (string)($backUrl ?? url('production/billing/view/' . $billId));
$currency = (string)($sellerCompany['currency_symbol'] ?? '₹');

$isCustomer = strtoupper((string)($bill['buyer_type'] ?? 'COMPANY')) === 'CUSTOMER';
$buyerName = $isCustomer ? ($bill['customer_name'] ?? '') : ($bill['company_name'] ?? '');
$buyerPhone = $isCustomer ? ($bill['customer_phone'] ?? '') : ($bill['company_phone'] ?? '');
$buyerEmail = $isCustomer ? '' : ($bill['company_email'] ?? '');
$buyerAddressParts = array_filter([
    trim((string)($isCustomer ? ($bill['customer_address'] ?? '') : ($bill['company_address'] ?? ''))),
    trim((string)(($bill['customer_city'] ?? '') . (!empty($bill['customer_state']) ? ', ' . $bill['customer_state'] : ''))),
]);
$buyerAddressText = implode(', ', $buyerAddressParts);
$buyerGstin = $isCustomer ? ($bill['customer_gstin'] ?? '') : ($bill['company_gstin'] ?? '');

$purchasedRaw = !empty($bill['created_at']) ? $bill['created_at'] : ($bill['bill_date'] ?? date('Y-m-d'));
$purchasedAtFormatted = date('d M Y h:i A', strtotime((string)$purchasedRaw));
$staffName = trim((string)($bill['created_by_name'] ?? '')) ?: 'Staff';

$companyNameRaw = trim((string)($sellerCompany['company_name'] ?? ''));
if ($companyNameRaw === '') {
    $companyNameRaw = trim((string)($bill['concern_name'] ?? '')) ?: 'GRM';
}
$logoWord = strtoupper(strtok($companyNameRaw, " \t\n\r") ?: 'GRM');
if (strlen($logoWord) > 10) {
    $logoWord = substr($logoWord, 0, 10);
}

$totalQty = 0;
foreach ($billItems as $bi) {
    $totalQty += (int)($bi['quantity'] ?? 0);
}
$exactTotal = (float)($bill['total_amount'] ?? 0);
$roundedNet = round($exactTotal, 0);
$footerRaw = trim((string)($sellerCompany['invoice_footer'] ?? ''));
$termsLines = $footerRaw !== ''
    ? array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $footerRaw))))
    : [
        '1. Please check your bill before leaving the shop',
        '2. No bargain, fixed price only',
        '3. No cash back',
    ];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $printFormat === 'pos' ? 'POS Receipt' : 'Tax Invoice' ?> <?= htmlspecialchars((string)$bill['bill_number']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #111827;
            margin: 0;
            padding: 18px 14px 36px;
        }
        .actions {
            max-width: <?= $printFormat === 'pos' ? '440px' : '900px' ?>;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .action-group {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #1e293b;
            font: 600 13px Arial, sans-serif;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-pill:hover { background: #f8fafc; }
        .btn-pill.active {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }
        .btn-pill.primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }
        .btn-pill.primary:hover { background: #1d4ed8; }

        <?php if ($printFormat === 'pos'): ?>
        @page { size: 80mm auto; margin: 3mm; }
        .receipt-card {
            width: 340px;
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 18px 14px;
            box-shadow: 0 6px 22px rgba(15, 23, 42, 0.12);
            font-family: 'Courier New', Courier, monospace;
            font-size: 11.5px;
            line-height: 1.4;
            color: #111;
        }
        .logo-frame {
            border: 2px solid #222;
            width: 150px;
            margin: 0 auto 8px;
            padding: 6px 10px;
            text-align: center;
            background: #f8fafc;
        }
        .logo-frame span {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #111;
        }
        .store-header { text-align: center; }
        .store-header .store-name {
            font-size: 13.5px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .dash {
            border-top: 1px dashed #222;
            margin: 7px 0;
        }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .receipt-table th {
            text-align: left;
            padding: 3px 1px;
            border-bottom: 1px dashed #222;
            font-weight: 700;
        }
        .receipt-table td {
            padding: 3px 1px 1px;
            vertical-align: top;
        }
        .receipt-table .num, .receipt-table th.num { text-align: right; }
        .receipt-table .tag-row td {
            padding: 0 1px 4px;
            font-size: 10.5px;
            color: #333;
            word-break: break-all;
        }
        .disc-val { color: #b91c1c; font-weight: 600; }
        .net-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 15px;
            font-weight: 700;
            padding: 3px 0;
        }
        .terms {
            font-size: 10.5px;
            margin-top: 6px;
        }
        .terms-title { font-weight: 700; margin-bottom: 2px; }
        .thank-you {
            text-align: center;
            font-weight: 700;
            margin-top: 10px;
            font-size: 11.5px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none !important; }
            .receipt-card { box-shadow: none; width: 100%; padding: 0; }
        }
        <?php else: ?>
        @page { size: A4; margin: 12mm; }
        .invoice {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
            font-size: 14px;
        }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #222; padding-bottom: 16px; gap: 16px; }
        .section { margin: 18px 0; }
        .label { font-size: 11px; text-transform: uppercase; color: #666; font-weight: bold; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
        .summary { width: 360px; margin-left: auto; }
        .summary td { border: 0; padding: 5px 0; }
        .total { font-weight: bold; font-size: 17px; border-top: 1px solid #222 !important; }
        .tags { font-size: 10px; color: #666; word-break: break-all; }
        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .actions { display: none !important; }
            .invoice { max-width: none; box-shadow: none; padding: 0; }
        }
        <?php endif; ?>
    </style>
</head>
<body>
<div class="actions">
    <a class="btn-pill" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">&larr; Back</a>
    <div class="action-group">
        <a class="btn-pill <?= $printFormat === 'a4' ? 'active' : '' ?>" href="<?= htmlspecialchars($printBaseUrl . '?format=a4', ENT_QUOTES, 'UTF-8') ?>">A4 Tax Invoice</a>
        <a class="btn-pill <?= $printFormat === 'pos' ? 'active' : '' ?>" href="<?= htmlspecialchars($printBaseUrl . '?format=pos', ENT_QUOTES, 'UTF-8') ?>">POS Receipt (80mm)</a>
        <button type="button" class="btn-pill primary" onclick="window.print()">Print Bill</button>
    </div>
</div>

<?php if ($printFormat === 'pos'): ?>
<main class="receipt-card">
    <div class="store-header">
        <div class="logo-frame"><span><?= htmlspecialchars($logoWord, ENT_QUOTES, 'UTF-8') ?></span></div>
        <div class="store-name"><?= htmlspecialchars($companyNameRaw, ENT_QUOTES, 'UTF-8') ?></div>
        <?php if (!empty($sellerCompany['company_address'])): ?>
            <div><?= nl2br($seller('company_address')) ?></div>
        <?php endif; ?>
        <?php if (!empty($sellerCompany['company_phone'])): ?>
            <div>Phone No: <?= $seller('company_phone') ?></div>
        <?php endif; ?>
        <?php if (!empty($sellerCompany['company_email'])): ?>
            <div>EMail Id: <?= $seller('company_email') ?></div>
        <?php endif; ?>
        <?php if (!empty($sellerCompany['company_gstin'])): ?>
            <div>GST NO: <?= $seller('company_gstin') ?></div>
        <?php endif; ?>
    </div>

    <div class="dash"></div>
    <div>
        <div class="flex-between">
            <span>Bill No: <strong><?= htmlspecialchars((string)$bill['bill_number']) ?></strong></span>
            <span>BID: <?= $billId ?></span>
        </div>
        <div>Purchased At: <?= htmlspecialchars($purchasedAtFormatted) ?></div>
        <div>Staff: <?= htmlspecialchars($staffName) ?></div>
    </div>

    <div class="dash"></div>
    <div class="flex-between">
        <div style="flex:1; min-width:0;">
            <div><strong>To:</strong> <?= htmlspecialchars($buyerName ?: 'Walk-in Customer') ?></div>
            <?php if ($buyerPhone !== ''): ?><div>Ph: <?= htmlspecialchars($buyerPhone) ?></div><?php endif; ?>
            <?php if ($buyerAddressText !== ''): ?><div>Add: <?= htmlspecialchars($buyerAddressText) ?></div><?php endif; ?>
            <?php if ($buyerGstin !== ''): ?><div>GSTIN: <?= htmlspecialchars($buyerGstin) ?></div><?php endif; ?>
        </div>
        <div style="text-align:right; white-space:nowrap;">
            <div>Pay Mode : <strong><?= htmlspecialchars((string)($bill['payment_method'] ?? 'CASH')) ?></strong></div>
            <?php if (($bill['payment_method'] ?? '') === 'CASH_UPI'): ?>
                <div style="font-size:10px;">C: <?= number_format((float)($bill['cash_amount'] ?? 0), 2) ?> / U: <?= number_format((float)($bill['upi_amount'] ?? 0), 2) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dash"></div>
    <table class="receipt-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>HSN</th>
                <th class="num">Qty</th>
                <th class="num">Price</th>
                <th class="num">Disc</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($billItems as $item):
                $variantLabel = trim(($item['size_name'] ?? '') . (!empty($item['color_name']) ? ' ' . $item['color_name'] : ''));
                $itemTitle = trim((string)$item['product_name']) . ($variantLabel !== '' ? ' - ' . $variantLabel : '');
                $qty = max(1, (int)($item['quantity'] ?? 0));
                $mrpUnit = (float)($item['mrp'] ?? 0);
                if ($mrpUnit <= 0) {
                    $mrpUnit = (float)($item['unit_price'] ?? 0);
                }
                $lineDisc = (float)($item['discount_amount'] ?? 0);
                $lineTotal = (float)($item['total_amount'] ?? 0);
                $hsn = trim((string)($item['hsn_code'] ?? ''));
            ?>
            <tr>
                <td><?= htmlspecialchars($itemTitle) ?></td>
                <td><?= htmlspecialchars($hsn !== '' ? $hsn : '—') ?></td>
                <td class="num"><?= $qty ?></td>
                <td class="num"><?= number_format($mrpUnit, 2) ?></td>
                <td class="num <?= $lineDisc > 0 ? 'disc-val' : '' ?>"><?= $lineDisc > 0 ? '-' . number_format($lineDisc, 2) : '0.00' ?></td>
                <td class="num"><?= number_format($lineTotal, 2) ?></td>
            </tr>
            <?php if (!empty($item['tags'])): ?>
            <tr class="tag-row">
                <td colspan="6">Tag: <?= htmlspecialchars(implode(', ', $item['tags'])) ?></td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="dash"></div>
    <div style="font-size:11px;">
        <div class="flex-between">
            <span>Total Qty: <?= $totalQty ?></span>
            <span>Taxable Base: <?= number_format((float)($bill['subtotal'] ?? 0), 2) ?></span>
        </div>
        <div class="flex-between disc-val">
            <span>Total Discount:</span>
            <span><?= (float)($bill['discount_amount'] ?? 0) > 0 ? '-' . number_format((float)$bill['discount_amount'], 2) : '0.00' ?></span>
        </div>
        <div class="flex-between">
            <span>GST Amount (Incl.): <?= number_format((float)($bill['tax_amount'] ?? 0), 2) ?></span>
            <span>Round Off: <?= number_format($roundedNet, 2) ?></span>
        </div>
    </div>

    <div class="dash"></div>
    <div class="net-row">
        <span>Net Amount</span>
        <span><?= number_format($roundedNet, 2) ?></span>
    </div>
    <?php if (abs($roundedNet - $exactTotal) >= 0.01): ?>
        <div style="text-align:right;font-size:10px;color:#555;">Exact Total: <?= htmlspecialchars($currency) ?><?= number_format($exactTotal, 2) ?></div>
    <?php endif; ?>
    <?php if (!empty($bill['notes'])): ?>
        <div class="dash"></div>
        <div style="font-size:10.5px;">Note: <?= htmlspecialchars((string)$bill['notes']) ?></div>
    <?php endif; ?>

    <div class="dash"></div>
    <div class="terms">
        <div class="terms-title">*Terms and Conditions</div>
        <?php foreach ($termsLines as $tLine): ?>
            <div><?= htmlspecialchars($tLine) ?></div>
        <?php endforeach; ?>
    </div>
    <div class="thank-you">***** Thank You !!! Visit Again *****</div>
</main>

<?php else: ?>
<main class="invoice">
    <div class="header">
        <div>
            <h2 style="margin:0 0 6px"><?= htmlspecialchars($companyNameRaw) ?></h2>
            <div><?= nl2br($seller('company_address')) ?></div>
            <div><?= $seller('company_phone') ?> &middot; <?= $seller('company_email') ?></div>
            <div>GSTIN: <?= $seller('company_gstin') ?></div>
        </div>
        <div class="right">
            <h2 style="margin:0 0 6px">TAX INVOICE</h2>
            <div><strong>Bill:</strong> <?= htmlspecialchars((string)$bill['bill_number']) ?></div>
            <div><strong>BID:</strong> <?= $billId ?></div>
            <div><strong>Date:</strong> <?= htmlspecialchars(date('d M Y', strtotime((string)$bill['bill_date']))) ?></div>
            <div><strong>Staff:</strong> <?= htmlspecialchars($staffName) ?></div>
        </div>
    </div>
    <div class="section">
        <?php if ($isCustomer): ?>
            <div class="label">Customer</div>
            <strong><?= htmlspecialchars((string)($bill['customer_name'] ?? '')) ?></strong>
            <div><?= htmlspecialchars((string)($bill['customer_phone'] ?? '')) ?></div>
            <div><?= nl2br(htmlspecialchars((string)($bill['customer_address'] ?? ''))) ?></div>
            <div><?= htmlspecialchars(trim(($bill['customer_city'] ?? '') . ' ' . ($bill['customer_state'] ?? ''))) ?></div>
            <?php if (!empty($bill['customer_gstin'])): ?><div>GSTIN: <?= htmlspecialchars((string)$bill['customer_gstin']) ?></div><?php endif; ?>
        <?php else: ?>
            <div>
                <div class="label">Buyer company</div>
                <strong><?= htmlspecialchars((string)($bill['company_name'] ?? '')) ?></strong>
                <div><?= nl2br(htmlspecialchars((string)($bill['company_address'] ?? ''))) ?></div>
                <div><?= htmlspecialchars((string)($bill['company_phone'] ?? '')) ?> &middot; <?= htmlspecialchars((string)($bill['company_email'] ?? '')) ?></div>
                <?php if (!empty($bill['company_gstin'])): ?><div>GSTIN: <?= htmlspecialchars((string)$bill['company_gstin']) ?></div><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>Item / QR</th>
                    <th>HSN</th>
                    <th>Qty</th>
                    <th class="right">MRP</th>
                    <th class="right">Discount</th>
                    <th class="right">Taxable value</th>
                    <th>GST details</th>
                    <th class="right">Tax</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($billItems as $item): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string)$item['product_name']) ?></strong>
                            <div class="tags">
                                <?= htmlspecialchars(trim(($item['sku'] ?? '') . ' ' . ($item['size_name'] ?? '') . ' ' . ($item['color_name'] ?? ''))) ?><br>
                                <?= htmlspecialchars(implode(', ', $item['tags'] ?? [])) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars((string)(($item['hsn_code'] ?? '') !== '' ? $item['hsn_code'] : '—')) ?></td>
                        <td><?= (int)($item['quantity'] ?? 0) ?></td>
                        <td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)($item['mrp'] ?? 0) * (int)($item['quantity'] ?? 0), 2) ?></td>
                        <td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)($item['discount_amount'] ?? 0), 2) ?></td>
                        <td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)($item['taxable_amount'] ?? 0), 2) ?></td>
                        <td>
                            <?= htmlspecialchars((string)($item['gst_type'] ?? 'BOTH')) ?> &middot; <?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? 'Percentage' : 'Fixed' ?><br>
                            <?php $gstUnit = ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ' . $currency . '/piece'; ?>
                            <?php if (($item['gst_type'] ?? '') === 'BOTH'): ?>SGST <?= number_format((float)($item['sgst_rate'] ?? 0), 2) ?><?= $gstUnit ?> &middot; CGST <?= number_format((float)($item['cgst_rate'] ?? 0), 2) ?><?= $gstUnit ?>
                            <?php elseif (($item['gst_type'] ?? '') === 'IGST'): ?>IGST <?= number_format((float)($item['igst_rate'] ?? 0), 2) ?><?= $gstUnit ?>
                            <?php elseif (($item['gst_type'] ?? '') === 'SGST'): ?>SGST <?= number_format((float)($item['sgst_rate'] ?? 0), 2) ?><?= $gstUnit ?>
                            <?php elseif (($item['gst_type'] ?? '') === 'CGST'): ?>CGST <?= number_format((float)($item['cgst_rate'] ?? 0), 2) ?><?= $gstUnit ?><?php endif; ?>
                        </td>
                        <td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)($item['tax_amount'] ?? 0), 2) ?></td>
                        <td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)($item['total_amount'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="section">
        <table class="summary">
            <tr><td>Gross / MRP</td><td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)($bill['gross_amount'] ?? 0), 2) ?></td></tr>
            <tr><td>Discount</td><td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)($bill['discount_amount'] ?? 0), 2) ?></td></tr>
            <tr><td>Taxable subtotal</td><td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)($bill['subtotal'] ?? 0), 2) ?></td></tr>
            <?php if ((float)($bill['cgst_amount'] ?? 0) > 0): ?><tr><td>CGST</td><td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)$bill['cgst_amount'], 2) ?></td></tr><?php endif; ?>
            <?php if ((float)($bill['sgst_amount'] ?? 0) > 0): ?><tr><td>SGST</td><td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)$bill['sgst_amount'], 2) ?></td></tr><?php endif; ?>
            <?php if ((float)($bill['igst_amount'] ?? 0) > 0): ?><tr><td>IGST</td><td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)$bill['igst_amount'], 2) ?></td></tr><?php endif; ?>
            <tr><td>Total taxes</td><td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)($bill['tax_amount'] ?? 0), 2) ?></td></tr>
            <tr class="total"><td>Total</td><td class="right"><?= htmlspecialchars($currency) ?><?= number_format((float)($bill['total_amount'] ?? 0), 2) ?></td></tr>
        </table>
    </div>
    <div class="section"><strong>Payment:</strong> <?= htmlspecialchars((string)$bill['payment_method']) ?> (<?= htmlspecialchars((string)$bill['payment_status']) ?>)</div>
    <?php if (!empty($bill['notes'])): ?><div class="section"><strong>Notes:</strong> <?= nl2br(htmlspecialchars((string)$bill['notes'])) ?></div><?php endif; ?>
    <div class="section" style="text-align:center;border-top:1px solid #ddd;padding-top:12px"><?= htmlspecialchars((string)($sellerCompany['invoice_footer'] ?? 'Thank you for your business.')) ?></div>
</main>
<?php endif; ?>
</body>
</html>
