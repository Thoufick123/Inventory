<?php
$register = $shift ?? [];
$difference = (float)($register['cash_difference'] ?? 0);
$openingDenominations = json_decode((string)($register['opening_denominations'] ?? ''), true) ?: [];
$closingDenominations = json_decode((string)($register['closing_denominations'] ?? ''), true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Shift Slip — <?= e($register['register_number'] ?? 'Shift') ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; color: #111; font-family: "Courier New", monospace; }
        body { background: #f1f5f9; padding: 20px 0; }
        .receipt { width: 80mm; max-width: 80mm; margin: 0 auto; padding: 12px 10px; background: #fff; }
        .center { text-align: center; } .right { text-align: right; } .bold { font-weight: 700; }
        .divider { border-top: 1px dashed #111; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        td, th { padding: 3px 0; vertical-align: top; }
        .actions { margin: 0 auto 15px; text-align: center; }
        .actions button { padding: 8px 14px; border: 0; background: #14532d; color: white; cursor: pointer; }
        @media print { body { padding: 0; background: #fff; } .receipt { margin: 0; } .actions { display: none; } @page { size: 80mm auto; margin: 0; } }
    </style>
</head>
<body>
    <div class="actions"><button onclick="window.print()">Print shift slip</button> <button onclick="window.close()">Close</button></div>
    <main class="receipt">
        <header class="center"><h2><?= e($register['production_unit_name'] ?? 'Production Unit') ?></h2><p><?= e($register['production_unit_code'] ?? '') ?></p><div class="divider"></div><p class="bold">CASH SHIFT RECONCILIATION</p><div class="divider"></div></header>
        <table>
            <tr><td class="bold">Shift #</td><td class="right bold"><?= e($register['register_number']) ?></td></tr>
            <tr><td>Status</td><td class="right"><?= e($register['status']) ?></td></tr>
            <tr><td>Cashier</td><td class="right"><?= e($register['cashier_name'] ?? 'Staff') ?></td></tr>
            <tr><td>Opened</td><td class="right"><?= e(formatDateTime($register['opening_time'])) ?></td></tr>
            <?php if (!empty($register['closing_time'])): ?><tr><td>Closed</td><td class="right"><?= e(formatDateTime($register['closing_time'])) ?></td></tr><?php endif; ?>
        </table>
        <div class="divider"></div>
        <table>
            <tr><td>Opening float</td><td class="right">₹<?= number_format((float)$register['opening_balance'], 2) ?></td></tr>
            <tr><td>Cash sales (+)</td><td class="right">₹<?= number_format((float)($register['cash_sales_amount'] ?? 0), 2) ?></td></tr>
            <tr><td>Cash top-ups (+)</td><td class="right">₹<?= number_format((float)$register['cash_in_amount'], 2) ?></td></tr>
            <tr><td>Cash expenses (-)</td><td class="right">₹<?= number_format((float)$register['cash_out_amount'], 2) ?></td></tr>
            <tr><td>Cash refunds (-)</td><td class="right">₹<?= number_format((float)($register['cash_refund_amount'] ?? 0), 2) ?></td></tr>
            <tr><td>Bank drops (-)</td><td class="right">₹<?= number_format((float)$register['bank_deposit_amount'], 2) ?></td></tr>
            <tr class="bold"><td>Expected till</td><td class="right">₹<?= number_format((float)$register['expected_cash'], 2) ?></td></tr>
            <?php if ((float)($register['upi_sales_amount'] ?? 0) > 0): ?><tr><td>UPI sales (info)</td><td class="right">₹<?= number_format((float)$register['upi_sales_amount'], 2) ?></td></tr><?php endif; ?>
            <?php if ((float)($register['card_sales_amount'] ?? 0) > 0): ?><tr><td>Card sales (info)</td><td class="right">₹<?= number_format((float)$register['card_sales_amount'], 2) ?></td></tr><?php endif; ?>
            <?php if ($register['status'] === 'CLOSED'): ?>
                <tr class="bold"><td>Counted cash</td><td class="right">₹<?= number_format((float)$register['closing_cash_counted'], 2) ?></td></tr>
                <tr><td>Variance</td><td class="right"><?php if (abs($difference) < 0.01): ?>₹0.00 (MATCH)<?php elseif ($difference > 0): ?>+₹<?= number_format($difference, 2) ?> (OVERAGE)<?php else: ?>-₹<?= number_format(abs($difference), 2) ?> (SHORTAGE)<?php endif; ?></td></tr>
                <?php if (!empty($register['discrepancy_reason'])): ?><tr><td colspan="2">Reason: <?= e($register['discrepancy_reason']) ?></td></tr><?php endif; ?>
            <?php endif; ?>
        </table>
        <?php foreach (['Opening denomination count' => $openingDenominations, 'Closing denomination count' => $closingDenominations] as $label => $counts): ?>
            <?php if ($counts): ?><div class="divider"></div><p class="bold center"><?= e($label) ?></p><table><?php foreach ($counts as $denomination => $count): ?><tr><td><?= $denomination === 'coins' ? 'Coins' : '₹' . e((string)$denomination) ?></td><td class="right"><?= (int)$count ?></td></tr><?php endforeach; ?></table><?php endif; ?>
        <?php endforeach; ?>
        <div class="divider"></div><p class="center">Production cash audit copy</p>
    </main>
</body>
</html>