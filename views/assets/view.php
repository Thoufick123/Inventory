<?php
/**
 * Asset 360 Profile View
 */
$statusBadge = match($asset['status']) {
    'IN_USE' => 'bg-success text-white',
    'IN_MAINTENANCE' => 'bg-warning text-dark',
    'IDLE' => 'bg-secondary text-white',
    'DAMAGED' => 'bg-danger text-white',
    'DISPOSED', 'WRITTEN_OFF' => 'bg-dark text-white',
    default => 'bg-light text-dark'
};
$condBadge = match($asset['condition_state']) {
    'EXCELLENT' => 'text-success bg-success bg-opacity-10 border border-success border-opacity-25',
    'GOOD' => 'text-primary bg-primary bg-opacity-10 border border-primary border-opacity-25',
    'FAIR' => 'text-warning bg-warning bg-opacity-10 border border-warning border-opacity-25',
    'POOR', 'CRITICAL' => 'text-danger bg-danger bg-opacity-10 border border-danger border-opacity-25',
    default => 'text-secondary bg-light'
};
?>

<div class="w-100" style="max-width: 100%; overflow-x: hidden;">
    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 fs-2">
                        <i class="bi <?= e($asset['category_icon']) ?>"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h3 class="fw-bold mb-0 text-dark"><?= e($asset['name']) ?></h3>
                            <span class="badge font-monospace bg-dark text-white px-2 py-1"><?= e($asset['asset_code']) ?></span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 text-muted small">
                            <span><i class="bi bi-tag text-primary"></i> <?= e($asset['category_name']) ?></span>
                            <span>•</span>
                            <span><?= e($asset['location_display']) ?></span>
                            <?php if (!empty($asset['floor_area'])): ?>
                                <span>(<?= e($asset['floor_area']) ?>)</span>
                            <?php endif; ?>
                            <span>•</span>
                            <span class="badge rounded-pill <?= $statusBadge ?> px-2"><?= str_replace('_', ' ', $asset['status']) ?></span>
                            <span class="badge rounded-pill <?= $condBadge ?> px-2"><?= e($asset['condition_state']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= url('asset/label/' . $asset['id']) ?>" target="_blank" class="btn btn-outline-success d-inline-flex align-items-center gap-2">
                        <i class="bi bi-qr-code"></i> Print QR Label
                    </a>
                    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2" onclick="openTransferModal()">
                        <i class="bi bi-arrow-left-right"></i> Transfer Unit
                    </button>
                    <button type="button" class="btn btn-outline-warning d-inline-flex align-items-center gap-2" onclick="openMaintenanceModal()">
                        <i class="bi bi-tools"></i> Log Service
                    </button>
                    <a href="<?= url('asset/edit/' . $asset['id']) ?>" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <i class="bi bi-pencil"></i> Edit Details
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Specs, Location & Commercials -->
        <div class="col-lg-7">
            <!-- Card 1: Technical Details -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-cpu text-primary"></i> Technical Specifications &amp; Identifiers
                    </h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <span class="text-muted small d-block">Brand / Make</span>
                            <span class="fw-semibold text-dark"><?= e($asset['brand'] ?: 'Not Specified') ?></span>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-muted small d-block">Model Number</span>
                            <span class="fw-semibold text-dark"><?= e($asset['model_number'] ?: 'Not Specified') ?></span>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-muted small d-block">Serial Number (SN)</span>
                            <span class="fw-semibold font-monospace text-dark"><?= e($asset['serial_number'] ?: 'N/A') ?></span>
                        </div>

                        <div class="col-sm-6">
                            <span class="text-muted small d-block">QR &amp; Barcode Tag Payload</span>
                            <span class="badge bg-light text-dark border font-monospace"><?= e($asset['barcode_qr']) ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Assigned Custodian / Operator</span>
                            <span class="fw-semibold text-dark"><?= e($asset['custodian_name'] ?: 'General Facility Pool') ?></span>
                        </div>

                        <?php if (!empty($asset['specifications'])): ?>
                            <div class="col-12">
                                <span class="text-muted small d-block">Technical Characteristics &amp; Notes</span>
                                <div class="p-3 bg-light rounded-3 small text-dark mt-1">
                                    <?= nl2br(e($asset['specifications'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Card 2: Commercial & Valuation -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-cash-coin text-primary"></i> Financial Valuation &amp; Depreciation
                    </h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-4 border">
                                <span class="text-muted small d-block text-uppercase fw-semibold">Current Net Book Value</span>
                                <h3 class="fw-bold text-primary mb-0 mt-1"><?= formatCurrency((float)$asset['current_book_value']) ?></h3>
                                <span class="text-muted small">Depreciation: <?= $asset['depreciation_rate_annual'] ?>% per year</span>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-4 border">
                                <span class="text-muted small d-block text-uppercase fw-semibold">Original Purchase Cost</span>
                                <h3 class="fw-bold text-dark mb-0 mt-1"><?= formatCurrency((float)$asset['purchase_cost']) ?></h3>
                                <span class="text-muted small">Purchased: <?= formatDate($asset['purchase_date']) ?></span>
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <span class="text-muted small d-block">Vendor / Supplier</span>
                            <span class="fw-semibold text-dark"><?= e($asset['vendor_name'] ?: 'N/A') ?></span>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-muted small d-block">Invoice Number</span>
                            <span class="fw-semibold text-dark"><?= e($asset['invoice_number'] ?: 'N/A') ?></span>
                            <?php if (!empty($asset['invoice_file_url'])): ?>
                                <a class="small d-block mt-1" href="<?= e($asset['invoice_file_url']) ?>" target="_blank" rel="noopener">View invoice / bill</a>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-muted small d-block">Warranty Expiry</span>
                            <span class="fw-semibold <?= (!empty($asset['warranty_expiry']) && strtotime($asset['warranty_expiry']) < time()) ? 'text-danger' : 'text-success' ?>">
                                <?= formatDate($asset['warranty_expiry']) ?>
                            </span>
                        </div>

                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Useful Life Cycle</span>
                            <span class="fw-semibold text-dark"><?= (int)$asset['useful_life_years'] ?> Years</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Salvage / Scrap Value</span>
                            <span class="fw-semibold text-dark"><?= formatCurrency((float)$asset['salvage_value']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Inter-Unit Movement History -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left-right text-primary"></i> Inter-Unit Transfer History (<?= count($asset['transfers'] ?? []) ?>)
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="openTransferModal()">
                        <i class="bi bi-plus-circle"></i> New Transfer
                    </button>
                </div>
                <div class="card-body p-4 pt-0">
                    <?php if (empty($asset['transfers'])): ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-clock-history fs-3 d-block mb-1 text-secondary"></i>
                            Asset has remained at its original registered facility.
                        </div>
                    <?php else: ?>
                        <div class="timeline position-relative ps-4 border-start">
                            <?php foreach ($asset['transfers'] as $trf): ?>
                                <div class="mb-4 position-relative">
                                    <span class="position-absolute top-0 start-0 translate-middle p-1 bg-primary rounded-circle" style="margin-left: -24px; width: 12px; height: 12px;"></span>
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="fw-bold text-dark"><?= e($trf['from_display']) ?> <i class="bi bi-arrow-right text-primary mx-1"></i> <?= e($trf['to_display']) ?></span>
                                        <span class="text-muted small"><?= formatDateTime($trf['transfer_date']) ?></span>
                                    </div>
                                    <div class="text-muted small mb-1">Manifest: <span class="font-monospace fw-semibold"><?= e($trf['transfer_number']) ?></span></div>
                                    <?php if (!empty($trf['reason'])): ?>
                                        <div class="small text-secondary"><strong>Reason:</strong> <?= e($trf['reason']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($trf['challan_notes'])): ?>
                                        <div class="small text-muted fst-italic">Note: <?= e($trf['challan_notes']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: QR Tag Canvas & Service Tickets -->
        <div class="col-lg-5">
            <?php if (!empty($asset['photo_url'])): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-image text-primary me-2"></i> Asset / Product Image
                        </h5>
                    </div>
                    <div class="card-body p-3 text-center">
                        <img src="<?= e($asset['photo_url']) ?>" alt="<?= e($asset['name']) ?>" class="img-fluid rounded-3" style="max-height: 320px;">
                    </div>
                </div>
            <?php endif; ?>

            <!-- QR Tag Sticker Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 text-center">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-qr-code text-primary me-2"></i> Thermal QR Tag Sticker
                    </h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="p-3 bg-light rounded-4 border d-inline-block mb-3 shadow-xs">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($asset['barcode_qr']) ?>" 
                             alt="QR Code" class="img-fluid rounded" style="width: 160px; height: 160px;">
                        <div class="fw-bold font-monospace text-dark mt-2 fs-5"><?= e($asset['asset_code']) ?></div>
                        <div class="text-muted small text-truncate" style="max-width: 200px;"><?= e($asset['name']) ?></div>
                    </div>
                    <div>
                        <a href="<?= url('asset/label/' . $asset['id']) ?>" target="_blank" class="btn btn-outline-dark btn-sm w-100 mb-2">
                            <i class="bi bi-printer me-1"></i> Print 50x25mm / 80mm Thermal Sticky Label
                        </a>
                    </div>
                </div>
            </div>

            <!-- Maintenance & Repair Service History -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-wrench-adjustable text-warning"></i> Service Logs (<?= count($asset['maintenance_logs'] ?? []) ?>)
                    </h5>
                    <button type="button" class="btn btn-sm btn-warning fw-semibold" onclick="openMaintenanceModal()">
                        <i class="bi bi-plus-circle"></i> Log Service
                    </button>
                </div>
                <div class="card-body p-4 pt-0">
                    <?php if (empty($asset['maintenance_logs'])): ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-shield-check fs-3 d-block mb-1 text-success"></i>
                            No breakdown or service tickets logged yet. Equipment in normal operation.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($asset['maintenance_logs'] as $log): ?>
                                <div class="list-group-item px-0 py-3">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            <span class="badge bg-warning bg-opacity-25 text-dark me-1"><?= str_replace('_', ' ', $log['service_type']) ?></span>
                                            <span class="font-monospace small text-muted"><?= e($log['log_number']) ?></span>
                                        </div>
                                        <span class="fw-bold text-dark"><?= formatCurrency((float)$log['service_cost']) ?></span>
                                    </div>
                                    <div class="fw-semibold text-dark small mb-1"><?= e($log['issue_description']) ?></div>
                                    <?php if (!empty($log['action_taken'])): ?>
                                        <div class="text-muted small mb-1"><i class="bi bi-check2 text-success"></i> <?= e($log['action_taken']) ?></div>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center text-muted small mt-2 pt-1 border-top">
                                        <span>Date: <?= formatDate($log['service_date']) ?></span>
                                        <?php if (!empty($log['next_due_date'])): ?>
                                            <span class="badge bg-light text-primary border">Next: <?= formatDate($log['next_due_date']) ?></span>
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
</div>

<!-- Modal: Inter-Unit Asset Transfer -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('asset/transfers/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="asset_id" value="<?= $asset['id'] ?>">
                <input type="hidden" name="redirect_back" value="<?= e($_SERVER['REQUEST_URI'] ?? url('asset/view/' . $asset['id'])) ?>">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-arrow-left-right me-2"></i> Inter-Unit Asset Transfer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="text-muted small">Asset:</div>
                        <div class="fw-bold text-dark"><?= e($asset['asset_code']) ?> - <?= e($asset['name']) ?></div>
                        <div class="text-muted small">Current Location: <span class="fw-semibold text-primary"><?= e($asset['location_display']) ?></span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Destination Target Unit <span class="text-danger">*</span></label>
                        <select name="target_unit" class="form-select" required>
                            <optgroup label="🏭 Production Units">
                                <?php foreach (($unitsMap['production'] ?? []) as $pu): ?>
                                    <option value="PU_<?= $pu['id'] ?>">PU: <?= e($pu['name']) ?> (<?= e($pu['code']) ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="🏢 Central Warehouses">
                                <?php foreach (($unitsMap['warehouses'] ?? []) as $wh): ?>
                                    <option value="WH_<?= $wh['id'] ?>">Warehouse: <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="🛍️ Retail Shops">
                                <?php foreach (($unitsMap['shops'] ?? []) as $sh): ?>
                                    <option value="SH_<?= $sh['id'] ?>">Shop: <?= e($sh['name']) ?> (<?= e($sh['code']) ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="🏛️ Head Office">
                                <option value="OFFICE_1">Head Office &amp; Central Logistics</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Specific Floor / Bay / Counter</label>
                        <input type="text" name="to_floor_area" class="form-control" placeholder="e.g. Line 2 Station 4, Billing Desk 1, Bay A">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Transfer Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Operational demand re-allocation">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Transfer Notes / Gate Pass Ref</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Vehicle number, driver name, instructions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Confirm Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Service & Maintenance Logging -->
<div class="modal fade" id="maintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('asset/maintenance/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="asset_id" value="<?= $asset['id'] ?>">
                <input type="hidden" name="redirect_back" value="<?= e($_SERVER['REQUEST_URI'] ?? url('asset/view/' . $asset['id'])) ?>">

                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-tools me-2"></i> Log Service / Repair Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="text-muted small">Asset:</div>
                        <div class="fw-bold text-dark"><?= e($asset['asset_code']) ?> - <?= e($asset['name']) ?></div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Service Type <span class="text-danger">*</span></label>
                            <select name="service_type" class="form-select form-select-sm" required>
                                <option value="ROUTINE_PREVENTIVE">Routine Preventive Maintenance</option>
                                <option value="BREAKDOWN_REPAIR">Breakdown Repair</option>
                                <option value="CALIBRATION">Calibration &amp; Safety Testing</option>
                                <option value="PART_REPLACEMENT">Spare Part Replacement</option>
                                <option value="ANNUAL_AMC">Annual AMC Servicing</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Service Date <span class="text-danger">*</span></label>
                            <input type="date" name="service_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Issue / Maintenance Scope</label>
                        <textarea name="issue_description" class="form-control" rows="2" placeholder="Describe the service required..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Action Taken / Service Summary</label>
                        <textarea name="action_taken" class="form-control" rows="2" placeholder="e.g. Oil change, motor belt replacement, thermal head cleaned..."></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Vendor / Service Agency</label>
                            <input type="text" name="serviced_by_vendor" class="form-control form-control-sm" placeholder="e.g. Tirupur Machinery Corp">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Technician Name &amp; Phone</label>
                            <input type="text" name="technician_name" class="form-control form-control-sm" placeholder="e.g. R. Kumar (9842100000)">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Total Cost (₹)</label>
                            <input type="number" step="0.01" name="service_cost" class="form-control form-control-sm" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Downtime (Hours)</label>
                            <input type="number" step="0.5" name="downtime_hours" class="form-control form-control-sm" value="0.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Next Due Date</label>
                            <input type="date" name="next_due_date" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Asset Condition Post-Service</label>
                            <select name="condition_state" class="form-select form-select-sm">
                                <option value="EXCELLENT">Excellent</option>
                                <option value="GOOD" selected>Good</option>
                                <option value="FAIR">Fair</option>
                                <option value="POOR">Poor</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Operational Status</label>
                            <select name="asset_status" class="form-select form-select-sm">
                                <option value="IN_USE" selected>In Use (Ready)</option>
                                <option value="IN_MAINTENANCE">Still Under Repair</option>
                                <option value="IDLE">Idle / Standby</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 fw-semibold"><i class="bi bi-check-circle me-1"></i> Save Service Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openTransferModal() {
    new bootstrap.Modal(document.getElementById('transferModal')).show();
}

function openMaintenanceModal() {
    new bootstrap.Modal(document.getElementById('maintenanceModal')).show();
}
</script>
