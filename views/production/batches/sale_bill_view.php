<?php
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$isReceiver = !empty($isReceiver);
$backUrl = $isReceiver ? url('production/inward') : url('production/batches');
$printUrl = url(($isReceiver ? 'production/inward/sale-bill/print/' : 'production/batches/sale-bill/print/') . (int)$batch['batch_id']);
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h4 class="mb-0 fw-bold"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Sale Bill <?= $escape($bill['bill_number']) ?></h4>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">Batch <?= $escape($batch['batch_number']) ?></span>
            <span class="badge <?= ($bill['payment_status'] ?? '') === 'PAID' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' ?>">
                <?= $escape($bill['payment_status'] ?? 'PAID') ?>
            </span>
        </div>
        <div class="text-muted small mt-1">
            Bill Date: <?= $escape(date('d M Y', strtotime($bill['bill_date']))) ?>
            · From <strong><?= $escape($batch['source_concern_name'] ?: 'Sender Concern') ?></strong>
            &rarr; To <strong><?= $escape($batch['destination_concern_name'] ?: 'Receiving Concern') ?></strong>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="<?= $backUrl ?>">
            <i class="bi bi-arrow-left me-1"></i><?= $isReceiver ? 'Back to Inward' : 'Back to Batches' ?>
        </a>
        <?php if (!$isReceiver): ?>
            <a class="btn btn-outline-primary" href="<?= url('production/outward') ?>">
                <i class="bi bi-truck me-1"></i>Outward Dispatch
            </a>
            <a class="btn btn-outline-secondary" href="<?= url('production/batches/label/' . (int)$batch['batch_id']) ?>" target="_blank" rel="noopener">
                <i class="bi bi-qr-code me-1"></i>Batch Label
            </a>
        <?php endif; ?>
        <?php if ($isReceiver && !empty($existingPurchase['id'])): ?>
            <a class="btn btn-success" href="<?= url('production/purchases/view/' . (int)$existingPurchase['id']) ?>">
                <i class="bi bi-bag-check me-1"></i>View Purchase Bill (<?= $escape($existingPurchase['purchase_number']) ?>)
            </a>
        <?php elseif ($isReceiver && !empty($canCreatePurchase)): ?>
            <a class="btn btn-success fw-semibold" href="<?= url('production/purchases/create?from_batch_id=' . (int)$batch['batch_id']) ?>">
                <i class="bi bi-bag-plus me-1"></i>Put Purchase Bill in Purchases
            </a>
        <?php endif; ?>
        <a class="btn btn-dark" target="_blank" rel="noopener" href="<?= $printUrl ?>?format=a4">
            <i class="bi bi-file-earmark-text me-1"></i>Print A4 Bill
        </a>
        <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= $printUrl ?>?format=pos">
            <i class="bi bi-receipt me-1"></i>POS Receipt
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="text-uppercase text-muted small fw-semibold mb-2">Sender &amp; Batch Dispatch</div>
                <div class="fw-bold text-dark fs-6"><?= $escape($batch['source_concern_name'] ?: 'Sender Concern') ?></div>
                <div class="small text-muted mt-1">Batch #: <code class="text-dark fw-semibold"><?= $escape($batch['batch_number']) ?></code> (<?= $escape($batch['batch_status']) ?>)</div>
                <div class="small text-muted">Destination Concern: <strong><?= $escape($batch['destination_concern_name'] ?: '—') ?></strong></div>
            </div>
            <?php if (($bill['buyer_type'] ?? 'COMPANY') === 'CUSTOMER'): ?>
            <div class="col-md-6">
                <div class="text-uppercase text-muted small fw-semibold mb-2">Billed To (Customer)</div>
                <h5 class="mb-1"><?= $escape($bill['customer_name'] ?? '') ?></h5>
                <div><?= $escape($bill['customer_phone'] ?? '') ?></div>
                <div><?= nl2br($escape($bill['customer_address'] ?? '')) ?></div>
                <div><?= $escape(trim(($bill['customer_city'] ?? '') . ' ' . ($bill['customer_state'] ?? ''))) ?></div>
                <?php if (!empty($bill['customer_gstin'])): ?><div class="small text-muted">GSTIN: <?= $escape($bill['customer_gstin']) ?></div><?php endif; ?>
            </div>
            <?php else: ?>
            <div class="col-md-6">
                <div class="text-uppercase text-muted small fw-semibold mb-2">Billed To (Company)</div>
                <h5 class="mb-1"><?= $escape($bill['company_name'] ?? '') ?></h5>
                <div><?= nl2br($escape($bill['company_address'] ?? '')) ?></div>
                <div><?= $escape($bill['company_phone'] ?? '') ?> <?= $escape($bill['company_email'] ?? '') ?></div>
                <?php if (!empty($bill['company_gstin'])): ?><div class="small text-muted">GSTIN: <?= $escape($bill['company_gstin']) ?></div><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Item / QR codes</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">MRP</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Discount</th>
                    <th class="text-end">Taxable</th>
                    <th>GST details</th>
                    <th class="text-end">Tax</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($billItems as $item): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= $escape($item['product_name']) ?></div>
                            <div class="small text-muted"><?= $escape(trim(($item['sku'] ?? '') . ' ' . ($item['size_name'] ?? '') . ' ' . ($item['color_name'] ?? ''))) ?></div>
                            <?php if (!empty($item['tags'])): ?>
                                <div class="small font-monospace text-muted"><?= $escape(implode(', ', $item['tags'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-semibold"><?= (int)$item['quantity'] ?></td>
                        <td class="text-end">₹<?= number_format((float)$item['mrp'], 2) ?></td>
                        <td class="text-end">₹<?= number_format((float)$item['unit_price'], 2) ?></td>
                        <td class="text-end">₹<?= number_format((float)$item['discount_amount'], 2) ?></td>
                        <td class="text-end">₹<?= number_format((float)$item['taxable_amount'], 2) ?></td>
                        <td>
                            <div><?= $escape($item['gst_type'] ?? 'BOTH') ?> · <?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? 'Percentage' : 'Fixed' ?></div>
                            <?php $unitLabel = ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ₹/piece'; ?>
                            <?php if (($item['gst_type'] ?? '') === 'BOTH'): ?>
                                <small class="text-muted">SGST <?= number_format((float)$item['sgst_rate'], 2) ?><?= $unitLabel ?> · CGST <?= number_format((float)$item['cgst_rate'], 2) ?><?= $unitLabel ?></small>
                            <?php elseif (($item['gst_type'] ?? '') === 'IGST'): ?>
                                <small class="text-muted">IGST <?= number_format((float)$item['igst_rate'], 2) ?><?= $unitLabel ?></small>
                            <?php elseif (($item['gst_type'] ?? '') === 'SGST'): ?>
                                <small class="text-muted">SGST <?= number_format((float)$item['sgst_rate'], 2) ?><?= $unitLabel ?></small>
                            <?php elseif (($item['gst_type'] ?? '') === 'CGST'): ?>
                                <small class="text-muted">CGST <?= number_format((float)$item['cgst_rate'], 2) ?><?= $unitLabel ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">₹<?= number_format((float)$item['tax_amount'], 2) ?></td>
                        <td class="text-end fw-semibold">₹<?= number_format((float)$item['total_amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row justify-content-end">
    <div class="col-md-5 col-lg-4">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="d-flex justify-content-between py-1"><span>Gross / MRP</span><strong>₹<?= number_format((float)$bill['gross_amount'], 2) ?></strong></div>
            <div class="d-flex justify-content-between py-1"><span>Discount</span><strong>₹<?= number_format((float)$bill['discount_amount'], 2) ?></strong></div>
            <div class="d-flex justify-content-between py-1"><span>Taxable subtotal</span><strong>₹<?= number_format((float)$bill['subtotal'], 2) ?></strong></div>
            <?php if ((float)$bill['cgst_amount'] > 0): ?><div class="d-flex justify-content-between py-1"><span>CGST</span><strong>₹<?= number_format((float)$bill['cgst_amount'], 2) ?></strong></div><?php endif; ?>
            <?php if ((float)$bill['sgst_amount'] > 0): ?><div class="d-flex justify-content-between py-1"><span>SGST</span><strong>₹<?= number_format((float)$bill['sgst_amount'], 2) ?></strong></div><?php endif; ?>
            <?php if ((float)$bill['igst_amount'] > 0): ?><div class="d-flex justify-content-between py-1"><span>IGST</span><strong>₹<?= number_format((float)$bill['igst_amount'], 2) ?></strong></div><?php endif; ?>
            <div class="d-flex justify-content-between py-1"><span>Total taxes</span><strong>₹<?= number_format((float)$bill['tax_amount'], 2) ?></strong></div>
            <hr>
            <div class="d-flex justify-content-between fs-5"><span class="fw-bold">Total</span><strong>₹<?= number_format((float)$bill['total_amount'], 2) ?></strong></div>
            <div class="small text-muted mt-2">Payment: <?= $escape($bill['payment_method']) ?> · <?= $escape($bill['payment_status']) ?></div>
            <?php if (($bill['payment_method'] ?? '') === 'CASH_UPI'): ?>
                <div class="small text-muted">Cash ₹<?= number_format((float)$bill['cash_amount'], 2) ?> · UPI ₹<?= number_format((float)$bill['upi_amount'], 2) ?></div>
            <?php endif; ?>
            <?php if (!empty($bill['notes'])): ?>
                <div class="small text-muted mt-2">Notes: <?= nl2br($escape($bill['notes'])) ?></div>
            <?php endif; ?>
        </div></div>
    </div>
</div>

