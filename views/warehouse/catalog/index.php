<?php
$canCreateCatalog = hasPermission('wh_catalog', 'create');
$canEditCatalog = hasPermission('wh_catalog', 'edit');
$canDeleteCatalog = hasPermission('wh_catalog', 'delete');
?>
<!-- PAGE HEADER -->
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-diagram-3-fill me-2 text-primary"></i>Product Catalog</h2>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($canCreateCatalog): ?>
            <button type="button" class="btn btn-primary shadow-sm fw-semibold" onclick="openAddCategoryModal()">
                <i class="bi bi-plus-circle me-1"></i> Add Category
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- KPI SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-primary border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Active Categories</span>
                    <h4 class="mb-0 fw-bold mt-1 text-dark" id="kpiCatCount"><?php echo number_format($catalogStats['total_categories'] ?? count($categories ?? [])); ?></h4>
                    <small class="text-muted">Primary department groups</small>
                </div>
                <div class="rounded-circle bg-primary-subtle text-primary p-3"><i class="bi bi-folder2-open fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Total Subcategories</span>
                    <h4 class="mb-0 fw-bold mt-1 text-info" id="kpiSubCount"><?php echo number_format($catalogStats['total_subcategories'] ?? 0); ?></h4>
                    <small class="text-muted">Secondary classifications</small>
                </div>
                <div class="rounded-circle bg-info-subtle text-info p-3"><i class="bi bi-diagram-3 fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Pre-set Attributes</span>
                    <h4 class="mb-0 fw-bold mt-1 text-warning" id="kpiAttrCount"><?php echo number_format($catalogStats['total_attributes'] ?? 0); ?></h4>
                    <small class="text-muted">Category specifications</small>
                </div>
                <div class="rounded-circle bg-warning-subtle text-warning p-3"><i class="bi bi-sliders fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Contained Products</span>
                    <h4 class="mb-0 fw-bold mt-1 text-success" id="kpiProdCount"><?php echo number_format($catalogStats['total_products'] ?? count($products ?? [])); ?></h4>
                    <small class="text-muted">Linked catalog inventory</small>
                </div>
                <div class="rounded-circle bg-success-subtle text-success p-3"><i class="bi bi-boxes fs-4"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- SEARCH & FILTER TOOLBAR (Image 1 Style with View Toggle) -->
<div class="card border-0 shadow-sm mb-4 bg-white rounded-3">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-lg-6 col-md-6 col-12">
                <label class="form-label small fw-semibold text-muted mb-1">Search Category / Subcategory / Style / Attributes</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="globalCatalogSearch" class="form-control" placeholder="Search by name, code, attribute..." oninput="handleGlobalSearch(this.value)">
                </div>
            </div>
            <div class="col-lg-2 col-md-2 col-6 d-flex align-items-end gap-1">
                <button type="button" class="btn btn-sm btn-primary px-3 fw-semibold flex-grow-1" onclick="applyFilters()" style="background:#5551ff;border-color:#5551ff;">
                    <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary px-2" onclick="resetFilters()" title="Reset Filters">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
            <div class="col-lg-4 col-md-4 col-12 d-flex justify-content-lg-end justify-content-start align-items-end mt-2 mt-lg-0">
                <!-- VIEW MODE SWITCHER: Hierarchy List vs Cascading Columns -->
                <div class="btn-group btn-group-sm p-1 bg-light rounded-pill border" role="group" aria-label="Catalog View Mode">
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold active btn-primary shadow-sm" id="btnToggleList" onclick="switchCatalogView('list')">
                        <i class="bi bi-list-ul me-1"></i> List
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold btn-light text-muted" id="btnToggleColumns" onclick="switchCatalogView('columns')">
                        <i class="bi bi-columns-gap me-1"></i> Columns
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CATEGORY HIERARCHY FLOW BREADCRUMB STRIP -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 px-1">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge bg-primary px-3 py-1 fw-bold" style="font-size:0.82rem;">
            <i class="bi bi-diagram-3-fill me-1"></i>Category Hierarchy Flow:
        </span>
        <div class="d-flex align-items-center gap-1 small fw-semibold text-muted flex-wrap">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">1. Category</span>
            <i class="bi bi-chevron-right text-muted"></i>
            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">2. Sub Category</span>
            <i class="bi bi-chevron-right text-muted"></i>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">3. Sub-Sub Category</span>
            <i class="bi bi-chevron-right text-muted"></i>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="bi bi-sliders me-1"></i>Pre-set Attributes &amp; Products</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="reloadCategoryTree()">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
    </div>
</div>

<!-- ========================================================================= -->
<!-- VIEW 1: CATEGORY HIERARCHY FLOW IN LIST VIEW (IMAGE 1 UI STYLE) -->
<!-- ========================================================================= -->
<div id="catalogListViewContainer">
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableCatalogHierarchyList">
                <thead class="table-light" style="border-bottom:2px solid #eef2f6;">
                    <tr class="text-uppercase" style="font-size:0.75rem; letter-spacing:0.5px; color:#64748b;">
                        <th style="width:24%; padding:14px 18px;">Category / Department</th>
                        <th style="width:20%; padding:14px 14px;">Sub Categories</th>
                        <th style="width:20%; padding:14px 14px;">Sub-Sub Categories</th>
                        <th style="width:22%; padding:14px 14px;">Pre-set Attributes (Scoped)</th>
                        <th class="text-end" style="width:14%; padding:14px 18px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="catalogListTableBody">
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary mb-2"></div>
                            <div class="small fw-semibold">Loading Category Hierarchy Flow...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- VIEW 2: CASCADING COLUMNS VIEW (IMAGE 2 UI STYLE) -->
<!-- ========================================================================= -->
<div id="catalogColumnsViewContainer" style="display:none;">
    <div class="row g-3" id="catalogHierarchyRow">

        <!-- COL 1: CATEGORY -->
        <div class="col-xl-4 col-lg-4 col-md-4 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 d-flex flex-column" style="border:1px solid #e8ecf0!important;">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <span class="cat-level-num bg-primary">1</span>
                        <h6 class="card-title mb-0 fw-bold text-dark">Category</h6>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill" id="badgeCatCount">0</span>
                    </div>
                    <?php if ($canCreateCatalog): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 rounded-2 fw-semibold" onclick="openAddCategoryModal()">
                            <i class="bi bi-plus-lg me-1"></i>Add
                        </button>
                    <?php endif; ?>
                </div>
                <div class="px-2 pt-2 pb-1 border-bottom">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted" style="font-size:.75rem;"></i></span>
                        <input type="text" id="filterCatInput" class="form-control form-control-sm border-start-0" placeholder="Search category..." oninput="filterColList('categoriesListContainer', this.value)">
                    </div>
                </div>
                <div class="flex-grow-1 overflow-auto p-0" id="categoriesListContainer" style="max-height:520px;min-height:320px;">
                    <div class="text-center py-5 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary mb-2"></div>
                        <div class="small">Loading categories...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COL 2: SUB CATEGORY -->
        <div class="col-xl-4 col-lg-4 col-md-4 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 d-flex flex-column" style="border:1px solid #e8ecf0!important;">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <span class="cat-level-num bg-info">2</span>
                        <h6 class="card-title mb-0 fw-bold text-dark text-truncate" id="col2HeaderTitle">Sub Category</h6>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill" id="badgeSubCount">0</span>
                    </div>
                    <?php if ($canCreateCatalog): ?>
                        <button type="button" class="btn btn-sm btn-outline-info py-1 px-2 rounded-2 fw-semibold" id="btnAddSubcatBtn" onclick="openAddSubcatForSelected()" disabled>
                            <i class="bi bi-plus-lg me-1"></i>Add
                        </button>
                    <?php endif; ?>
                </div>
                <div class="px-2 pt-2 pb-1 border-bottom">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted" style="font-size:.75rem;"></i></span>
                        <input type="text" id="filterSubInput" class="form-control form-control-sm border-start-0" placeholder="Search subcategory..." oninput="filterColList('subcategoriesListContainer', this.value)">
                    </div>
                </div>
                <div class="flex-grow-1 overflow-auto p-0" id="subcategoriesListContainer" style="max-height:520px;min-height:320px;">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-arrow-left-circle fs-3 text-secondary opacity-50 d-block mb-2"></i>
                        <div class="small fw-semibold">Select a Category first</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COL 3: PRE-SET ATTRIBUTES (INLINE, CATEGORY-DEPENDENT) -->
        <div class="col-xl-4 col-lg-4 col-md-4 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 d-flex flex-column" style="border:1px solid #e8ecf0!important;">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <span class="cat-level-num bg-warning text-dark">3</span>
                        <h6 class="card-title mb-0 fw-bold text-dark text-truncate" id="col4HeaderTitle">
                            <i class="bi bi-sliders me-1 text-warning"></i>Attributes
                        </h6>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill" id="attrCountBadge">—</span>
                    </div>
                    <?php if ($canEditCatalog): ?>
                        <button type="button" class="btn btn-sm btn-outline-warning py-1 px-2 rounded-2 fw-semibold" id="btnAddAttrRow" onclick="addAttributeRow()" disabled>
                            <i class="bi bi-plus-lg me-1"></i>Add
                        </button>
                    <?php endif; ?>
                </div>
                <!-- Scope + Save row -->
                <div class="px-3 py-2 border-bottom bg-light d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <span class="small text-truncate" style="max-width:180px;">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        <span id="labelActiveBranchScope" class="fw-semibold text-primary" style="font-size:.78rem;">Select category to configure</span>
                    </span>
                    <?php if ($canEditCatalog): ?>
                        <button type="button" class="btn btn-success fw-semibold" id="btnSaveAttrs" onclick="saveCategoryAttributes()" disabled style="font-size:.75rem;padding:3px 10px;">
                            <i class="bi bi-check2-circle me-1"></i>Save
                        </button>
                    <?php endif; ?>
                </div>
                <!-- Quick-add chips -->
                <!-- Attributes list container -->
                <div class="flex-grow-1 overflow-auto p-0" id="attributesListContainer" style="max-height:520px;min-height:260px;">
                    <div class="text-center py-5 text-muted" id="attrEmptyState">
                        <i class="bi bi-sliders fs-3 opacity-30 d-block mb-2"></i>
                        <div class="small fw-semibold">Select a category above</div>
                        <div style="font-size:.74rem;">Attributes are category-dependent, not global</div>
                    </div>
                    <div id="attributesInlineList"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODALS -->
<!-- ========================================================================= -->

<!-- MODAL: UNIFIED ADD CATEGORY HIERARCHY & PRE-SET ATTRIBUTES -->
<div class="modal fade" id="modalUnifiedCategory" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-white">
                        <i class="bi bi-diagram-3-fill me-2"></i>Create Category Hierarchy &amp; Pre-set Attributes
                    </h5>
                    <small class="text-white-50">Main Category &rsaquo; Sub Category &rsaquo; Sub-Sub Category &rsaquo; Attributes</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUnifiedCategory" onsubmit="submitUnifiedCategoryForm(event)">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-primary rounded-pill px-3 py-1">Step 1</span>
                            <h6 class="fw-bold mb-0 text-dark">Category Hierarchy Flow</h6>
                        </div>
                        <div class="row g-3 p-3 bg-light rounded-3 border">
                            <!-- 1. MAIN CATEGORY -->
                            <div class="col-md-4">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="form-label small fw-bold text-dark mb-0">
                                        <span class="badge rounded-circle bg-primary text-white me-1" style="width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;">1</span>
                                        Main Category <span class="text-danger">*</span>
                                    </label>
                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 fw-semibold" id="btnToggleNewUnifiedCat" onclick="toggleUnifiedNewCat()" style="font-size:0.72rem;">
                                        <i class="bi bi-plus-lg me-1"></i>Add New
                                    </button>
                                </div>
                                <div id="unifiedCatSelectWrap">
                                    <select id="unifiedCatSelect" class="form-select form-select-sm text-uppercase fw-semibold" onchange="onUnifiedCatSelectChange()">
                                        <option value="">-- Select Existing Category --</option>
                                        <option value="__NEW__" class="fw-bold text-primary">+ Add New Category</option>
                                    </select>
                                </div>
                                <div id="unifiedCatInputWrap" style="display:none;">
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="unifiedCatName" class="form-control form-control-sm text-uppercase fw-semibold" placeholder="e.g. MEN / WOMEN / KIDS" maxlength="150" oninput="onUnifiedCatNameInput()">
                                        <button type="button" class="btn btn-outline-secondary" onclick="cancelUnifiedNewCat()" title="Select from existing categories">
                                            <i class="bi bi-list-ul"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-text text-muted" id="unifiedCatHelpText" style="font-size:.72rem;">Select existing or click + Add New</div>
                            </div>

                            <!-- 2. SUB CATEGORY (STRICTLY SCOPED TO MAIN CATEGORY) -->
                            <div class="col-md-4">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="form-label small fw-bold text-dark mb-0">
                                        <span class="badge rounded-circle bg-info text-white me-1" style="width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;">2</span>
                                        Sub Category <span class="text-muted fw-normal">(Optional)</span>
                                    </label>
                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 fw-semibold" id="btnToggleNewUnifiedSubcat" onclick="toggleUnifiedNewSubcat()" style="font-size:0.72rem;">
                                        <i class="bi bi-plus-lg me-1"></i>Add New
                                    </button>
                                </div>
                                <div id="unifiedSubcatSelectWrap">
                                    <select id="unifiedSubcatSelect" class="form-select form-select-sm text-uppercase fw-semibold" onchange="onUnifiedSubcatSelectChange()">
                                        <option value="">-- None (Optional) --</option>
                                        <option value="__NEW__" class="fw-bold text-primary">+ Add New Sub Category</option>
                                    </select>
                                </div>
                                <div id="unifiedSubcatInputWrap" style="display:none;">
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="unifiedSubcatName" class="form-control form-control-sm text-uppercase fw-semibold" placeholder="e.g. SHIRT / TROUSER / DRESS" maxlength="150" oninput="onUnifiedSubcatNameInput()">
                                        <button type="button" class="btn btn-outline-secondary" id="btnCancelUnifiedSubcat" onclick="cancelUnifiedNewSubcat()" title="Select from existing list">
                                            <i class="bi bi-list-ul"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-text text-muted" id="unifiedSubcatHelpText" style="font-size:.72rem;">Belongs strictly to selected category</div>
                            </div>

                            <!-- 3. SUB-SUB CATEGORY (STRICTLY SCOPED TO SUB CATEGORY) -->
                            <div class="col-md-4">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="form-label small fw-bold text-dark mb-0">
                                        <span class="badge rounded-circle bg-success text-white me-1" style="width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;">3</span>
                                        Sub-Sub Category <span class="text-muted fw-normal">(Optional)</span>
                                    </label>
                                    <button type="button" class="btn btn-xs btn-outline-success py-0 px-2 fw-semibold" id="btnToggleNewUnifiedSubSubcat" onclick="toggleUnifiedNewSubSubcat()" style="font-size:0.72rem;">
                                        <i class="bi bi-plus-lg me-1"></i>Add New
                                    </button>
                                </div>
                                <div id="unifiedSubSubcatSelectWrap">
                                    <select id="unifiedSubSubcatSelect" class="form-select form-select-sm text-uppercase fw-semibold" onchange="onUnifiedSubSubcatSelectChange()">
                                        <option value="">-- None (Optional) --</option>
                                        <option value="__NEW__" class="fw-bold text-success">+ Add New Sub-Sub Category</option>
                                    </select>
                                </div>
                                <div id="unifiedSubSubcatInputWrap" style="display:none;">
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="unifiedSubSubcatName" class="form-control form-control-sm text-uppercase fw-semibold" placeholder="e.g. FULL SLEEVE / HALF SLEEVE" maxlength="150" oninput="onUnifiedSubSubcatNameInput()">
                                        <button type="button" class="btn btn-outline-secondary" id="btnCancelUnifiedSubSubcat" onclick="cancelUnifiedNewSubSubcat()" title="Select from existing list">
                                            <i class="bi bi-list-ul"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-text text-muted" id="unifiedSubSubcatHelpText" style="font-size:.72rem;">Belongs strictly to selected subcategory</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success rounded-pill px-3 py-1">Step 2</span>
                                <h6 class="fw-bold mb-0 text-dark">Pre-set Attributes (Scoped Specifications)</h6>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addUnifiedAttributeRow()">
                                <i class="bi bi-plus-lg me-1"></i> Add Attribute
                            </button>
                        </div>
                        <p class="text-muted small mb-2">Configure specifications for this category hierarchy.</p>
                        <div class="table-responsive border rounded-2">
                            <table class="table table-sm table-bordered align-middle mb-0" id="unifiedAttributesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width:180px;">Attribute Name <span class="text-danger">*</span></th>
                                        <th style="min-width:220px;">Options</th>
                                        <th style="width:50px;" class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody id="unifiedAttributesTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between align-items-center">
                    <span class="text-muted small"><i class="bi bi-info-circle me-1 text-primary"></i>Saved categories immediately display in the list.</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-semibold px-4 shadow-sm" id="btnSubmitUnifiedCat">
                            <i class="bi bi-check2-circle me-1"></i> Save Category Hierarchy
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- MODAL: FULL FORM EDIT CATEGORY HIERARCHY & PRE-SET ATTRIBUTES -->
<div class="modal fade" id="modalUnifiedEditCategory" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="item-icon-badge" style="background:#4f46e5;color:#fff;width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="fullEditModalTitle">Edit Category Hierarchy &amp; Pre-set Attributes</h5>
                        <small class="text-white-50">Main Category &rsaquo; Sub Category &rsaquo; Sub-Sub Category &rsaquo; Attributes</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUnifiedEditCategory" onsubmit="submitUnifiedEditCategoryForm(event)">
                <input type="hidden" id="editFullCatId" value="">
                <input type="hidden" id="editFullSubcatId" value="">
                <input type="hidden" id="editFullSubSubcatId" value="">
                <div class="modal-body p-4 bg-light">
                    <!-- SECTION 1: HIERARCHY FLOW -->
                    <div class="card border-0 shadow-xs rounded-3 bg-white mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom">
                                <span class="badge rounded-circle bg-primary text-white" style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;">1</span>
                                <h6 class="fw-bold mb-0 text-dark">Category Hierarchy Flow</h6>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-muted mb-1">
                                        <span class="badge rounded-circle bg-primary text-white me-1" style="width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;">1</span>
                                        Main Category <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="editFullCatName" class="form-control form-control-sm text-uppercase fw-semibold" placeholder="e.g. MEN / WOMEN" required maxlength="150">
                                    <div class="form-text text-muted" style="font-size:.72rem;">Primary department group</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="form-label small fw-semibold text-muted mb-0">
                                            <span class="badge rounded-circle bg-info text-white me-1" style="width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;">2</span>
                                            Sub Category
                                        </label>
                                        <div class="d-flex gap-1" id="editSubcatActionBtns">
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 fw-semibold" id="btnRenameEditSubcat" onclick="toggleEditRenameSubcat()" style="font-size:0.7rem;" title="Rename selected subcategory">
                                                <i class="bi bi-pencil me-1"></i>Rename
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 fw-semibold" id="btnToggleNewEditSubcat" onclick="toggleEditNewSubcat()" style="font-size:0.7rem;" title="Add new subcategory under this category">
                                                <i class="bi bi-plus-lg me-1"></i>Add New
                                            </button>
                                        </div>
                                    </div>
                                    <div id="editFullSubcatSelectWrap">
                                        <select id="editFullSubcatSelect" class="form-select form-select-sm text-uppercase fw-semibold" onchange="onEditSubcatSelectChange()">
                                            <option value="">-- Select Existing Sub Category --</option>
                                            <option value="__NEW__" class="fw-bold text-primary">+ Add New Sub Category</option>
                                        </select>
                                    </div>
                                    <div id="editFullSubcatInputWrap" style="display:none;">
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="editFullSubcatName" class="form-control form-control-sm text-uppercase fw-semibold" placeholder="e.g. SHIRT" maxlength="150">
                                            <button type="button" class="btn btn-outline-secondary" onclick="cancelEditNewSubcat()" title="Select from existing list">
                                                <i class="bi bi-list-ul"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-text text-muted" id="editSubcatHelpText" style="font-size:.72rem;">Pick from dropdown or click + Add New</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="form-label small fw-semibold text-muted mb-0">
                                            <span class="badge rounded-circle bg-success text-white me-1" style="width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;">3</span>
                                            Sub-Sub Category
                                        </label>
                                        <div class="d-flex gap-1" id="editSubSubcatActionBtns">
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 fw-semibold" id="btnRenameEditSubSubcat" onclick="toggleEditRenameSubSubcat()" style="font-size:0.7rem;" title="Rename selected sub-subcategory">
                                                <i class="bi bi-pencil me-1"></i>Rename
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-success py-0 px-2 fw-semibold" id="btnToggleNewEditSubSubcat" onclick="toggleEditNewSubSubcat()" style="font-size:0.7rem;" title="Add new sub-subcategory under this subcategory">
                                                <i class="bi bi-plus-lg me-1"></i>Add New
                                            </button>
                                        </div>
                                    </div>
                                    <div id="editFullSubSubcatSelectWrap">
                                        <select id="editFullSubSubcatSelect" class="form-select form-select-sm text-uppercase fw-semibold" onchange="onEditSubSubcatSelectChange()">
                                            <option value="">-- Select Existing Sub-Sub Category --</option>
                                            <option value="__NEW__" class="fw-bold text-success">+ Add New Sub-Sub Category</option>
                                        </select>
                                    </div>
                                    <div id="editFullSubSubcatInputWrap" style="display:none;">
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="editFullSubSubcatName" class="form-control form-control-sm text-uppercase fw-semibold" placeholder="e.g. FULL SLEEVE" maxlength="150">
                                            <button type="button" class="btn btn-outline-secondary" onclick="cancelEditNewSubSubcat()" title="Select from existing list">
                                                <i class="bi bi-list-ul"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-text text-muted" id="editSubSubcatHelpText" style="font-size:.72rem;">Pick from dropdown or click + Add New</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: PRE-SET ATTRIBUTES -->
                    <div class="card border-0 shadow-xs rounded-3 bg-white">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-circle bg-warning text-dark" style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;">2</span>
                                    <h6 class="fw-bold mb-0 text-dark">Pre-set Attributes (Scoped Specifications)</h6>
                                    <span class="badge bg-warning-subtle text-dark border border-warning rounded-pill" id="editFullAttrCountBadge">0</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-warning py-0.5 px-2 fw-semibold" onclick="addEditAttributeRow()">
                                    <i class="bi bi-plus-lg me-1"></i> Add Attribute
                                </button>
                            </div>
                            <p class="text-muted small mb-2" style="font-size:0.78rem;">Attributes defined here govern product specifications for this category hierarchy.</p>
                            <div class="table-responsive border rounded-2">
                                <table class="table table-sm table-bordered align-middle mb-0" id="editAttributesTable">
                                    <thead class="table-light" style="font-size:0.75rem;">
                                        <tr>
                                            <th style="min-width:200px;">Attribute Name <span class="text-danger">*</span></th>
                                            <th style="min-width:220px;">Options</th>
                                            <th style="width:50px;" class="text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="editAttributesTableBody" style="font-size:0.82rem;">
                                        <!-- Dynamic attribute rows -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between align-items-center">
                    <span class="text-muted small"><i class="bi bi-info-circle me-1 text-primary"></i>All changes save together directly to this category branch.</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-semibold px-4 shadow-sm" id="btnSubmitFullEditCat">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: ADD / EDIT CATEGORY -->
