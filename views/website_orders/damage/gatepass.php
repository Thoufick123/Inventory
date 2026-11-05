<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Damaged Outward Gate Pass - <?= htmlspecialchars($batch['return_number'] ?? ('RET-WEB-' . $batch['id'])) ?></title>
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
    <button onclick="window.print()" class="btn btn-danger px-4 fw-semibold me-2 shadow-sm">
        <i class="bi bi-printer me-1"></i> Print Delivery Gate Pass
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary px-3">Close</button>
</div>

<div class="gatepass-sheet">
    <!-- Header -->
    <div class="row align-items-center pb-3 border-bottom border-2 border-dark mb-3">
        <div class="col-8">
            <h3 class="fw-bold text-uppercase tracking-wider mb-1 text-dark">GRM APPARELS</h3>
            <h6 class="text-muted mb-1 small fw-semibold">WEBSITE DIVISION &rarr; WAREHOUSE DAMAGED STOCK DISPATCH GATE PASS</h6>
            <span class="badge bg-danger text-white px-2 py-1">DEFECTIVE STOCK DISPATCH CHALLAN</span>
        </div>
        <div class="col-4 text-end">
            <div id="gatepassQr" class="d-inline-block border p-1 bg-white rounded"></div>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="row g-3 mb-3 bg-light p-3 rounded border">
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">Return Consignment / Batch #</small>
            <span class="fw-bold font-monospace fs-5 text-danger"><?= htmlspecialchars($batch['return_number'] ?? ('RET-WEB-' . $batch['id'])) ?></span>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">Dispatched Date &amp; Time</small>
            <span class="fw-bold text-dark"><?= date('M d, Y h:i A', strtotime($batch['created_at'])) ?></span>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">From (Originating Division)</small>
            <strong class="text-dark">Website Online Division (<?= htmlspecialchars($batch['channel_name'] ?? 'Website Orders') ?>)</strong>
            <div class="small text-muted">E-Commerce Dispatch Hub</div>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">To (Destination Warehouse)</small>
            <strong class="text-dark"><?= htmlspecialchars($batch['warehouse_name'] ?? 'Warehouse #' . $batch['warehouse_id']) ?> (<?= htmlspecialchars($batch['warehouse_code'] ?? 'WH') ?>)</strong>
            <div class="small text-muted"><?= htmlspecialchars($batch['warehouse_address'] ?? 'Central Facility') ?></div>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">Vehicle / Transport Info</small>
            <span class="fw-semibold text-dark">
                <?= htmlspecialchars($batch['vehicle_no'] ?? 'N/A') ?> 
                <?php if (!empty($batch['carrier_name'])): ?>
                    (<?= htmlspecialchars($batch['carrier_name']) ?>)
                <?php endif; ?>
            </span>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 10px;">Consignment Status</small>
            <span class="badge bg-danger text-white px-2 py-1"><?= htmlspecialchars($batch['status'] ?? 'IN_TRANSIT') ?></span>
        </div>
    </div>

    <!-- Itemized Table -->
    <h6 class="fw-bold text-uppercase border-bottom pb-1 mb-2 small text-dark">Itemized Damaged Consignment Manifest</h6>
    <table class="table table-bordered table-sm align-middle mb-3 manifest-table">
        <thead class="table-secondary text-uppercase">
            <tr>
                <th class="text-center" style="width: 5%;">#</th>
                <th style="width: 35%;">Product Description</th>
                <th style="width: 25%;">SKU / Variant</th>
                <th class="text-center" style="width: 20%;">Defect Reason</th>
                <th class="text-center" style="width: 15%;">Quantity</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalQty = 0;
            foreach ($items as $idx => $it): 
                $qty = (int)($it['quantity'] ?? $it['returned_qty'] ?? 0);
                $totalQty += $qty;
            ?>
                <tr>
                    <td class="text-center fw-semibold text-muted"><?= $idx + 1 ?></td>
                    <td>
                        <strong class="text-dark d-block"><?= htmlspecialchars($it['product_name'] ?? 'Product #' . $it['variant_id']) ?></strong>
                    </td>
                    <td class="font-monospace small">
                        <span class="fw-semibold text-dark"><?= htmlspecialchars($it['variant_sku'] ?? $it['sku'] ?? 'N/A') ?></span>
                        <?php if (!empty($it['color']) || !empty($it['size'])): ?>
                            <div class="text-muted" style="font-size: 10.5px;">
                                <?= htmlspecialchars(($it['color'] ?? '') . ' • ' . ($it['size'] ?? '')) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center small">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                            <?= htmlspecialchars($it['reason'] ?? $it['defect_type'] ?? 'DAMAGED') ?>
                        </span>
                    </td>
                    <td class="text-center fw-bold text-danger"><?= number_format($qty) ?> pcs</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <th colspan="4" class="text-end text-uppercase">Total Quantity Dispatched:</th>
                <th class="text-center fs-6 text-danger fw-bold"><?= number_format($totalQty) ?> pcs</th>
            </tr>
        </tfoot>
    </table>

    <!-- Notes if any -->
    <?php if (!empty($batch['remarks'])): ?>
        <div class="mb-3 p-2 bg-light rounded border small">
            <strong>Notes / Instructions:</strong> <?= nl2br(htmlspecialchars($batch['remarks'])) ?>
        </div>
    <?php endif; ?>

    <!-- Triple Signature Blocks -->
    <div class="row pt-4 mt-3 border-top border-2 border-dark text-center">
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-2">
                <small class="fw-bold d-block">Website Channel In-charge</small>
                <small class="text-muted" style="font-size: 10px;">Dispatched By &amp; Stamp</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-2">
                <small class="fw-bold d-block">Driver / Carrier Sign</small>
                <small class="text-muted" style="font-size: 10px;">Transport Received Sign</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-2">
                <small class="fw-bold d-block">Warehouse QC &amp; Inward</small>
                <small class="text-muted" style="font-size: 10px;">Verification &amp; Acceptance</small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById('gatepassQr'), {
        text: '<?= htmlspecialchars($batch["return_number"] ?? ("RET-WEB-" . $batch["id"])) ?>',
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
