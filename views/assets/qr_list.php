<?php
/**
 * Asset QR register and label actions.
 */
$selectedUnit = $currentUnit ?? 'ALL';
$selectedCategory = (int)($filters['category_id'] ?? 0);
$selectedStatus = $filters['status'] ?? '';
$selectedSearch = $filters['search'] ?? '';
$printQuery = http_build_query([
    'mode' => 'print',
    'unit' => $selectedUnit,
    'category_id' => $selectedCategory ?: '',
    'status' => $selectedStatus,
    'search' => $selectedSearch,
    'columns' => 3,
]);
$bulkPrintUrl = url('asset/bulk-labels') . '?' . $printQuery;
?>
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-qr-code text-primary me-2"></i>Asset QR Register</h2>
            <div class="text-muted">Browse existing asset QR codes and print labels as needed.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e($bulkPrintUrl) ?>" class="btn btn-outline-primary">
                <i class="bi bi-printer me-1"></i> Bulk Print Sheet
            </a>
            <?php if (isSuperAdmin()): ?>
                <a href="<?= url('asset/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Create New Asset / QR
                </a>
            <?php endif; ?>
        </div>
    </div>

    <form method="GET" action="<?= url('asset/bulk-labels') ?>" class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Facility / Unit</label>
                <select name="unit" class="form-select">
                    <option value="ALL" <?= $selectedUnit === 'ALL' ? 'selected' : '' ?>>All Facilities</option>
                    <optgroup label="Warehouses">
                        <option value="WAREHOUSE_ALL" <?= $selectedUnit === 'WAREHOUSE_ALL' ? 'selected' : '' ?>>All Warehouses</option>
                        <?php foreach (($unitsMap['warehouses'] ?? []) as $unit): ?>
                            <option value="WH_<?= (int)$unit['id'] ?>" <?= $selectedUnit === 'WH_' . $unit['id'] ? 'selected' : '' ?>>
                                <?= e($unit['name']) ?> (<?= e($unit['code'] ?: 'WH' . $unit['id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Production Units">
                        <option value="PRODUCTION_ALL" <?= $selectedUnit === 'PRODUCTION_ALL' ? 'selected' : '' ?>>All Production Units</option>
                        <?php foreach (($unitsMap['production'] ?? []) as $unit): ?>
                            <option value="PU_<?= (int)$unit['id'] ?>" <?= $selectedUnit === 'PU_' . $unit['id'] ? 'selected' : '' ?>>
                                <?= e($unit['name']) ?> (<?= e($unit['code'] ?: 'PU' . $unit['id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Retail Shops">
                        <option value="SHOP_ALL" <?= $selectedUnit === 'SHOP_ALL' ? 'selected' : '' ?>>All Shops</option>
                        <?php foreach (($unitsMap['shops'] ?? []) as $unit): ?>
                            <option value="SH_<?= (int)$unit['id'] ?>" <?= $selectedUnit === 'SH_' . $unit['id'] ? 'selected' : '' ?>>
                                <?= e($unit['name']) ?> (<?= e($unit['code'] ?: 'SH' . $unit['id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Head Office">
                        <option value="OFFICE" <?= $selectedUnit === 'OFFICE' ? 'selected' : '' ?>>GRM Head Office &amp; Admin</option>
                    </optgroup>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category['id'] ?>" <?= $selectedCategory === (int)$category['id'] ? 'selected' : '' ?>>
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['IN_USE' => 'In Use', 'IDLE' => 'Idle', 'IN_MAINTENANCE' => 'In Maintenance', 'DAMAGED' => 'Damaged', 'DISPOSED' => 'Disposed'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Search Assets</label>
                <input type="search" name="search" class="form-control" value="<?= e($selectedSearch) ?>" placeholder="Asset, code, serial...">
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-primary" aria-label="Filter assets"><i class="bi bi-search"></i></button>
            </div>
        </div>
    </form>

    <section class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Registered QR Codes</h5>
            <span class="badge text-bg-light border"><?= count($assets) ?> assets</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>QR</th>
                        <th>Asset</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assets)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No assets match these filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td>
                                    <img class="qr-preview" src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&amp;data=<?= urlencode($asset['barcode_qr'] ?? $asset['asset_code']) ?>" alt="QR for <?= e($asset['asset_code']) ?>">
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= e($asset['name']) ?></div>
                                    <div class="small text-muted font-monospace"><?= e($asset['asset_code']) ?> · <?= e($asset['barcode_qr'] ?? '') ?></div>
                                </td>
                                <td><?= e($asset['location_display'] ?? '') ?></td>
                                <td><span class="badge text-bg-light border"><?= e(str_replace('_', ' ', $asset['status'])) ?></span></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <?php if (hasPermission('assets', 'view')): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="<?= url('asset/view/' . $asset['id']) ?>"><i class="bi bi-eye me-1"></i>View</a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('assets', 'print')): ?>
                                            <a class="btn btn-sm btn-outline-secondary" href="<?= url('asset/label/' . $asset['id']) ?>" target="_blank" rel="noopener"><i class="bi bi-printer me-1"></i>Print QR</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<style>
    .qr-preview { width: 48px; height: 48px; }
</style>
