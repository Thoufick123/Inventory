<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-cart-plus me-2 text-primary"></i>Create Direct Customer Purchase</h4>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">Warehouse Direct POS</span>
        </div>
        <div class="text-muted small mt-1">Record direct customer sales from warehouse stock with instant QR scanning, split payment, and tax invoice</div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('warehouse/purchases') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Purchases List
        </a>
    </div>
</div>

<form method="POST" action="<?= url('warehouse/purchases/store') ?>" id="purchaseForm" enctype="multipart/form-data" onsubmit="return validateBeforeSubmit(event)">
    <?= CSRF::input() ?>
    <div class="row g-3 mb-4">
        <!-- ========================================================================= -->
        <!-- LEFT COLUMN: CUSTOMER, ORDER & BILLING SUMMARY -->
        <!-- ========================================================================= -->
        <div class="col-xl-4 col-lg-5">
            <!-- Customer & Order Details Card -->
            <div class="card border-0 shadow-sm mb-3 bg-white rounded-3">
                <div class="card-header bg-white py-2.5 px-3 d-flex justify-content-between align-items-center border-bottom">
                    <h6 class="card-title mb-0 fw-bold text-dark" style="font-size: 0.92rem;">
                        <i class="bi bi-person-check me-1.5 text-primary"></i>Customer &amp; Order Details
                    </h6>
                    <span id="custStatusBadge" class="badge bg-secondary-subtle text-secondary border px-2 py-0.5" style="font-size: 0.72rem;">
                        <i class="bi bi-person me-0.5"></i>Walk-in Customer
                    </span>
                </div>
                <div class="card-body p-3">
                    <input type="hidden" name="customer_id" id="selectedCustomerId" value="">

                    <!-- 1. Customer Mobile / Phone with live auto-suggest -->
                    <div class="mb-2.5 position-relative">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold mb-0 text-secondary">
                                <i class="bi bi-telephone text-primary me-1"></i>Mobile Number <span class="text-danger">*</span>
                            </label>
                            <span id="phoneCounter" class="text-muted" style="font-size: 0.72rem;">10 Digits</span>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-primary border-end-0">
                                <i class="bi bi-phone"></i>
                            </span>
                            <input type="tel" name="customer_phone" id="custPhone" 
                                   class="form-control form-control-sm border-start-0 ps-1 font-monospace fw-semibold text-dark" 
                                   placeholder="10-digit mobile number *" 
                                   maxlength="10"
                                   required 
                                   autocomplete="off" 
                                   oninput="onCustPhoneInput(this.value)" 
                                   onkeyup="onCustPhoneKeyup(event)"
                                   onkeydown="onCustPhoneKeydown(event)"
                                   onfocus="onCustPhoneFocus()">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearCustomer" onclick="clearCustomerInputs()" style="display:none;" title="Clear and add new">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <!-- Floating Customer Auto-Suggest Dropdown -->
                        <div id="customerSuggestionsList" class="dropdown-menu w-100 shadow-lg p-0 d-none" 
                             style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 1060; max-height: 260px; overflow-y: auto; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff;">
                        </div>
                    </div>

                    <!-- 2. Customer Name -->
                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold mb-1 text-secondary">
                            <i class="bi bi-person text-primary me-1"></i>Customer Name <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-primary border-end-0">
                                <i class="bi bi-person-fill"></i>
                            </span>
                            <input type="text" name="customer_name" id="custName" 
                                   class="form-control form-control-sm border-start-0 ps-1 fw-semibold text-dark" 
                                   placeholder="Customer full name *" 
                                   required 
                                   autocomplete="off" 
                                   oninput="onCustNameInput(this.value)">
                        </div>
                    </div>

                    <!-- 3. Billing Address / Street -->
                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold mb-1 text-secondary">
                            <i class="bi bi-geo-alt text-primary me-1"></i>Customer Address / Street <span class="text-muted fw-normal">(for Invoice)</span>
                        </label>
                        <input type="text" name="customer_address" id="custAddress" 
                               class="form-control form-control-sm" 
                               placeholder="Door No, Street Name, Building / Area...">
                    </div>

                    <!-- 4. District / City & State -->
                    <div class="row g-2 mb-2.5">
                        <div class="col-7">
                            <label class="form-label small fw-semibold mb-1 text-secondary">District / City <span class="text-danger">*</span></label>
                            <select name="customer_city" id="custCity" class="form-select form-select-sm" required onchange="onCustDistrictChange(this)">
                                <option value="">-- Select City --</option>
                                <optgroup label="⭐ Primary Hubs">
                                    <option value="Tiruppur" selected>Tiruppur</option>
                                    <option value="Coimbatore">Coimbatore</option>
                                    <option value="Chennai">Chennai</option>
                                    <option value="Erode">Erode</option>
                                    <option value="Salem">Salem</option>
                                    <option value="Madurai">Madurai</option>
                                    <option value="Tiruchirappalli">Tiruchirappalli (Trichy)</option>
                                </optgroup>
                                <optgroup label="All Tamil Nadu Districts">
                                    <option value="Ariyalur">Ariyalur</option>
                                    <option value="Chengalpattu">Chengalpattu</option>
                                    <option value="Chennai">Chennai</option>
                                    <option value="Coimbatore">Coimbatore</option>
                                    <option value="Cuddalore">Cuddalore</option>
                                    <option value="Dharmapuri">Dharmapuri</option>
                                    <option value="Dindigul">Dindigul</option>
                                    <option value="Erode">Erode</option>
                                    <option value="Kallakurichi">Kallakurichi</option>
                                    <option value="Kanchipuram">Kanchipuram</option>
                                    <option value="Kanyakumari">Kanyakumari</option>
                                    <option value="Karur">Karur</option>
                                    <option value="Krishnagiri">Krishnagiri</option>
                                    <option value="Madurai">Madurai</option>
                                    <option value="Mayiladuthurai">Mayiladuthurai</option>
                                    <option value="Nagapattinam">Nagapattinam</option>
                                    <option value="Namakkal">Namakkal</option>
                                    <option value="Nilgiris">Nilgiris (Ooty)</option>
                                    <option value="Perambalur">Perambalur</option>
                                    <option value="Pudukkottai">Pudukkottai</option>
                                    <option value="Ramanathapuram">Ramanathapuram</option>
                                    <option value="Ranipet">Ranipet</option>
                                    <option value="Salem">Salem</option>
                                    <option value="Sivaganga">Sivaganga</option>
                                    <option value="Tenkasi">Tenkasi</option>
                                    <option value="Thanjavur">Thanjavur</option>
                                    <option value="Theni">Theni</option>
                                    <option value="Thoothukudi">Thoothukudi (Tuticorin)</option>
                                    <option value="Tiruchirappalli">Tiruchirappalli</option>
                                    <option value="Tirunelveli">Tirunelveli</option>
                                    <option value="Tirupathur">Tirupathur</option>
                                    <option value="Tiruppur">Tiruppur</option>
                                    <option value="Tiruvallur">Tiruvallur</option>
                                    <option value="Tiruvannamalai">Tiruvannamalai</option>
                                    <option value="Tiruvarur">Tiruvarur</option>
                                    <option value="Vellore">Vellore</option>
                                    <option value="Viluppuram">Viluppuram</option>
                                    <option value="Virudhunagar">Virudhunagar</option>
                                </optgroup>
                                <option value="Other">Other / Out of State</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-semibold mb-1 text-secondary">State</label>
                            <input type="text" name="customer_state" id="custState" class="form-control form-control-sm" value="Tamil Nadu" placeholder="State">
                        </div>
                    </div>

                    <!-- History link when customer selected -->
                    <div id="custHistorySection" class="d-none pt-1 mb-2.5">
                        <button type="button" class="btn btn-xs btn-outline-primary w-100 py-1 fw-semibold" onclick="openSelectedCustomerHistory()" title="View products and previous orders bought by this customer">
                            <i class="bi bi-clock-history me-1"></i> View Bought Products &amp; History
                        </button>
                    </div>

                    <!-- Source Warehouse -->
                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold mb-1 text-secondary">Source Warehouse <span class="text-danger">*</span></label>
                        <select name="warehouse_id" class="form-select form-select-sm" id="warehouseSelect" required onchange="onWarehouseChange()" <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1) ? 'disabled' : '' ?>>
                            <?php foreach ($warehouses as $wh): ?>
                                <option value="<?= $wh['id'] ?>"><?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || count($warehouses) <= 1): ?>
                            <input type="hidden" name="warehouse_id" value="<?= e($warehouses[0]['id'] ?? (function_exists('currentWarehouseId') ? currentWarehouseId() : '')) ?>">
                        <?php endif; ?>
                    </div>

                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold mb-1 text-secondary">Purchase Date</label>
                        <input type="date" name="order_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Payment mode; payment status is derived automatically -->
                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1 text-secondary">Payment Mode</label>
                            <select name="payment_method" id="paymentMethodSelect" class="form-select form-select-sm fw-semibold" onchange="onPaymentMethodChange()">
                                <option value="CASH" selected>CASH</option>
                                <option value="UPI">UPI / GPay / PhonePe</option>
                                <option value="CASH_UPI">CASH + UPI (Split)</option>
                                <option value="CARD">Debit / Credit Card</option>
                                <option value="NET_BANKING">Bank Transfer / NEFT</option>
                                <option value="CREDIT">On Account / Credit</option>
                            </select>
                        </div>
                        <input type="hidden" name="payment_status" id="paymentStatusInput" value="PAID">
                    </div>

                    <!-- ============================================================= -->
                    <!-- DYNAMIC SPLIT PAYMENT CONTAINER (CASH + UPI) -->
                    <!-- ============================================================= -->
                    <div id="splitPaymentSection" class="p-2.5 rounded-3 mb-2.5 border d-none" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold text-success small">
                                <i class="bi bi-wallet2 me-1"></i>Cash + UPI Split Breakdown
                            </span>
                            <button type="button" class="btn btn-xs btn-outline-success py-0 px-1.5" onclick="autoBalanceSplit()" title="Auto-balance remainder into UPI">
                                <i class="bi bi-arrow-repeat me-1"></i>Auto Balance
                            </button>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label text-secondary mb-1" style="font-size: 0.76rem; font-weight: 600;">Cash Amount (₹)</label>
                                <input type="number" step="0.01" min="0" name="cash_amount" id="splitCashInput" class="form-control form-control-sm text-end fw-bold" placeholder="0.00" oninput="onSplitCashChange()">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-secondary mb-1" style="font-size: 0.76rem; font-weight: 600;">UPI Amount (₹)</label>
                                <input type="number" step="0.01" min="0" name="upi_amount" id="splitUpiInput" class="form-control form-control-sm text-end fw-bold" placeholder="0.00" oninput="onSplitUpiChange()">
                            </div>
                        </div>
                        <div class="mb-1.5">
                            <label class="form-label text-secondary mb-1" style="font-size: 0.76rem; font-weight: 600;">UPI Ref / Transaction ID / App</label>
                            <input type="text" name="upi_reference" id="splitUpiRef" class="form-control form-control-sm font-monospace" placeholder="e.g. GPay Ref #402918237">
                        </div>
                        <div class="d-flex align-items-center justify-content-between small pt-1 border-top" style="border-color: #dcfce7 !important; font-size: 0.76rem;">
                            <span class="text-muted">Split Total: <strong id="lblSplitSum" class="text-dark">₹0.00</strong></span>
                            <span id="lblSplitDiff" class="badge bg-success-subtle text-success border border-success-subtle">Balanced (₹0.00)</span>
                        </div>
                    </div>

                    <input type="hidden" name="gst_type" id="gstTypeSelect" value="CGST_SGST">
                    <input type="hidden" name="status" value="COMPLETED">

                    <div class="mb-0">
                        <label class="form-label small fw-semibold mb-1 text-secondary">Notes / Terms / Reference</label>
                        <textarea name="notes" id="purchaseNotes" class="form-control form-control-sm" rows="2" placeholder="Customer order notes, PO reference, delivery instructions..."></textarea>
                    </div>

                    <!-- ============================================================= -->
                    <!-- PAYMENT SCREENSHOT UPLOAD (shown for UPI/Card/Bank/Split)     -->
                    <!-- ============================================================= -->
                    <div class="mt-2 pt-2 border-top" id="paymentScreenshotSection" style="display:none;">
                        <label class="form-label small fw-semibold mb-1 text-secondary">
                            <i class="bi bi-image me-1 text-success"></i>
                            Payment Screenshot
                            <span class="text-muted fw-normal">(Recommended for UPI/Online payments)</span>
                        </label>
                        <div id="screenshotDropzone"
                             onclick="document.getElementById('payment_screenshot').click()"
                             ondragover="event.preventDefault(); this.classList.add('border-primary');"
                             ondragleave="this.classList.remove('border-primary');"
                             ondrop="handleScreenshotDrop(event)"
                             style="border: 2px dashed #94a3b8; border-radius: 10px; padding: 16px 10px; text-align: center; cursor: pointer; background: #f8fafc; transition: all 0.2s; min-height: 90px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <div id="screenshotPlaceholder">
                                <i class="bi bi-cloud-upload text-primary" style="font-size: 1.6rem;"></i>
                                <div class="small text-muted mt-1 fw-semibold">Click or drag &amp; drop screenshot here</div>
                                <div class="text-muted" style="font-size: 0.72rem;">JPG, PNG, WEBP &mdash; max 5MB</div>
                            </div>
                            <img id="screenshotPreview" src="" alt="Payment Proof Preview"
                                 style="display:none; max-height: 130px; max-width: 100%; border-radius: 8px; object-fit: contain; box-shadow: 0 2px 8px rgba(0,0,0,0.12);">
                        </div>
                        <input type="file" name="payment_screenshot" id="payment_screenshot"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               class="d-none" onchange="previewScreenshot(this)">
                        <div id="screenshotActions" class="d-flex justify-content-between align-items-center mt-1" style="display:none !important;">
                            <span id="screenshotFileName" class="small text-success">
                                <i class="bi bi-check-circle-fill me-1"></i><span id="screenshotFileNameText">Ready to upload</span>
                            </span>
                            <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 small" onclick="clearScreenshot()">
                                <i class="bi bi-x-circle me-1"></i>Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Financial Summary Card -->
            <div class="card border-0 shadow-sm bg-white rounded-3">
                <div class="card-header bg-light py-2 px-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.88rem;">
                        <i class="bi bi-calculator me-1.5 text-success"></i>Billing &amp; Tax Summary
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-1.5 small">
                        <span class="text-muted">Gross Amount:</span>
                        <span class="fw-bold text-dark font-monospace" id="lblGross">₹0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1.5 small">
                        <span class="text-muted">Total Discount:</span>
                        <span class="fw-semibold text-danger font-monospace" id="lblDiscount">-₹0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1.5 small">
                        <span class="text-muted">Taxable Subtotal:</span>
                        <span class="fw-bold text-dark font-monospace" id="lblSubtotal">₹0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">GST Tax Amount:</span>
                        <span class="fw-semibold text-secondary font-monospace" id="lblTax">₹0.00</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-dark" style="font-size: 1rem;">Grand Total:</span>
                        <span class="fs-4 fw-bold text-success font-monospace" id="lblTotal">₹0.00</span>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm" id="btnSubmitPurchase">
                        <i class="bi bi-check2-circle me-1.5"></i> Record Purchase &amp; Generate Invoice
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- RIGHT COLUMN: PRODUCT SCANNER & LINE ITEMS TABLE -->
        <!-- ========================================================================= -->
        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm bg-white rounded-3">
                <div class="card-header bg-white py-2.5 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="card-title mb-0 fw-bold text-dark" style="font-size: 0.92rem;">
                            <i class="bi bi-basket3 me-1.5 text-primary"></i>Purchased Products &amp; Garments
                        </h6>
                        <span class="badge bg-secondary-subtle text-secondary px-2 py-0.5" id="itemCountBadge">1 item</span>
                    </div>
                    <button type="button" class="btn btn-xs btn-primary fw-semibold px-2.5 py-1" onclick="addItemRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Product Item
                    </button>
                </div>

                <div class="card-body p-3">
                    <!-- ========================================================================= -->
                    <!-- HIGH-SPEED SMART SCANNER & PRODUCT SEARCH BAR -->
                    <!-- ========================================================================= -->
                    <div class="rounded-3 mb-3 p-2.5 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1px solid #334155;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark px-2 py-0.5 fw-bold" style="font-size: 0.72rem;">
                                    <i class="bi bi-upc-scan me-1"></i>Live Scanner &amp; Search
                                </span>
                                <span class="text-white-50" style="font-size: 0.76rem;">
                                    Scan QR tag / barcode or type product code / SKU / name — <kbd class="bg-dark text-warning border border-secondary px-1 py-0" style="font-size:0.7rem;">Alt + S</kbd> to focus
                                </span>
                            </div>
                        </div>
                        
                        <!-- Search & Scanner Input with Floating Auto-Suggest Dropdown -->
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-warning px-2.5">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" id="purchaseScanInput" class="form-control bg-dark text-white border-secondary font-monospace" 
                                       style="font-size: 0.9rem;"
                                       placeholder="Scan piece QR tag, Barcode, or type Product Code / SKU / Name..." 
                                       autocomplete="off" autofocus oninput="onLiveSearchInput(this.value)" onkeydown="onScannerInputKeydown(event)">
                                <button type="button" class="btn btn-warning px-3 fw-bold" id="btnPurchaseScanSubmit" onclick="submitCurrentScan()">
                                    <i class="bi bi-plus-lg me-1"></i> Scan &amp; Add
                                </button>
                            </div>
                            
                            <!-- Floating Live Search Dropdown -->
                            <div id="liveSearchResults" class="dropdown-menu w-100 shadow-lg p-0 d-none" 
                                 style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 1055; max-height: 300px; overflow-y: auto; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff;">
                            </div>
                        </div>

                        <div id="scanFeedbackMsg" class="mt-2 small d-none alert py-1 px-2.5 mb-0" style="font-size: 0.8rem;"></div>
                    </div>

                    <!-- Line Items Table (Shop POS Style: Compact, Spacious, No-Overflow Layout) -->
                    <div class="table-responsive pos-cart-table-wrapper rounded-3 border">
                        <table class="table table-hover align-middle mb-0 pos-table" id="itemsTable" style="font-size: 0.86rem;">
                            <thead class="pos-table-head sticky-top" style="background:#f8fafc; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#475569; font-weight:700;">
                                <tr>
                                    <th class="ps-3 text-center" style="width: 38px;">#</th>
                                    <th>Item Details</th>
                                    <th class="text-end" style="width: 100px;">Base Price</th>
                                    <th class="text-end" style="width: 120px;">GST</th>
                                    <th class="text-end" style="width: 105px;">Discount</th>
                                    <th class="text-end" style="width: 130px;">Billed Price</th>
                                    <th class="text-center" style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Initial item row -->
                                <tr class="item-row align-middle">
                                    <td class="ps-3 text-center row-num-cell" style="width: 38px;">
                                        <span class="badge bg-light text-secondary rounded-pill px-2 py-1 font-monospace" style="font-size: 11px;">1</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center item-details-container">
                                            <div class="qr-canvas-box me-2 flex-shrink-0 rounded-2 bg-white border" style="width: 42px; height: 42px; min-width: 42px; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 2px;" title="QR Code">
                                                <div class="qr-render-target" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-qr-code fs-4 text-secondary opacity-50 qr-fallback-icon"></i>
                                                </div>
                                            </div>
                                            <div class="overflow-hidden flex-grow-1">
                                                <div class="variant-select-box">
                                                    <select name="variant_id[]" class="form-select form-select-sm variant-select" required onchange="onVariantChange(this)">
                                                        <option value="">-- Select Product Variant --</option>
                                                        <?php foreach ($variants as $v): ?>
                                                            <?php 
                                                                $isComp = ((isset($v['category_type']) && $v['category_type'] === 'COMPLEMENTARY') || !empty($v['is_complementary']) || !empty($v['p_is_comp']));
                                                                $isSeries = ((isset($v['category_type']) && $v['category_type'] === 'SERIES') || ($v['qr_type'] ?? '') === 'SERIES_BATCH' || (int)($v['pack_quantity'] ?? 1) > 1);
                                                                $retailPrice = $isComp ? 0.00 : (float)($v['selling_price'] > 0 ? $v['selling_price'] : $v['mrp']);
                                                                $mrpPrice = $isComp ? 0.00 : (float)($v['mrp'] > 0 ? $v['mrp'] : $retailPrice);
                                                                $availStock = (int)($v['available_stock'] ?? 0);
                                                                $qrTag = !empty($v['qr_code']) ? $v['qr_code'] : $v['sku'];
                                                                $catName = $v['category_name'] ?? 'General';
                                                                $catType = $v['category_type'] ?? 'NORMAL';
                                                                $qrType = $v['qr_type'] ?? 'INDIVIDUAL';
                                                                $sizeText = trim(($v['size_name'] ?? 'Free') . ' / ' . ($v['color_name'] ?? 'Std'), ' /');
                                                            ?>
                                                            <option value="<?= $v['id'] ?>"
                                                                    data-name="<?= e($v['product_name']) ?>"
                                                                    data-sku="<?= e($v['sku']) ?>"
                                                                    data-qr="<?= e($qrTag) ?>"
                                                                    data-category="<?= e($catName) ?>"
                                                                    data-cat-type="<?= e($catType) ?>"
                                                                    data-is-comp="<?= $isComp ? 1 : 0 ?>"
                                                                    data-is-series="<?= $isSeries ? 1 : 0 ?>"
                                                                    data-qr-type="<?= e($qrType) ?>"
                                                                    data-size="<?= e($sizeText) ?>"
                                                                    data-price="<?= $retailPrice ?>"
                                                                    data-mrp="<?= $mrpPrice ?>"
                                                                    data-gst="<?= $isComp ? 0 : (float)($v['gst_rate'] ?? 5) ?>"
                                                                    data-stock="<?= $availStock ?>">
                                                                <?= e($v['product_name']) ?> | <?= e($v['sku']) ?> (<?= e($sizeText) ?>)<?= $isComp ? ' [Complementary - ₹0.00]' : ($isSeries ? ' [Series]' : '') ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="variant-info-display d-none">
                                                    <div class="d-flex align-items-center">
                                                        <span class="fw-bold text-dark small text-truncate product-name-text" style="font-size: 13px;">-</span>
                                                    </div>
                                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-0.5" style="font-size:11px;">
                                                        <span class="badge bg-light text-dark border font-monospace sku-badge" style="font-size:10px;">SKU</span>
                                                        <span class="badge bg-primary-subtle text-primary size-badge" style="font-size:10px;">Size</span>
                                                        <span class="badge bg-secondary-subtle text-secondary cat-badge" style="font-size:10px;">Cat</span>
                                                        <span class="badge bg-light text-secondary border font-monospace mrp-badge" style="font-size:10px;">MRP: ₹0.00</span>
                                                        <span class="badge bg-success-subtle text-success stock-badge" style="font-size:10px;">Stock: 0 pcs</span>
                                                    </div>
                                                    <div class="mt-1 scanned-tag-box d-none">
                                                        <span class="badge bg-dark font-monospace text-warning scanned-tag-badge border border-warning-subtle px-2 py-0.5" style="font-size:10px;">
                                                            <i class="bi bi-qr-code-scan me-1 text-warning"></i>QR Tag: <strong class="tag-val text-white"></strong>
                                                        </span>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="scanned_qr_codes[]" class="scanned-qrs-input" value="">
                                                <input type="hidden" name="mrp[]" class="mrp-input" value="0">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold text-secondary font-monospace base-price-cell" style="font-size: 12.5px;">
                                        <span class="base-price-display">₹0.00</span>
                                    </td>
                                    <td class="text-end gst-cell">
                                        <div class="input-group input-group-sm gst-rate-control">
                                            <input type="number" name="gst_percentage[]" class="form-control form-control-sm text-end fw-bold font-monospace gst-input" value="5" min="0" max="100" step="0.01" required oninput="calculateTotals()" aria-label="GST percentage" title="Enter GST percentage (0 to 100)">
                                            <span class="input-group-text px-1">%</span>
                                        </div>
                                        <div class="text-muted font-monospace mt-0.5 gst-val-display" style="font-size: 10px; white-space: nowrap;">+₹0.00</div>
                                    </td>
                                    <td class="text-end discount-cell item-discount-cell">
                                        <span class="badge bg-light text-muted font-monospace disc-badge-display" style="font-size: 10.5px;">₹0.00</span>
                                        <div class="text-muted font-monospace mt-0.5 disc-pct-display d-none" style="font-size: 9.5px;">(0% off)</div>
                                        <input type="hidden" name="discount_percentage[]" class="disc-pct-hidden" value="0">
                                        <input type="hidden" name="discount_amount[]" class="disc-amt-hidden" value="0">
                                    </td>
                                    <td class="text-end billed-price-cell" style="width: 130px;">
                                        <div class="input-group input-group-sm justify-content-end">
                                            <span class="input-group-text px-1.5 py-0.5 text-muted fw-bold" style="font-size: 11px;">₹</span>
                                            <input type="number" step="any" min="0" name="unit_price[]" class="form-control form-control-sm text-end fw-bold font-monospace price-input text-primary" value="0.00" style="max-width: 85px; font-size: 13px;" required oninput="onRowPriceChange(this)" title="Given Billing Price (Inclusive of GST)">
                                            <input type="hidden" name="quantity[]" class="qty-input" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center pe-3" style="width: 40px;">
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle p-0" onclick="removeItemRow(this)" title="Remove Item" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Cart Summary & Payment Footer (Shop POS Exact Flow) -->
                    <div class="p-3 border-top bg-light-subtle rounded-bottom-3 pos-billing-footer mt-3">
                        <!-- Subtotal & Scanned Badge Bar -->
                        <div class="d-flex justify-content-between align-items-center mb-2.5 pb-2 border-bottom border-light-subtle">
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5" id="cartItemCountBadge">
                                <i class="bi bi-bag-check-fill"></i> 1 Item Scanned
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
                                        <span class="pos-modifier-label mb-0 small fw-bold text-dark">
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
                                        <span class="pos-modifier-label mb-0 small fw-bold text-dark">
                                            <i class="bi bi-tags-fill text-danger me-1"></i>Total Discount Given
                                        </span>
                                        <span class="badge bg-danger-subtle text-danger fw-bold font-monospace px-2 py-0.5" id="displayDiscount">-₹0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1 text-muted px-1" style="font-size: 11.5px;">
                                        <span>Total Customer Savings:</span>
                                        <strong id="displayDiscountDetail" class="text-danger font-monospace">₹0.00</strong>
                                    </div>
                                    <div class="text-muted mt-1 px-1" style="font-size: 10px;">
                                        <i class="bi bi-check2-all text-success me-1"></i>Calculated from given billing prices
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Net Payable Hero Card -->
                        <div class="pos-net-payable-hero rounded-3 p-3 shadow-sm" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="pos-hero-tag fw-bold" style="font-size: 12px; letter-spacing: 0.5px; color: #94a3b8;">
                                        <i class="bi bi-receipt-cutoff me-1 text-info"></i>NET PAYABLE AMOUNT
                                    </div>
                                    <div class="pos-hero-subtitle text-white-50 small mt-0.5">
                                        <i class="bi bi-shield-check text-info me-1"></i>Includes GST &amp; item discounts
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="pos-hero-total font-monospace fw-bold" id="displayTotal" style="font-size: 1.85rem; color: #38bdf8;">₹0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="modal fade" id="scanAlertModal" tabindex="-1" aria-labelledby="scanAlertTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="scanAlertTitle">Unable to scan item</h5>
                        <small class="text-muted">Please check this item before continuing.</small>
                    </div>
                </div>
                <button type="button" class="btn-close align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="p-3 rounded-3 bg-light border text-secondary" id="scanAlertMessage" style="line-height: 1.55;"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary px-4 fw-semibold rounded-3" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

