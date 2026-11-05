<?php
$pieceTags = !empty($damage['piece_tags']) ? $damage['piece_tags'] : ShopDamage::resolvePieceTags($damage);
$damQty = max(1, (int)($damage['quantity'] ?? count($pieceTags)));
?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <a href="<?= url('shop/damage') ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <h2 class="mb-0 fw-bold">
                <i class="bi bi-exclamation-octagon-fill me-2 <?= $damage['status'] === 'RETURN_TO_WH' ? 'text-primary' : 'text-danger' ?>"></i>Case #<?= e($damage['damage_number']) ?>
            </h2>
            <?php if (!empty($damage['allocation_number'])): ?>
                <a href="<?= url('shop/inward/view/' . ($damage['allocation_id'] ?? $damage['reference_id'])) ?>" class="badge bg-info-subtle text-info-emphasis border border-info-subtle fs-6 px-3 py-1.5 text-decoration-none shadow-sm font-monospace" title="View Inward Allocation Verification">
                    <i class="bi bi-box-arrow-in-down me-1"></i>Origin Allocation: <strong><?= e($damage['allocation_number']) ?></strong>
                </a>
            <?php endif; ?>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle fs-6 px-3 py-1.5">
                <i class="bi bi-shop me-1"></i><?= e($damage['shop_name']) ?>
            </span>
        </div>
        <span class="text-muted">Reported on <?= date('d M Y, h:i A', strtotime($damage['created_at'])) ?> by <strong><?= e($damage['reported_by_name'] ?? 'Shop Staff') ?></strong></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (!empty($damage['allocation_id'])): ?>
            <a href="<?= url('shop/inward/view/' . $damage['allocation_id']) ?>" class="btn btn-outline-primary rounded-pill">
                <i class="bi bi-box-arrow-in-down me-1"></i> View Inward Allocation GRN
            </a>
        <?php endif; ?>
        <a href="<?= url('shop/damage') ?>" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-list-check me-1"></i> All Damage Cases
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Left Column: Details, Origin Allocation Card & Evidence Photo -->
    <div class="col-lg-7">
        <!-- 1. Damage Case Specifications -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Damage Case Specifications</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($pieceTags)): ?>
                        <li class="list-group-item d-flex justify-content-between px-0 align-items-center bg-light p-2.5 rounded-3 mb-3 border">
                            <span class="fw-bold text-dark"><i class="bi bi-qr-code me-1 text-primary"></i>Damaged Piece Tag(s):</span>
                            <div class="d-flex flex-wrap gap-1 justify-content-end">
                                <?php foreach ($pieceTags as $pt): ?>
                                    <span class="badge bg-dark font-monospace text-warning fs-6 px-2.5 py-1.5 shadow-2xs"><?= e($pt) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </li>
                    <?php endif; ?>

                    <li class="list-group-item d-flex justify-content-between px-0 py-2.5">
                        <span class="text-muted">Product / Style:</span>
                        <span class="fw-bold text-dark"><?= e($damage['product_name']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2.5">
                        <span class="text-muted">SKU Code:</span>
                        <span class="fw-bold font-monospace text-primary"><?= e($damage['sku']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2.5">
                        <span class="text-muted">Variant (Size / Color):</span>
                        <span>
                            <span class="badge bg-light text-dark border me-1"><?= e($damage['size_name'] ?? 'Free') ?></span>
                            <span class="badge bg-light text-dark border"><?= e($damage['color_name'] ?? 'Std') ?></span>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2.5">
                        <span class="text-muted">Shop Location:</span>
                        <span class="badge bg-primary"><?= e($damage['shop_name']) ?> (<?= e($damage['shop_code']) ?>)</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2.5">
                        <span class="text-muted">Damage Source:</span>
                        <span class="badge <?= $damage['source_type'] === 'INWARD_TRANSIT' ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' ?>">
                            <?= e($damage['source_type']) ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2.5">
                        <span class="text-muted">Defect Classification:</span>
                        <span class="badge bg-light text-dark border"><?= e(str_replace('_', ' ', $damage['defect_type'] ?? 'OTHER')) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2.5">
                        <span class="text-muted">Damage Reason:</span>
                        <span class="fw-semibold text-danger"><?= e($damage['damage_reason']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2.5">
                        <span class="text-muted">Damaged Quantity:</span>
                        <span class="fw-bold text-danger fs-5"><?= number_format($damage['quantity']) ?> pcs</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2.5">
                        <span class="text-muted">Cost Per Unit:</span>
                        <span>₹<?= number_format($damage['cost_per_unit'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2.5">
                        <span class="text-muted">Total Financial Loss:</span>
                        <span class="fw-bold text-danger fs-5">₹<?= number_format($damage['total_loss'], 2) ?></span>
                    </li>
                    <?php if (!empty($damage['defect_notes'])): ?>
                        <li class="list-group-item px-0 py-2.5">
                            <span class="text-muted d-block mb-1">Defect Description / Notes:</span>
                            <div class="p-2.5 bg-light rounded-2 small text-dark border"><?= nl2br(e($damage['defect_notes'])) ?></div>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($damage['disposition_notes'])): ?>
                        <li class="list-group-item px-0 py-2.5">
                            <span class="text-muted d-block mb-1">Resolution / Disposition Notes:</span>
                            <div class="p-2.5 bg-success-subtle text-success-emphasis rounded-2 small border border-success-subtle"><?= nl2br(e($damage['disposition_notes'])) ?></div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- 2. Itemized Damaged Pieces Breakdown (N pcs) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-grid-3x3-gap-fill text-danger me-2"></i>Itemized Damaged Pieces Breakdown (<?= $damQty ?> pcs)
                    </h6>
                    <small class="text-muted">Itemized QR tags, quarantine defect status, and unit loss allocation for each damaged piece</small>
                </div>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill px-3 py-1.5 font-monospace">
                    <i class="bi bi-shield-x me-1"></i><?= $damQty ?> Unit(s) Quarantined
                </span>
            </div>
            <div class="card-body p-3 bg-light bg-opacity-50">
                <div class="row g-3">
                    <?php foreach ($pieceTags as $idx => $tag): ?>
                        <?php 
                        $pImg = !empty($damage['product_image']) ? $damage['product_image'] : '';
                        $costPerUnit = (float)($damage['cost_per_unit'] ?? 0);
                        ?>
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100 bg-white p-3 hover-shadow">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace" style="font-size: 0.75rem;">
                                        Damaged Piece #<?= $idx + 1 ?> of <?= $damQty ?>
                                    </span>
                                    <span class="badge bg-danger text-white small font-monospace">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($damage['status'] ?? 'QUARANTINED') ?>
                                    </span>
                                </div>

                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <?php if ($pImg): ?>
                                        <img src="<?= e($pImg) ?>" alt="" class="rounded border shadow-sm flex-shrink-0" style="width: 44px; height: 44px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 44px; height: 44px;">
                                            <i class="bi bi-box-seam fs-5"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-dark text-truncate small"><?= e($damage['product_name']) ?></div>
                                        <div class="font-monospace text-primary fw-semibold" style="font-size: 11px;"><?= e($damage['sku']) ?></div>
                                        <div class="text-muted text-truncate" style="font-size: 10px;">
                                            <span class="badge bg-light text-dark border me-1"><?= e($damage['size_name'] ?? 'Free') ?></span>
                                            <span class="badge bg-light text-dark border"><?= e($damage['color_name'] ?? 'Std') ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border mb-2 small">
                                    <span class="text-muted">Unit Financial Loss:</span>
                                    <span class="fw-bold text-danger fs-6">₹<?= number_format($costPerUnit, 2) ?></span>
                                </div>

                                <!-- QR Code & Tag Block -->
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                    <div class="d-flex align-items-center gap-2">
                                        <div id="dmg_qr_canvas_<?= $idx ?>" class="border p-1 bg-white rounded shadow-sm"></div>
                                        <div>
                                            <small class="text-muted d-block" style="font-size: 9px;">Piece QR Tag</small>
                                            <span class="badge bg-dark font-monospace text-warning" style="font-size: 11px;">
                                                <i class="bi bi-qr-code me-1"></i><?= e($tag) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="navigator.clipboard.writeText('<?= e($tag) ?>'); alert('Copied Damaged Tag: <?= e($tag) ?>');" title="Copy Tag">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                </div>

                                <?php if (!empty($damage['allocation_number'])): ?>
                                    <div class="mt-2 text-muted text-truncate" style="font-size: 9px;">
                                        <i class="bi bi-truck me-1 text-primary"></i>Origin: <strong>Allocation #<?= e($damage['allocation_number']) ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 3. Defect Evidence Photo -->
        <?php if (!empty($damage['photo_url'])): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-image me-2 text-primary"></i>Defect Evidence Photo</h6>
                </div>
                <div class="card-body text-center p-3">
                    <img src="<?= e($damage['photo_url']) ?>" alt="Damage Photo" class="img-fluid rounded-3 shadow-xs border" style="max-height: 380px; object-fit: contain;">
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Status Update & Disposition Form -->
    <div class="col-lg-5">
        <!-- Status & Disposition Action Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-sliders me-2 text-success"></i>Case Status &amp; Disposition</h6>
                <?php if (!hasPermission('shop_damage', 'edit') && !hasPermission('shop_damage', 'process')): ?>
                    <span class="badge bg-light text-muted border"><i class="bi bi-lock-fill me-1"></i>Read Only</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <?php if (hasPermission('shop_damage', 'edit') || hasPermission('shop_damage', 'process')): ?>
                <form method="POST" action="<?= url('shop/damage/update-status/' . $damage['id']) ?>" enctype="multipart/form-data">
                    <?= CSRF::input() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Lifecycle Status</label>
                        <select name="status" class="form-select form-select-lg border-2 fw-semibold">
                            <option value="LOGGED" <?= $damage['status'] === 'LOGGED' ? 'selected' : '' ?>>🔴 Quarantined / Logged</option>
                            <option value="INSPECTED" <?= $damage['status'] === 'INSPECTED' ? 'selected' : '' ?>>🔍 Inspected / Verified</option>
                            <option value="RETURN_TO_WH" <?= $damage['status'] === 'RETURN_TO_WH' ? 'selected' : '' ?>>📦 Mark for Return to Warehouse</option>
                            <option value="REPAIRED" <?= $damage['status'] === 'REPAIRED' ? 'selected' : '' ?>>🪡 Repaired / Restocked to Shop</option>
                            <option value="SALVAGED" <?= $damage['status'] === 'SALVAGED' ? 'selected' : '' ?>>♻️ Salvaged (Discount Clearance)</option>
                            <option value="WRITTEN_OFF" <?= $damage['status'] === 'WRITTEN_OFF' ? 'selected' : '' ?>>❌ Written Off / Scrapped</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Inspection / Disposition Notes</label>
                        <textarea name="disposition_notes" class="form-control" rows="3" placeholder="Enter resolution details, repair notes, or write-off reason..."><?= e($damage['disposition_notes'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Defect Notes</label>
                        <textarea name="defect_notes" class="form-control" rows="2" placeholder="Update defect description..."><?= e($damage['defect_notes'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase text-muted">Upload / Replace Evidence Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Save Status &amp; Disposition
                    </button>
                </form>
                <?php else: ?>
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Current Status</div>
                    <span class="badge <?= $damage['status'] === 'RETURN_TO_WH' ? 'bg-primary' : ($damage['status'] === 'LOGGED' ? 'bg-danger' : 'bg-secondary') ?> fs-6 px-3 py-1.5 shadow-2xs">
                        <?= e($damage['status']) ?>
                    </span>
                </div>
                <div class="mb-3">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Disposition Notes</div>
                    <div class="p-3 bg-white rounded-3 border text-dark">
                        <?= !empty($damage['disposition_notes']) ? nl2br(e($damage['disposition_notes'])) : '<span class="text-muted fst-italic">No disposition notes recorded.</span>' ?>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Defect Notes</div>
                    <div class="p-3 bg-white rounded-3 border text-dark">
                        <?= !empty($damage['defect_notes']) ? nl2br(e($damage['defect_notes'])) : '<span class="text-muted fst-italic">No defect notes recorded.</span>' ?>
                    </div>
                </div>
                <div class="alert alert-light border small text-muted mb-0">
                    <i class="bi bi-info-circle me-1 text-primary"></i> You have view-only access to damage management. Status modifications require edit/process permissions.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Audit Trail Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Audit Trail &amp; Activity Log</h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($auditLogs)): ?>
                    <p class="text-muted small mb-0 text-center py-3">No activity log entries recorded yet.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($auditLogs as $log): ?>
                            <div class="d-flex gap-2.5 mb-3 pb-3 border-bottom last-border-0">
                                <div class="bg-light rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                    <i class="bi bi-activity text-primary" style="font-size: 0.8rem;"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="small fw-bold text-dark"><?= e($log['action']) ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        By <strong><?= e($log['user_name'] ?? 'System') ?></strong> on <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?>
                                    </div>
                                    <?php if (!empty($log['new_values'])): ?>
                                        <div class="small text-secondary mt-1 font-monospace" style="font-size: 0.72rem;">
                                            <?= e(is_string($log['new_values']) ? $log['new_values'] : json_encode($log['new_values'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Load QRCode Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pieceTags = <?= json_encode($pieceTags) ?>;
    const sku = <?= json_encode($damage['sku'] ?? '') ?>;
    const cost = <?= json_encode(number_format((float)($damage['cost_per_unit'] ?? 0), 2)) ?>;

    pieceTags.forEach((tag, idx) => {
        const cEl = document.getElementById('dmg_qr_canvas_' + idx);
        if (cEl && typeof QRCode !== 'undefined') {
            try {
                new QRCode(cEl, {
                    text: tag + '|' + sku + '|Loss:₹' + cost,
                    width: 44,
                    height: 44,
                    colorDark: "#dc2626",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
            } catch(e) {
                console.error("QR Render Error:", e);
            }
        }
    });
});
</script>
