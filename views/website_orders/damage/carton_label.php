<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Damaged Carton QR Label — <?= htmlspecialchars($batch['return_number'] ?? ('RET-WEB-' . $batch['id'])) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            padding: 20px;
        }
        .label-card {
            max-width: 680px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        .label-header {
            border-bottom: 2px solid #dc3545;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .qr-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #fff5f5;
            border: 1px dashed #dc3545;
            padding: 12px;
            border-radius: 6px;
        }
        .barcode-box {
            text-align: center;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 6px 12px;
            border-radius: 6px;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .label-card {
                box-shadow: none;
                border: 2px solid #000;
                page-break-inside: avoid;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

<div class="no-print text-center mb-3">
    <button onclick="window.print()" class="btn btn-danger btn-lg me-2">
        <i class="bi bi-printer me-1"></i> Print Damaged Carton QR Sticker
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-lg">
        <i class="bi bi-x-lg me-1"></i> Close
    </button>
</div>

<div class="label-card">
    <!-- Header -->
    <div class="label-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold text-danger text-uppercase tracking-wider">GRM DAMAGED STOCK RETURN</h4>
            <span class="badge bg-danger text-uppercase">Website Division &rarr; Central Warehouse</span>
        </div>
        <div class="text-end">
            <span class="text-muted small d-block">Target Warehouse:</span>
            <span class="fw-bold fs-6 text-dark"><?= htmlspecialchars($batch['warehouse_name'] ?? 'Main Warehouse') ?> (<?= htmlspecialchars($batch['warehouse_code'] ?? 'WH') ?>)</span>
        </div>
    </div>

    <!-- Main Content & QR / Barcode Grid -->
    <div class="row g-3 align-items-center mb-3">
        <div class="col-7">
            <div class="mb-2">
                <span class="text-muted small d-block text-uppercase fw-semibold">Return Batch Number:</span>
                <span class="fw-bold font-monospace fs-4 text-danger"><?= htmlspecialchars($batch['return_number'] ?? ('RET-WEB-' . $batch['id'])) ?></span>
            </div>
            <div class="mb-2">
                <span class="text-muted small d-block text-uppercase fw-semibold">Source Division:</span>
                <span class="fw-semibold text-dark"><i class="bi bi-globe text-primary me-1"></i>Website Online Division</span>
            </div>
            <div class="d-flex gap-4">
                <div>
                    <span class="text-muted small d-block text-uppercase fw-semibold">Total Damaged Qty:</span>
                    <span class="fw-bold fs-4 text-danger"><?= number_format($batch['total_quantity'] ?? 0) ?> Pcs</span>
                </div>
                <div>
                    <span class="text-muted small d-block text-uppercase fw-semibold">Items Count:</span>
                    <span class="fw-bold fs-5 text-dark"><?= number_format(count($items ?? [])) ?> SKU(s)</span>
                </div>
            </div>
        </div>
        <div class="col-5">
            <div class="qr-box">
                <div id="qrcode"></div>
                <small class="font-monospace text-muted mt-2 fw-bold" style="font-size: 10px;"><?= htmlspecialchars($batch['return_number'] ?? ('RET-WEB-' . $batch['id'])) ?></small>
            </div>
        </div>
    </div>

    <!-- 1D Linear Barcode Banner -->
    <div class="barcode-box mb-3">
        <svg id="barcode"></svg>
    </div>

    <!-- Variant Breakdown Table -->
    <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered mb-0 text-center align-middle" style="border-color: #0f172a;">
            <thead class="table-dark">
                <tr>
                    <th class="text-start ps-2">Product Name</th>
                    <th>SKU Code</th>
                    <th>Variant</th>
                    <th>Defect</th>
                    <th style="width: 80px;">Qty (Pcs)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td class="text-start ps-2 fw-semibold"><?= htmlspecialchars($it['product_name'] ?? 'Product #' . $it['variant_id']) ?></td>
                        <td class="font-monospace small"><?= htmlspecialchars($it['variant_sku'] ?? $it['sku'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars(($it['color'] ?? '') . ' / ' . ($it['size'] ?? '')) ?></td>
                        <td>
                            <span class="badge bg-danger-subtle text-danger border"><?= htmlspecialchars($it['reason'] ?? $it['defect_type'] ?? 'DAMAGED') ?></span>
                        </td>
                        <td class="fw-bold text-danger fs-6"><?= number_format($it['quantity'] ?? $it['returned_qty'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Footer metadata -->
    <div class="d-flex justify-content-between align-items-center text-muted small pt-2 border-top">
        <span>Dispatched By: <strong><?= htmlspecialchars($batch['created_by_name'] ?? 'Website Team') ?></strong></span>
        <span>Date: <strong><?= date('d M Y, h:i A', strtotime($batch['created_at'])) ?></strong></span>
        <span class="badge bg-danger text-white">STATUS: <?= htmlspecialchars($batch['status'] ?? 'IN_TRANSIT') ?></span>
    </div>
</div>

<?php
$batchNum = $batch['return_number'] ?? ('RET-WEB-' . $batch['id']);
$qrPayload = json_encode([
    'type'          => 'WEBSITE_RETURN',
    'return_number' => $batchNum,
    'source'        => 'WEBSITE',
    'warehouse_id'  => (int)($batch['warehouse_id'] ?? 0),
    'total_qty'     => (int)($batch['total_quantity'] ?? 0)
], JSON_UNESCAPED_SLASHES);
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode"), {
        text: <?= json_encode($qrPayload) ?>,
        width: 120,
        height: 120,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });

    JsBarcode("#barcode", "<?= htmlspecialchars($batchNum) ?>", {
        format: "CODE128",
        width: 1.8,
        height: 40,
        displayValue: true,
        fontSize: 12,
        margin: 4
    });
});
</script>
</body>
</html>
