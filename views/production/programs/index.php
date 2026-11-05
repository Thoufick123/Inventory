<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1 fw-bold text-dark"><i class="bi bi-kanban me-2 text-primary"></i>Production Programs</h2>
        <span class="text-muted">Manufacturing orders, raw material rolls, 3-tier category hierarchy, and timeline schedules</span>
    </div>
    <?php if (hasPermission('programs', 'create')): ?>
    <a href="<?= url('production/programs/create') ?>" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-circle me-1"></i> Create Program
    </a>
    <?php endif; ?>
</div>

<!-- Filters Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/programs') ?>" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="DRAFT" <?= ($filters['status'] ?? '') === 'DRAFT' ? 'selected' : '' ?>>Draft</option>
                    <option value="APPROVED" <?= ($filters['status'] ?? '') === 'APPROVED' ? 'selected' : '' ?>>Approved</option>
                    <option value="SCHEDULED" <?= ($filters['status'] ?? '') === 'SCHEDULED' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="IN_PROGRESS" <?= ($filters['status'] ?? '') === 'IN_PROGRESS' ? 'selected' : '' ?>>In Progress</option>
                    <option value="COMPLETED" <?= ($filters['status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                    <option value="HOLD" <?= ($filters['status'] ?? '') === 'HOLD' ? 'selected' : '' ?>>On Hold</option>
                    <option value="CANCELLED" <?= ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted fw-semibold">Production Unit</label>
                <?php if (!empty($scopedUnitId)): 
                    $scopedUnitName = $units[0]['name'] ?? 'Assigned Unit';
                ?>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-primary border-end-0"><i class="bi bi-buildings-fill"></i></span>
                        <input type="text" class="form-control form-control-sm bg-light border-start-0 fw-semibold text-dark" value="<?= e($scopedUnitName) ?>" readonly>
                        <span class="input-group-text bg-light text-muted small border-start-0" title="Locked to your assigned unit"><i class="bi bi-lock-fill"></i></span>
                    </div>
                <?php else: ?>
                    <select name="unit_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Units</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filters['production_unit_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= e($u['name']) ?> (<?= e($u['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted fw-semibold">Priority</label>
                <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    <option value="LOW" <?= ($filters['priority'] ?? '') === 'LOW' ? 'selected' : '' ?>>Low</option>
                    <option value="MEDIUM" <?= ($filters['priority'] ?? '') === 'MEDIUM' ? 'selected' : '' ?>>Medium</option>
                    <option value="HIGH" <?= ($filters['priority'] ?? '') === 'HIGH' ? 'selected' : '' ?>>High</option>
                    <option value="URGENT" <?= ($filters['priority'] ?? '') === 'URGENT' ? 'selected' : '' ?>>Urgent</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted fw-semibold">Search</label>
                <input type="text" name="search" id="programsSearchInput" class="form-control form-control-sm" placeholder="Program name, roll #, category..." value="<?= e($filters['search'] ?? '') ?>" autocomplete="off">
            </div>
            <div class="col-md-1 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-secondary btn-sm flex-fill" title="Filter"><i class="bi bi-filter"></i></button>
                <?php if (!empty($filters['status']) || !empty($filters['production_unit_id']) || !empty($filters['priority']) || !empty($filters['search'])): ?>
                    <a href="<?= url('production/programs') ?>" class="btn btn-outline-danger btn-sm" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Programs Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="programsTable">
                <thead class="table-light">
                    <tr>
                        <th>Program Details</th>
                        <th>Raw Material Roll</th>
                        <th>Category Hierarchy (3-Tier)</th>
                        <th>Roll Specs</th>
                        <th>Schedule Dates</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="programsTableBody">
                    <?php if (empty($programs)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> No production programs found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($programs as $prog): ?>
                            <?php 
                                $prioClass = match($prog['priority']) {
                                    'URGENT' => 'bg-danger',
                                    'HIGH'   => 'bg-warning text-dark',
                                    'MEDIUM' => 'bg-info text-dark',
                                    default  => 'bg-secondary'
                                };
                                $statusClass = match($prog['status']) {
                                    'DRAFT'       => 'bg-secondary',
                                    'APPROVED'    => 'bg-info text-dark',
                                    'SCHEDULED'   => 'bg-primary',
                                    'IN_PROGRESS' => 'bg-warning text-dark',
                                    'COMPLETED'   => 'bg-success',
                                    'HOLD'        => 'bg-danger',
                                    default       => 'bg-dark'
                                };

                                // Build Category Path
                                $catParts = [];
                                if (!empty($prog['category_name'])) $catParts[] = $prog['category_name'];
                                if (!empty($prog['subcategory_name'])) $catParts[] = $prog['subcategory_name'];
                                if (!empty($prog['sub_subcategory_name'])) $catParts[] = $prog['sub_subcategory_name'];
                                $catBreadcrumb = !empty($catParts) ? implode(' › ', $catParts) : ($prog['product_name'] ?? '-');
                            ?>
                            <tr class="programs-table-row">
                                <td>
                                    <a href="<?= url('production/programs/view/' . $prog['id']) ?>" class="fw-bold text-decoration-none text-primary fs-6">
                                        <?= e($prog['name'] ?: $prog['program_number']) ?>
                                    </a>
                                    <div class="small text-muted font-monospace"><?= e($prog['program_number']) ?></div>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= formatDate($prog['created_at']) ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $pRolls = $programRollsMap[$prog['id']] ?? [];
                                        if (empty($pRolls) && (!empty($prog['roll_number']) || !empty($prog['seller_roll_id']))) {
                                            $pRolls = [[
                                                'seller_roll_id' => $prog['seller_roll_id'] ?? null,
                                                'roll_number'    => $prog['roll_number'] ?? ('Roll #' . ($prog['seller_roll_id'] ?? '')),
                                                'seller_name'    => $prog['seller_name'] ?? 'Supplier',
                                                'roll_color'     => $prog['roll_color'] ?? '',
                                                'roll_design'    => $prog['roll_design'] ?? '',
                                                'roll_weight'    => $prog['roll_weight'] ?? 0,
                                                'roll_price'     => $prog['roll_price'] ?? 0,
                                            ]];
                                        }
                                    ?>
                                    <?php if (!empty($pRolls)): ?>
                                        <?php if (count($pRolls) === 1): ?>
                                            <?php $r = $pRolls[0]; ?>
                                            <div class="fw-semibold text-dark">
                                                <i class="bi bi-circle-square text-primary me-1"></i><?= e(!empty($r['roll_number']) ? $r['roll_number'] : ('Roll #' . ($r['seller_roll_id'] ?? ''))) ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= e($r['roll_color'] ?: 'Standard') ?> &bull; <?= e($r['roll_design'] ?: 'Plain') ?>
                                            </div>
                                            <?php if (!empty($r['seller_name'])): ?>
                                                <div class="small text-muted"><i class="bi bi-shop me-1"></i><?= e($r['seller_name']) ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="fw-semibold text-dark mb-1">
                                                <i class="bi bi-layers-fill text-primary me-1"></i><?= count($pRolls) ?> Rolls Linked
                                            </div>
                                            <div class="d-flex flex-wrap gap-1 mb-1">
                                                <?php foreach ($pRolls as $r): ?>
                                                    <span class="badge bg-light text-dark border small" title="<?= e($r['seller_name'] ?? 'Supplier') ?> - <?= number_format((float)($r['roll_weight'] ?? 0), 2) ?> kg">
                                                        <?= e(!empty($r['roll_number']) ? $r['roll_number'] : ('#' . ($r['seller_roll_id'] ?? ''))) ?>
                                                        <?php if (!empty($r['roll_color'])): ?> (<?= e($r['roll_color']) ?>)<?php endif; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">No roll linked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <i class="bi bi-diagram-3 text-primary me-1"></i><?= e($catBreadcrumb) ?>
                                    </div>
                                    <?php if (!empty($prog['unit_name'])): ?>
                                        <div class="small text-muted">Unit: <?= e($prog['unit_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><strong class="text-dark"><?= number_format((float)($prog['roll_weight'] ?? 0), 2) ?></strong> <small class="text-muted">kg</small></div>
                                    <div class="small text-success fw-semibold"><?= formatCurrency((float)($prog['roll_price'] ?? 0)) ?></div>
                                </td>
                                <td>
                                    <div class="small">
                                        <span class="text-muted">Start:</span> <strong><?= !empty($prog['start_date']) ? formatDate($prog['start_date']) : '-' ?></strong>
                                    </div>
                                    <div class="small">
                                        <span class="text-muted">End:</span> <strong><?= !empty($prog['end_date']) ? formatDate($prog['end_date']) : (!empty($prog['target_date']) ? formatDate($prog['target_date']) : '-') ?></strong>
                                    </div>
                                </td>
                                <td><span class="badge <?= $prioClass ?>"><?= e($prog['priority']) ?></span></td>
                                <td><span class="badge <?= $statusClass ?>"><?= e($prog['status']) ?></span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('production/programs/view/' . $prog['id']) ?>" class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('programs', 'edit')): ?>
                                        <a href="<?= url('production/programs/edit/' . $prog['id']) ?>" class="btn btn-outline-secondary" title="Edit Program">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('programs', 'delete')): ?>
                                        <form method="POST" action="<?= url('production/programs/delete/' . $prog['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete production program \'<?= e(addslashes($prog['name'] ?: $prog['program_number'])) ?>\'?');">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-outline-danger" title="Delete Program">
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
    <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="programsPaginationFooter">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="programsPageInfo">
                Showing 1 to 10 of <?= count($programs) ?> programs
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="programsPageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                    <option value="10" selected>10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <nav aria-label="Programs Pagination">
            <ul class="pagination pagination-modern mb-0" id="programsPaginationList">
            </ul>
        </nav>
    </div>
</div>

<script>
let programsPagination;
function initProgramsPagination() {
    const searchInput = document.getElementById('programsSearchInput');
    const tableBody = document.getElementById('programsTableBody');
    if (!tableBody) return;

    if (typeof window.initClientPagination === 'function') {
        programsPagination = window.initClientPagination({
            container: '#programsTableBody',
            itemSelector: 'tr.programs-table-row',
            footer: '#programsPaginationFooter',
            infoEl: '#programsPageInfo',
            paginationList: '#programsPaginationList',
            pageSizeSelect: '#programsPageSizeSelect',
            emptyStateEl: '#noLiveProgramsResultsRow',
            pageSize: 10,
            itemName: 'programs'
        });
    }

    if (searchInput) {
        function filterRows() {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;
            const rows = tableBody.querySelectorAll('tr.programs-table-row');
            const totalCount = rows.length;

            if (programsPagination) {
                visibleCount = programsPagination.filter(row => {
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

            let noResultRow = document.getElementById('noLiveProgramsResultsRow');
            if (totalCount > 0 && visibleCount === 0 && query !== '') {
                if (!noResultRow) {
                    noResultRow = document.createElement('tr');
                    noResultRow.id = 'noLiveProgramsResultsRow';
                    noResultRow.innerHTML = `<td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                        <h6 class="fw-bold text-secondary">No production programs matching "${escapeHtml(searchInput.value)}"</h6>
                        <small class="text-muted">Try searching with a different keyword or clear the search filter.</small>
                    </td>`;
                    tableBody.appendChild(noResultRow);
                } else {
                    noResultRow.style.display = '';
                    const h6 = noResultRow.querySelector('h6');
                    if (h6) h6.textContent = `No production programs matching "${searchInput.value}"`;
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
    document.addEventListener('DOMContentLoaded', initProgramsPagination);
} else {
    initProgramsPagination();
}
</script>
