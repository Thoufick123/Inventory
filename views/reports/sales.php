<?php
// views/reports/sales.php
// Multi-Channel Sales & Invoice Register Report — Full Channel Breakdown
$reportType = 'sales';
$salesReportFilters = $_GET;
unset($salesReportFilters['url']);
$salesReportQuery = http_build_query($salesReportFilters);

// Extract channel data
$channels = $salesData['channels'] ?? [];
$whPOS = $channels['warehouse_pos'] ?? ['orders' => [], 'total_amount' => 0, 'total_paid' => 0, 'total_discount' => 0, 'total_tax' => 0, 'total_pending' => 0, 'count' => 0];
$shopPOS = $channels['shop_pos'] ?? ['orders' => [], 'total_amount' => 0, 'total_paid' => 0, 'total_discount' => 0, 'total_tax' => 0, 'total_pending' => 0, 'count' => 0];
$prodBilling = $channels['production_billing'] ?? ['orders' => [], 'total_amount' => 0, 'total_paid' => 0, 'total_discount' => 0, 'total_tax' => 0, 'total_pending' => 0, 'count' => 0];
$whatsapp = $channels['whatsapp'] ?? ['orders' => [], 'total_amount' => 0, 'total_paid' => 0, 'total_discount' => 0, 'total_tax' => 0, 'total_pending' => 0, 'count' => 0];
$website = $channels['website'] ?? ['orders' => [], 'total_amount' => 0, 'total_paid' => 0, 'total_discount' => 0, 'total_tax' => 0, 'total_pending' => 0, 'count' => 0];

$shopBreakdown = $salesData['shop_breakdown'] ?? [];
$warehouseBreakdown = $salesData['warehouse_breakdown'] ?? [];
$productionBreakdown = $salesData['production_breakdown'] ?? [];

