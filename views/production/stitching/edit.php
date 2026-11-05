<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-threads me-2 text-warning"></i>Edit Stitching Batch — <?= e($batch['name'] ?: $batch['batch_number']) ?></h2>
        <span class="text-muted">Update sewing quantities, multi-catalog pricing, garment weights, and worker assignments</span>
    </div>
    <a href="<?= url('production/stitching') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Stitching Batches
    </a>
</div>

<form method="POST" action="<?= url('production/stitching/update/' . $batch['id']) ?>" id="stitchingEditForm">
    <?= CSRF::field() ?>

    <input type="hidden" name="program_id" value="<?= e($batch['program_id']) ?>">
    <input type="hidden" name="cutting_batch_id" value="<?= e($batch['cutting_batch_id'] ?? '') ?>">
    <input type="hidden" name="category_id" value="<?= e($batch['category_id'] ?? '') ?>">
    <input type="hidden" name="subcategory_id" value="<?= e($batch['subcategory_id'] ?? '') ?>">
    <input type="hidden" name="sub_subcategory_id" value="<?= e($batch['sub_subcategory_id'] ?? '') ?>">

    <div class="row g-4">
        <!-- Left Column: Batch Details -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>1. Batch Information</h5>
                </div>
                <div class="card-body p-4">
                    <!-- Stitching Name -->
                    <div class="mb-3">
                        <label class="form-label required fw-semibold">Stitching Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="stitchingName" class="form-control text-uppercase fw-bold" value="<?= e($batch['name']) ?>" required>
                    </div>

                    <!-- Batch Number & Status -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Batch Number</label>
                            <input type="text" class="form-control bg-light fw-bold" value="<?= e($batch['batch_number'] ?: ('STCH-' . $batch['id'])) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="PENDING" <?= $batch['status'] === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                                <option value="IN_PROGRESS" <?= $batch['status'] === 'IN_PROGRESS' ? 'selected' : '' ?>>In Progress</option>
                                <option value="COMPLETED" <?= $batch['status'] === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                    </div>

                    <!-- Program & Cutting Details -->
                    <div class="mb-3 p-3 bg-light rounded border">
                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                            <span class="badge bg-primary px-2 py-1"><?= e($batch['program_number'] ?? 'Program') ?></span>
                            <div class="d-flex align-items-center gap-1">
                                <?php if (!empty($batch['cutting_batch_number'])): ?>
                                    <span class="badge bg-info-subtle text-info border"><?= e($batch['cutting_batch_number']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($cuttingUsableWeight) && (float)$cuttingUsableWeight > 0): ?>
                                    <span class="badge bg-success-subtle text-success border">
                                        <i class="bi bi-speedometer2 me-1"></i><?= number_format((float)$cuttingUsableWeight, 2) ?> kg Usable
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="fw-bold text-dark fs-6 mb-2"><?= e($batch['program_name'] ?: $batch['product_name']) ?></div>
                        
                        <!-- Catalog Hierarchy List -->
                        <div class="p-2 bg-white rounded border">
                            <div class="small text-muted mb-1"><i class="bi bi-diagram-3 me-1 text-info"></i>Assigned Catalog(s):</div>
                            <div class="d-flex flex-column gap-1">
                                <?php if (!empty($distinctCatalogs)): ?>
                                    <?php foreach ($distinctCatalogs as $dCat): ?>
                                        <div class="badge bg-primary-subtle text-primary border text-start p-2 d-flex justify-content-between align-items-center flex-wrap gap-1">
                                            <span><i class="bi bi-diagram-3-fill me-1 text-primary"></i><strong><?= e($dCat['name']) ?></strong></span>
                                            <span class="badge bg-white text-primary border"><?= $dCat['item_count'] ?> items &bull; <?= $dCat['total_qty'] ?> pcs</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php 
                                        $catHierarchy = array_filter([
                                            $batch['category_name'] ?? '',
                                            $batch['subcategory_name'] ?? '',
                                            $batch['sub_subcategory_name'] ?? ''
                                        ]);
                                        $catStr = !empty($catHierarchy) ? implode(' › ', $catHierarchy) : 'Apparel Catalog';
                                    ?>
                                    <span class="badge bg-light text-dark border fs-7 text-start"><?= e($catStr) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tailor / Worker -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tailor / Stitching Master</label>
                        <select name="worker_id" class="form-select">
                            <option value="">Select Worker / Operator</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($batch['worker_id'] == $u['id']) ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes &amp; Remarks</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Stitching instructions, lot details..."><?= e($batch['notes']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Quantities, Multi-Catalog Grouping & Weight Breakdown -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100 d-flex flex-column">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-grid-3x3 me-2 text-primary"></i>2. Stitching Quantities, Weights &amp; Pricing
                        </h5>
                        <small class="text-muted">Set distinct Base Price &amp; Discount Price per Catalog</small>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="copyCutQtyToStitchingQty()" title="Copy all Cut Qty values into Stitching Qty inputs">
                            <i class="bi bi-arrow-down-circle me-1"></i>Copy Cut Qty
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="toggleQuickPriceSetter()" title="Set Base & Discount Price per Catalog in bulk">
                            <i class="bi bi-cash-stack me-1"></i>Set Prices by Catalog
                        </button>
                        <?php 
                            $calcTotalCut = 0;
                            foreach ($items as $itItem) {
                                $calcTotalCut += (int)($itItem['received_qty'] ?? $itItem['cut_qty'] ?? $itItem['stitched_qty'] ?? 0);
                            }
                        ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-7" id="totalCutBadge">
                            <i class="bi bi-scissors me-1"></i><?= number_format($calcTotalCut) ?> Cut pcs
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-7" id="totalStitchedBadge">
                            <i class="bi bi-check2-circle me-1"></i><?= number_format($batch['stitched_qty'] ?? 0) ?> Stitched pcs
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 fs-7" id="cuttingWeightBadge">
                            <i class="bi bi-scissors me-1"></i>Cut Wt: <?= number_format((float)($cuttingUsableWeight ?? 0), 2) ?> kg
                        </span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 fs-7" id="totalWeightBadge">
                            <i class="bi bi-speedometer2 me-1"></i>Stitch Wt: <?= number_format($batch['total_weight'] ?? 0, 2) ?> kg
                        </span>
                        <span class="badge bg-success text-white border border-success px-2 py-1 fs-7" id="weightMatchBadge" style="<?= (!empty($cuttingUsableWeight) && (float)$cuttingUsableWeight > 0) ? '' : 'display: none;' ?>">
                            <i class="bi bi-check2-circle me-1"></i>Weight Matched
                        </span>
                    </div>
                </div>

                <!-- Catalog Dynamic Price Loader Bar -->
                <div id="catalogPriceLoaderBar" class="px-3 py-2 bg-light-subtle border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap flex-grow-1">
                        <span class="small fw-bold text-dark d-flex align-items-center">
                            <i class="bi bi-tag-fill text-success me-1"></i> Catalog Pricing:
                        </span>
                        <div style="min-width: 220px; max-width: 320px;" class="flex-grow-1">
                            <select id="quickPriceCatalogSelect" class="form-select form-select-sm fw-semibold shadow-sm border-success-subtle" onchange="onCatalogPriceDropdownChange(this.value)">
                                <option value="ALL">All Catalogs in Batch (<?= count($items) ?> items)</option>
                                <?php if (!empty($distinctCatalogs)): ?>
                                    <?php foreach ($distinctCatalogs as $dCat): ?>
                                        <option value="<?= e($dCat['name']) ?>"><?= e($dCat['name']) ?> (<?= $dCat['item_count'] ?> items)</option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <div class="input-group input-group-sm" style="width: 145px;">
                                <span class="input-group-text bg-white fw-bold text-muted">Base ₹</span>
                                <input type="number" id="quickBulkBasePrice" class="form-control form-control-sm text-center fw-bold" placeholder="0.00" min="0.01" step="0.01" title="Base Price (₹)">
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <div class="input-group input-group-sm" style="width: 145px;">
                                <span class="input-group-text bg-white fw-bold text-muted">Disc ₹</span>
                                <input type="number" id="quickBulkDiscPrice" class="form-control form-control-sm text-center fw-bold" placeholder="0.00" min="0.01" step="0.01" title="Discount Price (₹)">
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-success fw-semibold shadow-sm px-3" onclick="applyQuickPricesFromBar()" title="Load and set prices for the selected catalog into the table">
                            <i class="bi bi-arrow-down-circle-fill me-1"></i> Set Prices to Table
                        </button>
                    </div>
                    <div id="quickPriceStatusMsg" class="small fw-semibold text-success" style="display: none;"></div>
                </div>

                <!-- Catalog Filter Navigation Tabs (if multiple catalogs) -->
                <?php if (!empty($distinctCatalogs) && count($distinctCatalogs) > 1): ?>
                    <div id="catalogTabsContainer" class="px-3 py-2 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span class="small fw-semibold text-muted me-2"><i class="bi bi-funnel me-1"></i>Catalog Filter:</span>
                            <div class="btn-group btn-group-sm flex-wrap" role="group" id="catalogNavTabs">
                                <button type="button" class="btn btn-primary active fw-semibold btn-catalog-filter" data-target-catalog="ALL" onclick="filterTableByCatalog('ALL')">
                                    <i class="bi bi-grid-fill me-1"></i>All Catalogs <span class="badge bg-white text-primary ms-1"><?= count($items) ?></span>
                                </button>
                                <?php foreach ($distinctCatalogs as $dCat): ?>
                                    <button type="button" class="btn btn-outline-primary fw-semibold btn-catalog-filter text-truncate" style="max-width: 250px;" data-target-catalog="<?= e($dCat['name']) ?>" onclick="filterTableByCatalog('<?= e($dCat['name']) ?>')">
                                        <i class="bi bi-tag-fill me-1"></i><?= e($dCat['name']) ?> <span class="badge bg-primary-subtle text-primary ms-1"><?= $dCat['item_count'] ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="small text-muted" id="activeCatalogCountInfo">Showing all <strong><?= count($items) ?></strong> items</div>
                    </div>
                <?php endif; ?>

                <!-- Quick Price Setup Panel -->
                <div id="quickPricePanel" class="p-3 bg-light border-bottom" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0 fw-bold text-success"><i class="bi bi-lightning-charge-fill me-1"></i>Quick Price Setup by Catalog</h6>
                        <button type="button" class="btn btn-sm btn-close" onclick="toggleQuickPriceSetter()" aria-label="Close"></button>
                    </div>
                    <p class="small text-muted mb-2">Set Base Price (₹) and Discount Price (₹) for all variants of a specific catalog at once:</p>
                    <div id="quickPriceRows" class="d-flex flex-column gap-2">
                        <?php if (!empty($distinctCatalogs)): ?>
                            <?php foreach ($distinctCatalogs as $cIdx => $dCat): ?>
                                <div class="p-2 bg-white rounded border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2" style="min-width: 220px;">
                                        <span class="badge bg-primary-subtle text-primary border"><i class="bi bi-tag-fill me-1"></i><?= e($dCat['name']) ?></span>
                                        <span class="text-muted small">(<?= $dCat['item_count'] ?> items)</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <div class="d-flex align-items-center gap-1">
                                            <label class="small text-muted fw-semibold mb-0">Base ₹:</label>
                                            <input type="number" id="quickBasePrice_<?= $cIdx ?>" class="form-control form-control-sm text-center fw-bold" style="width: 100px;" placeholder="Base ₹" min="0.01" step="0.01">
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <label class="small text-muted fw-semibold mb-0">Discount ₹:</label>
                                            <input type="number" id="quickDiscPrice_<?= $cIdx ?>" class="form-control form-control-sm text-center fw-bold" style="width: 100px;" placeholder="Disc ₹" min="0.01" step="0.01">
                                        </div>
                                        <button type="button" class="btn btn-sm btn-success fw-semibold" onclick="applyQuickPriceToCatalog('<?= e(addslashes($dCat['name'])) ?>', 'quickBasePrice_<?= $cIdx ?>', 'quickDiscPrice_<?= $cIdx ?>')">
                                            <i class="bi bi-check-lg me-1"></i>Apply to Catalog
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body p-0 flex-grow-1">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 200px;">Catalog &amp; Variant Attribute</th>
                                    <th style="width: 100px;" class="text-center text-primary"><i class="bi bi-scissors me-1"></i>Cut Qty</th>
                                    <th style="width: 120px;" class="text-center">Stitching Qty (pcs) <span class="text-danger">*</span></th>
                                    <th style="width: 120px;" class="text-center">Per Pc Wt (kg) <span class="text-danger">*</span></th>
                                    <th style="width: 120px;" class="text-center">Total Wt (kg) <span class="text-danger">*</span></th>
                                    <th style="width: 120px;" class="text-center">Base Price (₹) <span class="text-danger">*</span></th>
                                    <th style="width: 120px;" class="text-center">Discount Price (₹) <span class="text-danger">*</span></th>
                                </tr>
                            </thead>
                            <tbody id="variantsTableBody">
                                <?php foreach ($items as $idx => $it): ?>
                                    <?php 
                                        $size = !empty($it['size_name']) ? $it['size_name'] : ($it['display_size'] ?? 'Standard');
                                        $color = !empty($it['color_name']) ? $it['color_name'] : ($it['display_color'] ?? 'Assorted');
                                        $catHierarchy = !empty($it['catalog_hierarchy']) ? $it['catalog_hierarchy'] : 'General Apparel';
                                        $stitchedQty = (int)($it['stitched_qty'] ?? 0);
                                        $receivedQty = (int)($it['received_qty'] ?? $stitchedQty);
                                        $totalItemWeight = (float)($it['weight'] ?? 0);
                                        $unitWeight = isset($it['weight_per_piece']) && (float)$it['weight_per_piece'] > 0 
                                            ? (float)$it['weight_per_piece'] 
                                            : ($stitchedQty > 0 && $totalItemWeight > 0 ? round($totalItemWeight / $stitchedQty, 3) : 0);
                                        $basePrice = (float)($it['base_price'] ?? 0);
                                        $discountPrice = (float)($it['discount_price'] ?? 0);

                                        $stitchedQtyVal = $stitchedQty > 0 ? $stitchedQty : '';
                                        $unitWeightVal = $unitWeight > 0 ? number_format($unitWeight, 3, '.', '') : '';
                                        $totalItemWeightVal = $totalItemWeight > 0 ? number_format($totalItemWeight, 2, '.', '') : '';
                                        $basePriceVal = $basePrice > 0 ? number_format($basePrice, 2, '.', '') : '';
                                        $discountPriceVal = $discountPrice > 0 ? number_format($discountPrice, 2, '.', '') : '';
                                    ?>
                                    <tr class="variant-item-row" data-catalog="<?= e($catHierarchy) ?>">
                                        <td>
                                            <input type="hidden" name="items[<?= $idx ?>][variant_id]" value="<?= e($it['variant_id'] ?? '') ?>">
                                            <input type="hidden" name="items[<?= $idx ?>][category_id]" value="<?= e($it['category_id'] ?? '') ?>">
                                            <input type="hidden" name="items[<?= $idx ?>][subcategory_id]" value="<?= e($it['subcategory_id'] ?? '') ?>">
                                            <input type="hidden" name="items[<?= $idx ?>][sub_subcategory_id]" value="<?= e($it['sub_subcategory_id'] ?? '') ?>">
                                            <input type="hidden" name="items[<?= $idx ?>][catalog_hierarchy]" value="<?= e($catHierarchy) ?>">
                                            <input type="hidden" name="items[<?= $idx ?>][size_name]" value="<?= e($size) ?>">
                                            <input type="hidden" name="items[<?= $idx ?>][color_name]" value="<?= e($color) ?>">
                                            <input type="hidden" name="items[<?= $idx ?>][received_qty]" value="<?= e($receivedQty) ?>">

                                            <!-- Prominent Catalog Hierarchy Badge -->
                                            <div class="mb-1">
                                                <span class="badge bg-primary-subtle text-primary border text-wrap text-start" style="font-size: 0.72rem; line-height: 1.2;">
                                                    <i class="bi bi-diagram-3-fill me-1 text-primary"></i><?= e($catHierarchy) ?>
                                                </span>
                                            </div>

                                            <!-- Variant Attributes -->
                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 fw-bold"><?= e($size) ?></span>
                                                <span class="badge bg-dark-subtle text-dark border px-2 py-1"><?= e($color) ?></span>
                                                <small class="text-muted ms-1"><code><?= e($it['sku'] ?: ($size . '-' . $color)) ?></code></small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6 fw-bold">
                                                <?= number_format($receivedQty) ?> pcs
                                            </span>
                                        </td>
                                        <td>
                                            <input type="number" name="items[<?= $idx ?>][stitched_qty]" 
                                                class="form-control form-control-sm text-center fw-bold stitching-qty-input border-primary" 
                                                min="1" value="<?= $stitchedQtyVal ?>" placeholder="Qty" required oninput="onRowQtyOrUnitWeightChange(this.closest('tr'))">
                                        </td>
                                        <td>
                                            <input type="number" name="items[<?= $idx ?>][weight_per_piece]" 
                                                class="form-control form-control-sm text-center stitching-unit-weight-input" 
                                                min="0.001" step="0.001" value="<?= $unitWeightVal ?>" placeholder="0.000" required oninput="onRowQtyOrUnitWeightChange(this.closest('tr'))">
                                        </td>
                                        <td>
                                            <input type="number" name="items[<?= $idx ?>][weight]" 
                                                class="form-control form-control-sm text-center fw-bold stitching-weight-input border-info" 
                                                min="0.01" step="0.01" value="<?= $totalItemWeightVal ?>" placeholder="0.00" required oninput="onRowTotalWeightChange(this.closest('tr'))">
                                            <input type="hidden" name="items[<?= $idx ?>][rejected_qty]" value="<?= e($it['rejected_qty'] ?? 0) ?>">
                                            <input type="hidden" name="items[<?= $idx ?>][rework_qty]" value="<?= e($it['rework_qty'] ?? 0) ?>">
                                        </td>
                                        <td>
                                            <input type="number" name="items[<?= $idx ?>][base_price]" 
                                                class="form-control form-control-sm text-center stitching-base-price-input" 
                                                min="0.01" step="0.01" value="<?= $basePriceVal ?>" placeholder="0.00" required oninput="validateStitchingPricePair(this.closest('tr'))">
                                        </td>
                                        <td>
                                            <input type="number" name="items[<?= $idx ?>][discount_price]" 
                                                class="form-control form-control-sm text-center stitching-discount-price-input" 
                                                min="0.01" step="0.01" value="<?= $discountPriceVal ?>" placeholder="0.00" required oninput="validateStitchingPricePair(this.closest('tr'))">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center border-top">
                    <a href="<?= url('production/stitching') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Update Stitching Batch
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function onRowQtyOrUnitWeightChange(row) {
    if (!row) return;
    const qtyInp = row.querySelector('.stitching-qty-input');
    const unitWeightInp = row.querySelector('.stitching-unit-weight-input');
    const totalWeightInp = row.querySelector('.stitching-weight-input');

    if (qtyInp && parseFloat(qtyInp.value) > 0) qtyInp.classList.remove('is-invalid', 'border-danger');
    if (unitWeightInp && parseFloat(unitWeightInp.value) > 0) unitWeightInp.classList.remove('is-invalid', 'border-danger');

    const qty = parseInt(qtyInp?.value) || 0;
    const unitWeight = parseFloat(unitWeightInp?.value) || 0;

    if (qty > 0 && unitWeight > 0) {
        const total = qty * unitWeight;
        if (totalWeightInp) {
            totalWeightInp.value = total.toFixed(2);
            totalWeightInp.classList.remove('is-invalid', 'border-danger');
        }
    } else if (unitWeight === 0 && totalWeightInp && !totalWeightInp.dataset.manual) {
        totalWeightInp.value = '';
    }
    calcStitchingTotals();
}

