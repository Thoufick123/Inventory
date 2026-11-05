<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-journal-plus me-2 text-primary"></i>Add Product Style</h2>
       
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('warehouse/catalog') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Catalog
        </a>
    </div>
</div>

<form method="POST" action="<?= url('warehouse/catalog/store') ?>" id="catalogForm" enctype="multipart/form-data">
    <?= CSRF::field() ?>

    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <!-- 1. PRODUCT STYLE SOURCING & DETAILS -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Source Type -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Product Source <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="source_type" id="srcPurchased" value="PURCHASED" checked>
                                    <label class="form-check-label fw-bold text-warning-emphasis" for="srcPurchased" style="cursor: pointer;">
                                        <i class="bi bi-bag-check me-1"></i> Third-Party Product
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="source_type" id="srcProduction" value="OWN_PRODUCTION">
                                    <label class="form-check-label fw-semibold text-success" for="srcProduction" style="cursor: pointer;">
                                        <i class="bi bi-gear-fill me-1"></i> In-House Production
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Style Code -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Style Code</label>
                            <input type="text" name="code" id="code" class="form-control text-uppercase font-monospace fw-bold" placeholder="<?= e($suggestedCode ?? 'TP-STY-0001') ?>">
                            <small class="text-muted" id="codeHelpText">Leave empty to auto-generate (e.g. <code>TP-STY-0001</code>).</small>
                        </div>

                        <!-- Product Name -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Product Style Name <small class="text-muted">(Optional - auto-builds)</small></label>
                            <input type="text" name="name" id="name" class="form-control text-uppercase" placeholder="e.g. FEEDING ZIP NIGHTY COTTON">
                        </div>

                        <!-- Seller / Supplier Dropdown (Dynamic for Third-Party) -->
                        <div class="col-md-4" id="sellerDropdownWrapper">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label fw-semibold mb-0 text-dark">
                                    <i class="bi bi-shop me-1 text-primary"></i> Seller / Supplier <span class="text-danger" id="sellerRequiredMark">*</span>
                                </label>
                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddSeller">
                                    <i class="bi bi-plus-circle-fill text-success"></i> + Add
                                </button>
                            </div>
                            <?php $selectedSellerId = $_GET['seller_id'] ?? ''; ?>
                            <select name="seller_id" id="seller_id" class="form-select">
                                <option value="">-- Select Seller / Supplier --</option>
                                <?php if (!empty($sellers)): ?>
                                    <?php foreach ($sellers as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= ((string)$s['id'] === (string)$selectedSellerId) ? 'selected' : '' ?>>
                                            <?= e($s['name']) ?><?= !empty($s['company']) ? ' (' . e($s['company']) . ')' : '' ?><?= !empty($s['city']) ? ' - ' . e($s['city']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <!-- Auto-Load Template & Create Fresh Options (Shown dynamically when a supplier is selected) -->
                            <div class="mt-2 d-flex align-items-center gap-2 flex-wrap" id="sellerTemplateControlWrapper" style="<?= empty($selectedSellerId) ? 'display: none !important;' : '' ?>">
                                <button type="button" class="btn btn-sm btn-primary fw-bold shadow-xs" id="btnAutoLoadSupplierTemplate" onclick="loadSupplierTemplateForMatrix()" title="Auto-fill variants from this supplier with brand new unique SKUs">
                                    <i class="bi bi-magic me-1"></i> Auto-Load Template
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearMatrixToFresh" onclick="resetMatrixToFresh()" title="Clear matrix to start blank">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Matrix
                                </button>
                            </div>
                        </div>

                        <!-- Brand Dropdown with + Add Option -->
                        <div class="col-md-4" id="brandDropdownWrapper">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label fw-semibold mb-0 text-dark">
                                    <i class="bi bi-tag-fill me-1 text-primary"></i> Brand / Label
                                </label>
                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddBrand">
                                    <i class="bi bi-plus-circle-fill text-success"></i> + Add
                                </button>
                            </div>
                            <select name="brand_id" id="brand_id" class="form-select" onchange="regenerateCatalogCode(true)">
                                <option value="">-- Select Brand / Label --</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Catalog Code (Auto-generated & editable for admin) -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold text-dark">
                                <i class="bi bi-upc-scan text-primary me-1"></i>Catalog Code <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text" name="catalog_code" id="catalog_code" class="form-control text-uppercase font-monospace fw-bold" placeholder="e.g. CAT-HAME-0001" value="<?= e($suggestedCatalogCode ?? 'CAT-STY-0001') ?>" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="regenerateCatalogCode(true)" title="Auto-generate / refresh Catalog Code">
                                    <i class="bi bi-arrow-clockwise"></i> Auto
                                </button>
                            </div>
                            <small class="text-muted" id="catalogCodeHelpText">Auto-generated easy catalog code for QR generation and inventory tracking.</small>
                            <input type="hidden" name="material" id="material" value="">
                            <input type="hidden" name="hsn_code" id="hsn_code" value="">
                        </div>

                        <!-- Complementary Product Switch -->
                        <div class="col-md-12">
                            <div class="p-2.5 rounded-3 border bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" name="is_complementary" id="catalogIsComplementary" value="1" <?= (!empty($product['is_complementary']) || (!empty($_GET['is_complementary']) && $_GET['is_complementary'] == '1')) ? 'checked' : '' ?> onchange="onToggleComplementaryMode(this.checked)" style="width: 2.2em; height: 1.2em; cursor: pointer;">
                                    <label class="form-check-label fw-bold text-dark cursor-pointer mb-0" for="catalogIsComplementary">
                                        <i class="bi bi-gift-fill text-warning me-1"></i> Complementary Product
                                    </label>
                                </div>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle small py-1 px-2">
                                    <i class="bi bi-check-circle me-1"></i>Direct Inventory Inward (Avoids QR Inward) &bull; Single Selling Price
                                </span>
                            </div>
                        </div>

                        <!-- Hidden fields: GST, MRP, Selling Price, Cost Price, Weight, Total Quantity, Dates (Managed variant-wise in Section 4) -->
                        <input type="hidden" name="gst_percentage" id="gst_percentage" value="5.00">
                        <input type="hidden" name="default_mrp" id="default_mrp" value="0.00">
                        <input type="hidden" name="default_selling_price" id="default_selling_price" value="0.00">
                        <input type="hidden" name="default_purchase_price" id="default_purchase_price" value="0.00">
                        <input type="hidden" name="quantity" id="quantity" value="0">
                        <input type="hidden" name="weight" id="weight" value="0.000">
                        <input type="hidden" name="mfg_date" id="mfg_date" value="">
                        <input type="hidden" name="expiry_date" id="expiry_date" value="">

                        <!-- Style Photo -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Style Photo / Reference Image</label>
                            <input type="file" name="image" id="image" class="form-control" accept="image/*">
                        </div>

                        <!-- Description -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Description / Notes</label>
                            <textarea name="description" id="description" class="form-control text-uppercase" rows="2" placeholder="Specific garment features, vendor details, wash care, etc."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. PRODUCT VARIANTS & PRICING MATRIX -->
            <div class="card shadow-sm border-0 mb-4" id="variantMatrixCard">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i> 2. Product Variants &amp; Pricing Matrix
                        </h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                            <i class="bi bi-boxes me-1"></i> Total Quantity: <span id="lblMatrixTotalQty" class="fw-bold">0</span> Pcs
                        </span>
                    </div>
                </div>

                <div class="card-body p-3">
                    <!-- Hidden Category & QR Inputs (Populated dynamically from Variant Cards) -->
                    <input type="hidden" name="category_id" id="category_id" value="">
                    <input type="hidden" name="subcategory_id" id="subcategory_id" value="">
                    <input type="hidden" name="sub_subcategory_id" id="sub_subcategory_id" value="">
                    <input type="hidden" name="category_code" id="category_code" value="">
                    <input type="hidden" name="subcategory_code" id="subcategory_code" value="">
                    <input type="hidden" name="sub_subcategory_code" id="sub_subcategory_code" value="">
                    <input type="hidden" name="qr_code_prefix" id="qr_code_prefix" value="">


                    <!-- Quick Batch Control Bar -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <span class="fw-bold small text-dark"><i class="bi bi-sliders me-1 text-primary"></i> Batch Value Fill &amp; Variant Actions:</span>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-success fw-semibold shadow-xs" onclick="openAddVariantRowModal('INDIVIDUAL')">
                                        <i class="bi bi-plus-circle-fill me-1"></i> Add Custom Variant Card
                                    </button>
                                <button type="button" class="btn btn-sm btn-success dropdown-toggle dropdown-toggle-split shadow-xs" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="min-width: 270px;">
                                    <li>
                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" onclick="openAddVariantRowModal('INDIVIDUAL')">
                                            <i class="bi bi-tag text-secondary fs-5"></i>
                                            <div>
                                                <div class="fw-bold text-dark">🏷️ Individual Piece Card</div>
                                                <small class="text-muted">Standard 1 sticker per piece mode</small>
                                            </div>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" onclick="openAddVariantRowModal('SERIES_BATCH')">
                                            <i class="bi bi-collection text-primary fs-5"></i>
                                            <div>
                                                <div class="fw-bold text-primary">📦 Series Bundle Pack Card</div>
                                                <small class="text-muted">Number series range bundle mode</small>
                                            </div>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        </div>
                        <div class="row g-2 align-items-center">
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <i class="bi bi-building me-1 text-primary"></i> Inward Warehouse
                                </label>
                                <select name="initial_warehouse_id" id="initial_warehouse_id" class="form-select form-select-sm" <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || (!empty($warehouses) && count($warehouses) <= 1)) ? 'disabled' : '' ?>>
                                    <?php foreach (($warehouses ?? []) as $wh): ?>
                                        <option value="<?= $wh['id'] ?>" <?= (count($warehouses ?? []) === 1 || (function_exists('currentWarehouseId') && currentWarehouseId() == $wh['id'])) ? 'selected' : '' ?>><?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || (!empty($warehouses) && count($warehouses) <= 1)): ?>
                                    <input type="hidden" name="initial_warehouse_id" value="<?= e($warehouses[0]['id'] ?? (function_exists('currentWarehouseId') ? currentWarehouseId() : '')) ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Variants Container: Card Format -->
                    <div id="variantCardsContainer" class="d-flex flex-column gap-3 mb-3">
                        <!-- Dynamic JS Generated Variant Cards -->
                    </div>

                    <!-- Matrix Summary Footer Card -->
                    <div class="card border rounded-3 bg-light bg-opacity-75 p-3 shadow-xs" id="matrixSummaryFooterCard">
                        <div class="row align-items-center g-3">
                            <div class="col-md-3 col-6">
                                <div class="small text-muted fw-semibold mb-1">Total Stock Quantity</div>
                                <div class="fs-5 fw-bold text-primary" id="lblFooterTotalQty">0 pcs</div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="small text-muted fw-semibold mb-1">Packaging Summary</div>
                                <div id="lblFooterSeriesSummary">—</div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="small text-muted fw-semibold mb-1">Est. Total Weight</div>
                                <div class="fs-6 fw-bold text-dark" id="lblFooterTotalWeight">—</div>
                            </div>
                            <div class="col-md-3 col-6 text-md-end">
                                <div class="small text-muted fw-semibold mb-1">Total Est. Valuation</div>
                                <div class="fs-5 fw-bold text-success" id="lblFooterTotalValuation">₹0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card Footer with Action Buttons -->
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                    <a href="<?= url('warehouse/catalog') ?>" class="btn btn-outline-secondary px-4">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold fs-6 shadow-sm" id="btnSubmitCatalog">
                        <i class="bi bi-check-circle-fill me-1"></i> Save Product Style to Warehouse Catalog
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- ADD CUSTOM PRODUCT VARIANT MODAL -->
<div class="modal fade" id="modalAddVariantRow" tabindex="-1" aria-labelledby="modalAddVariantRowLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-3 px-4 rounded-top-4">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="modalAddVariantRowLabel">
                        <i class="bi bi-box-seam me-2"></i> Add Product Variant Details
                    </h5>
                    <small class="text-white-50">Specify packaging mode, attributes (Size &amp; Color), SKU, quantity, pricing, and dates</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="formAddVariantRowModal" onsubmit="event.preventDefault(); submitAddVariantRowModal();">
                    <!-- Packaging QR Type Selector -->
                    <div class="card border-0 shadow-xs rounded-3 mb-3">
                        <div class="card-body p-3 bg-white rounded-3">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">
                                <i class="bi bi-qr-code-scan me-1 text-primary"></i> Packaging &amp; Label Mode <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="form-check p-3 border rounded-3 h-100 bg-light-subtle d-flex align-items-start gap-2" style="cursor: pointer;" onclick="document.getElementById('modalVarQrIndiv').checked = true; onModalVarQrTypeChange();">
                                        <input class="form-check-input mt-1" type="radio" name="modal_var_qr_type" id="modalVarQrIndiv" value="INDIVIDUAL" checked onchange="onModalVarQrTypeChange()">
                                        <label class="form-check-label w-100" for="modalVarQrIndiv" style="cursor: pointer;">
                                            <div class="fw-bold text-dark">🏷️ Individual Piece QR</div>
                                            <small class="text-muted d-block">1 Barcode / QR sticker per garment piece (Standard retail piece)</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check p-3 border rounded-3 h-100 bg-light-subtle d-flex align-items-start gap-2" style="cursor: pointer;" onclick="document.getElementById('modalVarQrSeries').checked = true; onModalVarQrTypeChange();">
                                        <input class="form-check-input mt-1" type="radio" name="modal_var_qr_type" id="modalVarQrSeries" value="SERIES_BATCH" onchange="onModalVarQrTypeChange()">
                                        <label class="form-check-label w-100" for="modalVarQrSeries" style="cursor: pointer;">
                                            <div class="fw-bold text-primary">📦 Series Bundle Pack QR</div>
                                            <small class="text-muted d-block">Number series sequential range barcode for bundle pack</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Variant Details Card -->
                    <div class="card border-0 shadow-xs rounded-3 mb-3">
                        <div class="card-body p-3 bg-white rounded-3">
                            <div class="row g-3">
                                <!-- Size Selection with "+ Add" Button -->
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold mb-1 text-dark">
                                        <i class="bi bi-rulers me-1 text-primary"></i> Size Attribute <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="text" id="modalVarSizeInput" list="modalVarSizeDatalist" class="form-control text-uppercase fw-semibold" placeholder="e.g. M, L, XL, 38" onkeydown="if(event.key==='Enter'){event.preventDefault();addModalSizeAttribute();}">
                                        <button type="button" class="btn btn-primary fw-semibold px-3" id="btnModalAddSize" onclick="addModalSizeAttribute()">
                                            <i class="bi bi-plus-lg me-1"></i> Add
                                        </button>
                                    </div>
                                    <datalist id="modalVarSizeDatalist">
                                        <?php if (!empty($sizes)): ?>
                                            <?php foreach ($sizes as $sz): ?>
                                                <option value="<?= e($sz['code']) ?>" data-id="<?= $sz['id'] ?>" data-name="<?= e($sz['name']) ?>"><?= e($sz['code']) ?><?= ($sz['name'] !== $sz['code']) ? ' (' . e($sz['name']) . ')' : '' ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <option value="STANDARD">Standard / Free Size</option>
                                        <option value="S">S - Small</option>
                                        <option value="M">M - Medium</option>
                                        <option value="L">L - Large</option>
                                        <option value="XL">XL - Extra Large</option>
                                        <option value="XXL">XXL - Double Large</option>
                                        <option value="3XL">3XL</option>
                                    </datalist>
                                    <small class="text-muted d-block">Type size (or pick from list) and click <strong>Add</strong>.</small>
                                    <div id="modalVarSizeBadges" class="d-flex flex-wrap gap-1 mt-2"></div>
                                </div>

                                <!-- Color Attributes Container (Right 8 Columns) -->
                                <div class="col-md-8">
                                    <div class="row g-2">
                                        <!-- Color Name Field -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold mb-1 text-dark">
                                                <i class="bi bi-palette me-1 text-primary"></i> Color Name <small class="text-muted">(Optional)</small>
                                            </label>
                                            <input type="text" id="modalVarColorInput" list="modalVarColorDatalist" class="form-control text-uppercase" placeholder="e.g. RED, BLUE, BLACK" oninput="onModalVarColorNameInput()" onkeydown="if(event.key==='Enter'){event.preventDefault();addModalColorAttribute();}">
                                            <datalist id="modalVarColorDatalist">
                                                <?php if (!empty($colors)): ?>
                                                    <?php foreach ($colors as $c): ?>
                                                        <option value="<?= e($c['name']) ?>" data-id="<?= $c['id'] ?>" data-hex="<?= e($c['hex_code'] ?? '') ?>"><?= e($c['name']) ?><?= !empty($c['hex_code']) ? ' (' . e($c['hex_code']) . ')' : '' ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </datalist>
                                            <small class="text-muted d-block">Type color name (or pick from list).</small>
                                        </div>

                                        <!-- Color Code Field with "+ Add" Button -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold mb-1 text-dark">
                                                <i class="bi bi-eyedropper me-1 text-primary"></i> Color Code <small class="text-muted">(Optional)</small>
                                            </label>
                                            <div class="input-group">
                                                <input type="color" id="modalVarColorPicker" class="form-control form-control-color p-1" style="max-width: 44px; height: 38px; cursor: pointer;" value="#000000" title="Choose color swatch" oninput="onModalVarColorPickerInput()">
                                                <input type="text" id="modalVarColorHex" class="form-control font-monospace text-uppercase" placeholder="#HEX (e.g. #FF0000)" value="" maxlength="7" oninput="onModalVarColorHexInput()" onblur="onModalVarColorHexBlur()" onkeydown="if(event.key==='Enter'){event.preventDefault();addModalColorAttribute();}">
                                                <button type="button" class="btn btn-primary fw-semibold px-3" id="btnModalAddColor" onclick="addModalColorAttribute()" title="Add Color to List">
                                                    <i class="bi bi-plus-lg me-1"></i> Add
                                                </button>
                                            </div>
                                            <small class="text-muted d-block">Pick swatch or type hex, then click <strong>Add</strong>.</small>
                                        </div>

                                        <!-- Selected Color Badges Container (strictly placed under the Color inputs) -->
                                        <div class="col-12 mt-2">
                                            <div id="modalVarColorBadges" class="d-flex flex-wrap gap-1"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Base SKU Prefix -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-dark">
                                        <i class="bi bi-upc-scan me-1 text-primary"></i> Base Variant SKU Prefix <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="text" id="modalVarSku" class="form-control font-monospace fw-bold text-uppercase" placeholder="e.g. STY-0001" required oninput="renderModalMultiVariantTable()">
                                        <button type="button" class="btn btn-outline-secondary" onclick="generateModalVarSku(true)" title="Auto-generate SKU Code">
                                            <i class="bi bi-magic me-1"></i> Auto SKU
                                        </button>
                                    </div>
                                    <small class="text-muted" id="modalVarSkuHelp">Base SKU prefix for variants.</small>
                                </div>

                                <!-- Series Range Inputs (Visible only when Series mode is selected) -->
                                <div class="col-md-12" id="modalVarSeriesWrapper" style="display: none;">
                                    <div class="p-3 bg-primary-subtle border border-primary-subtle rounded-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold small text-primary mb-0">
                                                <i class="bi bi-collection me-1"></i> Series Range Number Configuration
                                            </label>
                                            <span class="badge bg-white text-primary border border-primary-subtle font-monospace small" id="modalVarSeriesUnitsBadge">
                                                <i class="bi bi-boxes me-1"></i>0 Units Total
                                            </span>
                                        </div>
                                        <div class="row g-2 align-items-center">
                                             <div class="col-md-5">
                                                <label class="form-label small text-muted mb-1">Series Start Code</label>
                                                <input type="text" id="modalVarSeriesStart" class="form-control form-control-sm font-monospace" placeholder="e.g. 0001001" oninput="onModalVarSeriesStartChange()">
                                            </div>
                                            <div class="col-md-2 text-center pt-3">
                                                <span class="badge bg-primary text-white font-monospace">&rarr; TO &rarr;</span>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label small text-muted mb-1">Series End Code <span class="badge bg-white text-muted border border-secondary-subtle fw-normal ms-1" style="font-size:0.68rem;">Auto-calculated</span></label>
                                                <input type="text" id="modalVarSeriesEnd" class="form-control form-control-sm font-monospace" placeholder="e.g. 0001009" oninput="onModalVarSeriesEndChange()">
                                            </div>
                                        </div>
                                        <small class="text-primary-emphasis d-block mt-2" style="font-size: 0.78rem;">
                                            <i class="bi bi-magic me-1"></i> Enter the starting number. The ending number and variant series ranges are auto-generated based on total variants &amp; quantities.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Variant-Wise Details & Pricing Table -->
                    <div id="modalMultiVariantBreakdownCard" class="card border-0 shadow-xs rounded-3 mb-1" style="background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 pb-2 border-bottom">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                        <i class="bi bi-ui-checks-grid text-primary"></i>
                                        <span>Variant-Wise Inventory &amp; Pricing Details</span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace" id="modalMultiVariantBadgeCount">0 Variants</span>
                                    </h6>
                                    <small class="text-muted">Enter individual SKU, HSN, Weight, Qty, MRP, Cost, Mfg Date, and Exp Date for each variant below:</small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-primary btn-sm fw-bold px-3 shadow-xs d-inline-flex align-items-center gap-1.5" id="btnGenerateVariants" onclick="renderModalMultiVariantTable()">
                                        <i class="bi bi-gear-wide-connected"></i> Generate Variants
                                    </button>
                                </div>
                            </div>

                            <!-- Quick Batch Fill All Toolbar -->
                            <div class="d-flex flex-wrap align-items-center gap-2 p-2 bg-white rounded-3 border mb-2 shadow-xs">
                                <span class="small fw-bold text-dark text-nowrap"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Fill All:</span>
                                <input type="text" id="batchFillHsn" class="form-control form-control-sm text-uppercase" placeholder="HSN Code" style="width: 100px;" title="Batch HSN Code">
                                <input type="number" step="0.001" min="0" id="batchFillWeight" class="form-control form-control-sm text-center" placeholder="Weight (kg)" style="width: 95px;" title="Batch Weight">
                                <input type="number" step="1" min="1" id="batchFillQty" class="form-control form-control-sm text-center fw-bold text-primary" placeholder="Qty" style="width: 70px;" title="Batch Quantity">
                                <input type="number" step="0.01" min="0" id="batchFillMrp" class="form-control form-control-sm text-end fw-bold text-success" placeholder="MRP (₹)" style="width: 95px;" title="Batch MRP">
                                <input type="number" step="0.01" min="0" id="batchFillCost" class="form-control form-control-sm text-end" placeholder="Cost (₹)" style="width: 95px;" title="Batch Cost">
                                <div class="d-inline-flex align-items-center gap-1 bg-light border rounded px-1.5 py-0.5">
                                    <span class="small text-muted"><i class="bi bi-calendar-check text-success"></i> Mfg:</span>
                                    <input type="date" id="batchFillMfg" class="form-control form-control-sm border-0 bg-transparent p-0" style="width: 110px;" title="Batch Mfg Date">
                                </div>
                                <div class="d-inline-flex align-items-center gap-1 bg-light border rounded px-1.5 py-0.5">
                                    <span class="small text-muted"><i class="bi bi-calendar-x text-danger"></i> Exp:</span>
                                    <input type="date" id="batchFillExp" class="form-control form-control-sm border-0 bg-transparent p-0" style="width: 110px;" title="Batch Exp Date">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold px-2 py-1" onclick="applyQuickFillToAllRows()" title="Apply batch values to all variant rows below">
                                    <i class="bi bi-arrow-down-circle-fill me-1"></i> Apply to All Rows
                                </button>
                            </div>

                            <div class="table-responsive rounded-3 border bg-white shadow-xs" style="max-height: 340px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0" id="modalMultiVariantTable">
                                    <thead class="table-light sticky-top" style="font-size: 0.78rem; z-index: 2;">
                                        <tr>
                                            <th class="ps-3 py-2 text-uppercase text-secondary" style="min-width: 130px;">Variant</th>
                                            <th class="py-2 text-uppercase text-secondary" style="min-width: 150px;">Variant SKU <span class="text-danger">*</span></th>
                                            <th class="py-2 text-uppercase text-secondary th-series-col" style="min-width: 170px; display: none;"><i class="bi bi-collection me-1 text-primary"></i>Series Range</th>
                                            <th class="py-2 text-uppercase text-secondary" style="min-width: 90px;">HSN Code</th>
                                            <th class="py-2 text-center text-uppercase text-secondary" style="min-width: 85px;">Weight (kg)</th>
                                            <th class="py-2 text-center text-uppercase text-primary" style="min-width: 75px;">Qty (Pcs) <span class="text-danger">*</span></th>
                                            <th class="py-2 text-center text-uppercase text-success th-mrp-col" style="min-width: 95px;">MRP Price (₹) <span class="text-danger">*</span></th>
                                            <th class="py-2 text-center text-uppercase text-secondary" style="min-width: 95px;">Cost Price (₹)</th>
                                            <th class="py-2 text-center text-uppercase text-secondary" style="min-width: 125px;"><i class="bi bi-calendar-check text-success me-1"></i>Mfg Date</th>
                                            <th class="py-2 text-center text-uppercase text-secondary" style="min-width: 125px;"><i class="bi bi-calendar-x text-danger me-1"></i>Exp Date</th>
                                            <th class="py-2 text-center text-secondary pe-2" style="width: 35px;">Del</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalMultiVariantTableBody">
                                        <tr><td colspan="11" class="text-center py-4 text-muted"><i class="bi bi-info-circle me-1"></i> Add Size(s) and Color(s) above and click <strong>Add</strong> or <button type="button" class="btn btn-xs btn-primary fw-semibold px-2 py-0.5 ms-1" onclick="renderModalMultiVariantTable()"><i class="bi bi-gear-wide-connected me-1"></i>Generate Variants</button> to generate variant rows.</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Live Footer Summary -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 px-1 gap-2">
                                <div class="small text-muted">
                                    Each row generates a unique card in the matrix table with these exact individual values.
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <div class="small fw-semibold text-dark bg-white py-1 px-2 border rounded-2">
                                        Variants: <span id="modalSummaryVariantCount" class="fw-bold text-dark">0</span>
                                    </div>
                                    <div class="small fw-semibold text-dark bg-white py-1 px-2 border rounded-2">
                                        Total Qty: <span id="modalMultiVariantTotalQty" class="text-primary fw-bold">0</span> pcs
                                    </div>
                                    <div class="small fw-semibold text-dark bg-white py-1 px-2 border rounded-2">
                                        Total Weight: <span id="modalSummaryTotalWeight" class="text-dark fw-bold">0.000 kg</span>
                                    </div>
                                    <div class="small fw-semibold text-dark bg-white py-1 px-2 border rounded-2">
                                        Valuation: <span id="modalSummaryTotalValuation" class="text-success fw-bold">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light rounded-bottom-4 py-3 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4 fw-bold" id="btnModalAddVariantSubmit" onclick="submitAddVariantRowModal()">
                    <i class="bi bi-plus-circle me-1"></i> Add Variant to Matrix Table
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ADD MAIN CATEGORY MODAL -->
<div class="modal fade" id="modalAddMainCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-1 text-primary"></i> Quick Add Main Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Main Category Name <span class="text-danger">*</span></label>
                    <input type="text" id="modalMainCatName" class="form-control text-uppercase" placeholder="E.G. MEN'S APPAREL, HOME TEXTILES">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveQuickMainCat">
                    <i class="bi bi-plus-lg me-1"></i> Add Main Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT MAIN CATEGORY MODAL -->
