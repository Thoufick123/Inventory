<?php
// views/reports/online.php
// Consolidated Online & Digital Channels (Website + WhatsApp) Report
$reportType = 'online';
$webTotals = $websiteData['totals'] ?? ['total_amount' => 0, 'total_paid' => 0, 'total_pending' => 0, 'total_pcs' => 0, 'total_orders' => 0];
$waTotals = $whatsappData['totals'] ?? ['total_amount' => 0, 'total_paid' => 0, 'total_pending' => 0, 'total_pcs' => 0, 'total_orders' => 0];

$combinedOrders = array_merge($websiteData['orders'] ?? [], $whatsappData['orders'] ?? []);
usort($combinedOrders, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

$totDigitalRevenue = (float)$webTotals['total_amount'] + (float)$waTotals['total_amount'];
$totDigitalOrders = (int)$webTotals['total_orders'] + (int)$waTotals['total_orders'];
$totDigitalPcs = (int)$webTotals['total_pcs'] + (int)$waTotals['total_pcs'];
$totDigitalPaid = (float)$webTotals['total_paid'] + (float)$waTotals['total_paid'];
$totDigitalPending = max(0, $totDigitalRevenue - $totDigitalPaid);
?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 text-uppercase fw-bold">
                <i class="bi bi-laptop me-1"></i>Digital &amp; Online Commerce
            </span>
        </div>
        <h4 class="mb-0 fw-bold text-dark mt-1">
            <i class="bi bi-globe-americas text-primary me-2"></i>Online Commerce (Website + WhatsApp) Reports
        </h4>
        <div class="text-muted small mt-0.5">Consolidated multi-channel performance across all digital storefronts and social commerce channels</div>
    </div>
    
    <div class="d-flex align-items-center gap-1.5 flex-wrap">
        <a href="<?= url('reports/sales') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-cart-check me-1"></i>All Channels
        </a>
        <a href="<?= url('reports/website') ?>" class="btn btn-sm btn-outline-info">
            <i class="bi bi-globe2 me-1"></i>Website Reports
        </a>
        <a href="<?= url('reports/whatsapp') ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-whatsapp me-1"></i>WhatsApp Reports
        </a>
    </div>
</div>

<!-- Date Filter -->
<?php require __DIR__ . '/partials/filter_bar.php'; ?>

<!-- Master KPIs -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
            <div class="text-uppercase small fw-bold text-success" style="font-size: 0.72rem;">Total Digital Revenue</div>
            <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($totDigitalRevenue, 2) ?></div>
            <div class="small text-muted mt-1"><?= number_format($totDigitalOrders) ?> Total Digital Orders</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
            <div class="text-uppercase small fw-bold text-primary" style="font-size: 0.72rem;">Total Units Sold</div>
            <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= number_format($totDigitalPcs) ?> pcs</div>
            <div class="small text-muted mt-1">Across Website &amp; WhatsApp</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border-left: 4px solid #0284c7 !important;">
            <div class="text-uppercase small fw-bold text-info" style="font-size: 0.72rem; color: #0284c7 !important;">Website Channel Share</div>
            <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($webTotals['total_amount'], 2) ?></div>
            <div class="small text-muted mt-1"><?= number_format($webTotals['total_orders']) ?> Orders &bull; <?= number_format($webTotals['total_pcs']) ?> pcs</div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-left: 4px solid #059669 !important;">
            <div class="text-uppercase small fw-bold text-success" style="font-size: 0.72rem; color: #059669 !important;">WhatsApp Channel Share</div>
            <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($waTotals['total_amount'], 2) ?></div>
            <div class="small text-muted mt-1"><?= number_format($waTotals['total_orders']) ?> Orders &bull; <?= number_format($waTotals['total_pcs']) ?> pcs</div>
        </div>
    </div>
</div>

<!-- Channel Comparison Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3.5">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-info text-white p-2 rounded-2"><i class="bi bi-globe2 fs-5"></i></span>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Website E-Commerce Orders</h6>
                        <small class="text-muted">Online direct store customer orders</small>
                    </div>
                </div>
                <a href="<?= url('reports/website') ?>" class="btn btn-sm btn-outline-info">View Dedicated Report &rarr;</a>
            </div>
            <div class="row g-2 text-center">
                <div class="col-4 p-2 bg-light rounded-2">
                    <div class="small text-muted">Orders</div>
                    <div class="fw-bold text-dark font-monospace"><?= number_format($webTotals['total_orders']) ?></div>
                </div>
                <div class="col-4 p-2 bg-light rounded-2">
                    <div class="small text-muted">Units Sold</div>
                    <div class="fw-bold text-dark font-monospace"><?= number_format($webTotals['total_pcs']) ?> pcs</div>
                </div>
                <div class="col-4 p-2 bg-light rounded-2">
                    <div class="small text-muted">Revenue</div>
                    <div class="fw-bold text-success font-monospace">₹<?= number_format($webTotals['total_amount'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3.5">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success text-white p-2 rounded-2" style="background-color: #25D366 !important;"><i class="bi bi-whatsapp fs-5"></i></span>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">WhatsApp Social Commerce Orders</h6>
                        <small class="text-muted">Conversational direct billing &amp; agent sales</small>
                    </div>
                </div>
                <a href="<?= url('reports/whatsapp') ?>" class="btn btn-sm btn-outline-success">View Dedicated Report &rarr;</a>
            </div>
            <div class="row g-2 text-center">
                <div class="col-4 p-2 bg-light rounded-2">
                    <div class="small text-muted">Orders</div>
                    <div class="fw-bold text-dark font-monospace"><?= number_format($waTotals['total_orders']) ?></div>
                </div>
                <div class="col-4 p-2 bg-light rounded-2">
                    <div class="small text-muted">Units Sold</div>
                    <div class="fw-bold text-dark font-monospace"><?= number_format($waTotals['total_pcs']) ?> pcs</div>
                </div>
                <div class="col-4 p-2 bg-light rounded-2">
                    <div class="small text-muted">Revenue</div>
                    <div class="fw-bold text-success font-monospace">₹<?= number_format($waTotals['total_amount'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Consolidated Digital Orders Table -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 px-3.5 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-receipt text-primary me-2"></i>Consolidated Online &amp; Digital Orders Register
        </h5>
        <span class="badge bg-light text-dark border font-monospace"><?= count($combinedOrders) ?> Transactions</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Invoice #</th>
                        <th>Channel</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Items / Pcs</th>
                        <th>Payment Mode</th>
                        <th>Payment Status</th>
                        <th class="text-end">Gross Amount</th>
                        <th class="text-end">Paid Amount</th>
                        <th class="pe-3 text-end">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($combinedOrders)): ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">No digital orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($combinedOrders, 0, 100) as $inv): 
                            $isWeb = (strtoupper($inv['sale_type'] ?? '') === 'WEBSITE' || strtoupper($inv['sales_channel'] ?? '') === 'WEBSITE' || str_starts_with($inv['invoice_number'], 'WEB-'));
                            $ps = strtoupper($inv['payment_status'] ?? 'PENDING');
                            $psBadge = ($ps === 'PAID' || $ps === 'COMPLETED') ? 'bg-success' : (($ps === 'PARTIAL') ? 'bg-warning text-dark' : 'bg-danger');
                        ?>
                            <tr>
                                <td class="ps-3 fw-bold font-monospace <?= $isWeb ? 'text-info' : 'text-success' ?>"><?= e($inv['invoice_number']) ?></td>
                                <td>
                                    <?php if ($isWeb): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-globe2 me-1"></i>Website</span>
                                    <?php else: ?>
                                        <span class="badge border" style="background: rgba(37,211,102,0.12); color: #16a34a; border-color: rgba(37,211,102,0.3) !important;"><i class="bi bi-whatsapp me-1"></i>WhatsApp</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold text-dark"><?= e($inv['customer_name']) ?></td>
                                <td class="small text-muted font-monospace"><?= e($inv['customer_phone']) ?></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= $inv['total_pcs'] ?> pcs</span></td>
                                <td><span class="badge bg-light text-dark border"><?= e(strtoupper($inv['payment_mode'] ?? 'ONLINE')) ?></span></td>
                                <td><span class="badge <?= $psBadge ?>"><?= e($ps) ?></span></td>
                                <td class="text-end font-monospace fw-bold text-dark">₹<?= number_format($inv['total_amount'], 2) ?></td>
                                <td class="text-end font-monospace text-success fw-bold">₹<?= number_format($inv['paid_amount'], 2) ?></td>
                                <td class="pe-3 text-end small text-muted font-monospace"><?= date('d M Y, H:i', strtotime($inv['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
