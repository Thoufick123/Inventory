<?php
// views/website_orders/damage/view.php — Damage Record Details
?>

<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('warehouse/website-orders') ?>">Website Orders</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('warehouse/website-orders/damage') ?>">Damage Management</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($damage['damage_number']) ?></li>
                </ol>
            </nav>
            <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill text-danger"></i>
                <?= htmlspecialchars($damage['damage_number']) ?>
                <?php
                $statusBadges = [
                    'REPORTED' => 'bg-warning text-dark',
                    'INSPECTED' => 'bg-info text-dark',
                    'OUTWARD_PENDING' => 'bg-primary text-white',
                    'DISPATCHED' => 'bg-secondary text-white',
                    'WRITTEN_OFF' => 'bg-dark text-white',
                    'RESOLVED' => 'bg-success text-white'
                ];
                $sClass = $statusBadges[$damage['status']] ?? 'bg-secondary text-white';
                ?>
                <span class="badge <?= $sClass ?> fs-6"><?= htmlspecialchars($damage['status']) ?></span>
            </h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('warehouse/website-orders/damage') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Damage List
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Details -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Product & Defect Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Product Name</label>
                            <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($damage['product_name'] ?? 'N/A') ?></div>
                        </div>

                        <div class="col-md-4">
                            <label class="text-muted small">Damaged Quantity</label>
                            <div class="fw-bold fs-5 text-danger"><?= number_format($damage['quantity']) ?> pcs</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Defect Category</label>
                            <div>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                    <i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($damage['defect_type'] ?? 'UNSPECIFIED') ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Severity Level</label>
                            <div>
                                <?php
                                $sevBadge = match($damage['severity'] ?? 'MODERATE') {
                                    'MINOR' => 'bg-info-subtle text-info border-info-subtle',
                                    'MODERATE' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                    'CRITICAL' => 'bg-danger-subtle text-danger border-danger-subtle',
                                    default => 'bg-secondary-subtle text-secondary'
                                };
                                ?>
                                <span class="badge <?= $sevBadge ?> border px-2 py-1">
                                    <?= htmlspecialchars($damage['severity'] ?? 'MODERATE') ?>
                                </span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="text-muted small">Defect Description / Notes</label>
                            <div class="p-3 bg-light rounded text-dark">
                                <?= nl2br(htmlspecialchars($damage['notes'] ?? 'No special notes recorded.')) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Piece Serial Tags (if any) -->
            <?php
            $pieceTags = [];
            if (!empty($damage['piece_tags'])) {
                $pieceTags = is_array($damage['piece_tags']) ? $damage['piece_tags'] : json_decode($damage['piece_tags'], true);
            }
            ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="bi bi-qr-code-scan me-2 text-primary"></i>Tracked Piece Tags / Serial QR
                    </h6>
                    <span class="badge bg-secondary"><?= count($pieceTags ?? []) ?> Tags Recorded</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($pieceTags) && is_array($pieceTags)): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($pieceTags as $tag): ?>
                                <div class="p-2 border rounded bg-light d-flex align-items-center gap-2">
                                    <i class="bi bi-upc text-muted"></i>
                                    <code class="fw-bold text-dark"><?= htmlspecialchars($tag) ?></code>
                                    <span class="badge bg-danger-subtle text-danger ms-1">Damaged</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-qr-code fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            No specific piece serial tags assigned to this batch damage entry.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar / Meta Details -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Audit & Source Info</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Channel:</span>
                            <span class="fw-bold"><?= htmlspecialchars($damage['channel_name'] ?? 'Website Channel') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Discovered At:</span>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($damage['source_type'] ?? $damage['discovered_stage'] ?? 'INWARD_TRANSIT') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Logged By:</span>
                            <span><?= htmlspecialchars($damage['reported_by_name'] ?? 'System') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Recorded On:</span>
                            <span><?= date('M d, Y h:i A', strtotime($damage['created_at'])) ?></span>
                        </li>
                        <?php if (!empty($damage['allocation_id'])): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Inward Allocation Ref:</span>
                                <a href="<?= url('warehouse/website-orders/inward/view/' . $damage['allocation_id']) ?>" class="fw-bold text-decoration-none">
                                    Allocation #<?= $damage['allocation_id'] ?> <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="bi bi-shield-check text-success me-1"></i> Stock Ledger Status</h6>
                    <p class="small text-muted mb-0">
                        This item has been deducted from Available Website stock and isolated in the <code>DAMAGED</code> stock partition. It will remain isolated until dispatched to the central warehouse via an Outward Return Batch.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
