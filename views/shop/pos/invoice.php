<?php
// views/shop/pos/invoice.php — Exact Thermal POS Receipt Layout matching GRM Shop sample bill
$totalGst = (float)$sale['cgst_amount'] + (float)$sale['sgst_amount'] + (float)$sale['igst_amount'];
$subtotal = (float)$sale['subtotal'];
$totalAmountBeforeDisc = $subtotal + $totalGst;
$discountAmount = (float)($sale['discount_amount'] ?? 0);
$netAmount = (float)$sale['total_amount'];
$unroundedNet = max(0, $totalAmountBeforeDisc - $discountAmount);
$roundOff = round($netAmount - $unroundedNet, 2);
$totalQuantity = $totalQty ?? array_sum(array_column($items, 'quantity'));

// Extract Bill No
$billNo = $sale['invoice_number'];
if (preg_match('/(\d+)$/', $sale['invoice_number'], $m)) {
    $billNo = $m[1];
}
$billId = $sale['id'] ?? '1026';
$purchasedAt = formatUtcDateTime($sale['created_at'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?= e($sale['invoice_number']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            background-color: #f1f5f9;
            font-family: 'Courier New', Courier, Consolas, 'Lucida Console', Monaco, monospace;
            color: #000;
            font-size: 11.5px;
            line-height: 1.35;
            font-weight: 600;
            padding: 20px 10px;
        }
        .action-bar {
            max-width: 320px;
            margin: 0 auto 12px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            font-size: 12px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-weight: 600;
            border-radius: 20px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            transition: all 0.2s;
        }
        .btn:hover {
            background: #e2e8f0;
        }
        .btn-primary {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }
        .btn-primary:hover {
            background: #0b5ed7;
        }

        /* Thermal Bill Receipt Card */
        .thermal-receipt {
            width: 320px;
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 18px 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            color: #000;
        }

        /* Header Logo & Store */
        .logo-container {
            text-align: center;
            margin-bottom: 6px;
        }
        .logo-box {
            display: inline-block;
            border: 1.5px solid #000;
            padding: 4px 10px;
            background: #fff;
            position: relative;
        }
        .logo-text {
            font-family: 'Times New Roman', Times, Georgia, serif;
            font-size: 42px;
            font-weight: 900;
            letter-spacing: -2px;
            line-height: 1;
            color: #000;
            display: inline-block;
            text-transform: uppercase;
        }
        .store-title {
            text-align: center;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        .store-address {
            text-align: center;
            font-size: 10.5px;
            font-weight: 600;
            text-transform: uppercase;
            line-height: 1.3;
            margin-top: 2px;
        }
        .store-contact {
            text-align: center;
            font-size: 10.5px;
            font-weight: 600;
            margin-top: 2px;
        }
        .store-gst {
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        /* Dividers */
        .divider-dashed {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .divider-solid {
            border-top: 1px solid #000;
            margin: 5px 0;
        }
        .divider-double {
            border-top: 2px solid #000;
            margin: 6px 0;
        }

        /* Bill Meta Two-Column Grid */
        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.3;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            font-weight: 600;
        }
        .items-table th {
            font-weight: 800;
            padding: 3px 0;
            border-bottom: 1px dashed #000;
        }
        .items-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .item-name-row {
            font-weight: 700;
            padding-top: 3px;
            word-break: break-word;
        }
        .item-data-row td {
            padding-bottom: 3px;
        }
        .item-tag-badge {
            font-size: 9.5px;
            font-weight: 600;
            display: block;
            color: #111;
        }

        /* Totals Grid */
        .totals-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            font-weight: 600;
            margin: 2px 0;
        }
        .net-amount-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13.5px;
            font-weight: 900;
            margin: 4px 0;
        }

        /* Terms & Footer */
        .terms-section {
            font-size: 9.5px;
            font-weight: 600;
            line-height: 1.35;
            margin-top: 4px;
        }
        .terms-title {
            font-weight: 800;
            margin-bottom: 2px;
        }
        .visit-again {
            text-align: center;
            font-size: 10.5px;
            font-weight: 800;
            margin-top: 8px;
            letter-spacing: 0.5px;
        }

        @media print {
            body {
                background: transparent !important;
                padding: 0 !important;
            }
            .action-bar {
                display: none !important;
            }
            .thermal-receipt {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 4px 6px !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Top Navigation & Print Controls (Hidden on Print) -->
<div class="action-bar no-print">
    <a href="<?= url('shop/pos') ?>" class="btn">
        <i class="bi bi-arrow-left" style="margin-right: 4px;"></i> Back to POS
    </a>
    <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer-fill" style="margin-right: 4px;"></i> Print Bill
    </button>
</div>

<!-- Thermal Bill Sheet -->
<div class="thermal-receipt">
    <!-- 1. GRM LOGO -->
    <div class="logo-container">
        <div class="logo-box">
            <svg viewBox="0 0 150 75" width="130" height="65" xmlns="http://www.w3.org/2000/svg">
                <!-- Stipple shaded background -->
                <defs>
                    <pattern id="stipple" width="3" height="3" patternUnits="userSpaceOnUse">
                        <circle cx="1.5" cy="1.5" r="0.6" fill="#000" />
                    </pattern>
                </defs>
                <rect width="150" height="75" fill="url(#stipple)" opacity="0.35" />
                <rect width="150" height="75" fill="none" stroke="#000" stroke-width="1.5" />
                <!-- Stylized GRM Monogram -->
                <text x="75" y="54" font-family="'Times New Roman', Georgia, serif" font-size="52" font-weight="900" text-anchor="middle" fill="#000" letter-spacing="-2">GRM</text>
            </svg>
        </div>
    </div>

    <!-- 2. STORE HEADER -->
    <div class="store-title"><?= e(!empty($sale['shop_name']) && $sale['shop_name'] !== 'GRM Retail Store' ? $sale['shop_name'] : 'GRM SHOP') ?></div>
    
    <div class="store-address">
        <?php if (!empty($sale['shop_address']) && strlen($sale['shop_address']) > 15): ?>
            <?= nl2br(e($sale['shop_address'])) ?>
        <?php else: ?>
            SULUR KMCH HOSPITAL<br>
            OPPOSITE, TRICHY<br>
            ROAD, SULUR, COIMBATORE
        <?php endif; ?>
    </div>

    <div class="store-contact">
        Phone No: <?= e(!empty($sale['shop_phone']) ? $sale['shop_phone'] : '8807144175, 8610612625') ?><br>
        EMail Id: <?= e($sale['shop_email'] ?? '') ?>
    </div>

    <div class="store-gst">
        GST NO: <?= e(!empty($sale['shop_gstin']) ? $sale['shop_gstin'] : '33BFQPQ7507C1ZX') ?>
    </div>

    <div class="divider-dashed"></div>

    <!-- 3. BILL METADATA -->
    <div class="meta-row">
        <span>Bill No: <?= e($billNo) ?></span>
        <span>BID: <?= e($billId) ?></span>
    </div>
    <div class="meta-row">
        <span>Purchased At: <?= e($purchasedAt) ?></span>
    </div>
    <div class="meta-row">
        <?php if (!empty($sale['staff_name'])): ?>
            <span>Staff: <?= e($sale['staff_name']) ?></span>
        <?php endif; ?>
    </div>

    <div class="divider-dashed"></div>

    <!-- 4. CUSTOMER & PAYMENT MODE -->
    <div class="meta-row">
        <span>To: <?= e(!empty($sale['customer_name']) ? strtoupper($sale['customer_name']) : 'WALK-IN CUSTOMER') ?></span>
        <span>Pay Mode : <?= ($sale['payment_mode'] ?? '') === 'CASH_UPI' ? 'CASH + UPI' : e(strtoupper($sale['payment_mode'] ?? 'CASH')) ?></span>
    </div>
    <?php if (($sale['payment_mode'] ?? '') === 'CASH_UPI' && preg_match('/\[Split Payment:\s*Cash\s*₹([\d\.]+)\s*\+\s*UPI\s*₹([\d\.]+)(?:\s*\(Ref:\s*([^)]+)\))?\]/i', $sale['notes'] ?? '', $spm)): ?>
    <div class="meta-row" style="font-size: 9.5px; font-weight: 700; color: #000;">
        <span>Split: Cash ₹<?= number_format((float)$spm[1], 2) ?> | UPI ₹<?= number_format((float)$spm[2], 2) ?><?= !empty($spm[3]) ? ' (' . e($spm[3]) . ')' : '' ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($sale['customer_phone']) || !empty($sale['customer_gst'])): ?>
    <div class="meta-row">
        <?php if (!empty($sale['customer_phone'])): ?>
            <span>Ph: <?= e($sale['customer_phone']) ?></span>
        <?php endif; ?>
        <?php if (!empty($sale['customer_gst'])): ?>
            <span>Cust GST: <?= e($sale['customer_gst']) ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($sale['customer_address'])): ?>
    <div class="meta-row" style="font-size: 10px; color: #111;">
        <span>Add: <?= e($sale['customer_address']) ?></span>
    </div>
    <?php endif; ?>

    <div class="divider-dashed"></div>

    <!-- 5. ITEM TABLE -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="text-align: left; width: 40%;">Item</th>
                <th style="text-align: center; width: 10%;">Qty</th>
                <th style="text-align: right; width: 18%;">Price</th>
                <th style="text-align: right; width: 16%;">Disc</th>
                <th style="text-align: right; width: 16%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): 
                $itemGstPct = (float)($item['gst_percentage'] ?? 5.0);
                $itemRate = (float)$item['unit_price'];
                $itemDisc = (float)($item['discount_amount'] ?? 0);
                $itemTotal = (float)$item['total_amount'];
                $itemQty = (int)$item['quantity'];
            ?>
                <tr class="item-name-row">
                    <td colspan="5">
                        <?= e($item['product_name']) ?><?= !empty($item['size_name']) ? ' - ' . e($item['size_name']) : '' ?><?= !empty($item['color_name']) ? ' (' . e($item['color_name']) . ')' : '' ?>
                        <?php if (!empty($item['scanned_qr_codes'])): ?>
                            <span class="item-tag-badge">Tag: <?= e($item['scanned_qr_codes']) ?></span>
                        <?php else: ?>
                            <span class="item-tag-badge">QR: <?= e($item['sku'] ?? 'QR-CODE') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="item-data-row">
                    <td></td>
                    <td style="text-align: center;"><?= $itemQty ?></td>
                    <td style="text-align: right;"><?= number_format($itemRate, 2) ?></td>
                    <td style="text-align: right; color: <?= $itemDisc > 0 ? '#b91c1c' : '#000' ?>;"><?= $itemDisc > 0 ? '-' . number_format($itemDisc, 2) : '0.00' ?></td>
                    <td style="text-align: right; font-weight: 700;"><?= number_format($itemTotal, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider-dashed"></div>

    <!-- 6. SUMMARY TOTALS -->
    <div class="totals-row">
        <span>Total Qty: <?= $totalQuantity ?></span>
        <span>Taxable Base: <?= number_format($subtotal, 2) ?></span>
    </div>
    <?php if ($discountAmount > 0): ?>
    <div class="totals-row" style="color: #b91c1c;">
        <span>Total Discount:</span>
        <span>-<?= number_format($discountAmount, 2) ?></span>
    </div>
    <?php endif; ?>
    <div class="totals-row">
        <span>GST Amount (Incl.): <?= number_format($totalGst, 2) ?></span>
        <span>Round Off: <?= number_format($roundOff, 2) ?></span>
    </div>

    <div class="divider-double"></div>

    <!-- 7. NET AMOUNT -->
    <div class="net-amount-row">
        <span>Net Amount</span>
        <span><?= number_format($netAmount, 2) ?></span>
    </div>

    <div class="divider-dashed"></div>

    <!-- 8. TERMS AND CONDITIONS -->
    <div class="terms-section">
        <div class="terms-title">*Terms and Conditions</div>
        <div>1.Please check your bill before leaving the shop</div>
        <div>2.No bargain, fixed price only</div>
        <div>3.No cash back</div>
    </div>

    <!-- 9. FOOTER GREETING -->
    <div class="visit-again">
        ***** Thank You !!! Visit Again *****
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    // Auto-trigger print if opened in popup from POS checkout
    if (window.opener) {
        window.print();
    }
});
</script>

</body>
</html>
