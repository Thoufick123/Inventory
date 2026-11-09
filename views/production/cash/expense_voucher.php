<?php $expense = $expense ?? []; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Cash Voucher — <?= e($expense['voucher_number'] ?? 'Voucher') ?></title>
    <style>
        * { box-sizing: border-box; color: #15202b; font-family: Arial, sans-serif; }
        body { margin: 0; padding: 28px; background: #f1f5f9; }
        .voucher { max-width: 680px; margin: auto; padding: 28px; background: #fff; border: 1px solid #cbd5e1; }
        .header { display: flex; justify-content: space-between; gap: 16px; padding-bottom: 16px; margin-bottom: 18px; border-bottom: 2px solid #e2e8f0; }
        .right { text-align: right; } .bold { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        th, td { padding: 10px; text-align: left; border: 1px solid #e2e8f0; font-size: 13px; }
        th { width: 34%; background: #f8fafc; }
        .amount { padding: 16px; margin: 18px 0 28px; text-align: center; border: 2px dashed #15803d; }
        .amount strong { display: block; margin-top: 4px; font-size: 28px; }
        .signatures { display: flex; justify-content: space-between; gap: 18px; margin-top: 50px; }
        .signature { width: 32%; padding-top: 6px; border-top: 1px solid #64748b; text-align: center; font-size: 12px; }
        .actions { margin: 0 auto 16px; text-align: center; }
        .actions button { padding: 9px 16px; border: 0; background: #14532d; color: white; cursor: pointer; }
        @media print { body { padding: 0; background: #fff; } .voucher { border: 0; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions"><button onclick="window.print()">Print payment voucher</button> <button onclick="window.close()">Close</button></div>
    <main class="voucher">
        <header class="header"><div><h2><?= e($expense['production_unit_name'] ?? 'Production Unit') ?></h2><p><?= e($expense['production_unit_code'] ?? '') ?></p></div><div class="right"><strong>PRODUCTION CASH VOUCHER</strong><div><?= e($expense['voucher_number']) ?></div></div></header>
        <table>
            <tr><th>Voucher date</th><td><?= e(formatDateTime($expense['created_at'])) ?></td></tr>
            <tr><th>Expense category</th><td><?= e($expense['expense_category']) ?></td></tr>
            <tr><th>Paid to</th><td><?= e($expense['paid_to']) ?></td></tr>
            <tr><th>Payment mode</th><td><?= e($expense['payment_mode']) ?> · Shift <?= e($expense['register_number'] ?? 'Direct') ?></td></tr>
            <tr><th>Bill / reference</th><td><?= e($expense['bill_number'] ?: 'N/A') ?></td></tr>
            <tr><th>Description</th><td><?= nl2br(e($expense['notes'] ?: 'Production operational cash expense')) ?></td></tr>
            <tr><th>Recorded by</th><td><?= e($expense['staff_name'] ?? 'Staff') ?></td></tr>
            <tr><th>Authorized by</th><td><?= e($expense['approver_name'] ?? 'Production Manager') ?></td></tr>
        </table>
        <div class="amount">Total paid amount<strong>₹<?= number_format((float)$expense['amount'], 2) ?></strong></div>
        <div class="signatures"><div class="signature">Prepared by</div><div class="signature">Authorized by</div><div class="signature">Received by</div></div>
    </main>
</body>
</html>