<div class="page-header mb-4">
    <h2><i class="bi bi-person-plus me-2 text-primary"></i>Add Supplier</h2>
    <span class="text-muted">Register the supplier's contact and business details. Record goods and invoices under Purchases.</span>
</div>

<form method="POST" action="<?= url('production/sellers/store') ?>" class="card shadow-sm border">
    <?= CSRF::field() ?>
    <input type="hidden" name="supplier_profile_only" value="1">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold"><i class="bi bi-person-vcard me-2 text-primary"></i>Supplier Profile &amp; Business Details</h5>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label required">Contact Person / Supplier Name *</label>
                <input type="text" name="name" class="form-control text-uppercase" required minlength="2" maxlength="200" value="<?= e(old('name')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label required">Company / Mill / Business Name *</label>
                <input type="text" name="company" class="form-control text-uppercase" required minlength="2" maxlength="300" value="<?= e(old('company')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label required">Phone Number *</label>
                <input type="tel" name="phone" class="form-control" required pattern="[0-9]{10}" maxlength="10" inputmode="numeric" value="<?= e(old('phone')) ?>">
                <small class="text-muted">Enter a 10-digit contact number.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email Address (Optional)</label>
                <input type="email" name="email" class="form-control" maxlength="255" value="<?= e(old('email')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">GSTIN / Tax ID</label>
                <input type="text" name="gst_number" class="form-control text-uppercase" maxlength="15" value="<?= e(old('gst_number')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label required">City / District *</label>
                <input type="text" name="city" class="form-control text-uppercase" required maxlength="100" value="<?= e(old('city')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label required">State *</label>
                <input type="text" name="state" class="form-control text-uppercase" required maxlength="100" value="<?= e(old('state')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label required">Full Business Address *</label>
                <textarea name="address" class="form-control text-uppercase" required maxlength="500" rows="2"><?= e(old('address')) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Payment Terms (Optional)</label>
                <input type="text" name="payment_terms" class="form-control text-uppercase" maxlength="200" value="<?= e(old('payment_terms')) ?>" placeholder="e.g. NET 30">
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between">
        <a href="<?= url('production/sellers') ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Supplier</button>
    </div>
</form>
