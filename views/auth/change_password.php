<div class="page-header">
    <h2>Change Password</h2>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="form-card">
            <form action="<?= url('admin/change-password') ?>" method="POST" class="needs-validation" novalidate>
                <?= CSRF::tokenField() ?>
                
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    <div class="form-text">Minimum 6 characters.</div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-key me-2"></i>Update Password
                </button>
            </form>
        </div>
    </div>
</div>
