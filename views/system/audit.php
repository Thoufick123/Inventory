<?php
// views/system/audit.php — Clean Profile-Wise Audit Logs & Staff Activity Directory
$selectedUserId = !empty($filters['user_id']) ? (int)$filters['user_id'] : null;
$viewMode = $_GET['view'] ?? ($selectedUserId ? 'detail' : 'profiles');
?>
<style>
.profile-row-card {
    transition: all 0.2s ease-in-out;
}
.profile-row-card:hover {
    background-color: #f8fafc;
}
.staff-badge-pill {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 20px;
}
.nav-pills-custom .nav-link {
    border-radius: 30px;
    padding: 6px 18px;
    font-weight: 600;
    font-size: 13px;
    color: #64748b;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    transition: all 0.2s;
}
.nav-pills-custom .nav-link.active {
    background: #0f172a !important;
    color: #ffffff !important;
    border-color: #0f172a !important;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
}
</style>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="bi bi-shield-check text-primary me-2"></i>System Activity & Staff Audit Logs
            </h3>
            <p class="text-muted small mb-0">
                <?= $selectedStaff 
                    ? 'Inspecting change log history for <strong>' . e($selectedStaff['full_name']) . '</strong> (@' . e($selectedStaff['username']) . ')'
                    : 'Profile-wise directory of all staff members and their system changes.' ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if ($selectedStaff): ?>
                <a href="<?= url('system/audit') ?>" class="btn btn-sm btn-light border text-dark rounded-pill px-3 shadow-xs">
                    <i class="bi bi-arrow-left me-1"></i> Back to Staff Profiles
                </a>
            <?php endif; ?>
            
            <a href="<?= url('system/audit/export?' . http_build_query($filters)) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">
                <i class="bi bi-download me-1"></i> <?= $selectedStaff ? 'Export ' . e($selectedStaff['full_name']) . '\'s Logs' : 'Export Full Audit Report' ?>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- CASE 1: SPECIFIC PROFILE SELECTED (e.g. Super Admin clicked on "tester") -->
    <!-- ========================================================================= -->
    <?php if ($selectedStaff): ?>
        <!-- Staff Profile Showcase Banner -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-4 border-primary">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 56px; height: 56px; font-size: 22px; flex-shrink:0;">
                            <?= strtoupper(substr($selectedStaff['full_name'] ?: $selectedStaff['username'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h4 class="fw-bold text-dark mb-0"><?= e($selectedStaff['full_name']) ?></h4>
                                <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 text-uppercase" style="font-size: 0.72rem;">
                                    <i class="bi bi-shield-lock me-1"></i><?= e($selectedStaff['role_name'] ?? 'Staff') ?>
                                </span>
                                <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                                    <i class="bi bi-building me-1"></i><?= e(strtoupper($selectedStaff['department'] ?? 'GENERAL')) ?>
                                </span>
                                <?php if ($selectedStaff['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-0.5" style="font-size: 0.7rem;">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-0.5" style="font-size: 0.7rem;">Inactive</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small mt-1 d-flex flex-wrap align-items-center gap-3">
                                <span><i class="bi bi-person me-1"></i>Username: <strong>@<?= e($selectedStaff['username']) ?></strong></span>
                                <?php if (!empty($selectedStaff['email'])): ?>
                                    <span><i class="bi bi-envelope me-1"></i><?= e($selectedStaff['email']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($selectedStaff['phone'])): ?>
                                    <span><i class="bi bi-telephone me-1"></i><?= e($selectedStaff['phone']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($selectedStaff['production_unit_name'])): ?>
                                    <span><i class="bi bi-gear-wide me-1"></i>Unit: <strong><?= e($selectedStaff['production_unit_name']) ?></strong></span>
                                <?php elseif (!empty($selectedStaff['warehouse_name'])): ?>
                                    <span><i class="bi bi-buildings me-1"></i>Warehouse: <strong><?= e($selectedStaff['warehouse_name']) ?></strong></span>
                                <?php elseif (!empty($selectedStaff['shop_name'])): ?>
                                    <span><i class="bi bi-shop me-1"></i>Shop: <strong><?= e($selectedStaff['shop_name']) ?></strong></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2.5 flex-wrap">
                        <div class="bg-light p-2.5 px-3 rounded-4 border text-center">
                            <div class="text-muted small fw-semibold" style="font-size: 11px;">TOTAL ACTIONS</div>
                            <div class="fs-5 fw-bold text-dark"><?= number_format($selectedStaff['log_count'] ?? 0) ?></div>
                        </div>
                        <div class="bg-success-subtle p-2.5 px-3 rounded-4 border border-success-subtle text-center text-success">
                            <div class="text-muted small fw-semibold" style="font-size: 11px;">CREATIONS</div>
                            <div class="fs-5 fw-bold text-success"><?= number_format($selectedStaff['create_count'] ?? 0) ?></div>
                        </div>
                        <div class="bg-warning-subtle p-2.5 px-3 rounded-4 border border-warning-subtle text-center text-warning-emphasis">
                            <div class="text-muted small fw-semibold" style="font-size: 11px;">UPDATES</div>
                            <div class="fs-5 fw-bold text-warning-emphasis"><?= number_format($selectedStaff['update_count'] ?? 0) ?></div>
                        </div>
                        <div class="bg-danger-subtle p-2.5 px-3 rounded-4 border border-danger-subtle text-center text-danger">
                            <div class="text-muted small fw-semibold" style="font-size: 11px;">DELETIONS</div>
                            <div class="fs-5 fw-bold text-danger"><?= number_format($selectedStaff['delete_count'] ?? 0) ?></div>
                        </div>
                        <a href="<?= url('system/audit') ?>" class="btn btn-sm btn-light border text-danger rounded-pill px-3" title="Back to All Profiles">
                            <i class="bi bi-x-circle me-1"></i> View All Staff
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar for Selected Profile -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="<?= url('system/audit') ?>" id="auditFilterForm" class="row g-2 align-items-center">
                    <input type="hidden" name="user_id" value="<?= e($selectedStaff['id']) ?>">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="auditSearchInput" name="search" class="form-control border-start-0 ps-0" placeholder="Search <?= e($selectedStaff['full_name']) ?>'s actions..." value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
                            <?php if (!empty($filters['search'])): ?>
                                <button type="button" class="btn btn-sm btn-link text-muted pe-2 text-decoration-none" onclick="document.getElementById('auditSearchInput').value=''; document.getElementById('auditFilterForm').submit();"><i class="bi bi-x-circle-fill"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="module" id="auditModuleSelect" class="form-select form-select-sm" onchange="document.getElementById('auditFilterForm').submit()">
                            <option value="">All Modules</option>
                            <?php foreach ($stats['modules'] as $mod): ?>
                                <option value="<?= e($mod) ?>" <?= strtolower($filters['module'] ?? '') === strtolower($mod) ? 'selected' : '' ?>><?= e(ucfirst($mod)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-center gap-1.5">
                        <input type="date" name="date_from" id="auditDateInput" class="form-control form-control-sm" value="<?= e($filters['date_from'] ?? '') ?>" onchange="document.getElementById('auditFilterForm').submit()" title="Filter by date">
                        <?php if (!empty($filters['search']) || !empty($filters['module']) || !empty($filters['date_from'])): ?>
                            <a href="<?= url('system/audit?user_id=' . $selectedStaff['id']) ?>" class="btn btn-sm btn-light border text-danger rounded-circle flex-shrink-0" style="width:31px; height:31px; display:inline-flex; align-items:center; justify-content:center;" title="Reset filters">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Activity Timeline Table for this specific staff -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="fw-bold text-dark small text-uppercase">
                    <i class="bi bi-clock-history text-primary me-1.5"></i>Activity History for <?= e($selectedStaff['full_name']) ?>
                </div>
                <div class="text-muted small">Total: <strong><?= count($logs) ?></strong> entries</div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Date & Time</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Activity Summary</th>
                            <th class="text-end pe-4">Details & Diff</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    No activity records found for <?= e($selectedStaff['full_name']) ?> matching your filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <?php
                                $actionUpper = strtoupper($log['action']);
                                $badgeClass = 'bg-secondary-subtle text-secondary';
                                if (in_array($actionUpper, ['CREATE', 'STORE', 'ADD'])) $badgeClass = 'bg-success-subtle text-success';
                                elseif (in_array($actionUpper, ['UPDATE', 'EDIT', 'MODIFY', 'STATUS_CHANGE', 'TOGGLE'])) $badgeClass = 'bg-warning-subtle text-warning-emphasis';
                                elseif (in_array($actionUpper, ['DELETE', 'REMOVE', 'PURGE'])) $badgeClass = 'bg-danger-subtle text-danger';
                                elseif (in_array($actionUpper, ['LOGIN', 'RESTORE', 'IMPORT'])) $badgeClass = 'bg-info-subtle text-info-emphasis';

                                $userName = e($selectedStaff['full_name']);
                                $moduleName = ucfirst(e($log['module']));
                                $summaryText = "$userName performed " . strtolower($log['action']) . " in $moduleName";
                                if ($log['reference_type']) {
                                    $summaryText .= " (" . e($log['reference_type']) . " #" . e($log['reference_id'] ?? '-') . ")";
                                }
                                ?>
                                <tr>
                                    <td class="ps-4 text-nowrap">
                                        <div class="fw-semibold text-dark"><?= formatDateTime($log['created_at']) ?></div>
                                        <small class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])) ?> (IP: <?= e($log['ip_address'] ?? '127.0.0.1') ?>)</small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 text-uppercase fw-semibold" style="font-size: 0.72rem;"><?= e($moduleName) ?></span></td>
                                    <td><span class="badge <?= $badgeClass ?> rounded-pill px-2.5 py-1 text-uppercase fw-bold" style="font-size: 0.7rem;"><?= e($log['action']) ?></span></td>
                                    <td><span class="small text-dark fw-medium"><?= $summaryText ?></span></td>
                                    <td class="text-end pe-4">
                                        <?php if ($log['previous_value'] || $log['new_value']): ?>
                                            <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 btn-diff-view shadow-xs" 
                                                    data-id="<?= $log['id'] ?>"
                                                    data-user="<?= e($selectedStaff['full_name']) ?>"
                                                    data-action="<?= e($log['action']) ?>"
                                                    data-module="<?= e($moduleName) ?>"
                                                    data-time="<?= formatDateTime($log['created_at']) ?>"
                                                    data-prev="<?= htmlspecialchars($log['previous_value'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-new="<?= htmlspecialchars($log['new_value'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-sliders me-1 text-primary"></i> View Changes
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ========================================================================= -->
    <!-- CASE 2: DEFAULT VIEW — PROFILE-WISE SUMMARY (ONE PROFILE ONE TIME ONLY)  -->
    <!-- ========================================================================= -->
    <?php else: ?>

        <!-- Summary Metrics Bar -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-primary">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary-subtle p-3 text-primary me-3">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total Profiles</div>
                            <div class="fs-4 fw-bold text-dark"><?= count($staffProfiles) ?> Staff</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-success">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success-subtle p-3 text-success me-3">
                            <i class="bi bi-journal-check fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total Logged Changes</div>
                            <div class="fs-4 fw-bold text-dark"><?= number_format($stats['total'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-warning">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning-subtle p-3 text-warning me-3">
                            <i class="bi bi-pencil-square fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Modifications</div>
                            <div class="fs-4 fw-bold text-dark"><?= number_format($stats['updates'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-danger">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-danger-subtle p-3 text-danger me-3">
                            <i class="bi bi-trash3 fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Deletions</div>
                            <div class="fs-4 fw-bold text-dark"><?= number_format($stats['deletions'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Mode Navigation / Switcher -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <ul class="nav nav-pills nav-pills-custom gap-2">
                <li class="nav-item">
                    <a class="nav-link <?= $viewMode === 'profiles' ? 'active' : '' ?>" href="<?= url('system/audit?view=profiles') ?>">
                        <i class="bi bi-person-lines-fill me-1.5"></i>Staff Profiles Directory (One Profile per row)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $viewMode === 'stream' ? 'active' : '' ?>" href="<?= url('system/audit?view=stream') ?>">
                        <i class="bi bi-list-ul me-1.5"></i>Complete Activity Stream
                    </a>
                </li>
            </ul>

            <div class="text-muted small">
                <i class="bi bi-info-circle text-primary me-1"></i>Click <strong>View Changes</strong> on any staff member to inspect their complete audit logs.
            </div>
        </div>

        <!-- SUB-VIEW A: PROFILE-WISE DIRECTORY (ONE PROFILE SHOWN ONCE ONLY) -->
        <?php if ($viewMode === 'profiles'): ?>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <div class="fw-bold text-dark small text-uppercase">
                        <i class="bi bi-people text-primary me-1.5"></i>Staff Profile Change Summary Directory
                    </div>
                    <div class="text-muted small">
                        Showing <strong><?= count($staffProfiles) ?></strong> Unique Staff Profiles
                    </div>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 28%;">Staff Profile & Role</th>
                                <th style="width: 22%;">Department & Facility</th>
                                <th style="width: 20%;">Activity Breakdown</th>
                                <th style="width: 18%;">Latest Activity</th>
                                <th class="text-end pe-4" style="width: 12%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($staffProfiles)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        No staff profiles found in system.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($staffProfiles as $sp): ?>
                                    <?php
                                    $initial = strtoupper(substr($sp['full_name'] ?: $sp['username'], 0, 1));
                                    $hasLogs = ($sp['log_count'] > 0);
                                    ?>
                                    <tr class="profile-row-card">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle <?= $hasLogs ? 'bg-primary-subtle text-primary' : 'bg-light text-secondary border' ?> fw-bold me-3 d-flex align-items-center justify-content-center" style="width:42px; height:42px; font-size:15px; flex-shrink:0;">
                                                    <?= $initial ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark d-flex align-items-center gap-1.5">
                                                        <span><?= e($sp['full_name']) ?></span>
                                                        <?php if ($sp['is_active']): ?>
                                                            <span class="badge bg-success-subtle text-success rounded-pill" style="font-size: 9px;">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger-subtle text-danger rounded-pill" style="font-size: 9px;">Inactive</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-muted small" style="font-size: 11px;">
                                                        @<?= e($sp['username']) ?> &bull; <span class="badge bg-light text-primary border rounded-pill px-2 py-0.5"><?= e($sp['role_name'] ?? 'Staff') ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark small">
                                                <i class="bi bi-building text-secondary me-1"></i><?= e(strtoupper($sp['department'] ?? 'GENERAL')) ?>
                                            </div>
                                            <div class="text-muted small mt-0.5" style="font-size: 11px;">
                                                <?php if (!empty($sp['production_unit_name'])): ?>
                                                    <i class="bi bi-gear-wide me-1"></i><?= e($sp['production_unit_name']) ?>
                                                <?php elseif (!empty($sp['warehouse_name'])): ?>
                                                    <i class="bi bi-buildings me-1"></i><?= e($sp['warehouse_name']) ?>
                                                <?php elseif (!empty($sp['shop_name'])): ?>
                                                    <i class="bi bi-shop me-1"></i><?= e($sp['shop_name']) ?>
                                                <?php else: ?>
                                                    <span class="fst-italic text-muted">All Units / Central</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 fw-bold" style="font-size: 11px;">
                                                    <?= number_format($sp['log_count']) ?> Total Changes
                                                </span>
                                                <?php if ($sp['create_count'] > 0): ?>
                                                    <span class="badge bg-success-subtle text-success staff-badge-pill">
                                                        +<?= $sp['create_count'] ?> Create
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($sp['update_count'] > 0): ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis staff-badge-pill">
                                                        ~<?= $sp['update_count'] ?> Edit
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($sp['delete_count'] > 0): ?>
                                                    <span class="badge bg-danger-subtle text-danger staff-badge-pill">
                                                        -<?= $sp['delete_count'] ?> Del
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($sp['last_activity_at']): ?>
                                                <div class="fw-semibold text-dark small"><?= formatDateTime($sp['last_activity_at']) ?></div>
                                                <div class="text-muted small mt-0.5" style="font-size: 11px;">
                                                    <span class="badge bg-light text-secondary border rounded-pill px-2 py-0.5" style="font-size: 10px;"><?= e(strtoupper($sp['last_action'] ?? 'ACTION')) ?></span> in <?= e(ucfirst($sp['last_module'] ?? 'System')) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small fst-italic">No recorded activity yet</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="<?= url('system/audit?user_id=' . $sp['id']) ?>" class="btn btn-sm <?= $hasLogs ? 'btn-primary shadow-sm' : 'btn-light border text-muted' ?> rounded-pill px-3 text-nowrap">
                                                <i class="bi bi-list-check me-1"></i> View Changes (<?= $sp['log_count'] ?>)
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- SUB-VIEW B: RAW ACTIVITY STREAM (IF SUPER ADMIN TOGGLES TO COMPLETE STREAM) -->
        <?php else: ?>
            <!-- Quick Search & Dynamic Auto-Filter Bar -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="<?= url('system/audit') ?>" id="auditFilterForm" class="row g-2 align-items-center">
                        <input type="hidden" name="view" value="stream">
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="auditSearchInput" name="search" class="form-control border-start-0 ps-0" placeholder="Type to search activity, staff, or module..." value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
                                <?php if (!empty($filters['search'])): ?>
                                    <button type="button" class="btn btn-sm btn-link text-muted pe-2 text-decoration-none" onclick="document.getElementById('auditSearchInput').value=''; document.getElementById('auditFilterForm').submit();"><i class="bi bi-x-circle-fill"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="module" id="auditModuleSelect" class="form-select form-select-sm" onchange="document.getElementById('auditFilterForm').submit()">
                                <option value="">All Modules</option>
                                <?php foreach ($stats['modules'] as $mod): ?>
                                    <option value="<?= e($mod) ?>" <?= strtolower($filters['module'] ?? '') === strtolower($mod) ? 'selected' : '' ?>><?= e(ucfirst($mod)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="user_id" id="auditUserSelect" class="form-select form-select-sm" onchange="document.getElementById('auditFilterForm').submit()">
                                <option value="">All Staff Profiles</option>
                                <?php foreach ($staffProfiles as $usr): ?>
                                    <option value="<?= $usr['id'] ?>" <?= ($filters['user_id'] ?? '') == $usr['id'] ? 'selected' : '' ?>>
                                        <?= e($usr['full_name']) ?> (@<?= e($usr['username']) ?>) — <?= $usr['log_count'] ?> changes
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-center gap-1.5">
                            <input type="date" name="date_from" id="auditDateInput" class="form-control form-control-sm" value="<?= e($filters['date_from'] ?? '') ?>" onchange="document.getElementById('auditFilterForm').submit()">
                        </div>
                    </form>
                </div>
            </div>

            <!-- Complete Log Activity List -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date & Time</th>
                                <th>User / Staff</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Activity Summary</th>
                                <th class="text-end pe-4">Details & Diff</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        No activity log entries found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php
                                    $actionUpper = strtoupper($log['action']);
                                    $badgeClass = 'bg-secondary-subtle text-secondary';
                                    if (in_array($actionUpper, ['CREATE', 'STORE', 'ADD'])) $badgeClass = 'bg-success-subtle text-success';
                                    elseif (in_array($actionUpper, ['UPDATE', 'EDIT', 'MODIFY', 'STATUS_CHANGE', 'TOGGLE'])) $badgeClass = 'bg-warning-subtle text-warning-emphasis';
                                    elseif (in_array($actionUpper, ['DELETE', 'REMOVE', 'PURGE'])) $badgeClass = 'bg-danger-subtle text-danger';
                                    elseif (in_array($actionUpper, ['LOGIN', 'RESTORE', 'IMPORT'])) $badgeClass = 'bg-info-subtle text-info-emphasis';

                                    $userName = e($log['user_name'] ?? 'System / Automated');
                                    $moduleName = ucfirst(e($log['module']));
                                    $summaryText = "$userName performed " . strtolower($log['action']) . " in $moduleName";
                                    if ($log['reference_type']) {
                                        $summaryText .= " (" . e($log['reference_type']) . " #" . e($log['reference_id'] ?? '-') . ")";
                                    }
                                    ?>
                                    <tr>
                                        <td class="ps-4 text-nowrap">
                                            <div class="fw-semibold text-dark"><?= formatDateTime($log['created_at']) ?></div>
                                            <small class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['user_id'])): ?>
                                                <a href="<?= url('system/audit?user_id=' . $log['user_id']) ?>" class="text-decoration-none fw-bold text-primary small d-flex align-items-center gap-1.5" title="Inspect only this staff">
                                                    <span><?= $userName ?></span>
                                                    <i class="bi bi-arrow-right-short opacity-50"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="fw-bold text-dark small"><?= $userName ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 text-uppercase fw-semibold" style="font-size: 0.72rem;"><?= e($moduleName) ?></span></td>
                                        <td><span class="badge <?= $badgeClass ?> rounded-pill px-2.5 py-1 text-uppercase fw-bold" style="font-size: 0.7rem;"><?= e($log['action']) ?></span></td>
                                        <td><span class="small text-dark fw-medium"><?= $summaryText ?></span></td>
                                        <td class="text-end pe-4">
                                            <?php if ($log['previous_value'] || $log['new_value']): ?>
                                                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 btn-diff-view shadow-xs" 
                                                        data-id="<?= $log['id'] ?>"
                                                        data-user="<?= e($log['user_name'] ?? 'System') ?>"
                                                        data-action="<?= e($log['action']) ?>"
                                                        data-module="<?= e($moduleName) ?>"
                                                        data-time="<?= formatDateTime($log['created_at']) ?>"
                                                        data-prev="<?= htmlspecialchars($log['previous_value'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                        data-new="<?= htmlspecialchars($log['new_value'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="bi bi-sliders me-1 text-primary"></i> View Changes
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- Key-Value Diff Modal -->
<div class="modal fade" id="diffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom py-3 bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0"><i class="bi bi-shield-check text-primary me-2"></i>Activity Change Log Details</h5>
                    <div class="text-muted small mt-1" id="diffHeaderMeta"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light text-uppercase small">
                            <tr>
                                <th style="width: 30%;">Field / Property</th>
                                <th style="width: 35%;" class="text-danger"><i class="bi bi-arrow-left-circle me-1"></i>Previous Value</th>
                                <th style="width: 35%;" class="text-success"><i class="bi bi-arrow-right-circle me-1"></i>Updated Value</th>
                            </tr>
                        </thead>
                        <tbody id="diffTableBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const diffModalEl = document.getElementById('diffModal');
    if (!diffModalEl) return;
    const diffModal = new bootstrap.Modal(diffModalEl);
    
    function formatValue(val) {
        if (val === undefined || val === null || val === '') return '<span class="text-muted fst-italic">None</span>';
        if (typeof val === 'boolean') {
            return val ? '<span class="badge bg-success">Yes / Active</span>' : '<span class="badge bg-danger">No / Inactive</span>';
        }
        if (typeof val === 'object') {
            if (Array.isArray(val)) {
                return `<div class="d-flex flex-wrap gap-1">${val.map(item => `<span class="badge bg-light text-dark border">${typeof item === 'object' ? JSON.stringify(item) : item}</span>`).join('')}</div>`;
            }
            return `<pre class="mb-0 bg-light p-2 rounded small">${JSON.stringify(val, null, 2)}</pre>`;
        }
        return String(val);
    }

    document.querySelectorAll('.btn-diff-view').forEach(btn => {
        btn.addEventListener('click', function() {
            const user = this.getAttribute('data-user');
            const action = this.getAttribute('data-action');
            const module = this.getAttribute('data-module');
            const time = this.getAttribute('data-time');
            
            document.getElementById('diffHeaderMeta').innerHTML = `Staff Profile: <strong>${user}</strong> &bull; Action: <span class="badge bg-primary text-uppercase">${action}</span> in <strong>${module}</strong> on ${time}`;
            
            let prevRaw = this.getAttribute('data-prev');
            let newRaw = this.getAttribute('data-new');
            
            let prevObj = {}, newObj = {};
            try { if (prevRaw) prevObj = typeof prevRaw === 'object' ? prevRaw : JSON.parse(prevRaw); } catch(e){}
            try { if (newRaw) newObj = typeof newRaw === 'object' ? newRaw : JSON.parse(newRaw); } catch(e){}

            const ignoredKeys = ['csrf_token', '_token', 'confirm_password'];
            let allKeys = Array.from(new Set([...Object.keys(prevObj), ...Object.keys(newObj)]))
                .filter(k => !ignoredKeys.includes(k));
            
            let tbody = document.getElementById('diffTableBody');
            tbody.innerHTML = '';

            if (allKeys.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-3">No specific field property diff recorded.</td></tr>`;
            } else {
                allKeys.forEach(key => {
                    let oldVal = prevObj[key];
                    let newVal = newObj[key];
                    
                    let isChanged = JSON.stringify(oldVal) !== JSON.stringify(newVal);
                    let rowClass = isChanged ? 'table-warning bg-warning bg-opacity-10' : '';
                    
                    let tr = document.createElement('tr');
                    if (rowClass) tr.className = rowClass;
                    
                    tr.innerHTML = `
                        <td class="fw-bold text-dark">
                            ${key}
                            ${isChanged ? '<span class="badge bg-warning text-dark ms-1" style="font-size:0.6rem;">MODIFIED</span>' : ''}
                        </td>
                        <td class="text-danger bg-danger bg-opacity-10 text-break">${formatValue(oldVal)}</td>
                        <td class="text-success bg-success bg-opacity-10 text-break">${formatValue(newVal)}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            diffModal.show();
        });
    });

    // Real-time debounced auto-search
    let searchDebounceTimer = null;
    const searchInput = document.getElementById('auditSearchInput');
    const filterForm = document.getElementById('auditFilterForm');
    
    if (searchInput && filterForm) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(function() {
                filterForm.submit();
            }, 400);
        });
    }
});
</script>
