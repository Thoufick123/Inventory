<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-globe2 me-2 text-primary"></i>Website Orders RMA &amp; Exchanges</h2>
        <span class="text-muted">Manage eCommerce return merchandise authorizations (RMA), size replacements, and payment gateway refunds</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (hasPermission('website_returns.create') || hasPermission('website_returns.process')): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newReturnModal">
            <i class="bi bi-plus-circle me-1"></i> Create RMA / Exchange
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Summary Metrics Chips -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(13, 110, 253, 0.12); color: #0d6efd;">
                    <i class="bi bi-arrow-counterclockwise fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total RMA Returns</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($totalReturnsCount ?? 0) ?></h4>
                    <small class="text-muted">eCommerce RMAs</small>
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
                    <div class="text-muted small fw-semibold">Size / Color Swaps</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($totalExchangesCount ?? 0) ?></h4>
                    <small class="text-muted">Customer replacements</small>
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
                    <div class="text-muted small fw-semibold">Gateway Refund Value</div>
                    <h4 class="fw-bold mb-0 text-danger">₹<?= number_format($totalRefundAmount ?? 0, 2) ?></h4>
                    <small class="text-muted">Settled refunds</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 p-3 rounded-3 me-3" style="background-color: rgba(255, 193, 7, 0.2); color: #856404;">
                    <i class="bi bi-shield-x fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Damaged Returns</div>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($totalDamagedQty ?? 0) ?> <span class="fs-7 fw-normal text-muted">pcs</span></h4>
                    <small class="text-muted">Warehouse Defect Pool</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('warehouse/website-orders/returns') ?>" class="row g-2 align-items-end">
            <!-- Search Keyword -->
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-semibold text-secondary mb-1">Search RMA Orders</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="RMA #, Invoice #, Customer name, Phone..." value="<?= htmlspecialchars($search ?? '') ?>">
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
                <a href="<?= url('warehouse/website-orders/returns') ?>" class="btn btn-sm btn-light border">
                    <i class="bi bi-x-circle me-1"></i> Clear
                </a>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- RMA & Exchanges History Table -->
