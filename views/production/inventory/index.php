<?php
// views/production/inventory/index.php
// Batch-wise inventory listing page
?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-box2-heart me-2 text-success"></i>Production Inventory &amp; QC Outcomes</h2>
        <span class="text-muted">QC-passed inventory, outward dispatches, rework, and rejected quantities shown separately</span>
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('batches', 'create')): ?>
        <a href="<?= url('production/batches/create') ?>" class="btn btn-success">
            <i class="bi bi-box-seam me-1"></i> Create Packing Batch
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-success bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">In-Stock Remaining</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($stats['total_remaining_pieces'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted d-block mt-1">Available in production unit</small>
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
                    <span class="text-uppercase text-muted fw-semibold small d-block">In-Stock Valuation</span>
                    <h3 class="fw-bold text-primary mb-0 mt-1">₹<?= number_format($stats['total_remaining_valuation'] ?? 0, 2) ?></h3>
                    <small class="text-muted d-block mt-1">Current available value</small>
                </div>
                <div class="bg-primary text-white rounded-3 p-3">
                    <i class="bi bi-currency-rupee fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm bg-danger bg-opacity-10 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-semibold small d-block">Outward Dispatched</span>
                    <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($stats['total_outwarded_pieces'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
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
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($stats['total_passed_pieces'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted d-block mt-1"><?= number_format($stats['unique_batches_count'] ?? 0) ?> Total QC Batches</small>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                            Rework: <?= number_format($stats['total_rework_pieces'] ?? 0) ?>
                        </span>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                            Rejected: <?= number_format($stats['total_rejected_pieces'] ?? 0) ?>
                        </span>
                    </div>
                </div>
                <div class="bg-info text-dark rounded-3 p-3">
                    <i class="bi bi-patch-check-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Search -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" action="<?= url('production/inventory') ?>" class="row g-3 align-items-end" id="inventoryFilterForm">
            <div class="col-md-5">
                <label for="inventorySearchInput" class="form-label small text-muted fw-semibold">Search Inventory</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="search" 
                           id="inventorySearchInput" 
                           class="form-control border-start-0" 
                           placeholder="Search product name, program #, batch #, size, color, outward #..." 
                           value="<?= e($search ?? '') ?>"
                           autocomplete="off">
                    <?php if (!empty($search)): ?>
                        <button type="button" class="btn btn-outline-secondary border-start-0" onclick="clearSearchInput()" title="Clear search">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <label for="inventoryCategorySelect" class="form-label small text-muted fw-semibold">Filter by Category</label>
                <select name="category_id" id="inventoryCategorySelect" class="form-select" onchange="document.getElementById('inventoryFilterForm').submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($categoryFilter == $cat['id']) ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($categoryFilter)): ?>
                    <a href="<?= url('production/inventory') ?>" class="btn btn-outline-danger" title="Reset All Filters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Batch Cards Grid Header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-semibold">
        <i class="bi bi-grid-1x2 me-2 text-primary"></i>
        Inventory Batches
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-2" id="totalBatchBadgeCount"><?= count($batches) ?> Batches</span>
    </h5>
    <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Click "View Products" to see individual garments, remaining stock &amp; outward dispatch history</span>
</div>

<?php if (empty($batches)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-box-seam fs-1 d-block mb-3 text-secondary"></i>
        <h6 class="fw-semibold mb-1"><?= (!empty($search) || !empty($categoryFilter)) ? 'No Matching Inventory Batches Found' : 'No QC Outcomes Found' ?></h6>
        <p class="small text-muted mb-3">
            <?= (!empty($search) || !empty($categoryFilter)) 
                ? 'Try adjusting your search terms or clearing the category filter.' 
                : 'Batches with passed, rework, or rejected QC quantities will appear here.' ?>
        </p>
        <?php if (!empty($search) || !empty($categoryFilter)): ?>
            <a href="<?= url('production/inventory') ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Clear All Filters
            </a>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<!-- Live filter empty state (hidden by default) -->
<div id="noMatchingBatchesAlert" class="card border-0 shadow-sm mb-3" style="display: none;">
    <div class="card-body text-center py-5">
        <i class="bi bi-search fs-1 d-block mb-3 text-muted"></i>
        <h6 class="fw-semibold mb-1">No Batches Match Your Search</h6>
        <p class="small text-muted mb-3">No inventory items matched the current filter keyword.</p>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="clearLiveFilter()">
            <i class="bi bi-x-circle me-1"></i> Clear Search
        </button>
    </div>
</div>

<div class="row g-3" id="inventoryBatchesGrid">
    <?php foreach ($batches as $idx => $batch): ?>
    <?php
        $batchDate = new DateTime($batch['batch_created_at'] ?? 'now');
        $batchColors = ['success', 'primary', 'info', 'warning', 'danger', 'secondary'];
        $colorClass  = $batchColors[$idx % count($batchColors)];
        
        $variantKeywords = implode(' ', array_map(function($v) {
            return ($v['size_name'] ?? '') . ' ' . ($v['color_name'] ?? '');
        }, $batch['variants'] ?? []));

        $outwardKeywords = '';
        if (!empty($batch['outward_history'])) {
            foreach ($batch['outward_history'] as $ow) {
                $outwardKeywords .= ' ' . ($ow['outward_number'] ?? '') . ' ' . ($ow['warehouse_name'] ?? '') . ' ' . ($ow['vehicle_number'] ?? '');
            }
        }
        
        $searchDataText = strtolower(
            ($batch['product_name'] ?? '') . ' ' . 
            ($batch['category_name'] ?? '') . ' ' . 
            ($batch['program_number'] ?? '') . ' ' . 
            ($batch['inspector_name'] ?? '') . ' ' . 
            'rework rejected qc passed outward ' .
            'qc batch #' . $batch['qc_batch_id'] . ' ' . 
            $batch['qc_batch_id'] . ' ' . 
            $variantKeywords . ' ' .
            $outwardKeywords
        );

        $stockStatus = $batch['stock_status'] ?? 'IN_STOCK';
    ?>
    <div class="col-xl-4 col-md-6 batch-card-col" 
         data-batch-id="<?= $batch['qc_batch_id'] ?>"
         data-category-id="<?= $batch['category_id'] ?? '' ?>"
         data-search-text="<?= e($searchDataText) ?>">
        <?php $statusColor = in_array($stockStatus, ['FULLY_OUTWARDED', 'NO_PASSED_STOCK'], true) ? 'secondary' : ($stockStatus === 'PARTIALLY_OUTWARDED' ? 'warning' : 'success'); ?>
        <div class="card border-0 shadow-sm h-100 inventory-batch-card" style="border-left: 4px solid var(--bs-<?= $statusColor ?>) !important; transition: transform 0.18s, box-shadow 0.18s;">
            <div class="card-body p-4">
                <!-- Batch Header -->
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6 fw-bold text-nowrap">
                                <i class="bi bi-patch-check-fill me-1"></i>QC Batch #<?= $batch['qc_batch_id'] ?>
                            </span>
                            <?php if ($stockStatus === 'NO_PASSED_STOCK'): ?>
                                <span class="badge bg-secondary-subtle text-secondary border small"><i class="bi bi-clipboard-x me-1"></i>No QC-Passed Stock</span>
                            <?php elseif ($stockStatus === 'FULLY_OUTWARDED'): ?>
                                <span class="badge bg-secondary-subtle text-secondary border small"><i class="bi bi-truck me-1"></i>Fully Outwarded</span>
                            <?php elseif ($stockStatus === 'PARTIALLY_OUTWARDED'): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small"><i class="bi bi-pie-chart me-1"></i>Partially Outwarded</span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle small"><i class="bi bi-check2-circle me-1"></i>In Stock</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted small mt-1">
                            <i class="bi bi-calendar3 me-1"></i><?= $batchDate->format('d M Y, g:i A') ?>
                        </div>
                    </div>
                    <div class="bg-<?= $statusColor ?> bg-opacity-10 rounded-3 p-2 text-<?= $statusColor ?>">
                        <i class="bi bi-boxes fs-4"></i>
                    </div>
                </div>

                <!-- Product Name -->
                <div class="mb-3">
                    <div class="fw-bold text-dark fs-6 mb-1"><?= e($batch['product_name']) ?></div>
                    <?php if (!empty($batch['category_name'])): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-7"><?= e($batch['category_name']) ?></span>
                    <?php endif; ?>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-diagram-3 me-1"></i>Program: 
                        <a href="<?= url('production/programs/view/' . $batch['program_id']) ?>" class="text-decoration-none fw-semibold">
                            <?= e($batch['program_number']) ?>
                        </a>
                    </div>
                </div>

                <!-- Variants Preview -->
                <?php if (!empty($batch['variants'])): ?>
                <div class="mb-3">
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach (array_slice($batch['variants'], 0, 4) as $v): ?>
                            <span class="badge bg-light text-dark border small">
                                <?= e($v['size_name']) ?>/<?= e($v['color_name']) ?>
                                <span class="text-success ms-1">(<?= $v['passed_qty'] ?>)</span>
                            </span>
                        <?php endforeach; ?>
                        <?php if (count($batch['variants']) > 4): ?>
                            <span class="badge bg-secondary-subtle text-secondary border small">+<?= count($batch['variants']) - 4 ?> more</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <hr class="my-3 opacity-25">

                <!-- Stats Grid (Remaining In-Stock vs Outwarded vs Total) -->
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <div class="bg-success bg-opacity-10 rounded-2 p-2 text-center h-100">
                            <div class="fw-bold text-success fs-5"><?= number_format($batch['remaining_pieces']) ?></div>
                            <div class="text-muted small" style="font-size: 11px;">In Stock (pcs)</div>
                        </div>

                    </div>
                    <div class="col-4">
                        <div class="bg-danger bg-opacity-10 rounded-2 p-2 text-center h-100">
                            <div class="fw-bold text-danger fs-5"><?= number_format($batch['total_outward_pieces']) ?></div>
                            <div class="text-muted small" style="font-size: 11px;">Outward (pcs)</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-light border rounded-2 p-2 text-center h-100">
                            <div class="fw-bold text-dark fs-5"><?= number_format($batch['total_passed_pieces']) ?></div>
                            <div class="text-muted small" style="font-size: 11px;">Total Passed</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3" aria-label="QC outcome quantities">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="bi bi-check-circle me-1"></i>QC Passed: <?= number_format($batch['total_passed_pieces']) ?>
                    </span>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                        <i class="bi bi-arrow-repeat me-1"></i>QC Rework: <?= number_format($batch['total_rework_pieces']) ?>
                    </span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                        <i class="bi bi-x-circle me-1"></i>QC Rejected: <?= number_format($batch['total_rejected_pieces']) ?>
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded border">
                    <span class="small text-muted fw-semibold">Available Valuation:</span>
                    <span class="fw-bold text-primary">₹<?= number_format($batch['remaining_value'], 2) ?></span>
                </div>

                <!-- Outward History Mini-Badge -->
                <?php if (!empty($batch['outward_history'])): ?>
                <div class="mb-3 p-2 bg-warning-subtle bg-opacity-25 rounded border border-warning-subtle">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small fw-semibold text-dark"><i class="bi bi-truck text-warning-emphasis me-1"></i>Outward Dispatched:</span>
                        <span class="badge bg-warning-subtle text-dark border small"><?= count($batch['outward_history']) ?> Dispatch(es)</span>
                    </div>
                    <div class="small text-muted mt-1 text-truncate">
                        <?php $firstOw = $batch['outward_history'][0]; ?>
                        <code><?= e($firstOw['outward_number']) ?></code> &rarr; <?= e($firstOw['warehouse_name'] ?? 'Warehouse') ?> (<?= $firstOw['outward_qty'] ?> pcs)
                    </div>
                </div>
                <?php endif; ?>

                <!-- Inspector -->
                <?php if (!empty($batch['inspector_name'])): ?>
                <div class="small text-muted mb-3">
                    <i class="bi bi-person-check me-1 text-success"></i>Inspector: <strong><?= e($batch['inspector_name']) ?></strong>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="d-flex gap-2">
                    <a href="<?= url('production/inventory/batch/' . $batch['qc_batch_id']) ?>" 
                       class="btn btn-<?= ($stockStatus === 'FULLY_OUTWARDED') ? 'outline-secondary' : $colorClass ?> btn-sm flex-grow-1 fw-semibold">
                        <i class="bi bi-eye me-1"></i> View Products &amp; History
                    </a>
                    <?php if (hasPermission('batches', 'create') && $batch['remaining_pieces'] > 0): ?>
                    <a href="<?= url('production/batches/create?qc_batch_id=' . $batch['qc_batch_id']) ?>" 
                       class="btn btn-outline-success btn-sm" title="Pack Remaining Stock into Box Batch">
                        <i class="bi bi-box-seam"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pagination Card Footer for Batches -->
<div class="card border-0 shadow-sm mt-4" id="batchPaginationContainer">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="batchPageInfo">
                Showing <?= min(9, count($batches)) ?> of <?= count($batches) ?> batches
            </span>
            <div class="d-flex align-items-center gap-2">
                <label for="batchPageSizeSelect" class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="batchPageSizeSelect" class="form-select form-select-sm" style="width: auto;" onchange="changeBatchPageSize(this.value)">
                    <option value="6">6 per page</option>
                    <option value="9" selected>9 per page</option>
                    <option value="18">18 per page</option>
                    <option value="all">All batches</option>
                </select>
            </div>
        </div>
        <nav aria-label="Batch Pagination">
            <ul class="pagination pagination-modern mb-0" id="batchPaginationList">
                <!-- Dynamically rendered -->
            </ul>
        </nav>
    </div>
</div>
<?php endif; ?>

<style>
.inventory-batch-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
}
</style>

<script>
let currentBatchPage = 1;
let batchPageSize = 9;
let visibleBatchCards = [];

function clearSearchInput() {
    const searchInput = document.getElementById('inventorySearchInput');
    if (searchInput) {
        searchInput.value = '';
        document.getElementById('inventoryFilterForm').submit();
    }
}

function clearLiveFilter() {
    const searchInput = document.getElementById('inventorySearchInput');
    if (searchInput) {
        searchInput.value = '';
        filterBatchesLive('');
    }
}

function filterBatchesLive(query) {
    const term = (query || '').trim().toLowerCase();
    const allCards = Array.from(document.querySelectorAll('.batch-card-col'));
    const alertEl = document.getElementById('noMatchingBatchesAlert');
    const paginationContainer = document.getElementById('batchPaginationContainer');
    const badgeEl = document.getElementById('totalBatchBadgeCount');

    visibleBatchCards = allCards.filter(card => {
        if (!term) return true;
        const searchText = (card.getAttribute('data-search-text') || '').toLowerCase();
        return searchText.includes(term);
    });

    if (badgeEl) {
        badgeEl.textContent = `${visibleBatchCards.length} Batches`;
    }

    if (visibleBatchCards.length === 0 && allCards.length > 0) {
        if (alertEl) alertEl.style.display = 'block';
        if (paginationContainer) paginationContainer.style.display = 'none';
        allCards.forEach(c => c.style.display = 'none');
        return;
    }

    if (alertEl) alertEl.style.display = 'none';
    if (paginationContainer) paginationContainer.style.display = '';

    currentBatchPage = 1;
    renderBatchPagination();
}

function changeBatchPageSize(newSize) {
    batchPageSize = (newSize === 'all') ? 'all' : parseInt(newSize, 10);
    currentBatchPage = 1;
    renderBatchPagination();
}

function goToBatchPage(page) {
    currentBatchPage = page;
    renderBatchPagination();
    const gridEl = document.getElementById('inventoryBatchesGrid');
    if (gridEl) gridEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function renderBatchPagination() {
    const allCards = Array.from(document.querySelectorAll('.batch-card-col'));
    if (allCards.length === 0) return;

    const cardsToPaginate = (visibleBatchCards.length > 0 || (document.getElementById('inventorySearchInput') && document.getElementById('inventorySearchInput').value.trim() === ''))
        ? (visibleBatchCards.length > 0 ? visibleBatchCards : allCards)
        : [];

    const totalBatches = cardsToPaginate.length;
    const isAll = (batchPageSize === 'all');
    const effectiveSize = isAll ? totalBatches : parseInt(batchPageSize, 10);
    const totalPages = isAll ? 1 : Math.ceil(totalBatches / effectiveSize) || 1;

    if (currentBatchPage > totalPages) currentBatchPage = totalPages;
    if (currentBatchPage < 1) currentBatchPage = 1;

    const startIdx = isAll ? 0 : (currentBatchPage - 1) * effectiveSize;
    const endIdx = isAll ? totalBatches : Math.min(startIdx + effectiveSize, totalBatches);

    // Hide all first
    allCards.forEach(card => card.style.display = 'none');

    // Show only the page slice of matching cards
    cardsToPaginate.forEach((card, idx) => {
        card.style.display = (idx >= startIdx && idx < endIdx) ? '' : 'none';
    });

    const infoEl = document.getElementById('batchPageInfo');
    if (infoEl) {
        if (totalBatches === 0) {
            infoEl.innerHTML = `Showing <strong>0</strong> batches`;
        } else {
            infoEl.innerHTML = `Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${totalBatches}</strong> batches`;
        }
    }

    const paginationList = document.getElementById('batchPaginationList');
    if (!paginationList) return;

    if (totalPages <= 1) {
        paginationList.innerHTML = `
            <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left me-1"></i> Prev</span></li>
            <li class="page-item active"><span class="page-link">1</span></li>
            <li class="page-item disabled"><span class="page-link">Next <i class="bi bi-chevron-right ms-1"></i></span></li>
        `;
        return;
    }

    let html = '';
    const prevDisabled = (currentBatchPage <= 1) ? 'disabled' : '';
    html += `<li class="page-item ${prevDisabled}">
        <a class="page-link" href="javascript:void(0)" onclick="${currentBatchPage > 1 ? `goToBatchPage(${currentBatchPage - 1})` : 'return false;'}" aria-label="Previous">
            <i class="bi bi-chevron-left me-1"></i> Prev
        </a>
    </li>`;

    for (let p = 1; p <= totalPages; p++) {
        const isActive = (p === currentBatchPage) ? 'active' : '';
        html += `<li class="page-item ${isActive}">
            <a class="page-link" href="javascript:void(0)" onclick="goToBatchPage(${p})">${p}</a>
        </li>`;
    }

    const nextDisabled = (currentBatchPage >= totalPages) ? 'disabled' : '';
    html += `<li class="page-item ${nextDisabled}">
        <a class="page-link" href="javascript:void(0)" onclick="${currentBatchPage < totalPages ? `goToBatchPage(${currentBatchPage + 1})` : 'return false;'}" aria-label="Next">
            Next <i class="bi bi-chevron-right ms-1"></i>
        </a>
    </li>`;

    paginationList.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    const allCards = Array.from(document.querySelectorAll('.batch-card-col'));
    visibleBatchCards = allCards;
    renderBatchPagination();

    const searchInput = document.getElementById('inventorySearchInput');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                filterBatchesLive(e.target.value);
            }, 150);
        });
    }
});
</script>
