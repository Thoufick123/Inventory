<?php $isBatchBill = ($bill['bill_source'] ?? '') === 'BATCH_OUTWARD' && !empty($bill['source_batch_id']); ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1 fw-bold">
            <?= htmlspecialchars($bill['bill_number']) ?>
            <?php if ($isBatchBill): ?>
                <span class="badge bg-info-subtle text-info border border-info-subtle fs-6 ms-2">From Batch</span>
            <?php endif; ?>
        </h4>
        <div class="text-muted small">Production bill · <?= htmlspecialchars(date('d M Y', strtotime($bill['bill_date']))) ?></div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= url('production/billing') ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
        <?php if ($isBatchBill): ?>
            <a class="btn btn-outline-info" href="<?= url('production/batches/sale-bill/' . (int)$bill['source_batch_id']) ?>"><i class="bi bi-box-seam me-1"></i>Batch Details</a>
        <?php elseif (hasPermission('production_billing', 'edit')): ?>
            <a class="btn btn-outline-primary" href="<?= url('production/billing/edit/' . (int)$bill['id']) ?>"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
        <?php if (hasPermission('production_billing', 'print')): ?><a class="btn btn-dark" target="_blank" href="<?= url('production/billing/print/' . (int)$bill['id']) ?>"><i class="bi bi-printer me-1"></i>Print</a><?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-4">
            <?php if (($bill['buyer_type'] ?? 'COMPANY') === 'CUSTOMER'): ?>
            <div class="col-md-6">
                <div class="text-uppercase text-muted small fw-semibold mb-2">Customer</div>
                <h5><?= htmlspecialchars($bill['customer_name'] ?? '') ?></h5>
                <div><?= htmlspecialchars($bill['customer_phone'] ?? '') ?></div>
                <div><?= nl2br(htmlspecialchars($bill['customer_address'] ?? '')) ?></div>
                <div><?= htmlspecialchars(trim(($bill['customer_city'] ?? '') . ' ' . ($bill['customer_state'] ?? ''))) ?></div>
                <?php if (!empty($bill['customer_gstin'])): ?><div class="small text-muted">GSTIN: <?= htmlspecialchars($bill['customer_gstin']) ?></div><?php endif; ?>
            </div>
            <?php else: ?>
            <div class="col-md-6">
                <div class="text-uppercase text-muted small fw-semibold mb-2">Buyer company</div>
                <h5><?= htmlspecialchars($bill['company_name'] ?? '') ?></h5>
                <div><?= nl2br(htmlspecialchars($bill['company_address'] ?? '')) ?></div>
                <div><?= htmlspecialchars($bill['company_phone'] ?? '') ?> <?= htmlspecialchars($bill['company_email'] ?? '') ?></div>
                <?php if (!empty($bill['company_gstin'])): ?><div class="small text-muted">GSTIN: <?= htmlspecialchars($bill['company_gstin']) ?></div><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Item / QR codes</th><th class="text-center">Qty</th><th class="text-end">MRP</th><th class="text-end">Discount</th><th class="text-end">Taxable</th><th>GST details</th><th class="text-end">Tax</th><th class="text-end">Total</th></tr></thead>
            <tbody>
                <?php foreach ($billItems as $item): ?>
                    <tr>
                        <td><div class="fw-semibold"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars(trim(($item['sku'] ?? '') . ' ' . ($item['size_name'] ?? '') . ' ' . ($item['color_name'] ?? ''))) ?></div>
                            <div class="small font-monospace text-muted"><?= htmlspecialchars(implode(', ', $item['tags'])) ?></div></td>
                        <td class="text-center"><?= (int)$item['quantity'] ?></td>
                        <td class="text-end">₹<?= number_format((float)$item['mrp'] * (int)$item['quantity'], 2) ?></td>
                        <td class="text-end">₹<?= number_format((float)$item['discount_amount'], 2) ?></td>
                        <td class="text-end">₹<?= number_format((float)$item['taxable_amount'], 2) ?></td>
                        <td>
                            <div><?= htmlspecialchars($item['gst_type'] ?? 'BOTH') ?> · <?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? 'Percentage' : 'Fixed' ?></div>
                            <?php if (($item['gst_type'] ?? '') === 'BOTH'): ?>
                                <small class="text-muted">SGST <?= number_format((float)$item['sgst_rate'], 2) ?><?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ₹/piece' ?> · CGST <?= number_format((float)$item['cgst_rate'], 2) ?><?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ₹/piece' ?></small>
                            <?php elseif (($item['gst_type'] ?? '') === 'IGST'): ?>
                                <small class="text-muted">IGST <?= number_format((float)$item['igst_rate'], 2) ?><?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ₹/piece' ?></small>
                            <?php elseif (($item['gst_type'] ?? '') === 'SGST'): ?>
                                <small class="text-muted">SGST <?= number_format((float)$item['sgst_rate'], 2) ?><?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ₹/piece' ?></small>
                            <?php elseif (($item['gst_type'] ?? '') === 'CGST'): ?>
                                <small class="text-muted">CGST <?= number_format((float)$item['cgst_rate'], 2) ?><?= ($item['tax_input_type'] ?? 'PERCENT') === 'PERCENT' ? '%' : ' ₹/piece' ?></small>
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
            <?php if ((float)$bill['cgst_amount'] > 0): ?>
                <div class="d-flex justify-content-between py-1"><span>CGST</span><strong>₹<?= number_format((float)$bill['cgst_amount'], 2) ?></strong></div>
            <?php endif; ?>
            <?php if ((float)$bill['sgst_amount'] > 0): ?>
                <div class="d-flex justify-content-between py-1"><span>SGST</span><strong>₹<?= number_format((float)$bill['sgst_amount'], 2) ?></strong></div>
            <?php endif; ?>
            <?php if ((float)$bill['igst_amount'] > 0): ?>
                <div class="d-flex justify-content-between py-1"><span>IGST</span><strong>₹<?= number_format((float)$bill['igst_amount'], 2) ?></strong></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between py-1"><span>Total taxes</span><strong>₹<?= number_format((float)$bill['tax_amount'], 2) ?></strong></div>
            <hr>
            <div class="d-flex justify-content-between fs-5"><span class="fw-bold">Total</span><strong>₹<?= number_format((float)$bill['total_amount'], 2) ?></strong></div>
            <div class="small text-muted mt-2">Payment: <?= htmlspecialchars($bill['payment_method']) ?> · <?= htmlspecialchars($bill['payment_status']) ?></div>
            <?php if ($bill['payment_method'] === 'CASH_UPI'): ?><div class="small text-muted">Cash ₹<?= number_format((float)$bill['cash_amount'], 2) ?> · UPI ₹<?= number_format((float)$bill['upi_amount'], 2) ?></div><?php endif; ?>
        </div></div>
    </div>
</div>
