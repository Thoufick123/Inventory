<?php
// views/production/rework/index.php
// Production module: Rework from Warehouse management
?>
<div class="page-header mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h2 class="mb-0"><i class="bi bi-arrow-repeat me-2 text-warning"></i>Rework from Warehouse</h2>
            <span class="text-muted">Manage defective and damaged goods returned from warehouses for factory rework, repair, and re-dispatch</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (hasPermission('outward', 'view')): ?>
                <a href="<?= url('production/outward') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-truck me-1"></i> Outward Dispatches
                </a>
            <?php endif; ?>
            <?php if (hasPermission('warehouse_rework', 'create')): ?>
                <button type="button" class="btn btn-warning text-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#logReworkModal">
                    <i class="bi bi-plus-circle me-1"></i> Receive Rework from Warehouse
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- KPI Stats Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm bg-primary bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Total Rework Received</span>
                    <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($stats['total_pieces']) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted"><?= number_format($stats['total_records']) ?> total cases logged</small>
                </div>
                <div class="bg-primary text-white rounded-3 p-3">
                    <i class="bi bi-box-arrow-in-down-left fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm bg-warning bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Pending / In Rework</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($stats['qty_in_rework'] + $stats['qty_received']) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted"><?= ($stats['count_in_rework'] + $stats['count_received']) ?> active factory repairs</small>
                </div>
                <div class="bg-warning text-dark rounded-3 p-3">
                    <i class="bi bi-tools fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm bg-success bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Repaired &amp; Restocked</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($stats['qty_repaired']) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted"><?= number_format($stats['qty_redispatched']) ?> pcs re-dispatched</small>
                </div>
                <div class="bg-success text-white rounded-3 p-3">
                    <i class="bi bi-check2-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm bg-danger bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Scrapped / Beyond Repair</span>
                    <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($stats['qty_scrapped']) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted"><?= number_format($stats['count_scrapped']) ?> written-off cases</small>
                </div>
                <div class="bg-danger text-white rounded-3 p-3">
                    <i class="bi bi-trash3-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Search Bar -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body py-3">
        <form method="GET" action="<?= url('production/warehouse-rework') ?>" class="row g-2 align-items-center" id="reworkFilterForm">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" 
                           id="reworkLiveSearch" 
                           name="search" 
                           class="form-control border-start-0" 
                           placeholder="Search rework #, batch #, outward #, product, warehouse..." 
                           value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="RECEIVED" <?= ($filters['status'] ?? '') === 'RECEIVED' ? 'selected' : '' ?>>RECEIVED (Pending Rework)</option>
                    <option value="IN_REWORK" <?= ($filters['status'] ?? '') === 'IN_REWORK' ? 'selected' : '' ?>>IN_REWORK (Under Repair)</option>
                    <option value="REPAIRED" <?= ($filters['status'] ?? '') === 'REPAIRED' ? 'selected' : '' ?>>REPAIRED (Ready / Restocked)</option>
                    <option value="SCRAPPED" <?= ($filters['status'] ?? '') === 'SCRAPPED' ? 'selected' : '' ?>>SCRAPPED (Beyond Repair)</option>
                    <option value="RE_DISPATCHED" <?= ($filters['status'] ?? '') === 'RE_DISPATCHED' ? 'selected' : '' ?>>RE_DISPATCHED (Sent to Warehouse)</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Returning Warehouses</option>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?= $wh['id'] ?>" <?= ((string)($filters['warehouse_id'] ?? '') === (string)$wh['id']) ? 'selected' : '' ?>>
                            <?= e($wh['name']) ?> (<?= e($wh['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-secondary flex-fill"><i class="bi bi-funnel me-1"></i> Filter</button>
                <?php if (!empty($filters['search']) || !empty($filters['status']) || !empty($filters['warehouse_id']) || !empty($filters['defect_type'])): ?>
                    <a href="<?= url('production/warehouse-rework') ?>" class="btn btn-sm btn-outline-danger" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Main Rework Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="card-title mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Warehouse Rework Return Records</h5>
            <span class="badge bg-light text-dark border rounded-pill px-3 py-2"><?= count($reworks) ?> Records</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="warehouseReworkTable">
                <thead class="table-light">
                    <tr>
                        <th>Rework # &amp; Date</th>
                        <th>Origin Warehouse</th>
                        <th>Outward / Batch Ref</th>
                        <th>Product &amp; SKU</th>
                        <th>Returned Qty</th>
                        <th>Repaired / Scrap</th>
                        <th>Defect / Reason</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="reworkTableBody">
                    <?php if (empty($reworks)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary"></i>
                                <h6 class="fw-bold text-secondary">No warehouse rework returns found</h6>
                                <p class="small text-muted mb-3">When products sent outward to warehouses are returned due to damages or defects, log them here to route them through factory repair.</p>
                                <?php if (hasPermission('warehouse_rework', 'create')): ?>
                                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#logReworkModal">
                                        <i class="bi bi-plus-circle me-1"></i> Receive First Rework Return
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reworks as $rw): ?>
                            <?php 
                                $statusBadge = 'bg-secondary';
                                if ($rw['status'] === 'RECEIVED') $statusBadge = 'bg-warning text-dark';
                                elseif ($rw['status'] === 'IN_REWORK') $statusBadge = 'bg-primary';
                                elseif ($rw['status'] === 'REPAIRED') $statusBadge = 'bg-success';
                                elseif ($rw['status'] === 'SCRAPPED') $statusBadge = 'bg-danger';
                                elseif ($rw['status'] === 'RE_DISPATCHED') $statusBadge = 'bg-info text-dark';

                                $searchData = implode(' ', [
                                    $rw['rework_number'],
                                    $rw['challan_number'] ?? '',
                                    $rw['warehouse_name'],
                                    $rw['outward_number'] ?? '',
                                    $rw['batch_number'] ?? '',
                                    $rw['product_name'],
                                    $rw['sku'] ?? '',
                                    $rw['damage_reason'],
                                    $rw['defect_type'],
                                    $rw['assigned_to'] ?? '',
                                    $rw['status']
                                ]);
                            ?>
                            <tr class="rework-row" data-search-text="<?= e($searchData) ?>">
                                <td>
                                    <div class="fw-bold font-monospace text-primary"><?= e($rw['rework_number']) ?></div>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($rw['received_date'])) ?>
                                    </small>
                                    <?php if (!empty($rw['challan_number'])): ?>
                                        <span class="badge bg-light text-dark border font-monospace mt-1">DC: <?= e($rw['challan_number']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border fs-7 mb-1"><?= e($rw['warehouse_name']) ?></span>
                                    <small class="text-muted d-block font-monospace"><?= e($rw['warehouse_code']) ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($rw['outward_number'])): ?>
                                        <div class="font-monospace fw-semibold text-dark"><?= e($rw['outward_number']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($rw['batch_number'])): ?>
                                        <small class="text-muted">Batch: <code class="text-primary"><?= e($rw['batch_number']) ?></code></small>
                                    <?php else: ?>
                                        <small class="text-muted">Direct Warehouse Return</small>
                                    <?php endif; ?>
                                    <?php if (!empty($rw['production_unit_name'])): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small fw-semibold" title="Assigned Production Facility">
                                                <i class="bi bi-gear-wide-connected me-1"></i><?= e($rw['production_unit_name']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($rw['product_name']) ?></div>
                                    <small class="text-muted font-monospace"><?= e($rw['sku'] ?? 'NO-SKU') ?></small>
                                    <div class="d-flex gap-1 mt-1">
                                        <?php if (!empty($rw['size_name'])): ?>
                                            <span class="badge bg-light text-dark border"><?= e($rw['size_name']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($rw['color_name'])): ?>
                                            <span class="badge bg-light text-dark border"><?= e($rw['color_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold fs-6 text-danger"><?= number_format($rw['quantity']) ?> pcs</span>
                                </td>
                                <td>
                                    <?php if ($rw['status'] === 'REPAIRED' || $rw['status'] === 'RE_DISPATCHED'): ?>
                                        <span class="text-success fw-bold"><i class="bi bi-check-lg me-1"></i><?= number_format($rw['repaired_qty']) ?> rep.</span>
                                        <?php if ($rw['scrapped_qty'] > 0): ?>
                                            <small class="text-danger d-block"><?= number_format($rw['scrapped_qty']) ?> scrapped</small>
                                        <?php endif; ?>
                                    <?php elseif ($rw['status'] === 'SCRAPPED'): ?>
                                        <span class="text-danger fw-bold"><i class="bi bi-x-circle me-1"></i><?= number_format($rw['scrapped_qty'] ?: $rw['quantity']) ?> scrapped</span>
                                    <?php else: ?>
                                        <span class="text-muted small italic">Pending Repair</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1"><?= e(str_replace('_', ' ', $rw['defect_type'])) ?></span>
                                    <div class="small text-dark" style="max-width: 220px; white-space: normal;"><?= e($rw['damage_reason']) ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $statusBadge ?> px-2 py-1 fs-7 fw-bold"><?= e($rw['status']) ?></span>
                                    <?php if (!empty($rw['assigned_to'])): ?>
                                        <small class="text-muted d-block mt-1"><i class="bi bi-person me-1"></i><?= e($rw['assigned_to']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="viewReworkDetails(<?= $rw['id'] ?>)" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if (hasPermission('warehouse_rework', 'edit') && $rw['status'] === 'RECEIVED'): ?>
                                            <button type="button" class="btn btn-outline-warning text-dark" onclick="openStartReworkModal(<?= htmlspecialchars(json_encode($rw), ENT_QUOTES, 'UTF-8') ?>)" title="Assign &amp; Start Rework">
                                                <i class="bi bi-play-circle me-1"></i> Start
                                            </button>
                                        <?php elseif (hasPermission('warehouse_rework', 'edit') && $rw['status'] === 'IN_REWORK'): ?>
                                            <button type="button" class="btn btn-outline-success" onclick="openCompleteReworkModal(<?= htmlspecialchars(json_encode($rw), ENT_QUOTES, 'UTF-8') ?>)" title="Complete Rework &amp; Record Outcome">
                                                <i class="bi bi-check2-circle me-1"></i> Complete
                                            </button>
                                        <?php elseif (hasPermission('warehouse_rework', 'dispatch') && $rw['status'] === 'REPAIRED'): ?>
                                            <button type="button" class="btn btn-outline-info text-dark" onclick="openRedispatchModal(<?= htmlspecialchars(json_encode($rw), ENT_QUOTES, 'UTF-8') ?>)" title="Re-dispatch Repaired Goods to Warehouse">
                                                <i class="bi bi-truck me-1"></i> Dispatch
                                            </button>
                                        <?php endif; ?>
                                        <?php if (hasPermission('warehouse_rework', 'delete')): ?>
                                            <form method="POST" action="<?= url('production/warehouse-rework/delete/' . $rw['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete rework record <?= e(addslashes($rw['rework_number'])) ?>?');">
                                                <?= CSRF::field() ?>
                                                <button type="submit" class="btn btn-outline-danger" title="Delete Record">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
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
    <!-- Numbered Button Pagination Footer -->
    <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="reworkPaginationFooter">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="reworkPageInfo">
                Showing 1 to 10 of <?= count($reworks) ?> records
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="reworkPageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                    <option value="10" selected>10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <nav aria-label="Rework Pagination">
            <ul class="pagination pagination-modern mb-0" id="reworkPaginationList">
            </ul>
        </nav>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: RECEIVE / LOG REWORK FROM WAREHOUSE -->
<!-- ========================================================================= -->
<?php if (hasPermission('warehouse_rework', 'create')): ?>
<div class="modal fade" id="logReworkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-box-arrow-in-down-left me-2"></i>Receive Damaged Goods from Warehouse for Rework
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('production/warehouse-rework/store') ?>" enctype="multipart/form-data" id="logReworkForm">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-3 mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
                        <div class="small">
                            Items received from warehouses after inward due to transit damages, stitching errors, or fabric defects can be logged here for factory repair.
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Returning Warehouse <span class="text-danger">*</span></label>
                            <select name="warehouse_id" id="modalWarehouseId" class="form-select" required>
                                <option value="">-- Select Origin Warehouse --</option>
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?= $wh['id'] ?>"><?= e($wh['name']) ?> (<?= e($wh['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Return Challan / Delivery Note #</label>
                            <input type="text" name="challan_number" class="form-control" placeholder="e.g. DC-WH-1049 or Return Gatepass #">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Associated Outward / Production Batch</label>
                            <select name="production_batch_id" id="modalBatchSelect" class="form-select" onchange="onBatchSelected(this.value)">
                                <option value="">-- Optional: Link to Batch / Outward --</option>
                                <?php foreach ($dispatchedBatches as $db): ?>
                                    <option value="<?= $db['id'] ?>" data-product="<?= e($db['product_name']) ?>" data-warehouse="<?= $db['warehouse_id'] ?>" data-outward="<?= $db['outward_id'] ?? '' ?>">
                                        <?= e($db['batch_number']) ?> <?= !empty($db['outward_number']) ? '('.e($db['outward_number']).')' : '' ?> — <?= e($db['product_name']) ?> (<?= e($db['warehouse_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">Selecting a batch will filter the variants belonging to it.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date Received <span class="text-danger">*</span></label>
                            <input type="date" name="received_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Product Variant &amp; Size <span class="text-danger">*</span></label>
                            <select name="variant_id" id="modalVariantSelect" class="form-select" required>
                                <option value="">-- Select Product Variant --</option>
                                <?php foreach ($allVariants as $var): ?>
                                    <option value="<?= $var['id'] ?>">
                                        <?= e($var['product_name']) ?> — SKU: <?= e($var['sku']) ?> <?= !empty($var['size_name']) ? '['.e($var['size_name']).']' : '' ?> <?= !empty($var['color_name']) ? '('.e($var['color_name']).')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Damaged Qty to Rework <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="quantity" class="form-control fw-bold text-danger" min="1" value="1" required>
                                <span class="input-group-text">pcs</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Defect Classification <span class="text-danger">*</span></label>
                            <select name="defect_type" class="form-select" required>
                                <option value="STITCHING_ERROR" selected>Stitching Defect / Open Seam</option>
                                <option value="FABRIC_DEFECT">Fabric Flaw / Hole / Weave Error</option>
                                <option value="BUTTON_ZIPPER_ERROR">Button / Zipper / Trims Defect</option>
                                <option value="LABEL_TAG_ERROR">Label / Barcode Tag Missing / Incorrect</option>
                                <option value="SOIL_STAIN">Oil / Water / Soil Stain (Washable)</option>
                                <option value="PACKAGING_DAMAGE">Packaging / Polybag Crushed</option>
                                <option value="SIZE_MEASUREMENT_ERROR">Size / Measurement Alteration Needed</option>
                                <option value="TRANSIT_DAMAGE">Damaged in Transit / Transport</option>
                                <option value="OTHER">Other Manufacturing Defect</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Damage Reason / Issue Summary <span class="text-danger">*</span></label>
                            <input type="text" name="damage_reason" class="form-control" placeholder="e.g. Collar stitch loose, missing sleeve button..." required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assign to Production Unit</label>
                            <select name="production_unit_id" class="form-select">
                                <option value="">-- Main Factory / Facility --</option>
                                <?php foreach ($productionUnits as $pu): ?>
                                    <option value="<?= $pu['id'] ?>"><?= e($pu['name']) ?> (<?= e($pu['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assign Tailor / Master Operator</label>
                            <input type="text" name="assigned_to" class="form-control" placeholder="Tailor or supervisor name">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Detailed Defect &amp; Rework Instructions</label>
                        <textarea name="defect_notes" class="form-control" rows="2" placeholder="Specific notes on repair required, stitching alterations, wash treatment..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Photo of Damage (Optional)</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>

                    <?php if (hasPermission('warehouse_rework', 'edit')): ?>
                    <div class="form-check form-switch p-3 bg-light rounded-3 border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="start_immediately" id="startImmediatelyCheck" value="1" checked>
                        <label class="form-check-label fw-semibold" for="startImmediatelyCheck">
                            Immediately mark as "IN_REWORK" (Floor Work Started)
                        </label>
                        <small class="text-muted d-block">If unchecked, status will remain "RECEIVED" until work is initiated.</small>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4">
                        <i class="bi bi-check2-circle me-1"></i> Confirm &amp; Log Rework
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- MODAL 2: START REWORK -->
<!-- ========================================================================= -->
<?php if (hasPermission('warehouse_rework', 'edit')): ?>
<div class="modal fade" id="startReworkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-tools me-2"></i>Start Factory Rework</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="startReworkForm">
                <?= CSRF::field() ?>
                <input type="hidden" name="status" value="IN_REWORK">
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="fw-bold fs-6 text-primary" id="startReworkRef">—</div>
                        <div class="small text-muted" id="startReworkProduct">—</div>
                        <div class="fw-bold text-danger mt-1" id="startReworkQty">—</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assign Tailor / Craftsman</label>
                        <input type="text" name="assigned_to" id="startAssignedTo" class="form-control" placeholder="e.g. Master Selvam / Alteration Unit">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Floor Repair Notes</label>
                        <textarea name="repair_notes" class="form-control" rows="2" placeholder="Instructions for tailor or notes on repair approach..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4"><i class="bi bi-play-fill me-1"></i> Start Rework</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- MODAL 3: COMPLETE REWORK -->
<!-- ========================================================================= -->
<?php if (hasPermission('warehouse_rework', 'edit')): ?>
<div class="modal fade" id="completeReworkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-check2-circle me-2"></i>Complete Rework &amp; Record Outcome</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="completeReworkForm">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="fw-bold fs-6 text-primary" id="completeReworkRef">—</div>
                        <div class="small text-muted" id="completeReworkProduct">—</div>
                        <div class="fw-bold text-dark mt-1">Returned Qty: <span id="completeReworkTotalQty" class="text-danger">0</span> pcs</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold text-success">Repaired Successfully <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="repaired_qty" id="completeRepairedQty" class="form-control fw-bold text-success" min="0" value="0" required oninput="calcReworkOutcome()">
                                <span class="input-group-text">pcs</span>
                            </div>
                            <div class="form-text small">Passed inspection and ready for dispatch.</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold text-danger">Scrapped / Beyond Repair</label>
                            <div class="input-group">
                                <input type="number" name="scrapped_qty" id="completeScrappedQty" class="form-control fw-bold text-danger" min="0" value="0" oninput="calcReworkOutcome()">
                                <span class="input-group-text">pcs</span>
                            </div>
                            <div class="form-text small">Cannot be saved (fabric torn/burned).</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Repair / QC Verdict Notes</label>
                        <textarea name="repair_notes" class="form-control" rows="2" placeholder="e.g. Re-stitched collar, button replaced, passed QC inspection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4"><i class="bi bi-check-all me-1"></i> Save Outcome</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- MODAL 4: RE-DISPATCH REWORKED GOODS -->
<!-- ========================================================================= -->
<?php if (hasPermission('warehouse_rework', 'dispatch')): ?>
<div class="modal fade" id="redispatchReworkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-dark py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-truck me-2"></i>Re-dispatch Repaired Goods to Warehouse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="redispatchReworkForm">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="fw-bold fs-6 text-primary" id="redispatchReworkRef">—</div>
                        <div class="small text-muted" id="redispatchReworkWarehouse">—</div>
                        <div class="fw-bold text-success mt-1">Ready to Dispatch: <span id="redispatchReworkQty">0</span> pcs</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vehicle Number / Courier Ref <span class="text-danger">*</span></label>
                        <input type="text" name="redispatched_vehicle" class="form-control text-uppercase font-monospace" placeholder="e.g. TN-38-XY-9081 or Courier Tracking #" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dispatch Challan / Delivery Notes</label>
                        <textarea name="redispatched_notes" class="form-control" rows="2" placeholder="Driver contact, gate pass reference, box markings..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-dark fw-bold px-4"><i class="bi bi-send-check me-1"></i> Confirm Re-dispatch</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- MODAL 5: VIEW REWORK DETAILS -->
<!-- ========================================================================= -->
<div class="modal fade" id="viewReworkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-card-text me-2 text-warning"></i>Rework Return Dossier</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="viewReworkBody">
                <div class="text-center py-4 text-muted" id="viewReworkSpinner">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div>Loading rework details...</div>
                </div>
                <div id="viewReworkContent" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-3 bg-light border mb-4 flex-wrap gap-2">
                        <div>
                            <span class="text-uppercase small text-muted fw-bold d-block">Rework Ref Number</span>
                            <span class="fs-5 fw-bold text-primary font-monospace" id="vReworkNum">—</span>
                        </div>
                        <div>
                            <span class="badge px-3 py-2 fs-7" id="vStatusBadge">STATUS</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="small fw-bold text-muted text-uppercase mb-2"><i class="bi bi-box-seam text-primary me-1"></i> Product &amp; Origin</div>
                                <div class="mb-1"><strong>Product:</strong> <span id="vProductName" class="text-dark fw-bold">—</span></div>
                                <div class="mb-1"><strong>SKU:</strong> <span id="vSku" class="font-monospace">—</span></div>
                                <div class="mb-1"><strong>Size / Color:</strong> <span id="vSizeColor">—</span></div>
                                <div class="mb-1"><strong>Warehouse:</strong> <span id="vWarehouse" class="badge bg-light text-dark border">—</span></div>
                                <div class="mb-1"><strong>Outward / Batch:</strong> <span id="vOutwardBatch">—</span></div>
                                <div><strong>Target Unit:</strong> <span id="vProductionUnit" class="badge bg-primary-subtle text-primary border border-primary-subtle">—</span></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="small fw-bold text-muted text-uppercase mb-2"><i class="bi bi-tools text-warning me-1"></i> Quantities &amp; Floor</div>
                                <div class="mb-1"><strong>Returned Qty:</strong> <span id="vTotalQty" class="text-danger fw-bold">—</span></div>
                                <div class="mb-1"><strong>Repaired Qty:</strong> <span id="vRepairedQty" class="text-success fw-bold">—</span></div>
                                <div class="mb-1"><strong>Scrapped Qty:</strong> <span id="vScrappedQty" class="text-danger">—</span></div>
                                <div class="mb-1"><strong>Assigned Tailor:</strong> <span id="vAssignedTo">—</span></div>
                                <div><strong>Received Date:</strong> <span id="vReceivedDate">—</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="small fw-bold text-muted text-uppercase mb-1"><i class="bi bi-exclamation-triangle text-danger me-1"></i> Defect Analysis</div>
                        <div class="mb-1"><strong>Defect Type:</strong> <span id="vDefectType" class="badge bg-danger-subtle text-danger border border-danger-subtle">—</span></div>
                        <div class="mb-2"><strong>Damage Reason:</strong> <span id="vDamageReason" class="text-dark">—</span></div>
                        <div id="vDefectNotesContainer" style="display: none;">
                            <strong>Notes:</strong> <span id="vDefectNotes" class="text-muted small"></span>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-3" id="vRepairNotesContainer" style="display: none;">
                        <div class="small fw-bold text-muted text-uppercase mb-1"><i class="bi bi-check2-circle text-success me-1"></i> Repair Outcome Notes</div>
                        <div id="vRepairNotes" class="small text-dark"></div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-3" id="vRedispatchContainer" style="display: none;">
                        <div class="small fw-bold text-muted text-uppercase mb-1"><i class="bi bi-truck text-info me-1"></i> Re-dispatch Details</div>
                        <div class="mb-1"><strong>Vehicle:</strong> <span id="vRedispatchVehicle" class="font-monospace fw-bold"></span></div>
                        <div class="mb-1"><strong>Dispatched Date:</strong> <span id="vRedispatchDate"></span></div>
                        <div id="vRedispatchNotes" class="small text-muted"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let reworkPagination;

function initReworkPaginationModule() {
    const tableBody = document.getElementById('reworkTableBody');
    const searchInput = document.getElementById('reworkLiveSearch');
    if (!tableBody) return;

    if (typeof window.initClientPagination === 'function') {
        reworkPagination = window.initClientPagination({
            container: '#reworkTableBody',
            itemSelector: 'tr.rework-row',
            footer: '#reworkPaginationFooter',
            infoEl: '#reworkPageInfo',
            paginationList: '#reworkPaginationList',
            pageSizeSelect: '#reworkPageSizeSelect',
            pageSize: 10,
            itemName: 'records'
        });
    }

    if (searchInput) {
        function filterRows() {
            const query = searchInput.value.trim().toLowerCase();
            const rows = tableBody.querySelectorAll('.rework-row');
            let visibleCount = 0;

            if (reworkPagination) {
                visibleCount = reworkPagination.filter(row => {
                    if (!query) return true;
                    const text = (row.getAttribute('data-search-text') || row.textContent || '').toLowerCase();
                    return text.includes(query);
                });
            } else {
                rows.forEach(row => {
                    const text = (row.getAttribute('data-search-text') || row.textContent || '').toLowerCase();
                    if (!query || text.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        }

        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(filterRows, 150);
        });

        if (searchInput.value.trim() !== '') {
            filterRows();
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReworkPaginationModule);
} else {
    initReworkPaginationModule();
}

function onBatchSelected(batchId) {
    if (!batchId) return;

    const selectEl = document.getElementById('modalBatchSelect');
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const warehouseId = selectedOption.getAttribute('data-warehouse');
    if (warehouseId) {
        const whEl = document.getElementById('modalWarehouseId');
        if (whEl) whEl.value = warehouseId;
    }

    fetch('<?= url("production/warehouse-rework/lookup-batch/") ?>' + batchId)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.variants && data.variants.length > 0) {
                const varSelect = document.getElementById('modalVariantSelect');
                if (!varSelect) return;
                varSelect.innerHTML = '<option value="">-- Select Product Variant from this Batch --</option>';
                data.variants.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.variant_id;
                    opt.textContent = `${v.product_name} — SKU: ${v.sku} [${v.size_name || 'Standard'}] (${v.color_name || 'Standard'}) — Batch Qty: ${v.quantity} pcs`;
                    varSelect.appendChild(opt);
                });
                if (data.variants.length === 1) {
                    varSelect.selectedIndex = 1;
                }
            }
        })
        .catch(err => console.error(err));
}

function openStartReworkModal(rework) {
    document.getElementById('startReworkForm').action = '<?= url("production/warehouse-rework/status/") ?>' + rework.id;
    document.getElementById('startReworkRef').textContent = rework.rework_number;
    document.getElementById('startReworkProduct').textContent = rework.product_name + ' (SKU: ' + (rework.sku || 'N/A') + ')';
    document.getElementById('startReworkQty').textContent = 'Defective Qty to Repair: ' + rework.quantity + ' pcs';
    document.getElementById('startAssignedTo').value = rework.assigned_to || '';

    const modal = new bootstrap.Modal(document.getElementById('startReworkModal'));
    modal.show();
}

let activeTotalReworkQty = 0;
function openCompleteReworkModal(rework) {
    activeTotalReworkQty = parseInt(rework.quantity) || 1;
    document.getElementById('completeReworkForm').action = '<?= url("production/warehouse-rework/complete/") ?>' + rework.id;
    document.getElementById('completeReworkRef').textContent = rework.rework_number;
    document.getElementById('completeReworkProduct').textContent = rework.product_name + ' (SKU: ' + (rework.sku || 'N/A') + ')';
    document.getElementById('completeReworkTotalQty').textContent = activeTotalReworkQty;
    document.getElementById('completeRepairedQty').value = activeTotalReworkQty;
    document.getElementById('completeScrappedQty').value = 0;
    document.getElementById('completeRepairedQty').max = activeTotalReworkQty;
    document.getElementById('completeScrappedQty').max = activeTotalReworkQty;

    const modal = new bootstrap.Modal(document.getElementById('completeReworkModal'));
    modal.show();
}

function calcReworkOutcome() {
    const repairedEl = document.getElementById('completeRepairedQty');
    const scrappedEl = document.getElementById('completeScrappedQty');
    let repaired = parseInt(repairedEl.value) || 0;
    let scrapped = parseInt(scrappedEl.value) || 0;

    if (repaired > activeTotalReworkQty) {
        repaired = activeTotalReworkQty;
        repairedEl.value = repaired;
    }
    if ((repaired + scrapped) > activeTotalReworkQty) {
        scrapped = activeTotalReworkQty - repaired;
        scrappedEl.value = scrapped;
    }
}

function openRedispatchModal(rework) {
    document.getElementById('redispatchReworkForm').action = '<?= url("production/warehouse-rework/redispatch/") ?>' + rework.id;
    document.getElementById('redispatchReworkRef').textContent = rework.rework_number;
    document.getElementById('redispatchReworkWarehouse').textContent = 'Returning to: ' + rework.warehouse_name;
    document.getElementById('redispatchReworkQty').textContent = rework.repaired_qty || rework.quantity;

    const modal = new bootstrap.Modal(document.getElementById('redispatchReworkModal'));
    modal.show();
}

function viewReworkDetails(id) {
    const modalEl = document.getElementById('viewReworkModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    const spinner = document.getElementById('viewReworkSpinner');
    const content = document.getElementById('viewReworkContent');
    spinner.style.display = 'block';
    content.style.display = 'none';

    fetch('<?= url("production/warehouse-rework/view-ajax/") ?>' + id)
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                alert(res.message || 'Error loading details.');
                modal.hide();
                return;
            }
            const d = res.data;
            document.getElementById('vReworkNum').textContent = d.rework_number;
            const badge = document.getElementById('vStatusBadge');
            badge.textContent = d.status;
            badge.className = 'badge px-3 py-2 fs-7 ' + (
                d.status === 'RECEIVED' ? 'bg-warning text-dark' :
                d.status === 'IN_REWORK' ? 'bg-primary' :
                d.status === 'REPAIRED' ? 'bg-success' :
                d.status === 'SCRAPPED' ? 'bg-danger' : 'bg-info text-dark'
            );

            document.getElementById('vProductName').textContent = d.product_name;
            document.getElementById('vSku').textContent = d.sku || 'N/A';
            document.getElementById('vSizeColor').textContent = (d.size_name || 'Standard') + ' / ' + (d.color_name || 'Standard');
            document.getElementById('vWarehouse').textContent = d.warehouse_name + (d.warehouse_code ? ' (' + d.warehouse_code + ')' : '');
            document.getElementById('vOutwardBatch').textContent = (d.outward_number ? d.outward_number + ' | ' : '') + (d.batch_number ? 'Batch ' + d.batch_number : 'Direct Return');
            document.getElementById('vProductionUnit').textContent = d.production_unit_name ? (d.production_unit_name + (d.production_unit_code ? ' (' + d.production_unit_code + ')' : '')) : 'All Units / Unassigned';

            document.getElementById('vTotalQty').textContent = d.quantity + ' pcs';
            document.getElementById('vRepairedQty').textContent = (d.repaired_qty || 0) + ' pcs';
            document.getElementById('vScrappedQty').textContent = (d.scrapped_qty || 0) + ' pcs';
            document.getElementById('vAssignedTo').textContent = d.assigned_to || 'Not assigned';
            document.getElementById('vReceivedDate').textContent = d.received_date || '—';

            document.getElementById('vDefectType').textContent = (d.defect_type || '').replace(/_/g, ' ');
            document.getElementById('vDamageReason').textContent = d.damage_reason || '—';

            if (d.defect_notes && d.defect_notes.trim()) {
                document.getElementById('vDefectNotes').textContent = d.defect_notes;
                document.getElementById('vDefectNotesContainer').style.display = 'block';
            } else {
                document.getElementById('vDefectNotesContainer').style.display = 'none';
            }

            if (d.repair_notes && d.repair_notes.trim()) {
                document.getElementById('vRepairNotes').textContent = d.repair_notes;
                document.getElementById('vRepairNotesContainer').style.display = 'block';
            } else {
                document.getElementById('vRepairNotesContainer').style.display = 'none';
            }

            if (d.status === 'RE_DISPATCHED' || d.redispatched_vehicle) {
                document.getElementById('vRedispatchVehicle').textContent = d.redispatched_vehicle || 'Direct Handover';
                document.getElementById('vRedispatchDate').textContent = d.redispatched_date || '—';
                document.getElementById('vRedispatchNotes').textContent = d.redispatched_notes || '';
                document.getElementById('vRedispatchContainer').style.display = 'block';
            } else {
                document.getElementById('vRedispatchContainer').style.display = 'none';
            }

            spinner.style.display = 'none';
            content.style.display = 'block';
        })
        .catch(err => {
            console.error(err);
            alert('Failed to load rework details.');
            modal.hide();
        });
}
</script>
