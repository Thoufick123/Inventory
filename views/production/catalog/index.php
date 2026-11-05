<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-journal-bookmark me-2 text-primary"></i>Production Catalog</h2>
        <span class="text-muted">In-house manufactured garments, product styles, materials, and production cost structures</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (hasPermission('catalog', 'create')): ?>
        <a href="<?= url('production/catalog/create') ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle-fill me-1"></i> Add Product Style
        </a>
        <?php endif; ?>
        <?php if (hasPermission('programs', 'create')): ?>
        <a href="<?= url('production/programs/create') ?>" class="btn btn-outline-primary">
            <i class="bi bi-kanban me-1"></i> Start Production Program
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/catalog') ?>" id="catalogFilterForm" class="row g-2 align-items-center">
            <!-- Search input with live search and clear button -->
            <div class="col-lg-5 col-md-6">
                <label class="form-label small text-muted fw-semibold mb-1">Search Product Styles</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-primary"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="search" 
                           id="catalogSearchInput" 
                           class="form-control fw-semibold" 
                           placeholder="Search by style code, product name, or category..." 
                           value="<?= e($search ?? '') ?>"
                           oninput="liveFilterCatalog()">
                    <?php if (!empty($search)): ?>
                        <a href="<?= url('production/catalog') ?>" class="btn btn-outline-secondary" title="Clear Search">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Category Filter Dropdown -->
            <div class="col-lg-3 col-md-3 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Category</label>
                <select name="category_id" id="filterCategoryId" class="form-select" onchange="handleCategoryFilterChange()">
                    <option value="">-- All Categories --</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($categoryId ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Subcategory Filter Dropdown -->
            <div class="col-lg-2 col-md-3 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Subcategory</label>
                <select name="subcategory_id" id="filterSubcategoryId" class="form-select" onchange="liveFilterCatalog()">
                    <option value="">-- All Subcategories --</option>
                    <?php if (!empty($subcategories)): ?>
                        <?php foreach ($subcategories as $sc): ?>
                            <option value="<?= $sc['id'] ?>" data-cat-id="<?= $sc['category_id'] ?>" <?= ($subcategoryId ?? '') == $sc['id'] ? 'selected' : '' ?>><?= e($sc['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Actions (Filter & Reset) -->
            <div class="col-lg-2 col-md-12 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-primary flex-grow-1" title="Filter Records">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($categoryId) || !empty($subcategoryId)): ?>
                    <a href="<?= url('production/catalog') ?>" class="btn btn-outline-danger" title="Reset All Filters">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Header with Result Count -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-semibold text-dark">
        <i class="bi bi-grid-fill me-2 text-primary"></i>Manufactured Garment Styles
    </h5>
    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold" id="catalogCountBadge">
        <?= count($products) ?> Product Styles
    </span>
</div>

<!-- Products Grid -->
<div class="row g-4" id="catalogGrid">
    <!-- Live Empty Search State -->
    <div id="emptyCatalogLiveState" class="col-12" style="display: none;">
        <div class="card text-center py-5 shadow-sm border-0">
            <div class="card-body">
                <div class="mb-3">
                    <i class="bi bi-search fs-1 text-secondary opacity-50 p-3 bg-light rounded-circle d-inline-block"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">No Matching Product Styles Found</h5>
                <p class="text-muted mb-3">Try adjusting your search keywords or choosing another category filter.</p>
                <a href="<?= url('production/catalog') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reset Filters
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div class="col-12" id="noProductsServerState">
            <div class="card text-center py-5 shadow-sm border-0">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="bi bi-journal-plus fs-1 text-primary p-3 bg-primary-subtle rounded-circle d-inline-block"></i>
                    </div>
                    <h4 class="fw-bold mb-1">No In-House Production Styles Found</h4>
                    <p class="text-muted mb-4">Start by adding your manufactured garment styles with 3-tier categories, attributes, and variants.</p>
                    <?php if (hasPermission('catalog', 'create')): ?>
                    <a href="<?= url('production/catalog/create') ?>" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-plus-circle me-1"></i> Create Your First Product Style
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($products as $p): ?>
            <div class="col-md-6 col-xl-4 catalog-product-col"
                 data-code="<?= strtolower(e($p['code'])) ?>"
                 data-name="<?= strtolower(e($p['name'])) ?>"
                 data-category-id="<?= $p['category_id'] ?? '' ?>"
                 data-category-name="<?= strtolower(e($p['category_name'] ?? '')) ?>"
                 data-subcategory-id="<?= $p['subcategory_id'] ?? '' ?>"
                 data-subcategory-name="<?= strtolower(e($p['subcategory_name'] ?? '')) ?>"
                 data-subsub-name="<?= strtolower(e($p['sub_subcategory_name'] ?? '')) ?>"
                 data-material="<?= strtolower(e($p['material'] ?? '')) ?>"
                 data-brand="<?= strtolower(e($p['brand_name'] ?? '')) ?>">
                <div class="card h-100 shadow-sm border-0 d-flex flex-column catalog-card-hover" style="transition: transform 0.18s, box-shadow 0.18s;">
                    <!-- Card Top Image / Banner -->
                    <div class="bg-light position-relative border-bottom text-center d-flex align-items-center justify-content-center" style="height: 180px; overflow: hidden;">
                        <?php if (!empty($p['image_url'])): ?>
                            <img src="<?= e($p['image_url']) ?>" class="img-fluid" style="max-height: 180px; width: 100%; object-fit: contain;" alt="<?= e($p['name']) ?>">
                        <?php else: ?>
                            <div class="text-muted text-center py-4">
                                <i class="bi bi-image fs-1 text-secondary opacity-50 d-block mb-1"></i>
                                <span class="small">No Style Image</span>
                            </div>
                        <?php endif; ?>
                        <span class="position-absolute top-0 start-0 m-2 badge bg-dark text-white px-2 py-1 font-monospace">
                            <?= e($p['code']) ?>
                        </span>
                        <span class="position-absolute top-0 end-0 m-2 badge bg-success text-white">In-House</span>
                    </div>

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold mb-1 text-truncate" title="<?= e($p['name']) ?>">
                            <a href="<?= url('production/catalog/view/' . $p['id']) ?>" class="text-dark text-decoration-none hover-primary">
                                <?= e($p['name']) ?>
                            </a>
                        </h5>

                        <div class="text-muted small mb-3">
                            <i class="bi bi-folder2 me-1 text-primary"></i><?= e($p['category_name'] ?? 'General') ?>
                            <?php if (!empty($p['subcategory_name'])): ?>
                                <i class="bi bi-chevron-right text-muted small" style="font-size: 0.7rem;"></i> <?= e($p['subcategory_name']) ?>
                            <?php endif; ?>
                            <?php if (!empty($p['material'])): ?>
                                &bull; <span class="badge bg-light text-dark border"><?= e($p['material']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Pricing & Variant Summary Box -->
                        <div class="bg-light p-3 rounded mb-3 mt-auto">
                            <div class="row text-center g-2">
                                <div class="col-4 border-end">
                                    <div class="small text-muted" style="font-size: 0.75rem;">Variants</div>
                                    <div class="fw-bold fs-6 text-dark"><?= $p['variant_count'] ?> active</div>
                                </div>
                                <div class="col-4 border-end">
                                    <div class="small text-muted" style="font-size: 0.75rem;">Prod. Cost</div>
                                    <div class="fw-bold fs-6 text-primary">
                                        <?= (float)($p['min_prod_cost'] ?? 0) > 0 ? formatCurrency((float)$p['min_prod_cost']) : '—' ?>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted" style="font-size: 0.75rem;">Selling Price</div>
                                    <div class="fw-bold fs-6 text-success">
                                        <?= (float)($p['min_selling_price'] ?? 0) > 0 ? formatCurrency((float)$p['min_selling_price']) : '—' ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 pt-2 border-top align-items-center">
                            <a href="<?= url('production/catalog/view/' . $p['id']) ?>" class="btn btn-sm btn-outline-secondary flex-grow-1" title="View Full Details">
                                <i class="bi bi-eye me-1"></i> View
                            </a>
                            <?php if (hasPermission('catalog', 'edit')): ?>
                            <a href="<?= url('production/catalog/edit/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit Style">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('programs', 'create')): ?>
                            <a href="<?= url('production/programs/create?product_id=' . $p['id']) ?>" class="btn btn-sm btn-primary" title="Plan Production Program">
                                <i class="bi bi-kanban me-1"></i> Plan
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('catalog', 'delete')): ?>
                            <form method="POST" action="<?= url('production/catalog/delete/' . $p['id']) ?>" class="d-inline mb-0" onsubmit="return confirm('Are you sure you want to delete product style &quot;<?= e(addslashes($p['name'])) ?>&quot;?');">
                                <?= CSRF::field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Style">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Catalog Pagination Controls Card -->
<div class="card bg-white mt-4 shadow-sm border" id="catalogPaginationFooter">
    <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="catalogPageInfo">
                Showing 1 to 12 of <?= count($products) ?> product styles
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="catalogPageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                    <option value="12" selected>12 per page</option>
                    <option value="24">24 per page</option>
                    <option value="48">48 per page</option>
                    <option value="96">96 per page</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <nav aria-label="Catalog Pagination">
            <ul class="pagination pagination-modern mb-0" id="catalogPaginationList">
            </ul>
        </nav>
    </div>
</div>

<style>
.catalog-card-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12) !important;
}
.hover-primary:hover {
    color: var(--bs-primary) !important;
}
</style>

