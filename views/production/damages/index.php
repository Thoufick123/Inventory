<div class="page-header mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 w-100">
        <div>
            <h2 class="mb-0"><i class="bi bi-exclamation-octagon me-2 text-danger"></i>Fabric &amp; Roll Damage Management</h2>
            <span class="text-muted">Track defective fabric rolls, initiate supplier returns, and manage refunds or replacement rolls</span>
        </div>
        <div class="d-flex gap-2 align-items-center ms-auto">
            <?php if (hasPermission('damages', 'create')): ?>
            <button type="button" class="btn btn-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#reportDamageModal">
                <i class="bi bi-plus-circle me-1"></i> Report Roll Damage
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- KPI Stats Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm bg-danger bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Damaged Rolls</span>
                    <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($stats['total_damaged_records']) ?> <span class="fs-6 fw-normal text-muted">records</span></h3>
                    <small class="text-muted"><?= number_format($stats['total_damaged_weight'], 2) ?> kg total</small>
                </div>
                <div class="bg-danger text-white rounded-3 p-3">
                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm bg-warning bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Estimated Loss Value</span>
                    <h3 class="fw-bold text-warning mb-0 mt-1">₹<?= number_format($stats['total_loss_amount'], 2) ?></h3>
                    <small class="text-muted"><?= $stats['count_logged'] ?> pending action</small>
                </div>
                <div class="bg-warning text-dark rounded-3 p-3">
                    <i class="bi bi-currency-rupee fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm bg-info bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Returns / Claims</span>
                    <h3 class="fw-bold text-info mb-0 mt-1"><?= number_format($stats['count_return_initiated']) ?> <span class="fs-6 fw-normal text-muted">in transit</span></h3>
                    <small class="text-muted"><?= $stats['count_replacement'] ?> replacements</small>
                </div>
                <div class="bg-info text-white rounded-3 p-3">
                    <i class="bi bi-arrow-left-right fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm bg-success bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Refunds Recovered</span>
                    <h3 class="fw-bold text-success mb-0 mt-1">₹<?= number_format($stats['total_refund_received'], 2) ?></h3>
                    <small class="text-muted"><?= $stats['count_refunded'] ?> debit claims</small>
                </div>
                <div class="bg-success text-white rounded-3 p-3">
                    <i class="bi bi-check2-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Search Card -->