<style>
.search-result-item {
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
}
.search-result-item:last-child {
    border-bottom: none;
}
.search-result-item:hover, .search-result-item.active-search-item {
    background-color: #eff6ff !important;
}
.search-result-item.active-search-item {
    border-left: 3px solid #3b82f6;
}
.qr-canvas-box {
    width: 38px;
    height: 38px;
    background: #ffffff !important;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2px;
    border-radius: 4px;
    border: 1px solid #cbd5e1;
    overflow: hidden;
}
.qr-render-target {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.qr-render-target img {
    width: 34px !important;
    height: 34px !important;
    max-width: 34px !important;
    max-height: 34px !important;
    display: block !important;
    object-fit: contain !important;
    margin: 0 auto !important;
}
.qr-render-target canvas {
    display: none !important;
}
.gst-cell {
    min-width: 120px;
}
.gst-rate-control {
    width: 108px;
    min-width: 108px;
    margin-left: auto;
    margin-right: auto;
}
.gst-rate-control .gst-input {
    flex: 1 1 auto;
    min-width: 72px;
}
</style>

<!-- QRCode Library -->
<script src="<?= asset('js/qrcode.min.js') ?>"></script>
<script>
if (typeof QRCode === 'undefined') {
    document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>');
}
</script>
<script>
const VARIANTS_LIST = <?= json_encode($variants) ?>;
const CUSTOMERS_LIST = <?= json_encode($customers ?? []) ?>;
let activeSearchIndex = -1;
let searchDebounceTimer = null;

// Audio Synthesizer Beep on Scan
function playBeep(type = 'success') {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        if (type === 'success') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.12, audioCtx.currentTime);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.12);
        } else {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(220, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.25);
        }
    } catch (e) {}
}

// Payment Mode Change Handler
function onPaymentMethodChange() {
    const sel = document.getElementById('paymentMethodSelect');
    const splitSec = document.getElementById('splitPaymentSection');
    const screenshotSec = document.getElementById('paymentScreenshotSection');
    const paymentStatus = document.getElementById('paymentStatusInput');
    if (!sel) return;
    if (paymentStatus) paymentStatus.value = sel.value === 'CREDIT' ? 'PENDING' : 'PAID';

    if (sel.value === 'CASH_UPI') {
        if (splitSec) splitSec.classList.remove('d-none');
        autoBalanceSplit();
    } else {
        if (splitSec) splitSec.classList.add('d-none');
    }

    // Show screenshot section for non-cash payments
    const nonCashModes = ['UPI', 'CASH_UPI', 'CARD', 'NET_BANKING'];
    if (screenshotSec) {
        screenshotSec.style.display = nonCashModes.includes(sel.value) ? 'block' : 'none';
    }
}

