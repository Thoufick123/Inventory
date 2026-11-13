<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Production Billing</h4>
        <div class="text-muted small">Bills from QC-passed &amp; concern inwarded Production Inventory</div>
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('production_billing_returns', 'view')): ?>
            <a class="btn btn-outline-primary" href="<?= url('production/billing/returns') ?>"><i class="bi bi-arrow-left-right me-1"></i>Returns &amp; Exchanges</a>
        <?php endif; ?>
        <?php if (hasPermission('production_billing', 'create')): ?>
            <a class="btn btn-primary" href="<?= url('production/billing/create') ?>"><i class="bi bi-plus-lg me-1"></i>Create Bill</a>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Bill No.</th>
                    <th>Date</th>
                    <th>Bill To</th>
                    <th class="text-center">Lines</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bills)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">No Production Bills have been created.</td></tr>
                <?php else: ?>
                    <?php foreach ($bills as $bill):
                        $isBatchBill = ($bill['bill_source'] ?? '') === 'BATCH_OUTWARD' && !empty($bill['source_batch_id']);
                    ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($bill['bill_number']) ?></div>
                                <?php if ($isBatchBill): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle mt-1">
                                        From Batch <?= htmlspecialchars($bill['source_batch_number'] ?? ('#' . (int)$bill['source_batch_id'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($bill['bill_date']))) ?></td>
                            <td>
                                <span class="badge text-bg-light"><?= htmlspecialchars($bill['buyer_type'] ?? 'COMPANY') ?></span>
                                <div class="fw-semibold"><?= htmlspecialchars(($bill['buyer_type'] ?? 'COMPANY') === 'CUSTOMER' ? ($bill['customer_name'] ?? '') : ($bill['company_name'] ?? '')) ?></div>
                                <small class="text-muted"><?= htmlspecialchars(($bill['buyer_type'] ?? 'COMPANY') === 'CUSTOMER' ? ($bill['customer_phone'] ?? '') : ($bill['company_phone'] ?? '')) ?></small>
                            </td>
                            <td class="text-center"><?= (int)$bill['line_count'] ?></td>
                            <td class="text-end fw-semibold">₹<?= number_format((float)$bill['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge <?= $bill['payment_status'] === 'PAID' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' ?>">
                                    <?= htmlspecialchars($bill['payment_status']) ?>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php if (hasPermission('production_billing', 'view')): ?>
                                    <a class="btn btn-sm btn-outline-primary" title="View" href="<?= url('production/billing/view/' . (int)$bill['id']) ?>"><i class="bi bi-eye"></i></a>
                                <?php endif; ?>
                                <?php if ($isBatchBill): ?>
                                    <a class="btn btn-sm btn-outline-info" title="View Batch Sale Summary" href="<?= url('production/batches/sale-bill/' . (int)$bill['source_batch_id']) ?>"><i class="bi bi-box-seam"></i></a>
                                <?php endif; ?>
                                <?php if (!$isBatchBill && hasPermission('production_billing', 'edit')): ?>
                                    <a class="btn btn-sm btn-outline-secondary" title="Edit" href="<?= url('production/billing/edit/' . (int)$bill['id']) ?>"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <?php if (hasPermission('production_billing', 'print') || hasPermission('production_billing', 'view')): ?>
                                    <a class="btn btn-sm btn-outline-dark" title="Print A4 Bill" target="_blank" href="<?= url('production/billing/print/' . (int)$bill['id'] . '?format=a4') ?>"><i class="bi bi-printer"></i> A4</a>
                                    <a class="btn btn-sm btn-outline-success" title="Print POS Receipt" target="_blank" href="<?= url('production/billing/print/' . (int)$bill['id'] . '?format=pos') ?>"><i class="bi bi-receipt"></i> POS</a>
                                <?php endif; ?>
                                <?php if (!$isBatchBill && hasPermission('production_billing', 'delete')): ?>
                                    <form class="d-inline" method="POST" action="<?= url('production/billing/delete/' . (int)$bill['id']) ?>" onsubmit="return confirm('Delete this bill? Its billed QR pieces will become available again.')">
                                        <?= CSRF::input() ?>
                                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