<div class="modal fade" id="modalCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalCategoryTitle">Add Main Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCategory" onsubmit="submitCategoryForm(event)">
                <div class="modal-body p-4">
                    <input type="hidden" id="modalCatId" value="">
                    <input type="hidden" id="modalCatType" value="NORMAL">
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" id="modalCatName" class="form-control" placeholder="e.g. MENS WEAR / SHIRTS / ACCESSORIES" required maxlength="150" style="text-transform:uppercase;">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold" id="btnSubmitCat">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: ADD / EDIT SUBCATEGORY -->
<div class="modal fade" id="modalSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalSubcatTitle">Add Subcategory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSubcategory" onsubmit="submitSubcategoryForm(event)">
                <div class="modal-body p-4">
                    <input type="hidden" id="modalSubcatParentId" value="">
                    <input type="hidden" id="modalSubcatId" value="">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Parent Category</label>
                        <input type="text" id="modalSubcatParentName" class="form-control-plaintext fw-bold text-primary p-0" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subcategory Name <span class="text-danger">*</span></label>
                        <input type="text" id="modalSubcatName" class="form-control" placeholder="e.g. FORMAL SHIRTS / CASUAL / JEANS" required maxlength="150" style="text-transform:uppercase;">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold" id="btnSubmitSubcat">Save Subcategory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: ADD / EDIT SUB-SUBCATEGORY -->
<div class="modal fade" id="modalSubSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalSubSubcatTitle">Add Sub-Subcategory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSubSubcategory" onsubmit="submitSubSubcategoryForm(event)">
                <div class="modal-body p-4">
                    <input type="hidden" id="modalSubSubcatParentId" value="">
                    <input type="hidden" id="modalSubSubcatId" value="">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Parent Subcategory</label>
                        <input type="text" id="modalSubSubcatParentName" class="form-control-plaintext fw-bold text-primary p-0" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Sub-Subcategory Name <span class="text-danger">*</span></label>
                        <input type="text" id="modalSubSubcatName" class="form-control" placeholder="e.g. FULL HAND / SLIM FIT / CREW NECK" required maxlength="150" style="text-transform:uppercase;" oninput="validateModalSubSubcategoryName()">
                        <div class="invalid-feedback" id="modalSubSubcatNameFeedback">This sub-subcategory already exists under the selected subcategory.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold" id="btnSubmitSubSubcat">Save Sub-Subcategory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: CONFIGURE ATTRIBUTES (ACCESSIBLE DIRECTLY FROM LIST VIEW) -->
<div class="modal fade" id="modalConfigAttributes" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-warning text-dark py-3 px-4">
                <div>
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="bi bi-sliders me-2"></i>Configure Pre-set Attributes
                    </h5>
                    <small class="text-dark-50" id="modalAttrScopeSubtitle">Category-Dependent Scoping Active</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-shield-check fs-5 text-success"></i>
                    <div>
                        <strong>Strict Level Scoping:</strong> Attributes configured here apply specifically to <span class="fw-bold text-primary" id="modalAttrScopeTarget">Selected Branch</span>.
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold small text-dark">Configured Attributes (<span id="modalAttrCount">0</span>)</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addModalAttributeRow()">
                        <i class="bi bi-plus-lg me-1"></i> Add Attribute Row
                    </button>
                </div>

                <div class="table-responsive border rounded-2">
                    <table class="table table-sm table-bordered align-middle mb-0" id="tableModalAttributes">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:180px;">Attribute Name <span class="text-danger">*</span></th>
                                <th style="min-width:200px;">Options</th>
                                <th style="width:75px;" class="text-center">Required?</th>
                                <th style="width:40px;" class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="modalAttributesTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between align-items-center">
                <span class="text-muted small"><i class="bi bi-info-circle text-primary me-1"></i>Changes take effect immediately on inwarding.</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success fw-semibold px-4 shadow-sm" id="btnSaveModalAttrs" onclick="submitModalAttributes()">
                        <i class="bi bi-check2-circle me-1"></i> Save Attributes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: VIEW CATEGORY DETAILS AND PRODUCTS -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalViewCategoryHierarchy" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="item-icon-badge" style="background:#3b82f6; color:#fff; width:42px; height:42px; font-size:1.25rem; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="modal-title fw-bold mb-0 text-white" id="viewModalCatTitle">Category Details</h5>
                        </div>
                        <div class="small text-white-50 mt-1" id="viewModalCatBreadcrumb" aria-live="polite"></div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="px-4 pt-2 pb-2 border-bottom bg-white">
                <ul class="nav nav-pills gap-2" id="viewCategoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold py-1 px-3 rounded-pill d-flex align-items-center gap-1" id="view-cat-details-tab" data-bs-toggle="pill" data-bs-target="#view-cat-details-pane" type="button" role="tab" aria-controls="view-cat-details-pane" aria-selected="true">
                            <i class="bi bi-diagram-2"></i> Category Hierarchy &amp; Attributes
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold py-1 px-3 rounded-pill d-flex align-items-center gap-1" id="view-cat-products-tab" data-bs-toggle="pill" data-bs-target="#view-cat-products-pane" type="button" role="tab" aria-controls="view-cat-products-pane" aria-selected="false">
                            <i class="bi bi-boxes"></i> Category Products
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4 bg-light">
                <div class="tab-content" id="viewCategoryTabsContent">
                    <div class="tab-pane fade show active" id="view-cat-details-pane" role="tabpanel" aria-labelledby="view-cat-details-tab">
                        <div class="card border-0 shadow-xs rounded-3 bg-white mb-3">
                            <div class="card-body p-3">
                                <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                                    <i class="bi bi-folder-fill text-primary"></i> Category Information
                                </h6>
                                <div class="row g-2 text-dark small">
                                    <div class="col-12">
                                        <span class="text-muted d-block" style="font-size:0.75rem;">Name</span>
                                        <strong id="viewDetailCatName">—</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card border-0 shadow-xs rounded-3 bg-white mb-3">
                            <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-diagram-3 text-info"></i> Subcategory Tree &amp; Scope
                                </h6>
                                <span class="badge bg-secondary-subtle text-secondary" id="viewSubTreeCountBadge">0 Subcategories</span>
                            </div>
                            <div class="card-body p-3" id="viewSubcategoriesTreeContainer"></div>
                        </div>
                        <div class="card border-0 shadow-xs rounded-3 bg-white mb-3">
                            <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-tags text-success"></i> Sub-subcategories
                                </h6>
                                <span class="badge bg-success-subtle text-success" id="viewSubSubTreeCountBadge">0 Sub-subcategories</span>
                            </div>
                            <div class="card-body p-3" id="viewSubSubcategoriesTreeContainer"></div>
                        </div>
                        <div class="card border-0 shadow-xs rounded-3 bg-white">
                            <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-sliders text-warning"></i> Pre-set Category Specifications (Scoped Attributes)
                                </h6>
                                <span class="badge bg-warning-subtle text-dark border border-warning" id="viewAttrCountBadge">0 Attributes</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" id="tableViewScopedAttrs">
                                        <thead class="table-light" style="font-size:0.75rem;">
                                            <tr>
                                                <th style="width:24%;">Attribute Name</th>
                                                <th style="width:26%;">Applies To</th>
                                                <th class="pe-3" style="width:50%;">Options / Dropdown Values</th>
                                            </tr>
                                        </thead>
                                        <tbody id="viewScopedAttrsTableBody" style="font-size:0.82rem;"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="view-cat-products-pane" role="tabpanel" aria-labelledby="view-cat-products-tab">
                        <div class="card border-0 shadow-xs rounded-3 bg-white mb-3">
                            <div class="card-body p-3">
                                <div class="row g-2 align-items-center">
                                    <div class="col-lg-5">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-search"></i></span>
                                            <input type="search" id="viewCatProductSearchInput" class="form-control border-start-0" placeholder="Search products in this category..." oninput="filterViewCategoryProducts()">
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <select id="viewCatSubcatFilterSelect" class="form-select form-select-sm" onchange="populateViewSubSubcategoryFilter();filterViewCategoryProducts()">
                                            <option value="">All Subcategories</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <select id="viewCatSubSubcatFilterSelect" class="form-select form-select-sm" onchange="filterViewCategoryProducts()" disabled>
                                            <option value="">All Sub-subcategories</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-1 text-lg-end">
                                        <span class="badge bg-light text-dark border font-monospace" id="viewCatProductFilteredCount">0 products</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card border-0 shadow-xs rounded-3 bg-white">
                            <div class="table-responsive" style="max-height:480px;overflow-y:auto;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light sticky-top" style="font-size:.75rem;">
                                        <tr>
                                            <th class="ps-3">#</th>
                                            <th>Product / Style</th>
                                            <th>Product Code / SKU</th>
                                            <th>Category Path</th>
                                            <th class="text-center">Variants</th>
                                            <th class="text-end pe-3">Stock &amp; Price</th>
                                        </tr>
                                    </thead>
                                    <tbody id="viewCategoryProductsTableBody">
                                        <tr><td colspan="6" class="text-center py-5 text-muted">Open this tab to view products in this category.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white py-2 px-4 d-flex justify-content-between align-items-center">
                <span class="text-muted small"><i class="bi bi-diagram-2 text-primary me-1"></i>Category details and products are scoped to this category branch.</span>
                <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: ACTION ALERT / VALIDATION WARNING -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalCatalogActionAlert" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header bg-danger text-white py-3 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" id="modalCatalogActionAlertTitle">
                    <i class="bi bi-exclamation-octagon-fill"></i> Cannot Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width:68px;height:68px;">
                        <i class="bi bi-boxes fs-1"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-2" id="modalCatalogActionAlertHeading">Category Already Assigned to Products</h5>
                <p class="text-secondary small mb-3" id="modalCatalogActionAlertMessage" style="line-height:1.6; word-break:break-word;"></p>
                <div class="alert alert-warning py-2 px-3 small text-start mb-0">
                    <i class="bi bi-info-circle me-1 fw-bold"></i>
                    <strong>Action Required:</strong> To delete this category or subcategory, first reassign or remove the linked product styles from your warehouse catalog.
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 justify-content-center">
                <button type="button" class="btn btn-dark px-4 fw-semibold" data-bs-dismiss="modal">OK, Understood</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT LOGIC -->
<!-- ========================================================================= -->
<script>
const CSRF_TOKEN = '<?php echo addslashes(CSRF::token()); ?>';
const APP_URL = '<?php echo addslashes(rtrim(url(''), '/')); ?>';
const canCreateCatalog = <?= json_encode($canCreateCatalog) ?>;
const canEditCatalog = <?= json_encode($canEditCatalog) ?>;
const canDeleteCatalog = <?= json_encode($canDeleteCatalog) ?>;

let categoryTreeData = [];
let currentViewMode = localStorage.getItem('grm_catalog_view_mode') || 'list'; // 'list' or 'columns'
let currentSearchTerm = '';
let currentTypeFilter = 'all';

// Selected state for columns view
let selectedCatId = null, selectedCatName = '';
let selectedSubId = null, selectedSubName = '';
let selectedSubSubId = null, selectedSubSubName = '';
let selectedNode = null;

// Modal attributes state
let modalAttrTarget = { type: null, catId: null, subId: null, sscId: null, path: '' };

