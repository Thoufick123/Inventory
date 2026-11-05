<?php
// views/shop/return_to_warehouse/index.php — Return to Warehouse Dispatches & Dashboard
?>
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-warning-subtle text-warning-emphasis fw-semibold px-3 py-1 rounded-pill">
                    <i class="bi bi-arrow-return-left me-1"></i>Shop to Warehouse RTV
                </span>
                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-1 rounded-pill">
                    <i class="bi bi-shop me-1"></i><?= e($shop['name'] ?? 'Shop Store') ?>
                </span>
            </div>
            <h2 class="fw-bold text-dark mb-0">Return to Warehouse Consignments</h2>
            <p class="text-muted small mb-0">Manage damaged goods dispatches, excess inventory returns, seasonal stock transfers, and delivery gate passes.</p>
        </div>
        <div class="d-flex gap-2">
           
            <?php if (hasPermission('return_to_warehouse', 'create')): ?>
            <a href="<?= url('shop/return-to-warehouse/create') ?>" class="btn btn-danger rounded-pill px-4 shadow-sm fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> New Return Dispatch
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Dispatches -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Total Dispatches</span>
                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center text-primary" style="width: 44px; height: 44px;">
                            <i class="bi bi-arrow-up-right-circle-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format((int)($kpis['total_dispatches'] ?? 0)) ?> <span class="fs-6 text-muted fw-normal">consignments</span></div>
                    <div class="small text-muted mt-1"><i class="bi bi-clock-history me-1 text-primary"></i> Cumulative shop dispatches</div>
                </div>
            </div>
        </div>

        <!-- 2. Total Units Returned -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Total Units Returned</span>
                        <div class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center text-danger" style="width: 44px; height: 44px;">
                            <i class="bi bi-box-seam-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="fs-3 fw-bold text-danger"><?= number_format((int)($kpis['total_units_returned'] ?? 0)) ?> <span class="fs-6 text-muted fw-normal">pcs</span></div>
                    <div class="small text-muted mt-1"><i class="bi bi-dash-circle me-1 text-danger"></i> Deducted from shop stock</div>
                </div>
            </div>
        </div>

        <!-- 3. In-Transit / Pending Verification -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">In-Transit / Inspecting</span>
                        <div class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center text-warning-emphasis" style="width: 44px; height: 44px;">
                            <i class="bi bi-truck fs-5"></i>
                        </div>
                    </div>
                    <div class="fs-3 fw-bold text-warning-emphasis"><?= number_format((int)($kpis['in_transit_count'] ?? 0)) ?> <span class="fs-6 text-muted fw-normal">active</span></div>
                    <div class="small text-muted mt-1"><i class="bi bi-hourglass-split me-1 text-warning"></i> Awaiting warehouse QC</div>
                </div>
            </div>
        </div>

        <!-- 4. Classified / Completed -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Completed & Verified</span>
                        <div class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center text-success" style="width: 44px; height: 44px;">
                            <i class="bi bi-check2-all fs-4"></i>
                        </div>
                    </div>
                    <div class="fs-3 fw-bold text-success"><?= number_format((int)($kpis['completed_count'] ?? 0)) ?> <span class="fs-6 text-muted fw-normal">verified</span></div>
                    <div class="small text-muted mt-1"><i class="bi bi-shield-check me-1 text-success"></i> Reconciled into warehouse</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('shop/return-to-warehouse') ?>" class="row g-2 align-items-center" id="rtwFilterForm" onsubmit="return true;">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="rtwSearchInput" class="form-control bg-light border-start-0" 
                               placeholder="Search by Return #, Reason, or Destination Warehouse..." 
                               value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
                        <button type="button" id="btnClearRtwSearch" class="btn btn-outline-secondary <?= empty($filters['search']) ? 'd-none' : '' ?>" title="Clear Search" onclick="clearRtwSearch()"><i class="bi bi-x"></i></button>
                    </div>
                </div>

                <div class="col-md-4">
                    <select name="status" id="rtwStatusSelect" class="form-select bg-light">
                        <option value="ALL" <?= ($filters['status'] ?? '') === 'ALL' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="RECEIVED" <?= ($filters['status'] ?? '') === 'RECEIVED' ? 'selected' : '' ?>>RECEIVED (Dispatched / In-Transit)</option>
                        <option value="INSPECTING" <?= ($filters['status'] ?? '') === 'INSPECTING' ? 'selected' : '' ?>>INSPECTING (Under QC)</option>
                        <option value="CLASSIFIED" <?= ($filters['status'] ?? '') === 'CLASSIFIED' ? 'selected' : '' ?>>CLASSIFIED (QC Completed)</option>
                        <option value="COMPLETED" <?= ($filters['status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>COMPLETED (Restocked)</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary rounded-3 w-100" title="Apply Filter">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                    <?php if (!empty($filters['search']) || ($filters['status'] ?? 'ALL') !== 'ALL'): ?>
                        <a href="<?= url('shop/return-to-warehouse') ?>" class="btn btn-light border rounded-3" title="Clear Filters">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Dispatches Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-truck me-2 text-primary"></i>Dispatched Return Consignments</h5>
            <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill" id="rtwCountBadge">
                Showing <?= count($returns) ?> consignments
            </span>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0" id="rtwTable">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th class="ps-4" style="width: 5%;">#</th>
                        <th style="width: 18%;">Return Number</th>
                        <th style="width: 20%;">Destination Warehouse</th>
                        <th style="width: 18%;">Reason / Classification</th>
                        <th class="text-center" style="width: 12%;">Volume</th>
                        <th class="text-center" style="width: 12%;">Status</th>
                        <th style="width: 15%;">Dispatched Date</th>
                        <th class="text-end pe-4" style="width: 12%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="rtwTableBody">
                    <?php if (empty($returns)): ?>
                        <tr id="emptyRtwRow">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="bi bi-box-arrow-up fs-1 text-secondary d-block mb-2"></i>
                                    <h6>No Return-to-Warehouse dispatches found.</h6>
                                    <p class="small text-muted mb-3">Initiate a new return dispatch to send damaged or excess stock back to central storage.</p>
                                    <?php if (hasPermission('return_to_warehouse', 'create')): ?>
                                    <a href="<?= url('shop/return-to-warehouse/create') ?>" class="btn btn-sm btn-primary rounded-pill px-4">
                                        <i class="bi bi-plus-circle me-1"></i> Create First Return Dispatch
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($returns as $idx => $r): 
                            $searchHaystack = strtolower(e($r['return_number'] . ' ' . $r['warehouse_name'] . ' ' . $r['warehouse_code'] . ' ' . ($r['reason'] ?? '') . ' ' . $r['status']));
                        ?>
                            <tr class="rtw-row" data-search="<?= $searchHaystack ?>" data-status="<?= e($r['status']) ?>">
                                <td class="ps-4 text-muted small fw-bold rtw-idx"><?= $idx + 1 ?></td>
                                <td>
                                    <a href="<?= url('shop/return-to-warehouse/view/' . $r['id']) ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                        <?= e($r['return_number']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small"><?= e($r['warehouse_name']) ?></div>
                                    <small class="text-muted font-monospace"><i class="bi bi-building me-1"></i><?= e($r['warehouse_code']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-semibold">
                                        <?= e($r['reason'] ?? 'Stock Return') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-dark-subtle text-dark border px-3 py-1 rounded-pill fw-semibold">
                                        <?= $r['item_count'] ?> Item(s) • <strong><?= $r['total_returned_qty'] ?> Pcs</strong>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php
                                        $statusClass = match($r['status']) {
                                            'RECEIVED'   => 'bg-warning-subtle text-warning-emphasis',
                                            'INSPECTING' => 'bg-info-subtle text-info-emphasis',
                                            'CLASSIFIED' => 'bg-primary-subtle text-primary',
                                            'COMPLETED'  => 'bg-success-subtle text-success',
                                            default      => 'bg-secondary-subtle text-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $statusClass ?> px-3 py-1 rounded-pill fw-semibold">
                                        <?= e($r['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small text-dark"><?= formatDateTime($r['created_at']) ?></div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="<?= url('shop/return-to-warehouse/view/' . $r['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="View Manifest Details">
                                            <i class="bi bi-eye-fill me-1"></i> View
                                        </a>
                                        <?php if (hasPermission('return_to_warehouse', 'print')): ?>
                                        <a href="<?= url('shop/return-to-warehouse/label/' . $r['id']) ?>" target="_blank" class="btn btn-sm btn-light border rounded-circle" title="Print Carton QR Label">
                                            <i class="bi bi-qr-code"></i>
                                        </a>
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

<script>
window.clearRtwSearch = function() {
    const sInput = document.getElementById('rtwSearchInput');
    const btnClear = document.getElementById('btnClearRtwSearch');
    if (sInput) {
        sInput.value = '';
        if (btnClear) btnClear.classList.add('d-none');
        if (window.applyRtwFilters) window.applyRtwFilters();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('rtwSearchInput');
    const statusSelect = document.getElementById('rtwStatusSelect');
    const tableBody = document.getElementById('rtwTableBody');
    const countBadge = document.getElementById('rtwCountBadge');
    const btnClear = document.getElementById('btnClearRtwSearch');
    const rows = Array.from(document.querySelectorAll('.rtw-row'));

    window.applyRtwFilters = function() {
        if (!rows.length) return;

        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const statusVal = (statusSelect ? statusSelect.value : 'ALL').toUpperCase();

        if (btnClear) {
            if (query) btnClear.classList.remove('d-none');
            else btnClear.classList.add('d-none');
        }

        let visibleCount = 0;

        rows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            const rowStatus = (row.getAttribute('data-status') || '').toUpperCase();

            const matchQuery = !query || searchData.includes(query);
            const matchStatus = (statusVal === 'ALL') || (rowStatus === statusVal);

            if (matchQuery && matchStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (countBadge) {
            countBadge.textContent = `Showing ${visibleCount} consignments`;
        }

        let emptyRow = document.getElementById('noRtwMatchRow');
        if (visibleCount === 0) {
            if (!emptyRow && tableBody) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'noRtwMatchRow';
                emptyRow.innerHTML = `
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <h6 class="fw-bold text-secondary">No Matching Consignments Found</h6>
                        <p class="small text-muted mb-0">No return consignments match your live search criteria.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 mt-2" onclick="clearRtwSearch()">
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

    if (searchInput) {
        searchInput.addEventListener('input', applyRtwFilters);
        searchInput.addEventListener('keyup', applyRtwFilters);
    }
    if (statusSelect) {
        statusSelect.addEventListener('change', applyRtwFilters);
    }
});
</script>
