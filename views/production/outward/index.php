<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-truck me-2 text-primary"></i>Production Outward &amp; Dispatch</h2>
        <span class="text-muted">Dispatch finished production batches to their selected destination with vehicle tracking</span>
    </div>
    <?php if (!empty($approvedBatches) && hasPermission('outward', 'create')): ?>
        <button type="button" class="btn btn-primary shadow-sm" onclick="openDispatchModal(null)">
            <i class="bi bi-plus-circle me-1"></i> New Dispatch Outward
        </button>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('production/outward') ?>" class="row g-3 align-items-end">
            <div class="col-lg-5"><label class="form-label small fw-semibold">Search outward, batch, destination, vehicle, transporter</label><input type="search" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Outward #, batch #, concern, vehicle, LR"></div>
            <div class="col-lg-3"><label class="form-label small fw-semibold">Destination Warehouse</label><select name="warehouse_id" class="form-select"><option value="">All warehouses</option><?php foreach ($warehouses as $warehouse): ?><option value="<?= (int)$warehouse['id'] ?>" <?= (int)$warehouseId === (int)$warehouse['id'] ? 'selected' : '' ?>><?= e($warehouse['name']) ?><?= !empty($warehouse['code']) ? ' (' . e($warehouse['code']) . ')' : '' ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">All statuses</option><?php foreach (['DISPATCHED', 'RECEIVED', 'DISCREPANCY'] as $statusOption): ?><option value="<?= e($statusOption) ?>" <?= $currentStatus === $statusOption ? 'selected' : '' ?>><?= e($statusOption) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button><a class="btn btn-outline-secondary" href="<?= url('production/outward') ?>" title="Clear filters"><i class="bi bi-x-lg"></i></a></div>
        </form>
    </div>
</div>

