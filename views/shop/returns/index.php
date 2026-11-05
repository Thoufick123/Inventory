<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Shop Returns &amp; Exchanges</h2>
        <span class="text-muted">Process retail POS returns, size &amp; color exchanges, and refunds for Shop customers</span>
    </div>
</div>

<?php if (hasPermission('shop_returns', 'create') || hasPermission('shop_returns', 'process')): ?>
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white" style="border-top:3px solid #198754 !important;">
    <div class="card-body p-3">
        <label class="form-label small fw-semibold text-dark mb-2"><i class="bi bi-search text-success me-1"></i>Search Shop Customer Order</label>
        <div class="input-group input-group-lg mb-2" style="max-width:590px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-qr-code-scan text-success"></i></span>
            <input id="shopPageLookupInput" type="text" class="form-control border-start-0" placeholder="Phone number, invoice number, or customer name">
            <button id="shopPageLookupBtn" class="btn btn-success px-4 fw-semibold" type="button">Lookup Order</button>
        </div>
        <div id="shopPageLookupMessage" class="small text-primary fw-semibold mb-2" style="display:none;"></div>
        <div id="shopPageInvoiceList" class="list-group" style="display:none; max-height:245px; overflow:auto;"></div>
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
        <form method="GET" action="<?= url('shop/returns') ?>" class="row g-2 align-items-end">
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
                <a href="<?= url('shop/returns') ?>" class="btn btn-sm btn-light border">
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
            <i class="bi bi-list-check me-2 text-primary"></i>Shop Returns &amp; Exchanges Register
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
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($returns)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No Shop returns or exchanges found matching your criteria.
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
                                    <span class="fw-semibold text-primary font-monospace">
                                        <?= htmlspecialchars($row['sale_invoice'] ?? '-') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($row['customer_name'] ?? 'Shop Customer') ?></div>
                                    <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($row['customer_phone'] ?? '-') ?></small>
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
                                        <small class="text-muted"><?= htmlspecialchars($row['refund_mode'] ?? 'CASH') ?></small>
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
            <div class="modal-header border-bottom py-3 px-4 bg-white d-flex justify-content-between align-items-start position-relative">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-success text-white rounded-pill font-monospace" style="font-size:10px; padding:3px 8px;">
                            <i class="bi bi-shop me-1"></i>SHOP RETAIL POS
                        </span>
                        <span class="text-muted small">Return &amp; Exchange Portal</span>
                    </div>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="newReturnModalLabel">Step 2: Select Items &amp; Action Type</h5>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light-subtle">
                <!-- Search Input Section -->
                <div id="shopReturnSearchCard" class="card border-0 shadow-sm rounded-3 mb-4 bg-white" style="border-top:3px solid #198754 !important;">
                    <div class="card-body p-3">
                        <label class="form-label small fw-semibold text-dark mb-2"><i class="bi bi-search text-success me-1"></i>Search Shop Customer Order</label>
                        <div class="input-group input-group-lg mb-2" style="max-width:590px;">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-qr-code-scan text-success"></i></span>
                            <input type="text" id="orderSearchInput" class="form-control border-start-0" placeholder="Phone number, invoice number, or customer name">
                            <button class="btn btn-success px-4 fw-semibold" type="button" id="btnSearchOrder">
                                Lookup Order
                            </button>
                        </div>
                        <div id="shopReturnLookupMessage" class="small text-primary fw-semibold mb-2 d-none"></div>
                        <div id="invoiceSelectionContainer" class="card border-0 shadow-sm mb-4 d-none">
                            <div class="card-body">
                                <div class="fw-bold text-dark mb-2">Select the invoice to process</div>
                                <div id="invoiceSelectionList" class="list-group"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lookup Results Container -->
                <div id="lookupResultsContainer" style="display: none;">
                    <div id="returnInvoiceBlockedNotice" class="alert alert-warning d-none" role="alert">
                        <div class="fw-bold">This invoice has already been used for a return or exchange.</div>
                        <div class="small">Each invoice can only be processed once: <span id="blockedInvoiceNumber" class="font-monospace fw-semibold"></span></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="resetLookupSearch()">Search another invoice</button>
                    </div>
                    
                    <form id="processReturnForm" method="POST" action="<?= url('shop/returns/process') ?>">
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
                                <div class="col-md-2 border-end">
                                    <span class="text-muted small text-uppercase font-monospace d-block" style="font-size:10px;">Order Date</span>
                                    <strong id="metaDate" class="text-dark small d-block">-</strong>
                                </div>
                                <div class="col-md-2 border-end">
                                    <span class="text-muted small text-uppercase font-monospace d-block" style="font-size:10px;">Total Order Value</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace px-2.5 py-1 fs-6" id="metaTotal">
                                        ₹0.00
                                    </span>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1 text-nowrap" onclick="resetLookupSearch()" title="Search a different order">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Reset / New
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 2 Clickable Action Type Cards -->
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
                                            Customer returns item for direct refund via Cash, UPI, or Card.
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
                                            <span class="ms-auto badge bg-success-subtle text-success" style="font-size:11px;">Size / Color</span>
                                        </div>
                                        <p class="text-muted mb-0" style="font-size:12px; line-height:1.45;">
                                            Exchange for different size or color variant of the same garment.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Refund Mode Group (Shown when Direct Return selected) -->
                        <div class="card border-0 shadow-sm mb-4 p-3 bg-white" id="refundModeGroup" style="border-radius:12px; display:none;">
                            <!-- Header strip -->
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="fw-bold text-dark font-monospace" style="font-size:11px; letter-spacing:0.5px;">
                                    <i class="bi bi-wallet2 text-primary me-1"></i>PAYMENT METHOD
                                </span>
                                <span class="text-muted font-monospace" style="font-size:10px; letter-spacing:0.5px;">
                                    SELECT CUSTOMER PAYMENT MODE
                                </span>
                            </div>

                            <!-- 4 Visual Clickable Payment Cards -->
                            <div class="row g-2 mb-3" id="refundModeButtons">
                                <div class="col-6 col-sm-3">
                                    <div class="pay-mode-btn active p-2.5 rounded-3 text-center" onclick="selectRefundPaymentMode('CASH', this)">
                                        <i class="bi bi-cash-stack fs-3 text-success d-block mb-1"></i>
                                        <span class="fw-bold text-dark small">Cash</span>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="pay-mode-btn p-2.5 rounded-3 text-center" onclick="selectRefundPaymentMode('UPI', this)">
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
                            <input type="hidden" name="refund_mode" id="refundModeInput" value="CASH">

                            <!-- Cash + UPI Split Breakdown Container -->
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
                                <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Fitting issue, defect, customer requested refund...">
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
                                        <input type="text" id="scanReturnItemInput" class="form-control form-control-sm font-monospace border-start-0" placeholder="Scan a piece tag or SKU from this invoice..." onkeydown="if(event.key === 'Enter') { event.preventDefault(); scanReturningPiece(this.value); }">
                                        <button class="btn btn-sm btn-danger fw-bold px-2.5" type="button" onclick="scanReturningPiece(document.getElementById('scanReturnItemInput').value)">Scan</button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="returnItemsTable" style="font-size:13px;">
                                    <thead style="background:#f8f9fa; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#6c757d; font-weight:700;">
                                        <tr>
                                            <th class="ps-3 text-center" style="width:50px;">SELECT</th>
                                            <th style="min-width:230px;">OLD PRODUCT ITEM &amp; SCANNED PIECES</th>
                                            <th class="text-center" style="width:55px;">SOLD</th>
                                            <th class="text-center" style="width:85px;">RETURN QTY</th>
                                            <th style="width:130px;">CONDITION</th>
                                            <th class="text-end" style="width:100px;">UNIT RATE</th>
                                            <th class="text-end" style="width:110px;">LINE CREDIT</th>
                                            <th class="text-center pe-3" style="width:60px;">REMOVE</th>
                                        </tr>
                                    </thead>
                                    <tbody id="returnItemsTbody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Financial Settlement Calculations Box -->
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
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Process Shop Return / Exchange
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
    if (currentSaleReturnUsed) {
        alert('This invoice has already been used for a return or exchange. Each invoice can only be processed once.');
        return;
    }
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

    // Highlight selected card
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
    if (refundModeGroup) {
        refundModeGroup.style.display = (mode === 'RETURN') ? 'block' : 'none';
    }
    renderSameExchangeTable();
    recalculateSettlementSummary();
};

