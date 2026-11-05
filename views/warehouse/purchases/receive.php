<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 no-print">
    <div>
        <h2 class="mb-1"><i class="bi bi-receipt me-2 text-success"></i>Customer Purchase Tax Invoice #<?= e($po['po_number']) ?></h2>
        <span class="text-muted">Official direct customer sales invoice, itemized tax ledger &amp; dispatch slip</span>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-success shadow-sm">
            <i class="bi bi-printer me-1"></i> Print Tax Invoice
        </button>
        <a href="<?= url('warehouse/purchases') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Direct Purchases
        </a>
    </div>
</div>

<!-- ========================================================================= -->
<?php if (!empty($po['payment_screenshot_url'])): ?>
<div class="card border-0 shadow-sm rounded-3 bg-white p-3 mb-4 invoice-container d-print-none">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom">
        <span class="fw-bold text-dark"><i class="bi bi-receipt text-primary me-1"></i> Original Supplier Bill / Invoice Upload:</span>
        <a href="<?= e($po['payment_screenshot_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-box-arrow-up-right me-1"></i> Open Full Resolution
        </a>
    </div>
    <div class="text-center bg-light p-3 rounded border">
        <a href="<?= e($po['payment_screenshot_url']) ?>" target="_blank">
            <img src="<?= e($po['payment_screenshot_url']) ?>" alt="Supplier Bill" class="img-fluid rounded border shadow-sm" style="max-height: 420px; object-fit: contain;">
        </a>
    </div>
</div>
<?php endif; ?>

