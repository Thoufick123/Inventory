<?php
    $statusFullyReceived = ($allocation['status'] === 'RECEIVED' || $allocation['status'] === 'COMPLETED');
    $hasReceiptMismatch = false;
    if ($statusFullyReceived) {
        foreach ($items as $item) {
            $expected = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : $item['requested_qty']);
            $received = (int)($item['received_qty'] ?? $expected);
            $damaged = (int)($item['damaged_qty'] ?? 0);
            if ($received > $expected || $damaged > $received) {
                $hasReceiptMismatch = true;
                break;
            }
        }
    }
    $isFullyReceived = $statusFullyReceived && !$hasReceiptMismatch;
    $isPartiallyReceived = ($allocation['status'] === 'PARTIAL_RECEIVED') || $hasReceiptMismatch;
    $isReceived = $isFullyReceived;
    if ($statusFullyReceived) {
        foreach ($items as &$item) {
            if (empty($item['received_qty']) && empty($item['damaged_qty'])) {
                $item['received_qty'] = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : $item['requested_qty']);
                $item['damaged_qty'] = 0;
            }
        }
        unset($item);
    }
    $canInward = hasPermission('shop_inward', 'verify') || hasPermission('shop_inward', 'receive') || hasPermission('shop_inward', 'create');
    // Calculate actual total received, damaged, and missing quantities across all items
    $totalRecvCount = 0;
    $totalDamCount = 0;
    $totalMissCount = 0;
    foreach ($items as $it) {
        $exp = (int)($it['dispatched_qty'] > 0 ? $it['dispatched_qty'] : $it['requested_qty']);
        if ($isFullyReceived && empty($it['received_qty']) && empty($it['damaged_qty'])) {
            $r = $exp;
            $d = 0;
        } else {
            $r = (int)($it['received_qty'] ?? 0);
            $d = (int)($it['damaged_qty'] ?? 0);
        }
        $totalRecvCount += max(0, $r - $d);
        $totalDamCount += $d;
        $totalMissCount += max(0, $exp - $r);
    }
    $totalMatchPct = $totalExpected > 0 ? min(100, round((($totalRecvCount + $totalDamCount) / $totalExpected) * 100)) : 100;
?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-qr-code-scan me-2 text-primary"></i>Cross-Check Shop Inward #<?= e($allocation['allocation_number']) ?></h2>
        <span class="text-muted">Compare Outward Dispatch vs Inward Product Scans, Identify Missing QRs &amp; Update Shop Stock</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('shop/inventory') ?>?shop_id=<?= (int)($shop['id'] ?? 0) ?>" class="btn btn-success">
            <i class="bi bi-box-seam me-1"></i> View <?= e($shop['name'] ?? 'Destination') ?> Inventory
        </a>
        <a href="<?= url('shop/inward') ?>?shop_id=<?= (int)($shop['id'] ?? 0) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Inward Queue
        </a>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. LIVE SUMMARY METRIC CARDS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Outward Dispatched</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1" id="statExpected"><?= number_format($totalExpected) ?> <small class="fs-6 text-muted">pcs</small></h3>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
                        <i class="bi bi-truck fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Inward Scanned / Recv</span>
                        <h3 class="mb-0 fw-bold text-success mt-1" id="statScanned"><?= number_format($totalRecvCount) ?> <small class="fs-6 text-muted">pcs</small><?= $totalDamCount > 0 ? ' <span class="text-danger fs-6 fw-normal">(' . $totalDamCount . ' dmg)</span>' : '' ?></h3>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success">
                        <i class="bi bi-upc-scan fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Missing / Shortage</span>
                        <h3 class="mb-0 fw-bold text-danger mt-1" id="statMissing"><?= number_format($totalMissCount) ?> <small class="fs-6 text-muted">pcs</small></h3>
                    </div>
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 text-danger">
                        <i class="bi bi-exclamation-triangle fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Verification Match</span>
                        <h3 class="mb-0 fw-bold text-info mt-1" id="statProgressPct"><?= $totalMatchPct ?>%</h3>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 text-info">
                        <i class="bi bi-check2-circle fs-3"></i>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                    <div id="statProgressBar" class="progress-bar <?= $totalMatchPct >= 100 ? 'bg-success' : 'bg-info' ?>" role="progressbar" style="width: <?= $totalMatchPct ?>%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- LEFT COLUMN: Outward Dispatch Info & QR Widget -->
    <div class="col-lg-4">
        <!-- Scannable Master Carton QR Card -->
        <div class="card border-0 shadow-sm mb-4 text-center">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-qr-code me-2 text-primary"></i>Consignment Master QR Code</h6>
            </div>
            <div class="card-body p-4">
                <div id="batchQrView" class="d-flex justify-content-center mb-3"></div>
                <div class="font-monospace fw-bold text-primary fs-5"><?= e($allocation['allocation_number']) ?></div>
                <small class="text-muted d-block mt-1">Scan this master QR to instantly auto-verify all items in this consignment.</small>
            </div>
        </div>

        <!-- Outward Dispatch & Gate Pass Metadata -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Dispatch &amp; Transfer Details</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Gate Pass / Outward #:</span>
                        <span class="fw-bold text-primary font-monospace"><?= e($outward['outward_number'] ?? 'OUT-PENDING') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Allocation #:</span>
                        <span class="fw-bold font-monospace"><?= e($allocation['allocation_number']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Source Warehouse:</span>
                        <span class="badge bg-secondary"><?= e($warehouse['name'] ?? 'Central Warehouse') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Destination Shop:</span>
                        <span class="badge bg-primary"><?= e($shop['name'] ?? 'Retail Store') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Carton Box Count:</span>
                        <span class="badge bg-light text-dark border fs-6"><?= number_format($outward['total_boxes'] ?? 1) ?> Boxes</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Vehicle / Carrier:</span>
                        <span class="fw-semibold"><?= e($outward['vehicle_number'] ?? 'Retail Delivery Van') ?></span>
                    </li>
                    <?php if (!empty($outward['driver_name'])): ?>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Driver:</span>
                            <span><?= e($outward['driver_name']) ?> <?= !empty($outward['driver_contact']) ? '(' . e($outward['driver_contact']) . ')' : '' ?></span>
                        </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Dispatch Date:</span>
                        <span><?= !empty($outward['dispatch_date']) ? date('d M Y, h:i A', strtotime($outward['dispatch_date'])) : date('d M Y', strtotime($allocation['created_at'])) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Current Status:</span>
                        <?php if ($isFullyReceived): ?>
                            <span class="badge bg-success"><i class="bi bi-check2-all me-1"></i>RECEIVED &amp; STOCKED (100%)</span>
                        <?php elseif ($isPartiallyReceived): ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-pie-chart me-1"></i>PARTIAL INWARD (<?= number_format($totalRecvCount) ?> good + <?= number_format($totalDamCount) ?> damaged / <?= number_format($totalExpected) ?> pcs)</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>DISPATCHED (Awaiting Inward)</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Cross-Check Matrix -->
    <div class="col-lg-8">
        <?php if (!$isFullyReceived && $canInward): ?>
            <!-- RESTORED SCAN DRAFT ALERT BANNER -->
            <div id="draftRestoredBanner" class="alert alert-info alert-dismissible fade show d-none mb-4 border-0 shadow-sm" role="alert">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-info bg-opacity-25 p-2.5 text-info">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-check text-info me-1"></i>In-Progress Scan Progress Restored</h6>
                            <span class="text-muted small">
                                <span id="draftScannedCountText" class="fw-bold text-primary">0 pcs</span> scanned in draft were preserved from your previous session. You can safely switch sections anytime.
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-danger shadow-xs" onclick="clearInwardDraft(true);" title="Clear all saved progress and reset counts to 0">
                            <i class="bi bi-trash me-1"></i> Clear Draft &amp; Reset
                        </button>
                        <button type="button" class="btn-close position-static p-2" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- CROSS-CHECK MATRIX TABLE & FORM -->
        <form method="POST" action="<?= url('shop/inward/verify/' . $allocation['id']) ?>" id="inwardForm">
            <?= CSRF::input() ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Inward Verification &amp; Outward Cross-Check
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-compact align-middle mb-0" id="matrixTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45px;">S.NO</th>
                                    <th style="min-width: 210px;">Product &amp; SKU</th>
                                    <th style="min-width: 155px;">QR Range &amp; Next Tag</th>
                                    <th style="min-width: 120px;">Size &amp; Color</th>
                                    <th class="text-end" style="width: 95px;">Outward Exp</th>
                                    <th class="text-center" style="min-width: 160px;">Good / Damaged</th>
                                    <th class="text-center">Missing Qty</th>
                                    <th class="text-center">Difference</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width: 90px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $globalSeqCounter = 1;
                                    foreach ($items as $idx => $item): 
                                        $vid = $item['variant_id'];
                                        $expected = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : $item['requested_qty']);
                                        if ($isFullyReceived && empty($item['received_qty']) && empty($item['damaged_qty'])) {
                                            $initReceived = $expected;
                                            $initDamaged = 0;
                                        } else {
                                            $initDamaged = (int)($item['damaged_qty'] ?? 0);
                                            $initReceived = max(0, (int)($item['received_qty'] ?? 0) - $initDamaged);
                                        }
                                        $initDiff = $initReceived + $initDamaged - $expected;

                                        $itemStartSeq = $globalSeqCounter;
                                        $itemEndSeq = $globalSeqCounter + max(1, $expected) - 1;
                                        $globalSeqCounter += max(1, $expected);

                                        $isSeries = !empty($item['is_series']) || 
                                                    ($item['qr_type'] ?? '') === 'SERIES_BATCH' || 
                                                    strtoupper($item['category_type'] ?? '') === 'SERIES' || 
                                                    (int)($item['pack_quantity'] ?? 0) > 1 || 
                                                    !empty($item['is_complementary']);

                                        $seriesQr = !empty($item['series_qr_code']) 
                                            ? $item['series_qr_code'] 
                                            : (!empty($item['saved_tags'][0]) 
                                                ? $item['saved_tags'][0] 
                                                : (!empty($item['selected_qr_codes']) && !str_starts_with($item['selected_qr_codes'], '[') ? trim(explode(',', $item['selected_qr_codes'])[0]) : (!empty($item['series_start_code']) ? $item['series_start_code'] : '')));

                                        if ($isSeries && !empty($seriesQr)) {
                                            $tagRange = $seriesQr;
                                            $tagStart = $seriesQr;
                                        } else if (!empty($item['saved_tags'])) {
                                            $firstTag = $item['saved_tags'][0];
                                            $lastTag = end($item['saved_tags']);
                                            $tagRange = count($item['saved_tags']) > 1 ? ($firstTag . ' - ' . $lastTag) : $firstTag;
                                            $tagStart = $firstTag;
                                        } else {
                                            $c1 = !empty($item['category_name']) ? trim($item['category_name']) : (!empty($item['category_code']) ? trim($item['category_code']) : '');
                                            $c2 = !empty($item['subcategory_name']) ? trim($item['subcategory_name']) : (!empty($item['subcategory_code']) ? trim($item['subcategory_code']) : '');
                                            $c3 = !empty($item['sub_subcategory_name']) ? trim($item['sub_subcategory_name']) : (!empty($item['sub_subcategory_code']) ? trim($item['sub_subcategory_code']) : '');

                                            $prefix = '';
                                            if ($c1 !== '') $prefix .= strtoupper($c1[0]);
                                            if ($c2 !== '') $prefix .= strtoupper($c2[0]);
                                            if ($c3 !== '') $prefix .= strtoupper($c3[0]);

                                            if (strlen($prefix) < 2) {
                                                if (!empty($item['qr_code_prefix'])) {
                                                    $clean = preg_replace('/[^A-Za-z0-9]/', '', $item['qr_code_prefix']);
                                                    $prefix = strtoupper(substr($clean, 0, 3));
                                                }
                                            }
                                            if (empty($prefix)) $prefix = 'TTS';
                                            $monthStr = sprintf('%02d', date('n'));
                                            $yearStr = date('y');
                                            $tagStart = $prefix . $monthStr . $yearStr . sprintf('%02d', $itemStartSeq);
                                            $tagEnd = $prefix . $monthStr . $yearStr . sprintf('%02d', $itemEndSeq);
                                            $tagRange = $expected > 1 ? ($tagStart . ' - ' . $tagEnd) : $tagStart;
                                        }
                                    ?>
                                    <tr id="row-<?= $vid ?>" data-variant-id="<?= $vid ?>" data-sku="<?= e($item['sku']) ?>" data-product="<?= e($item['product_name']) ?>" data-qr="<?= e($tagStart) ?>" data-expected="<?= $expected ?>" data-is-series="<?= $isSeries ? '1' : '0' ?>" data-series-qr="<?= e($seriesQr) ?>">
                                        <td><?= $idx + 1 ?></td>
                                        <td>
                                            <span class="fw-bold font-monospace text-primary d-block"><?= e($item['sku']) ?></span>
                                            <small class="text-dark fw-semibold d-block"><?= e($item['product_name']) ?></small>
                                            <?php if (!empty($item['category_name'])): ?>
                                                <small class="text-muted d-block"><?= e($item['category_name']) ?></small>
                                            <?php endif; ?>
                                            <?php if (!$isSeries && isset($item['outward_dispatched_qty']) && $item['outward_dispatched_qty'] > $expected): ?>
                                                <small class="text-warning d-block mt-1">
                                                    Outward quantity <?= number_format($item['outward_dispatched_qty']) ?>, but only <?= number_format(count($item['saved_tags'])) ?> QR tag(s) recorded. Inward is limited to the recorded tags.
                                                </small>
                                            <?php endif; ?>
                                            <?php if ((int)($item['received_qty'] ?? 0) > $expected): ?>
                                                <small class="text-danger d-block mt-1">
                                                    Existing shop receipt exceeds the recorded outward QR count. Correct the received quantity to reconcile stock.
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small" id="qr-tags-<?= $vid ?>" data-default-tag="<?= e($tagRange) ?>">
                                            <?php if ($isSeries && !empty($seriesQr)): ?>
                                                <span class="badge bg-primary text-white font-monospace"><i class="bi bi-box-seam me-1"></i><?= e($seriesQr) ?></span>
                                                <small class="text-muted d-block mt-1">Series pack (<?= $expected ?> pcs)</small>
                                            <?php else: ?>
                                                <span class="badge bg-dark text-white font-monospace"><i class="bi bi-qr-code me-1 text-warning"></i><?= e($tagRange) ?></span>
                                                <small class="text-muted d-block mt-1">Next: <strong class="text-danger"><?= e($tagStart) ?></strong></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= e($item['size_name'] ?? 'Free') ?></span>
                                            <span class="ms-1 small"><?= e($item['color_name'] ?? 'Std') ?></span>
                                        </td>
                                        <td class="text-end fw-bold fs-6">
                                            <?= number_format($expected) ?> <small class="text-muted">pcs</small>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!$isFullyReceived && $canInward): ?>
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <div>
                                                        <small class="text-success fw-semibold d-block">Good</small>
                                                        <input type="text" inputmode="none" readonly
                                                               class="form-control form-control-sm text-center fw-bold text-success item-received-input bg-light"
                                                               style="width: 62px;"
                                                               id="input-recv-<?= $vid ?>"
                                                               value="<?= $initReceived ?>" aria-label="Good received quantity (read-only)" data-variant-id="<?= $vid ?>">
                                                    </div>
                                                    <div>
                                                        <small class="text-danger fw-semibold d-block">Damaged</small>
                                                        <input type="text" inputmode="none" readonly name="items[<?= $vid ?>][damaged_qty]"
                                                               class="form-control form-control-sm text-center fw-bold text-danger item-damaged-input bg-light"
                                                               style="width: 62px;"
                                                               id="input-dmg-<?= $vid ?>"
                                                               value="<?= $initDamaged ?>" aria-label="Damaged quantity (read-only)" data-variant-id="<?= $vid ?>">
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="fw-bold text-success"><?= number_format($initReceived) ?></span>
                                                <span class="text-muted mx-1">/</span>
                                                <span class="<?= $initDamaged > 0 ? 'fw-bold text-danger' : 'text-muted' ?>"><?= number_format($initDamaged) ?></span>
                                                <small class="text-muted d-block">good / damaged</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold text-danger" id="missing-qty-<?= $vid ?>">
                                            <?= number_format(max(0, $expected - $initReceived - $initDamaged)) ?> pcs
                                        </td>
                                        <td class="text-center">
                                            <span id="diff-badge-<?= $vid ?>" class="badge <?= $initDiff === 0 ? 'bg-success' : ($initDiff < 0 ? 'bg-danger' : 'bg-primary') ?> fs-6 font-monospace">
                                                <?= $initDiff > 0 ? "+$initDiff" : "$initDiff" ?> pcs
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span id="status-badge-<?= $vid ?>" class="badge <?= $initDiff === 0 ? ($initDamaged > 0 ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-success-subtle text-success border border-success') : 'bg-danger-subtle text-danger border border-danger' ?>">
                                                <?= $initDiff === 0 ? ($initDamaged > 0 ? "MATCHED ({$initDamaged} DMG)" : 'MATCHED') : "SHORTAGE (" . abs($initDiff) . ")" ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 fw-semibold shadow-sm" onclick="openMissingProductModal(<?= $vid ?>, 'all')" title="View Complete Product Details &amp; All Individual Piece QR Codes" aria-label="View product details and QR codes">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (!$isFullyReceived && $canInward): ?>
                    <div class="card-footer bg-light p-3">
                        <div class="d-flex align-items-center justify-content-end">
                            <button type="button" class="btn btn-success px-4 py-2 fw-bold shadow-sm fs-6" id="btnCreditScannedOnly" onclick="creditScannedOnly();">
                                <i class="bi bi-box-arrow-in-down me-2"></i> Process Scanned Qty (<span id="btnCreditScannedCount"><?= $totalRecvCount + $totalDamCount ?></span> pcs)
                            </button>
                        </div>
                    </div>
                <?php elseif (!$isFullyReceived): ?>
                    <div class="card-footer bg-warning bg-opacity-10 text-warning-emphasis p-3 text-center fw-semibold">
                        <i class="bi bi-hourglass-split me-2"></i>Inward Consignment awaiting verification and shop stock credit by authorized staff.
                    </div>
                <?php else: ?>
                    <div class="card-footer bg-success-subtle text-success p-3 text-center fw-semibold">
                        <i class="bi bi-check-circle-fill me-2"></i>Allocation Inward Completed (<?= $totalRecvCount ?> pcs received, <?= $totalDamCount ?> pcs damaged). Stock balances credited to Shop Inventory.
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($canInward): ?>
<!-- ========================================================================= -->
<!-- MODAL: CAMERA QR SCANNER -->
<!-- ========================================================================= -->
<div class="modal fade" id="cameraScanModal" tabindex="-1" aria-labelledby="cameraScanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="cameraScanModalLabel"><i class="bi bi-camera me-2"></i>Live Camera QR Scanner</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnCloseCam"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="reader" style="width: 100%; min-height: 280px; border-radius: 8px; overflow: hidden; background: #000;"></div>
                <small class="text-muted d-block mt-3">Position the Consignment, SKU, or Piece QR code within the camera frame.</small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
