<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 760px;">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="bg-warning-subtle text-warning rounded-circle p-3"><i class="bi bi-database-exclamation fs-4"></i></div>
                <div>
                    <h2 class="h4 fw-bold mb-1">Production Returns Database Setup</h2>
                    <p class="text-muted mb-0">The website database does not have all the required Production Billing Returns schema.</p>
                </div>
            </div>

            <?php if (!empty($schemaIssues)): ?>
                <div class="alert alert-warning">
                    <div class="fw-semibold mb-2">Schema checks found:</div>
                    <ul class="mb-0">
                        <?php foreach ($schemaIssues as $issue): ?>
                            <li><?= e($issue) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">A database query failed while loading this page. The exact exception was written to the application error log.</div>
            <?php endif; ?>

            <ol class="mb-4">
                <li>Confirm the database selected in the Hosting PHP terminal is the same database configured for the website in <code>.env</code>.</li>
                <li>From the project root, run these migrations in order:</li>
            </ol>
            <pre class="bg-light border rounded p-3 mb-4"><code>php database/migrations/create_production_billing.php
php database/migrations/create_production_billing_returns.php
php database/seed_permissions.php</code></pre>

            <p class="small text-muted">If all required tables and the <code>status</code> column are present but this page still fails, inspect <code>storage/logs/error.log</code> on the hosting account. The entry beginning with <code>Production Billing Returns page failed</code> contains the failing query’s cause.</p>
            <a href="<?= url('production/billing') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Production Billing</a>
        </div>
    </div>
</div>
