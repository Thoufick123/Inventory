<div class="page-header mb-4">
    <h2><i class="bi bi-box-seam me-2 text-success"></i>Pack Finished Production Batch</h2>
    <span class="text-muted">Pack QC-passed goods into delivery boxes and assign destination warehouse</span>
</div>

<?php if ($selectedQC && ($selectedQC['remaining_qty'] ?? 1) <= 0 && ($selectedQC['already_packed_qty'] ?? 0) > 0): ?>
    <div class="alert alert-info border border-info-subtle shadow-sm p-3 mb-4 rounded-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle">
                    <i class="bi bi-info-circle-fill fs-2"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1 text-dark">QC Batch #<?= $selectedQC['id'] ?> is Already Fully Packed</h5>
                    <p class="text-muted small mb-0">
                        All <strong><?= number_format($selectedQC['total_qc_passed'] ?? $selectedQC['passed_qty']) ?> passed pieces</strong> from this QC inspection have already been packed into finished batch(es) and dispatched. There are 0 remaining pieces to pack.
                    </p>
                    <?php if (!empty($existingBatches)): ?>
                    <div class="mt-2 d-flex gap-2 flex-wrap align-items-center">
                        <span class="small fw-semibold text-dark">Packed In:</span>
                        <?php foreach ($existingBatches as $eb): ?>
                            <span class="badge bg-white text-dark border shadow-xs p-2">
                                <i class="bi bi-box-seam me-1 text-primary"></i> <strong>Batch #<?= e($eb['batch_number']) ?></strong> &bull; <?= $eb['total_qty'] ?> pcs &bull; Status: <span class="badge bg-<?= $eb['status'] === 'DISPATCHED' ? 'warning-subtle text-dark border' : 'success' ?>"><?= e($eb['status']) ?></span>
                                <?php if (!empty($eb['warehouse_name'])): ?>
                                    &rarr; <?= e($eb['warehouse_name']) ?>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('production/batches' . (!empty($existingBatches[0]['batch_number']) ? '?search=' . urlencode($existingBatches[0]['batch_number']) : '')) ?>" class="btn btn-primary fw-semibold">
                    <i class="bi bi-box-seam me-1"></i> View Finished Batch
                </a>
                <a href="<?= url('production/inventory') ?>" class="btn btn-outline-secondary fw-semibold">
                    <i class="bi bi-boxes me-1"></i> View Inventory
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="<?= url('production/batches/store') ?>" id="packBatchForm">
    <?= CSRF::field() ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Packing Details</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold required">Select QC Inspection Batch <span class="text-danger">*</span></label>
                        <select name="qc_batch_id" id="qc_batch_id" class="form-select <?= empty($selectedQC) ? 'border-primary' : '' ?>" required onchange="handleQCBatchChange(this.value)">
                            <option value="">-- Select QC Batch --</option>
                            <?php foreach ($qcBatches as $qb): ?>
                                <option value="<?= $qb['id'] ?>" <?= ($selectedQC && (int)$selectedQC['id'] === (int)$qb['id']) ? 'selected' : '' ?>>
                                    QC #<?= $qb['id'] ?> (<?= e($qb['program_number'] ?? '') ?> — <?= e($qb['product_name'] ?? '') ?>) [<?= number_format($qb['remaining_qty'] ?? $qb['passed_qty']) ?> pcs remaining / <?= number_format($qb['total_qc_passed'] ?? $qb['passed_qty']) ?> pcs passed]
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small text-muted">Select a QC-passed batch to load approved quantities.</div>
                        <?php if (empty($qcBatches) && !$selectedQC): ?>
                            <div class="alert alert-warning py-2 px-3 small mt-2 mb-0">
                                <i class="bi bi-info-circle me-1"></i> All QC passed batches have already been fully packed into finished batches. <a href="<?= url('production/qc/create') ?>" class="alert-link">Create a QC inspection</a>.
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($selectedQC): ?>
                        <input type="hidden" name="program_id" id="program_id" value="<?= $selectedQC['program_id'] ?>">
                        <div class="mb-3 p-3 bg-light rounded border">
                            <div class="small text-muted fw-semibold">Program &amp; Product</div>
                            <div class="fw-bold text-dark"><?= e($selectedQC['program_number']) ?> — <?= e($selectedQC['product_name']) ?></div>
                            <div class="row g-2 mt-2 pt-2 border-top">
                                <div class="col-4">
                                    <div class="small text-muted">Total Passed</div>
                                    <div class="fw-bold text-dark"><?= number_format($selectedQC['total_qc_passed'] ?? $selectedQC['passed_qty']) ?> pcs</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Already Packed</div>
                                    <div class="fw-bold text-danger"><?= number_format($selectedQC['already_packed_qty'] ?? 0) ?> pcs</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Remaining</div>
                                    <div class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i><?= number_format($selectedQC['remaining_qty'] ?? $selectedQC['passed_qty']) ?> pcs</div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="program_id" id="program_id" value="">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold required">Destination Warehouse <span class="text-danger">*</span></label>
                        <select name="destination_warehouse_id" id="destination_warehouse_id" class="form-select" required>
                            <option value="">-- Select Warehouse --</option>
                            <?php foreach ($warehouses as $w): ?>
                                <option value="<?= $w['id'] ?>"><?= e($w['name']) ?> (<?= e($w['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold required">Total Box / Carton Count <span class="text-danger">*</span></label>
                        <input type="number" name="box_count" id="box_count" class="form-control" min="1" value="1" required placeholder="Enter number of boxes...">
                        <div class="form-text small text-muted">Total number of boxes or cartons packed for this batch.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm h-100 d-flex flex-column">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-grid-3x3 me-2 text-primary"></i>Packed Quantities</h5>
                    <span class="badge bg-success fs-6 px-3 py-2" id="totalPackedBadge">0 pcs Packed</span>
                </div>
                <div class="card-body p-0 flex-grow-1">
                    <?php if (!$selectedQC): ?>
                        <div class="p-5 text-center text-muted my-auto">
                            <div class="mb-3">
                                <div class="d-inline-flex p-3 rounded-circle bg-light">
                                    <i class="bi bi-arrow-left-circle fs-1 text-primary"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold text-dark">No QC Batch Selected</h5>
                            <p class="text-muted mb-0">Please select a QC inspection batch from the dropdown on the left to load the approved variant quantities.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="packedItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Variant</th>
                                        <th>QC Status &amp; Remaining</th>
                                        <th>Approved QR Code(s) to Pack</th>
                                        <th style="width: 180px;">Quantity to Pack <span class="text-danger">*</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $idx => $it): 
                                        $remQty = (int)($it['remaining_qty'] ?? $it['passed_qty']);
                                        $availablePieceTags = array_values($it['available_piece_tags'] ?? []);
                                        $hasAvailableTags = !empty($availablePieceTags);
                                        $packQty = $hasAvailableTags ? min($remQty, count($availablePieceTags)) : $remQty;
                                        $isZero = ($packQty <= 0);
                                        $rejectedQty = (int)($it['rejected_qty'] ?? 0);
                                        $reworkQty = (int)($it['rework_qty'] ?? 0);
                                    ?>
                                        <tr class="variant-item-row <?= $isZero ? 'table-light opacity-75' : '' ?>" data-variant-name="<?= e($it['size_name'] ?? $it['display_size'] ?? 'Standard') ?> / <?= e($it['color_name'] ?? $it['display_color'] ?? 'Assorted') ?>" data-passed="<?= $packQty ?>" data-is-zero="<?= $isZero ? '1' : '0' ?>">
                                            <td>
                                                <input type="hidden" name="items[<?= $idx ?>][qc_item_id]" value="<?= $it['id'] ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][variant_id]" value="<?= $it['variant_id'] ?? '' ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][size_name]" value="<?= e($it['size_name'] ?? $it['display_size'] ?? '') ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][color_name]" value="<?= e($it['color_name'] ?? $it['display_color'] ?? '') ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][sku]" value="<?= e($it['sku'] ?? '') ?>">
                                                <div class="fw-bold text-dark"><?= e($it['size_name'] ?? $it['display_size'] ?? 'Standard') ?> / <?= e($it['color_name'] ?? $it['display_color'] ?? 'Assorted') ?></div>
                                                <small class="text-muted font-monospace"><?= e($it['sku'] ?? '') ?></small>
                                            </td>
                                            <td>
                                                <div>
                                                    <?php if ($isZero): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                                            <i class="bi bi-slash-circle me-1"></i>0 pcs Available (Fully Packed)
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                            <i class="bi bi-box-seam me-1"></i><?= number_format($remQty) ?> pcs Available
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (($it['already_packed'] ?? 0) > 0): ?>
                                                    <small class="text-muted d-block mt-1">
                                                        Total Passed: <?= number_format($it['passed_qty']) ?> pcs (<?= number_format($it['already_packed']) ?> already packed)
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted d-block mt-1">
                                                        Total Passed: <?= number_format($it['passed_qty']) ?> pcs
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($hasAvailableTags): ?>
                                                    <div class="small fw-semibold text-success mb-1">
                                                        <i class="bi bi-patch-check-fill me-1"></i>
                                                        <?= number_format(count($availablePieceTags)) ?> approved QR code(s) available
                                                    </div>
                                                    <?php if ($rejectedQty > 0 || $reworkQty > 0): ?>
                                                        <div class="small text-danger mb-2">
                                                            <?= $rejectedQty > 0 ? number_format($rejectedQty) . ' rejected' : '' ?>
                                                            <?= $rejectedQty > 0 && $reworkQty > 0 ? ' · ' : '' ?>
                                                            <?= $reworkQty > 0 ? number_format($reworkQty) . ' need rework' : '' ?> QR code(s) excluded.
                                                            Re-inspect and pass them in QC before packing.
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="piece-tag-picker">
                                                        <input type="search"
                                                               class="form-control form-control-sm piece-tag-search mb-2"
                                                               placeholder="Search QR codes..."
                                                               aria-label="Search QR codes for <?= e($it['sku'] ?? '') ?>"
                                                               oninput="filterPieceTags(this)"
                                                               <?= $isZero ? 'disabled' : '' ?>>
                                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                                            <small class="text-muted piece-tag-count" aria-live="polite"></small>
                                                            <div class="d-flex gap-1">
                                                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" onclick="selectPieceTagsForQuantity(this)" <?= $isZero ? 'disabled' : '' ?>>Select Qty</button>
                                                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="clearPieceTags(this)" <?= $isZero ? 'disabled' : '' ?>>Clear</button>
                                                            </div>
                                                        </div>
                                                        <div class="piece-tag-options <?= $isZero ? 'opacity-50' : '' ?>">
                                                        <?php foreach ($availablePieceTags as $tagIndex => $tag): ?>
                                                            <label class="piece-tag-option" data-piece-tag-option>
                                                                <input type="checkbox"
                                                                       name="items[<?= $idx ?>][piece_tags][]"
                                                                       class="form-check-input piece-tags-input mt-0"
                                                                       value="<?= e($tag) ?>"
                                                                       <?= $tagIndex < $packQty ? 'checked' : '' ?>
                                                                       <?= $isZero ? 'disabled' : '' ?>
                                                                       onchange="syncPieceTagSelection(this)">
                                                                <span class="font-monospace"><?= e($tag) ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                        </div>
                                                        <div class="small text-muted mt-1">Check QR codes to pack. The quantity updates to match your selection.</div>
                                                <?php else: ?>
                                                    <span class="small text-muted">Individual QC QR codes are unavailable.</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" 
                                                           name="items[<?= $idx ?>][quantity]" 
                                                           class="form-control form-control-sm packed-input fw-semibold <?= $isZero ? 'bg-light text-muted border-secondary-subtle' : '' ?>" 
                                                           min="0" 
                                                           max="<?= $packQty ?>"
                                                           value="<?= $packQty ?>"
                                                           <?= $isZero ? 'disabled readonly tabindex="-1"' : '' ?>
                                                           oninput="calcPackedTotal(); updatePieceTagCounts()">
                                                    <span class="input-group-text <?= $isZero ? 'bg-light text-muted' : 'bg-light text-muted' ?>">pcs</span>
                                                </div>
                                                <?php if ($isZero): ?>
                                                    <div class="small text-muted mt-1"><i class="bi bi-lock-fill me-1"></i>0 pcs remaining (Disabled)</div>
                                                <?php else: ?>
                                                    <div class="small text-muted mt-1">Max: <?= number_format($packQty) ?> pcs</div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center border-top">
                    <a href="<?= url('production/batches') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </a>
                    <?php if ($selectedQC && ($selectedQC['remaining_qty'] ?? 1) <= 0 && ($selectedQC['already_packed_qty'] ?? 0) > 0): ?>
                        <a href="<?= url('production/batches' . (!empty($existingBatches[0]['batch_number']) ? '?search=' . urlencode($existingBatches[0]['batch_number']) : '')) ?>" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-box-seam me-1"></i> View Finished Batch (<?= e($existingBatches[0]['batch_number'] ?? '') ?>)
                        </a>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btnSavePackedBatch">
                            <i class="bi bi-check2-circle me-1"></i> Save Packed Batch
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.piece-tag-options {
    max-height: 180px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.25rem 0.5rem;
}

.piece-tag-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.2rem 0;
    cursor: pointer;
    overflow-wrap: anywhere;
}

.piece-tag-option:hover {
    background: #f8f9fa;
}
</style>

<script>
function handleQCBatchChange(qcBatchId) {
    if (qcBatchId) {
        window.location.href = '<?= url('production/batches/create') ?>?qc_batch_id=' + encodeURIComponent(qcBatchId);
    } else {
        window.location.href = '<?= url('production/batches/create') ?>';
    }
}

function calcPackedTotal() {
    let total = 0;
    document.querySelectorAll('.packed-input').forEach(inp => {
        if (!inp.disabled) {
            const val = parseInt(inp.value, 10);
            if (!isNaN(val) && val > 0) {
                total += val;
            }
        }
    });
    const badge = document.getElementById('totalPackedBadge');
    if (badge) {
        badge.innerText = total.toLocaleString() + ' pcs Packed';
    }
    return total;
}

function updatePieceTagCounts() {
    document.querySelectorAll('.variant-item-row').forEach(row => {
        const options = row.querySelector('.piece-tag-options');
        const countLabel = row.querySelector('.piece-tag-count');
        const quantityInput = row.querySelector('.packed-input');
        if (!options || !countLabel || !quantityInput) return;

        const selectedCount = options.querySelectorAll('.piece-tags-input:checked').length;
        countLabel.textContent = `${selectedCount} selected / ${quantityInput.value || 0} qty`;
    });
}

function syncPieceTagSelection(input) {
    const row = input.closest('.variant-item-row');
    const options = row?.querySelector('.piece-tag-options');
    const quantityInput = row?.querySelector('.packed-input');
    if (!options || !quantityInput) return;

    quantityInput.value = options.querySelectorAll('.piece-tags-input:checked').length;
    quantityInput.classList.remove('is-invalid', 'border-danger');
    options.classList.remove('is-invalid', 'border-danger');
    updatePieceTagCounts();
    calcPackedTotal();
}

function filterPieceTags(input) {
    const search = input.value.trim().toLowerCase();
    const options = input.closest('.piece-tag-picker')?.querySelectorAll('[data-piece-tag-option]') ?? [];
    options.forEach(option => {
        option.hidden = !option.textContent.toLowerCase().includes(search);
    });
}

function selectPieceTagsForQuantity(button) {
    const row = button.closest('.variant-item-row');
    const options = row?.querySelector('.piece-tag-options');
    const quantityInput = row?.querySelector('.packed-input');
    if (!options || !quantityInput) return;

    const quantity = Math.min(
        parseInt(quantityInput.max, 10) || 0,
        Math.max(0, parseInt(quantityInput.value, 10) || 0)
    );
    options.querySelectorAll('.piece-tags-input').forEach((checkbox, index) => {
        checkbox.checked = index < quantity;
    });
    syncPieceTagSelection(options.querySelector('.piece-tags-input'));
}

function clearPieceTags(button) {
    const row = button.closest('.variant-item-row');
    const options = row?.querySelector('.piece-tag-options');
    if (!options) return;

    options.querySelectorAll('.piece-tags-input').forEach(checkbox => {
        checkbox.checked = false;
    });
    syncPieceTagSelection(options.querySelector('.piece-tags-input'));
}

document.addEventListener('DOMContentLoaded', function() {
    calcPackedTotal();
    updatePieceTagCounts();

    const form = document.getElementById('packBatchForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const qcBatchSelect = document.getElementById('qc_batch_id');
            const warehouseSelect = document.getElementById('destination_warehouse_id');
            const boxCountInput = document.getElementById('box_count');

            // 1. Validate QC Batch selection
            if (!qcBatchSelect || !qcBatchSelect.value) {
                e.preventDefault();
                alert('Please select a QC Inspection Batch first.');
                if (qcBatchSelect) {
                    qcBatchSelect.classList.add('is-invalid', 'border-danger');
                    qcBatchSelect.focus();
                }
                return false;
            } else {
                qcBatchSelect.classList.remove('is-invalid', 'border-danger');
            }

            // 2. Validate Destination Warehouse
            if (!warehouseSelect || !warehouseSelect.value) {
                e.preventDefault();
                alert('Please select a Destination Warehouse.');
                if (warehouseSelect) {
                    warehouseSelect.classList.add('is-invalid', 'border-danger');
                    warehouseSelect.focus();
                }
                return false;
            } else {
                warehouseSelect.classList.remove('is-invalid', 'border-danger');
            }

            // 3. Validate Box Count
            const boxVal = parseInt(boxCountInput?.value, 10);
            if (!boxCountInput || isNaN(boxVal) || boxVal < 1) {
                e.preventDefault();
                alert('Total Box / Carton Count is required and must be at least 1.');
                if (boxCountInput) {
                    boxCountInput.classList.add('is-invalid', 'border-danger');
                    boxCountInput.focus();
                }
                return false;
            } else {
                boxCountInput?.classList.remove('is-invalid', 'border-danger');
            }

            // 4. Validate Variant Quantities
            const packedInputs = document.querySelectorAll('.packed-input');
            if (packedInputs.length === 0) {
                e.preventDefault();
                alert('No variant items available in the selected QC batch. Please choose a valid QC batch.');
                return false;
            }

            let totalPacked = 0;
            let hasError = false;

            packedInputs.forEach(inp => {
                if (inp.disabled) return;

                const tr = inp.closest('.variant-item-row');
                const variantLabel = tr ? tr.getAttribute('data-variant-name') : 'Variant';
                const maxPassed = tr ? parseInt(tr.getAttribute('data-passed'), 10) : 0;
                const val = parseInt(inp.value, 10);

                if (isNaN(val) || val < 0) {
                    e.preventDefault();
                    alert(`Invalid quantity for ${variantLabel}. Quantity must be 0 or greater.`);
                    inp.classList.add('is-invalid', 'border-danger');
                    inp.focus();
                    hasError = true;
                    return false;
                }

                if (val > maxPassed) {
                    e.preventDefault();
                    alert(`Quantity to pack for ${variantLabel} (${val} pcs) cannot exceed the remaining available quantity (${maxPassed} pcs).`);
                    inp.classList.add('is-invalid', 'border-danger');
                    inp.focus();
                    hasError = true;
                    return false;
                }

                const pieceTagOptions = tr ? tr.querySelector('.piece-tag-options') : null;
                const selectedPieceTagCount = pieceTagOptions ? pieceTagOptions.querySelectorAll('.piece-tags-input:checked').length : 0;
                if (pieceTagOptions && selectedPieceTagCount !== val) {
                    e.preventDefault();
                    alert(`Select exactly ${val} QR code(s) for ${variantLabel}.`);
                    pieceTagOptions.classList.add('is-invalid', 'border-danger');
                    pieceTagOptions.querySelector('.piece-tags-input')?.focus();
                    hasError = true;
                    return false;
                }
                if (pieceTagOptions) pieceTagOptions.classList.remove('is-invalid', 'border-danger');

                inp.classList.remove('is-invalid', 'border-danger');
                totalPacked += val;
            });

            if (hasError) return false;

            if (totalPacked <= 0) {
                e.preventDefault();
                alert('Please enter a packed quantity greater than 0 for at least one available variant item.');
                const firstEnabled = document.querySelector('.packed-input:not([disabled])');
                if (firstEnabled) firstEnabled.focus();
                return false;
            }

            return true;
        });
    }
});
</script>
