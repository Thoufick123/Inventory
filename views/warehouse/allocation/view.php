<?php
// views/warehouse/allocation/view.php
$isShopReq = ($isShopRequestView ?? false) 
    || str_starts_with($allocation['allocation_number'] ?? '', 'IND-')
    || str_starts_with($allocation['allocation_number'] ?? '', 'REQ-')
    || str_starts_with($allocation['allocation_number'] ?? '', 'WREQ-')
    || str_starts_with($allocation['allocation_number'] ?? '', 'WEBREQ-')
    || str_contains($_SERVER['REQUEST_URI'] ?? '', 'shop-requests')
    || (($_GET['from'] ?? '') === 'shop-requests');

$isBatches = (($_GET['tab'] ?? '') === 'batches' || in_array($allocation['status'] ?? '', ['APPROVED', 'PICKING', 'PACKING']));
$allocBackUrl = $isShopReq ? url('warehouse/shop-requests') : url('warehouse/allocation' . ($isBatches ? '?tab=batches' : ''));
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="h3 mb-0 text-gray-800">
            <i class="bi <?= $isShopReq ? 'bi-shop text-primary' : ($isBatches ? 'bi-boxes text-success' : 'bi-boxes text-primary') ?> me-2"></i>
            <?= $isShopReq ? 'Shop Stock Request #' : ($isBatches ? 'Allocation Batch #' : 'Allocation #') ?><?= htmlspecialchars($allocation['allocation_number']) ?>
        </h2>
        <p class="text-muted small mb-0">
            <?= $isShopReq 
                ? 'Stock requisition requested by <strong>' . htmlspecialchars($allocation['destination_name']) . '</strong> to be fulfilled from <strong>' . htmlspecialchars($allocation['warehouse_name']) . '</strong>'
                : 'Warehouse transfer batch to <strong>' . htmlspecialchars($allocation['destination_name']) . '</strong> (' . htmlspecialchars($allocation['destination_type']) . ')' ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if (!$isShopReq && in_array($allocation['status'], ['APPROVED', 'PICKING', 'PACKING']) && hasPermission('allocation_outward', 'create')): ?>
            <a href="<?= url('warehouse/allocation/outward') ?>" class="btn btn-primary">
                <i class="bi bi-truck me-1"></i> Dispatch Outward
            </a>
        <?php endif; ?>
        <a href="<?= $allocBackUrl ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> <?= $isShopReq ? 'Back to Shop Requests' : ($isBatches ? 'Back to Batches' : 'Back to Allocations') ?>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <?php if ($isShopReq): ?>
        <!-- SHOP REQUEST VIEW: Full width, No Piece QR Codes, Shop Details right below Shop Request Items -->
        <div class="col-12">
            <!-- 1. Shop Request Items -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-primary"></i>Shop Request Items</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item / Variant</th>
                                <th>SKU</th>
                                <th>Requested</th>
                                <th>Picked</th>
                                <th>Packed</th>
                                <th>Dispatched</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allocation['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></div>
                                        <small class="text-muted">Color: <?= htmlspecialchars($item['color_name'] ?? 'N/A') ?> | Size: <?= htmlspecialchars($item['size_name'] ?? 'N/A') ?></small>
                                    </td>
                                    <td><code><?= htmlspecialchars($item['sku']) ?></code></td>
                                    <td><span class="badge bg-light text-dark border"><?= $item['requested_qty'] ?> pcs</span></td>
                                    <td><?= $item['picked_qty'] ?> pcs</td>
                                    <td><?= $item['packed_qty'] ?> pcs</td>
                                    <td><strong class="text-success"><?= $item['dispatched_qty'] ?> pcs</strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Shop Details (Directly below Shop Request Items Container) -->
            <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
                <h5 class="fw-bold border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Shop Details</h5>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 0.72rem;">Status</small>
                        <?php
                            $stClass = match($allocation['status']) {
                                'REQUESTED' => 'bg-warning text-dark',
                                'APPROVED'  => 'bg-info text-dark',
                                'PICKING', 'PACKING' => 'bg-primary text-white',
                                'DISPATCHED', 'COMPLETED', 'DELIVERED', 'RECEIVED' => 'bg-success text-white',
                                default => 'bg-secondary text-white'
                            };
                        ?>
                        <span class="badge <?= $stClass ?> fs-6 mt-1"><?= htmlspecialchars($allocation['status']) ?></span>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 0.72rem;">Source Warehouse</small>
                        <strong class="text-dark fs-6"><?= htmlspecialchars($allocation['warehouse_name']) ?></strong>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 0.72rem;">Destination Shop</small>
                        <strong class="text-dark fs-6"><?= htmlspecialchars($allocation['destination_name']) ?></strong>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 0.72rem;">Requested By</small>
                        <span class="text-dark fw-semibold"><?= htmlspecialchars($allocation['requested_by_name'] ?? 'System Admin') ?></span>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 0.72rem;">Created At</small>
                        <span class="text-muted"><?= date('d M Y, h:i A', strtotime($allocation['created_at'])) ?></span>
                    </div>
                    <?php if (!empty($allocation['notes'])): ?>
                        <div class="col-12 mt-2">
                            <div class="p-3 bg-light rounded-3 small border">
                                <strong>Requisition Notes:</strong> <?= htmlspecialchars($allocation['notes']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. Outward Dispatch Info (if dispatched) -->
            <?php if (in_array($allocation['status'], ['DISPATCHED', 'COMPLETED', 'RECEIVED'])): ?>
                <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-light border-start border-success border-4">
                    <h5 class="fw-bold text-success mb-2"><i class="bi bi-check-circle-fill me-2"></i>Outward Dispatch Completed</h5>
                    <p class="text-muted small mb-3">This shop stock requisition has been dispatched. Reserved warehouse inventory was deducted upon dispatch confirmation.</p>
                    <div>
                        <?php if (hasPermission('allocation_outward', 'gatepass')): ?>
                            <?php if (hasPermission('allocation_outward', 'gatepass')): ?>
                                <a href="<?= url('warehouse/allocation/gatepass/' . $allocation['id']) ?>" target="_blank" class="btn btn-primary">
                                    <i class="bi bi-qr-code-scan me-1"></i> Print Gate Pass &amp; Box Sticker
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- REGULAR ALLOCATION PASS VIEW (Original layout: Left = Breakdown + QR Codes, Right = Allocation Meta) -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-primary"></i>Allocated Item Breakdown</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item / Variant</th>
                                <th>SKU</th>
                                <th>Requested</th>
                                <th>Picked</th>
                                <th>Packed</th>
                                <th>Dispatched</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allocation['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></div>
                                        <small class="text-muted">Color: <?= htmlspecialchars($item['color_name'] ?? 'N/A') ?> | Size: <?= htmlspecialchars($item['size_name'] ?? 'N/A') ?></small>
                                    </td>
                                    <td><code><?= htmlspecialchars($item['sku']) ?></code></td>
                                    <td><span class="badge bg-light text-dark border"><?= $item['requested_qty'] ?> pcs</span></td>
                                    <td><?= $item['picked_qty'] ?> pcs</td>
                                    <td><?= $item['packed_qty'] ?> pcs</td>
                                    <td><strong class="text-success"><?= $item['dispatched_qty'] ?> pcs</strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Allocated Stock Piece QR Codes Container -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-qr-code-scan text-primary fs-5"></i>
                        <span>Allocated Stock Piece QR Codes</span> 
                        <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 fs-6 rounded-pill"><?= count($pieceQRs ?? []) ?> Pieces</span>
                    </h5>
                    <?php if (hasPermission('allocation_outward', 'gatepass')): ?>
                        <a href="<?= url('warehouse/allocation/gatepass/' . $allocation['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary px-3 rounded-pill">
                            <i class="bi bi-printer me-1"></i> Print Labels
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($pieceQRs)): ?>
                    <div class="text-center text-muted py-5">No QR piece codes assigned to this allocation.</div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-2 row-cols-xl-3 g-3.5" style="--bs-gutter-x: 1.25rem; --bs-gutter-y: 1.25rem;">
                        <?php foreach ($pieceQRs as $idx => $p): ?>
                            <?php 
                                $fmtMrp = (!empty($p['mrp']) && (float)$p['mrp'] > 0) ? (((int)$p['mrp'] == $p['mrp']) ? (int)$p['mrp'] : number_format($p['mrp'], 2, '.', '')) : '999';
                                $costVal = (!empty($p['selling_price']) && (float)$p['selling_price'] > 0) ? (float)$p['selling_price'] : ((!empty($p['mrp']) && (float)$p['mrp'] > 0) ? (float)$p['mrp'] : 599);
                                $fmtDisc = ($costVal == (int)$costVal) ? (int)$costVal : number_format($costVal, 2, '.', '');
                                $qrPayload = $p['tag_code'];
                            ?>
                            <div class="col">
                                <div class="border rounded-3 bg-white shadow-sm d-flex align-items-start h-100 transition-all" style="padding: 1rem 1.15rem; gap: 1rem; border-color: #e2e8f0 !important;">
                                    <!-- Left: QR Code + Tag Code Badge -->
                                    <div class="text-center flex-shrink-0 d-flex flex-column align-items-center">
                                        <div class="alloc-qr-box bg-white p-1 rounded-2 border shadow-2xs d-flex align-items-center justify-content-center" id="alloc-qr-<?= $idx ?>" data-code="<?= htmlspecialchars($qrPayload, ENT_QUOTES, 'UTF-8') ?>" style="width: 68px; height: 68px;"></div>
                                        <div class="badge bg-dark text-white font-monospace mt-1.5 px-2 py-1 w-100 rounded" style="font-size: 0.70rem; letter-spacing: 0.5px;">
                                            <?= htmlspecialchars($p['tag_code']) ?>
                                        </div>
                                    </div>
                                    <!-- Right: Brand / Product Name, Size-Color, MRP, Disc -->
                                    <div class="overflow-hidden flex-grow-1 d-flex flex-column justify-content-center" style="min-width: 0; padding-top: 2px;">
                                        <div class="fw-bold text-uppercase text-dark text-truncate mb-1" style="font-size: 0.84rem; letter-spacing: 0.25px; line-height: 1.3;" title="<?= htmlspecialchars($p['product_name']) ?>">
                                            <?= htmlspecialchars($p['brand_name'] ?? $p['product_name'] ?? 'GRM CLASSIC') ?>
                                        </div>
                                        <div class="fw-semibold text-secondary text-truncate mb-1.5" style="font-size: 0.78rem;">
                                            <?= htmlspecialchars($p['size_name']) ?>-<?= htmlspecialchars($p['color_name']) ?>
                                        </div>
                                        <div class="d-flex flex-column gap-0.5">
                                            <?php if (!empty($p['mrp']) && (float)$p['mrp'] > 0): ?>
                                                <div class="text-muted text-decoration-line-through" style="font-size: 0.72rem; line-height: 1.2;">
                                                    MRP: ₹<?= $fmtMrp ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="fw-bold text-success" style="font-size: 0.84rem; line-height: 1.2;">
                                                Disc: ₹<?= $fmtDisc ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (in_array($allocation['status'], ['DISPATCHED', 'COMPLETED', 'RECEIVED'])): ?>
                <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-light border-start border-success border-4">
                    <h5 class="fw-bold text-success mb-2"><i class="bi bi-check-circle-fill me-2"></i>Outward Dispatch Completed</h5>
                    <p class="text-muted small mb-3">This allocation batch has been dispatched. Reserved warehouse inventory was deducted upon dispatch confirmation.</p>
                    <div>
                        <a href="<?= url('warehouse/allocation/gatepass/' . $allocation['id']) ?>" target="_blank" class="btn btn-primary">
                            <i class="bi bi-qr-code-scan me-1"></i> Print Gate Pass &amp; Box Sticker
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <h6 class="fw-bold border-bottom pb-2 mb-3">Allocation Meta</h6>
                <div class="mb-2">
                    <small class="text-muted d-block">Status</small>
                    <span class="badge bg-primary fs-6"><?= htmlspecialchars($allocation['status']) ?></span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Source Warehouse</small>
                    <strong><?= htmlspecialchars($allocation['warehouse_name']) ?></strong>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Destination</small>
                    <strong><?= htmlspecialchars($allocation['destination_name']) ?></strong>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Requested By</small>
                    <span><?= htmlspecialchars($allocation['requested_by_name'] ?? 'System Admin') ?></span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Created At</small>
                    <span><?= date('d M Y, h:i A', strtotime($allocation['created_at'])) ?></span>
                </div>
                <?php if (!empty($allocation['notes'])): ?>
                    <div class="mt-3 p-2 bg-light rounded small">
                        <strong>Notes:</strong> <?= htmlspecialchars($allocation['notes']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- QRCode JS CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alloc-qr-box').forEach(function(box) {
        const text = box.getAttribute('data-code');
        if (text && typeof QRCode !== 'undefined') {
            new QRCode(box, {
                text: text,
                width: 64,
                height: 64,
                colorDark: "#0f172a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    });
});
</script>
