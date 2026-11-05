<?php
// views/whatsapp_orders/customers.php — WhatsApp Customer Directory & CRM Hub

$totalCust = count($customers);
$totalOrders = 0;
$totalSpent = 0.0;
$cities = [];

foreach ($customers as $c) {
    $totalOrders += (int)($c['total_orders'] ?? 0);
    $totalSpent += (float)($c['total_spent'] ?? 0);
    if (!empty($c['city'])) {
        $cities[$c['city']] = true;
    }
}
$avgSpend = $totalCust > 0 ? ($totalSpent / $totalCust) : 0;
$uniqueCities = array_keys($cities);
sort($uniqueCities);
?>
<div class="container-fluid py-4">
    <!-- Top Header & Breadcrumb Navigation -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('warehouse/whatsapp-orders') ?>" class="text-decoration-none"><i class="bi bi-whatsapp text-success me-1"></i>WhatsApp Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Customers</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">
                <i class="bi bi-people-fill text-success me-2"></i>WhatsApp Customer Directory
            </h3>
            <small class="text-muted">Manage WhatsApp channel buyers, order history, and customer lifetime value</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('warehouse/whatsapp-orders/new') ?>" class="btn btn-success rounded-pill px-4 shadow-sm fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> New WhatsApp Order
            </a>
            <a href="<?= url('warehouse/whatsapp-orders') ?>" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm fw-semibold">
                <i class="bi bi-list-check me-1"></i> Orders Hub
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="row g-3 mb-4">
        <!-- 1. Total WhatsApp Customers -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Total Buyers</span>
                        <h3 class="fw-bold mb-1 text-dark"><?= number_format($totalCust) ?></h3>
                        <small class="text-muted"><i class="bi bi-person-check text-success me-1"></i>Active WhatsApp Clients</small>
                    </div>
                    <div class="rounded-circle bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Channel Orders -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Total Orders</span>
                        <h3 class="fw-bold mb-1 text-primary"><?= number_format($totalOrders) ?></h3>
                        <small class="text-muted"><i class="bi bi-receipt me-1"></i>Completed WhatsApp Invoices</small>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-cart-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. WhatsApp Lifetime Revenue -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Lifetime Revenue</span>
                        <h3 class="fw-bold mb-1 text-success">₹<?= number_format($totalSpent, 2) ?></h3>
                        <small class="text-muted"><i class="bi bi-currency-rupee me-1"></i>Cumulative Channel GMV</small>
                    </div>
                    <div class="rounded-circle bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Average Customer Spend -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Avg Customer LTV</span>
                        <h3 class="fw-bold mb-1 text-warning">₹<?= number_format($avgSpend, 2) ?></h3>
                        <small class="text-muted"><i class="bi bi-graph-up me-1"></i>Average Value Per Buyer</small>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-stars fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search, Filter & Quick Action Bar Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('warehouse/whatsapp-orders/customers') ?>" class="row g-2 align-items-center" id="filterForm">
                <!-- Search Input -->
                <div class="col-12 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="customerSearchInput" class="form-control bg-light border-start-0" 
                               placeholder="Search by Name, Phone, City, Address..." 
                               value="<?= e($search ?? '') ?>" autocomplete="off">
                        <?php if (!empty($search)): ?>
                            <a href="<?= url('warehouse/whatsapp-orders/customers') ?>" class="btn btn-outline-secondary" title="Clear Search"><i class="bi bi-x"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- City / Region Filter -->
                <div class="col-6 col-md-3">
                    <select id="customerCityFilter" class="form-select form-select-sm bg-light" onchange="filterCustomerTable()">
                        <option value="">All Cities (<?= count($uniqueCities) ?>)</option>
                        <?php foreach ($uniqueCities as $ct): ?>
                            <option value="<?= e(strtolower($ct)) ?>"><?= e($ct) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sort Order -->
                <div class="col-6 col-md-3">
                    <select id="customerSortSelect" class="form-select form-select-sm bg-light" onchange="sortCustomerTable()">
                        <option value="spend_desc">Highest Spend (₹)</option>
                        <option value="orders_desc">Most Orders</option>
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="recent">Recently Active</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Customer Directory Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0 text-dark">Customer Directory</h5>
                <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 font-monospace" id="customerCountBadge">
                    <?= $totalCust ?> <?= $totalCust === 1 ? 'Customer' : 'Customers' ?> Found
                </span>
            </div>
            <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 shadow-none text-muted" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print / Export
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="customerTable">
                <thead class="table-light text-muted small text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                    <tr>
                        <th class="ps-3" style="width: 45px;">#</th>
                        <th>Customer Details</th>
                        <th>Contact &amp; Location</th>
                        <th class="text-center">Orders</th>
                        <th class="text-end">Total Spend</th>
                        <th class="text-end">Avg / Order</th>
                        <th>Last Order</th>
                        <th class="text-end pe-3" style="width: 130px;">Quick Actions</th>
                    </tr>
                </thead>
                <tbody id="customerTableBody">
                    <?php if (empty($customers)): ?>
                        <tr id="emptyCustRow">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <div class="fw-semibold">No WhatsApp customer records found.</div>
                                <small>Customers who purchase through WhatsApp orders will appear here automatically.</small>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $idx => $c): 
                            $words = explode(' ', trim($c['name'] ?? ''));
                            $initials = '';
                            foreach (array_slice($words, 0, 2) as $w) {
                                $initials .= !empty($w) ? strtoupper($w[0]) : '';
                            }
                            if (empty($initials)) $initials = 'W';
                            $custOrders = (int)($c['total_orders'] ?? 0);
                            $custSpend = (float)($c['total_spent'] ?? 0);
                            $custAvg = $custOrders > 0 ? ($custSpend / $custOrders) : 0;
                            $searchHaystack = strtolower(($c['name'] ?? '') . ' ' . ($c['phone'] ?? '') . ' ' . ($c['email'] ?? '') . ' ' . ($c['city'] ?? '') . ' ' . ($c['address'] ?? '') . ' #' . ($c['id'] ?? ''));
                            $cleanPhone = preg_replace('/[^0-9]/', '', (string)($c['phone'] ?? ''));
                        ?>
                            <tr class="customer-row" 
                                data-search="<?= e($searchHaystack) ?>"
                                data-city="<?= e(strtolower($c['city'] ?? '')) ?>"
                                data-name="<?= e(strtolower($c['name'] ?? '')) ?>"
                                data-spend="<?= $custSpend ?>"
                                data-orders="<?= $custOrders ?>"
                                data-date="<?= e($c['last_order_date'] ?? $c['created_at'] ?? '') ?>">
                                <td class="ps-3 text-muted fw-bold small cust-idx"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-success-subtle text-success fw-bold d-flex align-items-center justify-content-center me-2 border border-success-subtle" 
                                             style="width: 38px; height: 38px; min-width: 38px; font-size: 13px;">
                                            <?= e($initials) ?>
                                        </div>
                                        <div class="overflow-hidden">
                                            <span class="fw-bold text-dark text-truncate d-block">
                                                <?= e($c['name'] ?? 'Unknown Customer') ?>
                                            </span>
                                            <small class="text-muted" style="font-size: 11px;">
                                                ID: #<?= $c['id'] ?>
                                                <?php if (!empty($c['gst_number'])): ?>
                                                    • GST: <span class="font-monospace fw-semibold"><?= e($c['gst_number']) ?></span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($c['phone'])): ?>
                                        <div>
                                            <a href="https://wa.me/<?= (strlen($cleanPhone) === 10 ? '91' . $cleanPhone : $cleanPhone) ?>" target="_blank" class="text-decoration-none font-monospace fw-semibold text-success small">
                                                <i class="bi bi-whatsapp me-1 small"></i><?= e($c['phone']) ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($c['city']) || !empty($c['address'])): ?>
                                        <small class="text-muted text-truncate d-block" style="font-size: 11px; max-width: 180px;">
                                            <i class="bi bi-geo-alt me-1"></i><?= e(!empty($c['city']) ? $c['city'] : $c['address']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace fs-6">
                                        <?= $custOrders ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold text-success">₹<?= number_format($custSpend, 2) ?></div>
                                </td>
                                <td class="text-end text-muted small fw-semibold">
                                    ₹<?= number_format($custAvg, 2) ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['last_order_date'])): ?>
                                        <div class="small fw-semibold text-dark"><?= date('d M Y', strtotime($c['last_order_date'])) ?></div>
                                        <small class="text-muted" style="font-size: 10px;"><?= date('h:i A', strtotime($c['last_order_date'])) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">No orders</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (!empty($cleanPhone)): ?>
                                            <a href="https://wa.me/<?= (strlen($cleanPhone) === 10 ? '91' . $cleanPhone : $cleanPhone) ?>" 
                                               target="_blank" 
                                               class="btn btn-outline-success rounded-pill px-2 py-1 shadow-none" 
                                               title="Chat on WhatsApp">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= url('warehouse/whatsapp-orders?customer_id=' . $c['id']) ?>" 
                                           class="btn btn-outline-primary rounded-pill px-2 py-1 shadow-none ms-1" 
                                           title="View Orders">
                                            <i class="bi bi-receipt"></i>
                                        </a>
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

