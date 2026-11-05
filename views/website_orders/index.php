<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-globe me-2 text-primary"></i>Website E-Commerce Channel Dashboard</h2>
        <span class="text-muted">Dynamic tracking of Website allocated stock, orders count, total revenue, and available stock valuation</span>
    </div>
</div>

<!-- 5 Dynamic KPI Cards -->
<div class="row g-3 mb-4">
    <!-- Card 1: Website Inventory Count (Allocated) -->
    <div class="col-sm-6 col-xl-2-4" style="flex: 0 0 auto; width: 20%;">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-2" style="background-color: rgba(13, 110, 253, 0.12); color: #0d6efd;">
                    <i class="bi bi-boxes fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Website Inventory</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($allocatedInventoryQty ?? 0) ?> <span class="fs-7 fw-normal text-muted">pcs</span></h4>
                    <small class="text-muted">Total allocated &amp; inwarded</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Order Count -->
    <div class="col-sm-6 col-xl-2-4" style="flex: 0 0 auto; width: 20%;">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-2" style="background-color: rgba(111, 66, 193, 0.12); color: #6f42c1;">
                    <i class="bi bi-cart-check fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Order Count</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($orderCount ?? 0) ?></h4>
                    <small class="text-muted"><?= number_format($soldQty ?? 0) ?> pcs sold</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Total Revenue (Orders Price Only) -->
    <div class="col-sm-6 col-xl-2-4" style="flex: 0 0 auto; width: 20%;">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-2" style="background-color: rgba(255, 193, 7, 0.2); color: #856404;">
                    <i class="bi bi-currency-rupee fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total Revenue</div>
                    <h4 class="fw-bold mb-0 text-dark">₹<?= number_format($totalRevenue ?? 0, 2) ?></h4>
                    <small class="text-muted">From orders price</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Available Stock (Allocated Stock Minus Total Sold) -->
    <div class="col-sm-6 col-xl-2-4" style="flex: 0 0 auto; width: 20%;">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-2" style="background-color: rgba(13, 202, 240, 0.15); color: #0891b2;">
                    <i class="bi bi-box-seam fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Available Stock</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($availableStockQty ?? 0) ?> <span class="fs-7 fw-normal text-muted">pcs</span></h4>
                    <small class="text-muted">On-hand good stock</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 5: Available Stock Revenue / Valuation -->
    <div class="col-sm-6 col-xl-2-4" style="flex: 0 0 auto; width: 20%;">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-2" style="background-color: rgba(25, 135, 84, 0.15); color: #198754;">
                    <i class="bi bi-wallet2 fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Available Stock Value</div>
                    <h4 class="fw-bold mb-0 text-dark">₹<?= number_format($availableStockValue ?? 0, 2) ?></h4>
                    <small class="text-muted">Available stock amount</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Section -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <div>
            <h5 class="mb-0 fw-bold"><i class="bi bi-box-arrow-right me-2 text-primary"></i>Website Inventory</h5>
            <small class="text-muted">Inventory dispatched from warehouse specifically to Website e-commerce channel</small>
        </div>
        <div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 fw-semibold">
                Total Records: <?= count($allocations ?? []) ?>
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Allocation Ref #</th>
                    <th>Destination Channel</th>
                    <th>Allocated Quantity</th>
                    <th>Total Value</th>
                    <th>Status</th>
                    <th>Date</th>
                    <?php if (hasPermission('website_orders', 'view')): ?>
                    <th class="text-end">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allocations)): ?>
                    <tr>
                        <td colspan="<?= hasPermission('website_orders', 'view') ? 7 : 6 ?>" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-primary"></i>
                            No Website inventory recorded yet.
                        </td>
                    </tr>
                <?php else: foreach ($allocations as $a): 
                    $aNum    = $a['number'] ?? $a['allocation_number'] ?? ('#' . ($a['id'] ?? ''));
                    $aChan   = $a['customer_or_channel'] ?? $a['channel_name'] ?? 'Website Channel';
                    $aQty    = (float)($a['quantity'] ?? $a['total_quantity'] ?? 0);
                    $aAmt    = (float)($a['amount'] ?? $a['total_value'] ?? 0);
                    $aStatus = $a['status'] ?? 'PENDING';
                    $aDate   = !empty($a['date']) ? $a['date'] : (!empty($a['created_at']) ? $a['created_at'] : date('Y-m-d H:i:s'));
                ?>
                    <tr>
                        <td class="fw-bold text-dark">
                            <span class="badge bg-primary me-1"><i class="bi bi-box-arrow-right"></i> Allocation</span>
                            <?= htmlspecialchars((string)$aNum) ?>
                        </td>
                        <td><?= htmlspecialchars((string)$aChan) ?></td>
                        <td><span class="fw-bold text-dark"><?= number_format($aQty) ?> pcs</span></td>
                        <td><span class="fw-bold text-success">₹<?= number_format($aAmt, 2) ?></span></td>
                        <td>
                            <span class="badge bg-<?= in_array($aStatus, ['COMPLETED', 'APPROVED', 'DISPATCHED']) ? 'success' : 'warning text-dark' ?>">
                                <?= htmlspecialchars((string)$aStatus) ?>
                            </span>
                        </td>
                        <td><small class="text-muted"><?= date('d M Y, h:i A', strtotime((string)$aDate)) ?></small></td>
                        <td class="text-end">
                            <a href="<?= url('warehouse/allocation/view/' . ($a['id'] ?? '')) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View Allocation
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Orders Quick Preview -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <div>
            <h5 class="mb-0 fw-bold"><i class="bi bi-cart-check me-2 text-success"></i>Recent Website Customer Orders</h5>
            <small class="text-muted">Latest customer sales orders placed via Website store</small>
        </div>
        <?php if (hasPermission('website_orders', 'view')): ?>
        <a href="<?= url('warehouse/website-orders/history') ?>" class="btn btn-sm btn-outline-primary fw-semibold">
            View All Orders (<?= number_format($orderCount ?? count($orders ?? $recentSales ?? [])) ?>) <i class="bi bi-arrow-right ms-1"></i>
        </a>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Order Ref #</th>
                    <th>Customer Details</th>
                    <th>Quantity (pcs)</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $ordersList = $orders ?? $recentSales ?? [];
                $recentOrders = array_slice($ordersList, 0, 5);
                if (empty($recentOrders)): 
                ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-cart-x fs-2 d-block mb-2 text-primary"></i>
                            No Website customer orders recorded yet.
                        </td>
                    </tr>
                <?php else: foreach ($recentOrders as $o): 
                    $oNum    = $o['number'] ?? $o['invoice_number'] ?? ('#' . ($o['id'] ?? ''));
                    $oCust   = $o['customer_name'] ?? $o['customer_or_channel'] ?? 'Direct Customer';
                    $oPhone  = $o['customer_phone'] ?? '';
                    $oAddr   = $o['customer_address'] ?? '';
                    $oQty    = (float)($o['quantity'] ?? $o['total_pcs'] ?? $o['total_items_count'] ?? 0);
                    $oAmt    = (float)($o['amount'] ?? $o['total_amount'] ?? $o['final_amount'] ?? 0);
                    $oStatus = $o['status'] ?? 'COMPLETED';
                    $oDate   = !empty($o['date']) ? $o['date'] : (!empty($o['created_at']) ? $o['created_at'] : date('Y-m-d H:i:s'));
                ?>
                    <tr>
                        <td class="fw-bold text-dark">
                            <span class="badge bg-primary me-1"><i class="bi bi-globe"></i> Order</span>
                            <?= htmlspecialchars((string)$oNum) ?>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars((string)$oCust) ?></div>
                            <?php if (!empty($oPhone) && $oPhone !== '-'): ?>
                                <div class="small text-muted"><i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars((string)$oPhone) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($oAddr) && $oAddr !== '-'): ?>
                                <div class="small text-muted text-truncate d-inline-block" style="max-width: 220px;" title="<?= htmlspecialchars((string)$oAddr) ?>">
                                    <i class="bi bi-geo-alt text-secondary me-1"></i><?= htmlspecialchars((string)$oAddr) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><span class="fw-bold text-dark"><?= number_format($oQty) ?> pcs</span></td>
                        <td><span class="fw-bold text-success">₹<?= number_format($oAmt, 2) ?></span></td>
                        <td>
                            <span class="badge bg-<?= in_array($oStatus, ['COMPLETED', 'PAID', 'DELIVERED', 'DISPATCHED']) ? 'success' : 'warning text-dark' ?>">
                                <?= htmlspecialchars((string)$oStatus) ?>
                            </span>
                        </td>
                        <td><small class="text-muted"><?= date('d M Y, h:i A', strtotime((string)$oDate)) ?></small></td>
                        <?php if (hasPermission('website_orders', 'view')): ?>
                        <td class="text-end">
                            <a href="<?= url('sales/website/view/' . ($o['id'] ?? '')) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View Order
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