// Helper function for rendering a mini invoice table
function renderSalesTable(array $invoices, string $emptyMsg = 'No orders found.'): void {
    if (empty($invoices)) {
        echo '<div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>' . $emptyMsg . '</div>';
        return;
    }
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.84rem;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Invoice #</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Pending</th>
                    <th class="text-end pe-3">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv):
                    $pending = max(0, (float)$inv['total_amount'] - (float)$inv['paid_amount']);
                    $ps = strtoupper($inv['payment_status'] ?? 'PENDING');
                    $badgeClass = ($ps === 'PAID' || $ps === 'COMPLETED') ? 'bg-success' : (($ps === 'PARTIAL') ? 'bg-warning text-dark' : 'bg-danger');
                ?>
                <tr>
                    <td class="ps-3 fw-semibold font-monospace text-primary"><?= e($inv['invoice_no']) ?></td>
                    <td class="fw-semibold text-dark"><?= e($inv['customer_name']) ?></td>
                    <td class="small text-muted font-monospace"><?= e($inv['customer_phone']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e(strtoupper($inv['payment_method'] ?? 'CASH')) ?></span></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= e($ps) ?></span></td>
                    <td class="text-end font-monospace fw-bold text-dark">₹<?= number_format($inv['total_amount'], 2) ?></td>
                    <td class="text-end font-monospace text-success fw-bold">₹<?= number_format($inv['paid_amount'], 2) ?></td>
                    <td class="text-end font-monospace <?= $pending > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">₹<?= number_format($pending, 2) ?></td>
                    <td class="pe-3 text-end small text-muted font-monospace"><?= date('d M Y, H:i', strtotime($inv['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Helper for channel summary mini KPI row
function renderChannelKPI(float $amount, float $paid, float $pending, int $count): void {
    ?>
    <div class="d-flex flex-wrap gap-3 px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small fw-semibold">Orders:</span>
            <span class="fw-bold text-dark"><?= number_format($count) ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small fw-semibold">Revenue:</span>
            <span class="fw-bold text-success font-monospace">₹<?= number_format($amount, 2) ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small fw-semibold">Collected:</span>
            <span class="fw-bold text-primary font-monospace">₹<?= number_format($paid, 2) ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small fw-semibold">Pending:</span>
            <span class="fw-bold <?= $pending > 0 ? 'text-danger' : 'text-muted' ?> font-monospace">₹<?= number_format($pending, 2) ?></span>
        </div>
    </div>
    <?php
}
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold text-dark">
            <i class="bi bi-cart-check-fill text-primary me-2"></i>Multi-Channel Sales &amp; Invoice Register
        </h4>
        <div class="text-muted small mt-1">All sales channels — Warehouse Direct, Shop POS, Production &amp; Concern Billing, WhatsApp Orders, Website Orders — with per-location breakdowns</div>
    </div>

    <!-- Channel Navigation Switcher -->
    <div class="d-flex align-items-center gap-1.5 flex-wrap">
        <a href="<?= url('reports/sales') ?>" class="btn btn-sm btn-primary text-white fw-bold shadow-xs">
            <i class="bi bi-cart-check me-1"></i>All Channels
        </a>
        <a href="<?= url('reports/website') ?>" class="btn btn-sm btn-outline-info">
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

<?php require __DIR__ . '/partials/filter_bar.php'; ?>

<!-- ========================================================================= -->
<!-- 1. OVERALL SALES KPI SUMMARY CARDS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 0.5px;">Gross Sales Revenue</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($salesData['total_amount'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-cart-check me-1"></i><?= number_format($salesData['total_orders']) ?> Total Invoices
                    </div>
                </div>
                <div class="bg-success text-white p-2.5 rounded-3 shadow-xs"><i class="bi bi-currency-rupee fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Cash Inflow (Paid)</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($salesData['total_paid'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-check2-circle me-1"></i>Settled Transactions
                    </div>
                </div>
                <div class="bg-primary text-white p-2.5 rounded-3 shadow-xs"><i class="bi bi-cash-stack fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #d97706 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-warning" style="font-size: 0.72rem; letter-spacing: 0.5px;">Pending Receivables</div>
                    <div class="fs-4 fw-bold text-warning-emphasis mt-1 font-monospace">₹<?= number_format($salesData['total_pending'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-clock-history me-1"></i>Uncollected balances
                    </div>
                </div>
                <div class="bg-warning text-dark p-2.5 rounded-3 shadow-xs"><i class="bi bi-hourglass-split fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-left: 4px solid #64748b !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Tax (GST) &amp; Discounts</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($salesData['total_tax'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-tag me-1"></i>Discounts: ₹<?= number_format($salesData['total_discount'], 2) ?>
                    </div>
                </div>
                <div class="bg-secondary text-white p-2.5 rounded-3 shadow-xs"><i class="bi bi-receipt fs-4"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. CHANNEL-WISE REVENUE DISTRIBUTION CARDS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Warehouse Direct -->
    <div class="col-xl col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-primary rounded-2 p-2"><i class="bi bi-buildings fs-6"></i></span>
                <div>
                    <div class="fw-bold text-dark small">Warehouse Direct</div>
                    <div class="text-muted" style="font-size: 0.7rem;"><?= $whPOS['count'] ?> orders</div>
                </div>
            </div>
            <div class="fs-5 fw-bold text-primary font-monospace">₹<?= number_format($whPOS['total_amount'], 2) ?></div>
        </div>
    </div>
    <!-- Shop POS -->
    <div class="col-xl col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-success rounded-2 p-2"><i class="bi bi-shop fs-6"></i></span>
                <div>
                    <div class="fw-bold text-dark small">Shop POS Sales</div>
                    <div class="text-muted" style="font-size: 0.7rem;"><?= $shopPOS['count'] ?> orders</div>
                </div>
            </div>
            <div class="fs-5 fw-bold text-success font-monospace">₹<?= number_format($shopPOS['total_amount'], 2) ?></div>
        </div>
    </div>
    <!-- Production Billing -->
    <div class="col-xl col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-dark rounded-2 p-2"><i class="bi bi-receipt-cutoff fs-6"></i></span>
                <div>
                    <div class="fw-bold text-dark small">Production Billing</div>
                    <div class="text-muted" style="font-size: 0.7rem;"><?= $prodBilling['count'] ?> bills</div>
                </div>
            </div>
            <div class="fs-5 fw-bold text-dark font-monospace">₹<?= number_format($prodBilling['total_amount'], 2) ?></div>
        </div>
    </div>
    <!-- WhatsApp -->
    <div class="col-xl col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge rounded-2 p-2" style="background: #25D366;"><i class="bi bi-whatsapp fs-6 text-white"></i></span>
                <div>
                    <div class="fw-bold text-dark small">WhatsApp Orders</div>
                    <div class="text-muted" style="font-size: 0.7rem;"><?= $whatsapp['count'] ?> orders</div>
                </div>
            </div>
            <div class="fs-5 fw-bold font-monospace" style="color: #25D366;">₹<?= number_format($whatsapp['total_amount'], 2) ?></div>
        </div>
    </div>
    <!-- Website -->
    <div class="col-xl col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-info rounded-2 p-2"><i class="bi bi-globe2 fs-6 text-white"></i></span>
                <div>
                    <div class="fw-bold text-dark small">Website Orders</div>
                    <div class="text-muted" style="font-size: 0.7rem;"><?= $website['count'] ?> orders</div>
                </div>
            </div>
            <div class="fs-5 fw-bold text-info font-monospace">₹<?= number_format($website['total_amount'], 2) ?></div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. PAYMENT MODE BREAKDOWN -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <div class="row align-items-center">
            <div class="col-md-3">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="bi bi-credit-card-2-front text-primary me-1"></i>Payment Mode Breakdown:
                </span>
            </div>
            <div class="col-md-9">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <?php if (empty($salesData['payment_modes'])): ?>
                        <span class="text-muted small">No payment data recorded in this period.</span>
                    <?php else: ?>
                        <?php foreach ($salesData['payment_modes'] as $modeName => $modeInfo): ?>
                            <span class="badge bg-light text-dark border px-3 py-2 rounded-2">
                                <span class="fw-bold text-primary"><?= e($modeName) ?></span>: 
                                <span class="font-monospace text-dark">₹<?= number_format($modeInfo['amount'], 2) ?></span>
                                <span class="text-muted small ms-1">(<?= $modeInfo['count'] ?>)</span>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 4. WAREHOUSE DIRECT SALES SECTION (per-warehouse) -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 px-3.5 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-2 p-2"><i class="bi bi-buildings"></i></span>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Warehouse Direct Customer Sales</h6>
                    <small class="text-muted">Walk-in customers &amp; B2C purchase orders from warehouse locations</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 font-monospace">
                    <?= $whPOS['count'] ?> Orders &bull; ₹<?= number_format($whPOS['total_amount'], 2) ?>
                </span>
                <button type="button" onclick="triggerPdfDownload(<?= e(json_encode(url('reports/pdf/sales_warehouse') . ($salesReportQuery !== '' ? '?' . $salesReportQuery : ''))) ?>, this)"
                   class="btn btn-xs btn-outline-danger shadow-xs py-0.5 px-2" title="Download Warehouse Sales PDF">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($warehouseBreakdown)): ?>
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>No warehouse direct sales found in this period.</div>
        <?php else: ?>
            <?php foreach ($warehouseBreakdown as $wid => $wData): ?>
                <div class="border-bottom">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 bg-light bg-opacity-50">
                        <span class="fw-semibold text-dark small">
                            <i class="bi bi-building text-primary me-1"></i><?= e($wData['warehouse_name']) ?>
                        </span>
                        <span class="small text-muted font-monospace">
                            <?= count($wData['orders']) ?> orders &bull; ₹<?= number_format($wData['total_amount'], 2) ?>
                        </span>
                    </div>
                    <?php renderSalesTable($wData['orders']); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 5. SHOP POS SALES SECTION (per-shop) -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 px-3.5 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success rounded-2 p-2"><i class="bi bi-shop"></i></span>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Retail Shop POS Sales</h6>
                    <small class="text-muted">In-store customer invoices &amp; counter billing across all shops</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 font-monospace">
                    <?= $shopPOS['count'] ?> Orders &bull; ₹<?= number_format($shopPOS['total_amount'], 2) ?>
                </span>
                <button type="button" onclick="triggerPdfDownload(<?= e(json_encode(url('reports/pdf/sales_pos') . ($salesReportQuery !== '' ? '?' . $salesReportQuery : ''))) ?>, this)"
                   class="btn btn-xs btn-outline-danger shadow-xs py-0.5 px-2" title="Download Shop POS Sales PDF">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($shopBreakdown)): ?>
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>No retail shop POS sales found in this period.</div>
        <?php else: ?>
            <?php foreach ($shopBreakdown as $sid => $sData): ?>
                <div class="border-bottom">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 bg-light bg-opacity-50">
                        <span class="fw-semibold text-dark small">
                            <i class="bi bi-shop-window text-success me-1"></i><?= e($sData['shop_name']) ?>
                        </span>
                        <span class="small text-muted font-monospace">
                            <?= count($sData['orders']) ?> orders &bull; ₹<?= number_format($sData['total_amount'], 2) ?>
                        </span>
                    </div>
                    <?php renderSalesTable($sData['orders']); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 6. WHATSAPP ORDERS SECTION -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 px-3.5 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-2 p-2" style="background: #25D366;"><i class="bi bi-whatsapp text-white"></i></span>
                <div>
                    <h6 class="fw-bold text-dark mb-0">WhatsApp Channel Orders</h6>
                    <small class="text-muted">Customer orders received via WhatsApp channel</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge border px-2.5 py-1 font-monospace" style="background: rgba(37, 211, 102, 0.1); color: #25D366; border-color: rgba(37, 211, 102, 0.3) !important;">
                    <?= $whatsapp['count'] ?> Orders &bull; ₹<?= number_format($whatsapp['total_amount'], 2) ?>
                </span>
                <a href="<?= url('reports/whatsapp') ?><?= $salesReportQuery !== '' ? '?' . e($salesReportQuery) : '' ?>" class="btn btn-xs btn-outline-success shadow-xs py-0.5 px-2">
                    <i class="bi bi-whatsapp me-1"></i>Full Report &rarr;
                </a>
                <button type="button" onclick="triggerPdfDownload(<?= e(json_encode(url('reports/pdf/sales_whatsapp') . ($salesReportQuery !== '' ? '?' . $salesReportQuery : ''))) ?>, this)"
                   class="btn btn-xs btn-outline-danger shadow-xs py-0.5 px-2" title="Download WhatsApp Orders PDF">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php renderChannelKPI($whatsapp['total_amount'], $whatsapp['total_paid'], $whatsapp['total_pending'], $whatsapp['count']); ?>
        <?php renderSalesTable($whatsapp['orders'], 'No WhatsApp orders found in this period.'); ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 7. WEBSITE ORDERS SECTION -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 px-3.5 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info rounded-2 p-2"><i class="bi bi-globe2 text-white"></i></span>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Website Orders</h6>
                    <small class="text-muted">Online customer orders placed through the website</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info-subtle text-info border border-info-subtle px-2.5 py-1 font-monospace">
                    <?= $website['count'] ?> Orders &bull; ₹<?= number_format($website['total_amount'], 2) ?>
                </span>
                <a href="<?= url('reports/website') ?><?= $salesReportQuery !== '' ? '?' . e($salesReportQuery) : '' ?>" class="btn btn-xs btn-outline-info shadow-xs py-0.5 px-2">
                    <i class="bi bi-globe2 me-1"></i>Full Report &rarr;
                </a>
                <button type="button" onclick="triggerPdfDownload(<?= e(json_encode(url('reports/pdf/sales_website') . ($salesReportQuery !== '' ? '?' . $salesReportQuery : ''))) ?>, this)"
                   class="btn btn-xs btn-outline-danger shadow-xs py-0.5 px-2" title="Download Website Orders PDF">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php renderChannelKPI($website['total_amount'], $website['total_paid'], $website['total_pending'], $website['count']); ?>
        <?php renderSalesTable($website['orders'], 'No website orders found in this period.'); ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 8. PRODUCTION & CONCERN BILLING SECTION -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 px-3.5 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark rounded-2 p-2"><i class="bi bi-receipt-cutoff text-white"></i></span>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Production &amp; Concern Billing Sales</h6>
                    <small class="text-muted">Direct finished-goods QR invoices billed across production units and concern stock</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark-subtle text-dark border border-secondary-subtle px-2.5 py-1 font-monospace">
                    <?= $prodBilling['count'] ?> Bills &bull; ₹<?= number_format($prodBilling['total_amount'], 2) ?>
                </span>
                <button type="button" onclick="triggerPdfDownload(<?= e(json_encode(url('reports/pdf/sales_production') . ($salesReportQuery !== '' ? '?' . $salesReportQuery : ''))) ?>, this)"
                   class="btn btn-xs btn-outline-danger shadow-xs py-0.5 px-2" title="Download Production Billing PDF">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($productionBreakdown)): ?>
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>No production billing invoices found in this period.</div>
        <?php else: ?>
            <?php renderChannelKPI($prodBilling['total_amount'], $prodBilling['total_paid'], $prodBilling['total_pending'], $prodBilling['count']); ?>
            <?php foreach ($productionBreakdown as $pData): ?>
                <div class="border-top">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 bg-light bg-opacity-50">
                        <span class="fw-semibold text-dark small">
                            <i class="bi bi-gear-wide-connected text-dark me-1"></i><?= e($pData['unit_name']) ?>
                        </span>
                        <span class="small text-muted font-monospace">
                            <?= count($pData['orders']) ?> bills &bull; ₹<?= number_format($pData['total_amount'], 2) ?>
                        </span>
                    </div>
                    <?php renderSalesTable($pData['orders']); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 9. PRODUCTION UNITS SUMMARY -->
<!-- ========================================================================= -->
<?php if (!empty($productionUnits)): ?>
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 px-3.5 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-dark rounded-2 p-2"><i class="bi bi-gear-wide-connected"></i></span>
            <div>
                <h6 class="fw-bold text-dark mb-0">Production Units Overview</h6>
                <small class="text-muted">Active manufacturing units feeding the supply chain</small>
            </div>
        </div>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <?php foreach ($productionUnits as $unit): ?>
            <div class="col-md-4">
                <div class="card border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-dark-subtle text-dark rounded-circle p-2"><i class="bi bi-gear"></i></span>
                        <div class="fw-semibold text-dark"><?= e($unit['name']) ?></div>
                    </div>
                    <div class="small text-muted">
                        <i class="bi bi-geo-alt me-1"></i><?= e($unit['location'] ?? 'N/A') ?>
                    </div>
                    <div class="mt-2">
                        <a href="<?= url("reports/production?unit_id={$unit['id']}&filter_type=" . urlencode($filter['filter_type']) . "&year={$filter['year']}&month={$filter['month']}") ?>" class="btn btn-xs btn-outline-primary">
                            View Production Report &rarr;
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- 9. ALL CHANNELS CONSOLIDATED REGISTER -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-0 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-receipt-cutoff text-primary me-2"></i>All Channels — Consolidated Sales Register
            </h5>
            <div class="small text-muted">Showing <?= count($salesData['invoices']) ?> transactions across all channels for <?= e($filter['label']) ?></div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" onclick="triggerPdfDownload(<?= e(json_encode(url('reports/pdf/sales') . ($salesReportQuery !== '' ? '?' . $salesReportQuery : ''))) ?>, this)"
               class="btn btn-sm btn-outline-danger">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i>Download PDF
            </button>
            <a href="<?= url('reports/export/sales') ?><?= $salesReportQuery !== '' ? '?' . e($salesReportQuery) : '' ?>"
               class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Excel (.xlsx)
            </a>
            <?php if ($canPrintReport): ?>
            <a href="<?= url('reports/print/sales') ?><?= $salesReportQuery !== '' ? '?' . e($salesReportQuery) : '' ?>"
               target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Print Register
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Invoice / PO #</th>
                        <th>Channel</th>
                        <th>Customer Name</th>
                        <th>Contact</th>
                        <th>Payment Mode</th>
                        <th>Payment Status</th>
                        <th class="text-end">Gross Total</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Tax / GST</th>
                        <th class="text-end">Paid Amount</th>
                        <th class="text-end">Pending</th>
                        <th class="pe-4 text-end">Date &amp; Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($salesData['invoices'])): ?>
                        <tr><td colspan="12" class="text-center py-5 text-muted">No sales orders or invoices found matching this filter criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($salesData['invoices'] as $inv): 
                            $pending = max(0, (float)$inv['total_amount'] - (float)$inv['paid_amount']);
                            $ch = $inv['channel'] ?? 'Unknown';
                        ?>
                            <tr>
                                <td class="ps-4 fw-semibold font-monospace text-primary"><?= e($inv['invoice_no']) ?></td>
                                <td>
                                    <?php if ($ch === 'Warehouse POS'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Warehouse</span>
                                    <?php elseif ($ch === 'Production Billing'): ?>
                                        <span class="badge bg-dark-subtle text-dark border border-secondary-subtle"><i class="bi bi-receipt-cutoff me-1"></i>Production Billing</span>
                                    <?php elseif ($ch === 'WhatsApp'): ?>
                                        <span class="badge border" style="background: rgba(37,211,102,0.12); color: #25D366; border-color: rgba(37,211,102,0.3) !important;"><i class="bi bi-whatsapp me-1"></i>WhatsApp</span>
                                    <?php elseif ($ch === 'Website'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-globe2 me-1"></i>Website</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Retail Shop</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold text-dark"><?= e($inv['customer_name']) ?></td>
                                <td class="small text-muted font-monospace"><?= e($inv['customer_phone']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e(strtoupper($inv['payment_method'] ?? 'CASH')) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $ps = strtoupper($inv['payment_status'] ?? 'PENDING');
                                    $badgeClass = ($ps === 'PAID' || $ps === 'COMPLETED') ? 'bg-success' : (($ps === 'PARTIAL') ? 'bg-warning text-dark' : 'bg-danger');
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= e($ps) ?></span>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark">₹<?= number_format($inv['total_amount'], 2) ?></td>
                                <td class="text-end font-monospace text-muted"><?= $inv['discount_amount'] > 0 ? '-₹'.number_format($inv['discount_amount'], 2) : '-' ?></td>
                                <td class="text-end font-monospace text-muted">₹<?= number_format($inv['tax_amount'], 2) ?></td>
                                <td class="text-end font-monospace text-success fw-bold">₹<?= number_format($inv['paid_amount'], 2) ?></td>
                                <td class="text-end font-monospace <?= $pending > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= $pending > 0 ? '₹'.number_format($pending, 2) : '₹0.00' ?>
                                </td>
                                <td class="pe-4 text-end small text-muted font-monospace">
                                    <?= date('d M Y, H:i', strtotime($inv['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($salesData['invoices'])): ?>
                <tfoot class="table-light fw-bold border-top">
                    <tr>
                        <td colspan="6" class="ps-4 text-uppercase">Grand Totals (<?= count($salesData['invoices']) ?> Orders)</td>
                        <td class="text-end font-monospace text-dark">₹<?= number_format($salesData['total_amount'], 2) ?></td>
                        <td class="text-end font-monospace text-muted">-₹<?= number_format($salesData['total_discount'], 2) ?></td>
                        <td class="text-end font-monospace text-muted">₹<?= number_format($salesData['total_tax'], 2) ?></td>
                        <td class="text-end font-monospace text-success">₹<?= number_format($salesData['total_paid'], 2) ?></td>
                        <td class="text-end font-monospace text-danger">₹<?= number_format($salesData['total_pending'], 2) ?></td>
                        <td class="pe-4"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
