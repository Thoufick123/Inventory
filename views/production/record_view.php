<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-eye me-2 text-primary"></i><?= e($recordTitle) ?></h2>
        <span class="text-muted">Read-only record details</span>
    </div>
    <a href="<?= e($backUrl) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<?php if (!empty($qrLabel['code'])): ?>
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold"><i class="bi bi-qr-code-scan me-2 text-danger"></i>Damaged Fabric Piece Label</h5>
        </div>
        <div class="card-body">
            <div class="d-inline-flex align-items-center gap-3 p-3 border rounded bg-white">
                <div id="damageRecordQr" class="p-1 border rounded"></div>
                <div>
                    <div class="fw-bold fs-5"><?= e($qrLabel['roll_number'] ?? '') ?></div>
                    <div class="font-monospace fw-semibold"><?= e($qrLabel['code']) ?></div>
                    <?php if (!empty($qrLabel['color'])): ?>
                        <div class="small text-muted">Color: <?= e($qrLabel['color']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($qrLabel['design'])): ?>
                        <div class="small text-muted">Design: <?= e($qrLabel['design']) ?></div>
                    <?php endif; ?>
                    <div class="small text-muted">Piece weight: <?= number_format((float)($qrLabel['weight'] ?? 0), 2) ?> kg</div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= asset('js/qrcode.min.js') ?>"></script>
    <script>
        if (window.QRCode) {
            new QRCode(document.getElementById('damageRecordQr'), {
                text: <?= json_encode((string)$qrLabel['code'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                width: 112,
                height: 112,
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    </script>
<?php endif; ?>

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
