<?php
// views/shop/dashboard.php — Retail Shop Dashboard with Dynamic Week/Month/Custom Date Range Filtering & Interactive Revenue Analytics
$currFilter = $current_filter ?? [];
$activePeriod = $currFilter['period'] ?? 'today';
$activeStartDate = $currFilter['start_date'] ?? date('Y-m-d');
$activeEndDate = $currFilter['end_date'] ?? date('Y-m-d');
$filterTitle = $currFilter['period_title'] ?? "Today's Sales";
$filterSubtitle = $currFilter['period_subtitle'] ?? "Invoices Today";
$filterRevenue = $currFilter['total_revenue'] ?? $today_revenue;
$filterInvoices = $currFilter['total_invoices'] ?? $today_invoices;
$filterCash = $currFilter['cash_total'] ?? $cash_total;
$filterUpi = $currFilter['upi_total'] ?? $upi_total;
$filterDigital = $currFilter['digital_total'] ?? ($upi_total + $card_total);
$filterAov = $currFilter['avg_order_value'] ?? $avg_order_value_today;
$filterLowStock = $currFilter['low_stock_count'] ?? $low_stock_count;
?>
<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="badge bg-primary-subtle text-primary mb-1">
                <i class="bi bi-shop me-1"></i><?= e($shop['name']) ?> (<?= e($shop['code']) ?>)
            </div>
            <h3 class="fw-bold mb-1 text-dark">Retail Shop Dashboard</h3>
            <p class="text-muted small mb-0">Live billing analytics, multi-period revenue filters, payment breakdowns, and quick retail operations.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (hasPermission('sales', 'view')): ?>
            <a href="<?= url('shop/sales') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <i class="bi bi-cart-check me-1"></i> Selled Products
            </a>
            <?php endif; ?>
            <?php if (hasPermission('shop_stock', 'view')): ?>
            <a href="<?= url('shop/inventory') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-boxes me-1"></i> Shop Stock
            </a>
            <?php endif; ?>
            <?php if (hasPermission('shop_returns', 'view')): ?>
            <a href="<?= url('shop/returns') ?>" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Customer Returns
            </a>
            <?php endif; ?>
            <?php if (hasPermission('return_to_warehouse', 'view')): ?>
            <a href="<?= url('shop/return-to-warehouse') ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                <i class="bi bi-arrow-return-left me-1"></i> Return to WH
            </a>
            <?php endif; ?>
            <?php if (hasPermission('pos', 'view') || hasPermission('pos', 'create')): ?>
            <a href="<?= url('shop/pos') ?>" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">
                <i class="bi bi-qr-code-scan me-1"></i> Open POS Terminal
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- DYNAMIC FILTER TOOLBAR (TODAY / WEEK / MONTH / CUSTOM RANGE) -->
    <!-- ============================================================ -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                <!-- Left: Preset Buttons -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted small fw-semibold me-1 d-none d-sm-inline">
                        <i class="bi bi-funnel-fill me-1 text-primary"></i>Filter Cards:
                    </span>
                    <div class="btn-group p-1 bg-light rounded-pill border" role="group" id="mainFilterBtnGroup">
                        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $activePeriod === 'today' ? 'active btn-primary' : 'text-secondary' ?>" data-period="today" onclick="applyMetricFilter('today')">
                            <i class="bi bi-sun me-1"></i>Today
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $activePeriod === 'week' ? 'active btn-primary' : 'text-secondary' ?>" data-period="week" onclick="applyMetricFilter('week')">
                            <i class="bi bi-calendar-week me-1"></i>This Week
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $activePeriod === 'month' ? 'active btn-primary' : 'text-secondary' ?>" data-period="month" onclick="applyMetricFilter('month')">
                            <i class="bi bi-calendar-month me-1"></i>This Month
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $activePeriod === 'custom' ? 'active btn-primary' : 'text-secondary' ?>" data-period="custom" id="customFilterToggleBtn" onclick="toggleCustomDateForm()">
                            <i class="bi bi-calendar-range me-1"></i>Custom Range <i class="bi bi-chevron-down ms-1 small"></i>
                        </button>
                    </div>
                </div>

                <!-- Right: Active Period Badge -->
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2 small shadow-none" id="activeFilterBadge">
                        <i class="bi bi-calendar3 me-1 text-primary"></i>
                        <span id="activeFilterBadgeText">Showing: <?= e($filterSubtitle) ?> (<?= formatDate($activeStartDate, 'd M') ?> – <?= formatDate($activeEndDate, 'd M Y') ?>)</span>
                    </span>
                    <div id="filterLoadingSpinner" class="spinner-border spinner-border-sm text-primary" style="display: none;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Expandable Custom Date Range Picker Container -->
            <div id="customDateRangeBox" class="mt-3 pt-3 border-top" style="<?= $activePeriod === 'custom' ? 'display: block;' : 'display: none;' ?>">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-sm-auto">
                        <small class="text-muted fw-semibold">From Date:</small>
                        <input type="date" class="form-control form-control-sm rounded-3 mt-1" id="filterStartDate" value="<?= e($activeStartDate) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-12 col-sm-auto">
                        <small class="text-muted fw-semibold">To Date:</small>
                        <input type="date" class="form-control form-control-sm rounded-3 mt-1" id="filterEndDate" value="<?= e($activeEndDate) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-12 col-sm-auto mt-sm-4 pt-sm-1 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" onclick="applyCustomDateFilter()">
                            <i class="bi bi-check2-circle me-1"></i> Apply Custom Range
                        </button>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="applyMetricFilter('today')">
                            Reset to Today
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- METRIC SUMMARY CARDS (DYNAMICALLY UPDATED) -->
    <!-- ============================================================ -->
    <div class="row g-3 mb-4" id="kpiCardsContainer">
        <!-- Sales Revenue -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-primary h-100 kpi-card position-relative overflow-hidden">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3 flex-shrink-0">
                        <i class="bi bi-currency-rupee fs-4"></i>
                    </div>
                    <div class="overflow-hidden flex-grow-1">
                        <div class="text-muted small fw-semibold text-uppercase text-truncate" id="cardSalesTitle"><?= e($filterTitle) ?></div>
                        <div class="fs-4 fw-bold text-dark text-truncate" id="cardSalesAmount"><?= formatCurrency((float)$filterRevenue) ?></div>
                        <div class="small text-muted text-truncate" id="cardSalesSubtitle"><?= $filterInvoices ?> Invoices</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash Collected -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-success h-100 kpi-card position-relative overflow-hidden">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success-subtle text-success p-3 me-3 flex-shrink-0">
                        <i class="bi bi-wallet2 fs-4"></i>
                    </div>
                    <div class="overflow-hidden flex-grow-1">
                        <div class="text-muted small fw-semibold text-uppercase text-truncate" id="cardCashTitle">Cash Collected</div>
                        <div class="fs-4 fw-bold text-dark text-truncate" id="cardCashAmount"><?= formatCurrency((float)$filterCash) ?></div>
                        <div class="small text-muted text-truncate" id="cardCashSubtitle">In-store Cash</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- UPI / Digital -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-info h-100 kpi-card position-relative overflow-hidden">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info-subtle text-info p-3 me-3 flex-shrink-0">
                        <i class="bi bi-qr-code fs-4"></i>
                    </div>
                    <div class="overflow-hidden flex-grow-1">
                        <div class="text-muted small fw-semibold text-uppercase text-truncate" id="cardDigitalTitle">UPI / Digital</div>
                        <div class="fs-4 fw-bold text-dark text-truncate" id="cardDigitalAmount"><?= formatCurrency((float)$filterDigital) ?></div>
                        <div class="small text-muted text-truncate" id="cardDigitalSubtitle">Online / QR / Card</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Order Value (AOV) -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-warning h-100 kpi-card position-relative overflow-hidden">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning-subtle text-warning p-3 me-3 flex-shrink-0">
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                    </div>
                    <div class="overflow-hidden flex-grow-1">
                        <div class="text-muted small fw-semibold text-uppercase text-truncate" id="cardAovTitle">Avg Order Value</div>
                        <div class="fs-4 fw-bold text-dark text-truncate" id="cardAovAmount"><?= formatCurrency((float)$filterAov) ?></div>
                        <div class="small text-muted text-truncate" id="cardAovSubtitle">Average bill size</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-danger h-100 kpi-card position-relative overflow-hidden">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-danger-subtle text-danger p-3 me-3 flex-shrink-0">
                        <i class="bi bi-exclamation-triangle fs-4"></i>
                    </div>
                    <div class="overflow-hidden flex-grow-1">
                        <div class="text-muted small fw-semibold text-uppercase text-truncate" id="cardStockTitle">Low Stock Alert</div>
                        <div class="fs-4 fw-bold text-danger text-truncate" id="cardStockAmount"><?= $filterLowStock ?> Items</div>
                        <div class="small text-muted text-truncate" id="cardStockSubtitle">Below threshold</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- REVENUE ANALYTICS & INTERACTIVE CHART SECTION -->
    <!-- ============================================================ -->
    <div class="row g-4 mb-4">
        <!-- Main Line/Bar Revenue Chart -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">
                            <i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Revenue & Sales Analytics
                        </h5>
                        <p class="text-muted small mb-0" id="chartSubtitle">Daily revenue and transaction performance trend</p>
                    </div>

                    <!-- Period Switcher Pills -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="btn-group btn-group-sm p-1 bg-light rounded-pill border" role="group" id="chartPeriodButtonGroup">
                            <button type="button" class="btn btn-sm rounded-pill px-3 active fw-semibold" data-chart-period="7days" onclick="switchRevenuePeriod('7days', this)">7 Days</button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold text-secondary" data-chart-period="30days" onclick="switchRevenuePeriod('30days', this)">30 Days</button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold text-secondary" data-chart-period="6months" onclick="switchRevenuePeriod('6months', this)">6 Months</button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold text-secondary" data-chart-period="hourly" onclick="switchRevenuePeriod('hourly', this)">Hourly Today</button>
                        </div>
                        
                        <!-- Toggle Metric Mode (Revenue vs Invoices) -->
                        <div class="btn-group btn-group-sm p-1 bg-light rounded-pill border">
                            <button type="button" class="btn btn-sm rounded-pill px-2 active fw-semibold" id="metricRevBtn" onclick="toggleMetricMode('revenue')" title="Show Revenue Amount">₹</button>
                            <button type="button" class="btn btn-sm rounded-pill px-2 fw-semibold text-secondary" id="metricCountBtn" onclick="toggleMetricMode('invoices')" title="Show Invoice Volume">Qty</button>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-0 pb-3">
                    <!-- Quick KPI Pill Bar over the Chart -->
                    <div class="row g-2 mb-3 p-3 bg-light rounded-4">
                        <div class="col-4 text-center border-end">
                            <small class="text-muted d-block">Period Revenue</small>
                            <span class="fw-bold text-dark fs-6" id="statPeriodTotal">₹0.00</span>
                        </div>
                        <div class="col-4 text-center border-end">
                            <small class="text-muted d-block">Avg / Day</small>
                            <span class="fw-bold text-primary fs-6" id="statPeriodAvg">₹0.00</span>
                        </div>
                        <div class="col-4 text-center">
                            <small class="text-muted d-block">Peak Spike</small>
                            <span class="fw-bold text-success fs-6" id="statPeriodPeak">₹0.00</span>
                        </div>
                    </div>

                    <!-- Canvas Container -->
                    <div class="position-relative" style="min-height: 280px; height: 320px;">
                        <canvas id="shopRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Breakdown & Top Selling Styles -->
        <div class="col-lg-4 d-flex flex-column gap-4">
            <!-- Payment Mode Doughnut -->
            <div class="card border-0 shadow-sm rounded-4 flex-grow-1">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-pie-chart-fill me-2 text-info"></i>Payment Modes
                    </h6>
                    <span class="badge bg-light text-dark border rounded-pill small" id="paymentDonutBadge">Current Filter</span>
                </div>
                <div class="card-body pt-1 pb-3">
                    <div class="position-relative d-flex justify-content-center align-items-center" style="height: 180px;">
                        <canvas id="paymentDonutChart"></canvas>
                    </div>

                    <!-- Custom Doughnut Legend Breakdown Container -->
                    <div class="mt-3 pt-2 border-top" id="paymentLegendContainer">
                        <!-- Rendered dynamically via JS or PHP fallback -->
                    </div>
                </div>
            </div>

            <!-- Top Selling Garments (Month) -->
            <div class="card border-0 shadow-sm rounded-4" id="topSellingCard">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-trophy-fill me-2 text-warning"></i>Top Selling Garments
                    </h6>
                    <span class="badge bg-success-subtle text-success rounded-pill small">Top 5</span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush rounded-bottom-4" id="topSellingListContainer">
                        <?php if (empty($top_selling_products)): ?>
                            <li class="list-group-item text-center py-3 text-muted small">No product sales in period</li>
                        <?php else: ?>
                            <?php foreach ($top_selling_products as $idx => $tsp): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 border-0 border-bottom">
                                <div class="d-flex align-items-center me-2 overflow-hidden">
                                    <span class="badge <?= $idx === 0 ? 'bg-warning text-dark' : ($idx === 1 ? 'bg-secondary' : 'bg-light text-dark border') ?> rounded-circle p-2 me-2" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 11px;">
                                        <?= $idx + 1 ?>
                                    </span>
                                    <div class="overflow-hidden">
                                        <div class="fw-semibold text-dark text-truncate small"><?= e($tsp['product_name']) ?></div>
                                        <span class="badge bg-light text-dark border font-monospace mt-0.5" style="font-size: 10px;">
                                            <i class="bi bi-qr-code text-primary me-1"></i><?= e($tsp['sku']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <span class="badge bg-primary-subtle text-primary rounded-pill"><?= (int)$tsp['total_sold_qty'] ?> pcs</span>
                                    <small class="text-muted d-block"><?= formatCurrency((float)$tsp['total_sold_amount']) ?></small>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- RECENT INVOICES & QUICK ACTION SHORTCUTS -->
    <!-- ============================================================ -->
    <div class="row g-4">
        <!-- Recent Invoices Table -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-receipt me-2 text-primary"></i>Today's Recent Invoices
                    </h5>
                    <?php if (hasPermission('pos', 'view') || hasPermission('pos', 'create')): ?>
                    <a href="<?= url('shop/pos') ?>" class="btn btn-sm btn-link text-decoration-none">New Sale <i class="bi bi-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Invoice #</th>
                                <th>Customer</th>
                                <th>Payment Mode</th>
                                <th>Amount</th>
                                <th>Time</th>
                                <th class="text-end pe-4">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_sales)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-receipt-cutoff fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        No sales recorded yet today. Click "Open POS Terminal" to scan your first product.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_sales as $sale): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">
                                            <a href="<?= url('shop/pos/invoice/' . $sale['id']) ?>" target="_blank" class="text-decoration-none">
                                                <?= e($sale['invoice_number']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($sale['customer_name'] ?? 'Walk-in Customer') ?></div>
                                            <?php if ($sale['customer_phone']): ?>
                                                <small class="text-muted"><?= e($sale['customer_phone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $pm = strtoupper($sale['payment_mode'] ?? 'CASH');
                                            $pmBadge = match($pm) {
                                                'CASH' => 'bg-success-subtle text-success border-success-subtle',
                                                'UPI'  => 'bg-info-subtle text-info border-info-subtle',
                                                'CARD' => 'bg-primary-subtle text-primary border-primary-subtle',
                                                default => 'bg-light text-dark border',
                                            };
                                            ?>
                                            <span class="badge border rounded-pill px-2 py-1 <?= $pmBadge ?>">
                                                <?= e($sale['payment_mode']) ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-dark"><?= formatCurrency((float)$sale['total_amount']) ?></td>
                                        <td><small class="text-muted"><?= date('h:i A', strtotime($sale['created_at'])) ?></small></td>
                                        <td class="text-end pe-4">
                                            <a href="<?= url('shop/pos/invoice/' . $sale['id']) ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill px-3 shadow-none" title="Print Invoice">
                                                <i class="bi bi-printer me-1 text-primary"></i> Print
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

        <!-- Quick Action Cards / Live Shortcuts -->
        <div class="col-lg-4">
            <!-- Fast POS Quick Banner -->
            <?php if (hasPermission('pos', 'view') || hasPermission('pos', 'create')): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white p-4 position-relative overflow-hidden" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;">
                <div class="position-absolute end-0 top-0 translate-middle-y me-n3 opacity-25">
                    <i class="bi bi-qr-code-scan" style="font-size: 8rem; color: #fff;"></i>
                </div>
                <div class="position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Fast Retail POS</h5>
                        <i class="bi bi-qr-code fs-2"></i>
                    </div>
                    <p class="small text-white-50 mb-3">Instant barcode / QR scanner billing terminal. Scan item tags, calculate tax, and print receipts in seconds.</p>
                    <a href="<?= url('shop/pos') ?>" class="btn btn-light text-primary fw-bold rounded-pill w-100 py-2 shadow-sm">
                        <i class="bi bi-play-fill me-1"></i> Start Billing Session
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Month Summary Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-calendar-check me-2 text-primary"></i>This Month's Performance</h6>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Total Invoices</span>
                    <span class="fw-bold text-dark"><?= $month_invoices ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Month Revenue</span>
                    <span class="fw-bold text-success fs-5"><?= formatCurrency($month_revenue) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Average Order Value</span>
                    <span class="fw-bold text-dark"><?= formatCurrency($avg_order_value_month) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted">Today's Returns</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill"><?= $returns_today ?> Processed</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT: FILTERING & CHART.JS INTERACTIVITY -->
<!-- ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initial State Data
    let activeCardPeriod = '<?= e($activePeriod) ?>';
    let currentChartPeriod = '7days';
    let currentMetric = 'revenue'; // 'revenue' or 'invoices'
    let customChartData = null; // Used when a custom filter is active

    let data7Days = <?= json_encode($daily_revenue_7days ?? []) ?>;
    let data30Days = <?= json_encode($daily_revenue_30days ?? []) ?>;
    let data6Months = <?= json_encode($monthly_revenue_6months ?? []) ?>;
    let dataHourly = <?= json_encode($hourly_revenue_today ?? []) ?>;
    let dataPaymentBreakdown = <?= json_encode($payment_breakdown ?? []) ?>;

    let revenueChart = null;
    let paymentChart = null;

    // Currency & Number Formatter Helpers
    const formatINR = (val) => '₹' + Number(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const formatCount = (val) => Number(val || 0).toLocaleString('en-IN') + ' Bills';

    // 2. Render Payment Doughnut & Custom Legend
    function renderPaymentDonut(paymentData) {
        const donutCtx = document.getElementById('paymentDonutChart').getContext('2d');
        const legendContainer = document.getElementById('paymentLegendContainer');
        
        const modeLabels = [];
        const modeTotals = [];
        const modeColors = [];
        
        const colorMap = {
            'CASH': ['#10b981', 'Cash'],
            'UPI': ['#06b6d4', 'UPI / QR'],
            'CARD': ['#6366f1', 'Card'],
            'MIXED': ['#f59e0b', 'Split / Mixed'],
            'ONLINE': ['#ec4899', 'Online'],
        };

        const list = Array.isArray(paymentData) ? paymentData : [];
        let totalSum = 0;
        list.forEach(i => totalSum += Number(i.total || 0));

        if (list.length === 0) {
            modeLabels.push('No Sales');
            modeTotals.push(1);
            modeColors.push('#e2e8f0');
            legendContainer.innerHTML = '<div class="text-center text-muted small py-2">No payment transactions in selected period.</div>';
        } else {
            let legendHtml = '<div class="d-flex flex-column gap-2">';
            list.forEach(item => {
                const m = (item.payment_mode || 'CASH').toUpperCase();
                const cCfg = colorMap[m] || ['#64748b', m];
                const itemTotal = Number(item.total || 0);
                const pPct = totalSum > 0 ? ((itemTotal / totalSum) * 100).toFixed(1) : '0.0';

                modeLabels.push(cCfg[1]);
                modeTotals.push(itemTotal);
                modeColors.push(cCfg[0]);

                legendHtml += `
                    <div class="d-flex justify-content-between align-items-center small">
                        <div class="d-flex align-items-center">
                            <span class="rounded-circle d-inline-block me-2" style="width: 10px; height: 10px; background-color: ${cCfg[0]};"></span>
                            <span class="fw-semibold text-dark">${cCfg[1]}</span>
                            <span class="text-muted ms-1">(${item.count || 0} bills)</span>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold text-dark">${formatINR(itemTotal)}</span>
                            <span class="badge bg-light text-secondary border rounded-pill ms-1">${pPct}%</span>
                        </div>
                    </div>`;
            });
            legendHtml += '</div>';
            legendContainer.innerHTML = legendHtml;
        }

        if (paymentChart) {
            paymentChart.destroy();
        }

        paymentChart = new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: modeLabels,
                datasets: [{
                    data: modeTotals,
                    backgroundColor: modeColors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#f8fafc',
                        bodyColor: '#38bdf8',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                if (list.length === 0) return ' No transactions';
                                return ' ' + context.label + ': ' + formatINR(context.raw);
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Render Revenue Chart
    function renderRevenueChart() {
        let activeData = data7Days;
        let subtitleText = "Daily revenue and transaction performance trend for the last 7 days";

        if (customChartData) {
            activeData = customChartData;
            subtitleText = "Custom filtered period daily performance trend";
        } else if (currentChartPeriod === '30days') {
            activeData = data30Days;
            subtitleText = "Daily revenue performance across the last 30 days";
        } else if (currentChartPeriod === '6months') {
            activeData = data6Months;
            subtitleText = "Month-over-month revenue growth comparison (Last 6 Months)";
        } else if (currentChartPeriod === 'hourly') {
            activeData = dataHourly;
            subtitleText = "Today's hourly sales volume and peak retail velocity";
        }

        document.getElementById('chartSubtitle').textContent = subtitleText;

        const labels = activeData.map(item => item.label || item.short_label || item.month || item.hour);
        const revenueValues = activeData.map(item => Number(item.revenue || 0));
        const invoiceValues = activeData.map(item => Number(item.invoices || 0));
        const plotValues = currentMetric === 'revenue' ? revenueValues : invoiceValues;

        const totalRevenue = revenueValues.reduce((a, b) => a + b, 0);
        const totalInvoices = invoiceValues.reduce((a, b) => a + b, 0);
        const peakVal = Math.max(...plotValues, 0);
        const avgVal = plotValues.length > 0 ? (plotValues.reduce((a, b) => a + b, 0) / plotValues.length) : 0;

        document.getElementById('statPeriodTotal').textContent = currentMetric === 'revenue' ? formatINR(totalRevenue) : totalInvoices + ' Invoices';
        document.getElementById('statPeriodAvg').textContent = currentMetric === 'revenue' ? formatINR(avgVal) : avgVal.toFixed(1) + ' / slot';
        document.getElementById('statPeriodPeak').textContent = currentMetric === 'revenue' ? formatINR(peakVal) : peakVal + ' Invoices';

        const ctx = document.getElementById('shopRevenueChart').getContext('2d');

        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        if (currentMetric === 'revenue') {
            gradient.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
            gradient.addColorStop(1, 'rgba(37, 99, 235, 0.00)');
        } else {
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
            gradient.addColorStop(1, 'rgba(16, 185, 129, 0.00)');
        }

        const borderColor = currentMetric === 'revenue' ? '#2563eb' : '#10b981';
        const pointBgColor = currentMetric === 'revenue' ? '#1d4ed8' : '#059669';

        if (revenueChart) {
            revenueChart.destroy();
        }

        revenueChart = new Chart(ctx, {
            type: currentChartPeriod === '6months' ? 'bar' : 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: currentMetric === 'revenue' ? 'Revenue (₹)' : 'Invoice Count',
                    data: plotValues,
                    borderColor: borderColor,
                    backgroundColor: currentChartPeriod === '6months' ? (currentMetric === 'revenue' ? '#3b82f6' : '#10b981') : gradient,
                    borderWidth: 2.5,
                    fill: currentChartPeriod !== '6months',
                    tension: 0.35,
                    pointRadius: activeData.length > 20 ? 2 : 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: pointBgColor,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    borderRadius: currentChartPeriod === '6months' ? 6 : 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#f8fafc',
                        bodyColor: '#38bdf8',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { size: 13, weight: '600', family: 'Inter' },
                        bodyFont: { size: 14, weight: '700', family: 'Inter' },
                        callbacks: {
                            label: function(context) {
                                const val = context.raw || 0;
                                const invCount = invoiceValues[context.dataIndex] || 0;
                                if (currentMetric === 'revenue') {
                                    return [' Revenue: ' + formatINR(val), ' Invoices: ' + invCount + ' bills'];
                                } else {
                                    const revAmt = revenueValues[context.dataIndex] || 0;
                                    return [' Invoices: ' + val + ' bills', ' Revenue: ' + formatINR(revAmt)];
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            color: '#64748b',
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 12,
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.7)' },
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            color: '#64748b',
                            callback: function(value) {
                                if (currentMetric === 'revenue') {
                                    if (value >= 100000) return '₹' + (value / 100000).toFixed(1) + 'L';
                                    if (value >= 1000) return '₹' + (value / 1000).toFixed(0) + 'k';
                                    return '₹' + value;
                                }
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    // 4. Update Top Selling Garments List
    function updateTopSellingList(items) {
        const container = document.getElementById('topSellingListContainer');
        if (!items || items.length === 0) {
            container.innerHTML = '<li class="list-group-item text-center py-3 text-muted small">No product sales in this period.</li>';
            return;
        }

        let html = '';
        items.forEach((tsp, idx) => {
            const badgeClass = idx === 0 ? 'bg-warning text-dark' : (idx === 1 ? 'bg-secondary' : 'bg-light text-dark border');
            html += `
                <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 border-0 border-bottom">
                    <div class="d-flex align-items-center me-2 overflow-hidden">
                        <span class="badge ${badgeClass} rounded-circle p-2 me-2" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 11px;">
                            ${idx + 1}
                        </span>
                        <div class="overflow-hidden">
                            <div class="fw-semibold text-dark text-truncate small">${tsp.product_name || 'Product'}</div>
                            <small class="text-muted">${tsp.sku || ''}</small>
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <span class="badge bg-primary-subtle text-primary rounded-pill">${tsp.total_sold_qty || 0} pcs</span>
                        <small class="text-muted d-block">${formatINR(tsp.total_sold_amount || 0)}</small>
                    </div>
                </li>`;
        });
        container.innerHTML = html;
    }

    // 5. Apply Metric Filter (Today / Week / Month / Custom) via AJAX
    window.applyMetricFilter = function(period, customStart = null, customEnd = null) {
        activeCardPeriod = period;
        
        // Update Button Group States
        const btnGroup = document.getElementById('mainFilterBtnGroup');
        btnGroup.querySelectorAll('button').forEach(btn => {
            btn.classList.remove('active', 'btn-primary');
            btn.classList.add('text-secondary');
        });

        const activeBtn = btnGroup.querySelector(`button[data-period="${period}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active', 'btn-primary');
            activeBtn.classList.remove('text-secondary');
        }

        const dateBox = document.getElementById('customDateRangeBox');
        if (period === 'custom') {
            dateBox.style.display = 'block';
        } else {
            dateBox.style.display = 'none';
        }

        // Show spinner
        const spinner = document.getElementById('filterLoadingSpinner');
        spinner.style.display = 'inline-block';

        // Add subtle opacity to cards while fetching
        const kpiContainer = document.getElementById('kpiCardsContainer');
        kpiContainer.style.opacity = '0.6';

        // Build URL
        let fetchUrl = '<?= url("api/shop/dashboard/metrics") ?>?period=' + encodeURIComponent(period);
        if (period === 'custom') {
            const startVal = customStart || document.getElementById('filterStartDate').value;
            const endVal = customEnd || document.getElementById('filterEndDate').value;
            if (startVal) fetchUrl += '&start_date=' + encodeURIComponent(startVal);
            if (endVal) fetchUrl += '&end_date=' + encodeURIComponent(endVal);
        }

        fetch(fetchUrl)
            .then(res => res.json())
            .then(res => {
                spinner.style.display = 'none';
                kpiContainer.style.opacity = '1';

                if (res.success && res.data) {
                    const d = res.data;

                    // 1. Update Card 1: Sales
                    document.getElementById('cardSalesTitle').textContent = d.period_title || 'Period Sales';
                    document.getElementById('cardSalesAmount').textContent = formatINR(d.total_revenue);
                    document.getElementById('cardSalesSubtitle').textContent = (d.total_invoices || 0) + ' Invoices (' + (d.period_subtitle || '') + ')';

                    // 2. Update Card 2: Cash
                    document.getElementById('cardCashAmount').textContent = formatINR(d.cash_total);

                    // 3. Update Card 3: UPI / Digital
                    document.getElementById('cardDigitalAmount').textContent = formatINR(d.digital_total);

                    // 4. Update Card 4: Avg Order Value
                    document.getElementById('cardAovAmount').textContent = formatINR(d.avg_order_value);
                    document.getElementById('cardAovSubtitle').textContent = 'Avg per invoice';

                    // 5. Update Card 5: Low Stock Alert
                    document.getElementById('cardStockAmount').textContent = (d.low_stock_count || 0) + ' Items';

                    // Update Active Badge
                    document.getElementById('activeFilterBadgeText').textContent = 
                        `Showing: ${d.period_title} (${d.start_date} to ${d.end_date})`;

                    // Update Charts & Side Widgets
                    if (d.daily_chart_data && d.daily_chart_data.length > 0) {
                        customChartData = d.daily_chart_data;
                        renderRevenueChart();
                    }
                    if (d.payment_breakdown) {
                        renderPaymentDonut(d.payment_breakdown);
                    }
                    if (d.top_selling) {
                        updateTopSellingList(d.top_selling);
                    }
                }
            })
            .catch(err => {
                spinner.style.display = 'none';
                kpiContainer.style.opacity = '1';
                console.error('Filter request error:', err);
            });
    };

    window.toggleCustomDateForm = function() {
        const dateBox = document.getElementById('customDateRangeBox');
        if (dateBox.style.display === 'none' || dateBox.style.display === '') {
            dateBox.style.display = 'block';
        } else {
            dateBox.style.display = 'none';
        }
    };

    window.applyCustomDateFilter = function() {
        const startVal = document.getElementById('filterStartDate').value;
        const endVal = document.getElementById('filterEndDate').value;

        if (!startVal || !endVal) {
            alert('Please select both Start Date and End Date.');
            return;
        }
        if (startVal > endVal) {
            alert('Start Date cannot be greater than End Date.');
            return;
        }

        applyMetricFilter('custom', startVal, endVal);
    };

    // Chart-specific Period Switcher
    window.switchRevenuePeriod = function(period, btnEl) {
        currentChartPeriod = period;
        customChartData = null; // Reset custom data to use standard tabs

        const group = document.getElementById('chartPeriodButtonGroup');
        group.querySelectorAll('button').forEach(b => {
            b.classList.remove('active', 'btn-primary');
            b.classList.add('text-secondary');
        });
        btnEl.classList.add('active');
        btnEl.classList.remove('text-secondary');

        renderRevenueChart();
    };

    window.toggleMetricMode = function(mode) {
        currentMetric = mode;
        const revBtn = document.getElementById('metricRevBtn');
        const countBtn = document.getElementById('metricCountBtn');

        if (mode === 'revenue') {
            revBtn.classList.add('active');
            revBtn.classList.remove('text-secondary');
            countBtn.classList.remove('active');
            countBtn.classList.add('text-secondary');
        } else {
            countBtn.classList.add('active');
            countBtn.classList.remove('text-secondary');
            revBtn.classList.remove('active');
            revBtn.classList.add('text-secondary');
        }

        renderRevenueChart();
    };

    // Initial Renders
    renderRevenueChart();
    renderPaymentDonut(dataPaymentBreakdown);
});
</script>
