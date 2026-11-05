<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-arrow-return-left me-2 text-danger"></i>Warehouse Returns &amp; Quality Control</h2>
       
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (hasPermission('warehouse', 'view')): ?>
            <a href="<?= url('warehouse/dashboard') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
        <?php endif; ?>
        <?php if (hasPermission('damage', 'view')): ?>
            <a href="<?= url('warehouse/damage') ?>" class="btn btn-outline-danger">
                <i class="bi bi-exclamation-octagon me-1"></i> Warehouse Damage
            </a>
        <?php endif; ?>
        <?php if (hasPermission('wh_labels', 'view')): ?>
            <a href="<?= url('warehouse/labels?tab=return_tags') ?>" class="btn btn-outline-primary">
                <i class="bi bi-qr-code me-1"></i> Return QR Suite
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-danger">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(220, 53, 69, 0.12); color: #dc3545;">
                    <i class="bi bi-box-arrow-in-left fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Return Batches</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($stats['total_batches'] ?? count($returns ?? [])) ?></h4>
                    <small class="text-muted"><?= number_format($stats['total_units'] ?? 0) ?> total returned pcs</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(25, 135, 84, 0.15); color: #198754;">
                    <i class="bi bi-whatsapp fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">WhatsApp Return Batches</div>
                    <h4 class="fw-bold mb-0 text-success"><?= number_format($stats['whatsapp_batches'] ?? 0) ?></h4>
                    <small class="text-muted">Damaged outward batches</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-primary">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(13, 110, 253, 0.12); color: #0d6efd;">
                    <i class="bi bi-shop fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Shop Return Batches</div>
                    <h4 class="fw-bold mb-0 text-primary"><?= number_format($stats['shop_batches'] ?? 0) ?></h4>
                    <small class="text-muted">Store returns to warehouse</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(13, 202, 240, 0.15); color: #0891b2;">
                    <i class="bi bi-globe fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Website Returns</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($stats['website_batches'] ?? 0) ?></h4>
                    <small class="text-muted">Online returns &amp; RMA</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('warehouse/returns') ?>" class="row g-2 align-items-end">
            <!-- Search Keyword -->
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-semibold text-secondary mb-1">Search Batch / Reference</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Return #, Shop, WhatsApp, Reason..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
            </div>

            <!-- Source Type Filter -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold text-secondary mb-1">Source Channel / Shop</label>
                <select name="source_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ALL">-- All Sources --</option>
                    <option value="WHATSAPP" <?= ($filters['source_type'] ?? '') === 'WHATSAPP' ? 'selected' : '' ?>>WhatsApp Orders</option>
                    <option value="SHOP" <?= ($filters['source_type'] ?? '') === 'SHOP' ? 'selected' : '' ?>>Retail Shops</option>
                    <option value="WEBSITE" <?= ($filters['source_type'] ?? '') === 'WEBSITE' ? 'selected' : '' ?>>Website Orders</option>
                </select>
            </div>

            <!-- Receiving Warehouse Filter -->
            <div class="col-lg-3 col-md-3 col-6">
                <label class="form-label small fw-semibold text-secondary mb-1">Receiving Warehouse</label>
                <select name="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()" <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                    <?php if (!function_exists('currentWarehouseId') || currentWarehouseId() === null && count($warehouses) > 1): ?>
                        <option value="ALL">-- All Warehouses --</option>
                    <?php endif; ?>
                    <?php if (!empty($warehouses)): foreach ($warehouses as $wh): ?>
                        <option value="<?= $wh['id'] ?>" <?= (((string)($filters['warehouse_id'] ?? '') === (string)$wh['id']) || (function_exists('currentWarehouseId') && currentWarehouseId() == $wh['id'])) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wh['name']) ?><?= !empty($wh['code']) ? ' (' . htmlspecialchars($wh['code']) . ')' : '' ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
                <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1): ?>
                    <input type="hidden" name="warehouse_id" value="<?= htmlspecialchars((string)($filters['warehouse_id'] ?? $warehouses[0]['id'] ?? (function_exists('currentWarehouseId') ? currentWarehouseId() : ''))) ?>">
                <?php endif; ?>
            </div>

            <!-- Status Filter -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold text-secondary mb-1">QC Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ALL">-- All Statuses --</option>
                    <option value="RECEIVED" <?= ($filters['status'] ?? '') === 'RECEIVED' ? 'selected' : '' ?>>RECEIVED (Awaiting QC)</option>
                    <option value="INSPECTING" <?= ($filters['status'] ?? '') === 'INSPECTING' ? 'selected' : '' ?>>INSPECTING</option>
                    <option value="CLASSIFIED" <?= ($filters['status'] ?? '') === 'CLASSIFIED' ? 'selected' : '' ?>>CLASSIFIED (Credited)</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-lg-1 col-md-3 col-6">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold" title="Filter Records">
                        <i class="bi bi-funnel"></i>
                    </button>
                    <?php if (!empty($filters['search']) || ($filters['source_type'] ?? 'ALL') !== 'ALL' || ($filters['warehouse_id'] ?? 'ALL') !== 'ALL' || ($filters['status'] ?? 'ALL') !== 'ALL'): ?>
                        <a href="<?= url('warehouse/returns') ?>" class="btn btn-sm btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Return Receipts Table -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white overflow-hidden">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <div>
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-arrow-return-left me-2 text-danger"></i>Incoming Return Receipts &amp; Damage Batches</h5>
            
        </div>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 fw-semibold">
            Total Returned: <?= number_format($stats['total_units'] ?? 0) ?> pcs
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 170px;">Return Batch #</th>
                    <th>Origin Source (Shop / Channel)</th>
                    <th>Destination Warehouse</th>
                    <th class="text-center">Returned Qty</th>
                    <th>Quality / QC Breakdown</th>
                    <th class="text-center">Status</th>
                    <th class="text-end" style="width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <div class="fw-semibold">No return receipts or damage batches found.</div>
                            <small class="text-muted">When WhatsApp or retail shops dispatch damaged stock or returns, they will appear here for QC classification.</small>
                        </td>
                    </tr>
                <?php else: foreach ($returns as $ret): 
                    $srcType = strtoupper($ret['source_type'] ?? 'SHOP');
                    $isWhatsapp = ($srcType === 'WHATSAPP');
                    $isShop     = ($srcType === 'SHOP');
                    $isWebsite  = ($srcType === 'WEBSITE');
                    $st = strtoupper($ret['status'] ?? 'RECEIVED');
                    $isClassified = ($st === 'CLASSIFIED' || $st === 'COMPLETED');
                ?>
                    <tr>
                        <!-- Return Number & Date -->
                        <td>
                            <div class="fw-bold font-monospace <?= $isWhatsapp ? 'text-success' : ($isShop ? 'text-primary' : 'text-dark') ?>" style="font-size: 0.9rem;">
                                <?= htmlspecialchars($ret['return_number']) ?>
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                <?= date('d M Y, h:i A', strtotime($ret['created_at'])) ?>
                            </small>
                        </td>

                        <!-- Origin Source -->
                        <td>
                            <?php if ($isWhatsapp): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 mb-1">
                                    <i class="bi bi-whatsapp me-1"></i>WhatsApp Channel
                                </span>
                                <div class="fw-semibold text-dark small"><?= htmlspecialchars($ret['source_name'] ?? 'WhatsApp Orders Channel') ?></div>
                            <?php elseif ($isShop): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-1">
                                    <i class="bi bi-shop me-1"></i>Retail Store
                                </span>
                                <div class="fw-semibold text-dark small"><?= htmlspecialchars($ret['source_name'] ?? 'Shop Return') ?></div>
                            <?php else: ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 mb-1">
                                    <i class="bi bi-globe me-1"></i>Website Store
                                </span>
                                <div class="fw-semibold text-dark small"><?= htmlspecialchars($ret['source_name'] ?? 'Website Channel') ?></div>
                            <?php endif; ?>
                            <?php if (!empty($ret['reason'])): ?>
                                <div class="text-muted" style="font-size: 11px;">Reason: <?= htmlspecialchars($ret['reason']) ?></div>
                            <?php endif; ?>
                        </td>

                        <!-- Receiving Warehouse -->
                        <td>
                            <div class="p-1.5 rounded bg-light border d-inline-block">
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-building text-secondary"></i>
                                    <span class="fw-bold text-dark small"><?= htmlspecialchars($ret['warehouse_name'] ?? 'Main Central Warehouse') ?></span>
                                    <span class="badge bg-secondary font-monospace" style="font-size: 9.5px;"><?= htmlspecialchars($ret['warehouse_code'] ?? 'WH-01') ?></span>
                                </div>
                            </div>
                        </td>

                        <!-- Returned Quantity -->
                        <td class="text-center">
                            <div class="fw-bold fs-6 text-dark"><?= number_format($ret['total_returned_qty']) ?> <span class="fs-8 text-muted fw-normal">pcs</span></div>
                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 10px;">
                                <?= number_format($ret['item_count'] ?? 1) ?> variant<?= ($ret['item_count'] ?? 1) == 1 ? '' : 's' ?>
                            </span>
                        </td>

                        <!-- Quality Breakdown -->
                        <td class="small">
                            <?php
                                $good     = (int)($ret['total_good_qty']      ?? 0);
                                $damaged  = (int)($ret['total_damaged_qty']   ?? 0);
                                $defect   = (int)($ret['total_defective_qty'] ?? 0);
                                $rework   = (int)($ret['total_rework_qty']    ?? 0);
                                $parts = [];
                                if ($good    > 0) $parts[] = "<span class='badge bg-success font-monospace px-1.5 py-1'>{$good} Good</span>";
                                if ($damaged > 0) $parts[] = "<span class='badge bg-danger font-monospace px-1.5 py-1'>{$damaged} Damaged</span>";
                                if ($defect  > 0) $parts[] = "<span class='badge font-monospace px-1.5 py-1' style='background-color:#fd7e14; color:#fff;'>{$defect} Defective</span>";
                                if ($rework  > 0) $parts[] = "<span class='badge font-monospace px-1.5 py-1' style='background-color:#6f42c1; color:#fff;'>{$rework} Wrong Size</span>";
                                
                                if (!empty($parts)) {
                                    echo implode(' ', $parts);
                                } else {
                                    echo "<span class='badge bg-warning text-dark font-monospace'><i class='bi bi-hourglass-split me-1'></i>Awaiting QC Inspection</span>";
                                }
                            ?>
                        </td>

                        <!-- Status -->
                        <td class="text-center">
                            <span class="badge bg-<?= $isClassified ? 'success' : 'warning text-dark' ?> px-2.5 py-1 font-monospace">
                                <?= $isClassified ? '<i class="bi bi-check-circle-fill me-1"></i>' : '<i class="bi bi-clock-history me-1"></i>' ?>
                                <?= htmlspecialchars($st) ?>
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="text-end">
                            <?php
                            $canInspect = hasPermission('returns', 'inspect');
                            $canViewReturn = hasPermission('returns', 'view');
                            ?>
                            <div class="d-flex justify-content-end gap-1">
                                <?php if ($canInspect): ?>
                                <a href="<?= url('warehouse/returns/inspect/' . $ret['id']) ?>" class="btn btn-sm <?= ($isClassified || !$canInspect) ? 'btn-outline-primary' : 'btn-primary' ?>" title="<?= ($isClassified || !$canInspect) ? 'View return batch QC details' : 'Inspect and classify return batch' ?>">
                                    <i class="bi <?= ($isClassified || !$canInspect) ? 'bi-eye' : 'bi-clipboard-check' ?> me-1"></i>
                                    <?= ($isClassified || !$canInspect) ? 'View QC' : 'Inspect &amp; Classify' ?>
                                </a>
                                <?php elseif ($canViewReturn): ?>
                                <a href="<?= url('warehouse/returns/view/' . $ret['id']) ?>" class="btn btn-sm btn-outline-primary" title="View return batch details">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
