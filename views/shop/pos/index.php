<?php
?>
<style>
.item-billed-price-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    width: 125px;
    margin-left: auto;
}
.item-billed-price-wrap .input-group-text {
    background-color: #f8fafc !important;
    border: 1.5px solid #cbd5e1 !important;
    border-right: none !important;
    color: #475569 !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    border-radius: 6px 0 0 6px !important;
    padding: 5px 8px !important;
}
.item-billed-price-input {
    border: 1.5px solid #cbd5e1 !important;
    background-color: #ffffff !important;
    color: #0d6efd !important;
    font-weight: 700 !important;
    font-size: 13.5px !important;
    border-radius: 0 6px 6px 0 !important;
    padding: 5px 8px !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}
.item-billed-price-input:hover {
    border-color: #94a3b8 !important;
}
.item-billed-price-input:focus {
    border-color: #0d6efd !important;
    background-color: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25) !important;
    outline: none !important;
}
</style>
<div class="container-fluid py-3">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
            <a href="<?= url('shop/dashboard') ?>" class="btn btn-sm btn-light border rounded-circle me-3" title="Back to Dashboard">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-0 text-dark"><i class="bi bi-qr-code-scan text-primary me-2"></i>POS Billing Terminal</h4>
                <small class="text-muted"><i class="bi bi-shop me-1"></i><?= e($shop['name']) ?> • Staff: <?= e(Session::user()['full_name'] ?? 'Cashier') ?></small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill"><i class="bi bi-circle-fill me-1 small"></i> QR Scanner Active</span>
        </div>
    </div>

    <div id="exchangeCreditBanner" class="alert alert-success border-0 rounded-4 shadow-sm mb-3 py-2 px-3 d-none align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i class="bi bi-arrow-left-right fs-4 text-success me-2"></i>
            <div>
                <strong class="text-dark">Exchange Replacement Credit Active:</strong> 
                <span class="badge bg-success font-monospace fs-6" id="exchangeCreditAmountBadge">₹0.00</span> 
                from Return <strong class="font-monospace" id="exchangeReturnRefText">#RET</strong> for <strong id="exchangeCustomerText">Customer</strong>.
            </div>
        </div>
        <span class="badge bg-white text-success border border-success fw-bold px-3 py-2">
            <i class="bi bi-check2-circle me-1"></i> Auto-Deducted from Total
        </span>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <!-- QR Scanner Box Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white border-top border-4 border-primary">
                <div class="card-body p-4">
                    <label class="form-label fw-bold text-dark fs-5 mb-2">
                        <i class="bi bi-upc-scan text-primary me-2"></i>Scan Product QR Code
                    </label>
                    <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                        <span class="input-group-text bg-primary text-white border-0"><i class="bi bi-qr-code fs-4"></i></span>
                        <input type="text" id="qrInput" class="form-control border-0 bg-light font-monospace fw-bold" 
                               placeholder="Point QR scanner or type code & hit Enter..." autofocus autocomplete="off">
                        <button class="btn btn-primary px-4 fw-semibold" id="btnManualScan" type="button">
                            <i class="bi bi-arrow-right-circle me-1"></i> Add
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Each scanned piece is added one by one.</small>
                        <small class="text-primary fw-semibold" id="scanStatus">Ready for next scan...</small>
                    </div>
                </div>
            </div>

            <!-- Instructions Notice -->
            <div class="card border-0 bg-light rounded-4 p-3 mt-3">
                <div class="d-flex align-items-center text-muted small">
                    <i class="bi bi-keyboard fs-4 me-3 text-secondary"></i>
                    <div>
                        <strong>Fast POS Shortcuts:</strong> Press <code>F2</code> to Complete Sale • Piece serial QR barcodes are automatically verified one by one.
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Modern POS Cart Ledger & Checkout Terminal -->
        <div class="col-lg-7">
            <div class="card pos-billing-card h-100 d-flex flex-column">
                <!-- Customer Details & Staff Info Header (Clean 2x2 Grid with floating focus) -->
                <div class="pos-customer-header p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pos-badge-icon bg-primary-subtle text-primary shadow-sm">
                                <i class="bi bi-person-bounding-box"></i>
                            </span>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 13.5px; line-height: 1.2;">Customer & Billed By</div>
                                <div class="text-muted" style="font-size: 11px;">Mandatory fields marked with <span class="text-danger fw-bold">*</span></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1" style="font-size: 10.5px;">
                                <i class="bi bi-asterisk me-1"></i>Name, Phone, Address
                            </span>
                            <span id="custBadge" class="badge bg-secondary-subtle text-secondary px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 11px;">
                                <i class="bi bi-person me-1"></i>Walk-in
                            </span>
                        </div>
                    </div>

                    <!-- 4 Structured Fields in 2x2 Layout -->
                    <div class="row g-2.5 position-relative">
                        <!-- 1. Customer Phone (Required with Live Auto-suggest) -->
                        <div class="col-12 col-sm-6">
                            <div class="pos-field-wrap">
                                <label class="pos-field-label" for="custPhone">
                                    <i class="bi bi-telephone text-primary me-1"></i>Mobile Number <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm pos-input-group">
                                    <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                    <input type="tel" id="custPhone" class="form-control" placeholder="10-digit mobile number *" required autocomplete="off">
                                </div>
                                <!-- Suggestions Dropdown -->
                                <div id="custSuggestions" class="list-group position-absolute shadow-lg rounded-3 mt-1 pos-suggest-dropdown" style="display:none; z-index: 1050; width: calc(100% - 12px); max-height: 220px; overflow-y: auto;"></div>
                            </div>
                        </div>

                        <!-- 2. Customer Name (Required) -->
                        <div class="col-12 col-sm-6">
                            <div class="pos-field-wrap">
                                <label class="pos-field-label" for="custName">
                                    <i class="bi bi-person text-primary me-1"></i>Customer Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm pos-input-group">
                                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" id="custName" class="form-control" placeholder="Customer full name *" required autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <!-- 3. Customer Address (Required) -->
                        <div class="col-12 col-sm-6">
                            <div class="pos-field-wrap">
                                <label class="pos-field-label" for="custAddress">
                                    <i class="bi bi-geo-alt text-primary me-1"></i>Customer Address <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm pos-input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt-fill"></i></span>
                                    <input type="text" id="custAddress" class="form-control" placeholder="Billing address / city *" required autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <!-- 4. Staff / Cashier (Employee ID/Name) -->
                        <div class="col-12 col-sm-6">
                            <div class="pos-field-wrap">
                                <label class="pos-field-label" for="employeeInput">
                                    <i class="bi bi-person-badge text-primary me-1"></i>Billed By Staff
                                </label>
                                <div class="input-group input-group-sm pos-input-group">
                                    <span class="input-group-text"><i class="bi bi-person-badge-fill"></i></span>
                                    <input type="text" id="employeeInput" class="form-control fw-semibold text-dark" 
                                           placeholder="Cashier / Employee" 
                                           value="<?= e(Session::user()['full_name'] ?? (Session::user()['username'] ?? '')) ?>" 
                                           list="staffDataList" autocomplete="off" title="Billing Staff / Employee ID">
                                    <datalist id="staffDataList">
                                        <?php if (!empty($staffList)): ?>
                                            <?php foreach ($staffList as $stf): ?>
                                                <option value="<?= e($stf['full_name']) ?>"><?= e($stf['username']) ?> (ID: <?= $stf['id'] ?>)</option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </datalist>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cart Items Table (One-by-One Piece Listing with Per-Item Billed Price & Live Discount) -->
                <div class="card-body p-0 flex-grow-1 table-responsive pos-cart-table-wrapper" style="max-height: 380px; min-height: 220px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 pos-table" id="cartTable">
                        <thead class="pos-table-head sticky-top">
                            <tr>
                                <th class="ps-3 text-center" style="width: 38px;">#</th>
                                <th>Item Details</th>
                                <th class="text-end" style="width: 100px;">Base Price</th>
                                <th class="text-end" style="width: 110px;">GST %</th>
                                <th class="text-end" style="width: 95px;">Discount</th>
                                <th class="text-end" style="width: 130px;">Billed Price</th>
                                <th class="text-center" style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="cartBody">
                            <tr id="emptyCartRow">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <div class="pos-empty-icon mb-3">
                                            <i class="bi bi-bag-plus text-primary"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">Billing Cart is Empty</h6>
                                        <p class="text-muted small mb-0">Point your scanner at garment QR codes or type SKU on the left to start billing.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Cart Summary & Payment Footer -->
                <div class="p-3.5 border-top bg-light-subtle rounded-bottom-4 pos-billing-footer">
                    <!-- Subtotal & Scanned Badge Bar -->
                    <div class="d-flex justify-content-between align-items-center mb-2.5 pb-2 border-bottom border-light-subtle">
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5" id="cartItemCountBadge">
                            <i class="bi bi-bag-check-fill"></i> 0 Items Scanned
                        </span>
                        <div class="d-flex align-items-baseline gap-2">
                            <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Taxable Base Subtotal</span>
                            <span class="fw-bold text-dark fs-5 font-monospace" id="displaySubtotal">₹0.00</span>
                        </div>
                    </div>

                    <!-- GST Breakdown & Discount Summary Grid -->
                    <div class="row g-2.5 mb-3">
                        <!-- 1. GST Breakdown Summary (Inclusive) -->
                        <div class="col-12 col-md-6">
                            <div class="pos-modifier-card h-100 p-2.5 bg-white rounded-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="pos-modifier-label mb-0">
                                        <i class="bi bi-receipt text-primary me-1"></i>GST (Inclusive)
                                    </span>
                                    <span class="badge bg-success-subtle text-success fw-bold font-monospace px-2 py-0.5" id="displayTax">Incl. ₹0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1 text-muted px-1" style="font-size: 11.5px;">
                                    <span>CGST: <strong id="displayCgst" class="text-dark font-monospace">₹0.00</strong></span>
                                    <span>SGST: <strong id="displaySgst" class="text-dark font-monospace">₹0.00</strong></span>
                                </div>
                                <div class="text-muted mt-1 px-1" style="font-size: 10px;">
                                    <i class="bi bi-info-circle me-1"></i>Auto-calculated from product GST %
                                </div>
                            </div>
                        </div>

                        <!-- 2. Total Discount Applied Summary -->
                        <div class="col-12 col-md-6">
                            <div class="pos-modifier-card h-100 p-2.5 bg-white rounded-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="pos-modifier-label mb-0">
                                        <i class="bi bi-tags-fill text-danger me-1"></i>Total Discount Given
                                    </span>
                                    <span class="badge bg-danger-subtle text-danger fw-bold font-monospace px-2 py-0.5" id="displayDiscount">-₹0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1 text-muted px-1" style="font-size: 11.5px;">
                                    <span>Total Customer Savings:</span>
                                    <strong id="displayDiscountDetail" class="text-danger font-monospace">₹0.00</strong>
                                </div>
                                <div class="text-muted mt-1 px-1" style="font-size: 10px;">
                                    <i class="bi bi-check2-all text-success me-1"></i>Discount compared against MRP
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Net Payable Hero Card -->
                    <div class="pos-net-payable-hero rounded-4 p-3 mb-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="pos-hero-tag"><i class="bi bi-receipt-cutoff me-1"></i>NET PAYABLE AMOUNT</div>
                                <div class="pos-hero-subtitle text-white-50">
                                    <i class="bi bi-shield-check text-info me-1"></i>Includes GST & item discounts
                                    <span id="roundOffBadge" class="badge bg-info-subtle text-info ms-1 font-monospace" style="font-size: 10px; display: none;"></span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="pos-hero-total font-monospace" id="displayTotal">₹0.00</div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Mode Interactive Tiles (4 Options) -->
                    <div class="mb-3">
                        <label class="pos-field-label mb-2 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-wallet2 text-primary me-1"></i>Payment Method</span>
                            <span class="text-muted" style="font-size: 10.5px;">Select customer payment mode</span>
                        </label>
                        <div class="row g-2">
                            <!-- Cash Option -->
                            <div class="col-6 col-sm-3">
                                <input type="radio" class="btn-check pos-pay-radio" name="paymentMode" id="payCash" value="CASH" checked>
                                <label class="pos-pay-tile w-100 h-100" for="payCash">
                                    <i class="bi bi-cash-stack pos-pay-icon text-success"></i>
                                    <span class="pos-pay-name">Cash</span>
                                </label>
                            </div>
                            <!-- UPI / QR Option -->
                            <div class="col-6 col-sm-3">
                                <input type="radio" class="btn-check pos-pay-radio" name="paymentMode" id="payUPI" value="UPI">
                                <label class="pos-pay-tile w-100 h-100" for="payUPI">
                                    <i class="bi bi-qr-code-scan pos-pay-icon text-primary"></i>
                                    <span class="pos-pay-name">UPI / QR</span>
                                </label>
                            </div>
                            <!-- Card Option -->
                            <div class="col-6 col-sm-3">
                                <input type="radio" class="btn-check pos-pay-radio" name="paymentMode" id="payCard" value="CARD">
                                <label class="pos-pay-tile w-100 h-100" for="payCard">
                                    <i class="bi bi-credit-card-2-front pos-pay-icon text-warning-emphasis"></i>
                                    <span class="pos-pay-name">Card / POS</span>
                                </label>
                            </div>
                            <!-- Cash + UPI (Split Payment) Option -->
                            <div class="col-6 col-sm-3">
                                <input type="radio" class="btn-check pos-pay-radio" name="paymentMode" id="payCashUPI" value="CASH_UPI">
                                <label class="pos-pay-tile w-100 h-100" for="payCashUPI">
                                    <i class="bi bi-pie-chart-fill pos-pay-icon text-info"></i>
                                    <span class="pos-pay-name">Cash + UPI</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- DYNAMIC SPLIT PAYMENT CONTAINER (CASH + UPI) -->
                    <div id="splitPaymentSection" class="p-3 rounded-3 mb-3 border d-none pos-split-card" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark small">
                                <i class="bi bi-wallet2 text-success me-1"></i>Cash + UPI Split Breakdown
                            </span>
                            <button type="button" class="btn btn-xs btn-outline-success py-0.5 px-2 rounded-pill fw-semibold" id="btnAutoBalanceSplit" title="Auto-balance remainder into UPI" style="font-size: 11px;">
                                <i class="bi bi-magic me-1"></i>Auto-Balance
                            </button>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label text-secondary mb-1" style="font-size: 0.76rem; font-weight: 600;">
                                    <i class="bi bi-cash text-success me-1"></i>Cash Amount (₹)
                                </label>
                                <input type="number" step="0.01" min="0" id="splitCashInput" class="form-control form-control-sm text-end fw-bold font-monospace bg-white" placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-secondary mb-1" style="font-size: 0.76rem; font-weight: 600;">
                                    <i class="bi bi-qr-code text-primary me-1"></i>UPI Amount (₹)
                                </label>
                                <input type="number" step="0.01" min="0" id="splitUpiInput" class="form-control form-control-sm text-end fw-bold font-monospace bg-white" placeholder="0.00">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-secondary mb-1" style="font-size: 0.76rem; font-weight: 600;">
                                <i class="bi bi-receipt text-muted me-1"></i>UPI Ref / Transaction ID / App
                            </label>
                            <input type="text" id="splitUpiRef" class="form-control form-control-sm font-monospace bg-white" placeholder="e.g. GPay / PhonePe / Ref #402918237">
                        </div>
                        <div class="d-flex align-items-center justify-content-between small pt-2 border-top" style="border-color: #dcfce7 !important; font-size: 0.78rem;">
                            <span class="text-muted">Split Total: <strong id="lblSplitSum" class="text-dark font-monospace">₹0.00</strong></span>
                            <span id="lblSplitDiff" class="badge bg-success-subtle text-success border border-success-subtle">Balanced (₹0.00)</span>
                        </div>
                    </div>

                    <!-- Checkout Button -->
                    <button type="button" class="btn btn-pos-checkout btn-lg w-100 rounded-pill py-3 fw-bold shadow-lg position-relative overflow-hidden <?= hasPermission('pos', 'create') ? '' : 'd-none' ?>" id="btnCheckout" disabled>
                        <span class="d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-printer-fill fs-5"></i>
                            <span>Complete Sale & Print Invoice</span>
                            <span class="badge bg-white text-dark bg-opacity-25 px-2 py-1 rounded ms-1 font-monospace" style="font-size: 11px; letter-spacing: 0.5px;">F2</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include html5-qrcode scanner library for web camera scanner support -->
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let cart = [];
    const canCreateSale = <?= hasPermission('pos', 'create') ? 'true' : 'false' ?>;
    const scannedPieceTags = new Set();
    const qrInput = document.getElementById('qrInput');
    const scanStatus = document.getElementById('scanStatus');
    const cartBody = document.getElementById('cartBody');
    const emptyCartRow = document.getElementById('emptyCartRow');
    const displaySubtotal = document.getElementById('displaySubtotal');
    const displayTax = document.getElementById('displayTax');
    const displayCgst = document.getElementById('displayCgst');
    const displaySgst = document.getElementById('displaySgst');
    const displayDiscount = document.getElementById('displayDiscount');
    const displayDiscountDetail = document.getElementById('displayDiscountDetail');
    const displayTotal = document.getElementById('displayTotal');
    const btnCheckout = document.getElementById('btnCheckout');
    const employeeInput = document.getElementById('employeeInput');
    const cartItemCountBadge = document.getElementById('cartItemCountBadge');

    // Split Payment DOM elements (Cash + UPI)
    const splitPaymentSection = document.getElementById('splitPaymentSection');
    const splitCashInput = document.getElementById('splitCashInput');
    const splitUpiInput = document.getElementById('splitUpiInput');
    const splitUpiRef = document.getElementById('splitUpiRef');
    const lblSplitSum = document.getElementById('lblSplitSum');
    const lblSplitDiff = document.getElementById('lblSplitDiff');
    const btnAutoBalanceSplit = document.getElementById('btnAutoBalanceSplit');

    // Helper: Calculate current Net Payable total from cart
    function getNetPayableAmount() {
        let billedSum = 0;
        cart.forEach(item => {
            const val = parseFloat(item.billed_price);
            billedSum += isNaN(val) ? (parseFloat(item.original_price) || 0) : val;
        });
        return Math.ceil(billedSum);
    }

    // Split Payment Auto-Balance
    function autoBalanceSplit() {
        const netTotal = getNetPayableAmount();
        if (!splitCashInput || !splitUpiInput) return;
        const cashVal = parseFloat(splitCashInput.value) || 0;
        if (cashVal > 0 && cashVal < netTotal) {
            splitUpiInput.value = (netTotal - cashVal).toFixed(2);
        } else {
            const half = (netTotal / 2).toFixed(2);
            splitCashInput.value = half;
            splitUpiInput.value = (netTotal - parseFloat(half)).toFixed(2);
        }
        updateSplitSummary();
    }

    function onSplitCashChange() {
        const netTotal = getNetPayableAmount();
        const cashVal = parseFloat(splitCashInput ? splitCashInput.value : 0) || 0;
        if (splitUpiInput) {
            splitUpiInput.value = Math.max(0, netTotal - cashVal).toFixed(2);
        }
        updateSplitSummary();
    }

    function onSplitUpiChange() {
        updateSplitSummary();
    }

    function updateSplitSummary() {
        const netTotal = getNetPayableAmount();
        const cashVal = parseFloat(splitCashInput ? splitCashInput.value : 0) || 0;
        const upiVal = parseFloat(splitUpiInput ? splitUpiInput.value : 0) || 0;
        const splitSum = cashVal + upiVal;
        const diff = netTotal - splitSum;

        if (lblSplitSum) lblSplitSum.textContent = '₹' + splitSum.toFixed(2);
        if (lblSplitDiff) {
            if (Math.abs(diff) < 0.01) {
                lblSplitDiff.className = 'badge bg-success-subtle text-success border border-success-subtle';
                lblSplitDiff.textContent = 'Balanced (₹0.00)';
            } else if (diff > 0) {
                lblSplitDiff.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                lblSplitDiff.textContent = 'Remaining: ₹' + diff.toFixed(2);
            } else {
                lblSplitDiff.className = 'badge bg-danger-subtle text-danger border border-danger-subtle';
                lblSplitDiff.textContent = 'Excess: +₹' + Math.abs(diff).toFixed(2);
            }
        }
    }

    if (splitCashInput) splitCashInput.addEventListener('input', onSplitCashChange);
    if (splitUpiInput) splitUpiInput.addEventListener('input', onSplitUpiChange);
    if (btnAutoBalanceSplit) btnAutoBalanceSplit.addEventListener('click', autoBalanceSplit);

    // Toggle split payment container when payment mode radio changes
    document.querySelectorAll('input[name="paymentMode"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'CASH_UPI') {
                if (splitPaymentSection) splitPaymentSection.classList.remove('d-none');
                autoBalanceSplit();
            } else {
                if (splitPaymentSection) splitPaymentSection.classList.add('d-none');
            }
        });
    });

    // Audio Feedback Context
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    function playBeep(freq = 880, duration = 0.1, type = 'sine') {
        try {
            const osc = audioContext.createOscillator();
            const gain = audioContext.createGain();
            osc.type = type;
            osc.connect(gain);
            gain.connect(audioContext.destination);
            osc.frequency.value = freq;
            osc.start();
            gain.gain.exponentialRampToValueAtTime(0.00001, audioContext.currentTime + duration);
            osc.stop(audioContext.currentTime + duration);
        } catch(e){}
    }

    function playDuplicateAlert() {
        playBeep(320, 0.15, 'sawtooth');
        setTimeout(() => playBeep(240, 0.25, 'sawtooth'), 160);
    }

    // Auto focus QR input
    function focusQR() {
        qrInput.focus();
    }
    focusQR();
    document.addEventListener('click', function(e) {
        if (!e.target.closest('input, button, a, select, textarea')) {
            focusQR();
        }
    });

    // Handle QR Input Enter Key
    qrInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const code = this.value.trim();
            if (code) {
                lookupAndAdd(code);
                this.value = '';
            }
        }
    });

    document.getElementById('btnManualScan').addEventListener('click', function() {
        const code = qrInput.value.trim();
        if (code) {
            lookupAndAdd(code);
            qrInput.value = '';
        }
    });

    // Lookup Variant via AJAX with Duplicate Piece Tag Protection
    function lookupAndAdd(code) {
        scanStatus.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Scanning: <code>${escapeHtml(code)}</code>...`;
        scanStatus.className = 'text-primary fw-semibold';

        fetch(`<?= url('shop/pos/scan') ?>/${encodeURIComponent(code)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    const variant = data.data;

                    // 1. Damaged piece tags cannot be billed.
                    if (variant.is_damaged) {
                        playDuplicateAlert();
                        scanStatus.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> <strong class="text-danger">DAMAGED ITEM:</strong> Piece Tag <code>${escapeHtml(variant.scanned_piece_tag || code)}</code> is recorded in Damage #<strong>${escapeHtml(variant.damage_number)}</strong>. Cannot bill this product.`;
                        scanStatus.className = 'text-danger fw-bold';
                        return;
                    }

                    // 2. Check if this physical piece tag was already billed in a past invoice (bypass if SERIES)
                    const isSeries = (variant.is_series == 1 || variant.category_type === 'SERIES' || variant.qr_type === 'SERIES_BATCH' || parseInt(variant.pack_quantity) > 1);
                    if (variant.already_sold_invoice && !isSeries) {
                        playDuplicateAlert();
                        scanStatus.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> <strong class="text-danger">ALREADY SOLD:</strong> Piece Tag <code>${escapeHtml(variant.scanned_piece_tag || code)}</code> was already sold in Invoice #<strong>${escapeHtml(variant.already_sold_invoice)}</strong>! Cannot bill again.`;
                        scanStatus.className = 'text-danger fw-bold';
                        return;
                    }

                    // 3. Check if this unique piece tag is ALREADY in the current active cart (bypass if SERIES)
                    if (variant.scanned_piece_tag && scannedPieceTags.has(variant.scanned_piece_tag) && !isSeries) {
                        playDuplicateAlert();
                        scanStatus.innerHTML = `<i class="bi bi-shield-slash-fill text-warning me-1"></i> <strong class="text-dark">DUPLICATE SCAN BLOCKED:</strong> Piece Tag <code class="text-danger fw-bold">${escapeHtml(variant.scanned_piece_tag)}</code> is already in the bill! Each physical garment has a unique serial QR and can only be scanned once.`;
                        scanStatus.className = 'bg-warning-subtle text-dark border border-warning p-2 rounded-3 small fw-bold d-block mt-1';
                        
                        // Flash the corresponding row in the cart
                        flashCartItemRow(variant.scanned_piece_tag);
                        return;
                    }

                    // 4. Valid new piece scan -> Add to cart as a separate individual row (one by one)
                    playBeep(980, 0.12);
                    addItemToCart(variant);
                    const tagInfo = variant.scanned_piece_tag ? ` [Tag: ${variant.scanned_piece_tag}]` : '';
                    scanStatus.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i> Added: <strong>${escapeHtml(variant.product_name)}</strong> (${escapeHtml(variant.size_name || 'Free')}) ${tagInfo}`;
                    scanStatus.className = 'text-success fw-bold';
                } else {
                    playBeep(300, 0.25, 'triangle');
                    scanStatus.innerHTML = `<i class="bi bi-x-circle-fill text-danger me-1"></i> ` + (data.message || `QR code not found: ${escapeHtml(code)}`);
                    scanStatus.className = 'text-danger fw-bold';
                }
            })
            .catch(err => {
                playBeep(300, 0.25, 'triangle');
                scanStatus.innerHTML = `<i class="bi bi-exclamation-octagon-fill text-danger me-1"></i> Network error looking up QR code.`;
                scanStatus.className = 'text-danger fw-bold';
            })
            .finally(() => {
                focusQR();
            });
    }

    // Add Product to Cart: EACH PIECE LISTED ONE BY ONE (Quantity = 1 per row)
    function getCartItemMrp(item) {
        const mrp = parseFloat(item.mrp);
        return Number.isFinite(mrp) && mrp >= 0 ? mrp : (parseFloat(item.original_price) || 0);
    }

    function getCartItemGst(item) {
        if (item.is_complementary == 1) return 0;
        const gst = parseFloat(item.gst_percentage);
        return Number.isFinite(gst) ? Math.min(100, Math.max(0, gst)) : 5.0;
    }

    function addItemToCart(variant) {
        const isComp = (variant.is_complementary == 1 || variant.category_type === 'COMPLEMENTARY');
        const isSeries = (variant.is_series == 1 || variant.category_type === 'SERIES' || variant.qr_type === 'SERIES_BATCH' || parseInt(variant.pack_quantity) > 1);
        const pieceTag = variant.scanned_piece_tag || null;
        if (pieceTag && !isSeries) {
            scannedPieceTags.add(pieceTag);
        }

        const cartItemId = 'item_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
        let retailPrice = isComp ? 0 : (parseFloat(variant.selling_price) || 0);
        let mrp = isComp ? 0 : (parseFloat(variant.mrp) || retailPrice);
        if (!isComp && retailPrice <= 0 && mrp > 0) retailPrice = mrp;
        if (!isComp && mrp <= 0 && retailPrice > 0) mrp = retailPrice;
        const variantGst = parseFloat(variant.gst_percentage);
        const gstRate = isComp ? 0 : (Number.isFinite(variantGst) ? variantGst : 5.0);

        // Always push as an individual one-by-one row
        cart.push({
            cart_id: cartItemId,
            variant_id: variant.id,
            product_name: variant.product_name,
            sku: variant.sku,
            size_name: variant.size_name || '',
            color_name: variant.color_name || '',
            image: variant.image || null,
            is_complementary: isComp ? 1 : 0,
            is_series: isSeries ? 1 : 0,
            category_type: variant.category_type || 'NORMAL',
            original_price: mrp,
            billed_price: retailPrice,
            mrp: mrp,
            discount_amount: 0.0,
            discount_percent: 0.0,
            gst_percentage: gstRate,
            quantity: 1, // Always 1
            available_stock: parseFloat(variant.available_stock) || 0,
            scanned_tag: pieceTag,
            scanned_tags: pieceTag ? [pieceTag] : []
        });

        renderCart();
    }

    function flashCartItemRow(pieceTag) {
        const row = document.querySelector(`tr[data-piece-tag="${pieceTag}"]`);
        if (row) {
            row.classList.add('table-warning');
            setTimeout(() => row.classList.remove('table-warning'), 1500);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Live update footer totals from cart items without re-rendering the whole table
    function updateSummaryTotals() {
        let subtotalTaxable = 0;
        let totalGst = 0;
        let totalDiscount = 0;
        let totalBilled = 0;

        cart.forEach(item => {
            const orig = item.is_complementary == 1 ? 0 : getCartItemMrp(item);
            const billed = parseFloat(item.billed_price) >= 0 ? parseFloat(item.billed_price) : orig;
            const gstPct = getCartItemGst(item);

            const lineTaxable = Math.round((billed / (1 + (gstPct / 100))) * 100) / 100;
            const lineGst = Math.round((billed - lineTaxable) * 100) / 100;
            const disc = orig > billed ? Math.round((orig - billed) * 100) / 100 : 0;

            subtotalTaxable += lineTaxable;
            totalGst += lineGst;
            totalDiscount += disc;
            totalBilled += billed;
        });

        const cgstTotal = Math.round((totalGst / 2) * 100) / 100;
        const sgstTotal = Math.round((totalGst - cgstTotal) * 100) / 100;
        const netTotal = Math.ceil(totalBilled);
        const roundOff = Math.round((netTotal - totalBilled) * 100) / 100;

        if (displaySubtotal) displaySubtotal.innerText = `₹${subtotalTaxable.toFixed(2)}`;
        if (displayTax) displayTax.innerText = `Incl. ₹${totalGst.toFixed(2)}`;
        if (displayCgst) displayCgst.innerText = `₹${cgstTotal.toFixed(2)}`;
        if (displaySgst) displaySgst.innerText = `₹${sgstTotal.toFixed(2)}`;
        if (displayDiscount) displayDiscount.innerText = `-₹${totalDiscount.toFixed(2)}`;
        if (displayDiscountDetail) displayDiscountDetail.innerText = `₹${totalDiscount.toFixed(2)}`;
        if (displayTotal) displayTotal.innerText = `₹${netTotal.toFixed(2)}`;

        const roundOffBadge = document.getElementById('roundOffBadge');
        if (roundOffBadge) {
            if (roundOff > 0) {
                roundOffBadge.style.display = 'inline-block';
                roundOffBadge.innerText = `Round Off: +₹${roundOff.toFixed(2)}`;
            } else {
                roundOffBadge.style.display = 'none';
            }
        }

        if (cartItemCountBadge) {
            cartItemCountBadge.innerHTML = `<i class="bi bi-bag-check-fill"></i> ${cart.length} ${cart.length === 1 ? 'Item' : 'Items'} Scanned`;
        }

        btnCheckout.disabled = cart.length === 0 || !canCreateSale;

        // Update split payment if selected
        const payMode = document.querySelector('input[name="paymentMode"]:checked');
        if (payMode && payMode.value === 'CASH_UPI') {
            updateSplitSummary();
        }
    }

    // Render Cart Table: One Row Per Scanned Piece with Given Billed Price Input & Separate Discount
    function renderCart() {
        if (cart.length === 0) {
            cartBody.innerHTML = `
                <tr id="emptyCartRow">
                    <td colspan="7" class="text-center py-5 text-muted">
                        <div class="py-4">
                            <div class="pos-empty-icon mb-3">
                                <i class="bi bi-bag-plus text-primary"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Billing Cart is Empty</h6>
                            <p class="text-muted small mb-0">Point your scanner at garment QR codes or type SKU on the left to start billing.</p>
                        </div>
                    </td>
                </tr>`;
            btnCheckout.disabled = true;
            if (displaySubtotal) displaySubtotal.innerText = '₹0.00';
            if (displayTax) displayTax.innerText = 'Incl. ₹0.00';
            if (displayCgst) displayCgst.innerText = '₹0.00';
            if (displaySgst) displaySgst.innerText = '₹0.00';
            if (displayDiscount) displayDiscount.innerText = '-₹0.00';
            if (displayDiscountDetail) displayDiscountDetail.innerText = '₹0.00';
            if (displayTotal) displayTotal.innerText = '₹0.00';
            if (cartItemCountBadge) cartItemCountBadge.innerHTML = '<i class="bi bi-bag-check-fill"></i> 0 Items Scanned';
            return;
        }

        let html = '';
        cart.forEach((item, index) => {
            const isComp = (item.is_complementary == 1);
            const isSeries = (item.is_series == 1);
            const orig = isComp ? 0 : getCartItemMrp(item);
            const billed = isComp ? 0 : (parseFloat(item.billed_price) >= 0 ? parseFloat(item.billed_price) : orig);
            const gstPercentage = getCartItemGst(item);
            
            const lineTaxable = isComp ? 0 : Math.round((billed / (1 + (gstPercentage / 100))) * 100) / 100;
            const lineGst = isComp ? 0 : Math.round((billed - lineTaxable) * 100) / 100;
            const discountAmount = (!isComp && orig > billed) ? Math.round((orig - billed) * 100) / 100 : 0;
            const discountPct = (!isComp && orig > 0 && discountAmount > 0) ? Math.round(((discountAmount / orig) * 100) * 10) / 10 : 0;

            const itemImgHtml = item.image 
                ? `<img src="${escapeHtml(item.image)}" alt="" class="rounded-3 border me-2.5 shadow-sm" style="width:38px; height:38px; object-fit: cover;">`
                : `<div class="rounded-3 bg-light border me-2.5 d-flex align-items-center justify-content-center text-primary" style="width:38px; height:38px; min-width:38px;"><i class="bi bi-bag-check fs-5"></i></div>`;

            const tagHtml = item.scanned_tag
                ? `<span class="badge bg-dark-subtle text-dark border border-secondary-subtle font-monospace mt-0.5 d-inline-block" style="font-size:10px;"><i class="bi bi-qr-code text-primary me-1"></i>Piece Tag: ${escapeHtml(item.scanned_tag)}</span>`
                : `<span class="badge bg-light text-dark border font-monospace mt-0.5 d-inline-block" style="font-size:10px;"><i class="bi bi-qr-code text-primary me-1"></i>QR: ${escapeHtml(item.sku)}</span>`;

            let typeBadgeHtml = '';
            if (isComp) {
                typeBadgeHtml = '<span class="badge bg-warning-subtle text-dark border border-warning" style="font-size:10px;"><i class="bi bi-gift-fill text-warning me-1"></i>Complementary (₹0.00)</span>';
            } else if (isSeries) {
                typeBadgeHtml = '<span class="badge bg-info-subtle text-info border border-info" style="font-size:10px;"><i class="bi bi-collection-fill me-1"></i>Series Batch</span>';
            }

            const discountBadgeHtml = discountAmount > 0
                ? `<span class="badge bg-danger-subtle text-danger fw-bold font-monospace" style="font-size: 11px;">-₹${discountAmount.toFixed(2)}</span><div class="text-muted font-monospace mt-0.5" style="font-size: 9.5px;">(${discountPct}% off)</div>`
                : `<span class="badge bg-light text-muted font-monospace" style="font-size: 10.5px;">₹0.00</span>`;

            html += `
                <tr data-cart-id="${item.cart_id}" data-piece-tag="${escapeHtml(item.scanned_tag || '')}" class="cart-item-row align-middle">
                    <td class="ps-3 text-center" style="width: 38px;">
                        <span class="badge bg-light text-secondary rounded-pill px-2 py-1 font-monospace" style="font-size: 11px;">${index + 1}</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${itemImgHtml}
                            <div class="overflow-hidden flex-grow-1">
                                <div class="fw-bold text-dark small text-truncate" title="${escapeHtml(item.product_name)}">${escapeHtml(item.product_name)}</div>
                                <div class="d-flex flex-wrap align-items-center gap-1 mt-0.5" style="font-size:11px;">
                                    <span class="badge bg-light text-dark border font-monospace" style="font-size:10px;">${escapeHtml(item.sku)}</span> 
                                    ${item.size_name ? `<span class="badge bg-primary-subtle text-primary" style="font-size:10px;">${escapeHtml(item.size_name)}</span>` : ''} 
                                    ${item.color_name ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;">${escapeHtml(item.color_name)}</span>` : ''}
                                    ${typeBadgeHtml}
                                    <span class="badge bg-light text-secondary border font-monospace item-mrp-badge" style="font-size:10px;" title="Maximum Retail Price">${isComp ? 'Complementary' : 'MRP: ₹' + getCartItemMrp(item).toFixed(2)}</span>
                                </div>
                                ${tagHtml}
                            </div>
                        </div>
                    </td>
                    <td class="text-end fw-semibold text-secondary font-monospace item-base-price-cell" style="font-size: 12.5px;">
                        ₹${lineTaxable.toFixed(2)}
                    </td>
                    <td class="text-end item-gst-cell">
                        <input type="number" min="0" max="100" step="0.01"
                               class="form-control form-control-sm text-end font-monospace item-gst-input"
                               data-cart-id="${item.cart_id}" value="${gstPercentage}"
                               style="width: 68px; display: inline-block;"
                               title="${isComp ? 'Complementary item GST is fixed at 0%' : 'Edit GST percentage'}"
                               ${isComp ? 'readonly' : ''}>
                        <div class="text-muted font-monospace mt-0.5 item-gst-val" style="font-size: 10px;">+₹${lineGst.toFixed(2)}</div>
                    </td>
                    <td class="text-end item-discount-cell" style="width: 105px;">
                        <span class="badge bg-danger-subtle text-danger fw-bold font-monospace item-discount-badge" style="font-size: 11px;">-₹${discountAmount.toFixed(2)}</span>
                        <div class="text-muted font-monospace mt-0.5 item-discount-pct text-end" style="font-size: 9.5px;">(${discountPct}% off)</div>
                    </td>
                    <td class="text-end" style="width: 140px;">
                        <div class="item-billed-price-wrap">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="any" min="0" 
                                   class="form-control form-control-sm text-end fw-bold font-monospace item-billed-price-input" 
                                   data-cart-id="${item.cart_id}" 
                                   value="${billed.toFixed(2)}" 
                                   style="${isComp ? 'background-color: #f8fafc; color: #16a34a !important;' : ''}"
                                   title="${isComp ? 'Complementary Item (Free - ₹0.00)' : 'Click to edit Billed Price'}"
                                   ${isComp ? 'readonly' : ''}>
                        </div>
                    </td>
                    <td class="text-center pe-3" style="width: 40px;">
                        <button class="btn btn-sm btn-outline-danger border-0 rounded-circle p-0 btn-remove-item" data-idx="${index}" title="Remove Item" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                    </td>
                </tr>`;
        });

        cartBody.innerHTML = html;
        attachCartRowEvents();
        updateSummaryTotals();
    }

    function attachCartRowEvents() {
        document.querySelectorAll('.item-gst-input').forEach(inp => {
            inp.addEventListener('input', function() {
                const cartId = this.getAttribute('data-cart-id');
                const item = cart.find(it => it.cart_id === cartId);
                if (!item || item.is_complementary == 1) return;

                const enteredRate = parseFloat(this.value);
                item.gst_percentage = Number.isFinite(enteredRate)
                    ? Math.min(100, Math.max(0, enteredRate))
                    : 0;

                const billed = Math.max(0, parseFloat(item.billed_price) || 0);
                const gstPct = getCartItemGst(item);
                const lineTaxable = Math.round((billed / (1 + (gstPct / 100))) * 100) / 100;
                const lineGst = Math.round((billed - lineTaxable) * 100) / 100;
                const row = this.closest('tr');
                if (row) {
                    const baseCell = row.querySelector('.item-base-price-cell');
                    const gstValue = row.querySelector('.item-gst-val');
                    if (baseCell) baseCell.innerText = `₹${lineTaxable.toFixed(2)}`;
                    if (gstValue) gstValue.innerText = `+₹${lineGst.toFixed(2)}`;
                }

                updateSummaryTotals();
            });
        });

        // Live Input on Item Billed Price (Row-level)
        document.querySelectorAll('.item-billed-price-input').forEach(inp => {
            inp.addEventListener('input', function() {
                const cartId = this.getAttribute('data-cart-id');
                const item = cart.find(it => it.cart_id === cartId);
                if (!item) return;

                if (item.is_complementary == 1) {
                    item.billed_price = 0.0;
                    item.original_price = 0.0;
                    item.mrp = 0.0;
                    item.discount_amount = 0.0;
                    item.discount_percent = 0.0;
                    this.value = '0.00';
                    updateSummaryTotals();
                    return;
                }

                let enteredVal = parseFloat(this.value);
                if (isNaN(enteredVal) || enteredVal < 0) {
                    enteredVal = 0;
                }
                item.billed_price = enteredVal;
                if (item.original_price <= 0 && enteredVal > 0) {
                    item.original_price = enteredVal;
                    item.mrp = enteredVal;
                }

                const orig = getCartItemMrp(item);
                if (orig > 0 && enteredVal < orig) {
                    item.discount_amount = Math.round((orig - enteredVal) * 100) / 100;
                    item.discount_percent = Math.round(((item.discount_amount / orig) * 100) * 10) / 10;
                } else {
                    item.discount_amount = 0;
                    item.discount_percent = 0;
                }

                const gstPct = getCartItemGst(item);
                const lineTaxable = Math.round((enteredVal / (1 + (gstPct / 100))) * 100) / 100;
                const lineGst = Math.round((enteredVal - lineTaxable) * 100) / 100;

                // Direct DOM updates for instant responsiveness without losing cursor focus
                const row = this.closest('tr');
                if (row) {
                    const baseCell = row.querySelector('.item-base-price-cell');
                    const gstCell = row.querySelector('.item-gst-cell');
                    const discBadge = row.querySelector('.item-discount-badge');
                    const discPct = row.querySelector('.item-discount-pct');
                    const mrpBadge = row.querySelector('.item-mrp-badge');

                    if (baseCell) baseCell.innerText = `₹${lineTaxable.toFixed(2)}`;
                    const gstValue = gstCell ? gstCell.querySelector('.item-gst-val') : null;
                    if (gstValue) gstValue.innerText = `+₹${lineGst.toFixed(2)}`;
                    if (discBadge) {
                        discBadge.innerText = `-₹${item.discount_amount.toFixed(2)}`;
                        discBadge.className = item.discount_amount > 0 ? 'badge bg-danger-subtle text-danger fw-bold font-monospace item-discount-badge' : 'badge bg-light text-muted font-monospace item-discount-badge';
                    }
                    if (discPct) discPct.innerText = `(${item.discount_percent}% off)`;
                    if (mrpBadge) mrpBadge.innerText = `MRP: ₹${getCartItemMrp(item).toFixed(2)}`;
                }

                updateSummaryTotals();
            });
        });

        // 2. Remove buttons
        document.querySelectorAll('.btn-remove-item').forEach(b => {
            b.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-idx'));
                const item = cart[idx];
                if (item && item.scanned_tag) {
                    scannedPieceTags.delete(item.scanned_tag);
                }
                cart.splice(idx, 1);
                renderCart();
                focusQR();
            });
        });
    }

    // Customer Autocomplete & Details Management
    const custPhoneInput = document.getElementById('custPhone');
    const custNameInput = document.getElementById('custName');
    const custAddressInput = document.getElementById('custAddress');
    const custEmailInput = document.getElementById('custEmail');
    const custBadge = document.getElementById('custBadge');
    const custSuggestions = document.getElementById('custSuggestions');
    const extraCustFields = document.getElementById('extraCustFields');
    const btnToggleExtraCust = document.getElementById('btnToggleExtraCust');

    if (btnToggleExtraCust && extraCustFields) {
        btnToggleExtraCust.addEventListener('click', function() {
            extraCustFields.style.display = extraCustFields.style.display === 'none' ? 'flex' : 'none';
        });
    }

    // Remove validation highlights as user types
    [custPhoneInput, custNameInput, custAddressInput].forEach(inp => {
        if (inp) {
            inp.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid', 'border-danger');
                }
            });
        }
    });

    let searchTimeout = null;
    function queryCustomer(q) {
        if (!q || q.length < 2) {
            if (custSuggestions) custSuggestions.style.display = 'none';
            return;
        }

        fetch(`<?= url('shop/pos/customer-lookup') ?>?q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(c => {
                        html += `
                            <button type="button" class="list-group-item list-group-item-action p-2 text-start customer-suggestion-item" 
                                    data-name="${escapeHtml(c.name)}" 
                                    data-phone="${escapeHtml(c.phone || '')}" 
                                    data-address="${escapeHtml(c.address || '')}" 
                                    data-email="${escapeHtml(c.email || '')}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-dark small">${escapeHtml(c.name)}</strong>
                                    <span class="badge bg-primary-subtle text-primary font-monospace">${escapeHtml(c.phone || '')}</span>
                                </div>
                                ${c.address ? `<small class="text-muted text-truncate d-block" style="font-size:10px;">${escapeHtml(c.address)}</small>` : ''}
                            </button>
                        `;
                    });
                    custSuggestions.innerHTML = html;
                    custSuggestions.style.display = 'block';

                    document.querySelectorAll('.customer-suggestion-item').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            custNameInput.value = this.getAttribute('data-name') || '';
                            custPhoneInput.value = this.getAttribute('data-phone') || '';
                            if (custAddressInput) custAddressInput.value = this.getAttribute('data-address') || '';
                            if (custEmailInput) custEmailInput.value = this.getAttribute('data-email') || '';

                            [custPhoneInput, custNameInput, custAddressInput].forEach(inp => {
                                if (inp && inp.value.trim()) inp.classList.remove('is-invalid', 'border-danger');
                            });

                            custSuggestions.style.display = 'none';
                            custBadge.className = 'badge bg-success-subtle text-success';
                            custBadge.innerHTML = '<i class="bi bi-check-circle me-1"></i>Saved Customer';
                            focusQR();
                        });
                    });
                } else {
                    if (custSuggestions) custSuggestions.style.display = 'none';
                }
            })
            .catch(() => {
                if (custSuggestions) custSuggestions.style.display = 'none';
            });
    }

    if (custPhoneInput) {
        custPhoneInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const val = this.value.trim();
            if (val.length >= 1) {
                custBadge.className = 'badge bg-primary-subtle text-primary';
                custBadge.innerText = 'Customer Set';
                searchTimeout = setTimeout(() => queryCustomer(val), 250);
            } else {
                custBadge.className = 'badge bg-secondary-subtle text-secondary';
                custBadge.innerText = 'Walk-in';
                if (custSuggestions) custSuggestions.style.display = 'none';
            }
        });
    }

    if (custNameInput) {
        custNameInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const val = this.value.trim();
            if (val.length >= 2 && (!custPhoneInput || !custPhoneInput.value)) {
                searchTimeout = setTimeout(() => queryCustomer(val), 300);
            }
        });
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#custSuggestions') && !e.target.closest('#custPhone') && !e.target.closest('#custName')) {
            if (custSuggestions) custSuggestions.style.display = 'none';
        }
    });

    // Checkout execution with Mandatory Customer Details & Per-Item Calculations
    function executeCheckout() {
        if (!canCreateSale || cart.length === 0) return;

        const phoneVal = custPhoneInput ? custPhoneInput.value.trim() : '';
        const nameVal = custNameInput ? custNameInput.value.trim() : '';
        const addressVal = custAddressInput ? custAddressInput.value.trim() : '';

        // Reset visual validation highlights
        [custPhoneInput, custNameInput, custAddressInput].forEach(inp => {
            if (inp) inp.classList.remove('is-invalid', 'border-danger');
        });

        // 1. Validate Phone Number (Required)
        if (!phoneVal) {
            if (custPhoneInput) {
                custPhoneInput.classList.add('is-invalid', 'border-danger');
                custPhoneInput.focus();
            }
            alert('Customer Mobile / Phone Number is required to proceed with billing.');
            return;
        }

        // 2. Validate Customer Name (Required)
        if (!nameVal) {
            if (custNameInput) {
                custNameInput.classList.add('is-invalid', 'border-danger');
                custNameInput.focus();
            }
            alert('Customer Name is required to proceed with billing.');
            return;
        }

        // 3. Validate Address (Required)
        if (!addressVal) {
            if (custAddressInput) {
                custAddressInput.classList.add('is-invalid', 'border-danger');
                custAddressInput.focus();
            }
            alert('Customer Address is required to proceed with billing.');
            return;
        }

        btnCheckout.disabled = true;
        btnCheckout.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Processing Sale...`;

        let totalDiscount = 0;
        let totalTaxable = 0;
        let totalGst = 0;
        let totalBilled = 0;

        cart.forEach(item => {
            const orig = item.is_complementary == 1 ? 0 : getCartItemMrp(item);
            const billed = parseFloat(item.billed_price) >= 0 ? parseFloat(item.billed_price) : orig;
            const gstPct = getCartItemGst(item);

            const lineTaxable = Math.round((billed / (1 + (gstPct / 100))) * 100) / 100;
            const lineGst = Math.round((billed - lineTaxable) * 100) / 100;
            const disc = orig > billed ? Math.round((orig - billed) * 100) / 100 : 0;

            totalTaxable += lineTaxable;
            totalGst += lineGst;
            totalDiscount += disc;
            totalBilled += billed;
        });

        const netPayableTotal = Math.ceil(totalBilled);
        const payMode = document.querySelector('input[name="paymentMode"]:checked').value;
        let splitCashVal = 0;
        let splitUpiVal = 0;
        let splitUpiRefVal = '';

        if (payMode === 'CASH_UPI') {
            splitCashVal = parseFloat(splitCashInput ? splitCashInput.value : 0) || 0;
            splitUpiVal = parseFloat(splitUpiInput ? splitUpiInput.value : 0) || 0;
            splitUpiRefVal = splitUpiRef ? splitUpiRef.value.trim() : '';

            const splitSum = splitCashVal + splitUpiVal;

            if (splitCashVal <= 0 && splitUpiVal <= 0) {
                alert('Please enter split Cash and UPI amounts.');
                if (splitCashInput) splitCashInput.focus();
                btnCheckout.disabled = false;
                btnCheckout.innerHTML = `<i class="bi bi-printer-fill me-2"></i> Complete Sale & Print Invoice (F2)`;
                return;
            }

            if (Math.abs(netPayableTotal - splitSum) > 0.50) {
                alert(`Split payment amounts (Cash ₹${splitCashVal.toFixed(2)} + UPI ₹${splitUpiVal.toFixed(2)} = ₹${splitSum.toFixed(2)}) must equal the Net Payable total (₹${netPayableTotal.toFixed(2)}).`);
                if (splitCashInput) splitCashInput.focus();
                btnCheckout.disabled = false;
                btnCheckout.innerHTML = `<i class="bi bi-printer-fill me-2"></i> Complete Sale & Print Invoice (F2)`;
                return;
            }
        }

        const payload = {
            items: cart,
            customer_phone: phoneVal,
            customer_name: nameVal,
            customer_address: addressVal,
            customer_email: custEmailInput ? custEmailInput.value.trim() : '',
            employee_name: employeeInput ? employeeInput.value.trim() : '',
            employee_id: employeeInput ? employeeInput.value.trim() : '',
            discount_amount: totalDiscount,
            subtotal: totalTaxable,
            total_amount: netPayableTotal,
            payment_mode: payMode,
            cash_amount: splitCashVal,
            upi_amount: splitUpiVal,
            upi_reference: splitUpiRefVal
        };

        fetch('<?= url('shop/pos/sale') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                // Open invoice receipt in popup/new tab for immediate printing
                window.open(res.data.invoice_url, '_blank', 'width=800,height=700');
                // Reset cart, split fields & customer fields
                cart = [];
                scannedPieceTags.clear();
                if (custPhoneInput) custPhoneInput.value = '';
                if (custNameInput) custNameInput.value = '';
                if (custAddressInput) custAddressInput.value = '';
                if (custEmailInput) custEmailInput.value = '';
                if (splitCashInput) splitCashInput.value = '';
                if (splitUpiInput) splitUpiInput.value = '';
                if (splitUpiRef) splitUpiRef.value = '';
                if (splitPaymentSection) splitPaymentSection.classList.add('d-none');
                const defaultPayCash = document.getElementById('payCash');
                if (defaultPayCash) defaultPayCash.checked = true;

                [custPhoneInput, custNameInput, custAddressInput].forEach(inp => {
                    if (inp) inp.classList.remove('is-invalid', 'border-danger');
                });
                if (custBadge) {
                    custBadge.className = 'badge bg-secondary-subtle text-secondary';
                    custBadge.innerText = 'Walk-in';
                }
                if (extraCustFields) extraCustFields.style.display = 'none';
                renderCart();
                scanStatus.innerHTML = `<i class="bi bi-check2-all text-success me-1"></i> Sale <strong>#${escapeHtml(res.data.invoice_number)}</strong> completed successfully!`;
                scanStatus.className = 'text-success fw-bold';
            } else {
                alert('Sale failed: ' + (res.message || 'Unknown error'));
            }
        })
        .catch(err => {
            alert('Sale processing failed. Check network connection.');
        })
        .finally(() => {
            btnCheckout.disabled = false;
            btnCheckout.innerHTML = `<i class="bi bi-printer-fill me-2"></i> Complete Sale & Print Invoice (F2)`;
            focusQR();
        });
    }

    btnCheckout.addEventListener('click', executeCheckout);

    // Keyboard shortcut F2
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F2') {
            e.preventDefault();
            executeCheckout();
        }
    });

    // 💥 Auto-fill from Exchange / Return redirect
    const urlParams = new URLSearchParams(window.location.search);
    const prefilledPhone = urlParams.get('phone');
    const prefilledCust = urlParams.get('cust');
    const prefilledCredit = parseFloat(urlParams.get('credit')) || 0;
    const prefilledReturnRef = urlParams.get('return_ref') || '';

    if (prefilledPhone && custPhoneInput) {
        custPhoneInput.value = prefilledPhone;
    }
    if (prefilledCust && custNameInput) {
        custNameInput.value = prefilledCust;
    }
    if (prefilledCredit > 0) {
        const banner = document.getElementById('exchangeCreditBanner');
        const amtBadge = document.getElementById('exchangeCreditAmountBadge');
        const refText = document.getElementById('exchangeReturnRefText');
        const custText = document.getElementById('exchangeCustomerText');

        if (banner) {
            banner.classList.remove('d-none');
            banner.classList.add('d-flex');
        }
        if (amtBadge) amtBadge.textContent = `₹${prefilledCredit.toFixed(2)}`;
        if (refText) refText.textContent = prefilledReturnRef || 'Return Credit';
        if (custText) custText.textContent = prefilledCust || 'Customer';

        if (typeof renderCart === 'function') {
            renderCart();
        }
    }
});
</script>