function formatCatTypeBadge(type) {
    const t = (type || 'NORMAL').toUpperCase();
    if (t === 'COMPLEMENTARY') {
        return `<span class="badge" style="background:#f3e8ff;color:#7e22ce;border:1px solid #d8b4fe;font-size:0.68rem;font-weight:600;"><i class="bi bi-gift-fill me-1"></i>Complementary</span>`;
    } else if (t === 'SERIES') {
        return `<span class="badge" style="background:#e0e7ff;color:#4338ca;border:1px solid #c7d2fe;font-size:0.68rem;font-weight:600;"><i class="bi bi-collection-fill me-1"></i>Series</span>`;
    }
    return `<span class="badge bg-light text-secondary border" style="font-size:0.68rem;font-weight:600;">Normal</span>`;
}

document.addEventListener('DOMContentLoaded', () => {
    switchCatalogView(currentViewMode, false);
    reloadCategoryTree();
});

// View Switcher: List View (Image 1 Style) vs Cascading Columns View (Image 2 Style)
function switchCatalogView(mode, persist = true) {
    currentViewMode = mode;
    if (persist) {
        localStorage.setItem('grm_catalog_view_mode', mode);
    }
    const listCnt = document.getElementById('catalogListViewContainer');
    const colsCnt = document.getElementById('catalogColumnsViewContainer');
    const btnList = document.getElementById('btnToggleList');
    const btnCols = document.getElementById('btnToggleColumns');

    if (mode === 'columns') {
        if (listCnt) listCnt.style.display = 'none';
        if (colsCnt) colsCnt.style.display = '';
        if (btnList) { btnList.className = 'btn btn-sm rounded-pill px-3 fw-semibold btn-light text-muted'; }
        if (btnCols) { btnCols.className = 'btn btn-sm rounded-pill px-3 fw-semibold active btn-primary shadow-sm'; }
    } else {
        if (listCnt) listCnt.style.display = '';
        if (colsCnt) colsCnt.style.display = 'none';
        if (btnList) { btnList.className = 'btn btn-sm rounded-pill px-3 fw-semibold active btn-primary shadow-sm'; }
        if (btnCols) { btnCols.className = 'btn btn-sm rounded-pill px-3 fw-semibold btn-light text-muted'; }
    }
}

// Global Search & Filters
function handleGlobalSearch(term) {
    currentSearchTerm = (term || '').toLowerCase().trim();
    applyFilters();
}

function applyTypeFilter(type) {
    currentTypeFilter = type;
    applyFilters();
}

function applyFilters() {
    renderCategoryListTable();
    ['categoriesListContainer', 'subcategoriesListContainer', 'subSubcategoriesListContainer'].forEach(id => {
        filterColList(id, currentSearchTerm);
    });
}

function resetFilters() {
    currentSearchTerm = '';
    currentTypeFilter = 'all';
    const sInput = document.getElementById('globalCatalogSearch'); if (sInput) sInput.value = '';
    const tpSelect = document.getElementById('catalogTypeFilter'); if (tpSelect) tpSelect.value = 'all';
    ['filterCatInput', 'filterSubInput', 'filterSubSubInput'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    document.querySelectorAll('.col-list-item').forEach(el => el.style.display = '');
    reloadCategoryTree();
}

function filterColList(containerId, query) {
    const term = (query || '').toLowerCase().trim();
    document.querySelectorAll(`#${containerId} .col-list-item`).forEach(el => {
        el.style.display = (!term || (el.getAttribute('data-search-text') || el.textContent.toLowerCase()).includes(term)) ? '' : 'none';
    });
}

// Reload Category Tree Data from Server
function reloadCategoryTree(tCat = null, tSub = null, tSsc = null) {
    const tbody = document.getElementById('catalogListTableBody');
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><div class="small fw-semibold">Loading Category Hierarchy Flow...</div></td></tr>`;
    }
    const catCol = document.getElementById('categoriesListContainer');
    if (catCol) {
        catCol.innerHTML = `<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><div class="small">Loading categories...</div></div>`;
    }

    fetch(`${APP_URL}/api/warehouse/catalog/category-tree`, {
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
        .then(async response => {
            const rawText = await response.text();
            if (response.redirected || response.status >= 400 || (response.url && response.url.includes('/login'))) {
                throw new Error('Your session is no longer valid or the catalog tree endpoint is unavailable. Please sign in again.');
            }

            let data = {};
            try {
                data = rawText ? JSON.parse(rawText) : {};
            } catch (e) {
                throw new Error('The catalog tree response was not valid JSON.');
            }

            if (!data.success || !data.tree) {
                throw new Error(data.message || 'Failed to load category tree data.');
            }

            return data;
        })
        .then(data => {
            categoryTreeData = data.tree;
            updateKpiCounts();
            renderCategoryListTable();
            renderCategoriesList(tCat, tSub, tSsc);
        })
        .catch(err => {
            const msg = err && err.message ? err.message : 'Failed to load the catalog hierarchy.';
            if (tbody) tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-danger small"><i class="bi bi-exclamation-triangle me-2"></i>${msg}</td></tr>`;
            if (catCol) catCol.innerHTML = `<div class="alert alert-danger small p-2 m-2">${msg}</div>`;
        });
}

function updateKpiCounts() {
    let cats = categoryTreeData.length, subs = 0, attrs = 0;
    categoryTreeData.forEach(c => {
        attrs += (c.attr_count || (c.attributes || []).length || 0);
        (c.subcategories || []).forEach(s => {
            subs++;
            attrs += (s.attr_count || (s.attributes || []).length || 0);
            (s.sub_subcategories || []).forEach(ss => {
                attrs += (ss.attr_count || (ss.attributes || []).length || 0);
            });
        });
    });
    [['kpiCatCount', cats], ['kpiSubCount', subs], ['kpiAttrCount', attrs]].forEach(([id, v]) => {
        const el = document.getElementById(id); if (el) el.textContent = v;
    });
}

