<?php
// views/reports/purchase.php
// Supplier Procurement & Purchase Order Analytics Report
$reportType = 'purchase';
$totals = $purchaseData['totals'] ?? [];
$avgSpend = ($totals['total_orders'] ?? 0) > 0 ? round(($totals['total_spend'] ?? 0) / $totals['total_orders'], 2) : 0;
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold text-dark">
            <i class="bi bi-truck text-primary me-2"></i>Supplier Procurement &amp; Purchase Orders
        </h4>
        <div class="text-muted small mt-1">Vendor purchase orders, procurement spending, inward received quantities, and outstanding dues</div>
    </div>
</div>

<?php require __DIR__ . '/partials/filter_bar.php'; ?>

<!-- ========================================================================= -->
<!-- 1. PROCUREMENT KPI SCOREBOARD -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Total Procurement Spend -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Procurement Spend</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($totals['total_spend'] ?? 0, 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-wallet2 me-1"></i>Direct Supplier Inflow
                    </div>
                </div>
                <div class="bg-primary text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-currency-rupee fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Inward Purchased Quantity -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 0.5px;">Purchased Quantity</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= number_format($totals['total_qty'] ?? 0) ?> pcs</div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-boxes me-1"></i>Materials &amp; Garments
                    </div>
                </div>
                <div class="bg-success text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-box-seam fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- PO Count -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #d97706 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-warning" style="font-size: 0.72rem; letter-spacing: 0.5px;">Purchase Orders Count</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= number_format($totals['total_orders'] ?? 0) ?> Orders</div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-file-text me-1"></i>Vendor Transactions
                    </div>
                </div>
                <div class="bg-warning text-dark p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-receipt fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Avg PO Value -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-left: 4px solid #64748b !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Avg Order Value</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($avgSpend, 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-graph-up me-1"></i>Average ticket size
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
<!-- 2. SUPPLIER FILTER BAR -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="bi bi-people me-1 text-primary"></i>Filter by Supplier:
                </span>
                <a href="<?= url('reports/purchase') ?>?filter_type=<?= urlencode($filter['filter_type']) ?>&supplier_id=0&year=<?= $filter['year'] ?>&month=<?= $filter['month'] ?>" 
                   class="btn btn-sm <?= empty($supplierId) ? 'btn-primary' : 'btn-light border' ?>">
                   All Suppliers
                </a>
                <?php foreach (array_slice($suppliers, 0, 5) as $sup): ?>
                    <a href="<?= url('reports/purchase') ?>?filter_type=<?= urlencode($filter['filter_type']) ?>&supplier_id=<?= $sup['id'] ?>&year=<?= $filter['year'] ?>&month=<?= $filter['month'] ?>" 
                       class="btn btn-sm <?= ($supplierId == $sup['id']) ? 'btn-primary' : 'btn-light border' ?>">
                       <?= e($sup['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. DETAILED PURCHASE ORDERS REGISTER -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-0 border-bottom d-flex align-items-center justify-content-between">
        <div>
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-truck-flatbed text-primary me-2"></i>Procurement &amp; Supplier Orders
            </h5>
            <div class="small text-muted">Showing <?= count($purchaseData['orders']) ?> supplier orders for <?= e($filter['label']) ?></div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">PO Number</th>
                        <th>Supplier / Vendor</th>
                        <th>Company</th>
                        <th>Receiving Warehouse</th>
                        <th class="text-end">Items</th>
                        <th class="text-end">Total Qty</th>
                        <th>Payment Status</th>
                        <th>PO Status</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance Due</th>
                        <th class="pe-4 text-end">Order Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($purchaseData['orders'])): ?>
                        <tr><td colspan="12" class="text-center py-5 text-muted">No supplier purchase orders found for this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($purchaseData['orders'] as $po): 
                            $due = max(0, (float)$po['total_amount'] - (float)$po['paid_amount']);
                        ?>
                            <tr>
                                <td class="ps-4 fw-semibold font-monospace text-primary">
                                    <?= e($po['po_number']) ?>
                                </td>
                                <td class="fw-semibold text-dark"><?= e($po['supplier_name'] ?? 'Supplier') ?></td>
                                <td class="small text-muted"><?= e($po['supplier_company'] ?: '-') ?></td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?= e($po['warehouse_name'] ?? 'Main Warehouse') ?></span></td>
                                <td class="text-end font-monospace"><?= number_format($po['item_count']) ?></td>
                                <td class="text-end font-monospace fw-bold"><?= number_format($po['total_qty']) ?> pcs</td>
                                <td>
                                    <?php
                                    $pStatus = strtoupper($po['payment_status'] ?? 'PENDING');
                                    $pClass = ($pStatus === 'PAID') ? 'bg-success' : (($pStatus === 'PARTIAL') ? 'bg-warning text-dark' : 'bg-danger');
                                    ?>
                                    <span class="badge <?= $pClass ?>"><?= e($pStatus) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($po['status'] ?? 'APPROVED') ?></span>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark">₹<?= number_format($po['total_amount'], 2) ?></td>
                                <td class="text-end font-monospace text-success">₹<?= number_format($po['paid_amount'], 2) ?></td>
                                <td class="text-end font-monospace <?= $due > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= $due > 0 ? '₹'.number_format($due, 2) : '₹0.00' ?>
                                </td>
                                <td class="pe-4 text-end small text-muted font-monospace"><?= date('d M Y', strtotime($po['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($purchaseData['orders'])): ?>
                <tfoot class="table-light fw-bold border-top">
                    <tr>
                        <td colspan="5" class="ps-4 text-uppercase">Grand Totals (<?= count($purchaseData['orders']) ?> POs)</td>
                        <td class="text-end font-monospace"><?= number_format($totals['total_qty'] ?? 0) ?> pcs</td>
                        <td colspan="2"></td>
                        <td class="text-end font-monospace text-dark">₹<?= number_format($totals['total_spend'] ?? 0, 2) ?></td>
                        <td colspan="2"></td>
                        <td class="pe-4"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
