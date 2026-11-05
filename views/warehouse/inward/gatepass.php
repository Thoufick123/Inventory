<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Outward Gate Pass - <?= e($outward['outward_number'] ?? $batch['batch_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .gatepass-sheet {
            background: #fff;
            max-width: 800px;
            margin: 20px auto;
            padding: 35px;
            border: 2px solid #334155;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        @media print {
            body { background: #fff; margin: 0; }
            .gatepass-sheet { border: 2px solid #000; box-shadow: none; margin: 0; max-width: 100%; width: 100%; padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container my-3 no-print text-center">
    <button onclick="window.print()" class="btn btn-primary px-4 fw-semibold me-2">
        <i class="bi bi-printer me-1"></i> Print Delivery Gate Pass
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary px-3">Close</button>
</div>

<div class="gatepass-sheet">
    <!-- Header -->
    <div class="row align-items-center pb-3 border-bottom border-2 border-dark mb-3">
        <div class="col-8">
            <h3 class="fw-black text-uppercase tracking-wider mb-1">GRM APPARELS</h3>
            <h6 class="text-muted mb-1">PRODUCTION DISPATCH & OUTWARD GATE PASS</h6>
            <span class="badge bg-dark text-white px-2 py-1">FINISHED GOODS TRANSFER</span>
        </div>
        <div class="col-4 text-end">
            <div id="gatepassQr" class="d-inline-block border p-2 bg-white rounded"></div>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="row g-3 mb-4 bg-light p-3 rounded border">
        <div class="col-4">
            <small class="text-muted d-block fw-semibold text-uppercase">Gate Pass #</small>
            <span class="fw-bold font-monospace fs-5 text-primary"><?= e($outward['outward_number'] ?? 'OUT-PENDING') ?></span>
        </div>
        <div class="col-4">
            <small class="text-muted d-block fw-semibold text-uppercase">Production Batch #</small>
            <span class="fw-bold font-monospace fs-5"><?= e($batch['batch_number']) ?></span>
        </div>
        <div class="col-4">
            <small class="text-muted d-block fw-semibold text-uppercase">Dispatch Date & Time</small>
            <span class="fw-semibold"><?= !empty($outward['dispatch_date']) ? date('d M Y, h:i A', strtotime($outward['dispatch_date'])) : date('d M Y, h:i A') ?></span>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase">Origin Production Unit</small>
            <span class="fw-bold text-dark"><i class="bi bi-gear-wide-connected text-warning me-1"></i><?= e($batch['production_unit_name'] ?? 'Main Apparel Facility') ?> (<?= e($batch['production_unit_code'] ?? 'PU1') ?>)</span>
            <?php if (!empty($batch['production_unit_manager'])): ?>
                <small class="text-muted d-block">Unit In-Charge: <?= e($batch['production_unit_manager']) ?> <?= !empty($batch['production_unit_phone']) ? '('.e($batch['production_unit_phone']).')' : '' ?></small>
            <?php endif; ?>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase">Destination Receiving Warehouse</small>
            <span class="badge bg-primary fs-6"><?= e($batch['warehouse_name']) ?> (<?= e($batch['warehouse_code']) ?>)</span>
            <small class="text-muted d-block">Vehicle: <?= e($outward['vehicle_number'] ?? 'Internal Transfer Van') ?></small>
        </div>
        <div class="col-12 border-top pt-2 mt-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted d-block fw-semibold text-uppercase">Product / Style</small>
                    <span class="fw-bold text-dark"><?= e($batch['product_name']) ?> (Prog: <?= e($batch['program_number']) ?>)</span>
                </div>
                <div class="text-end">
                    <small class="text-muted d-block fw-semibold text-uppercase">Carton Count</small>
                    <span class="badge bg-dark fs-6"><?= number_format($batch['box_count'] ?? 1) ?> Boxes</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Itemized Variant Table -->
    <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-2">Dispatched Items & Variant Manifest</h6>
    <table class="table table-bordered table-sm align-middle mb-4">
        <thead class="table-secondary">
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">SKU & Barcode</th>
                <th style="width: 20%;">Size & Color</th>
                <th style="width: 20%;" class="text-end">Selling Price</th>
                <th style="width: 20%;" class="text-end">Dispatched Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php $totalQty = 0; ?>
            <?php foreach ($items as $i => $item): ?>
                <?php $totalQty += (int)$item['quantity']; ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td>
                        <div class="fw-bold font-monospace"><?= e($item['sku']) ?></div>
                        <small class="text-muted font-monospace"><?= e($item['qr_code'] ?? $item['sku']) ?></small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!empty($item['fabric_photo'])): ?>
                                <img src="<?= e(uploadUrl($item['fabric_photo'])) ?>" 
                                     alt="<?= e($item['color_name']) ?>" 
                                     style="width: 28px; height: 28px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;"
                                     loading="lazy">
                            <?php endif; ?>
                            <div>
                                <span class="badge bg-light text-dark border"><?= e($item['size_name']) ?></span>
                                <span class="ms-1 fw-semibold"><?= e($item['color_name']) ?></span>
                                <?php if (!empty($item['fabric_design'])): ?>
                                    <small class="text-muted d-block" style="font-size: 10px;"><?= e($item['fabric_design']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="text-end">₹<?= number_format($item['selling_price'] ?? 0, 2) ?></td>
                    <td class="text-end fw-bold fs-6"><?= number_format($item['quantity']) ?> pcs</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="4" class="text-end fw-bold text-uppercase">Total Quantity Dispatched:</td>
                <td class="text-end fw-bold text-primary fs-5"><?= number_format($totalQty) ?> pcs</td>
            </tr>
            <tr>
                <td colspan="4" class="text-end fw-bold text-uppercase">Carton Box Count:</td>
                <td class="text-end fw-bold fs-6"><?= number_format($batch['box_count'] ?? 1) ?> Boxes</td>
            </tr>
        </tfoot>
    </table>

    <!-- Signature Footer -->
    <div class="row pt-4 mt-4 border-top border-dark text-center">
        <div class="col-4">
            <div style="height: 45px;"></div>
            <div class="border-top border-dark pt-1 small fw-bold">Production Supervisor</div>
        </div>
        <div class="col-4">
            <div style="height: 45px;"></div>
            <div class="border-top border-dark pt-1 small fw-bold">Driver / Security Sign</div>
        </div>
        <div class="col-4">
            <div style="height: 45px;"></div>
            <div class="border-top border-dark pt-1 small fw-bold">Warehouse Receiver Sign</div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var qrCodeText = <?= json_encode($outward['outward_number'] ?? $batch['batch_number']) ?>;
        new QRCode(document.getElementById("gatepassQr"), {
            text: qrCodeText,
            width: 80,
            height: 80,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });
    });
</script>

</body>
</html>
