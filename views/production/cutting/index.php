<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-scissors me-2 text-info"></i>Cutting Batches</h2>
        <span class="text-muted">Fabric laying, pattern cutting, material yield, and dynamic variant wastage tracking</span>
    </div>
    <?php if (hasPermission('cutting', 'create')): ?>
    <a href="<?= url('production/cutting/create') ?>" class="btn btn-primary px-3 py-2 fw-semibold">
        <i class="bi bi-plus-circle me-1"></i> New Cutting Batch
    </a>
    <?php endif; ?>
</div>

<!-- Summary Metrics Bar -->
<?php
    $totalBatches = count($batches);
    $totalCutPieces = 0;
    $totalFabricWastageKg = 0;
    $totalCostSum = 0;
    $validCostCount = 0;

    foreach ($batches as $b) {
        $totalCutPieces += (int)($b['cut_qty'] ?? 0);
        $totalFabricWastageKg += (float)($b['wastage_weight'] ?? 0);
        if ((float)($b['cost_per_piece'] ?? 0) > 0) {
            $totalCostSum += (float)$b['cost_per_piece'];
            $validCostCount++;
        }
    }
    $avgCostPerPiece = $validCostCount > 0 ? ($totalCostSum / $validCostCount) : 0;
?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 border-start border-4 border-primary">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Total Cutting Batches</div>
                        <div class="fs-4 fw-bold text-dark"><?= number_format($totalBatches) ?></div>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="bi bi-scissors fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Total Cut Output</div>
                        <div class="fs-4 fw-bold text-success"><?= number_format($totalCutPieces) ?> <small class="fs-6 text-muted">pcs</small></div>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                        <i class="bi bi-check2-all fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 border-start border-4 border-warning">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Fabric Scrap / Wastage</div>
                        <div class="fs-4 fw-bold text-warning"><?= number_format($totalFabricWastageKg, 2) ?> <small class="fs-6 text-muted">kg</small></div>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                        <i class="bi bi-trash3 fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 border-start border-4 border-info">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Avg Fabric Cost/Pc</div>
                        <div class="fs-4 fw-bold text-info">₹<?= number_format($avgCostPerPiece, 2) ?></div>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-circle">
                        <i class="bi bi-currency-rupee fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/cutting') ?>" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="IN_PROGRESS" <?= ($filters['status'] ?? '') === 'IN_PROGRESS' ? 'selected' : '' ?>>In Progress</option>
                    <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    <option value="COMPLETED" <?= ($filters['status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                    <option value="HOLD" <?= ($filters['status'] ?? '') === 'HOLD' ? 'selected' : '' ?>>On Hold</option>
                    <option value="CANCELLED" <?= ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label small text-muted fw-semibold">Search</label>
                <input type="text" name="search" id="cuttingSearchInput" class="form-control form-control-sm" placeholder="Cutting name, batch #, program #, product name, roll #, color, design..." value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-secondary btn-sm flex-fill" title="Filter"><i class="bi bi-filter me-1"></i> Filter</button>
                <?php if (!empty($filters['status']) || !empty($filters['search']) || !empty($filters['program_id'])): ?>
                    <a href="<?= url('production/cutting') ?>" class="btn btn-outline-danger btn-sm" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Batches Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="cuttingTable">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 170px;">Cutting Details</th>
                        <th style="min-width: 180px;">Program & Catalog Hierarchy</th>
                        <th style="min-width: 150px;">Fabric Roll Specs</th>
                        <th style="min-width: 140px;">Cut Qty</th>
                        <th style="min-width: 130px;">Scrap / Wastage</th>
                        <th style="min-width: 110px;">Fabric Cost</th>
                        <th>Status</th>
                        <th class="text-end" style="min-width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="cuttingTableBody">
                    <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-scissors fs-1 text-secondary d-block mb-3"></i>
                                <h5 class="fw-bold text-secondary">No Cutting Batches Found</h5>
                                <p class="text-muted mb-3">Start by creating a new cutting batch to process fabric rolls and pattern variants.</p>
                                <a href="<?= url('production/cutting/create') ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i> New Cutting Batch
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($batches as $b): ?>
                            <?php 
                                $statusClass = match($b['status']) {
                                    'COMPLETED'   => 'bg-success',
                                    'IN_PROGRESS' => 'bg-warning text-dark',
                                    default       => 'bg-secondary'
                                };

                                // Build Category Path
                                $catParts = array_filter([
                                    $b['category_name'] ?? '',
                                    $b['subcategory_name'] ?? '',
                                    $b['sub_subcategory_name'] ?? ''
                                ]);
                                $catBreadcrumb = !empty($catParts) ? implode(' › ', $catParts) : ($b['product_name'] ?? 'General Catalog');

                                $rollWeight = (float)($b['roll_weight'] ?? 0);
                                $wastageWeight = (float)($b['wastage_weight'] ?? 0);
                                $wastagePct = $rollWeight > 0 ? (($wastageWeight / $rollWeight) * 100) : 0;
                            ?>
                            <tr class="cutting-table-row">
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= e($b['name'] ?: $b['batch_number']) ?></div>
                                    <div class="small font-monospace text-primary"><?= e($b['batch_number']) ?></div>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= formatDate($b['created_at']) ?></small>
                                </td>
                                <td>
                                    <a href="<?= url('production/programs/view/' . $b['program_id']) ?>" class="fw-semibold text-decoration-none text-dark">
                                        <i class="bi bi-file-earmark-text text-primary me-1"></i><?= e($b['program_number']) ?>
                                    </a>
                                    <div class="small text-muted"><?= e($b['product_name'] ?: 'Apparel Product') ?></div>
                                    <div class="mt-1">
                                        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.72rem;">
                                            <?= e($catBreadcrumb) ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $cRolls = $cuttingRollsMap[$b['id']] ?? [];
                                        if (empty($cRolls) && (!empty($b['roll_number']) || !empty($b['seller_roll_id']))) {
                                            $cRolls = [[
                                                'seller_roll_id' => $b['seller_roll_id'] ?? null,
                                                'roll_number'    => $b['roll_number'] ?? ('Roll #' . ($b['seller_roll_id'] ?? '')),
                                                'roll_color'     => $b['roll_color'] ?? '',
                                                'roll_design'    => $b['roll_design'] ?? '',
                                                'roll_weight'    => $b['roll_weight'] ?? 0,
                                                'roll_price'     => $b['roll_price'] ?? 0,
                                            ]];
                                        }
                                    ?>
                                    <?php if (!empty($cRolls)): ?>
                                        <?php if (count($cRolls) === 1): ?>
                                             <?php $r = $cRolls[0]; ?>
                                            <div class="fw-semibold text-dark">
                                                <i class="bi bi-circle-square text-info me-1"></i><?= e(!empty($r['roll_number']) ? $r['roll_number'] : ('Roll #' . ($r['seller_roll_id'] ?? ''))) ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= e($r['roll_color'] ?: 'Standard') ?> &bull; <?= e($r['roll_design'] ?: 'Plain') ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= number_format($rollWeight, 2) ?> kg / ₹<?= number_format((float)($b['roll_price'] ?? 0), 2) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="fw-semibold text-dark mb-1">
                                                <i class="bi bi-layers-fill text-info me-1"></i><?= count($cRolls) ?> Rolls Linked
                                            </div>
                                            <div class="d-flex flex-wrap gap-1 mb-1">
                                                <?php foreach ($cRolls as $r): ?>
                                                    <span class="badge bg-light text-dark border small" title="<?= e($r['seller_name'] ?? 'Supplier') ?> - <?= number_format((float)($r['roll_weight'] ?? 0), 2) ?> kg">
                                                        <?= e(!empty($r['roll_number']) ? $r['roll_number'] : ('#' . ($r['seller_roll_id'] ?? ''))) ?>
                                                        <?php if (!empty($r['roll_color'])): ?> (<?= e($r['roll_color']) ?>)<?php endif; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="small text-muted">
                                                Total: <?= number_format($rollWeight, 2) ?> kg / ₹<?= number_format((float)($b['roll_price'] ?? 0), 2) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">No roll linked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-success fs-6"><?= number_format((int)$b['cut_qty']) ?> <small class="text-muted">pcs</small></div>
                                    <div class="small text-muted">
                                        <?php if ((int)$b['rejected_qty'] > 0): ?>
                                             <span class="text-danger"><i class="bi bi-x-circle me-1"></i><?= number_format((int)$b['rejected_qty']) ?> rej</span>
                                        <?php endif; ?>
                                        <?php if ((int)$b['wastage_qty'] > 0): ?>
                                            <span class="text-warning ms-1"><i class="bi bi-trash3 me-1"></i><?= number_format((int)$b['wastage_qty']) ?> scrap</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div><strong class="text-danger"><?= number_format($wastageWeight, 2) ?></strong> <small class="text-muted">kg</small></div>
                                    <?php 
                                        $wCost = (float)($b['wastage_cost'] ?? 0);
                                        if ($wCost <= 0 && $rollWeight > 0) {
                                            $wCost = $wastageWeight * ((float)($b['roll_price'] ?? 0) / $rollWeight);
                                        }
                                    ?>
                                    <div class="small text-danger fw-semibold">
                                        ₹<?= number_format($wCost, 2) ?> <span class="text-muted fw-normal">(<?= number_format($wastagePct, 1) ?>%)</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary">₹<?= number_format((float)($b['cost_per_piece'] ?? 0), 2) ?></div>
                                    <small class="text-muted">per cut piece</small>
                                </td>
                                <td>
                                    <span class="badge <?= $statusClass ?>"><?= e($b['status']) ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('production/cutting/view/' . $b['id']) ?>" class="btn btn-outline-info" title="View Cutting Batch">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('cutting', 'edit')): ?>
                                        <a href="<?= url('production/cutting/edit/' . $b['id']) ?>" class="btn btn-outline-primary" title="Edit Batch">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('stitching', 'create')): ?>
                                        <a href="<?= url('production/stitching/create?cutting_batch_id=' . $b['id']) ?>" class="btn btn-outline-warning" title="Create Stitching Job">
                                            <i class="bi bi-threads"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('cutting', 'delete')): ?>
                                        <form method="POST" action="<?= url('production/cutting/delete/' . $b['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Cutting Batch \'<?= e(addslashes($b['name'] ?: $b['batch_number'])) ?>\'?');">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-outline-danger" title="Delete Cutting Batch">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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
    <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="cuttingPaginationFooter">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="cuttingPageInfo">
                Showing 1 to 10 of <?= count($batches) ?> cutting batches
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="cuttingPageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                    <option value="10" selected>10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <nav aria-label="Cutting Batches Pagination">
            <ul class="pagination pagination-modern mb-0" id="cuttingPaginationList">
            </ul>
        </nav>
    </div>
