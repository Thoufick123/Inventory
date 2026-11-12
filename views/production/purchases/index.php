<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-bag-check me-2 text-primary"></i>Production Purchases</h2>
        <span class="text-muted">Record fabric roll inward and general garment supplies from registered suppliers.</span>
    </div>
    <?php if (hasPermission('sellers', 'create')): ?>
        <a href="<?= url('production/purchases/create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Record Purchase</a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small text-uppercase">Purchase Records</div>
            <div class="fs-3 fw-bold"><?= number_format(count($purchases)) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small text-uppercase">Purchase Value</div>
            <div class="fs-3 fw-bold text-success">₹<?= number_format((float)$totalPurchaseValue, 2) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small text-uppercase">General Supply Items</div>
            <div class="fs-3 fw-bold text-primary"><?= number_format(count($supplyStock)) ?></div>
        </div></div>
    </div>
</div>

<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white"><h5 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Purchase History</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Purchase # / Date</th><th>Supplier</th><th>Items</th><th class="text-end">Total</th><th>Invoice</th></tr></thead>
            <tbody>
            <?php if (empty($purchases)): ?>
                <tr><td colspan="5" class="text-center text-muted py-5">No purchases recorded yet.</td></tr>
            <?php else: foreach ($purchases as $purchase): ?>
                <tr>
                    <td>
                        <strong class="font-monospace"><?= e($purchase['purchase_number']) ?></strong>
                        <small class="d-block text-muted"><?= e(formatDate($purchase['purchase_date'])) ?></small>
                        <?php if (!empty($purchase['source_batch_number'])): ?>
                            <small class="d-block font-monospace text-info mt-1">
                                <?= e($purchase['source_batch_number']) ?><?= !empty($purchase['source_sale_bill_number']) ? ' · ' . e($purchase['source_sale_bill_number']) : '' ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td><?= e($purchase['company'] ?: $purchase['seller_name']) ?><small class="d-block text-muted"><?= e($purchase['seller_name']) ?></small></td>
                    <td>
                        <?php foreach ($purchase['items'] as $item): ?>
                            <div class="small">
                                <?php
                                    $badgeClass = $item['item_type'] === 'FABRIC_ROLL'
                                        ? 'bg-primary-subtle text-primary'
                                        : ($item['item_type'] === 'FINISHED_GOODS' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary');
                                    $badgeLabel = $item['item_type'] === 'FABRIC_ROLL'
                                        ? 'Fabric roll'
                                        : ($item['item_type'] === 'FINISHED_GOODS' ? 'Finished goods' : 'Supply');
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                                <?php if ($item['item_type'] === 'FABRIC_ROLL'): ?>
                                    <?= e($item['roll_number']) ?> · RQ <?= number_format((float)$item['quantity']) ?> · <?= number_format((float)$item['weight_kg'], 2) ?> kg
                                    <?php if (!empty($item['start_code'])): ?><span class="font-monospace text-muted"><?= e($item['start_code']) ?>–<?= e($item['end_code']) ?></span><?php endif; ?>
                                    <?php if (!empty($item['seller_roll_id'])): ?>
                                        <a href="<?= url('production/sellers/roll-labels/' . $item['seller_roll_id']) ?>" target="_blank" rel="noopener" class="text-nowrap"><i class="bi bi-qr-code me-1"></i>Labels</a>
                                    <?php endif; ?>
                                <?php elseif ($item['item_type'] === 'FINISHED_GOODS'): ?>
                                    <?= e($item['item_name']) ?>
                                    <?php if (!empty($item['sku'])): ?><span class="font-monospace text-muted">(<?= e($item['sku']) ?>)</span><?php endif; ?>
                                    · <?= number_format((float)$item['quantity']) ?> <?= e($item['unit']) ?>
                                <?php else: ?>
                                    <?= e($item['item_name']) ?> · <?= number_format((float)$item['quantity'], 3) ?> <?= e($item['unit']) ?>
                                <?php endif; ?>
                                <?php
                                    $purchaseItemTaxType = $item['gst_type'] ?? 'LEGACY';
                                    $purchaseItemTaxAmount = (float)($item['sgst_amount'] ?? 0)
                                        + (float)($item['cgst_amount'] ?? 0)
                                        + (float)($item['igst_amount'] ?? 0);
                                ?>
                                <small class="d-block text-muted">
                                    Base ₹<?= number_format((float)$item['unit_price'] * ($item['item_type'] === 'FABRIC_ROLL' ? 1 : (float)$item['quantity']), 2) ?>
                                    · <?= e($purchaseItemTaxType === 'LEGACY' ? 'Legacy SGST/CGST' : ($purchaseItemTaxType === 'BOTH' ? 'SGST + CGST' : $purchaseItemTaxType)) ?> ₹<?= number_format($purchaseItemTaxAmount, 2) ?>
                                    · Line total ₹<?= number_format((float)$item['total_price'], 2) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </td>
                    <td class="text-end fw-bold">₹<?= number_format((float)$purchase['total_amount'], 2) ?></td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <?php if (hasPermission('sellers', 'view')): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= url('production/purchases/view/' . $purchase['id']) ?>" title="View Purchase"><i class="bi bi-eye"></i></a>
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('production/purchases/print/' . $purchase['id']) ?>" target="_blank" rel="noopener" title="Print Report"><i class="bi bi-printer"></i></a>
                            <?php endif; ?>
                            <?php if (hasPermission('sellers', 'edit')): ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?= url('production/purchases/edit/' . $purchase['id']) ?>" title="Edit Purchase"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                            <?php if (hasPermission('sellers', 'delete')): ?>
                                <form method="POST" action="<?= url('production/purchases/delete/' . $purchase['id']) ?>" class="d-inline" onsubmit="return confirm('Delete purchase <?= e(addslashes($purchase['purchase_number'])) ?>? Fabric rolls already referenced by Production cannot be removed.');">
                                    <?= CSRF::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Purchase"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($purchase['invoice_number'])): ?><span class="small d-block mb-1"><?= e($purchase['invoice_number']) ?></span><?php endif; ?>
                        <?php if (!empty($purchase['document_url'])): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= uploadUrl($purchase['document_url']) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-text me-1"></i>View</a>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white"><h5 class="mb-0 fw-bold"><i class="bi bi-layers-half me-2 text-primary"></i>Fabric Rolls Inventory &amp; Details</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>#</th><th>Supplier</th><th>Photo</th><th>Fabric / Roll</th><th>Color / Design</th><th class="text-center">Designs</th><th class="text-end">Total RQ</th><th class="text-end">Weight</th><th class="text-end">Price</th><th>Sticker Codes</th><th class="text-center">Status</th></tr></thead>
            <tbody>
            <?php if (empty($fabricRollInventory)): ?>
                <tr><td colspan="11" class="text-center text-muted py-4">No fabric rolls recorded yet.</td></tr>
            <?php else: foreach ($fabricRollInventory as $index => $roll): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= e($roll['company'] ?: $roll['seller_name']) ?><small class="d-block text-muted"><?= e($roll['seller_name']) ?></small></td>
                    <td><?php if (!empty($roll['photo_url'])): ?><a href="<?= uploadUrl($roll['photo_url']) ?>" target="_blank" rel="noopener"><img src="<?= uploadUrl($roll['photo_url']) ?>" alt="Roll photo" class="rounded border" style="width:40px;height:40px;object-fit:cover"></a><?php else: ?><span class="text-muted"><i class="bi bi-camera"></i></span><?php endif; ?></td>
                    <td><strong><?= e($roll['roll_number'] ?: '—') ?></strong><small class="d-block text-muted"><?= e($roll['fabric_description']) ?></small></td>
                    <td><?= e($roll['color'] ?: '—') ?><small class="d-block text-muted"><?= e($roll['design_name'] ?: '—') ?></small></td>
                    <td class="text-center"><?= number_format((int)$roll['design_count']) ?></td>
                    <td class="text-end fw-semibold"><?= number_format((int)$roll['quantity']) ?> RQ</td>
                    <td class="text-end"><?= number_format((float)$roll['weight'], 2) ?> kg</td>
                    <td class="text-end">₹<?= number_format((float)$roll['price'], 2) ?></td>
                    <td class="font-monospace small"><?= e($roll['start_code'] ?: '—') ?><?= !empty($roll['end_code']) && $roll['end_code'] !== $roll['start_code'] ? '–' . e($roll['end_code']) : '' ?></td>
                    <td class="text-center">
                        <?php if ((int)$roll['available_quantity'] > 0): ?>
                            <span class="badge bg-success-subtle text-success"><?= number_format((int)$roll['available_quantity']) ?> Available</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary">Fully Used / Damaged</span>
                        <?php endif; ?>
                        <a class="d-block small" href="<?= url('production/sellers/roll-labels/' . $roll['id']) ?>" target="_blank" rel="noopener">Labels</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border shadow-sm">
    <div class="card-header bg-white"><h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-success"></i>General Supplies Purchased</h5><small class="text-muted">Totals received from recorded purchases; usage deductions are not included.</small></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Item</th><th>Unit</th><th class="text-end">Total Purchased</th><th class="text-end">Purchase Lines</th></tr></thead>
            <tbody>
            <?php if (empty($supplyStock)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No general supplies purchased yet.</td></tr>
            <?php else: foreach ($supplyStock as $stock): ?>
                <tr><td><?= e($stock['item_name']) ?></td><td><?= e($stock['unit']) ?></td><td class="text-end fw-semibold"><?= number_format((float)$stock['total_quantity'], 3) ?></td><td class="text-end"><?= number_format((int)$stock['purchase_lines']) ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
