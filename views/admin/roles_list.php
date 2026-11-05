<?php
/**
 * Roles & Permissions Directory View
 */
?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 fw-bold">Roles & Permissions</h1>
        </div>
        <div>
            <?php if (hasPermission('roles', 'create')): ?>
            <a href="<?= url('admin/roles/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-circle"></i>
                <span>Add New Role</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($roles)): ?>
    <!-- Roles Table Section -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0 fw-bold">Custom Roles</h5>
            <span class="badge bg-light text-dark border"><?= count($roles) ?> Total Roles</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Role Name</th>
                        <th>Identifier (Slug)</th>
                        <th>Description</th>
                        <th>Assigned Users</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $r): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= e($r['name']) ?></div>
                            </td>
                            <td>
                                <code class="text-primary"><?= e($r['slug']) ?></code>
                            </td>
                            <td>
                                <span class="text-muted small"><?= e($r['description'] ?: '—') ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    <i class="bi bi-person me-1"></i><?= (int)$r['user_count'] ?> Users
                                </span>
                            </td>
                            <td>
                                <?php if ($r['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <?php if (hasPermission('roles', 'edit')): ?>
                                    <a href="<?= url('admin/roles/permissions/' . $r['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-key me-1"></i> Permissions
                                    </a>
                                    <a href="<?= url('admin/roles/edit/' . $r['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <?php endif; ?>
                                    <?php if (hasPermission('roles', 'delete')): ?>
                                    <form method="POST" action="<?= url('admin/roles/delete/' . $r['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete the <?= e(addslashes($r['name'])) ?> role?');">
                                        <?= CSRF::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Role">
                                            <i class="bi bi-trash"></i> Delete
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
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center p-3 mb-3 text-primary" style="width: 64px; height: 64px;">
                <i class="bi bi-shield-lock fs-2"></i>
            </div>
            <h5 class="fw-bold mb-1">No Custom Staff Roles Found</h5>
            <p class="text-muted mb-3">There are currently no custom roles created. You can create custom roles with tailored module permissions.</p>
            <?php if (hasPermission('roles', 'create')): ?>
            <a href="<?= url('admin/roles/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-circle"></i>
                <span>Add New Role</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
