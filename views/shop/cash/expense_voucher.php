<?php
// views/shop/cash/expense_voucher.php — Printable Cash Payment Voucher (CPV)
$exp = $expense ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Payment Voucher — <?= e($exp['voucher_number'] ?? 'CPV') ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #1e293b;
        }
        body {
            background-color: #f1f5f9;
            padding: 30px;
        }
        .voucher-box {
            max-width: 650px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            border: 2px solid #cbd5e1;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
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
            align-items: center;
        }
        .title-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
        }
        .data-table th {
            background-color: #f8fafc;
            width: 30%;
            text-align: left;
        }

        .amount-box {
            background: #f8fafc;
            border: 2px dashed #0284c7;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin-bottom: 25px;
        }
        .amount-val {
            font-size: 28px;
            font-weight: 900;
            color: #0f172a;
        }

        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .sig-box {
            text-align: center;
            width: 30%;
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
            font-size: 12px;
            font-weight: bold;
        }

        .actions-bar {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 8px 18px;
            background: #4f46e5;
            color: #fff;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
            cursor: pointer;
            border: none;
            margin: 0 4px;
        }
        .btn-secondary {
            background: #64748b;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .voucher-box { box-shadow: none; border: 1px solid #94a3b8; }
            .actions-bar { display: none; }
        }
    </style>
</head>
<body>

    <div class="actions-bar">
        <button onclick="window.print()" class="btn">🖨️ Print Payment Voucher</button>
        <button onclick="window.close()" class="btn btn-secondary">✕ Close</button>
    </div>

    <div class="voucher-box">
        <div class="header">
            <div>
                <h2 style="font-size: 18px; font-weight: 800; color: #0f172a;"><?= e($exp['shop_name'] ?? 'GRM RETAIL STORE') ?></h2>
                <p style="font-size: 11px; color: #64748b;"><?= e($exp['shop_address'] ?? 'Tirupur, Tamil Nadu') ?></p>
            </div>
            <div class="text-end">
                <span class="title-badge">PETTY CASH VOUCHER</span>
                <div style="font-family: monospace; font-size: 14px; font-weight: bold; margin-top: 5px; color: #0f172a;"><?= e($exp['voucher_number']) ?></div>
            </div>
        </div>

        <table class="data-table">
            <tr>
                <th>Voucher Date &amp; Time</th>
                <td><?= formatDateTime($exp['created_at']) ?></td>
            </tr>
            <tr>
                <th>Expense Category</th>
                <td class="fw-bold"><?= e($exp['expense_category']) ?></td>
            </tr>
            <tr>
                <th>Paid To (Vendor / Person)</th>
                <td class="fw-bold" style="font-size: 14px; color: #0f172a;"><?= e($exp['paid_to']) ?></td>
            </tr>
            <tr>
                <th>Payment Mode</th>
                <td><strong><?= e($exp['payment_mode']) ?></strong> (Register Shift: <?= e($exp['register_number'] ?? 'Direct Till') ?>)</td>
            </tr>
            <tr>
                <th>Bill / Cash Memo / Ref #</th>
                <td style="font-family: monospace;"><?= e($exp['bill_number'] ?: 'N/A') ?></td>
            </tr>
            <tr>
                <th>Purpose / Description</th>
                <td><?= e($exp['notes'] ?: 'Petty cash payment for shop operational expenses.') ?></td>
            </tr>
        </table>

        <div class="amount-box">
            <span style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: bold; display: block;">Total Paid Amount</span>
            <span class="amount-val">₹<?= number_format((float)$exp['amount'], 2) ?></span>
        </div>

        <div class="signatures">
            <div class="sig-box">
                <?= e($exp['staff_name'] ?? 'Cashier') ?><br>
                <span style="font-weight: normal; font-size: 10px; color: #64748b;">Prepared By</span>
            </div>
            <div class="sig-box">
                <?= e($exp['approver_name'] ?? 'Store Manager') ?><br>
                <span style="font-weight: normal; font-size: 10px; color: #64748b;">Authorized By</span>
            </div>
            <div class="sig-box">
                ___________________<br>
                <span style="font-weight: normal; font-size: 10px; color: #64748b;">Receiver's Signature</span>
            </div>
        </div>
    </div>

</body>
</html>