<div class="modal fade" id="modalEditMainCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1 text-warning"></i> Edit Main Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Main Category Name <span class="text-danger">*</span></label>
                    <input type="text" id="modalEditMainCatNameInput" class="form-control text-uppercase">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveEditMainCat">
                    <i class="bi bi-check-lg me-1"></i> Update Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ADD SUBCATEGORY MODAL -->
<div class="modal fade" id="modalAddSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-1 text-primary"></i> Quick Add Subcategory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Parent Category</label>
                    <input type="text" id="modalSubcatParentName" class="form-control" readonly disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Subcategory Name <span class="text-danger">*</span></label>
                    <input type="text" id="modalSubcatName" class="form-control text-uppercase" placeholder="E.G. NIGHTWEAR & SLEEPWEAR">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveQuickSubcat">
                    <i class="bi bi-plus-lg me-1"></i> Add Subcategory
                </button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT SUBCATEGORY MODAL -->
<div class="modal fade" id="modalEditSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1 text-warning"></i> Edit Sub Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sub Category Name <span class="text-danger">*</span></label>
                    <input type="text" id="modalEditSubcatNameInput" class="form-control text-uppercase">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveEditSubcat">
                    <i class="bi bi-check-lg me-1"></i> Update Sub Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ADD SUB-SUBCATEGORY MODAL -->
<div class="modal fade" id="modalAddSubSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-1 text-primary"></i> Quick Add Sub-Sub Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Parent Subcategory</label>
                    <input type="text" id="modalSubSubcatParentName" class="form-control" readonly disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sub-Subcategory Name <span class="text-danger">*</span></label>
                    <input type="text" id="modalSubSubcatName" class="form-control text-uppercase" placeholder="E.G. FEEDING ZIP NIGHTY">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveQuickSubSubcat">
                    <i class="bi bi-plus-lg me-1"></i> Add Sub-Sub Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT SUB-SUBCATEGORY MODAL -->
<div class="modal fade" id="modalEditSubSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1 text-warning"></i> Edit Sub Sub Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sub Sub Category Name <span class="text-danger">*</span></label>
                    <input type="text" id="modalEditSubSubcatNameInput" class="form-control text-uppercase">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveEditSubSubcat">
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
                    <input type="text" id="modalSizeCode" class="form-control text-uppercase" placeholder="E.G. M, L, XL, 32, 34, FREE SIZE">
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
                    <input type="text" id="modalColorName" list="existingColorsList" class="form-control text-uppercase" placeholder="E.G. SKY BLUE, NAVY BLUE, MAROON">
                    <datalist id="existingColorsList">
                        <?php foreach ($colors as $c): ?>
                            <option value="<?= e($c['name']) ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </datalist>
                    <small class="text-muted">Type any custom color name or select an existing color.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Color Hex Code</label>
                    <div class="input-group">
                        <input type="color" id="modalColorPicker" class="form-control form-control-color" value="#000000" title="Choose color">
                        <input type="text" id="modalColorHex" class="form-control text-uppercase" placeholder="#HEX (e.g. #FF0000)" value="">
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

<!-- QUICK ADD BRAND MODAL -->
<div class="modal fade" id="modalAddBrand" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-tag-fill me-1 text-primary"></i> Quick Add Brand / Label</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Brand / Label Name <span class="text-danger">*</span></label>
                    <input type="text" id="modalBrandName" class="form-control text-uppercase" placeholder="E.G. ALPHA GARMENTS, GRM JUNIOR">
                    <small class="text-muted">Enter brand or manufacturer label.</small>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveQuickBrand">
                    <i class="bi bi-check-lg me-1"></i> Save Brand
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ADD SELLER / SUPPLIER MODAL -->
<div class="modal fade" id="modalAddSeller" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-shop me-1 text-primary"></i> Quick Add Supplier / Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Supplier / Contact Name <span class="text-danger">*</span></label>
                    <input type="text" id="modalSellerName" class="form-control" placeholder="E.g. Ramesh Kumar / Chennai Wholesalers">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Company / Mill Name</label>
                    <input type="text" id="modalSellerCompany" class="form-control" placeholder="E.g. RK Apparels Pvt Ltd">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mobile Number</label>
                        <input type="tel" id="modalSellerPhone" class="form-control" placeholder="10-digit mobile" maxlength="15">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">City</label>
                        <input type="text" id="modalSellerCity" class="form-control" placeholder="E.g. Tirupur / Surat">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">GSTIN <small class="text-muted">(Optional)</small></label>
                    <input type="text" id="modalSellerGst" class="form-control font-monospace" placeholder="33AAAAA0000A1Z5" maxlength="15" style="text-transform: uppercase;">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveQuickSeller">
                    <i class="bi bi-check-lg me-1"></i> Save Supplier
                </button>
            </div>
        </div>
    </div>
</div>

<!-- AUTO-GENERATE NUMBER SERIES RANGE MODAL -->
<div class="modal fade" id="modalAutoGenerateSeriesRange" tabindex="-1" aria-labelledby="modalAutoGenerateSeriesRangeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-3 px-4 rounded-top-4">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="modalAutoGenerateSeriesRangeLabel">
                        <i class="bi bi-magic me-2"></i> Auto-Generate Number Series Range
                    </h5>
                    <small class="text-white-50">Generate, configure, and review sequential series range numbers for each variant pack</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Configuration Card -->
                <div class="card border-0 shadow-xs rounded-3 mb-3">
                    <div class="card-body p-3 bg-white rounded-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-2 col-md-4 col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <i class="bi bi-hash text-primary"></i> Start Sequence #
                                </label>
                                <input type="number" id="seriesModalStartSeq" class="form-control form-control-sm font-monospace fw-bold" value="1" min="1" placeholder="1" oninput="onSeriesModalStartOrEndChange('start')">
                            </div>
                            <div class="col-lg-2 col-md-4 col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <i class="bi bi-flag-fill text-danger"></i> End Sequence #
                                </label>
                                <input type="number" id="seriesModalEndSeq" class="form-control form-control-sm font-monospace fw-bold" value="1" min="1" placeholder="End #" oninput="onSeriesModalStartOrEndChange('end')">
                            </div>
                            <div class="col-lg-3 col-md-4 col-6">
                                <label class="form-label small fw-semibold text-muted mb-1 d-flex justify-content-between">
                                    <span><i class="bi bi-sort-numeric-down text-primary"></i> Digit Length</span>
                                    <span class="text-primary font-monospace fw-bold" id="seriesModalDigitPreview">00000001</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" id="seriesModalPadding" class="form-control form-control-sm font-monospace fw-bold text-center" value="8" min="1" max="10" placeholder="8" oninput="renderSeriesModalList()">
                                    <span class="input-group-text small text-muted">Digits</span>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <i class="bi bi-tag text-primary"></i> Series Tag
                                </label>
                                <input type="text" id="seriesModalPrefix" class="form-control form-control-sm font-monospace text-uppercase" value="" placeholder="e.g. S (Optional)" oninput="renderSeriesModalList()">
                            </div>
                            <div class="col-lg-3 col-md-8 col-12">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <i class="bi bi-diagram-2 text-primary"></i> Sequence Mode
                                </label>
                                <div class="d-flex gap-1">
                                    <select id="seriesModalSeqMode" class="form-select form-select-sm" onchange="renderSeriesModalList()">
                                        <option value="CONTINUOUS" selected>Continuous / Contiguous</option>
                                        <option value="PER_VARIANT">Restart per Variant</option>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-primary px-2" onclick="renderSeriesModalList()" title="Refresh / Recalculate series">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Number Series Breakdown Table -->
                <div class="card border-0 shadow-xs rounded-3 mb-3">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold small text-dark"><i class="bi bi-list-ol me-1 text-primary"></i> Generated Number Series for Variant Batches:</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="modalSeriesTotalVariantsBadge">0 Variants</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 320px;">
                            <table class="table table-hover table-bordered align-middle mb-0" id="modalSeriesBreakdownTable">
                                <thead class="table-light sticky-top small text-muted">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">#</th>
                                        <th style="min-width: 140px;">Variant SKU / Spec</th>
                                        <th style="width: 80px;" class="text-center">Pack Qty</th>
                                        <th style="min-width: 170px;">Start Code</th>
                                        <th style="min-width: 170px;">End Code</th>
                                        <th style="min-width: 130px;" class="text-center">Series Range</th>
                                    </tr>
                                </thead>
                                <tbody id="modalSeriesTableBody">
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top py-2 px-3 d-flex justify-content-between align-items-center small">
                        <span class="text-muted">Total Batch Units: <strong class="text-dark" id="modalSeriesTotalUnits">0 pcs</strong></span>
                        <span class="text-muted">Overall Series Span: <strong class="text-primary font-monospace" id="modalSeriesOverallSpan">—</strong></span>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 mb-0 small rounded-3 border-info-subtle d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill text-info fs-5 flex-shrink-0"></i>
                    <div>
                        Clicking <strong>"Apply Range Codes to Matrix"</strong> will immediately write these sequential start and end range codes into your product matrix table.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3 px-4 rounded-bottom-4 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i> Close
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success fw-bold px-4 shadow-sm" id="btnApplySeriesFromModal" onclick="applyGeneratedSeriesToMatrix()">
                        <i class="bi bi-check2-circle me-1"></i> Apply Range Codes to Matrix
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MASTER SERIES / BUNDLE QR PREVIEW MODAL -->
<div class="modal fade" id="modalPreviewSeriesQR" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold text-white mb-0">
                    <i class="bi bi-qr-code-scan me-2"></i> Master Series / Range QR Label Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="alert alert-info bg-white border border-info-subtle shadow-xs rounded-3 py-2 px-3 mb-3 d-flex align-items-center gap-2 small">
                    <i class="bi bi-info-circle-fill text-info fs-5"></i>
                    <div>
                        <strong>Small Garment / Bundle Pack QR Solution:</strong> This single master QR label represents the entire batch series of products. When scanned in Inwarding, Stock, or POS, the system automatically registers the full pack quantity and range.
                    </div>
                </div>

                <!-- Printable Master Series Label Card Preview -->
                <div class="d-flex justify-content-center">
                    <div id="masterSeriesPrintableCard" class="bg-white border border-dark rounded-3 p-3 shadow-sm position-relative" style="width: 480px; max-width: 100%; font-family: 'Segoe UI', monospace; color: #111;">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-uppercase tracking-wider text-dark" style="letter-spacing: 0.5px;">GRM WAREHOUSE &amp; STORES</h6>
                                <small class="text-muted" id="modalSeriesHierarchyText" style="font-size: 11px;">APPAREL &rsaquo; SLEEPWEAR</small>
                            </div>
                            <span class="badge bg-dark text-white font-monospace px-2 py-1" id="modalSeriesPackTypeBadge">MASTER BUNDLE PACK</span>
                        </div>

                        <!-- Main Body: QR & Details -->
                        <div class="row g-2 align-items-center">
                            <!-- QR Code Box -->
                            <div class="col-5 text-center">
                                <div id="seriesQRCanvas" class="p-2 border border-secondary border-opacity-25 rounded bg-light d-inline-block shadow-xs"></div>
                                <div class="font-monospace fw-bold mt-1 text-dark" id="modalSeriesSkuText" style="font-size: 11px; word-break: break-all;">SKU-001</div>
                            </div>

                            <!-- Product & Series Specs -->
                            <div class="col-7">
                                <div class="fw-bold fs-6 text-dark text-uppercase mb-1" id="modalSeriesProdName">PRODUCT STYLE</div>
                                
                                <div class="d-flex gap-2 mb-2">
                                    <span class="badge bg-secondary px-2 py-1" id="modalSeriesSizeBadge">Size: M</span>
                                    <span class="badge bg-secondary-subtle text-dark border px-2 py-1" id="modalSeriesColorBadge">Color: Blue</span>
                                </div>

                                <!-- Series Range Callout Box -->
                                <div class="p-2 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded mb-2">
                                    <div class="small text-uppercase text-primary fw-bold" style="font-size: 10px;">★ Batch Series Range ★</div>
                                    <div class="d-flex justify-content-between font-monospace fw-bold text-dark mt-1" style="font-size: 11px;">
                                        <span>Start: <span class="text-primary" id="modalSeriesStartCodeText">WMF-001</span></span>
                                        <span>➔</span>
                                        <span>End: <span class="text-primary" id="modalSeriesEndCodeText">WMF-050</span></span>
                                    </div>
                                    <div class="badge bg-primary text-white mt-1 w-100 py-1 fw-bold fs-6" id="modalSeriesTotalQtyBadge">
                                        TOTAL: 50 PCS BUNDLE
                                    </div>
                                </div>

                                <!-- Price info -->
                                <div class="d-flex justify-content-between align-items-center border-top pt-1 small">
                                    <span class="text-muted">Unit MRP: <strong class="text-dark" id="modalSeriesUnitMrp">₹599.00</strong></span>
                                    <span class="text-muted">Pack MRP: <strong class="text-success fw-bold" id="modalSeriesTotalMrp">₹29,950.00</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Bar -->
                        <div class="border-top mt-2 pt-1 d-flex justify-content-between align-items-center text-muted" style="font-size: 10px;">
                            <span>Source: <strong id="modalSeriesSourceType">Third-Party Vendor</strong></span>
                            <span>Gen: <?= date('d/m/Y H:i') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm" onclick="printMasterSeriesFromModal()">
                    <i class="bi bi-printer-fill me-1"></i> Print Master Series Label
                </button>
            </div>
        </div>
    </div>
</div>

