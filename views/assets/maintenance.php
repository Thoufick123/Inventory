<?php
/**
 * Asset Maintenance & Service Tickets Register
 */
?>

<div class="w-100" style="max-width: 100%; overflow-x: hidden;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <a href="<?= url('asset/list') ?>" class="btn btn-outline-secondary btn-sm rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-arrow-left"></i>
                </a>
                Asset Maintenance &amp; Repair Tickets
            </h3>
            <p class="text-muted small mb-0">Record and track preventive servicing, motor overhauls, breakdowns, calibrations, and AMC contracts.</p>
        </div>
        <div>
            <button type="button" class="btn btn-warning fw-semibold d-inline-flex align-items-center gap-2 text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#newMaintenanceModal">
                <i class="bi bi-plus-circle"></i> Log Service Ticket
            </button>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('asset/maintenance') ?>" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by ticket #, equipment name, technician, vendor..." value="<?= e($_GET['search'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="service_type" class="form-select form-select-sm">
                        <option value="">All Service Types</option>
                        <option value="ROUTINE_PREVENTIVE" <?= ($_GET['service_type'] ?? '') === 'ROUTINE_PREVENTIVE' ? 'selected' : '' ?>>Routine Preventive</option>
                        <option value="BREAKDOWN_REPAIR" <?= ($_GET['service_type'] ?? '') === 'BREAKDOWN_REPAIR' ? 'selected' : '' ?>>Breakdown Repair</option>
                        <option value="CALIBRATION" <?= ($_GET['service_type'] ?? '') === 'CALIBRATION' ? 'selected' : '' ?>>Calibration &amp; Safety</option>
                        <option value="PART_REPLACEMENT" <?= ($_GET['service_type'] ?? '') === 'PART_REPLACEMENT' ? 'selected' : '' ?>>Part Replacement</option>
                        <option value="ANNUAL_AMC" <?= ($_GET['service_type'] ?? '') === 'ANNUAL_AMC' ? 'selected' : '' ?>>Annual AMC</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="COMPLETED" <?= ($_GET['status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                        <option value="IN_PROGRESS" <?= ($_GET['status'] ?? '') === 'IN_PROGRESS' ? 'selected' : '' ?>>In Progress</option>
                        <option value="PENDING" <?= ($_GET['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                    <a href="<?= url('asset/maintenance') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Maintenance Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th>Ticket #</th>
                        <th>Service Date</th>
                        <th>Equipment &amp; Location</th>
                        <th>Type &amp; Issue Summary</th>
                        <th>Serviced By / Vendor</th>
                        <th class="text-end">Cost (₹)</th>
                        <th class="text-center">Next Due</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-tools fs-1 d-block mb-2 text-secondary"></i>
                                No maintenance service tickets recorded.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold font-monospace text-dark"><?= e($l['log_number']) ?></span>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= formatDate($l['service_date']) ?></div>
                                    <?php if ((float)$l['downtime_hours'] > 0): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger font-monospace small"><?= $l['downtime_hours'] ?>h Down</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= url('asset/view/' . $l['asset_id']) ?>" class="fw-semibold text-dark text-decoration-none hover-primary d-block">
                                        <?= e($l['asset_name']) ?>
                                    </a>
                                    <span class="text-muted small"><i class="bi bi-geo-alt"></i> <?= e($l['location_display']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-warning bg-opacity-25 text-dark mb-1 small"><?= str_replace('_', ' ', $l['service_type']) ?></span>
                                    <div class="small text-dark fw-medium text-truncate" style="max-width: 280px;"><?= e($l['issue_description']) ?></div>
                                    <?php if (!empty($l['action_taken'])): ?>
                                        <div class="small text-muted text-truncate" style="max-width: 280px;"><i class="bi bi-check2 text-success"></i> <?= e($l['action_taken']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= e($l['serviced_by_vendor'] ?: 'Internal Tech Team') ?></div>
                                    <?php if (!empty($l['technician_name'])): ?>
                                        <span class="text-muted small"><?= e($l['technician_name']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    <?= formatCurrency((float)$l['service_cost']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($l['next_due_date'])): ?>
                                        <span class="badge bg-light text-primary border font-monospace small"><?= formatDate($l['next_due_date']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                        <?= e($l['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: New Maintenance Ticket with Location-Based Cascading Asset Dropdown -->
<div class="modal fade" id="newMaintenanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('asset/maintenance/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_back" value="<?= url('asset/maintenance') ?>">

                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-tools me-2"></i> Log Service &amp; Maintenance Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Step 1 & 2: Cascading Location and Product/Asset Selection -->
                    <div class="card border border-warning border-opacity-25 bg-warning bg-opacity-10 rounded-3 p-3 mb-3">
                        <div class="row g-3">
                            <!-- Location Dropdown -->
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-dark d-flex align-items-center gap-1">
                                    <i class="bi bi-geo-alt-fill text-danger"></i> 1. Select Location / Facility <span class="text-danger">*</span>
                                </label>
                                <select id="mnt_location_filter" class="form-select" onchange="filterMntAssetsByLocation()">
                                    <option value="ALL">🌐 -- All Locations &amp; Facilities --</option>
                                    <optgroup label="🏭 Production Units">
                                        <?php foreach (($unitsMap['production'] ?? []) as $pu): ?>
                                            <option value="PRODUCTION_<?= $pu['id'] ?>"><?= e($pu['name']) ?> (<?= e($pu['code'] ?: 'PU'.$pu['id']) ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="🏢 Central Warehouses">
                                        <?php foreach (($unitsMap['warehouses'] ?? []) as $wh): ?>
                                            <option value="WAREHOUSE_<?= $wh['id'] ?>"><?= e($wh['name']) ?> (<?= e($wh['code'] ?: 'WH'.$wh['id']) ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="🛍️ Retail Shops">
                                        <?php foreach (($unitsMap['shops'] ?? []) as $sh): ?>
                                            <option value="SHOP_<?= $sh['id'] ?>"><?= e($sh['name']) ?> (<?= e($sh['code'] ?: 'SH'.$sh['id']) ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="🏛️ Head Office">
                                        <option value="OFFICE_1">Head Office &amp; Central Logistics</option>
                                    </optgroup>
                                </select>
                                <div class="form-text small" id="mnt_loc_asset_count">Filter products inside facility</div>
                            </div>

                            <!-- Filtered Asset / Product Dropdown -->
                            <div class="col-md-7">
                                <label class="form-label fw-bold small text-dark d-flex align-items-center gap-1">
                                    <i class="bi bi-cpu-fill text-primary"></i> 2. Equipment / Asset Inside Location <span class="text-danger">*</span>
                                </label>
                                <select name="asset_id" id="mnt_asset_select" class="form-select" required onchange="onMntAssetSelected()">
                                    <option value="">-- Choose Asset / Product --</option>
                                </select>
                                <div class="form-text small">Lists only assets located in chosen facility</div>
                            </div>
                        </div>

                        <!-- Selected Asset Live Quick Info Preview -->
                        <div id="mnt_selected_asset_preview" class="mt-3 p-2 bg-white rounded border d-none">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-dark font-monospace" id="preview_ast_code">AST-0000</span>
                                    <strong class="text-dark small" id="preview_ast_name">-</strong>
                                    <span class="badge bg-light text-secondary border" id="preview_ast_cat">-</span>
                                </div>
                                <div>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" id="preview_ast_loc">-</span>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" id="preview_ast_status">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Service Details -->
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
                        <label class="form-label fw-semibold small">Issue Description / Scope <span class="text-danger">*</span></label>
                        <textarea name="issue_description" class="form-control" rows="2" placeholder="Issue observed or scheduled maintenance..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Action Taken / Service Summary</label>
                        <textarea name="action_taken" class="form-control" rows="2" placeholder="Summary of maintenance work performed..."></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Vendor / Service Agency</label>
                            <input type="text" name="serviced_by_vendor" class="form-control form-control-sm" placeholder="e.g. Tirupur Machinery Corp">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Technician Name</label>
                            <input type="text" name="technician_name" class="form-control form-control-sm" placeholder="e.g. S. Manikandan">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Service Cost (₹)</label>
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
                            <label class="form-label fw-semibold small">Condition Post-Service</label>
                            <select name="condition_state" class="form-select form-select-sm">
                                <option value="EXCELLENT">Excellent</option>
                                <option value="GOOD" selected>Good</option>
                                <option value="FAIR">Fair</option>
                                <option value="POOR">Poor</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Operational Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="COMPLETED" selected>Completed</option>
                                <option value="IN_PROGRESS">In Progress</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 fw-semibold"><i class="bi bi-check-circle me-1"></i> Save Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// All assets registry for live client-side cascading filter
const allAssetsData = <?= json_encode($allAssets ?? []) ?>;

/**
 * Filter Assets Dropdown by Selected Location
 */
function filterMntAssetsByLocation() {
    const locFilter = document.getElementById('mnt_location_filter').value;
    const assetSelect = document.getElementById('mnt_asset_select');
    const countLabel = document.getElementById('mnt_loc_asset_count');
    const previewBox = document.getElementById('mnt_selected_asset_preview');
    
    // Clear current options
    assetSelect.innerHTML = '<option value="">-- Choose Asset / Product --</option>';
    previewBox.classList.add('d-none');

    let filtered = allAssetsData;
    if (locFilter !== 'ALL') {
        const parts = locFilter.split('_');
        const locType = parts[0];
        const locId = parseInt(parts[1] || '1', 10);
        filtered = allAssetsData.filter(a => a.location_type === locType && parseInt(a.location_id, 10) === locId);
    }

    countLabel.innerHTML = `Showing <strong>${filtered.length}</strong> asset(s) in selected facility`;

    if (filtered.length === 0) {
        const opt = document.createElement('option');
        opt.value = "";
        opt.textContent = "No assets registered in this location";
        opt.disabled = true;
        assetSelect.appendChild(opt);
        return;
    }

    // Populate dropdown with filtered assets
    filtered.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.id;
        opt.textContent = `${a.asset_code} - ${a.name} (${a.location_display || 'Assigned'})`;
        opt.dataset.code = a.asset_code;
        opt.dataset.name = a.name;
        opt.dataset.cat = a.category_name || 'General';
        opt.dataset.loc = a.location_display || '';
        opt.dataset.status = a.status || '';
        opt.dataset.condition = a.condition_state || '';
        assetSelect.appendChild(opt);
    });
}

/**
 * Update Selected Asset Preview Info
 */
function onMntAssetSelected() {
    const assetSelect = document.getElementById('mnt_asset_select');
    const previewBox = document.getElementById('mnt_selected_asset_preview');
    const selectedOpt = assetSelect.options[assetSelect.selectedIndex];

    if (!selectedOpt || !selectedOpt.value) {
        previewBox.classList.add('d-none');
        return;
    }

    document.getElementById('preview_ast_code').textContent = selectedOpt.dataset.code || '';
    document.getElementById('preview_ast_name').textContent = selectedOpt.dataset.name || '';
    document.getElementById('preview_ast_cat').textContent = selectedOpt.dataset.cat || '';
    document.getElementById('preview_ast_loc').textContent = selectedOpt.dataset.loc || '';
    document.getElementById('preview_ast_status').textContent = (selectedOpt.dataset.status || '').replace('_', ' ');

    previewBox.classList.remove('d-none');
}

// Initial populate on load
document.addEventListener('DOMContentLoaded', () => {
    filterMntAssetsByLocation();
});
</script>
