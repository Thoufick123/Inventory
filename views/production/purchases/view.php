<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-receipt me-2 text-primary"></i><?= e($purchase['purchase_number']) ?></h2>
        <span class="text-muted">Purchase details and itemized tax summary</span>
    </div>
    <a href="<?= url('production/purchases') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Purchases</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border shadow-sm h-100">
            <div class="card-header bg-white fw-bold">Purchase Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6"><small class="text-muted d-block">Purchase Number</small><strong><?= e($purchase['purchase_number']) ?></strong></div>
                    <div class="col-sm-6"><small class="text-muted d-block">Purchase Date</small><strong><?= e(formatDate($purchase['purchase_date'])) ?></strong></div>
                    <div class="col-sm-6"><small class="text-muted d-block">Invoice / Bill Number</small><strong><?= e($purchase['invoice_number'] ?: '—') ?></strong></div>
                    <div class="col-sm-6"><small class="text-muted d-block">Notes</small><strong><?= e($purchase['notes'] ?: '—') ?></strong></div>
                    <?php if (!empty($purchase['source_batch_id'])): ?>
                        <div class="col-12">
                            <div class="p-2 bg-light border rounded d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span class="small">
                                    <strong>Inter-Concern Batch:</strong> <span class="font-monospace"><?= e($purchase['source_batch_number'] ?? ('#' . (int)$purchase['source_batch_id'])) ?></span>
                                    <?php if (!empty($purchase['source_sale_bill_number'])): ?>
                                        · Sender Sale Bill: <span class="font-monospace text-success"><?= e($purchase['source_sale_bill_number']) ?></span>
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($purchase['source_sale_bill_id'])): ?>
                                    <a href="<?= url('production/purchases/sale-bill/' . (int)$purchase['source_batch_id']) ?>" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-receipt me-1"></i>View Sender Sale Bill
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($purchase['document_url'])): ?><div class="col-12"><a href="<?= uploadUrl($purchase['document_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-text me-1"></i>View Invoice Attachment</a></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border shadow-sm h-100">
            <div class="card-header bg-white fw-bold">Supplier Details</div>
            <div class="card-body">
                <strong><?= e($purchase['seller_company'] ?: $purchase['seller_name']) ?></strong>
                <?php if (!empty($purchase['seller_company'])): ?><div><?= e($purchase['seller_name']) ?></div><?php endif; ?>
                <div class="mt-2"><?= e($purchase['seller_phone'] ?: '—') ?><?= !empty($purchase['seller_email']) ? ' · ' . e($purchase['seller_email']) : '' ?></div>
                <?php if (!empty($purchase['seller_gst_number'])): ?><div>GSTIN: <?= e($purchase['seller_gst_number']) ?></div><?php endif; ?>
                <?php if (!empty($purchase['seller_address'])): ?><div class="text-muted mt-2"><?= e($purchase['seller_address']) ?></div><?php endif; ?>
                <?php if (!empty($purchase['seller_city']) || !empty($purchase['seller_state'])): ?><div class="text-muted"><?= e(trim(($purchase['seller_city'] ?? '') . ', ' . ($purchase['seller_state'] ?? ''), ', ')) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">Purchased Items</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>#</th><th>Item</th><th>Quantity</th><th class="text-end">Unit Price</th><th class="text-end">Subtotal</th><th>GST</th><th class="text-end">Tax</th><th class="text-end">Total</th></tr></thead>
            <tbody>
            <?php
                $subtotal = 0.0;
                $taxTotal = 0.0;
                foreach ($items as $index => $item):
                    $base = (float)$item['unit_price'] * ($item['item_type'] === 'FABRIC_ROLL' ? 1 : (float)$item['quantity']);
                    $tax = (float)($item['sgst_amount'] ?? 0) + (float)($item['cgst_amount'] ?? 0) + (float)($item['igst_amount'] ?? 0);
                    $subtotal += $base;
                    $taxTotal += $tax;
                    $taxType = $item['gst_type'] ?? 'LEGACY';
                    if ($taxType === 'BOTH') $taxType = 'SGST + CGST';
                    if ($taxType === 'LEGACY') $taxType = $tax > 0 ? 'Legacy SGST + CGST' : 'Exempted';
                    $badgeCls = $item['item_type'] === 'FABRIC_ROLL'
                        ? 'bg-primary-subtle text-primary'
                        : ($item['item_type'] === 'FINISHED_GOODS' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary');
                    $badgeTxt = $item['item_type'] === 'FABRIC_ROLL'
                        ? 'Fabric Roll'
                        : ($item['item_type'] === 'FINISHED_GOODS' ? 'Finished Goods' : 'Supply');
            ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td>
                        <span class="badge <?= $badgeCls ?>"><?= $badgeTxt ?></span>
                        <strong class="d-block"><?= e($item['item_name']) ?></strong>
                        <?php if ($item['item_type'] === 'FABRIC_ROLL'): ?>
                            <small class="text-muted"><?= e($item['roll_number'] ?: '—') ?> · <?= e($item['color'] ?: '—') ?> · <?= e($item['design_name'] ?: '—') ?></small>
                            <?php if (!empty($item['start_code'])): ?><small class="d-block font-monospace"><?= e($item['start_code']) ?><?= $item['end_code'] !== $item['start_code'] ? '–' . e($item['end_code']) : '' ?></small><?php endif; ?>
                        <?php elseif ($item['item_type'] === 'FINISHED_GOODS'): ?>
                            <small class="text-muted font-monospace">
                                <?= e($item['sku'] ?: '') ?>
                                <?php if (!empty($item['size_name']) || !empty($item['color_name'])): ?>
                                    · <?= e(trim(($item['size_name'] ?? '') . ' / ' . ($item['color_name'] ?? ''), ' /')) ?>
                                <?php endif; ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format((float)$item['quantity'], 3) ?> <?= e($item['unit']) ?><?= !empty($item['weight_kg']) ? '<br><small class="text-muted">' . number_format((float)$item['weight_kg'], 2) . ' kg</small>' : '' ?></td>
                    <td class="text-end">₹<?= number_format((float)$item['unit_price'], 2) ?></td>
                    <td class="text-end">₹<?= number_format($base, 2) ?></td>
                    <td><?= e($taxType) ?></td>
                    <td class="text-end">₹<?= number_format($tax, 2) ?></td>
                    <td class="text-end fw-bold">₹<?= number_format((float)$item['total_price'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr><th colspan="4" class="text-end">Subtotal</th><th class="text-end">₹<?= number_format($subtotal, 2) ?></th><th colspan="2"></th><th></th></tr>
                <tr><th colspan="7" class="text-end">Taxes</th><th class="text-end">₹<?= number_format($taxTotal, 2) ?></th></tr>
                <tr><th colspan="7" class="text-end">Overall Total</th><th class="text-end text-success">₹<?= number_format((float)$purchase['total_amount'], 2) ?></th></tr>
            </tfoot>
        </table>
    </div>
</div>
