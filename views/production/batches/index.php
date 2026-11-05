<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-box-seam me-2 text-success"></i>Finished Production Batches</h2>
        <span class="text-muted">Finished goods packing boxes, destination warehouse assignment & approval</span>
    </div>
    <?php if (hasPermission('batches', 'create')): ?>
    <a href="<?= url('production/batches/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Pack New Batch
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/batches') ?>" id="batchesFilterForm" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="batchesStatusFilter" class="form-label small text-muted fw-semibold">Status</label>
                <select name="status" id="batchesStatusFilter" class="form-select form-select-sm" onchange="document.getElementById('batchesFilterForm').submit()">
                    <option value="">All Statuses</option>
                    <option value="PREPARING" <?= ($filters['status'] ?? '') === 'PREPARING' ? 'selected' : '' ?>>Preparing / Packed</option>
                    <option value="APPROVED" <?= ($filters['status'] ?? '') === 'APPROVED' ? 'selected' : '' ?>>Approved for Dispatch</option>
                    <option value="DISPATCHED" <?= ($filters['status'] ?? '') === 'DISPATCHED' ? 'selected' : '' ?>>Dispatched</option>
                    <option value="RECEIVED" <?= ($filters['status'] ?? '') === 'RECEIVED' ? 'selected' : '' ?>>Received at WH</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="batchesWarehouseFilter" class="form-label small text-muted fw-semibold">Destination Warehouse</label>
                <select name="warehouse_id" id="batchesWarehouseFilter" class="form-select form-select-sm" onchange="document.getElementById('batchesFilterForm').submit()">
                    <option value="">All Warehouses</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= (($filters['destination_warehouse_id'] ?? $filters['warehouse_id'] ?? '') == $w['id']) ? 'selected' : '' ?>>
                            <?= e($w['name']) ?> (<?= e($w['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="batchesSearchInput" class="form-label small text-muted fw-semibold">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="search" 
                           id="batchesSearchInput" 
                           class="form-control form-control-sm border-start-0" 
                           placeholder="Batch #, Program #, Product name, Warehouse..." 
                           value="<?= e($filters['search'] ?? '') ?>" 
                           autocomplete="off">
                    <?php if (!empty($filters['search'])): ?>
                        <button type="button" class="btn btn-outline-secondary border-start-0" onclick="clearBatchSearch()" title="Clear search">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-fill" title="Filter"><i class="bi bi-funnel me-1"></i> Filter</button>
                <?php if (!empty($filters['status']) || !empty($filters['destination_warehouse_id']) || !empty($filters['warehouse_id']) || !empty($filters['search'])): ?>
                    <a href="<?= url('production/batches') ?>" class="btn btn-outline-danger btn-sm" title="Reset All Filters"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Batches Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="prodBatchesTable">
                <thead class="table-light">
                    <tr>
                        <th>Batch #</th>
                        <th>Program</th>
                        <th>Product</th>
                        <th>Total Pieces</th>
                        <th>Box Count</th>
                        <th>Destination Warehouse</th>
                        <th>Packed By</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="batchesTableBody">
                    <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                <h6 class="fw-semibold mb-1"><?= (!empty($filters['status']) || !empty($filters['destination_warehouse_id']) || !empty($filters['search'])) ? 'No Matching Production Batches Found' : 'No Finished Production Batches Found' ?></h6>
                                <p class="small text-muted mb-3">
                                    <?= (!empty($filters['status']) || !empty($filters['destination_warehouse_id']) || !empty($filters['search'])) 
                                        ? 'Try clearing or changing your filter criteria.' 
                                        : 'Batches will appear here once goods are packed from QC inspection.' ?>
                                </p>
                                <?php if (!empty($filters['status']) || !empty($filters['destination_warehouse_id']) || !empty($filters['search'])): ?>
                                    <a href="<?= url('production/batches') ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($batches as $b): ?>
                            <?php 
                                $statusClass = match($b['status']) {
                                    'APPROVED'   => 'bg-success',
                                    'DISPATCHED' => 'bg-primary',
                                    'RECEIVED'   => 'bg-info text-dark',
                                    default      => 'bg-warning text-dark'
                                };

                                $searchData = strtolower(
                                    ($b['batch_number'] ?? '') . ' ' . 
                                    ($b['program_number'] ?? '') . ' ' . 
                                    ($b['product_name'] ?? '') . ' ' . 
                                    ($b['warehouse_name'] ?? '') . ' ' . 
                                    ($b['warehouse_code'] ?? '') . ' ' . 
                                    ($b['prepared_by_name'] ?? '') . ' ' . 
                                    ($b['status'] ?? '') . ' ' . 
                                    'batch #' . ($b['id'] ?? '') . ' ' . 
                                    'program #' . ($b['program_id'] ?? '')
                                );
                            ?>
                            <tr class="prod-batch-row" 
                                data-batch-id="<?= $b['id'] ?>"
                                data-status="<?= e($b['status']) ?>"
                                data-warehouse-id="<?= $b['destination_warehouse_id'] ?? '' ?>"
                                data-search-text="<?= e($searchData) ?>">
                                <td>
                                    <div class="fw-bold text-dark"><?= e($b['batch_number']) ?></div>
                                    <small class="text-muted"><?= formatDate($b['created_at']) ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($b['program_id'])): ?>
                                        <a href="<?= url('production/programs/view/' . $b['program_id']) ?>" class="text-decoration-none fw-semibold">
                                            <?= e($b['program_number'] ?: ('Program #' . $b['program_id'])) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($b['product_name'] ?: '—') ?></div>
                                </td>
                                <td class="text-success fw-bold"><?= number_format($b['total_qty']) ?> pcs</td>
                                <td><span class="fw-semibold"><?= number_format($b['box_count']) ?></span> boxes</td>
                                <td><span class="badge bg-light text-dark border"><?= e($b['warehouse_name'] ?: 'Unassigned') ?></span></td>
                                <td><?= e($b['prepared_by_name'] ?: 'Staff') ?></td>
                                <td><span class="badge <?= $statusClass ?>"><?= e($b['status']) ?></span></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= url('production/batches/label/' . $b['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Carton QR Label">
                                            <i class="bi bi-qr-code me-1"></i> QR Sticker
                                        </a>
                                        <?php if ($b['status'] === 'PREPARING' && hasPermission('batches', 'approve')): ?>
                                            <form method="POST" action="<?= url('production/batches/approve/' . $b['id']) ?>" class="d-inline" onsubmit="return confirm('Approve this batch for outward dispatch?');">
                                                <?= CSRF::field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-check2"></i> Approve
                                                </button>
                                            </form>
                                        <?php elseif ($b['status'] === 'APPROVED' && hasPermission('outward', 'create')): ?>
                                            <a href="<?= url('production/outward') ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-truck me-1"></i> Dispatch
                                            </a>
                                        <?php elseif ($b['status'] === 'DISPATCHED' || $b['status'] === 'RECEIVED' || !empty($b['outward_id'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-info" onclick="viewBatchDispatchDetails(<?= $b['id'] ?>)" title="View Dispatched Details">
                                                <i class="bi bi-eye me-1"></i> View Dispatch
                                            </button>
                                        <?php endif; ?>
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
    <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="batchesPaginationFooter">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="batchesPageInfo">
                Showing 1 to 10 of <?= count($batches) ?> batches
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="batchesPageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                    <option value="10" selected>10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <nav aria-label="Batches Pagination">
            <ul class="pagination pagination-modern mb-0" id="batchesPaginationList">
            </ul>
        </nav>
    </div>
</div>

<!-- Modal: View Dispatched Batch & Product Breakdown -->
<div class="modal fade" id="viewBatchDispatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="vbdModalTitle">
                    <i class="bi bi-truck me-2 text-primary"></i>Batch Outward Dispatch Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="vbdModalBody">
                <div class="text-center py-4 text-muted" id="vbdLoadingSpinner">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div>Loading dispatch details...</div>
                </div>
                <div id="vbdContent" style="display: none;">
                    <!-- Status & Outward Header Banner -->
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-3 bg-light border mb-4 flex-wrap gap-2">
                        <div>
                            <span class="text-uppercase small text-muted fw-bold d-block">Outward Document #</span>
                            <span class="fs-5 fw-bold text-primary font-monospace" id="vbdOutwardNumber">—</span>
                        </div>
                        <div>
                            <span class="badge px-3 py-2 fs-7" id="vbdStatusBadge">DISPATCHED</span>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="small fw-bold text-muted text-uppercase mb-2"><i class="bi bi-box-seam me-1 text-primary"></i> Batch &amp; Product Info</div>
                                <div class="mb-1"><strong>Batch Number:</strong> <span class="font-monospace text-dark fw-bold" id="vbdBatchNumber">—</span></div>
                                <div class="mb-1"><strong>Product:</strong> <span class="text-dark fw-bold" id="vbdProductName">—</span></div>
                                <div class="mb-1"><strong>Program:</strong> <span class="text-primary fw-bold" id="vbdProgramNumber">—</span></div>
                                <div><strong>Destination:</strong> <span class="badge bg-primary-subtle text-primary border" id="vbdWarehouseName">—</span></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="small fw-bold text-muted text-uppercase mb-2"><i class="bi bi-truck me-1 text-primary"></i> Transport &amp; Dispatch Details</div>
                                <div class="mb-1"><strong>Vehicle / Courier #:</strong> <span class="badge bg-dark font-monospace" id="vbdVehicleNumber">—</span></div>
                                <div class="mb-1"><strong>Dispatched Date:</strong> <span class="text-dark" id="vbdDispatchDate">—</span></div>
                                <div class="mb-1"><strong>Dispatched By:</strong> <span class="text-dark" id="vbdDispatchedBy">—</span></div>
                                <div><strong>Packed Quantity:</strong> <span class="text-success fw-bold" id="vbdTotalQty">—</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Dispatch Notes -->
                    <div class="mb-4" id="vbdNotesContainer" style="display: none;">
                        <div class="small fw-bold text-muted mb-1">Dispatch Notes / Gate Pass:</div>
                        <div class="p-2 bg-white border rounded small text-dark" id="vbdNotes"></div>
                    </div>

                    <!-- Variant Items Breakdown Table -->
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
                                <tbody id="vbdItemsTableBody">
                                    <!-- Populated via Ajax -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function clearBatchSearch() {
    const searchInput = document.getElementById('batchesSearchInput');
    if (searchInput) {
        searchInput.value = '';
        document.getElementById('batchesFilterForm').submit();
    }
}

function viewBatchDispatchDetails(batchId) {
    const modalEl = document.getElementById('viewBatchDispatchModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    const spinner = document.getElementById('vbdLoadingSpinner');
    const content = document.getElementById('vbdContent');
    if (spinner) spinner.style.display = 'block';
    if (content) content.style.display = 'none';

    fetch('<?= url("production/batches/view-ajax/") ?>/' + batchId)
        .then(res => {
            if (!res.ok) {
                throw new Error('Server returned status ' + res.status);
            }
            return res.json();
        })
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Error loading batch details.');
                modal.hide();
                return;
            }

            const batch = data.batch || {};
            const outward = data.outward || {};
            const items = data.items || [];

            const elBatchNum = document.getElementById('vbdBatchNumber');
            if (elBatchNum) elBatchNum.textContent = batch.batch_number || ('Batch #' + batch.id);

            const elProdName = document.getElementById('vbdProductName');
            if (elProdName) elProdName.textContent = batch.product_name || 'Finished Product';

            const elProgNum = document.getElementById('vbdProgramNumber');
            if (elProgNum) elProgNum.textContent = batch.program_number || ('Program #' + batch.program_id);

            const elWhName = document.getElementById('vbdWarehouseName');
            if (elWhName) elWhName.textContent = batch.warehouse_name || 'Unassigned';
            
            const totalQty = parseInt(batch.total_qty || 0);
            const boxCount = parseInt(batch.box_count || 1);
            const elTotalQty = document.getElementById('vbdTotalQty');
            if (elTotalQty) elTotalQty.textContent = totalQty.toLocaleString() + ' pcs (' + boxCount + ' ' + (boxCount === 1 ? 'box' : 'boxes') + ')';

            // Outward info
            const outNum = outward.outward_number || batch.outward_number || 'Direct Dispatch';
            const elOutNum = document.getElementById('vbdOutwardNumber');
            if (elOutNum) elOutNum.textContent = outNum;

            const vehicle = outward.vehicle_number || batch.vehicle_number || 'Direct Handover';
            const elVeh = document.getElementById('vbdVehicleNumber');
            if (elVeh) elVeh.textContent = vehicle;

            const dispDate = outward.dispatch_date || batch.dispatch_date || batch.updated_at || '—';
            const elDispDate = document.getElementById('vbdDispatchDate');
            if (elDispDate) elDispDate.textContent = dispDate;

            const dispBy = outward.created_by_name || batch.dispatched_by_name || 'Staff';
            const elDispBy = document.getElementById('vbdDispatchedBy');
            if (elDispBy) elDispBy.textContent = dispBy;

            // Status badge
            const statusBadge = document.getElementById('vbdStatusBadge');
            if (statusBadge) {
                const status = (batch.status || 'DISPATCHED').toUpperCase();
                statusBadge.textContent = status;
                if (status === 'RECEIVED') {
                    statusBadge.className = 'badge bg-info text-dark px-3 py-2 fs-7';
                } else {
                    statusBadge.className = 'badge bg-success px-3 py-2 fs-7';
                }
            }

            // Notes
            const notes = outward.notes || batch.outward_notes || '';
            const notesContainer = document.getElementById('vbdNotesContainer');
            const notesEl = document.getElementById('vbdNotes');
            if (notesContainer && notesEl) {
                if (notes.trim()) {
                    notesEl.textContent = notes;
                    notesContainer.style.display = 'block';
                } else {
                    notesContainer.style.display = 'none';
                }
            }

            // Variant Breakdown Table
            const tbody = document.getElementById('vbdItemsTableBody');
            if (tbody) {
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
                            <td><div class="d-flex flex-wrap gap-2 vbd-piece-qrs"></div></td>
                        `;
                        tbody.appendChild(row);

                        const qrList = row.querySelector('.vbd-piece-qrs');
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
                            qr.className = 'vbd-piece-qr mx-auto';
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
            }

            if (spinner) spinner.style.display = 'none';
            if (content) content.style.display = 'block';
        })
        .catch(err => {
            console.error('Batch dispatch modal error:', err);
            alert('Failed to fetch batch dispatch details: ' + (err.message || ''));
            modal.hide();
        });
}

let batchesPagination;
function initBatchesPagination() {
    const searchInput = document.getElementById('batchesSearchInput');
    const tableBody = document.getElementById('batchesTableBody');
    if (!tableBody) return;

    if (typeof window.initClientPagination === 'function') {
        batchesPagination = window.initClientPagination({
            container: '#batchesTableBody',
            itemSelector: 'tr.prod-batch-row',
            footer: '#batchesPaginationFooter',
            infoEl: '#batchesPageInfo',
            paginationList: '#batchesPaginationList',
            pageSizeSelect: '#batchesPageSizeSelect',
            emptyStateEl: '#noLiveBatchesResultsRow',
            pageSize: 10,
            itemName: 'batches'
        });
    }

    if (searchInput) {
        function filterRows() {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;
            const rows = tableBody.querySelectorAll('.prod-batch-row');
            const totalCount = rows.length;

            if (batchesPagination) {
                visibleCount = batchesPagination.filter(row => {
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

            let noResultRow = document.getElementById('noLiveBatchesResultsRow');
            if (totalCount > 0 && visibleCount === 0 && query !== '') {
                if (!noResultRow) {
                    noResultRow = document.createElement('tr');
                    noResultRow.id = 'noLiveBatchesResultsRow';
                    noResultRow.innerHTML = `<td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                        <h6 class="fw-bold text-secondary">No production batches matching "${escapeHtml(searchInput.value)}"</h6>
                        <small class="text-muted d-block mb-2">Try searching with a different keyword or clear the search filter.</small>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="clearBatchSearchLive()">Clear Search</button>
                    </td>`;
                    tableBody.appendChild(noResultRow);
                } else {
                    noResultRow.style.display = '';
                    const h6 = noResultRow.querySelector('h6');
                    if (h6) h6.textContent = `No production batches matching "${searchInput.value}"`;
                }
            } else if (noResultRow) {
                noResultRow.style.display = 'none';
            }
        }

        window.clearBatchSearchLive = function() {
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
    document.addEventListener('DOMContentLoaded', initBatchesPagination);
} else {
    initBatchesPagination();
}
</script>
