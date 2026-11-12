

<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-speedometer2 me-2 text-primary"></i>Admin Dashboard</h2>
        <span class="text-secondary"><?= date('l, d F Y') ?></span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-plus-circle me-1"></i> Quick Add
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="<?= url('production/programs/create') ?>"><i class="bi bi-calendar2-plus me-2 text-secondary"></i>New Production Program</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Secondary Operational Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
            <div class="text-muted small">Total Catalog SKUs</div>
            <div class="fs-4 fw-700 text-dark"><?= number_format($totalProducts ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
            <div class="text-muted small">Total Stock Valuation</div>
            <div class="fs-4 fw-700 text-success"><?= formatCurrency($totalStockValue ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
            <div class="text-muted small">Today's Invoices Count</div>
            <div class="fs-4 fw-700 text-primary"><?= number_format($todaySales ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
            <div class="text-muted small">Low Stock Warnings</div>
            <div class="fs-4 fw-700 <?= ($lowStockCount ?? 0) > 0 ? 'text-danger' : 'text-secondary' ?>"><?= number_format($lowStockCount ?? 0) ?></div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- RECENT SALES & ALERTS -->
<!-- ========================================================================= -->
<div class="row g-3">
    <!-- Recent Sales Table -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-700 text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Production &amp; Concern Bills</h6>
                <div class="d-flex gap-2">
                    <?php if (hasPermission('production_billing', 'view')): ?>
                        <a href="<?= url('production/billing') ?>" class="btn btn-sm btn-outline-secondary">All Bills</a>
                    <?php endif; ?>
                    <a href="<?= url('reports/sales') ?>" class="btn btn-sm btn-outline-primary">Sales Reports</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase text-muted">
                        <tr>
                            <th class="ps-3">Bill #</th>
                            <th>Mode</th>
                            <th>Buyer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="pe-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentSales)): ?>
                            <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark font-monospace">
                                    <?php if (!empty($sale['id']) && hasPermission('production_billing', 'view')): ?>
                                        <a href="<?= url('production/billing/view/' . (int)$sale['id']) ?>" class="text-decoration-none"><?= e($sale['invoice_number']) ?></a>
                                    <?php else: ?>
                                        <?= e($sale['invoice_number']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e($sale['sale_type']) ?></span></td>
                                <td><?= e($sale['customer_name'] ?? 'Walk-in Customer') ?></td>
                                <td class="fw-bold text-success"><?= formatCurrency($sale['total_amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= strtolower($sale['status']) === 'paid' ? 'success' : 'secondary' ?>-subtle text-<?= strtolower($sale['status']) === 'paid' ? 'success' : 'secondary' ?>">
                                        <?= e($sale['status']) ?>
                                    </span>
                                </td>
                                <td class="pe-3 text-muted small"><?= formatDate($sale['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No recent sales records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="col-lg-4">
        <!-- Low Stock Alerts -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-700 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Warnings</h6>
                <span class="badge bg-danger"><?= count($lowStockItems ?? []) ?></span>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($lowStockItems)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($lowStockItems, 0, 5) as $item): ?>
                        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center border-bottom">
                            <div>
                                <div class="fw-bold text-dark small"><?= e($item['product_name']) ?></div>
                                <span class="font-monospace text-muted small"><?= e($item['sku']) ?></span>
                            </div>
                            <span class="badge bg-danger-subtle text-danger px-2 py-1 font-monospace fw-bold">
                                <?= number_format($item['quantity']) ?> left
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-check-circle text-success fs-3 d-block mb-1"></i>
                        <small>All inventory items are currently above threshold.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3.5. LIVE STAFF ACTIVITY & ENTERPRISE AUDIT TRAIL -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mt-4 mb-4">
    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="mb-0 fw-700 text-dark">
                <i class="bi bi-shield-check text-primary me-2"></i>Live Staff Activity &amp; System Change Logs
            </h6>
            <small class="text-muted">Real-time audit records of staff actions, status toggles, unit reassignments, and operational updates.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('system/audit?module=staff') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <i class="bi bi-people me-1"></i> Staff Changes Only
            </a>
            <a href="<?= url('system/audit') ?>" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-journal-text me-1"></i> View Full Audit Trail (<?= number_format($auditStats['total'] ?? 0) ?>)
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase text-muted">
                <tr>
                    <th class="ps-3">Timestamp</th>
                    <th>Staff / User</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Activity Detail</th>
                    <th class="text-end pe-3">Audit Diff</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentAuditLogs)): ?>
                    <?php foreach ($recentAuditLogs as $log): ?>
                        <?php
                        $actionUpper = strtoupper($log['action']);
                        $badgeClass = 'bg-secondary-subtle text-secondary';
                        if (in_array($actionUpper, ['CREATE', 'STORE', 'ADD'])) $badgeClass = 'bg-success-subtle text-success';
                        elseif (in_array($actionUpper, ['UPDATE', 'EDIT', 'MODIFY', 'STATUS_CHANGE', 'TOGGLE'])) $badgeClass = 'bg-warning-subtle text-warning-emphasis';
                        elseif (in_array($actionUpper, ['DELETE', 'REMOVE', 'PURGE'])) $badgeClass = 'bg-danger-subtle text-danger';
                        elseif (in_array($actionUpper, ['LOGIN', 'RESTORE', 'IMPORT'])) $badgeClass = 'bg-info-subtle text-info-emphasis';
                        
                        $staffName = e($log['user_name'] ?? 'System');
                        $moduleTitle = ucfirst(e($log['module']));
                        ?>
                        <tr>
                            <td class="ps-3 text-nowrap">
                                <div class="fw-semibold text-dark small"><?= formatDateTime($log['created_at']) ?></div>
                                <small class="text-muted"><?= date('h:i A', strtotime($log['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm rounded-circle bg-primary-subtle text-primary fw-bold me-2 d-flex align-items-center justify-content-center" style="width:30px; height:30px; font-size:11px; flex-shrink:0;">
                                        <?= strtoupper(substr($staffName, 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small"><?= $staffName ?></div>
                                        <div class="text-muted" style="font-size: 11px;">@<?= e($log['username'] ?? 'system') ?> &bull; <?= e($log['role_name'] ?? 'Staff') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border rounded-pill px-2 py-0.5 text-uppercase fw-semibold" style="font-size: 0.7rem;"><?= $moduleTitle ?></span></td>
                            <td><span class="badge <?= $badgeClass ?> rounded-pill px-2 py-0.5 text-uppercase fw-bold" style="font-size: 0.68rem;"><?= e($log['action']) ?></span></td>
                            <td>
                                <span class="small text-dark"><?= $staffName ?> performed <?= strtolower($log['action']) ?> in <?= $moduleTitle ?></span>
                                <?php if ($log['reference_type']): ?>
                                    <small class="text-muted d-block font-monospace">(<?= e($log['reference_type']) ?> #<?= e($log['reference_id']) ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <?php if ($log['previous_value'] || $log['new_value']): ?>
                                    <a href="<?= url('system/audit?search=' . urlencode($log['user_name'] ?? $log['module'])) ?>" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 small">
                                        <i class="bi bi-eye text-primary me-1"></i> View Diff
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-shield-check fs-2 d-block mb-1 text-secondary opacity-50"></i>
                            No activity logs recorded yet. All changes performed by staff will appear here.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================================================= -->