// ---- Payment Screenshot Functions ----
function previewScreenshot(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 5 * 1024 * 1024) {
        alert('Screenshot file is too large. Maximum allowed size is 5MB.');
        clearScreenshot();
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('screenshotPreview');
        const placeholder = document.getElementById('screenshotPlaceholder');
        const actions = document.getElementById('screenshotActions');
        const nameText = document.getElementById('screenshotFileNameText');
        if (preview) { preview.src = e.target.result; preview.style.display = 'block'; }
        if (placeholder) placeholder.style.display = 'none';
        if (actions) actions.style.cssText = 'display: flex !important;';
        if (nameText) nameText.textContent = file.name;
    };
    reader.readAsDataURL(file);
}

function clearScreenshot() {
    const inp = document.getElementById('payment_screenshot');
    const preview = document.getElementById('screenshotPreview');
    const placeholder = document.getElementById('screenshotPlaceholder');
    const actions = document.getElementById('screenshotActions');
    if (inp) inp.value = '';
    if (preview) { preview.src = ''; preview.style.display = 'none'; }
    if (placeholder) placeholder.style.display = 'flex';
    if (actions) actions.style.cssText = 'display: none !important;';
}

function handleScreenshotDrop(event) {
    event.preventDefault();
    document.getElementById('screenshotDropzone').classList.remove('border-primary');
    const files = event.dataTransfer.files;
    if (files && files.length > 0) {
        const inp = document.getElementById('payment_screenshot');
        // Create a DataTransfer to assign to the file input
        const dt = new DataTransfer();
        dt.items.add(files[0]);
        inp.files = dt.files;
        previewScreenshot(inp);
    }
}


// Auto-balance Split Payment
function autoBalanceSplit() {
    const grandTotal = getGrandTotalValue();
    const cashInput = document.getElementById('splitCashInput');
    const upiInput = document.getElementById('splitUpiInput');
    
    let cashVal = parseFloat(cashInput.value) || 0;
    if (cashVal === 0 && grandTotal > 0) {
        cashInput.value = (0).toFixed(2);
        upiInput.value = grandTotal.toFixed(2);
    } else {
        let remainder = Math.max(0, grandTotal - cashVal);
        upiInput.value = remainder.toFixed(2);
    }
    updateSplitSummary();
}

function onSplitCashChange() {
    const grandTotal = getGrandTotalValue();
    const cashInput = document.getElementById('splitCashInput');
    const upiInput = document.getElementById('splitUpiInput');
    const cashVal = parseFloat(cashInput.value) || 0;
    
    if (cashVal <= grandTotal) {
        upiInput.value = Math.max(0, grandTotal - cashVal).toFixed(2);
    }
    updateSplitSummary();
}

function onSplitUpiChange() {
    updateSplitSummary();
}

function updateSplitSummary() {
    const grandTotal = getGrandTotalValue();
    const cashVal = parseFloat(document.getElementById('splitCashInput').value) || 0;
    const upiVal = parseFloat(document.getElementById('splitUpiInput').value) || 0;
    const splitSum = cashVal + upiVal;
    const diff = grandTotal - splitSum;

    const lblSplitSum = document.getElementById('lblSplitSum');
    const lblSplitDiff = document.getElementById('lblSplitDiff');

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

function getGrandTotalValue() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input')?.value || 1) || 1;
        const rate = parseFloat(row.querySelector('.price-input')?.value || 0) || 0;
        total += Math.max(0, qty * rate);
    });
    return total;
}

// =========================================================================
// DIRECT CUSTOMER AUTO-SUGGEST & LIVE MATCHING LOGIC
// =========================================================================
let activeCustomerIndex = -1;
let customerDebounceTimer = null;

function onCustPhoneInput(val) {
    clearTimeout(customerDebounceTimer);
    activeCustomerIndex = -1;
    const clean = (val || '').replace(/[^0-9]/g, '').slice(0, 10);
    const phoneInput = document.getElementById('custPhone');
    if (phoneInput && phoneInput.value !== clean) {
        phoneInput.value = clean;
    }
    
    const counter = document.getElementById('phoneCounter');
    if (counter) {
        counter.textContent = clean.length === 10 ? '✓ 10 Digits' : `${clean.length}/10 Digits`;
        counter.className = clean.length === 10 ? 'text-success fw-bold' : 'text-muted';
    }

    const btnClear = document.getElementById('btnClearCustomer');
    if (btnClear) btnClear.style.display = clean.length > 0 ? 'block' : 'none';

    if (clean.length === 0) {
        hideCustomerDropdown();
        updateCustBadge('Walk-in Customer', 'secondary');
        return;
    }

    customerDebounceTimer = setTimeout(() => {
        doCustomerSearch(clean, 'phone');
    }, 120);
}

function onCustPhoneKeyup(e) {
    if (['ArrowDown', 'ArrowUp', 'Enter', 'Escape', 'Tab'].includes(e.key)) return;
}

function onCustPhoneFocus() {
    const val = (document.getElementById('custPhone')?.value || '').trim();
    if (val.length > 0) {
        doCustomerSearch(val, 'phone');
    }
}

function onCustNameInput(val) {
    const clean = (val || '').trim();
    const btnClear = document.getElementById('btnClearCustomer');
    if (btnClear && clean.length > 0) btnClear.style.display = 'block';

    const hiddenId = document.getElementById('selectedCustomerId');
    if (!hiddenId || !hiddenId.value) {
        if (clean.length > 0) {
            updateCustBadge('New Customer', 'info');
        }
    }
}

function onCustDistrictChange(sel) {
    const stateInput = document.getElementById('custState');
    if (!stateInput) return;
    const val = sel.value;
    if (val && val !== 'Other') {
        stateInput.value = 'Tamil Nadu';
    } else if (val === 'Other') {
        stateInput.value = '';
        stateInput.focus();
    }
}

function updateCustBadge(text, type = 'secondary') {
    const badge = document.getElementById('custStatusBadge');
    if (!badge) return;
    badge.className = `badge bg-${type}-subtle text-${type} border border-${type}-subtle px-2 py-0.5`;
    badge.innerHTML = `<i class="bi bi-person me-0.5"></i>${escapeHtml(text)}`;
}

function showCustomerDropdown() {
    const dropdown = document.getElementById('customerSuggestionsList');
    if (dropdown) {
        dropdown.style.display = 'block';
        dropdown.classList.add('show');
        dropdown.classList.remove('d-none');
    }
}

function hideCustomerDropdown() {
    const dropdown = document.getElementById('customerSuggestionsList');
    if (dropdown) {
        dropdown.style.display = 'none';
        dropdown.classList.remove('show');
        dropdown.classList.add('d-none');
    }
}

function doCustomerSearch(query, fieldType = 'phone') {
    const q = (query || '').trim().toLowerCase();
    if (!q) {
        hideCustomerDropdown();
        return;
    }

    const matches = [];
    CUSTOMERS_LIST.forEach(c => {
        let score = 0;
        const name = (c.name || '').toLowerCase();
        const phone = (c.phone || '').toLowerCase();
        const city = (c.city || '').toLowerCase();

        if (phone === q) score += 100;
        else if (phone.startsWith(q)) score += 80;
        else if (phone.includes(q)) score += 60;
        else if (name === q) score += 90;
        else if (name.startsWith(q)) score += 70;
        else if (name.includes(q)) score += 50;
        else if (city.includes(q)) score += 30;

        if (score > 0) {
            matches.push({ customer: c, score });
        }
    });

    matches.sort((a, b) => b.score - a.score);
    const topMatches = matches.slice(0, 6).map(m => m.customer);

    if (q.length === 10 && matches.length > 0 && matches[0].customer.phone === q) {
        selectCustomer(matches[0].customer);
        return;
    }

    renderCustomerSuggestions(topMatches, q);

    fetch('<?= url("api/warehouse/customers/search") ?>?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            if (data.success && Array.isArray(data.customers)) {
                data.customers.forEach(dc => {
                    if (!CUSTOMERS_LIST.find(c => String(c.id) === String(dc.id))) {
                        CUSTOMERS_LIST.push(dc);
                    }
                });
                const curVal = (document.getElementById('custPhone')?.value || '').trim().toLowerCase();
                if (curVal === q) {
                    renderCustomerSuggestions(data.customers.slice(0, 6), q);
                }
            }
        })
        .catch(() => {});
}

function renderCustomerSuggestions(list, q) {
    const dropdown = document.getElementById('customerSuggestionsList');
    if (!dropdown) return;

    if (list && list.length > 0) {
        let html = '';
        list.forEach((c, idx) => {
            const loc = [c.city, c.state].filter(Boolean).join(', ');
            html += `
                <div class="search-result-item d-flex align-items-center justify-content-between p-2 border-bottom ${idx === 0 ? 'active-search-item' : ''}" 
                     data-index="${idx}" data-customer-id="${c.id}" onmousedown="selectCustomerById(${c.id})" style="cursor: pointer;">
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 0.85rem;">
                            <i class="bi bi-person-fill text-primary me-1"></i>${highlightMatch(c.name, q)}
                        </div>
                        <div class="d-flex align-items-center gap-1.5 mt-0.5 text-muted" style="font-size: 0.74rem;">
                            <span class="font-monospace text-secondary fw-semibold"><i class="bi bi-telephone me-0.5"></i>${highlightMatch(c.phone, q)}</span>
                            ${loc ? `<span class="badge bg-light text-dark border">📍 ${escapeHtml(loc)}</span>` : ''}
                        </div>
                    </div>
                    <div class="text-end">
                        ${c.gst_number ? `<span class="badge bg-primary-subtle text-primary font-monospace" style="font-size: 0.68rem;">GST: ${escapeHtml(c.gst_number)}</span>` : '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">✓ Saved</span>'}
                    </div>
                </div>
            `;
        });
        dropdown.innerHTML = html;
        showCustomerDropdown();
        activeCustomerIndex = 0;
    } else {
        dropdown.innerHTML = `
            <div class="p-2 text-center text-muted small">
                <i class="bi bi-person-plus text-primary me-1"></i>New Customer &mdash; will be automatically saved with this purchase.
            </div>
        `;
        showCustomerDropdown();
        activeCustomerIndex = -1;
    }
}

function onCustPhoneKeydown(e) {
    const dropdown = document.getElementById('customerSuggestionsList');
    const items = dropdown ? dropdown.querySelectorAll('.search-result-item') : [];
    const isVisible = dropdown && (dropdown.style.display === 'block' || dropdown.classList.contains('show'));

    if (e.key === 'ArrowDown') {
        if (isVisible && items.length > 0) {
            e.preventDefault();
            activeCustomerIndex = (activeCustomerIndex + 1) % items.length;
            highlightCustomerItem(items);
        }
    } else if (e.key === 'ArrowUp') {
        if (isVisible && items.length > 0) {
            e.preventDefault();
            activeCustomerIndex = (activeCustomerIndex - 1 + items.length) % items.length;
            highlightCustomerItem(items);
        }
    } else if (e.key === 'Enter') {
        if (isVisible && items.length > 0 && activeCustomerIndex >= 0 && items[activeCustomerIndex]) {
            e.preventDefault();
            const cId = items[activeCustomerIndex].getAttribute('data-customer-id');
            if (cId) {
                selectCustomerById(cId);
                return;
            }
        }
        hideCustomerDropdown();
    } else if (e.key === 'Escape') {
        hideCustomerDropdown();
    }
}

function selectCustomerById(customerId) {
    const matched = CUSTOMERS_LIST.find(c => String(c.id) === String(customerId));
    if (matched) {
        selectCustomer(matched);
    }
}

function selectCustomer(cust) {
    hideCustomerDropdown();
    if (!cust) return;

    const hiddenInput = document.getElementById('selectedCustomerId');
    const phoneInput = document.getElementById('custPhone');
    const nameInput = document.getElementById('custName');
    const addrInput = document.getElementById('custAddress');
    const cityInput = document.getElementById('custCity');
    const stateInput = document.getElementById('custState');
    const btnClear = document.getElementById('btnClearCustomer');
    const histSec = document.getElementById('custHistorySection');

    if (hiddenInput) hiddenInput.value = cust.id;
    if (phoneInput) {
        phoneInput.value = cust.phone || '';
        const counter = document.getElementById('phoneCounter');
        if (counter) {
            counter.textContent = cust.phone && cust.phone.length === 10 ? '✓ 10 Digits' : `${(cust.phone || '').length}/10 Digits`;
            counter.className = 'text-success fw-bold';
        }
    }
    if (nameInput) nameInput.value = cust.name || '';
    if (addrInput) addrInput.value = cust.address || '';
    if (cityInput && cust.city) {
        cityInput.value = cust.city;
        if (!cityInput.value && cust.city) {
            cityInput.value = 'Other';
        }
    }
    if (stateInput) stateInput.value = cust.state || 'Tamil Nadu';
    if (btnClear) btnClear.style.display = 'block';
    if (histSec) histSec.classList.remove('d-none');

    updateCustBadge('Saved Customer', 'success');
}

function clearCustomerInputs() {
    const hiddenInput = document.getElementById('selectedCustomerId');
    const phoneInput = document.getElementById('custPhone');
    const nameInput = document.getElementById('custName');
    const addrInput = document.getElementById('custAddress');
    const cityInput = document.getElementById('custCity');
    const stateInput = document.getElementById('custState');
    const btnClear = document.getElementById('btnClearCustomer');
    const histSec = document.getElementById('custHistorySection');
    const counter = document.getElementById('phoneCounter');

    if (hiddenInput) hiddenInput.value = '';
    if (phoneInput) {
        phoneInput.value = '';
        phoneInput.focus();
    }
    if (nameInput) nameInput.value = '';
    if (addrInput) addrInput.value = '';
    if (cityInput) cityInput.value = 'Tiruppur';
    if (stateInput) stateInput.value = 'Tamil Nadu';
    if (btnClear) btnClear.style.display = 'none';
    if (histSec) histSec.classList.add('d-none');
    if (counter) {
        counter.textContent = '10 Digits';
        counter.className = 'text-muted';
    }

    updateCustBadge('Walk-in Customer', 'secondary');
    hideCustomerDropdown();
}

