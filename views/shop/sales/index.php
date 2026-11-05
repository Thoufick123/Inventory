<?php
// views/shop/sales/index.php — Selled Products (Sales Ledger)
$cashRevenueTotal = array_sum(array_column($items, 'cash_revenue'));
$onlineRevenueTotal = array_sum(array_column($items, 'online_revenue'));
$cashRecordCount = count(array_filter($items, static fn($item) => (float)($item['cash_revenue'] ?? 0) > 0));
$onlineRecordCount = count(array_filter($items, static fn($item) => (float)($item['online_revenue'] ?? 0) > 0));
?>
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-1 rounded-pill">
                    <i class="bi bi-shop me-1"></i><?= e($shop['name'] ?? 'Shop Store') ?>
                </span>
                <span class="badge bg-success-subtle text-success fw-semibold px-3 py-1 rounded-pill">
                    <i class="bi bi-check2-circle me-1"></i>Live Sales Ledger
                </span>
            </div>
            <h2 class="fw-bold text-dark mb-0">Selled Products Register</h2>
            <p class="text-muted small mb-0">Complete itemized sales ledger, product-level transactions, and piece-level serial QR traceability.</p>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Units Sold -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Total Units Sold</span>
                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center text-primary" style="width: 44px; height: 44px;">
                            <i class="bi bi-bag-check-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format((int)($kpis['total_units_sold'] ?? 0)) ?> <span class="fs-6 text-muted fw-normal">pcs</span></div>
                    <div class="small text-muted mt-1"><i class="bi bi-receipt me-1 text-primary"></i> Across <?= number_format((int)($kpis['total_invoices'] ?? 0)) ?> invoices</div>
                </div>
            </div>
        </div>

        <!-- 2. Total Sales Revenue -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Gross Revenue</span>
                        <div class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center text-success" style="width: 44px; height: 44px;">
                            <i class="bi bi-currency-rupee fs-4"></i>
                        </div>
                    </div>
                    <div class="fs-3 fw-bold text-success">₹<?= number_format((float)($kpis['total_revenue'] ?? 0), 2) ?></div>
                    <div class="small text-muted mt-1"><i class="bi bi-graph-up-arrow me-1 text-success"></i> Net billed revenue</div>
                </div>
            </div>
        </div>

        <!-- 3. GST Tax Collected -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">GST Tax Collected</span>
                        <div class="rounded-circle bg-info-subtle d-flex align-items-center justify-content-center text-info-emphasis" style="width: 44px; height: 44px;">
                            <i class="bi bi-receipt-cutoff fs-5"></i>
                        </div>
                    </div>
                    <div class="fs-3 fw-bold text-info-emphasis">₹<?= number_format((float)($kpis['total_tax'] ?? 0), 2) ?></div>
                    <div class="small text-muted mt-1"><i class="bi bi-shield-check me-1 text-info"></i> CGST + SGST tax audit</div>
                </div>
            </div>
        </div>

        <!-- 4. Total Discounts -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Total Discounts</span>
                        <div class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center text-warning-emphasis" style="width: 44px; height: 44px;">
                            <i class="bi bi-tag-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="fs-3 fw-bold text-warning-emphasis">₹<?= number_format((float)($kpis['total_discount'] ?? 0), 2) ?></div>
                    <div class="small text-muted mt-1"><i class="bi bi-percent me-1 text-warning"></i> Store promotional savings</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Type Tabs -->
    <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white">
        <div class="card-body p-2">
            <div class="nav nav-pills gap-2" role="tablist" aria-label="Filter sold products by payment type">
                <button type="button" class="nav-link active rounded-3 d-flex align-items-center gap-2"
                        id="cashPaymentTab" data-payment-tab="cash" role="tab" aria-selected="true">
                    <i class="bi bi-cash-stack"></i>
                    <span>Cash</span>
                    <span class="badge bg-white text-success" id="cashPaymentTabTotal">₹<?= number_format($cashRevenueTotal, 2) ?></span>
                    <span class="small opacity-75" id="cashPaymentTabCount"><?= number_format($cashRecordCount) ?> records</span>
                </button>
                <button type="button" class="nav-link rounded-3 d-flex align-items-center gap-2 text-dark"
                        id="onlinePaymentTab" data-payment-tab="online" role="tab" aria-selected="false">
                    <i class="bi bi-globe2"></i>
                    <span>Online</span>
                    <span class="badge bg-light text-primary" id="onlinePaymentTabTotal">₹<?= number_format($onlineRevenueTotal, 2) ?></span>
                    <span class="small opacity-75" id="onlinePaymentTabCount"><?= number_format($onlineRecordCount) ?> records</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('shop/sales') ?>" class="row g-2 align-items-center" id="salesFilterForm" onsubmit="return true;">
                <!-- Search Input -->
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="salesSearchInput" class="form-control bg-light border-start-0" 
                               placeholder="Search product, SKU, invoice, tag, customer..." 
                               value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
                        <button type="button" id="btnClearSalesSearch" class="btn btn-outline-secondary <?= empty($filters['search']) ? 'd-none' : '' ?>" title="Clear Search" onclick="clearSalesSearch()"><i class="bi bi-x"></i></button>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="col-md-2">
                    <input type="date" name="start_date" id="salesStartDate" class="form-control bg-light" 
                           placeholder="Start Date" value="<?= e($filters['start_date'] ?? '') ?>" title="Start Date">
                </div>
                <div class="col-md-2">
                    <input type="date" name="end_date" id="salesEndDate" class="form-control bg-light" 
                           placeholder="End Date" value="<?= e($filters['end_date'] ?? '') ?>" title="End Date">
                </div>

                <!-- Category -->
                <div class="col-md-3">
                    <select name="category_id" id="salesCatSelect" class="form-select bg-light">
                        <option value="ALL">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary rounded-3 w-100" title="Apply Filter">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    <?php if (!empty($filters['search']) || !empty($filters['start_date']) || !empty($filters['end_date']) || ($filters['category_id'] ?? 'ALL') !== 'ALL'): ?>
                        <a href="<?= url('shop/sales') ?>" class="btn btn-light border rounded-3" title="Clear Filters">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Sold Products Itemized Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-cart-check-fill me-2 text-primary"></i>Itemized Sold Products</h5>
            <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill" id="salesCountBadge">
                Showing <?= count($items) ?> records
            </span>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0" id="salesTable">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th class="ps-4" style="width: 4%;">#</th>
                        <th style="width: 20%;">Product Details</th>
                        <th style="width: 12%;">Piece QR Serial Tag</th>
                        <th style="width: 12%;">Invoice #</th>
                        <th style="width: 12%;">Customer</th>
                        <th class="text-center" style="width: 5%;">Qty</th>
                        <th class="text-end" style="width: 12%;">Rate &amp; Discount</th>
                        <th class="text-end" style="width: 9%;">Net Total</th>
                        <th class="text-center" style="width: 6%;">Mode</th>
                        <th class="text-center pe-3" style="width: 9%;">Action</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody">
                    <?php if (empty($items)): ?>
                        <tr id="emptySalesRow">
                            <td colspan="10" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="bi bi-inbox fs-1 text-secondary d-block mb-2"></i>
                                    <h6>No sold products found matching your search criteria.</h6>
                                    <p class="small text-muted mb-0">Complete a sale in POS Billing to view records here.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $idx => $it): 
                            $searchHaystack = strtolower(($it['product_name'] ?? '') . ' ' . ($it['sku'] ?? '') . ' ' . ($it['scanned_qr_codes'] ?? '') . ' ' . ($it['invoice_number'] ?? '') . ' ' . ($it['customer_name'] ?? '') . ' ' . ($it['customer_phone'] ?? '') . ' ' . ($it['size_name'] ?? '') . ' ' . ($it['color_name'] ?? ''));
                            $saleDateFormatted = !empty($it['sale_date']) ? formatUtcDateTime($it['sale_date'], 'Y-m-d') : '';
                        ?>
                            <tr class="sales-row" 
                                data-search="<?= e($searchHaystack) ?>"
                                data-cat="<?= (int)($it['category_id'] ?? 0) ?>"
                                data-date="<?= e($saleDateFormatted) ?>"
                                data-cash-revenue="<?= number_format((float)($it['cash_revenue'] ?? 0), 2, '.', '') ?>"
                                data-online-revenue="<?= number_format((float)($it['online_revenue'] ?? 0), 2, '.', '') ?>">
                                <td class="ps-4 text-muted small fw-bold sales-idx"><?= $idx + 1 ?></td>
                                <td>
                                    <div>
                                        <div class="fw-bold text-dark small text-truncate" style="max-width: 220px;" title="<?= e($it['product_name']) ?>">
                                            <?= e($it['product_name']) ?>
                                        </div>
                                        <div class="text-muted font-monospace" style="font-size: 11px;">
                                            <span class="fw-semibold text-primary"><?= e($it['sku']) ?></span>
                                            <?= !empty($it['size_name']) ? ' • ' . e($it['size_name']) : '' ?>
                                            <?= !empty($it['color_name']) ? ' • ' . e($it['color_name']) : '' ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($it['scanned_qr_codes'])): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach (explode(',', $it['scanned_qr_codes']) as $tag): ?>
                                                <span class="badge bg-dark-subtle text-dark border font-monospace" style="font-size: 10px;">
                                                    <i class="bi bi-qr-code text-primary me-1"></i><?= e(trim($tag)) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border font-monospace" style="font-size: 10px;">
                                            <i class="bi bi-qr-code text-primary me-1"></i>QR: <?= e($it['sku'] ?? 'QR-CODE') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space: nowrap;">
                                    <a href="javascript:void(0)" onclick="openSoldProductModal(<?= $it['id'] ?>, 'thermal')" class="fw-bold font-monospace text-primary text-decoration-none small d-inline-flex align-items-center gap-1" title="Click to view exact POS bill & receipt">
                                        <i class="bi bi-receipt"></i> <?= e($it['invoice_number']) ?>
                                    </a>
                                    <div class="text-muted" style="font-size: 10px; white-space: nowrap;">
                                        <?= e($it['sale_date_display'] ?? formatUtcDateTime($it['sale_date'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small text-truncate" style="max-width: 140px;">
                                        <?= e($it['customer_name'] ?? 'Walk-in Customer') ?>
                                    </div>
                                    <?php if (!empty($it['customer_phone'])): ?>
                                        <small class="text-muted font-monospace"><i class="bi bi-telephone me-1"></i><?= e($it['customer_phone']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 fw-bold"><?= $it['quantity'] ?></span>
                                </td>
                                <td class="text-end">
                                    <?php if (!empty($it['has_discount'])): ?>
                                        <div class="fw-bold text-primary small">
                                            ₹<?= number_format((float)$it['billed_unit_price'], 2) ?> <span class="text-muted fw-normal" style="font-size: 10px;">/ pc</span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-end gap-1 mt-1">
                                            <del class="text-muted" style="font-size: 10.5px;">₹<?= number_format((float)$it['original_unit_price'], 2) ?></del>
                                            <span class="badge bg-danger-subtle text-danger" style="font-size: 9.5px; padding: 1px 4px;">
                                                -₹<?= number_format((float)$it['unit_discount'], 2) ?> (<?= round($it['discount_percent']) ?>%)
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="fw-bold text-dark small">
                                            ₹<?= number_format((float)$it['original_unit_price'], 2) ?> <span class="text-muted fw-normal" style="font-size: 10px;">/ pc</span>
                                        </div>
                                        <small class="text-muted" style="font-size: 10px;">Regular Rate</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-success">
                                    <div>₹<?= number_format((float)$it['total_amount'], 2) ?></div>
                                    <?php if ($it['tax_total'] > 0): ?>
                                        <div class="text-muted" style="font-size: 9.5px; font-weight: normal;">+GST: ₹<?= number_format($it['tax_total'], 2) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($it['discount_amount']) && $it['discount_amount'] > 0): ?>
                                        <div class="text-danger" style="font-size: 9.5px; font-weight: normal;">Saved: ₹<?= number_format($it['discount_amount'], 2) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                        $modeBadge = match($it['payment_mode']) {
                                            'CASH'     => 'bg-secondary',
                                            'UPI'      => 'bg-success',
                                            'CARD'     => 'bg-info text-dark',
                                            'CASH_UPI' => 'bg-primary',
                                            'MIXED'    => 'bg-warning text-dark',
                                            default    => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $modeBadge ?> rounded-pill px-2" style="font-size: 10px;"><?= $it['payment_mode'] === 'CASH_UPI' ? 'Cash+UPI' : e($it['payment_mode']) ?></span>
                                </td>
                                <td class="text-center pe-3" style="white-space: nowrap;">
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2"
                                                onclick="openSoldProductModal(<?= (int)$it['id'] ?>, 'specs')"
                                                aria-label="View sold product and invoice <?= e($it['invoice_number']) ?>"
                                                title="View product and invoice details">
                                            <i class="bi bi-eye me-1"></i>View
                                        </button>
                                        <a href="<?= url('shop/pos/invoice/' . $it['sale_id']) ?>" target="_blank" class="btn btn-sm btn-light border rounded-circle" title="Print POS Invoice">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: SOLD PRODUCT DETAILS & EXACT POS BILLING -->
<div class="modal fade" id="soldProductModal" tabindex="-1" aria-labelledby="soldProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <!-- Modal Header with View Switcher -->
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2 flex-wrap flex-grow-1">
                    <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-receipt-cutoff fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2 flex-wrap" id="soldProductModalLabel">
                            Sold Product Details &amp; POS Invoice:
                            <span id="modalInvNum" class="font-monospace text-primary fw-bold">INV-XXXX</span>
                        </h5>
                        <small class="text-muted" id="modalSubMeta">Sale Transaction &amp; Item Specification</small>
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
                        <div class="btn-group btn-group-sm p-1 bg-light rounded-pill border" role="group">
                            <button type="button" class="btn btn-sm rounded-pill fw-bold px-3 active" id="btnSoldModeSpecs" onclick="switchSoldModalView('specs')">
                                <i class="bi bi-tag-fill me-1"></i> Garment Specs
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill fw-bold px-3 text-muted" id="btnSoldModeThermal" onclick="switchSoldModalView('thermal')">
                                <i class="bi bi-receipt me-1"></i> Thermal POS Bill
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill fw-bold px-3 text-muted" id="btnSoldModeInvoice" onclick="switchSoldModalView('invoice')">
                                <i class="bi bi-grid-3x3-gap me-1"></i> Full Invoice
                            </button>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-0 bg-light">
                <!-- Loading Spinner -->
                <div id="modalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <p class="text-muted fw-semibold small mb-0">Loading complete sale and invoice details...</p>
                </div>

                <!-- Main Modal Content Container -->
                <div id="modalBodyContent" class="p-4" style="display: none;">
                    
                    <!-- VIEW 1: GARMENT SPECS & INDIVIDUAL ITEM AUDIT -->
                    <div id="soldSpecsView">
                        <div class="row g-4">
                            <!-- Product Visual & Piece Tags -->
                            <div class="col-md-5">
                                <div class="p-3 bg-white rounded-4 border mb-3 text-center shadow-xs">
                                    <img id="modalProdImg" src="" alt="" class="img-fluid rounded-3" style="max-height: 190px; object-fit: contain;">
                                </div>
                                <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <small class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                            <i class="bi bi-qr-code-scan text-primary me-1"></i>Piece Serial QR Codes
                                        </small>
                                        <span class="badge bg-primary-subtle text-primary rounded-pill font-monospace" id="modalPieceTagCount" style="font-size: 10px;">1 tag</span>
                                    </div>
                                    <div id="modalPieceTags" class="d-flex flex-wrap gap-1"></div>
                                </div>
                            </div>

                            <!-- Specification Details & Billing Breakdown -->
                            <div class="col-md-7">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1" id="modalProdName">Product Title</h5>
                                        <div class="text-muted font-monospace small">
                                            <span class="fw-semibold text-primary" id="modalProdSku">SKU</span> • 
                                            <span id="modalProdSize">Size</span> • 
                                            <span id="modalProdColor">Color</span>
                                            <span id="modalProdCategory" class="badge bg-secondary-subtle text-secondary ms-1">Category</span>
                                        </div>
                                    </div>
                                    <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill fw-bold" id="modalDiscountBadge" style="display: none;">
                                        <i class="bi bi-tag-fill me-1"></i><span id="modalDiscountPercentText">0% OFF</span>
                                    </span>
                                </div>

                                <!-- BILLING & PRICING AUDIT CARDS -->
                                <div class="card border-0 rounded-4 shadow-sm mb-3 overflow-hidden bg-white">
                                    <div class="card-header bg-primary-subtle border-0 py-2 px-3 d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-primary small text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                                            <i class="bi bi-receipt-cutoff me-1"></i>Pricing &amp; Rate Audit
                                        </span>
                                        <span class="badge bg-white text-primary border shadow-xs" id="modalPaymentModeBadge">CASH</span>
                                    </div>
                                    <div class="card-body p-3 bg-white">
                                        <!-- 4 Pricing Metric Cards Grid -->
                                        <div class="row g-2 mb-3">
                                            <!-- 1. Old / Regular Rate -->
                                            <div class="col-6 col-sm-3">
                                                <div class="p-2 bg-light rounded-3 border text-center h-100">
                                                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 9.5px;">Old / Regular Rate</small>
                                                    <div class="fs-6 fw-bold text-secondary text-decoration-line-through mt-1" id="modalOldPrice">₹0.00</div>
                                                    <span class="badge bg-light text-muted border mt-1" style="font-size: 9px;" id="modalMrpTag">Regular Rate</span>
                                                </div>
                                            </div>

                                            <!-- 2. Discount Price -->
                                            <div class="col-6 col-sm-3">
                                                <div class="p-2 bg-danger-subtle rounded-3 border border-danger-subtle text-center h-100" id="modalDiscountCard">
                                                    <small class="text-danger d-block text-uppercase fw-semibold" style="font-size: 9.5px;">Discount Price</small>
                                                    <div class="fs-6 fw-bold text-danger mt-1" id="modalDiscountAmount">-₹0.00</div>
                                                    <span class="badge bg-danger text-white mt-1" style="font-size: 9px;" id="modalDiscountRateTag">0% OFF</span>
                                                </div>
                                            </div>

                                            <!-- 3. New Billed Rate -->
                                            <div class="col-6 col-sm-3">
                                                <div class="p-2 bg-primary-subtle rounded-3 border border-primary-subtle text-center h-100">
                                                    <small class="text-primary d-block text-uppercase fw-semibold" style="font-size: 9.5px;">New Billed Rate</small>
                                                    <div class="fs-6 fw-bold text-primary mt-1" id="modalBilledUnitPrice">₹0.00</div>
                                                    <span class="badge bg-primary text-white mt-1" style="font-size: 9px;">Final / pc</span>
                                                </div>
                                            </div>

                                            <!-- 4. Sold Quantity -->
                                            <div class="col-6 col-sm-3">
                                                <div class="p-2 bg-light rounded-3 border text-center h-100">
                                                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 9.5px;">Sold Quantity</small>
                                                    <div class="fs-6 fw-bold text-dark mt-1" id="modalQty">1 pc(s)</div>
                                                    <span class="badge bg-secondary-subtle text-secondary mt-1" style="font-size: 9px;" id="modalGrossTotal">Gross: ₹0.00</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Summary Row: Taxable Base, GST Tax Breakdown, Net Line Total Paid -->
                                        <div class="p-3 bg-light rounded-3 border">
                                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                                <span class="text-muted">Taxable Base Amount:</span>
                                                <span class="fw-semibold text-dark" id="modalTaxableBase">₹0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                                <span class="text-muted">Tax (GST Total <span id="modalGstPercent">5%</span>):</span>
                                                <span class="fw-semibold text-info-emphasis" id="modalTax">₹0.00</span>
                                            </div>
                                            <div class="border-top pt-2 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="fw-bold text-dark fs-6">Net Line Total Paid</span>
                                                    <div class="text-success small fw-semibold" id="modalSavingsHighlight" style="font-size: 11px;">
                                                        <i class="bi bi-piggy-bank-fill me-1"></i>Customer saved ₹0.00
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <strong class="text-success fs-4 fw-bold" id="modalLineTotal">₹0.00</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer & Billed Audit Info -->
                                <div class="card border-0 bg-white rounded-4 p-3 shadow-xs">
                                    <h6 class="fw-bold text-muted text-uppercase mb-2" style="font-size: 10px; letter-spacing: 0.5px;">
                                        <i class="bi bi-person-badge-fill me-1 text-primary"></i>Customer &amp; Billed Audit
                                    </h6>
                                    <div class="row g-2 small">
                                        <div class="col-sm-6">
                                            <span class="text-muted">Customer:</span> <strong class="text-dark" id="modalCustName">Walk-in Customer</strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted">Phone:</span> <span class="font-monospace text-dark" id="modalCustPhone">--</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted">Billed By:</span> <span class="text-dark fw-semibold" id="modalCashierName">Admin</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted">Sale Timestamp:</span> <span class="text-dark" id="modalSaleDate">--</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- VIEW 2: EXACT THERMAL POS RECEIPT SLIP -->
                    <div id="soldThermalView" style="display: none;" class="justify-content-center">
                        <div class="thermal-receipt-card shadow-sm mx-auto">
                            <!-- GRM Stylized Logo -->
                            <div class="text-center mb-2">
                                <div class="thermal-logo-box">
                                    <svg viewBox="0 0 150 75" width="120" height="55" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <pattern id="stippleThermalSold" width="3" height="3" patternUnits="userSpaceOnUse">
                                                <circle cx="1.5" cy="1.5" r="0.6" fill="#000" />
                                            </pattern>
                                        </defs>
                                        <rect width="150" height="75" fill="url(#stippleThermalSold)" opacity="0.3" />
                                        <rect width="150" height="75" fill="none" stroke="#000" stroke-width="1.5" />
                                        <text x="75" y="52" font-family="'Times New Roman', Georgia, serif" font-size="50" font-weight="900" text-anchor="middle" fill="#000" letter-spacing="-2">GRM</text>
                                    </svg>
                                </div>
                            </div>

                            <!-- Store Header -->
                            <div class="thermal-title" id="thShopName">GRM SHOP</div>
                            <div class="thermal-address" id="thShopAddress">
                                SULUR KMCH HOSPITAL<br>
                                OPPOSITE, TRICHY<br>
                                ROAD, SULUR, COIMBATORE
                            </div>
                            <div class="thermal-contact">
                                Phone No: <span id="thShopPhone">8807144175, 8610612625</span>
                            </div>
                            <div class="thermal-gst">
                                GST NO: <span id="thShopGst">33BFQPQ7507C1ZX</span>
                            </div>

                            <div class="thermal-divider-dashed"></div>

                            <!-- Bill Metadata -->
                            <div class="thermal-meta-row">
                                <span>Bill No: <strong id="thBillNumber">--</strong></span>
                                <span>BID: <strong id="thBillId">--</strong></span>
                            </div>
                            <div class="thermal-meta-row">
                                <span>Purchased At: <strong id="thPurchasedAt">--</strong></span>
                            </div>
                            <div class="thermal-meta-row">
                                <span>Staff: <strong id="thStaffName">Cashier</strong></span>
                            </div>

                            <div class="thermal-divider-dashed"></div>

                            <!-- Customer & Payment Mode -->
                            <div class="thermal-meta-row">
                                <span>To: <strong id="thCustomerName">WALK-IN</strong></span>
                                <span>Pay: <strong id="thPayMode" class="text-uppercase">CASH</strong></span>
                            </div>
                            <div class="thermal-meta-row" id="thCustomerPhoneRow">
                                <span>Ph: <strong id="thCustomerPhone">--</strong></span>
                                <span id="thCustGstSpan"></span>
                            </div>
                            <div class="thermal-meta-row small" id="thSplitNotesRow" style="display: none; font-size: 10px; font-weight: 700;">
                                <span id="thSplitNotesText"></span>
                            </div>

                            <div class="thermal-divider-dashed"></div>

                            <!-- Items Table -->
                            <table class="thermal-items-table">
                                <thead>
                                    <tr>
                                        <th style="text-align: left; width: 42%;">Item</th>
                                        <th style="text-align: center; width: 10%;">Qty</th>
                                        <th style="text-align: right; width: 16%;">Price</th>
                                        <th style="text-align: right; width: 16%;">Disc</th>
                                        <th style="text-align: right; width: 16%;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="thItemsBody">
                                    <!-- Populated via JavaScript -->
                                </tbody>
                            </table>

                            <div class="thermal-divider-dashed"></div>

                            <!-- Financial Totals -->
                            <div class="thermal-totals-row">
                                <span>Sub Total (Taxable):</span>
                                <span id="thSubtotal">₹0.00</span>
                            </div>
                            <div class="thermal-totals-row">
                                <span>Total GST:</span>
                                <span id="thTotalGst">+₹0.00</span>
                            </div>
                            <div class="thermal-totals-row" id="thDiscRow">
                                <span>Gross Discount:</span>
                                <span id="thDiscount" class="text-danger">-₹0.00</span>
                            </div>
                            <div class="thermal-totals-row" id="thRoundOffRow" style="display: none;">
                                <span>Round Off:</span>
                                <span id="thRoundOff">₹0.00</span>
                            </div>

                            <div class="thermal-divider-solid"></div>

                            <!-- Net Total -->
                            <div class="thermal-net-row">
                                <span>NET AMOUNT:</span>
                                <span id="thNetTotal">₹0.00</span>
                            </div>

                            <div class="thermal-divider-double"></div>

                            <!-- Terms & Footer -->
                            <div class="thermal-terms">
                                <strong>Terms &amp; Conditions:</strong><br>
                                1. Goods once sold can only be exchanged within 7 days.<br>
                                2. Bill &amp; Price Tag mandatory for exchange.<br>
                                3. No cash refund under any circumstances.
                            </div>
                            <div class="thermal-visit-again">
                                ★ THANK YOU VISIT AGAIN ★
                            </div>
                        </div>
                    </div>

                    <!-- VIEW 3: FULL INVOICE & ITEMIZED AUDIT -->
                    <div id="soldInvoiceView" style="display: none;">
                        <!-- Customer & Bill Overview Card -->
                        <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white">
                            <div class="card-body p-3">
                                <div class="row g-3 align-items-center">
                                    <div class="col-sm-6 col-md-3">
                                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Invoice Number</small>
                                        <div class="fw-bold font-monospace text-primary fs-6" id="detInvNum">--</div>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Billing Date &amp; Time</small>
                                        <div class="fw-bold text-dark fs-6" id="detSaleDate">--</div>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Billing Cashier</small>
                                        <div class="fw-bold text-dark fs-6" id="detCashier">--</div>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Payment Mode</small>
                                        <div>
                                            <span class="badge bg-primary-subtle text-primary font-monospace px-2 py-1 fs-7" id="detPayMode">--</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Garment Items Table -->
                        <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white overflow-hidden">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-bag-check-fill text-success me-2"></i>Billed Garment Items &amp; Serial Tags</h6>
                                <span class="badge bg-light text-dark border" id="detItemCountBadge">0 items</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-muted small text-uppercase" style="font-size: 11px;">
                                        <tr>
                                            <th class="ps-3">#</th>
                                            <th>Product Details</th>
                                            <th>SKU &amp; Piece Serial Tags</th>
                                            <th>Size / Color</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">MRP / Rate</th>
                                            <th class="text-end">Discount</th>
                                            <th class="text-end">Tax (GST)</th>
                                            <th class="text-end pe-3">Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detItemsBody">
                                        <!-- Populated via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 4 Financial Metric Cards -->
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-white rounded-4 border shadow-sm text-center">
                                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Subtotal (Taxable)</small>
                                    <h5 class="fw-bold text-dark mt-1 mb-0" id="detSubtotal">₹0.00</h5>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-white rounded-4 border shadow-sm text-center">
                                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Total GST</small>
                                    <h5 class="fw-bold text-success mt-1 mb-0" id="detGst">+₹0.00</h5>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-white rounded-4 border shadow-sm text-center">
                                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Discount</small>
                                    <h5 class="fw-bold text-danger mt-1 mb-0" id="detDiscount">-₹0.00</h5>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-primary-subtle rounded-4 border border-primary-subtle shadow-sm text-center">
                                    <small class="text-primary text-uppercase fw-bold" style="font-size: 11px;">Net Paid Amount</small>
                                    <h5 class="fw-bold text-primary mt-1 mb-0" id="detNetTotal">₹0.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-none" onclick="copyModalInvNum()">
                        <i class="bi bi-clipboard me-1"></i> Copy Invoice #
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a id="modalInvoiceLink" href="#" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-none">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Full Page Receipt
                    </a>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="printModalThermal()">
                        <i class="bi bi-printer-fill me-1"></i> Print Bill
                    </button>
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 shadow-none" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Embedded Thermal Slip Styles for Modal -->
<style>
.thermal-receipt-card {
    width: 330px;
    max-width: 100%;
    background: #fff;
    padding: 20px 14px;
    color: #000;
    font-family: 'Courier New', Courier, Consolas, Monaco, monospace;
    font-size: 11.5px;
    font-weight: 600;
    line-height: 1.35;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}
.thermal-logo-box {
    display: inline-block;
    border: 1.5px solid #000;
    padding: 4px 10px;
    background: #fff;
}
.thermal-title {
    text-align: center;
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}
.thermal-address {
    text-align: center;
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    line-height: 1.3;
    margin-top: 2px;
}
.thermal-contact, .thermal-gst {
    text-align: center;
    font-size: 10.5px;
    font-weight: 600;
    margin-top: 2px;
}
.thermal-gst {
    font-weight: 800;
    font-size: 11px;
}
.thermal-divider-dashed {
    border-top: 1px dashed #000;
    margin: 6px 0;
}
.thermal-divider-solid {
    border-top: 1px solid #000;
    margin: 6px 0;
}
.thermal-divider-double {
    border-top: 2px solid #000;
    margin: 7px 0;
}
.thermal-meta-row {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.35;
}
.thermal-items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    font-weight: 600;
}
.thermal-items-table th {
    font-weight: 800;
    padding: 3px 0;
    border-bottom: 1px dashed #000;
}
.thermal-items-table td {
    padding: 2px 0;
    vertical-align: top;
}
.thermal-item-name {
    font-weight: 700;
    padding-top: 3px;
    word-break: break-word;
}
.thermal-item-tag {
    font-size: 9.5px;
    font-weight: 600;
    display: block;
    color: #111;
}
.thermal-totals-row {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 600;
    margin: 2px 0;
}
.thermal-net-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    font-weight: 900;
    margin: 4px 0;
}
.thermal-terms {
    font-size: 9.5px;
    font-weight: 600;
    line-height: 1.35;
    margin-top: 4px;
}
.thermal-visit-again {
    text-align: center;
    font-size: 11px;
    font-weight: 800;
    margin-top: 8px;
    letter-spacing: 0.5px;
}
</style>

<script>
window.clearSalesSearch = function() {
    const sInput = document.getElementById('salesSearchInput');
    const btnClear = document.getElementById('btnClearSalesSearch');
    if (sInput) {
        sInput.value = '';
        if (btnClear) btnClear.classList.add('d-none');
        if (window.applySalesFilters) window.applySalesFilters();
    }
};

let CURRENT_ACTIVE_SALE_ID = null;
let CURRENT_ACTIVE_INVOICE_NUM = '';
let soldModalInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    const soldModalEl = document.getElementById('soldProductModal');
    if (soldModalEl && typeof bootstrap !== 'undefined') {
        soldModalInstance = new bootstrap.Modal(soldModalEl);
    }

});

