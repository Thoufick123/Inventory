<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1 fw-bold"><i class="bi bi-exclamation-octagon me-2 text-danger"></i>Finished Goods Damage &amp; Rework</h2>
        <p class="text-muted mb-0">Damage cases for QC-passed pieces held in Production.</p>
    </div>
    <?php if (hasPermission('damages', 'create')): ?>
        <a href="<?= url('production/inventory-damages/create') ?>" class="btn btn-danger"><i class="bi bi-qr-code-scan me-1"></i>Scan &amp; Report</a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm border-start border-4 border-danger h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">Unavailable Pieces</small><div class="fs-3 fw-bold text-danger"><?= number_format($stats['quarantined_pieces'] ?? 0) ?></div><small class="text-muted">Not yet repaired or restocked</small></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm border-start border-4 border-warning h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">At Factory Rework</small><div class="fs-3 fw-bold text-warning-emphasis"><?= number_format($stats['rework_pieces'] ?? 0) ?></div><small class="text-muted">Pieces marked for rework</small></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm border-start border-4 border-success h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">Restored</small><div class="fs-3 fw-bold text-success"><?= number_format($stats['restored_pieces'] ?? 0) ?></div><small class="text-muted">Repaired or restocked</small></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm border-start border-4 border-dark h-100"><div class="card-body"><small class="text-muted text-uppercase fw-semibold">Value at Risk</small><div class="fs-3 fw-bold">₹<?= number_format((float)($stats['at_risk_value'] ?? 0), 2) ?></div><small class="text-muted">Open damage cases</small></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('production/inventory-damages') ?>" class="row g-3 align-items-end">
            <div class="col-lg-5"><label class="form-label small fw-semibold">Search</label><input type="search" name="search" class="form-control" value="<?= e($filters['search']) ?>" placeholder="Damage #, QR, QC batch, program, product"></div>
            <div class="col-lg-3"><label class="form-label small fw-semibold">Source Stage</label><select name="source_stage" class="form-select"><option value="">All stages</option><?php foreach ($sourceStages as $stage): ?><option value="<?= e($stage) ?>" <?= $filters['source_stage'] === $stage ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $stage)) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">All statuses</option><?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $status)) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button><a class="btn btn-outline-secondary" href="<?= url('production/inventory-damages') ?>" title="Clear filters"><i class="bi bi-x-lg"></i></a></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase small"><tr><th class="ps-3">Damage Case</th><th>QR / Product</th><th>QC Batch / Program</th><th>Stage &amp; Reason</th><th>Unit Value</th><th>Status</th><th>Reported</th><th class="text-end pe-3">Action</th></tr></thead>
            <tbody>
            <?php if (empty($damages)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-shield-check fs-2 d-block mb-2 text-success"></i>No finished-goods damage cases found.</td></tr>
            <?php else: foreach ($damages as $damage): ?>
                <tr>
                    <td class="ps-3"><strong class="font-monospace"><?= e($damage['damage_number']) ?></strong><small class="d-block text-muted"><?= e(date('d M Y, h:i A', strtotime($damage['created_at']))) ?></small></td>
                    <td><span class="font-monospace fw-semibold"><?= e($damage['tag_code']) ?></span><small class="d-block text-muted"><?= e($damage['product_name'] ?? $damage['program_name']) ?> · <?= e($damage['size_name'] ?? 'Std') ?> / <?= e($damage['color_name'] ?? 'Std') ?></small></td>
                    <td><?= e($damage['qc_batch_number'] ?? '-') ?><small class="d-block text-muted"><?= e($damage['program_number'] ?? '') ?></small></td>
                    <td><span class="badge bg-light text-dark border"><?= e(str_replace('_', ' ', $damage['source_stage'])) ?></span><small class="d-block text-danger mt-1"><?= e($damage['damage_reason']) ?></small></td>
                    <td>₹<?= number_format((float)$damage['unit_value'], 2) ?></td>
                    <td><span class="badge <?= in_array($damage['status'], ['REPAIRED', 'RESTOCKED'], true) ? 'bg-success-subtle text-success' : (in_array($damage['status'], ['WRITTEN_OFF', 'SALVAGED'], true) ? 'bg-secondary-subtle text-secondary' : 'bg-warning-subtle text-warning-emphasis') ?>"><?= e(str_replace('_', ' ', $damage['status'])) ?></span></td>
                    <td><?= e($damage['reported_by_name'] ?? 'Staff') ?><small class="d-block text-muted"><?= e($damage['production_unit_name'] ?? 'Production') ?></small></td>
                    <td class="text-end pe-3"><a class="btn btn-sm btn-outline-primary" href="<?= url('production/inventory-damages/view/' . $damage['id']) ?>" title="Open case"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>