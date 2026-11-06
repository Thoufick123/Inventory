<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div><h2><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Supplier Details</h2><span class="text-muted">Purchase records and fabric roll inventory are managed separately in Purchases.</span></div>
    <a href="<?= url('production/sellers') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Suppliers</a>
</div>
<form method="POST" action="<?= url('production/sellers/update/' . $seller['id']) ?>" class="card shadow-sm border">
    <?= CSRF::field() ?>
    <input type="hidden" name="supplier_profile_only" value="1">
    <div class="card-header bg-white"><h5 class="mb-0 fw-bold"><?= e($seller['company'] ?: $seller['name']) ?></h5></div>
    <div class="card-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label required">Contact Person / Supplier Name *</label><input name="name" class="form-control text-uppercase" required minlength="2" maxlength="200" value="<?= e($seller['name']) ?>"></div>
        <div class="col-md-6"><label class="form-label required">Company / Mill / Business Name *</label><input name="company" class="form-control text-uppercase" required minlength="2" maxlength="300" value="<?= e($seller['company']) ?>"></div>
        <div class="col-md-6"><label class="form-label required">Phone Number *</label><input type="tel" name="phone" class="form-control" required pattern="[0-9]{10}" maxlength="10" inputmode="numeric" value="<?= e($seller['phone']) ?>"></div>
        <div class="col-md-6"><label class="form-label">Email Address (Optional)</label><input type="email" name="email" class="form-control" maxlength="255" value="<?= e($seller['email']) ?>"></div>
        <div class="col-md-6"><label class="form-label">GSTIN / Tax ID</label><input name="gst_number" class="form-control text-uppercase" maxlength="15" value="<?= e($seller['gst_number']) ?>"></div>
        <div class="col-md-6"><label class="form-label required">City / District *</label><input name="city" class="form-control text-uppercase" required maxlength="100" value="<?= e($seller['city']) ?>"></div>
        <div class="col-md-6"><label class="form-label required">State *</label><input name="state" class="form-control text-uppercase" required maxlength="100" value="<?= e($seller['state']) ?>"></div>
        <div class="col-md-6"><label class="form-label required">Full Business Address *</label><textarea name="address" class="form-control text-uppercase" required maxlength="500" rows="2"><?= e($seller['address']) ?></textarea></div>
        <div class="col-md-6"><label class="form-label">Payment Terms (Optional)</label><input name="payment_terms" class="form-control text-uppercase" maxlength="200" value="<?= e($seller['payment_terms']) ?>"></div>
        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="supplierActive" <?= !empty($seller['is_active']) ? 'checked' : '' ?>><label class="form-check-label" for="supplierActive">Active supplier (available in new purchases)</label></div></div>
    </div></div>
    <div class="card-footer bg-white d-flex justify-content-between">
        <a href="<?= url('production/sellers') ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Changes</button>
    </div>
</form>
