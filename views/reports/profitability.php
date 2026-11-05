<?php
// views/reports/profitability.php
// Enterprise Profit & Loss (P&L) and Financial Health Audit
$reportType = 'profitability';
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold text-dark">
            <i class="bi bi-cash-coin text-success me-2"></i>Profit &amp; Loss (P&amp;L) Financial Statement
        </h4>
        <div class="text-muted small mt-1">Detailed audited breakdown of revenue, production cost of goods sold, overhead expenses, and recorded losses</div>
    </div>
</div>

<?php require __DIR__ . '/partials/filter_bar.php'; ?>

<!-- ========================================================================= -->
<!-- 1. FINANCIAL SUMMARY KPI SCOREBOARD -->
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
                        <i class="bi bi-shop me-1"></i>WH: ₹<?= number_format($pnl['wh_sales_revenue'], 2) ?> &bull; POS: ₹<?= number_format($pnl['shop_sales_revenue'], 2) ?>
                    </div>
                </div>
                <div class="bg-success text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Direct Production Costs (COGS) -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Direct Material &amp; Batch Cost</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($pnl['direct_cost'], 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-gear me-1"></i>Batches: ₹<?= number_format($pnl['production_cost'], 2) ?> &bull; POs: ₹<?= number_format($pnl['supplier_purchases'], 2) ?>
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
                        <i class="bi bi-lightning-charge me-1"></i>Units: ₹<?= number_format($pnl['production_expenses'], 2) ?> &bull; Shops: ₹<?= number_format($pnl['shop_expenses'], 2) ?>
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
                    <div class="text-uppercase small fw-bold text-danger" style="font-size: 0.72rem; letter-spacing: 0.5px;">Damages &amp; Scrap Losses</div>
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
<!-- 2. NET PROFITABILITY & MARGINS HIGHLIGHT -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4 text-white overflow-hidden" 
     style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); border: 1px solid #334155 !important;">
    <div class="card-body p-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">Official Income Statement</span>
                    <span class="text-white-50 small">&bull; Filter: <?= e($filter['label']) ?></span>
                </div>
                <h3 class="fw-bold mb-1">
                    Net Profit / Earnings: 
                    <span class="<?= $pnl['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?> font-monospace">
                        <?= $pnl['net_profit'] < 0 ? '-' : '' ?>₹<?= number_format(abs($pnl['net_profit']), 2) ?>
                    </span>
                </h3>
                <p class="text-light mb-0 small opacity-75">
                    Net earnings remaining after deducting direct material costs, all factory/shop operating expenditures, and recorded damage scrap write-offs.
                </p>
            </div>
            <div class="col-lg-6">
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="p-2.5 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10">
                            <div class="text-white-50 small" style="font-size: 0.72rem;">Gross Margin</div>
                            <div class="fs-5 fw-bold text-success font-monospace"><?= $pnl['gross_margin_pct'] ?>%</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2.5 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10">
                            <div class="text-white-50 small" style="font-size: 0.72rem;">Net Margin</div>
                            <div class="fs-5 fw-bold <?= $pnl['net_margin_pct'] >= 0 ? 'text-info' : 'text-danger' ?> font-monospace"><?= $pnl['net_margin_pct'] ?>%</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2.5 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10">
                            <div class="text-white-50 small" style="font-size: 0.72rem;">Loss Ratio</div>
                            <?php $lossRatio = $pnl['gross_revenue'] > 0 ? round(($pnl['recorded_losses'] / $pnl['gross_revenue']) * 100, 2) : 0; ?>
                            <div class="fs-5 fw-bold text-danger font-monospace"><?= $lossRatio ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. COMPREHENSIVE P&L FINANCIAL STATEMENT (TABLE FORMAT) -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 border-0 border-bottom d-flex align-items-center justify-content-between">
        <div>
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i>Executive Profit &amp; Loss Financial Statement
            </h5>
            <div class="small text-muted">Audited balance sheet view for <?= e($filter['label']) ?></div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('reports/export/profitability') ?>?filter_type=<?= urlencode($filter['filter_type']) ?>&year=<?= $filter['year'] ?>&month=<?= $filter['month'] ?>&from_date=<?= $filter['from_date'] ?>&to_date=<?= $filter['to_date'] ?>" 
               class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export P&amp;L (CSV)
            </a>
            <a href="<?= url('reports/print/profitability') ?>?filter_type=<?= urlencode($filter['filter_type']) ?>&year=<?= $filter['year'] ?>&month=<?= $filter['month'] ?>&from_date=<?= $filter['from_date'] ?>&to_date=<?= $filter['to_date'] ?>" 
               target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Print Statement
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Line Item Description</th>
                        <th>Classification</th>
                        <th class="text-end">Subtotal (₹)</th>
                        <th class="text-end pe-4">Total Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- REVENUE SECTION -->
                    <tr class="table-success bg-opacity-25 fw-bold">
                        <td colspan="3" class="ps-4 text-success">
                            <i class="bi bi-plus-circle-fill me-2"></i>1. GROSS OPERATING REVENUE (INFLOW)
                        </td>
                        <td class="text-end pe-4 text-success font-monospace">₹<?= number_format($pnl['gross_revenue'], 2) ?></td>
                    </tr>
                    <tr>
                        <td class="ps-5">&bull; Warehouse Direct Sales &amp; POS Orders</td>
                        <td><span class="badge bg-success-subtle text-success">Customer Sales</span></td>
                        <td class="text-end font-monospace">₹<?= number_format($pnl['wh_sales_revenue'], 2) ?></td>
                        <td class="text-end pe-4"></td>
                    </tr>
                    <tr>
                        <td class="ps-5">&bull; Retail Shop POS Collections</td>
                        <td><span class="badge bg-success-subtle text-success">Retail POS</span></td>
                        <td class="text-end font-monospace">₹<?= number_format($pnl['shop_sales_revenue'], 2) ?></td>
                        <td class="text-end pe-4"></td>
                    </tr>
                    <tr>
                        <td class="ps-5">&bull; Cutting &amp; Stitching Wastage / Scrap Sales</td>
                        <td><span class="badge bg-info-subtle text-info">By-Product Sales</span></td>
                        <td class="text-end font-monospace">₹<?= number_format($pnl['wastage_revenue'], 2) ?></td>
                        <td class="text-end pe-4"></td>
                    </tr>

                    <!-- DIRECT COSTS SECTION -->
                    <tr class="table-primary bg-opacity-25 fw-bold">
                        <td colspan="3" class="ps-4 text-primary">
                            <i class="bi bi-dash-circle-fill me-2"></i>2. DIRECT COST OF GOODS SOLD (COGS)
                        </td>
                        <td class="text-end pe-4 text-primary font-monospace">- ₹<?= number_format($pnl['direct_cost'], 2) ?></td>
                    </tr>
                    <tr>
                        <td class="ps-5">&bull; Production Batches Manufacturing Cost</td>
                        <td><span class="badge bg-primary-subtle text-primary">Manufacturing</span></td>
                        <td class="text-end font-monospace">₹<?= number_format($pnl['production_cost'], 2) ?></td>
                        <td class="text-end pe-4"></td>
                    </tr>
                    <tr>
                        <td class="ps-5">&bull; Direct Supplier Raw Material Purchases</td>
                        <td><span class="badge bg-primary-subtle text-primary">Procurement</span></td>
                        <td class="text-end font-monospace">₹<?= number_format($pnl['supplier_purchases'], 2) ?></td>
                        <td class="text-end pe-4"></td>
                    </tr>

                    <!-- GROSS PROFIT SUB-TOTAL -->
                    <tr class="fw-bold" style="background-color: #f8fafc; border-top: 2px solid #cbd5e1; border-bottom: 2px solid #cbd5e1;">
                        <td colspan="3" class="ps-4 text-dark fs-6">
                            <i class="bi bi-calculator me-2"></i>GROSS OPERATING PROFIT (Revenue - COGS)
                        </td>
                        <td class="text-end pe-4 fs-6 text-success font-monospace">
                            ₹<?= number_format($pnl['gross_profit'], 2) ?>
                            <span class="badge bg-success-subtle text-success ms-2 font-sans"><?= $pnl['gross_margin_pct'] ?>%</span>
                        </td>
                    </tr>

                    <!-- OPERATING EXPENSES SECTION -->
                    <tr class="table-warning bg-opacity-25 fw-bold">
                        <td colspan="3" class="ps-4 text-warning-emphasis">
                            <i class="bi bi-dash-circle-fill me-2 text-warning"></i>3. OPERATING &amp; ADMINISTRATIVE EXPENSES
                        </td>
                        <td class="text-end pe-4 text-warning-emphasis font-monospace">- ₹<?= number_format($pnl['operating_expenses'], 2) ?></td>
                    </tr>
                    <tr>
                        <td class="ps-5">&bull; Production Unit 1 &amp; Unit 2 Overhead Expenses</td>
                        <td><span class="badge bg-warning-subtle text-warning">Factory OpEx</span></td>
                        <td class="text-end font-monospace">₹<?= number_format($pnl['production_expenses'], 2) ?></td>
                        <td class="text-end pe-4"></td>
                    </tr>
                    <tr>
                        <td class="ps-5">&bull; Retail Shop Operational Petty Cash</td>
                        <td><span class="badge bg-warning-subtle text-warning">Retail OpEx</span></td>
                        <td class="text-end font-monospace">₹<?= number_format($pnl['shop_expenses'], 2) ?></td>
                        <td class="text-end pe-4"></td>
                    </tr>
                    <tr>
                        <td class="ps-5">&bull; Plant &amp; Asset Maintenance Costs</td>
                        <td><span class="badge bg-warning-subtle text-warning">Maintenance</span></td>
                        <td class="text-end font-monospace">₹<?= number_format($pnl['asset_maintenance'], 2) ?></td>
                        <td class="text-end pe-4"></td>
                    </tr>

                    <!-- LOSSES SECTION -->
                    <tr class="table-danger bg-opacity-25 fw-bold">
                        <td colspan="3" class="ps-4 text-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>4. RECORDED LOSSES, DEFECTS &amp; WRITE-OFFS
                        </td>
                        <td class="text-end pe-4 text-danger font-monospace">- ₹<?= number_format($pnl['recorded_losses'], 2) ?></td>
                    </tr>
                    <tr>
                        <td class="ps-5">&bull; Warehouse Finished Goods Damages</td>
                        <td><span class="badge bg-danger-subtle text-danger">Inventory Loss</span></td>
                        <td class="text-end font-monospace">₹<?= number_format($pnl['wh_damage_loss'], 2) ?></td>
                        <td class="text-end pe-4"></td>
                    </tr>
                    <tr>
                        <td class="ps-5">&bull; Raw Fabric Roll Inspection Damages</td>
                        <td><span class="badge bg-danger-subtle text-danger">Material Scrap</span></td>
                        <td class="text-end font-monospace">₹<?= number_format($pnl['roll_damage_loss'], 2) ?></td>
                        <td class="text-end pe-4"></td>
                    </tr>
                    <tr>
                        <td class="ps-5">&bull; Defective Customer Returns</td>
                        <td><span class="badge bg-danger-subtle text-danger">Defect Write-Off</span></td>
                        <td class="text-end font-monospace">₹<?= number_format($pnl['return_defect_loss'], 2) ?></td>
                        <td class="text-end pe-4"></td>
                    </tr>

                    <!-- NET PROFIT BOTTOM LINE -->
                    <tr class="fw-bold <?= $pnl['net_profit'] >= 0 ? 'table-success' : 'table-danger' ?>" style="font-size: 1.15rem; border-top: 3px double #000;">
                        <td colspan="3" class="ps-4">
                            <i class="bi bi-award-fill me-2"></i>NET INCOME / NET EARNINGS (BOTTOM LINE)
                        </td>
                        <td class="text-end pe-4 font-monospace">
                            <?= $pnl['net_profit'] < 0 ? '-' : '' ?>₹<?= number_format(abs($pnl['net_profit']), 2) ?>
                            <span class="badge <?= $pnl['net_margin_pct'] >= 0 ? 'bg-success' : 'bg-danger' ?> text-white ms-2" style="font-size: 0.85rem;">
                                <?= $pnl['net_margin_pct'] ?>% Margin
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 4. DETAILED LINE ITEMS AUDIT TRAIL -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-0 border-bottom">
        <ul class="nav nav-pills card-header-pills" id="pnlAuditTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-semibold" id="rev-tab" data-bs-toggle="tab" data-bs-target="#rev-content" type="button" role="tab">
                    <i class="bi bi-arrow-down-left-circle text-success me-1"></i>Revenue Inflow (<?= count($revenueDetails) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="cogs-tab" data-bs-toggle="tab" data-bs-target="#cogs-content" type="button" role="tab">
                    <i class="bi bi-boxes text-primary me-1"></i>Production Costs (<?= count($costDetails) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="exp-tab" data-bs-toggle="tab" data-bs-target="#exp-content" type="button" role="tab">
                    <i class="bi bi-receipt text-warning me-1"></i>Expenses Log (<?= count($expenseDetails) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="loss-tab" data-bs-toggle="tab" data-bs-target="#loss-content" type="button" role="tab">
                    <i class="bi bi-exclamation-triangle text-danger me-1"></i>Damages &amp; Losses (<?= count($lossDetails) ?>)
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content" id="pnlAuditTabsContent">
            <!-- REVENUE INFLOW TAB -->
            <div class="tab-pane fade show active" id="rev-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Reference #</th>
                                <th>Source</th>
                                <th>Customer / Buyer</th>
                                <th>Payment Method</th>
                                <th class="text-end">Tax / GST</th>
                                <th class="text-end">Total Amount</th>
                                <th class="pe-4 text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($revenueDetails)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No revenue records found in this date range.</td></tr>
                            <?php else: ?>
                                <?php foreach ($revenueDetails as $row): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-primary"><?= e($row['ref_no']) ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= e($row['source']) ?></span></td>
                                        <td><?= e($row['customer_name'] ?: 'Direct Customer') ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= e(strtoupper($row['payment_method'] ?? 'CASH')) ?></span></td>
                                        <td class="text-end font-monospace text-muted">₹<?= number_format($row['tax_amount'] ?? 0, 2) ?></td>
                                        <td class="text-end font-monospace fw-bold text-success">₹<?= number_format($row['total_amount'], 2) ?></td>
                                        <td class="pe-4 text-end small text-muted"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PRODUCTION COSTS TAB -->
            <div class="tab-pane fade" id="cogs-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Batch Number</th>
                                <th>Product Style</th>
                                <th class="text-end">Total Quantity</th>
                                <th class="text-end">Cost / Piece</th>
                                <th class="text-end">Total Cost</th>
                                <th class="pe-4 text-end">Production Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($costDetails)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No production batch records found in this date range.</td></tr>
                            <?php else: ?>
                                <?php foreach ($costDetails as $row): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-primary"><?= e($row['ref_no']) ?></td>
                                        <td><?= e($row['product_name'] ?? 'Production Item') ?></td>
                                        <td class="text-end font-monospace"><?= number_format($row['total_qty']) ?> pcs</td>
                                        <td class="text-end font-monospace text-muted">₹<?= number_format($row['cost_per_piece'] ?? 0, 2) ?></td>
                                        <td class="text-end font-monospace fw-bold text-primary">₹<?= number_format($row['total_cost'], 2) ?></td>
                                        <td class="pe-4 text-end small text-muted"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- OPERATING EXPENSES TAB -->
            <div class="tab-pane fade" id="exp-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Expense #</th>
                                <th>Facility / Unit</th>
                                <th>Category</th>
                                <th>Item / Description</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($expenseDetails)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No expense records found in this date range.</td></tr>
                            <?php else: ?>
                                <?php foreach ($expenseDetails as $row): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-warning-emphasis"><?= e($row['ref_no']) ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= e($row['facility'] ?? 'Factory Unit') ?></span></td>
                                        <td><?= e($row['category'] ?? 'General') ?></td>
                                        <td><?= e($row['item_name']) ?></td>
                                        <td class="text-end font-monospace fw-bold text-warning-emphasis">₹<?= number_format($row['amount'], 2) ?></td>
                                        <td><span class="badge bg-success-subtle text-success"><?= e($row['payment_status'] ?? 'PAID') ?></span></td>
                                        <td class="pe-4 text-end small text-muted"><?= date('d M Y', strtotime($row['expense_date'] ?? $row['created_at'] ?? 'now')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- DAMAGES & LOSSES TAB -->
            <div class="tab-pane fade" id="loss-content" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Damage Ref #</th>
                                <th>Loss Type</th>
                                <th>Product / Item</th>
                                <th class="text-end">Damaged Qty</th>
                                <th class="text-end">Loss Amount</th>
                                <th>Reason</th>
                                <th class="pe-4 text-end">Recorded Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lossDetails)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No recorded damage losses in this date range.</td></tr>
                            <?php else: ?>
                                <?php foreach ($lossDetails as $row): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold font-monospace text-danger"><?= e($row['ref_no']) ?></td>
                                        <td><span class="badge bg-danger-subtle text-danger"><?= e($row['loss_type']) ?></span></td>
                                        <td><?= e($row['product_name']) ?></td>
                                        <td class="text-end font-monospace text-danger fw-bold"><?= number_format($row['quantity']) ?></td>
                                        <td class="text-end font-monospace fw-bold text-danger">- ₹<?= number_format($row['amount'], 2) ?></td>
                                        <td class="small text-muted"><?= e($row['reason'] ?: 'Damaged in handling') ?></td>
                                        <td class="pe-4 text-end small text-muted"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
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