<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-header bg-transparent border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
        <h5 class="card-title fw-bold mb-0 text-dark">
            <i class="bi bi-receipt-cutoff me-2 text-primary"></i>Website RMA &amp; Exchanges Register
        </h5>
        <span class="badge bg-light text-secondary border"><?= count($returns ?? []) ?> Records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-3">RMA / Return #</th>
                        <th>Original Order</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th class="text-center">Returned Qty</th>
                        <th>Refund / Balance</th>
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
                                No Website RMA or exchange records found matching your criteria.
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
                                    <a href="<?= url('sales/website/history?search=' . urlencode($row['sale_invoice'])) ?>" class="fw-semibold text-decoration-none">
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
                                            <i class="bi bi-box-arrow-in-left me-1"></i>RMA Return
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
                                        <small class="text-muted"><?= htmlspecialchars($row['refund_mode'] ?? 'ONLINE') ?></small>
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
                                    <?php if (hasPermission('website_returns', 'print')): ?>
                                        <a href="<?= url('warehouse/website-orders/returns/invoice/' . $row['id']) ?>" class="btn btn-sm btn-outline-primary" title="Print RMA Slip" target="_blank">
                                            <i class="bi bi-printer me-1"></i> Slip
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Process Return & Exchange (Step 1 Search & Step 2 Processing) -->
<div class="modal fade" id="newReturnModal" tabindex="-1" aria-labelledby="newReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow:hidden;">
            <!-- Modal Header -->
            <div class="modal-header border-bottom py-3 px-4 bg-white d-flex justify-content-between align-items-start position-relative">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-primary text-white rounded-pill font-monospace" style="font-size:10px; padding:3px 8px;">
                            <i class="bi bi-globe2 me-1"></i>WEBSITE ORDERS
                        </span>
                        <span class="text-muted small">RMA Return &amp; Exchange Portal</span>
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
                            <i class="bi bi-search text-primary me-1"></i>Step 1: Search Website Order / Customer
                        </label>
                        <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-upc-scan text-primary fs-5"></i></span>
                            <input type="text" id="orderSearchInput" class="form-control border-start-0 font-monospace fw-bold" 
                                   placeholder="Type Website Invoice # (e.g. INV-WEB-...), Customer Phone, or Name..." style="font-size:14px;">
                            <button class="btn btn-primary px-4 fw-bold" type="button" id="btnSearchOrder">
                                <i class="bi bi-search me-1"></i> Lookup Order
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lookup Results Container -->
                <div id="lookupResultsContainer" style="display: none;">
                    
                    <form id="processReturnForm" method="POST" action="<?= url('warehouse/website-orders/returns/process') ?>">
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

                        <!-- 2 Clickable Action Type Cards -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-2" style="font-size:13px;">
                                <i class="bi bi-grid-fill me-1 text-primary"></i>Choose Action Type for Selected Items
                            </label>
                            <div class="row g-3">
                                <!-- Card 1: Direct Return -->
                                <div class="col-12 col-md-6">
                                    <div class="action-type-card card border-2 h-100 p-3 shadow-sm" 
                                         id="webCard_RETURN" onclick="selectWebActionCard('RETURN')"
                                         style="border-radius:12px; cursor:pointer; border-color:#e2e8f0; background:#fff;">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                  style="width:32px;height:32px;background:#fef2f2;">
                                                <i class="bi bi-box-arrow-in-left text-danger" style="font-size:15px;"></i>
                                            </span>
                                            <strong class="text-dark" style="font-size:14px;">Direct Return</strong>
                                            <span class="ms-auto badge bg-danger-subtle text-danger" style="font-size:11px;">RMA Refund</span>
                                        </div>
                                        <p class="text-muted mb-0" style="font-size:12px; line-height:1.45;">
                                            Customer returns item for direct refund via Payment Gateway, UPI, Card, or Store Credit.
                                        </p>
                                    </div>
                                </div>

                                <!-- Card 2: Exchange Same Product -->
                                <div class="col-12 col-md-6">
                                    <div class="action-type-card card border-2 h-100 p-3 shadow-sm" 
                                         id="webCard_EXCHANGE_SAME" onclick="selectWebActionCard('EXCHANGE_SAME')"
                                         style="border-radius:12px; cursor:pointer; border-color:#0d6efd; background:#eff6ff;">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                  style="width:32px;height:32px;background:#dbeafe;">
                                                <i class="bi bi-arrow-left-right text-primary" style="font-size:15px;"></i>
                                            </span>
                                            <strong class="text-dark" style="font-size:14px;">Exchanged Product</strong>
                                            <span class="ms-auto badge bg-primary-subtle text-primary" style="font-size:11px;">Size / Color</span>
                                        </div>
                                        <p class="text-muted mb-0" style="font-size:12px; line-height:1.45;">
                                            Exchange for a different size or color variant of the same garment with live stock check.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Refund Mode Group (Shown when Direct Return selected) -->
                        <div class="card border-0 shadow-sm mb-4 p-3 bg-white" id="refundModeGroup" style="border-radius:12px; display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="fw-bold text-dark font-monospace" style="font-size:11px; letter-spacing:0.5px;">
                                    <i class="bi bi-wallet2 text-primary me-1"></i>PAYMENT &amp; REFUND METHOD
                                </span>
                                <span class="text-muted font-monospace" style="font-size:10px; letter-spacing:0.5px;">
                                    SELECT CUSTOMER SETTLEMENT MODE
                                </span>
                            </div>

                            <!-- 4 Visual Clickable Payment Cards -->
                            <div class="row g-2 mb-3" id="refundModeButtons">
                                <div class="col-6 col-sm-3">
                                    <div class="pay-mode-btn p-2.5 rounded-3 text-center" onclick="selectRefundPaymentMode('ONLINE', this)">
                                        <i class="bi bi-globe fs-3 text-primary d-block mb-1"></i>
                                        <span class="fw-bold text-dark small">Gateway Refund</span>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="pay-mode-btn active p-2.5 rounded-3 text-center" onclick="selectRefundPaymentMode('UPI', this)">
                                        <i class="bi bi-qr-code-scan fs-3 text-success d-block mb-1"></i>
                                        <span class="fw-bold text-dark small">UPI / Net Banking</span>
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

                            <!-- Cash + UPI Split Breakdown Container -->
                            <div id="cashUpiSplitBox" class="p-3 mb-3 rounded-3 border border-primary-subtle bg-primary-subtle bg-opacity-10 d-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="fw-bold text-primary small mb-0">
                                        <i class="bi bi-wallet2 me-1"></i>Cash + UPI Split Breakdown
                                    </label>
                                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-0.5 fw-semibold" onclick="autoBalanceSplit()">
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
                                    <label class="small text-muted fw-semibold mb-1">UPI / Gateway Ref / Transaction ID</label>
                                    <input type="text" name="upi_reference" id="upiReferenceInput" class="form-control form-control-sm font-monospace bg-white" placeholder="e.g. Razorpay #pay_82918237" autocomplete="off">
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-1 border-top border-primary-subtle">
                                    <small class="fw-semibold text-dark">Split Total: <strong class="font-monospace text-primary" id="splitTotalText">₹0.00</strong></small>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace" id="splitBalanceBadge">Balanced (₹0.00)</span>
                                </div>
                            </div>

                            <!-- Reason / Notes -->
                            <div>
                                <label class="form-label fw-bold small mb-1"><i class="bi bi-pencil-square me-1"></i>Reason / Notes</label>
                                <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Size fitting issue, defective fabric, customer requested RMA refund...">
                            </div>
                        </div>

                        <!-- Step 2A: Returning (Old) Purchased Items Table -->
                        <div class="card border-0 shadow-sm mb-4" style="border-radius:12px; overflow:hidden;">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill font-monospace px-2.5 py-1" style="font-size:11px;">
                                        <i class="bi bi-box-arrow-in-left me-1"></i>STEP 2A: RETURNING (OLD) ITEMS
                                    </span>
                                    <span class="badge text-bg-light border fw-semibold" id="webItemCountBadge" style="font-size:11px;">0 Line Item(s) Selected</span>
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

                        <!-- Step 2B: Same Product Replacement (New Size/Color) Selection Card (Shown for EXCHANGE_SAME) -->
                        <div id="sameExchangeSection" class="card border-0 shadow-sm mb-4" style="border-radius:12px; overflow:hidden;">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace px-2.5 py-1" style="font-size:11px;">
                                        <i class="bi bi-arrow-left-right me-1"></i>STEP 2B: REPLACEMENT (NEW) SIZE / COLOR SWAP
                                    </span>
                                    <span class="text-muted small">Choose new garment variant for each returned item</span>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size:11px;">
                                    Live Warehouse Stock Available
                                </span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="sameExchangeTable" style="font-size:13px;">
                                        <thead style="background:#eff6ff; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#1e40af; font-weight:700;">
                                            <tr>
                                                <th class="ps-3" style="width:28%;">Old Returning Garment</th>
                                                <th style="width:34%;">Choose New Replacement Variant (Size / Color)</th>
                                                <th style="width:22%;">Scan New Piece Tag / Barcode</th>
                                                <th class="text-end pe-3" style="width:16%;">New Price / Diff</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sameExchangeTbody">
                                            <!-- Populated dynamically per checked return item -->
                                        </tbody>
                                    </table>
                                </div>
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
                                    <strong class="font-monospace text-primary fs-6" id="calcChooseAmount">₹0.00</strong>
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
                                <i class="bi bi-check2-circle me-1"></i> Process Website Return / Exchange
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
#webCard_RETURN:hover {
    border-color: #ef4444 !important;
}
#webCard_EXCHANGE_SAME:hover {
    border-color: #0d6efd !important;
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