function onWarehouseChange() {
    const whSel = document.getElementById('warehouseSelect');
    if (!whSel) return;
    const whId = whSel.value;
    
    fetch('<?= url("api/warehouse/warehouse-variant-stock") ?>?warehouse_id=' + encodeURIComponent(whId))
        .then(res => res.json())
        .then(data => {
            if (data.success && data.stock_map) {
                VARIANTS_LIST.forEach(v => {
                    if (data.stock_map[v.id] !== undefined) {
                        v.available_stock = data.stock_map[v.id];
                    } else {
                        v.available_stock = 0;
                    }
                });

                document.querySelectorAll('#itemsTableBody tr.item-row').forEach(row => {
                    const sel = row.querySelector('.variant-select');
                    if (sel && sel.selectedIndex >= 0) {
                        const opt = sel.options[sel.selectedIndex];
                        if (opt && opt.value) {
                            const newStock = data.stock_map[opt.value] !== undefined ? data.stock_map[opt.value] : 0;
                            opt.setAttribute('data-stock', newStock);
                            const stockBadge = row.querySelector('.stock-badge');
                            if (stockBadge) {
                                stockBadge.textContent = newStock + ' pcs';
                                stockBadge.className = newStock <= 0 
                                    ? 'badge bg-danger-subtle text-danger border border-danger-subtle stock-badge px-1.5 py-1' 
                                    : 'badge bg-success-subtle text-success border border-success-subtle stock-badge px-1.5 py-1';
                            }
                        }
                    }
                });
            }
        })
        .catch(() => {});
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderRowQrThumbnail(row, qrCodeText) {
    if (!row) return;
    const target = row.querySelector('.qr-render-target');
    if (!target) return;
    target.innerHTML = '';
    const text = (qrCodeText || '').trim();
    if (!text) {
        target.innerHTML = '<i class="bi bi-qr-code fs-4 text-secondary opacity-50 qr-fallback-icon"></i>';
        return;
    }
    try {
        if (typeof QRCode !== 'undefined') {
            new QRCode(target, {
                text: text,
                width: 36,
                height: 36,
                colorDark: '#0f172a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        } else {
            target.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=36x36&data=${encodeURIComponent(text)}" alt="QR" style="width:36px;height:36px;border-radius:3px;">`;
        }
    } catch (e) {
        target.innerHTML = '<i class="bi bi-qr-code fs-4 text-primary"></i>';
    }
}

function renderRowQrCell(row, customTags = null) {
    const sel = row.querySelector('.variant-select');
    const opt = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
    const qrPlaceholder = row.querySelector('.qr-placeholder');
    const qrActiveContent = row.querySelector('.qr-active-content');
    const tagsContainer = row.querySelector('.scanned-tags-container');
    const skuTextDisplay = row.querySelector('.sku-text-display');
    const hiddenQrs = row.querySelector('.scanned-qrs-input');

    if (!opt || !opt.value) {
        if (qrPlaceholder) qrPlaceholder.classList.remove('d-none');
        if (qrActiveContent) {
            qrActiveContent.classList.add('d-none');
            qrActiveContent.classList.remove('d-flex');
        }
        if (hiddenQrs) hiddenQrs.value = '';
        renderRowQrThumbnail(row, '');
        return;
    }

    const sku = opt.getAttribute('data-sku') || '';
    const defaultQr = opt.getAttribute('data-qr') || '';
    let tags = [];
    if (customTags !== null) {
        tags = Array.isArray(customTags) ? customTags : (customTags ? String(customTags).split(',').map(s => s.trim()).filter(Boolean) : []);
    } else if (hiddenQrs && hiddenQrs.value) {
        tags = hiddenQrs.value.split(',').map(s => s.trim()).filter(Boolean);
    }

    if (hiddenQrs) {
        hiddenQrs.value = tags.join(',');
    }

    if (qrPlaceholder) qrPlaceholder.classList.add('d-none');
    if (qrActiveContent) {
        qrActiveContent.classList.remove('d-none');
        qrActiveContent.classList.add('d-flex');
    }

    let tagsHtml = '';
    if (tags.length > 0) {
        tags.forEach((t, idx) => {
            tagsHtml += `<span class="badge bg-success font-monospace text-white px-2 py-1 shadow-xs d-inline-flex align-items-center gap-1" style="font-size:0.75rem;" title="Piece #${idx+1}"><i class="bi bi-qr-code-scan"></i>${escapeHtml(t)}</span>`;
        });
        renderRowQrThumbnail(row, tags[0]);
    } else {
        tagsHtml = `<span class="badge bg-light text-muted border font-monospace px-1.5 py-0.5 d-inline-flex align-items-center gap-1" style="font-size:0.7rem;"><i class="bi bi-upc-scan"></i>No Piece QR Scanned</span>`;
        renderRowQrThumbnail(row, defaultQr || sku);
    }
    if (tagsContainer) tagsContainer.innerHTML = tagsHtml;
    if (skuTextDisplay) {
        skuTextDisplay.innerHTML = sku ? `<span class="text-secondary fw-semibold">SKU:</span> <strong class="text-dark font-monospace">${escapeHtml(sku)}</strong>` : '';
    }
}

function toggleVariantEdit(btn) {
    const row = btn.closest('tr');
    const selectBox = row.querySelector('.variant-select-box');
    const infoDisplay = row.querySelector('.variant-info-display');
    if (selectBox && infoDisplay) {
        selectBox.classList.toggle('d-none');
        infoDisplay.classList.toggle('d-none');
    }
}

function onVariantChange(selectEl, customTags = null) {
    const row = selectEl.closest('tr');
    const opt = selectEl.options[selectEl.selectedIndex];
    const selectBox = row.querySelector('.variant-select-box');
    const infoDisplay = row.querySelector('.variant-info-display');
    const nameText = row.querySelector('.product-name-text');
    const skuBadge = row.querySelector('.sku-badge');
    const sizeBadge = row.querySelector('.size-badge');
    const catBadge = row.querySelector('.cat-badge');
    const mrpBadge = row.querySelector('.mrp-badge');
    const stockBadge = row.querySelector('.stock-badge');
    const scannedTagBox = row.querySelector('.scanned-tag-box');
    const tagValSpan = row.querySelector('.tag-val');
    const hiddenScanned = row.querySelector('.scanned-qrs-input');
    const priceInput = row.querySelector('.price-input');
    const mrpInput = row.querySelector('.mrp-input');
    const gstInput = row.querySelector('.gst-input');

    if (opt && opt.value) {
        const name = opt.getAttribute('data-name') || opt.text.split('|')[0].trim();
        const sku = opt.getAttribute('data-sku') || '';
        const isComp = opt.getAttribute('data-is-comp') === '1' || opt.getAttribute('data-cat-type') === 'COMPLEMENTARY';
        const isSeries = opt.getAttribute('data-is-series') === '1' || opt.getAttribute('data-cat-type') === 'SERIES' || opt.getAttribute('data-qr-type') === 'SERIES_BATCH';
        const price = isComp ? 0 : (parseFloat(opt.getAttribute('data-price')) || 0);
        const mrp = isComp ? 0 : (parseFloat(opt.getAttribute('data-mrp')) || price);
        const stock = parseInt(opt.getAttribute('data-stock')) || 0;
        const category = opt.getAttribute('data-category') || 'General';
        const size = opt.getAttribute('data-size') || 'Free';
        const gstRate = isComp ? 0 : (opt.hasAttribute('data-gst') ? parseFloat(opt.getAttribute('data-gst')) : 5.0);
        const defaultQr = opt.getAttribute('data-qr') || sku;

        if (mrpInput) mrpInput.value = mrp.toFixed(2);
        if (priceInput) {
            priceInput.value = price.toFixed(2);
            priceInput.readOnly = isComp;
            if (isComp) {
                priceInput.style.backgroundColor = '#f8fafc';
                priceInput.style.color = '#16a34a';
                priceInput.title = 'Complementary Item (Free - ₹0.00)';
            } else {
                priceInput.style.backgroundColor = '';
                priceInput.style.color = '';
                priceInput.title = 'Given Billing Price (Inclusive of GST)';
            }
        }
        if (gstInput) {
            gstInput.value = gstRate;
            gstInput.readOnly = isComp;
        }

        if (nameText) nameText.textContent = name;
        if (skuBadge) skuBadge.textContent = sku;
        if (sizeBadge) sizeBadge.textContent = size;
        if (catBadge) catBadge.textContent = category;
        if (mrpBadge) mrpBadge.textContent = isComp ? 'Complementary' : ('MRP: ₹' + mrp.toFixed(2));
        if (stockBadge) {
            stockBadge.textContent = 'Stock: ' + stock + ' pcs';
            stockBadge.className = stock <= 0 ? 'badge bg-danger-subtle text-danger stock-badge' : 'badge bg-success-subtle text-success stock-badge';
        }

        // Badges for complementary or series
        let badgeContainer = row.querySelector('.item-extra-badges');
        if (!badgeContainer) {
            badgeContainer = document.createElement('span');
            badgeContainer.className = 'item-extra-badges d-inline-flex gap-1 ms-1';
            const catBadgeEl = row.querySelector('.cat-badge');
            if (catBadgeEl && catBadgeEl.parentNode) catBadgeEl.parentNode.appendChild(badgeContainer);
        }
        if (badgeContainer) {
            if (isComp) {
                badgeContainer.innerHTML = '<span class="badge bg-warning-subtle text-dark border border-warning" style="font-size:10px;"><i class="bi bi-gift-fill text-warning me-1"></i>Complementary (₹0.00)</span>';
            } else if (isSeries) {
                badgeContainer.innerHTML = '<span class="badge bg-info-subtle text-info border border-info" style="font-size:10px;"><i class="bi bi-collection-fill me-1"></i>Series Batch</span>';
            } else {
                badgeContainer.innerHTML = '';
            }
        }

        const tag = (Array.isArray(customTags) && customTags.length > 0 && customTags[0]) ? customTags[0] : (hiddenScanned && hiddenScanned.value ? hiddenScanned.value : defaultQr);
        if (tag && tag.trim() !== '') {
            if (scannedTagBox) scannedTagBox.classList.remove('d-none');
            if (tagValSpan) tagValSpan.textContent = tag.trim();
            if (hiddenScanned) hiddenScanned.value = tag.trim();
            renderRowQrThumbnail(row, tag.trim());
        } else {
            if (scannedTagBox) scannedTagBox.classList.add('d-none');
            if (hiddenScanned) hiddenScanned.value = '';
            renderRowQrThumbnail(row, defaultQr || sku);
        }

        if (selectBox) selectBox.classList.add('d-none');
        if (infoDisplay) infoDisplay.classList.remove('d-none');

        // Stock alert
        let totalVariantQty = 0;
        document.querySelectorAll('#itemsTableBody tr.item-row').forEach(r => {
            const s = r.querySelector('.variant-select');
            const q = parseInt(r.querySelector('.qty-input')?.value || 1) || 1;
            if (s && String(s.value) === String(opt.value)) {
                totalVariantQty += q;
            }
        });
        if (totalVariantQty > stock && stock >= 0) {
            playBeep('error');
            const feedbackBox = document.getElementById('scanFeedbackMsg');
            if (feedbackBox) {
                feedbackBox.className = 'mt-2 small alert alert-warning py-1 px-2.5 mb-0 d-block';
                feedbackBox.innerHTML = `⚠️ <strong>Stock Alert:</strong> Total quantity for <strong>${escapeHtml(opt.text.trim())}</strong> (${totalVariantQty} pcs) exceeds warehouse available stock (${stock} pcs).`;
            }
        }
    } else {
        if (priceInput) {
            priceInput.value = '0.00';
            priceInput.readOnly = false;
        }
        if (mrpInput) mrpInput.value = '0.00';
        if (gstInput) {
            gstInput.value = '5';
            gstInput.readOnly = false;
        }
        if (selectBox) selectBox.classList.remove('d-none');
        if (infoDisplay) infoDisplay.classList.add('d-none');
        if (scannedTagBox) scannedTagBox.classList.add('d-none');
        if (hiddenScanned) hiddenScanned.value = '';
        renderRowQrThumbnail(row, '');
    }
    if (priceInput) onRowPriceChange(priceInput);
    else calculateTotals();
}

function onRowPriceChange(priceEl) {
    const row = priceEl.closest('tr');
    const sel = row.querySelector('.variant-select');
    const opt = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
    const isComp = opt && (opt.getAttribute('data-is-comp') === '1' || opt.getAttribute('data-cat-type') === 'COMPLEMENTARY');

    const mrpEl = row.querySelector('.mrp-input');
    const discBadge = row.querySelector('.disc-badge-display');
    const discPctDisp = row.querySelector('.disc-pct-display');
    const discPctHidden = row.querySelector('.disc-pct-hidden');
    const discAmtHidden = row.querySelector('.disc-amt-hidden');
    const baseDisp = row.querySelector('.base-price-display');
    const gstValDisp = row.querySelector('.gst-val-display');
    const gstInput = row.querySelector('.gst-input');
    const qtyInput = row.querySelector('.qty-input');
    
    let billed = parseFloat(priceEl.value) >= 0 ? parseFloat(priceEl.value) : 0;
    let mrp = parseFloat(mrpEl ? mrpEl.value : 0) || 0;
    
    if (isComp) {
        billed = 0.0;
        mrp = 0.0;
        if (priceEl) {
            priceEl.value = '0.00';
            priceEl.readOnly = true;
        }
        if (mrpEl) mrpEl.value = '0.00';
    } else if (mrp <= 0) {
        mrp = billed;
        if (mrpEl) mrpEl.value = mrp.toFixed(2);
    }
    
    const qty = parseFloat(qtyInput ? qtyInput.value : 1) || 1;
    const gstValue = parseFloat(gstInput ? gstInput.value : 5.0);
    const gstRate = isComp ? 0.0 : (Number.isFinite(gstValue) ? gstValue : 5.0);
    
    const lineBilledTotal = Math.max(0, billed * qty);
    const lineMrpGross = mrp * qty;
    
    const lineTaxable = isComp ? 0.0 : Math.round((lineBilledTotal / (1 + (gstRate / 100))) * 100) / 100;
    const lineGst = isComp ? 0.0 : Math.round((lineBilledTotal - lineTaxable) * 100) / 100;
    
    let lineDisc = 0;
    let discPct = 0;
    if (!isComp && lineMrpGross > lineBilledTotal) {
        lineDisc = Math.round((lineMrpGross - lineBilledTotal) * 100) / 100;
        discPct = lineMrpGross > 0 ? Math.round(((lineDisc / lineMrpGross) * 100) * 10) / 10 : 0;
    }
    
    if (baseDisp) baseDisp.textContent = '₹' + lineTaxable.toFixed(2);
    if (gstValDisp) gstValDisp.textContent = '+₹' + lineGst.toFixed(2);
    if (discBadge) {
        if (lineDisc > 0) {
            discBadge.className = 'badge bg-danger-subtle text-danger fw-bold font-monospace disc-badge-display';
            discBadge.textContent = '-₹' + lineDisc.toFixed(2);
            if (discPctDisp) {
                discPctDisp.textContent = `(${discPct}% off)`;
                discPctDisp.className = 'text-muted font-monospace mt-0.5 disc-pct-display';
            }
        } else {
            discBadge.className = 'badge bg-light text-muted font-monospace disc-badge-display';
            discBadge.textContent = '₹0.00';
            if (discPctDisp) {
                discPctDisp.className = 'text-muted font-monospace mt-0.5 disc-pct-display d-none';
            }
        }
    }
    
    if (discPctHidden) discPctHidden.value = discPct.toFixed(2);
    if (discAmtHidden) discAmtHidden.value = lineDisc.toFixed(2);
    
    calculateTotals();
}

function addItemRow(selectedVariantId = null, initialQr = null) {
    const tbody = document.getElementById('itemsTableBody');
    const row = document.createElement('tr');
    row.className = 'item-row align-middle';

    let optionsHtml = '<option value="">-- Select Product Variant --</option>';
    let matchedVariant = null;

    VARIANTS_LIST.forEach(v => {
        const isComp = (v.category_type === 'COMPLEMENTARY' || v.is_complementary == 1 || v.p_is_comp == 1);
        const isSeries = (v.category_type === 'SERIES' || v.qr_type === 'SERIES_BATCH' || v.is_series == 1 || parseInt(v.pack_quantity) > 1);
        const sellPrice = isComp ? 0 : (parseFloat(v.selling_price > 0 ? v.selling_price : v.mrp) || 0);
        const mrp = isComp ? 0 : (parseFloat(v.mrp > 0 ? v.mrp : sellPrice) || sellPrice);
        const stock = parseInt(v.available_stock) || 0;
        const isSelected = selectedVariantId && (String(v.id) === String(selectedVariantId));
        const qrTag = v.qr_code || v.sku;
        const catName = v.category_name || 'General';
        const catType = v.category_type || 'NORMAL';
        const qrType = v.qr_type || 'INDIVIDUAL';
        const sizeText = ((v.size_name || 'Free') + ' / ' + (v.color_name || 'Std')).trim();
        if (isSelected) matchedVariant = v;

        optionsHtml += `<option value="${v.id}" 
            data-name="${escapeHtml(v.product_name)}"
            data-sku="${escapeHtml(v.sku)}" 
            data-qr="${escapeHtml(qrTag)}" 
            data-category="${escapeHtml(catName)}" 
            data-cat-type="${escapeHtml(catType)}" 
            data-is-comp="${isComp ? 1 : 0}" 
            data-is-series="${isSeries ? 1 : 0}" 
            data-qr-type="${escapeHtml(qrType)}" 
            data-size="${escapeHtml(sizeText)}" 
            data-price="${sellPrice}" 
            data-mrp="${mrp}" 
            data-gst="${isComp ? 0 : (v.gst_rate ?? 5)}" 
            data-stock="${stock}" 
            ${isSelected ? 'selected' : ''}>
            ${escapeHtml(v.product_name)} | ${escapeHtml(v.sku)} (${escapeHtml(sizeText)})${isComp ? ' [Complementary - ₹0.00]' : (isSeries ? ' [Series]' : '')}
        </option>`;
    });

    const isMatchedComp = matchedVariant && (matchedVariant.category_type === 'COMPLEMENTARY' || matchedVariant.is_complementary == 1 || matchedVariant.p_is_comp == 1);
    const isMatchedSeries = matchedVariant && (matchedVariant.category_type === 'SERIES' || matchedVariant.qr_type === 'SERIES_BATCH' || matchedVariant.is_series == 1 || parseInt(matchedVariant.pack_quantity) > 1);

    const initPrice = isMatchedComp ? '0.00' : (matchedVariant ? parseFloat(matchedVariant.selling_price > 0 ? matchedVariant.selling_price : matchedVariant.mrp).toFixed(2) : '0.00');
    const initMrp = isMatchedComp ? '0.00' : (matchedVariant ? parseFloat(matchedVariant.mrp > 0 ? matchedVariant.mrp : initPrice).toFixed(2) : initPrice);
    const initStock = matchedVariant ? parseInt(matchedVariant.available_stock || 0) : 0;
    const initCat = matchedVariant ? (matchedVariant.category_name || 'General') : '-';
    const initSize = matchedVariant ? ((matchedVariant.size_name || 'Free') + ' / ' + (matchedVariant.color_name || 'Std')).trim() : '-';
    const initGst = isMatchedComp ? 0 : (matchedVariant ? (matchedVariant.gst_rate ?? 5) : 5);
    const stockClass = initStock <= 0 ? 'badge bg-danger-subtle text-danger stock-badge' : 'badge bg-success-subtle text-success stock-badge';
    const scannedVal = initialQr ? initialQr.trim() : (matchedVariant ? (matchedVariant.qr_code || matchedVariant.sku || '') : '');
    const initSku = matchedVariant ? matchedVariant.sku : '';
    const initName = matchedVariant ? matchedVariant.product_name : '-';
    const rowNum = tbody.querySelectorAll('.item-row').length + 1;

    // Calculate initial math for pre-selected variant
    const numPrice = parseFloat(initPrice) || 0;
    const numMrp = parseFloat(initMrp) || numPrice;
    const initTaxable = isMatchedComp ? 0 : Math.round((numPrice / (1 + (initGst / 100))) * 100) / 100;
    const initTax = isMatchedComp ? 0 : Math.round((numPrice - initTaxable) * 100) / 100;
    const initDisc = (!isMatchedComp && numMrp > numPrice) ? Math.round((numMrp - numPrice) * 100) / 100 : 0;
    const initDiscPct = numMrp > 0 && initDisc > 0 ? Math.round(((initDisc / numMrp) * 100) * 10) / 10 : 0;

    let extraBadgeHtml = '';
    if (isMatchedComp) {
        extraBadgeHtml = '<span class="badge bg-warning-subtle text-dark border border-warning" style="font-size:10px;"><i class="bi bi-gift-fill text-warning me-1"></i>Complementary (₹0.00)</span>';
    } else if (isMatchedSeries) {
        extraBadgeHtml = '<span class="badge bg-info-subtle text-info border border-info" style="font-size:10px;"><i class="bi bi-collection-fill me-1"></i>Series Batch</span>';
    }

    row.innerHTML = `
        <td class="ps-3 text-center row-num-cell" style="width: 38px;">
            <span class="badge bg-light text-secondary rounded-pill px-2 py-1 font-monospace" style="font-size: 11px;">${rowNum}</span>
        </td>
        <td>
            <div class="d-flex align-items-center item-details-container">
                <div class="qr-canvas-box me-2 flex-shrink-0 rounded-2 bg-white border" style="width: 42px; height: 42px; min-width: 42px; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 2px;" title="QR Code">
                    <div class="qr-render-target" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-qr-code fs-4 text-secondary opacity-50 qr-fallback-icon"></i>
                    </div>
                </div>
                <div class="overflow-hidden flex-grow-1">
                    <div class="variant-select-box ${matchedVariant ? 'd-none' : ''}">
                        <select name="variant_id[]" class="form-select form-select-sm variant-select" required onchange="onVariantChange(this)">
                            ${optionsHtml}
                        </select>
                    </div>
                    <div class="variant-info-display ${matchedVariant ? '' : 'd-none'}">
                        <div class="d-flex align-items-center">
                            <span class="fw-bold text-dark small text-truncate product-name-text" style="font-size: 13px;">${escapeHtml(initName)}</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-1 mt-0.5" style="font-size:11px;">
                            <span class="badge bg-light text-dark border font-monospace sku-badge" style="font-size:10px;">${escapeHtml(initSku)}</span>
                            <span class="badge bg-primary-subtle text-primary size-badge" style="font-size:10px;">${escapeHtml(initSize)}</span>
                            <span class="badge bg-secondary-subtle text-secondary cat-badge" style="font-size:10px;">${escapeHtml(initCat)}</span>
                            <span class="item-extra-badges d-inline-flex gap-1">${extraBadgeHtml}</span>
                            <span class="badge bg-light text-secondary border font-monospace mrp-badge" style="font-size:10px;">${isMatchedComp ? 'Complementary' : 'MRP: ₹' + initMrp}</span>
                            <span class="${stockClass}" style="font-size:10px;">Stock: ${initStock} pcs</span>
                        </div>
                        <div class="mt-1 scanned-tag-box ${scannedVal ? '' : 'd-none'}">
                            <span class="badge bg-dark font-monospace text-warning scanned-tag-badge border border-warning-subtle px-2 py-0.5" style="font-size:10px;">
                                <i class="bi bi-qr-code-scan me-1 text-warning"></i>QR Tag: <strong class="tag-val text-white">${escapeHtml(scannedVal)}</strong>
                            </span>
                        </div>
                    </div>
                    <input type="hidden" name="scanned_qr_codes[]" class="scanned-qrs-input" value="${escapeHtml(scannedVal)}">
                    <input type="hidden" name="mrp[]" class="mrp-input" value="${initMrp}">
                </div>
            </div>
        </td>
        <td class="text-end fw-semibold text-secondary font-monospace base-price-cell" style="font-size: 12.5px;">
            <span class="base-price-display">₹${initTaxable.toFixed(2)}</span>
        </td>
        <td class="text-end gst-cell">
            <div class="input-group input-group-sm gst-rate-control">
                <input type="number" name="gst_percentage[]" class="form-control form-control-sm text-end fw-bold font-monospace gst-input" value="${initGst}" min="0" max="100" step="0.01" required oninput="calculateTotals()" aria-label="GST percentage" title="Enter GST percentage (0 to 100)" ${isMatchedComp ? 'readonly' : ''}>
                <span class="input-group-text px-1">%</span>
            </div>
            <div class="text-muted font-monospace mt-0.5 gst-val-display" style="font-size: 10px; white-space: nowrap;">+₹${initTax.toFixed(2)}</div>
        </td>
        <td class="text-end discount-cell item-discount-cell">
            ${initDisc > 0 
                ? `<span class="badge bg-danger-subtle text-danger fw-bold font-monospace disc-badge-display" style="font-size: 11px;">-₹${initDisc.toFixed(2)}</span><div class="text-muted font-monospace mt-0.5 disc-pct-display" style="font-size: 9.5px;">(${initDiscPct}% off)</div>` 
                : `<span class="badge bg-light text-muted font-monospace disc-badge-display" style="font-size: 10.5px;">₹0.00</span><div class="text-muted font-monospace mt-0.5 disc-pct-display d-none" style="font-size: 9.5px;">(0% off)</div>`
            }
            <input type="hidden" name="discount_percentage[]" class="disc-pct-hidden" value="${initDiscPct.toFixed(2)}">
            <input type="hidden" name="discount_amount[]" class="disc-amt-hidden" value="${initDisc.toFixed(2)}">
        </td>
        <td class="text-end billed-price-cell" style="width: 130px;">
            <div class="input-group input-group-sm justify-content-end">
                <span class="input-group-text px-1.5 py-0.5 text-muted fw-bold" style="font-size: 11px;">₹</span>
                <input type="number" step="any" min="0" name="unit_price[]" class="form-control form-control-sm text-end fw-bold font-monospace price-input text-primary" value="${initPrice}" style="max-width: 85px; font-size: 13px; ${isMatchedComp ? 'background-color: #f8fafc; color: #16a34a !important;' : ''}" required oninput="onRowPriceChange(this)" title="${isMatchedComp ? 'Complementary Item (Free - ₹0.00)' : 'Given Billing Price (Inclusive of GST)'}" ${isMatchedComp ? 'readonly' : ''}>
                <input type="hidden" name="quantity[]" class="qty-input" value="1">
            </div>
        </td>
        <td class="text-center pe-3" style="width: 40px;">
            <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle p-0" onclick="removeItemRow(this)" title="Remove Item" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                <i class="bi bi-trash3-fill"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    if (scannedVal) {
        renderRowQrThumbnail(row, scannedVal);
    }
    updateItemCount();
    calculateTotals();
    return row;
}

function removeItemRow(btn) {
    const tbody = document.getElementById('itemsTableBody');
    if (tbody.querySelectorAll('.item-row').length > 1) {
        btn.closest('tr').remove();
        updateItemCount();
        calculateTotals();
    } else {
        alert('Purchase order must contain at least one product line item.');
    }
}

function updateItemCount() {
    const rows = document.querySelectorAll('#itemsTableBody tr.item-row');
    rows.forEach((r, idx) => {
        const numBadge = r.querySelector('.row-num-cell .badge');
        if (numBadge) numBadge.textContent = idx + 1;
    });
    const badge = document.getElementById('itemCountBadge');
    if (badge) badge.textContent = `${rows.length} item${rows.length > 1 ? 's' : ''}`;
    const cartBadge = document.getElementById('cartItemCountBadge');
    if (cartBadge) cartBadge.innerHTML = `<i class="bi bi-bag-check-fill"></i> ${rows.length} ${rows.length === 1 ? 'Item' : 'Items'} Scanned`;
}

// =========================================================================
// LIVE SEARCH & SUGGESTIONS LOGIC
// =========================================================================
function onLiveSearchInput(val) {
    clearTimeout(searchDebounceTimer);
    activeSearchIndex = -1;
    const dropdown = document.getElementById('liveSearchResults');
    if (!dropdown) return;

    const q = (val || '').trim().toLowerCase();
    if (!q || q.length < 1) {
        dropdown.innerHTML = '';
        dropdown.classList.add('d-none');
        return;
    }

    searchDebounceTimer = setTimeout(() => {
        doLiveSearch(q);
    }, 120);
}

function doLiveSearch(q) {
    const dropdown = document.getElementById('liveSearchResults');
    if (!dropdown) return;

    // Multi-strategy scoring match
    let matches = [];
    VARIANTS_LIST.forEach(v => {
        let score = 0;
        const sku = (v.sku || '').toLowerCase();
        const qr = (v.qr_code || '').toLowerCase();
        const code = (v.product_code || '').toLowerCase();
        const name = (v.product_name || '').toLowerCase();
        const size = (v.size_name || '').toLowerCase();
        const color = (v.color_name || '').toLowerCase();

        if (sku === q || qr === q || code === q) score += 100;
        else if (sku.startsWith(q) || code.startsWith(q)) score += 80;
        else if (sku.includes(q) || qr.includes(q) || code.includes(q)) score += 60;
        else if (name.includes(q)) score += 40;
        else if (size.includes(q) || color.includes(q)) score += 20;

        if (score > 0) {
            matches.push({ variant: v, score });
        }
    });

    matches.sort((a, b) => b.score - a.score);
    const topMatches = matches.slice(0, 8);

    if (topMatches.length > 0) {
        let html = '';
        topMatches.forEach((m, idx) => {
            const v = m.variant;
            const price = parseFloat(v.selling_price > 0 ? v.selling_price : v.mrp) || 0;
            const stock = parseInt(v.available_stock) || 0;
            const stockBadge = stock > 0 
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size:0.72rem;">${stock} in stock</span>`
                : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-1.5 py-0.5" style="font-size:0.72rem;">0 stock</span>`;

            html += `
                <div class="search-result-item d-flex align-items-center justify-content-between ${idx === 0 ? 'active-search-item' : ''}" 
                     data-index="${idx}" data-variant-id="${v.id}" onclick="selectSearchedVariant(${v.id})">
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 0.85rem;">${v.product_name}</div>
                        <div class="d-flex align-items-center gap-1.5 mt-0.5">
                            <span class="badge bg-dark font-monospace text-warning px-1.5 py-0.5" style="font-size: 0.72rem;">${v.sku}</span>
                            <span class="text-secondary small" style="font-size: 0.76rem;">${v.size_name || 'Free'} / ${v.color_name || 'Std'}</span>
                            ${v.qr_code ? `<span class="text-muted font-monospace small" style="font-size: 0.7rem;">[QR: ${v.qr_code}]</span>` : ''}
                        </div>
                    </div>
                    <div class="text-end ps-2">
                        <div class="fw-bold text-primary" style="font-size: 0.88rem;">₹${price.toFixed(2)}</div>
                        <div class="mt-0.5">${stockBadge}</div>
                    </div>
                </div>
            `;
        });
        dropdown.innerHTML = html;
        dropdown.classList.remove('d-none');
        activeSearchIndex = 0;
    } else {
        // Fallback AJAX lookup if query >= 3 characters
        if (q.length >= 3) {
            dropdown.innerHTML = `
                <div class="p-2 text-center text-muted small">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span> Searching database catalog...
                </div>`;
            dropdown.classList.remove('d-none');

            const whId = document.getElementById('warehouseSelect')?.value || 1;
            fetch('<?= url("api/warehouse/purchases/lookup-scan") ?>?code=' + encodeURIComponent(q) + '&warehouse_id=' + encodeURIComponent(whId))
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.variant) {
                        const v = data.variant;
                        const existingIdx = VARIANTS_LIST.findIndex(item => String(item.id) === String(v.id));
                        if (existingIdx >= 0) {
                            VARIANTS_LIST[existingIdx] = Object.assign({}, VARIANTS_LIST[existingIdx], v);
                        } else {
                            VARIANTS_LIST.push(v);
                        }
                        const isComp = (v.category_type === 'COMPLEMENTARY' || v.is_complementary == 1);
                        const price = isComp ? 0 : (parseFloat(v.selling_price > 0 ? v.selling_price : v.mrp) || 0);
                        const stock = parseInt(v.available_stock) || 0;
                        const stockBadge = stock > 0 
                            ? `<span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size:0.72rem;">${stock} in stock</span>`
                            : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-1.5 py-0.5" style="font-size:0.72rem;">0 stock</span>`;

                        dropdown.innerHTML = `
                            <div class="search-result-item d-flex align-items-center justify-content-between active-search-item" 
                                 data-index="0" data-variant-id="${v.id}" onclick="selectSearchedVariant(${v.id})">
                                <div>
                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">${escapeHtml(v.product_name)}</div>
                                    <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                        <span class="badge bg-dark font-monospace text-warning px-1.5 py-0.5" style="font-size: 0.72rem;">${escapeHtml(v.sku)}</span>
                                        <span class="text-secondary small" style="font-size: 0.76rem;">${escapeHtml(v.size_name || 'Free')} / ${escapeHtml(v.color_name || 'Std')}</span>
                                        ${isComp ? '<span class="badge bg-warning-subtle text-dark border border-warning" style="font-size:0.7rem;"><i class="bi bi-gift-fill text-warning me-1"></i>Complementary</span>' : ''}
                                    </div>
                                </div>
                                <div class="text-end ps-2">
                                    <div class="fw-bold text-primary" style="font-size: 0.88rem;">₹${price.toFixed(2)}</div>
                                    <div class="mt-0.5">${stockBadge}</div>
                                </div>
                            </div>`;
                        dropdown.classList.remove('d-none');
                        activeSearchIndex = 0;
                    } else {
                        dropdown.innerHTML = `<div class="p-2 text-center text-muted small">No matching product found for "<strong>${q}</strong>"</div>`;
                        dropdown.classList.remove('d-none');
                    }
                })
                .catch(() => {
                    dropdown.innerHTML = `<div class="p-2 text-center text-muted small">No results found.</div>`;
                });
        } else {
            dropdown.innerHTML = `<div class="p-2 text-center text-muted small">No matching product found.</div>`;
            dropdown.classList.remove('d-none');
        }
    }
}

