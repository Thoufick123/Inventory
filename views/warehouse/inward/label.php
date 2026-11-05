<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Carton QR Label — <?= e($batch['batch_number']) ?></title>
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
        .fabric-thumb-merged {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: inline-block;
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
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>

<div class="no-print text-center mb-3">
    <button onclick="window.print()" class="btn btn-primary btn-lg me-2">
        <i class="bi bi-printer me-1"></i> Print Warehouse Carton QR Sticker
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-lg">
        <i class="bi bi-x-lg me-1"></i> Close
    </button>
</div>

<div class="label-card">
    <!-- Header -->
    <div class="label-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold text-uppercase tracking-wider">GRM WAREHOUSE INWARD</h4>
            <span class="badge bg-dark text-uppercase">Production Batch Receiving Tag</span>
        </div>
        <div class="text-end">
            <span class="text-muted small d-block">Origin &rarr; Target Warehouse:</span>
            <span class="badge bg-warning text-dark me-1 font-monospace"><?= e($batch['production_unit_code'] ?? 'PU1') ?></span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="fw-bold fs-6 text-primary ms-1"><?= e($batch['warehouse_name']) ?> (<?= e($batch['warehouse_code']) ?>)</span>
        </div>
    </div>

    <!-- Main Content & QR / Barcode Grid -->
    <div class="row g-3 align-items-center mb-3">
        <div class="col-7">
            <div class="mb-2">
                <span class="text-muted small d-block text-uppercase fw-semibold">Production Batch Number:</span>
                <span class="fw-bold font-monospace fs-4 text-dark"><?= e($batch['batch_number']) ?></span>
            </div>
            <div class="mb-2">
                <span class="text-muted small d-block text-uppercase fw-semibold">Origin Production Facility:</span>
                <span class="fw-semibold text-dark"><i class="bi bi-gear-wide-connected text-warning me-1"></i><?= e($batch['production_unit_name'] ?? 'Main Production Unit') ?></span>
            </div>
            <div class="mb-2">
                <span class="text-muted small d-block text-uppercase fw-semibold">Product Style & Program:</span>
                <span class="fw-bold fs-5 text-primary"><?= e($batch['product_name']) ?></span>
                <span class="badge bg-secondary ms-1">PRG: <?= e($batch['program_number']) ?></span>
            </div>
            <div class="d-flex gap-4">
                <div>
                    <span class="text-muted small d-block text-uppercase fw-semibold">Total Quantity:</span>
                    <span class="fw-bold fs-4 text-success"><?= number_format($batch['total_qty']) ?> Pcs</span>
                </div>
                <div>
                    <span class="text-muted small d-block text-uppercase fw-semibold">Carton Count:</span>
                    <span class="fw-bold fs-5"><?= number_format($batch['box_count'] ?? 1) ?> Box(es)</span>
                </div>
            </div>
        </div>
        <div class="col-5">
            <div class="qr-box">
                <div id="qrcode"></div>
                <small class="font-monospace text-muted mt-2 fw-bold" style="font-size: 10px;"><?= e($batch['batch_number']) ?></small>
            </div>
        </div>
    </div>

    <!-- 1D Linear Barcode Banner -->
    <div class="barcode-box mb-3">
        <svg id="barcode"></svg>
    </div>

    <?php
    // Group items by fabric/color so one single image is rendered for all sizes of the same color
    $groupedItems = [];
    foreach ($items as $it) {
        $colorKey = $it['color_name'] ?? 'Standard';
        if (!isset($groupedItems[$colorKey])) {
            $groupedItems[$colorKey] = [
                'color_name'         => $it['color_name'] ?? '',
                'fabric_photo'       => $it['fabric_photo'] ?? null,
                'fabric_design'      => $it['fabric_design'] ?? null,
                'fabric_roll_number' => $it['fabric_roll_number'] ?? null,
                'fabric_seller_name' => $it['fabric_seller_name'] ?? null,
                'total_qty'          => 0,
                'items'              => []
            ];
        }
        $groupedItems[$colorKey]['total_qty'] += (int)($it['quantity'] ?? 0);
        $groupedItems[$colorKey]['items'][] = $it;
    }
    ?>

    <!-- Variant Breakdown Table with One Fabric Image Per Color -->
    <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered mb-0 text-center align-middle" style="border-color: #0f172a;">
            <thead class="table-dark">
                <tr>
                    <th style="width: 70px;">Fabric</th>
                    <th style="width: 120px;">Color &amp; Design</th>
                    <th>SKU Code</th>
                    <th style="width: 60px;">Size</th>
                    <th style="width: 90px;">Qty (Pcs)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupedItems as $grp): ?>
                    <?php 
                        $rowCount = count($grp['items']); 
                        foreach ($grp['items'] as $idx => $it):
                    ?>
                        <tr>
                            <?php if ($idx === 0): ?>
                                <td rowspan="<?= $rowCount ?>" class="p-2 align-middle text-center bg-white" style="vertical-align: middle;">
                                    <?php if (!empty($grp['fabric_photo'])): ?>
                                        <img src="<?= e(uploadUrl($grp['fabric_photo'])) ?>" 
                                             alt="<?= e($grp['color_name']) ?>" 
                                             class="fabric-thumb-merged"
                                             loading="lazy"
                                             onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'bg-light border rounded d-inline-flex align-items-center justify-content-center text-muted\' style=\'width: 48px; height: 48px; font-size: 11px;\' title=\'Image failed to load\'><i class=\'bi bi-image text-secondary fs-5\'></i></div>';"
                                             title="<?= e($grp['color_name']) ?> <?= !empty($grp['fabric_design']) ? '[' . e($grp['fabric_design']) . ']' : '' ?>">
                                    <?php else: ?>
                                        <div class="bg-light border rounded d-inline-flex align-items-center justify-content-center text-muted" 
                                             style="width: 48px; height: 48px; font-size: 11px;" title="No image">
                                            <i class="bi bi-image text-secondary fs-5"></i>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($grp['fabric_roll_number'])): ?>
                                        <div class="mt-1 font-monospace text-muted" style="font-size: 9px; font-weight: 600;"><?= e($grp['fabric_roll_number']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td rowspan="<?= $rowCount ?>" class="align-middle text-center bg-white" style="vertical-align: middle;">
                                    <span class="fw-bold fs-6 d-block text-dark"><?= e($grp['color_name']) ?></span>
                                    <?php if (!empty($grp['fabric_design'])): ?>
                                        <span class="badge bg-light text-secondary border mt-1" style="font-size: 10px; font-weight: 600;"><?= e($grp['fabric_design']) ?></span>
                                    <?php endif; ?>
                                    <div class="text-muted small mt-1" style="font-size: 10px;">
                                        Total: <strong class="text-success"><?= number_format($grp['total_qty']) ?></strong> pcs
                                    </div>
                                </td>
                            <?php endif; ?>
                            <td class="font-monospace fw-semibold text-start ps-2">
                                <?= e($it['sku']) ?>
                            </td>
                            <td><span class="fw-bold"><?= e($it['size_name']) ?></span></td>
                            <td class="fw-bold text-success fs-6"><?= number_format($it['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Footer metadata -->
    <div class="d-flex justify-content-between align-items-center text-muted small pt-2 border-top">
        <span>Prepared By: <strong><?= e($batch['prepared_by_name'] ?? 'Production Unit') ?></strong></span>
        <span>Date: <strong><?= date('d M Y, h:i A', strtotime($batch['created_at'])) ?></strong></span>
        <span class="badge bg-<?= $batch['status'] === 'RECEIVED' ? 'success' : 'warning text-dark' ?>">STATUS: <?= e($batch['status']) ?></span>
    </div>
</div>

<?php
$qrPayload = json_encode([
    'type'           => 'PRODUCTION_BATCH',
    'batch_number'   => $batch['batch_number'],
    'program_number' => $batch['program_number'] ?? '',
    'warehouse_code' => $batch['warehouse_code'] ?? '',
    'total_qty'      => (int)($batch['total_qty'] ?? 0),
], JSON_UNESCAPED_SLASHES);
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generate Production-Based QR Code for Warehouse Receiving Scanner
    new QRCode(document.getElementById("qrcode"), {
        text: <?= json_encode($qrPayload) ?>,
        width: 130,
        height: 130,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });

    // Generate 1D Barcode for standard scanners
    JsBarcode("#barcode", "<?= e($batch['batch_number']) ?>", {
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
