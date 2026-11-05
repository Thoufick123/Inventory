<?php
// views/warehouse/sellers/add_products.php
// Supplier Registration Wizard — Phase 2: Category Tile Auto-Fill & Product Entry
// Enforces mandatory: HSN Code, Weight, MRP, Selling Price, Supplier Bought Price, Quantity.
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-boxes me-2 text-primary"></i>Phase 2: Add Products for Supplier</h2>
        <p class="text-muted small mb-0">Select pre-set categories to auto-fill hierarchy, then enter product pricing, HSN &amp; weights.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('warehouse/sellers/view/' . $seller['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Supplier Profile
        </a>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2-PHASE WIZARD STEPPER -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <!-- Phase 1 Step Indicator (Completed) -->
            <div class="col-md-6">
                <div class="d-flex align-items-center p-2.5 rounded-3 bg-success-subtle border border-success">
                    <div class="badge rounded-circle bg-success text-white me-3 fs-6 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-check-lg fs-5"></i>
                    </div>
                    <div>
                        <span class="badge bg-success text-white mb-0.5">COMPLETED</span>
                        <div class="fw-bold text-dark">1. Supplier Details</div>
                        <small class="text-muted"><?= e($seller['name']) ?><?= !empty($seller['company']) ? ' (' . e($seller['company']) . ')' : '' ?></small>
                    </div>
                </div>
            </div>

            <!-- Phase 2 Step Indicator (Active) -->
            <div class="col-md-6">
                <div class="d-flex align-items-center p-2.5 rounded-3 bg-primary-subtle border border-primary">
                    <div class="badge rounded-circle bg-primary text-white me-3 fs-6 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        2
                    </div>
                    <div>
                        <span class="badge bg-primary text-white mb-0.5">CURRENT STEP</span>
                        <div class="fw-bold text-dark">2. Product Entry</div>
                        <small class="text-muted">Auto-fill category hierarchy &amp; pricing</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Supplier & Settlement Summary Ribbon -->
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4 border-start border-4 border-primary">
    <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-2.5 bg-primary-subtle text-primary rounded-circle">
                <i class="bi bi-shop fs-4"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0"><?= e($seller['company'] ?: $seller['name']) ?></h5>
                <div class="text-muted small d-flex align-items-center gap-3 flex-wrap mt-0.5">
                    <?php if (!empty($seller['name']) && $seller['company']): ?>
                        <span><i class="bi bi-person me-1"></i>Contact: <?= e($seller['name']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($seller['phone'])): ?>
                        <span><i class="bi bi-telephone me-1"></i><?= e($seller['phone']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($seller['gst_number'])): ?>
                        <span class="font-monospace"><i class="bi bi-card-text me-1"></i>GSTIN: <?= e($seller['gst_number']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($billSetup['brand_name'])): ?>
                        <span class="badge bg-primary text-white"><i class="bi bi-tag-fill me-1"></i>Brand: <?= e($billSetup['brand_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($seller['city'])): ?>
                        <span><i class="bi bi-geo-alt me-1"></i><?= e($seller['city']) ?>, <?= e($seller['state'] ?? 'Tamil Nadu') ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="text-end me-2 d-none d-md-block">
                <span class="badge bg-light text-dark border">
                    <i class="bi bi-building me-1 text-primary"></i>Inward to: 
                    <?php 
                        $destWhName = 'Central Warehouse';
                        $destWhId = $billSetup['warehouse_id'] ?? 1;
                        if (!empty($warehouses)) {
                            foreach ($warehouses as $w) {
                                if ($w['id'] == $destWhId) { $destWhName = $w['name']; break; }
                            }
                        }
                        echo e($destWhName);
                    ?>
                </span>
                <?php if (!empty($billSetup['bill_number'])): ?>
                    <span class="badge bg-light text-dark border font-monospace ms-1">Bill: <?= e($billSetup['bill_number']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- EXISTING SUPPLIER PRODUCTS (READ ONLY) -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-box-seam text-primary me-2"></i>Existing Supplier Products
            </h5>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 fw-bold">
                <?= count($existingSupplierProducts ?? []) ?> Product(s)
            </span>
        </div>
        <span class="text-muted small"><i class="bi bi-eye me-1"></i>View only — these items are not included in the new inward bill.</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover align-middle mb-0 text-nowrap">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Product Type</th>
                    <th>Variant</th>
                    <th>SKU</th>
                    <th>HSN</th>
                    <th class="text-end">Weight (kg)</th>
                    <th class="text-end">MRP (₹)</th>
                    <th class="text-end">Selling Price (₹)</th>
                    <th class="text-end">Supplier Cost (₹)</th>
                    <th class="text-end">Available Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($existingSupplierProducts)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">No existing products are linked to this supplier.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($existingSupplierProducts as $index => $product): ?>
                        <?php
                            $categoryPath = array_filter([
                                $product['category_name'] ?? '',
                                $product['subcategory_name'] ?? '',
                                $product['sub_subcategory_name'] ?? '',
                            ]);
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td class="fw-semibold"><?= e($product['product_name'] ?? '') ?></td>
                            <td><?= e(implode(' › ', $categoryPath) ?: '—') ?></td>
                            <?php $productType = strtoupper((string)($product['product_type'] ?? 'NORMAL')); ?>
                            <td>
                                <span class="badge rounded-pill <?= $productType === 'SERIES' ? 'bg-info-subtle text-info-emphasis' : ($productType === 'COMPLEMENTARY' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-primary-subtle text-primary') ?>">
                                    <?= $productType === 'SERIES' ? 'Series Pack' : ($productType === 'COMPLEMENTARY' ? 'Complementary' : 'Individual Piece') ?>
                                </span>
                            </td>
                            <td><?= e($product['variant_name'] ?? 'Standard') ?></td>
                            <td class="font-monospace"><?= e($product['sku'] ?? '') ?: '—' ?></td>
                            <td class="font-monospace"><?= e($product['hsn_code'] ?? '') ?: '—' ?></td>
                            <td class="text-end"><?= number_format((float)($product['weight'] ?? 0), 3) ?></td>
                            <td class="text-end">₹<?= number_format((float)($product['mrp'] ?? 0), 2) ?></td>
                            <td class="text-end">₹<?= number_format((float)($product['selling_price'] ?? 0), 2) ?></td>
                            <td class="text-end">₹<?= number_format((float)($product['purchase_price'] ?? 0), 2) ?></td>
                            <td class="text-end fw-semibold"><?= number_format((float)($product['available_stock'] ?? 0), 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. CATEGORY & ATTRIBUTE VARIANT SELECTION (Full Width Streamlined) -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="card-title mb-0 fw-bold"><i class="bi bi-grid-fill text-primary me-2"></i>1. Select Category &amp; Click Attributes to Generate Inward Products</h6>
            <small class="text-muted">Pick a category and click attribute options (e.g. S, M, L) to directly load products into the Inward List table below</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Active Hierarchy Breadcrumb -->
            <div class="d-flex align-items-center gap-2 bg-light px-2.5 py-1 rounded border">
                <span class="badge bg-primary text-white">CATEGORY PATH:</span>
                <span id="activeHierarchyDisplay" class="fw-bold text-dark small">
                    <span class="text-muted">Please select a category below</span>
                </span>
                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1.5" onclick="resetCategorySelection()" title="Clear Selection">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <a href="<?= url('warehouse/catalog?tab=category') ?>" target="_blank" class="btn btn-xs btn-outline-primary" title="Configure Categories & Attributes">
                <i class="bi bi-gear"></i> Pre-sets
            </a>
        </div>
    </div>
    <div class="card-body p-3">
        <!-- Hidden Active Category Tracking -->
        <input type="hidden" id="entryCatId" value="">
        <input type="hidden" id="entryCatName" value="">
        <input type="hidden" id="entrySubId" value="">
        <input type="hidden" id="entrySubName" value="">
        <input type="hidden" id="entrySubSubId" value="">
        <input type="hidden" id="entrySubSubName" value="">

        <!-- Step 1: Main Category Tiles -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark mb-1.5">Main Category:</label>
            <div class="d-flex flex-wrap gap-2" id="mainCategoryTiles">
                <?php if (empty($categories)): ?>
                    <div class="alert alert-warning small p-2 w-100 mb-0">No categories found. Please add categories in Catalog Manager.</div>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary cat-tile rounded-3 px-3 py-1.5 text-start fw-semibold d-flex align-items-center gap-2"
                                data-cat-id="<?= $cat['id'] ?>"
                                data-cat-name="<?= e($cat['name']) ?>"
                                onclick="selectCategoryTile(this, <?= $cat['id'] ?>, '<?= e(addslashes($cat['name'])) ?>')">
                            <i class="bi bi-folder-fill text-warning"></i>
                            <span><?= e($cat['name']) ?></span>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 2: Subcategories Container (Dynamic) -->
        <div class="mb-3 d-none" id="subcategoriesSection">
            <label class="form-label small fw-bold text-dark mb-1.5">
                <i class="bi bi-arrow-return-right me-1 text-primary"></i>Subcategory for: <span id="labelSelectedCatName" class="text-primary fw-bold"></span>
            </label>
            <div class="d-flex flex-wrap gap-2" id="subcategoryTiles">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Step 3: Sub-Subcategories Container (Dynamic) -->
        <div class="mb-3 d-none" id="subSubcategoriesSection">
            <label class="form-label small fw-bold text-dark mb-1.5">
                <i class="bi bi-arrow-return-right me-1 text-info"></i>Sub-Subcategory for: <span id="labelSelectedSubcatName" class="text-info fw-bold"></span>
            </label>
            <div class="d-flex flex-wrap gap-2" id="subSubcategoryTiles">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Pre-set Product Templates Quick Pick (When Sub-Subcategory has templates) -->
        <div class="d-none mb-3 p-2.5 bg-success-subtle border border-success-subtle rounded-3" id="templateQuickPickSection">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-bold text-success"><i class="bi bi-magic me-1"></i>Pre-set Product Templates (Click to add directly to table)</span>
                <span class="badge bg-success text-white fs-8">1-Click Add</span>
            </div>
            <div class="d-flex flex-wrap gap-2" id="templateButtonsContainer">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Pre-set Category Attributes & Variant Generator -->
        <div class="p-3 bg-light rounded-3 border d-none" id="attributesPreviewSection">
            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white"><i class="bi bi-sliders me-1"></i>Pre-set Attributes &amp; Variants:</span>
                    <span class="text-muted small">Click attribute chips across categories to generate matrix variants (e.g. S-BROWN, S-RED).</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-xs btn-primary fw-bold px-2.5 py-1" onclick="selectAllAttributeVariants(true)">
                        <i class="bi bi-check-all me-1"></i>Select All Variants
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary fw-semibold px-2.5 py-1" onclick="selectAllAttributeVariants(false)">
                        <i class="bi bi-x-circle me-1"></i>Clear All
                    </button>
                </div>
            </div>
            <div class="d-flex flex-column gap-2" id="attributesBadgeContainer">
                <!-- Populated by JS -->
            </div>

            <!-- Generated Matrix Combinations Bar -->
            <div class="mt-3 pt-3 border-top d-none" id="matrixCombinationsSection">
                <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small fw-bold text-dark">
                            <i class="bi bi-grid-3x3-gap-fill text-success me-1"></i>Active Matrix Combinations:
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace px-2 py-0.5 fw-bold" id="matrixCountBadge">0 Variants</span>
                    </div>
                    <span class="text-muted fs-8">Click a combination badge below to include / exclude it</span>
                </div>
                <div class="d-flex flex-wrap gap-1.5" id="matrixChipsContainer">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>

        <!-- Direct Add Category Button (If category has no attributes) -->
        <div class="d-none mt-2 pt-2 border-top text-end" id="singleCategoryAddContainer">
            <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="addSingleCategoryProduct()">
                <i class="bi bi-plus-circle me-1"></i> Add Category Product to Inward List
            </button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. ADDED PRODUCTS INWARD LIST (DIRECT INLINE EDITABLE SPREADSHEET) -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-cart-check-fill text-success me-2"></i>Added Products Inward List
            </h5>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold" id="badgeItemCount">
                0 Product(s)
            </span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="text-muted small">
                <i class="bi bi-info-circle text-primary me-1"></i>Only products added above are included in this inward bill; existing supplier products are not re-added.
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- ⚡ Quick Fill All Variants Bar -->
        <div class="p-3 border-bottom d-none" id="quickFillBar" style="background-color: #fbfcfe !important;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white px-2 py-1"><i class="bi bi-lightning-charge-fill me-1"></i>Quick Fill All Variants</span>
                    <span class="small text-muted">Fill details once below to apply across all variants.</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-xs btn-primary fw-bold shadow-xs px-2.5" onclick="applyQuickFillToAll()">
                        <i class="bi bi-check2-all me-1"></i>Apply to All Variants
                    </button>
                </div>
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-xl-2 col-lg-3 col-md-4 col-12">
                    <label class="form-label fs-8 text-muted mb-1 fw-bold">Product Name:</label>
                    <input type="text" class="form-control form-control-sm fw-semibold text-uppercase" id="qfProductName" placeholder="e.g. Cotton Shirt" oninput="this.value=this.value.toUpperCase()">
                </div>
                <div class="col-xl-2 col-lg-2 col-md-4 col-6">
                    <label class="form-label fs-8 text-muted mb-1 fw-bold">Category Type:</label>
                    <select class="form-select form-select-sm fw-semibold" id="qfCategoryType" onchange="handleQfCategoryTypeChange(this.value)">
                        <option value="NORMAL" selected>Normal</option>
                        <option value="COMPLEMENTARY">Complementary</option>
                        <option value="SERIES">Series</option>
                    </select>
                    <!-- Quick Fill Range (Shown if Series or Complementary) -->
                    <div id="qfSeriesRangeBox" class="d-none mt-1 p-1 bg-white rounded border shadow-xs">
                        <div class="d-flex align-items-center gap-1">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text py-0 px-1 font-monospace fs-8 text-muted bg-light">From</span>
                                <input type="number" min="1" step="1" id="qfSeriesFrom" class="form-control form-control-sm text-center px-1 font-monospace fw-bold" value="1" placeholder="1" disabled>
                            </div>
                            <span class="text-muted small">&rarr;</span>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text py-0 px-1 font-monospace fs-8 text-muted bg-light">End</span>
                                <input type="number" min="1" step="1" id="qfSeriesEnd" class="form-control form-control-sm text-center px-1 font-monospace fw-bold" value="1" placeholder="1" disabled>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-1 col-lg-1 col-md-4 col-6">
                    <label class="form-label fs-8 text-muted mb-1 fw-bold">HSN Code:</label>
                    <input type="text" class="form-control form-control-sm font-monospace text-center fw-bold" id="qfHsn" placeholder="e.g. 4566">
                </div>
                <div class="col-xl-1 col-lg-2 col-md-3 col-6">
                    <label class="form-label fs-8 text-muted mb-1 fw-bold">Weight (kg):</label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-sm text-end font-monospace" id="qfWeight" placeholder="0.500">
                </div>
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label fs-8 text-muted mb-1 fw-bold">Base MRP (₹):</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white px-1.5 fs-8">₹</span>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end text-success fw-bold" id="qfMrp" placeholder="250.00" oninput="validateQuickFillPricing()">
                    </div>
                </div>
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label fs-8 text-muted mb-1 fw-bold">Selling Price (₹):</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white px-1.5 fs-8">₹</span>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end text-primary fw-bold" id="qfSp" placeholder="200.00" oninput="validateQuickFillPricing()">
                    </div>
                    <div id="qfPriceWarn" class="text-danger fs-8 fw-bold mt-1 d-none"></div>
                </div>
                <div class="col-xl-1 col-lg-2 col-md-3 col-6">
                    <label class="form-label fs-8 text-muted mb-1 fw-bold">Supplier Cost (₹):</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white px-1.5 fs-8">₹</span>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end text-dark fw-bold" id="qfCost" placeholder="666.00" oninput="validateQuickFillPricing()">
                    </div>
                </div>
                <div class="col-xl-1 col-lg-1 col-md-3 col-6">
                    <label class="form-label fs-8 text-muted mb-1 fw-bold">Qty / Var:</label>
                    <input type="number" min="0" step="1" class="form-control form-control-sm text-center fw-bold fs-7" id="qfQty" placeholder="15" oninput="handleQfQtyInput(this.value)">
                </div>
            </div>
            <div id="quickFillSuccessNotice" class="alert alert-success alert-dismissible fade show py-1.5 px-3 mb-0 mt-2 fs-8 fw-semibold d-none" role="alert">
                <i class="bi bi-check-circle-fill me-1"></i><span id="quickFillNoticeText">All variants updated successfully.</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" id="addedProductsTable">
                <thead class="table-light text-nowrap">
                    <tr>
                        <th style="width: 35px;" class="text-center">#</th>
                        <th style="min-width: 140px;">Category Hierarchy</th>
                        <th style="min-width: 195px;">Category Type</th>
                        <th style="min-width: 220px;">Product Name <span class="text-danger">*</span></th>
                        <th style="width: 105px;" class="text-center">HSN <span class="text-danger">*</span></th>
                        <th style="width: 100px;" class="text-end">Weight (kg) <span class="text-danger">*</span></th>
                        <th style="min-width: 115px;" class="text-center">Variant / Size</th>
                        <th style="width: 125px;" class="text-end">Base MRP (₹) <span class="text-danger">*</span></th>
                        <th style="width: 125px;" class="text-end">Selling Price (₹) <span class="text-danger">*</span></th>
                        <th style="width: 120px;" class="text-end">Supplier Cost (₹) <span class="text-danger">*</span></th>
                        <th style="width: 80px;" class="text-center">Qty <span class="text-danger">*</span></th>
                        <th style="width: 130px;" class="text-end">Line Retail Value (₹)</th>
                        <th style="width: 50px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="addedProductsTableBody">
                    <tr id="emptyProductListNotice">
                        <td colspan="13" class="text-center py-5 text-muted">
                            <i class="bi bi-cart-x fs-1 opacity-50 d-block mb-2"></i>
                            <div class="fw-semibold">No Products in Inward List Yet</div>
                            <div class="small">Choose a category and click attribute options above to directly load products here.</div>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="5" class="text-end">Retail Totals:</td>
                        <td class="text-end font-monospace" id="footerTotalWeight">0.000 kg</td>
                        <td colspan="4"></td>
                        <td class="text-center fs-7" id="footerTotalQty">0 pcs</td>
                        <td class="text-end text-primary fs-6" id="footerGrandTotal">₹0.00</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="11" class="text-end">Supplier Product Total (Cost + GST):</td>
                        <td class="text-end text-success fs-6" id="footerSupplierProductTotal">₹0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- BOTTOM STICKY ACTION BAR -->
<!-- ========================================================================= -->
<form id="finalSubmitForm" method="POST" action="<?= url('warehouse/sellers/' . $seller['id'] . '/store-products') ?>">
    <?= CSRF::field() ?>
    <input type="hidden" name="items" id="jsonProductsInput" value="[]">
    <input type="hidden" name="warehouse_id" value="<?= e($billSetup['warehouse_id'] ?? 1) ?>">
    <input type="hidden" name="order_date" value="<?= e($billSetup['order_date'] ?? date('Y-m-d')) ?>">
    <input type="hidden" name="expected_date" value="<?= e($billSetup['expected_date'] ?? date('Y-m-d')) ?>">
    <input type="hidden" name="payment_method" value="<?= e($billSetup['payment_method'] ?? 'CASH') ?>">
    <input type="hidden" name="payment_status" value="<?= e($billSetup['payment_status'] ?? 'PAID') ?>">
    <input type="hidden" name="gst_type" value="<?= e($billSetup['gst_type'] ?? 'CGST_SGST') ?>">
    <input type="hidden" name="bill_number" value="<?= e($billSetup['bill_number'] ?? '') ?>">
    <input type="hidden" name="notes" value="<?= e($billSetup['notes'] ?? '') ?>">

    <div class="card border-0 shadow-lg rounded-3 bg-white border-top border-3 border-success">
        <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= url('warehouse/sellers/create') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Step 1
                </a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-primary px-5 py-2.5 fw-bold shadow" id="btnSaveAllProducts" onclick="submitFinalProducts()" disabled>
                    <i class="bi bi-qr-code me-1"></i> Save Products &amp; Generate QR Labels &rarr;
                </button>
            </div>
        </div>
    </div>
</form>

<!-- ========================================================================= -->
<!-- JAVASCRIPT: PHASE 2 DIRECT TABLE GENERATION & INLINE EDITING -->
<!-- ========================================================================= -->
<script>
const CATEGORIES_DATA = <?= json_encode($categories ?? [], JSON_UNESCAPED_UNICODE) ?>;
const ATTRIBUTES_DATA = <?= json_encode($allAttributes ?? [], JSON_UNESCAPED_UNICODE) ?>;
const DEFAULT_PRODUCTS_DATA = <?= json_encode($allDefaultProducts ?? [], JSON_UNESCAPED_UNICODE) ?>;
const DEFAULT_BRAND_ID = <?= json_encode(!empty($billSetup['brand_id']) ? (int)$billSetup['brand_id'] : (!empty($seller['brand_id']) ? (int)$seller['brand_id'] : null)) ?>;
const DEFAULT_BRAND_NAME = <?= json_encode(!empty($billSetup['brand_name']) ? $billSetup['brand_name'] : '') ?>;
// Array of active inward products
let addedProductsList = [];

// Track current available attributes for active category
let currentCategoryAttributes = [];
// Track selected options per attribute for matrix generation, e.g. { 'SIZE': ['S', 'L'], 'COLOR': ['BROWN', 'RED'] }
let selectedAttributeOptions = {};
// Track explicitly excluded matrix combo keys, e.g. 'COLOR:RED|SIZE:S'
let excludedComboKeys = new Set();

document.addEventListener('DOMContentLoaded', function() {
    renderAddedProductsTable();
});

// -----------------------------------------------------------------------------
// 1. TIERED CATEGORY SELECTION & ATTRIBUTE LOADING
// -----------------------------------------------------------------------------
function selectCategoryTile(btn, catId, catName) {
    const prevCatId = document.getElementById('entryCatId').value;
    if (String(prevCatId) !== String(catId)) {
        selectedAttributeOptions = {};
        excludedComboKeys.clear();
    }

    document.querySelectorAll('#mainCategoryTiles .cat-tile').forEach(t => {
        t.classList.remove('btn-primary', 'text-white');
        t.classList.add('btn-outline-primary');
    });
    btn.classList.remove('btn-outline-primary');
    btn.classList.add('btn-primary', 'text-white');

    document.getElementById('entryCatId').value = catId;
    document.getElementById('entryCatName').value = catName;
    document.getElementById('entrySubId').value = '';
    document.getElementById('entrySubName').value = '';
    document.getElementById('entrySubSubId').value = '';
    document.getElementById('entrySubSubName').value = '';

    updateHierarchyDisplay();
    displayCategoryAttributes(catId, null, null);

    const catObj = CATEGORIES_DATA.find(c => String(c.id) === String(catId));
    const subSection = document.getElementById('subcategoriesSection');
    const subContainer = document.getElementById('subcategoryTiles');
    const subSubSection = document.getElementById('subSubcategoriesSection');
    const templateSection = document.getElementById('templateQuickPickSection');

    subSubSection.classList.add('d-none');
    templateSection.classList.add('d-none');

    if (catObj && catObj.subcategories && catObj.subcategories.length > 0) {
        document.getElementById('labelSelectedCatName').textContent = catName;
        let html = '';
        catObj.subcategories.forEach(sc => {
            html += `
                <button type="button" 
                        class="btn btn-sm btn-outline-info subcat-tile rounded-3 px-3 py-1.5 text-start fw-semibold"
                        data-sub-id="${sc.id}"
                        data-sub-name="${escapeHtml(sc.name)}"
                        onclick="selectSubcategoryTile(this, ${sc.id}, '${escapeHtml(sc.name)}')">
                    <i class="bi bi-folder2 text-primary me-1"></i>${escapeHtml(sc.name)}
                </button>
            `;
        });
        subContainer.innerHTML = html;
        subSection.classList.remove('d-none');
    } else {
        subSection.classList.add('d-none');
    }
}

function selectSubcategoryTile(btn, subId, subName) {
    document.querySelectorAll('#subcategoryTiles .subcat-tile').forEach(t => {
        t.classList.remove('btn-info', 'text-white');
        t.classList.add('btn-outline-info');
    });
    btn.classList.remove('btn-outline-info');
    btn.classList.add('btn-info', 'text-white');

    document.getElementById('entrySubId').value = subId;
    document.getElementById('entrySubName').value = subName;
    document.getElementById('entrySubSubId').value = '';
    document.getElementById('entrySubSubName').value = '';

    updateHierarchyDisplay();
    displayCategoryAttributes(document.getElementById('entryCatId').value, subId, null);

    const catId = document.getElementById('entryCatId').value;
    const catObj = CATEGORIES_DATA.find(c => String(c.id) === String(catId));
    const subObj = catObj && catObj.subcategories ? catObj.subcategories.find(s => String(s.id) === String(subId)) : null;

    const subSubSection = document.getElementById('subSubcategoriesSection');
    const subSubContainer = document.getElementById('subSubcategoryTiles');
    const templateSection = document.getElementById('templateQuickPickSection');
    templateSection.classList.add('d-none');

    if (subObj && subObj.sub_subcategories && subObj.sub_subcategories.length > 0) {
        document.getElementById('labelSelectedSubcatName').textContent = subName;
        let html = '';
        subObj.sub_subcategories.forEach(ssc => {
            html += `
                <button type="button" 
                        class="btn btn-sm btn-outline-dark subsub-tile rounded-3 px-3 py-1.5 text-start fw-semibold"
                        data-ssc-id="${ssc.id}"
                        data-ssc-name="${escapeHtml(ssc.name)}"
                        onclick="selectSubSubcategoryTile(this, ${ssc.id}, '${escapeHtml(ssc.name)}')">
                    <i class="bi bi-tag-fill text-info me-1"></i>${escapeHtml(ssc.name)}
                </button>
            `;
        });
        subSubContainer.innerHTML = html;
        subSubSection.classList.remove('d-none');
    } else {
        subSubSection.classList.add('d-none');
    }
}

function selectSubSubcategoryTile(btn, sscId, sscName) {
    document.querySelectorAll('#subSubcategoryTiles .subsub-tile').forEach(t => {
        t.classList.remove('btn-dark', 'text-white');
        t.classList.add('btn-outline-dark');
    });
    btn.classList.remove('btn-outline-dark');
    btn.classList.add('btn-dark', 'text-white');

    document.getElementById('entrySubSubId').value = sscId;
    document.getElementById('entrySubSubName').value = sscName;

    updateHierarchyDisplay();
    const catId = document.getElementById('entryCatId').value;
    const subId = document.getElementById('entrySubId').value;
    displayCategoryAttributes(catId, subId, sscId);

    // Show templates if any
    displayProductTemplates(sscId, sscName);
}

function updateHierarchyDisplay() {
    const cat = document.getElementById('entryCatName').value;
    const sub = document.getElementById('entrySubName').value;
    const ssc = document.getElementById('entrySubSubName').value;

    const display = document.getElementById('activeHierarchyDisplay');
    if (!cat) {
        display.innerHTML = `<span class="text-muted">Please select a category below</span>`;
        return;
    }

    let str = `<span class="badge bg-primary text-white">${escapeHtml(cat)}</span>`;
    if (sub) {
        str += ` <i class="bi bi-chevron-right text-muted fs-8"></i> <span class="badge bg-info text-dark">${escapeHtml(sub)}</span>`;
    }
    if (ssc) {
        str += ` <i class="bi bi-chevron-right text-muted fs-8"></i> <span class="badge bg-dark text-white">${escapeHtml(ssc)}</span>`;
    }
    display.innerHTML = str;
}

function resetCategorySelection() {
    selectedAttributeOptions = {};
    excludedComboKeys.clear();

    const qfNameReset = document.getElementById('qfProductName');
    if (qfNameReset) qfNameReset.value = '';

    document.querySelectorAll('#mainCategoryTiles .cat-tile').forEach(t => {
        t.classList.remove('btn-primary', 'text-white');
        t.classList.add('btn-outline-primary');
    });
    document.getElementById('subcategoriesSection').classList.add('d-none');
    document.getElementById('subSubcategoriesSection').classList.add('d-none');
    document.getElementById('templateQuickPickSection').classList.add('d-none');
    document.getElementById('attributesPreviewSection').classList.add('d-none');
    document.getElementById('singleCategoryAddContainer').classList.add('d-none');

    document.getElementById('entryCatId').value = '';
    document.getElementById('entryCatName').value = '';
    document.getElementById('entrySubId').value = '';
    document.getElementById('entrySubName').value = '';
    document.getElementById('entrySubSubId').value = '';
    document.getElementById('entrySubSubName').value = '';
    updateHierarchyDisplay();
}

function displayProductTemplates(sscId, sscName) {
    const section = document.getElementById('templateQuickPickSection');
    const container = document.getElementById('templateButtonsContainer');

    const templates = DEFAULT_PRODUCTS_DATA.filter(p => String(p.sub_subcategory_id) === String(sscId));
    if (templates && templates.length > 0) {
        let html = '';
        templates.forEach(t => {
            html += `
                <button type="button" class="btn btn-sm btn-light border text-dark fw-bold text-start p-2 shadow-xs" onclick='addTemplateProduct(${JSON.stringify(t)})'>
                    <div class="d-flex align-items-center gap-1.5">
                        <i class="bi bi-magic text-success"></i>
                        <span>${escapeHtml(t.product_template_name)}</span>
                    </div>
                    <div class="text-muted fs-8 mt-1">
                        ${t.hsn_code ? `<span class="badge bg-secondary-subtle text-secondary me-1">HSN: ${escapeHtml(t.hsn_code)}</span>` : ''}
                        ${t.default_weight_kg ? `<span class="badge bg-info-subtle text-info me-1">${t.default_weight_kg} kg</span>` : ''}
                    </div>
                </button>
            `;
        });
        container.innerHTML = html;
        section.classList.remove('d-none');
    } else {
        section.classList.add('d-none');
    }
}

// -----------------------------------------------------------------------------
// 2. MATRIX VARIANT GENERATION & ATTRIBUTE CHIP CONTROLS
// -----------------------------------------------------------------------------
function displayCategoryAttributes(catId, subId, sscId) {
    const previewSection = document.getElementById('attributesPreviewSection');
    const badgeContainer = document.getElementById('attributesBadgeContainer');
    const singleAddBtn = document.getElementById('singleCategoryAddContainer');

    const matchingAttrs = ATTRIBUTES_DATA.filter(a => {
        if (String(a.category_id) !== String(catId)) return false;
        if (a.subcategory_id && (!subId || String(a.subcategory_id) !== String(subId))) return false;
        if (a.sub_subcategory_id && (!sscId || String(a.sub_subcategory_id) !== String(sscId))) return false;
        return true;
    });

    currentCategoryAttributes = matchingAttrs;

    if (matchingAttrs.length > 0) {
        let leftHtml = '';

        matchingAttrs.forEach(a => {
            const attrName = (a.attr_name || '').trim();
            const attrKey = attrName.toUpperCase();
            const rawOpts = a.attr_options || '';
            const opts = rawOpts ? rawOpts.split(',').map(o => o.trim()).filter(Boolean) : [];

            if (!selectedAttributeOptions[attrKey]) {
                selectedAttributeOptions[attrKey] = [];
            }

            leftHtml += `
                <div class="p-2.5 bg-white rounded border shadow-xs">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold text-primary small text-uppercase">
                            <i class="bi bi-tag-fill me-1"></i>${escapeHtml(attrName)}
                        </span>
                        <span class="badge bg-light text-muted border">${opts.length > 0 ? opts.length + ' variants' : a.attr_type}</span>
                    </div>
                    ${opts.length > 0 ? `
                        <div class="d-flex flex-wrap gap-2">
                            ${opts.map(opt => {
                                const cleanOpt = opt.toUpperCase();
                                const isSelected = selectedAttributeOptions[attrKey] && selectedAttributeOptions[attrKey].includes(cleanOpt);
                                return `
                                    <button type="button" 
                                            class="btn btn-sm ${isSelected ? 'btn-primary text-white' : 'btn-outline-primary'} attr-opt-chip fw-bold px-3 py-1.5 shadow-xs" 
                                            data-cat-id="${catId}"
                                            data-attr="${escapeHtml(attrKey)}" 
                                            data-val="${escapeHtml(cleanOpt)}" 
                                            onclick="toggleAttributeOption('${escapeHtml(attrKey)}', '${escapeHtml(cleanOpt)}')">
                                        <i class="bi ${isSelected ? 'bi-check-circle-fill me-1' : 'bi-plus-circle me-1'}"></i>${escapeHtml(cleanOpt)}
                                    </button>
                                `;
                            }).join('')}
                        </div>
                    ` : `<span class="text-muted small">${a.attr_type}</span>`}
                </div>
            `;
        });

        badgeContainer.innerHTML = leftHtml;
        previewSection.classList.remove('d-none');
        if (singleAddBtn) singleAddBtn.classList.add('d-none');
        renderMatrixSection();
    } else {
        previewSection.classList.add('d-none');
        if (singleAddBtn) singleAddBtn.classList.remove('d-none');
    }
}

function toggleAttributeOption(attrKey, optVal) {
    const cleanKey = attrKey.trim().toUpperCase();
    const cleanVal = optVal.trim().toUpperCase();

    if (!selectedAttributeOptions[cleanKey]) {
        selectedAttributeOptions[cleanKey] = [];
    }

    const idx = selectedAttributeOptions[cleanKey].indexOf(cleanVal);
    if (idx >= 0) {
        selectedAttributeOptions[cleanKey].splice(idx, 1);
    } else {
        selectedAttributeOptions[cleanKey].push(cleanVal);
    }

    regenerateMatrixProducts();
}

// Cartesian product algorithm across multiple attribute dimensions
function cartesianProduct(arrays) {
    if (!arrays || arrays.length === 0) return [];
    return arrays.reduce((acc, curr) => {
        const res = [];
        acc.forEach(a => {
            curr.forEach(c => {
                res.push(a.concat([c]));
            });
        });
        return res;
    }, [[]]);
}

function regenerateMatrixProducts() {
    const catId = document.getElementById('entryCatId').value;
    const catName = document.getElementById('entryCatName').value;
    const subId = document.getElementById('entrySubId').value;
    const subName = document.getElementById('entrySubName').value;
    const sscId = document.getElementById('entrySubSubId').value;
    const sscName = document.getElementById('entrySubSubName').value;

    if (!catId) {
        renderAddedProductsTable();
        updateAttributeChipVisuals();
        renderMatrixSection();
        return;
    }

    // Build dimensions in the defined attribute order
    const activeDimensions = [];
    currentCategoryAttributes.forEach(attr => {
        const aKey = (attr.attr_name || '').trim().toUpperCase();
        const selectedVals = selectedAttributeOptions[aKey] || [];
        if (selectedVals.length > 0) {
            activeDimensions.push(selectedVals.map(val => ({
                attr: aKey,
                val: val
            })));
        }
    });

    // Retain products from other categories or non-matrix items
    const otherProducts = addedProductsList.filter(item => {
        return String(item.category_id) !== String(catId) || !item.is_matrix_item;
    });

    if (activeDimensions.length === 0) {
        addedProductsList = otherProducts;
        renderAddedProductsTable();
        updateAttributeChipVisuals();
        renderMatrixSection();
        return;
    }

    const combinations = cartesianProduct(activeDimensions);
    const updatedCategoryProducts = [];

    combinations.forEach(combo => {
        const sortedCombo = [...combo].sort((a, b) => a.attr.localeCompare(b.attr));
        const comboKey = sortedCombo.map(c => `${c.attr}:${c.val}`).join('|');
        const comboDisplay = combo.map(c => c.val).join('-');

        // If user explicitly excluded this combination, skip adding it
        if (excludedComboKeys.has(comboKey)) {
            return;
        }

        // Check if item already exists in addedProductsList to preserve user-edited prices/quantities
        const existingItem = addedProductsList.find(it => {
            return String(it.category_id) === String(catId) && it.combo_key === comboKey;
        });

        if (existingItem) {
            if (!existingItem.category_type) {
                const qfCatType = document.getElementById('qfCategoryType');
                existingItem.category_type = (qfCatType && qfCatType.value) ? qfCatType.value : 'NORMAL';
                existingItem.is_complementary = (existingItem.category_type === 'COMPLEMENTARY') ? 1 : 0;
                existingItem.qr_type = (existingItem.category_type === 'SERIES') ? 'SERIES_BATCH' : 'INDIVIDUAL';
            }
            if (!existingItem.series_from) existingItem.series_from = 1;
            if (!existingItem.series_end) existingItem.series_end = (existingItem.quantity > 0) ? existingItem.quantity : 1;
            updatedCategoryProducts.push(existingItem);
        } else {
            let sizeVal = '';
            let colorVal = '';
            combo.forEach(c => {
                if (c.attr === 'SIZE') sizeVal = c.val;
                else if (c.attr === 'COLOR' || c.attr === 'COLOUR') colorVal = c.val;
            });
            if (!sizeVal) sizeVal = comboDisplay;

            // Product name: empty by default unless user entered a name in Quick Fill bar
            const qfNameInput = document.getElementById('qfProductName');
            const vProductName = (qfNameInput && qfNameInput.value.trim()) ? qfNameInput.value.trim().toUpperCase() : '';

            const qfCatType = document.getElementById('qfCategoryType');
            const initialCatType = (qfCatType && qfCatType.value) ? qfCatType.value : 'NORMAL';
            const isInitialSeriesOrComp = (initialCatType === 'SERIES' || initialCatType === 'COMPLEMENTARY');
            const qfQty = parseInt(document.getElementById('qfQty')?.value) || 0;
            const qfFrom = parseInt(document.getElementById('qfSeriesFrom')?.value) || 1;
            const qfEnd = parseInt(document.getElementById('qfSeriesEnd')?.value) || (qfQty > 0 ? qfQty : 1);

            updatedCategoryProducts.push({
                id: Date.now() + Math.floor(Math.random() * 100000),
                is_matrix_item: true,
                combo_key: comboKey,
                variant_display: comboDisplay,
                category_id: catId,
                category_name: catName,
                subcategory_id: subId || null,
                subcategory_name: subName || null,
                sub_subcategory_id: sscId || null,
                sub_subcategory_name: sscName || null,
                category_type: initialCatType,
                product_name: vProductName,
                hsn_code: '',
                weight: 0,
                mrp: 0,
                selling_price: 0,
                purchase_price: 0,
                quantity: 0,
                series_from: isInitialSeriesOrComp ? qfFrom : 1,
                series_end: isInitialSeriesOrComp ? qfEnd : 1,
                series_start_code: isInitialSeriesOrComp ? String(qfFrom) : '',
                series_end_code: isInitialSeriesOrComp ? String(qfEnd) : '',
                gst_percentage: 5.00,
                line_total: 0,
                size_name: sizeVal,
                color_name: colorVal,
                brand_id: DEFAULT_BRAND_ID,
                qr_type: (initialCatType === 'SERIES') ? 'SERIES_BATCH' : 'INDIVIDUAL',
                is_complementary: (initialCatType === 'COMPLEMENTARY') ? 1 : 0
            });
        }
    });

    addedProductsList = [...otherProducts, ...updatedCategoryProducts];

    renderAddedProductsTable();
    updateAttributeChipVisuals();
    renderMatrixSection();
}

function renderMatrixSection() {
    const section = document.getElementById('matrixCombinationsSection');
    const container = document.getElementById('matrixChipsContainer');
    const countBadge = document.getElementById('matrixCountBadge');

    if (!section || !container) return;

    const catId = document.getElementById('entryCatId').value;
    if (!catId) {
        section.classList.add('d-none');
        return;
    }

    const activeDimensions = [];
    currentCategoryAttributes.forEach(attr => {
        const aKey = (attr.attr_name || '').trim().toUpperCase();
        const selectedVals = selectedAttributeOptions[aKey] || [];
        if (selectedVals.length > 0) {
            activeDimensions.push(selectedVals.map(val => ({
                attr: aKey,
                val: val
            })));
        }
    });

    if (activeDimensions.length === 0) {
        section.classList.add('d-none');
        container.innerHTML = '';
        return;
    }

    const combinations = cartesianProduct(activeDimensions);
    if (combinations.length === 0) {
        section.classList.add('d-none');
        container.innerHTML = '';
        return;
    }

    const activeCombosCount = combinations.filter(combo => {
        const sortedCombo = [...combo].sort((a, b) => a.attr.localeCompare(b.attr));
        const comboKey = sortedCombo.map(c => `${c.attr}:${c.val}`).join('|');
        return !excludedComboKeys.has(comboKey);
    }).length;

    countBadge.textContent = `${activeCombosCount} of ${combinations.length} Variants Active`;

    let html = '';
    combinations.forEach(combo => {
        const sortedCombo = [...combo].sort((a, b) => a.attr.localeCompare(b.attr));
        const comboKey = sortedCombo.map(c => `${c.attr}:${c.val}`).join('|');
        const comboDisplay = combo.map(c => c.val).join('-');
        const isExcluded = excludedComboKeys.has(comboKey);

        if (isExcluded) {
            html += `
                <button type="button" class="btn btn-xs btn-outline-secondary matrix-combo-chip fw-semibold px-2.5 py-1 text-decoration-line-through opacity-75"
                        title="Click to add ${escapeHtml(comboDisplay)} back to Inward List"
                        onclick="toggleMatrixCombo('${escapeHtml(comboKey)}')">
                    <i class="bi bi-plus-circle me-1 text-primary"></i>${escapeHtml(comboDisplay)}
                </button>
            `;
        } else {
            html += `
                <button type="button" class="btn btn-xs btn-success text-white matrix-combo-chip fw-bold px-2.5 py-1 shadow-xs"
                        title="Click to exclude ${escapeHtml(comboDisplay)}"
                        onclick="toggleMatrixCombo('${escapeHtml(comboKey)}')">
                    <i class="bi bi-check2-circle me-1"></i>${escapeHtml(comboDisplay)}
                </button>
            `;
        }
    });

    container.innerHTML = html;
    section.classList.remove('d-none');
}

function toggleMatrixCombo(comboKey) {
    if (excludedComboKeys.has(comboKey)) {
        excludedComboKeys.delete(comboKey);
    } else {
        excludedComboKeys.add(comboKey);
    }
    regenerateMatrixProducts();
}

function selectAllAttributeVariants(selectAll) {
    const catId = document.getElementById('entryCatId').value;
    if (!catId) {
        alert('Please select a category first.');
        return;
    }

    if (!selectAll) {
        selectedAttributeOptions = {};
        excludedComboKeys.clear();
    } else {
        excludedComboKeys.clear();
        currentCategoryAttributes.forEach(attr => {
            const aKey = (attr.attr_name || '').trim().toUpperCase();
            const rawOpts = attr.attr_options || '';
            const opts = rawOpts ? rawOpts.split(',').map(o => o.trim().toUpperCase()).filter(Boolean) : [];
            selectedAttributeOptions[aKey] = [...opts];
        });
    }

    regenerateMatrixProducts();
}

function updateAttributeChipVisuals() {
    document.querySelectorAll('.attr-opt-chip').forEach(btn => {
        const attr = btn.getAttribute('data-attr');
        const val = btn.getAttribute('data-val');
        const isSelected = selectedAttributeOptions[attr] && selectedAttributeOptions[attr].includes(val);
        btn.classList.toggle('btn-primary', isSelected);
        btn.classList.toggle('text-white', isSelected);
        btn.classList.toggle('btn-outline-primary', !isSelected);
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = isSelected ? 'bi bi-check-circle-fill me-1' : 'bi bi-plus-circle me-1';
        }
    });
}

function addSingleCategoryProduct() {
    const catId = document.getElementById('entryCatId').value;
    const catName = document.getElementById('entryCatName').value;
    const subId = document.getElementById('entrySubId').value;
    const subName = document.getElementById('entrySubName').value;
    const sscId = document.getElementById('entrySubSubId').value;
    const sscName = document.getElementById('entrySubSubName').value;

    if (!catId) {
        alert('Please select a category first.');
        return;
    }

    const qfNameInput = document.getElementById('qfProductName');
    const pName = (qfNameInput && qfNameInput.value.trim()) ? qfNameInput.value.trim() : '';

    const qfCatType = document.getElementById('qfCategoryType');
    const initialCatType = (qfCatType && qfCatType.value) ? qfCatType.value : 'NORMAL';
    const isInitialSeriesOrComp = (initialCatType === 'SERIES' || initialCatType === 'COMPLEMENTARY');
    const qfQty = parseInt(document.getElementById('qfQty')?.value) || 0;
    const qfFrom = parseInt(document.getElementById('qfSeriesFrom')?.value) || 1;
    const qfEnd = parseInt(document.getElementById('qfSeriesEnd')?.value) || (qfQty > 0 ? qfQty : 1);

    addedProductsList.push({
        id: Date.now() + Math.floor(Math.random() * 10000),
        category_id: catId,
        category_name: catName,
        subcategory_id: subId || null,
        subcategory_name: subName || null,
        sub_subcategory_id: sscId || null,
        sub_subcategory_name: sscName || null,
        category_type: initialCatType,
        product_name: pName,
        hsn_code: '',
        weight: 0,
        mrp: 0,
        selling_price: 0,
        purchase_price: 0,
        quantity: 0,
        series_from: isInitialSeriesOrComp ? qfFrom : 1,
        series_end: isInitialSeriesOrComp ? qfEnd : 1,
        series_start_code: isInitialSeriesOrComp ? String(qfFrom) : '',
        series_end_code: isInitialSeriesOrComp ? String(qfEnd) : '',
        gst_percentage: 5.00,
        line_total: 0,
        size_name: 'Standard',
        color_name: '',
        brand_id: DEFAULT_BRAND_ID,
        qr_type: (initialCatType === 'SERIES') ? 'SERIES_BATCH' : 'INDIVIDUAL',
        is_complementary: (initialCatType === 'COMPLEMENTARY') ? 1 : 0
    });

    renderAddedProductsTable();
}

function addTemplateProduct(t) {
    const catId = document.getElementById('entryCatId').value;
    const catName = document.getElementById('entryCatName').value;
    const subId = document.getElementById('entrySubId').value;
    const subName = document.getElementById('entrySubName').value;
    const sscId = document.getElementById('entrySubSubId').value;
    const sscName = document.getElementById('entrySubSubName').value;

    const qfCatType = document.getElementById('qfCategoryType');
    const initialCatType = (qfCatType && qfCatType.value) ? qfCatType.value : 'NORMAL';
    const isInitialSeriesOrComp = (initialCatType === 'SERIES' || initialCatType === 'COMPLEMENTARY');
    const qfQty = parseInt(document.getElementById('qfQty')?.value) || 0;
    const qfFrom = parseInt(document.getElementById('qfSeriesFrom')?.value) || 1;
    const qfEnd = parseInt(document.getElementById('qfSeriesEnd')?.value) || (qfQty > 0 ? qfQty : 1);

    addedProductsList.push({
        id: Date.now() + Math.floor(Math.random() * 10000),
        category_id: catId,
        category_name: catName,
        subcategory_id: subId || null,
        subcategory_name: subName || null,
        sub_subcategory_id: sscId || null,
        sub_subcategory_name: sscName || null,
        category_type: initialCatType,
        product_name: t.product_template_name || '',
        hsn_code: t.hsn_code || '',
        weight: parseFloat(t.default_weight_kg) || 0,
        mrp: 0,
        selling_price: 0,
        purchase_price: 0,
        quantity: 0,
        series_from: isInitialSeriesOrComp ? qfFrom : 1,
        series_end: isInitialSeriesOrComp ? qfEnd : 1,
        series_start_code: isInitialSeriesOrComp ? String(qfFrom) : '',
        series_end_code: isInitialSeriesOrComp ? String(qfEnd) : '',
        gst_percentage: parseFloat(t.default_gst_pct) || 5.00,
        line_total: 0,
        size_name: 'Standard',
        color_name: '',
        brand_id: DEFAULT_BRAND_ID,
        qr_type: (initialCatType === 'SERIES') ? 'SERIES_BATCH' : 'INDIVIDUAL',
        is_complementary: (initialCatType === 'COMPLEMENTARY') ? 1 : 0
    });

    renderAddedProductsTable();
}

// -----------------------------------------------------------------------------
// 3. INLINE EDITABLE SPREADSHEET TABLE RENDERING
// -----------------------------------------------------------------------------
function renderAddedProductsTable() {
    const tbody = document.getElementById('addedProductsTableBody');
    const qfBar = document.getElementById('quickFillBar');
    const btnSubmit = document.getElementById('btnSaveAllProducts');

    if (addedProductsList.length === 0) {
        if (qfBar) qfBar.classList.add('d-none');
        tbody.innerHTML = `
            <tr id="emptyProductListNotice">
                <td colspan="13" class="text-center py-5 text-muted">
                    <i class="bi bi-cart-x fs-1 opacity-50 d-block mb-2"></i>
                    <div class="fw-semibold">No Products in Inward List Yet</div>
                    <div class="small">Choose a category and click attribute options above to directly load products here.</div>
                </td>
            </tr>`;
        if (btnSubmit) btnSubmit.disabled = true;
        recalculateAllTotals();
        return;
    }

    if (qfBar) qfBar.classList.remove('d-none');

    let html = '';

    addedProductsList.forEach((item, idx) => {
        let pathBadge = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle">${escapeHtml(item.category_name)}</span>`;
        if (item.subcategory_name) pathBadge += ` &gt; <span class="badge bg-info-subtle text-info border">${escapeHtml(item.subcategory_name)}</span>`;
        if (item.sub_subcategory_name) pathBadge += ` &gt; <span class="badge bg-light text-dark border">${escapeHtml(item.sub_subcategory_name)}</span>`;

        const mrp = parseFloat(item.mrp) || 0;
        const sp = parseFloat(item.selling_price) || 0;
        const cost = parseFloat(item.purchase_price) || 0;

        let priceWarnMsg = '';
        let isPriceInvalid = false;
        if (sp > 0 && cost > 0 && sp <= cost) {
            priceWarnMsg = `<i class="bi bi-exclamation-triangle-fill"></i> Selling must be &gt; Cost (₹${cost.toFixed(2)})!`;
            isPriceInvalid = true;
        } else if (sp > 0 && mrp > 0 && sp >= mrp) {
            priceWarnMsg = `<i class="bi bi-exclamation-triangle-fill"></i> Selling must be &lt; MRP (₹${mrp.toFixed(2)})!`;
            isPriceInvalid = true;
        }
        const isSeriesOrComp = (item.category_type === 'SERIES' || item.category_type === 'COMPLEMENTARY');
        const sFromVal = (item.series_from !== undefined && item.series_from !== null) ? item.series_from : 1;
        const sEndVal = (item.series_end !== undefined && item.series_end !== null) ? item.series_end : ((item.quantity && parseInt(item.quantity) > 0) ? parseInt(item.quantity) : 1);

        html += `
            <tr id="row_${item.id}">
                <td class="text-center fw-bold text-muted">${idx + 1}</td>
                <td><div class="text-nowrap small">${pathBadge}</div></td>
                <td>
                    <select class="form-select form-select-sm fw-semibold" id="catType_${item.id}"
                            onchange="updateItemField(${item.id}, 'category_type', this.value)">
                        <option value="NORMAL" ${(item.category_type || 'NORMAL') === 'NORMAL' ? 'selected' : ''}>Normal</option>
                        <option value="COMPLEMENTARY" ${item.category_type === 'COMPLEMENTARY' ? 'selected' : ''}>Complementary</option>
                        <option value="SERIES" ${item.category_type === 'SERIES' ? 'selected' : ''}>Series</option>
                    </select>
                    <div id="seriesBox_${item.id}" class="${isSeriesOrComp ? '' : 'd-none'} mt-1.5 p-1 bg-light rounded border shadow-xs">
                        <div class="d-flex align-items-center gap-1">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text py-0 px-1 font-monospace fs-8 text-muted bg-white">From</span>
                                <input type="number" min="1" step="1" id="from_${item.id}"
                                       class="form-control form-control-sm text-center px-1 font-monospace fw-bold series-range-input"
                                       disabled
                                       value="${sFromVal}"
                                       placeholder="1"
                                       oninput="updateItemField(${item.id}, 'series_from', this.value)">
                            </div>
                            <span class="text-muted small">&rarr;</span>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text py-0 px-1 font-monospace fs-8 text-muted bg-white">End</span>
                                <input type="number" min="1" step="1" id="end_${item.id}"
                                       class="form-control form-control-sm text-center px-1 font-monospace fw-bold series-range-input"
                                       disabled
                                       value="${sEndVal}"
                                       placeholder="1"
                                       oninput="updateItemField(${item.id}, 'series_end', this.value)">
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm fw-semibold text-uppercase" id="name_${item.id}"
                            value="${escapeHtml(item.product_name || '')}" 
                            placeholder="Product Name"
                            oninput="this.value=this.value.toUpperCase();updateItemField(${item.id}, 'product_name', this.value)">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm font-monospace text-center fw-bold" id="hsn_${item.id}"
                            value="${escapeHtml(item.hsn_code || '')}" 
                            placeholder="HSN" maxlength="20"
                            oninput="updateItemField(${item.id}, 'hsn_code', this.value)">
                </td>
                <td>
                    <input type="number" step="0.001" min="0" id="wt_${item.id}"
                            class="form-control form-control-sm text-end font-monospace" 
                            value="${(item.weight && parseFloat(item.weight) > 0) ? parseFloat(item.weight).toFixed(3) : ''}" 
                            placeholder="0.000"
                            oninput="updateItemField(${item.id}, 'weight', this.value)">
                </td>
                <td class="text-center">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1 fw-bold">
                        ${escapeHtml(item.variant_display || item.size_name || 'Standard')}
                    </span>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted px-1.5 fs-8">₹</span>
                        <input type="number" step="0.01" min="0" id="mrp_${item.id}"
                               class="form-control form-control-sm text-end text-success fw-bold ${(sp > 0 && mrp > 0 && sp >= mrp) ? 'is-invalid border-danger' : ''}" 
                               value="${(item.mrp && parseFloat(item.mrp) > 0) ? parseFloat(item.mrp).toFixed(2) : ''}" 
                               placeholder="0.00"
                               oninput="updateItemField(${item.id}, 'mrp', this.value)">
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted px-1.5 fs-8">₹</span>
                        <input type="number" step="0.01" min="0" id="sp_${item.id}"
                               class="form-control form-control-sm text-end text-primary fw-bold ${isPriceInvalid ? 'is-invalid border-danger' : ''}" 
                               value="${(item.selling_price && parseFloat(item.selling_price) > 0) ? parseFloat(item.selling_price).toFixed(2) : ''}" 
                               placeholder="0.00"
                               oninput="updateItemField(${item.id}, 'selling_price', this.value)">
                    </div>
                    <div id="valWarn_${item.id}" class="${isPriceInvalid ? '' : 'd-none'} text-danger fs-8 fw-bold mt-1 text-end">
                        ${priceWarnMsg}
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted px-1.5 fs-8">₹</span>
                        <input type="number" step="0.01" min="0" id="cost_${item.id}"
                               class="form-control form-control-sm text-end text-dark fw-bold ${(sp > 0 && cost > 0 && sp <= cost) ? 'is-invalid border-danger' : ''}" 
                               value="${(item.purchase_price && parseFloat(item.purchase_price) > 0) ? parseFloat(item.purchase_price).toFixed(2) : ''}" 
                               placeholder="0.00"
                               oninput="updateItemField(${item.id}, 'purchase_price', this.value)">
                    </div>
                </td>
                <td>
                    <input type="number" min="0" step="1" id="qty_${item.id}"
                           class="form-control form-control-sm text-center fw-bold fs-7" 
                           value="${(item.quantity && parseInt(item.quantity) > 0) ? item.quantity : ''}" 
                           placeholder="0"
                           oninput="updateItemField(${item.id}, 'quantity', this.value)">
                </td>
                <td class="text-end fw-bold text-primary fs-7" id="lineTotal_${item.id}">
                    ${(parseFloat(item.selling_price) > 0 && parseInt(item.quantity) > 0) ? `
                        <div>₹${(item.selling_price * item.quantity).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                        <div class="text-muted fs-8">₹${parseFloat(item.selling_price).toFixed(2)} &times; ${item.quantity}</div>
                    ` : `
                        <div class="text-muted">₹0.00</div>
                    `}
                </td>
                <td class="text-center text-nowrap">
                    <button type="button" class="btn btn-xs btn-outline-danger py-1 px-1.5" onclick="removeProductItem(${item.id})" title="Remove Product">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    if (btnSubmit) btnSubmit.disabled = false;
    recalculateAllTotals();
}

function updateItemField(itemId, fieldName, value) {
    const item = addedProductsList.find(it => it.id === itemId);
    if (!item) return;

    if (fieldName === 'category_type') {
        item.category_type = value;
        item.is_complementary = (value === 'COMPLEMENTARY') ? 1 : 0;
        item.qr_type = (value === 'SERIES') ? 'SERIES_BATCH' : 'INDIVIDUAL';

        const isSeriesOrComp = (value === 'SERIES' || value === 'COMPLEMENTARY');
        const box = document.getElementById(`seriesBox_${itemId}`);
        if (box) {
            box.classList.toggle('d-none', !isSeriesOrComp);
        }

        if (isSeriesOrComp) {
            const qty = (item.quantity && parseInt(item.quantity) > 0) ? parseInt(item.quantity) : 1;
            item.series_from = 1;
            item.series_end = qty;
            item.series_start_code = '1';
            item.series_end_code = String(qty);

            const fromEl = document.getElementById(`from_${itemId}`);
            const endEl = document.getElementById(`end_${itemId}`);
            if (fromEl) fromEl.value = 1;
            if (endEl) endEl.value = qty;
        } else {
            item.series_from = null;
            item.series_end = null;
            item.series_start_code = '';
            item.series_end_code = '';
        }
        return;
    } else if (fieldName === 'series_from') {
        item.series_from = parseInt(value) || 1;
        item.series_start_code = String(item.series_from);
        return;
    } else if (fieldName === 'series_end') {
        item.series_end = parseInt(value) || 1;
        item.series_end_code = String(item.series_end);
        return;
    } else if (fieldName === 'selling_price') {
        item.selling_price = parseFloat(value) || 0;
    } else if (fieldName === 'mrp') {
        item.mrp = parseFloat(value) || 0;
    } else if (fieldName === 'purchase_price') {
        item.purchase_price = parseFloat(value) || 0;
    } else if (fieldName === 'quantity') {
        const qty = parseInt(value) || 0;
        item.quantity = qty;

        // Auto-set From to 1 and End to Qty when Series or Complementary is selected
        if (item.category_type === 'SERIES' || item.category_type === 'COMPLEMENTARY') {
            item.series_from = 1;
            item.series_end = qty > 0 ? qty : 1;
            item.series_start_code = '1';
            item.series_end_code = String(item.series_end);

            const fromEl = document.getElementById(`from_${itemId}`);
            const endEl = document.getElementById(`end_${itemId}`);
            if (fromEl) fromEl.value = 1;
            if (endEl) endEl.value = item.series_end;
        }
    } else if (fieldName === 'weight') {
        item.weight = parseFloat(value) || 0;
    } else {
        item[fieldName] = value;
    }

    // Line total = Selling Price * Quantity (e.g. 800 * 20 = 16,000)
    item.line_total = (item.selling_price || 0) * (item.quantity || 0);

    // Update Line Total element on that row
    const lineTotalEl = document.getElementById(`lineTotal_${itemId}`);
    if (lineTotalEl) {
        if (item.selling_price > 0 && item.quantity > 0) {
            lineTotalEl.innerHTML = `
                <div>₹${item.line_total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                <div class="text-muted fs-8">₹${(item.selling_price || 0).toFixed(2)} &times; ${item.quantity || 0}</div>
            `;
        } else {
            lineTotalEl.innerHTML = `<div class="text-muted">₹0.00</div>`;
        }
    }

    // Price Validation: Supplier Cost < Selling Price < Base MRP
    const mrpInput = document.getElementById(`mrp_${itemId}`);
    const spInput = document.getElementById(`sp_${itemId}`);
    const costInput = document.getElementById(`cost_${itemId}`);
    const valWarn = document.getElementById(`valWarn_${itemId}`);

    const mrp = parseFloat(item.mrp) || 0;
    const sp = parseFloat(item.selling_price) || 0;
    const cost = parseFloat(item.purchase_price) || 0;

    let warnMsg = '';
    let isInvalid = false;
    if (sp > 0 && cost > 0 && sp <= cost) {
        warnMsg = `<i class="bi bi-exclamation-triangle-fill"></i> Selling must be &gt; Cost (₹${cost.toFixed(2)})!`;
        isInvalid = true;
    } else if (sp > 0 && mrp > 0 && sp >= mrp) {
        warnMsg = `<i class="bi bi-exclamation-triangle-fill"></i> Selling must be &lt; MRP (₹${mrp.toFixed(2)})!`;
        isInvalid = true;
    }

    if (spInput) {
        spInput.classList.toggle('is-invalid', isInvalid);
        spInput.classList.toggle('border-danger', isInvalid);
    }
    if (mrpInput) {
        const isMrpBad = (sp > 0 && mrp > 0 && sp >= mrp);
        mrpInput.classList.toggle('is-invalid', isMrpBad);
        mrpInput.classList.toggle('border-danger', isMrpBad);
    }
    if (costInput) {
        const isCostBad = (sp > 0 && cost > 0 && sp <= cost);
        costInput.classList.toggle('is-invalid', isCostBad);
        costInput.classList.toggle('border-danger', isCostBad);
    }

    if (valWarn) {
        if (isInvalid) {
            valWarn.innerHTML = warnMsg;
            valWarn.classList.remove('d-none');
        } else {
            valWarn.classList.add('d-none');
            valWarn.innerHTML = '';
        }
    }

    recalculateAllTotals();
}

// -----------------------------------------------------------------------------
// 3.1 QUICK FILL & COPY TO ALL VARIANTS UTILITIES
// -----------------------------------------------------------------------------
function validateQuickFillPricing() {
    const mrp = parseFloat(document.getElementById('qfMrp')?.value) || 0;
    const sp = parseFloat(document.getElementById('qfSp')?.value) || 0;
    const cost = parseFloat(document.getElementById('qfCost')?.value) || 0;

    const spInput = document.getElementById('qfSp');
    const mrpInput = document.getElementById('qfMrp');
    const costInput = document.getElementById('qfCost');
    const warn = document.getElementById('qfPriceWarn');

    let warnMsg = '';
    let isSpInvalid = false;

    if (sp > 0 && cost > 0 && sp <= cost) {
        warnMsg = `<i class="bi bi-exclamation-triangle-fill"></i> Selling must be &gt; Cost (₹${cost.toFixed(2)})`;
        isSpInvalid = true;
    } else if (sp > 0 && mrp > 0 && sp >= mrp) {
        warnMsg = `<i class="bi bi-exclamation-triangle-fill"></i> Selling must be &lt; MRP (₹${mrp.toFixed(2)})`;
        isSpInvalid = true;
    }

    if (spInput) {
        spInput.classList.toggle('is-invalid', isSpInvalid);
        spInput.classList.toggle('border-danger', isSpInvalid);
    }
    if (mrpInput) {
        const isMrpBad = (sp > 0 && mrp > 0 && sp >= mrp);
        mrpInput.classList.toggle('is-invalid', isMrpBad);
        mrpInput.classList.toggle('border-danger', isMrpBad);
    }
    if (costInput) {
        const isCostBad = (sp > 0 && cost > 0 && sp <= cost);
        costInput.classList.toggle('is-invalid', isCostBad);
        costInput.classList.toggle('border-danger', isCostBad);
    }

    if (warn) {
        if (isSpInvalid) {
            warn.innerHTML = warnMsg;
            warn.classList.remove('d-none');
        } else {
            warn.classList.add('d-none');
            warn.innerHTML = '';
        }
    }
    return !isSpInvalid;
}

function handleQfCategoryTypeChange(typeVal) {
    const box = document.getElementById('qfSeriesRangeBox');
    const fromInput = document.getElementById('qfSeriesFrom');
    const endInput = document.getElementById('qfSeriesEnd');
    const qtyVal = parseInt(document.getElementById('qfQty')?.value) || 0;
    const isSeriesOrComp = (typeVal === 'SERIES' || typeVal === 'COMPLEMENTARY');

    if (box) {
        box.classList.toggle('d-none', !isSeriesOrComp);
    }
    if (isSeriesOrComp) {
        if (fromInput) fromInput.value = 1;
        if (endInput) endInput.value = (qtyVal > 0) ? qtyVal : 1;
    }
}

function handleQfQtyInput(qtyVal) {
    const catType = document.getElementById('qfCategoryType')?.value || 'NORMAL';
    if (catType === 'SERIES' || catType === 'COMPLEMENTARY') {
        const fromInput = document.getElementById('qfSeriesFrom');
        const endInput = document.getElementById('qfSeriesEnd');
        const qty = parseInt(qtyVal) || 0;
        if (fromInput) fromInput.value = 1;
        if (endInput) endInput.value = (qty > 0) ? qty : 1;
    }
}

function showQuickFillNotice(msg) {
    const notice = document.getElementById('quickFillSuccessNotice');
    const noticeText = document.getElementById('quickFillNoticeText');
    if (notice && noticeText) {
        noticeText.textContent = msg;
        notice.classList.remove('d-none');
        clearTimeout(window._qfNoticeTimer);
        window._qfNoticeTimer = setTimeout(() => {
            notice.classList.add('d-none');
        }, 4000);
    }
}

function applyQuickFillToAll() {
    if (addedProductsList.length === 0) {
        alert('Please select attributes to load variants first.');
        return;
    }

    const pName = (document.getElementById('qfProductName')?.value || '').trim().toUpperCase();
    const catType = (document.getElementById('qfCategoryType')?.value || 'NORMAL');
    const hsn = (document.getElementById('qfHsn')?.value || '').trim();
    const weightVal = document.getElementById('qfWeight')?.value;
    const mrpVal = document.getElementById('qfMrp')?.value;
    const spVal = document.getElementById('qfSp')?.value;
    const costVal = document.getElementById('qfCost')?.value;
    const qtyVal = document.getElementById('qfQty')?.value;
    const qfFromVal = parseInt(document.getElementById('qfSeriesFrom')?.value) || 1;
    const qfEndVal = parseInt(document.getElementById('qfSeriesEnd')?.value) || ((qtyVal !== undefined && qtyVal !== '') ? (parseInt(qtyVal) > 0 ? parseInt(qtyVal) : 1) : 1);

    const mrpNum = parseFloat(mrpVal) || 0;
    const spNum = parseFloat(spVal) || 0;
    const costNum = parseFloat(costVal) || 0;

    if (spNum > 0 && costNum > 0 && spNum <= costNum) {
        alert(`Validation Error: Selling Price (₹${spNum.toFixed(2)}) must be greater than Supplier Cost (₹${costNum.toFixed(2)}).`);
        const spEl = document.getElementById('qfSp');
        if (spEl) spEl.focus();
        return;
    }
    if (spNum > 0 && mrpNum > 0 && spNum >= mrpNum) {
        alert(`Validation Error: Selling Price (₹${spNum.toFixed(2)}) must be less than Base MRP (₹${mrpNum.toFixed(2)}).`);
        const spEl = document.getElementById('qfSp');
        if (spEl) spEl.focus();
        return;
    }

    addedProductsList.forEach(item => {
        if (pName) item.product_name = pName;
        if (catType) {
            item.category_type = catType;
            item.is_complementary = (catType === 'COMPLEMENTARY') ? 1 : 0;
            item.qr_type = (catType === 'SERIES') ? 'SERIES_BATCH' : 'INDIVIDUAL';
        }
        if (hsn) item.hsn_code = hsn;
        if (weightVal !== undefined && weightVal !== '') item.weight = parseFloat(weightVal) || 0;
        if (mrpVal !== undefined && mrpVal !== '') item.mrp = parseFloat(mrpVal) || 0;
        if (spVal !== undefined && spVal !== '') item.selling_price = parseFloat(spVal) || 0;
        if (costVal !== undefined && costVal !== '') item.purchase_price = parseFloat(costVal) || 0;
        if (qtyVal !== undefined && qtyVal !== '') {
            item.quantity = parseInt(qtyVal) || 0;
        }

        if (item.category_type === 'SERIES' || item.category_type === 'COMPLEMENTARY') {
            item.series_from = qfFromVal;
            item.series_end = (qtyVal !== undefined && qtyVal !== '' && parseInt(qtyVal) > 0) ? parseInt(qtyVal) : qfEndVal;
            item.series_start_code = String(item.series_from);
            item.series_end_code = String(item.series_end);
        } else {
            item.series_from = null;
            item.series_end = null;
            item.series_start_code = '';
            item.series_end_code = '';
        }

        item.line_total = (item.selling_price || 0) * (item.quantity || 0);
    });

    renderAddedProductsTable();
    showQuickFillNotice(`Quick Fill details applied across all ${addedProductsList.length} variants!`);
}

function recalculateAllTotals() {
    let totalQty = 0;
    let totalWeight = 0.000;
    let retailTotal = 0.00;
    let supplierProductTotal = 0.00;

    addedProductsList.forEach(item => {
        const qty = parseInt(item.quantity) || 0;
        const wt = parseFloat(item.weight) || 0;
        const sp = parseFloat(item.selling_price) || 0;
        const cost = parseFloat(item.purchase_price) || 0;
        const gstPct = parseFloat(item.gst_percentage) || 0;
        item.line_total = sp * qty;
        totalQty += qty;
        totalWeight += (wt * qty);
        retailTotal += item.line_total;
        const lineCost = Math.round((cost * qty + Number.EPSILON) * 100) / 100;
        const lineTax = Math.round((lineCost * gstPct / 100 + Number.EPSILON) * 100) / 100;
        supplierProductTotal += lineCost + lineTax;
    });

    document.getElementById('badgeItemCount').textContent = `${addedProductsList.length} Product(s)`;
    document.getElementById('footerTotalQty').textContent = `${totalQty} pcs`;
    document.getElementById('footerTotalWeight').textContent = `${totalWeight.toFixed(3)} kg`;
    document.getElementById('footerGrandTotal').textContent = '₹' + retailTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const supplierTotalEl = document.getElementById('footerSupplierProductTotal');
    if (supplierTotalEl) supplierTotalEl.textContent = '₹' + supplierProductTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const btnSubmit = document.getElementById('btnSaveAllProducts');
    if (btnSubmit) {
        btnSubmit.disabled = (addedProductsList.length === 0);
    }
}

function removeProductItem(itemId) {
    const item = addedProductsList.find(it => it.id === itemId);
    if (item && item.combo_key) {
        excludedComboKeys.add(item.combo_key);
    }
    addedProductsList = addedProductsList.filter(it => it.id !== itemId);
    renderAddedProductsTable();
    updateAttributeChipVisuals();
    renderMatrixSection();
}

// -----------------------------------------------------------------------------
// 4. FINAL FORM SUBMIT & MULTI-ROW VALIDATION
// -----------------------------------------------------------------------------
function submitFinalProducts() {
    if (addedProductsList.length === 0) {
        alert('Please add at least one product to the inward list.');
        return;
    }

    // Strict Multi-Row Validation
    for (let i = 0; i < addedProductsList.length; i++) {
        const item = addedProductsList[i];
        const lineNum = i + 1;

        if (!item.product_name || !item.product_name.trim()) {
            alert(`Line #${lineNum}: Product Name is mandatory.`);
            return;
        }
        if (!item.hsn_code || !item.hsn_code.trim()) {
            alert(`Line #${lineNum} (${item.product_name}): HSN Code is mandatory.`);
            return;
        }
        if (parseFloat(item.weight) <= 0) {
            alert(`Line #${lineNum} (${item.product_name}): Weight in kg must be greater than 0.`);
            return;
        }
        if (parseFloat(item.mrp) <= 0) {
            alert(`Line #${lineNum} (${item.product_name}): Base MRP Price must be greater than 0.`);
            return;
        }
        if (parseFloat(item.selling_price) <= 0) {
            alert(`Line #${lineNum} (${item.product_name}): Selling Price must be greater than 0.`);
            return;
        }
        if (parseFloat(item.purchase_price) <= 0) {
            alert(`Line #${lineNum} (${item.product_name}): Supplier Cost must be greater than 0.`);
            return;
        }

        const mrp = parseFloat(item.mrp) || 0;
        const sp = parseFloat(item.selling_price) || 0;
        const cost = parseFloat(item.purchase_price) || 0;

        if (sp <= cost) {
            alert(`Validation Error on Line #${lineNum} (${item.product_name}):\n\nSelling Price (₹${sp.toFixed(2)}) must be greater than Supplier Cost (₹${cost.toFixed(2)}).`);
            const spInput = document.getElementById(`sp_${item.id}`);
            if (spInput) spInput.focus();
            return;
        }
        if (sp >= mrp) {
            alert(`Validation Error on Line #${lineNum} (${item.product_name}):\n\nSelling Price (₹${sp.toFixed(2)}) must be less than Base MRP (₹${mrp.toFixed(2)}).`);
            const spInput = document.getElementById(`sp_${item.id}`);
            if (spInput) spInput.focus();
            return;
        }
        if (parseInt(item.quantity) <= 0) {
            alert(`Line #${lineNum} (${item.product_name}): Quantity must be at least 1.`);
            return;
        }

        // Validate Series / Complementary range
        if (item.category_type === 'SERIES' || item.category_type === 'COMPLEMENTARY') {
            const sFrom = parseInt(item.series_from) || 1;
            const sEnd = parseInt(item.series_end) || (item.quantity > 0 ? item.quantity : 1);
            if (sEnd < sFrom) {
                alert(`Line #${lineNum} (${item.product_name}): Sequence End number (${sEnd}) cannot be smaller than From number (${sFrom}).`);
                const endInput = document.getElementById(`end_${item.id}`);
                if (endInput) endInput.focus();
                return;
            }
            item.series_from = sFrom;
            item.series_end = sEnd;
            item.series_start_code = String(sFrom);
            item.series_end_code = String(sEnd);
            item.pack_quantity = Math.max(1, (sEnd - sFrom + 1));
        }
    }

    const btn = document.getElementById('btnSaveAllProducts');
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Crediting Stock &amp; Saving Bill...`;

    document.getElementById('jsonProductsInput').value = JSON.stringify(addedProductsList);
    document.getElementById('finalSubmitForm').submit();
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

<style>
.fs-7 { font-size: 0.82rem !important; }
.fs-8 { font-size: 0.74rem !important; }
.btn-xs { padding: 0.15rem 0.4rem; font-size: 0.72rem; border-radius: 0.25rem; }
.cat-tile { transition: all 0.15s ease-in-out; }
.cat-tile:hover { transform: translateY(-1px); }
.attr-variant-chip, .attr-opt-chip, .matrix-combo-chip { transition: all 0.15s ease-in-out; }
.attr-variant-chip:hover, .attr-opt-chip:hover, .matrix-combo-chip:hover { transform: translateY(-1px); }
#addedProductsTable input { min-width: 60px; }
#addedProductsTable input.series-range-input { min-width: 36px !important; width: 100% !important; }
</style>
