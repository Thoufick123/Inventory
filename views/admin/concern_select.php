<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 fw-bold mb-2">Select Active Concern</h1>
                    <p class="text-muted">Operational screens use only the selected concern. New concerns do not inherit facilities, stock, bills, or history.</p>

                    <?php if (!$concerns): ?>
                        <div class="alert alert-warning mb-0">No active concerns are assigned to your account. Contact an administrator.</div>
                    <?php else: ?>
                        <form method="POST" action="<?= url('concern/activate') ?>">
                            <?= CSRF::field() ?>
                            <label for="activeConcern" class="form-label fw-semibold">Concern</label>
                            <select id="activeConcern" name="concern_id" class="form-select mb-3" required>
                                <option value="">-- Choose a concern --</option>
                                <?php foreach ($concerns as $concern): ?>
                                    <option value="<?= (int)$concern['id'] ?>" <?= (int)($activeConcern['id'] ?? 0) === (int)$concern['id'] ? 'selected' : '' ?>>
                                        <?= e($concern['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Use Concern</button>
                        </form>

                        <?php if ($activeConcern): ?>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <?php if (hasPermission('production', 'view')): ?>
                                    <a class="btn btn-outline-secondary" href="<?= url('production/dashboard') ?>">Open Production Dashboard</a>
                                <?php endif; ?>
                                <?php if (hasPermission('inventory', 'view')): ?>
                                    <a class="btn btn-outline-primary" href="<?= url('production/inventory') ?>">Open Production Inventory</a>
                                <?php endif; ?>
                                <?php if (hasPermission('production_inward', 'view')): ?>
                                    <a class="btn btn-outline-success" href="<?= url('production/inward') ?>">Open Production Inward</a>
                                <?php endif; ?>
                                <?php if (hasPermission('production_billing', 'view')): ?>
                                    <a class="btn btn-outline-dark" href="<?= url('production/billing') ?>">Open Production Billing</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
