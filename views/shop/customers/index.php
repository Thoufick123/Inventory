<?php
// views/shop/customers/index.php — Shop Customer Directory & 360 CRM Hub
?>
<div class="container-fluid py-4">
    <!-- Top Header & Breadcrumb Navigation -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('shop/dashboard') ?>" class="text-decoration-none"><i class="bi bi-shop me-1"></i>Shop</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Customer Details</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">
                <i class="bi bi-people-fill text-primary me-2"></i>Customer Directory &amp; CRM
            </h3>
            <small class="text-muted">Manage complete customer profiles, lifetime purchase history, and shopping activity</small>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Registered Customers -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Total Customers</span>
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
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Customer Revenue</span>
                        <h3 class="fw-bold mb-1 text-success">₹<?= number_format($metrics['total_revenue'], 2) ?></h3>
                        <small class="text-muted"><i class="bi bi-bag-check me-1"></i>Cumulative Billed Sales</small>
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
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Avg Customer LTV</span>
                        <h3 class="fw-bold mb-1 text-primary">₹<?= number_format($metrics['avg_ltv'], 2) ?></h3>
                        <small class="text-muted"><i class="bi bi-graph-up-arrow me-1"></i>Avg Spend per Buyer</small>
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
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Repeat Customer Rate</span>
                        <h3 class="fw-bold mb-1 text-warning"><?= $metrics['repeat_rate'] ?>%</h3>
                        <small class="text-muted"><i class="bi bi-arrow-repeat me-1"></i><?= number_format($metrics['repeat_customers']) ?> Repeat Buyers</small>
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
            <form method="GET" action="<?= url('shop/customers') ?>" class="row g-2 align-items-center" id="filterForm" onsubmit="return true;">
                <!-- Search Input -->
                <div class="col-12 col-md-8">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" id="customerSearchInput" class="form-control bg-light border-start-0" 
                               placeholder="Search by Name, Mobile, Email, GSTIN, Address..." 
                               value="<?= e($filters['q']) ?>" autocomplete="off">
                        <button type="button" id="btnClearCustSearch" class="btn btn-outline-secondary <?= empty($filters['q']) ? 'd-none' : '' ?>" title="Clear Search" onclick="clearCustomerSearch()"><i class="bi bi-x"></i></button>
                    </div>
                </div>

                <!-- City / Region Filter -->
                <div class="col-12 col-md-4">
                    <select name="city" id="customerCitySelect" class="form-select form-select-sm bg-light">
                        <option value="">All Cities</option>
                        <?php foreach ($metrics['cities'] as $ct): ?>
                            <option value="<?= e($ct) ?>" <?= ($filters['city'] === $ct) ? 'selected' : '' ?>><?= e($ct) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </form>
        </div>
    </div>

    <!-- Customer Directory Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0 text-dark">Customer Records</h5>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 font-monospace" id="customerCountBadge">
                    <?= count($customers) ?> <?= count($customers) === 1 ? 'Customer' : 'Customers' ?> Found
                </span>
            </div>
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
                        <th>Last Activity</th>
                        <th class="text-end pe-3" style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="customerTableBody">
                    <?php if (empty($customers)): ?>
                        <tr id="emptyCustRow">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <div class="fw-semibold">No customer records found matching the filter criteria.</div>
                                <small>Try adjusting your search query or add a new customer.</small>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $idx => $c): 
                            // Extract initials for avatar
                            $words = explode(' ', trim($c['name']));
                            $initials = '';
                            foreach (array_slice($words, 0, 2) as $w) {
                                $initials .= !empty($w) ? strtoupper($w[0]) : '';
                            }
                            if (empty($initials)) $initials = 'C';
                            $searchHaystack = strtolower(($c['name'] ?? '') . ' ' . ($c['phone'] ?? '') . ' ' . ($c['email'] ?? '') . ' ' . ($c['gst_number'] ?? '') . ' ' . ($c['city'] ?? '') . ' ' . ($c['address'] ?? '') . ' #' . $c['id']);
                        ?>
                            <tr class="customer-row" 
                                data-search="<?= e($searchHaystack) ?>"
                                data-city="<?= e(strtolower($c['city'] ?? '')) ?>"
                                data-name="<?= e(strtolower($c['name'] ?? '')) ?>"
                                data-spend="<?= (float)($c['total_spend'] ?? 0) ?>"
                                data-orders="<?= (int)($c['total_orders'] ?? 0) ?>"
                                data-date="<?= e($c['last_visit_date'] ?? $c['created_at'] ?? '') ?>">
                                <td class="ps-3 text-muted fw-bold small cust-idx"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center me-2 border border-primary-subtle" 
                                             style="width: 38px; height: 38px; min-width: 38px; font-size: 13px;">
                                            <?= e($initials) ?>
                                        </div>
                                        <div class="overflow-hidden">
                                            <a href="<?= url('shop/customers/view/' . $c['id']) ?>" class="fw-bold text-dark text-decoration-none text-truncate d-block">
                                                <?= e($c['name']) ?>
                                            </a>
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
                                            <a href="tel:<?= e($c['phone']) ?>" class="text-decoration-none font-monospace fw-semibold text-primary small">
                                                <i class="bi bi-telephone-fill me-1 small"></i><?= e($c['phone']) ?>
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
                                        <?= (int)$c['total_orders'] ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold text-success">₹<?= number_format((float)$c['total_spend'], 2) ?></div>
                                    <?php if ((int)$c['total_items_bought'] > 0): ?>
                                        <small class="text-muted" style="font-size: 10px;"><?= (int)$c['total_items_bought'] ?> pcs</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-muted small fw-semibold">
                                    ₹<?= number_format((float)$c['avg_order_value'], 2) ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['last_visit_date'])): ?>
                                        <div class="small fw-semibold text-dark"><?= date('d M Y', strtotime($c['last_visit_date'])) ?></div>
                                        <?php if (!empty($c['last_sale_id'])): ?>
                                            <a href="<?= url('shop/pos/invoice/' . $c['last_sale_id']) ?>" target="_blank" class="text-decoration-none small text-muted font-monospace" style="font-size: 10px;">
                                                <i class="bi bi-receipt me-1"></i><?= e($c['last_invoice_number']) ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small class="text-muted">No purchases yet</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <!-- Only View Details Button -->
                                    <a href="<?= url('shop/customers/view/' . $c['id']) ?>" 
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-none fw-semibold" 
                                       title="View Customer Profile & Purchase History">
                                        <i class="bi bi-eye me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL: Add / Edit Customer
     ========================================== -->
