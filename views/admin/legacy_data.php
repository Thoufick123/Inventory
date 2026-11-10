<div class="container-fluid py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1 fw-bold">Legacy Concern Data</h1>
        <p class="text-muted mb-0">Review historical records that are still unassigned and records already owned by a concern.</p>
    </div>

    <div class="alert alert-warning">
        <strong>Admin-only, deliberate assignment.</strong>
        New concerns do not inherit records. Assigning a row changes its ownership; it does not copy data.
        Only selected rows with no concern are eligible. Review related records before assignment; linked rows
        that have their own ownership column must be assigned separately where appropriate.
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Operational data ownership</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Data table</th>
                        <th class="text-end">Unassigned legacy</th>
                        <th class="text-end">Total</th>
                        <th>Concern-owned records</th>
                        <th class="text-end pe-3">Review</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tables as $tableSummary): ?>
                    <tr>
                        <td class="ps-3">
                            <strong><?= e($tableSummary['label']) ?></strong>
                            <small class="d-block text-muted"><code><?= e($tableSummary['table']) ?></code></small>
                        </td>
                        <?php if (!$tableSummary['available']): ?>
                            <td colspan="3"><span class="text-muted">Ownership schema unavailable; apply concern ownership migrations.</span></td>
                            <td class="text-end pe-3">—</td>
                        <?php else: ?>
                            <td class="text-end">
                                <?php if ($tableSummary['unassigned'] > 0): ?>
                                    <span class="badge text-bg-warning"><?= number_format($tableSummary['unassigned']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= number_format($tableSummary['total']) ?></td>
                            <td>
                                <?php if (!$tableSummary['concerns']): ?>
                                    <span class="text-muted">None</span>
                                <?php else: foreach ($tableSummary['concerns'] as $owner): ?>
                                    <span class="badge text-bg-light border text-dark me-1 mb-1"><?= e($owner['name']) ?>: <?= number_format($owner['count']) ?></span>
                                <?php endforeach; endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <?php if ($tableSummary['unassigned'] > 0): ?>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/legacy-data?table=' . rawurlencode($tableSummary['table'])) ?>"><?= in_array($tableSummary['table'], ['warehouse_transfers', 'transfer_items'], true) ? 'Review records' : 'Select records' ?></a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($selectedTable !== ''): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <strong>Unassigned <?= e($selectedTable) ?></strong>
                    <span class="text-muted ms-2"><?= number_format($recordCount) ?> record(s)</span>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/legacy-data') ?>">Close</a>
            </div>
            <div class="card-body">
                <?php if (!$recordCount): ?>
                    <p class="text-muted mb-0">There are no unassigned rows in this table.</p>
                <?php elseif (!$records): ?>
                    <p class="text-muted mb-0">No records on this page.</p>
                <?php else: ?>
                    <?php if ($canAssign): ?>
                        <form method="POST" action="<?= url('admin/legacy-data/assign') ?>" onsubmit="return confirm('Assign only the selected unassigned rows to the chosen concern? This changes their ownership and cannot be undone here.')">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="table" value="<?= e($selectedTable) ?>">
                            <div class="row g-3 align-items-end mb-3">
                                <div class="col-md-6 col-lg-4">
                                    <label for="legacyConcern" class="form-label fw-semibold">Assign to concern</label>
                                    <select id="legacyConcern" name="concern_id" class="form-select" required>
                                        <option value="">Choose an active concern</option>
                                        <?php foreach ($concerns as $concern): ?>
                                            <option value="<?= (int)$concern['id'] ?>"><?= e($concern['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-auto">
                                    <button type="submit" class="btn btn-primary" <?= !$concerns ? 'disabled' : '' ?>>Assign selected rows</button>
                                </div>
                                <div class="col">
                                    <small class="text-muted">Choose at most 50 rows per operation. Assignment is restricted to this table and unassigned IDs.</small>
                                </div>
                            </div>
                    <?php else: ?>
                        <div class="alert alert-info">Legacy transfer rows are shown for review only. Assigning a transfer requires a separate source/destination concern decision; this console does not infer either side.</div>
                    <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <?php if ($canAssign): ?><th style="width: 3rem"><input class="form-check-input" type="checkbox" aria-label="Select all rows on this page" onclick="document.querySelectorAll('.legacy-record-check').forEach((box) => box.checked = this.checked)"></th><?php endif; ?>
                                        <th>Record ID</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <?php if ($canAssign): ?><td><input class="form-check-input legacy-record-check" type="checkbox" name="record_ids[]" value="<?= (int)$record['id'] ?>" aria-label="Select record <?= (int)$record['id'] ?>"></td><?php endif; ?>
                                        <td><?= (int)$record['id'] ?></td>
                                        <td><?= e($record['display_value'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php if ($canAssign): ?>
                    </form>
                    <?php endif; ?>

                    <?php
                    $lastPage = max(1, (int)ceil($recordCount / $pageSize));
                    $baseUrl = 'admin/legacy-data?table=' . rawurlencode($selectedTable);
                    ?>
                    <?php if ($lastPage > 1): ?>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="small text-muted">Page <?= (int)$page ?> of <?= $lastPage ?></span>
                            <div class="d-flex gap-2">
                                <?php if ($page > 1): ?><a class="btn btn-sm btn-outline-secondary" href="<?= url($baseUrl . '&page=' . ($page - 1)) ?>">Previous</a><?php endif; ?>
                                <?php if ($page < $lastPage): ?><a class="btn btn-sm btn-outline-secondary" href="<?= url($baseUrl . '&page=' . ($page + 1)) ?>">Next</a><?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
