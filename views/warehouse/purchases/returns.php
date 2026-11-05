<?php
// views/warehouse/purchases/returns.php — Warehouse Direct Purchases Customer Returns & Exchanges Portal
$currentTab = $_GET['tab'] ?? 'all';
$summary = $summary ?? [];
$recentReturns = $recentReturns ?? [];
?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill fw-semibold">
                    <i class="bi bi-arrow-repeat me-1"></i>Warehouse Direct Purchases
                </span>
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 font-monospace">
                    Returns &amp; Exchanges
                </span>
            </div>
            <h2 class="fw-bold mb-1 text-dark">Customer Returns &amp; Exchanges Portal</h2>
           
        </div>
    </div>

    <!-- 5 QUICK ANALYSIS & FILTER CARDS -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Transactions -->
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 rounded-4 transition-card cursor-pointer quick-analysis-card <?= $currentTab === 'all' ? 'bg-primary bg-opacity-10 border-primary' : 'bg-white' ?>"
                 onclick="filterTab('all')" style="cursor: pointer;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">All Cases</span>
                            <h3 class="mb-0 mt-1 fw-bold text-primary">
                                <?= number_format($summary['total_transactions'] ?? 0) ?>
                                <span class="fs-6 fw-normal text-muted">txns</span>
                            </h3>
                            <small class="text-secondary d-flex align-items-center mt-1" style="font-size: 11px;">
                                <i class="bi bi-clock-history me-1 text-primary"></i> <?= number_format($summary['today_transactions'] ?? 0) ?> Today • <?= number_format($summary['total_items_returned'] ?? 0) ?> Pcs Total
                            </small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary p-3 rounded-circle fs-3 flex-shrink-0">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Direct Refunds Issued -->
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100 rounded-4 transition-card cursor-pointer quick-analysis-card <?= $currentTab === 'returns' ? 'bg-danger bg-opacity-10 border-danger' : 'bg-white' ?>"
                 onclick="filterTab('returns')" style="cursor: pointer;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Direct Refunds</span>
                            <h3 class="mb-0 mt-1 fw-bold text-danger">
                                <?= formatCurrency($summary['total_refund_amount'] ?? 0) ?>
                            </h3>
                            <small class="text-danger d-flex align-items-center mt-1" style="font-size: 11px;">
                                <i class="bi bi-cash-stack me-1"></i> <?= number_format($summary['total_returns_count'] ?? 0) ?> Cash / UPI Refunds
                            </small>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger p-3 rounded-circle fs-3 flex-shrink-0">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Size & Color Exchanges -->
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100 rounded-4 transition-card cursor-pointer quick-analysis-card <?= $currentTab === 'exchanges' ? 'bg-success bg-opacity-10 border-success' : 'bg-white' ?>"
                 onclick="filterTab('exchanges')" style="cursor: pointer;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Exchanges &amp; Swaps</span>
                            <h3 class="mb-0 mt-1 fw-bold text-success">
                                <?= number_format($summary['total_exchanges_count'] ?? 0) ?>
                                <span class="fs-6 fw-normal text-muted">cases</span>
                            </h3>
                            <small class="text-success d-flex align-items-center mt-1" style="font-size: 11px;">
                                <i class="bi bi-arrow-left-right me-1"></i> Size / Color / Catalog Swaps
                            </small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success p-3 rounded-circle fs-3 flex-shrink-0">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Returned Pieces Restocked -->
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100 rounded-4 bg-white transition-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Returned Pieces</span>
                            <h3 class="mb-0 mt-1 fw-bold text-info">
                                <?= number_format($summary['total_items_returned'] ?? 0) ?>
                                <span class="fs-6 fw-normal text-muted">pcs</span>
                            </h3>
                            <small class="text-info d-flex align-items-center mt-1" style="font-size: 11px;">
                                <i class="bi bi-check2-circle me-1"></i> Inwarded to Warehouse
                            </small>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info p-3 rounded-circle fs-3 flex-shrink-0">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 5: Damaged / Defective Pieces -->
        <div class="col-sm-6 col-xl">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100 rounded-4 bg-white transition-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Damaged Pieces</span>
                            <h3 class="mb-0 mt-1 fw-bold text-warning">
                                <?= number_format($summary['total_damaged_pcs'] ?? 0) ?>
                                <span class="fs-6 fw-normal text-muted">pcs</span>
                            </h3>
                            <small class="text-warning d-flex align-items-center mt-1" style="font-size: 11px;">
                                <i class="bi bi-exclamation-triangle me-1"></i> Routed to Damaged Pool
                            </small>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning p-3 rounded-circle fs-3 flex-shrink-0">
                            <i class="bi bi-shield-x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (hasPermission('purchases_returns', 'view')): ?>
    <!-- SEARCH & LOOKUP SECTION -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden border-top border-4 border-primary">
        <div class="card-body p-4 bg-white">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <label class="form-label fw-bold text-dark mb-1">
                        <i class="bi bi-search text-primary me-1"></i> Search Direct Customer Purchase Order
                    </label>
                    
                    <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                        <span class="input-group-text bg-light border-end-0 text-muted px-3">
                            <i class="bi bi-qr-code-scan text-primary fs-5"></i>
                        </span>
                        <input type="text" id="poSearchInput" class="form-control border-start-0 ps-0 fw-semibold" 
                               placeholder="Type PO #, Invoice #, or Customer Phone..." 
                               oninput="scheduleInvoiceCount()" onkeydown="if(event.key === 'Enter') executeLookup()" autofocus>
                        <button class="btn btn-primary px-4 fw-bold" type="button" id="btnLookup" onclick="executeLookup()">
                            <span id="lookupSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                            <span>Lookup Order</span>
                        </button>
                    </div>
                    <div id="lookupErrorContainer" class="mt-2 text-danger small fw-bold d-none">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="lookupErrorMessage"></span>
                    </div>
                    <div id="phoneInvoiceResults" class="mt-2 d-none" role="status" aria-live="polite">
                        <div id="phoneInvoiceCount" class="text-primary small fw-semibold mb-2"></div>
                        <div id="phoneInvoiceList" class="list-group small" style="max-height: 260px; overflow-y: auto;"></div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-info-circle text-primary fs-5"></i>
                            <span class="fw-bold text-dark small">How Returns &amp; Exchanges Work:</span>
                        </div>
                        <ul class="mb-0 text-secondary small ps-3" style="line-height: 1.5; font-size: 12px;">
                            <li><strong>Direct Refund:</strong> Returns items &amp; logs cash/UPI refund.</li>
                            <li><strong>Same-Product Swap:</strong> Exchange for other sizes/colors with live warehouse stock.</li>
                            <li><strong>Catalog Exchange:</strong> Pick alternate products with auto price difference adjustment.</li>
                            <li><strong>Stock Sync:</strong> Good items restock to available; damaged route to defect pool.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- RECENT TRANSACTIONS TABLE -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary fs-5"></i>
                <h5 class="fw-bold mb-0 text-dark">Return &amp; Exchange History</h5>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5">
                    <?= count($recentReturns) ?> records
                </span>
            </div>

            <!-- Filter Tabs -->
            <div class="btn-group btn-group-sm rounded-pill p-1 bg-light border" role="group">
                <button type="button" class="btn rounded-pill px-3 fw-semibold <?= $currentTab === 'all' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="filterTab('all')">
                    All
                </button>
                <button type="button" class="btn rounded-pill px-3 fw-semibold <?= $currentTab === 'returns' ? 'btn-danger shadow-sm' : 'btn-light text-muted' ?>" onclick="filterTab('returns')">
                    Direct Returns
                </button>
                <button type="button" class="btn rounded-pill px-3 fw-semibold <?= $currentTab === 'exchanges' ? 'btn-success shadow-sm' : 'btn-light text-muted' ?>" onclick="filterTab('exchanges')">
                    Exchanges
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted font-monospace" style="font-size: 11px;">
                    <tr>
                        <th class="ps-4">Return Number</th>
                        <th>Original PO / Date</th>
                        <th>Customer</th>
                        <th>Type &amp; Items</th>
                        <th>Refund / Diff Amount</th>
                        <th>Settlement</th>
                        <th>Processed By</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentReturns)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="py-4">
                                <i class="bi bi-inbox text-muted opacity-50 d-block mb-2" style="font-size: 40px;"></i>
                                <h6 class="fw-bold text-secondary">No Return or Exchange Records Found</h6>
                                <p class="small text-muted mb-0">Use the search box above to lookup a Direct Purchase and process a return or exchange.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentReturns as $r): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge <?= ($r['return_type'] ?? '') === 'EXCHANGE' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> rounded-pill px-2.5 py-1 fw-bold font-monospace" style="font-size: 11px;">
                                    <i class="bi <?= ($r['return_type'] ?? '') === 'EXCHANGE' ? 'bi-arrow-left-right' : 'bi-arrow-counterclockwise' ?> me-1"></i>
                                    <?= e($r['return_number']) ?>
                                </span>
                            </div>
                            <small class="text-muted d-block mt-0.5" style="font-size: 11px;">
                                <?= date('d M Y, h:i A', strtotime($r['created_at'])) ?>
                            </small>
                        </td>
                        <td>
                            <a href="<?= url('warehouse/purchases/receive/' . ($r['purchase_order_id'] ?? 0)) ?>" class="fw-bold text-decoration-none text-primary font-monospace">
                                <?= e($r['po_number'] ?? 'PO-Direct') ?>
                            </a>
                            <small class="text-muted d-block" style="font-size: 11px;">
                                PO Total: <?= formatCurrency($r['original_po_amount'] ?? 0) ?>
                            </small>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= e($r['customer_name'] ?? 'Direct Customer') ?></div>
                            <small class="text-muted font-monospace"><?= e($r['customer_phone'] ?? '-') ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                                <?= number_format($r['total_items_count'] ?? 1) ?> Pcs
                            </span>
                            <?php 
                            $hasScreenshot = false;
                            $displayNotesList = $r['notes'] ?? '';
                            if (preg_match('/Payment Proof:\s*(https?:\/\/[^\s]+)/', $displayNotesList, $m)) {
                                $hasScreenshot = true;
                                $displayNotesList = trim(str_replace($m[0], '', $displayNotesList));
                            }
                            ?>
                            <?php if ($hasScreenshot): ?>
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle mt-1 rounded-pill" style="font-size: 9px;"><i class="bi bi-camera-fill me-1"></i>Has Proof</span>
                            <?php endif; ?>
                            <?php if (!empty($displayNotesList)): ?>
                            <small class="text-muted d-block text-truncate mt-0.5" style="max-width: 180px;" title="<?= e($displayNotesList) ?>">
                                <?= e($displayNotesList) ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($r['return_type'] ?? '') === 'EXCHANGE'): ?>
                                <div class="fw-bold text-success">
                                    New: <?= formatCurrency($r['new_amount'] ?? 0) ?>
                                </div>
                                <small class="text-muted d-block">
                                    Old: <?= formatCurrency($r['refund_amount'] ?? 0) ?>
                                </small>
                            <?php else: ?>
                                <div class="fw-bold text-danger">
                                    -<?= formatCurrency($r['refund_amount'] ?? 0) ?>
                                </div>
                                <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-0.5" style="font-size: 10px;">
                                    Refund
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($r['return_type'] ?? '') === 'EXCHANGE'): ?>
                                <?php 
                                $pAction = $r['payment_action'] ?? 'NONE';
                                $diff = (float)($r['difference_amount'] ?? 0);
                                ?>
                                <?php if ($pAction === 'COLLECT' && $diff > 0): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-1 fw-bold">
                                        Collected +<?= formatCurrency($diff) ?>
                                    </span>
                                <?php elseif ($pAction === 'REFUND' && $diff > 0): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1 fw-bold">
                                        Refunded -<?= formatCurrency($diff) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 fw-bold">
                                        Even Swap (₹0)
                                    </span>
                                <?php endif; ?>
                                <?php 
                                $modeVal = $r['settlement_mode'] ?? $r['refund_mode'] ?? 'CASH';
                                $modeIcon = 'bi-cash';
                                if (stripos($modeVal, 'UPI') !== false) $modeIcon = 'bi-qr-code-scan';
                                elseif (stripos($modeVal, 'SPLIT') !== false) $modeIcon = 'bi-wallet2';
                                elseif (stripos($modeVal, 'BANK') !== false) $modeIcon = 'bi-bank';
                                elseif (stripos($modeVal, 'CREDIT') !== false) $modeIcon = 'bi-receipt';
                                ?>
                                <small class="text-muted d-block mt-1 font-monospace" style="font-size: 10px;">
                                    <i class="bi <?= $modeIcon ?> text-secondary me-0.5"></i> Mode: <strong class="text-dark"><?= e($modeVal) ?></strong>
                                </small>
                            <?php else: ?>
                                <?php 
                                $rModeVal = $r['refund_mode'] ?? 'CASH';
                                $rModeIcon = (stripos($rModeVal, 'UPI') !== false) ? 'bi-qr-code-scan' : ((stripos($rModeVal, 'SPLIT') !== false) ? 'bi-wallet2' : 'bi-cash');
                                ?>
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1 font-monospace">
                                    <i class="bi <?= $rModeIcon ?> me-1"></i><?= e($rModeVal) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="text-dark small fw-semibold"><?= e($r['inspected_by_name'] ?? 'Staff') ?></span>
                            <small class="text-muted d-block" style="font-size: 10px;"><?= e($r['warehouse_name'] ?? 'Central WH') ?></small>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= url('warehouse/purchases/returns/view/' . $r['id']) ?>" class="btn btn-outline-secondary" title="View Audit Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('warehouse/purchases/returns/invoice/' . $r['id']) ?>" target="_blank" class="btn btn-outline-primary" title="Print Tax Invoice / Exchange Slip">
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