#modalMissingPiecesGrid {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f8fafc;
}
#modalMissingPiecesGrid::-webkit-scrollbar {
    width: 6px;
}
#modalMissingPiecesGrid::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 4px;
}
#modalMissingPiecesGrid::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
#modalMissingPiecesGrid::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.piece-card-item {
    transition: all 0.15s ease-in-out;
}
.piece-card-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06) !important;
}
</style>

<!-- ========================================================================= -->
<!-- MODAL: MISSING PRODUCT DETAILS & ALL PIECE QR CODES SELECTION OPTIONS -->
<!-- ========================================================================= -->
<div class="modal fade" id="missingProductModal" tabindex="-1" aria-labelledby="missingProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white py-3 px-4">
                <h5 class="modal-title fw-bold" id="missingProductModalLabel">
                    <i class="bi bi-qr-code-scan me-2"></i>Product Details &amp; Individual Piece QR Codes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Product Overview Banner -->
                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border mb-4">
                    <div id="modalProductImgContainer">
                        <!-- Image or fallback icon -->
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="fw-bold mb-0 text-dark" id="modalProductName">-</h5>
                            <span class="badge bg-primary-subtle text-primary border" id="modalProductCategory">-</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                            <span class="badge bg-dark font-monospace text-white px-2 py-1" id="modalProductSku">-</span>
                            <span class="badge bg-light text-dark border px-2 py-1">Size: <strong id="modalProductSize">-</strong></span>
                            <span class="badge bg-light text-dark border px-2 py-1">Color: <strong id="modalProductColor">-</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Metrics Counter -->
                <div class="row g-3 mb-4 text-center">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 border shadow-2xs h-100">
                            <small class="text-muted fw-semibold text-uppercase d-block fs-8">Expected Outward</small>
                            <h4 class="fw-bold text-dark mb-0 mt-1" id="modalExpectedQty">0</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-25 shadow-2xs h-100">
                            <small class="text-success fw-semibold text-uppercase d-block fs-8">Scanned Inward</small>
                            <h4 class="fw-bold text-success mb-0 mt-1" id="modalScannedQty">0</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-danger bg-opacity-10 rounded-3 border border-danger border-opacity-25 shadow-2xs h-100">
                            <small class="text-danger fw-semibold text-uppercase d-block fs-8">Damaged / Defect</small>
                            <h4 class="fw-bold text-danger mb-0 mt-1" id="modalDamagedQty">0</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-warning bg-opacity-15 rounded-3 border border-warning border-opacity-50 shadow-2xs h-100">
                            <small class="text-warning-emphasis fw-semibold text-uppercase d-block fs-8">Missing / Pending</small>
                            <h4 class="fw-bold text-danger mb-0 mt-1" id="modalMissingQty">0</h4>
                        </div>
                    </div>
                </div>

                <?php if ($canInward): ?>
                <!-- QR Display & Action Box -->
                <div class="row g-4 align-items-center mb-4 p-3 bg-light bg-opacity-50 rounded-3 border">
                    <div class="col-md-5 text-center">
                        <div class="p-2.5 bg-white rounded-3 border shadow-sm d-inline-block">
                            <div id="modalQrCanvas" class="d-flex justify-content-center"></div>
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark border font-monospace px-3 py-2 fs-6 d-inline-block" id="modalQrTag">
                                -
                            </span>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-upc-scan text-primary me-2"></i>Instant Missing Piece Reconciliation</h6>
                        <p class="text-muted small mb-3">
                            Choose the condition, then scan an individual piece QR or barcode to record it immediately.
                        </p>
                        <div class="d-flex flex-column gap-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-upc-scan text-primary"></i></span>
                                <input type="text" class="form-control font-monospace" id="modalPieceQrInput" placeholder="Scan piece QR / barcode" aria-label="Scan piece QR code or barcode" autocomplete="off">
                                <button type="button" class="btn btn-outline-primary" id="btnModalPieceQrScan">Scan QR</button>
                            </div>
                            <small id="qrScanInstruction" class="text-muted" aria-live="polite">Press Enter or Scan QR to record the scanned piece using the selected condition.</small>
                            <div class="btn-group btn-group-sm align-self-start" role="group" aria-label="Product condition">
                                <input type="radio" class="btn-check" name="modalScanModeToggle" id="modalModeGood" value="good" autocomplete="off" checked onchange="setScanMode('good')">
                                <label class="btn btn-outline-success fw-semibold" for="modalModeGood"><i class="bi bi-check-circle-fill me-1"></i>Good / Intact</label>
                                <input type="radio" class="btn-check" name="modalScanModeToggle" id="modalModeDamage" value="damage" autocomplete="off" onchange="setScanMode('damage')">
                                <label class="btn btn-outline-danger fw-semibold" for="modalModeDamage"><i class="bi bi-exclamation-octagon-fill me-1"></i>Damaged / Defect</label>
                            </div>
                            <button type="button" class="btn btn-success fw-semibold py-2.5 shadow-xs" id="btnModalScanPlusOne">
                                <i class="bi bi-check-circle me-1" id="modalScanModeIcon"></i><span id="modalScanModeLabel">Scan +1 Good Piece</span> (<span id="modalBtnScanText">1 pc</span>)
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Mismatched Pieces Individual Card Grid with Filter Options -->
                <div class="border-top pt-3" id="modalPiecesSection">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0 fs-6" id="modalPiecesSectionTitle">
                                <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Mismatched / Missing Pieces (<span id="modalMissingPiecesCount">0</span> pending)
                            </h6>
                            <small class="text-muted" id="modalPiecesSubtitle">Click any piece to verify or un-verify</small>
                        </div>
                        <!-- Filter Pill Controls: Only Mismatched vs All Pieces vs Verified vs Damaged -->
                        <div class="btn-group btn-group-sm shadow-xs" role="group" aria-label="Piece Filter">
                            <button type="button" class="btn btn-danger btn-sm active fw-semibold px-2.5" id="btnFilterMismatched" onclick="setModalPieceFilter('mismatched')" title="Show only unverified / mismatched pieces">
                                <i class="bi bi-exclamation-circle me-1"></i> Pending (<span id="filterCountMismatched">0</span>)
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold px-2.5" id="btnFilterAll" onclick="setModalPieceFilter('all')" title="Show all pieces for this product">
                                <i class="bi bi-grid-3x3 me-1"></i> All (<span id="filterCountAll">0</span>)
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm fw-semibold px-2.5" id="btnFilterVerified" onclick="setModalPieceFilter('verified')" title="Show only intact verified pieces">
                                <i class="bi bi-check2-circle me-1"></i> Good (<span id="filterCountVerified">0</span>)
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm fw-semibold px-2.5" id="btnFilterDamaged" onclick="setModalPieceFilter('damaged')" title="Show only damaged pieces">
                                <i class="bi bi-slash-circle me-1"></i> Damaged (<span id="filterCountDamaged">0</span>)
                            </button>
                        </div>
                    </div>
                    <div class="row g-2.5 overflow-auto p-1" style="max-height: 380px; min-height: 220px; overflow-y: auto; overflow-x: hidden;" id="modalMissingPiecesGrid">
                        <!-- Populated by JS based on filter -->
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4">
                <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Load QRCode & Camera Scanner Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
    // In-memory data structures
    const ALLOCATION_ID = <?= (int)$allocation['id'] ?>;
    const ALLOCATION_NUMBER = <?= json_encode($allocation['allocation_number']) ?>;
    const OUTWARD_NUMBER = <?= json_encode($outward['outward_number'] ?? '') ?>;
    const TOTAL_EXPECTED = <?= (int)$totalExpected ?>;
    const ALLOCATION_STATUS = <?= json_encode($allocation['status']) ?>;
    const IS_FULLY_RECEIVED = <?= $isFullyReceived ? 'true' : 'false' ?>;
    const IS_RECEIVED = IS_FULLY_RECEIVED;
    const ITEMS = <?= json_encode($items) ?>;
    const PIECE_QRS = <?= json_encode($pieceQRs ?? []) ?>;
    const SAVED_SERVER_DRAFT = <?= json_encode($savedDraft ?? null) ?>;
    const DRAFT_STORAGE_KEY = 'grm_shop_inward_draft_' + ALLOCATION_ID;
    let draftDebounceTimer = null;

    let activeModalVariantId = null;
    const SCANNED_TAGS = new Set();
    const DAMAGED_TAGS = new Set();
    let currentScanMode = 'good'; // 'good' or 'damage'

    function setScanMode(mode) {
        currentScanMode = mode;
        const goodMode = document.getElementById('modalModeGood');
        const damageMode = document.getElementById('modalModeDamage');
        if (goodMode) goodMode.checked = mode === 'good';
        if (damageMode) damageMode.checked = mode === 'damage';

        const scanButton = document.getElementById('btnModalScanPlusOne');
        const scanButtonIcon = document.getElementById('modalScanModeIcon');
        const scanButtonLabel = document.getElementById('modalScanModeLabel');
        if (scanButton) {
            scanButton.className = `btn btn-${mode === 'damage' ? 'danger' : 'success'} fw-semibold py-2.5 shadow-xs`;
        }
        if (scanButtonIcon) {
            scanButtonIcon.className = `bi ${mode === 'damage' ? 'bi-slash-circle' : 'bi-check-circle'} me-1`;
        }
        if (scanButtonLabel) {
            scanButtonLabel.textContent = mode === 'damage' ? 'Mark +1 Damaged Piece' : 'Scan +1 Good Piece';
        }

        const liveInput = document.getElementById('liveScannerInput');
        if (liveInput) {
            if (mode === 'damage') {
                liveInput.placeholder = "Scan DAMAGED product QR / barcode...";
                liveInput.classList.add('border-danger', 'bg-danger-subtle');
            } else {
                liveInput.placeholder = "Scan GOOD product QR / barcode...";
                liveInput.classList.remove('border-danger', 'bg-danger-subtle');
            }
        }
    }

    // Map saved piece QR codes by variant_id
    const SAVED_PIECE_TAGS = {};
    if (PIECE_QRS && PIECE_QRS.length > 0) {
        PIECE_QRS.forEach(p => {
            const vid = String(p.variant_id);
            if (!SAVED_PIECE_TAGS[vid]) SAVED_PIECE_TAGS[vid] = [];
            SAVED_PIECE_TAGS[vid].push(p.tag_code);
        });
    }

    // Save current scanned pieces and quantities into persistent localStorage & session
    function saveInwardDraft() {
        if (IS_FULLY_RECEIVED) return; // Do not save if already marked 100% RECEIVED / COMPLETED

        const draftData = {
            allocationId: ALLOCATION_ID,
            allocationNumber: ALLOCATION_NUMBER,
            scannedTags: Array.from(SCANNED_TAGS),
            damagedTags: Array.from(DAMAGED_TAGS),
            receivedMap: {},
            damagedMap: {},
            timestamp: Date.now(),
            savedAtFormatted: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })
        };

        ITEMS.forEach(it => {
            const vid = String(it.variant_id);
            const recvInput = document.getElementById(`input-recv-${vid}`);
            const dmgInput = document.getElementById(`input-dmg-${vid}`);
            draftData.receivedMap[vid] = recvInput ? (parseInt(recvInput.value) || 0) : 0;
            draftData.damagedMap[vid] = dmgInput ? (parseInt(dmgInput.value) || 0) : 0;
        });

        // 1. Instant client localStorage persistence
        try {
            localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(draftData));
        } catch(e) {
            console.warn("LocalStorage save error:", e);
        }

        // 2. Visual Save Status Pill
        const saveIndicator = document.getElementById('draftSaveIndicator');
        const saveText = document.getElementById('draftSaveText');
        if (saveIndicator && saveText) {
            saveIndicator.classList.remove('d-none');
            saveText.textContent = `Auto-saved ${draftData.savedAtFormatted}`;
        }

        // 3. Debounced AJAX persistence to server session
        clearTimeout(draftDebounceTimer);
        draftDebounceTimer = setTimeout(() => {
            const csrfToken = document.querySelector('input[name="_csrf_token"]')?.value || '';
            const formData = new FormData();
            formData.append('_csrf_token', csrfToken);
            formData.append('allocation_id', ALLOCATION_ID);
            formData.append('scanned_tags', JSON.stringify(draftData.scannedTags));
            formData.append('received_map', JSON.stringify(draftData.receivedMap));
            formData.append('damaged_map', JSON.stringify(draftData.damagedMap));
            formData.append('notes', draftData.notes);

            fetch('<?= url("api/shop/inward/save-draft") ?>', {
                method: 'POST',
                body: formData
            }).catch(err => console.warn("Server draft sync notice:", err));
        }, 800);
    }

    // Load and restore scanned pieces on page open
    function loadInwardDraft() {
        if (IS_RECEIVED) {
            try { localStorage.removeItem(DRAFT_STORAGE_KEY); } catch(e) {}
            return;
        }

        let draft = null;
        try {
            const raw = localStorage.getItem(DRAFT_STORAGE_KEY);
            if (raw) {
                draft = JSON.parse(raw);
            }
        } catch(e) {
            console.warn("Draft parse error:", e);
        }

        // Fallback to server draft if localStorage is empty
        if (!draft && SAVED_SERVER_DRAFT) {
            draft = {
                allocationId: SAVED_SERVER_DRAFT.allocation_id,
                scannedTags: SAVED_SERVER_DRAFT.scanned_tags || [],
                receivedMap: SAVED_SERVER_DRAFT.received_map || {},
                damagedMap: SAVED_SERVER_DRAFT.damaged_map || {},
                notes: SAVED_SERVER_DRAFT.notes || '',
                timestamp: SAVED_SERVER_DRAFT.timestamp || Date.now()
            };
        }

        if (!draft) return;

        let hasScannedData = false;
        let totalRestoredPieces = 0;

        // Restore SCANNED_TAGS and DAMAGED_TAGS
        if (Array.isArray(draft.scannedTags) && draft.scannedTags.length > 0) {
            draft.scannedTags.forEach(tag => {
                if (tag) SCANNED_TAGS.add(String(tag).toUpperCase());
            });
            hasScannedData = true;
        }
        if (Array.isArray(draft.damagedTags) && draft.damagedTags.length > 0) {
            draft.damagedTags.forEach(tag => {
                if (tag) {
                    const cleanTag = String(tag).toUpperCase();
                    DAMAGED_TAGS.add(cleanTag);
                    SCANNED_TAGS.delete(cleanTag);
                }
            });
            hasScannedData = true;
        }

        // Restore numeric inputs for each variant
        ITEMS.forEach(it => {
            const vid = String(it.variant_id);
            const recvInput = document.getElementById(`input-recv-${vid}`);
            const dmgInput = document.getElementById(`input-dmg-${vid}`);

            if (draft.receivedMap && draft.receivedMap[vid] !== undefined) {
                const qty = parseInt(draft.receivedMap[vid]) || 0;
                if (recvInput) recvInput.value = qty;
                totalRestoredPieces += qty;
                if (qty > 0) hasScannedData = true;
            }

            if (draft.damagedMap && draft.damagedMap[vid] !== undefined) {
                const dmg = parseInt(draft.damagedMap[vid]) || 0;
                if (dmgInput) dmgInput.value = dmg;
                if (dmg > 0) hasScannedData = true;
            }

            syncVariantTagsWithInput(it);
        });

        if (hasScannedData) {
            recalculateMatrix();

            // Display Draft Restored Banner
            const banner = document.getElementById('draftRestoredBanner');
            const countText = document.getElementById('draftScannedCountText');
            if (banner) {
                banner.classList.remove('d-none');
            }
            if (countText) {
                const dmgInfo = DAMAGED_TAGS.size > 0 ? ` + ${DAMAGED_TAGS.size} damaged` : '';
                countText.textContent = `${totalRestoredPieces} pcs (${SCANNED_TAGS.size} verified${dmgInfo})`;
            }

            const saveIndicator = document.getElementById('draftSaveIndicator');
            if (saveIndicator) {
                saveIndicator.classList.remove('d-none');
                const text = document.getElementById('draftSaveText');
                if (text) text.textContent = 'Progress Preserved & Restored';
            }
        }
    }

    // Clear saved draft from localStorage & server
    function clearInwardDraft(resetInputs = false) {
        try {
            localStorage.removeItem(DRAFT_STORAGE_KEY);
        } catch(e) {}

        const banner = document.getElementById('draftRestoredBanner');
        if (banner) banner.classList.add('d-none');

        const saveIndicator = document.getElementById('draftSaveIndicator');
        if (saveIndicator) saveIndicator.classList.add('d-none');

        // Clear server session draft
        const csrfToken = document.querySelector('input[name="_csrf_token"]')?.value || '';
        const formData = new FormData();
        formData.append('_csrf_token', csrfToken);
        formData.append('allocation_id', ALLOCATION_ID);
        fetch('<?= url("api/shop/inward/clear-draft") ?>', {
            method: 'POST',
            body: formData
        }).catch(err => {});

        if (resetInputs) {
            SCANNED_TAGS.clear();
            DAMAGED_TAGS.clear();
            ITEMS.forEach(it => {
                const vid = String(it.variant_id);
                const recv = document.getElementById(`input-recv-${vid}`);
                const dmg = document.getElementById(`input-dmg-${vid}`);
                if (recv) recv.value = 0;
                if (dmg) dmg.value = 0;
            });
            recalculateMatrix();
            if (activeModalVariantId) {
                updateModalState(activeModalVariantId);
            }
            playBeep('double');
            showScanFeedback("↩️ In-progress scan draft cleared. Reset all quantities to 0.", "info");
        }
    }

    // Initialize global sequence ranges for multi-variant consignments
    let currentGlobalSeq = 1;
    ITEMS.forEach(it => {
        it.startSeq = currentGlobalSeq;
        const qty = parseInt(it.dispatched_qty > 0 ? it.dispatched_qty : it.requested_qty) || 1;
        it.endSeq = currentGlobalSeq + qty - 1;
        currentGlobalSeq += qty;

        // Detect Series Product
        const isSeries = Boolean(
            it.is_series ||
            it.qr_type === 'SERIES_BATCH' ||
            (it.category_type && String(it.category_type).toUpperCase() === 'SERIES') ||
            parseInt(it.pack_quantity) > 1 ||
            it.is_complementary
        );
        it.is_series = isSeries;

        let seriesQr = it.series_qr_code || '';
        if (!seriesQr && it.saved_tags && it.saved_tags.length > 0) {
            seriesQr = it.saved_tags[0];
        }
        if (!seriesQr && it.selected_qr_codes) {
            if (typeof it.selected_qr_codes === 'string' && !it.selected_qr_codes.startsWith('[')) {
                seriesQr = it.selected_qr_codes.split(',')[0].trim();
            }
        }
        if (!seriesQr && it.series_start_code) {
            seriesQr = it.series_start_code;
        }
        if (seriesQr) {
            it.series_qr_code = seriesQr.toUpperCase();
        }

        // Load saved piece tags from saved_tags or selected_qr_codes or saved pieces
        let savedTags = [];
        if (Array.isArray(it.saved_tags) && it.saved_tags.length > 0) {
            savedTags = it.saved_tags.map(t => String(t).toUpperCase());
        } else if (it.selected_qr_codes) {
            try {
                const parsed = typeof it.selected_qr_codes === 'string' ? JSON.parse(it.selected_qr_codes) : it.selected_qr_codes;
                if (Array.isArray(parsed)) {
                    savedTags = parsed.map(t => typeof t === 'object' ? (t.tag_code || t.qr_code || '') : String(t)).filter(t => t.length > 0);
                }
            } catch(e) {
                if (typeof it.selected_qr_codes === 'string') {
                    savedTags = it.selected_qr_codes.split(',').map(s => s.trim()).filter(s => s.length > 0);
                }
            }
        }
        if (savedTags.length === 0 && SAVED_PIECE_TAGS[String(it.variant_id)]) {
            savedTags = SAVED_PIECE_TAGS[String(it.variant_id)];
        }

        if (isSeries && it.series_qr_code) {
            savedTags = Array(qty).fill(it.series_qr_code);
            SAVED_PIECE_TAGS[String(it.variant_id)] = savedTags;
        } else if (savedTags.length > 0) {
            SAVED_PIECE_TAGS[String(it.variant_id)] = savedTags;
        }

        const dmgQty = parseInt(it.damaged_qty) || 0;
        const receivedTotal = parseInt(it.received_qty) || (IS_FULLY_RECEIVED ? qty : 0);
        const recvQty = Math.max(0, receivedTotal - dmgQty);

        if (isSeries && it.series_qr_code) {
            const sTag = it.series_qr_code;
            if (dmgQty > 0) {
                DAMAGED_TAGS.add(sTag);
            } else if (recvQty > 0) {
                SCANNED_TAGS.add(sTag);
            }
        } else if (savedTags.length > 0) {
            savedTags.forEach((tag, idx) => {
                const clean = String(tag).toUpperCase();
                if (idx >= (savedTags.length - dmgQty)) {
                    DAMAGED_TAGS.add(clean);
                } else if (idx < recvQty) {
                    SCANNED_TAGS.add(clean);
                }
            });
        } else if (IS_FULLY_RECEIVED) {
            for (let p = it.startSeq; p <= it.endSeq; p++) {
                SCANNED_TAGS.add(getLabelCode(p, it).toUpperCase());
            }
        } else {
            for (let s = 0; s < dmgQty; s++) {
                const dTag = getLabelCode(it.startSeq + s, it).toUpperCase();
                DAMAGED_TAGS.add(dTag);
            }
            const goodCount = recvQty;
            for (let g = 0; g < goodCount; g++) {
                const gTag = getLabelCode(it.startSeq + dmgQty + g, it).toUpperCase();
                SCANNED_TAGS.add(gTag);
            }
        }
    });

    // Synchronize SCANNED_TAGS and DAMAGED_TAGS sets with numeric quantity input values
    function syncVariantTagsWithInput(item) {
        const vid = item.variant_id;
        const recvInput = document.getElementById(`input-recv-${vid}`);
        const dmgInput = document.getElementById(`input-dmg-${vid}`);
        const expected = parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 0;
        const currentDmgQty = dmgInput ? (parseInt(dmgInput.value) || 0) : (parseInt(item.damaged_qty) || 0);
        const persistedTotal = parseInt(item.received_qty) || (IS_FULLY_RECEIVED ? expected : 0);
        const currentRecvQty = recvInput ? (parseInt(recvInput.value) || 0) : Math.max(0, persistedTotal - currentDmgQty);
        const validRecvQty = Math.max(0, Math.min(expected, currentRecvQty));
        const validDmgQty = Math.max(0, Math.min(expected - validRecvQty, currentDmgQty));

        if (item.is_series) {
            const seriesTag = (item.series_qr_code || getLabelCode(item.startSeq, item)).toUpperCase();
            if (validDmgQty > 0) {
                DAMAGED_TAGS.add(seriesTag);
                SCANNED_TAGS.delete(seriesTag);
            } else if (validRecvQty > 0) {
                SCANNED_TAGS.add(seriesTag);
                DAMAGED_TAGS.delete(seriesTag);
            } else {
                SCANNED_TAGS.delete(seriesTag);
                DAMAGED_TAGS.delete(seriesTag);
            }
            return;
        }

        let variantTags = [];
        for (let p = item.startSeq; p <= item.endSeq; p++) {
            variantTags.push(getLabelCode(p, item).toUpperCase());
        }

        // Sync damaged tags
        let damagedInSet = variantTags.filter(t => DAMAGED_TAGS.has(t));
        if (damagedInSet.length > validDmgQty) {
            for (let i = damagedInSet.length - 1; i >= validDmgQty; i--) {
                DAMAGED_TAGS.delete(damagedInSet[i]);
            }
        } else if (damagedInSet.length < validDmgQty) {
            for (let t of variantTags) {
                if (damagedInSet.length >= validDmgQty) break;
                if (!DAMAGED_TAGS.has(t) && !SCANNED_TAGS.has(t)) {
                    DAMAGED_TAGS.add(t);
                    damagedInSet.push(t);
                }
            }
        }

        // Sync verified good tags (excluding damaged tags)
        let verifiedInSet = variantTags.filter(t => SCANNED_TAGS.has(t) && !DAMAGED_TAGS.has(t));
        if (verifiedInSet.length > validRecvQty) {
            for (let i = verifiedInSet.length - 1; i >= validRecvQty; i--) {
                SCANNED_TAGS.delete(verifiedInSet[i]);
            }
        } else if (verifiedInSet.length < validRecvQty) {
            for (let t of variantTags) {
                if (verifiedInSet.length >= validRecvQty) break;
                if (!SCANNED_TAGS.has(t) && !DAMAGED_TAGS.has(t)) {
                    SCANNED_TAGS.add(t);
                    verifiedInSet.push(t);
                }
            }
        }
    }

    // Get count of verified tags in SCANNED_TAGS for a specific variant (excluding damaged)
    function getVariantVerifiedTagCount(item) {
        if (item.is_series) {
            const recvInput = document.getElementById(`input-recv-${item.variant_id}`);
            if (recvInput) return parseInt(recvInput.value) || 0;
            const seriesTag = (item.series_qr_code || getLabelCode(item.startSeq, item)).toUpperCase();
            const expected = parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 0;
            return SCANNED_TAGS.has(seriesTag) && !DAMAGED_TAGS.has(seriesTag) ? expected : 0;
        }
        let count = 0;
        for (let p = item.startSeq; p <= item.endSeq; p++) {
            const tag = getLabelCode(p, item).toUpperCase();
            if (SCANNED_TAGS.has(tag) && !DAMAGED_TAGS.has(tag)) count++;
        }
        return count;
    }

    // Get count of damaged tags for a specific variant
    function getVariantDamagedTagCount(item) {
        if (item.is_series) {
            const dmgInput = document.getElementById(`input-dmg-${item.variant_id}`);
            if (dmgInput) return parseInt(dmgInput.value) || 0;
            const seriesTag = (item.series_qr_code || getLabelCode(item.startSeq, item)).toUpperCase();
            const expected = parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 0;
            return DAMAGED_TAGS.has(seriesTag) ? expected : 0;
        }
        let count = 0;
        for (let p = item.startSeq; p <= item.endSeq; p++) {
            const tag = getLabelCode(p, item).toUpperCase();
            if (DAMAGED_TAGS.has(tag)) count++;
        }
        return count;
    }

    // Web Audio API Synthesizer for instant audible feedback
    let audioCtx = null;
    function getAudioContext() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    function playBeep(type = 'success') {
        try {
            const ctx = getAudioContext();
            if (!ctx) return;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);

            if (type === 'success') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
                osc.start();
                osc.stop(ctx.currentTime + 0.15);
            } else if (type === 'double') {
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(660, ctx.currentTime);
                osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.1);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
                osc.start();
                osc.stop(ctx.currentTime + 0.25);
            } else if (type === 'error') {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(220, ctx.currentTime);
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
                osc.start();
                osc.stop(ctx.currentTime + 0.35);
            }
        } catch (e) {
            console.log("Audio feedback error:", e);
        }
    }

    // Tag Code Generator matching warehouse format (e.g. GPF092601, MFK092601)
    function getCatalogAbbreviation(item) {
        // 1. Explicit QR Code Prefix (e.g. 'BSD', 'GPF')
        if (item && item.qr_code_prefix && String(item.qr_code_prefix).trim()) {
            const rawPrefix = String(item.qr_code_prefix).trim().replace(/[^A-Za-z0-9]/g, '');
            if (rawPrefix.length >= 2) return rawPrefix.slice(0, 4).toUpperCase();
        }

        // 2. Product Style Code Alpha Prefix (e.g. 'GPF-020' -> 'GPF', 'MFK-002' -> 'MFK', 'KPT-010' -> 'KPT', 'IOR-030' -> 'IOR', 'BCS-050' -> 'BCS', 'MBL-040' -> 'MBL')
        if (item && item.product_code) {
            const m = String(item.product_code).match(/^([A-Za-z]{2,5})/i);
            if (m) return m[1].toUpperCase();
        }

        // 3. Variant SKU Alpha Prefix (e.g. 'GPF020-36M-PNK' -> 'GPF')
        if (item && item.sku) {
            const m = String(item.sku).match(/^([A-Za-z]{2,5})/i);
            if (m) return m[1].toUpperCase();
        }

        // 4. Combined Category Codes (e.g. MOM + FK -> MFK, KIDS + POLO -> KPT)
        const c1 = (item && item.category_code ? String(item.category_code).trim() : '');
        const c2 = (item && item.subcategory_code ? String(item.subcategory_code).trim() : '');
        const c3 = (item && item.sub_subcategory_code ? String(item.sub_subcategory_code).trim() : '');
        if (c1 || c2 || c3) {
            const comb = (c1.slice(0, 1) + c2.slice(0, 1) + c3.slice(0, 1)).toUpperCase();
            if (comb.length >= 2) return comb;
        }

        // 5. Category Hierarchy Initials
        const n1 = (item && item.category_name ? String(item.category_name).trim() : '');
        const n2 = (item && item.subcategory_name ? String(item.subcategory_name).trim() : '');
        const n3 = (item && item.sub_subcategory_name ? String(item.sub_subcategory_name).trim() : '');
        let prefix = '';
        if (n1) prefix += n1.charAt(0).toUpperCase();
        if (n2) prefix += n2.charAt(0).toUpperCase();
        if (n3) prefix += n3.charAt(0).toUpperCase();
        if (prefix.length >= 2) return prefix.slice(0, 3);

        return 'GRM';
    }

    function getLabelCode(pieceIndex, item) {
        if (!item) return 'GRM01';
        if (item.is_series && (item.series_qr_code || item.series_start_code)) {
            return String(item.series_qr_code || item.series_start_code).toUpperCase();
        }
        const vid = String(item.variant_id);
        const itemRelativeIdx = pieceIndex - item.startSeq;
        if (SAVED_PIECE_TAGS[vid] && SAVED_PIECE_TAGS[vid][itemRelativeIdx]) {
            return String(SAVED_PIECE_TAGS[vid][itemRelativeIdx]).toUpperCase();
        }

        const abbr = getCatalogAbbreviation(item);
        const mfgDate = (item && (item.variant_mfg_date || item.mfg_date)) ? new Date(item.variant_mfg_date || item.mfg_date) : new Date();
        const now = isNaN(mfgDate.getTime()) ? new Date() : mfgDate;
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const year = String(now.getFullYear()).slice(-2);
        const seq = String(pieceIndex).padStart(2, '0');
        return `${abbr}${month}${year}${seq}`.toUpperCase();
    }

    function getQRPayload(pieceIndex, item) {
        return getLabelCode(pieceIndex, item).toUpperCase();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // 1. Render Master Carton QR Code
        const qrViewEl = document.getElementById("batchQrView");
        if (qrViewEl && typeof QRCode !== 'undefined') {
            try {
                new QRCode(qrViewEl, {
                    text: ALLOCATION_NUMBER,
                    width: 140,
                    height: 140,
                    colorDark : "#0f172a",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            } catch(e) {
                console.warn("QR render note:", e);
            }
        }

        // 2. Initialize live cross check engine
        initCrossCheckEngine();

        // 3. Load & restore any saved scan draft (preserved across page navigation)
        loadInwardDraft();

        // 4. Modal Action listeners
        const btnModalScan = document.getElementById('btnModalScanPlusOne');
        if (btnModalScan) {
            btnModalScan.addEventListener('click', function() {
                if (activeModalVariantId) {
                    if (currentScanMode === 'damage') {
                        markPieceDamage(activeModalVariantId);
                    } else {
                        fastScanVariant(activeModalVariantId);
                    }
                }
            });
        }

        // Auto refocus scanner input when modal closes
        const missingModal = document.getElementById('missingProductModal');
        if (missingModal) {
            missingModal.addEventListener('show.bs.modal', function() {
                setScanMode('good');
            });
            missingModal.addEventListener('shown.bs.modal', function() {
                document.getElementById('modalPieceQrInput')?.focus();
            });
            missingModal.addEventListener('hidden.bs.modal', function() {
                setScanMode('good');
                activeModalVariantId = null;
                const liveInput = document.getElementById('liveScannerInput');
                if (liveInput) liveInput.focus();
            });
        }

        const modalPieceQrInput = document.getElementById('modalPieceQrInput');
        const btnModalPieceQrScan = document.getElementById('btnModalPieceQrScan');
        if (modalPieceQrInput) {
            modalPieceQrInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    scanModalQr();
                }
            });
        }
        if (btnModalPieceQrScan) {
            btnModalPieceQrScan.addEventListener('click', function() {
                scanModalQr();
            });
        }

        initCameraScanner();
        setupGlobalScannerListener();
    });

    // Global USB / Bluetooth barcode scanner listener
    let globalScanBuffer = '';
    let lastKeyStrokeTime = Date.now();

    function setupGlobalScannerListener() {
        window.addEventListener('keydown', function(e) {
            const activeEl = document.activeElement;
            if (activeEl && activeEl.id === 'modalPieceQrInput') {
                if (e.key === 'Enter') globalScanBuffer = '';
                return;
            }
            const isEditingInput = activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA');
            const now = Date.now();
            const timeDiff = now - lastKeyStrokeTime;
            lastKeyStrokeTime = now;

            if (e.key === 'Enter') {
                if (globalScanBuffer.length >= 3) {
                    e.preventDefault();
                    const code = globalScanBuffer;
                    globalScanBuffer = '';
                    processScanInput(code);
                    return;
                }
            }

            if (e.key.length === 1) {
                if (timeDiff > 120) {
                    globalScanBuffer = '';
                }
                globalScanBuffer += e.key;
            }
        }, true);
    }

    function scanModalQr() {
        const qrInput = document.getElementById('modalPieceQrInput');
        const scanInstruction = document.getElementById('qrScanInstruction');
        const code = String(qrInput ? qrInput.value : '').trim();
        if (!code) {
            if (scanInstruction) {
                scanInstruction.textContent = 'Scan a piece QR code or barcode first.';
                scanInstruction.className = 'text-danger small';
            }
            if (qrInput) qrInput.focus();
            return;
        }

        if (scanInstruction) {
            scanInstruction.textContent = 'Scan another piece or close this window when finished.';
            scanInstruction.className = 'text-muted small';
        }
        if (qrInput) qrInput.value = '';
        processScanInput(code, true);
        if (qrInput) qrInput.focus();
    }

    function initCrossCheckEngine() {
        const liveInput = document.getElementById('liveScannerInput');
        const btnScan = document.getElementById('btnLiveScanSubmit');
        const btnAutoFill = document.getElementById('btnAutoFillExpected');

        recalculateMatrix();

        if (liveInput) {
            liveInput.focus();
            liveInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    processScanInput(liveInput.value);
                }
            });
        }

        if (btnScan) {
            btnScan.addEventListener('click', function() {
                if (liveInput) processScanInput(liveInput.value);
            });
        }

        if (btnAutoFill) {
            btnAutoFill.addEventListener('click', function() {
                autoFillAllExpected();
            });
        }

        // Keypad inputs listener
        document.querySelectorAll('.item-received-input, .item-damaged-input').forEach(input => {
            input.addEventListener('input', function() {
                const vid = this.dataset.variantId;
                const item = ITEMS.find(i => String(i.variant_id) === String(vid));
                if (item) {
                    syncVariantTagsWithInput(item);
                }
                recalculateMatrix();
                if (activeModalVariantId) {
                    updateModalState(activeModalVariantId);
                }
                saveInwardDraft();
            });
        });
    }

    function processScanInput(rawCode, scannedFromModalInput = false) {
        const code = (rawCode || '').replace(/[^\x20-\x7E]/g, '').trim();
        const liveInput = document.getElementById('liveScannerInput');
        if (!code) return;

        // Check if Master Consignment QR or Outward Number
        if (code === ALLOCATION_NUMBER || code === OUTWARD_NUMBER || code.includes(ALLOCATION_NUMBER) || (OUTWARD_NUMBER && code.includes(OUTWARD_NUMBER))) {
            autoFillAllExpected();
            playBeep('double');
            showScanFeedback("⚡ Master Consignment QR scanned! All " + TOTAL_EXPECTED + " pcs verified!", "success");
            if (liveInput) { liveInput.value = ''; liveInput.focus(); }
            return;
        }

        let cleanCode = code.toLowerCase();
        let extractedTag = '';
        let extractedVariantDesc = '';

        if (code.includes('|')) {
            const parts = code.split('|');
            extractedTag = parts[0].trim().toLowerCase();
            if (parts.length > 1) extractedVariantDesc = parts[1].trim().toLowerCase();
        } else if (code.startsWith('{') && code.endsWith('}')) {
            try {
                const j = JSON.parse(code);
                if (j.tag_code) extractedTag = j.tag_code.trim().toLowerCase();
                if (j.sku) extractedVariantDesc = j.sku.trim().toLowerCase();
                if (j.variant) extractedVariantDesc = j.variant.trim().toLowerCase();
            } catch(e) {}
        } else {
            extractedTag = cleanCode;
        }

        let matched = false;
        let matchedItem = null;
        let identifiedTag = '';

        // Strategy 0: Match Series QR Code or Saved Tags directly
        ITEMS.forEach(item => {
            if (matched) return;
            const sQr = (item.series_qr_code || '').trim().toLowerCase();
            if (sQr && (sQr === cleanCode || (extractedTag && sQr === extractedTag))) {
                matched = true;
                matchedItem = item;
                identifiedTag = sQr.toUpperCase();
                return;
            }
            if (Array.isArray(item.saved_tags)) {
                for (let st of item.saved_tags) {
                    const cleanSt = String(st).trim().toLowerCase();
                    if (cleanSt === cleanCode || (extractedTag && cleanSt === extractedTag)) {
                        matched = true;
                        matchedItem = item;
                        identifiedTag = String(st).toUpperCase();
                        return;
                    }
                }
            }
        });

        // Strategy 1: Match exact Piece Tag Code
        ITEMS.forEach(item => {
            if (matched) return;
            const start = item.startSeq || 1;
            const end = item.endSeq || (parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 1);

            for (let p = start; p <= end; p++) {
                const pTag = getLabelCode(p, item).toLowerCase();
                if (pTag === cleanCode || (extractedTag && pTag === extractedTag)) {
                    matched = true;
                    matchedItem = item;
                    identifiedTag = pTag.toUpperCase();
                    return;
                }
            }
        });

        // Strategy 2: Match by SKU / Size-Color
        if (!matched) {
            ITEMS.forEach(item => {
                if (matched) return;
                const sku = (item.sku || '').trim().toLowerCase();
                const sizeColor = ((item.size_name || '') + '-' + (item.color_name || '')).trim().toLowerCase();
                const sizeColorSpace = ((item.size_name || '') + ' - ' + (item.color_name || '')).trim().toLowerCase();

                if (sku && (sku === cleanCode || (extractedVariantDesc && sku === extractedVariantDesc) || cleanCode.includes(sku))) {
                    matched = true;
                    matchedItem = item;
                    if (extractedTag) identifiedTag = extractedTag.toUpperCase();
                    return;
                }

                if (extractedVariantDesc && (extractedVariantDesc === sizeColor || extractedVariantDesc === sizeColorSpace)) {
                    matched = true;
                    matchedItem = item;
                    if (extractedTag) identifiedTag = extractedTag.toUpperCase();
                    return;
                }
            });
        }

        // Strategy 3: Fallback single item match
        if (!matched && ITEMS.length === 1) {
            matched = true;
            matchedItem = ITEMS[0];
            if (extractedTag) identifiedTag = extractedTag.toUpperCase();
        }

        if (matched && matchedItem) {
            const vid = matchedItem.variant_id;
            if (scannedFromModalInput) {
                activeModalVariantId = vid;
                modalPieceFilter = 'all';
            }
            const recvInput = document.getElementById(`input-recv-${vid}`);
            const dmgInput = document.getElementById(`input-dmg-${vid}`);
            const expected = parseInt(matchedItem.dispatched_qty > 0 ? matchedItem.dispatched_qty : matchedItem.requested_qty) || 1;
            const row = document.getElementById(`row-${vid}`);

            // Specific handling for Series Product (1 QR code represents the entire allocated pack)
            if (matchedItem.is_series) {
                const seriesTag = (matchedItem.series_qr_code || identifiedTag || getLabelCode(matchedItem.startSeq, matchedItem)).toUpperCase();

                if (currentScanMode === 'damage') {
                    if (DAMAGED_TAGS.has(seriesTag)) {
                        playBeep('error');
                        if (row) {
                            row.classList.add('table-warning');
                            setTimeout(() => row.classList.remove('table-warning'), 800);
                        }
                        showScanFeedback(`⚠️ <strong>Already Marked Damaged:</strong> Series QR <span class="badge bg-danger font-monospace">${escapeHtml(seriesTag)}</span> is already marked as damaged.`, "warning");
                        if (liveInput) { liveInput.value = ''; liveInput.focus(); }
                        return;
                    }
                    SCANNED_TAGS.delete(seriesTag);
                    DAMAGED_TAGS.add(seriesTag);
                    if (dmgInput) dmgInput.value = expected;
                    if (recvInput) recvInput.value = 0;

                    if (row) {
                        row.classList.add('table-danger');
                        setTimeout(() => row.classList.remove('table-danger'), 800);
                    }
                    playBeep('double');
                    showScanFeedback(`🔴 <strong>Damaged Series Pack Logged:</strong> Series QR <span class="badge bg-danger font-monospace">${escapeHtml(seriesTag)}</span> marked as DEFECT / DAMAGE for <strong>${escapeHtml(matchedItem.sku)}</strong> (${expected} pcs).`, "danger");
                } else {
                    if (SCANNED_TAGS.has(seriesTag) && !DAMAGED_TAGS.has(seriesTag) && recvInput && parseInt(recvInput.value) >= expected) {
                        playBeep('error');
                        if (row) {
                            row.classList.add('table-warning');
                            setTimeout(() => row.classList.remove('table-warning'), 800);
                        }
                        showScanFeedback(`⚠️ <strong>Already Verified:</strong> Series QR <span class="badge bg-dark font-monospace">${escapeHtml(seriesTag)}</span> (${expected} pcs) has ALREADY been scanned &amp; verified.`, "warning");
                        if (liveInput) { liveInput.value = ''; liveInput.focus(); }
                        return;
                    }
                    DAMAGED_TAGS.delete(seriesTag);
                    SCANNED_TAGS.add(seriesTag);
                    if (recvInput) recvInput.value = expected;
                    if (dmgInput) dmgInput.value = 0;

                    if (row) {
                        row.classList.add('table-success');
                        setTimeout(() => row.classList.remove('table-success'), 600);
                    }
                    playBeep('success');
                    showScanFeedback(`✅ <strong>Series Pack Verified:</strong> Scanned single series QR <span class="badge bg-primary font-monospace">${escapeHtml(seriesTag)}</span> for <strong>${escapeHtml(matchedItem.sku)}</strong>. Verified: <strong>${expected} / ${expected} pcs</strong> (Entire pack verified).`, "success");
                }

                recalculateMatrix();
                saveInwardDraft();
                if (activeModalVariantId && String(activeModalVariantId) === String(vid)) {
                    updateModalState(vid);
                }
                if (liveInput) { liveInput.value = ''; liveInput.focus(); }
                return;
            }

            if (!identifiedTag) {
                for (let p = matchedItem.startSeq; p <= matchedItem.endSeq; p++) {
                    const tag = getLabelCode(p, matchedItem).toUpperCase();
                    if (currentScanMode === 'damage') {
                        if (!DAMAGED_TAGS.has(tag)) {
                            identifiedTag = tag;
                            break;
                        }
                    } else {
                        if (!SCANNED_TAGS.has(tag) && !DAMAGED_TAGS.has(tag)) {
                            identifiedTag = tag;
                            break;
                        }
                    }
                }
            }

            if (currentScanMode === 'damage') {
                // Handle Damaged Piece Scan
                if (identifiedTag && DAMAGED_TAGS.has(identifiedTag)) {
                    playBeep('error');
                    if (row) {
                        row.classList.add('table-warning');
                        setTimeout(() => row.classList.remove('table-warning'), 800);
                    }
                    showScanFeedback(`⚠️ <strong>Duplicate Scan Blocked:</strong> Piece QR tag <span class="badge bg-danger font-monospace">${escapeHtml(identifiedTag)}</span> is ALREADY marked as DAMAGED.`, "warning");
                    if (liveInput) { liveInput.value = ''; liveInput.focus(); }
                    return;
                }

                const currentDamaged = getVariantDamagedTagCount(matchedItem);
                const currentVerified = getVariantVerifiedTagCount(matchedItem);
                if (currentDamaged + currentVerified >= expected && !SCANNED_TAGS.has(identifiedTag)) {
                    playBeep('error');
                    if (row) {
                        row.classList.add('table-warning');
                        setTimeout(() => row.classList.remove('table-warning'), 800);
                    }
                    showScanFeedback(`⚠️ <strong>Consignment Limit Reached:</strong> All expected ${expected} pcs for <strong>${escapeHtml(matchedItem.sku)}</strong> are already processed.`, "warning");
                    if (liveInput) { liveInput.value = ''; liveInput.focus(); }
                    return;
                }

                if (identifiedTag) {
                    SCANNED_TAGS.delete(identifiedTag);
                    DAMAGED_TAGS.add(identifiedTag);
                }

                const newDamagedCount = getVariantDamagedTagCount(matchedItem);
                const newVerifiedCount = getVariantVerifiedTagCount(matchedItem);
                if (dmgInput) dmgInput.value = newDamagedCount;
                if (recvInput) recvInput.value = newVerifiedCount;

                if (row) {
                    row.classList.add('table-danger');
                    setTimeout(() => row.classList.remove('table-danger'), 800);
                }

                playBeep('double');
                const tagDisplay = identifiedTag || getLabelCode(newDamagedCount, matchedItem);
                showScanFeedback(`🔴 <strong>Damaged Piece Logged:</strong> QR <span class="badge bg-danger font-monospace">${escapeHtml(tagDisplay)}</span> marked as DEFECT / TRANSIT DAMAGE for <strong>${escapeHtml(matchedItem.sku)}</strong>. Damaged: <strong>${newDamagedCount} pcs</strong>`, "danger");
            } else {
                // Handle Good / Intact Piece Scan
                if (identifiedTag && SCANNED_TAGS.has(identifiedTag) && !DAMAGED_TAGS.has(identifiedTag)) {
                    playBeep('error');
                    if (row) {
                        row.classList.add('table-warning');
                        setTimeout(() => row.classList.remove('table-warning'), 800);
                    }
                    showScanFeedback(`⚠️ <strong>Duplicate Scan Blocked:</strong> Piece QR tag <span class="badge bg-dark font-monospace">${escapeHtml(identifiedTag)}</span> has ALREADY been scanned &amp; verified.`, "warning");
                    if (liveInput) { liveInput.value = ''; liveInput.focus(); }
                    return;
                }

                const currentVerified = getVariantVerifiedTagCount(matchedItem);
                const currentDamaged = getVariantDamagedTagCount(matchedItem);
                if (currentVerified + currentDamaged >= expected && !DAMAGED_TAGS.has(identifiedTag)) {
                    playBeep('error');
                    if (row) {
                        row.classList.add('table-warning');
                        setTimeout(() => row.classList.remove('table-warning'), 800);
                    }
                    showScanFeedback(`⚠️ <strong>Maximum Quantity Reached:</strong> All expected <strong>${expected} / ${expected} pcs</strong> for <strong>${escapeHtml(matchedItem.sku)}</strong> are already processed.`, "warning");
                    if (liveInput) { liveInput.value = ''; liveInput.focus(); }
                    return;
                }

                if (identifiedTag) {
                    DAMAGED_TAGS.delete(identifiedTag);
                    SCANNED_TAGS.add(identifiedTag);
                }

                const newVerifiedCount = getVariantVerifiedTagCount(matchedItem);
                const newDamagedCount = getVariantDamagedTagCount(matchedItem);
                if (recvInput) recvInput.value = newVerifiedCount;
                if (dmgInput) dmgInput.value = newDamagedCount;

                if (row) {
                    row.classList.add('table-success');
                    setTimeout(() => row.classList.remove('table-success'), 600);
                }

                playBeep('success');
                const tagDisplay = identifiedTag || getLabelCode(newVerifiedCount, matchedItem);
                showScanFeedback(`✅ Verified Piece QR <span class="badge bg-dark font-monospace">${escapeHtml(tagDisplay)}</span> for <strong>${escapeHtml(matchedItem.sku)}</strong> (+1 pc). Verified: <strong>${newVerifiedCount} / ${expected} pcs</strong>`, "success");
            }

            recalculateMatrix();
            saveInwardDraft();

            if (activeModalVariantId && String(activeModalVariantId) === String(vid)) {
                updateModalState(vid);
            }
        } else {
            playBeep('error');
            showScanFeedback(`⚠️ Scanned QR code '<strong>${escapeHtml(code)}</strong>' not found in this shop inward consignment!`, "danger");
        }

        if (liveInput) {
            liveInput.value = '';
            liveInput.focus();
        }
    }

    function autoFillAllExpected() {
        ITEMS.forEach(item => {
            const vid = item.variant_id;
            const recvInput = document.getElementById(`input-recv-${vid}`);
            const dmgInput = document.getElementById(`input-dmg-${vid}`);
            const exp = parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 1;
            if (recvInput) recvInput.value = exp;
            if (dmgInput) dmgInput.value = 0;

            for (let p = item.startSeq; p <= item.endSeq; p++) {
                const tag = getLabelCode(p, item).toUpperCase();
                SCANNED_TAGS.add(tag);
                DAMAGED_TAGS.delete(tag);
            }
        });
        recalculateMatrix();
        saveInwardDraft();
        if (activeModalVariantId) {
            updateModalState(activeModalVariantId);
        }
    }

    function recalculateMatrix() {
        let totalScanned = 0;
        let totalDamaged = 0;
        let missingItems = [];

        ITEMS.forEach(item => {
            const vid = item.variant_id;
            const expected = parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 0;
            const recvInput = document.getElementById(`input-recv-${vid}`);
            const dmgInput = document.getElementById(`input-dmg-${vid}`);

            const damaged = dmgInput ? (parseInt(dmgInput.value) || 0) : (parseInt(item.damaged_qty) || 0);
            const persistedTotal = parseInt(item.received_qty) || (IS_FULLY_RECEIVED ? expected : 0);
            const received = recvInput ? (parseInt(recvInput.value) || 0) : Math.max(0, persistedTotal - damaged);

            totalScanned += received;
            totalDamaged += damaged;

            const accounted = received + damaged;
            const diff = accounted - expected;
            const diffBadge = document.getElementById(`diff-badge-${vid}`);
            const statusBadge = document.getElementById(`status-badge-${vid}`);

            if (diffBadge) {
                diffBadge.textContent = (diff > 0 ? `+${diff}` : `${diff}`) + ' pcs';
                if (diff === 0) {
                    diffBadge.className = 'badge bg-success fs-6 font-monospace';
                } else if (diff < 0) {
                    diffBadge.className = 'badge bg-danger fs-6 font-monospace';
                } else {
                    diffBadge.className = 'badge bg-primary fs-6 font-monospace';
                }
            }

            if (statusBadge) {
                if (diff === 0) {
                    if (damaged > 0) {
                        statusBadge.textContent = `MATCHED (${damaged} DMG)`;
                        statusBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                    } else {
                        statusBadge.textContent = 'MATCHED';
                        statusBadge.className = 'badge bg-success-subtle text-success border border-success';
                    }
                } else if (diff < 0) {
                    statusBadge.textContent = `SHORTAGE (${Math.abs(diff)})`;
                    statusBadge.className = 'badge bg-danger-subtle text-danger border border-danger';
                } else {
                    statusBadge.textContent = `EXCESS (+${diff})`;
                    statusBadge.className = 'badge bg-primary-subtle text-primary border border-primary';
                }
            }

            if (diff < 0) {
                missingItems.push({
                    variant_id: vid,
                    sku: item.sku,
                    qr_code: item.qr_code || item.sku,
                    mrp: item.mrp || item.selling_price || 0,
                    selling_price: item.selling_price || 0,
                    product_name: item.product_name || 'Retail Product',
                    category_name: item.category_name || '',
                    product_image: item.product_image || '',
                    size_name: item.size_name || 'Free',
                    color_name: item.color_name || 'Std',
                    expected: expected,
                    scanned: received,
                    damaged: damaged,
                    missing: Math.abs(diff),
                    startSeq: item.startSeq || 1,
                    endSeq: item.endSeq || expected,
                    is_series: item.is_series,
                    series_qr_code: item.series_qr_code,
                    saved_tags: item.saved_tags,
                    selected_qr_codes: item.selected_qr_codes,
                    series_start_code: item.series_start_code,
                    qr_type: item.qr_type,
                    pack_quantity: item.pack_quantity,
                    category_type: item.category_type
                });
            }
        });

        // Update Top Summary Cards
        const statScanned = document.getElementById('statScanned');
        const statMissing = document.getElementById('statMissing');
        const statProgressPct = document.getElementById('statProgressPct');
        const statProgressBar = document.getElementById('statProgressBar');
        const btnScannedCount = document.getElementById('btnCreditScannedCount');

        const overallMissing = Math.max(0, TOTAL_EXPECTED - totalScanned - totalDamaged);
        const pct = TOTAL_EXPECTED > 0 ? Math.min(100, Math.round(((totalScanned + totalDamaged) / TOTAL_EXPECTED) * 100)) : 100;

        if (statScanned) {
            const dmgText = totalDamaged > 0 ? ` <span class="text-danger small fs-7">(${totalDamaged} dmg)</span>` : '';
            statScanned.innerHTML = `${totalScanned} <small class="fs-6 text-muted">pcs</small>${dmgText}`;
        }
        if (statMissing) statMissing.innerHTML = `${overallMissing} <small class="fs-6 text-muted">pcs</small>`;
        if (statProgressPct) statProgressPct.textContent = `${pct}%`;
        if (btnScannedCount) btnScannedCount.textContent = totalScanned + totalDamaged;

        if (statProgressBar) {
            statProgressBar.style.width = `${pct}%`;
            if (pct >= 100 && overallMissing === 0) {
                statProgressBar.className = 'progress-bar bg-success';
            } else {
                statProgressBar.className = 'progress-bar bg-info';
            }
        }

        updateCrossCheckDetails(missingItems);
    }

    function updateCrossCheckDetails(missingItems) {
        const pendingByVariant = new Map(missingItems.map(item => [String(item.variant_id), item]));

        ITEMS.forEach(item => {
            const variantId = String(item.variant_id);
            const pendingItem = pendingByVariant.get(variantId);
            const missingCell = document.getElementById(`missing-qty-${variantId}`);
            const qrCell = document.getElementById(`qr-tags-${variantId}`);

            if (missingCell) {
                const missingQty = pendingItem ? pendingItem.missing : 0;
                missingCell.textContent = `${missingQty} pcs`;
                missingCell.className = missingQty > 0
                    ? 'text-center fw-bold text-danger'
                    : 'text-center text-muted';
            }

            if (!qrCell) return;

            if (!pendingItem) {
                if (item.is_series && item.series_qr_code) {
                    qrCell.innerHTML = `
                        <span class="badge bg-primary text-white font-monospace"><i class="bi bi-box-seam me-1"></i>${escapeHtml(item.series_qr_code)}</span>
                        <small class="text-muted d-block mt-1">Series pack (${escapeHtml(item.dispatched_qty || item.requested_qty)} pcs)</small>
                    `;
                } else {
                    qrCell.innerHTML = `<span class="badge bg-dark text-white font-monospace"><i class="bi bi-qr-code me-1 text-warning"></i>${escapeHtml(qrCell.dataset.defaultTag || '')}</span>`;
                }
                return;
            }

            let pendingTags = [];
            for (let sequence = pendingItem.startSeq; sequence <= pendingItem.endSeq; sequence++) {
                const tag = getLabelCode(sequence, pendingItem).toUpperCase();
                if (!SCANNED_TAGS.has(tag) && !DAMAGED_TAGS.has(tag)) {
                    pendingTags.push(tag);
                }
            }

            const isSeries = Boolean(
                pendingItem.is_series ||
                pendingItem.qr_type === 'SERIES_BATCH' ||
                (pendingItem.category_type && String(pendingItem.category_type).toUpperCase() === 'SERIES') ||
                parseInt(pendingItem.pack_quantity) > 1 ||
                pendingItem.is_complementary
            );
            const seriesQr = pendingItem.series_qr_code ||
                (pendingItem.saved_tags && pendingItem.saved_tags[0]) ||
                (pendingItem.selected_qr_codes && !String(pendingItem.selected_qr_codes).startsWith('[') ? pendingItem.selected_qr_codes.split(',')[0].trim() : '') ||
                pendingItem.series_start_code ||
                '';

            if (isSeries && seriesQr) {
                qrCell.innerHTML = `
                    <span class="badge bg-primary text-white font-monospace"><i class="bi bi-box-seam me-1"></i>${escapeHtml(seriesQr.toUpperCase())}</span>
                    <small class="text-muted d-block mt-1">Series pack (${pendingItem.expected} pcs)</small>
                `;
                return;
            }

            const nextPendingTag = pendingTags[0] || getLabelCode(pendingItem.startSeq, pendingItem);
            const pendingTagRange = pendingTags.length > 1
                ? `${pendingTags[0]} - ${pendingTags[pendingTags.length - 1]}`
                : nextPendingTag;
            qrCell.innerHTML = `
                <span class="badge bg-dark text-white font-monospace"><i class="bi bi-qr-code me-1 text-warning"></i>${escapeHtml(pendingTagRange)}</span>
                <small class="text-muted d-block mt-1">Next: <strong class="text-danger">${escapeHtml(nextPendingTag)}</strong></small>
            `;
        });
    }

    let modalPieceFilter = 'mismatched'; // 'mismatched' | 'all' | 'verified' | 'damaged'

    function setModalPieceFilter(filter) {
        modalPieceFilter = filter;
        if (activeModalVariantId) {
            updateModalState(activeModalVariantId);
        }
    }

    function openMissingProductModal(variantId, initialFilter = 'mismatched') {
        try {
            activeModalVariantId = variantId;
            modalPieceFilter = initialFilter;
            updateModalState(variantId);
            
            const modalEl = document.getElementById('missingProductModal');
            const modalHeader = modalEl ? modalEl.querySelector('.modal-header') : null;
            const modalTitle = document.getElementById('missingProductModalLabel');
            
            if (modalHeader && modalTitle) {
                if (initialFilter === 'mismatched') {
                    modalHeader.className = 'modal-header bg-danger text-white border-0 py-3 px-4';
                    modalTitle.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>Missed / Pending Pieces Inspection';
                } else {
                    modalHeader.className = 'modal-header bg-dark text-white border-0 py-3 px-4';
                    modalTitle.innerHTML = '<i class="bi bi-box-seam-fill me-2 text-warning"></i>Product Specifications &amp; All Individual Piece QR Codes';
                }
            }

            if (modalEl) {
                let modal = null;
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    if (typeof bootstrap.Modal.getOrCreateInstance === 'function') {
                        modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    } else if (typeof bootstrap.Modal.getInstance === 'function') {
                        modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    } else {
                        modal = new bootstrap.Modal(modalEl);
                    }
                }
                if (modal) {
                    modal.show();
                } else if (window.jQuery && typeof $(modalEl).modal === 'function') {
                    $(modalEl).modal('show');
                }
            }
        } catch(e) {
            console.error("Error opening missing product modal:", e);
        }
    }

    // 2D QR Code Tag Preview Modal Handler (Matching Single Garment Sticker Mockup)
    window.previewSinglePieceTag = function(variantId, pieceNum, tagCode) {
        const item = ITEMS.find(i => String(i.variant_id) === String(variantId));
        if (!item) return;

        let mrp = parseFloat(item.mrp || item.selling_price || item.base_price || 0);
        let cost = parseFloat(item.purchase_price || item.production_cost || item.cost_price || item.selling_price || mrp || 0);

        if (isNaN(mrp) || mrp <= 0) mrp = parseFloat(item.selling_price || item.base_price || 999.00);
        if (isNaN(cost) || cost <= 0) cost = parseFloat(item.purchase_price || item.production_cost || item.cost_price || (mrp > 0 ? (mrp * 0.6).toFixed(2) : 240.00));

        const fmtMrp = mrp.toFixed(2);
        const fmtCost = cost.toFixed(2);
        const sSeq = item.startSeq || 1;
        const finalTag = (tagCode || getLabelCode(sSeq + pieceNum - 1, item)).toUpperCase();

        const qrContainer = document.getElementById('modalQrCanvas');
        if (qrContainer && typeof QRCode !== 'undefined') {
            qrContainer.innerHTML = '';
            try {
                new QRCode(qrContainer, {
                    text: finalTag,
                    width: 150,
                    height: 150,
                    colorDark: "#0f172a",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
            } catch(e) {
                console.warn("Modal QR error:", e);
            }
        }
        
        const elQrTag = document.getElementById('modalQrTag');
        if (elQrTag) {
            elQrTag.innerHTML = `<div class="font-monospace fw-bold text-primary fs-5 mt-2" style="letter-spacing: 0.5px;">${escapeHtml(finalTag)}</div>`;
        }
        
        // Highlight the clicked item in the grid
        document.querySelectorAll('.inward-piece-qr').forEach(el => el.classList.remove('border-primary', 'shadow'));
        const clickedQr = document.getElementById(`piece-qr-${pieceNum}`);
        if (clickedQr) {
            clickedQr.classList.add('border-primary', 'shadow');
        }
    };

    function updateModalState(variantId) {
        const item = ITEMS.find(i => String(i.variant_id) === String(variantId));
        if (!item) return;

        const expected = parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 0;
        const recvInput = document.getElementById(`input-recv-${variantId}`);
        const dmgInput = document.getElementById(`input-dmg-${variantId}`);
        const damaged = dmgInput ? (parseInt(dmgInput.value) || 0) : (parseInt(item.damaged_qty) || 0);
        const persistedTotal = parseInt(item.received_qty) || (IS_FULLY_RECEIVED ? expected : 0);
        const received = recvInput ? (parseInt(recvInput.value) || 0) : Math.max(0, persistedTotal - damaged);
        const missing = Math.max(0, expected - received - damaged);

        // Fill Metadata safely
        const elName = document.getElementById('modalProductName');
        if (elName) elName.textContent = item.product_name || 'Retail Product';
        const elCat = document.getElementById('modalProductCategory');
        if (elCat) elCat.textContent = item.category_name || 'Apparel';
        const elSku = document.getElementById('modalProductSku');
        if (elSku) elSku.textContent = item.sku || '';
        const elSize = document.getElementById('modalProductSize');
        if (elSize) elSize.textContent = item.size_name || 'Free';
        const elColor = document.getElementById('modalProductColor');
        if (elColor) elColor.textContent = item.color_name || 'Std';

        const elExp = document.getElementById('modalExpectedQty');
        if (elExp) elExp.textContent = `${expected} pcs`;
        const elScn = document.getElementById('modalScannedQty');
        if (elScn) elScn.textContent = `${received} pcs`;
        const elDmg = document.getElementById('modalDamagedQty');
        if (elDmg) elDmg.textContent = `${damaged} pcs`;
        const elMis = document.getElementById('modalMissingQty');
        if (elMis) elMis.textContent = `${missing > 0 ? '-' + missing : '0'} pcs`;

        const imgContainer = document.getElementById('modalProductImgContainer');
        if (imgContainer) {
            if (item.product_image) {
                imgContainer.innerHTML = `<img src="${escapeHtml(item.product_image)}" class="rounded border shadow-sm" style="width: 64px; height: 64px; object-fit: cover;">`;
            } else {
                imgContainer.innerHTML = `<div class="rounded bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center shadow-sm" style="width: 64px; height: 64px;"><i class="bi bi-box-seam fs-2"></i></div>`;
            }
        }

        // Render QR Code for next unscanned piece
        const sSeq = item.startSeq || 1;
        const eSeq = item.endSeq || (sSeq + Math.max(1, expected) - 1);
        let nextUnscannedPieceSeq = sSeq;
        for (let p = sSeq; p <= eSeq; p++) {
            const tag = getLabelCode(p, item).toUpperCase();
            if (!SCANNED_TAGS.has(tag) && !DAMAGED_TAGS.has(tag)) {
                nextUnscannedPieceSeq = p;
                break;
            }
        }

        const currentTagCode = getLabelCode(nextUnscannedPieceSeq, item);
        const qrPayload = getQRPayload(nextUnscannedPieceSeq, item);

        const elQrTag = document.getElementById('modalQrTag');
        if (elQrTag) {
            elQrTag.innerHTML = `<div class="font-monospace fw-bold text-primary fs-5 mt-2" style="letter-spacing: 0.5px;">${escapeHtml(currentTagCode)}</div>`;
        }

        const qrContainer = document.getElementById('modalQrCanvas');
        if (qrContainer && typeof QRCode !== 'undefined') {
            qrContainer.innerHTML = '';
            try {
                new QRCode(qrContainer, {
                    text: currentTagCode,
                    width: 150,
                    height: 150,
                    colorDark: "#0f172a",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
            } catch(e) {
                console.warn("Modal QR error:", e);
            }
        }

        // Update modal buttons
        const btnScan = document.getElementById('btnModalScanPlusOne');
        const btnText = document.getElementById('modalBtnScanText');

        if (btnText) btnText.textContent = `1 pc (Pending: ${missing})`;
        if (btnScan) btnScan.disabled = missing === 0;

        // Update Filter Button Badges and Active Styles
        const filterCountMismatched = document.getElementById('filterCountMismatched');
        const filterCountAll = document.getElementById('filterCountAll');
        const filterCountVerified = document.getElementById('filterCountVerified');
        const filterCountDamaged = document.getElementById('filterCountDamaged');
        if (filterCountMismatched) filterCountMismatched.textContent = missing;
        if (filterCountAll) filterCountAll.textContent = expected;
        if (filterCountVerified) filterCountVerified.textContent = received;
        if (filterCountDamaged) filterCountDamaged.textContent = damaged;

        const btnFilterMismatched = document.getElementById('btnFilterMismatched');
        const btnFilterAll = document.getElementById('btnFilterAll');
        const btnFilterVerified = document.getElementById('btnFilterVerified');
        const btnFilterDamaged = document.getElementById('btnFilterDamaged');
        const titleEl = document.getElementById('modalPiecesSectionTitle');

        if (btnFilterMismatched && btnFilterAll && btnFilterVerified) {
            btnFilterMismatched.className = (modalPieceFilter === 'mismatched') ? 'btn btn-danger btn-sm active fw-bold' : 'btn btn-outline-danger btn-sm fw-semibold';
            btnFilterAll.className = (modalPieceFilter === 'all') ? 'btn btn-secondary btn-sm active fw-bold' : 'btn btn-outline-secondary btn-sm fw-semibold';
            btnFilterVerified.className = (modalPieceFilter === 'verified') ? 'btn btn-success btn-sm active fw-bold' : 'btn btn-outline-success btn-sm fw-semibold';
            if (btnFilterDamaged) {
                btnFilterDamaged.className = (modalPieceFilter === 'damaged') ? 'btn btn-danger btn-sm active fw-bold' : 'btn btn-outline-danger btn-sm fw-semibold';
            }
        }

        if (titleEl) {
            if (modalPieceFilter === 'mismatched') {
                titleEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Mismatched / Missing Pieces (<span id="modalMissingPiecesCount">${missing}</span> pending)`;
            } else if (modalPieceFilter === 'verified') {
                titleEl.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i> Intact Verified Pieces (<span id="modalMissingPiecesCount">${received}</span> verified)`;
            } else if (modalPieceFilter === 'damaged') {
                titleEl.innerHTML = `<i class="bi bi-slash-circle-fill text-danger me-1"></i> Damaged / Defect Pieces (<span id="modalMissingPiecesCount">${damaged}</span> damaged)`;
            } else {
                titleEl.innerHTML = `<i class="bi bi-grid-3x3-gap-fill text-dark me-1"></i> All Individual Pieces (<span id="modalMissingPiecesCount">${expected}</span> total)`;
            }
        }

        // Render Individual Pieces Breakdown Grid Filtered by modalPieceFilter (STRICT 2-PER-ROW: col-12 col-md-6)
        const piecesCount = document.getElementById('modalMissingPiecesCount');
        const piecesGrid = document.getElementById('modalMissingPiecesGrid');
        if (piecesCount) {
            piecesCount.textContent = (modalPieceFilter === 'mismatched') ? missing : ((modalPieceFilter === 'verified') ? received : ((modalPieceFilter === 'damaged') ? damaged : expected));
        }

        if (piecesGrid) {
            let gridHtml = '';
            let displayedCount = 0;

            const displayBrand = (item.brand_name || item.product_name || 'GRM APPARELS').toUpperCase();
            const displayAttr = `${item.size_name || 'Free'}-${item.color_name || 'Std'}`.toUpperCase();

            let mrp = parseFloat(item.mrp || item.selling_price || item.base_price || 0);
            let cost = parseFloat(item.purchase_price || item.production_cost || item.cost_price || item.selling_price || mrp || 0);

            if (isNaN(mrp) || mrp <= 0) {
                mrp = parseFloat(item.selling_price || item.base_price || 999.00);
            }
            if (isNaN(cost) || cost <= 0) {
                cost = parseFloat(item.purchase_price || item.production_cost || item.cost_price || (mrp > 0 ? (mrp * 0.6).toFixed(2) : 240.00));
            }

            const fmtMrp = mrp.toFixed(2);
            const fmtCost = cost.toFixed(2);

            if (item.is_series) {
                const seriesTag = (item.series_qr_code || getLabelCode(item.startSeq, item)).toUpperCase();
                const isDamaged = DAMAGED_TAGS.has(seriesTag) || damaged > 0;
                const isVerified = (SCANNED_TAGS.has(seriesTag) || received >= expected) && !isDamaged;
                const isPending = !isVerified && !isDamaged;

                if (modalPieceFilter === 'mismatched' && !isPending) {
                    displayedCount = 0;
                } else if (modalPieceFilter === 'verified' && !isVerified) {
                    displayedCount = 0;
                } else if (modalPieceFilter === 'damaged' && !isDamaged) {
                    displayedCount = 0;
                } else {
                    displayedCount = 1;
                    const packQrPayload = `${seriesTag}|${item.sku || ''}|QTY:${expected}|MRP:${fmtMrp}|Disc:${fmtCost}`;
                    
                    gridHtml = `
                        <div class="col-12 mb-3">
                            <div class="p-3 border rounded-3 ${isVerified ? 'bg-success-subtle border-success' : (isDamaged ? 'bg-danger-subtle border-danger' : 'bg-light border-primary')} shadow-sm">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="text-center" style="width: 100px;">
                                            <div class="inward-piece-qr bg-white p-1 rounded border shadow-sm d-flex align-items-center justify-content-center cursor-pointer"
                                                 id="piece-qr-1"
                                                 data-qr="${escapeHtml(packQrPayload)}"
                                                 onclick="previewSinglePieceTag(${variantId}, 1, '${escapeHtml(seriesTag)}');"
                                                 title="Click to view full Series Master QR Code"
                                                 style="width: 96px; height: 96px; cursor: pointer;">
                                            </div>
                                            <div class="badge bg-primary text-white font-monospace mt-1 px-1 py-1 w-100 text-truncate"
                                                 title="${escapeHtml(seriesTag)}"
                                                 style="font-size: 0.75rem;">
                                                <i class="bi bi-box-seam me-1"></i>${escapeHtml(seriesTag)}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <h6 class="mb-0 fw-bold text-dark">${escapeHtml(displayBrand)}</h6>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">📦 Series Master Pack</span>
                                            </div>
                                            <div class="text-muted small mt-1 font-monospace">${escapeHtml(item.sku || '')} &bull; ${escapeHtml(displayAttr)}</div>
                                            <div class="d-flex align-items-center gap-3 mt-2">
                                                <span class="badge bg-dark fs-7"><i class="bi bi-layers me-1"></i>Pack Quantity: ${expected} pcs</span>
                                                <span class="text-dark fw-bold">MRP: ₹${fmtMrp}</span>
                                                <span class="text-success fw-bold">Disc: ₹${fmtCost}</span>
                                            </div>
                                            <div class="mt-2">
                                                ${isVerified 
                                                    ? '<span class="badge bg-success text-white py-1 px-2"><i class="bi bi-check-circle-fill me-1"></i>Series Pack Intact &amp; Verified (' + expected + ' pcs)</span>' 
                                                    : (isDamaged 
                                                        ? '<span class="badge bg-danger text-white py-1 px-2"><i class="bi bi-exclamation-octagon-fill me-1"></i>Series Pack Quarantined as Damaged (' + expected + ' pcs)</span>' 
                                                        : '<span class="badge bg-warning text-dark py-1 px-2"><i class="bi bi-clock-fill me-1"></i>Awaiting Inward Scan (Single QR on Pack)</span>')}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column gap-2">
                                        ${!IS_FULLY_RECEIVED ? `
                                            <button type="button" class="btn btn-sm btn-success px-3 py-2 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2" onclick="fastScanVariant(${variantId}, '${escapeHtml(seriesTag)}');">
                                                <i class="bi bi-check-circle"></i> Verify Series Pack (${expected} pcs)
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger px-3 py-1.5 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2" onclick="markPieceDamage(${variantId}, '${escapeHtml(seriesTag)}');">
                                                <i class="bi bi-slash-circle"></i> Mark Pack as Damaged
                                            </button>
                                        ` : `
                                            <span class="badge bg-success text-white p-2"><i class="bi bi-lock-fill me-1"></i>Inventory Credited</span>
                                        `}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            } else {
                for (let i = 1; i <= expected; i++) {
                    const globalSeq = sSeq + i - 1;
                    const pieceTagCode = getLabelCode(globalSeq, item).toUpperCase();
                    const isDamaged = DAMAGED_TAGS.has(pieceTagCode);
                    const isVerified = SCANNED_TAGS.has(pieceTagCode) && !isDamaged;
                    const isPending = !isVerified && !isDamaged;

                    // Apply Active Filter: Skip non-matching pieces
                    if (modalPieceFilter === 'mismatched' && !isPending) {
                        continue;
                    }
                    if (modalPieceFilter === 'verified' && !isVerified) {
                        continue;
                    }
                    if (modalPieceFilter === 'damaged' && !isDamaged) {
                        continue;
                    }

                    displayedCount++;
                    const pieceQrPayload = pieceTagCode;

                    if (isVerified) {
                        if (IS_FULLY_RECEIVED) {
                            // LOCKED & DISABLED ACTION FOR ALREADY INVENTORY ADDED PRODUCT
                            gridHtml += `
                                <div class="col-12 col-md-6 mb-2">
                                    <div class="p-2.5 border rounded-3 bg-success-subtle border-success border-opacity-50 d-flex align-items-center justify-content-between gap-2 shadow-xs h-100 position-relative overflow-hidden" style="background-color: #f0fdf4;">
                                        <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                                            <div class="text-center flex-shrink-0" style="width: 82px;">
                                                <div class="inward-piece-qr bg-white p-1 rounded border border-success-subtle shadow-xs d-flex align-items-center justify-content-center cursor-pointer" 
                                                     id="piece-qr-${i}" 
                                                     data-qr="${escapeHtml(pieceQrPayload)}" 
                                                     onclick="previewSinglePieceTag(${variantId}, ${i}, '${escapeHtml(pieceTagCode)}');" 
                                                     title="Click to view full 2D QR Code Tag" 
                                                     style="width: 80px; height: 80px; cursor: pointer;">
                                                </div>
                                                <div class="badge bg-dark text-white font-monospace mt-1 px-1 py-1 w-100 text-truncate cursor-pointer shadow-xs" 
                                                     onclick="previewSinglePieceTag(${variantId}, ${i}, '${escapeHtml(pieceTagCode)}');" 
                                                     title="${escapeHtml(pieceTagCode)}" 
                                                     style="font-size: 0.68rem; letter-spacing: 0.3px; cursor: pointer;">
                                                    ${escapeHtml(pieceTagCode)}
                                                </div>
                                            </div>
                                            <div class="overflow-hidden flex-grow-1 min-w-0" style="line-height: 1.25;">
                                                <div class="fw-bold text-uppercase text-dark text-truncate" style="font-size: 0.80rem; letter-spacing: 0.2px;" title="${escapeHtml(displayBrand)}">
                                                    ${escapeHtml(displayBrand)}
                                                </div>
                                                <div class="fw-bold text-secondary text-truncate mt-0.5" style="font-size: 0.76rem;">
                                                    ${escapeHtml(displayAttr)}
                                                </div>
                                                <div class="text-muted text-decoration-line-through mt-0.5" style="font-size: 0.74rem;">
                                                    MRP: ₹${fmtMrp}
                                                </div>
                                                <div class="fw-bold text-success mt-0.5" style="font-size: 0.82rem;">
                                                    Disc: ₹${fmtCost}
                                                </div>
                                                <div class="mt-1">
                                                    <span class="badge bg-success text-white py-0.5 px-1.5 shadow-xs" style="font-size: 0.68rem; letter-spacing: 0.2px;">
                                                        <i class="bi bi-lock-fill me-1"></i>Stock Added
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column gap-1.5 flex-shrink-0 align-items-center">
                                            <button type="button" class="btn btn-sm btn-success d-flex align-items-center justify-content-center shadow-xs disabled" style="width: 32px; height: 30px; padding: 0; border-radius: 6px; opacity: 0.95;" disabled title="Good Product (Credited to Inventory)">
                                                <i class="bi bi-check-lg" style="font-size: 1.05rem;"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center shadow-xs disabled" style="width: 32px; height: 30px; padding: 0; border-radius: 6px; opacity: 0.55;" disabled title="Already credited to inventory in previous inward session (Locked)">
                                                <i class="bi bi-lock-fill" style="font-size: 0.90rem;"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            gridHtml += `
                                <div class="col-12 col-md-6 mb-2">
                                    <div class="p-2.5 border rounded-3 bg-success-subtle border-success border-opacity-50 d-flex align-items-center justify-content-between gap-2 shadow-xs h-100 position-relative overflow-hidden">
                                        <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                                            <div class="text-center flex-shrink-0" style="width: 82px;">
                                                <div class="inward-piece-qr bg-white p-1 rounded border border-success-subtle shadow-xs d-flex align-items-center justify-content-center cursor-pointer" 
                                                     id="piece-qr-${i}" 
                                                     data-qr="${escapeHtml(pieceQrPayload)}" 
                                                     onclick="previewSinglePieceTag(${variantId}, ${i}, '${escapeHtml(pieceTagCode)}');" 
                                                     title="Click to view full 2D QR Code Tag" 
                                                     style="width: 80px; height: 80px; cursor: pointer;">
                                                </div>
                                                <div class="badge bg-dark text-white font-monospace mt-1 px-1 py-1 w-100 text-truncate cursor-pointer shadow-xs" 
                                                     onclick="previewSinglePieceTag(${variantId}, ${i}, '${escapeHtml(pieceTagCode)}');" 
                                                     title="${escapeHtml(pieceTagCode)}" 
                                                     style="font-size: 0.68rem; letter-spacing: 0.3px; cursor: pointer;">
                                                    ${escapeHtml(pieceTagCode)}
                                                </div>
                                            </div>
                                            <div class="overflow-hidden flex-grow-1 min-w-0" style="line-height: 1.25;">
                                                <div class="fw-bold text-uppercase text-dark text-truncate" style="font-size: 0.80rem; letter-spacing: 0.2px;" title="${escapeHtml(displayBrand)}">
                                                    ${escapeHtml(displayBrand)}
                                                </div>
                                                <div class="fw-bold text-secondary text-truncate mt-0.5" style="font-size: 0.76rem;">
                                                    ${escapeHtml(displayAttr)}
                                                </div>
                                                <div class="text-muted text-decoration-line-through mt-0.5" style="font-size: 0.74rem;">
                                                    MRP: ₹${fmtMrp}
                                                </div>
                                                <div class="fw-bold text-success mt-0.5" style="font-size: 0.82rem;">
                                                    Disc: ₹${fmtCost}
                                                </div>
                                                <div class="mt-1">
                                                    <span class="badge bg-success-subtle text-success border border-success py-0.5 px-1.5 shadow-xs" style="font-size: 0.68rem; letter-spacing: 0.2px;">
                                                        <i class="bi bi-check2 me-1"></i>Newly Scanned
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column gap-1.5 flex-shrink-0 align-items-center">
                                            <button type="button" class="btn btn-sm btn-success d-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 30px; padding: 0; border-radius: 6px;" onclick="unverifyPiece(${variantId}, '${escapeHtml(pieceTagCode)}');" title="Piece verified! Click to un-verify / undo.">
                                                <i class="bi bi-check-lg" style="font-size: 1.05rem;"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 30px; padding: 0; border-radius: 6px;" onclick="markPieceDamage(${variantId}, '${escapeHtml(pieceTagCode)}');" title="Change to Damage Product">
                                                <i class="bi bi-slash-circle" style="font-size: 0.95rem;"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    } else if (isDamaged) {
                        gridHtml += `
                            <div class="col-12 col-md-6 mb-2">
                                <div class="p-2.5 border rounded-3 bg-danger-subtle border-danger border-opacity-50 d-flex align-items-center justify-content-between gap-2 shadow-xs h-100 position-relative overflow-hidden">
                                    <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                                        <div class="text-center flex-shrink-0" style="width: 82px;">
                                            <div class="inward-piece-qr bg-white p-1 rounded border border-danger-subtle shadow-xs d-flex align-items-center justify-content-center cursor-pointer" 
                                                 id="piece-qr-${i}" 
                                                 data-qr="${escapeHtml(pieceQrPayload)}" 
                                                 onclick="previewSinglePieceTag(${variantId}, ${i}, '${escapeHtml(pieceTagCode)}');" 
                                                 title="Click to view full 2D QR Code Tag" 
                                                 style="width: 80px; height: 80px; cursor: pointer;">
                                            </div>
                                            <div class="badge bg-danger text-white font-monospace mt-1 px-1 py-1 w-100 text-truncate cursor-pointer shadow-xs" 
                                                 onclick="previewSinglePieceTag(${variantId}, ${i}, '${escapeHtml(pieceTagCode)}');" 
                                                 title="${escapeHtml(pieceTagCode)}" 
                                                 style="font-size: 0.68rem; letter-spacing: 0.3px; cursor: pointer;">
                                                ${escapeHtml(pieceTagCode)}
                                            </div>
                                        </div>
                                        <div class="overflow-hidden flex-grow-1 min-w-0" style="line-height: 1.25;">
                                            <div class="fw-bold text-uppercase text-dark text-truncate" style="font-size: 0.80rem; letter-spacing: 0.2px;" title="${escapeHtml(displayBrand)}">
                                                ${escapeHtml(displayBrand)}
                                            </div>
                                            <div class="fw-bold text-secondary text-truncate mt-0.5" style="font-size: 0.76rem;">
                                                ${escapeHtml(displayAttr)}
                                            </div>
                                            <div class="text-muted text-decoration-line-through mt-0.5" style="font-size: 0.74rem;">
                                                MRP: ₹${fmtMrp}
                                            </div>
                                            <div class="fw-bold text-danger mt-0.5" style="font-size: 0.82rem;">
                                                <i class="bi bi-exclamation-octagon-fill me-1"></i>QUARANTINED
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column gap-1.5 flex-shrink-0 align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-success d-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 30px; padding: 0; border-radius: 6px;" onclick="unmarkPieceDamage(${variantId}, '${escapeHtml(pieceTagCode)}');" title="Change to Good Product">
                                            <i class="bi bi-check-lg" style="font-size: 1.05rem;"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger d-flex align-items-center justify-content-center shadow-xs disabled" style="width: 32px; height: 30px; padding: 0; border-radius: 6px; opacity: 0.9;" disabled title="Damage Product">
                                            <i class="bi bi-slash-circle" style="font-size: 0.95rem;"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        gridHtml += `
                            <div class="col-12 col-md-6 mb-2">
                                <div class="p-2.5 border rounded-3 bg-white d-flex align-items-center justify-content-between gap-2 shadow-xs h-100 hover-shadow position-relative overflow-hidden">
                                    <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                                        <div class="text-center flex-shrink-0" style="width: 82px;">
                                            <div class="inward-piece-qr bg-white p-1 rounded border shadow-xs d-flex align-items-center justify-content-center cursor-pointer" 
                                                 id="piece-qr-${i}" 
                                                 data-qr="${escapeHtml(pieceQrPayload)}" 
                                                 onclick="previewSinglePieceTag(${variantId}, ${i}, '${escapeHtml(pieceTagCode)}');" 
                                                 title="Click to view full 2D QR Code Tag" 
                                                 style="width: 80px; height: 80px; cursor: pointer;">
                                            </div>
                                            <div class="badge bg-dark text-warning font-monospace mt-1 px-1 py-1 w-100 text-truncate cursor-pointer shadow-xs" 
                                                 onclick="previewSinglePieceTag(${variantId}, ${i}, '${escapeHtml(pieceTagCode)}');" 
                                                 title="${escapeHtml(pieceTagCode)}" 
                                                 style="font-size: 0.68rem; letter-spacing: 0.3px; cursor: pointer;">
                                                ${escapeHtml(pieceTagCode)}
                                            </div>
                                        </div>
                                        <div class="overflow-hidden flex-grow-1 min-w-0" style="line-height: 1.25;">
                                            <div class="fw-bold text-uppercase text-dark text-truncate" style="font-size: 0.80rem; letter-spacing: 0.2px;" title="${escapeHtml(displayBrand)}">
                                                ${escapeHtml(displayBrand)}
                                            </div>
                                            <div class="fw-bold text-secondary text-truncate mt-0.5" style="font-size: 0.76rem;">
                                                ${escapeHtml(displayAttr)}
                                            </div>
                                            <div class="text-muted text-decoration-line-through mt-0.5" style="font-size: 0.74rem;">
                                                MRP: ₹${fmtMrp}
                                            </div>
                                            <div class="fw-bold text-success mt-0.5" style="font-size: 0.82rem;">
                                                Disc: ₹${fmtCost}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column gap-1.5 flex-shrink-0 align-items-center">
                                        <button type="button" class="btn btn-sm btn-success d-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 30px; padding: 0; border-radius: 6px;" onclick="fastScanVariant(${variantId}, '${escapeHtml(pieceTagCode)}');" title="Mark Good Product">
                                            <i class="bi bi-check-lg" style="font-size: 1.05rem;"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 30px; padding: 0; border-radius: 6px;" onclick="markPieceDamage(${variantId}, '${escapeHtml(pieceTagCode)}');" title="Mark Damage Product">
                                            <i class="bi bi-slash-circle" style="font-size: 0.95rem;"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                }
            }

            if (displayedCount === 0) {
                if (modalPieceFilter === 'mismatched') {
                    gridHtml = `
                        <div class="col-12 text-center py-4">
                            <div class="p-3 bg-success-subtle text-success rounded-3 border border-success-subtle shadow-sm">
                                <i class="bi bi-check-circle-fill fs-3 d-block mb-1"></i>
                                <span class="fw-bold fs-6">All pieces processed!</span>
                                <p class="small text-muted mb-0 mt-1">Zero pending pieces remaining for <strong>${escapeHtml(item.sku)}</strong>.</p>
                            </div>
                        </div>
                    `;
                } else if (modalPieceFilter === 'verified') {
                    gridHtml = `
                        <div class="col-12 text-center py-4">
                            <div class="p-3 bg-light text-muted rounded-3 border">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                <span class="fw-bold">No intact pieces verified yet</span>
                                <p class="small text-muted mb-0 mt-1">Scan or click pieces to mark them as received.</p>
                            </div>
                        </div>
                    `;
                } else if (modalPieceFilter === 'damaged') {
                    gridHtml = `
                        <div class="col-12 text-center py-4">
                            <div class="p-3 bg-light text-muted rounded-3 border">
                                <i class="bi bi-shield-check fs-3 text-success d-block mb-1"></i>
                                <span class="fw-bold text-success">No damaged pieces logged</span>
                                <p class="small text-muted mb-0 mt-1">All pieces in this batch are in healthy intact condition.</p>
                            </div>
                        </div>
                    `;
                } else {
                    gridHtml = `
                        <div class="col-12 text-center py-4 text-muted">
                            <i class="bi bi-box-seam fs-3 d-block mb-1"></i>
                            No pieces found.
                        </div>
                    `;
                }
            }

            piecesGrid.innerHTML = gridHtml;

            // Render live 2D QR Code on each piece card
            if (typeof QRCode !== 'undefined') {
                piecesGrid.querySelectorAll('.inward-piece-qr').forEach(box => {
                    const payload = box.getAttribute('data-qr');
                    if (payload) {
                        box.innerHTML = '';
                        try {
                            new QRCode(box, {
                                text: payload,
                                width: 72,
                                height: 72,
                                colorDark: "#0f172a",
                                colorLight: "#ffffff",
                                correctLevel: (typeof QRCode.CorrectLevel !== 'undefined' && QRCode.CorrectLevel.M !== undefined) ? QRCode.CorrectLevel.M : 0
                            });
                        } catch(err) {
                            console.warn("QR render err:", err);
                        }
                    }
                });
            }
        }
    }

    function fastScanVariant(vid, specificTag = '') {
        const item = ITEMS.find(i => String(i.variant_id) === String(vid));
        if (!item) return;

        const recvInput = document.getElementById(`input-recv-${vid}`);
        const dmgInput = document.getElementById(`input-dmg-${vid}`);
        const expected = parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 1;
        const row = document.getElementById(`row-${vid}`);

        if (item.is_series) {
            const seriesTag = (item.series_qr_code || getLabelCode(item.startSeq, item)).toUpperCase();
            DAMAGED_TAGS.delete(seriesTag);
            SCANNED_TAGS.add(seriesTag);
            if (recvInput) recvInput.value = expected;
            if (dmgInput) dmgInput.value = 0;

            if (row) {
                row.classList.add('table-success');
                setTimeout(() => row.classList.remove('table-success'), 600);
            }
            playBeep('success');
            recalculateMatrix();
            saveInwardDraft();
            showScanFeedback(`✅ Verified series pack <span class="badge bg-primary font-monospace">${escapeHtml(seriesTag)}</span> for <strong>${escapeHtml(item.sku)}</strong> (${expected} pcs).`, 'success');
            if (activeModalVariantId && String(activeModalVariantId) === String(vid)) {
                updateModalState(vid);
            }
            return;
        }

        let tagToVerify = specificTag ? specificTag.toUpperCase() : '';
        if (!tagToVerify) {
            for (let p = item.startSeq; p <= item.endSeq; p++) {
                const code = getLabelCode(p, item).toUpperCase();
                if (!SCANNED_TAGS.has(code) && !DAMAGED_TAGS.has(code)) {
                    tagToVerify = code;
                    break;
                }
            }
        }

        if (tagToVerify && SCANNED_TAGS.has(tagToVerify) && !DAMAGED_TAGS.has(tagToVerify)) {
            playBeep('error');
            if (row) {
                row.classList.add('table-warning');
                setTimeout(() => row.classList.remove('table-warning'), 800);
            }
            showScanFeedback(`⚠️ <strong>Duplicate Scan Blocked:</strong> Piece QR <span class="badge bg-dark font-monospace">${escapeHtml(tagToVerify)}</span> is already verified.`, 'warning');
            return;
        }

        const currentVerified = getVariantVerifiedTagCount(item);
        const currentDamaged = getVariantDamagedTagCount(item);
        if (currentVerified + currentDamaged >= expected && !DAMAGED_TAGS.has(tagToVerify)) {
            playBeep('error');
            if (row) {
                row.classList.add('table-warning');
                setTimeout(() => row.classList.remove('table-warning'), 800);
            }
            showScanFeedback(`⚠️ <strong>Maximum Expected Quantity Reached:</strong> All ${expected} / ${expected} pcs for <strong>${escapeHtml(item.sku)}</strong> are already processed.`, 'warning');
            return;
        }

        if (tagToVerify) {
            DAMAGED_TAGS.delete(tagToVerify);
            SCANNED_TAGS.add(tagToVerify);
        }

        const newVerifiedCount = getVariantVerifiedTagCount(item);
        const newDamagedCount = getVariantDamagedTagCount(item);
        if (recvInput) recvInput.value = newVerifiedCount;
        if (dmgInput) dmgInput.value = newDamagedCount;

        if (row) {
            row.classList.add('table-success');
            setTimeout(() => row.classList.remove('table-success'), 600);
        }
        playBeep('success');
        recalculateMatrix();
        saveInwardDraft();
        showScanFeedback(`✅ Verified piece <span class="badge bg-dark font-monospace">${escapeHtml(tagToVerify || ('Piece #' + newVerifiedCount))}</span> for <strong>${escapeHtml(item.sku)}</strong> (+1 pc). Verified: <strong>${newVerifiedCount} / ${expected} pcs</strong>`, 'success');

        if (activeModalVariantId && String(activeModalVariantId) === String(vid)) {
            updateModalState(vid);
        }
    }

    function markPieceDamage(vid, specificTag = '') {
        const item = ITEMS.find(i => String(i.variant_id) === String(vid));
        if (!item) return;

        const dmgInput = document.getElementById(`input-dmg-${vid}`);
        const recvInput = document.getElementById(`input-recv-${vid}`);
        const expected = parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 1;
        const row = document.getElementById(`row-${vid}`);

        if (item.is_series) {
            const seriesTag = (item.series_qr_code || getLabelCode(item.startSeq, item)).toUpperCase();
            SCANNED_TAGS.delete(seriesTag);
            DAMAGED_TAGS.add(seriesTag);
            if (dmgInput) dmgInput.value = expected;
            if (recvInput) recvInput.value = 0;

            if (row) {
                row.classList.add('table-danger');
                setTimeout(() => row.classList.remove('table-danger'), 600);
            }
            playBeep('double');
            recalculateMatrix();
            saveInwardDraft();
            showScanFeedback(`🔴 Marked series pack <span class="badge bg-danger font-monospace">${escapeHtml(seriesTag)}</span> as DAMAGED for <strong>${escapeHtml(item.sku)}</strong> (${expected} pcs).`, 'danger');
            if (activeModalVariantId && String(activeModalVariantId) === String(vid)) {
                updateModalState(vid);
            }
            return;
        }

        let tagToMark = specificTag ? specificTag.toUpperCase() : '';
        if (!tagToMark) {
            for (let p = item.startSeq; p <= item.endSeq; p++) {
                const code = getLabelCode(p, item).toUpperCase();
                if (!DAMAGED_TAGS.has(code) && !SCANNED_TAGS.has(code)) {
                    tagToMark = code;
                    break;
                }
            }
        }

        if (tagToMark && DAMAGED_TAGS.has(tagToMark)) {
            playBeep('error');
            showScanFeedback(`⚠️ Piece QR <span class="badge bg-danger font-monospace">${escapeHtml(tagToMark)}</span> is already marked as damaged.`, 'warning');
            return;
        }

        if (tagToMark) {
            SCANNED_TAGS.delete(tagToMark);
            DAMAGED_TAGS.add(tagToMark);
        }

        const currentDamaged = getVariantDamagedTagCount(item);
        const currentVerified = getVariantVerifiedTagCount(item);
        if (dmgInput) dmgInput.value = currentDamaged;
        if (recvInput) recvInput.value = currentVerified;

        if (row) {
            row.classList.add('table-danger');
            setTimeout(() => row.classList.remove('table-danger'), 600);
        }
        playBeep('double');
        recalculateMatrix();
        saveInwardDraft();
        showScanFeedback(`🔴 Marked piece <span class="badge bg-danger font-monospace">${escapeHtml(tagToMark || 'Defect piece')}</span> as DAMAGED for <strong>${escapeHtml(item.sku)}</strong>. Damaged: <strong>${currentDamaged} pcs</strong>`, 'danger');

        if (activeModalVariantId && String(activeModalVariantId) === String(vid)) {
            updateModalState(vid);
        }
    }

    function unmarkPieceDamage(vid, tagCode) {
        const item = ITEMS.find(i => String(i.variant_id) === String(vid));
        if (!item) return;

        if (item.is_series) {
            const seriesTag = (item.series_qr_code || getLabelCode(item.startSeq, item)).toUpperCase();
            DAMAGED_TAGS.delete(seriesTag);
            const dmgInput = document.getElementById(`input-dmg-${vid}`);
            if (dmgInput) dmgInput.value = 0;
            playBeep('double');
            recalculateMatrix();
            saveInwardDraft();
            showScanFeedback(`↩️ Removed damage flag on series pack <span class="badge bg-dark font-monospace">${escapeHtml(seriesTag)}</span> for <strong>${escapeHtml(item.sku)}</strong>.`, 'info');
            if (activeModalVariantId && String(activeModalVariantId) === String(vid)) {
                updateModalState(vid);
            }
            return;
        }

        const normalizedTag = (tagCode || '').toUpperCase();
        if (DAMAGED_TAGS.has(normalizedTag)) {
            DAMAGED_TAGS.delete(normalizedTag);
        }

        const dmgInput = document.getElementById(`input-dmg-${vid}`);
        const currentDamaged = getVariantDamagedTagCount(item);
        if (dmgInput) dmgInput.value = currentDamaged;

        playBeep('double');
        recalculateMatrix();
        saveInwardDraft();
        showScanFeedback(`↩️ Removed damage flag on piece <span class="badge bg-dark font-monospace">${escapeHtml(normalizedTag)}</span> for <strong>${escapeHtml(item.sku)}</strong>.`, 'info');

        if (activeModalVariantId && String(activeModalVariantId) === String(vid)) {
            updateModalState(vid);
        }
    }

    function unverifyPiece(vid, tagCode) {
        const item = ITEMS.find(i => String(i.variant_id) === String(vid));
        if (!item) return;

        if (item.is_series) {
            const seriesTag = (item.series_qr_code || getLabelCode(item.startSeq, item)).toUpperCase();
            SCANNED_TAGS.delete(seriesTag);
            const recvInput = document.getElementById(`input-recv-${vid}`);
            if (recvInput) recvInput.value = 0;
            playBeep('double');
            recalculateMatrix();
            saveInwardDraft();
            showScanFeedback(`↩️ Un-verified series pack <span class="badge bg-dark font-monospace">${escapeHtml(seriesTag)}</span> for <strong>${escapeHtml(item.sku)}</strong>.`, 'info');
            if (activeModalVariantId && String(activeModalVariantId) === String(vid)) {
                updateModalState(vid);
            }
            return;
        }

        const expected = parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 1;
        const normalizedTag = (tagCode || '').toUpperCase();
        if (SCANNED_TAGS.has(normalizedTag)) {
            SCANNED_TAGS.delete(normalizedTag);
        }

        const recvInput = document.getElementById(`input-recv-${vid}`);
        const newVerifiedCount = getVariantVerifiedTagCount(item);
        if (recvInput) recvInput.value = newVerifiedCount;

        playBeep('double');
        recalculateMatrix();
        saveInwardDraft();
        showScanFeedback(`↩️ Un-verified piece <span class="badge bg-dark font-monospace">${escapeHtml(normalizedTag)}</span> for <strong>${escapeHtml(item.sku)}</strong>. Verified: <strong>${newVerifiedCount} / ${expected} pcs</strong>`, 'info');

        if (activeModalVariantId && String(activeModalVariantId) === String(vid)) {
            updateModalState(vid);
        }
    }

    function resetVariantPieces(vid) {
        const item = ITEMS.find(i => String(i.variant_id) === String(vid));
        if (!item) return;

        const expected = parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 1;
        for (let p = item.startSeq; p <= item.endSeq; p++) {
            const tag = getLabelCode(p, item).toUpperCase();
            SCANNED_TAGS.delete(tag);
            DAMAGED_TAGS.delete(tag);
        }

        const recvInput = document.getElementById(`input-recv-${vid}`);
        const dmgInput = document.getElementById(`input-dmg-${vid}`);
        if (recvInput) recvInput.value = 0;
        if (dmgInput) dmgInput.value = 0;

        playBeep('double');
        recalculateMatrix();
        saveInwardDraft();
        showScanFeedback(`↩️ Reset all verified &amp; damaged pieces for <strong>${escapeHtml(item.sku)}</strong> (0 / ${expected} pcs).`, 'info');

        if (activeModalVariantId && String(activeModalVariantId) === String(vid)) {
            updateModalState(vid);
        }
    }

    function creditSingleItem(vid) {
        const item = ITEMS.find(i => String(i.variant_id) === String(vid));
        if (!item) return;
        const recvInput = document.getElementById(`input-recv-${vid}`);
        const dmgInput = document.getElementById(`input-dmg-${vid}`);
        const expected = parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 0;
        
        if (recvInput) {
            for (let p = item.startSeq; p <= item.endSeq; p++) {
                const tag = getLabelCode(p, item).toUpperCase();
                SCANNED_TAGS.add(tag);
                DAMAGED_TAGS.delete(tag);
            }
            recvInput.value = expected;
            if (dmgInput) dmgInput.value = 0;
            playBeep('double');
            
            const row = document.getElementById(`row-${vid}`);
            if (row) {
                row.classList.add('table-success');
                setTimeout(() => row.classList.remove('table-success'), 800);
            }
            
            recalculateMatrix();
            saveInwardDraft();
            showScanFeedback(`✅ Item <strong>${escapeHtml(item.sku)}</strong> verified with full expected qty (${expected} pcs)!`, "success");
            if (activeModalVariantId && String(activeModalVariantId) === String(vid)) {
                updateModalState(vid);
            }
        }
    }

    function creditScannedOnly() {
        let totalGood = 0;
        let totalDamaged = 0;
        let missingCount = 0;
        ITEMS.forEach(item => {
            const vid = item.variant_id;
            const recvInput = document.getElementById(`input-recv-${vid}`);
            const dmgInput = document.getElementById(`input-dmg-${vid}`);
            const received = recvInput ? (parseInt(recvInput.value) || 0) : 0;
            const damaged = dmgInput ? (parseInt(dmgInput.value) || 0) : 0;
            const expected = parseInt(item.dispatched_qty > 0 ? item.dispatched_qty : item.requested_qty) || 0;
            totalGood += received;
            totalDamaged += damaged;
            if (received + damaged < expected) missingCount += (expected - received - damaged);
        });

        const totalAccounted = totalGood + totalDamaged;
        if (totalAccounted === 0) {
            alert("⚠️ No items have been scanned or verified yet! Please scan or enter received quantities before crediting.");
            return;
        }

        let confirmMsg = `Confirm crediting ${totalGood} good pcs and quarantining ${totalDamaged} damaged pcs?`;
        if (missingCount > 0) {
            confirmMsg = `⚠️ Note: There are ${missingCount} missing pieces.\n\nDo you want to credit ${totalGood} good pcs and quarantine ${totalDamaged} damaged pcs? Shortages will be recorded in the discrepancy ledger.`;
        }

        if (confirm(confirmMsg)) {
            clearInwardDraft(false);
            const form = document.getElementById('inwardForm');
            if (form) {
                // Attach scanned_tags and damage_tags hidden inputs for each item
                ITEMS.forEach(item => {
                    const vid = item.variant_id;
                    const variantScanned = [];
                    const variantDamaged = [];
                    const recvInput = document.getElementById(`input-recv-${vid}`);
                    const dmgInput = document.getElementById(`input-dmg-${vid}`);
                    const recVal = recvInput ? (parseInt(recvInput.value) || 0) : 0;
                    const dmgVal = dmgInput ? (parseInt(dmgInput.value) || 0) : 0;

                    if (item.is_series) {
                        const seriesTag = (item.series_qr_code || getLabelCode(item.startSeq, item)).toUpperCase();
                        if (recVal > 0) variantScanned.push(seriesTag);
                        if (dmgVal > 0) variantDamaged.push(seriesTag);
                    } else {
                        for (let p = item.startSeq; p <= item.endSeq; p++) {
                            const tag = getLabelCode(p, item).toUpperCase();
                            if (DAMAGED_TAGS.has(tag)) {
                                variantDamaged.push(tag);
                            } else if (SCANNED_TAGS.has(tag)) {
                                variantScanned.push(tag);
                            }
                        }

                        // Fallback if inputs were modified manually
                        if (variantDamaged.length === 0 && dmgVal > 0) {
                            for (let d = 1; d <= dmgVal; d++) {
                                const dTag = getLabelCode(item.startSeq + d - 1, item).toUpperCase();
                                variantDamaged.push(dTag);
                            }
                        }

                        if (variantScanned.length === 0 && recVal > 0) {
                            const startIdx = item.startSeq + variantDamaged.length;
                            for (let s = 0; s < recVal; s++) {
                                const sTag = getLabelCode(startIdx + s, item).toUpperCase();
                                variantScanned.push(sTag);
                            }
                        }
                    }

                    let hiddenScanned = form.querySelector(`input[name="items[${vid}][scanned_tags]"]`);
                    if (!hiddenScanned) {
                        hiddenScanned = document.createElement('input');
                        hiddenScanned.type = 'hidden';
                        hiddenScanned.name = `items[${vid}][scanned_tags]`;
                        form.appendChild(hiddenScanned);
                    }
                    hiddenScanned.value = variantScanned.join(',');

                    let hiddenDamaged = form.querySelector(`input[name="items[${vid}][damage_tags]"]`);
                    if (!hiddenDamaged) {
                        hiddenDamaged = document.createElement('input');
                        hiddenDamaged.type = 'hidden';
                        hiddenDamaged.name = `items[${vid}][damage_tags]`;
                        form.appendChild(hiddenDamaged);
                    }
                    hiddenDamaged.value = variantDamaged.join(',');

                    let hiddenReceived = form.querySelector(`input[name="items[${vid}][received_qty]"]`);
                    if (!hiddenReceived) {
                        hiddenReceived = document.createElement('input');
                        hiddenReceived.type = 'hidden';
                        hiddenReceived.name = `items[${vid}][received_qty]`;
                        form.appendChild(hiddenReceived);
                    }
                    hiddenReceived.value = String(recVal + dmgVal);
                });

                form.submit();
            }
        }
    }

    function showScanFeedback(message, type = 'success') {
        const box = document.getElementById('scanAlertMsg');
        if (!box) return;
        box.className = `alert alert-${type} alert-dismissible fade show mt-3 mb-0`;
        box.innerHTML = `${message} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        box.classList.remove('d-none');
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    // Camera Scanner logic with Html5Qrcode
    let html5QrCode = null;
    function initCameraScanner() {
        const camModal = document.getElementById('cameraScanModal');
        if (!camModal) return;

        camModal.addEventListener('shown.bs.modal', function () {
            if (typeof Html5Qrcode === 'undefined') {
                alert("QR Scanner library loading, please try again in a moment.");
                return;
            }
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("reader");
            }
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    processScanInput(decodedText);
                    const modalInstance = bootstrap.Modal.getInstance(camModal);
                    if (modalInstance) modalInstance.hide();
                },
                (errorMessage) => {}
            ).catch(err => {
                console.error("Camera open failed:", err);
            });
        });

        camModal.addEventListener('hidden.bs.modal', function () {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().catch(err => console.error("Error stopping scanner", err));
            }
        });
    }

    // Window bindings for modal piece actions
    window.fastScanVariant = fastScanVariant;
    window.markPieceDamage = markPieceDamage;
    window.markPieceDamaged = markPieceDamage;
    window.unmarkPieceDamage = unmarkPieceDamage;
    window.markPieceIntact = unmarkPieceDamage;
    window.unverifyPiece = unverifyPiece;
</script>