let loadedOrderItemsMap = {};
let currentSwapValues = {};
let currentSwapTags = {};
let currentSaleReturnUsed = false;

window.renderSameExchangeTable = function() {
    const isExchange = document.getElementById('formReturnTypeInput')?.value === 'EXCHANGE_SAME';
    document.querySelectorAll('.replacement-scan-controls').forEach(controls => {
        const itemId = controls.getAttribute('data-item-id');
        const checkbox = document.querySelector(`.item-select-cb[data-item-id="${itemId}"]`);
        const isSelected = Boolean(checkbox?.checked);
        controls.classList.toggle('d-none', !isExchange || !isSelected);

        const variantInput = document.getElementById('swapVariant_' + itemId);
        if (variantInput) variantInput.disabled = !isExchange || !isSelected;
    });
};

window.updateSameSwapRow = function(itemId) {
    const item = loadedOrderItemsMap[itemId];
    if (!item) return;

    const replacement = currentSwapValues[itemId];
    const nameEl = document.getElementById('swapProdName_' + itemId);
    const skuEl = document.getElementById('swapSku_' + itemId);
    const stockEl = document.getElementById('swapStock_' + itemId);
    if (nameEl) nameEl.textContent = replacement ? replacement.product_name : 'No replacement scanned';
    if (skuEl) skuEl.textContent = replacement ? `${replacement.sku} · ${replacement.tag_code}` : 'Scan a replacement QR/barcode';
    if (stockEl) {
        stockEl.textContent = replacement ? `${replacement.available_stock} pcs in shop` : '';
        stockEl.classList.toggle('d-none', !replacement);
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
            const replacement = currentSwapValues[itemId];
            if (replacement) chooseAmount += replacement.unit_price * qty;
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
                cashAmountInput.value = returnPaid.toFixed(2);
            }
        }
        recalculateSplitTotals();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const btnSearch = document.getElementById('btnSearchOrder');
    const searchInput = document.getElementById('orderSearchInput');
    const resultsContainer = document.getElementById('lookupResultsContainer');

    const rowPieceTagsMap = {};

    if (!btnSearch || !searchInput) return;
    btnSearch.addEventListener('click', performLookup);
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performLookup();
        }
    });

    function performLookup(selectedSaleId = 0) {
        const query = searchInput.value.trim();
        if (!selectedSaleId && !query) {
            alert('Please enter a shop invoice number, phone, or customer name.');
            return;
        }

        btnSearch.disabled = true;
        btnSearch.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Searching...';
        resultsContainer.style.display = 'none';
        const invoiceSelection = document.getElementById('invoiceSelectionContainer');
        const invoiceList = document.getElementById('invoiceSelectionList');
        invoiceSelection.classList.add('d-none');
        invoiceList.innerHTML = '';

        const lookupUrl = selectedSaleId
            ? '<?= url('shop/returns/lookup') ?>?sale_id=' + encodeURIComponent(selectedSaleId)
            : '<?= url('shop/returns/lookup') ?>?q=' + encodeURIComponent(query);
        fetch(lookupUrl)
            .then(res => res.json())
            .then(res => {
                btnSearch.disabled = false;
                btnSearch.innerHTML = '<i class="bi bi-search me-1"></i> Lookup Order';

                if (!res.success || !res.data) {
                    alert(res.message || 'No matching Shop orders found.');
                    resultsContainer.style.display = 'none';
                    return;
                }

                const matches = Array.isArray(res.data) ? res.data : [res.data];
                if (matches.length > 1 && matches.every(match => match.invoice_list_item)) {
                    matches.forEach(match => {
                        const sale = match.sale || {};
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3';
                        button.disabled = Number(sale.return_exchange_used || 0) === 1;
                        const summary = document.createElement('span');
                        summary.innerHTML = `<strong class="font-monospace">${escapeReturnHtml(sale.invoice_number || '')}</strong>
                            <span class="small text-muted d-block">${escapeReturnHtml(sale.customer_name || 'Shop Customer')} · ${escapeReturnHtml(sale.created_at || '')}</span>
                            ${button.disabled ? '<span class="small text-danger d-block fw-semibold">Already used for return / exchange</span>' : ''}`;
                        const total = document.createElement('span');
                        total.className = 'text-nowrap fw-semibold';
                        total.textContent = '₹' + Number(sale.total_amount || 0).toFixed(2);
                        button.append(summary, total);
                        button.addEventListener('click', () => performLookup(Number(sale.id)));
                        invoiceList.appendChild(button);
                    });
                    invoiceSelection.classList.remove('d-none');
                    return;
                }

                const match = matches[0];
                if (!match) {
                    alert(res.message || 'No matching Shop orders found.');
                    resultsContainer.style.display = 'none';
                    return;
                }

                const sale = match.sale || match;
                const items = match.items || res.items || [];
                currentSaleReturnUsed = Number(sale.return_exchange_used || 0) === 1;
                const blockedNotice = document.getElementById('returnInvoiceBlockedNotice');
                const processForm = document.getElementById('processReturnForm');
                if (blockedNotice) blockedNotice.classList.toggle('d-none', !currentSaleReturnUsed);
                if (processForm) processForm.classList.toggle('d-none', currentSaleReturnUsed);
                if (currentSaleReturnUsed) {
                    document.getElementById('blockedInvoiceNumber').textContent = sale.invoice_number || '';
                    resultsContainer.style.display = 'block';
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

                    if (item.scanned_qr_codes) {
                        const parts = item.scanned_qr_codes.split(',').map(s => s.trim()).filter(Boolean);
                        parts.forEach(p => rowPieceTagsMap[item.id].add(p));
                    }
                    if (item.qr_code && !rowPieceTagsMap[item.id].has(item.qr_code)) {
                        rowPieceTagsMap[item.id].add(item.qr_code);
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
                            <div class="scanned-tags-display d-flex flex-wrap gap-1 mt-1" id="waTagsDisplay_${item.id}"></div>
                            <input type="hidden" name="items[${item.id}][exchange_variant_id]" id="swapVariant_${item.id}" value="" disabled>
                            <div class="replacement-scan-controls d-none mt-2" data-item-id="${item.id}">
                                <div class="small fw-semibold text-success mb-1">Scan replacement piece</div>
                                <div class="input-group input-group-sm" style="max-width:240px;">
                                    <span class="input-group-text bg-success-subtle text-success border-success"><i class="bi bi-upc-scan"></i></span>
                                    <input type="text" id="swapTagInput_${item.id}" class="form-control form-control-sm font-monospace border-success" placeholder="Scan replacement..." style="font-size:11px;"
                                           onkeydown="if(event.key === 'Enter') { event.preventDefault(); scanSameProductSwap(${item.id}, this.value, this); }">
                                    <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="scanSameProductSwap(${item.id}, document.getElementById('swapTagInput_${item.id}').value, document.getElementById('swapTagInput_${item.id}'))">Scan</button>
                                </div>
                                <div class="small text-danger mt-1 d-none" id="swapScanError_${item.id}"></div>
                                <div class="small fw-semibold text-dark mt-1" id="swapProdName_${item.id}">No replacement scanned</div>
                                <div class="small text-muted font-monospace" id="swapSku_${item.id}">Scan a replacement QR/barcode</div>
                                <small class="badge bg-success-subtle text-success border border-success-subtle mt-1 d-none" id="swapStock_${item.id}"></small>
                            </div>
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
                        <td class="text-end fw-bold font-monospace text-danger" id="lineCredit_${item.id}" style="font-size:13px;">
                            ₹${parseFloat(item.unit_price).toFixed(2)}
                        </td>
                        <td class="text-center pe-3">
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width:28px; height:28px; font-size:11px;" onclick="toggleExcludeItem(${item.id})" id="btnExclude_${item.id}" title="Remove / Exclude this item">
                                <i class="bi bi-trash" id="trashIcon_${item.id}"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                    renderWaTagBadges(item.id);
                });

                if (tbody.children.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">All items in this order have already been returned.</td></tr>';
                }

                const badge = document.getElementById('waItemCountBadge');
                if (badge) {
                    badge.textContent = `${validItemCount} Line Item(s) Selected`;
                }

                resultsContainer.style.display = 'block';
                selectWaActionCard('EXCHANGE_SAME');
                document.getElementById('scanReturnItemInput')?.focus();
            })
            .catch(err => {
                btnSearch.disabled = false;
                btnSearch.innerHTML = '<i class="bi bi-search me-1"></i> Lookup Order';
                alert('Error looking up order: ' + err.message);
            });
    }

    window.lookupShopReturnSale = function(saleId) {
        performLookup(Number(saleId) || 0);
    };

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

        const saleId = document.getElementById('formSaleId').value;
        fetch('<?= url('api/shop/returns/lookup-piece') ?>?q=' + encodeURIComponent(tag) + '&sale_id=' + encodeURIComponent(saleId))
            .then(r => r.json())
            .then(res => {
                const payload = (res && res.data) || {};
                const variant = payload.variant || res.variant || res.product;
                const itemId = String(res.sale_item_id || payload.sale_item_id || (variant && variant.sale_item_id) || '');
                const cb = itemId ? document.querySelector(`.item-select-cb[data-item-id="${itemId}"]`) : null;
                if (!res.success || !cb) {
                    alert(res.message || `No line item found matching scanned piece "${tag}".`);
                    return;
                }

                if (!cb.checked) {
                    cb.checked = true;
                    toggleItemInputs(cb, itemId);
                }
                addWaPieceTag(itemId, payload.tag_code || res.tag_code || tag);
                const scanInput = document.getElementById('scanReturnItemInput');
                if (scanInput) scanInput.value = '';

                const row = document.getElementById('returnRow_' + itemId);
                if (row) {
                    row.classList.add('table-danger');
                    setTimeout(() => row.classList.remove('table-danger'), 1500);
                }
                recalculateSettlementSummary();
            })
            .catch(err => {
                console.error('Return-piece lookup failed:', err);
                alert(`Could not validate scanned piece "${tag}". Please try again.`);
            });
        };

    window.scanSameProductSwap = function(itemId, rawCode, inputEl) {
        const code = (rawCode || '').trim();
        if (!code) return;

        const item = loadedOrderItemsMap[itemId];
        const errorEl = document.getElementById('swapScanError_' + itemId);
        const submitButton = document.querySelector('#processReturnForm button[type="submit"]');
        if (!item) return;
        delete currentSwapValues[itemId];
        delete currentSwapTags[itemId];
        const hiddenVariant = document.getElementById('swapVariant_' + itemId);
        if (hiddenVariant) hiddenVariant.value = '';
        updateSameSwapRow(itemId);
        recalculateSettlementSummary();
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('d-none');
        }
        if (submitButton) submitButton.disabled = true;

        fetch('<?= url('api/shop/returns/lookup-piece') ?>?q=' + encodeURIComponent(code) + '&purpose=replacement')
            .then(r => r.json())
            .then(res => {
                const payload = (res && res.data) || {};
                const variant = payload.variant || res.variant || res.product;
                if (!res.success || !variant) {
                    throw new Error(res.message || 'Scanned replacement was not found.');
                }
                if (String(variant.product_id) !== String(item.product_id)) {
                    throw new Error(`Scan a replacement variant of ${item.product_name}.`);
                }

                const qty = parseInt(document.getElementById('qty_' + itemId)?.value || 1) || 1;
                const stock = parseFloat(variant.available_stock || 0);
                if (stock < qty) {
                    throw new Error(`Only ${stock} piece(s) are available in shop inventory; ${qty} required.`);
                }

                const replacementTag = payload.tag_code || res.tag_code || code;
                currentSwapValues[itemId] = {
                    variant_id: parseInt(variant.variant_id || variant.id, 10),
                    product_id: parseInt(variant.product_id, 10),
                    product_name: variant.product_name || item.product_name,
                    sku: variant.sku || '',
                    size_name: variant.size_name || '',
                    color_name: variant.color_name || '',
                    available_stock: stock,
                    unit_price: parseFloat(variant.selling_price || variant.mrp || item.unit_price || 0),
                    tag_code: replacementTag
                };
                currentSwapTags[itemId] = replacementTag;
                if (hiddenVariant) hiddenVariant.value = currentSwapValues[itemId].variant_id;
                const nameEl = document.getElementById('swapProdName_' + itemId);
                const skuEl = document.getElementById('swapSku_' + itemId);
                const stockEl = document.getElementById('swapStock_' + itemId);
                if (nameEl) nameEl.textContent = currentSwapValues[itemId].product_name;
                if (skuEl) skuEl.textContent = `${currentSwapValues[itemId].sku} · ${replacementTag}`;
                if (stockEl) {
                    stockEl.textContent = `${stock} pcs in shop`;
                    stockEl.classList.remove('d-none');
                }
                if (inputEl) inputEl.value = '';
                updateSameSwapRow(itemId);
                recalculateSettlementSummary();
            })
            .catch(err => {
                console.error('Replacement scan validation failed:', err);
                if (errorEl) {
                    errorEl.textContent = err.message;
                    errorEl.classList.remove('d-none');
                } else {
                    alert(err.message);
                }
            })
            .finally(() => {
                if (submitButton) submitButton.disabled = false;
            });
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
        const row = document.getElementById('returnRow_' + itemId);
        const trashIcon = document.getElementById('trashIcon_' + itemId);
        const btnExclude = document.getElementById('btnExclude_' + itemId);

        if (row) {
            if (isChecked) {
                row.classList.remove('opacity-50', 'bg-light');
            } else {
                row.classList.add('opacity-50', 'bg-light');
            }
        }
        if (trashIcon) {
            trashIcon.className = isChecked ? 'bi bi-trash' : 'bi bi-arrow-counterclockwise';
        }
        if (btnExclude) {
            btnExclude.title = isChecked ? 'Remove / Exclude this item' : 'Include this item';
            btnExclude.className = isChecked ? 'btn btn-sm btn-outline-danger rounded-circle p-1' : 'btn btn-sm btn-outline-success rounded-circle p-1';
        }

        const qtyEl = document.getElementById('qty_' + itemId);
        const condEl = document.getElementById('cond_' + itemId);
        const varEl = document.getElementById('var_' + itemId);
        const priceEl = document.getElementById('price_' + itemId);

        if (qtyEl) qtyEl.disabled = !isChecked;
        if (condEl) condEl.disabled = !isChecked;
        if (varEl) varEl.disabled = !isChecked;
        if (priceEl) priceEl.disabled = !isChecked;

        const checkedCount = document.querySelectorAll('.item-select-cb:checked').length;
        const totalCount = document.querySelectorAll('.item-select-cb').length;
        const badge = document.getElementById('waItemCountBadge');
        if (badge) {
            badge.textContent = `${checkedCount} of ${totalCount} Item(s) Selected`;
        }

        renderSameExchangeTable();
        recalculateSettlementSummary();
    };

    window.excludeReturnItem = function(itemId) {
        const cb = document.querySelector(`.item-select-cb[data-item-id="${itemId}"]`);
        if (cb) {
            cb.checked = false;
            toggleItemInputs(cb, itemId);
        }
    };

    window.toggleExcludeItem = function(itemId) {
        const cb = document.querySelector(`.item-select-cb[data-item-id="${itemId}"]`);
        if (cb) {
            cb.checked = !cb.checked;
            toggleItemInputs(cb, itemId);
        }
    };

    window.resetLookupSearch = function() {
        const searchInput = document.getElementById('orderSearchInput');
        if (searchInput) searchInput.value = '';
        const resultsContainer = document.getElementById('lookupResultsContainer');
        if (resultsContainer) resultsContainer.style.display = 'none';
        const invoiceSelection = document.getElementById('invoiceSelectionContainer');
        const invoiceList = document.getElementById('invoiceSelectionList');
        if (invoiceSelection) invoiceSelection.classList.add('d-none');
        if (invoiceList) invoiceList.innerHTML = '';
        const tbodyRet = document.getElementById('returnItemsTbody');
        if (tbodyRet) tbodyRet.innerHTML = '';
        loadedOrderItemsMap = {};
        currentSwapValues = {};
        currentSwapTags = {};
        currentSaleReturnUsed = false;
        const blockedNotice = document.getElementById('returnInvoiceBlockedNotice');
        const processForm = document.getElementById('processReturnForm');
        if (blockedNotice) blockedNotice.classList.add('d-none');
        if (processForm) processForm.classList.remove('d-none');
        if (searchInput) searchInput.focus();
    };

    const newReturnModalEl = document.getElementById('newReturnModal');
    if (newReturnModalEl) {
        newReturnModalEl.addEventListener('hidden.bs.modal', resetLookupSearch);
    }

    function escapeReturnHtml(value) {
        return String(value || '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[character]);
    }

    document.getElementById('processReturnForm').addEventListener('submit', function(e) {
        const selectedItems = [...document.querySelectorAll('.item-select-cb:checked')];
        const returnType = document.getElementById('formReturnTypeInput').value;
        if (currentSaleReturnUsed) {
            e.preventDefault();
            alert('This invoice has already been used for a return or exchange. Each invoice can only be processed once.');
            return;
        }
        if (selectedItems.length === 0) {
            e.preventDefault();
            alert('Select at least one invoice item to return.');
            return;
        }
        if (returnType === 'EXCHANGE_SAME') {
            const missingScan = selectedItems.find(cb => {
                const itemId = cb.getAttribute('data-item-id');
                const replacement = currentSwapValues[itemId];
                const qty = parseInt(document.getElementById('qty_' + itemId)?.value || 1) || 1;
                return !replacement || replacement.available_stock < qty;
            });
            if (missingScan) {
                e.preventDefault();
                const itemId = missingScan.getAttribute('data-item-id');
                document.getElementById('swapTagInput_' + itemId)?.focus();
                alert('Scan an available replacement product for every selected item before processing the exchange.');
                return;
            }
        }

        selectedItems.forEach(cb => {
            const itemId = cb.getAttribute('data-item-id');
            const tags = rowPieceTagsMap[itemId] ? [...rowPieceTagsMap[itemId]] : [];
            if (tags.length > 0) {
                tags.forEach(t => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = `items[${itemId}][piece_tags][]`;
                    hidden.value = t;
                    this.appendChild(hidden);
                });
            }
            const swapTag = returnType === 'EXCHANGE_SAME' ? currentSwapTags[itemId] : '';
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

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('shopPageLookupInput');
    const button = document.getElementById('shopPageLookupBtn');
    const message = document.getElementById('shopPageLookupMessage');
    const list = document.getElementById('shopPageInvoiceList');
    if (!input || !button || !message || !list) return;

    function lookupInvoices() {
        const query = input.value.trim();
        if (!query) {
            message.textContent = 'Enter a phone number or invoice number.';
            message.style.display = 'block';
            return;
        }

        button.disabled = true;
        list.style.display = 'none';
        list.innerHTML = '';
        message.textContent = 'Searching customer invoices...';
        message.style.display = 'block';

        fetch('<?= url('shop/returns/lookup') ?>?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(result => {
                button.disabled = false;
                if (!result.success || !result.data) {
                    message.textContent = result.message || 'No shop invoices found.';
                    return;
                }

                const orders = Array.isArray(result.data) ? result.data : [result.data];
                message.textContent = `This customer has ${orders.length} invoice${orders.length === 1 ? '' : 's'}. Select a bill to continue.`;

                orders.forEach(order => {
                    const sale = order.sale || order;
                    const used = Number(sale.return_exchange_used || 0) === 1;
                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2';
                    row.disabled = used;

                    const invoice = document.createElement('span');
                    invoice.className = 'font-monospace text-primary fw-semibold flex-grow-1';
                    invoice.textContent = sale.invoice_number || '-';
                    const customer = document.createElement('span');
                    customer.className = 'text-muted small';
                    customer.textContent = `${sale.customer_name || 'Shop Customer'} · ${sale.created_at || ''}`;
                    const total = document.createElement('span');
                    total.className = 'fw-semibold text-success';
                    total.textContent = '₹' + Number(sale.total_amount || 0).toFixed(2);
                    const status = document.createElement('span');
                    status.className = used ? 'btn btn-sm btn-outline-secondary' : 'btn btn-sm btn-outline-success';
                    status.textContent = used ? 'Already Used' : 'Select Bill';

                    row.append(invoice, customer, total, status);
                    row.addEventListener('click', function() {
                        document.getElementById('orderSearchInput').value = sale.invoice_number || '';
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('newReturnModal')).show();
                        window.lookupShopReturnSale(sale.id);
                    });
                    list.appendChild(row);
                });

                list.style.display = 'block';
            })
            .catch(() => {
                button.disabled = false;
                message.textContent = 'Unable to lookup invoices right now.';
                message.style.display = 'block';
            });
    }

    button.addEventListener('click', lookupInvoices);
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            lookupInvoices();
        }
    });
});
</script>