window.openSoldProductModal = function(saleItemId, defaultView = 'specs') {
    if (!saleItemId) return;

    // Reset view mode switcher
    switchSoldModalView(defaultView);

    document.getElementById('modalLoading').style.display = 'block';
    document.getElementById('modalBodyContent').style.display = 'none';

    if (!soldModalInstance) {
        const soldModalEl = document.getElementById('soldProductModal');
        if (soldModalEl && typeof bootstrap !== 'undefined') {
            soldModalInstance = new bootstrap.Modal(soldModalEl);
        }
    }
    if (soldModalInstance) {
        soldModalInstance.show();
    }

    fetch(`<?= url('api/shop/sales') ?>/${saleItemId}?type=item`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                populateSoldModal(res.data);
            } else {
                alert('Failed to load details: ' + (res.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Error fetching sold product details:', err);
            alert('Network error loading details.');
        });
};

window.switchSoldModalView = function(mode) {
    const btnSpecs = document.getElementById('btnSoldModeSpecs');
    const btnThermal = document.getElementById('btnSoldModeThermal');
    const btnInvoice = document.getElementById('btnSoldModeInvoice');

    const viewSpecs = document.getElementById('soldSpecsView');
    const viewThermal = document.getElementById('soldThermalView');
    const viewInvoice = document.getElementById('soldInvoiceView');

    if (!btnSpecs || !btnThermal || !btnInvoice) return;

    // Reset buttons
    btnSpecs.className = 'btn btn-sm rounded-pill fw-bold px-3 text-muted';
    btnThermal.className = 'btn btn-sm rounded-pill fw-bold px-3 text-muted';
    btnInvoice.className = 'btn btn-sm rounded-pill fw-bold px-3 text-muted';

    // Hide all views
    viewSpecs.style.display = 'none';
    viewThermal.style.display = 'none';
    viewInvoice.style.display = 'none';

    if (mode === 'thermal') {
        btnThermal.className = 'btn btn-sm rounded-pill active fw-bold px-3 btn-dark text-white';
        viewThermal.style.display = 'flex';
    } else if (mode === 'invoice') {
        btnInvoice.className = 'btn btn-sm rounded-pill active fw-bold px-3 btn-primary text-white';
        viewInvoice.style.display = 'block';
    } else {
        btnSpecs.className = 'btn btn-sm rounded-pill active fw-bold px-3 btn-primary text-white';
        viewSpecs.style.display = 'block';
    }
};

window.populateSoldModal = function(data) {
    const item = data.item || data;
    const invoiceData = data.invoice || null;
    const sale = (invoiceData && invoiceData.sale) ? invoiceData.sale : item;
    const invoiceItems = (invoiceData && invoiceData.items) ? invoiceData.items : [item];

    CURRENT_ACTIVE_SALE_ID = item.sale_id || sale.id;
    CURRENT_ACTIVE_INVOICE_NUM = item.invoice_number || sale.invoice_number || ('INV-' + CURRENT_ACTIVE_SALE_ID);

    document.getElementById('modalInvNum').innerText = CURRENT_ACTIVE_INVOICE_NUM;
    const saleDateDisplay = item.sale_date_display || sale.created_at_display
        || formatDateTimeDisplay(item.sale_date || sale.created_at);
    document.getElementById('modalSubMeta').innerText = `Billed on ${saleDateDisplay} • ${item.shop_name || sale.shop_name || 'GRM Retail Store'}`;
    document.getElementById('modalInvoiceLink').href = `<?= url('shop/pos/invoice') ?>/${CURRENT_ACTIVE_SALE_ID}`;

    // 1. POPULATE GARMENT SPECS VIEW
    document.getElementById('modalProdName').innerText = item.product_name || 'Product';
    document.getElementById('modalProdSku').innerText = item.sku || '--';
    document.getElementById('modalProdSize').innerText = item.size_name || 'Standard';
    document.getElementById('modalProdColor').innerText = item.color_name || 'Std';

    const catEl = document.getElementById('modalProdCategory');
    if (catEl) {
        catEl.innerText = item.category_name || 'General';
        catEl.style.display = item.category_name ? 'inline-block' : 'none';
    }

    const qty = parseInt(item.quantity) || 1;
    const origPrice = parseFloat(item.original_unit_price || item.unit_price || item.mrp || 0);
    const billedPrice = parseFloat(item.billed_unit_price || (item.total_amount ? item.total_amount / qty : origPrice));
    const discAmt = parseFloat(item.discount_amount || 0);
    const discPercent = parseFloat(item.discount_percent || (origPrice > 0 && discAmt > 0 ? (discAmt / (origPrice * qty)) * 100 : 0));
    const grossTotal = parseFloat(item.gross_total || (origPrice * qty));
    const lineTotal = parseFloat(item.total_amount || 0);
    const taxTotal = parseFloat(item.tax_total || 0);
    const taxableBase = parseFloat(item.taxable_base || (lineTotal - taxTotal));
    const gstRate = parseFloat(item.gst_percentage || 5);

    // Old Price Card
    document.getElementById('modalOldPrice').innerText = '₹' + origPrice.toFixed(2);
    const mrpTag = document.getElementById('modalMrpTag');
    if (mrpTag) {
        const mrpVal = parseFloat(item.mrp || origPrice);
        mrpTag.innerText = mrpVal > origPrice ? `MRP: ₹${mrpVal.toFixed(2)}` : `Regular Rate`;
    }

    // Discount Card
    const discCard = document.getElementById('modalDiscountCard');
    const discAmtEl = document.getElementById('modalDiscountAmount');
    const discRateTag = document.getElementById('modalDiscountRateTag');
    if (discAmt > 0 || discPercent > 0 || billedPrice < origPrice) {
        if (discAmtEl) discAmtEl.innerText = '-₹' + discAmt.toFixed(2);
        if (discRateTag) discRateTag.innerText = (discPercent > 0 ? discPercent.toFixed(1) : ((discAmt / (origPrice * qty)) * 100).toFixed(1)) + '% OFF';
        if (discCard) discCard.className = 'p-2 bg-danger-subtle rounded-3 border border-danger-subtle text-center h-100';
    } else {
        if (discAmtEl) discAmtEl.innerText = '₹0.00';
        if (discRateTag) discRateTag.innerText = 'No Discount';
        if (discCard) discCard.className = 'p-2 bg-light rounded-3 border text-center h-100';
    }

    // New Billed Unit Rate Card
    document.getElementById('modalBilledUnitPrice').innerText = '₹' + billedPrice.toFixed(2);

    // Quantity Card
    document.getElementById('modalQty').innerText = qty + ' pc(s)';
    const grossTag = document.getElementById('modalGrossTotal');
    if (grossTag) {
        grossTag.innerText = 'Gross: ₹' + grossTotal.toFixed(2);
    }

    // Tax & Net Total
    document.getElementById('modalTaxableBase').innerText = '₹' + taxableBase.toFixed(2);
    const gstPctEl = document.getElementById('modalGstPercent');
    if (gstPctEl) {
        gstPctEl.innerText = gstRate.toFixed(1) + '%';
    }
    document.getElementById('modalTax').innerText = '₹' + taxTotal.toFixed(2);
    document.getElementById('modalLineTotal').innerText = '₹' + lineTotal.toFixed(2);

    // Savings Highlight & Top Badge
    const savingsEl = document.getElementById('modalSavingsHighlight');
    const discTopBadge = document.getElementById('modalDiscountBadge');
    const discTopPercent = document.getElementById('modalDiscountPercentText');
    if (discAmt > 0 || billedPrice < origPrice) {
        if (savingsEl) {
            savingsEl.innerHTML = `<i class="bi bi-piggy-bank-fill me-1"></i>Customer saved <strong>₹${discAmt.toFixed(2)}</strong> (${discPercent.toFixed(1)}% Store Discount)`;
            savingsEl.style.display = 'block';
        }
        if (discTopBadge) {
            if (discTopPercent) discTopPercent.innerText = `${discPercent.toFixed(1)}% OFF`;
            discTopBadge.style.display = 'inline-block';
        }
    } else {
        if (savingsEl) {
            savingsEl.innerHTML = `<i class="bi bi-check-circle-fill me-1 text-muted"></i>Standard Rate (No Discount)`;
            savingsEl.style.display = 'block';
        }
        if (discTopBadge) {
            discTopBadge.style.display = 'none';
        }
    }

    // Payment Mode
    const payModeEl = document.getElementById('modalPaymentModeBadge');
    if (payModeEl) {
        payModeEl.innerText = item.payment_mode === 'CASH_UPI' ? 'Cash + UPI (Split)' : (item.payment_mode || 'CASH');
    }

    // Customer & Billed Info
    document.getElementById('modalCustName').innerText = item.customer_name || 'Walk-in Customer';
    document.getElementById('modalCustPhone').innerText = item.customer_phone || 'No phone recorded';
    document.getElementById('modalCashierName').innerText = item.cashier_name || item.billed_employee || 'System Staff';
    document.getElementById('modalSaleDate').innerText = saleDateDisplay;

    // Product Image
    const imgEl = document.getElementById('modalProdImg');
    if (item.image) {
        imgEl.src = item.image;
        imgEl.style.display = 'block';
    } else {
        imgEl.src = '<?= url("assets/images/placeholder.png") ?>';
    }

    // Piece QR Tags
    const tagsContainer = document.getElementById('modalPieceTags');
    const tagCountEl = document.getElementById('modalPieceTagCount');
    tagsContainer.innerHTML = '';
    if (item.scanned_qr_codes) {
        const tags = item.scanned_qr_codes.split(',').map(t => t.trim()).filter(Boolean);
        if (tagCountEl) tagCountEl.innerText = tags.length + (tags.length === 1 ? ' tag' : ' tags');
        tags.forEach(t => {
            const tagBadge = document.createElement('span');
            tagBadge.className = 'badge bg-dark-subtle text-dark border font-monospace px-2 py-1';
            tagBadge.style.fontSize = '11px';
            tagBadge.innerHTML = `<i class="bi bi-qr-code text-primary me-1"></i>${escapeHtml(t)}`;
            tagsContainer.appendChild(tagBadge);
        });
    } else {
        if (tagCountEl) tagCountEl.innerText = '0 tags';
        tagsContainer.innerHTML = '<span class="text-muted small">No individual piece serial tag recorded</span>';
    }

    // 2. POPULATE THERMAL POS BILL VIEW
    const subtotal = parseFloat(sale.subtotal || item.invoice_subtotal || taxableBase);
    const cgst = parseFloat(sale.cgst_amount || 0);
    const sgst = parseFloat(sale.sgst_amount || 0);
    const igst = parseFloat(sale.igst_amount || 0);
    const totalGst = (cgst + sgst + igst) > 0 ? (cgst + sgst + igst) : taxTotal;
    const invDiscount = parseFloat(sale.discount_amount || item.invoice_discount_total || discAmt);
    const invNetTotal = parseFloat(sale.total_amount || item.invoice_total_amount || lineTotal);
    const roundOff = parseFloat(sale.round_off || (invNetTotal - (subtotal + totalGst - invDiscount)));

    document.getElementById('thShopName').textContent = sale.shop_name || item.shop_name || 'GRM SHOP';
    if (sale.shop_address && sale.shop_address.length > 5) {
        document.getElementById('thShopAddress').innerHTML = sale.shop_address.replace(/\n/g, '<br>');
    }
    if (sale.shop_phone) {
        document.getElementById('thShopPhone').textContent = sale.shop_phone;
    }
    if (sale.shop_gstin) {
        document.getElementById('thShopGst').textContent = sale.shop_gstin;
    }

    let billNo = CURRENT_ACTIVE_INVOICE_NUM;
    const matchNo = billNo.match(/(\d+)$/);
    if (matchNo) billNo = matchNo[1];

    document.getElementById('thBillNumber').textContent = billNo;
    document.getElementById('thPurchasedAt').textContent = sale.created_at_display
        || item.sale_date_display
        || formatDateTimeDisplay(item.sale_date || sale.created_at);
    document.getElementById('thBillId').textContent = CURRENT_ACTIVE_SALE_ID || '1026';
    document.getElementById('thStaffName').textContent = sale.staff_name || item.cashier_name || 'Cashier';
    document.getElementById('thCustomerName').textContent = (item.customer_name || sale.customer_name || 'WALK-IN').toUpperCase();
    document.getElementById('thPayMode').textContent = (item.payment_mode || sale.payment_mode) === 'CASH_UPI' ? 'CASH + UPI' : (item.payment_mode || sale.payment_mode || 'CASH');

    if (item.customer_phone || sale.customer_phone) {
        document.getElementById('thCustomerPhoneRow').style.display = 'flex';
        document.getElementById('thCustomerPhone').textContent = item.customer_phone || sale.customer_phone;
    } else {
        document.getElementById('thCustomerPhoneRow').style.display = 'none';
    }

    // Split Payment note
    const splitRow = document.getElementById('thSplitNotesRow');
    if ((item.payment_mode === 'CASH_UPI' || sale.payment_mode === 'CASH_UPI') && ((sale.notes && sale.notes.includes('Split Payment')) || (item.sale_notes && item.sale_notes.includes('Split Payment')))) {
        splitRow.style.display = 'flex';
        document.getElementById('thSplitNotesText').textContent = sale.notes || item.sale_notes;
    } else {
        splitRow.style.display = 'none';
    }

    // Render Thermal Item Rows
    const thBody = document.getElementById('thItemsBody');
    thBody.innerHTML = '';
    invoiceItems.forEach(it => {
        const itQty = parseInt(it.quantity) || 1;
        const itPrice = parseFloat(it.unit_price || it.original_unit_price || 0);
        const itDisc = parseFloat(it.discount_amount || 0);
        const itAmt = parseFloat(it.total_amount || (itQty * itPrice - itDisc));
        const tags = it.scanned_qr_codes ? it.scanned_qr_codes : (it.sku || '');

        const trName = document.createElement('tr');
        trName.className = 'thermal-item-name';
        trName.innerHTML = `
            <td colspan="5">
                ${escapeHtml(it.product_name || 'Garment')} ${it.size_name ? '- ' + escapeHtml(it.size_name) : ''} ${it.color_name ? '(' + escapeHtml(it.color_name) + ')' : ''}
                <span class="thermal-item-tag">Tag: ${escapeHtml(tags)}</span>
            </td>
        `;

        const trData = document.createElement('tr');
        trData.innerHTML = `
            <td></td>
            <td style="text-align: center;">${itQty}</td>
            <td style="text-align: right;">${itPrice.toFixed(2)}</td>
            <td style="text-align: right;">${itDisc > 0 ? itDisc.toFixed(2) : '0.00'}</td>
            <td style="text-align: right;">${itAmt.toFixed(2)}</td>
        `;

        thBody.appendChild(trName);
        thBody.appendChild(trData);
    });

    document.getElementById('thSubtotal').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('thTotalGst').textContent = '+₹' + totalGst.toFixed(2);
    document.getElementById('thDiscount').textContent = invDiscount > 0 ? '-₹' + invDiscount.toFixed(2) : '₹0.00';
    document.getElementById('thNetTotal').textContent = '₹' + invNetTotal.toFixed(2);

    const roundRow = document.getElementById('thRoundOffRow');
    if (Math.abs(roundOff) > 0.009) {
        roundRow.style.display = 'flex';
        document.getElementById('thRoundOff').textContent = (roundOff > 0 ? '+' : '') + '₹' + roundOff.toFixed(2);
    } else {
        roundRow.style.display = 'none';
    }

    // 3. POPULATE FULL INVOICE DETAILED AUDIT VIEW
    document.getElementById('detInvNum').textContent = CURRENT_ACTIVE_INVOICE_NUM;
    document.getElementById('detSaleDate').textContent = saleDateDisplay;
    document.getElementById('detCashier').textContent = sale.staff_name || item.cashier_name || 'Staff Cashier';
    document.getElementById('detPayMode').textContent = item.payment_mode || sale.payment_mode || 'CASH';
    document.getElementById('detItemCountBadge').textContent = invoiceItems.length + (invoiceItems.length === 1 ? ' item' : ' items');

    const detBody = document.getElementById('detItemsBody');
    detBody.innerHTML = '';
    invoiceItems.forEach((it, idx) => {
        const itQty = parseInt(it.quantity) || 1;
        const itPrice = parseFloat(it.unit_price || it.original_unit_price || 0);
        const itDisc = parseFloat(it.discount_amount || 0);
        const itGstPct = parseFloat(it.gst_percentage || 5.0);
        const itAmt = parseFloat(it.total_amount || (itQty * itPrice - itDisc));

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-3 fw-bold">${idx + 1}</td>
            <td>
                <div class="fw-bold text-dark">${escapeHtml(it.product_name || 'Garment Item')}</div>
                <small class="text-muted font-monospace">${escapeHtml(it.product_code || '')}</small>
            </td>
            <td>
                <span class="badge bg-light text-dark border font-monospace">${escapeHtml(it.sku || '--')}</span>
                ${it.scanned_qr_codes ? `<div class="badge bg-dark-subtle text-dark border font-monospace d-inline-block mt-1"><i class="bi bi-qr-code text-primary me-1"></i>${escapeHtml(it.scanned_qr_codes)}</div>` : ''}
            </td>
            <td>
                <span class="badge bg-light text-dark border">${escapeHtml(it.size_name || 'Free')}</span>
                <span class="badge bg-light text-dark border">${escapeHtml(it.color_name || 'Standard')}</span>
            </td>
            <td class="text-center fw-bold">${itQty}</td>
            <td class="text-end font-monospace">₹${itPrice.toFixed(2)}</td>
            <td class="text-end font-monospace text-danger">${itDisc > 0 ? '-₹' + itDisc.toFixed(2) : '—'}</td>
            <td class="text-end font-monospace text-success">${itGstPct.toFixed(1)}%</td>
            <td class="text-end pe-3 fw-bold text-dark font-monospace fs-6">₹${itAmt.toFixed(2)}</td>
        `;
        detBody.appendChild(tr);
    });

    document.getElementById('detSubtotal').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('detGst').textContent = '+₹' + totalGst.toFixed(2);
    document.getElementById('detDiscount').textContent = invDiscount > 0 ? '-₹' + invDiscount.toFixed(2) : '₹0.00';
    document.getElementById('detNetTotal').textContent = '₹' + invNetTotal.toFixed(2);

    document.getElementById('modalLoading').style.display = 'none';
    document.getElementById('modalBodyContent').style.display = 'block';
};

window.printModalThermal = function() {
    if (!CURRENT_ACTIVE_SALE_ID) return;
    const invUrl = '<?= url("shop/pos/invoice") ?>/' + CURRENT_ACTIVE_SALE_ID;
    const printWin = window.open(invUrl, '_blank');
    if (printWin) {
        printWin.focus();
    }
};

window.copyModalInvNum = function() {
    if (!CURRENT_ACTIVE_INVOICE_NUM) return;
    navigator.clipboard.writeText(CURRENT_ACTIVE_INVOICE_NUM).then(() => {
        alert('Invoice Number copied to clipboard: ' + CURRENT_ACTIVE_INVOICE_NUM);
    });
};

function formatDateTimeDisplay(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
           d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Real-Time Live Letter-by-Letter Filter Engine for Sales Register
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('salesSearchInput');
    const startDateInput = document.getElementById('salesStartDate');
    const endDateInput = document.getElementById('salesEndDate');
    const catSelect = document.getElementById('salesCatSelect');
    const tableBody = document.getElementById('salesTableBody');
    const countBadge = document.getElementById('salesCountBadge');
    const btnClear = document.getElementById('btnClearSalesSearch');
    const rows = Array.from(document.querySelectorAll('.sales-row'));
    const paymentTabs = Array.from(document.querySelectorAll('[data-payment-tab]'));
    let activePaymentTab = 'cash';

    window.applySalesFilters = function() {
        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const catId = (catSelect ? catSelect.value : 'ALL');
        const startDate = (startDateInput ? startDateInput.value : '');
        const endDate = (endDateInput ? endDateInput.value : '');

        if (btnClear) {
            if (query) btnClear.classList.remove('d-none');
            else btnClear.classList.add('d-none');
        }

        let visibleCount = 0;
        let cashCount = 0;
        let onlineCount = 0;
        let cashRevenue = 0;
        let onlineRevenue = 0;

        rows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            const rowCat = row.getAttribute('data-cat') || '';
            const rowDate = row.getAttribute('data-date') || '';
            const rowCashRevenue = parseFloat(row.getAttribute('data-cash-revenue')) || 0;
            const rowOnlineRevenue = parseFloat(row.getAttribute('data-online-revenue')) || 0;

            const matchQuery = !query || searchData.includes(query);
            const matchCat = (catId === 'ALL') || (rowCat === catId);
            const matchStart = !startDate || (rowDate >= startDate);
            const matchEnd = !endDate || (rowDate <= endDate);
            const matchesOtherFilters = matchQuery && matchCat && matchStart && matchEnd;

            if (matchesOtherFilters) {
                cashRevenue += rowCashRevenue;
                onlineRevenue += rowOnlineRevenue;
                if (rowCashRevenue > 0) cashCount++;
                if (rowOnlineRevenue > 0) onlineCount++;
            }

            const matchesPaymentTab = activePaymentTab === 'cash'
                ? rowCashRevenue > 0
                : rowOnlineRevenue > 0;
            if (matchesOtherFilters && matchesPaymentTab) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const cashTotalEl = document.getElementById('cashPaymentTabTotal');
        const onlineTotalEl = document.getElementById('onlinePaymentTabTotal');
        const cashCountEl = document.getElementById('cashPaymentTabCount');
        const onlineCountEl = document.getElementById('onlinePaymentTabCount');
        if (cashTotalEl) cashTotalEl.textContent = `₹${cashRevenue.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (onlineTotalEl) onlineTotalEl.textContent = `₹${onlineRevenue.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (cashCountEl) cashCountEl.textContent = `${cashCount.toLocaleString('en-IN')} records`;
        if (onlineCountEl) onlineCountEl.textContent = `${onlineCount.toLocaleString('en-IN')} records`;

        if (countBadge) {
            countBadge.textContent = `Showing ${visibleCount} records`;
        }

        paymentTabs.forEach(tab => {
            const isActive = tab.getAttribute('data-payment-tab') === activePaymentTab;
            tab.classList.toggle('active', isActive);
            tab.classList.toggle('text-dark', !isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            const totalBadge = tab.querySelector('.badge');
            if (totalBadge) {
                totalBadge.className = isActive
                    ? (activePaymentTab === 'cash' ? 'badge bg-white text-success' : 'badge bg-white text-primary')
                    : 'badge bg-light text-primary';
            }
        });

        // Show/hide empty row
        let emptyRow = document.getElementById('noSalesMatchRow');
        if (visibleCount === 0) {
            if (rows.length > 0 && !emptyRow && tableBody) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'noSalesMatchRow';
                emptyRow.innerHTML = `
                    <td colspan="10" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <h6 class="fw-bold text-secondary">No Matching Sold Products Found</h6>
                        <p class="small text-muted mb-2">No transactions match your live search criteria.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="clearSalesSearch()">
                            Clear Search
                        </button>
                    </td>
                `;
                tableBody.appendChild(emptyRow);
            }
        } else if (emptyRow) {
            emptyRow.remove();
        }
    };

    paymentTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            activePaymentTab = tab.getAttribute('data-payment-tab') || 'cash';
            applySalesFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applySalesFilters);
        searchInput.addEventListener('keyup', applySalesFilters);
    }
    if (catSelect) {
        catSelect.addEventListener('change', applySalesFilters);
    }
    if (startDateInput) {
        startDateInput.addEventListener('change', applySalesFilters);
        startDateInput.addEventListener('input', applySalesFilters);
    }
    if (endDateInput) {
        endDateInput.addEventListener('change', applySalesFilters);
        endDateInput.addEventListener('input', applySalesFilters);
    }

    applySalesFilters();
});
</script>
