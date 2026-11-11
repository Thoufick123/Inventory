<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <?php
    $isAdmin = isSuperAdmin();
    $scopedUnits = getScopedProductionUnits();
    $canPrograms = hasPermission('programs', 'view');
    $canProgramsCreate = hasPermission('programs', 'create');
    $canCutting = hasPermission('cutting', 'view');
    $canStitching = hasPermission('stitching', 'view');
    $canLabels = hasPermission('labels', 'view');
    $canQC = hasPermission('qc', 'view');
    $canBatches = hasPermission('batches', 'view');
    $canOutward = hasPermission('outward', 'view');
    $canInventory = hasPermission('inventory', 'view');
    $canInward = hasPermission('production_inward', 'view');
    $canBilling = hasPermission('production_billing', 'view');
    $canBillingCreate = hasPermission('production_billing', 'create');
    $canManufacturing = $canPrograms || $canCutting || $canStitching || $canQC;
    ?>
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h2 class="mb-0"><i class="bi bi-gear-wide-connected me-2 text-primary"></i>Production Dashboard</h2>
            <?php
            $currUser = currentUser();
            $assignedPU = $currUser['production_unit_name'] ?? null;
            if ($assignedPU): ?>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1.5 rounded-pill fs-7 d-inline-flex align-items-center">
                    <i class="bi bi-buildings-fill me-1"></i> Unit: <?= e($assignedPU) ?>
                </span>
            <?php elseif ($isAdmin): ?>
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 rounded-pill fs-7 d-inline-flex align-items-center">
                    <i class="bi bi-globe-americas me-1"></i> All Production Units (Admin Scope)
                </span>
            <?php endif; ?>
        </div>
        <span class="text-muted">Live status of concern production, inward stock, billing &amp; dispatches</span>
        <?php if (!empty($activeConcern)): ?>
            <div class="small text-primary mt-1">Active concern: <strong><?= e($activeConcern['name']) ?></strong></div>
        <?php endif; ?>
    </div>
    <?php if ($canProgramsCreate || $canBillingCreate || $canInward || $canInventory || $canOutward): ?>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($canProgramsCreate): ?>
                <a href="<?= url('production/programs/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> New Program
                </a>
            <?php endif; ?>
            <?php if ($canBillingCreate): ?>
                <a href="<?= url('production/billing/create') ?>" class="btn btn-success">
                    <i class="bi bi-receipt me-1"></i> Create Bill
                </a>
            <?php endif; ?>
            <?php if ($canInward): ?>
                <a href="<?= url('production/inward') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Inward / GRN
                </a>
            <?php elseif ($canInventory): ?>
                <a href="<?= url('production/inventory') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-boxes me-1"></i> Inventory
                </a>
            <?php endif; ?>
            <?php if ($canOutward): ?>
                <a href="<?= url('production/outward') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-truck me-1"></i> Outward Dispatches
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (($scopedUnitId ?? null) === 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Your assigned production unit does not belong to the active concern. Manufacturing unit records remain hidden until your concern/unit assignment is updated.
    </div>
<?php elseif (empty($scopedUnits) && $canManufacturing): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        No manufacturing production units are assigned to this concern. Manufacturing stage metrics remain empty unless a production unit is configured.
    </div>
<?php endif; ?>

<!-- KPI Stats Row -->
<div class="row g-3 mb-4">
    <?php if ($canPrograms): ?>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon icon-primary">
                <i class="bi bi-kanban"></i>
            </div>
            <div class="stat-value"><?= number_format($activePrograms['count'] ?? 0) ?></div>
            <div class="stat-label">Active Programs</div>
            <small class="text-muted"><?= number_format($activePrograms['total_planned'] ?? 0) ?> pcs planned</small>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($canCutting): ?>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon icon-info">
                <i class="bi bi-scissors"></i>
            </div>
            <div class="stat-value"><?= number_format($pendingCutting['count'] ?? 0) ?></div>
            <div class="stat-label">Active Cutting</div>
            <small class="text-muted"><?= number_format($pendingCutting['total_qty'] ?? 0) ?> pcs in queue</small>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($canStitching): ?>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon icon-warning">
                <i class="bi bi-threads"></i>
            </div>
            <div class="stat-value"><?= number_format($pendingStitching['count'] ?? 0) ?></div>
            <div class="stat-label">Active Stitching</div>
            <small class="text-muted"><?= number_format($pendingStitching['total_qty'] ?? 0) ?> pcs sewing</small>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($canQC): ?>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon icon-danger">
                <i class="bi bi-patch-check"></i>
            </div>
            <div class="stat-value"><?= number_format($pendingQC['count'] ?? 0) ?></div>
            <div class="stat-label">Pending QC</div>
            <small class="text-muted"><?= number_format($pendingQC['total_qty'] ?? 0) ?> pcs awaiting inspect</small>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($canInventory): ?>
    <div class="col-sm-6 col-xl-2">
        <a href="<?= url('production/inventory') ?>" class="text-decoration-none text-reset d-block h-100">
            <div class="stat-card h-100">
                <div class="stat-icon icon-success">
                    <i class="bi bi-boxes"></i>
                </div>
                <div class="stat-value"><?= number_format((float)($inwardStock['available_qty'] ?? 0)) ?></div>
                <div class="stat-label">Inward Stock (Pcs)</div>
                <small class="text-muted">
                    <?= number_format((int)($inwardStock['variant_count'] ?? 0)) ?> SKUs<?= !empty($inwardStock['damaged_qty']) && (float)$inwardStock['damaged_qty'] > 0 ? ' • ' . number_format((float)$inwardStock['damaged_qty']) . ' dmg' : '' ?>
                </small>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if ($canInward): ?>
    <div class="col-sm-6 col-xl-2">
        <a href="<?= url('production/inward') ?>" class="text-decoration-none text-reset d-block h-100">
            <div class="stat-card h-100">
                <div class="stat-icon icon-info">
                    <i class="bi bi-box-arrow-in-down"></i>
                </div>
                <div class="stat-value"><?= number_format((int)($pendingInwards['count'] ?? 0)) ?></div>
                <div class="stat-label">Pending Inwards</div>
                <small class="text-muted">
                    <?= number_format((int)($pendingInwards['total_qty'] ?? 0)) ?> pcs • <?= number_format((int)($inwardReceipts['total_count'] ?? 0)) ?> GRNs
                </small>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if ($canBilling): ?>
    <div class="col-sm-6 col-xl-2">
        <a href="<?= url('production/billing') ?>" class="text-decoration-none text-reset d-block h-100">
            <div class="stat-card h-100">
                <div class="stat-icon icon-primary">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-value">₹<?= number_format((float)($billingSummary['month_total'] ?? 0), 0) ?></div>
                <div class="stat-label">Month Billing</div>
                <small class="text-muted">
                    <?= number_format((int)($billingSummary['month_count'] ?? 0)) ?> bills (<?= number_format((int)($billingSummary['total_count'] ?? 0)) ?> total)
                </small>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <?php if ($canBatches): ?>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon icon-success">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="stat-value"><?= number_format($readyDispatch['count'] ?? 0) ?></div>
            <div class="stat-label">Ready for Dispatch</div>
            <small class="text-muted"><?= number_format($readyDispatch['total_qty'] ?? 0) ?> pcs packed</small>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($canOutward): ?>
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon icon-primary">
                <i class="bi bi-truck"></i>
            </div>
            <div class="stat-value"><?= number_format($monthOutward['count'] ?? 0) ?></div>
            <div class="stat-label">Month Dispatches</div>
            <small class="text-muted"><?= number_format($monthOutward['total_qty'] ?? 0) ?> pcs dispatched</small>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($canManufacturing || $canLabels): ?>
<!-- Production Pipeline Flow Visualizer -->
<div class="card mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold"><i class="bi bi-diagram-3 me-2 text-primary"></i>Production Lifecycle Stages</h5>
    </div>
    <div class="card-body">
        <div class="row text-center g-3">
            <?php if ($canPrograms): ?>
            <div class="col-md-2 col-4">
                <div class="p-3 rounded bg-light border">
                    <div class="fs-4 text-primary"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="fw-bold mt-1">1. Program</div>
                    <small class="text-muted">Plan &amp; Approve</small>
                    <div class="mt-2"><a href="<?= url('production/programs') ?>" class="btn btn-sm btn-outline-primary w-100">View</a></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($canCutting): ?>
            <div class="col-md-2 col-4">
                <div class="p-3 rounded bg-light border">
                    <div class="fs-4 text-info"><i class="bi bi-scissors"></i></div>
                    <div class="fw-bold mt-1">2. Cutting</div>
                    <small class="text-muted">Material Issue</small>
                    <div class="mt-2"><a href="<?= url('production/cutting') ?>" class="btn btn-sm btn-outline-info w-100">View</a></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($canStitching): ?>
            <div class="col-md-2 col-4">
                <div class="p-3 rounded bg-light border">
                    <div class="fs-4 text-warning"><i class="bi bi-threads"></i></div>
                    <div class="fw-bold mt-1">3. Stitching</div>
                    <small class="text-muted">Sewing &amp; Rework</small>
                    <div class="mt-2"><a href="<?= url('production/stitching') ?>" class="btn btn-sm btn-outline-warning w-100">View</a></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($canLabels): ?>
            <div class="col-md-2 col-4">
                <div class="p-3 rounded bg-light border">
                    <div class="fs-4 text-secondary"><i class="bi bi-tags"></i></div>
                    <div class="fw-bold mt-1">4. Labeling</div>
                    <small class="text-muted">Tags &amp; Barcode</small>
                    <div class="mt-2"><a href="<?= url('production/labels') ?>" class="btn btn-sm btn-outline-secondary w-100">View</a></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($canQC): ?>
            <div class="col-md-2 col-4">
                <div class="p-3 rounded bg-light border">
                    <div class="fs-4 text-danger"><i class="bi bi-patch-check"></i></div>
                    <div class="fw-bold mt-1">5. QC Check</div>
                    <small class="text-muted">Pass/Reject/Rework</small>
                    <div class="mt-2"><a href="<?= url('production/qc') ?>" class="btn btn-sm btn-outline-danger w-100">View</a></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($canBatches): ?>
            <div class="col-md-2 col-4">
                <div class="p-3 rounded bg-light border">
                    <div class="fs-4 text-success"><i class="bi bi-box-seam"></i></div>
                    <div class="fw-bold mt-1">6. Batch &amp; Outward</div>
                    <small class="text-muted">Box &amp; Dispatch</small>
                    <div class="mt-2"><a href="<?= url('production/batches') ?>" class="btn btn-sm btn-outline-success w-100">View</a></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Dashboard Activity & Summary Panels -->
<div class="row g-4">
    <?php if ($canPrograms): ?>
    <!-- Active Programs Table -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                <h5 class="card-title mb-0 fw-bold"><i class="bi bi-list-task me-2 text-primary"></i>Active Production Programs</h5>
                <a href="<?= url('production/programs') ?>" class="btn btn-sm btn-link text-decoration-none">View All &rarr;</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Program #</th>
                                <th>Product</th>
                                <th>Unit</th>
                                <th>Progress</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPrograms)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-1"></i> No active production programs.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentPrograms as $prog): ?>
                                    <?php
                                        $plannedQuantity = (int)($prog['planned_quantity'] ?? 0);
                                        $completedQuantity = max(0, (int)($prog['completed_quantity'] ?? 0));
                                        $pct = $plannedQuantity > 0
                                            ? min(100, (int)round(($completedQuantity / $plannedQuantity) * 100))
                                            : 0;
                                        $prioClass = match($prog['priority']) {
                                            'URGENT' => 'bg-danger',
                                            'HIGH'   => 'bg-warning text-dark',
                                            'MEDIUM' => 'bg-info text-dark',
                                            default  => 'bg-secondary'
                                        };
                                        $statusClass = match($prog['status']) {
                                            'DRAFT'       => 'bg-secondary',
                                            'APPROVED'    => 'bg-info text-dark',
                                            'SCHEDULED'   => 'bg-primary',
                                            'IN_PROGRESS' => 'bg-warning text-dark',
                                            'COMPLETED'   => 'bg-success',
                                            'HOLD'        => 'bg-danger',
                                            default       => 'bg-dark'
                                        };
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('production/programs/view/' . $prog['id']) ?>" class="fw-bold text-decoration-none"><?= e($prog['program_number']) ?></a>
                                            <div class="small text-muted"><?= formatDate($prog['created_at']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= e($prog['product_name']) ?></div>
                                            <small class="text-muted"><?= e($prog['product_code']) ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= e($prog['unit_name'] ?? 'Unit') ?></span></td>
                                        <td style="min-width: 140px;">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span><?= number_format($completedQuantity) ?> / <?= number_format($plannedQuantity) ?></span>
                                                <span class="fw-bold"><?= $pct ?>%</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success" style="width: <?= min(100, $pct) ?>%"></div>
                                            </div>
                                        </td>
                                        <td><span class="badge <?= $prioClass ?>"><?= e($prog['priority']) ?></span></td>
                                        <td><span class="badge <?= $statusClass ?>"><?= e($prog['status']) ?></span></td>
                                        <td class="text-end">
                                            <a href="<?= url('production/programs/view/' . $prog['id']) ?>" class="btn btn-sm btn-outline-primary" title="View Details"><i class="bi bi-eye"></i></a>
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
    <?php endif; ?>

    <?php if ($canQC || $canOutward): ?>
    <!-- Recent QC & Outwards Side Column -->
    <div class="<?= $canPrograms ? 'col-lg-4' : 'col-lg-6' ?>">
        <?php if ($canQC): ?>
        <!-- Recent QC -->
        <div class="card mb-4">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-patch-check me-2 text-danger"></i>Recent QC Checks</h6>
                <a href="<?= url('production/qc') ?>" class="btn btn-sm btn-link text-decoration-none p-0">All QC &rarr;</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($recentQC)): ?>
                        <li class="list-group-item text-center text-muted py-3">No recent inspections</li>
                    <?php else: ?>
                        <?php foreach ($recentQC as $qc): ?>
                            <?php
                                $qcBadge = match($qc['status']) {
                                    'PASSED'   => 'bg-success',
                                    'REJECTED' => 'bg-danger',
                                    'REWORK'   => 'bg-warning text-dark',
                                    'PARTIAL'  => 'bg-info text-dark',
                                    default    => 'bg-secondary'
                                };
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">QC #<?= $qc['id'] ?> — <?= e($qc['product_name']) ?></div>
                                    <small class="text-muted">Passed: <?= $qc['passed_qty'] ?> / Total: <?= $qc['total_qty'] ?></small>
                                </div>
                                <span class="badge <?= $qcBadge ?>"><?= e($qc['status']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canOutward): ?>
        <!-- Recent Outwards -->
        <div class="card">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-truck me-2 text-success"></i>Recent Outward Dispatches</h6>
                <a href="<?= url('production/outward') ?>" class="btn btn-sm btn-link text-decoration-none p-0">All Outwards &rarr;</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($recentOutwards)): ?>
                        <li class="list-group-item text-center text-muted py-3">No dispatches yet</li>
                    <?php else: ?>
                        <?php foreach ($recentOutwards as $out): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><?= e($out['outward_number']) ?></div>
                                    <small class="text-muted"><?= number_format($out['total_qty']) ?> pcs &rarr; <?= e($out['destination_concern_name'] ?: $out['warehouse_name'] ?: 'Unassigned') ?></small>
                                </div>
                                <small class="text-muted"><?= formatDate($out['created_at'], 'd M') ?></small>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($canInward): ?>
    <!-- Recent Concern Inward Receipts -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-box-arrow-in-down me-2 text-info"></i>Recent Inward Receipts (GRN)</h6>
                <a href="<?= url('production/inward') ?>" class="btn btn-sm btn-link text-decoration-none p-0">Open Inward &rarr;</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($recentInwards)): ?>
                        <li class="list-group-item text-center text-muted py-4">No inward receipts recorded yet.</li>
                    <?php else: ?>
                        <?php foreach ($recentInwards as $inw): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><?= e($inw['grn_number']) ?><?= !empty($inw['source_batch_number']) ? ' — ' . e($inw['source_batch_number']) : '' ?></div>
                                    <small class="text-muted">
                                        To <?= e($inw['destination_name']) ?> &bull;
                                        <?= number_format((float)$inw['available_qty']) ?> good<?= (float)$inw['damaged_qty'] > 0 ? ', ' . number_format((float)$inw['damaged_qty']) . ' damaged' : '' ?>
                                    </small>
                                </div>
                                <small class="text-muted"><?= formatDate($inw['received_at'], 'd M') ?></small>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canBilling): ?>
    <!-- Recent Production Bills -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Recent Production Bills</h6>
                <a href="<?= url('production/billing') ?>" class="btn btn-sm btn-link text-decoration-none p-0">All Bills &rarr;</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($recentBills)): ?>
                        <li class="list-group-item text-center text-muted py-4">No production bills created yet.</li>
                    <?php else: ?>
                        <?php foreach ($recentBills as $bill): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="<?= url('production/billing/view/' . (int)$bill['id']) ?>" class="fw-semibold text-decoration-none"><?= e($bill['bill_number']) ?></a>
                                    <small class="text-muted d-block">
                                        <?= e(($bill['buyer_type'] ?? 'COMPANY') === 'CUSTOMER' ? ($bill['customer_name'] ?: 'Customer') : ($bill['company_name'] ?: 'Company')) ?>
                                        &bull; <?= formatDate($bill['bill_date'], 'd M Y') ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary">₹<?= number_format((float)$bill['total_amount'], 2) ?></div>
                                    <span class="badge <?= ($bill['payment_status'] ?? '') === 'PAID' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' ?>"><?= e($bill['payment_status'] ?? 'PAID') ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
