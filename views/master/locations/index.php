<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-diagram-2 me-2 text-primary"></i>Warehouse Locations & Racks</h2>
        <span class="text-muted">Manage storage aisles, racks, shelves, and bins for accurate item placement</span>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newLocationModal">
            <i class="bi bi-plus-circle me-1"></i> Add Storage Location
        </button>
    </div>
</div>

<!-- Filters Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('master/locations') ?>" class="row g-3">
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Search Location</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search rack, shelf, bin, label..." value="<?= e($search ?? '') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Warehouse</label>
                <?php if (!empty($scopedWarehouseId) || count($warehouses) <= 1): ?>
                    <input type="hidden" name="warehouse_id" value="<?= e($warehouseId ?? $scopedWarehouseId ?? '') ?>">
                <?php endif; ?>
                <select name="warehouse_id" class="form-select" <?= (!empty($scopedWarehouseId) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                    <?php if (empty($scopedWarehouseId) && count($warehouses) > 1): ?>
                        <option value="">All Warehouses</option>
                    <?php endif; ?>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?= $wh['id'] ?>" <?= ($warehouseId ?? '') == $wh['id'] ? 'selected' : '' ?>>
                            <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="<?= url('master/locations') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Locations Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($locations)): ?>
            <div class="text-center py-5">
                <i class="bi bi-diagram-2 fs-1 text-muted d-block mb-3"></i>
                <h5 class="text-muted">No Storage Locations Configured</h5>
                <p class="text-muted small">Add rack, shelf, and bin locations to organize warehouse stock.</p>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newLocationModal">
                    Add First Location
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Location Label</th>
                            <th>Warehouse</th>
                            <th>Rack / Aisle</th>
                            <th>Shelf</th>
                            <th>Bin / Slot</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $loc): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold font-monospace text-primary fs-6"><?= e($loc['label']) ?></span>
                                </td>
                                <td><span class="badge bg-secondary"><?= e($loc['warehouse_name']) ?></span></td>
                                <td><span class="badge bg-light text-dark border">Rack <?= e($loc['rack']) ?></span></td>
                                <td><span class="badge bg-light text-dark border">Shelf <?= e($loc['shelf']) ?></span></td>
                                <td>
                                    <?php if (!empty($loc['bin'])): ?>
                                        <span class="badge bg-light text-dark border">Bin <?= e($loc['bin']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $loc['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $loc['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?= $loc['id'] ?>">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </button>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade text-start" id="editModal<?= $loc['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST" action="<?= url('master/locations/update/' . $loc['id']) ?>">
                                        <?= CSRF::input() ?>
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Edit Location: <?= e($loc['label']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Warehouse <span class="text-danger">*</span></label>
                                                    <?php if (!empty($scopedWarehouseId) || count($warehouses) <= 1): ?>
                                                        <input type="hidden" name="warehouse_id" value="<?= $loc['warehouse_id'] ?>">
                                                    <?php endif; ?>
                                                    <select name="warehouse_id" class="form-select" <?= (!empty($scopedWarehouseId) || count($warehouses) <= 1) ? 'disabled' : '' ?> required>
                                                        <?php foreach ($warehouses as $wh): ?>
                                                            <option value="<?= $wh['id'] ?>" <?= $loc['warehouse_id'] == $wh['id'] ? 'selected' : '' ?>>
                                                                <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-4">
                                                        <label class="form-label small fw-semibold">Rack <span class="text-danger">*</span></label>
                                                        <input type="text" name="rack" class="form-control" value="<?= e($loc['rack']) ?>" required>
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="form-label small fw-semibold">Shelf</label>
                                                        <input type="text" name="shelf" class="form-control" value="<?= e($loc['shelf']) ?>">
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="form-label small fw-semibold">Bin</label>
                                                        <input type="text" name="bin" class="form-control" value="<?= e($loc['bin'] ?? '') ?>">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Custom Display Label</label>
                                                    <input type="text" name="label" class="form-control" value="<?= e($loc['label']) ?>">
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="actCheck<?= $loc['id'] ?>" <?= $loc['is_active'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label small" for="actCheck<?= $loc['id'] ?>">Location is Active</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: New Location -->
<div class="modal fade" id="newLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('master/locations/store') ?>">
            <?= CSRF::input() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-diagram-2 me-2 text-primary"></i>Add Storage Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Warehouse <span class="text-danger">*</span></label>
                        <?php if (!empty($scopedWarehouseId) || count($warehouses) <= 1): ?>
                            <input type="hidden" name="warehouse_id" value="<?= e($scopedWarehouseId ?? $warehouses[0]['id'] ?? '') ?>">
                        <?php endif; ?>
                        <select name="warehouse_id" class="form-select" <?= (!empty($scopedWarehouseId) || count($warehouses) <= 1) ? 'disabled' : '' ?> required>
                            <?php foreach ($warehouses as $wh): ?>
                                <option value="<?= $wh['id'] ?>"><?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Rack / Aisle <span class="text-danger">*</span></label>
                            <input type="text" name="rack" class="form-control" placeholder="e.g. A1" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Shelf</label>
                            <input type="text" name="shelf" class="form-control" placeholder="e.g. 02">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Bin / Slot</label>
                            <input type="text" name="bin" class="form-control" placeholder="e.g. B">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Custom Label (Optional)</label>
                        <input type="text" name="label" class="form-control" placeholder="Leave blank to auto-generate">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Save Location</button>
                </div>
            </div>
        </form>
    </div>
</div>
