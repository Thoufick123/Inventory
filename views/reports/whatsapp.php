<?php
// views/reports/whatsapp.php
// Dedicated WhatsApp Orders & Social Commerce Sales Analytics & Report
$reportType = 'whatsapp';
$orders = $reportData['orders'] ?? [];
$totals = $reportData['totals'] ?? [
    'total_amount' => 0, 'total_paid' => 0, 'total_pending' => 0,
    'total_discount' => 0, 'total_tax' => 0, 'total_pcs' => 0,
    'total_orders' => 0, 'avg_order_value' => 0
];
$topProducts = $reportData['topProducts'] ?? [];
$staffBreakdown = $reportData['staffBreakdown'] ?? [];
$paymentModes = $reportData['paymentModes'] ?? [];
$orderStatuses = $reportData['orderStatuses'] ?? [];
$itemsMap = $reportData['itemsMap'] ?? [];

$curSearch = $filters['search'] ?? '';
$curPaymentStatus = $filters['payment_status'] ?? 'all';
$curPaymentMode = $filters['payment_mode'] ?? 'all';
$curOrderStatus = $filters['order_status'] ?? 'all';
$curBillerName = $filters['biller_name'] ?? 'all';
?>

<!-- ========================================================================= -->
<!-- 1. HEADER & CHANNEL NAVIGATION -->
<!-- ========================================================================= -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge px-2.5 py-1 text-uppercase fw-bold border" style="background: rgba(37,211,102,0.15); color: #15803d; border-color: rgba(37,211,102,0.4) !important; letter-spacing: 0.5px;">
                <i class="bi bi-whatsapp me-1"></i>WhatsApp &amp; Social Commerce
            </span>
            <span class="badge bg-light text-muted border px-2 py-1">Code: WA-01</span>
        </div>
        <h4 class="mb-0 fw-bold text-dark mt-1">
            <i class="bi bi-whatsapp text-success me-2"></i>WhatsApp Orders &amp; Sales Report
        </h4>
        <div class="text-muted small mt-0.5">Conversational commerce analytics, sales staff biller tracking, payment collections, and customer orders breakdown</div>
    </div>
    
    <!-- Quick Navigation Pills -->
    <div class="d-flex align-items-center gap-1.5 flex-wrap">
        <a href="<?= url('reports/sales') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-cart-check me-1"></i>Multi-Channel
        </a>
        <a href="<?= url('reports/website') ?>" class="btn btn-sm btn-outline-info">
            <i class="bi bi-globe2 me-1"></i>Website Reports
        </a>
        <a href="<?= url('reports/whatsapp') ?>" class="btn btn-sm btn-success text-white fw-bold shadow-xs">
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
                    <div class="text-uppercase small fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 0.5px;">Gross WhatsApp Revenue</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($totals['total_amount'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-receipt me-1"></i><?= number_format($totals['total_orders']) ?> Total Invoices
                    </div>
                </div>
                <div class="bg-success text-white p-2.5 rounded-3 shadow-xs"><i class="bi bi-currency-rupee fs-4"></i></div>
            </div>
        </div>
    </div>

    <!-- Units Sold (Pieces) -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-left: 4px solid #059669 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 0.5px; color: #059669 !important;">Total Garments Sold</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= number_format($totals['total_pcs']) ?> <span class="fs-6 fw-normal text-muted">pcs</span></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-bag-check me-1"></i>Avg Order: ₹<?= number_format($totals['avg_order_value'], 2) ?>
                    </div>
                </div>
                <div class="text-white p-2.5 rounded-3 shadow-xs" style="background-color: #059669;"><i class="bi bi-box-seam fs-4"></i></div>
            </div>
        </div>
    </div>

    <!-- Total Collected & UPI Inflow -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Collected Cash &amp; UPI</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($totals['total_paid'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-check2-circle me-1"></i>Settled Transactions
                    </div>
                </div>
                <div class="bg-primary text-white p-2.5 rounded-3 shadow-xs"><i class="bi bi-cash-stack fs-4"></i></div>
            </div>
        </div>
    </div>

    <!-- Pending / Receivables -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #d97706 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-warning" style="font-size: 0.72rem; letter-spacing: 0.5px;">Pending &amp; Discounts</div>
                    <div class="fs-4 fw-bold text-warning-emphasis mt-1 font-monospace">₹<?= number_format($totals['total_pending'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-tag me-1"></i>Discounts: ₹<?= number_format($totals['total_discount'], 2) ?> &bull; GST: ₹<?= number_format($totals['total_tax'], 2) ?>
                    </div>
                </div>
                <div class="bg-warning text-dark p-2.5 rounded-3 shadow-xs"><i class="bi bi-hourglass-split fs-4"></i></div>
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
<div class="card border-0 shadow-sm rounded-3 mb-4" style="background: linear-gradient(135deg, #f8fafc 0%, #f0fdf4 100%); border-left: 4px solid #25D366 !important;">
    <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-2.5 rounded-3 bg-white shadow-xs text-success">
                <i class="bi bi-whatsapp fs-4" style="color: #25D366 !important;"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-0">WhatsApp Channel P&amp;L Financial Summary</h6>
                <div class="small text-muted">
                    Channel Operating Expenses: <strong class="text-danger">₹<?= number_format($chExp['total_amount'] ?? 0, 2) ?></strong> (Parcel Logistics, Meta API, Packaging) &bull;
                    Net Channel Profit: <strong class="<?= $netMargin >= 0 ? 'text-success' : 'text-danger' ?>">₹<?= number_format($netMargin, 2) ?></strong> (<?= $marginPct ?>% Margin)
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('warehouse/whatsapp-orders/expenses') ?>" class="btn btn-outline-success btn-sm rounded-pill px-3">
                <i class="bi bi-receipt me-1"></i> Manage Channel Expenses
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- 4. ANALYTICS & INSIGHTS ROW (Top Products & Staff Performance) -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Top 10 Selling Products in WhatsApp -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 px-3.5 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge p-1.5 rounded-2" style="background: rgba(37,211,102,0.2); color: #16a34a;"><i class="bi bi-trophy-fill fs-6"></i></span>
                    <h6 class="fw-bold text-dark mb-0">Top Selling Products &bull; WhatsApp Channel</h6>
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
                                <tr><td colspan="6" class="text-center py-4 text-muted">No WhatsApp product sales found in this period.</td></tr>
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

    <!-- Staff Biller & Payment Breakdown -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column">
            <div class="card-header bg-white py-3 px-3.5 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary p-1.5 rounded-2"><i class="bi bi-person-lines-fill fs-6"></i></span>
                    <h6 class="fw-bold text-dark mb-0">Staff Biller Performance &amp; Payment Modes</h6>
                </div>
            </div>
            <div class="card-body p-3.5 d-flex flex-column gap-3 justify-content-between">
                <!-- Staff / Billing Agent Breakdown -->
                <div>
                    <div class="text-uppercase small fw-bold text-muted mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        <i class="bi bi-people me-1"></i>WhatsApp Sales Agents / Billers
                    </div>
                    <?php if (empty($staffBreakdown)): ?>
                        <div class="text-muted small">No staff billing records found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Staff / Agent</th>
                                        <th class="text-center">Orders</th>
                                        <th class="text-end">Sales Value</th>
                                        <th class="text-end">Collected</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staffBreakdown as $sb): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark">
                                                <i class="bi bi-person-badge text-primary me-1"></i><?= e($sb['staff_name']) ?>
                                            </td>
                                            <td class="text-center font-monospace"><?= $sb['total_orders'] ?></td>
                                            <td class="text-end font-monospace fw-bold text-dark">₹<?= number_format($sb['total_sales'], 2) ?></td>
                                            <td class="text-end font-monospace text-success fw-semibold">₹<?= number_format($sb['total_collected'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <hr class="my-1 text-muted opacity-25">

                <!-- Payment Modes Breakdown -->
                <div>
                    <div class="text-uppercase small fw-bold text-muted mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        <i class="bi bi-credit-card me-1"></i>Payment Modes (UPI / COD / Split)
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (empty($paymentModes)): ?>
                            <span class="text-muted small">No payment mode records.</span>
                        <?php else: ?>
                            <?php foreach ($paymentModes as $pmName => $pmData): ?>
                                <span class="badge bg-light text-dark border px-3 py-2 rounded-2 font-monospace">
                                    <span class="fw-bold text-primary"><?= e($pmName) ?></span>: 
                                    <span>₹<?= number_format($pmData['amount'], 2) ?></span>
                                    <span class="text-muted small ms-1">(<?= $pmData['count'] ?>)</span>
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
<!-- 5. DETAILED WHATSAPP ORDERS REGISTER & LINE-ITEM EXPLORER -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 px-3.5 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-receipt-cutoff text-success me-2"></i>WhatsApp Orders &amp; Invoices Register
                </h5>
                <div class="small text-muted">Showing <?= count($orders) ?> WhatsApp orders for <?= e($filter['label']) ?></div>
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
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search order/agent/phone..." value="<?= e($curSearch) ?>">
                </div>

                <select name="payment_status" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                    <option value="all">All Payments</option>
                    <option value="PAID" <?= $curPaymentStatus === 'PAID' ? 'selected' : '' ?>>PAID</option>
                    <option value="PARTIAL" <?= $curPaymentStatus === 'PARTIAL' ? 'selected' : '' ?>>PARTIAL</option>
                    <option value="PENDING" <?= $curPaymentStatus === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                </select>

                <select name="payment_mode" class="form-select form-select-sm" style="width: 130px;" onchange="this.form.submit()">
                    <option value="all">All Modes</option>
                    <option value="UPI" <?= $curPaymentMode === 'UPI' ? 'selected' : '' ?>>UPI</option>
                    <option value="CASH" <?= $curPaymentMode === 'CASH' ? 'selected' : '' ?>>Cash</option>
                    <option value="CARD" <?= $curPaymentMode === 'CARD' ? 'selected' : '' ?>>Card</option>
                    <option value="SPLIT" <?= $curPaymentMode === 'SPLIT' ? 'selected' : '' ?>>Split</option>
                </select>

                <select name="biller_name" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                    <option value="all">All Staff/Agents</option>
                    <?php foreach ($staffBreakdown as $sb): ?>
                        <option value="<?= e($sb['staff_name']) ?>" <?= $curBillerName === $sb['staff_name'] ? 'selected' : '' ?>><?= e($sb['staff_name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-clockwise"></i></button>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="whatsappOrdersTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 30px;"></th>
                        <th>Order / Invoice #</th>
                        <th>Customer Details</th>
                        <th>Biller Agent</th>
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
                                No WhatsApp orders found matching the filter criteria.
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
                            $cleanPhone = preg_replace('/[^0-9]/', '', $inv['customer_phone'] ?? '');
                            if (strlen($cleanPhone) === 10) {
                                $cleanPhone = '91' . $cleanPhone;
                            }
                        ?>
                            <tr>
                                <td class="ps-3 text-center">
                                    <?php if (!empty($invItems)): ?>
                                        <button class="btn btn-xs btn-outline-secondary p-0 px-1 border-0" type="button" data-bs-toggle="collapse" data-bs-target="#wa-items-row-<?= $inv['id'] ?>" aria-expanded="false" title="View Ordered Items">
                                            <i class="bi bi-chevron-down text-success"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold font-monospace text-success">
                                    <?= e($inv['invoice_number']) ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($inv['customer_name']) ?></div>
                                    <div class="small text-muted font-monospace d-flex align-items-center gap-1.5">
                                        <span><i class="bi bi-telephone me-0.5"></i><?= e($inv['customer_phone']) ?></span>
                                        <?php if (!empty($cleanPhone)): ?>
                                            <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= urlencode('Hello ' . $inv['customer_name'] . ', regarding your Order #' . $inv['invoice_number']) ?>" 
                                               target="_blank" class="text-success" title="Chat on WhatsApp">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-person me-1"></i><?= e($inv['biller_name']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        <?= $inv['item_count'] ?> items &bull; <?= $inv['total_pcs'] ?> pcs
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1"><?= e(strtoupper($inv['payment_mode'] ?? 'UPI')) ?></span>
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
                                    <a href="<?= url('warehouse/whatsapp-orders/history?search=' . urlencode($inv['invoice_number'])) ?>" class="btn btn-xs btn-outline-success" title="View Order Record">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>

                            <!-- Expandable Line Items Row -->
                            <?php if (!empty($invItems)): ?>
                                <tr class="collapse bg-light p-0" id="wa-items-row-<?= $inv['id'] ?>">
                                    <td colspan="14" class="p-3">
                                        <div class="card border rounded-3 p-2 bg-white shadow-xs">
                                            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                                <span class="fw-bold small text-dark"><i class="bi bi-box-seam text-success me-1"></i>Order Line Items for <?= e($inv['invoice_number']) ?></span>
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
