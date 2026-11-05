<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Carton QR Label — <?= e($allocation['allocation_number']) ?></title>
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
            border: 2px solid #0f172a;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        .label-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .qr-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
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
            }
            .no-print {
                display: none !important;
            }
            .label-card {
                box-shadow: none;
                border: 2px solid #000;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="no-print text-center mb-3">
    <button onclick="window.print()" class="btn btn-primary btn-lg me-2">
        <i class="bi bi-printer me-1"></i> Print Shop Carton QR Sticker
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-lg">
        <i class="bi bi-x-lg me-1"></i> Close
    </button>
</div>

<div class="label-card">
    <!-- Header -->
    <div class="label-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold text-uppercase tracking-wider">GRM RETAIL INWARD</h4>
            <span class="badge bg-dark text-uppercase">Shop Stock Receiving Tag</span>
        </div>
        <div class="text-end">
            <span class="text-muted small d-block">Destination Shop:</span>
            <span class="fw-bold fs-6 text-primary"><?= e($shop['name'] ?? 'Retail Store') ?> (<?= e($shop['code'] ?? 'SHOP') ?>)</span>
        </div>
    </div>

    <!-- Main Content & QR / Barcode Grid -->
    <?php
        $totalQty = 0;
        foreach ($items as $it) {
            $totalQty += (int)($it['dispatched_qty'] > 0 ? $it['dispatched_qty'] : $it['requested_qty']);
        }
        $firstProduct = $items[0]['product_name'] ?? 'Assorted Apparels';
    ?>
    <div class="row g-3 align-items-center mb-3">
        <div class="col-7">
            <div class="mb-2">
                <span class="text-muted small d-block text-uppercase fw-semibold">Allocation Number:</span>
                <span class="fw-bold font-monospace fs-4 text-dark"><?= e($allocation['allocation_number']) ?></span>
            </div>
            <div class="mb-2">
                <span class="text-muted small d-block text-uppercase fw-semibold">Source Warehouse &amp; Vehicle:</span>
                <span class="fw-bold fs-5 text-primary"><?= e($warehouse['name'] ?? 'Central Warehouse') ?></span>
                <?php if (!empty($outward['vehicle_number'])): ?>
                    <span class="badge bg-secondary ms-1"><i class="bi bi-truck me-1"></i><?= e($outward['vehicle_number']) ?></span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-4">
                <div>
                    <span class="text-muted small d-block text-uppercase fw-semibold">Total Quantity:</span>
                    <span class="fw-bold fs-4 text-success"><?= number_format($totalQty) ?> Pcs</span>
                </div>
                <div>
                    <span class="text-muted small d-block text-uppercase fw-semibold">Carton Count:</span>
                    <span class="fw-bold fs-5"><?= number_format($outward['total_boxes'] ?? 1) ?> Box(es)</span>
                </div>
            </div>
        </div>
        <div class="col-5">
            <div class="qr-box">
                <div id="qrcode"></div>
                <small class="font-monospace text-muted mt-2 fw-bold" style="font-size: 10px;"><?= e($allocation['allocation_number']) ?></small>
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
                    <th>SKU Code</th>
                    <th>Product / Style</th>
                    <th>Size</th>
                    <th>Color</th>
                    <th>Qty (Pcs)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <?php $qty = (int)($it['dispatched_qty'] > 0 ? $it['dispatched_qty'] : $it['requested_qty']); ?>
                    <tr>
                        <td class="font-monospace fw-semibold text-start ps-2"><?= e($it['sku']) ?></td>
                        <td class="text-start ps-2"><small><?= e($it['product_name']) ?></small></td>
                        <td><span class="fw-bold"><?= e($it['size_name'] ?? 'Free') ?></span></td>
                        <td><?= e($it['color_name'] ?? 'Std') ?></td>
                        <td class="fw-bold text-success fs-6"><?= number_format($qty) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Footer metadata -->
    <div class="d-flex justify-content-between align-items-center text-muted small border-top pt-2">
        <span><i class="bi bi-calendar3 me-1"></i> Generated: <?= date('d M Y, h:i A') ?></span>
        <span><i class="bi bi-shield-check me-1"></i> Verified GRM Logistics System</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const allocNum = <?= json_encode($allocation['allocation_number']) ?>;
    
    // Render 2D QR Code
    new QRCode(document.getElementById("qrcode"), {
        text: allocNum,
        width: 120,
        height: 120,
        colorDark : "#0f172a",
        colorLight : "#f8fafc",
        correctLevel : QRCode.CorrectLevel.H
    });

    // Render 1D Code128 Barcode
    JsBarcode("#barcode", allocNum, {
        format: "CODE128",
        width: 2,
        height: 40,
        displayValue: true,
        font: "Inter",
        fontSize: 12,
        lineColor: "#0f172a"
    });
});
</script>

</body>
</html>
