<?php
/**
 * Asset overview
 */
$summary = $metrics['summary'] ?? [];
$totalAssets = (int)($summary['total_assets'] ?? 0);
$inUseCount = (int)($summary['in_use_count'] ?? 0);
$inMntCount = (int)($summary['in_maintenance_count'] ?? 0);
$damagedCount = (int)($summary['damaged_count'] ?? 0);
$idleCount = (int)($summary['idle_count'] ?? 0);
$critCount = (int)($summary['critical_condition_count'] ?? 0);
$expiringCount = (int)($summary['warranty_expiring_soon_count'] ?? 0);
$totalBookValue = (float)($summary['total_book_value'] ?? 0.00);
$totalPurchaseCost = (float)($summary['total_purchase_cost'] ?? 0.00);
$utilizationRate = $totalAssets > 0 ? round(($inUseCount / $totalAssets) * 100, 1) : 0;

$facilityBreakdown = $metrics['facilityBreakdown'] ?? [];
$categoryBreakdown = $metrics['categoryBreakdown'] ?? [];
$monthlyTrend = $metrics['monthlyTrend'] ?? [];
$serviceDue = $metrics['serviceDue'] ?? [];
?>

<!-- Chart.js CDN for Interactive Graphs -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
.chart-container-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    padding: 20px;
    height: 100%;
}
.facility-row:hover {
    background-color: #f8fafc;
}
.stat-card-gradient {
    border-radius: 14px;
    border: none;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
}
</style>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-speedometer2 text-primary"></i> Asset Overview
        </h2>
        <span class="text-secondary small">Asset value, current status, and maintenance at a glance.</span>
    </div>
    <div class="d-flex align-items-center flex-wrap gap-2">
        <a href="<?= url('asset/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> New Asset
        </a>
        <a href="<?= url('asset/scanner') ?>" class="btn btn-outline-dark d-inline-flex align-items-center gap-1">
            <i class="bi bi-qr-code-scan me-1 text-primary"></i> QR Scanner
        </a>
        <a href="<?= url('asset/list') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="bi bi-table me-1"></i> Asset Register
        </a>
    </div>
</div>

<!-- 4 High-Impact KPI Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Valuation -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card-gradient border-start border-primary border-4 p-3 h-100 bg-white shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase text-muted fw-semibold small">Total Book Valuation</div>
                    <div class="stat-value fw-bold text-primary mt-1 fs-4 font-monospace"><?= formatCurrency($totalBookValue) ?></div>
                    <div class="small text-muted mt-2">
                        Original Cost: <strong><?= formatCurrency($totalPurchaseCost) ?></strong> &bull; <span class="badge bg-primary-subtle text-primary"><?= $totalAssets ?> Assets</span>
                    </div>
                </div>
                <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Utilization -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card-gradient border-start border-success border-4 p-3 h-100 bg-white shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase text-muted fw-semibold small">Active &amp; Operational</div>
                    <div class="stat-value fw-bold text-success mt-1 fs-4"><?= $inUseCount ?> <span class="fs-6 fw-normal text-secondary">/ <?= $totalAssets ?></span></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-check2-circle text-success me-1"></i>Utilization: <strong class="text-success"><?= $utilizationRate ?>% Active</strong>
                    </div>
                </div>
                <div class="bg-success-subtle text-success p-3 rounded-circle">
                    <i class="bi bi-check2-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance & Repairs -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card-gradient border-start border-warning border-4 p-3 h-100 bg-white shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase text-muted fw-semibold small">Under Service / Repair</div>
                    <div class="stat-value fw-bold text-warning mt-1 fs-4"><?= $inMntCount ?> <span class="fs-6 fw-normal text-secondary">Units</span></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-exclamation-triangle text-danger me-1"></i>Damaged / Defective: <strong class="text-danger"><?= $damagedCount ?></strong>
                    </div>
                </div>
                <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                    <i class="bi bi-tools fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Alerts -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card-gradient border-start border-danger border-4 p-3 h-100 bg-white shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase text-muted fw-semibold small">Service &amp; Warranty Alerts</div>
                    <div class="stat-value fw-bold <?= $critCount > 0 ? 'text-danger' : 'text-primary' ?> mt-1 fs-4"><?= $expiringCount ?> <span class="fs-6 fw-normal text-secondary">Expiring</span></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-shield-exclamation text-danger me-1"></i>Critical Condition: <strong class="<?= $critCount > 0 ? 'text-danger' : 'text-secondary' ?>"><?= $critCount ?> Units</strong>
                    </div>
                </div>
                <div class="bg-danger-subtle text-danger p-3 rounded-circle">
                    <i class="bi bi-shield-exclamation fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- VISUAL GRAPH ANALYTICS SECTION (Chart.js Grid)                           -->
