<?php
// views/warehouse/purchases/return_view.php — Detailed Return & Exchange View
$return = $return ?? [];
$returnItems = $returnItems ?? [];
$exchange = $exchange ?? [];
$exchangeItems = $exchangeItems ?? [];

$isExchange = ($return['return_type'] ?? '') === 'EXCHANGE';
$diffAmount = (float)($exchange['difference_amount'] ?? 0);
$pAction = $exchange['payment_action'] ?? 'NONE';
?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?= url('warehouse/purchases/returns') ?>" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i> Returns &amp; Exchanges
                </a>
                <span class="badge <?= $isExchange ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> rounded-pill px-2.5 py-0.5 fw-bold font-monospace" style="font-size: 11px;">
                    <?= $isExchange ? 'EXCHANGE' : 'DIRECT RETURN' ?>
                </span>
            </div>
            <h2 class="fw-bold mb-0 text-dark">
                <?= e($return['return_number']) ?>
            </h2>
            <p class="text-muted small mb-0">Direct Purchase Order #<?= e($return['po_number'] ?? 'PO-Direct') ?> • Logged on <?= date('d M Y, h:i A', strtotime($return['created_at'])) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('warehouse/purchases/returns/invoice/' . $return['id']) ?>" target="_blank" class="btn btn-primary rounded-pill px-3 shadow-sm fw-semibold">
                <i class="bi bi-printer me-1"></i> Print Tax Slip
            </a>
            <a href="<?= url('warehouse/purchases/returns') ?>" class="btn btn-outline-secondary rounded-pill px-3 fw-semibold">
                Back to List
            </a>
        </div>
    </div>

    <!-- 3 Summary Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-3">
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Customer Information</span>
                <h5 class="fw-bold text-dark mb-1"><?= e($return['customer_name'] ?? 'Direct Customer') ?></h5>
                <span class="text-secondary small font-monospace d-block">Phone: <?= e($return['customer_phone'] ?? '-') ?></span>
                <span class="text-muted small d-block">Email: <?= e($return['customer_email'] ?? '-') ?></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-3">
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Original Purchase Order</span>
                <h5 class="fw-bold text-primary mb-1 font-monospace">
                    <a href="<?= url('warehouse/purchases/receive/' . ($return['purchase_order_id'] ?? 0)) ?>" class="text-decoration-none">
                        <?= e($return['po_number'] ?? 'PO-Direct') ?>
                    </a>
                </h5>
                <span class="text-secondary small d-block">PO Amount: <strong><?= formatCurrency($return['original_po_amount'] ?? 0) ?></strong></span>
                <span class="text-muted small d-block">Warehouse: <?= e($return['warehouse_name'] ?? 'Central WH') ?></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-3">
                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Financial Settlement</span>
                <?php 
                $settleMode = $exchange['settlement_mode'] ?? $return['refund_mode'] ?? 'CASH';
                $settleIcon = (stripos($settleMode, 'UPI') !== false) ? 'bi-qr-code-scan' : ((stripos($settleMode, 'SPLIT') !== false) ? 'bi-wallet2' : ((stripos($settleMode, 'BANK') !== false) ? 'bi-bank' : 'bi-cash'));
                ?>
                <?php if ($isExchange): ?>
                    <h5 class="fw-bold text-success mb-1">New: <?= formatCurrency($exchange['new_amount'] ?? 0) ?></h5>
                    <small class="text-danger d-block">Old Refund: -<?= formatCurrency($return['refund_amount'] ?? 0) ?></small>
                    <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                        <?php if ($pAction === 'COLLECT' && $diffAmount > 0): ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 fw-bold">
                                Collected Extra +<?= formatCurrency($diffAmount) ?>
                            </span>
                        <?php elseif ($pAction === 'REFUND' && $diffAmount > 0): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-bold">
                                Refunded Customer -<?= formatCurrency($diffAmount) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-bold">
                                Even Swap (₹0.00)
                            </span>
                        <?php endif; ?>
                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1 font-monospace">
                            <i class="bi <?= $settleIcon ?> me-1"></i>Mode: <?= e($settleMode) ?>
                        </span>
                    </div>
                <?php else: ?>
                    <h4 class="fw-bold text-danger mb-1 font-monospace">-<?= formatCurrency($return['refund_amount'] ?? 0) ?></h4>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1 font-monospace">
                        <i class="bi <?= $settleIcon ?> me-1"></i>Mode: <?= e($settleMode) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Returned Items Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-danger d-flex align-items-center gap-2">
                <i class="bi bi-arrow-down-left-circle-fill"></i>
                <span>Returned Items (Inwarded to Warehouse)</span>
            </h6>
            <span class="badge bg-danger-subtle text-danger rounded-pill px-2.5">
                <?= count($returnItems) ?> items
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                    <tr>
                        <th class="ps-4">Product Style</th>
                        <th>Variant (Size / Color)</th>
                        <th>SKU</th>
                        <th class="text-center">Returned Qty</th>
                        <th class="text-center">Condition</th>
                        <th>Unit Rate</th>
                        <th class="text-end pe-4">Line Refund</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($returnItems as $ri): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark">
                            <div><?= e($ri['product_name']) ?></div>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <?php if (!empty($ri['product_code'])): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace" style="font-size: 10px;">
                                        <i class="bi bi-tag-fill me-1"></i><?= e($ri['product_code']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($ri['reason']) && str_contains($ri['reason'], 'Piece Tags:')): ?>
                                    <span class="badge" style="background:#1e293b; color:#fbbf24; font-family:monospace; font-size:10px;">
                                        <i class="bi bi-qr-code-scan me-1"></i><?= e($ri['reason']) ?>
                                    </span>
                                <?php elseif (!empty($ri['qr_code'])): ?>
                                    <span class="badge bg-primary-subtle text-primary border font-monospace" style="font-size: 10px;">
                                        <i class="bi bi-qr-code me-1"></i><?= e($ri['qr_code']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= e($ri['size_name']) ?> / <?= e($ri['color_name']) ?></span>
                        </td>
                        <td class="font-monospace text-muted small"><?= e($ri['sku']) ?></td>
                        <td class="text-center fw-bold"><?= (int)$ri['quantity'] ?></td>
                        <td class="text-center">
                            <span class="badge <?= $ri['item_condition'] === 'GOOD' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> rounded-pill px-2.5 py-1 fw-semibold">
                                <?= e($ri['item_condition']) ?>
                            </span>
                        </td>
                        <td class="font-monospace">₹<?= number_format($ri['unit_price'], 2) ?></td>
                        <td class="text-end pe-4 font-monospace fw-bold text-danger">
                            ₹<?= number_format($ri['unit_price'] * $ri['quantity'], 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Replacement Exchange Items Table (If Exchange) -->
    <?php if ($isExchange && !empty($exchangeItems)): ?>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-success d-flex align-items-center gap-2">
                <i class="bi bi-arrow-up-right-circle-fill"></i>
                <span>Replacement Items Handed Over to Customer</span>
            </h6>
            <span class="badge bg-success-subtle text-success rounded-pill px-2.5">
                Debited from Warehouse Available Stock
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                    <tr>
                        <th class="ps-4">Product Style</th>
                        <th>Variant (Size / Color)</th>
                        <th>SKU</th>
                        <th class="text-center">Replacement Qty</th>
                        <th>Unit Rate</th>
                        <th class="text-end pe-4">Line Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exchangeItems as $ei): ?>
                    <?php if (($ei['item_type'] ?? '') !== 'NEW') continue; ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark">
                            <div><?= e($ei['product_name']) ?></div>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <?php if (!empty($ei['product_code'])): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace" style="font-size: 10px;">
                                        <i class="bi bi-tag-fill me-1"></i><?= e($ei['product_code']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($ei['piece_tag'])): ?>
                                    <span class="badge" style="background:#1e293b; color:#38bdf8; font-family:monospace; font-size:10px;">
                                        <i class="bi bi-qr-code-scan me-1"></i>Piece Tag: <?= e($ei['piece_tag']) ?>
                                    </span>
                                <?php elseif (!empty($ei['qr_code']) && $ei['qr_code'] !== $ei['sku'] && !str_starts_with($ei['qr_code'], 'QR-')): ?>
                                    <span class="badge bg-primary-subtle text-primary border font-monospace" style="font-size: 10px;">
                                        <i class="bi bi-qr-code me-1"></i><?= e($ei['qr_code']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= e($ei['size_name']) ?> / <?= e($ei['color_name']) ?></span>
                        </td>
                        <td class="font-monospace text-muted small"><?= e($ei['sku']) ?></td>
                        <td class="text-center fw-bold"><?= (int)$ei['quantity'] ?></td>
                        <td class="font-monospace">₹<?= number_format($ei['unit_price'], 2) ?></td>
                        <td class="text-end pe-4 font-monospace fw-bold text-success">
                            ₹<?= number_format($ei['total_amount'], 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
