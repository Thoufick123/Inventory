<style>
/* Invoice View & Print Styling */
.invoice-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
}

.invoice-header-banner {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    border-radius: 12px;
    padding: 20px 24px;
}

.invoice-table th {
    background-color: #f8fafc !important;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e2e8f0;
    padding: 10px 12px;
}

.invoice-table td {
    padding: 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}

.summary-table td {
    padding: 6px 12px;
    font-size: 13px;
}

@media print {
    /* Hide Non-Print Elements */
    .sidebar, #sidebar, .top-navbar, header, footer, .navbar, .no-print, .btn, .breadcrumb, nav[aria-label="breadcrumb"], .alert, .flash-alert {
        display: none !important;
    }

    /* Full Page Reset */
    html, body, .app-wrapper, .main-content, .page-content, .container, .container-fluid {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        box-shadow: none !important;
    }

    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    /* Print Header & Banner */
    .invoice-header-banner {
        background: #f8fafc !important;
        color: #0f172a !important;
        border: 1.5px solid #cbd5e1 !important;
        padding: 14px 18px !important;
        margin-bottom: 14px !important;
    }

    .invoice-header-banner * {
        color: #0f172a !important;
    }

    .row {
        display: flex !important;
        flex-wrap: wrap !important;
        margin-left: -6px !important;
        margin-right: -6px !important;
    }

    .col-md-6 {
        width: 50% !important;
        padding-left: 6px !important;
        padding-right: 6px !important;
    }

    .col-12 {
        width: 100% !important;
        padding-left: 6px !important;
        padding-right: 6px !important;
    }

    .invoice-card {
        border: 1px solid #cbd5e1 !important;
        box-shadow: none !important;
        margin-bottom: 12px !important;
        border-radius: 6px !important;
        page-break-inside: avoid;
    }

    /* Full Width Items Table in Print */
    .table-responsive {
        overflow: visible !important;
        width: 100% !important;
    }

    .table {
        width: 100% !important;
        font-size: 9pt !important;
        border-collapse: collapse !important;
        margin-bottom: 0 !important;
        table-layout: auto !important;
    }

    .table th {
        background-color: #f1f5f9 !important;
        color: #000000 !important;
        border: 1px solid #cbd5e1 !important;
        padding: 6px 8px !important;
        font-size: 8.5pt !important;
    }

    .table td {
        border: 1px solid #cbd5e1 !important;
        padding: 6px 8px !important;
        color: #000000 !important;
        font-size: 9pt !important;
    }

    .badge {
        border: 1px solid #94a3b8 !important;
        color: #000000 !important;
        background: transparent !important;
        padding: 2px 4px !important;
        font-size: 7.5pt !important;
    }

    .text-primary, .text-success, .text-danger {
        color: #000000 !important;
    }

    .fw-bold, .fw-bolder {
        font-weight: 700 !important;
    }
}
</style>

