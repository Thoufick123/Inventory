<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-person-vcard me-2 text-primary"></i><?= e($seller['name']) ?></h2>
        <span class="text-muted"><?= e($seller['company']) ?></span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('production/sellers') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Suppliers</a>
        <?php if (hasPermission('sellers', 'edit')): ?><a href="<?= url('production/sellers/edit/' . $seller['id']) ?>" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Edit Supplier</a><?php endif; ?>
        <?php if (hasPermission('sellers', 'create')): ?><a href="<?= url('production/purchases/create') ?>" class="btn btn-outline-primary"><i class="bi bi-bag-plus me-1"></i>Record Purchase</a><?php endif; ?>
    </div>
</div>
<div class="card shadow-sm border">
    <div class="card-header bg-white d-flex justify-content-between align-items-center"><h5 class="mb-0 fw-bold">Supplier Business Details</h5><span class="badge <?= !empty($seller['is_active']) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>"><?= !empty($seller['is_active']) ? 'Active' : 'Inactive' ?></span></div>
    <div class="card-body"><div class="row g-4">
        <div class="col-md-6"><small class="text-muted d-block">Contact Person</small><strong><?= e($seller['name']) ?></strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Company</small><strong><?= e($seller['company'] ?: '—') ?></strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Phone</small><strong><?= e($seller['phone'] ?: '—') ?></strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Email</small><strong><?= e($seller['email'] ?: '—') ?></strong></div>
        <div class="col-md-6"><small class="text-muted d-block">GSTIN / Tax ID</small><strong><?= e($seller['gst_number'] ?: '—') ?></strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Location</small><strong><?= e(trim(($seller['city'] ?? '') . ', ' . ($seller['state'] ?? ''), ', ') ?: '—') ?></strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Business Address</small><strong><?= e($seller['address'] ?: '—') ?></strong></div>
        <div class="col-md-6"><small class="text-muted d-block">Payment Terms</small><strong><?= e($seller['payment_terms'] ?: '—') ?></strong></div>
    </div></div>
</div>