<script>
let catalogPagination;

function handleCategoryFilterChange() {
    const catSelect = document.getElementById('filterCategoryId');
    const subcatSelect = document.getElementById('filterSubcategoryId');
    const selectedCatId = catSelect ? catSelect.value : '';

    if (subcatSelect) {
        const options = subcatSelect.querySelectorAll('option');
        options.forEach(opt => {
            const catId = opt.getAttribute('data-cat-id');
            if (!opt.value || !selectedCatId || catId === selectedCatId) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
                if (opt.selected) subcatSelect.value = '';
            }
        });
    }

    liveFilterCatalog();
}

function liveFilterCatalog() {
    const searchInput = document.getElementById('catalogSearchInput');
    const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

    const catSelect = document.getElementById('filterCategoryId');
    const selectedCatId = catSelect ? catSelect.value.trim() : '';

    const subcatSelect = document.getElementById('filterSubcategoryId');
    const selectedSubcatId = subcatSelect ? subcatSelect.value.trim() : '';

    const cards = document.querySelectorAll('.catalog-product-col');
    const totalCount = cards.length;

    const matchFn = (card) => {
        const code = card.getAttribute('data-code') || '';
        const name = card.getAttribute('data-name') || '';
        const catId = card.getAttribute('data-category-id') || '';
        const catName = card.getAttribute('data-category-name') || '';
        const subcatId = card.getAttribute('data-subcategory-id') || '';
        const subcatName = card.getAttribute('data-subcategory-name') || '';
        const subsubName = card.getAttribute('data-subsub-name') || '';
        const material = card.getAttribute('data-material') || '';
        const brand = card.getAttribute('data-brand') || '';

        const matchesQuery = !query || 
            code.includes(query) || 
            name.includes(query) || 
            catName.includes(query) || 
            subcatName.includes(query) || 
            subsubName.includes(query) || 
            material.includes(query) || 
            brand.includes(query);

        const matchesCategory = !selectedCatId || catId === selectedCatId;
        const matchesSubcategory = !selectedSubcatId || subcatId === selectedSubcatId;

        return matchesQuery && matchesCategory && matchesSubcategory;
    };

    let visibleCount = 0;
    if (catalogPagination) {
        visibleCount = catalogPagination.filter(matchFn);
    } else {
        cards.forEach(card => {
            if (matchFn(card)) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        const emptyLiveState = document.getElementById('emptyCatalogLiveState');
        if (emptyLiveState) {
            emptyLiveState.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
        }
    }

    const badge = document.getElementById('catalogCountBadge');
    if (badge) {
        if (query || selectedCatId || selectedSubcatId) {
            badge.textContent = `${visibleCount} of ${totalCount} Product Styles`;
            badge.className = 'badge bg-warning text-dark border border-warning rounded-pill px-3 py-2 fw-semibold';
        } else {
            badge.textContent = `${totalCount} Product Styles`;
            badge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold';
        }
    }
}

function initCatalogPagination() {
    if (typeof window.initClientPagination === 'function') {
        catalogPagination = window.initClientPagination({
            container: '#catalogGrid',
            itemSelector: '.catalog-product-col',
            footer: '#catalogPaginationFooter',
            infoEl: '#catalogPageInfo',
            paginationList: '#catalogPaginationList',
            pageSizeSelect: '#catalogPageSizeSelect',
            emptyStateEl: '#emptyCatalogLiveState',
            pageSize: 12,
            itemName: 'product styles'
        });
    }
    handleCategoryFilterChange();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCatalogPagination);
} else {
    initCatalogPagination();
}
</script>
