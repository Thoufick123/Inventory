<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-sliders me-2 text-primary"></i>Stock Adjustments & Audits</h2>
        <span class="text-muted">Reconcile physical inventory counts, record write-offs, and submit audit discrepancy requests</span>
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('stock', 'adjust')): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAdjustmentModal">
            <i class="bi bi-plus-circle me-1"></i> Request Adjustment
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filters Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('warehouse/adjustments') ?>" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Warehouse</label>
                <select name="warehouse_id" class="form-select" <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                    <?php if (!function_exists('currentWarehouseId') || currentWarehouseId() === null && count($warehouses) > 1): ?>
                        <option value="">All Warehouses</option>
                    <?php endif; ?>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?= $wh['id'] ?>" <?= (($warehouseId ?? '') == $wh['id'] || (function_exists('currentWarehouseId') && currentWarehouseId() == $wh['id'])) ? 'selected' : '' ?>>
                            <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1): ?>
                    <input type="hidden" name="warehouse_id" value="<?= e($warehouseId ?? ($warehouses[0]['id'] ?? '')) ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="PENDING" <?= ($status ?? '') === 'PENDING' ? 'selected' : '' ?>>PENDING (Awaiting Approval)</option>
                    <option value="APPROVED" <?= ($status ?? '') === 'APPROVED' ? 'selected' : '' ?>>APPROVED (Stock Updated)</option>
                    <option value="REJECTED" <?= ($status ?? '') === 'REJECTED' ? 'selected' : '' ?>>REJECTED</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Adjustment Type</label>
                <select name="adjustment_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="INCREASE" <?= ($type ?? '') === 'INCREASE' ? 'selected' : '' ?>>INCREASE (+ Stock Found)</option>
                    <option value="DECREASE" <?= ($type ?? '') === 'DECREASE' ? 'selected' : '' ?>>DECREASE (- Discrepancy / Damage)</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="<?= url('warehouse/adjustments') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Adjustments Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($adjustments)): ?>
            <div class="text-center py-5">
                <i class="bi bi-check-circle fs-1 text-success d-block mb-3"></i>
                <h5 class="text-muted">No Adjustment Requests Found</h5>
                <p class="text-muted small">Submit an adjustment request whenever a physical audit variance is detected.</p>
                <?php if (hasPermission('stock', 'adjust')): ?>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newAdjustmentModal">
                        Create First Adjustment
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Adjustment #</th>
                            <th>Warehouse</th>
                            <th>Product / SKU</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reason & Notes</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adjustments as $adj): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold font-monospace text-primary"><?= e($adj['adjustment_number']) ?></span>
                                    <br><small class="text-muted"><?= date('d M Y, h:i A', strtotime($adj['created_at'])) ?></small>
                                </td>
                                <td><span class="badge bg-secondary"><?= e($adj['warehouse_name']) ?></span></td>
                                <td>
                                    <span class="fw-bold font-monospace"><?= e($adj['sku']) ?></span>
                                    <br><small class="text-muted"><?= e($adj['product_name']) ?> (<?= e($adj['size_name']) ?>, <?= e($adj['color_name']) ?>)</small>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $adj['adjustment_type'] === 'INCREASE' ? 'success' : 'danger' ?>">
                                        <?= e($adj['adjustment_type']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold fs-6 <?= $adj['adjustment_type'] === 'INCREASE' ? 'text-success' : 'text-danger' ?>">
                                    <?= ($adj['adjustment_type'] === 'INCREASE' ? '+' : '-') . number_format($adj['quantity']) ?> pcs
                                </td>
                                <td>
                                    <span class="fw-semibold"><?= e($adj['reason']) ?></span>
                                    <?php
                                    $adjustmentQrCodes = json_decode((string)($adj['qr_codes'] ?? '[]'), true);
                                    if (is_array($adjustmentQrCodes) && !empty($adjustmentQrCodes)):
                                    ?>
                                        <br><small class="text-danger-emphasis fw-semibold"><?= $adj['status'] === 'APPROVED' ? 'Removed QR' : 'QR tags' ?>: <?= e(implode(', ', $adjustmentQrCodes)) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($adj['notes'])): ?>
                                        <br><small class="text-muted"><?= e($adj['notes']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="fw-semibold text-dark d-block"><?= e($adj['requested_by_name'] ?? 'Staff') ?></small>
                                    <?php if (!empty($adj['approved_by_name'])): ?>
                                        <small class="text-muted">Approved by: <?= e($adj['approved_by_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $adj['status'] === 'APPROVED' ? 'success' : ($adj['status'] === 'PENDING' ? 'warning text-dark' : 'danger') ?>">
                                        <?= e($adj['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($adj['status'] === 'PENDING' && hasPermission('stock', 'adjust')): ?>
                                        <div class="d-inline-flex gap-1">
                                            <form method="POST" action="<?= url('warehouse/adjustments/approve/' . $adj['id']) ?>" class="d-inline">
                                                <?= CSRF::input() ?>
                                                <input type="hidden" name="action" value="APPROVE">
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this stock adjustment and update inventory balance?');">
                                                    <i class="bi bi-check-lg"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" action="<?= url('warehouse/adjustments/approve/' . $adj['id']) ?>" class="d-inline">
                                                <?= CSRF::input() ?>
                                                <input type="hidden" name="action" value="REJECT">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this adjustment request?');">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php elseif ($adj['status'] === 'PENDING'): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis">Pending Review</span>
                                    <?php else: ?>
                                        <span class="text-muted small">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (hasPermission('stock', 'adjust')): ?>
<!-- Modal: New Adjustment Request -->
<div class="modal fade" id="newAdjustmentModal" tabindex="-1" aria-labelledby="newAdjustmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('warehouse/adjustments/store') ?>" id="stockAdjustmentForm">
            <?= CSRF::input() ?>
            <input type="hidden" name="qr_codes" id="adjustmentQrCodes" value="[]">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="newAdjustmentModalLabel"><i class="bi bi-sliders me-2 text-primary"></i>Request Stock Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Warehouse <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="adjustmentWarehouseSelect" class="form-select" required <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                            <?php foreach ($warehouses as $wh): ?>
                                <option value="<?= $wh['id'] ?>" <?= (int)$wh['id'] === (int)($warehouseId ?: ($warehouses[0]['id'] ?? 0)) ? 'selected' : '' ?>><?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($scopedWarehouseId === null && count($warehouses) <= 1): ?>
                            <input type="hidden" name="warehouse_id" value="<?= (int)($warehouseId ?: ($warehouses[0]['id'] ?? 0)) ?>">
                        <?php endif; ?>
                        <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1): ?>
                            <input type="hidden" name="warehouse_id" value="<?= e($scopedWarehouseId ?? ($warehouses[0]['id'] ?? '')) ?>">
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Item / Variant (SKU) <span class="text-danger">*</span></label>
                        <select name="variant_id" id="adjustmentVariantSelect" class="form-select" required>
                            <option value="">Select Variant</option>
                            <?php foreach ($variants as $v): ?>
                                <option value="<?= $v['id'] ?>">
                                    <?= e($v['sku']) ?> — <?= e($v['product_name']) ?> (<?= e($v['size_name']) ?>, <?= e($v['color_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Adjustment Type <span class="text-danger">*</span></label>
                            <select name="adjustment_type" id="adjustmentTypeSelect" class="form-select" required>
                                <option value="INCREASE">INCREASE (+ Found Stock)</option>
                                <option value="DECREASE">DECREASE (- Discrepancy / Damage)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Quantity (Pcs) <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="adjustmentQuantityInput" class="form-control" min="1" step="1" value="1" required>
                        </div>
                    </div>
                    <div class="mb-3 d-none" id="adjustmentQrScanner">
                        <label class="form-label small fw-semibold">Scan QR codes to remove from stock <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" id="adjustmentQrScanInput" class="form-control" placeholder="Scan or enter product QR code..." autocomplete="off">
                            <button type="button" class="btn btn-outline-primary" id="adjustmentQrScanButton"><i class="bi bi-upc-scan me-1"></i>Add QR</button>
                        </div>
                        <small class="form-text text-muted">Each scanned QR removes its available unit(s). Scanned tags are permanently recorded in adjustment history.</small>
                        <div id="adjustmentQrMessage" class="small mt-2" role="status"></div>
                        <div id="adjustmentQrList" class="d-flex flex-wrap gap-1 mt-2"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Primary Reason <span class="text-danger">*</span></label>
                        <select name="reason" class="form-select" required>
                            <option value="Physical Count Discrepancy">Physical Count Discrepancy (Stock Audit)</option>
                            <option value="Damaged / Spoiled in Warehouse">Damaged / Spoiled in Warehouse</option>
                            <option value="Lost / Missing Inventory">Lost / Missing Inventory</option>
                            <option value="Inventory Found / Corrected">Inventory Found / Corrected</option>
                            <option value="Sample / Showroom Pull">Sample / Showroom Pull</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Detailed Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Auditor name, audit batch reference..."></textarea>
                    </div>
                </div>

                <script>
                (() => {
                    const form = document.getElementById('stockAdjustmentForm');
                    const typeSelect = document.getElementById('adjustmentTypeSelect');
                    const quantityInput = document.getElementById('adjustmentQuantityInput');
                    const qrCodesInput = document.getElementById('adjustmentQrCodes');
                    const qrScanner = document.getElementById('adjustmentQrScanner');
                    const scanInput = document.getElementById('adjustmentQrScanInput');
                    const scanButton = document.getElementById('adjustmentQrScanButton');
                    const qrMessage = document.getElementById('adjustmentQrMessage');
                    const qrList = document.getElementById('adjustmentQrList');
                    const warehouseSelect = document.getElementById('adjustmentWarehouseSelect');
                    const variantSelect = document.getElementById('adjustmentVariantSelect');
                    const scannedTags = [];

                    function renderScannedTags() {
                        qrList.replaceChildren();
                        scannedTags.forEach((entry, index) => {
                            const badge = document.createElement('span');
                            badge.className = 'badge bg-dark font-monospace d-inline-flex align-items-center gap-1';
                            const label = document.createElement('span');
                            label.textContent = `${entry.tag} (${entry.quantity} pcs)`;
                            const remove = document.createElement('button');
                            remove.type = 'button';
                            remove.className = 'btn-close btn-close-white';
                            remove.setAttribute('aria-label', `Remove QR ${entry.tag}`);
                            remove.addEventListener('click', () => {
                                scannedTags.splice(index, 1);
                                updateQrState();
                            });
                            badge.append(label, remove);
                            qrList.appendChild(badge);
                        });
                        qrCodesInput.value = JSON.stringify(scannedTags.map(entry => entry.tag));
                        quantityInput.value = scannedTags.reduce((total, entry) => total + entry.quantity, 0) || 1;
                    }

                    function updateQrState() {
                        renderScannedTags();
                        qrMessage.textContent = scannedTags.length ? `${scannedTags.length} QR code(s) selected.` : '';
                        qrMessage.className = 'small mt-2 text-muted';
                    }

                    typeSelect.addEventListener('change', () => {
                        const isDecrease = typeSelect.value === 'DECREASE';
                        qrScanner.classList.toggle('d-none', !isDecrease);
                        quantityInput.readOnly = isDecrease;
                        quantityInput.title = isDecrease ? 'Quantity is set by the scanned QR code(s).' : '';
                        if (!isDecrease) {
                            qrCodesInput.value = '[]';
                        } else {
                            updateQrState();
                            scanInput.focus();
                        }
                    });

                    async function addScannedQr() {
                        const code = scanInput.value.trim();
                        if (!code) return;
                        scanButton.disabled = true;
                        qrMessage.textContent = 'Checking QR code and warehouse stock...';
                        qrMessage.className = 'small mt-2 text-muted';

                        try {
                            const warehouseId = warehouseSelect.value || document.querySelector('input[name="warehouse_id"]')?.value || '';
                            const url = `<?= url('api/warehouse/purchases/lookup-scan') ?>?code=${encodeURIComponent(code)}&warehouse_id=${encodeURIComponent(warehouseId)}&purpose=stock_adjustment`;
                            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                            const result = await response.json();
                            if (result.variant) {
                                const resolvedVariantId = Number(result.variant.id);
                                if (scannedTags.length && Number(scannedTags[0].variantId) !== resolvedVariantId) {
                                    throw new Error('The scanned QR code does not match the selected variant.');
                                }
                                variantSelect.value = String(resolvedVariantId);
                            }
                            if (!response.ok || !result.success || !result.variant) {
                                throw new Error(result.message || 'This QR code is not eligible for stock adjustment.');
                            }
                            if (result.eligible === false) {
                                throw new Error(result.message || 'This QR code is not eligible for stock adjustment.');
                            }

                            const variant = result.variant;
                            const tag = String(variant.piece_tag || result.extracted_tag || '').trim().toUpperCase();
                            if (!tag) throw new Error('The scanned QR code did not resolve to a product tag.');
                            if (scannedTags.some(entry => entry.tag === tag)) throw new Error(`QR code ${tag} is already in this request.`);
                            if (scannedTags.length && Number(scannedTags[0].variantId) !== Number(variant.id)) {
                                throw new Error('A single adjustment request can only contain QR codes for one variant.');
                            }

                            const isPack = variant.is_series || variant.is_complementary;
                            const tagQuantity = isPack ? Math.max(1, Number(variant.pack_quantity) || 1) : 1;
                            const availableStock = Number(variant.available_stock) || 0;
                            const currentQuantity = scannedTags.reduce((total, entry) => total + entry.quantity, 0);
                            if (currentQuantity + tagQuantity > availableStock) {
                                throw new Error(`Only ${availableStock} available pcs remain; this QR would exceed the available stock.`);
                            }
                            scannedTags.push({ tag, quantity: tagQuantity, variantId: variant.id, availableStock });
                            scanInput.value = '';
                            updateQrState();
                            scanInput.focus();
                        } catch (error) {
                            qrMessage.textContent = error.message || 'QR scan failed.';
                            qrMessage.className = 'small mt-2 text-danger fw-semibold';
                        } finally {
                            scanButton.disabled = false;
                        }
                    }

                    scanButton.addEventListener('click', addScannedQr);
                    scanInput.addEventListener('keydown', event => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            addScannedQr();
                        }
                    });

                    form.addEventListener('submit', event => {
                        if (typeSelect.value === 'DECREASE' && scannedTags.length === 0) {
                            event.preventDefault();
                            qrMessage.textContent = 'Scan at least one product QR code before requesting a stock decrease.';
                            qrMessage.className = 'small mt-2 text-danger fw-semibold';
                            scanInput.focus();
                        }
                    });

                    document.getElementById('newAdjustmentModal').addEventListener('hidden.bs.modal', () => {
                        scannedTags.splice(0, scannedTags.length);
                        variantSelect.value = '';
                        typeSelect.value = 'INCREASE';
                        quantityInput.value = '1';
                        quantityInput.readOnly = false;
                        quantityInput.title = '';
                        qrScanner.classList.add('d-none');
                        qrCodesInput.value = '[]';
                        qrList.replaceChildren();
                        qrMessage.textContent = '';
                    });
                })();
                </script>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Submit for Approval</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
