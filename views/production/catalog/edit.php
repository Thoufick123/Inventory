<?php
$existingSizeIds = array_filter(array_unique(array_column($variants ?? [], 'size_id')));
$existingColorIds = array_filter(array_unique(array_column($variants ?? [], 'color_id')));
?>
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Product Style</h2>
        <span class="text-muted">Modify category hierarchy and product style attributes</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('production/catalog/view/' . $product['id']) ?>" class="btn btn-outline-primary">
            <i class="bi bi-eye me-1"></i> View Style
        </a>
        <a href="<?= url('production/catalog') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Catalog
        </a>
    </div>
</div>

<form method="POST" action="<?= url('production/catalog/update/' . $product['id']) ?>" id="editCatalogForm">
    <?= CSRF::field() ?>

    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <!-- 1. CATEGORY HIERARCHY -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-diagram-3 me-2 text-primary"></i> 1. Category Hierarchy (3-Tier)
                    </h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Required</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Category -->
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0">Main Category <span class="text-danger">*</span></label>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-primary" id="btnEditOpenMainCatModal" data-bs-toggle="modal" data-bs-target="#modalAddMainCategory">
                                        <i class="bi bi-plus-circle"></i> New
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-warning <?= empty($product['category_id']) ? 'd-none' : '' ?>" id="btnEditMainCatModal" title="Edit Selected Main Category">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger <?= empty($product['category_id']) ? 'd-none' : '' ?>" id="btnDeleteMainCat" title="Delete Selected Main Category">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Subcategory -->
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0">Sub Category</label>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-primary" id="btnOpenSubcatModal" <?= empty($product['category_id']) ? 'disabled' : '' ?>>
                                        <i class="bi bi-plus-circle"></i> New
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-warning <?= empty($product['subcategory_id']) ? 'd-none' : '' ?>" id="btnEditSubcatModal" title="Edit Selected Sub Category">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger <?= empty($product['subcategory_id']) ? 'd-none' : '' ?>" id="btnDeleteSubcat" title="Delete Selected Sub Category">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                            <select name="subcategory_id" id="subcategory_id" class="form-select" <?= empty($product['category_id']) ? 'disabled' : '' ?>>
                                <option value="">-- Select Sub Category --</option>
                                <?php foreach ($subcategories as $sc): ?>
                                    <option value="<?= $sc['id'] ?>" <?= $product['subcategory_id'] == $sc['id'] ? 'selected' : '' ?>><?= e($sc['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Sub-Subcategory -->
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0">Sub Sub Category</label>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-primary" id="btnOpenSubSubcatModal" <?= empty($product['subcategory_id']) ? 'disabled' : '' ?>>
                                        <i class="bi bi-plus-circle"></i> New
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-warning <?= empty($product['sub_subcategory_id']) ? 'd-none' : '' ?>" id="btnEditSubSubcatModal" title="Edit Selected Sub Sub Category">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger <?= empty($product['sub_subcategory_id']) ? 'd-none' : '' ?>" id="btnDeleteSubSubcat" title="Delete Selected Sub Sub Category">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                            <select name="sub_subcategory_id" id="sub_subcategory_id" class="form-select" <?= empty($product['subcategory_id']) ? 'disabled' : '' ?>>
                                <option value="">-- Select Sub Sub Category --</option>
                                <?php foreach ($subSubcategories as $ssc): ?>
                                    <option value="<?= $ssc['id'] ?>" <?= $product['sub_subcategory_id'] == $ssc['id'] ? 'selected' : '' ?>><?= e($ssc['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Selected Category Path Breadcrumb Preview -->
                    <div class="mt-3 p-2 bg-light rounded d-flex align-items-center gap-2 text-muted small" id="categoryBreadcrumb">
                        <i class="bi bi-folder2-open text-primary"></i>
                        <span class="fw-semibold">Hierarchy Path:</span>
                        <span id="breadcrumbPathText" class="text-dark">Loading hierarchy...</span>
                    </div>

                    <!-- Manual Category Codes & Whole Code for QR Code Input Fields -->
                    <div class="mt-3 pt-3 border-top">
                        <label class="form-label fw-bold text-dark mb-2">
                            <i class="bi bi-qr-code-scan text-primary me-1"></i> Category Short Codes &amp; Whole Code for QR Code
                        </label>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted mb-1">Main Category Code</label>
                                <input type="text" name="category_code" id="category_code" class="form-control text-uppercase fw-semibold" placeholder="e.g. W" value="<?= e($product['category_code'] ?? '') ?>" oninput="this.dataset.userEdited='1'; updateCombinedQRCode(true);">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted mb-1">Sub Category Code</label>
                                <input type="text" name="subcategory_code" id="subcategory_code" class="form-control text-uppercase fw-semibold" placeholder="e.g. M" value="<?= e($product['subcategory_code'] ?? '') ?>" oninput="this.dataset.userEdited='1'; updateCombinedQRCode(true);">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted mb-1">Sub Sub Category Code</label>
                                <input type="text" name="sub_subcategory_code" id="sub_subcategory_code" class="form-control text-uppercase fw-semibold" placeholder="e.g. F" value="<?= e($product['sub_subcategory_code'] ?? '') ?>" oninput="this.dataset.userEdited='1'; updateCombinedQRCode(true);">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-primary mb-1">Whole Code for QR Code</label>
                                <input type="text" name="qr_code_prefix" id="qr_code_prefix" class="form-control text-uppercase fw-bold border-primary text-primary" placeholder="e.g. WMF" value="<?= e($product['qr_code_prefix'] ?? '') ?>" oninput="this.dataset.userEdited='1'; checkQRCodeAvailability(this.value);">
                            </div>
                        </div>
                        <div id="qrCodeFeedback" class="mt-2"></div>
                    </div>
                </div>
            </div>

            <!-- 2. PRODUCT ATTRIBUTES (SIZES & COLORS) -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-palette2 me-2 text-primary"></i> 2. Product Attributes
                        </h5>
                        <small class="text-muted">Create and select sizes and colors applicable for this category hierarchy</small>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Size Attributes Multi-Select -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <label class="form-label fw-bold mb-0">
                                <i class="bi bi-rulers me-1 text-primary"></i> Size Attributes:
                            </label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAddSize">
                                    <i class="bi bi-plus-circle me-1"></i> Add Size
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSelectAllSizes">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearSizes">Clear</button>
                            </div>
                        </div>
                        <div class="p-2 border rounded bg-light d-flex flex-wrap gap-2 align-items-center" id="sizeSelectorContainer" style="min-height: 48px;">
                            <?php if (empty($sizes)): ?>
                                <div class="w-100 text-center py-2 text-muted small" id="emptySizePrompt">
                                    <i class="bi bi-rulers fs-5 text-secondary opacity-50 d-block mb-1"></i>
                                    No size attributes found. Click <strong>"+ Add Size"</strong> above to create sizes for this category.
                                </div>
                            <?php else: ?>
                                <?php foreach ($sizes as $sz): ?>
                                    <div class="btn-group btn-group-sm size-chip-group" id="size-chip-<?= $sz['id'] ?>">
                                        <label class="btn btn-outline-dark size-check-btn d-flex align-items-center gap-1" style="cursor: pointer;">
                                            <input type="checkbox" name="sizes[]" class="form-check-input me-1 size-checkbox" value="<?= $sz['id'] ?>" data-code="<?= e($sz['code']) ?>" data-name="<?= e($sz['name']) ?>" <?= in_array($sz['id'], $existingSizeIds ?? []) ? 'checked' : '' ?>>
                                            <span class="fw-bold"><?= e($sz['code']) ?></span>
                                            <?php if ($sz['name'] !== $sz['code']): ?>
                                                <small class="text-muted">(<?= e($sz['name']) ?>)</small>
                                            <?php endif; ?>
                                        </label>
                                        <button type="button" class="btn btn-outline-danger btn-delete-size p-1 px-2" data-id="<?= $sz['id'] ?>" data-name="<?= e($sz['code']) ?>" title="Delete size attribute">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Color Attributes Multi-Select -->
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <label class="form-label fw-bold mb-0">
                                <i class="bi bi-palette me-1 text-primary"></i> Color Attributes:
                            </label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAddColor">
                                    <i class="bi bi-plus-circle me-1"></i> Add Color
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSelectAllColors">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearColors">Clear</button>
                            </div>
                        </div>
                        <div class="p-2 border rounded bg-light d-flex flex-wrap gap-2 align-items-center" id="colorSelectorContainer" style="min-height: 48px;">
                            <?php if (empty($colors)): ?>
                                <div class="w-100 text-center py-2 text-muted small" id="emptyColorPrompt">
                                    <i class="bi bi-palette fs-5 text-secondary opacity-50 d-block mb-1"></i>
                                    No color attributes found. Click <strong>"+ Add Color"</strong> above to create colors for this category.
                                </div>
                            <?php else: ?>
                                <?php foreach ($colors as $col): ?>
                                    <div class="btn-group btn-group-sm color-chip-group" id="color-chip-<?= $col['id'] ?>">
                                        <label class="btn btn-outline-secondary color-check-btn d-flex align-items-center gap-2" style="cursor: pointer;">
                                            <input type="checkbox" name="colors[]" class="form-check-input me-1 color-checkbox" value="<?= $col['id'] ?>" data-name="<?= e($col['name']) ?>" data-hex="<?= e($col['hex_code'] ?? '#000000') ?>" <?= in_array($col['id'], $existingColorIds ?? []) ? 'checked' : '' ?>>
                                            <span style="display:inline-block; width:14px; height:14px; border-radius:50%; background-color:<?= e($col['hex_code'] ?? '#999') ?>; border:1px solid #ccc;"></span>
                                            <span><?= e($col['name']) ?></span>
                                        </label>
                                        <button type="button" class="btn btn-outline-danger btn-delete-color p-1 px-2" data-id="<?= $col['id'] ?>" data-name="<?= e($col['name']) ?>" title="Delete color attribute">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Card Footer with Action Buttons -->
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                    <a href="<?= url('production/catalog') ?>" class="btn btn-outline-secondary px-4">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold fs-6 shadow-sm" id="btnSubmitCatalog">
                        <i class="bi bi-check-circle-fill me-1"></i> Update Product Style
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- QUICK ADD MAIN CATEGORY MODAL -->
<div class="modal fade" id="modalAddMainCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-folder-plus me-2 text-primary"></i> Quick Add Main Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Existing Categories List -->
                <div class="mb-3 p-3 bg-light rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0 small fw-bold text-secondary text-uppercase">
                            <i class="bi bi-collection me-1 text-primary"></i> Existing Main Categories (<span id="existingMainCatCount" class="text-primary fw-bold">0</span>)
                        </label>
                        <span class="text-muted small" style="font-size: 0.75rem;"><i class="bi bi-hand-index-thumb me-1"></i>Click to check/select</span>
                    </div>
                    <div id="existingMainCatList" class="d-flex flex-wrap gap-1" style="max-height: 120px; overflow-y: auto;">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark">Main Category Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-tag text-muted"></i></span>
                        <input type="text" id="modalMainCatName" class="form-control text-uppercase fw-semibold" placeholder="E.G. MEN'S APPAREL, HOME TEXTILES" autocomplete="off" required>
                    </div>
                    <div id="modalMainCatFeedback" class="mt-2"></div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-semibold" id="btnSaveQuickMainCat">
                    <i class="bi bi-plus-lg me-1"></i> Add Main Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT MAIN CATEGORY MODAL -->
<div class="modal fade" id="modalEditMainCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Main Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark">Main Category Name <span class="text-danger">*</span></label>
                    <input type="text" id="modalEditMainCatNameInput" class="form-control text-uppercase fw-semibold" required>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-semibold" id="btnSaveEditMainCat">
                    <i class="bi bi-check-lg me-1"></i> Update Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ADD SUBCATEGORY MODAL -->
<div class="modal fade" id="modalAddSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-folder-symlink me-2 text-primary"></i> Quick Add Subcategory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Parent Category</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-diagram-2 text-primary"></i></span>
                        <input type="text" id="modalSubcatParentName" class="form-control fw-bold bg-light" readonly disabled>
                    </div>
                </div>

                <!-- Existing Subcategories List -->
                <div class="mb-3 p-3 bg-light rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0 small fw-bold text-secondary text-uppercase">
                            <i class="bi bi-collection me-1 text-primary"></i> Existing Subcategories in <span id="subcatParentNameSpan" class="text-primary"></span> (<span id="existingSubcatCount" class="text-primary fw-bold">0</span>)
                        </label>
                        <span class="text-muted small" style="font-size: 0.75rem;"><i class="bi bi-hand-index-thumb me-1"></i>Click to check/select</span>
                    </div>
                    <div id="existingSubcatList" class="d-flex flex-wrap gap-1" style="max-height: 120px; overflow-y: auto;">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark">Subcategory Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-tag text-muted"></i></span>
                        <input type="text" id="modalSubcatName" class="form-control text-uppercase fw-semibold" placeholder="E.G. NIGHTWEAR & SLEEPWEAR" autocomplete="off" required>
                    </div>
                    <div id="modalSubcatFeedback" class="mt-2"></div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-semibold" id="btnSaveQuickSubcat">
                    <i class="bi bi-plus-lg me-1"></i> Add Subcategory
                </button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT SUBCATEGORY MODAL -->
<div class="modal fade" id="modalEditSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Sub Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark">Sub Category Name <span class="text-danger">*</span></label>
                    <input type="text" id="modalEditSubcatNameInput" class="form-control text-uppercase fw-semibold" required>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-semibold" id="btnSaveEditSubcat">
                    <i class="bi bi-check-lg me-1"></i> Update Sub Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ADD SUB-SUBCATEGORY MODAL -->
<div class="modal fade" id="modalAddSubSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-diagram-3 me-2 text-primary"></i> Quick Add Sub-Sub Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Parent Subcategory</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-diagram-3 text-primary"></i></span>
                        <input type="text" id="modalSubSubcatParentName" class="form-control fw-bold bg-light" readonly disabled>
                    </div>
                </div>

                <!-- Existing Sub-Subcategories List -->
                <div class="mb-3 p-3 bg-light rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0 small fw-bold text-secondary text-uppercase">
                            <i class="bi bi-collection me-1 text-primary"></i> Existing in <span id="subSubcatParentNameSpan" class="text-primary"></span> (<span id="existingSubSubcatCount" class="text-primary fw-bold">0</span>)
                        </label>
                        <span class="text-muted small" style="font-size: 0.75rem;"><i class="bi bi-hand-index-thumb me-1"></i>Click to check/select</span>
                    </div>
                    <div id="existingSubSubcatList" class="d-flex flex-wrap gap-1" style="max-height: 120px; overflow-y: auto;">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark">Sub-Subcategory Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-tag text-muted"></i></span>
                        <input type="text" id="modalSubSubcatName" class="form-control text-uppercase fw-semibold" placeholder="E.G. FEEDING ZIP NIGHTY" autocomplete="off" required>
                    </div>
                    <div id="modalSubSubcatFeedback" class="mt-2"></div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-semibold" id="btnSaveQuickSubSubcat">
                    <i class="bi bi-plus-lg me-1"></i> Add Sub-Sub Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT SUB-SUBCATEGORY MODAL -->
<div class="modal fade" id="modalEditSubSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Sub Sub Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark">Sub Sub Category Name <span class="text-danger">*</span></label>
                    <input type="text" id="modalEditSubSubcatNameInput" class="form-control text-uppercase fw-semibold" required>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-semibold" id="btnSaveEditSubSubcat">
                    <i class="bi bi-check-lg me-1"></i> Update Sub Sub Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ADD SIZE MODAL -->
<div class="modal fade" id="modalAddSize" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-1 text-primary"></i> Quick Add Size Attribute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Size Code / Label <span class="text-danger">*</span></label>
                    <input type="text" id="modalSizeCode" class="form-control text-uppercase" placeholder="E.G. M, L, XL, 32, 34, FREE SIZE" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Size Description <small class="text-muted">(Optional)</small></label>
                    <input type="text" id="modalSizeName" class="form-control text-uppercase" placeholder="E.G. MEDIUM, 32 INCHES">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveQuickSize">
                    <i class="bi bi-check-lg me-1"></i> Add Size
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ADD COLOR MODAL -->
<div class="modal fade" id="modalAddColor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-1 text-primary"></i> Quick Add Color Attribute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Color Name <span class="text-danger">*</span></label>
                    <input type="text" id="modalColorName" class="form-control text-uppercase" placeholder="E.G. SKY BLUE, NAVY BLUE, MAROON" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Color Hex Code</label>
                    <div class="input-group">
                        <input type="color" id="modalColorPicker" class="form-control form-control-color" value="#001F3F" title="Choose color">
                        <input type="text" id="modalColorHex" class="form-control text-uppercase" value="#001F3F">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveQuickColor">
                    <i class="bi bi-check-lg me-1"></i> Add Color
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const currentEditingProductId = <?= (int)($product['id'] ?? 0) ?>;
let isQrCodeDuplicate = false;
let qrDuplicateDetails = null;
let qrCheckTimeout = null;

function checkQRCodeAvailability(code) {
    const feedback = document.getElementById('qrCodeFeedback');
    const qrInp = document.getElementById('qr_code_prefix');
    if (!feedback || !qrInp) return;

    clearTimeout(qrCheckTimeout);
    const trimmed = (code || '').trim().toUpperCase();

    if (!trimmed) {
        feedback.innerHTML = '';
        qrInp.classList.remove('is-invalid', 'border-danger', 'border-warning', 'border-success', 'is-valid');
        isQrCodeDuplicate = false;
        qrDuplicateDetails = null;
        return;
    }

    qrCheckTimeout = setTimeout(() => {
        const baseUrl = '<?= url('') ?>';
        fetch(`${baseUrl}/api/catalog/check-qr-code?code=${encodeURIComponent(trimmed)}&exclude_id=${currentEditingProductId}`)
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    isQrCodeDuplicate = true;
                    qrDuplicateDetails = data;
                    qrInp.classList.add('border-warning', 'border-2', 'is-invalid');
                    qrInp.classList.remove('border-primary', 'border-success', 'is-valid');

                    const hierarchyBadge = data.product?.hierarchy ? `<span class="badge bg-light text-dark border ms-1">${data.product.hierarchy}</span>` : '';
                    feedback.innerHTML = `
                        <div class="alert alert-warning py-2 px-3 mb-0 small d-flex align-items-start gap-2 border-warning shadow-sm animate__animated animate__fadeIn">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0 mt-0.5"></i>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark">⚠️ Already Have This Code!</div>
                                <div class="text-secondary mt-0.5">
                                    The Whole Code <strong>"${trimmed}"</strong> is already in use by Style:
                                    <strong class="text-dark">${data.product?.name || 'Existing Style'}</strong>
                                    ${hierarchyBadge}
                                </div>
                                <div class="text-primary fw-semibold mt-1">
                                    <i class="bi bi-pencil-square me-1"></i> Please customize this Whole Code (or sub codes) to avoid duplicates.
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    isQrCodeDuplicate = false;
                    qrDuplicateDetails = null;
                    qrInp.classList.remove('border-warning', 'is-invalid', 'border-danger');
                    qrInp.classList.add('border-success');
                    feedback.innerHTML = `
                        <div class="text-success small fw-semibold mt-1 d-flex align-items-center gap-1">
                            <i class="bi bi-check-circle-fill"></i> Whole Code <strong>"${trimmed}"</strong> is available and unique!
                        </div>
                    `;
                }
            })
            .catch(err => {
                console.error('Error checking QR code:', err);
            });
    }, 200);
}

function updateCombinedQRCode(force = false) {
    const catInp = document.getElementById('category_code');
    const subcatInp = document.getElementById('subcategory_code');
    const subSubcatInp = document.getElementById('sub_subcategory_code');
    const qrInp = document.getElementById('qr_code_prefix');

    if (!qrInp) return;
    const c1 = (catInp?.value || '').trim().toUpperCase();
    const c2 = (subcatInp?.value || '').trim().toUpperCase();
    const c3 = (subSubcatInp?.value || '').trim().toUpperCase();

    if (force || !qrInp.dataset.userEdited || !qrInp.value.trim()) {
        qrInp.value = (c1 + c2 + c3).toUpperCase();
        delete qrInp.dataset.userEdited;
    }
    checkQRCodeAvailability(qrInp.value);
}

document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '<?= url('') ?>';

    // Auto-uppercase conversion on all text inputs
    document.querySelectorAll('input[type="text"], textarea').forEach(function(input) {
        input.addEventListener('input', function() {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(start, end);
        });
    });

    // -------------------------------------------------------------
    // 3-Tier Dynamic Cascading Dropdowns
    // -------------------------------------------------------------
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    const subSubcategorySelect = document.getElementById('sub_subcategory_id');
    const btnOpenSubcatModal = document.getElementById('btnOpenSubcatModal');
    const btnOpenSubSubcatModal = document.getElementById('btnOpenSubSubcatModal');
    const breadcrumbPathText = document.getElementById('breadcrumbPathText');

    // Action buttons for Edit & Delete
    const btnEditMainCatModal = document.getElementById('btnEditMainCatModal');
    const btnDeleteMainCat = document.getElementById('btnDeleteMainCat');
    const btnEditSubcatModal = document.getElementById('btnEditSubcatModal');
    const btnDeleteSubcat = document.getElementById('btnDeleteSubcat');
    const btnEditSubSubcatModal = document.getElementById('btnEditSubSubcatModal');
    const btnDeleteSubSubcat = document.getElementById('btnDeleteSubSubcat');

    function syncHierarchyCodes(changedTier = null) {
        const catInp = document.getElementById('category_code');
        const subcatInp = document.getElementById('subcategory_code');
        const subSubcatInp = document.getElementById('sub_subcategory_code');
        const qrInp = document.getElementById('qr_code_prefix');

        const catText = (categorySelect.options[categorySelect.selectedIndex]?.text || '').replace(/^--.*--$/, '').trim();
        const subcatText = (subcategorySelect.options[subcategorySelect.selectedIndex]?.text || '').replace(/^--.*--$/, '').trim();
        const subSubcatText = (subSubcategorySelect.options[subSubcategorySelect.selectedIndex]?.text || '').replace(/^--.*--$/, '').trim();

        // When user actively changes any dropdown tier, clear manual lock for that tier and for whole QR code
        if (changedTier === 'category') {
            if (catInp) delete catInp.dataset.userEdited;
            if (subcatInp) delete subcatInp.dataset.userEdited;
            if (subSubcatInp) delete subSubcatInp.dataset.userEdited;
            if (qrInp) delete qrInp.dataset.userEdited;
        } else if (changedTier === 'subcategory') {
            if (subcatInp) delete subcatInp.dataset.userEdited;
            if (subSubcatInp) delete subSubcatInp.dataset.userEdited;
            if (qrInp) delete qrInp.dataset.userEdited;
        } else if (changedTier === 'sub_subcategory') {
            if (subSubcatInp) delete subSubcatInp.dataset.userEdited;
            if (qrInp) delete qrInp.dataset.userEdited;
        }

        if (categorySelect.value && catText && (!catInp?.dataset.userEdited || changedTier === 'category')) {
            if (catInp) catInp.value = catText.charAt(0).toUpperCase();
        } else if (!categorySelect.value) {
            if (catInp && !catInp?.dataset.userEdited) catInp.value = '';
        }

        if (subcategorySelect.value && subcatText && (!subcatInp?.dataset.userEdited || changedTier === 'subcategory' || changedTier === 'category')) {
            if (subcatInp) subcatInp.value = subcatText.charAt(0).toUpperCase();
        } else if (!subcategorySelect.value) {
            if (subcatInp && !subcatInp?.dataset.userEdited) subcatInp.value = '';
        }

        if (subSubcategorySelect.value && subSubcatText && (!subSubcatInp?.dataset.userEdited || changedTier === 'sub_subcategory' || changedTier === 'subcategory' || changedTier === 'category')) {
            if (subSubcatInp) subSubcatInp.value = subSubcatText.charAt(0).toUpperCase();
        } else if (!subSubcategorySelect.value) {
            if (subSubcatInp && !subSubcatInp?.dataset.userEdited) subSubcatInp.value = '';
        }

        updateCombinedQRCode(Boolean(changedTier));
    }

    function updateHierarchyBreadcrumb(changedTier = null) {
        const catText = categorySelect.options[categorySelect.selectedIndex]?.text || '';
        const subcatText = subcategorySelect.options[subcategorySelect.selectedIndex]?.text || '';
        const subSubcatText = subSubcategorySelect.options[subSubcategorySelect.selectedIndex]?.text || '';

        let parts = [];
        if (categorySelect.value && !catText.startsWith('--')) parts.push(catText);
        if (subcategorySelect.value && !subcatText.startsWith('--')) parts.push(subcatText);
        if (subSubcategorySelect.value && !subSubcatText.startsWith('--')) parts.push(subSubcatText);

        if (parts.length > 0) {
            breadcrumbPathText.innerHTML = parts.map((p, idx) => `<span class="badge bg-primary-subtle text-primary border">${p}</span>`).join(' <i class="bi bi-chevron-right small text-muted"></i> ');
        } else {
            breadcrumbPathText.innerText = 'Select a category above...';
        }

        syncHierarchyCodes(changedTier);
    }

    updateHierarchyBreadcrumb();

    // Category Change -> Load Subcategories & Toggle Action Buttons
    categorySelect.addEventListener('change', function() {
        const catId = this.value;
        subcategorySelect.innerHTML = '<option value="">-- Loading Subcategories... --</option>';
        subcategorySelect.disabled = true;
        subSubcategorySelect.innerHTML = '<option value="">-- Select Sub Category First --</option>';
        subSubcategorySelect.disabled = true;
        btnOpenSubcatModal.disabled = !catId;
        btnOpenSubSubcatModal.disabled = true;

        if (catId) {
            btnEditMainCatModal?.classList.remove('d-none');
            btnDeleteMainCat?.classList.remove('d-none');
        } else {
            btnEditMainCatModal?.classList.add('d-none');
            btnDeleteMainCat?.classList.add('d-none');
        }

        btnEditSubcatModal?.classList.add('d-none');
        btnDeleteSubcat?.classList.add('d-none');
        btnEditSubSubcatModal?.classList.add('d-none');
        btnDeleteSubSubcat?.classList.add('d-none');

        if (!catId) {
            subcategorySelect.innerHTML = '<option value="">-- Select Category First --</option>';
            updateHierarchyBreadcrumb('category');
            return;
        }

        fetch(`${baseUrl}/api/subcategories/${catId}`)
            .then(res => res.json())
            .then(data => {
                subcategorySelect.innerHTML = '<option value="">-- Select Sub Category --</option>';
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.name;
                        subcategorySelect.appendChild(opt);
                    });
                }
                subcategorySelect.disabled = false;
                updateHierarchyBreadcrumb('category');
            })
            .catch(err => {
                console.error('Error fetching subcategories:', err);
                subcategorySelect.innerHTML = '<option value="">-- No Subcategories Found --</option>';
                subcategorySelect.disabled = false;
                updateHierarchyBreadcrumb('category');
            });
    });

    // Subcategory Change -> Load Sub-Subcategories & Toggle Action Buttons
    subcategorySelect.addEventListener('change', function() {
        const subcatId = this.value;
        subSubcategorySelect.innerHTML = '<option value="">-- Loading Sub-Subcategories... --</option>';
        subSubcategorySelect.disabled = true;
        btnOpenSubSubcatModal.disabled = !subcatId;

        if (subcatId) {
            btnEditSubcatModal?.classList.remove('d-none');
            btnDeleteSubcat?.classList.remove('d-none');
        } else {
            btnEditSubcatModal?.classList.add('d-none');
            btnDeleteSubcat?.classList.add('d-none');
        }

        btnEditSubSubcatModal?.classList.add('d-none');
        btnDeleteSubSubcat?.classList.add('d-none');

        if (!subcatId) {
            subSubcategorySelect.innerHTML = '<option value="">-- Select Sub Category First --</option>';
            updateHierarchyBreadcrumb('subcategory');
            return;
        }

        fetch(`${baseUrl}/api/sub-subcategories/${subcatId}`)
            .then(res => res.json())
            .then(data => {
                subSubcategorySelect.innerHTML = '<option value="">-- Select Sub Sub Category --</option>';
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.name;
                        subSubcategorySelect.appendChild(opt);
                    });
                }
                subSubcategorySelect.disabled = false;
                updateHierarchyBreadcrumb('subcategory');
            })
            .catch(err => {
                console.error('Error fetching sub-subcategories:', err);
                subSubcategorySelect.innerHTML = '<option value="">-- No Sub-Subcategories Found --</option>';
                subSubcategorySelect.disabled = false;
                updateHierarchyBreadcrumb('subcategory');
            });
    });

    // Sub-Subcategory Change -> Toggle Action Buttons & Update Breadcrumb
    subSubcategorySelect.addEventListener('change', function() {
        const subSubcatId = this.value;
        if (subSubcatId) {
            btnEditSubSubcatModal?.classList.remove('d-none');
            btnDeleteSubSubcat?.classList.remove('d-none');
        } else {
            btnEditSubSubcatModal?.classList.add('d-none');
            btnDeleteSubSubcat?.classList.add('d-none');
        }
        updateHierarchyBreadcrumb('sub_subcategory');
    });

    // =============================================================
    // DYNAMIC EXISTING CREATIONS LIST & DUPLICATE PREVENTION
    // =============================================================

    // -------------------------------------------------------------
    // 1. MAIN CATEGORY QUICK ADD & EXISTING LIST
    // -------------------------------------------------------------
    function refreshExistingMainCategories() {
        const list = document.getElementById('existingMainCatList');
        const countSpan = document.getElementById('existingMainCatCount');
        const feedback = document.getElementById('modalMainCatFeedback');
        if (!list || !countSpan) return;

        feedback.innerHTML = '';
        list.innerHTML = '';

        const cats = Array.from(categorySelect.options)
            .filter(o => o.value && o.value.trim() !== '')
            .map(o => ({ id: o.value, name: o.text.trim() }));

        countSpan.textContent = cats.length;

        if (cats.length === 0) {
            list.innerHTML = '<span class="text-muted small fst-italic p-1">No main categories created yet.</span>';
            return;
        }

        cats.forEach(cat => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill d-inline-flex align-items-center gap-1 main-cat-chip';
            btn.dataset.id = cat.id;
            btn.dataset.name = cat.name.toUpperCase();
            btn.innerHTML = `<i class="bi bi-tag text-primary small"></i> <span>${cat.name}</span>`;
            btn.title = `Click to inspect or use "${cat.name}"`;
            btn.onclick = function() {
                document.getElementById('modalMainCatName').value = cat.name;
                validateMainCatInput(cat.name);
            };
            list.appendChild(btn);
        });
    }

    function validateMainCatInput(val) {
        const inputName = val.trim().toUpperCase();
        const feedback = document.getElementById('modalMainCatFeedback');
        const chips = document.querySelectorAll('#existingMainCatList .main-cat-chip');
        if (!feedback) return;

        chips.forEach(c => {
            c.classList.remove('btn-warning', 'text-dark', 'fw-bold', 'border-warning', 'btn-primary', 'text-white');
            c.classList.add('btn-outline-secondary');
        });

        if (!inputName) {
            feedback.innerHTML = '';
            return;
        }

        let exactMatch = null;
        const partialMatches = [];

        chips.forEach(c => {
            const chipName = c.dataset.name;
            if (chipName === inputName) {
                exactMatch = { id: c.dataset.id, name: c.innerText.trim() };
                c.classList.remove('btn-outline-secondary');
                c.classList.add('btn-warning', 'text-dark', 'fw-bold', 'border-warning');
            } else if (chipName.includes(inputName) || inputName.includes(chipName)) {
                partialMatches.push(c.innerText.trim());
                c.classList.remove('btn-outline-secondary');
                c.classList.add('btn-primary', 'text-white');
            }
        });

        if (exactMatch) {
            feedback.innerHTML = `
                <div class="alert alert-warning py-2 px-3 mb-0 small d-flex align-items-center justify-content-between border-warning shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-5 me-2"></i>
                        <div>
                            <strong>Already Exists!</strong> "<span>${exactMatch.name}</span>" is already created.
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold px-2 py-1 ms-2 shadow-sm" id="btnSelectExistingMainCatBtn">
                        <i class="bi bi-check2-circle me-1"></i> Use Existing
                    </button>
                </div>`;
            document.getElementById('btnSelectExistingMainCatBtn').onclick = function() {
                selectExistingMainCategory(exactMatch.id);
            };
        } else if (partialMatches.length > 0) {
            feedback.innerHTML = `
                <div class="text-primary small mt-1">
                    <i class="bi bi-info-circle me-1"></i> Similar existing: <strong>${partialMatches.join(', ')}</strong>
                </div>`;
        } else {
            feedback.innerHTML = `
                <div class="text-success small mt-1 fw-semibold">
                    <i class="bi bi-check-circle-fill me-1"></i> "${inputName}" is available to create as a new Main Category.
                </div>`;
        }
    }

    function selectExistingMainCategory(catId) {
        categorySelect.value = catId;
        categorySelect.dispatchEvent(new Event('change'));
        const modalEl = document.getElementById('modalAddMainCategory');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
    }

    document.getElementById('btnEditOpenMainCatModal')?.addEventListener('click', function() {
        document.getElementById('modalMainCatName').value = '';
        refreshExistingMainCategories();
    });

    document.getElementById('modalMainCatName')?.addEventListener('input', function() {
        validateMainCatInput(this.value);
    });

    document.getElementById('btnSaveQuickMainCat').addEventListener('click', function() {
        const name = document.getElementById('modalMainCatName').value.trim().toUpperCase();
        if (!name) {
            alert('Please enter a Main Category name.');
            return;
        }

        const formData = new FormData();
        formData.append('name', name);

        fetch(`${baseUrl}/api/categories/quick-add`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let exists = false;
                Array.from(categorySelect.options).forEach(opt => {
                    if (opt.value == data.id) {
                        opt.selected = true;
                        exists = true;
                    }
                });
                if (!exists) {
                    const opt = document.createElement('option');
                    opt.value = data.id;
                    opt.textContent = data.name;
                    opt.selected = true;
                    categorySelect.appendChild(opt);
                }
                categorySelect.dispatchEvent(new Event('change'));
                bootstrap.Modal.getInstance(document.getElementById('modalAddMainCategory')).hide();
                document.getElementById('modalMainCatName').value = '';
            } else {
                alert(data.message || 'Error adding Main Category.');
            }
        })
        .catch(err => {
            console.error('Error adding main category:', err);
            alert('Server error while adding Main Category.');
        });
    });

    // -------------------------------------------------------------
    // Edit & Delete Main Category Handlers
    // -------------------------------------------------------------
    const modalEditMainCatNameInput = document.getElementById('modalEditMainCatNameInput');

    btnEditMainCatModal?.addEventListener('click', function() {
        const catId = categorySelect.value;
        if (!catId) return;
        const currentName = categorySelect.options[categorySelect.selectedIndex]?.text || '';
        modalEditMainCatNameInput.value = currentName;
        const modal = new bootstrap.Modal(document.getElementById('modalEditMainCategory'));
        modal.show();
    });

    document.getElementById('btnSaveEditMainCat')?.addEventListener('click', function() {
        const catId = categorySelect.value;
        const name = modalEditMainCatNameInput.value.trim().toUpperCase();
        if (!catId || !name) {
            alert('Please enter a category name.');
            return;
        }

        const formData = new FormData();
        formData.append('name', name);

        fetch(`${baseUrl}/api/categories/update/${catId}`, {
            method: 'POST',
            body: formData
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                if (categorySelect.selectedIndex >= 0) {
                    categorySelect.options[categorySelect.selectedIndex].text = data.name;
                }
                updateHierarchyBreadcrumb();
                refreshExistingMainCategories();
                bootstrap.Modal.getInstance(document.getElementById('modalEditMainCategory')).hide();
            } else {
                alert(data.message || 'Error updating category.');
            }
        })
        .catch(err => {
            console.error('Error updating category:', err);
            alert('Server error while updating category.');
        });
    });

    btnDeleteMainCat?.addEventListener('click', function() {
        const catId = categorySelect.value;
        if (!catId) return;
        const currentName = categorySelect.options[categorySelect.selectedIndex]?.text || 'this category';
        if (!confirm(`Are you sure you want to delete the main category "${currentName}"? This action cannot be undone.`)) {
            return;
        }

        fetch(`${baseUrl}/api/categories/delete/${catId}`, {
            method: 'POST'
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                const opt = categorySelect.querySelector(`option[value="${catId}"]`);
                if (opt) opt.remove();
                categorySelect.value = '';
                categorySelect.dispatchEvent(new Event('change'));
                refreshExistingMainCategories();
            } else {
                alert(data.message || 'Error deleting category.');
            }
        })
        .catch(err => {
            console.error('Error deleting category:', err);
            alert('Server error while deleting category.');
        });
    });

    // -------------------------------------------------------------
    // 2. SUBCATEGORY QUICK ADD & EXISTING LIST
    // -------------------------------------------------------------
    function refreshExistingSubcategories() {
        const list = document.getElementById('existingSubcatList');
        const countSpan = document.getElementById('existingSubcatCount');
        const parentSpan = document.getElementById('subcatParentNameSpan');
        const feedback = document.getElementById('modalSubcatFeedback');
        if (!list || !countSpan) return;

        feedback.innerHTML = '';
        list.innerHTML = '';

        const parentName = categorySelect.options[categorySelect.selectedIndex]?.text || 'Parent Category';
        if (parentSpan) parentSpan.textContent = parentName;

        const subcats = Array.from(subcategorySelect.options)
            .filter(o => o.value && o.value.trim() !== '')
            .map(o => ({ id: o.value, name: o.text.trim() }));

        countSpan.textContent = subcats.length;

        if (subcats.length === 0) {
            list.innerHTML = `<span class="text-muted small fst-italic p-1">No subcategories created yet in "${parentName}".</span>`;
            return;
        }

        subcats.forEach(sub => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill d-inline-flex align-items-center gap-1 subcat-chip';
            btn.dataset.id = sub.id;
            btn.dataset.name = sub.name.toUpperCase();
            btn.innerHTML = `<i class="bi bi-tag text-primary small"></i> <span>${sub.name}</span>`;
            btn.title = `Click to inspect or use "${sub.name}"`;
            btn.onclick = function() {
                document.getElementById('modalSubcatName').value = sub.name;
                validateSubcatInput(sub.name);
            };
            list.appendChild(btn);
        });
    }

    function validateSubcatInput(val) {
        const inputName = val.trim().toUpperCase();
        const feedback = document.getElementById('modalSubcatFeedback');
        const chips = document.querySelectorAll('#existingSubcatList .subcat-chip');
        if (!feedback) return;

        chips.forEach(c => {
            c.classList.remove('btn-warning', 'text-dark', 'fw-bold', 'border-warning', 'btn-primary', 'text-white');
            c.classList.add('btn-outline-secondary');
        });

        if (!inputName) {
            feedback.innerHTML = '';
            return;
        }

        let exactMatch = null;
        const partialMatches = [];

        chips.forEach(c => {
            const chipName = c.dataset.name;
            if (chipName === inputName) {
                exactMatch = { id: c.dataset.id, name: c.innerText.trim() };
                c.classList.remove('btn-outline-secondary');
                c.classList.add('btn-warning', 'text-dark', 'fw-bold', 'border-warning');
            } else if (chipName.includes(inputName) || inputName.includes(chipName)) {
                partialMatches.push(c.innerText.trim());
                c.classList.remove('btn-outline-secondary');
                c.classList.add('btn-primary', 'text-white');
            }
        });

        const parentName = categorySelect.options[categorySelect.selectedIndex]?.text || 'selected category';

        if (exactMatch) {
            feedback.innerHTML = `
                <div class="alert alert-warning py-2 px-3 mb-0 small d-flex align-items-center justify-content-between border-warning shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-5 me-2"></i>
                        <div>
                            <strong>Already Exists!</strong> "<span>${exactMatch.name}</span>" is already created under ${parentName}.
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold px-2 py-1 ms-2 shadow-sm" id="btnSelectExistingSubcatBtn">
                        <i class="bi bi-check2-circle me-1"></i> Use Existing
                    </button>
                </div>`;
            document.getElementById('btnSelectExistingSubcatBtn').onclick = function() {
                selectExistingSubcategory(exactMatch.id);
            };
        } else if (partialMatches.length > 0) {
            feedback.innerHTML = `
                <div class="text-primary small mt-1">
                    <i class="bi bi-info-circle me-1"></i> Similar in ${parentName}: <strong>${partialMatches.join(', ')}</strong>
                </div>`;
        } else {
            feedback.innerHTML = `
                <div class="text-success small mt-1 fw-semibold">
                    <i class="bi bi-check-circle-fill me-1"></i> "${inputName}" is available to create in ${parentName}.
                </div>`;
        }
    }

    function selectExistingSubcategory(subcatId) {
        subcategorySelect.value = subcatId;
        subcategorySelect.dispatchEvent(new Event('change'));
        const modalEl = document.getElementById('modalAddSubcategory');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
    }

    btnOpenSubcatModal.addEventListener('click', function() {
        const catText = categorySelect.options[categorySelect.selectedIndex]?.text || '';
        document.getElementById('modalSubcatParentName').value = catText;
        document.getElementById('modalSubcatName').value = '';
        refreshExistingSubcategories();
        const modal = new bootstrap.Modal(document.getElementById('modalAddSubcategory'));
        modal.show();
    });

    document.getElementById('modalSubcatName')?.addEventListener('input', function() {
        validateSubcatInput(this.value);
    });

    document.getElementById('btnSaveQuickSubcat').addEventListener('click', function() {
        const catId = categorySelect.value;
        const name = document.getElementById('modalSubcatName').value.trim().toUpperCase();
        if (!catId || !name) {
            alert('Please enter a subcategory name.');
            return;
        }

        const formData = new FormData();
        formData.append('category_id', catId);
        formData.append('name', name);

        fetch(`${baseUrl}/api/subcategories/quick-add`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let exists = false;
                Array.from(subcategorySelect.options).forEach(opt => {
                    if (opt.value == data.id) {
                        opt.selected = true;
                        exists = true;
                    }
                });
                if (!exists) {
                    const opt = document.createElement('option');
                    opt.value = data.id;
                    opt.textContent = data.name;
                    opt.selected = true;
                    subcategorySelect.appendChild(opt);
                }
                subcategorySelect.disabled = false;
                btnOpenSubSubcatModal.disabled = false;
                subcategorySelect.dispatchEvent(new Event('change'));
                bootstrap.Modal.getInstance(document.getElementById('modalAddSubcategory')).hide();
                document.getElementById('modalSubcatName').value = '';
            } else {
                alert(data.message || 'Error adding subcategory.');
            }
        })
        .catch(err => {
            console.error('Error adding subcategory:', err);
            alert('Server error while adding subcategory.');
        });
    });

    // -------------------------------------------------------------
    // Edit & Delete Sub Category Handlers
    // -------------------------------------------------------------
    const modalEditSubcatNameInput = document.getElementById('modalEditSubcatNameInput');

    btnEditSubcatModal?.addEventListener('click', function() {
        const subcatId = subcategorySelect.value;
        if (!subcatId) return;
        const currentName = subcategorySelect.options[subcategorySelect.selectedIndex]?.text || '';
        modalEditSubcatNameInput.value = currentName;
        const modal = new bootstrap.Modal(document.getElementById('modalEditSubcategory'));
        modal.show();
    });

    document.getElementById('btnSaveEditSubcat')?.addEventListener('click', function() {
        const subcatId = subcategorySelect.value;
        const name = modalEditSubcatNameInput.value.trim().toUpperCase();
        if (!subcatId || !name) {
            alert('Please enter a subcategory name.');
            return;
        }

        const formData = new FormData();
        formData.append('name', name);

        fetch(`${baseUrl}/api/subcategories/update/${subcatId}`, {
            method: 'POST',
            body: formData
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                if (subcategorySelect.selectedIndex >= 0) {
                    subcategorySelect.options[subcategorySelect.selectedIndex].text = data.name;
                }
                updateHierarchyBreadcrumb();
                refreshExistingSubcategories();
                bootstrap.Modal.getInstance(document.getElementById('modalEditSubcategory')).hide();
            } else {
                alert(data.message || 'Error updating subcategory.');
            }
        })
        .catch(err => {
            console.error('Error updating subcategory:', err);
            alert('Server error while updating subcategory.');
        });
    });

    btnDeleteSubcat?.addEventListener('click', function() {
        const subcatId = subcategorySelect.value;
        if (!subcatId) return;
        const currentName = subcategorySelect.options[subcategorySelect.selectedIndex]?.text || 'this subcategory';
        if (!confirm(`Are you sure you want to delete the sub category "${currentName}"?`)) {
            return;
        }

        fetch(`${baseUrl}/api/subcategories/delete/${subcatId}`, {
            method: 'POST'
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                const opt = subcategorySelect.querySelector(`option[value="${subcatId}"]`);
                if (opt) opt.remove();
                subcategorySelect.value = '';
                subcategorySelect.dispatchEvent(new Event('change'));
                refreshExistingSubcategories();
            } else {
                alert(data.message || 'Error deleting subcategory.');
            }
        })
        .catch(err => {
            console.error('Error deleting subcategory:', err);
            alert('Server error while deleting subcategory.');
        });
    });

    // -------------------------------------------------------------
    // 3. SUB-SUBCATEGORY QUICK ADD & EXISTING LIST
    // -------------------------------------------------------------
    function refreshExistingSubSubcategories() {
        const list = document.getElementById('existingSubSubcatList');
        const countSpan = document.getElementById('existingSubSubcatCount');
        const parentSpan = document.getElementById('subSubcatParentNameSpan');
        const feedback = document.getElementById('modalSubSubcatFeedback');
        if (!list || !countSpan) return;

        feedback.innerHTML = '';
        list.innerHTML = '';

        const parentName = subcategorySelect.options[subcategorySelect.selectedIndex]?.text || 'Parent Subcategory';
        if (parentSpan) parentSpan.textContent = parentName;

        const subSubcats = Array.from(subSubcategorySelect.options)
            .filter(o => o.value && o.value.trim() !== '')
            .map(o => ({ id: o.value, name: o.text.trim() }));

        countSpan.textContent = subSubcats.length;

        if (subSubcats.length === 0) {
            list.innerHTML = `<span class="text-muted small fst-italic p-1">No sub-subcategories created yet in "${parentName}".</span>`;
            return;
        }

        subSubcats.forEach(sub => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill d-inline-flex align-items-center gap-1 subsubcat-chip';
            btn.dataset.id = sub.id;
            btn.dataset.name = sub.name.toUpperCase();
            btn.innerHTML = `<i class="bi bi-tag text-primary small"></i> <span>${sub.name}</span>`;
            btn.title = `Click to inspect or use "${sub.name}"`;
            btn.onclick = function() {
                document.getElementById('modalSubSubcatName').value = sub.name;
                validateSubSubcatInput(sub.name);
            };
            list.appendChild(btn);
        });
    }

    function validateSubSubcatInput(val) {
        const inputName = val.trim().toUpperCase();
        const feedback = document.getElementById('modalSubSubcatFeedback');
        const chips = document.querySelectorAll('#existingSubSubcatList .subsubcat-chip');
        if (!feedback) return;

        chips.forEach(c => {
            c.classList.remove('btn-warning', 'text-dark', 'fw-bold', 'border-warning', 'btn-primary', 'text-white');
            c.classList.add('btn-outline-secondary');
        });

        if (!inputName) {
            feedback.innerHTML = '';
            return;
        }

        let exactMatch = null;
        const partialMatches = [];

        chips.forEach(c => {
            const chipName = c.dataset.name;
            if (chipName === inputName) {
                exactMatch = { id: c.dataset.id, name: c.innerText.trim() };
                c.classList.remove('btn-outline-secondary');
                c.classList.add('btn-warning', 'text-dark', 'fw-bold', 'border-warning');
            } else if (chipName.includes(inputName) || inputName.includes(chipName)) {
                partialMatches.push(c.innerText.trim());
                c.classList.remove('btn-outline-secondary');
                c.classList.add('btn-primary', 'text-white');
            }
        });

        const parentName = subcategorySelect.options[subcategorySelect.selectedIndex]?.text || 'selected subcategory';

        if (exactMatch) {
            feedback.innerHTML = `
                <div class="alert alert-warning py-2 px-3 mb-0 small d-flex align-items-center justify-content-between border-warning shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-5 me-2"></i>
                        <div>
                            <strong>Already Exists!</strong> "<span>${exactMatch.name}</span>" is already created under ${parentName}.
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold px-2 py-1 ms-2 shadow-sm" id="btnSelectExistingSubSubcatBtn">
                        <i class="bi bi-check2-circle me-1"></i> Use Existing
                    </button>
                </div>`;
            document.getElementById('btnSelectExistingSubSubcatBtn').onclick = function() {
                selectExistingSubSubcategory(exactMatch.id);
            };
        } else if (partialMatches.length > 0) {
            feedback.innerHTML = `
                <div class="text-primary small mt-1">
                    <i class="bi bi-info-circle me-1"></i> Similar in ${parentName}: <strong>${partialMatches.join(', ')}</strong>
                </div>`;
        } else {
            feedback.innerHTML = `
                <div class="text-success small mt-1 fw-semibold">
                    <i class="bi bi-check-circle-fill me-1"></i> "${inputName}" is available to create in ${parentName}.
                </div>`;
        }
    }

    function selectExistingSubSubcategory(subSubcatId) {
        subSubcategorySelect.value = subSubcatId;
        subSubcategorySelect.dispatchEvent(new Event('change'));
        const modalEl = document.getElementById('modalAddSubSubcategory');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
    }

    btnOpenSubSubcatModal.addEventListener('click', function() {
        const subcatText = subcategorySelect.options[subcategorySelect.selectedIndex]?.text || '';
        document.getElementById('modalSubSubcatParentName').value = subcatText;
        document.getElementById('modalSubSubcatName').value = '';
        refreshExistingSubSubcategories();
        const modal = new bootstrap.Modal(document.getElementById('modalAddSubSubcategory'));
        modal.show();
    });

    document.getElementById('modalSubSubcatName')?.addEventListener('input', function() {
        validateSubSubcatInput(this.value);
    });

    document.getElementById('btnSaveQuickSubSubcat').addEventListener('click', function() {
        const subcatId = subcategorySelect.value;
        const name = document.getElementById('modalSubSubcatName').value.trim().toUpperCase();
        if (!subcatId || !name) {
            alert('Please enter a sub-subcategory name.');
            return;
        }

        const formData = new FormData();
        formData.append('subcategory_id', subcatId);
        formData.append('name', name);

        fetch(`${baseUrl}/api/sub-subcategories/quick-add`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let exists = false;
                Array.from(subSubcategorySelect.options).forEach(opt => {
                    if (opt.value == data.id) {
                        opt.selected = true;
                        exists = true;
                    }
                });
                if (!exists) {
                    const opt = document.createElement('option');
                    opt.value = data.id;
                    opt.textContent = data.name;
                    opt.selected = true;
                    subSubcategorySelect.appendChild(opt);
                }
                subSubcategorySelect.disabled = false;
                subSubcategorySelect.dispatchEvent(new Event('change'));
                bootstrap.Modal.getInstance(document.getElementById('modalAddSubSubcategory')).hide();
                document.getElementById('modalSubSubcatName').value = '';
            } else {
                alert(data.message || 'Error adding sub-subcategory.');
            }
        })
        .catch(err => {
            console.error('Error adding sub-subcategory:', err);
            alert('Server error while adding sub-subcategory.');
        });
    });

    // -------------------------------------------------------------
    // Edit & Delete Sub Sub Category Handlers
    // -------------------------------------------------------------
    const modalEditSubSubcatNameInput = document.getElementById('modalEditSubSubcatNameInput');

    btnEditSubSubcatModal?.addEventListener('click', function() {
        const subSubcatId = subSubcategorySelect.value;
        if (!subSubcatId) return;
        const currentName = subSubcategorySelect.options[subSubcategorySelect.selectedIndex]?.text || '';
        modalEditSubSubcatNameInput.value = currentName;
        const modal = new bootstrap.Modal(document.getElementById('modalEditSubSubcategory'));
        modal.show();
    });

    document.getElementById('btnSaveEditSubSubcat')?.addEventListener('click', function() {
        const subSubcatId = subSubcategorySelect.value;
        const name = modalEditSubSubcatNameInput.value.trim().toUpperCase();
        if (!subSubcatId || !name) {
            alert('Please enter a sub-subcategory name.');
            return;
        }

        const formData = new FormData();
        formData.append('name', name);

        fetch(`${baseUrl}/api/sub-subcategories/update/${subSubcatId}`, {
            method: 'POST',
            body: formData
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                if (subSubcategorySelect.selectedIndex >= 0) {
                    subSubcategorySelect.options[subSubcategorySelect.selectedIndex].text = data.name;
                }
                updateHierarchyBreadcrumb();
                refreshExistingSubSubcategories();
                bootstrap.Modal.getInstance(document.getElementById('modalEditSubSubcategory')).hide();
            } else {
                alert(data.message || 'Error updating sub-subcategory.');
            }
        })
        .catch(err => {
            console.error('Error updating sub-subcategory:', err);
            alert('Server error while updating sub-subcategory.');
        });
    });

    btnDeleteSubSubcat?.addEventListener('click', function() {
        const subSubcatId = subSubcategorySelect.value;
        if (!subSubcatId) return;
        const currentName = subSubcategorySelect.options[subSubcategorySelect.selectedIndex]?.text || 'this sub-subcategory';
        if (!confirm(`Are you sure you want to delete the sub sub category "${currentName}"?`)) {
            return;
        }

        fetch(`${baseUrl}/api/sub-subcategories/delete/${subSubcatId}`, {
            method: 'POST'
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                const opt = subSubcategorySelect.querySelector(`option[value="${subSubcatId}"]`);
                if (opt) opt.remove();
                subSubcategorySelect.value = '';
                subSubcategorySelect.dispatchEvent(new Event('change'));
                refreshExistingSubSubcategories();
            } else {
                alert(data.message || 'Error deleting sub-subcategory.');
            }
        })
        .catch(err => {
            console.error('Error deleting sub-subcategory:', err);
            alert('Server error while deleting sub-subcategory.');
        });
    });

    // -------------------------------------------------------------
    // Quick Add & Delete Size Attribute Handlers
    // -------------------------------------------------------------
    const sizeContainer = document.getElementById('sizeSelectorContainer');

    function checkEmptySizes() {
        if (!sizeContainer.querySelector('.size-chip-group')) {
            sizeContainer.innerHTML = `
                <div class="w-100 text-center py-2 text-muted small" id="emptySizePrompt">
                    <i class="bi bi-rulers fs-5 text-secondary opacity-50 d-block mb-1"></i>
                    No size attributes found. Click <strong>"+ Add Size"</strong> above to create sizes for this category.
                </div>
            `;
        }
    }

    document.getElementById('btnSaveQuickSize').addEventListener('click', function() {
        const code = document.getElementById('modalSizeCode').value.trim().toUpperCase();
        const name = document.getElementById('modalSizeName').value.trim().toUpperCase() || code;
        if (!code) {
            alert('Please enter a Size code / label (e.g. M, L, XL, 32).');
            return;
        }

        const formData = new FormData();
        formData.append('code', code);
        formData.append('name', name);

        fetch(`${baseUrl}/api/sizes/quick-add`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const emptyPrompt = document.getElementById('emptySizePrompt');
                if (emptyPrompt) emptyPrompt.remove();

                const chipGroup = document.createElement('div');
                chipGroup.className = 'btn-group btn-group-sm size-chip-group';
                chipGroup.id = `size-chip-${data.id}`;
                chipGroup.innerHTML = `
                    <label class="btn btn-outline-dark size-check-btn d-flex align-items-center gap-1" style="cursor: pointer;">
                        <input type="checkbox" name="sizes[]" class="form-check-input me-1 size-checkbox" value="${data.id}" data-code="${data.code}" data-name="${data.name}" checked>
                        <span class="fw-bold">${data.code}</span>
                        ${data.name !== data.code ? `<small class="text-muted">(${data.name})</small>` : ''}
                    </label>
                    <button type="button" class="btn btn-outline-danger btn-delete-size p-1 px-2" data-id="${data.id}" data-name="${data.code}" title="Delete size attribute">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
                sizeContainer.appendChild(chipGroup);
                bootstrap.Modal.getInstance(document.getElementById('modalAddSize')).hide();
                document.getElementById('modalSizeCode').value = '';
                document.getElementById('modalSizeName').value = '';
            } else {
                alert(data.message || 'Error adding size.');
            }
        })
        .catch(err => {
            console.error('Error adding size:', err);
            alert('Server error while adding size.');
        });
    });

    // Delegated delete for sizes
    sizeContainer.addEventListener('click', function(e) {
        const delBtn = e.target.closest('.btn-delete-size');
        if (!delBtn) return;
        const sizeId = delBtn.dataset.id;
        const sizeName = delBtn.dataset.name;
        if (!confirm(`Are you sure you want to delete size "${sizeName}"?`)) return;

        fetch(`${baseUrl}/api/sizes/delete/${sizeId}`, {
            method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const chip = document.getElementById(`size-chip-${sizeId}`);
                if (chip) chip.remove();
                checkEmptySizes();
            } else {
                alert('Error deleting size.');
            }
        });
    });

    // -------------------------------------------------------------
    // Quick Add & Delete Color Attribute Handlers
    // -------------------------------------------------------------
    const colorContainer = document.getElementById('colorSelectorContainer');
    const modalColorPicker = document.getElementById('modalColorPicker');
    const modalColorHex = document.getElementById('modalColorHex');

    modalColorPicker.addEventListener('input', function() {
        modalColorHex.value = this.value.toUpperCase();
    });
    modalColorHex.addEventListener('input', function() {
        modalColorPicker.value = this.value;
    });

    function checkEmptyColors() {
        if (!colorContainer.querySelector('.color-chip-group')) {
            colorContainer.innerHTML = `
                <div class="w-100 text-center py-2 text-muted small" id="emptyColorPrompt">
                    <i class="bi bi-palette fs-5 text-secondary opacity-50 d-block mb-1"></i>
                    No color attributes found. Click <strong>"+ Add Color"</strong> above to create colors for this category.
                </div>
            `;
        }
    }

    document.getElementById('btnSaveQuickColor').addEventListener('click', function() {
        const name = document.getElementById('modalColorName').value.trim().toUpperCase();
        const hex = modalColorHex.value.trim().toUpperCase() || '#000000';
        if (!name) {
            alert('Please enter a color name.');
            return;
        }

        const formData = new FormData();
        formData.append('name', name);
        formData.append('hex_code', hex);

        fetch(`${baseUrl}/api/colors/quick-add`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const emptyPrompt = document.getElementById('emptyColorPrompt');
                if (emptyPrompt) emptyPrompt.remove();

                const chipGroup = document.createElement('div');
                chipGroup.className = 'btn-group btn-group-sm color-chip-group';
                chipGroup.id = `color-chip-${data.id}`;
                chipGroup.innerHTML = `
                    <label class="btn btn-outline-secondary color-check-btn d-flex align-items-center gap-2" style="cursor: pointer;">
                        <input type="checkbox" name="colors[]" class="form-check-input me-1 color-checkbox" value="${data.id}" data-name="${data.name}" data-hex="${data.hex_code}" checked>
                        <span style="display:inline-block; width:14px; height:14px; border-radius:50%; background-color:${data.hex_code}; border:1px solid #ccc;"></span>
                        <span>${data.name}</span>
                    </label>
                    <button type="button" class="btn btn-outline-danger btn-delete-color p-1 px-2" data-id="${data.id}" data-name="${data.name}" title="Delete color attribute">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
                colorContainer.appendChild(chipGroup);
                bootstrap.Modal.getInstance(document.getElementById('modalAddColor')).hide();
                document.getElementById('modalColorName').value = '';
            } else {
                alert(data.message || 'Error adding color.');
            }
        })
        .catch(err => {
            console.error('Error adding color:', err);
            alert('Server error while adding color.');
        });
    });

    // Delegated delete for colors
    colorContainer.addEventListener('click', function(e) {
        const delBtn = e.target.closest('.btn-delete-color');
        if (!delBtn) return;
        const colorId = delBtn.dataset.id;
        const colorName = delBtn.dataset.name;
        if (!confirm(`Are you sure you want to delete color "${colorName}"?`)) return;

        fetch(`${baseUrl}/api/colors/delete/${colorId}`, {
            method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const chip = document.getElementById(`color-chip-${colorId}`);
                if (chip) chip.remove();
                checkEmptyColors();
            } else {
                alert('Error deleting color.');
            }
        });
    });

    // -------------------------------------------------------------
    // Quick Selectors for Sizes & Colors
    // -------------------------------------------------------------
    document.getElementById('btnSelectAllSizes')?.addEventListener('click', function() {
        document.querySelectorAll('.size-checkbox').forEach(cb => cb.checked = true);
    });

    document.getElementById('btnClearSizes')?.addEventListener('click', function() {
        document.querySelectorAll('.size-checkbox').forEach(cb => cb.checked = false);
    });

    document.getElementById('btnSelectAllColors')?.addEventListener('click', function() {
        document.querySelectorAll('.color-checkbox').forEach(cb => cb.checked = true);
    });

    document.getElementById('btnClearColors')?.addEventListener('click', function() {
        document.querySelectorAll('.color-checkbox').forEach(cb => cb.checked = false);
    });

    // Prevent submission if Whole Code for QR Code is a duplicate
    const editCatalogForm = document.getElementById('editCatalogForm');
    if (editCatalogForm) {
        editCatalogForm.addEventListener('submit', function(e) {
            if (isQrCodeDuplicate) {
                e.preventDefault();
                const qrVal = document.getElementById('qr_code_prefix')?.value || '';
                const prodName = qrDuplicateDetails?.product?.name || 'an existing style';
                alert(`⚠️ Already have this code!\n\nThe Whole Code for QR Code "${qrVal}" is already in use by Style "${prodName}".\n\nPlease customize this code before saving.`);
                document.getElementById('qr_code_prefix')?.focus();
                return false;
            }
        });
    }
});
</script>
