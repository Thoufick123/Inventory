<?php
// Layout initialization & user/unit context
$authUser = currentUser() ?? Session::user() ?? [];
$isSuperAdminUser = isSuperAdmin();
$userRoleName = $authUser['role_name'] ?? ($isSuperAdminUser ? 'Super Admin' : 'Staff');
$userRoleSlug = strtolower($authUser['role_slug'] ?? ($isSuperAdminUser ? 'super-admin' : ''));
$userDept = $authUser['department'] ?? '';
$isAdminRole = $isSuperAdminUser || ($authUser['role_id'] ?? 0) === 1 || str_contains($userRoleSlug, 'admin');
$availableConcerns = [];
$activeConcern = null;
if (!empty($authUser['id']) && class_exists('Concern')) {
    $availableConcerns = Concern::getAccessibleToUser((int)$authUser['id'], $isSuperAdminUser);
    $activeConcern = Concern::getActiveForCurrentUser();
}

// Extract all assigned units defined by admin
$userAssignedUnits = [];
if (!empty($authUser['production_unit_name'])) {
    $userAssignedUnits[] = [
        'type'        => 'Production Unit',
        'short_type'  => 'Unit',
        'name'        => $authUser['production_unit_name'],
        'icon'        => 'bi bi-gear-wide-connected',
        'badge_class' => 'bg-primary text-white'
    ];
}
if (!empty($authUser['warehouse_name'])) {
    $userAssignedUnits[] = [
        'type'        => 'Warehouse',
        'short_type'  => 'Warehouse',
        'name'        => $authUser['warehouse_name'],
        'icon'        => 'bi bi-buildings',
        'badge_class' => 'bg-warning text-dark'
    ];
}
if (!empty($authUser['shop_name'])) {
    $userAssignedUnits[] = [
        'type'        => 'Shop',
        'short_type'  => 'Shop',
        'name'        => $authUser['shop_name'],
        'icon'        => 'bi bi-shop',
        'badge_class' => 'bg-success text-white'
    ];
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> — GRM Inventory</title>
    <meta name="description" content="GRM Inventory Management System">
    <?= CSRF::metaTag() ?>
    <meta name="auth-check-url" content="<?= url('api/auth/check-status') ?>">
    <meta name="login-url" content="<?= url('login') ?>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="<?= asset('css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="<?= asset('css/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= asset('css/style.css') ?>?v=<?= file_exists(BASE_PATH . '/public/assets/css/style.css') ? filemtime(BASE_PATH . '/public/assets/css/style.css') : '1.1' ?>" rel="stylesheet">
    <?php if (($activeModule ?? '') === 'reports'): ?>
    <link href="<?= asset('css/reports.css') ?>?v=<?= file_exists(BASE_PATH . '/public/assets/css/reports.css') ? filemtime(BASE_PATH . '/public/assets/css/reports.css') : '1' ?>" rel="stylesheet">
    <?php endif; ?>
    <!-- Modern Pagination Engine (Preloaded in head so all page scripts have instant access) -->
    <script src="<?= asset('js/pagination.js') ?>?v=<?= file_exists(BASE_PATH . '/public/assets/js/pagination.js') ? filemtime(BASE_PATH . '/public/assets/js/pagination.js') : '1.1' ?>"></script>
</head>
<body>
    <div class="app-wrapper">
        <!-- ============================================ -->
        <!-- SIDEBAR -->
        <!-- ============================================ -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <i class="bi bi-box-seam-fill"></i>
                    <span class="brand-text">GRM Inventory</span>
                </div>
                <button class="sidebar-toggle-btn d-lg-none" id="sidebarClose">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <!-- Admin Dashboard -->
                    <!-- Admin Dashboard -->
                    <?php if (hasPermission('dashboard', 'view')): ?>
                    <li class="nav-item">
                        <a href="<?= url('admin/dashboard') ?>" class="nav-link <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Production -->
                    <?php 
                    $canViewProduction = hasPermission('production', 'view') 
                        || hasPermission('sellers', 'view')
                        || hasPermission('damages', 'view')
                        || hasPermission('catalog', 'view')
                        || hasPermission('programs', 'view')
                        || hasPermission('cutting', 'view')
                        || hasPermission('wastage', 'view')
                        || hasPermission('stitching', 'view')
                        || hasPermission('stitching_wastage', 'view')
                        || hasPermission('production_expenses', 'view')
                        || hasPermission('production_cash', 'view')
                        || hasPermission('labels', 'view')
                        || hasPermission('qc', 'view')
                        || hasPermission('inventory', 'view')
                        || hasPermission('batches', 'view')
                        || hasPermission('outward', 'view')
                        || hasPermission('production_inward', 'view')
                        || hasPermission('production_billing', 'view')
                        || hasPermission('production_billing_returns', 'view')
                        || hasPermission('warehouse_rework', 'view');
                    ?>
                    <?php if ($canViewProduction): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= ($activeModule ?? '') === 'production' ? 'active' : '' ?>">
                            <i class="bi bi-gear-wide-connected"></i>
                            <span>Production</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= ($activeModule ?? '') === 'production' ? 'show' : '' ?>">
                            <?php if (hasPermission('production', 'view')): ?>
                            <li><a href="<?= url('production/dashboard') ?>" class="<?= ($activeMenu ?? '') === 'prod-dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('sellers', 'view')): ?>
                            <li><a href="<?= url('production/sellers') ?>" class="<?= ($activeMenu ?? '') === 'prod-sellers' ? 'active' : '' ?>">Sellers</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('sellers', 'view')): ?>
                            <li><a href="<?= url('production/purchases') ?>" class="<?= ($activeMenu ?? '') === 'prod-purchases' ? 'active' : '' ?>">Purchases</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('production_billing', 'view')): ?>
                            <li><a href="<?= url('production/billing') ?>" class="<?= ($activeMenu ?? '') === 'prod-billing' ? 'active' : '' ?>">Billing</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('production_billing_returns', 'view')): ?>
                            <li><a href="<?= url('production/billing/returns') ?>" class="<?= ($activeMenu ?? '') === 'prod-billing-returns' ? 'active' : '' ?>">Billing Returns &amp; Exchanges</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('damages', 'view')): ?>
                            <li><a href="<?= url('production/damages') ?>" class="<?= ($activeMenu ?? '') === 'prod-damages' ? 'active' : '' ?>">Fabric Roll Damage</a></li>
                            <li><a href="<?= url('production/inventory-damages') ?>" class="<?= ($activeMenu ?? '') === 'prod-inventory-damages' ? 'active' : '' ?>">Finished Goods Damage</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('catalog', 'view')): ?>
                            <li><a href="<?= url('production/catalog') ?>" class="<?= ($activeMenu ?? '') === 'prod-catalog' ? 'active' : '' ?>">Catalog</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('programs', 'view')): ?>
                            <li><a href="<?= url('production/programs') ?>" class="<?= ($activeMenu ?? '') === 'programs' ? 'active' : '' ?>">Programs</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('cutting', 'view')): ?>
                            <li><a href="<?= url('production/cutting') ?>" class="<?= ($activeMenu ?? '') === 'cutting' ? 'active' : '' ?>">Cutting</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('wastage', 'view')): ?>
                            <li><a href="<?= url('production/wastage') ?>" class="<?= ($activeMenu ?? '') === 'wastage-selling' ? 'active' : '' ?>">Wastage Selling</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('stitching', 'view')): ?>
                            <li><a href="<?= url('production/stitching') ?>" class="<?= ($activeMenu ?? '') === 'stitching' ? 'active' : '' ?>">Stitching</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('stitching_wastage', 'view')): ?>
                            <li><a href="<?= url('production/stitching-wastage') ?>" class="<?= ($activeMenu ?? '') === 'stitching-wastage' ? 'active' : '' ?>">Stitching Wastage</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('production_expenses', 'view')): ?>
                            <li><a href="<?= url('production/expenses') ?>" class="<?= ($activeMenu ?? '') === 'production-expenses' ? 'active' : '' ?>">Expenses &amp; Assets</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('production_cash', 'view')): ?>
                            <li><a href="<?= url('production/cash') ?>" class="<?= ($activeMenu ?? '') === 'production-cash' ? 'active' : '' ?>">Cash &amp; Cash Flow</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('labels', 'view')): ?>
                            <li><a href="<?= url('production/labels') ?>" class="<?= ($activeMenu ?? '') === 'labels' ? 'active' : '' ?>">Labels</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('qc', 'view')): ?>
                            <li><a href="<?= url('production/qc') ?>" class="<?= ($activeMenu ?? '') === 'qc' ? 'active' : '' ?>">QC</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('inventory', 'view')): ?>
                            <li><a href="<?= url('production/inventory') ?>" class="<?= ($activeMenu ?? '') === 'prod-inventory' ? 'active' : '' ?>">Inventory</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('production_inward', 'view')): ?>
                            <li><a href="<?= url('production/inward') ?>" class="<?= ($activeMenu ?? '') === 'prod-inward' ? 'active' : '' ?>">Inward / GRN</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('batches', 'view')): ?>
                            <li><a href="<?= url('production/batches') ?>" class="<?= ($activeMenu ?? '') === 'batches' ? 'active' : '' ?>">Batches</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('outward', 'view')): ?>
                            <li><a href="<?= url('production/outward') ?>" class="<?= ($activeMenu ?? '') === 'outward' ? 'active' : '' ?>">Outward</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('warehouse_rework', 'view') || hasPermission('production', 'view')): ?>
                            <li><a href="<?= url('production/warehouse-rework') ?>" class="<?= ($activeMenu ?? '') === 'warehouse-rework' ? 'active' : '' ?>">Rework from Warehouse</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- Warehouse -->
                    <?php 
                    $canViewWarehouse = hasPermission('warehouse', 'view')
                        || hasPermission('inward', 'view')
                        || hasPermission('stock', 'view')
                        || hasPermission('wh_sellers', 'view')
                        || hasPermission('wh_catalog', 'view')
                        || hasPermission('wh_labels', 'view')
                        || hasPermission('purchases', 'view')
                        || hasPermission('purchases_returns', 'view')
                        || hasPermission('allocation', 'view')
                        || hasPermission('wh_batches', 'view')
                        || hasPermission('allocation_outward', 'view')
                        || hasPermission('damage', 'view')
                        || hasPermission('returns', 'view')
                        || hasPermission('shop_requests', 'view')
                        || hasPermission('warehouse_expenses', 'view');
                    ?>
                    <?php if ($canViewWarehouse): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= ($activeModule ?? '') === 'warehouse' ? 'active' : '' ?>">
                            <i class="bi bi-buildings"></i>
                            <span>Warehouse</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= ($activeModule ?? '') === 'warehouse' ? 'show' : '' ?>">
                            <?php if (hasPermission('warehouse', 'view')): ?>
                            <li><a href="<?= url('warehouse/dashboard') ?>" class="<?= ($activeMenu ?? '') === 'wh-dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('inward', 'view')): ?>
                            <li><a href="<?= url('warehouse/inward') ?>" class="<?= in_array(($activeMenu ?? ''), ['inward', 'wh-inward']) ? 'active' : '' ?>">Inward / GRN</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('stock', 'view')): ?>
                            <li><a href="<?= url('warehouse/stock') ?>" class="<?= ($activeMenu ?? '') === 'wh-stock' ? 'active' : '' ?>">Stock</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('wh_catalog', 'view')): ?>
                            <li><a href="<?= url('warehouse/catalog') ?>" class="<?= ($activeMenu ?? '') === 'wh-catalog' ? 'active' : '' ?>">Catalog</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('wh_sellers', 'view')): ?>
                            <li><a href="<?= url('warehouse/sellers') ?>" class="<?= ($activeMenu ?? '') === 'wh-sellers' ? 'active' : '' ?>">Suppliers</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('wh_labels', 'view')): ?>
                            <li><a href="<?= url('warehouse/labels') ?>" class="<?= ($activeMenu ?? '') === 'wh-labels' ? 'active' : '' ?>">QR Labels</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('purchases', 'view')): ?>
                            <li><a href="<?= url('warehouse/purchases') ?>" class="<?= in_array(($activeMenu ?? ''), ['purchases', 'wh-purchases']) ? 'active' : '' ?>">Direct Purchases</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('purchases_returns', 'view')): ?>
                            <li><a href="<?= url('warehouse/purchases/returns') ?>" class="<?= ($activeMenu ?? '') === 'purchases-returns' ? 'active' : '' ?>">Purchases Returns &amp; Exchanges</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('allocation', 'view')): ?>
                            <li><a href="<?= url('warehouse/allocation') ?>" class="<?= in_array(($activeMenu ?? ''), ['allocation', 'wh-allocations']) ? 'active' : '' ?>">Allocation</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('warehouse', 'view')): ?>
                            <li><a href="<?= url('warehouse/transfers') ?>" class="<?= ($activeMenu ?? '') === 'wh-transfers' ? 'active' : '' ?>">Concern Transfers</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('allocation_outward', 'view')): ?>
                            <li><a href="<?= url('warehouse/allocation/outward') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-outward', 'allocation-outward']) ? 'active' : '' ?>">Outward</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('damage', 'view')): ?>
                            <li><a href="<?= url('warehouse/damage') ?>" class="<?= ($activeMenu ?? '') === 'wh-damage' ? 'active' : '' ?>">Damage</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('returns', 'view')): ?>
                            <li><a href="<?= url('warehouse/returns') ?>" class="<?= ($activeMenu ?? '') === 'wh-returns' ? 'active' : '' ?>">Returns</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('shop_requests', 'view')): ?>
                            <li><a href="<?= url('warehouse/shop-requests') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-shop-requests', 'shop-requests']) ? 'active' : '' ?>">Requests</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('warehouse_expenses', 'view')): ?>
                            <li><a href="<?= url('warehouse/expenses') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-expenses', 'warehouse-expenses']) ? 'active' : '' ?>"><i class="bi bi-cash-stack me-1"></i> Warehouse Expenses</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- WhatsApp Orders -->
                    <?php 
                    $canViewWhatsapp = hasPermission('whatsapp_dashboard', 'view')
                        || hasPermission('whatsapp_orders', 'view')
                        || hasPermission('whatsapp_inward', 'view')
                        || hasPermission('whatsapp_inventory', 'view')
                        || hasPermission('whatsapp_damage', 'view')
                        || hasPermission('whatsapp_returns', 'view')
                        || hasPermission('whatsapp_return_to_wh', 'view')
                        || hasPermission('whatsapp_expenses', 'view')
                        || hasPermission('whatsapp_customers', 'view');
                    ?>
                    <?php if ($canViewWhatsapp): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= in_array(($activeModule ?? ''), ['whatsapp', 'whatsapp-orders', 'wh-whatsapp']) ? 'active' : '' ?>">
                            <i class="bi bi-whatsapp"></i>
                            <span>WhatsApp Orders</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= in_array(($activeModule ?? ''), ['whatsapp', 'whatsapp-orders', 'wh-whatsapp']) ? 'show' : '' ?>">
                            <?php if (hasPermission('whatsapp_dashboard', 'view')): ?>
                            <li><a href="<?= url('warehouse/whatsapp-orders') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-whatsapp', 'wh-whatsapp-dashboard', 'whatsapp-dashboard']) ? 'active' : '' ?>">Dashboard</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('whatsapp_inward', 'view')): ?>
                            <li><a href="<?= url('warehouse/whatsapp-orders/inward') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-whatsapp-inward', 'whatsapp-inward']) ? 'active' : '' ?>">Inward / GRN</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('whatsapp_inventory', 'view')): ?>
                            <li><a href="<?= url('warehouse/whatsapp-orders/allocations') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-whatsapp-allocations', 'whatsapp-allocations']) ? 'active' : '' ?>">Inventory</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('whatsapp_orders', 'view')): ?>
                            <li><a href="<?= url('warehouse/whatsapp-orders/history') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-whatsapp-history', 'whatsapp-history', 'wh-whatsapp-orders']) ? 'active' : '' ?>">Orders History</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('whatsapp_damage', 'view')): ?>
                            <li><a href="<?= url('warehouse/whatsapp-orders/damage') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-whatsapp-damage', 'whatsapp-damage']) ? 'active' : '' ?>"><i class="bi bi-exclamation-octagon me-1"></i> Damage Management</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('whatsapp_returns', 'view')): ?>
                            <li><a href="<?= url('warehouse/whatsapp-orders/returns') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-whatsapp-returns', 'whatsapp-returns']) ? 'active' : '' ?>">Returns &amp; Exchanges</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('whatsapp_return_to_wh', 'view')): ?>
                            <li><a href="<?= url('warehouse/whatsapp-orders/return-to-warehouse') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-whatsapp-return-to-wh', 'whatsapp-return-to-wh']) ? 'active' : '' ?>"><i class="bi bi-arrow-return-left me-1"></i> Return to Warehouse</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('whatsapp_expenses', 'view')): ?>
                            <li><a href="<?= url('warehouse/whatsapp-orders/expenses') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-whatsapp-expenses', 'whatsapp-expenses']) ? 'active' : '' ?>"><i class="bi bi-cash-stack me-1"></i> Channel Expenses</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- Website Orders -->
                    <?php 
                    $canViewWebsite = hasPermission('website_dashboard', 'view')
                        || hasPermission('website_orders', 'view')
                        || hasPermission('website_inward', 'view')
                        || hasPermission('website_inventory', 'view')
                        || hasPermission('website_damage', 'view')
                        || hasPermission('website_returns', 'view')
                        || hasPermission('website_return_to_wh', 'view')
                        || hasPermission('website_customers', 'view')
                        || hasPermission('website_expenses', 'view');
                    ?>
                    <?php if ($canViewWebsite): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= in_array(($activeModule ?? ''), ['website', 'website-orders', 'wh-website']) ? 'active' : '' ?>">
                            <i class="bi bi-globe"></i>
                            <span>Website Orders</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= in_array(($activeModule ?? ''), ['website', 'website-orders', 'wh-website']) ? 'show' : '' ?>">
                            <?php if (hasPermission('website_dashboard', 'view')): ?>
                            <li><a href="<?= url('warehouse/website-orders') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-website', 'wh-website-dashboard', 'website-dashboard']) ? 'active' : '' ?>">Dashboard</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('website_inward', 'view')): ?>
                            <li><a href="<?= url('warehouse/website-orders/inward') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-website-inward', 'website-inward']) ? 'active' : '' ?>">Inward / GRN</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('website_inventory', 'view')): ?>
                            <li><a href="<?= url('warehouse/website-orders/allocations') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-website-allocations', 'website-allocations']) ? 'active' : '' ?>">Inventory</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('website_orders', 'view')): ?>
                            <li><a href="<?= url('warehouse/website-orders/history') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-website-history', 'website-history', 'wh-website-orders']) ? 'active' : '' ?>">Orders History</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('website_damage', 'view')): ?>
                            <li><a href="<?= url('warehouse/website-orders/damage') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-website-damage', 'website-damage']) ? 'active' : '' ?>"><i class="bi bi-exclamation-octagon me-1"></i> Damage Management</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('website_returns', 'view')): ?>
                            <li><a href="<?= url('warehouse/website-orders/returns') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-website-returns', 'website-returns']) ? 'active' : '' ?>">Returns &amp; Exchanges</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('website_return_to_wh', 'view')): ?>
                            <li><a href="<?= url('warehouse/website-orders/return-to-warehouse') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-website-return-to-wh', 'website-return-to-wh']) ? 'active' : '' ?>"><i class="bi bi-arrow-return-left me-1"></i> Return to Warehouse</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('website_customers', 'view')): ?>
                            <li><a href="<?= url('warehouse/website-orders/customers') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-website-customers', 'website-customers']) ? 'active' : '' ?>">Customers</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('website_expenses', 'view')): ?>
                            <li><a href="<?= url('warehouse/website-orders/expenses') ?>" class="<?= in_array(($activeMenu ?? ''), ['wh-website-expenses', 'website-expenses']) ? 'active' : '' ?>"><i class="bi bi-cash-stack me-1"></i> Channel Expenses</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- Shop -->
                    <?php 
                    $canViewShop = hasPermission('shop', 'view')
                        || hasPermission('pos', 'view')
                        || hasPermission('shop_cash', 'view')
                        || hasPermission('shop_inward', 'view')
                        || hasPermission('shop_stock', 'view')
                        || hasPermission('shop_damage', 'view')
                        || hasPermission('sales', 'view')
                        || hasPermission('customers', 'view')
                        || hasPermission('shop_returns', 'view')
                        || hasPermission('return_to_warehouse', 'view');
                    ?>
                    <?php if ($canViewShop): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= ($activeModule ?? '') === 'shop' ? 'active' : '' ?>">
                            <i class="bi bi-shop"></i>
                            <span>Shop</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= ($activeModule ?? '') === 'shop' ? 'show' : '' ?>">
                            <?php if (hasPermission('shop', 'view')): ?>
                            <li><a href="<?= url('shop/dashboard') ?>" class="<?= ($activeMenu ?? '') === 'shop-dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('shop_cash', 'view')): ?>
                            <li><a href="<?= url('shop/cash') ?>" class="<?= in_array(($activeMenu ?? ''), ['shop-cash', 'cash', 'cash-flow']) ? 'active' : '' ?>"><i class="bi bi-cash-stack me-1"></i> Cash &amp; Cash Flow</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('shop_inward', 'view')): ?>
                            <li><a href="<?= url('shop/inward') ?>" class="<?= in_array(($activeMenu ?? ''), ['shop-inward', 'inward']) ? 'active' : '' ?>">Inward / GRN</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('shop_stock', 'view')): ?>
                            <li><a href="<?= url('shop/inventory') ?>" class="<?= ($activeMenu ?? '') === 'shop-inventory' ? 'active' : '' ?>">Inventory</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('shop_damage', 'view') || hasPermission('damage', 'view')): ?>
                            <li><a href="<?= url('shop/damage') ?>" class="<?= in_array(($activeMenu ?? ''), ['shop-damage', 'damage']) ? 'active' : '' ?>"><i class="bi bi-exclamation-octagon me-1"></i> Damage Management</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('pos', 'view')): ?>
                            <li><a href="<?= url('shop/pos') ?>" class="<?= in_array(($activeMenu ?? ''), ['shop-pos', 'pos']) ? 'active' : '' ?>">POS / Billing</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('sales', 'view')): ?>
                            <li><a href="<?= url('shop/sales') ?>" class="<?= in_array(($activeMenu ?? ''), ['shop-sales', 'sales']) ? 'active' : '' ?>">Selled Products</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('customers', 'view')): ?>
                            <li><a href="<?= url('shop/customers') ?>" class="<?= in_array(($activeMenu ?? ''), ['shop-customers', 'customers']) ? 'active' : '' ?>"><i class="bi bi-people me-1"></i> Customer Details</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('shop_returns', 'view')): ?>
                            <li><a href="<?= url('shop/returns') ?>" class="<?= in_array(($activeMenu ?? ''), ['shop-returns', 'returns', 'shop-exchanges', 'exchanges']) ? 'active' : '' ?>">Customer Returns &amp; Exchanges</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('return_to_warehouse', 'view')): ?>
                            <li><a href="<?= url('shop/return-to-warehouse') ?>" class="<?= in_array(($activeMenu ?? ''), ['shop-return-to-wh', 'return-to-warehouse']) ? 'active' : '' ?>">Return to Warehouse</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- Assets -->
                    <?php 
                    $canViewAssets = hasPermission('assets', 'view')
                        || hasPermission('asset_scanner', 'view')
                        || hasPermission('asset_transfers', 'view')
                        || hasPermission('asset_maintenance', 'view')
                        || hasPermission('asset_bulk_labels', 'view');
                    ?>
                    <?php if ($canViewAssets): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= ($activeModule ?? '') === 'assets' ? 'active' : '' ?>">
                            <i class="bi bi-cpu"></i>
                            <span>Assets</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= ($activeModule ?? '') === 'assets' ? 'show' : '' ?>">
                            <?php if (hasPermission('assets', 'view')): ?>
                            <li><a href="<?= url('asset/dashboard') ?>" class="<?= ($activeMenu ?? '') === 'asset-dashboard' ? 'active' : '' ?>"><i class="bi bi-bar-chart-fill me-1 text-primary"></i> Overview</a></li>
                            <li><a href="<?= url('asset/list') ?>" class="<?= in_array(($activeMenu ?? ''), ['asset-list', 'asset-create']) ? 'active' : '' ?>"><i class="bi bi-table me-1 text-success"></i> Asset Register</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('asset_scanner', 'view')): ?>
                            <li><a href="<?= url('asset/scanner') ?>" class="<?= ($activeMenu ?? '') === 'asset-scanner' ? 'active' : '' ?>"><i class="bi bi-qr-code-scan me-1 text-info"></i> Scan Assets</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('asset_transfers', 'view')): ?>
                            <li><a href="<?= url('asset/transfers') ?>" class="<?= ($activeMenu ?? '') === 'asset-transfers' ? 'active' : '' ?>"><i class="bi bi-arrow-left-right me-1 text-warning"></i> Transfers</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('asset_maintenance', 'view')): ?>
                            <li><a href="<?= url('asset/maintenance') ?>" class="<?= ($activeMenu ?? '') === 'asset-maintenance' ? 'active' : '' ?>"><i class="bi bi-wrench-adjustable me-1 text-danger"></i> Maintenance</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('asset_bulk_labels', 'view')): ?>
                            <li><a href="<?= url('asset/bulk-labels') ?>"><i class="bi bi-qr-code me-1 text-secondary"></i> Print QR Labels</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- Reports -->
                    <?php 
                    $canViewReports = hasPermission('reports', 'view')
                        || hasPermission('reports_overview', 'view')
                        || hasPermission('reports_profitability', 'view')
                        || hasPermission('reports_sales', 'view')
                        || hasPermission('reports_online', 'view')
                        || hasPermission('reports_website', 'view')
                        || hasPermission('reports_whatsapp', 'view')
                        || hasPermission('reports_production', 'view')
                        || hasPermission('reports_warehouse', 'view')
                        || hasPermission('reports_purchase', 'view')
                        || hasPermission('reports_shop', 'view')
                        || hasPermission('reports_inventory', 'view');
                    ?>
                    <?php if ($canViewReports): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= ($activeModule ?? '') === 'reports' ? 'active' : '' ?>">
                            <i class="bi bi-bar-chart-line"></i>
                            <span>Reports</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= ($activeModule ?? '') === 'reports' ? 'show' : '' ?>">
                            <?php if (hasPermission('reports_overview', 'view') || hasPermission('reports', 'view')): ?>
                            <li><a href="<?= url('reports') ?>" class="<?= in_array(($activeMenu ?? ''), ['reports-overview', 'reports']) ? 'active' : '' ?>"><i class="bi bi-pie-chart me-1"></i> Overview &amp; P&amp;L</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('reports_profitability', 'view') || hasPermission('reports', 'view')): ?>
                            <li><a href="<?= url('reports/profitability') ?>" class="<?= ($activeMenu ?? '') === 'reports-profitability' ? 'active' : '' ?>"><i class="bi bi-cash-coin me-1"></i> Revenue &amp; Loss</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('reports_sales', 'view') || hasPermission('reports', 'view')): ?>
                            <li><a href="<?= url('reports/sales') ?>" class="<?= ($activeMenu ?? '') === 'reports-sales' ? 'active' : '' ?>"><i class="bi bi-cart-check me-1"></i> Multi-Channel Sales</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('reports_online', 'view') || hasPermission('reports', 'view')): ?>
                            <li><a href="<?= url('reports/online') ?>" class="<?= ($activeMenu ?? '') === 'reports-online' ? 'active' : '' ?>"><i class="bi bi-broadcast me-1 text-primary"></i> Online &amp; Digital Reports</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('reports_website', 'view') || hasPermission('reports', 'view')): ?>
                            <li><a href="<?= url('reports/website') ?>" class="<?= ($activeMenu ?? '') === 'reports-website' ? 'active' : '' ?>"><i class="bi bi-globe2 me-1 text-info"></i> Website Reports</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('reports_whatsapp', 'view') || hasPermission('reports', 'view')): ?>
                            <li><a href="<?= url('reports/whatsapp') ?>" class="<?= ($activeMenu ?? '') === 'reports-whatsapp' ? 'active' : '' ?>"><i class="bi bi-whatsapp me-1 text-success"></i> WhatsApp Reports</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('reports_production', 'view') || hasPermission('reports', 'view')): ?>
                            <li><a href="<?= url('reports/production') ?>" class="<?= ($activeMenu ?? '') === 'reports-production' ? 'active' : '' ?>"><i class="bi bi-gear me-1"></i> Production Reports</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('reports_warehouse', 'view') || hasPermission('reports', 'view')): ?>
                            <li><a href="<?= url('reports/warehouse') ?>" class="<?= ($activeMenu ?? '') === 'reports-warehouse' ? 'active' : '' ?>"><i class="bi bi-buildings me-1"></i> Warehouse Reports</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('reports_purchase', 'view') || hasPermission('reports', 'view')): ?>
                            <li><a href="<?= url('reports/purchase') ?>" class="<?= ($activeMenu ?? '') === 'reports-purchase' ? 'active' : '' ?>"><i class="bi bi-truck me-1"></i> Purchase Reports</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('reports_shop', 'view') || hasPermission('reports', 'view')): ?>
                            <li><a href="<?= url('reports/shop') ?>" class="<?= ($activeMenu ?? '') === 'reports-shop' ? 'active' : '' ?>"><i class="bi bi-shop me-1"></i> Shop Reports</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('reports_inventory', 'view') || hasPermission('reports', 'view')): ?>
                            <li><a href="<?= url('reports/inventory') ?>" class="<?= ($activeMenu ?? '') === 'reports-inventory' ? 'active' : '' ?>"><i class="bi bi-boxes me-1"></i> Inventory Reports</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <li class="nav-divider"></li>

                    <!-- Staff -->
                    <?php 
                    $canViewStaff = hasPermission('staff', 'view') || hasPermission('roles', 'view') || hasPermission('staff', 'create');
                    ?>
                    <?php if ($canViewStaff): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= ($activeModule ?? '') === 'staff' ? 'active' : '' ?>">
                            <i class="bi bi-people"></i>
                            <span>Staff</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= ($activeModule ?? '') === 'staff' ? 'show' : '' ?>">
                            <?php if (hasPermission('staff', 'create')): ?>
                            <li><a href="<?= url('admin/staff/create') ?>" class="<?= ($activeMenu ?? '') === 'staff-create' ? 'active' : '' ?>">Add Staff</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('staff', 'view')): ?>
                            <li><a href="<?= url('admin/staff?department=all') ?>" class="<?= in_array(($activeMenu ?? ''), ['staff', 'staff-all', 'staff-production', 'staff-warehouse', 'staff-whatsapp', 'staff-website', 'staff-shop', 'staff-reports']) ? 'active' : '' ?>">Total Staffs</a></li>
                            <li><a href="<?= url('admin/staff/concerns') ?>" class="<?= ($activeMenu ?? '') === 'staff-concerns' ? 'active' : '' ?>">Concerns &amp; Modules</a></li>
                            <?php if (isSuperAdmin()): ?>
                            <li><a href="<?= url('admin/legacy-data') ?>" class="<?= ($activeMenu ?? '') === 'legacy-data' ? 'active' : '' ?>">Legacy Concern Data</a></li>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php if (hasPermission('roles', 'view')): ?>
                            <li><a href="<?= url('admin/roles') ?>" class="<?= ($activeMenu ?? '') === 'roles' ? 'active' : '' ?>">Role Assign / Permissions</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- System -->
                    <?php 
                    $canViewSystem = hasPermission('settings', 'view') 
                        || hasPermission('audit', 'view')
                        || hasPermission('backup', 'view');
                    ?>
                    <?php if ($canViewSystem): ?>
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link <?= ($activeModule ?? '') === 'system' ? 'active' : '' ?>">
                            <i class="bi bi-sliders"></i>
                            <span>System</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="submenu <?= ($activeModule ?? '') === 'system' ? 'show' : '' ?>">
                            <?php if (hasPermission('audit', 'view')): ?>
                            <li><a href="<?= url('system/audit') ?>">Audit Logs</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('settings', 'view')): ?>
                            <li><a href="<?= url('system/settings') ?>">Settings</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('backup', 'view')): ?>
                            <li><a href="<?= url('system/backup') ?>">Backup</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <!-- ============================================ -->
        <!-- MAIN CONTENT -->
        <!-- ============================================ -->
        <main class="main-content <?= ($activeModule ?? '') === 'reports' ? 'reports-content' : '' ?>">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <div class="navbar-left">
                    <button class="sidebar-toggle-btn" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <!-- Breadcrumb -->
                    <?php 
                    $bc = $breadcrumb ?? $breadcrumbs ?? [];
                    if (!empty($bc)): 
                    ?>
                    <nav aria-label="breadcrumb" class="d-none d-md-block">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?= url('admin/dashboard') ?>">Home</a></li>
                            <?php foreach ($bc as $i => $crumb): ?>
                                <?php if (isset($crumb['url']) && $i < count($bc) - 1): ?>
                                    <li class="breadcrumb-item"><a href="<?= $crumb['url'] ?>"><?= e($crumb['label']) ?></a></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active"><?= e($crumb['label']) ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                    <?php endif; ?>
                </div>
                <div class="navbar-right">
                    <?php if ($availableConcerns): ?>
                    <a class="btn btn-sm <?= $activeConcern ? 'btn-outline-primary' : 'btn-warning' ?> me-2" href="<?= url('admin/concern/select') ?>">
                        <i class="bi bi-diagram-3 me-1"></i>
                        <?= $activeConcern ? e($activeConcern['name']) : 'Select Concern' ?>
                    </a>
                    <?php endif; ?>
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="user-dropdown" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <div class="user-info d-none d-md-block">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="user-name fw-semibold"><?= e($authUser['full_name'] ?? $authUser['username'] ?? 'User') ?></span>
                                    <span class="badge <?= $isAdminRole ? 'bg-primary' : 'bg-info text-dark' ?> rounded-pill" style="font-size: 0.6rem; padding: 1px 5px;">
                                        <?= $isAdminRole ? 'ADMIN' : 'STAFF' ?>
                                    </span>
                                </div>
                                <span class="user-role text-muted" style="font-size: 0.72rem;"><?= e($userRoleName) ?></span>
                            </div>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li class="px-3 py-2 border-bottom bg-light">
                                <div class="fw-semibold text-dark text-truncate"><?= e($authUser['full_name'] ?? $authUser['username'] ?? 'User') ?></div>
                                <small class="text-muted d-block"><i class="bi bi-shield me-1"></i>Role: <?= e($userRoleName) ?> (<?= $isAdminRole ? 'Admin' : 'Staff' ?>)</small>
                                <?php if (!empty($userAssignedUnits)): ?>
                                    <?php foreach ($userAssignedUnits as $uItem): ?>
                                    <small class="text-primary d-block text-truncate mt-0.5"><i class="<?= $uItem['icon'] ?> me-1"></i><?= e($uItem['name']) ?></small>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </li>
                            <li><a class="dropdown-item mt-1" href="<?= url('admin/change-password') ?>"><i class="bi bi-key me-2"></i>Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Flash Messages -->
                <?php foreach (['success', 'error', 'warning', 'info'] as $type): ?>
                    <?php $messages = Session::getFlash($type); ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show flash-alert" role="alert">
                            <i class="bi bi-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : ($type === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?> me-2"></i>
                            <?= e($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <!-- Main Content from child view -->
                <?= $content ?? '' ?>
            </div>

            <!-- Footer -->
            <footer class="app-footer">
                <span>© <?= date('Y') ?> GRM Inventory Management. All rights reserved.</span>
                <span>v1.0</span>
            </footer>
        </main>
    </div>

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <!-- Bootstrap 5 JS -->
    <script src="<?= asset('js/bootstrap.bundle.min.js') ?>"></script>
    <script>if (typeof bootstrap === 'undefined') document.write('<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"><\/script>');</script>
    <!-- QRCode Generator JS -->
    <script src="<?= asset('js/qrcode.min.js') ?>"></script>
    <script>if (typeof QRCode === 'undefined') document.write('<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"><\/script>');</script>
    <!-- HTML5 QR / Barcode Scanner JS -->
    <script src="<?= asset('js/html5-qrcode.min.js') ?>"></script>
    <script>if (typeof Html5Qrcode === 'undefined') document.write('<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"><\/script>');</script>
    <!-- Custom JS -->
    <script src="<?= asset('js/app.js') ?>?v=<?= file_exists(BASE_PATH . '/public/assets/js/app.js') ? filemtime(BASE_PATH . '/public/assets/js/app.js') : '1.1' ?>"></script>
    <!-- Page-specific scripts -->
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= asset('js/' . $script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
