<?php
/**
 * WebsiteController
 * Standalone module controller for Website Orders channel dispatches,
 * stock inwards, stock allocations, returns & exchanges, damage management,
 * piece QR tag resolution, and customers.
 */

require_once BASE_PATH . '/app/Services/StockService.php';
require_once BASE_PATH . '/app/Services/ShopService.php';
require_once BASE_PATH . '/app/Models/ChannelDamage.php';
require_once BASE_PATH . '/app/Models/WarehouseReturn.php';
require_once BASE_PATH . '/app/Models/WarehouseReturnItem.php';

class WebsiteController {

    /**
     * Helper to get Website channel record
     */
    private function getChannel(): array {
        $channels = function_exists('getScopedSalesChannels') ? getScopedSalesChannels('WEBSITE') : [];
        if (!$channels) {
            Response::error(409, 'No Website channel is configured for the active concern.');
        }
        return $channels[0];
    }

    private function getWebsiteChannelIds(): array {
        $channels = function_exists('getScopedSalesChannels') ? getScopedSalesChannels('WEBSITE') : [];
        return array_map('intval', array_column($channels, 'id'));
    }

    private function isAggregateStockVariant(int $variantId): bool {
        $type = Database::fetchOne(
            "SELECT COALESCE(wc.category_type, 'NORMAL') as category_type,
                    pv.is_complementary as variant_is_complementary,
                    p.is_complementary as product_is_complementary,
                    pv.qr_type, pv.pack_quantity
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN warehouse_categories wc ON wc.id = p.warehouse_category_id
             WHERE pv.id = ? LIMIT 1",
            [$variantId]
        ) ?: [];

        return in_array(strtoupper((string)($type['category_type'] ?? 'NORMAL')), ['SERIES', 'COMPLEMENTARY'], true)
            || !empty($type['variant_is_complementary'])
            || !empty($type['product_is_complementary'])
            || strtoupper((string)($type['qr_type'] ?? '')) === 'SERIES_BATCH'
            || (int)($type['pack_quantity'] ?? 1) > 1;
    }