</div>

<script>
let cuttingPagination;
function initCuttingPagination() {
    const searchInput = document.getElementById('cuttingSearchInput');
    const tableBody = document.getElementById('cuttingTableBody');
    if (!tableBody) return;

    if (typeof window.initClientPagination === 'function') {
        cuttingPagination = window.initClientPagination({
            container: '#cuttingTableBody',
            itemSelector: 'tr.cutting-table-row',
            footer: '#cuttingPaginationFooter',
            infoEl: '#cuttingPageInfo',
            paginationList: '#cuttingPaginationList',
            pageSizeSelect: '#cuttingPageSizeSelect',
            emptyStateEl: '#noLiveCuttingResultsRow',
            pageSize: 10,
            itemName: 'cutting batches'
        });
    }

    if (searchInput) {
        function filterRows() {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;
            const rows = tableBody.querySelectorAll('tr.cutting-table-row');
            const totalCount = rows.length;

            if (cuttingPagination) {
                visibleCount = cuttingPagination.filter(row => {
                    if (!query) return true;
                    const text = (row.textContent || '').toLowerCase();
                    return text.includes(query);
                });
            } else {
                rows.forEach(function(row) {
                    const text = (row.textContent || '').toLowerCase();
                    if (!query || text.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            let noResultRow = document.getElementById('noLiveCuttingResultsRow');
            if (totalCount > 0 && visibleCount === 0 && query !== '') {
                if (!noResultRow) {
                    noResultRow = document.createElement('tr');
                    noResultRow.id = 'noLiveCuttingResultsRow';
                    noResultRow.innerHTML = `<td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                        <h6 class="fw-bold text-secondary">No cutting batches matching "${escapeHtml(searchInput.value)}"</h6>
                        <small class="text-muted">Try searching with a different keyword or clear the search filter.</small>
                    </td>`;
                    tableBody.appendChild(noResultRow);
                } else {
                    noResultRow.style.display = '';
                    const h6 = noResultRow.querySelector('h6');
                    if (h6) h6.textContent = `No cutting batches matching "${searchInput.value}"`;
                }
            } else if (noResultRow) {
                noResultRow.style.display = 'none';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        searchInput.addEventListener('input', filterRows);
        searchInput.addEventListener('keyup', filterRows);
        if (searchInput.value.trim() !== '') {
            filterRows();
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCuttingPagination);
} else {
    initCuttingPagination();
}
</script>
