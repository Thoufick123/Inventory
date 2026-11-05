<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Production Analytics & Reports</h2>
        <span class="text-muted">Efficiency metrics, cutting yield, wastage rate, and QC pass percentages</span>
    </div>
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Print Report
    </button>
</div>

<!-- Top Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border">
            <div class="small text-muted mb-1"><i class="bi bi-kanban me-1 text-primary"></i>Total Planned Production</div>
            <div class="fw-bold fs-4 text-primary"><?= number_format($totalPrograms['planned'] ?? 0) ?> pcs</div>
            <small class="text-muted"><?= number_format($totalPrograms['cnt'] ?? 0) ?> total programs</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border">
            <div class="small text-muted mb-1"><i class="bi bi-check-circle me-1 text-success"></i>Finished & Delivered</div>
            <div class="fw-bold fs-4 text-success"><?= number_format($totalPrograms['completed'] ?? 0) ?> pcs</div>
            <?php 
                $compRate = ($totalPrograms['planned'] ?? 0) > 0 ? round((($totalPrograms['completed'] ?? 0) / $totalPrograms['planned']) * 100, 1) : 0;
            ?>
            <small class="text-muted"><?= $compRate ?>% overall completion rate</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border">
            <div class="small text-muted mb-1"><i class="bi bi-scissors me-1 text-info"></i>Cutting Wastage Rate</div>
            <?php 
                $cutTotal = ($totalCutting['cut'] ?? 0) + ($totalCutting['wastage'] ?? 0);
                $wasteRate = $cutTotal > 0 ? round((($totalCutting['wastage'] ?? 0) / $cutTotal) * 100, 1) : 0;
            ?>
            <div class="fw-bold fs-4 text-warning"><?= $wasteRate ?>%</div>
            <small class="text-muted"><?= number_format($totalCutting['wastage'] ?? 0) ?> pcs wastage / <?= number_format($totalCutting['cut'] ?? 0) ?> cut</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border">
            <div class="small text-muted mb-1"><i class="bi bi-patch-check me-1 text-danger"></i>QC Pass Rate</div>
            <?php 
                $qcTotal = $totalQC['inspected'] ?? 0;
                $passRate = $qcTotal > 0 ? round((($totalQC['passed'] ?? 0) / $qcTotal) * 100, 1) : 0;
            ?>
            <div class="fw-bold fs-4 text-success"><?= $passRate ?>%</div>
            <small class="text-muted"><?= number_format($totalQC['passed'] ?? 0) ?> passed / <?= number_format($totalQC['rejected'] ?? 0) ?> rejected</small>
        </div>
    </div>
</div>

<!-- Production Unit Performance -->
<div class="card mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold"><i class="bi bi-buildings me-2 text-primary"></i>Output by Production Unit</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Unit Name</th>
                        <th>Programs Run</th>
                        <th>Planned Output</th>
                        <th>Completed Output</th>
                        <th>Completion %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($unitStats)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No unit metrics recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($unitStats as $u): ?>
                            <?php 
                                $uPct = $u['total_planned'] > 0 ? round(($u['total_completed'] / $u['total_planned']) * 100) : 0;
                            ?>
                            <tr>
                                <td class="fw-bold"><?= e($u['unit_name']) ?></td>
                                <td><?= number_format($u['program_count']) ?></td>
                                <td><?= number_format($u['total_planned']) ?> pcs</td>
                                <td class="text-success fw-bold"><?= number_format($u['total_completed']) ?> pcs</td>
                                <td style="min-width: 150px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: <?= min(100, $uPct) ?>%"></div>
                                        </div>
                                        <span class="small fw-bold"><?= $uPct ?>%</span>
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

<!-- Manufacturing Pipeline Queue Status -->
<div class="card">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold"><i class="bi bi-kanban me-2 text-primary"></i>Live Pipeline Work-in-Progress</h5>
    </div>
    <div class="card-body">
        <div class="row text-center g-3">
            <div class="col-md">
                <div class="p-3 bg-light rounded border">
                    <div class="fs-4 fw-bold text-secondary"><?= $stageCounts['draft_programs'] ?? 0 ?></div>
                    <small class="text-muted">Draft Programs</small>
                </div>
            </div>
            <div class="col-md">
                <div class="p-3 bg-light rounded border">
                    <div class="fs-4 fw-bold text-primary"><?= $stageCounts['scheduled_programs'] ?? 0 ?></div>
                    <small class="text-muted">Scheduled</small>
                </div>
            </div>
            <div class="col-md">
                <div class="p-3 bg-light rounded border">
                    <div class="fs-4 fw-bold text-info"><?= $stageCounts['active_cutting'] ?? 0 ?></div>
                    <small class="text-muted">Active Cutting</small>
                </div>
            </div>
            <div class="col-md">
                <div class="p-3 bg-light rounded border">
                    <div class="fs-4 fw-bold text-warning"><?= $stageCounts['active_stitching'] ?? 0 ?></div>
                    <small class="text-muted">Active Stitching</small>
                </div>
            </div>
            <div class="col-md">
                <div class="p-3 bg-light rounded border">
                    <div class="fs-4 fw-bold text-danger"><?= $stageCounts['active_qc'] ?? 0 ?></div>
                    <small class="text-muted">Pending QC</small>
                </div>
            </div>
            <div class="col-md">
                <div class="p-3 bg-light rounded border">
                    <div class="fs-4 fw-bold text-success"><?= $stageCounts['ready_batches'] ?? 0 ?></div>
                    <small class="text-muted">Ready to Dispatch</small>
                </div>
            </div>
        </div>
    </div>
</div>