function onRowTotalWeightChange(row) {
    if (!row) return;
    const qtyInp = row.querySelector('.stitching-qty-input');
    const unitWeightInp = row.querySelector('.stitching-unit-weight-input');
    const totalWeightInp = row.querySelector('.stitching-weight-input');

    if (totalWeightInp) {
        totalWeightInp.dataset.manual = '1';
        if (parseFloat(totalWeightInp.value) > 0) totalWeightInp.classList.remove('is-invalid', 'border-danger');
    }

    const qty = parseInt(qtyInp?.value) || 0;
    const totalWeight = parseFloat(totalWeightInp?.value) || 0;

    if (qty > 0 && totalWeight > 0 && unitWeightInp) {
        unitWeightInp.value = (totalWeight / qty).toFixed(3);
        unitWeightInp.classList.remove('is-invalid', 'border-danger');
    }
    calcStitchingTotals();
}

let currentCuttingUsableWeight = <?= (float)($cuttingUsableWeight ?? 0) ?>;

function calcStitchingTotals() {
    let totalCutQty = 0;
    let totalQty = 0;
    let totalWeight = 0.0;

    document.querySelectorAll('input[name*="[received_qty]"]').forEach(inp => {
        totalCutQty += parseInt(inp.value) || 0;
    });

    document.querySelectorAll('.stitching-qty-input').forEach(inp => {
        totalQty += parseInt(inp.value) || 0;
    });

    document.querySelectorAll('.stitching-weight-input').forEach(inp => {
        totalWeight += parseFloat(inp.value) || 0;
    });

    totalWeight = Math.round(totalWeight * 100) / 100;

    const cutBadge = document.getElementById('totalCutBadge');
    if (cutBadge) {
        cutBadge.innerHTML = '<i class="bi bi-scissors me-1"></i>' + totalCutQty.toLocaleString() + ' Cut pcs';
    }

    const qtyBadge = document.getElementById('totalStitchedBadge');
    if (qtyBadge) {
        qtyBadge.innerHTML = '<i class="bi bi-check2-circle me-1"></i>' + totalQty.toLocaleString() + ' Stitched pcs';
    }

    const cutWeightBadge = document.getElementById('cuttingWeightBadge');
    if (cutWeightBadge) {
        cutWeightBadge.innerHTML = '<i class="bi bi-scissors me-1"></i>Cut Wt: ' + currentCuttingUsableWeight.toFixed(2) + ' kg';
    }

    const weightBadge = document.getElementById('totalWeightBadge');
    if (weightBadge) {
        weightBadge.innerHTML = '<i class="bi bi-speedometer2 me-1"></i>Stitch Wt: ' + totalWeight.toFixed(2) + ' kg';
    }

    const matchBadge = document.getElementById('weightMatchBadge');
    if (matchBadge) {
        if (currentCuttingUsableWeight > 0) {
            matchBadge.style.display = 'inline-block';
            const diff = Math.round((totalWeight - currentCuttingUsableWeight) * 100) / 100;
            if (Math.abs(diff) <= 0.01) {
                matchBadge.className = 'badge bg-success text-white border border-success px-2 py-1 fs-7';
                matchBadge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Weight Matched (${totalWeight.toFixed(2)} kg)`;
            } else if (diff > 0.01) {
                matchBadge.className = 'badge bg-danger text-white border border-danger px-2 py-1 fs-7';
                matchBadge.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>Weight Exceeded (+${diff.toFixed(2)} kg vs Cut ${currentCuttingUsableWeight.toFixed(2)} kg)`;
            } else {
                matchBadge.className = 'badge bg-warning text-dark border border-warning px-2 py-1 fs-7';
                matchBadge.innerHTML = `<i class="bi bi-info-circle-fill me-1"></i>Weight Short (${diff.toFixed(2)} kg vs Cut ${currentCuttingUsableWeight.toFixed(2)} kg)`;
            }
        } else {
            matchBadge.style.display = 'none';
        }
    }
}