function esc(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function filterSizeOnlyAttributes(attrs) {
    const list = Array.isArray(attrs) ? attrs : [];
    return list.filter(attr => {
        const name = String(attr && attr.attr_name ? attr.attr_name : '').trim().toLowerCase();
        return name.includes('size') || name.includes('dimension');
    });
}

function inArr(n, h) { return Array.isArray(h) && h.includes(n); }

function getCategoryBranchAttributes(category) {
    const attributes = (category.attributes || []).map(attr => ({
        ...attr,
        scope_name: category.name || 'Category'
    }));

    (category.subcategories || []).forEach(subcategory => {
        (subcategory.attributes || []).forEach(attr => {
            attributes.push({
                ...attr,
                scope_name: subcategory.name || 'Subcategory'
            });
        });

        (subcategory.sub_subcategories || []).forEach(subSubcategory => {
            (subSubcategory.attributes || []).forEach(attr => {
                attributes.push({
                    ...attr,
                    scope_name: `${subcategory.name || 'Subcategory'} › ${subSubcategory.name || 'Sub-subcategory'}`
                });
            });
        });
    });

    return attributes;
}

// =========================================================================
// RENDER VIEW 1: CATEGORY HIERARCHY FLOW IN LIST VIEW (IMAGE 1 UI STYLE)
// =========================================================================
function renderCategoryListTable() {
    const tbody = document.getElementById('catalogListTableBody');
    if (!tbody) return;

    if (!categoryTreeData || categoryTreeData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-muted">
            <i class="bi bi-folder-x fs-1 opacity-40 d-block mb-2"></i>
            <div class="fw-bold">No Categories Configured</div>
            <p class="small text-muted mb-3">Get started by creating your primary department category.</p>
            <button class="btn btn-primary btn-sm fw-semibold" onclick="openAddCategoryModal()"><i class="bi bi-plus-circle me-1"></i> Add Category</button>
        </td></tr>`;
        return;
    }

    // Filter by search term & category type
    const term = currentSearchTerm;
    const typeFilter = currentTypeFilter;
    const filtered = categoryTreeData.filter(cat => {
        if (typeFilter && typeFilter !== 'all') {
            const catType = (cat.category_type || 'NORMAL').toUpperCase();
            if (catType !== typeFilter.toUpperCase()) return false;
        }
        if (!term) return true;
        if (cat.name.toLowerCase().includes(term) || (cat.code && cat.code.toLowerCase().includes(term))) return true;
        const subMatch = (cat.subcategories || []).some(s => {
            return s.name.toLowerCase().includes(term) || (s.code && s.code.toLowerCase().includes(term));
        });
        if (subMatch) return true;
        const attrMatch = getCategoryBranchAttributes(cat).some(a => (a.attr_name || '').toLowerCase().includes(term));
        return attrMatch;
    });

    const sumEl = document.getElementById('catalogListSummaryText');
    if (sumEl) sumEl.textContent = `Showing ${filtered.length} of ${categoryTreeData.length} Category Hierarchies`;

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-muted">
            <i class="bi bi-search fs-2 opacity-30 d-block mb-2"></i>
            <div class="fw-semibold">No matching categories found</div>
            <div class="small">Try resetting your search query or category type filter.</div>
        </td></tr>`;
        return;
    }

    let html = '';
    filtered.forEach((cat, idx) => {
        const subcats = cat.subcategories || [];
        const allSubSubs = [];
        subcats.forEach(s => {
            (s.sub_subcategories || []).forEach(ss => allSubSubs.push(Object.assign({}, ss, { subcategory_id: s.id })));
        });
        const branchAttrs = getCategoryBranchAttributes(cat);

        const rowId = `cat_row_${cat.id}`;

        html += `
        <tr class="cat-table-row" id="${rowId}" style="border-bottom:1px solid #f1f5f9;">
            <!-- 1. CATEGORY / DEPARTMENT -->
            <td style="padding:14px 18px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="item-icon-badge" style="background:#e0e7ff; color:#4338ca;">
                        <i class="bi bi-folder-fill fs-5"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-dark fs-6" style="letter-spacing:0.3px;">${esc(cat.name)}</span>
                            ${cat.code ? `<span class="code-badge fw-bold" style="background:#eef2ff;color:#4f46e5;border-color:#c7d2fe;">${esc(cat.code)}</span>` : ''}
                        </div>
                        <div class="text-muted small mt-0.5 d-flex align-items-center gap-2">
                            <span><i class="bi bi-diagram-2 me-1 text-primary"></i>${subcats.length} Subcategor${subcats.length === 1 ? 'y' : 'ies'}</span>
                            ${allSubSubs.length > 0 ? `<span>• <i class="bi bi-tags me-1 text-success"></i>${allSubSubs.length} Sub-Subcategor${allSubSubs.length === 1 ? 'y' : 'ies'}</span>` : ''}
                        </div>
                    </div>
                </div>
            </td>

            <!-- 2. SUB CATEGORIES -->
            <td style="padding:14px 14px;">
                ${subcats.length > 0 ? `
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        ${subcats.slice(0, 4).map(s => {
                            const ssCount = (s.sub_subcategories || []).length;
                            return `
                            <span class="badge bg-light text-primary border border-primary-subtle px-2 py-1" style="font-size:0.75rem; font-weight:600; cursor:${canEditCatalog ? 'pointer' : 'default'};" ${canEditCatalog ? `onclick="openFullEditCategoryModal(event, ${cat.id}, ${s.id})" title="Click to edit ${esc(s.name)} under ${esc(cat.name)}"` : ''}>
                                <i class="bi bi-folder2 me-1"></i>${esc(s.name)}${ssCount > 0 ? ` <span class="badge bg-primary text-white rounded-pill ms-1" style="font-size:0.65rem;">${ssCount}</span>` : ''}
                            </span>
                            `;
                        }).join('')}
                        ${subcats.length > 4 ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size:0.72rem;">+${subcats.length - 4} more</span>` : ''}
                    </div>
                ` : `<span class="text-muted small" style="font-style:italic;"><i class="bi bi-dash-circle me-1"></i>No subcategories</span>`}
            </td>

            <!-- 3. SUB-SUB CATEGORIES -->
            <td style="padding:14px 14px;">
                ${allSubSubs.length > 0 ? `
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        ${allSubSubs.slice(0, 4).map(ss => `
                            <span class="badge bg-light text-success border border-success-subtle px-2 py-1" style="font-size:0.75rem; font-weight:600; cursor:${canEditCatalog ? 'pointer' : 'default'};" ${canEditCatalog ? `onclick="openFullEditCategoryModal(event, ${cat.id}, ${ss.subcategory_id || 0}, ${ss.id})" title="Click to edit ${esc(ss.name)}"` : ''}>
                                <i class="bi bi-tag me-1"></i>${esc(ss.name)}
                            </span>
                        `).join('')}
                        ${allSubSubs.length > 4 ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size:0.72rem;">+${allSubSubs.length - 4} more</span>` : ''}
                    </div>
                ` : `<span class="text-muted small" style="font-style:italic;"><i class="bi bi-dash-circle me-1"></i>No sub-subcategories</span>`}
            </td>

            <!-- 4. PRE-SET ATTRIBUTES (SCOPED) -->
            <td style="padding:14px 14px;">
                ${branchAttrs.length > 0 ? `
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        ${branchAttrs.slice(0, 3).map(a => `
                            <span class="pill-attr" style="font-size:0.72rem;" title="${esc(a.scope_name)}${a.attr_options ? ` | Options: ${esc(a.attr_options)}` : ''}">
                                <i class="bi bi-sliders me-1"></i>${esc(a.attr_name)}
                                ${a.attr_options ? `<small class="text-muted ms-0.5">(${esc(a.attr_options)})</small>` : ''}
                            </span>
                        `).join('')}
                        ${branchAttrs.length > 3 ? `<span class="badge bg-warning-subtle text-dark border border-warning" style="font-size:0.7rem;">+${branchAttrs.length - 3}</span>` : ''}
                    </div>
                ` : `
                    ${canEditCatalog ? `<button type="button" class="btn btn-sm btn-link text-decoration-none p-0 text-warning fw-semibold" style="font-size:0.76rem;" onclick="openFullEditCategoryModal(event, ${cat.id})">
                        <i class="bi bi-plus-circle me-1"></i>Set attributes
                    </button>` : ''}
                `}
            </td>

            <!-- 5. ACTIONS: VIEW, EDIT, DELETE -->
            <td class="text-end" style="padding:14px 18px;" onclick="event.stopPropagation()">
                <div class="d-inline-flex align-items-center gap-1">
                    <button type="button" class="icon-btn" onclick="openViewCategoryHierarchyModal(event, ${cat.id})" title="View Category Details">
                        <i class="bi bi-eye text-primary"></i>
                    </button>
                    ${canEditCatalog ? `<button type="button" class="icon-btn" onclick="openFullEditCategoryModal(event, ${cat.id})" title="Edit Category &amp; Attributes (Full Form)">
                        <i class="bi bi-pencil"></i>
                    </button>` : ''}
                    ${canDeleteCatalog ? `<button type="button" class="icon-btn danger" onclick="deleteCategory(event, ${cat.id}, '${esc(cat.name)}')" title="Delete Category">
                        <i class="bi bi-trash"></i>
                    </button>` : ''}
                </div>
            </td>
        </tr>
        `;
    });

    tbody.innerHTML = html;
}

// =========================================================================
// RENDER VIEW 2: CASCADING COLUMNS VIEW (IMAGE 2 UI STYLE)
// =========================================================================
function renderCategoriesList(tCat = null, tSub = null, tSsc = null) {
    const cnt = document.getElementById('categoriesListContainer');
    const badge = document.getElementById('badgeCatCount'); if (badge) badge.textContent = categoryTreeData.length;
    if (!categoryTreeData || !categoryTreeData.length) {
        if (cnt) cnt.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-folder-x fs-1 opacity-50 d-block mb-2"></i><div class="fw-semibold">No Categories Configured</div>' + (canCreateCatalog ? '<button class="btn btn-sm btn-primary mt-2" onclick="openAddCategoryModal()">+ Add Category</button>' : '') + '</div>';
        resetSubCol(); resetAttrCol(); return;
    }
    if (cnt) {
        let html = '<div class="d-flex flex-column">';
        categoryTreeData.forEach(cat => {
            const active = selectedCatId === cat.id;
            const sc = (cat.subcategories || []).length;
            const safeName = String(cat.name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            const codeHtml = cat.code ? '<span class="code-badge">' + esc(cat.code) + '</span>' : '';
            const iconBadgeClass = active ? ' active' : '';
            const folderColor = active ? 'text-primary' : 'text-warning';
            const chevronHtml = active ? '<i class="bi bi-chevron-right text-primary" style="font-size:.75rem;"></i>' : '';

            html +=
                '<div class="col-list-item' + (active ? ' active-col-item' : '') + '" data-cat-id="' + cat.id + '" data-search-text="' + esc(cat.name).toLowerCase() + '" onclick="onSelectCategory(' + cat.id + ',\'' + safeName + '\')">' +
                '<div class="d-flex align-items-center justify-content-between">' +
                '<div class="d-flex align-items-center gap-2 text-truncate me-2">' +
                '<div class="item-icon-badge' + iconBadgeClass + '"><i class="bi bi-folder-fill ' + folderColor + '"></i></div>' +
                '<div class="text-truncate">' +
                '<div class="d-flex align-items-center gap-1"><span class="fw-bold text-truncate item-title" style="font-size:.82rem;">' + esc(cat.name) + '</span>' + codeHtml + '</div>' +
                '<div class="d-flex align-items-center gap-1 mt-1"><span class="pill-info">' + sc + ' Subcats</span></div>' +
                '</div></div>' +
                '<div class="d-flex align-items-center gap-1 flex-shrink-0">' +
                '<button type="button" class="icon-btn" onclick="openViewCategoryHierarchyModal(event,' + cat.id + ')" title="View Category Details"><i class="bi bi-eye text-primary"></i></button>' +
                (canEditCatalog ? '<button type="button" class="icon-btn" onclick="openFullEditCategoryModal(event,' + cat.id + ')" title="Edit Category &amp; Attributes (Full Form)"><i class="bi bi-pencil"></i></button>' : '') +
                (canDeleteCatalog ? '<button type="button" class="icon-btn danger" onclick="deleteCategory(event,' + cat.id + ',\'' + safeName + '\')" title="Delete"><i class="bi bi-trash"></i></button>' : '') +
                chevronHtml +
                '</div></div></div>';
        });
        cnt.innerHTML = html + '</div>';
    }
    const toSel = tCat || selectedCatId;
    if (toSel) { const f = categoryTreeData.find(c => c.id === toSel); if (f) { onSelectCategory(f.id, f.name, false, tSub); return; } }
    if (categoryTreeData.length > 0) onSelectCategory(categoryTreeData[0].id, categoryTreeData[0].name, false);
}

function onSelectCategory(catId, catName, user = true, tSub = null) {
    selectedCatId = catId; selectedCatName = catName; selectedSubId = null; selectedSubName = '';
    document.querySelectorAll('#categoriesListContainer .col-list-item').forEach(el => {
        const a = parseInt(el.getAttribute('data-cat-id')) === catId;
        el.classList.toggle('active-col-item', a);
        const ib = el.querySelector('.item-icon-badge'), ic = el.querySelector('i.bi-folder-fill');
        if (ib) ib.classList.toggle('active', a);
        if (ic) ic.className = 'bi bi-folder-fill ' + (a ? 'text-primary' : 'text-warning');
    });
    const t2 = document.getElementById('col2HeaderTitle'); if (t2) t2.textContent = 'Sub Category (' + catName + ')';
    const ba = document.getElementById('btnAddSubcatBtn'); if (ba) ba.disabled = false;
    const cat = categoryTreeData.find(c => c.id === catId);
    renderSubcategoriesList(cat ? (cat.subcategories || []) : [], tSub);
    if (!tSub) syncAttributesTool('cat', catId, null, null, catName);
}

function renderSubcategoriesList(subcats, tSub = null) {
    const cnt = document.getElementById('subcategoriesListContainer');
    const badge = document.getElementById('badgeSubCount'); if (badge) badge.textContent = subcats.length;
    if (!subcats || !subcats.length) {
        if (cnt) cnt.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-folder-plus fs-2 opacity-50 d-block mb-2"></i><div class="small fw-semibold">No Subcategories in "' + esc(selectedCatName) + '"</div>' + (canCreateCatalog ? '<button class="btn btn-sm btn-primary mt-2" onclick="openAddSubcatForSelected()">+ Add Subcategory</button>' : '') + '</div>';
        return;
    }
    if (cnt) {
        let html = '<div class="d-flex flex-column">';
        subcats.forEach(sub => {
            const active = selectedSubId === sub.id;
            const safeName = String(sub.name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            const codeHtml = sub.code ? '<span class="code-badge">' + esc(sub.code) + '</span>' : '';
            const itemBadgeClass = active ? ' active-info' : '';
            const folderColor = active ? 'text-info' : 'text-primary';
            const chevronHtml = active ? '<i class="bi bi-chevron-right text-info" style="font-size:.75rem;"></i>' : '';

            html +=
                '<div class="col-list-item' + (active ? ' active-col-item-info' : '') + '" data-sub-id="' + sub.id + '" data-search-text="' + esc(sub.name).toLowerCase() + '" onclick="onSelectSubcategory(' + sub.id + ',\'' + safeName + '\')">' +
                '<div class="d-flex align-items-center justify-content-between">' +
                '<div class="d-flex align-items-center gap-2 text-truncate me-2">' +
                '<div class="item-icon-badge' + itemBadgeClass + '"><i class="bi bi-folder2-open ' + folderColor + '"></i></div>' +
                '<div class="text-truncate">' +
                '<div class="d-flex align-items-center gap-1"><span class="fw-bold text-truncate item-title" style="font-size:.82rem;">' + esc(sub.name) + '</span>' + codeHtml + '</div>' +
                '</div></div>' +
                '<div class="d-flex align-items-center gap-1 flex-shrink-0">' +
                (canEditCatalog ? '<button type="button" class="icon-btn" onclick="openEditSubcategoryModal(event,' + sub.id + ',\'' + safeName + '\',' + selectedCatId + ',\'' + String(selectedCatName || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'") + '\')" title="Edit"><i class="bi bi-pencil"></i></button>' : '') +
                (canDeleteCatalog ? '<button type="button" class="icon-btn danger" onclick="deleteSubcategory(event,' + sub.id + ',\'' + safeName + '\')" title="Delete"><i class="bi bi-trash"></i></button>' : '') +
                chevronHtml +
                '</div></div></div>';
        });
        cnt.innerHTML = html + '</div>';
    }
    if (tSub) { const f = subcats.find(s => s.id === tSub); if (f) { onSelectSubcategory(f.id, f.name, false); return; } }
    if (subcats.length > 0) onSelectSubcategory(subcats[0].id, subcats[0].name, false);
}

function onSelectSubcategory(subId, subName, user = true) {
    selectedSubId = subId; selectedSubName = subName;
    document.querySelectorAll('#subcategoriesListContainer .col-list-item').forEach(el => {
        el.classList.toggle('active-col-item-info', parseInt(el.getAttribute('data-sub-id')) === subId);
    });
    syncAttributesTool('sub', selectedCatId, subId, null, selectedCatName + ' › ' + subName);
}

function resetSubCol() {
    selectedSubId = null; selectedSubName = '';
    const c = document.getElementById('subcategoriesListContainer'); if (c) c.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-arrow-left-circle fs-3 opacity-50 d-block mb-2"></i><div class="small fw-semibold">Select a Category first</div></div>';
    const b = document.getElementById('badgeSubCount'); if (b) b.textContent = '0';
    const btn = document.getElementById('btnAddSubcatBtn'); if (btn) btn.disabled = true;
    const t = document.getElementById('col2HeaderTitle'); if (t) t.textContent = 'Sub Category';
}

function resetAttrCol() {
    selectedNode = null;
    const list = document.getElementById('attributesInlineList'); if (list) list.innerHTML = '';
    const em = document.getElementById('attrEmptyState'); if (em) em.style.display = '';
    const b = document.getElementById('attrCountBadge'); if (b) b.textContent = '--';
    const s = document.getElementById('labelActiveBranchScope'); if (s) s.textContent = 'Select any category to configure';
    const add = document.getElementById('btnAddAttrRow'); if (add) add.disabled = true;
    const save = document.getElementById('btnSaveAttrs'); if (save) save.disabled = true;
}

function openAddSubcatForSelected() {
    if (!selectedCatId) { alert('Please select a Category first.'); return; }
    openAddSubcategoryModal(null, selectedCatId, selectedCatName);
}

function openAddSubSubcatForSelected() {
    if (!selectedSubId) { alert('Please select a Subcategory first.'); return; }
    openAddSubSubcategoryModal(null, selectedSubId, selectedSubName);
}

// =========================================================================
// ATTRIBUTES INLINE MANAGER (FOR COLUMNS VIEW)
// =========================================================================
function syncAttributesTool(type, catId, subId, sscId, path) {
    selectedNode = { type, catId, subId, sscId, path };
    const s = document.getElementById('labelActiveBranchScope'); if (s) s.textContent = path;
    const t = document.getElementById('col4HeaderTitle');
    if (t) t.innerHTML = '<i class="bi bi-sliders me-1 text-warning"></i>' + (type === 'subsub' ? 'Sub-Sub Attrs' : type === 'sub' ? 'Subcat Attrs' : 'Cat Attrs');
    const add = document.getElementById('btnAddAttrRow'); if (add) add.disabled = false;
    const save = document.getElementById('btnSaveAttrs'); if (save) save.disabled = false;
    loadAttributesInline(catId, subId, sscId);
}

function loadAttributesInline(catId, subId, sscId) {
    const list = document.getElementById('attributesInlineList');
    const em = document.getElementById('attrEmptyState'); if (em) em.style.display = 'none';
    if (list) list.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-warning mb-1"></div><div class="small">Loading...</div></div>';
    let url = APP_URL + '/api/warehouse/catalog/attributes/' + catId + '?exact=1';
    if (subId) url += '&subcategory_id=' + subId;
    if (sscId) url += '&sub_subcategory_id=' + sscId;
    fetch(url).then(function(r){ return r.json(); }).then(function(data){
        if (list) list.innerHTML = '';
        const badge = document.getElementById('attrCountBadge');
        const visibleAttrs = filterSizeOnlyAttributes(data.success && Array.isArray(data.attributes) ? data.attributes : []);
        if (visibleAttrs.length > 0) {
            if (badge) badge.textContent = visibleAttrs.length;
            visibleAttrs.forEach(function(a){ renderAttrRow(a); });
        } else {
            if (badge) badge.textContent = '0';
            if (list) list.innerHTML = '<div class="text-center py-4 text-muted" id="noAttrsPlaceholder"><i class="bi bi-plus-circle-dotted fs-3 opacity-30 d-block mb-2"></i><div class="small">No size attributes defined yet.</div><div style="font-size:.72rem;">Add a size attribute to this branch.</div></div>';
        }
    }).catch(function(err){ if (list) list.innerHTML = '<div class="text-danger small p-3">' + (err && err.message ? err.message : 'Unable to load attributes') + '</div>'; });
}

function renderAttrRow(attr) {
    const list = document.getElementById('attributesInlineList');
    const div = document.createElement('div'); div.className = 'attr-row';
    const a = attr || { attr_name: '', attr_options: '', is_required: 0 };
    if (!canEditCatalog) {
        div.className = 'attr-row py-2 px-3 border-bottom';
        div.innerHTML = '<div class="d-flex justify-content-between gap-2"><strong>' + esc(a.attr_name || '') + '</strong><span class="text-muted">' + esc(a.attr_options || '') + '</span></div>';
        list.appendChild(div);
        return;
    }
    div.innerHTML = '<div class="attr-row-inner">' +
        '<div class="ar-name"><input type="text" class="form-control form-control-sm attr-name fw-semibold text-uppercase" value="' + esc(a.attr_name || '') + '" placeholder="Attribute Name" oninput="this.value=this.value.toUpperCase()" required></div>' +
        '<div class="ar-opts">' +
        '<input type="text" class="form-control form-control-sm attr-opts text-uppercase" value="' + esc(a.attr_options || '') + '" placeholder="e.g. S, M, L, XL or Red, Blue" oninput="this.value=this.value.toUpperCase()" required>' +
        '</div>' +
        '<div class="ar-req text-center"><div class="form-check form-switch d-inline-block mb-0"><input class="form-check-input attr-req" type="checkbox" ' + (a.is_required ? 'checked' : '') + '></div></div>' +
        '<div class="ar-del text-center"><button type="button" class="icon-btn danger" onclick="this.closest(\'.attr-row\').remove();updateAttrBadge();" title="Remove"><i class="bi bi-trash"></i></button></div>' +
        '</div>';
    list.appendChild(div);
}

function updateAttrBadge() {
    const c = document.querySelectorAll('#attributesInlineList .attr-row').length;
    const b = document.getElementById('attrCountBadge'); if (b) b.textContent = c;
}

function addAttributeRow(preset = null) {
    if (!canEditCatalog) return;
    const p = document.getElementById('noAttrsPlaceholder'); if (p) p.remove();
    const em = document.getElementById('attrEmptyState'); if (em) em.style.display = 'none';
    renderAttrRow(preset || { attr_name: '', attr_type: 'SELECT', attr_options: '', is_required: 0 });
    updateAttrBadge();
}

function saveCategoryAttributes() {
    if (!canEditCatalog || !selectedNode) return;
    const btn = document.getElementById('btnSaveAttrs');
    const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    const attrs = [];
    document.querySelectorAll('#attributesInlineList .attr-row').forEach((r, i) => {
        const n = r.querySelector('.attr-name').value.trim(); if (!n) return;
        attrs.push({ attr_name: n, attr_type: 'SELECT', attr_options: r.querySelector('.attr-opts').value.trim(), is_required: r.querySelector('.attr-req').checked ? 1 : 0, sort_order: i + 1 });
    });
    const p = new URLSearchParams();
    p.append('_csrf_token', CSRF_TOKEN); p.append('category_id', selectedNode.catId);
    if (selectedNode.subId) p.append('subcategory_id', selectedNode.subId);
    if (selectedNode.sscId) p.append('sub_subcategory_id', selectedNode.sscId);
    p.append('attributes', JSON.stringify(attrs));
    fetch(APP_URL + '/api/warehouse/catalog/attributes/save', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: p.toString() })
        .then(function(r){ return r.json(); }).then(function(data){
            btn.disabled = false; btn.innerHTML = orig;
            if (data.success) { showToast('Saved', 'Attributes saved for "' + selectedNode.path + '"!', 'success'); reloadCategoryTree(selectedNode.catId, selectedNode.subId, selectedNode.sscId); }
            else showToast('Error', data.message || 'Error saving', 'danger');
        }).catch(function(err){ btn.disabled = false; btn.innerHTML = orig; showToast('Error', err.message, 'danger'); });
}

// =========================================================================
// MODAL ATTRIBUTES MANAGER (FOR LIST VIEW DIRECT CONFIGURATION)
// =========================================================================
function openConfigAttributesModal(e, type, catId, subId, sscId, path) {
    if (e) e.stopPropagation();
    modalAttrTarget = { type, catId, subId, sscId, path };
    document.getElementById('modalAttrScopeTarget').textContent = path;
    document.getElementById('modalAttrScopeSubtitle').textContent = 'Target: ' + path;
    document.getElementById('modalAttributesTableBody').innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-warning mb-1"></div><div>Loading existing attributes...</div></td></tr>';
    new bootstrap.Modal(document.getElementById('modalConfigAttributes')).show();

    let url = APP_URL + '/api/warehouse/catalog/attributes/' + catId + '?exact=1';
    if (subId) url += '&subcategory_id=' + subId;
    if (sscId) url += '&sub_subcategory_id=' + sscId;

    fetch(url).then(function(r){ return r.json(); }).then(function(data){
        const tb = document.getElementById('modalAttributesTableBody');
        tb.innerHTML = '';
        if (data.success && data.attributes && data.attributes.length > 0) {
            data.attributes.forEach(function(a){ addModalAttributeRow(a); });
        } else {
            tb.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted" id="modalNoAttrsRow">No attributes configured yet. Click <strong>+ Add Attribute Row</strong> or use Quick Add buttons above.</td></tr>';
            updateModalAttrCount();
        }
    }).catch(function(err){
        document.getElementById('modalAttributesTableBody').innerHTML = '<tr><td colspan="5" class="text-danger small p-3">' + (err && err.message ? err.message : 'Unable to load attributes') + '</td></tr>';
    });
}

function quickAddModalAttr(name, type, opts, req = 0) {
    const noRow = document.getElementById('modalNoAttrsRow'); if (noRow) noRow.remove();
    const tb = document.getElementById('modalAttributesTableBody');
    const ex = Array.from(tb.querySelectorAll('.ma-name')).find(i => i.value.trim().toLowerCase() === name.toLowerCase());
    if (ex) { ex.focus(); ex.classList.add('is-valid'); setTimeout(() => ex.classList.remove('is-valid'), 1000); return; }
    addModalAttributeRow({ attr_name: name, attr_type: type, attr_options: opts, is_required: req });
}

function addModalAttributeRow(attr = null) {
    const noRow = document.getElementById('modalNoAttrsRow'); if (noRow) noRow.remove();
    const tb = document.getElementById('modalAttributesTableBody');
    const a = attr || { attr_name: '', attr_options: '', is_required: 0 };
    const tr = document.createElement('tr'); tr.className = 'ma-row';
    tr.innerHTML =     '<td><input type="text" class="form-control form-control-sm ma-name fw-semibold text-uppercase" value="' + esc(a.attr_name) + '" placeholder="e.g. Size, Fabric, Sleeve" oninput="this.value=this.value.toUpperCase()" required></td>' +
    '<td><input type="text" class="form-control form-control-sm ma-opts text-uppercase" value="' + esc(a.attr_options || '') + '" placeholder="e.g. S, M, L, XL or Red, Blue" oninput="this.value=this.value.toUpperCase()" required></td>' +
        '<td class="text-center"><div class="form-check form-switch d-inline-block"><input class="form-check-input ma-req" type="checkbox" ' + (a.is_required ? 'checked' : '') + '></div></td>' +
        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.closest(\'tr\').remove(); updateModalAttrCount();" title="Remove"><i class="bi bi-trash"></i></button></td>';
    tb.appendChild(tr);
    updateModalAttrCount();
}

function handleMAType(sel) {
    // Dropdown is only type available
}

function updateModalAttrCount() {
    const count = document.querySelectorAll('#modalAttributesTableBody tr.ma-row').length;
    const badge = document.getElementById('modalAttrCount'); if (badge) badge.textContent = count;
}

function submitModalAttributes() {
    if (!modalAttrTarget || !modalAttrTarget.catId) return;
    const btn = document.getElementById('btnSaveModalAttrs');
    const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    const attrs = [];
    document.querySelectorAll('#modalAttributesTableBody tr.ma-row').forEach((r, i) => {
        const n = r.querySelector('.ma-name').value.trim(); if (!n) return;
        attrs.push({
            attr_name: n,
            attr_type: 'SELECT',
            attr_options: r.querySelector('.ma-opts').value.trim(),
            is_required: r.querySelector('.ma-req').checked ? 1 : 0,
            sort_order: i + 1
        });
    });

    const p = new URLSearchParams();
    p.append('_csrf_token', CSRF_TOKEN);
    p.append('category_id', modalAttrTarget.catId);
    if (modalAttrTarget.subId) p.append('subcategory_id', modalAttrTarget.subId);
    if (modalAttrTarget.sscId) p.append('sub_subcategory_id', modalAttrTarget.sscId);
    p.append('attributes', JSON.stringify(attrs));

    fetch(APP_URL + '/api/warehouse/catalog/attributes/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: p.toString()
    })
    .then(function(r){ return r.json(); })
    .then(function(data){
        btn.disabled = false; btn.innerHTML = orig;
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalConfigAttributes')).hide();
            showToast('Success', 'Attributes saved for "' + modalAttrTarget.path + '"!', 'success');
            reloadCategoryTree(modalAttrTarget.catId, modalAttrTarget.subId, modalAttrTarget.sscId);
        } else {
            alert(data.message || 'Error saving attributes');
        }
    })
    .catch(function(err){
        btn.disabled = false; btn.innerHTML = orig;
        alert('Error: ' + err.message);
    });
}

// =========================================================================
// UNIFIED ADD CATEGORY HIERARCHY MODAL
// =========================================================================
function openAddCategoryModal() {
    document.getElementById('formUnifiedCategory').reset();
    document.getElementById('unifiedAttributesTableBody').innerHTML = '';
    addUnifiedAttributeRow();

    // Populate Category Dropdown with existing category names
    const catSel = document.getElementById('unifiedCatSelect');
    if (catSel) {
        let catOpts = '<option value="">-- Select Existing Category --</option>';
        catOpts += '<option value="__NEW__" class="fw-bold text-primary">+ Add New Category</option>';
        (categoryTreeData || []).forEach(c => {
            catOpts += `<option value="${c.id}" data-name="${esc(c.name)}">${esc(c.name)}</option>`;
        });
        catSel.innerHTML = catOpts;
    }

    // Default to dropdown if categories exist, or Add New input
    if (categoryTreeData && categoryTreeData.length > 0) {
        cancelUnifiedNewCat();
    } else {
        toggleUnifiedNewCat(true);
    }

    // Initialize subcategories list (strict isolation: none by default)
    populateUnifiedSubcategories(null);

    new bootstrap.Modal(document.getElementById('modalUnifiedCategory')).show();
    setTimeout(() => {
        const inpWrap = document.getElementById('unifiedCatInputWrap');
        const inp = document.getElementById('unifiedCatName');
        const sel = document.getElementById('unifiedCatSelect');
        if (inpWrap && inpWrap.style.display !== 'none' && inp) {
            inp.focus();
        } else if (sel) {
            sel.focus();
        }
    }, 400);
}

function onUnifiedCatSelectChange() {
    const sel = document.getElementById('unifiedCatSelect');
    const val = sel ? sel.value : '';

    if (val === '__NEW__') {
        toggleUnifiedNewCat(true);
        populateUnifiedSubcategories(null);
        return;
    }

    cancelUnifiedNewCat();

    if (!val) {
        populateUnifiedSubcategories(null);
        return;
    }

    const catObj = (categoryTreeData || []).find(c => String(c.id) === String(val));
    populateUnifiedSubcategories(catObj || null);
}

function toggleUnifiedNewCat(forceNew = true) {
    const selWrap = document.getElementById('unifiedCatSelectWrap');
    const inpWrap = document.getElementById('unifiedCatInputWrap');
    const inp = document.getElementById('unifiedCatName');
    const btn = document.getElementById('btnToggleNewUnifiedCat');
    const help = document.getElementById('unifiedCatHelpText');

    if (selWrap) selWrap.style.display = 'none';
    if (inpWrap) inpWrap.style.display = 'block';
    if (btn) btn.style.display = 'none';
    if (help) help.innerHTML = '<span class="text-primary fw-semibold"><i class="bi bi-pencil-square me-1"></i>Type new category name</span>';
    if (inp) {
        inp.value = '';
        setTimeout(() => inp.focus(), 100);
    }
    // Brand new category has ZERO existing subcategories!
    populateUnifiedSubcategories(null);
}

function cancelUnifiedNewCat() {
    const selWrap = document.getElementById('unifiedCatSelectWrap');
    const inpWrap = document.getElementById('unifiedCatInputWrap');
    const inp = document.getElementById('unifiedCatName');
    const btn = document.getElementById('btnToggleNewUnifiedCat');
    const sel = document.getElementById('unifiedCatSelect');
    const help = document.getElementById('unifiedCatHelpText');

    if (selWrap) selWrap.style.display = 'block';
    if (inpWrap) inpWrap.style.display = 'none';
    if (btn) btn.style.display = '';
    if (inp) inp.value = '';
    if (help) help.textContent = 'Select existing or click + Add New';

    if (sel && sel.value === '__NEW__') {
        sel.value = '';
    }
    const val = sel ? sel.value : '';
    const catObj = val ? (categoryTreeData || []).find(c => String(c.id) === String(val)) : null;
    populateUnifiedSubcategories(catObj || null);
}

function normalizeUnifiedHierarchyName(name) {
    return String(name || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
}

function onUnifiedCatNameInput() {
    const val = (document.getElementById('unifiedCatName')?.value || '').trim();
    const help = document.getElementById('unifiedCatHelpText');
    if (!val) {
        populateUnifiedSubcategories(null);
        if (help) help.innerHTML = '<span class="text-primary fw-semibold"><i class="bi bi-pencil-square me-1"></i>Type new category name</span>';
        return;
    }
    const matched = (categoryTreeData || []).find(c =>
        (c.name || '').trim().toLowerCase() === val.toLowerCase() ||
        normalizeUnifiedHierarchyName(c.name) === normalizeUnifiedHierarchyName(val)
    );
    populateUnifiedSubcategories(matched || null);
    if (help) {
        help.innerHTML = matched
            ? `<span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Category "${esc(matched.name)}" already exists. Select it from the list.</span>`
            : '<span class="text-primary fw-semibold"><i class="bi bi-pencil-square me-1"></i>Type new category name</span>';
    }
}

function getCurrentlySelectedCatObj() {
    const catInputWrap = document.getElementById('unifiedCatInputWrap');
    const catInput = document.getElementById('unifiedCatName');
    const catSelect = document.getElementById('unifiedCatSelect');

    if (catInputWrap && catInputWrap.style.display !== 'none' && catInput && catInput.value.trim()) {
        const val = catInput.value.trim().toLowerCase();
        return (categoryTreeData || []).find(c => (c.name || '').trim().toLowerCase() === val) || null;
    }
    if (catSelect && catSelect.value && catSelect.value !== '__NEW__') {
        return (categoryTreeData || []).find(c => String(c.id) === String(catSelect.value)) || null;
    }
    return null;
}

function populateUnifiedSubcategories(catObj) {
    const sel = document.getElementById('unifiedSubcatSelect');
    const help = document.getElementById('unifiedSubcatHelpText');
    const btnCancel = document.getElementById('btnCancelUnifiedSubcat');
    if (!sel) return;

    // STRICT ISOLATION: ONLY subcategories belonging strictly to catObj!
    // Never show subcategories of other categories!
    const subcats = (catObj && Array.isArray(catObj.subcategories)) ? catObj.subcategories : [];

    let opts = '<option value="">-- None (Optional) --</option>';
    opts += '<option value="__NEW__" class="fw-bold text-primary">+ Add New Sub Category</option>';
    subcats.forEach(s => {
        opts += `<option value="${s.id}" data-name="${esc(s.name)}">${esc(s.name)}</option>`;
    });
    sel.innerHTML = opts;

    if (subcats.length > 0) {
        cancelUnifiedNewSubcat();
        if (help) help.textContent = `Existing subcategories under "${catObj.name}"`;
        if (btnCancel) btnCancel.style.display = '';
    } else {
        cancelUnifiedNewSubcat();
        if (help) {
            help.textContent = catObj ? `No subcategories under "${catObj.name}". Add one if needed.` : 'Add a subcategory if needed.';
        }
        if (btnCancel) btnCancel.style.display = '';
    }
    populateUnifiedSubSubcategories(null);
}

function onUnifiedSubcatSelectChange() {
    const sel = document.getElementById('unifiedSubcatSelect');
    const val = sel ? sel.value : '';

    if (val === '__NEW__') {
        toggleUnifiedNewSubcat(true);
        populateUnifiedSubSubcategories(null);
        return;
    }

    cancelUnifiedNewSubcat();

    if (!val) {
        populateUnifiedSubSubcategories(null);
        return;
    }

    // Find subcategory strictly under current category
    const currentCat = getCurrentlySelectedCatObj();
    let subObj = null;
    if (currentCat && Array.isArray(currentCat.subcategories)) {
        subObj = currentCat.subcategories.find(s => String(s.id) === String(val));
    }
    if (!subObj) {
        for (const c of (categoryTreeData || [])) {
            const found = (c.subcategories || []).find(s => String(s.id) === String(val));
            if (found) { subObj = found; break; }
        }
    }
    populateUnifiedSubSubcategories(subObj || null);
}

function toggleUnifiedNewSubcat(forceNew = true) {
    const selWrap = document.getElementById('unifiedSubcatSelectWrap');
    const inpWrap = document.getElementById('unifiedSubcatInputWrap');
    const inp = document.getElementById('unifiedSubcatName');
    const btn = document.getElementById('btnToggleNewUnifiedSubcat');

    if (selWrap) selWrap.style.display = 'none';
    if (inpWrap) inpWrap.style.display = 'block';
    if (btn) btn.style.display = 'none';
    if (inp) {
        inp.value = '';
        setTimeout(() => inp.focus(), 100);
    }
    populateUnifiedSubSubcategories(null);
}

function cancelUnifiedNewSubcat() {
    const selWrap = document.getElementById('unifiedSubcatSelectWrap');
    const inpWrap = document.getElementById('unifiedSubcatInputWrap');
    const inp = document.getElementById('unifiedSubcatName');
    const btn = document.getElementById('btnToggleNewUnifiedSubcat');
    const sel = document.getElementById('unifiedSubcatSelect');

    if (selWrap) selWrap.style.display = 'block';
    if (inpWrap) inpWrap.style.display = 'none';
    if (btn) btn.style.display = '';
    if (inp) inp.value = '';
    if (sel && sel.value === '__NEW__') sel.value = '';
}

function findUnifiedSelectedSubcategory() {
    const category = getCurrentlySelectedCatObj();
    const subcategorySelect = document.getElementById('unifiedSubcatSelect');
    const selectedId = subcategorySelect && subcategorySelect.value !== '__NEW__' ? subcategorySelect.value : '';
    return (category?.subcategories || []).find(subcategory => String(subcategory.id) === String(selectedId)) || null;
}

function onUnifiedSubcatNameInput() {
    const input = document.getElementById('unifiedSubcatName');
    const help = document.getElementById('unifiedSubcatHelpText');
    const category = getCurrentlySelectedCatObj();
    const name = (input?.value || '').trim();
    if (!help) return;
    const duplicate = name && (category?.subcategories || []).find(subcategory =>
        (subcategory.name || '').trim().toLowerCase() === name.toLowerCase() ||
        normalizeUnifiedHierarchyName(subcategory.name) === normalizeUnifiedHierarchyName(name)
    );
    help.innerHTML = duplicate
        ? `<span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Subcategory "${esc(duplicate.name)}" already exists. Select it from the list.</span>`
        : `<span class="text-muted">Belongs strictly to selected category</span>`;
}

function populateUnifiedSubSubcategories(subObj) {
    const sel = document.getElementById('unifiedSubSubcatSelect');
    const help = document.getElementById('unifiedSubSubcatHelpText');
    const btnCancel = document.getElementById('btnCancelUnifiedSubSubcat');
    if (!sel) return;

    // STRICT ISOLATION: ONLY sub-subcategories belonging strictly to subObj!
    // Never show sub-subcategories of other categories or other subcategories!
    const subSubs = (subObj && Array.isArray(subObj.sub_subcategories)) ? subObj.sub_subcategories : [];

    let opts = '<option value="">-- None (Optional) --</option>';
    opts += '<option value="__NEW__" class="fw-bold text-success">+ Add New Sub-Sub Category</option>';
    subSubs.forEach(ss => {
        opts += `<option value="${ss.id}" data-name="${esc(ss.name)}">${esc(ss.name)}</option>`;
    });
    sel.innerHTML = opts;

    if (subSubs.length > 0) {
        cancelUnifiedNewSubSubcat();
        if (help) help.textContent = `Existing sub-subcategories under "${subObj.name}"`;
        if (btnCancel) btnCancel.style.display = '';
    } else {
        cancelUnifiedNewSubSubcat();
        if (help) {
            help.textContent = subObj ? `No sub-subcategories under "${subObj.name}". Add one if needed.` : 'Add a sub-subcategory if needed.';
        }
        if (btnCancel) btnCancel.style.display = '';
    }
}

function onUnifiedSubSubcatSelectChange() {
    const sel = document.getElementById('unifiedSubSubcatSelect');
    const val = sel ? sel.value : '';
    if (val === '__NEW__') {
        toggleUnifiedNewSubSubcat(true);
        return;
    }
    cancelUnifiedNewSubSubcat();
}

function onUnifiedSubSubcatNameInput() {
    const input = document.getElementById('unifiedSubSubcatName');
    const help = document.getElementById('unifiedSubSubcatHelpText');
    const subcategory = findUnifiedSelectedSubcategory();
    const name = (input?.value || '').trim();
    if (!help) return;
    const duplicate = name && (subcategory?.sub_subcategories || []).find(subSubcategory =>
        (subSubcategory.name || '').trim().toLowerCase() === name.toLowerCase() ||
        normalizeUnifiedHierarchyName(subSubcategory.name) === normalizeUnifiedHierarchyName(name)
    );
    help.innerHTML = duplicate
        ? `<span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Sub-subcategory "${esc(duplicate.name)}" already exists. Select it from the list.</span>`
        : `<span class="text-muted">Belongs strictly to selected subcategory</span>`;
}

function toggleUnifiedNewSubSubcat(forceNew = true) {
    const selWrap = document.getElementById('unifiedSubSubcatSelectWrap');
    const inpWrap = document.getElementById('unifiedSubSubcatInputWrap');
    const inp = document.getElementById('unifiedSubSubcatName');
    const btn = document.getElementById('btnToggleNewUnifiedSubSubcat');

    if (selWrap) selWrap.style.display = 'none';
    if (inpWrap) inpWrap.style.display = 'block';
    if (btn) btn.style.display = 'none';
    if (inp) {
        inp.value = '';
        setTimeout(() => inp.focus(), 100);
    }
}

function cancelUnifiedNewSubSubcat() {
    const selWrap = document.getElementById('unifiedSubSubcatSelectWrap');
    const inpWrap = document.getElementById('unifiedSubSubcatInputWrap');
    const inp = document.getElementById('unifiedSubSubcatName');
    const btn = document.getElementById('btnToggleNewUnifiedSubSubcat');
    const sel = document.getElementById('unifiedSubSubcatSelect');

    if (selWrap) selWrap.style.display = 'block';
    if (inpWrap) inpWrap.style.display = 'none';
    if (btn) btn.style.display = '';
    if (inp) inp.value = '';
    if (sel && sel.value === '__NEW__') sel.value = '';
}

function quickAddUnifiedAttr(name, type, opts) {
    const tb = document.getElementById('unifiedAttributesTableBody');
    const ex = Array.from(tb.querySelectorAll('.ua-name')).find(i => i.value.trim().toLowerCase() === name.toLowerCase());
    if (ex) { ex.focus(); ex.classList.add('is-valid'); setTimeout(() => ex.classList.remove('is-valid'), 1000); return; }
    addUnifiedAttributeRow({ attr_name: name, attr_type: type, attr_options: opts });
}

function addUnifiedAttributeRow(attr = null) {
    const tb = document.getElementById('unifiedAttributesTableBody');
    const a = attr || { attr_name: '', attr_options: '' };
    const tr = document.createElement('tr'); tr.className = 'ua-row';
    tr.innerHTML = `
        <td><input type="text" class="form-control form-control-sm ua-name fw-semibold text-uppercase" value="${esc(a.attr_name)}" placeholder="e.g. Size, Fabric, Collar" oninput="this.value=this.value.toUpperCase()" required></td>
        <td><input type="text" class="form-control form-control-sm ua-opts text-uppercase" value="${esc(a.attr_options || '')}" placeholder="e.g. S, M, L, XL or Red, Blue" oninput="this.value=this.value.toUpperCase()"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tb.appendChild(tr);
}

function submitUnifiedCategoryForm(e) {
    e.preventDefault();

    const catInputWrap = document.getElementById('unifiedCatInputWrap');
    const catInput = document.getElementById('unifiedCatName');
    const catSelect = document.getElementById('unifiedCatSelect');
    let cat = '';

    if (catInputWrap && catInputWrap.style.display !== 'none' && catInput && catInput.value.trim()) {
        cat = catInput.value.trim();
    } else if (catSelect && catSelect.value && catSelect.value !== '__NEW__') {
        cat = catSelect.options[catSelect.selectedIndex]?.getAttribute('data-name') || catSelect.options[catSelect.selectedIndex]?.text || '';
    } else if (catInput && catInput.value.trim()) {
        cat = catInput.value.trim();
    }

    if (!cat) {
        alert('Main Category Name is required.');
        if (catInputWrap && catInputWrap.style.display !== 'none' && catInput) {
            catInput.focus();
        } else if (catSelect) {
            catSelect.focus();
        }
        return;
    }
    const selectedCategory = catInputWrap && catInputWrap.style.display !== 'none'
        ? (categoryTreeData || []).find(category =>
            (category.name || '').trim().toLowerCase() === cat.toLowerCase() ||
            normalizeUnifiedHierarchyName(category.name) === normalizeUnifiedHierarchyName(cat)
        )
        : (categoryTreeData || []).find(category => String(category.id) === String(catSelect?.value || ''));
    if (catInputWrap && catInputWrap.style.display !== 'none' && selectedCategory) {
        onUnifiedCatNameInput();
        catInput?.focus();
        return;
    }

    const subcatInputWrap = document.getElementById('unifiedSubcatInputWrap');
    const subcatInput = document.getElementById('unifiedSubcatName');
    const subcatSelect = document.getElementById('unifiedSubcatSelect');
    let subcat = '';
    if (subcatInputWrap && subcatInputWrap.style.display !== 'none' && subcatInput && subcatInput.value.trim()) {
        subcat = subcatInput.value.trim();
    } else if (subcatSelect && subcatSelect.value && subcatSelect.value !== '__NEW__') {
        subcat = subcatSelect.options[subcatSelect.selectedIndex]?.getAttribute('data-name') || subcatSelect.options[subcatSelect.selectedIndex]?.text || '';
    } else if (subcatInput && subcatInput.value.trim()) {
        subcat = subcatInput.value.trim();
    }
    const selectedSubcategory = subcatInputWrap && subcatInputWrap.style.display !== 'none'
        ? (selectedCategory?.subcategories || []).find(subcategory =>
            (subcategory.name || '').trim().toLowerCase() === subcat.toLowerCase() ||
            normalizeUnifiedHierarchyName(subcategory.name) === normalizeUnifiedHierarchyName(subcat)
        )
        : (selectedCategory?.subcategories || []).find(subcategory => String(subcategory.id) === String(subcatSelect?.value || ''));
    if (subcatInputWrap && subcatInputWrap.style.display !== 'none' && subcat && selectedSubcategory) {
        onUnifiedSubcatNameInput();
        subcatInput?.focus();
        return;
    }

    const subSubcatInputWrap = document.getElementById('unifiedSubSubcatInputWrap');
    const subSubcatInput = document.getElementById('unifiedSubSubcatName');
    const subSubcatSelect = document.getElementById('unifiedSubSubcatSelect');
    let subSubcat = '';
    if (subSubcatInputWrap && subSubcatInputWrap.style.display !== 'none' && subSubcatInput && subSubcatInput.value.trim()) {
        subSubcat = subSubcatInput.value.trim();
    } else if (subSubcatSelect && subSubcatSelect.value && subSubcatSelect.value !== '__NEW__') {
        subSubcat = subSubcatSelect.options[subSubcatSelect.selectedIndex]?.getAttribute('data-name') || subSubcatSelect.options[subSubcatSelect.selectedIndex]?.text || '';
    } else if (subSubcatInput && subSubcatInput.value.trim()) {
        subSubcat = subSubcatInput.value.trim();
    }
    const selectedSubSubcategory = subSubcatInputWrap && subSubcatInputWrap.style.display !== 'none'
        ? (selectedSubcategory?.sub_subcategories || []).find(subSubcategory =>
            (subSubcategory.name || '').trim().toLowerCase() === subSubcat.toLowerCase() ||
            normalizeUnifiedHierarchyName(subSubcategory.name) === normalizeUnifiedHierarchyName(subSubcat)
        )
        : (selectedSubcategory?.sub_subcategories || []).find(subSubcategory => String(subSubcategory.id) === String(subSubcatSelect?.value || ''));
    if (subSubcatInputWrap && subSubcatInputWrap.style.display !== 'none' && subSubcat && selectedSubSubcategory) {
        onUnifiedSubSubcatNameInput();
        subSubcatInput?.focus();
        return;
    }

    const attrs = [];
    document.querySelectorAll('#unifiedAttributesTableBody tr.ua-row').forEach((r, i) => {
        const n = r.querySelector('.ua-name').value.trim(); if (!n) return;
        attrs.push({
            attr_name: n,
            attr_type: 'SELECT',
            attr_options: r.querySelector('.ua-opts').value.trim(),
            sort_order: i + 1
        });
    });

    const btn = document.getElementById('btnSubmitUnifiedCat');
    const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    const p = new URLSearchParams();
    p.append('_csrf_token', CSRF_TOKEN);
    p.append('category_name', cat);
    p.append('category_type', 'NORMAL');
    if (selectedCategory) p.append('category_id', selectedCategory.id);
    p.append('subcategory_name', subcat);
    if (selectedSubcategory && !(subcatInputWrap && subcatInputWrap.style.display !== 'none')) {
        p.append('subcategory_id', selectedSubcategory.id);
    }
    p.append('sub_subcategory_name', subSubcat);
    if (selectedSubSubcategory && !(subSubcatInputWrap && subSubcatInputWrap.style.display !== 'none')) {
        p.append('sub_subcategory_id', selectedSubSubcategory.id);
    }
    p.append('attributes', JSON.stringify(attrs));

    fetch(`${APP_URL}/api/warehouse/catalog/create-full-hierarchy`, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: p.toString() })
        .then(r => r.json()).then(data => {
            btn.disabled = false; btn.innerHTML = orig;
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalUnifiedCategory')).hide();
                showToast('Success', `Hierarchy "${data.category_name}" saved!`, 'success');
                reloadCategoryTree(data.category_id, data.subcategory_id);
            } else {
                alert(data.message || 'Error saving');
            }
        }).catch(err => { btn.disabled = false; btn.innerHTML = orig; alert('Error: ' + err.message); });
}

// =========================================================================
// FULL FORM EDIT CATEGORY HIERARCHY & PRE-SET ATTRIBUTES
// =========================================================================
function openFullEditCategoryModal(e, catId, targetSubcatId = null, targetSubSubcatId = null) {
    if (e) e.stopPropagation();
    const cat = categoryTreeData.find(c => c.id === catId);
    if (!cat) {
        showToast('Notice', 'Category details not found in view.', 'warning');
        return;
    }

    document.getElementById('editFullCatId').value = cat.id;
    document.getElementById('editFullCatName').value = cat.name || '';

    // Populate subcategories for THIS category
    populateEditSubcategories(cat, targetSubcatId, targetSubSubcatId);

    // Populate Attributes
    const attrsTbody = document.getElementById('editAttributesTableBody');
    attrsTbody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-warning me-1"></div>Loading attributes...</td></tr>';

    fetch(`${APP_URL}/api/warehouse/catalog/attributes/${cat.id}`)
        .then(r => r.json())
        .then(data => {
            attrsTbody.innerHTML = '';
            const attrs = (data.success && data.attributes && data.attributes.length > 0) ? data.attributes : (cat.attributes || []);
            if (attrs.length === 0) {
                addEditAttributeRow();
            } else {
                attrs.forEach(a => addEditAttributeRow(a));
            }
            updateEditAttrBadge();
        })
        .catch(err => {
            attrsTbody.innerHTML = '';
            const attrs = cat.attributes || [];
            if (attrs.length === 0) {
                addEditAttributeRow();
            } else {
                attrs.forEach(a => addEditAttributeRow(a));
            }
            updateEditAttrBadge();
        });

    new bootstrap.Modal(document.getElementById('modalUnifiedEditCategory')).show();
}

function addEditAttributeRow(attr = null) {
    const tb = document.getElementById('editAttributesTableBody');
    const a = attr || { attr_name: '', attr_options: '' };
    const tr = document.createElement('tr');
    tr.className = 'edit-attr-row';
    tr.innerHTML = `
        <td><input type="text" class="form-control form-control-sm ea-name fw-semibold text-uppercase" value="${esc(a.attr_name || '')}" placeholder="Attribute Name (e.g. Size, Fabric, Fit)" oninput="this.value=this.value.toUpperCase()" required></td>
        <td><input type="text" class="form-control form-control-sm ea-opts text-uppercase" value="${esc(a.attr_options || '')}" placeholder="Options comma-separated (e.g. S, M, L, XL or Red, Blue)" oninput="this.value=this.value.toUpperCase()"></td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.closest('tr').remove();updateEditAttrBadge();">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tb.appendChild(tr);
    updateEditAttrBadge();
}

function quickAddEditAttr(name, type, opts) {
    const tb = document.getElementById('editAttributesTableBody');
    const ex = Array.from(tb.querySelectorAll('.ea-name')).find(i => i.value.trim().toLowerCase() === name.toLowerCase());
    if (ex) {
        ex.focus();
        ex.classList.add('is-valid');
        setTimeout(() => ex.classList.remove('is-valid'), 1000);
        return;
    }
    addEditAttributeRow({ attr_name: name, attr_type: type, attr_options: opts });
}

function updateEditAttrBadge() {
    const count = document.querySelectorAll('#editAttributesTableBody tr.edit-attr-row').length;
    const badge = document.getElementById('editFullAttrCountBadge');
    if (badge) badge.textContent = `${count} Attributes`;
}

function populateEditSubcategories(cat, targetSubcatId = null, targetSubSubcatId = null) {
    const sel = document.getElementById('editFullSubcatSelect');
    if (!sel) return;

    const subcats = (cat && Array.isArray(cat.subcategories)) ? cat.subcategories : [];
    let opts = '<option value="">-- Select Existing Sub Category --</option>';
    opts += '<option value="__NEW__" class="fw-bold text-primary">+ Add New Sub Category</option>';
    subcats.forEach(s => {
        opts += `<option value="${s.id}" data-name="${esc(s.name)}">${esc(s.name)}</option>`;
    });
    sel.innerHTML = opts;

    if (subcats.length > 0) {
        let chosenSub = null;
        if (targetSubcatId) {
            chosenSub = subcats.find(s => String(s.id) === String(targetSubcatId));
        }
        if (!chosenSub) chosenSub = subcats[0];

        sel.value = chosenSub.id;
        document.getElementById('editFullSubcatId').value = chosenSub.id;
        cancelEditNewSubcat();
        populateEditSubSubcategories(cat, chosenSub, targetSubSubcatId);
    } else {
        document.getElementById('editFullSubcatId').value = '';
        toggleEditNewSubcat(true);
        populateEditSubSubcategories(cat, null, null);
    }
}

function onEditSubcatSelectChange() {
    const catId = parseInt(document.getElementById('editFullCatId').value);
    const cat = categoryTreeData.find(c => c.id === catId);
    const sel = document.getElementById('editFullSubcatSelect');
    const val = sel ? sel.value : '';

    if (val === '__NEW__') {
        toggleEditNewSubcat(true);
        populateEditSubSubcategories(cat, null);
        return;
    }

    cancelEditNewSubcat();
    document.getElementById('editFullSubcatId').value = val || '';

    const subObj = cat ? (cat.subcategories || []).find(s => String(s.id) === String(val)) : null;
    populateEditSubSubcategories(cat, subObj);
}

function toggleEditNewSubcat(forceNew = true) {
    const selWrap = document.getElementById('editFullSubcatSelectWrap');
    const inpWrap = document.getElementById('editFullSubcatInputWrap');
    const inp = document.getElementById('editFullSubcatName');
    const actBtns = document.getElementById('editSubcatActionBtns');
    const hidId = document.getElementById('editFullSubcatId');
    const help = document.getElementById('editSubcatHelpText');

    if (selWrap) selWrap.style.display = 'none';
    if (inpWrap) inpWrap.style.display = 'block';
    if (actBtns) actBtns.style.display = 'none';
    if (hidId) hidId.value = '';
    if (inp) {
        inp.value = '';
        setTimeout(() => inp.focus(), 100);
    }
    if (help) help.innerHTML = '<span class="text-primary fw-semibold"><i class="bi bi-plus-circle me-1"></i>Adding new subcategory under this category</span>';

    const catId = parseInt(document.getElementById('editFullCatId').value);
    const cat = categoryTreeData.find(c => c.id === catId);
    populateEditSubSubcategories(cat, null);
}

function toggleEditRenameSubcat() {
    const sel = document.getElementById('editFullSubcatSelect');
    if (!sel || !sel.value || sel.value === '__NEW__') {
        alert('Please select an existing subcategory first to rename.');
        return;
    }
    const selId = sel.value;
    const selName = sel.options[sel.selectedIndex]?.getAttribute('data-name') || sel.options[sel.selectedIndex]?.text || '';

    const selWrap = document.getElementById('editFullSubcatSelectWrap');
    const inpWrap = document.getElementById('editFullSubcatInputWrap');
    const inp = document.getElementById('editFullSubcatName');
    const actBtns = document.getElementById('editSubcatActionBtns');
    const hidId = document.getElementById('editFullSubcatId');
    const help = document.getElementById('editSubcatHelpText');

    if (selWrap) selWrap.style.display = 'none';
    if (inpWrap) inpWrap.style.display = 'block';
    if (actBtns) actBtns.style.display = 'none';
    if (hidId) hidId.value = selId;
    if (inp) {
        inp.value = selName;
        setTimeout(() => inp.focus(), 100);
    }
    if (help) help.innerHTML = `<span class="text-secondary fw-semibold"><i class="bi bi-pencil me-1"></i>Renaming subcategory "${esc(selName)}"</span>`;
}

function cancelEditNewSubcat() {
    const selWrap = document.getElementById('editFullSubcatSelectWrap');
    const inpWrap = document.getElementById('editFullSubcatInputWrap');
    const inp = document.getElementById('editFullSubcatName');
    const actBtns = document.getElementById('editSubcatActionBtns');
    const sel = document.getElementById('editFullSubcatSelect');
    const help = document.getElementById('editSubcatHelpText');

    if (selWrap) selWrap.style.display = 'block';
    if (inpWrap) inpWrap.style.display = 'none';
    if (actBtns) actBtns.style.display = 'flex';
    if (inp) inp.value = '';
    if (help) help.textContent = 'Pick from dropdown or click + Add New';

    if (sel && sel.value === '__NEW__') {
        sel.selectedIndex = sel.options.length > 2 ? 2 : 0;
        document.getElementById('editFullSubcatId').value = sel.value;
    } else if (sel) {
        document.getElementById('editFullSubcatId').value = sel.value;
    }
}

function populateEditSubSubcategories(cat, subObj, targetSubSubcatId = null) {
    const sel = document.getElementById('editFullSubSubcatSelect');
    if (!sel) return;

    const subSubs = (subObj && Array.isArray(subObj.sub_subcategories)) ? subObj.sub_subcategories : [];
    let opts = '<option value="">-- Select Existing Sub-Sub Category --</option>';
    opts += '<option value="__NEW__" class="fw-bold text-success">+ Add New Sub-Sub Category</option>';
    subSubs.forEach(ss => {
        opts += `<option value="${ss.id}" data-name="${esc(ss.name)}">${esc(ss.name)}</option>`;
    });
    sel.innerHTML = opts;

    if (subSubs.length > 0) {
        let chosenSubSub = null;
        if (targetSubSubcatId) {
            chosenSubSub = subSubs.find(ss => String(ss.id) === String(targetSubSubcatId));
        }
        if (!chosenSubSub) chosenSubSub = subSubs[0];

        sel.value = chosenSubSub.id;
        document.getElementById('editFullSubSubcatId').value = chosenSubSub.id;
        cancelEditNewSubSubcat();
    } else {
        document.getElementById('editFullSubSubcatId').value = '';
        toggleEditNewSubSubcat(true);
    }
}

function onEditSubSubcatSelectChange() {
    const sel = document.getElementById('editFullSubSubcatSelect');
    const val = sel ? sel.value : '';

    if (val === '__NEW__') {
        toggleEditNewSubSubcat(true);
        return;
    }

    cancelEditNewSubSubcat();
    document.getElementById('editFullSubSubcatId').value = val || '';
}

function toggleEditNewSubSubcat(forceNew = true) {
    const selWrap = document.getElementById('editFullSubSubcatSelectWrap');
    const inpWrap = document.getElementById('editFullSubSubcatInputWrap');
    const inp = document.getElementById('editFullSubSubcatName');
    const actBtns = document.getElementById('editSubSubcatActionBtns');
    const hidId = document.getElementById('editFullSubSubcatId');
    const help = document.getElementById('editSubSubcatHelpText');

    if (selWrap) selWrap.style.display = 'none';
    if (inpWrap) inpWrap.style.display = 'block';
    if (actBtns) actBtns.style.display = 'none';
    if (hidId) hidId.value = '';
    if (inp) {
        inp.value = '';
        setTimeout(() => inp.focus(), 100);
    }
    if (help) help.innerHTML = '<span class="text-success fw-semibold"><i class="bi bi-plus-circle me-1"></i>Adding new sub-subcategory under selected subcategory</span>';
}

function toggleEditRenameSubSubcat() {
    const sel = document.getElementById('editFullSubSubcatSelect');
    if (!sel || !sel.value || sel.value === '__NEW__') {
        alert('Please select an existing sub-subcategory first to rename.');
        return;
    }
    const selId = sel.value;
    const selName = sel.options[sel.selectedIndex]?.getAttribute('data-name') || sel.options[sel.selectedIndex]?.text || '';

    const selWrap = document.getElementById('editFullSubSubcatSelectWrap');
    const inpWrap = document.getElementById('editFullSubSubcatInputWrap');
    const inp = document.getElementById('editFullSubSubcatName');
    const actBtns = document.getElementById('editSubSubcatActionBtns');
    const hidId = document.getElementById('editFullSubSubcatId');
    const help = document.getElementById('editSubSubcatHelpText');

    if (selWrap) selWrap.style.display = 'none';
    if (inpWrap) inpWrap.style.display = 'block';
    if (actBtns) actBtns.style.display = 'none';
    if (hidId) hidId.value = selId;
    if (inp) {
        inp.value = selName;
        setTimeout(() => inp.focus(), 100);
    }
    if (help) help.innerHTML = `<span class="text-secondary fw-semibold"><i class="bi bi-pencil me-1"></i>Renaming sub-subcategory "${esc(selName)}"</span>`;
}

function cancelEditNewSubSubcat() {
    const selWrap = document.getElementById('editFullSubSubcatSelectWrap');
    const inpWrap = document.getElementById('editFullSubSubcatInputWrap');
    const inp = document.getElementById('editFullSubSubcatName');
    const actBtns = document.getElementById('editSubSubcatActionBtns');
    const sel = document.getElementById('editFullSubSubcatSelect');
    const help = document.getElementById('editSubSubcatHelpText');

    if (selWrap) selWrap.style.display = 'block';
    if (inpWrap) inpWrap.style.display = 'none';
    if (actBtns) actBtns.style.display = 'flex';
    if (inp) inp.value = '';
    if (help) help.textContent = 'Pick from dropdown or click + Add New';

    if (sel && sel.value === '__NEW__') {
        sel.selectedIndex = sel.options.length > 2 ? 2 : 0;
        document.getElementById('editFullSubSubcatId').value = sel.value;
    } else if (sel) {
        document.getElementById('editFullSubSubcatId').value = sel.value;
    }
}

function submitUnifiedEditCategoryForm(e) {
    e.preventDefault();
    const catId = parseInt(document.getElementById('editFullCatId').value);
    const catName = document.getElementById('editFullCatName').value.trim();
    if (!catName) {
        alert('Category Name is required.');
        return;
    }

    const subcatInputWrap = document.getElementById('editFullSubcatInputWrap');
    const subcatInput = document.getElementById('editFullSubcatName');
    const subcatSelect = document.getElementById('editFullSubcatSelect');
    let subcatId = document.getElementById('editFullSubcatId').value || '';
    let subcatName = '';

    if (subcatInputWrap && subcatInputWrap.style.display !== 'none' && subcatInput && subcatInput.value.trim()) {
        subcatName = subcatInput.value.trim();
    } else if (subcatSelect && subcatSelect.value && subcatSelect.value !== '__NEW__') {
        subcatId = subcatSelect.value;
        subcatName = subcatSelect.options[subcatSelect.selectedIndex]?.getAttribute('data-name') || subcatSelect.options[subcatSelect.selectedIndex]?.text || '';
    } else if (subcatInput && subcatInput.value.trim()) {
        subcatName = subcatInput.value.trim();
    }

    const subSubcatInputWrap = document.getElementById('editFullSubSubcatInputWrap');
    const subSubcatInput = document.getElementById('editFullSubSubcatName');
    const subSubcatSelect = document.getElementById('editFullSubSubcatSelect');
    let subSubcatId = document.getElementById('editFullSubSubcatId').value || '';
    let subSubcatName = '';

    if (subSubcatInputWrap && subSubcatInputWrap.style.display !== 'none' && subSubcatInput && subSubcatInput.value.trim()) {
        subSubcatName = subSubcatInput.value.trim();
    } else if (subSubcatSelect && subSubcatSelect.value && subSubcatSelect.value !== '__NEW__') {
        subSubcatId = subSubcatSelect.value;
        subSubcatName = subSubcatSelect.options[subSubcatSelect.selectedIndex]?.getAttribute('data-name') || subSubcatSelect.options[subSubcatSelect.selectedIndex]?.text || '';
    } else if (subSubcatInput && subSubcatInput.value.trim()) {
        subSubcatName = subSubcatInput.value.trim();
    }

    // Collect attributes
    const attributes = [];
    document.querySelectorAll('#editAttributesTableBody tr.edit-attr-row').forEach((row, idx) => {
        const name = row.querySelector('.ea-name').value.trim();
        if (!name) return;
        attributes.push({
            attr_name: name,
            attr_type: 'SELECT',
            attr_options: row.querySelector('.ea-opts').value.trim(),
            sort_order: idx + 1
        });
    });

    const btn = document.getElementById('btnSubmitFullEditCat');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    const p = new URLSearchParams();
    p.append('_csrf_token', CSRF_TOKEN);
    p.append('category_id', catId);
    p.append('category_name', catName);
    p.append('category_type', 'NORMAL');
    p.append('subcategory_id', subcatId);
    p.append('subcategory_name', subcatName);
    p.append('sub_subcategory_id', subSubcatId);
    p.append('sub_subcategory_name', subSubcatName);
    p.append('attributes', JSON.stringify(attributes));

    fetch(`${APP_URL}/api/warehouse/catalog/update-full-hierarchy`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: p.toString()
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = orig;
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalUnifiedEditCategory')).hide();
            showToast('Success', `Category "${catName}" and hierarchy updated!`, 'success');
            reloadCategoryTree(catId);
        } else {
            alert(data.message || 'Error updating category');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = orig;
        alert('Error: ' + err.message);
    });
}

function submitCategoryForm(e) {
    e.preventDefault();
    const id = document.getElementById('modalCatId').value, name = document.getElementById('modalCatName').value.trim();
    if (!name) return;
    const type = document.getElementById('modalCatType') ? document.getElementById('modalCatType').value : 'NORMAL';
    const url = id ? `${APP_URL}/api/warehouse-categories/update/${id}` : `${APP_URL}/api/warehouse-categories/quick-add`;
    const p = new URLSearchParams();
    p.append('_csrf_token', CSRF_TOKEN);
    p.append('name', name);
    p.append('category_type', type);
    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: p.toString() })
        .then(r => r.json()).then(data => {
            if (data.success) { bootstrap.Modal.getInstance(document.getElementById('modalCategory')).hide(); showToast('Success', id ? 'Category updated.' : 'Category added.', 'success'); reloadCategoryTree(id || data.id); }
            else alert(data.message || 'Error');
        });
}

async function deleteCategory(e, id, name) {
    if (e) e.stopPropagation();
    if (!confirm(`Delete category "${name}" and all subcategories?`)) return;
    const p = new URLSearchParams();
    p.append('_csrf_token', CSRF_TOKEN);
    try {
        const res = await fetch(`${APP_URL}/api/warehouse-categories/delete/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: p.toString()
        });
        const raw = await res.text();
        let data = {};
        try { data = JSON.parse(raw); } catch (e) { data = { success: false, message: 'Server returned an invalid response.' }; }
        if (!res.ok || !data.success) {
            const errorMsg = data.message || `Cannot delete category "${name}" because it is already assigned to existing products.`;
            showCatalogActionAlert('Cannot Delete Category', errorMsg, 'Category Already Assigned to Products');
            return;
        }
        showToast('Deleted', data.message || `Category "${name}" removed.`, 'info');
        reloadCategoryTree();
    } catch (err) {
        showCatalogActionAlert('Delete Error', err.message || 'An unexpected error occurred while deleting the category.', 'Cannot Delete Category');
    }
}

function openAddSubcategoryModal(e, pid, pname) {
    if (e) e.stopPropagation();
    document.getElementById('modalSubcatParentId').value = pid;
    document.getElementById('modalSubcatParentName').value = pname;
    document.getElementById('modalSubcatId').value = '';
    document.getElementById('modalSubcatName').value = '';
    document.getElementById('modalSubcatTitle').textContent = `Add Subcategory under ${pname}`;
    new bootstrap.Modal(document.getElementById('modalSubcategory')).show();
}

function openEditSubcategoryModal(e, id, name, pid, pname) {
    if (e) e.stopPropagation();
    document.getElementById('modalSubcatParentId').value = pid;
    document.getElementById('modalSubcatParentName').value = pname;
    document.getElementById('modalSubcatId').value = id;
    document.getElementById('modalSubcatName').value = name;
    document.getElementById('modalSubcatTitle').textContent = `Edit Subcategory (${pname})`;
    new bootstrap.Modal(document.getElementById('modalSubcategory')).show();
}

function submitSubcategoryForm(e) {
    e.preventDefault();
    const id = document.getElementById('modalSubcatId').value, pid = document.getElementById('modalSubcatParentId').value, name = document.getElementById('modalSubcatName').value.trim();
    if (!name) return;
    const url = id ? `${APP_URL}/api/warehouse-subcategories/update/${id}` : `${APP_URL}/api/warehouse-subcategories/quick-add`;
    const p = new URLSearchParams(); p.append('_csrf_token', CSRF_TOKEN); p.append('category_id', pid); p.append('name', name);
    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: p.toString() })
        .then(r => r.json()).then(data => {
            if (data.success) { bootstrap.Modal.getInstance(document.getElementById('modalSubcategory')).hide(); showToast('Success', id ? 'Subcategory updated.' : 'Subcategory added.', 'success'); reloadCategoryTree(pid, id || data.id); }
            else alert(data.message || 'Error');
        });
}

async function deleteSubcategory(e, id, name) {
    if (e) e.stopPropagation();
    if (!confirm(`Delete subcategory "${name}"?`)) return;
    const p = new URLSearchParams();
    p.append('_csrf_token', CSRF_TOKEN);
    try {
        const res = await fetch(`${APP_URL}/api/warehouse-subcategories/delete/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: p.toString()
        });
        const raw = await res.text();
        let data = {};
        try { data = JSON.parse(raw); } catch (e) { data = { success: false, message: 'Server returned an invalid response.' }; }
        if (!res.ok || !data.success) {
            const errorMsg = data.message || `Cannot delete subcategory "${name}" because it is already assigned to existing products.`;
            showCatalogActionAlert('Cannot Delete Subcategory', errorMsg, 'Subcategory Already Assigned to Products');
            return;
        }
        showToast('Deleted', data.message || `Subcategory "${name}" removed.`, 'info');
        reloadCategoryTree(selectedCatId);
    } catch (err) {
        showCatalogActionAlert('Delete Error', err.message || 'An unexpected error occurred while deleting the subcategory.', 'Cannot Delete Subcategory');
    }
}

function openAddSubSubcategoryModal(e, pid, pname) {
    if (e) e.stopPropagation();
    document.getElementById('modalSubSubcatParentId').value = pid;
    document.getElementById('modalSubSubcatParentName').value = pname;
    document.getElementById('modalSubSubcatId').value = '';
    document.getElementById('modalSubSubcatName').value = '';
    document.getElementById('modalSubSubcatName').classList.remove('is-invalid');
    document.getElementById('modalSubSubcatTitle').textContent = `Add Sub-Subcategory under ${pname}`;
    new bootstrap.Modal(document.getElementById('modalSubSubcategory')).show();
}

function openEditSubSubcategoryModal(e, id, name, pid, pname) {
    if (e) e.stopPropagation();
    document.getElementById('modalSubSubcatParentId').value = pid;
    document.getElementById('modalSubSubcatParentName').value = pname;
    document.getElementById('modalSubSubcatId').value = id;
    document.getElementById('modalSubSubcatName').value = name;
    document.getElementById('modalSubSubcatName').classList.remove('is-invalid');
    document.getElementById('modalSubSubcatTitle').textContent = `Edit Sub-Subcategory (${pname})`;
    new bootstrap.Modal(document.getElementById('modalSubSubcategory')).show();
}

function validateModalSubSubcategoryName() {
    const nameInput = document.getElementById('modalSubSubcatName');
    const parentId = Number(document.getElementById('modalSubSubcatParentId')?.value || 0);
    const currentId = Number(document.getElementById('modalSubSubcatId')?.value || 0);
    const name = (nameInput?.value || '').trim().toLowerCase();
    const parent = (categoryTreeData || []).flatMap(category => category.subcategories || [])
        .find(subcategory => Number(subcategory.id) === parentId);
    const duplicate = name && (parent?.sub_subcategories || []).find(subcategory =>
        Number(subcategory.id) !== currentId &&
        ((subcategory.name || '').trim().toLowerCase() === name ||
            normalizeUnifiedHierarchyName(subcategory.name) === normalizeUnifiedHierarchyName(name))
    );
    if (nameInput) nameInput.classList.toggle('is-invalid', Boolean(duplicate));
    const feedback = document.getElementById('modalSubSubcatNameFeedback');
    if (feedback && duplicate) {
        feedback.textContent = `Sub-subcategory "${duplicate.name}" already exists under this subcategory.`;
    }
    return !duplicate;
}

function submitSubSubcategoryForm(e) {
    e.preventDefault();
    const id = document.getElementById('modalSubSubcatId').value, pid = document.getElementById('modalSubSubcatParentId').value, name = document.getElementById('modalSubSubcatName').value.trim();
    if (!name) return;
    if (!validateModalSubSubcategoryName()) {
        document.getElementById('modalSubSubcatName').focus();
        return;
    }
    const url = id ? `${APP_URL}/api/warehouse-sub-subcategories/update/${id}` : `${APP_URL}/api/warehouse-sub-subcategories/quick-add`;
    const p = new URLSearchParams(); p.append('_csrf_token', CSRF_TOKEN); p.append('subcategory_id', pid); p.append('name', name);
    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: p.toString() })
        .then(r => r.json()).then(data => {
            if (data.success) { bootstrap.Modal.getInstance(document.getElementById('modalSubSubcategory')).hide(); showToast('Success', id ? 'Sub-Subcategory updated.' : 'Sub-Subcategory added.', 'success'); reloadCategoryTree(selectedCatId, pid, id || data.id); }
            else alert(data.message || 'Error');
        });
}

