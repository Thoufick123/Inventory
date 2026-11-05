<?php
// views/warehouse/purchases/return_invoice.php — Printable Return & Exchange Tax Slip
$return = $return ?? [];
$returnItems = $returnItems ?? [];
$exchange = $exchange ?? [];
$exchangeItems = $exchangeItems ?? [];

$isExchange = ($return['return_type'] ?? '') === 'EXCHANGE';
$diffAmount = (float)($exchange['difference_amount'] ?? 0);
$pAction = $exchange['payment_action'] ?? 'NONE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($return['return_number'] ?? 'Return Slip') ?> — GRM Warehouse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #212529;
        }
        .invoice-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            max-width: 850px;
            margin: 30px auto;
            padding: 40px;
        }
        .border-dashed {
            border-bottom: 2px dashed #dee2e6;
        }
        @media print {
            body { background: #fff; }
            .invoice-card { box-shadow: none; margin: 0; padding: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container py-4">
    <!-- Action Bar (Hidden on print) -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print" style="max-width: 850px; margin: 0 auto;">
        <a href="<?= url('warehouse/purchases/returns') ?>" class="btn btn-outline-secondary rounded-pill px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Returns Portal
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-printer me-1"></i> Print Tax Receipt
            </button>
        </div>
    </div>

    <!-- Printable Receipt Card -->
    <div class="invoice-card">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start border-dashed pb-4 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-dark text-white px-2.5 py-1 rounded-pill font-monospace" style="font-size: 11px;">
                        GRM INVENTORY MANAGEMENT
                    </span>
                    <span class="badge <?= $isExchange ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> rounded-pill px-2.5 py-1 fw-bold">
                        <?= $isExchange ? 'EXCHANGE RECEIPT' : 'RETURN TAX SLIP' ?>
                    </span>
                </div>
                <h3 class="fw-bold mb-0 text-dark"><?= e($return['warehouse_name'] ?? 'Central Distribution Warehouse') ?></h3>
                <p class="text-muted small mb-0"><?= e($return['warehouse_address'] ?? 'Warehouse Complex, Ring Road, Tirupur, Tamil Nadu') ?></p>
                <small class="text-secondary">Phone: <?= e($return['warehouse_phone'] ?? '+91 98765 00002') ?></small>
            </div>
            <div class="text-end">
                <h4 class="fw-bold text-primary font-monospace mb-1"><?= e($return['return_number']) ?></h4>
                <div class="small text-muted">Date: <strong><?= date('d M Y, h:i A', strtotime($return['created_at'])) ?></strong></div>
                <div class="small text-muted">Original PO: <strong class="text-dark font-monospace"><?= e($return['po_number'] ?? 'PO-Direct') ?></strong></div>
                <div class="small text-muted">Status: <span class="badge bg-success rounded-pill px-2 py-0.5">COMPLETED</span></div>
            </div>
        </div>

        <!-- Customer & Staff Info -->
        <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
            <div class="col-sm-6">
                <span class="text-muted text-uppercase small d-block font-monospace" style="font-size: 11px;">Customer Information</span>
                <strong class="text-dark fs-6 d-block"><?= e($return['customer_name'] ?? 'Direct Walk-in Customer') ?></strong>
                <span class="text-secondary small font-monospace d-block">Phone: <?= e($return['customer_phone'] ?? '-') ?></span>
                <?php if (!empty($return['customer_city'])): ?>
                <span class="text-muted small d-block"><?= e($return['customer_city']) ?>, <?= e($return['customer_state'] ?? '') ?></span>
                <?php endif; ?>
            </div>
            <div class="col-sm-6 text-sm-end">
                <span class="text-muted text-uppercase small d-block font-monospace" style="font-size: 11px;">Audit &amp; Authorization</span>
                <strong class="text-dark d-block">Authorized Staff: <?= e($return['inspected_by_name'] ?? 'Warehouse Supervisor') ?></strong>
                <span class="text-muted small d-block">Source: <strong>Warehouse Direct Purchase</strong></span>
                <span class="text-muted small d-block">Settlement: <strong><?= e($exchange['settlement_mode'] ?? $return['refund_mode'] ?? 'CASH') ?></strong></span>
            </div>
        </div>

        <!-- Section 1: Returned Items (Inward to Warehouse) -->
        <div class="mb-4">
            <h6 class="fw-bold text-danger mb-2 d-flex align-items-center gap-1">
                <i class="bi bi-arrow-down-left-circle-fill"></i>
                <span>Returned Items (Restocked / Inwarded)</span>
            </h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3">Item Description</th>
                            <th>SKU / Variant</th>
                            <th class="text-center" style="width: 80px;">Qty</th>
                            <th class="text-center" style="width: 110px;">Condition</th>
                            <th class="text-end" style="width: 110px;">Unit Rate</th>
                            <th class="text-end pe-3" style="width: 120px;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalReturnQty = 0;
                        foreach ($returnItems as $ri): 
                            $totalReturnQty += (int)$ri['quantity'];
                            $lineTotal = (float)$ri['unit_price'] * (int)$ri['quantity'];
                        ?>
                        <tr>
                            <td class="ps-3 fw-bold text-dark">
                                <div><?= e($ri['product_name']) ?></div>
                                <div class="d-flex flex-wrap gap-1 mt-0.5">
                                    <?php if (!empty($ri['product_code'])): ?>
                                        <span class="badge bg-light text-secondary border font-monospace" style="font-size:9px; padding:1px 4px;">
                                            Code: <?= e($ri['product_code']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($ri['reason']) && str_contains($ri['reason'], 'Piece Tags:')): ?>
                                        <span class="badge" style="background:#1e293b; color:#fbbf24; font-family:monospace; font-size:9px; padding:1px 5px;">
                                            <i class="bi bi-qr-code-scan me-1"></i><?= e($ri['reason']) ?>
                                        </span>
                                    <?php elseif (!empty($ri['qr_code'])): ?>
                                        <span class="badge bg-light text-primary border font-monospace" style="font-size:9px; padding:1px 4px;">
                                            <i class="bi bi-qr-code me-1"></i><?= e($ri['qr_code']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="font-monospace small"><?= e($ri['sku']) ?></span>
                                <small class="text-muted d-block"><?= e($ri['size_name']) ?> / <?= e($ri['color_name']) ?></small>
                            </td>
                            <td class="text-center fw-bold"><?= (int)$ri['quantity'] ?></td>
                            <td class="text-center">
                                <span class="badge <?= $ri['item_condition'] === 'GOOD' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> rounded-pill px-2 py-0.5" style="font-size: 10px;">
                                    <?= e($ri['item_condition']) ?>
                                </span>
                            </td>
                            <td class="text-end font-monospace">₹<?= number_format($ri['unit_price'], 2) ?></td>
                            <td class="text-end pe-3 fw-bold text-danger font-monospace">₹<?= number_format($lineTotal, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2" class="ps-3 fw-bold">Total Returned Value</td>
                            <td class="text-center fw-bold"><?= $totalReturnQty ?></td>
                            <td colspan="2"></td>
                            <td class="text-end pe-3 fw-bold text-danger font-monospace">
                                ₹<?= number_format($return['refund_amount'] ?? 0, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Section 2: Replacement Exchange Items (If Exchange) -->
        <?php if ($isExchange && !empty($exchangeItems)): ?>
        <div class="mb-4">
            <h6 class="fw-bold text-success mb-2 d-flex align-items-center gap-1">
                <i class="bi bi-arrow-up-right-circle-fill"></i>
                <span>Replacement Items Given to Customer</span>
            </h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3">Replacement Item Description</th>
                            <th>Size / Color / SKU</th>
                            <th class="text-center" style="width: 80px;">Qty</th>
                            <th class="text-end" style="width: 110px;">Unit Rate</th>
                            <th class="text-end pe-3" style="width: 120px;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalNewQty = 0;
                        foreach ($exchangeItems as $ei): 
                            if (($ei['item_type'] ?? '') !== 'NEW') continue;
                            $totalNewQty += (int)$ei['quantity'];
                        ?>
                        <tr>
                            <td class="ps-3 fw-bold text-dark">
                                <div><?= e($ei['product_name']) ?></div>
                                <div class="d-flex flex-wrap gap-1 mt-0.5">
                                    <?php if (!empty($ei['product_code']) || !empty($ei['sku'])): ?>
                                        <span class="badge bg-light text-secondary border font-monospace" style="font-size:9px; padding:1px 4px;">
                                            Code: <?= e($ei['product_code'] ?: $ei['sku']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($ei['piece_tag'])): ?>
                                        <span class="badge" style="background:#1e293b; color:#38bdf8; font-family:monospace; font-size:9px; padding:1px 5px;">
                                            <i class="bi bi-qr-code-scan me-1"></i>QR Code: <?= e($ei['piece_tag']) ?>
                                        </span>
                                    <?php elseif (!empty($ei['qr_code']) && $ei['qr_code'] !== $ei['sku'] && !str_starts_with($ei['qr_code'], 'QR-')): ?>
                                        <span class="badge bg-light text-primary border font-monospace" style="font-size:9px; padding:1px 4px;">
                                            <i class="bi bi-qr-code me-1"></i><?= e($ei['qr_code']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($ei['size_name']) ?> / <?= e($ei['color_name']) ?></span>
                                <small class="text-muted font-monospace d-block"><?= e($ei['sku']) ?></small>
                            </td>
                            <td class="text-center fw-bold"><?= (int)$ei['quantity'] ?></td>
                            <td class="text-end font-monospace">₹<?= number_format($ei['unit_price'], 2) ?></td>
                            <td class="text-end pe-3 fw-bold text-success font-monospace">₹<?= number_format($ei['total_amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2" class="ps-3 fw-bold">Total Replacement Value</td>
                            <td class="text-center fw-bold"><?= $totalNewQty ?></td>
                            <td></td>
                            <td class="text-end pe-3 fw-bold text-success font-monospace">
                                ₹<?= number_format($exchange['new_amount'] ?? 0, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Financial Summary Banner -->
        <div class="p-3 bg-light rounded-3 border mb-4">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="small text-muted mb-1">
                        <strong>Reason for Return/Swap:</strong> <?= e($return['reason'] ?? 'Customer request') ?>
                    </div>
                    <?php 
                    $paymentProofUrl = null;
                    $returnNotes = $return['notes'] ?? '';
                    if (preg_match('/Payment Proof:\s*(https?:\/\/[^\s]+)/', $returnNotes, $matches)) {
                        $paymentProofUrl = $matches[1];
                    }
                    ?>
                    <?php if ($paymentProofUrl): ?>
                    <div class="mt-2 no-print">
                        <strong class="small text-secondary d-block mb-1"><i class="bi bi-camera me-1"></i>Payment Proof Screenshot:</strong>
                        <a href="<?= e($paymentProofUrl) ?>" target="_blank">
                            <img src="<?= e($paymentProofUrl) ?>" alt="Payment Screenshot" class="img-thumbnail border-primary shadow-sm" style="max-height: 120px; border-radius: 8px;">
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-5 text-md-end">
                    <?php if ($isExchange): ?>
                        <div class="small text-muted">Replacement: <strong>₹<?= number_format($exchange['new_amount'] ?? 0, 2) ?></strong></div>
                        <div class="small text-muted mb-1">Returned: <strong>-₹<?= number_format($return['refund_amount'] ?? 0, 2) ?></strong></div>
                        <div class="border-top pt-1">
                            <?php if ($pAction === 'COLLECT' && $diffAmount > 0): ?>
                                <span class="text-uppercase small fw-bold text-warning-emphasis d-block">Amount Collected from Customer:</span>
                                <h4 class="fw-bold text-dark font-monospace mb-0">+₹<?= number_format($diffAmount, 2) ?></h4>
                                <small class="text-muted font-monospace d-block mt-0.5">Settlement Mode: <strong><?= e($exchange['settlement_mode'] ?? $return['refund_mode'] ?? 'CASH') ?></strong></small>
                            <?php elseif ($pAction === 'REFUND' && $diffAmount > 0): ?>
                                <span class="text-uppercase small fw-bold text-danger d-block">Amount Refunded to Customer:</span>
                                <h4 class="fw-bold text-danger font-monospace mb-0">-₹<?= number_format($diffAmount, 2) ?></h4>
                                <small class="text-muted font-monospace d-block mt-0.5">Settlement Mode: <strong><?= e($exchange['settlement_mode'] ?? $return['refund_mode'] ?? 'CASH') ?></strong></small>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 fs-6 fw-bold rounded-pill">
                                    Even Exchange (₹0.00)
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <span class="text-uppercase small fw-bold text-danger d-block">Net Refund Paid to Customer:</span>
                        <h3 class="fw-bold text-danger font-monospace mb-0">₹<?= number_format($return['refund_amount'] ?? 0, 2) ?></h3>
                        <small class="text-muted font-monospace">Mode: <?= e($return['refund_mode'] ?? 'CASH') ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer Signatures -->
        <div class="row pt-4 text-center">
            <div class="col-6">
                <div style="border-top: 1px solid #999; width: 180px; margin: 40px auto 5px auto;"></div>
                <span class="small text-muted">Customer Signature</span>
            </div>
            <div class="col-6">
                <div style="border-top: 1px solid #999; width: 180px; margin: 40px auto 5px auto;"></div>
                <span class="small text-muted">Authorized Warehouse Store Manager</span>
            </div>
        </div>

        <div class="text-center text-muted small mt-4 pt-3 border-top" style="font-size: 11px;">
            This is a computer generated return &amp; exchange voucher. Stock has been adjusted in real-time in the GRM Inventory System.
        </div>
    </div>
</div>

</body>
</html>
