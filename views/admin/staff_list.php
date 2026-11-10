<?php
/**
 * Staff Directory / Management List View
 */
$currentConcernId = (int)($filters['concern_id'] ?? 0);
?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">Staff Management</h1>
            <p class="text-muted mb-0">Manage staff, custom concern assignments, facility scopes, and module access.</p>
        </div>
        <div>
            <?php if (hasPermission('staff', 'create')): ?>
            <a href="<?= url('admin/staff/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-person-plus-fill"></i>
                <span>Add Staff Member</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('staff', 'view')): ?>
                <a href="<?= url('admin/staff/concerns') ?>" class="btn btn-outline-primary ms-2"><i class="bi bi-diagram-3 me-1"></i>Concerns &amp; Modules</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Summary Cards Container -->
    <div id="staffKpiContainer">
        <div class="row g-3 mb-4">
            <?php
            $totalStaff = count($staff);
            $activeCount = count(array_filter($staff, fn($s) => (int)$s['is_active'] === 1));
            $inactiveCount = $totalStaff - $activeCount;
            $totalRoles = count($roles);
            ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-3 bg-primary bg-opacity-10 p-3 me-3 text-primary">
                            <i class="bi bi-people-fill fs-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-medium">Total Staffs</div>
                            <div class="fs-4 fw-bold text-dark"><?= $totalStaff ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-3 bg-success bg-opacity-10 p-3 me-3 text-success">
                            <i class="bi bi-person-check-fill fs-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-medium">Active Accounts</div>
                            <div class="fs-4 fw-bold text-dark"><?= $activeCount ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-3 bg-secondary bg-opacity-10 p-3 me-3 text-secondary">
                            <i class="bi bi-person-dash-fill fs-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-medium">Deactivated</div>
                            <div class="fs-4 fw-bold text-dark"><?= $inactiveCount ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-3 bg-info bg-opacity-10 p-3 me-3 text-info">
                            <i class="bi bi-shield-check fs-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-medium">Active Roles</div>
                            <div class="fs-4 fw-bold text-dark"><?= $totalRoles ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card (Instant Live Filter) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= url('admin/staff') ?>" id="staffFilterForm" class="row g-3 align-items-center">

                <!-- Search Input -->
                <div class="col-12 col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Search by name, username, or email..." value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
                    </div>
                </div>

                <!-- Roles Dropdown -->
                <div class="col-12 col-md-2">
                    <select name="role_id" id="roleSelect" class="form-select">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" <?= (string)($filters['role_id'] ?? '') === (string)$role['id'] ? 'selected' : '' ?>>
                                <?= e($role['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <select name="concern_id" class="form-select">
                        <option value="">All Concerns</option>
                        <?php foreach ($concerns as $concern): ?>
                            <option value="<?= (int)$concern['id'] ?>" <?= $currentConcernId === (int)$concern['id'] ? 'selected' : '' ?>><?= e($concern['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <select name="status" id="statusSelect" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="1" <?= isset($filters['is_active']) && (int)$filters['is_active'] === 1 ? 'selected' : '' ?>>Active Only</option>
                        <option value="0" <?= isset($filters['is_active']) && (int)$filters['is_active'] === 0 ? 'selected' : '' ?>>Inactive Only</option>
                    </select>
                </div>

                <!-- Reset Button & Live Status Indicator -->
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Staff Table Container -->
    <div id="staffTableContainer" style="transition: opacity 0.2s ease;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-bold">
                    <?= $currentConcernId ? 'Concern Staff Directory' : 'All Staff Directory' ?>
                </h5>
                <span class="badge bg-light text-dark border"><?= count($staff) ?> Staff Members</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Staff Member</th>
                            <th>Assigned Concerns</th>
                            <th>Role &amp; Facility</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($staff)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                                    No staff members found for this criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($staff as $s): ?>
                                <?php $assignedConcernNames = array_filter(array_map('trim', explode(',', (string)($s['concern_names'] ?? '')))); ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3 bg-primary text-white fw-bold d-flex align-items-center justify-content-center rounded-circle" style="width: 42px; height: 42px; font-size: 16px;">
                                                <?= strtoupper(substr($s['full_name'] ?? $s['username'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= e($s['full_name']) ?></div>
                                                <div class="text-muted small">@<?= e($s['username']) ?> &bull; <?= e($s['email'] ?: 'No email') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($assignedConcernNames): foreach ($assignedConcernNames as $concernName): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1 mb-1"><?= e($concernName) ?></span>
                                        <?php endforeach; else: ?>
                                            <span class="text-muted">No concern assigned</span>
                                        <?php endif; ?>
                                        <small class="d-block text-muted mt-1">Data scope: <?= e($s['department'] ?? 'GENERAL') ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 me-1">
                                                <i class="bi bi-shield-check me-1"></i><?= e($s['role_name'] ?? 'Unassigned') ?>
                                            </span>
                                        </div>
                                        <div class="text-muted small mt-1">
                                            <?php if (!empty($s['production_unit_name'])): ?>
                                                <span class="me-2"><i class="bi bi-gear-wide me-1"></i><?= e($s['production_unit_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($s['warehouse_name'])): ?>
                                                <span class="me-2"><i class="bi bi-buildings me-1"></i><?= e($s['warehouse_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($s['channel_name'])): ?>
                                                <?php $chanIcon = strtoupper($s['department'] ?? '') === 'WHATSAPP' ? 'bi-whatsapp text-success' : 'bi-globe text-info'; ?>
                                                <span class="me-2"><i class="bi <?= $chanIcon ?> me-1"></i><?= e($s['channel_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($s['shop_name'])): ?>
                                                <span><i class="bi bi-shop me-1"></i><?= e($s['shop_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (empty($s['production_unit_name']) && empty($s['warehouse_name']) && empty($s['channel_name']) && empty($s['shop_name'])): ?>
                                                <span class="fst-italic text-muted">All Units / Channels</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($s['is_active']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                <i class="bi bi-circle-fill me-1 small"></i>Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill">
                                                <i class="bi bi-circle me-1 small"></i>Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="<?= url('admin/staff/view/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary" title="View Staff Details">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="<?= url('system/audit?user_id=' . $s['id']) ?>" class="btn btn-sm btn-outline-info" title="View Activity & Audit Logs for <?= e($s['full_name']) ?>">
                                                <i class="bi bi-clock-history"></i> Logs
                                            </a>
                                            <?php if (hasPermission('staff', 'edit')): ?>
                                            <a href="<?= url('admin/staff/edit/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit Staff Member">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <form method="POST" action="<?= url('admin/staff/toggle/' . $s['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to <?= $s['is_active'] ? 'deactivate' : 'activate' ?> this staff member?');">
                                                <?= CSRF::field() ?>
                                                <button type="submit" class="btn btn-sm <?= $s['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $s['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                    <i class="bi <?= $s['is_active'] ? 'bi-person-x' : 'bi-person-check' ?>"></i>
                                                    <?= $s['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <?php if (hasPermission('staff', 'delete')): ?>
                                            <form method="POST" action="<?= url('admin/staff/delete/' . $s['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete staff member \'<?= e($s['full_name']) ?>\'?');">
                                                <?= CSRF::field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Staff Member">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('staffFilterForm');
    if (!form) return;

    const searchInput = document.getElementById('searchInput');
    const selects = form.querySelectorAll('select');
    const resetBtn = document.getElementById('resetFiltersBtn');
    let debounceTimeout = null;

    function executeDynamicFilter() {
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        for (const [key, val] of formData.entries()) {
            if (val.trim() !== '') {
                params.set(key, val.trim());
            }
        }

        const baseUrl = form.getAttribute('action');
        const targetUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

        const tableContainer = document.getElementById('staffTableContainer');
        const kpiContainer = document.getElementById('staffKpiContainer');
        if (tableContainer) tableContainer.style.opacity = '0.4';

        fetch(targetUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newTable = doc.getElementById('staffTableContainer');
            const newKpi = doc.getElementById('staffKpiContainer');

            if (newTable && tableContainer) {
                tableContainer.innerHTML = newTable.innerHTML;
            }
            if (newKpi && kpiContainer) {
                kpiContainer.innerHTML = newKpi.innerHTML;
            }

            // Update browser URL silently
            window.history.replaceState({}, '', targetUrl);
        })
        .catch(err => {
            console.error('Filter request failed, reloading fallback:', err);
            window.location.href = targetUrl;
        })
        .finally(() => {
            if (tableContainer) tableContainer.style.opacity = '1';
        });
    }

    // Auto trigger on dropdown change
    selects.forEach(select => {
        select.addEventListener('change', executeDynamicFilter);
    });

    // Auto trigger on search typing (debounced)
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(executeDynamicFilter, 250);
        });
    }

    // Reset button
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            selects.forEach(sel => sel.value = '');
            executeDynamicFilter();
        });
    }

    // Prevent default form submit and use instant live filter
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        executeDynamicFilter();
    });
});
</script>