    private function buildWebsiteAllocWhere(string $prefix = 'wa'): array {
        $chanIds = $this->getWebsiteChannelIds();
        $placeholders = implode(',', array_fill(0, count($chanIds), '?'));
        $destList = [];
        foreach ($chanIds as $id) {
            $destList[] = 'CHANNEL_' . $id;
        }
        $destPlaceholders = implode(',', array_fill(0, count($destList), '?'));
        $sql = "(({$prefix}.destination_type = 'CHANNEL' AND {$prefix}.destination_id IN ($placeholders))
                 OR {$prefix}.destination_type IN ($destPlaceholders)
                 OR {$prefix}.destination_type = 'WEBSITE'
                 )";
        $params = array_merge($chanIds, $destList);
        return [$sql, $params];
    }

    /**
     * Website Channel Dashboard & Orders List
     */
    public function index(): void {
        if (!hasPermission('website_dashboard', 'view')) {
            Session::flash('error', 'Access denied. You do not have permission to view the Website dashboard.');
            Response::redirect(url('admin/dashboard'));
            return;
        }
        $chan = $this->getChannel();
        list($allocWhereSql, $allocWhereParams) = $this->buildWebsiteAllocWhere('wa');

        $search = trim($_GET['search'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $where = [$allocWhereSql];
        $where[] = "wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED')";
        $params = $allocWhereParams;

        if (!empty($search)) {
            $where[] = "(wa.allocation_number LIKE ? OR sc.name LIKE ? OR wa.notes LIKE ?)";
            $sWild = "%{$search}%";
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
        }

        if (!empty($status)) {
            $where[] = "wa.status = ?";
            $params[] = $status;
        }

        if (!empty($dateFrom)) {
            $where[] = "DATE(wa.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $where[] = "DATE(wa.created_at) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = implode(' AND ', $where);

        $allocations = Database::fetchAll(
            "SELECT wa.*, 
                    wa.allocation_number as number,
                    COALESCE(sc.name, 'Website Channel') as channel_name,
                    COALESCE(sc.name, 'Website Channel') as customer_or_channel,
                    (SELECT COALESCE(SUM(
                        CASE 
                            WHEN wa.status IN ('COMPLETED', 'DELIVERED', 'RECEIVED') THEN (CASE WHEN received_qty > 0 THEN received_qty ELSE GREATEST(dispatched_qty, requested_qty) END)
                            WHEN wa.status = 'PARTIAL_RECEIVED' THEN (received_qty + damaged_qty)
                            WHEN dispatched_qty > 0 THEN dispatched_qty 
                            ELSE requested_qty 
                        END
                    ), 0) FROM allocation_items WHERE allocation_id = wa.id) as quantity,
                    (SELECT COALESCE(SUM(
                        CASE 
                            WHEN wa.status IN ('COMPLETED', 'DELIVERED', 'RECEIVED') THEN (CASE WHEN received_qty > 0 THEN received_qty ELSE GREATEST(dispatched_qty, requested_qty) END)
                            WHEN wa.status = 'PARTIAL_RECEIVED' THEN (received_qty + damaged_qty)
                            WHEN dispatched_qty > 0 THEN dispatched_qty 
                            ELSE requested_qty 
                        END
                    ), 0) FROM allocation_items WHERE allocation_id = wa.id) as total_quantity,
                    (SELECT COUNT(DISTINCT variant_id) FROM allocation_items WHERE allocation_id = wa.id) as distinct_items_count,
                    (SELECT COALESCE(SUM(
                        (CASE 
                            WHEN wa.status IN ('COMPLETED', 'DELIVERED', 'RECEIVED') THEN (CASE WHEN ai.received_qty > 0 THEN ai.received_qty ELSE GREATEST(ai.dispatched_qty, ai.requested_qty) END)
                            WHEN wa.status = 'PARTIAL_RECEIVED' THEN (ai.received_qty + ai.damaged_qty)
                            WHEN ai.dispatched_qty > 0 THEN ai.dispatched_qty 
                            ELSE ai.requested_qty 
                        END) * COALESCE(NULLIF(pv.selling_price, 0), NULLIF(pv.mrp, 0), NULLIF(pv.production_cost, 0), 250)
                    ), 0)
                     FROM allocation_items ai
                     LEFT JOIN product_variants pv ON ai.variant_id = pv.id
                     WHERE ai.allocation_id = wa.id) as total_value,
                    ao.outward_number, ao.dispatch_date, ao.vehicle_number, ao.driver_name, ao.total_boxes
             FROM warehouse_allocations wa
             LEFT JOIN sales_channels sc ON wa.channel_id = sc.id
             LEFT JOIN allocation_outwards ao ON ao.allocation_id = wa.id
             WHERE {$whereClause}
             ORDER BY wa.created_at DESC",
            $params
        );

        $totalAllocationsCount = count($allocations);
        $allocatedInventoryQty = 0;
        $allocatedTotalValue   = 0.0;
        $pendingCount = 0;
        $inProgressCount = 0;
        $dispatchedCount = 0;
        $completedCount = 0;
        $totalValuation = 0.0;
        $totalItemsCount = 0;

        foreach ($allocations as $a) {
            $qty = (int)($a['total_quantity'] ?? 0);
            $val = (float)($a['total_value'] ?? 0);
            $allocatedInventoryQty += $qty;
            $allocatedTotalValue   += $val;
            $totalValuation        += $val;
            $totalItemsCount       += $qty;

            $st = strtoupper($a['status'] ?? '');
            if (in_array($st, ['PENDING', 'REQUESTED', 'APPROVED'])) $pendingCount++;
            elseif ($st === 'IN_PROGRESS' || $st === 'PARTIAL_RECEIVED' || $st === 'PACKED' || $st === 'PICKED') $inProgressCount++;
            elseif ($st === 'DISPATCHED' || $st === 'IN_TRANSIT') $dispatchedCount++;
            elseif ($st === 'COMPLETED' || $st === 'DELIVERED' || $st === 'RECEIVED') $completedCount++;
        }

        $salesStats = Database::fetchOne(
            "SELECT COUNT(*) as order_count,
                    COALESCE(SUM(s.total_amount), 0) as total_revenue,
                    COALESCE(SUM((SELECT SUM(quantity) FROM sale_items WHERE sale_id = s.id)), 0) as sold_qty
             FROM sales s
             WHERE (s.sales_channel = 'WEBSITE' OR s.sale_type = 'WEBSITE' OR s.notes LIKE '%Website%')
               AND s.status != 'CANCELLED'"
        );

        $orderCount   = (int)($salesStats['order_count'] ?? 0);
        $totalRevenue = (float)($salesStats['total_revenue'] ?? 0);
        $soldQty      = (int)($salesStats['sold_qty'] ?? 0);

        // Fetch live available stock & valuation directly from stock_balances for accuracy
        $websiteChanIds = $this->getWebsiteChannelIds();
        $placeholders = implode(',', array_fill(0, count($websiteChanIds), '?'));
        $liveStock = Database::fetchOne(
            "SELECT COALESCE(SUM(CASE WHEN sb.stock_state = 'AVAILABLE' THEN sb.quantity ELSE 0 END), 0) as available_qty,
                    COALESCE(SUM(CASE WHEN sb.stock_state = 'DAMAGED' THEN sb.quantity ELSE 0 END), 0) as damaged_qty,
                    COALESCE(SUM(CASE WHEN sb.stock_state = 'AVAILABLE' THEN sb.quantity * COALESCE(NULLIF(pv.selling_price, 0), NULLIF(pv.mrp, 0), 250) ELSE 0 END), 0) as available_value
             FROM stock_balances sb
             LEFT JOIN product_variants pv ON sb.variant_id = pv.id
             WHERE sb.location_type = 'CHANNEL' AND sb.location_id IN ($placeholders)
               AND sb.concern_id = " . (int)currentConcernId(),
            $websiteChanIds
        );

        $liveAvailQty = (float)($liveStock['available_qty'] ?? 0);
        $liveAvailVal = (float)($liveStock['available_value'] ?? 0);

        $availableStockQty   = $liveAvailQty > 0 ? (int)$liveAvailQty : max(0, $allocatedInventoryQty - $soldQty);
        $availableStockValue = $liveAvailVal > 0 ? $liveAvailVal : round($availableStockQty * ($allocatedInventoryQty > 0 ? ($allocatedTotalValue / $allocatedInventoryQty) : 0), 2);

        $recentSales = Database::fetchAll(
            "SELECT s.*, 
                    s.invoice_number as number,
                    COALESCE(c.name, s.customer_name, 'Website Customer') as customer_name, 
                    COALESCE(c.name, s.customer_name, 'Website Customer') as customer_or_channel, 
                    COALESCE(c.phone, s.customer_phone, '') as customer_phone,
                    COALESCE(c.address, s.customer_address, '') as customer_address,
                    c.city as customer_city,
                    (SELECT COALESCE(SUM(quantity), 0) FROM sale_items WHERE sale_id = s.id) as quantity,
                    (SELECT COALESCE(SUM(quantity), 0) FROM sale_items WHERE sale_id = s.id) as total_pcs,
                    (SELECT COALESCE(SUM(quantity), 0) FROM sale_items WHERE sale_id = s.id) as total_items_count,
                    s.total_amount as amount,
                    s.created_at as date
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.sales_channel = 'WEBSITE' OR s.sale_type = 'WEBSITE' OR s.notes LIKE '%Website%'
             ORDER BY s.created_at DESC LIMIT 10"
        );

        Response::view('website_orders.index', [
            'pageTitle'              => 'Website Orders — Channel Dashboard',
            'activeModule'           => 'website',
            'activeMenu'             => 'wh-website-dashboard',
            'breadcrumb'             => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Dashboard']
            ],
            'channel'                => $chan,
            'allocations'            => $allocations,
            'orders'                 => $recentSales,
            'recentSales'            => $recentSales,
            'allocatedInventoryQty'  => $allocatedInventoryQty,
            'allocatedTotalValue'    => $allocatedTotalValue,
            'soldQty'                => $soldQty,
            'orderCount'             => $orderCount,
            'totalRevenue'           => $totalRevenue,
            'availableStockQty'      => $availableStockQty,
            'availableStockValue'    => $availableStockValue,
            'totalAllocationsCount'  => $totalAllocationsCount,
            'totalAllocatedQty'      => $allocatedInventoryQty,
            'totalAllocatedValue'    => $allocatedTotalValue,
            'pendingCount'           => $pendingCount,
            'inProgressCount'        => $inProgressCount,
            'dispatchedCount'        => $dispatchedCount,
            'completedCount'         => $completedCount,
            'totalItemsCount'        => $totalItemsCount,
            'totalValuation'         => $totalValuation,
            'search'                 => $search,
            'status'                 => $status,
            'dateFrom'               => $dateFrom,
            'dateTo'                 => $dateTo,
        ]);
    }

    /**
     * Website Channel Stock Inward / GRN Verification
     */
    public function websiteInwardIndex(): void {
        if (!hasPermission('website_inward', 'view')) {
            Session::flash('error', 'Access denied. You do not have permission to view Website Inward.');
            Response::redirect(url('warehouse/website-orders'));
            return;
        }
        $chan = $this->getChannel();
        list($allocWhereSql, $allocWhereParams) = $this->buildWebsiteAllocWhere('wa');

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');
        $warehouseId = trim($_GET['warehouse_id'] ?? '');

        $where = [$allocWhereSql];
        $where[] = "wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED')";
        $params = $allocWhereParams;

        if (!empty($search)) {
            $where[] = "(wa.allocation_number LIKE ? OR sc.name LIKE ? OR ao.outward_number LIKE ?
                         OR ao.vehicle_number LIKE ? OR ao.driver_name LIKE ? OR wa.notes LIKE ?
                         OR w.name LIKE ?
                         OR EXISTS (
                             SELECT 1
                             FROM allocation_items search_ai
                             JOIN product_variants search_pv ON search_ai.variant_id = search_pv.id
                             JOIN products search_p ON search_pv.product_id = search_p.id
                             WHERE search_ai.allocation_id = wa.id
                               AND (search_pv.sku LIKE ? OR search_p.name LIKE ?)
                         ))";
            $sWild = "%{$search}%";
            $params = array_merge($params, array_fill(0, 9, $sWild));
        }

        if ($warehouseId !== '') {
            $where[] = "wa.warehouse_id = ?";
            $params[] = (int)$warehouseId;
        }

        if (!empty($dateFrom)) {
            $where[] = "DATE(wa.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $where[] = "DATE(wa.created_at) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = implode(' AND ', $where);

        $allocations = Database::fetchAll(
            "SELECT wa.*, 
                    COALESCE(sc.name, 'Website Channel') as channel_name,
                    COALESCE(w.name, 'Main Warehouse') as warehouse_name,
                    w.code as warehouse_code,
                    (SELECT COALESCE(SUM(CASE WHEN dispatched_qty > 0 THEN dispatched_qty ELSE requested_qty END), 0) FROM allocation_items WHERE allocation_id = wa.id) as total_quantity,
                    (SELECT COALESCE(SUM(received_qty), 0) FROM allocation_items WHERE allocation_id = wa.id) as total_received_quantity,
                    (SELECT COALESCE(SUM(damaged_qty), 0) FROM allocation_items WHERE allocation_id = wa.id) as total_damaged_quantity,
                    (SELECT COUNT(DISTINCT variant_id) FROM allocation_items WHERE allocation_id = wa.id) as distinct_items_count,
                    (SELECT COALESCE(SUM((CASE WHEN ai.dispatched_qty > 0 THEN ai.dispatched_qty ELSE ai.requested_qty END) * COALESCE(NULLIF(pv.selling_price, 0), NULLIF(pv.mrp, 0), 250)), 0)
                     FROM allocation_items ai
                     JOIN product_variants pv ON ai.variant_id = pv.id
                     WHERE ai.allocation_id = wa.id) as total_value,
                    ao.outward_number, ao.dispatch_date, ao.vehicle_number, ao.driver_name, ao.driver_contact, ao.transporter_name, ao.total_boxes
             FROM warehouse_allocations wa
             LEFT JOIN sales_channels sc ON wa.channel_id = sc.id
             LEFT JOIN warehouses w ON wa.warehouse_id = w.id
             LEFT JOIN allocation_outwards ao ON ao.allocation_id = wa.id
             WHERE {$whereClause}
             ORDER BY wa.created_at DESC",
            $params
        );

        $allocIds = array_column($allocations, 'id');
        $allocItemsMap = [];
        if (!empty($allocIds)) {
            $placeholders = implode(',', array_fill(0, count($allocIds), '?'));
            $rawItems = Database::fetchAll(
                "SELECT ai.*, pv.sku, pv.qr_code, pv.size_id, pv.color_id, pv.selling_price, pv.mrp,
                        s.name as size_name, c.name as color_name, c.hex_code as color_hex,
                        p.name as product_name, p.image_url as product_image, cat.name as category_name
                 FROM allocation_items ai
                 JOIN product_variants pv ON ai.variant_id = pv.id
                 JOIN products p ON pv.product_id = p.id
                 LEFT JOIN categories cat ON p.category_id = cat.id
                 LEFT JOIN sizes s ON pv.size_id = s.id
                 LEFT JOIN colors c ON pv.color_id = c.id
                 WHERE ai.allocation_id IN ($placeholders)
                 ORDER BY ai.id ASC",
                $allocIds
            );
            foreach ($rawItems as $row) {
                $allocItemsMap[$row['allocation_id']][] = $row;
            }
        }

        $pendingBatchesCount = 0;
        $receivedBatchesCount = 0;
        $totalReceivedQty = 0;
        $totalPendingMissingQty = 0;
        $totalDispatchedQty = 0;
        $totalValuation = 0.0;

        foreach ($allocations as &$a) {
            $a['items'] = $allocItemsMap[$a['id']] ?? [];
            $a['total_variants'] = count($a['items']);
            $quantity = (int)($a['total_quantity'] ?? 0);
            $goodQty = (int)($a['total_received_quantity'] ?? 0);
            $damagedQty = (int)($a['total_damaged_quantity'] ?? 0);
            $st = strtoupper($a['status'] ?? '');
            if ($quantity > 0 && $goodQty === 0 && $damagedQty === 0 && in_array($st, ['COMPLETED', 'RECEIVED', 'DELIVERED'], true)) {
                foreach ($a['items'] as &$item) {
                    $item['received_qty'] = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : ($item['requested_qty'] ?? 0));
                    $goodQty += (int)$item['received_qty'];
                }
                unset($item);
            }
            $receivedQty = $goodQty + $damagedQty;
            $a['quantity'] = $quantity;
            $a['received_qty'] = $goodQty;
            $a['good_received_quantity'] = $goodQty;
            $a['damaged_qty'] = $damagedQty;
            $a['total_received_quantity'] = $receivedQty;
            $a['missing_qty'] = max(0, $quantity - $receivedQty);
            $a['status_state'] = $quantity > 0 && $a['missing_qty'] === 0
                ? 'RECEIVED'
                : (($receivedQty > 0 || in_array($st, ['IN_PROGRESS', 'PARTIAL_RECEIVED'], true)) ? 'PARTIAL' : 'IN_TRANSIT');

            $totalDispatchedQty += $quantity;
            $totalReceivedQty += $goodQty;
            if ($a['missing_qty'] > 0) {
                $pendingBatchesCount++;
                $totalPendingMissingQty += $a['missing_qty'];
            } elseif ($quantity > 0) {
                $receivedBatchesCount++;
            }
            $totalValuation += (float)($a['total_value'] ?? 0);
        }
        unset($a);

        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : [];

        Response::view('website_orders.inward', [
            'pageTitle'              => 'Website Inward & GRN Verification',
            'activeModule'           => 'website',
            'activeMenu'             => 'wh-website-inward',
            'breadcrumb'             => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Stock Inward / GRN']
            ],
            'channel'                => $chan,
            'allocations'            => $allocations,
            'batches'                => $allocations,
            'warehouses'             => $warehouses,
            'warehouseId'            => $warehouseId,
            'pendingBatchesCount'    => $pendingBatchesCount,
            'receivedBatchesCount'   => $receivedBatchesCount,
            'totalReceivedQty'       => $totalReceivedQty,
            'totalPendingMissingQty' => $totalPendingMissingQty,
            'totalDispatchedQty'     => $totalDispatchedQty,
            'totalValuation'         => $totalValuation,
            'search'                 => $search,
            'dateFrom'               => $dateFrom,
            'dateTo'                 => $dateTo,
        ]);
    }

    /**
     * View Allocation from Allocations Tab
     */
    public function viewWebsiteAllocation(int $id): void {
        $_GET['from'] = 'allocations';
        $this->viewWebsiteInward($id);
    }

    /**
     * Cross-Check Inward Receiving View for Website Channel
     */
    public function viewWebsiteInward(int $id): void {
        $fromSection = $_GET['from'] ?? 'inward';
        $chan = $this->getChannel();
        $allocation = Database::fetchOne(
            "SELECT wa.*, 
                    COALESCE(sc.name, 'Website Channel') as channel_name,
                    COALESCE(w.name, 'Main Central Warehouse') as warehouse_name,
                    w.code as warehouse_code,
                    ao.outward_number, ao.vehicle_number, ao.driver_name, ao.driver_contact, ao.transporter_name, ao.total_boxes, ao.dispatch_date
             FROM warehouse_allocations wa
             LEFT JOIN sales_channels sc ON wa.channel_id = sc.id
             LEFT JOIN warehouses w ON wa.warehouse_id = w.id
             LEFT JOIN allocation_outwards ao ON ao.allocation_id = wa.id
             WHERE wa.id = ?",
            [$id]
        );

        if (!$allocation) {
            $_SESSION['flash_error'] = "Stock Allocation not found.";
            Response::redirect(url('warehouse/website-orders/inward'));
            return;
        }

        $items = Database::fetchAll(
            "SELECT ai.*, pv.sku, pv.qr_code, pv.size_id, pv.color_id, pv.selling_price, pv.mrp,
                    pv.qr_type as variant_qr_type, pv.pack_quantity,
                    pv.is_complementary as variant_is_complementary,
                    p.name as product_name, p.code as product_code, p.image_url as product_image,
                    p.qr_code_prefix, p.category_code, p.subcategory_code, p.sub_subcategory_code,
                    p.is_complementary as product_is_complementary,
                    wc.category_type as warehouse_category_type,
                    cat.name as category_name, cat.code as category_code_tbl,
                    subcat.name as subcategory_name, subcat.code as subcategory_code_tbl,
                    subsub.name as sub_subcategory_name, subsub.code as sub_subcategory_code_tbl,
                    s.name as size_name, c.name as color_name, c.hex_code as color_hex
             FROM allocation_items ai
             JOIN product_variants pv ON ai.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsub ON p.sub_subcategory_id = subsub.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE ai.allocation_id = ?
             ORDER BY ai.id ASC",
            [$id]
        );

        $totalExpected = 0;
        $totalReceived = 0;
        $totalDamaged = 0;
        $totalMissing = 0;

        $pieceQRs = [];
        $monthStr = sprintf('%02d', date('n', strtotime($allocation['created_at'] ?? 'now')));
        $yearStr = date('y', strtotime($allocation['created_at'] ?? 'now'));

        foreach ($items as &$item) {
            $exp = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : ($item['requested_qty'] ?? 1));
            $item['quantity'] = $exp;
            $rec = (int)($item['received_qty'] ?? (in_array(strtoupper($allocation['status'] ?? ''), ['COMPLETED', 'RECEIVED']) ? $exp : 0));
            $dmg = (int)($item['damaged_qty'] ?? 0);
            $mis = max(0, $exp - ($rec + $dmg));

            $item['received_qty'] = $rec;
            $item['damaged_qty'] = $dmg;
            $item['missing_qty'] = $mis;

            $totalExpected += $exp;
            $totalReceived += $rec;
            $totalDamaged += $dmg;
            $totalMissing += $mis;

            $warehouseCategoryType = strtoupper(trim((string)($item['warehouse_category_type'] ?? 'NORMAL')));
            $variantQrType = strtoupper(trim((string)($item['variant_qr_type'] ?? '')));
            $item['product_type'] = (!empty($item['product_is_complementary']) || !empty($item['variant_is_complementary']) || $warehouseCategoryType === 'COMPLEMENTARY')
                ? 'COMPLEMENTARY'
                : ($warehouseCategoryType === 'SERIES'
                    || in_array($variantQrType, ['SERIES', 'SERIES_BATCH'], true)
                    || (int)($item['pack_quantity'] ?? 1) > 1
                    ? 'SERIES'
                    : 'NORMAL');

            // Generate dynamic piece QR codes identical to warehouse allocation
            $c1 = !empty($item['category_name']) ? trim($item['category_name']) : (!empty($item['category_code']) ? trim($item['category_code']) : '');
            $c2 = !empty($item['subcategory_name']) ? trim($item['subcategory_name']) : (!empty($item['subcategory_code']) ? trim($item['subcategory_code']) : '');
            $c3 = !empty($item['sub_subcategory_name']) ? trim($item['sub_subcategory_name']) : (!empty($item['sub_subcategory_code']) ? trim($item['sub_subcategory_code']) : '');

            $prefix = '';
            if ($c1 !== '') $prefix .= strtoupper($c1[0]);
            if ($c2 !== '') $prefix .= strtoupper($c2[0]);
            if ($c3 !== '') $prefix .= strtoupper($c3[0]);

            if (strlen($prefix) < 2) {
                if (!empty($item['qr_code_prefix'])) {
                    $clean = preg_replace('/[^A-Za-z0-9]/', '', $item['qr_code_prefix']);
                    $prefix = strtoupper(substr($clean, 0, 3));
                }
            }
            if (empty($prefix)) {
                $prefix = 'TTS';
            }

            $fmtMrp = (!empty($item['mrp']) && (float)$item['mrp'] > 0) ? (((int)$item['mrp'] == $item['mrp']) ? (int)$item['mrp'] : number_format($item['mrp'], 2, '.', '')) : '999';
            $costVal = (!empty($item['selling_price']) && (float)$item['selling_price'] > 0) ? (float)$item['selling_price'] : ((!empty($item['mrp']) && (float)$item['mrp'] > 0) ? (float)$item['mrp'] : 599);
            $fmtDisc = ($costVal == (int)$costVal) ? (int)$costVal : number_format($costVal, 2, '.', '');

            $savedQrCodes = !empty($item['selected_qr_codes']) ? array_filter(array_map('trim', explode(',', $item['selected_qr_codes']))) : [];
            $itemPieceQRs = [];

            if (!empty($savedQrCodes)) {
                $pIdx = 1;
                foreach ($savedQrCodes as $code) {
                    $qrPayload = "{$code}|{$item['sku']}|MRP:{$fmtMrp}|Disc:{$fmtDisc}";
                    $isGood = ($pIdx <= $rec);
                    $isDmg = ($pIdx > $rec && $pIdx <= ($rec + $dmg));
                    $pieceData = [
                        'item_id'       => (int)$item['id'],
                        'variant_id'    => (int)$item['variant_id'],
                        'piece_no'      => $pIdx++,
                        'tag_code'      => $code,
                        'sku'           => $item['sku'],
                        'product_name'  => $item['product_name'] ?? 'Garment Product',
                        'category_name' => $item['category_name'] ?? 'Apparel',
                        'color_name'    => $item['color_name'] ?? 'Std',
                        'size_name'     => $item['size_name'] ?? 'Free',
                        'mrp'           => $fmtMrp,
                        'disc'          => $fmtDisc,
                        'selling_price' => $costVal,
                        'qr_payload'    => $qrPayload,
                        'status'        => $isGood ? 'good' : ($isDmg ? 'damaged' : 'pending'),
                    ];
                    $pieceQRs[] = $pieceData;
                    $itemPieceQRs[] = $pieceData;
                }
            } else {
                for ($p = 1; $p <= $exp; $p++) {
                    $tagCode = $prefix . sprintf("%02d", $monthStr) . $yearStr . sprintf("%02d", $p);
                    $qrPayload = "{$tagCode}|{$item['sku']}|MRP:{$fmtMrp}|Disc:{$fmtDisc}";
                    $isGood = ($p <= $rec);
                    $isDmg = ($p > $rec && $p <= ($rec + $dmg));
                    $pieceData = [
                        'item_id'       => (int)$item['id'],
                        'variant_id'    => (int)$item['variant_id'],
                        'piece_no'      => $p,
                        'tag_code'      => $tagCode,
                        'sku'           => $item['sku'],
                        'product_name'  => $item['product_name'] ?? 'Garment Product',
                        'category_name' => $item['category_name'] ?? 'Apparel',
                        'color_name'    => $item['color_name'] ?? 'Std',
                        'size_name'     => $item['size_name'] ?? 'Free',
                        'mrp'           => $fmtMrp,
                        'disc'          => $fmtDisc,
                        'selling_price' => $costVal,
                        'qr_payload'    => $qrPayload,
                        'status'        => $isGood ? 'good' : ($isDmg ? 'damaged' : 'pending'),
                    ];
                    $pieceQRs[] = $pieceData;
                    $itemPieceQRs[] = $pieceData;
                }
            }

            $item['piece_qrs'] = $itemPieceQRs;
            if (!empty($itemPieceQRs)) {
                $firstTag = $itemPieceQRs[0]['tag_code'];
                $lastTag = $itemPieceQRs[count($itemPieceQRs) - 1]['tag_code'];
                $item['qr_range'] = ($firstTag === $lastTag) ? $firstTag : "{$firstTag} - {$lastTag}";
            } else {
                $item['qr_range'] = $item['sku'];
            }
        }
        unset($item);

        $activeMenu = ($fromSection === 'allocations') ? 'wh-website-allocations' : 'wh-website-inward';
        $breadcrumb = ($fromSection === 'allocations') ? [
            ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
            ['label' => 'Website Inventory', 'url' => url('warehouse/website-orders/allocations')],
            ['label' => 'Allocation #' . ($allocation['allocation_number'] ?? $id)]
        ] : [
            ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
            ['label' => 'Stock Inward', 'url' => url('warehouse/website-orders/inward')],
            ['label' => 'Cross-Check Batch']
        ];

        Response::view('website_orders.inward_view', [
            'pageTitle'     => 'Allocation & Inward Batch #' . ($allocation['allocation_number'] ?? ('ALC-' . str_pad($id, 5, '0', STR_PAD_LEFT))),
            'activeModule'  => 'website',
            'activeMenu'    => $activeMenu,
            'fromSection'   => $fromSection,
            'channel'       => $chan,
            'allocation'    => $allocation,
            'pieceQRs'      => $pieceQRs,
            'batch'         => [
                'id'                   => $allocation['id'],
                'batch_number'         => $allocation['allocation_number'] ?? ('BAT-20260917-' . str_pad($id, 4, '0', STR_PAD_LEFT)),
                'allocation_number'    => $allocation['allocation_number'] ?? ('ALC-' . str_pad($id, 5, '0', STR_PAD_LEFT)),
                'outward_number'       => $allocation['outward_number'] ?? ('OUT-' . str_pad($id, 5, '0', STR_PAD_LEFT)),
                'program_number'       => 'PRG-' . date('Ymd', strtotime($allocation['created_at'] ?? 'now')) . '-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                'product_name'         => $items[0]['product_name'] ?? 'Assorted Garments & Merchandise',
                'production_unit_name' => $allocation['warehouse_name'] ?? 'Main Central Warehouse',
                'production_unit_code' => $allocation['warehouse_code'] ?? 'WH-01',
                'warehouse_name'       => $allocation['channel_name'] ?? 'Website Channel',
                'warehouse_code'       => 'WEB-01',
                'vehicle_number'       => $allocation['vehicle_number'] ?? 'Direct Transfer',
                'status'               => $allocation['status'],
                'box_count'            => $allocation['total_boxes'] ?? 1,
                'created_at'           => $allocation['created_at'],
            ],
            'items'         => $items,
            'totalExpected' => $totalExpected,
            'totalReceived' => $totalReceived,
            'totalDamaged'  => $totalDamaged,
            'totalMissing'  => $totalMissing,
            'breadcrumb'    => $breadcrumb,
        ]);
    }

    /**
     * Submit Inward Receipt Verification with Automatic Damage Quarantine
     */
    public function receiveWebsiteInward(int $id): void {
        if (!hasPermission('website_inward', 'receive')) {
            Session::flash('error', 'Access denied. You do not have permission to receive Website inward stock.');
            Response::redirect(url('warehouse/website-orders/inward'));
            return;
        }
        $allocation = Database::fetchOne("SELECT * FROM warehouse_allocations WHERE id = ?", [$id]);
        if (!$allocation) {
            $_SESSION['flash_error'] = "Stock Allocation not found.";
            Response::redirect(url('warehouse/website-orders/inward'));
            return;
        }

        $items = Database::fetchAll("SELECT * FROM allocation_items WHERE allocation_id = ?", [$id]);
        $isReceiveAll  = !empty($_POST['receive_all']);
        $receivedInput = $_POST['received_qty'] ?? [];
        $damagedInput  = $_POST['damaged_qty'] ?? [];
        // Session IDs can outlive a deleted user account. stock_transactions.user_id
        // references users.id, so only write an actor ID that still exists; NULL is
        // permitted by the FK and preserves the inward receipt when the account is gone.
        $sessionUserId = (int)(Session::get('user_id') ?? 0);
        $userId        = $sessionUserId > 0 && Database::fetchOne("SELECT id FROM users WHERE id = ?", [$sessionUserId])
            ? $sessionUserId
            : null;
        $channelId     = (int)($allocation['channel_id'] ?? 2);

        $totalDispatched = 0;
        $totalRec        = 0;
        $totalDmg        = 0;

        Database::beginTransaction();
        try {
            foreach ($items as $item) {
                $itemId = $item['id'];
                $dispQty = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : ($item['requested_qty'] ?? 1));
                
                $prevRec = (int)($item['received_qty'] ?? 0);
                $prevDmg = (int)($item['damaged_qty'] ?? 0);

                if ($isReceiveAll) {
                    $newRec = $dispQty;
                    $newDmg = 0;
                } else {
                    $newRec = isset($receivedInput[$itemId]) ? (int)$receivedInput[$itemId] : $dispQty;
                    $newDmg = isset($damagedInput[$itemId]) ? (int)$damagedInput[$itemId] : 0;
                }

                $newRec = max(0, min($dispQty, $newRec));
                $newDmg = max(0, min($dispQty - $newRec, $newDmg));

                Database::execute(
                    "UPDATE allocation_items SET received_qty = ?, damaged_qty = ? WHERE id = ?",
                    [$newRec, $newDmg, $itemId]
                );

                $deltaRec = $newRec - $prevRec;
                // Post AVAILABLE stock to Website Channel
                if ($deltaRec > 0 && !empty($item['variant_id'])) {
                    StockService::increase(
                        variantId: (int)$item['variant_id'],
                        locationType: 'CHANNEL',
                        locationId: $channelId,
                        stockState: 'AVAILABLE',
                        quantity: (float)$deltaRec,
                        transactionType: 'TRANSFER_IN',
                        referenceType: 'warehouse_allocations',
                        referenceId: $id,
                        userId: $userId,
                        notes: "Inward received into Website Channel for Allocation #" . ($allocation['allocation_number'] ?? $id)
                    );
                } elseif ($deltaRec < 0 && !empty($item['variant_id'])) {
                    StockService::decrease(
                        variantId: (int)$item['variant_id'],
                        locationType: 'CHANNEL',
                        locationId: $channelId,
                        stockState: 'AVAILABLE',
                        quantity: (float)abs($deltaRec),
                        transactionType: 'TRANSFER_IN',
                        referenceType: 'warehouse_allocations',
                        referenceId: $id,
                        userId: $userId,
                        notes: "Inward adjustment (reduced) for Website Channel Allocation #" . ($allocation['allocation_number'] ?? $id)
                    );
                }

                $deltaDmg = $newDmg - $prevDmg;
                // Post DAMAGED stock to Website Channel & auto-log in ChannelDamage
                if ($deltaDmg > 0 && !empty($item['variant_id'])) {
                    StockService::increase(
                        variantId: (int)$item['variant_id'],
                        locationType: 'CHANNEL',
                        locationId: $channelId,
                        stockState: 'DAMAGED',
                        quantity: (float)$deltaDmg,
                        transactionType: 'DAMAGE_LOGGED',
                        referenceType: 'warehouse_allocations',
                        referenceId: $id,
                        userId: $userId,
                        notes: "Damaged stock detected during inward for Allocation #" . ($allocation['allocation_number'] ?? $id)
                    );

                    // Auto-log into channel_damages
                    $savedCodes = !empty($item['selected_qr_codes']) ? array_filter(array_map('trim', explode(',', $item['selected_qr_codes']))) : [];
                    $damagedTagCodes = '';
                    if (!empty($savedCodes)) {
                        $damagedTagsSlice = array_slice($savedCodes, $newRec, $deltaDmg);
                        $damagedTagCodes = implode(', ', $damagedTagsSlice);
                    }

                    ChannelDamage::logInwardDamage(
                        channelId: $channelId,
                        allocationId: $id,
                        variantId: (int)$item['variant_id'],
                        quantity: $deltaDmg,
                        tagCodes: $damagedTagCodes,
                        reportedBy: $userId,
                        notes: "Damaged stock detected during Website inward receipt for Allocation #" . ($allocation['allocation_number'] ?? $id)
                    );
                } elseif ($deltaDmg < 0 && !empty($item['variant_id'])) {
                    StockService::decrease(
                        variantId: (int)$item['variant_id'],
                        locationType: 'CHANNEL',
                        locationId: $channelId,
                        stockState: 'DAMAGED',
                        quantity: (float)abs($deltaDmg),
                        transactionType: 'DAMAGE_LOGGED',
                        referenceType: 'warehouse_allocations',
                        referenceId: $id,
                        userId: $userId,
                        notes: "Damaged stock adjustment (reduced) for Website Channel Allocation #" . ($allocation['allocation_number'] ?? $id)
                    );
                }

                $totalDispatched += $dispQty;
                $totalRec        += $newRec;
                $totalDmg        += $newDmg;
            }

            $newStatus = ($totalRec + $totalDmg >= $totalDispatched) ? 'COMPLETED' : 'PARTIAL_RECEIVED';
            Database::execute("UPDATE warehouse_allocations SET status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $id]);
            Database::execute("UPDATE allocation_outwards SET status = 'DELIVERED', updated_at = NOW() WHERE allocation_id = ?", [$id]);

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }

        $shortage = max(0, $totalDispatched - ($totalRec + $totalDmg));
        if ($shortage > 0) {
            $_SESSION['flash_warning'] = "Partial inward saved for Allocation #" . ($allocation['allocation_number'] ?? $id) . ": {$totalRec} pcs credited, {$shortage} pcs pending missing.";
        } else {
            $_SESSION['flash_success'] = "Stock Allocation #" . ($allocation['allocation_number'] ?? $id) . " fully received & credited to Website Channel inventory ({$totalRec} pcs)!";
        }

        Response::redirect(url('warehouse/website-orders/inward'));
    }

    /**
     * AJAX endpoint to retrieve full traceability, inward audit log & stock ledger transactions
     */
    public function ajaxBatchHistory(string $id): void {
        $allocId = (int)$id;
        $allocation = Database::fetchOne(
            "SELECT wa.*, 
                    COALESCE(sc.name, 'Website Orders Channel') as channel_name,
                    sc.code as channel_code,
                    COALESCE(w.name, 'Main Central Warehouse - Tirupur') as warehouse_name,
                    COALESCE(w.code, 'WH-01') as warehouse_code,
                    ao.outward_number, ao.vehicle_number, ao.driver_name, ao.driver_contact, ao.transporter_name, ao.total_boxes, ao.dispatch_date,
                    u_appr.full_name as approved_by_name,
                    u_prep.full_name as created_by_name
             FROM warehouse_allocations wa
             LEFT JOIN sales_channels sc ON wa.channel_id = sc.id
             LEFT JOIN warehouses w ON wa.warehouse_id = w.id
             LEFT JOIN allocation_outwards ao ON ao.allocation_id = wa.id
             LEFT JOIN users u_appr ON wa.approved_by = u_appr.id
             LEFT JOIN users u_prep ON wa.created_by = u_prep.id
             WHERE wa.id = ?",
            [$allocId]
        );

        if (!$allocation) {
            Response::jsonError('Stock Allocation not found.', [], 404);
            return;
        }

        $items = Database::fetchAll(
            "SELECT ai.*, pv.sku, pv.qr_code, pv.size_id, pv.color_id, pv.selling_price, pv.mrp,
                    p.name as product_name, p.code as product_code, p.image_url as product_image,
                    s.name as size_name, c.name as color_name, c.hex_code as color_hex
             FROM allocation_items ai
             JOIN product_variants pv ON ai.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE ai.allocation_id = ?
             ORDER BY ai.id ASC",
            [$allocId]
        );

        $txns = Database::fetchAll(
            "SELECT st.*, pv.sku, p.name as product_name, s.name as size_name, c.name as color_name,
                    u.full_name as user_name
             FROM stock_transactions st
             JOIN product_variants pv ON st.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN users u ON st.user_id = u.id
             WHERE st.reference_type = 'warehouse_allocations' AND st.reference_id = ? AND st.concern_id = ?
             ORDER BY st.id ASC",
            [$allocId, (int)currentConcernId()]
        );

        $damages = Database::fetchAll(
            "SELECT cd.*, pv.sku, p.name as product_name, s.name as size_name, c.name as color_name
             FROM channel_damages cd
             JOIN product_variants pv ON cd.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE cd.allocation_id = ? OR cd.notes LIKE ?
             ORDER BY cd.id ASC",
            [$allocId, "%Allocation #" . ($allocation['allocation_number'] ?? $allocId) . "%"]
        );

        Response::jsonSuccess('Website Inward batch lifecycle history loaded', [
            'allocation' => $allocation,
            'items'      => $items,
            'txns'       => $txns,
            'damages'    => $damages,
        ]);
    }

    /**
     * Stock Allocations List & Stock Inventory for Website Orders (Dual Tab)
     */
    public function websiteAllocations(): void {
        if (!hasPermission('website_inventory', 'view')) {
            Session::flash('error', 'Access denied. You do not have permission to view Website stock allocations & inventory.');
            Response::redirect(url('warehouse/website-orders'));
            return;
        }
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 2);
        $activeTab = $_GET['tab'] ?? 'stock';
        $productType = strtolower(trim($_GET['product_type'] ?? 'normal'));

        $search = trim($_GET['search'] ?? '');
        $categoryId = (int)($_GET['category_id'] ?? 0);
        $stockStatus = strtolower(trim($_GET['stock_status'] ?? ''));
        $sort = $_GET['sort'] ?? 'name_asc';
        $allocStatus = trim($_GET['alloc_status'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $categories = Database::fetchAll("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");

        $chanIds = $this->getWebsiteChannelIds();
        $chanIn = implode(',', array_fill(0, count($chanIds), '?'));
        $destList = [];
        foreach ($chanIds as $cid) {
            $destList[] = 'CHANNEL_' . $cid;
        }
        $destIn = implode(',', array_fill(0, count($destList), '?'));

        $stockSql = "
            SELECT pv.id as variant_id, pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.purchase_price, pv.production_cost,
                   pv.image_url as variant_image,
                   p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                   p.category_code, p.min_stock, p.max_stock, p.description, p.is_complementary,
                   pv.is_complementary as variant_is_complementary, pv.qr_type, pv.pack_quantity,
                   cat.id as category_id, cat.name as category_name,
                   subcat.name as subcategory_name,
                   s.name as size_name,
                   c.name as color_name,
                   COALESCE((
                       SELECT SUM(sb.quantity) FROM stock_balances sb 
                       WHERE sb.variant_id = pv.id AND sb.location_type = 'CHANNEL' AND sb.location_id IN ($chanIn) AND sb.concern_id = " . (int)currentConcernId() . " AND sb.stock_state = 'AVAILABLE'
                   ), 0) as available_quantity,
                   COALESCE((
                       SELECT SUM(sb.quantity) FROM stock_balances sb 
                       WHERE sb.variant_id = pv.id AND sb.location_type = 'CHANNEL' AND sb.location_id IN ($chanIn) AND sb.concern_id = " . (int)currentConcernId() . " AND sb.stock_state = 'DAMAGED'
                   ), 0) as damaged_quantity,
                   COALESCE((
                       SELECT SUM(si.quantity) 
                       FROM sale_items si 
                       JOIN sales sl ON si.sale_id = sl.id 
                       WHERE si.variant_id = pv.id AND (sl.sales_channel = 'WEBSITE' OR sl.notes LIKE '%Website%') AND sl.status != 'CANCELLED'
                   ), 0) as sold_quantity,
                   COALESCE((
                       SELECT SUM(ai.received_qty) 
                       FROM allocation_items ai 
                       JOIN warehouse_allocations wa ON ai.allocation_id = wa.id 
                       WHERE wa.concern_id = " . (int)currentConcernId() . " AND ai.variant_id = pv.id AND ((wa.destination_type = 'CHANNEL' AND wa.destination_id IN ($chanIn)) OR wa.destination_type IN ($destIn) OR wa.destination_type = 'WEBSITE')
                   ), 0) as total_inward_quantity
            FROM product_variants pv
            JOIN products p ON pv.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
            LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
            LEFT JOIN sizes s ON pv.size_id = s.id
            LEFT JOIN colors c ON pv.color_id = c.id
            WHERE (
                EXISTS (
                    SELECT 1 FROM stock_balances sb 
                    WHERE sb.variant_id = pv.id AND sb.location_type = 'CHANNEL' AND sb.location_id IN ($chanIn) AND sb.concern_id = " . (int)currentConcernId() . " AND sb.quantity > 0
                )
                OR EXISTS (
                    SELECT 1 FROM allocation_items ai 
                    JOIN warehouse_allocations wa ON ai.allocation_id = wa.id 
                    WHERE wa.concern_id = " . (int)currentConcernId() . " AND ai.variant_id = pv.id
                      AND (COALESCE(ai.received_qty, 0) > 0 OR COALESCE(ai.damaged_qty, 0) > 0)
                      AND wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED')
                      AND ((wa.destination_type = 'CHANNEL' AND wa.destination_id IN ($chanIn)) OR wa.destination_type IN ($destIn) OR wa.destination_type = 'WEBSITE')
                )
            )
        ";

        $stockParams = array_merge(
            $chanIds, // available
            $chanIds, // damaged
            // sold has no params
            $chanIds, $destList, // inward
            $chanIds, // where exists stock
            $chanIds, $destList // where exists inward
        );
        $allStockItems = Database::fetchAll($stockSql, $stockParams);

        $summary = [
            'total_units'         => 0,
            'total_skus'          => count($allStockItems),
            'total_valuation'     => 0.0,
            'total_mrp_valuation' => 0.0,
            'in_stock_count'      => 0,
            'low_stock_count'     => 0,
            'out_of_stock_count'  => 0,
            'damaged_units'       => 0,
            'damaged_loss'        => 0.0,
            'total_sold_units'    => 0,
            'total_inward_units'  => 0
        ];

        foreach ($allStockItems as $it) {
            $avail     = (float)$it['available_quantity'];
            $dmg       = (float)$it['damaged_quantity'];
            $sold      = (float)$it['sold_quantity'];
            $inw       = (float)$it['total_inward_quantity'];
            $sellPrice = (float)($it['selling_price'] > 0 ? $it['selling_price'] : ($it['mrp'] > 0 ? $it['mrp'] : 100));
            $mrpPrice  = (float)($it['mrp'] > 0 ? $it['mrp'] : $sellPrice);
            $costPrice = (float)($it['production_cost'] > 0 ? $it['production_cost'] : ($it['purchase_price'] > 0 ? $it['purchase_price'] : $sellPrice));

            $summary['total_units']         += $avail;
            $summary['total_valuation']     += ($avail * $sellPrice);
            $summary['total_mrp_valuation'] += ($avail * $mrpPrice);
            $summary['damaged_units']       += $dmg;
            $summary['damaged_loss']        += ($dmg * $costPrice);
            $summary['total_sold_units']    += $sold;
            $summary['total_inward_units']  += $inw;

            $minStock = (int)($it['min_stock'] ?? 5);
            if ($avail <= 0) {
                $summary['out_of_stock_count']++;
            } elseif ($avail <= $minStock) {
                $summary['low_stock_count']++;
            } else {
                $summary['in_stock_count']++;
            }
        }

        $filteredStockItems = array_filter($allStockItems, function($it) use ($search, $categoryId, $stockStatus) {
            if ($categoryId > 0 && (int)($it['category_id'] ?? 0) !== $categoryId) {
                return false;
            }
            if (!empty($search)) {
                $term = strtolower($search);
                $haystack = strtolower(($it['product_name'] ?? '') . ' ' . ($it['sku'] ?? '') . ' ' . ($it['product_code'] ?? '') . ' ' . ($it['qr_code'] ?? '') . ' ' . ($it['category_name'] ?? ''));
                if (!str_contains($haystack, $term)) {
                    return false;
                }
            }
            $avail = (float)$it['available_quantity'];
            $dmg = (float)$it['damaged_quantity'];
            $minStock = (int)($it['min_stock'] ?? 5);

            if ($stockStatus === 'in_stock' && $avail <= $minStock) return false;
            if ($stockStatus === 'low_stock' && ($avail <= 0 || $avail > $minStock)) return false;
            if ($stockStatus === 'out_of_stock' && $avail > 0) return false;
            if ($stockStatus === 'damaged' && $dmg <= 0) return false;

            return true;
        });

        $productBuckets = ['normal' => [], 'series' => [], 'complementary' => []];
        foreach ($filteredStockItems as $it) {
            $isComp = !empty($it['is_complementary']) || !empty($it['variant_is_complementary']);
            $isSeries = (($it['qr_type'] ?? '') === 'SERIES_BATCH' || (int)($it['pack_quantity'] ?? 1) > 1);
            $productBuckets[$isComp ? 'complementary' : ($isSeries ? 'series' : 'normal')][] = $it;
        }
        if (!array_key_exists($productType, $productBuckets)) $productType = 'normal';
        $filteredStockItems = $productBuckets[$productType];

        usort($filteredStockItems, function($a, $b) use ($sort) {
            return match($sort) {
                'stock_desc' => (float)$b['available_quantity'] <=> (float)$a['available_quantity'],
                'stock_asc'  => (float)$a['available_quantity'] <=> (float)$b['available_quantity'],
                'price_desc' => (float)$b['selling_price'] <=> (float)$a['selling_price'],
                'price_asc'  => (float)$a['selling_price'] <=> (float)$b['selling_price'],
                'name_desc'  => strcasecmp($b['product_name'] ?? '', $a['product_name'] ?? ''),
                'sku_asc'    => strcasecmp($a['sku'] ?? '', $b['sku'] ?? ''),
                default      => strcasecmp($a['product_name'] ?? '', $b['product_name'] ?? '')
            };
        });

        list($allocWhereSql, $allocWhereParams) = $this->buildWebsiteAllocWhere('wa');
        $allocWhere = [$allocWhereSql];
        $allocWhere[] = "wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED')";
        $allocParams = $allocWhereParams;

        if (!empty($search)) {
            $allocWhere[] = "(wa.allocation_number LIKE ? OR sc.name LIKE ? OR wa.notes LIKE ?)";
            $sWild = "%{$search}%";
            $allocParams[] = $sWild;
            $allocParams[] = $sWild;
            $allocParams[] = $sWild;
        }

        if (!empty($allocStatus)) {
            $allocWhere[] = "wa.status = ?";
            $allocParams[] = $allocStatus;
        }

        if (!empty($dateFrom)) {
            $allocWhere[] = "DATE(wa.created_at) >= ?";
            $allocParams[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $allocWhere[] = "DATE(wa.created_at) <= ?";
            $allocParams[] = $dateTo;
        }

        $allocWhereClause = implode(' AND ', $allocWhere);

        $allocations = Database::fetchAll(
            "SELECT wa.*, 
                    COALESCE(sc.name, 'Website Channel') as channel_name,
                    (SELECT COALESCE(SUM(GREATEST(dispatched_qty, requested_qty)), 0) FROM allocation_items WHERE allocation_id = wa.id) as total_quantity,
                    (SELECT COUNT(DISTINCT variant_id) FROM allocation_items WHERE allocation_id = wa.id) as distinct_items_count,
                    (SELECT COALESCE(SUM(GREATEST(ai.dispatched_qty, ai.requested_qty) * COALESCE(NULLIF(pv.selling_price, 0), NULLIF(pv.mrp, 0), 250)), 0)
                     FROM allocation_items ai
                     JOIN product_variants pv ON ai.variant_id = pv.id
                     WHERE ai.allocation_id = wa.id) as total_value
             FROM warehouse_allocations wa
             LEFT JOIN sales_channels sc ON wa.channel_id = sc.id
             WHERE {$allocWhereClause}
             ORDER BY wa.created_at DESC",
            $allocParams
        );

        $totalAllocationsCount = count($allocations);
        $totalAllocatedQty = 0;
        $totalAllocatedValue = 0.0;
        $dispatchedCount = 0;

        foreach ($allocations as $a) {
            $totalAllocatedQty += (int)($a['total_quantity'] ?? 0);
            $totalAllocatedValue += (float)($a['total_value'] ?? 0);
            if (in_array($a['status'], ['DISPATCHED', 'COMPLETED', 'DELIVERED'])) {
                $dispatchedCount++;
            }
        }

        Response::view('website_orders.allocations', [
            'pageTitle'             => 'Website Stock & Inventory',
            'activeModule'          => 'website',
            'activeMenu'            => 'wh-website-allocations',
            'breadcrumb'            => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Stock Inventory']
            ],
            'channel'               => $chan,
            'activeTab'             => $activeTab,
            'inventory'             => $filteredStockItems,
            'summary'               => $summary,
            'categories'            => $categories,
            'filters'               => [
                'search'       => $search,
                'category_id'  => $categoryId,
                'stock_status' => $stockStatus,
                'sort'         => $sort,
                'alloc_status' => $allocStatus,
                'date_from'    => $dateFrom,
                'date_to'      => $dateTo,
                'product_type' => $productType,
            ],
            'productBuckets'        => $productBuckets,
            'allocations'           => $allocations,
            'totalAllocationsCount' => $totalAllocationsCount,
            'totalAllocatedQty'     => $totalAllocatedQty,
            'totalAllocatedValue'   => $totalAllocatedValue,
            'dispatchedCount'       => $dispatchedCount,
        ]);
    }

    /**
     * Website Product Stock Details & Full Lifecycle History with Piece QR & Serial Tags
     */
     public function websiteInventoryDetail(int $id): void {
         if (!hasPermission('website_inventory', 'view')) {
             Session::flash('error', 'Access denied. You do not have permission to view Website stock details.');
             Response::redirect(url('warehouse/website-orders/allocations'));
             return;
         }
         $chan = $this->getChannel();
        $chanIds = $this->getWebsiteChannelIds();
        $chanIn = implode(',', array_fill(0, count($chanIds), '?'));
        $destList = [];
        foreach ($chanIds as $cid) {
            $destList[] = 'CHANNEL_' . $cid;
        }
        $destIn = implode(',', array_fill(0, count($destList), '?'));
        $variantId = (int)$id;

        $sql = "
            SELECT pv.*, pv.id as variant_id,
                   pv.weight as variant_weight, pv.image_url as variant_image,
                   p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                   p.category_code, p.subcategory_code, p.sub_subcategory_code, p.qr_code_prefix,
                   p.description, p.material, p.hsn_code, p.gst_percentage, p.min_stock, p.max_stock, p.source_type,
                   p.is_complementary as product_is_complementary,
                   wc.category_type as warehouse_category_type,
                   s.name as size_name, c.name as color_name, 
                   cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name,
                   b.name as brand_name, sel.name as seller_name,
                   COALESCE((
                       SELECT SUM(sb.quantity) FROM stock_balances sb 
                       WHERE sb.variant_id = pv.id AND sb.location_type = 'CHANNEL' AND sb.location_id IN ($chanIn) AND sb.concern_id = " . (int)currentConcernId() . " AND sb.stock_state = 'AVAILABLE'
                   ), 0) as available_quantity,
                   COALESCE((
                       SELECT SUM(sb.quantity) FROM stock_balances sb 
                       WHERE sb.variant_id = pv.id AND sb.location_type = 'CHANNEL' AND sb.location_id IN ($chanIn) AND sb.concern_id = " . (int)currentConcernId() . " AND sb.stock_state = 'DAMAGED'
                   ), 0) as damaged_quantity
            FROM product_variants pv
            JOIN products p ON pv.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
            LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
            LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN sellers sel ON p.seller_id = sel.id
            LEFT JOIN sizes s ON pv.size_id = s.id
            LEFT JOIN colors c ON pv.color_id = c.id
            WHERE pv.id = ?
        ";

        $item = Database::fetchOne($sql, array_merge($chanIds, $chanIds, [$variantId]));

        if (!$item) {
            $_SESSION['flash_error'] = "Product variant not found.";
            Response::redirect(url('warehouse/website-orders/allocations'));
            return;
        }

         $isPackType = !empty($item['product_is_complementary'])
             || !empty($item['is_complementary'])
             || strtoupper((string)($item['warehouse_category_type'] ?? '')) === 'COMPLEMENTARY'
             || strtoupper((string)($item['warehouse_category_type'] ?? '')) === 'SERIES'
             || strtoupper((string)($item['qr_type'] ?? '')) === 'SERIES_BATCH'
             || (int)($item['pack_quantity'] ?? 1) > 1;

        // 1. Stock Ledger / Transactions for this variant at Website Channel
        $transactions = Database::fetchAll(
            "SELECT st.*, u.full_name as user_name
             FROM stock_transactions st
             LEFT JOIN users u ON st.user_id = u.id
             WHERE st.location_type = 'CHANNEL' AND st.location_id IN ($chanIn) AND st.variant_id = ?
             ORDER BY st.id DESC LIMIT 50",
            array_merge($chanIds, [$variantId])
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
             WHERE ai.variant_id = ? AND wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED') AND ((wa.destination_type = 'CHANNEL' AND wa.destination_id IN ($chanIn)) OR wa.destination_type IN ($destIn) OR wa.destination_type = 'WEBSITE')
             ORDER BY wa.id DESC",
            array_merge([$variantId], $chanIds, $destList)
        );

        // 3. Website Sales Orders History for this variant
        $salesHistory = Database::fetchAll(
            "SELECT si.*, s.invoice_number, s.customer_id, s.status as sale_status, s.payment_status, s.created_at as sale_date,
                    COALESCE(c.name, s.customer_name, 'Website Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone
             FROM sale_items si
             JOIN sales s ON si.sale_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE si.variant_id = ? AND (s.sales_channel = 'WEBSITE' OR s.notes LIKE '%Website%')
             ORDER BY s.created_at DESC",
            [$variantId]
        );

        // 4. Website Damage History
        $damageHistory = Database::fetchAll(
            "SELECT cd.*, u.full_name as reported_by_name,
                    wr.return_number as outward_batch_number, wr.status as outward_batch_status
             FROM channel_damages cd
             LEFT JOIN users u ON cd.reported_by = u.id
             LEFT JOIN warehouse_returns wr ON cd.outward_batch_id = wr.id
             WHERE cd.channel_id IN ($chanIn) AND cd.variant_id = ?
             ORDER BY cd.id DESC",
            array_merge($chanIds, [$variantId])
        );

        // 5. Website Returns History for this variant
        $returnsHistory = Database::fetchAll(
            "SELECT ri.*, r.return_number, r.return_type, r.reason as return_reason, r.refund_amount, r.refund_mode, r.refund_status, r.status as return_status, r.created_at as return_date,
                    s.invoice_number as sale_invoice,
                    COALESCE(c.name, s.customer_name, 'Website Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone
             FROM return_items ri
             JOIN returns r ON ri.return_id = r.id
             LEFT JOIN sales s ON r.sale_id = s.id
             LEFT JOIN customers c ON r.customer_id = c.id
             WHERE ri.variant_id = ? AND (r.source_type = 'WEBSITE' OR s.sales_channel = 'WEBSITE' OR s.notes LIKE '%Website%')
             ORDER BY r.created_at DESC",
            [$variantId]
        );

        // 6. Website Exchanges History for this variant (both OLD returned and NEW dispatched)
        $exchangesHistory = Database::fetchAll(
            "SELECT ei.*, e.exchange_number, e.original_amount, e.new_amount, e.difference_amount, e.settlement_mode, e.payment_action, e.status as exchange_status, e.created_at as exchange_date,
                    s.invoice_number as sale_invoice, r.return_number,
                    COALESCE(c.name, s.customer_name, 'Website Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone
             FROM exchange_items ei
             JOIN exchanges e ON ei.exchange_id = e.id
             LEFT JOIN returns r ON e.return_id = r.id
             LEFT JOIN sales s ON e.sale_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE ei.variant_id = ? AND (e.source_type = 'WEBSITE' OR s.sales_channel = 'WEBSITE' OR s.notes LIKE '%Website%')
             ORDER BY e.created_at DESC",
            [$variantId]
        );

        // 7. Warehouse Returns History for this variant (dispatched back to Central Warehouse)
        $warehouseReturnsHistory = Database::fetchAll(
            "SELECT wr.*, wri.returned_qty, wri.good_qty, wri.damaged_qty, wri.defective_qty, wri.classification,
                    w.name as destination_warehouse_name
             FROM warehouse_return_items wri
             JOIN warehouse_returns wr ON wri.warehouse_return_id = wr.id
             LEFT JOIN warehouses w ON wr.destination_warehouse_id = w.id
             WHERE wri.variant_id = ?
               AND (wr.source_type = 'WEBSITE' OR wr.source_id IN ($chanIn))
               AND wr.status NOT IN ('CANCELLED')
             ORDER BY wr.id DESC",
            array_merge([$variantId], $chanIds)
        );

        // 8. Piece-by-Piece QR & Serial Tag Codes Resolution (without double-counting)
        $c1 = !empty($item['category_name']) ? trim($item['category_name']) : (!empty($item['category_code']) ? trim($item['category_code']) : '');
        $c2 = !empty($item['subcategory_name']) ? trim($item['subcategory_name']) : (!empty($item['subcategory_code']) ? trim($item['subcategory_code']) : '');
        $c3 = !empty($item['sub_subcategory_name']) ? trim($item['sub_subcategory_name']) : (!empty($item['sub_subcategory_code']) ? trim($item['sub_subcategory_code']) : '');

        $prefix = '';
        if ($c1 !== '') $prefix .= strtoupper($c1[0]);
        if ($c2 !== '') $prefix .= strtoupper($c2[0]);
        if ($c3 !== '') $prefix .= strtoupper($c3[0]);

        if (strlen($prefix) < 2 && !empty($item['qr_code_prefix'])) {
            $clean = preg_replace('/[^A-Za-z0-9]/', '', $item['qr_code_prefix']);
            $prefix = strtoupper(substr($clean, 0, 3));
        }
        if (empty($prefix)) {
            $prefix = 'TTS';
        }

        $fmtMrp = (!empty($item['mrp']) && (float)$item['mrp'] > 0) ? (((int)$item['mrp'] == $item['mrp']) ? (int)$item['mrp'] : number_format($item['mrp'], 2, '.', '')) : '999';
        $costVal = (!empty($item['selling_price']) && (float)$item['selling_price'] > 0) ? (float)$item['selling_price'] : ((!empty($item['mrp']) && (float)$item['mrp'] > 0) ? (float)$item['mrp'] : 599);
        $fmtDisc = ($costVal == (int)$costVal) ? (int)$costVal : number_format($costVal, 2, '.', '');

        $pieceTags = [];
        $seenTags = [];

        // Gather tags from Damage Records & index transit damage by allocation_id
        $damagedTagsMap = [];
        $allocTransitDamageMap = [];
        $unassignedFloorDamagedCount = 0;

        foreach ($damageHistory as $dmg) {
            $dTags = ChannelDamage::resolvePieceTags($dmg);
            if (!empty($dTags)) {
                foreach ($dTags as $dt) {
                    $dtClean = trim($dt);
                    if ($dtClean) {
                        $damagedTagsMap[$dtClean] = [
                            'damage_id'     => $dmg['id'],
                            'damage_number' => $dmg['damage_number'],
                            'defect_type'   => $dmg['defect_type'],
                            'damage_reason' => $dmg['damage_reason'] ?? '',
                            'status'        => $dmg['status'],
                            'outward_batch' => $dmg['outward_batch_number'] ?? null,
                            'outward_id'    => $dmg['outward_batch_id'] ?? null,
                            'date'          => $dmg['created_at'],
                        ];
                    }
                }
            } else {
                if (!empty($dmg['allocation_id']) || ($dmg['source_type'] ?? '') === 'INWARD_TRANSIT') {
                    $allocIdKey = (int)($dmg['allocation_id'] ?: ($dmg['reference_id'] ?? 0));
                    if ($allocIdKey > 0) {
                        $allocTransitDamageMap[$allocIdKey] = $dmg;
                    } else {
                        $unassignedFloorDamagedCount += (int)$dmg['quantity'];
                    }
                } else {
                    $unassignedFloorDamagedCount += (int)$dmg['quantity'];
                }
            }
        }

        // Gather tags from Website Sales Orders
        $soldTagsMap = [];
        $unassignedSoldCount = 0;
        foreach ($salesHistory as $sale) {
            $sQrs = [];
            if (!empty($sale['scanned_qr_codes'])) {
                $parsed = json_decode($sale['scanned_qr_codes'], true);
                if (is_array($parsed)) {
                    $sQrs = $parsed;
                } else {
                    $sQrs = array_map('trim', explode(',', $sale['scanned_qr_codes']));
                }
            }
            if (!empty($sQrs)) {
                foreach ($sQrs as $sq) {
                    $sqClean = trim($sq);
                    if ($sqClean) {
                        $soldTagsMap[$sqClean] = [
                            'sale_id'        => $sale['sale_id'] ?? $sale['id'],
                            'invoice_number' => $sale['invoice_number'],
                            'customer_name'  => $sale['customer_name'] ?? 'Website Customer',
                            'date'           => $sale['sale_date'] ?? $sale['created_at'],
                        ];
                    }
                }
            } else {
                $unassignedSoldCount += (int)$sale['quantity'];
            }
        }

        // Helper to extract tag codes from comma/pipe/whitespace strings
        $extractTagsHelper = function($raw) {
            if (empty($raw)) return [];
            if (is_array($raw)) {
                $flat = [];
                foreach ($raw as $r) {
                    $flat = array_merge($flat, preg_split('/[\r\n,;|]+/', (string)$r));
                }
                return array_values(array_filter(array_map('trim', $flat)));
            }
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('trim', $decoded)));
            }
            return array_values(array_filter(array_map('trim', preg_split('/[\r\n,;|]+/', (string)$raw))));
        };

        // Gather tags from Returns & Restocked items
        $returnedTagsMap = [];
        foreach ($returnsHistory as $ret) {
            $cond = strtoupper(trim($ret['item_condition'] ?? $ret['condition_type'] ?? 'GOOD'));
            $rInfo = [
                'return_id'      => $ret['return_id'] ?? $ret['id'],
                'return_number'  => $ret['return_number'],
                'condition'      => $cond,
                'reason'         => $ret['return_reason'] ?? '',
                'sale_invoice'   => $ret['sale_invoice'] ?? '-',
                'customer_name'  => $ret['customer_name'] ?? 'Website Customer',
                'date'           => $ret['return_date'] ?? $ret['created_at'],
            ];

            // Check if return record references specific tags
            if (!empty($ret['piece_tag'])) {
                $pTags = $extractTagsHelper($ret['piece_tag']);
                foreach ($pTags as $pt) {
                    if ($pt) $returnedTagsMap[$pt] = $rInfo;
                }
            } elseif (!empty($ret['reason']) && preg_match('/Piece Tags?:\s*([^\n\r]+)/i', $ret['reason'], $m)) {
                $pTags = $extractTagsHelper($m[1]);
                foreach ($pTags as $pt) {
                    if ($pt) $returnedTagsMap[$pt] = $rInfo;
                }
            }
        }

        // Gather tags from Exchanges (both OLD restocked and NEW dispatched)
        $exchangeDispatchedTagsMap = [];
        foreach ($exchangesHistory as $ex) {
            $pTagStr = trim($ex['piece_tag'] ?? '');
            if ($pTagStr !== '') {
                $pTags = $extractTagsHelper($pTagStr);
                foreach ($pTags as $pt) {
                    if (!$pt) continue;
                    if (strtoupper($ex['item_type'] ?? '') === 'NEW') {
                        $exchangeDispatchedTagsMap[$pt] = [
                            'exchange_id'     => $ex['exchange_id'] ?? $ex['id'],
                            'exchange_number' => $ex['exchange_number'],
                            'sale_invoice'    => $ex['sale_invoice'] ?? '-',
                            'return_number'   => $ex['return_number'] ?? '',
                            'customer_name'   => $ex['customer_name'] ?? 'Website Customer',
                            'date'            => $ex['exchange_date'] ?? $ex['created_at'],
                        ];
                    } elseif (strtoupper($ex['item_type'] ?? '') === 'OLD') {
                        $returnedTagsMap[$pt] = [
                            'exchange_id'     => $ex['exchange_id'] ?? $ex['id'],
                            'exchange_number' => $ex['exchange_number'],
                            'return_number'   => $ex['return_number'] ?? $ex['exchange_number'],
                            'condition'       => 'GOOD',
                            'reason'          => 'Product Exchange (Old Piece Returned)',
                            'sale_invoice'    => $ex['sale_invoice'] ?? '-',
                            'customer_name'   => $ex['customer_name'] ?? 'Website Customer',
                            'date'            => $ex['exchange_date'] ?? $ex['created_at'],
                        ];
                    }
                }
            }
        }

        // Gather tags from Warehouse Returns (dispatched back to Central Warehouse)
        $warehouseReturnedTagsMap = [];
        $unassignedWarehouseReturnedGoodCount = 0;
        $unassignedWarehouseReturnedDamagedCount = 0;
        $latestWarehouseReturn = $warehouseReturnsHistory[0] ?? null;

        foreach ($warehouseReturnsHistory as $wrItem) {
            $wrInfo = [
                'warehouse_return_id'   => $wrItem['id'],
                'return_number'         => $wrItem['return_number'],
                'warehouse_name'        => $wrItem['destination_warehouse_name'] ?? 'Central Warehouse',
                'classification'        => $wrItem['classification'] ?? 'GOOD',
                'reason'                => $wrItem['reason'] ?? 'Returned to Central Warehouse',
                'notes'                 => $wrItem['notes'] ?? '',
                'date'                  => $wrItem['created_at'],
                'status'                => $wrItem['status'],
                'is_warehouse_returned' => true,
            ];

            $foundSpecificTags = false;
            if (!empty($wrItem['notes'])) {
                $notesStr = $wrItem['notes'];
                if (str_starts_with($notesStr, 'QR: ')) {
                    $notesStr = substr($notesStr, 4);
                }
                $tags = array_map('trim', explode(',', $notesStr));
                foreach ($tags as $t) {
                    if ($t && preg_match('/^[A-Za-z0-9\-_]+$/', $t) && (str_starts_with($t, 'KB') || str_starts_with($t, 'TTS') || str_contains($t, '-'))) {
                        $warehouseReturnedTagsMap[$t] = $wrInfo;
                        $foundSpecificTags = true;
                    }
                }
            }

            if (!$foundSpecificTags) {
                if (($wrItem['classification'] ?? 'GOOD') === 'DAMAGED') {
                    $unassignedWarehouseReturnedDamagedCount += (int)($wrItem['returned_qty'] ?? 1);
                } else {
                    $unassignedWarehouseReturnedGoodCount += (int)($wrItem['returned_qty'] ?? 1);
                }
            }
        }

        $globalPieceIdx = 1;
        foreach ($inwardHistory as $inw) {
            $allocId = (int)($inw['allocation_id'] ?? $inw['id']);
            $dispatchedQty = (int)($inw['dispatched_qty'] > 0 ? $inw['dispatched_qty'] : ($inw['requested_qty'] ?? 1));
            $rec = (int)($inw['received_qty'] ?? (in_array(strtoupper($inw['alloc_status'] ?? ''), ['COMPLETED', 'RECEIVED']) ? $dispatchedQty : 0));
            $dmg = (int)($inw['damaged_qty'] ?? 0);
            // Website inventory includes physically received pieces only.
            $exp = min($dispatchedQty, max(0, $rec + $dmg));
            $allocMonthStr = sprintf('%02d', date('n', strtotime($inw['alloc_date'] ?? 'now')));
            $allocYearStr  = date('y', strtotime($inw['alloc_date'] ?? 'now'));

            $transitDmgRecord = $allocTransitDamageMap[$allocId] ?? null;

            $savedQrs = [];
            if (!empty($inw['received_qr_codes'])) {
                $decodedReceivedQrs = json_decode((string)$inw['received_qr_codes'], true);
                $savedQrs = is_array($decodedReceivedQrs)
                    ? array_values(array_filter(array_map('trim', $decodedReceivedQrs)))
                    : array_values(array_filter(array_map('trim', explode(',', (string)$inw['received_qr_codes']))));
            }
            if (empty($savedQrs) && !empty($inw['selected_qr_codes'])) {
                $selectedQrs = array_values(array_filter(array_map('trim', explode(',', (string)$inw['selected_qr_codes']))));
                $savedQrs = array_slice($selectedQrs, 0, max(0, (int)($inw['received_qty'] ?? 0) + (int)($inw['damaged_qty'] ?? 0)));
            }

            if (!empty($savedQrs)) {
                $pSeq = 1;
                foreach ($savedQrs as $code) {
                    $tagCode = trim($code);
                    if (isset($seenTags[$tagCode])) continue;
                    $seenTags[$tagCode] = true;

                    $qrPayload = $tagCode;
                    
                    $status = 'AVAILABLE';
                    $statusMeta = [];

                    if (isset($warehouseReturnedTagsMap[$tagCode])) {
                        $status = 'RETURNED';
                        $statusMeta = $warehouseReturnedTagsMap[$tagCode];
                    } elseif (isset($damagedTagsMap[$tagCode])) {
                        $status = 'DAMAGED';
                        $statusMeta = $damagedTagsMap[$tagCode];
                    } elseif (isset($returnedTagsMap[$tagCode])) {
                        $rInfo = $returnedTagsMap[$tagCode];
                        if (($rInfo['condition'] ?? 'GOOD') === 'GOOD') {
                            $status = 'AVAILABLE';
                            $statusMeta = array_merge($rInfo, [
                                'is_restocked'  => true,
                                'restock_badge' => 'Restocked from Return/Exchange'
                            ]);
                        } else {
                            $status = 'DAMAGED';
                            $statusMeta = array_merge($rInfo, [
                                'is_returned_damaged' => true,
                                'damage_reason'       => 'Returned Damaged Stock'
                            ]);
                        }
                    } elseif (!$isPackType && isset($exchangeDispatchedTagsMap[$tagCode])) {
                        $status = 'SOLD';
                        $statusMeta = array_merge($exchangeDispatchedTagsMap[$tagCode], [
                            'is_exchange_dispatched' => true,
                            'dispatch_badge'         => 'Dispatched in Exchange',
                            'invoice_number'         => $exchangeDispatchedTagsMap[$tagCode]['exchange_number'] ?? 'Exchange Order'
                        ]);
                    } elseif (!$isPackType && isset($soldTagsMap[$tagCode])) {
                        $status = 'SOLD';
                        $statusMeta = $soldTagsMap[$tagCode];
                    } elseif ($pSeq > $rec && $pSeq <= ($rec + $dmg)) {
                        $status = 'DAMAGED';
                        if ($transitDmgRecord) {
                            $statusMeta = [
                                'damage_id'     => $transitDmgRecord['id'],
                                'damage_number' => $transitDmgRecord['damage_number'],
                                'defect_type'   => $transitDmgRecord['defect_type'],
                                'damage_reason' => $transitDmgRecord['damage_reason'] ?? 'Transit Inward Damage',
                                'status'        => $transitDmgRecord['status'],
                                'outward_batch' => $transitDmgRecord['outward_batch_number'] ?? null,
                                'outward_id'    => $transitDmgRecord['outward_batch_id'] ?? null,
                                'date'          => $transitDmgRecord['created_at'],
                            ];
                        } else {
                            $statusMeta = ['damage_reason' => 'Transit Inward Damage', 'defect_type' => 'TRANSIT_DAMAGE'];
                        }
                    } elseif (!in_array(strtoupper($inw['alloc_status'] ?? ''), ['COMPLETED', 'RECEIVED', 'DELIVERED']) && $pSeq > $rec) {
                        $status = 'IN_TRANSIT';
                    }

                    $pieceTags[] = [
                        'piece_no'          => $globalPieceIdx++,
                        'batch_piece_no'    => $pSeq++,
                        'tag_code'          => $tagCode,
                        'qr_payload'        => $qrPayload,
                        'sku'               => $item['sku'],
                        'mrp'               => $fmtMrp,
                        'disc'              => $fmtDisc,
                        'selling_price'     => $costVal,
                        'allocation_id'     => $allocId,
                        'allocation_number' => $inw['allocation_number'] ?? ('ALC-' . $allocId),
                        'warehouse_name'    => $inw['warehouse_name'] ?? 'Central Warehouse',
                        'inward_date'       => $inw['alloc_date'],
                        'status'            => $status,
                        'meta'              => $statusMeta,
                    ];
                }
            } else {
                // Calculate variant offset based on sibling variants of this product to prevent tag collisions
                $allProductVariants = Database::fetchAll(
                    "SELECT id FROM product_variants WHERE product_id = ? ORDER BY id ASC",
                    [$item['product_id']]
                );
                $varOffset = 0;
                foreach ($allProductVariants as $pvRow) {
                    if ((int)$pvRow['id'] === (int)$item['variant_id']) {
                        break;
                    }
                    $varOffset += 20;
                }

                for ($p = 1; $p <= $exp; $p++) {
                    $tagSeq = $varOffset + $p;
                    $tagCode = $prefix . sprintf("%02d", $allocMonthStr) . $allocYearStr . sprintf("%02d", $tagSeq);
                    if (isset($seenTags[$tagCode])) {
                        $tagCode = $prefix . sprintf("%02d", $allocMonthStr) . $allocYearStr . sprintf("%02d", $tagSeq) . '-' . $allocId;
                    }
                    $seenTags[$tagCode] = true;

                    $qrPayload = $tagCode;
                    
                    $status = 'AVAILABLE';
                    $statusMeta = [];

                    if (isset($warehouseReturnedTagsMap[$tagCode])) {
                        $status = 'RETURNED';
                        $statusMeta = $warehouseReturnedTagsMap[$tagCode];
                    } elseif (isset($damagedTagsMap[$tagCode])) {
                        $status = 'DAMAGED';
                        $statusMeta = $damagedTagsMap[$tagCode];
                    } elseif (isset($returnedTagsMap[$tagCode])) {
                        $rInfo = $returnedTagsMap[$tagCode];
                        if (($rInfo['condition'] ?? 'GOOD') === 'GOOD') {
                            $status = 'AVAILABLE';
                            $statusMeta = array_merge($rInfo, [
                                'is_restocked'  => true,
                                'restock_badge' => 'Restocked from Return/Exchange'
                            ]);
                        } else {
                            $status = 'DAMAGED';
                            $statusMeta = array_merge($rInfo, [
                                'is_returned_damaged' => true,
                                'damage_reason'       => 'Returned Damaged Stock'
                            ]);
                        }
                    } elseif (!$isPackType && isset($exchangeDispatchedTagsMap[$tagCode])) {
                        $status = 'SOLD';
                        $statusMeta = array_merge($exchangeDispatchedTagsMap[$tagCode], [
                            'is_exchange_dispatched' => true,
                            'dispatch_badge'         => 'Dispatched in Exchange',
                            'invoice_number'         => $exchangeDispatchedTagsMap[$tagCode]['exchange_number'] ?? 'Exchange Order'
                        ]);
                    } elseif (!$isPackType && isset($soldTagsMap[$tagCode])) {
                        $status = 'SOLD';
                        $statusMeta = $soldTagsMap[$tagCode];
                    } elseif ($p > $rec && $p <= ($rec + $dmg)) {
                        $status = 'DAMAGED';
                        if ($transitDmgRecord) {
                            $statusMeta = [
                                'damage_id'     => $transitDmgRecord['id'],
                                'damage_number' => $transitDmgRecord['damage_number'],
                                'defect_type'   => $transitDmgRecord['defect_type'],
                                'damage_reason' => $transitDmgRecord['damage_reason'] ?? 'Transit Inward Damage',
                                'status'        => $transitDmgRecord['status'],
                                'outward_batch' => $transitDmgRecord['outward_batch_number'] ?? null,
                                'outward_id'    => $transitDmgRecord['outward_batch_id'] ?? null,
                                'date'          => $transitDmgRecord['created_at'],
                            ];
                        } else {
                            $statusMeta = ['damage_reason' => 'Transit Inward Damage', 'defect_type' => 'TRANSIT_DAMAGE'];
                        }
                    } elseif (!in_array(strtoupper($inw['alloc_status'] ?? ''), ['COMPLETED', 'RECEIVED', 'DELIVERED']) && $p > $rec) {
                        $status = 'IN_TRANSIT';
                    }

                    $pieceTags[] = [
                        'piece_no'          => $globalPieceIdx++,
                        'batch_piece_no'    => $p,
                        'tag_code'          => $tagCode,
                        'qr_payload'        => $qrPayload,
                        'sku'               => $item['sku'],
                        'mrp'               => $fmtMrp,
                        'disc'              => $fmtDisc,
                        'selling_price'     => $costVal,
                        'allocation_id'     => $allocId,
                        'allocation_number' => $inw['allocation_number'] ?? ('ALC-' . $allocId),
                        'warehouse_name'    => $inw['warehouse_name'] ?? 'Central Warehouse',
                        'inward_date'       => $inw['alloc_date'],
                        'status'            => $status,
                        'meta'              => $statusMeta,
                    ];
                }
            }
        }

        // Direct / Opening Stock tag generation if no allocation records
        $currentAvail = (int)$item['available_quantity'];
        $currentDmg   = (int)$item['damaged_quantity'];
        if (empty($pieceTags) && ($currentAvail > 0 || $currentDmg > 0)) {
            $nowMonth = sprintf('%02d', date('n'));
            $nowYear  = date('y');
            
            // Calculate variant offset based on sibling variants of this product
            $allProductVariants = Database::fetchAll(
                "SELECT id FROM product_variants WHERE product_id = ? ORDER BY id ASC",
                [$item['product_id']]
            );
            $varOffset = 0;
            foreach ($allProductVariants as $pvRow) {
                if ((int)$pvRow['id'] === (int)$item['variant_id']) {
                    break;
                }
                $varOffset += 20;
            }

            for ($i = 1; $i <= ($currentAvail + $currentDmg); $i++) {
                $tagSeq = $varOffset + $i;
                $tagCode = $prefix . $nowMonth . $nowYear . sprintf("%02d", $tagSeq);
                $qrPayload = $tagCode;
                $isDmg = ($i > $currentAvail);
                
                $status = $isDmg ? 'DAMAGED' : 'AVAILABLE';
                $statusMeta = $isDmg ? ['damage_reason' => 'Stock Defect Quarantine'] : [];

                if (isset($warehouseReturnedTagsMap[$tagCode])) {
                    $status = 'RETURNED';
                    $statusMeta = $warehouseReturnedTagsMap[$tagCode];
                } elseif (isset($damagedTagsMap[$tagCode])) {
                    $status = 'DAMAGED';
                    $statusMeta = $damagedTagsMap[$tagCode];
                } elseif (isset($returnedTagsMap[$tagCode])) {
                    $rInfo = $returnedTagsMap[$tagCode];
                    if (($rInfo['condition'] ?? 'GOOD') === 'GOOD') {
                        $status = 'AVAILABLE';
                        $statusMeta = array_merge($rInfo, [
                            'is_restocked'  => true,
                            'restock_badge' => 'Restocked from Return/Exchange'
                        ]);
                    } else {
                        $status = 'DAMAGED';
                        $statusMeta = array_merge($rInfo, [
                            'is_returned_damaged' => true,
                            'damage_reason'       => 'Returned Damaged Stock'
                        ]);
                    }
                    } elseif (!$isPackType && isset($exchangeDispatchedTagsMap[$tagCode])) {
                    $status = 'SOLD';
                    $statusMeta = array_merge($exchangeDispatchedTagsMap[$tagCode], [
                        'is_exchange_dispatched' => true,
                        'dispatch_badge'         => 'Dispatched in Exchange',
                        'invoice_number'         => $exchangeDispatchedTagsMap[$tagCode]['exchange_number'] ?? 'Exchange Order'
                    ]);
                    } elseif (!$isPackType && isset($soldTagsMap[$tagCode])) {
                    $status = 'SOLD';
                    $statusMeta = $soldTagsMap[$tagCode];
                }

                $pieceTags[] = [
                    'piece_no'          => $globalPieceIdx++,
                    'batch_piece_no'    => $i,
                    'tag_code'          => $tagCode,
                    'qr_payload'        => $qrPayload,
                    'sku'               => $item['sku'],
                    'mrp'               => $fmtMrp,
                    'disc'              => $fmtDisc,
                    'selling_price'     => $costVal,
                    'allocation_id'     => null,
                    'allocation_number' => 'Direct Opening / Channel Inward',
                    'warehouse_name'    => 'Website Channel Inventory',
                    'inward_date'       => date('Y-m-d H:i:s'),
                    'status'            => $status,
                    'meta'              => $statusMeta,
                ];
            }
        }

        // Apply unassigned warehouse returns
        if ($unassignedWarehouseReturnedGoodCount > 0) {
            foreach ($pieceTags as &$pt) {
                if ($unassignedWarehouseReturnedGoodCount <= 0) break;
                if ($pt['status'] === 'AVAILABLE' && empty($pt['meta']['is_restocked'])) {
                    $pt['status'] = 'RETURNED';
                    $pt['meta'] = [
                        'is_warehouse_returned' => true,
                        'return_number'         => $latestWarehouseReturn['return_number'] ?? 'Warehouse Return',
                        'warehouse_name'        => $latestWarehouseReturn['destination_warehouse_name'] ?? 'Central Warehouse',
                        'reason'                => $latestWarehouseReturn['reason'] ?? 'Returned to Central Warehouse',
                        'date'                  => $latestWarehouseReturn['created_at'] ?? date('Y-m-d H:i:s'),
                    ];
                    $unassignedWarehouseReturnedGoodCount--;
                }
            }
            unset($pt);
        }

        if ($unassignedWarehouseReturnedDamagedCount > 0) {
            foreach ($pieceTags as &$pt) {
                if ($unassignedWarehouseReturnedDamagedCount <= 0) break;
                if ($pt['status'] === 'DAMAGED' && empty($pt['meta']['is_returned_damaged'])) {
                    $pt['status'] = 'RETURNED';
                    $pt['meta'] = [
                        'is_warehouse_returned' => true,
                        'return_number'         => $latestWarehouseReturn['return_number'] ?? 'Warehouse Return',
                        'warehouse_name'        => $latestWarehouseReturn['destination_warehouse_name'] ?? 'Central Warehouse',
                        'reason'                => $latestWarehouseReturn['reason'] ?? 'Damaged Stock Returned to Central Warehouse',
                        'date'                  => $latestWarehouseReturn['created_at'] ?? date('Y-m-d H:i:s'),
                    ];
                    $unassignedWarehouseReturnedDamagedCount--;
                }
            }
            unset($pt);
        }

        // Apply ONLY non-inward floor damages without specific tags to remaining available pieces
        if ($unassignedFloorDamagedCount > 0) {
            foreach ($pieceTags as &$pt) {
                if ($unassignedFloorDamagedCount <= 0) break;
                if ($pt['status'] === 'AVAILABLE' && empty($pt['meta']['is_restocked'])) {
                    $pt['status'] = 'DAMAGED';
                    $pt['meta'] = ['damage_reason' => 'Quarantined Channel Defect', 'defect_type' => 'STORAGE_HANDLING'];
                    $unassignedFloorDamagedCount--;
                }
            }
            unset($pt);
        }

        if ($unassignedSoldCount > 0 && !$isPackType) {
            foreach ($pieceTags as &$pt) {
                if ($unassignedSoldCount <= 0) break;
                if ($pt['status'] === 'AVAILABLE' && empty($pt['meta']['is_restocked'])) {
                    $pt['status'] = 'SOLD';
                    $pt['meta'] = ['invoice_number' => 'Website Order'];
                    $unassignedSoldCount--;
                }
            }
            unset($pt);
        }

        $totalInwardQty    = array_sum(array_column($inwardHistory, 'received_qty'));
        $totalSoldQty      = array_sum(array_column($salesHistory, 'quantity'));
        $totalSoldAmt      = array_sum(array_column($salesHistory, 'total_price'));
        $totalReturnedQty  = array_sum(array_column($returnsHistory, 'quantity'));
        $totalExchangedQty = count(array_filter($exchangesHistory, fn($e) => strtoupper($e['item_type'] ?? '') === 'NEW'));
        $totalWhReturnedQty= array_sum(array_column($warehouseReturnsHistory, 'returned_qty'));
        $totalDmgQty       = array_sum(array_column($damageHistory, 'quantity'));
        $totalDmgLoss      = array_sum(array_column($damageHistory, 'total_loss'));

        Response::view('website_orders.inventory_detail', [
            'pageTitle'               => 'Product Stock Details: ' . ($item['sku'] ?? 'Stock Item'),
            'activeModule'            => 'website',
            'activeMenu'              => 'wh-website-allocations',
            'breadcrumb'              => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Stock Inventory', 'url' => url('warehouse/website-orders/allocations')],
                ['label' => $item['sku'] ?? 'Product History']
            ],
            'channel'                 => $chan,
            'item'                    => $item,
            'pieceTags'               => $pieceTags,
            'transactions'            => $transactions,
            'inwardHistory'           => $inwardHistory,
            'salesHistory'            => $salesHistory,
            'damageHistory'           => $damageHistory,
            'returnsHistory'          => $returnsHistory,
            'exchangesHistory'        => $exchangesHistory,
            'warehouseReturnsHistory' => $warehouseReturnsHistory,
            'metrics'                 => [
                'total_inward_qty'     => $totalInwardQty,
                'total_sold_qty'       => $totalSoldQty,
                'total_sold_amt'       => $totalSoldAmt,
                'total_returned_qty'   => $totalReturnedQty,
                'total_exchanged_qty'  => $totalExchangedQty,
                'total_wh_returned_qty'=> $totalWhReturnedQty,
                'total_dmg_qty'        => $totalDmgQty,
                'total_dmg_loss'       => $totalDmgLoss,
            ]
        ]);
    }

    /**
     * Create Stock Request Form for Website Channel
     */
    public function createWebsiteStockRequest(): void {
        if (!hasPermission('website_orders', 'create')) {
            Session::flash('error', 'Access denied. You do not have permission to create Website stock requests.');
            Response::redirect(url('warehouse/website-orders/allocations'));
            return;
        }
        $chan = $this->getChannel();
        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : [];
        $warehouseIds = array_map('intval', array_column($warehouses, 'id'));
        $warehouseList = $warehouseIds ? implode(',', $warehouseIds) : '0';
        $variants = Database::fetchAll(
            "SELECT pv.id, pv.sku, pv.mrp, pv.selling_price, p.name as product_name,
                    COALESCE(wc.name, c.name, 'General') as category_name,
                    COALESCE((
                        SELECT SUM(quantity) FROM stock_balances 
                        WHERE variant_id = pv.id AND stock_state = 'AVAILABLE' AND location_type = 'WAREHOUSE'
                          AND location_id IN ({$warehouseList}) AND concern_id = " . (int)currentConcernId() . "
                    ), 0) as stock_qty
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE pv.is_active = 1
             ORDER BY p.name ASC, pv.sku ASC"
        );

        Response::view('website_orders.create_request', [
            'pageTitle'    => 'Create Website Stock Request',
            'activeModule' => 'website',
            'activeMenu'   => 'wh-website-allocations',
            'breadcrumb'   => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Inventory', 'url' => url('warehouse/website-orders/allocations')],
                ['label' => 'New Stock Request']
            ],
            'channel'      => $chan,
            'warehouses'   => $warehouses,
            'variants'     => $variants,
        ]);
    }

    public function storeWebsiteStockRequest(): void {
        if (!hasPermission('website_orders', 'create')) {
            Session::flash('error', 'Access denied. You do not have permission to submit Website stock requests.');
            Response::redirect(url('warehouse/website-orders/allocations'));
            return;
        }
        CSRF::verify();

        $chan = $this->getChannel();
        $channelId = (int)$chan['id'];
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $items = $_POST['items'] ?? [];

        if (!$warehouseId) {
            Session::flash('error', 'Source warehouse is required for fulfilling the stock request.');
            Response::redirect(url('warehouse/website-orders/requests/create'));
            return;
        }

        if (empty($items) || !is_array($items)) {
            Session::flash('error', 'Please select at least one product item for the stock request.');
            Response::redirect(url('warehouse/website-orders/requests/create'));
            return;
        }

        $allocationNumber = WarehouseAllocation::generateRequestNumber('WEBREQ-');
        $allocationId = WarehouseAllocation::create([
            'allocation_number' => $allocationNumber,
            'warehouse_id'      => $warehouseId,
            'destination_type'  => 'CHANNEL',
            'destination_id'    => $channelId,
            'channel_id'        => $channelId,
            'requested_by'      => Auth::id() ?? 1,
            'status'            => 'REQUESTED',
            'notes'             => $notes ?: 'Website Channel Stock Requisition',
        ]);

        foreach ($items as $itm) {
            $variantId = (int)($itm['variant_id'] ?? 0);
            $qty = (int)($itm['requested_qty'] ?? 0);
            if ($variantId > 0 && $qty > 0) {
                AllocationItem::create([
                    'allocation_id' => $allocationId,
                    'variant_id'    => $variantId,
                    'requested_qty' => $qty,
                    'dispatched_qty' => 0,
                    'status'        => 'PENDING',
                ]);
            }
        }

        Session::flash('success', "Website Stock Request #{$allocationNumber} submitted successfully!");
        Response::redirect(url('warehouse/website-orders/allocations'));
    }

    /**
     * Orders History for Website Channel
     */
    public function websiteOrdersHistory(): void {
        if (!hasPermission('website_orders', 'view')) {
            Session::flash('error', 'Access denied. You do not have permission to view Website orders history.');
            Response::redirect(url('warehouse/website-orders'));
            return;
        }
        $chan = $this->getChannel();
        $search = trim($_GET['search'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $where = ["(s.sales_channel = 'WEBSITE' OR s.sale_type = 'WEBSITE' OR s.notes LIKE '%Website%')"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(s.invoice_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR s.notes LIKE ? OR s.biller_name LIKE ? OR c.address LIKE ? OR s.customer_address LIKE ?)";
            $sWild = "%{$search}%";
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
        }

        if (!empty($dateFrom)) {
            $where[] = "DATE(s.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $where[] = "DATE(s.created_at) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = implode(' AND ', $where);

        $orders = Database::fetchAll(
            "SELECT s.*, 
                    COALESCE(c.name, s.customer_name, 'Website Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone,
                    COALESCE(c.address, s.customer_address, '-') as customer_address,
                    c.city as customer_city,
                    c.state as customer_state,
                    c.pincode as customer_pincode,
                    COALESCE(s.biller_name, u.full_name, 'System Administrator') as biller_name,
                    (SELECT COUNT(DISTINCT variant_id) FROM sale_items WHERE sale_id = s.id) as distinct_variants_count,
                    (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count,
                    (SELECT COALESCE(SUM(quantity), 0) FROM sale_items WHERE sale_id = s.id) as total_pcs,
                    (SELECT COALESCE(SUM(quantity), 0) FROM sale_items WHERE sale_id = s.id) as total_items_count
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             LEFT JOIN users u ON s.biller_id = u.id OR s.billing_staff_id = u.id
             WHERE {$whereClause}
             ORDER BY s.created_at DESC",
            $params
        );

        $totalOrdersCount = count($orders);
        $totalRevenue = 0.0;
        $totalPcsSold = 0;
        $completedCount = 0;
        $pendingCount = 0;

        foreach ($orders as $o) {
            $totalRevenue += (float)($o['total_amount'] ?? $o['final_amount'] ?? 0);
            $totalPcsSold += (int)($o['total_pcs'] ?? $o['total_items_count'] ?? 0);
            $st = strtoupper($o['status'] ?? '');
            if (in_array($st, ['COMPLETED', 'PAID', 'DELIVERED'])) {
                $completedCount++;
            } else if (in_array($st, ['PENDING', 'PROCESSING', 'CONFIRMED'])) {
                $pendingCount++;
            }
        }

        Response::view('website_orders.history', [
            'pageTitle'          => 'Website Orders History',
            'activeModule'       => 'website',
            'activeMenu'         => 'wh-website-history',
            'breadcrumb'         => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Orders History']
            ],
            'channel'            => $chan,
            'orders'             => $orders,
            'totalOrdersCount'   => $totalOrdersCount,
            'totalRevenue'       => $totalRevenue,
            'totalRevenueAmount' => $totalRevenue,
            'totalPcsSold'       => $totalPcsSold,
            'totalUnitsSold'     => $totalPcsSold,
            'completedCount'     => $completedCount,
            'pendingCount'       => $pendingCount,
            'search'             => $search,
            'dateFrom'           => $dateFrom,
            'dateTo'             => $dateTo,
        ]);
    }

    // =========================================================================
    // WEBSITE DAMAGE MANAGEMENT & OUTWARD RETURN TO WAREHOUSE
    // =========================================================================

    /**
     * Website Channel Damaged Stock Register
     */
    public function websiteDamageIndex(): void {
        if (!hasPermission('website_damage', 'view')) {
            Session::flash('error', 'Access denied. You do not have permission to view Website damage records.');
            Response::redirect(url('warehouse/website-orders'));
            return;
        }
        $chan = $this->getChannel();
        $channelIds = $this->getWebsiteChannelIds();

        $filters = [
            'channel_ids' => $channelIds,
            'status'      => $_GET['status'] ?? 'ALL',
            'defect_type' => $_GET['defect_type'] ?? 'ALL',
            'source_type' => $_GET['source_type'] ?? 'ALL',
            'date_from'   => $_GET['date_from'] ?? '',
            'date_to'     => $_GET['date_to'] ?? '',
            'search'      => $_GET['search'] ?? '',
        ];

        $damages = ChannelDamage::getAll($channelIds, $filters);
        $stats   = ChannelDamage::getSummaryStats($channelIds);

        Response::view('website_orders.damage.index', [
            'damages'      => $damages,
            'stats'        => $stats,
            'filters'      => $filters,
            'channel'      => $chan,
            'activeModule' => 'website',
            'activeMenu'   => 'wh-website-damage',
            'pageTitle'    => 'Website Channel Damaged Goods & Quarantine'
        ]);
    }

    /**
     * Report New Damaged / Defective Stock in Website Channel
     */
    public function createWebsiteDamage(): void {
        if (!hasPermission('website_damage', 'create')) {
            Session::flash('error', 'Access denied. You do not have permission to report Website damaged stock.');
            Response::redirect(url('warehouse/website-orders/damage'));
            return;
        }
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 2);

        $prefillVariantId = (int)($_GET['variant_id'] ?? 0);
        $prefillTagCode   = trim($_GET['tag_code'] ?? '');
        $prefillAllocId   = (int)($_GET['allocation_id'] ?? 0);

        $prefillVariant = null;
        if ($prefillVariantId > 0) {
            $prefillVariant = Database::fetchOne(
                "SELECT pv.*, p.name as product_name, p.code as product_code,
                        c.name as color, s.name as size
                 FROM product_variants pv
                 JOIN products p ON pv.product_id = p.id
                 LEFT JOIN colors c ON pv.color_id = c.id
                 LEFT JOIN sizes s ON pv.size_id = s.id
                 WHERE pv.id = ?",
                [$prefillVariantId]
            );
            if ($prefillVariant) {
                $prefillVariant['available_stock'] = StockService::getAvailable($prefillVariantId, 'CHANNEL', $channelId);
            }
        }

        Response::view('website_orders.damage.create', [
            'channel'          => $chan,
            'prefillVariant'   => $prefillVariant,
            'prefillTagCode'   => $prefillTagCode,
            'prefillAllocId'   => $prefillAllocId,
            'activeModule'     => 'website',
            'activeMenu'       => 'wh-website-damage',
            'pageTitle'        => 'Report Website Defective / Damaged Stock'
        ]);
    }

    /**
     * Store New Damage Record
     */
    public function storeWebsiteDamage(): void {
        if (!hasPermission('website_damage', 'create')) {
            Session::flash('error', 'Access denied. You do not have permission to log Website damage.');
            Response::redirect(url('warehouse/website-orders/damage'));
            return;
        }
        CSRF::verify();
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 2);
        $userId = Session::get('user_id') ?? 1;

        $variantId     = (int)($_POST['variant_id'] ?? 0);
        $quantity      = max(1, (int)($_POST['quantity'] ?? 1));
        $defectType    = trim($_POST['defect_type'] ?? 'OTHER');
        $costPerUnit  = (float)($_POST['cost_per_unit'] ?? 0);
        $damageReason = trim($_POST['damage_reason'] ?? "Manual Website defect quarantine ({$defectType})");
        $defectNotes  = trim($_POST['defect_notes'] ?? '');
        $photoUrl     = trim($_POST['photo_url'] ?? '');
        $sourceType   = trim($_POST['source_type'] ?? 'STORAGE_HANDLING');
        $tagCode      = trim($_POST['tag_code'] ?? '');
        $allocationId = !empty($_POST['allocation_id']) ? (int)$_POST['allocation_id'] : null;

        // Handle uploaded photo evidence file
        if (!empty($_FILES['photo']['name']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            require_once BASE_PATH . '/app/Services/CloudinaryService.php';
            $uploaded = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/channel/damages', 'image');
            if ($uploaded) {
                $photoUrl = $uploaded;
            }
        }

        $items = $_POST['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            $singleVariantId = (int)($_POST['variant_id'] ?? 0);
            if ($singleVariantId > 0) {
                $items = [[
                    'variant_id'    => $singleVariantId,
                    'quantity'      => max(1, (int)($_POST['quantity'] ?? 1)),
                    'cost_per_unit' => (float)($_POST['cost_per_unit'] ?? 0),
                    'tag_code'      => trim($_POST['tag_code'] ?? ''),
                    'allocation_id' => !empty($_POST['allocation_id']) ? (int)$_POST['allocation_id'] : null,
                ]];
            }
        }

        if (empty($items)) {
            $_SESSION['flash_error'] = "Please scan or select at least one garment product to log damage.";
            Response::redirect(url('warehouse/website-orders/damage/create'));
            return;
        }

        // Validate all items first
        $aggregateRequestedQty = [];
        foreach ($items as $idx => $itm) {
            $vId = (int)($itm['variant_id'] ?? 0);
            $qty = max(1, (int)($itm['quantity'] ?? 1));
            $tCode = trim($itm['tag_code'] ?? '');

            if ($vId <= 0) {
                $_SESSION['flash_error'] = "Invalid product variant on item #" . ($idx + 1);
                Response::redirect(url('warehouse/website-orders/damage/create'));
                return;
            }

            $isAggregateStock = $this->isAggregateStockVariant($vId);
            if (!empty($tCode)) {
                $existingDamage = $isAggregateStock ? null : Database::fetchOne(
                    "SELECT damage_number FROM channel_damages 
                     WHERE (tag_code = ? OR tag_code LIKE ? OR tag_code LIKE ? OR tag_code LIKE ?)
                       AND status NOT IN ('REWORK_RETURNED_TO_STOCK', 'CANCELLED', 'RESOLVED')
                     LIMIT 1",
                    [$tCode, "{$tCode},%", "%,{$tCode}", "%,{$tCode},%"]
                );
                if ($existingDamage) {
                    $_SESSION['flash_error'] = "Piece tag '{$tCode}' is ALREADY logged in Damaged Stock (Ref: {$existingDamage['damage_number']}). Only available good inventory stock can be logged as damaged.";
                    Response::redirect(url('warehouse/website-orders/damage/create'));
                    return;
                }

                $existingSale = $isAggregateStock ? null : Database::fetchOne(
                    "SELECT s.invoice_number 
                     FROM sale_items si 
                     JOIN sales s ON si.sale_id = s.id 
                     WHERE (si.scanned_qr_codes LIKE ? OR si.scanned_qr_codes = ?)
                       AND s.status NOT IN ('CANCELLED', 'RETURNED')
                     LIMIT 1",
                    ["%{$tCode}%", $tCode]
                );
                if ($existingSale) {
                    $_SESSION['flash_error'] = "Piece tag '{$tCode}' has ALREADY been billed / sold in Invoice {$existingSale['invoice_number']}. Only available good inventory stock can be logged as damaged.";
                    Response::redirect(url('warehouse/website-orders/damage/create'));
                    return;
                }
            }

            $avail = 0;
            $websiteChanIds = $this->getWebsiteChannelIds();
            foreach ($websiteChanIds as $wId) {
                $avail += StockService::getBalance($vId, 'CHANNEL', $wId, 'AVAILABLE');
            }
            $requiredQty = $qty;
            if ($isAggregateStock) {
                $requiredQty = ($aggregateRequestedQty[$vId] ?? 0) + $qty;
                $aggregateRequestedQty[$vId] = $requiredQty;
            }
            if ($avail < $requiredQty && !$isAggregateStock) {
                $shopBal = StockService::getBalance($vId, 'SHOP', 1, 'AVAILABLE');
                if ($shopBal >= $requiredQty) {
                    $avail = $shopBal;
                }
            }
            if ($avail < $requiredQty) {
                $_SESSION['flash_error'] = "Insufficient available good stock in Website Channel for item #" . ($idx + 1) . " (Available: {$avail}, Requested: {$requiredQty}).";
                Response::redirect(url('warehouse/website-orders/damage/create'));
                return;
            }
        }

        // Process all items
        $loggedNumbers = [];
        $totalQtyLogged = 0;
        foreach ($items as $itm) {
            $vId = (int)($itm['variant_id'] ?? 0);
            $qty = max(1, (int)($itm['quantity'] ?? 1));
            $tCode = trim($itm['tag_code'] ?? '');
            $allocId = !empty($itm['allocation_id']) ? (int)$itm['allocation_id'] : null;
            $cUnit = (float)($itm['cost_per_unit'] ?? 0);

            if ($cUnit <= 0) {
                $vRow = Database::fetchOne(
                    "SELECT pv.selling_price, pv.mrp, pv.production_cost, COALESCE(p.gst_percentage, 5.0) as gst_rate 
                     FROM product_variants pv 
                     JOIN products p ON pv.product_id = p.id 
                     WHERE pv.id = ?", 
                    [$vId]
                );
                $sPrice = (float)(($vRow['selling_price'] ?? 0) > 0 ? $vRow['selling_price'] : (($vRow['mrp'] ?? 0) > 0 ? $vRow['mrp'] : 100));
                $gst = (float)($vRow['gst_rate'] ?? 5.0);
                $cUnit = round($sPrice / (1 + ($gst / 100)), 2);
            }

            $targetChannelId = $channelId;
            foreach ($websiteChanIds as $wId) {
                if (StockService::getBalance($vId, 'CHANNEL', $wId, 'AVAILABLE') >= $qty) {
                    $targetChannelId = $wId;
                    break;
                }
            }

            $damageNumber = ChannelDamage::generateDamageNumber('DMG-WEB');

            $damageId = ChannelDamage::create([
                'damage_number'  => $damageNumber,
                'channel_id'     => $targetChannelId,
                'variant_id'     => $vId,
                'tag_code'       => $tCode,
                'source_type'    => $sourceType,
                'allocation_id'  => $allocId,
                'defect_type'    => $defectType,
                'quantity'       => $qty,
                'cost_per_unit'  => $cUnit,
                'damage_reason'  => $damageReason,
                'defect_notes'   => $defectNotes,
                'photo_url'      => $photoUrl,
                'status'         => 'LOGGED',
                'reported_by'    => $userId,
            ]);

            try {
                StockService::decrease(
                    variantId: $vId,
                    locationType: 'CHANNEL',
                    locationId: $targetChannelId,
                    stockState: 'AVAILABLE',
                    quantity: (float)$qty,
                    transactionType: 'DAMAGE_QUARANTINE',
                    referenceType: 'channel_damages',
                    referenceId: 0,
                    userId: $userId,
                    notes: "Quarantined {$qty} pcs to DAMAGED via {$damageNumber}"
                );
            } catch (\Throwable $e) {
                try {
                    StockService::decrease(
                        variantId: $vId,
                        locationType: 'SHOP',
                        locationId: 1,
                        stockState: 'AVAILABLE',
                        quantity: (float)$qty,
                        transactionType: 'DAMAGE_QUARANTINE',
                        referenceType: 'channel_damages',
                        referenceId: 0,
                        userId: $userId,
                        notes: "Quarantined {$qty} pcs to DAMAGED via {$damageNumber}"
                    );
                } catch (\Throwable $e2) {
                    // Ignore stock deduction failure if already logged
                }
            }

            try {
                StockService::increase(
                    variantId: $vId,
                    locationType: 'CHANNEL',
                    locationId: $targetChannelId,
                    stockState: 'DAMAGED',
                    quantity: (float)$qty,
                    transactionType: 'DAMAGE_QUARANTINE',
                    referenceType: 'channel_damages',
                    referenceId: 0,
                    userId: $userId,
                    notes: "Quarantined {$qty} pcs to DAMAGED via {$damageNumber}"
                );
            } catch (\Throwable $e) {
                // Ignore
            }

            $loggedNumbers[] = $damageNumber;
            $totalQtyLogged += $qty;
        }

        $summaryMsg = count($loggedNumbers) === 1
            ? "Damage record {$loggedNumbers[0]} logged successfully ({$totalQtyLogged} pcs quarantined)."
            : count($loggedNumbers) . " damage records (" . implode(', ', array_slice($loggedNumbers, 0, 3)) . (count($loggedNumbers) > 3 ? '...' : '') . ") logged successfully ({$totalQtyLogged} pcs quarantined).";

        $_SESSION['flash_success'] = $summaryMsg;
        Response::redirect(url('warehouse/website-orders/damage'));
    }

    /**
     * View Single Damage Record
     */
    public function viewWebsiteDamage(int $id): void {
        $damage = ChannelDamage::find($id);
        if (!$damage) {
            $_SESSION['flash_error'] = "Damage record not found.";
            Response::redirect(url('warehouse/website-orders/damage'));
            return;
        }

        Response::view('website_orders.damage.view', [
            'damage'       => $damage,
            'activeModule' => 'website',
            'activeMenu'   => 'wh-website-damage',
            'pageTitle'    => 'Damage Record: ' . $damage['damage_number']
        ]);
    }

    /**
     * List Outward Return Batches
     */
    public function websiteDamageOutwardList(): void {
        if (!hasPermission('website_damage', 'view') && !hasPermission('website_damage', 'outward')) {
            Session::flash('error', 'Access denied. You do not have permission to view Website damage outward batches.');
            Response::redirect(url('warehouse/website-orders/damage'));
            return;
        }
        $chan = $this->getChannel();
        $channelIds = $this->getWebsiteChannelIds();
        $placeholders = implode(',', array_fill(0, count($channelIds), '?'));

        $batches = Database::fetchAll(
            "SELECT wr.*, 
                    w.name as warehouse_name, w.code as warehouse_code,
                    sc.name as channel_name,
                    u.full_name as created_by_name,
                    (SELECT COALESCE(SUM(returned_qty), 0) FROM warehouse_return_items WHERE warehouse_return_id = wr.id) as total_quantity,
                    (SELECT COUNT(DISTINCT variant_id) FROM warehouse_return_items WHERE warehouse_return_id = wr.id) as item_count
             FROM warehouse_returns wr
             LEFT JOIN warehouses w ON wr.warehouse_id = w.id
             LEFT JOIN sales_channels sc ON wr.source_id = sc.id
             LEFT JOIN users u ON wr.received_by = u.id
             WHERE wr.source_type = 'CHANNEL' AND wr.source_id IN ($placeholders)
             ORDER BY wr.created_at DESC",
            $channelIds
        );

        Response::view('website_orders.damage.outward_list', [
            'batches'      => $batches,
            'channel'      => $chan,
            'activeModule' => 'website',
            'activeMenu'   => 'wh-website-damage',
            'pageTitle'    => 'Damaged Stock Outward Return Batches'
        ]);
    }

    /**
     * Form to Create Outward Return Batch
     */
    public function createWebsiteDamageOutward(): void {
        if (!hasPermission('website_damage', 'outward')) {
            Session::flash('error', 'Access denied. You do not have permission to create Website damage outward returns.');
            Response::redirect(url('warehouse/website-orders/damage/outward'));
            return;
        }
        $chan = $this->getChannel();
        $channelIds = $this->getWebsiteChannelIds();

        $pendingItems = ChannelDamage::getPendingOutward($channelIds);
        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : [];

        Response::view('website_orders.damage.outward_create', [
            'channel'      => $chan,
            'pendingItems' => $pendingItems,
            'warehouses'   => $warehouses,
            'activeModule' => 'website',
            'activeMenu'   => 'wh-website-damage',
            'pageTitle'    => 'Create Damaged Outward Return Batch'
        ]);
    }

    /**
     * Store Outward Return Batch
     */
    public function storeWebsiteDamageOutward(): void {
        if (!hasPermission('website_damage', 'outward')) {
            Session::flash('error', 'Access denied. You do not have permission to dispatch Website damage outward returns.');
            Response::redirect(url('warehouse/website-orders/damage/outward'));
            return;
        }
        CSRF::verify();
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 2);
        $userId = Session::get('user_id') ?? 1;

        $warehouseId = (int)($_POST['warehouse_id'] ?? 1);
        $vehicleNo   = trim($_POST['vehicle_number'] ?? '');
        $driverName  = trim($_POST['driver_name'] ?? '');
        $driverPhone = trim($_POST['driver_phone'] ?? '');
        $notes       = trim($_POST['notes'] ?? '');
        $damageIds   = $_POST['damage_ids'] ?? [];
        $dispatchQty = $_POST['dispatch_qty'] ?? [];

        if (empty($damageIds)) {
            $_SESSION['flash_error'] = "Please select at least one damaged item to include in the outward return.";
            Response::redirect(url('warehouse/website-orders/damage/outward/create'));
            return;
        }

        $today = date('Ymd');
        $lastBatch = Database::fetchOne("SELECT return_number FROM warehouse_returns WHERE return_number LIKE 'RET-WEB-{$today}-%' ORDER BY id DESC LIMIT 1");
        $seq = 1;
        if ($lastBatch && !empty($lastBatch['return_number'])) {
            $parts = explode('-', $lastBatch['return_number']);
            $seq = (int)end($parts) + 1;
        }
        $returnNumber = sprintf('RET-WEB-%s-%04d', $today, $seq);

        $notesData = [
            'vehicle_number'   => $vehicleNo,
            'driver_name'      => $driverName,
            'driver_phone'     => $driverPhone,
            'remarks'          => $notes,
            'dispatched_from'  => $chan['name'],
        ];

        Database::execute(
            "INSERT INTO warehouse_returns 
             (return_number, source_type, source_id, warehouse_id, destination_id, status, notes, received_by, created_at, updated_at)
             VALUES (?, 'CHANNEL', ?, ?, ?, 'DISPATCHED', ?, ?, NOW(), NOW())",
            [
                $returnNumber,
                $channelId,
                $warehouseId,
                $warehouseId,
                json_encode($notesData),
                $userId
            ]
        );

        $returnId = (int)Database::lastInsertId();
        $totalDispatchedPieces = 0;

        foreach ($damageIds as $dmgId) {
            $dmg = ChannelDamage::find((int)$dmgId);
            if (!$dmg || $dmg['channel_id'] != $channelId) continue;

            $qty = isset($dispatchQty[$dmgId]) ? (int)$dispatchQty[$dmgId] : (int)$dmg['quantity'];
            $qty = max(1, min((int)$dmg['quantity'], $qty));

            Database::execute(
                "INSERT INTO warehouse_return_items 
                 (warehouse_return_id, variant_id, returned_qty, good_qty, damaged_qty, defective_qty, classification)
                 VALUES (?, ?, ?, 0, ?, 0, 'DAMAGED')",
                [
                    $returnId,
                    $dmg['variant_id'],
                    $qty,
                    $qty
                ]
            );

            Database::execute(
                "UPDATE channel_damages SET outward_batch_id = ?, status = 'DISPATCHED', updated_at = NOW() WHERE id = ?",
                [$returnId, $dmg['id']]
            );

            StockService::decrease(
                variantId: (int)$dmg['variant_id'],
                locationType: 'CHANNEL',
                locationId: $channelId,
                stockState: 'DAMAGED',
                quantity: (float)$qty,
                transactionType: 'TRANSFER_OUT',
                referenceType: 'warehouse_returns',
                referenceId: $returnId,
                userId: $userId,
                notes: "Dispatched to Warehouse via Return Batch {$returnNumber}"
            );

            $totalDispatchedPieces += $qty;
        }

        $_SESSION['flash_success'] = "Damaged Outward Return Batch {$returnNumber} created successfully ({$totalDispatchedPieces} pcs dispatched)!";
        Response::redirect(url('warehouse/website-orders/damage/outward/view/' . $returnId));
    }

    private function getDamageOutwardBatch(int $id): ?array {
        $batch = Database::fetchOne(
            "SELECT wr.*, 
                    w.name as warehouse_name, w.code as warehouse_code, w.address as warehouse_address,
                    sc.name as channel_name,
                    u.full_name as created_by_name
             FROM warehouse_returns wr
             LEFT JOIN warehouses w ON wr.warehouse_id = w.id
             LEFT JOIN sales_channels sc ON wr.source_id = sc.id
             LEFT JOIN users u ON wr.received_by = u.id
             WHERE wr.id = ?",
            [$id]
        );

        if (!$batch) return null;

        $notes = json_decode($batch['notes'] ?? '', true);
        if (is_array($notes)) {
            $batch = array_merge($batch, $notes);
        }

        $items = Database::fetchAll(
            "SELECT wri.*, 
                    pv.sku as variant_sku, pv.sku,
                    p.name as product_name,
                    c.name as color,
                    s.name as size
             FROM warehouse_return_items wri
             JOIN product_variants pv ON wri.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             WHERE wri.warehouse_return_id = ?",
            [$id]
        );

        $damages = Database::fetchAll("SELECT * FROM channel_damages WHERE outward_batch_id = ?", [$id]);
        $damageMap = [];
        foreach ($damages as $d) {
            $damageMap[$d['variant_id']] = $d;
        }

        foreach ($items as &$it) {
            $it['quantity'] = $it['returned_qty'];
            if (isset($damageMap[$it['variant_id']])) {
                $dmg = $damageMap[$it['variant_id']];
                $it['defect_type'] = $dmg['defect_type'] ?? 'TRANSIT_DAMAGE';
                $it['piece_tags'] = ChannelDamage::resolvePieceTags($dmg);
                $it['tag_code'] = $dmg['tag_code'] ?? '';
            } else {
                $it['defect_type'] = $it['reason'] ?? 'DAMAGED';
                $it['piece_tags'] = [];
                $it['tag_code'] = '';
            }
        }

        $batch['items'] = $items;
        $batch['total_quantity'] = array_sum(array_column($items, 'returned_qty'));

        return $batch;
    }

    public function viewWebsiteDamageOutward(int $id): void {
        $batch = $this->getDamageOutwardBatch($id);
        if (!$batch) {
            $_SESSION['flash_error'] = "Outward Batch not found.";
            Response::redirect(url('warehouse/website-orders/damage/outward'));
            return;
        }

        Response::view('website_orders.damage.outward_view', [
            'batch'        => $batch,
            'items'        => $batch['items'],
            'activeModule' => 'website',
            'activeMenu'   => 'wh-website-damage',
            'pageTitle'    => 'Outward Batch: ' . ($batch['return_number'] ?? ('RET-WEB-' . $batch['id']))
        ]);
    }

    public function printWebsiteDamageGatePass(int $id): void {
        if (!hasPermission('website_damage', 'print')) {
            Session::flash('error', 'Access denied. You do not have permission to print Website gate passes.');
            Response::redirect(url('warehouse/website-orders/damage'));
            return;
        }
        $batch = $this->getDamageOutwardBatch($id);
        if (!$batch) {
            echo "Outward Batch not found.";
            return;
        }

        Response::viewOnly('website_orders.damage.gatepass', [
            'batch' => $batch,
            'items' => $batch['items']
        ]);
    }

    public function printWebsiteDamageCartonLabel(int $id): void {
        if (!hasPermission('website_damage', 'print')) {
            Session::flash('error', 'Access denied. You do not have permission to print Website carton labels.');
            Response::redirect(url('warehouse/website-orders/damage'));
            return;
        }
        $batch = $this->getDamageOutwardBatch($id);
        if (!$batch) {
            echo "Outward Batch not found.";
            return;
        }

        Response::viewOnly('website_orders.damage.carton_label', [
            'batch' => $batch,
            'items' => $batch['items']
        ]);
    }

    public function ajaxLookupDamageProduct(): void {
        header('Content-Type: application/json');
        $rawCode = trim($_REQUEST['query'] ?? $_REQUEST['q'] ?? $_REQUEST['code'] ?? $_REQUEST['search'] ?? '');
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 2);

        if (empty($rawCode)) {
            echo json_encode(['success' => false, 'message' => 'No scan code or search term provided.', 'results' => []]);
            return;
        }

        $rawCode = trim($rawCode, " \t\n\r\0\x0B\"'");
        $extractedTag = $rawCode;
        $candidateCodes = [$rawCode];

        // 1. JSON payload
        if (str_starts_with($rawCode, '{') && str_ends_with($rawCode, '}')) {
            $decoded = json_decode($rawCode, true);
            if (is_array($decoded)) {
                $extractedTag = $decoded['tag'] ?? $decoded['tag_code'] ?? $decoded['code'] ?? $rawCode;
                if (!empty($decoded['sku'])) $candidateCodes[] = trim($decoded['sku']);
                if (!empty($decoded['tag'])) $candidateCodes[] = trim($decoded['tag']);
                if (!empty($decoded['tag_code'])) $candidateCodes[] = trim($decoded['tag_code']);
            }
        }
        // 2. Pipe-delimited QR payload (e.g. "KB102611|BCT0303-12Y-BLK|MRP:999")
        elseif (strpos($rawCode, '|') !== false) {
            $parts = array_map('trim', explode('|', $rawCode));
            $extractedTag = $parts[0];
            foreach ($parts as $part) {
                if (stripos($part, 'MRP:') === 0 || stripos($part, 'Disc:') === 0 || stripos($part, 'Cost:') === 0 || stripos($part, 'Price:') === 0) continue;
                $candidateCodes[] = $part;
            }
            if (isset($parts[1]) && !empty($parts[1])) {
                $candidateCodes[] = $parts[1];
            }
        }

        $candidateCodes = array_values(array_unique(array_filter($candidateCodes)));
        $foundVariant = null;
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
                    OR ai.selected_qr_codes LIKE ?
                    OR FIND_IN_SET(?, REPLACE(COALESCE(ai.selected_qr_codes, ''), ' ', '')) > 0)
                 ORDER BY ai.id DESC LIMIT 1",
                [$c, "{$c},%", "%,{$c}", "%,{$c},%", $c]
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
                if ($foundVariant) {
                    $matchedTagCode = $c;
                    $matchedAllocId = (int)$allocItem['allocation_id'];
                    break;
                }
            }
        }

        // Strategy 2: Direct match on candidate codes (SKU, QR Code, Product Code, ID)
        if (!$foundVariant) {
            foreach ($candidateCodes as $c) {
                if (empty($c)) continue;
                $foundVariant = Database::fetchOne(
                    "SELECT pv.*, p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                            COALESCE(p.gst_percentage, 5.0) as gst_rate,
                            c.name as color_name, s.name as size_name
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN colors c ON pv.color_id = c.id
                     LEFT JOIN sizes s ON pv.size_id = s.id
                     WHERE pv.sku = ? OR pv.qr_code = ? OR p.code = ? OR (pv.id = ? AND ? > 0)
                     LIMIT 1",
                    [$c, $c, $c, is_numeric($c) ? (int)$c : 0, is_numeric($c) ? (int)$c : 0]
                );
                if ($foundVariant) break;
            }
        }

        // Strategy 3: Dynamic Piece Tag resolution (e.g. KB102644 -> Category KB, Month 10, Year 26, Sequence 44)
        if (!$foundVariant) {
            foreach ($candidateCodes as $c) {
                if (preg_match('/^([A-Za-z]+)(\d{2})(\d{2})(\d+)$/', $c, $m)) {
                    $prefix = strtoupper($m[1]);
                    $seq    = (int)$m[4];
                    $varIndex = (int)floor(($seq - 1) / 20);

                    $allProducts = Database::fetchAll(
                        "SELECT p.id, p.name, p.code, p.qr_code_prefix, c.name as cat_name, c.code as cat_code,
                                sc.name as subcat_name, sc.code as subcat_code,
                                ssc.name as sub_subcat_name, ssc.code as sub_subcat_code
                         FROM products p
                         LEFT JOIN categories c ON p.category_id = c.id
                         LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
                         LEFT JOIN sub_subcategories ssc ON p.sub_subcategory_id = ssc.id"
                    );

                    $matchedProductId = null;
                    foreach ($allProducts as $p) {
                        $c1 = !empty($p['cat_name']) ? trim($p['cat_name']) : (!empty($p['cat_code']) ? trim($p['cat_code']) : '');
                        $c2 = !empty($p['subcat_name']) ? trim($p['subcat_name']) : (!empty($p['subcat_code']) ? trim($p['subcat_code']) : '');
                        $c3 = !empty($p['sub_subcat_name']) ? trim($p['sub_subcat_name']) : (!empty($p['sub_subcat_code']) ? trim($p['sub_subcat_code']) : '');

                        $catPrefix = '';
                        if ($c1 !== '') $catPrefix .= strtoupper($c1[0]);
                        if ($c2 !== '') $catPrefix .= strtoupper($c2[0]);
                        if ($c3 !== '') $catPrefix .= strtoupper($c3[0]);
                        if (strlen($catPrefix) < 2 && !empty($p['qr_code_prefix'])) {
                            $clean = preg_replace('/[^A-Za-z0-9]/', '', $p['qr_code_prefix']);
                            $catPrefix = strtoupper(substr($clean, 0, 3));
                        }
                        if (empty($catPrefix)) $catPrefix = 'TTS';

                        if (strtoupper($catPrefix) === $prefix || strtoupper(substr($p['code'] ?? '', 0, strlen($prefix))) === $prefix) {
                            $matchedProductId = (int)$p['id'];
                            break;
                        }
                    }

                    if ($matchedProductId) {
                        $variants = Database::fetchAll(
                            "SELECT pv.*, p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                                    COALESCE(p.gst_percentage, 5.0) as gst_rate,
                                    c.name as color_name, s.name as size_name
                             FROM product_variants pv
                             JOIN products p ON pv.product_id = p.id
                             LEFT JOIN colors c ON pv.color_id = c.id
                             LEFT JOIN sizes s ON pv.size_id = s.id
                             WHERE pv.product_id = ?
                             ORDER BY pv.id ASC",
                            [$matchedProductId]
                        );

                        if (!empty($variants)) {
                            $foundVariant = $variants[$varIndex] ?? $variants[count($variants) - 1];
                            $matchedTagCode = $c;
                            break;
                        }
                    }
                }
            }
        }

        // Strategy 4: Fuzzy / Substring match
        if (!$foundVariant) {
            foreach ($candidateCodes as $c) {
                if (strlen($c) < 2) continue;
                $foundVariant = Database::fetchOne(
                    "SELECT pv.*, p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                            COALESCE(p.gst_percentage, 5.0) as gst_rate,
                            c.name as color_name, s.name as size_name
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN colors c ON pv.color_id = c.id
                     LEFT JOIN sizes s ON pv.size_id = s.id
                     WHERE pv.sku LIKE ? OR p.name LIKE ? OR p.code LIKE ? OR pv.qr_code LIKE ?
                     LIMIT 1",
                    ["%{$c}%", "%{$c}%", "%{$c}%", "%{$c}%"]
                );
                if ($foundVariant) break;
            }
        }

        // Also fetch suggestions / results list
        $qWild = "%{$rawCode}%";
        $variants = Database::fetchAll(
            "SELECT pv.id as variant_id, pv.sku, pv.qr_code, pv.mrp, pv.selling_price,
                    p.name as product_name, p.code as product_code, p.image_url as product_image,
                    c.name as color, s.name as size
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             WHERE pv.sku LIKE ? OR pv.qr_code LIKE ? OR p.name LIKE ? OR p.code LIKE ?
             LIMIT 15",
            [$qWild, $qWild, $qWild, $qWild]
        );

        $websiteChanIds = $this->getWebsiteChannelIds();
        $results = [];
        foreach ($variants as $v) {
            $avail = 0;
            $dmgStock = 0;
            foreach ($websiteChanIds as $wId) {
                $avail += StockService::getBalance((int)$v['variant_id'], 'CHANNEL', $wId, 'AVAILABLE');
                $dmgStock += StockService::getBalance((int)$v['variant_id'], 'CHANNEL', $wId, 'DAMAGED');
            }
            if ($avail <= 0) {
                $avail = StockService::getBalance((int)$v['variant_id'], 'SHOP', 1, 'AVAILABLE');
            }
            $v['available_stock'] = $avail;
            $v['damaged_stock'] = $dmgStock;
            $results[] = $v;
        }

        if ($foundVariant) {
            $isAggregateStock = $this->isAggregateStockVariant((int)$foundVariant['id']);

            // Restriction 1: Check if piece tag is already logged as damaged / quarantined
            if (!empty($matchedTagCode)) {
                $existingDamage = $isAggregateStock ? null : Database::fetchOne(
                    "SELECT damage_number, status, created_at FROM channel_damages 
                     WHERE (tag_code = ? OR tag_code LIKE ? OR tag_code LIKE ? OR tag_code LIKE ?)
                       AND status NOT IN ('REWORK_RETURNED_TO_STOCK', 'CANCELLED', 'RESOLVED')
                     ORDER BY id DESC LIMIT 1",
                    [$matchedTagCode, "{$matchedTagCode},%", "%,{$matchedTagCode}", "%,{$matchedTagCode},%"]
                );
                if ($existingDamage) {
                    echo json_encode([
                        'success' => false,
                        'is_already_damaged' => true,
                        'message' => "Piece tag '{$matchedTagCode}' is ALREADY logged in Damaged / Quarantine Stock (Ref: {$existingDamage['damage_number']}, Status: {$existingDamage['status']}). Only available good inventory stock can be logged as damaged."
                    ]);
                    return;
                }

                // Restriction 2: Check if piece tag has already been billed / sold
                $existingSale = $isAggregateStock ? null : Database::fetchOne(
                    "SELECT s.invoice_number, s.status, s.created_at 
                     FROM sale_items si 
                     JOIN sales s ON si.sale_id = s.id 
                     WHERE (si.scanned_qr_codes LIKE ? OR si.scanned_qr_codes = ?)
                       AND s.status NOT IN ('CANCELLED', 'RETURNED')
                     ORDER BY si.id DESC LIMIT 1",
                    ["%{$matchedTagCode}%", $matchedTagCode]
                );
                if ($existingSale) {
                    echo json_encode([
                        'success' => false,
                        'is_already_sold' => true,
                        'message' => "Piece tag '{$matchedTagCode}' has ALREADY been billed / sold in Invoice {$existingSale['invoice_number']}. Only available good inventory stock can be logged as damaged."
                    ]);
                    return;
                }
            }

            $avail = 0;
            $dmgStock = 0;
            foreach ($websiteChanIds as $wId) {
                $avail += StockService::getBalance((int)$foundVariant['id'], 'CHANNEL', $wId, 'AVAILABLE');
                $dmgStock += StockService::getBalance((int)$foundVariant['id'], 'CHANNEL', $wId, 'DAMAGED');
            }
            if ($avail <= 0 && !$isAggregateStock) {
                $avail = StockService::getBalance((int)$foundVariant['id'], 'SHOP', 1, 'AVAILABLE');
            }

            if ($avail <= 0) {
                echo json_encode([
                    'success' => false,
                    'is_out_of_stock' => true,
                    'message' => "Product variant '{$foundVariant['product_name']}' ({$foundVariant['sku']}) has 0 available good inventory stock in Website channel."
                ]);
                return;
            }

            $exactPrice = (float)(($foundVariant['selling_price'] ?? 0) > 0 ? $foundVariant['selling_price'] : (($foundVariant['mrp'] ?? 0) > 0 ? $foundVariant['mrp'] : (($foundVariant['production_cost'] ?? 0) > 0 ? $foundVariant['production_cost'] : 100)));
            $mrp = (float)($foundVariant['mrp'] ?? $foundVariant['selling_price'] ?? 0);
            $costPrice = (float)(($foundVariant['production_cost'] ?? 0) > 0 ? $foundVariant['production_cost'] : ($foundVariant['purchase_price'] ?? 0));
            $gstRate = (float)($foundVariant['gst_rate'] ?? 5.0);
            if ($gstRate < 0) $gstRate = 5.0;
            $priceExGst = round($exactPrice / (1 + ($gstRate / 100)), 2);

            $dataPayload = [
                'variant_id'      => (int)$foundVariant['id'],
                'tag_code'        => $matchedTagCode,
                'allocation_id'   => $matchedAllocId,
                'sku'             => $foundVariant['sku'],
                'product_name'    => $foundVariant['product_name'],
                'product_code'    => $foundVariant['product_code'],
                'size_name'       => $foundVariant['size_name'] ?? 'Std',
                'color_name'      => $foundVariant['color_name'] ?? 'Std',
                'price'           => $exactPrice,
                'selling_price'   => $exactPrice,
                'mrp'             => $mrp,
                'gst_rate'        => $gstRate,
                'price_ex_gst'    => $priceExGst,
                'cost_price'      => $costPrice,
                'available_stock' => $avail,
                'damaged_stock'   => $dmgStock,
                'is_aggregate_stock' => $isAggregateStock,
                'image'           => $foundVariant['product_image'] ?? $foundVariant['image_url'] ?? ''
            ];

            echo json_encode([
                'success'       => true,
                'tag_code'      => $matchedTagCode,
                'allocation_id' => $matchedAllocId,
                'data'          => $dataPayload,
                'variant'       => $dataPayload,
                'results'       => $results
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Product variant not found in Website inventory for '{$rawCode}'.",
                'results' => $results
            ]);
        }
    }

    // =========================================================================
    // RETURNS & EXCHANGES
    // =========================================================================

    public function websiteReturnsIndex(): void {
        if (!hasPermission('website_returns', 'view')) {
            Session::flash('error', 'Access denied. You do not have permission to view Website returns.');
            Response::redirect(url('warehouse/website-orders'));
            return;
        }
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $returnType = trim($_GET['type'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $where = ["r.source_type = 'WEBSITE'", "(s.sales_channel = 'WEBSITE' OR s.sale_type = 'WEBSITE' OR s.notes LIKE '%Website%' OR s.invoice_number LIKE 'WEB-%')"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(r.return_number LIKE ? OR s.invoice_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
            $sWild = "%{$search}%";
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
                    s.sales_channel,
                    COALESCE(c.name, s.customer_name, 'Website Customer') as customer_name,
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
                    COALESCE((SELECT SUM(quantity) FROM stock_balances WHERE variant_id = pv.id AND location_type = 'WAREHOUSE' AND concern_id = " . (int)currentConcernId() . " AND stock_state = 'AVAILABLE'), 0) as available_stock
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors clr ON pv.color_id = clr.id
             WHERE p.is_active = 1
             ORDER BY p.name ASC, sz.sort_order ASC"
        );

        Response::view('channel_returns', [
            'pageTitle'           => 'Website Returns & Exchanges',
            'activeModule'        => 'website',
            'activeMenu'          => 'wh-website-returns',
            'breadcrumb'          => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Returns & Exchanges']
            ],
            'returns'             => $returns,
            'totalReturnsCount'   => $totalReturnsCount,
            'totalExchangesCount' => $totalExchangesCount,
            'totalRefundAmount'   => $totalRefundAmount,
            'totalDamagedQty'     => $totalDamagedQty,
            'catalogVariants'     => $catalogVariants,
            'isWebsiteReturns'    => true,
            'search'              => $search,
            'status'              => $status,
            'returnType'          => $returnType,
            'dateFrom'            => $dateFrom,
            'dateTo'              => $dateTo,
        ]);
    }

    public function websiteReturnsLookup(): void {
        $term = trim($_REQUEST['q'] ?? $_REQUEST['query'] ?? $_REQUEST['term'] ?? $_REQUEST['invoice_number'] ?? $_REQUEST['search'] ?? '');
        if (empty($term)) {
            Response::jsonError("Please provide an invoice number, customer phone, or piece QR tag.");
            return;
        }

        if (preg_match('/^[+0-9().\-\s]+$/', $term)) {
            $phoneTerm = preg_replace('/[^0-9+]/', '', $term);
            if (strlen(preg_replace('/[^0-9]/', '', $phoneTerm)) >= 7) {
                $phoneSales = Database::fetchAll(
                    "SELECT s.*, COALESCE(c.name, s.customer_name, 'Website Customer') as customer_name,
                            COALESCE(c.phone, s.customer_phone, '-') as customer_phone, c.email as customer_email
                     FROM sales s LEFT JOIN customers c ON s.customer_id = c.id
                     WHERE (c.phone LIKE ? OR s.customer_phone LIKE ?)
                       AND (s.sales_channel = 'WEBSITE' OR s.sale_type = 'WEBSITE' OR s.notes LIKE '%Website%' OR s.invoice_number LIKE 'WEB-%')
                     ORDER BY s.id DESC LIMIT 50",
                    ["%{$phoneTerm}%", "%{$phoneTerm}%"]
                );
                $phoneOrders = [];
                foreach ($phoneSales as $phoneSale) {
                    $phoneItems = Database::fetchAll(
                        "SELECT si.*, p.id as product_id, p.name as product_name, p.code as product_code,
                                pv.sku, pv.qr_code, pv.mrp,
                                COALESCE(sz.name, 'Free Size') as size_name,
                                COALESCE(clr.name, 'Standard') as color_name
                         FROM sale_items si JOIN product_variants pv ON si.variant_id = pv.id
                         JOIN products p ON pv.product_id = p.id
                         LEFT JOIN sizes sz ON pv.size_id = sz.id LEFT JOIN colors clr ON pv.color_id = clr.id
                         WHERE si.sale_id = ?",
                        [(int)$phoneSale['id']]
                    );
                    foreach ($phoneItems as &$phoneItem) {
                        $returned = Database::fetchOne(
                            "SELECT COALESCE(SUM(quantity), 0) total FROM return_items WHERE sale_item_id = ?",
                            [(int)$phoneItem['id']]
                        );
                        $phoneItem['returnable_quantity'] = max(0, (int)$phoneItem['quantity'] - (int)($returned['total'] ?? 0));
                        $phoneItem['sibling_variants'] = Database::fetchAll(
                            "SELECT pv2.id, pv2.sku, pv2.selling_price as price, pv2.mrp,
                                    sz2.name as size_name, clr2.name as color_name
                             FROM product_variants pv2 LEFT JOIN sizes sz2 ON pv2.size_id = sz2.id
                             LEFT JOIN colors clr2 ON pv2.color_id = clr2.id
                             WHERE pv2.product_id = ? AND pv2.is_active = 1
                             ORDER BY sz2.sort_order ASC, clr2.name ASC",
                            [(int)$phoneItem['product_id']]
                        );
                    }
                    unset($phoneItem);
                    $existingInvoiceReturn = Database::fetchOne(
                        "SELECT id, return_number FROM returns WHERE sale_id = ? AND source_type = 'WEBSITE' ORDER BY id DESC LIMIT 1",
                        [(int)$phoneSale['id']]
                    );
                    $phoneOrders[] = [
                        'sale' => $phoneSale,
                        'items' => $phoneItems,
                        'already_returned' => (bool)$existingInvoiceReturn,
                        'return_number' => $existingInvoiceReturn['return_number'] ?? null,
                    ];
                }
                if (count($phoneOrders) > 1) {
                    Response::jsonSuccess('Website invoices found for this customer.', $phoneOrders);
                    return;
                }
            }
        }

        $sale = Database::fetchOne(
            "SELECT s.*, 
                    COALESCE(c.name, s.customer_name, 'Website Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone,
                    c.email as customer_email
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE (s.invoice_number = ? 
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
             AND (s.sales_channel = 'WEBSITE' OR s.sale_type = 'WEBSITE' OR s.notes LIKE '%Website%' OR s.invoice_number LIKE 'WEB-%')
             ORDER BY s.id DESC LIMIT 1",
            [
                $term, "%{$term}%", 
                $term, "%{$term}%", 
                $term, "%{$term}%", 
                "%{$term}%", "%{$term}%", 
                "%{$term}%", $term,
                "%{$term}%", $term
            ]
        );

        if (!$sale) {
            Response::jsonError("No billed Website order found for search: " . htmlspecialchars($term));
            return;
        }

        $items = Database::fetchAll(
            "SELECT si.*, 
                    p.id as product_id,
                    p.name as product_name, p.code as product_code,
                    pv.sku, pv.qr_code, pv.mrp,
                    COALESCE(sz.name, 'Free Size') as size_name,
                    COALESCE(clr.name, 'Standard') as color_name
             FROM sale_items si
             JOIN product_variants pv ON si.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors clr ON pv.color_id = clr.id
             WHERE si.sale_id = ?",
            [(int)$sale['id']]
        );

        foreach ($items as &$it) {
            $returnedQty = (int)(Database::fetchOne("SELECT COALESCE(SUM(quantity), 0) as total FROM return_items WHERE sale_item_id = ?", [$it['id']])['total'] ?? 0);
            $it['returnable_quantity'] = max(0, (int)$it['quantity'] - $returnedQty);
            
            // Sibling variants for size/color swap
            $siblings = Database::fetchAll(
                "SELECT pv2.id, pv2.sku, pv2.selling_price as price, pv2.mrp,
                        sz2.name as size_name, clr2.name as color_name
                 FROM product_variants pv2
                 LEFT JOIN sizes sz2 ON pv2.size_id = sz2.id
                 LEFT JOIN colors clr2 ON pv2.color_id = clr2.id
                 WHERE pv2.product_id = ? AND pv2.is_active = 1
                 ORDER BY sz2.sort_order ASC, clr2.name ASC",
                [(int)$it['product_id']]
            );
            $it['sibling_variants'] = $siblings;
        }
        unset($it);

        $existingInvoiceReturn = Database::fetchOne(
            "SELECT id, return_number FROM returns WHERE sale_id = ? AND source_type = 'WEBSITE' ORDER BY id DESC LIMIT 1",
            [(int)$sale['id']]
        );

        Response::jsonSuccess("Website Sale record loaded successfully", [
            [
                'sale'             => $sale,
                'items'            => $items,
                'already_returned' => (bool)$existingInvoiceReturn,
                'return_number'    => $existingInvoiceReturn['return_number'] ?? null,
            ]
        ]);
    }

    public function ajaxLookupReturnPiece(): void {
        header('Content-Type: application/json');
        $rawCode = trim($_REQUEST['query'] ?? $_REQUEST['q'] ?? $_REQUEST['code'] ?? $_REQUEST['search'] ?? '');
        
        if (empty($rawCode)) {
            echo json_encode(['success' => false, 'message' => 'No scan code or search term provided.']);
            return;
        }

        $rawCode = trim($rawCode, " \t\n\r\0\x0B\"'");
        $extractedTag = $rawCode;
        $candidateCodes = [$rawCode];

        // 1. JSON payload
        if (str_starts_with($rawCode, '{') && str_ends_with($rawCode, '}')) {
            $decoded = json_decode($rawCode, true);
            if (is_array($decoded)) {
                $extractedTag = $decoded['tag'] ?? $decoded['tag_code'] ?? $decoded['code'] ?? $rawCode;
                if (!empty($decoded['sku'])) $candidateCodes[] = trim($decoded['sku']);
                if (!empty($decoded['tag'])) $candidateCodes[] = trim($decoded['tag']);
                if (!empty($decoded['tag_code'])) $candidateCodes[] = trim($decoded['tag_code']);
            }
        }
        // 2. Pipe-delimited QR payload (e.g. "WEB102611|BCT0303-12Y-BLK|MRP:999")
        elseif (strpos($rawCode, '|') !== false) {
            $parts = array_map('trim', explode('|', $rawCode));
            $extractedTag = $parts[0];
            foreach ($parts as $part) {
                if (stripos($part, 'MRP:') === 0 || stripos($part, 'Disc:') === 0 || stripos($part, 'Cost:') === 0 || stripos($part, 'Price:') === 0) continue;
                $candidateCodes[] = $part;
            }
            if (isset($parts[1]) && !empty($parts[1])) {
                $candidateCodes[] = $parts[1];
            }
        }

        $candidateCodes = array_values(array_unique(array_filter($candidateCodes)));
        $foundVariant = null;
        $matchedTagCode = $extractedTag;
        $websiteChannelIds = $this->getWebsiteChannelIds();
        $channelPlaceholders = implode(',', array_fill(0, count($websiteChannelIds), '?'));

        // Strategy 1: Piece tag in allocation_items (selected_qr_codes)
        foreach ($candidateCodes as $c) {
            if (empty($c) || strlen($c) < 2) continue;
            $allocItem = Database::fetchOne(
                "SELECT ai.variant_id, ai.selected_qr_codes
                 FROM allocation_items ai
                 JOIN warehouse_allocations wa ON wa.id = ai.allocation_id
                 WHERE (ai.selected_qr_codes = ? 
                    OR ai.selected_qr_codes LIKE ? 
                    OR ai.selected_qr_codes LIKE ? 
                    OR ai.selected_qr_codes LIKE ?
                    OR FIND_IN_SET(?, REPLACE(COALESCE(ai.selected_qr_codes, ''), ' ', '')) > 0)
                    AND (wa.channel_id IN ({$channelPlaceholders})
                         OR (wa.destination_type = 'CHANNEL' AND wa.destination_id IN ({$channelPlaceholders})))
                 ORDER BY ai.id DESC LIMIT 1",
                array_merge([$c, "{$c},%", "%,{$c}", "%,{$c},%", $c], $websiteChannelIds, $websiteChannelIds)
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
                if ($foundVariant) {
                    $matchedTagCode = $c;
                    break;
                }
            }
        }

        // Strategy 2: Piece tag in a billed Website sale item
        if (!$foundVariant) {
            foreach ($candidateCodes as $c) {
                if (empty($c) || strlen($c) < 2) continue;
                $saleItem = Database::fetchOne(
                    "SELECT si.variant_id
                     FROM sale_items si
                     JOIN sales s ON s.id = si.sale_id
                     WHERE (si.scanned_qr_codes = ?
                        OR si.scanned_qr_codes LIKE ?
                        OR si.scanned_qr_codes LIKE ?
                        OR si.scanned_qr_codes LIKE ?
                        OR FIND_IN_SET(?, REPLACE(COALESCE(si.scanned_qr_codes, ''), ' ', '')) > 0
                        OR si.qr_code = ?)
                       AND (s.sales_channel = 'WEBSITE' OR s.sale_type = 'WEBSITE'
                            OR s.notes LIKE '%Website%' OR s.invoice_number LIKE 'WEB-%')
                     ORDER BY si.id DESC LIMIT 1",
                    [$c, "{$c},%", "%,{$c}", "%,{$c},%", $c, $c]
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
                    if ($foundVariant) {
                        $matchedTagCode = $c;
                        break;
                    }
                }
            }
        }

        // Strategy 3: Direct variant match by SKU or QR code
        if (!$foundVariant) {
            foreach ($candidateCodes as $c) {
                if (empty($c)) continue;
                $foundVariant = Database::fetchOne(
                    "SELECT pv.*, p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                            c.name as color_name, s.name as size_name
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN colors c ON pv.color_id = c.id
                     LEFT JOIN sizes s ON pv.size_id = s.id
                     WHERE pv.sku = ? OR pv.qr_code = ? OR pv.sku LIKE ? LIMIT 1",
                    [$c, $c, "%{$c}%"]
                );
                if ($foundVariant) {
                    $matchedTagCode = $c;
                    break;
                }
            }
        }

        // Strategy 4: Dynamic Piece Tag resolution (e.g. KB102648 -> Category KB, Month 10, Year 26, Sequence 48)
        if (!$foundVariant) {
            foreach ($candidateCodes as $c) {
                if (preg_match('/^([A-Za-z]+)(\d{2})(\d{2})(\d+)$/', $c, $m)) {
                    $prefix = strtoupper($m[1]);
                    $seq    = (int)$m[4];
                    $varIndex = (int)floor(($seq - 1) / 20);

                    $allProducts = Database::fetchAll(
                        "SELECT p.id, p.name, p.code, p.qr_code_prefix, c.name as cat_name, c.code as cat_code,
                                sc.name as subcat_name, sc.code as subcat_code,
                                ssc.name as sub_subcat_name, ssc.code as sub_subcat_code
                         FROM products p
                         LEFT JOIN categories c ON p.category_id = c.id
                         LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
                         LEFT JOIN sub_subcategories ssc ON p.sub_subcategory_id = ssc.id"
                    );

                    $matchedProductId = null;
                    foreach ($allProducts as $p) {
                        $c1 = !empty($p['cat_name']) ? trim($p['cat_name']) : (!empty($p['cat_code']) ? trim($p['cat_code']) : '');
                        $c2 = !empty($p['subcat_name']) ? trim($p['subcat_name']) : (!empty($p['subcat_code']) ? trim($p['subcat_code']) : '');
                        $c3 = !empty($p['sub_subcat_name']) ? trim($p['sub_subcat_name']) : (!empty($p['sub_subcat_code']) ? trim($p['sub_subcat_code']) : '');

                        $catPrefix = '';
                        if ($c1 !== '') $catPrefix .= strtoupper($c1[0]);
                        if ($c2 !== '') $catPrefix .= strtoupper($c2[0]);
                        if ($c3 !== '') $catPrefix .= strtoupper($c3[0]);
                        if (strlen($catPrefix) < 2 && !empty($p['qr_code_prefix'])) {
                            $clean = preg_replace('/[^A-Za-z0-9]/', '', $p['qr_code_prefix']);
                            $catPrefix = strtoupper(substr($clean, 0, 3));
                        }
                        if (empty($catPrefix)) $catPrefix = 'TTS';

                        if (strtoupper($catPrefix) === $prefix || strtoupper(substr($p['code'] ?? '', 0, strlen($prefix))) === $prefix) {
                            $matchedProductId = (int)$p['id'];
                            break;
                        }
                    }

                    if ($matchedProductId) {
                        $variants = Database::fetchAll(
                            "SELECT pv.*, p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                                    c.name as color_name, s.name as size_name
                             FROM product_variants pv
                             JOIN products p ON pv.product_id = p.id
                             LEFT JOIN colors c ON pv.color_id = c.id
                             LEFT JOIN sizes s ON pv.size_id = s.id
                             WHERE pv.product_id = ?
                             ORDER BY pv.id ASC",
                            [$matchedProductId]
                        );

                        if (!empty($variants)) {
                            $foundVariant = $variants[$varIndex] ?? $variants[count($variants) - 1];
                            $matchedTagCode = $c;
                            break;
                        }
                    }
                }
            }
        }

        // Strategy 5: Fuzzy / Substring match by product name or code
        if (!$foundVariant && !empty($rawCode)) {
            $foundVariant = Database::fetchOne(
                "SELECT pv.*, p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                        c.name as color_name, s.name as size_name
                 FROM product_variants pv
                 JOIN products p ON pv.product_id = p.id
                 LEFT JOIN colors c ON pv.color_id = c.id
                 LEFT JOIN sizes s ON pv.size_id = s.id
                 WHERE p.code = ? OR p.name LIKE ? OR pv.sku LIKE ? LIMIT 1",
                [$rawCode, "%{$rawCode}%", "%{$rawCode}%"]
            );
        }

        if ($foundVariant) {
            $stock = 0.0;
            $websiteChannelIds = $this->getWebsiteChannelIds();
            foreach ($websiteChannelIds as $websiteChannelId) {
                $stock += (float)StockService::getBalance((int)$foundVariant['id'], 'CHANNEL', (int)$websiteChannelId, 'AVAILABLE');
            }
            $isAggregateStock = $this->isAggregateStockVariant((int)$foundVariant['id']);

            if (($_REQUEST['available_only'] ?? '') === '1') {
                if ($stock < 1) {
                    echo json_encode(['success' => false, 'message' => 'This product has no available Website stock.']);
                    return;
                }

                if (!$isAggregateStock) {
                    $pieceTags = $this->resolveChannelPieceTags(
                        (int)($websiteChannelIds[0] ?? 0),
                        (int)$foundVariant['id'],
                        $websiteChannelIds,
                        'WEBSITE'
                    )['available'];
                    $availableTagKeys = array_map(
                        static fn($tag) => strtoupper(trim((string)$tag)),
                        $pieceTags
                    );
                    $scannedTagKeys = array_unique([
                        strtoupper(trim((string)$matchedTagCode)),
                        strtoupper(trim((string)$extractedTag)),
                    ]);
                    if (!array_intersect($scannedTagKeys, $availableTagKeys)) {
                        echo json_encode(['success' => false, 'message' => 'This QR code is not available Website stock. Scan an available product QR code.']);
                        return;
                    }
                }
            }

            echo json_encode([
                'success'       => true,
                'tag_code'      => $matchedTagCode,
                'variant'       => [
                    'id'            => (int)$foundVariant['id'],
                    'variant_id'    => (int)$foundVariant['id'],
                    'product_id'    => (int)$foundVariant['product_id'],
                    'product_name'  => $foundVariant['product_name'],
                    'product_code'  => $foundVariant['product_code'],
                    'sku'           => $foundVariant['sku'],
                    'color_name'    => $foundVariant['color_name'] ?? 'Standard',
                    'size_name'     => $foundVariant['size_name'] ?? 'Free Size',
                    'mrp'           => (float)$foundVariant['mrp'],
                    'price'         => (float)($foundVariant['selling_price'] ?: $foundVariant['mrp']),
                    'stock'         => $stock,
                    'aggregate_stock' => $isAggregateStock,
                ]
            ]);
            return;
        }

        echo json_encode(['success' => false, 'message' => "No product variant found matching scan: {$rawCode}"]);
    }

    public function processWebsiteReturn(): void {
        if (!hasPermission('website_returns', 'create') && !hasPermission('website_returns', 'process')) {
            Session::flash('error', 'Access denied. You do not have permission to process Website returns.');
            Response::redirect(url('warehouse/website-orders/returns'));
            return;
        }
        CSRF::verify();

        $saleId = (int)($_POST['sale_id'] ?? 0);
        $returnType = strtoupper(trim($_POST['return_type'] ?? 'RETURN'));
        $reason = trim($_POST['reason'] ?? 'Website Customer Request');
        $refundMethod = strtoupper(trim($_POST['refund_mode'] ?? $_POST['refund_method'] ?? 'UPI'));
        $items = $_POST['items'] ?? [];

        if (!$saleId || empty($items) || !is_array($items) || !in_array($returnType, ['RETURN', 'EXCHANGE_SAME'], true)) {
            Session::flash('error', 'Invalid Website return request payload. Please select items to return.');
            Response::redirect(url('warehouse/website-orders/returns'));
            return;
        }

        $sale = Database::fetchOne("SELECT * FROM sales WHERE id = ?", [$saleId]);
        if (!$sale) {
            Session::flash('error', 'Website sale record not found.');
            Response::redirect(url('warehouse/website-orders/returns'));
            return;
        }

        $saleChannel = strtoupper((string)($sale['sales_channel'] ?? ''));
        $saleType = strtoupper((string)($sale['sale_type'] ?? ''));
        $saleNotes = (string)($sale['notes'] ?? '');
        if (!in_array($saleChannel, ['WEBSITE'], true)
            && !in_array($saleType, ['WEBSITE'], true)
            && stripos($saleNotes, 'website') === false
            && !str_starts_with(strtoupper((string)($sale['invoice_number'] ?? '')), 'WEB-')) {
            Session::flash('error', 'Only Website orders can be processed in this return module.');
            Response::redirect(url('warehouse/website-orders/returns'));
            return;
        }

        $existingInvoiceReturn = Database::fetchOne(
            "SELECT return_number FROM returns WHERE sale_id = ? AND source_type = 'WEBSITE' ORDER BY id DESC LIMIT 1",
            [$saleId]
        );
        if ($existingInvoiceReturn) {
            Session::flash('error', "Invoice {$sale['invoice_number']} was already returned/exchanged under {$existingInvoiceReturn['return_number']}.");
            Response::redirect(url('warehouse/website-orders/returns'));
            return;
        }

        $chan = $this->getChannel();
        $channelId = (int)$chan['id'];
        $websiteChannelIds = $this->getWebsiteChannelIds();
        $validatedItems = [];
        $replacementQtyByVariant = [];
        $replacementStockPlan = [];
        $replacementStockDispatched = [];
        $replacementTagsSeen = [];
        $availableReplacementTagsByVariant = [];
        $totalReturnVal = 0.0;
        $totalReplacementVal = 0.0;

        foreach ($items as $k => $itm) {
            if (!is_array($itm)) {
                Session::flash('error', 'Invalid Website return item data.');
                Response::redirect(url('warehouse/website-orders/returns'));
                return;
            }
            $qtyInput = $itm['qty'] ?? $itm['quantity'] ?? null;
            $qty = filter_var($qtyInput, FILTER_VALIDATE_INT);
            if ($qty === false || $qty <= 0) {
                Session::flash('error', 'Return quantities must be positive whole numbers.');
                Response::redirect(url('warehouse/website-orders/returns'));
                return;
            }

            $saleItemId = is_numeric($k) ? (int)$k : (int)($itm['sale_item_id'] ?? 0);
            if ($saleItemId <= 0 || isset($validatedItems[$saleItemId])) {
                Session::flash('error', 'Invalid or duplicate sale item selected for return.');
                Response::redirect(url('warehouse/website-orders/returns'));
                return;
            }

            $saleItem = Database::fetchOne(
                "SELECT si.id, si.variant_id, si.quantity, si.unit_price, si.scanned_qr_codes,
                        si.qr_code
                 FROM sale_items si
                 WHERE si.id = ? AND si.sale_id = ? LIMIT 1",
                [$saleItemId, $saleId]
            );
            if (!$saleItem) {
                Session::flash('error', 'Selected item does not belong to this Website invoice.');
                Response::redirect(url('warehouse/website-orders/returns'));
                return;
            }

            $returned = Database::fetchOne(
                "SELECT COALESCE(SUM(quantity), 0) as total FROM return_items WHERE sale_item_id = ?",
                [$saleItemId]
            );
            $remainingQty = max(0, (int)$saleItem['quantity'] - (int)($returned['total'] ?? 0));
            if ($qty > $remainingQty) {
                Session::flash('error', "This item from {$sale['invoice_number']} has only {$remainingQty} piece(s) remaining for return.");
                Response::redirect(url('warehouse/website-orders/returns'));
                return;
            }

            $condition = strtoupper(trim($itm['condition'] ?? $itm['item_condition'] ?? 'GOOD'));
            if (!in_array($condition, ['GOOD', 'DAMAGED', 'DEFECTIVE'], true)) {
                Session::flash('error', 'Invalid returned item condition.');
                Response::redirect(url('warehouse/website-orders/returns'));
                return;
            }

            $unitPrice = (float)$saleItem['unit_price'];
            $pieceTags = $itm['piece_tags'] ?? [];
            if (!is_array($pieceTags)) {
                $pieceTags = array_filter(array_map('trim', explode(',', (string)$pieceTags)));
            }
            $pieceTags = array_values(array_unique(array_filter(array_map('trim', $pieceTags))));
            $soldTagsRaw = (string)($saleItem['scanned_qr_codes'] ?? '');
            $decodedSoldTags = json_decode($soldTagsRaw, true);
            if (is_array($decodedSoldTags)) {
                $soldTags = array_map(static function ($tag) {
                    return is_array($tag) ? (string)($tag['tag_code'] ?? $tag['qr_code'] ?? '') : (string)$tag;
                }, $decodedSoldTags);
            } else {
                $soldTags = explode(',', $soldTagsRaw);
            }
            $soldTags = array_values(array_unique(array_filter(array_map(
                static fn($tag) => strtoupper(trim((string)$tag)),
                $soldTags
            ))));
            if (!empty($soldTags)) {
                foreach ($pieceTags as $pieceTag) {
                    if (!in_array(strtoupper($pieceTag), $soldTags, true)) {
                        Session::flash('error', "Piece tag {$pieceTag} was not billed on this Website invoice.");
                        Response::redirect(url('warehouse/website-orders/returns'));
                        return;
                    }
                }
            }

            $validatedItem = [
                'sale_item_id' => $saleItemId,
                'variant_id' => (int)$saleItem['variant_id'],
                'qty' => $qty,
                'condition' => $condition,
                'unit_price' => $unitPrice,
                'piece_tags' => $pieceTags,
            ];
            if ($returnType === 'EXCHANGE_SAME') {
                $swapVariantId = (int)($itm['exchange_variant_id'] ?? 0);
                $replacementTag = trim((string)($itm['replacement_tag'] ?? ''));
                if ($swapVariantId <= 0 || $replacementTag === '') {
                    Session::flash('error', 'Scan a valid Website-stock replacement QR/SKU for every exchanged item.');
                    Response::redirect(url('warehouse/website-orders/returns'));
                    return;
                }

                $replacement = Database::fetchOne(
                    "SELECT id, product_id, sku, qr_code, selling_price, mrp, is_active
                     FROM product_variants WHERE id = ? LIMIT 1",
                    [$swapVariantId]
                );
                if (!$replacement || (int)$replacement['is_active'] !== 1) {
                    Session::flash('error', 'The replacement must be an active Website inventory product.');
                    Response::redirect(url('warehouse/website-orders/returns'));
                    return;
                }

                $normalizedTag = strtoupper($replacementTag);
                $isAggregateReplacement = $this->isAggregateStockVariant($swapVariantId);
                if (!$isAggregateReplacement && $qty !== 1) {
                    Session::flash('error', 'Normal Website products must be exchanged one scanned QR piece at a time.');
                    Response::redirect(url('warehouse/website-orders/returns'));
                    return;
                }
                $isUniquePieceTag = false;
                if ($isAggregateReplacement) {
                    $validReplacementTag = $normalizedTag === strtoupper((string)$replacement['sku'])
                        || ($replacement['qr_code'] && $normalizedTag === strtoupper((string)$replacement['qr_code']));
                } else {
                    if (!array_key_exists($swapVariantId, $availableReplacementTagsByVariant)) {
                        $availableReplacementTagsByVariant[$swapVariantId] = $this->resolveChannelPieceTags(
                            (int)($websiteChannelIds[0] ?? 0),
                            $swapVariantId,
                            $websiteChannelIds,
                            'WEBSITE'
                        )['available'];
                    }
                    $availableTagKeys = array_map(
                        static fn($tag) => strtoupper(trim((string)$tag)),
                        $availableReplacementTagsByVariant[$swapVariantId]
                    );
                    $validReplacementTag = in_array($normalizedTag, $availableTagKeys, true);
                    $isUniquePieceTag = $validReplacementTag;
                }
                if ($isAggregateReplacement && !$validReplacementTag) {
                    $channelPlaceholders = implode(',', array_fill(0, count($websiteChannelIds), '?'));
                    $allocationRows = Database::fetchAll(
                        "SELECT ai.selected_qr_codes FROM allocation_items ai
                         JOIN warehouse_allocations wa ON wa.id = ai.allocation_id
                         WHERE ai.variant_id = ? AND ai.selected_qr_codes IS NOT NULL AND ai.selected_qr_codes != ''
                           AND (wa.channel_id IN ({$channelPlaceholders})
                                OR (wa.destination_type = 'CHANNEL' AND wa.destination_id IN ({$channelPlaceholders})))",
                        array_merge([$swapVariantId], $websiteChannelIds, $websiteChannelIds)
                    );
                    foreach ($allocationRows as $allocationRow) {
                        $rawTags = (string)$allocationRow['selected_qr_codes'];
                        $decodedTags = json_decode($rawTags, true);
                        if (!is_array($decodedTags)) {
                            $decodedTags = explode(',', $rawTags);
                        }
                        foreach ($decodedTags as $tag) {
                            $tag = is_array($tag) ? ($tag['tag_code'] ?? $tag['qr_code'] ?? '') : $tag;
                            if ($normalizedTag === strtoupper(trim((string)$tag))) {
                                $validReplacementTag = true;
                                $isUniquePieceTag = true;
                                break 2;
                            }
                        }
                    }
                }
                if (!$validReplacementTag) {
                    Session::flash('error', 'The scanned replacement tag does not belong to the selected replacement variant.');
                    Response::redirect(url('warehouse/website-orders/returns'));
                    return;
                }
                if ($isUniquePieceTag && isset($replacementTagsSeen[$normalizedTag])) {
                    Session::flash('error', 'The same replacement QR/SKU cannot be used more than once in an exchange.');
                    Response::redirect(url('warehouse/website-orders/returns'));
                    return;
                }
                if ($isUniquePieceTag) {
                    $replacementTagsSeen[$normalizedTag] = true;
                }

                $replacementPrice = (float)($replacement['selling_price'] > 0 ? $replacement['selling_price'] : $replacement['mrp']);
                $validatedItem['exchange_variant_id'] = $swapVariantId;
                $validatedItem['replacement_tag'] = $replacementTag;
                $replacementQtyByVariant[$swapVariantId] = ($replacementQtyByVariant[$swapVariantId] ?? 0) + $qty;
                $totalReplacementVal += $qty * $replacementPrice;
            }

            $totalReturnVal += $qty * $unitPrice;
            $validatedItems[$saleItemId] = $validatedItem;
        }

        if (empty($validatedItems)) {
            Session::flash('error', 'Select at least one returnable Website invoice item.');
            Response::redirect(url('warehouse/website-orders/returns'));
            return;
        }

        foreach ($replacementQtyByVariant as $variantId => $requestedQty) {
            $remainingQty = $requestedQty;
            $stockPlan = [];
            foreach ($websiteChannelIds as $stockChannelId) {
                $available = (int)StockService::getBalance((int)$variantId, 'CHANNEL', (int)$stockChannelId, 'AVAILABLE');
                $allocated = min($available, $remainingQty);
                if ($allocated > 0) {
                    $stockPlan[(int)$stockChannelId] = $allocated;
                    $remainingQty -= $allocated;
                }
                if ($remainingQty === 0) {
                    break;
                }
            }
            if ($remainingQty > 0) {
                $replacement = Database::fetchOne("SELECT sku FROM product_variants WHERE id = ?", [$variantId]);
                Session::flash('error', "Replacement " . ($replacement['sku'] ?? ('#' . $variantId)) . " has insufficient Website stock. Requested: {$requestedQty}.");
                Response::redirect(url('warehouse/website-orders/returns'));
                return;
            }
            $replacementStockPlan[(int)$variantId] = $stockPlan;
        }

        $differenceAmount = $totalReplacementVal - $totalReturnVal;

        $dbReturnType = ($returnType === 'RETURN') ? 'RETURN' : 'EXCHANGE';
        $validRefundModes = ['CASH', 'UPI', 'CARD', 'ONLINE', 'CASH_UPI', 'CREDIT_NOTE'];
        if (!in_array($refundMethod, $validRefundModes, true)) {
            Session::flash('error', 'Select a valid refund payment mode.');
            Response::redirect(url('warehouse/website-orders/returns'));
            return;
        }
        $dbRefundMode = $refundMethod;
        $refundAmount = $differenceAmount < 0 ? abs($differenceAmount) : ($returnType === 'RETURN' ? $totalReturnVal : 0.0);
        $userId = Auth::id() ?? Session::userId() ?? 1;
        $items = $validatedItems;

        try {
            if (!Database::beginTransaction()) {
                throw new RuntimeException('Unable to start Website return transaction.');
            }

            Database::fetchOne("SELECT id FROM sales WHERE id = ? FOR UPDATE", [$saleId]);
            $lockedInvoiceReturn = Database::fetchOne(
                "SELECT return_number FROM returns WHERE sale_id = ? AND source_type = 'WEBSITE' ORDER BY id DESC LIMIT 1",
                [$saleId]
            );
            if ($lockedInvoiceReturn) {
                Database::rollback();
                Session::flash('error', "Invoice {$sale['invoice_number']} was already returned/exchanged under {$lockedInvoiceReturn['return_number']}.");
                Response::redirect(url('warehouse/website-orders/returns'));
                return;
            }

            $returnNumber = 'RET-WEB-' . date('Ymd') . '-' . mt_rand(1000, 9999);
            Database::execute(
                "INSERT INTO returns (return_number, source_type, sale_id, customer_id, return_type, reason, inspection_status,
                                     refund_amount, refund_mode, refund_status, total_amount, status, processed_by, approved_by, notes, created_at, updated_at)
                 VALUES (?, 'WEBSITE', ?, ?, ?, ?, 'APPROVED', ?, ?, 'COMPLETED', ?, ?, ?, ?, ?, NOW(), NOW())",
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
                        StockService::increase($varId, 'CHANNEL', $channelId, 'AVAILABLE', (float)$qty, 'RETURN_REINWARD', 'returns', $returnId, $userId, "Website Return Good Stock Re-Inward");
                    } else {
                        StockService::increase($varId, 'CHANNEL', $channelId, 'DAMAGED', (float)$qty, 'RETURN_DAMAGED', 'returns', $returnId, $userId, "Website Return Damaged Stock");
                    }
                }
            }

            if ($returnType === 'EXCHANGE_SAME') {
                $exchNumber = 'EXC-WEB-' . date('Ymd') . '-' . mt_rand(1000, 9999);
                $payAction = $differenceAmount > 0 ? 'COLLECT' : ($differenceAmount < 0 ? 'REFUND' : 'NONE');
                Database::execute(
                    "INSERT INTO exchanges (exchange_number, return_id, source_type, sale_id, original_amount, new_amount, difference_amount, settlement_mode, payment_action, status, payment_status, notes, created_at, updated_at)
                     VALUES (?, ?, 'WEBSITE', ?, ?, ?, ?, ?, ?, 'COMPLETED', 'PAID', ?, NOW(), NOW())",
                    [
                        $exchNumber,
                        $returnId,
                        $saleId,
                        $totalReturnVal,
                        $totalReplacementVal,
                        $differenceAmount,
                        $dbRefundMode,
                        $payAction,
                        'Website Product Exchange'
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
                        $swapVarId = (int)($itm['exchange_variant_id'] ?? 0);
                        $swapQty = (int)($itm['qty'] ?? $itm['quantity'] ?? 0);
                        $swapTag = trim($itm['replacement_tag'] ?? '');
                        $pvRow = Database::fetchOne("SELECT selling_price, mrp FROM product_variants WHERE id = ?", [$swapVarId]);
                        $p = $pvRow ? (float)($pvRow['selling_price'] > 0 ? $pvRow['selling_price'] : $pvRow['mrp']) : 0.0;

                        if ($swapVarId > 0 && $swapQty > 0) {
                            Database::execute(
                                "INSERT INTO exchange_items (exchange_id, item_type, variant_id, quantity, unit_price, total_amount, piece_tag)
                                 VALUES (?, 'NEW', ?, ?, ?, ?, ?)",
                                [$exchangeId, $swapVarId, $swapQty, $p, $swapQty * $p, $swapTag ?: null]
                            );
                            $remainingSwapQty = $swapQty;
                            foreach ($replacementStockPlan[$swapVarId] ?? [] as $stockChannelId => $plannedQty) {
                                $alreadyDispatched = $replacementStockDispatched[$swapVarId][$stockChannelId] ?? 0;
                                $dispatchQty = min($remainingSwapQty, $plannedQty - $alreadyDispatched);
                                if ($dispatchQty <= 0) {
                                    continue;
                                }
                                StockService::decrease(
                                    $swapVarId,
                                    'CHANNEL',
                                    (int)$stockChannelId,
                                    'AVAILABLE',
                                    (float)$dispatchQty,
                                    'EXCHANGE_DISPATCH',
                                    'exchanges',
                                    $returnId,
                                    $userId,
                                    'Website Same Product Swap Dispatch'
                                );
                                $replacementStockDispatched[$swapVarId][$stockChannelId] = $alreadyDispatched + $dispatchQty;
                                $remainingSwapQty -= $dispatchQty;
                                if ($remainingSwapQty === 0) {
                                    break;
                                }
                            }
                            if ($remainingSwapQty > 0) {
                                throw new RuntimeException('Website replacement stock changed while the exchange was being processed.');
                            }
                        }
                    }
                }
            }

            if (!Database::commit()) {
                throw new RuntimeException('Unable to commit Website return transaction.');
            }
        } catch (Throwable $e) {
            Database::rollback();
            error_log('Website return processing failed: ' . $e->getMessage());
            Session::flash('error', 'Website return/exchange could not be completed. No return or stock changes were saved.');
            Response::redirect(url('warehouse/website-orders/returns'));
            return;
        }

        Session::flash('success', "Website Return/Exchange #{$returnNumber} processed successfully!");
        Response::redirect(url('warehouse/website-orders/returns'));
    }

    public function websiteReturnInvoice(int $id): void {
        if (!hasPermission('website_returns', 'print')) {
            Session::flash('error', 'Access denied. You do not have permission to print Website return invoices.');
            Response::redirect(url('warehouse/website-orders/returns'));
            return;
        }
        $return = Database::fetchOne(
            "SELECT r.*, 
                    s.invoice_number as sale_invoice,
                    s.invoice_number,
                    s.created_at as sale_date,
                    COALESCE(c.name, s.customer_name, 'Website Customer') as customer_name, 
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
             LEFT JOIN users u ON r.processed_by = u.id
             WHERE r.id = ?",
            [$id]
        );

        if (!$return) {
            Session::flash('error', 'Return record not found.');
            Response::redirect(url('warehouse/website-orders/returns'));
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
            [$id]
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

        Response::view('website_orders.return_invoice', [
            'pageTitle'     => 'Website Return Invoice #' . $return['return_number'],
            'return'        => $return,
            'items'         => $items,
            'exchangeItems' => $exchangeItems
        ]);
    }

    public function websiteReturnView(int $id): void {
        $this->websiteReturnInvoice($id);
    }

    /**
     * Website Customer Directory & Statistics
     */
    public function websiteCustomers(): void {
        $search = trim($_GET['search'] ?? '');
        $where = ["(s.sales_channel = 'WEBSITE' OR s.notes LIKE '%Website%')"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
            $sWild = "%{$search}%";
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
        }

        $whereClause = implode(' AND ', $where);

        $customers = Database::fetchAll(
            "SELECT c.*, 
                    COUNT(DISTINCT s.id) as total_orders,
                    COALESCE(SUM(s.total_amount), 0) as total_spent,
                    MAX(s.created_at) as last_order_date
             FROM customers c
             JOIN sales s ON s.customer_id = c.id
             WHERE {$whereClause}
             GROUP BY c.id
             ORDER BY total_spent DESC",
            $params
        );

        Response::view('website_orders.customers', [
            'pageTitle'    => 'Website Customers Directory',
            'activeModule' => 'website',
            'activeMenu'   => 'wh-website-customers',
            'breadcrumb'   => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Customers']
            ],
            'customers'    => $customers,
            'search'       => $search
        ]);
    }

    // ==========================================
    // WEBSITE CHANNEL EXPENSE MANAGEMENT & OUTFLOW
    // ==========================================

    public function expensesIndex(): void {
        if (!hasPermission('website_expenses', 'view')) {
            Session::flash('error', 'Access denied. You do not have permission to view Website expenses.');
            Response::redirect(url('warehouse/website-orders'));
            return;
        }
        require_once BASE_PATH . '/app/Services/ExpenseService.php';
        $chan = $this->getChannel();
        $channelId = (int)$chan['id'];

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $category = $_GET['category'] ?? 'all';
        $paymentMethod = $_GET['payment_method'] ?? 'all';
        $search = trim($_GET['search'] ?? '');

        $filters = [
            'search'         => $search,
            'category'       => $category,
            'payment_method' => $paymentMethod,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'limit'          => 150
        ];

        $expenses = ExpenseService::getExpenses('WEBSITE', $channelId, $filters);
        $summary = ExpenseService::getSummary('WEBSITE', $channelId, $startDate, $endDate);
        $categories = ExpenseService::getCategories('WEBSITE');

        Response::view('website_orders.expenses.index', [
            'pageTitle'     => 'Website Operations & Channel Expenses',
            'activeModule'  => 'website',
            'activeMenu'    => 'wh-website-expenses',
            'breadcrumb'    => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Channel Expenses'],
            ],
            'channel'       => $chan,
            'expenses'      => $expenses,
            'summary'       => $summary,
            'categories'    => $categories,
            'startDate'     => $startDate,
            'endDate'       => $endDate,
            'filters'       => $filters,
        ]);
    }

    public function createExpense(): void {
        if (!hasPermission('website_expenses', 'create')) {
            Session::flash('error', 'Access denied. You do not have permission to create Website expenses.');
            Response::redirect(url('warehouse/website-orders/expenses'));
            return;
        }
        $this->expensesIndex();
    }

    public function storeExpense(): void {
        if (!hasPermission('website_expenses', 'create')) {
            Session::flash('error', 'Access denied. You do not have permission to record Website expenses.');
            Response::redirect(url('warehouse/website-orders/expenses'));
            return;
        }
        require_once BASE_PATH . '/app/Services/ExpenseService.php';
        $chan = $this->getChannel();
        $userId = Session::userId() ?? 1;

        $payload = $_POST;
        $payload['location_type'] = 'WEBSITE';
        $payload['location_id'] = (int)$chan['id'];
        $payload['location_name'] = $chan['name'] ?? 'Website Channel';

        try {
            $res = ExpenseService::recordExpense($payload, $userId);
            Session::flash('success', "Website Channel Expense Voucher #{$res['expense_number']} for ₹" . number_format($res['amount'], 2) . " recorded successfully.");
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        Response::redirect(url('warehouse/website-orders/expenses'));
    }

    public function printExpenseVoucher(int $id): void {
        if (!hasPermission('website_expenses', 'print') && !hasPermission('website_expenses', 'voucher')) {
            Session::flash('error', 'Access denied. You do not have permission to print Website expense vouchers.');
            Response::redirect(url('warehouse/website-orders/expenses'));
            return;
        }
        require_once BASE_PATH . '/app/Services/ExpenseService.php';
        $expense = ExpenseService::getVoucherDetails($id);
        if (!$expense) {
            die("Website expense record not found.");
        }

        Response::viewOnly('website_orders.expenses.voucher', [
            'expense' => $expense,
            'module'  => 'website'
        ]);
    }

    public function deleteExpense(int $id): void {
        if (!hasPermission('website_expenses', 'delete')) {
            Session::flash('error', 'Access denied. You do not have permission to delete Website expenses.');
            Response::redirect(url('warehouse/website-orders/expenses'));
            return;
        }

        CSRF::verify();
        require_once BASE_PATH . '/app/Services/ExpenseService.php';
        $expense = ExpenseService::getVoucherDetails($id);
        $channel = $this->getChannel();
        if (!$expense || ($expense['location_type'] ?? '') !== 'WEBSITE' || (int)($expense['location_id'] ?? 0) !== (int)$channel['id']) {
            Session::flash('error', 'Website expense record not found.');
            Response::redirect(url('warehouse/website-orders/expenses'));
            return;
        }

        $userId = Session::userId() ?? 1;
        if (!ExpenseService::deleteExpense($id, $userId)) {
            Session::flash('error', 'Website expense could not be deleted.');
            Response::redirect(url('warehouse/website-orders/expenses'));
            return;
        }
        Session::flash('success', "Expense record deleted successfully.");
        Response::redirect(url('warehouse/website-orders/expenses'));
    }

    /**
     * =========================================================================
     * RETURN TO WAREHOUSE (WEBSITE CHANNEL -> CENTRAL WAREHOUSE DISPATCH)
     * =========================================================================
     */

    /**
     * Website Returns to Warehouse List & Dispatch Dashboard
     */
    public function returnToWarehouseList(): void {
        if (!hasPermission('website_return_to_wh', 'view')) {
            Session::flash('error', 'Access denied. You do not have permission to view Website warehouse returns.');
            Response::redirect(url('warehouse/website-orders'));
            return;
        }
        $chan = $this->getChannel();
        $chanId = (int)$chan['id'];

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'status' => trim($_GET['status'] ?? 'ALL'),
        ];

        $data = $this->getWebsiteWarehouseReturns($chanId, $filters);

        Response::view('website_orders.return_to_warehouse.index', [
            'pageTitle'    => 'Website Return to Warehouse Consignments',
            'activeModule' => 'website',
            'activeMenu'   => 'website-return-to-wh',
            'breadcrumb'   => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Return to Warehouse'],
            ],
            'channel'      => $chan,
            'returns'      => $data['returns'],
            'kpis'         => $data['kpis'],
            'filters'      => $filters,
        ]);
    }

    /**
     * Create New Return to Warehouse Form for Website Channel
     */
    public function createReturnToWarehouse(): void {
        if (!hasPermission('website_return_to_wh.create')) {
            Session::flash('error', 'Access denied. You do not have permission to create Website warehouse returns.');
            Response::redirect(url('warehouse/website-orders/return-to-warehouse'));
            return;
        }
        $chan = $this->getChannel();
        $chanId = (int)$chan['id'];
        $chanIds = $this->getWebsiteChannelIds();
        $placeholders = implode(',', array_fill(0, count($chanIds), '?'));

        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : [];

        // Fetch inventory items available in Website channel (both AVAILABLE and DAMAGED stock across all website channel IDs)
        $inventory = Database::fetchAll(
            "SELECT sb.variant_id, sb.stock_state, SUM(sb.quantity) as quantity,
                    pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.image_url as variant_image,
                    pv.qr_type, pv.pack_quantity,
                    p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                    COALESCE(pv.is_complementary, p.is_complementary, 0) as is_complementary,
                    COALESCE(wc.category_type, 'NORMAL') as category_type,
                    p.category_code, p.subcategory_code, p.sub_subcategory_code, p.material, p.hsn_code, p.description,
                    c.id as category_id, c.name as category_name,
                    sc.id as subcategory_id, sc.name as subcategory_name,
                    ssc.id as sub_subcategory_id, ssc.name as sub_subcategory_name,
                    sz.name as size_name, sz.code as size_code,
                    cl.name as color_name, cl.hex_code as color_hex
             FROM stock_balances sb
             JOIN product_variants pv ON sb.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
             LEFT JOIN sub_subcategories ssc ON p.sub_subcategory_id = ssc.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors cl ON pv.color_id = cl.id
             WHERE sb.location_type = 'CHANNEL' AND sb.location_id IN ($placeholders)
               AND sb.concern_id = " . (int)currentConcernId() . "
             GROUP BY sb.variant_id, sb.stock_state
             HAVING quantity > 0
             ORDER BY c.name ASC, p.name ASC, pv.sku ASC, sb.stock_state ASC",
            $chanIds
        );

        // Fetch piece tags mapped to variants for this channel
        $allocTags = Database::fetchAll(
            "SELECT ai.variant_id, ai.selected_qr_codes
             FROM allocation_items ai
             JOIN warehouse_allocations wa ON ai.allocation_id = wa.id
             WHERE wa.destination_type = 'CHANNEL' AND wa.destination_id IN ($placeholders)
               AND ai.selected_qr_codes IS NOT NULL AND ai.selected_qr_codes != ''",
            $chanIds
        );

        $variantTagsMap = [];
        foreach ($allocTags as $at) {
            $vid = (int)$at['variant_id'];
            if (!isset($variantTagsMap[$vid])) {
                $variantTagsMap[$vid] = [];
            }
            $tags = explode(',', $at['selected_qr_codes']);
            foreach ($tags as $t) {
                $trimmed = trim($t);
                if ($trimmed && !in_array($trimmed, $variantTagsMap[$vid])) {
                    $variantTagsMap[$vid][] = $trimmed;
                }
            }
        }

        $categories = [];
        foreach ($inventory as &$item) {
            $item['image'] = !empty($item['variant_image']) ? $item['variant_image'] : ($item['product_image'] ?? null);
            $vid = (int)$item['variant_id'];
            $pieceRes = $this->resolveChannelPieceTags($chanId, $vid, $chanIds, 'WEBSITE');
            $item['piece_tags'] = ($item['stock_state'] === 'DAMAGED') ? $pieceRes['damaged'] : $pieceRes['available'];
            $item['piece_tags_all'] = $pieceRes['all'];

            $catName = $item['category_name'] ?? 'General';
            if (!in_array($catName, $categories)) {
                $categories[] = $catName;
            }
        }

        Response::view('website_orders.return_to_warehouse.create', [
            'pageTitle'    => 'Create Return to Warehouse Dispatch',
            'activeModule' => 'website',
            'activeMenu'   => 'website-return-to-wh',
            'breadcrumb'   => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Return to Warehouse', 'url' => url('warehouse/website-orders/return-to-warehouse')],
                ['label' => 'New Return Dispatch'],
            ],
            'channel'      => $chan,
            'warehouses'   => $warehouses,
            'inventory'    => $inventory,
            'categories'   => $categories,
        ]);
    }

    /**
     * Store and Process Return to Warehouse for Website Channel
     */
    public function storeReturnToWarehouse(): void {
        if (!hasPermission('website_return_to_wh.create')) {
            Session::flash('error', 'Access denied. You do not have permission to create Website warehouse returns.');
            Response::redirect(url('warehouse/website-orders/return-to-warehouse'));
            return;
        }
        $chan = $this->getChannel();
        $chanId = (int)$chan['id'];
        $userId = Session::userId() ?? 1;

        try {
            $result = $this->createWebsiteWarehouseReturn($chanId, $_POST, $userId);
            $returnNum = is_array($result) ? $result['return_number'] : $result;
            $returnId = is_array($result) ? $result['id'] : null;

            Session::setFlash('success', "Return to Warehouse #$returnNum created successfully and dispatched! Channel stock updated.");

            if ($returnId) {
                Response::redirect(url('warehouse/website-orders/return-to-warehouse/view/' . $returnId . '?outward_success=1'));
            } else {
                Response::redirect(url('warehouse/website-orders/return-to-warehouse'));
            }
        } catch (Exception $e) {
            Session::setFlash('error', "Failed to create return to warehouse: " . $e->getMessage());
            Response::redirect(url('warehouse/website-orders/return-to-warehouse/create'));
        }
    }

    /**
     * View Return to Warehouse Details Page for Website Channel
     */
    public function viewReturnToWarehouse(string $id): void {
        if (!hasPermission('website_return_to_wh', 'view')) {
            Session::flash('error', 'Access denied. You do not have permission to view Website warehouse returns.');
            Response::redirect(url('warehouse/website-orders'));
            return;
        }
        $details = $this->getWebsiteWarehouseReturnDetails((int)$id);
        if (!$details) {
            Session::setFlash('error', "Return consignment #$id not found.");
            Response::redirect(url('warehouse/website-orders/return-to-warehouse'));
            return;
        }

        Response::view('website_orders.return_to_warehouse.view', array_merge($details, [
            'pageTitle'    => 'Return #' . $details['return']['return_number'] . ' Manifest',
            'activeModule' => 'website',
            'activeMenu'   => 'website-return-to-wh',
            'breadcrumb'   => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Return to Warehouse', 'url' => url('warehouse/website-orders/return-to-warehouse')],
                ['label' => $details['return']['return_number']],
            ],
        ]));
    }

    /**
     * Print Delivery Gate Pass / Return Challan for Website Return to Warehouse
     */
    public function printReturnToWarehouseGatePass(string $id): void {
        if (!hasPermission('website_return_to_wh.gatepass')) {
            Session::flash('error', 'Access denied. You do not have permission to print gate pass.');
            Response::redirect(url('warehouse/website-orders/return-to-warehouse'));
            return;
        }
        $details = $this->getWebsiteWarehouseReturnDetails((int)$id);
        if (!$details) {
            Session::setFlash('error', "Return consignment #$id not found.");
            Response::redirect(url('warehouse/website-orders/return-to-warehouse'));
            return;
        }

        Response::viewOnly('website_orders.return_to_warehouse.gatepass', $details);
    }

    /**
     * Print Carton QR Shipping Label for Website Return to Warehouse
     */
    public function printReturnToWarehouseLabel(string $id): void {
        if (!hasPermission('website_return_to_wh.print')) {
            Session::flash('error', 'Access denied. You do not have permission to print return labels.');
            Response::redirect(url('warehouse/website-orders/return-to-warehouse'));
            return;
        }
        $details = $this->getWebsiteWarehouseReturnDetails((int)$id);
        if (!$details) {
            Session::setFlash('error', "Return consignment #$id not found.");
            Response::redirect(url('warehouse/website-orders/return-to-warehouse'));
            return;
        }

        Response::viewOnly('website_orders.return_to_warehouse.label', $details);
    }

    /**
     * AJAX Product Detail Endpoint for Modal Inspection
     */
    public function ajaxProductDetail(string $id): void {
        $chan = $this->getChannel();
        $variantId = (int)$id;
        $stockState = $_GET['stock_state'] ?? 'AVAILABLE';
        $item = $this->getChannelInventoryItem((int)$chan['id'], $variantId, $stockState);

        if (!$item) {
            Response::json(['success' => false, 'message' => 'Product variant not found.'], 404);
            return;
        }

        Response::json(['success' => true, 'item' => $item]);
    }

    /**
     * Update Product Selling Price & MRP from Website Inventory
     */
    public function updateProductPrice(): void {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                  (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) ||
                  isset($_POST['is_ajax']) || isset($_GET['ajax']);

        if (!hasPermission('website_inventory.adjust')) {
            if ($isAjax) {
                Response::json(['success' => false, 'message' => 'Access denied. You do not have permission to adjust prices.'], 403);
                return;
            }
            Session::flash('error', 'Access denied. You do not have permission to adjust product prices.');
            Response::redirect(url('warehouse/website-orders/allocations'));
            return;
        }

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
        $notes = trim($_POST['notes'] ?? $_POST['revision_notes'] ?? 'Website Inventory price adjustment');

        if (empty($variantIds)) {
            if ($isAjax) {
                Response::json(['success' => false, 'message' => 'Please select at least one product variant.'], 400);
                return;
            }
            Session::setFlash('error', 'Please select at least one product variant.');
            Response::redirect(url('warehouse/website-orders/allocations'));
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
            Response::redirect(url('warehouse/website-orders/allocations'));
        } catch (Exception $e) {
            if ($isAjax) {
                Response::json(['success' => false, 'message' => $e->getMessage()], 400);
                return;
            }
            Session::setFlash('error', 'Failed to update price: ' . $e->getMessage());
            Response::redirect(url('warehouse/website-orders/allocations'));
        }
    }

    /**
     * Helper: Get Website Return Consignments & KPIs
     */
    private function getWebsiteWarehouseReturns(int $channelId, array $filters = []): array {
        $chanIds = $this->getWebsiteChannelIds();
        $placeholders = implode(',', array_fill(0, count($chanIds), '?'));

        $sql = "SELECT wr.*, 
                       w.name as warehouse_name, w.code as warehouse_code, w.address as warehouse_address,
                       u.full_name as inspected_by_name,
                       (SELECT COUNT(*) FROM warehouse_return_items WHERE warehouse_return_id = wr.id) as item_count,
                       (SELECT COALESCE(SUM(returned_qty), 0) FROM warehouse_return_items WHERE warehouse_return_id = wr.id) as total_returned_qty,
                       (SELECT COALESCE(SUM(good_qty), 0) FROM warehouse_return_items WHERE warehouse_return_id = wr.id) as total_good_qty,
                       (SELECT COALESCE(SUM(damaged_qty), 0) FROM warehouse_return_items WHERE warehouse_return_id = wr.id) as total_damaged_qty
                FROM warehouse_returns wr
                JOIN warehouses w ON wr.warehouse_id = w.id
                LEFT JOIN users u ON wr.inspected_by = u.id
                WHERE (wr.source_type = 'WEBSITE' OR (wr.source_type = 'CHANNEL' AND wr.source_id IN ($placeholders))) 
                  AND wr.source_id IN ($placeholders)";
        
        $params = array_merge($chanIds, $chanIds);

        if (!empty($filters['status']) && $filters['status'] !== 'ALL') {
            $sql .= " AND wr.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $sql .= " AND (wr.return_number LIKE ? OR wr.reason LIKE ? OR w.name LIKE ?)";
            $params = array_merge($params, [$search, $search, $search]);
        }

        $sql .= " ORDER BY wr.created_at DESC";

        $returns = Database::fetchAll($sql, $params);

        $kpiSql = "SELECT COUNT(*) as total_dispatches,
                          COALESCE(SUM((SELECT SUM(returned_qty) FROM warehouse_return_items WHERE warehouse_return_id = wr.id)), 0) as total_units_returned,
                          SUM(CASE WHEN wr.status IN ('RECEIVED', 'INSPECTING') THEN 1 ELSE 0 END) as in_transit_count,
                          SUM(CASE WHEN wr.status IN ('CLASSIFIED', 'COMPLETED') THEN 1 ELSE 0 END) as completed_count
                   FROM warehouse_returns wr
                   WHERE (wr.source_type = 'WEBSITE' OR (wr.source_type = 'CHANNEL' AND wr.source_id IN ($placeholders)))
                     AND wr.source_id IN ($placeholders)";
        $kpis = Database::fetchOne($kpiSql, array_merge($chanIds, $chanIds)) ?: [
            'total_dispatches'     => 0,
            'total_units_returned' => 0,
            'in_transit_count'     => 0,
            'completed_count'      => 0,
        ];

        return [
            'returns' => $returns,
            'kpis'    => $kpis,
        ];
    }

    /**
     * Helper: Create Website Warehouse Return and deduct Channel Stock
     */
    private function createWebsiteWarehouseReturn(int $channelId, array $payload, int $userId): array {
        $warehouseId = (int)($payload['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            throw new InvalidArgumentException("Please select a destination warehouse.");
        }
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        $assignedWarehouseId = function_exists('currentWarehouseId') ? currentWarehouseId() : null;
        $whExists = $concernId === null || $assignedWarehouseId === 0 || ($assignedWarehouseId !== null && $assignedWarehouseId !== $warehouseId)
            ? null
            : Database::fetchOne(
                "SELECT id FROM warehouses WHERE id = ? AND concern_id = ? AND is_active = 1",
                [$warehouseId, $concernId]
            );
        if (!$whExists) {
            throw new InvalidArgumentException("Selected destination warehouse is invalid or inactive.");
        }

        $reason = trim($payload['reason'] ?? '');
        if (empty($reason)) {
            throw new InvalidArgumentException("Please select a valid return reason.");
        }

        $items = $payload['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            throw new InvalidArgumentException("Please select at least one product item to return to the warehouse.");
        }

        $carrierName  = trim($payload['carrier_name'] ?? '');
        $vehicleNo    = strtoupper(trim($payload['vehicle_no'] ?? ''));
        $driverName   = trim($payload['driver_name'] ?? '');
        $driverPhone  = trim($payload['driver_phone'] ?? '');
        $trackingNo   = trim($payload['tracking_no'] ?? '');
        $cartonCount  = max(1, (int)($payload['carton_count'] ?? 1));
        $dispatchDate = trim($payload['dispatch_date'] ?? date('Y-m-d H:i:s'));
        $remarks      = trim($payload['remarks'] ?? ($payload['notes'] ?? ''));

        // Validate driver phone format if provided
        if ($driverPhone !== '') {
            $digitsOnly = preg_replace('/\D/', '', $driverPhone);
            if (strlen($digitsOnly) !== 10 || !preg_match('/^[6-9]\d{9}$/', $digitsOnly)) {
                throw new InvalidArgumentException("Driver phone number must be a valid 10-digit mobile number starting with 6, 7, 8, or 9.");
            }
            $driverPhone = $digitsOnly;
        }

        // Validate vehicle format if provided
        if ($vehicleNo !== '' && (strlen($vehicleNo) < 4 || !preg_match('/^[A-Z0-9 -]{4,20}$/', $vehicleNo))) {
            throw new InvalidArgumentException("Vehicle number must be at least 4 alphanumeric characters (e.g. TN-01-AB-1234).");
        }

        // Validate driver name if provided
        if ($driverName !== '' && (mb_strlen($driverName) < 2 || !preg_match('/^[a-zA-Z\s.]{2,60}$/', $driverName))) {
            throw new InvalidArgumentException("Driver name must contain at least 2 alphabetic characters (letters and spaces only).");
        }

        Database::beginTransaction();
        try {
            $today = date('Ymd');
            $prefix = 'RET-WEB-' . $today . '-';
            $last = Database::fetchOne(
                "SELECT return_number FROM warehouse_returns WHERE return_number LIKE ? ORDER BY id DESC LIMIT 1",
                [$prefix . '%']
            );
            $seq = 1;
            if ($last && !empty($last['return_number'])) {
                $parts = explode('-', $last['return_number']);
                $seq = (int)end($parts) + 1;
            }
            $returnNumber = sprintf('%s%04d', $prefix, $seq);

            $notesData = [
                'carrier_name'  => $carrierName ?: 'GRM Logistics / Handover',
                'vehicle_no'    => $vehicleNo,
                'driver_name'   => $driverName,
                'driver_phone'  => $driverPhone,
                'tracking_no'   => $trackingNo,
                'carton_count'  => max(1, $cartonCount),
                'dispatch_date' => $dispatchDate,
                'remarks'       => $remarks,
                'item_qr_codes' => []
            ];
            $serializedNotes = json_encode($notesData);

            Database::execute(
                "INSERT INTO warehouse_returns (return_number, source_type, source_id, warehouse_id, destination_warehouse_id, reason, status, notes, created_at, updated_at)
                 VALUES (?, 'WEBSITE', ?, ?, ?, ?, 'RECEIVED', ?, NOW(), NOW())",
                [$returnNumber, $channelId, $warehouseId, $warehouseId, $reason, $serializedNotes]
            );
            $returnId = (int)Database::lastInsertId();

            $processedCount = 0;
            $itemQrCodes = [];
            foreach ($items as $it) {
                $variantId = (int)($it['variant_id'] ?? 0);
                $qty = (int)($it['quantity'] ?? 0);
                $stockState = strtoupper($it['stock_state'] ?? 'AVAILABLE');

                if ($variantId <= 0 || $qty <= 0) continue;
                if (!in_array($stockState, ['AVAILABLE', 'DAMAGED'], true)) {
                    throw new InvalidArgumentException('Return stock pool must be Available or Damaged.');
                }

                $variantType = Database::fetchOne(
                    "SELECT COALESCE(wc.category_type, 'NORMAL') as category_type,
                            COALESCE(pv.is_complementary, p.is_complementary, 0) as is_complementary,
                            pv.qr_type, pv.pack_quantity
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
                     WHERE pv.id = ? LIMIT 1",
                    [$variantId]
                );
                if (!$variantType) {
                    throw new InvalidArgumentException("Product variant #{$variantId} was not found.");
                }
                $allowsMultiple = in_array(strtoupper((string)$variantType['category_type']), ['SERIES', 'COMPLEMENTARY'], true)
                    || !empty($variantType['is_complementary'])
                    || strtoupper((string)$variantType['qr_type']) === 'SERIES_BATCH'
                    || (int)($variantType['pack_quantity'] ?? 1) > 1;
                if (!$allowsMultiple && $qty !== 1) {
                    throw new RuntimeException("Normal product returns must be selected one piece at a time for SKU #{$variantId}.");
                }
                $classification = $stockState === 'DAMAGED' ? 'DAMAGED' : 'GOOD';

                $chanIds = $this->getWebsiteChannelIds();
                $chanPlaceholders = implode(',', array_fill(0, count($chanIds), '?'));

                $chanStockRows = Database::fetchAll(
                    "SELECT * FROM stock_balances 
                     WHERE variant_id = ? AND location_type = 'CHANNEL' AND location_id IN ($chanPlaceholders)
                       AND concern_id = " . (int)currentConcernId() . " AND stock_state = ? AND quantity > 0
                     ORDER BY quantity DESC",
                    array_merge([$variantId], $chanIds, [$stockState])
                );

                $totalStockAvailable = 0.0;
                foreach ($chanStockRows as $csRow) {
                    $totalStockAvailable += (float)$csRow['quantity'];
                }

                if ($totalStockAvailable < $qty) {
                    $pv = Database::fetchOne("SELECT sku FROM product_variants WHERE id = ?", [$variantId]);
                    $sku = $pv['sku'] ?? "#$variantId";
                    throw new RuntimeException("Insufficient {$stockState} stock for SKU '{$sku}'. Requested: {$qty}, Available: {$totalStockAvailable}");
                }

                $rawQrCodes = $it['scanned_qr_codes'] ?? '[]';
                $scannedQrCodes = is_array($rawQrCodes) ? $rawQrCodes : json_decode((string)$rawQrCodes, true);
                if (!is_array($scannedQrCodes)) {
                    throw new InvalidArgumentException('Scanned product QR data is invalid.');
                }
                $scannedQrCodes = array_values(array_unique(array_filter(array_map(
                    static fn($qr): string => trim((string)$qr),
                    $scannedQrCodes
                ), static fn(string $qr): bool => $qr !== '')));
                if (count($scannedQrCodes) > $qty || array_filter($scannedQrCodes, static fn(string $qr): bool => strlen($qr) > 255)) {
                    throw new InvalidArgumentException('Scanned product QR tags do not match the selected return quantity.');
                }

                $goodQty = ($classification === 'GOOD') ? $qty : 0;
                $damQty  = ($classification === 'DAMAGED') ? $qty : 0;
                $defQty  = ($classification === 'DEFECTIVE') ? $qty : 0;

                Database::execute(
                    "INSERT INTO warehouse_return_items (warehouse_return_id, variant_id, returned_qty, good_qty, damaged_qty, defective_qty, classification, scanned_qr_codes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$returnId, $variantId, $qty, $goodQty, $damQty, $defQty, $classification, json_encode($scannedQrCodes)]
                );
                $returnItemId = (int)Database::lastInsertId();
                if ($scannedQrCodes) {
                    $itemQrCodes[$returnItemId] = $scannedQrCodes;
                }

                $remainingToDeduct = (float)$qty;
                foreach ($chanStockRows as $csRow) {
                    if ($remainingToDeduct <= 0) break;
                    $availInChan = (float)$csRow['quantity'];
                    $deductFromThisChan = min($availInChan, $remainingToDeduct);
                    $targetLocationId = (int)$csRow['location_id'];

                    StockService::decrease(
                        variantId: $variantId,
                        locationType: 'CHANNEL',
                        locationId: $targetLocationId,
                        stockState: $stockState,
                        quantity: $deductFromThisChan,
                        transactionType: 'TRANSFER_OUT',
                        referenceType: 'warehouse_returns',
                        referenceId: $returnId,
                        userId: $userId,
                        notes: "Website Return to Warehouse #{$returnNumber} ({$classification})"
                    );

                    $remainingToDeduct -= $deductFromThisChan;
                }

                $processedCount++;
            }

            if ($processedCount === 0) {
                throw new InvalidArgumentException("No valid items with quantity > 0 were provided.");
            }
            $notesData['item_qr_codes'] = $itemQrCodes;
            Database::execute(
                "UPDATE warehouse_returns SET notes = ? WHERE id = ?",
                [json_encode($notesData), $returnId]
            );

            if (class_exists('AuditService')) {
                AuditService::log('WEBSITE', 'RETURN_TO_WAREHOUSE_DISPATCH', 'warehouse_returns', $returnId, null, [
                    'return_number' => $returnNumber,
                    'channel_id'    => $channelId,
                    'warehouse_id'  => $warehouseId,
                    'items_count'   => $processedCount,
                    'outward'       => $notesData
                ]);
            }

            Database::commit();
            return [
                'id'            => $returnId,
                'return_number' => $returnNumber,
                'items_count'   => $processedCount
            ];
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Helper: Get Complete Details of a Website Return Consignment
     */
    private function getWebsiteWarehouseReturnDetails(int $returnId): ?array {
        $ret = Database::fetchOne(
            "SELECT wr.*, 
                    w.name as warehouse_name, w.code as warehouse_code, w.address as warehouse_address, w.phone as warehouse_phone,
                    sc.name as channel_name, sc.code as channel_code,
                    u.full_name as inspected_by_name
             FROM warehouse_returns wr
             JOIN warehouses w ON wr.warehouse_id = w.id
             LEFT JOIN sales_channels sc ON (wr.source_id = sc.id)
             LEFT JOIN users u ON wr.inspected_by = u.id
             WHERE wr.id = ? OR wr.return_number = ?",
            [$returnId, (string)$returnId]
        );

        if (!$ret) return null;

        // Unpack outward logistics JSON if present
        $outward = [
            'carrier_name'  => 'GRM Logistics / Handover',
            'vehicle_no'    => '',
            'driver_name'   => '',
            'driver_phone'  => '',
            'tracking_no'   => '',
            'carton_count'  => 1,
            'dispatch_date' => $ret['created_at'],
            'remarks'       => ''
        ];
        $itemQrCodes = [];
        if (!empty($ret['notes'])) {
            $decoded = json_decode($ret['notes'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $outward = array_merge($outward, $decoded);
                $itemQrCodes = is_array($decoded['item_qr_codes'] ?? null) ? $decoded['item_qr_codes'] : [];
                $ret['notes'] = $outward['remarks'] ?? '';
            }
        }
        unset($outward['item_qr_codes']);
        $ret['outward_logistics'] = $outward;

        $items = Database::fetchAll(
            "SELECT wri.*, 
                    pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.image_url as variant_image,
                    p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                    p.category_code, p.subcategory_code, p.hsn_code,
                    sz.name as size_name, cl.name as color_name, cat.name as category_name
             FROM warehouse_return_items wri
             JOIN product_variants pv ON wri.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors cl ON pv.color_id = cl.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             WHERE wri.warehouse_return_id = ?",
            [$ret['id']]
        );

        $totalReturnedQty = 0;
        $totalEstimatedValue = 0.0;

        foreach ($items as &$it) {
            $totalReturnedQty += (int)$it['returned_qty'];
            $qrCodes = json_decode((string)($it['scanned_qr_codes'] ?? ''), true);
            if (!is_array($qrCodes)) {
                $qrCodes = $itemQrCodes[(string)$it['id']] ?? [];
            }
            $it['scanned_qr_codes'] = array_values(array_filter(
                array_map('strval', $qrCodes),
                static fn(string $qr): bool => trim($qr) !== ''
            ));
            if (!$it['scanned_qr_codes'] && (int)$it['returned_qty'] === 1) {
                $channelIds = $this->getWebsiteChannelIds();
                $channelId = (int)($ret['source_id'] ?? 0);
                if ($channelId > 0 && in_array($channelId, $channelIds, true)) {
                    $pieceTags = $this->resolveChannelPieceTags($channelId, (int)$it['variant_id'], $channelIds, 'WEBSITE')['all'];
                    if (count($pieceTags) === 1) {
                        $it['scanned_qr_codes'] = $pieceTags;
                    }
                }
            }
            $it['image'] = !empty($it['variant_image']) ? $it['variant_image'] : ($it['product_image'] ?? null);
            $it['unit_price'] = (float)($it['selling_price'] ?? ($it['mrp'] ?? 0));
            $it['line_total'] = $it['unit_price'] * (int)$it['returned_qty'];
            $totalEstimatedValue += $it['line_total'];
        }

        return [
            'return'              => $ret,
            'items'               => $items,
            'totalReturnedQty'    => $totalReturnedQty,
            'totalEstimatedValue' => $totalEstimatedValue,
        ];
    }

    /**
     * Helper: Resolve exact piece QR tags for channel inventory item
     */
    private function resolveChannelPieceTags(int $channelId, int $variantId, array $chanIds, string $channelName = 'WEBSITE'): array {
        $placeholders = implode(',', array_fill(0, count($chanIds), '?'));

        // 1. Inward Allocation Tags (in allocation order)
        $allocItems = Database::fetchAll(
            "SELECT ai.selected_qr_codes, ai.received_qr_codes, ai.received_qty, wa.allocation_number
             FROM allocation_items ai
             JOIN warehouse_allocations wa ON ai.allocation_id = wa.id
             WHERE ai.variant_id = ? 
               AND wa.destination_type = 'CHANNEL' AND wa.destination_id IN ($placeholders)
             ORDER BY wa.id ASC, ai.id ASC",
            array_merge([$variantId], $chanIds)
        );

        $allAllocatedTags = [];
        $allAllocatedTagKeys = [];
        $allocRef = '';
        $parseTags = static function ($rawTags): array {
            $decoded = json_decode((string)$rawTags, true);
            $tags = is_array($decoded) ? $decoded : explode(',', (string)$rawTags);
            $parsedTags = [];
            foreach ($tags as $tag) {
                $tag = is_array($tag)
                    ? ($tag['tag_code'] ?? ($tag['qr_code'] ?? ''))
                    : (string)$tag;
                $tag = trim($tag);
                if ($tag !== '') {
                    $parsedTags[strtoupper($tag)] = $tag;
                }
            }
            return array_values($parsedTags);
        };
        foreach ($allocItems as $ai) {
            if (empty($allocRef) && !empty($ai['allocation_number'])) {
                $allocRef = $ai['allocation_number'];
            }
            $receivedTags = $parseTags($ai['received_qr_codes'] ?? '');
            $allocationTags = $receivedTags ?: $parseTags($ai['selected_qr_codes'] ?? '');
            if ($receivedTags && (int)($ai['received_qty'] ?? 0) > 0) {
                $allocationTags = array_slice($receivedTags, 0, (int)$ai['received_qty']);
            }
            foreach ($allocationTags as $tag) {
                $tagKey = strtoupper($tag);
                if (!isset($allAllocatedTagKeys[$tagKey])) {
                    $allAllocatedTagKeys[$tagKey] = true;
                    $allAllocatedTags[] = $tag;
                }
            }
        }

        // If no explicit tags were saved in allocation_items, generate dynamic Inward piece QR tags
        // using the exact same rule as Warehouse Inward & Website Inventory Detail
        if (empty($allAllocatedTags)) {
            $vInfo = Database::fetchOne(
                "SELECT pv.*, p.id as product_id, p.code as product_code, p.category_code, p.subcategory_code, p.sub_subcategory_code, p.qr_code_prefix,
                        cat.name as category_name, subcat.name as subcategory_name, subsub.name as sub_subcategory_name
                 FROM product_variants pv
                 JOIN products p ON pv.product_id = p.id
                 LEFT JOIN categories cat ON p.category_id = cat.id
                 LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
                 LEFT JOIN sub_subcategories subsub ON p.sub_subcategory_id = subsub.id
                 WHERE pv.id = ?",
                [$variantId]
            );

            if ($vInfo) {
                $c1 = !empty($vInfo['category_name']) ? trim($vInfo['category_name']) : (!empty($vInfo['category_code']) ? trim($vInfo['category_code']) : '');
                $c2 = !empty($vInfo['subcategory_name']) ? trim($vInfo['subcategory_name']) : (!empty($vInfo['subcategory_code']) ? trim($vInfo['subcategory_code']) : '');
                $c3 = !empty($vInfo['sub_subcategory_name']) ? trim($vInfo['sub_subcategory_name']) : (!empty($vInfo['sub_subcategory_code']) ? trim($vInfo['sub_subcategory_code']) : '');

                $prefix = '';
                if ($c1 !== '') $prefix .= strtoupper($c1[0]);
                if ($c2 !== '') $prefix .= strtoupper($c2[0]);
                if ($c3 !== '') $prefix .= strtoupper($c3[0]);

                if (strlen($prefix) < 2 && !empty($vInfo['qr_code_prefix'])) {
                    $clean = preg_replace('/[^A-Za-z0-9]/', '', $vInfo['qr_code_prefix']);
                    $prefix = strtoupper(substr($clean, 0, 3));
                }
                if (empty($prefix)) {
                    $prefix = 'TTS';
                }

                $nowMonth = sprintf('%02d', date('n'));
                $nowYear  = date('y');

                $allSiblingVariants = Database::fetchAll(
                    "SELECT id FROM product_variants WHERE product_id = ? ORDER BY id ASC",
                    [$vInfo['product_id']]
                );
                $varOffset = 0;
                foreach ($allSiblingVariants as $pvRow) {
                    if ((int)$pvRow['id'] === (int)$variantId) {
                        break;
                    }
                    $varOffset += 20;
                }

                $balRow = Database::fetchOne(
                    "SELECT COALESCE(SUM(quantity), 0) as tot_qty FROM stock_balances 
                     WHERE variant_id = ? AND location_type = 'CHANNEL' AND location_id IN ($placeholders)
                       AND concern_id = " . (int)currentConcernId(),
                    array_merge([$variantId], $chanIds)
                );
                $totalStockCount = (int)($balRow['tot_qty'] ?? 0);
                $genCount = max(50, $totalStockCount + 20);

                for ($p = 1; $p <= $genCount; $p++) {
                    $tagSeq = $varOffset + $p;
                    $tagCode = $prefix . $nowMonth . $nowYear . sprintf("%02d", $tagSeq);
                    $allAllocatedTags[] = $tagCode;
                }
            }
        }

        // 2. Sold piece tags
        $soldRows = Database::fetchAll(
            "SELECT si.scanned_qr_codes
             FROM sale_items si
             JOIN sales s ON si.sale_id = s.id
             WHERE si.variant_id = ?
               AND (s.sales_channel = ? OR s.sale_type = ? OR s.shop_id IN ($placeholders))
               AND s.status NOT IN ('CANCELLED')
               AND si.scanned_qr_codes IS NOT NULL AND si.scanned_qr_codes != ''",
            array_merge([$variantId, $channelName, $channelName], $chanIds)
        );

        $soldTags = [];
        foreach ($soldRows as $sr) {
            $parsed = json_decode($sr['scanned_qr_codes'], true);
            if (is_array($parsed)) {
                $tags = $parsed;
            } else {
                $tags = explode(',', $sr['scanned_qr_codes']);
            }
            foreach ($tags as $t) {
                $t = trim($t);
                if ($t && !in_array($t, $soldTags)) {
                    $soldTags[] = $t;
                }
            }
        }

        // 3. Exchange Dispatched Tags (New replacement pieces sent out to customer)
        $exchangeRows = Database::fetchAll(
            "SELECT ei.item_type, ei.piece_tag, e.status as exchange_status
             FROM exchange_items ei
             JOIN exchanges e ON ei.exchange_id = e.id
             LEFT JOIN sales s ON e.sale_id = s.id
             WHERE ei.variant_id = ?
               AND (e.source_type = ? OR s.sales_channel = ? OR s.notes LIKE ? OR s.shop_id IN ($placeholders))
               AND e.status NOT IN ('CANCELLED')
               AND ei.piece_tag IS NOT NULL AND ei.piece_tag != ''",
            array_merge([$variantId, $channelName, $channelName, '%' . $channelName . '%'], $chanIds)
        );

        $exchangeDispatchedTags = [];
        $restockedTags = [];
        foreach ($exchangeRows as $er) {
            $pTags = array_map('trim', explode(',', $er['piece_tag']));
            foreach ($pTags as $pt) {
                if (!$pt) continue;
                if ($er['item_type'] === 'NEW') {
                    if (!in_array($pt, $exchangeDispatchedTags)) {
                        $exchangeDispatchedTags[] = $pt;
                    }
                } elseif ($er['item_type'] === 'OLD') {
                    if (!in_array($pt, $restockedTags)) {
                        $restockedTags[] = $pt;
                    }
                }
            }
        }

        // 4. Returns Restocked / Returned Damaged Tags
        $returnsRows = Database::fetchAll(
            "SELECT ri.condition_type, ri.item_condition, ri.destination, r.notes as return_notes, si.scanned_qr_codes
             FROM return_items ri
             JOIN returns r ON ri.return_id = r.id
             LEFT JOIN sale_items si ON ri.sale_item_id = si.id
             LEFT JOIN sales s ON r.sale_id = s.id
             WHERE ri.variant_id = ?
               AND (r.source_type = ? OR s.sales_channel = ? OR s.notes LIKE ? OR s.shop_id IN ($placeholders))
               AND r.status NOT IN ('CANCELLED')",
            array_merge([$variantId, $channelName, $channelName, '%' . $channelName . '%'], $chanIds)
        );

        $returnedDamagedTags = [];
        foreach ($returnsRows as $rr) {
            $cond = strtoupper(trim($rr['item_condition'] ?? $rr['condition_type'] ?? 'GOOD'));
            $tagsToProcess = [];
            if (!empty($rr['scanned_qr_codes'])) {
                $decoded = json_decode($rr['scanned_qr_codes'], true);
                if (is_array($decoded)) {
                    $tagsToProcess = $decoded;
                } else {
                    $tagsToProcess = explode(',', $rr['scanned_qr_codes']);
                }
            }
            foreach ($tagsToProcess as $pt) {
                $pt = trim($pt);
                if (!$pt) continue;
                if ($cond === 'GOOD') {
                    if (!in_array($pt, $restockedTags)) $restockedTags[] = $pt;
                } else {
                    if (!in_array($pt, $returnedDamagedTags)) $returnedDamagedTags[] = $pt;
                }
            }
        }

        // 5. Quarantined Damaged piece tags
        $damagedRows = Database::fetchAll(
            "SELECT tag_code
             FROM channel_damages
             WHERE variant_id = ?
               AND channel_id IN ($placeholders)
               AND status NOT IN ('REPAIRED', 'RESTOCKED', 'RESOLVED')
               AND tag_code IS NOT NULL AND tag_code != ''",
            array_merge([$variantId], $chanIds)
        );

        $damagedTags = [];
        foreach ($damagedRows as $dr) {
            $t = trim($dr['tag_code'] ?? '');
            if ($t && !in_array($t, $damagedTags)) {
                $damagedTags[] = $t;
            }
        }
        foreach ($returnedDamagedTags as $rdt) {
            if (!in_array($rdt, $damagedTags)) {
                $damagedTags[] = $rdt;
            }
        }

        // 6. Returned to Warehouse piece tags
        $returnedRows = Database::fetchAll(
            "SELECT wr.notes, wri.id as return_item_id, wri.scanned_qr_codes, wri.returned_qty, wri.classification
             FROM warehouse_return_items wri
             JOIN warehouse_returns wr ON wri.warehouse_return_id = wr.id
             WHERE wri.variant_id = ?
               AND (wr.source_id IN ($placeholders) OR wr.source_type = ?)
               AND wr.status NOT IN ('CANCELLED')",
            array_merge([$variantId], $chanIds, [$channelName])
        );
        $returnedTags = [];
        $unassignedReturnedGoodCount = 0;
        $unassignedReturnedDamagedCount = 0;

        foreach ($returnedRows as $rr) {
            $hasQr = false;
            $savedTags = json_decode((string)($rr['scanned_qr_codes'] ?? ''), true);
            if (!is_array($savedTags)) {
                $returnNotes = json_decode((string)($rr['notes'] ?? ''), true);
                $savedTags = is_array($returnNotes)
                    ? ($returnNotes['item_qr_codes'][(string)$rr['return_item_id']] ?? [])
                    : [];
            }
            if (is_array($savedTags)) {
                foreach ($savedTags as $savedTag) {
                    $savedTag = trim((string)$savedTag);
                    if ($savedTag !== '' && !in_array($savedTag, $returnedTags, true)) {
                        $returnedTags[] = $savedTag;
                        $hasQr = true;
                    }
                }
            }
            if (!empty($rr['notes'])) {
                $notesStr = $rr['notes'];
                $decodedNotes = json_decode($notesStr, true);
                if (is_array($decodedNotes)) {
                    $notesStr = '';
                } elseif (str_starts_with($notesStr, 'QR: ')) {
                    $notesStr = substr($notesStr, 4);
                }
                $tags = array_map('trim', explode(',', $notesStr));
                foreach ($tags as $t) {
                    if ($t && preg_match('/^[A-Za-z0-9\-_]+$/', $t) && (str_starts_with($t, 'KB') || str_starts_with($t, 'TTS') || str_contains($t, '-'))) {
                        if (!in_array($t, $returnedTags)) {
                            $returnedTags[] = $t;
                            $hasQr = true;
                        }
                    }
                }
            }
            if (!$hasQr) {
                if (($rr['classification'] ?? 'GOOD') === 'DAMAGED') {
                    $unassignedReturnedDamagedCount += (int)($rr['returned_qty'] ?? 1);
                } else {
                    $unassignedReturnedGoodCount += (int)($rr['returned_qty'] ?? 1);
                }
            }
        }

        // 7. Available pieces = Allocated minus (Sold/Exchanged Out + Damaged + Returned to Warehouse), with Restocked items restored
        $availableTags = [];
        foreach ($allAllocatedTags as $tag) {
            if (in_array($tag, $damagedTags)) {
                continue;
            }
            if (in_array($tag, $returnedTags)) {
                continue;
            }
            if (in_array($tag, $exchangeDispatchedTags)) {
                continue;
            }
            if (in_array($tag, $soldTags) && !in_array($tag, $restockedTags)) {
                continue;
            }
            $availableTags[] = $tag;
        }

        // Deduct unassigned warehouse returns from available tags (from the end of available list)
        if ($unassignedReturnedGoodCount > 0 && count($availableTags) > 0) {
            for ($k = 0; $k < $unassignedReturnedGoodCount && count($availableTags) > 0; $k++) {
                $popped = array_pop($availableTags);
                if ($popped && !in_array($popped, $returnedTags)) {
                    $returnedTags[] = $popped;
                }
            }
        }

        return [
            'all'                 => $allAllocatedTags,
            'sold'                => array_values(array_unique(array_merge($soldTags, $exchangeDispatchedTags))),
            'exchange_dispatched' => $exchangeDispatchedTags,
            'restocked'           => $restockedTags,
            'damaged'             => $damagedTags,
            'returned'            => $returnedTags,
            'available'           => $availableTags,
            'inward_ref'          => $allocRef,
        ];
    }

    /**
     * Helper: Get Complete Variant Details for Channel Inventory Modal
     */
    private function getChannelInventoryItem(int $channelId, int $variantId, string $stockState = 'AVAILABLE'): ?array {
        $chanIds = $this->getWebsiteChannelIds();
        $placeholders = implode(',', array_fill(0, count($chanIds), '?'));
        $targetStockState = strtoupper(trim($stockState ?: 'AVAILABLE'));
        if (!in_array($targetStockState, ['AVAILABLE', 'DAMAGED', 'ALL'])) {
            $targetStockState = 'AVAILABLE';
        }

        // Sum up stock balances across all website channels for this variant
        $availRow = Database::fetchOne(
            "SELECT COALESCE(SUM(quantity), 0) as tot_qty FROM stock_balances 
             WHERE variant_id = ? AND location_type = 'CHANNEL' AND location_id IN ($placeholders)
               AND concern_id = " . (int)currentConcernId() . " AND stock_state = 'AVAILABLE'",
            array_merge([$variantId], $chanIds)
        );
        $availStock = (float)($availRow['tot_qty'] ?? 0);

        $dmgRow = Database::fetchOne(
            "SELECT COALESCE(SUM(quantity), 0) as tot_qty FROM stock_balances 
             WHERE variant_id = ? AND location_type = 'CHANNEL' AND location_id IN ($placeholders)
               AND concern_id = " . (int)currentConcernId() . " AND stock_state = 'DAMAGED'",
            array_merge([$variantId], $chanIds)
        );
        $dmgStock = (float)($dmgRow['tot_qty'] ?? 0);

        $sql = "SELECT pv.*, pv.id as variant_id,
                       pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.purchase_price, pv.production_cost, 
                       pv.weight as variant_weight, pv.image_url as variant_image,
                       p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                       p.category_code, p.subcategory_code, p.sub_subcategory_code, p.qr_code_prefix,
                       p.description, p.material, p.hsn_code, p.gst_percentage, p.min_stock, p.max_stock, p.source_type,
                       s.name as size_name, c.name as color_name, 
                       cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name,
                       b.name as brand_name
                FROM product_variants pv
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
                LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN sizes s ON pv.size_id = s.id
                LEFT JOIN colors c ON pv.color_id = c.id
                WHERE pv.id = ? LIMIT 1";

        $item = Database::fetchOne($sql, [$variantId]);
        if (!$item) return null;

        $item['available_quantity'] = $availStock;
        $item['damaged_quantity'] = $dmgStock;
        $item['stock_state'] = $targetStockState;
        $item['quantity'] = ($targetStockState === 'DAMAGED') ? $dmgStock : $availStock;
        $item['selling_price'] = (float)($item['selling_price'] ?? 0);
        $item['mrp'] = (float)($item['mrp'] ?? 0);

        $item['transactions'] = Database::fetchAll(
            "SELECT st.*, u.full_name as user_name
             FROM stock_transactions st
             LEFT JOIN users u ON st.user_id = u.id
             WHERE st.location_type = 'CHANNEL' AND st.location_id IN ($placeholders) AND st.variant_id = ?
               AND st.concern_id = " . (int)currentConcernId() . "
             ORDER BY st.id DESC LIMIT 15",
            array_merge($chanIds, [$variantId])
        );

        $pieceRes = $this->resolveChannelPieceTags($channelId, $variantId, $chanIds, 'WEBSITE');
        $inwardRef = !empty($pieceRes['inward_ref']) ? $pieceRes['inward_ref'] : 'Stock Allocation';

        $pName = $item['product_name'] ?? 'Product';
        $pCode = $item['product_code'] ?? ($item['code'] ?? '');
        $catName = $item['category_name'] ?? 'General';
        $subcatName = $item['subcategory_name'] ?? '';
        $szName = $item['size_name'] ?? 'Free';
        $clName = $item['color_name'] ?? 'Std';
        $sPrice = (float)$item['selling_price'];
        $img = !empty($item['variant_image']) ? $item['variant_image'] : ($item['product_image'] ?? '');
        $sku = $item['sku'] ?? '';

        // Determine piece tags and quantity based on target stock state
        $pieces = [];
        if ($targetStockState === 'DAMAGED') {
            $targetQty = (int)$dmgStock;
            $tagsToUse = $pieceRes['damaged'];
            for ($i = 0; $i < $targetQty; $i++) {
                $tag = $tagsToUse[$i] ?? sprintf('%s-DMG-%02d', $sku, $i + 1);
                $pieces[] = [
                    'piece_number'     => $i + 1,
                    'tag_code'         => $tag,
                    'qr_payload'       => $tag,
                    'status'           => 'DAMAGED',
                    'product_name'     => $pName,
                    'product_code'     => $pCode,
                    'sku'              => $sku,
                    'category_name'    => $catName,
                    'subcategory_name' => $subcatName,
                    'size_name'        => $szName,
                    'color_name'       => $clName,
                    'selling_price'    => $sPrice,
                    'product_image'    => $img,
                    'inward_reference' => $inwardRef,
                ];
            }
            $item['quantity'] = $targetQty;
        } elseif ($targetStockState === 'AVAILABLE') {
            $targetQty = (int)$availStock;
            $tagsToUse = $pieceRes['available'];
            for ($i = 0; $i < $targetQty; $i++) {
                $tag = $tagsToUse[$i] ?? sprintf('%s-%02d', $sku, $i + 1);
                $pieces[] = [
                    'piece_number'     => $i + 1,
                    'tag_code'         => $tag,
                    'qr_payload'       => $tag,
                    'status'           => 'AVAILABLE',
                    'product_name'     => $pName,
                    'product_code'     => $pCode,
                    'sku'              => $sku,
                    'category_name'    => $catName,
                    'subcategory_name' => $subcatName,
                    'size_name'        => $szName,
                    'color_name'       => $clName,
                    'selling_price'    => $sPrice,
                    'product_image'    => $img,
                    'inward_reference' => $inwardRef,
                ];
            }
            $item['quantity'] = $targetQty;
        } else { // ALL
            foreach ($pieceRes['all'] as $idx => $tag) {
                $status = 'AVAILABLE';
                if (in_array($tag, $pieceRes['sold'])) {
                    $status = 'SOLD';
                } elseif (in_array($tag, $pieceRes['damaged'])) {
                    $status = 'DAMAGED';
                } elseif (in_array($tag, $pieceRes['returned'])) {
                    $status = 'RETURNED';
                }
                $pieces[] = [
                    'piece_number'     => $idx + 1,
                    'tag_code'         => $tag,
                    'qr_payload'       => $tag,
                    'status'           => $status,
                    'product_name'     => $pName,
                    'product_code'     => $pCode,
                    'sku'              => $sku,
                    'category_name'    => $catName,
                    'subcategory_name' => $subcatName,
                    'size_name'        => $szName,
                    'color_name'       => $clName,
                    'selling_price'    => $sPrice,
                    'product_image'    => $img,
                    'inward_reference' => $inwardRef,
                ];
            }
        }

        $item['individual_pieces'] = $pieces;
        $item['inward_reference'] = $inwardRef;

        return $item;
    }
}
