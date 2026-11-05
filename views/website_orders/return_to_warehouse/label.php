<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Return Carton QR Label — <?= e($return['return_number']) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
                margin: 0;
                max-width: 100%;
                width: 100%;
                padding: 16px;
            }
        }
    </style>
</head>
<body>

<div class="container my-3 no-print text-center">
    <button onclick="window.print()" class="btn btn-primary px-4 fw-semibold me-2">
        <i class="bi bi-printer me-1"></i> Print Carton QR Sticker
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary px-3">Close</button>
</div>

<div class="label-card">
    <!-- Header -->
    <div class="label-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-0 text-uppercase tracking-wider">GRM APPARELS</h4>
            <div class="text-info fw-bold small text-uppercase"><i class="bi bi-globe me-1"></i>WEBSITE ORDERS RETURN TO WAREHOUSE CARTON</div>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-dark border px-3 py-1 font-monospace fs-6">
                <i class="bi bi-box-seam me-1 text-primary"></i>CARTON DISPATCH
            </span>
        </div>
    </div>

    <!-- Main Content: Codes & Destination -->
    <div class="row g-3 mb-3 align-items-center">
        <!-- 2D QR Code -->
        <div class="col-4">
            <div class="qr-box h-100">
                <div id="cartonQrCode"></div>
                <small class="text-muted mt-1 font-monospace fw-bold" style="font-size: 11px;">SCAN AT WH</small>
            </div>
        </div>

        <!-- Manifest Details -->
        <div class="col-8">
            <div class="bg-light border rounded-3 p-2 mb-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Return Manifest No:</span>
                <span class="font-monospace fw-bold text-primary fs-6"><?= e($return['return_number']) ?></span>
            </div>
            <div class="row g-2 small">
                <div class="col-6">
                    <span class="text-muted d-block" style="font-size: 10px;">FROM CHANNEL:</span>
                    <strong class="text-dark"><?= e($return['channel_name'] ?? 'Website Channel') ?> (<?= e($return['channel_code'] ?? 'WE-01') ?>)</strong>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block" style="font-size: 10px;">TO WAREHOUSE:</span>
                    <strong class="text-dark"><?= e($return['warehouse_name']) ?></strong>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block" style="font-size: 10px;">REASON:</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis"><?= e($return['reason']) ?></span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block" style="font-size: 10px;">DISPATCH DATE:</span>
                    <strong class="text-dark"><?= formatDateTime($return['created_at']) ?></strong>
                </div>
                <?php if (!empty($return['outward_logistics']['carrier_name']) || !empty($return['outward_logistics']['vehicle_no'])): ?>
                <div class="col-6">
                    <span class="text-muted d-block" style="font-size: 10px;">TRANSPORTER:</span>
                    <strong class="text-dark"><?= e($return['outward_logistics']['carrier_name'] ?? 'Dedicated Logistics') ?></strong>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block" style="font-size: 10px;">VEHICLE / CARTONS:</span>
                    <strong class="text-dark"><?= !empty($return['outward_logistics']['vehicle_no']) ? e($return['outward_logistics']['vehicle_no']) : 'Handover' ?> (<?= e($return['outward_logistics']['carton_count'] ?? 1) ?> Ctn)</strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Variant Breakdown Table -->
    <div class="border rounded overflow-hidden mb-3">
        <table class="table table-sm table-bordered mb-0 align-middle small">
            <thead class="table-light text-uppercase" style="font-size: 10px;">
                <tr>
                    <th class="ps-2">SKU Code</th>
                    <th>Product &amp; Attributes</th>
                    <th class="text-center">Condition</th>
                    <th class="text-center" style="width: 80px;">Dispatched Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="ps-2 font-monospace fw-bold text-primary">
                            <?= e($item['sku']) ?>
                            <?php if (!empty($item['product_code'])): ?>
                                <small class="text-muted d-block font-monospace" style="font-size: 9.5px;">Code: <?= e($item['product_code']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong class="text-dark"><?= e($item['product_name']) ?></strong>
                            <span class="text-muted">
                                <?= !empty($item['size_name']) ? ' • ' . e($item['size_name']) : '' ?>
                                <?= !empty($item['color_name']) ? ' • ' . e($item['color_name']) : '' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border"><?= e($item['classification']) ?></span>
                        </td>
                        <td class="text-center fw-bold text-danger fs-6"><?= $item['returned_qty'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="ps-2 text-uppercase">Total Units in this Carton:</td>
                    <td class="text-center text-danger fs-6"><?= $totalReturnedQty ?> pcs</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Footer Note -->
    <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 10px;">
        <span>Carton Manifest ID: <strong class="font-monospace text-dark"><?= e($return['return_number']) ?></strong></span>
        <span>Generated: <?= date('d M Y, h:i A') ?></span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("cartonQrCode"), {
        text: "<?= e($return['return_number']) ?>",
        width: 110,
        height: 110,
        colorDark: "#0f172a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });
});
</script>
</body>
</html>