<!-- ========================================================================= -->
<!-- INTERACTIVE RETURN & EXCHANGE MODAL — Modern Card Design                  -->
<!-- ========================================================================= -->
<div class="modal fade" id="returnExchangeModal" tabindex="-1" aria-labelledby="returnExchangeModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 1200px; width: 95vw;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">

            <!-- Modal Header: Step Title + Invoice Info Strip -->
            <div class="modal-header px-4 py-3 border-bottom bg-white" style="flex-direction: column; align-items: stretch; gap: 0;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold mb-0 text-dark" id="returnExchangeModalLabel" style="font-size: 1.15rem;">
                        <i class="bi bi-arrow-return-left text-primary me-2"></i>Step 2: Select Items &amp; Transaction Type
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Invoice Info Strip -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2" style="font-size: 13px; color: #555;">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span>Invoice: <strong class="text-dark font-monospace" id="modalPoNumber">—</strong></span>
                        <span class="text-muted">•</span>
                        <span>Customer: <strong class="text-dark" id="modalCustomerName">—</strong> (<span class="font-monospace" id="modalCustomerPhone">—</span>)</span>
                        <span class="text-muted">•</span>
                        <span>Date: <strong class="text-dark" id="modalOrderDate">—</strong></span>
                        <span class="text-muted">•</span>
                        <span><i class="bi bi-building me-1 text-secondary"></i><span id="modalWarehouseName">—</span></span>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge px-3 py-1.5 fw-semibold rounded-pill" id="modalPayModeBadge"
                              style="background:#e8f4fd; color:#1565c0; border:1px solid #bbdefb; font-size:12px;">
                            Pay Mode: —
                        </span>
                        <span class="badge px-3 py-1.5 fw-semibold rounded-pill"
                              style="background:#e8f5e9; color:#1b5e20; border:1px solid #c8e6c9; font-size:12px;">
                            Total Paid: <span id="modalOrderTotal">₹0.00</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body bg-light p-4" style="overflow-y:auto;">

                <!-- Action Type Card Selector -->
                <div class="mb-4">
                    <div class="fw-semibold text-dark mb-2" style="font-size:13px; letter-spacing:0.3px;">
                        Select Customer Request / Action Type:
                    </div>
                    <div class="row g-3" id="actionTypeCards">

                        <!-- Card 1: Direct Return -->
                        <div class="col-12 col-md-6">
                            <div class="action-type-card card border-2 h-100 p-3 selected-action shadow-sm"
                                 id="card_RETURN" onclick="selectActionCard('RETURN')"
                                 style="border-radius:12px; cursor:pointer;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                          style="width:32px;height:32px;background:#dbeafe;">
                                        <i class="bi bi-arrow-return-left text-primary" style="font-size:15px;"></i>
                                    </span>
                                    <strong class="text-dark" style="font-size:13.5px;">Direct Return</strong>
                                    <span class="ms-auto badge" style="background:#dbeafe;color:#2563eb;font-size:10px; white-space:nowrap;">
                                        <i class="bi bi-cash-coin"></i>
                                    </span>
                                </div>
                                <p class="text-muted mb-0" style="font-size:11.5px; line-height:1.4;">
                                    Return items and issue direct refund (Cash, UPI).
                                </p>
                            </div>
                        </div>

                        <!-- Card 2: Exchange Same Product -->
                        <div class="col-12 col-md-6">
                            <div class="action-type-card card border-2 h-100 p-3 shadow-sm"
                                 id="card_EXCHANGE_SAME" onclick="selectActionCard('EXCHANGE_SAME')"
                                 style="border-radius:12px; cursor:pointer;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                          style="width:32px;height:32px;background:#f0fdf4;">
                                        <i class="bi bi-arrow-left-right text-success" style="font-size:15px;"></i>
                                    </span>
                                    <strong class="text-dark" style="font-size:13.5px;">Exchange</strong>
                                    <span class="ms-auto badge" style="background:#dcfce7;color:#166534;font-size:10px; white-space:nowrap;">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </span>
                                </div>
                                <p class="text-muted mb-0" style="font-size:11.5px; line-height:1.4;">
                                    Swap for different size or color variant of this same product style.
                                </p>
                            </div>
                        </div>

                    </div><!-- /row -->
                </div>

                <!-- Hidden legacy select for JS compatibility -->
                <select id="actionTypeSelect" class="d-none" onchange="switchActionMode(this.value)">
                    <option value="RETURN">Direct Return</option>
                    <option value="EXCHANGE_SAME">Exchange</option>
                </select>

                <!-- Dynamic Replacement QR Scanner Box (Shown for Exchange) -->
                <div id="exchangeScannerBox" class="rounded-3 mb-3 p-3 text-white d-none" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1px solid #334155;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success text-white px-2.5 py-1 fw-bold" style="font-size: 0.75rem;">
                                <i class="bi bi-upc-scan me-1"></i>Scan Replacement Garment QR / Barcode
                            </span>
                            <span class="text-white-50 small">
                                Scan physical replacement piece QR tag, barcode, or SKU to auto-select replacement &amp; verify stock
                            </span>
                        </div>
                    </div>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-dark border-secondary text-success px-3">
                            <i class="bi bi-qr-code-scan fs-6"></i>
                        </span>
                        <input type="text" id="modalExchangeScanInput" class="form-control bg-dark text-white border-secondary font-monospace"
                               placeholder="Scan physical replacement piece QR tag (e.g. HHH102604), Barcode, or SKU..."
                               autocomplete="off"
                               onkeydown="if(event.key === 'Enter'){ event.preventDefault(); onModalScanExchangeItem(this.value); }">
                        <button type="button" class="btn btn-success px-4 fw-bold" onclick="onModalScanExchangeItem(document.getElementById('modalExchangeScanInput').value)">
                            <i class="bi bi-plus-circle me-1"></i> Scan &amp; Match
                        </button>
                    </div>
                    <div id="modalScanFeedback" class="mt-2 small d-none alert py-1 px-2.5 mb-0" style="font-size: 0.82rem;"></div>
                </div>

                <!-- Purchased Items Table -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius:12px; overflow:hidden;">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-danger" style="font-size:13px;">
                            <i class="bi bi-bag-check me-1"></i>
                            Purchased Items on Invoice <span class="fw-normal text-muted">(Select Items to Return)</span>
                        </span>
                        <span class="badge text-bg-light border fw-semibold" id="itemCountBadge" style="font-size:11px;">0 Line Item(s)</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="returnItemsTable" style="font-size:13px;">
                            <thead style="background:#f8f9fa; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#6c757d; font-weight:700;">
                                <tr>
                                    <th class="ps-3" style="width:40px;">
                                        <input type="checkbox" id="selectAllCheckbox" class="form-check-input" checked onchange="toggleSelectAll(this)">
                                    </th>
                                    <th style="min-width:280px;">Product Item (Purchased)</th>
                                    <th style="width:70px; text-align:center;">Sold</th>
                                    <th style="width:100px; text-align:center;">Return Qty</th>
                                    <th style="width:140px;" id="conditionColHeader">Condition</th>
                                    <th style="width:110px; text-align:right;">Unit Rate</th>
                                    <th style="width:120px; text-align:right;" id="lastColHeader">Item Credit</th>
                                    <th id="sameExchangeHeader" style="min-width:300px; width:340px;" class="d-none">Replacement (Size/Color)</th>
                                </tr>
                            </thead>
                            <tbody id="returnItemsTbody">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Financial Settlement & Refund Mode Card -->
                <div class="card border-0 shadow-sm" style="border-radius:12px; overflow:hidden;">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark" style="font-size:13px;">
                            <i class="bi bi-wallet2 text-primary me-1"></i>
                            Financial Settlement &amp; Reason
                        </span>
                        <span class="badge text-bg-light border text-muted" style="font-size:11px;">
                            Audit &amp; Restock Details
                        </span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-start">
                            <!-- Left: Settlement Mode + Reason + Notes -->
                            <div class="col-lg-7">
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-dark mb-1" style="font-size:12px;">Refund / Settlement Mode:</label>
                                        <select id="refundModeSelect" class="form-select form-select-sm" onchange="toggleSplitPaymentUI()">
                                            <option value="CASH">💵 Cash</option>
                                            <option value="UPI">📱 UPI / QR Transfer</option>
                                            <option value="BANK_TRANSFER">🏦 Bank Transfer</option>
                                            <option value="SPLIT">🔀 CASH + UPI (Split)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-dark mb-1" style="font-size:12px;">Reason for Return/Swap:</label>
                                        <select id="returnReasonSelect" class="form-select form-select-sm">
                                            <option value="Size ill-fitting / wrong size">Size ill-fitting / wrong size</option>
                                            <option value="Color preference change">Color preference change</option>
                                            <option value="Minor stitching defect">Minor stitching defect</option>
                                            <option value="Fabric quality issue">Fabric quality issue</option>
                                            <option value="Customer mind changed">Customer mind changed</option>
                                            <option value="Other">Other reason</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-2 d-none" id="paymentScreenshotContainer">
                                    <label class="form-label fw-semibold text-dark small mb-1 d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-camera me-1 text-primary"></i>Payment Screenshot / Proof</span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:.65rem;"><i class="bi bi-cloud-arrow-up me-1"></i>Cloudinary</span>
                                    </label>
                                    <input type="file" id="paymentScreenshotInput" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                </div>

                                <!-- Split Payment UI -->
                                <div id="splitPaymentUI" class="mb-2 p-2 rounded border bg-white d-none">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-success fw-bold small"><i class="bi bi-wallet2 me-1"></i>Cash + UPI Split Breakdown</span>
                                        <button type="button" class="btn btn-sm btn-outline-success py-0 px-2 fw-semibold" style="font-size:11px;" onclick="autoBalanceSplitPayment()">
                                            <i class="bi bi-arrow-repeat me-1"></i>Auto Balance
                                        </button>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="text-muted small mb-1" style="font-size:11px;">Cash Amount (₹)</label>
                                            <input type="number" id="splitCashInput" class="form-control form-control-sm text-center fw-bold" value="0.00" oninput="calculateSplitPayment()">
                                        </div>
                                        <div class="col-6">
                                            <label class="text-muted small mb-1" style="font-size:11px;">UPI Amount (₹)</label>
                                            <input type="number" id="splitUpiInput" class="form-control form-control-sm text-center fw-bold" value="0.00" oninput="calculateSplitPayment()">
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="text-muted small mb-1" style="font-size:11px;">UPI Ref / Transaction ID / App</label>
                                        <input type="text" id="splitUpiRefInput" class="form-control form-control-sm" placeholder="e.g. GPay Ref #402918237">
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small" style="font-size:11px;">Split Total: <strong class="text-dark" id="splitTotalDisplay">₹0.00</strong></span>
                                        <span id="splitBalanceBadge" class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;">Balanced (₹0.00)</span>
                                    </div>
                                </div>

                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted"><i class="bi bi-card-text"></i></span>
                                    <input type="text" id="returnNotesInput" class="form-control" placeholder="Additional audit notes (optional)...">
                                </div>
                            </div>

                            <!-- Right: Financial Summary -->
                            <div class="col-lg-5">
                                <div class="p-3 rounded-3 border bg-white shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-semibold">Return Product Paid Amount:</span>
                                        <strong class="text-danger font-monospace fs-6" id="pillTotalRefund">₹0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-muted small fw-semibold">Current Choose Product Amount:</span>
                                        <strong class="text-success font-monospace fs-6" id="pillTotalNew">₹0.00</strong>
                                    </div>
                                    <hr class="my-2 opacity-25">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-dark small">Settlement Net Balance:</span>
                                        </div>
                                        <div id="differenceBadge" class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 fw-bold rounded-3 text-wrap w-100 text-center font-monospace">
                                            Even Swap (₹0.00)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /modal-body -->

            <!-- Modal Footer -->
            <div class="modal-footer bg-white py-3 px-4 border-top">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <?php if (hasPermission('purchases_returns', 'process')): ?>
                    <button type="button" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm" id="btnConfirmSubmit" onclick="submitReturnProcess()">
                        <span id="submitSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <i class="bi bi-check2-circle me-1"></i> Confirm &amp; Generate Tax Slip
                    </button>
                <?php endif; ?>
            </div>

        </div><!-- /modal-content -->
    </div><!-- /modal-dialog -->