window.selectWebActionCard = function(mode) {
    document.getElementById('formReturnTypeInput').value = mode;

    // Reset all cards
    ['RETURN', 'EXCHANGE_SAME'].forEach(m => {
        const card = document.getElementById('webCard_' + m);
        if (card) {
            card.style.borderColor = '#e2e8f0';
            card.style.background = '#ffffff';
            card.style.boxShadow = 'none';
        }
    });

    // Highlight selected card
    const activeCard = document.getElementById('webCard_' + mode);
    if (activeCard) {
        if (mode === 'RETURN') {
            activeCard.style.borderColor = '#ef4444';
            activeCard.style.background = '#fef2f2';
            activeCard.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.15)';
        } else if (mode === 'EXCHANGE_SAME') {
            activeCard.style.borderColor = '#0d6efd';
            activeCard.style.background = '#eff6ff';
            activeCard.style.boxShadow = '0 0 0 3px rgba(13, 110, 253, 0.15)';
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

let loadedOrderItemsMap = {};
let currentSwapValues = {};
let currentSwapTags = {};

window.renderSameExchangeTable = function() {
    const tbody = document.getElementById('sameExchangeTbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    const checkboxes = document.querySelectorAll('.item-select-cb:checked');
    if (checkboxes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted small"><i class="bi bi-arrow-left-right fs-4 d-block mb-1 text-secondary opacity-50"></i>Select at least one returning item in Step 2A above to choose replacement variants.</td></tr>';
        return;
    }

    checkboxes.forEach(cb => {
        const itemId = cb.getAttribute('data-item-id');
        const item = loadedOrderItemsMap[itemId];
        if (!item) return;

        const qty = parseInt(document.getElementById('qty_' + itemId)?.value || 1) || 1;
        const unitPrice = parseFloat(item.unit_price || 0);

        let siblingOptions = `<option value="" data-price="${unitPrice}">-- Keep Same / Even Swap (₹${unitPrice.toFixed(2)}) --</option>`;
        if (item.sibling_variants && item.sibling_variants.length > 0) {
            item.sibling_variants.forEach(s => {
                const sPrice = parseFloat(s.price || unitPrice);
                const isCurrent = (parseInt(s.id) === parseInt(item.variant_id));
                siblingOptions += `<option value="${s.id}" data-price="${sPrice}" data-sku="${s.sku || ''}" data-size="${s.size_name || ''}" data-color="${s.color_name || ''}">${s.size_name || 'Free'} / ${s.color_name || 'Standard'} — ₹${sPrice.toFixed(2)} ${isCurrent ? '(Current)' : ''}</option>`;
            });
        }

        const prevVal = currentSwapValues[itemId] || '';
        const prevTag = currentSwapTags[itemId] || '';

        const tr = document.createElement('tr');
        tr.id = `sameSwapRow_${itemId}`;
        tr.innerHTML = `
            <td class="ps-3 align-middle">
                <div class="fw-bold text-dark" style="font-size:13px;">${item.product_name}</div>
                <div class="small text-muted font-monospace mt-0.5">
                    Old: <span class="badge bg-light text-dark border">${item.size_name || '-'} / ${item.color_name || '-'}</span>
                    <span class="ms-1 badge bg-secondary-subtle text-secondary font-monospace">${item.sku || '-'}</span>
                </div>
                <div class="small text-secondary mt-1">
                    Returning: <strong class="text-danger font-monospace" id="sameSwapQtyDisplay_${itemId}">${qty}</strong> pcs @ ₹${unitPrice.toFixed(2)}
                </div>
            </td>
            <td class="align-middle">
                <select name="items[${itemId}][exchange_variant_id]" id="swap_${itemId}" class="form-select form-select-sm border-primary fw-semibold" onchange="updateSameSwapRow(${itemId}); recalculateSettlementSummary();" style="font-size:12.5px;">
                    ${siblingOptions}
                </select>
                <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i>Select a different size/color variant of this same garment</small>
            </td>
            <td class="align-middle">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-primary-subtle text-primary border-primary" title="Scan Replacement Piece Tag or Barcode"><i class="bi bi-upc-scan"></i></span>
                    <input type="text" id="swapTagInput_${itemId}" class="form-control form-control-sm font-monospace border-primary" placeholder="Scan Swap Tag..." style="font-size:11px;"
                           onkeydown="if(event.key === 'Enter') { event.preventDefault(); scanSameProductSwap(${itemId}, this.value, this); }">
                    <button type="button" class="btn btn-sm btn-outline-primary px-2" onclick="scanSameProductSwap(${itemId}, document.getElementById('swapTagInput_${itemId}').value, document.getElementById('swapTagInput_${itemId}'))">OK</button>
                </div>
                <div class="scanned-replacement-tags-display d-flex flex-wrap gap-1 mt-1" id="swapTagsDisplay_${itemId}"></div>
            </td>
            <td class="text-end pe-3 align-middle">
                <div class="fw-bold font-monospace text-primary fs-6" id="newRateText_${itemId}">₹${(unitPrice * qty).toFixed(2)}</div>
                <small class="badge bg-light text-secondary border font-monospace" id="newDiffText_${itemId}">Even Swap</small>
            </td>
        `;
        tbody.appendChild(tr);

        const swapSel = document.getElementById(`swap_${itemId}`);
        if (swapSel && prevVal) {
            swapSel.value = prevVal;
        }
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
    let newUnitPrice = oldUnitPrice;
    if (swapSelect && swapSelect.value) {
        const opt = swapSelect.options[swapSelect.selectedIndex];
        newUnitPrice = parseFloat(opt.getAttribute('data-price') || oldUnitPrice);
        currentSwapValues[itemId] = swapSelect.value;
    } else {
        currentSwapValues[itemId] = '';
    }

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
                const opt = swapSelect.options[swapSelect.selectedIndex];
                const swapPrice = parseFloat(opt.getAttribute('data-price') || 0);
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
        } else if (curMode === 'UPI' || curMode === 'ONLINE') {
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

        fetch('<?= url('warehouse/website-orders/returns/lookup') ?>?q=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(res => {
                btnSearch.disabled = false;
                btnSearch.innerHTML = '<i class="bi bi-search me-1"></i> Lookup Order';

                if (!res.success || !res.data) {
                    alert(res.message || 'No matching Website orders found.');
                    resultsContainer.style.display = 'none';
                    return;
                }

                // Render the matched order (support both array and object response)
                const match = Array.isArray(res.data) ? res.data[0] : res.data;
                if (!match) {
                    alert(res.message || 'No matching Website orders found.');
                    resultsContainer.style.display = 'none';
                    return;
                }

                const sale = match.sale || match;
                const items = match.items || res.items || [];

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
                            <div class="d-flex align-items-center gap-1 mt-1">
                                <div class="input-group input-group-sm" style="max-width:170px;">
                                    <span class="input-group-text bg-light text-muted py-0 px-1" style="font-size:10px;"><i class="bi bi-qr-code"></i></span>
                                    <input type="text" id="webTagInput_${item.id}" class="form-control form-control-sm font-monospace py-0 px-1" placeholder="Scan piece tag..." style="font-size:11px; height:24px;" onkeydown="if(event.key==='Enter'){event.preventDefault(); addWebPieceTag(${item.id}, this.value); this.value='';}">
                                    <button type="button" id="webTagBtn_${item.id}" class="btn btn-sm btn-outline-danger py-0 px-2" style="height:24px; font-size:11px;" onclick="const inp=document.getElementById('webTagInput_${item.id}'); addWebPieceTag(${item.id}, inp.value); inp.value='';">+</button>
                                </div>
                            </div>
                            <div class="scanned-tags-display d-flex flex-wrap gap-1 mt-1" id="webTagsDisplay_${item.id}"></div>
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
                    renderWebTagBadges(item.id);
                });

                if (tbody.children.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">All items in this order have already been returned.</td></tr>';
                }

                const badge = document.getElementById('webItemCountBadge');
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
            addWebPieceTag(matchedItemId, tag);
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
            fetch('<?= url('api/website/returns/lookup-piece') ?>?q=' + encodeURIComponent(tag))
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
                        addWebPieceTag(matchedItemId, tag);
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
                        addWebPieceTag(matchedItemId, tag);
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
                        addWebPieceTag(matchedItemId, tag);
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
        const code = (rawCode || '').trim().toLowerCase();
        if (!code) return;

        const swapSelect = document.getElementById('swap_' + itemId);
        if (!swapSelect) return;

        let matchedVal = null;
        let matchedText = '';

        for (let i = 0; i < swapSelect.options.length; i++) {
            const opt = swapSelect.options[i];
            const optVal = opt.value;
            const optTxt = (opt.textContent || '').toLowerCase();
            const optSku = (opt.getAttribute('data-sku') || '').toLowerCase();
            const optSize = (opt.getAttribute('data-size') || '').toLowerCase();
            const optColor = (opt.getAttribute('data-color') || '').toLowerCase();

            if (optVal && (optTxt.includes(code) || (optSku && (optSku === code || code.includes(optSku) || optSku.includes(code))) || optSize === code || optColor === code)) {
                matchedVal = optVal;
                matchedText = opt.textContent;
                break;
            }
        }

        if (matchedVal !== null) {
            swapSelect.value = matchedVal;
            if (inputEl) inputEl.value = '';
            addSwapReplacementPieceTag(itemId, rawCode);
            updateSameSwapRow(itemId);
            recalculateSettlementSummary();
        } else {
            // Check live product lookup via AJAX for scanned tag or barcode
            fetch('<?= url('api/website/returns/lookup-piece') ?>?q=' + encodeURIComponent(rawCode))
                .then(r => r.json())
                .then(res => {
                    const variant = (res && (res.variant || res.data || res.product));
                    if (res && res.success && variant) {
                        const vId = String(variant.variant_id || variant.id);
                        let found = false;
                        for (let j = 0; j < swapSelect.options.length; j++) {
                            const optV = String(swapSelect.options[j].value || '');
                            const optSku = String(swapSelect.options[j].getAttribute('data-sku') || '').toLowerCase();
                            const varSku = String(variant.sku || '').toLowerCase();
                            if (optV === vId || (optSku && varSku && optSku === varSku)) {
                                swapSelect.selectedIndex = j;
                                found = true;
                                break;
                            }
                        }
                        // Also check if vId matches the original order item (Even Swap)
                        if (!found) {
                            const item = loadedOrderItemsMap[itemId];
                            if (item && (String(item.variant_id) === vId || (item.sku && variant.sku && String(item.sku).toLowerCase() === String(variant.sku).toLowerCase()))) {
                                swapSelect.value = "";
                                found = true;
                            }
                        }
                        if (inputEl) inputEl.value = '';
                        addSwapReplacementPieceTag(itemId, res.tag_code || rawCode);
                        updateSameSwapRow(itemId);
                        recalculateSettlementSummary();
                        return;
                    }
                    // Fallback: If not matched to variant, still attach scanned piece tag to this line
                    if (inputEl) inputEl.value = '';
                    addSwapReplacementPieceTag(itemId, rawCode);
                    updateSameSwapRow(itemId);
                    recalculateSettlementSummary();
                })
                .catch(err => {
                    console.error("Scan lookup error:", err);
                    if (inputEl) inputEl.value = '';
                    addSwapReplacementPieceTag(itemId, rawCode);
                    updateSameSwapRow(itemId);
                    recalculateSettlementSummary();
                });
        }
    };

    window.addSwapReplacementPieceTag = function(itemId, raw) {
        const tag = (raw || '').trim();
        if (!tag) return;
        currentSwapTags[itemId] = tag;
        const display = document.getElementById('swapTagsDisplay_' + itemId);
        if (!display) return;
        display.innerHTML = `<span class="badge bg-primary font-monospace" style="font-size:10px; cursor:pointer;" title="Click to remove" onclick="removeSwapReplacementPieceTag(${itemId})"><i class="bi bi-qr-code me-1"></i>Swap Tag: ${tag} <i class="bi bi-x ms-1"></i></span>`;
    };

    window.removeSwapReplacementPieceTag = function(itemId) {
        delete currentSwapTags[itemId];
        const display = document.getElementById('swapTagsDisplay_' + itemId);
        if (display) display.innerHTML = '';
    };

    window.addWebPieceTag = function(itemId, raw) {
        const tag = (raw || '').trim();
        if (!tag) return;
        if (!rowPieceTagsMap[itemId]) rowPieceTagsMap[itemId] = new Set();
        rowPieceTagsMap[itemId].add(tag);
        renderWebTagBadges(itemId);
    };

    window.removeWebPieceTag = function(itemId, tag) {
        if (rowPieceTagsMap[itemId]) rowPieceTagsMap[itemId].delete(tag);
        renderWebTagBadges(itemId);
    };

    function renderWebTagBadges(itemId) {
        const display = document.getElementById('webTagsDisplay_' + itemId);
        if (!display) return;
        const tags = rowPieceTagsMap[itemId] ? [...rowPieceTagsMap[itemId]] : [];
        if (tags.length === 0) { display.innerHTML = ''; return; }
        display.innerHTML = tags.map(t =>
            `<span class="badge" style="background:#1e293b; color:#38bdf8; font-family:monospace; font-size:10px; cursor:pointer;" title="Click to remove"
                   onclick="removeWebPieceTag(${itemId}, '${t.replace(/'/g, "\\'")}')"
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

        const tagInput = document.getElementById('webTagInput_' + itemId);
        const tagBtn = document.getElementById('webTagBtn_' + itemId);
        if (tagInput) tagInput.disabled = !isChecked;
        if (tagBtn) tagBtn.disabled = !isChecked;

        const checkedCount = document.querySelectorAll('.item-select-cb:checked').length;
        const totalCount = document.querySelectorAll('.item-select-cb').length;
        const badge = document.getElementById('webItemCountBadge');
        if (badge) {
            badge.textContent = `${checkedCount} of ${totalCount} Item(s) Selected`;
        }

        renderSameExchangeTable();
        recalculateSettlementSummary();
    };

    // Attach piece_tags & replacement swap tags payload on submit
    document.getElementById('processReturnForm').addEventListener('submit', function(e) {
        Object.keys(rowPieceTagsMap).forEach(itemId => {
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
</script>
