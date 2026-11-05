<?php
// views/website_orders/damage/outward_create.php — Create Damaged Stock Outward Batch
?>

<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('warehouse/website-orders') ?>">Website Orders</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('warehouse/website-orders/damage') ?>">Damage Management</a></li>
                    <li class="breadcrumb-item active">New Outward Batch</li>
                </ol>
            </nav>
            <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-up-right text-danger"></i> Dispatch Damaged Stock to Warehouse
            </h4>
            <div class="text-muted small">Bundle damaged products from Website division into an Outward Return Batch for warehouse reception & inspection.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('warehouse/website-orders/damage/outward') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Outward Batches List
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('warehouse/website-orders/damage/outward/store') ?>" id="outwardForm">
        <?= csrf_field() ?>

        <div class="row g-4">
            <!-- Left Side: Batch Configuration & Transport Details -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="card-title mb-0 fw-bold"><i class="bi bi-truck me-2 text-danger"></i>Destination & Transport</h6>
                    </div>
                    <div class="card-body">
                        <!-- Destination Warehouse -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Destination Warehouse <span class="text-danger">*</span></label>
                            <select name="warehouse_id" class="form-select" required>
                                <option value="">-- Select Central Warehouse --</option>
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?= $wh['id'] ?>" <?= (isset($defaultWarehouseId) && $defaultWarehouseId == $wh['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($wh['warehouse_name'] ?? $wh['name'] ?? 'Warehouse #' . $wh['id']) ?> (<?= htmlspecialchars($wh['warehouse_code'] ?? $wh['code'] ?? '') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Transport Carrier / Mode -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Transport / Carrier Name</label>
                            <input type="text" name="carrier_name" class="form-control" placeholder="e.g. GRM Internal Van / Professional Courier">
                        </div>

                        <!-- Vehicle Number -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Vehicle Number</label>
                            <input type="text" name="vehicle_no" class="form-control" placeholder="e.g. TN-38-AB-1234">
                        </div>

                        <!-- Driver Name & Phone -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Driver Name</label>
                                <input type="text" name="driver_name" class="form-control" placeholder="Driver name">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Driver Phone</label>
                                <input type="text" name="driver_phone" class="form-control" placeholder="Mobile no.">
                            </div>
                        </div>

                        <!-- Notes / Remarks -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Dispatch Notes / Instructions</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Additional notes for warehouse QA/Inward team..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="card border-0 shadow-sm bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="bi bi-clipboard-check text-primary me-2"></i>Outward Summary</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Selected Damage Records:</span>
                            <span class="fw-bold" id="selectedRecordsCount">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Damaged Pieces:</span>
                            <span class="fw-bold fs-5 text-danger" id="selectedTotalQty">0 pcs</span>
                        </div>
                        <hr>
                        <button type="submit" class="btn btn-danger w-100 py-2 fw-semibold" id="submitBtn" disabled>
                            <i class="bi bi-box-arrow-up-right me-1"></i> Generate Outward Batch & Gate Pass
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Side: Pending Damaged Items Selection -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 fw-bold">
                            <i class="bi bi-list-check me-2 text-danger"></i>Select Damaged Items for Dispatch
                        </h6>
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllBtn">
                                Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">
                                Deselect All
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($pendingDamages)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-shield-check fs-1 text-success d-block mb-2"></i>
                                <h6 class="text-muted fw-bold">No Pending Damaged Stock in Website Channel</h6>
                                <p class="text-muted small">All recorded damages have either been dispatched or resolved.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40" class="text-center">
                                                <input type="checkbox" class="form-check-input" id="masterCheckbox">
                                            </th>
                                            <th>Damage No. / Date</th>
                                            <th>Product & Variant</th>
                                            <th>Defect Type</th>
                                            <th class="text-center">Available Qty</th>
                                            <th class="text-center" width="130">Dispatch Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingDamages as $dmg): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" name="damage_ids[]" value="<?= $dmg['id'] ?>" 
                                                           class="form-check-input damage-checkbox" 
                                                           data-qty="<?= $dmg['quantity'] ?>"
                                                           data-variant="<?= $dmg['variant_id'] ?>"
                                                           data-id="<?= $dmg['id'] ?>">
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($dmg['damage_number']) ?></div>
                                                    <small class="text-muted"><?= date('M d, Y', strtotime($dmg['created_at'])) ?> (<?= htmlspecialchars($dmg['source_type'] ?? $dmg['discovered_stage'] ?? 'INWARD_TRANSIT') ?>)</small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?= htmlspecialchars($dmg['product_name'] ?? 'Product #' . $dmg['variant_id']) ?></div>
                                                    <small class="text-muted">
                                                        SKU: <code><?= htmlspecialchars($dmg['variant_sku'] ?? 'N/A') ?></code>
                                                        <?php if (!empty($dmg['color']) || !empty($dmg['size'])): ?>
                                                            &bull; <?= htmlspecialchars(($dmg['color'] ?? '') . ' / ' . ($dmg['size'] ?? '')) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                        <?= htmlspecialchars($dmg['defect_type'] ?? 'UNSPECIFIED') ?>
                                                    </span>
                                                </td>
                                                <td class="text-center fw-bold text-danger">
                                                    <?= number_format($dmg['quantity']) ?> pcs
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" name="dispatch_qty[<?= $dmg['id'] ?>]" 
                                                           class="form-control form-control-sm text-center dispatch-qty-input" 
                                                           value="<?= $dmg['quantity'] ?>" 
                                                           min="1" max="<?= $dmg['quantity'] ?>" 
                                                           disabled>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const masterCheckbox = document.getElementById('masterCheckbox');
    const checkboxes = document.querySelectorAll('.damage-checkbox');
    const selectAllBtn = document.getElementById('selectAllBtn');
    const deselectAllBtn = document.getElementById('deselectAllBtn');
    const selectedRecordsCount = document.getElementById('selectedRecordsCount');
    const selectedTotalQty = document.getElementById('selectedTotalQty');
    const submitBtn = document.getElementById('submitBtn');

    function updateSummary() {
        let count = 0;
        let totalQty = 0;

        checkboxes.forEach(cb => {
            const inputQty = document.querySelector(`input[name="dispatch_qty[${cb.value}]"]`);
            if (cb.checked) {
                count++;
                if (inputQty) {
                    inputQty.disabled = false;
                    totalQty += parseInt(inputQty.value) || 0;
                }
            } else {
                if (inputQty) {
                    inputQty.disabled = true;
                }
            }
        });

        selectedRecordsCount.textContent = count;
        selectedTotalQty.textContent = `${totalQty} pcs`;
        submitBtn.disabled = (count === 0 || totalQty <= 0);
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSummary);
    });

    document.querySelectorAll('.dispatch-qty-input').forEach(input => {
        input.addEventListener('input', updateSummary);
    });

    if (masterCheckbox) {
        masterCheckbox.addEventListener('change', function() {
            checkboxes.forEach(cb => { cb.checked = masterCheckbox.checked; });
            updateSummary();
        });
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            if (masterCheckbox) masterCheckbox.checked = true;
            checkboxes.forEach(cb => { cb.checked = true; });
            updateSummary();
        });
    }

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            if (masterCheckbox) masterCheckbox.checked = false;
            checkboxes.forEach(cb => { cb.checked = false; });
            updateSummary();
        });
    }
});
</script>
