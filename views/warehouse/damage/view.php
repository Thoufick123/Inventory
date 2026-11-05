<?php
    $mrpVal = (float)($damage['mrp'] ?? 0);
    $sellingVal = (float)($damage['selling_price'] ?? 0);
    $costVal = (float)($damage['cost_per_unit'] ?? 0);
    
    // Determine effective unit price and financial loss
    $effectiveUnitPrice = $costVal > 0 ? $costVal : ($sellingVal > 0 ? $sellingVal : ($mrpVal > 0 ? $mrpVal : 0));
    if ($effectiveUnitPrice <= 0) {
        $effectiveUnitPrice = $mrpVal > 0 ? $mrpVal : 0;
    }
    
    $effectiveTotalLoss = (float)($damage['total_loss'] ?? 0);
    if ($effectiveTotalLoss <= 0) {
        $effectiveTotalLoss = $effectiveUnitPrice * (int)($damage['quantity'] ?? 1);
    }
    
    if ($mrpVal <= 0) {
        $mrpVal = $effectiveUnitPrice > 0 ? $effectiveUnitPrice : 1000.00;
    }

    // Parse all damaged tags into array
    $rawTags = $damage['tag_code'] ?? '';
    $damagedTagList = [];
    if (!empty($rawTags)) {
        $parts = explode(',', $rawTags);
        foreach ($parts as $p) {
            $p = trim($p);
            if (!empty($p)) {
                $damagedTagList[] = $p;
            }
        }
    }

    // Determine category/date initials for piece tags
    $c1 = !empty($damage['category_name']) ? strtoupper(substr(trim($damage['category_name']), 0, 1)) : 'M';
    $c2 = !empty($damage['subcategory_name']) ? strtoupper(substr(trim($damage['subcategory_name']), 0, 1)) : 'T';
    $c3 = !empty($damage['sub_subcategory_name']) ? strtoupper(substr(trim($damage['sub_subcategory_name']), 0, 1)) : 'P';
    $mmyy = date('my', !empty($damage['created_at']) ? strtotime($damage['created_at']) : time());
    $defaultPrefix = $c1 . $c2 . $c3 . $mmyy;

    $totalQty = max(1, (int)($damage['quantity'] ?? 1));

    if (empty($damagedTagList)) {
        for ($i = 1; $i <= $totalQty; $i++) {
            $damagedTagList[] = $defaultPrefix . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
        }
    } elseif (count($damagedTagList) < $totalQty) {
        // Expand sequential piece tags up to total damaged quantity
        $firstTag = $damagedTagList[0];
        $basePrefix = $defaultPrefix;
        $startNum = 1;
        $padLen = 2;
        if (preg_match('/^(.*?)(\d{1,5})$/', $firstTag, $m)) {
            $basePrefix = $m[1];
            $startNum = (int)$m[2];
            $padLen = max(2, strlen($m[2]));
        }
        for ($i = count($damagedTagList); $i < $totalQty; $i++) {
            $damagedTagList[] = $basePrefix . str_pad((string)($startNum + $i), $padLen, '0', STR_PAD_LEFT);
        }
    }
    $dispTag = implode(', ', $damagedTagList);
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <a href="<?= url('warehouse/damage') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <h2 class="mb-0">
                <i class="bi bi-exclamation-octagon-fill me-2 <?= $damage['status'] === 'REWORK' ? 'text-warning-emphasis' : 'text-danger' ?>"></i>Case #<?= e($damage['damage_number']) ?>
            </h2>
            <?php if (!empty($damage['batch_number']) && hasPermission('inward', 'view')): ?>
                <a href="<?= url('warehouse/inward/view/' . ($damage['batch_id'] ?? $damage['reference_id'])) ?>" class="badge bg-info-subtle text-info-emphasis border border-info-subtle fs-6 px-3 py-2 text-decoration-none shadow-sm font-monospace" title="View Inward Batch Verification">
                    <i class="bi bi-box-seam me-1"></i>Origin Batch: <strong><?= e($damage['batch_number']) ?></strong>
                </a>
            <?php endif; ?>
            <?php if (!empty($damage['production_unit_name'])): ?>
                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 fs-6 px-3 py-2 shadow-sm">
                    <i class="bi bi-gear-wide-connected me-1 text-warning"></i>Origin Unit: <strong><?= e($damage['production_unit_code'] ?? 'PU1') ?> · <?= e($damage['production_unit_name']) ?></strong>
                </span>
            <?php endif; ?>
        </div>
        <span class="text-muted">Reported on <?= date('d M Y, h:i A', strtotime($damage['created_at'])) ?> by <strong><?= e($damage['reported_by_name'] ?? 'Staff') ?></strong></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (!empty($damage['batch_id'])): ?>
            <!-- <a href="<?= url('warehouse/inward/view/' . $damage['batch_id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-box-arrow-in-down me-1"></i> View Inward Batch GRN
            </a> -->
        <?php endif; ?>
        <!-- <a href="<?= url('warehouse/damage') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-list-check me-1"></i> All Damage Cases
        </a> -->
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Left Column: Details, Origin Batch Card, Individual Piece QRs & Evidence Photo -->
    <div class="col-lg-7">
        <!-- 1. Damage Case Specifications -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Damage Case Specifications</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($damagedTagList)): ?>
                        <li class="list-group-item d-flex justify-content-between px-0 align-items-center bg-light p-2 rounded-2 mb-2 flex-wrap gap-2">
                            <span class="fw-bold text-dark"><i class="bi bi-qr-code me-1 text-primary"></i>Scanned Piece Tags (<?= count($damagedTagList) ?> pcs):</span>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($damagedTagList as $tCode): ?>
                                    <span class="badge bg-dark font-monospace text-warning fs-6 px-2.5 py-1 shadow-2xs"><?= e($tCode) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </li>
                    <?php endif; ?>

                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Product / Style:</span>
                        <span class="fw-bold text-dark"><?= e($damage['product_name']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">SKU Code:</span>
                        <span class="fw-bold font-monospace text-primary"><?= e($damage['sku']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Variant (Size / Color):</span>
                        <span>
                            <span class="badge bg-light text-dark border me-1"><?= e($damage['size_name'] ?? 'Free') ?></span>
                            <span class="badge bg-light text-dark border"><?= e($damage['color_name'] ?? 'Std') ?></span>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Warehouse Location:</span>
                        <span class="badge bg-dark text-white border border-dark px-3 py-1.5 fs-7 fw-bold shadow-2xs" style="background-color: #0f172a !important; color: #f8fafc !important;"><i class="bi bi-building text-warning me-1"></i><?= e($damage['warehouse_name']) ?> (<?= e($damage['warehouse_code']) ?>)</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Damage Source:</span>
                        <span class="badge <?= $damage['source_type'] === 'PRODUCTION_INWARD' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-secondary' ?>">
                            <?= e($damage['source_type']) ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Damage Reason:</span>
                        <span class="fw-semibold text-danger"><?= e($damage['damage_reason']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Damaged Quantity:</span>
                        <span class="fw-bold <?= $damage['status'] === 'REWORK' ? 'text-warning-emphasis' : 'text-danger' ?> fs-5"><?= number_format($damage['quantity']) ?> pcs</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">MRP:</span>
                        <span class="fw-bold text-dark">₹<?= number_format($mrpVal, 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Unit Value / Cost:</span>
                        <span class="fw-bold text-dark">₹<?= number_format($effectiveUnitPrice, 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Total Financial Loss:</span>
                        <span class="fw-bold text-danger fs-5">₹<?= number_format($effectiveTotalLoss, 2) ?></span>
                    </li>
                </ul>

                <?php if (!empty($damage['defect_notes'])): ?>
                    <div class="mt-3 p-3 bg-light rounded-3 border">
                        <span class="small fw-bold text-muted d-block mb-1"><i class="bi bi-chat-left-text me-1"></i>Inspector / Defect Notes:</span>
                        <p class="mb-0 text-dark"><?= nl2br(e($damage['defect_notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Origin Manufacturing & Batch Lineage Card -->
        <?php if ($damage['source_type'] === 'PRODUCTION_INWARD' || !empty($damage['batch_number'])): ?>
            <div class="card border-0 shadow-sm mb-4 border-start border-4 border-warning">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-gear-wide-connected me-2 text-warning"></i>Origin Manufacturing Batch &amp; Facility</h6>
                    <?php if (!empty($damage['batch_id'])): ?>
                        <?php if (hasPermission('inward', 'view')): ?>
                        <a href="<?= url('warehouse/inward/view/' . $damage['batch_id']) ?>" class="btn btn-xs btn-outline-primary py-1 px-2.5 font-monospace fs-7">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open Inward Batch
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="text-muted small d-block mb-1 fw-semibold text-uppercase" style="font-size: 0.72rem;">Production Batch</span>
                                <div class="font-monospace fw-bold fs-6 text-primary mb-1"><?= e($damage['batch_number'] ?? 'N/A') ?></div>
                                <small class="text-muted d-block">Program #: <strong><?= e($damage['program_number'] ?? 'N/A') ?></strong></small>
                                <?php if (!empty($damage['program_name'])): ?>
                                    <small class="text-muted d-block"><?= e($damage['program_name']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-warning bg-opacity-10 rounded-3 border border-warning border-opacity-25 h-100">
                                <span class="text-dark small d-block mb-1 fw-semibold text-uppercase" style="font-size: 0.72rem;">Manufacturing Facility</span>
                                <div class="fw-bold text-dark fs-6 mb-1">
                                    <span class="badge bg-warning text-dark font-monospace me-1"><?= e($damage['production_unit_code'] ?? 'PU1') ?></span>
                                    <?= e($damage['production_unit_name'] ?? 'Main Apparel Unit') ?>
                                </div>
                                <?php if (!empty($damage['production_unit_manager']) || !empty($damage['production_unit_phone'])): ?>
                                    <small class="text-muted d-block"><i class="bi bi-person me-1"></i><?= e($damage['production_unit_manager'] ?? '') ?> <?= !empty($damage['production_unit_phone']) ? '('.e($damage['production_unit_phone']).')' : '' ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="text-muted small d-block mb-1 fw-semibold text-uppercase" style="font-size: 0.72rem;">Outward Logistics &amp; Carrier</span>
                                <div class="small fw-semibold text-dark mb-1">Gate Pass: <span class="font-monospace text-primary"><?= e($damage['outward_number'] ?? 'N/A') ?></span></div>
                                <div class="small text-muted">Vehicle: <span class="font-monospace fw-semibold text-dark"><?= e($damage['vehicle_number'] ?? 'Internal Transfer') ?></span></div>
                                <?php if (!empty($damage['driver_name'])): ?>
                                    <small class="text-muted d-block">Driver: <?= e($damage['driver_name']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="text-muted small d-block mb-1 fw-semibold text-uppercase" style="font-size: 0.72rem;">Dispatch &amp; Inward Dates</span>
                                <div class="small text-muted mb-1">Dispatched: <span class="fw-semibold text-dark"><?= !empty($damage['outward_dispatch_date']) ? date('d M Y, h:i A', strtotime($damage['outward_dispatch_date'])) : 'N/A' ?></span></div>
                                <div class="small text-muted">Quarantined at Inward: <span class="fw-semibold text-danger"><?= date('d M Y, h:i A', strtotime($damage['created_at'])) ?></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Live QR Tag, Status / Disposition Actions & Evidence Photo -->
    <div class="col-lg-5">
        <!-- Live 2D QR Tag Render Card -->
        <?php if (!empty($dispTag)): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-qr-code me-2 text-primary"></i>2D QR Code &amp; Garment Tag</h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-primary active fw-semibold" id="btnModePieceTagView" onclick="switchViewQrMode('piece')">
                            Piece Tag
                        </button>
                        <button type="button" class="btn btn-outline-secondary fw-semibold" id="btnModeProductQrView" onclick="switchViewQrMode('product')">
                            Product QR
                        </button>
                    </div>
                </div>
                <div class="card-body text-center p-4 bg-light">
                    <?php if (count($damagedTagList) > 1): ?>
                        <div class="mb-3 text-start">
                            <label class="form-label small fw-bold text-muted mb-1">Select Piece Tag Preview:</label>
                            <select id="selectPieceTagPreview" class="form-select form-select-sm font-monospace" onchange="changePreviewTag(this.value)">
                                <?php foreach ($damagedTagList as $idx => $tCode): ?>
                                    <option value="<?= e($tCode) ?>">Piece #<?= $idx + 1 ?>: <?= e($tCode) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Thermal Label Dual-Sticker Visual Preview -->
                    <div class="card border mx-auto shadow-sm p-3 mb-3 rounded-3 bg-white text-start" style="max-width: 320px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                        <div class="d-flex align-items-start gap-3">
                            <div class="text-center flex-shrink-0">
                                <div class="bg-white p-1 rounded border shadow-2xs d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                    <div id="viewQrContainer"></div>
                                </div>
                                <div class="badge bg-dark text-warning font-monospace mt-1 px-2 py-1 w-100" id="viewTagCodeDisplay" style="font-size: 0.78rem; letter-spacing: 0.5px;"><?= e($damagedTagList[0] ?? $dispTag) ?></div>
                            </div>
                            <div class="overflow-hidden flex-grow-1" style="margin-top: 2px;">
                                <div class="fw-bold text-uppercase text-dark text-truncate" style="font-size: 0.85rem; letter-spacing: 0.3px;">GRM WAREHOUSE</div>
                                <div class="fw-bold text-dark text-truncate small mb-1"><?= e($damage['product_name']) ?></div>
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span class="badge bg-dark-subtle text-dark font-monospace" style="font-size: 0.72rem;"><?= e($damage['sku']) ?></span>
                                    <?php if (!empty($damage['category_name'])): ?>
                                        <span class="badge bg-light text-primary border" style="font-size: 0.72rem;"><?= e($damage['category_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="fw-semibold text-secondary small mb-1"><?= e($damage['size_name'] ?? 'Free') ?> / <?= e($damage['color_name'] ?? 'Std') ?></div>
                                <?php if ($mrpVal > $effectiveUnitPrice): ?>
                                    <div class="text-muted small text-decoration-line-through" style="font-size: 0.76rem;">MRP: ₹<?= number_format($mrpVal, 2) ?></div>
                                <?php else: ?>
                                    <div class="text-muted small" style="font-size: 0.76rem;">MRP: ₹<?= number_format($mrpVal, 2) ?></div>
                                <?php endif; ?>
                                <div class="fw-bold text-danger fs-6">Price: ₹<?= number_format($effectiveUnitPrice, 2) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Scannable Payload String Box -->
                    <div class="p-2.5 bg-white rounded border text-start mb-2 shadow-2xs">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold text-muted" style="font-size: 0.75rem;"><i class="bi bi-code-square me-1"></i>Encoded QR Payload</span>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" onclick="copyViewQrPayload()" style="font-size: 0.72rem;">
                                <i class="bi bi-clipboard me-1"></i> Copy
                            </button>
                        </div>
                        <code class="d-block font-monospace text-dark text-break small p-1 bg-light rounded" id="viewQrPayloadText" style="font-size: 0.75rem;">-</code>
                    </div>

                    <small class="text-muted"><i class="bi bi-info-circle me-1 text-primary"></i>Scannable by 2D barcode guns &amp; mobile cameras.</small>
                </div>
            </div>
        <?php endif; ?>

        <!-- Status Workflow & Disposition Update -->
        <div class="card border-0 shadow-sm mb-4 border-top border-4 <?= $damage['status'] === 'REWORK' ? 'border-warning' : 'border-danger' ?>">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Disposition &amp; Status Workflow</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="text-muted small d-block mb-1">Current Disposition Status:</span>
                    <?php
                        $statusVal = !empty($damage['status']) ? strtoupper(trim($damage['status'])) : 'LOGGED';
                    ?>
                    <?php if ($statusVal === 'LOGGED'): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fs-6 fw-semibold d-inline-flex align-items-center gap-1">
                            <i class="bi bi-clock-history"></i> LOGGED (Quarantine)
                        </span>
                    <?php elseif ($statusVal === 'REWORK'): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 rounded-pill fs-6 fw-semibold d-inline-flex align-items-center gap-1">
                            <i class="bi bi-arrow-repeat"></i> REWORK (Sent to Factory)
                        </span>
                    <?php elseif ($statusVal === 'INSPECTED'): ?>
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-2 rounded-pill fs-6 fw-semibold d-inline-flex align-items-center gap-1">
                            <i class="bi bi-search"></i> INSPECTED
                        </span>
                    <?php elseif ($statusVal === 'SALVAGED'): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6 fw-semibold d-inline-flex align-items-center gap-1">
                            <i class="bi bi-recycle"></i> SALVAGED
                        </span>
                    <?php elseif ($statusVal === 'RESTOCKED' || $statusVal === 'REPAIRED'): ?>
                        <span class="badge bg-success text-white border px-3 py-2 rounded-pill fs-6 fw-semibold d-inline-flex align-items-center gap-1">
                            <i class="bi bi-check2-circle"></i> <?= $statusVal === 'RESTOCKED' ? 'RESTOCKED (In Available Stock)' : 'REPAIRED (Restocked)' ?>
                        </span>
                    <?php elseif ($statusVal === 'WRITTEN_OFF'): ?>
                        <span class="badge bg-dark-subtle text-dark border border-dark-subtle px-3 py-2 rounded-pill fs-6 fw-semibold d-inline-flex align-items-center gap-1">
                            <i class="bi bi-x-circle"></i> WRITTEN OFF
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill fs-6 fw-semibold">
                            <?= e($statusVal) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Rework Workflow Guidance & Quick Action Banner -->
                <?php if ($statusVal === 'REWORK'): ?>
                    <div class="alert alert-warning border border-warning-subtle rounded-3 p-3 mb-3 shadow-2xs">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1.5">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-arrow-repeat fs-5 text-warning-emphasis"></i>
                                <strong class="text-warning-emphasis">Currently at Factory for Rework</strong>
                            </div>
                            <?php if (!empty($damage['production_unit_name'])): ?>
                                <span class="badge bg-warning text-dark font-monospace">
                                    <i class="bi bi-gear-wide-connected me-1"></i><?= e($damage['production_unit_code'] ?? 'PU') ?>: <?= e($damage['production_unit_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="small text-muted mb-2.5">
                            Items are currently with <strong><?= e($damage['production_unit_name'] ?? 'the production unit') ?></strong> for stitching/fabric rework. When garments are returned back repaired to the warehouse, staff can click below to immediately restore them back to active salable inventory:
                        </p>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if (hasPermission('damage', 'resolve')): ?>
                            <button type="button" class="btn btn-sm btn-success fw-semibold shadow-sm d-inline-flex align-items-center gap-1" onclick="quickApplyStatus('REPAIRED', 'Factory rework completed successfully. Garments inspected and restocked back to active warehouse inventory.')">
                                <i class="bi bi-check2-circle"></i> Rework Completed &amp; Restock (+<?= (int)$damage['quantity'] ?> pcs)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger fw-semibold d-inline-flex align-items-center gap-1" onclick="quickApplyStatus('WRITTEN_OFF', 'Factory confirmed garments cannot be repaired. Total loss written off.')">
                                <i class="bi bi-trash3"></i> Irreparable (Write Off)
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($statusVal === 'LOGGED'): ?>
                    <div class="alert alert-danger-subtle border border-danger-subtle rounded-3 p-3 mb-3 shadow-2xs">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-shield-exclamation fs-5 text-danger"></i>
                            <strong class="text-danger">Quarantined in Warehouse</strong>
                        </div>
                        <p class="small text-muted mb-2">
                            Defects quarantined during inward/storage inspection. Quick actions for warehouse staff:
                        </p>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if (hasPermission('damage', 'resolve')): ?>
                            <button type="button" class="btn btn-sm btn-warning text-dark fw-semibold shadow-sm d-inline-flex align-items-center gap-1" onclick="quickApplyStatus('REWORK', 'Dispatched to factory for stitching/fabric rework.')">
                                <i class="bi bi-arrow-repeat"></i> Dispatch to Factory Rework
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success fw-semibold d-inline-flex align-items-center gap-1" onclick="quickApplyStatus('RESTOCKED', 'Minor defect cleared. Restocked directly to active available inventory.')">
                                <i class="bi bi-check2-circle"></i> Restock to Available
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <hr>

                <?php if (hasPermission('damage', 'resolve')): ?>
                <form id="damageStatusForm" method="POST" action="<?= url('warehouse/damage/update-status/' . $damage['id']) ?>" enctype="multipart/form-data">
                    <?= CSRF::input() ?>
                    <div id="hiddenFormSelectedTags"></div>
                    <h6 class="fw-bold mb-2">Update Disposition:</h6>

                    <!-- Selected Pieces Scope Notice -->
                    <div class="p-2.5 rounded-3 border mb-3 bg-light shadow-2xs" id="dispositionScopeCard">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="small fw-bold text-dark">
                                <i class="bi bi-boxes text-primary me-1"></i> Pieces Scope:
                            </span>
                            <span class="badge bg-primary px-2 py-1 rounded-pill fs-7" id="dispScopeBadge">
                                <?= count($damagedTagList) ?> of <?= count($damagedTagList) ?> Pieces Selected
                            </span>
                        </div>
                        <div class="small text-muted" id="dispScopeDesc" style="font-size: 0.78rem;">
                            All <?= count($damagedTagList) ?> pieces will be processed together.
                        </div>
                        <div class="small text-warning-emphasis mt-1 fw-semibold" id="dispScopeRemainingNotice" style="display: none; font-size: 0.76rem;">
                            <i class="bi bi-info-circle-fill me-1"></i> <span id="dispScopeRemainingText"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select New Status</label>
                        <select name="status" id="damageStatusSelect" class="form-select" onchange="handleDamageStatusChange(this.value)" required>
                            <option value="LOGGED" <?= $damage['status'] === 'LOGGED' ? 'selected' : '' ?>>LOGGED (Pending Inspection / Quarantine)</option>
                            <option value="REWORK" <?= $damage['status'] === 'REWORK' ? 'selected' : '' ?>>REWORK (Sent to Factory)</option>
                            <option value="INSPECTED" <?= $damage['status'] === 'INSPECTED' ? 'selected' : '' ?>>INSPECTED (Under Evaluation)</option>
                            <option value="RESTOCKED" <?= $damage['status'] === 'RESTOCKED' ? 'selected' : '' ?>>RESTOCKED (Restored &amp; Credited Back to Active Stock)</option>
                            <option value="REPAIRED" <?= $damage['status'] === 'REPAIRED' ? 'selected' : '' ?>>REPAIRED (Repaired &amp; Credited Back to Active Stock)</option>
                            <option value="SALVAGED" <?= $damage['status'] === 'SALVAGED' ? 'selected' : '' ?>>SALVAGED (Discount Clearance)</option>
                            <option value="WRITTEN_OFF" <?= $damage['status'] === 'WRITTEN_OFF' ? 'selected' : '' ?>>WRITTEN OFF (Total Loss / Disposed)</option>
                        </select>
                    </div>

                    <!-- Target Production Unit for REWORK -->
                    <div class="mb-3 p-3 bg-warning bg-opacity-10 rounded-3 border border-warning border-opacity-25" id="reworkUnitGroup" style="display: <?= ($damage['status'] === 'REWORK') ? 'block' : 'none' ?>;">
                        <label class="form-label small fw-bold text-dark d-flex align-items-center justify-content-between mb-1">
                            <span><i class="bi bi-gear-wide-connected text-warning-emphasis me-1"></i> Destination Production Unit for Rework <span class="text-danger">*</span></span>
                            <?php if (!empty($damage['production_unit_code'])): ?>
                                <span class="badge bg-warning text-dark font-monospace" style="font-size: 0.72rem;">Origin: <?= e($damage['production_unit_code']) ?></span>
                            <?php endif; ?>
                        </label>
                        <select name="production_unit_id" id="reworkProductionUnitSelect" class="form-select form-select-sm border-warning">
                            <option value="">-- Select Production Unit for Rework --</option>
                            <?php 
                                $selectedUnitId = (int)($damage['production_unit_id'] ?? 0);
                                if (!empty($productionUnits)): 
                                    foreach ($productionUnits as $pu): 
                                        $isMatch = ($selectedUnitId === (int)$pu['id']);
                            ?>
                                <option value="<?= $pu['id'] ?>" <?= $isMatch ? 'selected' : '' ?>>
                                    [<?= e($pu['code']) ?>] <?= e($pu['name']) ?><?= !empty($pu['manager_name']) ? ' &bull; Mgr: ' . e($pu['manager_name']) : '' ?><?= $isMatch ? ' (Origin / Assigned)' : '' ?>
                                </option>
                            <?php 
                                    endforeach; 
                                endif; 
                            ?>
                        </select>
                        <div class="form-text small text-muted mt-1" style="font-size: 0.76rem;">
                            <i class="bi bi-info-circle me-1"></i>Select which production unit will receive and repair these <strong><?= (int)$damage['quantity'] ?> pieces</strong>.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Disposition / Resolution Notes</label>
                        <textarea name="defect_notes" id="damageStatusNotes" class="form-control" rows="3" placeholder="Explain the inspection result, factory rework dispatch/receipt, or repair outcome..."><?= e($damage['defect_notes'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-camera me-1"></i> <?= !empty($damage['photo_url']) ? 'Update Evidence Photo (Optional)' : 'Attach Photo Evidence (Cloudinary CDN)' ?></span>
                        </label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <small class="text-muted d-block mt-1">Uploaded securely to Cloudinary cloud storage.</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Update Status &amp; Save
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert-light border small text-muted mb-0">
                    <i class="bi bi-lock me-1"></i> You have read-only access to this damage record. Status updates are restricted.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Photo Evidence Card (in Right Column for perfect balance) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-image me-2 text-primary"></i>Photo Evidence</h6>
                <?php if (!empty($damage['photo_url'])): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1">
                        <i class="bi bi-cloud-check-fill"></i> Cloudinary CDN
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body text-center p-3">
                <?php if (!empty($damage['photo_url'])): ?>
                    <div class="position-relative d-inline-block">
                        <img src="<?= e($damage['photo_url']) ?>" alt="Damage Photo Evidence" class="img-fluid rounded border shadow-sm" style="max-height: 280px; object-fit: contain;">
                        <div class="mt-2">
                            <a href="<?= e($damage['photo_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-box-arrow-up-right me-1"></i> View Full Size High-Res Image
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="py-3 text-muted">
                        <i class="bi bi-camera fs-2 d-block mb-1 text-secondary"></i>
                        <p class="mb-0 fw-semibold text-dark small">No Photo Evidence Attached</p>
                        <small class="text-muted">You can upload a reference photo using the disposition form above.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- FULL-WIDTH PANORAMIC DAMAGED INDIVIDUAL PIECE QR CODES & LABELS -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4 border-start border-4 border-danger">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h6 class="card-title mb-0 fw-bold d-flex align-items-center">
                <i class="bi bi-qr-code-scan me-2 text-danger"></i>
                Damaged Individual Piece QR Codes &amp; Labels
            </h6>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill" id="totalPiecesBadge">
                <?= count($damagedTagList) ?> Damaged Pieces
            </span>
            <span class="badge bg-primary text-white rounded-pill shadow-2xs fw-bold" id="selectedPiecesBadge">
                <?= count($damagedTagList) ?> Selected
            </span>
        </div>

        <!-- Selection Toolbar & Quick Dispatch -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-primary fw-semibold" onclick="selectAllPieces(true)">
                    <i class="bi bi-check-all me-1"></i>Select All
                </button>
                <button type="button" class="btn btn-outline-secondary fw-semibold" onclick="selectAllPieces(false)">
                    <i class="bi bi-dash-square me-1"></i>Deselect All
                </button>
            </div>
            <?php if (hasPermission('damage', 'resolve')): ?>
                <button type="button" class="btn btn-sm btn-warning text-dark fw-bold shadow-sm d-inline-flex align-items-center gap-1" onclick="sendSelectedToRework()">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Send Picked to Factory Rework (<span class="selected-count-span"><?= count($damagedTagList) ?></span>)</span>
                </button>
            <?php endif; ?>
            <?php if (hasPermission('wh_labels', 'print')): ?>
            <button type="button" class="btn btn-sm btn-outline-danger shadow-sm d-inline-flex align-items-center gap-1" onclick="printAllDamagedLabels()">
                <i class="bi bi-printer"></i>
                <span>Print All (<?= count($damagedTagList) ?> pcs)</span>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <?php foreach ($damagedTagList as $idx => $tagCode): ?>
                <?php 
                    $fmtMrp = number_format($mrpVal, 2, '.', '');
                    $fmtPrice = number_format($effectiveUnitPrice, 2, '.', '');
                    $cardPayload = $tagCode;
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <div class="card border h-100 shadow-2xs rounded-3 p-3 bg-white position-relative hover-shadow transition piece-item-card" id="pieceCard_<?= $idx ?>" data-tag="<?= e($tagCode) ?>" style="border-color: #0d6efd; transition: all 0.2s ease;">
                        <!-- Card Top Bar: Checkbox to Pick Product + Damaged # Badge -->
                        <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                            <div class="form-check form-check-inline m-0 d-flex align-items-center gap-1.5">
                                <input class="form-check-input piece-select-chk" type="checkbox" value="<?= e($tagCode) ?>" id="chk_tag_<?= $idx ?>" checked onchange="syncPieceSelection()" style="cursor: pointer; width: 1.15em; height: 1.15em;">
                                <label class="form-check-label fw-bold text-dark small" for="chk_tag_<?= $idx ?>" style="cursor: pointer; user-select: none;">
                                    Pick for Dispatch
                                </label>
                            </div>
                            <span class="badge bg-danger text-white px-2 py-0.5 rounded-pill" style="font-size: 0.68rem;">DAMAGED #<?= $idx + 1 ?></span>
                        </div>

                        <div class="d-flex align-items-start gap-2.5">
                            <div class="text-center flex-shrink-0">
                                <div class="bg-white p-1 rounded border shadow-2xs d-flex align-items-center justify-content-center" style="width: 82px; height: 82px;">
                                    <div id="gridDmgQr_<?= $idx ?>" class="d-flex align-items-center justify-content-center"></div>
                                </div>
                                <span class="badge bg-dark text-warning font-monospace mt-1 px-1.5 py-0.5 w-100" style="font-size: 0.72rem;"><?= e($tagCode) ?></span>
                            </div>
                            <div class="overflow-hidden flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between gap-1 mb-0.5">
                                    <span class="badge bg-light text-muted border" style="font-size: 0.68rem;"><?= e($damage['warehouse_code']) ?></span>
                                </div>
                                <div class="fw-bold text-dark text-truncate small" title="<?= e($damage['product_name']) ?>"><?= e($damage['product_name']) ?></div>
                                <div class="font-monospace text-primary text-truncate mb-1" style="font-size: 0.72rem;"><?= e($damage['sku']) ?></div>
                                <div class="text-secondary small mb-1" style="font-size: 0.74rem;">
                                    <?= e($damage['size_name'] ?? 'Free') ?> / <?= e($damage['color_name'] ?? 'Std') ?>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($mrpVal > $effectiveUnitPrice): ?>
                                        <small class="text-muted text-decoration-line-through" style="font-size: 0.72rem;">MRP: ₹<?= number_format($mrpVal, 2) ?></small>
                                    <?php endif; ?>
                                    <strong class="text-danger small" style="font-size: 0.80rem;">Price: ₹<?= number_format($effectiveUnitPrice, 2) ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2.5 pt-2 border-top d-flex align-items-center justify-content-between gap-1">
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-2 text-truncate" onclick="copyDmgCodePayload('<?= e($cardPayload) ?>')" style="font-size: 0.70rem;" title="Copy Scannable Payload">
                                <i class="bi bi-clipboard me-1"></i>Copy QR
                            </button>
                            <?php if (hasPermission('wh_labels', 'print')): ?>
                                <button type="button" class="btn btn-xs btn-outline-danger py-0.5 px-2" onclick="printSingleDamagedLabel('<?= e($tagCode) ?>', '<?= e($damage['sku']) ?>', <?= $mrpVal ?>, <?= $effectiveUnitPrice ?>)" style="font-size: 0.70rem;">
                                    <i class="bi bi-printer me-1"></i>Print Sticker
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- AUDIT LOGS & FULL LIFECYCLE TRACEABILITY TRAIL -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3">
        <ul class="nav nav-tabs card-header-tabs" id="damageAuditTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold text-dark" id="damage-audit-tab" data-bs-toggle="tab" data-bs-target="#damage-audit-pane" type="button" role="tab" aria-selected="true">
                    <i class="bi bi-shield-check me-2 text-danger"></i>Damage Case Audit Logs (<?= count($auditLogs ?? []) ?>)
                </button>
            </li>
            <?php if (!empty($batchAuditLogs) || !empty($batchLifecycle)): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-dark" id="batch-lifecycle-tab" data-bs-toggle="tab" data-bs-target="#batch-lifecycle-pane" type="button" role="tab" aria-selected="false">
                        <i class="bi bi-clock-history me-2 text-primary"></i>Batch Lifecycle &amp; Traceability
                    </button>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="card-body p-4">
        <div class="tab-content" id="damageAuditTabsContent">
            <!-- TAB 1: Damage Case Audit Logs -->
            <div class="tab-pane fade show active" id="damage-audit-pane" role="tabpanel" tabindex="0">
                <?php if (empty($auditLogs)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-shield-slash fs-2 d-block mb-2 text-secondary"></i>
                        <p class="mb-0">No direct audit logs recorded yet for this damage case.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 170px;">Timestamp</th>
                                    <th class="text-uppercase fs-7 fw-bold text-muted" style="min-width: 140px;">Action</th>
                                    <th class="text-uppercase fs-7 fw-bold text-muted" style="min-width: 140px;">User</th>
                                    <th class="text-uppercase fs-7 fw-bold text-muted" style="min-width: 200px;">Old State / Details</th>
                                    <th class="pe-3 text-uppercase fs-7 fw-bold text-muted" style="min-width: 200px;">New State / Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditLogs as $log): ?>
                                    <tr>
                                        <td class="ps-3 text-nowrap">
                                            <div class="fw-semibold text-dark small"><?= date('d M Y, h:i:s A', strtotime($log['created_at'])) ?></div>
                                            <small class="text-muted font-monospace"><?= e($log['ip_address'] ?? '127.0.0.1') ?></small>
                                        </td>
                                        <td class="text-nowrap">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2.5 py-1">
                                                <?= e($log['action']) ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap">
                                            <div class="fw-bold text-dark small"><?= e($log['user_name'] ?? 'System') ?></div>
                                            <small class="text-muted"><?= e($log['role_name'] ?? 'Staff') ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['previous_value'])): ?>
                                                <div class="p-2 bg-light rounded font-monospace small text-dark" style="font-size: 11px; max-height: 100px; overflow-y: auto;">
                                                    <?= e($log['previous_value']) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-3">
                                            <?php if (!empty($log['new_value'])): ?>
                                                <div class="p-2 bg-light rounded font-monospace small text-dark" style="font-size: 11px; max-height: 100px; overflow-y: auto;">
                                                    <?= e($log['new_value']) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 2: Batch Lifecycle & Traceability -->
            <?php if (!empty($batchAuditLogs) || !empty($batchLifecycle)): ?>
                <div class="tab-pane fade" id="batch-lifecycle-pane" role="tabpanel" tabindex="0">
                    <div class="row g-4">
                        <!-- Left: Lifecycle Stages Timeline -->
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-3 me-2 text-primary"></i>Manufacturing Lineage Timeline</h6>
                            
                            <div class="timeline-trail position-relative ps-4 border-start border-2 border-primary border-opacity-25 ms-2">
                                <!-- Stage 1: Program -->
                                <div class="mb-4 position-relative">
                                    <div class="position-absolute rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 26px; height: 26px; left: -29px; top: 0;">
                                        <i class="bi bi-file-earmark-text fs-7"></i>
                                    </div>
                                    <div class="p-3 bg-light rounded border shadow-2xs">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-dark small">Stage 1: Production Program</strong>
                                            <span class="badge bg-primary-subtle text-primary font-monospace"><?= e($damage['program_number'] ?? 'PRG') ?></span>
                                        </div>
                                        <div class="small text-muted"><?= e($damage['program_name'] ?? 'Production Order') ?></div>
                                    </div>
                                </div>

                                <!-- Stage 2: Origin Unit -->
                                <div class="mb-4 position-relative">
                                    <div class="position-absolute rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center shadow-sm" style="width: 26px; height: 26px; left: -29px; top: 0;">
                                        <i class="bi bi-gear-wide-connected fs-7"></i>
                                    </div>
                                    <div class="p-3 bg-light rounded border shadow-2xs">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-dark small">Stage 2: Manufacturing Facility</strong>
                                            <span class="badge bg-warning text-dark font-monospace"><?= e($damage['production_unit_code'] ?? 'PU1') ?></span>
                                        </div>
                                        <div class="small fw-semibold text-dark"><?= e($damage['production_unit_name'] ?? 'Main Facility') ?></div>
                                        <?php if (!empty($damage['production_unit_manager'])): ?>
                                            <small class="text-muted d-block mt-1">Manager: <?= e($damage['production_unit_manager']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Stage 3: Outward Logistics -->
                                <div class="mb-4 position-relative">
                                    <div class="position-absolute rounded-circle bg-info text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 26px; height: 26px; left: -29px; top: 0;">
                                        <i class="bi bi-truck fs-7"></i>
                                    </div>
                                    <div class="p-3 bg-light rounded border shadow-2xs">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-dark small">Stage 3: Outward Logistics Dispatch</strong>
                                            <span class="badge bg-info-subtle text-info font-monospace"><?= e($damage['outward_number'] ?? 'OUT') ?></span>
                                        </div>
                                        <div class="small text-muted">Vehicle: <strong><?= e($damage['vehicle_number'] ?? 'Internal Carrier') ?></strong></div>
                                        <small class="text-muted d-block">Dispatched: <?= !empty($damage['outward_dispatch_date']) ? date('d M Y, h:i A', strtotime($damage['outward_dispatch_date'])) : 'Recorded' ?></small>
                                    </div>
                                </div>

                                <!-- Stage 4: Inward Isolation & Quarantine -->
                                <div class="position-relative">
                                    <div class="position-absolute rounded-circle bg-danger text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 26px; height: 26px; left: -29px; top: 0;">
                                        <i class="bi bi-exclamation-octagon fs-7"></i>
                                    </div>
                                    <div class="p-3 bg-danger bg-opacity-10 rounded border border-danger border-opacity-25 shadow-2xs">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-danger small">Stage 4: Inward Quarantine &amp; Damage Case Logged</strong>
                                            <span class="badge bg-danger text-white font-monospace"><?= e($damage['damage_number']) ?></span>
                                        </div>
                                        <div class="small text-dark">Damaged Qty: <strong><?= number_format($damage['quantity']) ?> pcs</strong> (Loss: ₹<?= number_format($damage['total_loss'], 2) ?>)</div>
                                        <small class="text-danger d-block mt-1"><?= e($damage['damage_reason']) ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Batch Specific Audit Logs -->
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-list-columns-reverse me-2 text-primary"></i>Batch Activity Events</h6>
                            <?php if (empty($batchAuditLogs)): ?>
                                <div class="p-3 bg-light rounded border text-muted small text-center">
                                    No system audit log entries found for Batch #<?= e($damage['batch_number'] ?? '') ?>.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-2 text-uppercase fs-7 fw-bold text-muted">Time</th>
                                                <th class="text-uppercase fs-7 fw-bold text-muted">Action</th>
                                                <th class="text-uppercase fs-7 fw-bold text-muted">Actor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($batchAuditLogs as $bLog): ?>
                                                <tr>
                                                    <td class="ps-2 text-nowrap small"><?= date('d M Y, H:i', strtotime($bLog['created_at'])) ?></td>
                                                    <td class="text-nowrap"><span class="badge bg-light text-dark border font-monospace"><?= e($bLog['action']) ?></span></td>
                                                    <td class="small text-dark"><?= e($bLog['user_name'] ?? 'System') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($dispTag)): ?>
<script>
const DAMAGED_TAG_LIST = <?= json_encode($damagedTagList) ?>;
let selectedPreviewTag = <?= json_encode($damagedTagList[0] ?? $dispTag) ?>;
const VIEW_PRODUCT_QR = <?= json_encode(!empty($damage['qr_code']) ? $damage['qr_code'] : $damage['sku']) ?>;
const VIEW_SKU = <?= json_encode($damage['sku']) ?>;
const VIEW_PRODUCT_NAME = <?= json_encode($damage['product_name']) ?>;
const VIEW_VARIANT_DESC = <?= json_encode(($damage['size_name'] ?? 'Free') . ' / ' . ($damage['color_name'] ?? 'Std')) ?>;
const VIEW_MRP = <?= json_encode(number_format($mrpVal, 2, '.', '')) ?>;
const VIEW_PRICE = <?= json_encode(number_format($effectiveUnitPrice, 2, '.', '')) ?>;

let currentViewQrMode = 'piece';

function renderAllDamagedGridQRs() {
    if (typeof QRCode === 'undefined') return;
    DAMAGED_TAG_LIST.forEach((tag, idx) => {
        const container = document.getElementById(`gridDmgQr_${idx}`);
        if (container) {
            container.innerHTML = '';
            const payload = tag;
            new QRCode(container, {
                text: payload,
                width: 76,
                height: 76,
                colorDark : "#0f172a",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.M
            });
        }
    });
}

function renderDamageViewQR() {
    const qrContainer = document.getElementById('viewQrContainer');
    const tagDisplay = document.getElementById('viewTagCodeDisplay');
    const payloadText = document.getElementById('viewQrPayloadText');
    const btnPiece = document.getElementById('btnModePieceTagView');
    const btnProd = document.getElementById('btnModeProductQrView');

    let textToEncode = '';
    let displayLabel = '';

    if (currentViewQrMode === 'product') {
        textToEncode = VIEW_PRODUCT_QR;
        displayLabel = VIEW_PRODUCT_QR;
        if (btnPiece) btnPiece.className = 'btn btn-outline-secondary fw-semibold';
        if (btnProd) btnProd.className = 'btn btn-primary active fw-semibold';
    } else {
        textToEncode = selectedPreviewTag;
        displayLabel = selectedPreviewTag;
        if (btnPiece) btnPiece.className = 'btn btn-primary active fw-semibold';
        if (btnProd) btnProd.className = 'btn btn-outline-secondary fw-semibold';
    }

    if (tagDisplay) tagDisplay.innerText = displayLabel;
    if (payloadText) payloadText.innerText = textToEncode;

    if (qrContainer && typeof QRCode !== 'undefined') {
        qrContainer.innerHTML = '';
        new QRCode(qrContainer, {
            text: textToEncode,
            width: 90,
            height: 90,
            colorDark : "#0f172a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });
    }
}

function changePreviewTag(tag) {
    selectedPreviewTag = tag;
    renderDamageViewQR();
}

function switchViewQrMode(mode) {
    currentViewQrMode = mode;
    renderDamageViewQR();
}

function copyDmgCodePayload(text) {
    if (!text) return;
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            alert('✅ Copied Scannable QR Payload:\n' + text);
        }).catch(() => {
            prompt('Copy QR Payload:', text);
        });
    } else {
        prompt('Copy QR Payload:', text);
    }
}

function copyViewQrPayload() {
    const textEl = document.getElementById('viewQrPayloadText');
    const text = textEl ? textEl.innerText.trim() : '';
    if (!text || text === '-') return;
    copyDmgCodePayload(text);
}

function handleDamageStatusChange(val) {
    const group = document.getElementById('reworkUnitGroup');
    const select = document.getElementById('reworkProductionUnitSelect');
    if (!group) return;
    if (val === 'REWORK') {
        group.style.display = 'block';
        if (select) select.setAttribute('required', 'required');
    } else {
        group.style.display = 'none';
        if (select) select.removeAttribute('required');
    }
}

function syncPieceSelection() {
    const checkboxes = document.querySelectorAll('.piece-select-chk');
    const total = checkboxes.length;
    let selectedCount = 0;
    const selectedTags = [];

    checkboxes.forEach((chk, idx) => {
        const card = document.getElementById('pieceCard_' + idx);
        if (chk.checked) {
            selectedCount++;
            selectedTags.push(chk.value);
            if (card) {
                card.style.borderColor = '#0d6efd';
                card.style.backgroundColor = '#ffffff';
                card.style.opacity = '1';
                card.style.boxShadow = '0 0.125rem 0.25rem rgba(13, 110, 253, 0.12)';
            }
        } else {
            if (card) {
                card.style.borderColor = '#dee2e6';
                card.style.backgroundColor = '#f8f9fa';
                card.style.opacity = '0.65';
                card.style.boxShadow = 'none';
            }
        }
    });

    // Update spans & badges
    const selSpans = document.querySelectorAll('.selected-count-span');
    selSpans.forEach(el => el.innerText = selectedCount);

    const selectedPiecesBadge = document.getElementById('selectedPiecesBadge');
    if (selectedPiecesBadge) {
        selectedPiecesBadge.innerText = `${selectedCount} Selected`;
        if (selectedCount === 0) {
            selectedPiecesBadge.className = 'badge bg-secondary text-white rounded-pill';
        } else if (selectedCount < total) {
            selectedPiecesBadge.className = 'badge bg-warning text-dark rounded-pill fw-bold';
        } else {
            selectedPiecesBadge.className = 'badge bg-primary text-white rounded-pill shadow-2xs fw-bold';
        }
    }

    const dispScopeBadge = document.getElementById('dispScopeBadge');
    if (dispScopeBadge) {
        dispScopeBadge.innerText = `${selectedCount} of ${total} Pieces Picked`;
        dispScopeBadge.className = (selectedCount < total)
            ? 'badge bg-warning text-dark px-2 py-1 rounded-pill fw-bold'
            : 'badge bg-primary px-2 py-1 rounded-pill fs-7';
    }

    const dispScopeDesc = document.getElementById('dispScopeDesc');
    const remainingNotice = document.getElementById('dispScopeRemainingNotice');
    const remainingText = document.getElementById('dispScopeRemainingText');

    if (selectedCount === total) {
        if (dispScopeDesc) dispScopeDesc.innerText = `All ${total} pieces will be processed together.`;
        if (remainingNotice) remainingNotice.style.display = 'none';
    } else if (selectedCount === 0) {
        if (dispScopeDesc) dispScopeDesc.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle me-1"></i>No pieces picked! Please select at least 1 piece.</span>`;
        if (remainingNotice) remainingNotice.style.display = 'none';
    } else {
        const remaining = total - selectedCount;
        if (dispScopeDesc) dispScopeDesc.innerHTML = `<strong>${selectedCount} picked piece(s)</strong> will be dispatched to the selected disposition.`;
        if (remainingNotice) {
            remainingNotice.style.display = 'block';
            if (remainingText) remainingText.innerText = `Remaining ${remaining} piece(s) will stay quarantined in this Damage Case in the Warehouse.`;
        }
    }

    // Populate hidden inputs in the disposition form
    const container = document.getElementById('hiddenFormSelectedTags');
    if (container) {
        container.innerHTML = '';
        selectedTags.forEach(tag => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'selected_tags[]';
            inp.value = tag;
            container.appendChild(inp);
        });
    }
}

function selectAllPieces(checked) {
    const checkboxes = document.querySelectorAll('.piece-select-chk');
    checkboxes.forEach(chk => {
        chk.checked = checked;
    });
    syncPieceSelection();
}

function sendSelectedToRework() {
    const checked = document.querySelectorAll('.piece-select-chk:checked');
    if (checked.length === 0) {
        alert('Please pick at least one damaged piece card using the checkboxes first!');
        return;
    }
    const statusSelect = document.getElementById('damageStatusSelect');
    if (statusSelect) {
        statusSelect.value = 'REWORK';
        handleDamageStatusChange('REWORK');
    }
    const unitGroup = document.getElementById('reworkUnitGroup');
    const unitSelect = document.getElementById('reworkProductionUnitSelect');
    if (unitGroup) {
        unitGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
        unitGroup.style.boxShadow = '0 0 0 4px rgba(255, 193, 7, 0.4)';
        setTimeout(() => { unitGroup.style.boxShadow = ''; }, 1500);
    }
    if (unitSelect) {
        unitSelect.focus();
    }
}

function quickApplyStatus(newStatus, note) {
    syncPieceSelection();
    const checkedCount = document.querySelectorAll('.piece-select-chk:checked').length;
    if (checkedCount === 0) {
        alert('⚠️ Please pick at least one damaged piece using the checkboxes first!');
        return;
    }

    const form = document.getElementById('damageStatusForm');
    const statusSelect = document.getElementById('damageStatusSelect');
    const notesTextarea = document.getElementById('damageStatusNotes');

    if (newStatus === 'REWORK') {
        if (statusSelect) {
            statusSelect.value = 'REWORK';
            handleDamageStatusChange('REWORK');
        }
        if (notesTextarea && (!notesTextarea.value || notesTextarea.value.trim() === '')) {
            notesTextarea.value = note;
        }
        const unitGroup = document.getElementById('reworkUnitGroup');
        const unitSelect = document.getElementById('reworkProductionUnitSelect');
        if (unitGroup) {
            unitGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
            unitGroup.style.boxShadow = '0 0 0 3px rgba(255, 193, 7, 0.4)';
            setTimeout(() => { unitGroup.style.boxShadow = ''; }, 1500);
        }
        if (unitSelect) {
            unitSelect.focus();
        }
        return;
    }

    const statusLabels = {
        'REPAIRED': 'REPAIRED (Restock to Active Inventory)',
        'RESTOCKED': 'RESTOCKED (Restock to Active Inventory)',
        'WRITTEN_OFF': 'WRITTEN OFF (Total Loss Scrap)'
    };
    const label = statusLabels[newStatus] || newStatus;
    const totalPcs = document.querySelectorAll('.piece-select-chk').length;
    let confirmMsg = `Are you sure you want to update ${checkedCount} piece(s) to ${label}?`;
    if (checkedCount < totalPcs) {
        confirmMsg += `\n(The remaining ${totalPcs - checkedCount} pieces will stay quarantined in this case).`;
    }
    if (!confirm(confirmMsg)) {
        return;
    }
    if (statusSelect) statusSelect.value = newStatus;
    if (notesTextarea && note) notesTextarea.value = note;
    if (form) form.submit();
}

function printSingleDamagedLabel(tag, sku, mrp, price) {
    const printWindow = window.open('', '_blank', 'width=600,height=400');
    if (!printWindow) {
        alert('Please allow popups to print stickers.');
        return;
    }
    const payload = tag;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Damaged Tag - ${tag}</title>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>
            <style>
                @page { size: 50mm 25mm; margin: 0; }
                body { margin: 0; padding: 2mm; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 8px; line-height: 1.1; }
                .label-box { display: flex; align-items: center; justify-content: space-between; gap: 4px; height: 21mm; overflow: hidden; }
                .qr-area { width: 52px; height: 52px; flex-shrink: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
                .info-area { flex-grow: 1; overflow: hidden; padding-left: 2px; }
                .tag-badge { background: #000; color: #ffc107; font-family: monospace; font-size: 7.5px; font-weight: bold; padding: 1px 3px; border-radius: 2px; display: inline-block; margin-top: 1px; }
                .brand { font-weight: bold; font-size: 8px; text-transform: uppercase; }
                .sku { font-family: monospace; font-size: 7px; color: #333; margin: 1px 0; }
                .price { font-weight: bold; font-size: 8px; color: #d32f2f; margin-top: 1px; }
                .strike { text-decoration: line-through; color: #777; font-size: 7px; }
            </style>
        </head>
        <body>
            <div class="label-box">
                <div class="qr-area">
                    <div id="printQr"></div>
                    <div class="tag-badge">${tag}</div>
                </div>
                <div class="info-area">
                    <div class="brand">GRM WAREHOUSE (DAMAGED)</div>
                    <div class="sku">${sku}</div>
                    <div>${VIEW_PRODUCT_NAME.substring(0, 18)}</div>
                    <div>${VIEW_VARIANT_DESC}</div>
                    <div class="price">
                        ${mrp > price ? `<span class="strike">MRP: ₹${parseFloat(mrp).toFixed(2)}</span> ` : ''}
                        <span>Price: ₹${parseFloat(price).toFixed(2)}</span>
                    </div>
                </div>
            </div>
            <script>
                new QRCode(document.getElementById('printQr'), {
                    text: ${JSON.stringify(payload)},
                    width: 44,
                    height: 44,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
                setTimeout(function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 500);
                }, 300);
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function printAllDamagedLabels() {
    const printWindow = window.open('', '_blank', 'width=700,height=600');
    if (!printWindow) {
        alert('Please allow popups to print stickers.');
        return;
    }

    let labelsHtml = '';
    DAMAGED_TAG_LIST.forEach((tag, idx) => {
        const payload = tag;
        labelsHtml += `
            <div class="label-box">
                <div class="qr-area">
                    <div id="printQr_${idx}"></div>
                    <div class="tag-badge">${tag}</div>
                </div>
                <div class="info-area">
                    <div class="brand">GRM WAREHOUSE (DAMAGED)</div>
                    <div class="sku">${VIEW_SKU}</div>
                    <div>${VIEW_PRODUCT_NAME.substring(0, 18)}</div>
                    <div>${VIEW_VARIANT_DESC}</div>
                    <div class="price">
                        ${parseFloat(VIEW_MRP) > parseFloat(VIEW_PRICE) ? `<span class="strike">MRP: ₹${parseFloat(VIEW_MRP).toFixed(2)}</span> ` : ''}
                        <span>Price: ₹${parseFloat(VIEW_PRICE).toFixed(2)}</span>
                    </div>
                </div>
            </div>
        `;
    });

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print All Damaged Tags</title>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>
            <style>
                @page { size: 50mm 25mm; margin: 0; }
                body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
                .label-box { width: 48mm; height: 23mm; margin: 1mm auto; padding: 1mm; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; gap: 4px; overflow: hidden; page-break-after: always; }
                .qr-area { width: 52px; height: 52px; flex-shrink: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
                .info-area { flex-grow: 1; overflow: hidden; font-size: 7.5px; line-height: 1.1; }
                .tag-badge { background: #000; color: #ffc107; font-family: monospace; font-size: 7.5px; font-weight: bold; padding: 1px 3px; border-radius: 2px; display: inline-block; margin-top: 1px; }
                .brand { font-weight: bold; font-size: 8px; text-transform: uppercase; }
                .sku { font-family: monospace; font-size: 7px; color: #333; margin: 1px 0; }
                .price { font-weight: bold; font-size: 8px; color: #d32f2f; margin-top: 1px; }
                .strike { text-decoration: line-through; color: #777; font-size: 7px; }
            </style>
        </head>
        <body>
            ${labelsHtml}
            <script>
                ${DAMAGED_TAG_LIST.map((tag, idx) => {
                    const payload = tag;
                    return `
                        new QRCode(document.getElementById('printQr_${idx}'), {
                            text: ${JSON.stringify(payload)},
                            width: 44,
                            height: 44,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                    `;
                }).join('\n')}
                setTimeout(function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 500);
                }, 400);
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

document.addEventListener('DOMContentLoaded', function() {
    renderAllDamagedGridQRs();
    renderDamageViewQR();
    syncPieceSelection();

    const stSelect = document.getElementById('damageStatusSelect');
    if (stSelect) {
        handleDamageStatusChange(stSelect.value);
    }

    const stForm = document.getElementById('damageStatusForm');
    if (stForm) {
        stForm.addEventListener('submit', function(e) {
            syncPieceSelection();
            const checkedCount = document.querySelectorAll('.piece-select-chk:checked').length;
            if (checkedCount === 0) {
                e.preventDefault();
                alert('⚠️ Please select at least one damaged product piece to proceed.');
                return false;
            }

            const currentSt = document.getElementById('damageStatusSelect')?.value;
            const puSelect = document.getElementById('reworkProductionUnitSelect');
            if (currentSt === 'REWORK' && puSelect && (!puSelect.value || puSelect.value === '')) {
                e.preventDefault();
                alert('⚠️ Please select the destination Production Unit for this rework dispatch.');
                puSelect.focus();
                return false;
            }
        });
    }
});
</script>
<?php endif; ?>