<script>
function filterCustomerTable() {
    const city = document.getElementById('customerCityFilter').value.toLowerCase();
    const rows = document.querySelectorAll('.customer-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowCity = row.getAttribute('data-city') || '';
        const matchCity = !city || rowCity === city;

        if (matchCity) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const badge = document.getElementById('customerCountBadge');
    if (badge) {
        badge.innerText = visibleCount + (visibleCount === 1 ? ' Customer Found' : ' Customers Found');
    }
}

function sortCustomerTable() {
    const sortVal = document.getElementById('customerSortSelect').value;
    const tbody = document.getElementById('customerTableBody');
    const rows = Array.from(tbody.querySelectorAll('.customer-row'));

    rows.sort((a, b) => {
        if (sortVal === 'spend_desc') {
            return parseFloat(b.getAttribute('data-spend')) - parseFloat(a.getAttribute('data-spend'));
        } else if (sortVal === 'orders_desc') {
            return parseInt(b.getAttribute('data-orders')) - parseInt(a.getAttribute('data-orders'));
        } else if (sortVal === 'name_asc') {
            return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
        } else if (sortVal === 'recent') {
            return b.getAttribute('data-date').localeCompare(a.getAttribute('data-date'));
        }
        return 0;
    });

    rows.forEach((row, i) => {
        const idxCell = row.querySelector('.cust-idx');
        if (idxCell) idxCell.innerText = i + 1;
        tbody.appendChild(row);
    });
}
</script>
