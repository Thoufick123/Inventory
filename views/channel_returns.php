<?php
$isWebsiteReturns = !empty($isWebsiteReturns);
$returnChannelLabel = $isWebsiteReturns ? 'Website' : 'WhatsApp';
$returnBasePath = $isWebsiteReturns ? 'warehouse/website-orders/returns' : 'warehouse/whatsapp-orders/returns';
$returnApiPath = $isWebsiteReturns ? 'api/website/returns/lookup-piece' : 'api/whatsapp/returns/lookup-piece';
$returnHistoryPath = $isWebsiteReturns ? 'warehouse/website-orders/history' : 'sales/whatsapp';
$returnTheme = $isWebsiteReturns ? 'primary' : 'success';
$canProcessReturns = !$isWebsiteReturns || hasPermission('website_returns', 'create') || hasPermission('website_returns', 'process');
?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-arrow-left-right me-2 text-<?= $returnTheme ?>"></i><?= htmlspecialchars($returnChannelLabel) ?> Returns &amp; Exchanges</h2>
        <span class="text-muted">Process customer product returns, size &amp; color exchanges, and refunds for <?= htmlspecialchars($returnChannelLabel) ?> orders</span>
    </div>
</div>

<?php if ($canProcessReturns): ?>
<!-- Customer invoice lookup (returns/exchanges) -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white" style="border-top:3px solid #198754 !important;">
    <div class="card-body p-3">
        <label class="form-label small fw-semibold text-dark mb-2"><i class="bi bi-search text-success me-1"></i>Search <?= htmlspecialchars($returnChannelLabel) ?> Customer Order</label>
        <div class="input-group input-group-lg mb-2" style="max-width:590px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-qr-code-scan text-success"></i></span>
            <input id="waPageLookupInput" type="text" class="form-control border-start-0" placeholder="Phone number, invoice number, or customer name">
            <button id="waPageLookupBtn" class="btn btn-<?= $returnTheme ?> px-4 fw-semibold" type="button">Lookup Order</button>
        </div>
        <div id="waPageLookupMessage" class="small text-primary fw-semibold mb-2" style="display:none;"></div>
        <div id="waPageInvoiceList" class="list-group" style="display:none; max-height:245px; overflow:auto;"></div>
    </div>
</div>
<?php endif; ?>

<!-- Summary Metrics Chips -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(13, 110, 253, 0.12); color: #0d6efd;">
                    <i class="bi bi-box-arrow-in-left fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Direct Returns</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($totalReturnsCount ?? 0) ?></h4>
                    <small class="text-muted">Refund transactions</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(111, 66, 193, 0.12); color: #6f42c1;">
                    <i class="bi bi-arrow-repeat fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Size / Color Exchanges</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($totalExchangesCount ?? 0) ?></h4>
                    <small class="text-muted">Swapped variants</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(220, 53, 69, 0.12); color: #dc3545;">
                    <i class="bi bi-currency-rupee fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total Refunded Amount</div>
                    <h4 class="fw-bold mb-0 text-danger">₹<?= number_format($totalRefundAmount ?? 0, 2) ?></h4>
                    <small class="text-muted">Processed refunds</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(255, 193, 7, 0.2); color: #856404;">
                    <i class="bi bi-exclamation-triangle fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Damaged Units</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($totalDamagedQty ?? 0) ?> <span class="fs-7 fw-normal text-muted">pcs</span></h4>
                    <small class="text-muted">Routed to Defect Pool</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="<?= url($returnBasePath) ?>" class="row g-2 align-items-end">
            <!-- Search Keyword -->
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-semibold text-secondary mb-1">Search Returns</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Return #, Invoice #, Customer name, Phone..." value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
            </div>

            <!-- Type Filter -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold text-secondary mb-1">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">-- All Types --</option>
                    <option value="RETURN" <?= ($returnType ?? '') === 'RETURN' ? 'selected' : '' ?>>Direct Return</option>
                    <option value="EXCHANGE" <?= ($returnType ?? '') === 'EXCHANGE' ? 'selected' : '' ?>>Exchange / Swap</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold text-secondary mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- All Statuses --</option>
                    <option value="APPROVED" <?= ($status ?? '') === 'APPROVED' ? 'selected' : '' ?>>APPROVED</option>
                    <option value="PENDING" <?= ($status ?? '') === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                    <option value="REJECTED" <?= ($status ?? '') === 'REJECTED' ? 'selected' : '' ?>>REJECTED</option>
                </select>
            </div>

            <!-- Date Range -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold text-secondary mb-1">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom ?? '') ?>">
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold text-secondary mb-1">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo ?? '') ?>">
            </div>

            <!-- Filter Buttons -->
            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                <a href="<?= url($returnBasePath) ?>" class="btn btn-sm btn-light border">
                    <i class="bi bi-x-circle me-1"></i> Clear
                </a>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Returns & Exchanges History Table -->
