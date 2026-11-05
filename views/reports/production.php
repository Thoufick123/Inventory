<?php
// views/reports/production.php
// Production & Manufacturing Detailed Operations Report
$reportType = 'production';
$totals = $productionData['totals'] ?? [];
$completionRate = ($totals['planned_qty'] ?? 0) > 0 ? round((($totals['completed_qty'] ?? 0) / $totals['planned_qty']) * 100, 1) : 0;
$totalQc = ($totals['qc_passed'] ?? 0) + ($totals['qc_rejected'] ?? 0);
$qcPassRate = $totalQc > 0 ? round((($totals['qc_passed'] ?? 0) / $totalQc) * 100, 1) : 100;
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold text-dark">
            <i class="bi bi-gear-wide-connected text-primary me-2"></i>Production &amp; Manufacturing Operations
        </h4>
        <div class="text-muted small mt-1">Planned programs, cutting &amp; fabric consumption, stitching stages, QC pass rates, and factory expenses</div>
    </div>
</div>

<?php require __DIR__ . '/partials/filter_bar.php'; ?>

<!-- ========================================================================= -->
<!-- 1. PRODUCTION KPI SCOREBOARD -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Programs Completion -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Planned vs Completed</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= number_format($totals['completed_qty'] ?? 0) ?> <span class="fs-6 text-muted fw-normal">/ <?= number_format($totals['planned_qty'] ?? 0) ?></span></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <span class="badge bg-primary text-white me-1"><?= $completionRate ?>%</span> Completion Rate
                    </div>
                </div>
                <div class="bg-primary text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-gear-wide-connected fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Cutting & Fabric Wastage -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #d97706 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-warning" style="font-size: 0.72rem; letter-spacing: 0.5px;">Cutting Pieces &amp; Wastage</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= number_format($totals['cut_qty'] ?? 0) ?> pcs</div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-scissors me-1"></i>Fabric Scrap: <span class="fw-bold text-warning-emphasis"><?= number_format($totals['cutting_wastage'] ?? 0, 1) ?> kg</span>
                    </div>
                </div>
                <div class="bg-warning text-dark p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-scissors fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quality Control Pass Rate -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 0.5px;">QC Pass Rate</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= $qcPassRate ?>%</div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <span class="text-success fw-bold"><?= number_format($totals['qc_passed'] ?? 0) ?> Pass</span> &bull; 
                        <span class="text-danger fw-bold"><?= number_format($totals['qc_rejected'] ?? 0) ?> Rej</span>
                    </div>
                </div>
                <div class="bg-success text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Factory Operating Expenses & Scrap Sales -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-left: 4px solid #dc2626 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-danger" style="font-size: 0.72rem; letter-spacing: 0.5px;">Factory OpEx &amp; Scrap Rev</div>
                    <div class="fs-4 fw-bold text-danger mt-1 font-monospace">₹<?= number_format($totals['expense_total'] ?? 0, 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-recycle me-1"></i>Scrap Recovery: <span class="text-success fw-bold">₹<?= number_format($totals['wastage_revenue'] ?? 0, 2) ?></span>
                    </div>
                </div>
                <div class="bg-danger text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-lightning-charge fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. PRODUCTION UNIT FILTER PILLS -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="bi bi-building me-1 text-primary"></i>Filter by Factory Unit:
                </span>
                <a href="<?= url('reports/production') ?>?filter_type=<?= urlencode($filter['filter_type']) ?>&unit_id=0&year=<?= $filter['year'] ?>&month=<?= $filter['month'] ?>" 
                   class="btn btn-sm <?= empty($unitId) ? 'btn-primary' : 'btn-light border' ?>">
                   All Units
                </a>
                <?php foreach ($units as $u): ?>
                    <a href="<?= url('reports/production') ?>?filter_type=<?= urlencode($filter['filter_type']) ?>&unit_id=<?= $u['id'] ?>&year=<?= $filter['year'] ?>&month=<?= $filter['month'] ?>" 
                       class="btn btn-sm <?= ($unitId == $u['id']) ? 'btn-primary' : 'btn-light border' ?>">
                       <?= e($u['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. DETAILED PRODUCTION STAGES TABS -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-0 border-bottom">
        <ul class="nav nav-pills card-header-pills" id="prodReportTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-semibold" id="prog-tab" data-bs-toggle="tab" data-bs-target="#prog-content" type="button" role="tab">
                    <i class="bi bi-calendar-check me-1 text-primary"></i>Programs Planned &amp; Completed (<?= count($productionData['programs']) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="cut-tab" data-bs-toggle="tab" data-bs-target="#cut-content" type="button" role="tab">
                    <i class="bi bi-scissors me-1 text-warning"></i>Cutting Batches (<?= count($productionData['cutting']) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="stitch-tab" data-bs-toggle="tab" data-bs-target="#stitch-content" type="button" role="tab">
                    <i class="bi bi-pin-angle me-1 text-info"></i>Stitching Stages (<?= count($productionData['stitching']) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="qc-tab" data-bs-toggle="tab" data-bs-target="#qc-content" type="button" role="tab">
                    <i class="bi bi-shield-check me-1 text-success"></i>QC Inspections (<?= count($productionData['qc']) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="opex-tab" data-bs-toggle="tab" data-bs-target="#opex-content" type="button" role="tab">
                    <i class="bi bi-receipt me-1 text-danger"></i>Unit Expenses (<?= count($productionData['expenses']) ?>)
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content" id="prodReportTabsContent">
            <!-- 1. PROGRAMS TAB -->
            <div class="tab-pane fade show active" id="prog-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Program #</th>
                                <th>Product Style / Item</th>
                                <th>Unit / Facility</th>
                                <th class="text-end">Planned Qty</th>
                                <th class="text-end">Completed Qty</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Target Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productionData['programs'])): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No production programs found in this period.</td></tr>
                            <?php else: ?>
                                <?php foreach ($productionData['programs'] as $p): 
                                    $pRate = $p['planned_quantity'] > 0 ? round(($p['completed_quantity'] / $p['planned_quantity']) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-primary"><?= e($p['program_number']) ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($p['product_name']) ?></div>
                                            <div class="small text-muted font-monospace"><?= e($p['product_code']) ?></div>
                                        </td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= e($p['unit_name'] ?? 'Factory Unit') ?></span></td>
                                        <td class="text-end font-monospace"><?= number_format($p['planned_quantity']) ?> pcs</td>
                                        <td class="text-end font-monospace fw-bold text-success">
                                            <?= number_format($p['completed_quantity']) ?> pcs
                                            <span class="badge bg-success-subtle text-success ms-1"><?= $pRate ?>%</span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $p['status'] === 'COMPLETED' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                <?= e($p['status']) ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-end small text-muted font-monospace">
                                            <?= !empty($p['target_date']) ? date('d M Y', strtotime($p['target_date'])) : '-' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. CUTTING TAB -->
            <div class="tab-pane fade" id="cut-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Cutting Batch #</th>
                                <th>Program #</th>
                                <th>Unit</th>
                                <th class="text-end">Cut Quantity</th>
                                <th class="text-end">Wastage (Kg)</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productionData['cutting'])): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No cutting batches found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($productionData['cutting'] as $c): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-primary"><?= e($c['batch_number'] ?? ('CUT-'.$c['id'])) ?></td>
                                        <td class="font-monospace"><?= e($c['program_number'] ?? '-') ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= e($c['unit_name'] ?? 'Unit 1') ?></span></td>
                                        <td class="text-end font-monospace fw-bold"><?= number_format($c['cut_qty']) ?> pcs</td>
                                        <td class="text-end font-monospace text-danger fw-bold"><?= number_format($c['wastage_weight'], 2) ?> kg</td>
                                        <td><span class="badge bg-info-subtle text-info"><?= e($c['status'] ?? 'COMPLETED') ?></span></td>
                                        <td class="pe-4 text-end small text-muted"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. STITCHING TAB -->
            <div class="tab-pane fade" id="stitch-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Stitching Batch #</th>
                                <th>Program #</th>
                                <th>Unit</th>
                                <th class="text-end">Stitched Qty</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productionData['stitching'])): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No stitching batches found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($productionData['stitching'] as $s): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-primary"><?= e($s['batch_number'] ?? ('ST-'.$s['id'])) ?></td>
                                        <td class="font-monospace"><?= e($s['program_number'] ?? '-') ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= e($s['unit_name'] ?? 'Unit 2') ?></span></td>
                                        <td class="text-end font-monospace fw-bold text-success"><?= number_format($s['stitched_qty']) ?> pcs</td>
                                        <td><span class="badge bg-success-subtle text-success"><?= e($s['status'] ?? 'COMPLETED') ?></span></td>
                                        <td class="pe-4 text-end small text-muted"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. QC TAB -->
            <div class="tab-pane fade" id="qc-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">QC Batch #</th>
                                <th>Program #</th>
                                <th class="text-end">Inspected Qty</th>
                                <th class="text-end">Passed</th>
                                <th class="text-end">Rejected</th>
                                <th>Pass Rate</th>
                                <th class="pe-4 text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productionData['qc'])): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No QC inspection records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($productionData['qc'] as $q): 
                                    $tot = (int)$q['passed_qty'] + (int)$q['rejected_qty'];
                                    $pr = $tot > 0 ? round(($q['passed_qty'] / $tot) * 100, 1) : 100;
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-primary"><?= e($q['batch_number'] ?? ('QC-'.$q['id'])) ?></td>
                                        <td class="font-monospace"><?= e($q['program_number'] ?? '-') ?></td>
                                        <td class="text-end font-monospace"><?= number_format($tot) ?> pcs</td>
                                        <td class="text-end font-monospace text-success fw-bold"><?= number_format($q['passed_qty']) ?> pcs</td>
                                        <td class="text-end font-monospace text-danger fw-bold"><?= number_format($q['rejected_qty']) ?> pcs</td>
                                        <td>
                                            <span class="badge <?= $pr >= 95 ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                <?= $pr ?>%
                                            </span>
                                        </td>
                                        <td class="pe-4 text-end small text-muted"><?= date('d M Y', strtotime($q['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. OPEX TAB -->
            <div class="tab-pane fade" id="opex-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Expense Ref #</th>
                                <th>Unit / Facility</th>
                                <th>Category</th>
                                <th>Item / Description</th>
                                <th class="text-end">Total Amount</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productionData['expenses'])): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No factory unit expense records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($productionData['expenses'] as $exp): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-warning-emphasis"><?= e($exp['expense_number']) ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= e($exp['production_unit'] ?? 'Unit') ?></span></td>
                                        <td><?= e($exp['category']) ?></td>
                                        <td><?= e($exp['item_name']) ?></td>
                                        <td class="text-end font-monospace fw-bold text-danger">₹<?= number_format($exp['total_cost'], 2) ?></td>
                                        <td><span class="badge bg-success-subtle text-success"><?= e($exp['payment_status'] ?? 'PAID') ?></span></td>
                                        <td class="pe-4 text-end small text-muted"><?= date('d M Y', strtotime($exp['expense_date'] ?? $exp['created_at'])) ?></td>
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
