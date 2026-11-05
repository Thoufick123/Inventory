<?php
/**
 * Staff Form View (Create & Edit) with Dynamic Role Permissions Matrix
 */
$isEdit = !empty($staff['id']);
$formAction = $isEdit ? url('admin/staff/update/' . $staff['id']) : url('admin/staff/store');
$dept = strtoupper($_GET['department'] ?? $staff['department'] ?? 'PRODUCTION');
if (!in_array($dept, ['PRODUCTION', 'WAREHOUSE', 'WHATSAPP', 'WEBSITE', 'SHOP'])) {
    $dept = 'PRODUCTION';
}

$hierarchicalModules = $hierarchicalModules ?? Role::getHierarchicalPermissions();
$rolePermissionsMap = $rolePermissionsMap ?? Role::getAllRolePermissionsMap();
$staffPermissions = array_map('intval', $staffPermissions ?? []);
$staffPermissions = Role::filterCrudPermissionIds($staffPermissions);

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
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold"><?= $isEdit ? 'Edit Staff Member' : 'Create New Staff Member' ?></h1>
            <p class="text-muted mb-0"><?= $isEdit ? 'Update account details, role assignment, and View, Create, Edit, or Delete access.' : 'Add a team member and assign View, Create, Edit, or Delete access.' ?></p>
        </div>
        <div>
            <a href="<?= url('admin/staff') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Staff Directory</span>
            </a>
        </div>
    </div>

    <!-- Main Form -->
    <form method="POST" action="<?= $formAction ?>" id="staffForm">
        <?= CSRF::field() ?>
        <input type="hidden" name="permissions[]" value="">

        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">

                <!-- 1. Personal Details & Credentials Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="bi <?= $isEdit ? 'bi-person-gear' : 'bi-person-plus' ?> text-primary me-2"></i>
                            Personal Details &amp; Credentials
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <!-- Full Name -->
                            <div class="col-12 col-md-6">
                                <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Rahul Sharma" value="<?= e($staff['full_name'] ?? '') ?>" required>
                                </div>
                            </div>

                            <!-- Username -->
                            <div class="col-12 col-md-6">
                                <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">@</span>
                                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. rahul_sharma" value="<?= e($staff['username'] ?? '') ?>" <?= $isEdit ? 'readonly' : 'required' ?>>
                                </div>
                                <?php if ($isEdit): ?>
                                    <div class="form-text text-muted">Username cannot be changed after creation.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Email -->
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="rahul@example.com" value="<?= e($staff['email'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="col-12 col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="phone" class="form-label fw-semibold mb-0">
                                        <i class="bi bi-telephone text-primary me-1"></i>Phone Number
                                    </label>
                                    <span id="phoneCounter" class="badge bg-light text-muted border font-monospace">0 / 10 digits</span>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold text-secondary">+91</span>
                                    <input type="tel" 
                                           id="phone" 
                                           name="phone" 
                                           class="form-control font-monospace" 
                                           placeholder="10-digit mobile number" 
                                           value="<?= e($staff['phone'] ?? '') ?>"
                                           maxlength="10"
                                           inputmode="numeric"
                                           autocomplete="off"
                                           data-staff-id="<?= e($staff['id'] ?? '') ?>">
                                    <span class="input-group-text bg-light" id="phoneStatusIcon">
                                        <i class="bi bi-hash text-muted"></i>
                                    </span>
                                </div>
                                <div id="phoneFeedback" class="small mt-1 text-danger fw-semibold d-none"></div>
                                <div id="phoneSuccess" class="small mt-1 text-success fw-semibold d-none"></div>
                                <div class="form-text text-muted small">
                                    Only 10-digit numeric mobile numbers (starting with 6-9). Letters and duplicate numbers are rejected.
                                </div>
                            </div>

                            <!-- System Role -->
                            <div class="col-12 col-md-6">
                                <label for="role_id" class="form-label fw-semibold">Assign System Role <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-shield-check"></i></span>
                                    <select id="role_id" name="role_id" class="form-select" required>
                                        <option value="">-- Select Role --</option>
                                        <?php foreach ($roles as $r): ?>
                                            <?php
                                             $rDept = $r['primary_department'] ?? 'ALL';
                                             $rDepts = $r['departments'] ?? [];
                                             $rDeptsJson = htmlspecialchars(json_encode($rDepts), ENT_QUOTES, 'UTF-8');
                                             $badgeLabel = match($rDept) {
                                                 'PRODUCTION' => '🏭 Production',
                                                 'WAREHOUSE'  => '🏢 Warehouse',
                                                 'WHATSAPP'   => '💬 WhatsApp',
                                                 'WEBSITE'    => '🌐 Website',
                                                 'SHOP'       => '🛍️ Shop',
                                                 default      => '🌐 All Modules'
                                             };
                                             ?>
                                            <option value="<?= $r['id'] ?>" 
                                                    data-primary-dept="<?= e($rDept) ?>"
                                                    data-departments='<?= $rDeptsJson ?>'
                                                    data-name="<?= e($r['name']) ?>"
                                                    <?= (string)($staff['role_id'] ?? '') === (string)$r['id'] ? 'selected' : '' ?>>
                                                <?= e($r['name']) ?> [<?= $badgeLabel ?>]
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- Real-time Department Validation Alert -->
                                <div id="role_mismatch_warning" class="alert alert-warning d-none py-2 px-3 mt-2 small d-flex align-items-center gap-2 mb-0 rounded-3 shadow-xs">
                                    <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"></i>
                                    <div>
                                        <strong>Department Mismatch:</strong> 
                                        <span id="role_mismatch_msg">The selected role has no access permissions for this department.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label fw-semibold">
                                    Password <?= $isEdit ? '<span class="text-muted fw-normal">(Leave blank to keep unchanged)</span>' : '<span class="text-danger">*</span>' ?>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="<?= $isEdit ? '••••••••' : 'Min 6 characters' ?>" <?= $isEdit ? '' : 'required' ?>>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Department Selection Cards (5 Distinct Operational Concerns) -->
                            <div class="col-12 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold text-dark mb-0">
                                        <i class="bi bi-diagram-3 text-primary me-2"></i>Select Department / Operational Concern <span class="text-danger">*</span>
                                    </label>
                                    <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Auto-scoped based on assigned role</span>
                                </div>
                                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-2.5" id="departmentCards">
                                    <!-- 1. Production -->
                                    <div class="col">
                                        <input type="radio" class="btn-check dept-radio" name="department" id="dept_production" value="PRODUCTION" <?= $dept === 'PRODUCTION' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-primary w-100 p-2.5 text-start d-flex align-items-center h-100 dept-card rounded-3 shadow-xs" for="dept_production" id="label_dept_production">
                                            <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-2 text-primary flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                <i class="bi bi-gear-wide-connected fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="fw-bold text-truncate" style="font-size: 13.5px;">Production</span>
                                                    <span class="dept-badge badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small d-none" style="font-size: 9px;">Auto</span>
                                                </div>
                                                <div class="text-muted small text-truncate" style="font-size: 11px;">Manufacturing &amp; Lots</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- 2. Warehouse -->
                                    <div class="col">
                                        <input type="radio" class="btn-check dept-radio" name="department" id="dept_warehouse" value="WAREHOUSE" <?= $dept === 'WAREHOUSE' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success w-100 p-2.5 text-start d-flex align-items-center h-100 dept-card rounded-3 shadow-xs" for="dept_warehouse" id="label_dept_warehouse">
                                            <div class="rounded-circle bg-success bg-opacity-10 p-2 me-2 text-success flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                <i class="bi bi-buildings fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="fw-bold text-truncate" style="font-size: 13.5px;">Warehouse</span>
                                                    <span class="dept-badge badge bg-success-subtle text-success border border-success-subtle rounded-pill small d-none" style="font-size: 9px;">Auto</span>
                                                </div>
                                                <div class="text-muted small text-truncate" style="font-size: 11px;">Stock &amp; Storage</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- 3. WhatsApp -->
                                    <div class="col">
                                        <input type="radio" class="btn-check dept-radio" name="department" id="dept_whatsapp" value="WHATSAPP" <?= $dept === 'WHATSAPP' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success w-100 p-2.5 text-start d-flex align-items-center h-100 dept-card rounded-3 shadow-xs" for="dept_whatsapp" id="label_dept_whatsapp">
                                            <div class="rounded-circle bg-success bg-opacity-15 p-2 me-2 text-success flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                <i class="bi bi-whatsapp fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="fw-bold text-truncate" style="font-size: 13.5px;">WhatsApp</span>
                                                    <span class="dept-badge badge bg-success-subtle text-success border border-success-subtle rounded-pill small d-none" style="font-size: 9px;">Auto</span>
                                                </div>
                                                <div class="text-muted small text-truncate" style="font-size: 11px;">B2B Orders &amp; Dispatches</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- 4. Website -->
                                    <div class="col">
                                        <input type="radio" class="btn-check dept-radio" name="department" id="dept_website" value="WEBSITE" <?= $dept === 'WEBSITE' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-info w-100 p-2.5 text-start d-flex align-items-center h-100 dept-card rounded-3 shadow-xs" for="dept_website" id="label_dept_website">
                                            <div class="rounded-circle bg-info bg-opacity-10 p-2 me-2 text-info flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                <i class="bi bi-globe fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="fw-bold text-truncate" style="font-size: 13.5px;">Website</span>
                                                    <span class="dept-badge badge bg-info-subtle text-info border border-info-subtle rounded-pill small d-none" style="font-size: 9px;">Auto</span>
                                                </div>
                                                <div class="text-muted small text-truncate" style="font-size: 11px;">eCommerce Store</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- 5. Shop -->
                                    <div class="col">
                                        <input type="radio" class="btn-check dept-radio" name="department" id="dept_shop" value="SHOP" <?= $dept === 'SHOP' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-warning w-100 p-2.5 text-start d-flex align-items-center h-100 dept-card rounded-3 shadow-xs" for="dept_shop" id="label_dept_shop">
                                            <div class="rounded-circle bg-warning bg-opacity-10 p-2 me-2 text-warning flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                <i class="bi bi-shop fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="fw-bold text-truncate" style="font-size: 13.5px;">Shop</span>
                                                    <span class="dept-badge badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill small d-none" style="font-size: 9px;">Auto</span>
                                                </div>
                                                <div class="text-muted small text-truncate" style="font-size: 11px;">Retail &amp; POS Checkout</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic Facility Dropdown Block -->
                            <div class="col-12 pt-2">
                                <!-- Production Unit Block -->
                                <div class="dept-unit-block" id="block_production">
                                    <label for="production_unit_id" class="form-label fw-semibold">Assigned Production Unit</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-gear-wide"></i></span>
                                        <select id="production_unit_id" name="production_unit_id" class="form-select">
                                            <option value="">-- All Production Units --</option>
                                            <?php foreach ($productionUnits as $pu): ?>
                                                <option value="<?= $pu['id'] ?>" <?= (string)($staff['production_unit_id'] ?? '') === (string)$pu['id'] ? 'selected' : '' ?>>
                                                    <?= e($pu['name']) ?> (<?= e($pu['code']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Warehouse Block -->
                                <div class="dept-unit-block d-none" id="block_warehouse">
                                    <label for="warehouse_id" class="form-label fw-semibold" id="warehouse_label">Assigned Warehouse</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-buildings text-secondary" id="warehouse_icon"></i></span>
                                        <select id="warehouse_id" name="warehouse_id" class="form-select">
                                            <option value="">-- All Warehouses / Central Storage --</option>
                                            <?php foreach ($warehouses as $wh): ?>
                                                <option value="<?= $wh['id'] ?>" <?= (string)($staff['warehouse_id'] ?? '') === (string)$wh['id'] ? 'selected' : '' ?>>
                                                    <?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- WhatsApp Channel Block -->
                                <div class="dept-unit-block d-none" id="block_whatsapp">
                                    <label for="whatsapp_channel_id" class="form-label fw-semibold">Assigned WhatsApp Channel</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-whatsapp text-success"></i></span>
                                        <select id="whatsapp_channel_id" name="whatsapp_channel_id" class="form-select">
                                            <option value="">-- All WhatsApp Channels --</option>
                                            <?php foreach ($whatsappChannels ?? [] as $wch): ?>
                                                <option value="<?= $wch['id'] ?>" <?= (string)($staff['channel_id'] ?? '') === (string)$wch['id'] ? 'selected' : '' ?>>
                                                    <?= e($wch['name']) ?> (<?= e($wch['code']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Website Store / Channel Block -->
                                <div class="dept-unit-block d-none" id="block_website">
                                    <label for="website_channel_id" class="form-label fw-semibold">Assigned Website Store / Channel</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-globe text-info"></i></span>
                                        <select id="website_channel_id" name="website_channel_id" class="form-select">
                                            <option value="">-- All Website Stores / Channels --</option>
                                            <?php foreach ($websiteChannels ?? [] as $webch): ?>
                                                <option value="<?= $webch['id'] ?>" <?= (string)($staff['channel_id'] ?? '') === (string)$webch['id'] ? 'selected' : '' ?>>
                                                    <?= e($webch['name']) ?> (<?= e($webch['code']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Shop Block -->
                                <div class="dept-unit-block d-none" id="block_shop">
                                    <label for="shop_id" class="form-label fw-semibold">Assigned Retail Shop</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-shop"></i></span>
                                        <select id="shop_id" name="shop_id" class="form-select">
                                            <option value="">-- All Retail Shops --</option>
                                            <?php foreach ($shops as $sh): ?>
                                                <option value="<?= $sh['id'] ?>" <?= (string)($staff['shop_id'] ?? '') === (string)$sh['id'] ? 'selected' : '' ?>>
                                                    <?= e($sh['name']) ?> (<?= e($sh['code']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Toggle (Only on Edit) -->
                            <?php if ($isEdit): ?>
                                <div class="col-12 pt-2">
                                    <div class="form-check form-switch card p-3 bg-light border-0">
                                        <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?= ($staff['is_active'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold" for="is_active">
                                            Active Account
                                            <span class="d-block text-muted fw-normal small">Inactive users cannot log into the system and are immediately logged out if deactivated.</span>
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- 2. PERMISSIONS MATRIX & MODULE ACCESS (Dynamic Role Sync & Instant Edit) -->
                <!-- ========================================================================= -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <h5 class="card-title mb-1 fw-bold d-flex align-items-center gap-2">
                                <i class="bi bi-shield-lock-fill text-primary"></i>
                                Staff Permissions &amp; Module Access
                            </h5>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1" id="permSyncBadge">
                                    Role Sync: <span id="syncRoleName">None</span>
                                </span>
                                <span class="badge bg-light text-dark border px-2.5 py-1" id="activePermCountBadge">
                                    <span id="activePermCount">0</span> / <?= $totalPossiblePerms ?> Active Permissions
                                </span>
                            </div>
                        </div>

                        <!-- Quick Controls -->
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <!-- Module Search Input -->
                            <div class="input-group input-group-sm" style="width: 200px;">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="permModuleFilter" class="form-control bg-light border-start-0 ps-0" placeholder="Filter modules..." autocomplete="off">
                            </div>

                            <!-- Column Tools Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" id="colToolsDropdown" data-bs-toggle="dropdown">
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
                                <i class="bi bi-x-lg me-1"></i>Clear All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info" id="btnResetToRole" title="Restore permissions to the assigned role defaults">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset to Role Defaults
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="d-flex flex-column gap-3 p-3 p-md-4" id="permModulesWrapper">
                            <?php foreach ($hierarchicalModules as $modKey => $module): ?>
                                <?php
                                $submodules = $module['submodules'] ?? [];
                                $modIcon = $module['icon'] ?? 'bi-grid';
                                $modTitle = $module['title'] ?? ucfirst($modKey);
                                $modBadgeClass = $module['badge'] ?? 'bg-primary-subtle text-primary border-primary';
                                ?>
                                <div class="card border border-light-subtle rounded-3 shadow-xs perm-module-block" 
                                     data-module-key="<?= e($modKey) ?>"
                                     data-dept="<?= e($module['department'] ?? 'ALL') ?>">
                                    
                                    <!-- Module Header -->
                                    <div class="card-header bg-light bg-opacity-50 py-2.5 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi <?= $modIcon ?> text-primary fs-5"></i>
                                            <strong class="text-dark perm-module-title"><?= e($modTitle) ?></strong>
                                            <span class="badge <?= $modBadgeClass ?> border px-2 py-0.5 rounded-pill small">
                                                <?= count($submodules) ?> submodules
                                            </span>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill small perm-dept-lock-badge d-none">
                                                <i class="bi bi-lock-fill me-1"></i>Restricted
                                            </span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small perm-dept-active-badge d-none">
                                                <i class="bi bi-check-circle-fill me-1"></i>Active Scope
                                            </span>
                                        </div>
                                        <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                                            <label class="form-check-label small text-muted cursor-pointer" for="perm_mod_toggle_<?= e($modKey) ?>">
                                                Toggle Module
                                            </label>
                                            <input class="form-check-input perm-module-switch cursor-pointer" 
                                                   type="checkbox" 
                                                   id="perm_mod_toggle_<?= e($modKey) ?>" 
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
                                                    $subPerms   = $sub['permissions'] ?? [];
                                                    $subName    = $sub['name'] ?? $sub['title'] ?? ucfirst($subKey);
                                                    $subIcon    = $sub['icon'] ?? 'bi-circle';
                                                    $subDesc    = $sub['desc'] ?? $sub['description'] ?? '';
                                                    $subDept    = $sub['department'] ?? $module['department'] ?? 'ALL';

                                                    $crudColumns = Role::getCrudPermissionsByAction($subPerms);
                                                    ?>
                                                    <tr class="perm-submodule-row" data-submodule-key="<?= e($subKey) ?>" data-dept="<?= e($subDept) ?>">
                                                        <td class="ps-3 py-2">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <i class="bi <?= $subIcon ?> text-primary"></i>
                                                                <div>
                                                                    <div class="fw-semibold text-dark submodule-title"><?= e($subName) ?></div>
                                                                    <?php if (!empty($subDesc)): ?>
                                                                        <div class="text-muted small" style="font-size: 11px;"><?= e($subDesc) ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <?php foreach (['view', 'create', 'edit', 'delete'] as $crudAction): ?>
                                                            <td class="text-center">
                                                                <?php $columnPermissions = $crudColumns[$crudAction]; ?>
                                                                <?php if (!empty($columnPermissions)): ?>
                                                                    <div class="d-flex flex-column align-items-center gap-1">
                                                                        <?php foreach ($columnPermissions as $permissionAction => $permission): ?>
                                                                            <label class="form-check-label d-inline-flex align-items-center justify-content-center gap-1 small" title="<?= e($permission['description'] ?? ucfirst($permissionAction)) ?>">
                                                                                <input class="form-check-input perm-checkbox perm-<?= e($crudAction) ?>"
                                                                                       type="checkbox"
                                                                                       name="permissions[]"
                                                                                       value="<?= $permission['id'] ?>"
                                                                                       data-action="<?= e($permissionAction) ?>"
                                                                                       data-module="<?= e($modKey) ?>"
                                                                                       data-submodule="<?= e($subKey) ?>"
                                                                                       <?= in_array((int)$permission['id'], $staffPermissions) ? 'checked' : '' ?>>
                                                                                <?php if ($permissionAction !== $crudAction): ?>
                                                                                    <span class="text-capitalize"><?= e(str_replace('_', ' ', $permissionAction)) ?></span>
                                                                                <?php endif; ?>
                                                                            </label>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <span class="text-muted opacity-25">&mdash;</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endforeach; ?>

                                                        <!-- Row All -->
                                                        <td class="text-center pe-3">
                                                            <input class="form-check-input row-all-toggle cursor-pointer" 
                                                                   type="checkbox" 
                                                                   data-submodule="<?= e($subKey) ?>"
                                                                   title="Toggle all for this submodule">
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
                </div>

                <!-- 3. Form Actions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <a href="<?= url('admin/staff') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm fw-semibold">
                            <i class="bi <?= $isEdit ? 'bi-check-circle' : 'bi-person-plus-fill' ?> me-1"></i>
                            <?= $isEdit ? 'Update Staff Member & Permissions' : 'Create Staff Member & Save Permissions' ?>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // =========================================================================
    // Role Permissions Map & User Permissions Data
    // =========================================================================
    const rolePermissionsMap = <?= json_encode($rolePermissionsMap, JSON_UNESCAPED_UNICODE) ?>;
    const initialStaffPerms = <?= json_encode($staffPermissions, JSON_UNESCAPED_UNICODE) ?>;
    const isEditMode = <?= $isEdit ? 'true' : 'false' ?>;

    const toggleBtn = document.getElementById('togglePassword');
    const pwdInput = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = pwdInput.getAttribute('type') === 'password';
            pwdInput.setAttribute('type', isPassword ? 'text' : 'password');
            icon.classList.toggle('bi-eye', !isPassword);
            icon.classList.toggle('bi-eye-slash', isPassword);
        });
    }

    // =========================================================================
    // Phone Number Strict Validation & Uniqueness Check
    // =========================================================================
    const phoneInput = document.getElementById('phone');
    const phoneCounter = document.getElementById('phoneCounter');
    const phoneFeedback = document.getElementById('phoneFeedback');
    const phoneSuccess = document.getElementById('phoneSuccess');
    const phoneStatusIcon = document.getElementById('phoneStatusIcon');
    const currentStaffId = phoneInput ? (phoneInput.getAttribute('data-staff-id') || '') : '';
    let phoneCheckTimeout = null;
    let isPhoneDuplicate = false;

    function setPhoneFeedback(status, message) {
        if (!phoneFeedback || !phoneSuccess || !phoneStatusIcon || !phoneInput) return;

        phoneFeedback.classList.add('d-none');
        phoneSuccess.classList.add('d-none');
        phoneInput.classList.remove('is-invalid', 'is-valid');

        if (status === 'error') {
            phoneFeedback.textContent = message;
            phoneFeedback.classList.remove('d-none');
            phoneInput.classList.add('is-invalid');
            phoneStatusIcon.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
        } else if (status === 'success') {
            phoneSuccess.textContent = message;
            phoneSuccess.classList.remove('d-none');
            phoneInput.classList.add('is-valid');
            phoneStatusIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
        } else if (status === 'checking') {
            phoneStatusIcon.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>';
        } else {
            phoneStatusIcon.innerHTML = '<i class="bi bi-hash text-muted"></i>';
        }
    }

    function validatePhone() {
        if (!phoneInput) return true;

        const rawVal = phoneInput.value;
        const cleanedVal = rawVal.replace(/\D/g, '').slice(0, 10);
        if (rawVal !== cleanedVal) {
            phoneInput.value = cleanedVal;
        }

        const len = cleanedVal.length;
        if (phoneCounter) {
            phoneCounter.textContent = `${len} / 10 digits`;
            if (len === 10) {
                phoneCounter.className = 'badge bg-success-subtle text-success border border-success font-monospace';
            } else if (len > 0) {
                phoneCounter.className = 'badge bg-warning-subtle text-dark border border-warning font-monospace';
            } else {
                phoneCounter.className = 'badge bg-light text-muted border font-monospace';
            }
        }

        if (len === 0) {
            isPhoneDuplicate = false;
            setPhoneFeedback('neutral', '');
            return true;
        }

        if (len < 10) {
            isPhoneDuplicate = false;
            setPhoneFeedback('error', `Phone number must be exactly 10 digits (currently ${len} digits).`);
            return false;
        }

        const firstDigit = cleanedVal.charAt(0);
        if (!['6', '7', '8', '9'].includes(firstDigit)) {
            isPhoneDuplicate = false;
            setPhoneFeedback('error', 'Mobile number must start with 6, 7, 8, or 9.');
            return false;
        }

        setPhoneFeedback('checking', '');
        clearTimeout(phoneCheckTimeout);
        phoneCheckTimeout = setTimeout(() => {
            fetch(`<?= url('admin/staff/check-phone') ?>?phone=${encodeURIComponent(cleanedVal)}&except_id=${encodeURIComponent(currentStaffId)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.valid) {
                        isPhoneDuplicate = false;
                        setPhoneFeedback('error', data.message);
                    } else if (!data.available) {
                        isPhoneDuplicate = true;
                        setPhoneFeedback('error', `⚠️ Duplicate: ${data.message}`);
                    } else {
                        isPhoneDuplicate = false;
                        setPhoneFeedback('success', '✓ Phone number is valid & available.');
                    }
                })
                .catch(() => {
                    isPhoneDuplicate = false;
                    setPhoneFeedback('success', '✓ 10-digit number format valid.');
                });
        }, 300);

        return true;
    }

    if (phoneInput) {
        phoneInput.addEventListener('keypress', function (e) {
            if (['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) return;
            if (e.key < '0' || e.key > '9') {
                e.preventDefault();
                return false;
            }
            if (this.value.length >= 10 && this.selectionStart === this.selectionEnd) {
                e.preventDefault();
                return false;
            }
        });
        phoneInput.addEventListener('input', validatePhone);
        phoneInput.addEventListener('paste', () => setTimeout(validatePhone, 10));
        if (phoneInput.value) validatePhone();
    }

    // =========================================================================
    // Department & Role Auto-Choose Logic
    // =========================================================================
    const deptRadios = document.querySelectorAll('.dept-radio');
    const roleSelect = document.getElementById('role_id');
    const warningBox = document.getElementById('role_mismatch_warning');
    const warningMsg = document.getElementById('role_mismatch_msg');
    const staffForm  = document.getElementById('staffForm');

    function syncRoleAndDepartment(autoSelect = true) {
        const selectedOption = roleSelect ? roleSelect.options[roleSelect.selectedIndex] : null;
        const depts = ['PRODUCTION', 'WAREHOUSE', 'WHATSAPP', 'WEBSITE', 'SHOP'];

        if (!selectedOption || !selectedOption.value) {
            depts.forEach(d => {
                const radio = document.getElementById('dept_' + d.toLowerCase());
                const label = document.getElementById('label_dept_' + d.toLowerCase());
                const badge = label?.querySelector('.dept-badge');
                if (radio) radio.disabled = false;
                if (label) label.classList.remove('dept-card-disabled');
                if (badge) badge.classList.add('d-none');
            });
            updateFacilityBlocks();
            if (warningBox) warningBox.classList.add('d-none');
            return;
        }

        const rolePrimaryDept = (selectedOption.getAttribute('data-primary-dept') || 'ALL').toUpperCase();
        let roleDepts = [];
        try {
            roleDepts = JSON.parse(selectedOption.getAttribute('data-departments') || '[]').map(d => d.toUpperCase());
        } catch (e) {}

        let allowedDepts = [];
        if (rolePrimaryDept === 'ALL' || (roleDepts.length === 0 && rolePrimaryDept === 'ALL')) {
            allowedDepts = ['PRODUCTION', 'WAREHOUSE', 'WHATSAPP', 'WEBSITE', 'SHOP'];
        } else if (roleDepts.length > 0) {
            allowedDepts = roleDepts.filter(d => depts.includes(d));
            if (allowedDepts.length === 0 && depts.includes(rolePrimaryDept)) {
                allowedDepts = [rolePrimaryDept];
            }
        } else if (depts.includes(rolePrimaryDept)) {
            allowedDepts = [rolePrimaryDept];
        } else {
            allowedDepts = ['PRODUCTION', 'WAREHOUSE', 'WHATSAPP', 'WEBSITE', 'SHOP'];
        }

        let currentlyChecked = document.querySelector('.dept-radio:checked')?.value;
        let targetDept = currentlyChecked;
        if (!allowedDepts.includes(currentlyChecked) || (allowedDepts.length === 1 && autoSelect)) {
            targetDept = allowedDepts.includes(rolePrimaryDept) ? rolePrimaryDept : allowedDepts[0];
        }

        depts.forEach(d => {
            const radio = document.getElementById('dept_' + d.toLowerCase());
            const label = document.getElementById('label_dept_' + d.toLowerCase());
            const badge = label?.querySelector('.dept-badge');
            const isAllowed = allowedDepts.includes(d);

            if (radio) {
                if (d === targetDept && isAllowed) {
                    radio.checked = true;
                    radio.disabled = false;
                } else {
                    radio.disabled = !isAllowed;
                }
            }

            if (label) {
                if (isAllowed) {
                    label.classList.remove('dept-card-disabled');
                    if (badge) {
                        if (allowedDepts.length === 1 && d === targetDept) {
                            badge.innerHTML = '<i class="bi bi-shield-check me-1"></i>Auto Selected';
                            badge.className = 'dept-badge badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small';
                            badge.classList.remove('d-none');
                        } else {
                            badge.classList.add('d-none');
                        }
                    }
                } else {
                    label.classList.add('dept-card-disabled');
                    if (badge) {
                        badge.innerHTML = '<i class="bi bi-slash-circle me-1"></i>Disabled for Role';
                        badge.className = 'dept-badge badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill small';
                        badge.classList.remove('d-none');
                    }
                }
            }
        });

        updateFacilityBlocks();
        syncPermissionsScopeWithDepartment(targetDept);
        if (warningBox) warningBox.classList.add('d-none');
    }

    function updateFacilityBlocks() {
        const selectedDept = document.querySelector('.dept-radio:checked')?.value || 'PRODUCTION';
        
        document.getElementById('block_production')?.classList.toggle('d-none', selectedDept !== 'PRODUCTION');
        document.getElementById('block_warehouse')?.classList.toggle('d-none', selectedDept !== 'WAREHOUSE');
        document.getElementById('block_whatsapp')?.classList.toggle('d-none', selectedDept !== 'WHATSAPP');
        document.getElementById('block_website')?.classList.toggle('d-none', selectedDept !== 'WEBSITE');
        document.getElementById('block_shop')?.classList.toggle('d-none', selectedDept !== 'SHOP');
    }

    deptRadios.forEach(radio => radio.addEventListener('change', function () {
        updateFacilityBlocks();
        syncPermissionsScopeWithDepartment(this.value);
    }));

    // =========================================================================
    // Dynamic Permissions Matrix Synchronization & Scoped Editing
    // =========================================================================
    const allPermCheckboxes = document.querySelectorAll('.perm-checkbox');
    const activePermCountEl = document.getElementById('activePermCount');
    const permSyncBadge = document.getElementById('permSyncBadge');
    const syncRoleName = document.getElementById('syncRoleName');
    const permModuleFilter = document.getElementById('permModuleFilter');
    const permModuleBlocks = document.querySelectorAll('.perm-module-block');
    const btnResetToRole = document.getElementById('btnResetToRole');

    function syncPermissionsScopeWithDepartment(selectedDept) {
        if (!selectedDept) {
            selectedDept = document.querySelector('.dept-radio:checked')?.value || 'PRODUCTION';
        }
        selectedDept = selectedDept.toUpperCase();

        const operationalDepts = ['PRODUCTION', 'WAREHOUSE', 'WHATSAPP', 'WEBSITE', 'SHOP'];

        permModuleBlocks.forEach(block => {
            const blockDept = (block.getAttribute('data-dept') || 'ALL').toUpperCase();
            const modSwitch = block.querySelector('.perm-module-switch');
            const lockBadge = block.querySelector('.perm-dept-lock-badge');
            const activeBadge = block.querySelector('.perm-dept-active-badge');
            const subRows = block.querySelectorAll('.perm-submodule-row');

            const isOperationalBlock = operationalDepts.includes(blockDept);
            const isBlockMismatched = isOperationalBlock && (selectedDept !== 'ALL' && selectedDept !== 'GENERAL') && (blockDept !== selectedDept);
            const isBlockActive = isOperationalBlock && (selectedDept === blockDept);

            if (isBlockMismatched) {
                block.classList.add('d-none');
                block.classList.add('perm-block-disabled');
                block.classList.remove('border-info', 'border-success', 'border-primary');
                if (lockBadge) {
                    lockBadge.innerHTML = `<i class="bi bi-lock-fill me-1"></i>Restricted to ${blockDept}`;
                    lockBadge.classList.remove('d-none');
                }
                if (activeBadge) activeBadge.classList.add('d-none');

                if (modSwitch) {
                    modSwitch.checked = false;
                    modSwitch.disabled = true;
                }

                // Uncheck and disable all checkboxes inside this block
                block.querySelectorAll('.perm-checkbox, .row-all-toggle').forEach(cb => {
                    cb.checked = false;
                    cb.disabled = true;
                });
            } else {
                // ENABLED / ACTIVE / COMMON
                block.classList.remove('d-none');
                block.classList.remove('perm-block-disabled');
                if (lockBadge) lockBadge.classList.add('d-none');

                if (isBlockActive) {
                    if (activeBadge) {
                        activeBadge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Active (${selectedDept})`;
                        activeBadge.classList.remove('d-none');
                    }
                } else {
                    if (activeBadge) activeBadge.classList.add('d-none');
                }

                if (modSwitch) {
                    modSwitch.disabled = false;
                }

                // Check submodule-level department restrictions (e.g. inside Reports)
                subRows.forEach(row => {
                    const rowDepts = (row.getAttribute('data-dept') || 'ALL')
                        .split(',')
                        .map(dept => dept.trim().toUpperCase());
                    const isSubOp = rowDepts.some(dept => operationalDepts.includes(dept));
                    const isSubMismatched = isSubOp &&
                        (selectedDept !== 'ALL' && selectedDept !== 'GENERAL') &&
                        !rowDepts.includes(selectedDept);

                    const rowCbs = row.querySelectorAll('.perm-checkbox, .row-all-toggle');
                    if (isSubMismatched) {
                        row.classList.add('d-none');
                        row.classList.add('perm-row-disabled');
                        rowCbs.forEach(cb => {
                            cb.checked = false;
                            cb.disabled = true;
                        });
                    } else {
                        row.classList.remove('d-none');
                        row.classList.remove('perm-row-disabled');
                        rowCbs.forEach(cb => {
                            cb.disabled = false;
                        });
                    }
                });
            }
        });

        updateActiveCounterAndSwitches();
    }

    function updateActiveCounterAndSwitches() {
        let activeCount = 0;
        allPermCheckboxes.forEach(cb => {
            if (cb.checked && !cb.disabled) activeCount++;
        });
        if (activePermCountEl) activePermCountEl.textContent = activeCount;

        // Sync each row-all toggle
        document.querySelectorAll('.row-all-toggle').forEach(rowToggle => {
            const row = rowToggle.closest('.perm-submodule-row');
            if (row) {
                const cbs = row.querySelectorAll('.perm-checkbox:not(:disabled)');
                const allChecked = cbs.length > 0 && Array.from(cbs).every(c => c.checked);
                rowToggle.checked = allChecked;
            }
        });

        // Sync each module switch
        document.querySelectorAll('.perm-module-switch').forEach(modSwitch => {
            const card = modSwitch.closest('.perm-module-block');
            if (card) {
                const cbs = card.querySelectorAll('.perm-checkbox:not(:disabled)');
                const allChecked = cbs.length > 0 && Array.from(cbs).every(c => c.checked);
                modSwitch.checked = allChecked;
            }
        });
    }

    function applyRolePermissions(roleId, isManualOverride = false) {
        const roleOption = roleSelect ? roleSelect.options[roleSelect.selectedIndex] : null;
        const roleName = roleOption && roleOption.value ? roleOption.getAttribute('data-name') : 'None';
        
        if (syncRoleName) syncRoleName.textContent = roleName;

        if (!roleId || !rolePermissionsMap[roleId]) {
            if (!isEditMode || !isManualOverride) {
                allPermCheckboxes.forEach(cb => {
                    if (!cb.disabled) cb.checked = false;
                });
            }
            if (permSyncBadge) {
                permSyncBadge.className = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1';
                permSyncBadge.innerHTML = `Role Sync: <span id="syncRoleName">${roleName}</span> (No Role)`;
            }
            updateActiveCounterAndSwitches();
            return;
        }

        const allowedPids = (rolePermissionsMap[roleId] || []).map(Number);
        allPermCheckboxes.forEach(cb => {
            if (!cb.disabled) {
                cb.checked = allowedPids.includes(Number(cb.value));
            } else {
                cb.checked = false;
            }
        });

        if (permSyncBadge) {
            permSyncBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1';
            permSyncBadge.innerHTML = `<i class="bi bi-shield-check me-1"></i>Role Defaults: <strong>${roleName}</strong>`;
        }

        updateActiveCounterAndSwitches();
    }

    // When role changes -> auto sync department AND auto load role's permissions!
    roleSelect?.addEventListener('change', function () {
        syncRoleAndDepartment(true);
        const selectedRoleId = this.value;
        applyRolePermissions(selectedRoleId, false);
    });

    // Reset to role defaults button
    btnResetToRole?.addEventListener('click', function () {
        const selectedRoleId = roleSelect ? roleSelect.value : '';
        if (!selectedRoleId) {
            alert('Please select a system role first.');
            return;
        }
        applyRolePermissions(selectedRoleId, false);
    });

    // When individual permission checkbox is clicked -> mark as customized
    allPermCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            updateActiveCounterAndSwitches();
            const roleOption = roleSelect ? roleSelect.options[roleSelect.selectedIndex] : null;
            const roleName = roleOption && roleOption.value ? roleOption.getAttribute('data-name') : 'Custom';
            if (permSyncBadge) {
                permSyncBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1';
                permSyncBadge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Customized: <strong>${roleName}</strong> (Modified)`;
            }
        });
    });

    // Row-all toggles
    document.querySelectorAll('.row-all-toggle').forEach(toggle => {
        toggle.addEventListener('change', function () {
            const row = this.closest('.perm-submodule-row');
            if (row) {
                row.querySelectorAll('.perm-checkbox:not(:disabled)').forEach(cb => cb.checked = this.checked);
                updateActiveCounterAndSwitches();
                if (permSyncBadge) {
                    permSyncBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1';
                    permSyncBadge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Customized (Modified)`;
                }
            }
        });
    });

    // Module-wide switch toggles
    document.querySelectorAll('.perm-module-switch').forEach(modSwitch => {
        modSwitch.addEventListener('change', function () {
            const card = this.closest('.perm-module-block');
            if (card) {
                card.querySelectorAll('.perm-checkbox:not(:disabled)').forEach(cb => cb.checked = this.checked);
                updateActiveCounterAndSwitches();
                if (permSyncBadge) {
                    permSyncBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1';
                    permSyncBadge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Customized (Modified)`;
                }
            }
        });
    });

    // Column Tools
    function toggleColumn(actionType, checkState) {
        document.querySelectorAll(`.perm-checkbox.perm-${actionType}:not(:disabled)`).forEach(cb => cb.checked = checkState);
        updateActiveCounterAndSwitches();
        if (permSyncBadge) {
            permSyncBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1';
            permSyncBadge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Customized (Batch Column)`;
        }
    }

    document.getElementById('btnSelectAllView')?.addEventListener('click', (e) => { e.preventDefault(); toggleColumn('view', true); });
    document.getElementById('btnSelectAllCreate')?.addEventListener('click', (e) => { e.preventDefault(); toggleColumn('create', true); });
    document.getElementById('btnSelectAllEdit')?.addEventListener('click', (e) => { e.preventDefault(); toggleColumn('edit', true); });
    document.getElementById('btnSelectAllDelete')?.addEventListener('click', (e) => { e.preventDefault(); toggleColumn('delete', true); });
    document.getElementById('btnDeselectAllView')?.addEventListener('click', (e) => { e.preventDefault(); toggleColumn('view', false); });
    document.getElementById('btnDeselectAllCreate')?.addEventListener('click', (e) => { e.preventDefault(); toggleColumn('create', false); });
    document.getElementById('btnDeselectAllEdit')?.addEventListener('click', (e) => { e.preventDefault(); toggleColumn('edit', false); });
    document.getElementById('btnDeselectAllDelete')?.addEventListener('click', (e) => { e.preventDefault(); toggleColumn('delete', false); });

    // Global Select All / Clear All
    document.getElementById('btnSelectAll')?.addEventListener('click', function () {
        allPermCheckboxes.forEach(cb => {
            if (!cb.disabled) cb.checked = true;
        });
        updateActiveCounterAndSwitches();
        if (permSyncBadge) {
            permSyncBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1';
            permSyncBadge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Customized (All Selected)`;
        }
    });

    document.getElementById('btnDeselectAll')?.addEventListener('click', function () {
        allPermCheckboxes.forEach(cb => {
            if (!cb.disabled) cb.checked = false;
        });
        updateActiveCounterAndSwitches();
        if (permSyncBadge) {
            permSyncBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1';
            permSyncBadge.innerHTML = `<i class="bi bi-pencil-square me-1"></i>Customized (All Cleared)`;
        }
    });

    // Live Module Search Filter
    permModuleFilter?.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        permModuleBlocks.forEach(block => {
            const title = block.querySelector('.perm-module-title')?.textContent.toLowerCase() || '';
            const rows = block.querySelectorAll('.perm-submodule-row');
            let hasVisibleRow = false;

            rows.forEach(row => {
                const subTitle = row.querySelector('.fw-semibold')?.textContent.toLowerCase() || '';
                const match = query === '' || title.includes(query) || subTitle.includes(query);
                row.style.display = match ? '' : 'none';
                if (match) hasVisibleRow = true;
            });

            block.style.display = (query === '' || hasVisibleRow) ? '' : 'none';
        });
    });

    // Form submit guard
    staffForm?.addEventListener('submit', function (e) {
        const checkedRadio = document.querySelector('.dept-radio:checked');
        if (checkedRadio) {
            checkedRadio.disabled = false;
        }

        if (phoneInput && phoneInput.value.trim() !== '') {
            const phoneVal = phoneInput.value.replace(/\D/g, '');
            if (phoneVal.length !== 10) {
                e.preventDefault();
                alert(`⚠️ Invalid Phone Number:\n\nPhone number must be exactly 10 digits. Currently it has ${phoneVal.length} digits.`);
                phoneInput.focus();
                return false;
            }
            if (!['6', '7', '8', '9'].includes(phoneVal.charAt(0))) {
                e.preventDefault();
                alert('⚠️ Invalid Phone Number:\n\nIndian mobile numbers must start with 6, 7, 8, or 9.');
                phoneInput.focus();
                return false;
            }
            if (isPhoneDuplicate) {
                e.preventDefault();
                alert('⚠️ Duplicate Phone Number:\n\nThis phone number is already registered to another staff member. Please use a unique phone number.');
                phoneInput.focus();
                return false;
            }
        }
    });

    // =========================================================================
    // Initial Load Setup
    // =========================================================================
    syncRoleAndDepartment(false);

    if (isEditMode) {
        // In edit mode: if staff has custom permissions or role permissions
        updateActiveCounterAndSwitches();
        const roleOption = roleSelect ? roleSelect.options[roleSelect.selectedIndex] : null;
        const roleName = roleOption && roleOption.value ? roleOption.getAttribute('data-name') : 'Assigned Role';
        if (permSyncBadge) {
            permSyncBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1';
            permSyncBadge.innerHTML = `<i class="bi bi-shield-check me-1"></i>Active Permissions: <strong>${roleName}</strong>`;
        }
    } else {
        // In create mode: if role already selected (e.g. from old input), sync permissions
        if (roleSelect && roleSelect.value) {
            applyRolePermissions(roleSelect.value, false);
        } else {
            updateActiveCounterAndSwitches();
        }
    }
});
</script>

<style>
.dept-card {
    transition: all 0.25s ease-in-out;
    cursor: pointer;
}
.dept-card-disabled {
    opacity: 0.4 !important;
    filter: grayscale(0.85);
    cursor: not-allowed !important;
    pointer-events: none !important;
    background-color: #f8f9fa !important;
    border-color: #dee2e6 !important;
}
.perm-block-disabled {
    opacity: 0.45 !important;
    filter: grayscale(0.7);
    background-color: #f8f9fa !important;
    border-color: #e2e8f0 !important;
}
.perm-block-disabled .table {
    pointer-events: none !important;
}
.perm-row-disabled {
    opacity: 0.35 !important;
    filter: grayscale(0.8);
    background-color: #f8f9fa !important;
    pointer-events: none !important;
}
.permissions-table th, .permissions-table td {
    vertical-align: middle;
}
.cursor-pointer {
    cursor: pointer;
}
</style>