<?php if (hasPermission('customers', 'create') || hasPermission('customers', 'edit')): ?>
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="customerForm" method="POST" action="<?= url('shop/customers/store') ?>">
                <div class="modal-header bg-light border-0 py-3 px-4 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark" id="customerModalTitle">
                        <i class="bi bi-person-plus-fill text-primary me-2"></i>Add New Customer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="editCustomerId" name="id" value="">

                    <div class="row g-3">
                        <!-- Full Name -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark mb-1">Customer Full Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" name="name" id="modalCustName" class="form-control" placeholder="e.g. SARAN RAJ" required>
                            </div>
                        </div>

                        <!-- Mobile Number -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-bold text-dark mb-1">Mobile Phone Number <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                <input type="tel" name="phone" id="modalCustPhone" class="form-control" placeholder="e.g. 740248996" required>
                            </div>
                        </div>

                        <!-- Email Address -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-bold text-dark mb-1">Email Address</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="modalCustEmail" class="form-control" placeholder="e.g. saran@gmail.com">
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark mb-1">Street Address</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" name="address" id="modalCustAddress" class="form-control" placeholder="e.g. 14 Trichy Road, Sulur">
                            </div>
                        </div>

                        <!-- City -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-bold text-dark mb-1">City / Town</label>
                            <input type="text" name="city" id="modalCustCity" class="form-control form-control-sm" placeholder="e.g. Coimbatore">
                        </div>

                        <!-- State -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-bold text-dark mb-1">State</label>
                            <input type="text" name="state" id="modalCustState" class="form-control form-control-sm" value="Tamil Nadu" placeholder="Tamil Nadu">
                        </div>

                        <!-- GST Number -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-bold text-dark mb-1">GSTIN Number (Optional)</label>
                            <input type="text" name="gst_number" id="modalCustGst" class="form-control form-control-sm font-monospace text-uppercase" placeholder="33AAAAA0000A1Z5">
                        </div>

                        <!-- Active Status Checkbox -->
                        <div class="col-12">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" name="is_active" id="modalCustStatus" value="1" checked>
                                <label class="form-check-label small fw-semibold text-dark" for="modalCustStatus">Account is Active for Billing</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm" id="btnSaveCustomer">
                        <i class="bi bi-check-circle me-1"></i> Save Customer Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
