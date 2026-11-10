<?php
/**
 * Role & Staff Form with Comprehensive Hierarchical Permissions Matrix
 */
$isEdit = !empty($role['id']);
$formAction = $isEdit ? url('admin/roles/update/' . $role['id']) : url('admin/roles/store');
$concernFormAction = $isEdit ? url('admin/roles/edit/' . $role['id']) : url('admin/roles/create');
$selectedConcern = $selectedConcern ?? null;
$selectedConcernId = (int)($selectedConcernId ?? 0);
$assigned = Role::filterCrudPermissionIds(array_map('intval', $assignedPermissionIds ?? []));
$hierarchicalModules = $hierarchicalModules ?? Role::filterHierarchicalPermissions(
    Role::getHierarchicalPermissions(),
    $selectedConcern['module_keys'] ?? []
);

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
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 fw-bold"><?= $isEdit ? 'Edit Role: ' . e($role['name'] ?? '') : 'Add New Role' ?></h1>
            <div class="text-muted small mt-1">Define the role and assign permissions within one selected concern.</div>
        </div>
        <div>
            <a href="<?= url('admin/roles') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Roles</span>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <form method="GET" action="<?= $concernFormAction ?>" class="d-flex flex-grow-1 align-items-center gap-2">
                <label for="roleConcern" class="form-label fw-semibold mb-0">Concern</label>
                <select id="roleConcern" name="concern_id" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Select a concern --</option>
                    <?php foreach ($concerns ?? [] as $concern): ?>
                        <option value="<?= (int)$concern['id'] ?>" <?= $selectedConcernId === (int)$concern['id'] ? 'selected' : '' ?>>
                            <?= e($concern['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button class="btn btn-outline-primary" type="submit">Show Modules</button></noscript>
            </form>
            <?php if ($selectedConcern): ?>
                <span class="text-muted small">Only <strong><?= e($selectedConcern['name']) ?></strong> modules are shown. Saving preserves permissions for all other concerns.</span>
            <?php else: ?>
                <div class="alert alert-info mb-0">Select a concern to view and assign its modules.</div>
            <?php endif; ?>
        </div>
    </div>

    <form method="POST" action="<?= $formAction ?>" id="roleForm" autocomplete="off">
        <?= CSRF::field() ?>
        <input type="hidden" name="concern_id" value="<?= $selectedConcernId ?>">

        <!-- ============================================ -->
        <!-- ROLE DETAILS SECTION -->
        <!-- ============================================ -->
        <div class="card border-0 shadow-sm mb-4 rounded-3">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check text-primary"></i>
                    <span>Role Information</span>
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- 1. Role Name -->
                    <div class="col-12 col-md-5">
                        <label for="role_name" class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-tag"></i></span>
                            <input type="text" id="role_name" name="name" class="form-control" autocomplete="off" value="<?= e($role['name'] ?? '') ?>" placeholder="e.g. Warehouse Manager or Cutting Master" required autofocus>
                        </div>
                    </div>

                    <!-- 2. Role Slug / Code -->
                    <div class="col-12 col-md-4">
                        <label for="role_slug" class="form-label fw-semibold">Role Identifier (Slug) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light font-monospace">#</span>
                            <input type="text" id="role_slug" name="slug" class="form-control font-monospace" autocomplete="off" value="<?= e($role['slug'] ?? '') ?>" placeholder="e.g. warehouse-manager" required>
                        </div>
                        <div class="form-text text-muted">Unique slug identifier used for routing &amp; permissions.</div>
                    </div>

                    <!-- 3. Status -->
                    <div class="col-12 col-md-3">
                        <label for="is_active" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select id="is_active" name="is_active" class="form-select">
                            <option value="1" <?= (!isset($role['is_active']) || $role['is_active'] == 1) ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= (isset($role['is_active']) && $role['is_active'] == 0) ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- 4. Description -->
                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold">Role Description &amp; Scope</label>
                        <textarea id="description" name="description" class="form-control" rows="2" placeholder="Briefly describe the operational responsibilities and authority of this role..."><?= e($role['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- HIERARCHICAL PERMISSIONS MATRIX SECTION -->
        <!-- ============================================ -->
        <div class="card border-0 shadow-sm mb-4 rounded-3">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3 sticky-top" style="top: 70px; z-index: 1010; backdrop-filter: blur(8px); background: rgba(255,255,255,0.95);">
                <div>
                    <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-primary"></i>
                        <span>Module & Submodule Permissions Matrix</span>
                    </h5>
                    <div class="text-muted small mt-0.5">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 fw-semibold" id="activeCountBadge">
                            <span id="activeCount"><?= count($assigned) ?></span> / <?= $totalPossiblePerms ?> Active Permissions
                        </span>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Live Search Input -->
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="moduleFilter" class="form-control bg-light border-start-0 ps-0" placeholder="Search modules..." autocomplete="off">
                    </div>

                    <!-- Column Batch Tools -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" id="batchDropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-sliders"></i> Column Tools
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2">
                            <li><a class="dropdown-item py-1" href="#" id="btnSelectAllView"><i class="bi bi-eye text-primary me-2"></i>Select All View</a></li>
                            <li><a class="dropdown-item py-1" href="#" id="btnSelectAllCreate"><i class="bi bi-plus-circle text-success me-2"></i>Select All Create</a></li>
                            <li><a class="dropdown-item py-1" href="#" id="btnSelectAllEdit"><i class="bi bi-pencil-square text-warning me-2"></i>Select All Edit</a></li>
                            <li><a class="dropdown-item py-1" href="#" id="btnSelectAllDelete"><i class="bi bi-trash text-danger me-2"></i>Select All Delete</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-1" href="#" id="btnDeselectAllView"><i class="bi bi-eye-slash text-muted me-2"></i>Clear All View</a></li>
                            <li><a class="dropdown-item py-1" href="#" id="btnDeselectAllCreate"><i class="bi bi-dash-circle text-muted me-2"></i>Clear All Create</a></li>
                            <li><a class="dropdown-item py-1" href="#" id="btnDeselectAllEdit"><i class="bi bi-dash-square text-muted me-2"></i>Clear All Edit</a></li>
                            <li><a class="dropdown-item py-1" href="#" id="btnDeselectAllDelete"><i class="bi bi-trash-fill text-muted me-2"></i>Clear All Delete</a></li>
                        </ul>
                    </div>

                    <!-- Global Select / Deselect -->
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAll">
                        <i class="bi bi-check-all me-1"></i>Select All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAll">
                        <i class="bi bi-x-lg me-1"></i>Deselect All
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="d-flex flex-column gap-3 p-3 p-md-4" id="modulesWrapper">
                    <?php foreach ($hierarchicalModules as $modKey => $module): ?>
                        <?php
                        $submodules = $module['submodules'] ?? [];
                        $modIcon = $module['icon'] ?? 'bi-grid';
                        $modTitle = $module['title'] ?? ucfirst($modKey);
                        $modBadgeClass = $module['badge'] ?? 'bg-primary-subtle text-primary border-primary';
                        ?>
                        <div class="card border border-light-subtle rounded-3 shadow-xs module-card-block" data-module-key="<?= e($modKey) ?>">
                            
                            <!-- Module Header -->
                            <div class="card-header bg-light bg-opacity-50 py-2.5 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi <?= $modIcon ?> text-primary fs-5"></i>
                                    <strong class="text-dark module-header-title"><?= e($modTitle) ?></strong>
                                    <span class="badge <?= $modBadgeClass ?> border px-2 py-0.5 rounded-pill small">
                                        <?= count($submodules) ?> submodules
                                    </span>
                                </div>
                                <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                                    <label class="form-check-label small text-muted cursor-pointer" for="form_mod_toggle_<?= e($modKey) ?>">
                                        Toggle Module
                                    </label>
                                    <input class="form-check-input module-switch cursor-pointer" 
                                           type="checkbox" 
                                           id="form_mod_toggle_<?= e($modKey) ?>" 
                                           data-module="<?= e($modKey) ?>"
                                           title="Select / Deselect all in <?= e($modTitle) ?>">
                                </div>
                            </div>

                            <!-- Submodules Table -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 permissions-table">
                                    <thead class="table-light">
                                        <tr class="text-uppercase small text-muted" style="font-size: 11px;">
                                            <th class="ps-3 py-2" style="width: 36%;">Submodule</th>
                                            <th class="text-center py-2" style="width: 14%;">View</th>
                                            <th class="text-center py-2" style="width: 14%;">Create</th>
                                            <th class="text-center py-2" style="width: 14%;">Edit</th>
                                            <th class="text-center py-2" style="width: 14%;">Delete</th>
                                            <th class="text-center pe-3 py-2" style="width: 8%;">Row All</th>
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
                                            <tr class="submodule-row-item" data-submodule-key="<?= e($subKey) ?>">
                                                <!-- Submodule Info -->
                                                <td class="ps-3 py-2.5">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi <?= $subIcon ?> text-primary"></i>
                                                        <div>
                                                            <div class="fw-semibold text-dark submodule-title"><?= e($subName) ?></div>
                                                            <div class="text-muted small" style="font-size: 11px;"><?= e($subDesc) ?></div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <?php foreach (['view', 'create', 'edit', 'delete'] as $crudAction): ?>
                                                    <td class="text-center py-2.5">
                                                        <?php $columnPermissions = $crudColumns[$crudAction]; ?>
                                                        <?php if (!empty($columnPermissions)): ?>
                                                            <div class="d-flex flex-column align-items-center gap-1">
                                                                <?php foreach ($columnPermissions as $permissionAction => $permission): ?>
                                                                    <label class="form-check-label d-inline-flex align-items-center justify-content-center gap-1 small" title="<?= e($permission['description'] ?? ucfirst($permissionAction)) ?>">
                                                                        <input class="form-check-input perm-check perm-<?= e($crudAction) ?> mod-<?= e($modKey) ?> row-<?= e($subKey) ?>"
                                                                               type="checkbox"
                                                                               name="permissions[]"
                                                                               value="<?= $permission['id'] ?>"
                                                                               data-module="<?= e($modKey) ?>"
                                                                               data-submodule="<?= e($subKey) ?>"
                                                                               data-action="<?= e($permissionAction) ?>"
                                                                               <?= in_array((int)$permission['id'], $assigned, true) ? 'checked' : '' ?>>
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

                                                <!-- Row Toggle -->
                                                <td class="text-center pe-3 py-2.5">
                                                    <div class="form-check form-switch d-inline-block mb-0">
                                                        <input class="form-check-input row-switch cursor-pointer" 
                                                               type="checkbox" 
                                                               data-row="row-<?= e($subKey) ?>" 
                                                               title="Toggle all actions for <?= e($subName) ?>">
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Form Actions Footer -->
            <div class="card-footer bg-white py-3 px-4 border-top d-flex justify-content-between align-items-center">
                <a href="<?= url('admin/roles') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 shadow-sm d-inline-flex align-items-center gap-2 fw-semibold" <?= !$selectedConcern ? 'disabled' : '' ?>>
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= $isEdit ? 'Update Staff Role & Permissions' : 'Save Staff Role & Permissions' ?></span>
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.cursor-pointer { cursor: pointer; }
.shadow-xs { box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.permissions-table .form-check-input {
    width: 1.2em;
    height: 1.2em;
    cursor: pointer;
}
.perm-create:checked { background-color: #198754; border-color: #198754; }
.perm-edit:checked { background-color: #ffc107; border-color: #ffc107; }
.perm-delete:checked { background-color: #dc3545; border-color: #dc3545; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Prevent browser autofill on create
    <?php if (!$isEdit): ?>
    setTimeout(function() {
        ['staff_name', 'emp_id', 'email', 'password', 'mobile_number'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });
    }, 50);
    <?php endif; ?>

    const permBoxes = document.querySelectorAll('.perm-check');
    const moduleSwitches = document.querySelectorAll('.module-switch');
    const rowSwitches = document.querySelectorAll('.row-switch');
    const activeSpan = document.getElementById('activeCount');
    const moduleFilter = document.getElementById('moduleFilter');

    function syncState() {
        const activeCount = document.querySelectorAll('.perm-check:checked').length;
        if (activeSpan) activeSpan.textContent = activeCount;

        // Sync Row switches
        rowSwitches.forEach(function (sw) {
            const rowClass = sw.getAttribute('data-row');
            const boxes = document.querySelectorAll('.' + rowClass);
            const checked = document.querySelectorAll('.' + rowClass + ':checked').length;
            if (boxes.length > 0) {
                sw.checked = (checked === boxes.length);
                sw.indeterminate = (checked > 0 && checked < boxes.length);
            }
        });

        // Sync Module switches
        moduleSwitches.forEach(function (sw) {
            const modKey = sw.getAttribute('data-module');
            const boxes = document.querySelectorAll('.mod-' + modKey);
            const checked = document.querySelectorAll('.mod-' + modKey + ':checked').length;
            if (boxes.length > 0) {
                sw.checked = (checked === boxes.length);
                sw.indeterminate = (checked > 0 && checked < boxes.length);
            }
        });
    }

    permBoxes.forEach(function (cb) {
        cb.addEventListener('change', syncState);
    });

    rowSwitches.forEach(function (sw) {
        sw.addEventListener('change', function () {
            const rowClass = this.getAttribute('data-row');
            const state = this.checked;
            document.querySelectorAll('.' + rowClass).forEach(cb => cb.checked = state);
            syncState();
        });
    });

    moduleSwitches.forEach(function (sw) {
        sw.addEventListener('change', function () {
            const modKey = this.getAttribute('data-module');
            const state = this.checked;
            document.querySelectorAll('.mod-' + modKey).forEach(cb => cb.checked = state);
            syncState();
        });
    });

    // Global Select/Deselect
    document.getElementById('btnSelectAll')?.addEventListener('click', function () {
        permBoxes.forEach(cb => cb.checked = true);
        syncState();
    });

    document.getElementById('btnDeselectAll')?.addEventListener('click', function () {
        permBoxes.forEach(cb => cb.checked = false);
        syncState();
    });

    // Column batch tools
    function setColState(selector, state) {
        document.querySelectorAll(selector).forEach(cb => cb.checked = state);
        syncState();
    }
    document.getElementById('btnSelectAllView')?.addEventListener('click', (e) => { e.preventDefault(); setColState('.perm-view', true); });
    document.getElementById('btnDeselectAllView')?.addEventListener('click', (e) => { e.preventDefault(); setColState('.perm-view', false); });
    document.getElementById('btnSelectAllCreate')?.addEventListener('click', (e) => { e.preventDefault(); setColState('.perm-create', true); });
    document.getElementById('btnDeselectAllCreate')?.addEventListener('click', (e) => { e.preventDefault(); setColState('.perm-create', false); });
    document.getElementById('btnSelectAllEdit')?.addEventListener('click', (e) => { e.preventDefault(); setColState('.perm-edit', true); });
    document.getElementById('btnDeselectAllEdit')?.addEventListener('click', (e) => { e.preventDefault(); setColState('.perm-edit', false); });
    document.getElementById('btnSelectAllDelete')?.addEventListener('click', (e) => { e.preventDefault(); setColState('.perm-delete', true); });
    document.getElementById('btnDeselectAllDelete')?.addEventListener('click', (e) => { e.preventDefault(); setColState('.perm-delete', false); });

    // Live search filter
    if (moduleFilter) {
        moduleFilter.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.module-card-block').forEach(function (block) {
                const headerTitle = block.querySelector('.module-header-title')?.textContent.toLowerCase() || '';
                const rows = block.querySelectorAll('.submodule-row-item');
                let visRows = 0;

                rows.forEach(function (row) {
                    const title = row.querySelector('.submodule-title')?.textContent.toLowerCase() || '';
                    if (!q || headerTitle.includes(q) || title.includes(q)) {
                        row.style.display = '';
                        visRows++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                block.style.display = (visRows > 0) ? '' : 'none';
            });
        });
    }

    // Auto slug generator from Role Name
    const roleNameInput = document.getElementById('role_name');
    const roleSlugInput = document.getElementById('role_slug');
    let isSlugManual = <?= $isEdit ? 'true' : 'false' ?>;

    if (roleSlugInput) {
        roleSlugInput.addEventListener('input', function () {
            isSlugManual = true;
        });
    }

    if (roleNameInput && roleSlugInput) {
        roleNameInput.addEventListener('input', function () {
            if (!isSlugManual || !roleSlugInput.value) {
                const slug = this.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                roleSlugInput.value = slug;
            }
        });
    }

    // Initial sync
    syncState();
});
</script>
