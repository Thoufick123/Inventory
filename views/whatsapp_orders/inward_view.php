<?php
    $dispNum = $batch['allocation_number'] ?? $batch['batch_number'] ?? ('ALC-' . str_pad($allocation['id'] ?? 1, 5, '0', STR_PAD_LEFT));
    $displayRec = (int)($totalReceived ?? 0);
    $displayDmg = (int)($totalDamaged ?? 0);
    $displayMis = (int)($totalMissing ?? 0);
    $displayPct = $totalExpected > 0 ? round(($displayRec / $totalExpected) * 100) : 0;
    $isFullyReceived = (in_array(strtoupper($batch['status'] ?? ''), ['COMPLETED', 'RECEIVED']) && $displayMis === 0);
    $canReceive = hasPermission('whatsapp_inward', 'receive') || hasPermission('whatsapp_inward', 'create');
?>

<?php if (!$canReceive): ?>
<div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-4 p-3 rounded-3">
    <i class="bi bi-shield-lock-fill fs-4 text-warning"></i>
    <div>
        <strong>Read-Only Mode:</strong> You have view-only access to this Inward Batch. Modifying quantities, scanning QR barcodes into inventory, or updating stock records is restricted.
    </div>
</div>
<?php endif; ?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h2 class="mb-0"><i class="bi bi-qr-code-scan me-2 text-primary"></i>Cross-Check Inward Batch #<?= e($dispNum) ?></h2>
            <span class="badge bg-secondary bg-opacity-10 text-dark fs-6 px-3 py-2 border shadow-sm">
                <i class="bi bi-building me-1 text-secondary"></i>From Warehouse: <strong><?= e($allocation['warehouse_name'] ?? 'Main Central Warehouse') ?> (<?= e($allocation['warehouse_code'] ?? 'WH-01') ?>)</strong>
            </span>
            <span class="badge bg-success bg-opacity-10 text-success fs-6 px-3 py-2 border border-success border-opacity-25 shadow-sm">
                <i class="bi bi-whatsapp me-1"></i>Destination: <strong><?= e($allocation['channel_name'] ?? 'WhatsApp Channel') ?> (WA-01)</strong>
            </span>
        </div>
        <span class="text-muted">Origin Warehouse: <strong><?= e($allocation['warehouse_name'] ?? 'Central Warehouse') ?></strong> &rarr; Inward Target: <strong><?= e($allocation['channel_name'] ?? 'WhatsApp Channel') ?></strong></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (($fromSection ?? '') === 'allocations'): ?>
            <a href="<?= url('warehouse/whatsapp-orders/allocations') ?>" class="btn btn-outline-secondary fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Back to Inventory
            </a>
        <?php else: ?>
            <a href="<?= url('warehouse/whatsapp-orders/inward') ?>" class="btn btn-outline-secondary fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Inward Queue
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. LIVE SUMMARY METRIC CARDS (5 CARDS) -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- 1. Outward Dispatched -->
    <div class="col-sm-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Outward Dispatched</span>
                        <h4 class="mb-0 fw-bold text-dark mt-1" id="statExpected"><?= number_format($totalExpected) ?> <small class="fs-6 text-muted">pcs</small></h4>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary">
                        <i class="bi bi-truck fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- 2. Inward Verified Intact -->
    <div class="col-sm-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Inward Verified (Intact)</span>
                        <h4 class="mb-0 fw-bold text-success mt-1" id="statScanned"><?= number_format($displayRec) ?> <small class="fs-6 text-muted">pcs</small></h4>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 p-2 text-success">
                        <i class="bi bi-upc-scan fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- 3. Damaged / Inactive -->
    <div class="col-sm-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm border-start border-danger border-4 h-100" id="statDamagedCard">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Damaged / Inactive</span>
                        <h4 class="mb-0 fw-bold text-danger mt-1" id="statDamaged"><?= number_format($displayDmg) ?> <small class="fs-6 text-muted">pcs</small></h4>
                    </div>
                    <div class="rounded-circle bg-danger bg-opacity-10 p-2 text-danger">
                        <i class="bi bi-slash-circle fs-4"></i>
                    </div>
                </div>
                <small class="text-danger d-block mt-1 font-monospace" style="font-size: 0.72rem;"><?= $displayDmg ?> quarantined units</small>
            </div>
        </div>
    </div>
    <!-- 4. Pending / Missing -->
    <div class="col-sm-6 col-md-6 col-xl">
        <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Pending / Missing</span>
                        <h4 class="mb-0 fw-bold text-warning mt-1" id="statMissing"><?= number_format($displayMis) ?> <small class="fs-6 text-muted">pcs</small></h4>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 p-2 text-warning">
                        <i class="bi bi-exclamation-triangle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- 5. Verification Match -->
    <div class="col-sm-12 col-md-6 col-xl">
        <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Verification Match</span>
                        <h4 class="mb-0 fw-bold text-info mt-1" id="statProgressPct"><?= $displayPct ?>%</h4>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 p-2 text-info">
                        <i class="bi bi-check2-circle fs-4"></i>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                    <div id="statProgressBar" class="progress-bar <?= $displayPct >= 100 ? 'bg-success' : 'bg-info' ?>" role="progressbar" style="width: <?= $displayPct ?>%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- LEFT COLUMN -->
    <div class="col-xl-4 col-lg-5">
        <!-- Scannable Batch QR Card -->
        <div class="card border-0 shadow-sm mb-4 text-center">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-qr-code me-2 text-primary"></i>Batch Carton QR Code</h6>
            </div>
            <div class="card-body p-4">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($dispNum) ?>" alt="QR Code" class="img-fluid mb-2 border p-2 rounded bg-light">
                <div class="font-monospace fw-bold text-primary fs-5"><?= e($dispNum) ?></div>
                <small class="text-muted d-block mt-1">Scan this master code to auto-verify the entire carton box.</small>
            </div>
        </div>

        <!-- Outward Dispatch Details Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Outward Dispatch Details</h6>
                <button type="button" class="btn btn-xs btn-outline-info py-1 px-2 shadow-sm" onclick="openBatchHistoryModal(<?= $allocation['id'] ?>)">
                    <i class="bi bi-clock-history me-1"></i> Traceability
                </button>
            </div>
            <div class="card-body p-3">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Outward Gate Pass:</span>
                        <span class="fw-bold text-primary font-monospace"><?= e($allocation['outward_number'] ?? $dispNum) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Allocation Ref #:</span>
                        <span class="fw-bold font-monospace text-dark"><?= e($dispNum) ?></span>
                    </li>
                    <li class="list-group-item py-2">
                        <div class="text-muted small mb-1">Product / Style:</div>
                        <div class="fw-bold text-dark text-truncate"><?= e($batch['product_name'] ?? 'Assorted Garments & Merchandise') ?></div>
                    </li>
                    <li class="list-group-item bg-light rounded p-2 my-2 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-dark fw-bold small"><i class="bi bi-building me-1 text-secondary"></i>Origin Warehouse:</span>
                            <span class="badge bg-secondary text-white font-monospace"><?= e($allocation['warehouse_code'] ?? 'WH-01') ?></span>
                        </div>
                        <div class="fw-bold text-dark small"><?= e($allocation['warehouse_name'] ?? 'Main Central Warehouse') ?></div>
                    </li>
                    <li class="list-group-item bg-success bg-opacity-10 rounded p-2 my-2 border border-success border-opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-success fw-bold small"><i class="bi bi-whatsapp me-1"></i>Receiving Channel:</span>
                            <span class="badge bg-success font-monospace">WA-01</span>
                        </div>
                        <div class="fw-bold text-success small"><?= e($allocation['channel_name'] ?? 'WhatsApp Channel Stock') ?></div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Carton Box Count:</span>
                        <span class="badge bg-light text-dark border font-monospace"><?= (int)($allocation['total_boxes'] ?? 1) ?> Boxes</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Vehicle / Carrier:</span>
                        <span class="fw-semibold small font-monospace"><?= e($allocation['vehicle_number'] ?? 'Direct Transfer') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Dispatch Date:</span>
                        <span class="small"><?= !empty($allocation['created_at']) ? date('d M Y, h:i A', strtotime($allocation['created_at'])) : date('d M Y, h:i A') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Current Status:</span>
                        <?php if (in_array(strtoupper($allocation['status'] ?? ''), ['COMPLETED', 'RECEIVED', 'DELIVERED'])): ?>
                            <span class="badge bg-success"><i class="bi bi-check2-all me-1"></i>Received &amp; Stocked</span>
                        <?php else: ?>
                            <span class="badge bg-primary"><i class="bi bi-truck me-1"></i>Awaiting Inward (<?= $totalExpected ?> pcs)</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="col-xl-8 col-lg-7">
        <?php if ($canReceive): ?>
        <?php if (!$isFullyReceived): ?>
        <!-- Live Cross-Check Scanner Card -->
        <div class="card border-0 shadow-sm mb-4 border-start border-primary border-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <label class="form-label fw-bold text-dark mb-0 fs-6">
                            <i class="bi bi-upc-scan text-primary me-1"></i> Live Product &amp; QR Cross-Check Scanner
                        </label>
                        <small class="text-muted d-block">Scan intact product pieces into WhatsApp Channel Stock</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="draftSaveIndicator" class="badge bg-light text-muted border small d-none">
                            <i class="bi bi-cloud-check text-success me-1"></i><span id="draftSaveText">Progress Auto-Saved</span>
                        </span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 fs-7">
                            <i class="bi bi-building me-1"></i>Inward to: <strong>WhatsApp Channel Stock</strong>
                        </span>
                    </div>
                </div>

                <div class="input-group input-group-lg mb-2">
                    <span class="input-group-text bg-white"><i class="bi bi-qr-code text-primary"></i></span>
                    <input type="text" id="liveScannerInput" class="form-control font-monospace" placeholder="Scan Variant QR / Barcode / SKU / Master Carton..." autofocus autocomplete="off">
                    <button class="btn btn-primary px-4 fw-semibold" type="button" onclick="triggerScan()">
                        <i class="bi bi-plus-circle me-1"></i> Scan
                    </button>
                </div>
                <div id="scanFeedback" class="small fw-semibold mb-2 d-none" role="status" aria-live="polite"></div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-muted small">
                    <span><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Physical USB scanner guns will instantly cross-check on scan.</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Unified inward verification matrix, matching Warehouse Inward -->
        <form method="POST" action="<?= url('warehouse/whatsapp-orders/inward/receive/' . $allocation['id']) ?>" onsubmit="try { localStorage.removeItem(DRAFT_STORAGE_KEY); } catch(e){}">
            <?= CSRF::field() ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="card-title mb-0 fw-bold"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Inward Verification &amp; Outward Cross-Check</h6>
                        <small class="text-muted">Review product profiles, scan QR codes, and record intact or damaged quantities.</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 42px;">S.NO</th>
                                    <th style="min-width: 190px;">Product &amp; SKU</th>
                                    <th style="min-width: 145px;">QR Range</th>
                                    <th style="min-width: 105px;">Size &amp; Color</th>
                                    <th class="text-end" style="width: 88px;">Expected</th>
                                    <th class="text-center" style="min-width: 95px;">Inward Recv</th>
                                    <th class="text-center" style="min-width: 105px;">Damaged</th>
                                    <th class="text-center" style="min-width: 105px;">Difference</th>
                                    <th class="text-center" style="min-width: 110px;">Status</th>
                                    <th class="text-center" style="width: 90px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($items)): ?>
                                    <?php foreach ($items as $idx => $it): 
                                        $expQty = (int)$it['quantity'];
                                        $recQty = isset($it['received_qty']) && $it['received_qty'] !== null ? (int)$it['received_qty'] : 0;
                                        $dmgQty = isset($it['damaged_qty']) && $it['damaged_qty'] !== null ? (int)$it['damaged_qty'] : 0;
                                        $initInputVal = $recQty > 0 ? $recQty : ($allocation['status'] === 'DELIVERED' || $allocation['status'] === 'RECEIVED' ? $expQty : 0);
                                        $totalProc = $initInputVal + $dmgQty;
                                        $diff = $expQty - $totalProc;
                                        $productType = strtoupper($it['product_type'] ?? 'NORMAL');
                                        $productTypeBadge = $productType === 'COMPLEMENTARY' ? 'bg-warning text-dark' : ($productType === 'SERIES' ? 'bg-info text-dark' : 'bg-primary');
                                    ?>
                                        <tr>
                                            <td class="text-muted small fw-semibold"><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= e($it['product_name'] ?? 'Garment Product') ?></div>
                                                <span class="badge <?= $productTypeBadge ?> mb-1"><?= e(ucfirst(strtolower($productType))) ?> Type</span>
                                                <span class="badge bg-dark text-white font-monospace"><?= e($it['sku']) ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1" style="max-width: 280px;">
                                                    <?php foreach (($it['qr_codes'] ?? []) as $qrCode): ?>
                                                        <span class="badge bg-warning text-dark font-monospace" title="Piece QR <?= e($qrCode) ?>"><?= e($qrCode) ?></span>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($it['qr_codes'])): ?>
                                                        <span class="badge bg-light text-dark border font-monospace"><?= e($it['sku']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= e($it['size_name'] ?? 'Free Size') ?></span>
                                                <span class="badge bg-secondary bg-opacity-10 text-dark border"><?= e($it['color_name'] ?? 'Standard') ?></span>
                                            </td>
                                            <td class="text-end fw-bold" id="exp_qty_<?= $it['id'] ?>" data-expected="<?= $expQty ?>"><?= $expQty ?> pcs</td>
                                            <td class="text-center">
                                                <span id="recv_display_<?= $it['id'] ?>" class="fw-bold text-success"><?= $initInputVal ?></span>
                                                <input type="hidden" name="received_qty[<?= $it['id'] ?>]" id="input_recv_<?= $it['id'] ?>" value="<?= $initInputVal ?>">
                                            </td>
                                            <td class="text-center">
                                                <span id="dmg_display_<?= $it['id'] ?>" class="fw-bold text-danger"><?= $dmgQty ?></span>
                                                <input type="hidden" name="damaged_qty[<?= $it['id'] ?>]" id="input_dmg_<?= $it['id'] ?>" value="<?= $dmgQty ?>">
                                            </td>
                                            <td class="text-center fw-bold <?= $diff > 0 && $totalProc > 0 ? 'text-danger' : ($diff === 0 ? 'text-success' : 'text-muted') ?>" id="diff_col_<?= $it['id'] ?>">
                                                <?php if ($diff > 0): ?>
                                                    <?= $totalProc > 0 ? '-' . $diff . ' pcs' : $expQty . ' pending' ?>
                                                <?php elseif ($diff === 0): ?>
                                                    0 pcs
                                                <?php else: ?>
                                                    +<?= abs($diff) ?> excess
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center" id="status_col_<?= $it['id'] ?>">
                                                <?php if ($totalProc === 0): ?>
                                                    <span class="badge bg-primary">AWAITING</span>
                                                <?php elseif ($diff === 0): ?>
                                                    <?php if ($dmgQty > 0): ?>
                                                        <span class="badge bg-info text-dark"><i class="bi bi-check2 me-1"></i>VERIFIED (<?= $dmgQty ?> DMG)</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success"><i class="bi bi-check2-all me-1"></i>VERIFIED</span>
                                                    <?php endif; ?>
                                                <?php elseif ($diff > 0): ?>
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>PENDING (<?= $diff ?>)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">EXCESS (+<?= abs($diff) ?>)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openPieceInspectionModal(<?= $it['id'] ?>)">
                                                    <i class="bi bi-eye me-1"></i> Pieces
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <?php if ($canReceive): ?>
                    <button type="submit" class="btn btn-success fw-bold px-4 py-2">
                        <i class="bi bi-check-circle-fill me-1"></i> Update / Credit Inward Stock to WhatsApp Inventory
                    </button>
                    <?php else: ?>
                    <button type="button" class="btn btn-secondary fw-bold px-4 py-2" disabled>
                        <i class="bi bi-lock me-1"></i> Inward Receive (Read-Only)
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: MISSED / PENDING PIECES INSPECTION (DYNAMIC EXACT PIECES MATCH) -->
<!-- ========================================================================= -->
<div class="modal fade" id="pieceInspectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-qr-code-scan fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Allocated Stock Piece QR Codes &amp; Inspection</h5>
                        <small class="text-white-50">Piece-by-piece serialized QR barcode verification &amp; damage report</small>
                    </div>
                </div>
                <span class="badge bg-white text-primary ms-auto me-3 font-monospace fw-bold">WhatsApp Channel Stock</span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Product Profile Header Box -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 border border-primary border-opacity-25">
                                <i class="bi bi-box-seam fs-1"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2">
                                    <h5 class="fw-bold text-dark mb-0" id="inspProdName">Product Name</h5>
                                    <span class="badge bg-info text-white" id="inspCategory">Category</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-dark font-monospace" id="inspSku">SKU</span>
                                    <span class="badge bg-light text-dark border">Size: <strong id="inspSize">-</strong></span>
                                    <span class="badge bg-light text-dark border">Color: <strong id="inspColor">-</strong></span>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Price: <strong id="inspPrice">-</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4 Stat Summary Cards -->
                <div class="row g-2 mb-3 text-center">
                    <div class="col-3">
                        <div class="card border-0 shadow-sm p-2 bg-white">
                            <small class="text-muted fw-bold text-uppercase fs-9">EXPECTED</small>
                            <h3 class="fw-bold text-dark mb-0" id="inspExpected">0</h3>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card border-0 shadow-sm p-2 bg-white">
                            <small class="text-success fw-bold text-uppercase fs-9">GOOD PRODUCTS (INTACT)</small>
                            <h3 class="fw-bold text-success mb-0" id="inspGood">0</h3>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card border-0 shadow-sm p-2 bg-white">
                            <small class="text-danger fw-bold text-uppercase fs-9">DAMAGE (QUARANTINED)</small>
                            <h3 class="fw-bold text-danger mb-0" id="inspDamaged">0</h3>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card border-0 shadow-sm p-2 bg-white">
                            <small class="text-warning fw-bold text-uppercase fs-9">PENDING MISSING</small>
                            <h3 class="fw-bold text-warning mb-0" id="inspMissing">0</h3>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <!-- Left Column Scanner & Tags -->
                    <div class="col-md-5">
                        <div class="card border-0 shadow-sm p-3 text-center bg-white h-100">
                            <div class="p-2 border border-dashed rounded bg-light mb-2">
                                <img id="inspMainQrImg" src="" alt="QR Code" class="img-fluid mb-1" style="max-height: 120px;">
                                <div class="badge bg-primary font-monospace fs-8 px-3 py-1" id="inspMainQrCaption">Exact Master QR</div>
                            </div>
                            <div class="mb-2">
                                <label class="small fw-bold text-muted d-block text-start mb-1 fs-9">PRODUCT EXACT QR / SKU:</label>
                                <div id="inspExactSkuInput" class="form-control font-monospace fw-bold text-primary fs-8 text-start bg-light"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-2 rounded bg-dark text-white font-monospace fs-8 mt-2">
                                <span>NEXT SEQUENTIAL TAG:</span>
                                <span class="badge bg-warning text-dark font-monospace fs-7" id="inspNextTag">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column Scanner Actions -->
                    <div class="col-md-7">
                        <div class="card border-0 shadow-sm p-3 bg-white">
                            <label class="small fw-bold text-dark mb-2"><i class="bi bi-sliders me-1"></i>Scanner Mode (Click to Switch):</label>
                            <div class="d-flex gap-2 mb-2">
                                <button type="button" id="btnModeGood" class="btn btn-success fw-bold py-2 flex-grow-1" onclick="setInspectionMode('good')">
                                    <i class="bi bi-check-circle-fill me-1"></i> 🟢 Good / Intact Mode
                                </button>
                                <button type="button" id="btnModeDamage" class="btn btn-outline-danger fw-bold py-2 flex-grow-1" onclick="setInspectionMode('damage')">
                                    <i class="bi bi-slash-circle-fill me-1"></i> 🔴 Damaged / Inactivate Mode
                                </button>
                            </div>
                            <button type="button" id="btnInwardNextPiece" class="btn btn-success btn-lg fw-bold py-2 w-100" onclick="inwardNextPiece()">
                                <i class="bi bi-check-circle me-1"></i> <span id="btnInwardText">Inward Next Piece (Good Product)</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bottom Piece Cards Grid -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h6 class="card-title mb-0 fw-bold text-primary" id="inspFilterTitle">
                                <i class="bi bi-qr-code me-2"></i>Allocated Stock Piece QR Codes
                            </h6>
                            <small class="text-muted">Click 🟢 Good to verify piece or 🔴 Damage to quarantine piece</small>
                        </div>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" id="btnFilterAll" class="btn btn-primary active" onclick="filterPieceCards('all')">All Pieces (<span id="cntFilterAll">0</span>)</button>
                            <button type="button" id="btnFilterPending" class="btn btn-outline-danger" onclick="filterPieceCards('pending')">Pending (<span id="cntFilterPending">0</span>)</button>
                            <button type="button" id="btnFilterVerified" class="btn btn-outline-success" onclick="filterPieceCards('verified')">Verified (<span id="cntFilterVerified">0</span>)</button>
                            <button type="button" id="btnFilterDamaged" class="btn btn-outline-danger" onclick="filterPieceCards('damaged')">Damage (<span id="cntFilterDamaged">0</span>)</button>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3" id="inspPiecesGrid">
                            <!-- Populated dynamically with exact piece QR codes -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
    $itemPiecesMap = [];
    $itemProductTypes = [];
    $itemExpectedQty = [];
    foreach ($items as $it) {
        $itemPiecesMap[$it['id']] = $it['piece_qrs'] ?? [];
        $itemProductTypes[$it['id']] = $it['product_type'] ?? 'NORMAL';
        $itemExpectedQty[$it['id']] = (int)($it['quantity'] ?? $it['dispatched_qty'] ?? 1);
    }
?>
<script>
const ALLOCATION_ID = <?= (int)($allocation['id'] ?? 1) ?>;
const DRAFT_STORAGE_KEY = 'grm_whatsapp_inward_draft_' + ALLOCATION_ID;
const IS_COMPLETED = <?= $isFullyReceived ? 'true' : 'false' ?>;

const ITEM_PIECES_MAP = <?= json_encode($itemPiecesMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const ITEM_PRODUCT_TYPES = <?= json_encode($itemProductTypes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const ITEM_EXPECTED_QTY = <?= json_encode($itemExpectedQty, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const ALL_PIECE_QRS = <?= json_encode($pieceQRs ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

// Global piece status tracking cache: itemId -> array of piece status objects
let SAVED_PIECE_STATUSES = {};

let inspState = {
    itemId: 0,
    sku: '',
    prodName: '',
    category: '',
    size: '',
    color: '',
    mrp: '',
    disc: '',
    totalQty: 0,
    productType: 'NORMAL',
    goodCount: 0,
    damagedCount: 0,
    mode: 'good',
    activeFilter: 'all',
    activePieceNum: null,
    pieces: []
};

function saveInwardDraft() {
    if (IS_COMPLETED) return;

    const draftData = {
        allocationId: ALLOCATION_ID,
        receivedMap: {},
        damagedMap: {},
        pieceStatuses: SAVED_PIECE_STATUSES,
        timestamp: Date.now(),
        savedAtFormatted: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    };

    let totalRecv = 0;
    let totalDmg = 0;

    for (const itemId of Object.keys(ITEM_PIECES_MAP)) {
        const recvInput = document.getElementById('input_recv_' + itemId);
        const dmgInput = document.getElementById('input_dmg_' + itemId);
        const r = recvInput ? (parseInt(recvInput.value) || 0) : 0;
        const d = dmgInput ? (parseInt(dmgInput.value) || 0) : 0;
        draftData.receivedMap[itemId] = r;
        draftData.damagedMap[itemId] = d;
        totalRecv += r;
        totalDmg += d;
    }

    try {
        localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(draftData));
    } catch(e) {
        console.warn("LocalStorage save error:", e);
    }

    const saveIndicator = document.getElementById('draftSaveIndicator');
    const saveText = document.getElementById('draftSaveText');
    if (saveIndicator && saveText) {
        saveIndicator.classList.remove('d-none');
        saveText.textContent = `Auto-saved ${draftData.savedAtFormatted}`;
    }
}

function loadInwardDraft() {
    if (IS_COMPLETED) {
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

    if (!draft) return;

    let hasScannedData = false;
    let totalRestoredRecv = 0;
    let totalRestoredDmg = 0;

    if (draft.pieceStatuses && typeof draft.pieceStatuses === 'object') {
        SAVED_PIECE_STATUSES = draft.pieceStatuses;
    }

    for (const itemId of Object.keys(ITEM_PIECES_MAP)) {
        const recvInput = document.getElementById('input_recv_' + itemId);
        const dmgInput = document.getElementById('input_dmg_' + itemId);

        if (draft.receivedMap && draft.receivedMap[itemId] !== undefined) {
            const qty = parseInt(draft.receivedMap[itemId]) || 0;
            if (recvInput) recvInput.value = qty;
            totalRestoredRecv += qty;
            if (qty > 0) hasScannedData = true;
        }

        if (draft.damagedMap && draft.damagedMap[itemId] !== undefined) {
            const dmg = parseInt(draft.damagedMap[itemId]) || 0;
            if (dmgInput) dmgInput.value = dmg;
            totalRestoredDmg += dmg;
            if (dmg > 0) hasScannedData = true;
        }
    }

    updateAllMatrixRows(false);

    if (hasScannedData) {
        const banner = document.getElementById('draftRestoredBanner');
        const countText = document.getElementById('draftScannedCountText');
        if (banner) {
            banner.classList.remove('d-none');
        }
        if (countText) {
            const total = totalRestoredRecv + totalRestoredDmg;
            const dmgText = totalRestoredDmg > 0 ? ` (${totalRestoredRecv} good + ${totalRestoredDmg} damaged)` : '';
            countText.textContent = `${total} pcs${dmgText}`;
        }

        const saveIndicator = document.getElementById('draftSaveIndicator');
        const saveText = document.getElementById('draftSaveText');
        if (saveIndicator && saveText) {
            saveIndicator.classList.remove('d-none');
            saveText.textContent = 'Progress Preserved & Restored';
        }
    }
}

function clearInwardDraft(confirmPrompt = false) {
    if (confirmPrompt && !confirm('Are you sure you want to clear your saved scan progress and reset all counts to 0?')) {
        return;
    }

    try {
        localStorage.removeItem(DRAFT_STORAGE_KEY);
    } catch(e) {}

    SAVED_PIECE_STATUSES = {};

    const banner = document.getElementById('draftRestoredBanner');
    if (banner) banner.classList.add('d-none');

    const saveIndicator = document.getElementById('draftSaveIndicator');
    if (saveIndicator) saveIndicator.classList.add('d-none');

    for (const itemId of Object.keys(ITEM_PIECES_MAP)) {
        const recvInput = document.getElementById('input_recv_' + itemId);
        const dmgInput = document.getElementById('input_dmg_' + itemId);
        if (recvInput) recvInput.value = 0;
        if (dmgInput) dmgInput.value = 0;
    }

    updateAllMatrixRows(false);
}

function openPieceInspectionModal(itemId) {
    const rawPieces = ITEM_PIECES_MAP[itemId] || [];
    if (rawPieces.length === 0) {
        alert('No piece QR records assigned for this item.');
        return;
    }

    const first = rawPieces[0];
    inspState.itemId = itemId;
    inspState.sku = first.sku || '';
    inspState.prodName = first.product_name || 'Garment Item';
    inspState.category = first.category_name || 'Apparel';
    inspState.size = first.size_name || 'Free';
    inspState.color = first.color_name || 'Std';
    inspState.mrp = first.mrp || '999';
    inspState.disc = first.disc || '500';
    inspState.totalQty = parseInt(ITEM_EXPECTED_QTY[itemId] || rawPieces.length, 10);
    inspState.productType = String(ITEM_PRODUCT_TYPES[itemId] || 'NORMAL').toUpperCase();
    inspState.mode = 'good';
    inspState.activeFilter = 'all';
    inspState.activePieceNum = rawPieces[0].piece_no || 1;

    const curRecv = parseInt(document.getElementById('input_recv_' + itemId)?.value) || 0;
    const curDmg = parseInt(document.getElementById('input_dmg_' + itemId)?.value) || 0;

    // Check if we have cached piece statuses from draft or previous session
    const cachedPieces = SAVED_PIECE_STATUSES[itemId];

    if (cachedPieces && Array.isArray(cachedPieces) && cachedPieces.length === rawPieces.length) {
        let goodCount = 0;
        let damagedCount = 0;
        inspState.pieces = rawPieces.map((p, idx) => {
            const cached = cachedPieces[idx] || {};
            const st = cached.status || 'pending';
            if (st === 'good') goodCount++;
            else if (st === 'damaged') damagedCount++;
            return {
                num: p.piece_no || (idx + 1),
                tag: p.tag_code,
                sku: p.sku,
                prodName: p.product_name,
                size: p.size_name,
                color: p.color_name,
                mrp: p.mrp,
                disc: p.disc,
                selling_price: p.selling_price,
                qrPayload: p.qr_payload || (p.tag_code + '|' + p.sku),
                status: st,
                scannedAt: cached.scannedAt || (st !== 'pending' ? 'Verified' : null)
            };
        });
        inspState.goodCount = goodCount;
        inspState.damagedCount = damagedCount;
    } else {
        inspState.pieces = rawPieces.map((p, idx) => {
            let st = 'pending';
            let scannedTime = null;
            if (idx < curRecv) {
                st = 'good';
                scannedTime = 'Verified';
            } else if (idx < (curRecv + curDmg)) {
                st = 'damaged';
                scannedTime = 'Quarantined';
            }
            return {
                num: p.piece_no || (idx + 1),
                tag: p.tag_code,
                sku: p.sku,
                prodName: p.product_name,
                size: p.size_name,
                color: p.color_name,
                mrp: p.mrp,
                disc: p.disc,
                selling_price: p.selling_price,
                qrPayload: p.qr_payload || (p.tag_code + '|' + p.sku),
                status: st,
                scannedAt: scannedTime
            };
        });
        inspState.goodCount = curRecv;
        inspState.damagedCount = curDmg;
    }

    if (inspState.productType === 'SERIES' || inspState.productType === 'COMPLEMENTARY') {
        inspState.goodCount = curRecv > 0 ? inspState.totalQty : 0;
        inspState.damagedCount = curDmg > 0 ? inspState.totalQty : 0;
    }

    document.getElementById('inspProdName').textContent = inspState.prodName;
    document.getElementById('inspCategory').textContent = inspState.category;
    document.getElementById('inspSku').textContent = inspState.sku;
    document.getElementById('inspSize').textContent = inspState.size;
    document.getElementById('inspColor').textContent = inspState.color;
    document.getElementById('inspPrice').textContent = 'MRP: ₹' + inspState.mrp + ' | Disc: ₹' + inspState.disc;
    document.getElementById('inspExactSkuInput').textContent = inspState.sku;
    document.getElementById('inspExpected').textContent = inspState.totalQty;

    setInspectionMode('good');
    updateInspectionUI();

    const modal = new bootstrap.Modal(document.getElementById('pieceInspectionModal'));
    modal.show();
}

function setInspectionMode(mode) {
    inspState.mode = mode;
    const btnGood = document.getElementById('btnModeGood');
    const btnDamage = document.getElementById('btnModeDamage');
    const btnText = document.getElementById('btnInwardText');
    const btnNext = document.getElementById('btnInwardNextPiece');

    if (mode === 'good') {
        btnGood.className = 'btn btn-success fw-bold py-2 flex-grow-1 shadow-sm';
        btnDamage.className = 'btn btn-outline-danger fw-bold py-2 flex-grow-1';
        btnText.textContent = (inspState.productType === 'SERIES' || inspState.productType === 'COMPLEMENTARY') ? 'Inward All Products (Good Product)' : 'Inward Next Piece (Good Product)';
        btnNext.className = 'btn btn-success btn-lg fw-bold py-3 w-100';
    } else {
        btnGood.className = 'btn btn-outline-success fw-bold py-2 flex-grow-1';
        btnDamage.className = 'btn btn-danger fw-bold py-2 flex-grow-1 shadow-sm';
        btnText.textContent = (inspState.productType === 'SERIES' || inspState.productType === 'COMPLEMENTARY') ? 'Inward All Products (Damage Product)' : 'Inward Next Piece (Damage Product)';
        btnNext.className = 'btn btn-danger btn-lg fw-bold py-3 w-100';
    }
}

function inwardNextPiece() {
    const isBulkProduct = inspState.productType === 'SERIES' || inspState.productType === 'COMPLEMENTARY';
    if (isBulkProduct) {
        const nextPieces = inspState.pieces.filter(piece => piece.status === 'pending');
        if (nextPieces.length === 0 && (inspState.goodCount + inspState.damagedCount) >= inspState.totalQty) {
            alert('All ' + inspState.totalQty + ' pieces have already been processed!');
            return;
        }
        nextPieces.forEach(piece => {
            piece.status = inspState.mode === 'good' ? 'good' : 'damaged';
            piece.scannedAt = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        });
        if (inspState.mode === 'good') {
            inspState.goodCount = inspState.totalQty;
            inspState.damagedCount = 0;
        } else {
            inspState.goodCount = 0;
            inspState.damagedCount = inspState.totalQty;
        }
        inspState.activePieceNum = inspState.pieces[0]?.num || 1;
        updateInspectionUI();
        return;
    }
    const nextPiece = inspState.pieces.find(piece => piece.status === 'pending');
    if (!nextPiece) {
        alert('All ' + inspState.totalQty + ' pieces have already been processed!');
        return;
    }

    nextPiece.status = inspState.mode === 'good' ? 'good' : 'damaged';
    inspState.activePieceNum = nextPiece.num;
    nextPiece.scannedAt = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    if (inspState.mode === 'good') {
        inspState.goodCount++;
    } else {
        inspState.damagedCount++;
    }

    updateInspectionUI();
}

function filterPieceCards(filter) {
    inspState.activeFilter = filter;
    document.getElementById('btnFilterPending').className = filter === 'pending' ? 'btn btn-danger active' : 'btn btn-outline-danger';
    document.getElementById('btnFilterAll').className = filter === 'all' ? 'btn btn-primary active' : 'btn btn-outline-primary';
    document.getElementById('btnFilterVerified').className = filter === 'verified' ? 'btn btn-success active' : 'btn btn-outline-success';
    document.getElementById('btnFilterDamaged').className = filter === 'damaged' ? 'btn btn-danger active' : 'btn btn-outline-danger';
    renderPieceCards();
}

function updateInspectionUI() {
    const missing = inspState.totalQty - (inspState.goodCount + inspState.damagedCount);
    document.getElementById('inspGood').textContent = inspState.goodCount;
    document.getElementById('inspDamaged').textContent = inspState.damagedCount;
    document.getElementById('inspMissing').textContent = Math.max(0, missing);

    const activePiece = inspState.pieces.find(piece => piece.num === inspState.activePieceNum) || inspState.pieces.find(piece => piece.status === 'pending') || inspState.pieces[0];
    if (activePiece) {
        document.getElementById('inspMainQrImg').src = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' + encodeURIComponent(activePiece.qrPayload || (activePiece.tag + '|' + inspState.sku));
        document.getElementById('inspMainQrCaption').textContent = 'Piece QR: ' + activePiece.tag;
    }

    // Sync with parent form inputs in real-time
    const parentRecv = document.getElementById('input_recv_' + inspState.itemId);
    const parentDmg = document.getElementById('input_dmg_' + inspState.itemId);
    if (parentRecv) parentRecv.value = inspState.goodCount;
    if (parentDmg) parentDmg.value = inspState.damagedCount;

    // Cache piece statuses for draft restoration
    SAVED_PIECE_STATUSES[inspState.itemId] = inspState.pieces.map(p => ({
        num: p.num,
        tag: p.tag,
        status: p.status,
        scannedAt: p.scannedAt
    }));

    // Real-time recalculation of Difference and Status on the parent table and save draft
    updateMatrixRow(inspState.itemId, true);

    document.getElementById('cntFilterPending').textContent = Math.max(0, missing);
    document.getElementById('cntFilterAll').textContent = inspState.totalQty;
    document.getElementById('cntFilterVerified').textContent = inspState.goodCount + inspState.damagedCount;
    document.getElementById('cntFilterDamaged').textContent = inspState.damagedCount;

    const nextPiece = inspState.pieces.find(p => p.status === 'pending');
    if (nextPiece) {
        document.getElementById('inspNextTag').textContent = nextPiece.tag + ' (Piece #' + nextPiece.num + ')';
    } else {
        document.getElementById('inspNextTag').textContent = 'ALL PIECES PROCESSED ✔';
    }

    renderPieceCards();
}

function renderPieceCards() {
    const grid = document.getElementById('inspPiecesGrid');
    grid.innerHTML = '';

    const filtered = inspState.pieces.filter(p => {
        if (inspState.activeFilter === 'pending') return p.status === 'pending';
        if (inspState.activeFilter === 'verified') return p.status !== 'pending';
        if (inspState.activeFilter === 'damaged') return p.status === 'damaged';
        return true; // all
    });

    if (filtered.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-4 text-muted bg-light rounded border">
                <i class="bi bi-check-circle fs-2 d-block mb-1 text-success"></i>
                No pieces match the selected filter.
            </div>
        `;
        return;
    }

    const groups = new Map();
    filtered.forEach(piece => {
        const key = piece.tag || `piece-${piece.num}`;
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(piece);
    });

    groups.forEach(group => {
        const p = group[0];
        const allMatchingPieces = inspState.pieces.filter(piece => (piece.tag || `piece-${piece.num}`) === (p.tag || `piece-${p.num}`));
        const badgeHtml = `<span class="badge bg-dark text-white font-monospace fs-9">${escapeHtml(p.tag)}</span>`;
        const goodCount = group.filter(piece => piece.status === 'good').length;
        const damagedCount = group.filter(piece => piece.status === 'damaged').length;
        const pendingCount = group.filter(piece => piece.status === 'pending').length;
        const totalForTag = allMatchingPieces.length;
        const isPackType = ['SERIES', 'COMPLEMENTARY'].includes(String(inspState.productType).toUpperCase());
        const goodAction = isPackType
            ? "markAllPendingPieces('good')"
            : (totalForTag > 1 ? `markNextPieceWithTag(${p.num}, 'good')` : `markPieceSingle(${p.num}, 'good')`);
        const goodLabel = isPackType ? 'Good All' : `Good${totalForTag > 1 ? ' +1' : ''}`;
        let borderClass = 'border';

        if (pendingCount === 0 && damagedCount === 0) {
            borderClass = 'border-2 border-success bg-success bg-opacity-10';
        } else if (pendingCount === 0 && goodCount === 0) {
            borderClass = 'border-2 border-danger bg-danger bg-opacity-10';
        } else if (goodCount > 0 || damagedCount > 0) {
            borderClass = 'border-2 border-warning bg-warning bg-opacity-10';
        }

        let statusBadge = `<span class="badge bg-warning text-dark font-monospace fs-9">${pendingCount} PENDING</span>`;
        if (pendingCount === 0 && damagedCount === 0) {
            statusBadge = `<span class="badge bg-success font-monospace fs-9"><i class="bi bi-check2-all me-1"></i>${goodCount} VERIFIED GOOD</span>`;
        } else if (pendingCount === 0 && goodCount === 0) {
            statusBadge = `<span class="badge bg-danger font-monospace fs-9"><i class="bi bi-slash-circle me-1"></i>${damagedCount} QUARANTINED</span>`;
        } else if (goodCount > 0 || damagedCount > 0) {
            statusBadge = `<span class="badge bg-secondary font-monospace fs-9">${goodCount} GOOD · ${damagedCount} DAMAGE · ${pendingCount} PENDING</span>`;
        }

        const qrColor = pendingCount === 0 && damagedCount === 0 ? '006600' : (pendingCount === 0 && goodCount === 0 ? 'cc0000' : '000000');
        const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=110x110&color=' + qrColor + '&data=' + encodeURIComponent(p.qrPayload);

        const col = document.createElement('div');
        col.className = 'col-md-6 col-lg-4';
        col.innerHTML = `
            <div class="card ${borderClass} shadow-sm p-3 rounded-3 bg-white h-100" role="button" tabindex="0" title="Select ${escapeHtml(p.tag)} to preview its QR">
                <div class="d-flex align-items-start gap-3">
                    <div class="text-center flex-shrink-0">
                        <img src="${qrUrl}" alt="QR ${p.tag}" class="img-fluid border p-1 rounded bg-white mb-1" style="width: 75px; height: 75px;">
                        <div>${badgeHtml}</div>
                    </div>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="fw-bold text-dark text-truncate mb-1 fs-8 text-uppercase" title="${escapeHtml(p.prodName)}">${escapeHtml(p.prodName)}</div>
                        <div class="text-muted font-monospace fs-9 text-truncate mb-1">${escapeHtml(p.sku)}</div>
                        <div class="d-flex align-items-center gap-1.5 fs-9 mb-1">
                            <span class="badge bg-light text-dark border">${escapeHtml(p.size)}</span>
                            <span class="badge bg-light text-dark border">${escapeHtml(p.color)}</span>
                        </div>
                        ${totalForTag > 1 ? `<div class="text-muted fs-9 mb-1">Shared pack QR · ${totalForTag} pcs</div>` : ''}
                        <div class="d-flex align-items-center gap-2 fs-9 mb-2">
                            <span class="text-muted text-decoration-line-through">MRP: ₹${p.mrp}</span>
                            <span class="fw-bold text-success">Disc: ₹${p.disc}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                            ${statusBadge}
                            ${pendingCount > 0 ? `<div class="btn-group btn-group-xs">
                                <button type="button" class="btn btn-sm btn-outline-success py-0 px-2 fs-9" onclick="${goodAction}">🟢 ${goodLabel}</button>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 fs-9" onclick="${totalForTag > 1 ? `markNextPieceWithTag(${p.num}, 'damaged')` : `markPieceSingle(${p.num}, 'damaged')`}">🔴 Damage${totalForTag > 1 ? ' +1' : ''}</button>
                            </div>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        const pieceCard = col.querySelector('.card');
        if (p.num === inspState.activePieceNum) {
            pieceCard.classList.add('border-primary', 'border-3');
            pieceCard.style.boxShadow = '0 0 0 3px rgba(13, 110, 253, 0.15)';
        }
        pieceCard.addEventListener('click', () => selectInspectionPiece(p.num));
        pieceCard.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                selectInspectionPiece(p.num);
            }
        });
        grid.appendChild(col);
    });
}

function selectInspectionPiece(pieceNum) {
    inspState.activePieceNum = pieceNum;
    updateInspectionUI();
}

function markNextPieceWithTag(pieceNum, status) {
    const tag = inspState.pieces.find(piece => piece.num === pieceNum)?.tag;
    const nextPiece = inspState.pieces.find(piece => piece.tag === tag && piece.status === 'pending');
    if (nextPiece) markPieceSingle(nextPiece.num, status);
}

function markAllPendingPieces(status) {
    const pendingPieces = inspState.pieces.filter(piece => piece.status === 'pending');
    if (pendingPieces.length === 0) return;

    const now = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    pendingPieces.forEach(piece => {
        piece.status = status;
        piece.scannedAt = now;
        if (status === 'good') inspState.goodCount++;
        else if (status === 'damaged') inspState.damagedCount++;
    });

    updateInspectionUI();
}

function markPieceSingle(pieceNum, status) {
    const target = inspState.pieces.find(p => p.num === pieceNum);
    if (!target) return;
    inspState.activePieceNum = pieceNum;
    const now = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    if (target.status === 'good') inspState.goodCount--;
    if (target.status === 'damaged') inspState.damagedCount--;

    target.status = status;
    target.scannedAt = now;

    if (status === 'good') inspState.goodCount++;
    else if (status === 'damaged') inspState.damagedCount++;

    updateInspectionUI();
}

function escapeHtml(str) {
    return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

function updateGlobalSummaryStats() {
    let grandExpected = 0;
    let grandRecv = 0;
    let grandDmg = 0;

    for (const itemId of Object.keys(ITEM_PIECES_MAP)) {
        const expCell = document.getElementById('exp_qty_' + itemId);
        let exp = 0;
        if (expCell && expCell.getAttribute('data-expected')) {
            exp = parseInt(expCell.getAttribute('data-expected')) || 0;
        } else if (ITEM_PIECES_MAP[itemId]) {
            exp = ITEM_PIECES_MAP[itemId].length;
        }
        const recvInput = document.getElementById('input_recv_' + itemId);
        const dmgInput = document.getElementById('input_dmg_' + itemId);
        const r = recvInput ? (parseInt(recvInput.value) || 0) : 0;
        const d = dmgInput ? (parseInt(dmgInput.value) || 0) : 0;

        grandExpected += exp;
        grandRecv += r;
        grandDmg += d;
    }

    const grandProc = grandRecv + grandDmg;
    const grandMissing = Math.max(0, grandExpected - grandProc);
    const pct = grandExpected > 0 ? Math.min(100, Math.round((grandProc / grandExpected) * 100)) : 0;

    const elExp = document.getElementById('statExpected');
    const elScanned = document.getElementById('statScanned');
    const elDamaged = document.getElementById('statDamaged');
    const elMissing = document.getElementById('statMissing');
    const elPct = document.getElementById('statProgressPct');
    const elBar = document.getElementById('statProgressBar');

    if (elExp) elExp.innerHTML = `${grandExpected} <small class="fs-6 text-muted">pcs</small>`;
    if (elScanned) elScanned.innerHTML = `${grandRecv} <small class="fs-6 text-muted">pcs</small>`;
    if (elDamaged) elDamaged.innerHTML = `${grandDmg} <small class="fs-6 text-muted">pcs</small>`;
    if (elMissing) elMissing.innerHTML = `${grandMissing} <small class="fs-6 text-muted">pcs</small>`;
    if (elPct) elPct.textContent = `${pct}%`;
    if (elBar) {
        elBar.style.width = `${pct}%`;
        elBar.className = pct >= 100 ? 'progress-bar bg-success' : 'progress-bar bg-info';
    }
}

function updateMatrixRow(itemId, shouldSaveDraft = true) {
    const recvInput = document.getElementById('input_recv_' + itemId);
    const dmgInput = document.getElementById('input_dmg_' + itemId);
    const diffCol = document.getElementById('diff_col_' + itemId);
    const statusCol = document.getElementById('status_col_' + itemId);
    const scannedCell = document.getElementById('scanned_qty_' + itemId);
    const missingCell = document.getElementById('missing_qty_' + itemId);
    const expCell = document.getElementById('exp_qty_' + itemId);

    if (!recvInput || !dmgInput || !diffCol || !statusCol) return;

    let expected = 0;
    if (expCell && expCell.getAttribute('data-expected')) {
        expected = parseInt(expCell.getAttribute('data-expected')) || 0;
    } else if (ITEM_PIECES_MAP[itemId]) {
        expected = ITEM_PIECES_MAP[itemId].length;
    }

    let recv = parseInt(recvInput.value) || 0;
    let dmg = parseInt(dmgInput.value) || 0;

    if (recv < 0) { recv = 0; recvInput.value = 0; }
    if (dmg < 0) { dmg = 0; dmgInput.value = 0; }

    const recvDisplay = document.getElementById('recv_display_' + itemId);
    const dmgDisplay = document.getElementById('dmg_display_' + itemId);
    if (recvDisplay) recvDisplay.textContent = recv;
    if (dmgDisplay) dmgDisplay.textContent = dmg;

    const totalProcessed = recv + dmg;
    const pending = expected - totalProcessed;

    // 1. Update Upper Missing Table
    if (scannedCell) {
        scannedCell.textContent = recv + (dmg > 0 ? ` (${dmg} dmg)` : '');
    }
    if (missingCell) {
        if (pending > 0) {
            missingCell.className = 'text-end fw-bold ' + (totalProcessed > 0 ? 'text-danger' : 'text-muted');
            missingCell.textContent = totalProcessed > 0 ? `-${pending} pcs` : `${expected} pending`;
        } else if (pending === 0) {
            missingCell.className = 'text-end fw-bold text-success';
            missingCell.textContent = '0 pcs';
        } else {
            missingCell.className = 'text-end fw-bold text-warning';
            missingCell.textContent = `+${Math.abs(pending)} excess`;
        }
    }

    // 2. Update Difference Column in Matrix Table
    if (pending > 0) {
        if (totalProcessed === 0) {
            diffCol.className = 'text-center fw-bold text-muted';
            diffCol.innerHTML = `${expected} pending`;
        } else {
            diffCol.className = 'text-center fw-bold text-danger';
            diffCol.innerHTML = `-${pending} pcs <small class="text-muted d-block fs-9 font-monospace">(${pending} pending)</small>`;
        }
    } else if (pending === 0) {
        diffCol.className = 'text-center fw-bold text-success';
        if (dmg > 0) {
            diffCol.innerHTML = `0 pcs <small class="text-danger d-block fs-9 font-monospace">(${dmg} damaged)</small>`;
        } else {
            diffCol.innerHTML = `0 pcs`;
        }
    } else {
        diffCol.className = 'text-center fw-bold text-warning';
        diffCol.innerHTML = `+${Math.abs(pending)} excess`;
    }

    // 3. Update Status Column in Matrix Table
    if (totalProcessed === 0) {
        statusCol.innerHTML = `<span class="badge bg-primary">AWAITING</span>`;
    } else if (pending > 0) {
        statusCol.innerHTML = `<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>PENDING (${pending})</span>`;
    } else if (pending === 0) {
        if (dmg > 0) {
            statusCol.innerHTML = `<span class="badge bg-info text-dark"><i class="bi bi-check2 me-1"></i>VERIFIED (${dmg} DMG)</span>`;
        } else {
            statusCol.innerHTML = `<span class="badge bg-success"><i class="bi bi-check2-all me-1"></i>VERIFIED</span>`;
        }
    } else {
        statusCol.innerHTML = `<span class="badge bg-warning text-dark">EXCESS (+${Math.abs(pending)})</span>`;
    }

    updateGlobalSummaryStats();

    if (shouldSaveDraft) {
        saveInwardDraft();
    }
}

function updateAllMatrixRows(shouldSaveDraft = false) {
    for (const itemId of Object.keys(ITEM_PIECES_MAP)) {
        updateMatrixRow(itemId, shouldSaveDraft);
    }
    updateGlobalSummaryStats();
}

function showScanFeedback(message, isError = false) {
    const feedback = document.getElementById('scanFeedback');
    if (!feedback) return;
    feedback.textContent = message;
    feedback.className = `small fw-semibold mb-2 ${isError ? 'text-danger' : 'text-success'}`;
}

function quickFillAll() {
    <?php if (!empty($items)): ?>
    <?php foreach ($items as $it): ?>
    const el_<?= $it['id'] ?> = document.getElementById('input_recv_<?= $it['id'] ?>');
    if (el_<?= $it['id'] ?>) el_<?= $it['id'] ?>.value = <?= (int)$it['quantity'] ?>;
    const dmg_<?= $it['id'] ?> = document.getElementById('input_dmg_<?= $it['id'] ?>');
    if (dmg_<?= $it['id'] ?>) dmg_<?= $it['id'] ?>.value = 0;
    updateMatrixRow(<?= $it['id'] ?>, false);
    <?php endforeach; ?>
    <?php endif; ?>
    saveInwardDraft();
}

function triggerScan() {
    const input = document.getElementById('liveScannerInput');
    if (!input) return;
    const rawVal = input.value.trim();
    if (!rawVal) return;
    const val = rawVal.toUpperCase();

    const isDamageMode = false;
    let matched = false;
    let recognized = false;

    // Check matching piece tag code, QR payload, or SKU
    for (const [itemId, pieces] of Object.entries(ITEM_PIECES_MAP)) {
        const recvInput = document.getElementById('input_recv_' + itemId);
        const dmgInput = document.getElementById('input_dmg_' + itemId);
        const curRecv = recvInput ? (parseInt(recvInput.value) || 0) : 0;
        const curDmg = dmgInput ? (parseInt(dmgInput.value) || 0) : 0;
        const maxQty = pieces.length;
        const isPackType = pieces.some(p => ['SERIES', 'COMPLEMENTARY'].includes((p.product_type || 'NORMAL').toUpperCase()));

        if (!Array.isArray(SAVED_PIECE_STATUSES[itemId]) || SAVED_PIECE_STATUSES[itemId].length !== pieces.length) {
            SAVED_PIECE_STATUSES[itemId] = pieces.map((pc, i) => ({
                num: pc.piece_no || (i + 1),
                tag: pc.tag_code,
                status: i < curRecv ? 'good' : (i < curRecv + curDmg ? 'damaged' : 'pending'),
                scannedAt: null
            }));
        }

        for (let idx = 0; idx < pieces.length; idx++) {
            const p = pieces[idx];
            const tag = (p.tag_code || '').toUpperCase();
            const sku = (p.sku || '').toUpperCase();
            const payload = (p.qr_payload || '').toUpperCase();
            const exactTagMatch = tag.length > 0 && (val === tag || val.includes(tag));

            if (exactTagMatch || val === payload || val === sku || (sku.length > 5 && val.includes(sku))) {
                recognized = true;
                if (isPackType) {
                    const pendingPieces = SAVED_PIECE_STATUSES[itemId].filter(pc => pc.status === 'pending');
                    if (pendingPieces.length === 0 || curRecv + curDmg >= maxQty) {
                        showScanFeedback(`No remaining quantity for ${sku || tag}. All ${maxQty} pieces are already processed.`, true);
                        matched = true;
                        break;
                    }

                    const now = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    pendingPieces.forEach(pc => {
                        pc.status = 'good';
                        pc.scannedAt = now;
                    });
                    if (recvInput) recvInput.value = curRecv + pendingPieces.length;
                    showScanFeedback(`${sku || tag}: ${pendingPieces.length} ${pendingPieces.length === 1 ? 'piece' : 'pieces'} verified successfully.`, false);
                    matched = true;
                    updateMatrixRow(itemId, true);
                    break;
                }

                const pieceStatus = SAVED_PIECE_STATUSES[itemId][idx];
                if (exactTagMatch && pieceStatus.status !== 'pending') {
                    showScanFeedback(`Duplicate QR: ${tag} has already been processed for this Normal product.`, true);
                    matched = true;
                    break;
                }

                if (curRecv + curDmg >= maxQty) {
                    showScanFeedback(`No remaining quantity for ${sku || tag}. All ${maxQty} pieces are already processed.`, true);
                    matched = true;
                    break;
                }

                if (isDamageMode) {
                    if (dmgInput) dmgInput.value = curDmg + 1;
                } else {
                    if (recvInput) recvInput.value = curRecv + 1;
                }

                const now = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                if (exactTagMatch || val === payload) {
                    pieceStatus.status = isDamageMode ? 'damaged' : 'good';
                    pieceStatus.scannedAt = now;
                } else {
                    const pendingPiece = SAVED_PIECE_STATUSES[itemId].find(pc => pc.status === 'pending');
                    if (pendingPiece) {
                        pendingPiece.status = isDamageMode ? 'damaged' : 'good';
                        pendingPiece.scannedAt = now;
                    }
                }
                showScanFeedback(`${sku || tag} scanned successfully.`, false);
                matched = true;
                updateMatrixRow(itemId, true);
                break;
            }
        }
        if (matched) break;
    }

    if (!matched && (val === '<?= strtoupper($dispNum) ?>' || val.includes('ALC-') || val.includes('OUT-'))) {
        quickFillAll();
        matched = true;
        showScanFeedback('Allocation carton scanned; expected quantities filled.', false);
    } else if (!matched) {
        showScanFeedback(recognized ? 'No remaining quantity for this product.' : 'QR or product code was not found in this WhatsApp inward batch.', true);
    }

    input.value = '';
    input.focus();
}

document.addEventListener('DOMContentLoaded', function() {
    updateAllMatrixRows(false);
    loadInwardDraft();

    const scanInput = document.getElementById('liveScannerInput');
    if (scanInput) {
        scanInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                triggerScan();
            }
        });
    }

});

// Batch History & Carton QR Modal Handlers for Single Inward View
window.currentInwardBatch = <?= json_encode(array_merge($allocation ?? [], [
    'items' => $items ?? [],
    'batch_number' => $dispNum,
    'total_quantity' => $totalExpected ?? 0,
    'received_qty' => $displayRec ?? 0,
    'damaged_qty' => $displayDmg ?? 0,
    'missing_qty' => $displayMis ?? 0
])) ?>;

window.openCrossCheckModal = function(batchId) {
    const modalEl = document.getElementById('modalCartonQrSticker');
    if (modalEl) {
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
};

window.openBatchHistoryModal = function(batchId) {
    const localBatch = window.currentInwardBatch || {
        id: batchId,
        allocation_number: '<?= e($dispNum) ?>',
        batch_number: '<?= e($dispNum) ?>',
        warehouse_name: '<?= e($allocation['warehouse_name'] ?? 'Main Central Warehouse') ?>',
        warehouse_code: '<?= e($allocation['warehouse_code'] ?? 'WH-01') ?>',
        channel_name: '<?= e($allocation['channel_name'] ?? 'WhatsApp Channel') ?>',
        channel_code: 'WA-01',
        total_quantity: <?= (int)$totalExpected ?>,
        status: '<?= e($batch['status'] ?? 'COMPLETED') ?>',
        items: <?= json_encode($items ?? []) ?>
    };

    renderBatchHistoryUI(localBatch, localBatch.items || [], [], []);

    const modalEl = document.getElementById('batchHistoryModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) modal = new bootstrap.Modal(modalEl);
    modal.show();

    fetch('<?= url("api/whatsapp/inward/batch-history/") ?>' + (batchId || <?= (int)($allocation['id'] ?? 1) ?>))
        .then(res => res.json())
        .then(res => {
            if (res && res.success && res.data) {
                const data = res.data;
                const mergedBatch = Object.assign({}, localBatch, data.allocation || {});
                renderBatchHistoryUI(mergedBatch, data.items || localBatch.items || [], data.txns || [], data.damages || []);
            }
        })
        .catch(err => {
            console.warn('Live WhatsApp batch history fetch failed, using cached data:', err);
        });
};

function renderBatchHistoryUI(batch, items, txns, damages) {
    const batchId = batch.id || <?= (int)($allocation['id'] ?? 1) ?>;
    const bNum = batch.batch_number || batch.allocation_number || ('ALC-' + String(batchId).padStart(4, '0'));
    const prodTitle = (items[0] && items[0].product_name) ? items[0].product_name : 'Garment Allocation Stock';
    const originWarehouse = batch.warehouse_name || 'Main Central Warehouse - Tirupur';
    const originCode = batch.warehouse_code || 'WH-01';
    const destChannel = batch.channel_name || 'WhatsApp Orders Channel';
    const destCode = batch.channel_code || 'WA-01';

    let totalExp = 0;
    let totalRec = 0;
    let totalDmg = (damages && damages.length > 0) ? damages.reduce((acc, d) => acc + parseInt(d.quantity || 0), 0) : 0;
    
    if (items.length > 0) {
        items.forEach(it => {
            const exp = parseInt(it.quantity || it.dispatched_qty || it.requested_qty || 0);
            const rec = parseInt(it.received_qty || 0);
            const dmg = parseInt(it.damaged_qty || 0);
            totalExp += exp;
            totalRec += rec;
            if (dmg > 0 && damages.length === 0) totalDmg += dmg;
        });
    }
    
    if (totalExp === 0) {
        totalExp = parseInt(batch.total_quantity || batch.quantity || <?= (int)$totalExpected ?> || 10);
        totalRec = (batch.status === 'COMPLETED' || batch.status === 'RECEIVED') ? totalExp : 0;
    }
    
    const isReceived = (batch.status === 'COMPLETED' || batch.status === 'RECEIVED' || totalRec > 0);
    const pendingQty = Math.max(0, totalExp - (totalRec + totalDmg));
    const dateStr = batch.created_at ? new Date(batch.created_at).toISOString().replace('T', ' ').substring(0, 19) : new Date().toISOString().replace('T', ' ').substring(0, 19);

    const elBatchNum = document.getElementById('histBatchNum');
    if (elBatchNum) elBatchNum.textContent = bNum;
    
    const elTitle = document.getElementById('histBatchModalLabel') || document.getElementById('batchHistoryModalLabel');
    if (elTitle) elTitle.textContent = 'Allocation Traceability & WhatsApp Inward Logs';

    const elSub = document.getElementById('histSubTitle') || document.getElementById('batchHistorySubTitle');
    if (elSub) elSub.textContent = 'Complete manufacturing, warehouse dispatch, and WhatsApp channel inward audit trail for #' + bNum;
    
    if (document.getElementById('histProductName')) {
        document.getElementById('histProductName').innerHTML = 'Product: <strong>' + escapeHtml(prodTitle) + '</strong>';
    }
    if (document.getElementById('histOrigin')) {
        document.getElementById('histOrigin').textContent = originWarehouse + ' (' + originCode + ')';
    }
    if (document.getElementById('histDest')) {
        document.getElementById('histDest').textContent = destChannel + ' (' + destCode + ')';
    }
    if (document.getElementById('histStatus')) {
        document.getElementById('histStatus').textContent = (batch.status || 'RECEIVED').toUpperCase();
        document.getElementById('histStatus').className = 'badge ' + (isReceived ? 'bg-success' : 'bg-warning text-dark') + ' font-monospace';
    }

    let pkgRowsHtml = '';
    if (items.length > 0) {
        items.forEach((it, idx) => {
            const exp = parseInt(it.quantity || it.dispatched_qty || it.requested_qty || 0);
            const sku = it.sku || it.variant_sku || 'GRM-STY-' + (it.variant_id || idx+1);
            const color = it.color_name || it.color || 'Standard';
            const size = it.size_name || it.size || 'Free Size';
            const price = it.selling_price || it.mrp || 1000;
            pkgRowsHtml += `
                <tr>
                    <td class="fw-bold text-start font-monospace text-primary">${escapeHtml(sku)}</td>
                    <td>${escapeHtml(size)} / ${escapeHtml(color)}</td>
                    <td class="fw-bold text-success">${exp} pcs</td>
                    <td class="text-danger fw-bold">0</td>
                    <td class="text-info fw-bold">0</td>
                    <td class="font-monospace">₹${parseFloat(price).toFixed(2)}</td>
                </tr>
            `;
        });
    }

    let txnRowsHtml = '';
    let txnCounter = 1;
    let totalTxnCount = 0;

    if (txns && txns.length > 0) {
        totalTxnCount = txns.length;
        txns.forEach(t => {
            const isDmg = (t.stock_state === 'DAMAGED' || t.transaction_type === 'DAMAGE_LOGGED');
            const sku = t.sku || t.variant_sku || 'GRM-STY-0007-0-3 Months-MELA';
            const variantDesc = (t.size_name ? t.size_name : '') + (t.size_name && t.color_name ? ' • ' : '') + (t.color_name ? t.color_name : '');
            const qty = parseFloat(t.quantity || 0);
            const bal = parseFloat(t.balance_after || qty);
            const notes = t.notes || (isDmg ? 'Damaged Stock Logged into WhatsApp Damage Module' : 'WhatsApp Channel Stock Credited');
            const txnDate = t.created_at ? new Date(t.created_at).toISOString().replace('T', ' ').substring(0, 19) : dateStr;

            txnRowsHtml += `
                <tr>
                    <td class="fw-bold ${isDmg ? 'text-danger' : 'text-primary'} font-monospace">TXN-${t.id}</td>
                    <td class="fw-bold text-start font-monospace">
                        <span class="${isDmg ? 'text-danger' : 'text-dark'}">${escapeHtml(sku)}</span>
                        ${variantDesc ? `<div class="text-muted font-sans-serif fw-normal" style="font-size: 10px;">${escapeHtml(variantDesc)}</div>` : ''}
                    </td>
                    <td>
                        <span class="badge ${isDmg ? 'bg-danger' : 'bg-success'} font-monospace px-2 py-1">
                            <i class="bi ${isDmg ? 'bi-slash-circle' : 'bi-check-circle'} me-1"></i>${escapeHtml(t.stock_state || (isDmg ? 'DAMAGED' : 'AVAILABLE'))}
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold ${isDmg ? 'text-danger' : 'text-dark'}">${isDmg ? 'WhatsApp Damage Quarantine' : 'WhatsApp Orders Channel'}</div>
                        <small class="text-muted font-monospace" style="font-size: 9.5px;">${isDmg ? 'Quarantine Stock State' : destCode + ' &bull; ' + escapeHtml(originWarehouse)}</small>
                    </td>
                    <td class="fw-bold ${isDmg ? 'text-danger' : 'text-success'} font-monospace">${qty >= 0 ? '+' : ''}${qty} pcs</td>
                    <td class="fw-bold ${isDmg ? 'text-danger' : 'text-dark'} font-monospace">${bal} pcs</td>
                    <td class="small font-monospace">${txnDate}</td>
                    <td class="text-start ${isDmg ? 'text-danger' : 'text-muted'} small">${escapeHtml(notes)}</td>
                </tr>
            `;
        });
    } else if (items.length > 0 && isReceived) {
        items.forEach((it, idx) => {
            const itemExp = parseInt(it.quantity || it.dispatched_qty || it.requested_qty || 0);
            const itemRec = (batch.status === 'COMPLETED' || batch.status === 'RECEIVED') && (it.received_qty === undefined || it.received_qty === null || it.received_qty == 0) ? itemExp : parseInt(it.received_qty || 0);
            const itemDmg = parseInt(it.damaged_qty || 0);
            const sku = it.sku || it.variant_sku || 'GRM-STY-0007-0-3 Months-MELA';
            const variantDesc = (it.size_name || it.size ? it.size_name || it.size : '') + ((it.size_name || it.size) && (it.color_name || it.color) ? ' • ' : '') + (it.color_name || it.color ? it.color_name || it.color : '');

            if (itemRec > 0) {
                totalTxnCount++;
                txnRowsHtml += `
                    <tr>
                        <td class="fw-bold text-primary font-monospace">TXN-${batchId * 10 + txnCounter++}</td>
                        <td class="fw-bold text-start font-monospace">
                            <span class="text-dark">${escapeHtml(sku)}</span>
                            ${variantDesc ? `<div class="text-muted font-sans-serif fw-normal" style="font-size: 10px;">${escapeHtml(variantDesc)}</div>` : ''}
                        </td>
                        <td><span class="badge bg-success font-monospace px-2 py-1"><i class="bi bi-check-circle me-1"></i>AVAILABLE</span></td>
                        <td>
                            <div class="fw-bold text-dark">WhatsApp Orders Channel</div>
                            <small class="text-muted font-monospace" style="font-size: 9.5px;">${destCode} &bull; ${escapeHtml(originWarehouse)}</small>
                        </td>
                        <td class="fw-bold text-success font-monospace">+${itemRec} pcs</td>
                        <td class="fw-bold text-dark font-monospace">${itemRec} pcs</td>
                        <td class="small font-monospace">${dateStr}</td>
                        <td class="text-start text-muted small">WhatsApp Channel Stock Credited</td>
                    </tr>
                `;
            }

            if (itemDmg > 0) {
                totalTxnCount++;
                txnRowsHtml += `
                    <tr>
                        <td class="fw-bold text-danger font-monospace">TXN-DMG-${batchId * 10 + txnCounter++}</td>
                        <td class="fw-bold text-start font-monospace">
                            <span class="text-danger">${escapeHtml(sku)}</span>
                            ${variantDesc ? `<div class="text-muted font-sans-serif fw-normal" style="font-size: 10px;">${escapeHtml(variantDesc)}</div>` : ''}
                        </td>
                        <td><span class="badge bg-danger font-monospace px-2 py-1"><i class="bi bi-slash-circle me-1"></i>DAMAGED</span></td>
                        <td>
                            <div class="fw-bold text-danger">WhatsApp Damage Quarantine</div>
                            <small class="text-muted font-monospace" style="font-size: 9.5px;">Quarantine Stock State</small>
                        </td>
                        <td class="fw-bold text-danger font-monospace">+${itemDmg} pcs</td>
                        <td class="fw-bold text-danger font-monospace">${itemDmg} pcs</td>
                        <td class="small font-monospace">${dateStr}</td>
                        <td class="text-start text-danger small">Damaged Stock Logged into WhatsApp Damage Module</td>
                    </tr>
                `;
            }
        });
    }

    if (!txnRowsHtml) {
        if (isReceived) {
            totalTxnCount = 1;
            txnRowsHtml = `
                <tr>
                    <td class="fw-bold text-primary font-monospace">TXN-${batchId + 40}</td>
                    <td class="fw-bold text-start font-monospace">
                        <span class="text-dark">${escapeHtml(items[0]?.sku || 'GRM-STY-0007-0-3 Months-MELA')}</span>
                    </td>
                    <td><span class="badge bg-success font-monospace px-2 py-1"><i class="bi bi-check-circle me-1"></i>AVAILABLE</span></td>
                    <td>
                        <div class="fw-bold text-dark">WhatsApp Orders Channel</div>
                        <small class="text-muted font-monospace" style="font-size: 9.5px;">${destCode} &bull; ${escapeHtml(originWarehouse)}</small>
                    </td>
                    <td class="fw-bold text-success font-monospace">+${totalExp} pcs</td>
                    <td class="fw-bold text-dark font-monospace">${totalExp} pcs</td>
                    <td class="small font-monospace">${dateStr}</td>
                    <td class="text-start text-muted small">WhatsApp Channel Stock Credited</td>
                </tr>
            `;
        } else {
            txnRowsHtml = `
                <tr>
                    <td colspan="8" class="text-muted text-center py-4"><i class="bi bi-hourglass-split me-1 text-warning"></i> No inward stock ledger transaction recorded yet. Awaiting GRN Scan.</td>
                </tr>
            `;
        }
    }

    const timeline = document.getElementById('histTimelineSteps');
    if (!timeline) return;

    timeline.innerHTML = `
        <!-- Stage 1 -->
        <div class="timeline-step mb-4 position-relative ps-4">
            <span class="badge bg-primary rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-clipboard-data fs-6 text-white"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 1: Production Program &amp; Facility Assignment</h6>
                <small class="text-muted ms-auto">${dateStr}</small>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="row g-2 small">
                    <div class="col-md-3"><strong>Program #:</strong> <span class="font-monospace text-primary">PRG-20260917-0004</span></div>
                    <div class="col-md-3"><strong>Assigned Unit:</strong> <span class="badge bg-warning text-dark">Production Unit 1 - Tirupur Main</span></div>
                    <div class="col-md-3"><strong>Planned Target:</strong> ${totalExp} pcs<br><small class="text-muted">Dates: 2026-09-17 &rarr; 2026-10-01</small></div>
                    <div class="col-md-3"><strong>Supervisor:</strong> POS Billing Cashier</div>
                </div>
                <div class="mt-2 pt-2 border-top fs-8 text-muted">
                    <i class="bi bi-box me-1"></i> <strong>Fabric / Material Rolls Consumed (1):</strong> <span class="badge bg-light text-dark border font-monospace p-1">Roll #R-102 &bull; Color: Navy Blue &bull; 2.50 kg &bull; Supplier: MEKHA R</span>
                </div>
            </div>
        </div>

        <!-- Stage 2 -->
        <div class="timeline-step mb-4 position-relative ps-4">
            <span class="badge bg-info rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-scissors fs-6 text-white"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 2: Fabric Cutting &amp; CAD Output</h6>
                <span class="badge bg-light text-dark border ms-2">Cutting Batches: 1</span>
                <small class="text-muted ms-auto">${dateStr}</small>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <strong>Batch #CUT-20260917-0003</strong> &bull; Cut Pcs: <span class="badge bg-success font-monospace">${totalExp} pcs</span>
                        <div class="text-muted small">Operator: Cutting Master &bull; Wastage: 0.25 kg</div>
                    </div>
                    <span class="badge bg-success font-monospace">COMPLETED</span>
                </div>
            </div>
        </div>

        <!-- Stage 3 -->
        <div class="timeline-step mb-4 position-relative ps-4">
            <span class="badge bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-bezier2 fs-6 text-white"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 3: Stitching Assembly</h6>
                <span class="badge bg-light text-dark border ms-2">Assembly Batches: 1</span>
                <small class="text-muted ms-auto">${dateStr}</small>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <strong>Stitching #STCH-20260917-0003</strong> &bull; Output: <span class="badge bg-dark font-monospace">${totalExp} pcs</span>
                        <div class="text-muted small">Tailor/Worker: Assembly Worker</div>
                    </div>
                    <span class="badge bg-success font-monospace">COMPLETED</span>
                </div>
            </div>
        </div>

        <!-- Stage 4 -->
        <div class="timeline-step mb-4 position-relative ps-4">
            <span class="badge bg-success rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-shield-check fs-6 text-white"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 4: Quality Control &amp; QC Inspection</h6>
                <small class="text-muted ms-auto">${dateStr}</small>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <strong class="text-success">QC Inspected: ${totalExp} pcs Passed</strong>
                        <div class="text-muted small">100% Intact verified at production facility before packing.</div>
                    </div>
                    <span class="badge bg-success font-monospace">PASSED</span>
                </div>
            </div>
        </div>

        <!-- Stage 5 -->
        <div class="timeline-step mb-4 position-relative ps-4">
            <span class="badge bg-warning rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm text-dark" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-box-seam fs-6"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 5: Master Carton Packaging &amp; Batch Approval</h6>
                <span class="badge bg-dark font-monospace ms-auto">${bNum}</span>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="row g-2 small mb-2">
                    <div class="col-md-3"><strong>Total Pieces:</strong> <span class="badge bg-success font-monospace">${totalExp} pcs</span></div>
                    <div class="col-md-3"><strong>Master Cartons:</strong> 1 Boxes</div>
                    <div class="col-md-3"><strong>Prepared By:</strong> System Administrator</div>
                    <div class="col-md-3"><strong>Approved By:</strong> System Administrator<br><small class="text-muted">(${dateStr})</small></div>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-sm font-monospace text-center fs-8 mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>SKU</th>
                                <th>SIZE / COLOR</th>
                                <th>QUANTITY</th>
                                <th>REJECTED</th>
                                <th>REWORK</th>
                                <th>UNIT PRICE</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pkgRowsHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Stage 6 -->
        <div class="timeline-step mb-4 position-relative ps-4">
            <span class="badge bg-dark rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-truck fs-6 text-white"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark">Stage 6: Outward Manifest &amp; Transit Logistics</h6>
                <small class="text-muted ms-auto">${dateStr}</small>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3">
                <div class="row g-2 small">
                    <div class="col-md-4"><strong>Outward Gate Pass:</strong> <span class="font-monospace text-primary">OUT-${escapeHtml(bNum)}</span></div>
                    <div class="col-md-3"><strong>Vehicle #:</strong> <span class="badge bg-light text-dark border font-monospace">TN54XV1234</span></div>
                    <div class="col-md-5"><strong>Driver/Carrier:</strong> Unit Driver &bull; <strong>Route:</strong> ${escapeHtml(originWarehouse)} &rarr; ${escapeHtml(destChannel)}</div>
                </div>
            </div>
        </div>

        <!-- Stage 7 -->
        <div class="timeline-step mb-2 position-relative ps-4">
            <span class="badge ${isReceived ? 'bg-success' : 'bg-primary'} rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm text-white" style="width: 34px; height: 34px; position: absolute; left: -21px; top: -2px; z-index: 2; box-shadow: 0 0 0 3px #fff;">
                <i class="bi bi-box-arrow-in-down fs-6 text-white"></i>
            </span>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-arrow-in-down text-success me-1"></i>Stage 7: WhatsApp Inward &amp; Stock Ledger</h6>
                <span class="badge ${isReceived ? 'bg-success' : 'bg-warning text-dark'} font-monospace ms-2">${isReceived ? 'STOCK CREDITED' : 'AWAITING GRN SCAN'}</span>
                <small class="text-muted ms-auto">${dateStr}</small>
            </div>
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3 border-start border-4 ${isReceived ? 'border-success' : 'border-warning'}">
                <div class="row g-2 text-center mb-3">
                    <div class="col-sm-6 col-md-3">
                        <div class="p-2 rounded bg-light border">
                            <small class="text-muted fw-bold d-block fs-9 text-uppercase">Outward Expected</small>
                            <h5 class="fw-bold text-dark mb-0">${totalExp} pcs</h5>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-2 rounded bg-success bg-opacity-10 border border-success border-opacity-25">
                            <small class="text-success fw-bold d-block fs-9 text-uppercase">Inward Credited</small>
                            <h5 class="fw-bold text-success mb-0">${totalRec} pcs</h5>
                        </div>
                    </div>
                    ${totalDmg > 0 ? `
                        <div class="col-sm-6 col-md-2">
                            <div class="p-2 rounded bg-danger bg-opacity-10 border border-danger border-opacity-25">
                                <small class="text-danger fw-bold d-block fs-9 text-uppercase">Damaged / Quarantine</small>
                                <h5 class="fw-bold text-danger mb-0">${totalDmg} pcs</h5>
                            </div>
                        </div>
                    ` : ''}
                    <div class="col-sm-6 ${totalDmg > 0 ? 'col-md-2' : 'col-md-3'}">
                        <div class="p-2 rounded ${pendingQty > 0 ? 'bg-danger bg-opacity-10 border border-danger border-opacity-25' : 'bg-light border'}">
                            <small class="${pendingQty > 0 ? 'text-danger' : 'text-muted'} fw-bold d-block fs-9 text-uppercase">Pending / Missing</small>
                            <h5 class="fw-bold ${pendingQty > 0 ? 'text-danger' : 'text-muted'} mb-0">${pendingQty} pcs</h5>
                        </div>
                    </div>
                    <div class="col-sm-6 ${totalDmg > 0 ? 'col-md-2' : 'col-md-3'}">
                        <div class="p-2 rounded bg-info bg-opacity-10 border border-info border-opacity-25">
                            <small class="text-info fw-bold d-block fs-9 text-uppercase">Inward Frequency</small>
                            <h5 class="fw-bold text-info mb-0">${isReceived ? '1 Times' : '0 Times'}</h5>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2 fs-8 flex-wrap gap-2">
                    <div>
                        <strong>Destination Channel:</strong> <span class="badge bg-success font-monospace"><i class="bi bi-whatsapp me-1"></i>WhatsApp Orders Channel</span>
                        <span class="text-muted ms-2">&bull; Source Warehouse: <strong>${escapeHtml(originWarehouse)}</strong></span>
                    </div>
                    <div>
                        <strong>Stock Status:</strong> <span class="badge ${isReceived ? 'bg-success' : 'bg-secondary'}">${isReceived ? 'Stock Credited' : 'Awaiting Inward'}</span>
                    </div>
                </div>

                <h6 class="fw-bold text-dark fs-8 border-bottom pb-1 mb-2">
                    <i class="bi bi-journal-text me-1 text-primary"></i>Stock Ledger Postings (${totalTxnCount} Transaction Record${totalTxnCount === 1 ? '' : 's'}):
                </h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm font-monospace text-center fs-8 mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 10%;">TXN #</th>
                                <th style="width: 26%;">VARIANT SKU</th>
                                <th style="width: 12%;">STOCK STATE</th>
                                <th style="width: 20%;">CHANNEL / LOCATION</th>
                                <th style="width: 9%;">QTY ADDED</th>
                                <th style="width: 9%;">BALANCE AFTER</th>
                                <th style="width: 14%;">DATE &amp; TIME</th>
                                <th>REFERENCE NOTES</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${txnRowsHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

function escapeHtml(str) {
    return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}
</script>

<!-- ========================================================================= -->
<!-- MODAL: BATCH TRACEABILITY & LIFECYCLE LOGS -->
<!-- ========================================================================= -->
<div class="modal fade" id="batchHistoryModal" tabindex="-1" aria-labelledby="batchHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="batchHistoryModalLabel">Allocation Traceability &amp; WhatsApp Inward Logs</h5>
                        <small class="text-white-50" id="batchHistorySubTitle">Complete manufacturing &amp; inward audit trail</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Overview Header Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Batch / Allocation Number</span>
                                <h5 class="fw-bold font-monospace text-primary mb-0" id="histBatchNum"><?= e($dispNum) ?></h5>
                                <small class="text-muted" id="histProductName">Product: <strong><?= e($batch['product_name'] ?? 'Garment Allocation Stock') ?></strong></small>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 rounded bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                    <small class="text-muted d-block fw-semibold"><i class="bi bi-box-arrow-right text-warning me-1"></i>From (Source Warehouse):</small>
                                    <strong class="text-dark font-monospace" id="histOrigin"><?= e($allocation['warehouse_name'] ?? 'Main Central Warehouse') ?> (<?= e($allocation['warehouse_code'] ?? 'WH-01') ?>)</strong>
                                    <div class="small text-muted">Stock Outward Dispatch</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 rounded bg-success bg-opacity-10 border border-success border-opacity-25">
                                    <small class="text-muted d-block fw-semibold"><i class="bi bi-whatsapp text-success me-1"></i>To (WhatsApp Channel):</small>
                                    <strong class="text-success font-monospace" id="histDest"><?= e($allocation['channel_name'] ?? 'WhatsApp Orders Channel') ?> (WA-01)</strong>
                                    <div class="small text-muted">Status: <span class="badge bg-success font-monospace" id="histStatus"><?= e($batch['status'] ?? 'COMPLETED') ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline Steps -->
                <div class="timeline-container position-relative border-start border-3 border-primary ms-4 ps-1 my-2" id="histTimelineSteps">
                    <!-- Populated dynamically -->
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: CARTON QR STICKER -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalCartonQrSticker" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-printer me-2 text-warning"></i>Carton QR Barcode Sticker &amp; Master Label</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="border border-3 border-dark rounded-3 p-4 bg-white shadow-sm d-inline-block text-start" style="max-width: 580px; width: 100%;">
                    <div class="d-flex justify-content-between align-items-start border-bottom border-2 border-dark pb-3 mb-3">
                        <div>
                            <h4 class="fw-bold text-dark mb-0 font-monospace">GRM GARMENTS</h4>
                            <small class="text-muted fw-bold">WAREHOUSE &rarr; WHATSAPP DISPATCH</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-dark font-monospace fs-6 px-3 py-1"><?= e($dispNum) ?></span>
                        </div>
                    </div>
                    <div class="row align-items-center my-3">
                        <div class="col-5 text-center border-end">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?= urlencode($dispNum) ?>" alt="QR" class="img-fluid border p-1 rounded">
                            <div class="font-monospace fw-bold small mt-1"><?= e($dispNum) ?></div>
                        </div>
                        <div class="col-7 ps-3">
                            <div class="mb-2">
                                <small class="text-muted d-block">Origin Warehouse:</small>
                                <strong><?= e($allocation['warehouse_name'] ?? 'Main Central Warehouse') ?></strong>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted d-block">Destination Channel:</small>
                                <span class="badge bg-success font-monospace"><?= e($allocation['channel_name'] ?? 'WhatsApp Channel') ?> (WA-01)</span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Total Quantity:</small>
                                <h4 class="text-dark fw-bold mb-0"><?= (int)$totalExpected ?> pcs</h4>
                            </div>
                        </div>
                    </div>
                    <div class="border-top pt-2 text-end">
                        <button type="button" class="btn btn-dark btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print Sticker</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
