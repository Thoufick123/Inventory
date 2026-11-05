<?php
// views/reports/overview.php
// Executive Reporting & P&L Command Center — with Revenue Graph & Auto-Filter
$reportType = 'overview';

// Prepare chart data from PHP for JavaScript
$chartLabels = [];
$chartRevenue = [];
$chartExpenses = [];
$chartLoss = [];
$chartProfit = [];
if (!empty($monthlyTrend)) {
    foreach ($monthlyTrend as $mt) {
        $chartLabels[]  = $mt['month_name'];
        $chartRevenue[] = round($mt['revenue'], 2);
        $chartExpenses[]= round($mt['expense'], 2);
        $chartLoss[]    = round($mt['loss'], 2);
        $chartProfit[]  = round($mt['net_profit'], 2);
    }
}
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold text-dark">
            <i class="bi bi-pie-chart-fill text-primary me-2"></i>Executive Reports &amp; P&amp;L Dashboard
        </h4>
        <div class="text-muted small mt-1">Financial performance, revenue inflows, cost deductions, and net profitability overview</div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. REVENUE TREND GRAPH — 12-MONTH INTERACTIVE CHART -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white overflow-hidden">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-graph-up text-primary me-2"></i>Revenue & Profit Trend — Year <?= e($filter['year']) ?>
            </h6>
            <small class="text-muted">Month-by-month revenue, expenses, losses, and net profit visualization</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-xs btn-outline-primary px-2 py-1 active" data-chart-type="line" onclick="switchChartType('line', this)" style="font-size: 0.75rem;">
                <i class="bi bi-graph-up me-1"></i>Line
            </button>
            <button class="btn btn-xs btn-outline-primary px-2 py-1" data-chart-type="bar" onclick="switchChartType('bar', this)" style="font-size: 0.75rem;">
                <i class="bi bi-bar-chart me-1"></i>Bar
            </button>
        </div>
    </div>
    <div class="card-body p-3">
        <div style="position: relative; height: 320px;">
            <canvas id="revenueTrendChart"></canvas>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. NET PROFIT / EARNINGS HIGHLIGHT BANNER -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4 text-white overflow-hidden" 
     style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); border: 1px solid #334155 !important;">
    <div class="card-body p-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">Executive P&amp;L Summary</span>
                    <span class="text-white-50 small">&bull; Filter: <?= e($filter['label']) ?></span>
                </div>
                <h3 class="fw-bold mb-1">
                    Net Profit / Earnings: 
                    <span class="<?= $pnl['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?> font-monospace">
                        ₹<?= number_format($pnl['net_profit'], 2) ?>
                    </span>
                </h3>
                <div class="text-white-50 small mt-1">
                    Calculated as: Total Gross Revenue (₹<?= number_format($pnl['gross_revenue'], 2) ?>) &minus; Direct Material/Production Cost (₹<?= number_format($pnl['direct_cost'], 2) ?>) &minus; Operating Expenses (₹<?= number_format($pnl['operating_expenses'], 2) ?>) &minus; Defect &amp; Damage Losses (₹<?= number_format($pnl['recorded_losses'], 2) ?>).
                </div>
            </div>
            <div class="col-lg-6">
                <div class="row g-2 text-center">
                    <div class="col-6 col-sm-3">
                        <div class="bg-dark bg-opacity-50 p-2.5 rounded-3 border border-secondary border-opacity-25">
                            <div class="text-white-50 small" style="font-size: 0.72rem;">GROSS PROFIT</div>
                            <div class="fw-bold text-white mt-1 font-monospace" style="font-size: 0.95rem;">₹<?= number_format($pnl['gross_profit'], 2) ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="bg-dark bg-opacity-50 p-2.5 rounded-3 border border-secondary border-opacity-25">
                            <div class="text-white-50 small" style="font-size: 0.72rem;">GROSS MARGIN</div>
                            <div class="fw-bold text-success mt-1 font-monospace" style="font-size: 0.95rem;"><?= $pnl['gross_margin_pct'] ?>%</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="bg-dark bg-opacity-50 p-2.5 rounded-3 border border-secondary border-opacity-25">
                            <div class="text-white-50 small" style="font-size: 0.72rem;">NET MARGIN</div>
                            <div class="fw-bold <?= $pnl['net_margin_pct'] >= 0 ? 'text-success' : 'text-danger' ?> mt-1 font-monospace" style="font-size: 0.95rem;">
                                <?= $pnl['net_margin_pct'] ?>%
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="bg-dark bg-opacity-50 p-2.5 rounded-3 border border-secondary border-opacity-25">
                            <div class="text-white-50 small" style="font-size: 0.72rem;">TAX (GST)</div>
                            <div class="fw-bold text-warning mt-1 font-monospace" style="font-size: 0.95rem;">₹<?= number_format($pnl['total_tax'], 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. FILTER BAR (Auto-Submit, No Apply Button) -->
<!-- ========================================================================= -->
<?php require __DIR__ . '/partials/filter_bar.php'; ?>

<!-- ========================================================================= -->
<!-- 4. EXECUTIVE P&L FINANCIAL SCOREBOARD -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Gross Revenue -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 0.5px;">Gross Revenue</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($pnl['gross_revenue'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-receipt me-1"></i><?= $pnl['invoices_count'] ?> Orders &bull; Wastage: ₹<?= number_format($pnl['wastage_revenue'], 2) ?>
                    </div>
                </div>
                <div class="bg-success text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Direct Costs (COGS) -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Direct Production / Purchases</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($pnl['direct_cost'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-gear me-1"></i>Batch Cost: ₹<?= number_format($pnl['production_cost'], 2) ?>
                    </div>
                </div>
                <div class="bg-primary text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-boxes fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Operating & Facility Expenses -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #d97706 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-warning" style="font-size: 0.72rem; letter-spacing: 0.5px;">Operating Expenses</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($pnl['operating_expenses'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-lightning-charge me-1"></i>Units: ₹<?= number_format($pnl['production_expenses'], 2) ?> &bull; Shop: ₹<?= number_format($pnl['shop_expenses'], 2) ?>
                    </div>
                </div>
                <div class="bg-warning text-dark p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-receipt-cutoff fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recorded Losses & Damages -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-left: 4px solid #dc2626 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-danger" style="font-size: 0.72rem; letter-spacing: 0.5px;">Damage &amp; Scrap Losses</div>
                    <div class="fs-4 fw-bold text-danger mt-1 font-monospace">₹<?= number_format($pnl['recorded_losses'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-trash3 me-1"></i>WH: ₹<?= number_format($pnl['wh_damage_loss'], 2) ?> &bull; Roll: ₹<?= number_format($pnl['roll_damage_loss'], 2) ?>
                    </div>
                </div>
                <div class="bg-danger text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 5. DETAILED REVENUE & LOSS BREAKDOWN -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Revenue Stream Details -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-header bg-white py-3 px-3.5 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-graph-up-arrow text-success me-2"></i>Revenue Inflow Streams
                </h6>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    ₹<?= number_format($pnl['gross_revenue'], 2) ?>
                </span>
            </div>
            <div class="card-body p-3">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-2.5 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle p-2"><i class="bi bi-buildings"></i></span>
                            <div>
                                <div class="fw-semibold text-dark">Warehouse Direct Customer Sales</div>
                                <small class="text-muted">POS walk-in purchases &amp; B2C orders</small>
                            </div>
                        </div>
                        <div class="text-end font-monospace fw-bold text-dark">
                            ₹<?= number_format($pnl['wh_sales_revenue'], 2) ?>
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-2.5 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-info rounded-circle p-2"><i class="bi bi-shop"></i></span>
                            <div>
                                <div class="fw-semibold text-dark">Retail Shop Outlets POS</div>
                                <small class="text-muted">In-store customer invoices &amp; counter billing</small>
                            </div>
                        </div>
                        <div class="text-end font-monospace fw-bold text-dark">
                            ₹<?= number_format($pnl['shop_sales_revenue'], 2) ?>
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-2.5 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark rounded-circle p-2"><i class="bi bi-scissors"></i></span>
                            <div>
                                <div class="fw-semibold text-dark">Cutting Scrap / Wastage Sales</div>
                                <small class="text-muted">Fabric bits &amp; edge scrap liquidation</small>
                            </div>
                        </div>
                        <div class="text-end font-monospace fw-bold text-dark">
                            ₹<?= number_format($pnl['cutting_wastage_rev'], 2) ?>
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary rounded-circle p-2"><i class="bi bi-threads"></i></span>
                            <div>
                                <div class="fw-semibold text-dark">Stitching Waste Sales</div>
                                <small class="text-muted">Thread, cut-piece &amp; trim scrap revenue</small>
                            </div>
                        </div>
                        <div class="text-end font-monospace fw-bold text-dark">
                            ₹<?= number_format($pnl['stitch_wastage_rev'], 2) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Losses & Cost Outflows -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-header bg-white py-3 px-3.5 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-graph-down-arrow text-danger me-2"></i>Expenses, Cost &amp; Loss Breakdown
                </h6>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                    ₹<?= number_format($pnl['direct_cost'] + $pnl['operating_expenses'] + $pnl['recorded_losses'], 2) ?>
                </span>
            </div>
            <div class="card-body p-3">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-2.5 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary rounded-circle p-2"><i class="bi bi-gear-wide"></i></span>
                            <div>
                                <div class="fw-semibold text-dark">Direct Production Cost (COGS)</div>
                                <small class="text-muted">Material consumption, roll price &amp; production batches</small>
                            </div>
                        </div>
                        <div class="text-end font-monospace fw-bold text-dark">
                            ₹<?= number_format($pnl['production_cost'], 2) ?>
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-2.5 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning-subtle text-warning rounded-circle p-2"><i class="bi bi-lightning-charge"></i></span>
                            <div>
                                <div class="fw-semibold text-dark">Production Facilities Operating Expenses</div>
                                <small class="text-muted">Electricity, machinery, maintenance &amp; factory overheads</small>
                            </div>
                        </div>
                        <div class="text-end font-monospace fw-bold text-dark">
                            ₹<?= number_format($pnl['production_expenses'], 2) ?>
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-2.5 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger-subtle text-danger rounded-circle p-2"><i class="bi bi-trash3"></i></span>
                            <div>
                                <div class="fw-semibold text-dark">Warehouse Damaged &amp; Defective Goods</div>
                                <small class="text-muted">Defects, stitching flaws &amp; customer return damage writes</small>
                            </div>
                        </div>
                        <div class="text-end font-monospace fw-bold text-danger">
                            ₹<?= number_format($pnl['wh_damage_loss'] + $pnl['return_defect_loss'], 2) ?>
                        </div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary-subtle text-secondary rounded-circle p-2"><i class="bi bi-circle-square"></i></span>
                            <div>
                                <div class="fw-semibold text-dark">Fabric Roll Defect Losses</div>
                                <small class="text-muted">Damaged raw fabric rolls received from sellers</small>
                            </div>
                        </div>
                        <div class="text-end font-monospace fw-bold text-danger">
                            ₹<?= number_format($pnl['roll_damage_loss'], 2) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 6. 12-MONTH PERFORMANCE TREND TABLE -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-header bg-white py-3 px-3.5 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-calendar3 text-primary me-2"></i>12-Month Financial Performance Trend (Year <?= e($filter['year']) ?>)
            </h6>
            <small class="text-muted">Month-by-month revenue inflow, operational outflow, defect loss, and net profit</small>
        </div>
        <a href="<?= url("reports/profitability?period=yearly&year={$filter['year']}") ?>" class="btn btn-xs btn-outline-primary fw-semibold">
            View Yearly P&amp;L &rarr;
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.86rem;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Month</th>
                    <th class="text-end">Gross Revenue</th>
                    <th class="text-end">Expenses</th>
                    <th class="text-end">Losses</th>
                    <th class="text-end pe-3">Net Profit</th>
                    <th style="min-width: 140px;">Profit Visualizer</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $revValues = !empty($monthlyTrend) ? array_column($monthlyTrend, 'revenue') : [0];
                $maxRev = max(array_merge([1], $revValues));
                foreach ($monthlyTrend as $m): 
                    $pct = min(100, round(($m['revenue'] / $maxRev) * 100));
                    $isProfitable = $m['net_profit'] >= 0;
                ?>
                <tr>
                    <td class="ps-3 fw-bold text-dark">
                        <a href="?filter_type=monthly&month=<?= $m['month_num'] ?>&year=<?= $filter['year'] ?>" class="text-decoration-none">
                            <?= $m['month_name'] ?> <?= $filter['year'] ?>
                        </a>
                    </td>
                    <td class="text-end font-monospace fw-semibold text-success">₹<?= number_format($m['revenue'], 2) ?></td>
                    <td class="text-end font-monospace text-secondary">₹<?= number_format($m['expense'], 2) ?></td>
                    <td class="text-end font-monospace text-danger">₹<?= number_format($m['loss'], 2) ?></td>
                    <td class="text-end font-monospace fw-bold pe-3 <?= $isProfitable ? 'text-success' : 'text-danger' ?>">
                        ₹<?= number_format($m['net_profit'], 2) ?>
                    </td>
                    <td>
                        <div class="progress" style="height: 7px;">
                            <div class="progress-bar <?= $isProfitable ? 'bg-success' : 'bg-danger' ?>" role="progressbar" style="width: <?= max(5, $pct) ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 7. MODULE QUICK-ANALYTICS CARDS -->
<!-- ========================================================================= -->
<div class="row g-3">
    <!-- Production Quick Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Manufacturing Output</span>
                <a href="<?= url('reports/production') ?>" class="small text-primary text-decoration-none fw-semibold">View Report &rarr;</a>
            </div>
            <div class="fs-4 fw-bold text-dark font-monospace"><?= number_format($productionSummary['completed_qty']) ?> pcs</div>
            <div class="text-muted small">Total Finished Units Completed</div>
            <hr class="my-2">
            <div class="d-flex justify-content-between small text-secondary">
                <span>Cut Pieces: <strong><?= number_format($productionSummary['cut_qty']) ?></strong></span>
                <span>QC Pass Rate: <strong class="text-success"><?= $productionSummary['qc_pass_rate'] ?>%</strong></span>
            </div>
        </div>
    </div>

    <!-- Warehouse & Logistics Quick Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle">Warehouse Logistics</span>
                <a href="<?= url('reports/warehouse') ?>" class="small text-success text-decoration-none fw-semibold">View Report &rarr;</a>
            </div>
            <div class="fs-4 fw-bold text-dark font-monospace"><?= number_format($warehouseSummary['allocated_qty']) ?> pcs</div>
            <div class="text-muted small">Dispatched to Retail Outlets</div>
            <hr class="my-2">
            <div class="d-flex justify-content-between small text-secondary">
                <span>Damage Cases: <strong><?= $warehouseSummary['damages_count'] ?></strong></span>
                <span>Damage Loss: <strong class="text-danger">₹<?= number_format($warehouseSummary['damage_loss'], 2) ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Current Stock Valuation Quick Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-info-subtle text-info border border-info-subtle">Active Stock Valuation</span>
                <a href="<?= url('reports/inventory') ?>" class="small text-info text-decoration-none fw-semibold">Inventory &rarr;</a>
            </div>
            <div class="fs-4 fw-bold text-dark font-monospace">₹<?= number_format($inventorySummary['total_valuation'], 2) ?></div>
            <div class="text-muted small"><?= number_format($inventorySummary['total_stock_qty']) ?> Total Units in Stock</div>
            <hr class="my-2">
            <div class="d-flex justify-content-between small text-secondary">
                <span>Warehouse: <strong><?= number_format($inventorySummary['wh_qty']) ?> pcs</strong></span>
                <span>Shops: <strong><?= number_format($inventorySummary['shop_qty']) ?> pcs</strong></span>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- CHART.JS INITIALIZATION -->
<!-- ========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartLabels = <?= json_encode($chartLabels) ?>;
    const chartRevenue = <?= json_encode($chartRevenue) ?>;
    const chartExpenses = <?= json_encode($chartExpenses) ?>;
    const chartLoss = <?= json_encode($chartLoss) ?>;
    const chartProfit = <?= json_encode($chartProfit) ?>;

    const ctx = document.getElementById('revenueTrendChart');
    if (!ctx) return;

    // Format large numbers as ₹ with K/L suffixes
    function formatCurrency(value) {
        if (Math.abs(value) >= 100000) return '₹' + (value / 100000).toFixed(1) + 'L';
        if (Math.abs(value) >= 1000) return '₹' + (value / 1000).toFixed(1) + 'K';
        return '₹' + value.toFixed(0);
    }

    window.__trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'Revenue',
                    data: chartRevenue,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#16a34a',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    order: 1
                },
                {
                    label: 'Expenses',
                    data: chartExpenses,
                    borderColor: '#d97706',
                    backgroundColor: 'rgba(217, 119, 6, 0.06)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#d97706',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    borderDash: [5, 3],
                    order: 2
                },
                {
                    label: 'Losses',
                    data: chartLoss,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.06)',
                    borderWidth: 1.5,
                    fill: false,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#dc2626',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    borderDash: [3, 3],
                    order: 3
                },
                {
                    label: 'Net Profit',
                    data: chartProfit,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.10)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#2563eb',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    order: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16,
                        font: { family: "'Inter', sans-serif", size: 12, weight: '500' }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.92)',
                    titleColor: '#e2e8f0',
                    bodyColor: '#f1f5f9',
                    borderColor: '#334155',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12,
                    titleFont: { family: "'Inter', sans-serif", size: 13, weight: '600' },
                    bodyFont: { family: "'Inter', sans-serif", size: 12 },
                    callbacks: {
                        label: function(context) {
                            return '  ' + context.dataset.label + ': ₹' + context.parsed.y.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { family: "'Inter', sans-serif", size: 11, weight: '500' },
                        color: '#64748b'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(100, 116, 139, 0.08)', drawBorder: false },
                    ticks: {
                        callback: formatCurrency,
                        font: { family: "'Inter', sans-serif", size: 11 },
                        color: '#94a3b8',
                        maxTicksLimit: 7
                    }
                }
            }
        }
    });
});

// Switch between Line and Bar chart types
function switchChartType(type, btn) {
    if (!window.__trendChart) return;
    window.__trendChart.config.type = type;
    // Adjust visual for bar mode
    window.__trendChart.data.datasets.forEach(ds => {
        if (type === 'bar') {
            ds.borderWidth = 0;
            ds.borderRadius = 4;
            ds.borderDash = undefined;
        } else {
            ds.borderWidth = ds.label === 'Revenue' || ds.label === 'Net Profit' ? 2.5 : (ds.label === 'Expenses' ? 2 : 1.5);
            ds.borderRadius = undefined;
        }
    });
    window.__trendChart.update();
    // Toggle active button state
    document.querySelectorAll('[data-chart-type]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
</script>
