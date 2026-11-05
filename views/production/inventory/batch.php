<?php
// views/production/inventory/batch.php
// Piece-by-piece detail for a single QC batch with QR Code search, filtering & label printing
$batchDate = new DateTime($batchInfo['batch_created_at'] ?? 'now');
?>

<!-- QR Code Library CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #qrPrintModal, #qrPrintModal * {
        visibility: visible !important;
    }
    #qrPrintModal {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
    }
    .modal-dialog {
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
    }
    .modal-content {
        border: none !important;
        box-shadow: none !important;
    }
    .modal-header, .modal-footer, .no-print, .btn-close {
        display: none !important;
    }
    .modal-body {
        padding: 0 !important;
    }
    .qr-sticker-card {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border: 1px solid #000 !important;
        margin-bottom: 8px !important;
    }
}

.qr-sticker-card {
    border-radius: 6px;
    background: #fff;
    border: 1px solid #dee2e6;
    transition: all 0.2s ease;
}
.qr-sticker-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}
.lbl-title {
    font-size: 8px;
    font-weight: 800;
    color: #111;
    letter-spacing: 0.5px;
    line-height: 1.1;
}
.lbl-sku {
    font-size: 8.5px;
    font-family: monospace;
    color: #222;
    line-height: 1.1;
}
.lbl-damage-code {
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.3px;
    color: #111;
}
.lbl-mrp {
    font-size: 7.5px;
    color: #777;
    text-decoration: line-through;
    line-height: 1;
}
.lbl-disc {
    font-size: 9px;
    color: #0d6efd;
    line-height: 1.1;
}
</style>

<!-- Page Header -->
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-3 mb-1">
            <a href="<?= url('production/inventory') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Batches
            </a>
            <h2 class="mb-0">
                <i class="bi bi-patch-check-fill me-2 text-success"></i>QC Batch #<?= $qcBatchId ?> — Inventory
            </h2>
        </div>
        <span class="text-muted">
            <?= e($batchInfo['product_name']) ?> &nbsp;·&nbsp;
            Program: <strong><?= e($batchInfo['program_number']) ?></strong> &nbsp;·&nbsp;
            <i class="bi bi-calendar3 me-1"></i><?= $batchDate->format('d M Y, g:i A') ?>
        </span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-primary" onclick="printAllBatchLabels()">
            <i class="bi bi-printer me-1"></i> Print Batch Labels
        </button>
        <a href="<?= url('production/qc/inspect/' . $qcBatchId) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-clipboard-check me-1"></i> View QC Inspection
        </a>
        <?php if ($remainingPieces > 0 && hasPermission('batches', 'create')): ?>
        <a href="<?= url('production/batches/create?qc_batch_id=' . $qcBatchId) ?>" class="btn btn-success">
            <i class="bi bi-box-seam me-1"></i> Pack into Box Batch (<?= number_format($remainingPieces) ?> pcs)
        </a>
        <?php elseif ($remainingPieces == 0): ?>
        <a href="<?= url('production/batches?search=' . urlencode($batchInfo['program_number'] ?? '')) ?>" class="btn btn-outline-secondary" title="All pieces from this batch have already been packed into finished boxes">
            <i class="bi bi-check2-all me-1"></i> Fully Packed &amp; Dispatched
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Batch KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-success bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">In-Stock Remaining</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($remainingPieces) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted d-block mt-1">Available in production unit</small>
                </div>
                <div class="bg-success text-white rounded-3 p-3">
                    <i class="bi bi-box-seam-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-danger bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Outward Dispatched</span>
                    <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($totalOutwardPieces) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted d-block mt-1">Dispatched to warehouses</small>
                </div>
                <div class="bg-danger text-white rounded-3 p-3">
                    <i class="bi bi-truck fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-info bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Total QC Passed</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($totalPieces) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted d-block mt-1"><?= count($rawItems) ?> product variants</small>
                </div>
                <div class="bg-info text-dark rounded-3 p-3">
                    <i class="bi bi-patch-check-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-primary bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">In-Stock Valuation</span>
                    <h3 class="fw-bold text-primary mb-0 mt-1">₹<?= number_format($remainingValue, 2) ?></h3>
                    <small class="text-muted d-block mt-1">Total: ₹<?= number_format($totalValue, 2) ?></small>
                </div>
                <div class="bg-primary text-white rounded-3 p-3">
                    <i class="bi bi-currency-rupee fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Outward Dispatch History & Movement Log -->