</div>

<style>
/* ── Action card hover & selected states ── */
.action-type-card { 
    transition: all .18s ease;
    border-color: #e2e8f0 !important;
    background: #ffffff !important;
}
.action-type-card:hover:not(.selected-action) {
    border-color: #93c5fd !important;
    background: #f0f9ff !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(37,99,235,.1);
}
#card_RETURN.selected-action {
    border-color: #2563eb !important;
    background: #eff6ff !important;
    box-shadow: 0 0 0 3px rgba(37,99,235,.18) !important;
}
#card_EXCHANGE_SAME.selected-action {
    border-color: #16a34a !important;
    background: #f0fdf4 !important;
    box-shadow: 0 0 0 3px rgba(22,163,74,.18) !important;
}
/* Table row selected highlight */
#returnItemsTable tbody tr.row-selected { background: #f0f7ff !important; }
#returnItemsTable tbody tr { transition: background .12s; }
/* Piece tag badge */
.piece-tag-badge {
    background: #1e293b; color: #fbbf24;
    font-family: monospace; font-size: 10px;
    padding: 2px 7px; border-radius: 5px;
    display: inline-flex; align-items: center; gap: 3px;
}
/* SKU / size / color pill badges in item row */
.info-badge {
    font-size: 10px; padding: 2px 7px; border-radius: 4px;
    display: inline-flex; align-items: center; gap: 3px;
    font-weight: 600; white-space: nowrap;
}
.badge-sku  { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; font-family: monospace; }
.badge-code { background:#fffbeb; color:#92400e; border:1px solid #fde68a; font-family: monospace; }
.badge-qr   { background:#ede9fe; color:#5b21b6; border:1px solid #ddd6fe; font-family: monospace; }
.badge-size { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.badge-color{ background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
</style>


<!-- ========================================================================= -->
<!-- JAVASCRIPT LOGIC -->
<!-- ========================================================================= -->
<script>
let currentPurchaseData = null;
let invoiceCountTimer = null;
let invoiceCountRequestId = 0;
const pendingReplacementScans = new Set();

function isPhoneSearch(query) {
    const digits = query.replace(/\D/g, '');
    return /^[+\d\s()-]+$/.test(query) && digits.length >= 10 && digits.length <= 15;
}

function renderPhoneInvoices(result) {
    const results = document.getElementById('phoneInvoiceResults');
    const message = document.getElementById('phoneInvoiceCount');
    const list = document.getElementById('phoneInvoiceList');
    if (!results || !message || !list) return;

    const invoices = Array.isArray(result.invoices) ? result.invoices : [];
    message.textContent = `This customer has ${Number(result.invoice_count) || 0} invoice${Number(result.invoice_count) === 1 ? '' : 's'} on this phone number. Select a bill to continue.`;
    list.replaceChildren();

    if (invoices.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'list-group-item text-muted';
        empty.textContent = 'No direct purchase bills found for this phone number.';
        list.appendChild(empty);
    } else {
        invoices.forEach(invoice => {
            const row = document.createElement('div');
            row.className = 'list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2';

            const details = document.createElement('div');
            details.className = 'd-flex flex-wrap align-items-center gap-2';

            const number = document.createElement('strong');
            number.className = 'font-monospace text-primary';
            number.textContent = invoice.po_number || `PO #${invoice.id}`;
            details.appendChild(number);

            const date = document.createElement('span');
            date.className = 'text-muted';
            date.textContent = invoice.order_date || '';
            details.appendChild(date);

            const total = document.createElement('span');
            total.className = 'fw-semibold text-success';
            total.textContent = `₹${Number(invoice.total_amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
            details.appendChild(total);

            const qty = document.createElement('span');
            qty.className = 'badge bg-light text-dark border';
            qty.textContent = `${Number(invoice.total_qty) || 0} pcs · ${Number(invoice.item_count) || 0} line(s)`;
            details.appendChild(qty);

            const payment = document.createElement('span');
            payment.className = `badge ${invoice.payment_status === 'PAID' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis'}`;
            payment.textContent = invoice.payment_status || 'PENDING';
            details.appendChild(payment);
            row.appendChild(details);

            if (invoice.return_number) {
                const processed = document.createElement('span');
                processed.className = 'badge bg-secondary-subtle text-secondary';
                processed.textContent = `Already processed: ${invoice.return_number}`;
                row.appendChild(processed);
            } else {
                const select = document.createElement('button');
                select.type = 'button';
                select.className = 'btn btn-sm btn-outline-primary';
                select.textContent = 'Select Bill';
                select.addEventListener('click', () => executeLookup(invoice.po_number || String(invoice.id)));
                row.appendChild(select);
            }

            list.appendChild(row);
        });
    }

    results.classList.remove('d-none');
}

function scheduleInvoiceCount() {
    clearTimeout(invoiceCountTimer);
    const results = document.getElementById('phoneInvoiceResults');
    const message = document.getElementById('phoneInvoiceCount');
    const list = document.getElementById('phoneInvoiceList');
    const query = document.getElementById('poSearchInput').value.trim();
    const requestId = ++invoiceCountRequestId;

    if (!isPhoneSearch(query)) {
        results.classList.add('d-none');
        return;
    }

    const phone = query.replace(/\D/g, '');
    message.textContent = 'Checking invoice history...';
    list.replaceChildren();
    results.classList.remove('d-none');
    invoiceCountTimer = setTimeout(async () => {
        try {
            const response = await fetch('<?= url("warehouse/purchases/returns/lookup") ?>?query=' + encodeURIComponent(phone) + '&count_only=1');
            const result = await response.json();
            if (requestId !== invoiceCountRequestId) return;
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Could not load invoice history.');
            }
            if (result.invoice_count === null) {
                results.classList.add('d-none');
                return;
            }
            renderPhoneInvoices(result);
        } catch (error) {
            if (requestId !== invoiceCountRequestId) return;
            message.textContent = 'Could not load invoice history.';
            list.replaceChildren();
            results.classList.remove('d-none');
        }
    }, 350);
}

function focusSearch() {
    const input = document.getElementById('poSearchInput');
    input.focus();
    input.select();
}

function filterTab(tab) {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.location.href = url.toString();
}

async function executeLookup(selectedInvoice = null) {
    const input = document.getElementById('poSearchInput');
    const query = selectedInvoice || input.value.trim();
    const errorContainer = document.getElementById('lookupErrorContainer');
    const errorMessage = document.getElementById('lookupErrorMessage');
    
    if (errorContainer) errorContainer.classList.add('d-none');

    if (!query) {
        if (errorMessage) errorMessage.innerText = 'Please enter a PO Number, Invoice Number, or Customer Phone.';
        if (errorContainer) errorContainer.classList.remove('d-none');
        return;
    }

    if (!selectedInvoice && isPhoneSearch(query)) {
        scheduleInvoiceCount();
        return;
    }

    const btn = document.getElementById('btnLookup');
    const spinner = document.getElementById('lookupSpinner');
    btn.disabled = true;
    spinner.classList.remove('d-none');

    try {
        const response = await fetch('<?= url("warehouse/purchases/returns/lookup") ?>?query=' + encodeURIComponent(query));
        const result = await response.json();

        if (!result.success || !result.data) {
            if (errorMessage) errorMessage.innerText = result.message || 'No direct purchase order found.';
            if (errorContainer) errorContainer.classList.remove('d-none');
            return;
        }

        currentPurchaseData = result.data;
        if (isPhoneSearch(query) && input.value.trim() === query) {
            scheduleInvoiceCount();
        }
        openReturnModal(result.data);
    } catch (e) {
        if (errorMessage) errorMessage.innerText = 'Lookup error: ' + e.message;
        if (errorContainer) errorContainer.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        spinner.classList.add('d-none');
    }
}

function openReturnModal(data) {
    const po = data.purchase;
    const items = data.items;

    document.getElementById('modalPoNumber').innerText = po.po_number || ('PO #' + po.id);
    document.getElementById('modalCustomerName').innerText = po.customer_name || 'Direct Customer';
    document.getElementById('modalCustomerPhone').innerText = po.customer_phone || '-';
    document.getElementById('modalOrderDate').innerText = (po.order_date || '-') + (po.order_time ? ' ' + po.order_time : '');
    document.getElementById('modalWarehouseName').innerText = po.warehouse_name || 'Central WH';

    // Populate header strip badges
    const payBadge = document.getElementById('modalPayModeBadge');
    const payMethod = po.payment_method || po.payment_mode || 'N/A';
    if (payBadge) payBadge.innerHTML = `<i class="bi bi-credit-card me-1"></i>Pay Mode: <strong>${payMethod}</strong>`;
    const totalEl = document.getElementById('modalOrderTotal');
    if (totalEl) totalEl.innerText = '₹' + parseFloat(po.total_amount || po.grand_total || 0).toLocaleString('en-IN', {minimumFractionDigits: 2});

    // Default to RETURN card
    selectActionCard('RETURN');
    renderReturnItemsTable(items);

    const modal = new bootstrap.Modal(document.getElementById('returnExchangeModal'));
    modal.show();
}

// Card selector for Action Type (strictly single-select)
function selectActionCard(mode) {
    document.querySelectorAll('.action-type-card').forEach(c => {
        c.classList.remove('selected-action');
    });
    const card = document.getElementById('card_' + mode);
    if (card) {
        card.classList.add('selected-action');
    }

    // Sync legacy select
    const sel = document.getElementById('actionTypeSelect');
    if (sel) { sel.value = mode; }

    switchActionMode(mode);
}

function renderReturnItemsTable(items) {
    const tbody = document.getElementById('returnItemsTbody');
    tbody.innerHTML = '';
    pendingReplacementScans.clear();
    Object.keys(rowPieceTags).forEach(k => delete rowPieceTags[k]);

    // Update item count badge
    const countBadge = document.getElementById('itemCountBadge');
    if (countBadge) countBadge.textContent = (items ? items.length : 0) + ' Line Item(s)';

    if (!items || items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No items in this purchase order.</td></tr>';
        return;
    }

    items.forEach((it, idx) => {
        const origQty   = parseInt(it.quantity || 1);
        const maxQty    = parseInt(it.returnable_qty || it.quantity || 1);
        const unitPrice = parseFloat(it.unit_price || it.variant_selling_price || 0);
        const prodCode  = it.product_code || it.code || '';
        const sku       = it.sku   || '';
        const size      = it.size_name  || '';
        const color     = it.color_name || '';
        const itemQr    = it.batch_lot_no || it.qr_code || (it.notes && it.notes.includes('QR:') ? it.notes.replace(/^QR:\s*/i, '').trim() : (it.notes ? it.notes.trim() : '')) || '';

        if (itemQr) {
            rowPieceTags[idx] = new Set([itemQr]);
        }

        let siblingOptions = '<option value="">-- Swap with other size/color --</option>';
        if (it.sibling_variants && it.sibling_variants.length > 0) {
            it.sibling_variants.forEach(sib => {
                const isCurrent = sib.id == it.variant_id;
                const stock = parseInt(sib.warehouse_stock || 0);
                const disabled = stock <= 0 && !isCurrent ? 'disabled' : '';
                const sibQr = sib.qr_code || sib.sku || '';
                const sibCode = prodCode || '';
                siblingOptions += `<option value="${sib.id}" 
                    data-price="${sib.selling_price}" 
                    data-stock="${stock}" 
                    data-sku="${sib.sku}" 
                    data-qr="${sibQr}" 
                    data-code="${sibCode}" 
                    data-size="${sib.size_name || ''}" 
                    data-color="${sib.color_name || ''}" 
                    ${disabled}>
                    ${sib.size_name || ''} / ${sib.color_name || ''} (SKU: ${sib.sku}) — ₹${parseFloat(sib.selling_price).toFixed(2)} [Stock: ${stock} pcs]
                </option>`;
            });
        }

        const tr = document.createElement('tr');
        tr.id = `itemRow_${idx}`;
        tr.dataset.variantQr = it.qr_code || '';
        tr.innerHTML = `
            <td class="ps-3">
                <input type="checkbox" class="form-check-input item-select-cb" data-idx="${idx}" checked
                       onchange="this.closest('tr').classList.toggle('row-selected', this.checked); recalculateTotals()">
            </td>
            <td style="max-width:330px;">
                <div class="fw-semibold text-dark mb-1">${it.product_name || 'Garment'}</div>
                <div class="d-flex flex-wrap gap-1 mb-1">
                    ${prodCode ? `<span class="info-badge badge-code" title="Product Style Code"><i class="bi bi-tag-fill me-0.5"></i>${prodCode}</span>` : ''}
                    ${sku      ? `<span class="info-badge badge-sku" title="SKU">${sku}</span>` : ''}
                    ${itemQr   ? `<span class="info-badge badge-qr" title="Product / Piece QR Code"><i class="bi bi-qr-code me-0.5"></i>${itemQr}</span>` : ''}
                    ${size     ? `<span class="info-badge badge-size" title="Size">${size}</span>` : ''}
                    ${color    ? `<span class="info-badge badge-color" title="Color">${color}</span>` : ''}
                </div>
                <!-- Scanned piece-tag badges area -->
                <div class="scanned-tags-display d-flex flex-wrap gap-1" id="tagsDisplay_${idx}"></div>
                ${it.already_returned_qty > 0 ? `<div class="small text-danger mt-1" style="font-size:10px;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Already Returned: ${it.already_returned_qty} Pcs</div>` : ''}
            </td>
            <td class="text-center fw-semibold text-dark">${origQty}</td>
            <td>
                <input type="number" class="form-control form-control-sm return-qty-input fw-bold text-center"
                       style="width:80px;"
                       data-idx="${idx}" value="${maxQty}" min="1" max="${maxQty}"
                       oninput="document.querySelector('.item-select-cb[data-idx=\'${idx}\']').checked = true; this.closest('tr').classList.add('row-selected'); validateQty(this, ${maxQty}); recalculateTotals()">
            </td>
            <td>
                <select class="form-select form-select-sm item-condition-select" data-idx="${idx}" style="min-width:130px;">
                    <option value="GOOD" selected>Good (Restock)</option>
                    <option value="DAMAGED">Damaged (QC Defect)</option>
                </select>
            </td>
            <td class="fw-semibold font-monospace">₹${unitPrice.toFixed(2)}</td>
            <td>
                <strong class="text-danger item-refund-val" data-idx="${idx}">₹0.00</strong>
            </td>
            <td class="same-product-cell d-none" style="min-width:260px;">
                <input type="hidden" class="sibling-variant-input" data-idx="${idx}" id="siblingVariantInput_${idx}" value="">
                <div id="siblingPlaceholder_${idx}" class="text-muted text-center py-2 px-2 border border-dashed rounded-3 bg-light" style="font-size:11px; cursor:pointer;" onclick="document.getElementById('modalExchangeScanInput')?.focus()">
                    <i class="bi bi-qr-code-scan me-1 text-primary"></i><span>Scan replacement item QR</span>
                </div>
                <div id="siblingPreview_${idx}" class="sibling-preview-box small d-none p-2 rounded-3 border bg-white shadow-sm"></div>
            </td>
        `;
        tr.classList.add('row-selected');
        tbody.appendChild(tr);
    });

    recalculateTotals();
}

function setRowReplacementVariant(idx, v, scannedTag) {
    const input = document.getElementById('siblingVariantInput_' + idx);
    const placeholder = document.getElementById('siblingPlaceholder_' + idx);
    const preview = document.getElementById('siblingPreview_' + idx);
    if (!input || !preview) return;

    if (!v) {
        input.value = '';
        delete input.dataset.scannedCode;
        delete input.dataset.scannedTag;
        delete input.dataset.price;
        delete input.dataset.qr;
        if (placeholder) placeholder.classList.remove('d-none');
        preview.classList.add('d-none');
        preview.innerHTML = '';
        recalculateTotals();
        return;
    }

    input.value = v.id;
    const finalTag = scannedTag || v.piece_tag || v.qr_code || '';
    input.dataset.scannedTag = finalTag;
    input.dataset.price = v.selling_price || 0;
    input.dataset.qr = v.qr_code || '';

    const price = parseFloat(v.selling_price || 0);
    const stock = parseInt(v.available_stock !== undefined ? v.available_stock : (v.warehouse_stock || 0));
    const sku = v.sku || '';
    const defQr = v.qr_code || '';
    const size = v.size_name || '';
    const color = v.color_name || '';
    const code = v.product_code || v.code || '';

    const origItem = currentPurchaseData.items[idx];
    const origPrice = parseFloat(origItem.unit_price || 0);
    const priceDiff = price - origPrice;

    let diffText = '';
    if (priceDiff > 0.01) {
        diffText = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-auto" style="font-size:9.5px;">+₹${priceDiff.toFixed(2)}</span>`;
    } else if (priceDiff < -0.01) {
        diffText = `<span class="badge bg-success-subtle text-success border border-success-subtle ms-auto" style="font-size:9.5px;">-₹${Math.abs(priceDiff).toFixed(2)}</span>`;
    } else {
        diffText = `<span class="badge bg-light text-muted border ms-auto" style="font-size:9.5px;">Even (₹0.00)</span>`;
    }

    if (placeholder) placeholder.classList.add('d-none');

    // Build QR Badges: show scanned piece tag and product QR
    let qrBadgesHtml = '';
    if (finalTag) {
        qrBadgesHtml += `<span class="badge" style="background:#1e293b; color:#38bdf8; font-family:monospace; font-size:10px;"><i class="bi bi-qr-code-scan me-1"></i>QR: ${finalTag}</span>`;
    }
    if (defQr && defQr !== finalTag && defQr !== sku) {
        qrBadgesHtml += `<span class="badge bg-primary-subtle text-primary border font-monospace" style="font-size:9.5px;"><i class="bi bi-qr-code me-0.5"></i>${defQr}</span>`;
    }

    preview.innerHTML = `
        <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
            <div class="d-flex flex-wrap gap-1 align-items-center">
                ${qrBadgesHtml}
            </div>
            ${diffText}
        </div>
        <div class="d-flex flex-wrap gap-1 align-items-center mb-1">
            ${code ? `<span class="badge bg-warning-subtle text-warning-emphasis border font-monospace" style="font-size:9.5px;"><i class="bi bi-tag-fill me-0.5"></i>${code}</span>` : ''}
            <span class="badge bg-light text-dark border font-monospace" style="font-size:9.5px;">SKU: ${sku}</span>
            <span class="badge bg-secondary-subtle text-secondary" style="font-size:9.5px;">${size} / ${color}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center font-monospace" style="font-size:11px;">
            <span class="text-success fw-bold">₹${price.toFixed(2)}</span>
            <div class="d-flex align-items-center gap-2">
                <span class="${stock > 0 ? 'text-muted' : 'text-danger fw-bold'}">Stock: ${stock} pcs</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" title="Remove / Re-scan" onclick="setRowReplacementVariant(${idx}, null)"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    `;
    preview.classList.remove('d-none');
    recalculateTotals();
}

// Per-row scanned piece tags storage: { rowIdx -> Set of tags }
const rowPieceTags = {};

function addPieceTag(idx, raw) {
    const tag = raw.trim();
    if (!tag) return;
    if (!rowPieceTags[idx]) rowPieceTags[idx] = new Set();
    if (rowPieceTags[idx].has(tag)) {
        const inp = document.getElementById('tagInput_' + idx);
        if (inp) { inp.classList.add('border-danger'); setTimeout(() => inp.classList.remove('border-danger'), 1200); }
        return;
    }
    rowPieceTags[idx].add(tag);
    renderTagBadges(idx);
    const cb = document.querySelector('.item-select-cb[data-idx="' + idx + '"]');
    if (cb) { cb.checked = true; cb.closest('tr').classList.add('row-selected'); }
    recalculateTotals();
}

function removePieceTag(idx, tag) {
    if (rowPieceTags[idx]) rowPieceTags[idx].delete(tag);
    renderTagBadges(idx);
    recalculateTotals();
}

function renderTagBadges(idx) {
    const display = document.getElementById('tagsDisplay_' + idx);
    if (!display) return;
    const tags = rowPieceTags[idx] ? [...rowPieceTags[idx]] : [];
    if (tags.length === 0) { display.innerHTML = ''; return; }
    display.innerHTML = tags.map(t =>
        `<span class="piece-tag-badge" style="cursor:pointer;" title="Click to remove"
               onclick="removePieceTag(${idx}, '${t.replace(/'/g, "\\'")}')"
         ><i class="bi bi-qr-code" style="font-size:9px;"></i>${t} <i class="bi bi-x" style="font-size:9px;"></i></span>`
    ).join('');
}

function getRowPieceTags(idx) {
    return rowPieceTags[idx] ? [...rowPieceTags[idx]] : [];
}

function validateQty(input, max) {
    let v = parseInt(input.value) || 1;
    if (v < 1) v = 1;
    if (v > max) v = max;
    input.value = v;
}

function toggleSelectAll(master) {
    const cbs = document.querySelectorAll('.item-select-cb');
    cbs.forEach(cb => cb.checked = master.checked);
    recalculateTotals();
}

function switchActionMode(mode) {
    const sameCells = document.querySelectorAll('.same-product-cell');
    const sameHeader = document.getElementById('sameExchangeHeader');
    const lastColHdr = document.getElementById('lastColHeader');
    const scannerBox = document.getElementById('exchangeScannerBox');
    const scanInput = document.getElementById('modalExchangeScanInput');

    if (mode === 'EXCHANGE_SAME') {
        if (sameHeader) sameHeader.classList.remove('d-none');
        sameCells.forEach(c => c.classList.remove('d-none'));
        if (lastColHdr) lastColHdr.textContent = 'Item Credit';
        if (scannerBox) scannerBox.classList.remove('d-none');
        if (scanInput) setTimeout(() => scanInput.focus(), 150);
    } else { // RETURN / DIRECT REFUND
        if (sameHeader) sameHeader.classList.add('d-none');
        sameCells.forEach(c => c.classList.add('d-none'));
        if (lastColHdr) lastColHdr.textContent = 'Item Credit';
        if (scannerBox) scannerBox.classList.add('d-none');
    }

    clearModalScanFeedback();
    toggleSplitPaymentUI();
    recalculateTotals();
}

function showModalScanFeedback(message, type = 'danger') {
    const el = document.getElementById('modalScanFeedback');
    if (!el) return;
    el.className = `mt-2 small alert py-1.5 px-3 mb-0 text-${type} bg-${type}-subtle border border-${type}-subtle fw-bold d-flex align-items-center gap-2 rounded-3`;
    const icon = type === 'danger' ? 'bi-exclamation-circle-fill' : (type === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill');
    el.innerHTML = `<i class="bi ${icon} fs-6"></i> <span>${message}</span>`;
    el.classList.remove('d-none');
    if (type === 'success') {
        setTimeout(() => { el.classList.add('d-none'); }, 5000);
    }
}

function clearModalScanFeedback() {
    const el = document.getElementById('modalScanFeedback');
    if (el) el.classList.add('d-none');
}

function normalizeReplacementScanValue(value) {
    return String(value || '').trim().toLowerCase();
}

function hasScannedReplacement(code, tag = '') {
    const identifiers = [normalizeReplacementScanValue(code), normalizeReplacementScanValue(tag)].filter(Boolean);
    return [...document.querySelectorAll('.sibling-variant-input')].some(input =>
        [input.dataset.scannedCode, input.dataset.scannedTag]
            .map(normalizeReplacementScanValue)
            .some(identifier => identifier && identifiers.includes(identifier))
    );
}

function onModalScanExchangeItem(code) {
    if (!code || !code.trim()) return;
    code = code.trim();
    const normalizedCode = normalizeReplacementScanValue(code);
    if (hasScannedReplacement(normalizedCode) || pendingReplacementScans.has(normalizedCode)) {
        showModalScanFeedback('This product has already been scanned for this return/exchange.', 'warning');
        document.getElementById('modalExchangeScanInput').value = '';
        return;
    }

    pendingReplacementScans.add(normalizedCode);
    clearModalScanFeedback();
    document.getElementById('modalExchangeScanInput').value = '';

    const mode = document.getElementById('actionTypeSelect').value;
    const whId = currentPurchaseData && currentPurchaseData.purchase ? (currentPurchaseData.purchase.warehouse_id || 1) : 1;

    fetch(`<?= url("api/warehouse/purchases/lookup-scan") ?>?warehouse_id=${whId}&code=${encodeURIComponent(code)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.variant) {
                const scannedVar = data.variant;
                const scannedTag = data.extracted_tag || data.tag_code || data.variant.piece_tag || data.variant.qr_code || code;
                if (hasScannedReplacement(normalizedCode, scannedTag)) {
                    showModalScanFeedback('This product has already been scanned for this return/exchange.', 'warning');
                    return;
                }

                if (mode === 'EXCHANGE_SAME') {
                    // Smart priority matching for line items on this invoice
                    let matchedRow = -1;
                    const items = currentPurchaseData.items || [];

                    // Priority 1: A checked row that hasn't been assigned a replacement yet
                    for (let idx = 0; idx < items.length; idx++) {
                        const cb = document.querySelector(`.item-select-cb[data-idx="${idx}"]`);
                        const input = document.getElementById('siblingVariantInput_' + idx);
                        if (cb && cb.checked && (!input || !input.value)) {
                            const sibs = items[idx].sibling_variants || [];
                            const isMatch = (parseInt(items[idx].variant_id) === parseInt(scannedVar.id)) || sibs.some(s => parseInt(s.id) === parseInt(scannedVar.id));
                            if (isMatch) {
                                matchedRow = idx;
                                break;
                            }
                        }
                    }

                    // Priority 2: Any row where replacement is not assigned yet
                    if (matchedRow === -1) {
                        for (let idx = 0; idx < items.length; idx++) {
                            const input = document.getElementById('siblingVariantInput_' + idx);
                            if (!input || !input.value) {
                                const sibs = items[idx].sibling_variants || [];
                                const isMatch = (parseInt(items[idx].variant_id) === parseInt(scannedVar.id)) || sibs.some(s => parseInt(s.id) === parseInt(scannedVar.id));
                                if (isMatch) {
                                    matchedRow = idx;
                                    break;
                                }
                            }
                        }
                    }

                    // Priority 3: First matching row (replace existing)
                    if (matchedRow === -1) {
                        for (let idx = 0; idx < items.length; idx++) {
                            const sibs = items[idx].sibling_variants || [];
                            const isMatch = (parseInt(items[idx].variant_id) === parseInt(scannedVar.id)) || sibs.some(s => parseInt(s.id) === parseInt(scannedVar.id));
                            if (isMatch) {
                                matchedRow = idx;
                                break;
                            }
                        }
                    }

                    if (matchedRow !== -1) {
                        setRowReplacementVariant(matchedRow, scannedVar, scannedTag);
                        const assignedInput = document.getElementById('siblingVariantInput_' + matchedRow);
                        if (assignedInput) assignedInput.dataset.scannedCode = normalizedCode;

                        // Check row checkbox
                        const cb = document.querySelector(`.item-select-cb[data-idx="${matchedRow}"]`);
                        if (cb) { cb.checked = true; cb.closest('tr').classList.add('row-selected'); }

                        showModalScanFeedback(`✓ Replacement Scanned & Assigned: ${scannedVar.product_name} (${scannedVar.size_name || ''} / ${scannedVar.color_name || ''}) — Scanned QR: ${scannedTag}`, 'success');
                    } else {
                        showModalScanFeedback(`Scanned item '${scannedVar.product_name}' (${scannedTag}) is not a size or color variant of a product on this invoice.`, 'warning');
                    }
                } else {
                    showModalScanFeedback(`Direct Return mode is active. Switch to 'Exchange' action at the top to scan replacement items.`, 'warning');
                }
            } else {
                showModalScanFeedback(data.message || `No product found for scanned code: ${code}`, 'danger');
            }
        })
        .catch(err => {
            showModalScanFeedback('Scan Lookup Error: ' + err.message, 'danger');
        })
        .finally(() => {
            pendingReplacementScans.delete(normalizedCode);
        });
}

function recalculateTotals() {
    if (!currentPurchaseData || !currentPurchaseData.items) return;

    const mode = document.getElementById('actionTypeSelect').value;
    let totalRefund = 0.0;
    let totalNew = 0.0;

    const checkboxes = document.querySelectorAll('.item-select-cb');
    checkboxes.forEach(cb => {
        const idx = cb.getAttribute('data-idx');
        const it = currentPurchaseData.items[idx];
        const qtyInput = document.querySelector(`.return-qty-input[data-idx="${idx}"]`);
        const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
        const unitPrice = parseFloat(it.unit_price || it.variant_selling_price || 0);

        const refundValEl = document.querySelector(`.item-refund-val[data-idx="${idx}"]`);
        const lineRefund = unitPrice * qty;
        if (refundValEl) {
            refundValEl.innerText = '₹' + lineRefund.toFixed(2);
        }

        if (cb.checked) {
            totalRefund += lineRefund;

            if (mode === 'EXCHANGE_SAME') {
                const sibInput = document.getElementById(`siblingVariantInput_${idx}`);
                if (sibInput && sibInput.value) {
                    const sibPrice = parseFloat(sibInput.dataset.price || unitPrice);
                    totalNew += (sibPrice * qty);
                }
            }
        }
    });

    document.getElementById('pillTotalRefund').innerText = '₹' + totalRefund.toFixed(2);
    document.getElementById('pillTotalNew').innerText = '₹' + totalNew.toFixed(2);
    document.getElementById('modalOrderTotal').innerText = '₹' + totalRefund.toFixed(2);

    const diffBadge = document.getElementById('differenceBadge');
    const btnSubmit = document.getElementById('btnConfirmSubmit');

    if (mode === 'RETURN') {
        diffBadge.style.background = '';
        diffBadge.style.color = '';
        diffBadge.style.borderColor = '';
        diffBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6 fw-bold rounded text-wrap w-100 text-center';
        diffBadge.innerText = 'Refund Customer: -₹' + totalRefund.toFixed(2);
        if (btnSubmit) {
            btnSubmit.className = 'btn btn-danger rounded-pill px-5 fw-bold shadow-sm text-white';
            btnSubmit.style.background = '';
            btnSubmit.innerHTML = '<span id="submitSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span><i class="bi bi-cash-coin me-1"></i> Process Return &amp; Issue Refund';
        }
    } else {
        diffBadge.style.background = '';
        diffBadge.style.color = '';
        diffBadge.style.borderColor = '';
        const diff = totalNew - totalRefund;
        if (diff > 0.01) {
            diffBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 fs-6 fw-bold rounded text-wrap w-100 text-center';
            diffBadge.innerText = `Collect Extra: ₹${totalNew.toFixed(2)} - ₹${totalRefund.toFixed(2)} = +₹${diff.toFixed(2)}`;
        } else if (diff < -0.01) {
            diffBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6 fw-bold rounded text-wrap w-100 text-center';
            diffBadge.innerText = `Refund Customer: ₹${totalRefund.toFixed(2)} - ₹${totalNew.toFixed(2)} = -₹${Math.abs(diff).toFixed(2)}`;
        } else {
            diffBadge.className = 'badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 fw-bold rounded text-wrap w-100 text-center';
            diffBadge.innerText = 'Even Swap (₹0.00)';
        }
        if (btnSubmit) {
            btnSubmit.className = 'btn btn-primary rounded-pill px-5 fw-bold shadow-sm';
            btnSubmit.style.background = '';
            btnSubmit.innerHTML = '<span id="submitSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span><i class="bi bi-check2-circle me-1"></i> Confirm &amp; Generate Tax Slip';
        }
    }

    calculateSplitPayment();
}

function toggleSplitPaymentUI() {
    const mode = document.getElementById('refundModeSelect').value;
    const ui = document.getElementById('splitPaymentUI');
    const screenshotUi = document.getElementById('paymentScreenshotContainer');
    
    if (mode === 'SPLIT') {
        ui.classList.remove('d-none');
        autoBalanceSplitPayment();
    } else {
        ui.classList.add('d-none');
    }

    if (mode === 'UPI' || mode === 'SPLIT' || mode === 'BANK_TRANSFER') {
        screenshotUi.classList.remove('d-none');
    } else {
        screenshotUi.classList.add('d-none');
        document.getElementById('paymentScreenshotInput').value = '';
    }
}

function autoBalanceSplitPayment() {
    const diffText = document.getElementById('differenceBadge').innerText;
    let netAmount = parseFloat(diffText.replace(/[^0-9.-]+/g, '')) || 0;
    
    // Net amount is positive if we collect extra, negative if we refund.
    // In both cases, the absolute value is the settlement amount.
    netAmount = Math.abs(netAmount);
    
    document.getElementById('splitCashInput').value = netAmount.toFixed(2);
    document.getElementById('splitUpiInput').value = '0.00';
    calculateSplitPayment();
}

function calculateSplitPayment() {
    const cash = parseFloat(document.getElementById('splitCashInput').value) || 0;
    const upi = parseFloat(document.getElementById('splitUpiInput').value) || 0;
    const total = cash + upi;
    
    document.getElementById('splitTotalDisplay').innerText = '₹' + total.toFixed(2);
    
    const diffText = document.getElementById('differenceBadge').innerText;
    let requiredAmount = parseFloat(diffText.replace(/[^0-9.-]+/g, '')) || 0;
    requiredAmount = Math.abs(requiredAmount);
    
    const balance = total - requiredAmount;
    const badge = document.getElementById('splitBalanceBadge');
    
    if (Math.abs(balance) < 0.01) {
        badge.className = 'badge bg-success-subtle text-success border border-success-subtle';
        badge.innerText = 'Balanced (₹0.00)';
    } else if (balance > 0) {
        badge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle';
        badge.innerText = 'Over by ₹' + balance.toFixed(2);
    } else {
        badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle';
        badge.innerText = 'Short by ₹' + Math.abs(balance).toFixed(2);
    }
}

async function submitReturnProcess() {
    if (!currentPurchaseData) return;

    const mode = document.getElementById('actionTypeSelect').value;
    const items = [];
    const checkboxes = document.querySelectorAll('.item-select-cb:checked');

    if (checkboxes.length === 0) {
        alert('Please select at least one item from the purchase order to return/exchange.');
        return;
    }

    checkboxes.forEach(cb => {
        const idx = cb.getAttribute('data-idx');
        const it = currentPurchaseData.items[idx];
        const qty = parseInt(document.querySelector(`.return-qty-input[data-idx="${idx}"]`).value) || 1;
        const condition = document.querySelector(`.item-condition-select[data-idx="${idx}"]`).value;
        const sibInput = document.getElementById(`siblingVariantInput_${idx}`);
        const repVariantId = (mode === 'EXCHANGE_SAME' && sibInput && sibInput.value) ? sibInput.value : null;

        let repTag = null;
        if (mode === 'EXCHANGE_SAME' && sibInput && sibInput.value) {
            repTag = sibInput.dataset.scannedTag || sibInput.dataset.qr || null;
        }

        const tags = getRowPieceTags(parseInt(idx));
        const tagsStr = (tags && tags.length > 0) ? tags.join(', ') : (it.batch_lot_no || it.qr_code || (it.notes && it.notes.includes('QR:') ? it.notes.replace(/^QR:\s*/i, '').trim() : '') || '');

        // Auto-check the checkbox if they typed a quantity (fallback to make sure it's caught)
        if (qty > 0 && !cb.checked) {
            cb.checked = true;
        }

        items.push({
            selected: 1,
            purchase_item_id: it.id,
            variant_id: it.variant_id,
            quantity: qty,
            condition: condition,
            unit_price: it.unit_price,
            replacement_variant_id: repVariantId,
            replacement_piece_tag: repTag,
            piece_tags: tagsStr
        });
    });

    if (mode === 'EXCHANGE_SAME') {
        const missingSib = items.some(i => !i.replacement_variant_id);
        if (missingSib) {
            alert('Please scan a replacement product QR tag for all checked items.');
            return;
        }
    }

    const refundMode = document.getElementById('refundModeSelect').value;
    const cashAmt = parseFloat(document.getElementById('splitCashInput').value) || 0;
    const upiAmt = parseFloat(document.getElementById('splitUpiInput').value) || 0;
    
    if (refundMode === 'SPLIT') {
        const diffText = document.getElementById('differenceBadge').innerText;
        let requiredAmount = Math.abs(parseFloat(diffText.replace(/[^0-9.-]+/g, '')) || 0);
        if (Math.abs((cashAmt + upiAmt) - requiredAmount) > 0.01) {
            alert('Split Payment Total does not match the Net Settlement Amount! Please click Auto Balance or adjust the amounts.');
            return;
        }
    }

    let paymentScreenshotBase64 = null;
    const fileInput = document.getElementById('paymentScreenshotInput');
    if (fileInput && fileInput.files.length > 0) {
        const file = fileInput.files[0];
        try {
            paymentScreenshotBase64 = await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => resolve(reader.result);
                reader.onerror = error => reject(error);
            });
        } catch (err) {
            console.error("Error reading file:", err);
            alert("Failed to read the payment screenshot file.");
            return;
        }
    }

    const payload = {
        purchase_order_id: currentPurchaseData.purchase.id,
        warehouse_id: currentPurchaseData.purchase.warehouse_id || 1,
        action_type: mode,
        refund_mode: refundMode,
        settlement_mode: refundMode,
        split_cash_amount: cashAmt,
        split_upi_amount: upiAmt,
        split_upi_ref: document.getElementById('splitUpiRefInput').value.trim(),
        payment_screenshot_base64: paymentScreenshotBase64,
        reason: document.getElementById('returnReasonSelect').value,
        notes: document.getElementById('returnNotesInput').value.trim(),
        items: items,
        is_ajax: 1,
        _csrf_token: '<?= CSRF::token() ?>'
    };

    const btn = document.getElementById('btnConfirmSubmit');
    const spinner = document.getElementById('submitSpinner');
    btn.disabled = true;
    spinner.classList.remove('d-none');

    try {
        const response = await fetch('<?= url("warehouse/purchases/returns/process") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': '<?= CSRF::token() ?>'
            },
            body: JSON.stringify(payload)
        });

        const res = await response.json();
        if (res.success) {
            window.location.href = res.invoice_url;
        } else {
            alert('Error processing return: ' + (res.message || 'Unknown error'));
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    } catch (e) {
        alert('Network/Server error: ' + e.message);
        btn.disabled = false;
        spinner.classList.add('d-none');
    }
}
</script>
