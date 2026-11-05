<?php
// views/shop/inventory/index.php — Shop Stock Ledger with Quick Analysis Cards, Advanced Filter Options, Piece QR Codes & Indent Requests

$currStatus = strtolower($filters['status'] ?? '');
$currCategory = (string)($filters['category_id'] ?? '');
$currSort = $filters['sort'] ?? 'name_asc';
$currSearch = $filters['search'] ?? '';
$hasMultipleShops = count($shops ?? []) > 1;
$shopQuery = '?shop_id=' . (int)$shop['id'];
?>
<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<div class="container-fluid py-4">
    <!-- 1. Header & Quick Actions -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-1 rounded-pill fw-semibold">
                    <i class="bi bi-shop me-1"></i><?= e($shop['name']) ?>
                </span>
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 font-monospace">
                    <?= e($shop['code'] ?? 'SHOP-01') ?>
                </span>
            </div>
            <h2 class="fw-bold mb-1 text-dark">Shop Stock Inventory</h2>
            <p class="text-muted small mb-0">Live stock balances, commercial catalog specifications, piece-by-piece QR codes, and stock ledger.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (hasPermission('pos', 'view') || hasPermission('pos', 'create')): ?>
                <a href="<?= url('shop/pos') ?>" class="btn btn-outline-primary rounded-pill px-3 shadow-sm fw-semibold">
                    <i class="bi bi-receipt me-1"></i> Launch POS Terminal
                </a>
            <?php endif; ?>
            <?php if (hasPermission('shop_stock', 'request') || hasPermission('shop_stock', 'create')): ?>
                <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#requestStockModal">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Request Warehouse Stock
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. QUICK ANALYSIS CARDS (6 Interactive Analysis & Filter Cards) -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total In Stock / Available Units -->
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100 rounded-4 transition-card cursor-pointer <?= empty($currStatus) || $currStatus === 'in_stock' ? 'bg-success bg-opacity-10' : 'bg-white' ?>" 
                 onclick="quickFilterStatus('in_stock')" 
                 title="Click to view all sellable in-stock items"
                 style="cursor: pointer;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-success small fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">
                                <i class="bi bi-check2-circle me-1"></i>In Stock (Available)
                            </span>
                            <h3 class="mb-0 mt-1 fw-bold text-success">
                                <?= number_format($summary['total_available_units'] ?? $summary['total_units'] ?? 0) ?> 
                                <span class="fs-6 fw-normal text-muted">pcs</span>
                            </h3>
                            <small class="text-success d-flex align-items-center mt-1" style="font-size: 11px;">
                                <?= number_format($summary['in_stock_count'] ?? 0) ?> Active SKUs
                            </small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success p-3 rounded-circle fs-4 flex-shrink-0">
                            <i class="bi bi-boxes"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Damaged / Quarantined Stock -->
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100 rounded-4 transition-card cursor-pointer <?= $currStatus === 'damaged' ? 'bg-danger bg-opacity-10' : 'bg-white' ?>" 
                 onclick="quickFilterStatus('damaged')" 
                 title="Click to filter products with damaged stock"
                 style="cursor: pointer;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-danger small fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">
                                <i class="bi bi-slash-circle me-1"></i>Damaged Units
                            </span>
                            <h3 class="mb-0 mt-1 fw-bold text-danger">
                                <?= number_format($summary['total_damaged_units'] ?? $summary['damaged_units'] ?? 0) ?> 
                                <span class="fs-6 fw-normal text-muted">pcs</span>
                            </h3>
                            <small class="text-danger d-flex align-items-center mt-1" style="font-size: 11px;">
                                <?= number_format($summary['damaged_skus_count'] ?? 0) ?> SKUs Affected
                            </small>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger p-3 rounded-circle fs-4 flex-shrink-0">
                            <i class="bi bi-exclamation-octagon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Returned to Warehouse -->
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100 rounded-4 transition-card cursor-pointer <?= $currStatus === 'returned' ? 'bg-warning bg-opacity-10' : 'bg-white' ?>" 
                 onclick="quickFilterStatus('returned')" 
                 title="Click to filter products returned to warehouse"
                 style="cursor: pointer;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-warning-emphasis small fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">
                                <i class="bi bi-arrow-return-left me-1"></i>Returned to WH
                            </span>
                            <h3 class="mb-0 mt-1 fw-bold text-warning-emphasis">
                                <?= number_format($summary['total_returned_units'] ?? 0) ?> 
                                <span class="fs-6 fw-normal text-muted">pcs</span>
                            </h3>
                            <small class="text-warning-emphasis d-flex align-items-center mt-1" style="font-size: 11px;">
                                <?= number_format($summary['returned_skus_count'] ?? 0) ?> SKUs Returned
                            </small>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning-emphasis p-3 rounded-circle fs-4 flex-shrink-0">
                            <i class="bi bi-truck"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Customer Exchanged Units -->
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100 rounded-4 transition-card cursor-pointer <?= $currStatus === 'exchanged' ? 'bg-info bg-opacity-10' : 'bg-white' ?>" 
                 onclick="quickFilterStatus('exchanged')" 
                 title="Click to filter products with customer exchanges"
                 style="cursor: pointer;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-info small fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">
                                <i class="bi bi-arrow-repeat me-1"></i>Exchanged Units
                            </span>
                            <h3 class="mb-0 mt-1 fw-bold text-info">
                                <?= number_format($summary['total_exchanged_units'] ?? 0) ?> 
                                <span class="fs-6 fw-normal text-muted">pcs</span>
                            </h3>
                            <small class="text-info d-flex align-items-center mt-1" style="font-size: 11px;">
                                <?= number_format($summary['exchanged_skus_count'] ?? 0) ?> SKUs Exchanged
                            </small>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info p-3 rounded-circle fs-4 flex-shrink-0">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 5: Retail Stock Valuation -->
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 rounded-4 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Stock Valuation</span>
                            <h3 class="mb-0 mt-1 fw-bold text-primary">
                                <?= formatCurrency($summary['total_valuation'] ?? 0) ?>
                            </h3>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                MRP Val: <span class="fw-semibold text-dark"><?= formatCurrency($summary['total_mrp_valuation'] ?? 0) ?></span>
                            </small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary p-3 rounded-circle fs-4 flex-shrink-0">
                            <i class="bi bi-currency-rupee"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 6: Low / Out of Stock Alert -->
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-secondary border-4 h-100 rounded-4 transition-card cursor-pointer <?= $currStatus === 'low_stock' || $currStatus === 'out_of_stock' ? 'bg-secondary bg-opacity-10' : 'bg-white' ?>" 
                 onclick="quickFilterStatus('low_stock')" 
                 title="Click to filter low and depleted stock"
                 style="cursor: pointer;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-secondary small fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">
                                <i class="bi bi-exclamation-triangle me-1"></i>Low / Out Stock
                            </span>
                            <h3 class="mb-0 mt-1 fw-bold text-secondary">
                                <?= number_format(($summary['low_stock_count'] ?? 0) + ($summary['out_of_stock_count'] ?? 0)) ?> 
                                <span class="fs-6 fw-normal text-muted">SKUs</span>
                            </h3>
                            <small class="text-danger d-flex align-items-center mt-1" style="font-size: 11px;">
                                <?= number_format($summary['out_of_stock_count'] ?? 0) ?> Depleted SKUs
                            </small>
                        </div>
                        <div class="stat-icon bg-secondary bg-opacity-10 text-secondary p-3 rounded-circle fs-4 flex-shrink-0">
                            <i class="bi bi-bell"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2.5. INTERACTIVE STOCK ANALYTICS & VISUAL CHARTS SECTION -->
    <div class="row g-3 mb-4">
        <!-- Main Chart: Category Stock & Valuation Breakdown -->
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-white border-0 pt-3 px-4 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                            <i class="bi bi-bar-chart-fill text-primary me-2"></i>Stock by Category
                        </h6>
                        <small class="text-muted" id="categoryChartSubtitle">Available inventory volume across catalog categories</small>
                    </div>
                    <!-- Metric Switcher: Units vs Valuation -->
                    <div class="btn-group btn-group-sm p-1 bg-light rounded-pill border" role="group" id="stockMetricButtonGroup">
                        <button type="button" class="btn btn-sm rounded-pill px-3 active fw-semibold shadow-none" id="btnMetricUnits" onclick="switchStockMetric('units', this)">
                            <i class="bi bi-boxes me-1"></i> Units (pcs)
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold text-secondary shadow-none" id="btnMetricValuation" onclick="switchStockMetric('valuation', this)">
                            <i class="bi bi-currency-rupee me-1"></i> Valuation (₹)
                        </button>
                    </div>
                </div>
                <div class="card-body px-4 pb-4 pt-3">
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="categoryStockChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donut Chart: Stock Health & Status Breakdown -->
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-white border-0 pt-3 px-4 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                            <i class="bi bi-pie-chart-fill text-info me-2"></i>Stock Health Distribution
                        </h6>
                        <small class="text-muted">Proportion of Healthy, Low &amp; Out of Stock SKUs</small>
                    </div>
                    <span class="badge bg-light text-dark border rounded-pill px-2 py-1 font-monospace small">
                        <?= $summary['total_skus'] ?? 0 ?> Total SKUs
                    </span>
                </div>
                <div class="card-body px-4 pb-3 pt-2 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-center" style="position: relative; height: 185px; width: 100%;">
                        <canvas id="stockHealthDonutChart"></canvas>
                    </div>
                    <!-- Health Status Interactive Legend Cards -->
                    <div class="row g-2 mt-2 pt-2 border-top">
                        <div class="col-4">
                            <div class="p-2 rounded-3 text-center bg-success-subtle border border-success-subtle cursor-pointer" onclick="quickFilterStatus('in_stock')" style="cursor: pointer;" title="Filter by Healthy Stock">
                                <span class="d-block small text-success fw-bold" style="font-size: 11px;">Healthy</span>
                                <h6 class="fw-bold text-success mb-0"><?= $summary['in_stock_count'] ?? 0 ?> <span class="fw-normal small" style="font-size: 10px;">SKUs</span></h6>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3 text-center bg-warning-subtle border border-warning-subtle cursor-pointer" onclick="quickFilterStatus('low_stock')" style="cursor: pointer;" title="Filter by Low Stock">
                                <span class="d-block small text-warning-emphasis fw-bold" style="font-size: 11px;">Low Stock</span>
                                <h6 class="fw-bold text-warning-emphasis mb-0"><?= $summary['low_stock_count'] ?? 0 ?> <span class="fw-normal small" style="font-size: 10px;">SKUs</span></h6>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3 text-center bg-danger-subtle border border-danger-subtle cursor-pointer" onclick="quickFilterStatus('out_of_stock')" style="cursor: pointer;" title="Filter by Out of Stock">
                                <span class="d-block small text-danger fw-bold" style="font-size: 11px;">Out of Stock</span>
                                <h6 class="fw-bold text-danger mb-0"><?= $summary['out_of_stock_count'] ?? 0 ?> <span class="fw-normal small" style="font-size: 10px;">SKUs</span></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. QUICK FILTER PILLS & ADVANCED FILTER OPTIONS -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
        <div class="card-body p-3 p-lg-4">
            <!-- Quick Filter Status Pills -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-3 mb-3 border-bottom">
                <div class="d-flex align-items-center gap-2 flex-wrap" id="quickFilterPills">
                    <span class="text-muted small fw-semibold me-1"><i class="bi bi-funnel me-1"></i>Filter By Status:</span>
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold <?= empty($currStatus) ? 'btn-primary' : 'btn-light border text-secondary' ?>" onclick="quickFilterStatus('')">
                        All Items <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $summary['total_skus'] ?? 0 ?></span>
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $currStatus === 'in_stock' ? 'btn-success text-white' : 'btn-light border text-secondary' ?>" onclick="quickFilterStatus('in_stock')">
                        <i class="bi bi-check2-circle me-1"></i> In Stock <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $summary['in_stock_count'] ?? 0 ?></span>
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $currStatus === 'damaged' ? 'btn-danger text-white' : 'btn-light border text-secondary' ?>" onclick="quickFilterStatus('damaged')">
                        <i class="bi bi-slash-circle me-1"></i> Damaged <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $summary['damaged_skus_count'] ?? 0 ?></span>
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $currStatus === 'returned' ? 'btn-warning text-dark' : 'btn-light border text-secondary' ?>" onclick="quickFilterStatus('returned')">
                        <i class="bi bi-arrow-return-left me-1"></i> Returned <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $summary['returned_skus_count'] ?? 0 ?></span>
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $currStatus === 'exchanged' ? 'btn-info text-dark' : 'btn-light border text-secondary' ?>" onclick="quickFilterStatus('exchanged')">
                        <i class="bi bi-arrow-repeat me-1"></i> Exchanged <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $summary['exchanged_skus_count'] ?? 0 ?></span>
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $currStatus === 'low_stock' ? 'btn-warning text-dark' : 'btn-light border text-secondary' ?>" onclick="quickFilterStatus('low_stock')">
                        <i class="bi bi-exclamation-triangle me-1"></i> Low Stock <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $summary['low_stock_count'] ?? 0 ?></span>
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold <?= $currStatus === 'out_of_stock' ? 'btn-danger text-white' : 'btn-light border text-secondary' ?>" onclick="quickFilterStatus('out_of_stock')">
                        <i class="bi bi-x-circle me-1"></i> Out of Stock <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $summary['out_of_stock_count'] ?? 0 ?></span>
                    </button>
                </div>
                
                <?php if (!empty($currSearch) || !empty($currCategory) || !empty($currStatus) || $currSort !== 'name_asc'): ?>
                    <a href="<?= url('shop/inventory') . $shopQuery ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Clear All Filters
                    </a>
                <?php endif; ?>
            </div>

            <!-- Main Filter Form -->
            <form method="GET" action="<?= url('shop/inventory') ?>" id="inventoryFilterForm" class="row g-3 align-items-end">
                <input type="hidden" name="status" id="filterStatusInput" value="<?= e($currStatus) ?>">
                <?php if (!$hasMultipleShops): ?>
                    <input type="hidden" name="shop_id" value="<?= (int)$shop['id'] ?>">
                <?php endif; ?>

                <!-- 1. Search Bar -->
                <div class="<?= $hasMultipleShops ? 'col-lg-3' : 'col-lg-4' ?> col-md-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">
                        <i class="bi bi-search me-1"></i> Search Catalog
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="inventorySearchInput" class="form-control border-start-0 ps-0" 
                               placeholder="Product name, SKU, QR code, style code..." 
                               value="<?= e($currSearch) ?>" 
                               autocomplete="off">
                        <?php if (!empty($currSearch)): ?>
                            <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="clearSearchInput()"><i class="bi bi-x-lg"></i></button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($hasMultipleShops): ?>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">
                        <i class="bi bi-shop me-1"></i> Shop
                    </label>
                    <select name="shop_id" id="inventoryShopSelect" class="form-select">
                        <?php foreach ($shops as $availableShop): ?>
                            <option value="<?= (int)$availableShop['id'] ?>" <?= (int)$shop['id'] === (int)$availableShop['id'] ? 'selected' : '' ?>>
                                <?= e($availableShop['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- 2. Category Filter -->
                <div class="<?= $hasMultipleShops ? 'col-lg-2' : 'col-lg-3' ?> col-md-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">
                        <i class="bi bi-grid me-1"></i> Product Category
                    </label>
                    <select name="category_id" id="inventoryCategorySelect" class="form-select">
                        <option value="">All Categories (<?= count($categories ?? []) ?>)</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['filter_id']) ?>" <?= $currCategory === (string)$cat['filter_id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 3. Sort Filter -->
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">
                        <i class="bi bi-sort-down me-1"></i> Sort By
                    </label>
                    <select name="sort" id="inventorySortSelect" class="form-select">
                        <option value="name_asc" <?= $currSort === 'name_asc' ? 'selected' : '' ?>>Product Name (A &rarr; Z)</option>
                        <option value="name_desc" <?= $currSort === 'name_desc' ? 'selected' : '' ?>>Product Name (Z &rarr; A)</option>
                        <option value="stock_desc" <?= $currSort === 'stock_desc' ? 'selected' : '' ?>>Available Stock (High &rarr; Low)</option>
                        <option value="stock_asc" <?= $currSort === 'stock_asc' ? 'selected' : '' ?>>Available Stock (Low &rarr; High)</option>
                        <option value="price_asc" <?= $currSort === 'price_asc' ? 'selected' : '' ?>>Price (Low &rarr; High)</option>
                        <option value="price_desc" <?= $currSort === 'price_desc' ? 'selected' : '' ?>>Price (High &rarr; Low)</option>
                        <option value="sku_asc" <?= $currSort === 'sku_asc' ? 'selected' : '' ?>>SKU Code (A &rarr; Z)</option>
                    </select>
                </div>

                <!-- 4. Submit & Reset Buttons -->
                <div class="col-lg-2 col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-semibold rounded-3 shadow-sm">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <a href="<?= url('shop/inventory') . $shopQuery ?>" class="btn btn-light border rounded-3 text-secondary px-3" title="Reset Filters">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php $currTab = 'stock'; ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
        <div class="card-header bg-white border-bottom pt-3 pb-0 px-4">
            <ul class="nav nav-tabs card-header-tabs" id="shopInventoryTabs" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link product-type-tab active text-primary" data-product-type="NORMAL">
                        <i class="bi bi-box me-1"></i> Normal
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link product-type-tab" data-product-type="SERIES">
                        <i class="bi bi-boxes me-1"></i> Series
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link product-type-tab" data-product-type="COMPLEMENTARY">
                        <i class="bi bi-gift me-1"></i> Complementary
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <!-- ========================================================================= -->
            <!-- TAB 1: LIVE PRODUCT STOCK BALANCES -->
            <!-- ========================================================================= -->
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-boxes me-2 text-primary"></i>Stock Ledger</h5>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3 py-1 font-monospace" id="tableItemCountBadge">
                            <?= count($inventory) ?> Items Listed
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <small class="text-muted" id="realtimeFilterIndicator"></small>
                        <?php if (hasPermission('shop_stock', 'edit') || hasPermission('shop_stock', 'edit_price')): ?>
                        <button type="button" class="btn btn-outline-success shadow-sm fw-semibold btnTogglePriceEdit" onclick="togglePriceEditMode()">
                            <i class="bi bi-pencil-square me-1"></i> <span class="btnPriceEditLabel">Edit Price</span>
                        </button>
                        <button type="button" class="btn btn-success shadow-sm fw-semibold btnHeaderApplyPrice d-none" onclick="openBulkPriceModal()">
                            <i class="bi bi-check-circle-fill me-1"></i> Change Price (<span class="headerSelectedPriceCount">0</span>)
                        </button>
                        <?php endif; ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                            <i class="bi bi-boxes me-1"></i> <?= number_format($summary['total_available_units'] ?? $summary['total_units'] ?? 0) ?> pcs Available
                        </span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="shopInventoryTable">
                        <thead class="table-light">
                            <tr>
                                <th class="price-checkbox-col" style="display: none; width: 44px; text-align: center;">
                                    <input type="checkbox" class="form-check-input select-all-price-chk" onchange="toggleSelectAllPriceCheckboxes(this.checked)" title="Select all on this page">
                                </th>
                                <th class="ps-4">Product Name</th>
                                <th>SKU / QR Code</th>
                                <th>Category</th>
                                <th>Size &amp; Color</th>
                                <th>Selling Price</th>
                                <th class="text-center" style="min-width: 180px;">Stock Breakdown</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4" style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="shopInventoryTableBody">
                            <?php if (empty($inventory)): ?>
                                <tr id="noRecordsRow">
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <h6 class="fw-bold text-secondary">No Matching Inventory Records Found</h6>
                                        <p class="small text-muted mb-3">No inventory items matched your selected search and filter criteria.</p>
                                        <a href="<?= url('shop/inventory') . $shopQuery ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inventory as $item): ?>
                                    <?php
                                    $qty = (float)($item['quantity'] ?? 0);
                                    $itemDmgQty = (float)($item['damaged_quantity'] ?? 0);
                                    $itemSoldQty = (float)($item['sold_quantity'] ?? 0);
                                    $itemRetQty = (float)($item['returned_quantity'] ?? 0);
                                    $itemExcQty = (float)($item['exchanged_quantity'] ?? 0);

                                    $minStock = (float)($item['min_stock'] ?? 5);
                                    $isLow = ($qty <= $minStock && $qty > 0);
                                    $isOut = ($qty <= 0);
                                    $imgUrl = !empty($item['variant_image']) ? $item['variant_image'] : (!empty($item['product_image']) ? $item['product_image'] : '');
                                    
                                    $statusKey = $isOut ? 'out_of_stock' : ($isLow ? 'low_stock' : 'in_stock');
                                    $catId = (string)($item['category_filter_id'] ?? '');
                                    $searchHaystack = strtolower(($item['product_name'] ?? '') . ' ' . ($item['sku'] ?? '') . ' ' . ($item['product_code'] ?? '') . ' ' . ($item['category_name'] ?? '') . ' ' . ($item['subcategory_name'] ?? '') . ' ' . ($item['size_name'] ?? '') . ' ' . ($item['color_name'] ?? ''));
                                    $warehouseCategoryType = strtoupper(trim((string)($item['warehouse_category_type'] ?? '')));
                                    $variantQrType = strtoupper(trim((string)($item['variant_qr_type'] ?? '')));
                                    $isComplementary = !empty($item['is_complementary']) || $warehouseCategoryType === 'COMPLEMENTARY';
                                    $isSeries = !$isComplementary && (
                                        $warehouseCategoryType === 'SERIES'
                                        || $variantQrType === 'SERIES_BATCH'
                                        || (int)($item['pack_quantity'] ?? 1) > 1
                                    );
                                    $productType = $isComplementary ? 'COMPLEMENTARY' : ($isSeries ? 'SERIES' : 'NORMAL');
                                    $productTypeBadge = match ($productType) {
                                        'COMPLEMENTARY' => 'bg-info-subtle text-info-emphasis border-info-subtle',
                                        'SERIES' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                        default => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                    };
                                    ?>
                                    <tr class="inv-item-row" 
                                        id="inv-row-<?= (int)$item['variant_id'] ?>" 
                                        data-variant-id="<?= (int)$item['variant_id'] ?>"
                                        data-status="<?= $statusKey ?>"
                                        data-category-id="<?= $catId ?>"
                                        data-qty="<?= $qty ?>"
                                        data-damaged="<?= $itemDmgQty ?>"
                                        data-sold="<?= $itemSoldQty ?>"
                                        data-returned="<?= $itemRetQty ?>"
                                        data-exchanged="<?= $itemExcQty ?>"
                                        data-price="<?= (float)$item['selling_price'] ?>"
                                        data-name="<?= e(strtolower($item['product_name'] ?? '')) ?>"
                                        data-search="<?= e($searchHaystack) ?>"
                                        data-product-type="<?= e($productType) ?>">
                                        
                                        <td class="price-checkbox-col text-center" style="display: none;">
                                            <input type="checkbox" class="form-check-input price-item-checkbox" 
                                                   value="<?= (int)$item['variant_id'] ?>" 
                                                   data-sku="<?= htmlspecialchars($item['sku'] ?? '') ?>" 
                                                   data-product-name="<?= htmlspecialchars($item['product_name'] ?? '') ?>" 
                                                   data-selling-price="<?= (float)$item['selling_price'] ?>" 
                                                   data-mrp="<?= (float)($item['mrp'] ?? 0) ?>" 
                                                   data-cost-price="<?= (float)(($item['production_cost'] ?? 0) > 0 ? $item['production_cost'] : ($item['purchase_price'] ?? 0)) ?>" 
                                                   onchange="updatePriceSelectionState()">
                                        </td>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if ($imgUrl): ?>
                                                    <img src="<?= e($imgUrl) ?>" alt="" class="rounded-3 border shadow-sm flex-shrink-0" style="width: 48px; height: 48px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 48px; height: 48px;">
                                                        <i class="bi bi-box-seam fs-4"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold text-dark mb-0"><?= e($item['product_name']) ?></div>
                                                    <small class="text-muted font-monospace"><i class="bi bi-tag me-1"></i><?= e($item['product_code'] ?? $item['sku']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small fw-bold text-dark font-monospace"><?= e($item['sku']) ?></div>
                                            <span class="badge bg-light text-dark border font-monospace mt-1" style="font-size:10px;">
                                                <i class="bi bi-qr-code me-1 text-primary"></i><?= e(!empty($item['qr_code']) ? $item['qr_code'] : $item['sku']) ?>
                                            </span>
                                            <?php if (!empty($item['latest_allocation_number'])): ?>
                                                <div class="mt-1">
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace" style="font-size: 9px;" title="Latest Inward Allocation Batch">
                                                        <i class="bi bi-box-arrow-in-down me-1"></i><?= e($item['latest_allocation_number']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="mt-1">
                                                <span class="badge <?= $productTypeBadge ?> border font-monospace" style="font-size: 9px;" title="Product QR type">
                                                    <?= e($productType) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border fw-semibold"><?= e($item['category_name'] ?? 'General') ?></span>
                                            <?php if (!empty($item['subcategory_name'])): ?>
                                                <small class="text-muted d-block mt-1" style="font-size: 11px;">› <?= e($item['subcategory_name']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark bg-opacity-10 text-dark border px-2 py-1"><?= e($item['size_name'] ?? 'Free') ?></span>
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 ms-1"><?= e($item['color_name'] ?? 'Std') ?></span>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <div>
                                                <span class="fs-6 selling-price-val-<?= (int)$item['variant_id'] ?>"><?= formatCurrency((float)$item['selling_price']) ?></span>
                                                <?php if (!empty($item['mrp']) && (float)$item['mrp'] > (float)$item['selling_price']): ?>
                                                    <small class="text-muted text-decoration-line-through d-block mrp-price-val-<?= (int)$item['variant_id'] ?>" style="font-size: 10px;">MRP <?= formatCurrency((float)$item['mrp']) ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted text-decoration-line-through d-none mrp-price-val-<?= (int)$item['variant_id'] ?>" style="font-size: 10px;"></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <!-- Primary In Stock Available Count -->
                                            <div class="d-flex align-items-center justify-content-center gap-1 mb-1">
                                                <span class="badge bg-success text-white px-2.5 py-1 font-monospace" style="font-size: 11px;" title="Available / Sellable Stock for POS">
                                                    <i class="bi bi-check2-circle me-1"></i><?= $qty ?> In Stock
                                                </span>
                                            </div>
                                            <!-- Secondary Stock Status Tags -->
                                            <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap">
                                                <?php if ($itemSoldQty > 0): ?>
                                                    <a href="<?= url('shop/pos/history') ?>" class="badge bg-primary text-white text-decoration-none px-2 py-0.5 font-monospace shadow-2xs" style="font-size: 9px;" title="Sold / Billed via POS">
                                                        <i class="bi bi-cart-check me-1"></i><?= $itemSoldQty ?> Sold
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($itemDmgQty > 0): ?>
                                                    <a href="<?= url('shop/damage') ?>" class="badge bg-danger text-white text-decoration-none px-2 py-0.5 font-monospace shadow-2xs" style="font-size: 9px;" title="Damaged / Quarantined Units">
                                                        <i class="bi bi-slash-circle me-1"></i><?= $itemDmgQty ?> Damaged
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($itemRetQty > 0): ?>
                                                    <a href="<?= url('shop/return-to-warehouse') ?>" class="badge bg-warning text-dark text-decoration-none px-2 py-0.5 font-monospace shadow-2xs" style="font-size: 9px;" title="Returned to Warehouse">
                                                        <i class="bi bi-arrow-return-left me-1"></i><?= $itemRetQty ?> Returned
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($itemExcQty > 0): ?>
                                                    <a href="<?= url('shop/returns?tab=exchanges') ?>" class="badge bg-info text-dark text-decoration-none px-2 py-0.5 font-monospace shadow-2xs" style="font-size: 9px;" title="Customer Exchanged">
                                                        <i class="bi bi-arrow-repeat me-1"></i><?= $itemExcQty ?> Exchanged
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isOut): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">
                                                    <i class="bi bi-x-circle me-1"></i>Out of Stock
                                                </span>
                                            <?php elseif ($isLow): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>Low Stock
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                                    <i class="bi bi-check2-circle me-1"></i>In Stock
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4 text-nowrap">
                                            <div class="d-inline-flex justify-content-end gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 shadow-sm fw-semibold" 
                                                        onclick="openProductDetailModal(<?= (int)$item['variant_id'] ?>)" 
                                                        title="View Complete Product Details, Images &amp; Piece QR Codes">
                                                    <i class="bi bi-eye me-1"></i> View
                                                </button>
                                            </div>
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

<!-- ========================================================================= -->
<!-- MODAL: PRODUCT DETAILS & ALL PIECE QR CODES VIEW -->
<!-- ========================================================================= -->
<div class="modal fade" id="productDetailModal" tabindex="-1" aria-labelledby="productDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam-fill fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="productDetailModalLabel">Product Details &amp; Piece QR Codes</h5>
                        <small class="text-white-50 font-monospace" id="modalProductSkuCode">-</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light">
                <!-- Loading Spinner -->
                <div id="modalLoadingSpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted small mt-2">Loading detailed product specifications &amp; individual piece QR tags...</p>
                </div>

                <!-- Modal Content Body -->
                <div id="modalContentBody" class="d-none">
                    <!-- 1. Hero Product Banner -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                        <div class="card-body p-3 p-md-4 bg-white">
                            <div class="row g-3 align-items-center">
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                        <span class="badge bg-primary-subtle text-primary border" id="modalCategoryBadge">-</span>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace" id="modalProductTypeBadge">NORMAL</span>
                                        <span class="badge bg-info-subtle text-info-emphasis border" id="modalSourceBadge">-</span>
                                        <span class="badge bg-light text-dark border" id="modalBrandBadge">-</span>
                                    </div>
                                    <h4 class="fw-bold text-dark mb-1" id="modalProductName">-</h4>
                                    <div class="text-muted font-monospace small mb-2">
                                        Style Code: <strong class="text-primary" id="modalProductCode">-</strong>
                                    </div>

                                    <!-- Category Path -->
                                    <div class="small text-muted mb-3" id="modalCategoryPath">
                                        Category: <span class="fw-semibold text-dark">-</span>
                                    </div>

                                    <!-- Key Metrics in Hero -->
                                    <div class="row g-2 text-center">
                                        <div class="col-3">
                                            <div class="p-2 rounded-3 bg-light border position-relative">
                                                <small class="text-muted d-block text-uppercase" style="font-size: 10px;">Selling Price</small>
                                                <div class="fw-bold text-success fs-5" id="modalSellingPrice">₹0.00</div>
                                                <small class="text-muted text-decoration-line-through d-none" id="modalMrpPrice" style="font-size: 10px;"></small>
                                                <?php if (hasPermission('shop_stock', 'edit') || hasPermission('shop_stock', 'edit_price')): ?>
                                                <button type="button" class="btn btn-xs btn-outline-success rounded-pill px-2 py-0 mt-1 fw-semibold shadow-2xs" style="font-size: 10px;" id="modalBtnEditPrice" title="Edit Selling Price & MRP">
                                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="p-2 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25">
                                                <small class="text-success fw-semibold d-block text-uppercase" style="font-size: 10px;">Shop Stock</small>
                                                <div class="fw-bold text-success fs-5" id="modalAvailableStock">0</div>
                                                <small class="text-success" style="font-size: 10px;" id="modalStockStatus">In Stock</small>
                                                <small class="text-danger d-block" style="font-size: 10px;" id="modalDamagedStock">0 Damaged</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="p-2 rounded-3 bg-light border">
                                                <small class="text-muted d-block text-uppercase" style="font-size: 10px;">Size &amp; Color</small>
                                                <div class="fw-bold text-dark fs-6 mt-1" id="modalVariantAttributes">-</div>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="p-2 rounded-3 bg-light border">
                                                <small class="text-muted d-block text-uppercase" style="font-size: 10px;">Inward Ref</small>
                                                <div class="fw-bold text-primary small mt-1 font-monospace text-truncate" id="modalInwardRef">-</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product history tabs -->
                    <ul class="nav nav-tabs flex-nowrap overflow-auto mb-3" id="shopStockHistoryTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active text-nowrap" id="modal-tab-pieces-btn" data-bs-toggle="tab" data-bs-target="#modal-tab-pieces" type="button" role="tab">Piece QR &amp; Tag Codes (<span id="modalPiecesTabCount">0 In Stock / 0 Total</span>)</button></li>
                        <li class="nav-item"><button class="nav-link text-nowrap" id="modal-tab-ledger-btn" data-bs-toggle="tab" data-bs-target="#modal-tab-ledger" type="button" role="tab">Stock Ledger (<span id="modalTxCount">0</span>)</button></li>
                        <li class="nav-item"><button class="nav-link text-nowrap" id="modal-tab-inward-btn" data-bs-toggle="tab" data-bs-target="#modal-tab-inward" type="button" role="tab">Warehouse Inwards (<span id="modalInwardCount">0</span>)</button></li>
                        <li class="nav-item"><button class="nav-link text-nowrap" id="modal-tab-sales-btn" data-bs-toggle="tab" data-bs-target="#modal-tab-sales" type="button" role="tab">Shop POS Sales (<span id="modalSalesCount">0</span>)</button></li>
                        <li class="nav-item"><button class="nav-link text-nowrap" id="modal-tab-returns-btn" data-bs-toggle="tab" data-bs-target="#modal-tab-returns" type="button" role="tab">Returns &amp; Exchanges (<span id="modalReturnsCount">0</span>)</button></li>
                        <li class="nav-item"><button class="nav-link text-nowrap" id="modal-tab-damages-btn" data-bs-toggle="tab" data-bs-target="#modal-tab-damages" type="button" role="tab">Damage &amp; Quarantine (<span id="modalDamagesCount">0</span>)</button></li>
                        <li class="nav-item"><button class="nav-link text-nowrap" id="modal-tab-whreturns-btn" data-bs-toggle="tab" data-bs-target="#modal-tab-whreturns" type="button" role="tab">Warehouse Returns (<span id="modalWhReturnsCount">0</span>)</button></li>
                    </ul>

                    <div class="tab-content" id="shopStockHistoryTabContent">
                        <div class="tab-pane fade show active" id="modal-tab-pieces" role="tabpanel">
                            <div class="card border-0 shadow-sm rounded-4 bg-white">
                                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-grid-3x3-gap-fill text-danger me-2"></i>Piece QR Labels (<span id="modalPiecesTotalCount">0</span> pcs)</h6>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2"><i class="bi bi-check2-circle me-1"></i>Allocated Piece Tags</span>
                                </div>
                                <div class="card-body p-3 bg-light bg-opacity-50">
                                    <div class="row g-3" id="modalIndividualPiecesGrid"></div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="modal-tab-ledger" role="tabpanel">
                            <div class="card border-0 shadow-sm rounded-4 bg-white">
                                <div class="card-header bg-white py-3 border-0"><h6 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Recent Stock Movements at Shop</h6></div>
                                <div class="card-body p-0 table-responsive" style="max-height: 360px;">
                                    <table class="table table-sm table-hover align-middle mb-0 small">
                                        <thead class="table-light"><tr><th class="ps-3">Date</th><th>Type</th><th>Qty</th><th>Balance</th><th class="pe-3">Notes / Ref</th></tr></thead>
                                        <tbody id="modalTransactionsTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="modal-tab-inward" role="tabpanel"><div class="card border-0 shadow-sm rounded-4 bg-white"><div class="card-body p-0 table-responsive"><table class="table table-sm table-hover align-middle mb-0 small"><thead class="table-light"><tr><th class="ps-3">Allocation</th><th>Outward</th><th>Date</th><th>Quantity</th><th>Status</th><th>QR Tags</th></tr></thead><tbody id="modalInwardsTableBody"></tbody></table></div></div></div>
                        <div class="tab-pane fade" id="modal-tab-sales" role="tabpanel"><div class="card border-0 shadow-sm rounded-4 bg-white"><div class="card-body p-0 table-responsive"><table class="table table-sm table-hover align-middle mb-0 small"><thead class="table-light"><tr><th class="ps-3">Invoice</th><th>Date</th><th>Customer</th><th>Quantity</th><th>Scanned QR Tags</th></tr></thead><tbody id="modalSalesTableBody"></tbody></table></div></div></div>
                        <div class="tab-pane fade" id="modal-tab-returns" role="tabpanel"><div class="card border-0 shadow-sm rounded-4 bg-white"><div class="card-body p-0 table-responsive"><table class="table table-sm table-hover align-middle mb-0 small"><thead class="table-light"><tr><th class="ps-3">Return #</th><th>Date</th><th>Type</th><th>Quantity</th><th>Condition / Reason</th><th>Status</th></tr></thead><tbody id="modalReturnsTableBody"></tbody></table></div></div></div>
                        <div class="tab-pane fade" id="modal-tab-damages" role="tabpanel"><div class="card border-0 shadow-sm rounded-4 bg-white"><div class="card-body p-0 table-responsive"><table class="table table-sm table-hover align-middle mb-0 small"><thead class="table-light"><tr><th class="ps-3">Damage #</th><th>Date</th><th>QR Tag</th><th>Quantity</th><th>Reason</th><th>Status</th></tr></thead><tbody id="modalDamagesTableBody"></tbody></table></div></div></div>
                        <div class="tab-pane fade" id="modal-tab-whreturns" role="tabpanel"><div class="card border-0 shadow-sm rounded-4 bg-white"><div class="card-body p-0 table-responsive"><table class="table table-sm table-hover align-middle mb-0 small"><thead class="table-light"><tr><th class="ps-3">Return #</th><th>Date</th><th>Quantity</th><th>Reason</th><th>Status</th></tr></thead><tbody id="modalWhReturnsTableBody"></tbody></table></div></div></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2">
                    <a href="#" id="modalFullDetailLink" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Full Page View
                    </a>
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (hasPermission('shop_stock', 'request') || hasPermission('shop_stock', 'create')): ?>
<!-- Request Stock Modal -->
<div class="modal fade" id="requestStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" action="<?= url('shop/inventory/request') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="shop_id" value="<?= (int)$shop['id'] ?>">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-box-arrow-in-down text-primary me-2"></i>Indent Stock from Central Warehouse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Select items and enter the quantity you wish to request from the central warehouse.</p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Search / Select Item to Indent</label>
                        <select class="form-select" id="indentVariantSelect">
                            <option value="">-- Choose Product Variant --</option>
                            <?php 
                            $allVariants = Database::fetchAll("SELECT pv.id, pv.sku, p.name FROM product_variants pv JOIN products p ON pv.product_id = p.id WHERE pv.is_active = 1 ORDER BY p.name ASC");
                            foreach ($allVariants as $v):
                            ?>
                                <option value="<?= $v['id'] ?>" data-name="<?= e($v['name']) ?>" data-sku="<?= e($v['sku']) ?>"><?= e($v['name']) ?> (<?= e($v['sku']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle" id="indentTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th style="width: 130px;">Request Qty</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="indentBody">
                                <tr id="emptyIndentRow">
                                    <td colspan="3" class="text-center text-muted py-3">Select a product variant above to add to request.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes / Purpose</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Weekend retail replenishment">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4"><i class="bi bi-send me-1"></i> Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (hasPermission('shop_stock', 'edit') || hasPermission('shop_stock', 'edit_price')): ?>
<!-- Floating Bulk Price Action Toolbar (Active when items are selected) -->
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

<!-- ========================================================================= -->
<!-- MODAL: EDIT PRODUCT RETAIL PRICE & MRP -->
<!-- ========================================================================= -->
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
                            <small class="text-muted" style="font-size: 11px;">Active retail selling rate at POS</small>
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
                        <input type="text" name="notes" id="inputPriceNotes" class="form-control" placeholder="e.g. Retail festive promotion, Season discount">
                    </div>
                </div>

                <div class="modal-footer bg-white border-0 py-3 px-4 d-flex justify-content-between">
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

<style>
.transition-card {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.transition-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
}
.cursor-pointer {
    cursor: pointer;
}
.hover-shadow:hover {
    box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
}
</style>

<!-- Load QRCode Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
// Global Client-Side Instant Live Filter Engine
window.quickFilterStatus = function(status) {
    const statusInput = document.getElementById('filterStatusInput');
    if (statusInput) {
        statusInput.value = status;
    }
    // Update Quick Filter Pills active styling
    const pills = document.querySelectorAll('#quickFilterPills button');
    pills.forEach(p => {
        p.className = 'btn btn-sm rounded-pill px-3 fw-semibold btn-light border text-secondary';
    });
    // Update Analysis Cards active styling
    document.querySelectorAll('.transition-card[onclick*="quickFilterStatus"]').forEach(c => {
        c.classList.remove('bg-primary', 'bg-success', 'bg-warning', 'bg-danger', 'bg-opacity-10');
        c.classList.add('bg-white');
    });

    if (window.applyClientFilters) {
        window.applyClientFilters();
    }
};

window.clearSearchInput = function() {
    const sInput = document.getElementById('inventorySearchInput');
    if (sInput) {
        sInput.value = '';
        if (window.applyClientFilters) {
            window.applyClientFilters();
        }
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('inventorySearchInput');
    const categorySelect = document.getElementById('inventoryCategorySelect');
    const sortSelect = document.getElementById('inventorySortSelect');
    const tableBody = document.getElementById('shopInventoryTableBody');
    const countBadge = document.getElementById('tableItemCountBadge');
    const filterIndicator = document.getElementById('realtimeFilterIndicator');
    const rows = Array.from(document.querySelectorAll('.inv-item-row'));

    window.applyClientFilters = function() {
        if (!rows.length) return;

        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const catId = categorySelect ? categorySelect.value : '';
        const statusVal = document.getElementById('filterStatusInput') ? document.getElementById('filterStatusInput').value : '';

        let visibleCount = 0;

        rows.forEach(row => {
            const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();
            const rowCat = row.getAttribute('data-category-id') || '';
            const rowStatus = row.getAttribute('data-status') || '';

            const matchesQuery = !query || rowSearch.includes(query);
            const matchesCat = !catId || rowCat === catId;
            const matchesStatus = !statusVal || rowStatus === statusVal;

            if (matchesQuery && matchesCat && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Apply DOM Sorting if sortSelect exists
        if (sortSelect && tableBody) {
            const sortMode = sortSelect.value || 'name_asc';
            const visibleRows = rows.filter(r => r.style.display !== 'none');
            visibleRows.sort((a, b) => {
                const nameA = (a.getAttribute('data-name') || '').toLowerCase();
                const nameB = (b.getAttribute('data-name') || '').toLowerCase();
                const stockA = parseFloat(a.getAttribute('data-qty') || 0);
                const stockB = parseFloat(b.getAttribute('data-qty') || 0);
                const priceA = parseFloat(a.getAttribute('data-price') || 0);
                const priceB = parseFloat(b.getAttribute('data-price') || 0);

                if (sortMode === 'name_asc') return nameA.localeCompare(nameB);
                if (sortMode === 'name_desc') return nameB.localeCompare(nameA);
                if (sortMode === 'stock_desc') return stockB - stockA;
                if (sortMode === 'stock_asc') return stockA - stockB;
                if (sortMode === 'price_asc') return priceA - priceB;
                if (sortMode === 'price_desc') return priceB - priceA;
                return 0;
            });
            visibleRows.forEach(r => tableBody.appendChild(r));
        }

        // Update item count badge
        if (countBadge) {
            countBadge.textContent = `${visibleCount} Items Listed`;
        }

        if (filterIndicator) {
            if (query || catId || statusVal) {
                filterIndicator.innerHTML = `<span class="badge bg-light text-dark border"><i class="bi bi-funnel-fill text-primary me-1"></i>Showing ${visibleCount} of ${rows.length}</span>`;
            } else {
                filterIndicator.innerHTML = '';
            }
        }

        // Show/hide no results row if all rows hidden
        let noRow = document.getElementById('clientNoRecordsRow');
        if (visibleCount === 0) {
            if (!noRow && tableBody) {
                noRow = document.createElement('tr');
                noRow.id = 'clientNoRecordsRow';
                noRow.innerHTML = `
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <h6 class="fw-bold text-secondary">No Matching Items Found</h6>
                        <p class="small text-muted mb-2">Try adjusting your keyword or clearing filters.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="quickFilterStatus(''); clearSearchInput();">
                            Clear Filters
                        </button>
                    </td>
                `;
                tableBody.appendChild(noRow);
            }
        } else if (noRow) {
            noRow.remove();
        }
    };

    if (searchInput) {
        searchInput.addEventListener('input', applyClientFilters);
        searchInput.addEventListener('keyup', applyClientFilters);
    }
    if (categorySelect) {
        categorySelect.addEventListener('change', applyClientFilters);
    }
    if (sortSelect) {
        sortSelect.addEventListener('change', applyClientFilters);
    }
});

// Open and populate Product Detail Modal dynamically
function openProductDetailModal(variantId) {
    const modalEl = document.getElementById('productDetailModal');
    if (!modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    const spinner = document.getElementById('modalLoadingSpinner');
    const content = document.getElementById('modalContentBody');
    if (spinner) spinner.classList.remove('d-none');
    if (content) content.classList.add('d-none');

    // Fetch dynamic product detail data
    fetch(`<?= url('shop/inventory/ajax-detail') ?>/${variantId}?shop_id=<?= (int)$shop['id'] ?>`)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.item) {
                alert('Product details could not be loaded.');
                modal.hide();
                return;
            }

            const item = data.item;
            const qty = parseFloat(item.quantity) || 0;
            const damagedQty = parseFloat(item.damaged_quantity) || 0;
            const minStock = parseFloat(item.min_stock) || 5;
            const isLow = qty <= minStock && qty > 0;
            const isOut = qty <= 0;
            const mrp = parseFloat(item.mrp) || 0;
            const sellingPrice = parseFloat(item.selling_price) || 0;
            const cost = parseFloat(item.production_cost > 0 ? item.production_cost : (item.purchase_price || 0));

            // Populate Metadata
            document.getElementById('modalProductSkuCode').textContent = `SKU: ${item.sku}`;
            document.getElementById('modalProductName').textContent = item.product_name || 'Retail Product';
            document.getElementById('modalProductCode').textContent = item.product_code || item.sku;
            document.getElementById('modalCategoryBadge').textContent = item.category_name || 'General';
            const warehouseCategoryType = (item.warehouse_category_type || '').toUpperCase().trim();
            const variantQrType = (item.variant_qr_type || '').toUpperCase().trim();
            const isComplementary = Boolean(parseInt(item.is_complementary, 10)) || warehouseCategoryType === 'COMPLEMENTARY';
            const isSeries = !isComplementary && (
                warehouseCategoryType === 'SERIES'
                || variantQrType === 'SERIES_BATCH'
                || (parseInt(item.pack_quantity, 10) || 1) > 1
            );
            const productType = isComplementary ? 'COMPLEMENTARY' : (isSeries ? 'SERIES' : 'NORMAL');
            const productTypeBadge = document.getElementById('modalProductTypeBadge');
            productTypeBadge.textContent = productType;
            productTypeBadge.className = `badge border font-monospace ${
                productType === 'COMPLEMENTARY'
                    ? 'bg-info-subtle text-info-emphasis border-info-subtle'
                    : productType === 'SERIES'
                        ? 'bg-warning-subtle text-warning-emphasis border-warning-subtle'
                        : 'bg-secondary-subtle text-secondary border-secondary-subtle'
            }`;
            document.getElementById('modalSourceBadge').textContent = item.source_type || 'OWN_PRODUCTION';
            document.getElementById('modalBrandBadge').textContent = item.brand_name ? `Brand: ${item.brand_name}` : 'GRM Collection';

            // Category Path
            let catPath = escapeHtml(item.category_name || 'General');
            if (item.subcategory_name) catPath += ` › ${escapeHtml(item.subcategory_name)}`;
            if (item.sub_subcategory_name) catPath += ` › ${escapeHtml(item.sub_subcategory_name)}`;
            document.getElementById('modalCategoryPath').innerHTML = `Category: <strong class="text-dark">${catPath}</strong>`;

            // Inward Ref
            document.getElementById('modalInwardRef').textContent = item.inward_reference || 'Inward Stock';

            // Prices & Stock
            document.getElementById('modalSellingPrice').textContent = `₹${sellingPrice.toFixed(2)}`;
            const mrpEl = document.getElementById('modalMrpPrice');
            if (mrp > sellingPrice) {
                mrpEl.textContent = `MRP: ₹${mrp.toFixed(2)}`;
                mrpEl.classList.remove('d-none');
            } else {
                mrpEl.classList.add('d-none');
            }

            document.getElementById('modalAvailableStock').textContent = `${qty} pcs`;
            document.getElementById('modalDamagedStock').textContent = `${damagedQty} Damaged • ${qty + damagedQty} Total`;
            const stockStatusEl = document.getElementById('modalStockStatus');
            if (isOut) {
                stockStatusEl.textContent = 'Out of Stock';
                stockStatusEl.className = 'badge bg-danger rounded-pill';
            } else if (isLow) {
                stockStatusEl.textContent = 'Low Stock';
                stockStatusEl.className = 'badge bg-warning text-dark rounded-pill';
            } else {
                stockStatusEl.textContent = 'In Stock';
                stockStatusEl.className = 'badge bg-success rounded-pill';
            }

            document.getElementById('modalVariantAttributes').innerHTML = `
                <span class="badge bg-dark px-2 py-1 me-1">${escapeHtml(item.size_name || 'Free')}</span>
                <span class="badge bg-secondary px-2 py-1">${escapeHtml(item.color_name || 'Std')}</span>
            `;

            // RENDER ALL INDIVIDUAL PIECE CARDS
            const piecesGrid = document.getElementById('modalIndividualPiecesGrid');
            const piecesCountEl = document.getElementById('modalPiecesTotalCount');
            const pieces = item.individual_pieces || [];
            
            if (piecesCountEl) piecesCountEl.textContent = pieces.length;
            const availablePieceCount = pieces.filter(piece => {
                const status = (piece.status || '').toUpperCase();
                return status === 'AVAILABLE' || status === 'IN STOCK';
            }).length;
            const damagedPieceCount = pieces.filter(piece => (
                piece.is_damaged || (piece.status || '').toUpperCase() === 'DAMAGED'
            )).length;
            document.getElementById('modalPiecesTabCount').textContent = `${availablePieceCount} In Stock / ${damagedPieceCount} Damaged / ${pieces.length} Total`;

            if (piecesGrid) {
                if (pieces.length === 0) {
                    piecesGrid.innerHTML = `
                        <div class="col-12 text-center py-4 text-muted">
                            <i class="bi bi-qr-code fs-2 d-block mb-1 opacity-50"></i>
                            No individual piece tags recorded for this item.
                        </div>
                    `;
                } else {
                    let phtml = '';
                    pieces.forEach((p, idx) => {
                        const isRestocked = p.is_restocked || (p.status && p.status.toLowerCase() === 'in stock' && p.return_number);
                        const isExchanged = p.is_exchanged || (p.status && p.status.toLowerCase() === 'exchanged');
                        const isSold = (p.is_sold || (p.status && p.status.toLowerCase() === 'sold')) && !isRestocked;
                        const isDamaged = p.is_damaged || (p.status && p.status.toLowerCase() === 'damaged');
                        const isReturned = p.is_returned || (p.status && p.status.toLowerCase() === 'returned');
                        const isUnavailable = (p.status || '').toUpperCase() === 'OUT OF STOCK';

                        let statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle small font-monospace"><i class="bi bi-check2 me-1"></i>In Stock</span>`;
                        let cardBg = 'bg-white';

                        if (isRestocked) {
                            statusBadge = `<span class="badge bg-success text-white border border-success small font-monospace"><i class="bi bi-arrow-repeat me-1"></i>In Stock (Restocked)</span>`;
                            cardBg = 'border-success border-opacity-50 bg-success-subtle bg-opacity-10';
                        } else if (isSold) {
                            statusBadge = `<span class="badge bg-primary text-white border border-primary small font-monospace"><i class="bi bi-cart-check me-1"></i>Sold (Billed)</span>`;
                            cardBg = 'border-primary border-opacity-50 bg-primary-subtle bg-opacity-10';
                        } else if (isDamaged) {
                            statusBadge = `<span class="badge bg-danger text-white border border-danger small font-monospace"><i class="bi bi-slash-circle me-1"></i>Damaged</span>`;
                            cardBg = 'border-danger border-opacity-50 bg-danger-subtle bg-opacity-25';
                        } else if (isReturned) {
                            statusBadge = `<span class="badge bg-warning text-dark border border-warning small font-monospace"><i class="bi bi-arrow-return-left me-1"></i>Returned</span>`;
                            cardBg = 'border-warning border-opacity-50 bg-warning-subtle bg-opacity-25';
                        } else if (isExchanged) {
                            statusBadge = `<span class="badge bg-info text-dark border border-info small font-monospace"><i class="bi bi-arrow-repeat me-1"></i>Exchanged</span>`;
                            cardBg = 'border-info border-opacity-50 bg-info-subtle bg-opacity-25';
                        } else if (isUnavailable) {
                            statusBadge = `<span class="badge bg-secondary text-white border border-secondary small font-monospace"><i class="bi bi-box me-1"></i>Out of Stock</span>`;
                            cardBg = 'border-secondary border-opacity-50 bg-secondary-subtle bg-opacity-25';
                        }

                        const categoryHierarchy = [p.category_name, p.subcategory_name, p.sub_subcategory_name]
                            .filter(Boolean)
                            .map(escapeHtml)
                            .join(' › ');
                        const mrp = parseFloat(p.mrp) || 0;
                        const price = parseFloat(p.selling_price) || 0;
                        let pieceActionHtml = '';
                        if (isSold) {
                            pieceActionHtml = `<a href="${p.sale_id ? `<?= url('shop/pos/invoice/') ?>/${p.sale_id}` : `<?= url('shop/pos/history') ?>`}" class="btn btn-sm btn-primary py-1 px-2" target="_blank" title="View POS Invoice"><i class="bi bi-receipt"></i></a>`;
                        } else if (isDamaged) {
                            pieceActionHtml = `<a href="<?= url('shop/damage') ?>" class="btn btn-sm btn-danger py-1 px-2" title="View in Damage Module"><i class="bi bi-exclamation-octagon"></i></a>`;
                        } else if (isReturned) {
                            pieceActionHtml = `<a href="<?= url('shop/return-to-warehouse') ?>" class="btn btn-sm btn-warning py-1 px-2" title="View Return Consignments"><i class="bi bi-truck"></i></a>`;
                        } else if (isExchanged) {
                            pieceActionHtml = `<a href="<?= url('shop/returns?tab=exchanges') ?>" class="btn btn-sm btn-info py-1 px-2" title="View Returns & Exchanges"><i class="bi bi-arrow-repeat"></i></a>`;
                        }
                        phtml += `
                            <div class="col-md-6 col-xl-4">
                                <div class="card border shadow-sm rounded-3 h-100 ${cardBg} hover-shadow p-2">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.68rem;">
                                            Piece #${p.piece_number} of ${pieces.length}
                                        </span>
                                        ${statusBadge}
                                    </div>

                                    <div class="p-2 border rounded-3 bg-white mb-2">
                                        <div class="d-flex align-items-start gap-2">
                                            <div class="text-center flex-shrink-0" style="width: 80px;">
                                                <div id="piece_qr_canvas_${idx}" class="bg-white p-1 rounded border shadow-sm d-flex align-items-center justify-content-center mx-auto" style="width: 72px; height: 72px;"></div>
                                                <div class="badge bg-dark text-white font-monospace mt-1 px-1 py-1 w-100 text-break" style="font-size: 0.62rem; letter-spacing: 0.2px;" title="${escapeHtml(p.tag_code)}">
                                                    ${escapeHtml(p.tag_code)}
                                                </div>
                                            </div>
                                            <div class="overflow-hidden flex-grow-1" style="font-size: 0.76rem; margin-top: 1px;">
                                                <div class="fw-bold text-uppercase text-dark text-truncate" style="font-size: 0.80rem; letter-spacing: 0.2px;">
                                                    ${escapeHtml(p.brand_name || item.brand_name || 'GRM WAREHOUSE')}
                                                </div>
                                                ${categoryHierarchy ? `
                                                    <div class="mt-1 mb-1">
                                                        <span class="badge bg-light text-primary border font-monospace px-1 py-1 text-truncate d-inline-block mw-100" style="font-size: 0.65rem;" title="${categoryHierarchy}">
                                                            <i class="bi bi-diagram-3-fill me-1 text-warning"></i>${categoryHierarchy}
                                                        </span>
                                                    </div>
                                                ` : ''}
                                                <div class="fw-semibold text-secondary text-truncate mt-1" style="font-size: 0.76rem;">
                                                    ${escapeHtml(p.size_name || 'Free')}-${escapeHtml(p.color_name || 'Std')}
                                                </div>
                                                ${mrp > price ? `
                                                    <div class="text-muted text-decoration-line-through mt-1" style="font-size: 0.68rem;">MRP: ₹${mrp.toFixed(2)}</div>
                                                    <div class="fw-bold text-success mt-1" style="font-size: 0.78rem;">Discount Price: ₹${price.toFixed(2)}</div>
                                                ` : `
                                                    <div class="fw-bold text-success mt-1" style="font-size: 0.78rem;">Discount Price: ₹${(price > 0 ? price : mrp).toFixed(2)}</div>
                                                `}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-1 mt-auto pt-1 border-top">
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="navigator.clipboard.writeText('${escapeHtml(p.tag_code)}'); alert('Copied QR Tag: ${escapeHtml(p.tag_code)}');" title="Copy QR Tag">
                                                <i class="bi bi-copy"></i>
                                            </button>
                                            ${pieceActionHtml}
                                        </div>
                                    </div>

                                    ${isSold && p.invoice_number ? `
                                        <div class="mt-2 text-primary fw-semibold" style="font-size: 10px;">
                                            <i class="bi bi-receipt me-1"></i>Billed: ${escapeHtml(p.invoice_number)}
                                        </div>
                                    ` : ''}
                                    ${isDamaged && p.damage_number ? `
                                        <div class="mt-2 text-danger fw-semibold" style="font-size: 10px;">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Damaged: ${escapeHtml(p.damage_number)}${p.damage_reason ? ' (' + escapeHtml(p.damage_reason) + ')' : ''}
                                        </div>
                                    ` : ''}
                                    ${isReturned && p.return_number ? `
                                        <div class="mt-2 text-warning-emphasis fw-semibold" style="font-size: 10px;">
                                            <i class="bi bi-arrow-return-left me-1"></i>Return: ${escapeHtml(p.return_number)}
                                        </div>
                                    ` : ''}
                                    ${isExchanged && p.exchange_number ? `
                                        <div class="mt-2 text-info fw-semibold" style="font-size: 10px;">
                                            <i class="bi bi-arrow-repeat me-1"></i>Exchange: ${escapeHtml(p.exchange_number)}
                                        </div>
                                    ` : ''}

                                    <div class="mt-2 text-muted text-truncate" style="font-size: 9px;">
                                        <i class="bi bi-truck me-1 text-primary"></i>${escapeHtml(p.inward_reference)}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    piecesGrid.innerHTML = phtml;

                    // Render QR codes for each piece
                    setTimeout(() => {
                        pieces.forEach((p, idx) => {
                            const cEl = document.getElementById(`piece_qr_canvas_${idx}`);
                            if (cEl && typeof QRCode !== 'undefined') {
                                cEl.innerHTML = '';
                                try {
                                    new QRCode(cEl, {
                                        text: p.tag_code,
                                        width: 72,
                                        height: 72,
                                        colorDark: "#0f172a",
                                        colorLight: "#ffffff",
                                        correctLevel: QRCode.CorrectLevel.M
                                    });
                                } catch(e) {}
                            }
                        });
                    }, 50);
                }
            }

            // Transactions Table
            const txBody = document.getElementById('modalTransactionsTableBody');
            const txCountEl = document.getElementById('modalTxCount');
            if (txBody) {
                const txs = item.transactions || [];
                if (txCountEl) txCountEl.textContent = txs.length;
                if (txs.length === 0) {
                    txBody.innerHTML = `<tr><td colspan="5" class="text-center py-3 text-muted">No stock transactions logged yet.</td></tr>`;
                } else {
                    let txHtml = '';
                    txs.forEach(t => {
                        const typeStr = (t.transaction_type || '').toUpperCase();
                        const isCredit = typeStr.includes('INWARD') || typeStr.includes('IN') || typeStr.includes('RETURN') || typeStr.includes('CREDIT') || parseFloat(t.quantity) > 0;
                        const q = Math.abs(parseFloat(t.quantity) || 0);
                        txHtml += `
                            <tr>
                                <td class="ps-3 text-muted font-monospace">${escapeHtml(t.created_at || '')}</td>
                                <td><span class="badge bg-${isCredit ? 'success-subtle text-success border border-success-subtle' : 'danger-subtle text-danger border border-danger-subtle'} font-monospace">${escapeHtml(t.transaction_type)}</span></td>
                                <td class="fw-bold ${isCredit ? 'text-success' : 'text-danger'}">${isCredit ? '+' : '-'}${q}</td>
                                <td class="fw-bold text-dark">${parseFloat(t.balance_after) || 0}</td>
                                <td class="pe-3 text-muted">${escapeHtml(t.notes || t.reference_type || '-')}</td>
                            </tr>
                        `;
                    });
                    txBody.innerHTML = txHtml;
                }
            }

            const renderHistoryRows = (bodyId, countId, records, columnCount, rowRenderer) => {
                const body = document.getElementById(bodyId);
                const count = document.getElementById(countId);
                const rows = Array.isArray(records) ? records : [];
                if (count) count.textContent = rows.length;
                if (body) {
                    body.innerHTML = rows.length
                        ? rows.map(rowRenderer).join('')
                        : `<tr><td colspan="${columnCount}" class="text-center py-4 text-muted">No records found.</td></tr>`;
                }
            };

            const allocationBatches = item.allocation_batches || [];
            renderHistoryRows('modalInwardsTableBody', 'modalInwardCount', allocationBatches, 6, batch => `
                <tr>
                    <td class="ps-3 fw-semibold font-monospace">${escapeHtml(batch.allocation_number || '-')}</td>
                    <td class="font-monospace">${escapeHtml(batch.outward_number || 'DIRECT')}</td>
                    <td>${escapeHtml(batch.alloc_date || '-')}</td>
                    <td>${parseInt(batch.quantity, 10) || 0} pcs</td>
                    <td>${escapeHtml(batch.status || '-')}</td>
                    <td class="font-monospace">${escapeHtml((batch.tags || []).join(', ') || '-')}</td>
                </tr>
            `);

            const sales = item.sales || [];
            renderHistoryRows('modalSalesTableBody', 'modalSalesCount', sales, 5, sale => `
                <tr>
                    <td class="ps-3 fw-semibold font-monospace">${escapeHtml(sale.invoice_number || '-')}</td>
                    <td>${escapeHtml(sale.created_at || '-')}</td>
                    <td>${escapeHtml(sale.customer_name || 'Retail Customer')}</td>
                    <td>${parseInt(sale.quantity, 10) || 0} pcs</td>
                    <td class="font-monospace">${escapeHtml(sale.scanned_qr_codes || '-')}</td>
                </tr>
            `);

            const returnRecords = (item.returns || []).map(record => ({...record, record_type: record.return_type || 'RETURN'}));
            const exchangeRecords = (item.exchanges || []).map(record => ({
                return_number: record.exchange_number,
                created_at: record.exchange_date || record.created_at,
                record_type: 'EXCHANGE',
                quantity: record.quantity || 1,
                item_reason: record.piece_tag || '',
                status: record.exchange_status || record.status
            }));
            const returnAndExchangeRecords = [...returnRecords, ...exchangeRecords];
            renderHistoryRows('modalReturnsTableBody', 'modalReturnsCount', returnAndExchangeRecords, 6, record => `
                <tr>
                    <td class="ps-3 fw-semibold font-monospace">${escapeHtml(record.return_number || '-')}</td>
                    <td>${escapeHtml(record.created_at || '-')}</td>
                    <td>${escapeHtml(record.record_type || '-')}</td>
                    <td>${parseInt(record.quantity, 10) || 0} pcs</td>
                    <td>${escapeHtml(record.item_condition || record.item_reason || record.reason || '-')}</td>
                    <td>${escapeHtml(record.status || '-')}</td>
                </tr>
            `);

            renderHistoryRows('modalDamagesTableBody', 'modalDamagesCount', item.damages || [], 6, damage => `
                <tr>
                    <td class="ps-3 fw-semibold font-monospace">${escapeHtml(damage.damage_number || '-')}</td>
                    <td>${escapeHtml(damage.created_at || '-')}</td>
                    <td class="font-monospace">${escapeHtml(damage.tag_code || '-')}</td>
                    <td>${parseInt(damage.quantity, 10) || 0} pcs</td>
                    <td>${escapeHtml(damage.damage_reason || damage.defect_type || '-')}</td>
                    <td>${escapeHtml(damage.status || '-')}</td>
                </tr>
            `);

            renderHistoryRows('modalWhReturnsTableBody', 'modalWhReturnsCount', item.wh_returns || [], 5, record => `
                <tr>
                    <td class="ps-3 fw-semibold font-monospace">${escapeHtml(record.return_number || '-')}</td>
                    <td>${escapeHtml(record.created_at || '-')}</td>
                    <td>${parseInt(record.returned_qty, 10) || 0} pcs</td>
                    <td>${escapeHtml(record.reason || '-')}</td>
                    <td>${escapeHtml(record.status || '-')}</td>
                </tr>
            `);

            // Connect modal Edit Price button
            const btnEdit = document.getElementById('modalBtnEditPrice');
            if (btnEdit) {
                btnEdit.onclick = function() {
                    openEditPriceModal(item.variant_id || variantId, item.sku, item.product_name, sellingPrice, mrp, cost);
                };
            }

            // Update full page link
            const fullLink = document.getElementById('modalFullDetailLink');
            if (fullLink) {
                fullLink.href = `<?= url('shop/inventory/detail') ?>/${item.variant_id || variantId}?shop_id=<?= (int)$shop['id'] ?>`;
            }

            if (spinner) spinner.classList.add('d-none');
            if (content) content.classList.remove('d-none');
            bootstrap.Tab.getOrCreateInstance(document.getElementById('modal-tab-pieces-btn')).show();
        })
        .catch(err => {
            console.error(err);
            alert('Failed to connect to server for product details.');
            modal.hide();
        });
}

// =========================================================================
// EDIT PRODUCT RETAIL PRICE MODAL & BULK AJAX FUNCTIONS
// =========================================================================
let isPriceEditMode = false;
let currentEditPriceModal = null;

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

function togglePriceEditMode(forceState) {
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
    const form = document.getElementById('editPriceForm');
    const btn = document.getElementById('btnSavePrice');
    const alertBox = document.getElementById('editPriceAlertContainer');
    const sp = parseFloat(document.getElementById('inputSellingPrice').value) || 0;

    if (sp < 0) {
        alertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3">Selling price cannot be negative.</div>`;
        return;
    }

    btn.disabled = true;
    const origBtnHtml = btn.innerHTML;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...`;

    const formData = new FormData(form);
    formData.append('is_ajax', '1');

    fetch('<?= url("shop/inventory/update-price") ?>', {
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
            const row = document.getElementById(`inv-row-${vId}`) || document.querySelector(`.inv-item-row[data-variant-id="${vId}"]`);

            if (rowSellingEl) rowSellingEl.textContent = item.formatted_price;
            if (rowMrpEl) {
                if (item.new_mrp > item.new_selling_price) {
                    rowMrpEl.textContent = `MRP ${item.formatted_mrp}`;
                    rowMrpEl.classList.remove('d-none');
                } else {
                    rowMrpEl.classList.add('d-none');
                }
            }
            if (row) {
                row.setAttribute('data-price', item.new_selling_price);
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

        // Instant UI update in detail modal if open
        const modalSp = document.getElementById('modalSellingPrice');
        const modalMrp = document.getElementById('modalMrpPrice');
        if (res.data) {
            if (modalSp) modalSp.textContent = res.data.formatted_price;
            if (modalMrp) {
                if (res.data.new_mrp > res.data.new_selling_price) {
                    modalMrp.textContent = `MRP: ${res.data.formatted_mrp}`;
                    modalMrp.classList.remove('d-none');
                } else {
                    modalMrp.classList.add('d-none');
                }
            }
        }

        // Clear selection
        clearPriceSelection();

        // Hide Edit Price Modal
        if (currentEditPriceModal) {
            currentEditPriceModal.hide();
        }

        // Show toast notification
        showPriceUpdateToast(res.message || 'Price updated successfully!');
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origBtnHtml;
        alertBox.innerHTML = `<div class="alert alert-danger py-2 small mb-3">Server connection error. Please try again.</div>`;
    });
};

document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('indentVariantSelect');
    const tbody = document.getElementById('indentBody');
    const emptyRow = document.getElementById('emptyIndentRow');

    if (select && tbody) {
        select.addEventListener('change', function() {
            const val = this.value;
            if (!val) return;

            const opt = this.options[this.selectedIndex];
            const name = opt.getAttribute('data-name');
            const sku = opt.getAttribute('data-sku');

            if (document.getElementById(`indent_row_${val}`)) {
                alert('Item already added to request table.');
                this.value = '';
                return;
            }

            if (emptyRow) emptyRow.style.display = 'none';

            const tr = document.createElement('tr');
            tr.id = `indent_row_${val}`;
            tr.innerHTML = `
                <td>
                    <div class="fw-bold text-dark small">${escapeHtml(name)}</div>
                    <small class="text-muted font-monospace">${escapeHtml(sku)}</small>
                </td>
                <td>
                    <input type="number" name="items[${val}]" class="form-control form-control-sm" value="10" min="1" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
            this.value = '';
        });
    }

    // Initialize Stock Analytics Visual Charts
    initStockCharts();
});

// ==========================================
// SHOP INVENTORY VISUAL CHARTS (Chart.js)
// ==========================================
const stockChartData = <?= json_encode($chartsData ?? []) ?>;
let categoryStockChartInstance = null;
let healthDonutChartInstance = null;
let currentStockMetric = 'units'; // 'units' | 'valuation'

function initStockCharts() {
    if (typeof Chart === 'undefined') return;

    // 1. Category Stock & Valuation Chart (Bar Chart)
    const catCanvas = document.getElementById('categoryStockChart');
    if (catCanvas && stockChartData.categories && stockChartData.categories.length > 0) {
        const catCtx = catCanvas.getContext('2d');
        const labels = stockChartData.categories.map(c => c.category);
        const unitsData = stockChartData.categories.map(c => c.units);
        const valuationData = stockChartData.categories.map(c => c.valuation);
        const skuData = stockChartData.categories.map(c => c.sku_count);

        const blueGradient = catCtx.createLinearGradient(0, 0, 0, 260);
        blueGradient.addColorStop(0, 'rgba(59, 130, 246, 0.95)');
        blueGradient.addColorStop(1, 'rgba(37, 99, 235, 0.45)');

        const greenGradient = catCtx.createLinearGradient(0, 0, 0, 260);
        greenGradient.addColorStop(0, 'rgba(16, 185, 129, 0.95)');
        greenGradient.addColorStop(1, 'rgba(5, 150, 105, 0.45)');

        categoryStockChartInstance = new Chart(catCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: currentStockMetric === 'units' ? 'Available Units (pcs)' : 'Stock Valuation (₹)',
                    data: currentStockMetric === 'units' ? unitsData : valuationData,
                    backgroundColor: currentStockMetric === 'units' ? blueGradient : greenGradient,
                    borderColor: currentStockMetric === 'units' ? '#2563eb' : '#059669',
                    borderWidth: 1.5,
                    borderRadius: 8,
                    maxBarThickness: 54,
                    hoverBackgroundColor: currentStockMetric === 'units' ? '#1d4ed8' : '#047857'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.92)',
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const idx = context.dataIndex;
                                const u = unitsData[idx] || 0;
                                const v = valuationData[idx] || 0;
                                const skus = skuData[idx] || 0;
                                if (currentStockMetric === 'units') {
                                    return [
                                        ` Available Units: ${u.toLocaleString()} pcs`,
                                        ` Total Valuation: ₹${v.toLocaleString('en-IN', {minimumFractionDigits: 2})}`,
                                        ` Catalog SKUs: ${skus}`
                                    ];
                                } else {
                                    return [
                                        ` Total Valuation: ₹${v.toLocaleString('en-IN', {minimumFractionDigits: 2})}`,
                                        ` Available Units: ${u.toLocaleString()} pcs`,
                                        ` Catalog SKUs: ${skus}`
                                    ];
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 12, weight: '500' }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.8)', strokeDashArray: [4, 4] },
                        ticks: {
                            font: { size: 11 },
                            color: '#64748b',
                            callback: function(val) {
                                if (currentStockMetric === 'valuation') {
                                    return val >= 1000 ? '₹' + (val / 1000).toFixed(0) + 'k' : '₹' + val;
                                }
                                return val + ' pcs';
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Stock Health Breakdown (Doughnut Chart)
    const donutCanvas = document.getElementById('stockHealthDonutChart');
    if (donutCanvas && stockChartData.health_breakdown) {
        const donutCtx = donutCanvas.getContext('2d');
        const healthyCount = stockChartData.health_breakdown.healthy ? stockChartData.health_breakdown.healthy.count : 0;
        const lowCount = stockChartData.health_breakdown.low ? stockChartData.health_breakdown.low.count : 0;
        const outCount = stockChartData.health_breakdown.out ? stockChartData.health_breakdown.out.count : 0;

        healthDonutChartInstance = new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Healthy (> 5 pcs)', 'Low Stock (1-5 pcs)', 'Out of Stock (0 pcs)'],
                datasets: [{
                    data: [healthyCount, lowCount, outCount],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            borderRadius: 3,
                            usePointStyle: true,
                            font: { size: 11, weight: '500' },
                            padding: 12
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.92)',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const val = context.raw || 0;
                                const total = healthyCount + lowCount + outCount;
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ` ${context.label}: ${val} SKUs (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
}

// Switch between Units (pcs) and Valuation (₹)
window.switchStockMetric = function(metric, btn) {
    currentStockMetric = metric;
    
    document.querySelectorAll('#stockMetricButtonGroup button').forEach(b => {
        b.classList.remove('active', 'btn-primary', 'shadow-sm');
        b.classList.add('text-secondary');
    });
    if (btn) {
        btn.classList.add('active');
        btn.classList.remove('text-secondary');
    }

    const subtitle = document.getElementById('categoryChartSubtitle');
    if (subtitle) {
        subtitle.textContent = metric === 'units' 
            ? 'Available inventory volume across catalog categories' 
            : 'Total commercial valuation (₹) across catalog categories';
    }

    if (categoryStockChartInstance && stockChartData.categories) {
        const catCanvas = document.getElementById('categoryStockChart');
        const catCtx = catCanvas.getContext('2d');
        
        let gradient;
        if (metric === 'units') {
            gradient = catCtx.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.95)');
            gradient.addColorStop(1, 'rgba(37, 99, 235, 0.45)');
        } else {
            gradient = catCtx.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.95)');
            gradient.addColorStop(1, 'rgba(5, 150, 105, 0.45)');
        }

        categoryStockChartInstance.data.datasets[0].label = metric === 'units' ? 'Available Units (pcs)' : 'Stock Valuation (₹)';
        categoryStockChartInstance.data.datasets[0].data = stockChartData.categories.map(c => metric === 'units' ? c.units : c.valuation);
        categoryStockChartInstance.data.datasets[0].backgroundColor = gradient;
        categoryStockChartInstance.data.datasets[0].borderColor = metric === 'units' ? '#2563eb' : '#059669';
        categoryStockChartInstance.data.datasets[0].hoverBackgroundColor = metric === 'units' ? '#1d4ed8' : '#047857';
        categoryStockChartInstance.update();
    }
};

// Quick product-type tabs for the stock ledger.
document.querySelectorAll('.product-type-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const selectedType = this.dataset.productType || 'ALL';
        document.querySelectorAll('.product-type-tab').forEach(item => item.classList.remove('active', 'text-primary'));
        this.classList.add('active', 'text-primary');

        let visibleCount = 0;
        document.querySelectorAll('#shopInventoryTableBody .inv-item-row').forEach(row => {
            const visible = selectedType === 'ALL' || row.dataset.productType === selectedType;
            row.classList.toggle('d-none', !visible);
            if (visible) visibleCount++;
        });

        const countBadge = document.getElementById('tableItemCountBadge');
        if (countBadge) countBadge.textContent = `${visibleCount} Items Listed`;
    });
});
document.querySelector('.product-type-tab[data-product-type="NORMAL"]')?.click();
</script>
