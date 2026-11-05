<?php
// views/whatsapp_orders/damage/index.php — WhatsApp Channel Damage Management
$canCreateDamage = hasPermission('whatsapp_damage', 'create');
?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="mb-0 fw-bold text-dark"><i class="bi bi-exclamation-octagon me-2 text-danger"></i>WhatsApp Damage Management</h2>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill fw-semibold">
                <i class="bi bi-shield-slash me-1"></i>Defect Quarantine &amp; Return Outwards
            </span>
        </div>
        <span class="text-muted small">Manage transit-damaged inward garments, defective stock quarantine, and batch outwards back to central warehouse.</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($canCreateDamage): ?>
        <a href="<?= url('warehouse/whatsapp-orders/damage/create') ?>" class="btn btn-danger rounded-pill px-3 shadow-sm fw-semibold">
            <i class="bi bi-plus-lg me-1"></i> Log Damaged Stock
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- 4 KPI Summary Cards -->
<div class="row g-3 mb-4">
    <!-- 1. Total Damaged Units -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Total Damaged Units</span>
                    <div class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center text-danger" style="width: 44px; height: 44px;">
                        <i class="bi bi-slash-circle-fill fs-5"></i>
                    </div>
                </div>
                <div class="fs-3 fw-bold text-danger"><?= number_format((int)($stats['total_damaged_units'] ?? 0)) ?> <span class="fs-6 text-muted fw-normal">pcs</span></div>
                <div class="small text-muted mt-1"><i class="bi bi-journal-text me-1 text-danger"></i> Across <?= number_format((int)($stats['total_records'] ?? 0)) ?> incident logs</div>
            </div>
        </div>
    </div>

    <!-- 2. Inward Transit Damages -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Inward Transit Defects</span>
                    <div class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center text-warning-emphasis" style="width: 44px; height: 44px;">
                        <i class="bi bi-truck fs-4"></i>
                    </div>
                </div>
                <div class="fs-3 fw-bold text-dark"><?= number_format((int)($stats['transit_damaged_units'] ?? 0)) ?> <span class="fs-6 text-muted fw-normal">pcs</span></div>
                <div class="small text-muted mt-1"><i class="bi bi-box-arrow-in-down me-1 text-warning"></i> Auto-captured during inward scan</div>
            </div>
        </div>
    </div>

    <!-- 3. Quarantined in Channel -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Quarantined On-Hand</span>
                    <div class="rounded-circle bg-info-subtle d-flex align-items-center justify-content-center text-info-emphasis" style="width: 44px; height: 44px;">
                        <i class="bi bi-archive-fill fs-5"></i>
                    </div>
                </div>
                <div class="fs-3 fw-bold text-info-emphasis"><?= number_format((int)($stats['quarantined_units'] ?? 0)) ?> <span class="fs-6 text-muted fw-normal">pcs</span></div>
                <div class="small text-muted mt-1"><i class="bi bi-hourglass-split me-1 text-info"></i> Ready for outward return</div>
            </div>
        </div>
    </div>

    <!-- 4. Dispatched to Warehouse -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Dispatched to Warehouse</span>
                    <div class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center text-success" style="width: 44px; height: 44px;">
                        <i class="bi bi-send-check-fill fs-5"></i>
                    </div>
                </div>
                <div class="fs-3 fw-bold text-success"><?= number_format((int)($stats['outward_dispatched_units'] ?? 0)) ?> <span class="fs-6 text-muted fw-normal">pcs</span></div>
                <div class="small text-muted mt-1"><i class="bi bi-check2-all me-1 text-success"></i> Returned via Outward Batches</div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('warehouse/whatsapp-orders/damage') ?>" class="row g-2 align-items-center" id="damageFilterForm">
            <!-- Search Input -->
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" id="damageSearchInput" class="form-control bg-light border-start-0" 
                           placeholder="Search product, SKU, tag, reason..." 
                           value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-md-3">
                <select name="status" class="form-select bg-light" onchange="this.form.submit()">
                    <option value="ALL" <?= ($filters['status'] ?? '') === 'ALL' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="LOGGED" <?= ($filters['status'] ?? '') === 'LOGGED' ? 'selected' : '' ?>>LOGGED (Quarantined)</option>
                    <option value="INSPECTED" <?= ($filters['status'] ?? '') === 'INSPECTED' ? 'selected' : '' ?>>INSPECTED</option>
                    <option value="OUTWARD_BATCHED" <?= ($filters['status'] ?? '') === 'OUTWARD_BATCHED' ? 'selected' : '' ?>>OUTWARD BATCHED</option>
                    <option value="DISPATCHED" <?= ($filters['status'] ?? '') === 'DISPATCHED' ? 'selected' : '' ?>>DISPATCHED</option>
                    <option value="RECEIVED_AT_WH" <?= ($filters['status'] ?? '') === 'RECEIVED_AT_WH' ? 'selected' : '' ?>>RECEIVED AT WAREHOUSE</option>
                </select>
            </div>

            <!-- Defect Type Filter -->
            <div class="col-md-3">
                <select name="defect_type" class="form-select bg-light" onchange="this.form.submit()">
                    <option value="ALL">All Defect Types</option>
                    <option value="TRANSIT_DAMAGE" <?= ($filters['defect_type'] ?? '') === 'TRANSIT_DAMAGE' ? 'selected' : '' ?>>Transit Damage</option>
                    <option value="FABRIC_DEFECT" <?= ($filters['defect_type'] ?? '') === 'FABRIC_DEFECT' ? 'selected' : '' ?>>Fabric Defect</option>
                    <option value="COLOR_FADE" <?= ($filters['defect_type'] ?? '') === 'COLOR_FADE' ? 'selected' : '' ?>>Color Fade</option>
                    <option value="STITCHING_ERROR" <?= ($filters['defect_type'] ?? '') === 'STITCHING_ERROR' ? 'selected' : '' ?>>Stitching Error</option>
                    <option value="PACKAGING_DAMAGE" <?= ($filters['defect_type'] ?? '') === 'PACKAGING_DAMAGE' ? 'selected' : '' ?>>Packaging Damage</option>
                    <option value="WATER_STAIN" <?= ($filters['defect_type'] ?? '') === 'WATER_STAIN' ? 'selected' : '' ?>>Water Stain</option>
                    <option value="OTHER" <?= ($filters['defect_type'] ?? '') === 'OTHER' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>

            <!-- Source Type Filter -->
            <div class="col-md-2">
                <select name="source_type" class="form-select bg-light" onchange="this.form.submit()">
                    <option value="ALL">All Sources</option>
                    <option value="INWARD_TRANSIT" <?= ($filters['source_type'] ?? '') === 'INWARD_TRANSIT' ? 'selected' : '' ?>>Inward Transit</option>
                    <option value="STORAGE_HANDLING" <?= ($filters['source_type'] ?? '') === 'STORAGE_HANDLING' ? 'selected' : '' ?>>Storage / Handling</option>
                    <option value="CUSTOMER_RETURN" <?= ($filters['source_type'] ?? '') === 'CUSTOMER_RETURN' ? 'selected' : '' ?>>Customer Return</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary rounded-3 w-100" title="Apply Filter">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                <?php if (!empty($filters['search']) || ($filters['status'] ?? 'ALL') !== 'ALL' || ($filters['defect_type'] ?? 'ALL') !== 'ALL' || ($filters['source_type'] ?? 'ALL') !== 'ALL'): ?>
                    <a href="<?= url('warehouse/whatsapp-orders/damage') ?>" class="btn btn-light border rounded-3" title="Clear Filters">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Damaged Products Table -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-list-check me-2 text-danger"></i>Damaged Garments Register</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger-subtle text-danger fw-semibold px-3 py-2 rounded-pill">
                Showing <?= count($damages) ?> records
            </span>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th class="ps-4" style="width: 4%;">#</th>
                    <th style="width: 14%;">Damage Ref #</th>
                    <th style="width: 22%;">Garment Product Details</th>
                    <th style="width: 16%;">Piece QR Serial Tag</th>
                    <th style="width: 14%;">Defect &amp; Reason</th>
                    <th class="text-center" style="width: 6%;">Qty</th>
                    <th class="text-end" style="width: 9%;">Loss Value</th>
                    <th class="text-center" style="width: 8%;">Status</th>
                    <th class="text-end pe-4" style="width: 7%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($damages)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <div class="py-4">
                                <i class="bi bi-shield-check fs-1 text-success d-block mb-2"></i>
                                <h6 class="fw-bold text-dark">No Damaged Stock Found</h6>
                                <p class="small text-muted mb-0">All WhatsApp channel inward stock is intact with 0 quarantine defects.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($damages as $idx => $d): ?>
                        <tr>
                            <td class="ps-4 text-muted small fw-bold"><?= $idx + 1 ?></td>
                            <td>
                                <a href="<?= url('warehouse/whatsapp-orders/damage/view/' . $d['id']) ?>" class="fw-bold font-monospace text-danger text-decoration-none small d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-exclamation-octagon"></i> <?= e($d['damage_number']) ?>
                                </a>
                                <div class="text-muted" style="font-size: 10px;">
                                    <?= date('d M Y, h:i A', strtotime($d['created_at'])) ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($d['image'])): ?>
                                        <img src="<?= e($d['image']) ?>" alt="" class="rounded-3 border me-2 flex-shrink-0" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-3 bg-light border me-2 flex-shrink-0 d-flex align-items-center justify-content-center text-secondary" style="width: 40px; height: 40px;">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold text-dark small text-truncate" style="max-width: 200px;" title="<?= e($d['product_name']) ?>">
                                            <?= e($d['product_name']) ?>
                                        </div>
                                        <div class="text-muted font-monospace" style="font-size: 11px;">
                                            <span class="fw-semibold text-primary"><?= e($d['sku']) ?></span>
                                            <?= !empty($d['size_name']) ? ' • ' . e($d['size_name']) : '' ?>
                                            <?= !empty($d['color_name']) ? ' • ' . e($d['color_name']) : '' ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($d['resolved_tags'])): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($d['resolved_tags'] as $tag): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace" style="font-size: 10px;">
                                                <i class="bi bi-qr-code me-1"></i><?= e($tag) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border font-monospace" style="font-size: 10px;">
                                        <?= e($d['sku']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-danger text-white rounded-pill px-2 py-1" style="font-size: 10px;">
                                    <?= str_replace('_', ' ', e($d['defect_type'])) ?>
                                </span>
                                <div class="text-muted small text-truncate mt-1" style="font-size: 11px; max-width: 160px;" title="<?= e($d['damage_reason']) ?>">
                                    <?= e($d['damage_reason']) ?>
                                </div>
                                <?php if (!empty($d['allocation_number'])): ?>
                                    <div class="font-monospace text-primary" style="font-size: 10px;">
                                        <i class="bi bi-box-arrow-in-down me-1"></i><?= e($d['allocation_number']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger-subtle text-danger rounded-pill px-3 fw-bold"><?= $d['quantity'] ?></span>
                            </td>
                            <td class="text-end fw-bold text-danger font-monospace">
                                ₹<?= number_format((float)$d['total_loss'], 2) ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $stBadge = match($d['status']) {
                                        'LOGGED'          => 'bg-danger text-white',
                                        'INSPECTED'       => 'bg-warning text-dark',
                                        'OUTWARD_BATCHED' => 'bg-info text-dark',
                                        'DISPATCHED'      => 'bg-primary text-white',
                                        'RECEIVED_AT_WH'  => 'bg-success text-white',
                                        default           => 'bg-secondary text-white'
                                    };
                                ?>
                                <span class="badge <?= $stBadge ?> rounded-pill px-2 py-1 font-monospace" style="font-size: 10px;">
                                    <?= str_replace('_', ' ', e($d['status'])) ?>
                                </span>
                                <?php if (!empty($d['outward_batch_number'])): ?>
                                    <small class="d-block text-muted font-monospace mt-1" style="font-size: 9.5px;">
                                        <?= e($d['outward_batch_number']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4" style="white-space: nowrap;">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?= url('warehouse/whatsapp-orders/damage/view/' . $d['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2" title="View Full Damage Details">
                                        <i class="bi bi-eye-fill"></i>
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
