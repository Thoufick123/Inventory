<?php
// Stitching Scrap & Wastage Selling Management View
?>
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-thread text-warning me-2"></i>Stitching Scrap &amp; Wastage Selling
            </h4>
            <span class="text-muted small">Maintain selling prices, track scrap buyers, and record revenue from sewing thread, cut bits, and trim scrap</span>
        </div>
        <div class="d-flex gap-2">
            <?php if (hasPermission('stitching_wastage', 'create')): ?>
            <button type="button" class="btn btn-warning text-dark btn-sm fw-semibold shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#recordStitchingWastageModal">
                <i class="bi bi-plus-circle me-1"></i> Record Stitching Wastage Sale
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Quantity Sold -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Wastage Sold</span>
                        <div class="p-2 rounded-circle bg-warning-subtle text-warning">
                            <i class="bi bi-box2-heart fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-1"><?= number_format($stats['total_quantity'], 2) ?> <span class="fs-6 fw-normal text-muted">Units/kg</span></h3>
                    <div class="small text-muted d-flex justify-content-between align-items-center">
                        <span><?= number_format($stats['total_records']) ?> Sale Transaction(s)</span>
                        <span class="badge bg-light text-secondary border">Stitching Scrap</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Sales Value -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Total Sales Value</span>
                        <div class="p-2 rounded-circle bg-primary-subtle text-primary">
                            <i class="bi bi-currency-rupee fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-primary mb-1">₹ <?= number_format($stats['total_amount'], 2) ?></h3>
                    <div class="small text-muted d-flex justify-content-between align-items-center">
                        <span>Avg: <strong class="text-dark">₹<?= number_format($stats['avg_rate_per_unit'], 2) ?>/unit</strong></span>
                        <span class="badge bg-primary-subtle text-primary border">Total Billed</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Collected Revenue -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Paid / Received</span>
                        <div class="p-2 rounded-circle bg-success-subtle text-success">
                            <i class="bi bi-cash-stack fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-1">₹ <?= number_format($stats['total_paid'], 2) ?></h3>
                    <div class="small text-muted d-flex justify-content-between align-items-center">
                        <span><?= number_format($stats['count_paid']) ?> Fully Paid</span>
                        <span class="badge bg-success-subtle text-success border">Realized</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Pending Collections -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Pending Receivables</span>
                        <div class="p-2 rounded-circle bg-danger-subtle text-danger">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-danger mb-1">₹ <?= number_format($stats['total_pending'], 2) ?></h3>
                    <div class="small text-muted d-flex justify-content-between align-items-center">
                        <span><?= number_format($stats['count_pending'] + $stats['count_partial']) ?> Unsettled</span>
                        <span class="badge bg-danger-subtle text-danger border">Due</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('production/stitching-wastage') ?>" class="row g-2 align-items-center">
                <!-- Search -->
                <div class="col-lg-3 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="stitchingWastageSearchInput" class="form-control form-control-sm border-start-0" placeholder="Search buyer, sale #, batch, inv..." value="<?= e($search) ?>" autocomplete="off">
                    </div>
                </div>

                <!-- Stitching Batch Filter -->
                <div class="col-lg-2 col-md-4">
                    <select name="stitching_batch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Stitching Batches --</option>
                        <?php foreach ($stitchingBatches as $sb): ?>
                            <option value="<?= $sb['id'] ?>" <?= (string)($stitchingBatchId ?? '') === (string)$sb['id'] ? 'selected' : '' ?>>
                                <?= e($sb['batch_number']) ?><?= !empty($sb['name']) ? ' (' . e($sb['name']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Waste Type -->
                <div class="col-lg-2 col-md-4">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Waste Types --</option>
                        <option value="THREAD_WASTE" <?= $type === 'THREAD_WASTE' ? 'selected' : '' ?>>Thread Waste</option>
                        <option value="SEAM_TRIMMINGS" <?= $type === 'SEAM_TRIMMINGS' ? 'selected' : '' ?>>Seam &amp; Trimmings</option>
                        <option value="REJECTED_PANEL_SCRAP" <?= $type === 'REJECTED_PANEL_SCRAP' ? 'selected' : '' ?>>Rejected Panel Scrap</option>
                        <option value="STITCHING_LINE_WASTE" <?= $type === 'STITCHING_LINE_WASTE' ? 'selected' : '' ?>>Line Floor Waste</option>
                        <option value="OTHER" <?= $type === 'OTHER' ? 'selected' : '' ?>>Other Waste</option>
                    </select>
                </div>

                <!-- Payment Status -->
                <div class="col-lg-2 col-md-4">
                    <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Payment Statuses --</option>
                        <option value="PAID" <?= $paymentStatus === 'PAID' ? 'selected' : '' ?>>Paid</option>
                        <option value="PARTIAL" <?= $paymentStatus === 'PARTIAL' ? 'selected' : '' ?>>Partial</option>
                        <option value="PENDING" <?= $paymentStatus === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="col-lg-1 col-md-3">
                    <input type="date" name="date_from" class="form-control form-control-sm" title="From Date" value="<?= e($dateFrom) ?>" onchange="this.form.submit()">
                </div>
                <div class="col-lg-1 col-md-3">
                    <input type="date" name="date_to" class="form-control form-control-sm" title="To Date" value="<?= e($dateTo) ?>" onchange="this.form.submit()">
                </div>

                <!-- Actions -->
                <div class="col-lg-1 col-md-2 text-end">
                    <a href="<?= url('production/stitching-wastage') ?>" class="btn btn-sm btn-outline-secondary w-100" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Stitching Wastage Sales Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-receipt text-warning me-2"></i>Stitching Wastage Sales Log (<?= count($sales) ?>)
            </h6>
            <span class="text-muted small">Realized selling prices and scrap transactions</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($sales)): ?>
                <div class="text-center py-5 text-muted">
                    <div class="d-inline-flex p-3 rounded-circle bg-light mb-2">
                        <i class="bi bi-scissors fs-1 text-warning"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No Stitching Wastage Sales Recorded</h5>
                    <p class="text-muted small mb-0">No sales match your search or filter parameters. Click "Record Stitching Wastage Sale" to add a new transaction.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="stitchingWastageTable" style="font-size: 13px;">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Sale # &amp; Date</th>
                                <th>Buyer &amp; Contact</th>
                                <th>Stitching Batch &amp; Program</th>
                                <th>Waste Category &amp; Unit</th>
                                <th class="text-end">Quantity &amp; Rate</th>
                                <th class="text-end">Total Price</th>
                                <th class="text-center">Payment Status</th>
                                <th class="text-center">Bill / Proof</th>
                                <th class="text-center" style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $s): ?>
                                <tr class="stitching-wastage-row">
                                    <!-- Sale Number & Date -->
                                    <td>
                                        <span class="badge bg-dark font-monospace fs-7 px-2 py-1"><?= e($s['sale_number']) ?></span>
                                        <div class="text-muted small mt-1">
                                            <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($s['sale_date'])) ?>
                                        </div>
                                        <?php if (!empty($s['invoice_number'])): ?>
                                            <small class="text-muted d-block">Inv: <?= e($s['invoice_number']) ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Buyer Details -->
                                    <td>
                                        <strong class="text-dark d-block"><?= e($s['buyer_name']) ?></strong>
                                        <?php if (!empty($s['buyer_company'])): ?>
                                            <span class="text-muted small d-block"><i class="bi bi-building me-1"></i><?= e($s['buyer_company']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($s['buyer_phone'])): ?>
                                            <span class="text-muted small d-block"><i class="bi bi-telephone me-1"></i><?= e($s['buyer_phone']) ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Stitching Batch & Program Link -->
                                    <td>
                                        <?php if (!empty($s['stitching_batch_number'])): ?>
                                            <div class="d-flex align-items-center gap-1 mb-1">
                                                <span class="badge bg-warning-subtle text-dark border">
                                                    <i class="bi bi-gear-wide-connected me-1"></i><?= e($s['stitching_batch_number']) ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($s['stitching_batch_name'])): ?>
                                                <div class="small fw-semibold text-dark"><?= e($s['stitching_batch_name']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($s['program_number'])): ?>
                                                <small class="text-primary d-block">
                                                    <i class="bi bi-diagram-3 me-1"></i><?= e($s['program_number']) ?><?= !empty($s['program_name']) ? ' (' . e($s['program_name']) . ')' : '' ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border">Direct / General Scrap</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Waste Type & Unit -->
                                    <td>
                                        <?php
                                        $typeBadge = match($s['waste_type']) {
                                            'THREAD_WASTE'         => 'bg-warning-subtle text-dark border-warning-subtle',
                                            'SEAM_TRIMMINGS'       => 'bg-info-subtle text-info border-info-subtle',
                                            'REJECTED_PANEL_SCRAP' => 'bg-danger-subtle text-danger border-danger-subtle',
                                            'STITCHING_LINE_WASTE' => 'bg-primary-subtle text-primary border-primary-subtle',
                                            default                => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                        };
                                        $typeLabel = str_replace('_', ' ', $s['waste_type']);
                                        ?>
                                        <span class="badge <?= $typeBadge ?> border fw-bold mb-1">
                                            <?= e($typeLabel) ?>
                                        </span>
                                        <div class="small text-muted">
                                            <span class="badge bg-light text-secondary border"><?= e($s['unit_type']) ?></span>
                                        </div>
                                    </td>

                                    <!-- Quantity & Rate -->
                                    <td class="text-end">
                                        <div class="fw-bold text-dark fs-7"><?= number_format($s['quantity'], 2) ?> <?= e($s['unit_type']) ?></div>
                                        <small class="text-muted">@ ₹<?= number_format($s['rate_per_unit'], 2) ?> / <?= e($s['unit_type']) ?></small>
                                    </td>

                                    <!-- Total Selling Amount -->
                                    <td class="text-end">
                                        <div class="fw-bold text-success fs-6">₹ <?= number_format($s['total_amount'], 2) ?></div>
                                        <?php if ($s['paid_amount'] > 0 && $s['paid_amount'] < $s['total_amount']): ?>
                                            <small class="text-muted d-block">Paid: ₹<?= number_format($s['paid_amount'], 2) ?></small>
                                            <small class="text-danger fw-semibold d-block">Due: ₹<?= number_format($s['total_amount'] - $s['paid_amount'], 2) ?></small>
                                        <?php elseif ($s['paid_amount'] >= $s['total_amount']): ?>
                                            <small class="text-success fw-semibold"><i class="bi bi-check-all me-1"></i>Full Paid</small>
                                        <?php else: ?>
                                            <small class="text-warning fw-semibold">Unpaid</small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Payment Status -->
                                    <td class="text-center">
                                        <?php
                                        $payBadge = match($s['payment_status']) {
                                            'PAID'    => 'bg-success text-white',
                                            'PARTIAL' => 'bg-warning text-dark',
                                            default   => 'bg-danger text-white'
                                        };
                                        ?>
                                        <span class="badge <?= $payBadge ?> px-2 py-1"><?= e($s['payment_status']) ?></span>
                                        <div class="small text-muted mt-1"><?= e($s['payment_method']) ?></div>
                                    </td>

                                    <!-- Proof Photo -->
                                    <td class="text-center">
                                        <?php if (!empty($s['photo_url'])): ?>
                                            <a href="<?= uploadUrl($s['photo_url']) ?>" target="_blank" title="View sale bill or evidence photo">
                                                <img src="<?= uploadUrl($s['photo_url']) ?>" alt="Proof" class="rounded border shadow-sm" style="width: 36px; height: 36px; object-fit: cover;">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info" title="View stitching wastage sale details" onclick="openStitchingWastageReceipt(<?= (int)$s['id'] ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <!-- Print Receipt Button -->
                                            <button type="button" class="btn btn-outline-secondary" title="View & Print Sale Voucher" onclick='openStitchingWastageReceipt(<?= (int)$s['id'] ?>)'>
                                                <i class="bi bi-printer"></i>
                                            </button>

                                            <?php if (hasPermission('stitching_wastage', 'edit')): ?>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-outline-primary" title="Edit Sale Details" onclick='openEditStitchingWastageModal(<?= json_encode($s) ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php endif; ?>

                                            <?php if (hasPermission('stitching_wastage', 'delete')): ?>
                                            <!-- Delete Button -->
                                            <form method="POST" action="<?= url('production/stitching-wastage/delete/' . $s['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Stitching Wastage Sale #<?= e(addslashes($s['sale_number'])) ?>?');">
                                                <?= CSRF::field() ?>
                                                <button type="submit" class="btn btn-outline-danger" title="Delete Sale Record">
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
                <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="stitchingWastagePaginationFooter">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="small text-muted fw-medium" id="stitchingWastagePageInfo">
                            Showing 1 to 10 of <?= count($sales) ?> sales
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-muted mb-0 fw-medium">Show:</label>
                            <select id="stitchingWastagePageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                                <option value="10" selected>10 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                    </div>
                    <nav aria-label="Stitching Wastage Pagination">
                        <ul class="pagination pagination-modern mb-0" id="stitchingWastagePaginationList">
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. RECORD STITCHING WASTAGE SALE MODAL                                    -->
<!-- ========================================================================= -->
<div class="modal fade" id="recordStitchingWastageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-warning">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="bi bi-plus-circle me-2"></i>Record Stitching Scrap &amp; Wastage Sale
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('production/stitching-wastage/store') ?>" enctype="multipart/form-data" id="recordStitchingWastageForm">
                <?= CSRF::field() ?>
                <input type="hidden" name="program_id" id="newStwProgramId" value="">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- 1. Primary Dropdown: Stitching Module Batch / Program Selection -->
                        <div class="col-12">
                            <div class="p-3 bg-light rounded border border-warning-subtle shadow-sm">
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                    <span>
                                        <i class="bi bi-gear-wide-connected text-warning me-1"></i>Select Stitching Module Batch / Program <span class="text-danger">*</span>
                                    </span>
                                    <span class="badge bg-warning-subtle text-dark border">Stitching Program Dropdown</span>
                                </label>
                                <?php 
                                    $availableBatches = array_filter($stitchingBatches, fn($sb) => (float)($sb['sold_quantity'] ?? 0) <= 0 && (int)($sb['sales_count'] ?? 0) <= 0);
                                    $soldBatches = array_filter($stitchingBatches, fn($sb) => (float)($sb['sold_quantity'] ?? 0) > 0 || (int)($sb['sales_count'] ?? 0) > 0);
                                ?>
                                <select name="stitching_batch_id" id="newStwStitchingBatchId" class="form-select form-select-md fw-semibold border-warning" onchange="onNewStitchingBatchChange(this)" required>
                                    <option value="" data-program-id="0" data-batch-no="" data-program-no="" data-recd-qty="0" data-stitched-qty="0" data-total-weight="0" data-sold-qty="0" data-is-sold="0">-- Select Stitching Module Batch / Program * --</option>
                                    
                                    <?php if (!empty($availableBatches)): ?>
                                        <optgroup label="✓ Available Stitching Batches (Ready to Sell Scrap)">
                                            <?php foreach ($availableBatches as $sb): ?>
                                                <option value="<?= $sb['id'] ?>"
                                                    data-batch-no="<?= e($sb['batch_number']) ?>"
                                                    data-batch-name="<?= e($sb['name'] ?? '') ?>"
                                                    data-program-id="<?= (int)($sb['program_id'] ?? 0) ?>"
                                                    data-program-no="<?= e($sb['program_number'] ?? '') ?>"
                                                    data-program-name="<?= e($sb['program_name'] ?? '') ?>"
                                                    data-cutting-batch="<?= e($sb['cutting_batch_number'] ?? '') ?>"
                                                    data-recd-qty="<?= (int)($sb['received_qty'] ?? 0) ?>"
                                                    data-stitched-qty="<?= (int)($sb['stitched_qty'] ?? 0) ?>"
                                                    data-total-weight="<?= (float)($sb['total_weight'] ?? 0) ?>"
                                                    data-sold-qty="0.00"
                                                    data-is-sold="0">
                                                    [<?= e($sb['batch_number']) ?>] <?= e($sb['name'] ?: 'Stitching Batch') ?><?= !empty($sb['program_number']) ? ' • Prog: ' . e($sb['program_number']) . ' (' . e($sb['program_name']) . ')' : '' ?> • Stitched: <?= number_format($sb['stitched_qty'] ?? 0) ?> pcs • Ready to Sell
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php else: ?>
                                        <option value="" disabled class="text-danger fw-bold">✗ No stitching batches with unsold wastage currently available</option>
                                    <?php endif; ?>

                                    <?php if (!empty($soldBatches)): ?>
                                        <optgroup label="✗ Fully Sold Batches (Unavailable)">
                                            <?php foreach ($soldBatches as $sb): ?>
                                                <option value="<?= $sb['id'] ?>" disabled class="text-muted bg-light"
                                                    data-batch-no="<?= e($sb['batch_number']) ?>"
                                                    data-batch-name="<?= e($sb['name'] ?? '') ?>"
                                                    data-program-id="<?= (int)($sb['program_id'] ?? 0) ?>"
                                                    data-program-no="<?= e($sb['program_number'] ?? '') ?>"
                                                    data-program-name="<?= e($sb['program_name'] ?? '') ?>"
                                                    data-cutting-batch="<?= e($sb['cutting_batch_number'] ?? '') ?>"
                                                    data-recd-qty="<?= (int)($sb['received_qty'] ?? 0) ?>"
                                                    data-stitched-qty="<?= (int)($sb['stitched_qty'] ?? 0) ?>"
                                                    data-total-weight="<?= (float)($sb['total_weight'] ?? 0) ?>"
                                                    data-sold-qty="<?= (float)($sb['sold_quantity'] ?? 0) ?>"
                                                    data-is-sold="1">
                                                    [<?= e($sb['batch_number']) ?>] <?= e($sb['name'] ?: 'Stitching Batch') ?><?= !empty($sb['program_number']) ? ' • Prog: ' . e($sb['program_number']) . ' (' . e($sb['program_name']) . ')' : '' ?> • Stitched: <?= number_format($sb['stitched_qty'] ?? 0) ?> pcs [ALREADY SOLD - <?= number_format($sb['sold_quantity'], 2) ?> <?= e($sb['latest_unit_type'] ?? 'kg') ?>]
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>

                                <!-- Live Stitching Batch & Program Info Card -->
                                <div id="newStwBatchInfoCard" class="mt-2 p-2 bg-white rounded border small d-none">
                                    <div class="d-flex justify-content-between flex-wrap gap-2">
                                        <div>
                                            <span class="fw-bold text-dark" id="newStwInfoBatchName"></span>
                                            <span class="text-primary ms-2 fw-semibold" id="newStwInfoProg"></span>
                                        </div>
                                        <div>
                                            <span class="text-muted">Cutting Ref: </span><strong id="newStwInfoCutting" class="text-dark"></strong>
                                        </div>
                                    </div>
                                    <div class="mt-1 d-flex justify-content-between align-items-center flex-wrap gap-2 text-muted">
                                        <span>Received: <strong class="text-dark" id="newStwInfoRecd">0 pcs</strong></span>
                                        <span>Stitched: <strong class="text-success" id="newStwInfoStitched">0 pcs</strong></span>
                                        <span>Batch Weight: <strong class="text-dark" id="newStwInfoWeight">0.00 kg</strong></span>
                                        <span>Past Wastage Sold: <strong id="newStwInfoSold">0.00</strong></span>
                                    </div>
                                    <div id="newStwSoldAlert" class="alert alert-danger py-1 px-2 mt-2 mb-0 fw-bold small d-none">
                                        <i class="bi bi-x-circle me-1"></i> [ALREADY SOLD] This Stitching Batch scrap has already been sold. Duplicate selling is not allowed.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Buyer Name (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Scrap Buyer / Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="buyer_name" id="newStwBuyerName" class="form-control" placeholder="e.g. Ramesh Thread Waste Traders" required>
                        </div>

                        <!-- Buyer Phone (Required, 10 digits) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Buyer Phone / Mobile <span class="text-danger">*</span> <small class="text-muted fw-normal">(10 digits)</small></label>
                            <input type="tel" name="buyer_phone" id="newStwBuyerPhone" class="form-control" placeholder="10-digit mobile (e.g. 9876543210)" pattern="[0-9]{10}" maxlength="10" minlength="10" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" required>
                        </div>

                        <!-- Buyer Company (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Buyer Company / Business Name <span class="text-danger">*</span></label>
                            <input type="text" name="buyer_company" id="newStwBuyerCompany" class="form-control" placeholder="e.g. Tirupur Recycled Cotton Co." required>
                        </div>

                        <!-- Waste Category (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Stitching Waste Category <span class="text-danger">*</span></label>
                            <select name="waste_type" id="newStwWasteType" class="form-select" required>
                                <option value="">-- Select Stitching Waste Category * --</option>
                                <option value="THREAD_WASTE" selected>Thread Waste (Spool &amp; sewing end threads)</option>
                                <option value="SEAM_TRIMMINGS">Seam &amp; Overlock Edge Trimmings</option>
                                <option value="REJECTED_PANEL_SCRAP">Rejected Panel / Cut Bits Scrap</option>
                                <option value="STITCHING_LINE_WASTE">Stitching Floor / Line Waste</option>
                                <option value="OTHER">Other Sewing Waste</option>
                            </select>
                        </div>

                        <!-- Selling Price Maintenance Box (Manual Entry & Two-Way Auto-Calculation) -->
                        <div class="col-12">
                            <div class="p-3 bg-light rounded border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted fw-bold text-uppercase">
                                        <i class="bi bi-calculator me-1 text-warning"></i>Selling Price &amp; Quantity (Manual Entry) <span class="text-danger">*</span>
                                    </span>
                                    <span class="badge bg-white text-secondary border small">Type Rate or Total Price directly</span>
                                </div>
                                <div class="row g-3">
                                    <!-- Unit Type -->
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-dark">Unit Type <span class="text-danger">*</span></label>
                                        <select name="unit_type" id="newStwUnitType" class="form-select fw-semibold" onchange="updateNewStwUnitLabels()" required>
                                            <option value="KG">KG (Kilograms)</option>
                                            <option value="BAGS">Bags</option>
                                            <option value="BUNDLES">Bundles</option>
                                            <option value="PCS">Pcs (Pieces)</option>
                                        </select>
                                    </div>

                                    <!-- Quantity -->
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-dark">Quantity <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0.01" name="quantity" id="newStwQuantity" class="form-control fw-bold fs-6" placeholder="0.00" required oninput="onNewStwQuantityChange()">
                                            <span class="input-group-text bg-white" id="newStwUnitLabel">kg</span>
                                        </div>
                                    </div>

                                    <!-- Rate per Unit (Manual Entry) -->
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-dark">Selling Rate (<span id="newStwRateLabel">₹ / kg</span>) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white fw-bold">₹</span>
                                            <input type="number" step="0.01" min="0.01" name="rate_per_unit" id="newStwRatePerUnit" class="form-control fw-bold text-success fs-6" placeholder="0.00" required oninput="onNewStwRateChange()">
                                        </div>
                                    </div>

                                    <!-- Total Selling Amount (Editable Manual Entry) -->
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-success">Total Sale Price (₹) <span class="text-danger">*</span> <span class="badge bg-success-subtle text-success border ms-1">Editable</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-success-subtle text-success fw-bold">₹</span>
                                            <input type="number" step="0.01" min="0.01" name="total_amount" id="newStwTotalAmount" class="form-control fw-bold text-success fs-6" placeholder="0.00" required oninput="onNewStwTotalChange()">
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2" id="newStwCalcPreview">Calculated: 0.00 KG × ₹0.00 = ₹0.00</small>
                            </div>
                        </div>

                        <!-- Payment & Sale Date -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Amount Received (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">₹</span>
                                <input type="number" step="0.01" min="0" name="paid_amount" id="newStwPaidAmount" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Sale Date <span class="text-danger">*</span></label>
                            <input type="date" name="sale_date" id="newStwSaleDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark px-4 fw-bold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Save Stitching Wastage Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. EDIT STITCHING WASTAGE SALE MODAL                                      -->
<!-- ========================================================================= -->
<div class="modal fade" id="editStitchingWastageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-primary">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="bi bi-pencil-square me-2"></i>Edit Stitching Wastage Sale Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editStitchingWastageForm" enctype="multipart/form-data">
                <?= CSRF::field() ?>
                <input type="hidden" name="program_id" id="editStwProgramId" value="">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Stitching Module Batch / Program Selection in Edit -->
                        <div class="col-12">
                            <div class="p-3 bg-light rounded border border-primary-subtle shadow-sm">
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                    <span>
                                        <i class="bi bi-gear-wide-connected text-primary me-1"></i>Select Stitching Module Batch / Program <span class="text-danger">*</span>
                                    </span>
                                    <span class="badge bg-primary-subtle text-primary border">Stitching Program Dropdown</span>
                                </label>
                                <select name="stitching_batch_id" id="editStwStitchingBatchId" class="form-select form-select-md fw-semibold" onchange="onEditStitchingBatchChange(this)" required>
                                    <option value="" data-program-id="0" data-batch-no="" data-program-no="" data-recd-qty="0" data-stitched-qty="0" data-total-weight="0" data-sold-qty="0">-- Select Stitching Module Batch / Program * --</option>
                                    <?php foreach ($stitchingBatches as $sb): 
                                        $isSold = (float)($sb['sold_quantity'] ?? 0) > 0 || (int)($sb['sales_count'] ?? 0) > 0;
                                    ?>
                                        <option value="<?= $sb['id'] ?>"
                                            data-batch-no="<?= e($sb['batch_number']) ?>"
                                            data-batch-name="<?= e($sb['name'] ?? '') ?>"
                                            data-program-id="<?= (int)($sb['program_id'] ?? 0) ?>"
                                            data-program-no="<?= e($sb['program_number'] ?? '') ?>"
                                            data-program-name="<?= e($sb['program_name'] ?? '') ?>"
                                            data-cutting-batch="<?= e($sb['cutting_batch_number'] ?? '') ?>"
                                            data-recd-qty="<?= (int)($sb['received_qty'] ?? 0) ?>"
                                            data-stitched-qty="<?= (int)($sb['stitched_qty'] ?? 0) ?>"
                                            data-total-weight="<?= (float)($sb['total_weight'] ?? 0) ?>"
                                            data-sold-qty="<?= (float)($sb['sold_quantity'] ?? 0) ?>"
                                            data-is-sold="<?= $isSold ? '1' : '0' ?>"
                                            class="<?= $isSold ? 'text-muted' : '' ?>">
                                            [<?= e($sb['batch_number']) ?>] <?= e($sb['name'] ?: 'Stitching Batch') ?><?= !empty($sb['program_number']) ? ' • Prog: ' . e($sb['program_number']) . ' (' . e($sb['program_name']) . ')' : '' ?> • Stitched: <?= number_format($sb['stitched_qty'] ?? 0) ?> pcs <?= $isSold ? '[ALREADY SOLD - ' . number_format($sb['sold_quantity'], 2) . ' ' . e($sb['latest_unit_type'] ?? 'kg') . ']' : '• Ready to Sell' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <!-- Live Stitching Batch & Program Info Card in Edit -->
                                <div id="editStwBatchInfoCard" class="mt-2 p-2 bg-white rounded border small d-none">
                                    <div class="d-flex justify-content-between flex-wrap gap-2">
                                        <div>
                                            <span class="fw-bold text-dark" id="editStwInfoBatchName"></span>
                                            <span class="text-primary ms-2 fw-semibold" id="editStwInfoProg"></span>
                                        </div>
                                        <div>
                                            <span class="text-muted">Cutting Ref: </span><strong id="editStwInfoCutting" class="text-dark"></strong>
                                        </div>
                                    </div>
                                    <div class="mt-1 d-flex justify-content-between align-items-center flex-wrap gap-2 text-muted">
                                        <span>Received: <strong class="text-dark" id="editStwInfoRecd">0 pcs</strong></span>
                                        <span>Stitched: <strong class="text-success" id="editStwInfoStitched">0 pcs</strong></span>
                                        <span>Batch Weight: <strong class="text-dark" id="editStwInfoWeight">0.00 kg</strong></span>
                                        <span>Past Wastage Sold: <strong class="text-warning text-dark fw-bold" id="editStwInfoSold">0.00</strong></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Buyer Name (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Scrap Buyer / Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="buyer_name" id="editStwBuyerName" class="form-control" required>
                        </div>

                        <!-- Buyer Phone (Required, 10 digits) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Buyer Phone / Mobile <span class="text-danger">*</span> <small class="text-muted fw-normal">(10 digits)</small></label>
                            <input type="tel" name="buyer_phone" id="editStwBuyerPhone" class="form-control" placeholder="10-digit mobile (e.g. 9876543210)" pattern="[0-9]{10}" maxlength="10" minlength="10" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" required>
                        </div>

                        <!-- Buyer Company (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Buyer Company / Business Name <span class="text-danger">*</span></label>
                            <input type="text" name="buyer_company" id="editStwBuyerCompany" class="form-control" required>
                        </div>

                        <!-- Waste Category (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Stitching Waste Category <span class="text-danger">*</span></label>
                            <select name="waste_type" id="editStwWasteType" class="form-select" required>
                                <option value="THREAD_WASTE">Thread Waste</option>
                                <option value="SEAM_TRIMMINGS">Seam &amp; Overlock Trimmings</option>
                                <option value="REJECTED_PANEL_SCRAP">Rejected Panel Scrap</option>
                                <option value="STITCHING_LINE_WASTE">Stitching Floor Waste</option>
                                <option value="OTHER">Other Sewing Waste</option>
                            </select>
                        </div>

                        <!-- Price Calculation Box (Manual Entry) -->
                        <div class="col-12">
                            <div class="p-3 bg-light rounded border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted fw-bold text-uppercase">
                                        <i class="bi bi-calculator me-1 text-primary"></i>Selling Price &amp; Quantity (Manual Entry) <span class="text-danger">*</span>
                                    </span>
                                    <span class="badge bg-white text-secondary border small">Type Rate or Total Price directly</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-dark">Unit Type <span class="text-danger">*</span></label>
                                        <select name="unit_type" id="editStwUnitType" class="form-select fw-semibold" onchange="updateEditStwUnitLabels()" required>
                                            <option value="KG">KG (Kilograms)</option>
                                            <option value="BAGS">Bags</option>
                                            <option value="BUNDLES">Bundles</option>
                                            <option value="PCS">Pcs (Pieces)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-dark">Quantity <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0.01" name="quantity" id="editStwQuantity" class="form-control fw-bold fs-6" required oninput="onEditStwQuantityChange()">
                                            <span class="input-group-text bg-white" id="editStwUnitLabel">kg</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-dark">Selling Rate (<span id="editStwRateLabel">₹ / kg</span>) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white fw-bold">₹</span>
                                            <input type="number" step="0.01" min="0.01" name="rate_per_unit" id="editStwRatePerUnit" class="form-control fw-bold text-success fs-6" required oninput="onEditStwRateChange()">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold text-success">Total Sale Price (₹) <span class="text-danger">*</span> <span class="badge bg-success-subtle text-success border ms-1">Editable</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-success-subtle text-success fw-bold">₹</span>
                                            <input type="number" step="0.01" min="0.01" name="total_amount" id="editStwTotalAmount" class="form-control fw-bold text-success fs-6" required oninput="onEditStwTotalChange()">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Amount Received (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="paid_amount" id="editStwPaidAmount" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Sale Date <span class="text-danger">*</span></label>
                            <input type="date" name="sale_date" id="editStwSaleDate" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                        <i class="bi bi-save me-1"></i> Update Sale Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. PRINTABLE STITCHING RECEIPT VOUCHER MODAL                              -->
<!-- ========================================================================= -->
<div class="modal fade" id="stitchingWastageReceiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-receipt me-1"></i>Stitching Wastage Sale Voucher
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="printableStwReceiptArea">
                <div class="text-center mb-3">
                    <h5 class="fw-bold mb-0 text-dark">GRM APPARELS</h5>
                    <small class="text-muted d-block">Stitching Scrap &amp; Waste Sales Voucher</small>
                    <span class="badge bg-light text-dark border mt-1" id="recStwSaleNo">STW-2026-0001</span>
                </div>
                <hr class="my-2">
                <div class="row g-1 small mb-3">
                    <div class="col-6"><strong>Date:</strong> <span id="recStwDate">-</span></div>
                    <div class="col-6 text-end"><strong>Status:</strong> <span id="recStwStatus">-</span></div>
                    <div class="col-12"><strong>Buyer:</strong> <span id="recStwBuyer">-</span></div>
                    <div class="col-12" id="recStwCompanyRow"><strong>Company:</strong> <span id="recStwCompany">-</span></div>
                    <div class="col-12" id="recStwPhoneRow"><strong>Phone:</strong> <span id="recStwPhone">-</span></div>
                    <div class="col-12" id="recStwBatchRow"><strong>Stitching Batch:</strong> <span id="recStwBatch">-</span></div>
                    <div class="col-12" id="recStwProgramRow"><strong>Production Program:</strong> <span id="recStwProgram">-</span></div>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item / Type</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id="recStwType">Thread Waste</td>
                                <td class="text-end" id="recStwQty">0.00 KG</td>
                                <td class="text-end" id="recStwRate">₹0.00</td>
                                <td class="text-end fw-bold" id="recStwTotal">₹0.00</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total Payable:</th>
                                <th class="text-end text-success" id="recStwTotalFooter">₹0.00</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Amount Paid:</th>
                                <th class="text-end" id="recStwPaid">₹0.00</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Balance Due:</th>
                                <th class="text-end text-danger" id="recStwDue">₹0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="small text-muted mb-3" id="recStwNotes"></div>
                <div class="d-flex justify-content-between text-muted small pt-3 border-top">
                    <div>Receiver's Signature</div>
                    <div>Authorized Signatory</div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-dark btn-sm px-3" onclick="printStwReceiptArea()">
                    <i class="bi bi-printer me-1"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function onNewStitchingBatchChange(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const infoCard = document.getElementById('newStwBatchInfoCard');
    const programIdInput = document.getElementById('newStwProgramId');

    if (!opt || !selectEl.value) {
        infoCard.classList.add('d-none');
        programIdInput.value = '';
        return;
    }

    const batchNo = opt.getAttribute('data-batch-no') || '';
    const batchName = opt.getAttribute('data-batch-name') || '';
    const progId = opt.getAttribute('data-program-id') || '';
    const progNo = opt.getAttribute('data-program-no') || '';
    const progName = opt.getAttribute('data-program-name') || '';
    const cuttingBatch = opt.getAttribute('data-cutting-batch') || 'None';
    const recdQty = opt.getAttribute('data-recd-qty') || '0';
    const stitchedQty = opt.getAttribute('data-stitched-qty') || '0';
    const totalWeight = parseFloat(opt.getAttribute('data-total-weight') || 0).toFixed(2);
    const soldQty = parseFloat(opt.getAttribute('data-sold-qty') || 0).toFixed(2);
    const isSold = opt.getAttribute('data-is-sold') === '1' || parseFloat(soldQty) > 0;

    programIdInput.value = progId;

    document.getElementById('newStwInfoBatchName').textContent = `Batch #${batchNo}` + (batchName ? ` (${batchName})` : '');
    document.getElementById('newStwInfoProg').textContent = progNo ? `• Program: ${progNo}` + (progName ? ` (${progName})` : '') : '• No Program';
    document.getElementById('newStwInfoCutting').textContent = cuttingBatch || 'None';
    document.getElementById('newStwInfoRecd').textContent = `${recdQty} pcs`;
    document.getElementById('newStwInfoStitched').textContent = `${stitchedQty} pcs`;
    document.getElementById('newStwInfoWeight').textContent = `${totalWeight} kg`;

    const soldEl = document.getElementById('newStwInfoSold');
    const soldAlert = document.getElementById('newStwSoldAlert');
    if (isSold) {
        if (soldEl) {
            soldEl.className = 'text-danger fw-bold';
            soldEl.textContent = `${soldQty} units/kg [ALREADY SOLD]`;
        }
        if (soldAlert) soldAlert.classList.remove('d-none');
    } else {
        if (soldEl) {
            soldEl.className = 'text-success fw-bold';
            soldEl.textContent = `0.00 (Ready to Sell Scrap)`;
        }
        if (soldAlert) soldAlert.classList.add('d-none');
    }

    infoCard.classList.remove('d-none');
}

function onEditStitchingBatchChange(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const infoCard = document.getElementById('editStwBatchInfoCard');
    const programIdInput = document.getElementById('editStwProgramId');

    if (!opt || !selectEl.value) {
        infoCard.classList.add('d-none');
        programIdInput.value = '';
        return;
    }

    const batchNo = opt.getAttribute('data-batch-no') || '';
    const batchName = opt.getAttribute('data-batch-name') || '';
    const progId = opt.getAttribute('data-program-id') || '';
    const progNo = opt.getAttribute('data-program-no') || '';
    const progName = opt.getAttribute('data-program-name') || '';
    const cuttingBatch = opt.getAttribute('data-cutting-batch') || 'None';
    const recdQty = opt.getAttribute('data-recd-qty') || '0';
    const stitchedQty = opt.getAttribute('data-stitched-qty') || '0';
    const totalWeight = parseFloat(opt.getAttribute('data-total-weight') || 0).toFixed(2);
    const soldQty = parseFloat(opt.getAttribute('data-sold-qty') || 0).toFixed(2);

    programIdInput.value = progId;

    document.getElementById('editStwInfoBatchName').textContent = `Batch #${batchNo}` + (batchName ? ` (${batchName})` : '');
    document.getElementById('editStwInfoProg').textContent = progNo ? `• Program: ${progNo}` + (progName ? ` (${progName})` : '') : '• No Program';
    document.getElementById('editStwInfoCutting').textContent = cuttingBatch || 'None';
    document.getElementById('editStwInfoRecd').textContent = `${recdQty} pcs`;
    document.getElementById('editStwInfoStitched').textContent = `${stitchedQty} pcs`;
    document.getElementById('editStwInfoWeight').textContent = `${totalWeight} kg`;
    document.getElementById('editStwInfoSold').textContent = `${soldQty} units/kg`;

    infoCard.classList.remove('d-none');
}

function updateNewStwUnitLabels() {
    const unit = document.getElementById('newStwUnitType').value;
    document.getElementById('newStwUnitLabel').textContent = unit.toLowerCase();
    document.getElementById('newStwRateLabel').textContent = `₹ / ${unit.toLowerCase()}`;
    onNewStwQuantityChange();
}

function onNewStwQuantityChange() {
    const q = parseFloat(document.getElementById('newStwQuantity').value) || 0;
    const r = parseFloat(document.getElementById('newStwRatePerUnit').value) || 0;
    const t = parseFloat(document.getElementById('newStwTotalAmount').value) || 0;
    const unit = document.getElementById('newStwUnitType').value;

    if (r > 0) {
        const total = q * r;
        document.getElementById('newStwTotalAmount').value = total > 0 ? total.toFixed(2) : '';
        document.getElementById('newStwCalcPreview').textContent = `Calculated: ${q.toFixed(2)} ${unit} × ₹${r.toFixed(2)}/${unit} = ₹${total.toFixed(2)}`;
    } else if (t > 0 && q > 0) {
        const rate = t / q;
        document.getElementById('newStwRatePerUnit').value = rate.toFixed(2);
        document.getElementById('newStwCalcPreview').textContent = `Calculated: ${q.toFixed(2)} ${unit} × ₹${rate.toFixed(2)}/${unit} = ₹${t.toFixed(2)}`;
    } else {
        document.getElementById('newStwCalcPreview').textContent = `Calculated: ${q.toFixed(2)} ${unit} × ₹${r.toFixed(2)}/${unit} = ₹0.00`;
    }
    syncNewStwPaidAmount();
}

function onNewStwRateChange() {
    const q = parseFloat(document.getElementById('newStwQuantity').value) || 0;
    const r = parseFloat(document.getElementById('newStwRatePerUnit').value) || 0;
    const unit = document.getElementById('newStwUnitType').value;
    const total = q * r;
    document.getElementById('newStwTotalAmount').value = total > 0 ? total.toFixed(2) : '';
    document.getElementById('newStwCalcPreview').textContent = `Calculated: ${q.toFixed(2)} ${unit} × ₹${r.toFixed(2)}/${unit} = ₹${total.toFixed(2)}`;
    syncNewStwPaidAmount();
}

function onNewStwTotalChange() {
    const q = parseFloat(document.getElementById('newStwQuantity').value) || 0;
    const t = parseFloat(document.getElementById('newStwTotalAmount').value) || 0;
    const unit = document.getElementById('newStwUnitType').value;
    if (q > 0 && t > 0) {
        const rate = t / q;
        document.getElementById('newStwRatePerUnit').value = rate.toFixed(2);
        document.getElementById('newStwCalcPreview').textContent = `Calculated: ${q.toFixed(2)} ${unit} × ₹${rate.toFixed(2)}/${unit} = ₹${t.toFixed(2)}`;
    } else {
        document.getElementById('newStwCalcPreview').textContent = `Total Price Entered: ₹${t.toFixed(2)}`;
    }
    syncNewStwPaidAmount();
}

function syncNewStwPaidAmount() {
    autoCheckNewStwPaymentStatus();
}

function autoCheckNewStwPaymentStatus() {
    const statusSelect = document.getElementById('newStwPaymentStatus');
    if (!statusSelect) return;
    const total = parseFloat(document.getElementById('newStwTotalAmount').value) || 0;
    const paid = parseFloat(document.getElementById('newStwPaidAmount').value) || 0;

    if (total > 0 && paid >= total) {
        statusSelect.value = 'PAID';
    } else if (paid > 0 && paid < total) {
        statusSelect.value = 'PARTIAL';
    } else {
        statusSelect.value = 'PENDING';
    }
}

function updateEditStwUnitLabels() {
    const unit = document.getElementById('editStwUnitType').value;
    document.getElementById('editStwUnitLabel').textContent = unit.toLowerCase();
    document.getElementById('editStwRateLabel').textContent = `₹ / ${unit.toLowerCase()}`;
    onEditStwQuantityChange();
}

function onEditStwQuantityChange() {
    const q = parseFloat(document.getElementById('editStwQuantity').value) || 0;
    const r = parseFloat(document.getElementById('editStwRatePerUnit').value) || 0;
    const t = parseFloat(document.getElementById('editStwTotalAmount').value) || 0;

    if (r > 0) {
        const total = q * r;
        document.getElementById('editStwTotalAmount').value = total > 0 ? total.toFixed(2) : '';
    } else if (t > 0 && q > 0) {
        const rate = t / q;
        document.getElementById('editStwRatePerUnit').value = rate.toFixed(2);
    }
}

function onEditStwRateChange() {
    const q = parseFloat(document.getElementById('editStwQuantity').value) || 0;
    const r = parseFloat(document.getElementById('editStwRatePerUnit').value) || 0;
    const total = q * r;
    document.getElementById('editStwTotalAmount').value = total > 0 ? total.toFixed(2) : '';
}

function onEditStwTotalChange() {
    const q = parseFloat(document.getElementById('editStwQuantity').value) || 0;
    const t = parseFloat(document.getElementById('editStwTotalAmount').value) || 0;
    if (q > 0 && t > 0) {
        const rate = t / q;
        document.getElementById('editStwRatePerUnit').value = rate.toFixed(2);
    }
}

function openEditStitchingWastageModal(data) {
    document.getElementById('editStitchingWastageForm').action = '<?= url('production/stitching-wastage/update') ?>/' + data.id;
    document.getElementById('editStwBuyerName').value = data.buyer_name || '';
    document.getElementById('editStwBuyerPhone').value = data.buyer_phone || '';
    document.getElementById('editStwBuyerCompany').value = data.buyer_company || '';
    document.getElementById('editStwWasteType').value = data.waste_type || 'THREAD_WASTE';
    document.getElementById('editStwUnitType').value = data.unit_type || 'KG';
    document.getElementById('editStwQuantity').value = parseFloat(data.quantity).toFixed(2);
    document.getElementById('editStwRatePerUnit').value = parseFloat(data.rate_per_unit).toFixed(2);
    document.getElementById('editStwTotalAmount').value = parseFloat(data.total_amount).toFixed(2);
    document.getElementById('editStwPaidAmount').value = parseFloat(data.paid_amount || 0).toFixed(2);
    document.getElementById('editStwSaleDate').value = data.sale_date || '';
    
    const batchSelect = document.getElementById('editStwStitchingBatchId');
    const currentBatchId = data.stitching_batch_id || '';
    Array.from(batchSelect.options).forEach(opt => {
        if (!opt.value) return;
        const optIsSold = opt.getAttribute('data-is-sold') === '1';
        if (opt.value == currentBatchId) {
            opt.disabled = false;
        } else if (optIsSold) {
            opt.disabled = true;
        }
    });
    batchSelect.value = currentBatchId;
    onEditStitchingBatchChange(batchSelect);

    updateEditStwUnitLabels();

    const modal = new bootstrap.Modal(document.getElementById('editStitchingWastageModal'));
    modal.show();
}

function openStitchingWastageReceipt(id) {
    fetch('<?= url('production/stitching-wastage/receipt') ?>/' + id)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.sale) {
                const s = res.sale;
                document.getElementById('recStwSaleNo').textContent = s.sale_number;
                document.getElementById('recStwDate').textContent = s.sale_date;
                document.getElementById('recStwStatus').textContent = s.payment_status;
                document.getElementById('recStwBuyer').textContent = s.buyer_name;
                
                if (s.buyer_company) {
                    document.getElementById('recStwCompanyRow').classList.remove('d-none');
                    document.getElementById('recStwCompany').textContent = s.buyer_company;
                } else {
                    document.getElementById('recStwCompanyRow').classList.add('d-none');
                }

                if (s.buyer_phone) {
                    document.getElementById('recStwPhoneRow').classList.remove('d-none');
                    document.getElementById('recStwPhone').textContent = s.buyer_phone;
                } else {
                    document.getElementById('recStwPhoneRow').classList.add('d-none');
                }

                if (s.stitching_batch_number) {
                    document.getElementById('recStwBatchRow').classList.remove('d-none');
                    document.getElementById('recStwBatch').textContent = s.stitching_batch_number + (s.stitching_batch_name ? ' (' + s.stitching_batch_name + ')' : '');
                } else {
                    document.getElementById('recStwBatchRow').classList.add('d-none');
                }

                if (s.program_number) {
                    document.getElementById('recStwProgramRow').classList.remove('d-none');
                    document.getElementById('recStwProgram').textContent = s.program_number + (s.program_name ? ' (' + s.program_name + ')' : '');
                } else {
                    document.getElementById('recStwProgramRow').classList.add('d-none');
                }

                document.getElementById('recStwType').textContent = s.waste_type.replace(/_/g, ' ');
                document.getElementById('recStwQty').textContent = parseFloat(s.quantity).toFixed(2) + ' ' + (s.unit_type || 'KG');
                document.getElementById('recStwRate').textContent = '₹' + parseFloat(s.rate_per_unit).toFixed(2) + ' / ' + (s.unit_type || 'KG');
                document.getElementById('recStwTotal').textContent = '₹' + parseFloat(s.total_amount).toFixed(2);
                document.getElementById('recStwTotalFooter').textContent = '₹' + parseFloat(s.total_amount).toFixed(2);
                document.getElementById('recStwPaid').textContent = '₹' + parseFloat(s.paid_amount).toFixed(2);
                const due = Math.max(0, parseFloat(s.total_amount) - parseFloat(s.paid_amount));
                document.getElementById('recStwDue').textContent = '₹' + due.toFixed(2);
                document.getElementById('recStwNotes').textContent = s.notes ? ('Remarks: ' + s.notes) : '';

                const modal = new bootstrap.Modal(document.getElementById('stitchingWastageReceiptModal'));
                modal.show();
            }
        })
        .catch(err => {
            console.error('Error fetching stitching receipt data:', err);
            alert('Failed to load sale voucher.');
        });
}

function printStwReceiptArea() {
    const printContents = document.getElementById('printableStwReceiptArea').innerHTML;
    const origContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = origContents;
    window.location.reload();
}

document.addEventListener('DOMContentLoaded', () => {
    const recordForm = document.getElementById('recordStitchingWastageForm');
    if (recordForm) {
        recordForm.addEventListener('submit', function(e) {
            const batchId = document.getElementById('newStwStitchingBatchId').value;
            const buyerName = document.getElementById('newStwBuyerName').value.trim();
            const buyerPhone = document.getElementById('newStwBuyerPhone').value.trim();
            const buyerCompany = document.getElementById('newStwBuyerCompany').value.trim();
            const wasteType = document.getElementById('newStwWasteType').value;
            const qty = parseFloat(document.getElementById('newStwQuantity').value) || 0;
            const rate = parseFloat(document.getElementById('newStwRatePerUnit').value) || 0;
            const total = parseFloat(document.getElementById('newStwTotalAmount').value) || 0;
            const paid = document.getElementById('newStwPaidAmount').value.trim();
            const date = document.getElementById('newStwSaleDate').value.trim();

            if (!batchId) {
                e.preventDefault();
                alert('Select Stitching Module Batch / Program is required. Please choose a Stitching Batch.');
                document.getElementById('newStwStitchingBatchId').focus();
                return false;
            }
            const cutSelect = document.getElementById('newStwStitchingBatchId');
            const selectedOpt = cutSelect.options[cutSelect.selectedIndex];
            if (selectedOpt && (selectedOpt.disabled || selectedOpt.getAttribute('data-is-sold') === '1')) {
                e.preventDefault();
                alert('Validation Error: Stitching Wastage for this batch has already been sold. Please choose an available stitching batch.');
                cutSelect.focus();
                return false;
            }
            if (!buyerName) {
                e.preventDefault();
                alert('Scrap Buyer / Vendor Name is required.');
                document.getElementById('newStwBuyerName').focus();
                return false;
            }
            if (!buyerPhone || !/^[0-9]{10}$/.test(buyerPhone)) {
                e.preventDefault();
                alert('Buyer Phone / Mobile is required and must be exactly 10 digits.');
                document.getElementById('newStwBuyerPhone').focus();
                return false;
            }
            if (!buyerCompany) {
                e.preventDefault();
                alert('Buyer Company / Business Name is required.');
                document.getElementById('newStwBuyerCompany').focus();
                return false;
            }
            if (!wasteType) {
                e.preventDefault();
                alert('Stitching Waste Category is required.');
                document.getElementById('newStwWasteType').focus();
                return false;
            }
            if (qty <= 0) {
                e.preventDefault();
                alert('Quantity must be greater than 0.');
                document.getElementById('newStwQuantity').focus();
                return false;
            }
            if (rate <= 0 && total <= 0) {
                e.preventDefault();
                alert('Selling Price & Quantity (Manual Entry) is required. Enter Rate per unit or Total Sale Price.');
                document.getElementById('newStwRatePerUnit').focus();
                return false;
            }
            if (paid === '' || isNaN(parseFloat(paid)) || parseFloat(paid) < 0) {
                e.preventDefault();
                alert('Amount Received (₹) is required (enter 0 if unpaid).');
                document.getElementById('newStwPaidAmount').focus();
                return false;
            }
            if (!date) {
                e.preventDefault();
                alert('Sale Date is required.');
                document.getElementById('newStwSaleDate').focus();
                return false;
            }
        });
    }

    const editForm = document.getElementById('editStitchingWastageForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const batchId = document.getElementById('editStwStitchingBatchId').value;
            const buyerName = document.getElementById('editStwBuyerName').value.trim();
            const buyerPhone = document.getElementById('editStwBuyerPhone').value.trim();
            const buyerCompany = document.getElementById('editStwBuyerCompany').value.trim();
            const wasteType = document.getElementById('editStwWasteType').value;
            const qty = parseFloat(document.getElementById('editStwQuantity').value) || 0;
            const rate = parseFloat(document.getElementById('editStwRatePerUnit').value) || 0;
            const total = parseFloat(document.getElementById('editStwTotalAmount').value) || 0;
            const paid = document.getElementById('editStwPaidAmount').value.trim();
            const date = document.getElementById('editStwSaleDate').value.trim();

            if (!batchId) {
                e.preventDefault();
                alert('Select Stitching Module Batch / Program is required. Please choose a Stitching Batch.');
                document.getElementById('editStwStitchingBatchId').focus();
                return false;
            }
            const editBatchSel = document.getElementById('editStwStitchingBatchId');
            const editSelectedOpt = editBatchSel.options[editBatchSel.selectedIndex];
            if (editSelectedOpt && editSelectedOpt.disabled) {
                e.preventDefault();
                alert('Validation Error: The selected Stitching Batch scrap has already been sold under another sale.');
                editBatchSel.focus();
                return false;
            }
            if (!buyerName) {
                e.preventDefault();
                alert('Scrap Buyer / Vendor Name is required.');
                document.getElementById('editStwBuyerName').focus();
                return false;
            }
            if (!buyerPhone || !/^[0-9]{10}$/.test(buyerPhone)) {
                e.preventDefault();
                alert('Buyer Phone / Mobile is required and must be exactly 10 digits.');
                document.getElementById('editStwBuyerPhone').focus();
                return false;
            }
            if (!buyerCompany) {
                e.preventDefault();
                alert('Buyer Company / Business Name is required.');
                document.getElementById('editStwBuyerCompany').focus();
                return false;
            }
            if (!wasteType) {
                e.preventDefault();
                alert('Stitching Waste Category is required.');
                document.getElementById('editStwWasteType').focus();
                return false;
            }
            if (qty <= 0) {
                e.preventDefault();
                alert('Quantity must be greater than 0.');
                document.getElementById('editStwQuantity').focus();
                return false;
            }
            if (rate <= 0 && total <= 0) {
                e.preventDefault();
                alert('Selling Price & Quantity (Manual Entry) is required. Enter Rate per unit or Total Sale Price.');
                document.getElementById('editStwRatePerUnit').focus();
                return false;
            }
            if (paid === '' || isNaN(parseFloat(paid)) || parseFloat(paid) < 0) {
                e.preventDefault();
                alert('Amount Received (₹) is required (enter 0 if unpaid).');
                document.getElementById('editStwPaidAmount').focus();
                return false;
            }
            if (!date) {
                e.preventDefault();
                alert('Sale Date is required.');
                document.getElementById('editStwSaleDate').focus();
                return false;
            }
        });
    }

    // Initialize Stitching Wastage Table Pagination
    if (typeof window.initClientPagination === 'function' && document.getElementById('stitchingWastageTable')) {
        const stitchingWastagePagination = window.initClientPagination({
            container: '#stitchingWastageTable tbody',
            itemSelector: 'tr.stitching-wastage-row',
            footer: '#stitchingWastagePaginationFooter',
            infoEl: '#stitchingWastagePageInfo',
            paginationList: '#stitchingWastagePaginationList',
            pageSizeSelect: '#stitchingWastagePageSizeSelect',
            pageSize: 10,
            itemName: 'sales'
        });

        const searchInput = document.getElementById('stitchingWastageSearchInput');
        if (searchInput && stitchingWastagePagination) {
            let debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const q = searchInput.value.trim().toLowerCase();
                    stitchingWastagePagination.filter(row => {
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
