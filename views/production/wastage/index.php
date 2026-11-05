<?php
// Fabric & Cutting Wastage Selling Management View
?>
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-recycle text-success me-2"></i>Fabric &amp; Cutting Wastage Selling
            </h4>
            <span class="text-muted small">Maintain selling prices, link cutting module scrap batches, track buyers, and record waste revenue</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('production/cutting') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-scissors me-1"></i>Cutting Module
            </a>
            <?php if (hasPermission('wastage', 'create')): ?>
            <button type="button" class="btn btn-success btn-sm fw-semibold shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#recordWastageModal">
                <i class="bi bi-plus-circle me-1"></i> Record Wastage Sale
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Weight Sold -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Wastage Sold</span>
                        <div class="p-2 rounded-circle bg-success-subtle text-success">
                            <i class="bi bi-box-seam fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-1"><?= number_format($stats['total_weight'], 2) ?> <span class="fs-6 fw-normal text-muted">kg</span></h3>
                    <div class="small text-muted d-flex justify-content-between align-items-center">
                        <span><?= number_format($stats['total_records']) ?> Sale Transaction(s)</span>
                        <span class="badge bg-light text-secondary border">Scrap Out</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Revenue / Value -->
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
                        <span>Avg: <strong class="text-dark">₹<?= number_format($stats['avg_rate_per_kg'], 2) ?>/kg</strong></span>
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
                        <div class="p-2 rounded-circle bg-warning-subtle text-warning">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-warning mb-1">₹ <?= number_format($stats['total_pending'], 2) ?></h3>
                    <div class="small text-muted d-flex justify-content-between align-items-center">
                        <span><?= number_format($stats['count_pending'] + $stats['count_partial']) ?> Unsettled</span>
                        <span class="badge bg-warning-subtle text-warning border">Due</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('production/wastage') ?>" class="row g-2 align-items-center">
                <!-- Search -->
                <div class="col-lg-3 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="wastageSearchInput" class="form-control form-control-sm border-start-0" placeholder="Search buyer, sale #, invoice..." value="<?= e($search) ?>" autocomplete="off">
                    </div>
                </div>

                <!-- Cutting Batch Filter -->
                <div class="col-lg-2 col-md-6">
                    <select name="cutting_batch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Cutting Batches --</option>
                        <?php foreach ($cuttingBatches as $cb): ?>
                            <option value="<?= $cb['id'] ?>" <?= ($cuttingBatchId == $cb['id']) ? 'selected' : '' ?>>
                                <?= e($cb['batch_number']) ?> (<?= number_format($cb['wastage_weight'], 2) ?> kg waste)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Wastage Type -->
                <div class="col-lg-2 col-md-4">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Wastage Types --</option>
                        <option value="FABRIC_SCRAP" <?= $type === 'FABRIC_SCRAP' ? 'selected' : '' ?>>Fabric Scrap</option>
                        <option value="CUTTING_END_BITS" <?= $type === 'CUTTING_END_BITS' ? 'selected' : '' ?>>Cutting End-Bits</option>
                        <option value="DEFECTIVE_FABRIC_SCRAP" <?= $type === 'DEFECTIVE_FABRIC_SCRAP' ? 'selected' : '' ?>>Defective Fabric Scrap</option>
                        <option value="BULK_COTTON_WASTE" <?= $type === 'BULK_COTTON_WASTE' ? 'selected' : '' ?>>Bulk Cotton Waste</option>
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
                <div class="col-lg-2 col-md-4">
                    <div class="input-group input-group-sm">
                        <input type="date" name="date_from" class="form-control form-control-sm" title="From Date" value="<?= e($dateFrom) ?>" onchange="this.form.submit()">
                        <input type="date" name="date_to" class="form-control form-control-sm" title="To Date" value="<?= e($dateTo) ?>" onchange="this.form.submit()">
                    </div>
                </div>

                <!-- Actions -->
                <div class="col-lg-1 col-md-4 text-end">
                    <a href="<?= url('production/wastage') ?>" class="btn btn-sm btn-outline-secondary w-100" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Wastage Sales Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-receipt text-success me-2"></i>Wastage Sales Log (<?= count($sales) ?>)
            </h6>
            <span class="text-muted small">Realized selling prices and buyer transactions</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($sales)): ?>
                <div class="text-center py-5 text-muted">
                    <div class="d-inline-flex p-3 rounded-circle bg-light mb-2">
                        <i class="bi bi-recycle fs-1 text-success"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No Wastage Sales Recorded</h5>
                    <p class="text-muted small mb-0">No sales match your search or filter parameters. Click "Record Wastage Sale" to add a new transaction.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="wastageTable" style="font-size: 13px;">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Sale # &amp; Date</th>
                                <th>Buyer &amp; Contact</th>
                                <th>Cutting Batch &amp; Category</th>
                                <th class="text-end">Weight &amp; Rate</th>
                                <th class="text-end">Total Price</th>
                                <th class="text-center">Payment Status</th>
                                <th class="text-center">Bill / Proof</th>
                                <th class="text-center" style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $s): ?>
                                <tr class="wastage-row">
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

                                    <!-- Wastage Type & Linked Batch -->
                                    <td>
                                        <?php if (!empty($s['cutting_batch_number'])): ?>
                                            <div class="mb-1">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
                                                    <i class="bi bi-scissors me-1"></i><?= e($s['cutting_batch_number']) ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($s['cutting_batch_name'])): ?>
                                                <div class="small text-muted text-truncate" style="max-width: 180px;" title="<?= e($s['cutting_batch_name']) ?>">
                                                    <?= e($s['cutting_batch_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php
                                        $typeBadge = match($s['wastage_type']) {
                                            'FABRIC_SCRAP'           => 'bg-success-subtle text-success border-success-subtle',
                                            'CUTTING_END_BITS'       => 'bg-warning-subtle text-dark border-warning-subtle',
                                            'DEFECTIVE_FABRIC_SCRAP' => 'bg-danger-subtle text-danger border-danger-subtle',
                                            'BULK_COTTON_WASTE'      => 'bg-info-subtle text-info border-info-subtle',
                                            default                  => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                        };
                                        $typeLabel = str_replace('_', ' ', $s['wastage_type']);
                                        ?>
                                        <span class="badge <?= $typeBadge ?> border fw-bold mt-1">
                                            <?= e($typeLabel) ?>
                                        </span>
                                        <?php if (!empty($s['program_number'])): ?>
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-diagram-3 me-1 text-secondary"></i><?= e($s['program_number']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Weight & Rate -->
                                    <td class="text-end">
                                        <div class="fw-bold text-dark fs-7"><?= number_format($s['weight'], 2) ?> kg</div>
                                        <small class="text-muted">@ ₹<?= number_format($s['rate_per_kg'], 2) ?>/kg</small>
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
                                            <button type="button" class="btn btn-outline-info" title="View Wastage Sale Details" aria-label="View Wastage Sale Details" onclick="openWastageReceipt(<?= (int)$s['id'] ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <!-- Print Receipt Button -->
                                            <button type="button" class="btn btn-outline-secondary" title="Print Sale Receipt" aria-label="Print Sale Receipt" onclick='openWastageReceipt(<?= (int)$s['id'] ?>)'>
                                                <i class="bi bi-printer"></i>
                                            </button>

                                            <?php if (hasPermission('wastage', 'edit')): ?>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-outline-primary" title="Edit Sale Details" onclick='openEditWastageModal(<?= json_encode($s) ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php endif; ?>

                                            <?php if (hasPermission('wastage', 'delete')): ?>
                                            <!-- Delete Button -->
                                            <form method="POST" action="<?= url('production/wastage/delete/' . $s['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Wastage Sale #<?= e(addslashes($s['sale_number'])) ?>?');">
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
                <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="wastagePaginationFooter">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="small text-muted fw-medium" id="wastagePageInfo">
                            Showing 1 to 10 of <?= count($sales) ?> sales
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-muted mb-0 fw-medium">Show:</label>
                            <select id="wastagePageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                                <option value="10" selected>10 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                    </div>
                    <nav aria-label="Wastage Sales Pagination">
                        <ul class="pagination pagination-modern mb-0" id="wastagePaginationList">
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. RECORD WASTAGE SALE MODAL                                              -->
<!-- ========================================================================= -->
<div class="modal fade" id="recordWastageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-success">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="bi bi-plus-circle me-2"></i>Record Fabric &amp; Cutting Wastage Sale
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('production/wastage/store') ?>" enctype="multipart/form-data" id="recordWastageForm">
                <?= CSRF::field() ?>
                <input type="hidden" name="program_id" id="newProgramId" value="">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- 1. Primary Dropdown: Cutting Module Wastage Selection -->
                        <div class="col-12">
                            <div class="p-3 bg-light rounded border border-success-subtle shadow-sm">
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                    <span>
                                        <i class="bi bi-scissors text-success me-1"></i>Select Cutting Module Wastage / Scrap Batch <span class="text-danger">*</span>
                                    </span>
                                    <span class="badge bg-success-subtle text-success border">Cutting Wastage Dropdown</span>
                                </label>
                                <select name="cutting_batch_id" id="newCuttingBatchId" class="form-select form-select-md fw-semibold border-success" onchange="onNewCuttingBatchChange(this)" required>
                                    <option value="" data-avail-wastage="0" data-cost-rate="0" data-total-wastage="0">-- Select Cutting Module Wastage / Scrap Batch * --</option>
                                    <?php 
                                    $availableBatches = array_filter($cuttingBatches, fn($b) => (float)$b['available_wastage_weight'] > 0);
                                    $soldBatches = array_filter($cuttingBatches, fn($b) => (float)$b['available_wastage_weight'] <= 0);
                                    ?>
                                    <?php if (!empty($availableBatches)): ?>
                                        <optgroup label="✓ Available Cutting Wastage (Ready to Sell)">
                                            <?php foreach ($availableBatches as $cb): 
                                                $costRate = ($cb['wastage_weight'] > 0 && $cb['wastage_cost'] > 0) 
                                                    ? round($cb['wastage_cost'] / $cb['wastage_weight'], 2) 
                                                    : ($cb['roll_weight'] > 0 ? round($cb['roll_price'] / $cb['roll_weight'], 2) : 0);
                                            ?>
                                                <option value="<?= $cb['id'] ?>" 
                                                    data-batch-no="<?= e($cb['batch_number']) ?>" 
                                                    data-batch-name="<?= e($cb['name']) ?>" 
                                                    data-program-id="<?= (int)($cb['program_id'] ?? 0) ?>" 
                                                    data-program-no="<?= e($cb['program_number'] ?? '') ?>" 
                                                    data-program-name="<?= e($cb['program_name'] ?? '') ?>" 
                                                    data-total-wastage="<?= (float)$cb['wastage_weight'] ?>" 
                                                    data-sold-wastage="<?= (float)$cb['sold_weight'] ?>" 
                                                    data-avail-wastage="<?= (float)$cb['available_wastage_weight'] ?>" 
                                                    data-cost-rate="<?= $costRate ?>" 
                                                    data-wastage-cost="<?= (float)$cb['wastage_cost'] ?>" 
                                                    data-seller="<?= e($cb['seller_name'] ?? '') ?>" 
                                                    data-color="<?= e($cb['color'] ?? '') ?>" 
                                                    data-design="<?= e($cb['design_name'] ?? '') ?>">
                                                    [<?= e($cb['batch_number']) ?>] <?= e($cb['name']) ?> • Available: <?= number_format($cb['available_wastage_weight'], 2) ?> kg (Total: <?= number_format($cb['wastage_weight'], 2) ?> kg) • Rate: ₹<?= number_format($costRate, 2) ?>/kg
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php else: ?>
                                        <option value="" disabled class="text-danger fw-bold">⚠ No cutting batches with unsold wastage currently available</option>
                                    <?php endif; ?>

                                    <?php if (!empty($soldBatches)): ?>
                                        <optgroup label="✗ Fully Sold Batches (Unavailable)">
                                            <?php foreach ($soldBatches as $cb): 
                                                $costRate = ($cb['wastage_weight'] > 0 && $cb['wastage_cost'] > 0) 
                                                    ? round($cb['wastage_cost'] / $cb['wastage_weight'], 2) 
                                                    : ($cb['roll_weight'] > 0 ? round($cb['roll_price'] / $cb['roll_weight'], 2) : 0);
                                            ?>
                                                <option value="<?= $cb['id'] ?>" disabled class="text-muted bg-light"
                                                    data-batch-no="<?= e($cb['batch_number']) ?>" 
                                                    data-batch-name="<?= e($cb['name']) ?>" 
                                                    data-program-id="<?= (int)($cb['program_id'] ?? 0) ?>" 
                                                    data-program-no="<?= e($cb['program_number'] ?? '') ?>" 
                                                    data-program-name="<?= e($cb['program_name'] ?? '') ?>" 
                                                    data-total-wastage="<?= (float)$cb['wastage_weight'] ?>" 
                                                    data-sold-wastage="<?= (float)$cb['sold_weight'] ?>" 
                                                    data-avail-wastage="0.00" 
                                                    data-cost-rate="<?= $costRate ?>" 
                                                    data-wastage-cost="<?= (float)$cb['wastage_cost'] ?>" 
                                                    data-seller="<?= e($cb['seller_name'] ?? '') ?>" 
                                                    data-color="<?= e($cb['color'] ?? '') ?>" 
                                                    data-design="<?= e($cb['design_name'] ?? '') ?>">
                                                    [<?= e($cb['batch_number']) ?>] <?= e($cb['name']) ?> • [ALREADY SOLD] • Total: <?= number_format($cb['wastage_weight'], 2) ?> kg (Sold: <?= number_format($cb['sold_weight'], 2) ?> kg, Avail: 0.00 kg)
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>

                                <!-- Live Cutting Wastage Info Badge / Card -->
                                <div id="newBatchInfoCard" class="mt-2 p-2 bg-white rounded border small d-none">
                                    <div id="newBatchSoldAlert" class="alert alert-danger py-2 mb-2 small d-none"></div>
                                    <div class="d-flex justify-content-between flex-wrap gap-2">
                                        <div>
                                            <span class="fw-bold text-dark" id="newInfoBatchName"></span>
                                            <span class="text-muted ms-2" id="newInfoProg"></span>
                                        </div>
                                        <div>
                                            <span class="text-muted">Supplier: </span><strong id="newInfoSeller"></strong>
                                        </div>
                                    </div>
                                    <div class="mt-1 d-flex justify-content-between align-items-center flex-wrap gap-2 text-muted">
                                        <span>Total Cut Wastage: <strong class="text-dark" id="newInfoTotalWst">0.00 kg</strong></span>
                                        <span>Already Sold: <strong class="text-secondary" id="newInfoSoldWst">0.00 kg</strong></span>
                                        <span>Available To Sell: <strong class="text-success fs-7" id="newInfoAvailWst">0.00 kg</strong></span>
                                        <span>Fabric Cost: <strong class="text-primary" id="newInfoRate">₹0.00/kg</strong></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Buyer Name (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Scrap Buyer / Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="buyer_name" id="newBuyerName" class="form-control" placeholder="e.g. Krishna Scrap Traders" required>
                        </div>

                        <!-- Buyer Phone (Required, 10 digits) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Buyer Phone / Mobile <span class="text-danger">*</span> <small class="text-muted fw-normal">(10 digits)</small></label>
                            <input type="tel" name="buyer_phone" id="newBuyerPhone" class="form-control" placeholder="10-digit mobile (e.g. 9876543210)" pattern="[0-9]{10}" maxlength="10" minlength="10" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" required>
                        </div>

                        <!-- Buyer Company (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Buyer Company / Business Name <span class="text-danger">*</span></label>
                            <input type="text" name="buyer_company" id="newBuyerCompany" class="form-control" placeholder="e.g. Sri Waste Recycling Mills" required>
                        </div>

                        <!-- Wastage Category (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Wastage Category <span class="text-danger">*</span></label>
                            <select name="wastage_type" id="newWastageType" class="form-select" required>
                                <option value="">-- Select Wastage Category * --</option>
                                <option value="CUTTING_END_BITS" selected>Cutting End-Bits (Short length ends &amp; trims)</option>
                                <option value="FABRIC_SCRAP">Fabric Scrap (General Mixed Fabric)</option>
                                <option value="DEFECTIVE_FABRIC_SCRAP">Defective Fabric Scrap (Oil/Torn reject)</option>
                                <option value="BULK_COTTON_WASTE">Bulk Cotton Waste (Pure cotton bits)</option>
                                <option value="OTHER">Other Production Scrap</option>
                            </select>
                        </div>

                        <!-- Selling Price Maintenance Box (Manual Entry & Two-Way Auto-Calculation) -->
                        <div class="col-12">
                            <div class="p-3 bg-light rounded border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted fw-bold text-uppercase">
                                        <i class="bi bi-calculator me-1 text-success"></i>Selling Price &amp; Quantity (Manual Entry) <span class="text-danger">*</span>
                                    </span>
                                    <span class="badge bg-white text-secondary border small">Type Rate or Total Price directly</span>
                                </div>
                                <div class="row g-3">
                                    <!-- Weight in KG -->
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark">Wastage Weight (kg) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0.01" name="weight" id="newWeight" class="form-control fw-bold fs-6" placeholder="0.00" required oninput="onNewWeightChange()">
                                            <span class="input-group-text bg-white">kg</span>
                                        </div>
                                        <small class="text-muted" id="newWeightHint">Weight being sold to scrap buyer</small>
                                        <div id="newWeightExceedAlert" class="text-danger small mt-1 fw-bold d-none"></div>
                                    </div>

                                    <!-- Rate per KG (Manual Entry) -->
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark">Selling Rate (₹ / kg) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white fw-bold">₹</span>
                                            <input type="number" step="0.01" min="0.01" name="rate_per_kg" id="newRatePerKg" class="form-control fw-bold text-success fs-6" placeholder="0.00" required oninput="onNewRateChange()">
                                            <span class="input-group-text bg-white">/ kg</span>
                                        </div>
                                        <small class="text-muted">Enter manual scrap rate per kg</small>
                                    </div>

                                    <!-- Total Selling Amount (Editable Manual Entry) -->
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-success">Total Sale Price (₹) <span class="text-danger">*</span> <span class="badge bg-success-subtle text-success border ms-1">Editable</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-success-subtle text-success fw-bold">₹</span>
                                            <input type="number" step="0.01" min="0.01" name="total_amount" id="newTotalAmount" class="form-control fw-bold text-success fs-6" placeholder="0.00" required oninput="onNewTotalChange()">
                                        </div>
                                        <small class="text-muted">Or enter lump-sum total price</small>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2" id="newCalcPreview">Calculated: 0.00 kg × ₹0.00/kg = ₹0.00</small>
                            </div>
                        </div>

                        <!-- Payment & Sale Date Information -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Amount Received (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">₹</span>
                                <input type="number" step="0.01" min="0" name="paid_amount" id="newPaidAmount" class="form-control fw-bold" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Sale Date <span class="text-danger">*</span></label>
                            <input type="date" name="sale_date" id="newSaleDate" class="form-control fw-semibold" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm" id="btnRecordWastageSubmit">
                        <i class="bi bi-check-circle me-1"></i> Save Wastage Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. EDIT WASTAGE SALE MODAL                                                -->
<!-- ========================================================================= -->
<div class="modal fade" id="editWastageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-primary">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="bi bi-pencil-square me-2"></i>Edit Wastage Sale Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editWastageForm" enctype="multipart/form-data">
                <?= CSRF::field() ?>
                <input type="hidden" name="program_id" id="editProgramId" value="">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Cutting Module Wastage Selection in Edit -->
                        <div class="col-12">
                            <div class="p-3 bg-light rounded border border-primary-subtle shadow-sm">
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                    <span>
                                        <i class="bi bi-scissors text-primary me-1"></i>Select Cutting Module Wastage / Scrap Batch <span class="text-danger">*</span>
                                    </span>
                                    <span class="badge bg-primary-subtle text-primary border">Cutting Wastage Dropdown</span>
                                </label>
                                <select name="cutting_batch_id" id="editCuttingBatchId" class="form-select form-select-md fw-semibold" onchange="onEditCuttingBatchChange(this)" required>
                                    <option value="" data-avail-wastage="0" data-cost-rate="0">-- Select Cutting Module Wastage / Scrap Batch * --</option>
                                    <?php foreach ($cuttingBatches as $cb): 
                                        $costRate = ($cb['wastage_weight'] > 0 && $cb['wastage_cost'] > 0) 
                                            ? round($cb['wastage_cost'] / $cb['wastage_weight'], 2) 
                                            : ($cb['roll_weight'] > 0 ? round($cb['roll_price'] / $cb['roll_weight'], 2) : 0);
                                    ?>
                                        <option value="<?= $cb['id'] ?>" 
                                            data-batch-no="<?= e($cb['batch_number']) ?>" 
                                            data-batch-name="<?= e($cb['name']) ?>" 
                                            data-program-id="<?= (int)($cb['program_id'] ?? 0) ?>" 
                                            data-program-no="<?= e($cb['program_number'] ?? '') ?>" 
                                            data-program-name="<?= e($cb['program_name'] ?? '') ?>" 
                                            data-total-wastage="<?= (float)$cb['wastage_weight'] ?>" 
                                            data-sold-wastage="<?= (float)$cb['sold_weight'] ?>" 
                                            data-avail-wastage="<?= (float)$cb['available_wastage_weight'] ?>" 
                                            data-cost-rate="<?= $costRate ?>" 
                                            data-wastage-cost="<?= (float)$cb['wastage_cost'] ?>" 
                                            data-seller="<?= e($cb['seller_name'] ?? '') ?>" 
                                            data-color="<?= e($cb['color'] ?? '') ?>" 
                                            data-design="<?= e($cb['design_name'] ?? '') ?>">
                                            [<?= e($cb['batch_number']) ?>] <?= e($cb['name']) ?> • Wastage: <?= number_format($cb['wastage_weight'], 2) ?> kg (Avail: <?= number_format($cb['available_wastage_weight'], 2) ?> kg) • Rate: ₹<?= number_format($costRate, 2) ?>/kg
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Scrap Buyer / Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="buyer_name" id="editBuyerName" class="form-control" placeholder="e.g. Krishna Scrap Traders" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Buyer Phone / Mobile <span class="text-danger">*</span> <small class="text-muted fw-normal">(10 digits)</small></label>
                            <input type="tel" name="buyer_phone" id="editBuyerPhone" class="form-control" placeholder="10-digit mobile (e.g. 9876543210)" pattern="[0-9]{10}" maxlength="10" minlength="10" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Buyer Company / Business Name <span class="text-danger">*</span></label>
                            <input type="text" name="buyer_company" id="editBuyerCompany" class="form-control" placeholder="e.g. Sri Waste Recycling Mills" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Wastage Category <span class="text-danger">*</span></label>
                            <select name="wastage_type" id="editWastageType" class="form-select" required>
                                <option value="">-- Select Wastage Category * --</option>
                                <option value="CUTTING_END_BITS">Cutting End-Bits (Short length ends &amp; trims)</option>
                                <option value="FABRIC_SCRAP">Fabric Scrap (General Mixed Fabric)</option>
                                <option value="DEFECTIVE_FABRIC_SCRAP">Defective Fabric Scrap (Oil/Torn reject)</option>
                                <option value="BULK_COTTON_WASTE">Bulk Cotton Waste (Pure cotton bits)</option>
                                <option value="OTHER">Other Production Scrap</option>
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
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark">Wastage Weight (kg) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0.01" name="weight" id="editWeight" class="form-control fw-bold fs-6" required oninput="onEditWeightChange()">
                                            <span class="input-group-text bg-white">kg</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark">Selling Rate (₹ / kg) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white fw-bold">₹</span>
                                            <input type="number" step="0.01" min="0.01" name="rate_per_kg" id="editRatePerKg" class="form-control fw-bold text-success fs-6" required oninput="onEditRateChange()">
                                            <span class="input-group-text bg-white">/ kg</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-success">Total Sale Price (₹) <span class="text-danger">*</span> <span class="badge bg-success-subtle text-success border ms-1">Editable</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-success-subtle text-success fw-bold">₹</span>
                                            <input type="number" step="0.01" min="0.01" name="total_amount" id="editTotalAmount" class="form-control fw-bold text-success fs-6" required oninput="onEditTotalChange()">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Amount Received (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">₹</span>
                                <input type="number" step="0.01" min="0" name="paid_amount" id="editPaidAmount" class="form-control fw-bold" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Sale Date <span class="text-danger">*</span></label>
                            <input type="date" name="sale_date" id="editSaleDate" class="form-control fw-semibold" required>
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
<!-- 3. PRINTABLE RECEIPT SLIP MODAL                                           -->
<!-- ========================================================================= -->
<div class="modal fade" id="wastageReceiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-receipt me-1"></i>Wastage Sale Voucher
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="printableReceiptArea">
                <div class="text-center mb-3">
                    <h5 class="fw-bold mb-0 text-dark">GRM APPARELS</h5>
                    <small class="text-muted d-block">Production Scrap &amp; Wastage Sales Voucher</small>
                    <span class="badge bg-light text-dark border mt-1" id="recSaleNo">WST-2026-0001</span>
                </div>
                <hr class="my-2">
                <div class="row g-1 small mb-3">
                    <div class="col-6"><strong>Date:</strong> <span id="recDate">-</span></div>
                    <div class="col-6 text-end"><strong>Status:</strong> <span id="recStatus">-</span></div>
                    <div class="col-12"><strong>Buyer:</strong> <span id="recBuyer">-</span></div>
                    <div class="col-12" id="recCompanyRow"><strong>Company:</strong> <span id="recCompany">-</span></div>
                    <div class="col-12" id="recPhoneRow"><strong>Phone:</strong> <span id="recPhone">-</span></div>
                    <div class="col-12 text-muted" id="recBatchRow"><strong>Cutting Batch:</strong> <span id="recBatch">-</span></div>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item / Type</th>
                                <th class="text-end">Weight</th>
                                <th class="text-end">Rate/kg</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id="recType">Fabric Scrap</td>
                                <td class="text-end" id="recWeight">0.00 kg</td>
                                <td class="text-end" id="recRate">₹0.00</td>
                                <td class="text-end fw-bold" id="recTotal">₹0.00</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total Payable:</th>
                                <th class="text-end text-success" id="recTotalFooter">₹0.00</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Amount Paid:</th>
                                <th class="text-end" id="recPaid">₹0.00</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Balance Due:</th>
                                <th class="text-end text-danger" id="recDue">₹0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="small text-muted mb-3" id="recNotes"></div>
                <div class="d-flex justify-content-between text-muted small pt-3 border-top">
                    <div>Receiver's Signature</div>
                    <div>Authorized Signatory</div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-dark btn-sm px-3" onclick="printReceiptArea()">
                    <i class="bi bi-printer me-1"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function onNewCuttingBatchChange(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const batchId = selectEl.value;
    const infoCard = document.getElementById('newBatchInfoCard');
    const soldAlert = document.getElementById('newBatchSoldAlert');
    const submitBtn = document.getElementById('btnRecordWastageSubmit');
    const weightInput = document.getElementById('newWeight');
    const weightAlert = document.getElementById('newWeightExceedAlert');

    if (!batchId) {
        infoCard.classList.add('d-none');
        document.getElementById('newProgramId').value = '';
        if (soldAlert) soldAlert.classList.add('d-none');
        if (weightAlert) weightAlert.classList.add('d-none');
        if (submitBtn) { submitBtn.disabled = false; submitBtn.title = ""; }
        return;
    }

    const batchNo = selectedOption.getAttribute('data-batch-no') || '';
    const batchName = selectedOption.getAttribute('data-batch-name') || '';
    const progId = selectedOption.getAttribute('data-program-id') || '';
    const progNo = selectedOption.getAttribute('data-program-no') || '';
    const totalWst = parseFloat(selectedOption.getAttribute('data-total-wastage')) || 0;
    const soldWst = parseFloat(selectedOption.getAttribute('data-sold-wastage')) || 0;
    const availWst = parseFloat(selectedOption.getAttribute('data-avail-wastage')) || 0;
    const costRate = parseFloat(selectedOption.getAttribute('data-cost-rate')) || 0;
    const seller = selectedOption.getAttribute('data-seller') || 'In-House';

    document.getElementById('newProgramId').value = progId;
    document.getElementById('newInfoBatchName').textContent = `${batchNo} - ${batchName}`;
    document.getElementById('newInfoProg').textContent = progNo ? `(${progNo})` : '';
    document.getElementById('newInfoSeller').textContent = seller;
    document.getElementById('newInfoTotalWst').textContent = `${totalWst.toFixed(2)} kg`;
    document.getElementById('newInfoSoldWst').textContent = `${soldWst.toFixed(2)} kg`;
    document.getElementById('newInfoAvailWst').textContent = `${availWst.toFixed(2)} kg`;
    document.getElementById('newInfoRate').textContent = `₹${costRate.toFixed(2)}/kg`;
    infoCard.classList.remove('d-none');

    // Validation guard: If batch wastage is fully sold out
    if (availWst <= 0) {
        if (soldAlert) {
            soldAlert.innerHTML = `<i class="bi bi-x-octagon-fill me-2"></i><strong>Cannot Sell:</strong> Cutting batch <strong>${batchNo}</strong> wastage (${totalWst.toFixed(2)} kg) is <strong>ALREADY FULLY SOLD</strong> (0.00 kg available). Please choose an available batch.`;
            soldAlert.classList.remove('d-none');
        }
        weightInput.value = '0.00';
        weightInput.setAttribute('max', '0');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.title = "Cannot save: selected batch wastage is already fully sold.";
        }
    } else {
        if (soldAlert) soldAlert.classList.add('d-none');
        weightInput.value = availWst.toFixed(2);
        weightInput.setAttribute('max', availWst.toFixed(2));
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.title = "";
        }
    }

    // Auto select Cutting End-Bits
    const typeSelect = document.getElementById('newWastageType');
    if (typeSelect) {
        typeSelect.value = 'CUTTING_END_BITS';
    }

    onNewWeightChange();
}

function onEditCuttingBatchChange(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const progId = selectedOption.getAttribute('data-program-id') || '';
    document.getElementById('editProgramId').value = progId;
}

// Two-way manual selling price calculation for Record Modal with balance validation
function onNewWeightChange() {
    const w = parseFloat(document.getElementById('newWeight').value) || 0;
    const r = parseFloat(document.getElementById('newRatePerKg').value) || 0;
    const t = parseFloat(document.getElementById('newTotalAmount').value) || 0;
    const selectEl = document.getElementById('newCuttingBatchId');
    const selectedOption = selectEl ? selectEl.options[selectEl.selectedIndex] : null;
    const availWst = parseFloat(selectedOption?.getAttribute('data-avail-wastage')) || 0;
    const weightAlert = document.getElementById('newWeightExceedAlert');
    const submitBtn = document.getElementById('btnRecordWastageSubmit');

    // Validate weight against available balance
    if (selectEl && selectEl.value) {
        if (availWst <= 0) {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.title = "Cannot save: selected batch wastage is already fully sold.";
            }
        } else if (w > availWst) {
            if (weightAlert) {
                weightAlert.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>Weight (${w.toFixed(2)} kg) exceeds available balance (${availWst.toFixed(2)} kg)!`;
                weightAlert.classList.remove('d-none');
            }
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.title = `Weight cannot exceed available wastage (${availWst.toFixed(2)} kg)`;
            }
        } else {
            if (weightAlert) weightAlert.classList.add('d-none');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.title = "";
            }
        }
    }

    if (r > 0) {
        const total = w * r;
        document.getElementById('newTotalAmount').value = total > 0 ? total.toFixed(2) : '';
        document.getElementById('newCalcPreview').textContent = `Calculated: ${w.toFixed(2)} kg × ₹${r.toFixed(2)}/kg = ₹${total.toFixed(2)}`;
    } else if (t > 0 && w > 0) {
        const rate = t / w;
        document.getElementById('newRatePerKg').value = rate.toFixed(2);
        document.getElementById('newCalcPreview').textContent = `Calculated: ${w.toFixed(2)} kg × ₹${rate.toFixed(2)}/kg = ₹${t.toFixed(2)}`;
    } else {
        document.getElementById('newCalcPreview').textContent = `Calculated: ${w.toFixed(2)} kg × ₹${r.toFixed(2)}/kg = ₹0.00`;
    }
    autoCheckNewPaymentStatus();
}

function onNewRateChange() {
    const w = parseFloat(document.getElementById('newWeight').value) || 0;
    const r = parseFloat(document.getElementById('newRatePerKg').value) || 0;
    const total = w * r;
    document.getElementById('newTotalAmount').value = total > 0 ? total.toFixed(2) : '';
    document.getElementById('newCalcPreview').textContent = `Calculated: ${w.toFixed(2)} kg × ₹${r.toFixed(2)}/kg = ₹${total.toFixed(2)}`;
    autoCheckNewPaymentStatus();
}

function onNewTotalChange() {
    const w = parseFloat(document.getElementById('newWeight').value) || 0;
    const t = parseFloat(document.getElementById('newTotalAmount').value) || 0;
    if (w > 0 && t > 0) {
        const rate = t / w;
        document.getElementById('newRatePerKg').value = rate.toFixed(2);
        document.getElementById('newCalcPreview').textContent = `Calculated: ${w.toFixed(2)} kg × ₹${rate.toFixed(2)}/kg = ₹${t.toFixed(2)}`;
    } else {
        document.getElementById('newCalcPreview').textContent = `Total Price Entered: ₹${t.toFixed(2)}`;
    }
    autoCheckNewPaymentStatus();
}

function autoCheckNewPaymentStatus() {
    const total = parseFloat(document.getElementById('newTotalAmount').value) || 0;
    const paid = parseFloat(document.getElementById('newPaidAmount').value) || 0;
    const statusSelect = document.getElementById('newPaymentStatus');

    if (total > 0 && paid >= total) {
        statusSelect.value = 'PAID';
    } else if (paid > 0 && paid < total) {
        statusSelect.value = 'PARTIAL';
    } else {
        statusSelect.value = 'PENDING';
    }
}

// Two-way manual selling price calculation for Edit Modal
function onEditWeightChange() {
    const w = parseFloat(document.getElementById('editWeight').value) || 0;
    const r = parseFloat(document.getElementById('editRatePerKg').value) || 0;
    const t = parseFloat(document.getElementById('editTotalAmount').value) || 0;

    if (r > 0) {
        const total = w * r;
        document.getElementById('editTotalAmount').value = total > 0 ? total.toFixed(2) : '';
    } else if (t > 0 && w > 0) {
        const rate = t / w;
        document.getElementById('editRatePerKg').value = rate.toFixed(2);
    }
}

function onEditRateChange() {
    const w = parseFloat(document.getElementById('editWeight').value) || 0;
    const r = parseFloat(document.getElementById('editRatePerKg').value) || 0;
    const total = w * r;
    document.getElementById('editTotalAmount').value = total > 0 ? total.toFixed(2) : '';
}

function onEditTotalChange() {
    const w = parseFloat(document.getElementById('editWeight').value) || 0;
    const t = parseFloat(document.getElementById('editTotalAmount').value) || 0;
    if (w > 0 && t > 0) {
        const rate = t / w;
        document.getElementById('editRatePerKg').value = rate.toFixed(2);
    }
}

function openEditWastageModal(data) {
    document.getElementById('editWastageForm').action = '<?= url('production/wastage/update') ?>/' + data.id;
    document.getElementById('editCuttingBatchId').value = data.cutting_batch_id || '';
    document.getElementById('editProgramId').value = data.program_id || '';
    document.getElementById('editBuyerName').value = data.buyer_name || '';
    document.getElementById('editBuyerPhone').value = (data.buyer_phone || '').replace(/[^0-9]/g, '').slice(0, 10);
    document.getElementById('editBuyerCompany').value = data.buyer_company || '';
    document.getElementById('editWastageType').value = data.wastage_type || 'CUTTING_END_BITS';
    document.getElementById('editWeight').value = parseFloat(data.weight).toFixed(2);
    document.getElementById('editRatePerKg').value = parseFloat(data.rate_per_kg).toFixed(2);
    document.getElementById('editTotalAmount').value = parseFloat(data.total_amount).toFixed(2);
    document.getElementById('editPaidAmount').value = parseFloat(data.paid_amount || 0).toFixed(2);
    document.getElementById('editSaleDate').value = data.sale_date || '';

    const modal = new bootstrap.Modal(document.getElementById('editWastageModal'));
    modal.show();
}

function openWastageReceipt(id) {
    fetch('<?= url('production/wastage/receipt') ?>/' + id)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.sale) {
                const s = res.sale;
                document.getElementById('recSaleNo').textContent = s.sale_number;
                document.getElementById('recDate').textContent = s.sale_date;
                document.getElementById('recStatus').textContent = s.payment_status;
                document.getElementById('recBuyer').textContent = s.buyer_name;
                document.getElementById('recCompany').textContent = s.buyer_company || '-';
                document.getElementById('recPhone').textContent = s.buyer_phone || '-';
                
                if (s.cutting_batch_number) {
                    document.getElementById('recBatchRow').classList.remove('d-none');
                    document.getElementById('recBatch').textContent = s.cutting_batch_number + (s.cutting_batch_name ? (' - ' + s.cutting_batch_name) : '');
                } else {
                    document.getElementById('recBatchRow').classList.add('d-none');
                }

                document.getElementById('recType').textContent = s.wastage_type.replace(/_/g, ' ');
                document.getElementById('recWeight').textContent = parseFloat(s.weight).toFixed(2) + ' kg';
                document.getElementById('recRate').textContent = '₹' + parseFloat(s.rate_per_kg).toFixed(2);
                document.getElementById('recTotal').textContent = '₹' + parseFloat(s.total_amount).toFixed(2);
                document.getElementById('recTotalFooter').textContent = '₹' + parseFloat(s.total_amount).toFixed(2);
                document.getElementById('recPaid').textContent = '₹' + parseFloat(s.paid_amount).toFixed(2);
                const due = Math.max(0, parseFloat(s.total_amount) - parseFloat(s.paid_amount));
                document.getElementById('recDue').textContent = '₹' + due.toFixed(2);
                document.getElementById('recNotes').textContent = s.notes ? ('Remarks: ' + s.notes) : '';

                const modal = new bootstrap.Modal(document.getElementById('wastageReceiptModal'));
                modal.show();
            }
        })
        .catch(err => {
            console.error('Error fetching receipt data:', err);
            alert('Failed to load sale voucher.');
        });
}

function printReceiptArea() {
    const printContents = document.getElementById('printableReceiptArea').innerHTML;
    const origContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = origContents;
    window.location.reload();
}

// Client-side Validation Handlers
document.addEventListener('DOMContentLoaded', function() {
    const recordForm = document.getElementById('recordWastageForm');
    if (recordForm) {
        recordForm.addEventListener('submit', function(e) {
            const batchId = document.getElementById('newCuttingBatchId').value;
            const buyerName = document.getElementById('newBuyerName').value.trim();
            const buyerPhone = document.getElementById('newBuyerPhone').value.trim();
            const buyerCompany = document.getElementById('newBuyerCompany').value.trim();
            const wastageType = document.getElementById('newWastageType').value;
            const weight = parseFloat(document.getElementById('newWeight').value) || 0;
            const rate = parseFloat(document.getElementById('newRatePerKg').value) || 0;
            const total = parseFloat(document.getElementById('newTotalAmount').value) || 0;
            const paid = document.getElementById('newPaidAmount').value.trim();
            const status = document.getElementById('newPaymentStatus').value;
            const method = document.getElementById('newPaymentMethod').value;
            const date = document.getElementById('newSaleDate').value.trim();

            if (!batchId) {
                e.preventDefault();
                alert('Select Cutting Module Wastage / Scrap Batch is required.');
                document.getElementById('newCuttingBatchId').focus();
                return false;
            }
            if (!buyerName) {
                e.preventDefault();
                alert('Scrap Buyer / Vendor Name is required.');
                document.getElementById('newBuyerName').focus();
                return false;
            }
            if (!buyerPhone || !/^[0-9]{10}$/.test(buyerPhone)) {
                e.preventDefault();
                alert('Buyer Phone / Mobile is required and must be exactly 10 digits.');
                document.getElementById('newBuyerPhone').focus();
                return false;
            }
            if (!buyerCompany) {
                e.preventDefault();
                alert('Buyer Company / Business Name is required.');
                document.getElementById('newBuyerCompany').focus();
                return false;
            }
            if (!wastageType) {
                e.preventDefault();
                alert('Wastage Category is required.');
                document.getElementById('newWastageType').focus();
                return false;
            }
            if (weight <= 0) {
                e.preventDefault();
                alert('Wastage Weight must be greater than 0.');
                document.getElementById('newWeight').focus();
                return false;
            }
            if (rate <= 0 && total <= 0) {
                e.preventDefault();
                alert('Selling Price & Quantity (Manual Entry) is required. Enter Rate per kg or Total Sale Price.');
                document.getElementById('newRatePerKg').focus();
                return false;
            }
            if (paid === '' || isNaN(parseFloat(paid)) || parseFloat(paid) < 0) {
                e.preventDefault();
                alert('Amount Received (₹) is required (enter 0 if unpaid).');
                document.getElementById('newPaidAmount').focus();
                return false;
            }
            if (!date) {
                e.preventDefault();
                alert('Sale Date is required.');
                document.getElementById('newSaleDate').focus();
                return false;
            }
        });
    }

    const editForm = document.getElementById('editWastageForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const batchId = document.getElementById('editCuttingBatchId').value;
            const buyerName = document.getElementById('editBuyerName').value.trim();
            const buyerPhone = document.getElementById('editBuyerPhone').value.trim();
            const buyerCompany = document.getElementById('editBuyerCompany').value.trim();
            const wastageType = document.getElementById('editWastageType').value;
            const weight = parseFloat(document.getElementById('editWeight').value) || 0;
            const rate = parseFloat(document.getElementById('editRatePerKg').value) || 0;
            const total = parseFloat(document.getElementById('editTotalAmount').value) || 0;
            const paid = document.getElementById('editPaidAmount').value.trim();
            const status = document.getElementById('editPaymentStatus').value;
            const method = document.getElementById('editPaymentMethod').value;
            const date = document.getElementById('editSaleDate').value.trim();

            if (!batchId) {
                e.preventDefault();
                alert('Select Cutting Module Wastage / Scrap Batch is required.');
                document.getElementById('editCuttingBatchId').focus();
                return false;
            }
            if (!buyerName) {
                e.preventDefault();
                alert('Scrap Buyer / Vendor Name is required.');
                document.getElementById('editBuyerName').focus();
                return false;
            }
            if (!buyerPhone || !/^[0-9]{10}$/.test(buyerPhone)) {
                e.preventDefault();
                alert('Buyer Phone / Mobile is required and must be exactly 10 digits.');
                document.getElementById('editBuyerPhone').focus();
                return false;
            }
            if (!buyerCompany) {
                e.preventDefault();
                alert('Buyer Company / Business Name is required.');
                document.getElementById('editBuyerCompany').focus();
                return false;
            }
            if (!wastageType) {
                e.preventDefault();
                alert('Wastage Category is required.');
                document.getElementById('editWastageType').focus();
                return false;
            }
            if (weight <= 0) {
                e.preventDefault();
                alert('Wastage Weight must be greater than 0.');
                document.getElementById('editWeight').focus();
                return false;
            }
            if (rate <= 0 && total <= 0) {
                e.preventDefault();
                alert('Selling Price & Quantity (Manual Entry) is required. Enter Rate per kg or Total Sale Price.');
                document.getElementById('editRatePerKg').focus();
                return false;
            }
            if (paid === '' || isNaN(parseFloat(paid)) || parseFloat(paid) < 0) {
                e.preventDefault();
                alert('Amount Received (₹) is required (enter 0 if unpaid).');
                document.getElementById('editPaidAmount').focus();
                return false;
            }
            if (!date) {
                e.preventDefault();
                alert('Sale Date is required.');
                document.getElementById('editSaleDate').focus();
                return false;
            }
        });
    }

    // Initialize Wastage Sales Table Pagination
    if (typeof window.initClientPagination === 'function' && document.getElementById('wastageTable')) {
        const wastagePagination = window.initClientPagination({
            container: '#wastageTable tbody',
            itemSelector: 'tr.wastage-row',
            footer: '#wastagePaginationFooter',
            infoEl: '#wastagePageInfo',
            paginationList: '#wastagePaginationList',
            pageSizeSelect: '#wastagePageSizeSelect',
            pageSize: 10,
            itemName: 'sales'
        });

        const searchInput = document.getElementById('wastageSearchInput');
        if (searchInput && wastagePagination) {
            let debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const q = searchInput.value.trim().toLowerCase();
                    wastagePagination.filter(row => {
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