<!-- INDIVIDUAL PIECE STICKER QR PREVIEW MODAL -->
<div class="modal fade" id="modalPreviewIndividualQR" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold text-white mb-0">
                    <i class="bi bi-qr-code me-2"></i> Individual Piece QR Sticker Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="alert alert-secondary bg-white border shadow-xs rounded-3 py-2 px-3 mb-3 d-flex align-items-center gap-2 small">
                    <i class="bi bi-tag-fill text-primary fs-5"></i>
                    <div>
                        <strong>Individual Garment Barcode Sticker:</strong> Standard garment sticker (1 QR code per individual piece).
                    </div>
                </div>

                <!-- Printable Individual Piece Label Card Preview -->
                <div class="d-flex justify-content-center">
                    <div id="individualPiecePrintableCard" class="bg-white border border-dark rounded-3 p-3 shadow-sm position-relative" style="width: 380px; max-width: 100%; font-family: 'Segoe UI', monospace; color: #111;">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-uppercase text-dark" style="letter-spacing: 0.5px;">GRM WAREHOUSE &amp; STORES</h6>
                                <small class="text-muted" id="modalIndivHierarchyText" style="font-size: 11px;">APPAREL &rsaquo; GARMENTS</small>
                            </div>
                            <span class="badge bg-secondary text-white font-monospace px-2 py-1">PIECE STICKER</span>
                        </div>

                        <!-- Main Body: QR & Details -->
                        <div class="row g-2 align-items-center">
                            <!-- QR Code Box -->
                            <div class="col-5 text-center">
                                <div id="indivQRCanvas" class="p-2 border border-secondary border-opacity-25 rounded bg-light d-inline-block shadow-xs"></div>
                                <div class="font-monospace fw-bold mt-1 text-dark" id="modalIndivSkuText" style="font-size: 11px; word-break: break-all;">SKU-001</div>
                            </div>

                            <!-- Product Specs -->
                            <div class="col-7">
                                <div class="fw-bold fs-6 text-dark text-uppercase mb-1" id="modalIndivProdName">PRODUCT STYLE</div>
                                
                                <div class="d-flex gap-1 mb-2">
                                    <span class="badge bg-dark px-2 py-1" id="modalIndivSizeBadge">Size: M</span>
                                    <span class="badge bg-light text-dark border px-2 py-1" id="modalIndivColorBadge">Color: Blue</span>
                                </div>

                                <!-- Price info -->
                                <div class="p-2 bg-light border rounded">
                                    <div class="d-flex justify-content-between align-items-center small">
                                        <span class="text-muted">MRP:</span>
                                        <strong class="text-dark" id="modalIndivUnitMrp">₹599.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center small mt-1">
                                        <span class="text-muted">Price:</span>
                                        <strong class="text-success fw-bold" id="modalIndivSellingPrice">₹499.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Bar -->
                        <div class="border-top mt-2 pt-1 d-flex justify-content-between align-items-center text-muted" style="font-size: 10px;">
                            <span>Source: <strong id="modalIndivSourceType">Third-Party Vendor</strong></span>
                            <span>Gen: <?= date('d/m/Y H:i') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-dark fw-bold px-4 shadow-sm" onclick="printIndividualPieceFromModal()">
                    <i class="bi bi-printer-fill me-1"></i> Print Piece Sticker
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.regenerateCatalogCode = function(force = false) {
    const catalogCodeInp = document.getElementById('catalog_code');
    if (!catalogCodeInp) return;
    if (!force && catalogCodeInp.dataset.userEdited && catalogCodeInp.value.trim().length > 0) return;

    const brandSelect = document.getElementById('brand_id');
    const sellerSelect = document.getElementById('seller_id');
    const isThirdParty = document.getElementById('srcPurchased')?.checked;
    let prefix = 'CAT-STY';

    if (brandSelect && brandSelect.value) {
        const brandText = brandSelect.options[brandSelect.selectedIndex]?.text || '';
        const cleanBrand = brandText.split(/[\(\-\—]/)[0].replace(/[^A-Za-z0-9]/g, '').trim().toUpperCase();
        if (cleanBrand.length >= 2) {
            prefix = 'CAT-' + cleanBrand.substring(0, 6);
        }
    } else if (isThirdParty && sellerSelect && sellerSelect.value) {
        const selText = sellerSelect.options[sellerSelect.selectedIndex]?.text || '';
        const cleanName = selText.split(/[\(\-\—]/)[0].replace(/[^A-Za-z0-9]/g, '').trim().toUpperCase();
        if (cleanName.length >= 3) {
            prefix = 'CAT-' + cleanName.substring(0, 4);
        } else {
            prefix = 'CAT-TP';
        }
    } else if (!isThirdParty) {
        prefix = 'CAT-GRM';
    }

    const baseNum = '<?= str_pad((string)(($count ?? 0) + 1), 4, '0', STR_PAD_LEFT) ?>';
    catalogCodeInp.value = prefix + '-' + baseNum;
    if (force) {
        delete catalogCodeInp.dataset.userEdited;
    }
};

function updateCombinedQRCode() {
    const catInp = document.getElementById('category_code');
    const subcatInp = document.getElementById('subcategory_code');
    const subSubcatInp = document.getElementById('sub_subcategory_code');
    const qrInp = document.getElementById('qr_code_prefix');

    if (!qrInp) return;
    const c1 = (catInp?.value || '').trim().toUpperCase();
    const c2 = (subcatInp?.value || '').trim().toUpperCase();
    const c3 = (subSubcatInp?.value || '').trim().toUpperCase();

    if (!qrInp.dataset.userEdited) {
        qrInp.value = (c1 + c2 + c3).toUpperCase();
    }
    const matrixQrBadge = document.getElementById('matrixCategoryQrPrefixBadge');
    if (matrixQrBadge) {
        matrixQrBadge.innerText = 'QR Prefix: ' + (qrInp.value || '--');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = window.location.origin + '<?= rtrim(parse_url(url(''), PHP_URL_PATH) ?? '', '/') ?>';

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
    // Active Variant Card Modal Tracker & Category Hierarchy Logic
    // -------------------------------------------------------------
    let activeCardKeyForCatModal = null;
    let masterCategoriesList = <?= json_encode(array_map(fn($c) => ['id' => $c['id'], 'name' => $c['name'], 'code' => $c['code'] ?? ''], $categories ?? [])) ?>;

    /**
     * Smart Multi-Character Short Code Generator (Option 1)
     * Generates 2-3 character distinct codes with auto-disambiguation if needed.
     * Priority 1: Registered database code (if length >= 2)
     * Priority 2: Extract first 3 alphanumeric characters from name (e.g. "MATERNITY" -> "MAT", "WOMEN" -> "WOM", "FEEDING" -> "FEE")
     * Priority 3: Disambiguate against sibling codes (e.g. "NOR", "NOR1", "NOR2")
     */
    function getCategorySmartShortCode(name, dbCode = '', siblingCodes = [], fallback = 'CAT') {
        if (dbCode && String(dbCode).trim().length >= 2) {
            return String(dbCode).trim().toUpperCase();
        }
        if (!name || typeof name !== 'string') return '';

        const clean = name.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        if (!clean) return fallback;

        let base = clean.substring(0, 3);
        if (base.length < 2) {
            base = (base + fallback).substring(0, 3);
        }

        if (Array.isArray(siblingCodes) && siblingCodes.length > 0) {
            const upperSiblings = siblingCodes.map(s => String(s).trim().toUpperCase()).filter(Boolean);
            if (upperSiblings.includes(base)) {
                let counter = 1;
                while (upperSiblings.includes(base + counter)) {
                    counter++;
                }
                return base + counter;
            }
        }

        return base;
    }

    function getActiveCategoryHierarchyPath() {
        for (const key of variantRowsList) {
            if (variantStateMap[key] && variantStateMap[key].category_path && variantStateMap[key].category_path !== 'General Category') {
                return variantStateMap[key].category_path;
            }
        }
        return 'General Category';
    }

    // -------------------------------------------------------------
    // Quick Add Main Category Modal
    // -------------------------------------------------------------
    document.getElementById('btnSaveQuickMainCat').addEventListener('click', function() {
        const name = document.getElementById('modalMainCatName').value.trim().toUpperCase();
        if (!name) {
            alert('Please enter a Main Category name.');
            return;
        }

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        const formData = new FormData();
        formData.append('name', name);

        fetch(`${baseUrl}/api/warehouse-categories/quick-add`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async res => {
            const data = await res.json().catch(() => null);
            if (!res.ok) {
                const msg = (data && data.message) ? data.message : `Server error (HTTP ${res.status})`;
                throw new Error(msg);
            }
            return data;
        })
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Category';
            if (data && data.success) {
                // Add to masterCategoriesList if not present
                if (!masterCategoriesList.some(c => String(c.id) === String(data.id))) {
                    masterCategoriesList.push({ id: data.id, name: data.name, code: data.code || '' });
                }
                // Update all .card-cat-main selects
                document.querySelectorAll('.card-cat-main').forEach(sel => {
                    let exists = Array.from(sel.options).some(opt => String(opt.value) === String(data.id));
                    if (!exists) {
                        const opt = document.createElement('option');
                        opt.value = data.id;
                        opt.textContent = data.name;
                        opt.dataset.code = data.code || '';
                        sel.appendChild(opt);
                    }
                });
                // If triggered from a specific card, select it and trigger change
                if (activeCardKeyForCatModal) {
                    const card = document.querySelector(`.variant-matrix-card[data-key="${activeCardKeyForCatModal}"]`);
                    const mainSel = card?.querySelector('.card-cat-main');
                    if (mainSel) {
                        mainSel.value = data.id;
                        mainSel.dispatchEvent(new Event('change'));
                    }
                }
                const modalEl = document.getElementById('modalAddMainCategory');
                const modalInst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInst.hide();
                document.getElementById('modalMainCatName').value = '';
            } else {
                alert((data && data.message) || 'Error adding Main Category.');
            }
        })
        .catch(err => {
            console.error('Error adding main category:', err);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Category';
            alert(err.message || 'Server error while adding Main Category.');
        });
    });

    // -------------------------------------------------------------
    // Quick Add Subcategory Modal
    // -------------------------------------------------------------
    document.getElementById('btnSaveQuickSubcat').addEventListener('click', function() {
        let catId = '';
        if (activeCardKeyForCatModal) {
            const card = document.querySelector(`.variant-matrix-card[data-key="${activeCardKeyForCatModal}"]`);
            catId = card?.querySelector('.card-cat-main')?.value || '';
        }
        if (!catId) {
            const rootCat = document.getElementById('category_id');
            if (rootCat) catId = rootCat.value;
        }
        const name = document.getElementById('modalSubcatName').value.trim().toUpperCase();
        if (!catId || !name) {
            alert('Please select a category and enter a subcategory name.');
            return;
        }

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        const formData = new FormData();
        formData.append('category_id', catId);
        formData.append('name', name);

        fetch(`${baseUrl}/api/warehouse-subcategories/quick-add`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async res => {
            const data = await res.json().catch(() => null);
            if (!res.ok) {
                const msg = (data && data.message) ? data.message : `Server error (HTTP ${res.status})`;
                throw new Error(msg);
            }
            return data;
        })
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Sub Category';
            if (data && data.success) {
                if (activeCardKeyForCatModal) {
                    const card = document.querySelector(`.variant-matrix-card[data-key="${activeCardKeyForCatModal}"]`);
                    const subSel = card?.querySelector('.card-cat-sub');
                    if (subSel) {
                        let exists = Array.from(subSel.options).some(opt => String(opt.value) === String(data.id));
                        if (!exists) {
                            const opt = document.createElement('option');
                            opt.value = data.id;
                            opt.textContent = data.name;
                            opt.dataset.code = data.code || '';
                            subSel.appendChild(opt);
                        }
                        subSel.value = data.id;
                        subSel.disabled = false;
                        subSel.dispatchEvent(new Event('change'));
                    }
                }
                const modalEl = document.getElementById('modalAddSubcategory');
                const modalInst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInst.hide();
                document.getElementById('modalSubcatName').value = '';
            } else {
                alert((data && data.message) || 'Error adding subcategory.');
            }
        })
        .catch(err => {
            console.error('Error adding subcategory:', err);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Sub Category';
            alert(err.message || 'Server error while adding Subcategory.');
        });
    });

    // -------------------------------------------------------------
    // Quick Add Sub-Subcategory Modal
    // -------------------------------------------------------------
    document.getElementById('btnSaveQuickSubSubcat').addEventListener('click', function() {
        let subcatId = '';
        if (activeCardKeyForCatModal) {
            const card = document.querySelector(`.variant-matrix-card[data-key="${activeCardKeyForCatModal}"]`);
            subcatId = card?.querySelector('.card-cat-sub')?.value || '';
        }
        if (!subcatId) {
            const rootSub = document.getElementById('subcategory_id');
            if (rootSub) subcatId = rootSub.value;
        }
        const name = document.getElementById('modalSubSubcatName').value.trim().toUpperCase();
        if (!subcatId || !name) {
            alert('Please select a subcategory and enter a sub-subcategory name.');
            return;
        }

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        const formData = new FormData();
        formData.append('subcategory_id', subcatId);
        formData.append('name', name);

        fetch(`${baseUrl}/api/warehouse-sub-subcategories/quick-add`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async res => {
            const data = await res.json().catch(() => null);
            if (!res.ok) {
                const msg = (data && data.message) ? data.message : `Server error (HTTP ${res.status})`;
                throw new Error(msg);
            }
            return data;
        })
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Sub Sub Category';
            if (data && data.success) {
                if (activeCardKeyForCatModal) {
                    const card = document.querySelector(`.variant-matrix-card[data-key="${activeCardKeyForCatModal}"]`);
                    const subSubSel = card?.querySelector('.card-cat-subsub');
                    if (subSubSel) {
                        let exists = Array.from(subSubSel.options).some(opt => String(opt.value) === String(data.id));
                        if (!exists) {
                            const opt = document.createElement('option');
                            opt.value = data.id;
                            opt.textContent = data.name;
                            opt.dataset.code = data.code || '';
                            subSubSel.appendChild(opt);
                        }
                        subSubSel.value = data.id;
                        subSubSel.disabled = false;
                        subSubSel.dispatchEvent(new Event('change'));
                    }
                }
                const modalEl = document.getElementById('modalAddSubSubcategory');
                const modalInst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInst.hide();
                document.getElementById('modalSubSubcatName').value = '';
            } else {
                alert((data && data.message) || 'Error adding sub-subcategory.');
            }
        })
        .catch(err => {
            console.error('Error adding sub-subcategory:', err);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Sub Sub Category';
            alert(err.message || 'Server error while adding Sub-Subcategory.');
        });
    });

    // -------------------------------------------------------------
    // Quick Add & Delete Size Attribute Handlers
    // -------------------------------------------------------------


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
                // Add to modal size dropdown
                const sizeSelect = document.getElementById('modalVarSizeSelect');
                if (sizeSelect) {
                    const opt = new Option(data.code + (data.name !== data.code ? ' (' + data.name + ')' : ''), data.id, true, true);
                    opt.dataset.code = data.code;
                    opt.dataset.name = data.name;
                    sizeSelect.add(opt);
                    sizeSelect.value = data.id;
                    if (typeof onModalVarAttributeChange === 'function') onModalVarAttributeChange();
                }

                const modalSizeEl = document.getElementById('modalAddSize');
                if (window.bootstrap && bootstrap.Modal) {
                    const inst = bootstrap.Modal.getInstance(modalSizeEl) || (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalSizeEl) : new bootstrap.Modal(modalSizeEl));
                    if (inst) inst.hide();
                } else {
                    modalSizeEl?.querySelector('[data-bs-dismiss="modal"]')?.click();
                }
                document.getElementById('modalSizeCode').value = '';
                document.getElementById('modalSizeName').value = '';
            } else {
                alert((data && data.message) ? data.message : 'Error adding size.');
            }
        })
        .catch(err => {
            console.error('Error adding size:', err);
            alert('Error adding size: ' + err.message);
        });
    });

    // -------------------------------------------------------------
    // Quick Add Color Attribute Handlers
    // -------------------------------------------------------------
    const modalColorPicker = document.getElementById('modalColorPicker');
    const modalColorHex = document.getElementById('modalColorHex');

    if (modalColorPicker && modalColorHex) {
        modalColorPicker.addEventListener('input', function() {
            modalColorHex.value = this.value.toUpperCase();
        });
        modalColorHex.addEventListener('input', function() {
            modalColorPicker.value = this.value;
        });
    }

    const knownColorHexMap = <?= json_encode(array_reduce($colors ?? [], function($acc, $c) { $acc[strtoupper(trim($c['name']))] = $c['hex_code'] ?? '#000000'; return $acc; }, [])) ?>;
    document.getElementById('modalColorName')?.addEventListener('input', function() {
        const val = this.value.trim().toUpperCase();
        if (knownColorHexMap && knownColorHexMap[val]) {
            if (modalColorPicker) modalColorPicker.value = knownColorHexMap[val];
            if (modalColorHex) modalColorHex.value = knownColorHexMap[val];
        }
    });

    document.getElementById('btnSaveQuickColor')?.addEventListener('click', function() {
        const name = document.getElementById('modalColorName').value.trim().toUpperCase();
        const hex = modalColorHex ? (modalColorHex.value.trim().toUpperCase() || '#000000') : '#000000';
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
        .then(res => {
            if (!res.ok) {
                return res.json().catch(() => ({ success: false, message: `Server error HTTP ${res.status}` }));
            }
            return res.json();
        })
        .then(data => {
            if (data && data.success) {
                // Add to modal color datalist & update inputs
                const colorDatalist = document.getElementById('modalVarColorDatalist');
                if (colorDatalist) {
                    const opt = document.createElement('option');
                    opt.value = data.name;
                    opt.dataset.id = data.id;
                    opt.dataset.hex = data.hex_code;
                    colorDatalist.appendChild(opt);
                }

                const varColorInp = document.getElementById('modalVarColorInput');
                if (varColorInp) varColorInp.value = data.name;

                const varColorPick = document.getElementById('modalVarColorPicker');
                if (varColorPick && data.hex_code) varColorPick.value = data.hex_code;

                const varColorHex = document.getElementById('modalVarColorHex');
                if (varColorHex && data.hex_code) varColorHex.value = data.hex_code;

                if (typeof onModalVarAttributeChange === 'function') onModalVarAttributeChange();

                const modalColorEl = document.getElementById('modalAddColor');
                if (window.bootstrap && bootstrap.Modal) {
                    const inst = bootstrap.Modal.getInstance(modalColorEl) || (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalColorEl) : new bootstrap.Modal(modalColorEl));
                    if (inst) inst.hide();
                } else {
                    modalColorEl?.querySelector('[data-bs-dismiss="modal"]')?.click();
                }
                document.getElementById('modalColorName').value = '';
            } else {
                alert((data && data.message) ? data.message : 'Error adding color.');
            }
        })
        .catch(err => {
            console.error('Error adding color:', err);
            alert('Error adding color: ' + err.message);
        });
    });



    // -------------------------------------------------------------
    // Source Type Toggle (Third-Party vs In-House Production)
    // -------------------------------------------------------------
    const srcPurchased = document.getElementById('srcPurchased');
    const srcProduction = document.getElementById('srcProduction');
    const sellerDropdownWrapper = document.getElementById('sellerDropdownWrapper');
    const sellerRequiredMark = document.getElementById('sellerRequiredMark');
    const sourceHelpText = document.getElementById('sourceHelpText');
    const codeInp = document.getElementById('code');
    const codeHelpText = document.getElementById('codeHelpText');

    function toggleSourceType() {
        const isThirdParty = srcPurchased && srcPurchased.checked;
        if (sellerDropdownWrapper) {
            if (isThirdParty) {
                sellerDropdownWrapper.style.display = 'block';
                if (sellerRequiredMark) sellerRequiredMark.style.display = 'inline';
                if (sourceHelpText) sourceHelpText.innerText = 'Procured directly from outside manufacturers, suppliers, or third-party sellers.';
                if (codeInp && !codeInp.dataset.userEdited) {
                    codeInp.placeholder = 'TP-STY-0001';
                }
                if (codeHelpText) {
                    codeHelpText.innerHTML = 'Leave empty to auto-generate (e.g. <code>TP-STY-0001</code>).';
                }
            } else {
                sellerDropdownWrapper.style.display = 'none';
                const sellerSelect = document.getElementById('seller_id');
                if (sellerSelect) sellerSelect.value = '';
                if (sellerRequiredMark) sellerRequiredMark.style.display = 'none';
                if (sourceHelpText) sourceHelpText.innerText = 'Manufactured in-house through GRM garment production units.';
                if (codeInp && !codeInp.dataset.userEdited) {
                    codeInp.placeholder = 'GRM-STY-0001';
                }
                if (codeHelpText) {
                    codeHelpText.innerHTML = 'Leave empty to auto-generate (e.g. <code>GRM-STY-0001</code>).';
                }
            }
        }
        if (typeof window.regenerateCatalogCode === 'function') {
            window.regenerateCatalogCode(false);
        }
    }

    if (srcPurchased && srcProduction) {
        srcPurchased.addEventListener('change', toggleSourceType);
        srcProduction.addEventListener('change', toggleSourceType);
        toggleSourceType();
    }

    if (codeInp) {
        codeInp.addEventListener('input', function() {
            this.dataset.userEdited = this.value.trim().length > 0 ? '1' : '';
            renderVariantMatrix();
        });
    }

    const catalogCodeInput = document.getElementById('catalog_code');
    if (catalogCodeInput) {
        catalogCodeInput.addEventListener('input', function() {
            this.dataset.userEdited = this.value.trim().length > 0 ? '1' : '';
        });
    }

    // -------------------------------------------------------------
    // Quick Add Brand AJAX Handler
    // -------------------------------------------------------------
    const btnSaveQuickBrand = document.getElementById('btnSaveQuickBrand');
    if (btnSaveQuickBrand) {
        btnSaveQuickBrand.addEventListener('click', function() {
            const nameInp = document.getElementById('modalBrandName');
            const name = nameInp ? nameInp.value.trim().toUpperCase() : '';
            if (!name) {
                alert('Please enter a Brand Name.');
                return;
            }

            const formData = new FormData();
            formData.append('name', name);

            btnSaveQuickBrand.disabled = true;
            btnSaveQuickBrand.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            fetch(`${baseUrl}/api/brands/quick-add`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSaveQuickBrand.disabled = false;
                btnSaveQuickBrand.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Brand';
                if (data.success) {
                    const brandSelect = document.getElementById('brand_id');
                    if (brandSelect) {
                        const opt = new Option(data.name, data.id, true, true);
                        brandSelect.add(opt);
                    }
                    const modalEl = document.getElementById('modalAddBrand');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    if (nameInp) nameInp.value = '';
                } else {
                    alert(data.message || 'Error adding brand.');
                }
            })
            .catch(err => {
                console.error('Error adding brand:', err);
                btnSaveQuickBrand.disabled = false;
                btnSaveQuickBrand.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Brand';
                alert('Server error while adding brand.');
            });
        });
    }

    // -------------------------------------------------------------
    // Quick Add Seller / Supplier AJAX Handler
    // -------------------------------------------------------------
    const btnSaveQuickSeller = document.getElementById('btnSaveQuickSeller');
    if (btnSaveQuickSeller) {
        btnSaveQuickSeller.addEventListener('click', function() {
            const nameInp = document.getElementById('modalSellerName');
            const companyInp = document.getElementById('modalSellerCompany');
            const phoneInp = document.getElementById('modalSellerPhone');
            const cityInp = document.getElementById('modalSellerCity');
            const gstInp = document.getElementById('modalSellerGst');

            const name = nameInp ? nameInp.value.trim() : '';
            if (!name) {
                alert('Please enter Supplier / Contact Name.');
                return;
            }

            const formData = new FormData();
            formData.append('name', name);
            if (companyInp) formData.append('company', companyInp.value.trim());
            if (phoneInp) formData.append('phone', phoneInp.value.trim());
            if (cityInp) formData.append('city', cityInp.value.trim());
            if (gstInp) formData.append('gst_number', gstInp.value.trim());

            btnSaveQuickSeller.disabled = true;
            btnSaveQuickSeller.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            fetch(`${baseUrl}/api/warehouse/sellers/quick-add`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSaveQuickSeller.disabled = false;
                btnSaveQuickSeller.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Supplier';
                if (data.success) {
                    const sellerSelect = document.getElementById('seller_id');
                    if (sellerSelect) {
                        const label = data.name + (data.company ? ' (' + data.company + ')' : '');
                        const opt = new Option(label, data.id, true, true);
                        sellerSelect.add(opt);
                        sellerSelect.dispatchEvent(new Event('change'));
                    }
                    const modalEl = document.getElementById('modalAddSeller');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    if (nameInp) nameInp.value = '';
                    if (companyInp) companyInp.value = '';
                    if (phoneInp) phoneInp.value = '';
                    if (cityInp) cityInp.value = '';
                    if (gstInp) gstInp.value = '';
                } else {
                    alert(data.message || 'Error adding supplier.');
                }
            })
            .catch(err => {
                console.error('Error adding supplier:', err);
                btnSaveQuickSeller.disabled = false;
                btnSaveQuickSeller.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Supplier';
                alert('Server error while adding supplier.');
            });
        });
    }

    // -------------------------------------------------------------
    // Dynamic Seller & Brand Cascading & Supplier Products Matrix Loader
    // -------------------------------------------------------------
    const sellerSelectEl = document.getElementById('seller_id');
    const brandSelectEl = document.getElementById('brand_id');

    let variantRowsList = [];
    const variantStateMap = {};
    const variantMatrixTableBody = document.getElementById('variantMatrixTableBody');
    const lblMatrixTotalQty = document.getElementById('lblMatrixTotalQty');
    const lblFooterTotalQty = document.getElementById('lblFooterTotalQty');
    const lblFooterTotalValuation = document.getElementById('lblFooterTotalValuation');
    const lblFooterTotalWeight = document.getElementById('lblFooterTotalWeight');
    const section2QtyInput = document.getElementById('quantity');
    const section2WeightInput = document.getElementById('weight');
    const section2CostInput = document.getElementById('default_purchase_price');
    const section2MrpInput = document.getElementById('default_mrp');
    const section2SellingInput = document.getElementById('default_selling_price');



    if (sellerSelectEl) {
        sellerSelectEl.addEventListener('change', function() {
            const sId = this.value;
            const tplWrapper = document.getElementById('sellerTemplateControlWrapper');
            if (tplWrapper) {
                if (sId) {
                    tplWrapper.style.setProperty('display', 'flex', 'important');
                } else {
                    tplWrapper.style.setProperty('display', 'none', 'important');
                }
            }

            if (!sId) return;

            const selectedOptionText = this.options[this.selectedIndex]?.text || '';
            const sellerCleanName = selectedOptionText.split('(')[0].split('-')[0].trim().replace(/[^A-Za-z0-9]/g, '').substring(0, 4).toUpperCase();

            // Auto-suggest style code if not custom edited
            if (codeInp && !codeInp.dataset.userEdited) {
                const prefix = sellerCleanName ? `TP-${sellerCleanName}-` : 'TP-STY-';
                codeInp.placeholder = prefix + '0001';
            }

            // Auto-suggest catalog code if not custom edited
            const catalogCodeInp = document.getElementById('catalog_code');
            if (catalogCodeInp && !catalogCodeInp.dataset.userEdited) {
                const catPrefix = sellerCleanName ? `CAT-${sellerCleanName}` : 'CAT-STY';
                const baseNum = '<?= str_pad((string)(($count ?? 0) + 1), 4, '0', STR_PAD_LEFT) ?>';
                catalogCodeInp.value = `${catPrefix}-${baseNum}`;
            }

            // Dynamically load brands for this supplier and auto-select matching brand
            fetch(`${baseUrl}/api/warehouse/seller-catalog?seller_id=${sId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.brands && data.brands.length > 0 && brandSelectEl) {
                        const topBrand = data.brands[0].trim().toUpperCase();
                        for (let i = 0; i < brandSelectEl.options.length; i++) {
                            if (brandSelectEl.options[i].text.trim().toUpperCase() === topBrand) {
                                brandSelectEl.selectedIndex = i;
                                break;
                            }
                        }
                    }
                })
                .catch(e => {
                    console.warn('Could not fetch supplier catalog brands:', e);
                });
        });

        // If a seller is already selected on page load (e.g. restore/preselect), trigger change
        if (sellerSelectEl.value) {
            const tplWrapper = document.getElementById('sellerTemplateControlWrapper');
            if (tplWrapper) tplWrapper.style.setProperty('display', 'flex', 'important');
            setTimeout(() => {
                sellerSelectEl.dispatchEvent(new Event('change'));
            }, 100);
        }
    }

    window.loadSupplierTemplateForMatrix = function() {
        const sId = sellerSelectEl ? sellerSelectEl.value : '';
        if (!sId) {
            alert('Please select a Seller / Supplier first.');
            if (sellerSelectEl) sellerSelectEl.focus();
            return;
        }

        const btns = document.querySelectorAll('#btnAutoLoadSupplierTemplate, .btn-auto-load-template');
        btns.forEach(b => {
            b.disabled = true;
            b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Loading...';
        });

        fetch(`${baseUrl}/api/warehouse/seller-catalog?seller_id=${sId}`)
            .then(res => res.json())
            .then(data => {
                btns.forEach(b => {
                    b.disabled = false;
                    b.innerHTML = '<i class="bi bi-magic me-1"></i> Auto-Load Template';
                });
                if (data.success && data.variants && data.variants.length > 0) {
                    const firstProduct = (data.products && data.products.length > 0) ? data.products[0] : null;
                    const firstVar = data.variants[0];

                    // Check if supplier product was marked complementary
                    const isCompProduct = (firstProduct && (firstProduct.is_complementary == 1 || firstProduct.is_complementary == '1')) || data.variants.some(v => v.is_complementary == 1 || v.is_complementary == '1');
                    if (isCompProduct) {
                        const compSwitch = document.getElementById('catalogIsComplementary');
                        if (compSwitch) {
                            compSwitch.checked = true;
                            if (typeof onToggleComplementaryMode === 'function') {
                                onToggleComplementaryMode(true);
                            }
                        }
                    }

                    // Auto-fill Product Style Name if empty
                    const nameInp = document.getElementById('name');
                    if (nameInp && !nameInp.value.trim() && firstProduct && firstProduct.product_name) {
                        nameInp.value = firstProduct.product_name.toUpperCase();
                    }

                    // Auto-fill Material
                    const matInp = document.getElementById('material');
                    const matVal = (firstProduct && firstProduct.material) ? firstProduct.material : (firstVar.material || '');
                    if (matInp && !matInp.value && matVal) {
                        matInp.value = matVal.toUpperCase();
                    }

                    // Auto-fill HSN Code
                    const hsnInp = document.getElementById('hsn_code');
                    const hsnVal = (firstProduct && firstProduct.hsn_code) ? firstProduct.hsn_code : (firstVar.hsn_code || '');
                    if (hsnInp && !hsnInp.value && hsnVal) {
                        hsnInp.value = hsnVal.toUpperCase();
                    }

                    // Populate variant matrix with BRAND NEW UNIQUE SKUs
                    variantRowsList = [];
                    const prodCode = ((codeInp && codeInp.value.trim()) || (codeInp ? codeInp.placeholder : '') || 'TP-STY').toUpperCase();

                    data.variants.forEach((v, idx) => {
                        const key = `tpl_${Date.now()}_${idx}`;
                        variantRowsList.push(key);
                        const vQty = parseInt(v.quantity) > 0 ? parseInt(v.quantity) : (parseInt(v.pack_quantity) > 0 ? parseInt(v.pack_quantity) : 1);
                        const vQrType = (v.qr_type === 'SERIES_BATCH') ? 'SERIES_BATCH' : 'INDIVIDUAL';
                        const sCode = (v.size_code || v.size_name || 'STD').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
                        const cShort = (v.color_name ? v.color_name.replace(/[^A-Za-z0-9]/g, '').substring(0, 4) : 'COL').toUpperCase();
                        const uniqueSeq = Math.floor(100 + Math.random() * 900);
                        const freshSku = `${prodCode}-${sCode}-${cShort}-${uniqueSeq}`;

                        // Extract category details
                        const catId = v.category_id || (firstProduct ? firstProduct.category_id : '') || '';
                        const catName = v.category_name || (firstProduct ? firstProduct.category_name : '') || '';
                        let catCode = v.category_code || (firstProduct ? firstProduct.category_code : '') || '';
                        if (!catCode || catCode.length <= 1) {
                            const m = masterCategoriesList.find(c => String(c.id) === String(catId));
                            catCode = getCategorySmartShortCode(catName, m?.code || '', [], 'CAT');
                        }
                        const subcatId = v.subcategory_id || (firstProduct ? firstProduct.subcategory_id : '') || '';
                        const subcatName = v.subcategory_name || (firstProduct ? firstProduct.subcategory_name : '') || '';
                        let subcatCode = v.subcategory_code || (firstProduct ? firstProduct.subcategory_code : '') || '';
                        if (!subcatCode || subcatCode.length <= 1) {
                            subcatCode = getCategorySmartShortCode(subcatName, '', [], 'SUB');
                        }
                        const subSubcatId = v.sub_subcategory_id || (firstProduct ? firstProduct.sub_subcategory_id : '') || '';
                        const subSubcatName = v.sub_subcategory_name || (firstProduct ? firstProduct.sub_subcategory_name : '') || '';
                        let subSubcatCode = v.sub_subcategory_code || (firstProduct ? firstProduct.sub_subcategory_code : '') || '';
                        if (!subSubcatCode || subSubcatCode.length <= 1) {
                            subSubcatCode = getCategorySmartShortCode(subSubcatName, '', [], 'SSC');
                        }

                        variantStateMap[key] = {
                            is_autoloaded: true,
                            category_id: catId,
                            category_name: catName,
                            category_code: catCode,
                            subcategory_id: subcatId,
                            subcategory_name: subcatName,
                            subcategory_code: subcatCode,
                            sub_subcategory_id: subSubcatId,
                            sub_subcategory_name: subSubcatName,
                            sub_subcategory_code: subSubcatCode,
                            qr_code_prefix: (catCode + subcatCode + subSubcatCode).toUpperCase() || prodCode,
                            size_id: v.size_id || '',
                            size_code: v.size_code || v.size_name || 'Standard',
                            size_name: v.size_name || v.size_code || 'Standard',
                            color_id: v.color_id || '',
                            color_name: v.color_name || '',
                            color_hex: v.color_hex || '#000000',
                            sku: freshSku,
                            hsn_code: v.hsn_code || (firstProduct ? firstProduct.hsn_code : '') || '',
                            quantity: vQty,
                            weight: v.weight !== undefined && v.weight !== null ? parseFloat(v.weight).toFixed(3) : '0.000',
                            mrp: v.mrp !== undefined && v.mrp !== null ? parseFloat(v.mrp).toFixed(2) : '0.00',
                            selling_price: v.selling_price !== undefined && v.selling_price !== null ? parseFloat(v.selling_price).toFixed(2) : '0.00',
                            purchase_price: v.cost_price !== undefined && v.cost_price !== null ? parseFloat(v.cost_price).toFixed(2) : '0.00',
                            qr_type: vQrType,
                            series_start_code: String(uniqueSeq).padStart(5, '0') + '001',
                            series_end_code: String(uniqueSeq).padStart(5, '0') + String(vQty).padStart(3, '0'),
                            pack_quantity: vQty
                        };
                    });

                    // Render variants immediately into the section below
                    renderVariantMatrix();

                    // Smoothly scroll down to Section 2 (Product Variants & Pricing Matrix)
                    const matrixCard = document.getElementById('variantMatrixCard');
                    if (matrixCard) {
                        matrixCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } else {
                    alert('No previous catalog products found for this supplier. You can use the "+ Add Custom Variant Card" button to create fresh variants.');
                }
            })
            .catch(e => {
                btns.forEach(b => {
                    b.disabled = false;
                    b.innerHTML = '<i class="bi bi-magic me-1"></i> Auto-Load Template';
                });
                console.warn('Error loading supplier template:', e);
                alert('Could not load supplier products.');
            });
    };

    window.resetMatrixToFresh = function() {
        if (variantRowsList.length > 0 && !confirm('Clear the matrix to start fresh with an empty variant table?')) {
            return;
        }
        variantRowsList = [];
        for (const k in variantStateMap) delete variantStateMap[k];
        renderVariantMatrix();
    };

    // -------------------------------------------------------------
    // Variant Row Actions (Add Custom Row, Delete Row)
    // -------------------------------------------------------------
    window.removeVariantRow = function(key) {
        if (!confirm('Are you sure you want to remove this variant product from the table?')) return;
        variantRowsList = variantRowsList.filter(k => k !== key);
        delete variantStateMap[key];
        renderVariantMatrix();
        autoGenerateAllSeriesRanges(true);
    };

    // -------------------------------------------------------------
    // Add Product Variant Modal Handlers & Actions
    // -------------------------------------------------------------
    const standardColorMap = {
        'RED': '#FF0000',
        'GREEN': '#008000',
        'BLUE': '#0000FF',
        'BLACK': '#000000',
        'WHITE': '#FFFFFF',
        'YELLOW': '#FFFF00',
        'ORANGE': '#FFA500',
        'PINK': '#FFC0CB',
        'PURPLE': '#800080',
        'GREY': '#808080',
        'GRAY': '#808080',
        'BROWN': '#A52A2A',
        'NAVY': '#000080',
        'MAROON': '#800000',
        'TEAL': '#008080',
        'CYAN': '#00FFFF',
        'MAGENTA': '#FF00FF',
        'BEIGE': '#F5F5DC',
        'GOLD': '#FFD700',
        'SILVER': '#C0C0C0',
        'CORAL': '#FF7F50',
        'LAVENDER': '#E6E6FA',
        'TURQUOISE': '#40E0D0',
        'INDIGO': '#4B0082',
        'VIOLET': '#EE82EE',
        'PEACH': '#FFDAB9',
        'OLIVE': '#808000',
        'MINT': '#3EB489',
        'CREAM': '#FFFDD0',
        'MUSTARD': '#FFDB58',
        'CHARCOAL': '#36454F',
        'BURGUNDY': '#800020'
    };

    function normalizeHexColor(hex, name) {
        let h = (hex || '').trim().toUpperCase();
        if (h) {
            if (!h.startsWith('#')) h = '#' + h;
            h = '#' + h.substring(1).replace(/[^0-9A-F]/g, '');
            if (h.length === 4) {
                // #RGB -> #RRGGBB
                h = '#' + h[1] + h[1] + h[2] + h[2] + h[3] + h[3];
            } else if (h.length === 5) {
                h = h + '00';
            } else if (h.length === 6) {
                // Missing last hex digit e.g. #FF000 -> pad with 0 to make #FF0000
                h = h + '0';
            } else if (h.length > 7) {
                h = h.substring(0, 7);
            }
            if (/^#[0-9A-F]{6}$/i.test(h)) {
                return h;
            }
        }
        if (name) {
            const cleanName = name.trim().toUpperCase();
            if (standardColorMap[cleanName]) return standardColorMap[cleanName];
            for (const [sName, sHex] of Object.entries(standardColorMap)) {
                if (cleanName.includes(sName)) return sHex;
            }
        }
        return (h && /^#[0-9A-F]{6}$/i.test(h)) ? h : '';
    }

    let modalVarUserEditedSku = false;
    // Module-level state for modal attribute lists (accessible by renderModalMultiVariantTable outside closure)
    let _modalSelectedSizes = [];
    let _modalSelectedColors = [];

    window.openAddVariantRowModal = function(customQrType = 'INDIVIDUAL') {
        const modalEl = document.getElementById('modalAddVariantRow');
        if (!modalEl) return;

        modalVarUserEditedSku = false;

        // Set Packaging Type radio
        const isSeries = (customQrType === 'SERIES_BATCH');
        const radioIndiv = document.getElementById('modalVarQrIndiv');
        const radioSeries = document.getElementById('modalVarQrSeries');
        if (isSeries && radioSeries) {
            radioSeries.checked = true;
        } else if (radioIndiv) {
            radioIndiv.checked = true;
        }
        onModalVarQrTypeChange();

        // Reset module-level attribute lists on each modal open
        _modalSelectedSizes = [];
        _modalSelectedColors = [];

        window.addModalSizeAttribute = function() {
            const sizeInp = document.getElementById('modalVarSizeInput');
            const val = (sizeInp ? sizeInp.value.trim() : '').toUpperCase();
            if (!val) return;

            const parts = val.split(/[,/]+/).map(s => s.trim().toUpperCase()).filter(Boolean);
            parts.forEach(s => {
                if (!_modalSelectedSizes.includes(s)) {
                    _modalSelectedSizes.push(s);
                }
            });

            if (sizeInp) sizeInp.value = '';
            renderModalSizeBadges();
            renderModalMultiVariantTable();
        };

        window.removeModalSizeAttribute = function(sizeCode) {
            _modalSelectedSizes = _modalSelectedSizes.filter(s => s !== sizeCode);
            renderModalSizeBadges();
            renderModalMultiVariantTable();
        };

        window.renderModalSizeBadges = function() {
            const container = document.getElementById('modalVarSizeBadges');
            if (!container) return;
            if (_modalSelectedSizes.length === 0) {
                container.innerHTML = '';
                return;
            }
            let html = '';
            _modalSelectedSizes.forEach(s => {
                html += `<span class="badge bg-secondary-subtle text-dark border d-inline-flex align-items-center gap-1 py-1 px-2 font-monospace">
                    <strong>${s}</strong>
                    <button type="button" class="btn-close btn-close-sm p-0 m-0" style="font-size: 0.65rem;" onclick="removeModalSizeAttribute('${s}')" title="Remove size"></button>
                </span>`;
            });
            container.innerHTML = html;
        };

        window.addModalColorAttribute = function() {
            const colorInp = document.getElementById('modalVarColorInput');
            const hexInp = document.getElementById('modalVarColorHex');
            const pickInp = document.getElementById('modalVarColorPicker');

            let rawVal = (colorInp ? colorInp.value.trim() : '');
            let hexVal = (hexInp ? hexInp.value.trim() : '').toUpperCase();
            if (hexVal) {
                hexVal = normalizeHexColor(hexVal, rawVal);
            }

            if (!rawVal && !hexVal) return;

            // If user typed a hex code directly in color name or name is empty
            const isHexInput = /^#?[0-9A-Fa-f]{3,6}$/.test(rawVal);
            if (isHexInput || !rawVal) {
                let chosenHex = isHexInput ? (rawVal.startsWith('#') ? rawVal : '#' + rawVal) : hexVal;
                chosenHex = normalizeHexColor(chosenHex, '');
                const colorLabel = chosenHex || 'COLOR';
                if (!_modalSelectedColors.some(c => c.name === colorLabel)) {
                    _modalSelectedColors.push({ name: colorLabel, hex: chosenHex });
                }
                if (colorInp) colorInp.value = '';
                if (hexInp) hexInp.value = '';
                if (pickInp) pickInp.value = '#000000';
                renderModalColorBadges();
                renderModalMultiVariantTable();
                return;
            }

            const val = rawVal.toUpperCase();
            const parts = val.split(/[,/]+/).map(c => c.trim().toUpperCase()).filter(Boolean);
            const colorDatalist = document.getElementById('modalVarColorDatalist');

            parts.forEach(cName => {
                let finalHex = hexVal;
                if (!finalHex && colorDatalist) {
                    const matchOpt = Array.from(colorDatalist.options).find(o => o.value.toUpperCase() === cName);
                    if (matchOpt && matchOpt.dataset.hex) finalHex = matchOpt.dataset.hex;
                }
                finalHex = normalizeHexColor(finalHex, cName);

                const exists = _modalSelectedColors.some(c => c.name === cName);
                if (!exists) {
                    _modalSelectedColors.push({ name: cName, hex: finalHex || '' });
                }
            });

            if (colorInp) colorInp.value = '';
            if (hexInp) hexInp.value = '';
            if (pickInp) pickInp.value = '#000000';
            renderModalColorBadges();
            renderModalMultiVariantTable();
        };

        window.removeModalColorAttribute = function(colorName) {
            _modalSelectedColors = _modalSelectedColors.filter(c => c.name !== colorName);
            renderModalColorBadges();
            renderModalMultiVariantTable();
        };

        window.renderModalColorBadges = function() {
            const container = document.getElementById('modalVarColorBadges');
            if (!container) return;
            if (_modalSelectedColors.length === 0) {
                container.innerHTML = '';
                return;
            }
            let html = '';
            _modalSelectedColors.forEach(c => {
                const safeHex = normalizeHexColor(c.hex, c.name);
                const dotStyle = safeHex ? `background:${safeHex};border:1px solid rgba(0,0,0,0.3);` : 'background:#6c757d;';
                html += `<span class="badge bg-white text-dark border d-inline-flex align-items-center gap-1.5 py-1 px-2 shadow-xs rounded-2">
                    <span style="display:inline-block;width:13px;height:13px;border-radius:50%;${dotStyle}" title="${safeHex || 'No color code'}"></span>
                    <span class="fw-semibold">${c.name}</span>
                    ${safeHex ? `<span class="badge bg-light text-secondary border font-monospace ms-1" style="font-size:0.75rem;">${safeHex}</span>` : ''}
                    <button type="button" class="btn-close btn-close-sm p-0 m-0 ms-1" style="font-size: 0.65rem;" onclick="removeModalColorAttribute('${c.name}')" title="Remove color"></button>
                </span>`;
            });
            container.innerHTML = html;
        };

        const mfgInp = document.getElementById('modalVarMfgDate');
        const expInp = document.getElementById('modalVarExpDate');
        const sizeInp = document.getElementById('modalVarSizeInput');
        const colorInp = document.getElementById('modalVarColorInput');
        const batchMfgInp = document.getElementById('batchFillMfg');
        const batchExpInp = document.getElementById('batchFillExp');

        if (mfgInp) mfgInp.value = '';
        if (expInp) expInp.value = '';
        if (sizeInp) sizeInp.value = '';
        if (colorInp) colorInp.value = '';
        if (batchMfgInp) batchMfgInp.value = '';
        if (batchExpInp) batchExpInp.value = '';
        const hexInp = document.getElementById('modalVarColorHex');
        const pickInp = document.getElementById('modalVarColorPicker');
        if (hexInp) hexInp.value = '';
        if (pickInp) pickInp.value = '#000000';

        renderModalSizeBadges();
        renderModalColorBadges();

        const submitBtn = document.getElementById('btnModalAddVariantSubmit');
        if (submitBtn) submitBtn.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Add Variant to Matrix Table';
        const skuHelp = document.getElementById('modalVarSkuHelp');
        if (skuHelp) skuHelp.innerText = 'Base SKU prefix for variants.';

        // Auto SKU
        generateModalVarSku(true);
        renderModalMultiVariantTable();

        const modal = bootstrap.Modal.getInstance(modalEl) || (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl));
        modal.show();
    };

    window.addNewCustomVariantRow = function(customQrType = 'INDIVIDUAL') {
        openAddVariantRowModal(customQrType);
    };

    window.onModalVarQrTypeChange = function() {
        const isSeries = document.getElementById('modalVarQrSeries')?.checked;
        const wrapper = document.getElementById('modalVarSeriesWrapper');
        if (wrapper) {
            wrapper.style.display = isSeries ? 'block' : 'none';
        }
        if (isSeries) {
            updateModalSeriesCodes();
        }
    };

    window.onModalVarColorNameInput = function() {
        const colorInp = document.getElementById('modalVarColorInput');
        const hexInp = document.getElementById('modalVarColorHex');
        const pickInp = document.getElementById('modalVarColorPicker');
        const colorName = (colorInp ? colorInp.value.trim() : '').toUpperCase();

        const colorDatalist = document.getElementById('modalVarColorDatalist');
        let foundHex = '';
        if (colorDatalist && colorName) {
            const match = Array.from(colorDatalist.options).find(o => o.value.toUpperCase() === colorName);
            if (match && match.dataset.hex) {
                foundHex = match.dataset.hex;
            }
        }

        if (!foundHex && colorName) {
            foundHex = normalizeHexColor('', colorName);
        }

        if (foundHex) {
            const safeHex = normalizeHexColor(foundHex, colorName);
            if (safeHex) {
                if (hexInp) hexInp.value = safeHex;
                if (pickInp) pickInp.value = safeHex;
            }
        }
    };

    window.onModalVarColorHexInput = function() {
        const hexInp = document.getElementById('modalVarColorHex');
        const pickInp = document.getElementById('modalVarColorPicker');
        let val = (hexInp ? hexInp.value.trim() : '').toUpperCase();
        if (val && !val.startsWith('#') && (val.length === 3 || val.length === 6)) {
            val = '#' + val;
            if (hexInp) hexInp.value = val;
        }
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            if (pickInp) pickInp.value = val;
        }
    };

    window.onModalVarColorHexBlur = function() {
        const colorInp = document.getElementById('modalVarColorInput');
        const hexInp = document.getElementById('modalVarColorHex');
        const pickInp = document.getElementById('modalVarColorPicker');
        if (!hexInp || !hexInp.value.trim()) return;
        const norm = normalizeHexColor(hexInp.value, colorInp ? colorInp.value : '');
        if (norm) {
            hexInp.value = norm;
            if (pickInp) pickInp.value = norm;
        }
    };

    window.onModalVarColorPickerInput = function() {
        const pickInp = document.getElementById('modalVarColorPicker');
        const hexInp = document.getElementById('modalVarColorHex');
        if (pickInp && hexInp) {
            hexInp.value = pickInp.value.toUpperCase();
        }
    };

    window.isComplementaryMode = false;
    window.onToggleComplementaryMode = function(isComp) {
        window.isComplementaryMode = !!isComp;
        const thMrp = document.querySelector('#modalMultiVariantTable thead th.th-mrp-col');
        if (thMrp) {
            thMrp.innerHTML = isComp 
                ? '<i class="bi bi-tag-fill text-warning me-1"></i>Selling Price (₹) <span class="text-danger">*</span>' 
                : '<i class="bi bi-tag-fill text-success me-1"></i>MRP Price (₹) <span class="text-danger">*</span>';
        }
        const quickMrp = document.getElementById('batchFillMrp');
        if (quickMrp) {
            quickMrp.placeholder = isComp ? 'Selling (₹)' : 'MRP (₹)';
            quickMrp.title = isComp ? 'Batch Selling Price' : 'Batch MRP';
        }
        document.querySelectorAll('#modalMultiVariantTableBody .modal-combo-mrp').forEach(inp => {
            inp.placeholder = isComp ? 'Selling (₹)' : '0.00';
            inp.title = isComp ? 'Selling Price (₹)' : 'MRP Price (₹)';
        });
        document.querySelectorAll('.matrix-mrp-label').forEach(lbl => {
            lbl.innerHTML = isComp 
                ? '<i class="bi bi-tag-fill text-warning me-1"></i> Selling Price (₹) <span class="text-danger">*</span>' 
                : '<i class="bi bi-tag-fill text-success me-1"></i> MRP Price (₹) <span class="text-danger">*</span>';
        });
    };

    window.renderModalMultiVariantTable = function() {
        const pendingSize = (document.getElementById('modalVarSizeInput')?.value || '').trim();
        const pendingColor = (document.getElementById('modalVarColorInput')?.value || '').trim();

        // Use module-level arrays (set by openAddVariantRowModal and add/remove handlers)
        let sizesToUse = [..._modalSelectedSizes];
        if (sizesToUse.length === 0 && pendingSize) {
            sizesToUse = pendingSize.split(/[,/]+/).map(s => s.trim().toUpperCase()).filter(Boolean);
        }
        if (sizesToUse.length === 0) sizesToUse = ['STANDARD'];

        let colorsToUse = [..._modalSelectedColors];
        if (colorsToUse.length === 0 && pendingColor) {
            const parts = pendingColor.split(/[,/]+/).map(c => c.trim().toUpperCase()).filter(Boolean);
            const colorDatalist = document.getElementById('modalVarColorDatalist');
            const pendingHex = (document.getElementById('modalVarColorHex')?.value || '').trim();
            colorsToUse = parts.map(c => {
                let h = pendingHex;
                if (!h && colorDatalist) {
                    const m = Array.from(colorDatalist.options).find(o => o.value.toUpperCase() === c);
                    if (m && m.dataset.hex) h = m.dataset.hex;
                }
                return { name: c, hex: h || '' };
            });
        }
        if (colorsToUse.length === 0) colorsToUse = [{ name: '', hex: '' }];

        const hasAnyAttributes = (_modalSelectedSizes.length > 0 || pendingSize || _modalSelectedColors.length > 0 || pendingColor);
        const tbody = document.getElementById('modalMultiVariantTableBody');
        const badgeCount = document.getElementById('modalMultiVariantBadgeCount');
        const submitBtn = document.getElementById('btnModalAddVariantSubmit');

        if (!hasAnyAttributes) {
            if (tbody) tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4 text-muted"><i class="bi bi-info-circle me-1"></i> Add Size(s) and Color(s) above and click <strong>Add</strong> or <button type="button" class="btn btn-xs btn-primary fw-semibold px-2 py-0.5 ms-1" onclick="renderModalMultiVariantTable()"><i class="bi bi-gear-wide-connected me-1"></i>Generate Variants</button> to generate variant rows.</td></tr>`;
            if (badgeCount) badgeCount.innerText = '0 Variants';
            if (submitBtn) submitBtn.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Add Variant to Matrix Table';
            updateModalBreakdownTotals();
            return;
        }

        // Preserve already typed values
        const existingMap = {};
        document.querySelectorAll('#modalMultiVariantTableBody tr[data-combo]').forEach(tr => {
            const comboKey = tr.dataset.combo;
            if (comboKey) {
                existingMap[comboKey] = {
                    sku: tr.querySelector('.modal-combo-sku')?.value || '',
                    hsn: tr.querySelector('.modal-combo-hsn')?.value || '',
                    weight: tr.querySelector('.modal-combo-weight')?.value || '',
                    qty: tr.querySelector('.modal-combo-qty')?.value || '',
                    mrp: tr.querySelector('.modal-combo-mrp')?.value || '',
                    cost: tr.querySelector('.modal-combo-cost')?.value || '',
                    mfg: tr.querySelector('.modal-combo-mfg')?.value || '',
                    exp: tr.querySelector('.modal-combo-exp')?.value || ''
                };
            }
        });

        const prodCode = ((codeInp && codeInp.value.trim()) || 'PROD').toUpperCase();
        const skuInp = document.getElementById('modalVarSku');
        const enteredSku = (skuInp ? skuInp.value.trim() : '').toUpperCase();
        const baseSkuPrefix = enteredSku.split('-')[0].trim() || prodCode;

        let html = '';
        let comboCount = 0;

        sizesToUse.forEach(sCode => {
            colorsToUse.forEach(cObj => {
                comboCount++;
                const cName = cObj.name || '';
                const colorHex = normalizeHexColor(cObj.hex, cName);
                const comboKey = `${sCode}___${cName}`;

                let comboSku = baseSkuPrefix;
                if (sCode && sCode !== 'STANDARD') comboSku += '-' + sCode.replace(/[^A-Za-z0-9]/g, '');
                if (cName) comboSku += '-' + cName.replace(/[^A-Za-z0-9]/g, '').substring(0, 4);

                const prev = existingMap[comboKey] || {};
                const rowSku = prev.sku || comboSku;
                const rowHsn = prev.hsn !== undefined ? prev.hsn : '';
                const rowWeight = prev.weight !== undefined ? prev.weight : '';
                const rowQty = (prev.qty !== undefined && prev.qty !== '') ? prev.qty : '1';
                const rowMrp = (prev.mrp !== undefined && prev.mrp !== '') ? prev.mrp : '';
                const rowCost = (prev.cost !== undefined && prev.cost !== '') ? prev.cost : '';
                const rowMfg = prev.mfg !== undefined ? prev.mfg : '';
                const rowExp = prev.exp !== undefined ? prev.exp : '';

                html += `
                <tr data-size="${sCode}" data-color="${cName}" data-hex="${colorHex}" data-combo="${comboKey}">
                    <td class="ps-3 py-2">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span class="badge bg-secondary-subtle text-dark border fw-bold font-monospace">${sCode}</span>
                            ${cName ? `<span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1.5 py-1 px-2" style="font-size:0.75rem;"><span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:${colorHex || '#6c757d'};border:1px solid rgba(0,0,0,0.25);"></span> <span class="fw-semibold">${cName}</span></span>` : '<span class="text-muted small">Standard</span>'}
                        </div>
                    </td>
                    <td class="py-1">
                        <input type="text" class="form-control form-control-sm font-monospace text-uppercase fw-semibold modal-combo-sku" value="${rowSku}" placeholder="SKU" required>
                    </td>
                    <td class="py-1 td-series-col" style="${document.getElementById('modalVarQrSeries')?.checked ? '' : 'display:none;'}">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1 modal-combo-series-badge">---</span>
                    </td>
                    <td class="py-1">
                        <input type="text" class="form-control form-control-sm text-uppercase font-monospace modal-combo-hsn" value="${rowHsn}" placeholder="e.g. 6204">
                    </td>
                    <td class="py-1 text-center">
                        <input type="number" min="0" step="0.001" class="form-control form-control-sm text-center modal-combo-weight" value="${rowWeight}" placeholder="0.000" oninput="updateModalBreakdownTotals()">
                    </td>
                    <td class="py-1 text-center">
                        <input type="number" min="1" step="1" class="form-control form-control-sm text-center fw-bold text-primary modal-combo-qty" value="${rowQty}" oninput="updateModalBreakdownTotals()" required>
                    </td>
                    <td class="py-1 text-center">
                        <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end fw-bold text-success modal-combo-mrp" value="${rowMrp}" placeholder="0.00" oninput="updateModalBreakdownTotals()" required>
                    </td>
                    <td class="py-1 text-center">
                        <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end modal-combo-cost" value="${rowCost}" placeholder="0.00">
                    </td>
                    <td class="py-1 text-center">
                        <input type="date" class="form-control form-control-sm modal-combo-mfg" value="${rowMfg}">
                    </td>
                    <td class="py-1 text-center">
                        <input type="date" class="form-control form-control-sm modal-combo-exp" value="${rowExp}">
                    </td>
                    <td class="py-1 text-center pe-2">
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 border-0" onclick="removeModalComboRow(this)" title="Remove this variant combination">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
        });

        if (tbody) tbody.innerHTML = html;
        if (badgeCount) badgeCount.innerText = `${comboCount} Variants`;
        if (submitBtn) {
            submitBtn.innerHTML = comboCount > 1
                ? `<i class="bi bi-collection-fill me-1"></i> Add ${comboCount} Variants to Matrix Table`
                : `<i class="bi bi-plus-circle me-1"></i> Add Variant to Matrix Table`;
        }

        updateModalBreakdownTotals();
        updateModalSeriesCodes(true);
        syncModalVariantRowsSeriesCodes();
    };

    window.removeModalComboRow = function(btn) {
        const tr = btn.closest('tr');
        if (tr) {
            tr.remove();
            const remaining = document.querySelectorAll('#modalMultiVariantTableBody tr[data-combo]').length;
            const badgeCount = document.getElementById('modalMultiVariantBadgeCount');
            if (badgeCount) badgeCount.innerText = `${remaining} Variants`;
            const submitBtn = document.getElementById('btnModalAddVariantSubmit');
            if (submitBtn) {
                submitBtn.innerHTML = remaining > 1
                    ? `<i class="bi bi-collection-fill me-1"></i> Add ${remaining} Variants to Matrix Table`
                    : `<i class="bi bi-plus-circle me-1"></i> Add Variant to Matrix Table`;
            }
            if (remaining === 0) {
                const tbody = document.getElementById('modalMultiVariantTableBody');
                if (tbody) tbody.innerHTML = `<tr><td colspan="11" class="text-center py-4 text-muted"><i class="bi bi-info-circle me-1"></i> Add Size(s) and Color(s) above and click <strong>Add</strong> or <button type="button" class="btn btn-xs btn-primary fw-semibold px-2 py-0.5 ms-1" onclick="renderModalMultiVariantTable()"><i class="bi bi-gear-wide-connected me-1"></i>Generate Variants</button> to generate variant rows.</td></tr>`;
            }
            updateModalBreakdownTotals();
            updateModalSeriesCodes(true);
            syncModalVariantRowsSeriesCodes();
        }
    };

    window.applyQuickFillToAllRows = function() {
        const hsn = (document.getElementById('batchFillHsn')?.value || '').trim().toUpperCase();
        const weight = (document.getElementById('batchFillWeight')?.value || '').trim();
        const qty = (document.getElementById('batchFillQty')?.value || '').trim();
        const mrp = (document.getElementById('batchFillMrp')?.value || '').trim();
        const cost = (document.getElementById('batchFillCost')?.value || '').trim();
        const mfg = (document.getElementById('batchFillMfg')?.value || '').trim();
        const exp = (document.getElementById('batchFillExp')?.value || '').trim();

        const rows = document.querySelectorAll('#modalMultiVariantTableBody tr[data-combo]');
        if (rows.length === 0) {
            alert('No variant rows available to fill. Add sizes and colors first.');
            return;
        }

        rows.forEach(tr => {
            if (hsn !== '') {
                const el = tr.querySelector('.modal-combo-hsn');
                if (el) el.value = hsn;
            }
            if (weight !== '') {
                const el = tr.querySelector('.modal-combo-weight');
                if (el) el.value = weight;
            }
            if (qty !== '') {
                const el = tr.querySelector('.modal-combo-qty');
                if (el) el.value = qty;
            }
            if (mrp !== '') {
                const el = tr.querySelector('.modal-combo-mrp');
                if (el) el.value = mrp;
            }
            if (cost !== '') {
                const el = tr.querySelector('.modal-combo-cost');
                if (el) el.value = cost;
            }
            if (mfg !== '') {
                const el = tr.querySelector('.modal-combo-mfg');
                if (el) el.value = mfg;
            }
            if (exp !== '') {
                const el = tr.querySelector('.modal-combo-exp');
                if (el) el.value = exp;
            }
        });

        updateModalBreakdownTotals();
    };

    window.updateModalBreakdownTotals = function() {
        let totalRows = 0;
        let totalQty = 0;
        let totalWeight = 0;
        let totalValuation = 0;

        document.querySelectorAll('#modalMultiVariantTableBody tr[data-combo]').forEach(tr => {
            totalRows++;
            const q = parseInt(tr.querySelector('.modal-combo-qty')?.value) || 0;
            const w = parseFloat(tr.querySelector('.modal-combo-weight')?.value) || 0;
            const p = parseFloat(tr.querySelector('.modal-combo-mrp')?.value) || 0;

            totalQty += q;
            totalWeight += (w * q);
            totalValuation += (p * q);
        });

        const rowsEl = document.getElementById('modalSummaryVariantCount');
        if (rowsEl) rowsEl.innerText = totalRows;

        const qtyEl = document.getElementById('modalMultiVariantTotalQty');
        if (qtyEl) qtyEl.innerText = totalQty;

        const weightEl = document.getElementById('modalSummaryTotalWeight');
        if (weightEl) weightEl.innerText = totalWeight > 0 ? totalWeight.toFixed(3) + ' kg' : '0.000 kg';

        const valEl = document.getElementById('modalSummaryTotalValuation');
        if (valEl) valEl.innerText = '₹' + totalValuation.toFixed(2);

        updateModalSeriesCodes(false);
        syncModalVariantRowsSeriesCodes();
    };

    window.generateModalVarSku = function(force = false) {
        const prodCode = ((codeInp && codeInp.value.trim()) || 'PROD').toUpperCase();
        const skuInp = document.getElementById('modalVarSku');
        if (skuInp && (force || !modalVarUserEditedSku)) {
            skuInp.value = prodCode;
        }
        updateModalSeriesCodes(true);
    };

    function parseSeriesCode(str) {
        if (!str) return { prefix: '', num: 1, padLen: 4 };
        const match = str.match(/^(.*?)(\d+)$/);
        if (match) {
            return {
                prefix: match[1],
                num: parseInt(match[2], 10),
                padLen: match[2].length
            };
        }
        return { prefix: str + '-', num: 1, padLen: 4 };
    }

    function formatSeriesCode(prefix, num, padLen) {
        return (prefix || '') + String(num).padStart(padLen || 1, '0');
    }

    function getModalTotalUnits() {
        const rows = document.querySelectorAll('#modalMultiVariantTableBody tr[data-combo]');
        if (rows && rows.length > 0) {
            let total = 0;
            rows.forEach(tr => {
                const qtyVal = parseInt(tr.querySelector('.modal-combo-qty')?.value) || 1;
                total += Math.max(1, qtyVal);
            });
            return total;
        }
        const sizesCount = Math.max(1, (_modalSelectedSizes && _modalSelectedSizes.length) || 1);
        const colorsCount = Math.max(1, (_modalSelectedColors && _modalSelectedColors.length) || 1);
        return sizesCount * colorsCount;
    }

    function updateModalSeriesCodes(forceEndRecalc = false) {
        const isSeries = document.getElementById('modalVarQrSeries')?.checked;
        if (!isSeries) return;

        const startInp = document.getElementById('modalVarSeriesStart');
        const endInp = document.getElementById('modalVarSeriesEnd');
        const unitsBadge = document.getElementById('modalVarSeriesUnitsBadge');
        if (!startInp || !endInp) return;

        const skuInp = document.getElementById('modalVarSku');
        let sku = (skuInp ? skuInp.value.trim() : '') || '1';
        const skuDigitsMatch = sku.match(/(\d+)(?!.*\d)/);
        const numBase = skuDigitsMatch ? (parseInt(skuDigitsMatch[1]) % 100000) : 1;

        const totalUnits = getModalTotalUnits();
        if (unitsBadge) {
            unitsBadge.innerHTML = `<i class="bi bi-boxes me-1"></i>${totalUnits} Units Total`;
        }

        if (!startInp.value.trim() && !startInp.dataset.userEdited) {
            startInp.value = String(numBase).padStart(5, '0') + '001';
        }

        const currentStart = startInp.value.trim();
        if (currentStart) {
            const parsed = parseSeriesCode(currentStart);
            const endNum = parsed.num + Math.max(1, totalUnits) - 1;
            const calculatedEnd = formatSeriesCode(parsed.prefix, endNum, parsed.padLen);

            if (forceEndRecalc || !endInp.dataset.userEdited || !endInp.value.trim()) {
                endInp.value = calculatedEnd;
            }
        }

        syncModalVariantRowsSeriesCodes();
    }

    window.onModalVarSeriesStartChange = function() {
        const startInp = document.getElementById('modalVarSeriesStart');
        const endInp = document.getElementById('modalVarSeriesEnd');
        if (startInp) {
            startInp.dataset.userEdited = '1';
            const currentStart = startInp.value.trim();
            if (currentStart && endInp) {
                const totalUnits = getModalTotalUnits();
                const parsed = parseSeriesCode(currentStart);
                const endNum = parsed.num + Math.max(1, totalUnits) - 1;
                endInp.value = formatSeriesCode(parsed.prefix, endNum, parsed.padLen);
                delete endInp.dataset.userEdited;
            }
        }
        syncModalVariantRowsSeriesCodes();
    };

    window.onModalVarSeriesEndChange = function() {
        const endInp = document.getElementById('modalVarSeriesEnd');
        if (endInp) {
            endInp.dataset.userEdited = '1';
        }
        syncModalVariantRowsSeriesCodes();
    };

    window.syncModalVariantRowsSeriesCodes = function() {
        const isSeries = document.getElementById('modalVarQrSeries')?.checked;
        const seriesCols = document.querySelectorAll('.th-series-col');
        seriesCols.forEach(col => {
            col.style.display = isSeries ? '' : 'none';
        });

        const startInp = document.getElementById('modalVarSeriesStart');
        const startVal = (startInp ? startInp.value.trim() : '') || '0001001';
        const parsed = parseSeriesCode(startVal);

        let currentNum = parsed.num;
        const rows = document.querySelectorAll('#modalMultiVariantTableBody tr[data-combo]');
        rows.forEach(tr => {
            const qtyVal = Math.max(1, parseInt(tr.querySelector('.modal-combo-qty')?.value) || 1);
            const rowStartNum = currentNum;
            const rowEndNum = currentNum + qtyVal - 1;
            currentNum = rowEndNum + 1;

            const rowStartCode = formatSeriesCode(parsed.prefix, rowStartNum, parsed.padLen);
            const rowEndCode = formatSeriesCode(parsed.prefix, rowEndNum, parsed.padLen);

            tr.dataset.seriesStart = rowStartCode;
            tr.dataset.seriesEnd = rowEndCode;

            const badge = tr.querySelector('.modal-combo-series-badge');
            if (badge) {
                badge.innerText = `${rowStartCode} → ${rowEndCode}`;
                badge.title = `Series: ${rowStartCode} to ${rowEndCode} (${qtyVal} pcs)`;
            }
            const seriesCell = tr.querySelector('.td-series-col');
            if (seriesCell) {
                seriesCell.style.display = isSeries ? '' : 'none';
            }
        });
    };

    document.getElementById('modalVarSku')?.addEventListener('input', function() {
        modalVarUserEditedSku = (this.value.trim().length > 0);
        updateModalSeriesCodes(true);
    });

    window.submitAddVariantRowModal = function() {
        // If pending text in inputs, commit them
        if (document.getElementById('modalVarSizeInput')?.value.trim()) {
            addModalSizeAttribute();
        }
        if (document.getElementById('modalVarColorInput')?.value.trim()) {
            addModalColorAttribute();
        }

        const rows = document.querySelectorAll('#modalMultiVariantTableBody tr[data-combo]');
        if (!rows || rows.length === 0) {
            alert('Please add at least one Size attribute above to generate variant rows.');
            document.getElementById('modalVarSizeInput')?.focus();
            return;
        }

        // Validation pass
        let hasError = false;
        rows.forEach((tr, idx) => {
            if (hasError) return;
            const mrpInp = tr.querySelector('.modal-combo-mrp');
            const mrpVal = mrpInp ? mrpInp.value.trim() : '';
            if (!mrpVal || parseFloat(mrpVal) < 0) {
                alert(`Please enter MRP / Price (₹) for row #${idx + 1} (${tr.dataset.size} ${tr.dataset.color || ''}).`);
                if (mrpInp) mrpInp.focus();
                hasError = true;
                return;
            }
            const qtyInp = tr.querySelector('.modal-combo-qty');
            const qtyVal = parseInt(qtyInp ? qtyInp.value : 0) || 0;
            if (qtyVal < 1) {
                alert(`Please enter Quantity >= 1 for row #${idx + 1} (${tr.dataset.size} ${tr.dataset.color || ''}).`);
                if (qtyInp) qtyInp.focus();
                hasError = true;
                return;
            }
        });
        if (hasError) return;

        const isSeries = document.getElementById('modalVarQrSeries')?.checked;
        const qrType = isSeries ? 'SERIES_BATCH' : 'INDIVIDUAL';

        const sizeDatalist = document.getElementById('modalVarSizeDatalist');
        const colorDatalist = document.getElementById('modalVarColorDatalist');

        if (!Array.isArray(variantRowsList)) variantRowsList = [];

        rows.forEach((tr, comboIdx) => {
            const sCode = tr.dataset.size || 'Standard';
            const cName = tr.dataset.color || '';
            const colorHex = tr.dataset.hex || '';

            const itemSku = (tr.querySelector('.modal-combo-sku')?.value || '').trim().toUpperCase();
            const itemHsn = (tr.querySelector('.modal-combo-hsn')?.value || '').trim().toUpperCase();
            const itemWeight = (tr.querySelector('.modal-combo-weight')?.value || '').trim();
            const itemQty = Math.max(1, parseInt(tr.querySelector('.modal-combo-qty')?.value) || 1);
            const itemMrp = parseFloat(tr.querySelector('.modal-combo-mrp')?.value) || 0.00;
            const itemCostStr = tr.querySelector('.modal-combo-cost')?.value.trim();
            const itemCost = itemCostStr !== '' ? parseFloat(itemCostStr) : 0.00;
            const itemMfg = (tr.querySelector('.modal-combo-mfg')?.value || '').trim();
            const itemExp = (tr.querySelector('.modal-combo-exp')?.value || '').trim();

            let sizeId = '';
            if (sizeDatalist && sCode) {
                const matchOpt = Array.from(sizeDatalist.options).find(o => o.value.toUpperCase() === sCode);
                if (matchOpt && matchOpt.dataset.id) sizeId = matchOpt.dataset.id;
            }

            let colorId = '';
            if (colorDatalist && cName) {
                const matchOpt = Array.from(colorDatalist.options).find(o => o.value.toUpperCase() === cName);
                if (matchOpt && matchOpt.dataset.id) colorId = matchOpt.dataset.id;
            }

            let startCode = '';
            let endCode = '';
            if (isSeries) {
                startCode = tr.dataset.seriesStart || '';
                endCode = tr.dataset.seriesEnd || '';
                if (!startCode) {
                    const sDigitsMatch = itemSku.match(/(\d+)(?!.*\d)/);
                    const numBase = sDigitsMatch ? (parseInt(sDigitsMatch[1]) % 100000) : (comboIdx + 1);
                    startCode = String(numBase).padStart(5, '0') + '001';
                    endCode = String(numBase).padStart(5, '0') + String(itemQty).padStart(3, '0');
                }
            }

            const key = `custom_${Date.now()}_${comboIdx + 1}_${Math.random().toString(36).substr(2, 4)}`;
            variantRowsList.push(key);

            variantStateMap[key] = {
                size_id: sizeId,
                size_code: sCode,
                size_name: sCode,
                color_id: colorId,
                color_name: cName,
                color_hex: colorHex,
                sku: itemSku,
                hsn_code: itemHsn,
                quantity: itemQty,
                qr_type: qrType,
                series_start_code: startCode,
                series_end_code: endCode,
                pack_quantity: itemQty,
                weight: itemWeight !== '' ? parseFloat(itemWeight).toFixed(3) : '0.000',
                mrp: itemMrp.toFixed(2),
                selling_price: itemMrp.toFixed(2),
                purchase_price: itemCost.toFixed(2),
                mfg_date: itemMfg,
                expiry_date: itemExp
            };
        });

        renderVariantMatrix();
        if (isSeries && typeof autoGenerateAllSeriesRanges === 'function') {
            autoGenerateAllSeriesRanges(false);
        }

        const modalEl = document.getElementById('modalAddVariantRow');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl) || (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl));
            if (modal) modal.hide();
        }

        if (sizeInp) sizeInp.value = '';
        if (colorInp) colorInp.value = '';
    };

    // -------------------------------------------------------------
    // QR Packaging Mode Helpers & Number Series Handlers
    // -------------------------------------------------------------
    window.isSeriesBatchMode = function() {
        return (variantRowsList || []).some(k => variantStateMap[k] && variantStateMap[k].qr_type === 'SERIES_BATCH');
    };

    window.changeRowQrType = function(key, newType) {
        if (!variantStateMap[key]) variantStateMap[key] = {};
        variantStateMap[key].qr_type = newType;
        if (newType === 'SERIES_BATCH') {
            const card = document.querySelector(`.variant-matrix-card[data-key="${key}"]`) || document.querySelector(`tr[data-key="${key}"]`);
            const skuInp = card ? card.querySelector('.matrix-sku-input') : null;
            const qtyInp = card ? card.querySelector('.matrix-qty-input') : null;
            const sku = (skuInp && skuInp.value) ? skuInp.value : (variantStateMap[key].sku || 'SKU');
            const qty = parseInt(qtyInp ? qtyInp.value : variantStateMap[key].quantity) || 1;
            const skuDigitsMatch = sku.match(/(\d+)(?!.*\d)/);
            const numBase = skuDigitsMatch ? (parseInt(skuDigitsMatch[1]) % 100000) : 1;
            if (!variantStateMap[key].series_start_code) {
                variantStateMap[key].series_start_code = String(numBase).padStart(5, '0') + '001';
            }
            if (!variantStateMap[key].series_end_code) {
                variantStateMap[key].series_end_code = String(numBase).padStart(5, '0') + String(Math.max(1, qty)).padStart(3, '0');
            }
        }
        renderVariantMatrix();
        autoGenerateAllSeriesRanges(true);
        if (newType === 'SERIES_BATCH') {
            openAutoGenerateSeriesModal();
        }
    };

    let generatedSeriesCache = [];

    window.onSeriesModalStartOrEndChange = function(triggerSource) {
        const startSeqInput = document.getElementById('seriesModalStartSeq');
        const endSeqInput = document.getElementById('seriesModalEndSeq');
        let startSeq = parseInt(startSeqInput ? startSeqInput.value : 1) || 1;
        if (startSeq < 1) startSeq = 1;

        if (triggerSource === 'end') {
            let endSeq = parseInt(endSeqInput ? endSeqInput.value : 0) || 0;
            if (endSeq < startSeq) {
                endSeq = startSeq;
                if (endSeqInput) endSeqInput.value = endSeq;
            }
        }

        renderSeriesModalList(triggerSource);
    };

    window.openAutoGenerateSeriesModal = function() {
        // Ensure matrix has at least one variant row before opening modal
        if (!variantRowsList || variantRowsList.length === 0) {
            syncVariantRowsFromAttributes();
            if (!variantRowsList || variantRowsList.length === 0) {
                addNewCustomVariantRow('SERIES_BATCH');
            }
        }

        // If no row is currently in SERIES_BATCH mode, switch existing rows to series or prompt
        const hasSeriesRow = variantRowsList.some(k => variantStateMap[k] && variantStateMap[k].qr_type === 'SERIES_BATCH');
        if (!hasSeriesRow && variantRowsList.length > 0) {
            variantRowsList.forEach(k => {
                if (variantStateMap[k]) variantStateMap[k].qr_type = 'SERIES_BATCH';
            });
            renderVariantMatrix();
        }

        renderSeriesModalList();

        const modalEl = document.getElementById('modalAutoGenerateSeriesRange');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }
    };

    window.renderSeriesModalList = function(triggerSource = 'start') {
        const prodCode = ((codeInp && codeInp.value.trim()) || 'PROD').toUpperCase();
        const qrPrefix = ((document.getElementById('qr_code_prefix')?.value.trim()) || prodCode).toUpperCase();

        const startSeqInput = document.getElementById('seriesModalStartSeq');
        let startSeq = parseInt(startSeqInput ? startSeqInput.value : 1) || 1;
        if (startSeq < 1) {
            startSeq = 1;
            if (startSeqInput) startSeqInput.value = 1;
        }

        const digitLenInput = document.getElementById('seriesModalPadding');
        let padLen = parseInt(digitLenInput ? digitLenInput.value : 4);
        if (isNaN(padLen) || padLen < 1) padLen = 4;
        if (padLen > 10) padLen = 10;

        // Update digit preview badge
        const previewEl = document.getElementById('seriesModalDigitPreview');
        if (previewEl) {
            previewEl.innerText = String(startSeq).padStart(padLen, '0');
        }

        const tagInput = document.getElementById('seriesModalPrefix');
        const seriesTag = tagInput ? tagInput.value.trim().toUpperCase() : 'S';
        const tagPart = seriesTag ? `${seriesTag}` : '';

        const seqModeSelect = document.getElementById('seriesModalSeqMode');
        const isContinuous = !seqModeSelect || seqModeSelect.value === 'CONTINUOUS';

        generatedSeriesCache = [];
        const tbody = document.getElementById('modalSeriesTableBody');
        if (!tbody) return;

        // If container has no cards, build from state map or create a default row
        let cards = document.querySelectorAll('.variant-matrix-card[data-key]');
        if (cards.length === 0 && variantRowsList.length > 0) {
            renderVariantMatrix();
            cards = document.querySelectorAll('.variant-matrix-card[data-key]');
        }

        let validRowCount = 0;
        let totalUnits = 0;
        let html = '';
        let currentSeq = startSeq;

        cards.forEach((card) => {
            const key = card.dataset.key;
            if (!key) return;

            const state = variantStateMap[key] || {};
            // Only process SERIES_BATCH rows in the Number Series breakdown
            if (state.qr_type && state.qr_type !== 'SERIES_BATCH') return;

            const qtyInp = card.querySelector('.matrix-qty-input');
            const qty = parseInt(qtyInp ? qtyInp.value : state.quantity) || parseInt(state.quantity) || 1;
            const skuInp = card.querySelector('.matrix-sku-input');
            let sku = (skuInp ? skuInp.value.trim() : '') || state.sku || prodCode;

            const sizeText = state.size_code || 'Standard';
            const colorText = state.color_name || 'Standard';

            const rowStartNum = isContinuous ? currentSeq : startSeq;
            const rowEndNum = rowStartNum + Math.max(1, qty) - 1;
            if (isContinuous) {
                currentSeq = rowEndNum + 1;
            }

            const fmtStartNum = String(rowStartNum).padStart(padLen, '0');
            const fmtEndNum = String(rowEndNum).padStart(padLen, '0');

            const startCode = tagPart ? `${tagPart}${fmtStartNum}` : fmtStartNum;
            const endCode = tagPart ? `${tagPart}${fmtEndNum}` : fmtEndNum;

            generatedSeriesCache.push({
                key: key,
                startCode: startCode,
                endCode: endCode,
                startNum: rowStartNum,
                endNum: rowEndNum,
                fmtStart: fmtStartNum,
                fmtEnd: fmtEndNum,
                qty: qty
            });

            totalUnits += qty;
            validRowCount++;

            html += `
                <tr>
                    <td class="text-center text-muted small">${validRowCount}</td>
                    <td>
                        <div class="fw-bold text-dark font-monospace">${sku}</div>
                        <small class="text-muted">${sizeText} / ${colorText}</small>
                    </td>
                    <td class="text-center fw-bold text-primary">${qty}</td>
                    <td>
                        <span class="badge bg-light text-primary border font-monospace px-2 py-1">${startCode}</span>
                    </td>
                    <td>
                        <span class="badge bg-light text-primary border font-monospace px-2 py-1">${endCode}</span>
                    </td>
                    <td class="text-center font-monospace small">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">#${fmtStartNum} &rarr; #${fmtEndNum}</span>
                    </td>
                </tr>
            `;
        });

        if (validRowCount === 0) {
            html = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-exclamation-circle me-1"></i> No Series Bundle Pack variant cards found. Click <strong>"+ Add Custom Variant Card" &rarr; "📦 Series Bundle Pack Card"</strong> or switch a card's QR packaging type to <strong>"Series Bundle Pack"</strong>.
                    </td>
                </tr>
            `;
        }

        tbody.innerHTML = html;

        // Auto sync calculated End Sequence input field
        const endSeqInput = document.getElementById('seriesModalEndSeq');
        if (endSeqInput && triggerSource !== 'end') {
            const calculatedEnd = isContinuous ? (startSeq + Math.max(1, totalUnits) - 1) : startSeq;
            endSeqInput.value = calculatedEnd;
        }

        const totalVariantsBadge = document.getElementById('modalSeriesTotalVariantsBadge');
        if (totalVariantsBadge) totalVariantsBadge.innerText = `${validRowCount} Series Variants`;

        const totalUnitsEl = document.getElementById('modalSeriesTotalUnits');
        if (totalUnitsEl) totalUnitsEl.innerText = `${totalUnits} pcs`;

        const spanEl = document.getElementById('modalSeriesOverallSpan');
        if (spanEl) {
            if (validRowCount > 0 && generatedSeriesCache.length > 0) {
                const firstFmt = generatedSeriesCache[0].fmtStart;
                const lastFmt = generatedSeriesCache[generatedSeriesCache.length - 1].fmtEnd;
                spanEl.innerText = `#${firstFmt} ➔ #${lastFmt} (${totalUnits} Total Units, ${padLen} Digits)`;
            } else {
                spanEl.innerText = '—';
            }
        }
    };

    window.applyGeneratedSeriesToMatrix = function() {
        if (!generatedSeriesCache || generatedSeriesCache.length === 0) {
            renderSeriesModalList();
        }

        generatedSeriesCache.forEach(item => {
            if (variantStateMap[item.key]) {
                variantStateMap[item.key].qr_type = 'SERIES_BATCH';
                variantStateMap[item.key].series_start_code = item.startCode;
                variantStateMap[item.key].series_end_code = item.endCode;
            }
        });

        renderVariantMatrix();
        calculateMatrixTotals();

        const modalEl = document.getElementById('modalAutoGenerateSeriesRange');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
    };

    window.autoGenerateAllSeriesRanges = function(forceOverwrite = true, showModal = false) {
        if (showModal) {
            openAutoGenerateSeriesModal();
            return;
        }

        const prodCode = ((codeInp && codeInp.value.trim()) || 'PROD').toUpperCase();
        const qrPrefix = ((document.getElementById('qr_code_prefix')?.value.trim()) || prodCode).toUpperCase();

        let globalSequence = 1;

        variantRowsList.forEach(key => {
            const state = variantStateMap[key] || {};
            // Only auto generate sequence for SERIES_BATCH rows
            if (state.qr_type !== 'SERIES_BATCH') return;

            const qty = parseInt(state.quantity) || 1;
            let sku = state.sku || prodCode;

            const startNum = globalSequence;
            const endNum = globalSequence + Math.max(1, qty) - 1;
            globalSequence = endNum + 1;

            let baseCode = sku;
            if (qrPrefix && !baseCode.toUpperCase().startsWith(qrPrefix.toUpperCase())) {
                baseCode = `${qrPrefix}-${baseCode}`;
            }

            const defaultStart = `${baseCode}-S${String(startNum).padStart(4, '0')}`;
            const defaultEnd = `${baseCode}-S${String(endNum).padStart(4, '0')}`;

            if (forceOverwrite || !state.series_start_code) {
                state.series_start_code = defaultStart;
            }
            if (forceOverwrite || !state.series_end_code) {
                state.series_end_code = defaultEnd;
            }

            variantStateMap[key] = state;
        });

        calculateMatrixTotals();
    };

    // Attach click event directly to Auto-Generate Series button
    const btnAutoGenerateAllSeries = document.getElementById('btnAutoGenerateAllSeries');
    if (btnAutoGenerateAllSeries) {
        btnAutoGenerateAllSeries.addEventListener('click', function(e) {
            e.preventDefault();
            openAutoGenerateSeriesModal();
        });
    }

    let activeSeriesData = null;
    let activeIndividualData = null;

    window.previewIndividualQR = function(key) {
        const state = variantStateMap[key] || {};
        const prodName = ((document.getElementById('name')?.value.trim()) || (codeInp?.value.trim()) || 'PRODUCT STYLE').toUpperCase();
        const card = document.querySelector(`.variant-matrix-card[data-key="${key}"]`) || document.querySelector(`tr[data-key="${key}"]`);
        const hierarchyText = state.category_path || (card ? (card.querySelector('.card-cat-breadcrumb-text')?.innerText || '') : '') || 'GENERAL APPAREL';
        const sourceType = document.getElementById('srcPurchased')?.checked ? 'Third-Party Vendor' : 'In-House Production';
        const sizeBadgeText = state.size_code || 'Free Size';
        const colorBadgeText = state.color_name || 'Standard';
        
        const skuInp = card?.querySelector('.matrix-sku-input');
        const mrpInp = card?.querySelector('.matrix-mrp-input');
        const sellInp = card?.querySelector('.matrix-selling-input');

        const sku = (skuInp && skuInp.value) ? skuInp.value : (state.sku || 'SKU');
        const mrp = parseFloat(mrpInp ? mrpInp.value : state.mrp) || parseFloat(section2MrpInput?.value) || 0;
        const sellingPrice = parseFloat(sellInp ? sellInp.value : state.selling_price) || parseFloat(section2SellingInput?.value) || mrp;

        activeIndividualData = {
            sku: sku,
            prodName: prodName,
            hierarchyText: hierarchyText,
            sourceType: sourceType,
            sizeText: sizeBadgeText,
            colorText: colorBadgeText,
            mrp: mrp,
            sellingPrice: sellingPrice
        };

        const modalProdName = document.getElementById('modalIndivProdName');
        const modalHierarchy = document.getElementById('modalIndivHierarchyText');
        const modalSku = document.getElementById('modalIndivSkuText');
        const modalSize = document.getElementById('modalIndivSizeBadge');
        const modalColor = document.getElementById('modalIndivColorBadge');
        const modalMrp = document.getElementById('modalIndivUnitMrp');
        const modalSelling = document.getElementById('modalIndivSellingPrice');
        const modalSource = document.getElementById('modalIndivSourceType');

        if (modalProdName) modalProdName.innerText = prodName;
        if (modalHierarchy) modalHierarchy.innerText = hierarchyText;
        if (modalSku) modalSku.innerText = sku;
        if (modalSize) modalSize.innerText = 'Size: ' + sizeBadgeText;
        if (modalColor) modalColor.innerText = 'Color: ' + colorBadgeText;
        if (modalMrp) modalMrp.innerText = `₹${mrp.toFixed(2)}`;
        if (modalSelling) modalSelling.innerText = `₹${sellingPrice.toFixed(2)}`;
        if (modalSource) modalSource.innerText = sourceType;

        const qrCanvas = document.getElementById('indivQRCanvas');
        if (qrCanvas) {
            qrCanvas.innerHTML = '';
            new QRCode(qrCanvas, {
                text: sku,
                width: 100,
                height: 100,
                colorDark: "#0f172a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        }

        const modalEl = document.getElementById('modalPreviewIndividualQR');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    };

    window.printIndividualPieceFromModal = function() {
        if (!activeIndividualData) return;
        const cardHtml = document.getElementById('individualPiecePrintableCard')?.outerHTML || '';
        
        const win = window.open('', '_blank', 'width=600,height=500');
        win.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Piece QR Label — ${activeIndividualData.sku}</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { background: #fff; padding: 20px; font-family: 'Segoe UI', monospace; }
                    #individualPiecePrintableCard { border: 2px solid #000 !important; width: 100% !important; max-width: 400px; margin: 0 auto; }
                    @media print {
                        body { padding: 0; margin: 0; }
                        #individualPiecePrintableCard { box-shadow: none !important; }
                    }
                </style>
            </head>
            <body onload="window.print();">
                ${cardHtml}
            </body>
            </html>
        `);
        win.document.close();
    };

    window.previewSeriesQR = function(key) {
        const state = variantStateMap[key] || {};
        const prodName = ((document.getElementById('name')?.value.trim()) || (codeInp?.value.trim()) || 'PRODUCT STYLE').toUpperCase();
        const card = document.querySelector(`.variant-matrix-card[data-key="${key}"]`) || document.querySelector(`tr[data-key="${key}"]`);
        const hierarchyText = state.category_path || (card ? (card.querySelector('.card-cat-breadcrumb-text')?.innerText || '') : '') || 'GENERAL APPAREL';
        const sourceType = document.getElementById('srcPurchased')?.checked ? 'Third-Party Vendor' : 'In-House Production';
        const sizeBadgeText = state.size_code || 'Free Size';
        const colorBadgeText = state.color_name || 'Standard';
        
        const skuInp = card?.querySelector('.matrix-sku-input');
        const qtyInp = card?.querySelector('.matrix-qty-input');
        const startInp = card?.querySelector('.matrix-start-code');
        const endInp = card?.querySelector('.matrix-end-code');
        const mrpInp = card?.querySelector('.matrix-mrp-input');

        const sku = (skuInp && skuInp.value) ? skuInp.value : (state.sku || 'SKU');
        const qty = parseInt(qtyInp ? qtyInp.value : state.quantity) || 1;
        const mrp = parseFloat(mrpInp ? mrpInp.value : state.mrp) || parseFloat(section2MrpInput?.value) || 0;
        const totalMrp = mrp * qty;

        const qrPrefix = ((document.getElementById('qr_code_prefix')?.value.trim()) || 'PROD').toUpperCase();
        const startCode = (startInp && startInp.value) ? startInp.value : (state.series_start_code || `${qrPrefix}-${sku}-S0001`);
        const endCode = (endInp && endInp.value) ? endInp.value : (state.series_end_code || `${qrPrefix}-${sku}-S${String(qty).padStart(4, '0')}`);

        activeSeriesData = {
            sku: sku,
            prodName: prodName,
            hierarchyText: hierarchyText,
            sourceType: sourceType,
            sizeText: sizeBadgeText,
            colorText: colorBadgeText,
            qty: qty,
            mrp: mrp,
            totalMrp: totalMrp,
            startCode: startCode,
            endCode: endCode
        };

        // Populate Modal Fields
        document.getElementById('modalSeriesProdName').innerText = prodName;
        document.getElementById('modalSeriesHierarchyText').innerText = hierarchyText;
        document.getElementById('modalSeriesSkuText').innerText = sku;
        document.getElementById('modalSeriesSizeBadge').innerText = 'Size: ' + sizeBadgeText;
        document.getElementById('modalSeriesColorBadge').innerText = 'Color: ' + colorBadgeText;
        document.getElementById('modalSeriesStartCodeText').innerText = startCode;
        document.getElementById('modalSeriesEndCodeText').innerText = endCode;
        document.getElementById('modalSeriesTotalQtyBadge').innerText = `TOTAL: ${qty} PCS BUNDLE SERIES`;
        document.getElementById('modalSeriesUnitMrp').innerText = `₹${mrp.toFixed(2)}`;
        document.getElementById('modalSeriesTotalMrp').innerText = `₹${totalMrp.toFixed(2)}`;
        document.getElementById('modalSeriesSourceType').innerText = sourceType;

        // Generate Master QR Code Payload (Structured Range JSON)
        const qrCanvas = document.getElementById('seriesQRCanvas');
        if (qrCanvas) {
            qrCanvas.innerHTML = '';
            const qrPayload = JSON.stringify({
                type: "SERIES_BATCH",
                sku: sku,
                qty: qty,
                start: startCode,
                end: endCode,
                mrp: mrp,
                prod: prodName
            });

            new QRCode(qrCanvas, {
                text: qrPayload,
                width: 110,
                height: 110,
                colorDark: "#0f172a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        }

        const modalEl = document.getElementById('modalPreviewSeriesQR');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    };

    window.printMasterSeriesFromModal = function() {
        if (!activeSeriesData) return;
        const cardHtml = document.getElementById('masterSeriesPrintableCard').outerHTML;
        
        const win = window.open('', '_blank', 'width=750,height=600');
        win.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Master Series QR Label — ${activeSeriesData.sku}</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { background: #fff; padding: 20px; font-family: 'Segoe UI', monospace; }
                    #masterSeriesPrintableCard { border: 2px solid #000 !important; width: 100% !important; max-width: 500px; margin: 0 auto; }
                    @media print {
                        body { padding: 0; margin: 0; }
                        #masterSeriesPrintableCard { box-shadow: none !important; }
                    }
                </style>
            </head>
            <body onload="window.print();">
                ${cardHtml}
            </body>
            </html>
        `);
        win.document.close();
    };

    function renderVariantMatrix() {
        const variantCardsContainer = document.getElementById('variantCardsContainer');
        if (!variantCardsContainer) return;

        if (!Array.isArray(variantRowsList) || variantRowsList.length === 0) {
            variantCardsContainer.innerHTML = `
                <div class="card border border-dashed rounded-3 p-4 text-center bg-light">
                    <i class="bi bi-box-seam fs-2 text-secondary opacity-50 d-block mb-2"></i>
                    <div class="fw-bold fs-6 text-dark mb-1">No Product Variants Added Yet</div>
                    <p class="small text-muted mb-3">Click <strong>"+ Add Custom Variant Card"</strong> to add variants with Size, Color, SKU, Quantity, Pricing, and Dates via the product popup form.</p>
                    <div>
                        <button type="button" class="btn btn-sm btn-primary px-3 shadow-xs" onclick="openAddVariantRowModal('INDIVIDUAL')">
                            <i class="bi bi-plus-circle-fill me-1"></i> + Add Custom Variant Card
                        </button>
                    </div>
                </div>
            `;
            calculateMatrixTotals();
            return;
        }

        const baseCost = parseFloat(section2CostInput ? section2CostInput.value : 0) || 0;
        const baseMrp = parseFloat(section2MrpInput ? section2MrpInput.value : 0) || 0;
        const baseSelling = parseFloat(section2SellingInput ? section2SellingInput.value : 0) || baseMrp;
        const baseWeight = parseFloat(section2WeightInput ? section2WeightInput.value : 0) || 0;
        const prodCode = ((codeInp && codeInp.value.trim()) || 'PROD').toUpperCase();
        const qrPrefix = ((document.getElementById('qr_code_prefix')?.value.trim()) || prodCode).toUpperCase();

        let cardHtml = '';
        let rowIndex = 0;

        const activeCategoryPath = getActiveCategoryHierarchyPath();

        variantRowsList.forEach(key => {
            const state = variantStateMap[key] || {};

            // Per-variant category resolved values
            const varCatId         = state.category_id         || '';
            const varSubcatId      = state.subcategory_id      || '';
            const varSubSubcatId   = state.sub_subcategory_id  || '';
            const varCatName       = state.category_name       || '';
            const varSubcatName    = state.subcategory_name    || '';
            const varSubSubcatName = state.sub_subcategory_name || '';

            // Compute default short codes from names or master category codes if not explicitly set
            const matchedMaster = masterCategoriesList.find(c => String(c.id) === String(varCatId));
            const varCatCode       = (state.category_code !== undefined && state.category_code !== '' && state.category_code.length > 1) 
                ? state.category_code 
                : (varCatName ? getCategorySmartShortCode(varCatName, state.category_code || matchedMaster?.code || '', [], 'CAT') : '');
            const varSubcatCode    = (state.subcategory_code !== undefined && state.subcategory_code !== '' && state.subcategory_code.length > 1) 
                ? state.subcategory_code 
                : (varSubcatName ? getCategorySmartShortCode(varSubcatName, state.subcategory_code || '', [], 'SUB') : '');
            const varSubSubcatCode = (state.sub_subcategory_code !== undefined && state.sub_subcategory_code !== '' && state.sub_subcategory_code.length > 1) 
                ? state.sub_subcategory_code 
                : (varSubSubcatName ? getCategorySmartShortCode(varSubSubcatName, state.sub_subcategory_code || '', [], 'SSC') : '');
            const varQrPrefix      = (state.qr_code_prefix !== undefined && state.qr_code_prefix !== '' && state.qr_code_prefix.length > 3) 
                ? state.qr_code_prefix 
                : (varCatCode + varSubcatCode + varSubSubcatCode).toUpperCase();

            // Sync back to state
            state.category_code        = varCatCode;
            state.subcategory_code     = varSubcatCode;
            state.sub_subcategory_code = varSubSubcatCode;
            state.qr_code_prefix       = varQrPrefix;

            // Build main category options for this card
            let catOptionsHtml = '<option value="">-- Select Category --</option>';
            masterCategoriesList.forEach(c => {
                const sel = (String(c.id) === String(varCatId)) ? 'selected' : '';
                catOptionsHtml += `<option value="${c.id}" data-code="${c.code || ''}" ${sel}>${c.name}</option>`;
            });

            // Build subcategory options (pre-populated only if state has them)
            let subcatOptionsHtml = varSubcatId
                ? `<option value="">-- Select Sub Category --</option><option value="${varSubcatId}" data-code="${varSubcatCode}" selected>${varSubcatName}</option>`
                : '<option value="">-- Select Category First --</option>';

            // Build sub-subcategory options (pre-populated only if state has them)
            let subSubcatOptionsHtml = varSubSubcatId
                ? `<option value="">-- Select Sub Sub Category --</option><option value="${varSubSubcatId}" data-code="${varSubSubcatCode}" selected>${varSubSubcatName}</option>`
                : '<option value="">-- Select Sub Category First --</option>';

            // Hierarchy path string for breadcrumb display
            const varCategoryPath = state.category_path || [varCatName, varSubcatName, varSubSubcatName].filter(Boolean).join(' → ');

            // Default auto SKU
            let defaultSku = prodCode;
            if (state.size_code && state.size_code !== 'Standard') {
                defaultSku += '-' + state.size_code;
            }
            if (state.color_name) {
                const cShort = state.color_name.replace(/[^A-Za-z0-9]/g, '').substring(0, 4).toUpperCase();
                defaultSku += '-' + cShort;
            }

            const skuVal = state.sku !== undefined && state.sku !== '' ? state.sku : defaultSku;
            const hsnVal = state.hsn_code !== undefined ? state.hsn_code : '';
            const qtyVal = state.quantity !== undefined ? state.quantity : 1;
            const weightVal = state.weight !== undefined && state.weight !== '' ? state.weight : (baseWeight > 0 ? baseWeight.toFixed(3) : '0.000');
            const costVal = state.purchase_price !== undefined && state.purchase_price !== '' ? state.purchase_price : (baseCost > 0 ? baseCost.toFixed(2) : '');
            const mrpVal = state.mrp !== undefined && state.mrp !== '' ? state.mrp : (baseMrp > 0 ? baseMrp.toFixed(2) : '');
            const sellingVal = state.selling_price !== undefined && state.selling_price !== '' ? state.selling_price : (baseSelling > 0 ? baseSelling.toFixed(2) : '');
            const mfgVal = state.mfg_date !== undefined ? state.mfg_date : '';
            const expVal = state.expiry_date !== undefined ? state.expiry_date : '';

            const isRowSeries = (state.qr_type === 'SERIES_BATCH');

            // Series Range auto default
            const startCodeVal = state.series_start_code !== undefined && state.series_start_code !== '' ? state.series_start_code : `${qrPrefix}-${skuVal}-S0001`;
            const endCodeVal = state.series_end_code !== undefined && state.series_end_code !== '' ? state.series_end_code : `${qrPrefix}-${skuVal}-S${String(Math.max(1, qtyVal)).padStart(4, '0')}`;

            cardHtml += `
                <div class="card border rounded-3 shadow-xs variant-matrix-card bg-white" data-key="${key}">
                    <!-- Card Top Header Bar -->
                    <div class="card-header bg-light bg-opacity-75 py-2 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-secondary-subtle text-secondary border fw-bold px-2 py-1">#${rowIndex + 1}</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5" title="Category Hierarchy">
                                <i class="bi bi-diagram-3-fill me-1"></i><span class="card-cat-hierarchy-text">${activeCategoryPath}</span>
                            </span>
                            <input type="hidden" name="variants[${rowIndex}][size_id]" value="${state.size_id || ''}">
                            <input type="hidden" name="variants[${rowIndex}][size_name]" value="${state.size_code || state.size_name || ''}">
                            <span class="badge bg-dark px-2.5 py-1.5"><i class="bi bi-tag me-1"></i>Size: ${state.size_code || 'Standard'}${state.size_name && state.size_name !== state.size_code ? ` (${state.size_name})` : ''}</span>
                            <input type="hidden" name="variants[${rowIndex}][color_id]" value="${state.color_id || ''}">
                            <input type="hidden" name="variants[${rowIndex}][color_name]" value="${state.color_name || ''}">
                            <input type="hidden" name="variants[${rowIndex}][color_hex]" value="${normalizeHexColor(state.color_hex, state.color_name) || state.color_hex || ''}">
                            <span class="badge bg-white text-dark border px-2.5 py-1.5 d-inline-flex align-items-center gap-1.5">
                                <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background-color:${normalizeHexColor(state.color_hex, state.color_name) || '#000000'}; border:1px solid #ccc;"></span>
                                <span>${state.color_name || 'Standard Color'}</span>
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                            <div style="min-width: 200px;">
                                <select name="variants[${rowIndex}][qr_type]" class="form-select form-select-sm matrix-qr-type fw-semibold ${isRowSeries ? 'text-primary bg-primary-subtle border-primary' : 'text-dark'}" data-key="${key}" onchange="changeRowQrType('${key}', this.value)">
                                    <option value="INDIVIDUAL" ${!isRowSeries ? 'selected' : ''}>🏷️ Individual Piece QR</option>
                                    <option value="SERIES_BATCH" ${isRowSeries ? 'selected' : ''}>📦 Series Bundle Pack</option>
                                </select>
                            </div>
                            ${isRowSeries ? `
                                <button type="button" class="btn btn-xs btn-outline-primary fw-semibold px-2 py-1 shadow-xs" onclick="previewSeriesQR('${key}')" title="Preview single master series QR label for this batch">
                                    <i class="bi bi-collection me-1"></i>Master QR
                                </button>
                            ` : ''}
                            <button type="button" class="btn btn-xs btn-outline-danger p-1 px-2" onclick="removeVariantRow('${key}')" title="Delete this variant product card">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Card Body Form Grid -->
                    <div class="card-body p-3">

                        <!-- Per-Variant Category Hierarchy & QR Prefix -->
                        <div class="card border rounded-2 mb-3" style="background: #f8faff;">
                            <div class="card-body p-2 px-3">
                                <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                    <span class="fw-bold small text-dark">
                                        <i class="bi bi-diagram-3-fill me-1 text-primary"></i> Category Hierarchy (3-Tier) &amp; QR Code Prefix
                                    </span>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Required for this Variant</span>
                                </div>
                                <div class="row g-2 mb-2">
                                    <!-- Main Category -->
                                    <div class="col-md-4">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label small fw-semibold mb-0 text-dark">Main Category <span class="text-danger">*</span></label>
                                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-primary card-btn-new-main" data-key="${key}">
                                                <i class="bi bi-plus-circle"></i> New
                                            </button>
                                        </div>
                                        <select class="form-select form-select-sm card-cat-main" data-key="${key}">
                                            ${catOptionsHtml}
                                        </select>
                                        <input type="hidden" name="variants[${rowIndex}][category_id]" class="card-cat-main-hidden" value="${varCatId}">
                                    </div>
                                    <!-- Sub Category -->
                                    <div class="col-md-4">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label small fw-semibold mb-0 text-dark">Sub Category</label>
                                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-primary card-btn-new-subcat" data-key="${key}" ${varCatId ? '' : 'disabled'}>
                                                <i class="bi bi-plus-circle"></i> New
                                            </button>
                                        </div>
                                        <select class="form-select form-select-sm card-cat-sub" data-key="${key}" ${varCatId ? '' : 'disabled'}>
                                            ${subcatOptionsHtml}
                                        </select>
                                        <input type="hidden" name="variants[${rowIndex}][subcategory_id]" class="card-cat-sub-hidden" value="${varSubcatId}">
                                    </div>
                                    <!-- Sub Sub Category -->
                                    <div class="col-md-4">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label small fw-semibold mb-0 text-dark">Sub Sub Category</label>
                                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-primary card-btn-new-subsubcat" data-key="${key}" ${varSubcatId ? '' : 'disabled'}>
                                                <i class="bi bi-plus-circle"></i> New
                                            </button>
                                        </div>
                                        <select class="form-select form-select-sm card-cat-subsub" data-key="${key}" ${varSubcatId ? '' : 'disabled'}>
                                            ${subSubcatOptionsHtml}
                                        </select>
                                        <input type="hidden" name="variants[${rowIndex}][sub_subcategory_id]" class="card-cat-subsub-hidden" value="${varSubSubcatId}">
                                    </div>
                                </div>

                                <!-- Hierarchy Path Breadcrumb -->
                                <div class="mb-2 p-2 bg-white border rounded d-flex align-items-center gap-2 text-muted small card-cat-breadcrumb-wrap">
                                    <i class="bi bi-folder2-open text-primary"></i>
                                    <span class="fw-semibold">Hierarchy Path:</span>
                                    <span class="card-cat-breadcrumb-text text-dark">${varCatId ? (varCategoryPath || 'Selected') : 'Select a category above...'}</span>
                                </div>

                                <!-- Category Short Codes & QR Code -->
                                <div class="pt-2 border-top">
                                    <div class="d-flex align-items-center mb-1">
                                        <label class="form-label fw-bold text-dark mb-0 small">
                                            <i class="bi bi-qr-code-scan text-primary me-1"></i> Category Short Codes &amp; Whole Code for QR Code
                                        </label>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold text-muted mb-1">Main Category Code</label>
                                            <input type="text" name="variants[${rowIndex}][category_code]" class="form-control form-control-sm text-uppercase fw-semibold card-cat-code-main" value="${varCatCode}" placeholder="E.G. WOM" data-key="${key}" oninput="updateCardQrPrefix('${key}')">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold text-muted mb-1">Sub Category Code</label>
                                            <input type="text" name="variants[${rowIndex}][subcategory_code]" class="form-control form-control-sm text-uppercase fw-semibold card-cat-code-sub" value="${varSubcatCode}" placeholder="E.G. MAT" data-key="${key}" oninput="updateCardQrPrefix('${key}')">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold text-muted mb-1">Sub Sub Category Code</label>
                                            <input type="text" name="variants[${rowIndex}][sub_subcategory_code]" class="form-control form-control-sm text-uppercase fw-semibold card-cat-code-subsub" value="${varSubSubcatCode}" placeholder="E.G. FEE" data-key="${key}" oninput="updateCardQrPrefix('${key}')">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold text-primary mb-1">Whole Code for QR Code</label>
                                            <input type="text" name="variants[${rowIndex}][qr_code_prefix]" class="form-control form-control-sm text-uppercase fw-bold font-monospace border-primary text-primary card-cat-qr-prefix" value="${varQrPrefix}" placeholder="E.G. WOMMATFEE" data-key="${key}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Variant SKU Code -->
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <i class="bi bi-upc-scan text-primary me-1"></i> Variant SKU Code <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="variants[${rowIndex}][sku]" class="form-control form-control-sm text-uppercase font-monospace fw-bold matrix-sku-input" value="${skuVal}" placeholder="SKU" data-key="${key}">
                            </div>

                            <!-- HSN Code for each product -->
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <i class="bi bi-hash text-secondary me-1"></i> HSN Code
                                </label>
                                <input type="text" name="variants[${rowIndex}][hsn_code]" class="form-control form-control-sm text-uppercase matrix-hsn-input" value="${hsnVal}" placeholder="e.g. 6204" data-key="${key}">
                            </div>

                            <!-- Qty (Pcs) -->
                            <div class="col-lg-2 col-md-4 col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <i class="bi bi-boxes text-primary me-1"></i> Qty (Pcs) <span class="text-danger">*</span>
                                </label>
                                <input type="number" min="0" step="1" name="variants[${rowIndex}][quantity]" class="form-control form-control-sm text-center fw-bold text-primary fs-6 matrix-qty-input" value="${qtyVal}" placeholder="1" data-key="${key}" ${state.is_autoloaded ? 'readonly style="background-color: #e9ecef; cursor: not-allowed; pointer-events: none;" title="Auto-loaded quantity from supplier product cannot be edited"' : ''}>
                                <input type="hidden" name="variants[${rowIndex}][pack_quantity]" value="${qtyVal > 0 ? qtyVal : 1}">
                            </div>

                            <!-- MRP / Product Price / Selling Price -->
                            <div class="col-lg-2 col-md-4 col-6 matrix-mrp-wrapper">
                                <label class="form-label small fw-bold text-success mb-1 matrix-mrp-label">
                                    ${window.isComplementaryMode ? '<i class="bi bi-tag-fill text-warning me-1"></i> Selling Price (₹) <span class="text-danger">*</span>' : '<i class="bi bi-tag-fill text-success me-1"></i> MRP Price (₹) <span class="text-danger">*</span>'}
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text ${window.isComplementaryMode ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success'} fw-bold">₹</span>
                                    <input type="number" min="0" step="0.01" name="variants[${rowIndex}][mrp]" class="form-control form-control-sm text-end fw-bold matrix-mrp-input" value="${mrpVal}" placeholder="0.00" data-key="${key}" title="${window.isComplementaryMode ? 'Selling Price (₹)' : 'MRP Price (₹)'}">
                                </div>
                                <input type="hidden" name="variants[${rowIndex}][selling_price]" class="matrix-selling-input" value="${mrpVal}" data-key="${key}">
                            </div>

                            <!-- Cost Price -->
                            <div class="col-lg-3 col-md-4 col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <i class="bi bi-cart-check text-secondary me-1"></i> Cost Price (₹)
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted">₹</span>
                                    <input type="number" min="0" step="0.01" name="variants[${rowIndex}][purchase_price]" class="form-control form-control-sm text-end matrix-cost-input" value="${costVal}" placeholder="0.00" data-key="${key}">
                                </div>
                            </div>

                            <!-- Series Range (if Series) or Packaging Label -->
                            <div class="col-lg-4 col-md-12">
                                ${isRowSeries ? `
                                    <label class="form-label small fw-semibold text-primary mb-1">
                                        <i class="bi bi-collection me-1"></i> Series Range (Start &rarr; End Code)
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="variants[${rowIndex}][series_start_code]" class="form-control form-control-sm font-monospace matrix-start-code" placeholder="Start (e.g. S0001)" value="${startCodeVal}" data-key="${key}" title="Series Start Code">
                                        <span class="input-group-text px-2 bg-light text-muted small">&rarr;</span>
                                        <input type="text" name="variants[${rowIndex}][series_end_code]" class="form-control form-control-sm font-monospace matrix-end-code" placeholder="End (e.g. S0050)" value="${endCodeVal}" data-key="${key}" title="Series End Code">
                                        <button type="button" class="btn btn-outline-primary px-2" onclick="openAutoGenerateSeriesModal()" title="Auto-configure series number range popup">
                                            <i class="bi bi-magic"></i> Auto Series
                                        </button>
                                    </div>
                                ` : `
                                    <label class="form-label small fw-semibold text-muted mb-1">
                                        <i class="bi bi-qr-code me-1 text-secondary"></i> Packaging Mode Info
                                    </label>
                                    <div class="p-1 px-2 bg-light border rounded text-secondary small d-flex align-items-center gap-1" style="height: 31px;">
                                        <i class="bi bi-tag-fill text-primary"></i> <span>1 QR Sticker / Piece (Standard Pack)</span>
                                    </div>
                                    <input type="hidden" name="variants[${rowIndex}][series_start_code]" value="">
                                    <input type="hidden" name="variants[${rowIndex}][series_end_code]" value="">
                                `}
                            </div>

                            <!-- Weight (kg) -->
                            <div class="col-lg-2 col-md-4 col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">Weight (kg)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" min="0" step="0.001" name="variants[${rowIndex}][weight]" class="form-control form-control-sm text-center matrix-weight-input" value="${weightVal}" placeholder="0.000" data-key="${key}">
                                    <span class="input-group-text bg-light text-muted small">kg</span>
                                </div>
                            </div>

                            <!-- Mfg Date -->
                            <div class="col-lg-3 col-md-4 col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <i class="bi bi-calendar-check text-success me-1"></i> Mfg Date
                                </label>
                                <input type="date" name="variants[${rowIndex}][mfg_date]" class="form-control form-control-sm matrix-mfg-input" value="${mfgVal}" data-key="${key}" title="Manufacture Date">
                            </div>

                            <!-- Exp Date -->
                            <div class="col-lg-3 col-md-4 col-6">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <i class="bi bi-calendar-x text-danger me-1"></i> Exp Date
                                </label>
                                <input type="date" name="variants[${rowIndex}][expiry_date]" class="form-control form-control-sm matrix-exp-input" value="${expVal}" data-key="${key}" title="Expiry Date">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            rowIndex++;
        });

        variantCardsContainer.innerHTML = cardHtml;

        // Wire up per-card category cascade via event delegation & update totals
        initAllVariantCardCategorySelects();
        calculateMatrixTotals();
    }

    // Update the QR Code Prefix field from short codes (auto-combine)
    window.updateCardQrPrefix = function(key, force = false) {
        const card = document.querySelector(`.variant-matrix-card[data-key="${key}"]`);
        if (!card) return;
        const c1 = (card.querySelector('.card-cat-code-main')?.value || '').trim().toUpperCase();
        const c2 = (card.querySelector('.card-cat-code-sub')?.value || '').trim().toUpperCase();
        const c3 = (card.querySelector('.card-cat-code-subsub')?.value || '').trim().toUpperCase();
        const qrField = card.querySelector('.card-cat-qr-prefix');
        if (qrField && (force || !qrField.dataset.userEdited || !qrField.value.trim())) {
            const combined = (c1 + c2 + c3).toUpperCase();
            qrField.value = combined;
            delete qrField.dataset.userEdited;
            if (variantStateMap[key]) variantStateMap[key].qr_code_prefix = combined;
        }
    };

    function resetCardHierarchyState(card, parentLevel = 'main') {
        const key = card?.dataset?.key;
        const mainSel = card?.querySelector('.card-cat-main');
        const subSel = card?.querySelector('.card-cat-sub');
        const subSubSel = card?.querySelector('.card-cat-subsub');
        const hiddenMain = card?.querySelector('.card-cat-main-hidden');
        const hiddenSub = card?.querySelector('.card-cat-sub-hidden');
        const hiddenSubSub = card?.querySelector('.card-cat-subsub-hidden');

        if (hiddenMain && parentLevel === 'main') {
            hiddenMain.value = mainSel?.value || '';
        }
        if (hiddenSub) hiddenSub.value = '';
        if (hiddenSubSub) hiddenSubSub.value = '';

        if (parentLevel === 'main') {
            if (subSel) {
                subSel.innerHTML = '<option value="">-- Select Category First --</option>';
                subSel.disabled = true;
            }
            if (subSubSel) {
                subSubSel.innerHTML = '<option value="">-- Select Sub Category First --</option>';
                subSubSel.disabled = true;
            }
            if (variantStateMap[key]) {
                variantStateMap[key].subcategory_id = '';
                variantStateMap[key].subcategory_name = '';
                variantStateMap[key].subcategory_code = '';
                variantStateMap[key].sub_subcategory_id = '';
                variantStateMap[key].sub_subcategory_name = '';
                variantStateMap[key].sub_subcategory_code = '';
                variantStateMap[key].category_path = (mainSel && mainSel.selectedOptions[0]?.text ? mainSel.selectedOptions[0].text.trim() : variantStateMap[key].category_name || 'General Category');
            }
            return;
        }

        if (parentLevel === 'sub') {
            if (subSubSel) {
                subSubSel.innerHTML = '<option value="">-- Select Sub Category First --</option>';
                subSubSel.disabled = true;
            }
            if (hiddenSubSub) hiddenSubSub.value = '';
            if (variantStateMap[key]) {
                variantStateMap[key].sub_subcategory_id = '';
                variantStateMap[key].sub_subcategory_name = '';
                variantStateMap[key].sub_subcategory_code = '';
                const catName = variantStateMap[key].category_name || '';
                const subName = variantStateMap[key].subcategory_name || '';
                variantStateMap[key].category_path = [catName, subName].filter(Boolean).join(' → ') || 'General Category';
            }
        }
    }

    // ------------------------------------------------------------------
    // Per-Card Category Cascade: event delegation (runs after each render)
    // ------------------------------------------------------------------
    function initAllVariantCardCategorySelects() {
        // Main Category change -> load subcategories for that card
        document.querySelectorAll('.card-cat-main').forEach(function(mainSel) {
            mainSel.addEventListener('change', function() {
                const key = this.dataset.key;
                const catId = this.value;
                const catName = catId ? (this.options[this.selectedIndex]?.text || '').replace(/^--.*--$/, '').trim() : '';
                const card = document.querySelector(`.variant-matrix-card[data-key="${key}"]`);
                if (!card) return;

                const hiddenMain = card.querySelector('.card-cat-main-hidden');
                const subSel = card.querySelector('.card-cat-sub');
                const hiddenSub = card.querySelector('.card-cat-sub-hidden');
                const subSubSel = card.querySelector('.card-cat-subsub');
                const hiddenSubSub = card.querySelector('.card-cat-subsub-hidden');

                if (hiddenMain) hiddenMain.value = catId;
                resetCardHierarchyState(card, 'main');

                // Reset sub selects
                if (subSel) { subSel.innerHTML = '<option value="">-- Loading... --</option>'; subSel.disabled = true; }
                if (hiddenSub) hiddenSub.value = '';
                if (subSubSel) { subSubSel.innerHTML = '<option value="">-- Select Sub Category First --</option>'; subSubSel.disabled = true; }
                if (hiddenSubSub) hiddenSubSub.value = '';

                // Dynamically update short codes based on selected Main Category (Option 1: Smart 2-3 char code)
                const selOpt = this.options[this.selectedIndex];
                const dbCode = selOpt?.dataset?.code || '';
                const siblingCodes = Array.from(this.options)
                    .filter(opt => opt.value && opt.value !== catId)
                    .map(opt => opt.dataset.code || (opt.text ? opt.text.replace(/[^A-Za-z0-9]/g, '').substring(0, 3).toUpperCase() : ''))
                    .filter(Boolean);
                const mainCode = getCategorySmartShortCode(catName, dbCode, siblingCodes, 'CAT');
                const codeMain = card.querySelector('.card-cat-code-main');
                if (codeMain) {
                    codeMain.value = mainCode;
                    delete codeMain.dataset.userEdited;
                }
                const codeSub = card.querySelector('.card-cat-code-sub');
                if (codeSub) {
                    codeSub.value = '';
                    delete codeSub.dataset.userEdited;
                }
                const codeSubSub = card.querySelector('.card-cat-code-subsub');
                if (codeSubSub) {
                    codeSubSub.value = '';
                    delete codeSubSub.dataset.userEdited;
                }

                // Reset userEdited on QR prefix so it recalculates dynamically
                const qrField = card.querySelector('.card-cat-qr-prefix');
                if (qrField) {
                    delete qrField.dataset.userEdited;
                }

                // Save to state
                if (variantStateMap[key]) {
                    variantStateMap[key].category_id = catId;
                    variantStateMap[key].category_name = catName;
                    variantStateMap[key].category_code = mainCode;
                    variantStateMap[key].subcategory_id = '';
                    variantStateMap[key].subcategory_name = '';
                    variantStateMap[key].subcategory_code = '';
                    variantStateMap[key].sub_subcategory_id = '';
                    variantStateMap[key].sub_subcategory_name = '';
                    variantStateMap[key].sub_subcategory_code = '';
                    variantStateMap[key].category_path = catName || 'General Category';
                    const badge = card.querySelector('.card-cat-hierarchy-text');
                    if (badge) badge.textContent = catName || 'General Category';
                    // Update breadcrumb
                    const crumb = card.querySelector('.card-cat-breadcrumb-text');
                    if (crumb) crumb.textContent = catName || 'Select a category above...';
                    // Enable/disable New Subcat button
                    const btnNewSub = card.querySelector('.card-btn-new-subcat');
                    if (btnNewSub) btnNewSub.disabled = !catId;
                }

                // Dynamically update Whole Code for QR Code
                updateCardQrPrefix(key);

                if (!catId) {
                    if (subSel) { subSel.innerHTML = '<option value="">-- Select Category First --</option>'; }
                    return;
                }

                fetch(`${baseUrl}/api/warehouse-subcategories/${catId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                    .then(r => r.json())
                    .then(data => {
                        if (subSel) {
                            subSel.innerHTML = '<option value="">-- Select Sub Category --</option>';
                            if (Array.isArray(data) && data.length > 0) {
                                data.forEach(item => {
                                    const opt = document.createElement('option');
                                    opt.value = item.id;
                                    opt.textContent = item.name;
                                    opt.dataset.code = item.code || '';
                                    subSel.appendChild(opt);
                                });
                            }
                            subSel.disabled = false;
                        }
                    })
                    .catch(() => {
                        if (subSel) { subSel.innerHTML = '<option value="">-- Error Loading --</option>'; subSel.disabled = false; }
                    });
            });
        });

        // Sub Category change -> load sub-subcategories
        document.querySelectorAll('.card-cat-sub').forEach(function(subSel) {
            subSel.addEventListener('change', function() {
                const key = this.dataset.key;
                const subcatId = this.value;
                const subcatName = subcatId ? (this.options[this.selectedIndex]?.text || '').replace(/^--.*--$/, '').trim() : '';
                const card = document.querySelector(`.variant-matrix-card[data-key="${key}"]`);
                if (!card) return;

                const hiddenSub = card.querySelector('.card-cat-sub-hidden');
                const subSubSel = card.querySelector('.card-cat-subsub');
                const hiddenSubSub = card.querySelector('.card-cat-subsub-hidden');
                const mainSel = card.querySelector('.card-cat-main');
                const catName = mainSel ? (mainSel.options[mainSel.selectedIndex]?.text || '').replace(/^--.*--$/, '').trim() : (variantStateMap[key]?.category_name || '');

                if (hiddenSub) hiddenSub.value = subcatId;
                resetCardHierarchyState(card, 'sub');
                if (subSubSel) { subSubSel.innerHTML = '<option value="">-- Loading... --</option>'; subSubSel.disabled = true; }
                if (hiddenSubSub) hiddenSubSub.value = '';

                // Dynamically update subcategory short code based on selected Sub Category (Option 1: Smart 2-3 char code)
                const selOpt = this.options[this.selectedIndex];
                const dbCode = selOpt?.dataset?.code || '';
                const siblingCodes = Array.from(this.options)
                    .filter(opt => opt.value && opt.value !== subcatId)
                    .map(opt => opt.dataset.code || (opt.text ? opt.text.replace(/[^A-Za-z0-9]/g, '').substring(0, 3).toUpperCase() : ''))
                    .filter(Boolean);
                const subCode = getCategorySmartShortCode(subcatName, dbCode, siblingCodes, 'SUB');
                const codeSub = card.querySelector('.card-cat-code-sub');
                if (codeSub) {
                    codeSub.value = subCode;
                    delete codeSub.dataset.userEdited;
                }
                const codeSubSub = card.querySelector('.card-cat-code-subsub');
                if (codeSubSub) {
                    codeSubSub.value = '';
                    delete codeSubSub.dataset.userEdited;
                }

                const qrField = card.querySelector('.card-cat-qr-prefix');
                if (qrField) {
                    delete qrField.dataset.userEdited;
                }

                // Save to state
                if (variantStateMap[key]) {
                    variantStateMap[key].subcategory_id = subcatId;
                    variantStateMap[key].subcategory_name = subcatName;
                    variantStateMap[key].subcategory_code = subCode;
                    variantStateMap[key].sub_subcategory_id = '';
                    variantStateMap[key].sub_subcategory_name = '';
                    variantStateMap[key].sub_subcategory_code = '';
                    const parts = [catName, subcatName].filter(Boolean);
                    variantStateMap[key].category_path = parts.join(' → ') || 'General Category';
                    // Update badge
                    const badge = card.querySelector('.card-cat-hierarchy-text');
                    if (badge) badge.textContent = variantStateMap[key].category_path;
                    // Update breadcrumb
                    const crumb = card.querySelector('.card-cat-breadcrumb-text');
                    if (crumb) crumb.textContent = variantStateMap[key].category_path || 'Select a category above...';
                    // Enable/disable New SubSubcat button
                    const btnNewSubSub = card.querySelector('.card-btn-new-subsubcat');
                    if (btnNewSubSub) btnNewSubSub.disabled = !subcatId;
                }

                // Dynamically update Whole Code for QR Code
                updateCardQrPrefix(key);

                if (!subcatId) {
                    if (subSubSel) { subSubSel.innerHTML = '<option value="">-- Select Sub Category First --</option>'; }
                    return;
                }

                fetch(`${baseUrl}/api/warehouse-sub-subcategories/${subcatId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                    .then(r => r.json())
                    .then(data => {
                        if (subSubSel) {
                            subSubSel.innerHTML = '<option value="">-- Select Sub Sub Category --</option>';
                            if (Array.isArray(data) && data.length > 0) {
                                data.forEach(item => {
                                    const opt = document.createElement('option');
                                    opt.value = item.id;
                                    opt.textContent = item.name;
                                    opt.dataset.code = item.code || '';
                                    subSubSel.appendChild(opt);
                                });
                            }
                            subSubSel.disabled = false;
                        }
                    })
                    .catch(() => {
                        if (subSubSel) { subSubSel.innerHTML = '<option value="">-- Error Loading --</option>'; subSubSel.disabled = false; }
                    });
            });
        });

        // Sub-Sub Category change -> save to state
        document.querySelectorAll('.card-cat-subsub').forEach(function(subSubSel) {
            subSubSel.addEventListener('change', function() {
                const key = this.dataset.key;
                const subSubcatId = this.value;
                const subSubcatName = subSubcatId ? (this.options[this.selectedIndex]?.text || '').replace(/^--.*--$/, '').trim() : '';
                const card = document.querySelector(`.variant-matrix-card[data-key="${key}"]`);
                if (!card) return;

                const hiddenSubSub = card.querySelector('.card-cat-subsub-hidden');
                if (hiddenSubSub) hiddenSubSub.value = subSubcatId;

                // Dynamically update sub-subcategory short code based on selected Sub Sub Category (Option 1: Smart 2-3 char code)
                const selOpt = this.options[this.selectedIndex];
                const dbCode = selOpt?.dataset?.code || '';
                const siblingCodes = Array.from(this.options)
                    .filter(opt => opt.value && opt.value !== subSubcatId)
                    .map(opt => opt.dataset.code || (opt.text ? opt.text.replace(/[^A-Za-z0-9]/g, '').substring(0, 3).toUpperCase() : ''))
                    .filter(Boolean);
                const subSubCode = getCategorySmartShortCode(subSubcatName, dbCode, siblingCodes, 'SSC');
                const codeSubSub = card.querySelector('.card-cat-code-subsub');
                if (codeSubSub) {
                    codeSubSub.value = subSubCode;
                    delete codeSubSub.dataset.userEdited;
                }

                const qrField = card.querySelector('.card-cat-qr-prefix');
                if (qrField) {
                    delete qrField.dataset.userEdited;
                }

                if (variantStateMap[key]) {
                    variantStateMap[key].sub_subcategory_id = subSubcatId;
                    variantStateMap[key].sub_subcategory_name = subSubcatName;
                    variantStateMap[key].sub_subcategory_code = subSubCode;
                    const catName = variantStateMap[key].category_name || '';
                    const subcatName = variantStateMap[key].subcategory_name || '';
                    const parts = [catName, subcatName, subSubcatName].filter(Boolean);
                    variantStateMap[key].category_path = parts.join(' → ') || 'General Category';
                    // Update badge
                    const badge = card.querySelector('.card-cat-hierarchy-text');
                    if (badge) badge.textContent = variantStateMap[key].category_path;
                    // Update breadcrumb
                    const crumb = card.querySelector('.card-cat-breadcrumb-text');
                    if (crumb) crumb.textContent = variantStateMap[key].category_path || 'Select a category above...';
                }

                // Dynamically update Whole Code for QR Code
                updateCardQrPrefix(key);
            });
        });

        // QR Prefix input -> save to state (mark user-edited)
        document.querySelectorAll('.card-cat-qr-prefix').forEach(function(inp) {
            inp.addEventListener('input', function() {
                const key = this.dataset.key;
                this.value = this.value.toUpperCase();
                this.dataset.userEdited = '1';
                if (variantStateMap[key]) {
                    variantStateMap[key].qr_code_prefix = this.value.trim().toUpperCase();
                }
            });
        });

        // Category Short Code inputs -> save to state & update QR prefix
        document.querySelectorAll('.card-cat-code-main, .card-cat-code-sub, .card-cat-code-subsub').forEach(function(inp) {
            inp.addEventListener('input', function() {
                const key = this.dataset.key;
                this.value = this.value.toUpperCase();
                this.dataset.userEdited = '1';
                if (variantStateMap[key]) {
                    if (this.classList.contains('card-cat-code-main')) variantStateMap[key].category_code = this.value.trim().toUpperCase();
                    if (this.classList.contains('card-cat-code-sub')) variantStateMap[key].subcategory_code = this.value.trim().toUpperCase();
                    if (this.classList.contains('card-cat-code-subsub')) variantStateMap[key].sub_subcategory_code = this.value.trim().toUpperCase();
                }
                const card = document.querySelector(`.variant-matrix-card[data-key="${key}"]`);
                const qrField = card?.querySelector('.card-cat-qr-prefix');
                if (qrField) delete qrField.dataset.userEdited;
                updateCardQrPrefix(key, true);
            });
        });

        // Wire up + New buttons on variant cards to open respective Quick Add modals
        document.querySelectorAll('.card-btn-new-main').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                activeCardKeyForCatModal = this.dataset.key;
                document.getElementById('modalMainCatName').value = '';
                const modal = new bootstrap.Modal(document.getElementById('modalAddMainCategory'));
                modal.show();
            });
        });

        document.querySelectorAll('.card-btn-new-subcat').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const key = this.dataset.key;
                activeCardKeyForCatModal = key;
                const card = document.querySelector(`.variant-matrix-card[data-key="${key}"]`);
                const mainSel = card?.querySelector('.card-cat-main');
                const catId = mainSel?.value;
                const catName = catId ? (mainSel.options[mainSel.selectedIndex]?.text || '') : '';
                if (!catId) {
                    alert('Please select a Main Category first.');
                    return;
                }
                document.getElementById('modalSubcatParentName').value = catName;
                document.getElementById('modalSubcatName').value = '';
                const modal = new bootstrap.Modal(document.getElementById('modalAddSubcategory'));
                modal.show();
            });
        });

        document.querySelectorAll('.card-btn-new-subsubcat').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const key = this.dataset.key;
                activeCardKeyForCatModal = key;
                const card = document.querySelector(`.variant-matrix-card[data-key="${key}"]`);
                const subSel = card?.querySelector('.card-cat-sub');
                const subcatId = subSel?.value;
                const subcatName = subcatId ? (subSel.options[subSel.selectedIndex]?.text || '') : '';
                if (!subcatId) {
                    alert('Please select a Sub Category first.');
                    return;
                }
                document.getElementById('modalSubSubcatParentName').value = subcatName;
                document.getElementById('modalSubSubcatName').value = '';
                const modal = new bootstrap.Modal(document.getElementById('modalAddSubSubcategory'));
                modal.show();
            });
        });
    }

    function calculateMatrixTotals() {
        let totalQty = 0;
        let totalValuation = 0;
        let totalWeight = 0;
        let seriesCount = 0;
        let indivCount = 0;

        const qtyInputs = document.querySelectorAll('.matrix-qty-input');
        qtyInputs.forEach(inp => {
            const card = inp.closest('.variant-matrix-card') || inp.closest('tr');
            const qty = parseInt(inp.value) || 0;
            totalQty += qty;

            if (card) {
                const costInp = card.querySelector('.matrix-cost-input');
                const sellInp = card.querySelector('.matrix-selling-input');
                const mrpInp = card.querySelector('.matrix-mrp-input');
                const weightInp = card.querySelector('.matrix-weight-input');

                let unitVal = parseFloat(costInp ? costInp.value : 0) || 0;
                if (unitVal <= 0) {
                    unitVal = parseFloat(sellInp ? sellInp.value : 0) || parseFloat(mrpInp ? mrpInp.value : 0) || 0;
                }
                const weight = parseFloat(weightInp ? weightInp.value : 0) || 0;

                totalValuation += qty * unitVal;
                totalWeight += qty * weight;
            }
        });

        variantRowsList.forEach(k => {
            const st = variantStateMap[k] || {};
            if (st.qr_type === 'SERIES_BATCH') {
                seriesCount++;
            } else {
                indivCount++;
            }
        });

        if (lblMatrixTotalQty) lblMatrixTotalQty.textContent = totalQty;
        if (lblFooterTotalQty) lblFooterTotalQty.textContent = totalQty + ' pcs';
        if (lblFooterTotalValuation) {
            lblFooterTotalValuation.textContent = '₹' + totalValuation.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        if (lblFooterTotalWeight) {
            lblFooterTotalWeight.textContent = totalWeight > 0 ? (totalWeight.toFixed(3) + ' kg') : '—';
        }

        const lblSeriesSummary = document.getElementById('lblFooterSeriesSummary');
        if (lblSeriesSummary) {
            if (seriesCount > 0 && indivCount > 0) {
                lblSeriesSummary.innerHTML = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-shuffle me-1"></i>Mixed Packaging (${indivCount} Indiv, ${seriesCount} Series)</span>`;
            } else if (seriesCount > 0) {
                lblSeriesSummary.innerHTML = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-collection me-1"></i>All Series Batch (${totalQty} Total Units)</span>`;
            } else {
                lblSeriesSummary.innerHTML = `<span class="badge bg-secondary-subtle text-secondary border"><i class="bi bi-tag me-1"></i>All Individual Pieces</span>`;
            }
        }

        // Live 2-way sync to Section 2 Total Quantity
        if (section2QtyInput && (qtyInputs.length > 0 || totalQty > 0)) {
            section2QtyInput.value = totalQty;
        }
    }

    // Delegated input listeners for matrix card fields to preserve state & dynamically update valuations
    const matrixContainer = document.getElementById('variantMatrixCard') || document.getElementById('variantCardsContainer');
    if (matrixContainer) {
        matrixContainer.addEventListener('input', function(e) {
            const target = e.target;
            const key = target.dataset.key;
            if (!key) return;

            variantStateMap[key] = variantStateMap[key] || {};

            if (target.classList.contains('matrix-qty-input')) {
                const q = parseInt(target.value) || 0;
                variantStateMap[key].quantity = q;
                calculateMatrixTotals();
                
                // Auto update series ranges dynamically across cards
                if (typeof window.isSeriesBatchMode === 'function' && window.isSeriesBatchMode()) {
                    autoGenerateAllSeriesRanges(true);
                }
            } else if (target.classList.contains('matrix-start-code')) {
                target.dataset.userEdited = '1';
                variantStateMap[key].series_start_code = target.value;
            } else if (target.classList.contains('matrix-end-code')) {
                target.dataset.userEdited = '1';
                variantStateMap[key].series_end_code = target.value;
            } else if (target.classList.contains('matrix-weight-input')) {
                variantStateMap[key].weight = target.value;
                calculateMatrixTotals();
            } else if (target.classList.contains('matrix-cost-input')) {
                variantStateMap[key].purchase_price = target.value;
                calculateMatrixTotals();
            } else if (target.classList.contains('matrix-mrp-input')) {
                variantStateMap[key].mrp = target.value;
                const card = target.closest('.variant-matrix-card') || target.closest('tr');
                const sellingInp = card ? card.querySelector('.matrix-selling-input') : null;
                if (sellingInp) {
                    sellingInp.value = target.value;
                    variantStateMap[key].selling_price = target.value;
                }
                calculateMatrixTotals();
            } else if (target.classList.contains('matrix-selling-input')) {
                variantStateMap[key].selling_price = target.value;
                calculateMatrixTotals();
            } else if (target.classList.contains('matrix-hsn-input')) {
                variantStateMap[key].hsn_code = target.value;
            } else if (target.classList.contains('matrix-sku-input')) {
                variantStateMap[key].sku = target.value;
                if (typeof window.isSeriesBatchMode === 'function' && window.isSeriesBatchMode()) {
                    autoGenerateAllSeriesRanges(true);
                }
            } else if (target.classList.contains('matrix-mfg-input')) {
                variantStateMap[key].mfg_date = target.value;
            } else if (target.classList.contains('matrix-exp-input')) {
                variantStateMap[key].expiry_date = target.value;
            }
        });
    }

    // Batch Apply Quantity
    const btnApplyBatchQty = document.getElementById('btnApplyBatchQty');
    if (btnApplyBatchQty) {
        btnApplyBatchQty.addEventListener('click', function() {
            const batchVal = document.getElementById('batchQtyInput').value;
            if (batchVal === '') {
                alert('Please enter a quantity value to fill across all variants.');
                return;
            }
            const q = Math.max(0, parseInt(batchVal) || 0);
            document.querySelectorAll('.matrix-qty-input').forEach(inp => {
                const k = inp.dataset.key;
                if (k && variantStateMap[k] && variantStateMap[k].is_autoloaded) {
                    return; // Locked for auto-loaded supplier products
                }
                inp.value = q;
                if (k) {
                    variantStateMap[k] = variantStateMap[k] || {};
                    variantStateMap[k].quantity = q;
                }
            });
            if (typeof window.isSeriesBatchMode === 'function' && window.isSeriesBatchMode()) {
                autoGenerateAllSeriesRanges(true);
            } else {
                calculateMatrixTotals();
            }
        });
    }

    // Batch Apply Cost
    const btnApplyBatchCost = document.getElementById('btnApplyBatchCost');
    if (btnApplyBatchCost) {
        btnApplyBatchCost.addEventListener('click', function() {
            const batchVal = document.getElementById('batchCostInput').value;
            if (batchVal === '') {
                alert('Please enter a cost price value to fill across all variants.');
                return;
            }
            const c = Math.max(0, parseFloat(batchVal) || 0).toFixed(2);
            document.querySelectorAll('.matrix-cost-input').forEach(inp => {
                inp.value = c;
                const k = inp.dataset.key;
                if (k) {
                    variantStateMap[k] = variantStateMap[k] || {};
                    variantStateMap[k].purchase_price = c;
                }
            });
            calculateMatrixTotals();
        });
    }

    // Batch Apply MRP
    const btnApplyBatchMrp = document.getElementById('btnApplyBatchMrp');
    if (btnApplyBatchMrp) {
        btnApplyBatchMrp.addEventListener('click', function() {
            const batchVal = document.getElementById('batchMrpInput').value;
            if (batchVal === '') {
                alert('Please enter an MRP value to fill across all variants.');
                return;
            }
            const m = Math.max(0, parseFloat(batchVal) || 0).toFixed(2);
            document.querySelectorAll('.matrix-mrp-input').forEach(inp => {
                inp.value = m;
                const k = inp.dataset.key;
                if (k) {
                    variantStateMap[k] = variantStateMap[k] || {};
                    variantStateMap[k].mrp = m;
                }
                const card = inp.closest('.variant-matrix-card') || inp.closest('tr');
                const sellInp = card ? card.querySelector('.matrix-selling-input') : null;
                if (sellInp) {
                    sellInp.value = m;
                    if (k) variantStateMap[k].selling_price = m;
                }
            });
            calculateMatrixTotals();
        });
    }

    // Batch Apply Mfg Date
    const btnApplyBatchMfg = document.getElementById('btnApplyBatchMfg');
    if (btnApplyBatchMfg) {
        btnApplyBatchMfg.addEventListener('click', function() {
            const batchVal = document.getElementById('batchMfgInput').value;
            if (!batchVal) {
                alert('Please select a manufacture date to fill across all variants.');
                return;
            }
            document.querySelectorAll('.matrix-mfg-input').forEach(inp => {
                inp.value = batchVal;
                const k = inp.dataset.key;
                if (k) {
                    variantStateMap[k] = variantStateMap[k] || {};
                    variantStateMap[k].mfg_date = batchVal;
                }
            });
        });
    }

    // Batch Apply Exp Date
    const btnApplyBatchExp = document.getElementById('btnApplyBatchExp');
    if (btnApplyBatchExp) {
        btnApplyBatchExp.addEventListener('click', function() {
            const batchVal = document.getElementById('batchExpInput').value;
            if (!batchVal) {
                alert('Please select an expiry date to fill across all variants.');
                return;
            }
            document.querySelectorAll('.matrix-exp-input').forEach(inp => {
                inp.value = batchVal;
                const k = inp.dataset.key;
                if (k) {
                    variantStateMap[k] = variantStateMap[k] || {};
                    variantStateMap[k].expiry_date = batchVal;
                }
            });
        });
    }

    // Synchronize base price inputs from Section 2 to Batch inputs & default matrix rows
    if (section2CostInput) {
        section2CostInput.addEventListener('input', function() {
            const batchInp = document.getElementById('batchCostInput');
            if (batchInp && (!batchInp.value || batchInp.value === '0.00')) {
                batchInp.value = this.value;
            }
        });
    }
    if (section2MrpInput) {
        section2MrpInput.addEventListener('input', function() {
            const batchInp = document.getElementById('batchMrpInput');
            if (batchInp && (!batchInp.value || batchInp.value === '0.00')) {
                batchInp.value = this.value;
            }
        });
    }

    // Form Submit Interceptor: Sync category hierarchy from variant cards to root inputs & validate
    const catalogForm = document.getElementById('catalogForm');
    if (catalogForm) {
        catalogForm.addEventListener('submit', function(e) {
            let primaryCatId = '', primarySubcatId = '', primarySubSubcatId = '';
            let primaryCatCode = '', primarySubcatCode = '', primarySubSubcatCode = '', primaryQrPrefix = '';

            for (const k of variantRowsList) {
                const st = variantStateMap[k] || {};
                if (st.category_id) {
                    primaryCatId = st.category_id;
                    primarySubcatId = st.subcategory_id || '';
                    primarySubSubcatId = st.sub_subcategory_id || '';
                    primaryCatCode = st.category_code || '';
                    primarySubcatCode = st.subcategory_code || '';
                    primarySubSubcatCode = st.sub_subcategory_code || '';
                    primaryQrPrefix = st.qr_code_prefix || '';
                    break;
                }
            }

            if (!primaryCatId) {
                const firstMain = document.querySelector('.card-cat-main');
                if (firstMain && firstMain.value) {
                    primaryCatId = firstMain.value;
                    primarySubcatId = document.querySelector('.card-cat-sub')?.value || '';
                    primarySubSubcatId = document.querySelector('.card-cat-subsub')?.value || '';
                    primaryCatCode = document.querySelector('.card-cat-code-main')?.value || '';
                    primarySubcatCode = document.querySelector('.card-cat-code-sub')?.value || '';
                    primarySubSubcatCode = document.querySelector('.card-cat-code-subsub')?.value || '';
                    primaryQrPrefix = document.querySelector('.card-cat-qr-prefix')?.value || '';
                }
            }

            if (document.getElementById('category_id')) document.getElementById('category_id').value = primaryCatId;
            if (document.getElementById('subcategory_id')) document.getElementById('subcategory_id').value = primarySubcatId;
            if (document.getElementById('sub_subcategory_id')) document.getElementById('sub_subcategory_id').value = primarySubSubcatId;
            if (document.getElementById('category_code')) document.getElementById('category_code').value = primaryCatCode;
            if (document.getElementById('subcategory_code')) document.getElementById('subcategory_code').value = primarySubcatCode;
            if (document.getElementById('sub_subcategory_code')) document.getElementById('sub_subcategory_code').value = primarySubSubcatCode;
            if (document.getElementById('qr_code_prefix')) document.getElementById('qr_code_prefix').value = primaryQrPrefix;

            if (!primaryCatId && variantRowsList.length > 0) {
                e.preventDefault();
                alert('Please select a Main Category for your variant(s).');
                return false;
            }
        });
    }

    // Initial matrix render
    renderVariantMatrix();
});
</script>
