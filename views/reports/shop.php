<?php
// views/reports/shop.php
// Retail Outlets, POS Cash Register & Shop Expenses Report
$reportType = 'shop';
$totals = $shopData['totals'] ?? [];
$netShopCash = ($totals['total_sales'] ?? 0) - ($totals['total_expenses'] ?? 0);
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold text-dark">
            <i class="bi bi-shop text-primary me-2"></i>Retail Stores &amp; POS Cash Flow Reports
        </h4>
        <div class="text-muted small mt-1">Retail store point-of-sale collections, counter invoices, petty expenses, and net store cash</div>
    </div>
</div>

<?php require __DIR__ . '/partials/filter_bar.php'; ?>

<!-- ========================================================================= -->
<!-- 1. RETAIL SHOP KPI SCOREBOARD -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Total POS Sales Revenue -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 0.5px;">Retail POS Sales</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($totals['total_sales'] ?? 0, 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-receipt me-1"></i><?= number_format($totals['sales_count'] ?? 0) ?> Invoices
                    </div>
                </div>
                <div class="bg-success text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-shop fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Shop Petty Cash Expenses -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #d97706 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-warning" style="font-size: 0.72rem; letter-spacing: 0.5px;">Shop Petty Expenses</div>
                    <div class="fs-4 fw-bold text-warning-emphasis mt-1 font-monospace">₹<?= number_format($totals['total_expenses'] ?? 0, 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-wallet me-1"></i>Operational Cash Outflow
                    </div>
                </div>
                <div class="bg-warning text-dark p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-cash-coin fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Net Cash Balance -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Net Store Operating Balance</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($netShopCash, 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-graph-up-arrow me-1"></i>Gross Sales minus OpEx
                    </div>
                </div>
                <div class="bg-primary text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-piggy-bank fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Avg Invoice Value -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-left: 4px solid #64748b !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Avg Store Ticket Size</div>
                    <?php $avgShop = ($totals['sales_count'] ?? 0) > 0 ? round(($totals['total_sales'] ?? 0) / $totals['sales_count'], 2) : 0; ?>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($avgShop, 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-person-check me-1"></i>Per Customer Average
                    </div>
                </div>
                <div class="bg-secondary text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-calculator fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. RETAIL OUTLET SELECTOR BAR -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="bi bi-shop me-1 text-primary"></i>Filter by Store:
                </span>
                <a href="<?= url('reports/shop') ?>?filter_type=<?= urlencode($filter['filter_type']) ?>&shop_id=0&year=<?= $filter['year'] ?>&month=<?= $filter['month'] ?>" 
                   class="btn btn-sm <?= empty($shopId) ? 'btn-primary' : 'btn-light border' ?>">
                   All Stores
                </a>
                <?php foreach ($shops as $sh): ?>
                    <a href="<?= url('reports/shop') ?>?filter_type=<?= urlencode($filter['filter_type']) ?>&shop_id=<?= $sh['id'] ?>&year=<?= $filter['year'] ?>&month=<?= $filter['month'] ?>" 
                       class="btn btn-sm <?= ($shopId == $sh['id']) ? 'btn-primary' : 'btn-light border' ?>">
                       <?= e($sh['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="d-flex gap-2">
                <a href="<?= url('reports/export/shop') ?>?filter_type=<?= urlencode($filter['filter_type']) ?>&shop_id=<?= $shopId ?>&year=<?= $filter['year'] ?>&month=<?= $filter['month'] ?>&from_date=<?= $filter['from_date'] ?>&to_date=<?= $filter['to_date'] ?>" 
                   class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export Store (CSV)
                </a>
                <a href="<?= url('reports/print/shop') ?>?filter_type=<?= urlencode($filter['filter_type']) ?>&shop_id=<?= $shopId ?>&year=<?= $filter['year'] ?>&month=<?= $filter['month'] ?>&from_date=<?= $filter['from_date'] ?>&to_date=<?= $filter['to_date'] ?>" 
                   target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-printer me-1"></i>Print Report
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. RETAIL AUDIT TABS: SALES & PETTY EXPENSES -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-0 border-bottom">
        <ul class="nav nav-pills card-header-pills" id="shopReportTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-semibold" id="ss-tab" data-bs-toggle="tab" data-bs-target="#ss-content" type="button" role="tab">
                    <i class="bi bi-receipt me-1 text-success"></i>POS Sales Transactions (<?= count($shopData['sales']) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="se-tab" data-bs-toggle="tab" data-bs-target="#se-content" type="button" role="tab">
                    <i class="bi bi-wallet2 me-1 text-warning"></i>Petty Cash Expenses (<?= count($shopData['expenses']) ?>)
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content" id="shopReportTabsContent">
            <!-- 1. SALES TAB -->
            <div class="tab-pane fade show active" id="ss-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Invoice #</th>
                                <th>Store Location</th>
                                <th>Customer Name</th>
                                <th>Payment Mode</th>
                                <th class="text-end">Tax / GST</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Paid</th>
                                <th class="pe-4 text-end">Sale Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shopData['sales'])): ?>
                                <tr><td colspan="8" class="text-center py-5 text-muted">No POS store transactions recorded in this date range.</td></tr>
                            <?php else: ?>
                                <?php foreach ($shopData['sales'] as $sale): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-primary"><?= e($sale['invoice_number']) ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-shop me-1"></i><?= e($sale['shop_name'] ?? 'Store') ?></span></td>
                                        <td><?= e($sale['customer_name'] ?: 'Walk-in Customer') ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= e(strtoupper($sale['payment_mode'] ?? 'CASH')) ?></span></td>
                                        <td class="text-end font-monospace text-muted">₹<?= number_format($sale['tax_amount'] ?? 0, 2) ?></td>
                                        <td class="text-end font-monospace fw-bold text-dark">₹<?= number_format($sale['total_amount'], 2) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold">₹<?= number_format($sale['paid_amount'], 2) ?></td>
                                        <td class="pe-4 text-end small text-muted font-monospace"><?= date('d M Y, H:i', strtotime($sale['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. EXPENSES TAB -->
            <div class="tab-pane fade" id="se-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Expense Ref #</th>
                                <th>Store Location</th>
                                <th>Title / Purpose</th>
                                <th class="text-end">Amount</th>
                                <th class="pe-4 text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shopData['expenses'])): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No shop cash expenses recorded.</td></tr>
                            <?php else: ?>
                                <?php foreach ($shopData['expenses'] as $exp): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-warning-emphasis"><?= e($exp['expense_number'] ?? ('EXP-'.$exp['id'])) ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= e($exp['shop_name'] ?? 'Store') ?></span></td>
                                        <td class="fw-semibold text-dark"><?= e($exp['title'] ?? 'Petty Cash Expense') ?></td>
                                        <td class="text-end font-monospace fw-bold text-danger">₹<?= number_format($exp['amount'], 2) ?></td>
                                        <td class="pe-4 text-end small text-muted"><?= date('d M Y, H:i', strtotime($exp['created_at'])) ?></td>
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
