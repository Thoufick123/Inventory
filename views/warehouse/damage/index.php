<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1 text-dark fw-bold"><i class="bi bi-exclamation-octagon me-2 text-danger"></i>Warehouse Damage &amp; Factory Rework</h2>
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('damage', 'create')): ?>
        <a href="<?= url('warehouse/damage/create') ?>" class="btn btn-danger shadow-sm px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-qr-code-scan fs-6"></i>
            <span>Scan &amp; Log Damage / Rework</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- 1. KPI Summary Cards (Clickable to Filter) -->
<div class="row g-3 mb-4">
    <!-- Damaged Units Card -->
    <div class="col-xl-3 col-sm-6">
        <a href="<?= url('warehouse/damage?status=LOGGED') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10 h-100 rounded-3 transition-hover border-start border-4 border-danger">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold small d-block">Damaged Units (Quarantine)</span>
                        <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($stats['total_units'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                        <small class="text-danger fw-semibold"><i class="bi bi-filter me-1"></i>Click to filter</small>
                    </div>
                    <div class="bg-danger text-white rounded-3 p-3 shadow-sm">
                        <i class="bi bi-slash-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Factory Rework Units Card -->
    <div class="col-xl-3 col-sm-6">
        <a href="<?= url('warehouse/damage?status=REWORK') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10 h-100 rounded-3 transition-hover border-start border-4 border-warning">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold small d-block">Factory Rework Units</span>
                        <h3 class="fw-bold text-warning-emphasis mb-0 mt-1"><?= number_format($stats['rework_units'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                        <small class="text-warning-emphasis fw-semibold"><i class="bi bi-arrow-repeat me-1"></i>Sent to factory</small>
                    </div>
                    <div class="bg-warning text-dark rounded-3 p-3 shadow-sm">
                        <i class="bi bi-arrow-repeat fs-4"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Estimated Loss Card -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-dark bg-opacity-10 h-100 rounded-3 border-start border-4 border-dark">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-bold small d-block">Estimated Loss Valuation</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1">₹<?= number_format($stats['total_valuation_loss'] ?? 0, 2) ?></h3>
                    <small class="text-muted">Total potential write-off</small>
                </div>
                <div class="bg-dark text-white rounded-3 p-3 shadow-sm">
                    <i class="bi bi-currency-rupee fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Salvaged & Repaired Card -->
    <div class="col-xl-3 col-sm-6">
        <a href="<?= url('warehouse/damage?status=SALVAGED') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10 h-100 rounded-3 transition-hover border-start border-4 border-success">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold small d-block">Salvaged / Repaired</span>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format(($stats['salvaged'] ?? 0) + ($stats['repaired'] ?? 0)) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                        <small class="text-success fw-semibold"><i class="bi bi-check2-circle me-1"></i>Recovered value</small>
                    </div>
                    <div class="bg-success text-white rounded-3 p-3 shadow-sm">
                        <i class="bi bi-recycle fs-4"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- 2. Filters & Search -->
<div class="card mb-4 border-0 shadow-sm rounded-3">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="<?= url('warehouse/damage') ?>" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-search me-1"></i> Search Records</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Damage #, Batch, Tag, SKU..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-funnel me-1"></i> Source Type</label>
                <select name="source_type" class="form-select">
                    <option value="">All Sources</option>
                    <option value="PRODUCTION_INWARD" <?= ($filters['source_type'] ?? '') === 'PRODUCTION_INWARD' ? 'selected' : '' ?>>Production Inward (GRN)</option>
                    <option value="STORAGE_HANDLING" <?= ($filters['source_type'] ?? '') === 'STORAGE_HANDLING' ? 'selected' : '' ?>>Storage &amp; Handling</option>
                    <option value="SHOP_RETURN" <?= ($filters['source_type'] ?? '') === 'SHOP_RETURN' ? 'selected' : '' ?>>Shop / Store Return</option>
                    <option value="FACTORY_REWORK" <?= ($filters['source_type'] ?? '') === 'FACTORY_REWORK' ? 'selected' : '' ?>>Factory Rework</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-building me-1"></i> Warehouse</label>
                <select name="warehouse_id" class="form-select" <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                    <?php if (!function_exists('currentWarehouseId') || currentWarehouseId() === null && count($warehouses) > 1): ?>
                        <option value="">All Warehouses</option>
                    <?php endif; ?>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?= $wh['id'] ?>" <?= ((isset($filters['warehouse_id']) && $filters['warehouse_id'] == $wh['id']) || ((function_exists('currentWarehouseId') && currentWarehouseId() == $wh['id']))) ? 'selected' : '' ?>>
                            <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1): ?>
                    <input type="hidden" name="warehouse_id" value="<?= e($filters['warehouse_id'] ?? ($warehouses[0]['id'] ?? '')) ?>">
                <?php endif; ?>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-tag me-1"></i> Status / Action</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="LOGGED" <?= ($filters['status'] ?? '') === 'LOGGED' ? 'selected' : '' ?>>LOGGED (Pending / Quarantine)</option>
                    <option value="REWORK" <?= ($filters['status'] ?? '') === 'REWORK' ? 'selected' : '' ?>>REWORK (Sent to Factory)</option>
                    <option value="INSPECTED" <?= ($filters['status'] ?? '') === 'INSPECTED' ? 'selected' : '' ?>>INSPECTED</option>
                    <option value="SALVAGED" <?= ($filters['status'] ?? '') === 'SALVAGED' ? 'selected' : '' ?>>SALVAGED</option>
                    <option value="WRITTEN_OFF" <?= ($filters['status'] ?? '') === 'WRITTEN_OFF' ? 'selected' : '' ?>>WRITTEN OFF</option>
                    <option value="REPAIRED" <?= ($filters['status'] ?? '') === 'REPAIRED' ? 'selected' : '' ?>>REPAIRED</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 fw-semibold shadow-sm">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if (!empty($filters['search']) || !empty($filters['warehouse_id']) || !empty($filters['status']) || !empty($filters['source_type'])): ?>
                    <a href="<?= url('warehouse/damage') ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- 3. Damage Entries Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 text-nowrap py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 140px;">Case</th>
                    <th class="text-nowrap py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 250px;">Product &amp; Variant</th>
                    <th class="text-nowrap py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 160px;">Warehouse</th>
                    <th class="text-nowrap py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 220px;">Source &amp; Origin Batch</th>
                    <th class="text-center text-nowrap py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 90px;">Quantity</th>
                    <th class="text-nowrap py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 130px;">Loss / Valuation</th>
                    <th class="text-nowrap py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 130px;">Status</th>
                    <th class="text-nowrap py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 130px;">Date</th>
                    <th class="pe-3 text-end text-nowrap py-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 90px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($damages)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle fs-1 d-block mb-3 text-success"></i>
                            <h6 class="fw-semibold mb-1">No Damage or Rework Records Found</h6>
                            <p class="small text-muted mb-0">All inventory items are healthy and accounted for.</p>
                        </td>
                    </tr>
                <?php else: foreach ($damages as $d): ?>
                    <?php 
                        $statusVal = !empty($d['status']) ? strtoupper(trim($d['status'])) : 'LOGGED';
                    ?>
                    <tr>
                        <!-- CASE NUMBER -->
                        <td class="ps-3 text-nowrap">
                            <div class="d-flex align-items-center gap-1">
                                <a href="<?= url('warehouse/damage/view/' . $d['id']) ?>" class="badge bg-light text-danger border border-danger-subtle font-monospace px-2.5 py-1.5 fs-7 fw-bold text-decoration-none shadow-2xs d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-file-earmark-text text-danger"></i>
                                    <?= e($d['damage_number']) ?>
                                </a>
                                <?php if (!empty($d['photo_url'])): ?>
                                    <a href="<?= e($d['photo_url']) ?>" target="_blank" rel="noopener noreferrer" class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-1 text-decoration-none" title="View Cloudinary Photo Evidence">
                                        <i class="bi bi-image"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- SCANNED QR TAG -->
                        <!-- <td class="text-nowrap">
                            <?php 
                                $displayTag = $d['tag_code'] ?? '';
                                if (empty($displayTag) || str_contains($displayTag, '-') || str_contains($displayTag, 'SKU')) {
                                    $c1 = !empty($d['category_name']) ? strtoupper(substr(trim($d['category_name']), 0, 1)) : 'M';
                                    $c2 = !empty($d['subcategory_name']) ? strtoupper(substr(trim($d['subcategory_name']), 0, 1)) : 'T';
                                    $c3 = !empty($d['sub_subcategory_name']) ? strtoupper(substr(trim($d['sub_subcategory_name']), 0, 1)) : 'P';
                                    $mmyy = date('my', !empty($d['created_at']) ? strtotime($d['created_at']) : time());
                                    $displayTag = $c1 . $c2 . $c3 . $mmyy . '01';
                                }
                            ?>
                            <?php if (!empty($displayTag)): ?>
                                <button type="button" class="btn btn-sm btn-dark font-monospace text-warning py-1 px-2.5 rounded-2 d-inline-flex align-items-center gap-1 shadow-sm btn-view-tag" 
                                        data-tag="<?= e($displayTag) ?>"
                                        data-sku="<?= e($d['sku']) ?>"
                                        data-qr="<?= e(!empty($d['qr_code']) ? $d['qr_code'] : $d['sku']) ?>"
                                        data-code="<?= e($d['product_code'] ?? '') ?>"
                                        data-name="<?= e($d['product_name']) ?>"
                                        data-price="<?= e($d['mrp'] ?? $d['selling_price'] ?? 0) ?>"
                                        data-cost="<?= e($d['cost_per_unit'] ?? $d['purchase_price'] ?? $d['selling_price'] ?? 0) ?>"
                                        data-size="<?= e($d['size_name'] ?? 'Std') ?>"
                                        data-color="<?= e($d['color_name'] ?? 'Std') ?>"
                                        data-category="<?= e($d['category_name'] ?? '') ?>"
                                        title="Click to inspect detailed 2D QR Code & Garment Label">
                                    <i class="bi bi-qr-code text-warning"></i>
                                    <span><?= e($displayTag) ?></span>
                                </button>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border text-nowrap"><i class="bi bi-dash me-1"></i>No Tag</span>
                            <?php endif; ?>
                        </td> -->

                        <!-- PRODUCT & VARIANT -->
                        <td>
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div>
                                    <div class="fw-bold text-dark mb-1" style="font-size: 0.92rem; line-height: 1.35; max-width: 260px;">
                                        <?= e($d['product_name']) ?>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                        <span class="badge bg-dark-subtle text-dark-emphasis font-monospace border small px-2 py-0.5"><?= e($d['sku']) ?></span>
                                        <span class="badge bg-light text-secondary border small px-2 py-0.5">
                                            <?= e($d['size_name'] ?? 'Free') ?> / <?= e($d['color_name'] ?? 'Std') ?>
                                        </span>
                                        <?php if (!empty($d['qr_code']) && $d['qr_code'] !== $d['sku']): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle small font-monospace px-1.5 py-0.5" title="Product Variant QR Code">
                                                <i class="bi bi-qr-code me-1"></i><?= e($d['qr_code']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-1.5 rounded-2 btn-view-tag"
                                        data-tag="<?= e($displayTag) ?>"
                                        data-sku="<?= e($d['sku']) ?>"
                                        data-qr="<?= e(!empty($d['qr_code']) ? $d['qr_code'] : $d['sku']) ?>"
                                        data-code="<?= e($d['product_code'] ?? '') ?>"
                                        data-name="<?= e($d['product_name']) ?>"
                                        data-price="<?= e($d['mrp'] ?? $d['selling_price'] ?? 0) ?>"
                                        data-cost="<?= e($d['cost_per_unit'] ?? $d['purchase_price'] ?? $d['selling_price'] ?? 0) ?>"
                                        data-size="<?= e($d['size_name'] ?? 'Std') ?>"
                                        data-color="<?= e($d['color_name'] ?? 'Std') ?>"
                                        data-category="<?= e($d['category_name'] ?? '') ?>"
                                        title="Inspect Product QR">
                                    <i class="bi bi-eye"></i>
                                </button> -->
                            </div>
                        </td>

                        <!-- WAREHOUSE -->
                        <td class="text-nowrap">
                            <span class="badge bg-dark text-white border border-dark px-2.5 py-1.5 rounded-2 fw-semibold d-inline-flex align-items-center gap-1 shadow-2xs" style="background-color: #0f172a !important; color: #f8fafc !important;">
                                <i class="bi bi-building text-warning"></i>
                                <span><?= e($d['warehouse_name']) ?></span>
                            </span>
                        </td>

                        <!-- SOURCE & ORIGIN BATCH -->
                        <td>
                            <?php if ($d['source_type'] === 'PRODUCTION_INWARD' || !empty($d['batch_number'])): ?>
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle small px-2 py-0.5">
                                            <i class="bi bi-box-arrow-in-down me-1"></i>Inward GRN
                                        </span>
                                        <?php if (!empty($d['batch_id']) && hasPermission('inward', 'view')): ?>
                                            <a href="<?= url('warehouse/inward/view/' . $d['batch_id']) ?>" class="badge bg-info-subtle text-info-emphasis border border-info-subtle text-decoration-none font-monospace small px-2 py-0.5" title="View Inward Batch Verification">
                                                <i class="bi bi-box-seam me-1"></i><?= e($d['batch_number']) ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted d-flex align-items-center gap-1">
                                        <i class="bi bi-gear-wide-connected text-warning"></i>
                                        <span class="fw-semibold text-dark"><?= e($d['production_unit_code'] ?? 'PU1') ?></span>
                                        <span class="text-truncate" style="max-width: 140px;" title="<?= e($d['production_unit_name'] ?? '') ?>"><?= e($d['production_unit_name'] ?? 'Main Facility') ?></span>
                                    </div>
                                    <div class="small text-danger text-truncate" style="max-width: 200px;" title="<?= e($d['damage_reason']) ?>">
                                        <i class="bi bi-exclamation-triangle me-1"></i><?= e($d['damage_reason']) ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border small d-inline-block mb-1 text-nowrap">
                                    <?= e($d['source_type']) ?>
                                </span>
                                <div class="small text-muted text-truncate" style="max-width: 180px;" title="<?= e($d['damage_reason']) ?>">
                                    <i class="bi bi-info-circle me-1 text-danger"></i><?= e($d['damage_reason']) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- QUANTITY -->
                        <td class="text-center text-nowrap">
                            <span class="badge <?= $statusVal === 'REWORK' ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> fs-7 px-2.5 py-1 fw-bold">
                                <?= number_format($d['quantity']) ?> pcs
                            </span>
                        </td>

                        <!-- LOSS / VALUATION -->
                        <td class="text-nowrap">
                            <?php 
                                $effectiveUnitCost = (float)($d['cost_per_unit'] ?? 0);
                                if ($effectiveUnitCost <= 0) {
                                    $effectiveUnitCost = (float)(
                                        (!empty($d['selling_price']) && (float)$d['selling_price'] > 0) ? $d['selling_price'] :
                                        ((!empty($d['mrp']) && (float)$d['mrp'] > 0) ? $d['mrp'] :
                                        ((!empty($d['production_cost']) && (float)$d['production_cost'] > 0) ? $d['production_cost'] :
                                        ((!empty($d['purchase_price']) && (float)$d['purchase_price'] > 0) ? $d['purchase_price'] : 0.0)))
                                    );
                                }
                                $effectiveLoss = (float)($d['total_loss'] ?? 0);
                                if ($effectiveLoss <= 0 && $effectiveUnitCost > 0) {
                                    $effectiveLoss = (int)($d['quantity'] ?? 1) * $effectiveUnitCost;
                                }
                            ?>
                            <div class="fw-bold text-dark fs-6">₹<?= number_format($effectiveLoss, 2) ?></div>
                            <small class="text-muted">@ ₹<?= number_format($effectiveUnitCost, 2) ?>/pc</small>
                        </td>

                        <!-- STATUS -->
                        <td class="text-nowrap">
                            <?php if ($statusVal === 'LOGGED'): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-clock-history"></i> LOGGED
                                </span>
                            <?php elseif ($statusVal === 'REWORK'): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1.5 rounded-pill fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-arrow-repeat"></i> REWORK
                                </span>
                            <?php elseif ($statusVal === 'INSPECTED'): ?>
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1.5 rounded-pill fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-search"></i> INSPECTED
                                </span>
                            <?php elseif ($statusVal === 'SALVAGED'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-recycle"></i> SALVAGED
                                </span>
                            <?php elseif ($statusVal === 'REPAIRED'): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 rounded-pill fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-tools"></i> REPAIRED
                                </span>
                            <?php elseif ($statusVal === 'WRITTEN_OFF'): ?>
                                <span class="badge bg-dark-subtle text-dark border border-dark-subtle px-2.5 py-1.5 rounded-pill fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-x-circle"></i> WRITTEN OFF
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1.5 rounded-pill fw-semibold text-nowrap">
                                    <?= e($statusVal) ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- DATE -->
                        <td class="text-nowrap">
                            <div class="fw-semibold text-dark small"><?= date('d M Y', strtotime($d['created_at'])) ?></div>
                            <small class="text-muted d-block" style="max-width: 140px; overflow: hidden; text-overflow: ellipsis;">
                                <i class="bi bi-person me-1"></i><?= e($d['reported_by_name'] ?? 'Staff') ?>
                            </small>
                        </td>

                        <!-- ACTIONS -->
                        <td class="pe-3 text-end text-nowrap">
                            <a href="<?= url('warehouse/damage/view/' . $d['id']) ?>" class="btn btn-sm btn-outline-primary px-2.5 py-1 shadow-sm d-inline-flex align-items-center gap-1">
                                <i class="bi bi-eye"></i>
                                <span>View</span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- PIECE & PRODUCT 2D QR CODE TAG INSPECTION MODAL -->
<div class="modal fade" id="tagInspectModal" tabindex="-1" aria-labelledby="tagInspectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-qr-code-scan fs-5 text-warning"></i>
                    <div>
                        <h5 class="modal-title fs-6 fw-bold mb-0" id="tagInspectModalLabel">Garment 2D QR Tag &amp; Product Details</h5>
                        <small class="text-white-50" style="font-size: 0.75rem;">Piece-level traceability, SKU payload &amp; pricing breakdown</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Mode Switcher Tabs -->
                <div class="d-flex justify-content-center mb-3">
                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                        <button type="button" class="btn btn-primary active fw-semibold" id="btnModePieceTag" onclick="switchInspectMode('piece')">
                            <i class="bi bi-tag-fill me-1"></i> Piece QR Tag
                        </button>
                        <button type="button" class="btn btn-outline-secondary fw-semibold" id="btnModeProductQr" onclick="switchInspectMode('product')">
                            <i class="bi bi-upc-scan me-1"></i> Product Variant QR
                        </button>
                    </div>
                </div>

                <!-- Realistic Garment Label Preview Card -->
                <div class="card border mx-auto shadow-sm p-3 mb-3 rounded-3 bg-white" style="max-width: 320px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                    <div class="d-flex align-items-start gap-3">
                        <div class="text-center flex-shrink-0">
                            <div class="bg-white p-1 rounded border shadow-2xs d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                <div id="modalQrCodeContainer"></div>
                            </div>
                            <div class="badge bg-dark text-warning font-monospace mt-1 px-2 py-1 w-100" id="modalTagCode" style="font-size: 0.78rem; letter-spacing: 0.5px;">TAG CODE</div>
                        </div>
                        <div class="overflow-hidden flex-grow-1 text-start" style="margin-top: 2px;">
                            <div class="fw-bold text-uppercase text-dark text-truncate" id="modalBrandHeader" style="font-size: 0.85rem; letter-spacing: 0.3px;">GRM WAREHOUSE</div>
                            <div class="fw-bold text-dark text-truncate small mb-1" id="modalProductName">Product Title</div>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <span id="modalSku" class="badge bg-dark-subtle text-dark font-monospace" style="font-size: 0.72rem;">SKU</span>
                                <span id="modalCategory" class="badge bg-light text-primary border" style="font-size: 0.72rem;">General</span>
                            </div>
                            <div class="fw-semibold text-secondary small mb-1" id="modalVariant">Size / Color</div>
                            <div class="text-muted small text-decoration-line-through" id="modalMRP" style="font-size: 0.76rem;">MRP: ₹0.00</div>
                            <div class="fw-bold text-success fs-6" id="modalDiscPrice">Disc: ₹0.00</div>
                        </div>
                    </div>
                </div>

                <!-- Scannable Payload String Box -->
                <div class="p-2.5 bg-white rounded border text-start mb-2 shadow-2xs">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold text-muted" style="font-size: 0.75rem;"><i class="bi bi-code-square me-1"></i>Encoded QR Payload</span>
                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" onclick="copyInspectQrPayload()" style="font-size: 0.72rem;">
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                    </div>
                    <code class="d-block font-monospace text-dark text-break small p-1 bg-light rounded" id="modalQrPayloadText" style="font-size: 0.75rem;">-</code>
                </div>

                <div class="text-muted small text-center" style="font-size: 0.75rem;">
                    <i class="bi bi-info-circle me-1 text-primary"></i>Scannable by 2D barcode scanner guns &amp; mobile cameras.
                </div>
            </div>
            <div class="modal-footer bg-white py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentInspectData = null;
let currentInspectMode = 'piece'; // 'piece' or 'product'

function renderInspectQR() {
    if (!currentInspectData) return;
    const modalQrCodeContainer = document.getElementById('modalQrCodeContainer');
    const modalTagCode = document.getElementById('modalTagCode');
    const modalQrPayloadText = document.getElementById('modalQrPayloadText');
    const btnPiece = document.getElementById('btnModePieceTag');
    const btnProd = document.getElementById('btnModeProductQr');

    let qrText = '';
    let displayTag = '';

    const price = parseFloat(currentInspectData.price) || 0;
    const cost = parseFloat(currentInspectData.cost) || price;
    const fmtMrp = price > 0 ? price.toFixed(2) : '—';
    const fmtDisc = cost > 0 ? cost.toFixed(2) : '—';

    if (currentInspectMode === 'product') {
        qrText = currentInspectData.qr || currentInspectData.sku;
        displayTag = currentInspectData.qr || currentInspectData.sku;
        if (btnPiece) btnPiece.className = 'btn btn-outline-secondary fw-semibold';
        if (btnProd) btnProd.className = 'btn btn-primary active fw-semibold';
    } else {
        qrText = currentInspectData.tag;
        displayTag = currentInspectData.tag;
        if (btnPiece) btnPiece.className = 'btn btn-primary active fw-semibold';
        if (btnProd) btnProd.className = 'btn btn-outline-secondary fw-semibold';
    }

    if (modalTagCode) modalTagCode.innerText = displayTag;
    if (modalQrPayloadText) modalQrPayloadText.innerText = qrText;

    if (modalQrCodeContainer && typeof QRCode !== 'undefined') {
        modalQrCodeContainer.innerHTML = '';
        new QRCode(modalQrCodeContainer, {
            text: qrText,
            width: 90,
            height: 90,
            colorDark : "#0f172a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });
    }
}

function switchInspectMode(mode) {
    currentInspectMode = mode;
    renderInspectQR();
}

function copyInspectQrPayload() {
    const textEl = document.getElementById('modalQrPayloadText');
    const text = textEl ? textEl.innerText.trim() : '';
    if (!text || text === '-') return;

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            alert('✅ Copied QR Payload:\n' + text);
        }).catch(() => {
            prompt('Copy QR Payload:', text);
        });
    } else {
        prompt('Copy QR Payload:', text);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const tagButtons = document.querySelectorAll('.btn-view-tag');
    const tagInspectModalEl = document.getElementById('tagInspectModal');
    const tagInspectModal = tagInspectModalEl ? new bootstrap.Modal(tagInspectModalEl) : null;
    
    const modalProductName = document.getElementById('modalProductName');
    const modalSku = document.getElementById('modalSku');
    const modalVariant = document.getElementById('modalVariant');
    const modalCategory = document.getElementById('modalCategory');
    const modalMRP = document.getElementById('modalMRP');
    const modalDiscPrice = document.getElementById('modalDiscPrice');
    
    tagButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            currentInspectData = {
                tag: this.dataset.tag || '',
                sku: this.dataset.sku || '',
                qr: this.dataset.qr || this.dataset.sku || '',
                name: this.dataset.name || 'Product',
                price: parseFloat(this.dataset.price) || 0,
                cost: parseFloat(this.dataset.cost) || 0,
                size: this.dataset.size || 'Free',
                color: this.dataset.color || 'Std',
                category: this.dataset.category || 'General'
            };
            
            currentInspectMode = 'piece';

            if (modalProductName) modalProductName.innerText = currentInspectData.name;
            if (modalSku) modalSku.innerText = currentInspectData.sku;
            if (modalVariant) modalVariant.innerText = currentInspectData.size + ' / ' + currentInspectData.color;
            if (modalCategory) modalCategory.innerText = currentInspectData.category;
            
            const mrpVal = currentInspectData.price > 0 ? currentInspectData.price.toFixed(2) : '0.00';
            const discVal = currentInspectData.cost > 0 ? currentInspectData.cost.toFixed(2) : mrpVal;
            
            if (modalMRP) modalMRP.innerText = 'MRP: ₹' + mrpVal;
            if (modalDiscPrice) modalDiscPrice.innerText = 'Disc: ₹' + discVal;
            
            renderInspectQR();
            if (tagInspectModal) tagInspectModal.show();
        });
    });
});
</script>