function onScannerInputKeydown(e) {
    const dropdown = document.getElementById('liveSearchResults');
    const items = dropdown ? dropdown.querySelectorAll('.search-result-item') : [];

    if (e.key === 'ArrowDown') {
        if (!dropdown.classList.contains('d-none') && items.length > 0) {
            e.preventDefault();
            activeSearchIndex = (activeSearchIndex + 1) % items.length;
            highlightSearchItem(items);
        }
    } else if (e.key === 'ArrowUp') {
        if (!dropdown.classList.contains('d-none') && items.length > 0) {
            e.preventDefault();
            activeSearchIndex = (activeSearchIndex - 1 + items.length) % items.length;
            highlightSearchItem(items);
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        submitCurrentScan();
    } else if (e.key === 'Escape') {
        if (dropdown) dropdown.classList.add('d-none');
    }
}

function highlightSearchItem(items) {
    items.forEach((item, idx) => {
        if (idx === activeSearchIndex) {
            item.classList.add('active-search-item');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('active-search-item');
        }
    });
}

function selectSearchedVariant(variantId) {
    const dropdown = document.getElementById('liveSearchResults');
    if (dropdown) dropdown.classList.add('d-none');

    const matched = VARIANTS_LIST.find(v => String(v.id) === String(variantId));
    const scanInput = document.getElementById('purchaseScanInput');
    const query = scanInput ? scanInput.value.trim() : '';
    
    if (matched) {
        applyVariantToTable(matched, query || matched.qr_code || matched.sku || '');
    }

    if (scanInput) {
        scanInput.value = '';
        scanInput.focus();
    }
}

function submitCurrentScan() {
    const scanInput = document.getElementById('purchaseScanInput');
    if (!scanInput) return;
    const rawVal = scanInput.value.trim();
    if (!rawVal) return;
    handleScanInput(rawVal);
}

// =========================================================================
// SCANNER ENGINE (PIECE QR, BARCODE, SKU, PRODUCT CODE)
// =========================================================================
function handleScanInput(rawCode) {
    if (!rawCode) return;
    const clean = rawCode.trim();
    const feedbackBox = document.getElementById('scanFeedbackMsg');
    const dropdown = document.getElementById('liveSearchResults');
    if (dropdown) dropdown.classList.add('d-none');
    const whId = document.getElementById('warehouseSelect')?.value || 1;

    // Strict Backend Stock Verification & Piece Resolver with selected warehouse_id
    fetch('<?= url("api/warehouse/purchases/lookup-scan") ?>?code=' + encodeURIComponent(clean) + '&warehouse_id=' + encodeURIComponent(whId))
        .then(res => res.json())
        .then(data => {
            if (data.success && data.variant) {
                const existingIdx = VARIANTS_LIST.findIndex(v => String(v.id) === String(data.variant.id));
                if (existingIdx >= 0) {
                    VARIANTS_LIST[existingIdx] = Object.assign({}, VARIANTS_LIST[existingIdx], data.variant);
                } else {
                    VARIANTS_LIST.push(data.variant);
                }
                document.querySelectorAll('#itemsTableBody .variant-select').forEach(select => {
                    const option = Array.from(select.options).find(item => String(item.value) === String(data.variant.id));
                    if (!option) return;
                    option.setAttribute('data-price', data.variant.selling_price);
                    option.setAttribute('data-mrp', data.variant.mrp);
                    option.setAttribute('data-stock', data.variant.available_stock);
                });
                applyVariantToTable(data.variant, data.tag_code || clean);
            } else {
                playBeep('error');
                if (feedbackBox) {
                    feedbackBox.className = 'mt-2 small alert alert-danger py-1.5 px-2.5 mb-0 d-block shadow-sm';
                    feedbackBox.innerHTML = `${escapeHtml(data.message || 'Scanned QR code is not in active warehouse stock!')}`;
                }
                showScanAlert(data.message || 'Scanned QR code is not in active warehouse stock!');
            }
        })
        .catch(() => {
            playBeep('error');
            const message = `Error verifying scan code: ${clean}`;
            if (feedbackBox) {
                feedbackBox.className = 'mt-2 small alert alert-danger py-1 px-2.5 mb-0 d-block shadow-sm';
                feedbackBox.innerHTML = `⚠️ ${escapeHtml(message)}`;
            }
            showScanAlert(message);
        });

    const scanInput = document.getElementById('purchaseScanInput');
    if (scanInput) {
        scanInput.value = '';
        scanInput.focus();
    }
}

function showScanAlert(message) {
    const modalEl = document.getElementById('scanAlertModal');
    const titleEl = document.getElementById('scanAlertTitle');
    const messageEl = document.getElementById('scanAlertMessage');
    if (!modalEl || !titleEl || !messageEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        return;
    }

    const normalizedMessage = message.toLowerCase();
    titleEl.textContent = normalizedMessage.includes('already sold')
        ? 'This item is already sold'
        : normalizedMessage.includes('already been scanned')
            ? 'Duplicate scan'
            : normalizedMessage.includes('out of stock') || normalizedMessage.includes('no pieces available')
                ? 'Out of stock'
                : 'Unable to scan item';
    messageEl.textContent = message;
    modalEl.addEventListener('hidden.bs.modal', function focusScannerInput() {
        document.getElementById('purchaseScanInput')?.focus();
    }, { once: true });
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function applyVariantToTable(matched, tagLabel = '') {
    const feedbackBox = document.getElementById('scanFeedbackMsg');
    const rows = document.querySelectorAll('#itemsTableBody tr.item-row');
    const cleanTag = (tagLabel || '').trim() || (matched.qr_code || matched.sku || '');

    // 1. CROSS-CHECK IF THIS EXACT PIECE QR HAS ALREADY BEEN SCANNED IN ANY ROW (PREVENT DUPLICATE SCAN)
    const isSeries = (matched.category_type === 'SERIES' || matched.qr_type === 'SERIES_BATCH' || matched.is_series == 1 || parseInt(matched.pack_quantity) > 1);
    if (cleanTag && !isSeries) {
        let isDuplicateTag = false;
        let dupRow = null;
        rows.forEach(r => {
            const hiddenInput = r.querySelector('.scanned-qrs-input');
            const rowTag = (hiddenInput?.value || '').trim().toLowerCase();
            const tagValText = (r.querySelector('.tag-val')?.textContent || '').trim().toLowerCase();
            if ((rowTag && rowTag === cleanTag.toLowerCase()) || (tagValText && tagValText === cleanTag.toLowerCase())) {
                isDuplicateTag = true;
                dupRow = r;
            }
        });

        if (isDuplicateTag) {
            playBeep('error');
            if (feedbackBox) {
                feedbackBox.className = 'mt-2 small alert alert-danger py-1.5 px-2.5 mb-0 d-block shadow-sm';
                feedbackBox.innerHTML = `🚫 <strong>Duplicate Scan Blocked:</strong> Piece QR / Tag code <strong>${escapeHtml(cleanTag)}</strong> has ALREADY been scanned into this purchase order! Each individual piece QR tag can only be added once.`;
            }
            if (dupRow) {
                dupRow.classList.add('table-danger');
                dupRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => dupRow.classList.remove('table-danger'), 2500);
            }
            showScanAlert(`Piece QR / Tag "${cleanTag}" has already been scanned into this purchase order. Each individual piece QR tag can only be added once.`);
            return;
        }
    }

    // 2. CHECK TOTAL WAREHOUSE AVAILABLE STOCK ACROSS ALL ROWS FOR THIS VARIANT
    const maxAvailable = (matched.available_stock !== undefined && matched.available_stock !== null) ? parseInt(matched.available_stock) : 0;
    if (maxAvailable <= 0) {
        playBeep('error');
        if (feedbackBox) {
            feedbackBox.className = 'mt-2 small alert alert-danger py-1.5 px-2.5 mb-0 d-block shadow-sm';
            feedbackBox.innerHTML = `🚫 <strong>Out of Stock in Warehouse:</strong> <strong>${escapeHtml(matched.product_name)}</strong> (${escapeHtml(matched.sku)}) has 0 pcs available in this warehouse inventory.`;
        }
        showScanAlert(`"${matched.product_name}" (${matched.sku}) has no pieces available in the selected warehouse.`);
        return;
    }

    // Count how many pieces of this product variant are already in the table
    let currentVariantTotalQty = 0;
    rows.forEach(r => {
        const sel = r.querySelector('.variant-select');
        const q = parseInt(r.querySelector('.qty-input')?.value || 0) || 0;
        if (sel && String(sel.value) === String(matched.id)) {
            currentVariantTotalQty += q;
        }
    });

    if (currentVariantTotalQty + 1 > maxAvailable) {
        playBeep('error');
        if (feedbackBox) {
            feedbackBox.className = 'mt-2 small alert alert-danger py-1.5 px-2.5 mb-0 d-block shadow-sm';
            feedbackBox.innerHTML = `⚠️ <strong>Warehouse Stock Limit Reached:</strong> Cannot add more! Selected Warehouse has only <strong>${maxAvailable} pcs</strong> available for <strong>${escapeHtml(matched.product_name)}</strong> (${escapeHtml(matched.sku)}). You have already scanned <strong>${currentVariantTotalQty} pcs</strong> in this order.`;
        }
        showScanAlert(`Only ${maxAvailable} pcs are available for "${matched.product_name}" (${matched.sku}) in this warehouse. This order already contains ${currentVariantTotalQty} pcs.`);
        return;
    }

    // 3. ADD AS NEXT PRODUCT IN THE LIST (ONE ROW PER PIECE QR)
    const pieceTagValue = cleanTag;
    const pieceTagsList = [pieceTagValue];

    playBeep('success');

    // If table only has 1 initial row that is untouched/empty, populate it
    let targetRow = null;
    if (rows.length === 1) {
        const firstSel = rows[0].querySelector('.variant-select');
        const firstHidden = rows[0].querySelector('.scanned-qrs-input');
        if (firstSel && (!firstSel.value || firstSel.value === '') && (!firstHidden || !firstHidden.value)) {
            targetRow = rows[0];
        }
    }

    if (targetRow) {
        const sel = targetRow.querySelector('.variant-select');
        sel.value = matched.id;
        onVariantChange(sel, pieceTagsList);
        targetRow.classList.add('table-success');
        targetRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        setTimeout(() => targetRow.classList.remove('table-success'), 1200);
    } else {
        // Create next row in the product list for this scanned piece QR
        const newRow = addItemRow(matched.id, pieceTagValue);
        newRow.classList.add('table-success');
        newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        setTimeout(() => newRow.classList.remove('table-success'), 1200);
    }

    if (feedbackBox) {
        feedbackBox.className = 'mt-2 small alert alert-success py-1 px-2.5 mb-0 d-block shadow-sm';
        if (pieceTagValue) {
            feedbackBox.innerHTML = `✅ Scanned Piece QR <strong>${escapeHtml(pieceTagValue)}</strong> (${escapeHtml(matched.product_name)} - ${escapeHtml(matched.sku)}). [${currentVariantTotalQty + 1} of ${maxAvailable} pcs in warehouse]`;
        } else {
            feedbackBox.innerHTML = `✅ Added <strong>${escapeHtml(matched.product_name)} (${escapeHtml(matched.sku)})</strong>. [${currentVariantTotalQty + 1} of ${maxAvailable} pcs in warehouse]`;
        }
    }

    updateItemCount();
    calculateTotals();
}

function calculateTotals() {
    let grossTotal = 0;
    let totalDiscount = 0;
    let taxableSubtotal = 0;
    let totalTax = 0;
    let grandTotal = 0;

    const gstTypeEl = document.getElementById('gstTypeSelect');
    const isIGST = gstTypeEl ? gstTypeEl.value === 'IGST' : false;
    const rows = document.querySelectorAll('#itemsTableBody tr.item-row');

    rows.forEach(row => {
        const mrpEl = row.querySelector('.mrp-input');
        const priceEl = row.querySelector('.price-input');
        const qtyEl = row.querySelector('.qty-input');
        const gstEl = row.querySelector('.gst-input') || row.querySelector('.gst-select');
        const discPctHidden = row.querySelector('.disc-pct-hidden');
        const discAmtHidden = row.querySelector('.disc-amt-hidden');
        const baseDisp = row.querySelector('.base-price-display');
        const gstValDisp = row.querySelector('.gst-val-display');
        const discBadge = row.querySelector('.disc-badge-display');
        const discPctDisp = row.querySelector('.disc-pct-display');

        let mrp = parseFloat(mrpEl ? mrpEl.value : 0) || 0;
        const billed = parseFloat(priceEl ? priceEl.value : 0) || 0;
        if (mrp <= 0) {
            mrp = billed;
            if (mrpEl) mrpEl.value = mrp.toFixed(2);
        }
        const qty = parseFloat(qtyEl ? qtyEl.value : 1) || 1;
        const gstValue = parseFloat(gstEl ? gstEl.value : 5.0);
        const gstRate = Number.isFinite(gstValue) ? gstValue : 5.0;

        const lineMrpGross = mrp * qty;
        const lineBilledTotal = Math.max(0, billed * qty);
        const lineDisc = lineMrpGross > lineBilledTotal ? Math.round((lineMrpGross - lineBilledTotal) * 100) / 100 : 0;
        const discPct = lineMrpGross > 0 && lineDisc > 0 ? Math.round(((lineDisc / lineMrpGross) * 100) * 10) / 10 : 0;

        if (discPctHidden) discPctHidden.value = discPct.toFixed(2);
        if (discAmtHidden) discAmtHidden.value = lineDisc.toFixed(2);

        const lineTaxable = Math.round((lineBilledTotal / (1 + (gstRate / 100))) * 100) / 100;
        const lineTax = Math.round((lineBilledTotal - lineTaxable) * 100) / 100;

        grossTotal += lineMrpGross;
        totalDiscount += lineDisc;
        taxableSubtotal += lineTaxable;
        totalTax += lineTax;
        grandTotal += lineBilledTotal;

        if (baseDisp) baseDisp.textContent = '₹' + lineTaxable.toFixed(2);
        if (gstValDisp) gstValDisp.textContent = '+₹' + lineTax.toFixed(2);
        if (discBadge) {
            if (lineDisc > 0) {
                discBadge.className = 'badge bg-danger-subtle text-danger fw-bold font-monospace disc-badge-display';
                discBadge.textContent = '-₹' + lineDisc.toFixed(2);
                if (discPctDisp) {
                    discPctDisp.textContent = `(${discPct}% off)`;
                    discPctDisp.className = 'text-muted font-monospace mt-0.5 disc-pct-display';
                }
            } else {
                discBadge.className = 'badge bg-light text-muted font-monospace disc-badge-display';
                discBadge.textContent = '₹0.00';
                if (discPctDisp) {
                    discPctDisp.className = 'text-muted font-monospace mt-0.5 disc-pct-display d-none';
                }
            }
        }
    });

    const cgstTotal = Math.round((totalTax / 2) * 100) / 100;
    const sgstTotal = Math.round((totalTax - cgstTotal) * 100) / 100;

    // Right column Shop POS footer display elements:
    const displaySubtotal = document.getElementById('displaySubtotal');
    const displayTax = document.getElementById('displayTax');
    const displayCgst = document.getElementById('displayCgst');
    const displaySgst = document.getElementById('displaySgst');
    const displayDiscount = document.getElementById('displayDiscount');
    const displayDiscountDetail = document.getElementById('displayDiscountDetail');
    const displayTotal = document.getElementById('displayTotal');
    const cartItemCountBadge = document.getElementById('cartItemCountBadge');

    if (displaySubtotal) displaySubtotal.textContent = '₹' + taxableSubtotal.toFixed(2);
    if (displayTax) displayTax.textContent = 'Incl. ₹' + totalTax.toFixed(2);
    if (displayCgst) displayCgst.textContent = isIGST ? '₹0.00' : '₹' + cgstTotal.toFixed(2);
    if (displaySgst) displaySgst.textContent = isIGST ? ('IGST: ₹' + totalTax.toFixed(2)) : '₹' + sgstTotal.toFixed(2);
    if (displayDiscount) displayDiscount.textContent = '-₹' + totalDiscount.toFixed(2);
    if (displayDiscountDetail) displayDiscountDetail.textContent = '₹' + totalDiscount.toFixed(2);
    if (displayTotal) displayTotal.textContent = '₹' + grandTotal.toFixed(2);
    if (cartItemCountBadge) {
        cartItemCountBadge.innerHTML = `<i class="bi bi-bag-check-fill"></i> ${rows.length} ${rows.length === 1 ? 'Item' : 'Items'} Scanned`;
    }

    // Left column summary elements:
    const lblGross = document.getElementById('lblGross');
    const lblDiscount = document.getElementById('lblDiscount');
    const lblSubtotal = document.getElementById('lblSubtotal');
    const lblTax = document.getElementById('lblTax');
    const lblTotal = document.getElementById('lblTotal');

    if (lblGross) lblGross.textContent = '₹' + grossTotal.toFixed(2);
    if (lblDiscount) lblDiscount.textContent = '-₹' + totalDiscount.toFixed(2);
    if (lblSubtotal) lblSubtotal.textContent = '₹' + taxableSubtotal.toFixed(2);
    if (lblTax) lblTax.textContent = 'Incl. ₹' + totalTax.toFixed(2) + (isIGST ? ' (IGST)' : ' (CGST+SGST)');
    if (lblTotal) lblTotal.textContent = '₹' + grandTotal.toFixed(2);

    // If Cash + UPI is active, adjust split payment
    const paymentMethodSel = document.getElementById('paymentMethodSelect');
    if (paymentMethodSel && paymentMethodSel.value === 'CASH_UPI') {
        updateSplitSummary();
    }
}

function validateBeforeSubmit(e) {
    const phoneInput = document.getElementById('custPhone');
    const nameInput = document.getElementById('custName');
    
    const rawPhone = (phoneInput?.value || '').replace(/[^0-9]/g, '');
    const name = (nameInput?.value || '').trim();

    if (!rawPhone || rawPhone.length !== 10) {
        alert('Please enter a valid 10-digit customer mobile number.');
        if (phoneInput) phoneInput.focus();
        return false;
    }

    if (!name) {
        alert('Please enter the customer full name.');
        if (nameInput) nameInput.focus();
        return false;
    }

    // Duplicate tag check across line items
    const seenTags = new Set();
    const rows = document.querySelectorAll('#itemsTableBody tr.item-row');
    for (let r of rows) {
        const sel = r.querySelector('.variant-select');
        if (!sel || !sel.value) {
            alert('Please select a valid product variant for all rows.');
            if (sel) sel.focus();
            return false;
        }
        const opt = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
        const isSeries = opt && (opt.getAttribute('data-is-series') === '1' || opt.getAttribute('data-cat-type') === 'SERIES' || opt.getAttribute('data-qr-type') === 'SERIES_BATCH');
        const tag = (r.querySelector('.scanned-qrs-input')?.value || '').trim().toLowerCase();
        if (tag && !isSeries) {
            if (seenTags.has(tag)) {
                r.classList.add('table-danger');
                showScanAlert(`Piece QR tag "${tag}" is added multiple times. Each piece QR tag must be unique in this purchase order.`);
                return false;
            }
            seenTags.add(tag);
        }
    }

    const paymentMethodSel = document.getElementById('paymentMethodSelect');
    if (paymentMethodSel && paymentMethodSel.value === 'CASH_UPI') {
        const grandTotal = getGrandTotalValue();
        const cashVal = parseFloat(document.getElementById('splitCashInput').value) || 0;
        const upiVal = parseFloat(document.getElementById('splitUpiInput').value) || 0;
        const splitSum = cashVal + upiVal;
        
        if (Math.abs(grandTotal - splitSum) > 0.05 && grandTotal > 0) {
            const confirmProceed = confirm(`⚠️ Split Payment Alert:\nCash (₹${cashVal.toFixed(2)}) + UPI (₹${upiVal.toFixed(2)}) = ₹${splitSum.toFixed(2)}\ndoes not match the Grand Total ₹${grandTotal.toFixed(2)}.\n\nDo you want to auto-balance the remainder to UPI before saving?`);
            if (confirmProceed) {
                autoBalanceSplit();
            }
        }
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    calculateTotals();
    updateItemCount();

    // Render initial QR thumbnails if variants are pre-selected
    document.querySelectorAll('#itemsTableBody tr.item-row').forEach(row => {
        const sel = row.querySelector('.variant-select');
        const hiddenTag = row.querySelector('.scanned-qrs-input')?.value;
        const opt = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
        const qrCode = hiddenTag || (opt ? (opt.getAttribute('data-qr') || opt.getAttribute('data-sku')) : '');
        if (qrCode) {
            renderRowQrThumbnail(row, qrCode);
        }
    });

    // Keyboard shortcut Alt + S to focus scanner
    window.addEventListener('keydown', function(e) {
        if (e.altKey && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            const scanInput = document.getElementById('purchaseScanInput');
            if (scanInput) {
                scanInput.focus();
                scanInput.select();
            }
        }
    });

    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('liveSearchResults');
        const scanInput = document.getElementById('purchaseScanInput');
        if (dropdown && !dropdown.contains(e.target) && e.target !== scanInput) {
            dropdown.classList.add('d-none');
        }

        const custDropdown = document.getElementById('customerSuggestionsList');
        const custPhoneInput = document.getElementById('custPhone');
        if (custDropdown && !custDropdown.contains(e.target) && e.target !== custPhoneInput) {
            custDropdown.classList.add('d-none');
        }
    });

    // Global barcode gun listener
    let scanBuffer = '';
    let lastKeyTime = Date.now();
    window.addEventListener('keydown', function(e) {
        const active = document.activeElement;
        const isEditing = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT');
        const now = Date.now();
        
        if (now - lastKeyTime > 120) {
            scanBuffer = '';
        }
        lastKeyTime = now;

        if (e.key === 'Enter') {
            if (scanBuffer.length >= 3 && !isEditing) {
                e.preventDefault();
                const code = scanBuffer;
                scanBuffer = '';
                handleScanInput(code);
            }
        } else if (e.key.length === 1 && !isEditing) {
            scanBuffer += e.key;
        }
    }, true);

});

