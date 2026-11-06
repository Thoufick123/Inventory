<?php
/**
 * Router — URL to Controller Mapping
 * Simple routing system for the inventory management application.
 */

class Router {
    private array $routes = [];

    public function __construct() {
        $this->registerRoutes();
    }

    /**
     * Register a GET route.
     */
    public function get(string $path, string $controller, string $method, array $middleware = []): void {
        $this->routes[] = [
            'method'     => 'GET',
            'path'       => $path,
            'controller' => $controller,
            'action'     => $method,
            'middleware'  => $middleware,
        ];
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, string $controller, string $method, array $middleware = []): void {
        $this->routes[] = [
            'method'     => 'POST',
            'path'       => $path,
            'controller' => $controller,
            'action'     => $method,
            'middleware'  => $middleware,
        ];
    }

    /**
     * Dispatch a request to the appropriate controller.
     */
    public function dispatch(string $url, string $method): void {
        while (str_starts_with($url, 'grm_inventory/')) {
            $url = substr($url, strlen('grm_inventory/'));
        }
        if ($url === 'grm_inventory') {
            $url = '';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = $this->convertToRegex($route['path']);
            if (preg_match($pattern, $url, $matches)) {
                // Remove the full match
                array_shift($matches);

                // Run middleware
                foreach ($route['middleware'] as $mw) {
                    if (is_array($mw)) {
                        $mwClass = $mw[0];
                        $mwParams = array_slice($mw, 1);
                        call_user_func_array([new $mwClass, 'handle'], $mwParams);
                    } else {
                        (new $mw)->handle();
                    }
                }

                // Load and instantiate controller
                $controllerFile = BASE_PATH . '/app/Controllers/' . $route['controller'] . '.php';
                if (!file_exists($controllerFile)) {
                    Response::error(500, "Controller not found: {$route['controller']}");
                    return;
                }
                require_once $controllerFile;

                $controller = new $route['controller']();
                $action = $route['action'];

                if (!method_exists($controller, $action)) {
                    Response::error(500, "Action not found: {$route['controller']}@$action");
                    return;
                }

                call_user_func_array([$controller, $action], $matches);
                return;
            }
        }

        // No route matched
        Response::error(404, "Page not found");
    }

