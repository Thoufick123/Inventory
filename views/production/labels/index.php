<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-tags me-2 text-secondary"></i>Label Batches</h2>
        <span class="text-muted">Barcode tags, brand labels, care wash tags printing and application verification</span>
    </div>
    <?php if (hasPermission('labels', 'create')): ?>
    <a href="<?= url('production/labels/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Label Batch
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/labels') ?>" id="labelFilterForm" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small text-muted fw-semibold">Status</label>
                <select name="status" id="labelStatusFilter" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    <option value="IN_PROGRESS" <?= ($filters['status'] ?? '') === 'IN_PROGRESS' ? 'selected' : '' ?>>In Progress</option>
                    <option value="COMPLETED" <?= ($filters['status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small text-muted fw-semibold">Search</label>
                <input type="text" name="search" id="labelSearchInput" class="form-control form-control-sm" placeholder="Batch #, Program #, Product name, Code..." value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-secondary btn-sm flex-fill" title="Filter"><i class="bi bi-filter me-1"></i> Filter</button>
                <?php if (!empty($filters['status']) || !empty($filters['search']) || !empty($filters['program_id'])): ?>
                    <a href="<?= url('production/labels') ?>" class="btn btn-outline-danger btn-sm" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Labels Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="labelBatchesTable">
                <thead class="table-light">
                    <tr>
                        <th>Batch #</th>
                        <th>Program</th>
                        <th>Product</th>
                        <th>Required Labels</th>
                        <th>Printed</th>
                        <th>Used / Applied</th>
                        <th>Damaged</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="labelTableBody">
                    <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i> No label batches found.
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
                            ?>
                            <tr class="label-batch-row">
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
                                    <div class="fw-semibold text-dark"><?= e($b['product_name'] ?: ($b['program_name'] ?? '—')) ?></div>
                                    <?php if (!empty($b['product_code'])): ?>
                                        <small class="text-muted font-monospace"><?= e($b['product_code']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="fw-semibold"><?= number_format($b['required_qty']) ?></span> pcs</td>
                                <td class="text-primary fw-bold"><?= number_format($b['printed_qty']) ?> pcs</td>
                                <td class="text-success fw-bold"><?= number_format($b['used_qty']) ?> pcs</td>
                                <td class="text-danger"><?= number_format($b['damaged_qty']) ?> pcs</td>
                                <td><span class="badge <?= $statusClass ?>"><?= e($b['status']) ?></span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('production/labels/view/' . $b['id']) ?>" class="btn btn-outline-info" title="View Label Batch">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('labels', 'edit')): ?>
                                        <a href="<?= url('production/labels/edit/' . $b['id']) ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('labels', 'delete')): ?>
                                        <form method="POST" action="<?= url('production/labels/delete/' . $b['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Label Batch <?= e(addslashes($b['batch_number'] ?: ('#' . $b['id']))) ?>? This cannot be undone.');">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-outline-danger" title="Delete Label Batch">
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
    <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="labelsPaginationFooter">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="labelsPageInfo">
                Showing 1 to 10 of <?= count($batches) ?> label batches
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="labelsPageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                    <option value="10" selected>10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <nav aria-label="Label Batches Pagination">
            <ul class="pagination pagination-modern mb-0" id="labelsPaginationList">
            </ul>
        </nav>
    </div>
</div>

<script>
let labelsPagination;
function initLabelsPagination() {
    const searchInput = document.getElementById('labelSearchInput');
    const tableBody = document.getElementById('labelTableBody');
    if (!tableBody) return;

    if (typeof window.initClientPagination === 'function') {
        labelsPagination = window.initClientPagination({
            container: '#labelTableBody',
            itemSelector: 'tr.label-batch-row',
            footer: '#labelsPaginationFooter',
            infoEl: '#labelsPageInfo',
            paginationList: '#labelsPaginationList',
            pageSizeSelect: '#labelsPageSizeSelect',
            emptyStateEl: '#noLiveLabelResultsRow',
            pageSize: 10,
            itemName: 'label batches'
        });
    }

    if (searchInput) {
        function filterRows() {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;
            const rows = tableBody.querySelectorAll('.label-batch-row');
            const totalCount = rows.length;

            if (labelsPagination) {
                visibleCount = labelsPagination.filter(row => {
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

            let noResultRow = document.getElementById('noLiveLabelResultsRow');
            if (totalCount > 0 && visibleCount === 0 && query !== '') {
                if (!noResultRow) {
                    noResultRow = document.createElement('tr');
                    noResultRow.id = 'noLiveLabelResultsRow';
                    noResultRow.innerHTML = `<td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                        <h6 class="fw-bold text-secondary">No label batches matching "${escapeHtml(searchInput.value)}"</h6>
                        <small class="text-muted">Try searching with a different keyword or clear the search filter.</small>
                    </td>`;
                    tableBody.appendChild(noResultRow);
                } else {
                    noResultRow.style.display = '';
                    const h6 = noResultRow.querySelector('h6');
                    if (h6) h6.textContent = `No label batches matching "${searchInput.value}"`;
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
    document.addEventListener('DOMContentLoaded', initLabelsPagination);
} else {
    initLabelsPagination();
}
</script>