<!-- Approved Batches Container (One-by-One Listing with Dispatch Now Buttons) -->
<?php if (!empty($approvedBatches) && hasPermission('outward', 'create')): ?>
    <div class="card border-primary shadow-sm mb-4">
        <div class="card-header bg-primary bg-opacity-10 border-primary py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-box-seam-fill text-primary fs-5"></i>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Approved Batches Ready for Dispatch</h6>
                    <small class="text-muted">The following approved finished production batches are packed and waiting for outward dispatch:</small>
                </div>
            </div>
            <span class="badge bg-primary rounded-pill px-3 py-2 fs-7 fw-semibold">
                <i class="bi bi-clock-history me-1"></i><?= count($approvedBatches) ?> Batch(es) Ready
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Production Batch</th>
                            <th>Product &amp; Program</th>
                            <th>Destination Concern / Warehouse</th>
                            <th>Total Pieces &amp; Boxes</th>
                            <th>Status</th>
                            <th class="text-end" style="min-width: 160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approvedBatches as $idx => $ab): ?>
                            <tr>
                                <td class="text-muted fw-bold"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-primary font-monospace fs-7">
                                        <i class="bi bi-box-seam me-1"></i><?= e($ab['batch_number']) ?>
                                    </div>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= formatDate($ab['created_at']) ?></small>
                                    <?php if (!empty($ab['sale_bill_id'])): ?>
                                        <div class="mt-1"><span class="badge bg-success-subtle text-success border border-success-subtle font-monospace"><i class="bi bi-receipt me-1"></i><?= e($ab['sale_bill_number']) ?> · ₹<?= number_format((float)($ab['sale_bill_total'] ?? 0), 2) ?></span></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($ab['product_name']) ?></div>
                                    <small class="text-muted">
                                        <?php if (!empty($ab['program_id'])): ?>
                                            Program: <a href="<?= url('production/programs/view/' . $ab['program_id']) ?>" class="text-decoration-none fw-semibold"><?= e($ab['program_number']) ?></a>
                                        <?php elseif (($ab['source_type'] ?? '') === 'SCAN'): ?>
                                            Source: <span class="fw-semibold text-primary">QR Scan Batch</span>
                                        <?php else: ?>
                                            Source: <span class="fw-semibold text-info">Inwarded Inventory</span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="bi bi-geo-alt text-primary me-1"></i><?= e($ab['destination_concern_name'] ?: $ab['warehouse_name'] ?: 'Unassigned') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-success fs-6">
                                        <i class="bi bi-check-circle me-1"></i><?= number_format($ab['total_qty']) ?> pcs
                                    </div>
                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace"><?= number_format($ab['box_count']) ?> <?= $ab['box_count'] == 1 ? 'box' : 'boxes' ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-patch-check-fill me-1"></i>APPROVED
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                        <?php if (!empty($ab['sale_bill_id'])): ?>
                                            <a href="<?= url('production/batches/sale-bill/' . (int)$ab['id']) ?>" class="btn btn-sm btn-outline-success" title="View Sale Bill">
                                                <i class="bi bi-receipt me-1"></i>Sale Bill
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-primary fw-semibold shadow-xs" 
                                                onclick='openDispatchModal(<?= json_encode($ab, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            <i class="bi bi-truck me-1"></i> Dispatch Now &rarr;
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Outward History Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="card-title mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Outward Dispatch History</h5>
            <span class="badge bg-light text-dark border rounded-pill px-3 py-2"><?= count($outwards) ?> Records</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="outwardSearchInput" class="form-control border-start-0 ps-0" placeholder="Search dispatches...">
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="outwardTable">
                <thead class="table-light">
                    <tr>
                        <th>Outward #</th>
                        <th>Production Batch</th>
                        <th>Product</th>
                        <th>Destination Warehouse</th>
                        <th>Total Pieces</th>
                        <th>Vehicle Number</th>
                        <th>Dispatched At</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="outwardTableBody">
                    <?php if (empty($outwards)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> No outward dispatches recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($outwards as $o): ?>
                            <?php
                                $searchData = strtolower(
                                    ($o['outward_number'] ?? '') . ' ' . 
                                    ($o['batch_number'] ?? '') . ' ' . 
                                    ($o['product_name'] ?? '') . ' ' . 
                                    ($o['destination_concern_name'] ?? '') . ' ' .
                                    ($o['warehouse_name'] ?? '') . ' ' . 
                                    ($o['vehicle_number'] ?? '') . ' ' . 
                                    ($o['driver_name'] ?? '') . ' ' .
                                    ($o['driver_contact'] ?? '') . ' ' .
                                    ($o['transporter_name'] ?? '') . ' ' .
                                    ($o['lr_number'] ?? '') . ' ' .
                                    ($o['status'] ?? '') . ' ' .
                                    ($o['created_by_name'] ?? '') . ' ' . 
                                    ($o['sale_bill_number'] ?? '') . ' ' . 
                                    ($o['total_qty'] ?? '') . ' ' . 
                                    ($o['box_count'] ?? '')
                                );
                            ?>
                            <tr class="outward-row" data-search-text="<?= e($searchData) ?>">
                                <td>
                                    <div class="fw-bold text-primary font-monospace"><?= e($o['outward_number']) ?></div>
                                    <small class="text-muted">Dispatched by <?= e($o['created_by_name'] ?? 'Staff') ?></small>
                                </td>
                                <td>
                                    <code class="fw-bold text-dark"><?= e($o['batch_number']) ?></code>
                                    <?php if (!empty($o['sale_bill_id'])): ?>
                                        <div class="mt-1"><span class="badge bg-success-subtle text-success border border-success-subtle font-monospace"><i class="bi bi-receipt me-1"></i><?= e($o['sale_bill_number']) ?> · ₹<?= number_format((float)($o['sale_bill_total'] ?? 0), 2) ?></span></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($o['product_name']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($o['destination_concern_name'] ?: $o['warehouse_name'] ?: 'Unassigned') ?></span></td>
                                <td class="text-success fw-bold"><?= number_format($o['total_qty']) ?> pcs (<?= $o['box_count'] ?> <?= $o['box_count'] == 1 ? 'box' : 'boxes' ?>)</td>
                                <td><span class="badge bg-dark font-monospace"><?= e($o['vehicle_number'] ?: 'Direct Handover') ?></span></td>
                                <td><?= formatDateTime($o['dispatch_date']) ?></td>
                                <td>
                                    <?php $outwardStatus = $o['status'] ?? 'DISPATCHED'; ?>
                                    <span class="badge <?= $outwardStatus === 'RECEIVED' ? 'bg-success' : ($outwardStatus === 'DISCREPANCY' ? 'bg-warning text-dark' : 'bg-primary') ?> px-2 py-1"><i class="bi <?= $outwardStatus === 'RECEIVED' ? 'bi-check-all' : ($outwardStatus === 'DISCREPANCY' ? 'bi-exclamation-triangle' : 'bi-truck') ?> me-1"></i><?= e($outwardStatus) ?></span>
                                    <?php if (!empty($o['received_date'])): ?><small class="d-block text-muted mt-1">Received <?= e(formatDateTime($o['received_date'])) ?></small><?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                        <?php if (!empty($o['sale_bill_id']) && !empty($o['production_batch_id'])): ?>
                                            <a href="<?= url('production/batches/sale-bill/' . (int)$o['production_batch_id']) ?>" class="btn btn-sm btn-outline-success fw-semibold" title="View Batch Sale Bill">
                                                <i class="bi bi-receipt me-1"></i>Sale Bill
                                            </a>
                                            <a href="<?= url('production/batches/sale-bill/print/' . (int)$o['production_batch_id']) ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Print Batch Sale Bill">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($o['production_batch_id']) && hasPermission('batches', 'print')): ?>
                                            <a href="<?= url('production/batches/label/' . (int)$o['production_batch_id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary fw-semibold" title="Print the QR label for receiving this outward batch">
                                                <i class="bi bi-qr-code me-1"></i> Batch Label
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="viewOutwardDispatchDetails(<?= $o['id'] ?>)" title="View Outward & Product Breakdown">
                                            <i class="bi bi-eye me-1"></i> View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Pagination Controls Footer -->
    <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="outwardPaginationFooter">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="outwardPageInfo">
                Showing 1 to 10 of <?= count($outwards) ?> records
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="outwardPageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                    <option value="10" selected>10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <nav aria-label="Outward Pagination">
            <ul class="pagination pagination-modern mb-0" id="outwardPaginationList">
            </ul>
        </nav>
    </div>
</div>

<!-- Modal: View Outward Dispatch Breakdown -->
<div class="modal fade" id="viewOutwardDispatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-truck me-2 text-primary"></i>Outward Dispatch Breakdown
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center py-4 text-muted" id="vodLoadingSpinner">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div>Loading outward dispatch breakdown...</div>
                </div>
                <div id="vodContent" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-3 bg-light border mb-4 flex-wrap gap-2">
                        <div>
                            <span class="text-uppercase small text-muted fw-bold d-block">Outward Document #</span>
                            <span class="fs-5 fw-bold text-primary font-monospace" id="vodOutwardNumber">—</span>
                        </div>
                        <div>
                            <span class="badge bg-success px-3 py-2 fs-7" id="vodStatusBadge">DISPATCHED</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="small fw-bold text-muted text-uppercase mb-2"><i class="bi bi-box-seam me-1 text-primary"></i> Batch &amp; Product</div>
                                <div class="mb-1"><strong>Batch #:</strong> <span class="font-monospace text-dark fw-bold" id="vodBatchNumber">—</span></div>
                                <div class="mb-1"><strong>Product:</strong> <span class="text-dark fw-bold" id="vodProductName">—</span></div>
                                <div class="mb-1"><strong>Program:</strong> <span class="text-primary fw-bold" id="vodProgramNumber">—</span></div>
                                <div><strong>Destination:</strong> <span class="badge bg-primary-subtle text-primary border" id="vodWarehouseName">—</span></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="small fw-bold text-muted text-uppercase mb-2"><i class="bi bi-truck me-1 text-primary"></i> Transport &amp; Vehicle</div>
                                <div class="mb-1"><strong>Vehicle / Courier #:</strong> <span class="badge bg-dark font-monospace" id="vodVehicleNumber">—</span></div>
                                <div class="mb-1"><strong>Driver:</strong> <span class="text-dark" id="vodDriverName">—</span></div>
                                <div class="mb-1"><strong>Driver Contact:</strong> <span class="text-dark" id="vodDriverContact">—</span></div>
                                <div class="mb-1"><strong>Transporter:</strong> <span class="text-dark" id="vodTransporter">—</span></div>
                                <div class="mb-1"><strong>LR / Tracking:</strong> <span class="text-dark" id="vodLRNumber">—</span></div>
                                <div class="mb-1"><strong>Dispatch Date:</strong> <span class="text-dark" id="vodDispatchDate">—</span></div>
                                <div class="mb-1"><strong>Dispatched By:</strong> <span class="text-dark" id="vodDispatchedBy">—</span></div>
                                <div class="mb-1"><strong>Received By:</strong> <span class="text-dark" id="vodReceivedBy">—</span></div>
                                <div><strong>Total Dispatched:</strong> <span class="text-success fw-bold" id="vodTotalQty">—</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4" id="vodNotesContainer" style="display: none;">
                        <div class="small fw-bold text-muted mb-1">Dispatch Notes / Gate Pass:</div>
                        <div class="p-2 bg-white border rounded small text-dark" id="vodNotes"></div>
                    </div>

                    <div class="alert alert-warning mb-4" id="vodDiscrepancyContainer" style="display: none;">
                        <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Receipt discrepancy reported by receiving concern</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0 bg-white">
                                <thead class="table-light"><tr><th>SKU / Variant</th><th>Dispatched</th><th>Received</th><th>Missing QR codes</th><th>Extra QR codes</th></tr></thead>
                                <tbody id="vodDiscrepancyTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h6 class="fw-bold mb-2"><i class="bi bi-layers-fill text-primary me-1"></i> Dispatched Variant Items Breakdown</h6>
                        <div class="table-responsive border rounded-3">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>SKU / Variant</th>
                                        <th>Size</th>
                                        <th>Color</th>
                                        <th class="text-end">Quantity (pcs)</th>
                                        <th>Outward Piece QR Codes</th>
                                    </tr>
                                </thead>
                                <tbody id="vodItemsTableBody">
                                    <!-- Populated via Ajax -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 justify-content-between">
                <div id="vodSaleBillAction"></div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Outward Dispatch -->
<?php if (!empty($approvedBatches)): ?>
<div class="modal fade" id="newOutwardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <form method="POST" action="<?= url('production/outward/store') ?>" id="outwardDispatchForm">
                <?= CSRF::field() ?>
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-truck me-2 text-primary"></i>Dispatch Production Batch</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Dynamic Batch Summary Preview -->
                    <div class="mb-3 p-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3" id="modalBatchPreview">
                        <div class="small text-muted fw-semibold text-uppercase">Selected Batch for Dispatch</div>
                        <div class="fw-bold text-dark fs-6" id="modalBatchTitle">Select a batch below</div>
                        <div class="small text-muted mt-1" id="modalBatchDetails">-</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold required">Select Approved Batch <span class="text-danger">*</span></label>
                        <select name="production_batch_id" id="modal_batch_id" class="form-select fw-medium" required onchange="updateModalBatchSummary(this.value)">
                            <option value="">-- Select approved batch --</option>
                            <?php foreach ($approvedBatches as $ab): ?>
                                <option value="<?= $ab['id'] ?>" 
                                        data-batch-number="<?= e($ab['batch_number']) ?>"
                                        data-product="<?= e($ab['product_name']) ?>"
                                        data-warehouse="<?= e($ab['destination_concern_name'] ?: $ab['warehouse_name'] ?: 'Unassigned') ?>"
                                        data-qty="<?= $ab['total_qty'] ?>"
                                        data-boxes="<?= $ab['box_count'] ?>">
                                    <?= e($ab['batch_number']) ?> — <?= e($ab['product_name']) ?> (<?= number_format($ab['total_qty']) ?> pcs &rarr; <?= e($ab['destination_concern_name'] ?: $ab['warehouse_name'] ?: 'Unassigned') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold required">Vehicle / Transport Number <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_number" id="vehicle_number" class="form-control text-uppercase fw-semibold" placeholder="e.g. TN-38-AB-1234 or Courier Reference" required>
                        <div class="form-text small text-muted">Vehicle plate number or courier consignment tracking number.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Driver Name <span class="text-danger">*</span></label><input type="text" name="driver_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Driver Contact <span class="text-danger">*</span></label><input type="text" name="driver_contact" class="form-control" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" required></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Transporter / Logistics <span class="text-danger">*</span></label><input type="text" name="transporter_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Total Boxes / Cartons <span class="text-danger">*</span></label><input type="number" id="outward_total_boxes" name="total_boxes" class="form-control" value="1" min="1" required></div>
                    </div>

                    <div class="mb-3"><label class="form-label fw-semibold">LR / Docket / Tracking Number</label><input type="text" name="lr_number" class="form-control" placeholder="Optional courier or lorry receipt reference"></div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dispatch Date &amp; Time</label>
                        <input type="datetime-local" name="dispatch_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Delivery / Dispatch Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Driver contact, delivery challan reference, gate pass #..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4"><i class="bi bi-check2-circle me-1"></i> Confirm Dispatch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.approvedBatchesData = <?= json_encode($approvedBatches, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

function openDispatchModal(batchData) {
    const modalEl = document.getElementById('newOutwardModal');
    if (!modalEl) return;

    const selectEl = document.getElementById('modal_batch_id');
    const vehicleInput = document.getElementById('vehicle_number');

    if (batchData && batchData.id) {
        if (selectEl) selectEl.value = batchData.id;
        updateModalBatchSummary(batchData.id);
    } else if (selectEl) {
        selectEl.value = '';
        updateModalBatchSummary('');
    }

    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    setTimeout(() => {
        if (vehicleInput) vehicleInput.focus();
    }, 400);
}

function updateModalBatchSummary(batchId) {
    const titleEl = document.getElementById('modalBatchTitle');
    const detailsEl = document.getElementById('modalBatchDetails');
    if (!titleEl || !detailsEl) return;

    const batch = window.approvedBatchesData.find(b => b.id == batchId);
    if (batch) {
        titleEl.textContent = `${batch.batch_number} — ${batch.product_name}`;
        detailsEl.textContent = `Destination: ${batch.destination_concern_name || batch.warehouse_name || 'Unassigned'} | Quantity: ${parseInt(batch.total_qty).toLocaleString()} pcs (${batch.box_count} ${batch.box_count == 1 ? 'box' : 'boxes'})`;
        const boxInput = document.getElementById('outward_total_boxes');
        if (boxInput) boxInput.value = Math.max(1, parseInt(batch.box_count || 1));
    } else {
        titleEl.textContent = 'Select a batch below';
        detailsEl.textContent = '-';
        const boxInput = document.getElementById('outward_total_boxes');
        if (boxInput) boxInput.value = 1;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('outwardDispatchForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const batchSelect = document.getElementById('modal_batch_id');
            const vehicleInput = document.getElementById('vehicle_number');

            if (!batchSelect || !batchSelect.value) {
                e.preventDefault();
                alert('Please select an approved batch to dispatch.');
                if (batchSelect) batchSelect.focus();
                return false;
            }

            if (!vehicleInput || !vehicleInput.value.trim()) {
                e.preventDefault();
                alert('Please enter Vehicle / Transport Number or Courier reference before dispatching.');
                if (vehicleInput) {
                    vehicleInput.classList.add('is-invalid', 'border-danger');
                    vehicleInput.focus();
                }
                return false;
            } else {
                vehicleInput.classList.remove('is-invalid', 'border-danger');
            }

            return true;
        });
    }
});
</script>
<?php endif; ?>

<script>
function viewOutwardDispatchDetails(outwardId) {
    const modalEl = document.getElementById('viewOutwardDispatchModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    const spinner = document.getElementById('vodLoadingSpinner');
    const content = document.getElementById('vodContent');
    if (spinner) spinner.style.display = 'block';
    if (content) content.style.display = 'none';

    fetch('<?= url("production/outward/view-ajax/") ?>' + outwardId)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Error loading outward dispatch details.');
                modal.hide();
                return;
            }

            const outward = data.outward || {};
            const batch = data.batch || {};
            const items = data.items || [];
            const discrepancies = data.discrepancies || [];
            const outwardStatus = outward.status || 'DISPATCHED';

            document.getElementById('vodOutwardNumber').textContent = outward.outward_number || ('Outward #' + outward.id);
            const statusBadge = document.getElementById('vodStatusBadge');
            statusBadge.textContent = outwardStatus;
            statusBadge.className = `badge px-3 py-2 fs-7 ${outwardStatus === 'RECEIVED' ? 'bg-success' : (outwardStatus === 'DISCREPANCY' ? 'bg-warning text-dark' : 'bg-primary')}`;
            document.getElementById('vodBatchNumber').textContent = batch.batch_number || outward.batch_number || '—';
            document.getElementById('vodProductName').textContent = batch.product_name || outward.product_name || 'Finished Product';
            document.getElementById('vodProgramNumber').textContent = batch.program_number || outward.program_number || ((batch.source_type || outward.source_type) === 'SCAN' ? 'QR Scan Batch' : 'Inwarded Stock');
            const saleBatchId = batch.id || outward.production_batch_id;
            const saleBillId = batch.sale_bill_id || outward.sale_bill_id;
            const saleBillNum = batch.sale_bill_number || outward.sale_bill_number || '';
            const saleAction = document.getElementById('vodSaleBillAction');
            if (saleAction) {
                if (saleBillId && saleBatchId) {
                    const viewHref = '<?= url("production/batches/sale-bill") ?>/' + saleBatchId;
                    const printHref = '<?= url("production/batches/sale-bill/print") ?>/' + saleBatchId;
                    saleAction.innerHTML = `<div class="d-inline-flex gap-2">
                        <a href="${viewHref}" class="btn btn-sm btn-success"><i class="bi bi-receipt me-1"></i>Sale Bill ${escapeHtml(saleBillNum)}</a>
                        <a href="${printHref}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-printer me-1"></i>Print Sale Bill</a>
                    </div>`;
                } else {
                    saleAction.innerHTML = '';
                }
            }
            document.getElementById('vodWarehouseName').textContent = batch.destination_concern_name || outward.destination_concern_name || batch.warehouse_name || outward.warehouse_name || 'Unassigned';

            const totalQty = parseInt(outward.total_qty || batch.total_qty || 0);
            const boxCount = parseInt(outward.box_count || batch.box_count || 1);
            document.getElementById('vodTotalQty').textContent = totalQty.toLocaleString() + ' pcs (' + boxCount + ' ' + (boxCount === 1 ? 'box' : 'boxes') + ')';

            document.getElementById('vodVehicleNumber').textContent = outward.vehicle_number || 'Direct Handover';
            document.getElementById('vodDriverName').textContent = outward.driver_name || '—';
            document.getElementById('vodDriverContact').textContent = outward.driver_contact || '—';
            document.getElementById('vodTransporter').textContent = outward.transporter_name || '—';
            document.getElementById('vodLRNumber').textContent = outward.lr_number || '—';
            document.getElementById('vodDispatchDate').textContent = outward.dispatch_date || '—';
            document.getElementById('vodDispatchedBy').textContent = outward.dispatched_by_name || outward.created_by_name || 'Staff';
            document.getElementById('vodReceivedBy').textContent = outward.received_by_name || (outward.received_date ? outward.received_date : 'Not received');

            // Notes
            const notes = outward.notes || '';
            const notesContainer = document.getElementById('vodNotesContainer');
            const notesEl = document.getElementById('vodNotes');
            if (notes.trim()) {
                notesEl.textContent = notes;
                notesContainer.style.display = 'block';
            } else {
                notesContainer.style.display = 'none';
            }

            const discrepancyContainer = document.getElementById('vodDiscrepancyContainer');
            const discrepancyBody = document.getElementById('vodDiscrepancyTableBody');
            discrepancyBody.replaceChildren();
            if (discrepancies.some(item => Number(item.missing_qty || 0) > 0 || Number(item.excess_qty || 0) > 0 || Number(item.damaged_qty || 0) > 0)) {
                discrepancies.forEach(item => {
                    if (Number(item.missing_qty || 0) === 0 && Number(item.excess_qty || 0) === 0 && Number(item.damaged_qty || 0) === 0) return;
                    const row = discrepancyBody.insertRow();
                    const variant = row.insertCell();
                    variant.textContent = [item.sku || '—', item.product_name, item.size_name, item.color_name].filter(Boolean).join(' · ');
                    const dispatched = row.insertCell();
                    dispatched.textContent = Number(item.dispatched_qty || 0).toLocaleString();
                    const received = row.insertCell();
                    received.textContent = `${(Number(item.quantity || 0) + Number(item.damaged_qty || 0)).toLocaleString()} (${Number(item.damaged_qty || 0).toLocaleString()} damaged)`;
                    const missing = row.insertCell();
                    missing.textContent = item.missing_codes || (Number(item.missing_qty || 0) ? `${Number(item.missing_qty).toLocaleString()} QR code(s) not identified` : '—');
                    const extra = row.insertCell();
                    extra.textContent = item.excess_codes || '—';
                });
                discrepancyContainer.style.display = 'block';
            } else {
                discrepancyContainer.style.display = 'none';
            }

            // Variant Items Breakdown
            const tbody = document.getElementById('vodItemsTableBody');
            tbody.innerHTML = '';
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No individual variant breakdown records found.</td></tr>';
            } else {
                items.forEach((it, idx) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="text-muted small">${idx + 1}</td>
                        <td class="font-monospace fw-bold text-dark">${escapeHtml(it.sku || it.product_sku || '—')}</td>
                        <td><span class="badge bg-secondary-subtle text-secondary border font-monospace">${escapeHtml(it.size_name || 'Std')}</span></td>
                        <td><span class="badge bg-info-subtle text-info border font-monospace">${escapeHtml(it.color_name || 'Std')}</span></td>
                        <td class="text-end fw-bold text-success">${parseInt(it.quantity || 0).toLocaleString()} pcs</td>
                        <td><div class="d-flex flex-wrap gap-2 vod-piece-qrs"></div></td>
                    `;
                    tbody.appendChild(row);

                    const qrList = row.querySelector('.vod-piece-qrs');
                    const pieceTags = Array.isArray(it.outward_piece_tags) ? it.outward_piece_tags : [];
                    if (!pieceTags.length) {
                        qrList.innerHTML = '<span class="small text-muted">Individual piece QR tags are unavailable.</span>';
                        return;
                    }

                    pieceTags.forEach((tag, pieceIndex) => {
                        const pieceCard = document.createElement('div');
                        pieceCard.className = 'border rounded-2 p-1 text-center bg-white';
                        pieceCard.style.width = '76px';
                        pieceCard.title = `Piece ${pieceIndex + 1}: ${tag}`;

                        const qr = document.createElement('div');
                        qr.className = 'vod-piece-qr mx-auto';
                        qr.setAttribute('aria-label', `QR code for ${tag}`);
                        const code = document.createElement('div');
                        code.className = 'font-monospace text-dark text-break mt-1';
                        code.style.fontSize = '0.62rem';
                        code.textContent = tag;
                        pieceCard.append(qr, code);
                        qrList.appendChild(pieceCard);

                        if (typeof QRCode === 'undefined') {
                            qr.textContent = 'QR unavailable';
                            qr.className += ' small text-danger';
                            return;
                        }
                        new QRCode(qr, {
                            text: tag,
                            width: 60,
                            height: 60,
                            correctLevel: QRCode.CorrectLevel.M
                        });
                    });
                });
            }

            if (spinner) spinner.style.display = 'none';
            if (content) content.style.display = 'block';
        })
        .catch(err => {
            console.error(err);
            alert('Failed to fetch outward dispatch breakdown.');
            modal.hide();
        });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

let outwardPagination;
function initOutwardPagination() {
    const searchInput = document.getElementById('outwardSearchInput');
    const tableBody = document.getElementById('outwardTableBody');
    if (!tableBody) return;

    if (typeof window.initClientPagination === 'function') {
        outwardPagination = window.initClientPagination({
            container: '#outwardTableBody',
            itemSelector: 'tr.outward-row',
            footer: '#outwardPaginationFooter',
            infoEl: '#outwardPageInfo',
            paginationList: '#outwardPaginationList',
            pageSizeSelect: '#outwardPageSizeSelect',
            emptyStateEl: '#noLiveOutwardResultsRow',
            pageSize: 10,
            itemName: 'records'
        });
    }

    if (searchInput) {
        function filterRows() {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;
            const rows = tableBody.querySelectorAll('.outward-row');
            const totalCount = rows.length;

            if (outwardPagination) {
                visibleCount = outwardPagination.filter(row => {
                    if (!query) return true;
                    const searchText = (row.getAttribute('data-search-text') || row.textContent || '').toLowerCase();
                    return searchText.includes(query);
                });
            } else {
                rows.forEach(function(row) {
                    const searchText = (row.getAttribute('data-search-text') || row.textContent || '').toLowerCase();
                    if (!query || searchText.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            let noResultRow = document.getElementById('noLiveOutwardResultsRow');
            if (totalCount > 0 && visibleCount === 0 && query !== '') {
                if (!noResultRow) {
                    noResultRow = document.createElement('tr');
                    noResultRow.id = 'noLiveOutwardResultsRow';
                    noResultRow.innerHTML = `<td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                        <h6 class="fw-bold text-secondary">No outward dispatches matching "${escapeHtml(searchInput.value)}"</h6>
                        <small class="text-muted d-block mb-2">Try searching with a different keyword or clear the search filter.</small>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="clearOutwardSearchLive()">Clear Search</button>
                    </td>`;
                    tableBody.appendChild(noResultRow);
                } else {
                    noResultRow.style.display = '';
                    const h6 = noResultRow.querySelector('h6');
                    if (h6) h6.textContent = `No outward dispatches matching "${searchInput.value}"`;
                }
            } else if (noResultRow) {
                noResultRow.style.display = 'none';
            }
        }

        window.clearOutwardSearchLive = function() {
            if (searchInput) {
                searchInput.value = '';
                filterRows();
            }
        };

        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(filterRows, 150);
        });

        if (searchInput.value.trim() !== '') {
            filterRows();
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOutwardPagination);
} else {
    initOutwardPagination();
}
</script>
