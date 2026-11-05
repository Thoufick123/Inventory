<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-threads me-2 text-warning"></i>Stitching Batches</h2>
        <span class="text-muted">Sewing lines, worker assignment, piece assembly, weights, and rework tracking</span>
    </div>
    <?php if (hasPermission('stitching', 'create')): ?>
    <a href="<?= url('production/stitching/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Stitching Batch
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/stitching') ?>" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    <option value="IN_PROGRESS" <?= ($filters['status'] ?? '') === 'IN_PROGRESS' ? 'selected' : '' ?>>In Progress</option>
                    <option value="COMPLETED" <?= ($filters['status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                    <option value="CANCELLED" <?= ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label small text-muted fw-semibold">Search</label>
                <input type="text" name="search" id="stitchingSearchInput" class="form-control form-control-sm" placeholder="Stitching Name, Batch #, Program #, Product, Worker..." value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-secondary btn-sm flex-fill"><i class="bi bi-filter me-1"></i> Filter</button>
                <?php if (!empty($filters['status']) || !empty($filters['search']) || !empty($filters['program_id']) || !empty($filters['cutting_batch_id'])): ?>
                    <a href="<?= url('production/stitching') ?>" class="btn btn-outline-danger btn-sm" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Stitching Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="stitchingTable">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 180px;">Batch / Stitching Name</th>
                        <th>Program</th>
                        <th>Catalog</th>
                        <th>Worker / Line</th>
                        <th>Cut vs Stitched Qty</th>
                        <th>Total Weight</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="stitchingTableBody">
                    <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> No stitching batches found.
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
                                $catParts = array_filter([
                                    $b['category_name'] ?? '',
                                    $b['subcategory_name'] ?? '',
                                    $b['sub_subcategory_name'] ?? ''
                                ]);
                                $catText = !empty($catParts) ? implode(' › ', $catParts) : ($b['product_name'] ?? 'General Apparel');
                            ?>
                            <tr class="stitching-table-row">
                                <td>
                                    <div class="fw-bold text-dark"><?= e($b['name'] ?: ($b['batch_number'] ?: ('Batch #' . $b['id']))) ?></div>
                                    <small class="text-muted"><code><?= e($b['batch_number'] ?: ('STCH-' . $b['id'])) ?></code> &bull; <?= formatDate($b['created_at']) ?></small>
                                </td>
                                <td>
                                    <a href="<?= url('production/programs/view/' . $b['program_id']) ?>" class="text-decoration-none fw-semibold">
                                        <?= e($b['program_number']) ?>
                                    </a>
                                    <div class="small text-muted"><?= e($b['program_name'] ?: $b['product_name']) ?></div>
                                    <?php if (!empty($b['cutting_batch_number']) || !empty($b['cutting_name'])): ?>
                                        <div class="small text-primary mt-1"><i class="bi bi-scissors me-1"></i><?= e($b['cutting_name'] ?: $b['cutting_batch_number']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($catText) ?></span>
                                </td>
                                <td><?= e($b['worker_name'] ?: 'Unassigned') ?></td>
                                <td>
                                    <div class="fw-bold text-success fs-6"><?= number_format($b['stitched_qty']) ?> Stitched</div>
                                    <small class="text-primary"><i class="bi bi-scissors me-1"></i>Cut: <?= number_format($b['received_qty'] ?? 0) ?> pcs</small>
                                    <?php if (!empty($b['rejected_qty']) || !empty($b['rework_qty'])): ?>
                                        <div class="small text-muted">
                                            <?php if (!empty($b['rejected_qty'])): ?><span class="text-danger"><?= $b['rejected_qty'] ?> rej</span> <?php endif; ?>
                                            <?php if (!empty($b['rework_qty'])): ?><span class="text-warning"><?= $b['rework_qty'] ?> rew</span><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold text-info fs-6"><?= number_format($b['total_weight'] ?? 0, 2) ?> kg</span>
                                </td>
                                <td><span class="badge <?= $statusClass ?>"><?= e($b['status']) ?></span></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <a href="<?= url('production/stitching/view/' . $b['id']) ?>" class="btn btn-sm btn-outline-info" title="View Stitching Batch">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('stitching', 'edit')): ?>
                                        <a href="<?= url('production/stitching/edit/' . $b['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit Stitching Batch">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($b['status'] === 'COMPLETED' && hasPermission('qc', 'create')): ?>
                                            <a href="<?= url('production/qc/create?stitching_batch_id=' . $b['id']) ?>" class="btn btn-sm btn-outline-success" title="Send to QC Check">
                                                <i class="bi bi-patch-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('stitching', 'delete')): ?>
                                        <form method="POST" action="<?= url('production/stitching/delete/' . $b['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this stitching batch?');">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
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
    <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="stitchingPaginationFooter">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="stitchingPageInfo">
                Showing 1 to 10 of <?= count($batches) ?> stitching batches
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="stitchingPageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                    <option value="10" selected>10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <nav aria-label="Stitching Batches Pagination">
            <ul class="pagination pagination-modern mb-0" id="stitchingPaginationList">
            </ul>
        </nav>
    </div>
</div>

<script>
let stitchingPagination;
function initStitchingPagination() {
    const searchInput = document.getElementById('stitchingSearchInput');
    const tableBody = document.getElementById('stitchingTableBody');
    if (!tableBody) return;

    if (typeof window.initClientPagination === 'function') {
        stitchingPagination = window.initClientPagination({
            container: '#stitchingTableBody',
            itemSelector: 'tr.stitching-table-row',
            footer: '#stitchingPaginationFooter',
            infoEl: '#stitchingPageInfo',
            paginationList: '#stitchingPaginationList',
            pageSizeSelect: '#stitchingPageSizeSelect',
            emptyStateEl: '#noLiveStitchingResultsRow',
            pageSize: 10,
            itemName: 'stitching batches'
        });
    }

    if (searchInput) {
        function filterRows() {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;
            const rows = tableBody.querySelectorAll('tr.stitching-table-row');
            const totalCount = rows.length;

            if (stitchingPagination) {
                visibleCount = stitchingPagination.filter(row => {
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

            let noResultRow = document.getElementById('noLiveStitchingResultsRow');
            if (totalCount > 0 && visibleCount === 0 && query !== '') {
                if (!noResultRow) {
                    noResultRow = document.createElement('tr');
                    noResultRow.id = 'noLiveStitchingResultsRow';
                    noResultRow.innerHTML = `<td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                        <h6 class="fw-bold text-secondary">No stitching batches matching "${escapeHtml(searchInput.value)}"</h6>
                        <small class="text-muted">Try searching with a different keyword or clear the search filter.</small>
                    </td>`;
                    tableBody.appendChild(noResultRow);
                } else {
                    noResultRow.style.display = '';
                    const h6 = noResultRow.querySelector('h6');
                    if (h6) h6.textContent = `No stitching batches matching "${searchInput.value}"`;
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
    document.addEventListener('DOMContentLoaded', initStitchingPagination);
} else {
    initStitchingPagination();
}
</script>
