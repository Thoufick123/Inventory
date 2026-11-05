<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-patch-check me-2 text-danger"></i>Quality Control (QC) Inspections</h2>
        <span class="text-muted">Multi-checkpoint inspection (stitching, fabric, color, size, trims) & finished goods approval</span>
    </div>
    <?php if (hasPermission('qc', 'create')): ?>
    <a href="<?= url('production/qc/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New QC Batch
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/qc') ?>" id="qcFilterForm" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small text-muted fw-semibold">Inspection Status</label>
                <select name="status" id="qcStatusFilter" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    <option value="PASSED" <?= ($filters['status'] ?? '') === 'PASSED' ? 'selected' : '' ?>>Passed (100%)</option>
                    <option value="PARTIAL" <?= ($filters['status'] ?? '') === 'PARTIAL' ? 'selected' : '' ?>>Partial Pass</option>
                    <option value="REWORK" <?= ($filters['status'] ?? '') === 'REWORK' ? 'selected' : '' ?>>Rework Required</option>
                    <option value="REJECTED" <?= ($filters['status'] ?? '') === 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small text-muted fw-semibold">Search</label>
                <input type="text" name="search" id="qcSearchInput" class="form-control form-control-sm" placeholder="QC #, Program #, Product name, Inspector..." value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-secondary btn-sm flex-fill" title="Filter"><i class="bi bi-filter me-1"></i> Filter</button>
                <?php if (!empty($filters['status']) || !empty($filters['search']) || !empty($filters['program_id'])): ?>
                    <a href="<?= url('production/qc') ?>" class="btn btn-outline-danger btn-sm" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- QC Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="qcBatchesTable">
                <thead class="table-light">
                    <tr>
                        <th>QC Batch ID</th>
                        <th>Program</th>
                        <th>Product</th>
                        <th>Inspector</th>
                        <th>Inspected</th>
                        <th>Passed</th>
                        <th>Rejected</th>
                        <th>Rework</th>
                        <th>Status</th>
                        <th class="text-center qc-actions-column">Actions</th>
                    </tr>
                </thead>
                <tbody id="qcTableBody">
                    <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-funnel fs-2 d-block mb-2 text-secondary"></i>
                                <?php if (!empty($filters['status']) || !empty($filters['search'])): ?>
                                    <h6 class="fw-bold text-dark">No QC inspections found matching your filter criteria.</h6>
                                    <p class="small text-muted mb-3">
                                        <?= !empty($filters['status']) ? 'Status: <strong>' . e($filters['status']) . '</strong> ' : '' ?>
                                        <?= !empty($filters['search']) ? 'Search: "<strong>' . e($filters['search']) . '</strong>"' : '' ?>
                                    </p>
                                    <a href="<?= url('production/qc') ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-x-circle me-1"></i> Clear Filters &amp; Show All
                                    </a>
                                <?php else: ?>
                                    <h6 class="fw-bold text-dark">No QC inspections recorded.</h6>
                                    <p class="small text-muted mb-3">Start by creating a new QC batch from completed stitching or labeling.</p>
                                    <?php if (hasPermission('qc', 'create')): ?>
                                    <a href="<?= url('production/qc/create') ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i> New QC Batch
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($batches as $b): ?>
                            <?php 
                                $statusClass = match($b['status']) {
                                    'PASSED'    => 'bg-success',
                                    'REJECTED'  => 'bg-danger',
                                    'REWORK'    => 'bg-warning text-dark',
                                    'PARTIAL'   => 'bg-info text-dark',
                                    'COMPLETED' => 'bg-primary',
                                    default     => 'bg-secondary'
                                };
                            ?>
                            <tr class="qc-batch-row">
                                <td>
                                    <div class="fw-bold text-dark">QC #<?= $b['id'] ?></div>
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
                                <td><?= e($b['inspector_name'] ?: 'Unassigned') ?></td>
                                <td><span class="fw-semibold"><?= number_format($b['total_qty']) ?></span> pcs</td>
                                <td class="text-success fw-bold"><?= number_format($b['passed_qty']) ?> pcs</td>
                                <td class="text-danger fw-semibold"><?= number_format($b['rejected_qty']) ?> pcs</td>
                                <td class="text-warning fw-semibold"><?= number_format($b['rework_qty']) ?> pcs</td>
                                <td><span class="badge <?= $statusClass ?>"><?= e($b['status']) ?></span></td>
                                <td class="text-center">
                                    <div class="qc-action-group" role="group" aria-label="QC batch actions">
                                        <a href="<?= url('production/qc/view/' . $b['id']) ?>" class="btn btn-outline-info qc-action-btn" title="View QC Details">
                                            <i class="bi bi-eye"></i><span>View</span>
                                        </a>
                                        <?php if (hasPermission('qc', 'edit')): ?>
                                            <?php if ($b['rework_qty'] > 0 || $b['rejected_qty'] > 0): ?>
                                                <a href="<?= url('production/qc/inspect/' . $b['id']) ?>" class="btn btn-warning text-dark fw-semibold qc-action-btn" title="Re-check Rework &amp; Rejected Products">
                                                    <i class="bi bi-arrow-repeat"></i><span>Re-check <small>(<?= ($b['rework_qty'] + $b['rejected_qty']) ?>)</small></span>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= url('production/qc/inspect/' . $b['id']) ?>" class="btn btn-outline-primary qc-action-btn" title="Inspect &amp; Record Defects">
                                                    <i class="bi bi-clipboard-check"></i><span>Inspect</span>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (hasPermission('batches', 'create')): ?>
                                            <?php if (($b['remaining_qty'] ?? $b['passed_qty']) > 0): ?>
                                                <a href="<?= url('production/batches/create?qc_batch_id=' . $b['id']) ?>" class="btn btn-outline-success qc-action-btn" title="Pack Passed Items into Box Batch (<?= $b['remaining_qty'] ?? $b['passed_qty'] ?> pcs remaining)">
                                                    <i class="bi bi-box-seam"></i><span>Pack</span>
                                                </a>
                                            <?php elseif (!empty($b['already_packed_qty']) && (int)$b['already_packed_qty'] > 0): ?>
                                                <a href="<?= url('production/batches?search=' . urlencode($b['packed_batch_number'] ?? $b['program_number'] ?? '')) ?>" class="btn btn-outline-secondary qc-action-btn" title="Already Packed into Batch #<?= e($b['packed_batch_number'] ?? '') ?> (<?= $b['already_packed_qty'] ?> pcs)">
                                                    <i class="bi bi-check2-all"></i><span>Packed</span>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (hasPermission('qc', 'delete')): ?>
                                        <form method="POST" action="<?= url('production/qc/delete/' . $b['id']) ?>" class="qc-action-form" onsubmit="return confirm('Are you sure you want to delete QC Inspection Batch #<?= $b['id'] ?>? This cannot be undone.');">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-outline-danger qc-action-btn" title="Delete QC Batch">
                                                <i class="bi bi-trash"></i><span>Delete</span>
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
    <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="qcPaginationFooter">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="qcPageInfo">
                Showing 1 to 10 of <?= count($batches) ?> inspections
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="qcPageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                    <option value="10" selected>10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <nav aria-label="QC Inspections Pagination">
            <ul class="pagination pagination-modern mb-0" id="qcPaginationList">
            </ul>
        </nav>
    </div>
