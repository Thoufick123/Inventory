<?php
    $pct = $program['planned_quantity'] > 0 ? round(($program['completed_quantity'] / $program['planned_quantity']) * 100) : 0;
    $statusClass = match($program['status']) {
        'DRAFT'       => 'bg-secondary',
        'APPROVED'    => 'bg-info text-dark',
        'SCHEDULED'   => 'bg-primary',
        'IN_PROGRESS' => 'bg-warning text-dark',
        'COMPLETED'   => 'bg-success',
        'HOLD'        => 'bg-danger',
        default       => 'bg-dark'
    };
    $prioClass = match($program['priority']) {
        'URGENT' => 'bg-danger',
        'HIGH'   => 'bg-warning text-dark',
        'MEDIUM' => 'bg-info text-dark',
        default  => 'bg-secondary'
    };
?>

<?php
    $catParts = [];
    if (!empty($program['category_name'])) $catParts[] = $program['category_name'];
    if (!empty($program['subcategory_name'])) $catParts[] = $program['subcategory_name'];
    if (!empty($program['sub_subcategory_name'])) $catParts[] = $program['sub_subcategory_name'];
    $catBreadcrumb = !empty($catParts) ? implode(' › ', $catParts) : ($program['product_name'] ?? '-');
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="mb-0 fw-bold"><i class="bi bi-kanban me-2 text-primary"></i><?= e($program['name'] ?: $program['program_number']) ?></h2>
            <span class="badge <?= $statusClass ?> fs-6"><?= e($program['status']) ?></span>
            <span class="badge <?= $prioClass ?>"><?= e($program['priority']) ?> Priority</span>
        </div>
        <span class="text-muted">
            Code: <code><?= e($program['program_number']) ?></code> &bull; 
            Category: <strong class="text-dark"><?= e($catBreadcrumb) ?></strong>
        </span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('production/programs/edit/' . $program['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>

        <?php if ($program['status'] === 'APPROVED' || $program['status'] === 'IN_PROGRESS'): ?>
            <a href="<?= url('production/cutting/create?program_id=' . $program['id']) ?>" class="btn btn-primary">
                <i class="bi bi-scissors me-1"></i> New Cutting Batch
            </a>
            <a href="<?= url('production/labels/create?program_id=' . $program['id']) ?>" class="btn btn-outline-dark">
                <i class="bi bi-tags me-1"></i> Labels
            </a>
        <?php endif; ?>

        <!-- Change Status Modal Trigger -->
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
            <i class="bi bi-arrow-repeat me-1"></i> Status
        </button>

        <!-- Delete Program Form -->
        <form method="POST" action="<?= url('production/programs/delete/' . $program['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this production program?');">
            <?= CSRF::field() ?>
            <button type="submit" class="btn btn-outline-danger" title="Delete Program">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </form>
    </div>
</div>

<!-- Key Info Cards -->
<div class="row g-3 mb-4">
    <!-- Raw Material Roll Info -->
    <div class="col-md-3">
        <div class="card p-3 bg-light border h-100">
            <div class="small text-muted mb-1 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-layers text-primary me-1"></i>Assigned Fabric Rolls</span>
                <span class="badge bg-primary-subtle text-primary border"><?= !empty($programRolls) ? count($programRolls) : 1 ?> Roll(s)</span>
            </div>
            <?php if (!empty($programRolls) && count($programRolls) > 1): ?>
                <div class="fw-bold fs-6 text-dark"><?= count($programRolls) ?> Rolls Assigned</div>
                <div class="small text-muted text-truncate" title="<?= e(implode(', ', array_map(fn($r) => $r['roll_number'] ?: ('Roll #' . $r['seller_roll_id']), $programRolls))) ?>">
                    <?= e(implode(', ', array_map(fn($r) => $r['roll_number'] ?: ('Roll #' . $r['seller_roll_id']), $programRolls))) ?>
                </div>
            <?php else: ?>
                <div class="fw-bold fs-6 text-dark"><?= e($program['roll_number'] ?: ('Roll #' . ($program['seller_roll_id'] ?? '-'))) ?></div>
                <div class="small text-muted">
                    <?= e($program['seller_name'] ?? 'Supplier') ?>
                    <?php if (!empty($program['roll_color'])): ?>
                        &bull; <?= e($program['roll_color']) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Roll Weight & Price -->
    <div class="col-md-3">
        <div class="card p-3 bg-light border h-100">
            <div class="small text-muted mb-1"><i class="bi bi-speedometer2 text-primary me-1"></i>Total Weight & Price</div>
            <div class="fw-bold fs-6 text-dark"><?= number_format((float)($program['roll_weight'] ?? 0), 2) ?> kg</div>
            <div class="small text-success fw-semibold"><?= formatCurrency((float)($program['roll_price'] ?? 0)) ?></div>
        </div>
    </div>

    <!-- Timeline / Dates -->
    <div class="col-md-3">
        <div class="card p-3 bg-light border h-100">
            <div class="small text-muted mb-1"><i class="bi bi-calendar-range text-primary me-1"></i>Schedule Timeline</div>
            <div class="small"><span class="text-muted">Start:</span> <strong><?= !empty($program['start_date']) ? formatDate($program['start_date']) : '-' ?></strong></div>
            <div class="small"><span class="text-muted">End:</span> <strong><?= !empty($program['end_date']) ? formatDate($program['end_date']) : (!empty($program['target_date']) ? formatDate($program['target_date']) : '-') ?></strong></div>
        </div>
    </div>

    <!-- Supervisor & Unit -->
    <div class="col-md-3">
        <div class="card p-3 bg-light border h-100">
            <div class="small text-muted mb-1"><i class="bi bi-person-badge text-primary me-1"></i>Supervisor & Unit</div>
            <div class="fw-bold fs-6 text-dark"><?= e($program['assigned_to_name'] ?? 'Unassigned') ?></div>
            <div class="small text-muted"><?= e($program['unit_name'] ?? 'No unit assigned') ?></div>
        </div>
    </div>
</div>

<?php if (!empty($programRolls) && count($programRolls) > 1): ?>
    <!-- Multiple Rolls Detail Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-2 border-bottom d-flex align-items-center justify-content-between">
            <span class="fw-bold text-dark"><i class="bi bi-layers text-primary me-2"></i>Assigned Fabric Rolls Breakdown (<?= count($programRolls) ?>)</span>
            <span class="badge bg-light text-dark border">Combined: <?= number_format((float)($program['roll_weight'] ?? 0), 2) ?> kg / <?= formatCurrency((float)($program['roll_price'] ?? 0)) ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>#</th>
                            <th>Roll Number</th>
                            <th>Catalog Hierarchy</th>
                            <th>Material Type</th>
                            <th>Color</th>
                            <th>Design</th>
                            <th>Supplier</th>
                            <th class="text-end">Qty (pcs)</th>
                            <th class="text-end">Weight (kg)</th>
                            <th class="text-end">Price (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programRolls as $idx => $pr): ?>
                            <?php 
                                $catParts = array_filter([
                                    $pr['category_name'] ?? '',
                                    $pr['subcategory_name'] ?? '',
                                    $pr['sub_subcategory_name'] ?? ''
                                ]);
                                $catText = !empty($catParts) ? implode(' › ', $catParts) : 'General Apparel';
                            ?>
                            <tr>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?= $idx + 1 ?></span></td>
                                <td class="fw-bold text-primary"><?= e($pr['roll_number'] ?: ('Roll #' . $pr['seller_roll_id'])) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($catText) ?></span></td>
                                <td><?= e($pr['material_type'] ?: 'Fabric') ?></td>
                                <td><?= e($pr['roll_color'] ?: 'Standard') ?></td>
                                <td><?= e($pr['roll_design'] ?: 'Plain') ?></td>
                                <td><?= e($pr['seller_name'] ?? '-') ?></td>
                                <td class="text-end fw-semibold text-dark"><?= number_format((int)($pr['roll_qty'] ?? 0)) ?> pcs</td>
                                <td class="text-end fw-semibold"><?= number_format((float)$pr['roll_weight'], 2) ?> kg</td>
                                <td class="text-end fw-bold text-success"><?= formatCurrency((float)$pr['roll_price']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Progress Bar -->
<div class="card mb-4">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Overall Program Completion</span>
            <span class="fw-bold"><?= $pct ?>%</span>
        </div>
        <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width: <?= min(100, $pct) ?>%"></div>
        </div>
    </div>
</div>

<!-- Tabs for Stages -->
<ul class="nav nav-tabs mb-4" id="progTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active fw-semibold" id="items-tab" data-bs-toggle="tab" data-bs-target="#items-pane" type="button">
            <i class="bi bi-grid-3x3 me-1 text-primary"></i> Planned Variants (<?= count($items) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="cutting-tab" data-bs-toggle="tab" data-bs-target="#cutting-pane" type="button">
            <i class="bi bi-scissors me-1 text-info"></i> Cutting (<?= count($cutting) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="stitching-tab" data-bs-toggle="tab" data-bs-target="#stitching-pane" type="button">
            <i class="bi bi-threads me-1 text-warning"></i> Stitching (<?= count($stitching) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="labels-tab" data-bs-toggle="tab" data-bs-target="#labels-pane" type="button">
            <i class="bi bi-tags me-1 text-secondary"></i> Labels (<?= count($labels) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="qc-tab" data-bs-toggle="tab" data-bs-target="#qc-pane" type="button">
            <i class="bi bi-patch-check me-1 text-danger"></i> QC Inspections (<?= count($qc) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="batches-tab" data-bs-toggle="tab" data-bs-target="#batches-pane" type="button">
            <i class="bi bi-box-seam me-1 text-success"></i> Finished Batches (<?= count($batches) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="progTabContent">
    <!-- 1. PLANNED VARIANTS TAB -->
    <div class="tab-pane fade show active" id="items-pane" role="tabpanel">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Variant SKU</th>
                                <th>Size</th>
                                <th>Color</th>
                                <th>Unit Production Cost</th>
                                <th>Planned Qty</th>
                                <th>Completed Qty</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <?php $itemPct = $it['planned_quantity'] > 0 ? round(($it['completed_quantity'] / $it['planned_quantity']) * 100) : 0; ?>
                                <tr>
                                    <td><code><?= e($it['sku']) ?></code></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($it['size_name']) ?></span></td>
                                    <td>
                                        <?php if (!empty($it['color_hex'])): ?>
                                            <span class="d-inline-block rounded-circle me-1 border" style="width: 12px; height: 12px; background-color: <?= e($it['color_hex']) ?>;"></span>
                                        <?php endif; ?>
                                        <?= e($it['color_name']) ?>
                                    </td>
                                    <td><?= formatCurrency((float)$it['production_cost']) ?></td>
                                    <td class="fw-bold"><?= number_format($it['planned_quantity']) ?> pcs</td>
                                    <td class="text-success fw-bold"><?= number_format($it['completed_quantity']) ?> pcs</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; width: 80px;">
                                                <div class="progress-bar bg-success" style="width: <?= min(100, $itemPct) ?>%"></div>
                                            </div>
                                            <span class="small text-muted"><?= $itemPct ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. CUTTING TAB -->
    <div class="tab-pane fade" id="cutting-pane" role="tabpanel">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Batch #</th>
                                <th>Planned</th>
                                <th>Cut Qty</th>
                                <th>Rejected</th>
                                <th>Wastage</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cutting)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No cutting batches created.</td></tr>
                            <?php else: ?>
                                <?php foreach ($cutting as $c): ?>
                                    <tr>
                                        <td><strong><?= e($c['batch_number']) ?></strong></td>
                                        <td><?= number_format($c['planned_qty']) ?> pcs</td>
                                        <td class="text-success fw-bold"><?= number_format($c['cut_qty']) ?> pcs</td>
                                        <td class="text-danger"><?= number_format($c['rejected_qty']) ?> pcs</td>
                                        <td class="text-warning"><?= number_format($c['wastage_qty']) ?> pcs</td>
                                        <td><span class="badge bg-info text-dark"><?= e($c['status']) ?></span></td>
                                        <td class="text-end">
                                            <a href="<?= url('production/cutting/edit/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="<?= url('production/stitching/create?cutting_batch_id=' . $c['id']) ?>" class="btn btn-sm btn-outline-warning" title="Send to Stitching">
                                                <i class="bi bi-threads"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. STITCHING TAB -->
    <div class="tab-pane fade" id="stitching-pane" role="tabpanel">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Stitching Batch ID</th>
                                <th>Received</th>
                                <th>Stitched</th>
                                <th>Rejected</th>
                                <th>Rework</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stitching)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No stitching batches recorded.</td></tr>
                            <?php else: ?>
                                <?php foreach ($stitching as $st): ?>
                                    <tr>
                                        <td><strong>Batch #<?= $st['id'] ?></strong></td>
                                        <td><?= number_format($st['received_qty']) ?> pcs</td>
                                        <td class="text-success fw-bold"><?= number_format($st['stitched_qty']) ?> pcs</td>
                                        <td class="text-danger"><?= number_format($st['rejected_qty']) ?> pcs</td>
                                        <td class="text-warning"><?= number_format($st['rework_qty']) ?> pcs</td>
                                        <td><span class="badge bg-warning text-dark"><?= e($st['status']) ?></span></td>
                                        <td class="text-end">
                                            <a href="<?= url('production/stitching/edit/' . $st['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($st['status'] === 'COMPLETED'): ?>
                                                <a href="<?= url('production/qc/create?stitching_batch_id=' . $st['id']) ?>" class="btn btn-sm btn-outline-danger" title="Send to QC">
                                                    <i class="bi bi-patch-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. LABELS TAB -->
    <div class="tab-pane fade" id="labels-pane" role="tabpanel">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Label Batch #</th>
                                <th>Required</th>
                                <th>Printed</th>
                                <th>Used</th>
                                <th>Damaged</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($labels)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No label batches.</td></tr>
                            <?php else: ?>
                                <?php foreach ($labels as $lb): ?>
                                    <tr>
                                        <td><strong><?= e($lb['batch_number']) ?></strong></td>
                                        <td><?= number_format($lb['required_qty']) ?></td>
                                        <td class="text-success"><?= number_format($lb['printed_qty']) ?></td>
                                        <td><?= number_format($lb['used_qty']) ?></td>
                                        <td class="text-danger"><?= number_format($lb['damaged_qty']) ?></td>
                                        <td><span class="badge bg-secondary"><?= e($lb['status']) ?></span></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('production/labels/edit/' . $lb['id']) ?>" class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if (hasPermission('labels', 'delete')): ?>
                                                <form method="POST" action="<?= url('production/labels/delete/' . $lb['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Label Batch <?= e(addslashes($lb['batch_number'] ?: ('#' . $lb['id']))) ?>? This cannot be undone.');">
                                                    <?= CSRF::field() ?>
                                                    <button type="submit" class="btn btn-outline-danger" title="Delete Label Batch">
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
        </div>
    </div>

    <!-- 5. QC TAB -->
    <div class="tab-pane fade" id="qc-pane" role="tabpanel">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>QC Batch #</th>
                                <th>Total Inspected</th>
                                <th>Passed</th>
                                <th>Rejected</th>
                                <th>Rework</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($qc)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No QC inspections performed.</td></tr>
                            <?php else: ?>
                                <?php foreach ($qc as $q): ?>
                                    <?php 
                                        $qcClass = match($q['status']) {
                                            'PASSED'   => 'bg-success',
                                            'REJECTED' => 'bg-danger',
                                            'REWORK'   => 'bg-warning text-dark',
                                            'PARTIAL'  => 'bg-info text-dark',
                                            default    => 'bg-secondary'
                                        };
                                    ?>
                                    <tr>
                                        <td><strong>QC #<?= $q['id'] ?></strong></td>
                                        <td><?= number_format($q['total_qty']) ?> pcs</td>
                                        <td class="text-success fw-bold"><?= number_format($q['passed_qty']) ?> pcs</td>
                                        <td class="text-danger"><?= number_format($q['rejected_qty']) ?> pcs</td>
                                        <td class="text-warning"><?= number_format($q['rework_qty']) ?> pcs</td>
                                        <td><span class="badge <?= $qcClass ?>"><?= e($q['status']) ?></span></td>
                                        <td class="text-end">
                                            <a href="<?= url('production/qc/inspect/' . $q['id']) ?>" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-clipboard-check"></i> Inspect
                                            </a>
                                            <?php if ($q['passed_qty'] > 0): ?>
                                                <a href="<?= url('production/batches/create?qc_batch_id=' . $q['id']) ?>" class="btn btn-sm btn-outline-success" title="Pack Passed Items">
                                                    <i class="bi bi-box-seam"></i> Pack
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. FINISHED PRODUCTION BATCHES TAB -->
    <div class="tab-pane fade" id="batches-pane" role="tabpanel">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Batch #</th>
                                <th>Total Pieces</th>
                                <th>Boxes Packed</th>
                                <th>Destination Warehouse</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($batches)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No finished packing batches created.</td></tr>
                            <?php else: ?>
                                <?php foreach ($batches as $bt): ?>
                                    <tr>
                                        <td><strong><?= e($bt['batch_number']) ?></strong></td>
                                        <td class="fw-bold text-success"><?= number_format($bt['total_qty']) ?> pcs</td>
                                        <td><?= number_format($bt['box_count']) ?> boxes</td>
                                        <td>Warehouse #<?= $bt['destination_warehouse_id'] ?></td>
                                        <td><span class="badge bg-primary"><?= e($bt['status']) ?></span></td>
                                        <td class="text-end">
                                            <?php if ($bt['status'] === 'PREPARING'): ?>
                                                <form method="POST" action="<?= url('production/batches/approve/' . $bt['id']) ?>" class="d-inline">
                                                    <?= CSRF::field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-check2"></i> Approve
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('production/programs/status/' . $program['id']) ?>">
                <?= CSRF::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Change Program Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label required">Select New Status</label>
                    <select name="status" class="form-select" required>
                        <option value="DRAFT" <?= $program['status'] === 'DRAFT' ? 'selected' : '' ?>>Draft</option>
                        <option value="APPROVED" <?= $program['status'] === 'APPROVED' ? 'selected' : '' ?>>Approved</option>
                        <option value="SCHEDULED" <?= $program['status'] === 'SCHEDULED' ? 'selected' : '' ?>>Scheduled</option>
                        <option value="IN_PROGRESS" <?= $program['status'] === 'IN_PROGRESS' ? 'selected' : '' ?>>In Progress</option>
                        <option value="COMPLETED" <?= $program['status'] === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                        <option value="HOLD" <?= $program['status'] === 'HOLD' ? 'selected' : '' ?>>On Hold</option>
                        <option value="CANCELLED" <?= $program['status'] === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
