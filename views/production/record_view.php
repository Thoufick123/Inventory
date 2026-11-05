<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-eye me-2 text-primary"></i><?= e($recordTitle) ?></h2>
        <span class="text-muted">Read-only record details</span>
    </div>
    <a href="<?= e($backUrl) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold">Record Details</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($fields as $label => $value): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="text-muted small fw-semibold"><?= e($label) ?></div>
                    <div class="text-dark"><?= e((string)($value === '' || $value === null ? '—' : $value)) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (!empty($items)): ?>
    <div class="card border shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold"><?= e($itemsTitle) ?></h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <?php foreach ($itemColumns as $label => $key): ?>
                            <th><?= e($label) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <?php foreach ($itemColumns as $key): ?>
                                <td><?= e((string)($item[$key] ?? '—')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