<div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-danger bg-opacity-10 text-danger rounded p-2 text-center" style="width: 36px; height: 36px;">
                <i class="bi bi-truck fs-5"></i>
            </div>
            <div>
                <h5 class="card-title mb-0 fw-bold text-dark">Outward Dispatch History &amp; Inventory Movement</h5>
                <small class="text-muted">Track all product batches dispatched outward from production unit to central warehouses</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fw-bold">
                <?= count($outwards) ?> Outward Dispatch(es) &nbsp;·&nbsp; <?= number_format($totalOutwardPieces) ?> pcs dispatched
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($outwards)): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Outward #</th>
                        <th>Packing Batch</th>
                        <th>Destination Warehouse</th>
                        <th>Dispatched Qty</th>
                        <th>Boxes</th>
                        <th>Transport / Vehicle</th>
                        <th>Dispatch Date</th>
                        <th>Status</th>
                        <th class="text-end">Delivery Challan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($outwards as $ow): ?>
                    <tr>
                        <td>
                            <span class="fw-bold text-primary font-monospace">
                                <i class="bi bi-file-earmark-text me-1"></i><?= e($ow['outward_number']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-box-seam me-1 text-secondary"></i><?= e($ow['batch_number'] ?? 'Batch') ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><?= e($ow['warehouse_name'] ?? 'Main Warehouse') ?></div>
                            <?php if (!empty($ow['warehouse_code'])): ?>
                                <small class="text-muted"><code><?= e($ow['warehouse_code']) ?></code></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="fw-bold text-danger fs-6">
                                <?= number_format($ow['outward_qty']) ?> pcs
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border">
                                <?= (int)($ow['total_boxes'] ?? $ow['batch_box_count'] ?? 1) ?> Boxes
                            </span>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark">
                                <i class="bi bi-truck me-1 text-muted"></i><?= e($ow['vehicle_number'] ?: 'N/A') ?>
                            </div>
                            <?php if (!empty($ow['driver_name']) || !empty($ow['transporter_name'])): ?>
                                <small class="text-muted d-block" style="font-size: 11px;">
                                    <?= e($ow['transporter_name'] ?: '') ?><?= (!empty($ow['transporter_name']) && !empty($ow['driver_name'])) ? ' · ' : '' ?><?= e($ow['driver_name'] ? 'Driver: ' . $ow['driver_name'] : '') ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="small text-dark">
                                <?= !empty($ow['dispatch_date']) ? (new DateTime($ow['dispatch_date']))->format('d M Y, g:i A') : 'N/A' ?>
                            </div>
                            <?php if (!empty($ow['dispatched_by_name'])): ?>
                                <small class="text-muted" style="font-size: 11px;">By: <?= e($ow['dispatched_by_name']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($ow['status'] ?? '') === 'RECEIVED'): ?>
                                <span class="badge bg-success"><i class="bi bi-check2-all me-1"></i>RECEIVED</span>
                            <?php else: ?>
                                <span class="badge bg-primary"><i class="bi bi-truck me-1"></i>DISPATCHED</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('production/outward') ?>" class="btn btn-sm btn-outline-primary" title="View in Outward Management">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Outward Pass
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="p-4 text-center text-muted">
            <i class="bi bi-info-circle fs-3 d-block mb-2 text-info"></i>
            <h6 class="fw-semibold text-dark mb-1">No Outward Dispatches Yet</h6>
            <p class="small text-muted mb-0">100% of finished pieces in this QC batch (<strong><?= number_format($totalPieces) ?> pcs</strong>) are currently available in production unit inventory.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Fast 2D QR Code Search & Filter Bar -->
<div class="card shadow-sm mb-4 border-primary">
    <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold fs-7"><i class="bi bi-qr-code-scan me-2"></i>Garment QR Code Search &amp; Quick Label Print</span>
        <span class="badge bg-white text-primary fw-bold fs-8" id="searchMatchBadge">Showing all <?= count($pieceItems) ?> pieces</span>
    </div>
    <div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <div class="col-lg-7 col-md-6">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light border-primary"><i class="bi bi-upc-scan text-primary fs-5"></i></span>
                    <input type="text" id="qrInventorySearch" class="form-control border-primary fw-bold" 
                           placeholder="Search or Scan QR sticker tag (e.g. <?= $pieceItems[0]['tag_code'] ?? 'MFD92601' ?>)..." 
                           autofocus oninput="filterInventoryPieces()" onkeyup="if(event.key==='Enter') filterInventoryPieces()">
                    <button type="button" class="btn btn-outline-secondary" onclick="clearInventorySearch()" title="Clear Search">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="col-lg-5 col-md-6 d-flex gap-2 justify-content-md-end flex-wrap">
                <button type="button" class="btn btn-primary btn-lg fw-semibold shadow-sm flex-grow-1 flex-md-grow-0" id="btnPrintFiltered" onclick="printFilteredLabels()">
                    <i class="bi bi-printer-fill me-1"></i> Print Filtered Labels (<span id="filteredCountLabel"><?= count($pieceItems) ?></span>)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Items Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-table text-primary"></i>
                Post-QC Approved Finished Garments
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fs-7 px-2" id="headerCountBadge">
                    <?= $remainingPieces ?> In Stock / <?= count($pieceItems) ?> Total Pieces
                </span>
            </h5>
            <span class="text-muted small">
                <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
                Piece-by-piece listing for QC Batch #<?= $qcBatchId ?> (<?= $remainingPieces ?> pcs remaining in stock)
            </span>
        </div>
        <!-- View Mode Switch -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary active fw-semibold" id="btnPieceView" onclick="switchView('piece')">
                    <i class="bi bi-list-check me-1"></i> Each Piece (<?= count($pieceItems) ?>)
                </button>
                <button type="button" class="btn btn-outline-secondary fw-semibold" id="btnGroupView" onclick="switchView('group')">
                    <i class="bi bi-layers-fill me-1"></i> By Variant (<?= count($rawItems) ?>)
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-4">

        <!-- 1. Piece-by-Piece Table View (Default View Matching Image 2) -->
        <div class="table-responsive" id="pieceViewContainer" style="display: block;">
            <table class="table table-hover align-middle mb-0" id="pieceInventoryTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">Piece #</th>
                        <th>Garment Tag / Barcode</th>
                        <th>Product &amp; Category</th>
                        <th>Variant (Size / Color)</th>
                        <th>QC Batch &amp; Program</th>
                        <th>Price (Offer / Base)</th>
                        <th>Inventory &amp; QC Status</th>
                        <th class="text-end" style="min-width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Empty Search State Row -->
                    <tr id="emptyPieceSearchRow" style="display: none;">
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-search fs-1 d-block mb-2 text-secondary"></i>
                            <h6 class="fw-semibold mb-1">No Matching Garment Tag Found</h6>
                            <p class="text-muted small mb-0">No piece in this batch matches the scanned or searched QR code.</p>
                        </td>
                    </tr>

                    <?php if (empty($pieceItems)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-3 text-secondary"></i>
                                <h6 class="fw-semibold mb-1">No Passed Items Found</h6>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pieceItems as $it): ?>
                            <tr class="inventory-piece-row" 
                                id="rowPiece_<?= $it['piece_no'] ?>"
                                data-piece-no="<?= $it['piece_no'] ?>"
                                data-tag="<?= strtoupper($it['tag_code']) ?>"
                                data-sku="<?= strtoupper($it['product_sku']) ?>"
                                data-size="<?= strtoupper($it['size_name']) ?>"
                                data-color="<?= strtoupper($it['color_name']) ?>"
                                data-product="<?= strtoupper($it['product_name']) ?>">
                                <td class="text-muted small fw-bold">#<?= $it['piece_no'] ?></td>
                                <td>
                                    <span class="badge bg-dark text-white font-monospace fs-7 px-2 py-1">
                                        <i class="bi bi-qr-code-scan me-1 text-warning"></i><?= e($it['tag_code']) ?>
                                    </span>
                                    <div class="small text-muted mt-1">Garment #<?= $it['piece_seq'] ?> of <?= $it['passed_in_group'] ?></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded p-2 text-center" style="width: 38px; height: 38px;">
                                            <i class="bi bi-bag-check fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= e($it['product_name']) ?></div>
                                            <?php if (!empty($it['category_name'])): ?>
                                                <div class="mt-1">
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-7"><?= e($it['category_name']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-secondary-subtle text-secondary border font-monospace px-2 py-1">Size: <?= e($it['size_name']) ?></span>
                                        <span class="badge bg-info-subtle text-info border font-monospace px-2 py-1">Color: <?= e($it['color_name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <a href="<?= url('production/qc/inspect/' . $it['qc_batch_id']) ?>" class="fw-semibold text-decoration-none">
                                            QC Batch #<?= $it['qc_batch_id'] ?>
                                        </a>
                                    </div>
                                    <small class="text-muted">
                                        Program: <a href="<?= url('production/programs/view/' . $it['program_id']) ?>" class="text-decoration-none"><?= e($it['program_number']) ?></a>
                                    </small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">₹<?= number_format($it['discount_price'] > 0 ? $it['discount_price'] : $it['base_price'], 2) ?></div>
                                    <?php if ($it['discount_price'] > 0 && $it['base_price'] > $it['discount_price']): ?>
                                        <small class="text-muted text-decoration-line-through">₹<?= number_format($it['base_price'], 2) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($it['is_dispatched'])): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 d-block mb-1">
                                            <i class="bi bi-truck me-1"></i>DISPATCHED
                                        </span>
                                        <small class="text-muted font-monospace d-block" style="font-size: 11px;"><?= e($it['outward_info']) ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 d-block mb-1">
                                            <i class="bi bi-check2-circle me-1"></i>IN STOCK
                                        </span>
                                        <small class="text-success" style="font-size: 11px;"><i class="bi bi-shield-check me-1"></i>Available</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="printSinglePieceLabel(<?= $it['piece_no'] ?>)" title="Print QR Label">
                                            <i class="bi bi-printer me-1"></i>Print
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0)" onclick="printSinglePieceLabel(<?= $it['piece_no'] ?>)">
                                                        <i class="bi bi-printer me-2 text-primary"></i>Print QR Label
                                                    </a>
                                                </li>
                                                <?php if (empty($it['is_dispatched'])): ?>
                                                <li>
                                                    <a class="dropdown-item text-success" href="<?= url('production/batches/create?qc_batch_id=' . $it['qc_batch_id']) ?>">
                                                        <i class="bi bi-box-seam me-2"></i>Pack into Box Batch
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                                <li>
                                                    <a class="dropdown-item" href="<?= url('production/qc/inspect/' . $it['qc_batch_id']) ?>">
                                                        <i class="bi bi-eye me-2"></i>View QC Inspection
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls Footer for Piece View -->
        <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="piecePaginationFooter">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="small text-muted fw-medium" id="pageEntriesInfo">
                    Showing 1 to 10 of <?= count($pieceItems) ?> pieces
                </span>
                <div class="d-flex align-items-center gap-2">
                    <label for="pageSizeSelect" class="small text-muted mb-0 fw-medium">Show:</label>
                    <select id="pageSizeSelect" class="form-select form-select-sm" style="width: auto;" onchange="changePageSize(this.value)">
                        <option value="10" selected>10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                        <option value="all">All pieces</option>
                    </select>
                </div>
            </div>
            <nav aria-label="Inventory Pieces Pagination">
                <ul class="pagination pagination-sm mb-0 shadow-xs" id="inventoryPaginationList">
                    <!-- Dynamically rendered pagination buttons -->
                </ul>
            </nav>
        </div>

        <!-- 2. Grouped Variant View -->
        <div class="table-responsive" id="groupViewContainer" style="display: none;">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Product &amp; Category</th>
                        <th>Variant (Size / Color)</th>
                        <th>Total Passed</th>
                        <th>Price (Base / Offer)</th>
                        <th>QC Status</th>
                        <th class="text-end" style="min-width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rawItems as $idx => $it): ?>
                        <tr>
                            <td class="text-muted small fw-medium"><?= $idx + 1 ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2 text-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-bag-check fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= e($it['product_name']) ?></div>
                                        <?php if (!empty($it['category_name'])): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-7 mt-1"><?= e($it['category_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-dark text-white font-monospace px-2 py-1">Size: <?= e($it['size_name']) ?></span>
                                    <span class="badge bg-info text-dark font-monospace px-2 py-1">Color: <?= e($it['color_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-success fs-6">
                                    <i class="bi bi-check-circle me-1"></i><?= number_format($it['passed_qty']) ?> pcs
                                </div>
                                <small class="text-muted">of <?= number_format($it['inspected_qty']) ?> inspected</small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">₹<?= number_format($it['discount_price'] > 0 ? $it['discount_price'] : $it['base_price'], 2) ?></div>
                                <?php if ($it['discount_price'] > 0 && $it['base_price'] > $it['discount_price']): ?>
                                    <small class="text-muted text-decoration-line-through">₹<?= number_format($it['base_price'], 2) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-success px-2 py-1">
                                    <i class="bi bi-patch-check-fill me-1"></i>PASSED
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="printVariantGroupLabels('<?= e($it['size_name']) ?>', '<?= e($it['color_name']) ?>')">
                                    <i class="bi bi-printer me-1"></i>Print (<?= $it['passed_qty'] ?>)
                                </button>
                                <?php if ($remainingPieces > 0): ?>
                                <a href="<?= url('production/batches/create?qc_batch_id=' . $it['qc_batch_id']) ?>" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-box-seam me-1"></i>Pack
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<style>
.piece-card {
    transition: all 0.2s ease-in-out;
}
.piece-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
}
.btn-xs {
    padding: 0.2rem 0.4rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}
</style>

<!-- QRCode JS CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<!-- QR Code Batch Print Modal & Printable Area -->
<div class="modal fade" id="qrPrintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3 no-print">
                <h5 class="modal-title fw-bold"><i class="bi bi-printer me-2 text-primary"></i>Batch QR Label Sticker Tags</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Toolbar with delete search & count -->
                <div class="bg-white p-3 rounded shadow-xs mb-3 border d-flex justify-content-between align-items-center flex-wrap gap-2 no-print">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-tag me-1 text-primary"></i>Ready to Print QR Stickers</h6>
                        <small class="text-muted" id="printableCountBadge">0 Stickers Ready</small>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="input-group input-group-sm" style="max-width: 240px;">
                            <input type="text" id="deleteLabelInput" class="form-control" placeholder="Remove tag code..." onkeyup="if(event.key==='Enter') removeLabelByCode()">
                            <button class="btn btn-outline-danger" type="button" onclick="removeLabelByCode()" title="Remove this label">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        </div>
                        <button type="button" class="btn btn-success fw-semibold shadow-sm px-4" onclick="window.print()">
                            <i class="bi bi-printer-fill me-1"></i> Print QR Stickers Now
                        </button>
                    </div>
                </div>

                <!-- Stickers Grid Layout -->
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3" id="qrStickersGrid">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
            <div class="modal-footer bg-white border-top py-2 no-print">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success fw-semibold px-4" onclick="window.print()">
                    <i class="bi bi-printer-fill me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const PIECE_ITEMS = <?= json_encode($pieceItems ?? []) ?>;
window.inventoryPieceItems = <?= json_encode($pieceItems ?? []) ?>;

// Pagination state
let currentInventoryPage = 1;
let inventoryPageSize = 10;
let matchingPieceElements = [];

document.addEventListener('DOMContentLoaded', function() {
    // Render individual QR codes on cards
    document.querySelectorAll('.piece-qr-box').forEach(function(box) {
        const text = box.getAttribute('data-json');
        if (text) {
            new QRCode(box, {
                text: text,
                width: 80,
                height: 80,
                colorDark : "#0f172a",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.M
            });
        }
    });
});

function switchView(mode) {
    const pieceContainer = document.getElementById('pieceViewContainer');
    const groupContainer = document.getElementById('groupViewContainer');
    
    const btnPiece = document.getElementById('btnPieceView');
    const btnGroup = document.getElementById('btnGroupView');
    const paginationFooter = document.getElementById('piecePaginationFooter');

    if (mode === 'piece') {
        if (pieceContainer) pieceContainer.style.display = 'block';
        if (groupContainer) groupContainer.style.display = 'none';
        if (btnPiece) btnPiece.className = 'btn btn-primary active fw-semibold';
        if (btnGroup) btnGroup.className = 'btn btn-outline-secondary fw-semibold';
        if (paginationFooter) paginationFooter.style.display = 'flex';
    } else {
        if (pieceContainer) pieceContainer.style.display = 'none';
        if (groupContainer) groupContainer.style.display = 'block';
        if (btnPiece) btnPiece.className = 'btn btn-outline-secondary fw-semibold';
        if (btnGroup) btnGroup.className = 'btn btn-primary active fw-semibold';
        if (paginationFooter) paginationFooter.style.display = 'none';
    }
}


function filterInventoryPieces() {
    const input = document.getElementById('qrInventorySearch');
    const rawVal = input ? input.value.trim().toUpperCase() : '';
    const totalCount = window.inventoryPieceItems ? window.inventoryPieceItems.length : 0;

    const rows = Array.from(document.querySelectorAll('.inventory-piece-row'));
    matchingPieceElements = [];

    rows.forEach(row => {
        const tag = row.getAttribute('data-tag') || '';
        const sku = row.getAttribute('data-sku') || '';
        const size = row.getAttribute('data-size') || '';
        const color = row.getAttribute('data-color') || '';
        const prod = row.getAttribute('data-product') || '';

        if (!rawVal || tag.includes(rawVal) || sku.includes(rawVal) || prod.includes(rawVal) || (size.includes(rawVal) && color.includes(rawVal))) {
            matchingPieceElements.push(row);
        } else {
            row.style.display = 'none';
        }
    });

    const badge = document.getElementById('searchMatchBadge');
    if (badge) {
        if (rawVal) {
            badge.textContent = `Filtered: ${matchingPieceElements.length} of ${totalCount} pieces`;
            badge.className = 'badge bg-warning text-dark fw-bold fs-8';
        } else {
            badge.textContent = `Showing all ${totalCount} pieces`;
            badge.className = 'badge bg-white text-primary fw-bold fs-8';
        }
    }

    const filterBtnLbl = document.getElementById('filteredCountLabel');
    if (filterBtnLbl) {
        filterBtnLbl.textContent = matchingPieceElements.length;
    }

    // Reset to page 1 on filter/search change
    currentInventoryPage = 1;
    renderInventoryPagination();
}

function changePageSize(newSize) {
    inventoryPageSize = (newSize === 'all') ? 'all' : parseInt(newSize, 10);
    currentInventoryPage = 1;
    renderInventoryPagination();
}

function goToInventoryPage(pageNum) {
    currentInventoryPage = pageNum;
    renderInventoryPagination();
    // Smooth scroll to table top if needed
    const tableEl = document.getElementById('pieceInventoryTable');
    if (tableEl) {
        tableEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function renderInventoryPagination() {
    const totalMatching = matchingPieceElements.length;
    const totalCount = window.inventoryPieceItems ? window.inventoryPieceItems.length : 0;
    const isAll = (inventoryPageSize === 'all');
    const effectivePageSize = isAll ? totalMatching : parseInt(inventoryPageSize, 10);
    const totalPages = isAll ? 1 : Math.ceil(totalMatching / effectivePageSize) || 1;

    // Clamp current page
    if (currentInventoryPage > totalPages) currentInventoryPage = totalPages;
    if (currentInventoryPage < 1) currentInventoryPage = 1;

    const startIndex = isAll ? 0 : (currentInventoryPage - 1) * effectivePageSize;
    const endIndex = isAll ? totalMatching : Math.min(startIndex + effectivePageSize, totalMatching);

    // Hide all matching elements first, then display only current page slice
    matchingPieceElements.forEach((row, idx) => {
        if (idx >= startIndex && idx < endIndex) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    // Empty search state
    const emptyRow = document.getElementById('emptyPieceSearchRow');
    if (emptyRow) {
        emptyRow.style.display = (totalMatching === 0 && totalCount > 0) ? '' : 'none';
    }

    // Update Entries Counter text
    const infoEl = document.getElementById('pageEntriesInfo');
    const input = document.getElementById('qrInventorySearch');
    const isFiltered = input && input.value.trim().length > 0;

    if (infoEl) {
        if (totalMatching === 0) {
            infoEl.textContent = 'Showing 0 pieces';
        } else {
            const startDisplay = startIndex + 1;
            const endDisplay = endIndex;
            if (isFiltered) {
                infoEl.innerHTML = `Showing <strong>${startDisplay}</strong> to <strong>${endDisplay}</strong> of <strong>${totalMatching}</strong> filtered pieces <span class="text-muted">(${totalCount} total)</span>`;
            } else {
                infoEl.innerHTML = `Showing <strong>${startDisplay}</strong> to <strong>${endDisplay}</strong> of <strong>${totalCount}</strong> pieces`;
            }
        }
    }

    // Render pagination navigation items
    const paginationList = document.getElementById('inventoryPaginationList');
    if (!paginationList) return;

    if (totalPages <= 1) {
        paginationList.innerHTML = `
            <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li>
            <li class="page-item active"><span class="page-link">1</span></li>
            <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>
        `;
        return;
    }

    let html = '';

    // Previous Button
    const prevDisabled = (currentInventoryPage <= 1) ? 'disabled' : '';
    html += `<li class="page-item ${prevDisabled}">
        <a class="page-link" href="javascript:void(0)" onclick="${currentInventoryPage > 1 ? `goToInventoryPage(${currentInventoryPage - 1})` : 'return false;'}" aria-label="Previous">
            <i class="bi bi-chevron-left"></i>
        </a>
    </li>`;

    // Page Number Generation with Smart Ellipsis
    const delta = 2; // numbers before and after current
    const range = [];
    for (let i = Math.max(2, currentInventoryPage - delta); i <= Math.min(totalPages - 1, currentInventoryPage + delta); i++) {
        range.push(i);
    }

    // Always include first page
    if (currentInventoryPage - delta > 2) {
        range.unshift('...');
    }
    range.unshift(1);

    // Always include last page
    if (currentInventoryPage + delta < totalPages - 1) {
        range.push('...');
    }
    if (totalPages > 1 && !range.includes(totalPages)) {
        range.push(totalPages);
    }

    range.forEach(p => {
        if (p === '...') {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        } else {
            const isActive = (p === currentInventoryPage) ? 'active' : '';
            html += `<li class="page-item ${isActive}">
                <a class="page-link" href="javascript:void(0)" onclick="goToInventoryPage(${p})">${p}</a>
            </li>`;
        }
    });

    // Next Button
    const nextDisabled = (currentInventoryPage >= totalPages) ? 'disabled' : '';
    html += `<li class="page-item ${nextDisabled}">
        <a class="page-link" href="javascript:void(0)" onclick="${currentInventoryPage < totalPages ? `goToInventoryPage(${currentInventoryPage + 1})` : 'return false;'}" aria-label="Next">
            <i class="bi bi-chevron-right"></i>
        </a>
    </li>`;

    paginationList.innerHTML = html;
}

function clearInventorySearch() {
    const input = document.getElementById('qrInventorySearch');
    if (input) {
        input.value = '';
        filterInventoryPieces();
        input.focus();
    }
}

function generateQRCodeContainer(containerId, textPayload) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';

    if (window.QRCode) {
        new QRCode(container, {
            text: textPayload,
            width: 70,
            height: 70,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });
    } else {
        const encoded = encodeURIComponent(textPayload);
        const imgUrl = `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encoded}`;
        container.innerHTML = `<img src="${imgUrl}" style="width: 70px; height: 70px; display: block;" alt="QR">`;
    }
}

function renderStickersToModal(itemsToPrint) {
    const grid = document.getElementById('qrStickersGrid');
    if (!grid) return;
    grid.innerHTML = '';

    if (!itemsToPrint || itemsToPrint.length === 0) {
        alert('No garment pieces selected to print.');
        return;
    }

    itemsToPrint.forEach((it, idx) => {
        const tagCode = it.tag_code || 'TAG';
        const sizeName = it.size_name || 'Standard';
        const colorName = it.color_name || 'Assorted';
        const sku = it.product_sku || (sizeName + '-' + colorName);
        const basePrice = parseFloat(it.base_price || 0);
        const discountPrice = parseFloat(it.discount_price || 0);

        const fmtBase = (basePrice % 1 === 0) ? String(Math.round(basePrice)) : basePrice.toFixed(2);
        const fmtDisc = (discountPrice % 1 === 0) ? String(Math.round(discountPrice)) : discountPrice.toFixed(2);

        const cardId = `qrSticker_${idx + 1}`;
        const canvasId = `qrCanvas_${idx + 1}`;

        const colDiv = document.createElement('div');
        colDiv.className = 'col';
        colDiv.innerHTML = `
            <div class="card h-100 shadow-sm border p-2 bg-white qr-sticker-card position-relative" id="${cardId}">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-1 no-print" style="font-size: 0.65rem; z-index: 10; opacity: 0.8;" onclick="removeSingleStickerCard(this)" title="Remove this label"></button>
                <div class="d-flex align-items-start gap-2">
                    <div class="text-center flex-shrink-0" style="width: 70px;">
                        <div id="${canvasId}" class="bg-white p-1 rounded border shadow-xs d-flex justify-content-center align-items-center" style="width: 70px; height: 70px;"></div>
                        <div class="lbl-damage-code mt-1 code">${tagCode}</div>
                    </div>
                    <div class="flex-grow-1 overflow-hidden" style="margin-top: 2px;">
                        <div class="lbl-title text-uppercase text-truncate" title="GRM WAREHOUSE">GRM WAREHOUSE</div>
                        <div class="lbl-sku text-truncate fw-bold text-dark mt-1" title="${sku}">${sku}</div>
                        <div class="small text-muted" style="font-size: 7.5px;">Size: ${sizeName} · ${colorName}</div>
                        ${discountPrice > 0 && basePrice > discountPrice ? `<div class="lbl-mrp mt-1">MRP: ₹${fmtBase}</div>` : ''}
                        <div class="lbl-disc fw-bold text-success">Offer: ₹${fmtDisc > 0 ? fmtDisc : fmtBase}</div>
                    </div>
                </div>
            </div>
        `;

        grid.appendChild(colDiv);
        const qrPayload = `${tagCode}|${sku}|MRP:${fmtBase}|Disc:${fmtDisc}`;
        generateQRCodeContainer(canvasId, qrPayload);
    });

    updatePrintModalSummary();
    const modal = new bootstrap.Modal(document.getElementById('qrPrintModal'));
    modal.show();
}

function updatePrintModalSummary() {
    const grid = document.getElementById('qrStickersGrid');
    if (!grid) return;
    const stickers = grid.querySelectorAll('.col');
    const badge = document.getElementById('printableCountBadge');
    if (badge) {
        badge.textContent = `${stickers.length} Stickers Ready`;
    }
}

function removeSingleStickerCard(btn) {
    const col = btn.closest('.col');
    if (col) {
        col.remove();
        updatePrintModalSummary();
    }
}

function removeLabelByCode() {
    const input = document.getElementById('deleteLabelInput');
    if (!input || !input.value.trim()) return;
    const targetCode = input.value.trim().toUpperCase();
    const grid = document.getElementById('qrStickersGrid');
    const cols = grid.querySelectorAll('.col');
    let removedCount = 0;

    cols.forEach(col => {
        const codeEl = col.querySelector('.lbl-damage-code');
        if (codeEl && codeEl.textContent.trim().toUpperCase() === targetCode) {
            col.remove();
            removedCount++;
        }
    });

    if (removedCount > 0) {
        input.value = '';
        updatePrintModalSummary();
    } else {
        alert(`Label code "${targetCode}" not found in current print view.`);
    }
}

function printSinglePieceLabel(pieceNo) {
    const piece = window.inventoryPieceItems.find(p => p.piece_no == pieceNo);
    if (!piece) {
        alert('Garment piece not found.');
        return;
    }
    renderStickersToModal([piece]);
}

function printFilteredLabels() {
    const visiblePieces = [];
    const input = document.getElementById('qrInventorySearch');
    const rawVal = input ? input.value.trim().toUpperCase() : '';

    if (!window.inventoryPieceItems || window.inventoryPieceItems.length === 0) {
        alert('No garment pieces in this batch.');
        return;
    }

    if (!rawVal) {
        // Print all pieces in batch
        renderStickersToModal(window.inventoryPieceItems);
        return;
    }

    // Filter from whole dataset across all pages
    window.inventoryPieceItems.forEach(piece => {
        const tag = (piece.tag_code || '').toUpperCase();
        const sku = (piece.product_sku || '').toUpperCase();
        const size = (piece.size_name || '').toUpperCase();
        const color = (piece.color_name || '').toUpperCase();
        const prod = (piece.product_name || '').toUpperCase();

        if (tag.includes(rawVal) || sku.includes(rawVal) || prod.includes(rawVal) || (size.includes(rawVal) && color.includes(rawVal))) {
            visiblePieces.push(piece);
        }
    });

    if (visiblePieces.length === 0) {
        alert('No matching garment pieces found in current filter.');
        return;
    }

    renderStickersToModal(visiblePieces);
}

function printAllBatchLabels() {
    if (!window.inventoryPieceItems || window.inventoryPieceItems.length === 0) {
        alert('No garment pieces in this batch.');
        return;
    }
    renderStickersToModal(window.inventoryPieceItems);
}

function printSingleBatchItem(index) {
    if (window.inventoryPieceItems && window.inventoryPieceItems[index]) {
        renderStickersToModal([window.inventoryPieceItems[index]]);
    } else if (PIECE_ITEMS && PIECE_ITEMS[index]) {
        renderStickersToModal([PIECE_ITEMS[index]]);
    }
}

function printAllBatchStickers() {
    printAllBatchLabels();
}

function copyTagCode(code, btn) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code).then(() => {
            showCopyFeedback(btn);
        }).catch(() => {
            fallbackCopy(code, btn);
        });
    } else {
        fallbackCopy(code, btn);
    }
}

function fallbackCopy(code, btn) {
    const temp = document.createElement('textarea');
    temp.value = code;
    document.body.appendChild(temp);
    temp.select();
    try {
        document.execCommand('copy');
        showCopyFeedback(btn);
    } catch (e) {
        console.error('Copy failed', e);
    }
    document.body.removeChild(temp);
}

function showCopyFeedback(btn) {
    if (!btn) return;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check2 text-success me-1"></i> Copied!';
    setTimeout(() => { btn.innerHTML = orig; }, 1500);
}

// Initialize pagination on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    filterInventoryPieces();
});
</script>
