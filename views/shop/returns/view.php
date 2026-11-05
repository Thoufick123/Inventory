<?php
// views/shop/returns/view.php — Complete Return / Exchange Record View
$isExchange = (($return['return_type'] ?? '') === 'EXCHANGE');
$grandRefund = (float)($return['refund_amount'] ?? 0);
if ($grandRefund <= 0 && !empty($items)) {
    $grandRefund = array_sum(array_column($items, 'calculated_refund'));
}
?>
<div class="container-fluid py-4">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center">
            <a href="<?= url('shop/returns') ?>" class="btn btn-sm btn-light border rounded-circle me-3" title="Back to Returns">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <?php if ($isExchange): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-arrow-left-right me-1"></i>Product Exchange</span>
                    <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-arrow-counterclockwise me-1"></i>Customer Return</span>
                    <?php endif; ?>
                    <span class="badge bg-success-subtle text-success"><?= e($return['inspection_status'] ?? 'APPROVED') ?></span>
                </div>
                <h3 class="fw-bold mb-0 text-dark"><?= e($return['return_number']) ?></h3>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <?php if ($grandRefund > 0 && (hasPermission('pos', 'create') || hasPermission('pos', 'view'))): ?>
                <a href="<?= url('shop/pos?credit=' . number_format($grandRefund, 2, '.', '') . '&cust=' . urlencode($return['customer_name'] ?? '') . '&phone=' . urlencode($return['customer_phone'] ?? '') . '&return_ref=' . urlencode($return['return_number'])) ?>" class="btn btn-success rounded-pill px-3 btn-sm fw-bold shadow-sm">
                    <i class="bi bi-arrow-left-right me-1"></i> Issue Exchange in POS (₹<?= number_format($grandRefund, 2) ?>)
                </a>
            <?php endif; ?>
            <?php if (hasPermission('pos', 'print') || hasPermission('sales', 'view') || hasPermission('sales', 'print')): ?>
            <a href="<?= url('shop/pos/invoice/' . $return['sale_id']) ?>" target="_blank" class="btn btn-outline-primary rounded-pill px-3 btn-sm">
                <i class="bi bi-receipt me-1"></i> Original Invoice #<?= e($return['invoice_number']) ?>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('shop_returns', 'print')): ?>
            <a href="<?= url('shop/returns/invoice/' . $return['id']) ?>" target="_blank" class="btn btn-primary rounded-pill px-4 btn-sm shadow-sm">
                <i class="bi bi-printer-fill me-1"></i> Print Voucher / Slip
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row g-3 mb-4">
        <!-- Customer & Invoice Info -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="bi bi-person me-1 text-primary"></i> Customer & Invoice Info
                    </h6>
                    <div class="mb-2">
                        <small class="text-muted d-block">Customer Name</small>
                        <strong class="text-dark fs-6"><?= e($return['customer_name'] ?? 'Walk-in Customer') ?></strong>
                    </div>
                    <?php if (!empty($return['customer_phone'])): ?>
                    <div class="mb-2">
                        <small class="text-muted d-block">Mobile Number</small>
                        <span class="font-monospace text-dark fw-semibold"><?= e($return['customer_phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="mb-2">
                        <small class="text-muted d-block">Original Invoice #</small>
                        <a href="<?= url('shop/pos/invoice/' . $return['sale_id']) ?>" target="_blank" class="fw-bold font-monospace text-primary text-decoration-none">
                            <?= e($return['invoice_number']) ?> <i class="bi bi-box-arrow-up-right small"></i>
                        </a>
                    </div>
                    <div>
                        <small class="text-muted d-block">Sale Date</small>
                        <span class="text-dark small"><?= formatDateTime($return['sale_date']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Return & Refund Summary -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="bi bi-cash-stack me-1 text-success"></i> Settlement & Inspection
                    </h6>
                    <div class="mb-2">
                        <small class="text-muted d-block">Amount Value / Credit</small>
                        <div class="fs-4 fw-bold <?= $isExchange ? 'text-primary' : 'text-danger' ?>">₹<?= number_format($grandRefund, 2) ?></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <small class="text-muted d-block">Mode</small>
                            <span class="badge bg-secondary"><?= e($return['refund_mode'] ?? 'CASH') ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge bg-success-subtle text-success"><?= e($return['refund_status'] ?? 'COMPLETED') ?></span>
                        </div>
                    </div>
                    <div>
                        <small class="text-muted d-block">Reason / Notes</small>
                        <span class="badge bg-warning-subtle text-warning-emphasis fw-semibold"><?= e($return['reason']) ?></span>
                        <?php if (!empty($return['notes'])): ?>
                            <div class="small text-muted mt-1"><?= e($return['notes']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit & Processing Info -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="bi bi-shield-check me-1 text-info"></i> Processing & Counter
                    </h6>
                    <div class="mb-2">
                        <small class="text-muted d-block">Processed By Staff</small>
                        <strong class="text-dark"><?= e($return['staff_name'] ?? 'System Administrator') ?></strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Shop / Counter</small>
                        <span class="text-dark small fw-semibold"><?= e($return['shop_name'] ?? 'Flagship Store') ?></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Processed Date & Time</small>
                        <span class="text-dark small"><?= formatDateTime($return['created_at']) ?></span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Items Handled</small>
                        <span class="badge bg-dark rounded-pill px-3"><?= (int)$totalReturnedQty ?> Piece(s)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Itemized Returned Products List -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0">
                <i class="bi bi-box-seam me-2 text-primary"></i>Returned Items Breakdown
            </h5>
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3"><?= count($items) ?> Item(s)</span>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 5%;">#</th>
                        <th style="width: 35%;">Product & SKU Details</th>
                        <th class="text-center" style="width: 10%;">Returned Qty</th>
                        <th class="text-end" style="width: 12%;">Original Rate</th>
                        <th class="text-end" style="width: 13%;">Total Value</th>
                        <th style="width: 12%;">Item Condition</th>
                        <th class="text-end pe-4" style="width: 13%;">Restock Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $idx = 1; foreach ($items as $item): ?>
                        <tr>
                            <td class="ps-4 text-muted fw-bold"><?= $idx++ ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?= e($item['image']) ?>" alt="" class="rounded-3 border me-3" style="width: 46px; height: 46px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-3 bg-light border me-3 d-flex align-items-center justify-content-center text-secondary" style="width: 46px; height: 46px; min-width: 46px;">
                                            <i class="bi bi-bag fs-5"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0"><?= e($item['product_name']) ?></h6>
                                        <div class="small text-muted font-monospace d-flex flex-wrap align-items-center gap-1 mt-1">
                                            <span class="badge bg-light text-dark border"><i class="bi bi-qr-code text-primary me-1"></i><?= e($item['sku']) ?></span>
                                            <?= !empty($item['size_name']) ? '<span class="badge bg-light text-dark border">' . e($item['size_name']) . '</span>' : '' ?>
                                            <?= !empty($item['color_name']) ? '<span class="badge bg-light text-dark border">' . e($item['color_name']) . '</span>' : '' ?>
                                            <?php if (!empty($item['scanned_qr_codes'])): ?>
                                                <span class="badge bg-dark text-white"><i class="bi bi-upc-scan me-1"></i>Piece: <?= e($item['scanned_qr_codes']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill px-3 fs-6"><?= (int)$item['quantity'] ?></span>
                            </td>
                            <td class="text-end fw-semibold text-dark">₹<?= number_format((float)$item['unit_price'], 2) ?></td>
                            <td class="text-end fw-bold text-danger">₹<?= number_format((float)$item['calculated_refund'], 2) ?></td>
                            <td>
                                <?php if ($item['item_condition'] === 'GOOD'): ?>
                                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                                        <i class="bi bi-check-circle me-1"></i> Good Condition
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill">
                                        <i class="bi bi-exclamation-triangle me-1"></i> <?= e($item['item_condition']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($item['destination'] === 'AVAILABLE' || $item['item_condition'] === 'GOOD'): ?>
                                    <span class="badge bg-info-subtle text-info-emphasis px-3 py-2 rounded-pill">
                                        <i class="bi bi-arrow-return-left me-1"></i> Restocked in Shop
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2 rounded-pill">
                                        <i class="bi bi-shield-x me-1"></i> Damaged Quarantine
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- If Same-Product Exchange: Display Exchanged Replacement Items -->
    <?php if (!empty($exchangeItems)): ?>
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-arrow-left-right me-2 text-success"></i>Issued Replacement Variants (Deducted from Shop Stock)
                </h5>
                <span class="badge bg-success-subtle text-success rounded-pill px-3">Same-Product Swap</span>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Type</th>
                            <th>Product &amp; SKU</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end pe-4">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exchangeItems as $ei): ?>
                            <tr>
                                <td class="ps-4">
                                    <?php if ($ei['item_type'] === 'NEW'): ?>
                                        <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill"><i class="bi bi-arrow-up-right me-1"></i>NEW (ISSUED)</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill"><i class="bi bi-arrow-down-left me-1"></i>OLD (RETURNED)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($ei['product_name']) ?></div>
                                    <div class="small text-muted font-monospace d-flex flex-wrap align-items-center gap-1 mt-1">
                                        <span class="badge bg-light text-dark border"><i class="bi bi-qr-code text-primary me-1"></i><?= e($ei['sku']) ?></span> 
                                        <?= !empty($ei['size_name']) ? '<span class="badge bg-light text-dark border">' . e($ei['size_name']) . '</span>' : '' ?>
                                        <?= !empty($ei['color_name']) ? '<span class="badge bg-light text-dark border">' . e($ei['color_name']) . '</span>' : '' ?>
                                    </div>
                                </td>
                                <td class="text-center fw-bold"><?= (int)$ei['quantity'] ?></td>
                                <td class="text-end">₹<?= number_format((float)$ei['unit_price'], 2) ?></td>
                                <td class="text-end pe-4 fw-bold <?= $ei['item_type'] === 'NEW' ? 'text-success' : 'text-danger' ?>">
                                    ₹<?= number_format((float)$ei['total_amount'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