async function deleteSubSubcategory(e, id, name) {
    if (e) e.stopPropagation();
    if (!confirm(`Delete sub-subcategory "${name}"?`)) return;
    const p = new URLSearchParams();
    p.append('_csrf_token', CSRF_TOKEN);
    try {
        const res = await fetch(`${APP_URL}/api/warehouse-sub-subcategories/delete/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: p.toString()
        });
        const raw = await res.text();
        let data = {};
        try { data = JSON.parse(raw); } catch (e) { data = { success: false, message: 'Server returned an invalid response.' }; }
        if (!res.ok || !data.success) {
            const errorMsg = data.message || `Cannot delete sub-subcategory "${name}" because it is already assigned to existing products.`;
            showCatalogActionAlert('Cannot Delete Sub-Subcategory', errorMsg, 'Sub-Subcategory Already Assigned to Products');
            return;
        }
        showToast('Deleted', data.message || `Sub-Subcategory "${name}" removed.`, 'info');
        reloadCategoryTree(selectedCatId, selectedSubId);
    } catch (err) {
        showCatalogActionAlert('Delete Error', err.message || 'An unexpected error occurred while deleting the sub-subcategory.', 'Cannot Delete Sub-Subcategory');
    }
}

function showToast(title, message, type = 'info') {
    const d = document.createElement('div');
    d.className = `alert alert-${type} alert-dismissible fade show shadow-lg position-fixed bottom-0 end-0 m-4`;
    d.style.cssText = 'z-index:9999;min-width:280px;max-width:450px;';
    d.innerHTML = `<strong>${title}:</strong> ${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(d);
    setTimeout(() => { d.classList.remove('show'); setTimeout(() => d.remove(), 250); }, 3500);
}

function showCatalogActionAlert(title, message, heading = 'Item Already In Use') {
    const modalEl = document.getElementById('modalCatalogActionAlert');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const titleEl = document.getElementById('modalCatalogActionAlertTitle');
        const headingEl = document.getElementById('modalCatalogActionAlertHeading');
        const msgEl = document.getElementById('modalCatalogActionAlertMessage');
        if (titleEl) titleEl.innerHTML = `<i class="bi bi-exclamation-octagon-fill me-2"></i> ${esc(title || 'Cannot Delete')}`;
        if (headingEl) headingEl.textContent = heading;
        if (msgEl) msgEl.textContent = message;
        try {
            const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            bsModal.show();
        } catch (e) {
            alert(`${title}:\n\n${message}`);
        }
    } else {
        alert(`${title}:\n\n${message}`);
    }
    showToast(title, message, 'danger');
}

// =========================================================================
// VIEW CATEGORY DETAILS MODAL LOGIC
// =========================================================================
let viewCategoryModalInstance = null;
let viewCategoryProducts = [];
let viewCategoryProductsLoadToken = 0;
let viewCategoryId = 0;

function populateViewCategoryProductFilters(category) {
    const subcategorySelect = document.getElementById('viewCatSubcatFilterSelect');
    const subSubcategorySelect = document.getElementById('viewCatSubSubcatFilterSelect');
    if (!subcategorySelect || !subSubcategorySelect) return;

    subcategorySelect.innerHTML = '<option value="">All Subcategories</option>';
    (category.subcategories || []).forEach(subcategory => {
        subcategorySelect.insertAdjacentHTML('beforeend', `<option value="${Number(subcategory.id)}">${esc(subcategory.name)}</option>`);
    });
    subSubcategorySelect.innerHTML = '<option value="">All Sub-subcategories</option>';
    subSubcategorySelect.disabled = true;
    const searchInput = document.getElementById('viewCatProductSearchInput');
    if (searchInput) searchInput.value = '';
}

function populateViewSubSubcategoryFilter() {
    const category = categoryTreeData.find(item => Number(item.id) === viewCategoryId);
    const subcategoryId = document.getElementById('viewCatSubcatFilterSelect')?.value || '';
    const subcategorySelect = document.getElementById('viewCatSubcatFilterSelect');
    const subSubcategorySelect = document.getElementById('viewCatSubSubcatFilterSelect');
    if (!subcategorySelect || !subSubcategorySelect) return;

    const subcategory = (category?.subcategories || []).find(item => String(item.id) === String(subcategoryId));
    subSubcategorySelect.innerHTML = '<option value="">All Sub-subcategories</option>';
    (subcategory?.sub_subcategories || []).forEach(subSubcategory => {
        subSubcategorySelect.insertAdjacentHTML('beforeend', `<option value="${Number(subSubcategory.id)}">${esc(subSubcategory.name)}</option>`);
    });
    subSubcategorySelect.disabled = !subcategory || !(subcategory.sub_subcategories || []).length;
}

async function loadViewCategoryProducts(category) {
    const tbody = document.getElementById('viewCategoryProductsTableBody');
    if (!tbody) return;
    const loadToken = ++viewCategoryProductsLoadToken;
    viewCategoryProducts = [];
    const count = document.getElementById('viewCatProductFilteredCount');
    if (count) count.textContent = '0 products';
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm text-primary me-2"></span>Loading products in this category...</td></tr>';

    try {
        const query = new URLSearchParams({ category_id: String(category.id) });
        const response = await fetch(`${APP_URL}/api/warehouse/catalog/products-by-category?${query.toString()}`, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (!response.ok || !data.success || !Array.isArray(data.products)) {
            throw new Error(data.message || 'Unable to load products for this category.');
        }
        if (loadToken !== viewCategoryProductsLoadToken) return;
        viewCategoryProducts = data.products;
        filterViewCategoryProducts();
    } catch (error) {
        if (loadToken !== viewCategoryProductsLoadToken) return;
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-danger small"><i class="bi bi-exclamation-triangle me-2"></i>${esc(error.message || 'Unable to load category products.')}</td></tr>`;
    }
}

function filterViewCategoryProducts() {
    const tbody = document.getElementById('viewCategoryProductsTableBody');
    if (!tbody) return;
    const category = categoryTreeData.find(item => Number(item.id) === viewCategoryId);
    const subcategoryId = document.getElementById('viewCatSubcatFilterSelect')?.value || '';
    const subSubcategoryId = document.getElementById('viewCatSubSubcatFilterSelect')?.value || '';
    const search = (document.getElementById('viewCatProductSearchInput')?.value || '').trim().toLowerCase();
    const selectedSubcategory = (category?.subcategories || []).find(item => String(item.id) === String(subcategoryId));
    const selectedSubSubcategory = (selectedSubcategory?.sub_subcategories || []).find(item => String(item.id) === String(subSubcategoryId));
    const matchesBranch = (product, id, legacyId, name) => {
        const productId = product[id] || product[legacyId];
        return String(productId || '') === String(name.id) || String(product[`${name.field}_name`] || '').trim().toLowerCase() === String(name.name || '').trim().toLowerCase();
    };

    const filtered = viewCategoryProducts.filter(product => {
        if (selectedSubcategory && !matchesBranch(product, 'warehouse_subcategory_id', 'subcategory_id', {
            id: selectedSubcategory.id,
            field: 'subcategory',
            name: selectedSubcategory.name
        })) return false;
        if (selectedSubSubcategory && !matchesBranch(product, 'warehouse_sub_subcategory_id', 'sub_subcategory_id', {
            id: selectedSubSubcategory.id,
            field: 'sub_subcategory',
            name: selectedSubSubcategory.name
        })) return false;
        if (!search) return true;
        const searchable = [
            product.name, product.product_name, product.product_code, product.code,
            product.primary_sku, product.category_name, product.subcategory_name,
            product.sub_subcategory_name, product.brand_name
        ].join(' ').toLowerCase();
        return searchable.includes(search);
    });
    const count = document.getElementById('viewCatProductFilteredCount');
    if (count) count.textContent = `${filtered.length} products`;
    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-box-seam fs-3 d-block mb-2 opacity-50"></i>${viewCategoryProducts.length ? 'No products match these filters.' : 'No active products are assigned to this category.'}</td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map((product, index) => {
        const productName = product.name || product.product_name || 'Unnamed product';
        const productCode = product.product_code || product.code || product.primary_sku || '—';
        const path = [product.category_name || category?.name, product.subcategory_name, product.sub_subcategory_name].filter(Boolean).join(' › ');
        const variants = Number(product.variants_count || 0);
        const stock = Number(product.live_stock || 0);
        const mrp = Number(product.max_mrp || 0);
        const sellingPrice = Number(product.max_selling_price || 0);
        return `<tr>
            <td class="ps-3 text-muted small">${index + 1}</td>
            <td><span class="fw-semibold text-dark">${esc(productName)}</span>${product.brand_name ? `<div class="small text-muted">${esc(product.brand_name)}</div>` : ''}</td>
            <td class="font-monospace small">${esc(productCode)}</td>
            <td class="small text-muted">${esc(path || '—')}</td>
            <td class="text-center"><span class="badge bg-light text-dark border">${variants}</span></td>
            <td class="text-end pe-3"><div class="fw-semibold">${stock.toLocaleString()} in stock</div><div class="small text-muted">Selling ${sellingPrice.toFixed(2)}${mrp > 0 ? ` · MRP ${mrp.toFixed(2)}` : ''}</div></td>
        </tr>`;
    }).join('');
}

function openViewCategoryHierarchyModal(e, catId) {
    if (e) e.stopPropagation();
    const cat = categoryTreeData.find(c => c.id === catId);
    if (!cat) {
        showToast('Notice', 'Category details not found in current view.', 'warning');
        return;
    }

    // Set Header
    const titleEl = document.getElementById('viewModalCatTitle');
    if (titleEl) titleEl.textContent = cat.name || 'Category Details';
    viewCategoryId = Number(cat.id);
    const detailsTab = document.getElementById('view-cat-details-tab');
    if (detailsTab) new bootstrap.Tab(detailsTab).show();
    populateViewCategoryProductFilters(cat);
    loadViewCategoryProducts(cat);
    const subcats = cat.subcategories || [];
    const catAttrs = cat.attributes || [];

    // Keep attribute rows grouped by hierarchy level.
    const categoryAttrs = [];
    const subcategoryAttrs = [];
    const subSubcategoryAttrs = [];
    catAttrs.forEach(a => {
        categoryAttrs.push({
            ...a,
            scope_name: cat.name || 'Category',
            attr_name: a.attr_name,
            attr_options: a.attr_options || '',
            attr_type: a.attr_type || 'SELECT'
        });
    });
    subcats.forEach(s => {
        (s.attributes || []).forEach(a => {
            subcategoryAttrs.push({
                ...a,
                scope_name: s.name || 'Subcategory',
                attr_name: a.attr_name,
                attr_options: a.attr_options || '',
                attr_type: a.attr_type || 'SELECT'
            });
        });
        (s.sub_subcategories || []).forEach(ss => {
            (ss.attributes || []).forEach(a => {
                subSubcategoryAttrs.push({
                    ...a,
                    scope_name: `${s.name || 'Subcategory'} › ${ss.name || 'Sub-subcategory'}`,
                    attr_name: a.attr_name,
                    attr_options: a.attr_options || '',
                    attr_type: a.attr_type || 'SELECT'
                });
            });
        });
    });
    const allScopedAttrs = [...categoryAttrs, ...subcategoryAttrs, ...subSubcategoryAttrs];

    // Populate Breadcrumb
    const bcEl = document.getElementById('viewModalCatBreadcrumb');
    if (bcEl) {
        const subcategoryNames = subcats.map(sub => sub.name).filter(Boolean);
        bcEl.textContent = `Category: ${cat.name || 'Unnamed'}  ›  Subcategories: ${subcategoryNames.length ? subcategoryNames.join(', ') : 'None'}`;
    }

    // Summary KPIs
    const attrCountBadge = document.getElementById('viewAttrCountBadge'); if (attrCountBadge) attrCountBadge.textContent = `${allScopedAttrs.length} Attributes`;
    const subTreeBadge = document.getElementById('viewSubTreeCountBadge'); if (subTreeBadge) subTreeBadge.textContent = `${subcats.length} Subcategor${subcats.length === 1 ? 'y' : 'ies'}`;
    const totalSubSubcategories = subcats.reduce((count, sub) => count + (sub.sub_subcategories || []).length, 0);
    const subSubTreeBadge = document.getElementById('viewSubSubTreeCountBadge');
    if (subSubTreeBadge) subSubTreeBadge.textContent = `${totalSubSubcategories} Sub-subcategor${totalSubSubcategories === 1 ? 'y' : 'ies'}`;

    // Main Category
    const dName = document.getElementById('viewDetailCatName'); if (dName) dName.textContent = cat.name || '—';

    // Subcategories
    const treeContainer = document.getElementById('viewSubcategoriesTreeContainer');
    if (treeContainer) {
        if (subcats.length === 0) {
            treeContainer.innerHTML = `<div class="text-center py-4 text-muted">
                <i class="bi bi-folder-x fs-2 opacity-40 d-block mb-1"></i>
                <div class="small fw-semibold">No subcategories defined for "${esc(cat.name)}"</div>
            </div>`;
        } else {
            treeContainer.innerHTML = subcats.map((sub, sIdx) => {
                const sAttrs = sub.attributes || [];
                return `
                <div class="p-2.5 rounded-3 border bg-white mb-2 shadow-xs">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-circle bg-info text-white" style="width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:0.72rem;">${sIdx + 1}</span>
                            <span class="fw-bold text-dark" style="font-size:0.9rem;">${esc(sub.name)}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            ${sAttrs.length > 0 ? `<span class="badge bg-warning-subtle text-dark border border-warning" style="font-size:0.72rem;">${sAttrs.length} Attributes</span>` : '<span class="text-muted small" style="font-size:0.72rem;">Inherits category attributes</span>'}
                        </div>
                    </div>
                </div>`;
            }).join('');
        }
    }

    // Sub-subcategories
    const subSubTreeContainer = document.getElementById('viewSubSubcategoriesTreeContainer');
    if (subSubTreeContainer) {
        const subSubcategoryRows = subcats.flatMap(sub =>
            (sub.sub_subcategories || []).map((subSub, index) => ({ sub, subSub, index }))
        );
        if (subSubcategoryRows.length === 0) {
            subSubTreeContainer.innerHTML = `<div class="text-center py-4 text-muted">
                <i class="bi bi-tags fs-2 opacity-40 d-block mb-1"></i>
                <div class="small fw-semibold">No sub-subcategories defined for "${esc(cat.name)}"</div>
            </div>`;
        } else {
            subSubTreeContainer.innerHTML = subSubcategoryRows.map(({ sub, subSub, index }) => `
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-2 rounded-3 border bg-white mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-circle bg-success text-white" style="width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:0.72rem;">${index + 1}</span>
                        <span class="fw-semibold text-dark">${esc(subSub.name)}</span>
                    </div>
                    <span class="small text-muted">Subcategory: <strong class="text-dark">${esc(sub.name)}</strong></span>
                </div>
            `).join('');
        }
    }

    // Attributes
    const attrsTbody = document.getElementById('viewScopedAttrsTableBody');
    if (attrsTbody) {
        if (allScopedAttrs.length === 0) {
            attrsTbody.innerHTML = `<tr><td colspan="3" class="text-center py-4 text-muted">
                <i class="bi bi-sliders fs-2 opacity-40 d-block mb-1"></i>
                <div class="small fw-semibold">No Pre-set Attributes defined across this category branch</div>
                <div style="font-size:0.75rem;">Attributes configured here apply automatically when inwarding products.</div>
            </td></tr>`;
        } else {
            attrsTbody.innerHTML = allScopedAttrs.map(attr => {
                const opts = (attr.attr_options || '').split(',').map(o => o.trim()).filter(Boolean);
                return `
                <tr>
                    <td>
                        <strong class="text-dark" style="font-size:0.84rem;">${esc(attr.attr_name)}</strong>
                    </td>
                    <td>
                        <span class="badge bg-light text-primary border" style="font-size:0.72rem;">${esc(attr.scope_name)}</span>
                    </td>
                    <td class="pe-3">
                        ${opts.length > 0 ? `
                            <div class="d-flex flex-wrap gap-1">
                                ${opts.slice(0, 5).map(o => `<span class="badge bg-white text-dark border shadow-xs" style="font-size:0.72rem;">${esc(o)}</span>`).join('')}
                                ${opts.length > 5 ? `<span class="badge bg-light text-muted border" style="font-size:0.70rem;">+${opts.length - 5} more</span>` : ''}
                            </div>
                        ` : `<span class="text-muted small">None / Free text</span>`}
                    </td>
                </tr>`;
            }).join('');
        }
    }

    // Show Modal
    const modalEl = document.getElementById('modalViewCategoryHierarchy');
    viewCategoryModalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    viewCategoryModalInstance.show();
}

</script>

<!-- ========================================================================= -->
<!-- STYLES -->
<!-- ========================================================================= -->
<style>
/* Header badges & level icons */
.cat-level-num { width:22px; height:22px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:700; color:#fff; flex-shrink:0; }
.item-icon-badge { width:32px; height:32px; border-radius:8px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-size:.95rem; flex-shrink:0; transition:all .15s; }
.item-icon-badge.active { background:#dbeafe; }
.item-icon-badge.active-info { background:#cffafe; }
.item-icon-badge.active-success { background:#dcfce7; }

/* Cascading column list items (Image 2 style) */
.col-list-item { cursor:pointer; padding:9px 12px; border-bottom:1px solid #f0f3f8; transition:all .12s; border-left:3px solid transparent; background:#fff; }
.col-list-item:hover { background:#f8fafc; border-left-color:#cbd5e1; }
.col-list-item:last-child { border-bottom:none; }
.col-list-item.active-col-item { background:#f0f6ff!important; border-left:3px solid #3b82f6!important; }
.col-list-item.active-col-item .item-title { color:#1d4ed8!important; }
.col-list-item.active-col-item-info { background:#f0faff!important; border-left:3px solid #06b6d4!important; }
.col-list-item.active-col-item-info .item-title { color:#0e7490!important; }
.col-list-item.active-col-item-success { background:#f0fdf6!important; border-left:3px solid #10b981!important; }
.col-list-item.active-col-item-success .item-title { color:#047857!important; }

/* Status & info pills (Image 1 style) */
.pill-active { display:inline-block; padding:2px 8px; border-radius:20px; font-size:.7rem; font-weight:600; color:#16a34a; background:#f0fdf4; border:1px solid #bbf7d0; }
.pill-info { display:inline-block; padding:2px 7px; border-radius:20px; font-size:.68rem; font-weight:600; color:#0369a1; background:#f0f9ff; border:1px solid #bae6fd; }
.pill-attr { display:inline-block; padding:2px 8px; border-radius:20px; font-size:.7rem; font-weight:600; color:#92400e; background:#fffbeb; border:1px solid #fde68a; }
.code-badge { display:inline-block; padding:1px 6px; border-radius:4px; font-size:.7rem; font-family:monospace; color:#64748b; background:#f8fafc; border:1px solid #e2e8f0; }

/* Action icon buttons (Image 1 style: clean, outlined, 28x28) */
.icon-btn { width:28px; height:28px; padding:0; display:inline-flex; align-items:center; justify-content:center; font-size:.76rem; border-radius:6px; border:1px solid #d1d5db; background:#fff; cursor:pointer; transition:all .12s; color:#475569; }
.icon-btn:hover { background:#f1f5f9; border-color:#94a3b8; color:#0f172a; }
.icon-btn.danger { border-color:#fca5a5; color:#dc2626; }
.icon-btn.danger:hover { background:#fee2e2; border-color:#f87171; color:#b91c1c; }

/* Quick Add chips */
.btn-chip { padding:3px 9px; font-size:.72rem; border-radius:20px; border:1px solid #d1d5db; background:#fff; cursor:pointer; transition:all .12s; color:#374151; font-weight:500; }
.btn-chip:hover { background:#f3f4f6; border-color:#9ca3af; }

/* Inline attributes editor */
.attr-row { border-bottom:1px solid #f0f3f8; padding:5px 8px; background:#fff; }
.attr-row:hover { background:#fafbfc; }
.attr-row:last-child { border-bottom:none; }
.attr-row-inner { display:flex; align-items:center; gap:4px; flex-wrap:nowrap; }
.ar-name { flex:2; min-width:70px; }
.ar-type { flex:1.2; min-width:60px; }
.ar-opts { flex:2; min-width:60px; }
.ar-req { flex:0 0 36px; }
.ar-del { flex:0 0 28px; }
.attr-row input.form-control-sm, .attr-row select.form-select-sm { font-size:.73rem; padding:2px 5px; height:26px; }

/* Custom scrollbars */
#categoriesListContainer::-webkit-scrollbar, #subcategoriesListContainer::-webkit-scrollbar, #subSubcategoriesListContainer::-webkit-scrollbar, #attributesListContainer::-webkit-scrollbar { width:4px; }
#categoriesListContainer::-webkit-scrollbar-thumb, #subcategoriesListContainer::-webkit-scrollbar-thumb, #subSubcategoriesListContainer::-webkit-scrollbar-thumb, #attributesListContainer::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:2px; }

/* Table hover & expand styles */
.cat-table-row:hover { background-color:#f8fafc!important; }
.cat-table-row td { vertical-align:middle; }
.view-summary-stat { min-height: 82px; }
.btn-xs { padding: 0.15rem 0.5rem; font-size: 0.72rem; line-height: 1.3; border-radius: 4px; }
</style>
