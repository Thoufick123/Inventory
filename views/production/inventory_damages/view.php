<div class="d-flex align-items-center gap-2 mb-4">
    <a class="btn btn-outline-secondary btn-sm" href="<?= url('production/inventory-damages') ?>" title="Back to cases"><i class="bi bi-arrow-left"></i></a>
    <div><h2 class="h4 mb-0 fw-bold">Damage Case <span class="font-monospace"><?= e($damage['damage_number']) ?></span></h2><small class="text-muted">Reported <?= e(date('d M Y, h:i A', strtotime($damage['created_at']))) ?> by <?= e($damage['reported_by_name'] ?? 'Staff') ?></small></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Piece and production traceability</strong></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">QR tag</dt><dd class="col-sm-8 font-monospace fw-semibold"><?= e($damage['tag_code']) ?></dd>
                    <dt class="col-sm-4 text-muted">Product</dt><dd class="col-sm-8"><?= e($damage['product_name'] ?? $damage['program_name']) ?> · <?= e($damage['size_name'] ?? 'Std') ?> / <?= e($damage['color_name'] ?? 'Std') ?></dd>
                    <dt class="col-sm-4 text-muted">QC batch</dt><dd class="col-sm-8"><?= e($damage['qc_batch_number'] ?? '-') ?></dd>
                    <dt class="col-sm-4 text-muted">Production program</dt><dd class="col-sm-8"><?= e($damage['program_number'] ?? '-') ?> · <?= e($damage['program_name'] ?? '') ?></dd>
                    <dt class="col-sm-4 text-muted">Production unit</dt><dd class="col-sm-8"><?= e($damage['production_unit_name'] ?? 'Production') ?></dd>
                    <dt class="col-sm-4 text-muted">Found at</dt><dd class="col-sm-8"><?= e(str_replace('_', ' ', $damage['source_stage'])) ?></dd>
                    <dt class="col-sm-4 text-muted">Reason</dt><dd class="col-sm-8 text-danger fw-semibold"><?= e($damage['damage_reason']) ?></dd>
                    <dt class="col-sm-4 text-muted">Value at risk</dt><dd class="col-sm-8">₹<?= number_format((float)$damage['unit_value'], 2) ?></dd>
                    <dt class="col-sm-4 text-muted">Status</dt><dd class="col-sm-8"><span class="badge bg-warning-subtle text-warning-emphasis"><?= e(str_replace('_', ' ', $damage['status'])) ?></span></dd>
                </dl>
                <?php if (!empty($damage['defect_notes'])): ?><div class="border-top mt-3 pt-3"><small class="text-muted d-block mb-1">Inspection and disposition notes</small><?= nl2br(e($damage['defect_notes'])) ?></div><?php endif; ?>
            </div>
        </div>
        <?php if (!empty($damage['photo_url'])): ?>
            <div class="card border-0 shadow-sm"><div class="card-header bg-white"><strong>Evidence photo</strong></div><div class="card-body text-center"><a href="<?= e($damage['photo_url']) ?>" target="_blank" rel="noopener"><img src="<?= e($damage['photo_url']) ?>" alt="Damage evidence" class="img-fluid rounded border" style="max-height: 420px; object-fit: contain;"></a></div></div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>Update disposition</strong></div>
            <div class="card-body">
                <?php if (hasPermission('damages', 'edit')): ?>
                    <form method="POST" action="<?= url('production/inventory-damages/update-status/' . $damage['id']) ?>">
                        <?= CSRF::input() ?>
                        <div class="mb-3"><label class="form-label fw-semibold" for="damageStatus">Status</label><select class="form-select" name="status" id="damageStatus" required><?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $damage['status'] === $status ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $status)) ?></option><?php endforeach; ?></select></div>
                        <div class="alert alert-info small"><strong>Inventory effect:</strong> LOGGED, INSPECTED, REWORK, SALVAGED, and WRITTEN OFF remain unavailable. REPAIRED or RESTOCKED returns this QR piece to available Production inventory.</div>
                        <div class="mb-3"><label class="form-label fw-semibold" for="dispositionNotes">Notes</label><textarea class="form-control" id="dispositionNotes" name="defect_notes" rows="5"><?= e($damage['defect_notes'] ?? '') ?></textarea></div>
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2-circle me-1"></i>Save disposition</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-light border mb-0">You have read-only access to this case.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>