// Customer Profile & Purchase History Modal Handlers
let currentLoadedCustomer = null;
let currentLoadedProducts = [];
let currentLoadedOrders = [];

function openSelectedCustomerHistory() {
    const custId = document.getElementById('selectedCustomerId')?.value;
    if (!custId) {
        alert('Please select a saved customer first.');
        return;
    }
    const custName = document.getElementById('custName')?.value || 'Customer';
    openCustomerProfileModal(custId, custName);
}

function openCustomerProfileModal(customerId, customerName = '') {
    if (!customerId) return;

    const modalEl = document.getElementById('customerProfileModal');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();

    document.getElementById('modalCustLoading').classList.remove('d-none');
    document.getElementById('modalCustContent').classList.add('d-none');

    const productsTabBtn = document.getElementById('tab-products-btn');
    if (productsTabBtn) {
        const tab = bootstrap.Tab.getInstance(productsTabBtn) || new bootstrap.Tab(productsTabBtn);
        tab.show();
    }

    if (customerName) {
        document.getElementById('modalCustName').innerText = customerName;
        document.getElementById('modalCustAvatar').innerText = customerName.charAt(0).toUpperCase();
    }

    const apiUrl = '<?= url("api/warehouse/customers") ?>/' + customerId + '/purchase-history';

    fetch(apiUrl)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentLoadedCustomer = data.customer;
                currentLoadedProducts = data.products || [];
                currentLoadedOrders = data.orders || [];

                renderCustomerProfileData(data);

                document.getElementById('modalCustLoading').classList.add('d-none');
                document.getElementById('modalCustContent').classList.remove('d-none');
            } else {
                document.getElementById('modalCustLoading').innerHTML = `
                    <div class="text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                        <h6>${data.message || 'Unable to load customer purchase history.'}</h6>
                    </div>
                `;
            }
        })
        .catch(err => {
            console.error('Error fetching customer history:', err);
            document.getElementById('modalCustLoading').innerHTML = `
                <div class="text-danger py-4">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                    <h6>Failed to connect to server. Please try again.</h6>
                </div>
            `;
        });
}

