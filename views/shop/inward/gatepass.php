<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Delivery Gate Pass - <?= e($outward['outward_number'] ?? $allocation['allocation_number']) ?></title>
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
            <h6 class="text-muted mb-1">SHOP DISPATCH &amp; DELIVERY GATE PASS</h6>
            <span class="badge bg-dark text-white px-2 py-1">RETAIL INVENTORY TRANSFER</span>
        </div>
        <div class="col-4 text-end">
            <div id="gatepassQr" class="d-inline-block border p-2 bg-white rounded"></div>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="row g-3 mb-4 bg-light p-3 rounded border">
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase">Gate Pass / Outward #</small>
            <span class="fw-bold font-monospace fs-5 text-primary"><?= e($outward['outward_number'] ?? 'OUT-PENDING') ?></span>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase">Allocation #</small>
            <span class="fw-bold font-monospace fs-5"><?= e($allocation['allocation_number']) ?></span>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase">Dispatch Date &amp; Time</small>
            <span class="fw-semibold"><?= !empty($outward['dispatch_date']) ? date('d M Y, h:i A', strtotime($outward['dispatch_date'])) : date('d M Y, h:i A') ?></span>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase">Vehicle / Carrier</small>
            <span class="fw-bold text-dark"><?= e($outward['vehicle_number'] ?? 'Retail Delivery Van') ?></span>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase">Source Warehouse</small>
            <span class="badge bg-secondary fs-6"><?= e($warehouse['name'] ?? 'Central Warehouse') ?></span>
        </div>
        <div class="col-6">
            <small class="text-muted d-block fw-semibold text-uppercase">Destination Shop</small>
            <span class="badge bg-primary fs-6"><?= e($shop['name'] ?? 'Retail Store') ?> (<?= e($shop['code'] ?? 'SHOP') ?>)</span>
        </div>
        <?php if (!empty($outward['driver_name']) || !empty($outward['transporter_name'])): ?>
            <div class="col-6">
                <small class="text-muted d-block fw-semibold text-uppercase">Driver / Contact</small>
                <span><?= e($outward['driver_name'] ?? 'N/A') ?> <?= !empty($outward['driver_contact']) ? '(' . e($outward['driver_contact']) . ')' : '' ?></span>
            </div>
            <div class="col-6">
                <small class="text-muted d-block fw-semibold text-uppercase">Transporter / LR #</small>
                <span><?= e($outward['transporter_name'] ?? 'Direct') ?> <?= !empty($outward['lr_number']) ? '[LR: ' . e($outward['lr_number']) . ']' : '' ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Itemized Variant Table -->
    <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-2">Dispatched Items &amp; Variant Manifest</h6>
    <table class="table table-bordered table-sm align-middle mb-4">
        <thead class="table-secondary">
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">SKU &amp; Product</th>
                <th style="width: 20%;">Size &amp; Color</th>
                <th style="width: 20%;" class="text-end">Selling Price</th>
                <th style="width: 20%;" class="text-end">Dispatched Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php $totalQty = 0; ?>
            <?php foreach ($items as $i => $item): ?>
                <?php 
                    $qty = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : $item['requested_qty']);
                    $totalQty += $qty; 
                ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td>
                        <div class="fw-bold font-monospace text-dark"><i class="bi bi-qr-code text-primary me-1"></i><?= e($item['sku']) ?></div>
                        <small class="text-muted"><?= e($item['product_name']) ?></small>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border"><?= e($item['size_name'] ?? 'Free') ?></span>
                        <span class="ms-1"><?= e($item['color_name'] ?? 'Std') ?></span>
                    </td>
                    <td class="text-end">₹<?= number_format($item['selling_price'] ?? 0, 2) ?></td>
                    <td class="text-end fw-bold fs-6"><?= number_format($qty) ?> pcs</td>
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
                <td class="text-end fw-bold fs-6"><?= number_format($outward['total_boxes'] ?? 1) ?> Boxes</td>
            </tr>
        </tfoot>
    </table>

    <!-- Signature Footer -->
    <div class="row pt-4 mt-4 border-top border-dark text-center">
        <div class="col-4">
            <div style="height: 45px;"></div>
            <div class="border-top border-dark pt-1 small fw-bold">Warehouse Dispatcher</div>
        </div>
        <div class="col-4">
            <div style="height: 45px;"></div>
            <div class="border-top border-dark pt-1 small fw-bold">Driver / Carrier Sign</div>
        </div>
        <div class="col-4">
            <div style="height: 45px;"></div>
            <div class="border-top border-dark pt-1 small fw-bold">Shop Receiver / Store Manager</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var qrCodeText = <?= json_encode($outward['outward_number'] ?? $allocation['allocation_number']) ?>;
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