    /**
     * Convert route path with {param} placeholders to regex.
     */
    private function convertToRegex(string $path): string {
        $path = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $path);
        return '#^' . $path . '$#';
    }

    /**
     * Register all application routes.
     */
    private function registerRoutes(): void {
        // ==========================================
        // AUTH ROUTES (no auth middleware)
        // ==========================================
        $this->get('', 'AuthController', 'loginForm');
        $this->get('login', 'AuthController', 'loginForm');
        $this->post('login', 'AuthController', 'login');
        $this->get('logout', 'AuthController', 'logout');
        $this->get('api/auth/check-status', 'AuthController', 'checkSessionStatus');

        // ==========================================
        // ADMIN ROUTES
        // ==========================================
        $this->get('admin/dashboard', 'AdminController', 'dashboard', ['AuthMiddleware']);
        $this->get('admin/staff', 'AdminController', 'staffList', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'view']]);
        $this->get('admin/staff/check-phone', 'AdminController', 'checkPhoneAvailability', ['AuthMiddleware']);
        $this->get('admin/staff/create', 'AdminController', 'createStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'create']]);
        $this->post('admin/staff/store', 'AdminController', 'storeStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'create']]);
        $this->get('admin/staff/view/{id}', 'AdminController', 'viewStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'view']]);
        $this->get('admin/staff/edit/{id}', 'AdminController', 'editStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'edit']]);
        $this->post('admin/staff/update/{id}', 'AdminController', 'updateStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'edit']]);
        $this->post('admin/staff/toggle/{id}', 'AdminController', 'toggleStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'edit']]);
        $this->post('admin/staff/delete/{id}', 'AdminController', 'deleteStaff', ['AuthMiddleware', ['PermissionMiddleware', 'staff', 'delete']]);
        $this->get('admin/roles', 'AdminController', 'roles', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'view']]);
        $this->get('admin/roles/create', 'AdminController', 'createRole', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'create']]);
        $this->post('admin/roles/store', 'AdminController', 'storeRole', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'create']]);
        $this->get('admin/roles/edit/{id}', 'AdminController', 'editRole', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'edit']]);
        $this->post('admin/roles/update/{id}', 'AdminController', 'updateRole', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'edit']]);
        $this->get('admin/roles/permissions/{id}', 'AdminController', 'managePermissions', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'edit']]);
        $this->post('admin/roles/permissions/{id}', 'AdminController', 'savePermissions', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'edit']]);
        $this->post('admin/roles/delete/{id}', 'AdminController', 'deleteRole', ['AuthMiddleware', ['PermissionMiddleware', 'roles', 'delete']]);
        $this->get('admin/audit', 'SystemController', 'auditLogs', ['AuthMiddleware', ['PermissionMiddleware', 'audit', 'view']]);
        $this->get('admin/audit-logs', 'SystemController', 'auditLogs', ['AuthMiddleware', ['PermissionMiddleware', 'audit', 'view']]);
        $this->get('admin/change-password', 'AuthController', 'changePasswordForm', ['AuthMiddleware']);
        $this->post('admin/change-password', 'AuthController', 'changePassword', ['AuthMiddleware']);

        // Production Units Management
        $this->post('admin/production-units/store', 'AdminController', 'storeProductionUnit', ['AuthMiddleware']);
        $this->post('admin/production-units/update/{id}', 'AdminController', 'updateProductionUnit', ['AuthMiddleware']);
        $this->post('admin/production-units/delete/{id}', 'AdminController', 'deleteProductionUnit', ['AuthMiddleware']);
        $this->post('admin/production-units/toggle/{id}', 'AdminController', 'toggleProductionUnit', ['AuthMiddleware']);
        $this->post('api/admin/production-units/quick-add', 'AdminController', 'ajaxAddProductionUnit', ['AuthMiddleware']);

        // Warehouses Management
        $this->post('admin/warehouses/store', 'AdminController', 'storeWarehouse', ['AuthMiddleware']);
        $this->post('admin/warehouses/update/{id}', 'AdminController', 'updateWarehouse', ['AuthMiddleware']);
        $this->post('admin/warehouses/delete/{id}', 'AdminController', 'deleteWarehouse', ['AuthMiddleware']);
        $this->post('admin/warehouses/toggle/{id}', 'AdminController', 'toggleWarehouse', ['AuthMiddleware']);

        // Shops Management
        $this->post('admin/shops/store', 'AdminController', 'storeShop', ['AuthMiddleware']);
        $this->post('admin/shops/update/{id}', 'AdminController', 'updateShop', ['AuthMiddleware']);
        $this->post('admin/shops/delete/{id}', 'AdminController', 'deleteShop', ['AuthMiddleware']);
        $this->post('admin/shops/toggle/{id}', 'AdminController', 'toggleShop', ['AuthMiddleware']);

        $this->post('admin/channels/store', 'AdminController', 'storeChannel', ['AuthMiddleware']);
        $this->post('admin/channels/update/{id}', 'AdminController', 'updateChannel', ['AuthMiddleware']);
        $this->post('admin/channels/delete/{id}', 'AdminController', 'deleteChannel', ['AuthMiddleware']);
        $this->post('admin/channels/toggle/{id}', 'AdminController', 'toggleChannel', ['AuthMiddleware']);

        // ==========================================
        // MASTER DATA ROUTES
        // ==========================================
        // Categories
        $this->get('master/categories', 'MasterController', 'categories', ['AuthMiddleware']);
        $this->get('master/categories/create', 'MasterController', 'createCategory', ['AuthMiddleware']);
        $this->post('master/categories/store', 'MasterController', 'storeCategory', ['AuthMiddleware']);
        $this->get('master/categories/edit/{id}', 'MasterController', 'editCategory', ['AuthMiddleware']);
        $this->post('master/categories/update/{id}', 'MasterController', 'updateCategory', ['AuthMiddleware']);
        $this->post('master/categories/delete/{id}', 'MasterController', 'deleteCategory', ['AuthMiddleware']);

        // Subcategories
        $this->get('master/subcategories', 'MasterController', 'subcategories', ['AuthMiddleware']);
        $this->get('master/subcategories/create', 'MasterController', 'createSubcategory', ['AuthMiddleware']);
        $this->post('master/subcategories/store', 'MasterController', 'storeSubcategory', ['AuthMiddleware']);
        $this->get('master/subcategories/edit/{id}', 'MasterController', 'editSubcategory', ['AuthMiddleware']);
        $this->post('master/subcategories/update/{id}', 'MasterController', 'updateSubcategory', ['AuthMiddleware']);

        // Products
        $this->get('master/products', 'MasterController', 'products', ['AuthMiddleware']);
        $this->get('master/products/create', 'MasterController', 'createProduct', ['AuthMiddleware']);
        $this->post('master/products/store', 'MasterController', 'storeProduct', ['AuthMiddleware']);
        $this->get('master/products/edit/{id}', 'MasterController', 'editProduct', ['AuthMiddleware']);
        $this->post('master/products/update/{id}', 'MasterController', 'updateProduct', ['AuthMiddleware']);
        $this->get('master/products/view/{id}', 'MasterController', 'viewProduct', ['AuthMiddleware']);
        $this->post('master/products/delete/{id}', 'MasterController', 'deleteProduct', ['AuthMiddleware']);

        // Product Variants
        $this->get('master/products/{id}/variants', 'MasterController', 'variants', ['AuthMiddleware']);
        $this->post('master/products/{id}/variants/store', 'MasterController', 'storeVariant', ['AuthMiddleware']);
        $this->post('master/variants/update/{id}', 'MasterController', 'updateVariant', ['AuthMiddleware']);
        $this->post('master/variants/delete/{id}', 'MasterController', 'deleteVariant', ['AuthMiddleware']);

        // Brands
        $this->get('master/brands', 'MasterController', 'brands', ['AuthMiddleware']);
        $this->post('master/brands/store', 'MasterController', 'storeBrand', ['AuthMiddleware']);
        $this->post('master/brands/update/{id}', 'MasterController', 'updateBrand', ['AuthMiddleware']);

        // Sizes
        $this->get('master/sizes', 'MasterController', 'sizes', ['AuthMiddleware']);
        $this->post('master/sizes/store', 'MasterController', 'storeSize', ['AuthMiddleware']);
        $this->post('master/sizes/update/{id}', 'MasterController', 'updateSize', ['AuthMiddleware']);

        // Colors
        $this->get('master/colors', 'MasterController', 'colors', ['AuthMiddleware']);
        $this->post('master/colors/store', 'MasterController', 'storeColor', ['AuthMiddleware']);
        $this->post('master/colors/update/{id}', 'MasterController', 'updateColor', ['AuthMiddleware']);

        // Sellers
        $this->get('master/sellers', 'MasterController', 'sellers', ['AuthMiddleware']);
        $this->get('master/sellers/create', 'MasterController', 'createSeller', ['AuthMiddleware']);
        $this->post('master/sellers/store', 'MasterController', 'storeSeller', ['AuthMiddleware']);
        $this->get('master/sellers/edit/{id}', 'MasterController', 'editSeller', ['AuthMiddleware']);
        $this->post('master/sellers/update/{id}', 'MasterController', 'updateSeller', ['AuthMiddleware']);

        // Customers
        $this->get('master/customers', 'MasterController', 'customers', ['AuthMiddleware']);
        $this->get('master/customers/create', 'MasterController', 'createCustomer', ['AuthMiddleware']);
        $this->post('master/customers/store', 'MasterController', 'storeCustomer', ['AuthMiddleware']);
        $this->get('master/customers/edit/{id}', 'MasterController', 'editCustomer', ['AuthMiddleware']);
        $this->post('master/customers/update/{id}', 'MasterController', 'updateCustomer', ['AuthMiddleware']);

        // Locations (Warehouse racks/shelves/bins)
        $this->get('master/locations', 'MasterController', 'locations', ['AuthMiddleware']);
        $this->post('master/locations/store', 'MasterController', 'storeLocation', ['AuthMiddleware']);
        $this->post('master/locations/update/{id}', 'MasterController', 'updateLocation', ['AuthMiddleware']);

        // Units
        $this->get('master/units', 'MasterController', 'units', ['AuthMiddleware']);
        $this->post('master/units/store', 'MasterController', 'storeUnit', ['AuthMiddleware']);
        $this->post('master/units/update/{id}', 'MasterController', 'updateUnit', ['AuthMiddleware']);

        // AJAX Endpoints for master data & cascading
        $this->get('api/categories', 'ProductionController', 'ajaxCategories', ['AuthMiddleware']);
        $this->post('api/categories/quick-add', 'ProductionController', 'ajaxQuickAddCategory', ['AuthMiddleware']);
        $this->post('api/categories/update/{id}', 'ProductionController', 'ajaxUpdateCategory', ['AuthMiddleware']);
        $this->post('api/categories/delete/{id}', 'ProductionController', 'ajaxDeleteCategory', ['AuthMiddleware']);
        $this->get('api/subcategories/{category_id}', 'ProductionController', 'ajaxSubcategories', ['AuthMiddleware']);
        $this->post('api/subcategories/quick-add', 'ProductionController', 'ajaxQuickAddSubcategory', ['AuthMiddleware']);
        $this->post('api/subcategories/update/{id}', 'ProductionController', 'ajaxUpdateSubcategory', ['AuthMiddleware']);
        $this->post('api/subcategories/delete/{id}', 'ProductionController', 'ajaxDeleteSubcategory', ['AuthMiddleware']);
        $this->get('api/sub-subcategories/{subcategory_id}', 'ProductionController', 'ajaxSubSubcategories', ['AuthMiddleware']);
        $this->post('api/sub-subcategories/quick-add', 'ProductionController', 'ajaxQuickAddSubSubcategory', ['AuthMiddleware']);
        $this->post('api/sub-subcategories/update/{id}', 'ProductionController', 'ajaxUpdateSubSubcategory', ['AuthMiddleware']);
        $this->post('api/sub-subcategories/delete/{id}', 'ProductionController', 'ajaxDeleteSubSubcategory', ['AuthMiddleware']);

        // Warehouse Category Routes
        $this->get('api/warehouse-categories', 'WarehouseController', 'ajaxCategories', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'view']]);
        $this->post('api/warehouse-categories/quick-add', 'WarehouseController', 'ajaxQuickAddCategory', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'create']]);
        $this->post('api/warehouse-categories/update/{id}', 'WarehouseController', 'ajaxUpdateCategory', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'edit']]);
        $this->post('api/warehouse-categories/delete/{id}', 'WarehouseController', 'ajaxDeleteCategory', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'delete']]);
        $this->get('api/warehouse-subcategories/{category_id}', 'WarehouseController', 'ajaxSubcategories', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'view']]);
        $this->post('api/warehouse-subcategories/quick-add', 'WarehouseController', 'ajaxQuickAddSubcategory', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'create']]);
        $this->post('api/warehouse-subcategories/update/{id}', 'WarehouseController', 'ajaxUpdateSubcategory', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'edit']]);
        $this->post('api/warehouse-subcategories/delete/{id}', 'WarehouseController', 'ajaxDeleteSubcategory', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'delete']]);
        $this->get('api/warehouse-sub-subcategories/{subcategory_id}', 'WarehouseController', 'ajaxSubSubcategories', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'view']]);
        $this->post('api/warehouse-sub-subcategories/quick-add', 'WarehouseController', 'ajaxQuickAddSubSubcategory', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'create']]);
        $this->post('api/warehouse-sub-subcategories/update/{id}', 'WarehouseController', 'ajaxUpdateSubSubcategory', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'edit']]);
        $this->post('api/warehouse-sub-subcategories/delete/{id}', 'WarehouseController', 'ajaxDeleteSubSubcategory', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'delete']]);
        $this->post('api/brands/quick-add', 'ProductionController', 'ajaxQuickAddBrand', ['AuthMiddleware']);
        $this->post('api/sizes/quick-add', 'ProductionController', 'ajaxQuickAddSize', ['AuthMiddleware']);
        $this->post('api/sizes/delete/{id}', 'ProductionController', 'ajaxDeleteSize', ['AuthMiddleware']);
        $this->post('api/colors/quick-add', 'ProductionController', 'ajaxQuickAddColor', ['AuthMiddleware']);
        $this->post('api/colors/delete/{id}', 'ProductionController', 'ajaxDeleteColor', ['AuthMiddleware']);
        $this->get('api/products/search', 'ProductionController', 'searchProducts', ['AuthMiddleware']);
        $this->get('api/variants/search', 'ProductionController', 'searchVariants', ['AuthMiddleware']);
        $this->get('api/stock/check/{variant_id}', 'ProductionController', 'checkStock', ['AuthMiddleware']);
        $this->get('api/programs/{id}/details', 'ProductionController', 'ajaxProgramDetails', ['AuthMiddleware']);
        $this->get('api/cutting/{id}/details', 'ProductionController', 'ajaxCuttingDetails', ['AuthMiddleware']);
        $this->get('api/stitching/{id}/details', 'ProductionController', 'ajaxStitchingDetails', ['AuthMiddleware']);
        $this->get('api/catalog/variants', 'ProductionController', 'ajaxCatalogVariants', ['AuthMiddleware']);
        $this->get('api/catalog/check-qr-code', 'ProductionController', 'ajaxCheckQRCode', ['AuthMiddleware']);
        $this->post('api/warehouse/quick-add', 'WarehouseController', 'ajaxAddWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse', 'create']]);
        $this->post('api/warehouse/store', 'WarehouseController', 'ajaxAddWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse', 'create']]);
        $this->get('api/warehouse/get/{id}', 'WarehouseController', 'ajaxGetWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse', 'view']]);
        $this->post('api/warehouse/update', 'WarehouseController', 'ajaxUpdateWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse', 'edit']]);
        $this->post('api/warehouse/update/{id}', 'WarehouseController', 'ajaxUpdateWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse', 'edit']]);
        $this->post('api/warehouse/delete/{id}', 'WarehouseController', 'ajaxDeleteWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse', 'delete']]);
        $this->post('api/shops/quick-add', 'WarehouseController', 'ajaxAddShop', ['AuthMiddleware']);
        $this->post('api/shop/store', 'WarehouseController', 'ajaxAddShop', ['AuthMiddleware']);
        $this->get('api/shops/get/{id}', 'WarehouseController', 'ajaxGetShop', ['AuthMiddleware']);
        $this->get('api/shop/get/{id}', 'WarehouseController', 'ajaxGetShop', ['AuthMiddleware']);
        $this->post('api/shops/update', 'WarehouseController', 'ajaxUpdateShop', ['AuthMiddleware']);
        $this->post('api/shop/update', 'WarehouseController', 'ajaxUpdateShop', ['AuthMiddleware']);
        $this->post('api/shops/update/{id}', 'WarehouseController', 'ajaxUpdateShop', ['AuthMiddleware']);
        $this->post('api/shop/update/{id}', 'WarehouseController', 'ajaxUpdateShop', ['AuthMiddleware']);
        $this->post('api/shops/delete/{id}', 'WarehouseController', 'ajaxDeleteShop', ['AuthMiddleware']);
        $this->post('api/shop/delete/{id}', 'WarehouseController', 'ajaxDeleteShop', ['AuthMiddleware']);
        $this->post('api/channels/quick-add', 'WarehouseController', 'ajaxAddChannel', ['AuthMiddleware']);
        $this->post('api/sales-channel/store', 'WarehouseController', 'ajaxAddChannel', ['AuthMiddleware']);
        $this->get('api/channels/get/{id}', 'WarehouseController', 'ajaxGetChannel', ['AuthMiddleware']);
        $this->get('api/sales-channel/get/{id}', 'WarehouseController', 'ajaxGetChannel', ['AuthMiddleware']);
        $this->post('api/channels/update', 'WarehouseController', 'ajaxUpdateChannel', ['AuthMiddleware']);
        $this->post('api/sales-channel/update', 'WarehouseController', 'ajaxUpdateChannel', ['AuthMiddleware']);
        $this->post('api/channels/update/{id}', 'WarehouseController', 'ajaxUpdateChannel', ['AuthMiddleware']);
        $this->post('api/sales-channel/update/{id}', 'WarehouseController', 'ajaxUpdateChannel', ['AuthMiddleware']);
        $this->post('api/channels/delete/{id}', 'WarehouseController', 'ajaxDeleteChannel', ['AuthMiddleware']);
        $this->post('api/sales-channel/delete/{id}', 'WarehouseController', 'ajaxDeleteChannel', ['AuthMiddleware']);
        $this->get('api/warehouse/variant-stock-hierarchy', 'WarehouseController', 'ajaxVariantStockHierarchy', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'create']]);
        $this->post('api/warehouse/variant-stock-hierarchy', 'WarehouseController', 'ajaxVariantStockHierarchy', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'create']]);
        $this->get('api/warehouse/scan-allocation-qr', 'WarehouseController', 'ajaxScanAllocationQr', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'create']]);
        $this->post('api/warehouse/scan-allocation-qr', 'WarehouseController', 'ajaxScanAllocationQr', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'create']]);
        $this->get('api/warehouse/purchases/lookup-scan', 'WarehouseController', 'ajaxScanPurchaseQr', ['AuthMiddleware']);
        $this->post('api/warehouse/purchases/lookup-scan', 'WarehouseController', 'ajaxScanPurchaseQr', ['AuthMiddleware']);
        $this->get('api/warehouse/customers/search', 'WarehouseController', 'ajaxSearchCustomers', ['AuthMiddleware']);
        $this->post('api/warehouse/customers/search', 'WarehouseController', 'ajaxSearchCustomers', ['AuthMiddleware']);
        $this->get('api/warehouse/customers/purchase-history', 'WarehouseController', 'ajaxCustomerPurchaseHistory', ['AuthMiddleware']);
        $this->post('api/warehouse/customers/purchase-history', 'WarehouseController', 'ajaxCustomerPurchaseHistory', ['AuthMiddleware']);
        $this->get('api/warehouse/customers/{id}/purchase-history', 'WarehouseController', 'ajaxCustomerPurchaseHistory', ['AuthMiddleware']);
        $this->post('api/warehouse/customers/{id}/purchase-history', 'WarehouseController', 'ajaxCustomerPurchaseHistory', ['AuthMiddleware']);
        $this->get('api/warehouse/warehouse-variant-stock', 'WarehouseController', 'ajaxGetWarehouseStock', ['AuthMiddleware']);
        $this->post('api/warehouse/warehouse-variant-stock', 'WarehouseController', 'ajaxGetWarehouseStock', ['AuthMiddleware']);

        // ==========================================
        // PRODUCTION ROUTES
        // ==========================================
        $this->get('production/dashboard', 'ProductionController', 'dashboard', ['AuthMiddleware', ['PermissionMiddleware', 'production', 'view']]);
        
        // Production Sellers
        $this->get('production/sellers', 'ProductionController', 'sellerList', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('production/sellers/create', 'ProductionController', 'createSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'create']]);
        $this->post('production/sellers/store', 'ProductionController', 'storeSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'create']]);
        $this->get('production/sellers/view/{id}', 'ProductionController', 'viewSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('production/sellers/edit/{id}', 'ProductionController', 'editSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'edit']]);
        $this->post('production/sellers/update/{id}', 'ProductionController', 'updateSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'edit']]);
        $this->post('production/sellers/delete/{id}', 'ProductionController', 'deleteSeller', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'delete']]);
        $this->post('production/sellers/delete-roll/{id}', 'ProductionController', 'deleteSellerRoll', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'delete']]);
        $this->get('production/sellers/download-bill/{id}', 'ProductionController', 'downloadBill', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('production/sellers/download-roll-photo/{id}', 'ProductionController', 'downloadRollPhoto', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('production/sellers/{id}/labels', 'ProductionController', 'printSellerLabels', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('production/sellers/roll-labels/{id}', 'ProductionController', 'printSellerRollLabel', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->post('production/sellers/add-extra-label/{id}', 'ProductionController', 'addExtraRollLabel', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'edit']]);
        $this->post('production/sellers/delete-extra-label/{id}', 'ProductionController', 'deleteExtraRollLabel', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'delete']]);
        $this->get('api/sellers/{id}/roll-labels-ajax', 'ProductionController', 'ajaxSellerRollLabels', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);
        $this->get('api/sellers/next-roll-codes', 'ProductionController', 'ajaxNextFabricRollCodes', ['AuthMiddleware']);
        $this->get('api/sellers/{id}/rolls', 'ProductionController', 'ajaxSellerRolls', ['AuthMiddleware', ['PermissionMiddleware', 'sellers', 'view']]);

        // Production Fabric & Roll Damages
        $this->get('production/damages', 'ProductionController', 'damageList', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'view']]);
        $this->get('api/production/damages/scan-roll', 'ProductionController', 'ajaxLookupDamageRoll', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'create']]);
        $this->post('production/damages/reasons/store', 'ProductionController', 'storeDamageReason', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'create']]);
        $this->post('production/damages/store', 'ProductionController', 'storeDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'create']]);
        $this->get('production/damages/view/{id}', 'ProductionController', 'viewDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'view']]);
        $this->post('production/damages/update/{id}', 'ProductionController', 'updateDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'edit']]);
        $this->post('production/damages/resolve/{id}', 'ProductionController', 'resolveDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'edit']]);
        $this->post('production/damages/delete/{id}', 'ProductionController', 'deleteDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damages', 'delete']]);

        // Production Catalog
        $this->get('production/catalog', 'ProductionController', 'catalogList', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'view']]);
        $this->get('production/catalog/create', 'ProductionController', 'createCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'create']]);
        $this->post('production/catalog/store', 'ProductionController', 'storeCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'create']]);
        $this->get('production/catalog/view/{id}', 'ProductionController', 'viewCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'view']]);
        $this->get('production/catalog/edit/{id}', 'ProductionController', 'editCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'edit']]);
        $this->post('production/catalog/update/{id}', 'ProductionController', 'updateCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'edit']]);
        $this->post('production/catalog/delete/{id}', 'ProductionController', 'deleteCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'catalog', 'delete']]);

        // Production Programs
        $this->get('production/programs', 'ProductionController', 'programList', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'view']]);
        $this->get('production/programs/create', 'ProductionController', 'createProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'create']]);
        $this->post('production/programs/store', 'ProductionController', 'storeProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'create']]);
        $this->get('production/programs/edit/{id}', 'ProductionController', 'editProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'edit']]);
        $this->post('production/programs/update/{id}', 'ProductionController', 'updateProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'edit']]);
        $this->get('production/programs/view/{id}', 'ProductionController', 'viewProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'view']]);
        $this->post('production/programs/approve/{id}', 'ProductionController', 'approveProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'approve']]);
        $this->post('production/programs/status/{id}', 'ProductionController', 'updateProgramStatus', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'edit']]);
        $this->post('production/programs/delete/{id}', 'ProductionController', 'deleteProgram', ['AuthMiddleware', ['PermissionMiddleware', 'programs', 'delete']]);

        // Cutting
        $this->get('production/cutting', 'ProductionController', 'cuttingList', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'view']]);
        $this->get('production/cutting/view/{id}', 'ProductionController', 'viewCuttingBatch', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'view']]);
        $this->get('production/cutting/create', 'ProductionController', 'createCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'create']]);
        $this->post('production/cutting/store', 'ProductionController', 'storeCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'create']]);
        $this->get('production/cutting/edit/{id}', 'ProductionController', 'editCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'edit']]);
        $this->post('production/cutting/update/{id}', 'ProductionController', 'updateCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'edit']]);
        $this->post('production/cutting/delete/{id}', 'ProductionController', 'deleteCutting', ['AuthMiddleware', ['PermissionMiddleware', 'cutting', 'delete']]);
        $this->get('api/programs/{id}/details', 'ProductionController', 'ajaxProgramDetails', ['AuthMiddleware']);

        // Wastage Selling
        $this->get('production/wastage', 'ProductionController', 'wastageList', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'view']]);
        $this->post('production/wastage/store', 'ProductionController', 'storeWastage', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'create']]);
        $this->post('production/wastage/update/{id}', 'ProductionController', 'updateWastage', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'edit']]);
        $this->post('production/wastage/delete/{id}', 'ProductionController', 'deleteWastage', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'delete']]);
        $this->get('production/wastage/receipt/{id}', 'ProductionController', 'wastageReceipt', ['AuthMiddleware', ['PermissionMiddleware', 'wastage', 'view']]);

        // Stitching
        $this->get('production/stitching', 'ProductionController', 'stitchingList', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'view']]);
        $this->get('production/stitching/view/{id}', 'ProductionController', 'viewStitchingBatch', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'view']]);
        $this->get('production/stitching/create', 'ProductionController', 'createStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'create']]);
        $this->post('production/stitching/store', 'ProductionController', 'storeStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'create']]);
        $this->get('production/stitching/edit/{id}', 'ProductionController', 'editStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'edit']]);
        $this->post('production/stitching/update/{id}', 'ProductionController', 'updateStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'edit']]);
        $this->post('production/stitching/delete/{id}', 'ProductionController', 'deleteStitching', ['AuthMiddleware', ['PermissionMiddleware', 'stitching', 'delete']]);

        // Stitching Wastage Selling
        $this->get('production/stitching-wastage', 'ProductionController', 'stitchingWastageList', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'view']]);
        $this->post('production/stitching-wastage/store', 'ProductionController', 'storeStitchingWastage', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'create']]);
        $this->post('production/stitching-wastage/update/{id}', 'ProductionController', 'updateStitchingWastage', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'edit']]);
        $this->post('production/stitching-wastage/delete/{id}', 'ProductionController', 'deleteStitchingWastage', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'delete']]);
        $this->get('production/stitching-wastage/receipt/{id}', 'ProductionController', 'stitchingWastageReceipt', ['AuthMiddleware', ['PermissionMiddleware', 'stitching_wastage', 'view']]);

        // Production Expenses & Assets Management (Unit 1 & Unit 2)
        $this->get('production/expenses', 'ProductionController', 'expensesList', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'view']]);
        $this->post('production/expenses/store', 'ProductionController', 'storeExpense', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'create']]);
        $this->post('production/expenses/update/{id}', 'ProductionController', 'updateExpense', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'edit']]);
        $this->post('production/expenses/delete/{id}', 'ProductionController', 'deleteExpense', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'delete']]);
        $this->get('production/expenses/receipt/{id}', 'ProductionController', 'expenseReceipt', ['AuthMiddleware', ['PermissionMiddleware', 'production_expenses', 'view']]);
        $this->post('api/production/expenses/quick-add-item', 'ProductionController', 'ajaxQuickAddExpenseItem', ['AuthMiddleware']);
        $this->get('api/production/expenses/items', 'ProductionController', 'ajaxGetExpenseItems', ['AuthMiddleware']);

        // Label
        $this->get('production/labels', 'ProductionController', 'labelList', ['AuthMiddleware', ['PermissionMiddleware', 'labels', 'view']]);
        $this->get('production/labels/view/{id}', 'ProductionController', 'viewLabelBatch', ['AuthMiddleware', ['PermissionMiddleware', 'labels', 'view']]);
        $this->get('production/labels/create', 'ProductionController', 'createLabel', ['AuthMiddleware', ['PermissionMiddleware', 'labels', 'create']]);
        $this->post('production/labels/store', 'ProductionController', 'storeLabel', ['AuthMiddleware', ['PermissionMiddleware', 'labels', 'create']]);
        $this->get('production/labels/edit/{id}', 'ProductionController', 'editLabel', ['AuthMiddleware', ['PermissionMiddleware', 'labels', 'edit']]);
        $this->post('production/labels/update/{id}', 'ProductionController', 'updateLabel', ['AuthMiddleware', ['PermissionMiddleware', 'labels', 'edit']]);
        $this->post('production/labels/delete/{id}', 'ProductionController', 'deleteLabel', ['AuthMiddleware', ['PermissionMiddleware', 'labels', 'delete']]);
        $this->get('api/production/labels/deleted-codes', 'ProductionController', 'ajaxGetDeletedLabelCodes', ['AuthMiddleware']);
        $this->post('api/production/labels/delete-code', 'ProductionController', 'ajaxDeleteLabelCode', ['AuthMiddleware']);

        // QC
        $this->get('production/qc', 'ProductionController', 'qcList', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'view']]);
        $this->get('production/qc/view/{id}', 'ProductionController', 'viewQCBatch', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'view']]);
        $this->get('production/qc/create', 'ProductionController', 'createQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'create']]);
        $this->post('production/qc/store', 'ProductionController', 'storeQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'create']]);
        $this->get('production/qc/inspect/{id}', 'ProductionController', 'inspectQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'edit']]);
        $this->post('production/qc/update/{id}', 'ProductionController', 'updateQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'edit']]);
        $this->post('production/qc/save-ajax/{id}', 'ProductionController', 'saveQCAjax', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'edit']]);
        $this->post('production/qc/delete/{id}', 'ProductionController', 'deleteQC', ['AuthMiddleware', ['PermissionMiddleware', 'qc', 'delete']]);

        // Production Inventory (Post-QC Passed Inventory)
        $this->get('production/inventory', 'ProductionController', 'productionInventory', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'view']]);
        $this->get('production/inventory/batch/{id}', 'ProductionController', 'productionInventoryBatch', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'view']]);
        $this->post('production/inventory/delete-piece', 'ProductionController', 'deleteProductionInventoryPiece', ['AuthMiddleware', ['PermissionMiddleware', 'inventory', 'delete']]);

        // Batch & Outward
        $this->get('production/batches', 'ProductionController', 'batchList', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'view']]);
        $this->get('production/batches/create', 'ProductionController', 'createBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'create']]);
        $this->post('production/batches/store', 'ProductionController', 'storeBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'create']]);
        $this->post('production/batches/approve/{id}', 'ProductionController', 'approveBatch', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'approve']]);
        $this->get('production/batches/label/{id}', 'ProductionController', 'printBatchQRLabel', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'print']]);
        $this->get('production/batches/view-ajax/{id}', 'ProductionController', 'batchDetailsAjax', ['AuthMiddleware', ['PermissionMiddleware', 'batches', 'view']]);
        $this->get('production/outward', 'ProductionController', 'outwardList', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'view']]);
        $this->post('production/outward/store', 'ProductionController', 'storeOutward', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'create']]);
        $this->post('production/outward/dispatch/{id}', 'ProductionController', 'dispatchOutward', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'create']]);
        $this->get('production/outward/view-ajax/{id}', 'ProductionController', 'outwardDetailsAjax', ['AuthMiddleware', ['PermissionMiddleware', 'outward', 'view']]);

        // Rework from Warehouse
        $this->get('production/warehouse-rework', 'ProductionController', 'warehouseReworkList', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'view']]);
        $this->post('production/warehouse-rework/store', 'ProductionController', 'storeWarehouseRework', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'create']]);
        $this->post('production/warehouse-rework/status/{id}', 'ProductionController', 'updateWarehouseReworkStatus', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'edit']]);
        $this->post('production/warehouse-rework/complete/{id}', 'ProductionController', 'completeWarehouseRework', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'edit']]);
        $this->post('production/warehouse-rework/redispatch/{id}', 'ProductionController', 'redispatchWarehouseRework', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'dispatch']]);
        $this->get('production/warehouse-rework/view-ajax/{id}', 'ProductionController', 'viewWarehouseReworkAjax', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'view']]);
        $this->post('production/warehouse-rework/delete/{id}', 'ProductionController', 'deleteWarehouseRework', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'delete']]);
        $this->get('production/warehouse-rework/lookup-batch/{batchId}', 'ProductionController', 'lookupBatchVariantsAjax', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_rework', 'view']]);

        // Production Reports
        $this->get('production/reports', 'ReportController', 'productionReports', ['AuthMiddleware', ['PermissionMiddleware', 'reports', 'view']]);

        // ==========================================
        // WAREHOUSE ROUTES
        // ==========================================
        $this->get('warehouse/dashboard', 'WarehouseController', 'dashboard', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse', 'view']]);

        // Inward/GRN & QR Scanner
        $this->get('warehouse/inward', 'WarehouseController', 'inwardList', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->get('warehouse/inward/create', 'WarehouseController', 'createInward', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'create']]);
        $this->post('warehouse/inward/store', 'WarehouseController', 'storeInward', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'create']]);
        $this->get('warehouse/inward/view/{id}', 'WarehouseController', 'viewInward', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->post('warehouse/inward/verify/{id}', 'WarehouseController', 'verifyInward', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->get('warehouse/inward/label/{id}', 'WarehouseController', 'printBatchQRLabel', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'print']]);
        $this->post('api/warehouse/inward/scan', 'WarehouseController', 'ajaxScanInward', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'create']]);
        $this->post('api/warehouse/inward/crosscheck-scan', 'WarehouseController', 'ajaxCrossCheckScan', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'verify']]);
        $this->get('api/warehouse/inward/lookup/{code}', 'WarehouseController', 'ajaxLookupBatch', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->get('api/warehouse/inward/batch-history/{id}', 'WarehouseController', 'ajaxBatchHistory', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);
        $this->get('api/batches/history/{id}', 'WarehouseController', 'ajaxBatchHistory', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'view']]);

        // Stock
        $this->get('warehouse/stock', 'WarehouseController', 'stockList', ['AuthMiddleware', ['PermissionMiddleware', 'stock', 'view']]);
        $this->get('warehouse/stock/detail/{id}', 'WarehouseController', 'stockDetail', ['AuthMiddleware', ['PermissionMiddleware', 'stock', 'view']]);
        $this->post('warehouse/stock/regenerate-qr/{id}', 'WarehouseController', 'regenerateStockQR', ['AuthMiddleware', ['PermissionMiddleware', 'wh_labels', 'print']]);
        $this->post('warehouse/stock/quick-inward', 'WarehouseController', 'storeQuickInward', ['AuthMiddleware', ['PermissionMiddleware', 'inward', 'create']]);
        $this->post('warehouse/stock/update-price', 'WarehouseController', 'ajaxUpdateProductPrice', ['AuthMiddleware', ['PermissionMiddleware', 'stock', 'edit']]);
        $this->post('api/warehouse/stock/update-price', 'WarehouseController', 'ajaxUpdateProductPrice', ['AuthMiddleware', ['PermissionMiddleware', 'stock', 'edit']]);

        // Damage Management
        $this->get('warehouse/damage', 'WarehouseController', 'damageList', ['AuthMiddleware', ['PermissionMiddleware', 'damage', 'view']]);
        $this->get('warehouse/damage/create', 'WarehouseController', 'createDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damage', 'create']]);
        $this->post('warehouse/damage/store', 'WarehouseController', 'storeDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damage', 'create']]);
        $this->get('warehouse/damage/view/{id}', 'WarehouseController', 'viewDamage', ['AuthMiddleware', ['PermissionMiddleware', 'damage', 'view']]);
        $this->post('warehouse/damage/update-status/{id}', 'WarehouseController', 'updateDamageStatus', ['AuthMiddleware', ['PermissionMiddleware', 'damage', 'resolve']]);
        $this->get('api/warehouse/damage/lookup-scan', 'WarehouseController', 'ajaxLookupDamageProduct', ['AuthMiddleware', ['PermissionMiddleware', 'damage', 'view']]);
        $this->post('api/warehouse/damage/lookup-scan', 'WarehouseController', 'ajaxLookupDamageProduct', ['AuthMiddleware', ['PermissionMiddleware', 'damage', 'view']]);

        // Allocation
        $this->get('warehouse/allocation', 'WarehouseController', 'allocationList', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'view']]);
        $this->get('warehouse/allocation/create', 'WarehouseController', 'createAllocation', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'create']]);
        $this->post('warehouse/allocation/store', 'WarehouseController', 'storeAllocation', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'create']]);
        $this->get('warehouse/allocation/view/{id}', 'WarehouseController', 'viewAllocation', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'view']]);
        $this->post('warehouse/allocation/approve/{id}', 'WarehouseController', 'approveAllocation', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'approve']]);
        $this->post('warehouse/allocation/pick/{id}', 'WarehouseController', 'pickAllocation', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'pick']]);
        $this->post('warehouse/allocation/pack/{id}', 'WarehouseController', 'packAllocation', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'pack']]);
        $this->post('warehouse/allocation/dispatch/{id}', 'WarehouseController', 'dispatchAllocation', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'dispatch']]);
        $this->get('warehouse/allocation/outward', 'WarehouseController', 'allocationOutwardList', ['AuthMiddleware', ['PermissionMiddleware', 'allocation_outward', 'view']]);
        $this->post('warehouse/allocation/outward/store', 'WarehouseController', 'storeAllocationOutward', ['AuthMiddleware', ['PermissionMiddleware', 'allocation_outward', 'create']]);
        $this->post('warehouse/allocation/outward/mark-delivered/{id}', 'WarehouseController', 'markOutwardDelivered', ['AuthMiddleware', ['PermissionMiddleware', 'allocation_outward', 'deliver']]);
        $this->get('warehouse/allocation/gatepass/{id}', 'WarehouseController', 'printAllocationGatePass', ['AuthMiddleware', ['PermissionMiddleware', 'allocation_outward', 'gatepass']]);
        $this->get('api/warehouse/scan-allocation-qr', 'WarehouseController', 'ajaxScanAllocationQr', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'view']]);
        $this->post('api/warehouse/scan-allocation-qr', 'WarehouseController', 'ajaxScanAllocationQr', ['AuthMiddleware', ['PermissionMiddleware', 'allocation', 'view']]);
        $this->get('api/warehouse/variant-stock-hierarchy', 'WarehouseController', 'ajaxVariantStockHierarchy', ['AuthMiddleware', ['PermissionMiddleware', 'stock', 'view']]);

        // Warehouse Catalog (Products & Third-Party Styles)
        $this->get('warehouse/catalog', 'WarehouseController', 'catalogList', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'view']]);
        $this->get('warehouse/catalog/create', 'WarehouseController', 'createCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'create']]);
        $this->post('warehouse/catalog/store', 'WarehouseController', 'storeCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'create']]);
        $this->get('warehouse/catalog/view/{id}', 'WarehouseController', 'viewCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'view']]);
        $this->get('warehouse/catalog/edit/{id}', 'WarehouseController', 'editCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'edit']]);
        $this->post('warehouse/catalog/update/{id}', 'WarehouseController', 'updateCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'edit']]);
        $this->post('warehouse/catalog/delete/{id}', 'WarehouseController', 'deleteCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'delete']]);

        // Warehouse Catalog Category & Attributes (AJAX)
        $this->get('api/warehouse/catalog/category-tree', 'WarehouseController', 'ajaxGetCategoryTree', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'view']]);
        $this->get('api/warehouse/catalog/attributes/{category_id}', 'WarehouseController', 'ajaxGetCategoryAttributes', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'view']]);
        $this->post('api/warehouse/catalog/attributes/save', 'WarehouseController', 'ajaxSaveCategoryAttributes', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'edit']]);
        $this->get('api/warehouse/catalog/default-products/{ssc_id}', 'WarehouseController', 'ajaxGetDefaultProducts', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'view']]);
        $this->post('api/warehouse/catalog/default-products/save', 'WarehouseController', 'ajaxSaveDefaultProducts', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'edit']]);
        $this->get('api/warehouse/catalog/products-by-category', 'WarehouseController', 'ajaxGetProductsByCategory', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'view']]);
        $this->post('api/warehouse/catalog/create-full-hierarchy', 'WarehouseController', 'ajaxCreateFullHierarchy', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'create']]);
        $this->post('api/warehouse/catalog/update-full-hierarchy', 'WarehouseController', 'ajaxUpdateFullHierarchy', ['AuthMiddleware', ['PermissionMiddleware', 'wh_catalog', 'edit']]);

        // Warehouse Sellers & Third-Party Purchases
        $this->get('warehouse/sellers', 'WarehouseController', 'sellerList', ['AuthMiddleware', ['PermissionMiddleware', 'wh_sellers', 'view']]);
        $this->get('warehouse/sellers/create', 'WarehouseController', 'createSeller', ['AuthMiddleware', ['PermissionMiddleware', 'wh_sellers', 'create']]);
        $this->post('warehouse/sellers/store', 'WarehouseController', 'storeSeller', ['AuthMiddleware', ['PermissionMiddleware', 'wh_sellers', 'create']]);
        $this->post('warehouse/sellers/store-phase1', 'WarehouseController', 'storeSellerPhase1', ['AuthMiddleware', ['PermissionMiddleware', 'wh_sellers', 'create']]);
        $this->get('warehouse/sellers/{id}/add-products', 'WarehouseController', 'sellerAddProducts', ['AuthMiddleware', ['PermissionMiddleware', 'wh_sellers', 'create']]);
        $this->post('warehouse/sellers/{id}/store-products', 'WarehouseController', 'storeSellerProducts', ['AuthMiddleware', ['PermissionMiddleware', 'wh_sellers', 'create']]);
        $this->get('warehouse/sellers/buy', 'WarehouseController', 'createThirdPartyPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'purchases', 'create']]);
        $this->post('warehouse/sellers/store-buy', 'WarehouseController', 'storeThirdPartyPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'purchases', 'create']]);
        $this->get('warehouse/sellers/view/{id}', 'WarehouseController', 'viewSeller', ['AuthMiddleware', ['PermissionMiddleware', 'wh_sellers', 'view']]);
        $this->get('warehouse/sellers/edit/{id}', 'WarehouseController', 'editSeller', ['AuthMiddleware', ['PermissionMiddleware', 'wh_sellers', 'edit']]);
        $this->post('warehouse/sellers/update/{id}', 'WarehouseController', 'updateSeller', ['AuthMiddleware', ['PermissionMiddleware', 'wh_sellers', 'edit']]);
        $this->post('warehouse/sellers/delete/{id}', 'WarehouseController', 'deleteSeller', ['AuthMiddleware', ['PermissionMiddleware', 'wh_sellers', 'delete']]);
        $this->post('warehouse/sellers/inward-bill/{id}', 'WarehouseController', 'inwardSellerBill', ['AuthMiddleware', ['PermissionMiddleware', 'purchases', 'receive']]);
        $this->post('api/warehouse/sellers/quick-add', 'WarehouseController', 'ajaxQuickAddSeller', ['AuthMiddleware', ['PermissionMiddleware', 'wh_sellers', 'create']]);
        $this->get('api/warehouse/seller-catalog', 'WarehouseController', 'ajaxSellerCatalog', ['AuthMiddleware', ['PermissionMiddleware', 'wh_sellers', 'view']]);

        // Direct Customer Purchases & Customer Orders
        $this->get('warehouse/purchases', 'WarehouseController', 'purchaseList', ['AuthMiddleware', ['PermissionMiddleware', 'purchases', 'view']]);
        $this->get('warehouse/purchases/create', 'WarehouseController', 'createPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'purchases', 'create']]);
        $this->post('warehouse/purchases/store', 'WarehouseController', 'storePurchase', ['AuthMiddleware', ['PermissionMiddleware', 'purchases', 'create']]);
        $this->get('warehouse/purchases/edit/{id}', 'WarehouseController', 'editPurchase', ['AuthMiddleware', ['PermissionMiddleware', 'purchases', 'edit']]);
        $this->post('warehouse/purchases/update/{id}', 'WarehouseController', 'updatePurchase', ['AuthMiddleware', ['PermissionMiddleware', 'purchases', 'edit']]);
        $this->get('warehouse/purchases/receive/{id}', 'WarehouseController', 'receivePurchase', ['AuthMiddleware', ['PermissionMiddleware', 'purchases', 'receive']]);
        $this->post('warehouse/purchases/receive/{id}', 'WarehouseController', 'storeReceivePurchase', ['AuthMiddleware', ['PermissionMiddleware', 'purchases', 'receive']]);
        $this->post('warehouse/customers/ajax-add', 'WarehouseController', 'ajaxAddCustomer', ['AuthMiddleware', ['PermissionMiddleware', 'customers', 'create']]);

        // Direct Purchases Customer Returns & Exchanges
        $this->get('warehouse/purchases/returns', 'WarehouseController', 'purchaseReturnsIndex', ['AuthMiddleware', ['PermissionMiddleware', 'purchases_returns', 'view']]);
        $this->get('warehouse/purchases/returns/lookup', 'WarehouseController', 'purchaseReturnsLookup', ['AuthMiddleware', ['PermissionMiddleware', 'purchases_returns', 'view']]);
        $this->post('warehouse/purchases/returns/process', 'WarehouseController', 'processPurchaseReturn', ['AuthMiddleware', ['PermissionMiddleware', 'purchases_returns', 'process']]);
        $this->get('warehouse/purchases/returns/view/{id}', 'WarehouseController', 'purchaseReturnView', ['AuthMiddleware', ['PermissionMiddleware', 'purchases_returns', 'view']]);
        $this->get('warehouse/purchases/returns/invoice/{id}', 'WarehouseController', 'purchaseReturnInvoice', ['AuthMiddleware', ['PermissionMiddleware', 'purchases_returns', 'print']]);

        // Returns
        $this->get('warehouse/returns', 'WarehouseController', 'returnList', ['AuthMiddleware', ['PermissionMiddleware', 'returns', 'view']]);
        $this->post('warehouse/returns/receive', 'WarehouseController', 'receiveReturn', ['AuthMiddleware', ['PermissionMiddleware', 'returns', 'receive']]);
        $this->get('warehouse/returns/view/{id}', 'WarehouseController', 'inspectReturn', ['AuthMiddleware', ['PermissionMiddleware', 'returns', 'view']]);
        $this->get('warehouse/returns/inspect/{id}', 'WarehouseController', 'inspectReturn', ['AuthMiddleware', ['PermissionMiddleware', 'returns', 'inspect']]);
        $this->post('warehouse/returns/classify/{id}', 'WarehouseController', 'classifyReturn', ['AuthMiddleware', ['PermissionMiddleware', 'returns', 'classify']]);

        // Labels/QR Codes
        $this->get('warehouse/labels', 'WarehouseController', 'labels', ['AuthMiddleware', ['PermissionMiddleware', 'wh_labels', 'view']]);
        $this->get('warehouse/labels/ajax-source/{type}/{id}', 'WarehouseController', 'ajaxLabelSourceData', ['AuthMiddleware', ['PermissionMiddleware', 'wh_labels', 'view']]);
        $this->post('warehouse/labels/generate', 'WarehouseController', 'generateLabels', ['AuthMiddleware', ['PermissionMiddleware', 'wh_labels', 'create']]);
        $this->post('warehouse/labels/confirm-stickered-inward', 'WarehouseController', 'confirmStickeredInward', ['AuthMiddleware']);
        $this->post('api/warehouse/labels/confirm-stickered-inward', 'WarehouseController', 'confirmStickeredInward', ['AuthMiddleware']);

        // Shop Requests, WhatsApp & Website Channel Orders
        $this->get('warehouse/shop-requests', 'WarehouseController', 'shopRequestList', ['AuthMiddleware', ['PermissionMiddleware', 'shop_requests', 'view']]);
        $this->get('warehouse/shop-requests/create', 'WarehouseController', 'createShopRequest', ['AuthMiddleware', ['PermissionMiddleware', 'shop_requests', 'create']]);
        $this->post('warehouse/shop-requests/store', 'WarehouseController', 'storeShopRequest', ['AuthMiddleware', ['PermissionMiddleware', 'shop_requests', 'create']]);
        $this->get('warehouse/shop-requests/view/{id}', 'WarehouseController', 'viewShopRequest', ['AuthMiddleware', ['PermissionMiddleware', 'shop_requests', 'view']]);
        // WhatsApp Channel Module
        $this->get('warehouse/whatsapp-orders', 'WhatsappController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_dashboard', 'view']]);
        $this->get('warehouse/whatsapp-orders/inward', 'WhatsappController', 'whatsappInwardIndex', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_inward', 'view']]);
        $this->get('warehouse/whatsapp-orders/inward/view/{id}', 'WhatsappController', 'viewWhatsappInward', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_inward', 'view']]);
        $this->post('warehouse/whatsapp-orders/inward/receive/{id}', 'WhatsappController', 'receiveWhatsappInward', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_inward', 'receive']]);
        $this->get('api/whatsapp/inward/batch-history/{id}', 'WhatsappController', 'ajaxBatchHistory', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_inward', 'view']]);
        $this->get('warehouse/whatsapp-orders/allocations', 'WhatsappController', 'whatsappAllocations', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_inventory', 'view']]);
        $this->get('warehouse/whatsapp-orders/allocations/view/{id}', 'WhatsappController', 'viewWhatsappAllocation', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_inventory', 'view']]);
        $this->get('warehouse/whatsapp-orders/inventory/detail/{id}', 'WhatsappController', 'whatsappInventoryDetail', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_inventory', 'view']]);
        $this->get('warehouse/whatsapp-orders/requests/create', 'WhatsappController', 'createWhatsappStockRequest', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_orders', 'create']]);
        $this->post('warehouse/whatsapp-orders/requests/store', 'WhatsappController', 'storeWhatsappStockRequest', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_orders', 'create']]);
        $this->get('warehouse/whatsapp-orders/history', 'WhatsappController', 'whatsappOrdersHistory', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_orders', 'view']]);
        
        // WhatsApp Damage Management & Outward Dispatch
        $this->get('warehouse/whatsapp-orders/damage', 'WhatsappController', 'whatsappDamageIndex', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_damage', 'view']]);
        $this->get('warehouse/whatsapp-orders/damage/create', 'WhatsappController', 'createWhatsappDamage', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_damage', 'create']]);
        $this->post('warehouse/whatsapp-orders/damage/store', 'WhatsappController', 'storeWhatsappDamage', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_damage', 'create']]);
        $this->get('warehouse/whatsapp-orders/damage/view/{id}', 'WhatsappController', 'viewWhatsappDamage', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_damage', 'view']]);
        $this->get('warehouse/whatsapp-orders/damage/outward', 'WhatsappController', 'whatsappDamageOutwardList', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_damage', 'view']]);
        $this->get('warehouse/whatsapp-orders/damage/outward/create', 'WhatsappController', 'createWhatsappDamageOutward', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_damage', 'outward']]);
        $this->post('warehouse/whatsapp-orders/damage/outward/store', 'WhatsappController', 'storeWhatsappDamageOutward', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_damage', 'outward']]);
        $this->get('warehouse/whatsapp-orders/damage/outward/view/{id}', 'WhatsappController', 'viewWhatsappDamageOutward', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_damage', 'view']]);
        $this->get('warehouse/whatsapp-orders/damage/outward/gatepass/{id}', 'WhatsappController', 'printWhatsappDamageGatePass', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_damage', 'print']]);
        $this->get('warehouse/whatsapp-orders/damage/outward/label/{id}', 'WhatsappController', 'printWhatsappDamageCartonLabel', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_damage', 'print']]);
        $this->get('api/whatsapp/damage/lookup-scan', 'WhatsappController', 'ajaxLookupDamageProduct', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_damage', 'view']]);
        $this->post('api/whatsapp/damage/lookup-scan', 'WhatsappController', 'ajaxLookupDamageProduct', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_damage', 'view']]);

        $this->get('warehouse/whatsapp-orders/returns', 'WhatsappController', 'whatsappReturnsIndex', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_returns', 'view']]);
        $this->get('warehouse/whatsapp-orders/returns/lookup', 'WhatsappController', 'whatsappReturnsLookup', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_returns', 'view']]);
        $this->get('api/whatsapp/returns/lookup-piece', 'WhatsappController', 'ajaxLookupReturnPiece', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_returns', 'view']]);
        $this->post('api/whatsapp/returns/lookup-piece', 'WhatsappController', 'ajaxLookupReturnPiece', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_returns', 'view']]);
        $this->post('warehouse/whatsapp-orders/returns/process', 'WhatsappController', 'processWhatsappReturn', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_returns', 'create']]);
        $this->get('warehouse/whatsapp-orders/returns/view/{id}', 'WhatsappController', 'whatsappReturnView', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_returns', 'view']]);
        $this->get('warehouse/whatsapp-orders/returns/invoice/{id}', 'WhatsappController', 'whatsappReturnInvoice', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_returns', 'print']]);
        $this->get('warehouse/whatsapp-orders/customers', 'WhatsappController', 'whatsappCustomers', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_customers', 'view']]);
        
        // WhatsApp Channel Expenses & Cash Outflow
        $this->get('warehouse/whatsapp-orders/expenses', 'WhatsappController', 'expensesIndex', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_expenses', 'view']]);
        $this->get('warehouse/whatsapp-orders/expenses/create', 'WhatsappController', 'createExpense', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_expenses', 'create']]);
        $this->post('warehouse/whatsapp-orders/expenses/store', 'WhatsappController', 'storeExpense', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_expenses', 'create']]);
        $this->get('warehouse/whatsapp-orders/expenses/voucher/{id}', 'WhatsappController', 'printExpenseVoucher', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_expenses', 'voucher']]);
        $this->get('warehouse/whatsapp-orders/expenses/delete/{id}', 'WhatsappController', 'confirmDeleteExpense', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_expenses', 'delete']]);
        $this->post('warehouse/whatsapp-orders/expenses/delete/{id}', 'WhatsappController', 'deleteExpense', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_expenses', 'delete']]);

        // WhatsApp Channel Return to Warehouse
        $this->get('warehouse/whatsapp-orders/return-to-warehouse', 'WhatsappController', 'returnToWarehouseList', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_return_to_wh', 'view']]);
        $this->get('warehouse/whatsapp-orders/return-to-warehouse/create', 'WhatsappController', 'createReturnToWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_return_to_wh', 'create']]);
        $this->post('warehouse/whatsapp-orders/return-to-warehouse/store', 'WhatsappController', 'storeReturnToWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_return_to_wh', 'create']]);
        $this->get('warehouse/whatsapp-orders/return-to-warehouse/view/{id}', 'WhatsappController', 'viewReturnToWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_return_to_wh', 'view']]);
        $this->get('warehouse/whatsapp-orders/return-to-warehouse/gatepass/{id}', 'WhatsappController', 'printReturnToWarehouseGatePass', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_return_to_wh', 'gatepass']]);
        $this->get('warehouse/whatsapp-orders/return-to-warehouse/label/{id}', 'WhatsappController', 'printReturnToWarehouseLabel', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_return_to_wh', 'print']]);
        $this->get('api/whatsapp/inventory/ajax-detail/{id}', 'WhatsappController', 'ajaxProductDetail', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_inventory', 'view']]);
        $this->post('warehouse/whatsapp-orders/inventory/update-price', 'WhatsappController', 'updateProductPrice', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_inventory', 'adjust']]);
        $this->post('api/whatsapp/inventory/update-price', 'WhatsappController', 'updateProductPrice', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_inventory', 'adjust']]);

        // Website Channel Module
        $this->get('warehouse/website-orders', 'WebsiteController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'website_dashboard', 'view']]);
        $this->get('warehouse/website-orders/inward', 'WebsiteController', 'websiteInwardIndex', ['AuthMiddleware', ['PermissionMiddleware', 'website_inward', 'view']]);
        $this->get('warehouse/website-orders/inward/view/{id}', 'WebsiteController', 'viewWebsiteInward', ['AuthMiddleware', ['PermissionMiddleware', 'website_inward', 'view']]);
        $this->post('warehouse/website-orders/inward/receive/{id}', 'WebsiteController', 'receiveWebsiteInward', ['AuthMiddleware', ['PermissionMiddleware', 'website_inward', 'receive']]);
        $this->get('api/website/inward/batch-history/{id}', 'WebsiteController', 'ajaxBatchHistory', ['AuthMiddleware', ['PermissionMiddleware', 'website_inward', 'view']]);
        $this->get('warehouse/website-orders/allocations', 'WebsiteController', 'websiteAllocations', ['AuthMiddleware', ['PermissionMiddleware', 'website_inventory', 'view']]);
        $this->get('warehouse/website-orders/allocations/view/{id}', 'WebsiteController', 'viewWebsiteAllocation', ['AuthMiddleware', ['PermissionMiddleware', 'website_inventory', 'view']]);
        $this->get('warehouse/website-orders/inventory/detail/{id}', 'WebsiteController', 'websiteInventoryDetail', ['AuthMiddleware', ['PermissionMiddleware', 'website_inventory', 'view']]);
        $this->get('warehouse/website-orders/requests/create', 'WebsiteController', 'createWebsiteStockRequest', ['AuthMiddleware', ['PermissionMiddleware', 'website_orders', 'create']]);
        $this->post('warehouse/website-orders/requests/store', 'WebsiteController', 'storeWebsiteStockRequest', ['AuthMiddleware', ['PermissionMiddleware', 'website_orders', 'create']]);
        $this->get('warehouse/website-orders/history', 'WebsiteController', 'websiteOrdersHistory', ['AuthMiddleware', ['PermissionMiddleware', 'website_orders', 'view']]);
        
        // Website Damage Management & Outward Dispatch
        $this->get('warehouse/website-orders/damage', 'WebsiteController', 'websiteDamageIndex', ['AuthMiddleware', ['PermissionMiddleware', 'website_damage', 'view']]);
        $this->get('warehouse/website-orders/damage/create', 'WebsiteController', 'createWebsiteDamage', ['AuthMiddleware', ['PermissionMiddleware', 'website_damage', 'create']]);
        $this->post('warehouse/website-orders/damage/store', 'WebsiteController', 'storeWebsiteDamage', ['AuthMiddleware', ['PermissionMiddleware', 'website_damage', 'create']]);
        $this->get('warehouse/website-orders/damage/view/{id}', 'WebsiteController', 'viewWebsiteDamage', ['AuthMiddleware', ['PermissionMiddleware', 'website_damage', 'view']]);
        $this->get('warehouse/website-orders/damage/outward', 'WebsiteController', 'websiteDamageOutwardList', ['AuthMiddleware', ['PermissionMiddleware', 'website_damage', 'outward']]);
        $this->get('warehouse/website-orders/damage/outward/create', 'WebsiteController', 'createWebsiteDamageOutward', ['AuthMiddleware', ['PermissionMiddleware', 'website_damage', 'outward']]);
        $this->post('warehouse/website-orders/damage/outward/store', 'WebsiteController', 'storeWebsiteDamageOutward', ['AuthMiddleware', ['PermissionMiddleware', 'website_damage', 'outward']]);
        $this->get('warehouse/website-orders/damage/outward/view/{id}', 'WebsiteController', 'viewWebsiteDamageOutward', ['AuthMiddleware', ['PermissionMiddleware', 'website_damage', 'view']]);
        $this->get('warehouse/website-orders/damage/outward/gatepass/{id}', 'WebsiteController', 'printWebsiteDamageGatePass', ['AuthMiddleware', ['PermissionMiddleware', 'website_damage', 'print']]);
        $this->get('warehouse/website-orders/damage/outward/label/{id}', 'WebsiteController', 'printWebsiteDamageCartonLabel', ['AuthMiddleware', ['PermissionMiddleware', 'website_damage', 'print']]);
        $this->get('api/website/damage/lookup-scan', 'WebsiteController', 'ajaxLookupDamageProduct', ['AuthMiddleware', ['PermissionMiddleware', 'website_damage', 'view']]);
        $this->post('api/website/damage/lookup-scan', 'WebsiteController', 'ajaxLookupDamageProduct', ['AuthMiddleware', ['PermissionMiddleware', 'website_damage', 'view']]);

        $this->get('warehouse/website-orders/returns', 'WebsiteController', 'websiteReturnsIndex', ['AuthMiddleware', ['PermissionMiddleware', 'website_returns', 'view']]);
        $this->get('warehouse/website-orders/returns/lookup', 'WebsiteController', 'websiteReturnsLookup', ['AuthMiddleware', ['PermissionMiddleware', 'website_returns', 'view']]);
        $this->get('api/website/returns/lookup-piece', 'WebsiteController', 'ajaxLookupReturnPiece', ['AuthMiddleware', ['PermissionMiddleware', 'website_returns', 'view']]);
        $this->post('api/website/returns/lookup-piece', 'WebsiteController', 'ajaxLookupReturnPiece', ['AuthMiddleware', ['PermissionMiddleware', 'website_returns', 'view']]);
        $this->post('warehouse/website-orders/returns/process', 'WebsiteController', 'processWebsiteReturn', ['AuthMiddleware']);
        $this->get('warehouse/website-orders/returns/view/{id}', 'WebsiteController', 'websiteReturnView', ['AuthMiddleware', ['PermissionMiddleware', 'website_returns', 'view']]);
        $this->get('warehouse/website-orders/returns/invoice/{id}', 'WebsiteController', 'websiteReturnInvoice', ['AuthMiddleware', ['PermissionMiddleware', 'website_returns', 'print']]);
        $this->get('warehouse/website-orders/customers', 'WebsiteController', 'websiteCustomers', ['AuthMiddleware', ['PermissionMiddleware', 'website_customers', 'view']]);

        // Website Channel Return to Warehouse
        $this->get('warehouse/website-orders/return-to-warehouse', 'WebsiteController', 'returnToWarehouseList', ['AuthMiddleware', ['PermissionMiddleware', 'website_return_to_wh', 'view']]);
        $this->get('warehouse/website-orders/return-to-warehouse/create', 'WebsiteController', 'createReturnToWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'website_return_to_wh', 'create']]);
        $this->post('warehouse/website-orders/return-to-warehouse/store', 'WebsiteController', 'storeReturnToWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'website_return_to_wh', 'create']]);
        $this->get('warehouse/website-orders/return-to-warehouse/view/{id}', 'WebsiteController', 'viewReturnToWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'website_return_to_wh', 'view']]);
        $this->get('warehouse/website-orders/return-to-warehouse/gatepass/{id}', 'WebsiteController', 'printReturnToWarehouseGatePass', ['AuthMiddleware', ['PermissionMiddleware', 'website_return_to_wh', 'gatepass']]);
        $this->get('warehouse/website-orders/return-to-warehouse/label/{id}', 'WebsiteController', 'printReturnToWarehouseLabel', ['AuthMiddleware', ['PermissionMiddleware', 'website_return_to_wh', 'print']]);
        $this->get('api/website/inventory/ajax-detail/{id}', 'WebsiteController', 'ajaxProductDetail', ['AuthMiddleware', ['PermissionMiddleware', 'website_inventory', 'view']]);
        $this->post('warehouse/website-orders/inventory/update-price', 'WebsiteController', 'updateProductPrice', ['AuthMiddleware', ['PermissionMiddleware', 'website_inventory', 'adjust']]);
        $this->post('api/website/inventory/update-price', 'WebsiteController', 'updateProductPrice', ['AuthMiddleware', ['PermissionMiddleware', 'website_inventory', 'adjust']]);

        // Website Channel Expenses & Cash Outflow
        $this->get('warehouse/website-orders/expenses', 'WebsiteController', 'expensesIndex', ['AuthMiddleware', ['PermissionMiddleware', 'website_expenses', 'view']]);
        $this->get('warehouse/website-orders/expenses/create', 'WebsiteController', 'createExpense', ['AuthMiddleware', ['PermissionMiddleware', 'website_expenses', 'create']]);
        $this->post('warehouse/website-orders/expenses/store', 'WebsiteController', 'storeExpense', ['AuthMiddleware', ['PermissionMiddleware', 'website_expenses', 'create']]);
        $this->get('warehouse/website-orders/expenses/voucher/{id}', 'WebsiteController', 'printExpenseVoucher', ['AuthMiddleware']);
        $this->post('warehouse/website-orders/expenses/delete/{id}', 'WebsiteController', 'deleteExpense', ['AuthMiddleware', ['PermissionMiddleware', 'website_expenses', 'delete']]);

        // Warehouse Operational Expenses
        $this->get('warehouse/expenses', 'WarehouseController', 'expensesIndex', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_expenses', 'view']]);
        $this->get('warehouse/expenses/create', 'WarehouseController', 'createExpense', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_expenses', 'create']]);
        $this->post('warehouse/expenses/store', 'WarehouseController', 'storeExpense', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_expenses', 'create']]);
        $this->get('warehouse/expenses/voucher/{id}', 'WarehouseController', 'printExpenseVoucher', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_expenses', 'voucher']]);
        $this->post('warehouse/expenses/delete/{id}', 'WarehouseController', 'deleteExpense', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse_expenses', 'delete']]);

        // Delivery
        $this->get('warehouse/delivery', 'WarehouseController', 'deliveryList', ['AuthMiddleware', ['PermissionMiddleware', 'allocation_outward', 'view']]);
        $this->post('warehouse/delivery/approve/{id}', 'WarehouseController', 'approveDelivery', ['AuthMiddleware', ['PermissionMiddleware', 'allocation_outward', 'create']]);
        $this->post('warehouse/delivery/dispatch/{id}', 'WarehouseController', 'dispatchDelivery', ['AuthMiddleware', ['PermissionMiddleware', 'allocation_outward', 'create']]);
        $this->post('warehouse/delivery/confirm/{id}', 'WarehouseController', 'confirmDelivery', ['AuthMiddleware', ['PermissionMiddleware', 'allocation_outward', 'deliver']]);

        // Transfers
        $this->get('warehouse/transfers', 'WarehouseController', 'transferList', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse', 'view']]);
        $this->get('warehouse/transfers/create', 'WarehouseController', 'createTransfer', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse', 'create']]);
        $this->post('warehouse/transfers/store', 'WarehouseController', 'storeTransfer', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse', 'create']]);
        $this->post('warehouse/transfers/approve/{id}', 'WarehouseController', 'approveTransfer', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse', 'approve']]);
        $this->post('warehouse/transfers/receive/{id}', 'WarehouseController', 'receiveTransfer', ['AuthMiddleware', ['PermissionMiddleware', 'warehouse', 'receive']]);

        // Stock Adjustments
        $this->get('warehouse/adjustments', 'WarehouseController', 'adjustmentList', ['AuthMiddleware', ['PermissionMiddleware', 'stock', 'view']]);
        $this->post('warehouse/adjustments/store', 'WarehouseController', 'storeAdjustment', ['AuthMiddleware', ['PermissionMiddleware', 'stock', 'adjust']]);
        $this->post('warehouse/adjustments/approve/{id}', 'WarehouseController', 'approveAdjustment', ['AuthMiddleware', ['PermissionMiddleware', 'stock', 'adjust']]);

        // ==========================================
        // SHOP ROUTES
        // ==========================================
        $this->get('shop/dashboard', 'ShopController', 'dashboard', ['AuthMiddleware', ['PermissionMiddleware', 'shop', 'view']]);
        $this->get('api/shop/dashboard/metrics', 'ShopController', 'ajaxFilterMetrics', ['AuthMiddleware', ['PermissionMiddleware', 'shop', 'view']]);

        // POS / Billing
        $this->get('shop/pos', 'ShopController', 'posInterface', ['AuthMiddleware', ['PermissionMiddleware', 'pos', 'view']]);
        $this->get('shop/pos/scan/{code}', 'ShopController', 'scanQR', ['AuthMiddleware', ['PermissionMiddleware', 'pos', 'view']]);
        $this->get('shop/pos/customer-lookup/{query}', 'ShopController', 'lookupCustomer', ['AuthMiddleware', ['PermissionMiddleware', 'customers', 'view']]);
        $this->get('shop/pos/customer-lookup', 'ShopController', 'lookupCustomer', ['AuthMiddleware', ['PermissionMiddleware', 'customers', 'view']]);
        $this->post('shop/pos/sale', 'ShopController', 'processSale', ['AuthMiddleware', ['PermissionMiddleware', 'pos', 'create']]);
        $this->get('shop/pos/invoice/{id}', 'ShopController', 'printInvoice', ['AuthMiddleware', ['PermissionMiddleware', 'pos', 'print']]);

        // Shop Customer Directory & 360 CRM
        $this->get('shop/customers', 'ShopController', 'customersList', ['AuthMiddleware', ['PermissionMiddleware', 'customers', 'view']]);
        $this->get('shop/customers/view/{id}', 'ShopController', 'viewCustomer', ['AuthMiddleware', ['PermissionMiddleware', 'customers', 'view']]);
        $this->get('api/shop/customers/{id}', 'ShopController', 'ajaxCustomerDetails', ['AuthMiddleware', ['PermissionMiddleware', 'customers', 'view']]);
        $this->post('shop/customers/store', 'ShopController', 'storeCustomer', ['AuthMiddleware', ['PermissionMiddleware', 'customers', 'create']]);
        $this->post('shop/customers/update/{id}', 'ShopController', 'updateCustomer', ['AuthMiddleware', ['PermissionMiddleware', 'customers', 'edit']]);
        $this->post('shop/customers/toggle-status/{id}', 'ShopController', 'toggleCustomerStatus', ['AuthMiddleware', ['PermissionMiddleware', 'customers', 'edit']]);

        // Shop Inward / Stock Receive & QR Suite
        $this->get('shop/inward', 'ShopController', 'inwardList', ['AuthMiddleware', ['PermissionMiddleware', 'shop_inward', 'view']]);
        $this->get('shop/inward/view/{id}', 'ShopController', 'viewInward', ['AuthMiddleware', ['PermissionMiddleware', 'shop_inward', 'view']]);
        $this->post('shop/inward/receive/{id}', 'ShopController', 'receiveInward', ['AuthMiddleware', ['PermissionMiddleware', 'shop_inward', 'receive']]);
        $this->post('shop/inward/verify/{id}', 'ShopController', 'verifyInward', ['AuthMiddleware', ['PermissionMiddleware', 'shop_inward', 'verify']]);
        $this->get('shop/inward/label/{id}', 'ShopController', 'printInwardQRLabel', ['AuthMiddleware', ['PermissionMiddleware', 'shop_inward', 'print']]);
        $this->get('shop/inward/gatepass/{id}', 'ShopController', 'printInwardGatePass', ['AuthMiddleware', ['PermissionMiddleware', 'shop_inward', 'print']]);
        $this->post('api/shop/inward/scan', 'ShopController', 'ajaxScanInward', ['AuthMiddleware', ['PermissionMiddleware', 'shop_inward', 'receive']]);
        $this->post('api/shop/inward/crosscheck-scan', 'ShopController', 'ajaxCrossCheckScan', ['AuthMiddleware', ['PermissionMiddleware', 'shop_inward', 'verify']]);
        $this->post('api/shop/inward/save-draft', 'ShopController', 'ajaxSaveInwardDraft', ['AuthMiddleware', ['PermissionMiddleware', 'shop_inward', 'receive']]);
        $this->post('api/shop/inward/clear-draft', 'ShopController', 'ajaxClearInwardDraft', ['AuthMiddleware', ['PermissionMiddleware', 'shop_inward', 'receive']]);
        $this->get('api/shop/inward/lookup/{code}', 'ShopController', 'ajaxLookupInward', ['AuthMiddleware', ['PermissionMiddleware', 'shop_inward', 'view']]);

        // Shop Inventory
        $this->get('shop/inventory', 'ShopController', 'inventoryList', ['AuthMiddleware', ['PermissionMiddleware', 'shop_stock', 'view']]);
        $this->get('shop/inventory/detail/{id}', 'ShopController', 'inventoryDetail', ['AuthMiddleware', ['PermissionMiddleware', 'shop_stock', 'view']]);
        $this->get('shop/inventory/ajax-detail/{id}', 'ShopController', 'ajaxProductDetail', ['AuthMiddleware', ['PermissionMiddleware', 'shop_stock', 'view']]);
        $this->post('shop/inventory/request', 'ShopController', 'requestStock', ['AuthMiddleware', ['PermissionMiddleware', 'shop_stock', 'request']]);
        $this->post('shop/inventory/update-price', 'ShopController', 'updateProductPrice', ['AuthMiddleware', ['PermissionMiddleware', 'shop_stock', 'edit_price']]);
        $this->post('api/shop/inventory/update-price', 'ShopController', 'updateProductPrice', ['AuthMiddleware', ['PermissionMiddleware', 'shop_stock', 'edit_price']]);
        $this->get('api/shop/products/search', 'ShopController', 'ajaxSearchProducts', ['AuthMiddleware', ['PermissionMiddleware', 'shop_stock', 'view']]);

        // Shop Damage Management
        $this->get('shop/damage', 'ShopController', 'damageList', ['AuthMiddleware', ['PermissionMiddleware', 'shop_damage', 'view']]);
        $this->get('shop/damage/create', 'ShopController', 'createDamage', ['AuthMiddleware', ['PermissionMiddleware', 'shop_damage', 'create']]);
        $this->post('shop/damage/store', 'ShopController', 'storeDamage', ['AuthMiddleware', ['PermissionMiddleware', 'shop_damage', 'create']]);
        $this->get('shop/damage/view/{id}', 'ShopController', 'viewDamage', ['AuthMiddleware', ['PermissionMiddleware', 'shop_damage', 'view']]);
        $this->post('shop/damage/update-status/{id}', 'ShopController', 'updateDamageStatus', ['AuthMiddleware', ['PermissionMiddleware', 'shop_damage', 'edit']]);
        $this->get('api/shop/damage/lookup-scan', 'ShopController', 'ajaxLookupDamageProduct', ['AuthMiddleware', ['PermissionMiddleware', 'shop_damage', 'view']]);
        $this->post('api/shop/damage/lookup-scan', 'ShopController', 'ajaxLookupDamageProduct', ['AuthMiddleware', ['PermissionMiddleware', 'shop_damage', 'view']]);

        // Selled Products (Sales Register)
        $this->get('shop/sales', 'ShopController', 'soldProductsList', ['AuthMiddleware', ['PermissionMiddleware', 'sales', 'view']]);
        $this->get('api/shop/sales/{id}', 'ShopController', 'ajaxGetSaleDetails', ['AuthMiddleware', ['PermissionMiddleware', 'sales', 'view']]);

        // Customer Returns & Exchanges
        $this->get('shop/returns', 'ShopController', 'returnList', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'view']]);
        $this->get('shop/returns/lookup', 'ShopController', 'ajaxReturnsLookup', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'view']]);
        $this->get('api/shop/returns/lookup-piece', 'ShopController', 'ajaxLookupReturnPiece', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'view']]);
        $this->post('api/shop/returns/lookup-piece', 'ShopController', 'ajaxLookupReturnPiece', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'view']]);
        $this->post('shop/returns/process', 'ShopController', 'processReturn', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'create']]);
        $this->post('shop/returns/store', 'ShopController', 'processReturn', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'create']]);
        $this->post('shop/returns/process/{id}', 'ShopController', 'processReturn', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'process']]);
        $this->get('shop/returns/view/{id}', 'ShopController', 'viewReturn', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'view']]);
        $this->get('shop/returns/invoice/{id}', 'ShopController', 'printReturnInvoice', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'print']]);
        $this->get('shop/returns/print/{id}', 'ShopController', 'printReturnInvoice', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'print']]);
        $this->get('api/shop/returns/{id}', 'ShopController', 'ajaxGetReturnDetails', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'view']]);
        $this->get('shop/returns/create', 'ShopController', 'createReturn', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'create']]);

        // Return to Warehouse
        $this->get('shop/return-to-warehouse', 'ShopController', 'returnToWarehouseList', ['AuthMiddleware', ['PermissionMiddleware', 'return_to_warehouse', 'view']]);
        $this->get('shop/return-to-warehouse/create', 'ShopController', 'createReturnToWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'return_to_warehouse', 'create']]);
        $this->post('shop/return-to-warehouse/store', 'ShopController', 'storeReturnToWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'return_to_warehouse', 'create']]);
        $this->get('shop/return-to-warehouse/view/{id}', 'ShopController', 'viewReturnToWarehouse', ['AuthMiddleware', ['PermissionMiddleware', 'return_to_warehouse', 'view']]);
        $this->get('shop/return-to-warehouse/gatepass/{id}', 'ShopController', 'printReturnToWarehouseGatePass', ['AuthMiddleware', ['PermissionMiddleware', 'return_to_warehouse', 'gatepass']]);
        $this->get('shop/return-to-warehouse/label/{id}', 'ShopController', 'printReturnToWarehouseLabel', ['AuthMiddleware', ['PermissionMiddleware', 'return_to_warehouse', 'print']]);

        // Exchanges
        $this->get('shop/exchanges', 'ShopController', 'exchangeList', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'view']]);
        $this->get('shop/exchanges/create', 'ShopController', 'createExchange', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'create']]);
        $this->post('shop/exchanges/store', 'ShopController', 'storeExchange', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'create']]);
        $this->post('shop/exchanges/process/{id}', 'ShopController', 'processExchange', ['AuthMiddleware', ['PermissionMiddleware', 'shop_returns', 'process']]);

        // Cash & Cash Flow Management
        $this->get('shop/cash', 'CashController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'shop_cash', 'view']]);
        $this->post('shop/cash/open', 'CashController', 'openRegister', ['AuthMiddleware', ['PermissionMiddleware', 'shop_cash', 'create']]);
        $this->post('shop/cash/close', 'CashController', 'closeRegister', ['AuthMiddleware', ['PermissionMiddleware', 'shop_cash', 'close_shift']]);
        $this->post('shop/cash/expense', 'CashController', 'storeExpense', ['AuthMiddleware', ['PermissionMiddleware', 'shop_cash', 'create']]);
        $this->post('shop/cash/cash-in', 'CashController', 'storeCashIn', ['AuthMiddleware', ['PermissionMiddleware', 'shop_cash', 'create']]);
        $this->post('shop/cash/bank-deposit', 'CashController', 'storeBankDeposit', ['AuthMiddleware', ['PermissionMiddleware', 'shop_cash', 'create']]);
        $this->get('shop/cash/shift-receipt/{id}', 'CashController', 'shiftReceipt', ['AuthMiddleware', ['PermissionMiddleware', 'shop_cash', 'voucher']]);
        $this->get('shop/cash/expense-voucher/{id}', 'CashController', 'expenseVoucher', ['AuthMiddleware', ['PermissionMiddleware', 'shop_cash', 'voucher']]);
        $this->get('api/shop/cash/status', 'CashController', 'ajaxRegisterStatus', ['AuthMiddleware', ['PermissionMiddleware', 'shop_cash', 'view']]);

        // ==========================================
        // SALES CHANNEL ROUTES
        // ==========================================
        $this->get('sales/website', 'SalesController', 'websiteOrderList', ['AuthMiddleware', ['PermissionMiddleware', 'website_orders', 'view']]);
        $this->get('sales/website/create', 'SalesController', 'createWebsiteOrder', ['AuthMiddleware', ['PermissionMiddleware', 'website_orders', 'create']]);
        $this->post('api/website/sales/validate-scan', 'SalesController', 'validateSalesScan', ['AuthMiddleware', ['PermissionMiddleware', 'website_orders', 'create']]);
        $this->get('api/website/customers/lookup', 'SalesController', 'lookupWebsiteCustomer', ['AuthMiddleware', ['PermissionMiddleware', 'website_orders', 'create']]);
        $this->post('sales/website/store', 'SalesController', 'storeWebsiteOrder', ['AuthMiddleware', ['PermissionMiddleware', 'website_orders', 'create']]);
        $this->get('sales/website/view/{id}', 'SalesController', 'viewWebsiteOrder', ['AuthMiddleware', ['PermissionMiddleware', 'website_orders', 'view']]);
        $this->post('sales/website/process/{id}', 'SalesController', 'processWebsiteOrder', ['AuthMiddleware', ['PermissionMiddleware', 'website_orders', 'process']]);

        $this->get('sales/whatsapp', 'SalesController', 'whatsappOrderList', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_orders', 'view']]);
        $this->get('sales/whatsapp/create', 'SalesController', 'createWhatsappOrder', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_orders', 'create']]);
        $this->post('api/whatsapp/sales/validate-scan', 'SalesController', 'validateSalesScan', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_orders', 'create']]);
        $this->get('api/whatsapp/customers/lookup', 'SalesController', 'lookupWhatsappCustomer', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_orders', 'create']]);
        $this->post('sales/whatsapp/store', 'SalesController', 'storeWhatsappOrder', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_orders', 'create']]);
        $this->get('sales/whatsapp/view/{id}', 'SalesController', 'viewWhatsappOrder', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_orders', 'view']]);
        $this->post('sales/whatsapp/process/{id}', 'SalesController', 'processWhatsappOrder', ['AuthMiddleware', ['PermissionMiddleware', 'whatsapp_orders', 'process']]);

        // ==========================================
        // REPORT ROUTES
        // ==========================================
        $this->get('reports', 'ReportController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'reports_overview', 'view']]);
        $this->get('reports/overview', 'ReportController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'reports_overview', 'view']]);
        $this->get('reports/profitability', 'ReportController', 'profitabilityReports', ['AuthMiddleware', ['PermissionMiddleware', 'reports_profitability', 'view']]);
        $this->get('reports/sales', 'ReportController', 'salesReports', ['AuthMiddleware', ['PermissionMiddleware', 'reports_sales', 'view']]);
        $this->get('reports/website', 'ReportController', 'websiteReports', ['AuthMiddleware', ['PermissionMiddleware', 'reports_website', 'view']]);
        $this->get('reports/whatsapp', 'ReportController', 'whatsappReports', ['AuthMiddleware', ['PermissionMiddleware', 'reports_whatsapp', 'view']]);
        $this->get('reports/online', 'ReportController', 'onlineReports', ['AuthMiddleware', ['PermissionMiddleware', 'reports_online', 'view']]);
        $this->get('reports/production', 'ReportController', 'productionReports', ['AuthMiddleware', ['PermissionMiddleware', 'reports_production', 'view']]);
        $this->get('reports/warehouse', 'ReportController', 'warehouseReports', ['AuthMiddleware', ['PermissionMiddleware', 'reports_warehouse', 'view']]);
        $this->get('reports/purchase', 'ReportController', 'purchaseReports', ['AuthMiddleware', ['PermissionMiddleware', 'reports_purchase', 'view']]);
        $this->get('reports/shop', 'ReportController', 'shopReports', ['AuthMiddleware', ['PermissionMiddleware', 'reports_shop', 'view']]);
        $this->get('reports/inventory', 'ReportController', 'inventoryReports', ['AuthMiddleware', ['PermissionMiddleware', 'reports_inventory', 'view']]);
        $this->get('reports/export/{type}', 'ReportController', 'exportReport', ['AuthMiddleware']);
        $this->get('reports/pdf/{type}', 'ReportController', 'downloadReportPdf', ['AuthMiddleware']);
        $this->get('reports/print/{type}', 'ReportController', 'printReport', ['AuthMiddleware']);

        // ==========================================
        // ENTERPRISE ASSET MANAGEMENT ROUTES
        // ==========================================
        $this->get('asset/dashboard', 'AssetController', 'dashboard', ['AuthMiddleware', ['PermissionMiddleware', 'assets', 'view']]);
        $this->get('asset/list', 'AssetController', 'index', ['AuthMiddleware', ['PermissionMiddleware', 'assets', 'view']]);
        $this->get('asset/scanner', 'AssetController', 'scanner', ['AuthMiddleware', ['PermissionMiddleware', 'asset_scanner', 'view']]);
        $this->get('asset/create', 'AssetController', 'create', ['AuthMiddleware', ['PermissionMiddleware', 'assets', 'create']]);
        $this->post('asset/store', 'AssetController', 'store', ['AuthMiddleware', ['PermissionMiddleware', 'assets', 'create']]);
        $this->get('asset/view/{id}', 'AssetController', 'view', ['AuthMiddleware', ['PermissionMiddleware', 'assets', 'view']]);
        $this->get('asset/edit/{id}', 'AssetController', 'edit', ['AuthMiddleware', ['PermissionMiddleware', 'assets', 'edit']]);
        $this->post('asset/update/{id}', 'AssetController', 'update', ['AuthMiddleware', ['PermissionMiddleware', 'assets', 'edit']]);
        $this->post('asset/delete/{id}', 'AssetController', 'delete', ['AuthMiddleware', ['PermissionMiddleware', 'assets', 'delete']]);
        $this->get('asset/transfers', 'AssetController', 'transfers', ['AuthMiddleware', ['PermissionMiddleware', 'asset_transfers', 'view']]);
        $this->post('asset/transfers/store', 'AssetController', 'storeTransfer', ['AuthMiddleware', ['PermissionMiddleware', 'asset_transfers', 'create']]);
        $this->get('asset/maintenance', 'AssetController', 'maintenance', ['AuthMiddleware', ['PermissionMiddleware', 'asset_maintenance', 'view']]);
        $this->post('asset/maintenance/store', 'AssetController', 'storeMaintenance', ['AuthMiddleware', ['PermissionMiddleware', 'asset_maintenance', 'create']]);
        $this->get('asset/label/{id}', 'AssetController', 'label', ['AuthMiddleware', ['PermissionMiddleware', 'assets', 'print']]);
        $this->get('asset/bulk-labels', 'AssetController', 'bulkLabels', ['AuthMiddleware', ['PermissionMiddleware', 'asset_bulk_labels', 'view']]);
        $this->get('api/asset/lookup', 'AssetController', 'ajaxLookup', ['AuthMiddleware', ['PermissionMiddleware', 'asset_scanner', 'view']]);
        $this->post('api/asset/lookup', 'AssetController', 'ajaxLookup', ['AuthMiddleware', ['PermissionMiddleware', 'asset_scanner', 'view']]);
        $this->post('api/asset/category/create', 'AssetController', 'ajaxCreateCategory', ['AuthMiddleware', ['PermissionMiddleware', 'assets', 'create']]);
        $this->post('api/asset/unit/create', 'AssetController', 'ajaxCreateUnit', ['AuthMiddleware', ['PermissionMiddleware', 'assets', 'create']]);

        // ==========================================
        // SYSTEM ROUTES
        // ==========================================
        $this->get('system/notifications', 'SystemController', 'notifications', ['AuthMiddleware']);
        $this->post('system/notifications/read/{id}', 'SystemController', 'markRead', ['AuthMiddleware']);
        $this->post('system/notifications/read-all', 'SystemController', 'markAllRead', ['AuthMiddleware']);
        $this->get('api/notifications/unread', 'SystemController', 'unreadNotifications', ['AuthMiddleware']);

        $this->get('system/audit', 'SystemController', 'auditLogs', ['AuthMiddleware']);
        $this->get('system/audit/export', 'SystemController', 'exportAuditLogs', ['AuthMiddleware']);
        $this->get('system/settings', 'SystemController', 'settings', ['AuthMiddleware']);
        $this->post('system/settings/update', 'SystemController', 'updateSettings', ['AuthMiddleware']);
        $this->get('system/backup', 'SystemController', 'backupList', ['AuthMiddleware']);
        $this->get('system/backup/export-sql', 'SystemController', 'exportSqlDirect', ['AuthMiddleware']);
        $this->get('system/backup/export-zip', 'SystemController', 'exportZipDirect', ['AuthMiddleware']);
        $this->post('system/backup/create', 'SystemController', 'createBackup', ['AuthMiddleware']);
        $this->post('system/backup/import', 'SystemController', 'importBackup', ['AuthMiddleware']);
        $this->get('system/backup/download/{id}', 'SystemController', 'downloadBackup', ['AuthMiddleware']);
        $this->post('system/backup/restore', 'SystemController', 'restoreBackup', ['AuthMiddleware']);
        $this->post('system/backup/delete/{id}', 'SystemController', 'deleteBackup', ['AuthMiddleware']);
        $this->post('system/backup/wipe-test-data', 'SystemController', 'wipeTestData', ['AuthMiddleware']);
        $this->post('system/backup/email-latest', 'SystemController', 'emailLatestBackup', ['AuthMiddleware']);
        $this->get('system/backup/send-email/{id}', 'SystemController', 'emailSpecificBackup', ['AuthMiddleware']);
        $this->post('system/backup/send-email/{id}', 'SystemController', 'emailSpecificBackup', ['AuthMiddleware']);
    }
}