<style>
/* Modern Retail POS Billing Interface Styling */
.pos-billing-card {
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.03);
    background: #ffffff;
    overflow: hidden;
}

.pos-customer-header {
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
}

.pos-badge-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.pos-field-wrap {
    position: relative;
    margin-bottom: 2px;
}

.pos-field-label {
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 4px;
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.pos-input-group .input-group-text {
    background-color: #f8fafc;
    border-color: #e2e8f0;
    color: #64748b;
    font-size: 12.5px;
    padding: 0.42rem 0.65rem;
    border-radius: 8px 0 0 8px;
}

.pos-input-group .form-control {
    background-color: #f8fafc;
    border-color: #e2e8f0;
    color: #0f172a;
    font-size: 12.5px;
    font-weight: 500;
    padding: 0.42rem 0.75rem;
    border-radius: 0 8px 8px 0;
    transition: all 0.2s ease;
}

.pos-input-group .form-control:focus {
    background-color: #ffffff;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.pos-input-group .form-control.is-invalid {
    border-color: #ef4444;
    background-color: #fef2f2;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.pos-suggest-dropdown {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(12px);
    border: 1px solid #cbd5e1;
    border-radius: 10px;
}

.pos-cart-table-wrapper {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.pos-cart-table-wrapper::-webkit-scrollbar {
    width: 6px;
}

.pos-cart-table-wrapper::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
}

.pos-table {
    margin-bottom: 0;
}

.pos-table-head {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.pos-table-head th {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-weight: 700;
    color: #64748b;
    padding: 11px 12px;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.cart-item-row {
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.15s ease;
}

.cart-item-row:hover {
    background-color: #f8fafc !important;
}

.pos-empty-icon {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background-color: #eff6ff;
    border: 1px solid #dbeafe;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
}

.pos-billing-footer {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.pos-modifier-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 12px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.pos-modifier-card:focus-within {
    border-color: #93c5fd;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.06);
}

.pos-modifier-label {
    font-size: 11.5px;
    font-weight: 700;
    color: #1e293b;
}

.pos-pill-toggle .btn {
    border-color: #cbd5e1;
    color: #64748b;
    background-color: #f8fafc;
    border-radius: 6px !important;
    margin: 0 1px;
}

.pos-pill-toggle .btn-check:checked + .btn {
    background-color: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
    box-shadow: 0 1px 3px rgba(37, 99, 235, 0.25);
}

.pos-net-payable-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.pos-hero-tag {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.8px;
    color: #94a3b8;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.pos-hero-subtitle {
    font-size: 11px;
}

.pos-hero-total {
    font-size: 28px;
    font-weight: 800;
    color: #38bdf8;
    line-height: 1.1;
    letter-spacing: -0.5px;
}

/* Payment Method Tiles */
.pos-pay-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px 8px;
    border-radius: 12px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-align: center;
}

.pos-pay-tile:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
    transform: translateY(-1px);
}

.pos-pay-icon {
    font-size: 20px;
    margin-bottom: 3px;
    transition: transform 0.2s ease;
}

.pos-pay-name {
    font-size: 12px;
    font-weight: 600;
    color: #334155;
}

.pos-pay-radio:checked + .pos-pay-tile {
    border-color: #2563eb;
    background: #eff6ff;
    box-shadow: 0 0 0 1px #2563eb, 0 4px 12px rgba(37, 99, 235, 0.12);
}

.pos-pay-radio:checked + .pos-pay-tile .pos-pay-name {
    color: #1d4ed8;
    font-weight: 700;
}

.pos-pay-radio:checked + .pos-pay-tile .pos-pay-icon {
    transform: scale(1.12);
}

/* Checkout Button */
.btn-pos-checkout {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    border: none;
    color: #ffffff;
    font-size: 15px;
    letter-spacing: 0.3px;
    transition: all 0.25s ease;
    box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35);
}

.btn-pos-checkout:hover:not(:disabled) {
    background: linear-gradient(135deg, #15803d 0%, #166534 100%);
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(22, 163, 74, 0.45);
}

.btn-pos-checkout:disabled {
    background: #cbd5e1;
    border-color: #cbd5e1;
    color: #64748b;
    box-shadow: none;
    cursor: not-allowed;
    opacity: 0.7;
}
</style>
