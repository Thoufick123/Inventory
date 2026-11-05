<?php
// views/reports/website.php
// Dedicated Website Orders & E-Commerce Sales Analytics & Report
$reportType = 'website';
$orders = $reportData['orders'] ?? [];
$totals = $reportData['totals'] ?? [
    'total_amount' => 0, 'total_paid' => 0, 'total_pending' => 0,
    'total_discount' => 0, 'total_tax' => 0, 'total_pcs' => 0,
    'total_orders' => 0, 'avg_order_value' => 0
];
$topProducts = $reportData['topProducts'] ?? [];
$paymentModes = $reportData['paymentModes'] ?? [];
$orderStatuses = $reportData['orderStatuses'] ?? [];
$itemsMap = $reportData['itemsMap'] ?? [];

$curSearch = $filters['search'] ?? '';
$curPaymentStatus = $filters['payment_status'] ?? 'all';
$curPaymentMode = $filters['payment_mode'] ?? 'all';
$curOrderStatus = $filters['order_status'] ?? 'all';
?>

<!-- ========================================================================= -->
<!-- 1. HEADER & CHANNEL NAVIGATION -->
<!-- ========================================================================= -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-info-subtle text-info border border-info-subtle px-2.5 py-1 text-uppercase fw-bold" style="letter-spacing: 0.5px;">
                <i class="bi bi-globe2 me-1"></i>E-Commerce Channel
            </span>
            <span class="badge bg-light text-muted border px-2 py-1">Code: WEB-01</span>
        </div>
        <h4 class="mb-0 fw-bold text-dark mt-1">
            <i class="bi bi-globe2 text-info me-2"></i>Website Orders &amp; Sales Report
        </h4>
        <div class="text-muted small mt-0.5">Comprehensive analytics, online customer revenue, product demand, and order fulfillment breakdown</div>
    </div>
    
    <!-- Quick Navigation Pills -->
    <div class="d-flex align-items-center gap-1.5 flex-wrap">
        <a href="<?= url('reports/sales') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-cart-check me-1"></i>Multi-Channel
        </a>
        <a href="<?= url('reports/website') ?>" class="btn btn-sm btn-info text-white fw-bold shadow-xs">
            <i class="bi bi-globe2 me-1"></i>Website Reports
        </a>
        <a href="<?= url('reports/whatsapp') ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-whatsapp me-1"></i>WhatsApp Reports
        </a>
        <a href="<?= url('reports/shop') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-shop me-1"></i>Shop POS
        </a>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. DATE FILTER BAR -->
<!-- ========================================================================= -->
<?php require __DIR__ . '/partials/filter_bar.php'; ?>