window.clearCustomerSearch = function() {
    const sInput = document.getElementById('customerSearchInput');
    const btnClear = document.getElementById('btnClearCustSearch');
    if (sInput) {
        sInput.value = '';
        if (btnClear) btnClear.classList.add('d-none');
        if (window.applyCustomerFilters) window.applyCustomerFilters();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('customerModal');
    const customerModal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('customerForm');
    const modalTitle = document.getElementById('customerModalTitle');
    const editIdInput = document.getElementById('editCustomerId');

    window.openCreateCustomerModal = function() {
        form.reset();
        editIdInput.value = '';
        form.action = "<?= url('shop/customers/store') ?>";
        modalTitle.innerHTML = '<i class="bi bi-person-plus-fill text-primary me-2"></i>Add New Customer';
        document.getElementById('modalCustState').value = 'Tamil Nadu';
        document.getElementById('modalCustStatus').checked = true;
    };

    document.querySelectorAll('.btn-edit-customer').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            editIdInput.value = id;
            form.action = "<?= url('shop/customers/update') ?>/" + id;
            modalTitle.innerHTML = '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Customer Profile';

            document.getElementById('modalCustName').value = this.getAttribute('data-name') || '';
            document.getElementById('modalCustPhone').value = this.getAttribute('data-phone') || '';
            document.getElementById('modalCustEmail').value = this.getAttribute('data-email') || '';
            document.getElementById('modalCustAddress').value = this.getAttribute('data-address') || '';
            document.getElementById('modalCustCity').value = this.getAttribute('data-city') || '';
            document.getElementById('modalCustState').value = this.getAttribute('data-state') || 'Tamil Nadu';
            document.getElementById('modalCustGst').value = this.getAttribute('data-gst') || '';
            document.getElementById('modalCustStatus').checked = this.getAttribute('data-status') == '1';

            customerModal.show();
        });
    });

    // Instant Client-Side Letter-by-Letter Search & Filter Engine
    const searchInput = document.getElementById('customerSearchInput');
    const citySelect = document.getElementById('customerCitySelect');
    const tableBody = document.getElementById('customerTableBody');
    const countBadge = document.getElementById('customerCountBadge');
    const btnClear = document.getElementById('btnClearCustSearch');
    const rows = Array.from(document.querySelectorAll('.customer-row'));

    window.applyCustomerFilters = function() {
        if (!rows.length) return;

        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const cityVal = (citySelect ? citySelect.value : '').trim().toLowerCase();

        if (btnClear) {
            if (query) btnClear.classList.remove('d-none');
            else btnClear.classList.add('d-none');
        }

        let visibleCount = 0;
        let visibleRows = [];

        rows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            const rowCity = (row.getAttribute('data-city') || '').toLowerCase();

            const matchQuery = !query || searchData.includes(query);
            const matchCity = !cityVal || rowCity === cityVal || searchData.includes(cityVal);

            if (matchQuery && matchCity) {
                row.style.display = '';
                visibleCount++;
                visibleRows.push(row);
            } else {
                row.style.display = 'none';
            }
        });

        // Client-side instant sorting
        if (visibleRows.length > 0 && tableBody) {
            visibleRows.sort((a, b) => {
                const dateA = a.getAttribute('data-date') || '';
                const dateB = b.getAttribute('data-date') || '';
                return dateB.localeCompare(dateA);
            });
            visibleRows.forEach((r, idx) => {
                const idxEl = r.querySelector('.cust-idx');
                if (idxEl) idxEl.textContent = idx + 1;
                tableBody.appendChild(r);
            });
        }

        if (countBadge) {
            countBadge.textContent = `${visibleCount} ${visibleCount === 1 ? 'Customer' : 'Customers'} Found`;
        }

        // Show/hide no match row
        let emptyRow = document.getElementById('noCustomerMatchRow');
        if (visibleCount === 0) {
            if (!emptyRow && tableBody) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'noCustomerMatchRow';
                emptyRow.innerHTML = `
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <h6 class="fw-bold text-secondary">No Matching Customer Records</h6>
                        <p class="small text-muted mb-2">No customers match "${escapeHtml(query)}".</p>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="clearCustomerSearch()">
                            Clear Search
                        </button>
                    </td>
                `;
                tableBody.appendChild(emptyRow);
            }
        } else if (emptyRow) {
            emptyRow.remove();
        }
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyCustomerFilters);
        searchInput.addEventListener('keyup', applyCustomerFilters);
    }
    if (citySelect) {
        citySelect.addEventListener('change', applyCustomerFilters);
    }
    if (sortSelect) {
        sortSelect.addEventListener('change', applyCustomerFilters);
    }
});
</script>