function copyCutQtyToStitchingQty() {
    document.querySelectorAll('#stitchingEditForm tbody tr').forEach(tr => {
        const cutBadge = tr.querySelector('.badge.bg-primary-subtle');
        const stitchedInput = tr.querySelector('.stitching-qty-input');
        if (cutBadge && stitchedInput) {
            const cutVal = parseInt(cutBadge.textContent) || 0;
            stitchedInput.value = cutVal;
            onRowQtyOrUnitWeightChange(tr);
        }
    });
}

function toggleQuickPriceSetter() {
    const panel = document.getElementById('quickPricePanel');
    if (!panel) return;
    panel.style.display = (panel.style.display === 'none' || panel.style.display === '') ? 'block' : 'none';
}

function filterTableByCatalog(targetCatalog) {
    document.querySelectorAll('#catalogNavTabs .btn-catalog-filter').forEach(btn => {
        const target = btn.getAttribute('data-target-catalog');
        const badge = btn.querySelector('.badge');
        if (target === targetCatalog) {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary', 'active');
            if (badge) badge.className = 'badge bg-white text-primary ms-1';
        } else {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-outline-primary');
            if (badge) badge.className = 'badge bg-primary-subtle text-primary ms-1';
        }
    });

    let visibleCount = 0;
    document.querySelectorAll('#variantsTableBody tr.variant-item-row').forEach(row => {
        const rowCat = row.getAttribute('data-catalog') || '';
        if (targetCatalog === 'ALL' || rowCat === targetCatalog) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const info = document.getElementById('activeCatalogCountInfo');
    if (info) {
        info.innerHTML = targetCatalog === 'ALL' 
            ? `Showing all <strong>${visibleCount}</strong> items`
            : `Showing <strong>${visibleCount}</strong> items in <em>${targetCatalog}</em>`;
    }
}

function onCatalogPriceDropdownChange(targetCatalog) {
    const baseInp = document.getElementById('quickBulkBasePrice');
    const discInp = document.getElementById('quickBulkDiscPrice');
    if (!baseInp || !discInp) return;

    // Find the first matching row to read existing prices
    const rows = document.querySelectorAll('#variantsTableBody tr.variant-item-row');
    for (let tr of rows) {
        const rowCat = tr.getAttribute('data-catalog') || '';
        if (targetCatalog === 'ALL' || rowCat === targetCatalog) {
            const bVal = parseFloat(tr.querySelector('.stitching-base-price-input')?.value) || 0;
            const dVal = parseFloat(tr.querySelector('.stitching-discount-price-input')?.value) || 0;
            if (bVal > 0) baseInp.value = bVal.toFixed(2);
            if (dVal > 0) discInp.value = dVal.toFixed(2);
            break;
        }
    }
}

function validateStitchingPricePair(row) {
    if (!row) return true;
    const baseInput = row.querySelector('.stitching-base-price-input');
    const discountInput = row.querySelector('.stitching-discount-price-input');
    if (!baseInput || !discountInput) return true;

    const basePrice = parseFloat(baseInput.value);
    const discountPrice = parseFloat(discountInput.value);
    const pricesEntered = baseInput.value.trim() !== '' && discountInput.value.trim() !== ''
        && Number.isFinite(basePrice) && Number.isFinite(discountPrice);
    const isInvalid = pricesEntered && discountPrice >= basePrice;

    baseInput.classList.toggle('is-invalid', isInvalid);
    baseInput.classList.toggle('border-danger', isInvalid);
    discountInput.classList.toggle('is-invalid', isInvalid);
    discountInput.classList.toggle('border-danger', isInvalid);
    if (isInvalid) {
        discountInput.setCustomValidity('Discount Price must be less than Base Price.');
    } else {
        discountInput.setCustomValidity('');
    }

    return !isInvalid;
}

function applyQuickPricesFromBar() {
    const catSelect = document.getElementById('quickPriceCatalogSelect');
    const targetCatalog = catSelect ? catSelect.value : 'ALL';
    const baseInp = document.getElementById('quickBulkBasePrice');
    const discInp = document.getElementById('quickBulkDiscPrice');

    let baseVal = parseFloat(baseInp?.value) || 0;
    let discVal = parseFloat(discInp?.value) || 0;

    if (baseVal <= 0 || discVal <= 0) {
        alert('Enter both prices. Discount Price must be less than Base Price.');
        if (baseInp) baseInp.focus();
        return;
    }

    if (discVal >= baseVal) {
        alert('Discount Price must be less than Base Price.');
        if (discInp) discInp.focus();
        return;
    }

    let appliedCount = 0;
    document.querySelectorAll('#variantsTableBody tr.variant-item-row').forEach(tr => {
        const rowCat = tr.getAttribute('data-catalog') || '';
        if (targetCatalog === 'ALL' || rowCat === targetCatalog) {
            const bInp = tr.querySelector('.stitching-base-price-input');
            const dInp = tr.querySelector('.stitching-discount-price-input');
            if (bInp) {
                bInp.value = baseVal.toFixed(2);
                bInp.classList.remove('is-invalid', 'border-danger');
                bInp.classList.add('bg-success-subtle');
                setTimeout(() => bInp.classList.remove('bg-success-subtle'), 1200);
            }
            if (dInp) {
                dInp.value = discVal.toFixed(2);
                dInp.classList.remove('is-invalid', 'border-danger');
                dInp.classList.add('bg-success-subtle');
                setTimeout(() => dInp.classList.remove('bg-success-subtle'), 1200);
            }
            validateStitchingPricePair(tr);
            appliedCount++;
        }
    });

    const statusMsg = document.getElementById('quickPriceStatusMsg');
    if (statusMsg) {
        const catLabel = targetCatalog === 'ALL' ? 'all variants' : `"${targetCatalog}"`;
        statusMsg.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Loaded ₹${baseVal.toFixed(2)} / ₹${discVal.toFixed(2)} to ${appliedCount} items (${catLabel})!`;
        statusMsg.style.display = 'inline-block';
        setTimeout(() => { statusMsg.style.display = 'none'; }, 4000);
    }
}

function applyQuickPriceToCatalog(catalogName, baseInputId, discInputId) {
    const baseVal = parseFloat(document.getElementById(baseInputId)?.value) || 0;
    const discVal = parseFloat(document.getElementById(discInputId)?.value) || 0;

    if (baseVal <= 0 || discVal <= 0) {
        alert('Enter both prices. Discount Price must be less than Base Price.');
        return;
    }
    if (discVal >= baseVal) {
        alert('Discount Price must be less than Base Price.');
        return;
    }

    let appliedCount = 0;
    document.querySelectorAll('#variantsTableBody tr.variant-item-row').forEach(tr => {
        const rowCat = tr.getAttribute('data-catalog') || '';
        if (rowCat === catalogName || catalogName === 'ALL') {
            const baseInp = tr.querySelector('.stitching-base-price-input');
            const discInp = tr.querySelector('.stitching-discount-price-input');
            if (baseInp && baseVal > 0) {
                baseInp.value = baseVal.toFixed(2);
                baseInp.classList.remove('is-invalid', 'border-danger');
            }
            if (discInp && discVal > 0) {
                discInp.value = discVal.toFixed(2);
                discInp.classList.remove('is-invalid', 'border-danger');
            }
            validateStitchingPricePair(tr);
            appliedCount++;
        }
    });

    alert(`Successfully applied pricing to ${appliedCount} items in "${catalogName}"!`);
}

document.addEventListener('DOMContentLoaded', () => {
    // Pre-populate quick price bar with existing prices if available
    const catSelect = document.getElementById('quickPriceCatalogSelect');
    if (catSelect) {
        onCatalogPriceDropdownChange(catSelect.value);
    }
    // Form submission validation
    const form = document.getElementById('stitchingEditForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const nameInput = document.getElementById('stitchingName');
            if (!nameInput || !nameInput.value.trim()) {
                e.preventDefault();
                alert('Stitching Name is required. Please enter a valid batch name.');
                nameInput?.focus();
                return false;
            }

            const rows = document.querySelectorAll('#variantsTableBody tr.variant-item-row');
            if (rows.length === 0) {
                e.preventDefault();
                alert('No stitching variant items found.');
                return false;
            }

            for (let i = 0; i < rows.length; i++) {
                const tr = rows[i];
                const sizeBadge = tr.querySelector('.badge.bg-secondary-subtle')?.textContent || 'Variant';
                const colorBadge = tr.querySelector('.badge.bg-dark-subtle')?.textContent || '';
                const catBadge = tr.querySelector('.badge.bg-primary-subtle')?.textContent || '';
                const variantLabel = `[${catBadge}] ${sizeBadge} / ${colorBadge}`.trim();

                const qtyInp = tr.querySelector('.stitching-qty-input');
                const unitWeightInp = tr.querySelector('.stitching-unit-weight-input');
                const totalWeightInp = tr.querySelector('.stitching-weight-input');
                const basePriceInp = tr.querySelector('.stitching-base-price-input');
                const discountPriceInp = tr.querySelector('.stitching-discount-price-input');

                const missingFields = [];

                const qVal = parseFloat(qtyInp?.value);
                if (!qtyInp || qtyInp.value.trim() === '' || isNaN(qVal) || qVal <= 0) {
                    missingFields.push('Stitching Qty (pcs)');
                    qtyInp?.classList.add('is-invalid', 'border-danger');
                } else {
                    qtyInp?.classList.remove('is-invalid', 'border-danger');
                }

                const uwVal = parseFloat(unitWeightInp?.value);
                if (!unitWeightInp || unitWeightInp.value.trim() === '' || isNaN(uwVal) || uwVal <= 0) {
                    missingFields.push('Per Pc Weight (kg)');
                    unitWeightInp?.classList.add('is-invalid', 'border-danger');
                } else {
                    unitWeightInp?.classList.remove('is-invalid', 'border-danger');
                }

                const twVal = parseFloat(totalWeightInp?.value);
                if (!totalWeightInp || totalWeightInp.value.trim() === '' || isNaN(twVal) || twVal <= 0) {
                    missingFields.push('Total Weight (kg)');
                    totalWeightInp?.classList.add('is-invalid', 'border-danger');
                } else {
                    totalWeightInp?.classList.remove('is-invalid', 'border-danger');
                }

                const bpVal = parseFloat(basePriceInp?.value);
                if (!basePriceInp || basePriceInp.value.trim() === '' || isNaN(bpVal) || bpVal <= 0) {
                    missingFields.push('Base Price (₹)');
                    basePriceInp?.classList.add('is-invalid', 'border-danger');
                } else {
                    basePriceInp?.classList.remove('is-invalid', 'border-danger');
                }

                const dpVal = parseFloat(discountPriceInp?.value);
                if (!discountPriceInp || discountPriceInp.value.trim() === '' || isNaN(dpVal) || dpVal <= 0) {
                    missingFields.push('Discount Price (₹)');
                    discountPriceInp?.classList.add('is-invalid', 'border-danger');
                } else {
                    discountPriceInp?.classList.remove('is-invalid', 'border-danger');
                }

                if (bpVal > 0 && dpVal > 0 && dpVal >= bpVal) {
                    e.preventDefault();
                    basePriceInp?.classList.add('is-invalid', 'border-danger');
                    discountPriceInp?.classList.add('is-invalid', 'border-danger');
                    alert(`Row #${i + 1} (${variantLabel}): Discount Price must be less than Base Price.`);
                    discountPriceInp?.focus();
                    return false;
                }

                if (missingFields.length > 0) {
                    e.preventDefault();
                    tr.style.display = '';
                    alert(`Row #${i + 1} (${variantLabel}) has missing or zero required fields:\n• ${missingFields.join('\n• ')}\n\nPlease fill all required fields with valid values before saving.`);
                    if (qtyInp && qtyInp.classList.contains('is-invalid')) qtyInp.focus();
                    else if (unitWeightInp && unitWeightInp.classList.contains('is-invalid')) unitWeightInp.focus();
                    else if (totalWeightInp && totalWeightInp.classList.contains('is-invalid')) totalWeightInp.focus();
                    else if (basePriceInp && basePriceInp.classList.contains('is-invalid')) basePriceInp.focus();
                    else if (discountPriceInp && discountPriceInp.classList.contains('is-invalid')) discountPriceInp.focus();
                    return false;
                }
            }

            // Weight matching validation against Cutting Usable Weight
            if (currentCuttingUsableWeight > 0) {
                let sumStitchWeight = 0;
                document.querySelectorAll('.stitching-weight-input').forEach(inp => {
                    sumStitchWeight += parseFloat(inp.value) || 0;
                });
                sumStitchWeight = Math.round(sumStitchWeight * 100) / 100;
                const maxAllowedWeight = Math.round((currentCuttingUsableWeight + 0.01) * 100) / 100;

                if (sumStitchWeight > maxAllowedWeight) {
                    e.preventDefault();
                    alert(`Validation Error: Total Stitching Weight (${sumStitchWeight.toFixed(2)} kg) exceeds the Source Cutting Batch Usable Weight (${currentCuttingUsableWeight.toFixed(2)} kg).\n\nThe stitching weight must match the cutting weight (${currentCuttingUsableWeight.toFixed(2)} kg). Please adjust the Per Piece Weight or Total Weight before saving.`);
                    const firstWeightInp = document.querySelector('.stitching-weight-input');
                    firstWeightInp?.focus();
                    return false;
                }
            }
        });
    }
});
</script>
