<?php
$batchNumber = (string)($batch['batch_number'] ?? '');
$destination = $batch['destination_concern_name'] ?? $batch['warehouse_name'] ?? 'Unassigned';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Production Batch Label — <?= e($batchNumber) ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            background: #eef2f7;
            color: #172033;
            font-family: Arial, sans-serif;
        }
        .toolbar { max-width: 720px; margin: 0 auto 16px; text-align: right; }
        .toolbar button {
            border: 0;
            border-radius: 6px;
            padding: 10px 18px;
            background: #1769e0;
            color: #fff;
            font-size: 15px;
            cursor: pointer;
        }
        .label {
            width: 100%;
            max-width: 720px;
            margin: auto;
            padding: 28px;
            border: 2px solid #172033;
            border-radius: 10px;
            background: #fff;
        }
        .heading {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            border-bottom: 2px solid #172033;
            padding-bottom: 14px;
        }
        .heading h1 { margin: 0; font-size: 22px; }
        .status { border: 1px solid #9aa4b2; border-radius: 4px; padding: 4px 8px; font-size: 11px; }
        .details { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 20px; margin: 18px 0; }
        .detail-label { color: #596579; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .detail-value { margin-top: 3px; font-size: 16px; font-weight: bold; overflow-wrap: anywhere; }
        .batch-number { font-family: monospace; font-size: 23px; }
        .qr-section { display: flex; align-items: center; justify-content: center; gap: 24px; padding: 18px; border: 1px solid #c7ced8; }
        #batchQr { min-width: 160px; min-height: 160px; display: flex; align-items: center; justify-content: center; }
        #batchQr img, #batchQr canvas { max-width: 100%; }
        .qr-copy { text-align: center; overflow-wrap: anywhere; }
        .items { width: 100%; margin-top: 18px; border-collapse: collapse; font-size: 12px; }
        .items th, .items td { border: 1px solid #9aa4b2; padding: 6px; text-align: left; }
        .items th { background: #edf1f6; }
        .items .qty { text-align: right; }
        .footer { margin-top: 14px; display: flex; justify-content: space-between; gap: 8px; color: #596579; font-size: 10px; }
        @media print {
            @page { margin: 8mm; }
            body { padding: 0; background: #fff; }
            .toolbar { display: none; }
            .label { max-width: none; border-radius: 0; }
        }
        @media (max-width: 520px) {
            body { padding: 10px; }
            .label { padding: 16px; }
            .details { grid-template-columns: 1fr; }
            .qr-section { flex-direction: column; gap: 8px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print Batch Label</button>
    </div>
    <main class="label">
        <header class="heading">
            <div>
                <h1>Production Batch</h1>
                <div class="detail-value batch-number"><?= e($batchNumber) ?></div>
            </div>
            <span class="status"><?= e($batch['status'] ?? 'PREPARING') ?></span>
        </header>

        <section class="details">
            <div>
                <div class="detail-label">Product</div>
                <div class="detail-value"><?= e($batch['product_name'] ?? 'Finished Product') ?></div>
            </div>
            <div>
                <div class="detail-label">Program</div>
                <div class="detail-value"><?= e($batch['program_number'] ?? '—') ?></div>
            </div>
            <div>
                <div class="detail-label">Destination Concern</div>
                <div class="detail-value"><?= e($destination) ?></div>
            </div>
            <div>
                <div class="detail-label">Quantity / Cartons</div>
                <div class="detail-value"><?= number_format((int)($batch['total_qty'] ?? 0)) ?> pcs / <?= number_format((int)($batch['box_count'] ?? 1)) ?> carton(s)</div>
            </div>
        </section>

        <section class="qr-section">
            <div id="batchQr" role="img" aria-label="QR code for batch <?= e($batchNumber) ?>"></div>
            <div class="qr-copy">
                <div class="detail-label">Scan batch number</div>
                <div class="detail-value batch-number"><?= e($batchNumber) ?></div>
            </div>
        </section>

        <?php if (!empty($items)): ?>
            <table class="items">
                <thead>
                    <tr><th>SKU</th><th>Size</th><th>Color</th><th class="qty">Qty</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['sku'] ?? '—') ?></td>
                            <td><?= e($item['size_name'] ?? 'Std') ?></td>
                            <td><?= e($item['color_name'] ?? 'Std') ?></td>
                            <td class="qty"><?= number_format((int)($item['quantity'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <footer class="footer">
            <span>Prepared by: <?= e($batch['prepared_by_name'] ?? 'Production') ?></span>
            <span><?= !empty($batch['created_at']) ? e(formatDate($batch['created_at'])) : '' ?></span>
        </footer>
    </main>

    <script>
    (() => {
        const container = document.getElementById('batchQr');
        const code = <?= json_encode($batchNumber, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        if (typeof QRCode === 'undefined') {
            container.textContent = 'QR code could not be loaded. Check your internet connection and reload.';
            container.style.color = '#b42318';
            return;
        }
        new QRCode(container, {
            text: code,
            width: 160,
            height: 160,
            correctLevel: QRCode.CorrectLevel.M
        });
    })();
    </script>
</body>
</html>