<div class="card shadow-sm border mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/damages') ?>" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="damageSearchInput" class="form-control" placeholder="Search damage #, roll, supplier..." value="<?= e($search) ?>" autocomplete="off">
                </div>
            </div>
            <div class="col-md-3">
                <select name="seller_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- All Material Suppliers --</option>
                    <?php foreach ($sellers as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $sellerId == $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['name']) ?><?= !empty($s['company']) ? ' (' . e($s['company']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="reason" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- All Defect Reasons --</option>
                    <?php foreach ($damageReasons as $damageReasonOption): ?>
                        <?php $reasonValue = $damageReasonOption['damage_reason']; ?>
                        <option value="<?= e($reasonValue) ?>" <?= $reason === $reasonValue ? 'selected' : '' ?>><?= e($reasonValue) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- All Statuses --</option>
                    <option value="LOGGED" <?= $status === 'LOGGED' ? 'selected' : '' ?>>Logged (Pending Action)</option>
                    <option value="RETURN_INITIATED" <?= $status === 'RETURN_INITIATED' ? 'selected' : '' ?>>Return Initiated</option>
                    <option value="RETURNED_TO_SELLER" <?= $status === 'RETURNED_TO_SELLER' ? 'selected' : '' ?>>Returned to Supplier</option>
                    <option value="REFUND_RECEIVED" <?= $status === 'REFUND_RECEIVED' ? 'selected' : '' ?>>Refund Received</option>
                    <option value="REPLACEMENT_RECEIVED" <?= $status === 'REPLACEMENT_RECEIVED' ? 'selected' : '' ?>>Replacement Received</option>
                    <option value="CLOSED" <?= $status === 'CLOSED' ? 'selected' : '' ?>>Closed / Resolved</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="<?= url('production/damages') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Damage Records Table Card -->
<div class="card shadow-sm border mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0 fw-bold">
            <i class="bi bi-list-check me-2 text-danger"></i>Fabric Roll Damage Log (<?= count($damages) ?>)
        </h5>
        <span class="text-muted small">Damaged roll weights are automatically deducted from production programs</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($damages)): ?>
            <div class="text-center py-5 text-muted">
                <div class="d-inline-flex p-3 rounded-circle bg-light mb-2">
                    <i class="bi bi-shield-check fs-1 text-success"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">No Fabric Damages Recorded</h5>
                <p class="text-muted small mb-0">No fabric rolls match your filter criteria or have reported defect issues.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="damagesTable" style="font-size: 13px;">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Damage # &amp; Date</th>
                            <th>Supplier &amp; Lot</th>
                            <th>Roll Details</th>
                            <th>Defect Reason &amp; Notes</th>
                            <th class="text-end">Damaged Qty / Wt</th>
                            <th class="text-end">Loss Valuation</th>
                            <th class="text-center">Resolution &amp; Status</th>
                            <th class="text-center" style="width: 170px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($damages as $d): ?>
                            <tr class="damage-row">
                                <!-- Damage Number & Date -->
                                <td>
                                    <span class="badge bg-dark font-monospace fs-7 px-2 py-1"><?= e($d['damage_number']) ?></span>
                                    <div class="text-muted small mt-1">
                                        <i class="bi bi-clock me-1"></i><?= date('d M Y, h:i A', strtotime($d['created_at'])) ?>
                                    </div>
                                    <small class="text-muted d-block">By: <?= e($d['reported_by_name'] ?? 'Staff') ?></small>
                                </td>

                                <!-- Supplier & Mill -->
                                <td>
                                    <a href="<?= url('production/sellers/view/' . $d['seller_id']) ?>" class="fw-bold text-decoration-none text-dark d-block">
                                        <?= e($d['seller_name']) ?>
                                    </a>
                                    <?php if (!empty($d['seller_company'])): ?>
                                        <small class="text-muted"><i class="bi bi-building me-1"></i><?= e($d['seller_company']) ?></small>
                                    <?php endif; ?>
                                </td>

                                <!-- Roll Details & Photo -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($d['photo_url'])): ?>
                                            <a href="<?= uploadUrl($d['photo_url']) ?>" target="_blank" title="View damage proof photo">
                                                <img src="<?= uploadUrl($d['photo_url']) ?>" alt="Damage Photo" class="rounded border shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                                            </a>
                                        <?php elseif (!empty($d['roll_photo_url'])): ?>
                                            <a href="<?= uploadUrl($d['roll_photo_url']) ?>" target="_blank" title="View roll photo">
                                                <img src="<?= uploadUrl($d['roll_photo_url']) ?>" alt="Roll Photo" class="rounded border shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                                            </a>
                                        <?php else: ?>
                                            <div class="rounded bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 40px; height: 40px;">
                                                <i class="bi bi-circle-square text-secondary"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <span class="fw-bold text-dark fs-7"><?= e($d['roll_number']) ?></span>
                                            <div class="d-flex gap-1 flex-wrap mt-1">
                                                <?php if (!empty($d['roll_color'])): ?>
                                                    <span class="badge bg-info-subtle text-info border px-1"><?= e($d['roll_color']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($d['roll_design'])): ?>
                                                    <span class="badge bg-warning-subtle text-warning border px-1"><?= e($d['roll_design']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Defect Reason & Notes -->
                                <td>
                                    <span class="badge bg-danger-subtle text-danger border fw-bold mb-1 d-inline-block">
                                        <i class="bi bi-exclamation-diamond me-1"></i><?= e($d['damage_reason']) ?>
                                    </span>
                                    <?php if (!empty($d['defect_notes'])): ?>
                                        <div class="small text-muted text-wrap" style="max-width: 240px; line-height: 1.2;">
                                            <?= e($d['defect_notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Damaged Qty & Weight -->
                                <td class="text-end">
                                    <div class="fw-bold text-danger"><?= number_format($d['damaged_weight'], 2) ?> kg</div>
                                    <small class="text-muted"><?= (int)$d['damaged_quantity'] ?> Roll(s)</small>
                                </td>

                                <!-- Loss Valuation -->
                                <td class="text-end">
                                    <div class="fw-bold text-dark fs-7">₹ <?= number_format($d['loss_amount'], 2) ?></div>
                                    <?php if ($d['refund_amount'] > 0): ?>
                                        <small class="text-success fw-semibold d-block">
                                            <i class="bi bi-check-all me-1"></i>₹<?= number_format($d['refund_amount'], 2) ?> Refunded
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <!-- Status & Resolution -->
                                <td class="text-center">
                                    <?php
                                    $statusBadgeClass = match($d['status']) {
                                        'LOGGED'               => 'bg-danger-subtle text-danger border-danger-subtle',
                                        'RETURN_INITIATED'     => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                        'RETURNED_TO_SELLER'   => 'bg-info-subtle text-info border-info-subtle',
                                        'REFUND_RECEIVED'      => 'bg-success-subtle text-success border-success-subtle',
                                        'REPLACEMENT_RECEIVED' => 'bg-primary-subtle text-primary border-primary-subtle',
                                        'CLOSED'               => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                        default                => 'bg-light text-dark border'
                                    };
                                    $statusLabel = match($d['status']) {
                                        'LOGGED'               => 'Logged (Pending)',
                                        'RETURN_INITIATED'     => 'Return Initiated',
                                        'RETURNED_TO_SELLER'   => 'Returned to Supplier',
                                        'REFUND_RECEIVED'      => 'Refund / Debit Claimed',
                                        'REPLACEMENT_RECEIVED' => 'Replacement Received',
                                        'CLOSED'               => 'Closed / Resolved',
                                        default                => $d['status']
                                    };
                                    ?>
                                    <span class="badge <?= $statusBadgeClass ?> px-2 py-1 mb-1 d-inline-block">
                                        <?= $statusLabel ?>
                                    </span>
                                    <?php if (!empty($d['debit_note_number'])): ?>
                                        <small class="text-muted d-block font-monospace">DN: <?= e($d['debit_note_number']) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($d['replacement_roll_number']) || !empty($d['replacement_roll_code'])): ?>
                                        <small class="text-primary d-block font-monospace">Repl: <?= e($d['replacement_roll_code'] ?? $d['replacement_roll_number']) ?></small>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="text-center text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center gap-1 flex-nowrap">
                                        <a href="<?= url('production/damages/view/' . $d['id']) ?>" class="btn btn-sm btn-outline-info d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px;" title="View Damage Details" aria-label="View Damage Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('damages', 'edit')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px;" title="Edit Damage Record" aria-label="Edit Damage Record" onclick='openEditDamageModal(<?= json_encode($d) ?>)'>
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px;" title="Update Return / Refund / Resolution" aria-label="Update Return / Refund / Resolution" onclick='openResolveModal(<?= json_encode($d) ?>)'>
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (hasPermission('damages', 'delete')): ?>
                                        <form method="POST" action="<?= url('production/damages/delete/' . $d['id']) ?>" class="d-inline-flex flex-shrink-0" onsubmit="return confirm('Are you sure you want to delete Damage Record #<?= e(addslashes($d['damage_number'])) ?>? Roll inventory will be restored.');">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px;" title="Delete Damage Record" aria-label="Delete Damage Record">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls Footer -->
            <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="damagesPaginationFooter">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="small text-muted fw-medium" id="damagesPageInfo">
                        Showing 1 to 10 of <?= count($damages) ?> records
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-muted mb-0 fw-medium">Show:</label>
                        <select id="damagesPageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                            <option value="10" selected>10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                </div>
                <nav aria-label="Damages Pagination">
                    <ul class="pagination pagination-modern mb-0" id="damagesPaginationList">
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<datalist id="damageReasonOptions">
    <?php foreach ($damageReasons as $damageReasonOption): ?>
        <option value="<?= e($damageReasonOption['damage_reason']) ?>"></option>
    <?php endforeach; ?>
</datalist>

<!-- 1. Report Damage Modal -->
<div class="modal fade" id="reportDamageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-danger">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Report Fabric Roll Damage / Defect
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('production/damages/store') ?>" enctype="multipart/form-data" id="reportDamageForm">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="damageRollQrScan" class="form-label fw-bold">Scan Fabric Piece QR Labels</label>
                            <input type="text" id="damageRollQrScan" class="form-control form-control-lg" placeholder="Scan a QR and press Enter; repeat for labels from any roll" autocomplete="off">
                            <div id="damageRollScanStatus" class="small mt-1" role="status" aria-live="polite"></div>
                            <div class="border rounded mt-2 p-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Scanned labels</strong>
                                    <span id="damageScannedCount" class="badge bg-secondary">0 pieces</span>
                                </div>
                                <div id="damageScannedLabels" class="list-group small">
                                    <div class="list-group-item text-muted" id="damageScannedEmpty">No labels scanned yet.</div>
                                </div>
                            </div>
                            <div id="damageQrInputs"></div>
                        </div>

                        <!-- Damage Reason -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="reportReasonInput">Damage / Defect Reason <span class="text-danger">*</span></label>
                            <input type="text" name="damage_reason" id="reportReasonInput" class="form-control" list="damageReasonOptions" maxlength="255" required placeholder="Select an existing reason or type a new one">
                            <small class="text-muted d-block mt-1">Choose a saved reason or enter a new reason.</small>
                        </div>

                        <!-- Damaged Weight (Disabled with clear visibility - Auto-calculated) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">
                                Damaged Weight <span class="badge bg-secondary-subtle text-secondary border ms-1">Auto</span>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="damaged_weight" id="inputDamagedWeight" class="form-control bg-light fw-bold text-dark" placeholder="0.00" readonly tabindex="-1" style="background-color: #f1f3f5 !important; cursor: not-allowed;">
                                <span class="input-group-text bg-light">kg</span>
                            </div>
                            <small class="text-muted d-block mt-1" id="reportWeightHelp">Rate: ₹0.00 / kg</small>
                        </div>

                        <!-- Estimated Loss Valuation (Disabled with clear visibility - Auto-calculated) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">
                                <i class="bi bi-currency-rupee me-1"></i>Estimated Loss Valuation <span class="badge bg-danger-subtle text-danger border ms-1">Auto</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-danger-subtle text-danger fw-bold">₹</span>
                                <input type="number" step="0.01" name="loss_amount" id="inputEstimatedLoss" class="form-control fw-bold text-danger bg-light fs-6" placeholder="0.00" readonly tabindex="-1" style="background-color: #f1f3f5 !important; cursor: not-allowed;">
                                <span class="input-group-text bg-light text-muted small" id="reportRateBadge">₹0.00 / kg</span>
                            </div>
                            <small class="text-muted d-block mt-1" id="reportLossCalcHelp">Calculated: 0.00 kg × ₹0.00/kg</small>
                        </div>

                        <!-- Photo Evidence Upload (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold required">
                                <i class="bi bi-camera me-1 text-danger"></i>Defect Photo / Evidence
                            </label>
                            <input type="file" name="photo" id="reportPhotoInput" class="form-control" accept="image/*,application/pdf" required>
                            <small class="text-muted d-block mt-1">Upload clear photo/evidence of the damaged roll</small>
                        </div>

                        <!-- Specific Notes (Either Reason or Notes is required) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" id="reportNotesLabel">
                                Defect Notes &amp; Observations <small class="text-muted fw-normal">(Optional)</small>
                            </label>
                            <textarea name="defect_notes" id="reportDefectNotes" class="form-control" rows="2" placeholder="Add additional observations if needed"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Save Damage &amp; Deduct Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. Edit Damage Modal -->
<div class="modal fade" id="editDamageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-warning">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="bi bi-pencil-square me-2"></i>Edit Fabric Roll Damage Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editDamageForm" enctype="multipart/form-data">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <div class="p-2 px-3 bg-light rounded border mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="small text-muted d-block">RECORD NUMBER</span>
                            <span class="badge bg-dark font-monospace fs-7" id="editDamageNo">DMG-ROL-XXXX</span>
                        </div>
                        <div class="text-end">
                            <span class="small text-muted d-block">CURRENT STATUS</span>
                            <span class="badge bg-danger-subtle text-danger border" id="editDamageStatus">LOGGED</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Supplier Selection -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold required">Material Supplier / Mill</label>
                            <select name="seller_id" id="editSellerSelect" class="form-select form-select-lg" required onchange="loadSupplierRollsForEdit(this.value)">
                                <option value="">-- Choose Supplier --</option>
                                <?php foreach ($sellers as $s): ?>
                                    <option value="<?= $s['id'] ?>">
                                        <?= e($s['name']) ?><?= !empty($s['company']) ? ' (' . e($s['company']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Roll Selection -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold required">Defective Fabric Roll</label>
                            <select name="seller_roll_id" id="editRollSelect" class="form-select form-select-lg" required onchange="handleEditRollSelectionChange(this)">
                                <option value="">-- Select Supplier First --</option>
                            </select>
                        </div>

                        <!-- Roll Info Box -->
                        <div class="col-12" id="editRollInfoBox">
                            <div class="p-3 bg-light rounded border d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span class="small text-muted d-block">SELECTED ROLL SPECIFICATION</span>
                                    <strong class="text-dark fs-6" id="editInfoRollCode">ROLL-01</strong>
                                    <span class="text-muted mx-2">•</span>
                                    <span class="badge bg-info-subtle text-info border" id="editInfoRollColor">Color</span>
                                    <span class="badge bg-warning-subtle text-warning border" id="editInfoRollDesign">Design</span>
                                    <div class="mt-1 d-flex gap-1 flex-wrap" id="editInfoRollRates"></div>
                                </div>
                                <div class="text-end">
                                    <span class="small text-muted d-block">DAMAGEABLE BALANCE</span>
                                    <div id="editInfoRollBalance">0.00 kg</div>
                                    <small class="text-muted d-block" id="editInfoRollPrice">₹0.00</small>
                                </div>
                            </div>
                        </div>

                        <!-- Damage Reason -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="editReasonInput">Damage / Defect Reason <span class="text-danger">*</span></label>
                            <input type="text" name="damage_reason" id="editReasonInput" class="form-control" list="damageReasonOptions" maxlength="255" required placeholder="Select an existing reason or type a new one">
                            <small class="text-muted d-block mt-1">Choose a saved reason or enter a new reason.</small>
                        </div>

                        <!-- Damaged Quantity -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold required">Damaged Quantity</label>
                            <div class="input-group">
                                <input type="number" name="damaged_quantity" id="editDamagedQty" class="form-control" value="1" min="1" required oninput="onEditQtyChange()">
                                <span class="input-group-text bg-light">pcs</span>
                            </div>
                            <small class="text-muted d-block mt-1" id="editQtyHelp">1 pc = 0.00 kg</small>
                        </div>

                        <!-- Damaged Weight (Disabled with clear visibility) -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-dark">
                                Damaged Weight <span class="badge bg-secondary-subtle text-secondary border ms-1">Auto</span>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="damaged_weight" id="editDamagedWeight" class="form-control bg-light fw-bold text-dark" placeholder="0.00" readonly tabindex="-1" style="background-color: #f1f3f5 !important; cursor: not-allowed;">
                                <span class="input-group-text bg-light">kg</span>
                            </div>
                            <small class="text-muted d-block mt-1" id="editWeightHelp">Rate: ₹0.00 / kg</small>
                        </div>

                        <!-- Estimated Loss Valuation (Disabled with clear visibility) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">
                                <i class="bi bi-currency-rupee me-1"></i>Estimated Loss Valuation <span class="badge bg-danger-subtle text-danger border ms-1">Auto</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-danger-subtle text-danger fw-bold">₹</span>
                                <input type="number" step="0.01" name="loss_amount" id="editEstimatedLoss" class="form-control fw-bold text-danger bg-light fs-6" placeholder="0.00" readonly tabindex="-1" style="background-color: #f1f3f5 !important; cursor: not-allowed;">
                                <span class="input-group-text bg-light text-muted small" id="editRateBadge">₹0.00 / kg</span>
                            </div>
                            <small class="text-muted d-block mt-1" id="editLossCalcHelp">Calculated: 0.00 kg × ₹0.00/kg</small>
                        </div>

                        <!-- Photo Upload & Current Preview -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" id="editPhotoLabel">
                                Defect Photo / Evidence <span class="text-danger" id="editPhotoReqStar" style="display:none;">*</span>
                            </label>
                            <input type="file" name="photo" id="editPhotoInput" class="form-control" accept="image/*,application/pdf">
                            <div id="editCurrentPhotoBox" class="mt-2" style="display: none;">
                                <small class="text-muted d-block mb-1">Current Attached Proof:</small>
                                <a href="#" target="_blank" id="editCurrentPhotoLink" class="d-inline-block">
                                    <img src="" id="editCurrentPhotoImg" class="rounded border shadow-sm" style="max-height: 50px; max-width: 100px; object-fit: cover;">
                                </a>
                            </div>
                        </div>

                        <!-- Defect Notes -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" id="editNotesLabel">
                                Defect Notes &amp; Observations <small class="text-muted fw-normal">(Optional)</small>
                            </label>
                            <textarea name="defect_notes" id="editDefectNotes" class="form-control" rows="2" placeholder="Add additional observations if needed"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Update Damage Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. Resolve / Supplier Return & Refund Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-primary">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="bi bi-arrow-repeat me-2"></i>Resolve Fabric Damage &amp; Supplier Return / Claim
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="resolveDamageForm">
                <?= CSRF::field() ?>
                <div class="modal-body p-4">
                    <!-- Damage Summary Banner -->
                    <div class="p-3 bg-light rounded border mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="badge bg-dark font-monospace" id="resDamageNo">DMG-ROL-XXXX</span>
                            <strong class="text-dark ms-2" id="resSellerName">Supplier</strong>
                            <div class="small text-muted mt-1">
                                Roll: <span class="fw-semibold text-dark" id="resRollInfo">Roll #01</span> • 
                                Defect: <span class="badge bg-danger-subtle text-danger" id="resReason">Reason</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="small text-muted d-block">ORIGINAL LOSS VALUATION</span>
                            <span class="fs-5 fw-bold text-danger" id="resLossAmount">₹0.00</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Action Status (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold required">Update Damage Status</label>
                            <select name="status" id="resStatusSelect" class="form-select form-select-lg" required onchange="handleResolutionStatusChange(this.value)">
                                <option value="">-- Choose Damage Status --</option>
                                <option value="LOGGED">Logged (Pending Action)</option>
                                <option value="RETURN_INITIATED">Return Initiated to Supplier</option>
                                <option value="RETURNED_TO_SELLER">Returned / Delivered to Supplier</option>
                                <option value="REFUND_RECEIVED">Refund / Debit Note Received</option>
                                <option value="REPLACEMENT_RECEIVED">Replacement Roll Received</option>
                                <option value="CLOSED">Closed / Resolved</option>
                            </select>
                        </div>

                        <!-- Resolution Type (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold required">Resolution Method</label>
                            <select name="resolution_type" id="resTypeSelect" class="form-select form-select-lg" required onchange="handleResolutionTypeChange(this.value)">
                                <option value="">-- Choose Resolution Method --</option>
                                <option value="SUPPLIER_REFUND">Supplier Refund / Debit Note (Amount Credited)</option>
                                <option value="SUPPLIER_REPLACEMENT">Supplier Replacement Roll (Exchange for New Roll)</option>
                                <option value="RATE_CONCESSION">Rate Concession / Discount Adjustment</option>
                                <option value="SCRAP_WRITE_OFF">Scrap / Internal Write-Off</option>
                            </select>
                        </div>

                        <!-- 1. Refund Fields (shown when Refund / Debit Note) -->
                        <div class="col-md-6 res-field-refund" id="fieldRefundAmount" style="display: none;">
                            <label class="form-label fw-bold text-success">Refund / Debit Amount Received (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success-subtle text-success">₹</span>
                                <input type="number" step="0.01" name="refund_amount" id="inputRefundAmount" class="form-control fw-bold text-success" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6 res-field-refund" id="fieldDebitNote" style="display: none;">
                            <label class="form-label fw-semibold">Supplier Debit / Credit Note #</label>
                            <input type="text" name="debit_note_number" id="inputDebitNote" class="form-control" placeholder="e.g. DN-2026-8841">
                        </div>

                        <!-- 2. Replacement Fields (shown when Replacement Roll) -->
                        <div class="col-md-6 res-field-replacement" id="fieldReplacementRoll" style="display: none;">
                            <label class="form-label fw-bold text-primary">Replacement Roll Number / Tag</label>
                            <input type="text" name="replacement_roll_number" id="inputReplacementRoll" class="form-control fw-bold border-primary" placeholder="e.g. ROLL-01-REPL">
                        </div>
                        <div class="col-md-6 res-field-replacement" id="fieldCreateRollOption" style="display: none;">
                            <div class="form-check form-switch mt-4 pt-2">
                                <input class="form-check-input" type="checkbox" name="create_replacement_roll" id="checkCreateReplacement" value="1" checked>
                                <label class="form-check-label fw-semibold" for="checkCreateReplacement">
                                     Automatically add this replacement roll to Supplier's active inventory
                                </label>
                            </div>
                        </div>

                        <!-- Resolution Notes (Optional) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Resolution Notes &amp; Supplier Remarks <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <textarea name="resolution_notes" id="inputResolutionNotes" class="form-control" rows="2" placeholder="e.g. Supplier agreed to issue debit note in next invoice / replacement roll delivered on..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Save Resolution
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let loadedEditRollsMap = {};
let damageScannedLabels = [];
let damageScanInProgress = false;

function setDamageRollScanStatus(message, isError = false) {
    const status = document.getElementById('damageRollScanStatus');
    if (!status) return;
    status.textContent = message;
    status.className = `small mt-1 ${isError ? 'text-danger' : 'text-success'}`;
}

function clearDamageRollSelection() {
    damageScannedLabels = [];
    renderDamageScannedLabels();
    document.getElementById('inputDamagedWeight').value = '';
    document.getElementById('inputEstimatedLoss').value = '';
}

function renderDamageScannedLabels() {
    const list = document.getElementById('damageScannedLabels');
    const inputs = document.getElementById('damageQrInputs');
    if (!list || !inputs) return;

    list.replaceChildren();
    inputs.replaceChildren();
    const count = document.getElementById('damageScannedCount');
    if (count) count.textContent = `${damageScannedLabels.length} ${damageScannedLabels.length === 1 ? 'piece' : 'pieces'}`;

    if (damageScannedLabels.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'list-group-item text-muted';
        empty.textContent = 'No labels scanned yet.';
        list.appendChild(empty);
    }

    let totalWeight = 0;
    let totalLoss = 0;
    damageScannedLabels.forEach((item, index) => {
        totalWeight += item.weight;
        totalLoss += item.loss;

        const row = document.createElement('div');
        row.className = 'list-group-item d-flex justify-content-between align-items-center gap-2';
        const details = document.createElement('div');
        details.className = 'text-break';
        const code = document.createElement('strong');
        code.textContent = item.code;
        const metadata = document.createElement('div');
        metadata.className = 'text-muted';
        metadata.textContent = `${item.sellerName} • ${item.rollNumber} • ${item.weight.toFixed(2)} kg`;
        details.append(code, metadata);

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-outline-danger flex-shrink-0';
        remove.textContent = 'Remove';
        remove.setAttribute('aria-label', `Remove ${item.code}`);
        remove.addEventListener('click', () => {
            damageScannedLabels.splice(index, 1);
            renderDamageScannedLabels();
        });
        row.append(details, remove);
        list.appendChild(row);

        const hiddenCode = document.createElement('input');
        hiddenCode.type = 'hidden';
        hiddenCode.name = 'qr_codes[]';
        hiddenCode.value = item.code;
        inputs.appendChild(hiddenCode);
    });

    document.getElementById('inputDamagedWeight').value = totalWeight.toFixed(2);
    document.getElementById('inputEstimatedLoss').value = totalLoss.toFixed(2);
    const weightHelp = document.getElementById('reportWeightHelp');
    if (weightHelp) weightHelp.textContent = `${damageScannedLabels.length} scanned piece(s)`;
    const rateBadge = document.getElementById('reportRateBadge');
    if (rateBadge) rateBadge.textContent = totalWeight > 0 ? `₹${(totalLoss / totalWeight).toFixed(2)} / kg` : '₹0.00 / kg';
    const lossHelp = document.getElementById('reportLossCalcHelp');
    if (lossHelp) lossHelp.textContent = `Estimated loss for ${damageScannedLabels.length} scanned piece(s): ₹${totalLoss.toFixed(2)}`;
}

function scanDamageRollQr() {
    const scanInput = document.getElementById('damageRollQrScan');
    const scannedCode = scanInput ? scanInput.value.trim() : '';
    if (!scannedCode || damageScanInProgress) return;

    const normalizedCode = scannedCode.split('|', 1)[0].trim().toUpperCase();
    if (damageScannedLabels.some(item => item.code === normalizedCode)) {
        setDamageRollScanStatus(`QR label ${normalizedCode} is already in this damage list.`, true);
        scanInput.select();
        return;
    }

    damageScanInProgress = true;
    setDamageRollScanStatus('Looking up fabric roll…');
    fetch('<?= url('api/production/damages/scan-roll') ?>?code=' + encodeURIComponent(scannedCode))
        .then(async response => {
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Unable to find this fabric roll.');
            }
            return data;
        })
        .then(data => {
            const quantity = Math.max(1, parseInt(data.quantity, 10) || 1);
            const rollWeight = parseFloat(data.weight) || 0;
            const rollPrice = parseFloat(data.price) || 0;
            const pieceWeight = Math.round((rollWeight / quantity) * 100) / 100;
            const loss = Math.round(pieceWeight * (rollWeight > 0 ? rollPrice / rollWeight : 0) * 100) / 100;
            damageScannedLabels.push({
                code: data.code,
                sellerName: data.seller_name,
                rollNumber: data.roll_number,
                weight: pieceWeight,
                loss
            });
            renderDamageScannedLabels();
            setDamageRollScanStatus(`Added ${data.code} — ${data.seller_name}, ${data.roll_number}. Scan another label or submit.`);
            scanInput.value = '';
            scanInput.focus();
        })
        .catch(error => {
            setDamageRollScanStatus(error.message || 'Unable to scan this fabric roll.', true);
        })
        .finally(() => {
            damageScanInProgress = false;
        });
}

function getRollMetrics(r) {
    const totQty = parseInt(r.quantity) || 1;
    const totWeight = parseFloat(r.weight) || 0;
    const totPrice = parseFloat(r.price) || 0;
    const availQty = r.available_quantity !== undefined ? Math.max(0, parseInt(r.available_quantity, 10) || 0) : totQty;
    const availWeight = r.available_weight !== undefined ? Math.max(0, parseFloat(r.available_weight) || 0) : totWeight;
    
    const weightPerPc = totQty > 0 ? (totWeight / totQty) : totWeight;
    const ratePerKg = totWeight > 0 ? (totPrice / totWeight) : 0;
    const pricePerPc = totQty > 0 ? (totPrice / totQty) : totPrice;
    
    return {
        totQty,
        totWeight,
        totPrice,
        availQty,
        availWeight,
        usedQty: parseInt(r.used_quantity, 10) || 0,
        weightPerPc,
        ratePerKg,
        pricePerPc
    };
}

function updateEditCalculations(m, qty, weight) {
    if (!m) return;
    const lossVal = weight * m.ratePerKg;
    
    const lossInput = document.getElementById('editEstimatedLoss');
    if (lossInput) lossInput.value = lossVal.toFixed(2);

    const rateBadge = document.getElementById('editRateBadge');
    if (rateBadge) rateBadge.textContent = `₹${m.ratePerKg.toFixed(2)} / kg`;

    const qtyHelp = document.getElementById('editQtyHelp');
    if (qtyHelp) qtyHelp.textContent = `1 pc = ${m.weightPerPc.toFixed(2)} kg (Max damageable: ${m.availQty} pcs)`;

    const weightHelp = document.getElementById('editWeightHelp');
    if (weightHelp) weightHelp.textContent = `Rate: ₹${m.ratePerKg.toFixed(2)} / kg`;

    const lossHelp = document.getElementById('editLossCalcHelp');
    if (lossHelp) lossHelp.textContent = `Calculated: ${weight.toFixed(2)} kg × ₹${m.ratePerKg.toFixed(2)}/kg = ₹${lossVal.toFixed(2)}`;
}

function onEditQtyChange() {
    const rollId = document.getElementById('editRollSelect').value;
    if (!rollId || !loadedEditRollsMap[rollId]) return;
    const m = getRollMetrics(loadedEditRollsMap[rollId]);

    const qtyInput = document.getElementById('editDamagedQty');
    let qty = parseInt(qtyInput.value) || 0;
    const maxQty = m.availQty;

    if (qty > maxQty) {
        alert(`Damaged Quantity (${qty} pcs) cannot exceed the available roll quantity (${maxQty} pcs) in supplier stock.`);
        qty = maxQty;
        qtyInput.value = maxQty;
    } else if (qty < 1 && qtyInput.value !== '' && maxQty > 0) {
        qty = 1;
        qtyInput.value = 1;
    }

    const calcWeight = qty * m.weightPerPc;
    const weightInput = document.getElementById('editDamagedWeight');
    if (weightInput) {
        weightInput.value = calcWeight.toFixed(2);
    }
    
    updateEditCalculations(m, qty, calcWeight);
}

function onEditWeightChange() {
    const rollId = document.getElementById('editRollSelect').value;
    if (!rollId || !loadedEditRollsMap[rollId]) return;
    const m = getRollMetrics(loadedEditRollsMap[rollId]);

    const weight = parseFloat(document.getElementById('editDamagedWeight').value) || 0;
    const qty = parseInt(document.getElementById('editDamagedQty').value) || 1;
    
    updateEditCalculations(m, qty, weight);
}

function openEditDamageModal(damageData) {
    document.getElementById('editDamageForm').action = '<?= url('production/damages/update') ?>/' + damageData.id;
    document.getElementById('editDamageNo').textContent = damageData.damage_number;
    document.getElementById('editDamageStatus').textContent = damageData.status;

    document.getElementById('editSellerSelect').value = damageData.seller_id;
    document.getElementById('editReasonInput').value = damageData.damage_reason;
    document.getElementById('editDamagedQty').value = damageData.damaged_quantity || 1;
    document.getElementById('editDamagedWeight').value = parseFloat(damageData.damaged_weight || 0).toFixed(2);
    document.getElementById('editEstimatedLoss').value = parseFloat(damageData.loss_amount || 0).toFixed(2);
    document.getElementById('editDefectNotes').value = damageData.defect_notes || '';

    // Photo preview
    const photoBox = document.getElementById('editCurrentPhotoBox');
    const photoLink = document.getElementById('editCurrentPhotoLink');
    const photoImg = document.getElementById('editCurrentPhotoImg');
    const photoInput = document.getElementById('editPhotoInput');
    const photoReqStar = document.getElementById('editPhotoReqStar');

    if (damageData.photo_url) {
        photoBox.style.display = '';
        const url = damageData.photo_url.startsWith('http') ? damageData.photo_url : ('<?= uploadUrl('') ?>/' + damageData.photo_url);
        photoLink.href = url;
        photoImg.src = url;
        if (photoInput) photoInput.removeAttribute('required');
        if (photoReqStar) photoReqStar.style.display = 'none';
    } else {
        photoBox.style.display = 'none';
        if (photoInput) photoInput.setAttribute('required', 'required');
        if (photoReqStar) photoReqStar.style.display = '';
    }

    loadSupplierRollsForEdit(damageData.seller_id, damageData.seller_roll_id, damageData.id);

    const modal = new bootstrap.Modal(document.getElementById('editDamageModal'));
    modal.show();
}

function loadSupplierRollsForEdit(sellerId, selectedRollId = null, excludeDamageId = 0) {
    const rollSelect = document.getElementById('editRollSelect');
    const infoBox = document.getElementById('editRollInfoBox');
    if (!sellerId) {
        rollSelect.innerHTML = '<option value="">-- Select Supplier First --</option>';
        if (infoBox) infoBox.style.display = 'none';
        return;
    }

    rollSelect.innerHTML = '<option value="">Loading rolls...</option>';

    fetch('<?= url('api/sellers') ?>/' + sellerId + '/rolls?exclude_damage_id=' + encodeURIComponent(excludeDamageId))
        .then(res => res.json())
        .then(data => {
            rollSelect.innerHTML = '<option value="">-- Choose Fabric Roll --</option>';
            loadedEditRollsMap = {};

            if (data.rolls && data.rolls.length > 0) {
                data.rolls.forEach(r => {
                    loadedEditRollsMap[r.id] = r;
                    const opt = document.createElement('option');
                    opt.value = r.id;
                    const availWt = parseFloat(r.available_weight) || 0;
                    const totalWt = parseFloat(r.weight) || 0;
                    const availQty = parseInt(r.available_quantity, 10) || 0;
                    const usedQty = parseInt(r.used_quantity, 10) || 0;
                    opt.textContent = `${r.roll_number} (${r.color || 'No Color'} - ${r.design_name || 'No Design'}) • Damageable: ${availQty} pcs / ${availWt.toFixed(2)} kg${usedQty > 0 ? ` • ${usedQty} pcs assigned` : ''}`;
                    if (selectedRollId && parseInt(selectedRollId) === parseInt(r.id)) {
                        opt.selected = true;
                    }
                    rollSelect.appendChild(opt);
                });

                if (selectedRollId) {
                    handleEditRollSelectionChange(rollSelect, false);
                }
            } else {
                rollSelect.innerHTML = '<option value="">No rolls found for this supplier</option>';
            }
        })
        .catch(err => {
            console.error('Error fetching edit rolls:', err);
            rollSelect.innerHTML = '<option value="">Failed to load rolls</option>';
        });
}

function handleEditRollSelectionChange(selectEl, resetValues = true) {
    const rollId = selectEl.value;
    const infoBox = document.getElementById('editRollInfoBox');
    if (!rollId || !loadedEditRollsMap[rollId]) {
        if (infoBox) infoBox.style.display = 'none';
        return;
    }

    const r = loadedEditRollsMap[rollId];
    const m = getRollMetrics(r);

    document.getElementById('editInfoRollCode').textContent = r.roll_number;
    document.getElementById('editInfoRollColor').textContent = r.color || 'Standard';
    document.getElementById('editInfoRollDesign').textContent = r.design_name || 'Plain';
    
    document.getElementById('editInfoRollRates').innerHTML = 
        `<span class="badge bg-success-subtle text-success border me-1">1 kg = ₹${m.ratePerKg.toFixed(2)}</span>` +
        `<span class="badge bg-primary-subtle text-primary border">1 pc = ${m.weightPerPc.toFixed(2)} kg (₹${m.pricePerPc.toFixed(2)})</span>`;

    if (m.availWeight > 0) {
        document.getElementById('editInfoRollBalance').innerHTML = `<span class="text-success fw-bold">${m.availWeight.toFixed(2)} kg damageable</span> <small class="text-muted">(${m.availQty} pcs damageable / ${m.totWeight.toFixed(2)} kg total${m.usedQty ? ` • ${m.usedQty} pcs assigned` : ''})</small>`;
    } else {
        document.getElementById('editInfoRollBalance').innerHTML = `<span class="text-dark fw-bold">No damageable balance</span> <small class="text-muted">(${m.usedQty || 0} pcs assigned / ${m.totQty} pcs total)</small>`;
    }
    document.getElementById('editInfoRollPrice').textContent = `Total Lot: ₹${m.totPrice.toFixed(2)}`;

    const qtyInput = document.getElementById('editDamagedQty');
    if (qtyInput) {
        qtyInput.max = m.availQty;
        qtyInput.disabled = m.availQty < 1;
    }

    if (resetValues) {
        const defaultQty = Math.min(1, m.availQty);
        const defaultWeight = defaultQty * m.weightPerPc;
        if (qtyInput) qtyInput.value = defaultQty;
        document.getElementById('editDamagedWeight').value = defaultWeight.toFixed(2);
        updateEditCalculations(m, defaultQty, defaultWeight);
    } else {
        const curQty = parseInt(document.getElementById('editDamagedQty').value) || 1;
        const curWeight = parseFloat(document.getElementById('editDamagedWeight').value) || (curQty * m.weightPerPc);
        updateEditCalculations(m, curQty, curWeight);
    }

    if (infoBox) infoBox.style.display = '';
}

function openResolveModal(damageData) {
    document.getElementById('resolveDamageForm').action = '<?= url('production/damages/resolve') ?>/' + damageData.id;
    document.getElementById('resDamageNo').textContent = damageData.damage_number;
    document.getElementById('resSellerName').textContent = damageData.seller_name;
    document.getElementById('resRollInfo').textContent = `${damageData.roll_number} (${damageData.damaged_weight} kg)`;
    document.getElementById('resReason').textContent = damageData.damage_reason;
    document.getElementById('resLossAmount').textContent = '₹ ' + parseFloat(damageData.loss_amount).toFixed(2);

    document.getElementById('resStatusSelect').value = damageData.status || 'LOGGED';
    document.getElementById('resTypeSelect').value = (damageData.resolution_type && damageData.resolution_type !== 'NONE') ? damageData.resolution_type : '';
    document.getElementById('inputRefundAmount').value = damageData.refund_amount > 0 ? damageData.refund_amount : damageData.loss_amount;
    document.getElementById('inputDebitNote').value = damageData.debit_note_number || '';
    document.getElementById('inputReplacementRoll').value = damageData.replacement_roll_number || (damageData.roll_number + '-REPL');
    document.getElementById('inputResolutionNotes').value = damageData.resolution_notes || '';

    handleResolutionStatusChange(document.getElementById('resStatusSelect').value);
    handleResolutionTypeChange(document.getElementById('resTypeSelect').value);

    const modal = new bootstrap.Modal(document.getElementById('resolveModal'));
    modal.show();
}

function handleResolutionStatusChange(status) {
    const resType = document.getElementById('resTypeSelect');
    if (status === 'REFUND_RECEIVED') {
        resType.value = 'SUPPLIER_REFUND';
    } else if (status === 'REPLACEMENT_RECEIVED') {
        resType.value = 'SUPPLIER_REPLACEMENT';
    }
    handleResolutionTypeChange(resType.value);
}

function handleResolutionTypeChange(type) {
    const refundFields = document.querySelectorAll('.res-field-refund');
    const replFields = document.querySelectorAll('.res-field-replacement');

    refundFields.forEach(el => el.style.display = (type === 'SUPPLIER_REFUND' || type === 'RATE_CONCESSION') ? '' : 'none');
    replFields.forEach(el => el.style.display = (type === 'SUPPLIER_REPLACEMENT') ? '' : 'none');
}

// Client-side form submit validation
document.addEventListener('DOMContentLoaded', () => {
    const scanInput = document.getElementById('damageRollQrScan');
    if (scanInput) {
        const damageModal = document.getElementById('reportDamageModal');
        damageModal.addEventListener('shown.bs.modal', () => scanInput.focus());
        damageModal.addEventListener('hidden.bs.modal', () => {
            clearDamageRollSelection();
            scanInput.value = '';
            setDamageRollScanStatus('');
        });
        scanInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                scanDamageRollQr();
            }
        });
        scanInput.addEventListener('input', () => {
            if (!damageScanInProgress) setDamageRollScanStatus('');
        });
    }

    const resolveForm = document.getElementById('resolveDamageForm');
    if (resolveForm) {
        resolveForm.addEventListener('submit', function(e) {
            const status = document.getElementById('resStatusSelect').value.trim();
            const resType = document.getElementById('resTypeSelect').value.trim();

            if (!status) {
                alert('Update Damage Status is required. Please select a status.');
                e.preventDefault();
                return false;
            }
            if (!resType || resType === 'NONE') {
                alert('Resolution Method is required. Please select a resolution method.');
                e.preventDefault();
                return false;
            }
        });
    }

    const reportForm = document.getElementById('reportDamageForm');
    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            const reason = document.getElementById('reportReasonInput').value.trim();
            const photoInput = document.getElementById('reportPhotoInput');

            if (damageScannedLabels.length === 0) {
                alert('Scan at least one valid fabric QR label first.');
                e.preventDefault();
                return false;
            }
            if (damageScanInProgress) {
                alert('Please wait for the current QR scan to finish.');
                e.preventDefault();
                return false;
            }
            if (!reason) {
                alert('Please enter a Damage / Defect Reason.');
                e.preventDefault();
                return false;
            }
            if (photoInput && photoInput.hasAttribute('required') && (!photoInput.files || photoInput.files.length === 0)) {
                alert('Defect Photo / Evidence is required. Please attach a clear photo of the damaged roll.');
                e.preventDefault();
                return false;
            }
        });
    }

    const editForm = document.getElementById('editDamageForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const sellerId = document.getElementById('editSellerSelect').value;
            const rollId = document.getElementById('editRollSelect').value;
            const reason = document.getElementById('editReasonInput').value.trim();
            const qty = parseInt(document.getElementById('editDamagedQty').value) || 0;
            const photoInput = document.getElementById('editPhotoInput');

            if (!sellerId) {
                alert('Please choose a Material Supplier / Mill.');
                e.preventDefault();
                return false;
            }
            if (!rollId) {
                alert('Please select a Defective Fabric Roll.');
                e.preventDefault();
                return false;
            }
            if (rollId && loadedEditRollsMap[rollId]) {
                const m = getRollMetrics(loadedEditRollsMap[rollId]);
                const maxQty = m.availQty;
                if (qty > maxQty) {
                    alert(`Damaged Quantity (${qty} pcs) cannot exceed the available roll quantity (${maxQty} pcs).`);
                    e.preventDefault();
                    return false;
                }
                if (qty < 1) {
                    alert('Damaged Quantity must be at least 1 pc.');
                    e.preventDefault();
                    return false;
                }
            }
            if (!reason) {
                alert('Please enter a Damage / Defect Reason.');
                e.preventDefault();
                return false;
            }
            if (photoInput && photoInput.hasAttribute('required') && (!photoInput.files || photoInput.files.length === 0)) {
                alert('Defect Photo / Evidence is required. Please attach a photo.');
                e.preventDefault();
                return false;
            }
        });
    }

    // Deep-linking from Supplier view
    const urlParams = new URLSearchParams(window.location.search);
    const reportSellerId = urlParams.get('report_seller_id');
    if (reportSellerId) {
        const modal = new bootstrap.Modal(document.getElementById('reportDamageModal'));
        modal.show();
        setDamageRollScanStatus('Scan the specific fabric QR label to report damage.');
    }

    // Initialize Damage Records Table Pagination
    if (typeof window.initClientPagination === 'function' && document.getElementById('damagesTable')) {
        const damagesPagination = window.initClientPagination({
            container: '#damagesTable tbody',
            itemSelector: 'tr.damage-row',
            footer: '#damagesPaginationFooter',
            infoEl: '#damagesPageInfo',
            paginationList: '#damagesPaginationList',
            pageSizeSelect: '#damagesPageSizeSelect',
            pageSize: 10,
            itemName: 'records'
        });

        const searchInput = document.getElementById('damageSearchInput');
        if (searchInput && damagesPagination) {
            let debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const q = searchInput.value.trim().toLowerCase();
                    damagesPagination.filter(row => {
                        if (!q) return true;
                        const text = (row.textContent || '').toLowerCase();
                        return text.includes(q);
                    });
                }, 150);
            });
        }
    }
});
</script>