<!-- TAX INVOICE CARD (Printable Document Format) -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 bg-white p-4 p-md-5 invoice-container">
    <!-- Invoice Header -->
    <div class="row align-items-start border-bottom pb-4 mb-4">
        <div class="col-sm-7">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="bg-primary text-white rounded p-2 fw-bold fs-4">GRM</div>
                <div>
                    <h4 class="mb-0 fw-bold text-dark">GRM APPARELS &amp; TEXTILES</h4>
                    <span class="small text-muted">Direct Warehouse Sales &amp; Dispatch Division</span>
                </div>
            </div>
            <p class="text-muted small mb-0">
                <strong>Dispatch Warehouse:</strong> <?= e($po['warehouse_name'] ?? 'Main Warehouse') ?> (<?= e($po['warehouse_code'] ?? 'WH') ?>)<br>
                <strong>GSTIN:</strong> 33AAACG1234F1Z5 &nbsp;|&nbsp; <strong>State Code:</strong> 33 (Tamil Nadu)<br>
                <strong>Email:</strong> sales@grmapparels.com &nbsp;|&nbsp; <strong>Phone:</strong> +91 421 2233445
            </p>
        </div>
        <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 fw-bold mb-2 d-inline-block">
                TAX INVOICE / DIRECT SALE
            </span>
            <div class="font-monospace fw-bold fs-5 text-primary">#<?= e($po['po_number']) ?></div>
            <div class="small text-muted">Invoice Date: <strong><?= date('d M Y', strtotime($po['order_date'])) ?></strong></div>
            <div class="small text-muted">Payment Mode: <strong class="text-dark"><?= e($po['payment_method'] ?? 'CASH') ?></strong> (<span class="text-success fw-semibold"><?= e($po['payment_status'] ?? 'PAID') ?></span>)</div>
        </div>
    </div>

    <!-- Bill To / Customer Details Grid -->
    <div class="row g-4 mb-4 pb-3 border-bottom">
        <div class="col-sm-6">
            <h6 class="text-muted text-uppercase fs-7 fw-bold mb-2">Billed To (Customer Details):</h6>
            <h5 class="fw-bold text-dark mb-1"><?= e($po['display_customer_name']) ?></h5>
            <div class="small text-secondary">
                <?php if (!empty($po['display_customer_phone'])): ?>
                    <div><i class="bi bi-telephone me-1"></i> Phone: <strong><?= e($po['display_customer_phone']) ?></strong></div>
                <?php endif; ?>
                <?php if (!empty($po['customer_email'])): ?>
                    <div><i class="bi bi-envelope me-1"></i> Email: <?= e($po['customer_email']) ?></div>
                <?php endif; ?>
                <?php if (!empty($po['customer_address'])): ?>
                    <div><i class="bi bi-geo-alt me-1"></i> <?= e($po['customer_address']) ?></div>
                <?php endif; ?>
                <div><i class="bi bi-building me-1"></i> <?= e($po['customer_city'] ?? 'Tiruppur') ?><?= !empty($po['customer_state']) ? ', ' . e($po['customer_state']) : '' ?></div>
                <?php if (!empty($po['customer_gst'])): ?>
                    <div class="fw-bold text-dark mt-1 font-monospace">GSTIN: <?= e($po['customer_gst']) ?></div>
                <?php else: ?>
                    <div class="text-muted">GST: Unregistered Person (URP)</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-sm-6 text-sm-end">
            <h6 class="text-muted text-uppercase fs-7 fw-bold mb-2">Order &amp; Dispatch Status:</h6>
            <div class="mb-1">
                <span class="badge bg-<?= in_array($po['status'], ['COMPLETED', 'RECEIVED']) ? 'success' : 'primary' ?> px-3 py-1 fs-7">
                    <?= e($po['status']) ?>
                </span>
            </div>
            <?php if (!empty($po['notes'])): ?>
                <div class="small text-muted mt-2">
                    <strong>Order Notes:</strong> <?= e($po['notes']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Itemized Products Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 34%;">Product Description</th>
                    <th class="text-center" style="width: 10%;">Size / Color</th>
                    <th class="text-center" style="width: 7%;">Qty</th>
                    <th class="text-end" style="width: 11%;">Rate (₹)</th>
                    <th class="text-end" style="width: 9%;">Disc (₹)</th>
                    <th class="text-end" style="width: 11%;">Taxable (₹)</th>
                    <th class="text-center" style="width: 6%;">GST %</th>
                    <th class="text-end" style="width: 13%;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalQty = 0; 
                $calcGross = 0;
                $calcDisc = 0;
                $calcTaxable = 0;
                foreach ($items as $idx => $item): 
                    $qty = (int)$item['quantity'];
                    $totalQty += $qty; 
                    $billedRate = (float)$item['unit_price'];
                    $mrp = (float)($item['mrp'] ?? $billedRate);
                    if ($mrp <= 0) $mrp = $billedRate;
                    $lineGross = $qty * $mrp;
                    $lineTotal = (float)$item['total_amount'];
                    $lineDisc = max(0, round($lineGross - $lineTotal, 2));
                    $gstPct = (float)($item['gst_percentage'] ?? 5);
                    $lineTaxable = round($lineTotal / (1 + ($gstPct / 100)), 2);

                    $calcGross += $lineGross;
                    $calcDisc += $lineDisc;
                    $calcTaxable += $lineTaxable;
                ?>
                    <tr>
                        <td class="text-center text-muted"><?= $idx + 1 ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= e($item['product_name']) ?></div>
                            <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                <?php if (!empty($item['batch_lot_no'])): ?>
                                    <span class="badge bg-success font-monospace fs-8 text-white"><i class="bi bi-qr-code-scan me-1"></i>QR: <?= e($item['batch_lot_no']) ?></span>
                                <?php elseif (!empty($item['qr_code'])): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-8"><i class="bi bi-qr-code me-1"></i><?= e($item['qr_code']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center small text-secondary">
                            <?= e($item['size_name'] ?? 'Free') ?> / <?= e($item['color_name'] ?? 'Std') ?>
                        </td>
                        <td class="text-center fw-bold fs-6"><?= number_format($qty) ?></td>
                        <td class="text-end font-monospace">₹<?= number_format($billedRate, 2) ?></td>
                        <td class="text-end font-monospace text-danger"><?= $lineDisc > 0 ? '-₹' . number_format($lineDisc, 2) : '-' ?></td>
                        <td class="text-end font-monospace">₹<?= number_format($lineTaxable, 2) ?></td>
                        <td class="text-center font-monospace small"><?= $gstPct ?>%</td>
                        <td class="text-end fw-bold text-dark font-monospace">₹<?= number_format($lineTotal, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Financial Totals & Authorised Sign-off Footer -->
    <div class="row g-4 align-items-start mt-2">
        <div class="col-sm-6 ms-auto">
            <?php
            $displayGross = $calcGross > 0 ? $calcGross : ((float)$po['total_amount'] + (float)($po['discount_amount'] ?? 0));
            $displayDisc = (float)($po['discount_amount'] ?? 0) > 0 ? (float)$po['discount_amount'] : $calcDisc;
            $displayTaxable = (float)($po['subtotal'] ?? 0) > 0 ? (float)$po['subtotal'] : $calcTaxable;
            $totalTaxAmt = (float)($po['cgst_amount'] ?? 0) + (float)($po['sgst_amount'] ?? 0) + (float)($po['igst_amount'] ?? 0);
            ?>
            <div class="card border-0 bg-light p-3">
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">Gross Amount:</span>
                    <span class="fw-semibold font-monospace">₹<?= number_format($displayGross, 2) ?></span>
                </div>
                <?php if ($displayDisc > 0): ?>
                    <div class="d-flex justify-content-between mb-2 small text-danger">
                        <span>Total Discount:</span>
                        <span class="fw-semibold font-monospace">-₹<?= number_format($displayDisc, 2) ?></span>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">Taxable Base Value:</span>
                    <span class="fw-semibold font-monospace">₹<?= number_format($displayTaxable, 2) ?></span>
                </div>
                <?php if (($po['igst_amount'] ?? 0) > 0): ?>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">IGST (Inclusive):</span>
                        <span class="fw-semibold font-monospace">₹<?= number_format($po['igst_amount'], 2) ?></span>
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">CGST (Inclusive):</span>
                        <span class="fw-semibold font-monospace">₹<?= number_format($po['cgst_amount'] ?? ($totalTaxAmt / 2), 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">SGST (Inclusive):</span>
                        <span class="fw-semibold font-monospace">₹<?= number_format($po['sgst_amount'] ?? ($totalTaxAmt / 2), 2) ?></span>
                    </div>
                <?php endif; ?>
                <hr class="my-2">
                <div class="d-flex justify-content-between fs-5 fw-bold text-success">
                    <span>Net Total Payable:</span>
                    <span class="font-monospace">₹<?= number_format($po['total_amount'], 2) ?></span>
                </div>
            </div>

            <!-- Authorised Signature Box -->
            <div class="text-end mt-4 pt-3">
                <div class="small fw-bold text-dark">For GRM APPARELS &amp; TEXTILES</div>
                <div style="height: 40px;"></div>
                <div class="small text-muted border-top d-inline-block pt-1 px-3">Authorized Signatory</div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- PRINT STYLES -->
<!-- ========================================================================= -->
<style>
.invoice-container {
    max-width: 950px;
    margin: 0 auto;
}
.fs-7 { font-size: 0.85rem; }
.fs-8 { font-size: 0.75rem; }

@media print {
    .no-print, .page-header, .sidebar, .navbar, .breadcrumb, footer {
        display: none !important;
    }
    body {
        background: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .invoice-container {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        max-width: 100% !important;
    }
    .table-light {
        background-color: #f8f9fa !important;
    }
}
</style>