<div class="container-fluid py-3">
    <!-- Action Bar (Hidden in Print) -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary px-3 py-2 rounded-pill font-monospace fs-6">
                <i class="bi bi-globe me-1"></i> Website Order
            </span>
            <span class="text-muted small">Recorded on <?= date('d M Y, h:i A', strtotime($sale['created_at'])) ?></span>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary px-4 fw-semibold shadow-sm">
                <i class="bi bi-printer me-1"></i> Print Invoice
            </button>
            <a href="<?= url('warehouse/website-orders') ?>?tab=orders" class="btn btn-outline-secondary px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to Website Channel
            </a>
        </div>
    </div>

    <!-- Invoice Header Banner -->
    <div class="invoice-header-banner mb-3 shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-receipt fs-4 text-info"></i>
                    <h3 class="fw-bold mb-0 text-white font-monospace">#<?= htmlspecialchars($sale['invoice_number']) ?></h3>
                    <span class="badge bg-success bg-opacity-75 text-white px-2 py-1 ms-2 font-monospace">
                        <?= htmlspecialchars($sale['status']) ?>
                    </span>
                    <span class="badge bg-info bg-opacity-75 text-white px-2 py-1 font-monospace">
                        <?= htmlspecialchars($sale['payment_status'] ?? 'PAID') ?>
                    </span>
                </div>
                <small class="text-light opacity-75">Order Date &amp; Time: <?= date('d M Y, h:i A', strtotime($sale['created_at'])) ?></small>
            </div>
            <div class="col-md-5 text-md-end mt-2 mt-md-0">
                <small class="text-uppercase tracking-wider opacity-75 d-block text-white" style="font-size: 11px;">Sales Channel</small>
                <h5 class="fw-bold text-info mb-0">Website E-Commerce Portal</h5>
            </div>
        </div>
    </div>

    <!-- Customer & Biller Details (Side-by-Side Cards) -->
    <div class="row g-3 mb-3">
        <!-- Customer Details -->
        <div class="col-md-6 col-12">
            <div class="invoice-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                    <i class="bi bi-person-circle fs-5 text-primary"></i>
                    <h6 class="fw-bold mb-0 text-dark">Customer Details</h6>
                </div>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <small class="text-muted d-block" style="font-size: 11px;">Customer Name</small>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($sale['customer_name'] ?? 'Direct Online Buyer') ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block" style="font-size: 11px;">Customer Phone</small>
                        <span class="fw-bold text-dark font-monospace"><?= htmlspecialchars($sale['customer_phone'] ?? 'N/A') ?></span>
                    </div>
                    <div class="col-12 mt-1">
                        <small class="text-muted d-block" style="font-size: 11px;">Delivery Address</small>
                        <span class="text-dark small"><?= nl2br(htmlspecialchars($sale['customer_address'] ?? $sale['customer_db_address'] ?? 'Not provided')) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff & Biller Details -->
        <div class="col-md-6 col-12">
            <div class="invoice-card p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                    <i class="bi bi-person-badge fs-5 text-primary"></i>
                    <h6 class="fw-bold mb-0 text-dark">Staff &amp; Biller Details</h6>
                </div>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <small class="text-muted d-block" style="font-size: 11px;">Biller Name</small>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($sale['biller_name'] ?? 'System Administrator') ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block" style="font-size: 11px;">Payment Method</small>
                        <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($sale['payment_mode'] ?? 'ONLINE') ?></span>
                    </div>
                    <?php if (!empty($sale['employee_name'])): ?>
                        <div class="col-sm-6 mt-1">
                            <small class="text-muted d-block" style="font-size: 11px;">Sales Representative</small>
                            <span class="text-dark fw-semibold"><?= htmlspecialchars($sale['employee_name']) ?> <?= !empty($sale['employee_code']) ? '(' . htmlspecialchars($sale['employee_code']) . ')' : '' ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="col-12 mt-1">
                        <small class="text-muted d-block" style="font-size: 11px;">Order Notes</small>
                        <span class="text-muted small"><?= htmlspecialchars($sale['notes'] ?: 'Website E-Commerce Order') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Full-Width Ordered Items Table -->
    <div class="invoice-card mb-3 overflow-hidden">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-boxes me-2 text-primary"></i>Ordered Items &amp; Products
            </h6>
            <span class="badge bg-white text-dark border font-monospace"><?= count($items) ?> Item(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table invoice-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 22%;">QR / SKU</th>
                        <th style="width: 15%;">Category</th>
                        <th style="width: 28%;">Product Name</th>
                        <th style="width: 10%;">Size</th>
                        <th style="width: 8%;" class="text-center">Quantity</th>
                        <th style="width: 12%;" class="text-end">Unit Price</th>
                        <th style="width: 12%;" class="text-end">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $seqIdx = 1;
                    foreach ($items as $itm): 
                    ?>
                        <tr>
                            <td class="text-muted fw-semibold"><?= $seqIdx++ ?></td>
                            <td>
                                <?php if (!empty($itm['scanned_qr_codes'])): ?>
                                    <?php $qrs = array_filter(array_map('trim', explode(',', $itm['scanned_qr_codes']))); ?>
                                    <div class="d-flex flex-wrap gap-1 align-items-center mb-1">
                                        <?php foreach ($qrs as $qrTag): ?>
                                            <span class="badge bg-dark text-white font-monospace" style="font-size: 0.75rem; padding: 3px 6px;">
                                                <i class="bi bi-qr-code me-1 text-warning"></i><?= htmlspecialchars($qrTag) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.75rem;">
                                    SKU: <?= htmlspecialchars($itm['sku']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($itm['category_name'] ?? 'Apparel') ?>
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($itm['product_name']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
                                    <?= htmlspecialchars($itm['size_name'] ?? 'Standard') ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold fs-6 text-dark"><?= number_format($itm['quantity']) ?></span> <small class="text-muted">pcs</small>
                            </td>
                            <td class="text-end fw-semibold text-dark">
                                ₹<?= number_format($itm['unit_price'], 2) ?>
                            </td>
                            <td class="text-end fw-bold text-primary fs-6">
                                ₹<?= number_format($itm['total_amount'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Financial Summary Breakdown Card (Full Width with Right-Aligned Calculation Block) -->
    <div class="invoice-card p-3 mb-3">
        <div class="row g-3 align-items-center">
            <div class="col-md-6 col-12">
                <div class="p-3 bg-light rounded-3 h-100">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle me-1 text-primary"></i>Tax &amp; Order Information</h6>
                    <div class="small text-muted mb-1">
                        <span>GST Applied: <strong><?= number_format($sale['gst_percentage'] ?? 5, 1) ?>% Inclusive</strong></span>
                        <span class="ms-2">| CGST: <strong>₹<?= number_format($sale['cgst_amount'] ?? (($sale['gst_amount'] ?? 0) / 2), 2) ?></strong></span>
                        <span class="ms-2">| SGST: <strong>₹<?= number_format($sale['sgst_amount'] ?? (($sale['gst_amount'] ?? 0) / 2), 2) ?></strong></span>
                    </div>
                    <div class="small text-muted">
                        Payment Mode: <strong class="text-dark"><?= htmlspecialchars($sale['payment_mode'] ?? 'ONLINE') ?></strong> | Status: <strong class="text-success"><?= htmlspecialchars($sale['payment_status'] ?? 'PAID') ?></strong>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-12">
                <div class="d-flex justify-content-md-end">
                    <table class="summary-table w-100" style="max-width: 360px;">
                        <tr>
                            <td class="text-muted fw-semibold">Taxable Subtotal:</td>
                            <td class="text-end fw-bold text-dark">₹<?= number_format($sale['subtotal'] ?? $sale['total_amount'], 2) ?></td>
                        </tr>
                        <?php if (($sale['discount_amount'] ?? 0) > 0): ?>
                            <tr>
                                <td class="text-danger fw-semibold">Discount:</td>
                                <td class="text-end fw-bold text-danger">-₹<?= number_format($sale['discount_amount'], 2) ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-muted fw-semibold">GST (<?= number_format($sale['gst_percentage'] ?? 5, 1) ?>% Incl.):</td>
                            <td class="text-end fw-semibold text-dark">₹<?= number_format($sale['gst_amount'] ?? 0, 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Shipping Cost:</td>
                            <td class="text-end fw-semibold text-dark">₹<?= number_format($sale['shipping_cost'] ?? 0, 2) ?></td>
                        </tr>
                        <tr class="border-top border-2">
                            <td class="pt-2 text-primary fw-bold text-uppercase fs-6">Net Payable Amount:</td>
                            <td class="pt-2 text-end fw-bolder text-primary fs-4 font-monospace">₹<?= number_format($sale['total_amount'], 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