</div>

<script>
let qcPagination;
function initQCPagination() {
    const searchInput = document.getElementById('qcSearchInput');
    const tableBody = document.getElementById('qcTableBody');
    if (!tableBody) return;

    if (typeof window.initClientPagination === 'function') {
        qcPagination = window.initClientPagination({
            container: '#qcTableBody',
            itemSelector: 'tr.qc-batch-row',
            footer: '#qcPaginationFooter',
            infoEl: '#qcPageInfo',
            paginationList: '#qcPaginationList',
            pageSizeSelect: '#qcPageSizeSelect',
            emptyStateEl: '#noLiveQCResultsRow',
            pageSize: 10,
            itemName: 'inspections'
        });
    }

    if (searchInput) {
        function filterRows() {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;
            const rows = tableBody.querySelectorAll('tr.qc-batch-row');
            const totalCount = rows.length;

            if (qcPagination) {
                visibleCount = qcPagination.filter(row => {
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

            let noResultRow = document.getElementById('noLiveQCResultsRow');
            if (totalCount > 0 && visibleCount === 0 && query !== '') {
                if (!noResultRow) {
                    noResultRow = document.createElement('tr');
                    noResultRow.id = 'noLiveQCResultsRow';
                    noResultRow.innerHTML = `<td colspan="10" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                        <h6 class="fw-bold text-secondary">No QC inspections matching "${escapeHtml(searchInput.value)}"</h6>
                        <small class="text-muted">Try searching with a different keyword or clear the search filter.</small>
                    </td>`;
                    tableBody.appendChild(noResultRow);
                } else {
                    noResultRow.style.display = '';
                    const h6 = noResultRow.querySelector('h6');
                    if (h6) h6.textContent = `No QC inspections matching "${searchInput.value}"`;
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
    document.addEventListener('DOMContentLoaded', initQCPagination);
} else {
    initQCPagination();
}
</script>
<style>
.qc-actions-column {
    min-width: 290px;
}

.qc-action-group {
    display: inline-flex;
    align-items: stretch;
    justify-content: center;
    gap: .35rem;
    white-space: nowrap;
}

.qc-action-form {
    display: flex;
}

.qc-action-btn {
    width: 68px;
    min-height: 60px;
    padding: .35rem .2rem;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .25rem;
    line-height: 1.1;
    white-space: normal;
}

.qc-action-btn > i {
    font-size: 1rem;
}

.qc-action-btn > span {
    font-size: .72rem;
}

.qc-action-btn small {
    font-size: .68rem;
}
</style>
