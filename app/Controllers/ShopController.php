<?php
/**
 * Shop Controller — Retail POS Billing, Shop Inventory, Returns & Exchanges
 */

require_once BASE_PATH . '/app/Services/ShopService.php';
require_once BASE_PATH . '/app/Services/StockService.php';
require_once BASE_PATH . '/app/Services/AuditService.php';
require_once BASE_PATH . '/app/Services/CloudinaryService.php';
require_once BASE_PATH . '/app/Models/WarehouseAllocation.php';
require_once BASE_PATH . '/app/Models/AllocationItem.php';
require_once BASE_PATH . '/app/Models/Warehouse.php';
require_once BASE_PATH . '/app/Models/Shop.php';
require_once BASE_PATH . '/app/Models/ShopDamage.php';

class ShopController {

    private function resolveInventoryShop(?int $requestedShopId = null): ?array {
        if ($requestedShopId !== null) {
            $shops = function_exists('getScopedShops') ? getScopedShops() : [];
            $allowedShopIds = array_map(static fn(array $shop): int => (int)$shop['id'], $shops);
            if (!in_array($requestedShopId, $allowedShopIds, true)) {
                return null;
            }
        }

        return ShopService::getActiveShop($requestedShopId);
    }

    /**
     * Shop Overview & Dashboard
     */
    public function dashboard(): void {
        $shop = ShopService::getActiveShop();
        $period = $_GET['period'] ?? 'today';
        $customStart = $_GET['start_date'] ?? null;
        $customEnd = $_GET['end_date'] ?? null;

        $stats = ShopService::getDashboardStats((int)$shop['id']);
        $filtered = ShopService::getFilteredMetrics((int)$shop['id'], $period, $customStart, $customEnd);

        Response::view('shop.dashboard', array_merge($stats, [
            'pageTitle'       => 'Shop Overview & Live POS Dashboard',
            'activeModule'    => 'shop',
            'activeMenu'      => 'shop-dashboard',
            'breadcrumb'      => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Dashboard'],
            ],
            'shop'            => $shop,
            'current_filter'  => $filtered,
        ]));
    }

    /**
     * AJAX Endpoint: Get Filtered Metric Summary & Chart Data
     */
    public function ajaxFilterMetrics(): void {
        $shop = ShopService::getActiveShop();
        $period = $_GET['period'] ?? 'today';
        $customStart = $_GET['start_date'] ?? null;
        $customEnd = $_GET['end_date'] ?? null;

        $filtered = ShopService::getFilteredMetrics((int)$shop['id'], $period, $customStart, $customEnd);
        Response::jsonSuccess('Metrics loaded successfully', $filtered);
    }

    /**
     * POS Billing Terminal Interface
     */
    public function posInterface(): void {
        if (!hasPermission('pos', 'view') && !hasPermission('pos', 'create')) {
            Session::setFlash('error', 'Access denied: You do not have permission to view the POS billing terminal.');
            Response::redirect(url('shop/dashboard'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $staffList = Database::fetchAll("SELECT id, username, full_name FROM users WHERE is_active = 1 ORDER BY full_name ASC");

        // POS terminal view rendered with minimal layout wrapper for speed
        Response::view('shop.pos.index', [
            'pageTitle'    => 'POS Billing Terminal — QR Scanner',
            'activeModule' => 'shop',
            'activeMenu'   => 'shop-pos',
            'breadcrumb'   => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'POS Terminal'],
            ],
            'shop'         => $shop,
            'staffList'    => $staffList,
        ]);
    }

    /**
     * AJAX Endpoint: Scan QR Code / Barcode
     */
    public function scanQR(string $code = ''): void {
        if (!hasPermission('pos', 'view') && !hasPermission('pos', 'create')) {
            Response::jsonError("Access denied: You do not have permission to use POS billing scanner.", [], 403);
            return;
        }

        $code = $_GET['code'] ?? $code;
        $shop = ShopService::getActiveShop();
        
        $variant = ShopService::findVariantByQR($code, (int)$shop['id']);
        if (!$variant) {
            Response::jsonError("Product variant not found for scanned QR code: $code", [], 404);
            return;
        }

        Response::jsonSuccess("Variant found", $variant);
    }

    /**
     * AJAX Endpoint: Customer Lookup by Mobile / Name
     */
    public function lookupCustomer(string $query = ''): void {
        if (!hasPermission('pos', 'view') && !hasPermission('pos', 'create') && !hasPermission('customers', 'view')) {
            Response::jsonError("Access denied: You do not have permission to lookup customers.", [], 403);
            return;
        }

        $query = trim($_GET['query'] ?? ($_GET['q'] ?? $query));
        if (empty($query)) {
            Response::jsonSuccess("Empty query", []);
            return;
        }

        $customers = Database::fetchAll(
            "SELECT id, name, phone, email, address, city, state, gst_number
             FROM customers 
             WHERE phone LIKE ? OR name LIKE ? 
             ORDER BY id DESC LIMIT 10",
            ['%' . $query . '%', '%' . $query . '%']
        );

        Response::jsonSuccess("Customers found", $customers);
    }

    /**
     * AJAX Endpoint: Search Products & Variants for In-place Exchange
     */
    public function ajaxSearchProducts(): void {
        $query = trim($_GET['q'] ?? ($_GET['query'] ?? ''));
        $shop = ShopService::getActiveShop();
        $shopId = (int)$shop['id'];

        $sql = "SELECT pv.id, pv.sku, pv.qr_code, pv.selling_price, pv.mrp,
                       p.id as product_id, p.name as product_name, p.code as product_code,
                       p.image_url as product_image, pv.image_url as variant_image,
                       sz.name as size_name, cl.name as color_name, cat.name as category_name,
                       COALESCE((SELECT SUM(quantity) FROM stock_balances sb 
                                 WHERE sb.variant_id = pv.id 
                                   AND sb.location_type = 'SHOP' 
                                   AND sb.location_id = ? 
                                   AND sb.stock_state = 'AVAILABLE'), 0) as available_stock
                FROM product_variants pv
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN sizes sz ON pv.size_id = sz.id
                LEFT JOIN colors cl ON pv.color_id = cl.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                WHERE pv.is_active = 1";
        $params = [$shopId];

        if (!empty($query)) {
            $sql .= " AND (p.name LIKE ? OR pv.sku LIKE ? OR pv.qr_code LIKE ? OR p.code LIKE ?)";
            $searchTerm = '%' . $query . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $sql .= " ORDER BY p.name ASC, sz.name ASC LIMIT 25";
        $products = Database::fetchAll($sql, $params);
        foreach ($products as &$prod) {
            ShopService::resolveVariantPrices($prod);
        }
        unset($prod);

        Response::jsonSuccess("Products found", $products);
    }

    /**
     * Process POS Sale Checkout
     */
    public function processSale(): void {
        if (!hasPermission('pos', 'create')) {
            Response::json(['success' => false, 'message' => 'Access denied: You do not have permission to process POS sales.'], 403);
            return;
        }

        $shop = ShopService::getActiveShop();
        $userId = Session::userId() ?? 1;

        // Read JSON or Form POST payload
        $rawInput = file_get_contents('php://input');
        $payload = json_decode($rawInput, true);
        if (!$payload) {
            $payload = $_POST;
        }

        $payload['shop_id'] = $shop['id'];

        // Strict validation: Customer Name, Phone Number, and Address are required
        $custPhone = trim($payload['customer_phone'] ?? '');
        $custName = trim($payload['customer_name'] ?? '');
        $custAddress = trim($payload['customer_address'] ?? '');

        if (empty($custPhone)) {
            $msg = "Customer Mobile / Phone Number is required.";
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                Response::jsonError($msg, [], 422);
                return;
            }
            throw new InvalidArgumentException($msg);
        }

        if (empty($custName)) {
            $msg = "Customer Name is required.";
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                Response::jsonError($msg, [], 422);
                return;
            }
            throw new InvalidArgumentException($msg);
        }

        if (empty($custAddress)) {
            $msg = "Customer Address is required.";
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                Response::jsonError($msg, [], 422);
                return;
            }
            throw new InvalidArgumentException($msg);
        }

        try {
            $result = ShopService::processPOSSale($payload, $userId);
            
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                Response::jsonSuccess("Sale processed successfully!", [
                    'sale_id'        => $result['sale_id'],
                    'invoice_number' => $result['invoice_number'],
                    'invoice_url'    => url('shop/pos/invoice/' . $result['sale_id']),
                ]);
            } else {
                Session::setFlash('success', "Sale #{$result['invoice_number']} completed successfully!");
                Response::redirect(url('shop/pos/invoice/' . $result['sale_id']));
            }
        } catch (Exception $e) {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                Response::jsonError($e->getMessage(), [], 400);
            } else {
                Session::setFlash('error', $e->getMessage());
                Response::redirect(url('shop/pos'));
            }
        }
    }

    /**
     * Printable Tax Invoice Receipt (Thermal & A4)
     */
    public function printInvoice(string $id): void {
        $details = ShopService::getInvoiceDetails((int)$id);
        if (!$details) {
            Response::error(404, "Invoice #$id not found.");
            return;
        }

        // ViewOnly layout for pure printing without navigation chrome
        Response::viewOnly('shop.pos.invoice', $details);
    }

    /**
     * Shop Stock Inventory List
     */
    public function inventoryList(): void {
        if (!hasPermission('shop_stock', 'view') && !hasPermission('shop', 'view')) {
            Response::error(403, 'Access denied: You do not have permission to view shop inventory.');
            return;
        }

        $requestedShopId = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : null;
        if ($requestedShopId !== null && $requestedShopId <= 0) {
            $requestedShopId = null;
        }
        $shop = $this->resolveInventoryShop($requestedShopId);
        if (!$shop) {
            Response::error(403, 'You do not have access to the selected shop inventory.');
            return;
        }
        $shops = function_exists('getScopedShops') ? getScopedShops() : [$shop];
        $shopId = (int)$shop['id'];

        $filters = [
            'search'      => trim($_GET['search'] ?? ''),
            'category_id' => $_GET['category_id'] ?? '',
            'status'      => $_GET['status'] ?? '',
            'sort'        => $_GET['sort'] ?? 'name_asc',
        ];

        $summary = ShopService::getShopInventorySummary($shopId);
        $inventory = ShopService::getShopInventory($shopId, $filters);
        $categories = Database::fetchAll(
            "SELECT filter_id, name
             FROM (
                 SELECT DISTINCT CONCAT('warehouse:', wc.id) COLLATE utf8mb4_unicode_ci as filter_id,
                                 CONVERT(wc.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as name
                 FROM products p
                 LEFT JOIN warehouse_sub_subcategories wssc ON wssc.id = p.warehouse_sub_subcategory_id
                 LEFT JOIN warehouse_subcategories wssc_parent ON wssc_parent.id = wssc.subcategory_id
                 LEFT JOIN warehouse_subcategories wsc ON wsc.id = COALESCE(p.warehouse_subcategory_id, wssc.subcategory_id)
                 JOIN warehouse_categories wc ON wc.id = COALESCE(p.warehouse_category_id, wsc.category_id, wssc_parent.category_id)
                 JOIN product_variants pv ON pv.product_id = p.id
                 JOIN stock_balances sb ON sb.variant_id = pv.id
                 WHERE sb.location_type = 'SHOP'
                   AND sb.location_id = ?
                   AND sb.stock_state = 'AVAILABLE'
                 UNION
                 SELECT DISTINCT CONCAT('product:', c.id) COLLATE utf8mb4_unicode_ci as filter_id,
                                 CONVERT(c.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as name
                 FROM categories c
                 JOIN products p ON p.category_id = c.id
                 JOIN product_variants pv ON pv.product_id = p.id
                 JOIN stock_balances sb ON sb.variant_id = pv.id
                 WHERE sb.location_type = 'SHOP'
                   AND sb.location_id = ?
                   AND sb.stock_state = 'AVAILABLE'
             ) shop_categories
             ORDER BY name ASC",
            [$shopId, $shopId]
        );
        $chartsData = ShopService::getShopInventoryChartsData($shopId);

        $allocations = Database::fetchAll(
            "SELECT wa.*, 
                    w.name as warehouse_name,
                    COALESCE((SELECT SUM(ai.requested_qty) FROM allocation_items ai WHERE ai.allocation_id = wa.id), 0) as total_quantity,
                    COALESCE((SELECT SUM(ai.received_qty) FROM allocation_items ai WHERE ai.allocation_id = wa.id), 0) as total_received_quantity,
                    COALESCE((SELECT SUM(ai.damaged_qty) FROM allocation_items ai WHERE ai.allocation_id = wa.id), 0) as total_damaged_quantity
             FROM warehouse_allocations wa
             LEFT JOIN warehouses w ON wa.warehouse_id = w.id
             WHERE (wa.destination_type = 'SHOP' AND wa.destination_id = ?) OR wa.shop_id = ?
             ORDER BY wa.id DESC",
            [$shopId, $shopId]
        );

        Response::view('shop.inventory.index', [
            'pageTitle'    => 'Shop Inventory & Stock Ledger',
            'activeModule' => 'shop',
            'activeMenu'   => 'shop-inventory',
            'breadcrumb'   => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Inventory'],
            ],
            'shop'         => $shop,
            'shops'        => $shops,
            'inventory'    => $inventory,
            'summary'      => $summary,
            'categories'   => $categories,
            'filters'      => $filters,
            'chartsData'   => $chartsData,
            'allocations'  => $allocations,
            'activeTab'    => $_GET['tab'] ?? 'stock',
        ]);
    }

    /**
     * Shop Stock Inventory Detail View
     */
    public function inventoryDetail(string $id): void {
        if (!hasPermission('shop_stock', 'view') && !hasPermission('shop', 'view')) {
            Response::error(403, 'Access denied: You do not have permission to view shop inventory details.');
            return;
        }

        $requestedShopId = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : null;
        if ($requestedShopId !== null && $requestedShopId <= 0) {
            $requestedShopId = null;
        }
        $shop = $this->resolveInventoryShop($requestedShopId);
        if (!$shop) {
            Response::error(403, 'You do not have access to the selected shop inventory.');
            return;
        }
        $shopId = (int)$shop['id'];
        $variantId = (int)$id;
        $item = ShopService::getShopInventoryItem($shopId, $variantId);

        if (!$item) {
            Session::setFlash('error', "Product variant not found in shop inventory.");
            Response::redirect(url('shop/inventory') . '?shop_id=' . $shopId);
            return;
        }

        // 1. Stock Ledger / Transactions for this variant at this shop
        $transactions = Database::fetchAll(
            "SELECT st.*, u.full_name as user_name
             FROM stock_transactions st
             LEFT JOIN users u ON st.user_id = u.id
             WHERE st.location_type = 'SHOP' AND st.location_id = ? AND st.variant_id = ?
             ORDER BY st.id DESC LIMIT 50",
            [$shopId, $variantId]
        );

        // 2. Inward Allocations & Dispatches History
        $inwardHistory = Database::fetchAll(
            "SELECT ai.*, wa.allocation_number, wa.created_at as alloc_date, wa.status as alloc_status,
                    w.name as warehouse_name, w.code as warehouse_code,
                    ao.outward_number, ao.dispatch_date, ao.vehicle_number, ao.transporter_name
             FROM allocation_items ai
             JOIN warehouse_allocations wa ON ai.allocation_id = wa.id
             LEFT JOIN warehouses w ON wa.warehouse_id = w.id
             LEFT JOIN allocation_outwards ao ON ao.allocation_id = wa.id
             WHERE ai.variant_id = ? 
               AND wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED') 
               AND (wa.shop_id = ? OR wa.destination_id = ? OR wa.destination_type = 'SHOP')
             ORDER BY wa.id DESC",
            [$variantId, $shopId, $shopId]
        );

        // 3. Shop POS Sales Orders History for this variant (strictly SHOP sales)
        $salesHistory = Database::fetchAll(
            "SELECT si.*, s.invoice_number, s.customer_id, s.status as sale_status, s.payment_status, s.payment_mode, s.created_at as sale_date,
                    s.discount_amount, s.tax_amount, s.total_amount as final_amount,
                    COALESCE(c.name, s.customer_name, 'Retail Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone
             FROM sale_items si
             JOIN sales s ON si.sale_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE si.variant_id = ? AND s.shop_id = ? AND s.sale_type = 'SHOP'
             ORDER BY s.created_at DESC",
            [$variantId, $shopId]
        );

        // 4. Shop Damage History
        $damageHistory = Database::fetchAll(
            "SELECT sd.*, u.full_name as reported_by_name
             FROM shop_damages sd
             LEFT JOIN users u ON sd.reported_by = u.id
             WHERE sd.shop_id = ? AND sd.variant_id = ?
             ORDER BY sd.id DESC",
            [$shopId, $variantId]
        );

        // 5. Shop Customer Returns History for this variant (strictly SHOP returns)
        $returnsHistory = Database::fetchAll(
            "SELECT ri.*, r.return_number, r.return_type, r.reason as return_reason, r.refund_amount, r.refund_mode, r.refund_status, r.status as return_status, r.created_at as return_date,
                    s.invoice_number as sale_invoice,
                    COALESCE(c.name, s.customer_name, 'Retail Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone
             FROM return_items ri
             JOIN returns r ON ri.return_id = r.id
             LEFT JOIN sales s ON r.sale_id = s.id
             LEFT JOIN customers c ON r.customer_id = c.id
             WHERE ri.variant_id = ? AND r.source_type = 'SHOP'
             ORDER BY r.created_at DESC",
            [$variantId]
        );

        // 6. Shop Customer Exchanges History for this variant (strictly SHOP exchanges)
        $exchangesHistory = Database::fetchAll(
            "SELECT ei.*, e.exchange_number, e.original_amount, e.new_amount, e.difference_amount, e.settlement_mode, e.payment_action, e.status as exchange_status, e.created_at as exchange_date,
                    s.invoice_number as sale_invoice, r.return_number,
                    COALESCE(c.name, s.customer_name, 'Retail Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone
             FROM exchange_items ei
             JOIN exchanges e ON ei.exchange_id = e.id
             LEFT JOIN returns r ON e.return_id = r.id
             LEFT JOIN sales s ON e.sale_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE ei.variant_id = ? AND e.source_type = 'SHOP'
             ORDER BY e.created_at DESC",
            [$variantId]
        );

        // 7. Warehouse Returns History (dispatched back to Central Warehouse)
        $warehouseReturnsHistory = Database::fetchAll(
            "SELECT wr.*, wri.returned_qty, wri.good_qty, wri.damaged_qty, wri.defective_qty, wri.classification,
                    w.name as destination_warehouse_name
             FROM warehouse_return_items wri
             JOIN warehouse_returns wr ON wri.warehouse_return_id = wr.id
             LEFT JOIN warehouses w ON wr.destination_warehouse_id = w.id
             WHERE wri.variant_id = ?
               AND (wr.source_type = 'SHOP' OR wr.source_id = ?)
               AND wr.status NOT IN ('CANCELLED')
             ORDER BY wr.id DESC",
            [$variantId, $shopId]
        );

        // Piece tags resolution
        $pieceTags = $item['individual_pieces'] ?? [];

        // Compute metrics
        $totalInwardQty     = array_sum(array_column($inwardHistory, 'received_qty'));
        $totalSoldQty       = array_sum(array_column($salesHistory, 'quantity'));
        $totalSoldAmt       = array_sum(array_column($salesHistory, 'total_price'));
        $totalReturnedQty   = array_sum(array_column($returnsHistory, 'quantity'));
        $totalExchangedQty  = count(array_filter($exchangesHistory, fn($e) => strtoupper($e['item_type'] ?? '') === 'NEW'));
        $totalWhReturnedQty = array_sum(array_column($warehouseReturnsHistory, 'returned_qty'));
        $totalDmgQty        = array_sum(array_column($damageHistory, 'quantity'));
        $totalDmgLoss       = array_sum(array_column($damageHistory, 'total_loss'));

        $metrics = [
            'total_inward_qty'      => $totalInwardQty,
            'total_sold_qty'        => $totalSoldQty,
            'total_sold_amt'        => $totalSoldAmt,
            'total_returned_qty'    => $totalReturnedQty,
            'total_exchanged_qty'   => $totalExchangedQty,
            'total_wh_returned_qty' => $totalWhReturnedQty,
            'total_dmg_qty'         => $totalDmgQty,
            'total_dmg_loss'        => $totalDmgLoss,
        ];

        Response::view('shop.inventory.detail', [
            'pageTitle'               => 'Product Stock Details: ' . ($item['sku'] ?? 'Stock Item'),
            'activeModule'            => 'shop',
            'activeMenu'              => 'shop-inventory',
            'breadcrumb'              => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Inventory', 'url' => url('shop/inventory') . '?shop_id=' . $shopId],
                ['label' => $item['sku'] ?? 'Product Detail'],
            ],
            'shop'                    => $shop,
            'item'                    => $item,
            'pieceTags'               => $pieceTags,
            'transactions'            => $transactions,
            'inwardHistory'           => $inwardHistory,
            'salesHistory'            => $salesHistory,
            'damageHistory'           => $damageHistory,
            'returnsHistory'          => $returnsHistory,
            'exchangesHistory'        => $exchangesHistory,
            'warehouseReturnsHistory' => $warehouseReturnsHistory,
            'metrics'                 => $metrics,
        ]);
    }

    /**
     * AJAX Product Detail Endpoint
     */
    public function ajaxProductDetail(string $id): void {
        if (!hasPermission('shop_stock', 'view') && !hasPermission('shop', 'view')) {
            Response::json(['success' => false, 'message' => 'Access denied: You do not have permission to view shop inventory.'], 403);
            return;
        }

        $requestedShopId = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : null;
        if ($requestedShopId !== null && $requestedShopId <= 0) {
            $requestedShopId = null;
        }
        $shop = $this->resolveInventoryShop($requestedShopId);
        if (!$shop) {
            Response::json(['success' => false, 'message' => 'You do not have access to the selected shop inventory.'], 403);
            return;
        }
        $variantId = (int)$id;
        $item = ShopService::getShopInventoryItem((int)$shop['id'], $variantId);

        if (!$item) {
            Response::json(['success' => false, 'message' => 'Product variant not found.'], 404);
            return;
        }

        Response::json(['success' => true, 'item' => $item]);
    }

    /**
     * Submit Warehouse Stock Indent Request
     */
    public function requestStock(): void {
        if (!hasPermission('shop_stock', 'request') && !hasPermission('shop_stock', 'create') && !hasPermission('shop_requests', 'create')) {
            Session::setFlash('error', 'Access denied: You do not have permission to create stock requests.');
            Response::redirect(url('shop/inventory'));
            return;
        }

        $requestedShopId = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : null;
        if ($requestedShopId !== null && $requestedShopId <= 0) {
            $requestedShopId = null;
        }
        $shop = $this->resolveInventoryShop($requestedShopId);
        if (!$shop) {
            Response::error(403, 'You do not have access to the selected shop inventory.');
            return;
        }
        $shopQuery = '?shop_id=' . (int)$shop['id'];
        $userId = Session::userId() ?? 1;
        $items = $_POST['items'] ?? [];
        $notes = $_POST['notes'] ?? 'Shop stock transfer request';

        try {
            $indentNumber = ShopService::createStockIndent((int)$shop['id'], $items, $notes, $userId);
            Session::setFlash('success', "Stock request $indentNumber submitted to Warehouse.");
        } catch (Exception $e) {
            Session::setFlash('error', "Failed to submit stock request: " . $e->getMessage());
        }

        Response::redirect(url('shop/inventory') . $shopQuery);
    }

    /**
     * Update Product Retail Selling Price & MRP from Shop Inventory
     */
    public function updateProductPrice(): void {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                  (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) ||
                  isset($_POST['is_ajax']) || isset($_GET['ajax']);

        if (!hasPermission('shop_stock', 'edit') && !hasPermission('shop_stock', 'edit_price')) {
            if ($isAjax) {
                Response::json(['success' => false, 'message' => 'Access denied: You do not have permission to edit product price.'], 403);
                return;
            }
            Session::setFlash('error', 'Access denied: You do not have permission to edit product price.');
            Response::redirect(url('shop/inventory'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $userId = Session::userId() ?? 1;

        $variantIds = [];
        if (!empty($_POST['variant_ids'])) {
            if (is_array($_POST['variant_ids'])) {
                $variantIds = array_map('intval', $_POST['variant_ids']);
            } else {
                $variantIds = array_map('intval', explode(',', (string)$_POST['variant_ids']));
            }
        } elseif (!empty($_POST['variant_id'])) {
            $variantIds = [(int)$_POST['variant_id']];
        }
        $variantIds = array_values(array_unique(array_filter($variantIds, fn($id) => $id > 0)));

        $sellingPrice = (float)($_POST['selling_price'] ?? 0);
        $mrp = isset($_POST['mrp']) && $_POST['mrp'] !== '' ? (float)$_POST['mrp'] : null;
        $notes = trim($_POST['notes'] ?? $_POST['revision_notes'] ?? 'Shop price adjustment');

        if (empty($variantIds)) {
            if ($isAjax) {
                Response::json(['success' => false, 'message' => 'Please select at least one product variant.'], 400);
                return;
            }
            Session::setFlash('error', 'Please select at least one product variant.');
            Response::redirect(url('shop/inventory'));
            return;
        }

        try {
            $results = ShopService::updateProductPrices($variantIds, $sellingPrice, $mrp, $userId, $notes);
            $first = $results[0] ?? [];
            $count = count($results);
            $msg = $count > 1 
                ? "Price updated successfully for {$count} products." 
                : ("Price updated successfully for SKU " . ($first['sku'] ?? '') . ": Selling Price is now " . ($first['formatted_price'] ?? '') . (!empty($first['new_mrp']) && $first['new_mrp'] > $first['new_selling_price'] ? " (MRP: " . $first['formatted_mrp'] . ")" : ""));

            if ($isAjax) {
                Response::json([
                    'success'          => true,
                    'message'          => $msg,
                    'count'            => $count,
                    'data'             => $first,
                    'updated_variants' => $results,
                ]);
                return;
            }

            Session::setFlash('success', $msg);
        } catch (Exception $e) {
            if ($isAjax) {
                Response::json(['success' => false, 'message' => $e->getMessage()], 400);
                return;
            }
            Session::setFlash('error', 'Failed to update price: ' . $e->getMessage());
        }

        $redirectUrl = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : url('shop/inventory');
        Response::redirect($redirectUrl);
    }

    /**
    /**
     * Returns & Exchanges Dashboard for Shop Retail Module
     */
    public function returnList(): void {
        if (!hasPermission('shop_returns', 'view')) {
            Session::setFlash('error', 'Access denied: You do not have permission to view customer returns.');
            Response::redirect(url('shop/dashboard'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $shopId = (int)($shop['id'] ?? 1);

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $returnType = trim($_GET['type'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $where = ["(s.sale_type = 'SHOP' OR (s.sale_type IS NULL AND s.shop_id = {$shopId}))"];
        $where[] = "(r.source_type = 'SHOP' OR r.source_type IS NULL)";
        $params = [];

        if (!empty($search)) {
            $where[] = "(r.return_number LIKE ? OR s.invoice_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR s.customer_name LIKE ? OR s.customer_phone LIKE ?)";
            $sWild = "%{$search}%";
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
        }

        if (!empty($status)) {
            $where[] = "r.status = ?";
            $params[] = $status;
        }

        if (!empty($returnType)) {
            $where[] = "r.return_type = ?";
            $params[] = $returnType;
        }

        if (!empty($dateFrom)) {
            $where[] = "DATE(r.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $where[] = "DATE(r.created_at) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = implode(' AND ', $where);

        $returns = Database::fetchAll(
            "SELECT r.*, 
                    s.invoice_number as sale_invoice,
                    s.sale_type,
                    COALESCE(c.name, s.customer_name, 'Shop Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone,
                    (SELECT COALESCE(SUM(quantity), 0) FROM return_items WHERE return_id = r.id) as total_returned_qty,
                    (SELECT COALESCE(SUM(quantity), 0) FROM return_items WHERE return_id = r.id AND item_condition = 'DAMAGED') as damaged_qty,
                    e.id as exchange_id,
                    e.exchange_number,
                    e.difference_amount,
                    e.payment_action
             FROM returns r
             JOIN sales s ON r.sale_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             LEFT JOIN exchanges e ON e.return_id = r.id
             WHERE {$whereClause}
             ORDER BY r.created_at DESC",
            $params
        );

        $totalReturnsCount = 0;
        $totalExchangesCount = 0;
        $totalRefundAmount = 0.0;
        $totalDamagedQty = 0;

        foreach ($returns as $row) {
            if ($row['return_type'] === 'EXCHANGE') {
                $totalExchangesCount++;
            } else {
                $totalReturnsCount++;
            }
            $totalRefundAmount += (float)($row['refund_amount'] ?? 0);
            $totalDamagedQty += (int)($row['damaged_qty'] ?? 0);
        }

        $catalogVariants = Database::fetchAll(
            "SELECT pv.id, pv.sku, pv.qr_code, pv.mrp,
                    COALESCE(NULLIF(pv.selling_price, 0), pv.mrp) as selling_price,
                    p.name as product_name, p.code as product_code,
                    COALESCE(sz.name, 'Free Size') as size_name,
                    COALESCE(clr.name, 'Standard') as color_name,
                    COALESCE((SELECT SUM(quantity) FROM stock_balances WHERE variant_id = pv.id AND location_type = 'SHOP' AND location_id = ? AND stock_state = 'AVAILABLE'), 0) as available_stock
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors clr ON pv.color_id = clr.id
             WHERE p.is_active = 1
             ORDER BY p.name ASC, sz.sort_order ASC",
            [$shopId]
        );

        Response::view('shop.returns.index', [
            'pageTitle'           => 'Shop Returns & Exchanges',
            'activeModule'        => 'shop',
            'activeMenu'          => 'shop-returns',
            'breadcrumb'          => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Returns & Exchanges']
            ],
            'shop'                => $shop,
            'returns'             => $returns,
            'totalReturnsCount'   => $totalReturnsCount,
            'totalExchangesCount' => $totalExchangesCount,
            'totalRefundAmount'   => $totalRefundAmount,
            'totalDamagedQty'     => $totalDamagedQty,
            'catalogVariants'     => $catalogVariants,
            'search'              => $search,
            'status'              => $status,
            'returnType'          => $returnType,
            'dateFrom'            => $dateFrom,
            'dateTo'              => $dateTo,
        ]);
    }

    /**
     * AJAX Search for Shop Sales Orders for Return/Exchange lookup
     */
    private function getShopSaleItemPaidUnitPrice(array $saleItem): float {
        $quantity = max(1, (int)($saleItem['quantity'] ?? 1));
        $lineTotal = (float)($saleItem['total_amount'] ?? 0);
        if ($lineTotal > 0 || (float)($saleItem['unit_price'] ?? 0) <= 0) {
            return round($lineTotal / $quantity, 2);
        }

        return max(0.0, round(
            (float)$saleItem['unit_price'] - (float)($saleItem['discount_amount'] ?? 0) / $quantity,
            2
        ));
    }

    private function findShopDamageForPieceTag(int $shopId, int $variantId, string $tagCode): ?array {
        $tagCode = trim($tagCode, " \t\n\r\0\x0B\"'");
        if ($tagCode === '') {
            return null;
        }

        $damageRecords = Database::fetchAll(
            "SELECT damage_number, tag_code
             FROM shop_damages
             WHERE shop_id = ? AND variant_id = ? AND status <> 'RESTOCKED'
               AND tag_code IS NOT NULL AND tag_code <> ''",
            [$shopId, $variantId]
        );

        foreach ($damageRecords as $damageRecord) {
            $storedTags = (string)$damageRecord['tag_code'];
            $decodedTags = json_decode($storedTags, true);
            if (is_array($decodedTags)) {
                $storedTags = array_map(
                    static fn($tag): string => is_array($tag)
                        ? (string)($tag['tag_code'] ?? $tag['qr_code'] ?? '')
                        : (string)$tag,
                    $decodedTags
                );
            } else {
                $storedTags = preg_split('/[,|]/', $storedTags) ?: [];
            }

            foreach ($storedTags as $storedTag) {
                if (strcasecmp(trim((string)$storedTag, " \t\n\r\0\x0B\"'"), $tagCode) === 0) {
                    return $damageRecord;
                }
            }
        }

        return null;
    }

    private function findShopBilledPieceTag(int $shopId, int $variantId, string $tagCode): ?array {
        $tagCode = trim($tagCode, " \t\n\r\0\x0B\"'");
        if ($tagCode === '') {
            return null;
        }

        $saleItems = Database::fetchAll(
            "SELECT s.invoice_number, si.scanned_qr_codes, si.qr_code
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             WHERE s.shop_id = ? AND s.sale_type = 'SHOP' AND s.status <> 'CANCELLED'
               AND si.variant_id = ?
               AND (si.scanned_qr_codes LIKE ? OR si.qr_code = ?)",
            [$shopId, $variantId, '%' . $tagCode . '%', $tagCode]
        );

        foreach ($saleItems as $saleItem) {
            if (strcasecmp(trim((string)($saleItem['qr_code'] ?? ''), " \t\n\r\0\x0B\"'"), $tagCode) === 0) {
                return $saleItem;
            }

            $storedTags = (string)($saleItem['scanned_qr_codes'] ?? '');
            $decodedTags = json_decode($storedTags, true);
            if (is_array($decodedTags)) {
                $storedTags = array_map(
                    static fn($tag): string => is_array($tag)
                        ? (string)($tag['tag_code'] ?? $tag['qr_code'] ?? '')
                        : (string)$tag,
                    $decodedTags
                );
            } else {
                $storedTags = preg_split('/[,|]/', $storedTags) ?: [];
            }

            foreach ($storedTags as $storedTag) {
                if (strcasecmp(trim((string)$storedTag, " \t\n\r\0\x0B\"'"), $tagCode) === 0) {
                    return $saleItem;
                }
            }
        }

        return null;
    }

    public function ajaxReturnsLookup(): void {
        $term = trim($_REQUEST['q'] ?? $_REQUEST['query'] ?? $_REQUEST['term'] ?? $_REQUEST['invoice_number'] ?? $_REQUEST['search'] ?? '');
        $requestedSaleId = (int)($_REQUEST['sale_id'] ?? 0);
        if (empty($term) && $requestedSaleId <= 0) {
            Response::jsonError("Please provide a shop invoice number, customer phone, or piece QR tag.");
            return;
        }

        $shop = ShopService::getActiveShop();
        $shopId = (int)($shop['id'] ?? 1);

        $saleSql = "SELECT s.*,
                    COALESCE(c.name, s.customer_name, 'Shop Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone,
                    c.email as customer_email,
                    EXISTS (
                        SELECT 1
                        FROM returns r
                        LEFT JOIN exchanges e ON e.return_id = r.id
                        WHERE r.sale_id = s.id
                          AND r.source_type = 'SHOP'
                          AND r.status <> 'CANCELLED'
                          AND (e.id IS NULL OR e.status <> 'CANCELLED')
                    ) as return_exchange_used
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
        ";
        if ($requestedSaleId > 0) {
            $sales = Database::fetchAll(
                $saleSql . " WHERE s.id = ? AND s.sale_type = 'SHOP' AND s.shop_id = ? LIMIT 1",
                [$requestedSaleId, $shopId]
            );
        } else {
            $sales = Database::fetchAll(
                $saleSql . " WHERE (
                    s.invoice_number = ?
                    OR s.invoice_number LIKE ?
                    OR c.phone = ?
                    OR c.phone LIKE ?
                    OR s.customer_phone = ?
                    OR s.customer_phone LIKE ?
                    OR c.name LIKE ?
                    OR s.customer_name LIKE ?
                    OR s.id IN (
                        SELECT si_sub.sale_id FROM sale_items si_sub
                        WHERE si_sub.scanned_qr_codes LIKE ?
                           OR si_sub.scanned_qr_codes = ?
                           OR si_sub.qr_code LIKE ?
                           OR si_sub.qr_code = ?
                    )
                )
                AND s.sale_type = 'SHOP' AND s.shop_id = ?
                ORDER BY s.id DESC",
                [
                    $term, "%{$term}%",
                    $term, "%{$term}%",
                    $term, "%{$term}%",
                    "%{$term}%", "%{$term}%",
                    "%{$term}%", $term,
                    "%{$term}%", $term,
                    $shopId
                ]
            );
        }

        if (!$sales) {
            Response::jsonError("No shop billed order found for search: " . htmlspecialchars($term));
            return;
        }

        if (count($sales) > 1) {
            $invoiceChoices = array_map(
                static fn(array $matchedSale): array => [
                    'invoice_list_item' => true,
                    'sale' => $matchedSale,
                ],
                $sales
            );
            Response::jsonSuccess('Multiple shop invoices found. Select the invoice to continue.', $invoiceChoices);
            return;
        }

        $sale = $sales[0];
        $items = Database::fetchAll(
            "SELECT si.*, 
                    p.id as product_id,
                    p.name as product_name, p.code as product_code,
                    pv.sku, pv.qr_code, pv.mrp,
                    COALESCE(sz.name, 'Free Size') as size_name,
                    COALESCE(clr.name, 'Standard') as color_name,
                    COALESCE((SELECT SUM(quantity) FROM stock_balances WHERE variant_id = pv.id AND location_type = 'SHOP' AND location_id = ? AND stock_state = 'AVAILABLE'), 0) as available_stock
             FROM sale_items si
             JOIN product_variants pv ON si.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors clr ON pv.color_id = clr.id
             WHERE si.sale_id = ?",
            [$shopId, (int)$sale['id']]
        );

        foreach ($items as &$it) {
            $returnedQty = (int)(Database::fetchOne("SELECT COALESCE(SUM(quantity), 0) as total FROM return_items WHERE sale_item_id = ?", [$it['id']])['total'] ?? 0);
            $it['returnable_quantity'] = max(0, (int)$it['quantity'] - $returnedQty);
            $it['unit_price'] = $this->getShopSaleItemPaidUnitPrice($it);
        }
        unset($it);

        Response::jsonSuccess("Shop Sale record loaded successfully", [
            [
                'sale'  => $sale,
                'items' => $items
            ]
        ]);
    }

    /**
     * AJAX Lookup for Return & Exchange Piece Tag / Barcode / SKU
     */
    public function ajaxLookupReturnPiece(): void {
        header('Content-Type: application/json');
        $rawCode = trim($_REQUEST['query'] ?? $_REQUEST['q'] ?? $_REQUEST['code'] ?? $_REQUEST['search'] ?? '');
        if (empty($rawCode)) {
            Response::jsonError("Please provide a piece tag, QR barcode, or SKU.");
            return;
        }

        $shop = ShopService::getActiveShop();
        $shopId = (int)($shop['id'] ?? 1);
        $lookupPurpose = strtolower(trim((string)($_REQUEST['purpose'] ?? '')));
        if ($lookupPurpose === 'replacement') {
            $replacement = $this->findExactShopReplacement((string)$rawCode, $shopId);
            if (!$replacement) {
                Response::jsonError('Scanned replacement was not found as an available product in this shop.');
                return;
            }
            $billedSale = $this->findShopBilledPieceTag(
                $shopId,
                (int)$replacement['id'],
                (string)$replacement['matched_scan_code']
            );
            if ($billedSale) {
                Response::jsonError(
                    "This product piece was already billed"
                    . (!empty($billedSale['invoice_number']) ? " in invoice {$billedSale['invoice_number']}" : '')
                    . ' and cannot be scanned as a replacement.'
                );
                return;
            }
            $damageRecord = $this->findShopDamageForPieceTag(
                $shopId,
                (int)$replacement['id'],
                (string)$replacement['matched_scan_code']
            );
            if ($damageRecord) {
                Response::jsonError(
                    "This piece tag is allocated to Damage Management"
                    . (!empty($damageRecord['damage_number']) ? " ({$damageRecord['damage_number']})" : '')
                    . ' and cannot be used as a replacement.'
                );
                return;
            }
            if ((float)$replacement['available_stock'] <= 0) {
                Response::jsonError('This replacement is not available in shop inventory.');
                return;
            }
            Response::jsonSuccess('Scanned replacement identified', [
                'variant' => $replacement,
                'tag_code' => $replacement['matched_scan_code'],
            ]);
            return;
        }

        $saleId = (int)($_REQUEST['sale_id'] ?? 0);

        $rawCode = trim($rawCode, " \t\n\r\0\x0B\"'");
        $matchedTagCode = $rawCode;

        if ($saleId > 0) {
            $scanCandidates = [$rawCode];
            if (str_starts_with($rawCode, '{') && str_ends_with($rawCode, '}')) {
                $scanPayload = json_decode($rawCode, true);
                if (is_array($scanPayload)) {
                    foreach (['tag', 'tag_code', 'code', 'qr_code', 'sku'] as $field) {
                        if (!empty($scanPayload[$field])) {
                            $scanCandidates[] = trim((string)$scanPayload[$field]);
                        }
                    }
                }
            } elseif (str_contains($rawCode, '|')) {
                foreach (explode('|', $rawCode) as $part) {
                    $part = trim($part);
                    if ($part !== '' && !preg_match('/^(MRP|Disc|Cost|Price):/i', $part)) {
                        $scanCandidates[] = $part;
                    }
                }
            }
            $scanCandidates = array_values(array_unique(array_filter($scanCandidates)));

            $sale = Database::fetchOne(
                "SELECT id FROM sales WHERE id = ? AND sale_type = 'SHOP' AND shop_id = ?",
                [$saleId, $shopId]
            );
            if (!$sale) {
                Response::jsonError('The selected invoice is not available in the active shop.');
                return;
            }

            $saleItems = Database::fetchAll(
                "SELECT si.id as sale_item_id, si.variant_id, si.quantity, si.scanned_qr_codes, si.qr_code as sale_qr_code,
                        pv.sku, pv.qr_code, p.code as product_code,
                        p.name as product_name, pv.selling_price, pv.mrp,
                        COALESCE(sz.name, 'Free Size') as size_name,
                        COALESCE(clr.name, 'Standard') as color_name
                 FROM sale_items si
                 JOIN product_variants pv ON pv.id = si.variant_id
                 JOIN products p ON p.id = pv.product_id
                 LEFT JOIN sizes sz ON sz.id = pv.size_id
                 LEFT JOIN colors clr ON clr.id = pv.color_id
                 WHERE si.sale_id = ?",
                [$saleId]
            );

            foreach ($saleItems as $saleItem) {
                $scanMatches = false;
                foreach ($scanCandidates as $scanCandidate) {
                    foreach ([$saleItem['sku'], $saleItem['qr_code'], $saleItem['sale_qr_code']] as $candidate) {
                        if ($candidate !== null && strcasecmp(trim((string)$candidate), $scanCandidate) === 0) {
                            $scanMatches = true;
                            $matchedTagCode = $scanCandidate;
                            break 2;
                        }
                    }
                }

                $savedTags = trim((string)($saleItem['scanned_qr_codes'] ?? ''));
                if (!$scanMatches && $savedTags !== '') {
                    $decodedTags = json_decode($savedTags, true);
                    if (is_array($decodedTags)) {
                        $savedTags = array_map(
                            static fn($tag) => is_array($tag)
                                ? (string)($tag['tag_code'] ?? $tag['qr_code'] ?? '')
                                : (string)$tag,
                            $decodedTags
                        );
                    } else {
                        $savedTags = preg_split('/[,|]/', $savedTags) ?: [];
                    }
                    foreach ($savedTags as $savedTag) {
                        foreach ($scanCandidates as $scanCandidate) {
                            if (strcasecmp(trim((string)$savedTag, " \t\n\r\0\x0B\"'"), $scanCandidate) === 0) {
                                $scanMatches = true;
                                $matchedTagCode = $scanCandidate;
                                break 2;
                            }
                        }
                    }
                }

                if (!$scanMatches) {
                    continue;
                }

                $damageRecord = $this->findShopDamageForPieceTag(
                    $shopId,
                    (int)$saleItem['variant_id'],
                    $matchedTagCode
                );
                if ($damageRecord) {
                    Response::jsonError(
                        "Piece tag '{$matchedTagCode}' is allocated to Damage Management"
                        . (!empty($damageRecord['damage_number']) ? " ({$damageRecord['damage_number']})" : '')
                        . ' and cannot be returned or exchanged.'
                    );
                    return;
                }

                $returnedQty = Database::fetchOne(
                    "SELECT COALESCE(SUM(quantity), 0) as returned_quantity
                     FROM return_items WHERE sale_item_id = ?",
                    [(int)$saleItem['sale_item_id']]
                );
                $returnableQty = (int)$saleItem['quantity'] - (int)($returnedQty['returned_quantity'] ?? 0);
                if ($returnableQty <= 0) {
                    Response::jsonError('This invoice item has already been returned in full.');
                    return;
                }

                $variant = $saleItem;
                $variant['id'] = (int)$saleItem['variant_id'];
                $variant['variant_id'] = (int)$saleItem['variant_id'];
                $variant['selling_price'] = (float)($saleItem['selling_price'] ?? $saleItem['mrp'] ?? 0);
                Response::jsonSuccess('Invoice item identified', [
                    'variant' => $variant,
                    'data' => $variant,
                    'sale_item_id' => (int)$saleItem['sale_item_id'],
                    'tag_code' => $matchedTagCode,
                ]);
                return;
            }

            Response::jsonError('This piece tag or SKU is not on the selected shop invoice.');
            return;
        }

        // Strategy 1: ShopService findVariantByQR (handles JSON, pipe-delimited, piece tags, allocations, etc.)
        $foundVariant = ShopService::findVariantByQR($rawCode, $shopId);

        // Strategy 2: Search allocation_items.selected_qr_codes (JSON array or comma delimited)
        if (!$foundVariant) {
            $allocItem = Database::fetchOne(
                "SELECT ai.variant_id, ai.selected_qr_codes
                 FROM allocation_items ai
                 WHERE (ai.selected_qr_codes LIKE ? 
                    OR ai.selected_qr_codes LIKE ? 
                    OR ai.selected_qr_codes = ?
                    OR FIND_IN_SET(?, REPLACE(COALESCE(ai.selected_qr_codes, ''), ' ', '')) > 0)
                 ORDER BY ai.id DESC LIMIT 1",
                ['%' . $rawCode . '%', '%' . $rawCode . ',%', $rawCode, $rawCode]
            );

            if ($allocItem && !empty($allocItem['variant_id'])) {
                $foundVariant = Database::fetchOne(
                    "SELECT pv.*, p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                            c.name as color_name, s.name as size_name
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN colors c ON pv.color_id = c.id
                     LEFT JOIN sizes s ON pv.size_id = s.id
                     WHERE pv.id = ? LIMIT 1",
                    [$allocItem['variant_id']]
                );
            }
        }

        // Strategy 3: Search sale_items.scanned_qr_codes
        if (!$foundVariant) {
            $saleItem = Database::fetchOne(
                "SELECT si.variant_id 
                 FROM sale_items si
                 WHERE (si.scanned_qr_codes LIKE ? 
                    OR si.scanned_qr_codes = ?
                    OR si.qr_code = ?)
                 ORDER BY si.id DESC LIMIT 1",
                ['%' . $rawCode . '%', $rawCode, $rawCode]
            );
            if ($saleItem && !empty($saleItem['variant_id'])) {
                $foundVariant = Database::fetchOne(
                    "SELECT pv.*, p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                            c.name as color_name, s.name as size_name
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN colors c ON pv.color_id = c.id
                     LEFT JOIN sizes s ON pv.size_id = s.id
                     WHERE pv.id = ? LIMIT 1",
                    [$saleItem['variant_id']]
                );
            }
        }

        // Strategy 4: Direct match on SKU, QR code, Product code
        if (!$foundVariant) {
            $foundVariant = Database::fetchOne(
                "SELECT pv.*, p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                        c.name as color_name, s.name as size_name
                 FROM product_variants pv
                 JOIN products p ON pv.product_id = p.id
                 LEFT JOIN colors c ON pv.color_id = c.id
                 LEFT JOIN sizes s ON pv.size_id = s.id
                 WHERE pv.sku = ? OR pv.qr_code = ? OR p.code = ? OR pv.sku LIKE ? OR pv.qr_code LIKE ?
                 LIMIT 1",
                [$rawCode, $rawCode, $rawCode, "%{$rawCode}%", "%{$rawCode}%"]
            );
        }

        if (!$foundVariant) {
            Response::jsonError("Piece tag or SKU '{$rawCode}' not found in catalog.");
            return;
        }

        $availableStock = StockService::getBalance((int)$foundVariant['id'], 'SHOP', $shopId, 'AVAILABLE');
        if (strtolower(trim((string)($_REQUEST['purpose'] ?? ''))) === 'replacement' && $availableStock <= 0) {
            Response::jsonError('This replacement product is not currently available in shop inventory.');
            return;
        }

        $foundVariant['variant_id'] = (int)$foundVariant['id'];
        $foundVariant['selling_price'] = (float)($foundVariant['selling_price'] ?? $foundVariant['price'] ?? $foundVariant['mrp'] ?? 0);
        $foundVariant['available_stock'] = $availableStock;

        Response::jsonSuccess("Piece identified", [
            'variant'   => $foundVariant,
            'data'      => $foundVariant,
            'tag_code'  => $matchedTagCode,
        ]);
    }

    /**
     * Process Shop Return or Exchange
     */
    public function processReturn(): void {
        CSRF::verify();

        if (!hasPermission('shop_returns', 'create') && !hasPermission('shop_returns', 'process')) {
            Session::flash('error', 'Access denied: You do not have permission to process returns or exchanges.');
            Response::redirect(url('shop/returns'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $shopId = (int)($shop['id'] ?? 1);
        $userId = Auth::id() ?? Session::userId() ?? 1;

        $saleId = (int)($_POST['sale_id'] ?? 0);
        $returnType = strtoupper(trim($_POST['return_type'] ?? 'RETURN'));
        $reason = trim($_POST['reason'] ?? 'Shop Customer Request');
        $refundMethod = strtoupper(trim($_POST['refund_mode'] ?? $_POST['refund_method'] ?? 'CASH'));
        $items = $_POST['items'] ?? [];
        $replacements = $_POST['replacement_items'] ?? [];

        if (!$saleId || empty($items) || !is_array($items)) {
            Session::flash('error', 'Invalid Shop return request payload. Please select items to return.');
            Response::redirect(url('shop/returns'));
            return;
        }

        $allowedReturnTypes = ['RETURN', 'EXCHANGE', 'EXCHANGE_SAME', 'EXCHANGE_DIFFERENT'];
        if (!in_array($returnType, $allowedReturnTypes, true)) {
            Session::flash('error', 'Please select a valid return or exchange action.');
            Response::redirect(url('shop/returns'));
            return;
        }

        try {
            if (!Database::beginTransaction()) {
                throw new RuntimeException('Unable to start the return transaction. Please try again.');
            }
            $sale = Database::fetchOne(
                "SELECT * FROM sales
                 WHERE id = ? AND sale_type = 'SHOP' AND shop_id = ?
                 FOR UPDATE",
                [$saleId, $shopId]
            );
            if (!$sale) {
                throw new RuntimeException('Shop sale record was not found in the active shop.');
            }

            $previousReturn = Database::fetchOne(
                "SELECT r.return_number
                 FROM returns r
                 LEFT JOIN exchanges e ON e.return_id = r.id
                 WHERE r.sale_id = ? AND r.source_type = 'SHOP'
                   AND r.status <> 'CANCELLED'
                   AND (e.id IS NULL OR e.status <> 'CANCELLED')
                 ORDER BY r.id DESC
                 LIMIT 1",
                [$saleId]
            );
            if ($previousReturn) {
                throw new RuntimeException('This invoice has already been used for a return or exchange. Each invoice can be processed only once.');
            }

            $normalizedItems = [];
            $totalReturnVal = 0.0;
            $totalReplacementVal = 0.0;
            $replacementStockRequirements = [];
            $normalizedReplacements = [];

            foreach ($items as $itemKey => $item) {
                if (!is_array($item)) {
                    throw new RuntimeException('Invalid return item data.');
                }

                $saleItemId = (int)($item['sale_item_id'] ?? (is_numeric($itemKey) ? $itemKey : 0));
                $qty = (int)($item['qty'] ?? $item['quantity'] ?? 0);
                if ($saleItemId <= 0 || $qty <= 0) {
                    throw new RuntimeException('Each selected return item must have a valid invoice line and quantity.');
                }

                $saleItem = Database::fetchOne(
                    "SELECT si.*, pv.product_id
                     FROM sale_items si
                     JOIN product_variants pv ON pv.id = si.variant_id
                     WHERE si.id = ? AND si.sale_id = ?",
                    [$saleItemId, $saleId]
                );
                if (!$saleItem) {
                    throw new RuntimeException('A selected item does not belong to this shop invoice.');
                }

                $submittedVariantId = (int)($item['variant_id'] ?? 0);
                if ($submittedVariantId > 0 && $submittedVariantId !== (int)$saleItem['variant_id']) {
                    throw new RuntimeException('A selected item does not match the product on this invoice.');
                }

                $previouslyReturned = Database::fetchOne(
                    "SELECT COALESCE(SUM(quantity), 0) as returned_quantity
                     FROM return_items WHERE sale_item_id = ?",
                    [$saleItemId]
                );
                $returnableQty = (int)$saleItem['quantity'] - (int)($previouslyReturned['returned_quantity'] ?? 0);
                if ($qty > $returnableQty) {
                    throw new RuntimeException("Return quantity exceeds the remaining quantity on invoice line {$saleItemId}.");
                }

                $submittedPieceTags = $item['piece_tags'] ?? [];
                if (!is_array($submittedPieceTags)) {
                    $submittedPieceTags = preg_split('/[,|]/', (string)$submittedPieceTags) ?: [];
                }
                foreach ($submittedPieceTags as $pieceTag) {
                    $damageRecord = $this->findShopDamageForPieceTag(
                        $shopId,
                        (int)$saleItem['variant_id'],
                        (string)$pieceTag
                    );
                    if ($damageRecord) {
                        throw new RuntimeException(
                            "Piece tag '{$pieceTag}' is allocated to Damage Management"
                            . (!empty($damageRecord['damage_number']) ? " ({$damageRecord['damage_number']})" : '')
                            . ' and cannot be returned or exchanged.'
                        );
                    }
                }

                $unitPrice = $this->getShopSaleItemPaidUnitPrice($saleItem);
                $item['sale_item_id'] = $saleItemId;
                $item['variant_id'] = (int)$saleItem['variant_id'];
                $item['qty'] = $qty;
                $item['unit_price'] = $unitPrice;
                $item['condition'] = strtoupper(trim((string)($item['condition'] ?? $item['item_condition'] ?? 'GOOD')));
                if (!in_array($item['condition'], ['GOOD', 'DAMAGED', 'DEFECTIVE'], true)) {
                    $item['condition'] = 'GOOD';
                }

                if ($returnType === 'EXCHANGE_SAME') {
                    $swapVariantId = (int)($item['exchange_variant_id'] ?? 0);
                    $replacementTag = trim((string)($item['replacement_tag'] ?? ''));
                    if ($swapVariantId <= 0 || $replacementTag === '') {
                        throw new RuntimeException('Scan an available replacement product for every item being exchanged.');
                    }
                    $billedSale = $this->findShopBilledPieceTag($shopId, $swapVariantId, $replacementTag);
                    if ($billedSale) {
                        throw new RuntimeException(
                            "Replacement piece '{$replacementTag}' was already billed"
                            . (!empty($billedSale['invoice_number']) ? " in invoice {$billedSale['invoice_number']}" : '')
                            . ' and cannot be exchanged.'
                        );
                    }
                    $damageRecord = $this->findShopDamageForPieceTag($shopId, $swapVariantId, $replacementTag);
                    if ($damageRecord) {
                        throw new RuntimeException(
                            "Replacement piece tag '{$replacementTag}' is allocated to Damage Management"
                            . (!empty($damageRecord['damage_number']) ? " ({$damageRecord['damage_number']})" : '')
                            . ' and cannot be exchanged.'
                        );
                    }
                    $scannedReplacement = $this->findExactShopReplacement($replacementTag, $shopId);
                    if (!$scannedReplacement || (int)$scannedReplacement['id'] !== $swapVariantId) {
                        throw new RuntimeException('The replacement product must match the product scanned for this exchange.');
                    }
                    $swapVariant = Database::fetchOne(
                        "SELECT id, product_id, COALESCE(NULLIF(selling_price, 0), mrp, 0) as exchange_price
                         FROM product_variants WHERE id = ? AND is_active = 1",
                        [$swapVariantId]
                    );
                    if (!$swapVariant || (int)$swapVariant['product_id'] !== (int)$saleItem['product_id']) {
                        throw new RuntimeException('A same-product exchange must use an active size/color variant of the returned product.');
                    }
                    $item['exchange_variant_id'] = $swapVariantId;
                    $item['exchange_unit_price'] = (float)$swapVariant['exchange_price'];
                    $replacementStockRequirements[$swapVariantId] = ($replacementStockRequirements[$swapVariantId] ?? 0) + $qty;
                    $totalReplacementVal += (float)$swapVariant['exchange_price'] * $qty;
                    $item['replacement_tag'] = $replacementTag;
                }

                $normalizedItems[$saleItemId] = $item;
                $totalReturnVal += $unitPrice * $qty;
            }

            if (empty($normalizedItems)) {
                throw new RuntimeException('Please select at least one valid invoice item to return.');
            }
            $items = $normalizedItems;

            if ($returnType === 'EXCHANGE' || $returnType === 'EXCHANGE_DIFFERENT') {
                if (!is_array($replacements) || empty($replacements)) {
                    throw new RuntimeException('Please select at least one replacement product for this exchange.');
                }
                foreach ($replacements as $replacement) {
                    if (!is_array($replacement)) {
                        throw new RuntimeException('Invalid replacement item data.');
                    }
                    $replacementVariantId = (int)($replacement['variant_id'] ?? 0);
                    $replacementQty = (int)($replacement['quantity'] ?? 0);
                    if ($replacementVariantId <= 0 || $replacementQty <= 0) {
                        throw new RuntimeException('Each replacement product must have a valid variant and quantity.');
                    }
                    $replacementTag = trim((string)($replacement['piece_tag'] ?? ''));
                    $billedSale = $replacementTag !== ''
                        ? $this->findShopBilledPieceTag($shopId, $replacementVariantId, $replacementTag)
                        : null;
                    if ($billedSale) {
                        throw new RuntimeException(
                            "Replacement piece '{$replacementTag}' was already billed"
                            . (!empty($billedSale['invoice_number']) ? " in invoice {$billedSale['invoice_number']}" : '')
                            . ' and cannot be exchanged.'
                        );
                    }
                    $damageRecord = $replacementTag !== ''
                        ? $this->findShopDamageForPieceTag($shopId, $replacementVariantId, $replacementTag)
                        : null;
                    if ($damageRecord) {
                        throw new RuntimeException(
                            "Replacement piece tag '{$replacementTag}' is allocated to Damage Management"
                            . (!empty($damageRecord['damage_number']) ? " ({$damageRecord['damage_number']})" : '')
                            . ' and cannot be exchanged.'
                        );
                    }
                    $scannedReplacement = $replacementTag !== ''
                        ? $this->findExactShopReplacement($replacementTag, $shopId)
                        : null;
                    if (!$scannedReplacement || (int)$scannedReplacement['id'] !== $replacementVariantId) {
                        throw new RuntimeException('Scan an available replacement product for every replacement item.');
                    }
                    $replacementVariant = Database::fetchOne(
                        "SELECT id, COALESCE(NULLIF(selling_price, 0), mrp, 0) as exchange_price
                         FROM product_variants WHERE id = ? AND is_active = 1",
                        [$replacementVariantId]
                    );
                    if (!$replacementVariant) {
                        throw new RuntimeException('A selected replacement product is no longer available.');
                    }
                    $replacement['variant_id'] = $replacementVariantId;
                    $replacement['quantity'] = $replacementQty;
                    $replacement['piece_tag'] = $replacementTag;
                    $replacement['unit_price'] = (float)$replacementVariant['exchange_price'];
                    $replacementStockRequirements[$replacementVariantId] = ($replacementStockRequirements[$replacementVariantId] ?? 0) + $replacementQty;
                    $totalReplacementVal += (float)$replacementVariant['exchange_price'] * $replacementQty;
                    $normalizedReplacements[] = $replacement;
                }
                $replacements = $normalizedReplacements;
            }

            foreach ($replacementStockRequirements as $replacementVariantId => $requiredQty) {
                $availableQty = StockService::getBalance((int)$replacementVariantId, 'SHOP', $shopId, 'AVAILABLE');
                if ($availableQty < $requiredQty) {
                    throw new RuntimeException("Replacement variant {$replacementVariantId} has only {$availableQty} piece(s) available in this shop; {$requiredQty} required.");
                }
            }

        $differenceAmount = $totalReplacementVal - $totalReturnVal;

        $dbReturnType = ($returnType === 'RETURN') ? 'RETURN' : 'EXCHANGE';
        $validRefundModes = ['CASH', 'UPI', 'CARD', 'CASH_UPI', 'SPLIT', 'ONLINE', 'CREDIT_NOTE'];
        $dbRefundMode = in_array($refundMethod, $validRefundModes) ? $refundMethod : 'CASH';
        $refundAmount = $differenceAmount < 0 ? abs($differenceAmount) : ($returnType === 'RETURN' ? $totalReturnVal : 0.0);

        $returnNumber = 'RET-SH-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        Database::execute(
            "INSERT INTO returns (return_number, source_type, sale_id, customer_id, return_type, reason, inspection_status, 
                                 refund_amount, refund_mode, refund_status, total_amount, status, processed_by, approved_by, inspected_by, notes, created_at, updated_at)
             VALUES (?, 'SHOP', ?, ?, ?, ?, 'APPROVED', ?, ?, 'COMPLETED', ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $returnNumber,
                $saleId,
                $sale['customer_id'] ?? null,
                $dbReturnType,
                $reason,
                $refundAmount,
                $dbRefundMode,
                $totalReturnVal,
                $refundAmount > 0 ? 'REFUNDED' : 'APPROVED',
                $userId,
                $userId,
                $userId,
                $reason
            ]
        );
        $returnId = (int)Database::lastInsertId();

        foreach ($items as $k => $itm) {
            $saleItemId = is_numeric($k) ? (int)$k : (int)($itm['sale_item_id'] ?? 0);
            $varId = (int)($itm['variant_id'] ?? 0);
            $qty = (int)($itm['qty'] ?? $itm['quantity'] ?? 0);
            $cond = strtoupper(trim($itm['condition'] ?? $itm['item_condition'] ?? 'GOOD'));
            if (!in_array($cond, ['GOOD', 'DAMAGED', 'DEFECTIVE'])) $cond = 'GOOD';
            $uPrice = (float)($itm['unit_price'] ?? 0);

            if ($saleItemId > 0 && ($varId <= 0 || $uPrice <= 0)) {
                $siRow = Database::fetchOne("SELECT variant_id, unit_price FROM sale_items WHERE id = ?", [$saleItemId]);
                if ($siRow) {
                    if ($varId <= 0) $varId = (int)$siRow['variant_id'];
                    if ($uPrice <= 0) $uPrice = (float)$siRow['unit_price'];
                }
            }

            if ($varId > 0 && $qty > 0) {
                Database::execute(
                    "INSERT INTO return_items (return_id, sale_item_id, variant_id, quantity, item_condition, destination, refund_amount, condition_type, reason)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $returnId,
                        $saleItemId ?: null,
                        $varId,
                        $qty,
                        $cond,
                        $cond === 'GOOD' ? 'AVAILABLE' : 'DAMAGE',
                        $qty * $uPrice,
                        $cond,
                        $reason
                    ]
                );

                if ($cond === 'GOOD') {
                    StockService::increase($varId, 'SHOP', $shopId, 'AVAILABLE', (float)$qty, 'RETURN_REINWARD', 'returns', $returnId, $userId, "Shop Return Good Stock Re-Inward");
                } else {
                    StockService::increase($varId, 'SHOP', $shopId, 'DAMAGED', (float)$qty, 'RETURN_DAMAGED', 'returns', $returnId, $userId, "Shop Return Damaged Stock");
                }
            }
        }

        if ($returnType === 'EXCHANGE' || $returnType === 'EXCHANGE_SAME' || $returnType === 'EXCHANGE_DIFFERENT') {
            $exchNumber = 'EXC-SH-' . date('Ymd') . '-' . mt_rand(1000, 9999);
            $payAction = $differenceAmount > 0 ? 'COLLECT' : ($differenceAmount < 0 ? 'REFUND' : 'NONE');
            Database::execute(
                "INSERT INTO exchanges (exchange_number, return_id, source_type, sale_id, original_amount, new_amount, difference_amount, settlement_mode, payment_action, status, payment_status, notes, created_at, updated_at)
                 VALUES (?, ?, 'SHOP', ?, ?, ?, ?, ?, ?, 'COMPLETED', 'PAID', ?, NOW(), NOW())",
                [
                    $exchNumber,
                    $returnId,
                    $saleId,
                    $totalReturnVal,
                    $totalReplacementVal,
                    $differenceAmount,
                    $dbRefundMode,
                    $payAction,
                    'Shop Product Exchange'
                ]
            );
            $exchangeId = (int)Database::lastInsertId();

            // Record old returning items in exchange_items
            foreach ($items as $k => $itm) {
                $varId = (int)($itm['variant_id'] ?? 0);
                $qty = (int)($itm['qty'] ?? $itm['quantity'] ?? 0);
                $uPrice = (float)($itm['unit_price'] ?? 0);
                $pTag = is_array($itm['piece_tags'] ?? null) ? implode(',', $itm['piece_tags']) : ($itm['piece_tags'] ?? '');
                if ($varId > 0 && $qty > 0) {
                    Database::execute(
                        "INSERT INTO exchange_items (exchange_id, item_type, variant_id, quantity, unit_price, total_amount, piece_tag)
                         VALUES (?, 'OLD', ?, ?, ?, ?, ?)",
                        [$exchangeId, $varId, $qty, $uPrice, $qty * $uPrice, $pTag ?: null]
                    );
                }
            }

            if ($returnType === 'EXCHANGE_SAME') {
                foreach ($items as $k => $itm) {
                    $swapVarId = (int)($itm['exchange_variant_id'] ?? $itm['variant_id'] ?? 0);
                    $swapQty = (int)($itm['qty'] ?? $itm['quantity'] ?? 0);
                    $swapTag = trim($itm['replacement_tag'] ?? '');
                    $p = (float)($itm['exchange_unit_price'] ?? 0);
                    if ($p <= 0 && $swapVarId > 0) {
                        $pvRow = Database::fetchOne("SELECT selling_price FROM product_variants WHERE id = ?", [$swapVarId]);
                        if ($pvRow) $p = (float)$pvRow['selling_price'];
                    }
                    if ($p <= 0) $p = (float)($itm['unit_price'] ?? 0);

                    if ($swapVarId > 0 && $swapQty > 0) {
                        Database::execute(
                            "INSERT INTO exchange_items (exchange_id, item_type, variant_id, quantity, unit_price, total_amount, piece_tag)
                             VALUES (?, 'NEW', ?, ?, ?, ?, ?)",
                            [$exchangeId, $swapVarId, $swapQty, $p, $swapQty * $p, $swapTag ?: null]
                        );
                        StockService::decrease($swapVarId, 'SHOP', $shopId, 'AVAILABLE', $swapQty, 'EXCHANGE_DISPATCH', 'exchanges', $returnId, $userId, "Shop Same Product Swap Dispatch");
                    }
                }
            } elseif (!empty($replacements) && is_array($replacements)) {
                foreach ($replacements as $rep) {
                    $repVarId = (int)($rep['variant_id'] ?? 0);
                    $repQty = (int)($rep['quantity'] ?? 0);
                    $repPrice = (float)($rep['unit_price'] ?? 0);
                    $repTag = trim($rep['piece_tag'] ?? '');
                    if ($repVarId > 0 && $repQty > 0) {
                        Database::execute(
                            "INSERT INTO exchange_items (exchange_id, item_type, variant_id, quantity, unit_price, total_amount, piece_tag)
                             VALUES (?, 'NEW', ?, ?, ?, ?, ?)",
                            [$exchangeId, $repVarId, $repQty, $repPrice, $repQty * $repPrice, $repTag ?: null]
                        );
                        StockService::decrease($repVarId, 'SHOP', $shopId, 'AVAILABLE', $repQty, 'EXCHANGE_DISPATCH', 'exchanges', $returnId, $userId, "Shop Exchange New Catalog Swap Dispatch");
                    }
                }
            }
        }

            Database::commit();
            Session::flash('success', "Shop Return/Exchange #{$returnNumber} processed successfully!");
            Response::redirect(url('shop/returns'));
        } catch (Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            Session::flash('error', $e->getMessage());
            Response::redirect(url('shop/returns'));
        }
    }

    /**
     * Print Thermal Receipt / Slip for Customer Return or Exchange
     */
    public function printReturnInvoice(string|int $id): void {
        if (!hasPermission('shop_returns', 'print') && !hasPermission('shop_returns', 'view')) {
            Session::setFlash('error', 'Access denied: You do not have permission to print return vouchers.');
            Response::redirect(url('shop/returns'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $return = Database::fetchOne(
            "SELECT r.*, 
                    s.invoice_number as sale_invoice,
                    s.invoice_number,
                    s.created_at as sale_date,
                    COALESCE(c.name, s.customer_name, 'Shop Customer') as customer_name, 
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone, 
                    c.email as customer_email, 
                    c.address as customer_address, 
                    e.id as exchange_id,
                    e.exchange_number, 
                    e.difference_amount, 
                    e.new_amount,
                    e.original_amount,
                    e.payment_action,
                    u.full_name as processor_name
             FROM returns r
             JOIN sales s ON r.sale_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             LEFT JOIN exchanges e ON e.return_id = r.id
             LEFT JOIN users u ON COALESCE(r.approved_by, r.processed_by) = u.id
             WHERE r.id = ? OR r.return_number = ?",
            [$id, (string)$id]
        );

        if (!$return) {
            Session::flash('error', 'Return record not found.');
            Response::redirect(url('shop/returns'));
            return;
        }

        $items = Database::fetchAll(
            "SELECT ri.*, p.name as product_name, p.code as product_code, pv.sku, pv.qr_code, sz.name as size_name, clr.name as color_name,
                    si.scanned_qr_codes as sale_piece_tags
             FROM return_items ri
             JOIN product_variants pv ON ri.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sale_items si ON ri.sale_item_id = si.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors clr ON pv.color_id = clr.id
             WHERE ri.return_id = ?",
            [(int)$return['id']]
        );

        $exchangeItems = [];
        if (!empty($return['exchange_id'])) {
            $exchangeItems = Database::fetchAll(
                "SELECT ei.*, p.name as product_name, p.code as product_code, pv.sku, pv.qr_code, sz.name as size_name, clr.name as color_name
                 FROM exchange_items ei
                 JOIN product_variants pv ON ei.variant_id = pv.id
                 JOIN products p ON pv.product_id = p.id
                 LEFT JOIN sizes sz ON pv.size_id = sz.id
                 LEFT JOIN colors clr ON pv.color_id = clr.id
                 WHERE ei.exchange_id = ?",
                [(int)$return['exchange_id']]
            );
        }

        Response::view('shop.returns.invoice', [
            'pageTitle'     => 'Return Invoice #' . $return['return_number'],
            'shop'          => $shop,
            'return'        => $return,
            'items'         => $items,
            'exchangeItems' => $exchangeItems
        ]);
    }

    /**
     * View Customer Return Details Page
     */
    public function viewReturn(string|int $id): void {
        $this->printReturnInvoice($id);
    }

    /**
     * AJAX Endpoint: Get Return Details for Modal
     */
    public function ajaxGetReturnDetails(string|int $id): void {
        $return = Database::fetchOne(
            "SELECT r.*, s.invoice_number as sale_invoice, c.name as customer_name, c.phone as customer_phone
             FROM returns r
             JOIN sales s ON r.sale_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE r.id = ? OR r.return_number = ?",
            [$id, (string)$id]
        );

        if (!$return) {
            Response::jsonError("Return record #$id not found.", [], 404);
            return;
        }

        $items = Database::fetchAll(
            "SELECT ri.*, p.name as product_name, pv.sku, sz.name as size_name, clr.name as color_name
             FROM return_items ri
             JOIN product_variants pv ON ri.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors clr ON pv.color_id = clr.id
             WHERE ri.return_id = ?",
            [(int)$return['id']]
        );

        Response::jsonSuccess("Return details loaded", [
            'return' => $return,
            'items'  => $items
        ]);
    }

    /**
     * Store Processed Return / Exchange (Legacy wrapper)
     */
    public function storeReturn(): void {
        $this->processReturn();
    }

    // =========================================================================
    // SHOP INWARD / GRN (Warehouse Allocation Receptions & QR Suite)
    // =========================================================================

    /**
     * Shop Stock Inward & Goods Receipt Portal (Mirroring Warehouse Inward).
     */
    public function inwardList(): void {
        if (!hasPermission('shop_inward', 'view') && !hasPermission('shop', 'view')) {
            Session::setFlash('error', 'Access denied: You do not have permission to view inward shipments.');
            Response::redirect(url('shop/dashboard'));
            return;
        }

        require_once BASE_PATH . '/app/Models/WarehouseAllocation.php';
        require_once BASE_PATH . '/app/Models/Warehouse.php';

        $shops = function_exists('getScopedShops') ? getScopedShops() : [ShopService::getActiveShop()];
        $scopedShopId = function_exists('currentShopId') ? currentShopId() : null;
        $requestedShopId = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : null;
        if ($scopedShopId !== null) {
            $shop = ShopService::getActiveShop();
            $shopId = (int)$shop['id'];
        } elseif ($requestedShopId === null && count($shops) > 1) {
            $shop = ShopService::getActiveShop();
            $shopId = 0;
        } elseif ($requestedShopId !== null && $requestedShopId <= 0) {
            $shop = ShopService::getActiveShop();
            $shopId = 0;
        } else {
            $shop = ShopService::getActiveShop($requestedShopId);
            $shopId = (int)$shop['id'];
        }
        $warehouseId = $_GET['warehouse_id'] ?? '';
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';

        $destinationShopIdSql = "COALESCE(
            NULLIF(ao.shop_id, 0),
            CASE WHEN ao.destination_type = 'SHOP' THEN NULLIF(ao.destination_id, 0) END,
            CASE WHEN LEFT(UPPER(ao.destination_type), 5) = 'SHOP_' THEN CAST(SUBSTRING(ao.destination_type, 6) AS UNSIGNED) END,
            NULLIF(wa.shop_id, 0),
            CASE WHEN wa.destination_type = 'SHOP' THEN NULLIF(wa.destination_id, 0) END,
            CASE WHEN LEFT(UPPER(wa.destination_type), 5) = 'SHOP_' THEN CAST(SUBSTRING(wa.destination_type, 6) AS UNSIGNED) END
        )";
        $sql = "SELECT wa.*,
                       $destinationShopIdSql as destination_shop_id,
                       w.name as warehouse_name, w.code as warehouse_code,
                       ao.outward_number, ao.vehicle_number, ao.driver_name, ao.driver_contact, ao.transporter_name, ao.lr_number, ao.total_boxes, ao.dispatch_date,
                       (SELECT COUNT(*) FROM allocation_items WHERE allocation_id = wa.id) as item_count,
                       (SELECT COALESCE(SUM(requested_qty), 0) FROM allocation_items WHERE allocation_id = wa.id) as total_requested_qty,
                       (SELECT COALESCE(SUM(dispatched_qty), 0) FROM allocation_items WHERE allocation_id = wa.id) as total_dispatched_qty,
                       u_prep.full_name as prepared_by_name,
                       destination_shop.name as destination_shop_name,
                       destination_shop.code as destination_shop_code
                FROM warehouse_allocations wa
                JOIN warehouses w ON wa.warehouse_id = w.id
                LEFT JOIN allocation_outwards ao ON wa.id = ao.allocation_id
                LEFT JOIN shops destination_shop ON destination_shop.id = $destinationShopIdSql
                LEFT JOIN users u_prep ON wa.requested_by = u_prep.id
                WHERE wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED')
                  AND $destinationShopIdSql > 0";
        $params = [];

        if ($shopId > 0) {
            $sql .= " AND $destinationShopIdSql = ?";
            $params[] = $shopId;
        }

        if (!empty($warehouseId)) {
            $sql .= " AND wa.warehouse_id = ?";
            $params[] = (int)$warehouseId;
        }

        if ($status === 'RECEIVED') {
            $sql .= " AND wa.status IN ('RECEIVED', 'COMPLETED', 'DELIVERED')";
        } elseif (!empty($status)) {
            $sql .= " AND wa.status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (
                wa.allocation_number LIKE ?
                OR ao.outward_number LIKE ?
                OR ao.vehicle_number LIKE ?
                OR ao.driver_name LIKE ?
                OR w.name LIKE ?
                OR EXISTS (
                    SELECT 1
                    FROM allocation_items search_ai
                    JOIN product_variants search_pv ON search_ai.variant_id = search_pv.id
                    JOIN products search_p ON search_pv.product_id = search_p.id
                    WHERE search_ai.allocation_id = wa.id
                      AND (search_pv.sku LIKE ? OR search_p.name LIKE ?)
                )
            )";
            $srch = '%' . $search . '%';
            $params = array_merge($params, [$srch, $srch, $srch, $srch, $srch, $srch, $srch]);
        }

        $sql .= " ORDER BY wa.id DESC";
        $allocations = Database::fetchAll($sql, $params);
        $warehouses = Warehouse::getAll(['is_active' => 1]);

        // Fetch variant items for all retrieved allocations for rich batch-wise card cards UI
        $allocIds = array_column($allocations, 'id');
        $allocItemsMap = [];
        if (!empty($allocIds)) {
            $placeholders = implode(',', array_fill(0, count($allocIds), '?'));
            $itemsSql = "SELECT ai.*, pv.sku, pv.qr_code, pv.size_id, pv.color_id, pv.selling_price, pv.mrp,
                                s.name as size_name, c.name as color_name, c.hex_code as color_hex,
                                p.name as product_name, p.image_url as product_image, cat.name as category_name
                         FROM allocation_items ai
                         JOIN product_variants pv ON ai.variant_id = pv.id
                         JOIN products p ON pv.product_id = p.id
                         LEFT JOIN categories cat ON p.category_id = cat.id
                         LEFT JOIN sizes s ON pv.size_id = s.id
                         LEFT JOIN colors c ON pv.color_id = c.id
                         WHERE ai.allocation_id IN ($placeholders)
                         ORDER BY ai.id ASC";
            $rawItems = Database::fetchAll($itemsSql, $allocIds);
            foreach ($rawItems as $row) {
                $allocItemsMap[$row['allocation_id']][] = $row;
            }
        }

        $pendingInwardsCount = 0;
        $totalPendingMissingQty = 0;
        $receivedInwardsCount = 0;
        $stockedInwardsCount = 0;
        $totalReceivedQty = 0;
        $totalDamagedQty = 0;
        $totalDispatchedQty = 0;

        foreach ($allocations as &$a) {
            $a['items'] = $allocItemsMap[$a['id']] ?? [];
            $a['total_variants'] = count($a['items']);
            $qty = (int)($a['total_dispatched_qty'] > 0 ? $a['total_dispatched_qty'] : $a['total_requested_qty']);
            $a['effective_qty'] = $qty;
            $totalDispatchedQty += $qty;

            // Received quantities include damaged units; only good units become available shop stock.
            $itemRecvSum = 0;
            $itemDamSum = 0;
            $isFinalReceipt = in_array($a['status'], ['RECEIVED', 'COMPLETED', 'DELIVERED'], true);
            foreach ($a['items'] as $itemLine) {
                $lineReceived = (int)($itemLine['received_qty'] ?? 0);
                $lineDamaged = (int)($itemLine['damaged_qty'] ?? 0);
                if ($isFinalReceipt && $lineReceived === 0 && $lineDamaged === 0) {
                    $lineReceived = (int)($itemLine['dispatched_qty'] > 0 ? $itemLine['dispatched_qty'] : $itemLine['requested_qty']);
                }
                $itemRecvSum += $lineReceived;
                $itemDamSum += $lineDamaged;
            }
            $a['total_received_items_qty'] = $itemRecvSum;
            $a['total_damaged_items_qty'] = $itemDamSum;
            $a['total_good_items_qty'] = max(0, $itemRecvSum - $itemDamSum);
            $a['missing_qty'] = max(0, $qty - $itemRecvSum);
            $totalReceivedQty += $a['total_good_items_qty'];
            $totalDamagedQty += $itemDamSum;

            if ($itemRecvSum > 0) {
                $receivedInwardsCount++;
            }
            if ($a['total_good_items_qty'] > 0) {
                $stockedInwardsCount++;
            }
            if ($a['missing_qty'] > 0) {
                $pendingInwardsCount++;
                $totalPendingMissingQty += $a['missing_qty'];
            }
        }
        unset($a);

        Response::view('shop.inward.index', [
            'pageTitle'              => 'Shop Stock Inward & GRN Receptions',
            'activeModule'           => 'shop',
            'activeMenu'             => 'shop-inward',
            'shop'                   => $shop,
            'shops'                  => $shops,
            'allocations'            => $allocations,
            'warehouses'             => $warehouses,
            'warehouseId'            => $warehouseId,
            'status'                 => $status,
            'search'                 => $search,
            'selectedShopId'         => $shopId,
            'shopQuery'              => '?shop_id=' . $shopId,
            'pendingInwardsCount'    => $pendingInwardsCount,
            'totalPendingMissingQty' => $totalPendingMissingQty,
            'receivedInwardsCount'   => $receivedInwardsCount,
            'stockedInwardsCount'    => $stockedInwardsCount,
            'totalReceivedQty'       => $totalReceivedQty,
            'totalDamagedQty'        => $totalDamagedQty,
            'totalDispatchedQty'     => $totalDispatchedQty,
            'breadcrumb'             => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Inward / GRN'],
            ],
        ]);
    }

    /**
     * View Inward Allocation Details & Cross-Check Verification Portal.
     */
    public function viewInward(string $id): void {
        if (!hasPermission('shop_inward', 'view') && !hasPermission('shop', 'view')) {
            Session::setFlash('error', 'Access denied: You do not have permission to view inward verification details.');
            Response::redirect(url('shop/inward'));
            return;
        }

        $allocationId = (int)$id;
        $details = ShopService::getInwardAllocationDetails($allocationId);

        if (!$details) {
            Session::setFlash('error', 'Inward allocation consignment not found.');
            Response::redirect(url('shop/inward'));
            return;
        }

        $scopedShopId = function_exists('currentShopId') ? currentShopId() : null;
        if ($scopedShopId !== null && (int)($details['shop']['id'] ?? 0) !== $scopedShopId) {
            Session::setFlash('error', 'This inward allocation is not assigned to your shop.');
            Response::redirect(url('shop/inward'));
            return;
        }

        $totalExpected = 0;
        foreach ($details['items'] as $item) {
            $totalExpected += (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : $item['requested_qty']);
        }

        $savedDraft = $_SESSION['shop_inward_drafts'][$allocationId] ?? null;

        Response::view('shop.inward.view', array_merge($details, [
            'pageTitle'     => 'Cross-Check Shop Inward #' . $details['allocation']['allocation_number'],
            'activeModule'  => 'shop',
            'activeMenu'    => 'shop-inward',
            'totalExpected' => $totalExpected,
            'savedDraft'    => $savedDraft,
            'breadcrumb'    => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Inward / GRN', 'url' => url('shop/inward')],
                ['label' => 'Cross-Check #' . $details['allocation']['allocation_number']],
            ],
        ]));
    }

    /**
     * Process Itemized Verification & Credit Stock to Shop Inventory.
     */
    public function verifyInward(string $id): void {
        $allocationId = (int)$id;
        if (!hasPermission('shop_inward', 'verify') && !hasPermission('shop_inward', 'receive') && !hasPermission('shop_inward', 'create')) {
            Session::setFlash('error', 'Access denied: You do not have permission to verify or receive inward shipments.');
            Response::redirect(url('shop/inward/view/' . $allocationId));
            return;
        }

        $notes = trim($_POST['notes'] ?? '');

        $receivedItems = null;
        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            $receivedItems = [];
            foreach ($_POST['items'] as $varId => $data) {
                $scannedTags = [];
                if (!empty($data['scanned_tags'])) {
                    $scannedTags = is_array($data['scanned_tags']) ? $data['scanned_tags'] : array_filter(array_map('trim', explode(',', (string)$data['scanned_tags'])));
                }
                $damageTags = [];
                if (!empty($data['damage_tags'])) {
                    $damageTags = is_array($data['damage_tags']) ? $data['damage_tags'] : array_filter(array_map('trim', explode(',', (string)$data['damage_tags'])));
                }
                $receivedItems[(int)$varId] = [
                    'received_qty' => (float)($data['received_qty'] ?? 0),
                    'damaged_qty'  => (float)($data['damaged_qty'] ?? 0),
                    'notes'        => trim($data['notes'] ?? ''),
                    'scanned_tags' => $scannedTags,
                    'damage_tags'  => $damageTags,
                ];
            }
        }

        try {
            $details = ShopService::getInwardAllocationDetails($allocationId);
            if (!$details) {
                throw new Exception("Allocation Pass #$allocationId not found.");
            }
            $shopId = (int)($details['shop']['id'] ?? 0);
            $scopedShopId = function_exists('currentShopId') ? currentShopId() : null;
            if ($shopId <= 0 || ($scopedShopId !== null && $scopedShopId !== $shopId)) {
                throw new Exception('This inward allocation is not assigned to your shop.');
            }

            $userId = Session::userId() ?? 1;
            ShopService::receiveShopAllocation($allocationId, $shopId, $userId, $notes, $receivedItems);

            // Clear in-progress scan draft upon final credit
            if (isset($_SESSION['shop_inward_drafts'][$allocationId])) {
                unset($_SESSION['shop_inward_drafts'][$allocationId]);
            }

            $details = ShopService::getInwardAllocationDetails($allocationId);
            $allocNum = $details['allocation']['allocation_number'] ?? "#$allocationId";
            Response::redirect(
                url('shop/inventory') . '?shop_id=' . $shopId,
                'success',
                "Allocation $allocNum successfully verified and credited. Showing inventory for " . ($details['shop']['name'] ?? 'the destination shop') . '.'
            );
        } catch (Exception $e) {
            Response::redirect(url('shop/inward/view/' . $allocationId), 'error', "Inward verification failed: " . $e->getMessage());
        }
    }

    /**
     * Simple One-Click Receive Endpoint.
     */
    public function receiveInward(string $id): void {
        $this->verifyInward($id);
    }

    /**
     * Printable Shop Carton QR Sticker Label.
     */
    public function printInwardQRLabel(string $id): void {
        $allocationId = (int)$id;
        $details = ShopService::getInwardAllocationDetails($allocationId);
        if (!$details) {
            Response::error(404, 'Stock Allocation pass not found.');
            return;
        }

        Response::viewOnly('shop.inward.label', $details);
    }

    /**
     * Printable Shop Inward / Dispatch Delivery Gate Pass.
     */
    public function printInwardGatePass(string $id): void {
        $allocationId = (int)$id;
        $details = ShopService::getInwardAllocationDetails($allocationId);
        if (!$details) {
            Response::error(404, 'Stock Allocation pass not found.');
            return;
        }

        Response::viewOnly('shop.inward.gatepass', $details);
    }

    /**
     * AJAX Endpoint: Live QR Scanner for Shop Inward (Auto-Inward / Inspection).
     */
    public function ajaxScanInward(): void {
        if (!hasPermission('shop_inward', 'verify') && !hasPermission('shop_inward', 'receive') && !hasPermission('shop_inward', 'create')) {
            Response::jsonError("Access denied: You do not have permission to scan or verify inward shipments.", [], 403);
            return;
        }

        $code = trim($_POST['qr_code'] ?? '');
        $autoReceive = isset($_POST['auto_receive']) && ($_POST['auto_receive'] === '1' || $_POST['auto_receive'] === 'true');
        $notes = trim($_POST['notes'] ?? 'Scanned & Received via Shop Inward QR Scanner');

        if (empty($code)) {
            Response::jsonError("QR code or allocation identifier cannot be empty.");
            return;
        }

        $shop = ShopService::getActiveShop();
        $shopId = (int)$shop['id'];

        // Clean code and handle JSON / Pipe formats
        $parsedCode = $code;
        $extractedSku = '';

        if (str_starts_with($code, '{') && str_ends_with($code, '}')) {
            $json = json_decode($code, true);
            if (!empty($json['allocation_number'])) {
                $parsedCode = $json['allocation_number'];
            } elseif (!empty($json['outward_number'])) {
                $parsedCode = $json['outward_number'];
            } elseif (!empty($json['sku'])) {
                $extractedSku = trim($json['sku']);
            }
        } elseif (str_contains($code, '|')) {
            $parts = explode('|', $code);
            if (!empty($parts[1])) {
                $extractedSku = trim($parts[1]);
            }
        }

        // Search for matching allocation: by allocation_number, outward_number, variant SKU, or piece QR tag
        $sql = "SELECT wa.*, w.name as warehouse_name, ao.outward_number, ao.vehicle_number
                FROM warehouse_allocations wa
                JOIN warehouses w ON wa.warehouse_id = w.id
                LEFT JOIN allocation_outwards ao ON wa.id = ao.allocation_id
                LEFT JOIN allocation_items ai ON ai.allocation_id = wa.id
                LEFT JOIN product_variants pv ON ai.variant_id = pv.id
                LEFT JOIN products p ON pv.product_id = p.id
                WHERE (wa.shop_id = ? OR (wa.destination_type = 'SHOP' AND wa.destination_id = ?))
                  AND wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED')
                  AND (
                      wa.allocation_number = ?
                      OR ao.outward_number = ?
                      OR (? != '' AND pv.sku = ?)
                      OR (? != '' AND pv.qr_code = ?)
                      OR (? != '' AND ai.selected_qr_codes LIKE CONCAT('%', ?, '%'))
                      OR (? != '' AND ? LIKE CONCAT('%', wa.allocation_number, '%'))
                  )
                ORDER BY (wa.status IN ('DISPATCHED', 'PACKED', 'APPROVED')) DESC, wa.id DESC
                LIMIT 1";

        $allocation = Database::fetchOne($sql, [
            $shopId, $shopId,
            $parsedCode,
            $parsedCode,
            $extractedSku, $extractedSku,
            $parsedCode, $parsedCode,
            $parsedCode, $parsedCode,
            $code, $code
        ]);

        if (!$allocation) {
            Response::jsonError("Inward consignment or product matching '$code' not found for this shop.");
            return;
        }

        $allocationId = (int)$allocation['id'];

        if (in_array($allocation['status'], ['RECEIVED', 'COMPLETED', 'DELIVERED'])) {
            Response::jsonSuccess("Allocation #{$allocation['allocation_number']} is ALREADY received & stocked in {$shop['name']}.", [
                'allocation'       => $allocation,
                'already_received' => true,
                'viewUrl'          => url('shop/inward/view/' . $allocationId)
            ]);
            return;
        }

        if ($autoReceive) {
            try {
                $userId = Session::userId() ?? 1;
                ShopService::receiveShopAllocation($allocationId, $shopId, $userId, $notes);

                Response::jsonSuccess("Allocation #{$allocation['allocation_number']} verified and stock credited to {$shop['name']}!", [
                    'allocation' => $allocation,
                    'credited'   => true,
                    'viewUrl'    => url('shop/inward/view/' . $allocationId)
                ]);
            } catch (Exception $e) {
                Response::jsonError("Auto-inward failed: " . $e->getMessage());
            }
        } else {
            $details = ShopService::getInwardAllocationDetails($allocationId);
            Response::jsonSuccess("Allocation #{$allocation['allocation_number']} loaded for inspection.", [
                'allocation' => $allocation,
                'items'      => $details['items'] ?? [],
                'credited'   => false,
                'viewUrl'    => url('shop/inward/view/' . $allocationId)
            ]);
        }
    }

    /**
     * AJAX Endpoint: Live Piece & SKU Cross-Check Scan during Inward Inspection.
     */
    public function ajaxCrossCheckScan(): void {
        if (!hasPermission('shop_inward', 'verify') && !hasPermission('shop_inward', 'receive') && !hasPermission('shop_inward', 'create')) {
            Response::jsonError("Access denied: You do not have permission to verify or cross-check inward stock.", [], 403);
            return;
        }

        $allocationId = (int)($_POST['allocation_id'] ?? 0);
        $scannedCode = trim($_POST['qr_code'] ?? '');
        $rawScannedMap = $_POST['scanned_map'] ?? [];

        if (is_string($rawScannedMap)) {
            $scannedMap = json_decode($rawScannedMap, true) ?? [];
        } else {
            $scannedMap = (array)$rawScannedMap;
        }

        if ($allocationId <= 0) {
            Response::jsonError("Invalid allocation identifier.");
            return;
        }

        if (empty($scannedCode)) {
            Response::jsonError("QR code or SKU cannot be empty.");
            return;
        }

        if (str_starts_with($scannedCode, '{') && str_ends_with($scannedCode, '}')) {
            $json = json_decode($scannedCode, true);
            if (!empty($json['sku'])) {
                $scannedCode = $json['sku'];
            } elseif (!empty($json['qr_code'])) {
                $scannedCode = $json['qr_code'];
            } elseif (!empty($json['allocation_number'])) {
                $scannedCode = $json['allocation_number'];
            }
        }

        try {
            $result = ShopService::processShopCrossCheckScan($allocationId, $scannedCode, $scannedMap);
            if (!$result['success']) {
                Response::jsonError($result['message'], $result, 404);
                return;
            }
            Response::jsonSuccess($result['message'], $result);
        } catch (Exception $e) {
            Response::jsonError("Cross-check scan error: " . $e->getMessage());
        }
    }

    /**
     * AJAX Endpoint: Lookup Allocation Consignment by Code.
     */
    public function ajaxLookupInward(string $code): void {
        $code = trim($code);
        $shop = ShopService::getActiveShop();
        $shopId = (int)$shop['id'];

        $sql = "SELECT wa.*, w.name as warehouse_name, ao.outward_number, ao.vehicle_number
                FROM warehouse_allocations wa
                JOIN warehouses w ON wa.warehouse_id = w.id
                LEFT JOIN allocation_outwards ao ON wa.id = ao.allocation_id
                WHERE (wa.shop_id = ? OR (wa.destination_type = 'SHOP' AND wa.destination_id = ?))
                  AND wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED')
                  AND (wa.allocation_number = ? OR ao.outward_number = ?)
                LIMIT 1";

        $allocation = Database::fetchOne($sql, [$shopId, $shopId, $code, $code]);

        if (!$allocation) {
            Response::jsonError("No inward consignment found matching '$code'.", [], 404);
            return;
        }

        $details = ShopService::getInwardAllocationDetails((int)$allocation['id']);
        Response::jsonSuccess("Allocation found.", [
            'allocation' => $allocation,
            'items'      => $details['items'] ?? [],
            'viewUrl'    => url('shop/inward/view/' . $allocation['id']),
            'labelUrl'   => url('shop/inward/label/' . $allocation['id']),
        ]);
    }

    /**
     * AJAX Endpoint: Save In-Progress Scan Draft for Shop Inward.
     */
    public function ajaxSaveInwardDraft(): void {
        if (!hasPermission('shop_inward', 'verify') && !hasPermission('shop_inward', 'receive') && !hasPermission('shop_inward', 'create')) {
            Response::jsonError("Access denied: You do not have permission to modify inward drafts.", [], 403);
            return;
        }

        $allocationId = (int)($_POST['allocation_id'] ?? 0);
        if ($allocationId <= 0) {
            Response::jsonError("Invalid allocation ID.");
            return;
        }

        $scannedTags = $_POST['scanned_tags'] ?? [];
        if (is_string($scannedTags)) {
            $scannedTags = json_decode($scannedTags, true) ?? [];
        }

        $receivedMap = $_POST['received_map'] ?? [];
        if (is_string($receivedMap)) {
            $receivedMap = json_decode($receivedMap, true) ?? [];
        }

        $damagedMap = $_POST['damaged_map'] ?? [];
        if (is_string($damagedMap)) {
            $damagedMap = json_decode($damagedMap, true) ?? [];
        }

        $notes = trim($_POST['notes'] ?? '');

        if (!isset($_SESSION['shop_inward_drafts'])) {
            $_SESSION['shop_inward_drafts'] = [];
        }

        $_SESSION['shop_inward_drafts'][$allocationId] = [
            'allocation_id'  => $allocationId,
            'scanned_tags'   => (array)$scannedTags,
            'received_map'   => (array)$receivedMap,
            'damaged_map'    => (array)$damagedMap,
            'notes'          => $notes,
            'updated_at'     => date('Y-m-d H:i:s'),
            'timestamp'      => time(),
        ];

        Response::jsonSuccess("Inward scan draft saved successfully.", [
            'allocation_id' => $allocationId,
            'updated_at'    => date('h:i:s A'),
        ]);
    }

    /**
     * AJAX Endpoint: Clear In-Progress Scan Draft for Shop Inward.
     */
    public function ajaxClearInwardDraft(): void {
        if (!hasPermission('shop_inward', 'verify') && !hasPermission('shop_inward', 'receive') && !hasPermission('shop_inward', 'create')) {
            Response::jsonError("Access denied: You do not have permission to modify inward drafts.", [], 403);
            return;
        }

        $allocationId = (int)($_POST['allocation_id'] ?? 0);
        if ($allocationId > 0 && isset($_SESSION['shop_inward_drafts'][$allocationId])) {
            unset($_SESSION['shop_inward_drafts'][$allocationId]);
        }
        Response::jsonSuccess("Draft cleared.");
    }

    /**
     * =========================================================================
     * SELLED PRODUCTS (SOLD PRODUCTS / SALES REGISTER)
     * =========================================================================
     */

    /**
     * Sold Products Register & Itemized Breakdown
     */
    public function soldProductsList(): void {
        $shop = ShopService::getActiveShop();
        $shopId = (int)$shop['id'];

        $filters = [
            'search'       => trim($_GET['search'] ?? ''),
            'start_date'   => trim($_GET['start_date'] ?? ''),
            'end_date'     => trim($_GET['end_date'] ?? ''),
            'category_id'  => trim($_GET['category_id'] ?? 'ALL'),
        ];

        $categories = Database::fetchAll("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
        $data = ShopService::getSoldProducts($shopId, $filters);

        Response::view('shop.sales.index', [
            'pageTitle'    => 'Selled Products Ledger',
            'activeModule' => 'shop',
            'activeMenu'   => 'shop-sales',
            'breadcrumb'   => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Selled Products'],
            ],
            'shop'         => $shop,
            'items'        => $data['items'],
            'kpis'         => $data['kpis'],
            'filters'      => $filters,
            'categories'   => $categories,
        ]);
    }

    /**
     * AJAX Endpoint: Get Full Sale Invoice and Sold Product Details
     */
    public function ajaxGetSaleDetails(string $id): void {
        $idNum = (int)$id;
        $type = trim($_GET['type'] ?? '');

        // If explicitly requested as sale_item ID
        if ($type === 'item') {
            $item = ShopService::getSoldProductDetails($idNum);
            if ($item) {
                $invoice = ShopService::getInvoiceDetails((int)$item['sale_id']);
                Response::jsonSuccess("Sold product details loaded.", [
                    'item'    => $item,
                    'invoice' => $invoice,
                    'sale'    => $invoice['sale'] ?? null,
                    'items'   => $invoice['items'] ?? [],
                ]);
                return;
            }
        }

        // 1. Check if passed ID is a sale ID directly (Standard behavior)
        $invoice = ShopService::getInvoiceDetails($idNum);
        if ($invoice) {
            Response::jsonSuccess("Invoice details loaded.", [
                'item'    => $invoice['items'][0] ?? null,
                'invoice' => $invoice,
                'sale'    => $invoice['sale'] ?? null,
                'items'   => $invoice['items'] ?? [],
            ]);
            return;
        }

        // 2. Fallback: Try fetching as sale_item ID
        $item = ShopService::getSoldProductDetails($idNum);
        if ($item) {
            $invoice = ShopService::getInvoiceDetails((int)$item['sale_id']);
            Response::jsonSuccess("Sold product details loaded.", [
                'item'    => $item,
                'invoice' => $invoice,
                'sale'    => $invoice['sale'] ?? null,
                'items'   => $invoice['items'] ?? [],
            ]);
            return;
        }

        Response::jsonError("Invoice or Sale item #$id not found.", [], 404);
    }

    /**
     * =========================================================================
     * RETURN TO WAREHOUSE (SHOP -> CENTRAL WAREHOUSE DISPATCH)
     * =========================================================================
     */

    /**
     * Shop Returns to Warehouse List & Dispatch Dashboard
     */
    public function returnToWarehouseList(): void {
        if (!hasPermission('return_to_warehouse', 'view')) {
            Session::setFlash('error', 'Access denied: You do not have permission to view return to warehouse consignments.');
            Response::redirect(url('shop/dashboard'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $shopId = (int)$shop['id'];

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'status' => trim($_GET['status'] ?? 'ALL'),
        ];

        $data = ShopService::getShopWarehouseReturns($shopId, $filters);

        Response::view('shop.return_to_warehouse.index', [
            'pageTitle'    => 'Return to Warehouse Consignments',
            'activeModule' => 'shop',
            'activeMenu'   => 'shop-return-to-wh',
            'breadcrumb'   => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Return to Warehouse'],
            ],
            'shop'         => $shop,
            'returns'      => $data['returns'],
            'kpis'         => $data['kpis'],
            'filters'      => $filters,
        ]);
    }

    /**
     * Create New Return to Warehouse Form with Rich Product Specifications
     */
    public function createReturnToWarehouse(): void {
        if (!hasPermission('return_to_warehouse', 'create')) {
            Session::setFlash('error', 'Access denied: You do not have permission to create return to warehouse dispatches.');
            Response::redirect(url('shop/return-to-warehouse'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $shopId = (int)$shop['id'];

        $warehouses = Database::fetchAll("SELECT id, name, code, address FROM warehouses WHERE is_active = 1 ORDER BY name ASC");
        
        // Return-to-warehouse accepts both available and damaged shop stock; the scan flow preserves each item's condition.
        $inventory = Database::fetchAll(
            "SELECT sb.variant_id, sb.stock_state, sb.quantity,
                    pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.image_url as variant_image,
                    p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                    p.category_code, p.subcategory_code, p.sub_subcategory_code, p.material, p.hsn_code, p.description,
                    c.id as category_id, c.name as category_name,
                    COALESCE(wc.category_type, 'NORMAL') as category_type,
                    COALESCE(pv.is_complementary, 0) as is_complementary,
                    pv.qr_type, pv.pack_quantity,
                    sc.id as subcategory_id, sc.name as subcategory_name,
                    ssc.id as sub_subcategory_id, ssc.name as sub_subcategory_name,
                    sz.name as size_name, sz.code as size_code,
                    cl.name as color_name, cl.hex_code as color_hex
             FROM stock_balances sb
             JOIN product_variants pv ON sb.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
             LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
             LEFT JOIN sub_subcategories ssc ON p.sub_subcategory_id = ssc.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors cl ON pv.color_id = cl.id
             WHERE sb.location_type = 'SHOP' AND sb.location_id = ?
               AND sb.stock_state IN ('AVAILABLE', 'DAMAGED') AND sb.quantity > 0
             ORDER BY c.name ASC, p.name ASC, pv.sku ASC, sb.stock_state ASC",
            [$shopId]
        );

        // Fetch piece tags mapped to variants for this shop
        $allocTags = Database::fetchAll(
            "SELECT ai.variant_id, ai.selected_qr_codes
             FROM allocation_items ai
             JOIN warehouse_allocations wa ON ai.allocation_id = wa.id
             WHERE (wa.shop_id = ? OR (wa.destination_type = 'SHOP' AND wa.destination_id = ?))
                  AND wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED')
               AND ai.selected_qr_codes IS NOT NULL AND ai.selected_qr_codes != ''",
            [$shopId, $shopId]
        );

        $variantTagsMap = [];
        foreach ($allocTags as $at) {
            $vid = (int)$at['variant_id'];
            if (!isset($variantTagsMap[$vid])) {
                $variantTagsMap[$vid] = [];
            }
            $decodedTags = json_decode((string)$at['selected_qr_codes'], true);
            $tags = is_array($decodedTags) ? $decodedTags : preg_split('/[,|]/', (string)$at['selected_qr_codes']);
            foreach ($tags as $t) {
                $trimmed = is_array($t)
                    ? trim((string)($t['tag_code'] ?? $t['qr_code'] ?? ''))
                    : trim((string)$t);
                if ($trimmed && !in_array($trimmed, $variantTagsMap[$vid])) {
                    $variantTagsMap[$vid][] = $trimmed;
                }
            }
        }

        $damageCases = Database::fetchAll(
            "SELECT variant_id, tag_code
             FROM shop_damages
             WHERE shop_id = ? AND status <> 'RESTOCKED' AND tag_code IS NOT NULL AND tag_code <> ''",
            [$shopId]
        );
        $damagedTagsMap = [];
        foreach ($damageCases as $damageCase) {
            foreach (ShopDamage::resolvePieceTags($damageCase) as $damageTag) {
                $damagedTagsMap[(int)$damageCase['variant_id']][strtoupper($damageTag)] = $damageTag;
            }
        }

        $categories = [];
        foreach ($inventory as &$item) {
            $item['image'] = !empty($item['variant_image']) ? $item['variant_image'] : ($item['product_image'] ?? null);
            $vid = (int)$item['variant_id'];
            $item['piece_tags'] = $variantTagsMap[$vid] ?? [];
            $item['damaged_piece_tags'] = array_values($damagedTagsMap[$vid] ?? []);

            // Group categories for filter pills
            $catName = $item['category_name'] ?? 'General';
            if (!in_array($catName, $categories)) {
                $categories[] = $catName;
            }
        }

        Response::view('shop.return_to_warehouse.create', [
            'pageTitle'    => 'Create Return to Warehouse Dispatch',
            'activeModule' => 'shop',
            'activeMenu'   => 'shop-return-to-wh',
            'breadcrumb'   => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Return to Warehouse', 'url' => url('shop/return-to-warehouse')],
                ['label' => 'New Return Dispatch'],
            ],
            'shop'         => $shop,
            'warehouses'   => $warehouses,
            'inventory'    => $inventory,
            'categories'   => $categories,
        ]);
    }

    /**
     * Store and Process Return to Warehouse
     */
    public function storeReturnToWarehouse(): void {
        if (!hasPermission('return_to_warehouse', 'create')) {
            Session::setFlash('error', 'Access denied: You do not have permission to create return to warehouse dispatches.');
            Response::redirect(url('shop/return-to-warehouse'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $shopId = (int)$shop['id'];
        $userId = Session::userId() ?? 1;

        try {
            $result = ShopService::createShopWarehouseReturn($shopId, $_POST, $userId);
            $returnId = is_array($result) ? $result['id'] : (int)Database::fetchOne("SELECT id FROM warehouse_returns WHERE return_number = ?", [$result])['id'];
            $returnNum = is_array($result) ? $result['return_number'] : $result;
            Session::setFlash('success', "Return to Warehouse #$returnNum created and dispatched successfully! Delivery Gate Pass and Carton QR Label are ready.");
            Response::redirect(url('shop/return-to-warehouse/view/' . $returnId . '?outward_success=1'));
        } catch (Exception $e) {
            Session::setFlash('error', "Failed to create return to warehouse: " . $e->getMessage());
            Response::redirect(url('shop/return-to-warehouse/create'));
        }
    }

    /**
     * View Return to Warehouse Details Page
     */
    public function viewReturnToWarehouse(string $id): void {
        if (!hasPermission('return_to_warehouse', 'view')) {
            Session::setFlash('error', 'Access denied: You do not have permission to view return to warehouse consignments.');
            Response::redirect(url('shop/return-to-warehouse'));
            return;
        }

        $details = ShopService::getShopWarehouseReturnDetails((int)$id);
        if (!$details) {
            Session::setFlash('error', "Return consignment #$id not found.");
            Response::redirect(url('shop/return-to-warehouse'));
            return;
        }

        Response::view('shop.return_to_warehouse.view', array_merge($details, [
            'pageTitle'    => 'Return #' . $details['return']['return_number'] . ' Manifest',
            'activeModule' => 'shop',
            'activeMenu'   => 'shop-return-to-wh',
            'breadcrumb'   => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Return to Warehouse', 'url' => url('shop/return-to-warehouse')],
                ['label' => $details['return']['return_number']],
            ],
        ]));
    }

    /**
     * Print Delivery Gate Pass / Return Challan for Return to Warehouse
     */
    public function printReturnToWarehouseGatePass(string $id): void {
        if (!hasPermission('return_to_warehouse', 'gatepass') && !hasPermission('return_to_warehouse', 'print') && !hasPermission('return_to_warehouse', 'view')) {
            Session::setFlash('error', 'Access denied: You do not have permission to view or print gate passes.');
            Response::redirect(url('shop/return-to-warehouse'));
            return;
        }

        $details = ShopService::getShopWarehouseReturnDetails((int)$id);
        if (!$details) {
            Session::setFlash('error', "Return consignment #$id not found.");
            Response::redirect(url('shop/return-to-warehouse'));
            return;
        }

        Response::viewOnly('shop.return_to_warehouse.gatepass', $details);
    }

    /**
     * Print Carton QR Shipping Label for Return to Warehouse
     */
    public function printReturnToWarehouseLabel(string $id): void {
        if (!hasPermission('return_to_warehouse', 'print') && !hasPermission('return_to_warehouse', 'view')) {
            Session::setFlash('error', 'Access denied: You do not have permission to print carton QR labels.');
            Response::redirect(url('shop/return-to-warehouse'));
            return;
        }

        $details = ShopService::getShopWarehouseReturnDetails((int)$id);
        if (!$details) {
            Session::setFlash('error', "Return consignment #$id not found.");
            Response::redirect(url('shop/return-to-warehouse'));
            return;
        }

        Response::viewOnly('shop.return_to_warehouse.label', $details);
    }

    // ==========================================
    // SHOP CUSTOMER DIRECTORY & 360 CRM
    // ==========================================

    /**
     * Shop Customer Directory & CRM List View
     */
    public function customersList(): void {
        if (!hasPermission('customers', 'view') && !hasPermission('shop', 'view')) {
            Session::setFlash('error', 'Access denied: You do not have permission to view customers.');
            Response::redirect(url('shop/dashboard'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $filters = [
            'q'       => $_GET['q'] ?? '',
            'status'  => $_GET['status'] ?? 'all',
            'tier'    => $_GET['tier'] ?? 'all',
            'city'    => $_GET['city'] ?? '',
            'sort'    => $_GET['sort'] ?? 'recent',
            'channel' => 'SHOP',
        ];

        $customers = ShopService::getCustomersList($filters);
        $metrics = ShopService::getCustomerSummaryMetrics('SHOP');

        Response::view('shop.customers.index', [
            'pageTitle'    => 'Shop Retail Customers',
            'activeModule' => 'shop',
            'activeMenu'   => 'shop-customers',
            'breadcrumb'   => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Retail Customers'],
            ],
            'shop'         => $shop,
            'customers'    => $customers,
            'metrics'      => $metrics,
            'filters'      => $filters,
        ]);
    }

    /**
     * View Dedicated 360-Degree Customer Profile Page
     */
    public function viewCustomer(string $id): void {
        if (!hasPermission('customers', 'view') && !hasPermission('shop', 'view')) {
            Session::setFlash('error', 'Access denied: You do not have permission to view customers.');
            Response::redirect(url('shop/customers'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $details = ShopService::getCustomerDetails((int)$id);

        if (!$details) {
            Session::setFlash('error', "Customer record #$id not found.");
            Response::redirect(url('shop/customers'));
            return;
        }

        Response::view('shop.customers.view', array_merge($details, [
            'pageTitle'    => 'Customer Profile: ' . $details['customer']['name'],
            'activeModule' => 'shop',
            'activeMenu'   => 'shop-customers',
            'breadcrumb'   => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Customers', 'url' => url('shop/customers')],
                ['label' => $details['customer']['name']],
            ],
            'shop'         => $shop,
        ]));
    }

    /**
     * AJAX Endpoint: Get Customer Details for Quick Drawer/Modal
     */
    public function ajaxCustomerDetails(string $id): void {
        $details = ShopService::getCustomerDetails((int)$id);
        if (!$details) {
            Response::json(['success' => false, 'message' => 'Customer not found.'], 404);
            return;
        }
        Response::jsonSuccess('Customer details loaded', $details);
    }

    /**
     * Store New Customer
     */
    public function storeCustomer(): void {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if (!hasPermission('customers', 'create')) {
            if ($isAjax) {
                Response::json(['success' => false, 'message' => 'Access denied: You do not have permission to create customers.'], 403);
                return;
            }
            Session::setFlash('error', 'Access denied: You do not have permission to create customers.');
            Response::redirect(url('shop/customers'));
            return;
        }

        $payload = $_POST;
        if (empty($payload) && ($json = file_get_contents('php://input'))) {
            $payload = json_decode($json, true) ?: [];
        }

        try {
            $customer = ShopService::saveCustomer($payload);
            if ($isAjax) {
                Response::jsonSuccess('Customer created successfully', $customer);
            } else {
                Session::setFlash('success', "Customer '{$customer['name']}' added successfully!");
                Response::redirect(url('shop/customers'));
            }
        } catch (Throwable $e) {
            if ($isAjax) {
                Response::json(['success' => false, 'message' => $e->getMessage()], 400);
            } else {
                Session::setFlash('error', $e->getMessage());
                Response::redirect(url('shop/customers'));
            }
        }
    }

    /**
     * Update Existing Customer Profile
     */
    public function updateCustomer(string $id): void {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if (!hasPermission('customers', 'edit')) {
            if ($isAjax) {
                Response::json(['success' => false, 'message' => 'Access denied: You do not have permission to edit customers.'], 403);
                return;
            }
            Session::setFlash('error', 'Access denied: You do not have permission to edit customers.');
            Response::redirect(url('shop/customers/view/' . $id));
            return;
        }

        $payload = $_POST;
        if (empty($payload) && ($json = file_get_contents('php://input'))) {
            $payload = json_decode($json, true) ?: [];
        }

        try {
            $customer = ShopService::saveCustomer($payload, (int)$id);
            if ($isAjax) {
                Response::jsonSuccess('Customer profile updated successfully', $customer);
            } else {
                Session::setFlash('success', "Customer '{$customer['name']}' updated successfully!");
                Response::redirect(url('shop/customers/view/' . $id));
            }
        } catch (Throwable $e) {
            if ($isAjax) {
                Response::json(['success' => false, 'message' => $e->getMessage()], 400);
            } else {
                Session::setFlash('error', $e->getMessage());
                Response::redirect(url('shop/customers'));
            }
        }
    }

    /**
     * AJAX Endpoint: Toggle Customer Active Status
     */
    public function toggleCustomerStatus(string $id): void {
        if (!hasPermission('customers', 'edit')) {
            Response::json(['success' => false, 'message' => 'Access denied: You do not have permission to edit customers.'], 403);
            return;
        }

        try {
            $newStatus = ShopService::toggleCustomerStatus((int)$id);
            Response::jsonSuccess("Customer status updated to " . ($newStatus ? 'Active' : 'Inactive'), ['is_active' => $newStatus]);
        } catch (Throwable $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // =========================================================================
    // SHOP DAMAGE & DEFECT MANAGEMENT
    // =========================================================================

    /**
     * List all damaged/defective stock in the shop (quarantine list)
     */
    public function damageList(): void {
        if (!hasPermission('shop_damage', 'view') && !hasPermission('shop', 'view')) {
            Session::setFlash('error', 'Access denied: You do not have permission to view shop damage records.');
            Response::redirect(url('shop/dashboard'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $shopId = (int)$shop['id'];

        $filters = [
            'shop_id'     => $shopId,
            'status'      => $_GET['status'] ?? '',
            'source_type' => $_GET['source_type'] ?? '',
            'search'      => $_GET['search'] ?? '',
        ];

        $damages = ShopDamage::getAll($filters);
        $stats = ShopDamage::getSummaryStats($shopId);
        $shops = Shop::getAll(['is_active' => 1]);

        Response::view('shop.damage.index', [
            'pageTitle'    => 'Shop Damaged Goods & Defect Management',
            'activeModule' => 'shop',
            'activeMenu'   => 'shop-damage',
            'shop'         => $shop,
            'shops'        => $shops,
            'damages'      => $damages,
            'stats'        => $stats,
            'filters'      => $filters,
            'breadcrumb'   => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Damage Management'],
            ],
        ]);
    }

    /**
     * Create / Log Damaged Stock Entry from Shop Floor / Bins
     */
    public function createDamage(): void {
        if (!hasPermission('shop_damage', 'create')) {
            Session::setFlash('error', 'Access denied: You do not have permission to log damaged items.');
            Response::redirect(url('shop/damage'));
            return;
        }

        $shop = ShopService::getActiveShop();
        $shopId = (int)$shop['id'];
        $shops = Shop::getAll(['is_active' => 1]);

        // Fetch catalog variants with current available stock in this shop
        $variants = Database::fetchAll(
            "SELECT pv.*, p.name as product_name, p.code as product_code,
                    cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name,
                    s.name as size_name, c.name as color_name,
                    COALESCE((SELECT quantity FROM stock_balances WHERE variant_id = pv.id AND location_type = 'SHOP' AND location_id = ? AND stock_state = 'AVAILABLE' LIMIT 1), 0) as available_qty
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE pv.is_active = 1
             ORDER BY p.name ASC, pv.sku ASC",
            [$shopId]
        );

        $prefillVariantId = (int)($_GET['variant_id'] ?? 0);

        Response::view('shop.damage.create', [
            'pageTitle'        => 'Log Shop Damaged Stock Entry',
            'activeModule'     => 'shop',
            'activeMenu'       => 'shop-damage',
            'shop'             => $shop,
            'shops'            => $shops,
            'variants'         => $variants,
            'prefillVariantId' => $prefillVariantId,
            'breadcrumb'       => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Damage Management', 'url' => url('shop/damage')],
                ['label' => 'Log Damaged Stock'],
            ],
        ]);
    }

    /**
     * Store Damaged Stock Entries & Adjust Shop Inventory Balances
     */
    public function storeDamage(): void {
        if (!hasPermission('shop_damage', 'create')) {
            Session::setFlash('error', 'Access denied: You do not have permission to log damaged items.');
            Response::redirect(url('shop/damage'));
            return;
        }

        if (!CSRF::validateRequest()) {
            Session::setFlash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $shop = ShopService::getActiveShop();
        $shopId = (int)($shop['id'] ?? 1);
        $actionType = strtolower(trim($_POST['action_type'] ?? 'damage')); // 'damage' or 'rework' / 'return_wh'
        $userId = Session::userId() ?? 1;

        if ($shopId <= 0) {
            Session::setFlash('error', 'Please select a valid shop.');
            Response::back();
        }

        $rawItems = $_POST['items'] ?? null;
        $items = [];

        if (is_string($rawItems)) {
            $decoded = json_decode($rawItems, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        } elseif (is_array($rawItems)) {
            $items = $rawItems;
        }

        // Fallback for single item submission
        if (empty($items) && !empty($_POST['variant_id'])) {
            $items[] = [
                'variant_id'    => (int)$_POST['variant_id'],
                'tag_code'      => trim($_POST['tag_code'] ?? ''),
                'quantity'      => max(1, (int)($_POST['quantity'] ?? 1)),
                'cost_per_unit' => max(0.0, (float)($_POST['cost_per_unit'] ?? 0.0)),
                'total_loss'    => max(0.0, (float)($_POST['total_loss'] ?? 0.0)),
                'damage_reason' => trim($_POST['damage_reason'] ?? 'Shop Floor Defect / Damage'),
                'source_type'   => $_POST['source_type'] ?? 'SHOP_BIN_HANDLING',
                'defect_type'   => $_POST['defect_type'] ?? 'OTHER',
                'defect_notes'  => trim($_POST['defect_notes'] ?? ''),
            ];
        }

        if (empty($items)) {
            Session::setFlash('error', 'Please scan or add at least one product before submitting.');
            Response::back();
        }

        // Handle Photo upload via Cloudinary or local storage
        $photoUrl = null;
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedImgExt = ['jpg', 'jpeg', 'png', 'webp'];
            $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, $allowedImgExt, true)) {
                try {
                    $photoUrl = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/shop/damages', 'image');
                } catch (Throwable $e) {
                    error_log("Shop damage photo upload failed: " . $e->getMessage());
                }
            }
        }

        $status = ($actionType === 'rework' || $actionType === 'return_wh') ? 'RETURN_TO_WH' : 'LOGGED';
        $prefix = 'SH-DMG-';
        $savedCount = 0;
        $totalQty = 0;
        $processedQtyByVariant = [];
        $availableQtyByVariant = [];

        try {
            Database::beginTransaction();

            $baseCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM shop_damages WHERE shop_id = ?", [$shopId])['c'] ?? 0);

            foreach ($items as $idx => $it) {
                $variantId = (int)($it['variant_id'] ?? 0);
                if ($variantId <= 0) continue;

                $productType = Database::fetchOne(
                    "SELECT COALESCE(wc.category_type, 'NORMAL') as category_type,
                            COALESCE(pv.qr_type, '') as qr_type,
                            COALESCE(pv.pack_quantity, 1) as pack_quantity,
                            GREATEST(COALESCE(pv.is_complementary, 0), COALESCE(p.is_complementary, 0)) as is_complementary
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
                     WHERE pv.id = ? AND pv.is_active = 1",
                    [$variantId]
                );
                if (!$productType) {
                    throw new RuntimeException("Product variant {$variantId} is no longer available.");
                }

                $categoryType = strtoupper(trim((string)$productType['category_type']));
                $isComplementary = !empty($productType['is_complementary']) || $categoryType === 'COMPLEMENTARY';
                $isSeries = !$isComplementary && (
                    $categoryType === 'SERIES'
                    || strtoupper((string)$productType['qr_type']) === 'SERIES_BATCH'
                    || (int)$productType['pack_quantity'] > 1
                );
                $tagCode = trim($it['tag_code'] ?? '');
                if (!$isSeries && !$isComplementary && $tagCode !== '') {
                    $pieceAvailable = $this->isShopPieceAvailableForDamage($variantId, $shopId, $tagCode);
                    if ($pieceAvailable === false) {
                        throw new RuntimeException("Piece tag '{$tagCode}' is not available in this shop's inventory.");
                    }
                }
                $qty = ($isSeries || $isComplementary)
                    ? max(1, (int)($it['quantity'] ?? 1))
                    : 1;
                if (!array_key_exists($variantId, $availableQtyByVariant)) {
                    $availableQtyByVariant[$variantId] = (float)StockService::getBalance($variantId, 'SHOP', $shopId, 'AVAILABLE');
                }
                $availableBalance = $availableQtyByVariant[$variantId];
                $previouslyQueuedQty = (float)($processedQtyByVariant[$variantId] ?? 0);
                if ($availableBalance <= 0) {
                    throw new RuntimeException("This product has no available stock in the selected shop and cannot be marked damaged.");
                }
                if ($previouslyQueuedQty + $qty > $availableBalance) {
                    throw new RuntimeException("Damage quantity for variant {$variantId} exceeds available shop stock.");
                }
                $processedQtyByVariant[$variantId] = $previouslyQueuedQty + $qty;
                $costPerUnit = max(0.0, (float)($it['cost_per_unit'] ?? 0.0));
                $totalLoss = max(0.0, (float)($it['total_loss'] ?? ($qty * $costPerUnit)));
                $damageReason = trim($it['damage_reason'] ?? 'Shop Floor Defect');
                $sourceType = !empty($it['source_type']) ? $it['source_type'] : ($_POST['source_type'] ?? 'SHOP_BIN_HANDLING');
                $allowedDefectTypes = ['FABRIC_DEFECT','COLOR_FADE','STITCHING_ERROR','PACKAGING_DAMAGE','WATER_STAIN','TRANSIT_DAMAGE','EXPIRED','SHOP_SOIL','OTHER'];
                $rawDefect = strtoupper(trim((string)(!empty($it['defect_type']) ? $it['defect_type'] : ($_POST['defect_type'] ?? 'OTHER'))));
                if ($rawDefect === 'STITCHING_DEFECT') $rawDefect = 'STITCHING_ERROR';
                $defectType = in_array($rawDefect, $allowedDefectTypes, true) ? $rawDefect : 'OTHER';
                $defectNotes = trim($it['defect_notes'] ?? ($_POST['defect_notes'] ?? ''));

                $damageNumber = $prefix . date('Ymd') . '-' . str_pad((string)($baseCount + $idx + 1), 4, '0', STR_PAD_LEFT);

                $damageId = ShopDamage::create([
                    'damage_number' => $damageNumber,
                    'shop_id'       => $shopId,
                    'variant_id'    => $variantId,
                    'tag_code'      => !empty($tagCode) ? $tagCode : null,
                    'source_type'   => $sourceType,
                    'defect_type'   => $defectType,
                    'quantity'      => $qty,
                    'cost_per_unit' => $costPerUnit,
                    'estimated_cost'=> $costPerUnit,
                    'total_loss'    => $totalLoss,
                    'damage_reason' => $damageReason,
                    'defect_notes'  => $defectNotes,
                    'photo_url'     => $photoUrl,
                    'status'        => $status,
                    'reported_by'   => $userId,
                ]);

                // Adjust Stock: Deduct from AVAILABLE shop stock (if available) and Move to DAMAGED state
                StockService::decrease(
                    variantId: $variantId,
                    locationType: 'SHOP',
                    locationId: $shopId,
                    stockState: 'AVAILABLE',
                    quantity: (float)$qty,
                    transactionType: 'DAMAGE_OUT',
                    referenceType: 'shop_damages',
                    referenceId: $damageId,
                    userId: $userId,
                    notes: "Shop stock damaged / quarantined: {$damageNumber} - {$damageReason}" . (!empty($tagCode) ? " [Tag: {$tagCode}]" : "")
                );

                StockService::increase(
                    variantId: $variantId,
                    locationType: 'SHOP',
                    locationId: $shopId,
                    stockState: 'DAMAGED',
                    quantity: $qty,
                    transactionType: 'DAMAGE_IN',
                    referenceType: 'shop_damages',
                    referenceId: $damageId,
                    userId: $userId,
                    notes: "Quarantine damaged shop stock: {$damageNumber}" . (!empty($tagCode) ? " [Tag: {$tagCode}]" : "")
                );

                AuditService::log('SHOP', 'DAMAGE_LOGGED', 'shop_damages', $damageId, null, [
                    'damage_number' => $damageNumber,
                    'variant_id'    => $variantId,
                    'quantity'      => $qty,
                    'tag_code'      => $tagCode,
                    'damage_reason' => $damageReason,
                    'source_type'   => $sourceType,
                ], $userId);

                $savedCount++;
                $totalQty += $qty;
            }

            Database::commit();

            Session::setFlash('success', "Successfully recorded and moved {$savedCount} damaged item(s) ({$totalQty} pcs) to Shop Damage section.");
            Response::redirect(url('shop/damage'));
        } catch (Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            Session::setFlash('error', 'Error recording damage entry: ' . $e->getMessage());
            Response::back();
        }
    }

    /**
     * View Detailed Shop Damage Record
     */
    public function viewDamage(string $id): void {
        if (!hasPermission('shop_damage', 'view') && !hasPermission('shop', 'view')) {
            Session::setFlash('error', 'Access denied: You do not have permission to view damage records.');
            Response::redirect(url('shop/damage'));
            return;
        }

        $damageId = (int)$id;
        $damage = ShopDamage::find($damageId);
        if (!$damage) {
            Session::setFlash('error', 'Shop damage record not found.');
            Response::redirect(url('shop/damage'));
        }

        $shop = ShopService::getActiveShop();
        $auditLogs = AuditService::getLogsForReference('shop_damages', $damageId);

        // If linked to warehouse allocation inward
        $allocation = null;
        if (!empty($damage['reference_id']) && $damage['source_type'] === 'INWARD_TRANSIT') {
            $allocation = WarehouseAllocation::find((int)$damage['reference_id']);
        }

        Response::view('shop.damage.view', [
            'pageTitle'    => 'Shop Damage Case: ' . $damage['damage_number'],
            'activeModule' => 'shop',
            'activeMenu'   => 'shop-damage',
            'shop'         => $shop,
            'damage'       => $damage,
            'allocation'   => $allocation,
            'auditLogs'    => $auditLogs,
            'breadcrumb'   => [
                ['label' => 'Shop', 'url' => url('shop/dashboard')],
                ['label' => 'Damage Management', 'url' => url('shop/damage')],
                ['label' => $damage['damage_number']],
            ],
        ]);
    }

    /**
     * Update Damage Case Status & Disposition
     */
    public function updateDamageStatus(string $id): void {
        if (!hasPermission('shop_damage', 'edit') && !hasPermission('shop_damage', 'process')) {
            Session::setFlash('error', 'Access denied: You do not have permission to update damage case status.');
            Response::redirect(url('shop/damage/view/' . $id));
            return;
        }

        if (!CSRF::validateRequest()) {
            Session::setFlash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $damageId = (int)$id;
        $damage = ShopDamage::find($damageId);
        if (!$damage) {
            Session::setFlash('error', 'Shop damage record not found.');
            Response::redirect(url('shop/damage'));
        }

        $status = $_POST['status'] ?? 'LOGGED';
        $notes = trim($_POST['defect_notes'] ?? '');
        $dispositionNotes = trim($_POST['disposition_notes'] ?? '');
        $userId = Session::userId() ?? 1;

        $photoUrl = $damage['photo_url'] ?? null;
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedImgExt = ['jpg', 'jpeg', 'png', 'webp'];
            $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, $allowedImgExt, true)) {
                try {
                    $uploadedUrl = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/shop/damages', 'image');
                    if ($uploadedUrl) {
                        $photoUrl = $uploadedUrl;
                    }
                } catch (Throwable $e) {
                    error_log("Shop damage photo update failed: " . $e->getMessage());
                }
            }
        }

        try {
            $oldStatus = $damage['status'];
            ShopDamage::update($damageId, [
                'status'            => $status,
                'defect_notes'      => $notes,
                'disposition_notes' => $dispositionNotes,
                'photo_url'         => $photoUrl,
                'inspected_by'      => $userId,
            ]);

            AuditService::log('SHOP', 'DAMAGE_STATUS_UPDATED', 'shop_damages', $damageId, 
                ['status' => $oldStatus], 
                [
                    'status'            => $status, 
                    'defect_notes'      => $notes,
                    'disposition_notes' => $dispositionNotes,
                    'photo_url'         => $photoUrl,
                    'damage_number'     => $damage['damage_number'],
                ], 
                $userId
            );

            Session::setFlash('success', "Shop Damage Case #{$damage['damage_number']} status updated to {$status}.");
            Response::redirect(url('shop/damage/view/' . $damageId));
        } catch (Throwable $e) {
            Session::setFlash('error', $e->getMessage());
            Response::back();
        }
    }

    /**
     * AJAX Endpoint: Smart Search & Barcode / Piece QR Lookup for Damage Logging
     */
    public function ajaxLookupDamageProduct(): void {
        header('Content-Type: application/json');

        $rawCode = trim($_REQUEST['code'] ?? $_REQUEST['query'] ?? $_REQUEST['search'] ?? '');
        $isReturnLookup = ($_REQUEST['purpose'] ?? '') === 'return';
        $shop = ShopService::getActiveShop();
        $shopId = (int)($shop['id'] ?? 1);

        if (empty($rawCode)) {
            echo json_encode(['success' => false, 'message' => 'No scan code or SKU provided.']);
            exit;
        }

        $rawCode = trim($rawCode, " \t\n\r\0\x0B\"'");
        $extractedTag = $rawCode;
        $candidateCodes = [$rawCode];

        // 1. JSON payload (e.g. {"tag":"GRM102615","sku":"..."})
        if (str_starts_with($rawCode, '{') && str_ends_with($rawCode, '}')) {
            $decoded = json_decode($rawCode, true);
            if (is_array($decoded)) {
                $extractedTag = $decoded['tag'] ?? $decoded['tag_code'] ?? $decoded['code'] ?? $rawCode;
                if (!empty($decoded['sku'])) $candidateCodes[] = trim($decoded['sku']);
                if (!empty($decoded['tag'])) $candidateCodes[] = trim($decoded['tag']);
                if (!empty($decoded['tag_code'])) $candidateCodes[] = trim($decoded['tag_code']);
                if (!empty($decoded['code'])) $candidateCodes[] = trim($decoded['code']);
                if (!empty($decoded['qr_code'])) $candidateCodes[] = trim($decoded['qr_code']);
            }
        }
        // 2. Pipe-delimited QR payload (e.g. "GRM102615|BCT0303-12Y-BLK|MRP:999|Disc:599")
        elseif (strpos($rawCode, '|') !== false) {
            $parts = array_map('trim', explode('|', $rawCode));
            $extractedTag = $parts[0];
            foreach ($parts as $part) {
                if (stripos($part, 'MRP:') === 0 || stripos($part, 'Disc:') === 0 || stripos($part, 'Cost:') === 0 || stripos($part, 'Price:') === 0) {
                    continue;
                }
                $candidateCodes[] = $part;
            }
            if (isset($parts[1]) && !empty($parts[1])) {
                $candidateCodes[] = $parts[1];
            }
            if (isset($parts[2]) && !empty($parts[2])) {
                $candidateCodes[] = $parts[2];
            }
        }
        // 3. URL query string
        elseif (strpos($rawCode, 'http') === 0 && (strpos($rawCode, '?') !== false || strpos($rawCode, '/') !== false)) {
            $parsedUrl = parse_url($rawCode);
            if (!empty($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (!empty($queryParams['code'])) $candidateCodes[] = $queryParams['code'];
                if (!empty($queryParams['sku'])) $candidateCodes[] = $queryParams['sku'];
                if (!empty($queryParams['tag'])) $candidateCodes[] = $queryParams['tag'];
            }
            if (!empty($parsedUrl['path'])) {
                $pathSegments = explode('/', trim($parsedUrl['path'], '/'));
                $candidateCodes[] = end($pathSegments);
            }
        }

        // Smart Tag decomposition (e.g. BABYROM092605 -> BABYROM, BABY)
        if (preg_match('/^([A-Za-z0-9_\-]+?)(\d{4,6})$/', $rawCode, $m)) {
            $candidateCodes[] = $m[1];
            if (preg_match('/^([A-Za-z]+)(\d+)/', $m[1], $m2)) {
                $candidateCodes[] = $m2[1];
            }
        }
        if (preg_match('/^([A-Za-z]+)/', $rawCode, $m3)) {
            $candidateCodes[] = $m3[1];
        }

        $candidateCodes = array_values(array_unique(array_filter($candidateCodes)));
        $foundVariant = null;

        $baseSelect = "SELECT pv.*, 
                              p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                              p.qr_code_prefix, p.category_code, p.subcategory_code, p.sub_subcategory_code,
                              p.category_id, p.subcategory_id, p.sub_subcategory_id,
                              COALESCE(wc.category_type, 'NORMAL') as category_type,
                              GREATEST(COALESCE(pv.is_complementary, 0), COALESCE(p.is_complementary, 0)) as is_complementary,
                              s.name as size_name, s.code as size_code,
                              col.name as color_name, col.hex_code as color_hex,
                              cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name,
                              COALESCE((SELECT SUM(quantity) FROM stock_balances WHERE variant_id = pv.id AND location_type = 'SHOP' AND location_id = {$shopId} AND stock_state = 'AVAILABLE'), 0) as available_qty,
                              COALESCE((SELECT SUM(quantity) FROM stock_balances WHERE variant_id = pv.id AND location_type = 'SHOP' AND location_id = {$shopId} AND stock_state = 'DAMAGED'), 0) as damaged_qty
                       FROM product_variants pv
                       JOIN products p ON pv.product_id = p.id
                       LEFT JOIN categories cat ON p.category_id = cat.id
                       LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
                       LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
                       LEFT JOIN sizes s ON pv.size_id = s.id
                       LEFT JOIN colors col ON pv.color_id = col.id
                       LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id";

        $matchedTagCode = $extractedTag;
        $matchedAllocId = null;

        // Strategy 1: Check piece serial tag in allocation_items (selected_qr_codes)
        foreach ($candidateCodes as $c) {
            if (empty($c) || strlen($c) < 2) continue;
            $allocItem = Database::fetchOne(
                "SELECT ai.variant_id, ai.allocation_id, ai.selected_qr_codes
                 FROM allocation_items ai
                 JOIN warehouse_allocations wa ON ai.allocation_id = wa.id
                 WHERE (ai.selected_qr_codes = ? 
                    OR ai.selected_qr_codes LIKE ? 
                    OR ai.selected_qr_codes LIKE ?
                    OR ai.selected_qr_codes LIKE ?)
                 AND ((wa.destination_type = 'SHOP' AND wa.destination_id = ?) OR wa.shop_id = ?)
                 ORDER BY ai.id DESC LIMIT 1",
                [$c, '%' . $c . '%', '%"' . $c . '"%', '%' . trim($c, '"') . '%', $shopId, $shopId]
            );
            if (!$allocItem) {
                // Fallback across all allocations
                $allocItem = Database::fetchOne(
                    "SELECT ai.variant_id, ai.allocation_id, ai.selected_qr_codes
                     FROM allocation_items ai
                     WHERE (ai.selected_qr_codes = ? 
                        OR ai.selected_qr_codes LIKE ? 
                        OR ai.selected_qr_codes LIKE ?
                        OR ai.selected_qr_codes LIKE ?)
                     ORDER BY ai.id DESC LIMIT 1",
                    [$c, '%' . $c . '%', '%"' . $c . '"%', '%' . trim($c, '"') . '%']
                );
            }
            if ($allocItem && !empty($allocItem['variant_id'])) {
                $foundVariant = Database::fetchOne("{$baseSelect} WHERE pv.id = ? LIMIT 1", [$allocItem['variant_id']]);
                if ($foundVariant) {
                    $matchedTagCode = $c;
                    $matchedAllocId = $allocItem['allocation_id'] ?? null;
                    break;
                }
            }
        }

        // Strategy 2: Exact match on candidate codes against product_variants (sku, qr_code, id) or products (code, qr_code_prefix)
        if (!$foundVariant) {
            foreach ($candidateCodes as $c) {
                if (empty($c)) continue;
                $res = Database::fetchOne(
                    "{$baseSelect}
                     WHERE pv.sku = ? OR pv.qr_code = ? OR p.code = ? OR p.qr_code_prefix = ? OR (pv.id = ? AND ? > 0)
                     LIMIT 1",
                    [$c, $c, $c, $c, is_numeric($c) ? (int)$c : 0, is_numeric($c) ? (int)$c : 0]
                );
                if ($res) {
                    $foundVariant = $res;
                    break;
                }
            }
        }

        // Strategy 2: Historical stock transactions with tag
        if (!$foundVariant) {
            foreach ($candidateCodes as $c) {
                if (empty($c) || strlen($c) < 3) continue;
                $tx = Database::fetchOne(
                    "SELECT st.variant_id
                     FROM stock_transactions st
                     WHERE (st.notes LIKE ? OR st.notes LIKE ?) AND st.location_type = 'SHOP' AND st.location_id = ?
                     ORDER BY st.id DESC
                     LIMIT 1",
                    ['%' . $c . '%', '%Tags: ' . $c . '%', $shopId]
                );
                if ($tx && !empty($tx['variant_id'])) {
                    $foundVariant = Database::fetchOne("{$baseSelect} WHERE pv.id = ? LIMIT 1", [$tx['variant_id']]);
                    if ($foundVariant) break;
                }
            }
        }

        // Strategy 3: Previous Shop / Warehouse Damage records
        if (!$foundVariant) {
            foreach ($candidateCodes as $c) {
                if (empty($c)) continue;
                $sd = Database::fetchOne(
                    "SELECT variant_id FROM shop_damages WHERE tag_code = ? OR damage_number = ? ORDER BY id DESC LIMIT 1",
                    [$c, $c]
                );
                if ($sd && !empty($sd['variant_id'])) {
                    $foundVariant = Database::fetchOne("{$baseSelect} WHERE pv.id = ? LIMIT 1", [$sd['variant_id']]);
                    if ($foundVariant) break;
                }
                $wd = Database::fetchOne(
                    "SELECT variant_id FROM warehouse_damages WHERE tag_code = ? OR damage_number = ? ORDER BY id DESC LIMIT 1",
                    [$c, $c]
                );
                if ($wd && !empty($wd['variant_id'])) {
                    $foundVariant = Database::fetchOne("{$baseSelect} WHERE pv.id = ? LIMIT 1", [$wd['variant_id']]);
                    if ($foundVariant) break;
                }
            }
        }

        // Strategy 4: QC recorded tag_code
        if (!$foundVariant) {
            foreach ($candidateCodes as $c) {
                if (empty($c)) continue;
                $qc = Database::fetchOne(
                    "SELECT variant_id, tag_code FROM qc_items WHERE tag_code = ? ORDER BY id DESC LIMIT 1",
                    [$c]
                );
                if ($qc && !empty($qc['variant_id'])) {
                    $foundVariant = Database::fetchOne("{$baseSelect} WHERE pv.id = ? LIMIT 1", [$qc['variant_id']]);
                    if ($foundVariant) {
                        $extractedTag = $qc['tag_code'] ?: $extractedTag;
                        break;
                    }
                }
            }
        }

        // Strategy 5: Dynamic Piece Tag Decomposition & Reverse Prefix/Sequence Matching
        if (!$foundVariant) {
            if (preg_match('/^([A-Za-z_-]{2,6})(\d{4})(\d{1,5})$/i', $extractedTag, $matches)) {
                $prefix = strtoupper($matches[1]);
                $mfgDigits = $matches[2];
                $seq = (int)$matches[3];
                $mfgMonth = (int)substr($mfgDigits, 0, 2);

                if ($seq >= 1 && $mfgMonth >= 1 && $mfgMonth <= 12) {
                    $allVariants = Database::fetchAll("
                        SELECT pv.*, 
                               p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                               p.qr_code_prefix, p.category_code, p.subcategory_code, p.sub_subcategory_code,
                               p.category_id, p.subcategory_id, p.sub_subcategory_id,
                               s.name as size_name, s.code as size_code,
                               col.name as color_name, col.hex_code as color_hex,
                               cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name,
                               COALESCE(cat.code, p.category_code) as cat_code,
                               COALESCE(subcat.code, p.subcategory_code) as sub_code,
                               COALESCE(subsubcat.code, p.sub_subcategory_code) as subsub_code,
                               COALESCE(sb.quantity, 0) as shop_stock
                        FROM product_variants pv
                        JOIN products p ON pv.product_id = p.id
                        LEFT JOIN sizes s ON pv.size_id = s.id
                        LEFT JOIN colors col ON pv.color_id = col.id
                        LEFT JOIN categories cat ON p.category_id = cat.id
                        LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
                        LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
                        LEFT JOIN stock_balances sb ON sb.variant_id = pv.id AND sb.location_type = 'SHOP' AND sb.location_id = ? AND sb.stock_state = 'AVAILABLE'
                        WHERE pv.is_active = 1
                        ORDER BY shop_stock DESC, pv.id ASC
                    ", [$shopId]);

                    $bestMatch = null;
                    $bestScore = 0;

                    foreach ($allVariants as $v) {
                        $prefixes = [];
                        if (!empty($v['qr_code_prefix'])) {
                            $prefixes[] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $v['qr_code_prefix']));
                        }
                        if (!empty($v['product_code'])) {
                            $parts = explode('-', $v['product_code']);
                            $prefixes[] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $parts[0]));
                            if (isset($parts[1])) {
                                $prefixes[] = strtoupper($parts[0] . $parts[1]);
                            }
                            $prefixes[] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $v['product_code']), 0, 3));
                        }
                        if (!empty($v['sku'])) {
                            $prefixes[] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', explode('-', $v['sku'])[0]));
                            $prefixes[] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $v['sku']), 0, 3));
                        }
                        $c1 = !empty($v['category_name']) ? strtoupper($v['category_name'][0]) : (!empty($v['cat_code']) ? strtoupper($v['cat_code'][0]) : '');
                        $c2 = !empty($v['subcategory_name']) ? strtoupper($v['subcategory_name'][0]) : (!empty($v['sub_code']) ? strtoupper($v['sub_code'][0]) : '');
                        $c3 = !empty($v['sub_subcategory_name']) ? strtoupper($v['sub_subcategory_name'][0]) : (!empty($v['subsub_code']) ? strtoupper($v['subsub_code'][0]) : '');
                        if ($c1 && $c2) {
                            $prefixes[] = $c1 . $c2 . $c3;
                            $prefixes[] = $c1 . $c2;
                            $prefixes[] = 'R' . $c1 . $c2 . $c3;
                        }
                        // Global company fallback abbreviation
                        $prefixes[] = 'GRM';

                        $stock = (float)$v['shop_stock'];
                        $score = 0;

                        foreach ($prefixes as $pfx) {
                            if (empty($pfx)) continue;
                            if ($prefix === $pfx) {
                                $score = ($pfx === 'GRM' ? 50 : 100) + ($stock > 0 ? 50 + min(20, $stock) : 10);
                                break;
                            } elseif (str_starts_with($prefix, $pfx) || str_starts_with($pfx, $prefix)) {
                                $s = 30 + ($stock > 0 ? 30 : 0);
                                if ($s > $score) $score = $s;
                            }
                        }

                        if ($score > $bestScore) {
                            $bestScore = $score;
                            $bestMatch = $v;
                        }
                    }

                    if ($bestMatch) {
                        $foundVariant = Database::fetchOne("{$baseSelect} WHERE pv.id = ? LIMIT 1", [$bestMatch['id']]);
                    }
                }
            }
        }

        // Strategy 6: Partial substring search on SKU, product name, code, or qr prefix
        if (!$foundVariant) {
            $foundVariant = Database::fetchOne(
                "{$baseSelect}
                 WHERE pv.sku LIKE ? OR p.name LIKE ? OR p.code LIKE ? OR p.qr_code_prefix LIKE ?
                 ORDER BY available_qty DESC, pv.id ASC
                 LIMIT 1",
                ["%$rawCode%", "%$rawCode%", "%$rawCode%", "%$rawCode%"]
            );
        }

        // Strategy 7: Smart Keyword combination search (e.g. BABY + ROM)
        if (!$foundVariant) {
            $keywords = [];
            if (stripos($rawCode, 'BABY') !== false) $keywords[] = 'Baby';
            if (stripos($rawCode, 'ROM') !== false) $keywords[] = 'Rom';
            if (stripos($rawCode, 'KID') !== false) $keywords[] = 'Kid';
            if (stripos($rawCode, 'MAT') !== false) $keywords[] = 'Mat';
            if (stripos($rawCode, 'FRO') !== false) $keywords[] = 'Frock';
            if (stripos($rawCode, 'LEG') !== false) $keywords[] = 'Legging';
            if (stripos($rawCode, 'SHO') !== false) $keywords[] = 'Short';
            if (stripos($rawCode, 'KUR') !== false) $keywords[] = 'Kurti';
            if (stripos($rawCode, 'POLO') !== false) $keywords[] = 'Polo';
            if (stripos($rawCode, 'TEE') !== false) $keywords[] = 'Shirt';

            if (!empty($keywords)) {
                $whereParts = [];
                $params = [];
                foreach ($keywords as $kw) {
                    $whereParts[] = "(p.name LIKE ? OR cat.name LIKE ? OR pv.sku LIKE ?)";
                    $params[] = "%$kw%";
                    $params[] = "%$kw%";
                    $params[] = "%$kw%";
                }
                $whereSql = implode(' AND ', $whereParts);
                $foundVariant = Database::fetchOne(
                    "{$baseSelect}
                     WHERE $whereSql
                     ORDER BY available_qty DESC, pv.id ASC
                     LIMIT 1",
                    $params
                );
            }
        }

        if ($foundVariant) {
            $categoryType = strtoupper(trim((string)($foundVariant['category_type'] ?? 'NORMAL')));
            $isComplementary = !empty($foundVariant['is_complementary']) || $categoryType === 'COMPLEMENTARY';
            $isSeries = !$isComplementary && (
                $categoryType === 'SERIES'
                || strtoupper((string)($foundVariant['qr_type'] ?? '')) === 'SERIES_BATCH'
                || (int)($foundVariant['pack_quantity'] ?? 1) > 1
            );
            if (!$isReturnLookup && !$isSeries && !$isComplementary) {
                $pieceAvailable = $this->isShopPieceAvailableForDamage(
                    (int)$foundVariant['id'],
                    $shopId,
                    $matchedTagCode
                );
                if ($pieceAvailable === false || ($matchedAllocId !== null && $pieceAvailable !== true)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'This piece tag is not available in the selected shop. Only pieces currently in shop inventory can be added to damage.',
                    ]);
                    exit;
                }
            }

            if (!$isReturnLookup && (float)($foundVariant['available_qty'] ?? 0) <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'This QR/product has no available stock in the selected shop. Sold or out-of-stock items cannot be added to damage.',
                ]);
                exit;
            }

            $cost = (float)($foundVariant['production_cost'] > 0 ? $foundVariant['production_cost'] : ($foundVariant['purchase_price'] > 0 ? $foundVariant['purchase_price'] : ($foundVariant['selling_price'] ?? 0)));
            $mrp = (float)($foundVariant['mrp'] ?? $foundVariant['selling_price'] ?? 0);

            echo json_encode([
                'success'       => true,
                'tag_code'      => $extractedTag,
                'variant_id'    => (int)$foundVariant['id'],
                'product_id'    => (int)$foundVariant['product_id'],
                'product_name'  => $foundVariant['product_name'],
                'product_code'  => $foundVariant['product_code'],
                'sku'           => $foundVariant['sku'],
                'size_name'     => $foundVariant['size_name'] ?? 'Free',
                'color_name'    => $foundVariant['color_name'] ?? 'Std',
                'category_name' => $foundVariant['category_name'] ?? '',
                'category_type' => $foundVariant['category_type'] ?? 'NORMAL',
                'is_complementary' => (int)($foundVariant['is_complementary'] ?? 0),
                'qr_type'       => $foundVariant['qr_type'] ?? '',
                'pack_quantity' => (int)($foundVariant['pack_quantity'] ?? 1),
                'mrp'           => $mrp,
                'cost_price'    => $cost,
                'selling_price' => (float)$foundVariant['selling_price'],
                'available_qty' => (float)$foundVariant['available_qty'],
                'damaged_qty'   => (float)$foundVariant['damaged_qty'],
                'product_image' => $foundVariant['product_image'] ?? '',
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "No product variant matched code '{$rawCode}' in catalog.",
            ]);
        }
        exit;
    }

    private function isShopPieceAvailableForDamage(int $variantId, int $shopId, string $tagCode): ?bool {
        $inventoryItem = ShopService::getShopInventoryItem($shopId, $variantId);
        foreach ($inventoryItem['individual_pieces'] ?? [] as $piece) {
            if (strcasecmp(trim((string)($piece['tag_code'] ?? '')), trim($tagCode)) === 0) {
                return strcasecmp(trim((string)($piece['status'] ?? '')), 'In Stock') === 0;
            }
        }

        return null;
    }

    private function findExactShopReplacement(string $rawCode, int $shopId): ?array {
        $rawCode = trim($rawCode, " \t\n\r\0\x0B\"'");
        if ($rawCode === '') {
            return null;
        }

        $codes = [$rawCode];
        if (str_starts_with($rawCode, '{') && str_ends_with($rawCode, '}')) {
            $payload = json_decode($rawCode, true);
            if (is_array($payload)) {
                foreach (['tag', 'tag_code', 'code', 'qr_code', 'sku'] as $field) {
                    if (!empty($payload[$field])) {
                        $codes[] = trim((string)$payload[$field]);
                    }
                }
            }
        } elseif (str_contains($rawCode, '|')) {
            foreach (explode('|', $rawCode) as $part) {
                $part = trim($part);
                if ($part !== '' && !preg_match('/^(MRP|Disc|Cost|Price):/i', $part)) {
                    $codes[] = $part;
                }
            }
        } elseif (str_starts_with(strtolower($rawCode), 'http')) {
            $urlParts = parse_url($rawCode);
            if (!empty($urlParts['query'])) {
                parse_str($urlParts['query'], $queryValues);
                foreach (['code', 'sku', 'tag', 'qr_code'] as $field) {
                    if (!empty($queryValues[$field])) {
                        $codes[] = trim((string)$queryValues[$field]);
                    }
                }
            }
            if (!empty($urlParts['path'])) {
                $codes[] = basename((string)$urlParts['path']);
            }
        }
        $codes = array_values(array_unique(array_filter($codes)));

        $loadVariant = static function (int $variantId, string $matchedCode) use ($shopId): ?array {
            $variant = Database::fetchOne(
                "SELECT pv.id, pv.sku, pv.qr_code, pv.selling_price, pv.mrp,
                        p.id as product_id, p.name as product_name,
                        COALESCE(sz.name, 'Free Size') as size_name,
                        COALESCE(clr.name, 'Standard') as color_name,
                        COALESCE((
                            SELECT SUM(sb.quantity)
                            FROM stock_balances sb
                            WHERE sb.variant_id = pv.id
                              AND sb.location_type = 'SHOP'
                              AND sb.location_id = ?
                              AND sb.stock_state = 'AVAILABLE'
                        ), 0) as available_stock
                 FROM product_variants pv
                 JOIN products p ON p.id = pv.product_id
                 LEFT JOIN sizes sz ON sz.id = pv.size_id
                 LEFT JOIN colors clr ON clr.id = pv.color_id
                 WHERE pv.id = ? AND pv.is_active = 1",
                [$shopId, $variantId]
            );
            if (!$variant) {
                return null;
            }
            $variant['matched_scan_code'] = $matchedCode;
            return $variant;
        };

        foreach ($codes as $code) {
            $variant = Database::fetchOne(
                "SELECT id
                 FROM product_variants
                 WHERE is_active = 1 AND (sku = ? OR qr_code = ?)
                 ORDER BY id DESC LIMIT 1",
                [$code, $code]
            );
            if ($variant) {
                return $loadVariant((int)$variant['id'], $code);
            }
        }

        $matchesCode = static function (string $encodedTags) use ($codes): ?string {
            $decoded = json_decode($encodedTags, true);
            if (is_array($decoded)) {
                $tags = array_map(
                    static fn($tag): string => is_array($tag)
                        ? (string)($tag['tag_code'] ?? $tag['qr_code'] ?? '')
                        : (string)$tag,
                    $decoded
                );
            } else {
                $tags = preg_split('/[,|]/', $encodedTags) ?: [];
            }
            foreach ($tags as $tag) {
                $tag = trim((string)$tag, " \t\n\r\0\x0B\"'");
                foreach ($codes as $code) {
                    if ($tag !== '' && strcasecmp($tag, $code) === 0) {
                        return $code;
                    }
                }
            }
            return null;
        };

        foreach ($codes as $code) {
            $allocationItems = Database::fetchAll(
                "SELECT ai.variant_id, ai.selected_qr_codes
                 FROM allocation_items ai
                 JOIN warehouse_allocations wa ON wa.id = ai.allocation_id
                 WHERE ((wa.destination_type = 'SHOP' AND wa.destination_id = ?) OR wa.shop_id = ?)
                   AND ai.selected_qr_codes LIKE ?",
                [$shopId, $shopId, '%' . $code . '%']
            );
            foreach ($allocationItems as $allocationItem) {
                $matchedCode = $matchesCode((string)$allocationItem['selected_qr_codes']);
                if ($matchedCode !== null) {
                    return $loadVariant((int)$allocationItem['variant_id'], $matchedCode);
                }
            }

            $saleItems = Database::fetchAll(
                "SELECT si.variant_id, si.scanned_qr_codes
                 FROM sale_items si
                 JOIN sales s ON s.id = si.sale_id
                 WHERE s.sale_type = 'SHOP' AND s.shop_id = ?
                   AND si.scanned_qr_codes LIKE ?",
                [$shopId, '%' . $code . '%']
            );
            foreach ($saleItems as $saleItem) {
                $matchedCode = $matchesCode((string)$saleItem['scanned_qr_codes']);
                if ($matchedCode !== null) {
                    return $loadVariant((int)$saleItem['variant_id'], $matchedCode);
                }
            }
        }

        return null;
    }

}