<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-header bg-transparent border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
        <h5 class="card-title fw-bold mb-0 text-dark">
            <i class="bi bi-list-check me-2 text-primary"></i><?= htmlspecialchars($returnChannelLabel) ?> Returns &amp; Exchanges Register
        </h5>
        <span class="badge bg-light text-secondary border"><?= count($returns ?? []) ?> Records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-3">Return / Slip #</th>
                        <th>Original Order</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th class="text-center">Returned Qty</th>
                        <th>Amount / Balance</th>
                        <th>Status</th>
                        <th>Processed At</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($returns)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No <?= htmlspecialchars($returnChannelLabel) ?> returns or exchanges found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($returns as $row): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['return_number']) ?></div>
                                    <?php if (!empty($row['exchange_number'])): ?>
                                        <div class="small text-primary"><i class="bi bi-arrow-repeat me-1"></i><?= htmlspecialchars($row['exchange_number']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= url($returnHistoryPath . '?search=' . urlencode($row['sale_invoice'])) ?>" class="fw-semibold text-decoration-none">
                                        <?= htmlspecialchars($row['sale_invoice']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($row['customer_name']) ?></div>
                                    <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($row['customer_phone']) ?></small>
                                </td>
                                <td>
                                    <?php if ($row['return_type'] === 'EXCHANGE'): ?>
                                        <span class="badge bg-purple-subtle text-purple border border-purple-subtle px-2 py-1" style="background-color: #f3e8ff; color: #7e22ce;">
                                            <i class="bi bi-arrow-repeat me-1"></i>Exchange
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                            <i class="bi bi-box-arrow-in-left me-1"></i>Return
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold"><?= (int)$row['total_returned_qty'] ?> pcs</span>
                                    <?php if ((int)$row['damaged_qty'] > 0): ?>
                                        <div class="small text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i><?= (int)$row['damaged_qty'] ?> Damaged</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['return_type'] === 'EXCHANGE'): ?>
                                        <?php if ($row['payment_action'] === 'COLLECT'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                Collect ₹<?= number_format((float)$row['difference_amount'], 2) ?>
                                            </span>
                                        <?php elseif ($row['payment_action'] === 'REFUND'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                Refund ₹<?= number_format((float)$row['difference_amount'], 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Even Swap</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="fw-bold text-dark">₹<?= number_format((float)$row['refund_amount'], 2) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($row['refund_mode'] ?? 'UPI') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $st = strtoupper($row['status'] ?? 'APPROVED');
                                    $badgeClass = 'bg-success-subtle text-success border-success-subtle';
                                    if ($st === 'PENDING') $badgeClass = 'bg-warning-subtle text-warning border-warning-subtle';
                                    elseif ($st === 'REJECTED') $badgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                                    ?>
                                    <span class="badge <?= $badgeClass ?> border px-2 py-1"><?= $st ?></span>
                                </td>
                                <td>
                                    <div class="small text-dark"><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                                    <small class="text-muted"><?= date('h:i A', strtotime($row['created_at'])) ?></small>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?= url($returnBasePath . '/invoice/' . $row['id']) ?>" class="btn btn-sm btn-outline-primary" title="Print Return / Exchange Slip" target="_blank">
                                        <i class="bi bi-printer me-1"></i> Slip
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Process Return & Exchange -->
<div class="modal fade" id="newReturnModal" tabindex="-1" aria-labelledby="newReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow:hidden;">
            <!-- Modal Header -->
            <div class="modal-header border-bottom py-3 px-4 bg-white d-flex justify-content-between align-items-start position-relative">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-success text-white rounded-pill font-monospace" style="font-size:10px; padding:3px 8px;">
                            <i class="bi <?= $isWebsiteReturns ? 'bi-globe2' : 'bi-whatsapp' ?> me-1"></i><?= strtoupper(htmlspecialchars($returnChannelLabel)) ?> ORDERS
                        </span>
                        <span class="text-muted small">Return &amp; Exchange Portal</span>
                    </div>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="newReturnModalLabel">
                        Step 2: Select Items &amp; Action Type
                    </h5>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close" style="font-size:1.1rem; opacity:0.75; transition:opacity 0.2s;"></button>
            </div>

            <div class="modal-body p-4 bg-light-subtle">
                <!-- Search Input Section -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
                    <div class="card-body p-3">
                        <label class="form-label fw-bold text-dark mb-2" style="font-size:13px;">
                            <i class="bi bi-search text-success me-1"></i>Step 1: Search <?= htmlspecialchars($returnChannelLabel) ?> Order / Customer
                        </label>
                        <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-upc-scan text-success fs-5"></i></span>
                            <input type="text" id="orderSearchInput" class="form-control border-start-0 font-monospace fw-bold" 
                                   placeholder="Type <?= htmlspecialchars($returnChannelLabel) ?> Invoice #, Customer Phone, or Name..." style="font-size:14px;">
                            <button class="btn btn-success px-4 fw-bold" type="button" id="btnSearchOrder">
                                <i class="bi bi-search me-1"></i> Lookup Order
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lookup Results Container -->
                <div id="lookupResultsContainer" style="display: none;">
                    <div id="invoiceChoices" class="card border-primary-subtle shadow-sm mb-3 p-3" style="display:none; border-radius:12px;"></div>
                    
                    <form id="processReturnForm" method="POST" action="<?= url($returnBasePath . '/process') ?>">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="sale_id" id="formSaleId" value="">
                        <input type="hidden" name="return_type" id="formReturnTypeInput" value="EXCHANGE_SAME">

                        <!-- Order Metadata Strip -->
                        <div class="card border-0 shadow-sm mb-4 p-3 bg-white" style="border-radius:12px;">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-3 border-end">
                                    <span class="text-muted small text-uppercase font-monospace d-block" style="font-size:10px;">Invoice Number</span>
                                    <strong id="metaInvoice" class="text-primary font-monospace fs-6">-</strong>
                                </div>
                                <div class="col-md-3 border-end">
                                    <span class="text-muted small text-uppercase font-monospace d-block" style="font-size:10px;">Customer Name</span>
                                    <strong id="metaCustomer" class="text-dark fs-6 d-block text-truncate">-</strong>
                                    <small id="metaPhone" class="text-muted font-monospace d-block">-</small>
                                </div>
                                <div class="col-md-3 border-end">
                                    <span class="text-muted small text-uppercase font-monospace d-block" style="font-size:10px;">Order Date</span>
                                    <strong id="metaDate" class="text-dark small d-block">-</strong>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted small text-uppercase font-monospace d-block" style="font-size:10px;">Total Order Value</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace px-3 py-1 fs-6" id="metaTotal">
                                        ₹0.00
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- 4 Clickable Action Type Cards -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-2" style="font-size:13px;">
                                <i class="bi bi-grid-fill me-1 text-primary"></i>Choose Action Type for Selected Items
                            </label>
                            <div class="row g-3">
                                <!-- Card 1: Direct Return -->
                                <div class="col-12 col-md-6">
                                    <div class="action-type-card card border-2 h-100 p-3 shadow-sm" 
                                         id="waCard_RETURN" onclick="selectWaActionCard('RETURN')"
                                         style="border-radius:12px; cursor:pointer; border-color:#e2e8f0; background:#fff;">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                  style="width:32px;height:32px;background:#fef2f2;">
                                                <i class="bi bi-box-arrow-in-left text-danger" style="font-size:15px;"></i>
                                            </span>
                                            <strong class="text-dark" style="font-size:14px;">Direct Return</strong>
                                            <span class="ms-auto badge bg-danger-subtle text-danger" style="font-size:11px;">Refund</span>
                                        </div>
                                        <p class="text-muted mb-0" style="font-size:12px; line-height:1.45;">
                                            Customer returns item for direct refund via Cash, UPI, or Bank.
                                        </p>
                                    </div>
                                </div>

                                <!-- Card 2: Exchange Same Product -->
                                <div class="col-12 col-md-6">
                                    <div class="action-type-card card border-2 h-100 p-3 shadow-sm" 
                                         id="waCard_EXCHANGE_SAME" onclick="selectWaActionCard('EXCHANGE_SAME')"
                                         style="border-radius:12px; cursor:pointer; border-color:#10b981; background:#f0fdf4;">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                  style="width:32px;height:32px;background:#dcfce7;">
                                                <i class="bi bi-arrow-left-right text-success" style="font-size:15px;"></i>
                                            </span>
                                            <strong class="text-dark" style="font-size:14px;">Exchanged Product</strong>
                                            <span class="ms-auto badge bg-success-subtle text-success" style="font-size:11px;"><?= $isWebsiteReturns ? 'Available Website Stock' : 'Size / Color' ?></span>
                                        </div>
                                        <p class="text-muted mb-0" style="font-size:12px; line-height:1.45;">
                                            <?= $isWebsiteReturns ? 'Exchange for any product with available Website inventory stock.' : 'Exchange for different size or color variant of the same garment.' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Refund Mode Group (Shown when Direct Return or Credit Note selected) -->
                        <div class="card border-0 shadow-sm mb-4 p-3 bg-white" id="refundModeGroup" style="border-radius:12px; display:none;">
                            <!-- Header strip (Image 2 Style) -->
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="fw-bold text-dark font-monospace" style="font-size:11px; letter-spacing:0.5px;">
                                    <i class="bi bi-wallet2 text-primary me-1"></i>PAYMENT METHOD
                                </span>
                                <span class="text-muted font-monospace" style="font-size:10px; letter-spacing:0.5px;">
                                    SELECT CUSTOMER PAYMENT MODE
                                </span>
                            </div>

                            <!-- 4 Visual Clickable Payment Cards (Image 2 Style) -->
                            <div class="row g-2 mb-3" id="refundModeButtons">
                                <div class="col-6 col-sm-3">
                                    <div class="pay-mode-btn p-2.5 rounded-3 text-center" onclick="selectRefundPaymentMode('CASH', this)">
                                        <i class="bi bi-cash-stack fs-3 text-success d-block mb-1"></i>
                                        <span class="fw-bold text-dark small">Cash</span>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="pay-mode-btn active p-2.5 rounded-3 text-center" onclick="selectRefundPaymentMode('UPI', this)">
                                        <i class="bi bi-qr-code-scan fs-3 text-primary d-block mb-1"></i>
                                        <span class="fw-bold text-dark small">UPI / QR</span>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="pay-mode-btn p-2.5 rounded-3 text-center" onclick="selectRefundPaymentMode('CARD', this)">
                                        <i class="bi bi-credit-card-2-front fs-3 text-warning d-block mb-1"></i>
                                        <span class="fw-bold text-dark small">Card / POS</span>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="pay-mode-btn p-2.5 rounded-3 text-center" onclick="selectRefundPaymentMode('CASH_UPI', this)">
                                        <i class="bi bi-pie-chart-fill fs-3 text-info d-block mb-1"></i>
                                        <span class="fw-bold text-dark small">Cash + UPI</span>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="refund_mode" id="refundModeInput" value="UPI">

                            <!-- Cash + UPI Split Breakdown Container (Image 3 Style) -->
                            <div id="cashUpiSplitBox" class="p-3 mb-3 rounded-3 border border-success-subtle bg-success-subtle bg-opacity-10 d-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="fw-bold text-success small mb-0">
                                        <i class="bi bi-wallet2 me-1"></i>Cash + UPI Split Breakdown
                                    </label>
                                    <button type="button" class="btn btn-xs btn-outline-success rounded-pill px-2.5 py-0.5 fw-semibold" onclick="autoBalanceSplit()">
                                        <i class="bi bi-arrow-repeat me-1"></i>Auto Balance
                                    </button>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="small text-muted fw-semibold mb-1">Cash Amount (₹)</label>
                                        <input type="number" step="0.01" name="cash_amount" id="cashAmountInput" class="form-control form-control-sm text-end fw-bold font-monospace bg-white" value="0.00" oninput="recalculateSplitTotals()">
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted fw-semibold mb-1">UPI Amount (₹)</label>
                                        <input type="number" step="0.01" name="upi_amount" id="upiAmountInput" class="form-control form-control-sm text-end fw-bold font-monospace bg-white" value="0.00" oninput="recalculateSplitTotals()">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="small text-muted fw-semibold mb-1">UPI Ref / Transaction ID / App</label>
                                    <input type="text" name="upi_reference" id="upiReferenceInput" class="form-control form-control-sm font-monospace bg-white" placeholder="e.g. GPay Ref #402918237" autocomplete="off">
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-1 border-top border-success-subtle">
                                    <small class="fw-semibold text-dark">Split Total: <strong class="font-monospace text-success" id="splitTotalText">₹0.00</strong></small>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace" id="splitBalanceBadge">Balanced (₹0.00)</span>
                                </div>
                            </div>

                            <!-- Reason / Notes -->
                            <div>
                                <label class="form-label fw-bold small mb-1"><i class="bi bi-pencil-square me-1"></i>Reason / Notes</label>
                                <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Fitting issue, fabric flaw, customer requested refund...">
                            </div>
                        </div>

                        <!-- Step 2A: Returning (Old) Purchased Items Table -->
                        <div class="card border-0 shadow-sm mb-4" style="border-radius:12px; overflow:hidden;">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill font-monospace px-2.5 py-1" style="font-size:11px;">
                                        <i class="bi bi-box-arrow-in-left me-1"></i>STEP 2A: RETURNING (OLD) ITEMS
                                    </span>
                                    <span class="badge text-bg-light border fw-semibold" id="waItemCountBadge" style="font-size:11px;">0 Line Item(s) Selected</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="input-group input-group-sm" style="max-width: 320px;">
                                        <span class="input-group-text bg-light text-danger border-end-0"><i class="bi bi-upc-scan"></i></span>
                                        <input type="text" id="scanReturnItemInput" class="form-control form-control-sm font-monospace border-start-0" placeholder="Scan Old Piece Tag/SKU..." onkeydown="if(event.key === 'Enter') { event.preventDefault(); scanReturningPiece(this.value); }">
                                        <button class="btn btn-sm btn-danger fw-bold px-2.5" type="button" onclick="scanReturningPiece(document.getElementById('scanReturnItemInput').value)">Scan</button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="returnItemsTable" style="font-size:13px;">
                                    <thead style="background:#f8f9fa; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#6c757d; font-weight:700;">
                                        <tr>
                                            <th class="ps-3 text-center" style="width:50px;">SELECT</th>
                                            <th style="min-width:250px;">OLD PRODUCT ITEM &amp; SCANNED PIECES</th>
                                            <th class="text-center" style="width:60px;">SOLD</th>
                                            <th class="text-center" style="width:90px;">RETURN QTY</th>
                                            <th style="width:140px;">CONDITION</th>
                                            <th class="text-end" style="width:110px;">UNIT RATE</th>
                                            <th class="text-end pe-3" style="width:120px;">LINE CREDIT</th>
                                        </tr>
                                    </thead>
                                    <tbody id="returnItemsTbody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Replacement product scanner for exchanges -->
                        <div id="sameExchangeSection" class="card border-0 shadow-sm mb-4" style="border-radius:12px; overflow:hidden;">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace px-2.5 py-1" style="font-size:11px;">
                                        <i class="bi bi-upc-scan me-1"></i>SCAN REPLACEMENT PRODUCT
                                    </span>
                                    <span class="text-muted small"><?= $isWebsiteReturns ? 'Scan any product with available Website inventory stock' : 'Scan any product with available WhatsApp stock' ?></span>
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1" style="font-size:11px;">
                                    <?= $isWebsiteReturns ? 'Website Inventory Stock Only' : 'WhatsApp Stock Only' ?>
                                </span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="sameExchangeTable" style="font-size:13px;">
                                        <thead style="background:#f0fdf4; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#166534; font-weight:700;">
                                            <tr>
                                                <th style="width:100%;">Replacement Product QR / Barcode</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sameExchangeTbody">
                                            <!-- Populated dynamically per checked return item -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>



                        <!-- Financial Settlement Calculations Box (Image 3 Style) -->
                        <div class="card border-0 shadow-sm mb-4" id="settlementCalcCard" style="border-radius:12px; overflow:hidden;">
                            <div class="card-body p-3 bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Return Product Paid Amount:</span>
                                    <strong class="font-monospace text-danger fs-6" id="calcReturnPaid">₹0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Current Choose Product Amount:</span>
                                    <strong class="font-monospace text-success fs-6" id="calcChooseAmount">₹0.00</strong>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong class="text-dark small">Total Pay Amount:</strong>
                                </div>
                                <div class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6 fw-bold rounded text-wrap w-100 text-center font-monospace" id="calcDifferenceBadge">
                                    Refund Customer: ₹0.00 - ₹0.00 = -₹0.00
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top bg-white p-3 rounded-3">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Process <?= htmlspecialchars($returnChannelLabel) ?> Return / Exchange
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.action-type-card {
    transition: all 0.2s ease-in-out;
}
#waCard_RETURN:hover {
    border-color: #ef4444 !important;
}
#waCard_EXCHANGE_SAME:hover {
    border-color: #10b981 !important;
}
.pay-mode-btn {
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 8px;
    text-align: center;
    cursor: pointer;
    background: #ffffff;
    transition: all 0.2s ease;
}
.pay-mode-btn:hover {
    border-color: #0d6efd;
    background: #f8fafc;
}
.pay-mode-btn.active {
    border-color: #0d6efd !important;
    background: #eff6ff !important;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
}
</style>

<script>
window.selectRefundPaymentMode = function(mode, el) {
    document.querySelectorAll('#refundModeButtons .pay-mode-btn').forEach(btn => btn.classList.remove('active'));
    if (el) el.classList.add('active');
    
    const input = document.getElementById('refundModeInput');
    if (input) input.value = mode;

    const splitBox = document.getElementById('cashUpiSplitBox');
    if (splitBox) {
        if (mode === 'CASH_UPI' || mode === 'SPLIT') {
            splitBox.classList.remove('d-none');
            recalculateSplitTotals();
        } else {
            splitBox.classList.add('d-none');
        }
    }
};

window.autoBalanceSplit = function() {
    let grandTotal = 0;
    const metaTotalEl = document.getElementById('metaTotal');
    if (metaTotalEl) {
        const txt = metaTotalEl.textContent.replace(/[^0-9.]/g, '');
        grandTotal = parseFloat(txt) || 0;
    }

    const cashInput = document.getElementById('cashAmountInput');
    const upiInput = document.getElementById('upiAmountInput');
    
    if (cashInput && upiInput) {
        const cashVal = parseFloat(cashInput.value) || 0;
        if (grandTotal > 0) {
            const remaining = Math.max(0, grandTotal - cashVal);
            upiInput.value = remaining.toFixed(2);
        }
    }
    recalculateSplitTotals();
};

window.recalculateSplitTotals = function() {
    const cashVal = parseFloat(document.getElementById('cashAmountInput')?.value) || 0;
    const upiVal = parseFloat(document.getElementById('upiAmountInput')?.value) || 0;
    const splitTotal = cashVal + upiVal;

    let grandTotal = 0;
    const metaTotalEl = document.getElementById('metaTotal');
    if (metaTotalEl) {
        const txt = metaTotalEl.textContent.replace(/[^0-9.]/g, '');
        grandTotal = parseFloat(txt) || 0;
    }

    const totalText = document.getElementById('splitTotalText');
    if (totalText) totalText.textContent = '₹' + splitTotal.toFixed(2);

    const badge = document.getElementById('splitBalanceBadge');
    if (badge) {
        if (grandTotal > 0 && Math.abs(splitTotal - grandTotal) > 0.01) {
            const diff = grandTotal - splitTotal;
            if (diff > 0) {
                badge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace';
                badge.textContent = `Pending (₹${diff.toFixed(2)})`;
            } else {
                badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle font-monospace';
                badge.textContent = `Excess (+₹${Math.abs(diff).toFixed(2)})`;
            }
        } else {
            badge.className = 'badge bg-success-subtle text-success border border-success-subtle font-monospace';
            badge.textContent = `Balanced (₹${splitTotal.toFixed(2)})`;
        }
    }
};

window.selectWaActionCard = function(mode) {
    document.getElementById('formReturnTypeInput').value = mode;

    // Reset all cards
    ['RETURN', 'EXCHANGE_SAME'].forEach(m => {
        const card = document.getElementById('waCard_' + m);
        if (card) {
            card.style.borderColor = '#e2e8f0';
            card.style.background = '#ffffff';
            card.style.boxShadow = 'none';
        }
    });

    // Highlight selected card with matching theme color border & shadow
    const activeCard = document.getElementById('waCard_' + mode);
    if (activeCard) {
        if (mode === 'RETURN') {
            activeCard.style.borderColor = '#ef4444';
            activeCard.style.background = '#fef2f2';
            activeCard.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.15)';
        } else if (mode === 'EXCHANGE_SAME') {
            activeCard.style.borderColor = '#10b981';
            activeCard.style.background = '#f0fdf4';
            activeCard.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.15)';
        }
    }

    const refundModeGroup = document.getElementById('refundModeGroup');
    const sameSection = document.getElementById('sameExchangeSection');

    if (refundModeGroup) {
        refundModeGroup.style.display = (mode === 'RETURN') ? 'block' : 'none';
    }
    if (sameSection) {
        sameSection.style.display = (mode === 'EXCHANGE_SAME') ? 'block' : 'none';
    }
    renderSameExchangeTable();
    recalculateSettlementSummary();
};

let differentExchangeItems = [];
let loadedOrderItemsMap = {};
let currentSwapValues = {};
let currentSwapTags = {};

window.enableAddCatalogBtn = function() {
    const sel = document.getElementById('catalogVariantSelect');
    const btn = document.getElementById('btnAddCatalogItem');
    if (btn) btn.disabled = !sel.value;
};

window.scanAlternateItem = function(rawCode) {
    const code = rawCode ? rawCode.trim() : '';
    if (!code) return;

    const sel = document.getElementById('catalogVariantSelect');
    let matchedOption = null;
    if (sel) {
        for (let i = 0; i < sel.options.length; i++) {
            const opt = sel.options[i];
            const sku = (opt.getAttribute('data-sku') || '').toLowerCase();
            const qr = (opt.getAttribute('data-qr') || '').toLowerCase();
            if (sku === code.toLowerCase() || qr === code.toLowerCase() || code.toLowerCase().includes(sku)) {
                matchedOption = opt;
                break;
            }
        }
    }

    if (matchedOption) {
        sel.value = matchedOption.value;
        enableAddCatalogBtn();
        addDifferentExchangeItem(code);
        document.getElementById('scanAlternateInput').value = '';
        showScanFeedback('Scanned: ' + matchedOption.getAttribute('data-name') + ' (' + code + ')', 'success');
    } else {
        showScanFeedback('Item with tag/SKU "' + code + '" not found in live warehouse stock.', 'danger');
    }
};

window.showScanFeedback = function(msg, type) {
    const feedback = document.getElementById('scanAlternateFeedback');
    if (!feedback) return;
    feedback.className = `small mt-1 text-${type} fw-semibold`;
    feedback.textContent = msg;
    feedback.classList.remove('d-none');
    setTimeout(() => { feedback.classList.add('d-none'); }, 4000);
};

window.addDifferentExchangeItem = function(scannedTag = null) {
    const sel = document.getElementById('catalogVariantSelect');
    const val = sel.value;
    if (!val) return;

    const opt = sel.options[sel.selectedIndex];
    const varId = parseInt(val);
    const name = opt.getAttribute('data-name');
    const sku = opt.getAttribute('data-sku');
    const defaultQr = opt.getAttribute('data-qr') || sku || '';
    const activeTag = scannedTag ? scannedTag.trim() : defaultQr;
    const size = opt.getAttribute('data-size');
    const color = opt.getAttribute('data-color');
    const price = parseFloat(opt.getAttribute('data-price') || 0);
    const stock = parseInt(opt.getAttribute('data-stock') || 0);

    const existing = differentExchangeItems.find(i => i.variant_id === varId);
    if (existing) {
        existing.quantity += 1;
        if (!existing.scanned_tags) existing.scanned_tags = [];
        if (activeTag && !existing.scanned_tags.includes(activeTag)) {
            existing.scanned_tags.push(activeTag);
        }
    } else {
        differentExchangeItems.push({
            variant_id: varId,
            product_name: name,
            sku: sku,
            qr_code: activeTag,
            scanned_tags: activeTag ? [activeTag] : [],
            size_name: size,
            color_name: color,
            unit_price: price,
            quantity: 1,
            max_stock: stock
        });
    }

    renderDifferentExchangeTable();
    recalculateSettlementSummary();
};

window.removeDifferentItem = function(idx) {
    differentExchangeItems.splice(idx, 1);
    renderDifferentExchangeTable();
    recalculateSettlementSummary();
};

window.renderDifferentExchangeTable = function() {
    const tbody = document.getElementById('differentExchangeItemsTbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (differentExchangeItems.length === 0) {
        tbody.innerHTML = '<tr id="emptyDifferentRow"><td colspan="6" class="text-center py-3 text-muted small">No alternate items added yet. Select from the dropdown above.</td></tr>';
        return;
    }

    differentExchangeItems.forEach((item, idx) => {
        const lineTotal = item.unit_price * item.quantity;
        const tr = document.createElement('tr');
        
        let qrDisplay = '';
        if (item.scanned_tags && item.scanned_tags.length > 0) {
            qrDisplay = `<div class="mt-1 d-flex flex-wrap gap-1">${item.scanned_tags.map(t => `<span class="badge bg-primary-subtle text-primary border font-monospace" style="font-size:10px;"><i class="bi bi-qr-code me-1"></i>${t}</span>`).join('')}</div>`;
        }

        tr.innerHTML = `
            <td class="ps-3">
                <div class="fw-bold text-dark">${item.product_name}</div>
                <small class="text-muted font-monospace">${item.sku}</small>
                ${qrDisplay}
                <input type="hidden" name="replacement_items[${idx}][variant_id]" value="${item.variant_id}">
                <input type="hidden" name="replacement_items[${idx}][quantity]" value="${item.quantity}">
                <input type="hidden" name="replacement_items[${idx}][unit_price]" value="${item.unit_price}">
            </td>
            <td>
                <span class="badge bg-light text-dark border">${item.size_name} / ${item.color_name}</span>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm fw-bold text-center bg-light" value="${item.quantity}" min="1" onchange="differentExchangeItems[${idx}].quantity = parseInt(this.value)||1; renderDifferentExchangeTable(); recalculateSettlementSummary();" style="max-width:70px;">
            </td>
            <td>₹${item.unit_price.toFixed(2)}</td>
            <td class="fw-bold text-success">₹${lineTotal.toFixed(2)}</td>
            <td>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeDifferentItem(${idx})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
};

window.renderSameExchangeTable = function() {
    const tbody = document.getElementById('sameExchangeTbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    const checkboxes = document.querySelectorAll('.item-select-cb:checked');
    if (checkboxes.length === 0) {
        tbody.innerHTML = '<tr><td class="text-center py-3 text-muted small"><i class="bi bi-qr-code-scan fs-4 d-block mb-1 text-secondary opacity-50"></i>Select a returning item above to scan its replacement product.</td></tr>';
        return;
    }

    checkboxes.forEach(cb => {
        const itemId = cb.getAttribute('data-item-id');
        const item = loadedOrderItemsMap[itemId];
        if (!item) return;

        const qty = parseInt(document.getElementById('qty_' + itemId)?.value || 1) || 1;
        const unitPrice = parseFloat(item.unit_price || 0);

        const prevTag = currentSwapTags[itemId] || '';

        const tr = document.createElement('tr');
        tr.id = `sameSwapRow_${itemId}`;
        tr.innerHTML = `
            <td class="align-middle p-3">
                <input type="hidden" name="items[${itemId}][exchange_variant_id]" id="swap_${itemId}" value="${item.variant_id}" data-price="${unitPrice}">
                <input type="text" id="swapTagInput_${itemId}" class="form-control form-control-sm font-monospace border-success" placeholder="Scan or enter new product QR / SKU" style="font-size:11px;" onkeydown="if(event.key === 'Enter'){event.preventDefault(); scanSameProductSwap(${itemId}, this.value, this);}">
                <div id="swapProductDetails_${itemId}" class="small mt-2 text-success" style="display:none;"></div>
                <div class="scanned-replacement-tags-display d-flex flex-wrap gap-1 mt-1" id="swapTagsDisplay_${itemId}"></div>
            </td>
        `;
        tbody.appendChild(tr);

        if (prevTag) {
            addSwapReplacementPieceTag(itemId, prevTag);
        }
        updateSameSwapRow(itemId);
    });
};

window.updateSameSwapRow = function(itemId) {
    const item = loadedOrderItemsMap[itemId];
    if (!item) return;

    const qty = parseInt(document.getElementById('qty_' + itemId)?.value || 1) || 1;
    const oldUnitPrice = parseFloat(item.unit_price || 0);
    const oldLineTotal = oldUnitPrice * qty;

    const swapSelect = document.getElementById('swap_' + itemId);
    const newUnitPrice = parseFloat(swapSelect?.dataset.price || oldUnitPrice);

    const newLineTotal = newUnitPrice * qty;
    const diff = newLineTotal - oldLineTotal;

    const rateEl = document.getElementById('newRateText_' + itemId);
    const diffEl = document.getElementById('newDiffText_' + itemId);
    const qtyDisplay = document.getElementById('sameSwapQtyDisplay_' + itemId);

    if (qtyDisplay) qtyDisplay.textContent = qty;
    if (rateEl) rateEl.textContent = '₹' + newLineTotal.toFixed(2);
    if (diffEl) {
        if (Math.abs(diff) <= 0.01) {
            diffEl.className = 'badge bg-success-subtle text-success border border-success-subtle font-monospace';
            diffEl.textContent = 'Even Swap';
        } else if (diff > 0) {
            diffEl.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace';
            diffEl.textContent = `+₹${diff.toFixed(2)} Extra`;
        } else {
            diffEl.className = 'badge bg-danger-subtle text-danger border border-danger-subtle font-monospace';
            diffEl.textContent = `-₹${Math.abs(diff).toFixed(2)} Refund`;
        }
    }
};

window.recalculateSettlementSummary = function() {
    let returnPaid = 0.0;
    let chooseAmount = 0.0;

    const checkboxes = document.querySelectorAll('.item-select-cb:checked');
    checkboxes.forEach(cb => {
        const itemId = cb.getAttribute('data-item-id');
        const qty = parseInt(document.getElementById('qty_' + itemId)?.value || 1) || 1;
        const unitPrice = parseFloat(cb.getAttribute('data-unit-price') || document.getElementById('price_' + itemId)?.value || 0) || 0;
        const lineCredit = unitPrice * qty;
        returnPaid += lineCredit;

        const creditCell = document.getElementById('lineCredit_' + itemId);
        if (creditCell) {
            creditCell.textContent = '₹' + lineCredit.toFixed(2);
        }
    });

    const mode = document.getElementById('formReturnTypeInput')?.value || 'RETURN';

    if (mode === 'RETURN') {
        chooseAmount = 0.0;
    } else if (mode === 'EXCHANGE_SAME') {
        checkboxes.forEach(cb => {
            const itemId = cb.getAttribute('data-item-id');
            const qty = parseInt(document.getElementById('qty_' + itemId)?.value || 1) || 1;
            const unitPrice = parseFloat(cb.getAttribute('data-unit-price') || document.getElementById('price_' + itemId)?.value || 0) || 0;
            const swapSelect = document.getElementById('swap_' + itemId);
            if (swapSelect && swapSelect.value) {
                const swapPrice = parseFloat(swapSelect.dataset.price || unitPrice);
                chooseAmount += (swapPrice * qty);
            } else {
                chooseAmount += (unitPrice * qty);
            }
        });
    }

    const calcReturnPaid = document.getElementById('calcReturnPaid');
    const calcChooseAmount = document.getElementById('calcChooseAmount');
    const badge = document.getElementById('calcDifferenceBadge');

    if (calcReturnPaid) calcReturnPaid.textContent = '₹' + returnPaid.toFixed(2);
    if (calcChooseAmount) calcChooseAmount.textContent = '₹' + chooseAmount.toFixed(2);

    if (badge) {
        if (mode === 'RETURN') {
            badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6 fw-bold rounded text-wrap w-100 text-center font-monospace';
            badge.textContent = `Refund Customer: ₹${returnPaid.toFixed(2)}`;
        } else {
            const diff = chooseAmount - returnPaid;
            if (Math.abs(diff) <= 0.01) {
                badge.className = 'badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 fw-bold rounded text-wrap w-100 text-center font-monospace';
                badge.textContent = `Even Swap (₹${returnPaid.toFixed(2)} = ₹${chooseAmount.toFixed(2)})`;
            } else if (diff > 0) {
                badge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 fs-6 fw-bold rounded text-wrap w-100 text-center font-monospace';
                badge.textContent = `Collect Extra Pay Amount: ₹${chooseAmount.toFixed(2)} - ₹${returnPaid.toFixed(2)} = +₹${diff.toFixed(2)}`;
            } else {
                badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6 fw-bold rounded text-wrap w-100 text-center font-monospace';
                badge.textContent = `Refund Customer: ₹${returnPaid.toFixed(2)} - ₹${chooseAmount.toFixed(2)} = -₹${Math.abs(diff).toFixed(2)}`;
            }
        }
    }

    // Auto-update Cash/UPI split default when in Refund mode
    const cashAmountInput = document.getElementById('cashAmountInput');
    const upiAmountInput = document.getElementById('upiAmountInput');
    const refundModeInput = document.getElementById('refundModeInput');
    if (refundModeInput && mode === 'RETURN') {
        const curMode = refundModeInput.value;
        if (curMode === 'CASH') {
            if (cashAmountInput) cashAmountInput.value = returnPaid.toFixed(2);
            if (upiAmountInput) upiAmountInput.value = '0.00';
        } else if (curMode === 'UPI') {
            if (cashAmountInput) cashAmountInput.value = '0.00';
            if (upiAmountInput) upiAmountInput.value = returnPaid.toFixed(2);
        } else if (curMode === 'CASH_UPI') {
            if (cashAmountInput && parseFloat(cashAmountInput.value || 0) === 0 && upiAmountInput && parseFloat(upiAmountInput.value || 0) === 0) {
                upiAmountInput.value = returnPaid.toFixed(2);
            }
        }
        recalculateSplitTotals();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const btnSearch = document.getElementById('btnSearchOrder');
    const searchInput = document.getElementById('orderSearchInput');
    const resultsContainer = document.getElementById('lookupResultsContainer');

    // Scanned piece tags per item: { itemId -> Set of tags }
    const rowPieceTagsMap = {};

    btnSearch.addEventListener('click', performLookup);
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performLookup();
        }
    });

    function performLookup() {
        const query = searchInput.value.trim();
        if (!query) {
            alert('Please enter an invoice number, phone, or customer name.');
            return;
        }

        btnSearch.disabled = true;
        btnSearch.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Searching...';

        fetch('<?= url($returnBasePath . '/lookup') ?>?q=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(res => {
                btnSearch.disabled = false;
                btnSearch.innerHTML = '<i class="bi bi-search me-1"></i> Lookup Order';

                if (!res.success || !res.data) {
                    alert(res.message || 'No matching <?= htmlspecialchars($returnChannelLabel) ?> orders found.');
                    resultsContainer.style.display = 'none';
                    return;
                }

                // Render the matched order (support both array and object response)
                const matches = Array.isArray(res.data) ? res.data : [res.data];
                const choiceBox = document.getElementById('invoiceChoices');
                if (choiceBox && matches.length > 1) {
                    choiceBox.style.display = 'block';
                    choiceBox.innerHTML = `<label class="form-label fw-bold mb-2"><i class="bi bi-receipt me-1 text-primary"></i>Select the customer invoice</label>
                        <select class="form-select" id="customerInvoiceChoice">
                            ${matches.map((m, i) => `<option value="${i}">${m.sale.invoice_number} — ${m.sale.created_at} — ₹${parseFloat(m.sale.total_amount || 0).toFixed(2)}</option>`).join('')}
                        </select>`;
                    document.getElementById('customerInvoiceChoice').addEventListener('change', function () {
                        searchInput.value = matches[Number(this.value)].sale.invoice_number;
                        performLookup();
                    });
                } else if (choiceBox) {
                    choiceBox.style.display = 'none';
                    choiceBox.innerHTML = '';
                }
                const match = matches[0];
                if (!match) {
                    alert(res.message || 'No matching <?= htmlspecialchars($returnChannelLabel) ?> orders found.');
                    resultsContainer.style.display = 'none';
                    return;
                }

                const sale = match.sale || match;
                const items = match.items || res.items || [];

                if (match.already_returned) {
                    alert(`Invoice ${sale.invoice_number} was already returned/exchanged${match.return_number ? ` under ${match.return_number}` : ''}. Please select another invoice.`);
                    resultsContainer.style.display = 'none';
                    return;
                }

                document.getElementById('formSaleId').value = sale.id;
                document.getElementById('metaInvoice').textContent = sale.invoice_number;
                document.getElementById('metaCustomer').textContent = sale.customer_name;
                document.getElementById('metaPhone').textContent = sale.customer_phone || 'No phone';
                document.getElementById('metaDate').textContent = sale.created_at;
                document.getElementById('metaTotal').textContent = '₹' + parseFloat(sale.total_amount || 0).toFixed(2);

                const tbody = document.getElementById('returnItemsTbody');
                tbody.innerHTML = '';
                loadedOrderItemsMap = {};
                currentSwapValues = {};
                currentSwapTags = {};

                let validItemCount = 0;
                items.forEach(item => {
                    const retQty = item.returnable_quantity ?? item.quantity;
                    if (retQty <= 0) return;
                    validItemCount++;
                    loadedOrderItemsMap[item.id] = item;
                    rowPieceTagsMap[item.id] = new Set();

                    // Pre-populate any billed piece tags attached to this sale item
                    if (item.scanned_qr_codes) {
                        let parts;
                        try {
                            const decoded = JSON.parse(item.scanned_qr_codes);
                            parts = Array.isArray(decoded) ? decoded.map(tag =>
                                typeof tag === 'object' && tag !== null ? (tag.tag_code || tag.qr_code || '') : tag
                            ) : String(item.scanned_qr_codes).split(',');
                        } catch {
                            parts = String(item.scanned_qr_codes).split(',');
                        }
                        parts = parts.map(tag => String(tag).trim()).filter(Boolean);
                        parts.forEach(p => rowPieceTagsMap[item.id].add(p));
                    }
                    const row = document.createElement('tr');
                    row.id = `returnRow_${item.id}`;
                    row.innerHTML = `
                        <td class="text-center ps-3">
                            <input type="checkbox" class="form-check-input item-select-cb" data-item-id="${item.id}" data-unit-price="${item.unit_price}" checked onchange="toggleItemInputs(this, ${item.id})">
                            <input type="hidden" name="items[${item.id}][variant_id]" id="var_${item.id}" value="${item.variant_id}">
                            <input type="hidden" name="items[${item.id}][unit_price]" id="price_${item.id}" value="${item.unit_price}">
                        </td>
                        <td>
                            <div class="fw-bold text-dark" style="font-size:13px; line-height:1.3;">${item.product_name}</div>
                            <div class="small text-muted mb-1" style="font-size:11px;">
                                SKU: <strong class="text-dark font-monospace">${item.sku || '-'}</strong> | Size: <span class="badge bg-light text-dark border" style="font-size:10px;">${item.size_name || '-'}</span> | Color: <span class="badge bg-light text-dark border" style="font-size:10px;">${item.color_name || '-'}</span>
                            </div>
                            <div class="d-flex align-items-center gap-1 mt-1">
                                <div class="input-group input-group-sm" style="max-width:170px;">
                                    <span class="input-group-text bg-light text-muted py-0 px-1" style="font-size:10px;"><i class="bi bi-qr-code"></i></span>
                                    <input type="text" id="waTagInput_${item.id}" class="form-control form-control-sm font-monospace py-0 px-1" placeholder="Scan piece tag..." style="font-size:11px; height:24px;" onkeydown="if(event.key==='Enter'){event.preventDefault(); addWaPieceTag(${item.id}, this.value); this.value='';}">
                                    <button type="button" id="waTagBtn_${item.id}" class="btn btn-sm btn-outline-danger py-0 px-2" style="height:24px; font-size:11px;" onclick="const inp=document.getElementById('waTagInput_${item.id}'); addWaPieceTag(${item.id}, inp.value); inp.value='';">+</button>
                                </div>
                            </div>
                            <div class="scanned-tags-display d-flex flex-wrap gap-1 mt-1" id="waTagsDisplay_${item.id}"></div>
                        </td>
                        <td class="text-center fw-bold text-dark">
                            ${item.quantity}
                        </td>
                        <td class="text-center">
                            <input type="number" name="items[${item.id}][qty]" id="qty_${item.id}" class="form-control form-control-sm text-center font-monospace fw-bold" min="1" max="${retQty}" value="1" oninput="updateRowLineCredit(${item.id}); renderSameExchangeTable(); recalculateSettlementSummary();" onchange="updateRowLineCredit(${item.id}); renderSameExchangeTable(); recalculateSettlementSummary();" style="max-width:75px; margin:0 auto;">
                        </td>
                        <td>
                            <select name="items[${item.id}][condition]" id="cond_${item.id}" class="form-select form-select-sm" style="min-width:130px;">
                                <option value="GOOD">Good (Restock)</option>
                                <option value="DAMAGED">Damaged (QC Defect)</option>
                            </select>
                        </td>
                        <td class="text-end fw-bold font-monospace text-dark" style="font-size:13px;">
                            ₹${parseFloat(item.unit_price).toFixed(2)}
                        </td>
                        <td class="text-end pe-3 fw-bold font-monospace text-danger" id="lineCredit_${item.id}" style="font-size:13px;">
                            ₹${parseFloat(item.unit_price).toFixed(2)}
                        </td>
                    `;
                    tbody.appendChild(row);
                    renderWaTagBadges(item.id);
                });

                if (tbody.children.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">All items in this order have already been returned.</td></tr>';
                }

                const badge = document.getElementById('waItemCountBadge');
                if (badge) {
                    badge.textContent = `${validItemCount} Line Item(s) Selected`;
                }

                resultsContainer.style.display = 'block';
                renderSameExchangeTable();
                recalculateSettlementSummary();
            })
            .catch(err => {
                btnSearch.disabled = false;
                btnSearch.innerHTML = '<i class="bi bi-search me-1"></i> Lookup Order';
                alert('Error looking up order: ' + err.message);
            });
    }

    window.updateRowLineCredit = function(itemId) {
        const item = loadedOrderItemsMap[itemId];
        if (!item) return;
        const qty = parseInt(document.getElementById('qty_' + itemId)?.value || 1) || 1;
        const lineTotal = parseFloat(item.unit_price || 0) * qty;
        const creditCell = document.getElementById('lineCredit_' + itemId);
        if (creditCell) creditCell.textContent = '₹' + lineTotal.toFixed(2);
    };

    window.scanReturningPiece = function(rawTag) {
        const tag = (rawTag || '').trim();
        if (!tag) return;

        let matchedItemId = null;
        const checkboxes = document.querySelectorAll('.item-select-cb');
        
        checkboxes.forEach(cb => {
            const itemId = cb.getAttribute('data-item-id');
            const row = document.getElementById('returnRow_' + itemId);
            const rowText = row ? row.textContent : '';
            const tags = rowPieceTagsMap[itemId] || new Set();
            
            if (rowText.toLowerCase().includes(tag.toLowerCase()) || [...tags].some(t => t.toLowerCase() === tag.toLowerCase())) {
                matchedItemId = itemId;
            }
        });

        if (matchedItemId) {
            const cb = document.querySelector(`.item-select-cb[data-item-id="${matchedItemId}"]`);
            if (cb && !cb.checked) {
                cb.checked = true;
                toggleItemInputs(cb, matchedItemId);
            }
            addWaPieceTag(matchedItemId, tag);
            const scanInput = document.getElementById('scanReturnItemInput');
            if (scanInput) scanInput.value = '';
            
            const row = document.getElementById('returnRow_' + matchedItemId);
            if (row) {
                row.classList.add('table-danger');
                setTimeout(() => row.classList.remove('table-danger'), 1500);
            }
            recalculateSettlementSummary();
        } else {
            // Live piece lookup if not matched in plain text
            fetch('<?= url($returnApiPath) ?>?q=' + encodeURIComponent(tag))
                .then(r => r.json())
                .then(res => {
                    const variant = (res && (res.variant || res.data || res.product));
                    if (res && res.success && variant) {
                        const vId = String(variant.variant_id || variant.id);
                        for (let k in loadedOrderItemsMap) {
                            if (String(loadedOrderItemsMap[k].variant_id) === vId) {
                                matchedItemId = k;
                                break;
                            }
                        }
                    }
                    if (matchedItemId) {
                        const cb = document.querySelector(`.item-select-cb[data-item-id="${matchedItemId}"]`);
                        if (cb && !cb.checked) {
                            cb.checked = true;
                            toggleItemInputs(cb, matchedItemId);
                        }
                        addWaPieceTag(matchedItemId, tag);
                        const scanInput = document.getElementById('scanReturnItemInput');
                        if (scanInput) scanInput.value = '';
                        const row = document.getElementById('returnRow_' + matchedItemId);
                        if (row) {
                            row.classList.add('table-danger');
                            setTimeout(() => row.classList.remove('table-danger'), 1500);
                        }
                        recalculateSettlementSummary();
                    } else if (checkboxes.length === 1) {
                        matchedItemId = checkboxes[0].getAttribute('data-item-id');
                        addWaPieceTag(matchedItemId, tag);
                        const scanInput = document.getElementById('scanReturnItemInput');
                        if (scanInput) scanInput.value = '';
                        recalculateSettlementSummary();
                    } else {
                        alert(`No line item found matching scanned piece "${tag}".`);
                    }
                })
                .catch(() => {
                    if (checkboxes.length === 1) {
                        matchedItemId = checkboxes[0].getAttribute('data-item-id');
                        addWaPieceTag(matchedItemId, tag);
                        const scanInput = document.getElementById('scanReturnItemInput');
                        if (scanInput) scanInput.value = '';
                        recalculateSettlementSummary();
                    } else {
                        alert(`No line item found matching scanned piece "${tag}".`);
                    }
                });
        }
    };

    window.scanSameProductSwap = function(itemId, rawCode, inputEl) {
        const code = (rawCode || '').trim();
        if (!code) return;

        const swapSelect = document.getElementById('swap_' + itemId);
        if (!swapSelect) return;

        fetch('<?= url($returnApiPath) ?>?q=' + encodeURIComponent(rawCode) + '<?= $isWebsiteReturns ? '&available_only=1' : '' ?>')
            .then(r => r.json())
            .then(res => {
                const variant = res && (res.variant || res.data || res.product);
                if (!res.success || !variant) { alert(res.message || `Product matching "${rawCode}" not found.`); return; }
                const quantity = parseInt(document.getElementById('qty_' + itemId)?.value || 1, 10) || 1;
                const available = Number(variant.stock || 0);
                if (available < quantity) {
                    alert(`This replacement has only ${available} available in <?= e($returnChannelLabel) ?> stock; ${quantity} requested.`);
                    return;
                }
                if (<?= $isWebsiteReturns ? 'true' : 'false' ?> && !variant.aggregate_stock && quantity !== 1) {
                    alert('Normal Website products must be exchanged one scanned QR piece at a time.');
                    return;
                }

                swapSelect.value = String(variant.variant_id || variant.id);
                swapSelect.dataset.price = variant.selling_price || variant.price || swapSelect.dataset.price;
                const detail = document.getElementById('swapProductDetails_' + itemId);
                if (detail) {
                    const mrp = Number(variant.mrp || variant.selling_price || 0);
                    const selling = Number(variant.selling_price || variant.price || 0);
                    const discount = mrp > selling ? (((mrp - selling) / mrp) * 100).toFixed(0) : '0';
                    detail.innerHTML = `<strong>${variant.product_name || 'Product'}</strong> · ${variant.sku || ''}<br><span class="text-muted">${variant.size_name || ''} / ${variant.color_name || ''} · ${available} available · Base Price: ₹${mrp.toFixed(2)}</span> · <strong>Discount Price: ₹${selling.toFixed(2)}</strong>${discount > 0 ? ` · <span class="text-danger">${discount}% OFF</span>` : ''}`;
                    detail.style.display = 'block';
                }
                if (inputEl) inputEl.value = '';
                addSwapReplacementPieceTag(itemId, res.tag_code || rawCode);
                updateSameSwapRow(itemId);
                recalculateSettlementSummary();
            })
            .catch(() => alert(`Product matching "${rawCode}" not found.`));
    };

    window.addSwapReplacementPieceTag = function(itemId, raw) {
        const tag = (raw || '').trim();
        if (!tag) return;
        currentSwapTags[itemId] = tag;
        const display = document.getElementById('swapTagsDisplay_' + itemId);
        if (!display) return;
        display.innerHTML = `<span class="badge bg-success font-monospace" style="font-size:10px; cursor:pointer;" title="Click to remove" onclick="removeSwapReplacementPieceTag(${itemId})"><i class="bi bi-qr-code me-1"></i>Swap Tag: ${tag} <i class="bi bi-x ms-1"></i></span>`;
    };

    window.removeSwapReplacementPieceTag = function(itemId) {
        delete currentSwapTags[itemId];
        const display = document.getElementById('swapTagsDisplay_' + itemId);
        if (display) display.innerHTML = '';
    };

    window.addWaPieceTag = function(itemId, raw) {
        const tag = (raw || '').trim();
        if (!tag) return;
        if (!rowPieceTagsMap[itemId]) rowPieceTagsMap[itemId] = new Set();
        rowPieceTagsMap[itemId].add(tag);
        renderWaTagBadges(itemId);
    };

    window.removeWaPieceTag = function(itemId, tag) {
        if (rowPieceTagsMap[itemId]) rowPieceTagsMap[itemId].delete(tag);
        renderWaTagBadges(itemId);
    };

    function renderWaTagBadges(itemId) {
        const display = document.getElementById('waTagsDisplay_' + itemId);
        if (!display) return;
        const tags = rowPieceTagsMap[itemId] ? [...rowPieceTagsMap[itemId]] : [];
        if (tags.length === 0) { display.innerHTML = ''; return; }
        display.innerHTML = tags.map(t =>
            `<span class="badge" style="background:#1e293b; color:#fbbf24; font-family:monospace; font-size:10px; cursor:pointer;" title="Click to remove"
                   onclick="removeWaPieceTag(${itemId}, '${t.replace(/'/g, "\\'")}')"
             ><i class="bi bi-qr-code me-1"></i>${t} <i class="bi bi-x ms-1"></i></span>`
        ).join('');
    }

    window.toggleItemInputs = function(cb, itemId) {
        const isChecked = cb.checked;
        const qtyEl = document.getElementById('qty_' + itemId);
        const condEl = document.getElementById('cond_' + itemId);
        const varEl = document.getElementById('var_' + itemId);
        const priceEl = document.getElementById('price_' + itemId);

        if (qtyEl) qtyEl.disabled = !isChecked;
        if (condEl) condEl.disabled = !isChecked;
        if (varEl) varEl.disabled = !isChecked;
        if (priceEl) priceEl.disabled = !isChecked;

        const tagInput = document.getElementById('waTagInput_' + itemId);
        const tagBtn = document.getElementById('waTagBtn_' + itemId);
        if (tagInput) tagInput.disabled = !isChecked;
        if (tagBtn) tagBtn.disabled = !isChecked;

        const checkedCount = document.querySelectorAll('.item-select-cb:checked').length;
        const totalCount = document.querySelectorAll('.item-select-cb').length;
        const badge = document.getElementById('waItemCountBadge');
        if (badge) {
            badge.textContent = `${checkedCount} of ${totalCount} Item(s) Selected`;
        }

        renderSameExchangeTable();
        recalculateSettlementSummary();
    };

    // Attach piece_tags & replacement swap tags payload on submit
    document.getElementById('processReturnForm').addEventListener('submit', function(e) {
        const selectedItems = [...document.querySelectorAll('.item-select-cb:checked')];
        if (selectedItems.length === 0) {
            e.preventDefault();
            alert('Select at least one invoice item to return.');
            return;
        }
        const selectedItemIds = new Set(selectedItems.map(cb => cb.getAttribute('data-item-id')));

        if (document.getElementById('formReturnTypeInput')?.value === 'EXCHANGE_SAME') {
            const missingReplacement = selectedItems.find(cb => {
                const itemId = cb.getAttribute('data-item-id');
                return !document.getElementById('swap_' + itemId)?.value || !currentSwapTags[itemId];
            });
            if (missingReplacement) {
                e.preventDefault();
                alert('Scan a replacement QR/SKU for every selected item before processing the exchange.');
                return;
            }
        }

        Object.keys(rowPieceTagsMap).forEach(itemId => {
            if (!selectedItemIds.has(itemId)) return;
            const tags = [...rowPieceTagsMap[itemId]];
            if (tags.length > 0) {
                tags.forEach(t => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = `items[${itemId}][piece_tags][]`;
                    hidden.value = t;
                    this.appendChild(hidden);
                });
            }
        });

        // Attach swap piece tags
        Object.keys(currentSwapTags).forEach(itemId => {
            if (!selectedItemIds.has(itemId)) return;
            const swapTag = currentSwapTags[itemId];
            if (swapTag) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = `items[${itemId}][replacement_tag]`;
                hidden.value = swapTag;
                this.appendChild(hidden);
            }
        });
    });
});

// Full-page channel customer invoice lookup.
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('waPageLookupInput');
    const button = document.getElementById('waPageLookupBtn');
    const message = document.getElementById('waPageLookupMessage');
    const list = document.getElementById('waPageInvoiceList');
    if (!input || !button || !list) return;

    function lookup() {
        const q = input.value.trim();
        if (!q) { message.textContent = 'Enter a phone number or invoice number.'; message.style.display = 'block'; return; }
        button.disabled = true;
        list.style.display = 'none';
        message.textContent = 'Searching customer invoices...';
        message.style.display = 'block';
        fetch('<?= url($returnBasePath . '/lookup') ?>?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(result => {
                button.disabled = false;
                if (!result.success || !result.data || !result.data.length) {
                    message.textContent = result.message || 'No <?= htmlspecialchars($returnChannelLabel) ?> invoices found.';
                    return;
                }
                const orders = Array.isArray(result.data) ? result.data : [result.data];
                message.textContent = `This customer has ${orders.length} invoice${orders.length === 1 ? '' : 's'}. Select a bill to continue.`;
                list.innerHTML = orders.map((order, index) => {
                    const sale = order.sale || order;
                    const usable = !order.already_returned && (order.items || []).some(i => Number(i.returnable_quantity ?? i.quantity ?? 0) > 0);
                    return `<button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2 ${usable ? '' : 'disabled'}" data-invoice="${sale.invoice_number || ''}">
                        <span class="font-monospace text-primary fw-semibold flex-grow-1">${sale.invoice_number || '-'}</span>
                        <span class="text-muted small">${sale.created_at || ''}</span>
                        <span class="fw-semibold text-success">₹${Number(sale.total_amount || 0).toFixed(2)}</span>
                        <span class="badge bg-light text-dark border">${(order.items || []).length} line(s)</span>
                        <span class="btn btn-sm btn-outline-success">${usable ? 'Select Bill' : 'Already Returned'}</span>
                    </button>`;
                }).join('');
                list.querySelectorAll('[data-invoice]').forEach(row => row.addEventListener('click', function () {
                    if (this.classList.contains('disabled')) return;
                    document.getElementById('orderSearchInput').value = this.dataset.invoice;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('newReturnModal')).show();
                    document.getElementById('btnSearchOrder').click();
                }));
                list.style.display = 'block';
            })
            .catch(() => { button.disabled = false; message.textContent = 'Unable to lookup invoices right now.'; });
    }
    button.addEventListener('click', lookup);
    input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); lookup(); } });
});
</script>