<!-- ========================================================================= -->
<div class="row g-4 mb-4">
    <!-- Graph 1: Unit-Wise Asset Valuation & Distribution -->
    <div class="col-lg-8">
        <div class="chart-container-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-buildings text-primary"></i> Facility Asset Valuation &amp; Unit Breakdown
                    </h5>
                    <span class="text-muted small">Comparison of capital asset investment and counts across all Warehouses, Production Units, and Shops.</span>
                </div>
            </div>
            <div style="height: 320px;">
                <canvas id="chartUnitValuation"></canvas>
            </div>
        </div>
    </div>

    <!-- Graph 2: Operational Status & Condition Donut -->
    <div class="col-lg-4">
        <div class="chart-container-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-pie-chart text-success"></i> Health &amp; Status Split
                    </h5>
                    <span class="text-muted small">Fleet operational status.</span>
                </div>
            </div>
            <div style="height: 250px; position: relative;">
                <canvas id="chartStatusSplit"></canvas>
            </div>
            <div class="d-flex justify-content-around text-center small mt-3 pt-2 border-top">
                <div><span class="badge bg-success mb-1"><?= $inUseCount ?></span><div class="text-muted">In Use</div></div>
                <div><span class="badge bg-warning text-dark mb-1"><?= $inMntCount ?></span><div class="text-muted">Service</div></div>
                <div><span class="badge bg-secondary mb-1"><?= $idleCount ?></span><div class="text-muted">Idle</div></div>
                <div><span class="badge bg-danger mb-1"><?= $damagedCount ?></span><div class="text-muted">Damaged</div></div>
            </div>
        </div>
    </div>

    <!-- Graph 3: Category Breakdown -->
    <div class="col-lg-5">
        <div class="chart-container-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-tags text-info"></i> Category Capital Allocation
                    </h5>
                    <span class="text-muted small">Machinery, IT Hardware, POS Fixtures, HVAC, Vehicles.</span>
                </div>
            </div>
            <div style="height: 280px;">
                <canvas id="chartCategorySplit"></canvas>
            </div>
        </div>
    </div>

    <!-- Graph 4: Monthly Maintenance Cost & Downtime Trend -->
    <div class="col-lg-7">
        <div class="chart-container-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-graph-up-arrow text-warning"></i> Monthly Maintenance Spending &amp; Downtime
                    </h5>
                    <span class="text-muted small">Service expenditure (₹) and cumulative machine downtime hours.</span>
                </div>
            </div>
            <div style="height: 280px;">
                <canvas id="chartMaintenanceTrend"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- FACILITY-BY-FACILITY OVERVIEW BREAKDOWN TABLE                             -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
    <div class="card-header bg-white py-3 px-3 border-0 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-building-check text-primary"></i> Facility-by-Facility Asset Ledger
            </h5>
            <span class="text-muted small">Granular asset count, active uptime, and book valuation per facility.</span>
        </div>
        <a href="<?= url('asset/list') ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
            <i class="bi bi-table"></i> Open Full Asset Table
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-3">Facility / Location</th>
                    <th>Type</th>
                    <th class="text-center">Total Assets</th>
                    <th class="text-center">In Use (Active)</th>
                    <th class="text-center">In Service</th>
                    <th class="text-center">Damaged</th>
                    <th class="text-end">Book Value (₹)</th>
                    <th class="text-end pe-3">Quick Filter</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($facilityBreakdown)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No facility records located.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($facilityBreakdown as $fac): ?>
                        <?php 
                            $unitKey = $fac['type'] === 'PRODUCTION' ? 'PU_' . $fac['id'] : ($fac['type'] === 'WAREHOUSE' ? 'WH_' . $fac['id'] : ($fac['type'] === 'SHOP' ? 'SH_' . $fac['id'] : 'OFFICE'));
                        ?>
                        <tr class="facility-row">
                            <td class="ps-3 fw-bold text-dark"><?= e($fac['label']) ?></td>
                            <td>
                                <span class="badge <?= $fac['type'] === 'PRODUCTION' ? 'bg-primary-subtle text-primary' : ($fac['type'] === 'WAREHOUSE' ? 'bg-info-subtle text-info' : ($fac['type'] === 'SHOP' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary')) ?>">
                                    <?= e($fac['type']) ?>
                                </span>
                            </td>
                            <td class="text-center fw-bold"><?= $fac['asset_count'] ?></td>
                            <td class="text-center text-success fw-semibold"><?= $fac['in_use_count'] ?></td>
                            <td class="text-center text-warning fw-semibold"><?= $fac['in_maintenance'] ?></td>
                            <td class="text-center text-danger fw-semibold"><?= $fac['damaged_count'] ?></td>
                            <td class="text-end font-monospace fw-bold text-dark"><?= formatCurrency($fac['total_value']) ?></td>
                            <td class="text-end pe-3">
                                <a href="<?= url('asset/list?unit=' . urlencode($unitKey)) ?>" class="btn btn-xs btn-outline-secondary py-1 px-2">
                                    <i class="bi bi-filter"></i> View Assets
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js Setup Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Unit Valuation & Asset Counts Bar Chart
    const facilityLabels = <?= json_encode(array_column($facilityBreakdown, 'label')) ?>;
    const facilityValues = <?= json_encode(array_column($facilityBreakdown, 'total_value')) ?>;
    const facilityCounts = <?= json_encode(array_column($facilityBreakdown, 'asset_count')) ?>;

    const ctxUnit = document.getElementById('chartUnitValuation');
    if (ctxUnit) {
        new Chart(ctxUnit, {
            type: 'bar',
            data: {
                labels: facilityLabels,
                datasets: [
                    {
                        label: 'Book Value (₹)',
                        data: facilityValues,
                        backgroundColor: 'rgba(59, 130, 246, 0.75)',
                        borderColor: '#2563eb',
                        borderWidth: 1.5,
                        borderRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Asset Count',
                        data: facilityCounts,
                        type: 'line',
                        borderColor: '#10b981',
                        backgroundColor: '#10b981',
                        borderWidth: 2.5,
                        pointRadius: 4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: {
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            callback: function(val) {
                                return '₹' + (val >= 1000 ? (val / 1000).toFixed(0) + 'k' : val);
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    // 2. Operational Status Split Donut
    const ctxStatus = document.getElementById('chartStatusSplit');
    if (ctxStatus) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['In Use (Active)', 'Under Maintenance', 'Idle Reserve', 'Damaged'],
                datasets: [{
                    data: [<?= $inUseCount ?>, <?= $inMntCount ?>, <?= $idleCount ?>, <?= $damagedCount ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#64748b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                },
                cutout: '65%'
            }
        });
    }

    // 3. Category Breakdown Doughnut / Bar
    const catLabels = <?= json_encode(array_column($categoryBreakdown, 'name')) ?>;
    const catValues = <?= json_encode(array_column($categoryBreakdown, 'total_value')) ?>;

    const ctxCat = document.getElementById('chartCategorySplit');
    if (ctxCat) {
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catValues,
                    backgroundColor: ['#3b82f6', '#8b5cf6', '#06b6d4', '#f59e0b', '#10b981', '#ec4899'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const val = context.raw || 0;
                                return ' ' + context.label + ': ₹' + Number(val).toLocaleString('en-IN');
                            }
                        }
                    }
                },
                cutout: '55%'
            }
        });
    }

    // 4. Monthly Maintenance & Downtime Trend
    const trendLabels = <?= json_encode(array_column($monthlyTrend, 'month_label')) ?>;
    const trendCosts = <?= json_encode(array_column($monthlyTrend, 'total_cost')) ?>;
    const trendDowntimes = <?= json_encode(array_column($monthlyTrend, 'total_downtime')) ?>;

    const ctxMnt = document.getElementById('chartMaintenanceTrend');
    if (ctxMnt) {
        new Chart(ctxMnt, {
            type: 'bar',
            data: {
                labels: trendLabels.length ? trendLabels : ['Current Month'],
                datasets: [
                    {
                        label: 'Maintenance Cost (₹)',
                        data: trendCosts.length ? trendCosts : [0],
                        backgroundColor: 'rgba(245, 158, 11, 0.75)',
                        borderColor: '#d97706',
                        borderRadius: 6,
                        yAxisID: 'yCost'
                    },
                    {
                        label: 'Downtime (Hours)',
                        data: trendDowntimes.length ? trendDowntimes : [0],
                        type: 'line',
                        borderColor: '#ef4444',
                        backgroundColor: '#ef4444',
                        borderWidth: 2,
                        pointRadius: 4,
                        yAxisID: 'yHours'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yCost: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            callback: function(val) { return '₹' + val; }
                        }
                    },
                    yHours: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: {
                            callback: function(val) { return val + 'h'; }
                        }
                    }
                }
            }
        });
    }
});
</script>
