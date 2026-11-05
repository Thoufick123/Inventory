<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h2 class="mb-1"><i class="bi bi-shop-window me-2 text-primary"></i><?= e($seller['name']) ?></h2>
            <?php if ($seller['is_active']): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active Supplier</span>
            <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inactive</span>
            <?php endif; ?>
        </div>
        <span class="text-muted">
            <?= !empty($seller['company']) ? e($seller['company']) . ' &bull; ' : '' ?>
            <!-- Third-Party Vendor Profile, Catalog &amp; Inward Management Hub -->
        </span>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= url('warehouse/sellers') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Suppliers
        </a>
    </div>
</div>

<!-- ========================================================================= -->
<!-- DYNAMIC ADD PRODUCTS PANEL (Brand → Variant Loader) -->
<!-- ========================================================================= -->
<div id="addProductsPanel" style="display:none;" class="mb-4">
    <div class="card border-0 shadow rounded-3 overflow-hidden" style="background: linear-gradient(135deg, #f0f4ff 0%, #fff 100%);">
        <div class="card-header bg-primary d-flex align-items-center justify-content-between py-3 px-4">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-grid-3x3-gap-fill fs-5 text-white"></i>
                <h5 class="mb-0 fw-bold text-white">Add Products to Catalog — <?= e($seller['company'] ?: $seller['name']) ?></h5>
            </div>
            <button type="button" class="btn btn-sm btn-outline-light fw-semibold" onclick="closeAddProductsPanel()">
                <i class="bi bi-x-lg me-1"></i> Close
            </button>
        </div>
        <div class="card-body p-4">
            <!-- Step 1: Brand Selection Instruction -->
            <div class="mb-3">
                <div class="fw-bold text-dark mb-2"><i class="bi bi-1-circle-fill text-primary me-1"></i> Select a Brand / Label to view its product variants:</div>
                <div id="brandPillsContainer" class="d-flex gap-2 flex-wrap">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="text-muted small">Loading brands...</span>
                </div>
            </div>
            <!-- Step 2: Product + Variant Cards (loaded dynamically) -->
            <div id="brandVariantArea" style="display:none;" class="mt-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-2-circle-fill text-success me-1"></i>
                        Products &amp; Variants for Brand: <span id="activeBrandLabel" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 ms-1"></span>
                    </h6>
                    <a href="<?= url('warehouse/catalog/create?seller_id=' . $seller['id']) ?>" class="btn btn-sm btn-primary fw-semibold shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> Add New Product Style
                    </a>
                </div>
                <div id="variantCardsGrid" class="row g-3"></div>
                <div id="noVariantsMsg" style="display:none;" class="text-center py-4 text-muted">
                    <i class="bi bi-journal-plus fs-1 d-block mb-2"></i>
                    <p>No catalog products found for this brand/supplier yet.</p>
                    <a href="<?= url('warehouse/catalog/create?seller_id=' . $seller['id']) ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Create First Product Style
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- SELLER FINANCIAL & PROCUREMENT METRIC CARDS -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-primary border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Total Purchase Bills</span>
                    <h4 class="mb-0 fw-bold mt-1 text-dark"><?= number_format($stats['total_orders'] ?? 0) ?></h4>
                    <small class="text-muted">Purchase logs recorded</small>
                </div>
                <div class="rounded-circle bg-primary-subtle text-primary p-3">
                    <i class="bi bi-receipt fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Total Procurement Spend</span>
                    <h4 class="mb-0 fw-bold mt-1 text-success">₹<?= number_format($stats['total_spend'] ?? 0, 2) ?></h4>
                    <small class="text-muted">Total buying valuation</small>
                </div>
                <div class="rounded-circle bg-success-subtle text-success p-3">
                    <i class="bi bi-currency-rupee fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Total Units Purchased</span>
                    <h4 class="mb-0 fw-bold mt-1 text-info"><?= number_format($stats['total_units'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h4>
                    <small class="text-muted"><?= number_format($stats['unique_products'] ?? 0) ?> unique styles in catalog</small>
                </div>
                <div class="rounded-circle bg-info-subtle text-info p-3">
                    <i class="bi bi-box-seam fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Settlement Status</span>
                    <h5 class="mb-0 fw-bold mt-1 text-dark">
                        <span class="text-success">₹<?= number_format($stats['paid_spend'] ?? 0) ?></span>
                        <small class="text-danger fs-7">/ ₹<?= number_format($stats['pending_dues'] ?? 0) ?> due</small>
                    </h5>
                    <small class="text-muted">Paid vs outstanding</small>
                </div>
                <div class="rounded-circle bg-warning-subtle text-warning p-3">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- ========================================================================= -->
    <!-- LEFT: SUPPLIER PROFILE & COMMERCIAL DETAILS & BILL ATTACHMENTS -->
    <!-- ========================================================================= -->
    <div class="col-lg-4">
        <!-- Supplier Profile Details -->
        <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Supplier &amp; Commercial Terms</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <span class="text-muted small d-block">COMPANY / BRAND</span>
                    <div class="fw-bold text-dark fs-6"><?= e($seller['company'] ?: $seller['name']) ?></div>
                    <span class="badge bg-light text-secondary border font-monospace mt-1">
                        <?= e($seller['seller_type'] === 'MATERIAL_SUPPLIER' ? 'Fabric Material Supplier' : 'Finished Product Seller') ?>
                    </span>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <span class="text-muted small d-block">CONTACT PERSON &amp; MOBILE</span>
                    <div class="fw-bold text-dark"><i class="bi bi-person me-1 text-muted"></i><?= e($seller['name']) ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-telephone me-1"></i><?= e($seller['phone']) ?></div>
                    <?php if (!empty($seller['email'])): ?>
                        <div class="text-muted small"><i class="bi bi-envelope me-1"></i><?= e($seller['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <span class="text-muted small d-block">GST &amp; TAX COMPLIANCE</span>
                    <?php if (!empty($seller['gst_number'])): ?>
                        <div class="font-monospace fw-bold text-dark fs-7 mt-1"><?= e($seller['gst_number']) ?></div>
                    <?php else: ?>
                        <div class="text-muted small mt-1">Unregistered Person (URP)</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <span class="text-muted small d-block">LOCATION &amp; ADDRESS</span>
                    <div class="text-dark small mt-1"><?= e($seller['address'] ?: '—') ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-geo-alt me-1"></i><?= e(implode(', ', array_filter([$seller['city'] ?? '', $seller['state'] ?? '']))) ?: 'Tirupur' ?></div>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <span class="text-muted small d-block">COMMERCIAL &amp; PAYMENT TERMS</span>
                    <div class="text-dark small mt-1"><?= e($seller['payment_terms'] ?: 'Standard Commercial Terms') ?></div>
                    <?php if (!empty($seller['material_type'])): ?>
                        <div class="text-muted small mt-1"><strong>Specialty:</strong> <?= e($seller['material_type']) ?></div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($seller['notes'])): ?>
                    <div>
                        <span class="text-muted small d-block">SUPPLIER REMARKS / NOTES</span>
                        <div class="text-secondary small mt-1"><?= nl2br(e($seller['notes'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Master Supplier Bill / Document Attachment Card -->
        <?php if (!empty($seller['document_url'])): ?>
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-file-earmark-image me-2 text-primary"></i>Supplier Registration Document</h6>
                    <span class="badge bg-light text-muted border">Attached</span>
                </div>
                <div class="card-body text-center p-3">
                    <?php 
                        $docUrl = $seller['document_url'];
                        $isImg = preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $docUrl) || str_contains($docUrl, 'res.cloudinary.com');
                    ?>
                    <?php if ($isImg): ?>
                        <img src="<?= e($docUrl) ?>" alt="Supplier Document" class="img-fluid rounded border shadow-sm mb-2" style="max-height: 160px; object-fit: contain; cursor: pointer;" onclick="openImageModal('<?= e($docUrl) ?>', 'Supplier Document - <?= e($seller['name']) ?>')">
                    <?php else: ?>
                        <div class="p-3 bg-light rounded border mb-2">
                            <i class="bi bi-file-earmark-pdf fs-1 text-danger"></i>
                            <div class="small fw-semibold mt-1">PDF / Inward Document</div>
                        </div>
                    <?php endif; ?>
                    <div>
                        <a href="<?= e($docUrl) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Open Full Document
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- RIGHT: PRODUCT BUY LIST & BILLS INWARD HISTORY (TABS) -->
    <!-- ========================================================================= -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header bg-white py-3 border-bottom">
                <ul class="nav nav-tabs card-header-tabs" id="supplierTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" id="products-tab" data-bs-toggle="tab" data-bs-target="#productsTabPane" type="button" role="tab" aria-controls="productsTabPane" aria-selected="true">
                            <i class="bi bi-boxes me-2 text-primary"></i>Products in Catalog (<?= count($purchasedProducts) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="bills-tab" data-bs-toggle="tab" data-bs-target="#billsTabPane" type="button" role="tab" aria-controls="billsTabPane" aria-selected="false">
                            <i class="bi bi-receipt-cutoff me-2 text-success"></i>Purchase Bills &amp; Inward Receipts (<?= count($orders) ?>)
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="tab-content" id="supplierTabContent">
                    <!-- ================================================================= -->
                    <!-- TAB 1: PRODUCTS BOUGHT LIST WITH QR CODES & STOCK STATUS -->
                    <!-- ================================================================= -->
                    <div class="tab-pane fade show active" id="productsTabPane" role="tabpanel" aria-labelledby="products-tab">
                        <?php if (empty($purchasedProducts)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-journal-plus fs-1 text-muted d-block mb-3"></i>
                                <h6 class="text-muted">No catalog products linked to this supplier yet.</h6>
                                <p class="text-muted small">Add products to the catalog for this supplier first. QR codes will be available once products are catalogued.</p>
                                <?php if (hasPermission('wh_sellers', 'create')): ?>
                                <a href="<?= url('warehouse/catalog/create?seller_id=' . $seller['id']) ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Add First Product to Catalog
                                </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <?php if (hasPermission('wh_sellers', 'create')): ?>
                                <a href="<?= url('warehouse/catalog/create?seller_id=' . $seller['id']) ?>" class="btn btn-xs btn-outline-primary shadow-sm">
                                    <i class="bi bi-plus-circle me-1"></i> Add More to Catalog
                                </a>
                                <?php endif; ?>
                            </div> -->

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product / Garment Style</th>
                                            <th>SKU &amp; Third-Party QR</th>
                                            <th>Size / Color</th>
                                            <th class="text-center">Total Bought</th>
                                            <th class="text-center">Warehouse Stock</th>
                                            <th class="text-end">Cost Rate (₹)</th>
                                            <th class="text-end">MRP / Selling</th>
                                            <!-- <th class="text-center">QR &amp; Actions</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($purchasedProducts as $prod): ?>
                                            <?php 
                                                $qrVal = !empty($prod['qr_code']) ? $prod['qr_code'] : $prod['sku'];
                                                $prodPayload = json_encode([
                                                    'name'    => $prod['product_name'],
                                                    'sku'     => $prod['sku'],
                                                    'size'    => $prod['size_name'] ?? 'Free',
                                                    'color'   => $prod['color_name'] ?? 'Std',
                                                    'mrp'     => (float)($prod['mrp'] ?? 0),
                                                    'price'   => (float)($prod['selling_price'] ?? $prod['mrp'] ?? 0),
                                                    'qr_code' => $qrVal,
                                                    'seller'  => $seller['name']
                                                ]);
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark d-flex align-items-center gap-1.5 flex-wrap">
                                                        <span><?= e($prod['product_name']) ?></span>
                                                        <?php if (!empty($prod['is_complementary']) || !empty($prod['variant_is_complementary'])): ?>
                                                            <span class="badge bg-warning text-dark border border-warning fw-bold px-2 py-0.5 shadow-xs" style="font-size: 0.70rem;" title="Complementary Product (Direct Warehouse Stock)">
                                                                <i class="bi bi-gift-fill me-1 text-danger"></i>COMPLEMENTARY
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted"><?= e($prod['category_name'] ?? 'Garments') ?> &bull; <?= e($prod['product_code']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="font-monospace fw-semibold text-primary"><?= e($prod['sku']) ?></span>
                                                    <div class="mt-1">
                                                        <span class="badge bg-dark-subtle text-dark border font-monospace fs-8 cursor-pointer" onclick='openSingleQrModal(<?= htmlspecialchars($prodPayload, ENT_QUOTES) ?>)' title="Click to view QR label">
                                                            <i class="bi bi-qr-code me-1 text-primary"></i><?= e($qrVal) ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                                        <?= e($prod['size_name'] ?? 'Free') ?> / <?= e($prod['color_name'] ?? 'Std') ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark border fs-7 fw-bold"><?= number_format($prod['total_bought_qty']) ?> pcs</span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if (($prod['current_stock_qty'] ?? 0) > 0): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold fs-7">
                                                            <i class="bi bi-check-circle me-1"></i><?= number_format($prod['current_stock_qty']) ?> pcs
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-8">
                                                            0 pcs (Pending Inward)
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold text-dark">₹<?= number_format($prod['last_unit_price'], 2) ?></span>
                                                    <?php if ($prod['avg_unit_price'] != $prod['last_unit_price']): ?>
                                                        <small class="text-muted d-block">avg ₹<?= number_format($prod['avg_unit_price'], 2) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="text-secondary small">MRP: ₹<?= number_format($prod['mrp'], 2) ?></div>
                                                    <div class="fw-bold text-success fs-7">Discount: ₹<?= number_format($prod['selling_price'] ?: $prod['mrp'], 2) ?></div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ================================================================= -->
                    <!-- TAB 2: INWARD BILLS & RECEIPTS HISTORY -->
                    <!-- ================================================================= -->
                    <div class="tab-pane fade" id="billsTabPane" role="tabpanel" aria-labelledby="bills-tab">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-receipt fs-1 text-muted d-block mb-3"></i>
                                <h6 class="text-muted">No purchase bills logged for this supplier yet.</h6>
                                <p class="text-muted small">First add products to the catalog, then purchase bills will appear here.</p>
                                <?php if (hasPermission('wh_sellers', 'create')): ?>
                                <a href="<?= url('warehouse/catalog/create?seller_id=' . $seller['id']) ?>" class="btn btn-sm btn-primary mt-2">
                                    <i class="bi bi-plus-circle me-1"></i> Add Products to Catalog First
                                </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>PO / Bill #</th>
                                            <th>Bill Screenshot</th>
                                            <th>Warehouse</th>
                                            <th>Date</th>
                                            <th class="text-center">Items / Units</th>
                                            <th class="text-center">Stock Inward Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $po): ?>
                                            <?php 
                                                $isPending = ($po['status'] ?? '') !== 'RECEIVED';
                                                $poJson = json_encode([
                                                    'id'           => $po['id'],
                                                    'po_number'    => $po['po_number'],
                                                    'warehouse_id' => $po['warehouse_id'],
                                                    'warehouse'    => $po['warehouse_name'],
                                                    'total_items'  => $po['total_items'],
                                                    'total_qty'    => $po['total_qty'],
                                                    'order_date'   => $po['order_date'],
                                                    'seller_name'  => $seller['name']
                                                ]);
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-bold font-monospace text-primary"><?= e($po['po_number']) ?></span>
                                                    <?php if (!empty($po['notes'])): ?>
                                                        <small class="text-muted d-block text-truncate" style="max-width: 140px;"><?= e($po['notes']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($po['payment_screenshot_url'])): ?>
                                                        <?php 
                                                            $billImg = $po['payment_screenshot_url'];
                                                            $isPdf = preg_match('/\.pdf(?:$|[?#])/i', $billImg);
                                                            $isImg = !$isPdf && (preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $billImg) || str_contains($billImg, 'res.cloudinary.com'));
                                                        ?>
                                                        <?php if ($isImg): ?>
                                                            <div class="position-relative d-inline-block cursor-pointer" onclick="openImageModal('<?= e($billImg) ?>', 'Bill Screenshot - PO #<?= e($po['po_number']) ?>')">
                                                                <img src="<?= e($billImg) ?>" alt="Bill Screenshot" class="rounded border shadow-sm" style="width: 44px; height: 44px; object-fit: cover;">
                                                                <span class="badge bg-dark bg-opacity-75 position-absolute bottom-0 end-0 p-1" style="font-size: 0.6rem;"><i class="bi bi-zoom-in text-white"></i></span>
                                                            </div>
                                                        <?php else: ?>
                                                            <a href="<?= e($billImg) ?>" target="_blank" class="btn btn-xs btn-outline-danger" title="Open PDF Bill">
                                                                <i class="bi bi-file-earmark-pdf"></i> Bill PDF
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted border">No Upload</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge bg-secondary"><?= e($po['warehouse_name']) ?></span></td>
                                                <td><small class="text-muted"><?= date('d M Y', strtotime($po['order_date'])) ?></small></td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark border fw-bold"><?= number_format($po['total_qty']) ?> pcs</span>
                                                    <small class="text-muted d-block"><?= number_format($po['total_items']) ?> SKU(s)</small>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($isPending): ?>
                                                        <span class="badge bg-warning text-dark px-2 py-1 shadow-sm border border-warning">
                                                            <i class="bi bi-clock-history me-1"></i>Pending Inward
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                            <i class="bi bi-check2-circle me-1"></i>Stock Inwarded
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-flex justify-content-end gap-1">
                                                        <?php if (!empty($po['payment_screenshot_url'])): ?>
                                                            <?php 
                                                                $billImg = $po['payment_screenshot_url'];
                                                                $isPdf = preg_match('/\.pdf(?:$|[?#])/i', $billImg);
                                                                $isImg = !$isPdf && (preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $billImg) || str_contains($billImg, 'res.cloudinary.com'));
                                                            ?>
                                                            <?php if ($isImg): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-primary shadow-xs" onclick="openImageModal('<?= e($billImg) ?>', 'Supplier Bill / Invoice - PO #<?= e($po['po_number']) ?>')" title="View Uploaded Supplier Bill / Invoice">
                                                                    <i class="bi bi-receipt me-1"></i> Invoice
                                                                </button>
                                                            <?php else: ?>
                                                                <a href="<?= e($billImg) ?>" target="_blank" class="btn btn-sm btn-outline-primary shadow-xs" title="Open Uploaded Bill PDF">
                                                                    <i class="bi bi-file-earmark-pdf me-1"></i> Invoice
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true" title="No supplier invoice was uploaded for this bill">
                                                                <i class="bi bi-receipt me-1"></i> No Invoice
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: SINGLE PRODUCT THIRD-PARTY QR LABEL VIEWER & PRINT -->
<!-- ========================================================================= -->
<div class="modal fade" id="singleQrModal" tabindex="-1" aria-labelledby="singleQrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6" id="singleQrModalLabel"><i class="bi bi-qr-code me-2 text-warning"></i>Product QR Label Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center bg-light">
                <!-- Printable QR Label Card Container -->
                <div class="card border-2 border-dark rounded-3 shadow-sm mx-auto bg-white p-3 text-start" id="printableQrCard" style="max-width: 320px;">
                    <div class="text-center border-bottom pb-2 mb-2">
                        <div class="fw-bold fs-6 text-dark tracking-wide">GRM APPARELS</div>
                        <div class="text-muted fs-8 font-monospace">THIRD-PARTY PROCUREMENT</div>
                    </div>

                    <div class="text-center mb-2">
                        <img id="modalQrImg" src="" alt="Product QR Code" class="img-fluid border p-1 rounded" style="width: 140px; height: 140px;">
                        <div id="modalQrCodeText" class="font-monospace fw-bold fs-7 mt-1 text-dark"></div>
                    </div>

                    <div class="border-top pt-2">
                        <div id="modalQrProductName" class="fw-bold text-dark fs-7 text-truncate mb-1"></div>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>SKU: <strong id="modalQrSku" class="text-dark font-monospace"></strong></span>
                            <span>Size: <strong id="modalQrSize" class="text-dark"></strong></span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Color: <strong id="modalQrColor" class="text-dark"></strong></span>
                            <span>Vendor: <strong id="modalQrSeller" class="text-dark"></strong></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                            <div>
                                <span class="text-muted fs-8">MRP: </span>
                                <del id="modalQrMrp" class="text-secondary small"></del>
                            </div>
                            <div>
                                <span class="text-muted fs-8">Price: </span>
                                <span id="modalQrPrice" class="fw-bold text-success fs-6"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white d-flex justify-content-between">
                <a id="modalQrSuiteLink" href="<?= url('warehouse/labels?source_type=seller&source_id=' . $seller['id']) ?>" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-grid me-1"></i> Open QR Printing Suite
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="printSingleQrLabel()">
                        <i class="bi bi-printer me-1"></i> Print Label
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: ADMIN CONFIRM STOCK INWARD -->
<!-- ========================================================================= -->
<div class="modal fade" id="inwardBillModal" tabindex="-1" aria-labelledby="inwardBillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fs-6" id="inwardBillModalLabel">
                    <i class="bi bi-box-arrow-in-down me-2"></i>Confirm Stock Inward to Warehouse
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="inwardBillForm" method="POST" action="">
                <?= CSRF::input() ?>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Inwarding will create the Goods Receipt Note (GRN) and immediately credit the purchased units into available warehouse inventory.
                    </div>

                    <div class="card bg-light border p-3 rounded-3 mb-3">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Purchase Bill #:</span>
                            <strong id="modalInwardPoNum" class="font-monospace text-primary"></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Supplier:</span>
                            <strong id="modalInwardSeller" class="text-dark"></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Total Products:</span>
                            <span id="modalInwardItems" class="badge bg-white text-dark border font-monospace"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Total Quantity:</span>
                            <strong id="modalInwardQty" class="text-success fs-6"></strong>
                        </div>
                        <div class="d-flex justify-content-between small border-top pt-1 mt-1">
                            <span class="text-muted">Total Bill Valuation:</span>
                            <strong id="modalInwardAmount" class="text-dark"></strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Destination Warehouse (Stock Inward) <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="modalInwardWhSelect" class="form-select" required <?= ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || (!empty($warehouses) && count($warehouses) <= 1)) ? 'disabled' : '' ?>>
                            <?php if (!empty($warehouses)): ?>
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?= $wh['id'] ?>"><?= e($wh['name']) ?><?= !empty($wh['code']) ? ' (' . e($wh['code']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="1">Main Central Warehouse (WH-MAIN)</option>
                            <?php endif; ?>
                        </select>
                        <?php if ((function_exists('currentWarehouseId') && currentWarehouseId() !== null) || (!empty($warehouses) && count($warehouses) <= 1)): ?>
                            <input type="hidden" name="warehouse_id" value="<?= e($warehouses[0]['id'] ?? (function_exists('currentWarehouseId') ? currentWarehouseId() : '1')) ?>">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Confirm &amp; Inward Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: HIGH-RESOLUTION BILL SCREENSHOT VIEWER -->
<!-- ========================================================================= -->
<div class="modal fade" id="billImageModal" tabindex="-1" aria-labelledby="billImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6" id="billImageModalLabel"><i class="bi bi-receipt me-2 text-warning"></i>Bill / Invoice Screenshot</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3 bg-light">
                <img id="modalBillImg" src="" alt="Bill Screenshot" class="img-fluid rounded border shadow-sm" style="max-height: 80vh; object-fit: contain;">
            </div>
            <div class="modal-footer bg-white d-flex justify-content-between">
                <a id="modalDownloadBtn" href="" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i> Open / Download Full Resolution
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openImageModal(imgUrl, title) {
    document.getElementById('modalBillImg').src = imgUrl;
    document.getElementById('modalDownloadBtn').href = imgUrl;
    if (title) {
        document.getElementById('billImageModalLabel').innerHTML = '<i class="bi bi-receipt me-2 text-warning"></i>' + title;
    }
    const modal = new bootstrap.Modal(document.getElementById('billImageModal'));
    modal.show();
}

function openSingleQrModal(prod) {
    document.getElementById('modalQrProductName').textContent = prod.name || '';
    document.getElementById('modalQrSku').textContent = prod.sku || '';
    document.getElementById('modalQrSize').textContent = prod.size || 'Free';
    document.getElementById('modalQrColor').textContent = prod.color || 'Std';
    document.getElementById('modalQrSeller').textContent = prod.seller || '';
    document.getElementById('modalQrMrp').textContent = '₹' + parseFloat(prod.mrp || 0).toFixed(2);
    document.getElementById('modalQrPrice').textContent = '₹' + parseFloat(prod.price || prod.mrp || 0).toFixed(2);
    
    const qrData = prod.qr_code || prod.sku;
    document.getElementById('modalQrCodeText').textContent = qrData;
    
    // Generate QR Image URL
    const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(qrData);
    document.getElementById('modalQrImg').src = qrUrl;

    const modal = new bootstrap.Modal(document.getElementById('singleQrModal'));
    modal.show();
}

function printSingleQrLabel() {
    const cardHtml = document.getElementById('printableQrCard').outerHTML;
    const printWindow = window.open('', '_blank', 'width=600,height=600');
    printWindow.document.write('<html><head><title>Print QR Label</title>');
    printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
    printWindow.document.write('<style>@media print { body { margin: 0; padding: 10px; } .card { border: 1px solid #000 !important; box-shadow: none !important; } } </style>');
    printWindow.document.write('</head><body class="p-3 d-flex justify-content-center">');
    printWindow.document.write(cardHtml);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 400);
}

function openInwardModal(po) {
    document.getElementById('modalInwardPoNum').textContent = po.po_number || ('PO #' + po.id);
    document.getElementById('modalInwardSeller').textContent = po.seller_name || '';
    document.getElementById('modalInwardItems').textContent = (po.total_items || 1) + ' SKU(s)';
    document.getElementById('modalInwardQty').textContent = (po.total_qty || 0) + ' pcs';
    document.getElementById('modalInwardAmount').textContent = '₹' + parseFloat(po.total_amount || 0).toFixed(2);
    
    if (po.warehouse_id) {
        document.getElementById('modalInwardWhSelect').value = po.warehouse_id;
    }

    const form = document.getElementById('inwardBillForm');
    form.action = '<?= url("warehouse/sellers/inward-bill/") ?>' + po.id;

    const modal = new bootstrap.Modal(document.getElementById('inwardBillModal'));
    modal.show();
}

// =====================================================================
// DYNAMIC ADD PRODUCTS PANEL (Brand → Variant Loader)
// =====================================================================
const SELLER_ID = <?= (int)$seller['id'] ?>;
const API_SELLER_CATALOG = '<?= url('api/warehouse/seller-catalog') ?>';
const CATALOG_CREATE_URL = '<?= url('warehouse/catalog/create') ?>';
const CATALOG_EDIT_URL   = '<?= url('warehouse/catalog/edit') ?>';
const QR_LABELS_URL      = '<?= url('warehouse/labels') ?>';

let _allGrouped = {};
let _panelLoaded = false;

function toggleAddProductsPanel() {
    const panel = document.getElementById('addProductsPanel');
    const btn   = document.getElementById('btnAddProducts');
    if (panel.style.display === 'none' || !panel.style.display) {
        panel.style.display = 'block';
        btn.classList.replace('btn-primary', 'btn-warning');
        btn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Close Panel';
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (!_panelLoaded) loadBrandPills();
    } else {
        closeAddProductsPanel();
    }
}

function closeAddProductsPanel() {
    const panel = document.getElementById('addProductsPanel');
    const btn   = document.getElementById('btnAddProducts');
    panel.style.display = 'none';
    btn.classList.replace('btn-warning', 'btn-primary');
    btn.innerHTML = '<i class="bi bi-grid-3x3-gap-fill me-1"></i> Add Products';
}

let _brandsList = [];

function loadBrandPills() {
    const container = document.getElementById('brandPillsContainer');
    container.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div><span class="text-muted small">Loading brands for this supplier...</span>';

    fetch(`${API_SELLER_CATALOG}?seller_id=${SELLER_ID}`)
        .then(r => r.json())
        .then(data => {
            _panelLoaded = true;
            _allGrouped  = data.all_grouped || {};
            _brandsList  = data.brands || [];
            if (!_brandsList.length) {
                container.innerHTML = `<div class="alert alert-info mb-0 w-100 py-2">
                    <i class="bi bi-info-circle me-2"></i>No catalog brands found for this supplier yet.
                    <a href="${CATALOG_CREATE_URL}?seller_id=${SELLER_ID}" class="btn btn-sm btn-primary ms-3">
                        <i class="bi bi-plus-circle me-1"></i> Add First Product
                    </a>
                </div>`;
                return;
            }
            let pills = '';
            _brandsList.forEach((brand, idx) => {
                const prodCount = Object.keys(_allGrouped[brand] || {}).length;
                pills += `<button type="button" class="btn btn-outline-primary btn-sm fw-semibold rounded-pill px-3 brand-pill-btn"
                            id="brand-pill-${idx}"
                            data-brand-idx="${idx}">
                    <i class="bi bi-tag-fill me-1"></i>${brand}
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">${prodCount}</span>
                </button>`;
            });
            container.innerHTML = pills;

            // Attach click listeners safely
            container.querySelectorAll('.brand-pill-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idx = parseInt(this.getAttribute('data-brand-idx'));
                    const selectedBrand = _brandsList[idx];
                    selectBrand(selectedBrand, this);
                });
            });

            // Auto-select the first brand immediately so data loads dynamically
            const firstPill = document.getElementById('brand-pill-0');
            if (firstPill && _brandsList[0]) {
                selectBrand(_brandsList[0], firstPill);
            }
        })
        .catch(err => {
            console.error('Error loading seller brands:', err);
            container.innerHTML = `<div class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>Failed to load brands. Please refresh.</div>`;
        });
}

