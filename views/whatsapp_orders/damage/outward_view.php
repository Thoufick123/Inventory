<?php
// views/whatsapp_orders/damage/outward_view.php — Damaged Outward Batch Details
?>

<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('warehouse/whatsapp-orders') ?>">WhatsApp Orders</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('warehouse/whatsapp-orders/damage') ?>">Damage Management</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('warehouse/whatsapp-orders/damage/outward') ?>">Outward Batches</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($batch['return_number'] ?? ('RET-WA-' . $batch['id'])) ?></li>
                </ol>
            </nav>
            <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-up-right text-danger"></i>
                <?= htmlspecialchars($batch['return_number'] ?? ('RET-WA-' . $batch['id'])) ?>
                <?php
                $status = $batch['status'] ?? 'IN_TRANSIT';
                $badgeClass = match($status) {
                    'PENDING', 'REQUESTED' => 'bg-warning text-dark',
                    'IN_TRANSIT', 'APPROVED' => 'bg-primary text-white',
                    'RECEIVED', 'COMPLETED' => 'bg-success text-white',
                    'REJECTED' => 'bg-danger text-white',
                    default => 'bg-secondary text-white'
                };
                ?>
                <span class="badge <?= $badgeClass ?> fs-6"><?= htmlspecialchars($status) ?></span>
            </h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('warehouse/whatsapp-orders/damage/outward') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Outwards
            </a>
            <a href="<?= url('warehouse/whatsapp-orders/damage/outward/gatepass/' . $batch['id']) ?>" target="_blank" class="btn btn-dark">
                <i class="bi bi-printer me-1"></i> Print Gate Pass
            </a>
            <a href="<?= url('warehouse/whatsapp-orders/damage/outward/label/' . $batch['id']) ?>" target="_blank" class="btn btn-danger">
                <i class="bi bi-qr-code me-1"></i> Print Carton QR Label
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Batch Metadata -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-danger"></i>Dispatch Batch Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Origin Unit</label>
                            <div class="fw-bold text-dark fs-6">WhatsApp Division (<?= htmlspecialchars($batch['channel_name'] ?? 'WhatsApp Channel') ?>)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Destination Warehouse</label>
                            <div class="fw-bold text-dark fs-6">
                                <?= htmlspecialchars($batch['warehouse_name'] ?? 'Warehouse #' . $batch['warehouse_id']) ?>
                                <?php if (!empty($batch['warehouse_code'])): ?>
                                    <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($batch['warehouse_code']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Total Quantity</label>
                            <div class="fw-bold fs-5 text-danger"><?= number_format($batch['total_quantity'] ?? 0) ?> pcs</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Dispatched At</label>
                            <div class="fw-semibold text-dark"><?= date('M d, Y h:i A', strtotime($batch['created_at'])) ?></div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Created By</label>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($batch['created_by_name'] ?? 'User') ?></div>
                        </div>
                        <?php if (!empty($batch['remarks'])): ?>
                            <div class="col-12">
                                <label class="text-muted small">Dispatch Remarks / Instructions</label>
                                <div class="p-3 bg-light rounded text-dark">
                                    <?= nl2br(htmlspecialchars($batch['remarks'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="bi bi-boxes me-2 text-danger"></i>Dispatched Damaged Products (<?= count($items ?? []) ?> items)
                    </h6>
                    <span class="badge bg-danger fs-6"><?= number_format($batch['total_quantity'] ?? 0) ?> Total Pieces</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product Details</th>
                                    <th>Defect Type</th>
                                    <th class="text-center">Quantity</th>
                                    <th>Tracked Piece Serial Tags</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No items in this batch.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $i = 1; foreach ($items as $item): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($item['product_name'] ?? 'Product #' . $item['variant_id']) ?></div>
                                                <small class="text-muted">
                                                    SKU: <code><?= htmlspecialchars($item['variant_sku'] ?? 'N/A') ?></code>
                                                    <?php if (!empty($item['color']) || !empty($item['size'])): ?>
                                                        &bull; <?= htmlspecialchars(($item['color'] ?? '') . ' / ' . ($item['size'] ?? '')) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                    <?= htmlspecialchars($item['reason'] ?? $item['defect_type'] ?? 'DAMAGED') ?>
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold text-danger fs-6">
                                                <?= number_format($item['quantity']) ?> pcs
                                            </td>
                                            <td>
                                                <?php
                                                $pieceTags = [];
                                                if (!empty($item['piece_tags'])) {
                                                    $pieceTags = is_array($item['piece_tags']) ? $item['piece_tags'] : json_decode($item['piece_tags'], true);
                                                }
                                                ?>
                                                <?php if (!empty($pieceTags) && is_array($pieceTags)): ?>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php foreach (array_slice($pieceTags, 0, 8) as $tag): ?>
                                                            <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($tag) ?></span>
                                                        <?php endforeach; ?>
                                                        <?php if (count($pieceTags) > 8): ?>
                                                            <span class="badge bg-secondary">+<?= count($pieceTags) - 8 ?> more</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">Standard Damaged Stock</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Logistics & Transport Details -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-truck me-2 text-danger"></i>Logistics & Transport</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Carrier / Transporter:</span>
                            <span class="fw-bold"><?= htmlspecialchars($batch['carrier_name'] ?? 'GRM Internal Transport') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Vehicle Number:</span>
                            <span class="fw-bold font-monospace"><?= htmlspecialchars($batch['vehicle_no'] ?? 'N/A') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Driver Name:</span>
                            <span><?= htmlspecialchars($batch['driver_name'] ?? 'N/A') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Driver Contact:</span>
                            <span><?= htmlspecialchars($batch['driver_phone'] ?? 'N/A') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Dispatch Status:</span>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Warehouse Receipt Status -->
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="bi bi-building text-primary me-2"></i>Warehouse Inward Action</h6>
                    <p class="small text-muted mb-3">
                        This batch is ready to be inspected and received in the central warehouse under <strong>Warehouse &gt; Returns Management</strong>.
                    </p>
                    <a href="<?= url('warehouse/returns') ?>" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-box-arrow-in-down me-1"></i> View in Warehouse Returns
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
