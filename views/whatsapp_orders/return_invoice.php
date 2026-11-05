<div class="container-fluid py-3">
    <!-- Action Bar (Hidden on Print) -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <a href="<?= url('warehouse/whatsapp-orders/returns') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to WhatsApp Returns
            </a>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer me-1"></i> Print Return / Exchange Slip
            </button>
        </div>
    </div>

    <!-- Printable Slip Card -->
    <div class="card border shadow-sm mx-auto p-4 bg-white" style="max-width: 880px;" id="printableSlip">
        <!-- Header -->
        <div class="row align-items-center pb-3 mb-3 border-bottom">
            <div class="col-sm-7">
                <h3 class="fw-bold mb-0 text-success"><i class="bi bi-whatsapp me-2"></i>GRM INVENTORY</h3>
                <div class="text-muted small">Warehouse Channel Fulfillment &amp; Sales Management</div>
                <div class="small text-secondary mt-1">
                    GSTIN: <strong>33AAAAA0000A1Z5</strong> | Phone: +91 98765 43210
                </div>
            </div>
            <div class="col-sm-5 d-flex align-items-center justify-content-sm-end gap-3 mt-2 mt-sm-0">
                <div class="qr-canvas-box bg-white p-1 border rounded shadow-sm d-flex align-items-center justify-content-center" data-qr="<?= htmlspecialchars($return['return_number']) ?>" style="width: 68px; height: 68px;" title="Slip QR: <?= htmlspecialchars($return['return_number']) ?>"></div>
                <div class="text-sm-end">
                    <div class="badge bg-dark fs-6 px-3 py-1 mb-1">
                        <?= ($return['return_type'] === 'EXCHANGE') ? 'EXCHANGE &amp; RETURN SLIP' : 'CUSTOMER RETURN INVOICE' ?>
                    </div>
                    <div class="fw-bold text-dark fs-5 font-monospace">#<?= htmlspecialchars($return['return_number']) ?></div>
                    <div class="small text-muted">Date: <?= date('d M Y, h:i A', strtotime($return['created_at'])) ?></div>
                </div>
            </div>
        </div>

        <!-- Meta Information Grid -->
        <div class="row g-3 mb-4 p-3 bg-light rounded-3">
            <div class="col-6 col-md-3">
                <small class="text-muted d-block text-uppercase" style="font-size: 0.75rem;">Original Sale</small>
                <strong class="text-dark font-monospace"><?= htmlspecialchars($return['sale_invoice'] ?? $return['invoice_number'] ?? '-') ?></strong>
                <div class="small text-muted"><?= !empty($return['sale_date']) ? date('d M Y', strtotime($return['sale_date'])) : (!empty($return['sale_created_at']) ? date('d M Y', strtotime($return['sale_created_at'])) : date('d M Y', strtotime($return['created_at'] ?? 'now'))) ?></div>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block text-uppercase" style="font-size: 0.75rem;">Customer Name</small>
                <strong class="text-dark"><?= htmlspecialchars($return['customer_name']) ?></strong>
                <div class="small text-muted font-monospace"><?= htmlspecialchars($return['customer_phone']) ?></div>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block text-uppercase" style="font-size: 0.75rem;">Return Mode</small>
                <span class="badge <?= ($return['return_type'] === 'EXCHANGE') ? 'bg-purple' : 'bg-primary' ?>">
                    <?= htmlspecialchars($return['return_type']) ?>
                </span>
                <div class="small text-muted font-monospace"><?= htmlspecialchars($return['refund_mode'] ?? 'CASH') ?></div>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block text-uppercase" style="font-size: 0.75rem;">Processed By</small>
                <strong class="text-dark"><?= htmlspecialchars($return['processor_name'] ?? 'Warehouse Staff') ?></strong>
                <div class="small text-success"><i class="bi bi-patch-check-fill me-1"></i>VERIFIED</div>
            </div>
        </div>

        <!-- Returned Items Table -->
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">
            <i class="bi bi-box-arrow-in-left me-1 text-danger"></i> Returned Product Items
        </h6>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="text-center" style="width: 35px;">#</th>
                        <th style="width: 85px;" class="text-center">Product QR</th>
                        <th>Product &amp; SKU</th>
                        <th>Variant (Size / Color)</th>
                        <th>Condition</th>
                        <th class="text-center" style="width: 55px;">Qty</th>
                        <th class="text-end" style="width: 100px;">Unit Price</th>
                        <th class="text-end" style="width: 110px;">Refund Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sn = 1;
                    $sumReturnedVal = 0.0;
                    foreach ($items as $it): 
                        $val = (float)($it['refund_amount'] ?? 0);
                        $sumReturnedVal += $val;
                        $firstTag = '';
                        if (!empty($it['sale_piece_tags'])) {
                            $pts = array_filter(array_map('trim', explode(',', $it['sale_piece_tags'])));
                            $firstTag = reset($pts) ?: '';
                        }
                        $qrCodeVal = $firstTag ?: (!empty($it['qr_code']) ? $it['qr_code'] : ($it['sku'] ?? ''));
                    ?>
                        <tr>
                            <td class="text-center text-muted"><?= $sn++ ?></td>
                            <td class="text-center align-middle bg-light-subtle">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <div class="qr-canvas-box bg-white p-1 border rounded shadow-sm" data-qr="<?= htmlspecialchars($qrCodeVal) ?>" style="width: 54px; height: 54px;" title="QR: <?= htmlspecialchars($qrCodeVal) ?>"></div>
                                    <span class="font-monospace text-dark fw-bold mt-1" style="font-size: 9.5px; line-height: 1; word-break: break-all;"><?= htmlspecialchars($qrCodeVal) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark" style="font-size: 13px;"><?= htmlspecialchars($it['product_name']) ?></div>
                                <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                    <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 10.5px;">SKU: <?= htmlspecialchars($it['sku'] ?? '-') ?></span>
                                    <?php if (!empty($it['product_code'])): ?>
                                        <span class="badge bg-light text-dark border font-monospace" style="font-size: 10.5px;">Code: <?= htmlspecialchars($it['product_code']) ?></span>
                                    <?php endif; ?>
                                    <?php 
                                    $tags = !empty($it['sale_piece_tags']) ? array_filter(array_map('trim', explode(',', $it['sale_piece_tags']))) : [];
                                    foreach ($tags as $t): ?>
                                        <span class="badge bg-dark text-warning font-monospace" style="font-size: 10px;"><i class="bi bi-qr-code me-1"></i>Piece: <?= htmlspecialchars($t) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($it['size_name'] ?? '-') ?></span>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($it['color_name'] ?? '-') ?></span>
                            </td>
                            <td>
                                <?php if ($it['item_condition'] === 'DAMAGED'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">DAMAGED (QC)</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">GOOD (Restocked)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold"><?= (int)$it['quantity'] ?></td>
                            <td class="text-end font-monospace">₹<?= number_format($val / max(1, (int)$it['quantity']), 2) ?></td>
                            <td class="text-end fw-bold text-danger font-monospace">₹<?= number_format($val, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="7" class="text-end fw-bold">Total Returned Items Value:</td>
                        <td class="text-end fw-bold text-danger fs-6 font-monospace">₹<?= number_format($sumReturnedVal, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- If Exchange: Replacement Items Table -->
        <?php if (!empty($exchangeItems)): ?>
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">
                <i class="bi bi-arrow-repeat me-1 text-success"></i> Replacement Items (Exchange)
            </h6>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="text-center" style="width: 50px;">Type</th>
                            <th style="width: 85px;" class="text-center">Product QR</th>
                            <th>Product &amp; SKU</th>
                            <th>Variant (Size / Color)</th>
                            <th class="text-center" style="width: 55px;">Qty</th>
                            <th class="text-end" style="width: 100px;">Unit Price</th>
                            <th class="text-end" style="width: 110px;">Replacement Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($exchangeItems as $ei): 
                            if ($ei['item_type'] !== 'NEW') continue;
                            $swapQrCodeVal = !empty($ei['piece_tag']) ? $ei['piece_tag'] : (!empty($ei['qr_code']) ? $ei['qr_code'] : ($ei['sku'] ?? ''));
                        ?>
                            <tr>
                                <td class="text-center"><span class="badge bg-success text-white">NEW</span></td>
                                <td class="text-center align-middle bg-light-subtle">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="qr-canvas-box bg-white p-1 border rounded shadow-sm" data-qr="<?= htmlspecialchars($swapQrCodeVal) ?>" style="width: 54px; height: 54px;" title="QR: <?= htmlspecialchars($swapQrCodeVal) ?>"></div>
                                        <span class="font-monospace text-dark fw-bold mt-1" style="font-size: 9.5px; line-height: 1; word-break: break-all;"><?= htmlspecialchars($swapQrCodeVal) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size: 13px;"><?= htmlspecialchars($ei['product_name']) ?></div>
                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                        <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 10.5px;">SKU: <?= htmlspecialchars($ei['sku'] ?? '-') ?></span>
                                        <?php if (!empty($ei['product_code'])): ?>
                                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 10.5px;">Code: <?= htmlspecialchars($ei['product_code']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($ei['piece_tag'])): ?>
                                            <span class="badge bg-success font-monospace text-white" style="font-size: 10px;"><i class="bi bi-qr-code me-1"></i>Swap Tag: <?= htmlspecialchars($ei['piece_tag']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($ei['size_name'] ?? '-') ?></span>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($ei['color_name'] ?? '-') ?></span>
                                </td>
                                <td class="text-center fw-bold"><?= (int)$ei['quantity'] ?></td>
                                <td class="text-end font-monospace">₹<?= number_format((float)$ei['unit_price'], 2) ?></td>
                                <td class="text-end fw-bold text-success font-monospace">₹<?= number_format((float)$ei['total_amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Settlement Summary -->
        <div class="row justify-content-end mb-4">
            <div class="col-sm-6">
                <div class="card border-0 bg-light rounded-3 p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Returned Value:</span>
                        <strong class="font-monospace">₹<?= number_format((float)$return['total_amount'] ?: (float)$return['refund_amount'], 2) ?></strong>
                    </div>
                    <?php if (!empty($return['exchange_id'])): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Replacement Items Value:</span>
                            <strong class="font-monospace">₹<?= number_format((float)$return['new_amount'], 2) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2">
                            <span class="fw-bold text-dark">Settlement Action:</span>
                            <?php if ($return['payment_action'] === 'COLLECT'): ?>
                                <strong class="text-warning-emphasis font-monospace">Collect ₹<?= number_format((float)$return['difference_amount'], 2) ?></strong>
                            <?php elseif ($return['payment_action'] === 'REFUND'): ?>
                                <strong class="text-danger font-monospace">Refund ₹<?= number_format((float)$return['difference_amount'], 2) ?></strong>
                            <?php else: ?>
                                <strong class="text-success font-monospace">Even Swap (₹0.00)</strong>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between border-top pt-2">
                            <span class="fw-bold text-dark">Refund Issued (<?= htmlspecialchars($return['refund_mode'] ?? 'CASH') ?>):</span>
                            <strong class="text-danger fs-5 font-monospace">₹<?= number_format((float)$return['refund_amount'], 2) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Signature Block -->
        <div class="row pt-4 mt-4 border-top">
            <div class="col-6 text-center">
                <div style="height: 50px;"></div>
                <div class="border-top pt-1 small text-muted">Customer / Receiver Signature</div>
            </div>
            <div class="col-6 text-center">
                <div style="height: 50px;"></div>
                <div class="border-top pt-1 small text-muted">Authorized Warehouse Officer</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
function renderAllQRCodes() {
    if (typeof QRCode === 'undefined') {
        setTimeout(renderAllQRCodes, 100);
        return;
    }
    document.querySelectorAll('.qr-canvas-box').forEach(function(el) {
        if (el.dataset.rendered) return;
        const text = (el.getAttribute('data-qr') || '').trim();
        if (!text) return;
        el.innerHTML = '';
        const w = (parseInt(el.style.width) || 54) - 8;
        const h = (parseInt(el.style.height) || 54) - 8;
        new QRCode(el, {
            text: text,
            width: w,
            height: h,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
        el.dataset.rendered = '1';
    });
}
document.addEventListener('DOMContentLoaded', renderAllQRCodes);
window.addEventListener('load', renderAllQRCodes);
setTimeout(renderAllQRCodes, 150);
</script>

<style>
@media print {
    .no-print, .sidebar, .navbar, .page-header {
        display: none !important;
    }
    body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    #printableSlip {
        border: none !important;
        box-shadow: none !important;
        max-width: 100% !important;
        padding: 0 !important;
    }
}
</style>
