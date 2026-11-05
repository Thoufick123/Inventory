<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-shop-window me-2 text-primary"></i>Warehouse Third-Party Suppliers</h2>
        <!-- <span class="text-muted">Manage third-party manufacturers, brands, suppliers, product buying bills &amp; warehouse stock inward</span> -->
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('wh_sellers', 'create')): ?>
        <a href="<?= url('warehouse/sellers/create') ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Register New Supplier
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- METRIC SUMMARY / REVENUE & SPEND KPI CARDS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-primary border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Active Suppliers</span>
                    <h4 class="mb-0 fw-bold mt-1 text-dark"><?= number_format($stats['total_sellers'] ?? 0) ?></h4>
                    <small class="text-muted"><?= number_format($stats['total_orders'] ?? 0) ?> total purchase bills</small>
                </div>
                <div class="rounded-circle bg-primary-subtle text-primary p-3">
                    <i class="bi bi-buildings fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Total Product Purchases</span>
                    <h4 class="mb-0 fw-bold mt-1 text-success">₹<?= number_format($stats['total_spend'] ?? 0, 2) ?></h4>
                    <small class="text-muted">Third-party procurement spend</small>
                </div>
                <div class="rounded-circle bg-success-subtle text-success p-3">
                    <i class="bi bi-currency-rupee fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Total Inward Stock Units</span>
                    <h4 class="mb-0 fw-bold mt-1 text-info"><?= number_format($stats['total_inward_units'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h4>
                    <small class="text-muted">Credited to warehouse available stock</small>
                </div>
                <div class="rounded-circle bg-info-subtle text-info p-3">
                    <i class="bi bi-box-seam fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Paid vs Pending Payables</span>
                    <h5 class="mb-0 fw-bold mt-1 text-dark">
                        <span class="text-success">₹<?= number_format($stats['paid_spend'] ?? 0) ?></span>
                        <small class="text-danger fs-7">/ ₹<?= number_format($stats['pending_spend'] ?? 0) ?> due</small>
                    </h5>
                    <small class="text-muted">Supplier settlement ledger</small>
                </div>
                <div class="rounded-circle bg-warning-subtle text-warning p-3">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- FILTERS & SEARCH -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4 bg-white rounded-3">
    <div class="card-body">
        <form method="GET" action="<?= url('warehouse/sellers') ?>" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Search Supplier / Company / Contact / GST</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, company, mobile, city, GSTIN..." value="<?= e($search ?? '') ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Active Only</option>
                    <option value="all" <?= ($status ?? '') === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="0" <?= ($status ?? '') === '0' ? 'selected' : '' ?>>Inactive Only</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="<?= url('warehouse/sellers') ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filters"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- SELLERS LIST TABLE -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-body p-0">
        <?php if (empty($sellers)): ?>
            <div class="text-center py-5">
                <i class="bi bi-shop-window fs-1 text-muted d-block mb-3"></i>
                <h5 class="text-muted">No Third-Party Suppliers Registered</h5>
                <p class="text-muted small">Register vendors &amp; third-party manufacturers to record product buying bills directly into warehouse stock.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="<?= url('warehouse/sellers/create') ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Register First Supplier
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">Supplier / Company</th>
                            <th style="width: 18%;">Contact &amp; Location</th>
                            <th style="width: 14%;">GSTIN / Terms</th>
                            <th class="text-center" style="width: 12%;">Purchases</th>
                            <th class="text-end" style="width: 16%;">Total Spent (₹)</th>
                            <th class="text-center" style="width: 8%;">Status</th>
                            <th class="text-end" style="width: 7%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sellers as $s): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark fs-6">
                                        <a href="<?= url('warehouse/sellers/view/' . $s['id']) ?>" class="text-decoration-none text-dark hover-primary">
                                            <?= e($s['name']) ?>
                                        </a>
                                    </div>
                                    <?php if (!empty($s['company'])): ?>
                                        <div class="small text-primary fw-semibold"><i class="bi bi-building me-1"></i><?= e($s['company']) ?></div>
                                    <?php endif; ?>
                                    <span class="badge bg-light text-secondary border font-monospace fs-8 mt-1">
                                        <?= e($s['seller_type'] === 'MATERIAL_SUPPLIER' ? 'Fabric Supplier' : 'Third-Party Brand / Vendor') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($s['phone'])): ?>
                                        <div class="small text-dark"><i class="bi bi-telephone me-1 text-muted"></i><?= e($s['phone']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($s['email'])): ?>
                                        <div class="small text-muted"><i class="bi bi-envelope me-1"></i><?= e($s['email']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($s['city']) || !empty($s['state'])): ?>
                                        <div class="small text-secondary"><i class="bi bi-geo-alt me-1"></i><?= e(implode(', ', array_filter([$s['city'] ?? '', $s['state'] ?? '']))) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($s['gst_number'])): ?>
                                        <span class="badge bg-light text-dark border font-monospace"><?= e($s['gst_number']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">URP (No GST)</span>
                                    <?php endif; ?>
                                    <?php if (!empty($s['payment_terms'])): ?>
                                        <small class="d-block text-muted mt-1"><?= e($s['payment_terms']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">
                                        <?= number_format($s['total_orders']) ?> orders
                                    </span>
                                    <small class="text-muted d-block mt-1"><?= number_format($s['total_units']) ?> pcs inwarded</small>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold text-success fs-6">₹<?= number_format($s['total_spend'], 2) ?></span>
                                    <?php if (($s['pending_dues'] ?? 0) > 0): ?>
                                        <small class="text-danger d-block">Due: ₹<?= number_format($s['pending_dues'], 2) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['is_active']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('warehouse/sellers/view/' . $s['id']) ?>" class="btn btn-outline-primary" title="View Profile &amp; Order History">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('wh_sellers', 'edit')): ?>
                                        <a href="<?= url('warehouse/sellers/create?seller_id=' . $s['id'] . '&mode=edit') ?>" class="btn btn-outline-secondary" title="Edit Supplier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('wh_sellers', 'delete')): ?>
                                        <form method="POST" action="<?= url('warehouse/sellers/delete/' . $s['id']) ?>" class="d-inline mb-0" onsubmit="return confirm('Are you sure you want to permanently delete supplier &quot;<?= e(addslashes($s['name'])) ?>&quot; from the database?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger" title="Delete Supplier">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
