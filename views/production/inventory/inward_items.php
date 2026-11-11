<?php
$pieceCount = count($pieces);
$locationLabel = $locationType === 'CONCERN' ? 'Concern Inventory' : ($stock['production_unit_name'] ?? 'Production Unit');
$statusClasses = ['VERIFIED' => 'success', 'BILLED' => 'info', 'RETURNED' => 'warning', 'DAMAGED' => 'danger', 'MISSING' => 'secondary'];
$displayName = trim(($stock['product_name'] ?? '') . ' - ' . ($stock['size_name'] ?? '') . ' - ' . ($stock['color_name'] ?? ''), ' -');
$unitSellingPrice = (float)($stock['selling_price'] ?? 0);
$unitMrp = (float)($stock['mrp'] ?? 0);
$inStockValuation = $availableQuantity * $unitSellingPrice;
$excessCount = (int)($excessCount ?? 0);
$billedCount = (int)($billedCount ?? 0);
$damagedStockQuantity = (int)($damagedStockQuantity ?? 0);
?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 no-print">
    <div>
        <h2 class="mb-1"><i class="bi bi-box2-heart me-2 text-success"></i>Inward Inventory Items</h2>
        <span class="text-muted"><?= e($stock['product_name']) ?> · <?= e($stock['sku']) ?> · <?= e($locationLabel) ?></span>
    </div>
    <div class="d-flex gap-2">
        <?php if ($pieceCount > 0): ?>
            <button type="button" class="btn btn-primary fw-semibold shadow-sm" onclick="printAllInwardStickers()">
                <i class="bi bi-printer-fill me-1"></i> Print All QR Stickers (<?= number_format($pieceCount) ?>)
            </button>
        <?php endif; ?>
        <a href="<?= url('production/inventory') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Inventory
        </a>
    </div>
</div>

<!-- KPI Summary Cards (matching QC batch detail parity) -->
<div class="row g-3 mb-4 inward-kpi-row no-print">
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-success bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">In-Stock Remaining</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($availableQuantity) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted d-block mt-1">Value: ₹<?= number_format($inStockValuation, 2) ?></small>
                </div>
                <div class="bg-success text-white rounded-3 p-3">
                    <i class="bi bi-box-seam-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-primary bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Verified Pieces</span>
                    <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($verifiedCount) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted d-block mt-1"><?= $excessCount > 0 ? number_format($excessCount) . ' excess accepted/received' : 'From ' . number_format($pieceCount) . ' logged piece(s)' ?></small>
                </div>
                <div class="bg-primary text-white rounded-3 p-3">
                    <i class="bi bi-patch-check-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-danger bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Damaged Pieces</span>
                    <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($damagedCount) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted d-block mt-1">Damaged stock: <?= number_format($damagedStockQuantity) ?> pcs</small>
                </div>
                <div class="bg-danger text-white rounded-3 p-3">
                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-secondary bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Missing / Pending</span>
                    <h3 class="fw-bold text-secondary mb-0 mt-1"><?= number_format($missingCount) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted d-block mt-1"><?= e($locationLabel) ?></small>
                </div>
                <div class="bg-secondary text-white rounded-3 p-3">
                    <i class="bi bi-question-circle-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Garment QR Code Search & Quick Label Print -->
