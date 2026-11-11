<?php
/**
 * Permissions Matrix Manager View
 * Comprehensive hierarchical permissions management for roles & submodules.
 */
$rolePermIds = Role::filterCrudPermissionIds(array_map('intval', $rolePermissionIds ?? []));
$totalPossiblePerms = 0;
foreach ($hierarchicalModules as $m) {
    foreach ($m['submodules'] as $s) {
        $totalPossiblePerms += count(array_intersect_key(
            $s['permissions'] ?? [],
            array_flip(['view', 'create', 'edit', 'delete'])
        ));
    }
}
?>

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <label for="permissionConcern" class="form-label fw-semibold mb-0">Concern</label>
            <form method="GET" action="<?= url('admin/roles/permissions/' . $role['id']) ?>" class="d-flex flex-grow-1 gap-2">
                <select id="permissionConcern" name="concern_id" class="form-select" onchange="this.form.submit()" <?= empty($concerns) ? 'disabled' : '' ?>>
                    <option value="" <?= !$selectedConcernId ? 'selected' : '' ?>>-- Select a concern --</option>
                    <?php foreach ($concerns as $concern): ?>
                        <option value="<?= (int)$concern['id'] ?>" <?= (int)$selectedConcernId === (int)$concern['id'] ? 'selected' : '' ?>>
                            <?= e($concern['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($selectedConcern): ?><noscript><button class="btn btn-outline-primary" type="submit">Show Modules</button></noscript><?php endif; ?>
            </form>
            <?php if ($selectedConcern): ?>
                <span class="text-muted small">Only modules assigned to <strong><?= e($selectedConcern['name']) ?></strong> are shown. Other concern permissions are preserved when saving.</span>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!$selectedConcern): ?>
        <div class="alert alert-info">Create a concern and assign modules in <a href="<?= url('admin/staff/concerns') ?>">Staff → Concerns &amp; Modules</a> before managing concern-scoped role permissions.</div>
    <?php endif; ?>
    <form method="POST" action="<?= url('admin/roles/permissions/' . $role['id']) ?>" id="permissionsMatrixForm">
        <?= CSRF::field() ?>
        <input type="hidden" name="concern_id" value="<?= (int)$selectedConcernId ?>">

        <!-- ============================================ -->
        <!-- STICKY TOP ACTION TOOLBAR -->
        <!-- ============================================ -->
        <div class="card border-0 shadow-sm sticky-top mb-4 rounded-3" style="top: 70px; z-index: 1020; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
            <div class="card-body py-3 px-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    
                    <!-- Role Info & Active Stats -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 p-2 text-primary d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px;">
                            <i class="bi bi-shield-lock-fill fs-4"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h4 class="mb-0 fw-bold text-dark">
                                    Permissions Matrix: <span class="text-primary"><?= e($role['name']) ?></span>
                                </h4>
                                <span class="badge bg-light text-dark border px-2 py-1 font-monospace small">
                                    <i class="bi bi-key-fill text-muted me-1"></i><?= e($role['slug']) ?>
                                </span>
                            </div>
                            <div class="text-muted small mt-1 d-flex align-items-center gap-2 flex-wrap">
                                <span>Role Access Control Panel</span>
                                <span>&bull;</span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 fw-semibold" id="activeCountBadge">
                                    <span id="activeCount"><?= count($rolePermIds) ?></span> / <?= $totalPossiblePerms ?> Active Permissions
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Search Filter & Quick Actions -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Live Search Input -->
                        <div class="input-group input-group-sm" style="width: 220px;">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="moduleFilter" class="form-control bg-light border-start-0 ps-0" placeholder="Filter modules & submodules..." autocomplete="off">
                            <button type="button" class="btn btn-light border border-start-0 d-none" id="clearFilterBtn">
                                <i class="bi bi-x-circle-fill text-muted"></i>
                            </button>
                        </div>

                        <!-- Column Batch Selectors Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" id="batchActionDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-sliders"></i>
                                <span>Column Tools</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" aria-labelledby="batchActionDropdown">
                                <li><h6 class="dropdown-header text-uppercase small fw-bold">Toggle Column-Wide</h6></li>
                                <li><a class="dropdown-item py-1.5" href="#" id="btnSelectAllView"><i class="bi bi-eye text-primary me-2"></i>Select All View</a></li>
                                <li><a class="dropdown-item py-1.5" href="#" id="btnSelectAllCreate"><i class="bi bi-plus-circle text-success me-2"></i>Select All Create</a></li>
                                <li><a class="dropdown-item py-1.5" href="#" id="btnSelectAllEdit"><i class="bi bi-pencil-square text-warning me-2"></i>Select All Edit</a></li>
                                <li><a class="dropdown-item py-1.5" href="#" id="btnSelectAllDelete"><i class="bi bi-trash text-danger me-2"></i>Select All Delete</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-1.5" href="#" id="btnDeselectAllView"><i class="bi bi-eye-slash text-muted me-2"></i>Clear All View</a></li>
                                <li><a class="dropdown-item py-1.5" href="#" id="btnDeselectAllCreate"><i class="bi bi-dash-circle text-muted me-2"></i>Clear All Create</a></li>
                                <li><a class="dropdown-item py-1.5" href="#" id="btnDeselectAllEdit"><i class="bi bi-dash-square text-muted me-2"></i>Clear All Edit</a></li>
                                <li><a class="dropdown-item py-1.5" href="#" id="btnDeselectAllDelete"><i class="bi bi-trash-fill text-muted me-2"></i>Clear All Delete</a></li>
                            </ul>
                        </div>

                        <!-- Global Select / Deselect -->
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAll" title="Select all permissions across all modules">
                            <i class="bi bi-check-all me-1"></i>Select All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAll" title="Deselect all permissions">
                            <i class="bi bi-x-lg me-1"></i>Deselect All
                        </button>

                        <!-- Cancel & Save -->
                        <a href="<?= url('admin/roles') ?>" class="btn btn-sm btn-outline-dark">Cancel</a>
                        <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm d-flex align-items-center gap-1" <?= !$selectedConcern ? 'disabled' : '' ?>>
                            <i class="bi bi-check2-circle fs-6"></i>
                            <span class="fw-semibold">Save Permissions</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- No Results Warning for Filter -->
        <div id="noResultsAlert" class="alert alert-info border-0 shadow-sm d-none mb-4 align-items-center gap-2">
            <i class="bi bi-info-circle-fill fs-5"></i>
            <div>No matching modules or submodules found for "<span id="searchQueryText" class="fw-bold"></span>".</div>
        </div>

        <!-- ============================================ -->
        <!-- HIERARCHICAL MODULES PERMISSIONS MATRIX -->
        <!-- ============================================ -->
        <div class="d-flex flex-column gap-4" id="modulesContainer">
            <?php foreach ($hierarchicalModules as $modKey => $module): ?>
                <?php
                $submodules = $module['submodules'] ?? [];
                $modIcon = $module['icon'] ?? 'bi-grid';
                $modTitle = $module['title'] ?? ucfirst($modKey);
                $modBadgeClass = $module['badge'] ?? 'bg-primary-subtle text-primary border-primary';

                // Calculate module-level active permissions count
                $modTotalPerms = 0;
                $modActivePerms = 0;
                foreach ($submodules as $sub) {
                    $crudPermissions = Role::getCrudPermissionsByAction($sub['permissions'] ?? []);
                    foreach ($crudPermissions as $actionPermissions) {
                        foreach ($actionPermissions as $p) {
                            $modTotalPerms++;
                            if (in_array((int)$p['id'], $rolePermIds, true)) {
                                $modActivePerms++;
                            }
                        }
                    }
                }
                ?>
                <div class="card border-0 shadow-sm rounded-3 module-section" data-module-key="<?= e($modKey) ?>">
                    
                    <!-- Module Card Header -->
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-2 d-flex align-items-center justify-content-center bg-light text-primary" style="width: 40px; height: 40px;">
                                <i class="bi <?= $modIcon ?> fs-5"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h5 class="mb-0 fw-bold text-dark module-title-text"><?= e($modTitle) ?></h5>
                                    <span class="badge <?= $modBadgeClass ?> border px-2 py-0.5 small rounded-pill">
                                        <?= count($submodules) ?> <?= count($submodules) === 1 ? 'Submodule' : 'Submodules' ?>
                                    </span>
                                </div>
                                <div class="text-muted small mt-0.5">
                                    <span class="mod-active-count fw-semibold text-dark"><?= $modActivePerms ?></span> of <span class="mod-total-count"><?= $modTotalPerms ?></span> permissions active
                                </div>
                            </div>
                        </div>

                        <!-- Module Level Master Toggle -->
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                <label class="form-check-label small fw-semibold text-muted cursor-pointer" for="mod_toggle_<?= e($modKey) ?>">
                                    Toggle Module
                                </label>
                                <input class="form-check-input module-master-toggle cursor-pointer" 
                                       type="checkbox" 
                                       id="mod_toggle_<?= e($modKey) ?>" 
                                       data-module="<?= e($modKey) ?>"
                                       title="Toggle all permissions in <?= e($modTitle) ?>"
                                       style="width: 2.5em; height: 1.25em;">
                            </div>
                        </div>
                    </div>

                    <!-- Submodules Table -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 permissions-table">
                                <thead class="table-light">
                                    <tr class="text-uppercase small text-muted font-monospace">
                                        <th class="ps-4 py-3" style="min-width: 260px; width: 34%;">Submodule & Description</th>
                                        <th class="text-center py-3" style="width: 14%;">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">View</span>
                                        </th>
                                        <th class="text-center py-3" style="width: 14%;">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Create / Receive</span>
                                        </th>
                                        <th class="text-center py-3" style="width: 14%;">
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Edit</span>
                                        </th>
                                        <th class="text-center py-3" style="width: 14%;">
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Delete</span>
                                        </th>
                                        <th class="text-center pe-4 py-3" style="width: 10%;">
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Row All</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submodules as $subKey => $sub): ?>
                                        <?php
                                        $subPerms = $sub['permissions'] ?? [];
                                        $subName = $sub['name'] ?? ucfirst($subKey);
                                        $subIcon = $sub['icon'] ?? 'bi-circle';
                                        $subDesc = $sub['desc'] ?? '';

                                        $crudColumns = Role::getCrudPermissionsByAction($subPerms);
                                        ?>
                                        <tr class="submodule-row" data-submodule-key="<?= e($subKey) ?>" data-module-key="<?= e($modKey) ?>">
                                            <!-- Submodule Title & Info -->
                                            <td class="ps-4 py-3">
                                                <div class="d-flex align-items-start gap-2.5">
                                                    <div class="text-primary mt-0.5">
                                                        <i class="bi <?= $subIcon ?> fs-5"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark submodule-name-text"><?= e($subName) ?></div>
                                                        <div class="text-muted small line-clamp-1" style="font-size: 12px;"><?= e($subDesc) ?></div>
                                                    </div>
                                                </div>
                                            </td>

                                            <?php foreach (['view', 'create', 'edit', 'delete'] as $crudAction): ?>
                                                <td class="text-center py-3">
                                                    <?php $columnPermissions = $crudColumns[$crudAction]; ?>
                                                    <?php if (!empty($columnPermissions)): ?>
                                                        <div class="d-flex flex-column align-items-center gap-1">
                                                            <?php foreach ($columnPermissions as $permissionAction => $permission): ?>
                                                                <?php $isChecked = in_array((int)$permission['id'], $rolePermIds, true); ?>
                                                                <?php $permissionColumnAction = $permissionAction === 'receive' ? 'receive' : $crudAction; ?>
                                                                <label class="form-check-label d-inline-flex align-items-center justify-content-center gap-1 small" title="<?= e($permission['description'] ?? ucfirst($permissionAction)) ?>">
                                                                <input class="form-check-input mt-0 perm-checkbox perm-<?= e($permissionColumnAction) ?> mod-<?= e($modKey) ?> row-<?= e($subKey) ?>"
                                                                           type="checkbox"
                                                                           name="permissions[]"
                                                                           value="<?= $permission['id'] ?>"
                                                                           id="perm_<?= $permission['id'] ?>"
                                                                           data-module="<?= e($modKey) ?>"
                                                                           data-submodule="<?= e($subKey) ?>"
                                                                           data-action="<?= e($permissionAction) ?>"
                                                                           <?= $isChecked ? 'checked' : '' ?>>
                                                                    <?php if ($permissionAction !== $crudAction): ?>
                                                                        <span class="text-capitalize"><?= e(str_replace('_', ' ', $permissionAction)) ?></span>
                                                                    <?php endif; ?>
                                                                </label>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted small opacity-50">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>

                                            <!-- 6. ROW MASTER TOGGLE -->
                                            <td class="text-center pe-4 py-3">
                                                <div class="form-check form-switch d-inline-block mb-0">
                                                    <input class="form-check-input row-master-toggle cursor-pointer" 
                                                           type="checkbox" 
                                                           id="row_toggle_<?= e($subKey) ?>" 
                                                           data-row="row-<?= e($subKey) ?>" 
                                                           data-module="<?= e($modKey) ?>"
                                                           title="Toggle all actions for <?= e($subName) ?>">
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- ============================================ -->
        <!-- FLOATING BOTTOM SAVE BAR -->
        <!-- ============================================ -->
        <div class="card border-0 shadow-sm mt-4 rounded-3 bg-white">
            <div class="card-body py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= url('admin/roles') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-arrow-left"></i>
                        <span>Back to Roles</span>
                    </a>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small d-none d-sm-inline">
                        Clicking save will instantly synchronize active permissions for role <strong class="text-dark"><?= e($role['name']) ?></strong>.
                    </span>
                    <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2 fw-bold" <?= !$selectedConcern ? 'disabled' : '' ?>>
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Save Permissions Matrix</span>
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

