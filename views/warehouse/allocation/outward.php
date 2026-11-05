<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="h3 mb-1 text-gray-800"><i class="bi bi-truck me-2 text-primary"></i>Warehouse Outward & Dispatch</h2>
        <span class="text-muted small">Dispatch finished allocation boxes to retail shops and sales channels with vehicle tracking</span>
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('allocation', 'view')): ?>
        <a href="<?= url('warehouse/allocation') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-list-ul me-1"></i> View Requisitions
        </a>
        <?php endif; ?>
        <?php if (!empty($approvedAllocations) && hasPermission('allocation_outward', 'create')): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAllocationOutwardModal">
                <i class="bi bi-plus-circle me-1"></i> New Dispatch Outward
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Stat Notice if Approved Allocations Ready -->
<?php if (!empty($approvedAllocations)): ?>
    <div class="alert alert-info d-flex align-items-center justify-content-between mb-4 shadow-sm border-0 rounded-3">
        <div>
            <i class="bi bi-info-circle-fill me-2 fs-5 text-info"></i>
            <strong><?= count($approvedAllocations) ?> Approved / Packed Allocation(s)</strong> ready for dispatch to shops or channels.
        </div>
        <?php if (hasPermission('allocation_outward', 'create')): ?>
        <button type="button" class="btn btn-sm btn-info text-white fw-bold" data-bs-toggle="modal" data-bs-target="#newAllocationOutwardModal">
            Dispatch Outward Now &rarr;
        </button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Filter & Search Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('warehouse/allocation/outward') ?>" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Search outward #, allocation #, vehicle, or shop..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-md-4">
                <select name="warehouse_id" class="form-select" <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                    <?php if (!function_exists('currentWarehouseId') || currentWarehouseId() === null && count($warehouses) > 1): ?>
                        <option value="">-- All Source Warehouses --</option>
                    <?php endif; ?>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?= $wh['id'] ?>" <?= (($warehouseId ?? 0) == $wh['id'] || (function_exists('currentWarehouseId') && currentWarehouseId() == $wh['id'])) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wh['name']) ?><?= !empty($wh['code']) ? ' (' . htmlspecialchars($wh['code']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1): ?>
                    <input type="hidden" name="warehouse_id" value="<?= htmlspecialchars((string)($warehouseId ?: ($warehouses[0]['id'] ?? ''))) ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter me-1"></i> Filter Outwards</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- READY FOR DISPATCH: APPROVED & PACKED ALLOCATIONS TABLE -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4" style="border-left: 4px solid #0d6efd !important;">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-box-seam-fill me-2 text-primary"></i>Ready for Dispatch (Approved & Packed Allocations)
            </h5>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">
                <?= count($approvedAllocations ?? []) ?> Batches Ready
            </span>
        </div>
        <?php if (!empty($approvedAllocations) && hasPermission('allocation_outward', 'create')): ?>
            <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newAllocationOutwardModal">
                <i class="bi bi-plus-circle me-1"></i> Dispatch Batch
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 18%;">Allocation #</th>
                        <th style="width: 18%;">Source Warehouse</th>
                        <th style="width: 20%;">Destination</th>
                        <th style="width: 14%;">Total Quantity</th>
                        <th style="width: 12%;">Status</th>
                        <th style="width: 18%;" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($approvedAllocations)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
                                <span>No pending approved allocations waiting for dispatch. All batches are dispatched!</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($approvedAllocations as $ab): 
                            $dest = !empty($ab['shop_name']) ? $ab['shop_name'] : (!empty($ab['channel_name']) ? $ab['channel_name'] : (!empty($ab['dest_warehouse_name']) ? $ab['dest_warehouse_name'] : ($ab['destination_type'] ?? 'Destination')));
                            
                            $statusBg = 'bg-secondary';
                            if ($ab['status'] === 'APPROVED') $statusBg = 'bg-success';
                            elseif ($ab['status'] === 'PACKING') $statusBg = 'bg-info text-dark';
                            elseif ($ab['status'] === 'PICKING') $statusBg = 'bg-warning text-dark';
                            elseif ($ab['status'] === 'REQUESTED') $statusBg = 'bg-primary';
                        ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark font-monospace"><?= htmlspecialchars($ab['allocation_number']) ?></div>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y, h:i A', strtotime($ab['created_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($ab['warehouse_name']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary text-white"><?= htmlspecialchars($dest) ?></span>
                                    <small class="text-muted d-block mt-0.5"><?= htmlspecialchars($ab['destination_type']) ?></small>
                                </td>
                                <td>
                                    <span class="fw-bold text-success fs-6"><?= number_format($ab['total_qty']) ?> pcs</span>
                                    <?php if (!empty($ab['total_items'])): ?>
                                        <small class="text-muted d-block">(<?= $ab['total_items'] ?> SKU<?= $ab['total_items'] > 1 ? 's' : '' ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $statusBg ?> px-2.5 py-1.5"><?= htmlspecialchars($ab['status']) ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <?php if (hasPermission('allocation_outward', 'create')): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-primary fw-bold dispatch-batch-btn shadow-sm" 
                                                data-allocation-id="<?= $ab['id'] ?>" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#newAllocationOutwardModal" 
                                                title="Dispatch this allocation outward">
                                            <i class="bi bi-truck me-1"></i> Dispatch Outward
                                        </button>
                                        <?php endif; ?>
                                        <a href="<?= url('warehouse/allocation/view/' . $ab['id']) ?>" class="btn btn-sm btn-outline-secondary" title="View Allocation Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Outward History Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="card-title mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Outward Dispatch History</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Outward #</th>
                        <th>Allocation #</th>
                        <th>Source Warehouse</th>
                        <th>Destination</th>
                        <th>Total Pieces</th>
                        <th>Vehicle / Tracking</th>
                        <th>Dispatched At</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($outwards)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> No warehouse outward dispatches recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($outwards as $o): 
                            $destName = !empty($o['shop_name']) ? $o['shop_name'] : (!empty($o['channel_name']) ? $o['channel_name'] : ($o['alloc_dest_type'] ?? 'Destination'));
                        ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($o['outward_number']) ?></div>
                                    <small class="text-muted">By <?= htmlspecialchars($o['created_by_name'] ?? 'Staff') ?></small>
                                </td>
                                <td><code class="text-primary fw-bold"><?= htmlspecialchars($o['allocation_number']) ?></code></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($o['warehouse_name']) ?></span></td>
                                <td><span class="badge bg-primary text-white"><?= htmlspecialchars($destName) ?></span></td>
                                <td class="text-success fw-bold"><?= number_format($o['total_quantity']) ?> pcs (<?= $o['total_boxes'] ?> box)</td>
                                <td><code><?= htmlspecialchars($o['vehicle_number'] ?: 'Handover') ?></code></td>
                                <td><?= date('d M Y, h:i A', strtotime($o['dispatch_date'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= ($o['status'] ?? '') === 'DELIVERED' ? 'success' : 'primary' ?>">
                                        <?= htmlspecialchars($o['status'] ?? 'DISPATCHED') ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <?php if (hasPermission('allocation_outward', 'gatepass')): ?>
                                            <a href="<?= url('warehouse/allocation/gatepass/' . $o['allocation_id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-qr-code-scan me-1"></i> QR Sticker / Pass
                                            </a>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for New Allocation Outward Dispatch -->
<?php if (!empty($approvedAllocations)): ?>
<div class="modal fade" id="newAllocationOutwardModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('warehouse/allocation/outward/store') ?>" id="outwardDispatchForm" novalidate>
                <?= CSRF::field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-truck me-2"></i>Dispatch Warehouse Allocation Outward</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Approved Allocation Batch <span class="text-danger">*</span></label>
                        <select name="allocation_id" id="outward_allocation_id" class="form-select" required>
                            <option value="">-- Select Approved Allocation Batch --</option>
                            <?php foreach ($approvedAllocations as $ab): 
                                $dest = !empty($ab['shop_name']) ? $ab['shop_name'] : (!empty($ab['channel_name']) ? $ab['channel_name'] : (!empty($ab['dest_warehouse_name']) ? $ab['dest_warehouse_name'] : ($ab['destination_type'] ?? 'Destination')));
                            ?>
                                <option value="<?= $ab['id'] ?>">
                                    <?= htmlspecialchars($ab['allocation_number']) ?> — <?= htmlspecialchars($ab['warehouse_name']) ?> &rarr; <?= htmlspecialchars($dest) ?> (<?= number_format($ab['total_qty']) ?> pcs)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select an approved allocation batch.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Vehicle / Transport Number <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle_number" id="outward_vehicle_number" class="form-control text-uppercase" placeholder="e.g. TN-38-AB-1234 or Courier Ref" required>
                            <div class="invalid-feedback">Vehicle / Transport Number is required.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Driver Name <span class="text-danger">*</span></label>
                            <input type="text" name="driver_name" id="outward_driver_name" class="form-control" placeholder="e.g. Ramesh" required>
                            <div class="invalid-feedback">Driver Name is required.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Driver Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="driver_contact" id="outward_driver_contact" class="form-control" placeholder="e.g. 9876543210" maxlength="10" required>
                            <div class="invalid-feedback">Please enter a valid 10-digit mobile number.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Transporter / Logistics Name <span class="text-danger">*</span></label>
                            <input type="text" name="transporter_name" id="outward_transporter_name" class="form-control" placeholder="e.g. VRL Logistics / BlueDart" required>
                            <div class="invalid-feedback">Transporter / Logistics Name is required.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">LR / Docket / Tracking Number <span class="text-muted small fw-normal">(Optional)</span></label>
                            <input type="text" name="lr_number" id="outward_lr_number" class="form-control" placeholder="e.g. LR-987654">
                            <div class="form-text text-muted">Optional tracking reference if available</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Total Box / Carton Count <span class="text-danger">*</span></label>
                            <input type="number" name="total_boxes" id="outward_total_boxes" class="form-control" value="1" min="1" required>
                            <div class="invalid-feedback">Total Box / Carton Count must be at least 1.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Dispatch Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="dispatch_date" id="outward_dispatch_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                            <div class="invalid-feedback">Dispatch Date & Time is required.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Delivery / Dispatch Notes <span class="text-muted small fw-normal">(Optional)</span></label>
                            <textarea name="notes" id="outward_notes" class="form-control" rows="1" placeholder="e.g. Handle with care..."></textarea>
                            <div class="form-text text-muted">Optional instructions or delivery remarks</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold" id="submitOutwardBtn"><i class="bi bi-check2 me-1"></i> Confirm & Dispatch Outward</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('outwardDispatchForm');
    if (!form) return;

    const allocationSelect = document.getElementById('outward_allocation_id');
    const vehicleInput = document.getElementById('outward_vehicle_number');
    const driverNameInput = document.getElementById('outward_driver_name');
    const driverContactInput = document.getElementById('outward_driver_contact');
    const transporterInput = document.getElementById('outward_transporter_name');
    const totalBoxesInput = document.getElementById('outward_total_boxes');
    const dispatchDateInput = document.getElementById('outward_dispatch_date');

    function setFieldStatus(input, isValid, errorMsg = '') {
        if (!input) return;
        const parent = input.closest('.col-md-6') || input.parentElement;
        const feedback = parent ? parent.querySelector('.invalid-feedback') : null;
        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            if (feedback && errorMsg) {
                feedback.textContent = errorMsg;
            }
        }
    }

    function validateAllocation() {
        if (!allocationSelect) return true;
        const isValid = allocationSelect.value.trim() !== '';
        setFieldStatus(allocationSelect, isValid, 'Please select an approved allocation batch.');
        return isValid;
    }

    function validateVehicle() {
        if (!vehicleInput) return true;
        const isValid = vehicleInput.value.trim().length >= 2;
        setFieldStatus(vehicleInput, isValid, 'Vehicle / Transport Number is required.');
        return isValid;
    }

    function validateDriverName() {
        if (!driverNameInput) return true;
        const isValid = driverNameInput.value.trim().length >= 2;
        setFieldStatus(driverNameInput, isValid, 'Driver Name is required.');
        return isValid;
    }

    function validateDriverContact() {
        if (!driverContactInput) return true;
        const clean = driverContactInput.value.replace(/[^0-9]/g, '');
        driverContactInput.value = clean.slice(0, 10);
        const isValid = clean.length === 10;
        setFieldStatus(driverContactInput, isValid, 'Please enter a valid 10-digit mobile number.');
        return isValid;
    }

    function validateTransporter() {
        if (!transporterInput) return true;
        const isValid = transporterInput.value.trim().length >= 2;
        setFieldStatus(transporterInput, isValid, 'Transporter / Logistics Name is required.');
        return isValid;
    }

    function validateTotalBoxes() {
        if (!totalBoxesInput) return true;
        const val = parseInt(totalBoxesInput.value, 10);
        const isValid = !isNaN(val) && val >= 1;
        setFieldStatus(totalBoxesInput, isValid, 'Total Box / Carton Count must be at least 1.');
        return isValid;
    }

    function validateDispatchDate() {
        if (!dispatchDateInput) return true;
        const isValid = dispatchDateInput.value.trim() !== '';
        setFieldStatus(dispatchDateInput, isValid, 'Dispatch Date & Time is required.');
        return isValid;
    }

    // Real-time inline event listeners
    if (allocationSelect) {
        allocationSelect.addEventListener('change', validateAllocation);
    }
    if (vehicleInput) {
        vehicleInput.addEventListener('input', validateVehicle);
        vehicleInput.addEventListener('blur', validateVehicle);
    }
    if (driverNameInput) {
        driverNameInput.addEventListener('input', validateDriverName);
        driverNameInput.addEventListener('blur', validateDriverName);
    }
    if (driverContactInput) {
        driverContactInput.addEventListener('input', validateDriverContact);
        driverContactInput.addEventListener('blur', validateDriverContact);
    }
    if (transporterInput) {
        transporterInput.addEventListener('input', validateTransporter);
        transporterInput.addEventListener('blur', validateTransporter);
    }
    if (totalBoxesInput) {
        totalBoxesInput.addEventListener('input', validateTotalBoxes);
        totalBoxesInput.addEventListener('blur', validateTotalBoxes);
    }
    if (dispatchDateInput) {
        dispatchDateInput.addEventListener('input', validateDispatchDate);
        dispatchDateInput.addEventListener('change', validateDispatchDate);
    }

    // Preselect allocation batch when clicking Dispatch Outward from table row
    document.querySelectorAll('.dispatch-batch-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const allocId = this.getAttribute('data-allocation-id');
            if (allocId && allocationSelect) {
                allocationSelect.value = allocId;
                validateAllocation();
            }
        });
    });

    // Form submit validation
    form.addEventListener('submit', function(e) {
        const isAllocValid = validateAllocation();
        const isVehValid = validateVehicle();
        const isDriverValid = validateDriverName();
        const isContactValid = validateDriverContact();
        const isTransValid = validateTransporter();
        const isBoxesValid = validateTotalBoxes();
        const isDateValid = validateDispatchDate();

        const allValid = isAllocValid && isVehValid && isDriverValid && isContactValid && isTransValid && isBoxesValid && isDateValid;

        if (!allValid) {
            e.preventDefault();
            e.stopPropagation();

            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return false;
        }

        const submitBtn = document.getElementById('submitOutwardBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Dispatching...';
        }
    });
});
</script>
<?php endif; ?>
