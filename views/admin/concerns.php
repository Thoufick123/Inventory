<?php
$isEditingConcern = !empty($selectedConcern);
$formAction = $isEditingConcern
    ? url('admin/staff/concerns/update/' . (int)$selectedConcern['id'])
    : url('admin/staff/concerns/store');
$selectedModuleKeys = array_fill_keys($selectedConcern['module_keys'] ?? [], true);
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">Staff Concerns &amp; Modules</h1>
            <p class="text-muted mb-0">Create custom concerns, choose their modules, and assign staff to one or more concerns. A Main Production Unit is created automatically when production modules are selected and the concern has no production unit.</p>
        </div>
        <?php if (hasPermission('staff', 'create')): ?>
            <a class="btn btn-outline-primary" href="<?= url('admin/staff/concerns') ?>"><i class="bi bi-plus-lg me-1"></i>New Concern</a>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold"><?= $isEditingConcern ? 'Edit Concern' : 'Create Concern' ?></div>
                <div class="card-body">
                    <?php if ($isEditingConcern && !hasPermission('staff', 'edit')): ?>
                        <div class="alert alert-warning">You do not have permission to edit concerns.</div>
                    <?php elseif (!$isEditingConcern && !hasPermission('staff', 'create')): ?>
                        <div class="alert alert-warning">You do not have permission to create concerns.</div>
                    <?php else: ?>
                        <form method="POST" action="<?= $formAction ?>">
                            <?= CSRF::field() ?>
                            <div class="mb-3">
                                <label for="concernName" class="form-label fw-semibold">Concern name <span class="text-danger">*</span></label>
                                <input id="concernName" name="name" class="form-control" maxlength="100" required value="<?= e($selectedConcern['name'] ?? '') ?>" placeholder="e.g. Production Operations">
                            </div>
                            <div class="mb-3">
                                <label for="concernDescription" class="form-label fw-semibold">Description</label>
                                <textarea id="concernDescription" name="description" class="form-control" rows="2" maxlength="500"><?= e($selectedConcern['description'] ?? '') ?></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <label class="form-label fw-semibold mb-0">Modules <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" type="button" id="selectAllConcernModules">Select all</button>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" id="clearConcernModules">Clear all</button>
                                </div>
                            </div>
                            <div id="concernModuleList" class="border rounded-3 p-3" style="max-height: 55vh; overflow-y: auto;">
                                <?php foreach ($hierarchicalModules as $module): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="fw-semibold small text-uppercase text-muted"><?= e($module['title'] ?? 'Modules') ?></div>
                                            <button class="btn btn-sm btn-link p-0 text-decoration-none concern-module-group-toggle" type="button">Select group</button>
                                        </div>
                                        <?php foreach ($module['submodules'] ?? [] as $moduleKey => $submodule): ?>
                                            <?php if (empty($submodule['permissions'])) continue; ?>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input concern-module-checkbox" type="checkbox" name="module_keys[]" id="module_<?= e($moduleKey) ?>" value="<?= e($moduleKey) ?>" <?= isset($selectedModuleKeys[$moduleKey]) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="module_<?= e($moduleKey) ?>"><?= e($submodule['name'] ?? ucfirst($moduleKey)) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i><?= $isEditingConcern ? 'Save Changes' : 'Create Concern' ?></button>
                                <?php if ($isEditingConcern): ?><a class="btn btn-outline-secondary" href="<?= url('admin/staff/concerns') ?>">Cancel</a><?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <script>
            (() => {
                const moduleList = document.getElementById('concernModuleList');
                if (!moduleList) return;

                const moduleCheckboxes = () => Array.from(
                    moduleList.querySelectorAll('.concern-module-checkbox')
                );

                document.getElementById('selectAllConcernModules')?.addEventListener('click', () => {
                    moduleCheckboxes().forEach((checkbox) => { checkbox.checked = true; });
                });

                document.getElementById('clearConcernModules')?.addEventListener('click', () => {
                    moduleCheckboxes().forEach((checkbox) => { checkbox.checked = false; });
                });

                moduleList.querySelectorAll('.concern-module-group-toggle').forEach((button) => {
                    button.addEventListener('click', () => {
                        const group = button.closest('.mb-3');
                        const checkboxes = Array.from(group.querySelectorAll('.concern-module-checkbox'));
                        const selectGroup = checkboxes.some((checkbox) => !checkbox.checked);
                        checkboxes.forEach((checkbox) => { checkbox.checked = selectGroup; });
                    });
                });
            })();
            </script>
        </div>

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Configured Concerns <span class="badge bg-light text-dark border ms-1"><?= count($concerns) ?></span></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th class="ps-3">Concern</th><th>Modules</th><th class="text-end pe-3">Actions</th></tr></thead>
                        <tbody>
                        <?php if (!$concerns): ?>
                            <tr><td colspan="3" class="text-center text-muted py-5">No concerns yet. Create one and assign its modules.</td></tr>
                        <?php else: foreach ($concerns as $concern): ?>
                            <tr>
                                <td class="ps-3">
                                    <strong><?= e($concern['name']) ?></strong>
                                    <?php if (!empty($concern['is_active'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border ms-1">Inactive</span>
                                    <?php endif; ?>
                                    <?php if (!empty($concern['description'])): ?><small class="d-block text-muted"><?= e($concern['description']) ?></small><?php endif; ?>
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary"><?= count($concern['module_keys']) ?> modules</span></td>
                                <td class="text-end pe-3 text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/staff/concerns?concern_id=' . (int)$concern['id']) ?>"><i class="bi bi-pencil"></i> Edit</a>
                                    <?php if (hasPermission('staff', 'edit') && (int)$concern['id'] !== 1): ?>
                                        <form method="POST" action="<?= url('admin/staff/concerns/toggle/' . (int)$concern['id']) ?>" class="d-inline" onsubmit="return confirm('<?= !empty($concern['is_active']) ? 'Deactivate this concern? Staff will not be able to select or transact on it.' : 'Reactivate this concern?' ?>')">
                                            <?= CSRF::field() ?>
                                            <button class="btn btn-sm btn-outline-<?= !empty($concern['is_active']) ? 'warning' : 'success' ?>" type="submit" title="<?= !empty($concern['is_active']) ? 'Deactivate Concern' : 'Activate Concern' ?>">
                                                <i class="bi bi-<?= !empty($concern['is_active']) ? 'pause-circle' : 'play-circle' ?>"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (hasPermission('staff', 'delete') && (int)$concern['id'] !== 1): ?>
                                        <form method="POST" action="<?= url('admin/staff/concerns/delete/' . (int)$concern['id']) ?>" class="d-inline" onsubmit="return confirm('Delete this unused concern? Concerns with existing batches, stock, or transactions cannot be deleted and should be deactivated.')">
                                            <?= CSRF::field() ?>
                                            <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete Concern"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="alert alert-info mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Role permissions are edited one concern at a time. Saving a concern keeps permissions for modules outside that concern unchanged.</div>
        </div>
    </div>
</div>