<style>
/* Permissions Matrix Enhanced Styles */
.cursor-pointer { cursor: pointer; }
.shadow-xs { box-shadow: 0 1px 3px rgba(0,0,0,0.05); }

/* Permissions Checkboxes */
.permissions-table .form-check-input {
    width: 1.25em;
    height: 1.25em;
    cursor: pointer;
    border-color: #cbd5e1;
    transition: all 0.15s ease-in-out;
}
.permissions-table .form-check-input:hover {
    border-color: #0d6efd;
    transform: scale(1.08);
}
.permissions-table .form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Custom Special Action Colors */
.bg-purple-subtle { background-color: #f3e8ff !important; }
.text-purple { color: #7e22ce !important; }
.border-purple { border-color: #d8b4fe !important; }

.bg-indigo-subtle { background-color: #e0e7ff !important; }
.text-indigo { color: #4338ca !important; }
.border-indigo { border-color: #a5b4fc !important; }

.bg-teal-subtle { background-color: #ccfbf1 !important; }
.text-teal { color: #0f766e !important; }
.border-teal { border-color: #99f6e4 !important; }

/* Action Specific Checkbox Tint on Checked */
.perm-create:checked {
    background-color: #198754 !important;
    border-color: #198754 !important;
}
.perm-edit:checked {
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
}
.perm-delete:checked {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}

/* Row Hover & Focus */
.permissions-table tbody tr:hover {
    background-color: #f8fafc;
}
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const totalPossible = <?= (int)$totalPossiblePerms ?>;
    const permCheckboxes = document.querySelectorAll('.perm-checkbox');
    const moduleMasterToggles = document.querySelectorAll('.module-master-toggle');
    const rowMasterToggles = document.querySelectorAll('.row-master-toggle');
    const activeCountSpan = document.getElementById('activeCount');
    const moduleFilter = document.getElementById('moduleFilter');
    const clearFilterBtn = document.getElementById('clearFilterBtn');
    const noResultsAlert = document.getElementById('noResultsAlert');
    const searchQueryText = document.getElementById('searchQueryText');

    // Sync all stats, counts, and indeterminate states
    function syncAllStates() {
        // 1. Total Active Count
        const activeCount = document.querySelectorAll('.perm-checkbox:checked').length;
        if (activeCountSpan) {
            activeCountSpan.textContent = activeCount;
        }

        // 2. Row Master Toggles
        rowMasterToggles.forEach(function (rowToggle) {
            const rowClass = rowToggle.getAttribute('data-row');
            const rowBoxes = document.querySelectorAll('.' + rowClass);
            const rowChecked = document.querySelectorAll('.' + rowClass + ':checked').length;

            if (rowBoxes.length > 0) {
                rowToggle.checked = (rowChecked === rowBoxes.length);
                rowToggle.indeterminate = (rowChecked > 0 && rowChecked < rowBoxes.length);
            }
        });

        // 3. Module Master Toggles & Module Header Counters
        moduleMasterToggles.forEach(function (modToggle) {
            const modKey = modToggle.getAttribute('data-module');
            const modBoxes = document.querySelectorAll('.mod-' + modKey);
            const modChecked = document.querySelectorAll('.mod-' + modKey + ':checked').length;
            const modSection = modToggle.closest('.module-section');

            if (modBoxes.length > 0) {
                modToggle.checked = (modChecked === modBoxes.length);
                modToggle.indeterminate = (modChecked > 0 && modChecked < modBoxes.length);
            }

            if (modSection) {
                const countSpan = modSection.querySelector('.mod-active-count');
                if (countSpan) {
                    countSpan.textContent = modChecked;
                }
            }
        });
    }

    // Individual checkbox change listener
    permCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            syncAllStates();
        });
    });

    // Row Master Toggle listener
    rowMasterToggles.forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            const rowClass = this.getAttribute('data-row');
            const isChecked = this.checked;
            document.querySelectorAll('.' + rowClass).forEach(function (cb) {
                cb.checked = isChecked;
            });
            syncAllStates();
        });
    });

    // Module Master Toggle listener
    moduleMasterToggles.forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            const modKey = this.getAttribute('data-module');
            const isChecked = this.checked;
            document.querySelectorAll('.mod-' + modKey).forEach(function (cb) {
                cb.checked = isChecked;
            });
            syncAllStates();
        });
    });

    // Global Select All
    const btnSelectAll = document.getElementById('btnSelectAll');
    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', function () {
            permCheckboxes.forEach(cb => cb.checked = true);
            syncAllStates();
        });
    }

    // Global Deselect All
    const btnDeselectAll = document.getElementById('btnDeselectAll');
    if (btnDeselectAll) {
        btnDeselectAll.addEventListener('click', function () {
            permCheckboxes.forEach(cb => cb.checked = false);
            syncAllStates();
        });
    }

    // Column-Wide Selectors
    function setColumnState(selector, state) {
        document.querySelectorAll(selector).forEach(cb => cb.checked = state);
        syncAllStates();
    }

    document.getElementById('btnSelectAllView')?.addEventListener('click', (e) => { e.preventDefault(); setColumnState('.perm-view', true); });
    document.getElementById('btnDeselectAllView')?.addEventListener('click', (e) => { e.preventDefault(); setColumnState('.perm-view', false); });

    document.getElementById('btnSelectAllCreate')?.addEventListener('click', (e) => { e.preventDefault(); setColumnState('.perm-create, .perm-receive', true); });
    document.getElementById('btnDeselectAllCreate')?.addEventListener('click', (e) => { e.preventDefault(); setColumnState('.perm-create, .perm-receive', false); });

    document.getElementById('btnSelectAllEdit')?.addEventListener('click', (e) => { e.preventDefault(); setColumnState('.perm-edit', true); });
    document.getElementById('btnDeselectAllEdit')?.addEventListener('click', (e) => { e.preventDefault(); setColumnState('.perm-edit', false); });

    document.getElementById('btnSelectAllDelete')?.addEventListener('click', (e) => { e.preventDefault(); setColumnState('.perm-delete', true); });
    document.getElementById('btnDeselectAllDelete')?.addEventListener('click', (e) => { e.preventDefault(); setColumnState('.perm-delete', false); });

    // Live Search Filter
    if (moduleFilter) {
        moduleFilter.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            
            if (clearFilterBtn) {
                if (query.length > 0) {
                    clearFilterBtn.classList.remove('d-none');
                } else {
                    clearFilterBtn.classList.add('d-none');
                }
            }

            let visibleModulesCount = 0;
            const moduleSections = document.querySelectorAll('.module-section');

            moduleSections.forEach(function (section) {
                const modTitle = section.querySelector('.module-title-text')?.textContent.toLowerCase() || '';
                const rows = section.querySelectorAll('.submodule-row');
                let visibleRowsInModule = 0;

                rows.forEach(function (row) {
                    const subName = row.querySelector('.submodule-name-text')?.textContent.toLowerCase() || '';
                    const subDesc = row.querySelector('.line-clamp-1')?.textContent.toLowerCase() || '';

                    if (!query || modTitle.includes(query) || subName.includes(query) || subDesc.includes(query)) {
                        row.style.display = '';
                        visibleRowsInModule++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (visibleRowsInModule > 0) {
                    section.style.display = '';
                    visibleModulesCount++;
                } else {
                    section.style.display = 'none';
                }
            });

            if (noResultsAlert) {
                if (visibleModulesCount === 0 && query.length > 0) {
                    noResultsAlert.classList.remove('d-none');
                    noResultsAlert.classList.add('d-flex');
                    if (searchQueryText) searchQueryText.textContent = query;
                } else {
                    noResultsAlert.classList.add('d-none');
                    noResultsAlert.classList.remove('d-flex');
                }
            }
        });

        if (clearFilterBtn) {
            clearFilterBtn.addEventListener('click', function () {
                moduleFilter.value = '';
                moduleFilter.dispatchEvent(new Event('input'));
                moduleFilter.focus();
            });
        }
    }

    // Initial state calculation on page load
    syncAllStates();
});
</script>
