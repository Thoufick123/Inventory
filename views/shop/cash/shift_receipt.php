<?php
// views/shop/cash/shift_receipt.php — Exact 80mm/58mm Thermal Print Layout for Shift & Till Reconciliation
$sft = $shift ?? [];
$diff = (float)($sft['cash_difference'] ?? 0);
$openDenoms = !empty($sft['opening_denominations']) ? json_decode($sft['opening_denominations'], true) : [];
$closeDenoms = !empty($sft['closing_denominations']) ? json_decode($sft['closing_denominations'], true) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Summary — <?= e($sft['register_number'] ?? 'Shift') ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
            color: #000;
        }
        body {
            background-color: #f5f5f5;
            padding: 20px 0;
        }
        .receipt-container {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            background: #fff;
            padding: 12px 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .double-divider {
            border-top: 2px solid #000;
            margin: 8px 0;
        }

        .header h2 {
            font-size: 16px;
            font-weight: 900;
            margin-bottom: 2px;
        }
        .header p {
            font-size: 10px;
            line-height: 1.3;
        }

        .meta-table, .data-table, .denom-table {
            width: 100%;
            font-size: 11px;
            border-collapse: collapse;
        }
        .meta-table td, .data-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .denom-table th, .denom-table td {
            padding: 2px;
            font-size: 10px;
        }

        .total-box {
            background: #eee;
            padding: 6px;
            margin: 8px 0;
            font-size: 13px;
        }

        .actions-bar {
            text-align: center;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #4f46e5;
            color: #fff;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            border: none;
            margin: 0 4px;
        }
        .btn-secondary {
            background: #64748b;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .receipt-container {
                width: 80mm;
                max-width: 80mm;
                box-shadow: none;
                padding: 5px;
            }
            .actions-bar {
                display: none;
            }
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <div class="actions-bar">
        <button onclick="window.print()" class="btn">🖨️ Print Shift Slip</button>
        <button onclick="window.close()" class="btn btn-secondary">✕ Close</button>
    </div>

    <div class="receipt-container">
        <!-- Header -->
        <div class="header text-center">
            <h2><?= e($sft['shop_name'] ?? 'GRM RETAIL STORE') ?></h2>
            <p><?= e($sft['shop_address'] ?? 'Tirupur, Tamil Nadu') ?></p>
            <p>Phone: <?= e($sft['shop_phone'] ?? '+91 98765 43210') ?> | GST: <?= e($sft['shop_gst'] ?? 'N/A') ?></p>
            <div class="double-divider"></div>
            <p class="fw-bold" style="font-size: 12px;">DAILY SHIFT RECONCILIATION SLIP</p>
            <div class="divider"></div>
        </div>

        <!-- Meta -->
        <table class="meta-table">
            <tr>
                <td class="fw-bold">Shift #:</td>
                <td class="text-end fw-bold"><?= e($sft['register_number']) ?></td>
            </tr>
            <tr>
                <td>Status:</td>
                <td class="text-end fw-bold"><?= e($sft['status']) ?></td>
            </tr>
            <tr>
                <td>Cashier (Open):</td>
                <td class="text-end"><?= e($sft['open_cashier_name'] ?? 'Admin') ?></td>
            </tr>
            <tr>
                <td>Opened At:</td>
                <td class="text-end"><?= formatDateTime($sft['opening_time']) ?></td>
            </tr>
            <?php if (!empty($sft['closing_time'])): ?>
                <tr>
                    <td>Cashier (Close):</td>
                    <td class="text-end"><?= e($sft['close_cashier_name'] ?? $sft['open_cashier_name']) ?></td>
                </tr>
                <tr>
                    <td>Closed At:</td>
                    <td class="text-end"><?= formatDateTime($sft['closing_time']) ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="double-divider"></div>
        <div class="text-center fw-bold" style="font-size: 11px;">CASH TILL MOVEMENTS</div>
        <div class="divider"></div>

        <!-- Movement Table -->
        <table class="data-table">
            <tr>
                <td>Opening Cash Float:</td>
                <td class="text-end fw-bold">₹<?= number_format((float)$sft['opening_balance'], 2) ?></td>
            </tr>
            <tr>
                <td>Cash Sales (+):</td>
                <td class="text-end fw-bold">₹<?= number_format((float)$sft['cash_sales_amount'], 2) ?></td>
            </tr>
            <tr>
                <td>Cash Top-Up / In (+):</td>
                <td class="text-end">₹<?= number_format((float)$sft['cash_in_amount'], 2) ?></td>
            </tr>
            <tr>
                <td>Petty Cash Expenses (-):</td>
                <td class="text-end">₹<?= number_format((float)$sft['cash_out_amount'], 2) ?></td>
            </tr>
            <tr>
                <td>Customer Cash Refunds (-):</td>
                <td class="text-end">₹<?= number_format((float)$sft['cash_refund_amount'], 2) ?></td>
            </tr>
            <tr>
                <td>Bank Cash Drops (-):</td>
                <td class="text-end">₹<?= number_format((float)$sft['bank_deposit_amount'], 2) ?></td>
            </tr>
            <tr class="double-divider"><td colspan="2"></td></tr>
            <tr class="fw-bold" style="font-size: 12px;">
                <td>Expected Till Cash:</td>
                <td class="text-end">₹<?= number_format((float)$sft['expected_cash'], 2) ?></td>
            </tr>
            <?php if ($sft['status'] === 'CLOSED'): ?>
                <tr class="fw-bold" style="font-size: 12px;">
                    <td>Physical Counted Cash:</td>
                    <td class="text-end">₹<?= number_format((float)$sft['closing_cash_counted'], 2) ?></td>
                </tr>
                <tr>
                    <td>Discrepancy / Variance:</td>
                    <td class="text-end fw-bold">
                        <?php if (abs($diff) < 0.01): ?>
                            ₹0.00 (EXACT MATCH)
                        <?php elseif ($diff > 0): ?>
                            +₹<?= number_format($diff, 2) ?> (OVERAGE)
                        <?php else: ?>
                            -₹<?= number_format(abs($diff), 2) ?> (SHORTAGE)
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($sft['discrepancy_reason'])): ?>
                    <tr>
                        <td colspan="2" style="font-size: 9px; font-style: italic;">
                            Reason: <?= e($sft['discrepancy_reason']) ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
        </table>

        <!-- Digital Breakdown -->
        <div class="divider"></div>
        <div class="text-center fw-bold" style="font-size: 10px;">NON-CASH DIGITAL SALES</div>
        <div class="divider"></div>
        <table class="data-table">
            <tr>
                <td>UPI QR Payments:</td>
                <td class="text-end fw-bold">₹<?= number_format((float)$sft['upi_sales_amount'], 2) ?></td>
            </tr>
            <tr>
                <td>Card POS Swipes:</td>
                <td class="text-end fw-bold">₹<?= number_format((float)$sft['card_sales_amount'], 2) ?></td>
            </tr>
            <tr class="fw-bold">
                <td>Total Non-Cash Revenue:</td>
                <td class="text-end">₹<?= number_format((float)$sft['upi_sales_amount'] + (float)$sft['card_sales_amount'], 2) ?></td>
            </tr>
        </table>

        <!-- Physical Denomination Table if available -->
        <?php if (!empty($closeDenoms)): ?>
            <div class="divider"></div>
            <div class="text-center fw-bold" style="font-size: 10px;">PHYSICAL DENOMINATION COUNT</div>
            <div class="divider"></div>
            <table class="denom-table">
                <thead>
                    <tr style="border-bottom: 1px solid #000;">
                        <th class="text-start">Note</th>
                        <th class="text-center">Count</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $denomSum = 0;
                    foreach ($closeDenoms as $note => $cnt):
                        $val = ($note === 'coins') ? 1 : (float)$note;
                        $sub = $val * (int)$cnt;
                        $denomSum += $sub;
                    ?>
                        <tr>
                            <td><?= $note === 'coins' ? 'Coins' : '₹' . $note ?></td>
                            <td class="text-center"><?= $cnt ?></td>
                            <td class="text-end">₹<?= number_format($sub, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Signatures -->
        <div class="double-divider" style="margin-top: 25px;"></div>
        <table style="width: 100%; font-size: 10px; margin-top: 20px;">
            <tr>
                <td style="width: 50%; text-align: center;">
                    ___________________<br>
                    <strong>Cashier Signature</strong>
                </td>
                <td style="width: 50%; text-align: center;">
                    ___________________<br>
                    <strong>Manager Signature</strong>
                </td>
            </tr>
        </table>

        <div class="divider" style="margin-top: 15px;"></div>
        <div class="text-center" style="font-size: 9px;">
            <p>Printed: <?= date('d M Y, h:i A') ?></p>
            <p>GRM Inventory Core OS</p>
        </div>
    </div>

</body>
</html>
