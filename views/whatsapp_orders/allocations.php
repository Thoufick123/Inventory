<?php
// views/whatsapp_orders/allocations.php — WhatsApp Stock Inventory & Warehouse Allocations Dashboard

$currTab = $activeTab ?? ($_GET['tab'] ?? 'stock');
$currStatus = strtolower($filters['stock_status'] ?? '');
$currCategory = (int)($filters['category_id'] ?? 0);
$currSort = $filters['sort'] ?? 'name_asc';
$currSearch = $filters['search'] ?? '';
$currProductType = $filters['product_type'] ?? 'normal';
$canRequestStock = hasPermission('whatsapp_orders', 'create');
$canEditPrice = hasPermission('whatsapp_inventory', 'adjust');
$productTabs = ['normal' => ['Normal Products', 'bi-tag'], 'series' => ['Series Products', 'bi-boxes'], 'complementary' => ['Complementary Products', 'bi-gift-fill']];
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-1 rounded-pill fw-semibold">
                <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($channel['name'] ?? 'WhatsApp Channel') ?>
            </span>
            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 font-monospace">
                <?= htmlspecialchars($channel['code'] ?? 'WA-01') ?>
            </span>
        </div>
        <h2 class="fw-bold mb-1 text-dark"><i class="bi bi-boxes me-2 text-success"></i>WhatsApp Stock &amp; Inventory</h2>
        <span class="text-muted">Real-time WhatsApp stock balances, catalog specifications, piece-by-piece quarantine, and warehouse allocations.</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($canRequestStock): ?>
        <a href="<?= url('warehouse/whatsapp-orders/requests/create') ?>" class="btn btn-primary rounded-pill px-3 shadow-sm fw-semibold">
            <i class="bi bi-plus-lg me-1"></i> Request Warehouse Stock
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- 5 Dynamic KPI Analysis Cards -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total WhatsApp Available Stock -->
    <div class="col-sm-6 col-xl">
        <a href="<?= url('warehouse/whatsapp-orders/allocations?tab=stock') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100 rounded-4 transition-card <?= $currTab === 'stock' && empty($currStatus) ? 'bg-success bg-opacity-10' : 'bg-white' ?>">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Available Stock</span>
                            <h3 class="mb-0 mt-1 fw-bold text-success">
                                <?= number_format($summary['total_units'] ?? 0) ?> 
                                <span class="fs-6 fw-normal text-muted">pcs</span>
                            </h3>
                            <small class="text-secondary d-flex align-items-center mt-1">
                                <i class="bi bi-box-seam me-1 text-success"></i> <?= number_format($summary['total_skus'] ?? 0) ?> Active SKUs
                            </small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success p-3 rounded-circle fs-3 flex-shrink-0">
                            <i class="bi bi-boxes"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 2: Retail Stock Valuation -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 rounded-4 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Stock Valuation</span>
                        <h3 class="mb-0 mt-1 fw-bold text-primary">
                            ₹<?= number_format($summary['total_valuation'] ?? 0, 2) ?>
                        </h3>
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">
                            MRP Val: <span class="fw-semibold text-dark">₹<?= number_format($summary['total_mrp_valuation'] ?? 0, 2) ?></span>
                        </small>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary p-3 rounded-circle fs-3 flex-shrink-0">
                        <i class="bi bi-currency-rupee"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Healthy Stock (In Stock) -->
    <div class="col-sm-6 col-xl">
        <a href="<?= url('warehouse/whatsapp-orders/allocations?tab=stock&stock_status=in_stock') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm border-start border-4 h-100 rounded-4 transition-card <?= $currStatus === 'in_stock' ? 'bg-info bg-opacity-10' : 'bg-white' ?>" style="border-color: #0d9488 !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">In Stock (Healthy)</span>
                            <h3 class="mb-0 mt-1 fw-bold" style="color: #0d9488;">
                                <?= number_format($summary['in_stock_count'] ?? 0) ?> 
                                <span class="fs-6 fw-normal text-muted">SKUs</span>
                            </h3>
                            <small class="text-success d-flex align-items-center mt-1">
                                <i class="bi bi-check-circle-fill me-1"></i> 
                                <?= ($summary['total_skus'] ?? 0) > 0 ? round((($summary['in_stock_count'] ?? 0) / $summary['total_skus']) * 100) : 0 ?>% of Catalog
                            </small>
                        </div>
                        <div class="stat-icon p-3 rounded-circle fs-3 flex-shrink-0" style="background-color: rgba(13, 148, 136, 0.1); color: #0d9488;">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 4: Low Stock Alert -->
    <div class="col-sm-6 col-xl">
        <a href="<?= url('warehouse/whatsapp-orders/allocations?tab=stock&stock_status=low_stock') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100 rounded-4 transition-card <?= $currStatus === 'low_stock' ? 'bg-warning bg-opacity-10' : 'bg-white' ?>">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Low Stock Alerts</span>
                            <h3 class="mb-0 mt-1 fw-bold text-warning-emphasis">
                                <?= number_format($summary['low_stock_count'] ?? 0) ?> 
                                <span class="fs-6 fw-normal text-muted">SKUs</span>
                            </h3>
                            <small class="text-warning-emphasis d-flex align-items-center mt-1">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Reorder Threshold Met
                            </small>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning-emphasis p-3 rounded-circle fs-3 flex-shrink-0">
                            <i class="bi bi-bell"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 5: Quarantined Damaged Stock -->
    <div class="col-sm-6 col-xl">
        <a href="<?= url('warehouse/whatsapp-orders/damage') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100 rounded-4 transition-card bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Damaged Quarantined</span>
                            <h3 class="mb-0 mt-1 fw-bold text-danger">
                                <?= number_format($summary['damaged_units'] ?? 0) ?> 
                                <span class="fs-6 fw-normal text-muted">pcs</span>
                            </h3>
                            <small class="text-danger d-flex align-items-center mt-1">
                                <i class="bi bi-shield-x me-1"></i> Est Loss: ₹<?= number_format($summary['damaged_loss'] ?? 0, 2) ?>
                            </small>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger p-3 rounded-circle fs-3 flex-shrink-0">
                            <i class="bi bi-exclamation-octagon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Nav Tabs: Live Stock Balances vs Warehouse Dispatches -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
    <div class="card-header bg-white border-bottom pt-3 pb-2 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <ul class="nav nav-tabs card-header-tabs border-bottom-0" id="whatsappInventoryTabs" role="tablist">
            <?php foreach ($productTabs as $type => [$label, $icon]): ?>
            <li class="nav-item">
                <a class="nav-link fw-semibold <?= $currTab === 'stock' && $currProductType === $type ? 'active text-success border-success border-bottom-0' : 'text-muted' ?>" href="<?= url('warehouse/whatsapp-orders/allocations?tab=stock&product_type=' . $type) ?>">
                    <i class="bi <?= $icon ?> me-1"></i><?= $label ?> (<?= count($productBuckets[$type] ?? []) ?>)
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if ($canEditPrice): ?>
            <button type="button" class="btn btn-outline-success shadow-sm fw-semibold btnTogglePriceEdit" onclick="togglePriceEditMode()">
                <i class="bi bi-pencil-square me-1"></i> <span class="btnPriceEditLabel">Edit Price</span>
            </button>
            <button type="button" class="btn btn-success shadow-sm fw-semibold btnHeaderApplyPrice d-none" onclick="openBulkPriceModal()">
                <i class="bi bi-check-circle-fill me-1"></i> Change Price (<span class="headerSelectedPriceCount">0</span>)
            </button>
            <?php endif; ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                <i class="bi bi-boxes me-1"></i> <?= number_format($summary['total_units'] ?? 0) ?> pcs Available
            </span>
        </div>
    </div>

    <div class="card-body p-4">
        <?php if ($currTab === 'stock'): ?>
            <!-- ========================================================================= -->
            <!-- TAB 1: LIVE PRODUCT STOCK BALANCES -->
            <!-- ========================================================================= -->
            
            <!-- Filter Bar -->
            <form method="GET" action="<?= url('warehouse/whatsapp-orders/allocations') ?>" class="row g-2 align-items-end mb-4">
                <input type="hidden" name="tab" value="stock">

                <!-- Keyword Search -->
                <div class="col-lg-4 col-md-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">Search Products / Variants</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Product name, SKU, QR, Code..." value="<?= htmlspecialchars($currSearch) ?>">
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="col-lg-3 col-md-3 col-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">-- All Categories --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $currCategory === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Stock Status Filter -->
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">Stock Health</label>
                    <select name="stock_status" class="form-select form-select-sm">
                        <option value="">-- All Stock Status --</option>
                        <option value="in_stock" <?= $currStatus === 'in_stock' ? 'selected' : '' ?>>In Stock (Healthy)</option>
                        <option value="low_stock" <?= $currStatus === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                        <option value="out_of_stock" <?= $currStatus === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                        <option value="damaged" <?= $currStatus === 'damaged' ? 'selected' : '' ?>>With Damaged Stock</option>
                    </select>
                </div>

                <!-- Sort Filter -->
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">Sort By</label>
                    <select name="sort" class="form-select form-select-sm">
                        <option value="name_asc" <?= $currSort === 'name_asc' ? 'selected' : '' ?>>Name (A &rarr; Z)</option>
                        <option value="name_desc" <?= $currSort === 'name_desc' ? 'selected' : '' ?>>Name (Z &rarr; A)</option>
                        <option value="stock_desc" <?= $currSort === 'stock_desc' ? 'selected' : '' ?>>Stock: High to Low</option>
                        <option value="stock_asc" <?= $currSort === 'stock_asc' ? 'selected' : '' ?>>Stock: Low to High</option>
                        <option value="price_desc" <?= $currSort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="price_asc" <?= $currSort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="sku_asc" <?= $currSort === 'sku_asc' ? 'selected' : '' ?>>SKU (A &rarr; Z)</option>
                    </select>
                </div>

                <!-- Submit / Clear -->
                <div class="col-lg-1 col-md-3 col-6 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-success w-100 fw-semibold" title="Apply Filter">
                        <i class="bi bi-funnel"></i>
                    </button>
                    <?php if (!empty($currSearch) || !empty($currCategory) || !empty($currStatus) || $currSort !== 'name_asc'): ?>
                        <a href="<?= url('warehouse/whatsapp-orders/allocations?tab=stock') ?>" class="btn btn-sm btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Stock Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="price-checkbox-col" style="display: none; width: 44px; text-align: center;">
                                <input type="checkbox" class="form-check-input select-all-price-chk" onchange="toggleSelectAllPriceCheckboxes(this.checked)" title="Select all on this page">
                            </th>
                            <th style="width: 45px;">#</th>
                            <th>Product &amp; Variant Specification</th>
                            <th>Category</th>
                            <th>Commercial Pricing</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Available Stock</th>
                            <th class="text-center">Damaged</th>
                            <th class="text-center">Total Sold</th>
                            <th class="text-center">Inward Received</th>
                            <th class="text-end" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventory)): ?>
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="bi bi-box-seam fs-1 d-block mb-2 text-success opacity-50"></i>
                                    <div class="fw-semibold">No stock products found matching criteria.</div>
                                    <small class="text-muted">Request stock from central warehouse to populate WhatsApp available inventory.</small>
                                    <?php if ($canRequestStock): ?>
                                    <div class="mt-3">
                                        <a href="<?= url('warehouse/whatsapp-orders/requests/create') ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-lg me-1"></i> Request Warehouse Stock
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: $idx = 1; foreach ($inventory as $it): 
                            $avail = (float)$it['available_quantity'];
                            $dmg   = (float)$it['damaged_quantity'];
                            $sold  = (float)$it['sold_quantity'];
                            $inw   = (float)$it['total_inward_quantity'];
                            $minStock = (int)($it['min_stock'] ?? 5);

                            if ($avail <= 0) {
                                $stBadge = '<span class="badge bg-dark text-white px-2 py-1">OUT OF STOCK</span>';
                            } elseif ($avail <= $minStock) {
                                $stBadge = '<span class="badge bg-warning text-dark px-2 py-1">LOW STOCK (' . $avail . ')</span>';
                            } else {
                                $stBadge = '<span class="badge bg-success text-white px-2 py-1">IN STOCK</span>';
                            }

                            $img = !empty($it['variant_image']) ? $it['variant_image'] : (!empty($it['product_image']) ? $it['product_image'] : '');
                        ?>
                            <tr id="stock-row-<?= (int)$it['variant_id'] ?>">
                                <td class="price-checkbox-col text-center" style="display: none;">
                                    <input type="checkbox" class="form-check-input price-item-checkbox" 
                                           value="<?= (int)$it['variant_id'] ?>" 
                                           data-sku="<?= htmlspecialchars($it['sku'] ?? '') ?>" 
                                           data-product-name="<?= htmlspecialchars($it['product_name'] ?? '') ?>" 
                                           data-selling-price="<?= (float)$it['selling_price'] ?>" 
                                           data-mrp="<?= (float)($it['mrp'] ?? 0) ?>" 
                                           data-cost-price="<?= (float)(($it['production_cost'] ?? 0) > 0 ? $it['production_cost'] : ($it['purchase_price'] ?? 0)) ?>" 
                                           onchange="updatePriceSelectionState()">
                                </td>
                                <td class="text-muted small fw-semibold"><?= $idx++ ?></td>

                                <!-- Product Info -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="flex-shrink-0 bg-light rounded border p-1 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                            <?php if ($img): ?>
                                                <img src="<?= htmlspecialchars($img) ?>" alt="Product" class="rounded" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <i class="bi bi-box-seam text-secondary fs-4"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="<?= url('warehouse/whatsapp-orders/inventory/detail/' . $it['variant_id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary mb-0 d-block">
                                                <?= htmlspecialchars($it['product_name']) ?>
                                            </a>
                                            <small class="text-muted font-monospace" style="font-size: 0.78rem;">
                                                SKU: <a href="<?= url('warehouse/whatsapp-orders/inventory/detail/' . $it['variant_id']) ?>" class="text-decoration-none"><code><?= htmlspecialchars($it['sku']) ?></code></a>
                                            </small>
                                            <div class="d-flex gap-1 mt-0.5 align-items-center flex-wrap">
                                                <?php if (!empty($it['size_name'])): ?>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.68rem;">Size: <?= htmlspecialchars($it['size_name']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($it['color_name'])): ?>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.68rem;">Color: <?= htmlspecialchars($it['color_name']) ?></span>
                                                <?php endif; ?>
                                                <a href="<?= url('warehouse/whatsapp-orders/inventory/detail/' . $it['variant_id']) ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none" style="font-size: 0.68rem;" title="View Tracked Piece QR & Tag Codes">
                                                    <i class="bi bi-qr-code me-1"></i>Track QR &amp; Tags
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Category -->
                                <td>
                                    <?php if (!empty($it['category_name'])): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <?= htmlspecialchars($it['category_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Commercial Pricing -->
                                <td>
                                    <div>
                                        <div class="fw-bold text-dark selling-price-val-<?= (int)$it['variant_id'] ?>">₹<?= number_format($it['selling_price'], 2) ?></div>
                                        <small class="text-muted mrp-price-val-<?= (int)$it['variant_id'] ?>" style="font-size: 0.74rem;">
                                            MRP: <span class="text-decoration-line-through">₹<?= number_format($it['mrp'], 2) ?></span>
                                        </small>
                                    </div>
                                </td>

                                <!-- Stock Status -->
                                <td class="text-center">
                                    <?= $stBadge ?>
                                </td>

                                <!-- Available Stock -->
                                <td class="text-center">
                                    <span class="fw-bold text-success fs-6"><?= number_format($avail) ?></span> <span class="text-muted small">pcs</span>
                                </td>

                                <!-- Damaged Stock -->
                                <td class="text-center">
                                    <?php if ($dmg > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">
                                            <i class="bi bi-exclamation-octagon me-1"></i><?= number_format($dmg) ?> pcs
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Total Sold -->
                                <td class="text-center">
                                    <span class="text-dark fw-semibold"><?= number_format($sold) ?></span> <span class="text-muted small">pcs</span>
                                    <?php 
                                        $retQty = (float)($it['returned_quantity'] ?? 0);
                                        $excQty = (float)($it['exchanged_out_quantity'] ?? 0);
                                        if ($retQty > 0 || $excQty > 0): 
                                    ?>
                                        <div class="d-flex justify-content-center gap-1 mt-1 flex-wrap" style="font-size: 0.68rem;">
                                            <?php if ($retQty > 0): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="<?= $retQty ?> item(s) returned">
                                                    <i class="bi bi-arrow-return-left me-0.5"></i>-<?= $retQty ?> ret
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($excQty > 0): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="<?= $excQty ?> item(s) exchange replacement dispatched">
                                                    <i class="bi bi-arrow-left-right me-0.5"></i>+<?= $excQty ?> exc
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Total Inward Received -->
                                <td class="text-center">
                                    <span class="text-secondary small fw-semibold"><?= number_format($inw) ?> pcs</span>
                                </td>

                                <!-- Actions -->
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                                        <a href="<?= url('warehouse/whatsapp-orders/inventory/detail/' . $it['variant_id']) ?>" 
                                            class="btn btn-xs btn-outline-primary px-2 py-1" 
                                            title="View Full Product History &amp; Stock Ledger">
                                            <i class="bi bi-clock-history me-1"></i>History
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- ========================================================================= -->
            <!-- TAB 2: WAREHOUSE INWARD ALLOCATIONS HISTORY -->
            <!-- ========================================================================= -->

            <!-- Search & Filter Card -->
            <form method="GET" action="<?= url('warehouse/whatsapp-orders/allocations') ?>" class="row g-2 align-items-end mb-4">
                <input type="hidden" name="tab" value="allocations">

                <!-- Search Keyword -->
                <div class="col-lg-4 col-md-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">Search Allocations</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Allocation #, Channel, Notes..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">Status</label>
                    <select name="alloc_status" class="form-select form-select-sm">
                        <option value="">-- All Statuses --</option>
                        <option value="COMPLETED" <?= ($filters['alloc_status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>COMPLETED</option>
                        <option value="DISPATCHED" <?= ($filters['alloc_status'] ?? '') === 'DISPATCHED' ? 'selected' : '' ?>>DISPATCHED</option>
                        <option value="APPROVED" <?= ($filters['alloc_status'] ?? '') === 'APPROVED' ? 'selected' : '' ?>>APPROVED</option>
                        <option value="PENDING" <?= ($filters['alloc_status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                        <option value="CANCELLED" <?= ($filters['alloc_status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>CANCELLED</option>
                    </select>
                </div>

                <!-- Date Range From -->
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                </div>

                <!-- Date Range To -->
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                </div>

                <!-- Action Buttons -->
                <div class="col-lg-2 col-md-3 col-6 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <?php if (!empty($filters['search']) || !empty($filters['alloc_status']) || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
                        <a href="<?= url('warehouse/whatsapp-orders/allocations?tab=allocations') ?>" class="btn btn-sm btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Allocations Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 150px;">Allocation Ref #</th>
                            <th>Destination Channel</th>
                            <th class="text-center">Unique Items</th>
                            <th class="text-center">Quantity (pcs)</th>
                            <th class="text-end">Total Value</th>
                            <th class="text-center">Status</th>
                            <th>Allocation Date</th>
                            <th class="text-end" style="width: 130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allocations)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-primary opacity-50"></i>
                                    <div class="fw-semibold">No WhatsApp allocation records found.</div>
                                    <small class="text-muted">Dispatches from Central Warehouse to WhatsApp channel appear here.</small>
                                    <?php if ($canRequestStock): ?>
                                    <div class="mt-3">
                                        <a href="<?= url('warehouse/whatsapp-orders/requests/create') ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-lg me-1"></i> New Stock Request
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: foreach ($allocations as $a): 
                            $st = strtoupper($a['status'] ?? 'PENDING');
                            $stColor = in_array($st, ['COMPLETED', 'DISPATCHED', 'DELIVERED', 'APPROVED']) ? 'success' : ($st === 'CANCELLED' ? 'danger' : 'warning');
                        ?>
                            <tr>
                                <!-- Ref # -->
                                <td>
                                    <div class="fw-bold text-dark font-monospace" style="font-size: 0.9rem;">
                                        <?= htmlspecialchars($a['allocation_number']) ?>
                                    </div>
                                    <?php if (!empty($a['notes'])): ?>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 140px;" title="<?= htmlspecialchars($a['notes']) ?>">
                                            <?= htmlspecialchars($a['notes']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <!-- Channel -->
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($a['channel_name']) ?>
                                    </span>
                                </td>

                                <!-- Unique Items -->
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        <?= number_format($a['distinct_items_count'] ?? 1) ?> items
                                    </span>
                                </td>

                                <!-- Quantity -->
                                <td class="text-center">
                                    <span class="fw-bold text-dark fs-6"><?= number_format($a['total_quantity']) ?></span> <span class="text-muted small">pcs</span>
                                </td>

                                <!-- Total Value -->
                                <td class="text-end">
                                    <span class="fw-bold text-success fs-6">₹<?= number_format($a['total_value'], 2) ?></span>
                                </td>

                                <!-- Status -->
                                <td class="text-center">
                                    <span class="badge bg-<?= $stColor ?>-subtle text-<?= $stColor ?> border border-<?= $stColor ?>-subtle px-2 py-1">
                                        <?= htmlspecialchars($a['status']) ?>
                                    </span>
                                </td>

                                <!-- Date -->
                                <td>
                                    <div class="text-dark small fw-semibold"><?= date('d M Y', strtotime($a['created_at'])) ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;"><?= date('h:i A', strtotime($a['created_at'])) ?></small>
                                </td>

                                <!-- Actions -->
                                <td class="text-end">
                                    <a href="<?= url('warehouse/whatsapp-orders/allocations/view/' . $a['id']) ?>" class="btn btn-xs btn-outline-primary px-2 py-1">
                                        <i class="bi bi-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Floating Bulk Price Action Toolbar (Active when items are selected) -->
<?php if ($canEditPrice): ?>
<div id="priceEditFloatingBar" class="card shadow-lg border-0 position-fixed bottom-0 start-50 translate-middle-x mb-4 px-4 py-2 z-3 d-none align-items-center flex-row gap-3 rounded-pill bg-dark text-white border border-light border-opacity-25" style="backdrop-filter: blur(12px); z-index: 1060; max-width: 92vw; box-shadow: 0 10px 25px rgba(0,0,0,0.3) !important;">
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-success rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
            <i class="bi bi-check2 text-white fw-bold"></i>
        </span>
        <span class="fw-semibold text-nowrap"><span id="floatingSelectedCount">0</span> selected</span>
    </div>
    <div class="vr bg-secondary my-1"></div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="selectAllPriceItems(true)">
            <i class="bi bi-check-all me-1"></i> Select All
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary text-white-50 rounded-pill px-2.5" onclick="clearPriceSelection()">
            Clear
        </button>
        <button type="button" class="btn btn-sm btn-success rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" onclick="openBulkPriceModal()">
            <i class="bi bi-pencil-square"></i>
            <span>Change Price</span>
        </button>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- MODAL: EDIT PRODUCT RETAIL PRICE & MRP (WHATSAPP CHANNEL) -->
<!-- ========================================================================= -->
<?php if ($canEditPrice): ?>
<div class="modal fade" id="editPriceModal" tabindex="-1" aria-labelledby="editPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form id="editPriceForm" onsubmit="handlePriceFormSubmit(event)">
                <?= csrf_field() ?>
                <input type="hidden" name="variant_ids" id="editPriceVariantIds" value="">
                <input type="hidden" name="variant_id" id="editPriceVariantId" value="">
                <input type="hidden" id="editPriceCostVal" value="0">
                
                <div class="modal-header bg-success text-white py-3 px-4 border-0">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="rounded-circle p-1.5 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.15);">
                            <i class="bi bi-tag-fill fs-4 text-white"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-white mb-0" id="editPriceModalLabel">Edit Product Price</h5>
                            <small class="text-white-50 font-monospace" id="editPriceModalSku">SKU: ---</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <!-- Product Title Banner / Selected Items Box -->
                    <div class="p-3 bg-white rounded-3 border shadow-2xs mb-3" id="modalProductInfoBox">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <h6 class="fw-bold text-dark mb-0" id="editPriceModalProductName">Product</h6>
                            <span class="badge bg-success" id="modalSelectedCountBadge" style="display: none;">0 Products</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap" id="modalSingleInfoTags">
                            <span class="badge bg-dark font-monospace text-white px-2 py-1" id="editPriceBadgeSku">-</span>
                            <span class="badge bg-light text-muted border" id="editPriceCostBadge">Cost: ₹0.00</span>
                        </div>
                        <div id="modalSelectedBadgesList" class="d-flex flex-wrap gap-1 mt-2" style="max-height: 110px; overflow-y: auto; display: none;">
                            <!-- Populated dynamically with SKU chips in bulk mode -->
                        </div>
                    </div>

                    <!-- Alert message container -->
                    <div id="editPriceAlertContainer"></div>

                    <!-- Price Inputs (MRP and Selling Price) -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-bold small text-uppercase text-dark mb-1">
                                Maximum Retail Price (MRP ₹)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted">₹</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       name="mrp" 
                                       id="inputMrpPrice" 
                                       class="form-control form-control-lg text-dark fw-semibold" 
                                       placeholder="0.00" 
                                       oninput="calculatePriceAnalytics()">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Printed sticker / catalog MRP</small>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-bold small text-uppercase text-dark mb-1">
                                Selling Price (₹) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold text-success">₹</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       name="selling_price" 
                                       id="inputSellingPrice" 
                                       class="form-control form-control-lg fw-bold text-success" 
                                       placeholder="0.00" 
                                       required
                                       oninput="calculatePriceAnalytics()">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Active retail selling rate</small>
                        </div>
                    </div>

                    <!-- Live Price Analytics Card -->
                    <div class="p-3 bg-white rounded-3 border shadow-2xs mb-3" id="priceAnalyticsCard">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted fw-semibold text-uppercase" style="font-size: 10px;">Price Dynamics &amp; Margin</small>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace" id="badgeDiscountPreview">Standard Price</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small">
                                <span class="text-muted">Markup over Cost: </span>
                                <strong class="text-dark" id="txtMarkupPreview">—</strong>
                            </div>
                            <div class="small">
                                <span class="text-muted">Discount on MRP: </span>
                                <strong class="text-primary" id="txtDiscountPreview">0%</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Reason / Revision Notes -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-muted text-uppercase mb-1">Revision Notes / Reason (Optional)</label>
                        <input type="text" name="notes" id="inputPriceNotes" class="form-control" placeholder="e.g. WhatsApp channel promotion, Season discount">
                    </div>
                </div>

                <div class="modal-footer bg-white px-4 py-3 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm" id="btnSavePrice">
                        <i class="bi bi-check-circle me-1"></i> Save Price Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
let isPriceEditMode = false;
let currentEditPriceModal = null;
const canEditPrice = <?= $canEditPrice ? 'true' : 'false' ?>;

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function togglePriceEditMode(forceState) {
    if (!canEditPrice) return;
    if (typeof forceState === 'boolean') {
        isPriceEditMode = forceState;
    } else {
        isPriceEditMode = !isPriceEditMode;
    }
    
    const checkboxCols = document.querySelectorAll('.price-checkbox-col');
    checkboxCols.forEach(col => {
        col.style.display = isPriceEditMode ? '' : 'none';
    });
    
    const btns = document.querySelectorAll('.btnTogglePriceEdit, #btnTogglePriceEdit');
    const labels = document.querySelectorAll('.btnPriceEditLabel, #btnPriceEditLabel');
    btns.forEach(btn => {
        if (isPriceEditMode) {
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-success');
        } else {
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-success');
        }
    });
    labels.forEach(lbl => {
        lbl.innerText = isPriceEditMode ? 'Close Price Edit' : 'Edit Price';
    });
    if (!isPriceEditMode) {
        clearPriceSelection();
    }
    updatePriceSelectionState();
}

function getSelectedPriceCheckboxes() {
    return Array.from(document.querySelectorAll('.price-item-checkbox:checked'));
}

function updatePriceSelectionState() {
    const selected = getSelectedPriceCheckboxes();
    const count = selected.length;
    
    const floatingBar = document.getElementById('priceEditFloatingBar');
    const headerBtns = document.querySelectorAll('.btnHeaderApplyPrice, #btnHeaderApplyPrice');
    const floatCount = document.getElementById('floatingSelectedCount');
    const headerCounts = document.querySelectorAll('.headerSelectedPriceCount, #headerSelectedPriceCount');
    
    if (floatCount) floatCount.innerText = count;
    headerCounts.forEach(el => el.innerText = count);
    
    if (isPriceEditMode && count > 0) {
        if (floatingBar) floatingBar.classList.remove('d-none');
        headerBtns.forEach(btn => btn.classList.remove('d-none'));
    } else {
        if (floatingBar) floatingBar.classList.add('d-none');
        headerBtns.forEach(btn => btn.classList.add('d-none'));
    }
    
    // Highlight selected rows
    document.querySelectorAll('.price-item-checkbox').forEach(chk => {
        const tr = chk.closest('tr');
        if (tr) {
            if (chk.checked) {
                tr.classList.add('table-success');
            } else {
                tr.classList.remove('table-success');
            }
        }
    });
}

function toggleSelectAllPriceCheckboxes(checked) {
    const checkboxes = document.querySelectorAll('.price-item-checkbox');
    checkboxes.forEach(chk => {
        const table = chk.closest('table');
        if (table && table.offsetParent !== null) {
            chk.checked = checked;
        }
    });
    const selectAllHeaders = document.querySelectorAll('.select-all-price-chk');
    selectAllHeaders.forEach(chk => chk.checked = checked);
    updatePriceSelectionState();
}

function selectAllPriceItems(checked) {
    const checkboxes = document.querySelectorAll('.price-item-checkbox');
    checkboxes.forEach(chk => {
        const table = chk.closest('table');
        if (table && table.offsetParent !== null) {
            chk.checked = checked;
        }
    });
    const selectAllHeaders = document.querySelectorAll('.select-all-price-chk');
    selectAllHeaders.forEach(chk => chk.checked = checked);
    updatePriceSelectionState();
}

function clearPriceSelection() {
    const checkboxes = document.querySelectorAll('.price-item-checkbox');
    checkboxes.forEach(chk => chk.checked = false);
    const selectAllHeaders = document.querySelectorAll('.select-all-price-chk');
    selectAllHeaders.forEach(chk => chk.checked = false);
    updatePriceSelectionState();
}

function openBulkPriceModal() {
    if (!canEditPrice) return;
    const selected = getSelectedPriceCheckboxes();
    if (selected.length === 0) {
        alert('Please select at least one product using the checkboxes.');
        return;
    }
    
    const variantIds = selected.map(chk => chk.value);
    document.getElementById('editPriceVariantIds').value = variantIds.join(',');
    document.getElementById('editPriceVariantId').value = variantIds[0];
    
    const countBadge = document.getElementById('modalSelectedCountBadge');
    const singleTags = document.getElementById('modalSingleInfoTags');
    const badgesContainer = document.getElementById('modalSelectedBadgesList');
    badgesContainer.innerHTML = '';
    
    let totalCost = 0;
    let costCount = 0;
    let firstSelling = '';
    let firstMrp = '';
    
    selected.forEach((chk, idx) => {
        const sku = chk.dataset.sku || ('Variant #' + chk.value);
        const name = chk.dataset.productName || '';
        const cost = parseFloat(chk.dataset.costPrice) || 0;
        const sp = parseFloat(chk.dataset.sellingPrice) || 0;
        const mrp = parseFloat(chk.dataset.mrp) || 0;
        
        if (cost > 0) {
            totalCost += cost;
            costCount++;
        }
        if (idx === 0) {
            firstSelling = sp > 0 ? sp.toFixed(2) : '';
            firstMrp = mrp > 0 ? mrp.toFixed(2) : '';
        }
        
        const badge = document.createElement('span');
        badge.className = 'badge bg-secondary-subtle text-dark border font-monospace py-1 px-2';
        badge.style.fontSize = '0.74rem';
        badge.innerText = sku + (name ? ` (${name})` : '');
        badgesContainer.appendChild(badge);
    });
    
    if (variantIds.length === 1) {
        const firstChk = selected[0];
        document.getElementById('editPriceModalLabel').innerText = 'Edit Product Price';
        document.getElementById('editPriceModalSku').innerText = 'SKU: ' + (firstChk.dataset.sku || '---');
        document.getElementById('editPriceModalProductName').innerText = firstChk.dataset.productName || '1 Product Selected';
        document.getElementById('editPriceBadgeSku').innerText = firstChk.dataset.sku || 'SKU';
        const cost = parseFloat(firstChk.dataset.costPrice) || 0;
        document.getElementById('editPriceCostVal').value = cost;
        document.getElementById('editPriceCostBadge').innerText = (cost > 0) ? 'Cost: ₹' + cost.toFixed(2) : 'Cost: Standard';
        
        document.getElementById('inputSellingPrice').value = firstSelling;
        document.getElementById('inputMrpPrice').value = firstMrp;
        
        if (countBadge) countBadge.style.display = 'none';
        if (singleTags) singleTags.style.display = 'flex';
        if (badgesContainer) badgesContainer.style.display = 'none';
    } else {
        document.getElementById('editPriceModalLabel').innerText = 'Bulk Edit Product Prices';
        document.getElementById('editPriceModalSku').innerText = `${variantIds.length} Products Selected`;
        document.getElementById('editPriceModalProductName').innerText = `Batch Price Update (${variantIds.length} Products)`;
        
        const avgCost = costCount > 0 ? (totalCost / costCount) : 0;
        document.getElementById('editPriceCostVal').value = avgCost;
        
        document.getElementById('inputSellingPrice').value = firstSelling;
        document.getElementById('inputMrpPrice').value = firstMrp;
        
        if (countBadge) {
            countBadge.innerText = `${variantIds.length} Products`;
            countBadge.style.display = 'inline-block';
        }
        if (singleTags) singleTags.style.display = 'none';
        if (badgesContainer) badgesContainer.style.display = 'flex';
    }
    
    document.getElementById('editPriceAlertContainer').innerHTML = '';
    document.getElementById('inputPriceNotes').value = '';
    calculatePriceAnalytics();
    
    const modalEl = document.getElementById('editPriceModal');
    if (modalEl) {
        currentEditPriceModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        currentEditPriceModal.show();
        setTimeout(() => {
            const spInput = document.getElementById('inputSellingPrice');
            if (spInput) {
                spInput.focus();
                spInput.select();
            }
        }, 300);
    }
}

window.openEditPriceModal = function(variantId, sku, productName, sellingPrice, mrp, costPrice = 0) {
    if (!canEditPrice) return;
    if (typeof variantId === 'object' && variantId !== null) {
        const obj = variantId;
        variantId = obj.variantId || obj.variant_id;
        sku = obj.sku;
        productName = obj.productName || obj.product_name;
        sellingPrice = obj.sellingPrice || obj.selling_price;
        mrp = obj.mrp;
        costPrice = obj.costPrice || obj.cost_price || 0;
    }
    
    const chk = document.querySelector(`.price-item-checkbox[value="${variantId}"]`);
    if (chk) {
        chk.checked = true;
        updatePriceSelectionState();
        openBulkPriceModal();
        return;
    }
    
    document.getElementById('editPriceVariantIds').value = variantId;
    document.getElementById('editPriceVariantId').value = variantId;
    document.getElementById('editPriceModalSku').textContent = `SKU: ${sku || '---'}`;
    document.getElementById('editPriceBadgeSku').textContent = sku || '-';
    document.getElementById('editPriceModalProductName').textContent = productName || 'Product';
    document.getElementById('inputSellingPrice').value = parseFloat(sellingPrice || 0).toFixed(2);
    document.getElementById('inputMrpPrice').value = (parseFloat(mrp) > 0) ? parseFloat(mrp).toFixed(2) : parseFloat(sellingPrice || 0).toFixed(2);
    document.getElementById('editPriceCostVal').value = parseFloat(costPrice || 0);
    document.getElementById('editPriceCostBadge').textContent = (parseFloat(costPrice) > 0) ? `Cost: ₹${parseFloat(costPrice).toFixed(2)}` : 'Cost: Standard';
    document.getElementById('editPriceAlertContainer').innerHTML = '';
    document.getElementById('inputPriceNotes').value = '';

    const countBadge = document.getElementById('modalSelectedCountBadge');
    const singleTags = document.getElementById('modalSingleInfoTags');
    const badgesContainer = document.getElementById('modalSelectedBadgesList');
    if (countBadge) countBadge.style.display = 'none';
    if (singleTags) singleTags.style.display = 'flex';
    if (badgesContainer) badgesContainer.style.display = 'none';

    calculatePriceAnalytics();

    const modalEl = document.getElementById('editPriceModal');
    if (modalEl) {
        currentEditPriceModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        currentEditPriceModal.show();
        setTimeout(() => {
            const spInput = document.getElementById('inputSellingPrice');
            if (spInput) {
                spInput.focus();
                spInput.select();
            }
        }, 300);
    }
};

window.calculatePriceAnalytics = function() {
    const sp = parseFloat(document.getElementById('inputSellingPrice')?.value) || 0;
    const mrp = parseFloat(document.getElementById('inputMrpPrice')?.value) || 0;
    const cost = parseFloat(document.getElementById('editPriceCostVal')?.value) || 0;

    const discountBadge = document.getElementById('badgeDiscountPreview');
    const txtDiscount = document.getElementById('txtDiscountPreview');
    const txtMarkup = document.getElementById('txtMarkupPreview');

    // Calculate Discount on MRP
    if (mrp > sp && sp > 0 && mrp > 0) {
        const discPct = Math.round(((mrp - sp) / mrp) * 100);
        if (txtDiscount) txtDiscount.textContent = `${discPct}% Off`;
        if (discountBadge) {
            discountBadge.textContent = `${discPct}% Discount`;
            discountBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill font-monospace';
        }
    } else {
        if (txtDiscount) txtDiscount.textContent = '0%';
        if (discountBadge) {
            discountBadge.textContent = 'Standard Price';
            discountBadge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace';
        }
    }

    // Calculate Markup over Cost
    if (cost > 0 && sp > 0) {
        const markupPct = Math.round(((sp - cost) / cost) * 100);
        if (txtMarkup) {
            txtMarkup.textContent = markupPct >= 0 ? `+${markupPct}%` : `${markupPct}%`;
            txtMarkup.className = markupPct >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
        }
    } else {
        if (txtMarkup) txtMarkup.textContent = '—';
    }
};

window.handlePriceFormSubmit = function(event) {
    event.preventDefault();
    if (!canEditPrice) return;
    const form = document.getElementById('editPriceForm');
    const btn = document.getElementById('btnSavePrice');
    const alertBox = document.getElementById('editPriceAlertContainer');
    const sp = parseFloat(document.getElementById('inputSellingPrice').value) || 0;
    const mrp = parseFloat(document.getElementById('inputMrpPrice').value) || 0;

    if (sp < 0) {
        alertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3">Selling price cannot be negative.</div>`;
        return;
    }
    if (mrp <= 0 || sp > mrp) {
        alertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3">Selling/discount price must be less than or equal to the MRP/base price.</div>`;
        return;
    }

    btn.disabled = true;
    const origBtnHtml = btn.innerHTML;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...`;

    const formData = new FormData(form);
    formData.append('is_ajax', '1');

    fetch('<?= url("warehouse/whatsapp-orders/inventory/update-price") ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = origBtnHtml;

        if (!res.success) {
            alertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3"><i class="bi bi-exclamation-triangle me-1"></i>${escapeHtml(res.message || 'Error updating price')}</div>`;
            return;
        }

        // Handle array of updated variants
        const updatedList = res.updated_variants || (res.data ? [res.data] : []);
        updatedList.forEach(item => {
            const vId = item.variant_id;
            const rowSellingEl = document.querySelector(`.selling-price-val-${vId}`);
            const rowMrpEl = document.querySelector(`.mrp-price-val-${vId}`);
            const row = document.getElementById(`stock-row-${vId}`);

            if (rowSellingEl) rowSellingEl.textContent = item.formatted_price;
            if (rowMrpEl) {
                if (item.new_mrp > item.new_selling_price) {
                    rowMrpEl.innerHTML = `MRP: <span class="text-decoration-line-through">${item.formatted_mrp}</span>`;
                    rowMrpEl.classList.remove('d-none');
                } else {
                    rowMrpEl.classList.add('d-none');
                }
            }

            // Update checkbox dataset attributes
            const chk = document.querySelector(`.price-item-checkbox[value="${vId}"]`);
            if (chk) {
                chk.dataset.sellingPrice = item.new_selling_price;
                chk.dataset.mrp = item.new_mrp;
            }

            // Green pulse animation on affected row
            if (row) {
                row.classList.remove('table-success');
                row.style.transition = 'background-color 0.4s ease';
                row.style.backgroundColor = '#d1e7dd';
                setTimeout(() => {
                    row.style.backgroundColor = '';
                }, 2500);
            }
        });

        // Clear selection
        clearPriceSelection();

        // Hide Edit Price Modal
        if (currentEditPriceModal) {
            currentEditPriceModal.hide();
        }

        // Show alert / toast
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Price Updated!',
                text: res.message,
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            showPriceUpdateToast(res.message || 'Price updated successfully!');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origBtnHtml;
        alertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3">Server connection error. Please try again.</div>`;
    });
};

function showPriceUpdateToast(message) {
    let toast = document.getElementById('priceUpdateLiveToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'priceUpdateLiveToast';
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="toast align-items-center text-white bg-success border-0 show shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <span id="priceUpdateLiveToastMsg"></span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        document.body.appendChild(toast);
    }
    const msgEl = document.getElementById('priceUpdateLiveToastMsg');
    if (msgEl) msgEl.textContent = message;
    setTimeout(() => {
        if (toast && toast.parentNode) toast.remove();
    }, 4000);
}
</script>
