<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-6 font-monospace">
                <code><?= e($product['code']) ?></code>
            </span>
            <?php if (($product['source_type'] ?? '') === 'PURCHASED' || ($product['source_type'] ?? '') === 'EXTERNAL_PURCHASE'): ?>
                <span class="badge bg-warning text-dark border border-warning">
                    <i class="bi bi-bag-check me-1"></i>Third-Party Vendor Product
                </span>
            <?php else: ?>
                <span class="badge bg-success text-white">
                    <i class="bi bi-gear-fill me-1"></i>In-House Production Style
                </span>
            <?php endif; ?>
            <?php if (!empty($product['is_complementary'])): ?>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                    <i class="bi bi-gift-fill me-1"></i>Complementary Product
                </span>
            <?php endif; ?>
        </div>
        <h2 class="mb-1 fw-bold"><?= e($product['name']) ?></h2>
        <div class="text-muted small d-flex align-items-center gap-2 flex-wrap">
            <span><i class="bi bi-folder2 me-1"></i><?= e($product['category_name'] ?? 'General') ?></span>
            <?php if (!empty($product['subcategory_name'])): ?>
                <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
                <span><?= e($product['subcategory_name']) ?></span>
            <?php endif; ?>
            <?php if (!empty($product['sub_subcategory_name'])): ?>
                <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
                <span><?= e($product['sub_subcategory_name']) ?></span>
            <?php endif; ?>
            <?php if (!empty($product['material'])): ?>
                &bull; <span class="badge bg-light text-dark border"><?= e($product['material']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (hasPermission('wh_catalog', 'edit')): ?>
        <a href="<?= url('warehouse/catalog/edit/' . $product['id']) ?>" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit Style
        </a>
        <?php endif; ?>
        <?php if (hasPermission('wh_catalog', 'delete')): ?>
        <form method="POST" action="<?= url('warehouse/catalog/delete/' . $product['id']) ?>" class="d-inline mb-0" onsubmit="return confirm('Are you sure you want to delete this product style from the warehouse catalog?');">
            <?= CSRF::field() ?>
            <button type="submit" class="btn btn-outline-danger">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </form>
        <?php endif; ?>
        <a href="<?= url('warehouse/catalog') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Catalog
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Image & Core Info -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body text-center p-3">
                <?php if (!empty($product['image_url'])): ?>
                    <img src="<?= e($product['image_url']) ?>" class="img-fluid rounded border shadow-sm w-100" style="max-height: 320px; object-fit: contain;" alt="<?= e($product['name']) ?>">
                <?php else: ?>
                    <div class="py-5 bg-light rounded text-muted border">
                        <i class="bi bi-image fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <span>No Style Photo Uploaded</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
            $costs = [];
            $sellings = [];
            $mrps = [];
            $totalWhStock = 0;
            $totalShopStock = 0;
            foreach ($variants as $v) {
                $cost = (float)($v['purchase_price'] > 0 ? $v['purchase_price'] : ($v['production_cost'] ?? 0));
                if ($cost > 0) $costs[] = $cost;
                $sell = (float)($v['selling_price'] ?? 0);
                if ($sell > 0) $sellings[] = $sell;
                $mrp = (float)($v['mrp'] ?? 0);
                if ($mrp > 0) $mrps[] = $mrp;
                $totalWhStock += (int)($v['warehouse_stock'] ?? 0);
                $totalShopStock += (int)($v['shop_stock'] ?? 0);
            }
            $minCost = !empty($costs) ? min($costs) : 0;
            $maxCost = !empty($costs) ? max($costs) : 0;
            $minSell = !empty($sellings) ? min($sellings) : 0;
            $maxSell = !empty($sellings) ? max($sellings) : 0;
            $minMrp = !empty($mrps) ? min($mrps) : 0;
            $maxMrp = !empty($mrps) ? max($mrps) : 0;
        ?>

        <!-- Pricing & Stock Summary Card -->
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-currency-rupee me-1 text-success"></i>Pricing & Stock Summary</h6>
                <span class="badge bg-success-subtle text-success border border-success-subtle"><?= number_format($totalWhStock) ?> pcs in Stock</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small"><?= !empty($product['is_complementary']) ? 'Selling Price (₹):' : 'MRP (Product Price) (₹):' ?></span>
                        <span class="fw-bold text-success font-monospace">
                            <?= $minMrp > 0 ? (($minMrp === $maxMrp) ? '₹' . number_format($minMrp, 2) : '₹' . number_format($minMrp, 2) . ' – ₹' . number_format($maxMrp, 2)) : '—' ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Cost Price (₹):</span>
                        <span class="fw-semibold text-primary font-monospace">
                            <?= $minCost > 0 ? (($minCost === $maxCost) ? '₹' . number_format($minCost, 2) : '₹' . number_format($minCost, 2) . ' – ₹' . number_format($maxCost, 2)) : '—' ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Live Warehouse Stock:</span>
                        <span class="badge <?= $totalWhStock > 0 ? 'bg-success' : 'bg-danger' ?> px-2 py-1">
                            <?= number_format($totalWhStock) ?> pcs
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Live Shop Stock:</span>
                        <span class="badge bg-secondary px-2 py-1">
                            <?= number_format($totalShopStock) ?> pcs
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Style Specifications</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Style Code:</span>
                        <span class="fw-semibold font-monospace text-primary"><?= e($product['code']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Source Type:</span>
                        <span class="fw-semibold"><?= ($product['source_type'] ?? '') === 'PURCHASED' ? 'Third-Party Vendor' : 'In-House Production' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Brand / Label:</span>
                        <span class="fw-semibold"><?= e($product['brand_name'] ?? 'In-House') ?></span>
                    </li>
                    <?php if (!empty($product['seller_name'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Seller / Supplier:</span>
                        <span class="fw-semibold text-dark">
                            <i class="bi bi-shop me-1 text-primary"></i><?= e($product['seller_name']) ?><?= !empty($product['seller_company']) ? ' (' . e($product['seller_company']) . ')' : '' ?>
                        </span>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Main Category:</span>
                        <span class="fw-semibold"><?= e($product['category_name'] ?? 'General') ?></span>
                    </li>
                    <?php if (!empty($product['subcategory_name'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Sub Category:</span>
                        <span class="fw-semibold"><?= e($product['subcategory_name']) ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($product['sub_subcategory_name'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Sub Sub Category:</span>
                        <span class="fw-semibold"><?= e($product['sub_subcategory_name']) ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($product['qr_code_prefix'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">QR Code Prefix:</span>
                        <span class="fw-bold text-primary font-monospace"><?= e($product['qr_code_prefix']) ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($product['catalog_code'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Catalog Code:</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fw-bold px-2 py-1"><?= e($product['catalog_code']) ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($product['material'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Material / Fabric:</span>
                        <span class="fw-semibold"><?= e($product['material']) ?></span>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Quantity:</span>
                        <span class="fw-bold text-dark"><i class="bi bi-boxes me-1 text-primary"></i><?= number_format((int)($product['quantity'] ?? 0)) ?> Pcs</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Weight:</span>
                        <span class="fw-bold text-dark"><i class="bi bi-speedometer me-1 text-primary"></i><?= number_format((float)($product['weight'] ?? 0), 3) ?> Kg</span>
                    </li>
                    <?php if (!empty($product['mfg_date'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Manufacture Date:</span>
                        <span class="fw-semibold text-dark"><i class="bi bi-calendar-check me-1 text-success"></i><?= date('d M Y', strtotime($product['mfg_date'])) ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($product['expiry_date'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Expiry Date:</span>
                        <span class="fw-semibold text-danger"><i class="bi bi-calendar-x me-1 text-danger"></i><?= date('d M Y', strtotime($product['expiry_date'])) ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($product['hsn_code'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">HSN Code:</span>
                        <span class="fw-semibold"><?= e($product['hsn_code']) ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <?php if (!empty($product['description'])): ?>
            <div class="card shadow-sm border-0 mb-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-card-text me-2 text-primary"></i>Description & Notes</h6>
                </div>
                <div class="card-body text-muted small">
                    <?= nl2br(e($product['description'])) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Variants Breakdown & Recent Purchases -->
    <div class="col-lg-8">
        <!-- Variant Items Card -->
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-boxes me-2 text-primary"></i> Garment Variants & Live Warehouse Stock (<?= count($variants) ?> Items)
                </h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Active Variants</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Size</th>
                                <th>Color</th>
                                <th>SKU & QR Mode</th>
                                <th class="text-end"><?= !empty($product['is_complementary']) ? 'Selling Price (₹)' : 'MRP (₹)' ?></th>
                                <th class="text-end">Cost Price (₹)</th>
                                <th class="text-end">Warehouse Stock</th>
                                <th class="text-center">QR Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($variants)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No variants configured for this style.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($variants as $v): 
                                    $isSeries = ($v['qr_type'] ?? '') === 'SERIES_BATCH';
                                    $pQty = (int)($v['pack_quantity'] ?? 1);
                                    $sStart = $v['series_start_code'] ?? '';
                                    $sEnd = $v['series_end_code'] ?? '';
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-dark-subtle text-dark border px-2 py-1">
                                                <?= e($v['size_code'] ?? ($v['size_name'] ?? 'Free Size')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($v['color_hex'])): ?>
                                                    <span style="display:inline-block; width:14px; height:14px; border-radius:50%; background-color:<?= e($v['color_hex']) ?>; border:1px solid #ccc;"></span>
                                                <?php endif; ?>
                                                <span class="fw-semibold small"><?= e($v['color_name'] ?? 'Standard') ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="text-primary fw-bold"><?= e($v['sku']) ?></code>
                                            <?php if (!empty($v['hsn_code'])): ?>
                                                <span class="badge bg-secondary-subtle text-secondary border font-monospace ms-1" style="font-size: 0.7rem;">HSN: <?= e($v['hsn_code']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($isSeries): ?>
                                                <div class="mt-1">
                                                    <span class="badge bg-warning text-dark border border-warning-subtle" style="font-size: 0.72rem;">
                                                        <i class="bi bi-layers-half me-1"></i>Series Pack (<?= $pQty ?> Pcs)
                                                    </span>
                                                    <div class="small font-monospace text-secondary mt-1">
                                                        <i class="bi bi-arrow-left-right me-1"></i><?= e($sStart ?: '—') ?> ➔ <?= e($sEnd ?: '—') ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="mt-1">
                                                    <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">
                                                        <i class="bi bi-tag me-1"></i>Individual Piece QR
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end text-success fw-bold font-monospace">
                                            <?= formatCurrency((float)$v['mrp']) ?>
                                        </td>
                                        <td class="text-end fw-semibold text-primary font-monospace">
                                            <?= formatCurrency((float)($v['purchase_price'] > 0 ? $v['purchase_price'] : $v['production_cost'])) ?>
                                        </td>
                                        <td class="text-end fw-bold <?= (int)$v['warehouse_stock'] > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format((int)$v['warehouse_stock']) ?> pcs
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isSeries && hasPermission('wh_labels', 'print')): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-semibold" 
                                                        onclick='openMasterQRModal(<?= json_encode([
                                                            "sku" => $v["sku"],
                                                            "product_name" => $product["name"],
                                                            "size" => $v["size_code"] ?? ($v["size_name"] ?? "Free"),
                                                            "color" => $v["color_name"] ?? "Std",
                                                            "pack_qty" => $pQty,
                                                            "start_code" => $sStart,
                                                            "end_code" => $sEnd,
                                                            "mrp" => (float)$v["mrp"],
                                                            "category" => $product["category_name"] ?? "Apparel"
                                                        ]) ?>)'>
                                                    <i class="bi bi-qr-code me-1"></i> Master QR
                                                </button>
                                            <?php else: ?>
                                                <?php if (hasPermission('wh_labels', 'print')): ?>
                                                    <a href="<?= url('warehouse/labels?tab=stock&variant_id=' . $v['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-printer me-1"></i> Print Labels
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Third-Party Purchases Card -->
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-cart-check me-2 text-warning"></i> Recent Third-Party Vendor Purchases
                </h5>
                <?php if (hasPermission('purchases', 'create')): ?>
                    <a href="<?= url('warehouse/sellers/buy') ?>" class="btn btn-sm btn-warning text-dark fw-semibold">
                        <i class="bi bi-plus-circle me-1"></i> New Purchase Order
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>PO Number</th>
                                <th>Seller / Vendor</th>
                                <th>Order Date</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPurchases)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No purchase orders recorded for this product style yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentPurchases as $po): ?>
                                    <tr>
                                        <td><code><?= e($po['po_number']) ?></code></td>
                                        <td class="fw-semibold">
                                            <?= e($po['seller_name'] ?? '—') ?>
                                            <?php if (!empty($po['seller_company'])): ?>
                                                <small class="text-muted d-block"><?= e($po['seller_company']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= formatDate($po['order_date']) ?></td>
                                        <td class="text-end fw-semibold"><?= (int)($po['quantity'] ?? 0) ?> pcs</td>
                                        <td class="text-end text-primary fw-semibold"><?= formatCurrency((float)($po['unit_price'] ?? 0)) ?></td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><?= e($po['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Master Series Pack QR Code Preview Modal -->
<div class="modal fade" id="modalPreviewMasterSeriesQR" tabindex="-1" aria-labelledby="modalPreviewMasterSeriesQRLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-qr-code-scan fs-5 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalPreviewMasterSeriesQRLabel">Master Series Pack QR Label</h6>
                        <small class="text-white-50" style="font-size: 0.75rem;">1 QR Code for the Entire Pack / Bundle</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Visual Physical Master Label Card -->
                <div id="masterSeriesLabelPrintArea" class="card border border-2 border-dark shadow-sm bg-white mx-auto text-center p-3" style="max-width: 340px; border-radius: 12px;">
                    <div class="border-bottom pb-2 mb-2">
                        <div class="fw-bold text-uppercase text-dark" style="font-size: 0.82rem; letter-spacing: 0.5px;">GRM INVENTORY SYSTEM</div>
                        <div class="badge bg-warning text-dark border border-warning px-2 py-1 mt-1 font-monospace" style="font-size: 0.75rem;">
                            <i class="bi bi-layers-half me-1"></i>MASTER SERIES PACK
                        </div>
                    </div>
                    
                    <div class="fw-bold text-primary fs-6" id="qrModalProductName">—</div>
                    <div class="text-muted small mb-2" id="qrModalVariantInfo">—</div>

                    <!-- Centered QR Canvas Box -->
                    <div class="d-flex justify-content-center my-2 p-2 bg-white rounded border border-light">
                        <div id="qrModalCanvasContainer" style="width: 170px; height: 170px;"></div>
                    </div>

                    <!-- Series Range & Quantity Badges -->
                    <div class="bg-light p-2 rounded border my-2 text-start small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Pack Quantity:</span>
                            <span class="fw-bold text-dark font-monospace" id="qrModalPackQty">0 Pcs</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Start Code:</span>
                            <span class="fw-bold font-monospace text-primary" id="qrModalStartCode">—</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">End Code:</span>
                            <span class="fw-bold font-monospace text-primary" id="qrModalEndCode">—</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Max Retail (MRP):</span>
                            <span class="fw-bold text-success font-monospace" id="qrModalMRP">₹0.00</span>
                        </div>
                    </div>

                    <div class="text-muted" style="font-size: 0.65rem;">
                        Scan this single Master QR at Warehouse/Shop inward to credit all <span id="qrModalFootQty">0</span> pcs automatically.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top py-2 px-3 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <div class="d-flex gap-2">
                    <?php if (hasPermission('wh_labels', 'print')): ?>
                        <button type="button" class="btn btn-warning text-dark btn-sm fw-semibold" onclick="printMasterSeriesLabel()">
                            <i class="bi bi-printer-fill me-1"></i> Print Master QR Sticker
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
let activeMasterQR = null;

function openMasterQRModal(data) {
    document.getElementById('qrModalProductName').textContent = data.product_name || 'Product Style';
    document.getElementById('qrModalVariantInfo').textContent = (data.category ? data.category + ' | ' : '') + 'Size: ' + data.size + ' | Col: ' + data.color + ' (' + data.sku + ')';
    document.getElementById('qrModalPackQty').textContent = data.pack_qty + ' Pcs';
    document.getElementById('qrModalFootQty').textContent = data.pack_qty;
    document.getElementById('qrModalStartCode').textContent = data.start_code || '—';
    document.getElementById('qrModalEndCode').textContent = data.end_code || '—';
    document.getElementById('qrModalMRP').textContent = '₹' + parseFloat(data.mrp || 0).toFixed(2);

    const qrContainer = document.getElementById('qrModalCanvasContainer');
    qrContainer.innerHTML = '';

    // Master Series QR Payload formatted as structured JSON for universal scanners
    const qrPayload = JSON.stringify({
        type: "SERIES_BATCH",
        sku: data.sku,
        qty: parseInt(data.pack_qty || 1),
        start: data.start_code || '',
        end: data.end_code || '',
        mrp: parseFloat(data.mrp || 0),
        prod: data.product_name || ''
    });

    activeMasterQR = new QRCode(qrContainer, {
        text: qrPayload,
        width: 170,
        height: 170,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });

    const modal = new bootstrap.Modal(document.getElementById('modalPreviewMasterSeriesQR'));
    modal.show();
}

function printMasterSeriesLabel() {
    const printContent = document.getElementById('masterSeriesLabelPrintArea').innerHTML;
    const printWindow = window.open('', '_blank', 'width=450,height=600');
    printWindow.document.open();
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Master Series Pack Label</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                @page { size: 80mm 100mm; margin: 0; }
                body { margin: 10px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
                .card { border: 2px solid #000 !important; border-radius: 8px; }
            </style>
        </head>
        <body onload="window.print(); setTimeout(function(){ window.close(); }, 500);">
            <div class="card p-3 text-center">
                \${printContent}
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
}
</script>
