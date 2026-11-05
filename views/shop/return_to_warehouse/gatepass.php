<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Return Gate Pass - <?= e($return['return_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            background-color: #f1f5f9;
            color: #1e293b;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 20px 10px;
        }
        .gatepass-sheet {
            background: #fff;
            max-width: 860px;
            width: 100%;
            margin: 0 auto;
            padding: 30px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        .manifest-table {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .manifest-table th, .manifest-table td {
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            padding: 7px 6px;
            vertical-align: middle;
        }
        .manifest-table thead th {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .manifest-table tbody td {
            font-size: 12px;
        }
        .manifest-table tfoot th {
            font-size: 12px;
            padding: 8px 6px;
        }
        @media print {
            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 11px !important;
            }
            .gatepass-sheet {
                border: 1.5px solid #000 !important;
                box-shadow: none !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                padding: 16px !important;
                page-break-inside: avoid;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container my-3 no-print text-center">
    <button onclick="window.print()" class="btn btn-primary px-4 fw-semibold me-2 shadow-sm">
        <i class="bi bi-printer me-1"></i> Print Delivery Gate Pass
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary px-3">Close</button>
</div>

<div class="gatepass-sheet">
    <!-- Header -->
    <div class="row align-items-center pb-3 border-bottom border-2 border-dark mb-3">
        <div class="col-8">
            <h3 class="fw-bold text-uppercase tracking-wider mb-1 text-dark">GRM APPARELS</h3>
            <h6 class="text-muted mb-1 small fw-semibold">SHOP RETURN TO WAREHOUSE DELIVERY CHALLAN &amp; GATE PASS</h6>
            <span class="badge bg-danger text-white px-2 py-1">INVENTORY RTV RETURN</span>
        </div>
        <div class="col-4 text-end">
            <div id="gatepassQr" class="d-inline-block border p-1 bg-white rounded"></div>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="row g-2 mb-3 bg-light p-3 rounded border">
        <div class="col-4">
            <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">Return Consignment #</small>
            <span class="fw-bold font-monospace fs-5 text-danger"><?= e($return['return_number']) ?></span>
        </div>
        <div class="col-4">
            <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">Dispatched Date &amp; Time</small>
            <span class="fw-bold text-dark"><?= formatDateTime($return['created_at']) ?></span>
        </div>
        <div class="col-4">
            <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">Consignment Status</small>
            <span class="badge bg-warning text-dark px-2 py-1"><?= e($return['status']) ?></span>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">From (Originating Shop)</small>
            <strong class="text-dark"><?= e($return['shop_name'] ?? 'Flagship Store') ?> (<?= e($return['shop_code'] ?? 'SH-01') ?>)</strong>
            <div class="small text-muted text-truncate"><?= e($return['shop_address'] ?? '') ?></div>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">To (Destination Warehouse)</small>
            <strong class="text-dark"><?= e($return['warehouse_name']) ?> (<?= e($return['warehouse_code']) ?>)</strong>
            <div class="small text-muted text-truncate"><?= e($return['warehouse_address'] ?? '') ?></div>
        </div>
        
        <!-- Outward Process Details -->
        <div class="col-12 mt-2 pt-2 border-top">
            <div class="row g-2">
                <div class="col-4">
                    <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">Carrier / Transporter</small>
                    <strong class="text-dark small"><?= e($return['outward_logistics']['carrier_name'] ?? 'GRM Dedicated Logistics') ?></strong>
                </div>
                <div class="col-3">
                    <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">Vehicle No / Cartons</small>
                    <span class="fw-bold text-dark font-monospace small"><?= !empty($return['outward_logistics']['vehicle_no']) ? e($return['outward_logistics']['vehicle_no']) : 'N/A' ?></span>
                    <span class="badge bg-light text-dark border ms-1"><?= e($return['outward_logistics']['carton_count'] ?? 1) ?> Ctn</span>
                </div>
                <div class="col-5">
                    <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">Driver &amp; Phone / LR No</small>
                    <span class="text-dark small fw-semibold"><?= !empty($return['outward_logistics']['driver_name']) ? e($return['outward_logistics']['driver_name']) : 'Logistics Bearer' ?> <?= !empty($return['outward_logistics']['driver_phone']) ? '(' . e($return['outward_logistics']['driver_phone']) . ')' : '' ?></span>
                    <?php if (!empty($return['outward_logistics']['tracking_no'])): ?>
                        <div class="font-monospace text-primary small" style="font-size: 11px;">LR: <?= e($return['outward_logistics']['tracking_no']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Itemized Table -->
    <h6 class="fw-bold text-uppercase border-bottom pb-1 mb-2 small text-dark">Itemized Consignment Manifest</h6>
    <table class="table table-bordered table-sm align-middle mb-3 manifest-table">
        <thead class="table-secondary text-uppercase">
            <tr>
                <th class="text-center" style="width: 5%;">#</th>
                <th style="width: 32%;">Product Description</th>
                <th style="width: 22%;">SKU / Variant</th>
                <th class="text-center" style="width: 13%;">Condition</th>
                <th class="text-center" style="width: 10%;">Dispatched Qty</th>
                <th class="text-end" style="width: 9%;">Rate (₹)</th>
                <th class="text-end" style="width: 9%;">Est. Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $idx => $it): ?>
                <tr>
                    <td class="text-center fw-semibold text-muted"><?= $idx + 1 ?></td>
                    <td>
                        <strong class="text-dark d-block"><?= e($it['product_name']) ?></strong>
                    </td>
                    <td class="font-monospace small">
                        <span class="fw-semibold text-dark"><?= e($it['sku']) ?></span>
                        <div class="text-muted" style="font-size: 10.5px;">
                            <?= !empty($it['size_name']) ? e($it['size_name']) : '' ?>
                            <?= (!empty($it['size_name']) && !empty($it['color_name'])) ? ' • ' : '' ?>
                            <?= !empty($it['color_name']) ? e($it['color_name']) : '' ?>
                        </div>
                    </td>
                    <td class="text-center small">
                        <span class="badge bg-light text-dark border px-2 py-1"><?= e($it['classification']) ?></span>
                    </td>
                    <td class="text-center fw-bold text-danger"><?= $it['returned_qty'] ?></td>
                    <td class="text-end font-monospace">₹<?= number_format((float)$it['unit_price'], 2) ?></td>
                    <td class="text-end font-monospace fw-bold text-dark">₹<?= number_format((float)$it['line_total'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <th colspan="4" class="text-end text-uppercase">Total Quantity &amp; Value:</th>
                <th class="text-center fs-6 text-danger fw-bold"><?= $totalReturnedQty ?> pcs</th>
                <th></th>
                <th class="text-end fs-6 text-success fw-bold font-monospace">₹<?= number_format((float)$totalEstimatedValue, 2) ?></th>
            </tr>
        </tfoot>
    </table>

    <!-- Notes if any -->
    <?php 
        $gatepassRemarks = '';
        if (!empty($return['outward_logistics']['remarks'])) {
            $gatepassRemarks = trim($return['outward_logistics']['remarks']);
        } elseif (!empty($return['notes'])) {
            $decoded = json_decode($return['notes'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $gatepassRemarks = trim($decoded['remarks'] ?? '');
            } else {
                $gatepassRemarks = trim($return['notes']);
            }
        }
    ?>
    <?php if (!empty($gatepassRemarks)): ?>
        <div class="mb-3 p-2 bg-light rounded border small">
            <strong>Notes / Instructions:</strong> <?= nl2br(e($gatepassRemarks)) ?>
        </div>
    <?php endif; ?>

    <!-- Triple Signature Blocks -->
    <div class="row pt-4 mt-3 border-top border-2 border-dark text-center">
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-2">
                <small class="fw-bold d-block">Shop Store Manager</small>
                <small class="text-muted" style="font-size: 10px;">Dispatched By &amp; Stamp</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-2">
                <small class="fw-bold d-block">Driver / Logistics Courier</small>
                <small class="text-muted" style="font-size: 10px;">Transport Received Sign</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-2">
                <small class="fw-bold d-block">Central Warehouse QC</small>
                <small class="text-muted" style="font-size: 10px;">Verification &amp; Acceptance</small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById('gatepassQr'), {
        text: '<?= e($return["return_number"]) ?>',
        width: 75,
        height: 75,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });
});
</script>
</body>
</html>
