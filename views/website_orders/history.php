<?php $canCreateOrder = hasPermission('website_orders', 'create'); ?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-clock-history me-2 text-primary"></i>Website Customer Orders History</h2>
        <span class="text-muted">Comprehensive history of all customer sales orders generated via Website store</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('warehouse/website-orders') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
        </a>
        <?php if ($canCreateOrder): ?>
        <a href="<?= url('sales/website/create') ?>" class="btn btn-primary">
            <i class="bi bi-cart-plus me-1"></i> Create Website Order
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Summary Metrics Chips -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(13, 110, 253, 0.12); color: #0d6efd;">
                    <i class="bi bi-receipt fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total Orders</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($totalOrdersCount ?? 0) ?></h4>
                    <small class="text-muted"><?= number_format($completedCount ?? 0) ?> completed</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(25, 135, 84, 0.15); color: #198754;">
                    <i class="bi bi-currency-rupee fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total Sales Revenue</div>
                    <h4 class="fw-bold mb-0 text-success">₹<?= number_format($totalRevenueAmount ?? 0, 2) ?></h4>
                    <small class="text-muted">Direct Website Orders</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(255, 193, 7, 0.2); color: #856404;">
                    <i class="bi bi-bag-check fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total Units Sold</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($totalUnitsSold ?? 0) ?> <span class="fs-7 fw-normal text-muted">pcs</span></h4>
                    <small class="text-muted">Garments &amp; Products</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(111, 66, 193, 0.12); color: #6f42c1;">
                    <i class="bi bi-check2-circle fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Order Completion Rate</div>
                    <h4 class="fw-bold mb-0 text-dark">
                        <?= (!empty($totalOrdersCount) && $totalOrdersCount > 0) ? round((($completedCount ?? 0) / $totalOrdersCount) * 100, 1) : 0 ?>%
                    </h4>
                    <small class="text-muted"><?= number_format($pendingCount ?? 0) ?> pending / processing</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('warehouse/website-orders/history') ?>" class="row g-2 align-items-end">
            <!-- Search Keyword -->
            <div class="col-lg-5 col-md-6">
                <label class="form-label small fw-semibold text-secondary mb-1">Search Orders</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Invoice #, Customer name, Phone, Staff..." value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
            </div>

            <!-- Date Range From -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold text-secondary mb-1">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom ?? '') ?>">
            </div>

            <!-- Date Range To -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold text-secondary mb-1">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo ?? '') ?>">
            </div>

            <!-- Action Buttons -->
            <div class="col-lg-3 col-md-3 col-6 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($dateFrom) || !empty($dateTo)): ?>
                    <a href="<?= url('warehouse/website-orders/history') ?>" class="btn btn-sm btn-outline-secondary" title="Clear Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Orders History Table -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <div>
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-check me-2 text-primary"></i>Customer Sales Orders</h5>
            <small class="text-muted">Showing <?= count($orders ?? []) ?> orders matching filter criteria</small>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 fw-semibold">
            Total Value: ₹<?= number_format($totalRevenueAmount ?? 0, 2) ?>
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 140px;">Order #</th>
                    <th>Customer Details</th>
                    <th class="text-center">Items (Qty)</th>
                    <th class="text-end">Total Amount</th>
                    <th class="text-center">Payment</th>
                    <th class="text-center">Status</th>
                    <th>Date &amp; Time</th>
                    <th class="text-end" style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-cart-x fs-1 d-block mb-2 text-primary opacity-50"></i>
                            <div class="fw-semibold">No Website customer orders found.</div>
                            <small class="text-muted">Try adjusting your search criteria<?= $canCreateOrder ? ' or click below to create a new order' : '' ?>.</small>
                            <?php if ($canCreateOrder): ?>
                            <div class="mt-3">
                                <a href="<?= url('sales/website/create') ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg me-1"></i> Create Website Order
                                </a>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: foreach ($orders as $o): ?>
                    <tr>
                        <!-- Order Number -->
                        <td>
                            <div class="fw-bold text-dark font-monospace" style="font-size: 0.9rem;">
                                <?= htmlspecialchars($o['invoice_number']) ?>
                            </div>
                            <?php if (!empty($o['biller_name'])): ?>
                                <small class="text-muted" style="font-size: 0.76rem;">Staff: <?= htmlspecialchars($o['biller_name']) ?></small>
                            <?php endif; ?>
                        </td>

                        <!-- Customer Details -->
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($o['customer_name'] ?? 'Direct Customer') ?></div>
                            <?php if (!empty($o['customer_phone']) && $o['customer_phone'] !== '-'): ?>
                                <div class="small mt-0.5 text-muted">
                                    <i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($o['customer_phone']) ?>
                                    <?php if (!empty($o['customer_city'])): ?>
                                        <span class="text-secondary ms-1">| <?= htmlspecialchars($o['customer_city']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($o['customer_address']) && $o['customer_address'] !== '-'): ?>
                                <div class="small text-muted text-truncate d-inline-block mt-0.5" style="max-width: 250px;" title="<?= htmlspecialchars($o['customer_address']) ?>">
                                    <i class="bi bi-geo-alt text-secondary me-1"></i><?= htmlspecialchars($o['customer_address']) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Items Count -->
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1 fw-bold">
                                <?= number_format((int)($o['total_items_count'] ?? $o['total_pcs'] ?? $o['item_count'] ?? 0)) ?> pcs
                            </span>
                            <?php if (!empty($o['distinct_variants_count']) && $o['distinct_variants_count'] > 1): ?>
                                <div class="text-muted small mt-0.5" style="font-size: 0.72rem;"><?= $o['distinct_variants_count'] ?> items</div>
                            <?php elseif (!empty($o['item_count']) && $o['item_count'] > 1): ?>
                                <div class="text-muted small mt-0.5" style="font-size: 0.72rem;"><?= $o['item_count'] ?> items</div>
                            <?php endif; ?>
                        </td>

                        <!-- Total Amount -->
                        <td class="text-end">
                            <div class="fw-bold text-success fs-6">₹<?= number_format((float)($o['total_amount'] ?? $o['final_amount'] ?? 0), 2) ?></div>
                            <?php if (!empty($o['discount_amount']) && (float)$o['discount_amount'] > 0): ?>
                                <small class="text-danger" style="font-size: 0.74rem;">Disc: -₹<?= number_format((float)$o['discount_amount'], 2) ?></small>
                            <?php endif; ?>
                        </td>

                        <!-- Payment Mode / Status -->
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 small">
                                <?= htmlspecialchars($o['payment_mode'] ?? $o['payment_method'] ?? 'CARD') ?>
                            </span>
                            <div class="small mt-0.5" style="font-size: 0.72rem;">
                                <span class="badge bg-<?= ($o['payment_status'] ?? '') === 'PAID' ? 'success' : 'warning text-dark' ?>-subtle text-<?= ($o['payment_status'] ?? '') === 'PAID' ? 'success' : 'warning' ?>">
                                    <?= htmlspecialchars($o['payment_status'] ?? 'PAID') ?>
                                </span>
                            </div>
                        </td>

                        <!-- Order Status -->
                        <td class="text-center">
                            <?php 
                            $st = strtoupper($o['status'] ?? 'COMPLETED');
                            $stColor = in_array($st, ['COMPLETED', 'PAID', 'DELIVERED', 'DISPATCHED']) ? 'success' : ($st === 'CANCELLED' ? 'danger' : 'warning');
                            ?>
                            <span class="badge bg-<?= $stColor ?>-subtle text-<?= $stColor ?> border border-<?= $stColor ?>-subtle px-2 py-1">
                                <?= htmlspecialchars($o['status']) ?>
                            </span>
                        </td>

                        <!-- Date & Time -->
                        <td>
                            <div class="text-dark small fw-semibold"><?= date('d M Y', strtotime($o['created_at'])) ?></div>
                            <small class="text-muted" style="font-size: 0.75rem;"><?= date('h:i A', strtotime($o['created_at'])) ?></small>
                        </td>

                        <!-- Actions -->
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="<?= url('sales/website/view/' . $o['id']) ?>" class="btn btn-xs btn-outline-primary px-2 py-1" title="View Order Details">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
