<?php 
$canClassify = hasPermission('returns', 'classify');
$returnQrDate = !empty($return['created_at']) ? date('my', strtotime($return['created_at'])) : date('my');
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="h3 mb-1 text-gray-800">
            <i class="bi bi-clipboard-check me-2 text-primary"></i><?= $canClassify ? 'Inspect Return Batch #' : 'Return Batch #' ?><?= htmlspecialchars($return['return_number']) ?>
        </h2>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-light text-dark border font-monospace">
                <i class="bi bi-box-arrow-right me-1 text-danger"></i>Source: <strong><?= htmlspecialchars($return['source_name']) ?></strong>
            </span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">
                <i class="bi bi-building me-1"></i>Receiving Warehouse: <strong><?= htmlspecialchars($return['warehouse_name']) ?> (<?= htmlspecialchars($return['warehouse_code'] ?? 'WH-01') ?>)</strong>
            </span>
            <span class="badge bg-secondary font-monospace">
                Status: <?= htmlspecialchars($return['status'] ?? 'RECEIVED') ?>
            </span>
        </div>
    </div>
    <div>
        <a href="<?= url('warehouse/returns') ?>" class="btn btn-outline-secondary fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Returns &amp; QC
        </a>
    </div>
</div>

<form method="POST" action="<?= url('warehouse/returns/classify/' . $return['id']) ?>" class="card border-0 shadow-sm rounded-3 p-4">
    <?= CSRF::field() ?>

    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Return Items Quality Classification</h5>
    <p class="text-muted small">
        Classify items into <strong class="text-success">Good Condition</strong> (Available stock) |
        <strong class="text-danger">Damaged</strong> (Scrap) |
        <strong style="color:#fd7e14">Defective</strong> (Production Rework) |
        <strong style="color:#6f42c1">Wrong Size</strong> (Exchange/Rework)
    </p>

    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Variant / SKU</th>
                    <th>Returned Qty</th>
                    <th style="width: 18%;">Good Qty (pcs)</th>
                    <th style="width: 20%;" id="nonGoodHeader">Damaged / Rework Qty (pcs)</th>
                    <th style="width: 25%;">Classification Type</th>
                    <th style="width: 15%;" class="text-center">Return Tag</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($return['items'] as $item): ?>
                    <tr data-returned="<?= (int)$item['returned_qty'] ?>">
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></div>
                            <?php 
                                $catParts = array_filter([$item['category_name'] ?? '', $item['subcategory_name'] ?? '', $item['sub_subcategory_name'] ?? '']);
                                if (!empty($catParts)):
                            ?>
                                <div class="my-1"><span class="badge bg-light text-primary border small font-monospace"><i class="bi bi-diagram-3-fill me-1 text-warning"></i><?= htmlspecialchars(implode(' › ', $catParts)) ?></span></div>
                            <?php endif; ?>
                            <code><?= htmlspecialchars($item['sku']) ?></code>
                            <div class="mt-2 d-flex align-items-center gap-2">
                                <?php
                                    $initial = static function ($value): string {
                                        $clean = trim((string)$value);
                                        return $clean !== '' ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $clean), 0, 1)) : 'X';
                                    };
                                    $returnQrPrefix = $initial($item['category_name'] ?? '')
                                        . $initial($item['subcategory_name'] ?? '')
                                        . $initial($item['sub_subcategory_name'] ?? '');
                                    $returnQrValue = trim((string)($item['returned_tag_code'] ?? '')) !== ''
                                        ? $item['returned_tag_code']
                                        : $returnQrPrefix . $returnQrDate . str_pad((string)$item['id'], 3, '0', STR_PAD_LEFT);
                                ?>
                                <div class="return-item-qr border rounded p-1 bg-white" data-qr-value="<?= htmlspecialchars($returnQrValue) ?>" style="width:58px;height:58px;"></div>
                                <small class="font-monospace text-dark fw-bold">QR: <?= htmlspecialchars($returnQrValue) ?></small>
                            </div>
                        </td>
                        <td><span class="badge bg-secondary fs-6"><?= $item['returned_qty'] ?> pcs</span></td>
                        <td>
                            <input type="number"
                                   name="good_qty[<?= $item['id'] ?>]"
                                   class="form-control text-success fw-bold good-qty-input"
                                   min="0"
                                   max="<?= $item['returned_qty'] ?>"
                                   value="<?= (int)$item['good_qty'] ?>"
                                   data-returned="<?= (int)$item['returned_qty'] ?>"
                                   <?= !$canClassify ? 'readonly disabled' : '' ?>>
                        </td>
                        <td>
                            <input type="number"
                                   name="damaged_qty[<?= $item['id'] ?>]"
                                   class="form-control fw-bold damaged-qty-input"
                                   min="0"
                                   max="<?= $item['returned_qty'] ?>"
                                   value="<?= max(0, (int)$item['returned_qty'] - (int)$item['good_qty']) ?>"
                                   style="color:#fd7e14;"
                                   <?= !$canClassify ? 'readonly disabled' : '' ?>>
                        </td>
                        <td>
                            <select name="classification[<?= $item['id'] ?>]" class="form-select classification-select" <?= !$canClassify ? 'disabled' : '' ?>>
                                <option value="GOOD"       <?= $item['classification'] === 'GOOD'       ? 'selected' : '' ?>>GOOD (Re-Sellable Stock)</option>
                                <option value="DAMAGED"    <?= $item['classification'] === 'DAMAGED'    ? 'selected' : '' ?>>DAMAGED (Scrap/Disposal)</option>
                                <option value="DEFECTIVE"  <?= $item['classification'] === 'DEFECTIVE'  ? 'selected' : '' ?>>DEFECTIVE (Production Rework)</option>
                                <option value="WRONG_SIZE" <?= $item['classification'] === 'WRONG_SIZE' ? 'selected' : '' ?>>WRONG SIZE / EXCHANGE</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <?php if (hasPermission('wh_labels', 'create')): ?>
                                <a href="<?= url('warehouse/labels?source_type=returns&view=generate&tab=return_tags&return_id=' . $return['id'] . '&return_item_id=' . $item['id'] . '&return_variant_id=' . $item['variant_id']) ?>"
                                   class="btn btn-sm btn-outline-danger" title="Generate Return QR Tag">
                                    <i class="bi bi-qr-code me-1"></i> Return Tag
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div>
            <?php if (hasPermission('wh_labels', 'create')): ?>
                <a href="<?= url('warehouse/labels?source_type=returns&view=generate&tab=return_tags&return_id=' . $return['id']) ?>" class="btn btn-outline-danger">
                    <i class="bi bi-qr-code-scan me-1"></i> Open Return QR Suite
                </a>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="<?= url('warehouse/returns') ?>" class="btn btn-light">Back to List</a>
            <?php if ($canClassify): ?>
            <button type="submit" class="btn btn-primary px-4" onclick="return confirm('Classify returns and credit inventory balances?');">
                <i class="bi bi-check-circle me-1"></i> Save &amp; Credit Inventory
            </button>
            <?php else: ?>
            <span class="badge bg-secondary px-3 py-2"><i class="bi bi-lock me-1"></i> Read-Only View</span>
            <?php endif; ?>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.return-item-qr').forEach(function (el) {
        if (typeof QRCode !== 'undefined') {
            new QRCode(el, { text: el.dataset.qrValue, width: 50, height: 50, correctLevel: QRCode.CorrectLevel.M });
        }
    });

    // Classification config: label, colors, whether damaged field is used
    const classConfig = {
        'GOOD':       { rowBorder: '#198754', inputColor: '#198754', label: 'Good Qty',      clearDamaged: true  },
        'DAMAGED':    { rowBorder: '#dc3545', inputColor: '#dc3545', label: 'Damaged Qty',   clearDamaged: false },
        'DEFECTIVE':  { rowBorder: '#fd7e14', inputColor: '#fd7e14', label: 'Defective Qty', clearDamaged: false },
        'WRONG_SIZE': { rowBorder: '#6f42c1', inputColor: '#6f42c1', label: 'Wrong Size Qty',clearDamaged: false },
    };

    function applyClassificationUI(row) {
        const sel       = row.querySelector('.classification-select');
        const goodInput = row.querySelector('.good-qty-input');
        const dmgInput  = row.querySelector('.damaged-qty-input');
        const dmgCell   = row.querySelector('.damaged-qty-cell');
        if (!sel || !goodInput || !dmgInput) return;

        const val      = sel.value;
        const cfg      = classConfig[val] || classConfig['GOOD'];
        const returned = parseInt(goodInput.getAttribute('data-returned')) || 0;
        const good     = Math.min(Math.max(0, parseInt(goodInput.value) || 0), returned);

        // Row left-border highlight
        row.style.borderLeft = '4px solid ' + cfg.rowBorder;
        row.style.background = cfg.rowBorder + '08'; // very faint background tint

        // Good qty input styling
        goodInput.style.color       = '#198754';
        goodInput.style.borderColor = cfg.rowBorder;

        if (cfg.clearDamaged) {
            // GOOD: fill good qty with full returned amount, zero out & disable damaged field
            goodInput.value          = returned;
            dmgInput.value           = 0;
            dmgInput.disabled        = true;
            dmgInput.style.color     = '#adb5bd';
            dmgInput.style.background = '#f8f9fa';
            dmgInput.style.borderColor = '#dee2e6';
            if (dmgCell) dmgCell.style.opacity = '0.45';
        } else {
            // Non-GOOD: enable and fill remainder
            const remainder = Math.max(0, returned - good);
            if (parseInt(dmgInput.value) === 0 || dmgInput.value === '') {
                dmgInput.value = remainder;
            }
            dmgInput.disabled        = false;
            dmgInput.style.color     = cfg.inputColor;
            dmgInput.style.background = '';
            dmgInput.style.borderColor = cfg.rowBorder;
            if (dmgCell) dmgCell.style.opacity = '1';
        }
    }

    // When good qty changes: recalculate non-good remainder
    document.querySelectorAll('.good-qty-input').forEach(function (goodInput) {
        goodInput.addEventListener('input', function () {
            const row      = this.closest('tr');
            const sel      = row.querySelector('.classification-select');
            const dmgInput = row.querySelector('.damaged-qty-input');
            const returned = parseInt(this.getAttribute('data-returned')) || 0;
            const good     = Math.min(Math.max(0, parseInt(this.value) || 0), returned);

            if (sel && sel.value !== 'GOOD') {
                const remainder = Math.max(0, returned - good);
                if (dmgInput) dmgInput.value = remainder;
            }
            applyClassificationUI(row);
        });
    });

    // When classification changes: update all UI
    document.querySelectorAll('.classification-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            applyClassificationUI(this.closest('tr'));
        });
        // Apply on page load for pre-saved state
        applyClassificationUI(sel.closest('tr'));
    });

});
</script>
