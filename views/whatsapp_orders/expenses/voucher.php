<?php
// views/whatsapp_orders/expenses/voucher.php — Printable WhatsApp Channel Expense Voucher (WA-EXP)
$exp = $expense ?? [];
$notes = $exp['notes'] ?? '';
$metadataStr = '';
if (preg_match('/\[Metadata: (.*?)\]/', $notes, $matches)) {
    $metadataStr = $matches[1];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Channel Expense Voucher — <?= e($exp['expense_number'] ?? 'WA-EXP') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
        }
        body {
            background-color: #f1f5f9;
            padding: 30px;
        }
        .voucher-box {
            max-width: 680px;
            margin: 0 auto;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            border: 2px solid #cbd5e1;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            position: relative;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        
        .header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .title-badge {
            background: #dcfce7;
            color: #166534;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.5px;
            display: inline-block;
            text-transform: uppercase;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
        }
        .data-table th {
            background-color: #f8fafc;
            width: 32%;
            text-align: left;
            color: #475569;
            font-weight: 600;
        }

        .amount-box {
            background: #f8fafc;
            border: 2px dashed #16a34a;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            margin-bottom: 25px;
        }
        .amount-val {
            font-size: 30px;
            font-weight: 900;
            color: #14532d;
        }

        .signatures {
            margin-top: 45px;
            display: flex;
            justify-content: space-between;
        }
        .sig-box {
            text-align: center;
            width: 30%;
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .actions-bar {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 9px 20px;
            background: #128c7e;
            color: #fff;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
            cursor: pointer;
            border: none;
            margin: 0 4px;
            transition: all 0.2s;
        }
        .btn:hover {
            background: #075e54;
        }
        .btn-secondary {
            background: #64748b;
        }
        .btn-secondary:hover {
            background: #475569;
        }

        .meta-pill {
            display: inline-block;
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 12px;
            margin-right: 6px;
            margin-bottom: 4px;
            font-family: monospace;
            font-weight: bold;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .voucher-box { box-shadow: none; border: 1px solid #94a3b8; max-width: 100%; }
            .actions-bar { display: none; }
        }
    </style>
</head>
<body>

    <div class="actions-bar">
        <button onclick="window.print()" class="btn"><i class="bi bi-printer me-1"></i> Print WhatsApp Voucher</button>
        <button onclick="window.close()" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i> Close</button>
    </div>

    <div class="voucher-box">
        <div class="header">
            <div>
                <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px;">GRM APPARELS PVT LTD</h2>
                <p style="font-size: 12px; color: #128c7e; font-weight: bold; margin-top: 2px;">
                    <i class="bi bi-whatsapp me-1"></i> WhatsApp Orders &amp; Social Commerce Channel
                </p>
                <p style="font-size: 11px; color: #94a3b8;">Direct Sales &amp; Dispatch Logistics Division</p>
            </div>
            <div class="text-end">
                <span class="title-badge">WHATSAPP CHANNEL VOUCHER</span>
                <div style="font-family: monospace; font-size: 15px; font-weight: bold; margin-top: 6px; color: #166534;"><?= e($exp['expense_number'] ?? 'WA-EXP') ?></div>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Date: <?= !empty($exp['expense_date']) ? date('d M Y', strtotime($exp['expense_date'])) : date('d M Y') ?></div>
            </div>
        </div>

        <table class="data-table">
            <tr>
                <th>Voucher Created</th>
                <td><?= !empty($exp['created_at']) ? date('d M Y, h:i A', strtotime($exp['created_at'])) : date('d M Y, h:i A') ?></td>
            </tr>
            <tr>
                <th>Sales Channel</th>
                <td class="fw-bold" style="color: #128c7e;">WhatsApp Commerce Hub</td>
            </tr>
            <tr>
                <th>Expense Category</th>
                <td><span style="background: #e2e8f0; padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;"><?= e($exp['category']) ?></span></td>
            </tr>
            <tr>
                <th>Expense Title / Service</th>
                <td class="fw-bold"><?= e($exp['item_name'] ?: 'WhatsApp Operating Expense') ?></td>
            </tr>
            <tr>
                <th>Paid To / Payee</th>
                <td class="fw-bold" style="font-size: 14px; color: #0f172a;">
                    <?= e($exp['paid_to'] ?: 'Bearer') ?>
                    <?php if (!empty($exp['vendor_phone'])): ?>
                        <span style="font-weight: normal; font-size: 12px; color: #64748b;">(Phone: <?= e($exp['vendor_phone']) ?>)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Payment Mode &amp; Status</th>
                <td>
                    <strong><?= e($exp['payment_method'] ?? 'UPI') ?></strong>
                    <span style="margin-left: 8px; font-size: 11px; padding: 2px 8px; border-radius: 10px; font-weight: bold; background: <?= ($exp['payment_status'] ?? '') === 'PAID' ? '#dcfce7; color: #166534;' : '#fef3c7; color: #92400e;' ?>">
                        <?= e($exp['payment_status'] ?? 'PAID') ?>
                    </span>
                    <?php if (!empty($exp['payment_reference'])): ?>
                        <div style="font-size: 11px; color: #64748b; margin-top: 3px;">Ref/UTR: <span style="font-family: monospace;"><?= e($exp['payment_reference']) ?></span></div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if (!empty($exp['receipt_url'])): ?>
            <tr>
                <th>Attached Bill / Receipt</th>
                <td>
                    <a href="<?= uploadUrl($exp['receipt_url']) ?>" target="_blank" style="color: #166534; text-decoration: none; font-weight: 600; font-size: 12.5px;">
                        <i class="bi bi-cloud-arrow-down me-1"></i> View Cloudinary Receipt (<?= e(basename($exp['receipt_url'])) ?>)
                    </a>
                </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($metadataStr)): ?>
            <tr>
                <th>Courier / Order Metadata</th>
                <td>
                    <?php
                    $pills = explode('|', $metadataStr);
                    foreach ($pills as $pill) {
                        echo '<span class="meta-pill">' . e(trim($pill)) . '</span>';
                    }
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Purpose / Remarks</th>
                <td>
                    <?= nl2br(e(preg_replace('/\[Metadata: .*?\]/', '', $notes) ?: 'WhatsApp channel operations, courier shipping, or automation expenditure.')) ?>
                </td>
            </tr>
        </table>

        <div class="amount-box">
            <span style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: bold; display: block; letter-spacing: 0.5px;">Authorized Paid Amount</span>
            <span class="amount-val">₹<?= number_format((float)($exp['paid_amount'] ?? $exp['total_cost'] ?? 0), 2) ?></span>
        </div>

        <div class="signatures">
            <div class="sig-box">
                <?= e($exp['created_by_name'] ?? 'WhatsApp Channel Agent') ?><br>
                <span style="font-weight: normal; font-size: 11px; color: #64748b;">Prepared By</span>
            </div>
            <div class="sig-box">
                <?= e($exp['approved_by_name'] ?? 'E-Commerce Manager') ?><br>
                <span style="font-weight: normal; font-size: 11px; color: #64748b;">Authorized By</span>
            </div>
            <div class="sig-box">
                ___________________<br>
                <span style="font-weight: normal; font-size: 11px; color: #64748b;">Payee / Courier Receiver</span>
            </div>
        </div>
    </div>

</body>
</html>
