<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocation Gate Pass & QR Sticker — <?= e($allocation['allocation_number']) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            padding: 15px;
            font-size: 12px;
        }
        .label-card {
            max-width: 680px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #0f172a;
            border-radius: 8px;
            padding: 18px 20px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        .label-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .meta-label {
            font-size: 9px;
            letter-spacing: 0.5px;
        }
        .qr-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 8px;
            border-radius: 6px;
        }
        .sig-box {
            text-align: center;
            width: 30%;
            font-size: 10px;
            color: #64748b;
        }
        .sig-line {
            border-top: 1px solid #0f172a;
            margin-top: 30px;
            margin-bottom: 4px;
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
    <button onclick="window.print()" class="btn btn-primary btn-sm me-2">
        <i class="bi bi-printer me-1"></i> Print Delivery Gate Pass & QR Sticker
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-x-lg me-1"></i> Close
    </button>
</div>

<?php
$totalPcs = 0;
foreach ($items as $item) {
    $dQty = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : ($item['packed_qty'] > 0 ? $item['packed_qty'] : $item['requested_qty']));
    $totalPcs += $dQty;
}
?>

<div class="label-card">
    <!-- Header -->
    <div class="label-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-uppercase tracking-wider" style="font-size: 15px;">GRM WAREHOUSE ALLOCATION GATE PASS</h5>
            <span class="badge bg-dark text-uppercase" style="font-size: 9px; padding: 3px 6px;">Official Stock Outward & Delivery Challan Pass</span>
        </div>
        <div class="text-end">
            <span class="text-muted d-block meta-label text-uppercase fw-semibold">Destination Counter / Channel:</span>
            <span class="fw-bold text-primary" style="font-size: 13px;"><?= e($allocation['destination_name']) ?></span>
        </div>
    </div>

    <!-- Main Content & QR Grid -->
    <div class="row g-2 align-items-center mb-2">
        <div class="col-7">
            <div class="mb-1">
                <span class="text-muted d-block text-uppercase fw-semibold meta-label">Allocation Number:</span>
                <span class="fw-bold font-monospace text-dark" style="font-size: 16px;"><?= e($allocation['allocation_number']) ?></span>
            </div>
            <div class="mb-1">
                <span class="text-muted d-block text-uppercase fw-semibold meta-label">Source Warehouse:</span>
                <span class="fw-bold text-primary" style="font-size: 13px;"><?= e($allocation['warehouse_name']) ?></span>
            </div>
            <div class="row g-2 mb-1" style="font-size: 11px;">
                <div class="col-6">
                    <span class="text-muted d-block text-uppercase fw-semibold meta-label">Vehicle Number:</span>
                    <span class="fw-bold text-dark"><?= e($outward['vehicle_number'] ?? 'Direct Handover') ?></span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block text-uppercase fw-semibold meta-label">Transporter / LR #:</span>
                    <span class="fw-bold text-dark"><?= e(($outward['transporter_name'] ?? 'N/A') . ' / ' . ($outward['lr_number'] ?? 'N/A')) ?></span>
                </div>
            </div>
            <div class="d-flex gap-3" style="font-size: 11px;">
                <div>
                    <span class="text-muted d-block text-uppercase fw-semibold meta-label">Dispatch Date & Time:</span>
                    <span class="fw-semibold text-dark"><?= !empty($outward['dispatch_date']) ? date('d M Y, h:i A', strtotime($outward['dispatch_date'])) : date('d M Y, h:i A') ?></span>
                </div>
                <div>
                    <span class="text-muted d-block text-uppercase fw-semibold meta-label">Total Quantity:</span>
                    <span class="fw-bold text-success" style="font-size: 13px;"><?= number_format($totalPcs) ?> Pcs</span>
                </div>
                <div>
                    <span class="text-muted d-block text-uppercase fw-semibold meta-label">Total Box Count:</span>
                    <span class="fw-bold" style="font-size: 13px;"><?= (int)($outward['total_boxes'] ?? 1) ?> Box(es)</span>
                </div>
            </div>
        </div>
        <div class="col-5">
            <div class="qr-box">
                <div id="qrcode"></div>
                <small class="font-monospace text-muted mt-1 fw-bold" style="font-size: 10px;"><?= e($allocation['allocation_number']) ?></small>
            </div>
        </div>
    </div>

    <!-- Variant Breakdown Table -->
    <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered mb-0 align-middle" style="border-color: #0f172a; font-size: 11px;">
            <thead class="table-dark text-center" style="font-size: 10px;">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 28%;">SKU</th>
                    <th>Item / Variant Description</th>
                    <th style="width: 15%;" class="text-end pe-2">Requested Qty</th>
                    <th style="width: 15%;" class="text-end pe-2">Dispatched Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $item): 
                    $dQty = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : ($item['packed_qty'] > 0 ? $item['packed_qty'] : $item['requested_qty']));
                ?>
                    <tr>
                        <td class="text-center"><?= $idx + 1 ?></td>
                        <td class="font-monospace fw-semibold ps-2" style="font-size: 10px;"><?= e($item['sku']) ?></td>
                        <td><?= e($item['product_name']) ?> <span class="text-muted">(<?= e($item['color_name'] ?? 'Std') ?> / <?= e($item['size_name'] ?? 'Free') ?>)</span></td>
                        <td class="text-end pe-2"><?= number_format($item['requested_qty']) ?> pcs</td>
                        <td class="text-end pe-2 fw-bold text-success"><?= number_format($dQty) ?> pcs</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8fafc;" class="fw-bold">
                    <td colspan="4" class="text-end pe-2">TOTAL DISPATCHED PIECES:</td>
                    <td class="text-end pe-2 text-success" style="font-size: 12px;"><?= number_format($totalPcs) ?> pcs</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Gate Instructions -->
    <?php if (!empty($allocation['notes']) || !empty($outward['notes'])): ?>
        <div class="alert alert-warning border border-warning text-dark rounded p-2 mb-2" style="font-size: 10px;">
            <strong>Gate Instructions:</strong> <?= e($allocation['notes'] ?: $outward['notes']) ?>
        </div>
    <?php endif; ?>

    <!-- Signature Block -->
    <div class="d-flex justify-content-between text-center pt-1 mb-2">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="fw-semibold text-uppercase">Warehouse Dispatch Manager</div>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="fw-semibold text-uppercase">Driver / Transporter Signature</div>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="fw-semibold text-uppercase">Receiver Signature</div>
        </div>
    </div>

    <!-- Footer metadata -->
    <div class="d-flex justify-content-between align-items-center text-muted pt-1 border-top" style="font-size: 10px;">
        <span>Prepared By: <strong><?= e($allocation['created_by_name'] ?? 'System Administrator') ?></strong></span>
        <span>Date: <strong><?= date('d M Y, h:i A', strtotime($allocation['created_at'] ?? 'now')) ?></strong></span>
        <span class="badge bg-<?= ($allocation['status'] ?? '') === 'DISPATCHED' || ($allocation['status'] ?? '') === 'COMPLETED' ? 'success' : 'primary' ?>">STATUS: <?= e($allocation['status'] ?? 'OUTWARD') ?></span>
    </div>
</div>

<?php
$qrPayload = json_encode([
    'type'              => 'ALLOCATION_OUTWARD',
    'allocation_number' => $allocation['allocation_number'],
    'warehouse_name'    => $allocation['warehouse_name'] ?? '',
    'destination_name'  => $allocation['destination_name'] ?? '',
    'total_pcs'         => (int)$totalPcs,
], JSON_UNESCAPED_SLASHES);
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generate QR Code
    new QRCode(document.getElementById("qrcode"), {
        text: <?= json_encode($qrPayload) ?>,
        width: 100,
        height: 100,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });
});
</script>
</body>
</html>
