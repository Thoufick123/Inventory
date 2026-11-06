<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-truck-flatbed me-2 text-primary"></i>Production Suppliers</h2>
        <span class="text-muted">Manage supplier contact and business details here. Record fabric and other purchases separately.</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('production/purchases') ?>" class="btn btn-outline-primary"><i class="bi bi-bag-check me-1"></i>Purchases</a>
        <?php if (hasPermission('sellers', 'create')): ?>
            <a href="<?= url('production/sellers/create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Supplier</a>
        <?php endif; ?>
    </div>
</div>

<div class="card border shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('production/sellers') ?>" class="row g-3 align-items-end">
            <div class="col-lg-5 col-md-6">
                <label class="form-label" for="supplierSearch">Search supplier details</label>
                <input type="search" class="form-control" id="supplierSearch" name="search" value="<?= e($search) ?>" placeholder="Name, company, phone, address or GSTIN">
            </div>
            <div class="col-lg-3 col-md-3">
                <label class="form-label" for="supplierCity">City</label>
                <select class="form-select" id="supplierCity" name="city">
                    <option value="">All cities</option>
                    <?php foreach ($cities as $cityOption): ?>
                        <?php if (!empty($cityOption['city'])): ?>
                            <option value="<?= e($cityOption['city']) ?>" <?= $city === $cityOption['city'] ? 'selected' : '' ?>><?= e($cityOption['city']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label" for="supplierStatus">Status</label>
                <select class="form-select" id="supplierStatus" name="status">
                    <option value="">All statuses</option>
                    <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i>Filter</button>
                <?php if ($search !== '' || $city !== '' || $status !== ''): ?>
                    <a href="<?= url('production/sellers') ?>" class="btn btn-outline-secondary" aria-label="Clear filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-person-vcard me-2 text-primary"></i>Supplier Profiles</h5>
        <span class="badge bg-primary-subtle text-primary"><?= number_format(count($sellers)) ?> suppliers</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Supplier / Contact</th><th>Company</th><th>Contact Details</th><th>Location</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php if (empty($sellers)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No suppliers match these filters.
                        <?php if (hasPermission('sellers', 'create')): ?>
                            <div class="mt-3"><a href="<?= url('production/sellers/create') ?>" class="btn btn-sm btn-primary">Add Supplier</a></div>
                        <?php endif; ?>
                    </td></tr>
                <?php else: foreach ($sellers as $seller): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($seller['name']) ?></td>
                        <td><?= e($seller['company'] ?: '—') ?></td>
                        <td>
                            <div><?= e($seller['phone']) ?></div>
                            <?php if (!empty($seller['email'])): ?><small class="text-muted"><?= e($seller['email']) ?></small><?php endif; ?>
                        </td>
                        <td>
                            <?= e($seller['city'] ?: '—') ?><?= !empty($seller['state']) ? ', ' . e($seller['state']) : '' ?>
                            <?php if (!empty($seller['gst_number'])): ?><small class="d-block text-muted">GSTIN: <?= e($seller['gst_number']) ?></small><?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($seller['is_active'])): ?>
                                <span class="badge bg-success-subtle text-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <?php if (hasPermission('sellers', 'view')): ?>
                                <a href="<?= url('production/sellers/view/' . $seller['id']) ?>" class="btn btn-sm btn-outline-secondary" title="View supplier"><i class="bi bi-eye"></i></a>
                            <?php endif; ?>
                            <?php if (hasPermission('sellers', 'edit')): ?>
                                <a href="<?= url('production/sellers/edit/' . $seller['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit supplier"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                            <?php if (hasPermission('sellers', 'delete')): ?>
                                <form method="POST" action="<?= url('production/sellers/delete/' . $seller['id']) ?>" class="d-inline" onsubmit="return confirm('Delete this supplier? Suppliers with purchase history cannot be deleted.');">
                                    <?= CSRF::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete supplier"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