function renderCustomerProfileData(data) {
    const cust = data.customer || {};
    const sum = data.summary || {};
    const products = data.products || [];
    const orders = data.orders || [];

    const cName = cust.name || 'Direct Customer';
    document.getElementById('modalCustName').innerText = cName;
    document.getElementById('modalCustAvatar').innerText = cName.charAt(0).toUpperCase();
    document.getElementById('modalCustPhone').innerText = cust.phone || 'No Mobile';
    
    let locationStr = cust.city || 'Local';
    if (cust.state) locationStr += ', ' + cust.state;
    document.getElementById('modalCustLocation').innerText = locationStr;

    if (cust.gst_number) {
        document.getElementById('modalCustGst').innerText = cust.gst_number;
        document.getElementById('modalCustGstSpan').classList.remove('d-none');
    } else {
        document.getElementById('modalCustGstSpan').classList.add('d-none');
    }

    const isRepeat = sum.is_repeat_customer || orders.length > 1;
    const repeatBadge = document.getElementById('modalCustRepeatBadge');
    if (isRepeat) {
        repeatBadge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small fw-semibold';
        repeatBadge.innerHTML = `<i class="bi bi-repeat me-1"></i>Repeat Buyer (${sum.total_orders} Orders)`;
    } else {
        repeatBadge.className = 'badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 py-1 small';
        repeatBadge.innerHTML = `<i class="bi bi-person-check me-1"></i>Direct Customer`;
    }

    document.getElementById('statCustTotalOrders').innerText = (sum.total_orders || 0) + ' Orders';
    document.getElementById('statCustOrdersSub').innerText = sum.last_order_date ? 'Latest: ' + formatDate(sum.last_order_date) : 'Across all warehouses';

    document.getElementById('statCustTotalUnits').innerText = (sum.total_pieces || 0) + ' pcs';
    document.getElementById('statCustProductsCount').innerText = (sum.distinct_products_count || products.length) + ' unique garment styles';

    document.getElementById('statCustTotalSpend').innerText = '₹' + Number(sum.total_spend || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('statCustAvgOrder').innerText = 'Avg ₹' + Number(sum.avg_order_value || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' / order';

    const paidAmt = Number(sum.paid_spend || 0).toLocaleString('en-IN', {minimumFractionDigits: 0});
    const pendAmt = Number(sum.pending_spend || 0).toLocaleString('en-IN', {minimumFractionDigits: 0});
    const elPayStat = document.getElementById('statCustPaymentStatus');
    if (elPayStat) {
        elPayStat.innerHTML = `<span class="text-success">₹${paidAmt}</span> <small class="text-white-50 fs-7">/ ₹${pendAmt} pend</small>`;
    }
    const elPayMeth = document.getElementById('statCustPayMethods');
    if (elPayMeth) {
        elPayMeth.innerText = (sum.payment_methods && sum.payment_methods.length > 0) ? sum.payment_methods.join(', ') : 'CASH';
    }

    document.getElementById('badgeTotalProductsCount').innerText = products.length;
    document.getElementById('badgeTotalOrdersCount').innerText = orders.length;

    renderPurchasedProductsTable(products);
    renderOrdersHistoryTable(orders);

    document.getElementById('infoCustName').innerText = cust.name || '--';
    document.getElementById('infoCustPhone').innerText = cust.phone || '--';
    document.getElementById('infoCustEmail').innerText = cust.email || 'None';
    document.getElementById('infoCustGst').innerText = cust.gst_number || 'Unregistered / Consumer';
    document.getElementById('infoCustAddress').innerText = cust.address || 'Direct counter billing';
    document.getElementById('infoCustCity').innerText = cust.city || 'Local';
    document.getElementById('infoCustState').innerText = cust.state || 'Tamil Nadu';
    document.getElementById('infoCustPrimaryWh').innerText = sum.primary_warehouse || 'Central Warehouse';
}

function renderPurchasedProductsTable(products) {
    const tbody = document.getElementById('tblModalPurchasedProductsBody');
    const emptyNotice = document.getElementById('emptyModalProductsNotice');
    tbody.innerHTML = '';

    if (!products || products.length === 0) {
        emptyNotice.classList.remove('d-none');
        return;
    }
    emptyNotice.classList.add('d-none');

    products.forEach((p) => {
        const tr = document.createElement('tr');
        
        // Combined Invoices & Scanned Piece QRs (Placed near each order)
        let ordersAndQrsHtml = '';
        if (p.orders && p.orders.length > 0) {
            ordersAndQrsHtml = p.orders.map(ord => {
                let cleanQr = (ord.qr_code || '').replace(/^QR:\s*/i, '').trim();
                let qrPills = '';
                if (cleanQr && cleanQr !== 'Standard SKU') {
                    let qrList = cleanQr.split(',').map(s => s.trim()).filter(Boolean);
                    qrPills = qrList.map(q => `
                        <span class="badge bg-dark-subtle text-dark border font-monospace" style="font-size:0.71rem;" title="Scanned Piece QR: ${escapeHtml(q)}">
                            <i class="bi bi-qr-code text-primary me-0.5"></i>${escapeHtml(q)}
                        </span>
                    `).join(' ');
                } else {
                    qrPills = '<span class="badge bg-light text-muted border font-monospace" style="font-size:0.7rem;">Std SKU</span>';
                }

                return `
                    <div class="d-flex align-items-center gap-1.5 flex-wrap py-0.5">
                        <a href="<?= url('warehouse/purchases/receive/') ?>${ord.po_id}" target="_blank" 
                           class="badge bg-light text-primary border text-decoration-none font-monospace py-1 px-1.5" 
                           title="View Separate Invoice for Order Date: ${formatDate(ord.order_date)}">
                            <i class="bi bi-file-earmark-text me-0.5"></i>${escapeHtml(ord.po_number)} <span class="text-secondary">(${ord.quantity} pcs)</span>
                        </a>
                        ${qrPills}
                    </div>
                `;
            }).join('');
        } else {
            ordersAndQrsHtml = '<span class="text-muted small">--</span>';
        }

        tr.innerHTML = `
            <td class="ps-3">
                <div class="fw-bold text-dark">${escapeHtml(p.product_name)}</div>
                <div class="d-flex align-items-center gap-1.5 mt-0.5">
                    <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.7rem;">${escapeHtml(p.category_name || 'Garments')}</span>
                    <small class="text-muted font-monospace">${escapeHtml(p.product_code || '')}</small>
                </div>
            </td>
            <td>
                <span class="font-monospace fw-semibold text-primary" style="font-size: 0.8rem;">${escapeHtml(p.sku)}</span>
            </td>
            <td>
                <div class="d-flex align-items-center gap-1.5">
                    <span class="badge bg-primary text-white font-monospace px-1.5 py-0.5" style="font-size: 0.75rem;">${escapeHtml(p.size_name || 'Std')}</span>
                    <span class="badge bg-light text-dark border px-1.5 py-0.5 d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                        <span class="d-inline-block rounded-circle border" style="width: 8px; height: 8px; background-color: ${escapeHtml(p.color_hex || '#ccc')};"></span>
                        ${escapeHtml(p.color_name || 'Std')}
                    </span>
                </div>
            </td>
            <td class="text-center">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-2.5 py-1 fw-bold">
                    ${p.total_quantity} pcs
                </span>
                <small class="text-muted d-block mt-0.5">${p.times_ordered} order(s)</small>
            </td>
            <td>
                <div class="fw-bold text-dark">₹${Number(p.unit_price).toFixed(2)}</div>
                ${p.mrp > p.unit_price ? `<small class="text-muted text-decoration-line-through">₹${Number(p.mrp).toFixed(2)}</small>` : ''}
            </td>
            <td>
                <span class="fw-bold text-success fs-6">₹${Number(p.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
            </td>
            <td class="pe-3">
                <div class="d-flex flex-column gap-1" style="min-width: 200px;">
                    ${ordersAndQrsHtml}
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function renderOrdersHistoryTable(orders) {
    const tbody = document.getElementById('tblModalOrdersHistoryBody');
    tbody.innerHTML = '';

    if (!orders || orders.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No purchase orders recorded for this customer.</td></tr>`;
        return;
    }

    orders.forEach((ord) => {
        const tr = document.createElement('tr');
        let payBadgeClass = ord.payment_status === 'PAID' ? 'bg-success' : (ord.payment_status === 'PARTIAL' ? 'bg-warning text-dark' : 'bg-danger');

        let itemsListHtml = '';
        if (ord.items && ord.items.length > 0) {
            itemsListHtml = ord.items.map(it => `
                <div class="small text-muted d-flex justify-content-between py-0.5 border-bottom border-light">
                    <span>• <strong>${escapeHtml(it.product_name)}</strong> (${escapeHtml(it.size_name || 'Std')} / ${escapeHtml(it.color_name || 'Std')})</span>
                    <span class="fw-bold text-dark font-monospace">${it.quantity} pcs @ ₹${Number(it.unit_price).toFixed(2)}</span>
                </div>
            `).join('');
        }

        tr.innerHTML = `
            <td class="ps-3"><span class="fw-bold font-monospace text-primary">${escapeHtml(ord.po_number)}</span></td>
            <td><small class="text-dark fw-semibold">${formatDate(ord.order_date)}</small></td>
            <td><span class="badge bg-secondary-subtle text-secondary border">${escapeHtml(ord.warehouse_name || 'Central WH')}</span></td>
            <td><div style="max-width: 260px;">${itemsListHtml}</div></td>
            <td><span class="badge bg-light text-dark border fw-bold">${ord.total_ordered_qty || 0} pcs</span></td>
            <td><span class="fw-bold text-success fs-6">₹${Number(ord.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></td>
            <td>
                <span class="badge ${payBadgeClass}">${escapeHtml(ord.payment_status || 'PAID')}</span>
                <small class="text-muted d-block font-monospace mt-0.5">${escapeHtml(ord.payment_method || 'CASH')}</small>
            </td>
            <td class="text-end pe-3">
                <a href="<?= url('warehouse/purchases/receive/') ?>${ord.id}" target="_blank" class="btn btn-xs btn-outline-primary" title="Print Tax Invoice">
                    <i class="bi bi-printer me-1"></i> Invoice
                </a>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function filterModalPurchasedProducts(term) {
    term = term.trim().toLowerCase();
    if (!currentLoadedProducts) return;

    if (term === '') {
        renderPurchasedProductsTable(currentLoadedProducts);
        return;
    }

    const filtered = currentLoadedProducts.filter(p => {
        const pName = (p.product_name || '').toLowerCase();
        const sku = (p.sku || '').toLowerCase();
        const pCode = (p.product_code || '').toLowerCase();
        const cat = (p.category_name || '').toLowerCase();
        const size = (p.size_name || '').toLowerCase();
        const color = (p.color_name || '').toLowerCase();
        const qrs = (p.qr_codes || []).join(' ').toLowerCase();

        return pName.includes(term) || sku.includes(term) || pCode.includes(term) ||
               cat.includes(term) || size.includes(term) || color.includes(term) || qrs.includes(term);
    });

    renderPurchasedProductsTable(filtered);
}

function formatDate(dateStr) {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${d.getDate().toString().padStart(2, '0')} ${months[d.getMonth()]} ${d.getFullYear()}`;
}
</script>

<!-- CUSTOMER PROFILE & PURCHASED PRODUCTS MODAL -->
<div class="modal fade" id="customerProfileModal" tabindex="-1" aria-labelledby="customerProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom-0 pb-0 text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="w-100 py-2">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div id="modalCustAvatar" class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center shadow" 
                                 style="width: 50px; height: 50px; font-size: 1.3rem; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: 2px solid rgba(255,255,255,0.2);">
                                C
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h4 class="mb-0 fw-bold text-white" id="modalCustName">Customer Name</h4>
                                    <span id="modalCustRepeatBadge" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small">
                                        <i class="bi bi-repeat me-1"></i>Repeat Buyer
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-3 text-white-50 small mt-1 flex-wrap" id="modalCustMetaChips">
                                    <span><i class="bi bi-telephone text-info me-1"></i><strong class="text-white" id="modalCustPhone">--</strong></span>
                                    <span><i class="bi bi-geo-alt text-warning me-1"></i><span id="modalCustLocation">--</span></span>
                                    <span id="modalCustGstSpan" class="d-none"><i class="bi bi-shield-check text-success me-1"></i>GSTIN: <span class="font-monospace text-white" id="modalCustGst">--</span></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="row g-2 pt-2 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
                        <div class="col-12 col-md-4">
                            <div class="p-2.5 rounded-3" style="background: rgba(255, 255, 255, 0.06); backdrop-filter: blur(4px);">
                                <div class="text-white-50 small" style="font-size: 0.75rem;"><i class="bi bi-receipt me-1 text-primary"></i>Total Direct Orders</div>
                                <div class="h5 fw-bold text-white mb-0 mt-0.5" id="statCustTotalOrders">0</div>
                                <div class="text-white-50" style="font-size: 0.7rem;" id="statCustOrdersSub">Across all warehouses</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-2.5 rounded-3" style="background: rgba(255, 255, 255, 0.06); backdrop-filter: blur(4px);">
                                <div class="text-white-50 small" style="font-size: 0.75rem;"><i class="bi bi-box-seam me-1 text-info"></i>Total Garments Bought</div>
                                <div class="h5 fw-bold text-info mb-0 mt-0.5" id="statCustTotalUnits">0 pcs</div>
                                <div class="text-white-50" style="font-size: 0.7rem;" id="statCustProductsCount">0 distinct styles</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-2.5 rounded-3" style="background: rgba(255, 255, 255, 0.06); backdrop-filter: blur(4px);">
                                <div class="text-white-50 small" style="font-size: 0.75rem;"><i class="bi bi-currency-rupee me-1 text-success"></i>Total Lifetime Spend</div>
                                <div class="h5 fw-bold text-success mb-0 mt-0.5" id="statCustTotalSpend">₹0.00</div>
                                <div class="text-white-50" style="font-size: 0.7rem;" id="statCustAvgOrder">Avg ₹0.00 / order</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-light px-4 pt-2 border-bottom">
                <ul class="nav nav-tabs border-0" id="customerProfileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold text-dark py-2.5 px-3 border-0 border-bottom border-primary border-3 bg-transparent" 
                                id="tab-products-btn" data-bs-toggle="tab" data-bs-target="#tab-products-pane" type="button" role="tab">
                            <i class="bi bi-bag-check me-1.5 text-primary"></i>Purchased Products Summary 
                            <span class="badge bg-primary-subtle text-primary rounded-pill ms-1" id="badgeTotalProductsCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-secondary py-2.5 px-3 border-0 bg-transparent" 
                                id="tab-orders-btn" data-bs-toggle="tab" data-bs-target="#tab-orders-pane" type="button" role="tab">
                            <i class="bi bi-receipt-cutoff me-1.5 text-success"></i>Purchase Invoices History 
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1" id="badgeTotalOrdersCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-secondary py-2.5 px-3 border-0 bg-transparent" 
                                id="tab-details-btn" data-bs-toggle="tab" data-bs-target="#tab-details-pane" type="button" role="tab">
                            <i class="bi bi-person-lines-fill me-1.5 text-info"></i>Customer Details
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4 bg-white" style="min-height: 380px;">
                <div id="modalCustLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <h6 class="text-muted fw-semibold">Loading Customer Purchase History &amp; Products...</h6>
                </div>

                <div id="modalCustContent" class="d-none">
                    <div class="tab-content" id="customerProfileTabContent">
                        <!-- TAB 1: PRODUCTS -->
                        <div class="tab-pane fade show active" id="tab-products-pane" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-stars text-primary me-1"></i>All Garment Products Purchased</h6>
                                    <small class="text-muted">Itemized breakdown of distinct styles, sizes, and colors previously bought by this customer.</small>
                                </div>
                                <div class="w-auto" style="min-width: 250px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                        <input type="text" id="filterModalProductsInput" class="form-control border-start-0" placeholder="Filter style, SKU, size, color..." oninput="filterModalPurchasedProducts(this.value)">
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive border rounded-3">
                                <table class="table table-hover align-middle mb-0" id="tblModalPurchasedProducts">
                                    <thead class="table-light" style="font-size: 0.82rem;">
                                        <tr>
                                            <th class="ps-3">Product Name &amp; Style</th>
                                            <th>SKU Code</th>
                                            <th>Size &amp; Color</th>
                                            <th class="text-center">Total Bought</th>
                                            <th>Unit Rate / MRP</th>
                                            <th>Total Spend</th>
                                            <th class="pe-3">Invoices &amp; Scanned Piece QRs</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tblModalPurchasedProductsBody" style="font-size: 0.86rem;"></tbody>
                                </table>
                            </div>
                            <div id="emptyModalProductsNotice" class="text-center py-4 d-none text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-1"></i> No matching products found for this customer.
                            </div>
                        </div>

                        <!-- TAB 2: ORDERS -->
                        <div class="tab-pane fade" id="tab-orders-pane" role="tabpanel">
                            <div class="table-responsive border rounded-3">
                                <table class="table table-hover align-middle mb-0" id="tblModalOrdersHistory">
                                    <thead class="table-light" style="font-size: 0.82rem;">
                                        <tr>
                                            <th class="ps-3">Invoice # / PO</th>
                                            <th>Order Date</th>
                                            <th>Warehouse</th>
                                            <th>Garment Items</th>
                                            <th>Total Units</th>
                                            <th>Grand Total</th>
                                            <th>Payment Method &amp; Status</th>
                                            <th class="text-end pe-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tblModalOrdersHistoryBody" style="font-size: 0.86rem;"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 3: DETAILS -->
                        <div class="tab-pane fade" id="tab-details-pane" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border rounded-3 shadow-none">
                                        <div class="card-header bg-light py-2 px-3 fw-bold small text-dark"><i class="bi bi-person-badge me-1.5 text-primary"></i>Customer Info</div>
                                        <div class="card-body p-3">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr><td class="text-muted" style="width:130px;">Name:</td><td class="fw-bold text-dark" id="infoCustName">--</td></tr>
                                                <tr><td class="text-muted">Phone:</td><td class="fw-semibold text-dark font-monospace" id="infoCustPhone">--</td></tr>
                                                <tr><td class="text-muted">Email:</td><td class="text-dark" id="infoCustEmail">--</td></tr>
                                                <tr><td class="text-muted">GSTIN:</td><td class="fw-bold font-monospace text-primary" id="infoCustGst">--</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border rounded-3 shadow-none">
                                        <div class="card-header bg-light py-2 px-3 fw-bold small text-dark"><i class="bi bi-geo-alt me-1.5 text-danger"></i>Address Details</div>
                                        <div class="card-body p-3">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr><td class="text-muted" style="width:130px;">Address:</td><td class="text-dark" id="infoCustAddress">--</td></tr>
                                                <tr><td class="text-muted">City / Town:</td><td class="fw-semibold text-dark" id="infoCustCity">--</td></tr>
                                                <tr><td class="text-muted">State:</td><td class="text-dark" id="infoCustState">--</td></tr>
                                                <tr><td class="text-muted">Primary WH:</td><td class="text-dark" id="infoCustPrimaryWh">Central Warehouse</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light py-2 px-4 border-top">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