function selectBrand(brandName, pillEl) {
    // Highlight active pill
    document.querySelectorAll('.brand-pill-btn').forEach(b => {
        b.classList.remove('btn-primary');
        b.classList.add('btn-outline-primary');
    });
    if (pillEl) {
        pillEl.classList.remove('btn-outline-primary');
        pillEl.classList.add('btn-primary');
    }

    const labelEl = document.getElementById('activeBrandLabel');
    if (labelEl) labelEl.textContent = brandName;
    const areaEl = document.getElementById('brandVariantArea');
    if (areaEl) areaEl.style.display = 'block';
    
    const gridEl = document.getElementById('variantCardsGrid');
    if (gridEl) gridEl.innerHTML = '<div class="col-12 text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';
    const noMsgEl = document.getElementById('noVariantsMsg');
    if (noMsgEl) noMsgEl.style.display = 'none';

    const productsForBrand = _allGrouped[brandName] ? Object.values(_allGrouped[brandName]) : [];
    renderVariantCards(productsForBrand, brandName);
}

function renderVariantCards(products, brandName) {
    const grid = document.getElementById('variantCardsGrid');
    const noMsg = document.getElementById('noVariantsMsg');

    if (!products || !products.length) {
        grid.innerHTML = '';
        noMsg.style.display = 'block';
        return;
    }

    noMsg.style.display = 'none';
    let html = '';
    products.forEach(prod => {
        const imgHtml = prod.image_url
            ? `<img src="${prod.image_url}" alt="${prod.product_name}" class="rounded border" style="width:54px;height:54px;object-fit:cover;">`
            : `<div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted" style="width:54px;height:54px;"><i class="bi bi-image text-secondary fs-4 opacity-50"></i></div>`;

        let variantBadges = '';
        const variants = prod.variants || [];
        if (variants.length) {
            variants.forEach(v => {
                const stockBadge = v.warehouse_stock > 0
                    ? `<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.65rem;">${v.warehouse_stock} pcs</span>`
                    : `<span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:0.65rem;">0 pcs</span>`;
                const colorDot = v.color_hex
                    ? `<span class="rounded-circle border d-inline-block ms-1" style="width:8px;height:8px;background:${v.color_hex};vertical-align:middle;"></span>` : '';
                variantBadges += `<div class="d-flex align-items-center gap-1 flex-wrap py-1 border-bottom" style="font-size:0.78rem;">
                    <span class="badge bg-light text-dark border font-monospace fw-semibold">${v.size_name}${colorDot}</span>
                    <span class="text-muted font-monospace" style="font-size:0.7rem;">${v.sku}</span>
                    <span class="text-success fw-semibold ms-auto">₹${parseFloat(v.mrp).toFixed(0)}</span>
                    ${stockBadge}
                    <a href="${QR_LABELS_URL}?source_type=tp_purchase&variant_id=${v.variant_id}&seller_id=${SELLER_ID}" class="btn btn-xs btn-outline-success py-0 px-1" title="Generate QR Labels" style="font-size:0.7rem;">
                        <i class="bi bi-qr-code"></i>
                    </a>
                </div>`;
            });
        } else {
            variantBadges = '<div class="text-muted small py-2">No variants added yet</div>';
        }

        html += `<div class="col-md-4 col-lg-3">
            <div class="card border shadow-sm h-100 rounded-3 overflow-hidden">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        ${imgHtml}
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-bold text-dark text-truncate small">${prod.product_name}</div>
                            <span class="badge bg-dark text-white font-monospace" style="font-size:0.65rem;">${prod.product_code}</span>
                            <span class="badge bg-secondary-subtle text-secondary border" style="font-size:0.65rem;">${brandName}</span>
                        </div>
                    </div>
                    <div class="mb-2" style="max-height:180px;overflow-y:auto;">
                        ${variantBadges}
                    </div>
                    <div class="d-flex gap-1 mt-2">
                        <a href="${CATALOG_EDIT_URL}/${prod.product_id}" class="btn btn-sm btn-outline-primary flex-fill fw-semibold py-1" style="font-size:0.75rem;">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                        <a href="${QR_LABELS_URL}?source_type=tp_purchase&seller_id=${SELLER_ID}" class="btn btn-sm btn-outline-success flex-fill fw-semibold py-1" style="font-size:0.75rem;">
                            <i class="bi bi-qr-code me-1"></i> Labels
                        </a>
                    </div>
                </div>
            </div>
        </div>`;
    });
    grid.innerHTML = html;
}
</script>
