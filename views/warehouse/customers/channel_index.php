<?php
// views/warehouse/customers/channel_index.php — Channel-Specific Customer Directory (WhatsApp & Website)
?>
<div class="container-fluid py-4">
    <!-- Top Header & Breadcrumb Navigation -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('warehouse/dashboard') ?>" class="text-decoration-none"><i class="bi bi-box-seam me-1"></i>Warehouse</a></li>
                    <li class="breadcrumb-item"><a href="<?= $ordersUrl ?>" class="text-decoration-none"><?= e($channelTitle) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Customer Directory</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">
                <i class="bi <?= $channelIcon ?> me-2"></i><?= e($channelTitle) ?> Directory
            </h3>
            <small class="text-muted">Dedicated CRM directory for <?= e(strtolower($channelTitle)) ?> with lifetime purchases and spend analytics</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $ordersUrl ?>" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm fw-semibold">
                <i class="bi bi-card-checklist me-1"></i> Channel Orders
            </a>
            <?php if (hasPermission('orders', 'create') || hasPermission('whatsapp_orders', 'create') || hasPermission('website_orders', 'create') || hasPermission('purchases', 'create')): ?>
            <a href="<?= $createUrl ?>" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> New Order
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Registered Channel Customers -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Total <?= e($channel) ?> Clients</span>
                        <h3 class="fw-bold mb-1 text-dark"><?= number_format($metrics['total_customers']) ?></h3>
                        <div class="small text-muted">
                            <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i><?= number_format($metrics['active_customers']) ?> Active</span>
                            • <span class="text-secondary"><?= number_format($metrics['inactive_customers']) ?> Inactive</span>
                        </div>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Customer Lifetime Revenue -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Channel Revenue</span>
                        <h3 class="fw-bold mb-1 text-success">₹<?= number_format($metrics['total_revenue'], 2) ?></h3>
                        <small class="text-muted"><i class="bi bi-bag-check me-1"></i>Cumulative Channel Orders</small>
                    </div>
                    <div class="rounded-circle bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-currency-rupee fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Average Customer LTV -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Avg Spend per Buyer</span>
                        <h3 class="fw-bold mb-1 text-primary">₹<?= number_format($metrics['avg_ltv'], 2) ?></h3>
                        <small class="text-muted"><i class="bi bi-graph-up-arrow me-1"></i>Average Lifetime Value</small>
                    </div>
                    <div class="rounded-circle bg-info-subtle text-info p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-gem fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Repeat Customer Rate -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Repeat Buyer Rate</span>
                        <h3 class="fw-bold mb-1 text-warning"><?= $metrics['repeat_rate'] ?>%</h3>
                        <small class="text-muted"><i class="bi bi-arrow-repeat me-1"></i><?= number_format($metrics['repeat_customers']) ?> Repeat Clients</small>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-stars fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center" id="filterForm">
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control bg-light border-start-0 ps-0" placeholder="Search by name, phone, city..." value="<?= e($filters['q']) ?>">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active Only</option>
                        <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="tier" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= ($filters['tier'] ?? 'all') === 'all' ? 'selected' : '' ?>>All Tiers</option>
                        <option value="vip" <?= ($filters['tier'] ?? '') === 'vip' ? 'selected' : '' ?>>VIP (₹10k+)</option>
                        <option value="regular" <?= ($filters['tier'] ?? '') === 'regular' ? 'selected' : '' ?>>Regular (₹3k-₹10k)</option>
                        <option value="new" <?= ($filters['tier'] ?? '') === 'new' ? 'selected' : '' ?>>New (< ₹3k)</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <select name="sort" class="form-select flex-grow-1" onchange="this.form.submit()">
                        <option value="recent" <?= ($filters['sort'] ?? 'recent') === 'recent' ? 'selected' : '' ?>>Most Recent Order</option>
                        <option value="spend_desc" <?= ($filters['sort'] ?? '') === 'spend_desc' ? 'selected' : '' ?>>Highest Spend</option>
                        <option value="orders_desc" <?= ($filters['sort'] ?? '') === 'orders_desc' ? 'selected' : '' ?>>Most Orders</option>
                        <option value="name_asc" <?= ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                    </select>
                    <?php if (!empty($filters['q']) || ($filters['status'] ?? 'all') !== 'all' || ($filters['tier'] ?? 'all') !== 'all'): ?>
                        <a href="<?= current_url() ?>" class="btn btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-circle"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Customers Data Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-table me-2 text-primary"></i><?= e($channelTitle) ?> Records (<?= count($customers) ?>)
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                <thead class="table-light text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="ps-4">Customer Name &amp; Channel</th>
                        <th>Phone / Mobile</th>
                        <th>Location</th>
                        <th class="text-center">Total Orders</th>
                        <th class="text-end">Lifetime Spend</th>
                        <th class="text-center">Tier</th>
                        <th class="text-center">Last Active</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="my-3">
                                    <i class="bi bi-person-x fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    <div class="fw-semibold">No <?= e(strtolower($channelTitle)) ?> found</div>
                                    <small class="text-muted">Customers registered from this channel will appear here.</small>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <div class="rounded-circle bg-light border text-primary fw-bold d-flex align-items-center justify-content-center shadow-2xs" style="width: 36px; height: 36px; font-size: 0.85rem;">
                                            <?= strtoupper(substr($c['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= e($c['name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary border px-1.5 py-0" style="font-size: 0.68rem;">
                                                <i class="bi <?= $channelIcon ?> me-0.5"></i> <?= e($c['source_channel'] ?? $channel) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-monospace text-dark fw-semibold"><?= e($c['phone']) ?></div>
                                    <?php if (!empty($c['email'])): ?>
                                        <small class="text-muted d-block text-truncate" style="max-width: 160px;"><?= e($c['email']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-dark"><?= e($c['city'] ?: 'Local') ?></div>
                                    <?php if (!empty($c['state'])): ?>
                                        <small class="text-muted"><?= e($c['state']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-2 py-1 fw-bold"><?= (int)$c['total_orders'] ?> orders</span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold text-success font-monospace">₹<?= number_format((float)$c['total_spend'], 2) ?></span>
                                    <div class="text-muted" style="font-size: 0.72rem;">Avg: ₹<?= number_format((float)$c['avg_order_value'], 2) ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $c['tier_badge'] ?> px-2 py-1">
                                        <i class="bi <?= $c['tier_icon'] ?> me-1"></i><?= $c['tier_label'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted"><?= !empty($c['last_visit_date']) ? date('d M Y', strtotime($c['last_visit_date'])) : date('d M Y', strtotime($c['created_at'])) ?></small>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (hasPermission('orders', 'create') || hasPermission('whatsapp_orders', 'create') || hasPermission('website_orders', 'create') || hasPermission('purchases', 'create')): ?>
                                        <a href="<?= $createUrl ?>?customer_id=<?= $c['id'] ?>" class="btn btn-outline-primary" title="Create New Order for Customer">
                                            <i class="bi bi-cart-plus me-1"></i> Order
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted small">View Only</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
