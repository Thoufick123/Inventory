<?php
/**
 * Inter-Unit Asset Transfers Manifest
 */
?>

<div class="w-100" style="max-width: 100%; overflow-x: hidden;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <a href="<?= url('asset/list') ?>" class="btn btn-outline-secondary btn-sm rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-arrow-left"></i>
                </a>
                Inter-Unit Asset Movement Manifest
            </h3>
            <p class="text-muted small mb-0">Audit history of equipment transfers between Production plants, Warehouses, and Retail outlets.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#newTransferModal">
                <i class="bi bi-plus-circle"></i> Initiate New Transfer
            </button>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('asset/transfers') ?>" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by transfer #, asset code, equipment name, reason..." value="<?= e($_GET['search'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Transfer Statuses</option>
                        <option value="COMPLETED" <?= ($_GET['status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                        <option value="IN_TRANSIT" <?= ($_GET['status'] ?? '') === 'IN_TRANSIT' ? 'selected' : '' ?>>In Transit</option>
                        <option value="INITIATED" <?= ($_GET['status'] ?? '') === 'INITIATED' ? 'selected' : '' ?>>Initiated</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                    <a href="<?= url('asset/transfers') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Transfers Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th>Transfer #</th>
                        <th>Date &amp; Time</th>
                        <th>Equipment &amp; Asset Code</th>
                        <th>Origin Facility (From)</th>
                        <th>Destination Facility (To)</th>
                        <th>Reason / Delivery Notes</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transfers)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-arrow-left-right fs-1 d-block mb-2 text-secondary"></i>
                                No inter-unit transfers recorded.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transfers as $t): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold font-monospace text-primary"><?= e($t['transfer_number']) ?></span>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= formatDate($t['transfer_date']) ?></div>
                                    <div class="text-muted small"><?= date('h:i A', strtotime($t['transfer_date'])) ?></div>
                                </td>
                                <td>
                                    <a href="<?= url('asset/view/' . $t['asset_id']) ?>" class="fw-semibold text-dark text-decoration-none hover-primary d-block">
                                        <?= e($t['asset_name']) ?>
                                    </a>
                                    <span class="badge bg-light text-muted border font-monospace small"><?= e($t['asset_code']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-medium small"><?= e($t['from_display']) ?></div>
                                    <?php if (!empty($t['from_floor_area'])): ?>
                                        <span class="text-muted small"><?= e($t['from_floor_area']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-medium text-success small"><?= e($t['to_display']) ?></div>
                                    <?php if (!empty($t['to_floor_area'])): ?>
                                        <span class="text-muted small"><?= e($t['to_floor_area']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= e($t['reason'] ?: 'Internal Unit Transfer') ?></div>
                                    <?php if (!empty($t['challan_notes'])): ?>
                                        <div class="text-muted small fst-italic"><?= e($t['challan_notes']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                        <?= e($t['status']) ?>
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

<!-- Modal: New Transfer with Location-Based Cascading Asset Dropdown -->
<div class="modal fade" id="newTransferModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('asset/transfers/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_back" value="<?= url('asset/transfers') ?>">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-arrow-left-right me-2"></i> New Inter-Unit Asset Transfer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Step 1 & 2: Origin Location and Equipment Selection -->
                    <div class="card border border-primary border-opacity-25 bg-primary bg-opacity-10 rounded-3 p-3 mb-3">
                        <div class="row g-3">
                            <!-- Origin Location Dropdown -->
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-dark d-flex align-items-center gap-1">
                                    <i class="bi bi-geo-alt-fill text-danger"></i> 1. Origin Unit (From) <span class="text-danger">*</span>
                                </label>
                                <select id="trf_origin_location_filter" class="form-select" required onchange="filterTrfAssetsByLocation()">
                                    <option value="">-- Select a unit first --</option>
                                    <optgroup label="🏭 Production Units">
                                        <?php foreach (($unitsMap['production'] ?? []) as $pu): ?>
                                            <option value="PRODUCTION_<?= $pu['id'] ?>">PU: <?= e($pu['name']) ?> (<?= e($pu['code'] ?: 'PU'.$pu['id']) ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="🏢 Central Warehouses">
                                        <?php foreach (($unitsMap['warehouses'] ?? []) as $wh): ?>
                                            <option value="WAREHOUSE_<?= $wh['id'] ?>">WH: <?= e($wh['name']) ?> (<?= e($wh['code'] ?: 'WH'.$wh['id']) ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="🛍️ Retail Shops">
                                        <?php foreach (($unitsMap['shops'] ?? []) as $sh): ?>
                                            <option value="SHOP_<?= $sh['id'] ?>">Shop: <?= e($sh['name']) ?> (<?= e($sh['code'] ?: 'SH'.$sh['id']) ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="🏛️ Head Office">
                                        <option value="OFFICE_1">Head Office &amp; Central Logistics</option>
                                    </optgroup>
                                </select>
                                <div class="form-text small">Choose the facility that currently holds the asset.</div>
                            </div>

                            <!-- Assets currently assigned to the selected unit -->
                            <div class="col-md-7">
                                <label class="form-label fw-bold small text-dark d-flex align-items-center gap-1">
                                    <i class="bi bi-cpu-fill text-primary"></i> 2. Contents of Selected Unit <span class="text-danger">*</span>
                                </label>
                                <select name="asset_id" id="trf_asset_select" class="form-select" required disabled onchange="onTrfAssetSelected()">
                                    <option value="">Select an origin unit to load its assets</option>
                                </select>
                                <div class="form-text small" id="trf_loc_asset_count" aria-live="polite">Assets assigned to the selected unit will appear here.</div>
                            </div>
                        </div>

                        <!-- Selected Asset Quick Info Preview -->
                        <div id="trf_selected_asset_preview" class="mt-3 p-2 bg-white rounded border d-none">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-dark font-monospace" id="trf_preview_code">AST-0000</span>
                                    <strong class="text-dark small" id="trf_preview_name">-</strong>
                                    <span class="badge bg-light text-secondary border" id="trf_preview_cat">-</span>
                                </div>
                                <div>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" id="trf_preview_loc">-</span>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" id="trf_preview_status">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Destination Location -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-dark d-flex align-items-center gap-1">
                            <i class="bi bi-box-arrow-in-right text-success"></i> 3. Destination Target Unit (To) <span class="text-danger">*</span>
                        </label>
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
                        <input type="text" name="to_floor_area" class="form-control" placeholder="e.g. Line 2 Station 4, Billing Counter 1">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Transfer Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Seasonal promotion, production spike, maintenance reserve">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Challan / Vehicle Details</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Vehicle TN-38-..., driver name..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Submit Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// All assets registry for client-side live cascading filter
const allTrfAssetsData = <?= json_encode($allAssets ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

/**
 * Filter Transfer Assets by Selected Origin Location
 */
function filterTrfAssetsByLocation() {
    const locFilter = document.getElementById('trf_origin_location_filter').value;
    const assetSelect = document.getElementById('trf_asset_select');
    const countLabel = document.getElementById('trf_loc_asset_count');
    const previewBox = document.getElementById('trf_selected_asset_preview');
    
    // Reset the contents field whenever the origin changes so stale assets cannot be submitted.
    assetSelect.innerHTML = '';
    assetSelect.disabled = !locFilter;
    previewBox.classList.add('d-none');

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = locFilter ? '-- Choose an asset in this unit --' : 'Select an origin unit to load its assets';
    assetSelect.appendChild(placeholder);

    if (!locFilter) {
        countLabel.textContent = 'Assets assigned to the selected unit will appear here.';
        return;
    }

    const separatorIndex = locFilter.lastIndexOf('_');
    const locType = locFilter.slice(0, separatorIndex);
    const locId = Number(locFilter.slice(separatorIndex + 1));
    const filtered = allTrfAssetsData
        .filter(asset => asset.location_type === locType && Number(asset.location_id) === locId)
        .sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));

    countLabel.textContent = `${filtered.length} asset${filtered.length === 1 ? '' : 's'} currently assigned to this unit.`;

    if (filtered.length === 0) {
        placeholder.textContent = 'No assets currently assigned to this unit';
        return;
    }

    assetSelect.disabled = false;

    // Populate dropdown with filtered assets
    filtered.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.id;
        opt.textContent = `${a.asset_code} - ${a.name}${a.floor_area ? ` (${a.floor_area})` : ''}`;
        opt.dataset.code = a.asset_code;
        opt.dataset.name = a.name;
        opt.dataset.cat = a.category_name || 'General';
        opt.dataset.loc = a.location_display || '';
        opt.dataset.status = a.status || '';
        assetSelect.appendChild(opt);
    });
}

/**
 * Update Selected Asset Preview Info
 */
function onTrfAssetSelected() {
    const assetSelect = document.getElementById('trf_asset_select');
    const previewBox = document.getElementById('trf_selected_asset_preview');
    const selectedOpt = assetSelect.options[assetSelect.selectedIndex];

    if (!selectedOpt || !selectedOpt.value) {
        previewBox.classList.add('d-none');
        return;
    }

    document.getElementById('trf_preview_code').textContent = selectedOpt.dataset.code || '';
    document.getElementById('trf_preview_name').textContent = selectedOpt.dataset.name || '';
    document.getElementById('trf_preview_cat').textContent = selectedOpt.dataset.cat || '';
    document.getElementById('trf_preview_loc').textContent = selectedOpt.dataset.loc || '';
    document.getElementById('trf_preview_status').textContent = (selectedOpt.dataset.status || '').replace('_', ' ');

    previewBox.classList.remove('d-none');
}

// Keep the dependent contents field empty until the user chooses an origin unit.
</script>
