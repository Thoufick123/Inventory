<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset QR Sticker - <?= e($asset['asset_code']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @page {
            size: 50mm 25mm;
            margin: 0;
        }
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .label-card {
            width: 50mm;
            min-height: 25mm;
            background: #fff;
            border: 1px dashed #ccc;
            padding: 2mm;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 0 auto 20px auto;
            border-radius: 4px;
        }
        .qr-side {
            width: 20mm;
            text-align: center;
        }
        .qr-side img {
            width: 19mm;
            height: 19mm;
            display: block;
        }
        .info-side {
            width: 27mm;
            font-size: 6.5pt;
            line-height: 1.15;
            color: #000;
            overflow: hidden;
        }
        .brand-header {
            font-size: 6.5pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #111;
            text-transform: uppercase;
            border-bottom: 0.5px solid #000;
            padding-bottom: 1px;
            margin-bottom: 1.5px;
        }
        .asset-code {
            font-size: 7.5pt;
            font-weight: 900;
            font-family: monospace;
            color: #000;
        }
        .asset-name {
            font-weight: 600;
            font-size: 6pt;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 1px;
        }
        .asset-loc {
            font-size: 5.5pt;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .asset-sn {
            font-size: 5pt;
            font-family: monospace;
            color: #555;
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
                border: none;
                margin: 0;
                width: 50mm;
                height: 25mm;
                page-break-inside: avoid;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

<div class="container text-center mb-4 no-print">
    <div class="card p-3 shadow-sm d-inline-block border-0 rounded-4">
        <h5 class="fw-bold mb-1"><i class="bi bi-qr-code text-primary me-2"></i> Thermal QR Sticker Preview (50x25mm)</h5>
        <p class="text-muted small mb-3">Suitable for standard thermal barcode sticker printers (TSC TE244, Zebra ZD220, etc.)</p>
        <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-bold">
            <i class="bi bi-printer me-1"></i> Print Sticker Now
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
            Close
        </button>
    </div>
</div>

<div class="label-card">
    <div class="qr-side">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($asset['barcode_qr']) ?>" alt="QR Code">
    </div>
    <div class="info-side">
        <div class="brand-header">GRM APPARELS</div>
        <div class="asset-code"><?= e($asset['asset_code']) ?></div>
        <div class="asset-name"><?= e($asset['name']) ?></div>
        <div class="asset-loc"><?= e($asset['location_display']) ?></div>
        <?php if (!empty($asset['serial_number'])): ?>
            <div class="asset-sn">SN: <?= e($asset['serial_number']) ?></div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
