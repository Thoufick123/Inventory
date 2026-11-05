<?php
// views/shop/damage/index.php — Shop Damaged Goods & Defect Quarantine Ledger
$activeStatus = $filters['status'] ?? '';
$activeSource = $filters['source_type'] ?? '';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-3 py-1 rounded-pill fw-semibold">
                <i class="bi bi-shop me-1"></i><?= e($shop['name']) ?>
            </span>
            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 font-monospace">
                <?= e($shop['code'] ?? 'SHOP-01') ?>
            </span>
        </div>
        <h2 class="mb-1 text-dark fw-bold"><i class="bi bi-exclamation-octagon me-2 text-danger"></i>Shop Damaged Stock &amp; Defect Management</h2>
        <span class="text-muted">Central quarantine register for transit damages from Inward GRN and shop floor / bin defect identification</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (hasPermission('shop_damage', 'create')): ?>
        <a href="<?= url('shop/damage/create') ?>" class="btn btn-danger rounded-pill shadow-sm px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-qr-code-scan fs-6"></i>
            <span>Scan &amp; Log Floor Damage</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- 1. KPI Summary Cards (Clickable to Filter) -->
<div class="row g-3 mb-4">
    <!-- Card 1: Damaged Units in Quarantine -->
    <div class="col-xl-3 col-sm-6">
        <a href="<?= url('shop/damage?status=LOGGED') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10 h-100 rounded-3 transition-hover border-start border-4 border-danger <?= $activeStatus === 'LOGGED' ? 'ring-2 ring-danger' : '' ?>">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold small d-block" style="font-size: 11px;">Quarantined Damaged Stock</span>
                        <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($stats['total_units'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                        <small class="text-danger fw-semibold"><i class="bi bi-filter me-1"></i><?= number_format($stats['pending_inspection'] ?? 0) ?> pending inspection</small>
                    </div>
                    <div class="bg-danger text-white rounded-3 p-3 shadow-sm">
                        <i class="bi bi-slash-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 2: Inward Transit Damages -->
    <div class="col-xl-3 col-sm-6">
        <a href="<?= url('shop/damage?source_type=INWARD_TRANSIT') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10 h-100 rounded-3 transition-hover border-start border-4 border-warning <?= $activeSource === 'INWARD_TRANSIT' ? 'ring-2 ring-warning' : '' ?>">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold small d-block" style="font-size: 11px;">Inward Transit Damages</span>
                        <h3 class="fw-bold text-warning-emphasis mb-0 mt-1"><?= number_format($stats['inward_damage_units'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                        <small class="text-warning-emphasis fw-semibold"><i class="bi bi-truck me-1"></i>From Warehouse Inward</small>
                    </div>
                    <div class="bg-warning text-dark rounded-3 p-3 shadow-sm">
                        <i class="bi bi-truck fs-4"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 3: Shop Bin / Floor Defect -->
    <div class="col-xl-3 col-sm-6">
        <a href="<?= url('shop/damage?source_type=SHOP_BIN_HANDLING') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm bg-primary bg-opacity-10 h-100 rounded-3 transition-hover border-start border-4 border-primary <?= $activeSource === 'SHOP_BIN_HANDLING' ? 'ring-2 ring-primary' : '' ?>">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold small d-block" style="font-size: 11px;">Shop Bin / Floor Damage</span>
                        <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($stats['bin_damage_units'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                        <small class="text-primary fw-semibold"><i class="bi bi-boxes me-1"></i>Identified in Shop Bins</small>
                    </div>
                    <div class="bg-primary text-white rounded-3 p-3 shadow-sm">
                        <i class="bi bi-shop-window fs-4"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 4: Financial Loss Valuation -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-dark bg-opacity-10 h-100 rounded-3 border-start border-4 border-dark">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-bold small d-block" style="font-size: 11px;">Estimated Loss Valuation</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1">₹<?= number_format($stats['total_valuation_loss'] ?? 0, 2) ?></h3>
                    <small class="text-muted"><i class="bi bi-shield-x me-1"></i>Total quarantine loss</small>
                </div>
                <div class="bg-dark text-white rounded-3 p-3 shadow-sm">
                    <i class="bi bi-currency-rupee fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2. Filters & Search Bar -->
<div class="card mb-4 border-0 shadow-sm rounded-3">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="<?= url('shop/damage') ?>" class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-search me-1"></i> Search Records</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Damage #, Allocation #, Tag, SKU..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-funnel me-1"></i> Damage Source</label>
                <select name="source_type" class="form-select">
                    <option value="">-- All Source Types --</option>
                    <option value="INWARD_TRANSIT" <?= ($filters['source_type'] ?? '') === 'INWARD_TRANSIT' ? 'selected' : '' ?>>🚚 Inward Transit Damage</option>
                    <option value="SHOP_BIN_HANDLING" <?= ($filters['source_type'] ?? '') === 'SHOP_BIN_HANDLING' ? 'selected' : '' ?>>🏬 Shop Bin / Floor Handling</option>
                    <option value="CUSTOMER_RETURN" <?= ($filters['source_type'] ?? '') === 'CUSTOMER_RETURN' ? 'selected' : '' ?>>↩️ Customer Return Defect</option>
                    <option value="EXPIRED_SPOILED" <?= ($filters['source_type'] ?? '') === 'EXPIRED_SPOILED' ? 'selected' : '' ?>>⏳ Expired / Soiled</option>
                    <option value="OTHER" <?= ($filters['source_type'] ?? '') === 'OTHER' ? 'selected' : '' ?>>📌 Other</option>
                </select>
            </div>
            <div class="col-lg-4 col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <?php if (!empty($filters['search']) || !empty($filters['source_type']) || !empty($filters['status'])): ?>
                    <a href="<?= url('shop/damage') ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- 3. Damage Records Table -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="card-title mb-0 fw-bold text-dark">
            <i class="bi bi-list-columns-reverse me-2 text-danger"></i>Damaged Stock Quarantine List (<?= count($damages) ?> Records)
        </h6>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-muted border">Shop: <?= e($shop['name']) ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th>Date &amp; Source</th>
                    <th>Product &amp; SKU</th>
                    <th>Piece Tag / QR</th>
                    <th>Defect Type &amp; Reason</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Est. Loss</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($damages)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="py-4">
                                <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-4 d-inline-flex mb-3 shadow-xs">
                                    <i class="bi bi-check-circle-fill fs-1 text-success"></i>
                                </div>
                                <h5 class="fw-bold text-dark">No Damaged Stock Found</h5>
                                <p class="text-muted small mb-3">There are currently no damaged or quarantined pieces recorded matching your filter criteria.</p>
                                <?php if (hasPermission('shop_damage', 'create')): ?>
                                <a href="<?= url('shop/damage/create') ?>" class="btn btn-sm btn-danger rounded-pill px-3">
                                    <i class="bi bi-plus-lg me-1"></i> Log Damaged Item
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($damages as $d): 
                        $status = $d['status'] ?? 'LOGGED';
                        $statusBadgeClass = match($status) {
                            'LOGGED'       => 'bg-danger text-white',
                            'INSPECTED'    => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                            'SALVAGED'     => 'bg-success text-white',
                            'WRITTEN_OFF'  => 'bg-dark text-white',
                            'REPAIRED'     => 'bg-teal text-white',
                            'RETURN_TO_WH' => 'bg-primary text-white',
                            'REWORK'       => 'bg-warning text-dark',
                            default        => 'bg-secondary text-white'
                        };

                        $source = $d['source_type'] ?? 'SHOP_BIN_HANDLING';
                        $sourceBadgeClass = match($source) {
                            'INWARD_TRANSIT'     => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                            'SHOP_BIN_HANDLING'  => 'bg-primary-subtle text-primary border border-primary-subtle',
                            'CUSTOMER_RETURN'    => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                            default              => 'bg-light text-dark border'
                        };
                        ?>
                        <tr>
                            <td>
                                <div class="small fw-semibold text-dark"><?= date('d M Y, h:i A', strtotime($d['created_at'])) ?></div>
                                <span class="badge <?= $sourceBadgeClass ?> mt-1" style="font-size: 0.7rem;">
                                    <?= e($d['source_type']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="min-w-0">
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 200px;" title="<?= e($d['product_name']) ?>">
                                            <?= e($d['product_name']) ?>
                                        </div>
                                        <div class="small font-monospace text-primary"><?= e($d['sku']) ?></div>
                                        <div class="text-muted" style="font-size: 0.72rem;">
                                            <?= e($d['size_name'] ?? 'Free') ?> / <?= e($d['color_name'] ?? 'Std') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $tagsList = !empty($d['piece_tags']) ? $d['piece_tags'] : ShopDamage::resolvePieceTags($d); 
                                ?>
                                <?php if (!empty($tagsList)): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($tagsList as $tagItem): ?>
                                            <span class="badge bg-dark font-monospace text-warning px-2.5 py-1.5 shadow-2xs fs-7" style="letter-spacing: 0.3px;">
                                                <i class="bi bi-qr-code me-1"></i><?= e($tagItem) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-danger small"><?= e($d['damage_reason']) ?></div>
                                <?php if (!empty($d['defect_type']) && $d['defect_type'] !== 'OTHER'): ?>
                                    <span class="badge bg-light text-secondary border mt-0.5" style="font-size: 0.68rem;"><?= e(str_replace('_', ' ', $d['defect_type'])) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($d['defect_notes'])): ?>
                                    <div class="text-muted small text-truncate mt-0.5" style="max-width: 180px;" title="<?= e($d['defect_notes']) ?>">
                                        <?= e($d['defect_notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle fs-7 px-2.5 py-1 fw-bold">
                                    <?= (int)$d['quantity'] ?> pcs
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold text-dark">₹<?= number_format($d['total_loss'] ?? 0, 2) ?></div>
                                <small class="text-muted">@ ₹<?= number_format($d['cost_per_unit'] ?? 0, 2) ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $statusBadgeClass ?> px-2.5 py-1.5 shadow-2xs">
                                    <?= e($d['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