<div class="card shadow-sm mb-4 border-primary inward-search-card no-print">
    <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold fs-7"><i class="bi bi-qr-code-scan me-2"></i>Garment QR Code Search &amp; Quick Label Print</span>
        <span class="badge bg-white text-primary fw-bold fs-8" id="inwardMatchCount">Showing all <?= number_format($pieceCount) ?> pieces</span>
    </div>
    <div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <div class="col-lg-7 col-md-6">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light border-primary"><i class="bi bi-upc-scan text-primary fs-5"></i></span>
                    <input type="search" class="form-control border-primary fw-bold" id="inwardItemSearch"
                           placeholder="Search or scan QR sticker tag (e.g. <?= e($pieces[0]['tag_code'] ?? 'MFD92601') ?>), GRN, batch, or status..."
                           autofocus>
                    <button type="button" class="btn btn-outline-secondary" id="clearInwardSearch" aria-label="Clear search" title="Clear Search">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="col-lg-5 col-md-6 d-flex gap-2 justify-content-md-end flex-wrap">
                <button type="button" class="btn btn-primary btn-lg fw-semibold shadow-sm flex-grow-1 flex-md-grow-0" id="btnPrintInwardFiltered" onclick="printFilteredInwardLabels()" <?= $pieceCount === 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-printer-fill me-1"></i> Print Filtered Labels (<span id="filteredInwardCountLabel"><?= number_format($pieceCount) ?></span>)
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm no-print" id="inwardMainCard">
    <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-table text-primary"></i>
                Inwarded Product Pieces
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fs-7 px-2">
                    <?= number_format($availableQuantity) ?> In Stock / <?= number_format($pieceCount) ?> Total Pieces
                </span>
            </h5>
            <span class="text-muted small">
                <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
                Piece-by-piece listing for <?= e($displayName) ?> at <?= e($locationLabel) ?>
            </span>
        </div>
        <div class="btn-group" role="group" aria-label="Inventory view">
            <button type="button" class="btn btn-outline-secondary fw-semibold inward-view-button" data-view="cards" aria-pressed="false">
                <i class="bi bi-grid-3x3-gap me-1"></i>Cards
            </button>
            <button type="button" class="btn btn-primary active fw-semibold inward-view-button" data-view="list" aria-pressed="true">
                <i class="bi bi-list-ul me-1"></i>List (<?= number_format($pieceCount) ?>)
            </button>
            <button type="button" class="btn btn-outline-secondary fw-semibold inward-view-button" data-view="variant" aria-pressed="false">
                <i class="bi bi-layers-fill me-1"></i>By Variant (1)
            </button>
        </div>
    </div>

    <div class="card-body p-4">
        <?php if ($pieceCount === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-box-seam fs-1 d-block mb-3"></i>
                <h6 class="fw-semibold">No inward QR labels are recorded for this variant.</h6>
                <p class="small mb-0">Current available stock is <?= number_format($availableQuantity) ?> pieces.</p>
            </div>
        <?php else: ?>
            <div id="inwardPieceList" class="list-group list-group-flush gap-2">
                <?php foreach ($pieces as $index => $piece): ?>
                    <?php
                        $status = $piece['status'];
                        $isExcess = !empty($piece['is_excess']);
                        $searchText = strtolower(implode(' ', array_filter([
                            $piece['tag_code'],
                            $piece['grn_number'],
                            $piece['batch_number'] ?? '',
                            $piece['source_name'],
                            $status,
                            $isExcess ? 'excess extra overage' : '',
                            $stock['product_name'],
                            $stock['sku'],
                            $stock['size_name'] ?? '',
                            $stock['color_name'] ?? '',
                        ])));
                    ?>
                    <article class="list-group-item inward-piece-list-item d-flex flex-wrap align-items-center gap-3 p-3"
                             data-piece-search="<?= e($searchText) ?>"
                             data-piece-tag="<?= e(strtoupper($piece['tag_code'])) ?>"
                             data-piece-grn="<?= e($piece['grn_number'] ?: '') ?>"
                             data-piece-batch="<?= e($piece['batch_number'] ?: '') ?>"
                             data-piece-status="<?= e($status) ?>"
                             data-piece-excess="<?= $isExcess ? '1' : '0' ?>">
                        <div class="inward-list-tag">
                            <div class="small text-muted fw-semibold mb-1">Piece #<?= $index + 1 ?></div>
                            <span class="badge bg-dark text-white font-monospace fs-7 px-2 py-1">
                                <i class="bi bi-qr-code-scan me-1 text-warning"></i><?= e($piece['tag_code']) ?>
                            </span>
                            <?php if ($isExcess): ?>
                                <span class="badge bg-info text-dark ms-1">Excess</span>
                            <?php endif; ?>
                            <div class="small text-muted mt-1">Item #<?= $index + 1 ?> of <?= $pieceCount ?></div>
                        </div>

                        <div class="inward-list-product d-flex align-items-center gap-2">
                            <div class="bg-primary bg-opacity-10 text-primary rounded p-2 text-center flex-shrink-0" style="width: 38px; height: 38px;">
                                <i class="bi bi-bag-check fs-5"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="fw-bold text-dark"><?= e($stock['product_name']) ?></div>
                                <?php if (!empty($stock['category_name'])): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-7 mt-1"><?= e($stock['category_name']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="inward-list-meta d-flex flex-wrap gap-3 align-items-center">
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-secondary-subtle text-secondary border font-monospace px-2 py-1">Size: <?= e($stock['size_name'] ?? '—') ?></span>
                                <span class="badge bg-info-subtle text-info border font-monospace px-2 py-1">Color: <?= e($stock['color_name'] ?? '—') ?></span>
                            </div>
                            <div>
                                <div class="fw-semibold text-primary"><?= e($piece['grn_number'] ?: 'Inward Receipt') ?></div>
                                <small class="text-muted">
                                    <?= e($piece['source_name'] ?: 'Inward') ?>
                                    <?php if (!empty($piece['batch_number'])): ?> · Batch: <?= e($piece['batch_number']) ?><?php endif; ?>
                                </small>
                            </div>
                            <div>
                                <span class="badge bg-<?= $statusClasses[$status] ?? 'secondary' ?>-subtle text-<?= $statusClasses[$status] ?? 'secondary' ?> border">
                                    <i class="bi bi-<?= $status === 'VERIFIED' ? 'patch-check' : ($status === 'BILLED' ? 'receipt' : ($status === 'DAMAGED' ? 'exclamation-triangle' : 'question-circle')) ?> me-1"></i><?= e($status) ?>
                                </span>
                                <?php if (!empty($piece['received_at'])): ?>
                                    <small class="d-block text-muted mt-1"><?= e(date('d M Y', strtotime($piece['received_at']))) ?></small>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark">₹<?= number_format($unitSellingPrice, 2) ?></div>
                                <?php if ($unitMrp > $unitSellingPrice): ?>
                                    <small class="text-muted text-decoration-line-through">₹<?= number_format($unitMrp, 2) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="inward-list-actions d-flex justify-content-end ms-auto">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><button type="button" class="dropdown-item inward-copy-code" data-code="<?= e($piece['tag_code']) ?>">
                                    <i class="bi bi-clipboard me-2"></i>Copy QR number
                                </button></li>
                                <li><button type="button" class="dropdown-item inward-print-code" data-code="<?= e($piece['tag_code']) ?>">
                                    <i class="bi bi-printer me-2"></i>Print this item
                                </button></li>
                            </ul>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div id="inwardPieceCards" class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" style="display:none;">
                <?php foreach ($pieces as $index => $piece): ?>
                    <?php
                        $status = $piece['status'];
                        $isExcess = !empty($piece['is_excess']);
                        $searchText = strtolower(implode(' ', array_filter([
                            $piece['tag_code'],
                            $piece['grn_number'],
                            $piece['batch_number'] ?? '',
                            $piece['source_name'],
                            $status,
                            $isExcess ? 'excess extra overage' : '',
                            $stock['product_name'],
                            $stock['sku'],
                            $stock['size_name'] ?? '',
                            $stock['color_name'] ?? '',
                        ])));
                    ?>
                    <div class="col inward-piece-card"
                         data-piece-search="<?= e($searchText) ?>"
                         data-piece-tag="<?= e(strtoupper($piece['tag_code'])) ?>">
                        <div class="card border shadow-sm h-100 piece-card">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                    <div>
                                        <div class="small text-muted fw-semibold">Piece #<?= $index + 1 ?></div>
                                        <span class="badge bg-dark text-white font-monospace mt-1"><i class="bi bi-qr-code-scan me-1 text-warning"></i><?= e($piece['tag_code']) ?></span>
                                        <?php if ($isExcess): ?>
                                            <span class="badge bg-info text-dark mt-1">Excess</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-<?= $statusClasses[$status] ?? 'secondary' ?>"><?= e($status) ?></span>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle py-0 px-2" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><button type="button" class="dropdown-item inward-copy-code" data-code="<?= e($piece['tag_code']) ?>">
                                                    <i class="bi bi-clipboard me-2"></i>Copy QR number
                                                </button></li>
                                                <li><button type="button" class="dropdown-item inward-print-code" data-code="<?= e($piece['tag_code']) ?>">
                                                    <i class="bi bi-printer me-2"></i>Print this item
                                                </button></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="fw-bold"><?= e($stock['product_name']) ?></div>
                                <div class="small text-muted mb-2"><?= e($stock['sku']) ?> · Size <?= e($stock['size_name'] ?? '—') ?> · <?= e($stock['color_name'] ?? '—') ?></div>
                                <hr class="mt-auto">
                                <div class="d-flex justify-content-between gap-2 small">
                                    <span class="text-muted"><?= e($piece['grn_number'] ?: 'Inward Receipt') ?><?= !empty($piece['batch_number']) ? ' · ' . e($piece['batch_number']) : '' ?></span>
                                    <span class="fw-semibold">₹<?= number_format($unitSellingPrice, 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="inwardVariantView" class="table-responsive" style="display:none;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Product &amp; SKU</th><th>Variant</th><th>Pieces</th><th>In Stock</th><th>Price</th><th>Status Breakdown</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><div class="fw-bold"><?= e($stock['product_name']) ?></div><small class="text-muted"><?= e($stock['sku']) ?></small></td>
                            <td><?= e($stock['size_name'] ?? '—') ?> / <?= e($stock['color_name'] ?? '—') ?></td>
                            <td><?= number_format($pieceCount) ?></td>
                            <td class="fw-semibold text-success"><?= number_format($availableQuantity) ?></td>
                            <td>₹<?= number_format($unitSellingPrice, 2) ?></td>
                            <td>
                                <span class="badge bg-success-subtle text-success border"><?= number_format($verifiedCount) ?> Verified</span>
                                <?php if ($billedCount > 0): ?>
                                    <span class="badge bg-info-subtle text-info-emphasis border"><?= number_format($billedCount) ?> Billed</span>
                                <?php endif; ?>
                                <?php if ($excessCount > 0): ?>
                                    <span class="badge bg-info-subtle text-info-emphasis border"><?= number_format($excessCount) ?> Excess</span>
                                <?php endif; ?>
                                <span class="badge bg-danger-subtle text-danger border"><?= number_format($damagedCount) ?> Damaged</span>
                                <span class="badge bg-secondary-subtle text-secondary border"><?= number_format($missingCount) ?> Missing</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="inwardNoMatches" class="text-center text-muted py-5" style="display:none;">
                <i class="bi bi-search fs-2 d-block mb-2"></i>
                <h6 class="fw-semibold mb-1">No Matching Inward Item Found</h6>
                <p class="small mb-0">No piece at this location matches the searched QR code, GRN, or status.</p>
            </div>

            <!-- Pagination Footer Bar -->
            <div class="card-footer bg-transparent px-0 pt-3 mt-3 border-top d-flex flex-column flex-md-row align-items-center justify-content-between gap-2" id="inwardPaginationFooter">
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted fw-medium" id="inwardPageEntriesInfo">Showing all entries</span>
                    <select id="inwardPageSizeSelect" class="form-select form-select-sm" style="width: auto;" onchange="changeInwardPageSize(this.value)">
                        <option value="10">10 / page</option>
                        <option value="25" selected>25 / page</option>
                        <option value="50">50 / page</option>
                        <option value="100">100 / page</option>
                        <option value="all">Show All</option>
                    </select>
                </div>
                <nav aria-label="Inward piece pagination">
                    <ul class="pagination pagination-sm mb-0" id="inwardPaginationList"></ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- QR Stickers Preview & Print Modal -->
<div class="modal fade" id="inwardQrPrintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3 no-print">
                <h5 class="modal-title fw-bold"><i class="bi bi-printer me-2 text-primary"></i>Inward QR Label Sticker Tags</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="bg-white p-3 rounded shadow-xs mb-3 border d-flex justify-content-between align-items-center flex-wrap gap-2 no-print">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-tag me-1 text-primary"></i>Ready to Print QR Stickers</h6>
                        <small class="text-muted" id="inwardPrintableCountBadge">0 Stickers Ready</small>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="input-group input-group-sm" style="max-width: 240px;">
                            <input type="text" id="deleteInwardLabelInput" class="form-control" placeholder="Remove tag code..." onkeyup="if(event.key==='Enter') removeInwardLabelByCode()">
                            <button class="btn btn-outline-danger" type="button" onclick="removeInwardLabelByCode()" title="Remove this label">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        </div>
                        <button type="button" class="btn btn-success fw-semibold shadow-sm px-4" onclick="window.print()">
                            <i class="bi bi-printer-fill me-1"></i> Print QR Stickers Now
                        </button>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3" id="inwardQrStickersGrid"></div>
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

<style>
.inward-piece-list-item {
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem !important;
    background: #fff;
    min-height: 110px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}
.inward-piece-list-item:hover,
.piece-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.06);
}
.inward-piece-highlight {
    border: 2px solid #f59e0b !important;
    background-color: #fffbeb !important;
}
.inward-list-tag { min-width: 195px; }
.inward-list-product { min-width: 220px; flex: 1 1 240px; }
.inward-list-meta { flex: 1 1 400px; }
.inward-list-actions { min-width: 105px; }
.qr-sticker-card {
    background: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 0.75rem;
    text-align: center;
    height: 100%;
    position: relative;
}
.qr-sticker-remove {
    position: absolute;
    top: 4px;
    right: 6px;
}
@media (max-width: 767.98px) {
    .inward-list-tag, .inward-list-product, .inward-list-meta, .inward-list-actions { min-width: 100%; }
    .inward-list-actions { justify-content: flex-start !important; }
}
@media print {
    .no-print, .inward-list-actions { display: none !important; }
    .modal.show { position: static !important; display: block !important; overflow: visible !important; }
    .modal-dialog { max-width: 100% !important; margin: 0 !important; }
    .qr-sticker-remove { display: none !important; }
    .qr-sticker-card { break-inside: avoid; border: 1px solid #333; }
}
</style>

<script>
const inwardMeta = <?= json_encode([
    'product_name' => (string)($stock['product_name'] ?? ''),
    'sku'          => (string)($stock['sku'] ?? ''),
    'size_name'    => (string)($stock['size_name'] ?? '—'),
    'color_name'   => (string)($stock['color_name'] ?? '—'),
    'location'     => (string)$locationLabel,
    'price'        => $unitSellingPrice,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const inwardPiecesData = <?= json_encode(array_values($pieces), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

let inwardActiveView = 'list';
let inwardCurrentPage = 1;
let inwardPageSize = 25;
let inwardMatchedIndexes = inwardPiecesData.map((_, idx) => idx);
let inwardCurrentStickers = [];
let inwardLastHighlightTag = null;

function escapeInwardHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[char]));
}

function renderInwardView() {
    const searchEl = document.getElementById('inwardItemSearch');
    const query = searchEl ? searchEl.value.trim().toLowerCase() : '';
    const exactCode = searchEl ? searchEl.value.trim().toUpperCase() : '';

    const listEl = document.getElementById('inwardPieceList');
    const cardsEl = document.getElementById('inwardPieceCards');
    const variantEl = document.getElementById('inwardVariantView');
    const emptyEl = document.getElementById('inwardNoMatches');
    const countEl = document.getElementById('inwardMatchCount');
    const filteredCountLabel = document.getElementById('filteredInwardCountLabel');
    const footerEl = document.getElementById('inwardPaginationFooter');
    const listItems = Array.from(document.querySelectorAll('.inward-piece-list-item'));
    const cardItems = Array.from(document.querySelectorAll('.inward-piece-card'));

    inwardMatchedIndexes = [];
    listItems.forEach((item, idx) => {
        const matches = !query || (item.dataset.pieceSearch || '').includes(query);
        const tag = item.dataset.pieceTag || '';
        const isExact = exactCode !== '' && tag === exactCode;
        item.classList.toggle('inward-piece-highlight', isExact);
        if (cardItems[idx]) {
            const cardBox = cardItems[idx].querySelector('.piece-card');
            cardBox?.classList.toggle('inward-piece-highlight', isExact);
        }
        if (matches) {
            inwardMatchedIndexes.push(idx);
        }
    });

    const totalMatches = inwardMatchedIndexes.length;
    if (countEl) {
        countEl.textContent = query
            ? `Showing ${totalMatches} of ${listItems.length} pieces`
            : `Showing all ${listItems.length} pieces`;
    }
    if (filteredCountLabel) {
        filteredCountLabel.textContent = String(totalMatches);
    }
    const btnPrintFiltered = document.getElementById('btnPrintInwardFiltered');
    if (btnPrintFiltered) {
        btnPrintFiltered.disabled = totalMatches === 0;
    }

    const isAll = inwardPageSize === 'all';
    const effectiveSize = isAll ? Math.max(1, totalMatches) : parseInt(inwardPageSize, 10);
    const totalPages = isAll ? 1 : Math.max(1, Math.ceil(totalMatches / effectiveSize));
    if (inwardCurrentPage > totalPages) inwardCurrentPage = totalPages;
    if (inwardCurrentPage < 1) inwardCurrentPage = 1;

    const startIdx = isAll ? 0 : (inwardCurrentPage - 1) * effectiveSize;
    const endIdx = isAll ? totalMatches : Math.min(startIdx + effectiveSize, totalMatches);
    const pageIndexSet = new Set(inwardMatchedIndexes.slice(startIdx, endIdx));

    listItems.forEach((item, idx) => {
        item.style.display = (inwardActiveView === 'list' && pageIndexSet.has(idx)) ? '' : 'none';
    });
    cardItems.forEach((card, idx) => {
        card.style.display = (inwardActiveView === 'cards' && pageIndexSet.has(idx)) ? '' : 'none';
    });

    if (listEl) listEl.style.display = (inwardActiveView === 'list' && totalMatches > 0) ? 'flex' : 'none';
    if (cardsEl) cardsEl.style.display = (inwardActiveView === 'cards' && totalMatches > 0) ? 'flex' : 'none';
    if (variantEl) variantEl.style.display = inwardActiveView === 'variant' ? '' : 'none';
    if (emptyEl) emptyEl.style.display = (totalMatches === 0 && inwardActiveView !== 'variant') ? '' : 'none';
    if (footerEl) footerEl.style.display = (totalMatches === 0 || inwardActiveView === 'variant') ? 'none' : '';

    const entriesInfo = document.getElementById('inwardPageEntriesInfo');
    if (entriesInfo) {
        entriesInfo.innerHTML = totalMatches === 0
            ? 'Showing <strong>0</strong> pieces'
            : `Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${totalMatches}</strong> pieces`;
    }

    const paginationList = document.getElementById('inwardPaginationList');
    if (paginationList) {
        if (totalPages <= 1) {
            paginationList.innerHTML = `
                <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left me-1"></i> Prev</span></li>
                <li class="page-item active"><span class="page-link">1</span></li>
                <li class="page-item disabled"><span class="page-link">Next <i class="bi bi-chevron-right ms-1"></i></span></li>
            `;
        } else {
            let html = `<li class="page-item ${inwardCurrentPage <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="${inwardCurrentPage > 1 ? `goToInwardPage(${inwardCurrentPage - 1})` : 'return false;'}"><i class="bi bi-chevron-left me-1"></i> Prev</a>
            </li>`;
            for (let p = 1; p <= totalPages; p++) {
                html += `<li class="page-item ${p === inwardCurrentPage ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="goToInwardPage(${p})">${p}</a>
                </li>`;
            }
            html += `<li class="page-item ${inwardCurrentPage >= totalPages ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="${inwardCurrentPage < totalPages ? `goToInwardPage(${inwardCurrentPage + 1})` : 'return false;'}">Next <i class="bi bi-chevron-right ms-1"></i></a>
            </li>`;
            paginationList.innerHTML = html;
        }
    }

    if (exactCode !== '' && exactCode !== inwardLastHighlightTag) {
        const matchedIdx = inwardMatchedIndexes.find(idx => (listItems[idx]?.dataset.pieceTag || '') === exactCode);
        if (matchedIdx !== undefined) {
            inwardLastHighlightTag = exactCode;
            const matchedEl = inwardActiveView === 'cards' ? cardItems[matchedIdx] : listItems[matchedIdx];
            matchedEl?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    } else if (exactCode === '') {
        inwardLastHighlightTag = null;
    }
}

function changeInwardPageSize(val) {
    inwardPageSize = val === 'all' ? 'all' : parseInt(val, 10);
    inwardCurrentPage = 1;
    renderInwardView();
}

function goToInwardPage(page) {
    inwardCurrentPage = page;
    renderInwardView();
    document.getElementById('inwardMainCard')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function openInwardStickerModal(piecesToPrint) {
    inwardCurrentStickers = piecesToPrint.slice();
    renderInwardStickersGrid();
    const modalEl = document.getElementById('inwardQrPrintModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

function renderInwardStickersGrid() {
    const grid = document.getElementById('inwardQrStickersGrid');
    const badge = document.getElementById('inwardPrintableCountBadge');
    if (!grid) return;

    if (badge) {
        badge.textContent = `${inwardCurrentStickers.length} Sticker${inwardCurrentStickers.length === 1 ? '' : 's'} Ready`;
    }
    grid.replaceChildren();
    if (inwardCurrentStickers.length === 0) {
        grid.innerHTML = '<div class="col-12 text-center text-muted py-5">No QR stickers selected to print.</div>';
        return;
    }

    inwardCurrentStickers.forEach((piece, idx) => {
        const col = document.createElement('div');
        col.className = 'col';
        col.innerHTML = `
            <div class="qr-sticker-card">
                <button type="button" class="btn btn-sm btn-link text-danger p-0 qr-sticker-remove no-print" title="Remove sticker" onclick="removeInwardStickerAt(${idx})">
                    <i class="bi bi-x-circle"></i>
                </button>
                <div class="fw-bold small text-truncate">${escapeInwardHtml(inwardMeta.product_name)}</div>
                <div class="text-muted mb-2" style="font-size: 11px;">${escapeInwardHtml(inwardMeta.size_name)} · ${escapeInwardHtml(inwardMeta.color_name)}</div>
                <div class="d-flex justify-content-center my-2 inward-modal-qr-box" id="inwardQrBox_${idx}"></div>
                <div class="font-monospace fw-bold small">${escapeInwardHtml(piece.tag_code)}</div>
                <div class="text-muted" style="font-size: 11px;">${escapeInwardHtml(piece.grn_number || inwardMeta.location)} · ₹${Number(inwardMeta.price || 0).toFixed(2)}</div>
            </div>
        `;
        grid.appendChild(col);
        const qrContainer = col.querySelector('.inward-modal-qr-box');
        if (qrContainer && typeof QRCode !== 'undefined') {
            new QRCode(qrContainer, {
                text: piece.tag_code,
                width: 96,
                height: 96,
                correctLevel: QRCode.CorrectLevel.M
            });
        } else if (qrContainer) {
            qrContainer.innerHTML = `<code class="border rounded px-2 py-1">${escapeInwardHtml(piece.tag_code)}</code>`;
        }
    });
}

function removeInwardStickerAt(index) {
    inwardCurrentStickers.splice(index, 1);
    renderInwardStickersGrid();
}

function removeInwardLabelByCode() {
    const input = document.getElementById('deleteInwardLabelInput');
    if (!input) return;
    const code = input.value.trim().toUpperCase();
    if (!code) return;
    inwardCurrentStickers = inwardCurrentStickers.filter(p => String(p.tag_code || '').toUpperCase() !== code);
    input.value = '';
    renderInwardStickersGrid();
}

function printFilteredInwardLabels() {
    const items = inwardMatchedIndexes.map(idx => inwardPiecesData[idx]).filter(Boolean);
    openInwardStickerModal(items);
}

function printAllInwardStickers() {
    openInwardStickerModal(inwardPiecesData);
}

document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('inwardItemSearch');
    const clearSearch = document.getElementById('clearInwardSearch');

    document.querySelectorAll('.inward-view-button').forEach(function (button) {
        button.addEventListener('click', function () {
            inwardActiveView = button.dataset.view || 'list';
            document.querySelectorAll('.inward-view-button').forEach(function (viewButton) {
                viewButton.classList.toggle('btn-primary', viewButton === button);
                viewButton.classList.toggle('active', viewButton === button);
                viewButton.classList.toggle('btn-outline-secondary', viewButton !== button);
                viewButton.setAttribute('aria-pressed', viewButton === button ? 'true' : 'false');
            });
            inwardCurrentPage = 1;
            renderInwardView();
        });
    });

    search?.addEventListener('input', function () {
        inwardCurrentPage = 1;
        renderInwardView();
    });

    clearSearch?.addEventListener('click', function () {
        if (search) {
            search.value = '';
            search.focus();
        }
        inwardCurrentPage = 1;
        renderInwardView();
    });

    document.querySelectorAll('.inward-copy-code').forEach(function (button) {
        button.addEventListener('click', async function () {
            const code = button.dataset.code || '';
            try {
                await navigator.clipboard.writeText(code);
                button.innerHTML = '<i class="bi bi-check2 me-2"></i>Copied';
                setTimeout(() => {
                    button.innerHTML = '<i class="bi bi-clipboard me-2"></i>Copy QR number';
                }, 1500);
            } catch (error) {
                window.prompt('Copy this QR number:', code);
            }
        });
    });

    document.querySelectorAll('.inward-print-code').forEach(function (button) {
        button.addEventListener('click', function () {
            const code = String(button.dataset.code || '').toUpperCase();
            const found = inwardPiecesData.find(p => String(p.tag_code || '').toUpperCase() === code) || { tag_code: code, grn_number: '' };
            openInwardStickerModal([found]);
        });
    });

    renderInwardView();
});
</script>