<!-- ========================================================================= -->
<!-- 3. TOP KPI SUMMARY CARDS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Gross Revenue -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 0.5px;">Gross Website Revenue</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($totals['total_amount'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-receipt me-1"></i><?= number_format($totals['total_orders']) ?> Total Orders
                    </div>
                </div>
                <div class="bg-success text-white p-2.5 rounded-3 shadow-xs"><i class="bi bi-currency-rupee fs-4"></i></div>
            </div>
        </div>
    </div>

    <!-- Units Sold (Pieces) -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border-left: 4px solid #0284c7 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-info" style="font-size: 0.72rem; letter-spacing: 0.5px; color: #0284c7 !important;">Total Units Sold</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= number_format($totals['total_pcs']) ?> <span class="fs-6 fw-normal text-muted">pcs</span></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-graph-up me-1"></i>Avg Order: ₹<?= number_format($totals['avg_order_value'], 2) ?>
                    </div>
                </div>
                <div class="bg-info text-white p-2.5 rounded-3 shadow-xs" style="background-color: #0284c7 !important;"><i class="bi bi-box-seam fs-4"></i></div>
            </div>
        </div>
    </div>

    <!-- Settled Cash & Online Inflow -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Settled &amp; Paid Inflow</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($totals['total_paid'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-check2-circle me-1"></i>Completed Settlements
                    </div>
                </div>
                <div class="bg-primary text-white p-2.5 rounded-3 shadow-xs"><i class="bi bi-credit-card-2-front fs-4"></i></div>
            </div>
        </div>
    </div>

    <!-- Pending / Receivables & Discounts -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #d97706 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-warning" style="font-size: 0.72rem; letter-spacing: 0.5px;">Discounts &amp; GST</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($totals['total_tax'], 2) ?> <span class="fs-6 fw-normal text-muted">GST</span></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-tag me-1"></i>Discounts: ₹<?= number_format($totals['total_discount'], 2) ?>
                        <?php if ($totals['total_pending'] > 0): ?>
                            &bull; <span class="text-danger fw-semibold">Pending: ₹<?= number_format($totals['total_pending'], 2) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="bg-warning text-dark p-2.5 rounded-3 shadow-xs"><i class="bi bi-percent fs-4"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Channel P&L Operating Expenses & Net Margin Summary -->
<?php if (!empty($reportData['channelExpenses'])): 
    $chExp = $reportData['channelExpenses'];
    $netMargin = ($totals['total_amount'] ?? 0) - ($chExp['total_amount'] ?? 0);
    $marginPct = ($totals['total_amount'] ?? 0) > 0 ? round(($netMargin / $totals['total_amount']) * 100, 1) : 0;
?>
<div class="card border-0 shadow-sm rounded-3 mb-4" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-left: 4px solid #0284c7 !important;">
    <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-2.5 rounded-3 bg-white shadow-xs text-primary">
                <i class="bi bi-wallet2 fs-4" style="color: #0284c7 !important;"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-0">Website Channel P&amp;L Financial Summary</h6>
                <div class="small text-muted">
                    Channel Operating Expenses: <strong class="text-danger">₹<?= number_format($chExp['total_amount'] ?? 0, 2) ?></strong> (Gateway 2%, Server &amp; Courier) &bull;
                    Net Channel Profit: <strong class="<?= $netMargin >= 0 ? 'text-success' : 'text-danger' ?>">₹<?= number_format($netMargin, 2) ?></strong> (<?= $marginPct ?>% Margin)
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('warehouse/website-orders/expenses') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-receipt-cutoff me-1"></i> Manage Channel Expenses
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- 4. ANALYTICS & INSIGHTS ROW (Top Products & Payment / Status Mix) -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Top 10 Selling Products in Website -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 px-3.5 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-info-subtle text-info p-1.5 rounded-2"><i class="bi bi-trophy-fill fs-6"></i></span>
                    <h6 class="fw-bold text-dark mb-0">Top Selling Products &bull; Website Channel</h6>
                </div>
                <span class="badge bg-light text-muted border font-monospace">Top <?= count($topProducts) ?> Styles</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 380px;">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.84rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3" style="width: 40px;">#</th>
                                <th>Product / Style Name</th>
                                <th>SKU / Variant</th>
                                <th class="text-center">Orders</th>
                                <th class="text-end">Units Sold</th>
                                <th class="text-end pe-3">Revenue (INR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topProducts)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No website product sales found in this period.</td></tr>
                            <?php else: ?>
                                <?php foreach ($topProducts as $idx => $p): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold <?= $idx < 3 ? 'text-warning' : 'text-muted' ?>">
                                            <?php if ($idx === 0): ?><i class="bi bi-award-fill text-warning me-1"></i><?php endif; ?>
                                            #<?= $idx + 1 ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($p['product_name']) ?></div>
                                            <div class="small text-muted" style="font-size: 0.72rem;"><?= e($p['category_name'] ?? 'Apparel') ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace"><?= e($p['sku']) ?></span>
                                            <div class="small text-muted mt-0.5" style="font-size: 0.72rem;">
                                                <?= e($p['size_name'] ?? '-') ?> &bull; <?= e($p['color_name'] ?? '-') ?>
                                            </div>
                                        </td>
                                        <td class="text-center font-monospace fw-semibold"><?= number_format($p['total_orders']) ?></td>
                                        <td class="text-end font-monospace fw-bold text-dark"><?= number_format($p['total_sold_qty']) ?> pcs</td>
                                        <td class="text-end pe-3 font-monospace text-success fw-bold">₹<?= number_format($p['total_revenue'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modes & Order Statuses -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column">
            <div class="card-header bg-white py-3 px-3.5 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary p-1.5 rounded-2"><i class="bi bi-pie-chart-fill fs-6"></i></span>
                    <h6 class="fw-bold text-dark mb-0">Payment Modes &amp; Order Statuses</h6>
                </div>
            </div>
            <div class="card-body p-3.5 d-flex flex-column gap-3 justify-content-between">
                <!-- Payment Modes Breakdown -->
                <div>
                    <div class="text-uppercase small fw-bold text-muted mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        <i class="bi bi-credit-card me-1"></i>Payment Modes Inflow
                    </div>
                    <?php if (empty($paymentModes)): ?>
                        <div class="text-muted small">No payment data available.</div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($paymentModes as $modeName => $modeData): 
                                $pct = $totals['total_amount'] > 0 ? round(($modeData['amount'] / $totals['total_amount']) * 100, 1) : 0;
                            ?>
                                <div>
                                    <div class="d-flex justify-content-between align-items-center small mb-1">
                                        <span class="fw-bold text-dark"><i class="bi bi-check-circle-fill text-success me-1"></i><?= e($modeName) ?> (<?= $modeData['count'] ?> orders)</span>
                                        <span class="font-monospace fw-bold text-dark">₹<?= number_format($modeData['amount'], 2) ?> <span class="text-muted fw-normal">(<?= $pct ?>%)</span></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?= $pct ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <hr class="my-1 text-muted opacity-25">

                <!-- Order Statuses Breakdown -->
                <div>
                    <div class="text-uppercase small fw-bold text-muted mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        <i class="bi bi-truck me-1"></i>Fulfillment &amp; Order Lifecycle
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (empty($orderStatuses)): ?>
                            <span class="text-muted small">No status records.</span>
                        <?php else: ?>
                            <?php foreach ($orderStatuses as $stName => $stCount): 
                                $stBadge = match($stName) {
                                    'DELIVERED', 'COMPLETED' => 'bg-success',
                                    'SHIPPED', 'DISPATCHED' => 'bg-info text-white',
                                    'PAID', 'CONFIRMED' => 'bg-primary',
                                    'PACKED', 'PROCESSING' => 'bg-warning text-dark',
                                    'CANCELLED', 'RETURNED' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                            ?>
                                <span class="badge <?= $stBadge ?> px-3 py-2 rounded-2 d-flex align-items-center gap-1.5 font-monospace">
                                    <span><?= e($stName) ?></span>
                                    <span class="badge bg-light text-dark rounded-pill"><?= $stCount ?></span>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 5. DETAILED WEBSITE ORDERS REGISTER & LINE-ITEM EXPLORER -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 px-3.5 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-receipt-cutoff text-info me-2"></i>Website Orders &amp; Invoices Register
                </h5>
                <div class="small text-muted">Showing <?= count($orders) ?> website orders for <?= e($filter['label']) ?></div>
            </div>

            <!-- In-Table Secondary Filter Form -->
            <form method="GET" action="" class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Keep date parameters -->
                <input type="hidden" name="filter_type" value="<?= e($filter['filter_type']) ?>">
                <input type="hidden" name="year" value="<?= e($filter['year']) ?>">
                <input type="hidden" name="month" value="<?= e($filter['month']) ?>">
                <input type="hidden" name="from_date" value="<?= e($filter['from_date']) ?>">
                <input type="hidden" name="to_date" value="<?= e($filter['to_date']) ?>">

                <div class="input-group input-group-sm" style="width: 220px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search order/cust..." value="<?= e($curSearch) ?>">
                </div>

                <select name="payment_status" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                    <option value="all">All Payments</option>
                    <option value="PAID" <?= $curPaymentStatus === 'PAID' ? 'selected' : '' ?>>PAID</option>
                    <option value="PARTIAL" <?= $curPaymentStatus === 'PARTIAL' ? 'selected' : '' ?>>PARTIAL</option>
                    <option value="PENDING" <?= $curPaymentStatus === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                </select>

                <select name="payment_mode" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                    <option value="all">All Modes</option>
                    <option value="CARD" <?= $curPaymentMode === 'CARD' ? 'selected' : '' ?>>Card</option>
                    <option value="UPI" <?= $curPaymentMode === 'UPI' ? 'selected' : '' ?>>UPI</option>
                    <option value="NETBANKING" <?= $curPaymentMode === 'NETBANKING' ? 'selected' : '' ?>>Netbanking</option>
                    <option value="COD" <?= $curPaymentMode === 'COD' ? 'selected' : '' ?>>COD</option>
                </select>

                <select name="order_status" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                    <option value="all">All Statuses</option>
                    <option value="DELIVERED" <?= $curOrderStatus === 'DELIVERED' ? 'selected' : '' ?>>DELIVERED</option>
                    <option value="SHIPPED" <?= $curOrderStatus === 'SHIPPED' ? 'selected' : '' ?>>SHIPPED</option>
                    <option value="CONFIRMED" <?= $curOrderStatus === 'CONFIRMED' ? 'selected' : '' ?>>CONFIRMED</option>
                    <option value="CANCELLED" <?= $curOrderStatus === 'CANCELLED' ? 'selected' : '' ?>>CANCELLED</option>
                </select>

                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-clockwise"></i></button>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="websiteOrdersTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 30px;"></th>
                        <th>Order / Invoice #</th>
                        <th>Customer Details</th>
                        <th>Location</th>
                        <th>Items / Pcs</th>
                        <th>Payment Mode</th>
                        <th>Payment Status</th>
                        <th>Order Status</th>
                        <th class="text-end">Gross Total</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Paid Amount</th>
                        <th class="text-end">Pending</th>
                        <th class="pe-3 text-end">Date &amp; Time</th>
                        <th class="pe-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="14" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                No website orders found matching the filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $inv): 
                            $pending = max(0, (float)$inv['total_amount'] - (float)$inv['paid_amount']);
                            $ps = strtoupper($inv['payment_status'] ?? 'PENDING');
                            $psBadge = ($ps === 'PAID' || $ps === 'COMPLETED') ? 'bg-success' : (($ps === 'PARTIAL') ? 'bg-warning text-dark' : 'bg-danger');
                            
                            $st = strtoupper($inv['status'] ?? 'DELIVERED');
                            $stBadge = match($st) {
                                'DELIVERED', 'COMPLETED' => 'bg-success',
                                'SHIPPED', 'DISPATCHED' => 'bg-info text-white',
                                'CONFIRMED', 'PAID' => 'bg-primary',
                                'CANCELLED', 'RETURNED' => 'bg-danger',
                                default => 'bg-secondary'
                            };

                            $invItems = $itemsMap[$inv['id']] ?? [];
                        ?>
                            <tr>
                                <td class="ps-3 text-center">
                                    <?php if (!empty($invItems)): ?>
                                        <button class="btn btn-xs btn-outline-secondary p-0 px-1 border-0" type="button" data-bs-toggle="collapse" data-bs-target="#items-row-<?= $inv['id'] ?>" aria-expanded="false" title="View Ordered Items">
                                            <i class="bi bi-chevron-down text-primary"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold font-monospace text-primary">
                                    <?= e($inv['invoice_number']) ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($inv['customer_name']) ?></div>
                                    <div class="small text-muted font-monospace"><i class="bi bi-telephone me-1"></i><?= e($inv['customer_phone']) ?></div>
                                </td>
                                <td>
                                    <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($inv['customer_city'] ?? '-') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        <?= $inv['item_count'] ?> items &bull; <?= $inv['total_pcs'] ?> pcs
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1"><?= e(strtoupper($inv['payment_mode'] ?? 'CARD')) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $psBadge ?>"><?= e($ps) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $stBadge ?>"><?= e($st) ?></span>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark">₹<?= number_format($inv['total_amount'], 2) ?></td>
                                <td class="text-end font-monospace text-muted"><?= $inv['discount_amount'] > 0 ? '-₹'.number_format($inv['discount_amount'], 2) : '-' ?></td>
                                <td class="text-end font-monospace text-success fw-bold">₹<?= number_format($inv['paid_amount'], 2) ?></td>
                                <td class="text-end font-monospace <?= $pending > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= $pending > 0 ? '₹'.number_format($pending, 2) : '₹0.00' ?>
                                </td>
                                <td class="pe-3 text-end small text-muted font-monospace">
                                    <?= date('d M Y, H:i', strtotime($inv['created_at'])) ?>
                                </td>
                                <td class="pe-3 text-center">
                                    <a href="<?= url('warehouse/website-orders/history?search=' . urlencode($inv['invoice_number'])) ?>" class="btn btn-xs btn-outline-info" title="View Order Record">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>

                            <!-- Expandable Line Items Row -->
                            <?php if (!empty($invItems)): ?>
                                <tr class="collapse bg-light p-0" id="items-row-<?= $inv['id'] ?>">
                                    <td colspan="14" class="p-3">
                                        <div class="card border rounded-3 p-2 bg-white shadow-xs">
                                            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                                <span class="fw-bold small text-dark"><i class="bi bi-box-seam text-info me-1"></i>Order Line Items for <?= e($inv['invoice_number']) ?></span>
                                                <span class="small text-muted"><?= count($invItems) ?> Variant Items</span>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.8rem;">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Product Name</th>
                                                            <th>SKU</th>
                                                            <th>Size / Color</th>
                                                            <th class="text-center">Quantity</th>
                                                            <th class="text-end">Unit Price</th>
                                                            <th class="text-end">Discount</th>
                                                            <th class="text-end">Item Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($invItems as $itm): ?>
                                                            <tr>
                                                                <td class="fw-semibold text-dark"><?= e($itm['product_name']) ?></td>
                                                                <td class="font-monospace text-muted"><?= e($itm['sku']) ?></td>
                                                                <td><?= e($itm['size_name'] ?? '-') ?> / <?= e($itm['color_name'] ?? '-') ?></td>
                                                                <td class="text-center font-monospace fw-bold"><?= $itm['quantity'] ?> pcs</td>
                                                                <td class="text-end font-monospace">₹<?= number_format($itm['unit_price'], 2) ?></td>
                                                                <td class="text-end font-monospace text-muted"><?= $itm['discount_amount'] > 0 ? '₹'.number_format($itm['discount_amount'], 2) : '-' ?></td>
                                                                <td class="text-end font-monospace fw-bold text-success">₹<?= number_format($itm['total_amount'], 2) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($orders)): ?>
                    <tfoot class="table-light fw-bold border-top">
                        <tr>
                            <td colspan="4" class="ps-3 text-uppercase">Totals (<?= count($orders) ?> Orders)</td>
                            <td><?= number_format($totals['total_pcs']) ?> pcs</td>
                            <td colspan="3"></td>
                            <td class="text-end font-monospace text-dark">₹<?= number_format($totals['total_amount'], 2) ?></td>
                            <td class="text-end font-monospace text-muted">-₹<?= number_format($totals['total_discount'], 2) ?></td>
                            <td class="text-end font-monospace text-success">₹<?= number_format($totals['total_paid'], 2) ?></td>
                            <td class="text-end font-monospace text-danger">₹<?= number_format($totals['total_pending'], 2) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
